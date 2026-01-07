<?php
require_once 'config/config.php';
require_once 'includes/data_stories.php';

// ID取得
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$story = $interviews[$id] ?? null;

// データがない場合は一覧へリダイレクト（簡易）
if (!$story) {
    header('Location: success-stories.php');
    exit;
}

$pageTitle = $story['title'];
include 'includes/header.php';
?>

<!-- Article Hero -->
<div class="relative bg-gray-900 text-white pb-32">
    <div class="absolute inset-0 z-0">
        <!-- Thumbnail Layout: Standardize image usage to 'image' key -->
        <img src="<?= h($story['image']) ?>" alt="" class="w-full h-full object-cover opacity-30">
        <div class="absolute inset-0 bg-gradient-to-t from-gray-900 via-gray-900/60 to-transparent"></div>
    </div>
    
    <div class="relative z-10 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 pt-32 pb-10 text-center">
        <div class="flex justify-center gap-3 mb-6">
             <span class="px-4 py-1.5 bg-blue-600/90 backdrop-blur rounded-full text-sm font-bold shadow-lg">
                <?= h($story['category']) ?>
            </span>
             <span class="px-4 py-1.5 bg-white/20 backdrop-blur rounded-full text-sm font-medium border border-white/30">
                <?= h($story['type']) ?>
            </span>
        </div>
        
        <h1 class="text-3xl md:text-5xl font-bold leading-tight mb-8">
            <?= h($story['title']) ?>
        </h1>

        <!-- Result Badge Big -->
        <div class="inline-flex items-center gap-3 px-6 py-3 bg-gradient-to-r from-yellow-400 to-yellow-600 text-black text-xl font-black rounded-xl shadow-2xl transform hover:scale-105 transition-transform cursor-default">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <?= h($story['result']) ?>
        </div>
    </div>
</div>

<!-- Article Body -->
<div class="relative z-20 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 -mt-24">
    <div class="bg-white rounded-3xl shadow-xl overflow-hidden">
        
        <!-- Intro -->
        <div class="p-8 md:p-12 border-b border-gray-100 bg-gray-50/50">
            <div class="flex items-center gap-6 mb-6">
                <div class="flex-shrink-0">
                    <span class="sr-only"><?= h($story['name']) ?></span>
                    <div class="h-16 w-16 rounded-full bg-gray-300 flex items-center justify-center text-xl font-bold text-white overflow-hidden shadow-md">
                        <?= mb_substr($story['name'], 0, 1) ?>
                    </div>
                </div>
                <div>
                    <div class="text-lg font-bold text-gray-900"><?= h($story['name']) ?></div>
                    <div class="text-gray-600"><?= h($story['role']) ?></div>
                </div>
            </div>
            <p class="text-lg text-gray-700 leading-relaxed font-medium">
                <?= isset($story['intro']) ? h($story['intro']) : '記事の紹介文がここに入ります。' ?>
            </p>
        </div>

        <!-- Q&A Content -->
        <div class="p-8 md:p-12">
            <?php if (!empty($story['body'])): ?>
                <?php foreach ($story['body'] as $section): ?>
                    <div class="mb-12 last:mb-0">
                        <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-start">
                            <span class="text-blue-200 text-5xl -mt-2 -ml-4 mr-2 select-none">“</span>
                            <span class="relative top-1"><?= h($section['h']) ?></span>
                        </h2>
                        <p class="text-gray-600 leading-loose text-lg">
                            <?= h($section['p']) ?>
                        </p>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Dummy Text for empty articles -->
                <div class="prose max-w-none text-gray-600">
                    <p>この記事の詳細は現在準備中です。インタビュー取材を行っておりますので、公開まで今しばらくお待ちください。</p>
                </div>
            <?php endif; ?>
            
            <div class="mt-12 text-center">
                <a href="success-stories.php" class="inline-flex items-center text-blue-600 font-bold hover:underline">
                    <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    記事一覧に戻る
                </a>
            </div>
        </div>

    </div>
</div>

<!-- Related Articles (Simple Random) -->
<section class="py-20 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h3 class="text-xl font-bold text-gray-900 mb-8">他の記事も読む</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <?php 
            $count = 0;
            foreach ($interviews as $related): 
                if ($related['id'] === $story['id']) continue;
                if ($count >= 3) break;
                $count++;
            ?>
                <a href="success-story-detail.php?id=<?= $related['id'] ?>" class="group block bg-white rounded-xl overflow-hidden shadow-sm hover:shadow-md transition-all">
                    <div class="h-40 overflow-hidden relative">
                        <img src="<?= h($related['image']) ?>" alt="" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                        <div class="absolute bottom-2 left-2">
                             <span class="inline-flex items-center gap-1 px-2 py-1 bg-yellow-500 text-white text-xs font-bold rounded shadow">
                                <?= h($related['result']) ?>
                            </span>
                        </div>
                    </div>
                    <div class="p-4">
                        <h4 class="font-bold text-gray-900 text-sm line-clamp-2 group-hover:text-blue-600 transition-colors">
                            <?= h($related['title']) ?>
                        </h4>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
