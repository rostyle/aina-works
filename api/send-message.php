<?php
require_once '../config/config.php';

header('Content-Type: application/json');

// ログイン確認
if (!isLoggedIn()) {
    jsonResponse(['error' => 'ログインが必要です'], 401);
}

// CSRFトークン検証
if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    jsonResponse(['error' => '不正なリクエストです'], 403);
}

try {
    $db = Database::getInstance();
    $currentUser = getCurrentUser();
    
    $recipientId = (int)($_POST['recipient_id'] ?? 0);
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    // バリデーション
    if (!$recipientId) {
        jsonResponse(['error' => '受信者が指定されていません'], 400);
    }
    
    if (empty($subject)) {
        jsonResponse(['error' => '件名を入力してください'], 400);
    }
    
    if (empty($message)) {
        jsonResponse(['error' => 'メッセージ内容を入力してください'], 400);
    }
    
    // 受信者の存在確認
    $recipient = $db->selectOne("SELECT id, full_name FROM users WHERE id = ? AND is_active = 1", [$recipientId]);
    if (!$recipient) {
        jsonResponse(['error' => '受信者が見つかりません'], 404);
    }
    
    // 自分自身へのメッセージ送信を防ぐ
    if ($currentUser['id'] === $recipientId) {
        jsonResponse(['error' => '自分自身にメッセージを送ることはできません'], 400);
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
                
                $actionUrl = url('chats.php', true);
                sendNotificationMail($recipientUser['email'], $emailSubject, $emailMessage, $actionUrl, 'メッセージを確認する');
            }
        } catch (Exception $e) {
            error_log('メッセージ通知メール送信エラー: ' . $e->getMessage());
            // メール送信エラーでもメッセージ送信は成功として処理
        }
        
        jsonResponse([
            'success' => true,
            'message' => 'メッセージを送信しました',
            'message_id' => $messageId
        ]);
    } else {
        jsonResponse(['error' => 'メッセージの送信に失敗しました'], 500);
    }
    
} catch (Exception $e) {
    error_log("Message send error: " . $e->getMessage());
    jsonResponse(['error' => 'システムエラーが発生しました'], 500);
}