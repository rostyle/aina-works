<?php
require_once 'config/config.php';

// ログインチェック
if (!isLoggedIn() || (!isset($_GET['id']) && !hasPermission('create_work'))) {
    redirect(url('login'));
}

$user = getCurrentUser();
$db = Database::getInstance();
$errors = [];
$success = false;

$workId = (int)($_GET['id'] ?? 0);
$isEditMode = ($workId > 0);

$formData = [
    'title' => '',
    'description' => '',
    'category_id' => '',
    'price_min' => '',
    'price_max' => '',
    'main_image' => '',
    'images' => '[]',
    'tags' => '[]',
    'technologies' => '[]',
    'duration_weeks' => ''
];

if ($isEditMode) {
    $existingWork = $db->selectOne("SELECT * FROM works WHERE id = ? AND user_id = ?", [$workId, $user['id']]);
    if ($existingWork) {
        $formData = array_merge($formData, $existingWork);
    } else {
        redirect(url('dashboard')); // Not found or not owner
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = '不正なリクエストです。';
    } else {
        $formData['title'] = trim($_POST['title'] ?? '');
        $formData['description'] = trim($_POST['description'] ?? '');
        $formData['category_id'] = (int)($_POST['category_id'] ?? 0);
        $formData['price_min'] = (int)($_POST['price_min'] ?? 0);
        $formData['price_max'] = (int)($_POST['price_max'] ?? 0);
        $formData['duration_weeks'] = (int)($_POST['duration_weeks'] ?? 0);

        // Main image upload (resize & compress)
        if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] === UPLOAD_ERR_OK) {
            try {
                // 目安: 1600x900以内、品質82、画像のみ許可
                $formData['main_image'] = uploadImage(
                    $_FILES['main_image'],
                    ['max_width' => 1600, 'max_height' => 900, 'quality' => 82, 'strict' => true]
                );
            } catch (Exception $e) {
                $errors[] = 'メイン画像のアップロードに失敗しました: ' . $e->getMessage();
            }
        }

        if (empty($errors)) {
            $dataToSave = [
                $user['id'],
                $formData['title'],
                $formData['description'],
                $formData['category_id'],
                $formData['price_min'],
                $formData['price_max'],
                $formData['main_image'],
                $formData['duration_weeks']
            ];

            try {
                if ($isEditMode) {
                    $db->update(
                        "UPDATE works SET user_id=?, title=?, description=?, category_id=?, price_min=?, price_max=?, main_image=?, duration_weeks=? WHERE id=?",
                        array_merge($dataToSave, [$workId])
                    );
                } else {
                    $workId = $db->insert(
                        "INSERT INTO works (user_id, title, description, category_id, price_min, price_max, main_image, duration_weeks, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'published')",
                        $dataToSave
                    );
                }
                setFlash('success', '作品を保存しました。');
                redirect(url('work-detail?id=' . $workId));
            } catch (Exception $e) {
                $errors[] = '作品の保存に失敗しました。';
            }
        }
    }
}

$categories = $db->select("SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order ASC");

$pageTitle = $isEditMode ? '作品の編集' : '作品の新規登録';
include 'includes/header.php';
?>

<section class="py-8 bg-gray-50 min-h-screen">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-8"><?= $pageTitle ?></h1>

        <?php if (!empty($errors)):
            foreach ($errors as $error) {
                echo "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6' role='alert'><p>" . h($error) . "</p></div>";
            }
        endif; ?>

        <form method="POST" enctype="multipart/form-data" class="bg-white rounded-lg shadow-sm border border-gray-200 p-8 space-y-6">
            <input type="hidden" name="csrf_token" value="<?= h(generateCsrfToken()) ?>">

            <div>
                <label for="title" class="block text-sm font-medium text-gray-700">タイトル</label>
                <input type="text" name="title" id="title" value="<?= h($formData['title']) ?>" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
            </div>

            <div>
                <label for="description" class="block text-sm font-medium text-gray-700">説明</label>
                <textarea name="description" id="description" rows="4" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"><?= h($formData['description']) ?></textarea>
            </div>

            <div>
                <label for="category_id" class="block text-sm font-medium text-gray-700">カテゴリ</label>
                <select name="category_id" id="category_id" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    <option value="">選択してください</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= h($category['id']) ?>" <?= ($formData['category_id'] == $category['id']) ? 'selected' : '' ?>><?= h($category['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="grid grid-cols-2 gap-6">
                <div>
                    <label for="price_min" class="block text-sm font-medium text-gray-700">最低価格</label>
                    <input type="number" name="price_min" id="price_min" value="<?= h($formData['price_min']) ?>" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                </div>
                <div>
                    <label for="price_max" class="block text-sm font-medium text-gray-700">最高価格</label>
                    <input type="number" name="price_max" id="price_max" value="<?= h($formData['price_max']) ?>" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                </div>
            </div>

            <div>
                <label for="duration_weeks" class="block text-sm font-medium text-gray-700">制作期間（週）</label>
                <input type="number" name="duration_weeks" id="duration_weeks" value="<?= h($formData['duration_weeks']) ?>" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">メイン画像</label>
                <?php if ($isEditMode && $formData['main_image']): ?>
                    <div class="mt-2">
                        <img src="<?= uploaded_asset($formData['main_image']) ?>" alt="現在の画像" class="w-32 h-32 object-cover rounded-md">
                    </div>
                <?php endif; ?>
                <input type="file" name="main_image" id="main_image" accept="image/jpeg,image/png,image/gif" class="mt-2">
                <p class="mt-1 text-xs text-gray-500">推奨: 1600×900px（16:9）、5MB以下。アップロード時に自動でリサイズ・圧縮されます。</p>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="px-6 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">保存</button>
            </div>
        </form>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
