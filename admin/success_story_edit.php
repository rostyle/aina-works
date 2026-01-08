<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/admin_layout.php';
requireAdmin();

$db = Database::getInstance();
$id = (int)($_GET['id'] ?? 0);
$story = null;
$sections = [];

if ($id > 0) {
    try {
        $story = $db->selectOne("SELECT * FROM success_stories WHERE id = ?", [$id]);
        if ($story) {
            $sections = $db->select("SELECT * FROM success_story_sections WHERE success_story_id = ? ORDER BY display_order ASC", [$id]);
        } else {
            setFlash('error', '指定された記事が見つかりません');
            redirect(adminUrl('success_stories.php'));
        }
    } catch (Exception $e) {
        setFlash('error', 'データ取得エラー: ' . $e->getMessage());
    }
}

// POST handle
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', '不正なリクエストです');
        redirect($_SERVER['REQUEST_URI']);
    }
    
    $title = trim($_POST['title'] ?? '');
    $memberName = trim($_POST['member_name'] ?? '');
    $interviewDate = trim($_POST['interview_date'] ?? date('Y-m-d'));
    $status = ($_POST['status'] ?? 'draft') === 'published' ? 'published' : 'draft';
    
    if ($title === '' || $memberName === '') {
        $error = 'タイトルとメンバー名は必須です';
    } else {
        try {
            $mainImage = $_POST['main_image'] ?? '';
            $memberImage = $_POST['member_image'] ?? '';

            // Handle main image upload
            if (isset($_FILES['main_image_file']) && $_FILES['main_image_file']['error'] === UPLOAD_ERR_OK) {
                try {
                    $filename = uploadImage($_FILES['main_image_file'], ['max_width' => 1200, 'max_height' => 1200]);
                    $mainImage = 'storage/app/uploads/' . $filename;
                } catch (Exception $e) {
                    setFlash('warning', 'メイン画像のアップロードに失敗しました: ' . $e->getMessage());
                }
            }

            // Handle member image upload
            if (isset($_FILES['member_image_file']) && $_FILES['member_image_file']['error'] === UPLOAD_ERR_OK) {
                try {
                    $filename = uploadImage($_FILES['member_image_file'], ['max_width' => 400, 'max_height' => 400]);
                    $memberImage = 'storage/app/uploads/' . $filename;
                } catch (Exception $e) {
                    setFlash('warning', 'メンバー画像のアップロードに失敗しました: ' . $e->getMessage());
                }
            }

            $data = [
                $title,
                $memberName,
                $_POST['member_role'] ?? '',
                $_POST['category_name'] ?? '',
                $_POST['tag_type'] ?? '',
                $_POST['result_badge'] ?? '',
                $_POST['intro_text'] ?? '',
                $mainImage,
                $memberImage,
                $interviewDate,
                $status
            ];
            
            if ($id > 0) {
                // Update
                $sql = "UPDATE success_stories SET 
                        title=?, member_name=?, member_role=?, category_name=?, tag_type=?, 
                        result_badge=?, intro_text=?, main_image=?, member_image=?, interview_date=?, status=? 
                        WHERE id = ?";
                $data[] = $id;
                $db->update($sql, $data);
                setFlash('success', '記事を更新しました');
            } else {
                // Create
                $sql = "INSERT INTO success_stories 
                        (title, member_name, member_role, category_name, tag_type, result_badge, intro_text, main_image, member_image, interview_date, status, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                $newId = $db->insert($sql, $data);
                setFlash('success', '記事を作成しました');
                redirect(adminUrl('success_story_edit.php?id=' . $newId));
            }
        } catch (Exception $e) {
            $error = 'DBエラー: ' . $e->getMessage();
        }
    }
    
    if (isset($error)) {
        setFlash('error', $error);
    } else {
        redirect(adminUrl('success_story_edit.php?id=' . $id));
    }
}

renderAdminHeader($id > 0 ? '記事編集' : '新規記事作成', 'success_stories');
$flashes = getFlash();
?>

<div class="flex items-center justify-between mb-8">
    <div class="flex items-center gap-4">
        <a href="<?= h(adminUrl('success_stories.php')) ?>" class="w-10 h-10 flex items-center justify-center bg-white border rounded-xl text-gray-400 hover:text-gray-900 shadow-sm transition">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
        </a>
        <h1 class="text-2xl font-bold text-gray-900"><?= $id > 0 ? '記事編集' : '成功実績を投稿' ?></h1>
    </div>
    
    <?php if ($id > 0): ?>
        <a href="<?= h(url('success-story-detail.php?id=' . $id)) ?>" target="_blank" class="px-4 py-2 bg-white border border-blue-100 text-blue-600 rounded-xl hover:bg-blue-50 transition shadow-sm font-bold flex items-center text-sm">
            <span>プレビュー表示</span>
            <svg class="w-4 h-4 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
            </svg>
        </a>
    <?php endif; ?>
</div>

<?php if (!empty($flashes)): ?>
  <?php foreach ($flashes as $type => $msg): if (!$msg) continue; ?>
    <div class="mb-6 rounded-xl px-4 py-3 flex items-center gap-3 <?= $type === 'success' ? 'bg-green-50 text-green-800 border border-green-100' : 'bg-red-50 text-red-800 border border-red-100' ?>">
        <span class="font-medium"><?= h($msg) ?></span>
    </div>
  <?php endforeach; ?>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data" class="grid grid-cols-1 lg:grid-cols-3 gap-8 pb-20">
    <input type="hidden" name="csrf_token" value="<?= h(generateCsrfToken()) ?>">
    
    <div class="lg:col-span-2 space-y-8">
        <!-- Basic Section -->
        <div class="bg-white border rounded-2xl p-6 md:p-8 shadow-sm">
            <h2 class="text-lg font-bold text-gray-900 mb-6 flex items-center gap-2">
                <span class="w-1 h-6 bg-blue-600 rounded-full"></span>
                基本情報
            </h2>
            
            <div class="space-y-6">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">タイトル <span class="text-red-500">*</span></label>
                    <input type="text" name="title" value="<?= h($story['title'] ?? '') ?>" class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-blue-500 font-bold text-lg" placeholder="例: 未経験から3ヶ月で月収20万達成！" required>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">メンバー名 <span class="text-red-500">*</span></label>
                        <input type="text" name="member_name" value="<?= h($story['member_name'] ?? '') ?>" class="w-full px-4 py-3 border rounded-xl" placeholder="例: 田中 太郎" required>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">役職・属性</label>
                        <input type="text" name="member_role" value="<?= h($story['member_role'] ?? '') ?>" class="w-full px-4 py-3 border rounded-xl" placeholder="例: 会社員 / 副業からスタート">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">キャッチコピー / 導入文</label>
                    <textarea name="intro_text" rows="4" class="w-full px-4 py-3 border rounded-xl bg-gray-50 focus:bg-white transition-colors" placeholder="記事の冒頭に表示される文章です..."><?= h($story['intro_text'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

        <!-- Content Section (Visible only after initial save) -->
        <div class="bg-white border rounded-2xl p-6 md:p-8 shadow-sm">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                    <span class="w-1 h-6 bg-indigo-600 rounded-full"></span>
                    インタビュー内容 (Q&A)
                </h2>
                <?php if ($id > 0): ?>
                    <a href="<?= h(adminUrl('success_story_section_edit.php?story_id=' . $id)) ?>" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition shadow-md font-bold text-sm">
                        + 項目を追加
                    </a>
                <?php endif; ?>
            </div>

            <?php if ($id === 0): ?>
                <div class="text-center py-12 bg-gray-50 rounded-2xl border border-dashed border-gray-200">
                    <div class="text-gray-400 mb-2">
                        <svg class="w-12 h-12 mx-auto mb-2 opacity-20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                    </div>
                    <p class="text-gray-500 font-medium tracking-tight">先に「基本情報」を保存すると、<br>詳細なインタビュー内容を追加できるようになります。</p>
                </div>
            <?php elseif (empty($sections)): ?>
                <div class="text-center py-12 bg-gray-50 rounded-2xl border border-dashed border-gray-200">
                    <p class="text-gray-400">インタビュー内容がまだありません</p>
                </div>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($sections as $sec): ?>
                        <div class="flex items-start bg-white border rounded-xl p-5 hover:border-indigo-300 hover:shadow-md transition group">
                            <div class="mr-4 flex flex-col items-center">
                                <span class="bg-gray-100 text-gray-400 text-[10px] px-2 py-0.5 rounded font-mono mb-2">#<?= $sec['display_order'] ?></span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h3 class="font-bold text-gray-900 text-base mb-1 truncate"><?= h($sec['heading']) ?></h3>
                                <p class="text-sm text-gray-500 line-clamp-2"><?= h($sec['body_text']) ?></p>
                            </div>
                            <a href="<?= h(adminUrl('success_story_section_edit.php?id=' . $sec['id'])) ?>" class="ml-4 p-2 text-gray-400 hover:text-indigo-600 transition">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                </svg>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Right Sidebar -->
    <div class="space-y-8">
        <!-- Status Card -->
        <div class="bg-white border rounded-2xl p-6 shadow-sm">
            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-4">公開設定</h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">記事の状態</label>
                    <select name="status" class="w-full px-4 py-3 border rounded-xl bg-gray-50 focus:ring-2 focus:ring-blue-500 font-bold">
                        <option value="draft" <?= (!isset($story['status']) || $story['status'] === 'draft') ? 'selected' : '' ?>>下書き</option>
                        <option value="published" <?= (isset($story['status']) && $story['status'] === 'published') ? 'selected' : '' ?>>公開</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">取材日</label>
                    <input type="date" name="interview_date" value="<?= h($story['interview_date'] ?? date('Y-m-d')) ?>" class="w-full px-4 py-3 border rounded-xl">
                </div>
            </div>
            
            <div class="mt-8">
                <button type="submit" class="w-full py-4 bg-blue-600 text-white font-bold rounded-2xl hover:bg-blue-700 shadow-xl hover:shadow-blue-200 transition transform hover:-translate-y-0.5">
                    <?= $id > 0 ? '記事を更新する' : '新しく投稿する' ?>
                </button>
            </div>
        </div>

        <!-- Meta Data -->
        <div class="bg-white border rounded-2xl p-6 shadow-sm">
            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-4">属性・表示</h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">成果バッジ (Golden)</label>
                    <input type="text" name="result_badge" value="<?= h($story['result_badge'] ?? '') ?>" placeholder="例: 月収 35万円 達成" class="w-full px-4 py-2 border rounded-xl font-bold bg-yellow-50 text-yellow-700 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">カテゴリー</label>
                    <input type="text" name="category_name" value="<?= h($story['category_name'] ?? '') ?>" placeholder="例: 動画編集" class="w-full px-4 py-3 border rounded-xl text-sm">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">タグ</label>
                    <input type="text" name="tag_type" value="<?= h($story['tag_type'] ?? '') ?>" placeholder="例: 主婦から転身" class="w-full px-4 py-3 border rounded-xl text-sm">
                </div>
            </div>
        </div>

        <!-- Images -->
        <div class="bg-white border rounded-2xl p-6 shadow-sm">
            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-4">メディア素材</h3>
            <div class="space-y-6">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">メイン画像</label>
                    <div class="space-y-3">
                        <input type="file" name="main_image_file" accept="image/*" class="w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 transition">
                        <div class="relative">
                            <input type="text" name="main_image" value="<?= h($story['main_image'] ?? '') ?>" class="w-full px-4 py-2 border rounded-xl text-xs font-mono pl-10" placeholder="https://...">
                            <span class="absolute left-3 top-2 text-gray-400 text-[10px] font-bold">URL</span>
                        </div>
                    </div>
                    <?php if (!empty($story['main_image'])): ?>
                        <div class="mt-3 aspect-video rounded-xl overflow-hidden bg-gray-100 border shadow-inner">
                            <img src="<?= h(uploaded_asset($story['main_image'])) ?>" class="w-full h-full object-cover">
                        </div>
                    <?php endif; ?>
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">メンバー画像</label>
                    <div class="space-y-3">
                        <input type="file" name="member_image_file" accept="image/*" class="w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 transition">
                        <div class="relative">
                            <input type="text" name="member_image" value="<?= h($story['member_image'] ?? '') ?>" class="w-full px-4 py-2 border rounded-xl text-xs font-mono pl-10" placeholder="https://...">
                            <span class="absolute left-3 top-2 text-gray-400 text-[10px] font-bold">URL</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<?php renderAdminFooter(); ?>
