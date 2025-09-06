<?php
require_once 'config/config.php';

// 案件ID取得
$jobId = (int)($_GET['id'] ?? 0);
if (!$jobId) {
    redirect(url('jobs.php'));
}

// データベース接続
$db = Database::getInstance();

// 案件詳細取得
$job = $db->selectOne("
    SELECT j.*, u.full_name as client_name, u.profile_image as client_image, 
           u.location as client_location, c.name as category_name,
           (SELECT COUNT(*) FROM job_applications WHERE job_id = j.id) as application_count
    FROM jobs j
    JOIN users u ON j.client_id = u.id
    LEFT JOIN categories c ON j.category_id = c.id
    WHERE j.id = ?
", [$jobId]);

if (!$job) {
    setFlash('error', '案件が見つかりません。');
    redirect(url('jobs.php'));
}

// 必要スキルをデコード
$requiredSkills = json_decode($job['required_skills'] ?? '[]', true) ?: [];

$pageTitle = $job['title'];
$pageDescription = mb_substr($job['description'], 0, 150) . '...';

// 応募処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply'])) {
    if (!isLoggedIn()) {
        setFlash('error', 'ログインが必要です。');
        redirect(url('login.php?redirect=' . urlencode('job-detail.php?id=' . $jobId)));
    }

    $user = getCurrentUser();
    if ($user['user_type'] !== 'creator') {
        setFlash('error', 'クリエイターアカウントでのみ応募できます。');
    } else {
        // CSRFトークン検証
        if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            setFlash('error', '不正なリクエストです。');
        } else {
            $coverLetter = trim($_POST['cover_letter'] ?? '');
            $proposedPrice = (int)($_POST['proposed_price'] ?? 0);
            $proposedDuration = (int)($_POST['proposed_duration'] ?? 0);

            $errors = [];
            if (empty($coverLetter)) {
                $errors[] = '応募メッセージは必須です。';
            }
            if ($proposedPrice <= 0) {
                $errors[] = '提案金額を入力してください。';
            }
            if ($proposedDuration <= 0) {
                $errors[] = '提案期間を入力してください。';
            }

            if (empty($errors)) {
                $db->beginTransaction();
                try {
                    // 既に応募済みかチェック
                    $existing = $db->selectOne(
                        "SELECT id FROM job_applications WHERE job_id = ? AND creator_id = ?",
                        [$jobId, $user['id']]
                    );

                    if ($existing) {
                        setFlash('error', 'この案件には既に応募済みです。');
                        $db->rollBack();
                    } else {
                        $db->insert("
                            INSERT INTO job_applications (
                                job_id, creator_id, cover_letter, proposed_price, 
                                proposed_duration, created_at
                            ) VALUES (?, ?, ?, ?, ?, NOW())
                        ", [
                            $jobId, $user['id'], $coverLetter, 
                            $proposedPrice, $proposedDuration
                        ]);

                        // 応募数を更新
                        $db->update(
                            "UPDATE jobs SET application_count = application_count + 1 WHERE id = ?",
                            [$jobId]
                        );
                        
                        $db->commit();

                        setFlash('success', '応募を送信しました。');
                        redirect(url('job-detail.php?id=' . $jobId));
                    }
                } catch (Exception $e) {
                    $db->rollBack();
                    setFlash('error', '応募の送信に失敗しました。');
                }
            } else {
                foreach ($errors as $error) {
                    setFlash('error', $error);
                }
            }
        }
    }
}

include 'includes/header.php';
?>

<!-- Job Detail Section -->
<section class="py-8 bg-gray-50 min-h-screen">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Back Button -->
        <div class="mb-6">
            <a href="<?= url('jobs.php') ?>" class="inline-flex items-center text-gray-600 hover:text-blue-600">
                <svg class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                案件一覧に戻る
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Job Header -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex-1">
                            <h1 class="text-2xl font-bold text-gray-900 mb-2"><?= h($job['title']) ?></h1>
                            <div class="flex items-center space-x-4 text-sm text-gray-600">
                                <span class="inline-flex items-center">
                                    <svg class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a.997.997 0 01-1.414 0l-7-7A1.997 1.997 0 013 12V7a4 4 0 014-4z" />
                                    </svg>
                                    <?= h($job['category_name']) ?>
                                </span>
                                <span class="inline-flex items-center">
                                    <svg class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <?= timeAgo($job['created_at']) ?>
                                </span>
                                <span class="inline-flex items-center">
                                    <svg class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                    <?= $job['application_count'] ?>件の応募
                                </span>
                            </div>
                        </div>
                        <div class="ml-4">
                            <span class="px-3 py-1 text-sm rounded-full 
                                <?php
                                switch($job['status']) {
                                    case 'open': echo 'bg-green-100 text-green-800'; break;
                                    case 'in_progress': echo 'bg-blue-100 text-blue-800'; break;
                                    case 'completed': echo 'bg-gray-100 text-gray-800'; break;
                                    case 'cancelled': echo 'bg-red-100 text-red-800'; break;
                                }
                                ?>">
                                <?php
                                switch($job['status']) {
                                    case 'open': echo '募集中'; break;
                                    case 'in_progress': echo '進行中'; break;
                                    case 'completed': echo '完了'; break;
                                    case 'cancelled': echo 'キャンセル'; break;
                                }
                                ?>
                            </span>
                        </div>
                    </div>

                    <!-- Job Details -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6 p-4 bg-gray-50 rounded-lg">
                        <div>
                            <h4 class="text-sm font-medium text-gray-500">予算</h4>
                            <p class="text-lg font-semibold text-gray-900">
                                <?= formatPrice($job['budget_min']) ?> - <?= formatPrice($job['budget_max']) ?>
                            </p>
                        </div>
                        <div>
                            <h4 class="text-sm font-medium text-gray-500">期間</h4>
                            <p class="text-lg font-semibold text-gray-900"><?= $job['duration_weeks'] ?>週間</p>
                        </div>
                        <div>
                            <h4 class="text-sm font-medium text-gray-500">緊急度</h4>
                            <p class="text-lg font-semibold text-gray-900">
                                <?php
                                switch($job['urgency']) {
                                    case 'low': echo '低'; break;
                                    case 'medium': echo '中'; break;
                                    case 'high': echo '高'; break;
                                }
                                ?>
                            </p>
                        </div>
                        <div>
                            <h4 class="text-sm font-medium text-gray-500">リモート</h4>
                            <p class="text-lg font-semibold text-gray-900">
                                <?= $job['remote_ok'] ? '可能' : '不可' ?>
                            </p>
                        </div>
                    </div>

                    <?php if ($job['deadline']): ?>
                        <div class="mb-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                            <div class="flex items-center">
                                <svg class="h-5 w-5 text-yellow-600 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                                </svg>
                                <span class="text-sm font-medium text-yellow-800">
                                    応募締切: <?= formatDate($job['deadline']) ?>
                                </span>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Job Description -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">案件の詳細</h2>
                    <div class="prose max-w-none">
                        <?= nl2br(h($job['description'])) ?>
                    </div>
                </div>

                <!-- Required Skills -->
                <?php if (!empty($requiredSkills)): ?>
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-4">必要なスキル</h2>
                        <div class="flex flex-wrap gap-2">
                            <?php foreach ($requiredSkills as $skill): ?>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                    <?= h($skill) ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($job['location']): ?>
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-4">勤務地・作業場所</h2>
                        <p class="text-gray-700"><?= h($job['location']) ?></p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Client Info -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">依頼者情報</h3>
                    <div class="flex items-center mb-4">
                        <img src="<?= uploaded_asset($job['client_image'] ?? 'assets/images/default-avatar.png') ?>" 
                             alt="<?= h($job['client_name']) ?>" 
                             class="w-12 h-12 rounded-full mr-4">
                        <div>
                            <h4 class="font-semibold text-gray-900"><?= h($job['client_name']) ?></h4>
                            <?php if ($job['client_location']): ?>
                                <p class="text-sm text-gray-600"><?= h($job['client_location']) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <a href="<?= url('creator-profile.php?id=' . $job['client_id']) ?>" 
                       class="text-blue-600 hover:text-blue-500 text-sm font-medium">
                        プロフィールを見る
                    </a>
                </div>

                <!-- Application Form -->
                <?php if (isLoggedIn() && getCurrentUser()['user_type'] === 'creator' && $job['status'] === 'open'): ?>
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">この案件に応募</h3>
                        
                        <form method="POST" class="space-y-4">
                            <input type="hidden" name="csrf_token" value="<?= h(generateCsrfToken()) ?>">
                            
                            <div>
                                <label for="cover_letter" class="block text-sm font-medium text-gray-700 mb-2">
                                    応募メッセージ <span class="text-red-500">*</span>
                                </label>
                                <textarea id="cover_letter" 
                                          name="cover_letter" 
                                          rows="6" 
                                          required
                                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-vertical"
                                          placeholder="なぜこの案件に興味を持ったか、どのような価値を提供できるかを記載してください"></textarea>
                            </div>

                            <div>
                                <label for="proposed_price" class="block text-sm font-medium text-gray-700 mb-2">
                                    提案金額 <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500">¥</span>
                                    <input type="number" 
                                           id="proposed_price" 
                                           name="proposed_price" 
                                           min="<?= $job['budget_min'] ?>"
                                           max="<?= $job['budget_max'] ?>"
                                           required
                                           class="w-full pl-8 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                           placeholder="<?= $job['budget_min'] ?>">
                                </div>
                                <p class="text-xs text-gray-500 mt-1">
                                    予算範囲: <?= formatPrice($job['budget_min']) ?> - <?= formatPrice($job['budget_max']) ?>
                                </p>
                            </div>

                            <div>
                                <label for="proposed_duration" class="block text-sm font-medium text-gray-700 mb-2">
                                    提案期間（週） <span class="text-red-500">*</span>
                                </label>
                                <input type="number" 
                                       id="proposed_duration" 
                                       name="proposed_duration" 
                                       min="1"
                                       max="52"
                                       required
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       placeholder="<?= $job['duration_weeks'] ?>">
                                <p class="text-xs text-gray-500 mt-1">
                                    希望期間: <?= $job['duration_weeks'] ?>週間
                                </p>
                            </div>

                            <button type="submit" 
                                    name="apply"
                                    class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg font-semibold hover:bg-blue-700 transition-colors">
                                応募する
                            </button>
                        </form>
                    </div>
                <?php elseif (!isLoggedIn()): ?>
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 text-center">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">応募するにはログインが必要です</h3>
                        <a href="<?= url('login.php?redirect=' . urlencode('job-detail.php?id=' . $jobId)) ?>" 
                           class="inline-block bg-blue-600 text-white py-2 px-4 rounded-lg font-semibold hover:bg-blue-700 transition-colors">
                            ログイン
                        </a>
                        <p class="text-sm text-gray-600 mt-2">
                            アカウントをお持ちでない場合は
                            <a href="<?= url('register.php') ?>" class="text-blue-600 hover:text-blue-500">
                                クリエイター登録
                            </a>
                        </p>
                    </div>
                <?php elseif (getCurrentUser()['user_type'] !== 'creator'): ?>
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 text-center">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">クリエイターアカウントでのみ応募できます</h3>
                        <p class="text-sm text-gray-600">
                            クリエイターとして登録するには
                            <a href="<?= url('register.php') ?>" class="text-blue-600 hover:text-blue-500">
                                こちら
                            </a>
                        </p>
                    </div>
                <?php elseif ($job['status'] !== 'open'): ?>
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 text-center">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">この案件は募集を終了しています</h3>
                        <a href="<?= url('jobs.php') ?>" 
                           class="inline-block bg-gray-600 text-white py-2 px-4 rounded-lg font-semibold hover:bg-gray-700 transition-colors">
                            他の案件を見る
                        </a>
                    </div>
                <?php endif; ?>

                <!-- Share -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">この案件をシェア</h3>
                    <div class="flex space-x-2">
                        <button class="flex-1 bg-blue-600 text-white py-2 px-3 rounded text-sm font-medium hover:bg-blue-700 transition-colors">
                            Twitter
                        </button>
                        <button class="flex-1 bg-blue-800 text-white py-2 px-3 rounded text-sm font-medium hover:bg-blue-900 transition-colors">
                            Facebook
                        </button>
                        <button class="flex-1 bg-gray-600 text-white py-2 px-3 rounded text-sm font-medium hover:bg-gray-700 transition-colors">
                            コピー
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
