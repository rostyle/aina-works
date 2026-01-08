<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/admin_layout.php';
requireAdmin();

$db = Database::getInstance();

$id = (int)($_GET['id'] ?? 0);
$storyId = (int)($_GET['story_id'] ?? 0);

$section = null;
$story = null;

try {
    if ($id > 0) {
        // Edit existing section
        $section = $db->selectOne("SELECT * FROM success_story_sections WHERE id = ?", [$id]);
        if (!$section) {
            throw new Exception('指定されたセクションが見つかりません');
        }
        $storyId = $section['success_story_id'];
    }

    if ($storyId > 0) {
        $story = $db->selectOne("SELECT * FROM success_stories WHERE id = ?", [$storyId]);
    }
} catch (Exception $e) {
    setFlash('error', $e->getMessage());
    redirect(adminUrl('success_stories.php'));
}

if (!$story) {
    setFlash('error', '親記事が見つかりません');
    redirect(adminUrl('success_stories.php'));
}

// Actions
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', '不正なリクエストです');
        redirect($_SERVER['REQUEST_URI']);
    }

    // Delete Action
    if (($_POST['action'] ?? '') === 'delete' && $id > 0) {
        try {
            $db->delete("DELETE FROM success_story_sections WHERE id = ?", [$id]);
            setFlash('success', 'セクションを削除しました');
            redirect(adminUrl('success_story_edit.php?id=' . $storyId));
        } catch (Exception $e) {
            setFlash('error', '削除エラー: ' . $e->getMessage());
        }
    }

    // Save Action
    $heading = trim($_POST['heading'] ?? '');
    $bodyText = trim($_POST['body_text'] ?? '');
    $order = (int)($_POST['display_order'] ?? 0);

    try {
        if ($id > 0) {
            // Update
            $db->update(
                "UPDATE success_story_sections SET heading = ?, body_text = ?, display_order = ? WHERE id = ?",
                [$heading, $bodyText, $order, $id]
            );
            setFlash('success', 'セクションを更新しました');
        } else {
            // Create
            $db->insert(
                "INSERT INTO success_story_sections (success_story_id, heading, body_text, display_order) VALUES (?, ?, ?, ?)",
                [$storyId, $heading, $bodyText, $order]
            );
            setFlash('success', 'セクションを作成しました');
        }
        redirect(adminUrl('success_story_edit.php?id=' . $storyId));

    } catch (Exception $e) {
        setFlash('error', '保存エラー: ' . $e->getMessage());
    }
}

renderAdminHeader('セクション編集', 'success_stories');
$flashes = getFlash();
?>

<div class="max-w-3xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <a href="<?= h(adminUrl('success_story_edit.php?id=' . $storyId)) ?>" class="flex items-center text-gray-500 hover:text-gray-900 font-bold transition">
            <svg class="w-5 h-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            記事編集に戻る
        </a>
        <h1 class="text-xl font-bold text-gray-900"><?= $id > 0 ? 'セクション編集' : 'セクション作成' ?></h1>
    </div>

    <!-- Info Card -->
    <div class="bg-blue-50 border border-blue-100 rounded-xl p-4 mb-6 flex items-center justify-between">
        <div>
            <div class="text-xs text-blue-600 font-bold uppercase tracking-wider mb-1">Parent Story</div>
            <div class="font-bold text-gray-800"><?= h($story['title']) ?></div>
        </div>
        <?php if ($id > 0): ?>
            <form method="POST" onsubmit="return confirm('本当に削除しますか？');">
                <input type="hidden" name="csrf_token" value="<?= h(generateCsrfToken()) ?>">
                <input type="hidden" name="action" value="delete">
                <button class="text-red-600 hover:bg-red-100 px-3 py-1.5 rounded transition text-sm font-bold">
                    削除する
                </button>
            </form>
        <?php endif; ?>
    </div>

    <form method="POST" class="bg-white border rounded-xl p-6 shadow-xl relative">
        <input type="hidden" name="csrf_token" value="<?= h(generateCsrfToken()) ?>">

        <div class="space-y-6">
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">
                    見出し (Q) <span class="bg-gray-100 text-gray-500 text-xs px-2 py-0.5 rounded ml-2">任意</span>
                </label>
                <input type="text" name="heading" value="<?= h($section['heading'] ?? '') ?>" placeholder="例: きっかけは？" class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-blue-500 text-lg font-bold">
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">
                    本文 (A)
                </label>
                <textarea name="body_text" rows="8" class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-blue-500 leading-relaxed" placeholder="回答や本文を入力..."><?= h($section['body_text'] ?? '') ?></textarea>
            </div>

            <div class="w-32">
                <label class="block text-sm font-bold text-gray-700 mb-1">表示順</label>
                <input type="number" name="display_order" value="<?= (int)($section['display_order'] ?? 0) ?>" class="w-full px-3 py-2 border rounded-lg">
            </div>
        </div>

        <div class="mt-8 pt-6 border-t flex justify-end">
            <button type="submit" class="px-8 py-3 bg-blue-600 text-white font-bold rounded-xl hover:bg-blue-700 shadow-lg hover:shadow-xl transition transform hover:-translate-y-0.5">
                保存する
            </button>
        </div>
    </form>
</div>

<?php renderAdminFooter(); ?>
