<?php
/**
 * 死活監視用（外部の監視サービスから1〜5分おきに叩く想定）
 * 200 {"ok":true} … アプリとDBが応答している
 * 503 {"ok":false} … DBに接続できない
 * 個人情報・設定値は返さない。
 */
require_once __DIR__ . '/../config/config.php';

while (ob_get_level()) {
    ob_end_clean();
}
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$ok = false;
try {
    $ok = (bool)Database::getInstance()->selectOne("SELECT 1 AS ok");
} catch (Throwable $e) {
    error_log('health: ' . $e->getMessage());
}
http_response_code($ok ? 200 : 503);
echo json_encode(['ok' => $ok, 'time' => date('c')]);
