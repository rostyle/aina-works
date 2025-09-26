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
$urgency = $_GET['urgency'] ?? '';
$status = $_GET['status'] ?? '';
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


if ($urgency) {
    $conditions[] = "j.urgency = ?";
    $values[] = $urgency;
}

if ($status) {
    $conditions[] = "j.status = ?";
    $values[] = $status;
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

    // 総件数取得（全ステータス対象）
    $totalSql = "
        SELECT COUNT(*) as total
        FROM jobs j
        WHERE 1=1
        {$whereClause}
    ";
    
    $total = $db->selectOne($totalSql, $values)['total'] ?? 0;
    
    // ページネーション計算
    $pagination = calculatePagination($total, $perPage, $page);
    
    // 案件一覧取得（全ステータス）
    $jobsSql = "
        SELECT j.*, 
               u.full_name as client_name, 
               u.profile_image as client_image,
               c.name as category_name,
               c.color as category_color
        FROM jobs j
        JOIN users u ON j.client_id = u.id
        LEFT JOIN categories c ON j.category_id = c.id
        WHERE 1=1
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
            <div class="hidden lg:grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4">
                <!-- Category Filter -->
                <select name="category_id" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                    <option value="">すべてのカテゴリ</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= h($category['id']) ?>" <?= $categoryId == $category['id'] ? 'selected' : '' ?>>
                            <?= h($category['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <!-- Status Filter -->
                <select name="status" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                    <option value="">すべてのステータス</option>
                    <option value="open" <?= $status == 'open' ? 'selected' : '' ?>>
                        🟢 募集中
                    </option>
                    <option value="closed" <?= $status == 'closed' ? 'selected' : '' ?>>
                        🟡 募集終了
                    </option>
                    <option value="contracted" <?= $status == 'contracted' ? 'selected' : '' ?>>
                        🔵 契約済み
                    </option>
                    <option value="delivered" <?= $status == 'delivered' ? 'selected' : '' ?>>
                        🟣 納品済み
                    </option>
                    <option value="approved" <?= $status == 'approved' ? 'selected' : '' ?>>
                        🟦 検収済み
                    </option>
                    <option value="cancelled" <?= $status == 'cancelled' ? 'selected' : '' ?>>
                        🔴 キャンセル
                    </option>
                    <option value="in_progress" <?= $status == 'in_progress' ? 'selected' : '' ?>>
                        🔵 進行中
                    </option>
                    <option value="completed" <?= $status == 'completed' ? 'selected' : '' ?>>
                        ⚫ 完了
                    </option>
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

                

                <!-- Sort -->
                <select name="sort" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                    <option value="newest" <?= $sortBy == 'newest' ? 'selected' : '' ?>>新着順</option>
                    <option value="budget_high" <?= $sortBy == 'budget_high' ? 'selected' : '' ?>>予算が高い順</option>
                    <option value="budget_low" <?= $sortBy == 'budget_low' ? 'selected' : '' ?>>予算が安い順</option>
                    <option value="deadline" <?= $sortBy == 'deadline' ? 'selected' : '' ?>>締切が近い順</option>
                    <option value="applications" <?= $sortBy == 'applications' ? 'selected' : '' ?>>応募が少ない順</option>
                </select>
            </div>

            <!-- Mobile Accordion Filters -->
            <div class="lg:hidden">
                <details class="bg-white rounded-lg border border-gray-200">
                    <summary class="px-4 py-3 cursor-pointer font-medium text-gray-900 flex items-center justify-between">
                        フィルターを表示
                        <svg class="w-5 h-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </summary>
                    <div class="p-4 border-t border-gray-200">
                        <div class="grid grid-cols-2 gap-3">
                            <select name="category_id" class="px-2 py-2 text-sm border border-gray-300 rounded-md bg-white text-gray-900">
                                <option value="">カテゴリ</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= h($category['id']) ?>" <?= $categoryId == $category['id'] ? 'selected' : '' ?>>
                                        <?= h($category['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <select name="status" class="px-2 py-2 text-sm border border-gray-300 rounded-md bg-white text-gray-900">
                                <option value="">ステータス</option>
                                <option value="open" <?= $status == 'open' ? 'selected' : '' ?>>募集中</option>
                                <option value="in_progress" <?= $status == 'in_progress' ? 'selected' : '' ?>>進行中</option>
                                <option value="completed" <?= $status == 'completed' ? 'selected' : '' ?>>完了</option>
                                <option value="closed" <?= $status == 'closed' ? 'selected' : '' ?>>募集終了</option>
                            </select>
                            <select name="budget_min" class="px-2 py-2 text-sm border border-gray-300 rounded-md bg-white text-gray-900">
                                <option value="">予算下限</option>
                                <option value="10000" <?= $budgetMin == '10000' ? 'selected' : '' ?>>1万円〜</option>
                                <option value="50000" <?= $budgetMin == '50000' ? 'selected' : '' ?>>5万円〜</option>
                                <option value="100000" <?= $budgetMin == '100000' ? 'selected' : '' ?>>10万円〜</option>
                                <option value="300000" <?= $budgetMin == '300000' ? 'selected' : '' ?>>30万円〜</option>
                            </select>
                            <select name="urgency" class="px-2 py-2 text-sm border border-gray-300 rounded-md bg-white text-gray-900">
                                <option value="">緊急度</option>
                                <option value="low" <?= $urgency == 'low' ? 'selected' : '' ?>>低</option>
                                <option value="medium" <?= $urgency == 'medium' ? 'selected' : '' ?>>中</option>
                                <option value="high" <?= $urgency == 'high' ? 'selected' : '' ?>>高</option>
                            </select>
                            <select name="sort" class="px-2 py-2 text-sm border border-gray-300 rounded-md bg-white text-gray-900 col-span-2">
                                <option value="newest" <?= $sortBy == 'newest' ? 'selected' : '' ?>>新着順</option>
                                <option value="budget_high" <?= $sortBy == 'budget_high' ? 'selected' : '' ?>>予算が高い順</option>
                                <option value="budget_low" <?= $sortBy == 'budget_low' ? 'selected' : '' ?>>予算が安い順</option>
                                <option value="deadline" <?= $sortBy == 'deadline' ? 'selected' : '' ?>>締切が近い順</option>
                                <option value="applications" <?= $sortBy == 'applications' ? 'selected' : '' ?>>応募が少ない順</option>
                            </select>
                        </div>
                        <div class="flex gap-2 mt-4">
                            <button type="submit" class="flex-1 px-4 py-2 bg-purple-600 text-white text-sm font-medium rounded-md hover:bg-purple-700 transition-colors">適用</button>
                            <a href="<?= url('jobs') ?>" class="flex-1 px-4 py-2 bg-gray-100 text-gray-800 text-center text-sm font-medium rounded-md border border-gray-300 hover:bg-gray-200 transition-colors">クリア</a>
                        </div>
                    </div>
                </details>
            </div>
        </form>
    </div>
</section>

<!-- Jobs List -->
<section class="py-12 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Results Info -->
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-900">
                <?= number_format($total) ?>件の案件が見つかりました
            </h2>
            <?php if ($keyword || $categoryId || $budgetMin || $location || $urgency || $status): ?>
                <a href="<?= url('jobs') ?>" class="text-purple-600 hover:text-purple-700 font-medium">
                    検索条件をクリア
                </a>
            <?php endif; ?>
        </div>
        
        <!-- Active Filters Display -->
        <?php if ($keyword || $categoryId || $budgetMin || $location || $urgency || $status): ?>
            <div class="mb-6 p-4 bg-gray-50 border border-gray-200 rounded-lg">
                <h3 class="text-sm font-medium text-gray-900 mb-3">適用中のフィルター:</h3>
                <div class="flex flex-wrap gap-2">
                    <?php if ($keyword): ?>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800 border border-purple-200">
                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"></path></svg>
                            キーワード: "<?= h($keyword) ?>"
                        </span>
                    <?php endif; ?>
                    
                    <?php if ($categoryId): 
                        $selectedCategory = array_filter($categories, fn($cat) => $cat['id'] == $categoryId);
                        $categoryName = !empty($selectedCategory) ? array_values($selectedCategory)[0]['name'] : 'カテゴリ';
                    ?>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 border border-blue-200">
                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path d="M7 3a1 1 0 000 2h6a1 1 0 100-2H7zM4 7a1 1 0 011-1h10a1 1 0 110 2H5a1 1 0 01-1-1zM2 11a2 2 0 012-2h12a2 2 0 012 2v4a2 2 0 01-2 2H4a2 2 0 01-2-2v-4z"></path></svg>
                            カテゴリ: <?= h($categoryName) ?>
                        </span>
                    <?php endif; ?>
                    
                    <?php if ($status): 
                        $statusLabels = [
                            'open' => '🟢 募集中',
                            'closed' => '🟡 募集終了', 
                            'contracted' => '🔵 契約済み',
                            'delivered' => '🟣 納品済み',
                            'approved' => '🟦 検収済み',
                            'cancelled' => '🔴 キャンセル',
                            'in_progress' => '🔵 進行中',
                            'completed' => '⚫ 完了'
                        ];
                        $statusLabel = $statusLabels[$status] ?? $status;
                    ?>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 border border-green-200">
                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                            ステータス: <?= $statusLabel ?>
                        </span>
                    <?php endif; ?>
                    
                    <?php if ($budgetMin): ?>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 border border-yellow-200">
                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"></path><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"></path></svg>
                            予算: <?= number_format($budgetMin) ?>円以上
                        </span>
                    <?php endif; ?>
                    
                    <?php if ($urgency): 
                        $urgencyLabels = ['low' => '低', 'medium' => '中', 'high' => '高'];
                        $urgencyLabel = $urgencyLabels[$urgency] ?? $urgency;
                    ?>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800 border border-orange-200">
                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                            緊急度: <?= $urgencyLabel ?>
                        </span>
                    <?php endif; ?>
                    
                    <?php if ($location): ?>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800 border border-indigo-200">
                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path></svg>
                            場所: "<?= h($location) ?>"
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

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
                <a href="<?= url('jobs') ?>" class="inline-flex items-center px-6 py-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
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
                                    <a href="<?= url('job-detail?id=' . $job['id']) ?>" class="hover:text-purple-600 transition-colors">
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
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 1 1 -18 0 9 9 0 0 1 18 0z" />
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
                                <img src="<?= uploaded_asset($job['client_image'] ?? 'assets/images/default-avatar.png') ?>" 
                                     alt="<?= h($job['client_name']) ?>" 
                                     class="w-8 h-8 rounded-full mr-3">
                                <div>
                                    <p class="text-sm font-medium text-gray-900"><?= h($job['client_name']) ?></p>
                                    <p class="text-xs text-gray-500"><?= timeAgo($job['created_at']) ?></p>
                                </div>
                            </div>

                            <div class="flex items-center space-x-3">
                                <?php
                                // ステータス表示の詳細設定
                                $statusConfig = [
                                    'open' => [
                                        'class' => 'bg-green-100 text-green-800 border border-green-200',
                                        'label' => '募集中',
                                        'icon' => '<svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>',
                                        'description' => '応募受付中'
                                    ],
                                    'closed' => [
                                        'class' => 'bg-yellow-100 text-yellow-800 border border-yellow-200',
                                        'label' => '募集終了',
                                        'icon' => '<svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>',
                                        'description' => '応募締切済み'
                                    ],
                                    'contracted' => [
                                        'class' => 'bg-blue-100 text-blue-800 border border-blue-200',
                                        'label' => '契約済み',
                                        'icon' => '<svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path d="M9 12l2 2 4-4m6 2a9 9 0 1 1 -18 0 9 9 0 0 1 18 0z"></path></svg>',
                                        'description' => '作業開始'
                                    ],
                                    'delivered' => [
                                        'class' => 'bg-purple-100 text-purple-800 border border-purple-200',
                                        'label' => '納品済み',
                                        'icon' => '<svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 001 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"></path></svg>',
                                        'description' => '完了実績'
                                    ],
                                    'approved' => [
                                        'class' => 'bg-indigo-100 text-indigo-800 border border-indigo-200',
                                        'label' => '検収済み',
                                        'icon' => '<svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm-1.293-5.293l-2-2a1 1 0 10-1.414 1.414l2.707 2.707a1 1 0 001.414 0l5.707-5.707a1 1 0 10-1.414-1.414l-5 5z" clip-rule="evenodd"></path></svg>',
                                        'description' => '検収完了'
                                    ],
                                    'cancelled' => [
                                        'class' => 'bg-red-100 text-red-800 border border-red-200',
                                        'label' => 'キャンセル',
                                        'icon' => '<svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>',
                                        'description' => '中止'
                                    ],
                                    // 互換性のため
                                    'in_progress' => [
                                        'class' => 'bg-blue-100 text-blue-800 border border-blue-200',
                                        'label' => '進行中',
                                        'icon' => '<svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path></svg>',
                                        'description' => '作業中'
                                    ],
                                    'completed' => [
                                        'class' => 'bg-gray-100 text-gray-800 border border-gray-200',
                                        'label' => '完了',
                                        'icon' => '<svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>',
                                        'description' => '完了済み'
                                    ]
                                ];
                                
                                $currentStatus = $statusConfig[$job['status']] ?? $statusConfig['open'];
                                ?>
                                
                                <div class="flex flex-col items-end space-y-1">
                                    <div class="flex items-center">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium <?= $currentStatus['class'] ?>">
                                            <?= $currentStatus['icon'] ?>
                                            <?= $currentStatus['label'] ?>
                                        </span>
                                        
                                        <?php
                                        // 募集中だが停止中表示（is_recruiting=0）
                                        if ($job['status'] === 'open' && array_key_exists('is_recruiting', $job) && (int)$job['is_recruiting'] === 0): ?>
                                            <span class="ml-2 px-2 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800 border border-orange-200">
                                                <svg class="w-3 h-3 mr-1 inline" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                                                停止中
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="flex items-center space-x-3 text-xs text-gray-500">
                                        <span class="flex items-center">
                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"></path></svg>
                                            応募 <?= number_format($job['applications_count']) ?>件
                                        </span>
                                        <span class="text-gray-400">•</span>
                                        <span title="<?= $currentStatus['description'] ?>"><?= $currentStatus['description'] ?></span>
                                    </div>
                                </div>
                                <a href="<?= url('job-detail?id=' . $job['id']) ?>" 
                                   class="inline-flex items-center px-4 py-2 bg-purple-600 text-white text-sm font-medium rounded-lg hover:bg-purple-700 transition-colors">
                                    詳細を見る
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Achievements: 完了のみ（一覧に納品済みを含めるため重複回避） -->
            <?php
            try {
                $achievements = $db->select("
                    SELECT j.*, u.full_name as client_name, u.profile_image as client_image, c.name as category_name, c.color as category_color
                    FROM jobs j
                    JOIN users u ON j.client_id = u.id
                    LEFT JOIN categories c ON j.category_id = c.id
                    WHERE j.status IN ('completed', 'delivered', 'approved')
                    ORDER BY j.updated_at DESC, j.created_at DESC
                    LIMIT 8
                ");
            } catch (Exception $e) {
                $achievements = [];
            }
            ?>
            <?php if (!empty($achievements)): ?>
            <div class="mt-16">
                <h3 class="text-xl font-bold text-gray-900 mb-6">最近の実績（納品済み/完了）</h3>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <?php foreach ($achievements as $job): ?>
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <div class="flex items-start justify-between mb-3">
                            <h4 class="text-lg font-semibold text-gray-900 line-clamp-1">
                                <a href="<?= url('job-detail?id=' . $job['id']) ?>" class="hover:text-purple-600 transition-colors">
                                    <?= h($job['title']) ?>
                                </a>
                            </h4>
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">納品済み</span>
                        </div>
                        <p class="text-gray-600 line-clamp-2 mb-3"><?= h(mb_substr($job['description'], 0, 90)) ?><?= mb_strlen($job['description']) > 90 ? '...' : '' ?></p>
                        <div class="flex items-center text-sm text-gray-600">
                            <img src="<?= uploaded_asset($job['client_image'] ?? 'assets/images/default-avatar.png') ?>" class="w-6 h-6 rounded-full mr-2" alt="<?= h($job['client_name']) ?>">
                            <span><?= h($job['client_name']) ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

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
        <a href="<?= url('post-job') ?>" 
           class="inline-flex items-center px-8 py-4 bg-purple-600 text-white text-lg font-semibold rounded-lg hover:bg-purple-700 transition-colors shadow-lg">
            案件を投稿する
            <svg class="ml-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
        </a>
    </div>
</section>

<?php include 'includes/footer.php'; ?>