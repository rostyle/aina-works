<?php
require_once 'config/config.php';
header('Content-Type: application/json');

$uploadDir = 'storage/app/uploads/chat/';
$absUploadDir = __DIR__ . '/' . $uploadDir;

echo json_encode([
    'php_version' => PHP_VERSION,
    'server_os' => PHP_OS,
    'base_url' => BASE_URL,
    'extensions' => [
        'pdo_mysql' => extension_loaded('pdo_mysql'),
        'gd' => extension_loaded('gd'),
        'openssl' => extension_loaded('openssl'),
        'mbstring' => extension_loaded('mbstring'),
        'fileinfo' => extension_loaded('fileinfo'),
    ],
    'vendor_autoload' => file_exists(BASE_PATH . '/vendor/autoload.php'),
    'upload_dir_info' => [
        'path' => $uploadDir,
        'absolute_path' => $absUploadDir,
        'exists' => file_exists($absUploadDir),
        'is_dir' => is_dir($absUploadDir),
        'is_writable' => is_writable($absUploadDir) || (is_dir(dirname($absUploadDir)) && is_writable(dirname($absUploadDir))),
        'perms' => file_exists($absUploadDir) ? substr(sprintf('%o', fileperms($absUploadDir)), -4) : 'N/A'
    ]
]);
