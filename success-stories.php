<?php
require_once 'config/config.php';
require_once 'includes/database.php'; 
require_once 'includes/functions.php';

// データベース接続 & 記事取得（カテゴリ・タグで絞り込み、12件ずつ）
$interviews = [];
$categoriesList = [];
$tagsList = [];
$filterCategory = is_string($_GET['category'] ?? null) ? trim($_GET['category']) : '';
$filterTag = is_string($_GET['tag'] ?? null) ? trim($_GET['tag']) : '';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;
$pagination = calculatePagination(0, $perPage, 1);
try {
    $db = Database::getInstance();
    $categoriesList = array_column($db->select("SELECT DISTINCT category_name FROM success_stories WHERE status = 'published' AND category_name IS NOT NULL AND category_name <> '' ORDER BY category_name"), 'category_name');
    $tagsList = array_column($db->select("SELECT DISTINCT tag_type FROM success_stories WHERE status = 'published' AND tag_type IS NOT NULL AND tag_type <> '' ORDER BY tag_type"), 'tag_type');

    $where = ["status = 'published'"];
    $params = [];
    if ($filterCategory !== '' && in_array($filterCategory, $categoriesList, true)) {
        $where[] = 'category_name = ?';
        $params[] = $filterCategory;
    } else {
        $filterCategory = '';
    }
    if ($filterTag !== '' && in_array($filterTag, $tagsList, true)) {
        $where[] = 'tag_type = ?';
        $params[] = $filterTag;
    } else {
        $filterTag = '';
    }
    $whereSql = implode(' AND ', $where);
    $total = (int)($db->selectOne("SELECT COUNT(*) AS c FROM success_stories WHERE {$whereSql}", $params)['c'] ?? 0);
    $pagination = calculatePagination($total, $perPage, $page);
    $offset = max(0, (int)$pagination['offset']);
    $interviews = $db->select("SELECT * FROM success_stories WHERE {$whereSql} ORDER BY interview_date DESC LIMIT {$perPage} OFFSET {$offset}", $params);
} catch (Exception $e) {
    error_log("Database Error: " . $e->getMessage());
}
$storyListUrl = function (array $override) use ($filterCategory, $filterTag) {
    $q = array_filter(array_merge(['category' => $filterCategory, 'tag' => $filterTag], $override), fn($v) => $v !== '' && $v !== null && $v !== 1);
    return url('success-stories' . ($q ? '?' . http_build_query($q) : ''));
};

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
<?php if (!empty($categoriesList) || !empty($tagsList)): ?>
<div class="bg-white border-b border-gray-200 shadow-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col md:flex-row md:items-center justify-between py-4 gap-4">
            <div class="flex items-center overflow-x-auto gap-2 no-scrollbar">
                <a href="<?= h($storyListUrl(['category' => '', 'page' => null])) ?>" class="px-4 py-2 rounded-full text-sm whitespace-nowrap <?= $filterCategory === '' ? 'bg-blue-600 text-white font-bold' : 'bg-gray-100 text-gray-600 hover:bg-gray-200 font-medium' ?>">すべて</a>
                <?php foreach ($categoriesList as $cat): ?>
                    <a href="<?= h($storyListUrl(['category' => $cat, 'page' => null])) ?>" class="px-4 py-2 rounded-full text-sm whitespace-nowrap <?= $filterCategory === $cat ? 'bg-blue-600 text-white font-bold' : 'bg-gray-100 text-gray-600 hover:bg-gray-200 font-medium' ?>"><?= h($cat) ?></a>
                <?php endforeach; ?>
            </div>
            <?php if (!empty($tagsList)): ?>
            <form method="GET" action="<?= h(url('success-stories')) ?>" class="flex items-center gap-3">
                <?php if ($filterCategory !== ''): ?><input type="hidden" name="category" value="<?= h($filterCategory) ?>"><?php endif; ?>
                <label for="story-tag" class="text-sm text-gray-500">働き方</label>
                <select id="story-tag" name="tag" onchange="this.form.submit()" class="bg-white text-sm font-medium text-gray-700 border border-gray-200 rounded-lg px-3 py-2 cursor-pointer">
                    <option value="">すべて</option>
                    <?php foreach ($tagsList as $tg): ?>
                        <option value="<?= h($tg) ?>" <?= $filterTag === $tg ? 'selected' : '' ?>><?= h($tg) ?></option>
                    <?php endforeach; ?>
                </select>
                <noscript><button type="submit" class="text-sm text-blue-600">絞り込む</button></noscript>
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Interview Grid -->
<section class="bg-gray-50 py-16 min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 mb-16">
            <?php if (!empty($interviews) && is_array($interviews)): ?>
                <?php foreach ($interviews as $story): ?>
                    <article class="bg-white rounded-2xl overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300 group flex flex-col h-full border border-gray-100">
                        <!-- Link to Detail -->
                        <a href="<?= h(url('success-story-detail?id=' . (int)$story['id'])) ?>" class="block h-full flex flex-col">

                            <!-- Image Area -->
                            <div class="relative h-48 overflow-hidden">
                                <img src="<?= h(uploaded_asset($story['main_image'])) ?>" alt="" class="w-full h-full object-cover transform group-hover:scale-105 transition-transform duration-500">
                                <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
                                
                                <!-- Badges -->
                                <div class="absolute top-4 left-4 flex gap-2">
                                    <span class="px-3 py-1 bg-white/90 backdrop-blur text-xs font-bold text-gray-800 rounded-full">
                                        <?= h($story['category_name']) ?>
                                    </span>
                                    <span class="px-3 py-1 bg-black/60 backdrop-blur text-xs font-medium text-white rounded-full">
                                        <?= h($story['tag_type']) ?>
                                    </span>
                                </div>
                                
                                <!-- Result Badge (Golden) -->
                                <div class="absolute bottom-4 left-4">
                                    <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-gradient-to-r from-yellow-400 to-yellow-600 text-white text-sm font-bold rounded shadow-lg">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <?= h($story['result_badge']) ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Content -->
                            <div class="p-6 flex-1 flex flex-col">
                                <div class="text-xs text-gray-400 mb-2 font-medium"><?= h(date('Y.m.d', strtotime($story['interview_date']))) ?></div>
                                <h3 class="text-lg font-bold text-gray-900 leading-snug mb-4 group-hover:text-blue-600 transition-colors line-clamp-3">
                                    <?= h($story['title']) ?>
                                </h3>
                                
                                <div class="mt-auto pt-4 border-t border-gray-100 flex items-center">
                                    <div class="flex-shrink-0">
                                        <span class="sr-only"><?= h($story['member_name']) ?></span>
                                        <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center text-gray-500 font-bold overflow-hidden">
                                            <?= h(mb_substr((string)$story['member_name'], 0, 1)) ?>
                                        </div>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm font-bold text-gray-900"><?= h($story['member_name']) ?></p>
                                        <p class="text-xs text-gray-500"><?= h($story['member_role']) ?></p>
                                    </div>
                                </div>
                            </div>

                        </a>
                    </article>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-span-3 text-center py-12 text-gray-500">
                    記事が見つかりませんでした。
                </div>
            <?php endif; ?>
        </div>

        <?php if (($pagination['total_pages'] ?? 1) > 1): ?>
        <nav class="flex justify-center items-center gap-2" aria-label="ページ送り">
            <?php if ($pagination['has_prev']): ?>
                <a href="<?= h($storyListUrl(['page' => $pagination['prev_page']])) ?>" class="px-4 py-2 rounded-lg border border-gray-300 bg-white text-sm text-gray-700 hover:bg-gray-50">前へ</a>
            <?php endif; ?>
            <span class="px-4 py-2 text-sm text-gray-600"><?= (int)$pagination['current_page'] ?> / <?= (int)$pagination['total_pages'] ?></span>
            <?php if ($pagination['has_next']): ?>
                <a href="<?= h($storyListUrl(['page' => $pagination['next_page']])) ?>" class="px-4 py-2 rounded-lg border border-gray-300 bg-white text-sm text-gray-700 hover:bg-gray-50">次へ</a>
            <?php endif; ?>
        </nav>
        <?php endif; ?>
        
    </div>
</section>

<?php include 'includes/footer.php'; ?>
