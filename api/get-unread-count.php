<?php
require_once '../config/config.php';

header('Content-Type: application/json');

// ログイン確認
if (!isLoggedIn()) {
    jsonResponse(['error' => 'ログインが必要です'], 401);
}

try {
    $db = Database::getInstance();
    $currentUser = getCurrentUser();
    
    // 未読メッセージ数を取得
    $unreadCount = $db->selectOne("
        SELECT COUNT(*) as count
        FROM chat_messages cm
        JOIN chat_rooms cr ON cm.room_id = cr.id
        WHERE (cr.user1_id = ? OR cr.user2_id = ?)
        AND cm.sender_id != ?
        AND cm.is_read = 0
    ", [$currentUser['id'], $currentUser['id'], $currentUser['id']]);
    
    jsonResponse([
        'success' => true,
        'unread_count' => (int)($unreadCount['count'] ?? 0)
    ]);
    
} catch (Exception $e) {
    error_log("Get unread count error: " . $e->getMessage());
    jsonResponse(['error' => 'システムエラーが発生しました'], 500);
}