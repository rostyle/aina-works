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
            setFlash('success', '記事を削除しました');
        }
    } catch (Exception $e) {
        setFlash('error', '処理に失敗しました: ' . $e->getMessage());
    }
    redirect(adminUrl('success_stories.php'));
}

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

$total = (int)($db->selectOne("SELECT COUNT(*) AS c FROM success_stories")['c'] ?? 0);
$rows = $db->select("SELECT * FROM success_stories ORDER BY interview_date DESC LIMIT {$perPage} OFFSET {$offset}");

renderAdminHeader('インタビュー管理', 'success_stories');
$flashes = getFlash();
?>

<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-gray-900">会員インタビュー管理</h1>
    <a href="<?= h(adminUrl('success_story_edit.php')) ?>" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition shadow-sm font-bold flex items-center">
        <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        新規作成
    </a>
</div>

<?php if (!empty($flashes)): ?>
  <?php foreach ($flashes as $type => $msg): if (!$msg) continue; ?>
    <div class="mb-4 rounded-lg px-4 py-3 <?= $type === 'success' ? 'bg-green-50 text-green-800 border border-green-200' : 'bg-red-50 text-red-800 border border-red-200' ?>"><?= h($msg) ?></div>
  <?php endforeach; ?>
<?php endif; ?>

<div class="bg-white border rounded-xl overflow-hidden shadow-sm">
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-700 font-medium">
                <tr>
                    <th class="px-4 py-3 text-left w-16">ID</th>
                    <th class="px-4 py-3 text-left w-20">Thumb</th>
                    <th class="px-4 py-3 text-left">タイトル / メンバー</th>
                    <th class="px-4 py-3 text-left w-32">カテゴリ</th>
                    <th class="px-4 py-3 text-left w-32">取材日</th>
                    <th class="px-4 py-3 text-right w-40">操作</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach ($rows as $r): ?>
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-4 py-3 text-gray-500 font-mono">#<?= h($r['id']) ?></td>
                    <td class="px-4 py-3">
                        <?php if (!empty($r['main_image'])): ?>
                            <img src="<?= h($r['main_image']) ?>" class="w-12 h-12 object-cover rounded shadow-sm border border-gray-100">
                        <?php else: ?>
                            <div class="w-12 h-12 bg-gray-200 rounded flex items-center justify-center text-xs text-gray-400">No Img</div>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-3">
                        <div class="font-bold text-gray-900 mb-0.5"><?= h($r['title']) ?></div>
                        <div class="text-xs text-gray-500">
                            <?= h($r['member_name']) ?> (<?= h($r['member_role']) ?>)
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        <span class="inline-block px-2 py-1 bg-gray-100 text-gray-600 rounded text-xs font-medium">
                            <?= h($r['category_name']) ?>
                        </span>
                    </td>
                    <td class="px-4 py-3 text-gray-600 whitespace-nowrap">
                        <?= h($r['interview_date']) ?>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <a href="<?= h(adminUrl('success_story_edit.php?id=' . $r['id'])) ?>" class="p-1 text-blue-600 hover:bg-blue-50 rounded">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </a>
                            <form method="POST" class="inline" onsubmit="return confirm('本当に削除しますか？');">
                                <input type="hidden" name="csrf_token" value="<?= h(generateCsrfToken()) ?>">
                                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                <input type="hidden" name="action" value="delete">
                                <button class="p-1 text-red-600 hover:bg-red-50 rounded">
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
                    <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                        記事がまだありません。<br>
                        <a href="<?= h(adminUrl('success_story_edit.php')) ?>" class="text-blue-600 hover:underline mt-2 inline-block">新しく作成する</a>
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
<div class="mt-6 flex justify-center gap-2">
    <?php for ($p = 1; $p <= $totalPages; $p++): $is = $p === $page; ?>
    <a class="px-3 py-1.5 rounded-md border text-sm <?= $is ? 'bg-blue-600 text-white border-blue-600' : 'bg-white hover:bg-gray-50 text-gray-600' ?>" href="?page=<?= $p ?>"><?= $p ?></a>
    <?php endfor; ?>
</div>
<?php endif; ?>

<?php renderAdminFooter(); ?>
