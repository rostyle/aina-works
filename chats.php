<?php
require_once 'config/config.php';

// ログイン確認
if (!isLoggedIn()) {
    redirect(url('login'));
}

$currentUser = getCurrentUser();
$db = Database::getInstance();

// チャットルーム一覧を取得
$chatRooms = $db->select("
    SELECT 
        cr.*,
        CASE 
            WHEN cr.user1_id = ? THEN u2.full_name
            ELSE u1.full_name
        END as other_user_name,
        CASE 
            WHEN cr.user1_id = ? THEN u2.profile_image
            ELSE u1.profile_image
        END as other_user_image,
        CASE 
            WHEN cr.user1_id = ? THEN u2.id
            ELSE u1.id
        END as other_user_id,
        CASE 
            WHEN cr.user1_id = ? THEN u2.is_online
            ELSE u1.is_online
        END as other_user_online,
        cm.message as last_message,
        cm.created_at as last_message_time,
        cm.sender_id as last_message_sender_id,
        COUNT(CASE WHEN cm.is_read = 0 AND cm.sender_id != ? THEN 1 END) as unread_count
    FROM chat_rooms cr
    LEFT JOIN users u1 ON cr.user1_id = u1.id
    LEFT JOIN users u2 ON cr.user2_id = u2.id
    LEFT JOIN chat_messages cm ON cr.id = cm.room_id
    WHERE cr.user1_id = ? OR cr.user2_id = ?
    GROUP BY cr.id
    ORDER BY cr.updated_at DESC
", [
    $currentUser['id'], $currentUser['id'], $currentUser['id'], $currentUser['id'], 
    $currentUser['id'], $currentUser['id'], $currentUser['id']
]);

$pageTitle = 'チャット';
$pageDescription = 'チャット一覧';

include 'includes/header.php';
?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">チャット</h1>
        <p class="text-gray-600">クリエイターとの会話</p>
    </div>

    <?php if (empty($chatRooms)): ?>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-8 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-3.582 8-8 8a8.955 8.955 0 01-4.126-.98L3 20l1.98-5.874A8.955 8.955 0 013 12c0-4.418 3.582-8 8-8s8 3.582 8 8z" />
            </svg>
            <h3 class="text-lg font-medium text-gray-900 mb-2">まだチャットがありません</h3>
            <p class="text-gray-500 mb-6">作品を見てクリエイターとチャットを始めましょう</p>
            <a href="<?= url('work') ?>" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                作品を探す
            </a>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="divide-y divide-gray-200">
                <?php foreach ($chatRooms as $room): ?>
                    <a href="<?= url('chat?user_id=' . $room['other_user_id']) ?>" 
                       class="block p-4 hover:bg-gray-50 transition-colors">
                        <div class="flex items-center space-x-4">
                            <div class="relative flex-shrink-0">
                                <img src="<?= uploaded_asset($room['other_user_image'] ?? 'assets/images/default-avatar.png') ?>" 
                                     alt="<?= h($room['other_user_name']) ?>" 
                                     class="w-12 h-12 rounded-full">
                                <div class="absolute -bottom-1 -right-1 w-4 h-4 rounded-full border-2 border-white <?= $room['other_user_online'] ? 'bg-green-500' : 'bg-gray-400' ?>"></div>
                            </div>
                            
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-sm font-medium text-gray-900 truncate">
                                        <?= h($room['other_user_name']) ?>
                                    </h3>
                                    <div class="flex items-center space-x-2">
                                        <?php if ($room['unread_count'] > 0): ?>
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                <?= $room['unread_count'] ?>
                                            </span>
                                        <?php endif; ?>
                                        <span class="text-xs text-gray-500">
                                            <?= $room['last_message_time'] ? timeAgo($room['last_message_time']) : '' ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <?php if ($room['last_message']): ?>
                                    <p class="text-sm text-gray-500 truncate mt-1">
                                        <?php if ($room['last_message_sender_id'] === $currentUser['id']): ?>
                                            <span class="text-gray-400">あなた: </span>
                                        <?php endif; ?>
                                        <?= h($room['last_message']) ?>
                                    </p>
                                <?php else: ?>
                                    <p class="text-sm text-gray-400 mt-1">まだメッセージがありません</p>
                                <?php endif; ?>
                            </div>
                            
                            <div class="flex-shrink-0">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
