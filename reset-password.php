<?php
require_once 'config/config.php';

$pageTitle = 'パスワードリセット';
$pageDescription = '新しいパスワードを設定してください';

// 既にログインしている場合はリダイレクト
if (isLoggedIn()) {
    redirect(url('dashboard'));
}

$errors = [];
$success = false;
$token = $_GET['token'] ?? '';
$validToken = false;
$email = '';

// トークンの検証
if (!empty($token)) {
    try {
        $db = Database::getInstance();
        $resetData = $db->selectOne(
            "SELECT * FROM password_resets WHERE token = ? AND used_at IS NULL AND expires_at > NOW()",
            [$token]
        );
        
        if ($resetData) {
            $validToken = true;
            $email = $resetData['email'];
        }
    } catch (Exception $e) {
        error_log("トークン検証エラー: " . $e->getMessage());
    }
}

if (!$validToken && !empty($token)) {
    $errors[] = 'パスワードリセット用のリンクが無効または期限切れです。';
}

// フォーム送信処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken) {
    // CSRFトークン検証
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors['general'] = '不正なリクエストです。';
    } else {
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';
        
        // バリデーション
        if (empty($password)) {
            $errors['password'] = 'パスワードを入力してください。';
        } elseif (strlen($password) < 8) {
            $errors['password'] = 'パスワードは8文字以上で入力してください。';
        }
        
        if (empty($passwordConfirm)) {
            $errors['password_confirm'] = 'パスワード（確認）を入力してください。';
        } elseif ($password !== $passwordConfirm) {
            $errors['password_confirm'] = 'パスワードが一致しません。';
        }
        
        // パスワード強度チェック
        if (!empty($password)) {
            if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/', $password)) {
                $errors['password'] = 'パスワードは大文字・小文字・数字を含む必要があります。';
            }
        }
        
        // エラーがない場合はパスワード更新処理
        if (empty($errors)) {
            try {
                $db = Database::getInstance();
                $db->beginTransaction();
                
                // パスワードをハッシュ化
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                
                // ユーザーのパスワードを更新
                $updated = $db->update(
                    "UPDATE users SET password_hash = ?, updated_at = NOW() WHERE email = ? AND is_active = 1",
                    [$passwordHash, $email]
                );
                
                if ($updated > 0) {
                    // リセットトークンを使用済みにマーク
                    $db->update(
                        "UPDATE password_resets SET used_at = NOW() WHERE token = ?",
                        [$token]
                    );
                    
                    $db->commit();
                    $success = true;
                    
                    // ログに記録
                    error_log("パスワードリセット完了: " . $email);
                } else {
                    $db->rollback();
                    $errors['general'] = 'パスワードの更新に失敗しました。';
                }
                
            } catch (Exception $e) {
                $db->rollback();
                error_log("パスワードリセットエラー: " . $e->getMessage());
                $errors['general'] = 'パスワードの更新に失敗しました。再度お試しください。';
            }
        }
    }
}

include 'includes/header.php';
?>

<!-- Reset Password Section -->
<section class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div>
            <div class="mx-auto h-12 w-12 flex items-center justify-center rounded-full bg-gradient-to-r from-blue-600 to-purple-600">
                <span class="text-white font-bold text-xl">CM</span>
            </div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                新しいパスワードを設定
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                <?php if ($validToken): ?>
                    <?= h($email) ?> のパスワードをリセットします
                <?php else: ?>
                    パスワードリセット用のリンクが必要です
                <?php endif; ?>
            </p>
        </div>

        <?php if ($success): ?>
            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                <div class="flex">
                    <svg class="h-5 w-5 text-green-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    <div class="text-sm text-green-700">
                        <p class="font-medium">パスワードがリセットされました</p>
                        <p class="mt-1">新しいパスワードでログインできます。</p>
                    </div>
                </div>
            </div>
            
            <div class="text-center">
                <a href="<?= url('login') ?>" 
                   class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    ログインページへ
                </a>
            </div>
        <?php elseif ($validToken): ?>
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
                
                <div class="space-y-4">
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                            新しいパスワード
                        </label>
                        <div class="relative">
                            <input id="password" 
                                   name="password" 
                                   type="password" 
                                   autocomplete="new-password" 
                                   required 
                                   class="appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm" 
                                   placeholder="8文字以上（大文字・小文字・数字を含む）">
                            <button type="button" class="absolute inset-y-0 right-0 pr-3 flex items-center text-sm leading-5" onclick="togglePasswordVisibility('password')">
                                <svg id="password-toggle-icon-show" class="h-5 w-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <svg id="password-toggle-icon-hide" class="h-5 w-5 text-gray-500 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7 .95-3.15 3.544-5.47 6.542-6.35M15.75 15.75l-2.086-2.086m4.072.086a11.95 11.95 0 00-4.072-4.072M12 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </button>
                        </div>
                    </div>
                    
                    <div>
                        <label for="password_confirm" class="block text-sm font-medium text-gray-700 mb-2">
                            パスワード（確認）
                        </label>
                        <div class="relative">
                            <input id="password_confirm" 
                                   name="password_confirm" 
                                   type="password" 
                                   autocomplete="new-password" 
                                   required 
                                   class="appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm" 
                                   placeholder="パスワードを再入力してください">
                            <button type="button" class="absolute inset-y-0 right-0 pr-3 flex items-center text-sm leading-5" onclick="togglePasswordVisibility('password_confirm')">
                                <svg id="password_confirm-toggle-icon-show" class="h-5 w-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <svg id="password_confirm-toggle-icon-hide" class="h-5 w-5 text-gray-500 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7 .95-3.15 3.544-5.47 6.542-6.35M15.75 15.75l-2.086-2.086m4.072.086a11.95 11.95 0 00-4.072-4.072M12 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <div class="flex">
                        <svg class="h-5 w-5 text-blue-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                        <div class="text-sm text-blue-700">
                            <p class="font-medium">パスワード要件</p>
                            <ul class="mt-1 list-disc list-inside space-y-1">
                                <li>8文字以上</li>
                                <li>大文字を含む</li>
                                <li>小文字を含む</li>
                                <li>数字を含む</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div>
                    <button type="submit" 
                            class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                            <svg class="h-5 w-5 text-blue-500 group-hover:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                            </svg>
                        </span>
                        パスワードを更新
                    </button>
                </div>
            </form>
        <?php else: ?>
            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                <div class="flex">
                    <svg class="h-5 w-5 text-red-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                    <div class="text-sm text-red-700">
                        <p class="font-medium">無効なリンクです</p>
                        <p class="mt-1">パスワードリセット用のリンクが無効または期限切れです。</p>
                        <p class="mt-2">新しいパスワードリセットリンクをリクエストしてください。</p>
                    </div>
                </div>
            </div>
            
            <div class="text-center space-y-4">
                <a href="<?= url('forgot-password') ?>" 
                <a href="<?= url('forgot-password') ?>" 
                   class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    新しいリセットリンクを取得
                </a>
                
                <a href="<?= url('login') ?>" class="font-medium text-blue-600 hover:text-blue-500">
                <a href="<?= url('login') ?>" class="font-medium text-blue-600 hover:text-blue-500">
                    ← ログインページに戻る
                </a>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include 'includes/footer.php'; ?>

<script>
function togglePasswordVisibility(fieldId) {
    const passwordField = document.getElementById(fieldId);
    const showIcon = document.getElementById(`${fieldId}-toggle-icon-show`);
    const hideIcon = document.getElementById(`${fieldId}-toggle-icon-hide`);

    if (passwordField.type === 'password') {
        passwordField.type = 'text';
        showIcon.classList.add('hidden');
        hideIcon.classList.remove('hidden');
    } else {
        passwordField.type = 'password';
        showIcon.classList.remove('hidden');
        hideIcon.classList.add('hidden');
    }
}
</script>
