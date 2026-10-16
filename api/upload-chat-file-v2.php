<?php
/**
 * チャット添付アップロードAPI
 * 画像（jpg/png/gif/webp）とPDFだけを受け付け、中身（MIME）で判定する。
 * 保存先は storage/app/uploads/chat/（.htaccessで直接アクセス禁止）。配信は api/chat-file.php が参加者だけに行う。
 */
ob_start();
ini_set('display_errors', '0');

register_shutdown_function(function () {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        error_log('upload-chat-file fatal: ' . $error['message'] . ' @' . basename($error['file']) . ':' . $error['line']);
        while (ob_get_level()) {
            ob_end_clean();
        }
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode(['success' => false, 'message' => 'ファイルを送信できませんでした。時間をおいて再度お試しください。', 'error_code' => 'SERVER_ERROR'], JSON_UNESCAPED_UNICODE);
    }
});

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/error-handler.php';

const CHAT_FILE_MAX_BYTES = 10 * 1024 * 1024; // 10MB
const CHAT_FILE_TYPES = [
    'image/jpeg' => ['ext' => 'jpg', 'type' => 'image'],
    'image/png' => ['ext' => 'png', 'type' => 'image'],
    'image/gif' => ['ext' => 'gif', 'type' => 'image'],
    'image/webp' => ['ext' => 'webp', 'type' => 'image'],
    'application/pdf' => ['ext' => 'pdf', 'type' => 'document'],
];

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
    if ($roomId <= 0) {
        ErrorHandler::jsonError('チャットルームが指定されていません', 400);
    }
    if (!rateLimitAllow('chat_upload', (string)$currentUser['id'], 20, 600)) {
        ErrorHandler::jsonError('短時間に送信しすぎています。少し時間をおいてから送ってください。', 429, null, 'RATE_LIMITED');
    }

    $chatRoom = $db->selectOne("
        SELECT * FROM chat_rooms
        WHERE id = ? AND (user1_id = ? OR user2_id = ?)
    ", [$roomId, $currentUser['id'], $currentUser['id']]);
    if (!$chatRoom) {
        ErrorHandler::jsonNotFoundError('チャットルーム');
    }

    if (!isset($_FILES['file']) || !is_array($_FILES['file']) || ($_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        $code = $_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE;
        $msg = in_array($code, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)
            ? 'ファイルが大きすぎます（10MBまで）'
            : 'ファイルを受け取れませんでした。もう一度お試しください。';
        ErrorHandler::jsonError($msg, 400);
    }
    $file = $_FILES['file'];
    if (!is_uploaded_file($file['tmp_name'])) {
        ErrorHandler::jsonError('ファイルを受け取れませんでした。もう一度お試しください。', 400);
    }
    if ((int)$file['size'] <= 0 || (int)$file['size'] > CHAT_FILE_MAX_BYTES) {
        ErrorHandler::jsonError('ファイルが大きすぎます（10MBまで）', 400);
    }

    // 拡張子ではなく中身で判定する
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = (string)finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    if (!isset(CHAT_FILE_TYPES[$mime])) {
        ErrorHandler::jsonError('画像（JPG・PNG・GIF・WebP）またはPDFだけ送れます', 400);
    }
    $kind = CHAT_FILE_TYPES[$mime];

    $uploadDir = rtrim(UPLOAD_PATH, '/') . '/chat';
    if (!is_dir($uploadDir) && !@mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
        error_log('upload-chat-file: cannot create ' . $uploadDir);
        ErrorHandler::jsonError('ファイルを保存できませんでした', 500);
    }

    // 推測できない名前で保存する
    $storedName = bin2hex(random_bytes(16)) . '.' . $kind['ext'];
    $filePath = $uploadDir . '/' . $storedName;
    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        ErrorHandler::jsonError('ファイルを保存できませんでした', 500);
    }
    @chmod($filePath, 0644);
    $relativePath = 'storage/app/uploads/chat/' . $storedName;

    // 表示用の元ファイル名（制御文字とパスを除く）
    $originalName = preg_replace('/[\x00-\x1f\x7f]/u', '', basename((string)$file['name']));
    $originalName = mb_substr($originalName !== '' ? $originalName : ('添付ファイル.' . $kind['ext']), 0, 200);

    $messageId = $db->insert("
        INSERT INTO chat_messages (room_id, sender_id, message, message_type, file_path, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ", [$roomId, $currentUser['id'], $originalName, $kind['type'], $relativePath]);
    if (!$messageId) {
        @unlink($filePath);
        ErrorHandler::jsonError('ファイルを保存できませんでした', 500);
    }

    $db->update("UPDATE chat_rooms SET updated_at = NOW() WHERE id = ?", [$roomId]);

    $sentMessage = $db->selectOne("
        SELECT cm.*, u.full_name AS sender_name, u.profile_image AS sender_image
        FROM chat_messages cm
        JOIN users u ON cm.sender_id = u.id
        WHERE cm.id = ?
    ", [$messageId]);

    try {
        $recipientId = ($chatRoom['user1_id'] == $currentUser['id']) ? $chatRoom['user2_id'] : $chatRoom['user1_id'];
        $recipient = $db->selectOne("SELECT email, full_name FROM users WHERE id = ? AND is_active = 1", [$recipientId]);
        if ($recipient && rateLimitAllow('chat_mail', $roomId . ':' . $recipientId, 1, 600)) {
            $subject = "【AiNA Works】新しいファイルが届きました";
            $emailMessage = "{$currentUser['full_name']}さんからファイルが届きました。\n\nファイル名: {$originalName}\n";
            sendNotificationMail($recipient['email'], $subject, $emailMessage, url('chat?room_id=' . $roomId, true), 'チャットを開く');
        }
    } catch (Throwable $e) {
        error_log('Chat notification mail error (upload): ' . $e->getMessage());
    }

    ErrorHandler::jsonSuccess('ファイルを送信しました', [
        'id' => $sentMessage['id'],
        'sender_id' => $sentMessage['sender_id'],
        'message' => $sentMessage['message'],
        'message_type' => $sentMessage['message_type'],
        'file_path' => $sentMessage['file_path'],
        'file_url' => chatFileUrl($sentMessage['id']),
        'download_url' => chatFileUrl($sentMessage['id'], true),
        'is_image' => $kind['type'] === 'image',
        'sender_name' => $sentMessage['sender_name'],
        'sender_image' => uploaded_asset($sentMessage['sender_image'] ?? ''),
        'time' => date('H:i', strtotime($sentMessage['created_at'])),
        'is_read' => false,
    ]);
} catch (Throwable $e) {
    ErrorHandler::handleException($e, 'upload-chat-file: ' . $e->getMessage());
}
