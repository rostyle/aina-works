<?php
// 出力バッファリングを開始してクリーンなJSONレスポンスを確保
ob_start();

// エラー表示を無効にしてHTMLエラーを防ぐ
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', dirname(__DIR__) . '/logs/chat_debug.log');

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
ini_set('log_errors', 1);
ini_set('error_log', dirname(__DIR__) . '/logs/chat_debug.log');

require_once '../config/config.php';

// 出力バッファを完全にクリアしてヘッダー問題を回避
while (ob_get_level()) {
    ob_end_clean();
}
ob_start();


// ヘッダーが送信済みでない場合のみ設定
if (!headers_sent()) {
    header('Content-Type: application/json');
}

// ログイン確認
if (!isLoggedIn()) {
    ErrorHandler::jsonAuthError();
}

// CSRFトークン検証
if (!verifyCsrfToken(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '')) {
    ErrorHandler::jsonCsrfError();
}

try {
    $db = Database::getInstance();
    $currentUser = getCurrentUser();
    
    $roomId = (int)(isset($_POST['room_id']) ? $_POST['room_id'] : 0);
    $message = trim(isset($_POST['message']) ? $_POST['message'] : '');
    
    // バリデーション
    $validation = new ValidationResult();
    
    $error = validatePositiveInteger($roomId, 'チャットルーム');
    if ($error) {
        $validation->addError('room_id', $error);
    }
    
    $error = validateRequired($message, 'メッセージ');
    if ($error) {
        $validation->addError('message', $error);
    }
    
    $error = validateLength($message, null, 1000, 'メッセージ');
    if ($error) {
        $validation->addError('message', $error);
    }
    
    if (!$validation->isValid) {
        ErrorHandler::jsonValidationError($validation);
    }
    
    // チャットルームの存在確認と権限チェック
    $chatRoom = $db->selectOne("
        SELECT * FROM chat_rooms 
        WHERE id = ? AND (user1_id = ? OR user2_id = ?)
    ", [$roomId, $currentUser['id'], $currentUser['id']]);
    
    if (!$chatRoom) {
        ErrorHandler::jsonNotFoundError('チャットルーム');
    }
    
    // メッセージをデータベースに保存
    $messageId = $db->insert("
        INSERT INTO chat_messages (room_id, sender_id, message, created_at) 
        VALUES (?, ?, ?, NOW())
    ", [$roomId, $currentUser['id'], $message]);
    
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
                $subject = "【AiNA Works】新しいチャットメッセージが届きました";
                $emailMessage = "こんにちは、{$recipient['full_name']}さん\n\n";
                $emailMessage .= "{$currentUser['full_name']}さんからチャットメッセージが届きました。\n\n";
                $emailMessage .= "メッセージ:\n";
                $emailMessage .= $message . "\n\n";
                $emailMessage .= "返信はチャットルームで行ってください。";
                
                $actionUrl = url('chats', true);
                sendNotificationMail($recipient['email'], $subject, $emailMessage, $actionUrl, 'チャットを確認する');
            }
        } catch (Exception $e) {
            error_log('チャット通知メール送信エラー: ' . $e->getMessage());
            // メール送信エラーでもチャット送信は成功として処理
        }
        
        ErrorHandler::jsonSuccess('メッセージを送信しました', [
            'id' => $sentMessage['id'],
            'sender_id' => $sentMessage['sender_id'],
            'message' => $sentMessage['message'],
            'sender_name' => $sentMessage['sender_name'],
            'sender_image' => $sentMessage['sender_image'],
            'time' => date('H:i', strtotime($sentMessage['created_at'])),
            'is_read' => false
        ]);
    } else {
        ErrorHandler::jsonError('メッセージの送信に失敗しました', 500, null, 'SEND_FAILED');
    }
    
} catch (Exception $e) {
    ErrorHandler::handleException($e, 'Chat message send error: ' . $e->getMessage());
}