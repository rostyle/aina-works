<?php
/**
 * チャットメッセージ送信API
 */
ob_start();
ini_set('display_errors', '0');

// 致命的エラーでも詳細は返さず、固定文言のJSONにする（詳細はサーバーログ）
register_shutdown_function(function () {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        error_log('send-chat-message fatal: ' . $error['message'] . ' @' . basename($error['file']) . ':' . $error['line']);
        while (ob_get_level()) {
            ob_end_clean();
        }
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode(['success' => false, 'message' => 'メッセージを送信できませんでした。時間をおいて再度お試しください。', 'error_code' => 'SERVER_ERROR'], JSON_UNESCAPED_UNICODE);
    }
});

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/error-handler.php';

if (!isLoggedIn()) {
    ErrorHandler::jsonAuthError();
}
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    ErrorHandler::jsonError('POSTリクエストのみ受け付けます', 405);
}
if (!verifyCsrfToken(csrfTokenFromRequest())) {
    ErrorHandler::jsonCsrfError(Messages::CSRF_INVALID);
}

try {
    $db = Database::getInstance();
    $currentUser = getCurrentUser();
    if (!$currentUser) {
        ErrorHandler::jsonAuthError();
    }

    $roomId = (int)($_POST['room_id'] ?? 0);
    $message = trim((string)($_POST['message'] ?? ''));

    if ($roomId <= 0 || $message === '') {
        ErrorHandler::jsonError('チャットルームまたはメッセージが正しくありません', 400);
    }
    if (mb_strlen($message) > 1000) {
        ErrorHandler::jsonError('メッセージは1000文字以内で入力してください', 400);
    }
    // 連投の上限（1分30通）
    if (!rateLimitAllow('chat_send', (string)$currentUser['id'], 30, 60)) {
        ErrorHandler::jsonError('短時間に送信しすぎています。少し時間をおいてから送ってください。', 429, null, 'RATE_LIMITED');
    }

    $chatRoom = $db->selectOne("
        SELECT * FROM chat_rooms
        WHERE id = ? AND (user1_id = ? OR user2_id = ?)
    ", [$roomId, $currentUser['id'], $currentUser['id']]);
    if (!$chatRoom) {
        ErrorHandler::jsonNotFoundError('チャットルーム');
    }

    $messageId = $db->insert("
        INSERT INTO chat_messages (room_id, sender_id, message, created_at)
        VALUES (?, ?, ?, NOW())
    ", [$roomId, $currentUser['id'], $message]);
    if (!$messageId) {
        ErrorHandler::jsonError('メッセージを保存できませんでした', 500);
    }

    $db->update("UPDATE chat_rooms SET updated_at = NOW() WHERE id = ?", [$roomId]);

    $sentMessage = $db->selectOne("
        SELECT cm.*, u.full_name AS sender_name, u.profile_image AS sender_image
        FROM chat_messages cm
        JOIN users u ON cm.sender_id = u.id
        WHERE cm.id = ?
    ", [$messageId]);

    // 相手へのメール通知（同じ相手・同じルームには10分に1通まで）
    try {
        $recipientId = ($chatRoom['user1_id'] == $currentUser['id']) ? $chatRoom['user2_id'] : $chatRoom['user1_id'];
        $recipient = $db->selectOne("SELECT email, full_name FROM users WHERE id = ? AND is_active = 1", [$recipientId]);
        if ($recipient && rateLimitAllow('chat_mail', $roomId . ':' . $recipientId, 1, 600)) {
            $subject = "【AiNA Works】新しいチャットメッセージが届きました";
            $emailMessage = "{$currentUser['full_name']}さんからチャットメッセージが届きました。\n\n";
            $emailMessage .= "メッセージ:\n" . mb_strimwidth($message, 0, 400, '…') . "\n\n";
            $emailMessage .= "返信はチャットで行ってください。";
            sendNotificationMail($recipient['email'], $subject, $emailMessage, url('chat?room_id=' . $roomId, true), 'チャットを開く');
        }
    } catch (Throwable $e) {
        error_log('Chat notification mail error: ' . $e->getMessage());
    }

    ErrorHandler::jsonSuccess('メッセージを送信しました', [
        'id' => $sentMessage['id'],
        'sender_id' => $sentMessage['sender_id'],
        'message' => $sentMessage['message'],
        'sender_name' => $sentMessage['sender_name'],
        'sender_image' => uploaded_asset($sentMessage['sender_image'] ?? ''),
        'time' => date('H:i', strtotime($sentMessage['created_at'])),
        'is_read' => false,
    ]);
} catch (Throwable $e) {
    ErrorHandler::handleException($e, 'send-chat-message: ' . $e->getMessage());
}
