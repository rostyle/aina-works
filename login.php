<?php
require_once 'config/config.php';

$pageTitle = 'ログイン';
$pageDescription = 'AiNA Worksにログインしてください';

// 既にログインしている場合はリダイレクト
if (isLoggedIn()) {
    redirect(url('dashboard.php'));
}

$errors = [];
$email = '';

// フォーム送信処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRFトークン検証
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors['general'] = '不正なリクエストです。';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        // バリデーション
        if (empty($email)) {
            $errors['email'] = 'メールアドレスを入力してください。';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'メールアドレスの形式が正しくありません。';
        }
        
        if (empty($password)) {
            $errors['password'] = 'パスワードを入力してください。';
        }
        
        // エラーがない場合は認証処理
        if (empty($errors)) {
            try {
                $db = Database::getInstance();
                $user = $db->selectOne(
                    "SELECT * FROM users WHERE email = ? AND is_active = 1",
                    [$email]
                );
                
                if ($user && password_verify($password, $user['password_hash'])) {
                    // ログイン成功
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_type'] = $user['user_type']; // 後方互換性のため残す
                    $_SESSION['active_role'] = $user['active_role']; // 現在のアクティブロール
                    
                    setFlash('success', 'ログインしました。');
                    
                    // リダイレクト先を決定
                    $redirectUrl = $_GET['redirect'] ?? 'dashboard.php';
                    redirect(url($redirectUrl));
                } else {
                    $errors['general'] = 'メールアドレスまたはパスワードが間違っています。';
                }
            } catch (Exception $e) {
                $errors['general'] = 'ログインに失敗しました。再度お試しください。';
            }
        }
    }
}

include 'includes/header.php';
?>

<!-- Login Section -->
<section class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div>
            <div class="mx-auto h-12 w-12 flex items-center justify-center rounded-full bg-gradient-to-r from-blue-600 to-purple-600">
                <span class="text-white font-bold text-xl">CM</span>
            </div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                アカウントにログイン
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                アカウントをお持ちでない場合は
                <a href="<?= url('register.php') ?>" class="font-medium text-blue-600 hover:text-blue-500">
                    新規登録
                </a>
            </p>
        </div>

        <?php if (!empty($errors['general'])): ?>
            <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-8">
                <div class="flex">
                    <svg class="h-5 w-5 text-red-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                    <div>
                        <p class="text-sm text-red-700"><?= h($errors['general']) ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <form class="mt-8 space-y-6" method="POST">
            <input type="hidden" name="csrf_token" value="<?= h(generateCsrfToken()) ?>">
            
            <div class="rounded-md shadow-sm -space-y-px">
                <div>
                    <label for="email" class="sr-only">メールアドレス</label>
                    <input id="email" 
                           name="email" 
                           type="email" 
                           autocomplete="email" 
                           required 
                           value="<?= h($email) ?>"
                           class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm" 
                           placeholder="メールアドレス">
                    <?php if (!empty($errors['email'])): ?>
                        <p class="mt-2 text-sm text-red-600"><?= h($errors['email']) ?></p>
                    <?php endif; ?>
                </div>
                <div class="relative">
                    <label for="password" class="sr-only">パスワード</label>
                    <input id="password" 
                           name="password" 
                           type="password" 
                           autocomplete="current-password" 
                           required 
                           class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm" 
                           placeholder="パスワード">
                    <button type="button" class="absolute inset-y-0 right-0 pr-3 flex items-center text-sm leading-5" onclick="togglePasswordVisibility('password')">
                        <svg id="password-toggle-icon-show" class="h-5 w-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        <svg id="password-toggle-icon-hide" class="h-5 w-5 text-gray-500 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7 .95-3.15 3.544-5.47 6.542-6.35M15.75 15.75l-2.086-2.086m4.072.086a11.95 11.95 0 00-4.072-4.072M12 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </button>
                    <?php if (!empty($errors['password'])): ?>
                        <p class="mt-2 text-sm text-red-600"><?= h($errors['password']) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <input id="remember-me" 
                           name="remember-me" 
                           type="checkbox" 
                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                    <label for="remember-me" class="ml-2 block text-sm text-gray-900">
                        ログイン状態を保持
                    </label>
                </div>

                <div class="text-sm">
                    <a href="<?= url('forgot-password.php') ?>" class="font-medium text-blue-600 hover:text-blue-500">
                        パスワードを忘れた場合
                    </a>
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
                    ログイン
                </button>
            </div>

        </form>
    </div>
</section>

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

<?php include 'includes/footer.php'; ?>
