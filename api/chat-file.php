<?php
/**
 * チャット添付の配信（そのチャットの参加者と管理者だけ）
 * GET ?id=<chat_messages.id>[&dl=1]
 */
require_once __DIR__ . '/../config/config.php';

function chatFileFail(int $code): void {
    while (ob_get_level()) {
        ob_end_clean();
    }
    http_response_code($code);
    header('Content-Type: text/plain; charset=utf-8');
    echo $code === 404 ? 'ファイルが見つかりません' : 'このファイルを開く権限がありません';
    exit;
}

if (!isLoggedIn()) {
    chatFileFail(403);
}
$messageId = (int)($_GET['id'] ?? 0);
if ($messageId <= 0) {
    chatFileFail(404);
}

$db = Database::getInstance();
$row = $db->selectOne("
    SELECT cm.id, cm.message, cm.file_path, cr.user1_id, cr.user2_id
    FROM chat_messages cm
    JOIN chat_rooms cr ON cm.room_id = cr.id
    WHERE cm.id = ?
", [$messageId]);
if (!$row || empty($row['file_path'])) {
    chatFileFail(404);
}
$uid = (int)$_SESSION['user_id'];
if ($uid !== (int)$row['user1_id'] && $uid !== (int)$row['user2_id'] && !isAdminUser()) {
    chatFileFail(403);
}

// 保存先の外を指していないか確認する
$base = realpath(rtrim(UPLOAD_PATH, '/') . '/chat');
$relative = preg_replace('#^storage/app/uploads/chat/#', '', (string)$row['file_path']);
$path = $base ? realpath($base . '/' . basename($relative)) : false;
if ($base === false || $path === false || strpos($path, $base . DIRECTORY_SEPARATOR) !== 0 || !is_file($path)) {
    chatFileFail(404);
}

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = (string)finfo_file($finfo, $path);
finfo_close($finfo);
$inline = in_array($mime, ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf'], true) && empty($_GET['dl']);
if (!$inline && !in_array($mime, ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf'], true)) {
    $mime = 'application/octet-stream';
}

$name = trim((string)$row['message']) !== '' ? (string)$row['message'] : basename($path);
$name = preg_replace('/[\x00-\x1f\x7f"\\\\]/u', '', $name);
$ascii = preg_replace('/[^A-Za-z0-9._-]/', '_', $name) ?: 'file';

while (ob_get_level()) {
    ob_end_clean();
}
header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($path));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, max-age=3600');
header("Content-Security-Policy: default-src 'none'; img-src 'self'; style-src 'unsafe-inline'; sandbox");
header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="' . $ascii . '"; filename*=UTF-8\'\'' . rawurlencode($name));
readfile($path);
exit;
