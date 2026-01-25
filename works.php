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
    
    // ログイン中のユーザーのいいね状態を取得（work_likes から）
    $userLikes = [];
    if (isLoggedIn() && !empty($works)) {
        $workIds = array_column($works, 'id');
        $placeholders = str_repeat('?,', count($workIds) - 1) . '?';
        $likesResult = $db->select(
            "SELECT work_id FROM work_likes WHERE user_id = ? AND work_id IN ($placeholders)",
            array_merge([$_SESSION['user_id']], $workIds)
        );
        $userLikes = array_column($likesResult, 'work_id');
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

<!-- Premium Compact Hero Section -->
<section class="relative py-24 flex items-center justify-center overflow-hidden bg-slate-900 text-white">
    <!-- Animated Background -->
    <div class="absolute inset-0 z-0">
        <div class="absolute inset-0 bg-cover bg-center animate-ken-burns-slow transform-gpu" 
             style="background-image: url('assets/images/hero-background.jpg');">
        </div>
        <div class="absolute inset-0 bg-gradient-to-r from-slate-900/95 via-blue-900/90 to-slate-900/95 mix-blend-multiply"></div>
        <div class="absolute inset-0 bg-black/30"></div>
    </div>

    <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-4xl md:text-5xl font-bold tracking-tight mb-4 animate-fade-in-up">
            作品を探す
            <span class="block text-lg md:text-xl font-medium text-blue-200 mt-2 tracking-widest opacity-80">CREATIVE SHOWCASE</span>
        </h1>
        <p class="text-lg text-slate-300 max-w-2xl mx-auto leading-relaxed animate-fade-in-up animation-delay-100">
            AIスクール生の創造性が光る優秀作品の数々。<br class="hidden md:inline">
            インスピレーションを刺激するクリエイティブな世界へ。
        </p>
    </div>
    <style>
        @keyframes ken-burns-slow {
            0% { transform: scale(1) translate(0, 0); }
            100% { transform: scale(1.1) translate(-1%, -1%); }
        }
        .animate-ken-burns-slow { animation: ken-burns-slow 20s ease-in-out infinite alternate; }
        .animate-fade-in-up { animation: fadeInUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards; opacity: 0; transform: translateY(20px); }
        .animation-delay-100 { animation-delay: 0.1s; }
        @keyframes fadeInUp { to { opacity: 1; transform: translateY(0); } }
    </style>
</section>


<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Glassmorphism Filter Panel -->
    <div class="mb-12 relative z-30 -mt-16">
        <div class="bg-white/90 backdrop-blur-xl rounded-2xl shadow-xl border border-white/20 p-6 md:p-8 transform transition-all hover:shadow-2xl">
            <form method="GET" id="filter-form" class="space-y-6">
                <!-- Main Search & Sort -->
                <div class="flex flex-col md:flex-row gap-4">
                    <div class="flex-1 relative group">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <svg class="h-6 w-6 text-slate-400 group-focus-within:text-blue-500 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input type="text" name="keyword" value="<?= h($keyword) ?>" 
                               class="block w-full pl-12 pr-4 py-4 bg-slate-50/50 border-0 rounded-xl text-slate-900 placeholder-slate-400 ring-1 ring-slate-200 focus:ring-2 focus:ring-blue-500 focus:bg-white transition-all text-lg" 
                               placeholder="作品名、キーワードで検索..."
                               onkeydown="if(event.key === 'Enter') document.getElementById('filter-form').submit()">
                    </div>
                </div>

                <!-- Filters Grid -->
                <div class="grid grid-cols-1 md:grid-cols-12 gap-6 pt-6 border-t border-slate-100">
                    <!-- Category Filter -->
                    <div class="md:col-span-8">
                        <label class="block text-sm font-bold text-slate-700 mb-3">カテゴリ</label>
                        <div class="flex flex-wrap gap-2">
                            <label class="cursor-pointer">
                                <input type="radio" name="category_id" value="" class="peer sr-only" <?= empty($categoryId) ? 'checked' : '' ?> onchange="this.form.submit()">
                                <div class="px-4 py-2 rounded-lg text-sm font-medium transition-all peer-checked:bg-slate-900 peer-checked:text-white peer-checked:shadow-md bg-slate-100 text-slate-600 hover:bg-slate-200">
                                    すべて
                                </div>
                            </label>
                            <?php foreach ($categories as $category): ?>
                                <label class="cursor-pointer">
                                    <input type="radio" name="category_id" value="<?= h($category['id']) ?>" class="peer sr-only" <?= $categoryId == $category['id'] ? 'checked' : '' ?> onchange="this.form.submit()">
                                    <div class="px-4 py-2 rounded-lg text-sm font-medium transition-all peer-checked:bg-blue-600 peer-checked:text-white peer-checked:shadow-md bg-white border border-slate-200 text-slate-600 hover:border-blue-300 hover:text-blue-600">
                                        <?= h($category['name']) ?>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Price Filter -->
                    <div class="md:col-span-4">
                        <label class="block text-sm font-bold text-slate-700 mb-3">価格帯</label>
                        <div class="relative">
                            <select name="price_range" onchange="const [min, max] = this.value.split('-'); setPriceRange(min, max);" 
                                    class="block w-full pl-4 pr-10 py-3 bg-white border-0 ring-1 ring-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 text-slate-700 cursor-pointer hover:bg-slate-50 transition-colors appearance-none">
                                <option value="">すべての価格</option>
                                <option value="0-30000" <?= ($priceMin == '0' && $priceMax == '30000') ? 'selected' : '' ?>>〜¥30,000</option>
                                <option value="30000-50000" <?= ($priceMin == '30000' && $priceMax == '50000') ? 'selected' : '' ?>>¥30,000〜¥50,000</option>
                                <option value="50000-100000" <?= ($priceMin == '50000' && $priceMax == '100000') ? 'selected' : '' ?>>¥50,000〜¥100,000</option>
                                <option value="100000-" <?= ($priceMin == '100000') ? 'selected' : '' ?>>¥100,000〜</option>
                            </select>
                            <div class="absolute inset-y-0 right-0 flex items-center px-4 pointer-events-none text-slate-500">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </div>
                        </div>
                        <input type="hidden" name="price_min" value="<?= h($priceMin) ?>">
                        <input type="hidden" name="price_max" value="<?= h($priceMax) ?>">
                        <input type="hidden" name="sort" value="<?= h($sortBy) ?>">
                    </div>
                </div>
                
                <!-- Active Filters & Reset -->
                <?php if ($keyword || $categoryId || $priceMin || $priceMax): ?>
                    <div class="pt-4 border-t border-slate-100 flex justify-end">
                        <button type="button" onclick="clearFilters()" class="text-sm text-red-500 hover:text-red-700 font-medium flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            条件をクリア
                        </button>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>
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
                    <label class="text-sm font-medium text-gray-900 mr-3">並び替え:</label>
                    <select name="sort" onchange="changeSort(this.value)" class="border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="recommended" <?= $sortBy === 'recommended' ? 'selected' : '' ?>>おすすめ順</option>
                        <option value="newest" <?= $sortBy === 'newest' ? 'selected' : '' ?>>新着順</option>
                        <option value="popular" <?= $sortBy === 'popular' ? 'selected' : '' ?>>人気順</option>
                        <option value="rating" <?= $sortBy === 'rating' ? 'selected' : '' ?>>評価の高い順</option>
                        <option value="price_low" <?= $sortBy === 'price_low' ? 'selected' : '' ?>>価格の安い順</option>
                        <option value="price_high" <?= $sortBy === 'price_high' ? 'selected' : '' ?>>価格の高い順</option>
                    </select>
                </div>
            </div>

            <!-- Works Grid & Empty State -->
            <?php if (empty($works)): ?>
                <!-- Premium Empty State -->
                <div class="text-center py-24 px-6">
                    <div class="relative w-32 h-32 mx-auto mb-8 group">
                        <div class="absolute inset-0 bg-blue-100 rounded-full animate-ping opacity-20"></div>
                        <div class="relative w-32 h-32 bg-gradient-to-br from-slate-50 to-white rounded-full flex items-center justify-center shadow-lg border border-slate-100">
                            <svg class="h-12 w-12 text-slate-300 group-hover:text-blue-500 transition-colors duration-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                    </div>
                    <h3 class="text-2xl font-bold text-slate-900 mb-3">作品が見つかりませんでした</h3>
                    <p class="text-slate-500 mb-8 max-w-md mx-auto leading-relaxed">
                        条件に一致する作品はありませんでした。<br>検索条件を少し広げてみてください。
                    </p>
                    <button type="button" onclick="clearFilters()" class="inline-flex items-center px-8 py-3 bg-white text-slate-700 font-medium rounded-xl border border-slate-200 hover:bg-slate-50 hover:border-blue-300 hover:text-blue-600 transition-all shadow-sm hover:shadow-md">
                        検索条件をクリア
                    </button>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <?php foreach ($works as $work): ?>
                        <div class="group bg-white rounded-2xl overflow-hidden hover:shadow-2xl transition-all duration-300 hover:-translate-y-1 border border-slate-100 flex flex-col h-full">
                            <!-- Image Container -->
                            <div class="relative aspect-[4/3] overflow-hidden bg-slate-100">
                                <a href="<?= url('work-detail?id=' . $work['id']) ?>" class="block w-full h-full">
                                    <div class="absolute inset-0 bg-slate-900/0 group-hover:bg-slate-900/10 transition-colors z-10"></div>
                                    <img src="<?= uploaded_asset($work['main_image']) ?>" 
                                         alt="<?= h($work['title']) ?>" 
                                         class="w-full h-full object-cover transform group-hover:scale-105 transition-transform duration-700" 
                                         loading="lazy">
                                    
                                    <!-- Badges -->
                                    <div class="absolute top-4 left-4 z-20 flex gap-2">
                                        <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-white/90 backdrop-blur-md text-slate-700 shadow-sm border border-white/20">
                                            <?= h($work['category_name']) ?>
                                        </span>
                                    </div>

                                    <!-- Like Button -->
                                    <div class="absolute top-4 right-4 z-20">
                                        <?php
                                        $isLiked = in_array($work['id'], $userLikes);
                                        $heartFill = $isLiked ? 'currentColor' : 'none';
                                        $heartColor = $isLiked ? 'text-rose-500' : 'text-slate-900/50 hover:text-rose-500';
                                        ?>
                                        <button onclick="toggleLike('work', <?= $work['id'] ?>, this); return false;" 
                                                class="p-2.5 rounded-full bg-white/90 backdrop-blur-md hover:bg-white shadow-sm transition-all transform hover:scale-105 group/btn"
                                                data-liked="<?= $isLiked ? 'true' : 'false' ?>">
                                            <svg class="h-5 w-5 <?= $heartColor ?> transition-colors" fill="<?= $heartFill ?>" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                            </svg>
                                        </button>
                                    </div>
                                    
                                    <!-- Price Tag -->
                                    <div class="absolute bottom-4 right-4 z-20">
                                        <span class="px-3 py-1.5 rounded-lg text-sm font-bold bg-slate-900/90 backdrop-blur-md text-white shadow-lg border border-white/10">
                                            <?= formatPrice($work['price_min']) ?>~
                                        </span>
                                    </div>
                                </a>
                            </div>
                            
                            <!-- Content -->
                            <div class="p-5 flex-1 flex flex-col">
                                <h3 class="text-lg font-bold text-slate-900 mb-2 line-clamp-1 group-hover:text-blue-600 transition-colors">
                                    <a href="<?= url('work-detail?id=' . $work['id']) ?>">
                                        <?= h($work['title']) ?>
                                    </a>
                                </h3>

                                <div class="flex items-center justify-between mb-4">
                                    <div class="flex items-center space-x-2">
                                        <img src="<?= uploaded_asset($work['creator_image'] ?? 'assets/images/default-avatar.png') ?>" 
                                             alt="<?= h($work['creator_name']) ?>" 
                                             class="w-6 h-6 rounded-full border border-slate-100">
                                        <span class="text-sm text-slate-600 truncate max-w-[120px]"><?= h($work['creator_name']) ?></span>
                                    </div>
                                    <div class="flex items-center text-yellow-500 text-sm font-bold">
                                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" /></svg>
                                        <?= number_format($work['avg_rating'] ?? 0, 1) ?>
                                    </div>
                                </div>
                                
                                <div class="mt-auto pt-4 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500 font-medium">
                                    <div class="flex items-center">
                                        <svg class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                        <?= number_format($work['view_count']) ?> views
                                    </div>
                                    <div class="flex items-center text-rose-500">
                                        <svg class="h-4 w-4 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd" /></svg>
                                        <span id="like-count-<?= $work['id'] ?>"><?= number_format($work['like_count'] ?? 0) ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Modern Pagination -->
                <?php if ($pagination['total_pages'] > 1): ?>
                    <div class="flex justify-center mt-16">
                        <nav class="flex items-center gap-2 p-2 bg-white rounded-full shadow-lg border border-slate-100">
                            <?php if ($pagination['has_prev']): ?>
                                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $pagination['prev_page']])) ?>" 
                                   class="p-2 w-10 h-10 flex items-center justify-center text-slate-500 hover:text-blue-600 hover:bg-blue-50 rounded-full transition-colors">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                                </a>
                            <?php endif; ?>
                            
                            <?php
                            $startPage = max(1, $pagination['current_page'] - 2);
                            $endPage = min($pagination['total_pages'], $pagination['current_page'] + 2);
                            
                            for ($i = $startPage; $i <= $endPage; $i++):
                            ?>
                                <?php if ($i === $pagination['current_page']): ?>
                                    <span class="w-10 h-10 flex items-center justify-center text-white bg-slate-900 rounded-full shadow-md font-bold">
                                        <?= $i ?>
                                    </span>
                                <?php else: ?>
                                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" 
                                       class="w-10 h-10 flex items-center justify-center text-slate-600 hover:text-blue-600 hover:bg-slate-50 rounded-full transition-colors font-medium">
                                        <?= $i ?>
                                    </a>
                                <?php endif; ?>
                            <?php endfor; ?>
                            
                            <?php if ($pagination['has_next']): ?>
                                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $pagination['next_page']])) ?>" 
                                   class="p-2 w-10 h-10 flex items-center justify-center text-slate-500 hover:text-blue-600 hover:bg-blue-50 rounded-full transition-colors">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                                </a>
                            <?php endif; ?>
                        </nav>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
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
        const response = await fetch('<?= url("api/like.php") ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                work_id: targetId
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            // ボタンの状態を更新
            const svg = button.querySelector('svg');
            const likeCountElement = document.getElementById(`like-count-${targetId}`);
            
            if (result.is_liked) {
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
            showNotification(result.message || result.error || 'エラーが発生しました', 'error');
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
