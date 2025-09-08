<?php
require_once 'config/config.php';

$pageTitle = '案件を編集';
$db = Database::getInstance();

$jobId = (int)($_GET['id'] ?? 0);
if (!$jobId) {
    redirect(url('jobs'));
}

if (!isLoggedIn()) {
    redirect(url('login?redirect=' . urlencode('edit-job?id=' . $jobId)));
}

$currentUser = getCurrentUser();

$job = $db->selectOne("SELECT * FROM jobs WHERE id = ?", [$jobId]);
if (!$job) {
    setFlash('error', '案件が見つかりません');
    redirect(url('jobs'));
}

if ((int)$job['client_id'] !== (int)$currentUser['id']) {
    setFlash('error', 'この案件を編集する権限がありません');
    redirect(url('job-detail?id=' . $jobId));
}

if ($job['status'] !== 'open') {
    setFlash('error', '募集中の案件のみ編集できます');
    redirect(url('job-detail?id=' . $jobId));
}

$categories = $db->select("SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order ASC");

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = '不正なリクエストです。';
    } else {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $categoryId = (int)($_POST['category_id'] ?? 0);
        $budgetMin = (int)($_POST['budget_min'] ?? 0);
        $budgetMax = (int)($_POST['budget_max'] ?? 0);
        $durationWeeks = (int)($_POST['duration_weeks'] ?? 1);
        $urgency = $_POST['urgency'] ?? 'medium';
        $deadline = $_POST['deadline'] ?? '';

        if ($title === '' || mb_strlen($title) > 200) {
            $errors[] = '案件タイトルは1〜200文字で入力してください。';
        }
        if ($description === '' || mb_strlen($description) < 50) {
            $errors[] = '詳細説明は50文字以上で入力してください。';
        }
        if (!$categoryId) {
            $errors[] = 'カテゴリを選択してください。';
        }
        if ($budgetMin <= 0 || $budgetMax <= 0 || $budgetMin > $budgetMax) {
            $errors[] = '予算を正しく入力してください。';
        }
        if ($durationWeeks <= 0) {
            $errors[] = '期間を正しく入力してください。';
        }
        if (!in_array($urgency, ['low','medium','high'], true)) {
            $urgency = 'medium';
        }
        if (!empty($deadline) && strtotime($deadline) < time()) {
            $errors[] = '締切日は未来の日付を入力してください。';
        }

        if (empty($errors)) {
            try {
                $db->update(
                    "UPDATE jobs SET title=?, description=?, category_id=?, budget_min=?, budget_max=?, duration_weeks=?, urgency=?, deadline=? WHERE id=?",
                    [
                        $title,
                        $description,
                        $categoryId,
                        $budgetMin,
                        $budgetMax,
                        $durationWeeks,
                        $urgency,
                        $deadline ?: null,
                        $jobId
                    ]
                );
                setFlash('success', '案件を更新しました');
                redirect(url('job-detail?id=' . $jobId));
            } catch (Exception $e) {
                $errors[] = '更新に失敗しました。再度お試しください。';
            }
        }
    }
}

include 'includes/header.php';
?>

<section class="py-8 bg-gray-50 min-h-screen">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-2xl font-bold text-gray-900 mb-6">案件を編集</h1>

        <?php if (!empty($errors)): ?>
        <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
            <ul class="text-sm text-red-700 space-y-1">
                <?php foreach ($errors as $err): ?>
                    <li><?= h($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <form method="POST" class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 space-y-6">
            <input type="hidden" name="csrf_token" value="<?= h(generateCsrfToken()) ?>">

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">タイトル</label>
                <input type="text" name="title" value="<?= h($job['title']) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg" required>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">詳細</label>
                <textarea name="description" rows="8" class="w-full px-3 py-2 border border-gray-300 rounded-lg" required><?= h($job['description']) ?></textarea>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">カテゴリ</label>
                    <select name="category_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg" required>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= h($c['id']) ?>" <?= (int)$c['id'] === (int)$job['category_id'] ? 'selected' : '' ?>><?= h($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">期間（週）</label>
                    <input type="number" name="duration_weeks" min="1" value="<?= h($job['duration_weeks']) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg" required>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">予算（下限）</label>
                    <input type="number" name="budget_min" min="0" value="<?= h($job['budget_min']) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">予算（上限）</label>
                    <input type="number" name="budget_max" min="0" value="<?= h($job['budget_max']) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg" required>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">緊急度</label>
                    <select name="urgency" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        <option value="low" <?= $job['urgency']==='low'?'selected':'' ?>>低</option>
                        <option value="medium" <?= $job['urgency']==='medium'?'selected':'' ?>>中</option>
                        <option value="high" <?= $job['urgency']==='high'?'selected':'' ?>>高</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">締切</label>
                    <input type="date" name="deadline" value="<?= h($job['deadline']) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                </div>
            </div>

            <div class="flex justify-end space-x-3">
                <a href="<?= url('job-detail?id=' . $jobId) ?>" class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700">戻る</a>
                <button type="submit" class="px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700">保存</button>
            </div>
        </form>
    </div>
</section>

<?php include 'includes/footer.php'; ?>


