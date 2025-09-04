<?php
require_once 'config/config.php';

$pageTitle = 'AiNAのポートフォリオ&マッチングプラットフォーム';
$pageDescription = 'AIスクール生と企業をつなぐ、新しいクリエイティブプラットフォーム';

// データベース接続
$db = Database::getInstance();

// 統計情報取得
$stats = [
    'creators' => $db->selectOne("SELECT COUNT(*) as count FROM users WHERE user_type = 'creator' AND is_active = 1")['count'] ?? 0,
    'works' => $db->selectOne("SELECT COUNT(*) as count FROM works WHERE status = 'published'")['count'] ?? 0,
    'jobs_completed' => $db->selectOne("SELECT COUNT(*) as count FROM jobs WHERE status = 'completed'")['count'] ?? 0,
    'categories' => $db->selectOne("SELECT COUNT(*) as count FROM categories WHERE is_active = 1")['count'] ?? 0,
];

// カテゴリ取得
$categories = $db->select("
    SELECT c.*, COUNT(w.id) as work_count 
    FROM categories c 
    LEFT JOIN works w ON c.id = w.category_id AND w.status = 'published'
    WHERE c.is_active = 1 
    GROUP BY c.id 
    ORDER BY c.sort_order ASC
");

// おすすめ作品取得
$featuredWorks = $db->select("
    SELECT w.*, u.full_name as creator_name, u.profile_image as creator_image, c.name as category_name,
           AVG(r.rating) as avg_rating, COUNT(r.id) as review_count
    FROM works w
    JOIN users u ON w.user_id = u.id
    LEFT JOIN categories c ON w.category_id = c.id
    LEFT JOIN reviews r ON w.id = r.work_id
    WHERE w.status = 'published' AND w.is_featured = 1
    GROUP BY w.id
    ORDER BY w.view_count DESC, w.created_at DESC
    LIMIT 6
");

include 'includes/header.php';
?>

<!-- Enhanced Hero Section with Modern Design -->
<main id="main-content" role="main">
<section class="relative min-h-screen flex items-center justify-center bg-gradient-to-br from-primary-900 via-primary-800 to-secondary-900 text-white overflow-hidden">
    <!-- Animated Background Elements -->
    <div class="absolute inset-0">
        <!-- Gradient Orbs -->
        <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-primary-500/20 rounded-full blur-3xl animate-pulse-gentle"></div>
        <div class="absolute bottom-1/4 right-1/4 w-80 h-80 bg-secondary-500/20 rounded-full blur-3xl animate-pulse-gentle" style="animation-delay: 1s;"></div>
        <div class="absolute top-1/2 left-1/2 w-64 h-64 bg-accent-emerald/10 rounded-full blur-2xl animate-bounce-gentle" style="animation-delay: 2s;"></div>
        
        <!-- Grid Pattern -->
        <div class="absolute inset-0 opacity-10">
            <div class="absolute inset-0" style="background-image: linear-gradient(rgba(255,255,255,0.1) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.1) 1px, transparent 1px); background-size: 50px 50px;"></div>
        </div>
    </div>
    
    <!-- Background Image with Enhanced Overlay -->
    <div class="absolute inset-0 bg-cover bg-center bg-no-repeat opacity-10" style="background-image: url('<?= asset('images/hero-background.jpg') ?>');"></div>
    
    <!-- Content -->
    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 z-10">
        <div class="text-center">
            <!-- Hero Badge -->
            <div class="inline-flex items-center px-4 py-2 bg-white/10 backdrop-blur-lg rounded-full text-sm font-medium text-white/90 mb-8 animate-fade-in">
                <span class="w-2 h-2 bg-green-400 rounded-full mr-2 animate-pulse"></span>
                新しいクリエイティブプラットフォーム
            </div>
            
            <!-- Main Headline -->
            <h1 class="text-display-lg md:text-display-xl font-bold mb-8 animate-slide-up text-balance">
                <span class="text-gradient-warm">AIスキル</span>で未来を創る
                <br />
                <span class="text-white/90">クリエイター</span><span class="text-gradient">マッチング</span>
            </h1>
            
            <!-- Subtitle -->
            <p class="text-xl md:text-2xl mb-12 text-white/80 max-w-4xl mx-auto leading-relaxed animate-slide-up" style="animation-delay: 0.2s;">
                AIスクール修了生と企業をつなぐ、革新的なクリエイティブプラットフォーム。
                <br class="hidden md:block">
                才能と情熱を持つクリエイターが、素晴らしいプロジェクトと出会える場所
            </p>
            
            <!-- Enhanced Search Bar -->
            <div class="max-w-3xl mx-auto mb-12 animate-scale-in" style="animation-delay: 0.4s;">
                <form action="<?= url('works.php') ?>" method="GET" class="relative group">
                    <div class="absolute inset-0 bg-gradient-to-r from-primary-500 to-secondary-500 rounded-2xl blur opacity-20 group-hover:opacity-30 transition-opacity duration-500"></div>
                    <div class="relative bg-white/95 backdrop-blur-lg rounded-2xl p-2 shadow-2xl">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 pl-4">
                                <svg class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <label for="hero-search" class="sr-only">クリエイター検索</label>
                            <input
                                type="text"
                                id="hero-search"
                                name="keyword"
                                placeholder="スキル、カテゴリ、キーワードで検索..."
                                aria-describedby="search-help"
                                class="flex-1 px-4 py-4 text-lg bg-transparent text-gray-900 placeholder-gray-500 border-0 focus:ring-0 focus:outline-none"
                            />
                            <button type="submit" class="btn btn-primary btn-lg btn-shimmer mr-2" aria-label="クリエイターを検索">
                                <svg class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                                検索
                            </button>
                            <div id="search-help" class="sr-only">クリエイターの名前、スキル、専門分野で検索できます</div>
                        </div>
                    </div>
                </form>
            </div>

            <!-- CTA Buttons -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center mb-16 animate-fade-in" style="animation-delay: 0.6s;">
                <a href="<?= url('register.php?type=creator') ?>" class="btn btn-outline btn-lg btn-shimmer group">
                    <svg class="h-5 w-5 mr-2 group-hover:rotate-12 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z" />
                    </svg>
                    クリエイター登録
                    <span class="ml-2 group-hover:translate-x-1 transition-transform duration-300">→</span>
                </a>
                <a href="<?= url('register.php?type=client') ?>" class="btn btn-secondary btn-lg btn-shimmer group">
                    <svg class="h-5 w-5 mr-2 group-hover:scale-110 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2-2v2m8 0V6a2 2 0 012 2v6a2 2 0 01-2 2H6a2 2 0 01-2-2V8a2 2 0 012-2V6z" />
                    </svg>
                    依頼者登録
                    <span class="ml-2 group-hover:translate-x-1 transition-transform duration-300">→</span>
                </a>
            </div>
            
            <!-- Trust Indicators -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8 max-w-4xl mx-auto text-center animate-slide-up" style="animation-delay: 0.8s;">
                <div class="group">
                    <div class="text-3xl md:text-4xl font-bold text-white mb-2 group-hover:scale-110 transition-transform duration-300">
                        <?= number_format($stats['creators']) ?>+
                    </div>
                    <div class="text-sm text-white/70">登録クリエイター</div>
                </div>
                <div class="group">
                    <div class="text-3xl md:text-4xl font-bold text-white mb-2 group-hover:scale-110 transition-transform duration-300">
                        <?= number_format($stats['works']) ?>+
                    </div>
                    <div class="text-sm text-white/70">公開作品</div>
                </div>
                <div class="group">
                    <div class="text-3xl md:text-4xl font-bold text-white mb-2 group-hover:scale-110 transition-transform duration-300">
                        <?= number_format($stats['jobs_completed']) ?>+
                    </div>
                    <div class="text-sm text-white/70">完了案件</div>
                </div>
                <div class="group">
                    <div class="text-3xl md:text-4xl font-bold text-white mb-2 group-hover:scale-110 transition-transform duration-300">
                        98%
                    </div>
                    <div class="text-sm text-white/70">満足度</div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Scroll Indicator -->
    <div class="absolute bottom-8 left-1/2 transform -translate-x-1/2 animate-bounce">
        <div class="w-6 h-10 border-2 border-white/30 rounded-full flex justify-center">
            <div class="w-1 h-3 bg-white/50 rounded-full mt-2 animate-pulse"></div>
        </div>
    </div>
</section>

<!-- Enhanced Categories Section -->
<section class="py-24 bg-gradient-to-b from-white to-gray-50" aria-labelledby="categories-heading">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16 fade-in-on-scroll">
            <div class="inline-flex items-center px-4 py-2 bg-primary-100 text-primary-800 rounded-full text-sm font-medium mb-6">
                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
                人気カテゴリ
            </div>
            <h2 id="categories-heading" class="text-display-md md:text-display-lg font-bold text-gray-900 mb-6 text-balance">
                多様な<span class="text-gradient">スキル</span>を持つ
                <br />クリエイターが活躍中
            </h2>
            <p class="text-xl text-gray-600 max-w-3xl mx-auto leading-relaxed">
                AIスクールで身につけた最新のスキルを活かし、様々な分野で活動するクリエイターたちの作品をご覧ください
            </p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-6 mb-12">
            <?php foreach ($categories as $category): ?>
                <a href="<?= url('works.php?category_id=' . $category['id']) ?>" 
                   class="group card hover-lift fade-in-on-scroll">
                    <div class="p-8 text-center">
                        <!-- Enhanced Icon with Gradient Background -->
                        <div class="relative mb-6">
                            <div class="w-20 h-20 mx-auto rounded-2xl flex items-center justify-center text-3xl transition-all duration-500 group-hover:scale-110 group-hover:rotate-3"
                                 style="background: linear-gradient(135deg, <?= h($category['color'] ?? '#3B82F6') ?>15, <?= h($category['color'] ?? '#3B82F6') ?>25); color: <?= h($category['color'] ?? '#3B82F6') ?>;">
                                <?php
                                $icons = [
                                    'ロゴ制作' => '🎨',
                                    'ライティング' => '✍️',
                                    'Web制作' => '💻',
                                    '動画編集' => '🎬',
                                    'AI漫画' => '🤖',
                                    '音楽制作' => '🎵'
                                ];
                                echo $icons[$category['name']] ?? '📝';
                                ?>
                            </div>
                            <!-- Glow Effect -->
                            <div class="absolute inset-0 rounded-2xl opacity-0 group-hover:opacity-20 transition-opacity duration-500"
                                 style="background: radial-gradient(circle, <?= h($category['color'] ?? '#3B82F6') ?>40, transparent 70%);"></div>
                        </div>
                        
                        <h3 class="font-bold text-lg text-gray-900 mb-3 group-hover:text-primary-600 transition-colors duration-300">
                            <?= h($category['name']) ?>
                        </h3>
                        
                        <div class="flex items-center justify-center space-x-2 text-sm text-gray-500">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <span><?= number_format($category['work_count']) ?>件の作品</span>
                        </div>
                        
                        <!-- Hover Arrow -->
                        <div class="mt-4 opacity-0 group-hover:opacity-100 transition-all duration-300 transform translate-y-2 group-hover:translate-y-0">
                            <div class="inline-flex items-center text-sm font-medium text-primary-600">
                                作品を見る
                                <svg class="w-4 h-4 ml-1 group-hover:translate-x-1 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
        
        <!-- View All Categories Button -->
        <div class="text-center fade-in-on-scroll">
            <a href="<?= url('works.php') ?>" class="btn btn-outline btn-lg group">
                すべてのカテゴリを見る
                <svg class="w-5 h-5 ml-2 group-hover:translate-x-1 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                </svg>
            </a>
        </div>
    </div>
</section>

<!-- Enhanced Featured Works Section -->
<section class="py-24 bg-white relative overflow-hidden" aria-labelledby="works-heading">
    <!-- Background Pattern -->
    <div class="absolute inset-0 opacity-5">
        <div class="absolute inset-0" style="background-image: radial-gradient(circle at 2px 2px, rgba(59, 130, 246, 0.15) 1px, transparent 0); background-size: 40px 40px;"></div>
    </div>
    
    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16 fade-in-on-scroll">
            <div class="inline-flex items-center px-4 py-2 bg-secondary-100 text-secondary-800 rounded-full text-sm font-medium mb-6">
                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                </svg>
                おすすめ作品
            </div>
            <h2 id="works-heading" class="text-display-md md:text-display-lg font-bold text-gray-900 mb-6 text-balance">
                厳選された<span class="text-gradient">クオリティ</span>の高い
                <br />作品をご紹介
            </h2>
            <p class="text-xl text-gray-600 max-w-3xl mx-auto leading-relaxed">
                プロフェッショナルなクリエイターたちが手がけた、実績と評価の高い作品をピックアップしました
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 mb-12">
            <?php foreach ($featuredWorks as $work): ?>
                <div class="group card card-elevated hover-lift fade-in-on-scroll image-overlay">
                    <!-- Enhanced Image with Overlay Effects -->
                    <div class="relative overflow-hidden rounded-t-2xl">
                        <img src="<?= h($work['main_image']) ?>" 
                             alt="<?= h($work['title']) ?>" 
                             class="w-full h-64 object-cover transition-transform duration-700 group-hover:scale-110">
                        
                        <!-- Gradient Overlay -->
                        <div class="absolute inset-0 bg-gradient-to-t from-black/50 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                        
                        <!-- Category Badge -->
                        <div class="absolute top-4 left-4">
                            <span class="badge badge-primary backdrop-blur-lg">
                                <?= h($work['category_name']) ?>
                            </span>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="absolute top-4 right-4 flex space-x-2 opacity-0 group-hover:opacity-100 transition-all duration-500 transform translate-y-2 group-hover:translate-y-0">
                            <button onclick="toggleLike('work', <?= $work['id'] ?>, this)" 
                                    class="p-2.5 bg-white/90 backdrop-blur-lg rounded-xl hover:bg-white hover:scale-110 transition-all duration-300 shadow-lg"
                                    title="いいね">
                                <svg class="h-4 w-4 text-gray-600 hover:text-red-500 transition-colors duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                </svg>
                            </button>
                            <button class="p-2.5 bg-white/90 backdrop-blur-lg rounded-xl hover:bg-white hover:scale-110 transition-all duration-300 shadow-lg"
                                    title="シェア">
                                <svg class="h-4 w-4 text-gray-600 hover:text-primary-600 transition-colors duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.367 2.684 3 3 0 00-5.367-2.684z" />
                                </svg>
                            </button>
                        </div>
                        
                        <!-- View Work Button (appears on hover) -->
                        <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all duration-500">
                            <a href="<?= url('work-detail.php?id=' . $work['id']) ?>" 
                               class="btn btn-primary btn-lg backdrop-blur-lg transform scale-90 group-hover:scale-100 transition-all duration-300">
                                作品を見る
                            </a>
                        </div>
                    </div>
                    
                    <!-- Enhanced Content -->
                    <div class="p-8">
                        <h3 class="text-xl font-bold text-gray-900 mb-3 group-hover:text-primary-600 transition-colors duration-300">
                            <a href="<?= url('work-detail.php?id=' . $work['id']) ?>" class="line-clamp-2">
                                <?= h($work['title']) ?>
                            </a>
                        </h3>
                        
                        <!-- Creator Info -->
                        <div class="flex items-center mb-4">
                            <div class="relative">
                                <img src="<?= h($work['creator_image'] ?? asset('images/default-avatar.png')) ?>" 
                                     alt="<?= h($work['creator_name']) ?>" 
                                     class="w-10 h-10 rounded-full mr-3 ring-2 ring-gray-200 group-hover:ring-primary-300 transition-all duration-300">
                                <div class="absolute -bottom-1 -right-1 w-4 h-4 bg-green-400 rounded-full border-2 border-white"></div>
                            </div>
                            <div>
                                <span class="text-sm font-medium text-gray-900"><?= h($work['creator_name']) ?></span>
                                <div class="text-xs text-gray-500">プロクリエイター</div>
                            </div>
                        </div>
                        
                        <!-- Rating and Price -->
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center">
                                <div class="flex items-center mr-2">
                                    <?= renderStars($work['avg_rating'] ?? 0) ?>
                                </div>
                                <span class="text-sm font-medium text-gray-900">
                                    <?= number_format($work['avg_rating'] ?? 0, 1) ?>
                                </span>
                                <span class="text-sm text-gray-500 ml-1">
                                    (<?= $work['review_count'] ?? 0 ?>)
                                </span>
                            </div>
                            <div class="text-right">
                                <div class="text-lg font-bold text-gray-900"><?= formatPrice($work['price_min']) ?>〜</div>
                                <div class="text-xs text-gray-500">から</div>
                            </div>
                        </div>
                        
                        <!-- Stats -->
                        <div class="flex items-center justify-between text-sm text-gray-500 pt-4 border-t border-gray-100">
                            <div class="flex items-center">
                                <svg class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <span><?= number_format($work['view_count']) ?></span>
                            </div>
                            <div class="flex items-center">
                                <svg class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                </svg>
                                <span id="like-count-<?= $work['id'] ?>"><?= number_format($work['like_count'] ?? 0) ?></span>
                            </div>
                            <div class="flex items-center">
                                <svg class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                </svg>
                                <span><?= $work['review_count'] ?? 0 ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- View All Button -->
        <div class="text-center fade-in-on-scroll">
            <a href="<?= url('works.php') ?>" class="btn btn-primary btn-xl btn-shimmer group">
                すべての作品を見る
                <svg class="ml-3 h-5 w-5 group-hover:translate-x-1 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                </svg>
            </a>
        </div>
    </div>
</section>

<!-- Enhanced Stats Section -->
<section class="py-24 bg-gradient-to-b from-gray-50 to-white relative overflow-hidden" aria-labelledby="stats-heading">
    <!-- Background Pattern -->
    <div class="absolute inset-0 opacity-5">
        <div class="absolute inset-0" style="background-image: linear-gradient(45deg, rgba(59, 130, 246, 0.1) 25%, transparent 25%), linear-gradient(-45deg, rgba(168, 85, 247, 0.1) 25%, transparent 25%), linear-gradient(45deg, transparent 75%, rgba(59, 130, 246, 0.1) 75%), linear-gradient(-45deg, transparent 75%, rgba(168, 85, 247, 0.1) 75%); background-size: 60px 60px; background-position: 0 0, 0 30px, 30px -30px, -30px 0px;"></div>
    </div>
    
    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16 fade-in-on-scroll">
            <div class="inline-flex items-center px-4 py-2 bg-green-100 text-green-800 rounded-full text-sm font-medium mb-6">
                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                実績・成果
            </div>
                                        <h2 id="stats-heading" class="text-display-md md:text-display-lg font-bold text-gray-900 mb-6 text-balance">
                <span class="text-gradient">AiNA Works</span>の実績
            </h2>
            <p class="text-xl text-gray-600 max-w-3xl mx-auto leading-relaxed">
                多くのクリエイターと企業に選ばれ、数多くの成功プロジェクトを生み出しています
            </p>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-8 mb-16">
            <div class="text-center group fade-in-on-scroll">
                <div class="relative mb-4">
                    <div class="w-20 h-20 mx-auto bg-gradient-to-br from-primary-500 to-primary-600 rounded-2xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform duration-500 shadow-lg">
                        <svg class="w-10 h-10 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z" />
                        </svg>
                    </div>
                    <div class="absolute inset-0 bg-gradient-to-br from-primary-400 to-primary-500 rounded-2xl opacity-0 group-hover:opacity-20 transition-opacity duration-500 blur"></div>
                </div>
                <div class="text-4xl md:text-5xl font-bold text-primary-600 mb-2 counter" data-target="<?= $stats['creators'] ?>">
                    0
                </div>
                <div class="text-lg font-medium text-gray-900 mb-1">登録クリエイター</div>
                <div class="text-sm text-gray-500">活躍中のプロフェッショナル</div>
            </div>
            
            <div class="text-center group fade-in-on-scroll" style="animation-delay: 0.1s;">
                <div class="relative mb-4">
                    <div class="w-20 h-20 mx-auto bg-gradient-to-br from-secondary-500 to-secondary-600 rounded-2xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform duration-500 shadow-lg">
                        <svg class="w-10 h-10 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <div class="absolute inset-0 bg-gradient-to-br from-secondary-400 to-secondary-500 rounded-2xl opacity-0 group-hover:opacity-20 transition-opacity duration-500 blur"></div>
                </div>
                <div class="text-4xl md:text-5xl font-bold text-secondary-600 mb-2 counter" data-target="<?= $stats['works'] ?>">
                    0
                </div>
                <div class="text-lg font-medium text-gray-900 mb-1">公開作品</div>
                <div class="text-sm text-gray-500">クオリティの高い制作物</div>
            </div>
            
            <div class="text-center group fade-in-on-scroll" style="animation-delay: 0.2s;">
                <div class="relative mb-4">
                    <div class="w-20 h-20 mx-auto bg-gradient-to-br from-green-500 to-green-600 rounded-2xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform duration-500 shadow-lg">
                        <svg class="w-10 h-10 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="absolute inset-0 bg-gradient-to-br from-green-400 to-green-500 rounded-2xl opacity-0 group-hover:opacity-20 transition-opacity duration-500 blur"></div>
                </div>
                <div class="text-4xl md:text-5xl font-bold text-green-600 mb-2 counter" data-target="<?= $stats['jobs_completed'] ?>">
                    0
                </div>
                <div class="text-lg font-medium text-gray-900 mb-1">完了案件</div>
                <div class="text-sm text-gray-500">成功したプロジェクト</div>
            </div>
            
            <div class="text-center group fade-in-on-scroll" style="animation-delay: 0.3s;">
                <div class="relative mb-4">
                    <div class="w-20 h-20 mx-auto bg-gradient-to-br from-orange-500 to-orange-600 rounded-2xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform duration-500 shadow-lg">
                        <svg class="w-10 h-10 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                        </svg>
                    </div>
                    <div class="absolute inset-0 bg-gradient-to-br from-orange-400 to-orange-500 rounded-2xl opacity-0 group-hover:opacity-20 transition-opacity duration-500 blur"></div>
                </div>
                <div class="text-4xl md:text-5xl font-bold text-orange-600 mb-2">
                    98<span class="text-2xl">%</span>
                </div>
                <div class="text-lg font-medium text-gray-900 mb-1">満足度</div>
                <div class="text-sm text-gray-500">クライアント評価</div>
            </div>
        </div>
        
        <!-- Trust Badges -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 fade-in-on-scroll">
            <div class="text-center p-6 bg-white rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow duration-300">
                <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">安心・安全</h3>
                <p class="text-gray-600">厳格な審査を通過したクリエイターのみが登録</p>
            </div>
            
            <div class="text-center p-6 bg-white rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow duration-300">
                <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">スピード納期</h3>
                <p class="text-gray-600">平均3日以内でのプロジェクト開始を実現</p>
            </div>
            
            <div class="text-center p-6 bg-white rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow duration-300">
                <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192L5.636 18.364M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">24/7サポート</h3>
                <p class="text-gray-600">専任スタッフがプロジェクト完了まで徹底サポート</p>
            </div>
        </div>
    </div>
</section>

<!-- Enhanced CTA Section -->
<section class="py-24 bg-gradient-to-br from-primary-900 via-primary-800 to-secondary-900 text-white relative overflow-hidden" aria-labelledby="cta-heading">
    <!-- Animated Background -->
    <div class="absolute inset-0">
        <div class="absolute top-1/4 right-1/4 w-64 h-64 bg-primary-500/10 rounded-full blur-3xl animate-pulse-gentle"></div>
        <div class="absolute bottom-1/4 left-1/4 w-80 h-80 bg-secondary-500/10 rounded-full blur-3xl animate-pulse-gentle" style="animation-delay: 1s;"></div>
        <div class="absolute top-1/2 left-1/2 w-96 h-96 bg-accent-emerald/5 rounded-full blur-3xl animate-bounce-gentle" style="animation-delay: 2s;"></div>
    </div>
    
    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <div class="fade-in-on-scroll">
            <div class="inline-flex items-center px-4 py-2 bg-white/10 backdrop-blur-lg rounded-full text-sm font-medium text-white/90 mb-8">
                <span class="w-2 h-2 bg-green-400 rounded-full mr-2 animate-pulse"></span>
                今すぐ始めよう
            </div>
            
            <h2 id="cta-heading" class="text-display-md md:text-display-lg font-bold mb-8 text-balance">
                <span class="text-gradient-warm">AiNA Works</span>で
                <br />新しい可能性を見つけよう
            </h2>
            
            <p class="text-xl md:text-2xl text-white/80 mb-12 max-w-4xl mx-auto leading-relaxed">
                AIスキルを活かして新しいキャリアを築くか、優秀なクリエイターと出会って
                <br class="hidden md:block">
                素晴らしいプロジェクトを実現しましょう
            </p>
            
            <div class="flex flex-col sm:flex-row gap-6 justify-center mb-16">
                <a href="<?= url('register.php?type=creator') ?>" class="btn btn-outline btn-xl btn-shimmer group bg-white/10 backdrop-blur-lg border-white/30 text-white hover:bg-white hover:text-primary-600">
                    <svg class="h-6 w-6 mr-3 group-hover:rotate-12 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z" />
                    </svg>
                    クリエイター登録
                    <span class="ml-3 group-hover:translate-x-1 transition-transform duration-300">→</span>
                </a>
                <a href="<?= url('register.php?type=client') ?>" class="btn btn-secondary btn-xl btn-shimmer group">
                    <svg class="h-6 w-6 mr-3 group-hover:scale-110 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2-2v2m8 0V6a2 2 0 012 2v6a2 2 0 01-2 2H6a2 2 0 01-2-2V8a2 2 0 012-2V6z" />
                    </svg>
                    依頼者登録
                    <span class="ml-3 group-hover:translate-x-1 transition-transform duration-300">→</span>
                </a>
            </div>
            
            <!-- Additional Features -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 text-left max-w-4xl mx-auto">
                <div class="flex items-start space-x-4">
                    <div class="flex-shrink-0 w-10 h-10 bg-white/10 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-white mb-2">登録・利用無料</h3>
                        <p class="text-white/70 text-sm">初期費用なしで今すぐ開始</p>
                    </div>
                </div>
                <div class="flex items-start space-x-4">
                    <div class="flex-shrink-0 w-10 h-10 bg-white/10 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-white mb-2">安心の保証制度</h3>
                        <p class="text-white/70 text-sm">満足いただけない場合は全額返金</p>
                    </div>
                </div>
                <div class="flex items-start space-x-4">
                    <div class="flex-shrink-0 w-10 h-10 bg-white/10 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-white mb-2">最短即日マッチング</h3>
                        <p class="text-white/70 text-sm">AIが最適なマッチングを瞬時に提案</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
</main>

<?php include 'includes/footer.php'; ?>

