<?php
require_once 'config/config.php';

$pageTitle = '案件一覧';
$pageDescription = '豊富な案件から自分にピッタリの仕事を見つけよう';

// データベース接続
$db = Database::getInstance();

// 検索・フィルター条件
$keyword = $_GET['keyword'] ?? '';
$categoryId = $_GET['category_id'] ?? '';
$budgetMin = $_GET['budget_min'] ?? '';
$budgetMax = $_GET['budget_max'] ?? '';
$location = $_GET['location'] ?? '';
$remoteOnly = isset($_GET['remote_only']) ? 1 : 0;
$urgency = $_GET['urgency'] ?? '';
$sortBy = $_GET['sort'] ?? 'newest';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;

// 検索条件構築
$conditions = [];
$values = [];

if ($keyword) {
    $conditions[] = "(j.title LIKE ? OR j.description LIKE ?)";
    $values[] = "%{$keyword}%";
    $values[] = "%{$keyword}%";
}

if ($categoryId) {
    $conditions[] = "j.category_id = ?";
    $values[] = $categoryId;
}

if ($budgetMin) {
    $conditions[] = "j.budget_max >= ?";
    $values[] = $budgetMin;
}

if ($budgetMax) {
    $conditions[] = "j.budget_min <= ?";
    $values[] = $budgetMax;
}

if ($location) {
    $conditions[] = "j.location LIKE ?";
    $values[] = "%{$location}%";
}

if ($remoteOnly) {
    $conditions[] = "j.remote_ok = 1";
}

if ($urgency) {
    $conditions[] = "j.urgency = ?";
    $values[] = $urgency;
}

$whereClause = !empty($conditions) ? 'AND ' . implode(' AND ', $conditions) : '';

// ソート条件
$orderBy = match($sortBy) {
    'budget_high' => 'j.budget_max DESC',
    'budget_low' => 'j.budget_min ASC',
    'deadline' => 'j.deadline ASC',
    'applications' => 'j.applications_count ASC',
    default => 'j.created_at DESC'
};

try {
    // カテゴリ一覧取得
    $categories = $db->select("
        SELECT * FROM categories 
        WHERE is_active = 1 
        ORDER BY sort_order ASC
    ");

    // 総件数取得
    $totalSql = "
        SELECT COUNT(*) as total
        FROM jobs j
        WHERE j.status = 'open'
        {$whereClause}
    ";
    
    $total = $db->selectOne($totalSql, $values)['total'] ?? 0;
    
    // ページネーション計算
    $pagination = calculatePagination($total, $perPage, $page);
    
    // 案件一覧取得
    $jobsSql = "
        SELECT j.*, 
               u.full_name as client_name, 
               u.profile_image as client_image,
               c.name as category_name,
               c.color as category_color
        FROM jobs j
        JOIN users u ON j.client_id = u.id
        LEFT JOIN categories c ON j.category_id = c.id
        WHERE j.status = 'open'
        {$whereClause}
        ORDER BY {$orderBy}
        LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}
    ";
    
    $jobs = $db->select($jobsSql, $values);

} catch (Exception $e) {
    $jobs = [];
    $total = 0;
    $pagination = calculatePagination(0, $perPage, 1);
    setFlash('error', 'データの取得に失敗しました。');
}

include 'includes/header.php';
?>

<!-- Hero Section -->
<section class="bg-gradient-to-r from-purple-600 to-blue-600 text-white py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <h1 class="text-4xl md:text-5xl font-bold mb-6">案件一覧</h1>
            <p class="text-xl text-purple-100 max-w-3xl mx-auto">
                豊富な案件から自分にピッタリの仕事を見つけて、スキルを活かしてください
            </p>
        </div>
    </div>
</section>

<!-- Search & Filter Section -->
<section class="bg-white shadow-sm border-b border-gray-200 sticky top-16 z-40">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <form method="GET" class="space-y-4">
            <!-- Search Bar -->
            <div class="flex flex-col lg:flex-row gap-4">
                <div class="flex-1">
                    <input type="text" 
                           name="keyword" 
                           value="<?= h($keyword) ?>"
                           placeholder="案件名やキーワードで検索..."
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                </div>
                <button type="submit" 
                        class="px-8 py-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors font-medium">
                    検索
                </button>
            </div>

            <!-- Filters -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                <!-- Category Filter -->
                <select name="category_id" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                    <option value="">すべてのカテゴリ</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= h($category['id']) ?>" <?= $categoryId == $category['id'] ? 'selected' : '' ?>>
                            <?= h($category['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <!-- Budget Filter -->
                <select name="budget_min" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                    <option value="">予算下限なし</option>
                    <option value="10000" <?= $budgetMin == '10000' ? 'selected' : '' ?>>1万円以上</option>
                    <option value="50000" <?= $budgetMin == '50000' ? 'selected' : '' ?>>5万円以上</option>
                    <option value="100000" <?= $budgetMin == '100000' ? 'selected' : '' ?>>10万円以上</option>
                    <option value="300000" <?= $budgetMin == '300000' ? 'selected' : '' ?>>30万円以上</option>
                </select>

                <!-- Urgency Filter -->
                <select name="urgency" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                    <option value="">緊急度</option>
                    <option value="low" <?= $urgency == 'low' ? 'selected' : '' ?>>低</option>
                    <option value="medium" <?= $urgency == 'medium' ? 'selected' : '' ?>>中</option>
                    <option value="high" <?= $urgency == 'high' ? 'selected' : '' ?>>高</option>
                </select>

                <!-- Remote Work Filter -->
                <label class="flex items-center px-3 py-2 border border-gray-300 rounded-lg cursor-pointer">
                    <input type="checkbox" 
                           name="remote_only" 
                           value="1" 
                           <?= $remoteOnly ? 'checked' : '' ?>
                           class="mr-2 rounded text-purple-600 focus:ring-purple-500">
                    <span class="text-sm">リモートOK</span>
                </label>

                <!-- Sort -->
                <select name="sort" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                    <option value="newest" <?= $sortBy == 'newest' ? 'selected' : '' ?>>新着順</option>
                    <option value="budget_high" <?= $sortBy == 'budget_high' ? 'selected' : '' ?>>予算が高い順</option>
                    <option value="budget_low" <?= $sortBy == 'budget_low' ? 'selected' : '' ?>>予算が安い順</option>
                    <option value="deadline" <?= $sortBy == 'deadline' ? 'selected' : '' ?>>締切が近い順</option>
                    <option value="applications" <?= $sortBy == 'applications' ? 'selected' : '' ?>>応募が少ない順</option>
                </select>
            </div>
        </form>
    </div>
</section>

<!-- Jobs List -->
<section class="py-12 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Results Info -->
        <div class="flex justify-between items-center mb-8">
            <h2 class="text-2xl font-bold text-gray-900">
                <?= number_format($total) ?>件の案件が見つかりました
            </h2>
            <?php if ($keyword || $categoryId || $budgetMin || $location || $remoteOnly || $urgency): ?>
                <a href="<?= url('jobs.php') ?>" class="text-purple-600 hover:text-purple-700 font-medium">
                    検索条件をクリア
                </a>
            <?php endif; ?>
        </div>

        <?php if (empty($jobs)): ?>
            <!-- No Results -->
            <div class="text-center py-16">
                <div class="w-24 h-24 bg-gray-200 rounded-full mx-auto mb-6 flex items-center justify-center">
                    <svg class="h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">案件が見つかりませんでした</h3>
                <p class="text-gray-600 mb-6">検索条件を変更して再度お試しください。</p>
                <a href="<?= url('jobs.php') ?>" class="inline-flex items-center px-6 py-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                    すべての案件を見る
                </a>
            </div>
        <?php else: ?>
            <!-- Jobs Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-12">
                <?php foreach ($jobs as $job): ?>
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 hover:shadow-md transition-all duration-200 p-6">
                        <!-- Job Header -->
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-2">
                                    <?php if ($job['category_name']): ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" 
                                              style="background-color: <?= h($job['category_color']) ?>20; color: <?= h($job['category_color']) ?>;">
                                            <?= h($job['category_name']) ?>
                                        </span>
                                    <?php endif; ?>
                                    
                                    <?php if ($job['remote_ok']): ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            リモートOK
                                        </span>
                                    <?php endif; ?>

                                    <?php
                                    $urgencyColors = [
                                        'low' => 'bg-gray-100 text-gray-800',
                                        'medium' => 'bg-yellow-100 text-yellow-800',
                                        'high' => 'bg-red-100 text-red-800'
                                    ];
                                    $urgencyLabels = ['low' => '低', 'medium' => '中', 'high' => '高'];
                                    ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $urgencyColors[$job['urgency']] ?? 'bg-gray-100 text-gray-800' ?>">
                                        緊急度: <?= $urgencyLabels[$job['urgency']] ?? '中' ?>
                                    </span>
                                </div>
                                
                                <h3 class="text-lg font-semibold text-gray-900 mb-2 line-clamp-2">
                                    <a href="<?= url('job-detail.php?id=' . $job['id']) ?>" class="hover:text-purple-600 transition-colors">
                                        <?= h($job['title']) ?>
                                    </a>
                                </h3>
                            </div>
                        </div>

                        <!-- Job Description -->
                        <p class="text-gray-600 mb-4 line-clamp-3">
                            <?= h(mb_substr($job['description'], 0, 150)) ?><?= mb_strlen($job['description']) > 150 ? '...' : '' ?>
                        </p>

                        <!-- Job Details -->
                        <div class="space-y-2 mb-4">
                            <div class="flex items-center text-sm text-gray-600">
                                <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                                </svg>
                                <span class="font-semibold text-purple-600">
                                    <?= formatPrice($job['budget_min']) ?> 〜 <?= formatPrice($job['budget_max']) ?>
                                </span>
                            </div>

                            <div class="flex items-center text-sm text-gray-600">
                                <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span><?= h($job['duration_weeks']) ?>週間</span>
                            </div>

                            <?php if ($job['location']): ?>
                                <div class="flex items-center text-sm text-gray-600">
                                    <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <span><?= h($job['location']) ?></span>
                                </div>
                            <?php endif; ?>

                            <?php if ($job['deadline']): ?>
                                <div class="flex items-center text-sm text-gray-600">
                                    <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <span>締切: <?= formatDate($job['deadline']) ?></span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Client Info & Actions -->
                        <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                            <div class="flex items-center">
                                <img src="<?= h($job['client_image'] ?? asset('images/default-avatar.png')) ?>" 
                                     alt="<?= h($job['client_name']) ?>" 
                                     class="w-8 h-8 rounded-full mr-3">
                                <div>
                                    <p class="text-sm font-medium text-gray-900"><?= h($job['client_name']) ?></p>
                                    <p class="text-xs text-gray-500"><?= timeAgo($job['created_at']) ?></p>
                                </div>
                            </div>

                            <div class="flex items-center space-x-3">
                                <span class="text-sm text-gray-500">
                                    応募: <?= number_format($job['applications_count']) ?>件
                                </span>
                                <a href="<?= url('job-detail.php?id=' . $job['id']) ?>" 
                                   class="inline-flex items-center px-4 py-2 bg-purple-600 text-white text-sm font-medium rounded-lg hover:bg-purple-700 transition-colors">
                                    詳細を見る
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($pagination['total_pages'] > 1): ?>
                <div class="flex justify-center">
                    <nav class="flex items-center space-x-1">
                        <?php if ($pagination['has_prev']): ?>
                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $pagination['prev_page']])) ?>" 
                               class="px-3 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded-l-lg hover:bg-gray-50">
                                前へ
                            </a>
                        <?php endif; ?>

                        <?php for ($i = max(1, $pagination['current_page'] - 2); $i <= min($pagination['total_pages'], $pagination['current_page'] + 2); $i++): ?>
                            <?php if ($i == $pagination['current_page']): ?>
                                <span class="px-3 py-2 text-sm text-white bg-purple-600 border border-purple-600">
                                    <?= $i ?>
                                </span>
                            <?php else: ?>
                                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" 
                                   class="px-3 py-2 text-sm text-gray-700 bg-white border border-gray-300 hover:bg-gray-50">
                                    <?= $i ?>
                                </a>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <?php if ($pagination['has_next']): ?>
                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $pagination['next_page']])) ?>" 
                               class="px-3 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded-r-lg hover:bg-gray-50">
                                次へ
                            </a>
                        <?php endif; ?>
                    </nav>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>

<!-- CTA Section -->
<section class="bg-white py-16">
    <div class="max-w-4xl mx-auto text-center px-4 sm:px-6 lg:px-8">
        <h2 class="text-3xl font-bold text-gray-900 mb-6">案件をお探しの企業様へ</h2>
        <p class="text-xl text-gray-600 mb-8">
            優秀なクリエイターとのマッチングをサポートします。<br>
            まずは無料で案件を投稿してみませんか？
        </p>
        <a href="<?= url('post-job.php') ?>" 
           class="inline-flex items-center px-8 py-4 bg-purple-600 text-white text-lg font-semibold rounded-lg hover:bg-purple-700 transition-colors shadow-lg">
            案件を投稿する
            <svg class="ml-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
        </a>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
