<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/admin_layout.php';
requireAdmin();

$db = Database::getInstance();

// Update status
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', '不正なリクエストです');
        redirect(adminUrl('jobs.php'));
    }
    $jobId = (int)($_POST['job_id'] ?? 0);
    $newStatus = trim($_POST['status'] ?? '');
    $allowed = ['open','in_progress','contracted','delivered','closed','completed','cancelled'];
    if ($jobId > 0 && in_array($newStatus, $allowed, true)) {
        try {
            $db->update("UPDATE jobs SET status = ? WHERE id = ?", [$newStatus, $jobId]);
            setFlash('success', '案件ステータスを更新しました');
        } catch (Exception $e) {
            setFlash('error', '更新に失敗しました: ' . $e->getMessage());
        }
    }
    redirect(adminUrl('jobs.php'));
}

// Filters
$q = trim($_GET['q'] ?? '');
$status = $_GET['status'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$where = [];
$params = [];
if ($q !== '') {
    $where[] = "(j.title LIKE ? OR u.full_name LIKE ? OR j.id = ?)";
    $params[] = "%{$q}%";
    $params[] = "%{$q}%";
    $params[] = ctype_digit($q) ? (int)$q : 0;
}
if ($status !== '') {
    $where[] = "j.status = ?";
    $params[] = $status;
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$total = (int)($db->selectOne("SELECT COUNT(*) AS c FROM jobs j JOIN users u ON j.client_id = u.id {$whereSql}", $params)['c'] ?? 0);
$offset = ($page - 1) * $perPage;

$rows = $db->select(
    "SELECT j.id, j.title, j.status, j.budget_min, j.budget_max, j.applications_count,
            u.full_name AS client_name, u.id AS client_id,
            c.name AS category_name
       FROM jobs j
       JOIN users u ON j.client_id = u.id
       LEFT JOIN categories c ON j.category_id = c.id
       {$whereSql}
       ORDER BY j.id DESC
       LIMIT {$perPage} OFFSET {$offset}",
    $params
);

$statusOptions = [
    'open' => '募集中',
    'in_progress' => '進行中',
    'contracted' => '契約済み',
    'delivered' => '納品済み',
    'closed' => '募集終了',
    'completed' => '完了',
    'cancelled' => 'キャンセル',
];

renderAdminHeader('案件管理', 'jobs');
$flashes = getFlash();
?>

<div class="flex items-center justify-between mb-4">
  <h1 class="text-2xl font-bold text-gray-900">案件管理</h1>
  <form method="GET" class="flex gap-2">
    <input type="text" name="q" value="<?= h($q) ?>" placeholder="案件/クライアント/ID" class="px-3 py-2 border rounded-lg">
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
        <th class="text-left px-4 py-3">クライアント</th>
        <th class="text-left px-4 py-3">カテゴリ</th>
        <th class="text-left px-4 py-3">応募</th>
        <th class="text-left px-4 py-3">状態</th>
        <th class="text-right px-4 py-3">操作</th>
      </tr>
    </thead>
    <tbody class="divide-y">
      <?php foreach ($rows as $r): ?>
        <tr class="hover:bg-gray-50">
          <td class="px-4 py-3 font-mono">#<?= (int)$r['id'] ?></td>
          <td class="px-4 py-3">
            <div class="font-medium text-gray-900"><?= h($r['title']) ?></div>
            <div class="text-xs text-gray-500"><?= '¥' . number_format((int)$r['budget_min']) ?> - <?= '¥' . number_format((int)$r['budget_max']) ?></div>
          </td>
          <td class="px-4 py-3 text-gray-700">#<?= (int)$r['client_id'] ?> <?= h($r['client_name']) ?></td>
          <td class="px-4 py-3 text-gray-700"><?= h($r['category_name'] ?? '-') ?></td>
          <td class="px-4 py-3 text-gray-700"><?= (int)$r['applications_count'] ?></td>
          <td class="px-4 py-3 text-gray-700">
            <?php
            $badges = [
                'open' => 'bg-green-100 text-green-800',
                'in_progress' => 'bg-blue-100 text-blue-800',
                'contracted' => 'bg-indigo-100 text-indigo-800',
                'delivered' => 'bg-purple-100 text-purple-800',
                'closed' => 'bg-gray-100 text-gray-800',
                'completed' => 'bg-gray-800 text-white',
                'cancelled' => 'bg-red-100 text-red-800',
            ];
            $color = $badges[$r['status']] ?? 'bg-gray-100 text-gray-800';
            ?>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $color ?>">
              <?= h($statusOptions[$r['status']] ?? $r['status']) ?>
            </span>
          <td class="px-4 py-3 text-right">
            <button type="button" onclick="openJobModal(<?= (int)$r['id'] ?>)" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-medium bg-white border hover:bg-gray-50 text-gray-700 shadow-sm transition-colors">
              <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
              詳細 / 編集
            </button>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($rows)): ?>
        <tr><td colspan="7" class="px-4 py-8 text-center text-gray-500">該当する案件が見つかりません</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php
$totalPages = (int)ceil(max(1, $total) / $perPage);
if ($totalPages > 1):
  $base = './jobs.php?q=' . urlencode($q) . ($status!=='' ? '&status=' . urlencode($status) : '') . '&page=';
?>
  <div class="mt-4 flex justify-center gap-2">
    <?php for ($p = 1; $p <= $totalPages; $p++): $is = $p === $page; ?>
      <a class="px-3 py-1.5 rounded-md border text-sm <?= $is ? 'bg-blue-600 text-white border-blue-600' : 'bg-white hover:bg-gray-50' ?>" href="<?= h($base . $p) ?>"><?= $p ?></a>
    <?php endfor; ?>
  </div>
<?php endif; ?>

<!-- Admin Job Detail Modal -->
<div id="job-modal" class="fixed inset-0 z-50 hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm transition-opacity" onclick="closeJobModal()"></div>
    <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
            <div class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-2xl">
                
                <!-- Close Button -->
                <button type="button" class="absolute top-4 right-4 z-10 text-gray-400 hover:text-gray-500 focus:outline-none" onclick="closeJobModal()">
                    <span class="sr-only">Close</span>
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>

                <!-- Modal Content -->
                <form method="POST" action="jobs.php" id="modal-form">
                    <input type="hidden" name="csrf_token" value="<?= h(generateCsrfToken()) ?>">
                    <input type="hidden" name="job_id" id="modal-job-id" value="">
                    
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div id="modal-loading" class="text-center py-10">
                            <svg class="animate-spin h-8 w-8 text-blue-500 mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <p class="mt-2 text-sm text-gray-500">読み込み中...</p>
                        </div>

                        <div id="modal-body" class="hidden text-left">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-1" id="modal-title">Job Title</h3>
                            <div class="text-xs text-gray-500 mb-4 flex gap-3">
                                <span id="modal-category" class="bg-gray-100 px-2 py-0.5 rounded">Category</span>
                                <span id="modal-date">Date</span>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                                <div class="col-span-2 space-y-4">
                                    <div>
                                        <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">案件内容</h4>
                                        <div class="text-sm text-gray-700 bg-gray-50 p-3 rounded-lg border border-gray-100 max-h-48 overflow-y-auto whitespace-pre-wrap" id="modal-desc"></div>
                                    </div>
                                    
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">予算</h4>
                                            <p class="text-sm font-bold text-blue-600" id="modal-budget">¥0</p>
                                        </div>
                                        <div>
                                            <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">期限</h4>
                                            <p class="text-sm font-bold text-red-600" id="modal-deadline">-</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-span-1 border-l pl-6 border-gray-100 space-y-4">
                                    <div>
                                        <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">クライアント</h4>
                                        <div class="flex items-center mb-2">
                                            <img id="modal-client-img" src="" class="w-8 h-8 rounded-full bg-gray-200 mr-2">
                                            <div>
                                                <p class="text-sm font-bold text-gray-900" id="modal-client-name"></p>
                                                <p class="text-xs text-gray-500" id="modal-client-email"></p>
                                            </div>
                                        </div>
                                    </div>

                                    <div>
                                        <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">ステータス変更</h4>
                                        <select name="status" id="modal-status" class="block w-full text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                                            <?php foreach ($statusOptions as $k => $label): ?>
                                                <option value="<?= h($k) ?>"><?= h($label) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t border-gray-100 hidden" id="modal-footer">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                            ステータスを更新して閉じる
                        </button>
                        <button type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm" onclick="closeJobModal()">
                            キャンセル
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function openJobModal(jobId) {
    const modal = document.getElementById('job-modal');
    modal.classList.remove('hidden');
    
    // Reset
    document.getElementById('modal-loading').classList.remove('hidden');
    document.getElementById('modal-body').classList.add('hidden');
    document.getElementById('modal-footer').classList.add('hidden');
    document.getElementById('modal-job-id').value = jobId;

    // Fetch
    fetch(`../api/get_job.php?id=${jobId}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const job = data.job;
                document.getElementById('modal-title').textContent = job.title;
                document.getElementById('modal-desc').textContent = job.description; // raw text for admin
                document.getElementById('modal-category').textContent = job.category_name || '未分類';
                document.getElementById('modal-date').textContent = job.created_at_formatted;
                document.getElementById('modal-budget').textContent = `¥${job.budget_min_formatted} ~ ¥${job.budget_max_formatted}`;
                document.getElementById('modal-deadline').textContent = job.deadline_formatted;
                
                document.getElementById('modal-client-name').textContent = job.client_name;
                document.getElementById('modal-client-img').src = job.client_image ? `../${job.client_image}` : '../assets/images/default-avatar.png'; // Adjust path for admin
                // Note: client_email might be sensitive, checking if API returned it.
                if(job.client_email) document.getElementById('modal-client-email').textContent = job.client_email;

                // Set Select Status
                const statusSelect = document.getElementById('modal-status');
                statusSelect.value = job.status;

                // Show content
                document.getElementById('modal-loading').classList.add('hidden');
                document.getElementById('modal-body').classList.remove('hidden');
                document.getElementById('modal-footer').classList.remove('hidden');
            } else {
                alert('Load failed');
                closeJobModal();
            }
        })
        .catch(err => {
            console.error(err);
            alert('Error loading job details');
            closeJobModal();
        });
}

function closeJobModal() {
    document.getElementById('job-modal').classList.add('hidden');
}
</script>

<?php renderAdminFooter();
