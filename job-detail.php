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
           j.applications_count as application_count
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

// 既に応募済みかチェック & 自分の案件かチェック
$hasApplied = false;
$isOwnJob = false;
$currentUser = null;
if (isLoggedIn()) {
    $currentUser = getCurrentUser();
    
    // 自分の案件かチェック
    $isOwnJob = ($job['client_id'] == $currentUser['id']);
    
    // クリエイターの場合、応募済みかチェック
    if ($currentUser['user_type'] === 'creator') {
        $existingApplication = $db->selectOne(
            "SELECT id FROM job_applications WHERE job_id = ? AND creator_id = ?",
            [$jobId, $currentUser['id']]
        );
        $hasApplied = (bool)$existingApplication;
    }
}

// 応募処理 - デバッグ版
// ※ 隠しフィールドで submit_application=1 を常に送るように修正（下のフォーム）
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_application'])) {
    error_log('応募処理開始');
    error_log('POST data: ' . print_r($_POST, true));
    
    // ログイン確認
    if (!isLoggedIn()) {
        error_log('ログインしていません');
        setFlash('error', 'ログインが必要です。');
        redirect(url('job-detail.php?id=' . $jobId));
    }

    $user = getCurrentUser();
    error_log('ユーザー情報: ' . print_r($user, true));

    // クリエイター以外の応募ブロック
    if ($user['user_type'] !== 'creator') {
        setFlash('error', 'クリエイターのみ応募できます。');
        redirect(url('job-detail.php?id=' . $jobId));
    }
    
    // 自分の案件かチェック
    if ($job['client_id'] == $user['id']) {
        setFlash('error', '自分の案件には応募できません。');
        redirect(url('job-detail.php?id=' . $jobId));
    }

    // 入力値取得（数値は下限ガード）
    $coverLetter = trim($_POST['cover_letter'] ?? '');
    $proposedPrice = max(0, (int)($_POST['proposed_price'] ?? 0));
    $proposedDuration = max(0, (int)($_POST['proposed_duration'] ?? 0));
    
    error_log('入力値: coverLetter=' . $coverLetter . ', proposedPrice=' . $proposedPrice . ', proposedDuration=' . $proposedDuration);

    // バリデーション
    if ($coverLetter === '') {
        error_log('応募メッセージが空です');
        setFlash('error', '応募メッセージを入力してください。');
        redirect(url('job-detail.php?id=' . $jobId));
    }
    if ($proposedPrice <= 0) {
        error_log('提案金額が無効です: ' . $proposedPrice);
        setFlash('error', '提案金額を正しく入力してください。');
        redirect(url('job-detail.php?id=' . $jobId));
    }
    if ($proposedDuration <= 0) {
        error_log('提案期間が無効です: ' . $proposedDuration);
        setFlash('error', '提案期間を正しく入力してください。');
        redirect(url('job-detail.php?id=' . $jobId));
    }

    // 重複チェック
    $existingApp = $db->selectOne(
        "SELECT id FROM job_applications WHERE job_id = ? AND creator_id = ?",
        [$jobId, $user['id']]
    );
    if ($existingApp) {
        setFlash('error', '既に応募済みです。');
        redirect(url('job-detail.php?id=' . $jobId));
    }

    // 応募データ挿入
    try {
        error_log('データベース処理開始');
        $db->beginTransaction();
        
        $applicationId = $db->insert(
            "INSERT INTO job_applications (job_id, creator_id, cover_letter, proposed_price, proposed_duration, status, created_at) VALUES (?, ?, ?, ?, ?, 'pending', NOW())",
            [$jobId, $user['id'], $coverLetter, $proposedPrice, $proposedDuration]
        );
        
        error_log('応募データ挿入完了: ID=' . $applicationId);
        
        // 応募数更新
        $db->update(
            "UPDATE jobs SET applications_count = applications_count + 1 WHERE id = ?",
            [$jobId]
        );
        
        error_log('応募数更新完了');
        
        $db->commit();
        
        error_log('応募処理成功');
        setFlash('success', '応募を送信しました！');

        // 成功時は同ページに戻して成功バナーを表示
        redirect(url('job-detail.php?id=' . $jobId . '&applied=1'));
        
    } catch (Exception $e) {
        error_log('応募処理エラー: ' . $e->getMessage());
        $db->rollBack();
        setFlash('error', '応募の送信に失敗しました。もう一度お試しください。');
        redirect(url('job-detail.php?id=' . $jobId));
    }
}

include 'includes/header.php';

// 応募成功の表示
$showSuccess = isset($_GET['applied']) && $_GET['applied'] == '1';
?>

<!-- Job Detail Section -->
<section class="py-8 bg-gray-50 min-h-screen">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Success Message -->
        <?php if ($showSuccess): ?>
        <div class="mb-6 bg-green-50 border border-green-200 rounded-lg p-4">
            <div class="flex items-center">
                <svg class="h-5 w-5 text-green-600 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <p class="text-green-800 font-medium">応募を送信しました！依頼者からの連絡をお待ちください。</p>
            </div>
        </div>
        <?php endif; ?>
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
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0z" />
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
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6 p-4 bg-gray-50 rounded-lg">
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

                <!-- Application Button -->
                <?php if (isLoggedIn() && getCurrentUser()['user_type'] === 'creator' && $job['status'] === 'open' && !$hasApplied && !$isOwnJob): ?>
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 text-center">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">この案件に応募</h3>
                        <button id="open-application-modal" 
                                    class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg font-semibold hover:bg-blue-700 transition-colors">
                                応募する
                            </button>
                    </div>
                <?php elseif (isLoggedIn() && getCurrentUser()['user_type'] === 'creator' && $hasApplied): ?>
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 text-center">
                        <div class="flex items-center justify-center mb-4">
                            <svg class="h-12 w-12 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">応募済み</h3>
                        <p class="text-sm text-gray-600 mb-4">この案件には既に応募済みです。</p>
                        <a href="<?= url('dashboard.php') ?>" 
                           class="inline-block bg-gray-600 text-white py-2 px-4 rounded-lg font-semibold hover:bg-gray-700 transition-colors">
                            応募状況を確認
                        </a>
                    </div>
                <?php elseif (isLoggedIn() && $isOwnJob): ?>
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 text-center">
                        <div class="flex items-center justify-center mb-4">
                            <svg class="h-12 w-12 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">あなたの案件です</h3>
                        <p class="text-sm text-gray-600 mb-4">自分が投稿した案件には応募できません。</p>
                        <a href="<?= url('job-applications.php?job_id=' . $jobId) ?>" 
                           class="inline-block bg-blue-600 text-white py-2 px-4 rounded-lg font-semibold hover:bg-blue-700 transition-colors">
                            応募状況を確認
                        </a>
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
                <?php elseif ($job['status'] !== 'open'): ?>
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 text-center">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">この案件は応募受付を終了しています</h3>
                        <p class="text-sm text-gray-600">
                            この案件は応募受付を終了しています
                        </p>
                        <a href="<?= url('jobs.php') ?>" 
                           class="inline-block bg-gray-600 text-white py-2 px-4 rounded-lg font-semibold hover:bg-gray-700 transition-colors">
                            他の案件を見る
                        </a>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</section>

<!-- Application Modal -->
<?php if (isLoggedIn() && getCurrentUser()['user_type'] === 'creator' && $job['status'] === 'open' && !$hasApplied && !$isOwnJob): ?>
<div id="application-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 hidden" role="dialog" aria-modal="true">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <!-- Modal Header -->
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">案件に応募</h3>
                <button id="close-modal" class="text-gray-400 hover:text-gray-600" type="button" aria-label="閉じる">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            
            <!-- Application Form -->
            <form id="application-form" method="POST" action="<?= url('job-detail.php?id=' . $jobId) ?>" class="space-y-4">
                <!-- 常に送られるように隠しフィールドを追加 -->
                <input type="hidden" name="submit_application" value="1">
                <input type="hidden" name="job_id" value="<?= $jobId ?>">
                
                <div>
                    <label for="modal_cover_letter" class="block text-sm font-medium text-gray-700 mb-2">
                        応募メッセージ <span class="text-red-500">*</span>
                    </label>
                    <textarea id="modal_cover_letter" 
                              name="cover_letter" 
                              rows="4" 
                              required
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-vertical"
                              placeholder="なぜこの案件に興味を持ったか、どのような価値を提供できるかを記載してください"></textarea>
                </div>

                <div>
                    <label for="modal_proposed_price" class="block text-sm font-medium text-gray-700 mb-2">
                        提案金額 <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500">¥</span>
                        <input type="number" 
                               id="modal_proposed_price" 
                               name="proposed_price" 
                               min="1000"
                               step="1000"
                               required
                               class="w-full pl-8 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="100000">
                    </div>
                    <p class="text-xs text-gray-500 mt-1">
                        参考予算: <?= formatPrice($job['budget_min']) ?> - <?= formatPrice($job['budget_max']) ?>
                    </p>
                </div>

                <div>
                    <label for="modal_proposed_duration" class="block text-sm font-medium text-gray-700 mb-2">
                        提案期間（週） <span class="text-red-500">*</span>
                    </label>
                    <input type="number" 
                           id="modal_proposed_duration" 
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

                <div class="flex space-x-3 pt-4">
                    <button type="button" 
                            id="cancel-application"
                            class="flex-1 bg-gray-300 text-gray-700 py-2 px-4 rounded-lg font-medium hover:bg-gray-400 transition-colors">
                        キャンセル
                    </button>
                    <button type="submit" 
                            id="submit-application-btn"
                            name="submit_application"
                            value="1"
                            class="flex-1 bg-blue-600 text-white py-2 px-4 rounded-lg font-medium hover:bg-blue-700 transition-colors">
                        応募する
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('application-modal');
    const openBtn = document.getElementById('open-application-modal');
    const closeBtn = document.getElementById('close-modal');
    const cancelBtn = document.getElementById('cancel-application');
    const form = document.getElementById('application-form');
    const submitBtn = document.getElementById('submit-application-btn');
    
    // モーダルを開く
    if (openBtn) {
        openBtn.addEventListener('click', function() {
            console.log('モーダルを開く');
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            // フォーカスを最初の必須項目へ
            const firstInput = document.getElementById('modal_cover_letter');
            if (firstInput) firstInput.focus();
        });
    }
    
    // フォーム送信のデバッグ & 二重送信防止
    if (form) {
        form.addEventListener('submit', function(e) {
            console.log('フォーム送信開始');
            try {
                const fd = new FormData(form);
                for (const [k, v] of fd.entries()) {
                    console.log(k + ':', v);
                }
            } catch (err) {
                console.log('FormDataのログでエラー:', err);
            }
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = '送信中...';
            }
        });
    }
    
    // モーダルを閉じる
    function closeModal() {
        modal.classList.add('hidden');
        document.body.style.overflow = 'auto';
    }
    
    if (closeBtn) {
        closeBtn.addEventListener('click', closeModal);
    }
    
    if (cancelBtn) {
        cancelBtn.addEventListener('click', closeModal);
    }
    
    // 背景クリックで閉じる
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeModal();
            }
        });
    }
    
    // ESCキーで閉じる
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal && !modal.classList.contains('hidden')) {
            closeModal();
        }
    });
});
</script>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
