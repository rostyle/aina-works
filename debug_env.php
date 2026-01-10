<?php
require_once 'config/config.php';
header('Content-Type: application/json');

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
    'upload_dir_exists' => is_dir(UPLOAD_PATH),
    'upload_dir_writable' => is_writable(UPLOAD_PATH),
]);
