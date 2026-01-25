<?php
require_once 'config/config.php';

$pageTitle = 'AiNAのポートフォリオ&マッチングプラットフォーム';
$pageDescription = 'AIスクール生と企業をつなぐ、新しいクリエイティブプラットフォーム';

// データベース接続
$db = Database::getInstance();

// 期限切れ案件の自動終了
updateExpiredJobs();

// 統計情報取得
$stats = [
    'creators' => $db->selectOne("SELECT COUNT(*) as count FROM users WHERE is_creator = 1 AND is_active = 1")['count'] ?? 0,
    'works' => $db->selectOne("SELECT COUNT(*) as count FROM works WHERE status = 'published'")['count'] ?? 0,
    'jobs_completed' => $db->selectOne("SELECT COUNT(*) as count FROM jobs WHERE status = 'completed'")['count'] ?? 0,
    'categories' => $db->selectOne("SELECT COUNT(*) as count FROM categories WHERE is_active = 1")['count'] ?? 0,
    'satisfaction_rate' => $db->selectOne("SELECT ROUND((AVG(rating) / 5) * 100) as rate FROM reviews")['rate'] ?? 0,
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

<!-- Classic Hero Section with Animation -->
<section class="relative min-h-screen flex items-center justify-center overflow-hidden bg-slate-900 text-white">
    <!-- Animated Background Image -->
    <div class="absolute inset-0 z-0">
        <div class="absolute inset-0 bg-cover bg-center animate-ken-burns transform-gpu" 
             style="background-image: url('<?= asset('images/hero-background.jpg') ?>');">
        </div>
        <!-- Gradient Overlay for Readability -->
        <div class="absolute inset-0 bg-gradient-to-br from-slate-900/90 via-blue-900/80 to-slate-900/90 mix-blend-multiply"></div>
        <div class="absolute inset-0 bg-blue-900/30 mix-blend-overlay"></div>
        <div class="absolute inset-0 bg-black/40"></div>
    </div>

    <!-- Content -->
    <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center pt-20">
        <!-- Badge -->
        <div class="inline-flex items-center px-4 py-2 bg-white/10 backdrop-blur-md rounded-full border border-white/20 text-sm font-medium text-white mb-10 shadow-lg animate-fade-in-up">
            <span class="flex h-2 w-2 relative mr-3">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2 w-2 bg-blue-500"></span>
            </span>
            Next Gen Creative Platform
        </div>

        <!-- Main Headline -->
        <h1 class="text-5xl md:text-7xl lg:text-8xl font-bold tracking-tight mb-8 animate-fade-in-up animation-delay-100 drop-shadow-2xl">
            AIスキルで<br class="md:hidden" />
            <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-200 via-white to-blue-200">
                未来を創る
            </span>
        </h1>

        <!-- Subtitle -->
        <p class="mt-6 text-xl text-slate-200 max-w-3xl mx-auto leading-relaxed animate-fade-in-up animation-delay-200 font-medium drop-shadow-md">
            AIスクール生と企業をつなぐ、新しいクリエイティブプラットフォーム。<br class="hidden md:block" />
            あなたの才能と情熱が、ビジネスの未来を加速させます。
        </p>

        <!-- Search Bar -->
        <div class="max-w-3xl mx-auto mt-12 relative group animate-fade-in-up animation-delay-300">
            <form action="<?= url('works') ?>" method="GET" class="relative">
                <div class="relative flex items-center bg-white/10 backdrop-blur-lg border border-white/30 rounded-full p-2 shadow-2xl transition-all duration-300 hover:bg-white/15 focus-within:bg-white/20 focus-within:ring-2 focus-within:ring-blue-400/50">
                    <div class="pl-6 text-slate-300">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input
                        type="text"
                        name="keyword"
                        class="w-full bg-transparent border-none focus:ring-0 text-white placeholder-slate-300 text-lg px-4 py-3 font-medium h-14"
                        placeholder="キーワードで検索 (例: LP制作, 動画編集...)"
                        autocomplete="off"
                    >
                    <button type="submit" class="hidden md:flex items-center gap-2 bg-blue-600 hover:bg-blue-500 text-white px-8 py-3 rounded-full font-bold transition-all shadow-lg hover:shadow-blue-500/30 transform hover:-translate-y-0.5 h-12 mr-1">
                        検索
                    </button>
                </div>
            </form>
        </div>

        <!-- Trust Stats (Simple & Clean) -->
        <div class="mt-20 pt-10 border-t border-white/10 grid grid-cols-2 md:grid-cols-4 gap-8 animate-fade-in-up animation-delay-400">
            <div class="group">
                <div class="text-3xl font-bold text-white"><?= number_format($stats['creators']) ?>+</div>
                <div class="text-sm text-slate-300/80 mt-1 uppercase tracking-wider font-medium">Creators</div>
            </div>
            <div class="group">
                <div class="text-3xl font-bold text-white"><?= number_format($stats['works']) ?>+</div>
                <div class="text-sm text-slate-300/80 mt-1 uppercase tracking-wider font-medium">Works</div>
            </div>
            <div class="group">
                <div class="text-3xl font-bold text-white"><?= number_format($stats['jobs_completed']) ?>+</div>
                <div class="text-sm text-slate-300/80 mt-1 uppercase tracking-wider font-medium">Projects</div>
            </div>
            <div class="group">
                <div class="text-3xl font-bold text-white"><?= $stats['satisfaction_rate'] ?>%</div>
                <div class="text-sm text-slate-300/80 mt-1 uppercase tracking-wider font-medium">Satisfaction</div>
            </div>
        </div>
    </div>

    <!-- Scroll Indicator -->
    <div class="absolute bottom-10 left-1/2 -translate-x-1/2 animate-bounce hidden md:block opacity-70">
        <div class="w-6 h-10 rounded-full border-2 border-white/50 flex justify-center p-1">
            <div class="w-1 h-3 bg-white rounded-full animate-scroll-down"></div>
        </div>
    </div>

    <style>
        @keyframes ken-burns {
            0% { transform: scale(1) translate(0, 0); }
            50% { transform: scale(1.1) translate(-1%, -1%); }
            100% { transform: scale(1) translate(0, 0); }
        }
        .animate-ken-burns {
            animation: ken-burns 30s ease-in-out infinite alternate;
        }
        .animate-fade-in-up {
            animation: fadeInUp 1s cubic-bezier(0.16, 1, 0.3, 1) forwards;
            opacity: 0;
            transform: translateY(20px);
        }
        .animation-delay-100 { animation-delay: 0.1s; }
        .animation-delay-200 { animation-delay: 0.2s; }
        .animation-delay-300 { animation-delay: 0.3s; }
        .animation-delay-400 { animation-delay: 0.4s; }
        
        @keyframes fadeInUp {
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</section>

<!-- Categories Bento Grid Section -->
<section class="py-32 bg-slate-50 relative overflow-hidden" aria-labelledby="categories-heading">
    <div class="absolute inset-0 bg-[url('<?= asset('images/grid.svg') ?>')] opacity-[0.05]"></div>
    
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
        <div class="text-center mb-20 animate-on-scroll">
            <span class="inline-block py-1 px-3 rounded-full bg-blue-100/80 text-blue-600 text-sm font-semibold tracking-wide mb-4">
                Popular Categories
            </span>
            <h2 id="categories-heading" class="text-4xl md:text-5xl font-bold text-slate-900 mb-6 tracking-tight">
                多様な<span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-purple-600">スキル</span>、<br class="md:hidden" />
                無限の可能性
            </h2>
            <p class="text-xl text-slate-500 max-w-2xl mx-auto leading-relaxed">
                AIスクールで磨かれた最先端の技術を持つクリエイターたちが、<br class="hidden md:block" />
                あなたのプロジェクトを次のレベルへ引き上げます。
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php foreach ($categories as $index => $category): ?>
                <?php
                // Generate unique gradients for each card
                $gradients = [
                    'from-blue-500 to-cyan-400',
                    'from-purple-500 to-pink-400',
                    'from-orange-500 to-yellow-400',
                    'from-green-500 to-emerald-400',
                    'from-red-500 to-rose-400',
                    'from-indigo-500 to-violet-400'
                ];
                $gradient = $gradients[$index % count($gradients)];
                ?>
                <a href="<?= url('works?category_id=' . $category['id']) ?>" 
                   class="group relative bg-white rounded-3xl p-8 hover:shadow-2xl hover:shadow-blue-500/10 transition-all duration-500 hover:-translate-y-2 border border-slate-100 overflow-hidden">
                    
                    <!-- Hover Gradient Background -->
                    <div class="absolute inset-0 bg-gradient-to-br <?= $gradient ?> opacity-0 group-hover:opacity-5 transition-opacity duration-500"></div>
                    
                    <div class="relative z-10 flex flex-col items-center">
                        <div class="w-24 h-24 rounded-2xl bg-gradient-to-br <?= $gradient ?> p-[2px] mb-6 transform group-hover:scale-110 group-hover:rotate-6 transition-transform duration-500 shadow-lg group-hover:shadow-<?= explode('-', $gradient)[1] ?>-500/30">
                            <div class="w-full h-full bg-white rounded-xl flex items-center justify-center text-4xl">
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
                        </div>
                        
                        <h3 class="text-xl font-bold text-slate-900 mb-2 group-hover:text-transparent group-hover:bg-clip-text group-hover:bg-gradient-to-r group-hover:<?= $gradient ?> transition-all">
                            <?= h($category['name']) ?>
                        </h3>
                        
                        <p class="text-sm font-medium text-slate-400 mb-6 group-hover:text-slate-500 transition-colors">
                            <?= number_format($category['work_count']) ?> Projects
                        </p>
                        
                        <div class="w-8 h-8 rounded-full bg-slate-50 flex items-center justify-center group-hover:bg-blue-600 group-hover:text-white transition-all duration-300">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
        
        <!-- View All Button -->
        <div class="text-center mt-16">
            <a href="<?= url('works') ?>" class="inline-flex items-center gap-2 px-8 py-4 bg-white border border-slate-200 rounded-full text-slate-700 font-semibold hover:bg-slate-50 hover:border-slate-300 transition-all shadow-sm hover:shadow-md">
                <span>すべてのカテゴリを見る</span>
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                </svg>
            </a>
        </div>
    </div>
</section>

<!-- Premium Featured Works Section -->
<section class="py-32 bg-white relative overflow-hidden" aria-labelledby="works-heading">
    <!-- Subtle Background Elements -->
    <div class="absolute top-0 right-0 w-1/3 h-1/3 bg-gradient-to-br from-purple-50 to-blue-50 rounded-bl-[100px] -z-10"></div>
    <div class="absolute bottom-0 left-0 w-1/4 h-1/4 bg-gradient-to-tr from-pink-50 to-orange-50 rounded-tr-[100px] -z-10"></div>
    
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col md:flex-row md:items-end justify-between mb-16 animate-on-scroll">
            <div>
                <span class="text-blue-600 font-semibold tracking-wider text-sm uppercase mb-2 block">Selected Works</span>
                <h2 id="works-heading" class="text-4xl md:text-5xl font-bold text-slate-900 leading-tight">
                    厳選された<br />
                    <span class="relative inline-block">
                        <span class="relative z-10">クリエイティブ</span>
                        <span class="absolute bottom-2 left-0 w-full h-3 bg-blue-100 -z-10 transform -rotate-1"></span>
                    </span>
                </h2>
            </div>
            <p class="mt-6 md:mt-0 text-slate-500 max-w-md text-right md:text-left">
                プロフェッショナルによる最高品質の作品群。<br />
                あなたのインスピレーションを刺激します。
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-10">
            <?php foreach ($featuredWorks as $work): ?>
                <div class="group relative flex flex-col gap-4 animate-on-scroll">
                    <!-- Image Card -->
                    <div class="relative aspect-[4/3] rounded-2xl overflow-hidden bg-slate-100">
                        <img src="<?= h($work['main_image']) ?>" 
                             alt="<?= h($work['title']) ?>" 
                             class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105 will-change-transform">
                        
                        <!-- Overlay Actions -->
                        <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center justify-center gap-4 backdrop-blur-[2px]">
                            <a href="<?= url('work-detail?id=' . $work['id']) ?>" 
                               class="px-6 py-3 bg-white text-slate-900 rounded-full font-medium transform translate-y-4 opacity-0 group-hover:translate-y-0 group-hover:opacity-100 transition-all duration-300 delay-75 hover:bg-blue-50">
                                詳細を見る
                            </a>
                            <button onclick="toggleLike('work', <?= $work['id'] ?>, this)" 
                                    class="p-3 bg-white/20 backdrop-blur-md border border-white/30 text-white rounded-full transform translate-y-4 opacity-0 group-hover:translate-y-0 group-hover:opacity-100 transition-all duration-300 delay-100 hover:bg-white hover:text-red-500">
                                <svg class="w-5 h-5" fill="<?= isset($work['is_liked']) && $work['is_liked'] ? 'currentColor' : 'none' ?>" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                </svg>
                            </button>
                        </div>

                        <!-- Category Badge -->
                        <span class="absolute top-4 left-4 px-3 py-1 bg-white/90 backdrop-blur-md rounded-full text-xs font-semibold text-slate-700">
                            <?= h($work['category_name']) ?>
                        </span>
                    </div>

                    <!-- Content -->
                    <div class="space-y-2">
                        <div class="flex justify-between items-start">
                            <h3 class="text-xl font-bold text-slate-900 leading-snug group-hover:text-blue-600 transition-colors">
                                <a href="<?= url('work-detail?id=' . $work['id']) ?>">
                                    <?= h($work['title']) ?>
                                </a>
                            </h3>
                            <div class="flex items-center gap-1 text-slate-700 font-semibold bg-slate-50 px-2 py-1 rounded-lg">
                                <span class="text-xs text-slate-400">¥</span>
                                <?= number_format($work['price_min']) ?>
                            </div>
                        </div>

                        <div class="flex items-center justify-between pt-2">
                            <div class="flex items-center gap-2">
                                <img src="<?= h($work['creator_image'] ?? asset('images/default-avatar.png')) ?>" 
                                     alt="<?= h($work['creator_name']) ?>" 
                                     class="w-6 h-6 rounded-full object-cover ring-2 ring-white shadow-sm">
                                <span class="text-sm text-slate-600"><?= h($work['creator_name']) ?></span>
                            </div>
                            <div class="flex items-center gap-4 text-xs text-slate-400">
                                <span class="flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    <?= number_format($work['view_count']) ?>
                                </span>
                                <span class="flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24" class="text-yellow-400">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                    </svg>
                                    <?= number_format($work['avg_rating'] ?? 0, 1) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- View All Button -->
        <div class="text-center mt-20">
            <a href="<?= url('works') ?>" class="inline-flex items-center gap-2 text-slate-900 font-bold border-b-2 border-slate-900 pb-1 hover:text-blue-600 hover:border-blue-600 transition-all">
                <span>すべての作品を見る</span>
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                </svg>
            </a>
        </div>
    </div>
</section>

<!-- Premium Stats Section -->
<section class="py-24 relative overflow-hidden bg-slate-900 text-white" aria-labelledby="stats-heading">
    <!-- Abstract Shapes -->
    <div class="absolute inset-0 overflow-hidden">
        <div class="absolute -top-[20%] -left-[10%] w-[50%] h-[50%] bg-blue-600/20 rounded-full blur-[100px]"></div>
        <div class="absolute top-[30%] -right-[10%] w-[40%] h-[40%] bg-purple-600/20 rounded-full blur-[100px]"></div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-16 items-center">
            <div class="animate-on-scroll">
                <span class="text-blue-400 font-semibold tracking-wider text-sm uppercase mb-4 block">Proven Track Record</span>
                <h2 id="stats-heading" class="text-4xl md:text-5xl font-bold mb-6 leading-tight">
                    数字で見る<br />
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-purple-400">AiNA Works</span>の信頼
                </h2>
                <p class="text-slate-400 text-lg leading-relaxed mb-8">
                    多くのクリエイターと企業様にご利用いただき、<br />
                    確かな実績と信頼を積み重ねてきました。
                </p>
                
                <div class="grid grid-cols-2 gap-8">
                    <div>
                        <div class="text-4xl font-bold text-white mb-2"><?= number_format($stats['creators']) ?>+</div>
                        <div class="text-sm text-slate-400">登録クリエイター</div>
                    </div>
                    <div>
                        <div class="text-4xl font-bold text-white mb-2"><?= number_format($stats['works']) ?>+</div>
                        <div class="text-sm text-slate-400">公開作品数</div>
                    </div>
                    <div>
                        <div class="text-4xl font-bold text-white mb-2"><?= number_format($stats['jobs_completed']) ?>+</div>
                        <div class="text-sm text-slate-400">マッチング成立</div>
                    </div>
                    <div>
                        <div class="text-4xl font-bold text-white mb-2"><?= $stats['satisfaction_rate'] ?>%</div>
                        <div class="text-sm text-slate-400">クライアント満足度</div>
                    </div>
                </div>
            </div>
            
            <div class="relative animate-on-scroll" style="transition-delay: 200ms;">
                <!-- Floating Cards Visualization -->
                <div class="relative w-full aspect-square md:aspect-auto md:h-[500px]">
                    <div class="absolute top-10 left-10 w-48 h-48 bg-white/5 backdrop-blur-xl border border-white/10 rounded-2xl p-6 flex flex-col justify-between transform -rotate-6 hover:rotate-0 transition-transform duration-500 hover:z-20 hover:scale-105 shadow-2xl">
                        <div class="w-10 h-10 rounded-full bg-blue-500/20 flex items-center justify-center text-blue-400">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z" /></svg>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-white">Creators</div>
                            <div class="text-xs text-slate-400 mt-1">Top Tier Talent</div>
                        </div>
                    </div>
                    
                    <div class="absolute top-1/4 right-0 w-56 h-56 bg-white/5 backdrop-blur-xl border border-white/10 rounded-2xl p-8 flex flex-col justify-between transform rotate-12 hover:rotate-0 transition-transform duration-500 hover:z-20 hover:scale-105 shadow-2xl z-10">
                        <div class="w-12 h-12 rounded-full bg-purple-500/20 flex items-center justify-center text-purple-400">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                        </div>
                        <div>
                            <div class="text-3xl font-bold text-white">Works</div>
                            <div class="text-xs text-slate-400 mt-2">High Quality Portfolio</div>
                        </div>
                    </div>

                    <div class="absolute bottom-10 left-20 w-64 h-40 bg-white/5 backdrop-blur-xl border border-white/10 rounded-2xl p-6 flex items-center gap-4 transform -rotate-3 hover:rotate-0 transition-transform duration-500 hover:z-20 hover:scale-105 shadow-2xl">
                        <div class="w-16 h-16 rounded-full bg-green-500/20 flex items-center justify-center text-green-400 shrink-0">
                            <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                        <div>
                            <div class="text-white font-bold text-lg">Satisfaction</div>
                            <div class="text-slate-400 text-sm">Consistent Quality</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Magnetic CTA Section -->
<section class="py-32 relative flex items-center justify-center overflow-hidden bg-white text-center">
    <div class="absolute inset-0 opacity-[0.03] bg-[url('<?= asset('images/grid.svg') ?>')]"></div>
    
    <div class="max-w-4xl mx-auto px-4 relative z-10 animate-on-scroll">
        <h2 class="text-5xl md:text-7xl font-bold text-slate-900 mb-8 tracking-tight">
            Ready to <span class="bg-clip-text text-transparent bg-gradient-to-r from-blue-600 to-purple-600">Start?</span>
        </h2>
        
        <p class="text-xl md:text-2xl text-slate-500 mb-12 max-w-2xl mx-auto">
            あなたの才能が、誰かの未来を変える。<br />
            まずは無料で、新しい一歩を踏み出しましょう。
        </p>
        
        <div class="flex flex-col sm:flex-row gap-6 justify-center items-center">
            <!-- Buttons are rendered by header, but we can add secondary CTA here if needed -->
            <!-- Using a 'magnetic' button style -->
            <a href="<?= url('register') ?>" class="group relative px-8 py-4 bg-slate-900 rounded-full text-white font-bold text-lg overflow-hidden shadow-2xl hover:shadow-blue-500/50 transition-shadow duration-300">
                <div class="absolute inset-0 w-full h-full bg-gradient-to-r from-blue-600 to-purple-600 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                <span class="relative flex items-center gap-2">
                    今すぐ登録する
                    <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                    </svg>
                </span>
            </a>
            
            <a href="<?= url('about') ?>" class="text-slate-500 hover:text-slate-900 font-medium transition-colors">
                AiNA Worksについて知る
            </a>
        </div>
    </div>
</section>

<script>
// いいね機能（トップページ用）
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
            const svg = button.querySelector('svg');
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
        } else {
            alert(result.message || 'エラーが発生しました');
        }
    } catch (e) {
        console.error(e);
        alert('いいね機能でエラーが発生しました');
    }
}
</script>

<?php include 'includes/footer.php'; ?>
