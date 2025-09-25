<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/admin_layout.php';
requireAdmin();

$db = Database::getInstance();

// Update status
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', '不正なリクエストです');
        redirect(adminUrl('applications.php'));
    }
    $appId = (int)($_POST['application_id'] ?? 0);
    $newStatus = trim($_POST['status'] ?? '');
    $allowed = ['pending','accepted','rejected','cancelled'];
    if ($appId > 0 && in_array($newStatus, $allowed, true)) {
        try {
            $db->update("UPDATE job_applications SET status = ? WHERE id = ?", [$newStatus, $appId]);
            setFlash('success', '応募ステータスを更新しました');
        } catch (Exception $e) {
            setFlash('error', '更新に失敗しました: ' . $e->getMessage());
        }
    }
    redirect(adminUrl('applications.php'));
}

// Filters
$q = trim($_GET['q'] ?? '');
$status = $_GET['status'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$where = [];
$params = [];
if ($q !== '') {
    $where[] = "(j.title LIKE ? OR cu.full_name LIKE ? OR au.full_name LIKE ? OR ja.id = ?)";
    $params[] = "%{$q}%";
    $params[] = "%{$q}%";
    $params[] = "%{$q}%";
    $params[] = ctype_digit($q) ? (int)$q : 0;
}
if ($status !== '') {
    $where[] = "ja.status = ?";
    $params[] = $status;
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$total = (int)($db->selectOne(
    "SELECT COUNT(*) AS c
       FROM job_applications ja
       JOIN jobs j ON ja.job_id = j.id
       JOIN users au ON ja.creator_id = au.id
       JOIN users cu ON j.client_id = cu.id
       {$whereSql}", $params)['c'] ?? 0);
$offset = ($page - 1) * $perPage;

$rows = $db->select(
    "SELECT ja.id, ja.status, ja.proposed_price, ja.proposed_duration, ja.created_at,
            j.id AS job_id, j.title AS job_title,
            au.id AS creator_id, au.full_name AS creator_name,
            cu.id AS client_id, cu.full_name AS client_name
       FROM job_applications ja
       JOIN jobs j ON ja.job_id = j.id
       JOIN users au ON ja.creator_id = au.id
       JOIN users cu ON j.client_id = cu.id
       {$whereSql}
       ORDER BY ja.id DESC
       LIMIT {$perPage} OFFSET {$offset}",
    $params
);

$statusOptions = [
    'pending' => '保留',
    'accepted' => '受諾',
    'rejected' => '拒否',
    'cancelled' => 'キャンセル',
];

renderAdminHeader('応募管理', 'applications');
$flashes = getFlash();
?>

<div class="flex items-center justify-between mb-4">
  <h1 class="text-2xl font-bold text-gray-900">応募管理</h1>
  <form method="GET" class="flex gap-2">
    <input type="text" name="q" value="<?= h($q) ?>" placeholder="案件/応募者/ID" class="px-3 py-2 border rounded-lg">
    <select name="status" class="px-3 py-2 border rounded-lg">
      <option value="">すべて</option>
      <?php foreach ($statusOptions as $k => $label): ?>
        <option value="<?= h($k) ?>" <?= $status===$k?'selected':'' ?>><?= h($label) ?></option>
      <?php endforeach; ?>
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
        <th class="text-left px-4 py-3">案件</th>
        <th class="text-left px-4 py-3">応募者</th>
        <th class="text-left px-4 py-3">クライアント</th>
        <th class="text-left px-4 py-3">提案</th>
        <th class="text-left px-4 py-3">状態</th>
        <th class="text-right px-4 py-3">操作</th>
      </tr>
    </thead>
    <tbody class="divide-y">
      <?php foreach ($rows as $r): ?>
        <tr class="hover:bg-gray-50">
          <td class="px-4 py-3 font-mono">#<?= (int)$r['id'] ?></td>
          <td class="px-4 py-3 text-gray-900">#<?= (int)$r['job_id'] ?> <?= h($r['job_title']) ?></td>
          <td class="px-4 py-3 text-gray-700">#<?= (int)$r['creator_id'] ?> <?= h($r['creator_name']) ?></td>
          <td class="px-4 py-3 text-gray-700">#<?= (int)$r['client_id'] ?> <?= h($r['client_name']) ?></td>
          <td class="px-4 py-3 text-gray-700"><?= '¥' . number_format((int)($r['proposed_price'] ?? 0)) ?> / <?= h($r['proposed_duration'] ?? '-') ?></td>
          <td class="px-4 py-3 text-gray-700"><?= h($statusOptions[$r['status']] ?? $r['status']) ?></td>
          <td class="px-4 py-3 text-right">
            <form method="POST" class="inline-flex items-center gap-2">
              <input type="hidden" name="csrf_token" value="<?= h(generateCsrfToken()) ?>">
              <input type="hidden" name="application_id" value="<?= (int)$r['id'] ?>">
              <select name="status" class="px-2 py-1 text-xs border rounded-md">
                <?php foreach ($statusOptions as $k => $label): ?>
                  <option value="<?= h($k) ?>" <?= $r['status']===$k?'selected':'' ?>><?= h($label) ?></option>
                <?php endforeach; ?>
              </select>
              <button class="px-3 py-1.5 rounded-md border text-xs bg-white hover:bg-gray-50">更新</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($rows)): ?>
        <tr><td colspan="7" class="px-4 py-8 text-center text-gray-500">該当する応募が見つかりません</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php
$totalPages = (int)ceil(max(1, $total) / $perPage);
if ($totalPages > 1):
  $base = './applications.php?q=' . urlencode($q) . ($status!=='' ? '&status=' . urlencode($status) : '') . '&page=';
?>
  <div class="mt-4 flex justify-center gap-2">
    <?php for ($p = 1; $p <= $totalPages; $p++): $is = $p === $page; ?>
      <a class="px-3 py-1.5 rounded-md border text-sm <?= $is ? 'bg-blue-600 text-white border-blue-600' : 'bg-white hover:bg-gray-50' ?>" href="<?= h($base . $p) ?>"><?= $p ?></a>
    <?php endfor; ?>
  </div>
<?php endif; ?>

<?php renderAdminFooter();
