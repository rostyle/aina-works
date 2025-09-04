<?php
require_once 'config/config.php';

$pageTitle = 'ダッシュボード';
$pageDescription = 'あなたの活動状況を確認';

// ログインチェック
if (!isLoggedIn()) {
    redirect(url('login.php'));
}

$user = getCurrentUser();
if (!$user) {
    // ユーザー情報が取得できない場合はログアウト
    session_destroy();
    redirect(url('login.php'));
}

$currentRole = getCurrentRole();
$availableRoles = getUserRoles();
$db = Database::getInstance();

// 統計情報取得
$stats = [];

if ($currentRole === 'creator') {
    // クリエイター向け統計
    $stats = [
        'works' => $db->selectOne("SELECT COUNT(*) as count FROM works WHERE user_id = ?", [$user['id']])['count'] ?? 0,
        'applications' => $db->selectOne("SELECT COUNT(*) as count FROM job_applications WHERE creator_id = ?", [$user['id']])['count'] ?? 0,
        'reviews' => $db->selectOne("SELECT COUNT(*) as count FROM reviews WHERE reviewee_id = ?", [$user['id']])['count'] ?? 0,
        'avg_rating' => $db->selectOne("SELECT AVG(rating) as avg FROM reviews WHERE reviewee_id = ?", [$user['id']])['avg'] ?? 0,
    ];

    // 最近の応募
    $recentApplications = $db->select("
        SELECT ja.*, j.title as job_title, j.budget_min, j.budget_max, u.full_name as client_name
        FROM job_applications ja
        JOIN jobs j ON ja.job_id = j.id
        JOIN users u ON j.client_id = u.id
        WHERE ja.creator_id = ?
        ORDER BY ja.created_at DESC
        LIMIT 5
    ", [$user['id']]);

    // おすすめ案件
    $recommendedJobs = $db->select("
        SELECT j.*, u.full_name as client_name, c.name as category_name
        FROM jobs j
        JOIN users u ON j.client_id = u.id
        LEFT JOIN categories c ON j.category_id = c.id
        WHERE j.status = 'open'
        ORDER BY j.created_at DESC
        LIMIT 5
    ");

} else {
    // クライアント向け統計
    $stats = [
        'jobs' => $db->selectOne("SELECT COUNT(*) as count FROM jobs WHERE client_id = ?", [$user['id']])['count'] ?? 0,
        'applications' => $db->selectOne("SELECT COUNT(*) as count FROM job_applications ja JOIN jobs j ON ja.job_id = j.id WHERE j.client_id = ?", [$user['id']])['count'] ?? 0,
        'completed' => $db->selectOne("SELECT COUNT(*) as count FROM jobs WHERE client_id = ? AND status = 'completed'", [$user['id']])['count'] ?? 0,
        'in_progress' => $db->selectOne("SELECT COUNT(*) as count FROM jobs WHERE client_id = ? AND status = 'in_progress'", [$user['id']])['count'] ?? 0,
    ];

    // 最近の案件
    $recentJobs = $db->select("
        SELECT j.*, c.name as category_name,
               (SELECT COUNT(*) FROM job_applications WHERE job_id = j.id) as application_count
        FROM jobs j
        LEFT JOIN categories c ON j.category_id = c.id
        WHERE j.client_id = ?
        ORDER BY j.created_at DESC
        LIMIT 5
    ", [$user['id']]);
}

include 'includes/header.php';
?>

<!-- Dashboard Section -->
<section class="py-8 bg-gray-50 min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">
                おかえりなさい、<?= h($user['full_name']) ?>さん
            </h1>
            <p class="text-gray-600 mt-2">
                <?php if ($currentRole === 'creator'): ?>
                    あなたのクリエイター活動の状況をご確認ください
                <?php else: ?>
                    あなたの案件の状況をご確認ください
                <?php endif; ?>
            </p>
            
            <?php if (is_array($availableRoles) && count($availableRoles) > 1): ?>
                <div class="mt-4">
                    <p class="text-sm text-gray-500 mb-2">現在のロール: <span class="font-medium text-blue-600"><?= getRoleDisplayName($currentRole) ?></span></p>
                    <div class="flex items-center space-x-2">
                        <span class="text-sm text-gray-500">切り替え:</span>
                        <?php foreach ($availableRoles as $role): ?>
                            <?php if ($role !== $currentRole): ?>
                                <a href="<?= url('switch-role.php?role=' . urlencode($role)) ?>" 
                                   class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-700 hover:bg-blue-100 hover:text-blue-700 transition-colors">
                                    <?= getRoleDisplayName($role) ?>
                                </a>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <?php if ($currentRole === 'creator'): ?>
                <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
                    <div class="flex items-center">
                        <div class="p-2 bg-blue-100 rounded-lg">
                            <svg class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-sm font-medium text-gray-500">公開作品</h3>
                            <p class="text-2xl font-bold text-gray-900"><?= number_format($stats['works']) ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
                    <div class="flex items-center">
                        <div class="p-2 bg-green-100 rounded-lg">
                            <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-sm font-medium text-gray-500">応募案件</h3>
                            <p class="text-2xl font-bold text-gray-900"><?= number_format($stats['applications']) ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
                    <div class="flex items-center">
                        <div class="p-2 bg-yellow-100 rounded-lg">
                            <svg class="h-6 w-6 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-sm font-medium text-gray-500">平均評価</h3>
                            <p class="text-2xl font-bold text-gray-900"><?= number_format($stats['avg_rating'] ?? 0, 1) ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
                    <div class="flex items-center">
                        <div class="p-2 bg-purple-100 rounded-lg">
                            <svg class="h-6 w-6 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-sm font-medium text-gray-500">レビュー数</h3>
                            <p class="text-2xl font-bold text-gray-900"><?= number_format($stats['reviews']) ?></p>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
                    <div class="flex items-center">
                        <div class="p-2 bg-blue-100 rounded-lg">
                            <svg class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2-2v2m8 0V6a2 2 0 012 2v6a2 2 0 01-2 2H6a2 2 0 01-2-2V8a2 2 0 012-2V6" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-sm font-medium text-gray-500">投稿案件</h3>
                            <p class="text-2xl font-bold text-gray-900"><?= number_format($stats['jobs']) ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
                    <div class="flex items-center">
                        <div class="p-2 bg-green-100 rounded-lg">
                            <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-sm font-medium text-gray-500">応募総数</h3>
                            <p class="text-2xl font-bold text-gray-900"><?= number_format($stats['applications']) ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
                    <div class="flex items-center">
                        <div class="p-2 bg-yellow-100 rounded-lg">
                            <svg class="h-6 w-6 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-sm font-medium text-gray-500">進行中</h3>
                            <p class="text-2xl font-bold text-gray-900"><?= number_format($stats['in_progress']) ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
                    <div class="flex items-center">
                        <div class="p-2 bg-purple-100 rounded-lg">
                            <svg class="h-6 w-6 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-sm font-medium text-gray-500">完了</h3>
                            <p class="text-2xl font-bold text-gray-900"><?= number_format($stats['completed']) ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <?php if ($currentRole === 'creator'): ?>
                <!-- Recent Applications -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="p-6 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">最近の応募</h2>
                    </div>
                    <div class="p-6">
                        <?php if (empty($recentApplications)): ?>
                            <p class="text-gray-500 text-center py-8">まだ応募した案件がありません</p>
                        <?php else: ?>
                            <div class="space-y-4">
                                <?php foreach ($recentApplications as $app): ?>
                                    <div class="border border-gray-200 rounded-lg p-4">
                                        <div class="flex justify-between items-start mb-2">
                                            <h3 class="font-semibold text-gray-900"><?= h($app['job_title']) ?></h3>
                                            <span class="px-2 py-1 text-xs rounded-full 
                                                <?php
                                                switch($app['status']) {
                                                    case 'pending': echo 'bg-yellow-100 text-yellow-800'; break;
                                                    case 'accepted': echo 'bg-green-100 text-green-800'; break;
                                                    case 'rejected': echo 'bg-red-100 text-red-800'; break;
                                                    default: echo 'bg-gray-100 text-gray-800';
                                                }
                                                ?>">
                                                <?php
                                                switch($app['status']) {
                                                    case 'pending': echo '審査中'; break;
                                                    case 'accepted': echo '採用'; break;
                                                    case 'rejected': echo '不採用'; break;
                                                    case 'withdrawn': echo '辞退'; break;
                                                }
                                                ?>
                                            </span>
                                        </div>
                                        <p class="text-sm text-gray-600 mb-2"><?= h($app['client_name']) ?></p>
                                        <p class="text-sm text-gray-900 font-medium">
                                            <?= formatPrice($app['budget_min']) ?> - <?= formatPrice($app['budget_max']) ?>
                                        </p>
                                        <p class="text-xs text-gray-500 mt-2"><?= timeAgo($app['created_at']) ?></p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recommended Jobs -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="p-6 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">おすすめ案件</h2>
                    </div>
                    <div class="p-6">
                        <div class="space-y-4">
                            <?php foreach ($recommendedJobs as $job): ?>
                                <div class="border border-gray-200 rounded-lg p-4 hover:border-blue-300 transition-colors">
                                    <div class="flex justify-between items-start mb-2">
                                        <h3 class="font-semibold text-gray-900">
                                            <a href="<?= url('job-detail.php?id=' . $job['id']) ?>" class="hover:text-blue-600">
                                                <?= h($job['title']) ?>
                                            </a>
                                        </h3>
                                        <span class="text-xs text-gray-500"><?= h($job['category_name']) ?></span>
                                    </div>
                                    <p class="text-sm text-gray-600 mb-2"><?= h($job['client_name']) ?></p>
                                    <p class="text-sm text-gray-900 font-medium">
                                        <?= formatPrice($job['budget_min']) ?> - <?= formatPrice($job['budget_max']) ?>
                                    </p>
                                    <p class="text-xs text-gray-500 mt-2"><?= timeAgo($job['created_at']) ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions for Creator -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="p-6 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">クイックアクション</h2>
                    </div>
                    <div class="p-6">
                        <div class="space-y-4">
                            <a href="<?= url('edit-work.php') ?>" 
                               class="flex items-center p-4 border border-gray-200 rounded-lg hover:border-blue-300 hover:bg-blue-50 transition-colors">
                                <div class="p-2 bg-blue-100 rounded-lg mr-4">
                                    <svg class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-900">新しい作品を投稿</h3>
                                    <p class="text-sm text-gray-600">あなたのポートフォリオに新しい作品を追加します</p>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- Recent Jobs -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="p-6 border-b border-gray-200">
                        <div class="flex justify-between items-center">
                            <h2 class="text-lg font-semibold text-gray-900">最近の案件</h2>
                            <a href="<?= url('post-job.php') ?>" class="text-blue-600 hover:text-blue-500 text-sm font-medium">
                                新規投稿
                            </a>
                        </div>
                    </div>
                    <div class="p-6">
                        <?php if (empty($recentJobs)): ?>
                            <p class="text-gray-500 text-center py-8">まだ案件を投稿していません</p>
                        <?php else: ?>
                            <div class="space-y-4">
                                <?php foreach ($recentJobs as $job): ?>
                                    <div class="border border-gray-200 rounded-lg p-4">
                                        <div class="flex justify-between items-start mb-2">
                                            <h3 class="font-semibold text-gray-900">
                                                <a href="<?= url('job-detail.php?id=' . $job['id']) ?>" class="hover:text-blue-600">
                                                    <?= h($job['title']) ?>
                                                </a>
                                            </h3>
                                            <span class="px-2 py-1 text-xs rounded-full 
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
                                        <p class="text-sm text-gray-600 mb-2"><?= h($job['category_name']) ?></p>
                                        <div class="flex justify-between items-center">
                                            <p class="text-sm text-gray-900 font-medium">
                                                <?= formatPrice($job['budget_min']) ?> - <?= formatPrice($job['budget_max']) ?>
                                            </p>
                                            <p class="text-xs text-gray-500"><?= $job['application_count'] ?>件の応募</p>
                                        </div>
                                        <p class="text-xs text-gray-500 mt-2"><?= timeAgo($job['created_at']) ?></p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="p-6 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">クイックアクション</h2>
                    </div>
                    <div class="p-6">
                        <div class="space-y-4">
                            <a href="<?= url('post-job.php') ?>" 
                               class="flex items-center p-4 border border-gray-200 rounded-lg hover:border-blue-300 hover:bg-blue-50 transition-colors">
                                <div class="p-2 bg-blue-100 rounded-lg mr-4">
                                    <svg class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-900">新しい案件を投稿</h3>
                                    <p class="text-sm text-gray-600">クリエイターに依頼したい案件を投稿</p>
                                </div>
                            </a>

                            <a href="<?= url('creators.php') ?>" 
                               class="flex items-center p-4 border border-gray-200 rounded-lg hover:border-green-300 hover:bg-green-50 transition-colors">
                                <div class="p-2 bg-green-100 rounded-lg mr-4">
                                    <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-900">クリエイターを探す</h3>
                                    <p class="text-sm text-gray-600">スキルや実績からクリエイターを検索</p>
                                </div>
                            </a>

                            <a href="<?= url('works.php') ?>" 
                               class="flex items-center p-4 border border-gray-200 rounded-lg hover:border-purple-300 hover:bg-purple-50 transition-colors">
                                <div class="p-2 bg-purple-100 rounded-lg mr-4">
                                    <svg class="h-6 w-6 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-900">作品を見る</h3>
                                    <p class="text-sm text-gray-600">クリエイターの過去の作品を閲覧</p>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
