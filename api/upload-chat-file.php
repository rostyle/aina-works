<?php
// 出力バッファリングを開始してクリーンなJSONレスポンスを確保
ob_start();

// エラー表示を無効にしてHTMLエラーを防ぐ
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once '../config/config.php';

// 出力バッファを完全にクリアしてヘッダー問題を回避
while (ob_get_level()) {
    ob_end_clean();
}
ob_start();

// シャットダウンハンドラーで致命的なエラーをキャッチ
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        if (ob_get_level()) ob_clean();
        if (!headers_sent()) header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'PHP Fatal Error: ' . $error['message'],
            'file' => $error['file'],
            'line' => $error['line'],
            'error_code' => 'FATAL_ERROR'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
});

// ヘッダーが送信済みでない場合のみ設定
if (!headers_sent()) {
    header('Content-Type: application/json');
}

// ログイン確認
if (!isLoggedIn()) {
    jsonResponse(['error' => 'ログインが必要です'], 401);
}

// CSRFトークン検証
if (!verifyCsrfToken(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '')) {
    jsonResponse(['error' => '不正なリクエストです'], 403);
}

try {
    $db = Database::getInstance();
    $currentUser = getCurrentUser();
    
    $roomId = (int)(isset($_POST['room_id']) ? $_POST['room_id'] : 0);
    
    // バリデーション
    if (!$roomId) {
        jsonResponse(['error' => 'チャットルームが指定されていません'], 400);
    }
    
    // チャットルームの存在確認と権限チェック
    $chatRoom = $db->selectOne("
        SELECT * FROM chat_rooms 
        WHERE id = ? AND (user1_id = ? OR user2_id = ?)
    ", [$roomId, $currentUser['id'], $currentUser['id']]);
    
    if (!$chatRoom) {
        jsonResponse(['error' => 'チャットルームが見つかりません'], 404);
    }
    
    // ファイルアップロードの確認
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        $errorMessages = [
            UPLOAD_ERR_INI_SIZE => 'ファイルサイズが大きすぎます',
            UPLOAD_ERR_FORM_SIZE => 'ファイルサイズが大きすぎます',
            UPLOAD_ERR_PARTIAL => 'ファイルが部分的にしかアップロードされませんでした',
            UPLOAD_ERR_NO_FILE => 'ファイルが選択されていません',
            UPLOAD_ERR_NO_TMP_DIR => '一時ディレクトリが見つかりません',
            UPLOAD_ERR_CANT_WRITE => 'ファイルの書き込みに失敗しました',
            UPLOAD_ERR_EXTENSION => 'ファイルアップロードが拡張機能によって停止されました'
        ];
        
        $errorCode = isset($_FILES['file']['error']) ? $_FILES['file']['error'] : UPLOAD_ERR_NO_FILE;
        $errorMessage = isset($errorMessages[$errorCode]) ? $errorMessages[$errorCode] : 'ファイルアップロードエラーが発生しました';
        jsonResponse(['error' => $errorMessage], 400);
    }
    
    $file = $_FILES['file'];
    $fileName = $file['name'];
    $fileSize = $file['size'];
    $fileTmpName = $file['tmp_name'];
    $fileType = $file['type'];
    
    // ファイルサイズ制限（10MB）
    $maxFileSize = 10 * 1024 * 1024; // 10MB
    if ($fileSize > $maxFileSize) {
        jsonResponse(['error' => 'ファイルサイズは10MB以下にしてください'], 400);
    }
    
    // ファイル拡張子の取得
    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    
    // 許可されたファイル形式の確認
    $allowedImageTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $allowedDocumentTypes = ['pdf'];
    $allowedTypes = array_merge($allowedImageTypes, $allowedDocumentTypes);
    
    if (!in_array($fileExtension, $allowedTypes)) {
        jsonResponse(['error' => '画像（JPG, PNG, GIF, WebP）またはPDFファイルのみアップロードできます'], 400);
    }
    
    // 動画ファイルの除外（念のため）
    $videoExtensions = ['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm', 'mkv'];
    if (in_array($fileExtension, $videoExtensions)) {
        jsonResponse(['error' => '動画ファイルはアップロードできません'], 400);
    }
    
    // ファイルタイプの判定
    $messageType = 'text';
    if (in_array($fileExtension, $allowedImageTypes)) {
        $messageType = 'image';
    } elseif (in_array($fileExtension, $allowedDocumentTypes)) {
        $messageType = 'document';
    }
    
    // アップロードディレクトリの作成
    $uploadDir = '../storage/app/uploads/chat/';
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            jsonResponse(['error' => 'アップロードディレクトリの作成に失敗しました'], 500);
        }
    }
    
    // ディレクトリの書き込み権限確認
    if (!is_writable($uploadDir)) {
        jsonResponse(['error' => 'アップロードディレクトリに書き込み権限がありません'], 500);
    }
    
    // 一意のファイル名を生成
    $uniqueFileName = uniqid() . '_' . time() . '.' . $fileExtension;
    $filePath = $uploadDir . $uniqueFileName;
    
    // ファイルを移動
    if (!move_uploaded_file($fileTmpName, $filePath)) {
        error_log("File upload failed: tmp_name={$fileTmpName}, target={$filePath}");
        jsonResponse(['error' => 'ファイルの保存に失敗しました'], 500);
    }
    
    // ファイルが正常に保存されたか確認
    if (!file_exists($filePath)) {
        error_log("Uploaded file does not exist: {$filePath}");
        jsonResponse(['error' => 'ファイルの保存確認に失敗しました'], 500);
    }
    
    // ファイルサイズの再確認
    $actualFileSize = filesize($filePath);
    if ($actualFileSize !== $fileSize) {
        error_log("File size mismatch: expected={$fileSize}, actual={$actualFileSize}");
        unlink($filePath); // 不正なファイルを削除
        jsonResponse(['error' => 'ファイルサイズの検証に失敗しました'], 500);
    }
    
    // 相対パスを保存（データベース用）
    $relativePath = 'storage/app/uploads/chat/' . $uniqueFileName;
    
    // メッセージをデータベースに保存
    $messageId = $db->insert("
        INSERT INTO chat_messages (room_id, sender_id, message, message_type, file_path, created_at) 
        VALUES (?, ?, ?, ?, ?, NOW())
    ", [$roomId, $currentUser['id'], $fileName, $messageType, $relativePath]);
    
    if ($messageId) {
        // チャットルームの更新時間を更新
        $db->update("
            UPDATE chat_rooms 
            SET updated_at = NOW() 
            WHERE id = ?
        ", [$roomId]);
        
        // 送信したメッセージの詳細を取得
        $sentMessage = $db->selectOne("
            SELECT cm.*, u.full_name as sender_name, u.profile_image as sender_image
            FROM chat_messages cm
            JOIN users u ON cm.sender_id = u.id
            WHERE cm.id = ?
        ", [$messageId]);
        
        // 相手にメール通知を送信
        try {
            $recipientId = ($chatRoom['user1_id'] == $currentUser['id']) ? $chatRoom['user2_id'] : $chatRoom['user1_id'];
            $recipient = $db->selectOne("
                SELECT email, full_name FROM users WHERE id = ?
            ", [$recipientId]);
            
            if ($recipient) {
                $subject = "【AiNA Works】新しいファイルがアップロードされました";
                $emailMessage = "こんにちは、{$recipient['full_name']}さん\n\n";
                $emailMessage .= "{$currentUser['full_name']}さんがファイルをアップロードしました。\n\n";
                $emailMessage .= "ファイル名: {$fileName}\n";
                $emailMessage .= "ファイルタイプ: " . ($messageType === 'image' ? '画像' : 'PDF') . "\n\n";
                $emailMessage .= "確認はチャットルームで行ってください。";
                
                $actionUrl = url('chats', true);
                sendNotificationMail($recipient['email'], $subject, $emailMessage, $actionUrl, 'チャットを確認する');
            }
        } catch (Exception $e) {
            error_log('チャット通知メール送信エラー: ' . $e->getMessage());
            // メール送信エラーでもチャット送信は成功として処理
        }
        
        jsonResponse([
            'success' => true,
            'message' => [
                'id' => $sentMessage['id'],
                'sender_id' => $sentMessage['sender_id'],
                'message' => $sentMessage['message'],
                'message_type' => $sentMessage['message_type'],
                'file_path' => $sentMessage['file_path'],
                'sender_name' => $sentMessage['sender_name'],
                'sender_image' => $sentMessage['sender_image'],
                'time' => date('H:i', strtotime($sentMessage['created_at'])),
                'is_read' => false
            ]
        ]);
    } else {
        // データベース保存に失敗した場合、アップロードしたファイルを削除
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        jsonResponse(['error' => 'メッセージの送信に失敗しました'], 500);
    }
    
} catch (Exception $e) {
    error_log("Chat file upload error: " . $e->getMessage());
    jsonResponse(['error' => 'システムエラーが発生しました'], 500);
}
