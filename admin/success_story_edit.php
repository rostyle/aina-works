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
        redirect(adminUrl('success_story_edit.php?id=' . $id));
    }
    
    $title = trim($_POST['title'] ?? '');
    $memberName = trim($_POST['member_name'] ?? '');
    $interviewDate = trim($_POST['interview_date'] ?? date('Y-m-d'));
    
    // Basic validation
    if ($title === '' || $memberName === '') {
        $error = 'タイトルとメンバー名は必須です';
    } else {
        try {
            $data = [
                $title,
                $memberName,
                $_POST['member_role'] ?? '',
                $_POST['category_name'] ?? '',
                $_POST['tag_type'] ?? '',
                $_POST['result_badge'] ?? '',
                $_POST['intro_text'] ?? '',
                $_POST['main_image'] ?? '',
                $_POST['member_image'] ?? '',
                $interviewDate
            ];
            
            if ($id > 0) {
                // Update
                $sql = "UPDATE success_stories SET 
                        title=?, member_name=?, member_role=?, category_name=?, tag_type=?, 
                        result_badge=?, intro_text=?, main_image=?, member_image=?, interview_date=? 
                        WHERE id = ?";
                $data[] = $id;
                $db->update($sql, $data);
                setFlash('success', '記事を更新しました');
            } else {
                // Create
                $sql = "INSERT INTO success_stories 
                        (title, member_name, member_role, category_name, tag_type, result_badge, intro_text, main_image, member_image, interview_date) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
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

<div class="flex items-center justify-between mb-6">
    <div class="flex items-center gap-4">
        <a href="<?= h(adminUrl('success_stories.php')) ?>" class="text-gray-500 hover:text-gray-700">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
        </a>
        <h1 class="text-2xl font-bold text-gray-900"><?= $id > 0 ? '記事編集: ' . h($story['title']) : '新規記事作成' ?></h1>
    </div>
    
    <?php if ($id > 0): ?>
        <a href="<?= h(url('success-story-detail.php?id=' . $id)) ?>" target="_blank" class="text-blue-600 hover:underline flex items-center text-sm">
            プレビュー
            <svg class="w-4 h-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
            </svg>
        </a>
    <?php endif; ?>
</div>

<?php if (!empty($flashes)): ?>
  <?php foreach ($flashes as $type => $msg): if (!$msg) continue; ?>
    <div class="mb-4 rounded-lg px-4 py-3 <?= $type === 'success' ? 'bg-green-50 text-green-800 border border-green-200' : 'bg-red-50 text-red-800 border border-red-200' ?>"><?= h($msg) ?></div>
  <?php endforeach; ?>
<?php endif; ?>

<!-- Main Form -->
<form method="POST" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <input type="hidden" name="csrf_token" value="<?= h(generateCsrfToken()) ?>">
    
    <!-- Left Column (Main Info) -->
    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white border rounded-xl p-6 shadow-sm">
            <h2 class="text-lg font-bold text-gray-900 mb-4 pb-2 border-b">基本情報</h2>
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">タイトル <span class="text-red-500">*</span></label>
                    <input type="text" name="title" value="<?= h($story['title'] ?? '') ?>" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 bg-gray-50" required>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">メンバー名 <span class="text-red-500">*</span></label>
                        <input type="text" name="member_name" value="<?= h($story['member_name'] ?? '') ?>" class="w-full px-3 py-2 border rounded-lg" required>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">役職・肩書き</label>
                        <input type="text" name="member_role" value="<?= h($story['member_role'] ?? '') ?>" class="w-full px-3 py-2 border rounded-lg">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">導入文 (Intro)</label>
                    <textarea name="intro_text" rows="4" class="w-full px-3 py-2 border rounded-lg bg-gray-50"><?= h($story['intro_text'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

        <?php if ($id > 0): ?>
        <!-- Sections Management -->
        <div class="bg-white border rounded-xl p-6 shadow-sm">
            <div class="flex items-center justify-between mb-4 pb-2 border-b">
                <h2 class="text-lg font-bold text-gray-900">記事セクション (Q&A)</h2>
                <a href="<?= h(adminUrl('success_story_section_edit.php?story_id=' . $id)) ?>" class="text-sm px-3 py-1 bg-green-600 text-white rounded hover:bg-green-700 transition">
                    + セクション追加
                </a>
            </div>

            <?php if (empty($sections)): ?>
                <div class="text-center py-8 text-gray-500 bg-gray-50 rounded-lg border border-dashed">
                    セクションがまだありません
                </div>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($sections as $sec): ?>
                        <div class="flex items-start bg-gray-50 p-4 rounded-lg border hover:border-blue-300 transition group">
                            <div class="mr-4 mt-1 bg-white border rounded px-2 py-0.5 text-xs font-mono text-gray-500">
                                Order: <?= $sec['display_order'] ?>
                            </div>
                            <div class="flex-1">
                                <h3 class="font-bold text-gray-800 text-sm mb-1"><?= h($sec['heading']) ?></h3>
                                <p class="text-xs text-gray-600 line-clamp-2"><?= h($sec['body_text']) ?></p>
                            </div>
                            <div class="ml-4 flex gap-2 opacity-100 lg:opacity-0 lg:group-hover:opacity-100 transition-opacity">
                                <a href="<?= h(adminUrl('success_story_section_edit.php?id=' . $sec['id'])) ?>" class="p-1.5 bg-white border rounded hover:bg-blue-50 text-blue-600">
                                    編集
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Right Column (Meta & Images) -->
    <div class="space-y-6">
        <div class="bg-white border rounded-xl p-6 shadow-sm">
            <h2 class="text-sm font-bold text-gray-900 mb-4 uppercase tracking-wider text-gray-500">メタ情報</h2>
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">取材日</label>
                    <input type="date" name="interview_date" value="<?= h($story['interview_date'] ?? date('Y-m-d')) ?>" class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">カテゴリ名</label>
                    <input type="text" name="category_name" value="<?= h($story['category_name'] ?? '') ?>" placeholder="例: 動画編集" class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">タグタイプ</label>
                    <input type="text" name="tag_type" value="<?= h($story['tag_type'] ?? '') ?>" placeholder="例: 副業スタート" class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">実績バッジ</label>
                    <input type="text" name="result_badge" value="<?= h($story['result_badge'] ?? '') ?>" placeholder="例: 月収 35万円 達成" class="w-full px-3 py-2 border rounded-lg text-yellow-600 font-bold bg-yellow-50">
                </div>
            </div>
        </div>

        <div class="bg-white border rounded-xl p-6 shadow-sm">
            <h2 class="text-sm font-bold text-gray-900 mb-4 uppercase tracking-wider text-gray-500">画像設定</h2>
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">メイン画像URL</label>
                    <input type="text" name="main_image" value="<?= h($story['main_image'] ?? '') ?>" class="w-full px-3 py-2 border rounded-lg text-xs font-mono">
                    <?php if (!empty($story['main_image'])): ?>
                        <div class="mt-2 aspect-video rounded overflow-hidden bg-gray-100">
                            <img src="<?= h($story['main_image']) ?>" class="w-full h-full object-cover">
                        </div>
                    <?php endif; ?>
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">メンバーアイコンURL</label>
                    <input type="text" name="member_image" value="<?= h($story['member_image'] ?? '') ?>" class="w-full px-3 py-2 border rounded-lg text-xs font-mono">
                </div>
            </div>
        </div>

        <div class="sticky top-6">
            <button type="submit" class="w-full py-3 bg-blue-600 text-white font-bold rounded-xl hover:shadow-lg hover:bg-blue-700 transition transform hover:-translate-y-0.5">
                <?= $id > 0 ? '保存する' : '作成する' ?>
            </button>
        </div>
    </div>
</form>

<?php renderAdminFooter(); ?>
