<?php
require_once '../config/config.php';

// ログイン確認
if (!isLoggedIn()) {
    ErrorHandler::jsonAuthError();
}

// CSRFトークン検証
if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    ErrorHandler::jsonCsrfError();
}

try {
    $db = Database::getInstance();
    $currentUser = getCurrentUser();
    
    $recipientId = (int)($_POST['recipient_id'] ?? 0);
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    // バリデーション
    $validation = new ValidationResult();
    
    $error = validatePositiveInteger($recipientId, '受信者');
    if ($error) {
        $validation->addError('recipient_id', $error);
    }
    
    $error = validateRequired($subject, '件名');
    if ($error) {
        $validation->addError('subject', $error);
    }
    
    $error = validateLength($subject, null, 200, '件名');
    if ($error) {
        $validation->addError('subject', $error);
    }
    
    $error = validateRequired($message, 'メッセージ内容');
    if ($error) {
        $validation->addError('message', $error);
    }
    
    $error = validateLength($message, null, 5000, 'メッセージ内容');
    if ($error) {
        $validation->addError('message', $error);
    }
    
    if (!$validation->isValid) {
        ErrorHandler::jsonValidationError($validation);
    }
    
    // 受信者の存在確認
    $recipient = $db->selectOne("SELECT id, full_name FROM users WHERE id = ? AND is_active = 1", [$recipientId]);
    if (!$recipient) {
        ErrorHandler::jsonNotFoundError('受信者');
    }
    
    // 自分自身へのメッセージ送信を防ぐ
    if ($currentUser['id'] === $recipientId) {
        ErrorHandler::jsonError(Messages::USER_SELF_OPERATION, 400, null, 'OWN_RESOURCE');
    }
    
    // メッセージをデータベースに保存
    $messageId = $db->insert("
        INSERT INTO messages (sender_id, recipient_id, subject, message, created_at) 
        VALUES (?, ?, ?, ?, NOW())
    ", [$currentUser['id'], $recipientId, $subject, $message]);
    
    if ($messageId) {
        // 受信者にメール通知を送信
        try {
            $recipientUser = $db->selectOne(
                "SELECT email, full_name FROM users WHERE id = ?",
                [$recipientId]
            );
            
            if ($recipientUser) {
                $emailSubject = "【AiNA Works】新しいメッセージが届きました - {$subject}";
                $emailMessage = "こんにちは、{$recipientUser['full_name']}さん\n\n";
                $emailMessage .= "{$currentUser['full_name']}さんから新しいメッセージが届きました。\n\n";
                $emailMessage .= "件名: {$subject}\n\n";
                $emailMessage .= "メッセージ:\n";
                $emailMessage .= $message . "\n\n";
                $emailMessage .= "返信はプラットフォーム上で行ってください。";
                
                $actionUrl = url('chats', true);
                sendNotificationMail($recipientUser['email'], $emailSubject, $emailMessage, $actionUrl, 'メッセージを確認する');
            }
        } catch (Exception $e) {
            error_log('メッセージ通知メール送信エラー: ' . $e->getMessage());
            // メール送信エラーでもメッセージ送信は成功として処理
        }
        
        ErrorHandler::jsonSuccess('メッセージを送信しました', ['message_id' => $messageId]);
    } else {
        ErrorHandler::jsonError('メッセージの送信に失敗しました', 500, null, 'SEND_FAILED');
    }
    
} catch (Exception $e) {
    ErrorHandler::handleException($e, 'Message send error: ' . $e->getMessage());
}