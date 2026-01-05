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
    // 経験年数カラムが存在しない環境向けにフォールバック
    'experience' => 'u.created_at DESC',
    // 時給カラム廃止のため料金ソートは無効化（created_atにフォールバック）
    'price_low' => 'u.created_at DESC',
    'price_high' => 'u.created_at DESC',
    default => 'u.is_pro DESC, avg_rating DESC, u.created_at DESC'
};

try {
    // 総件数取得
    $totalSql = "
        SELECT COUNT(DISTINCT u.id) as total
        FROM users u
        WHERE u.is_creator = 1 AND u.is_active = 1
        {$whereClause}
    ";
    
    $total = $db->selectOne($totalSql, $values)['total'] ?? 0;
    
    // ページネーション計算
    $pagination = calculatePagination($total, $perPage, $page);
    
    // クリエイター一覧取得
    $creatorsSql = "
        SELECT u.id,
               u.aina_user_id,
               u.name,
               u.username,
               u.email,
               u.full_name,
               u.nickname,
               u.profile_image,
               u.bio,
               u.location,
               u.website,
               u.twitter_url,
               u.instagram_url,
               u.facebook_url,
               u.linkedin_url,
               u.youtube_url,
               u.tiktok_url,
               u.response_time,
               0 as experience_years,
               u.is_pro,
               u.is_verified,
               u.is_active,
               u.last_seen,
               u.created_at,
               u.updated_at,
               u.is_creator,
               u.is_client,
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
        WHERE u.is_creator = 1 AND u.is_active = 1
        {$whereClause}
        GROUP BY u.id
        ORDER BY {$orderBy}
        LIMIT {$perPage} OFFSET {$pagination['offset']}
    ";
    
    $creators = $db->select($creatorsSql, $values);
    // 念のためIDで重複排除（参照不具合やJOINの影響に備えたフェイルセーフ）
    if (!empty($creators)) {
        $uniqueById = [];
        foreach ($creators as $row) {
            $uniqueById[$row['id']] = $row;
        }
        $creators = array_values($uniqueById);
    }
    
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

    // 各クリエイターにスキルをセット（参照を使わないで安全に代入）
    foreach ($creators as $idx => $c) {
        $creators[$idx]['skills'] = $skillsByCreator[$c['id']] ?? [];
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

<!-- Premium Compact Hero Section -->
<section class="relative py-24 flex items-center justify-center overflow-hidden bg-slate-900 text-white">
    <!-- Animated Background -->
    <div class="absolute inset-0 z-0">
        <div class="absolute inset-0 bg-cover bg-center animate-ken-burns-slow transform-gpu" 
             style="background-image: url('<?= asset('images/hero-background.jpg') ?>');">
        </div>
        <div class="absolute inset-0 bg-gradient-to-r from-slate-900/95 via-purple-900/90 to-slate-900/95 mix-blend-multiply"></div>
        <div class="absolute inset-0 bg-black/30"></div>
    </div>

    <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-4xl md:text-5xl font-bold tracking-tight mb-4 animate-fade-in-up">
            クリエイター一覧
            <span class="block text-lg md:text-xl font-medium text-purple-200 mt-2 tracking-widest opacity-80">CREATORS</span>
        </h1>
        <p class="text-lg text-slate-300 max-w-2xl mx-auto leading-relaxed animate-fade-in-up animation-delay-100">
            AIスキルを持つ優秀なクリエイターと出会おう。<br class="hidden md:inline">
            あなたのビジョンを形にする、最適なパートナーが見つかります。
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
<!-- Glassmorphism Search & Sorting Section -->
<section class="relative -mt-8 z-20 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto mb-12">
    <div class="bg-white rounded-2xl shadow-xl border border-slate-100 p-6 md:p-8 backdrop-blur-xl bg-opacity-95">
        <form method="GET" id="creator-search-form" class="space-y-6">
            
            <!-- Search Bar & Primary Actions -->
            <div class="flex flex-col md:flex-row gap-4">
                <div class="flex-1 relative group">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <svg class="h-6 w-6 text-slate-400 group-focus-within:text-purple-500 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input type="text" 
                           name="keyword" 
                           value="<?= h($keyword) ?>"
                           placeholder="クリエイター名、スキル、キーワード..."
                           class="w-full pl-12 pr-4 py-4 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 transition-all font-medium text-slate-700 placeholder-slate-400 text-lg">
                </div>
                <button type="submit" 
                        class="px-10 py-4 bg-gradient-to-r from-purple-600 to-indigo-600 text-white rounded-xl hover:from-purple-700 hover:to-indigo-700 shadow-lg hover:shadow-purple-500/30 transition-all transform hover:-translate-y-0.5 font-bold text-lg flex items-center justify-center gap-2">
                    検索する
                </button>
            </div>

            <div class="h-px bg-slate-100 w-full my-6"></div>

            <!-- Filters Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                
                <!-- Category Filter -->
                <div class="relative">
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5 ml-1">専門分野</label>
                    <select name="category_id" class="w-full pl-4 pr-10 py-3 bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 text-slate-700 font-medium appearance-none cursor-pointer hover:bg-slate-50 transition-colors" onchange="this.form.submit()">
                        <option value="">すべて表示</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= h($category['id']) ?>" <?= $categoryId == $category['id'] ? 'selected' : '' ?>>
                                <?= h($category['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="pointer-events-none absolute bottom-0 right-0 flex items-center px-4 py-3.5 text-slate-500">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                    </div>
                </div>

                <!-- Location Filter -->
                <div class="relative">
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5 ml-1">活動地域</label>
                    <div class="relative">
                         <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                        </div>
                        <input type="text" name="location" value="<?= h($location) ?>" 
                               placeholder="東京都..."
                               class="w-full pl-10 pr-4 py-3 bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 text-slate-700 font-medium hover:bg-slate-50 transition-colors">
                    </div>
                </div>

                <!-- Sort Filter -->
                <div class="relative">
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5 ml-1">並び替え</label>
                    <select name="sort" class="w-full pl-4 pr-10 py-3 bg-slate-50 border border-slate-200/80 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 text-slate-700 font-medium appearance-none cursor-pointer hover:bg-slate-100 transition-colors" onchange="this.form.submit()">
                        <option value="recommended" <?= $sortBy === 'recommended' ? 'selected' : '' ?>>おすすめ順 ✨</option>
                        <option value="newest" <?= $sortBy === 'newest' ? 'selected' : '' ?>>新着順 ⚡</option>
                        <option value="rating" <?= $sortBy === 'rating' ? 'selected' : '' ?>>評価順 ⭐</option>
                        <option value="experience" <?= $sortBy === 'experience' ? 'selected' : '' ?>>経験年数順 🎓</option>
                    </select>
                    <div class="pointer-events-none absolute bottom-0 right-0 flex items-center px-4 py-3.5 text-slate-500">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                    </div>
                </div>

                <!-- Reset Button -->
                <div class="relative flex items-end">
                    <button type="button" onclick="location.href='creators.php'" class="w-full py-3 bg-slate-100 text-slate-600 font-medium rounded-lg hover:bg-slate-200 transition-colors flex items-center justify-center gap-2">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        クリア
                    </button>
                </div>
            </div>
        </form>
    </div>
</section>

<!-- Main Layout (Grid only now, sidebar removed) -->
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-16">
    <div class="w-full">

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
                    <label class="text-sm font-medium text-gray-900 mr-3">並び替え:</label>
                    <select name="sort" onchange="changeSort(this.value)" class="border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="recommended" <?= $sortBy === 'recommended' ? 'selected' : '' ?>>おすすめ順</option>
                        <option value="newest" <?= $sortBy === 'newest' ? 'selected' : '' ?>>新着順</option>
                        <option value="rating" <?= $sortBy === 'rating' ? 'selected' : '' ?>>評価順</option>
                        <option value="experience" <?= $sortBy === 'experience' ? 'selected' : '' ?>>経験年数順</option>
                        <option value="price_low" <?= $sortBy === 'price_low' ? 'selected' : '' ?>>料金の安い順</option>
                        <option value="price_high" <?= $sortBy === 'price_high' ? 'selected' : '' ?>>料金の高い順</option>
                    </select>
                </div>
            </div>

            <!-- Creators Grid & Empty State -->
            <?php if (empty($creators)): ?>
                <!-- Premium Empty State -->
                <div class="text-center py-24 px-6">
                    <div class="relative w-32 h-32 mx-auto mb-8 group">
                        <div class="absolute inset-0 bg-blue-100 rounded-full animate-ping opacity-20"></div>
                        <div class="relative w-32 h-32 bg-gradient-to-br from-slate-50 to-white rounded-full flex items-center justify-center shadow-lg border border-slate-100">
                            <svg class="h-12 w-12 text-slate-300 group-hover:text-blue-500 transition-colors duration-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                    </div>
                    <h3 class="text-2xl font-bold text-slate-900 mb-3">クリエイターが見つかりませんでした</h3>
                    <p class="text-slate-500 mb-8 max-w-md mx-auto leading-relaxed">
                        現在の条件に一致するクリエイターはいませんでした。<br>条件を変更して再度検索してみてください。
                    </p>
                    <button type="button" onclick="clearFilters()" class="inline-flex items-center px-8 py-3 bg-white text-slate-700 font-medium rounded-xl border border-slate-200 hover:bg-slate-50 hover:border-blue-300 hover:text-blue-600 transition-all shadow-sm hover:shadow-md">
                        検索条件をクリア
                    </button>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <?php foreach ($creators as $creator): ?>
                        <div class="group relative bg-white rounded-2xl overflow-hidden hover:shadow-2xl transition-all duration-300 hover:-translate-y-1 border border-slate-100 flex flex-col h-full">
                            <!-- Helper Background Gradient -->
                            <div class="absolute inset-x-0 top-0 h-32 bg-gradient-to-br from-blue-50 to-purple-50 group-hover:from-blue-100 group-hover:to-purple-100 transition-colors duration-500"></div>

                            <div class="p-6 pt-8 relative flex-1 flex flex-col">
                                <!-- Top Actions (Like) -->
                                <div class="absolute top-4 right-4 z-10">
                                    <?php 
                                    $isCreatorLiked = in_array($creator['id'], $userCreatorLikes ?? []);
                                    $heartFill = $isCreatorLiked ? 'currentColor' : 'none';
                                    $heartColor = $isCreatorLiked ? 'text-rose-500' : 'text-slate-300';
                                    ?>
                                    <button onclick="toggleFavorite('creator', <?= (int)$creator['id'] ?>, this)"
                                            class="p-2.5 rounded-full bg-white/80 backdrop-blur-sm shadow-sm hover:shadow-md hover:bg-white transition-all transform hover:scale-105 group/btn"
                                            data-liked="<?= $isCreatorLiked ? 'true' : 'false' ?>">
                                        <svg class="h-5 w-5 <?= $heartColor ?> group-hover/btn:text-rose-500 transition-colors" fill="<?= $heartFill ?>" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                        </svg>
                                    </button>
                                </div>

                                <!-- Profile Image & Status -->
                                <div class="relative w-24 h-24 mx-auto mb-4">
                                    <div class="absolute inset-0 bg-white rounded-full p-1 shadow-md">
                                        <img src="<?= uploaded_asset($creator['profile_image'] ?? 'assets/images/default-avatar.png') ?>" 
                                             alt="<?= h($creator['full_name']) ?>" 
                                             class="w-full h-full rounded-full object-cover">
                                    </div>
                                    <?php if ($creator['is_active']): ?>
                                        <div class="absolute bottom-1 right-1 w-5 h-5 bg-green-500 border-4 border-white rounded-full" title="オンライン"></div>
                                    <?php endif; ?>
                                </div>

                                <!-- Name & Title -->
                                <div class="text-center mb-6">
                                    <h3 class="text-xl font-bold text-slate-900 mb-1 group-hover:text-purple-600 transition-colors">
                                        <a href="<?= url('creator-profile?id=' . $creator['id']) ?>">
                                            <?= h($creator['full_name']) ?>
                                        </a>
                                    </h3>
                                    <?php if ($creator['is_pro']): ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-sm mb-2">
                                            PRO CERTIFIED
                                        </span>
                                    <?php endif; ?>
                                    <p class="text-sm text-slate-500 flex items-center justify-center gap-1">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                        <?= h($creator['location']) ?>
                                    </p>
                                </div>

                                <!-- Stats Grid -->
                                <div class="grid grid-cols-2 gap-px bg-slate-100 rounded-xl overflow-hidden mb-6 border border-slate-100">
                                    <div class="bg-slate-50 p-3 text-center group-hover:bg-white transition-colors">
                                        <div class="text-xs text-slate-500 mb-0.5">評価</div>
                                        <div class="font-bold text-slate-900 flex items-center justify-center gap-1">
                                            <svg class="w-4 h-4 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" /></svg>
                                            <?= number_format($creator['avg_rating'] ?? 0, 1) ?>
                                        </div>
                                    </div>
                                    <div class="bg-slate-50 p-3 text-center group-hover:bg-white transition-colors">
                                        <div class="text-xs text-slate-500 mb-0.5">実績</div>
                                        <div class="font-bold text-slate-900"><?= number_format($creator['completed_jobs']) ?>件</div>
                                    </div>
                                </div>

                                <!-- Skills -->
                                <?php if (!empty($creator['skills'])): ?>
                                    <div class="flex flex-wrap gap-2 mb-6 justify-center">
                                        <?php foreach (array_slice($creator['skills'], 0, 3) as $skill): ?>
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-medium bg-slate-100 text-slate-600 border border-slate-200">
                                                <?= h($skill['name']) ?>
                                            </span>
                                        <?php endforeach; ?>
                                        <?php if (count($creator['skills']) > 3): ?>
                                            <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-slate-50 text-slate-400 border border-slate-100">
                                                +<?= count($creator['skills']) - 3 ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="h-8 mb-6"></div> <!-- Spacer if no skills -->
                                <?php endif; ?>

                                <!-- Bio Preview -->
                                <p class="text-xs text-slate-500 text-center line-clamp-3 mb-6 px-2 italic">
                                    "<?= h(mb_substr($creator['bio'], 0, 80)) ?>..."
                                </p>

                                <!-- Footer Action -->
                                <div class="mt-auto pt-4 border-t border-slate-100 text-center">
                                    <a href="<?= url('creator-profile?id=' . $creator['id']) ?>" class="inline-flex items-center justify-center w-full px-4 py-2.5 bg-slate-900 text-white text-sm font-bold rounded-xl hover:bg-purple-600 transition-colors shadow-lg shadow-slate-200 group-hover:shadow-purple-500/20">
                                        プロフィールを見る
                                        <svg class="ml-2 w-4 h-4 transform group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                                    </a>
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
    </div>
</div>

<script>
function changeSort(value) {
    const url = new URL(window.location);
    url.searchParams.set('sort', value);
    url.searchParams.delete('page'); 
    window.location.href = url.toString();
}

function clearFilters() {
    const url = new URL(window.location);
    url.searchParams.delete('category_id');
    url.searchParams.delete('location');
    url.searchParams.delete('page');
    window.location.href = url.toString();
}

// お気に入り機能（クリエイター）
async function toggleFavorite(targetType, targetId, button) {
    try {
        const isFavorited = button.getAttribute('data-liked') === 'true';
        const response = await fetch('api/favorite.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                action: isFavorited ? 'remove' : 'add',
                target_type: targetType,
                target_id: targetId
            })
        });

        if (response.status === 401) {
            const redirectUrl = 'login.php?redirect=' + encodeURIComponent(window.location.href);
            window.location.href = redirectUrl;
            return;
        }

        const result = await response.json();

        if (result.success) {
            const svg = button.querySelector('svg');
            if (result.is_favorite) {
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
        console.error('Favorite toggle error:', error);
        if (typeof showNotification === 'function') {
            showNotification('ネットワークエラーが発生しました', 'error');
        }
    }
}
</script>

<?php include 'includes/footer.php'; ?>
