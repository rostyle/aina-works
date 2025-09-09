<?php
require_once 'config/config.php';

$workId = (int)($_GET['id'] ?? 0);

if (!$workId) {
    redirect(url('work'));
}

// データベース接続
$db = Database::getInstance();

// 現在のユーザー情報を取得
$currentUser = getCurrentUser();

try {
    // 作品詳細取得
    $work = $db->selectOne("
        SELECT w.*, u.full_name as creator_name, u.profile_image as creator_image, 
               u.bio as creator_bio, u.location as creator_location, u.response_time,
               u.experience_years, u.is_pro, u.is_verified,
               u.website, u.twitter_url, u.instagram_url, u.facebook_url, 
               u.linkedin_url, u.youtube_url, u.tiktok_url,
               c.name as category_name, c.color as category_color,
               AVG(r.rating) as avg_rating, COUNT(DISTINCT r.id) as review_count
        FROM works w
        JOIN users u ON w.user_id = u.id
        LEFT JOIN categories c ON w.category_id = c.id
        LEFT JOIN reviews r ON w.id = r.work_id
        WHERE w.id = ? AND w.status = 'published' AND u.is_active = 1
        GROUP BY w.id
    ", [$workId]);

    if (!$work) {
        redirect(url('work'));
    }

    // 閲覧数更新
    $db->update("UPDATE works SET view_count = view_count + 1 WHERE id = ?", [$workId]);

    // クリエイターの統計情報取得
    $creatorStats = $db->selectOne("
        SELECT 
            COUNT(DISTINCT w.id) as work_count,
            COUNT(DISTINCT ja.id) as completed_jobs,
            AVG(r.rating) as avg_rating
        FROM users u
        LEFT JOIN works w ON u.id = w.user_id AND w.status = 'published'
        LEFT JOIN job_applications ja ON u.id = ja.creator_id AND ja.status = 'accepted'
        LEFT JOIN reviews r ON u.id = r.reviewee_id
        WHERE u.id = ?
    ", [$work['user_id']]);

    // クリエイターのスキル取得
    $creatorSkills = $db->select("
        SELECT s.name, us.proficiency
        FROM user_skills us
        JOIN skills s ON us.skill_id = s.id
        WHERE us.user_id = ?
        ORDER BY us.proficiency DESC, s.name ASC
    ", [$work['user_id']]);

    // 関連作品取得
    $relatedWorks = $db->select("
        SELECT w.*, AVG(r.rating) as avg_rating, COUNT(DISTINCT r.id) as review_count
        FROM works w
        LEFT JOIN reviews r ON w.id = r.work_id
        WHERE w.user_id = ? AND w.id != ? AND w.status = 'published'
        GROUP BY w.id
        ORDER BY w.view_count DESC
        LIMIT 3
    ", [$work['user_id'], $workId]);

} catch (Exception $e) {
    // エラーログ出力（開発時のみ）
    if (DEBUG) {
        error_log("Work detail error: " . $e->getMessage());
    }
    
    // エラー時は404ページにリダイレクト
    redirect(url('work'));
}

$pageTitle = h($work['title']) . ' - 作品詳細';
$pageDescription = h($work['description']);

// 画像配列をデコード
$images = json_decode($work['images'] ?? '[]', true) ?: [$work['main_image']];
$tags = json_decode($work['tags'] ?? '[]', true) ?: [];
$technologies = json_decode($work['technologies'] ?? '[]', true) ?: [];

include 'includes/header.php';
?>

<!-- Breadcrumb -->
<nav class="bg-gray-50 py-4">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <ol class="flex items-center space-x-2 text-sm">
            <li><a href="<?= url() ?>" class="text-gray-500 hover:text-gray-700">ホーム</a></li>
            <li><span class="text-gray-400">/</span></li>
            <li><a href="<?= url('work') ?>" class="text-gray-500 hover:text-gray-700">作品一覧</a></li>
            <li><span class="text-gray-400">/</span></li>
            <li><span class="text-gray-900 font-medium"><?= h($work['title']) ?></span></li>
        </ol>
    </div>
</nav>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main Content -->
        <div class="lg:col-span-2">
            <!-- Image Gallery -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden mb-8 relative z-10">
                <div class="relative">
                    <!-- メイン画像 -->
                    <div class="aspect-w-16 aspect-h-9 bg-gray-100">
                        <img id="main-image" 
                             src="<?= uploaded_asset($images[0]) ?>" 
                             alt="<?= h($work['title']) ?>" 
                             class="w-full h-full object-cover cursor-zoom-in"
                             onclick="openImageModal('<?= uploaded_asset($images[0]) ?>')">
                    </div>
                    
                    <!-- 画像カウンター -->
                    <?php if (count($images) > 1): ?>
                        <div class="absolute top-4 right-4 bg-black bg-opacity-60 text-white px-3 py-1 rounded-full text-sm z-20">
                            <span id="current-image-index">1</span> / <?= count($images) ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- 画像ナビゲーション矢印 -->
                    <?php if (count($images) > 1): ?>
                        <button id="prev-btn" onclick="previousImage()" 
                                class="absolute left-4 top-1/2 transform -translate-y-1/2 bg-black bg-opacity-50 text-white p-2 rounded-full hover:bg-opacity-70 transition-all z-20">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                            </svg>
                        </button>
                        <button id="next-btn" onclick="nextImage()" 
                                class="absolute right-4 top-1/2 transform -translate-y-1/2 bg-black bg-opacity-50 text-white p-2 rounded-full hover:bg-opacity-70 transition-all z-20">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </button>
                    <?php endif; ?>
                </div>
                
                <!-- サムネイル -->
                <?php if (count($images) > 1): ?>
                    <div class="p-4 bg-gray-50">
                        <div class="flex space-x-3 overflow-x-auto pb-2">
                            <?php foreach ($images as $index => $image): ?>
                                <button onclick="changeImage('<?= h($image) ?>', <?= $index ?>)" 
                                        class="image-thumb flex-shrink-0 w-20 h-20 rounded-lg overflow-hidden border-2 <?= $index === 0 ? 'border-blue-500' : 'border-gray-200' ?> hover:border-blue-300 transition-colors">
                                    <img src="<?= uploaded_asset($image) ?>" alt="画像<?= $index + 1 ?>" class="w-full h-full object-cover">
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- 画像モーダル -->
            <div id="image-modal" class="fixed inset-0 bg-black bg-opacity-90 z-50 hidden items-center justify-center p-4">
                <div class="relative max-w-7xl max-h-full">
                    <button onclick="closeImageModal()" 
                            class="absolute top-4 right-4 text-white text-2xl hover:text-gray-300 z-10">
                        ×
                    </button>
                    <img id="modal-image" src="" alt="" class="max-w-full max-h-full object-contain">
                </div>
            </div>

            <!-- Work Details -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-8">
                    <div class="flex items-start justify-between mb-6">
                    <div class="flex-1">
                        <h1 class="text-3xl font-bold text-gray-900 mb-2"><?= h($work['title']) ?></h1>
                        <div class="flex items-center space-x-4 text-sm text-gray-500 mb-4">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                <?= h($work['category_name']) ?>
                            </span>
                            <span><?= formatDate($work['created_at']) ?></span>
                            <span><?= number_format($work['view_count']) ?> views</span>
                        </div>
                        <!-- 星評価を追加 -->
                        <div class="flex items-center mb-4">
                            <?= renderStars($work['avg_rating'] ?: 0) ?>
                            <span class="ml-2 text-sm font-medium text-gray-900">
                                <?= number_format($work['avg_rating'] ?: 0, 1) ?>
                            </span>
                            <span class="ml-1 text-sm text-gray-500">
                                (<?= $work['review_count'] ?: 0 ?>件のレビュー)
                            </span>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-2xl font-bold text-gray-900 mb-1">
                            <?= formatPrice($work['price_min']) ?>〜
                        </div>
                        <div class="text-sm text-gray-500">参考価格</div>
                    </div>
                </div>

                <!-- Description -->
                <div class="mb-8">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">プロジェクト概要</h3>
                    <div class="prose prose-gray max-w-none">
                        <p class="text-gray-700 leading-relaxed text-base"><?= nl2br(h($work['description'])) ?></p>
                    </div>
                </div>

                <!-- Technologies -->
                <?php if (!empty($technologies)): ?>
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-3">使用技術</h3>
                        <div class="flex flex-wrap gap-2">
                            <?php foreach ($technologies as $tech): ?>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800">
                                    <?= h($tech) ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Project Details -->
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-3">プロジェクト詳細</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="font-medium text-gray-900">制作期間:</span>
                            <span class="text-gray-700 ml-2"><?= $work['duration_weeks'] ?>週間</span>
                        </div>
                        <div>
                            <span class="font-medium text-gray-900">価格帯:</span>
                            <span class="text-gray-700 ml-2"><?= formatPrice($work['price_min']) ?>〜<?= formatPrice($work['price_max']) ?></span>
                        </div>
                    </div>
                </div>

                <!-- Tags -->
                <?php if (!empty($tags)): ?>
                    <div class="mb-6">
                        <h4 class="text-sm font-medium text-gray-900 mb-3">タグ</h4>
                        <div class="flex flex-wrap gap-2">
                            <?php foreach ($tags as $tag): ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 cursor-pointer hover:bg-blue-200 transition-colors">
                                    #<?= h($tag) ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Stats -->
                <div class="pt-6 border-t border-gray-200">
                    <div class="grid grid-cols-3 gap-4 text-center">
                        <div>
                            <div class="text-2xl font-bold text-gray-900">
                                <span id="like-count-<?= $work['id'] ?>"><?= number_format($work['like_count'] ?: 0) ?></span>
                            </div>
                            <div class="text-sm text-gray-500">いいね</div>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-gray-900"><?= number_format($work['view_count'] ?: 0) ?></div>
                            <div class="text-sm text-gray-500">閲覧数</div>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-gray-900"><?= $work['review_count'] ?: 0 ?></div>
                            <div class="text-sm text-gray-500">レビュー</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Reviews Section -->
            <?php 
            // レビューデータを取得
            $reviews = $db->select("
                SELECT r.*, u.full_name as reviewer_name, u.profile_image as reviewer_image
                FROM reviews r
                JOIN users u ON r.reviewer_id = u.id
                WHERE r.work_id = ?
                ORDER BY r.created_at DESC
                LIMIT 5
            ", [$workId]);
            ?>
            <?php if (!empty($reviews)): ?>
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-8">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-semibold text-gray-900">レビュー</h3>
                        <div class="flex items-center">
                            <?= renderStars($work['avg_rating'] ?: 0) ?>
                            <span class="ml-2 text-sm text-gray-600">
                                <?= number_format($work['avg_rating'] ?: 0, 1) ?> (<?= $work['review_count'] ?: 0 ?>件)
                            </span>
                        </div>
                    </div>
                    
                    <div class="space-y-6">
                        <?php foreach ($reviews as $review): ?>
                            <div class="flex space-x-4">
                                <img src="<?= uploaded_asset($review['reviewer_image'] ?? 'assets/images/default-avatar.png') ?>" 
                                     alt="<?= h($review['reviewer_name']) ?>" 
                                     class="w-12 h-12 rounded-full flex-shrink-0">
                                <div class="flex-1">
                                    <div class="flex items-center justify-between mb-2">
                                        <h4 class="font-medium text-gray-900"><?= h($review['reviewer_name']) ?></h4>
                                        <div class="flex items-center">
                                            <?= renderStars($review['rating']) ?>
                                            <span class="ml-2 text-sm text-gray-500">
                                                <?= formatDate($review['created_at']) ?>
                                            </span>
                                        </div>
                                    </div>
                                    <?php if (!empty($review['comment'])): ?>
                                        <p class="text-gray-700 leading-relaxed"><?= h($review['comment']) ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <?php if ($work['review_count'] > 5): ?>
                        <div class="mt-6 text-center">
                            <button class="text-blue-600 hover:text-blue-800 font-medium text-sm">
                                すべてのレビューを見る (<?= $work['review_count'] ?>件)
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <!-- Review Form -->
            <?php if (!isLoggedIn()): ?>
                <!-- ログインしていない場合 -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-8">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">レビューを投稿</h3>
                    <div class="text-center py-8">
                        <p class="text-gray-600 mb-4">レビューを投稿するにはログインが必要です</p>
                        <div class="space-x-4">
                            <a href="<?= url('login?redirect=' . urlencode($_SERVER['REQUEST_URI'])) ?>" 
                               class="inline-block px-6 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 transition-colors">
                                ログイン
                            </a>
                            <!-- ローカル新規登録は無効化 -->
                        </div>
                    </div>
                </div>
            <?php elseif ($work['user_id'] == (getCurrentUser()['id'] ?? 0)): ?>
                <!-- 自分の作品の場合 -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-8">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">レビューを投稿</h3>
                    <div class="text-center py-8">
                        <p class="text-gray-600">自分の作品にはレビューを投稿できません</p>
                    </div>
                </div>
            <?php else: ?>
                <!-- レビューUI（details/summaryベース・ゼロJS） -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-8">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">レビューを投稿</h3>
                    <details class="border border-gray-200 rounded-md">
                        <summary class="list-none cursor-pointer select-none px-4 py-2 bg-blue-600 text-white rounded-md inline-flex items-center justify-center">
                            <span class="font-medium">レビューを書く</span>
                        </summary>
                        <div class="p-4 border-t border-gray-200">
                            <form method="POST" action="api/submit-review.php" class="space-y-4" id="review-form">
                                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                <input type="hidden" name="work_id" value="<?= $workId ?>">
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">評価</label>
                                    <div class="flex items-center space-x-2">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <label class="flex items-center">
                                                <input type="radio" name="rating" value="<?= $i ?>" class="mr-1" <?= $i === 5 ? 'checked' : '' ?>>
                                                <span class="text-sm"><?= $i ?>★</span>
                                            </label>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                
                                <div>
                                    <label for="comment" class="block text-sm font-medium text-gray-700 mb-2">コメント</label>
                                    <textarea 
                                        name="comment" 
                                        id="comment" 
                                        rows="4" 
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        placeholder="この作品についてのコメントを書いてください..."
                                        required
                                    ></textarea>
                                </div>
                                
                                <div class="flex justify-end pt-2">
                                    <button type="submit" class="px-6 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700">
                                        送信
                                    </button>
                                </div>
                            </form>
                        </div>
                    </details>
                </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <div class="lg:col-span-1">
            <!-- Creator Info -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6 sticky top-24">
                <div class="text-center mb-6">
                    <div class="relative inline-block mb-4">
                        <img src="<?= uploaded_asset($work['creator_image'] ?? 'assets/images/default-avatar.png') ?>" 
                             alt="<?= h($work['creator_name']) ?>" 
                             class="w-24 h-24 rounded-full mx-auto border-4 border-white shadow-lg">
                        <?php if ($work['is_verified']): ?>
                            <div class="absolute -bottom-1 -right-1 bg-green-500 text-white rounded-full p-1">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <h3 class="text-xl font-bold text-gray-900 mb-1"><?= h($work['creator_name']) ?></h3>
                    <p class="text-sm text-gray-600 mb-3"><?= h($work['category_name']) ?>クリエイター</p>
                    
                    <?php if (!empty($work['creator_bio'])): ?>
                        <p class="text-sm text-gray-700 mb-4 leading-relaxed"><?= h($work['creator_bio']) ?></p>
                    <?php endif; ?>
                    
                    <div class="flex items-center justify-center mb-4">
                        <?= renderStars($work['avg_rating'] ?: 0) ?>
                        <span class="ml-2 text-sm font-medium text-gray-900">
                            <?= number_format($work['avg_rating'] ?: 0, 1) ?>
                        </span>
                        <span class="ml-1 text-sm text-gray-500">
                            (<?= $work['review_count'] ?: 0 ?>件)
                        </span>
                    </div>
                    
                    <div class="flex flex-wrap items-center justify-center gap-2 mb-4">
                        <?php if ($work['is_pro']): ?>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                </svg>
                                プロ認定
                            </span>
                        <?php endif; ?>
                        <?php if ($work['response_time'] <= 6): ?>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                                </svg>
                                レスポンス早い
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <!-- 基本情報 -->
                    <div class="grid grid-cols-2 gap-4 text-sm text-gray-600 mb-6">
                        <div>
                            <span class="block text-gray-400">所在地</span>
                            <span class="font-medium"><?= h($work['creator_location'] ?? '未設定') ?></span>
                        </div>
                        <div>
                            <span class="block text-gray-400">返信時間</span>
                            <span class="font-medium"><?= $work['response_time'] ?>時間以内</span>
                        </div>
                    </div>
                    
                    <!-- Creator Stats -->
                    <div class="grid grid-cols-3 gap-4 text-center mb-6">
                        <div>
                            <div class="text-lg font-bold text-gray-900"><?= $creatorStats['work_count'] ?></div>
                            <div class="text-xs text-gray-500">作品数</div>
                        </div>
                        <div>
                            <div class="text-lg font-bold text-gray-900"><?= $creatorStats['completed_jobs'] ?></div>
                            <div class="text-xs text-gray-500">完了案件</div>
                        </div>
                        <div>
                            <div class="text-lg font-bold text-gray-900">98%</div>
                            <div class="text-xs text-gray-500">満足度</div>
                        </div>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="space-y-3">
                        <button onclick="toggleLike('work', <?= $work['id'] ?>, this)" 
                                class="like-btn w-full px-4 py-3 bg-red-50 text-red-600 border border-red-200 font-medium rounded-md hover:bg-red-100 transition-colors"
                                data-liked="false">
                            <svg class="h-5 w-5 inline mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                            </svg>
                            <span class="like-text">この作品をいいね</span>
                        </button>
                        <?php if ($currentUser && $currentUser['id'] == $work['user_id']): ?>
                            <button disabled class="w-full px-4 py-3 border border-gray-300 text-gray-400 font-medium rounded-md cursor-not-allowed">
                                <svg class="h-5 w-5 inline mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2-2v2m8 0V6a2 2 0 012 2v6a2 2 0 01-2 2H6a2 2 0 01-2-2V8a2 2 0 012-2V6z" />
                                </svg>
                                自分の作品です
                            </button>
                        <?php else: ?>
                            <a href="<?= url('chat?user_id=' . $work['user_id']) ?>" class="w-full px-4 py-3 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 transition-colors inline-flex items-center justify-center">
                                <svg class="h-5 w-5 inline mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-3.582 8-8 8a8.955 8.955 0 01-4.126-.98L3 20l1.98-5.874A8.955 8.955 0 013 12c0-4.418 3.582-8 8-8s8 3.582 8 8z" />
                                </svg>
                                チャットを開始
                            </a>
                        <?php endif; ?>
                        <a href="<?= url('creator-profile?id=' . $work['user_id']) ?>" class="w-full px-4 py-3 border border-gray-300 text-gray-700 font-medium rounded-md hover:bg-gray-50 transition-colors inline-flex items-center justify-center">
                            <svg class="h-5 w-5 inline mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            プロフィールを見る
                        </a>
                        <?php if (!($currentUser && $currentUser['id'] == $work['user_id'])): ?>
                            <button onclick="openJobRequestModal()" class="w-full px-4 py-3 border border-gray-300 text-gray-700 font-medium rounded-md hover:bg-gray-50 transition-colors">
                                <svg class="h-5 w-5 inline mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2-2v2m8 0V6a2 2 0 012 2v6a2 2 0 01-2 2H6a2 2 0 01-2-2V8a2 2 0 012-2V6z" />
                                </svg>
                                案件を依頼する
                            </button>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Skills -->
                <?php if (!empty($creatorSkills)): ?>
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
                        <h4 class="font-semibold text-gray-900 mb-4">スキル</h4>
                        <div class="space-y-3">
                            <?php foreach ($creatorSkills as $skill): ?>
                                <div>
                                    <div class="flex justify-between text-sm mb-1">
                                        <span class="text-gray-700"><?= h($skill['name']) ?></span>
                                        <span class="text-gray-500">
                                            <?php
                                            $proficiencyMap = [
                                                'expert' => '95%',
                                                'advanced' => '85%',
                                                'intermediate' => '70%',
                                                'beginner' => '50%'
                                            ];
                                            echo $proficiencyMap[$skill['proficiency']] ?? '70%';
                                            ?>
                                        </span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="bg-blue-600 h-2 rounded-full" style="width: <?= $proficiencyMap[$skill['proficiency']] ?? '70%' ?>"></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Related Works -->
                <?php if (!empty($relatedWorks)): ?>
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 relative z-0 overflow-hidden">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="font-semibold text-gray-900">この作者の他の作品</h4>
                            <a href="<?= url('creator-profile?id=' . $work['user_id']) ?>" 
                               class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                                すべて見る →
                            </a>
                        </div>
                        <div class="carousel" data-autoplay>
                            <div class="relative overflow-hidden rounded-lg">
                                <div class="carousel-track flex transition-transform duration-500">
                                    <?php foreach ($relatedWorks as $relatedWork): ?>
                                        <div class="carousel-slide min-w-full">
                                            <a href="<?= url('work-detail?id=' . $relatedWork['id']) ?>" 
                                               class="block group hover:bg-gray-50 rounded-lg p-3 transition-colors">
                                                <div class="flex space-x-4">
                                                    <div class="relative flex-shrink-0">
                                                        <img src="<?= uploaded_asset($relatedWork['main_image']) ?>" 
                                                             alt="<?= h($relatedWork['title']) ?>" 
                                                             class="w-24 h-24 md:w-28 md:h-28 rounded-lg object-cover group-hover:scale-105 transition-transform">
                                                        <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-10 rounded-lg transition-all"></div>
                                                    </div>
                                                    <div class="flex-1 min-w-0">
                                                        <h5 class="text-sm font-medium text-gray-900 group-hover:text-blue-600 transition-colors line-clamp-2 mb-1">
                                                            <?= h($relatedWork['title']) ?>
                                                        </h5>
                                                        <div class="flex items-center justify-between mb-2">
                                                            <span class="text-sm font-semibold text-gray-900">
                                                                <?= formatPrice($relatedWork['price_min']) ?>〜
                                                            </span>
                                                            <div class="flex items-center">
                                                                <svg class="h-3 w-3 text-yellow-400 fill-current" viewBox="0 0 20 20">
                                                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                                                </svg>
                                                                <span class="text-xs text-gray-500 ml-1">
                                                                    <?= number_format($relatedWork['avg_rating'] ?: 0, 1) ?> (<?= $relatedWork['review_count'] ?: 0 ?>)
                                                                </span>
                                                            </div>
                                                        </div>
                                                        <div class="flex items-center text-xs text-gray-500">
                                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                            </svg>
                                                            <?= number_format($relatedWork['view_count'] ?: 0) ?> views
                                                            <span class="mx-1">•</span>
                                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                                                            </svg>
                                                            <?= number_format($relatedWork['like_count'] ?: 0) ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </a>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <button class="carousel-prev absolute left-2 top-1/2 -translate-y-1/2 bg-white/90 backdrop-blur-sm p-2 rounded-full shadow hover:bg-white">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                                </button>
                                <button class="carousel-next absolute right-2 top-1/2 -translate-y-1/2 bg-white/90 backdrop-blur-sm p-2 rounded-full shadow hover:bg-white">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                </button>
                            </div>
                            <div class="flex justify-center mt-3 space-x-2">
                                <?php foreach ($relatedWorks as $i => $_): ?>
                                    <button class="carousel-indicator w-2 h-2 rounded-full <?= $i === 0 ? 'bg-blue-600' : 'bg-gray-300' ?>"></button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <h4 class="font-semibold text-gray-900 mb-4">この作者の他の作品</h4>
                        <div class="text-center py-8">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">他の作品はまだありません</h3>
                            <p class="mt-1 text-sm text-gray-500">この作者の最新作をお楽しみに！</p>
                            <div class="mt-6">
                                <a href="<?= url('creator-profile?id=' . $work['user_id']) ?>" 
                                   class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                                    作者のプロフィールを見る
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- メッセージ送信モーダル -->
<div id="message-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">メッセージを送る</h3>
                <button onclick="closeMessageModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <form id="message-form" onsubmit="sendMessage(event)">
                <input type="hidden" name="recipient_id" value="<?= $work['user_id'] ?>">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">件名</label>
                    <input type="text" name="subject" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="件名を入力してください">
                </div>
                
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">メッセージ</label>
                    <textarea name="message" rows="5" required
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                              placeholder="メッセージ内容を入力してください"></textarea>
                </div>
                
                <div class="flex space-x-3">
                    <button type="button" onclick="closeMessageModal()" 
                            class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 transition-colors">
                        キャンセル
                    </button>
                    <button type="submit" 
                            class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                        送信
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 案件依頼モーダル -->
<div id="job-request-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">案件を依頼する</h3>
                <button onclick="closeJobRequestModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <form id="job-request-form" onsubmit="requestJob(event)">
                <input type="hidden" name="creator_id" value="<?= $work['user_id'] ?>">
                <input type="hidden" name="work_id" value="<?= $work['id'] ?>">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">プロジェクトタイトル</label>
                        <input type="text" name="project_title" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="プロジェクトタイトルを入力">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">希望納期</label>
                        <input type="date" name="deadline" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">プロジェクト概要</label>
                    <textarea name="project_description" rows="3" required
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                              placeholder="プロジェクトの概要を入力してください"></textarea>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">予算（最小）</label>
                        <input type="number" name="budget_min" required min="1"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="100000">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">予算（最大）</label>
                        <input type="number" name="budget_max" required min="1"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="500000">
                    </div>
                </div>
                
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">詳細要件</label>
                    <textarea name="requirements" rows="4"
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                              placeholder="詳細な要件や仕様を入力してください（任意）"></textarea>
                </div>
                
                <div class="flex space-x-3">
                    <button type="button" onclick="closeJobRequestModal()" 
                            class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 transition-colors">
                        キャンセル
                    </button>
                    <button type="submit" 
                            class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                        依頼を送信
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let currentImageIndex = 0;
const images = <?= json_encode($images) ?>;
const imageUrls = <?= json_encode(array_map('uploaded_asset', $images)) ?>;

function changeImage(src, index) {
    // 事前に生成されたURLを使用
    const assetSrc = imageUrls[index];
    document.getElementById('main-image').src = assetSrc;
    document.getElementById('main-image').onclick = function() { openImageModal(assetSrc); };
    currentImageIndex = index;
    
    // Update thumbnail borders
    const thumbs = document.querySelectorAll('.image-thumb');
    thumbs.forEach((thumb, i) => {
        if (i === index) {
            thumb.classList.remove('border-gray-200');
            thumb.classList.add('border-blue-500');
        } else {
            thumb.classList.remove('border-blue-500');
            thumb.classList.add('border-gray-200');
        }
    });
    
    // Update image counter
    const counter = document.getElementById('current-image-index');
    if (counter) {
        counter.textContent = index + 1;
    }
}

function previousImage() {
    const newIndex = currentImageIndex > 0 ? currentImageIndex - 1 : images.length - 1;
    changeImage(images[newIndex], newIndex);
}

function nextImage() {
    const newIndex = currentImageIndex < images.length - 1 ? currentImageIndex + 1 : 0;
    changeImage(images[newIndex], newIndex);
}

function openImageModal(src) {
    document.getElementById('modal-image').src = src;
    document.getElementById('image-modal').classList.remove('hidden');
    document.getElementById('image-modal').classList.add('flex');
    document.body.classList.add('overflow-hidden');
}

function closeImageModal() {
    document.getElementById('image-modal').classList.add('hidden');
    document.getElementById('image-modal').classList.remove('flex');
    document.body.classList.remove('overflow-hidden');
}

// キーボードナビゲーション
document.addEventListener('keydown', function(e) {
    const modal = document.getElementById('image-modal');
    if (!modal.classList.contains('hidden')) {
        if (e.key === 'Escape') {
            closeImageModal();
        } else if (e.key === 'ArrowLeft') {
            previousImage();
            openImageModal(imageUrls[currentImageIndex]);
        } else if (e.key === 'ArrowRight') {
            nextImage();
            openImageModal(imageUrls[currentImageIndex]);
        }
    } else {
        if (e.key === 'ArrowLeft') {
            previousImage();
        } else if (e.key === 'ArrowRight') {
            nextImage();
        }
    }
});

// モーダル背景クリックで閉じる
document.getElementById('image-modal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeImageModal();
    }
});

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
            const likeText = button.querySelector('.like-text');
            const likeCountElement = document.getElementById(`like-count-${targetId}`);
            
            if (result.liked) {
                // いいね状態
                svg.setAttribute('fill', 'currentColor');
                button.classList.remove('bg-red-50', 'text-red-600', 'border-red-200');
                button.classList.add('bg-red-600', 'text-white', 'border-red-600');
                button.setAttribute('data-liked', 'true');
                if (likeText) likeText.textContent = 'いいね済み';
            } else {
                // いいね解除状態
                svg.setAttribute('fill', 'none');
                button.classList.remove('bg-red-600', 'text-white', 'border-red-600');
                button.classList.add('bg-red-50', 'text-red-600', 'border-red-200');
                button.setAttribute('data-liked', 'false');
                if (likeText) likeText.textContent = 'この作品をいいね';
            }
            
            // いいね数を更新
            if (likeCountElement) {
                likeCountElement.textContent = new Intl.NumberFormat().format(result.like_count);
            }
            
            // 成功メッセージを表示
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

// メッセージモーダル制御
function openMessageModal() {
    document.getElementById('message-modal').classList.remove('hidden');
    document.getElementById('message-modal').classList.add('flex');
    document.body.classList.add('overflow-hidden');
}

function closeMessageModal() {
    document.getElementById('message-modal').classList.add('hidden');
    document.getElementById('message-modal').classList.remove('flex');
    document.body.classList.remove('overflow-hidden');
    document.getElementById('message-form').reset();
}

// 案件依頼モーダル制御
function openJobRequestModal() {
    document.getElementById('job-request-modal').classList.remove('hidden');
    document.getElementById('job-request-modal').classList.add('flex');
    document.body.classList.add('overflow-hidden');
}

function closeJobRequestModal() {
    document.getElementById('job-request-modal').classList.add('hidden');
    document.getElementById('job-request-modal').classList.remove('flex');
    document.body.classList.remove('overflow-hidden');
    document.getElementById('job-request-form').reset();
}

// メッセージ送信
async function sendMessage(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    
    try {
        const response = await fetch('api/send-message.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification(result.message, 'success');
            closeMessageModal();
        } else {
            showNotification(result.error || 'エラーが発生しました', 'error');
        }
        
    } catch (error) {
        console.error('Message send error:', error);
        showNotification('ネットワークエラーが発生しました', 'error');
    }
}

// 案件依頼送信
async function requestJob(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    
    try {
        const response = await fetch('api/request-job.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification(result.message, 'success');
            closeJobRequestModal();
        } else {
            showNotification(result.error || 'エラーが発生しました', 'error');
        }
        
    } catch (error) {
        console.error('Job request error:', error);
        showNotification('ネットワークエラーが発生しました', 'error');
    }
}

// モーダル背景クリックで閉じる
document.getElementById('message-modal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeMessageModal();
    }
});

document.getElementById('job-request-modal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeJobRequestModal();
    }
});
</script>


<?php include 'includes/footer.php'; ?>

