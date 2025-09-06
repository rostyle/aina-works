<?php
require_once 'config/config.php';

$pageTitle = 'クリエイター一覧';
$pageDescription = 'AIスクール生の優秀なクリエイターをご紹介';

// データベース接続
$db = Database::getInstance();

// 検索・フィルター条件
$keyword = $_GET['keyword'] ?? '';
$categoryId = $_GET['category_id'] ?? '';
$location = $_GET['location'] ?? '';
$sortBy = $_GET['sort'] ?? 'recommended';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;

// 検索条件構築
$conditions = [];
$values = [];

if ($keyword) {
    $conditions[] = "(u.full_name LIKE ? OR u.bio LIKE ?)";
    $values[] = "%{$keyword}%";
    $values[] = "%{$keyword}%";
}

if ($categoryId) {
    $conditions[] = "EXISTS (
        SELECT 1 FROM user_skills us 
        JOIN skills s ON us.skill_id = s.id 
        WHERE us.user_id = u.id AND s.category_id = ?
    )";
    $values[] = $categoryId;
}

if ($location) {
    $conditions[] = "u.location LIKE ?";
    $values[] = "%{$location}%";
}

$whereClause = !empty($conditions) ? 'AND ' . implode(' AND ', $conditions) : '';

// ソート条件
$orderBy = match($sortBy) {
    'newest' => 'u.created_at DESC',
    'rating' => 'avg_rating DESC',
    'experience' => 'u.experience_years DESC',
    'price_low' => 'u.hourly_rate ASC',
    'price_high' => 'u.hourly_rate DESC',
    default => 'u.is_pro DESC, avg_rating DESC, u.created_at DESC'
};

try {
    // 総件数取得
    $totalSql = "
        SELECT COUNT(DISTINCT u.id) as total
        FROM users u
        WHERE u.user_type = 'creator' AND u.is_active = 1
        {$whereClause}
    ";
    
    $total = $db->selectOne($totalSql, $values)['total'] ?? 0;
    
    // ページネーション計算
    $pagination = calculatePagination($total, $perPage, $page);
    
    // クリエイター一覧取得
    $creatorsSql = "
        SELECT u.*, 
               COALESCE(r_stats.avg_rating, 0) as avg_rating, 
               COALESCE(r_stats.review_count, 0) as review_count,
               COALESCE(w_stats.work_count, 0) as work_count,
               COALESCE(ja_stats.completed_jobs, 0) as completed_jobs
        FROM users u
        LEFT JOIN (
            SELECT reviewee_id, AVG(rating) as avg_rating, COUNT(*) as review_count
            FROM reviews 
            GROUP BY reviewee_id
        ) r_stats ON u.id = r_stats.reviewee_id
        LEFT JOIN (
            SELECT user_id, COUNT(*) as work_count
            FROM works 
            WHERE status = 'published'
            GROUP BY user_id
        ) w_stats ON u.id = w_stats.user_id
        LEFT JOIN (
            SELECT creator_id, COUNT(*) as completed_jobs
            FROM job_applications 
            WHERE status = 'accepted'
            GROUP BY creator_id
        ) ja_stats ON u.id = ja_stats.creator_id
        WHERE u.user_type = 'creator' AND u.is_active = 1
        {$whereClause}
        ORDER BY {$orderBy}
        LIMIT {$perPage} OFFSET {$pagination['offset']}
    ";
    
    $creators = $db->select($creatorsSql, $values);
    
    // ログイン中ユーザーのクリエイターいいね状態を取得
    $userCreatorLikes = [];
    if (isLoggedIn() && !empty($creators)) {
        $creatorIds = array_column($creators, 'id');
        $placeholders = str_repeat('?,', count($creatorIds) - 1) . '?';
        $likesResult = $db->select(
            "SELECT target_id FROM favorites WHERE user_id = ? AND target_type = 'creator' AND target_id IN ($placeholders)",
            array_merge([$_SESSION['user_id']], $creatorIds)
        );
        $userCreatorLikes = array_column($likesResult, 'target_id');
    }
    
    // N+1問題対策：スキルを一括取得
    $creatorIds = array_map(fn($c) => $c['id'], $creators);
    $skillsByCreator = [];
    if (!empty($creatorIds)) {
        $skillsSql = "
            SELECT us.user_id, s.name, c.name as category_name, c.color as category_color
            FROM user_skills us
            JOIN skills s ON us.skill_id = s.id
            LEFT JOIN categories c ON s.category_id = c.id
            WHERE us.user_id IN (" . implode(',', array_fill(0, count($creatorIds), '?')) . ")
            ORDER BY us.user_id, us.proficiency DESC
        ";
        $allSkills = $db->select($skillsSql, $creatorIds);
        foreach ($allSkills as $skill) {
            if (!isset($skillsByCreator[$skill['user_id']])) {
                $skillsByCreator[$skill['user_id']] = [];
            }
            // 各クリエイターごとにスキルを3つまで保持
            if (count($skillsByCreator[$skill['user_id']]) < 3) {
                $skillsByCreator[$skill['user_id']][] = $skill;
            }
        }
    }

    // 各クリエイターにスキルをセット
    foreach ($creators as &$creator) {
        $creator['skills'] = $skillsByCreator[$creator['id']] ?? [];
    }
    
    // カテゴリ一覧取得
    $categories = $db->select("
        SELECT * FROM categories 
        WHERE is_active = 1 
        ORDER BY sort_order ASC
    ");
    
} catch (Exception $e) {
    // エラーログを記録
    error_log("Creators page error: " . $e->getMessage());
    error_log("SQL Query: " . $creatorsSql);
    error_log("Values: " . print_r($values, true));
    
    // エラー時は空の結果を返す
    $total = 0;
    $pagination = calculatePagination($total, $perPage, $page);
    $creators = [];
    $categories = [];
    
    // カテゴリだけは取得を試行
    try {
        $categories = $db->select("
            SELECT * FROM categories 
            WHERE is_active = 1 
            ORDER BY sort_order ASC
        ");
    } catch (Exception $categoryError) {
        error_log("Categories fetch error: " . $categoryError->getMessage());
        $categories = [];
    }
}

include 'includes/header.php';
?>

<!-- Breadcrumb -->
<nav class="bg-gray-50 py-4">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <ol class="flex items-center space-x-2 text-sm">
            <li><a href="<?= url() ?>" class="text-gray-500 hover:text-gray-700">ホーム</a></li>
            <li><span class="text-gray-400">/</span></li>
            <li><span class="text-gray-900 font-medium">クリエイター一覧</span></li>
        </ol>
    </div>
</nav>

<!-- Header -->
<section class="bg-white py-8 border-b border-gray-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">クリエイターを探す</h1>
            <p class="text-xl text-gray-600 mb-8">AIスキルを持つ優秀なクリエイターと出会おう</p>
            
            <!-- Search Bar -->
            <div class="max-w-2xl mx-auto">
                <form method="GET" class="relative">
                    <svg class="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input
                        type="text"
                        name="keyword"
                        value="<?= h($keyword) ?>"
                        placeholder="クリエイター名、スキル、地域で検索..."
                        class="w-full pl-12 pr-4 py-3 text-lg border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    />
                    <!-- 隠しフィールドで他の検索条件を保持 -->
                    <?php if ($categoryId): ?><input type="hidden" name="category_id" value="<?= h($categoryId) ?>"><?php endif; ?>
                    <?php if ($location): ?><input type="hidden" name="location" value="<?= h($location) ?>"><?php endif; ?>
                    <?php if ($sortBy !== 'recommended'): ?><input type="hidden" name="sort" value="<?= h($sortBy) ?>"><?php endif; ?>
                </form>
            </div>
        </div>
    </div>
</section>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex flex-col lg:flex-row gap-8">
        <!-- Sidebar Filters -->
        <div class="lg:w-1/4">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 sticky top-24">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">フィルター</h3>
                
                <form method="GET" id="filter-form">
                    <!-- 検索キーワードを保持 -->
                    <?php if ($keyword): ?><input type="hidden" name="keyword" value="<?= h($keyword) ?>"><?php endif; ?>
                    
                    <!-- カテゴリフィルター -->
                    <div class="mb-6">
                        <h4 class="font-medium text-gray-900 mb-3">専門分野</h4>
                        <div class="space-y-2">
                            <label class="flex items-center">
                                <input type="radio" name="category_id" value="" 
                                       <?= !$categoryId ? 'checked' : '' ?>
                                       class="text-blue-600 focus:ring-blue-500 border-gray-300"
                                       onchange="document.getElementById('filter-form').submit()">
                                <span class="ml-2 text-sm text-gray-700">すべて</span>
                            </label>
                            <?php foreach ($categories as $category): ?>
                                <label class="flex items-center">
                                    <input type="radio" name="category_id" value="<?= h($category['id']) ?>" 
                                           <?= $categoryId == $category['id'] ? 'checked' : '' ?>
                                           class="text-blue-600 focus:ring-blue-500 border-gray-300"
                                           onchange="document.getElementById('filter-form').submit()">
                                    <span class="ml-2 text-sm text-gray-700"><?= h($category['name']) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- 地域フィルター -->
                    <div class="mb-6">
                        <h4 class="font-medium text-gray-900 mb-3">地域</h4>
                        <input type="text" name="location" value="<?= h($location) ?>" 
                               placeholder="東京都、大阪府など"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                    </div>
                    
                    <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 transition-colors mb-3">
                        検索
                    </button>
                    
                    <button type="button" onclick="clearFilters()" class="w-full px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200 transition-colors">
                        フィルターをクリア
                    </button>
                </form>
            </div>
        </div>

        <!-- Main Content -->
        <div class="lg:w-3/4">
            <!-- Results Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
                <div>
                    <h2 class="text-xl font-semibold text-gray-900">
                        <?= number_format($total) ?>名のクリエイターが見つかりました
                    </h2>
                    <?php if ($keyword): ?>
                        <p class="text-gray-600 mt-1">「<?= h($keyword) ?>」の検索結果</p>
                    <?php endif; ?>
                </div>
                
                <!-- Sort Options -->
                <div class="flex items-center mt-4 sm:mt-0">
                    <label class="text-sm font-medium text-gray-700 mr-3">並び替え:</label>
                    <select name="sort" onchange="changeSort(this.value)" class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="recommended" <?= $sortBy === 'recommended' ? 'selected' : '' ?>>おすすめ順</option>
                        <option value="newest" <?= $sortBy === 'newest' ? 'selected' : '' ?>>新着順</option>
                        <option value="rating" <?= $sortBy === 'rating' ? 'selected' : '' ?>>評価順</option>
                        <option value="experience" <?= $sortBy === 'experience' ? 'selected' : '' ?>>経験年数順</option>
                        <option value="price_low" <?= $sortBy === 'price_low' ? 'selected' : '' ?>>料金の安い順</option>
                        <option value="price_high" <?= $sortBy === 'price_high' ? 'selected' : '' ?>>料金の高い順</option>
                    </select>
                </div>
            </div>

            <!-- Creators Grid -->
            <?php if (empty($creators)): ?>
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">クリエイターが見つかりませんでした</h3>
                    <p class="mt-1 text-sm text-gray-500">検索条件を変更してお試しください。</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                    <?php foreach ($creators as $creator): ?>
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-lg transition-all duration-300 hover:-translate-y-1">
                            <div class="p-6">
                                <!-- Creator Header -->
                                <div class="flex items-start space-x-4 mb-4">
                                    <img src="<?= h($creator['profile_image'] ?? asset('images/default-avatar.png')) ?>" 
                                         alt="<?= h($creator['full_name']) ?>" 
                                         class="w-16 h-16 rounded-full">
                                    <div class="flex-1 min-w-0">
                                        <h3 class="text-lg font-semibold text-gray-900 truncate">
                                            <a href="<?= url('creator-profile.php?id=' . $creator['id']) ?>" class="hover:text-blue-600 transition-colors">
                                                <?= h($creator['full_name']) ?>
                                            </a>
                                        </h3>
                                        <p class="text-sm text-gray-600"><?= h($creator['location']) ?></p>
                                        
                                        <div class="flex items-center mt-1">
                                            <?= renderStars($creator['avg_rating'] ?? 0) ?>
                                            <span class="ml-2 text-sm text-gray-600">
                                                <?= number_format($creator['avg_rating'] ?? 0, 1) ?> (<?= $creator['review_count'] ?? 0 ?>)
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Badges -->
                                <div class="flex flex-wrap gap-2 mb-4">
                                    <?php if ($creator['is_pro']): ?>
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            プロ認定
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($creator['response_time'] <= 6): ?>
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            レスポンス早い
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($creator['completed_jobs'] >= 50): ?>
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                            実績豊富
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <!-- Bio -->
                                <p class="text-sm text-gray-700 mb-4 line-clamp-3">
                                    <?= h($creator['bio']) ?>
                                </p>

                                <!-- Skills -->
                                <?php if (!empty($creator['skills'])): ?>
                                    <div class="mb-4">
                                        <div class="flex flex-wrap gap-1">
                                            <?php foreach (array_slice($creator['skills'], 0, 3) as $skill): ?>
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                    <?= h($skill['name']) ?>
                                                </span>
                                            <?php endforeach; ?>
                                            <?php if (count($creator['skills']) > 3): ?>
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-500">
                                                    +<?= count($creator['skills']) - 3 ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- Stats -->
                                <div class="grid grid-cols-3 gap-4 text-center mb-4 py-3 bg-gray-50 rounded-lg">
                                    <div>
                                        <div class="text-lg font-bold text-gray-900"><?= $creator['work_count'] ?></div>
                                        <div class="text-xs text-gray-500">作品</div>
                                    </div>
                                    <div>
                                        <div class="text-lg font-bold text-gray-900"><?= $creator['completed_jobs'] ?></div>
                                        <div class="text-xs text-gray-500">完了案件</div>
                                    </div>
                                    <div>
                                        <div class="text-lg font-bold text-gray-900"><?= $creator['experience_years'] ?>年</div>
                                        <div class="text-xs text-gray-500">経験</div>
                                    </div>
                                </div>

                                <!-- Price and Actions -->
                                <div class="flex items-center justify-between">
                                    <div>
                                        <div class="text-lg font-bold text-gray-900"><?= formatPrice($creator['hourly_rate']) ?>〜</div>
                                        <div class="text-xs text-gray-500">/ プロジェクト</div>
                                    </div>
                                    <div class="flex space-x-2">
                                        <?php 
                                        $isCreatorLiked = in_array($creator['id'], $userCreatorLikes ?? []);
                                        $heartFill = $isCreatorLiked ? 'currentColor' : 'none';
                                        $heartColor = $isCreatorLiked ? 'text-red-500' : 'text-gray-400';
                                        ?>
                                        <button onclick="toggleLike('creator', <?= (int)$creator['id'] ?>, this)"
                                                class="p-2 like-btn transition-colors bg-white/90 rounded-full hover:bg-white"
                                                data-liked="<?= $isCreatorLiked ? 'true' : 'false' ?>">
                                            <svg class="h-5 w-5 <?= $heartColor ?>" fill="<?= $heartFill ?>" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                            </svg>
                                        </button>
                                        <a href="<?= url('creator-profile.php?id=' . $creator['id']) ?>" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 transition-colors">
                                            プロフィール
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($pagination['total_pages'] > 1): ?>
                    <div class="mt-8 flex justify-center">
                        <nav class="flex items-center space-x-2">
                            <?php if ($pagination['has_prev']): ?>
                                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $pagination['prev_page']])) ?>" 
                                   class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                                    前へ
                                </a>
                            <?php endif; ?>
                            
                            <?php
                            $startPage = max(1, $pagination['current_page'] - 2);
                            $endPage = min($pagination['total_pages'], $pagination['current_page'] + 2);
                            
                            for ($i = $startPage; $i <= $endPage; $i++):
                            ?>
                                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" 
                                   class="px-3 py-2 text-sm font-medium <?= $i === $pagination['current_page'] ? 'text-blue-600 bg-blue-50 border-blue-500' : 'text-gray-500 bg-white border-gray-300 hover:bg-gray-50' ?> border rounded-md">
                                    <?= $i ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($pagination['has_next']): ?>
                                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $pagination['next_page']])) ?>" 
                                   class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                                    次へ
                                </a>
                            <?php endif; ?>
                        </nav>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function changeSort(value) {
    const url = new URL(window.location);
    url.searchParams.set('sort', value);
    url.searchParams.delete('page'); // ページをリセット
    window.location.href = url.toString();
}

function clearFilters() {
    const url = new URL(window.location);
    url.searchParams.delete('category_id');
    url.searchParams.delete('location');
    url.searchParams.delete('page');
    window.location.href = url.toString();
}

// いいね機能（クリエイター）
async function toggleLike(targetType, targetId, button) {
    try {
        const response = await fetch('api/like.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                target_type: targetType,
                target_id: targetId
            })
        });
        const result = await response.json();
        if (result.success) {
            const svg = button.querySelector('svg');
            if (result.liked) {
                svg.setAttribute('fill', 'currentColor');
                svg.classList.remove('text-gray-400');
                svg.classList.add('text-red-500');
                button.setAttribute('data-liked', 'true');
            } else {
                svg.setAttribute('fill', 'none');
                svg.classList.remove('text-red-500');
                svg.classList.add('text-gray-400');
                button.setAttribute('data-liked', 'false');
            }
            if (typeof showNotification === 'function') {
                showNotification(result.message, 'success');
            }
        } else {
            if (typeof showNotification === 'function') {
                showNotification(result.error || 'エラーが発生しました', 'error');
            }
        }
    } catch (error) {
        console.error('Like toggle error:', error);
        if (typeof showNotification === 'function') {
            showNotification('ネットワークエラーが発生しました', 'error');
        }
    }
}
</script>

<?php include 'includes/footer.php'; ?>

