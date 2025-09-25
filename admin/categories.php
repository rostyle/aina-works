<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/admin_layout.php';
requireAdmin();

$db = Database::getInstance();

// Handle actions
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', '不正なリクエストです');
        redirect(adminUrl('categories.php'));
    }
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    try {
        if ($id > 0) {
            if ($action === 'toggle_active') {
                $c = $db->selectOne("SELECT is_active FROM categories WHERE id = ?", [$id]);
                if ($c) {
                    $new = ((int)($c['is_active'] ?? 0) === 1) ? 0 : 1;
                    $db->update("UPDATE categories SET is_active = ? WHERE id = ?", [$new, $id]);
                    setFlash('success', 'カテゴリの有効状態を変更しました');
                }
            } elseif ($action === 'update_sort') {
                $sort = (int)($_POST['sort_order'] ?? 0);
                $db->update("UPDATE categories SET sort_order = ? WHERE id = ?", [$sort, $id]);
                setFlash('success', '表示順を更新しました');
            }
        }
    } catch (Exception $e) {
        setFlash('error', '更新に失敗しました: ' . $e->getMessage());
    }
    redirect(adminUrl('categories.php'));
}

$rows = $db->select("SELECT id, name, description, color, is_active, sort_order FROM categories ORDER BY sort_order ASC, id ASC");

renderAdminHeader('カテゴリ管理', 'categories');
$flashes = getFlash();
?>

<div class="flex items-center justify-between mb-4">
  <h1 class="text-2xl font-bold text-gray-900">カテゴリ管理</h1>
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
        <th class="text-left px-4 py-3">カテゴリ</th>
        <th class="text-left px-4 py-3">色</th>
        <th class="text-left px-4 py-3">表示順</th>
        <th class="text-left px-4 py-3">状態</th>
        <th class="text-right px-4 py-3">操作</th>
      </tr>
    </thead>
    <tbody class="divide-y">
      <?php foreach ($rows as $r): ?>
        <tr class="hover:bg-gray-50">
          <td class="px-4 py-3 font-mono">#<?= (int)$r['id'] ?></td>
          <td class="px-4 py-3">
            <div class="font-medium text-gray-900"><?= h($r['name']) ?></div>
            <div class="text-xs text-gray-600 max-w-xl truncate" title="<?= h($r['description'] ?? '') ?>"><?= h($r['description'] ?? '') ?></div>
          </td>
          <td class="px-4 py-3">
            <?php $color = $r['color'] ?: '#e5e7eb'; ?>
            <span class="inline-flex items-center gap-2">
              <span class="inline-block w-4 h-4 rounded" style="background: <?= h($color) ?>"></span>
              <span class="text-gray-700 font-mono text-xs"><?= h($color) ?></span>
            </span>
          </td>
          <td class="px-4 py-3">
            <form method="POST" class="inline-flex items-center gap-2">
              <input type="hidden" name="csrf_token" value="<?= h(generateCsrfToken()) ?>">
              <input type="hidden" name="action" value="update_sort">
              <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
              <input type="number" name="sort_order" value="<?= (int)($r['sort_order'] ?? 0) ?>" class="w-24 px-2 py-1 border rounded">
              <button class="px-3 py-1.5 rounded-md border text-xs bg-white hover:bg-gray-50">更新</button>
            </form>
          </td>
          <td class="px-4 py-3">
            <?php if ((int)($r['is_active'] ?? 0) === 1): ?>
              <span class="px-2 py-1 text-xs rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">有効</span>
            <?php else: ?>
              <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-600 border border-gray-200">無効</span>
            <?php endif; ?>
          </td>
          <td class="px-4 py-3 text-right">
            <form method="POST" class="inline">
              <input type="hidden" name="csrf_token" value="<?= h(generateCsrfToken()) ?>">
              <input type="hidden" name="action" value="toggle_active">
              <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
              <button class="px-3 py-1.5 rounded-md border text-xs bg-white hover:bg-gray-50"><?= (int)($r['is_active'] ?? 0) === 1 ? '無効化' : '有効化' ?></button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($rows)): ?>
        <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">カテゴリがありません</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
 </div>

<script>
(function(){
  const tbody = document.querySelector('table tbody');
  if (!tbody) return;
  let draggingEl = null;
  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

  function makeRowsDraggable(){
    const rows = tbody.querySelectorAll('tr');
    rows.forEach(tr => {
      tr.setAttribute('draggable', 'true');
      tr.classList.add('cursor-grab');
      tr.addEventListener('dragstart', onDragStart);
      tr.addEventListener('dragend', onDragEnd);
    });
  }

  function onDragStart(e){
    draggingEl = e.currentTarget;
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/plain', 'drag');
    draggingEl.classList.add('opacity-50');
  }
  function onDragEnd(){
    if (draggingEl){ draggingEl.classList.remove('opacity-50'); }
    draggingEl = null;
  }
  function getRowAfter(y){
    const rows = [...tbody.querySelectorAll('tr')].filter(r=>r!==draggingEl);
    return rows.find(row => y <= row.getBoundingClientRect().top + row.offsetHeight/2);
  }
  function onDragOver(e){
    e.preventDefault();
    const after = getRowAfter(e.clientY);
    if (!after){
      tbody.appendChild(draggingEl);
    } else {
      tbody.insertBefore(draggingEl, after);
    }
  }
  function persistOrder(){
    const ids = [...tbody.querySelectorAll('tr')].map(tr => {
      const idCell = tr.querySelector('td');
      if (!idCell) return null;
      const text = idCell.textContent || '';
      const m = text.match(/#?(\d+)/);
      return m ? parseInt(m[1],10) : null;
    }).filter(v=>Number.isInteger(v));
    if (!ids.length) return;
    fetch('./categories_reorder.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
      body: JSON.stringify({ order: ids })
    }).then(r=>r.json()).then(j=>{
      if (!j.success){
        console.error('Reorder failed', j);
        alert(j.message || '並び順の保存に失敗しました');
      }
    }).catch(err=>{
      console.error(err);
      alert('並び順の保存に失敗しました');
    });
  }
  function onDrop(){
    persistOrder();
  }

  tbody.addEventListener('dragover', onDragOver);
  tbody.addEventListener('drop', onDrop);
  makeRowsDraggable();
})();
</script>

<?php renderAdminFooter();
