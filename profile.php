<?php
require_once 'config/config.php';

// ログインチェック
if (!isLoggedIn()) {
    redirect(url('login'));
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
        $isCreator = isset($_POST['is_creator']) ? 1 : 0;
        $isClient = isset($_POST['is_client']) ? 1 : 0;

        // バリデーション
        if (empty($fullName)) {
            $errors[] = '氏名は必須です。';
        }
        
        // 少なくとも一つのロールが選択されている必要がある
        if (!$isCreator && !$isClient) {
            $errors[] = 'クリエイターまたは依頼者のいずれかは選択してください。';
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
                $db->beginTransaction();
                
                // ユーザー情報を更新
                $db->update(
                    "UPDATE users SET full_name = ?, bio = ?, location = ?, profile_image = ?, is_creator = ?, is_client = ? WHERE id = ?",
                    [$fullName, $bio, $location, $profileImagePath, $isCreator, $isClient, $user['id']]
                );
                
                // user_rolesテーブルを更新
                // 既存のロールを削除
                $db->delete("DELETE FROM user_roles WHERE user_id = ?", [$user['id']]);
                
                // 新しいロールを追加
                if ($isCreator) {
                    $db->insert("INSERT INTO user_roles (user_id, role, is_enabled) VALUES (?, 'creator', 1)", [$user['id']]);
                }
                if ($isClient) {
                    $db->insert("INSERT INTO user_roles (user_id, role, is_enabled) VALUES (?, 'client', 1)", [$user['id']]);
                }
                
                // active_roleを更新（現在のロールが無効になった場合は最初の有効なロールに変更）
                $currentRole = getCurrentRole();
                $availableRoles = [];
                if ($isCreator) $availableRoles[] = 'creator';
                if ($isClient) $availableRoles[] = 'client';
                
                if (!in_array($currentRole, $availableRoles) && !empty($availableRoles)) {
                    $newActiveRole = $availableRoles[0];
                    $db->update("UPDATE users SET active_role = ? WHERE id = ?", [$newActiveRole, $user['id']]);
                    $_SESSION['active_role'] = $newActiveRole;
                }
                
                $db->commit();
                $success = true;
                setFlash('success', 'プロフィールを更新しました。');
                // 更新されたユーザー情報を再取得
                $user = $db->selectOne("SELECT * FROM users WHERE id = ?", [$user['id']]);
            } catch (Exception $e) {
                $db->rollback();
                $errors[] = 'プロフィールの更新に失敗しました: ' . $e->getMessage();
                // デバッグ用（本番環境では削除）
                if (defined('DEBUG') && DEBUG) {
                    error_log("Profile update error: " . $e->getMessage());
                    error_log("Stack trace: " . $e->getTraceAsString());
                }
            }
        }
    }
}

$pageTitle = 'プロフィール編集';
include 'includes/header.php';
?>

<section class="py-4 sm:py-8 bg-gray-50 min-h-screen">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-6 sm:mb-8 break-words">プロフィール編集</h1>

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

        <form method="POST" enctype="multipart/form-data" class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 sm:p-6 lg:p-8 space-y-4 sm:space-y-6">
            <input type="hidden" name="csrf_token" value="<?= h(generateCsrfToken()) ?>">

            <div class="flex flex-col sm:flex-row sm:items-center space-y-4 sm:space-y-0 sm:space-x-6">
                <img src="<?= uploaded_asset($user['profile_image'] ?? 'assets/images/default-avatar.png') ?>" alt="プロフィール画像" class="w-20 h-20 sm:w-24 sm:h-24 rounded-full object-cover mx-auto sm:mx-0">
                <div class="text-center sm:text-left">
                    <label for="profile_image" class="block text-sm font-medium text-gray-700 mb-2">プロフィール画像を変更</label>
                    <input type="file" name="profile_image" id="profile_image" class="text-sm text-gray-600 w-full sm:w-auto">
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

            <div class="border-t pt-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">アカウントタイプ設定</h3>
                <p class="text-sm text-gray-600 mb-4">クリエイターと依頼者の両方の機能を使用できます。どちらを使用するか選択してください。</p>
                
                <div class="space-y-3">
                    <div class="flex items-center">
                        <input type="checkbox" name="is_creator" id="is_creator" value="1" <?= ($user['is_creator'] ?? 1) ? 'checked' : '' ?> class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="is_creator" class="ml-2 block text-sm text-gray-900">
                            <span class="font-medium">クリエイター</span>
                            <span class="text-gray-500">- 作品を投稿し、案件に応募できます</span>
                        </label>
                    </div>
                    
                    <div class="flex items-center">
                        <input type="checkbox" name="is_client" id="is_client" value="1" <?= ($user['is_client'] ?? 1) ? 'checked' : '' ?> class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="is_client" class="ml-2 block text-sm text-gray-900">
                            <span class="font-medium">依頼者</span>
                            <span class="text-gray-500">- 案件を投稿し、クリエイターを探せます</span>
                        </label>
                    </div>
                </div>
                
                <div class="mt-4 p-3 bg-blue-50 rounded-md">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-blue-700">
                                両方のタイプを選択することで、クリエイターとして作品を投稿しながら、依頼者として案件を投稿することも可能です。
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end pt-4">
                <button type="submit" class="w-full sm:w-auto px-6 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">保存</button>
            </div>
        </form>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
