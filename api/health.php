<?php
/**
 * 死活監視用（外部の監視サービスから1〜5分おきに叩く想定）
 * 200 {"ok":true} … アプリとDBが応答している
 * 503 {"ok":false} … DBに接続できない
 * clock_ok … DBの時刻とPHPの時刻（Asia/Tokyo）が一致しているか。false ならDBのタイムゾーンがずれている
 * 個人情報・設定値は返さない。
 */
require_once __DIR__ . '/../config/config.php';

header('Cache-Control: no-store');

$ok = false;
$clockOk = null;
try {
    $row = Database::getInstance()->selectOne("SELECT NOW() AS now");
    $ok = (bool)$row;
    // DBの NOW() とPHPの時刻が2分以上ずれていたら false（タイムゾーン不一致の検知用）
    $clockOk = $ok && abs(strtotime((string)$row['now']) - time()) < 120;
} catch (Throwable $e) {
    error_log('health: ' . $e->getMessage());
}
jsonResponse(['ok' => $ok, 'clock_ok' => $clockOk, 'time' => date('c')], $ok ? 200 : 503);
