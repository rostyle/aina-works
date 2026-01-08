<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/admin_layout.php';
requireAdmin();

$db = Database::getInstance();
$id = (int)($_GET['id'] ?? 0);
$work = null;

if ($id > 0) {
    try {
        $work = $db->selectOne("SELECT * FROM works WHERE id = ?", [$id]);
        if (!$work) {
            setFlash('error', '指定された作品が見つかりません');
            redirect(adminUrl('works.php'));
        }
    } catch (Exception $e) {
        setFlash('error', 'データ取得エラー: ' . $e->getMessage());
    }
}

// Fetch categories and creators for selection
$categories = $db->select("SELECT id, name FROM categories ORDER BY name ASC");
$creators = $db->select("SELECT id, full_name, email FROM users WHERE is_creator = 1 ORDER BY full_name ASC");

// POST handle
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', '不正なリクエストです');
        redirect(adminUrl('works_edit.php' . ($id > 0 ? '?id='.$id : '')));
    }
    
    $title = trim($_POST['title'] ?? '');
    $userId = (int)($_POST['user_id'] ?? 0);
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $status = $_POST['status'] === 'published' ? 'published' : 'archived';
    $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
    
    if ($title === '' || $userId === 0) {
        $error = 'タイトルとクリエイターは必須です';
    } else {
        try {
            $mainImage = $_POST['main_image'] ?? '';

            // Handle file upload
            if (isset($_FILES['main_image_file']) && $_FILES['main_image_file']['error'] === UPLOAD_ERR_OK) {
                try {
                    $filename = uploadImage($_FILES['main_image_file'], ['max_width' => 1200, 'max_height' => 1200]);
                    $mainImage = 'storage/app/uploads/' . $filename;
                } catch (Exception $e) {
                    setFlash('warning', '画像のアップロードに失敗しました（URLは維持されます）: ' . $e->getMessage());
                }
            }

            $data = [
                $userId,
                $categoryId ?: null,
                $title,
                $_POST['description'] ?? '',
                (int)($_POST['price_min'] ?? 0),
                (int)($_POST['price_max'] ?? 0),
                (int)($_POST['duration_weeks'] ?? 0),
                $mainImage,
                $_POST['project_url'] ?? '',
                $status,
                $isFeatured
            ];
            
            if ($id > 0) {
                // Update
                $sql = "UPDATE works SET 
                        user_id=?, category_id=?, title=?, description=?, 
                        price_min=?, price_max=?, duration_weeks=?,
                        main_image=?, project_url=?, status=?, is_featured=? 
                        WHERE id = ?";
                $data[] = $id;
                $db->update($sql, $data);
                setFlash('success', '作品を更新しました');
            } else {
                // Create
                $sql = "INSERT INTO works 
                        (user_id, category_id, title, description, price_min, price_max, duration_weeks, main_image, project_url, status, is_featured, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                $newId = $db->insert($sql, $data);
                setFlash('success', '作品を登録しました');
                $id = $newId;
            }
            redirect(adminUrl('works.php'));
        } catch (Exception $e) {
            $error = 'DBエラー: ' . $e->getMessage();
        }
    }
    
    if (isset($error)) {
        setFlash('error', $error);
    }
}

renderAdminHeader($id > 0 ? '作品編集' : '新規作品登録', 'works');
$flashes = getFlash();
?>

<div class="flex items-center justify-between mb-6">
    <div class="flex items-center gap-4">
        <a href="<?= h(adminUrl('works.php')) ?>" class="text-gray-500 hover:text-gray-700">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
        </a>
        <h1 class="text-2xl font-bold text-gray-900"><?= $id > 0 ? '作品編集: ' . h($work['title']) : '新規作品登録' ?></h1>
    </div>
</div>

<?php if (!empty($flashes)): ?>
  <?php foreach ($flashes as $type => $msg): if (!$msg) continue; ?>
    <div class="mb-4 rounded-lg px-4 py-3 <?= $type === 'success' ? 'bg-green-50 text-green-800 border border-green-200' : 'bg-red-50 text-red-800 border border-red-200' ?>"><?= h($msg) ?></div>
  <?php endforeach; ?>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <input type="hidden" name="csrf_token" value="<?= h(generateCsrfToken()) ?>">
    
    <!-- Left Column (Main Info) -->
    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white border rounded-xl p-6 shadow-sm">
            <h2 class="text-lg font-bold text-gray-900 mb-4 pb-2 border-b">作品情報</h2>
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">作品タイトル <span class="text-red-500">*</span></label>
                    <input type="text" name="title" value="<?= h($work['title'] ?? '') ?>" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-white" required>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">最低価格 (¥)</label>
                        <input type="number" name="price_min" value="<?= (int)($work['price_min'] ?? 0) ?>" class="w-full px-3 py-2 border rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">最高価格 (¥)</label>
                        <input type="number" name="price_max" value="<?= (int)($work['price_max'] ?? 0) ?>" class="w-full px-3 py-2 border rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">制作期間 (週)</label>
                        <input type="number" name="duration_weeks" value="<?= (int)($work['duration_weeks'] ?? 0) ?>" class="w-full px-3 py-2 border rounded-lg">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">説明文</label>
                    <textarea name="description" rows="10" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-white"><?= h($work['description'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

        <div class="bg-white border rounded-xl p-6 shadow-sm">
            <h2 class="text-lg font-bold text-gray-900 mb-4 pb-2 border-b">メディア・リンク</h2>
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">メイン画像</label>
                    <div class="space-y-3">
                        <div class="flex items-center gap-2">
                            <input type="file" name="main_image_file" accept="image/*" class="flex-1 text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-bold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 transition">
                        </div>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-gray-400 text-xs">URL:</span>
                            </div>
                            <input type="text" name="main_image" value="<?= h($work['main_image'] ?? '') ?>" class="pl-12 w-full px-3 py-2 border rounded-lg text-xs font-mono" placeholder="https://example.com/image.jpg">
                        </div>
                    </div>
                    <?php if (!empty($work['main_image'])): ?>
                        <div class="mt-4 aspect-video rounded-xl overflow-hidden border bg-gray-50 shadow-inner group relative">
                            <img src="<?= h(uploaded_asset($work['main_image'])) ?>" class="w-full h-full object-contain">
                            <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                <span class="text-white text-xs font-bold px-2 py-1 bg-black/50 rounded-full">現在の画像</span>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">プロジェクトURL (外部サイト等)</label>
                    <input type="text" name="project_url" value="<?= h($work['project_url'] ?? '') ?>" class="w-full px-3 py-2 border rounded-lg text-sm font-mono" placeholder="https://github.com/... or https://example.com/">
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column (Meta & Actions) -->
    <div class="space-y-6">
        <div class="bg-white border rounded-xl p-6 shadow-sm">
            <h2 class="text-sm font-bold text-gray-900 mb-4 uppercase tracking-wider text-gray-500">属性設定</h2>
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">クリエイター <span class="text-red-500">*</span></label>
                    <select name="user_id" class="w-full px-3 py-2 border rounded-lg" required>
                        <option value="">選択してください</option>
                        <?php foreach ($creators as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= (isset($work['user_id']) && (int)$work['user_id'] === (int)$c['id']) ? 'selected' : '' ?>>
                                <?= h($c['full_name']) ?> (ID: <?= $c['id'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">カテゴリー</label>
                    <select name="category_id" class="w-full px-3 py-2 border rounded-lg">
                        <option value="">選択なし</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= (isset($work['category_id']) && (int)$work['category_id'] === (int)$cat['id']) ? 'selected' : '' ?>>
                                <?= h($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="pt-4 border-t">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="status" value="published" <?= (!isset($work['status']) || $work['status'] === 'published') ? 'checked' : '' ?> class="w-4 h-4 text-blue-600 rounded">
                        <span class="text-sm font-bold text-gray-700">公開する</span>
                    </label>
                </div>

                <div>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_featured" value="1" <?= (isset($work['is_featured']) && (int)$work['is_featured'] === 1) ? 'checked' : '' ?> class="w-4 h-4 text-yellow-600 rounded">
                        <span class="text-sm font-bold text-gray-700">「おすすめ」に設定</span>
                    </label>
                </div>
            </div>
        </div>

        <div class="sticky top-6">
            <button type="submit" class="w-full py-3 bg-blue-600 text-white font-bold rounded-xl hover:shadow-lg hover:bg-blue-700 transition transform hover:-translate-y-0.5">
                <?= $id > 0 ? '変更を保存する' : '新規登録する' ?>
            </button>
            <a href="<?= h(adminUrl('works.php')) ?>" class="block w-full mt-3 py-3 text-center text-gray-600 font-medium hover:text-gray-900 transition font-sm">
                キャンセルして戻る
            </a>
        </div>
    </div>
</form>

<?php renderAdminFooter(); ?>
