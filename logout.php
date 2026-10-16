<?php
require_once 'config/config.php';

// セッションの中身とCookieを消してから、フラッシュ表示用に新しいセッションを始める
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires' => time() - 42000,
        'path' => $params['path'] ?: '/',
        'domain' => $params['domain'] ?? '',
        'secure' => (bool)($params['secure'] ?? false),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}
session_destroy();
session_start();
session_regenerate_id(true);

setFlash('success', 'ログアウトしました。');
redirect(url());
