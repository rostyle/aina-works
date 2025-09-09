<?php
require_once 'config/config.php';

$pageTitle = 'ログイン';
$pageDescription = 'AiNA Worksにログインしてください';

// 既にログインしている場合はリダイレクト
if (isLoggedIn()) {
    redirect(url('dashboard'));
}

$errors = [];
$email = '';
$password = '';

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
        
        // エラーがない場合はAPI認証処理
        if (empty($errors)) {
            try {
                $loginResult = performApiLogin($email, $password);
                
                if ($loginResult['success']) {
                    setFlash('success', 'ログインしました。');
                    
                    // リダイレクト先を決定
                    $redirectUrl = $_GET['redirect'] ?? 'dashboard';
                    redirect(url($redirectUrl));
                } else {
                    $errors['general'] = $loginResult['message'];
                }
            } catch (Exception $e) {
                error_log('ログインエラー: ' . $e->getMessage());
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
                AiNA Worksにログイン
            </h2>
            <div class="mt-4 bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="text-center">
                    <p class="text-sm font-medium text-blue-800 mb-2">
                        AiNAマイページのログイン情報でログインしてください
                    </p>
                    <p class="text-xs text-blue-600">
                        メンバープラン以上でアクティブな会員のみ使用できます
                    </p>
                </div>
            </div>
        </div>

        <?php if (!empty($errors['general'])): ?>
            <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-8">
                <div class="flex">
                    <svg class="h-5 w-5 text-red-400 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                    <div class="flex-1">
                        <h3 class="text-sm font-medium text-red-800 mb-1">ログインに失敗しました</h3>
                        <p class="text-sm text-red-700"><?= h($errors['general']) ?></p>
                        
                        <?php if (strpos($errors['general'], 'メールアドレスまたはパスワード') !== false): ?>
                            <div class="mt-3 text-xs text-red-600">
                                <p><strong>解決方法：</strong></p>
                                <ul class="list-disc list-inside mt-1 space-y-1">
                                    <li>メールアドレスとパスワードを再度確認してください</li>
                                    <li>AiNA マイページと同じログイン情報を使用してください</li>
                                    <li>パスワードを忘れた場合は AiNA マイページでリセットしてください</li>
                                </ul>
                            </div>
                        <?php elseif (strpos($errors['general'], 'プラン') !== false): ?>
                            <div class="mt-3 text-xs text-red-600">
                                <p><strong>解決方法：</strong></p>
                                <ul class="list-disc list-inside mt-1 space-y-1">
                                    <li>AiNA マイページでプラン状況を確認してください</li>
                                    <li>メンバープラン以上へのアップグレードが必要です</li>
                                </ul>
                            </div>
                        <?php elseif (strpos($errors['general'], 'サーバー') !== false || strpos($errors['general'], '接続') !== false): ?>
                            <div class="mt-3 text-xs text-red-600">
                                <p><strong>解決方法：</strong></p>
                                <ul class="list-disc list-inside mt-1 space-y-1">
                                    <li>しばらく時間をおいて再度お試しください</li>
                                    <li>インターネット接続を確認してください</li>
                                    <li>問題が続く場合は管理者にお問い合わせください</li>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <form class="mt-8 space-y-6" method="POST">
            <input type="hidden" name="csrf_token" value="<?= h(generateCsrfToken()) ?>">
            
            <div class="rounded-md shadow-sm space-y-4">
                <div>
                    <label for="email" class="sr-only">メールアドレス</label>
                    <input id="email" 
                           name="email" 
                           type="email" 
                           autocomplete="email" 
                           required 
                           value="<?= h($email) ?>"
                           class="appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm" 
                           placeholder="AiNAマイページに登録のメールアドレス">
                    <?php if (!empty($errors['email'])): ?>
                        <p class="mt-2 text-sm text-red-600"><?= h($errors['email']) ?></p>
                    <?php endif; ?>
                </div>
                
                <div>
                    <label for="password" class="sr-only">パスワード</label>
                    <input id="password" 
                           name="password" 
                           type="password" 
                           autocomplete="current-password" 
                           required 
                           class="appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm" 
                           placeholder="パスワード">
                    <?php if (!empty($errors['password'])): ?>
                        <p class="mt-2 text-sm text-red-600"><?= h($errors['password']) ?></p>
                    <?php endif; ?>
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

<?php include 'includes/footer.php'; ?>