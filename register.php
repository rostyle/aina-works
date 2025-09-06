<?php
require_once 'config/config.php';

$pageTitle = '新規登録';
$pageDescription = 'AiNA Worksに新規登録してクリエイターとしても依頼者としても活動できます';

// 既にログインしている場合はリダイレクト
if (isLoggedIn()) {
    redirect(url('dashboard.php'));
}

// データベース接続
$db = Database::getInstance();

$errors = [];
$formData = [
    'email' => '',
    'full_name' => '',
    'nickname' => '',
    'bio' => '',
    'location' => ''
];

// フォーム送信処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRFトークン検証
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors['general'] = '不正なリクエストです。';
    } else {
        // フォームデータ取得
        $formData['email'] = trim($_POST['email'] ?? '');
        $formData['full_name'] = trim($_POST['full_name'] ?? '');
        $formData['nickname'] = trim($_POST['nickname'] ?? '');
        $formData['bio'] = trim($_POST['bio'] ?? '');
        $formData['location'] = trim($_POST['location'] ?? '');
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';

        // バリデーション
        if (empty($formData['nickname'])) {
            $errors['nickname'] = 'ニックネームは必須です。';
        } elseif (mb_strlen($formData['nickname']) > 50) {
            $errors['nickname'] = 'ニックネームは50文字以内で入力してください。';
        }

        if (empty($formData['email'])) {
            $errors['email'] = 'メールアドレスは必須です。';
        } elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'メールアドレスの形式が正しくありません。';
        } else {
            // メールアドレスの重複チェック
            $existingUser = $db->selectOne(
                "SELECT id FROM users WHERE email = ?",
                [$formData['email']]
            );
            if ($existingUser) {
                $errors['email'] = 'このメールアドレスは既に登録されています。';
            }
        }

        if (empty($formData['full_name'])) {
            $errors['full_name'] = '氏名は必須です。';
        } elseif (mb_strlen($formData['full_name']) > 100) {
            $errors['full_name'] = '氏名は100文字以内で入力してください。';
        }


        if (empty($password)) {
            $errors['password'] = 'パスワードは必須です。';
        } elseif (strlen($password) < 8) {
            $errors['password'] = 'パスワードは8文字以上で入力してください。';
        } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/', $password)) {
            $errors['password'] = 'パスワードは大文字・小文字・数字を含む必要があります。';
        }

        if ($password !== $passwordConfirm) {
            $errors['password_confirm'] = 'パスワードが一致しません。';
        }

        if (!empty($formData['bio']) && mb_strlen($formData['bio']) > 1000) {
            $errors['bio'] = '自己紹介は1000文字以内で入力してください。';
        }

        if (!empty($formData['location']) && mb_strlen($formData['location']) > 100) {
            $errors['location'] = '所在地は100文字以内で入力してください。';
        }

        // 利用規約同意チェック
        if (!isset($_POST['terms']) || $_POST['terms'] !== 'on') {
            $errors['terms'] = '利用規約およびプライバシーポリシーに同意してください。';
        }

        // エラーがない場合は保存
        if (empty($errors)) {
            try {
                $db->beginTransaction();

                // ニックネームからユーザー名を自動生成（メールアドレスのローカル部分を使用）
                $username = explode('@', $formData['email'])[0];
                
                $userId = $db->insert("
                    INSERT INTO users (
                        username, email, password_hash, full_name, nickname, user_type, 
                        active_role, bio, location
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ", [
                    $username, // メールアドレスから自動生成
                    $formData['email'],
                    password_hash($password, PASSWORD_DEFAULT),
                    $formData['full_name'],
                    $formData['nickname'],
                    'creator', // デフォルトでcreatorに設定
                    'creator', // active_roleもcreatorに設定
                    $formData['bio'],
                    $formData['location']
                ]);

                // 両方のロールを追加
                $db->insert("
                    INSERT INTO user_roles (user_id, role, is_enabled) 
                    VALUES (?, ?, 1), (?, ?, 1)
                ", [$userId, 'creator', $userId, 'client']);

                $db->commit();

                // 自動ログイン
                $_SESSION['user_id'] = $userId;
                $_SESSION['user_type'] = 'creator';

                setFlash('success', 'アカウントを作成しました。AiNA Worksへようこそ！');
                redirect(url('dashboard.php'));

            } catch (Exception $e) {
                $db->rollback();
                // デバッグ用：詳細なエラー情報を表示
                if (defined('DEBUG') && DEBUG) {
                    $errors['general'] = 'アカウントの作成に失敗しました。エラー: ' . $e->getMessage();
                } else {
                    $errors['general'] = 'アカウントの作成に失敗しました。再度お試しください。';
                }
                // エラーログに記録
                error_log("Registration error: " . $e->getMessage() . " - File: " . $e->getFile() . " - Line: " . $e->getLine());
            }
        }
    }
}

include 'includes/header.php';
?>

<!-- Register Section -->
<section class="min-h-screen bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-2xl mx-auto">
        <div class="text-center mb-8">
            <div class="mx-auto h-12 w-12 flex items-center justify-center rounded-full bg-gradient-to-r from-blue-600 to-purple-600">
                <span class="text-white font-bold text-xl">CM</span>
            </div>
            <h2 class="mt-6 text-3xl font-extrabold text-gray-900">
                新規登録
            </h2>
            <p class="mt-2 text-sm text-gray-600">
                既にアカウントをお持ちの場合は
                <a href="<?= url('login.php') ?>" class="font-medium text-blue-600 hover:text-blue-500">
                    ログイン
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
                        <p class="text-sm font-medium text-red-800 mb-2">入力エラーがあります</p>
                        <ul class="text-sm text-red-700 space-y-1">
                            <li><?= h($errors['general']) ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <form method="POST" class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 space-y-6">
            <input type="hidden" name="csrf_token" value="<?= h(generateCsrfToken()) ?>">

            <!-- Basic Information -->
            <div>
                <h2 class="text-xl font-bold text-gray-900 mb-4">基本情報</h2>
            </div>
            
            <div class="space-y-6">
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                        メールアドレス <span class="text-red-500">*</span>
                    </label>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           value="<?= h($formData['email']) ?>"
                           required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="例: tanaka@example.com">
                    <p class="text-sm text-gray-500 mt-1">ログイン時に使用します</p>
                    <?php if (!empty($errors['email'])): ?>
                        <p class="mt-2 text-sm text-red-600"><?= h($errors['email']) ?></p>
                    <?php endif; ?>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="full_name" class="block text-sm font-medium text-gray-700 mb-2">
                            氏名 <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               id="full_name" 
                               name="full_name" 
                               value="<?= h($formData['full_name']) ?>"
                               maxlength="100"
                               required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="例: 田中 愛">
                        <?php if (!empty($errors['full_name'])): ?>
                            <p class="mt-2 text-sm text-red-600"><?= h($errors['full_name']) ?></p>
                        <?php endif; ?>
                    </div>

                    <div>
                        <label for="nickname" class="block text-sm font-medium text-gray-700 mb-2">
                            ニックネーム <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               id="nickname" 
                               name="nickname" 
                               value="<?= h($formData['nickname']) ?>"
                               maxlength="50"
                               required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="例: AI Creator">
                        <p class="text-sm text-gray-500 mt-1">プロフィールで表示される名前</p>
                        <?php if (!empty($errors['nickname'])): ?>
                            <p class="mt-2 text-sm text-red-600"><?= h($errors['nickname']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Password -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                        パスワード <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input type="password" 
                               id="password" 
                               name="password" 
                               minlength="8"
                               required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="8文字以上">
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
                    <p class="text-sm text-gray-500 mt-1">大文字・小文字・数字を含む8文字以上</p>
                    <?php if (!empty($errors['password'])): ?>
                        <p class="mt-2 text-sm text-red-600"><?= h($errors['password']) ?></p>
                    <?php endif; ?>
                </div>

                <div>
                    <label for="password_confirm" class="block text-sm font-medium text-gray-700 mb-2">
                        パスワード（確認） <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input type="password" 
                               id="password_confirm" 
                               name="password_confirm" 
                               minlength="8"
                               required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="もう一度入力">
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
                    <?php if (!empty($errors['password_confirm'])): ?>
                        <p class="mt-2 text-sm text-red-600"><?= h($errors['password_confirm']) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Optional Information -->
            <div class="border-t border-gray-200 pt-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">追加情報（任意）</h2>
                <p class="text-sm text-gray-600 mb-4">登録後、クリエイターとしても依頼者としても活動できます</p>
                
                <div class="space-y-6">
                    <div>
                        <label for="bio" class="block text-sm font-medium text-gray-700 mb-2">
                            自己紹介・事業内容
                        </label>
                        <textarea id="bio" 
                                  name="bio" 
                                  rows="4" 
                                  maxlength="1000"
                                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-vertical"
                                  placeholder="スキルや経験、得意分野、事業内容などを自由に記載してください"><?= h($formData['bio']) ?></textarea>
                        <p class="text-sm text-gray-500 mt-1">1000文字以内</p>
                        <?php if (!empty($errors['bio'])): ?>
                            <p class="mt-2 text-sm text-red-600"><?= h($errors['bio']) ?></p>
                        <?php endif; ?>
                    </div>

                    <div>
                        <label for="location" class="block text-sm font-medium text-gray-700 mb-2">
                            所在地
                        </label>
                        <input type="text" 
                               id="location" 
                               name="location" 
                               value="<?= h($formData['location']) ?>"
                               maxlength="100"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="例: 東京都渋谷区">
                        <?php if (!empty($errors['location'])): ?>
                            <p class="mt-2 text-sm text-red-600"><?= h($errors['location']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Terms -->
            <div class="flex items-start">
                <input id="terms" 
                       name="terms" 
                       type="checkbox" 
                       required
                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded mt-1">
                <label for="terms" class="ml-3 text-sm text-gray-700">
                    <a href="<?= url('terms.php') ?>" class="text-blue-600 hover:text-blue-500">利用規約</a>
                    および
                    <a href="<?= url('privacy.php') ?>" class="text-blue-600 hover:text-blue-500">プライバシーポリシー</a>
                    に同意します <span class="text-red-500">*</span>
                </label>
                <?php if (!empty($errors['terms'])): ?>
                    <p class="mt-2 text-sm text-red-600"><?= h($errors['terms']) ?></p>
                <?php endif; ?>
            </div>

            <!-- Submit Button -->
            <div class="border-t border-gray-200 pt-6">
                <button type="submit" 
                        class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    新規登録
                </button>
            </div>
        </form>
    </div>
</section>

<script>
// Password validation
document.addEventListener('DOMContentLoaded', function() {
    const password = document.getElementById('password');
    const passwordConfirm = document.getElementById('password_confirm');

    function validatePassword() {
        const value = password.value;
        const hasLower = /[a-z]/.test(value);
        const hasUpper = /[A-Z]/.test(value);
        const hasNumber = /\d/.test(value);
        const isLongEnough = value.length >= 8;

        if (value && (!hasLower || !hasUpper || !hasNumber || !isLongEnough)) {
            password.setCustomValidity('パスワードは大文字・小文字・数字を含む8文字以上で入力してください');
        } else {
            password.setCustomValidity('');
        }
    }

    function validatePasswordConfirm() {
        if (passwordConfirm.value && password.value !== passwordConfirm.value) {
            passwordConfirm.setCustomValidity('パスワードが一致しません');
        } else {
            passwordConfirm.setCustomValidity('');
        }
    }

    password.addEventListener('input', validatePassword);
    passwordConfirm.addEventListener('input', validatePasswordConfirm);
});

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
