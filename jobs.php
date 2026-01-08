<?php
require_once 'config/config.php';

$pageTitle = '案件一覧';
$pageDescription = '豊富な案件から自分にピッタリの仕事を見つけよう';

// データベース接続
$db = Database::getInstance();

// 検索・フィルター条件
$keyword = trim($_GET['keyword'] ?? '');
$categoryId = trim($_GET['category_id'] ?? '');
$budgetMin = trim($_GET['budget_min'] ?? '');
$budgetMax = trim($_GET['budget_max'] ?? '');
$location = trim($_GET['location'] ?? '');
$urgency = trim($_GET['urgency'] ?? '');
$status = trim($_GET['status'] ?? '');
$sortBy = $_GET['sort'] ?? 'newest';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;

// 検索条件構築
$conditions = [];
$values = [];

if ($keyword !== '') {
    $conditions[] = "(j.title LIKE ? OR j.description LIKE ?)";
    $values[] = "%{$keyword}%";
    $values[] = "%{$keyword}%";
}

if ($categoryId !== '') {
    $conditions[] = "j.category_id = ?";
    $values[] = $categoryId;
}

if ($budgetMin !== '') {
    $conditions[] = "j.budget_max >= ?";
    $values[] = $budgetMin;
}

if ($budgetMax !== '') {
    $conditions[] = "j.budget_min <= ?";
    $values[] = $budgetMax;
}

if ($location !== '') {
    $conditions[] = "j.location LIKE ?";
    $values[] = "%{$location}%";
}


if ($urgency !== '') {
    $conditions[] = "j.urgency = ?";
    $values[] = $urgency;
}

if ($status !== '') {
    $conditions[] = "j.status = ?";
    $values[] = $status;
}

$whereClause = !empty($conditions) ? 'AND ' . implode(' AND ', $conditions) : '';

// ソート条件
$orderBy = match($sortBy) {
    'budget_high' => 'j.budget_max DESC',
    'budget_low' => 'j.budget_min ASC',
    'deadline' => 'j.deadline ASC',
    'applications' => 'j.applications_count ASC',
    default => 'j.created_at DESC'
};

try {
    // カテゴリ一覧取得
    $categories = $db->select("
        SELECT * FROM categories 
        WHERE is_active = 1 
        ORDER BY sort_order ASC
    ");

    // 総件数取得（全ステータス対象）
    $totalSql = "
        SELECT COUNT(*) as total
        FROM jobs j
        WHERE 1=1
        {$whereClause}
    ";
    
    $total = $db->selectOne($totalSql, $values)['total'] ?? 0;
    
    // ページネーション計算
    $pagination = calculatePagination($total, $perPage, $page);
    
    // 案件一覧取得（全ステータス）
    $jobsSql = "
        SELECT j.*, 
               u.full_name as client_name, 
               u.profile_image as client_image,
               c.name as category_name,
               c.color as category_color
        FROM jobs j
        JOIN users u ON j.client_id = u.id
        LEFT JOIN categories c ON j.category_id = c.id
        WHERE 1=1
        {$whereClause}
        ORDER BY {$orderBy}
        LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}
    ";
    
    $jobs = $db->select($jobsSql, $values);

} catch (Exception $e) {
    $jobs = [];
    $total = 0;
    $pagination = calculatePagination(0, $perPage, 1);
    setFlash('error', 'データの取得に失敗しました。');
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
        <div class="absolute inset-0 bg-gradient-to-r from-slate-900/95 via-blue-900/90 to-slate-900/95 mix-blend-multiply"></div>
        <div class="absolute inset-0 bg-black/30"></div>
    </div>

    <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-4xl md:text-5xl font-bold tracking-tight mb-4 animate-fade-in-up">
            案件一覧
            <span class="block text-lg md:text-xl font-medium text-blue-200 mt-2 tracking-widest opacity-80">PROJECTS</span>
        </h1>
        <p class="text-lg text-slate-300 max-w-2xl mx-auto leading-relaxed animate-fade-in-up animation-delay-100">
            あなたのスキルを活かせる最適な案件がここに。<br class="hidden md:inline">
            新しい挑戦で、キャリアを次のステージへ。
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

<!-- Glassmorphism Search & Sorting Section -->
<section class="relative -mt-8 z-20 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
    <div class="bg-white rounded-2xl shadow-xl border border-slate-100 p-6 md:p-8 backdrop-blur-xl bg-opacity-95">
        <form method="GET" id="job-search-form" class="space-y-6" onsubmit="return handleJobSearchSubmit(event)">
            
            <!-- Search Bar & Primary Actions -->
            <div class="flex flex-col md:flex-row gap-4">
                <div class="flex-1 relative group">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <svg class="h-6 w-6 text-slate-400 group-focus-within:text-blue-500 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input type="text" 
                           name="keyword" 
                           value="<?= h($keyword) ?>"
                           placeholder="案件名、キーワード、技術要素などを入力..."
                           class="w-full pl-12 pr-4 py-4 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all font-medium text-slate-700 placeholder-slate-400 text-lg">
                </div>
                <button type="submit" 
                        class="px-10 py-4 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-xl hover:from-blue-700 hover:to-indigo-700 shadow-lg hover:shadow-blue-500/30 transition-all transform hover:-translate-y-0.5 font-bold text-lg flex items-center justify-center gap-2">
                    検索する
                </button>
            </div>

            <div class="h-px bg-slate-100 w-full my-6"></div>

            <!-- Filters Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                
                <!-- Category Filter -->
                <div class="relative">
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5 ml-1">カテゴリ</label>
                    <select name="category_id" class="w-full pl-4 pr-10 py-3 bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-slate-700 font-medium appearance-none cursor-pointer hover:bg-slate-50 transition-colors" onchange="handleFilterChange(this)">
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

                <!-- Status Filter -->
                <div class="relative">
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5 ml-1">ステータス</label>
                    <select name="status" class="w-full pl-4 pr-10 py-3 bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-slate-700 font-medium appearance-none cursor-pointer hover:bg-slate-50 transition-colors" onchange="handleFilterChange(this)">
                        <option value="">全ステータス</option>
                        <option value="open" <?= $status == 'open' ? 'selected' : '' ?>>🟢 募集中</option>
                        <option value="in_progress" <?= $status == 'in_progress' ? 'selected' : '' ?>>🔵 進行中</option>
                        <option value="completed" <?= $status == 'completed' ? 'selected' : '' ?>>⚫ 完了</option>
                        <option value="closed" <?= $status == 'closed' ? 'selected' : '' ?>>🟡 募集終了</option>
                    </select>
                    <div class="pointer-events-none absolute bottom-0 right-0 flex items-center px-4 py-3.5 text-slate-500">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                    </div>
                </div>

                <!-- Budget Filter -->
                <div class="relative">
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5 ml-1">ご予算</label>
                    <select name="budget_min" class="w-full pl-4 pr-10 py-3 bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-slate-700 font-medium appearance-none cursor-pointer hover:bg-slate-50 transition-colors" onchange="handleFilterChange(this)">
                        <option value="">下限なし</option>
                        <option value="10000" <?= $budgetMin == '10000' ? 'selected' : '' ?>>1万円以上</option>
                        <option value="50000" <?= $budgetMin == '50000' ? 'selected' : '' ?>>5万円以上</option>
                        <option value="100000" <?= $budgetMin == '100000' ? 'selected' : '' ?>>10万円以上</option>
                        <option value="300000" <?= $budgetMin == '300000' ? 'selected' : '' ?>>30万円以上</option>
                    </select>
                    <div class="pointer-events-none absolute bottom-0 right-0 flex items-center px-4 py-3.5 text-slate-500">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                    </div>
                </div>

                <!-- Urgency Filter -->
                <div class="relative">
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5 ml-1">緊急度</label>
                    <select name="urgency" class="w-full pl-4 pr-10 py-3 bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-slate-700 font-medium appearance-none cursor-pointer hover:bg-slate-50 transition-colors" onchange="handleFilterChange(this)">
                        <option value="">指定なし</option>
                        <option value="low" <?= $urgency == 'low' ? 'selected' : '' ?>>低め</option>
                        <option value="medium" <?= $urgency == 'medium' ? 'selected' : '' ?>>通常</option>
                        <option value="high" <?= $urgency == 'high' ? 'selected' : '' ?>>急募 🔥</option>
                    </select>
                    <div class="pointer-events-none absolute bottom-0 right-0 flex items-center px-4 py-3.5 text-slate-500">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                    </div>
                </div>

                <!-- Sort -->
                <div class="relative">
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5 ml-1">並び替え</label>
                    <select name="sort" class="w-full pl-4 pr-10 py-3 bg-slate-50 border border-slate-200/80 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-slate-700 font-medium appearance-none cursor-pointer hover:bg-slate-100 transition-colors" onchange="handleFilterChange(this)">
                        <option value="newest" <?= $sortBy == 'newest' ? 'selected' : '' ?>>新着順 ⚡</option>
                        <option value="budget_high" <?= $sortBy == 'budget_high' ? 'selected' : '' ?>>予算が高い順 💰</option>
                        <option value="deadline" <?= $sortBy == 'deadline' ? 'selected' : '' ?>>締切が近い順 ⏰</option>
                        <option value="applications" <?= $sortBy == 'applications' ? 'selected' : '' ?>>応募が少ない順 🎯</option>
                    </select>
                    <div class="pointer-events-none absolute bottom-0 right-0 flex items-center px-4 py-3.5 text-slate-500">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                    </div>
                </div>

            </div>
        </form>
    </div>
</section>

<script>
// フィルター変更時の処理
function handleFilterChange(changedElement) {
    const form = document.getElementById('job-search-form');
    if (!form) return;
    
    const params = new URLSearchParams();
    
    // フォームのすべての入力要素を直接取得
    const inputs = form.querySelectorAll('input[name], select[name]');
    
    inputs.forEach(function(element) {
        const name = element.name;
        let value = '';
        
        if (element.tagName === 'SELECT') {
            value = element.value ? element.value.trim() : '';
        } else if (element.tagName === 'INPUT') {
            value = element.value ? element.value.trim() : '';
        }
        
        // 変更された要素の値は確実に反映
        if (changedElement && changedElement.name === name) {
            value = changedElement.value ? changedElement.value.trim() : '';
        }
        
        // 空でない値のみをパラメータに追加
        if (value !== '') {
            params.set(name, value);
        }
    });
    
    // ページパラメータをリセット（新しい検索時は1ページ目に戻る）
    params.delete('page');
    
    // URLを構築してリダイレクト
    const baseUrl = window.location.pathname;
    const queryString = params.toString();
    const newUrl = queryString ? baseUrl + '?' + queryString : baseUrl;
    
    window.location.href = newUrl;
}

// フォーム送信時の処理
function handleJobSearchSubmit(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    const params = new URLSearchParams();
    
    // 空でない値のみをパラメータに追加
    for (const [key, value] of formData.entries()) {
        if (value && value.trim() !== '') {
            params.append(key, value.trim());
        }
    }
    
    // ページパラメータをリセット（新しい検索時は1ページ目に戻る）
    params.delete('page');
    
    // URLを構築してリダイレクト
    const baseUrl = window.location.pathname;
    const queryString = params.toString();
    const newUrl = queryString ? baseUrl + '?' + queryString : baseUrl;
    
    window.location.href = newUrl;
    
    return false;
}
</script>

<!-- Jobs List -->
<section class="py-12 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Results Info -->
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-900">
                <?= number_format($total) ?>件の案件が見つかりました
            </h2>
            <?php if ($keyword || $categoryId || $budgetMin || $location || $urgency || $status): ?>
                <a href="<?= url('jobs') ?>" class="text-purple-600 hover:text-purple-700 font-medium">
                    検索条件をクリア
                </a>
            <?php endif; ?>
        </div>
        
        <!-- Active Filters Display -->
        <?php if ($keyword || $categoryId || $budgetMin || $location || $urgency || $status): ?>
            <div class="mb-6 p-4 bg-gray-50 border border-gray-200 rounded-lg">
                <h3 class="text-sm font-medium text-gray-900 mb-3">適用中のフィルター:</h3>
                <div class="flex flex-wrap gap-2">
                    <?php if ($keyword): ?>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800 border border-purple-200">
                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"></path></svg>
                            キーワード: "<?= h($keyword) ?>"
                        </span>
                    <?php endif; ?>
                    
                    <?php if ($categoryId): 
                        $selectedCategory = array_filter($categories, fn($cat) => $cat['id'] == $categoryId);
                        $categoryName = !empty($selectedCategory) ? array_values($selectedCategory)[0]['name'] : 'カテゴリ';
                    ?>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 border border-blue-200">
                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path d="M7 3a1 1 0 000 2h6a1 1 0 100-2H7zM4 7a1 1 0 011-1h10a1 1 0 110 2H5a1 1 0 01-1-1zM2 11a2 2 0 012-2h12a2 2 0 012 2v4a2 2 0 01-2 2H4a2 2 0 01-2-2v-4z"></path></svg>
                            カテゴリ: <?= h($categoryName) ?>
                        </span>
                    <?php endif; ?>
                    
                    <?php if ($status): 
                        $statusLabels = [
                            'open' => '🟢 募集中',
                            'closed' => '🟡 募集終了', 
                            'contracted' => '🔵 契約済み',
                            'delivered' => '🟣 納品済み',
                            'approved' => '🟦 検収済み',
                            'cancelled' => '🔴 キャンセル',
                            'in_progress' => '🔵 進行中',
                            'completed' => '⚫ 完了'
                        ];
                        $statusLabel = $statusLabels[$status] ?? $status;
                    ?>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 border border-green-200">
                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                            ステータス: <?= $statusLabel ?>
                        </span>
                    <?php endif; ?>
                    
                    <?php if ($budgetMin): ?>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 border border-yellow-200">
                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"></path><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"></path></svg>
                            予算: <?= number_format($budgetMin) ?>円以上
                        </span>
                    <?php endif; ?>
                    
                    <?php if ($urgency): 
                        $urgencyLabels = ['low' => '低', 'medium' => '中', 'high' => '高'];
                        $urgencyLabel = $urgencyLabels[$urgency] ?? $urgency;
                    ?>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800 border border-orange-200">
                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                            緊急度: <?= $urgencyLabel ?>
                        </span>
                    <?php endif; ?>
                    
                    <?php if ($location): ?>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800 border border-indigo-200">
                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path></svg>
                            場所: "<?= h($location) ?>"
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if (empty($jobs)): ?>
            <!-- Premium Empty State -->
            <div class="text-center py-24 px-6">
                <div class="relative w-32 h-32 mx-auto mb-8 group">
                    <div class="absolute inset-0 bg-blue-100 rounded-full animate-ping opacity-20"></div>
                    <div class="relative w-32 h-32 bg-gradient-to-br from-slate-50 to-white rounded-full flex items-center justify-center shadow-lg border border-slate-100">
                        <svg class="h-12 w-12 text-slate-300 group-hover:text-blue-500 transition-colors duration-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                </div>
                <h3 class="text-2xl font-bold text-slate-900 mb-3">案件が見つかりませんでした</h3>
                <p class="text-slate-500 mb-8 max-w-md mx-auto leading-relaxed">
                    現在の条件に一致する案件はありませんでした。<br>条件を変更して再度検索してみてください。
                </p>
                <a href="<?= url('jobs') ?>" class="inline-flex items-center px-8 py-3 bg-white text-slate-700 font-medium rounded-xl border border-slate-200 hover:bg-slate-50 hover:border-blue-300 hover:text-blue-600 transition-all shadow-sm hover:shadow-md">
                    すべての案件を表示
                </a>
            </div>
        <?php else: ?>
            <!-- Jobs Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-16">
                <?php foreach ($jobs as $job): ?>
                    <div class="group relative bg-white rounded-2xl p-6 hover:shadow-2xl transition-all duration-300 hover:-translate-y-1 border border-slate-100 overflow-hidden">
                        <!-- Gradient Border Effect on Hover -->
                        <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-blue-500 via-purple-500 to-pink-500 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                        
                        <!-- Job Header -->
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex-1 pr-4">
                                <div class="flex flex-wrap items-center gap-2 mb-3">
                                    <?php if ($job['category_name']): ?>
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100">
                                            <?= h($job['category_name']) ?>
                                        </span>
                                    <?php endif; ?>
                                    
                                    <?php
                                    $urgencyColors = [
                                        'low' => 'bg-slate-100 text-slate-600',
                                        'medium' => 'bg-yellow-50 text-yellow-700 border border-yellow-100',
                                        'high' => 'bg-rose-50 text-rose-700 border border-rose-100 animate-pulse'
                                    ];
                                    $urgencyLabels = ['low' => '低', 'medium' => '中', 'high' => '急募 🔥'];
                                    ?>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium <?= $urgencyColors[$job['urgency']] ?? 'bg-slate-100 text-slate-600' ?>">
                                        緊急度: <?= $urgencyLabels[$job['urgency']] ?? '普通' ?>
                                    </span>
                                </div>
                                
                                <h3 class="text-xl font-bold text-slate-900 mb-2 line-clamp-2 group-hover:text-blue-600 transition-colors">
                                    <a href="<?= url('job-detail?id=' . $job['id']) ?>" class="block">
                                        <?= h($job['title']) ?>
                                    </a>
                                </h3>
                            </div>
                        </div>

                        <!-- Job Description -->
                        <p class="text-slate-500 mb-6 line-clamp-2 text-sm leading-relaxed">
                            <?= h(mb_substr($job['description'], 0, 150)) ?><?= mb_strlen($job['description']) > 150 ? '...' : '' ?>
                        </p>

                        <!-- Key Details Grid -->
                        <div class="grid grid-cols-2 gap-4 mb-6 bg-slate-50 rounded-xl p-4 border border-slate-100/50">
                            <!-- Budget -->
                            <div class="flex items-center text-sm">
                                <div class="w-8 h-8 rounded-lg bg-blue-100/50 flex items-center justify-center mr-3 text-blue-600">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" /></svg>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-400 mb-0.5">予算</p>
                                    <p class="font-bold text-slate-700"><?= formatPrice($job['budget_min']) ?>~</p>
                                </div>
                            </div>
                            
                            <!-- Deadline -->
                            <?php if ($job['deadline']): ?>
                            <div class="flex items-center text-sm">
                                <div class="w-8 h-8 rounded-lg bg-red-100/50 flex items-center justify-center mr-3 text-red-500">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-400 mb-0.5">締切</p>
                                    <p class="font-bold text-slate-700"><?= formatDate($job['deadline']) ?></p>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Footer Actions -->
                        <div class="flex items-center justify-between pt-4 border-t border-slate-100">
                            <div class="flex items-center">
                                <img src="<?= uploaded_asset($job['client_image'] ?? 'assets/images/default-avatar.png') ?>" 
                                     alt="<?= h($job['client_name']) ?>" 
                                     class="w-10 h-10 rounded-full border-2 border-white shadow-sm mr-3">
                                <div>
                                    <p class="text-sm font-semibold text-slate-800"><?= h($job['client_name']) ?></p>
                                    <p class="text-xs text-slate-400"><?= timeAgo($job['created_at']) ?></p>
                                </div>
                            </div>

                            <button onclick="openJobModal(<?= $job['id'] ?>)" 
                               class="inline-flex items-center px-4 py-2 bg-slate-900 text-white text-sm font-medium rounded-lg hover:bg-blue-600 transition-colors shadow-lg shadow-slate-200 group-hover:shadow-blue-500/20">
                                詳細を見る
                                <svg class="ml-2 w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Achievements (Hidden for cleaner list, or kept if essential? Removed to focus on clean jobs list primarily, user can find achievements on profile or top page) -->

            <!-- Modern Pagination -->
            <?php if ($pagination['total_pages'] > 1): ?>
                <div class="flex justify-center mt-12">
                    <nav class="flex items-center gap-2 p-2 bg-white rounded-full shadow-lg border border-slate-100">
                        <?php if ($pagination['has_prev']): ?>
                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $pagination['prev_page']])) ?>" 
                               class="p-2 w-10 h-10 flex items-center justify-center text-slate-500 hover:text-blue-600 hover:bg-blue-50 rounded-full transition-colors">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                            </a>
                        <?php endif; ?>

                        <?php for ($i = max(1, $pagination['current_page'] - 2); $i <= min($pagination['total_pages'], $pagination['current_page'] + 2); $i++): ?>
                            <?php if ($i == $pagination['current_page']): ?>
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
</section>

<!-- Modern Magnetic CTA Section -->
<section class="relative py-24 bg-slate-900 overflow-hidden">
    <div class="absolute inset-0">
        <div class="absolute inset-0 bg-blue-900/20 mix-blend-multiply"></div>
        <div class="absolute inset-0 bg-[url('<?= asset('images/grid-pattern.svg') ?>')] opacity-10"></div>
    </div>
    
    <div class="relative z-10 max-w-4xl mx-auto text-center px-4 sm:px-6 lg:px-8">
        <h2 class="text-3xl md:text-4xl font-bold text-white mb-6">
            案件をお探しの企業様へ
        </h2>
        <p class="text-lg text-blue-100 mb-10 max-w-2xl mx-auto leading-relaxed">
            優秀なクリエイターとのマッチングをサポートします。<br>
            まずは無料で案件を投稿し、最適なパートナーを見つけましょう。
        </p>
        <a href="<?= url('post-job') ?>" 
           class="inline-flex items-center px-10 py-5 bg-white text-slate-900 text-lg font-bold rounded-full hover:bg-blue-50 transition-all transform hover:-translate-y-1 hover:shadow-2xl hover:shadow-blue-500/20 group">
            案件を投稿する (無料)
            <svg class="ml-3 h-5 w-5 transform group-hover:translate-x-1 transition-transform text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
            </svg>
        </a>
    </div>
</section>

<!-- Job Detail Modal -->
<div id="job-modal" class="fixed inset-0 z-50 hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <!-- Backdrop -->
    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity opacity-0" id="modal-backdrop"></div>

    <!-- Modal Panel -->
    <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
            <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-3xl opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" id="modal-panel">
                
                <!-- Close Button -->
                <button type="button" class="absolute top-4 right-4 z-10 rounded-full bg-white/80 p-2 text-slate-400 hover:text-slate-500 focus:outline-none transition-colors" onclick="closeJobModal()">
                    <span class="sr-only">Close</span>
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <!-- Loading State -->
                <div id="modal-loading" class="flex flex-col items-center justify-center py-20">
                    <div class="w-12 h-12 border-4 border-blue-100 border-t-blue-600 rounded-full animate-spin mb-4"></div>
                    <p class="text-slate-500 font-medium">読み込み中...</p>
                </div>

                <!-- Content State -->
                <div id="modal-content" class="hidden">
                    <!-- Dynamic Content populated via JS -->
                </div>

            </div>
        </div>
    </div>
</div>

<script>
// Modal Logic
const modal = document.getElementById('job-modal');
const backdrop = document.getElementById('modal-backdrop');
const panel = document.getElementById('modal-panel');
const loading = document.getElementById('modal-loading');
const content = document.getElementById('modal-content');

function openJobModal(jobId) {
    if (!modal) return;
    
    // Show Modal
    modal.classList.remove('hidden');
    
    // Animation In
    setTimeout(() => {
        backdrop.classList.remove('opacity-0');
        panel.classList.remove('opacity-0', 'translate-y-4', 'sm:translate-y-0', 'sm:scale-95');
        panel.classList.add('opacity-100', 'translate-y-0', 'sm:scale-100');
    }, 10);

    // Reset Content
    loading.classList.remove('hidden');
    content.classList.add('hidden');
    
    // Fetch Data
    fetch(`api/get_job.php?id=${jobId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderJobDetails(data.job);
                loading.classList.add('hidden');
                content.classList.remove('hidden');
            } else {
                alert('案件詳細の取得に失敗しました: ' + (data.error || '不明なエラー'));
                closeJobModal();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('通信エラーが発生しました。');
            closeJobModal();
        });
}

function closeJobModal() {
    if (!modal) return;

    // Animation Out
    backdrop.classList.add('opacity-0');
    panel.classList.remove('opacity-100', 'translate-y-0', 'sm:scale-100');
    panel.classList.add('opacity-0', 'translate-y-4', 'sm:translate-y-0', 'sm:scale-95');
    
    // Hide after animation (300ms match transition)
    setTimeout(() => {
        modal.classList.add('hidden');
    }, 300);
}

function renderJobDetails(job) {
    const detailUrl = `<?= url('job-detail?id=') ?>${job.id}`;
    
    content.innerHTML = `
        <!-- Hero Header -->
        <div class="relative bg-slate-900 text-white p-8 overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-br from-blue-600/20 to-purple-600/20"></div>
            <div class="relative z-10">
                <div class="flex flex-wrap items-center gap-2 mb-4">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-500/20 text-blue-100 border border-blue-400/30">
                        ${job.category_name || '未分類'}
                    </span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-white/10 text-white border border-white/20">
                        ${job.created_at_formatted}
                    </span>
                </div>
                <h2 class="text-2xl md:text-3xl font-bold leading-tight mb-4">${escapeHtml(job.title)}</h2>
                
                <div class="flex items-center justify-between mt-6 pt-6 border-t border-white/10">
                    <div class="flex items-center">
                         <img src="${job.client_image ? job.client_image : '<?= asset('images/default-avatar.png') ?>'}" 
                              alt="${escapeHtml(job.client_name)}" 
                              class="w-10 h-10 rounded-full border-2 border-slate-700 bg-slate-800">
                        <div class="ml-3">
                            <p class="text-sm font-medium text-white">${escapeHtml(job.client_name)}</p>
                            <p class="text-xs text-slate-400">${job.client_location || '場所未設定'}</p>
                        </div>
                    </div>
                    <div class="text-right">
                         <p class="text-xs text-slate-400 mb-0.5">予算</p>
                         <p class="text-xl font-bold text-blue-300">¥${job.budget_min_formatted}~</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Body Content -->
        <div class="p-8 bg-white">
            <div class="prose prose-slate max-w-none mb-8">
                <h3 class="text-lg font-bold text-slate-900 mb-4 border-l-4 border-blue-500 pl-3">案件概要</h3>
                <div class="text-slate-600 leading-relaxed space-y-4">
                   ${job.description_html}
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-8">
                <div class="bg-slate-50 p-4 rounded-xl border border-slate-100">
                    <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">応募締切</h4>
                    <p class="font-bold text-slate-900 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                        ${job.deadline_formatted}
                    </p>
                </div>
                <div class="bg-slate-50 p-4 rounded-xl border border-slate-100">
                    <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">募集人数</h4>
                    <p class="font-bold text-slate-900 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                         1名（予定）
                    </p>
                </div>
            </div>

            <!-- Footer Actions -->
            <div class="flex flex-col sm:flex-row gap-4 pt-6 border-t border-slate-100">
                <a href="${detailUrl}" class="flex-1 inline-flex justify-center items-center px-6 py-3 border border-slate-200 shadow-sm text-base font-medium rounded-xl text-slate-700 bg-white hover:bg-slate-50 hover:text-blue-600 transition-colors">
                     詳細ページへ
                </a>
                <a href="${detailUrl}#apply" class="flex-1 inline-flex justify-center items-center px-6 py-3 border border-transparent text-base font-bold rounded-xl shadow-lg text-white bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 transition-all transform hover:-translate-y-0.5">
                    この案件に応募する
                </a>
            </div>
        </div>
    `;
}

// Utility to escape HTML
function escapeHtml(text) {
    if (!text) return '';
    return text
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

// Close on backdrop click
backdrop && backdrop.addEventListener('click', closeJobModal);

// Close on Esc key
document.addEventListener('keydown', function(event) {
    if (event.key === "Escape" && !modal.classList.contains('hidden')) {
        closeJobModal();
    }
});
</script>

<?php include 'includes/footer.php'; ?>