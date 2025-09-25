<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/admin_layout.php';
requireAdmin();

$db = Database::getInstance();

// Delete action
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', '不正なリクエストです');
        redirect(adminUrl('reviews.php'));
    }
    $reviewId = (int)($_POST['review_id'] ?? 0);
    if ($reviewId > 0) {
        try {
            $db->delete("DELETE FROM reviews WHERE id = ?", [$reviewId]);
            setFlash('success', 'レビューを削除しました');
        } catch (Exception $e) {
            setFlash('error', '削除に失敗しました: ' . $e->getMessage());
        }
    }
    redirect(adminUrl('reviews.php'));
}

// Filters
$q = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$where = [];
$params = [];
if ($q !== '') {
    $where[] = "(au.full_name LIKE ? OR tu.full_name LIKE ? OR j.title LIKE ? OR w.title LIKE ? OR r.id = ?)";
    $params[] = "%{$q}%"; // author user
    $params[] = "%{$q}%"; // target user
    $params[] = "%{$q}%"; // job title (if any)
    $params[] = "%{$q}%"; // work title
    $params[] = ctype_digit($q) ? (int)$q : 0;
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// Note: jobs may not directly link; we will just join work title and users
$total = (int)($db->selectOne(
    "SELECT COUNT(*) AS c
       FROM reviews r
       LEFT JOIN users au ON r.reviewer_id = au.id
       LEFT JOIN users tu ON r.reviewee_id = tu.id
       LEFT JOIN works w ON r.work_id = w.id
       LEFT JOIN jobs j ON 1=0
       {$whereSql}", $params)['c'] ?? 0);
$offset = ($page - 1) * $perPage;

$rows = $db->select(
    "SELECT r.id, r.rating, r.comment, r.created_at,
            au.id AS reviewer_id, au.full_name AS reviewer_name,
            tu.id AS reviewee_id, tu.full_name AS reviewee_name,
            w.id AS work_id, w.title AS work_title
       FROM reviews r
       LEFT JOIN users au ON r.reviewer_id = au.id
       LEFT JOIN users tu ON r.reviewee_id = tu.id
       LEFT JOIN works w ON r.work_id = w.id
       {$whereSql}
       ORDER BY r.id DESC
       LIMIT {$perPage} OFFSET {$offset}",
    $params
);

renderAdminHeader('レビュー管理', 'reviews');
$flashes = getFlash();
?>

<div class="flex items-center justify-between mb-4">
  <h1 class="text-2xl font-bold text-gray-900">レビュー管理</h1>
  <form method="GET" class="flex gap-2">
    <input type="text" name="q" value="<?= h($q) ?>" placeholder="レビュワー/対象/作品/ID" class="px-3 py-2 border rounded-lg">
    <button class="px-4 py-2 rounded-lg bg-blue-600 text-white">検索</button>
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
        <th class="text-left px-4 py-3">レビュワー</th>
        <th class="text-left px-4 py-3">対象</th>
        <th class="text-left px-4 py-3">評価</th>
        <th class="text-left px-4 py-3">コメント</th>
        <th class="text-right px-4 py-3">操作</th>
      </tr>
    </thead>
    <tbody class="divide-y">
      <?php foreach ($rows as $r): ?>
        <tr class="hover:bg-gray-50">
          <td class="px-4 py-3 font-mono">#<?= (int)$r['id'] ?></td>
          <td class="px-4 py-3 text-gray-700"><?= $r['work_id'] ? ('#' . (int)$r['work_id'] . ' ' . h($r['work_title'])) : '-' ?></td>
          <td class="px-4 py-3 text-gray-700">#<?= (int)($r['reviewer_id'] ?? 0) ?> <?= h($r['reviewer_name'] ?? '-') ?></td>
          <td class="px-4 py-3 text-gray-700">#<?= (int)($r['reviewee_id'] ?? 0) ?> <?= h($r['reviewee_name'] ?? '-') ?></td>
          <td class="px-4 py-3 text-gray-700"><?= (int)$r['rating'] ?> / 5</td>
          <td class="px-4 py-3 text-gray-700 max-w-lg truncate" title="<?= h($r['comment']) ?>"><?= h(mb_strimwidth($r['comment'] ?? '', 0, 64, '…', 'UTF-8')) ?></td>
          <td class="px-4 py-3 text-right">
            <form method="POST" onsubmit="return confirm('このレビューを削除しますか？');" class="inline">
              <input type="hidden" name="csrf_token" value="<?= h(generateCsrfToken()) ?>">
              <input type="hidden" name="review_id" value="<?= (int)$r['id'] ?>">
              <button class="px-3 py-1.5 rounded-md border text-xs bg-white hover:bg-gray-50">削除</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($rows)): ?>
        <tr><td colspan="7" class="px-4 py-8 text-center text-gray-500">レビューが見つかりません</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php
$totalPages = (int)ceil(max(1, $total) / $perPage);
if ($totalPages > 1):
  $base = './reviews.php?q=' . urlencode($q) . '&page=';
?>
  <div class="mt-4 flex justify-center gap-2">
    <?php for ($p = 1; $p <= $totalPages; $p++): $is = $p === $page; ?>
      <a class="px-3 py-1.5 rounded-md border text-sm <?= $is ? 'bg-blue-600 text-white border-blue-600' : 'bg-white hover:bg-gray-50' ?>" href="<?= h($base . $p) ?>"><?= $p ?></a>
    <?php endfor; ?>
  </div>
<?php endif; ?>

<?php renderAdminFooter();
