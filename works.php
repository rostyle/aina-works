<?php
require_once 'config/config.php';

$pageTitle = '作品一覧';
$pageDescription = 'AIスクール生の優秀な作品をご覧ください';

// データベース接続
$db = Database::getInstance();

// 検索・フィルター条件
$keyword = $_GET['keyword'] ?? '';
$categoryId = $_GET['category_id'] ?? '';
$priceMin = $_GET['price_min'] ?? '';
$priceMax = $_GET['price_max'] ?? '';
$sortBy = $_GET['sort'] ?? 'recommended';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;

// 検索条件構築
$searchParams = [
    'keyword' => $keyword,
    'category_id' => $categoryId,
    'price_min' => $priceMin,
    'price_max' => $priceMax,
];
$allowedFields = ['keyword', 'category_id', 'price_min', 'price_max'];
$search = buildSearchConditions($searchParams, $allowedFields);

// ソート条件
$orderBy = match($sortBy) {
    'newest' => 'w.created_at DESC',
    'popular' => 'w.view_count DESC',
    'rating' => 'avg_rating DESC, w.view_count DESC',
    'price_low' => 'w.price_min ASC',
    'price_high' => 'w.price_min DESC',
    default => 'w.is_featured DESC, w.view_count DESC'
};

try {
    // 総件数取得
    $totalSql = "
        SELECT COUNT(DISTINCT w.id) as total
        FROM works w
        JOIN users u ON w.user_id = u.id
        LEFT JOIN categories c ON w.category_id = c.id
        WHERE w.status = 'published' AND u.is_active = 1 AND u.is_creator = 1
        " . ($search['where'] ? 'AND ' . str_replace('WHERE ', '', $search['where']) : '');
    
    $total = $db->selectOne($totalSql, $search['values'])['total'] ?? 0;
    
    // ページネーション計算
    $pagination = calculatePagination($total, $perPage, $page);
    
    // 作品一覧取得
    $worksSql = "
        SELECT w.*, u.full_name as creator_name, u.profile_image as creator_image, 
               c.name as category_name, c.color as category_color,
               AVG(r.rating) as avg_rating, COUNT(DISTINCT r.id) as review_count
        FROM works w
        JOIN users u ON w.user_id = u.id
        LEFT JOIN categories c ON w.category_id = c.id
        LEFT JOIN reviews r ON w.id = r.work_id
        WHERE w.status = 'published' AND u.is_active = 1 AND u.is_creator = 1
        " . ($search['where'] ? 'AND ' . str_replace('WHERE ', '', $search['where']) : '') . "
        GROUP BY w.id
        ORDER BY {$orderBy}
        LIMIT {$perPage} OFFSET {$pagination['offset']}
    ";
    
    $works = $db->select($worksSql, $search['values']);
    
    // ログイン中のユーザーのいいね状態を取得
    $userLikes = [];
    if (isLoggedIn() && !empty($works)) {
        $workIds = array_column($works, 'id');
        $placeholders = str_repeat('?,', count($workIds) - 1) . '?';
        $likesResult = $db->select(
            "SELECT target_id FROM favorites WHERE user_id = ? AND target_type = 'work' AND target_id IN ($placeholders)",
            array_merge([$_SESSION['user_id']], $workIds)
        );
        $userLikes = array_column($likesResult, 'target_id');
    }
    
    // デバッグ: 取得された作品データを確認
    if (DEBUG) {
        echo "<!-- DEBUG: 取得された作品数: " . count($works) . " -->";
        foreach ($works as $work) {
            echo "<!-- DEBUG: 作品ID: " . $work['id'] . ", タイトル: " . $work['title'] . ", 画像: " . $work['main_image'] . " -->";
        }
    }
    
    // カテゴリ一覧取得（検索条件を反映）
    $categoryCountSql = "
        SELECT c.*, COUNT(w.id) as work_count 
        FROM categories c 
        LEFT JOIN works w ON c.id = w.category_id AND w.status = 'published'";
    
    // 検索条件をカテゴリ件数にも適用（カテゴリ自体は除く）
    $categoryCountParams = [];
    if (!empty($keyword)) {
        $categoryCountSql .= " AND (w.title LIKE ? OR w.description LIKE ?)";
        $categoryCountParams[] = "%$keyword%";
        $categoryCountParams[] = "%$keyword%";
    }
    if (!empty($priceMin)) {
        $categoryCountSql .= " AND w.price_min >= ?";
        $categoryCountParams[] = $priceMin;
    }
    if (!empty($priceMax)) {
        $categoryCountSql .= " AND w.price_max <= ?";
        $categoryCountParams[] = $priceMax;
    }
    
    $categoryCountSql .= "
        WHERE c.is_active = 1 
        GROUP BY c.id 
        ORDER BY c.sort_order ASC
    ";
    
    $categories = $db->select($categoryCountSql, $categoryCountParams);
    
} catch (Exception $e) {
    // エラー時は空データを使用
    $total = 0;
    $pagination = calculatePagination($total, $perPage, $page);
    $works = [];
    
    $userLikes = [];
    $categories = [];
}

include 'includes/header.php';
?>

<!-- Breadcrumb -->
<nav class="bg-gray-50 py-4">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <ol class="flex items-center space-x-2 text-sm">
            <li><a href="<?= url() ?>" class="text-gray-500 hover:text-gray-700">ホーム</a></li>
            <li><span class="text-gray-400">/</span></li>
            <li><span class="text-gray-900 font-medium">作品一覧</span></li>
        </ol>
    </div>
</nav>

<!-- Header -->
<section class="bg-white py-8 border-b border-gray-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">作品を探す</h1>
            <p class="text-xl text-gray-600 mb-8">AIスクール生の優秀作品をご覧ください</p>
            
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
                        placeholder="作品名、クリエイター名、タグで検索..."
                        class="w-full pl-12 pr-4 py-3 text-lg border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    />
                    <!-- 隠しフィールドで他の検索条件を保持 -->
                    <?php if ($categoryId): ?><input type="hidden" name="category_id" value="<?= h($categoryId) ?>"><?php endif; ?>
                    <?php if ($priceMin): ?><input type="hidden" name="price_min" value="<?= h($priceMin) ?>"><?php endif; ?>
                    <?php if ($priceMax): ?><input type="hidden" name="price_max" value="<?= h($priceMax) ?>"><?php endif; ?>
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
                        <h4 class="font-medium text-gray-900 mb-3">カテゴリ</h4>
                        <div class="space-y-2">
                            <?php foreach ($categories as $category): ?>
                                <label class="flex items-center">
                                    <input type="radio" name="category_id" value="<?= h($category['id']) ?>" 
                                           <?= $categoryId == $category['id'] ? 'checked' : '' ?>
                                           class="text-blue-600 focus:ring-blue-500 border-gray-300"
                                           onchange="document.getElementById('filter-form').submit()">
                                    <span class="ml-2 text-sm text-gray-700">
                                        <?= h($category['name']) ?> 
                                        <span class="text-gray-500">(<?= number_format($category['work_count']) ?>)</span>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- 価格フィルター -->
                    <div class="mb-6">
                        <h4 class="font-medium text-gray-900 mb-3">価格帯</h4>
                        <div class="space-y-2">
                            <label class="flex items-center">
                                <input type="radio" name="price_range" value="" 
                                       <?= !$priceMin && !$priceMax ? 'checked' : '' ?>
                                       class="text-blue-600 focus:ring-blue-500 border-gray-300"
                                       onchange="clearPriceInputs(); document.getElementById('filter-form').submit()">
                                <span class="ml-2 text-sm text-gray-700">すべて</span>
                            </label>
                            <label class="flex items-center">
                                <input type="radio" name="price_range" value="0-30000" 
                                       class="text-blue-600 focus:ring-blue-500 border-gray-300"
                                       onchange="setPriceRange(0, 30000)">
                                <span class="ml-2 text-sm text-gray-700">〜¥30,000</span>
                            </label>
                            <label class="flex items-center">
                                <input type="radio" name="price_range" value="30000-50000" 
                                       class="text-blue-600 focus:ring-blue-500 border-gray-300"
                                       onchange="setPriceRange(30000, 50000)">
                                <span class="ml-2 text-sm text-gray-700">¥30,000〜¥50,000</span>
                            </label>
                            <label class="flex items-center">
                                <input type="radio" name="price_range" value="50000-100000" 
                                       class="text-blue-600 focus:ring-blue-500 border-gray-300"
                                       onchange="setPriceRange(50000, 100000)">
                                <span class="ml-2 text-sm text-gray-700">¥50,000〜¥100,000</span>
                            </label>
                            <label class="flex items-center">
                                <input type="radio" name="price_range" value="100000-" 
                                       class="text-blue-600 focus:ring-blue-500 border-gray-300"
                                       onchange="setPriceRange(100000, '')">
                                <span class="ml-2 text-sm text-gray-700">¥100,000〜</span>
                            </label>
                        </div>
                        <input type="hidden" name="price_min" value="<?= h($priceMin) ?>">
                        <input type="hidden" name="price_max" value="<?= h($priceMax) ?>">
                    </div>
                    
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
                        <?= number_format($total) ?>件の作品が見つかりました
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
                        <option value="popular" <?= $sortBy === 'popular' ? 'selected' : '' ?>>人気順</option>
                        <option value="rating" <?= $sortBy === 'rating' ? 'selected' : '' ?>>評価の高い順</option>
                        <option value="price_low" <?= $sortBy === 'price_low' ? 'selected' : '' ?>>価格の安い順</option>
                        <option value="price_high" <?= $sortBy === 'price_high' ? 'selected' : '' ?>>価格の高い順</option>
                    </select>
                </div>
            </div>

            <!-- Works Grid -->
            <?php if (empty($works)): ?>
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">作品が見つかりませんでした</h3>
                    <p class="mt-1 text-sm text-gray-500">検索条件を変更してお試しください。</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                    <?php foreach ($works as $work): ?>
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-lg transition-all duration-300 hover:-translate-y-1">
                            <div class="relative">
                                <img src="<?= uploaded_asset($work['main_image']) ?>" alt="<?= h($work['title']) ?>" class="w-full h-48 object-cover" <?= DEBUG ? 'title="Debug: ' . h($work['main_image'] . ' -> ' . uploaded_asset($work['main_image'])) . '"' : '' ?>>
                                <div class="absolute top-4 left-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        <?= h($work['category_name']) ?>
                                    </span>
                                </div>
                                <div class="absolute top-4 right-4 flex space-x-2">
                                    <?php
                                    $isLiked = in_array($work['id'], $userLikes);
                                    $heartFill = $isLiked ? 'currentColor' : 'none';
                                    $heartColor = $isLiked ? 'text-red-500' : 'text-gray-600';
                                    ?>
                                    <button onclick="toggleLike('work', <?= $work['id'] ?>, this)" 
                                            class="like-btn p-2 bg-white/90 backdrop-blur-sm rounded-full hover:bg-white transition-colors"
                                            data-liked="<?= $isLiked ? 'true' : 'false' ?>">
                                        <svg class="h-4 w-4 <?= $heartColor ?>" fill="<?= $heartFill ?>" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="p-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-2">
                                    <a href="<?= url('work-detail?id=' . $work['id']) ?>" class="hover:text-blue-600 transition-colors">
                                        <?= h($work['title']) ?>
                                    </a>
                                </h3>
                                
                                <div class="flex items-center mb-3">
                                    <img src="<?= uploaded_asset($work['creator_image'] ?? 'assets/images/default-avatar.png') ?>" 
                                         alt="<?= h($work['creator_name']) ?>" 
                                         class="w-8 h-8 rounded-full mr-3">
                                    <span class="text-sm text-gray-600"><?= h($work['creator_name']) ?></span>
                                </div>
                                
                                <div class="flex items-center justify-between mb-4">
                                    <div class="flex items-center">
                                        <?= renderStars($work['avg_rating'] ?? 0) ?>
                                        <span class="ml-2 text-sm text-gray-600">
                                            <?= number_format($work['avg_rating'] ?? 0, 1) ?> (<?= $work['review_count'] ?? 0 ?>)
                                        </span>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-lg font-bold text-gray-900"><?= formatPrice($work['price_min']) ?>〜</div>
                                    </div>
                                </div>
                                
                                <div class="flex items-center justify-between text-sm text-gray-500">
                                    <div class="flex items-center">
                                        <svg class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        <?= number_format($work['view_count']) ?>
                                    </div>
                                    <div class="flex items-center">
                                        <svg class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                        </svg>
                                        <span id="like-count-<?= $work['id'] ?>"><?= number_format($work['like_count'] ?? 0) ?></span>
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

function setPriceRange(min, max) {
    document.querySelector('input[name="price_min"]').value = min;
    document.querySelector('input[name="price_max"]').value = max;
    document.getElementById('filter-form').submit();
}

function clearPriceInputs() {
    document.querySelector('input[name="price_min"]').value = '';
    document.querySelector('input[name="price_max"]').value = '';
}

function clearFilters() {
    const url = new URL(window.location);
    url.searchParams.delete('category_id');
    url.searchParams.delete('price_min');
    url.searchParams.delete('price_max');
    url.searchParams.delete('page');
    window.location.href = url.toString();
}

// いいね機能
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
            // ボタンの状態を更新
            const svg = button.querySelector('svg');
            const likeCountElement = document.getElementById(`like-count-${targetId}`);
            
            if (result.liked) {
                // いいね状態
                svg.setAttribute('fill', 'currentColor');
                svg.classList.remove('text-gray-600');
                svg.classList.add('text-red-500');
                button.setAttribute('data-liked', 'true');
            } else {
                // いいね解除状態
                svg.setAttribute('fill', 'none');
                svg.classList.remove('text-red-500');
                svg.classList.add('text-gray-600');
                button.setAttribute('data-liked', 'false');
            }
            
            // いいね数を更新
            if (likeCountElement) {
                likeCountElement.textContent = new Intl.NumberFormat().format(result.like_count);
            }
            
            // 成功メッセージを表示（オプション）
            showNotification(result.message, 'success');
            
        } else {
            showNotification(result.error || 'エラーが発生しました', 'error');
        }
        
    } catch (error) {
        console.error('Like toggle error:', error);
        showNotification('ネットワークエラーが発生しました', 'error');
    }
}

// 通知表示機能
function showNotification(message, type = 'info') {
    // 既存の通知を削除
    const existing = document.querySelector('.notification');
    if (existing) {
        existing.remove();
    }
    
    // 通知要素を作成
    const notification = document.createElement('div');
    notification.className = `notification fixed top-4 right-4 px-4 py-2 rounded-md text-white z-50 transition-all duration-300 ${
        type === 'success' ? 'bg-green-500' : 
        type === 'error' ? 'bg-red-500' : 
        'bg-blue-500'
    }`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    // 3秒後に自動削除
    setTimeout(() => {
        if (notification.parentNode) {
            notification.style.opacity = '0';
            notification.style.transform = 'translateY(-10px)';
            setTimeout(() => notification.remove(), 300);
        }
    }, 3000);
}
</script>

<?php include 'includes/footer.php'; ?>

