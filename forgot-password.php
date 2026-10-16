<?php
require_once 'config/config.php';

$pageTitle = 'パスワードを忘れた場合';
$pageDescription = 'パスワードをリセットするためのメールを送信します';

// 既にログインしている場合はリダイレクト
if (isLoggedIn()) {
    redirect(url('dashboard'));
}

$errors = [];
$success = false;
$email = '';

// フォーム送信処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRFトークン検証
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = '不正なリクエストです。';
    } else {
        $email = trim($_POST['email'] ?? '');
        
        // バリデーション
        if (empty($email)) {
            $errors[] = 'メールアドレスを入力してください。';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'メールアドレスの形式が正しくありません。';
        }
        
        // エラーがない場合はパスワードリセット処理
        if (empty($errors) && !rateLimitAllow('pw_reset', strtolower($email) . '|' . clientIp(), 3, 3600)) {
            // 回数を超えても結果は同じ表示にする（登録の有無を推測させない）
            $success = true;
        } elseif (empty($errors)) {
            try {
                $db = Database::getInstance();

                $user = $db->selectOne(
                    "SELECT id, email, full_name, aina_user_id FROM users WHERE email = ? AND is_active = 1",
                    [$email]
                );

                if ($user && !empty($user['aina_user_id'])) {
                    // マイページ会員はマイページのパスワードでログインする。ここではローカルパスワードを作らない
                    $subject = "【AiNA Works】ログインについてのご案内";
                    $message = "AiNA Works は AiNAマイページと同じメールアドレス・パスワードでログインします。\n\n";
                    $message .= "パスワードをお忘れの場合は、AiNAマイページのログイン画面から再設定してください。\n";
                    $message .= "再設定したパスワードで AiNA Works にもログインできます。";
                    sendNotificationMail($email, $subject, $message, AINA_MYPAGE_URL, 'AiNAマイページを開く');
                    $success = true;
                } elseif ($user) {
                    // マイページに紐づかないアカウント（管理者・法人など）だけ、ここで再設定する
                    $token = bin2hex(random_bytes(32));
                    $expiresAt = date('Y-m-d H:i:s', time() + 3600);

                    $db->delete(
                        "DELETE FROM password_resets WHERE email = ? AND used_at IS NULL",
                        [$email]
                    );
                    $db->insert(
                        "INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)",
                        [$email, hash('sha256', $token), $expiresAt]
                    );

                    $resetUrl = url("reset-password?token=" . $token, true);
                    $subject = "【AiNA Works】パスワード再設定のご案内";
                    $message = "パスワード再設定のリクエストを受け付けました。\n\n";
                    $message .= "下のボタンから新しいパスワードを設定してください。\n";
                    $message .= "このリンクの有効期限は1時間です。\n\n";
                    $message .= "お心当たりがない場合は、このメールを破棄してください。パスワードは変わりません。";

                    if (!sendNotificationMail($email, $subject, $message, $resetUrl, 'パスワードを再設定する')) {
                        error_log('パスワード再設定メールの送信に失敗 (user_id=' . $user['id'] . ')');
                    }
                    $success = true;
                } else {
                    // 登録の有無を推測させないため同じ表示にする
                    $success = true;
                }
            } catch (Exception $e) {
                error_log("パスワードリセットエラー: " . $e->getMessage());
                $errors[] = 'パスワード再設定の処理に失敗しました。時間をおいて再度お試しください。';
            }
        }
    }
}

include 'includes/header.php';
?>

<!-- Forgot Password Section -->
<section class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div>
            <div class="mx-auto h-12 w-12 flex items-center justify-center rounded-full bg-gradient-to-r from-blue-600 to-purple-600">
                <span class="text-white font-bold text-xl">AW</span>
            </div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                パスワードをお忘れですか？
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                AiNA Works は <strong>AiNAマイページと同じメールアドレス・パスワード</strong>でログインします。<br>
                お忘れの場合は <a href="<?= h(AINA_MYPAGE_URL) ?>" class="text-blue-600 underline" target="_blank" rel="noopener">AiNAマイページ</a> で再設定してください。
            </p>
            <p class="mt-2 text-center text-xs text-gray-500">
                マイページ会員ではない法人アカウントの方は、下にメールアドレスを入力してください。再設定用のリンクをお送りします。
            </p>
        </div>

        <?php if ($success): ?>
            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                <div class="flex">
                    <svg class="h-5 w-5 text-green-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    <div class="text-sm text-green-700">
                        <p class="font-medium">ご案内を送りました</p>
                        <p class="mt-1">ご登録のメールアドレスあてに、ログインのご案内をお送りしました。届かない場合は迷惑メールフォルダもご確認ください。</p>
                    </div>
                </div>
            </div>
            
            <div class="text-center">
                <a href="<?= url('login') ?>" class="font-medium text-blue-600 hover:text-blue-500">
                    ← ログインページに戻る
                </a>
            </div>
        <?php else: ?>
            <?php if (!empty($errors)): ?>
                <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                    <div class="flex">
                        <svg class="h-5 w-5 text-red-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                        <div>
                            <ul class="text-sm text-red-700 space-y-1">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= h($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <form class="mt-8 space-y-6" method="POST">
                <input type="hidden" name="csrf_token" value="<?= h(generateCsrfToken()) ?>">
                
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                        メールアドレス
                    </label>
                    <input id="email" 
                           name="email" 
                           type="email" 
                           autocomplete="email" 
                           required 
                           value="<?= h($email) ?>"
                           class="appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm" 
                           placeholder="example@email.com">
                </div>

                <div>
                    <button type="submit" 
                            class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                            <svg class="h-5 w-5 text-blue-500 group-hover:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                                <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                            </svg>
                        </span>
                        パスワードリセット用メールを送信
                    </button>
                </div>

                <div class="text-center">
                    <a href="<?= url('login') ?>" class="font-medium text-blue-600 hover:text-blue-500">
                        ← ログインページに戻る
                    </a>
                </div>
            </form>
        <?php endif; ?>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
