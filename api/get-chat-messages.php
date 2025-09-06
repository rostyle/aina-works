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
    
    $roomId = (int)($_GET['room_id'] ?? 0);
    $lastMessageId = (int)($_GET['last_message_id'] ?? 0);
    
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
    
    // 新しいメッセージを取得
    $messages = $db->select("
        SELECT cm.*, u.full_name as sender_name, u.profile_image as sender_image
        FROM chat_messages cm
        JOIN users u ON cm.sender_id = u.id
        WHERE cm.room_id = ? AND cm.id > ?
        ORDER BY cm.created_at ASC
    ", [$roomId, $lastMessageId]);
    
    // メッセージをフォーマット
    $formattedMessages = array_map(function($message) {
        return [
            'id' => $message['id'],
            'sender_id' => $message['sender_id'],
            'message' => $message['message'],
            'sender_name' => $message['sender_name'],
            'sender_image' => $message['sender_image'],
            'time' => date('H:i', strtotime($message['created_at'])),
            'is_read' => (bool)$message['is_read']
        ];
    }, $messages);
    
    jsonResponse([
        'success' => true,
        'messages' => $formattedMessages
    ]);
    
} catch (Exception $e) {
    error_log("Get chat messages error: " . $e->getMessage());
    jsonResponse(['error' => 'システムエラーが発生しました'], 500);
}
?>
