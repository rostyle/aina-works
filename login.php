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
        // メールアドレスとパスワードの前後空白・改行を削除
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');
        
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
        <div class="text-center">
            <div class="mx-auto h-20 w-20 flex items-center justify-center rounded-2xl bg-white shadow-xl border border-gray-100 mb-8 transform hover:scale-105 transition-transform duration-300">
                <img src="<?= asset('images/logo.png') ?>" alt="AiNA Works Logo" class="h-12 w-auto">
            </div>
            <h2 class="text-3xl font-extrabold text-gray-900 tracking-tight">
                AiNA Worksにログイン
            </h2>
            <div class="mt-4 bg-gradient-to-br from-blue-50 to-indigo-50 border border-blue-100 rounded-2xl p-5 shadow-sm">
                <div class="flex items-center space-x-3">
                    <div class="flex-shrink-0 w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center shadow-lg shadow-blue-200">
                        <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="text-left">
                        <p class="text-sm font-bold text-gray-900">
                            AiNAマイページのログイン情報を使用
                        </p>
                        <p class="text-xs text-gray-600 mt-0.5">
                            メンバープラン以上のアクティブ会員限定
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!empty($errors['general'])): ?>
            <div class="bg-red-50 border border-red-200 rounded-2xl p-5 shadow-sm animate-shake">
                <div class="flex">
                    <div class="flex-shrink-0 w-8 h-8 bg-red-100 rounded-full flex items-center justify-center">
                        <svg class="h-5 w-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div class="ml-4 flex-1">
                        <h3 class="text-sm font-bold text-red-800">ログインに失敗しました</h3>
                        <p class="text-sm text-red-700 mt-1"><?= h($errors['general']) ?></p>
                        
                        <?php if (strpos($errors['general'], 'メールアドレス') !== false || strpos($errors['general'], 'パスワード') !== false): ?>
                            <div class="mt-3 bg-white/50 rounded-lg p-3">
                                <ul class="text-xs text-red-600 space-y-1.5">
                                    <li class="flex items-center"><span class="mr-2">●</span>入力内容を再度ご確認ください</li>
                                    <li class="flex items-center"><span class="mr-2">●</span>AiNA マイページと同じ情報が必要です</li>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <style>
                @keyframes shake {
                    0%, 100% { transform: translateX(0); }
                    25% { transform: translateX(-5px); }
                    75% { transform: translateX(5px); }
                }
                .animate-shake { animation: shake 0.4s ease-in-out; }
            </style>
        <?php endif; ?>

        <form class="mt-8 space-y-6" action="login.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= h(generateCsrfToken()) ?>">
            
            <div class="space-y-5">
                <div class="group">
                    <label for="email" class="block text-sm font-bold text-gray-700 mb-2 ml-1 transition-colors group-focus-within:text-blue-600">
                        メールアドレス
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-400 group-focus-within:text-blue-500 transition-colors">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.206" />
                            </svg>
                        </div>
                        <input id="email" 
                               name="email" 
                               type="email" 
                               autocomplete="email" 
                               required 
                               value="<?= h($email) ?>"
                               class="appearance-none block w-full pl-12 pr-4 py-4 border border-gray-200 placeholder-gray-400 text-gray-900 rounded-2xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all hover:border-gray-300" 
                               placeholder="example@aina-works.com">
                    </div>
                    <?php if (!empty($errors['email'])): ?>
                        <p class="mt-2 text-xs text-red-600 font-medium ml-1"><?= h($errors['email']) ?></p>
                    <?php endif; ?>
                </div>
                
                <div class="group">
                    <div class="flex items-center justify-between mb-2">
                        <label for="password" class="block text-sm font-bold text-gray-700 ml-1 transition-colors group-focus-within:text-blue-600">
                            パスワード
                        </label>
                    </div>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-400 group-focus-within:text-blue-500 transition-colors">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        </div>
                        <input id="password" 
                               name="password" 
                               type="password" 
                               autocomplete="current-password" 
                               required 
                               class="appearance-none block w-full pl-12 pr-4 py-4 border border-gray-200 placeholder-gray-400 text-gray-900 rounded-2xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all hover:border-gray-300" 
                               placeholder="••••••••">
                    </div>
                    <?php if (!empty($errors['password'])): ?>
                        <p class="mt-2 text-xs text-red-600 font-medium ml-1"><?= h($errors['password']) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <div>
                <button type="submit" 
                        class="group relative w-full flex justify-center py-4 px-4 border border-transparent text-base font-bold rounded-2xl text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all shadow-lg shadow-blue-200 hover:shadow-blue-300 hover:-translate-y-0.5 active:translate-y-0">
                    <span class="absolute left-0 inset-y-0 flex items-center pl-4">
                        <svg class="h-5 w-5 text-white/80 group-hover:text-white transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                        </svg>
                    </span>
                    ログインする
                </button>
            </div>
        </form>
    </div>
</section>

<?php include 'includes/footer.php'; ?>