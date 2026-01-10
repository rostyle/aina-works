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
    ErrorHandler::jsonAuthError();
}

try {
    $db = Database::getInstance();
    $currentUser = getCurrentUser();
    
    if (!$currentUser) {
        ErrorHandler::jsonError(Messages::USER_NOT_FOUND, 401, null, 'USER_NOT_FOUND');
    }
    
    $roomId = (int)($_GET['room_id'] ?? 0);
    $lastMessageId = (int)($_GET['last_message_id'] ?? 0);
    
    // バリデーション
    $validation = new ValidationResult();
    
    $error = validatePositiveInteger($roomId, 'チャットルーム');
    if ($error) {
        $validation->addError('room_id', $error);
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
            'message_type' => $message['message_type'] ?? 'text',
            'file_path' => $message['file_path'] ?? null,
            'sender_name' => $message['sender_name'],
            'sender_image' => $message['sender_image'],
            'time' => date('H:i', strtotime($message['created_at'])),
            'is_read' => (bool)$message['is_read']
        ];
    }, $messages);
    
    ErrorHandler::jsonSuccess(null, ['messages' => $formattedMessages]);
    
} catch (Exception $e) {
    ErrorHandler::handleException($e, 'Get chat messages error: ' . $e->getMessage());
} catch (Error $e) {
    ErrorHandler::handleException($e, 'Get chat messages fatal error: ' . $e->getMessage());
}