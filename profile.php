<?php
require_once 'config/config.php';

// ログインチェック
if (!isLoggedIn()) {
    redirect(url('login.php'));
}

$user = getCurrentUser();
$db = Database::getInstance();
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRFトークン検証
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = '不正なリクエストです。';
    } else {
        // フォームデータ取得
        $fullName = trim($_POST['full_name'] ?? '');
        $bio = trim($_POST['bio'] ?? '');
        $location = trim($_POST['location'] ?? '');

        // バリデーション
        if (empty($fullName)) {
            $errors[] = '氏名は必須です。';
        }

        // 画像アップロード処理
        $profileImagePath = $user['profile_image'];
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            try {
                $profileImagePath = uploadFile($_FILES['profile_image']);
            } catch (Exception $e) {
                $errors[] = $e->getMessage();
            }
        }

        if (empty($errors)) {
            try {
                $db->update(
                    "UPDATE users SET full_name = ?, bio = ?, location = ?, profile_image = ? WHERE id = ?",
                    [$fullName, $bio, $location, $profileImagePath, $user['id']]
                );
                $success = true;
                setFlash('success', 'プロフィールを更新しました。');
                // 更新されたユーザー情報を再取得
                $user = $db->selectOne("SELECT * FROM users WHERE id = ?", [$user['id']]);
            } catch (Exception $e) {
                $errors[] = 'プロフィールの更新に失敗しました。';
            }
        }
    }
}

$pageTitle = 'プロフィール編集';
include 'includes/header.php';
?>

<section class="py-8 bg-gray-50 min-h-screen">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-8">プロフィール編集</h1>

        <?php if ($success): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
                <p class="font-bold">成功</p>
                <p>プロフィールを更新しました。</p>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)):
            foreach ($errors as $error) {
                echo "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6' role='alert'><p>" . h($error) . "</p></div>";
            }
        endif; ?>

        <form method="POST" enctype="multipart/form-data" class="bg-white rounded-lg shadow-sm border border-gray-200 p-8 space-y-6">
            <input type="hidden" name="csrf_token" value="<?= h(generateCsrfToken()) ?>">

            <div class="flex items-center space-x-6">
                <img src="<?= uploaded_asset($user['profile_image'] ?? 'assets/images/default-avatar.png') ?>" alt="プロフィール画像" class="w-24 h-24 rounded-full object-cover">
                <div>
                    <label for="profile_image" class="block text-sm font-medium text-gray-700">プロフィール画像を変更</label>
                    <input type="file" name="profile_image" id="profile_image" class="mt-1 text-sm text-gray-600">
                </div>
            </div>

            <div>
                <label for="full_name" class="block text-sm font-medium text-gray-700">氏名</label>
                <input type="text" name="full_name" id="full_name" value="<?= h($user['full_name'] ?? '') ?>" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            </div>

            <div>
                <label for="bio" class="block text-sm font-medium text-gray-700">自己紹介</label>
                <textarea name="bio" id="bio" rows="4" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"><?= h($user['bio'] ?? '') ?></textarea>
            </div>

            <div>
                <label for="location" class="block text-sm font-medium text-gray-700">所在地</label>
                <input type="text" name="location" id="location" value="<?= h($user['location'] ?? '') ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            </div>

            <div class="flex justify-end">
                <button type="submit" class="px-6 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">保存</button>
            </div>
        </form>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
