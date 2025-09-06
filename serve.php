<?php
require_once 'config/config.php';

// 画像ファイルは一般公開（ログイン不要）

$file = $_GET['file'] ?? '';

// ファイル名が指定されていない、または不正な文字が含まれている場合はエラー
if (empty($file) || !preg_match('/^[a-z0-9\.\_\-]+$/i', $file) || strpos($file, '..') !== false) {
    http_response_code(400);
    echo "Invalid file specified.";
    exit;
}

$filePath = UPLOAD_PATH . '/' . $file;

if (!file_exists($filePath)) {
    http_response_code(404);
    echo "File not found.";
    exit;
}

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $filePath);
finfo_close($finfo);

header('Content-Type: ' . $mimeType);
header('Content-Length: ' . filesize($filePath));

readfile($filePath);
exit;
