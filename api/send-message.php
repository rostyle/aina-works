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
?>
