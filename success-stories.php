<?php
require_once 'config/config.php';
require_once 'includes/data_stories.php'; // 共通データ読み込み

$pageTitle = '会員インタビュー・実績一覧';
$pageDescription = 'AiNA Worksで人生を変えた、会員たちのリアルな成功ストーリー。未経験からの挑戦、副業での収入アップ、フリーランスとしての独立など、多様な実績をブログ形式でご紹介します。';
include 'includes/header.php';
?>

<!-- Simple Hero -->
<section class="bg-gray-900 border-b border-gray-800 text-white pt-16 pb-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <div class="inline-flex items-center px-4 py-2 rounded-full bg-blue-900/50 border border-blue-700 text-blue-300 font-bold text-sm mb-6">
            AiNA Works SUCCESS STORIES
        </div>
        <h1 class="text-4xl md:text-5xl font-bold mb-6 tracking-tight">
            会員インタビュー・実績
        </h1>
        <p class="text-gray-400 text-lg max-w-2xl mx-auto leading-relaxed">
            AiNA Worksを通じて、会員たちはどのようにキャリアを変えたのか。<br>
            稼いだ実績だけでなく、その裏にあるストーリーや工夫を公開しています。
        </p>
    </div>
</section>

<!-- Filter Navigation -->
<div class="sticky top-0 z-40 bg-white/95 backdrop-blur border-b border-gray-200 shadow-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col md:flex-row md:items-center justify-between py-4 gap-4">
            <div class="flex items-center overflow-x-auto gap-2 no-scrollbar">
                <button class="px-4 py-2 rounded-full bg-blue-600 text-white font-bold text-sm whitespace-nowrap">
                    すべて
                </button>
                <button class="px-4 py-2 rounded-full bg-gray-100 text-gray-600 hover:bg-gray-200 font-medium text-sm whitespace-nowrap transition-colors">
                    動画編集
                </button>
                <button class="px-4 py-2 rounded-full bg-gray-100 text-gray-600 hover:bg-gray-200 font-medium text-sm whitespace-nowrap transition-colors">
                    デザイン・画像
                </button>
                <button class="px-4 py-2 rounded-full bg-gray-100 text-gray-600 hover:bg-gray-200 font-medium text-sm whitespace-nowrap transition-colors">
                    マーケティング
                </button>
                <button class="px-4 py-2 rounded-full bg-gray-100 text-gray-600 hover:bg-gray-200 font-medium text-sm whitespace-nowrap transition-colors">
                    その他
                </button>
            </div>
            
            <div class="flex items-center gap-3">
                <span class="text-sm text-gray-500 hidden md:inline">Tag:</span>
                <select class="bg-transparent text-sm font-medium text-gray-700 border-none focus:ring-0 cursor-pointer">
                    <option>働き方で絞り込み</option>
                    <option>副業からスタート</option>
                    <option>主婦・ママ</option>
                    <option>フリーランス</option>
                </select>
            </div>
        </div>
    </div>
</div>

<!-- Interview Grid -->
<section class="bg-gray-50 py-16 min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 mb-16">
            <?php foreach ($interviews as $story): ?>
                <article class="bg-white rounded-2xl overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300 group flex flex-col h-full border border-gray-100">
                    <!-- Link to Detail -->
                    <a href="success-story-detail.php?id=<?= h($story['id']) ?>" class="block h-full flex flex-col">

                        <!-- Image Area -->
                        <div class="relative h-48 overflow-hidden">
                            <img src="<?= h($story['image']) ?>" alt="" class="w-full h-full object-cover transform group-hover:scale-105 transition-transform duration-500">
                            <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
                            
                            <!-- Badges -->
                            <div class="absolute top-4 left-4 flex gap-2">
                                <span class="px-3 py-1 bg-white/90 backdrop-blur text-xs font-bold text-gray-800 rounded-full">
                                    <?= h($story['category']) ?>
                                </span>
                                <span class="px-3 py-1 bg-black/60 backdrop-blur text-xs font-medium text-white rounded-full">
                                    <?= h($story['type']) ?>
                                </span>
                            </div>
                            
                            <!-- Result Badge (Golden) -->
                            <div class="absolute bottom-4 left-4">
                                <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-gradient-to-r from-yellow-400 to-yellow-600 text-white text-sm font-bold rounded shadow-lg">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <?= h($story['result']) ?>
                                </div>
                            </div>
                        </div>

                        <!-- Content -->
                        <div class="p-6 flex-1 flex flex-col">
                            <div class="text-xs text-gray-400 mb-2 font-medium"><?= h($story['date']) ?></div>
                            <h3 class="text-lg font-bold text-gray-900 leading-snug mb-4 group-hover:text-blue-600 transition-colors line-clamp-3">
                                <?= h($story['title']) ?>
                            </h3>
                            
                            <div class="mt-auto pt-4 border-t border-gray-100 flex items-center">
                                <div class="flex-shrink-0">
                                    <span class="sr-only"><?= h($story['name']) ?></span>
                                    <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center text-gray-500 font-bold overflow-hidden">
                                        <?= mb_substr($story['name'], 0, 1) ?>
                                    </div>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-bold text-gray-900"><?= h($story['name']) ?></p>
                                    <p class="text-xs text-gray-500"><?= h($story['role']) ?></p>
                                </div>
                            </div>
                        </div>

                    </a>
                </article>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <div class="flex justify-center">
            <nav class="inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                <a href="#" class="relative inline-flex items-center px-4 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                    <span class="sr-only">Previous</span>
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                </a>
                <a href="#" aria-current="page" class="z-10 bg-blue-50 border-blue-500 text-blue-600 relative inline-flex items-center px-4 py-2 border text-sm font-medium">
                    1
                </a>
                <a href="#" class="bg-white border-gray-300 text-gray-500 hover:bg-gray-50 relative inline-flex items-center px-4 py-2 border text-sm font-medium">
                    2
                </a>
                <a href="#" class="bg-white border-gray-300 text-gray-500 hover:bg-gray-50 text-gray-700 relative inline-flex items-center px-4 py-2 border text-sm font-medium">
                    3
                </a>
                <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">
                    ...
                </span>
                <a href="#" class="relative inline-flex items-center px-4 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                    <span class="sr-only">Next</span>
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                    </svg>
                </a>
            </nav>
        </div>
        
    </div>
</section>

<?php include 'includes/footer.php'; ?>
