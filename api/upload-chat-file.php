<?php
// PHP 7.0+ Required (Uses Throwable and ?? operator)

/**
 * 送信処理中の予期せぬエラー（構文エラーや致命的エラー）をキャッチしてJSONで返す
 */
register_shutdown_function(function() {
    $error = error_get_last();
    // 致命的なエラーのみ処理
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        if (ob_get_level()) ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'message' => 'アップロード中に致命的なエラーが発生しました: ' . $error['message'],
            'debug' => [
                'file' => basename($error['file']),
                'line' => $error['line'],
                'type' => $error['type']
            ],
            'error_code' => 'FATAL_ERROR'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
});

// 出力バッファリング開始（途中の警告などがJSONを破壊するのを防ぐ）
ob_start();

// エラー表示設定（画面には出さず、JSONとして制御する）
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once '../config/config.php';

// ログイン確認
if (!isLoggedIn()) {
    if (ob_get_level()) ob_clean();
    ErrorHandler::jsonAuthError();
}

// CSRFトークン検証
if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    if (ob_get_level()) ob_clean();
    ErrorHandler::jsonCsrfError();
}

try {
    $db = Database::getInstance();
    $currentUser = getCurrentUser();
    
    $roomId = (int)($_POST['room_id'] ?? 0);
    
    // バリデーション
    if ($roomId <= 0) {
        if (ob_get_level()) ob_clean();
        ErrorHandler::jsonError('チャットルームが指定されていません', 400);
    }
    
    // チャットルームの存在確認と権限チェック
    $chatRoom = $db->selectOne("
        SELECT * FROM chat_rooms 
        WHERE id = ? AND (user1_id = ? OR user2_id = ?)
    ", [$roomId, $currentUser['id'], $currentUser['id']]);
    
    if (!$chatRoom) {
        if (ob_get_level()) ob_clean();
        ErrorHandler::jsonNotFoundError('チャットルーム');
    }
    
    // ファイルアップロードの確認
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        $errorCode = $_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE;
        if (ob_get_level()) ob_clean();
        ErrorHandler::jsonError('ファイルのアップロードに失敗しました (Error Code: ' . $errorCode . ')', 400);
    }
    
    $file = $_FILES['file'];
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    // 許可されたファイル形式の確認
    $allowedImageTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $allowedDocumentTypes = ['pdf'];
    
    $messageType = 'text';
    if (in_array($fileExtension, $allowedImageTypes)) {
        $messageType = 'image';
    } elseif (in_array($fileExtension, $allowedDocumentTypes)) {
        $messageType = 'document';
    } else {
        if (ob_get_level()) ob_clean();
        ErrorHandler::jsonError('画像またはPDFファイルのみアップロードできます', 400);
    }
    
    // アップロードディレクトリの作成と権限確認
    $uploadDir = '../storage/app/uploads/chat/';
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            if (ob_get_level()) ob_clean();
            ErrorHandler::jsonServerError('アップロードディレクトリの作成に失敗しました');
        }
    }
    
    // ファイル移動
    $uniqueFileName = uniqid() . '_' . time() . '.' . $fileExtension;
    $filePath = $uploadDir . $uniqueFileName;
    
    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        if (ob_get_level()) ob_clean();
        ErrorHandler::jsonServerError('ファイルの保存に失敗しました');
    }
    
    // 相対パスを保存
    $relativePath = 'chat/' . $uniqueFileName;
    
    // メッセージをデータベースに保存
    $messageId = $db->insert("
        INSERT INTO chat_messages (room_id, sender_id, message, message_type, file_path, created_at) 
        VALUES (?, ?, ?, ?, ?, NOW())
    ", [$roomId, $currentUser['id'], $file['name'], $messageType, $relativePath]);
    
    if (!$messageId) {
        if (file_exists($filePath)) unlink($filePath);
        if (ob_get_level()) ob_clean();
        ErrorHandler::jsonError('データベースへの保存に失敗しました', 500);
    }

    // チャットルーム更新
    $db->update("UPDATE chat_rooms SET updated_at = NOW() WHERE id = ?", [$roomId]);
    
    // 送信したメッセージの詳細を取得
    $sentMessage = $db->selectOne("
        SELECT cm.*, u.full_name as sender_name, u.profile_image as sender_image
        FROM chat_messages cm
        JOIN users u ON cm.sender_id = u.id
        WHERE cm.id = ?
    ", [$messageId]);
    
    // メール通知
    try {
        $recipientId = ($chatRoom['user1_id'] == $currentUser['id']) ? $chatRoom['user2_id'] : $chatRoom['user1_id'];
        $recipient = $db->selectOne("SELECT email, full_name FROM users WHERE id = ?", [$recipientId]);
        
        if ($recipient) {
            $subject = "【AiNA Works】新しいファイルが届きました";
            $emailMessage = "{$currentUser['full_name']}さんからファイルが届きました。\n\n";
            $emailMessage .= "ファイル名: " . $file['name'] . "\n";
            $actionUrl = url('chats', true);
            sendNotificationMail($recipient['email'], $subject, $emailMessage, $actionUrl, 'チャットを確認する');
        }
    } catch (Throwable $e) {
        error_log('Chat notification mail error (upload): ' . $e->getMessage());
    }
    
    // 成功
    if (ob_get_level()) ob_clean();
    ErrorHandler::jsonSuccess('ファイルを送信しました', [
        'id' => $sentMessage['id'],
        'sender_id' => $sentMessage['sender_id'],
        'message' => $sentMessage['message'],
        'message_type' => $sentMessage['message_type'],
        'file_path' => $sentMessage['file_path'],
        'sender_name' => $sentMessage['sender_name'],
        'sender_image' => $sentMessage['sender_image'],
        'time' => date('H:i', strtotime($sentMessage['created_at'])),
        'is_read' => false
    ]);

} catch (Throwable $e) {
    if (ob_get_level()) ob_clean();
    ErrorHandler::handleException($e, 'API Upload Chat File Error');
}
