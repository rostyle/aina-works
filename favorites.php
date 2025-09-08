<?php
require_once 'config/config.php';

$pageTitle = 'お気に入り一覧';
$pageDescription = 'あなたがお気に入りに登録した作品やクリエイターを確認';

// ログインチェック
if (!isLoggedIn()) {
    redirect(url('login'));
}

$user = getCurrentUser();
if (!$user) {
    session_destroy();
    redirect(url('login'));
}

$db = Database::getInstance();

// タブの選択
$tab = $_GET['tab'] ?? 'works';
if (!in_array($tab, ['works', 'creators'])) {
    $tab = 'works';
}

// 両方の数を常に取得（タブ表示のため）
$favoriteWorksCount = $db->selectOne("
    SELECT COUNT(*) as count 
    FROM favorites 
    WHERE user_id = ? AND target_type = 'work'
", [$user['id']])['count'] ?? 0;

$favoriteCreatorsCount = $db->selectOne("
    SELECT COUNT(*) as count 
    FROM favorites 
    WHERE user_id = ? AND target_type = 'creator'
", [$user['id']])['count'] ?? 0;

// お気に入りの作品を取得
$favoriteWorks = [];
if ($tab === 'works') {
    $favoriteWorks = $db->select("
        SELECT w.*, u.full_name as creator_name, u.nickname as creator_nickname, 
               c.name as category_name, f.created_at as favorited_at
        FROM favorites f
        JOIN works w ON f.target_id = w.id
        JOIN users u ON w.user_id = u.id
        LEFT JOIN categories c ON w.category_id = c.id
        WHERE f.user_id = ? AND f.target_type = 'work'
        ORDER BY f.created_at DESC
    ", [$user['id']]);
}

// お気に入りのクリエイターを取得
$favoriteCreators = [];
if ($tab === 'creators') {
    $favoriteCreators = $db->select("
        SELECT u.*, f.created_at as favorited_at,
               (SELECT COUNT(*) FROM works WHERE user_id = u.id AND status = 'published') as works_count,
               (SELECT AVG(rating) FROM reviews WHERE reviewee_id = u.id) as avg_rating
        FROM favorites f
        JOIN users u ON f.target_id = u.id
        WHERE f.user_id = ? AND f.target_type = 'creator'
        ORDER BY f.created_at DESC
    ", [$user['id']]);
}

include 'includes/header.php';
?>

<!-- Favorites Section -->
<section class="py-8 bg-gray-50 min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">お気に入り一覧</h1>
            <p class="text-gray-600 mt-2">あなたがお気に入りに登録したアイテムを管理できます</p>
        </div>

        <!-- Tabs -->
        <div class="mb-8">
            <nav class="flex space-x-8" aria-label="Tabs">
                <a href="<?= url('favorites?tab=works') ?>" 
                   class="<?= $tab === 'works' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' ?> whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                    お気に入り作品
                    <span class="ml-2 bg-gray-100 text-gray-900 py-0.5 px-2.5 rounded-full text-xs">
                        <?= $favoriteWorksCount ?>
                    </span>
                </a>
                <a href="<?= url('favorites?tab=creators') ?>" 
                   class="<?= $tab === 'creators' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' ?> whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                    お気に入りクリエイター
                    <span class="ml-2 bg-gray-100 text-gray-900 py-0.5 px-2.5 rounded-full text-xs">
                        <?= $favoriteCreatorsCount ?>
                    </span>
                </a>
            </nav>
        </div>

        <?php if ($tab === 'works'): ?>
            <!-- Favorite Works -->
            <?php if (empty($favoriteWorks)): ?>
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">お気に入りの作品がありません</h3>
                    <p class="mt-1 text-sm text-gray-500">気になる作品を見つけてお気に入りに追加しましょう</p>
                    <div class="mt-6">
                        <a href="<?= url('work') ?>" class="btn btn-primary">
                            作品を探す
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($favoriteWorks as $work): ?>
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow">
                            <!-- Work Image -->
                            <div class="aspect-w-16 aspect-h-9">
                                <img src="<?= uploaded_asset($work['main_image']) ?>" 
                                     alt="<?= h($work['title']) ?>" 
                                     class="w-full h-48 object-cover">
                            </div>
                            
                            <!-- Work Info -->
                            <div class="p-4">
                                <div class="flex justify-between items-start mb-2">
                                    <h3 class="text-lg font-semibold text-gray-900 line-clamp-2">
                                        <a href="<?= url('work-detail?id=' . $work['id']) ?>" class="hover:text-primary-600">
                                            <?= h($work['title']) ?>
                                        </a>
                                    </h3>
                                    <button onclick="removeFavorite('work', <?= $work['id'] ?>)" 
                                            class="text-red-500 hover:text-red-700 p-1">
                                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                        </svg>
                                    </button>
                                </div>
                                
                                <p class="text-sm text-gray-600 mb-2">
                                    by <a href="<?= url('creator-profile?id=' . $work['user_id']) ?>" class="text-primary-600 hover:underline">
                                        <?= h($work['creator_nickname'] ?: $work['creator_name']) ?>
                                    </a>
                                </p>
                                
                                <?php if ($work['category_name']): ?>
                                    <span class="inline-block px-2 py-1 text-xs bg-gray-100 text-gray-800 rounded-full mb-2">
                                        <?= h($work['category_name']) ?>
                                    </span>
                                <?php endif; ?>
                                
                                <div class="flex justify-between items-center text-sm text-gray-500 mt-3">
                                    <div class="flex items-center space-x-4">
                                        <span class="flex items-center">
                                            <svg class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            <?= number_format($work['view_count']) ?>
                                        </span>
                                        <span class="flex items-center">
                                            <svg class="h-4 w-4 mr-1 text-red-500" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                            </svg>
                                            <?= number_format($work['like_count']) ?>
                                        </span>
                                    </div>
                                    <span class="text-xs">
                                        <?= timeAgo($work['favorited_at']) ?>にお気に入り登録
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <!-- Favorite Creators -->
            <?php if (empty($favoriteCreators)): ?>
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">お気に入りのクリエイターがいません</h3>
                    <p class="mt-1 text-sm text-gray-500">気になるクリエイターを見つけてお気に入りに追加しましょう</p>
                    <div class="mt-6">
                        <a href="<?= url('creators') ?>" class="btn btn-primary">
                            クリエイターを探す
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($favoriteCreators as $creator): ?>
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
                            <div class="flex items-start justify-between">
                                <div class="flex items-center space-x-4">
                                    <img src="<?= uploaded_asset($creator['profile_image'] ?: 'assets/images/default-avatar.png') ?>" 
                                         alt="<?= h($creator['full_name']) ?>" 
                                         class="w-12 h-12 rounded-full object-cover">
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-900">
                                            <a href="<?= url('creator-profile?id=' . $creator['id']) ?>" class="hover:text-primary-600">
                                                <?= h($creator['nickname'] ?: $creator['full_name']) ?>
                                            </a>
                                        </h3>
                                        <p class="text-sm text-gray-500"><?= h($creator['location']) ?></p>
                                    </div>
                                </div>
                                <button onclick="removeFavorite('creator', <?= $creator['id'] ?>)" 
                                        class="text-red-500 hover:text-red-700 p-1">
                                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                    </svg>
                                </button>
                            </div>
                            
                            <?php if ($creator['bio']): ?>
                                <p class="text-sm text-gray-600 mt-3 line-clamp-3"><?= h($creator['bio']) ?></p>
                            <?php endif; ?>
                            
                            <div class="flex justify-between items-center mt-4 pt-4 border-t border-gray-200">
                                <div class="flex items-center space-x-4 text-sm text-gray-500">
                                    <span><?= number_format($creator['works_count']) ?>作品</span>
                                    <?php if ($creator['avg_rating'] > 0): ?>
                                        <span class="flex items-center">
                                            <svg class="h-4 w-4 mr-1 text-yellow-400" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                            </svg>
                                            <?= number_format($creator['avg_rating'], 1) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <span class="text-xs text-gray-500">
                                    <?= timeAgo($creator['favorited_at']) ?>にお気に入り登録
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>

<script>
async function removeFavorite(type, targetId) {
    if (!confirm('お気に入りから削除しますか？')) {
        return;
    }
    
    try {
        const response = await fetch('<?= url('api/favorite.php') ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                action: 'remove',
                target_type: type,
                target_id: targetId
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            // ページをリロード
            location.reload();
        } else {
            alert('削除に失敗しました: ' + (result.message || '不明なエラー'));
        }
    } catch (error) {
        console.error('Error:', error);
        alert('削除に失敗しました');
    }
}
</script>

<?php include 'includes/footer.php'; ?>
