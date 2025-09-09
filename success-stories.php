<?php
require_once 'config/config.php';

$pageTitle = '成功事例';
$pageDescription = 'AiNA Worksで実現した素晴らしいプロジェクトの成功事例をご紹介';

// データベース接続
$db = Database::getInstance();

// 検索・フィルター条件
$categoryId = $_GET['category_id'] ?? '';
$sortBy = $_GET['sort'] ?? 'newest';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 9;

// 検索条件構築
$conditions = [];
$values = [];

if ($categoryId) {
    $conditions[] = "w.category_id = ?";
    $values[] = $categoryId;
}

$whereClause = !empty($conditions) ? 'AND ' . implode(' AND ', $conditions) : '';

// ソート条件
$orderBy = match($sortBy) {
    'popular' => 'w.view_count DESC',
    'rating' => 'avg_rating DESC',
    'price_high' => 'w.price_max DESC',
    'price_low' => 'w.price_min ASC',
    default => 'w.created_at DESC'
};

try {
    // カテゴリ一覧取得
    $categories = $db->select("
        SELECT * FROM categories 
        WHERE is_active = 1 
        ORDER BY sort_order ASC
    ");

    // 統計情報取得
    $stats = [
        'total_projects' => $db->selectOne("SELECT COUNT(*) as count FROM jobs WHERE status = 'completed'")['count'] ?? 0,
        'total_creators' => $db->selectOne("SELECT COUNT(*) as count FROM users WHERE is_creator = 1 AND is_active = 1")['count'] ?? 0,
        'avg_satisfaction' => 4.8, // 仮の数値
        'total_budget' => $db->selectOne("SELECT SUM(budget_max) as total FROM jobs WHERE status = 'completed'")['total'] ?? 0,
    ];

    // 総件数取得（成功事例として完了した案件に関連する作品）
    $totalSql = "
        SELECT COUNT(DISTINCT w.id) as total
        FROM works w
        JOIN users u ON w.user_id = u.id
        LEFT JOIN categories c ON w.category_id = c.id
        WHERE w.status = 'published' AND w.is_featured = 1
        {$whereClause}
    ";
    
    $total = $db->selectOne($totalSql, $values)['total'] ?? 0;
    
    // ページネーション計算
    $pagination = calculatePagination($total, $perPage, $page);
    
    // 成功事例取得（フィーチャーされた作品を成功事例として表示）
    $storiesSql = "
        SELECT w.*, 
               u.full_name as creator_name, 
               u.profile_image as creator_image,
               u.location as creator_location,
               c.name as category_name,
               c.color as category_color,
               AVG(r.rating) as avg_rating,
               COUNT(DISTINCT r.id) as review_count
        FROM works w
        JOIN users u ON w.user_id = u.id
        LEFT JOIN categories c ON w.category_id = c.id
        LEFT JOIN reviews r ON w.id = r.work_id
        WHERE w.status = 'published' AND w.is_featured = 1
        {$whereClause}
        GROUP BY w.id
        ORDER BY {$orderBy}
        LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}
    ";
    
    $stories = $db->select($storiesSql, $values);

    // 実際のプロジェクトデータを使用（ダミーデータは削除）

} catch (Exception $e) {
    $stories = [];
    $total = 0;
    $pagination = calculatePagination(0, $perPage, 1);
    setFlash('error', 'データの取得に失敗しました。');
}

include 'includes/header.php';
?>

<!-- Hero Section -->
<section class="bg-gradient-to-r from-green-600 to-blue-600 text-white py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <h1 class="text-4xl md:text-5xl font-bold mb-6">成功事例</h1>
            <p class="text-xl text-green-100 max-w-3xl mx-auto mb-8">
                AiNA Worksで実現した素晴らしいプロジェクトの成功事例をご紹介します
            </p>
            
            <!-- Stats -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6 max-w-4xl mx-auto">
                <div class="text-center">
                    <div class="text-3xl md:text-4xl font-bold mb-2"><?= number_format($stats['total_projects']) ?>+</div>
                    <div class="text-green-100">完了プロジェクト</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl md:text-4xl font-bold mb-2"><?= number_format($stats['total_creators']) ?>+</div>
                    <div class="text-green-100">登録クリエイター</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl md:text-4xl font-bold mb-2"><?= $stats['avg_satisfaction'] ?></div>
                    <div class="text-green-100">平均満足度</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl md:text-4xl font-bold mb-2"><?= formatPrice($stats['total_budget']) ?></div>
                    <div class="text-green-100">総取引額</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Filter Section -->
<section class="bg-white shadow-sm border-b border-gray-200 sticky top-16 z-40">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <form method="GET" class="flex flex-col md:flex-row gap-4 items-center">
            <!-- Category Filter -->
            <select name="category_id" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                <option value="">すべてのカテゴリ</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= h($category['id']) ?>" <?= $categoryId == $category['id'] ? 'selected' : '' ?>>
                        <?= h($category['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <!-- Sort -->
            <select name="sort" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                <option value="newest" <?= $sortBy == 'newest' ? 'selected' : '' ?>>新着順</option>
                <option value="popular" <?= $sortBy == 'popular' ? 'selected' : '' ?>>人気順</option>
                <option value="rating" <?= $sortBy == 'rating' ? 'selected' : '' ?>>評価が高い順</option>
                <option value="price_high" <?= $sortBy == 'price_high' ? 'selected' : '' ?>>予算が高い順</option>
                <option value="price_low" <?= $sortBy == 'price_low' ? 'selected' : '' ?>>予算が安い順</option>
            </select>

            <button type="submit" 
                    class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-medium">
                フィルター適用
            </button>

            <?php if ($categoryId || $sortBy !== 'newest'): ?>
                <a href="<?= url('success-stories') ?>" class="text-green-600 hover:text-green-700 font-medium">
                    リセット
                </a>
            <?php endif; ?>
        </form>
    </div>
</section>

<!-- Success Stories -->
<section class="py-12 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Results Info -->
        <div class="flex justify-between items-center mb-8">
            <h2 class="text-2xl font-bold text-gray-900">
                <?= number_format($total) ?>件の成功事例
            </h2>
        </div>

        <?php if (empty($stories)): ?>
            <!-- No Results -->
            <div class="text-center py-16">
                <div class="w-24 h-24 bg-gray-200 rounded-full mx-auto mb-6 flex items-center justify-center">
                    <svg class="h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">成功事例が見つかりませんでした</h3>
                <p class="text-gray-600 mb-6">条件を変更して再度お試しください。</p>
                <a href="<?= url('success-stories') ?>" class="inline-flex items-center px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                    すべての事例を見る
                </a>
            </div>
        <?php else: ?>
            <!-- Stories Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 mb-12">
                <?php foreach ($stories as $story): ?>
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-lg transition-all duration-200">
                        <!-- Image -->
                        <div class="aspect-w-16 aspect-h-9">
                            <img src="<?= uploaded_asset($story['main_image'] ?? 'assets/images/default-avatar.png') ?>" 
                                 alt="<?= h($story['title']) ?>" 
                                 class="w-full h-48 object-cover">
                        </div>

                        <div class="p-6">
                            <!-- Category & Status -->
                            <div class="flex items-center gap-2 mb-3">
                                <?php if ($story['category_name']): ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" 
                                          style="background-color: <?= h($story['category_color']) ?>20; color: <?= h($story['category_color']) ?>;">
                                        <?= h($story['category_name']) ?>
                                    </span>
                                <?php endif; ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    完了済み
                                </span>
                            </div>

                            <!-- Title -->
                            <h3 class="text-lg font-semibold text-gray-900 mb-2 line-clamp-2">
                                <a href="<?= url('work-detail?id=' . $story['id']) ?>" class="hover:text-green-600 transition-colors">
                                    <?= h($story['title']) ?>
                                </a>
                            </h3>

                            <!-- Description -->
                            <?php if ($story['description']): ?>
                            <p class="text-gray-600 text-sm mb-4 line-clamp-2">
                                <?= h($story['description']) ?>
                            </p>
                            <?php endif; ?>

                            <!-- Project Stats -->
                            <div class="space-y-2 mb-4">
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-600">予算:</span>
                                    <span class="font-medium text-green-600">
                                        <?= formatPrice($story['price_min']) ?> 〜 <?= formatPrice($story['price_max']) ?>
                                    </span>
                                </div>
                                <?php if ($story['avg_rating']): ?>
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-600">評価:</span>
                                    <div class="flex items-center">
                                        <?= renderStars($story['avg_rating']) ?>
                                        <span class="ml-1 text-xs text-gray-500">
                                            (<?= number_format($story['avg_rating'] ?? 0, 1) ?>)
                                        </span>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>

                            <!-- Client & Creator Info -->
                            <div class="border-t border-gray-200 pt-4">
                                <div class="flex items-center justify-between">
                                    <!-- Creator -->
                                    <div class="flex items-center">
                                        <img src="<?= uploaded_asset($story['creator_image'] ?? 'assets/images/default-avatar.png') ?>" 
                                             alt="<?= h($story['creator_name']) ?>" 
                                             class="w-8 h-8 rounded-full mr-2">
                                        <div>
                                            <p class="text-sm font-medium text-gray-900"><?= h($story['creator_name']) ?></p>
                                            <p class="text-xs text-gray-500"><?= h($story['creator_location']) ?></p>
                                        </div>
                                    </div>

                                    <!-- View Details -->
                                    <a href="<?= url('work-detail?id=' . $story['id']) ?>" 
                                    <a href="<?= url('work-detail?id=' . $story['id']) ?>" 
                                       class="text-green-600 hover:text-green-700 text-sm font-medium">
                                        詳細を見る →
                                    </a>
                                </div>

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
                                <span class="px-3 py-2 text-sm text-white bg-green-600 border border-green-600">
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

<!-- Featured Testimonials -->
<section class="bg-white py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-gray-900 mb-4">お客様の声</h2>
            <p class="text-lg text-gray-600">AiNA Worksをご利用いただいたお客様からの嬉しいお声をご紹介</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <!-- Testimonial 1 -->
            <div class="bg-gray-50 rounded-xl p-6">
                <div class="flex items-center mb-4">
                    <?= renderStars(5) ?>
                </div>
                <p class="text-gray-700 mb-4">
                    「期待以上のロゴデザインを短期間で制作していただきました。コミュニケーションも丁寧で、安心して依頼できました。」
                </p>
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center mr-3">
                        <span class="text-white font-semibold text-sm">A</span>
                    </div>
                    <div>
                        <p class="font-medium text-gray-900">A社 マーケティング部</p>
                        <p class="text-sm text-gray-500">ロゴ制作プロジェクト</p>
                    </div>
                </div>
            </div>

            <!-- Testimonial 2 -->
            <div class="bg-gray-50 rounded-xl p-6">
                <div class="flex items-center mb-4">
                    <?= renderStars(5) ?>
                </div>
                <p class="text-gray-700 mb-4">
                    「AI漫画の制作をお願いしました。技術力が高く、要望通りの素晴らしい作品に仕上げていただきました。」
                </p>
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-purple-500 rounded-full flex items-center justify-center mr-3">
                        <span class="text-white font-semibold text-sm">B</span>
                    </div>
                    <div>
                        <p class="font-medium text-gray-900">B出版 編集部</p>
                        <p class="text-sm text-gray-500">AI漫画制作プロジェクト</p>
                    </div>
                </div>
            </div>

            <!-- Testimonial 3 -->
            <div class="bg-gray-50 rounded-xl p-6">
                <div class="flex items-center mb-4">
                    <?= renderStars(4) ?>
                </div>
                <p class="text-gray-700 mb-4">
                    「Webサイトのリニューアルを依頼しました。デザインセンスが良く、レスポンシブ対応も完璧でした。」
                </p>
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center mr-3">
                        <span class="text-white font-semibold text-sm">C</span>
                    </div>
                    <div>
                        <p class="font-medium text-gray-900">C株式会社 代表取締役</p>
                        <p class="text-sm text-gray-500">Web制作プロジェクト</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="bg-gradient-to-r from-green-600 to-blue-600 text-white py-16">
    <div class="max-w-4xl mx-auto text-center px-4 sm:px-6 lg:px-8">
        <h2 class="text-3xl font-bold mb-6">あなたも成功事例の一部になりませんか？</h2>
        <p class="text-xl text-green-100 mb-8">
            優秀なクリエイターとのマッチングで、<br>
            あなたのプロジェクトを成功に導きます
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="<?= url('post-job') ?>" 
               class="inline-flex items-center px-8 py-4 bg-white text-green-600 text-lg font-semibold rounded-lg hover:bg-gray-50 transition-colors shadow-lg">
                案件を投稿する
                <svg class="ml-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </a>
            <a href="<?= url('creators') ?>" 
               class="inline-flex items-center px-8 py-4 border-2 border-white text-white text-lg font-semibold rounded-lg hover:bg-white hover:text-green-600 transition-colors">
                クリエイターを探す
                <svg class="ml-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </a>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
