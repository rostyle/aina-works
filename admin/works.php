<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/admin_layout.php';
requireAdmin();

$db = Database::getInstance();

// Actions
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', '不正なリクエストです');
        redirect(adminUrl('works.php'));
    }
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    try {
        if ($id > 0) {
            if ($action === 'toggle_publish') {
                $w = $db->selectOne("SELECT status FROM works WHERE id = ?", [$id]);
                if ($w) {
                    $new = ($w['status'] === 'published') ? 'archived' : 'published';
                    $db->update("UPDATE works SET status = ? WHERE id = ?", [$new, $id]);
                    setFlash('success', '公開状態を変更しました');
                }
            } elseif ($action === 'delete') {
                $db->execute("DELETE FROM works WHERE id = ?", [$id]);
                setFlash('success', '作品を削除しました');
            }
        }
    } catch (Exception $e) {
        setFlash('error', '処理に失敗しました: ' . $e->getMessage());
    }
    redirect(adminUrl('works.php'));
}

// Filters
$q = trim($_GET['q'] ?? '');
$status = $_GET['status'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$where = [];
$params = [];
if ($q !== '') {
    $where[] = "(w.title LIKE ? OR u.full_name LIKE ? OR w.id = ?)";
    $params[] = "%{$q}%";
    $params[] = "%{$q}%";
    $params[] = ctype_digit($q) ? (int)$q : 0;
}
if ($status !== '') {
    $where[] = "w.status = ?";
    $params[] = $status;
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$total = (int)($db->selectOne("SELECT COUNT(*) AS c FROM works w JOIN users u ON w.user_id = u.id {$whereSql}", $params)['c'] ?? 0);
$offset = ($page - 1) * $perPage;

$rows = $db->select(
    "SELECT w.id, w.title, w.status, IFNULL(w.is_featured,0) AS is_featured, IFNULL(w.like_count,0) AS like_count,
            u.full_name as user_name, u.id as user_id,
            c.name as category_name
       FROM works w
       JOIN users u ON w.user_id = u.id
       LEFT JOIN categories c ON w.category_id = c.id
       {$whereSql}
       ORDER BY w.id DESC
       LIMIT {$perPage} OFFSET {$offset}",
    $params
);

renderAdminHeader('作品管理', 'works');
$flashes = getFlash();
?>

<div class="flex items-center justify-between mb-4">
  <div class="flex items-center gap-4">
    <h1 class="text-2xl font-bold text-gray-900">作品管理</h1>
    <a href="<?= h(adminUrl('works_edit.php')) ?>" class="px-4 py-2 bg-green-600 text-white text-sm font-bold rounded-lg hover:bg-green-700 transition shadow-sm">
      + 作品を追加
    </a>
  </div>
  <form method="GET" class="flex gap-2">
    <input type="text" name="q" value="<?= h($q) ?>" placeholder="作品名/ユーザー/ID" class="px-3 py-2 border rounded-lg">
    <select name="status" class="px-3 py-2 border rounded-lg">
      <option value="">すべて</option>
      <option value="published" <?= $status==='published'?'selected':'' ?>>公開</option>
      <option value="archived" <?= $status==='archived'?'selected':'' ?>>非公開</option>
    </select>
    <button class="px-4 py-2 rounded-lg bg-blue-600 text-white">絞り込み</button>
  </form>
</div>

<?php if (!empty($flashes)): ?>
  <?php foreach ($flashes as $type => $msg): if (!$msg) continue; ?>
    <div class="mb-4 rounded-lg px-4 py-3 <?= $type === 'success' ? 'bg-green-50 text-green-800 border border-green-200' : 'bg-red-50 text-red-800 border border-red-200' ?>"><?= h($msg) ?></div>
  <?php endforeach; ?>
<?php endif; ?>

<div class="overflow-x-auto bg-white border rounded-xl">
  <table class="min-w-full text-sm">
    <thead class="bg-gray-50 text-gray-600">
      <tr>
        <th class="text-left px-4 py-3">ID</th>
        <th class="text-left px-4 py-3">作品</th>
        <th class="text-left px-4 py-3">作者</th>
        <th class="text-left px-4 py-3">カテゴリ</th>
        <th class="text-left px-4 py-3">状態</th>
        <th class="text-right px-4 py-3">操作</th>
      </tr>
    </thead>
    <tbody class="divide-y">
    <?php foreach ($rows as $r): ?>
      <tr class="hover:bg-gray-50">
        <td class="px-4 py-3 font-mono">#<?= h($r['id']) ?></td>
        <td class="px-4 py-3">
          <div class="font-medium text-gray-900"><?= h($r['title']) ?></div>
          <div class="text-xs text-gray-500">♥ <?= (int)($r['like_count'] ?? 0) ?></div>
        </td>
        <td class="px-4 py-3 text-gray-700">#<?= (int)$r['user_id'] ?> <?= h($r['user_name']) ?></td>
        <td class="px-4 py-3 text-gray-700"><?= h($r['category_name'] ?? '-') ?></td>
        <td class="px-4 py-3">
          <?php if ($r['status'] === 'published'): ?>
            <span class="px-2 py-1 text-xs rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">公開</span>
          <?php else: ?>
            <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-600 border border-gray-200">非公開</span>
          <?php endif; ?>
          <?php if ((int)$r['is_featured'] === 1): ?>
            <span class="ml-2 px-2 py-1 text-xs rounded-full bg-yellow-50 text-yellow-800 border border-yellow-200">おすすめ</span>
          <?php endif; ?>
        </td>
        <td class="px-4 py-3 text-right">
          <div class="flex justify-end gap-2">
            <a href="<?= h(adminUrl('works_edit.php?id=' . $r['id'])) ?>" class="px-3 py-1.5 rounded-md border text-xs bg-white hover:bg-gray-50 text-blue-600">
              編集
            </a>
            <form method="POST" class="inline" onsubmit="return confirm('本当に削除しますか？');">
              <input type="hidden" name="csrf_token" value="<?= h(generateCsrfToken()) ?>">
              <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
              <input type="hidden" name="action" value="delete">
              <button type="submit" class="px-3 py-1.5 rounded-md border text-xs bg-white hover:bg-red-50 text-red-600">
                削除
              </button>
            </form>
          </div>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (empty($rows)): ?>
      <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">該当する作品が見つかりません</td></tr>
    <?php endif; ?>
    </tbody>
  </table>
</div>

<?php
$totalPages = (int)ceil(max(1, $total) / $perPage);
if ($totalPages > 1):
  $base = './works.php?q=' . urlencode($q) . ($status!=='' ? '&status=' . urlencode($status) : '') . '&page=';
?>
  <div class="mt-4 flex justify-center gap-2">
    <?php for ($p = 1; $p <= $totalPages; $p++): $is = $p === $page; ?>
      <a class="px-3 py-1.5 rounded-md border text-sm <?= $is ? 'bg-blue-600 text-white border-blue-600' : 'bg-white hover:bg-gray-50' ?>" href="<?= h($base . $p) ?>"><?= $p ?></a>
    <?php endfor; ?>
  </div>
<?php endif; ?>

<?php renderAdminFooter();
