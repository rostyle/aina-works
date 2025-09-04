<?php
require_once 'config/config.php';

// ログインチェック
if (!isLoggedIn()) {
    redirect(url('login.php'));
}

// POSTリクエストのみ許可（CSRFトークン付き）またはGETリクエスト（簡単な切り替え用）
$newRole = null;
$redirectUrl = 'dashboard.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRFトークン検証
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', '不正なリクエストです。');
        redirect(url('dashboard.php'));
    }
    
    $newRole = $_POST['role'] ?? null;
    $redirectUrl = $_POST['redirect'] ?? 'dashboard.php';
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // GETリクエストでの簡易切り替え
    $newRole = $_GET['role'] ?? null;
    $redirectUrl = $_GET['redirect'] ?? 'dashboard.php';
}

if (!$newRole) {
    setFlash('error', 'ロールが指定されていません。');
    redirect(url('dashboard.php'));
}

// ロール切り替え実行
if (switchRole($newRole)) {
    setFlash('success', getRoleDisplayName($newRole) . 'モードに切り替えました。');
} else {
    setFlash('error', '指定されたロールに切り替えることができません。');
}

// リダイレクト
redirect(url($redirectUrl));
?>
