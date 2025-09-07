<?php
require_once 'config/config.php';

$creatorId = (int)($_GET['id'] ?? 0);

if (!$creatorId) {
    redirect(url('creators.php'));
}

// データベース接続
$db = Database::getInstance();

// 現在のユーザー情報を取得
$currentUser = getCurrentUser();

try {
    // クリエイター詳細取得
    $creator = $db->selectOne("
        SELECT u.*, 
               AVG(r.rating) as avg_rating, 
               COUNT(DISTINCT r.id) as review_count,
               COUNT(DISTINCT w.id) as work_count,
               COUNT(DISTINCT ja.id) as completed_jobs
        FROM users u
        LEFT JOIN reviews r ON u.id = r.reviewee_id
        LEFT JOIN works w ON u.id = w.user_id AND w.status = 'published'
        LEFT JOIN job_applications ja ON u.id = ja.creator_id AND ja.status = 'accepted'
        WHERE u.id = ? AND u.user_type = 'creator' AND u.is_active = 1
        GROUP BY u.id
    ", [$creatorId]);

    if (!$creator) {
        redirect(url('creators.php'));
    }

    // スキル取得
    $skills = $db->select("
        SELECT s.name, us.proficiency, c.name as category_name, c.color as category_color
        FROM user_skills us
        JOIN skills s ON us.skill_id = s.id
        LEFT JOIN categories c ON s.category_id = c.id
        WHERE us.user_id = ?
        ORDER BY us.proficiency DESC, s.name ASC
    ", [$creatorId]);

    // 作品取得
    $works = $db->select("
        SELECT w.*, c.name as category_name,
               AVG(r.rating) as avg_rating, COUNT(DISTINCT r.id) as review_count
        FROM works w
        LEFT JOIN categories c ON w.category_id = c.id
        LEFT JOIN reviews r ON w.id = r.work_id
        WHERE w.user_id = ? AND w.status = 'published'
        GROUP BY w.id
        ORDER BY w.view_count DESC, w.created_at DESC
        LIMIT 6
    ", [$creatorId]);

    // 現在ユーザーのお気に入り状態（クリエイター）
    $isCreatorLiked = false;
    if (isLoggedIn()) {
        $fav = $db->selectOne(
            "SELECT 1 FROM favorites WHERE user_id = ? AND target_type = 'creator' AND target_id = ?",
            [$_SESSION['user_id'], $creatorId]
        );
        $isCreatorLiked = (bool)$fav;
    }

} catch (Exception $e) {
    // エラーログを記録
    error_log("Creator profile error: " . $e->getMessage());
    // クリエイター一覧ページにリダイレクト
    redirect(url('creators.php'));
}

$pageTitle = h($creator['full_name']) . ' - クリエイタープロフィール';
$pageDescription = h($creator['bio']);

include 'includes/header.php';
?>

<!-- Breadcrumb -->
<nav class="bg-gray-50 py-4">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <ol class="flex items-center space-x-2 text-sm">
            <li><a href="<?= url() ?>" class="text-gray-500 hover:text-gray-700">ホーム</a></li>
            <li><span class="text-gray-400">/</span></li>
            <li><a href="<?= url('creators.php') ?>" class="text-gray-500 hover:text-gray-700">クリエイター一覧</a></li>
            <li><span class="text-gray-400">/</span></li>
            <li><span class="text-gray-900 font-medium"><?= h($creator['full_name']) ?></span></li>
        </ol>
    </div>
</nav>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main Content -->
        <div class="lg:col-span-2">
            <!-- Profile Header -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-8">
                <div class="flex flex-col md:flex-row items-start md:items-center space-y-4 md:space-y-0 md:space-x-6">
                    <img src="<?= uploaded_asset($creator['profile_image'] ?? 'assets/images/default-avatar.png') ?>" 
                         alt="<?= h($creator['full_name']) ?>" 
                         class="w-24 h-24 rounded-full">
                    
                    <div class="flex-1">
                        <div class="flex items-center space-x-3 mb-2">
                            <h1 class="text-3xl font-bold text-gray-900"><?= h($creator['full_name']) ?></h1>
                            <?php if ($creator['is_verified']): ?>
                                <svg class="h-6 w-6 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                            <?php endif; ?>
                        </div>
                        
                        <p class="text-lg text-gray-600 mb-3"><?= h($creator['location']) ?></p>
                        
                        <div class="flex items-center space-x-6 mb-4">
                            <div class="flex items-center">
                                <?= renderStars($creator['avg_rating']) ?>
                                <span class="ml-2 text-sm text-gray-600">
                                    <?= number_format($creator['avg_rating'] ?? 0, 1) ?> (<?= $creator['review_count'] ?>件のレビュー)
                                </span>
                            </div>
                            <div class="text-sm text-gray-600">
                                <?= formatDate($creator['created_at']) ?>から活動
                            </div>
                        </div>
                        
                        <div class="flex flex-wrap gap-2">
                            <?php if ($creator['is_pro']): ?>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                    プロ認定
                                </span>
                            <?php endif; ?>
                            <?php if ($creator['response_time'] <= 6): ?>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                    レスポンス早い
                                </span>
                            <?php endif; ?>
                            <?php if ($creator['completed_jobs'] >= 50): ?>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-purple-100 text-purple-800">
                                    実績豊富
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- About -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">自己紹介</h2>
                <p class="text-gray-700 leading-relaxed"><?= nl2br(h($creator['bio'])) ?></p>
            </div>

            <!-- Skills -->
            <?php if (!empty($skills)): ?>
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-8">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">スキル・技術</h2>
                    <div class="space-y-4">
                        <?php foreach ($skills as $skill): ?>
                            <div>
                                <div class="flex justify-between items-center mb-2">
                                    <span class="text-sm font-medium text-gray-900"><?= h($skill['name']) ?></span>
                                    <span class="text-sm text-gray-500">
                                        <?php
                                        $proficiencyMap = [
                                            'expert' => 'エキスパート',
                                            'advanced' => '上級',
                                            'intermediate' => '中級',
                                            'beginner' => '初級'
                                        ];
                                        echo $proficiencyMap[$skill['proficiency']] ?? '中級';
                                        ?>
                                    </span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <?php
                                    $widthMap = [
                                        'expert' => '95%',
                                        'advanced' => '85%',
                                        'intermediate' => '70%',
                                        'beginner' => '50%'
                                    ];
                                    $width = $widthMap[$skill['proficiency']] ?? '70%';
                                    ?>
                                    <div class="bg-blue-600 h-2 rounded-full" style="width: <?= $width ?>"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Portfolio -->
            <?php if (!empty($works)): ?>
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-8">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-semibold text-gray-900">ポートフォリオ</h2>
                        <a href="<?= url('works.php?creator_id=' . $creator['id']) ?>" class="text-blue-600 hover:text-blue-700 text-sm font-medium">
                            すべて見る →
                        </a>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <?php foreach ($works as $work): ?>
                            <div class="group">
                                <div class="relative overflow-hidden rounded-lg mb-3">
                                    <img src="<?= uploaded_asset($work['main_image']) ?>" alt="<?= h($work['title']) ?>" 
                                         class="w-full h-48 object-cover group-hover:scale-105 transition-transform duration-300">
                                    <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-20 transition-opacity duration-300"></div>
                                </div>
                                <h3 class="font-medium text-gray-900 mb-1">
                                    <a href="<?= url('work-detail.php?id=' . $work['id']) ?>" class="hover:text-blue-600 transition-colors">
                                        <?= h($work['title']) ?>
                                    </a>
                                </h3>
                                <div class="flex items-center justify-between text-sm text-gray-500">
                                    <span><?= h($work['category_name']) ?></span>
                                    <div class="flex items-center">
                                        <svg class="h-4 w-4 text-yellow-400 fill-current mr-1" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                        </svg>
                                        <?= number_format($work['avg_rating'] ?? 0, 1) ?> (<?= $work['review_count'] ?>)
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Sidebar -->
        </div>

        <!-- Sidebar -->
        <div class="lg:col-span-1">
            <!-- Contact Card -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6 sticky top-24">
                <div class="text-center mb-6">
                    <div class="text-2xl font-bold text-gray-900 mb-1"><?= formatPrice($creator['hourly_rate']) ?>〜</div>
                    <div class="text-sm text-gray-500">/ プロジェクト</div>
                </div>

                <!-- Stats -->
                <div class="grid grid-cols-3 gap-4 text-center mb-6">
                    <div>
                        <div class="text-lg font-bold text-gray-900"><?= $creator['work_count'] ?></div>
                        <div class="text-xs text-gray-500">作品数</div>
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

                <!-- Response Time -->
                <div class="text-center mb-6">
                    <div class="text-sm text-gray-600">
                        平均レスポンス時間: <span class="font-medium"><?= $creator['response_time'] ?>時間以内</span>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="space-y-3">
                    <?php if ($currentUser && $currentUser['id'] == $creator['id']): ?>
                        <button disabled class="w-full px-4 py-3 bg-gray-400 text-white font-medium rounded-md cursor-not-allowed block">
                            <svg class="h-5 w-5 inline mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-3.582 8-8 8a8.955 8.955 0 01-4.126-.98L3 20l1.98-5.874A8.955 8.955 0 013 12c0-4.418 3.582-8 8-8s8 3.582 8 8z" />
                            </svg>
                            自分のプロフィールです
                        </button>
                    <?php else: ?>
                        <a href="<?= url('chat.php?user_id=' . $creator['id']) ?>" class="w-full block text-center px-4 py-3 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 transition-colors">
                            <svg class="h-5 w-5 inline mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-3.582 8-8 8a8.955 8.955 0 01-4.126-.98L3 20l1.98-5.874A8.955 8.955 0 013 12c0-4.418 3.582-8 8-8s8 3.582 8 8z" />
                            </svg>
                            メッセージを送る
                        </a>
                    <?php endif; ?>
                    <?php 
                    $heartFill = $isCreatorLiked ? 'currentColor' : 'none';
                    $heartColor = $isCreatorLiked ? 'text-red-500' : 'text-gray-700';
                    $favLabel = $isCreatorLiked ? 'お気に入り済み' : 'お気に入りに追加';
                    ?>
                    <button onclick="toggleLike('creator', <?= (int)$creator['id'] ?>, this)"
                            class="w-full block text-center px-4 py-3 border border-gray-300 text-gray-700 font-medium rounded-md hover:bg-gray-50 transition-colors like-btn"
                            data-liked="<?= $isCreatorLiked ? 'true' : 'false' ?>">
                        <svg class="h-5 w-5 inline mr-2 <?= $heartColor ?>" fill="<?= $heartFill ?>" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                        </svg>
                        <span class="fav-label"><?= $favLabel ?></span>
                    </button>
                </div>
            </div>

            <!-- Basic Info -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
                <h3 class="font-semibold text-gray-900 mb-4">基本情報</h3>
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600">所在地</span>
                        <span class="text-gray-900"><?= h($creator['location']) ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">経験年数</span>
                        <span class="text-gray-900"><?= $creator['experience_years'] ?>年</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">対応可能状況</span>
                        <span class="text-green-600 font-medium">対応可能</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">最終ログイン</span>
                        <span class="text-gray-900">1時間前</span>
                    </div>
                </div>
            </div>

            <!-- Certifications -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h3 class="font-semibold text-gray-900 mb-4">認定・資格</h3>
                <div class="space-y-3">
                    <div class="flex items-center space-x-3">
                        <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                            <svg class="h-4 w-4 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-gray-900">プロ認定クリエイター</div>
                            <div class="text-xs text-gray-500">2023年12月取得</div>
                        </div>
                    </div>
                    <div class="flex items-center space-x-3">
                        <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                            <svg class="h-4 w-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-gray-900">AI技術認定</div>
                            <div class="text-xs text-gray-500">2023年8月取得</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// いいね機能（クリエイタープロフィール）
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
            const label = button.querySelector('.fav-label');
            if (result.liked) {
                svg.setAttribute('fill', 'currentColor');
                svg.classList.remove('text-gray-700');
                svg.classList.add('text-red-500');
                button.setAttribute('data-liked', 'true');
                if (label) label.textContent = 'お気に入り済み';
            } else {
                svg.setAttribute('fill', 'none');
                svg.classList.remove('text-red-500');
                svg.classList.add('text-gray-700');
                button.setAttribute('data-liked', 'false');
                if (label) label.textContent = 'お気に入りに追加';
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

