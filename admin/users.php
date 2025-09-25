<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/admin_layout.php';
requireAdmin();

$db = Database::getInstance();

// Handle actions
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', '不正なリクエストです');
        redirect(adminUrl('users.php'));
    }
    $action = $_POST['action'] ?? '';
    $userId = (int)($_POST['user_id'] ?? 0);
    if ($userId > 0) {
        try {
            switch ($action) {
                case 'toggle_active':
                    $target = $db->selectOne("SELECT is_active FROM users WHERE id = ?", [$userId]);
                    if ($target) {
                        $new = ($target['is_active'] ?? 0) ? 0 : 1;
                        $db->update("UPDATE users SET is_active = ? WHERE id = ?", [$new, $userId]);
                        setFlash('success', 'ユーザーの有効状態を更新しました');
                    }
                    break;
                case 'grant_admin':
                    $exists = $db->selectOne("SELECT id, is_enabled FROM user_roles WHERE user_id = ? AND role = 'admin'", [$userId]);
                    if ($exists) {
                        if ((int)$exists['is_enabled'] !== 1) {
                            $db->update("UPDATE user_roles SET is_enabled = 1 WHERE id = ?", [$exists['id']]);
                        }
                    } else {
                        $db->insert("INSERT INTO user_roles (user_id, role, is_enabled) VALUES (?, 'admin', 1)", [$userId]);
                    }
                    setFlash('success', '管理者ロールを付与しました');
                    break;
                case 'revoke_admin':
                    $db->update("UPDATE user_roles SET is_enabled = 0 WHERE user_id = ? AND role = 'admin'", [$userId]);
                    setFlash('success', '管理者ロールを解除しました');
                    break;
            }
        } catch (Exception $e) {
            setFlash('error', '処理に失敗しました: ' . $e->getMessage());
        }
    }
    redirect(adminUrl('users.php'));
}

// Filters
$q = trim($_GET['q'] ?? '');
$onlyActive = isset($_GET['active']) && $_GET['active'] === '1';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$where = [];
$params = [];
if ($q !== '') {
    $where[] = "(email LIKE ? OR full_name LIKE ? OR id = ?)";
    $params[] = "%{$q}%";
    $params[] = "%{$q}%";
    $params[] = ctype_digit($q) ? (int)$q : 0;
}
if ($onlyActive) {
    $where[] = "is_active = 1";
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$total = (int)($db->selectOne("SELECT COUNT(*) AS c FROM users {$whereSql}", $params)['c'] ?? 0);
$offset = ($page - 1) * $perPage;

$rows = $db->select(
    "SELECT u.id, u.full_name, u.email, u.is_active, u.is_creator, u.is_client, u.profile_image,
            (SELECT is_enabled FROM user_roles WHERE user_id = u.id AND role = 'admin' LIMIT 1) AS is_admin
       FROM users u
       {$whereSql}
       ORDER BY u.id DESC
       LIMIT {$perPage} OFFSET {$offset}",
    $params
);

renderAdminHeader('ユーザー管理', 'users');
$flashes = getFlash();
?>

<div class="flex items-center justify-between mb-4">
  <h1 class="text-2xl font-bold text-gray-900">ユーザー管理</h1>
  <form method="GET" class="flex gap-2">
    <input type="text" name="q" value="<?= h($q) ?>" placeholder="名前/メール/ID 検索" class="px-3 py-2 border rounded-lg">
    <label class="inline-flex items-center gap-2 text-sm text-gray-700 px-3 py-2 border rounded-lg bg-white">
      <input type="checkbox" name="active" value="1" <?= $onlyActive ? 'checked' : '' ?>> 有効のみ
    </label>
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
        <th class="text-left px-4 py-3">ユーザー</th>
        <th class="text-left px-4 py-3">メール</th>
        <th class="text-left px-4 py-3">ロール</th>
        <th class="text-left px-4 py-3">状態</th>
        <th class="text-right px-4 py-3">操作</th>
      </tr>
    </thead>
    <tbody class="divide-y">
      <?php foreach ($rows as $r): ?>
        <tr class="hover:bg-gray-50">
          <td class="px-4 py-3 font-mono text-gray-700">#<?= h($r['id']) ?></td>
          <td class="px-4 py-3">
            <div class="font-medium text-gray-900"><?= h($r['full_name'] ?: '(未設定)') ?></div>
          </td>
          <td class="px-4 py-3 text-gray-700"><?= h($r['email']) ?></td>
          <td class="px-4 py-3">
            <span class="inline-flex items-center gap-1 text-xs px-2 py-1 rounded-full border <?= ($r['is_creator'] ? 'border-blue-200 text-blue-700 bg-blue-50' : 'border-gray-200 text-gray-600') ?>">CREATOR</span>
            <span class="inline-flex items-center gap-1 text-xs px-2 py-1 rounded-full border <?= ($r['is_client'] ? 'border-green-200 text-green-700 bg-green-50' : 'border-gray-200 text-gray-600') ?>">CLIENT</span>
            <?php if ((int)($r['is_admin'] ?? 0) === 1): ?>
              <span class="inline-flex items-center gap-1 text-xs px-2 py-1 rounded-full border border-yellow-300 text-yellow-800 bg-yellow-50">ADMIN</span>
            <?php endif; ?>
          </td>
          <td class="px-4 py-3">
            <?php if ((int)$r['is_active'] === 1): ?>
              <span class="px-2 py-1 text-xs rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">有効</span>
            <?php else: ?>
              <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-600 border border-gray-200">無効</span>
            <?php endif; ?>
          </td>
          <td class="px-4 py-3 text-right">
            <form method="POST" class="inline">
              <input type="hidden" name="csrf_token" value="<?= h(generateCsrfToken()) ?>">
              <input type="hidden" name="user_id" value="<?= (int)$r['id'] ?>">
              <button name="action" value="toggle_active" class="px-3 py-1.5 rounded-md border text-xs <?= (int)$r['is_active'] ? 'bg-white hover:bg-gray-50' : 'bg-blue-600 text-white border-blue-600 hover:bg-blue-700' ?>">
                <?= (int)$r['is_active'] ? '無効化' : '有効化' ?>
              </button>
            </form>
            <?php if ((int)($r['is_admin'] ?? 0) === 1): ?>
              <form method="POST" class="inline">
                <input type="hidden" name="csrf_token" value="<?= h(generateCsrfToken()) ?>">
                <input type="hidden" name="user_id" value="<?= (int)$r['id'] ?>">
                <button name="action" value="revoke_admin" class="px-3 py-1.5 rounded-md border text-xs bg-white hover:bg-gray-50">ADMIN解除</button>
              </form>
            <?php else: ?>
              <form method="POST" class="inline">
                <input type="hidden" name="csrf_token" value="<?= h(generateCsrfToken()) ?>">
                <input type="hidden" name="user_id" value="<?= (int)$r['id'] ?>">
                <button name="action" value="grant_admin" class="px-3 py-1.5 rounded-md border text-xs bg-yellow-600 text-white border-yellow-600 hover:bg-yellow-700">ADMIN付与</button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($rows)): ?>
        <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">該当するユーザーが見つかりません</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php
$totalPages = (int)ceil(max(1, $total) / $perPage);
if ($totalPages > 1):
  $base = './users.php?q=' . urlencode($q) . ($onlyActive ? '&active=1' : '') . '&page=';
?>
  <div class="mt-4 flex justify-center gap-2">
    <?php for ($p = 1; $p <= $totalPages; $p++): $is = $p === $page; ?>
      <a class="px-3 py-1.5 rounded-md border text-sm <?= $is ? 'bg-blue-600 text-white border-blue-600' : 'bg-white hover:bg-gray-50' ?>" href="<?= h($base . $p) ?>"><?= $p ?></a>
    <?php endfor; ?>
  </div>
<?php endif; ?>

<?php renderAdminFooter();
