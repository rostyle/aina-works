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
        if (empty($errors)) {
            try {
                $db = Database::getInstance();
                
                // ユーザーが存在するかチェック
                $user = $db->selectOne(
                    "SELECT id, email, full_name FROM users WHERE email = ? AND is_active = 1",
                    [$email]
                );
                
                if ($user) {
                    // パスワードリセットトークンを生成
                    $token = bin2hex(random_bytes(32));
                    $expiresAt = date('Y-m-d H:i:s', time() + 3600); // 1時間後に期限切れ
                    
                    // 既存の未使用トークンを削除
                    $db->delete(
                        "DELETE FROM password_resets WHERE email = ? AND used_at IS NULL",
                        [$email]
                    );
                    
                    // 新しいトークンを保存
                    $db->insert(
                        "INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)",
                        [$email, $token, $expiresAt]
                    );
                    
                    // メール送信（PHPMyAdminでの確認用にログ出力）
                    $resetUrl = url("reset-password.php?token=" . $token);
                    $emailContent = "
                    パスワードリセットのリクエストを受け付けました。
                    
                    以下のリンクをクリックしてパスワードをリセットしてください：
                    {$resetUrl}
                    
                    このリンクは1時間後に期限切れとなります。
                    
                    もしこのリクエストに心当たりがない場合は、このメールを無視してください。
                    ";
                    
                    // PHPMyAdmin確認用：メール内容をログに記録
                    error_log("=== パスワードリセットメール ===");
                    error_log("宛先: " . $email);
                    error_log("件名: [AiNA Works] パスワードリセットのご案内");
                    error_log("内容:\n" . $emailContent);
                    error_log("リセットURL: " . $resetUrl);
                    error_log("トークン: " . $token);
                    error_log("有効期限: " . $expiresAt);
                    error_log("========================");
                    
                    // 実際のメール送信処理（本番環境では有効化）
                    /*
                    $subject = "[AiNA Works] パスワードリセットのご案内";
                    $headers = "From: noreply@ainaworks.com\r\n";
                    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
                    
                    if (mail($email, $subject, $emailContent, $headers)) {
                        $success = true;
                    } else {
                        $errors[] = 'メール送信に失敗しました。再度お試しください。';
                    }
                    */
                    
                    // 開発環境では常に成功とする
                    $success = true;
                } else {
                    // セキュリティのため、存在しないメールアドレスでも成功メッセージを表示
                    $success = true;
                }
                
            } catch (Exception $e) {
                error_log("パスワードリセットエラー: " . $e->getMessage());
                $errors[] = 'パスワードリセットの処理に失敗しました。再度お試しください。';
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
                <span class="text-white font-bold text-xl">CM</span>
            </div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                パスワードをお忘れですか？
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                登録したメールアドレスを入力してください。<br>
                パスワードリセット用のリンクをお送りします。
            </p>
        </div>

        <?php if ($success): ?>
            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                <div class="flex">
                    <svg class="h-5 w-5 text-green-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    <div class="text-sm text-green-700">
                        <p class="font-medium">メールを送信しました</p>
                        <p class="mt-1">パスワードリセット用のリンクをメールでお送りしました。メールをご確認ください。</p>
                        <p class="mt-2 text-xs text-green-600">
                            ※開発環境では実際のメール送信は行われません。<br>
                            サーバーログまたはPHPMyAdminでメール内容を確認してください。
                        </p>
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
