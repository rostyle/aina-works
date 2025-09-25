<?php
require_once __DIR__ . '/../config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

// CSRF token from header or JSON body
$headers = function_exists('getallheaders') ? (getallheaders() ?: []) : [];
$csrfHeader = '';
foreach ($headers as $k => $v) {
    if (strtolower($k) === 'x-csrf-token') { $csrfHeader = $v; break; }
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
$csrfBody = is_array($data) ? (string)($data['csrf_token'] ?? '') : '';
$csrfToken = $csrfHeader ?: $csrfBody;

if (!verifyCsrfToken($csrfToken)) {
    jsonResponse(['success' => false, 'message' => 'Invalid CSRF token'], 400);
}

if (!isLoggedIn() || !isAdminUser()) {
    jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
}

if (!is_array($data) || empty($data['order']) || !is_array($data['order'])) {
    jsonResponse(['success' => false, 'message' => 'Invalid payload'], 400);
}

$order = array_values(array_filter($data['order'], function($v){ return is_int($v) || ctype_digit((string)$v); }));
if (empty($order)) {
    jsonResponse(['success' => false, 'message' => 'No items to order'], 400);
}

$db = Database::getInstance();
try {
    $db->beginTransaction();
    $position = 1;
    foreach ($order as $id) {
        $id = (int)$id;
        if ($id <= 0) continue;
        $db->update('UPDATE categories SET sort_order = ? WHERE id = ?', [$position, $id]);
        $position++;
    }
    $db->commit();
    jsonResponse(['success' => true]);
} catch (Exception $e) {
    try { $db->rollback(); } catch (Exception $e2) {}
    jsonResponse(['success' => false, 'message' => 'Failed to save order'], 500);
}
