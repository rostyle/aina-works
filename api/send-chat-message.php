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
    
    $roomId = (int)($_POST['room_id'] ?? 0);
    $message = trim($_POST['message'] ?? '');
    
    // バリデーション
    if (!$roomId) {
        jsonResponse(['error' => 'チャットルームが指定されていません'], 400);
    }
    
    if (empty($message)) {
        jsonResponse(['error' => 'メッセージを入力してください'], 400);
    }
    
    // チャットルームの存在確認と権限チェック
    $chatRoom = $db->selectOne("
        SELECT * FROM chat_rooms 
        WHERE id = ? AND (user1_id = ? OR user2_id = ?)
    ", [$roomId, $currentUser['id'], $currentUser['id']]);
    
    if (!$chatRoom) {
        jsonResponse(['error' => 'チャットルームが見つかりません'], 404);
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
        
        jsonResponse([
            'success' => true,
            'message' => [
                'id' => $sentMessage['id'],
                'sender_id' => $sentMessage['sender_id'],
                'message' => $sentMessage['message'],
                'sender_name' => $sentMessage['sender_name'],
                'sender_image' => $sentMessage['sender_image'],
                'time' => date('H:i', strtotime($sentMessage['created_at'])),
                'is_read' => false
            ]
        ]);
    } else {
        jsonResponse(['error' => 'メッセージの送信に失敗しました'], 500);
    }
    
} catch (Exception $e) {
    error_log("Chat message send error: " . $e->getMessage());
    jsonResponse(['error' => 'システムエラーが発生しました'], 500);
}
?>
