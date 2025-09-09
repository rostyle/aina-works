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

// ヘッダーが送信済みでない場合のみ設定
if (!headers_sent()) {
    header('Content-Type: application/json');
}

// ログイン確認
if (!isLoggedIn()) {
    jsonResponse(['error' => 'ログインが必要です'], 401);
}

try {
    error_log("Get chat messages API called with room_id: " . ($_GET['room_id'] ?? 'null') . ", last_message_id: " . ($_GET['last_message_id'] ?? 'null'));
    
    $db = Database::getInstance();
    $currentUser = getCurrentUser();
    
    if (!$currentUser) {
        error_log("Get chat messages: Current user is null");
        jsonResponse(['error' => 'ユーザー情報が取得できません'], 401);
    }
    
    $roomId = (int)($_GET['room_id'] ?? 0);
    $lastMessageId = (int)($_GET['last_message_id'] ?? 0);
    
    error_log("Get chat messages: roomId=$roomId, lastMessageId=$lastMessageId, userId=" . $currentUser['id']);
    
    // バリデーション
    if (!$roomId) {
        jsonResponse(['error' => 'チャットルームが指定されていません'], 400);
    }
    
    // チャットルームの存在確認と権限チェック
    error_log("Checking chat room access for room_id: $roomId, user_id: " . $currentUser['id']);
    $chatRoom = $db->selectOne("
        SELECT * FROM chat_rooms 
        WHERE id = ? AND (user1_id = ? OR user2_id = ?)
    ", [$roomId, $currentUser['id'], $currentUser['id']]);
    
    if (!$chatRoom) {
        error_log("Chat room not found or access denied for room_id: $roomId, user_id: " . $currentUser['id']);
        jsonResponse(['error' => 'チャットルームが見つかりません'], 404);
    }
    
    error_log("Chat room found: " . json_encode($chatRoom));
    
    // 新しいメッセージを取得
    error_log("Fetching messages for room_id: $roomId, last_message_id: $lastMessageId");
    $messages = $db->select("
        SELECT cm.*, u.full_name as sender_name, u.profile_image as sender_image
        FROM chat_messages cm
        JOIN users u ON cm.sender_id = u.id
        WHERE cm.room_id = ? AND cm.id > ?
        ORDER BY cm.created_at ASC
    ", [$roomId, $lastMessageId]);
    
    error_log("Found " . count($messages) . " new messages");
    
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
    error_log("Get chat messages error trace: " . $e->getTraceAsString());
    jsonResponse(['error' => 'システムエラーが発生しました: ' . $e->getMessage()], 500);
} catch (Error $e) {
    error_log("Get chat messages fatal error: " . $e->getMessage());
    error_log("Get chat messages fatal error trace: " . $e->getTraceAsString());
    jsonResponse(['error' => '致命的なエラーが発生しました: ' . $e->getMessage()], 500);
}