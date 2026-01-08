<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/admin_layout.php';
requireAdmin();

$db = Database::getInstance();

// Actions
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', '不正なリクエストです');
        redirect(adminUrl('success_stories.php'));
    }
    
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    
    try {
        if ($id > 0 && $action === 'delete') {
            $db->delete("DELETE FROM success_stories WHERE id = ?", [$id]);
            // Also delete sections
            $db->delete("DELETE FROM success_story_sections WHERE success_story_id = ?", [$id]);
            setFlash('success', '記事を削除しました');
        }
    } catch (Exception $e) {
        setFlash('error', '処理に失敗しました: ' . $e->getMessage());
    }
    redirect(adminUrl('success_stories.php'));
}

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

$total = (int)($db->selectOne("SELECT COUNT(*) AS c FROM success_stories")['c'] ?? 0);
$rows = $db->select("SELECT * FROM success_stories ORDER BY interview_date DESC LIMIT {$perPage} OFFSET {$offset}");

renderAdminHeader('インタビュー管理', 'success_stories');
$flashes = getFlash();
?>

<div class="flex flex-col md:flex-row items-start md:items-center justify-between mb-8 gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">会員インタビュー管理</h1>
        <p class="text-sm text-gray-500 mt-1">success-stories.php に表示される記事の管理</p>
    </div>
    <a href="<?= h(adminUrl('success_story_edit.php')) ?>" class="px-6 py-3 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition shadow-lg hover:shadow-blue-200 font-bold flex items-center transform hover:-translate-y-0.5">
        <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        新しい記事を投稿する
    </a>
</div>

<?php if (!empty($flashes)): ?>
  <?php foreach ($flashes as $type => $msg): if (!$msg) continue; ?>
    <div class="mb-6 rounded-xl px-4 py-3 flex items-center gap-3 <?= $type === 'success' ? 'bg-green-50 text-green-800 border border-green-100' : 'bg-red-50 text-red-800 border border-red-100' ?>">
        <?php if ($type === 'success'): ?>
            <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
        <?php else: ?>
            <svg class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
        <?php endif; ?>
        <span class="font-medium"><?= h($msg) ?></span>
    </div>
  <?php endforeach; ?>
<?php endif; ?>

<div class="bg-white border rounded-2xl overflow-hidden shadow-sm">
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50/50 text-gray-500 font-bold uppercase tracking-wider">
                <tr>
                    <th class="px-6 py-4 text-left w-20">ID</th>
                    <th class="px-6 py-4 text-left">記事情報</th>
                    <th class="px-6 py-4 text-left">メンバー</th>
                    <th class="px-6 py-4 text-left">取材日</th>
                    <th class="px-6 py-4 text-right">操作</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach ($rows as $r): ?>
                <tr class="hover:bg-gray-50 transition-colors group">
                    <td class="px-6 py-4 text-gray-400 font-mono text-xs">#<?= h($r['id']) ?></td>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-4">
                            <div class="w-16 h-10 rounded-lg overflow-hidden bg-gray-100 flex-shrink-0 shadow-sm">
                                <?php if (!empty($r['main_image'])): ?>
                                    <img src="<?= h(uploaded_asset($r['main_image'])) ?>" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <div class="w-full h-full flex items-center justify-center text-[10px] text-gray-400">NO IMG</div>
                                <?php endif; ?>
                            </div>
                            <div>
                                <div class="font-bold text-gray-900 group-hover:text-blue-600 transition-colors line-clamp-1"><?= h($r['title']) ?></div>
                                <div class="flex gap-2 mt-1">
                                    <span class="inline-block px-1.5 py-0.5 bg-blue-50 text-blue-600 rounded text-[10px] font-bold">
                                        <?= h($r['category_name']) ?>
                                    </span>
                                    <span class="inline-block px-1.5 py-0.5 bg-yellow-50 text-yellow-600 rounded text-[10px] font-bold">
                                        <?= h($r['result_badge']) ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-2">
                            <div class="w-6 h-6 rounded-full bg-gray-200 flex items-center justify-center text-[10px] font-bold overflow-hidden shadow-sm ring-1 ring-white">
                                <?php if (!empty($r['member_image'])): ?>
                                    <img src="<?= h(uploaded_asset($r['member_image'])) ?>" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <?= mb_substr($r['member_name'], 0, 1) ?>
                                <?php endif; ?>
                            </div>
                            <div>
                                <div class="text-gray-900 font-medium"><?= h($r['member_name']) ?></div>
                                <div class="text-[10px] text-gray-500"><?= h($r['member_role']) ?></div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-gray-600 whitespace-nowrap">
                        <?= h(date('Y/m/d', strtotime($r['interview_date']))) ?>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <a href="<?= h(adminUrl('success_story_edit.php?id=' . $r['id'])) ?>" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition" title="編集">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </a>
                            <form method="POST" class="inline" onsubmit="return confirm('この記事を削除してもよろしいですか？（セクションもすべて削除されます）');">
                                <input type="hidden" name="csrf_token" value="<?= h(generateCsrfToken()) ?>">
                                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                <input type="hidden" name="action" value="delete">
                                <button class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition" title="削除">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($rows)): ?>
                <tr>
                    <td colspan="5" class="px-6 py-16 text-center">
                        <div class="inline-flex items-center justify-center w-16 h-16 bg-gray-50 rounded-full mb-4">
                            <svg class="w-8 h-8 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
                            </svg>
                        </div>
                        <p class="text-gray-400 font-medium">インタビュー記事がまだありません</p>
                        <a href="<?= h(adminUrl('success_story_edit.php')) ?>" class="text-blue-600 hover:underline mt-2 inline-block font-bold">新しく作成する</a>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$totalPages = (int)ceil(max(1, $total) / $perPage);
if ($totalPages > 1):
?>
<div class="mt-8 flex justify-center">
    <nav class="flex items-center gap-1 p-1 bg-white border rounded-xl shadow-sm">
        <?php for ($p = 1; $p <= $totalPages; $p++): $is = $p === $page; ?>
        <a class="w-10 h-10 flex items-center justify-center rounded-lg text-sm font-bold transition <?= $is ? 'bg-gray-900 text-white shadow-md' : 'text-gray-600 hover:bg-gray-50' ?>" href="?page=<?= $p ?>"><?= $p ?></a>
        <?php endfor; ?>
    </nav>
</div>
<?php endif; ?>

<?php renderAdminFooter(); ?>
