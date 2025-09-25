<?php
// Admin local-only login page
require_once __DIR__ . '/../config/config.php';

// すでに管理者としてログイン済みならダッシュボードへ
if (isLoggedIn() && isAdminUser()) {
    redirect('./index.php');
}

$errors = [];
$email = '';
$password = '';

// リダイレクト先（同ディレクトリ内のファイル名に限定）
$redirectParam = isset($_GET['redirect']) ? basename((string)$_GET['redirect']) : 'index.php';
if ($redirectParam === '') {
    $redirectParam = 'index.php';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors['general'] = '不正なリクエストです。';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = (string)($_POST['password'] ?? '');

        if ($email === '') {
            $errors['email'] = 'メールアドレスを入力してください。';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'メールアドレスの形式が正しくありません。';
        }
        if ($password === '') {
            $errors['password'] = 'パスワードを入力してください。';
        }

        if (empty($errors)) {
            try {
                // ローカルDB認証のみ
                $loginResult = performLocalLogin($email, $password);
                if ($loginResult['success']) {
                    // 成功したら admin/index.php などへ
                    redirect('./' . $redirectParam);
                } else {
                    $errors['general'] = $loginResult['message'] ?? 'ログインに失敗しました。';
                }
            } catch (Exception $e) {
                error_log('Admin local login error: ' . $e->getMessage());
                $errors['general'] = 'ログイン処理中にエラーが発生しました。';
            }
        }
    }
}

// シンプルなログインフォーム（Tailwind CDN 利用）
?><!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>管理画面ログイン</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <meta name="csrf-token" content="<?= h(generateCsrfToken()) ?>">
  <style>body{min-height:100vh}</style>
  <link rel="icon" href="data:,">
  <!-- 管理画面はローカルDB認証のみ -->
  <!-- info@ai-na.co.jp は .env の ADMIN_EMAILS に設定済みであれば admin 権限が付与されます -->
  <!-- 例: ADMIN_EMAILS=info@ai-na.co.jp -->
</head>
<body class="bg-gray-50 flex items-center justify-center py-12 px-4">
  <div class="w-full max-w-md">
    <div class="bg-white border rounded-xl shadow-sm p-6">
      <h1 class="text-xl font-semibold text-gray-900 mb-1">管理画面ログイン</h1>
      <p class="text-sm text-gray-600 mb-6">ローカルDBのアカウントで認証します。</p>

      <?php if (!empty($errors['general'])): ?>
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800">
          <?= h($errors['general']) ?>
        </div>
      <?php endif; ?>

      <form method="post" class="space-y-4">
        <input type="hidden" name="csrf_token" value="<?= h(generateCsrfToken()) ?>">

        <div>
          <label for="email" class="block text-sm font-medium text-gray-700 mb-1">メールアドレス</label>
          <input id="email" name="email" type="email" autocomplete="email" required
                 value="<?= h($email) ?>"
                 class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
          <?php if (!empty($errors['email'])): ?>
            <p class="mt-1 text-sm text-red-600"><?= h($errors['email']) ?></p>
          <?php endif; ?>
        </div>

        <div>
          <label for="password" class="block text-sm font-medium text-gray-700 mb-1">パスワード</label>
          <input id="password" name="password" type="password" autocomplete="current-password" required
                 class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
          <?php if (!empty($errors['password'])): ?>
            <p class="mt-1 text-sm text-red-600"><?= h($errors['password']) ?></p>
          <?php endif; ?>
        </div>

        <button type="submit" class="w-full inline-flex justify-center items-center gap-2 rounded-md bg-blue-600 px-4 py-2 text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
          ログイン
        </button>
      </form>
    </div>

    <div class="text-center mt-4">
      <a class="text-sm text-gray-600 hover:text-gray-900" href="<?= h(url('', true)) ?>">サイトへ戻る</a>
    </div>
  </div>
</body>
</html>
