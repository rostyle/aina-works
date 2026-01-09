<?php
require_once 'config/config.php';

$pageTitle = 'ダッシュボード';
$pageDescription = 'あなたの活動状況を確認';

// ログインチェック
if (!isLoggedIn()) {
    redirect(url('login'));
}

$user = getCurrentUser();
if (!$user) {
    // ユーザー情報が取得できない場合はログアウト
    session_destroy();
    redirect(url('login'));
}

$currentRole = getCurrentRole();
$availableRoles = getUserRoles();
$db = Database::getInstance();

// 期限切れ案件の自動終了
updateExpiredJobs();

// 統計情報取得 - 両方の情報を取得
$creatorStats = [
    'works' => $db->selectOne("SELECT COUNT(*) as count FROM works WHERE user_id = ?", [$user['id']])['count'] ?? 0,
    'applications' => $db->selectOne("SELECT COUNT(*) as count FROM job_applications WHERE creator_id = ?", [$user['id']])['count'] ?? 0,
    'reviews' => $db->selectOne("SELECT COUNT(*) as count FROM reviews WHERE reviewee_id = ?", [$user['id']])['count'] ?? 0,
    'avg_rating' => $db->selectOne("SELECT AVG(rating) as avg FROM reviews WHERE reviewee_id = ?", [$user['id']])['avg'] ?? 0,
    'total_likes' => $db->selectOne("SELECT SUM(like_count) as total FROM works WHERE user_id = ?", [$user['id']])['total'] ?? 0,
    'favorites_received' => $db->selectOne("SELECT COUNT(*) as count FROM favorites WHERE target_type = 'work' AND target_id IN (SELECT id FROM works WHERE user_id = ?)", [$user['id']])['count'] ?? 0,
    'favorites_made' => $db->selectOne("SELECT COUNT(*) as count FROM favorites WHERE user_id = ?", [$user['id']])['count'] ?? 0,
];

$clientStats = [
    'jobs' => $db->selectOne("SELECT COUNT(*) as count FROM jobs WHERE client_id = ?", [$user['id']])['count'] ?? 0,
    'applications' => $db->selectOne("SELECT COUNT(*) as count FROM job_applications ja JOIN jobs j ON ja.job_id = j.id WHERE j.client_id = ?", [$user['id']])['count'] ?? 0,
    'completed' => $db->selectOne("SELECT COUNT(*) as count FROM jobs WHERE client_id = ? AND status = 'completed'", [$user['id']])['count'] ?? 0,
    'in_progress' => $db->selectOne("SELECT COUNT(*) as count FROM jobs WHERE client_id = ? AND status = 'in_progress'", [$user['id']])['count'] ?? 0,
];

// 最近の応募（クリエイター用）
$recentApplications = $db->select("
    SELECT ja.*, j.title as job_title, j.budget_min, j.budget_max, u.full_name as client_name
    FROM job_applications ja
    JOIN jobs j ON ja.job_id = j.id
    JOIN users u ON j.client_id = u.id
    WHERE ja.creator_id = ?
    ORDER BY ja.created_at DESC
    LIMIT 5
", [$user['id']]);

// 最近の案件（依頼者用）
$recentJobs = $db->select("
    SELECT j.*, c.name as category_name,
           j.applications_count as application_count
    FROM jobs j
    LEFT JOIN categories c ON j.category_id = c.id
    WHERE j.client_id = ?
    ORDER BY j.created_at DESC
    LIMIT 5
", [$user['id']]);

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
            
            <!-- オンボーディングツアーボタン -->
            <div class="mt-4">
                <button onclick="startOnboardingTour()" 
                        class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-blue-500 to-purple-600 text-white text-sm font-medium rounded-lg hover:from-blue-600 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200 shadow-md hover:shadow-lg">
                    <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    ツアーを開始
                </button>
            </div>
        </div>

        <!-- Stats Cards - ダッシュボード統計（SP省スペース: 2列） -->
        <div id="dashboard-stats" class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-4 gap-4 md:gap-6 mb-8">
            <style>
            @media (prefers-color-scheme: dark) {
                .dashboard-card { 
                    background-color: #ffffff !important; 
                    border-color: #e5e7eb !important; 
                }
                .dashboard-card .text-gray-900 { 
                    color: #111827 !important; 
                }
                .dashboard-card .text-gray-500 { 
                    color: #6b7280 !important; 
                }
                .dashboard-card .text-gray-600 { 
                    color: #4b5563 !important; 
                }
                .dashboard-card .border-gray-200 { 
                    border-color: #e5e7eb !important; 
                }
                /* 白背景の要素には白文字を適用しない */
                .dashboard-card .text-white:not(.bg-primary-600):not(.bg-primary-700):not(.bg-primary-800):not(.bg-primary-900):not(.bg-secondary-600):not(.bg-secondary-700):not(.bg-secondary-800):not(.bg-secondary-900) {
                    color: #111827 !important;
                }
            }
            </style>
            <!-- 公開作品 -->
            <div class="bg-white rounded-lg shadow-sm p-4 md:p-6 border border-gray-200 dashboard-card">
                <div class="flex items-center">
                    <div class="p-2 bg-blue-100 rounded-lg">
                        <svg class="h-5 w-5 md:h-6 md:w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-sm font-medium text-gray-500">公開作品</h3>
                        <p class="text-xl md:text-2xl font-bold text-gray-900"><?= number_format($creatorStats['works']) ?></p>
                        <p class="text-xs text-blue-600 mt-1"><?= number_format($creatorStats['total_likes']) ?>いいね</p>
                    </div>
                </div>
            </div>

            <!-- 投稿案件 -->
            <div class="bg-white rounded-lg shadow-sm p-4 md:p-6 border border-gray-200 dashboard-card">
                <div class="flex items-center">
                    <div class="p-2 bg-purple-100 rounded-lg">
                        <svg class="h-5 w-5 md:h-6 md:w-6 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2-2v2m8 0V6a2 2 0 012 2v6a2 2 0 01-2 2H6a2 2 0 01-2-2V8a2 2 0 012-2V6" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-sm font-medium text-gray-500">投稿案件</h3>
                        <p class="text-xl md:text-2xl font-bold text-gray-900"><?= number_format($clientStats['jobs']) ?></p>
                        <p class="text-xs text-purple-600 mt-1"><?= number_format($clientStats['applications']) ?>件の応募</p>
                    </div>
                </div>
            </div>

            <!-- お気に入り -->
            <div class="bg-white rounded-lg shadow-sm p-4 md:p-6 border border-gray-200 dashboard-card">
                <div class="flex items-center">
                    <div class="p-2 bg-red-100 rounded-lg">
                        <svg class="h-5 w-5 md:h-6 md:w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-sm font-medium text-gray-500">お気に入り</h3>
                        <p class="text-xl md:text-2xl font-bold text-gray-900"><?= number_format($creatorStats['favorites_received']) ?></p>
                        <p class="text-xs text-red-600 mt-1">受け取った数</p>
                    </div>
                </div>
            </div>

            <!-- アクティビティ -->
            <div class="bg-white rounded-lg shadow-sm p-4 md:p-6 border border-gray-200 dashboard-card">
                <div class="flex items-center">
                    <div class="p-2 bg-green-100 rounded-lg">
                        <svg class="h-5 w-5 md:h-6 md:w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-sm font-medium text-gray-500">アクティビティ</h3>
                        <p class="text-xl md:text-2xl font-bold text-gray-900"><?= number_format($creatorStats['applications'] + $clientStats['jobs']) ?></p>
                        <p class="text-xs text-green-600 mt-1">総活動数</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- 統合されたダッシュボード - 両方のメニューを表示 -->
        <div id="dashboard-menu-area" class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Recent Applications (for creators) -->
            <?php if (!empty($recentApplications)): ?>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="p-6 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <svg class="h-5 w-5 text-blue-600 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                            </svg>
                            <h2 class="text-lg font-semibold text-gray-900">応募した案件（クリエイターとして）</h2>
                        </div>
                        <a href="<?= url('job-applications') ?>" 
                           class="text-sm text-blue-600 hover:text-blue-500">
                            全て見る
                        </a>
                    </div>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <?php foreach ($recentApplications as $app): ?>
                            <div class="border border-gray-200 rounded-lg p-4">
                                <div class="mb-2">
                                    <h3 class="font-semibold text-gray-900"><?= h($app['job_title']) ?></h3>
                                </div>
                                <p class="text-sm text-gray-600 mb-2"><?= h($app['client_name']) ?></p>
                                <p class="text-sm text-gray-900 font-medium">
                                    <?= formatPrice($app['budget_min']) ?> - <?= formatPrice($app['budget_max']) ?>
                                </p>
                                <p class="text-xs text-gray-500 mt-2"><?= timeAgo($app['created_at']) ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Recent Jobs (for clients) -->
            <?php if (!empty($recentJobs)): ?>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="p-6 border-b border-gray-200">
                    <div class="flex justify-between items-center">
                        <div class="flex items-center">
                            <svg class="h-5 w-5 text-green-600 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2-2v2m8 0V6a2 2 0 012 2v6a2 2 0 01-2 2H6a2 2 0 01-2-2V8a2 2 0 012-2V6z" />
                            </svg>
                            <h2 class="text-lg font-semibold text-gray-900">投稿した案件（依頼者として）</h2>
                        </div>
                        <div class="flex space-x-3">
                            <a href="<?= url('job-applications') ?>" class="text-blue-600 hover:text-blue-500 text-sm font-medium">
                                <svg class="h-4 w-4 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 7.89a2 2 0 002.83 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                                応募管理
                            </a>
                            <a href="<?= url('post-job') ?>" class="text-blue-600 hover:text-blue-500 text-sm font-medium">
                                新規投稿
                            </a>
                        </div>
                    </div>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <?php foreach ($recentJobs as $job): ?>
                            <div class="border border-gray-200 rounded-lg p-4">
                                <div class="flex justify-between items-start mb-2">
                                    <h3 class="font-semibold text-gray-900">
                                        <a href="<?= url('job-detail?id=' . $job['id']) ?>" class="hover:text-blue-600">
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
                                    <p class="text-xs text-gray-500">
                                        <a href="<?= url('job-applications?search_job_id=' . $job['id']) ?>" 
                                           class="text-blue-600 hover:text-blue-500">
                                            <?= $job['application_count'] ?>件の応募
                                        </a>
                                    </p>
                                </div>
                                <p class="text-xs text-gray-500 mt-2"><?= timeAgo($job['created_at']) ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Creator Menu -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">クリエイターメニュー</h2>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <a href="<?= url('edit-work') ?>" 
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
                        
                        <a href="<?= url('works?user=' . $user['id']) ?>" 
                           class="flex items-center p-4 border border-gray-200 rounded-lg hover:border-purple-300 hover:bg-purple-50 transition-colors">
                            <div class="p-2 bg-purple-100 rounded-lg mr-4">
                                <svg class="h-6 w-6 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                </svg>
                            </div>
                            <div class="flex-1">
                                <h3 class="font-semibold text-gray-900">自分の作品を管理</h3>
                                <p class="text-sm text-gray-600">投稿した作品の閲覧数やいいね数を確認</p>
                                <p class="text-xs text-blue-600 mt-1"><?= number_format($creatorStats['works']) ?>件の作品を公開中</p>
                            </div>
                        </a>
                        
                        <a href="<?= url('jobs') ?>" 
                           class="flex items-center p-4 border border-gray-200 rounded-lg hover:border-green-300 hover:bg-green-50 transition-colors">
                            <div class="p-2 bg-green-100 rounded-lg mr-4">
                                <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2-2v2m8 0V6a2 2 0 012 2v6a2 2 0 01-2 2H6a2 2 0 01-2-2V8a2 2 0 012-2V6" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-900">案件を探す</h3>
                                <p class="text-sm text-gray-600">新しい案件に応募して収入を得る</p>
                            </div>
                        </a>
                        
                        <a href="<?= url('favorites?tab=works') ?>" 
                           class="flex items-center p-4 border border-gray-200 rounded-lg hover:border-pink-300 hover:bg-pink-50 transition-colors">
                            <div class="p-2 bg-pink-100 rounded-lg mr-4">
                                <svg class="h-6 w-6 text-pink-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                </svg>
                            </div>
                            <div class="flex-1">
                                <h3 class="font-semibold text-gray-900">お気に入り</h3>
                                <p class="text-sm text-gray-600">気になる作品や案件をチェック</p>
                                <p class="text-xs text-pink-600 mt-1"><?= number_format($creatorStats['favorites_made']) ?>件をお気に入り登録</p>
                            </div>
                        </a>
                        
                        <a href="<?= url('chats') ?>" 
                           class="flex items-center p-4 border border-gray-200 rounded-lg hover:border-orange-300 hover:bg-orange-50 transition-colors">
                            <div class="p-2 bg-orange-100 rounded-lg mr-4">
                                <svg class="h-6 w-6 text-orange-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-3.582 8-8 8a8.955 8.955 0 01-4.126-.98L3 20l1.98-5.874A8.955 8.955 0 013 12c0-4.418 3.582-8 8-8s8 3.582 8 8z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-900">チャット</h3>
                                <p class="text-sm text-gray-600">クライアントとのやり取りを確認</p>
                            </div>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Client Menu -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">依頼者メニュー</h2>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <a href="<?= url('post-job') ?>" 
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

                        <a href="<?= url('jobs?client=' . $user['id']) ?>" 
                           class="flex items-center p-4 border border-gray-200 rounded-lg hover:border-purple-300 hover:bg-purple-50 transition-colors">
                            <div class="p-2 bg-purple-100 rounded-lg mr-4">
                                <svg class="h-6 w-6 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2-2v2m8 0V6a2 2 0 012 2v6a2 2 0 01-2 2H6a2 2 0 01-2-2V8a2 2 0 012-2V6" />
                                </svg>
                            </div>
                            <div class="flex-1">
                                <h3 class="font-semibold text-gray-900">自分の案件を管理</h3>
                                <p class="text-sm text-gray-600">投稿した案件の応募状況や進捗を確認</p>
                                <p class="text-xs text-blue-600 mt-1"><?= number_format($clientStats['jobs']) ?>件の案件を投稿済み</p>
                            </div>
                        </a>

                        <a href="<?= url('creators') ?>" 
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

                        <a href="<?= url('works') ?>" 
                        <a href="<?= url('works') ?>" 
                           class="flex items-center p-4 border border-gray-200 rounded-lg hover:border-indigo-300 hover:bg-indigo-50 transition-colors">
                            <div class="p-2 bg-indigo-100 rounded-lg mr-4">
                                <svg class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-900">作品を見る</h3>
                                <p class="text-sm text-gray-600">クリエイターの過去の作品を閲覧</p>
                            </div>
                        </a>
                        
                        <a href="<?= url('favorites?tab=creators') ?>" 
                           class="flex items-center p-4 border border-gray-200 rounded-lg hover:border-pink-300 hover:bg-pink-50 transition-colors">
                            <div class="p-2 bg-pink-100 rounded-lg mr-4">
                                <svg class="h-6 w-6 text-pink-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                </svg>
                            </div>
                            <div class="flex-1">
                                <h3 class="font-semibold text-gray-900">お気に入りクリエイター</h3>
                                <p class="text-sm text-gray-600">気になるクリエイターをブックマーク</p>
                                <p class="text-xs text-pink-600 mt-1"><?= number_format($creatorStats['favorites_made']) ?>件をお気に入り登録</p>
                            </div>
                        </a>
                        
                        <a href="<?= url('chats') ?>" 
                           class="flex items-center p-4 border border-gray-200 rounded-lg hover:border-orange-300 hover:bg-orange-50 transition-colors">
                            <div class="p-2 bg-orange-100 rounded-lg mr-4">
                                <svg class="h-6 w-6 text-orange-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-3.582 8-8 8a8.955 8.955 0 01-4.126-.98L3 20l1.98-5.874A8.955 8.955 0 013 12c0-4.418 3.582-8 8-8s8 3.582 8 8z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-900">チャット</h3>
                                <p class="text-sm text-gray-600">クリエイターとのやり取りを確認</p>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>