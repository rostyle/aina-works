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
            WHEN cr.user1_id = ? THEN u2.last_seen
            ELSE u1.last_seen
        END as other_user_last_seen,
        latest_message.message as last_message,
        latest_message.created_at as last_message_time,
        latest_message.sender_id as last_message_sender_id,
        unread_counts.unread_count
    FROM chat_rooms cr
    LEFT JOIN users u1 ON cr.user1_id = u1.id
    LEFT JOIN users u2 ON cr.user2_id = u2.id
    LEFT JOIN (
        SELECT 
            room_id,
            message,
            created_at,
            sender_id,
            ROW_NUMBER() OVER (PARTITION BY room_id ORDER BY created_at DESC) as rn
        FROM chat_messages
    ) latest_message ON cr.id = latest_message.room_id AND latest_message.rn = 1
    LEFT JOIN (
        SELECT 
            room_id,
            COUNT(CASE WHEN is_read = 0 AND sender_id != ? THEN 1 END) as unread_count
        FROM chat_messages
        GROUP BY room_id
    ) unread_counts ON cr.id = unread_counts.room_id
    WHERE cr.user1_id = ? OR cr.user2_id = ?
    ORDER BY COALESCE(latest_message.created_at, cr.created_at) DESC
", [
    $currentUser['id'], $currentUser['id'], $currentUser['id'], $currentUser['id'], 
    $currentUser['id'], $currentUser['id'], $currentUser['id']
]);

// 選択されたユーザーとのチャットを取得
$targetUserId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
$targetUser = null;
$messages = [];
$roomId = null;

if ($targetUserId) {
    // 相手の情報を取得
    $targetUser = $db->selectOne("SELECT * FROM users WHERE id = ?", [$targetUserId]);
    
    if (!$targetUser) {
        redirect(url('chat')); // ユーザーが存在しない場合はリストへ
    }
    
    // チャットルームの取得または作成
    $chatRoom = $db->selectOne("
        SELECT * FROM chat_rooms 
        WHERE (user1_id = ? AND user2_id = ?) 
           OR (user1_id = ? AND user2_id = ?)
    ", [$currentUser['id'], $targetUserId, $targetUserId, $currentUser['id']]);
    
    if (!$chatRoom) {
        $roomId = $db->insert("
            INSERT INTO chat_rooms (user1_id, user2_id, created_at, updated_at)
            VALUES (?, ?, NOW(), NOW())
        ", [$currentUser['id'], $targetUserId]);
    } else {
        $roomId = $chatRoom['id'];
    }
    
    // メッセージ履歴を取得
    $messages = $db->select("
        SELECT cm.*, u.full_name as sender_name, u.profile_image as sender_image
        FROM chat_messages cm
        JOIN users u ON cm.sender_id = u.id
        WHERE cm.room_id = ?
        ORDER BY cm.created_at ASC
    ", [$roomId]);
    
    // 既読にする
    $db->update("
        UPDATE chat_messages 
        SET is_read = 1 
        WHERE room_id = ? AND sender_id != ? AND is_read = 0
    ", [$roomId, $currentUser['id']]);
}

$pageTitle = 'チャット';
include 'includes/header.php';
?>

<div class="h-[calc(100vh-64px)] bg-gray-50 flex relative overflow-hidden">
    <!-- Sidebar (Chat List) -->
    <aside class="w-full md:w-80 lg:w-96 bg-white/80 backdrop-blur-xl border-r border-white/20 flex flex-col absolute inset-0 z-20 md:relative transform transition-transform duration-300 <?= $targetUserId ? '-translate-x-full md:translate-x-0' : 'translate-x-0' ?>">
        
        <!-- Sidebar Header -->
        <div class="h-16 px-4 border-b border-gray-100 flex items-center justify-between flex-shrink-0 bg-white/50 backdrop-blur-md">
            <h2 class="text-xl font-bold bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">Messages</h2>
            <div class="flex items-center space-x-2">
                <button class="p-2 text-gray-400 hover:text-blue-600 transition-colors rounded-full hover:bg-blue-50">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Search Bar -->
        <div class="p-4 flex-shrink-0">
            <div class="relative">
                <input type="text" placeholder="チャットを検索..." 
                       class="w-full pl-10 pr-4 py-2 bg-gray-100 border-none rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white transition-all text-sm">
                <svg class="w-4 h-4 text-gray-400 absolute left-3 top-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
        </div>

        <!-- Chat List -->
        <div class="flex-1 overflow-y-auto custom-scrollbar px-2 space-y-1 pb-4">
            <?php if (empty($chatRooms)): ?>
                <div class="text-center py-10">
                    <p class="text-gray-400 text-sm">チャット履歴はありません</p>
                </div>
            <?php else: ?>
                <?php foreach ($chatRooms as $room): ?>
                    <?php $isActive = $targetUserId == $room['other_user_id']; ?>
                    <a href="?user_id=<?= $room['other_user_id'] ?>" 
                       class="block p-3 rounded-xl transition-all duration-200 group <?= $isActive ? 'bg-blue-50 shadow-sm ring-1 ring-blue-100' : 'hover:bg-gray-50' ?>">
                        <div class="flex items-center space-x-3">
                            <div class="relative flex-shrink-0">
                                <img src="<?= uploaded_asset($room['other_user_image'] ?? 'assets/images/default-avatar.png') ?>" 
                                     alt="<?= h($room['other_user_name']) ?>" 
                                     class="w-12 h-12 rounded-full object-cover ring-2 ring-white shadow-sm">
                                <?php $isOnline = isset($room['other_user_last_seen']) && (time() - strtotime($room['other_user_last_seen'])) <= 300; ?>
                                <span class="absolute bottom-0 right-0 w-3 h-3 rounded-full border-2 border-white <?= $isOnline ? 'bg-green-500' : 'bg-gray-300' ?>"></span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex justify-between items-baseline mb-1">
                                    <h3 class="text-sm font-bold text-gray-900 truncate"><?= h($room['other_user_name']) ?></h3>
                                    <span class="text-xs text-gray-400"><?= $room['last_message_time'] ? date('H:i', strtotime($room['last_message_time'])) : '' ?></span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <p class="text-xs text-gray-500 truncate max-w-[140px] group-hover:text-gray-700">
                                        <?php if ($room['last_message_sender_id'] === $currentUser['id']): ?>
                                            <span class="text-blue-500">You:</span>
                                        <?php endif; ?>
                                        <?= h($room['last_message'] ?? 'メッセージなし') ?>
                                    </p>
                                    <?php if ($room['unread_count'] > 0): ?>
                                        <span class="flex items-center justify-center w-5 h-5 bg-blue-600 text-white text-[10px] font-bold rounded-full shadow-lg shadow-blue-200">
                                            <?= $room['unread_count'] ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </aside>

    <!-- Main Chat Area -->
    <main class="flex-1 flex flex-col bg-slate-50 relative w-full h-full transform transition-transform duration-300 md:transform-none <?= $targetUserId ? 'translate-x-0' : 'translate-x-full md:translate-x-0' ?>">
        
        <?php if ($targetUserId && $targetUser): ?>
            <!-- Chat Header -->
            <div class="h-16 px-4 md:px-6 border-b border-gray-100 flex items-center justify-between bg-white/80 backdrop-blur-md sticky top-0 z-10 shadow-sm">
                <div class="flex items-center space-x-4">
                    <!-- Back Button (Mobile) -->
                    <a href="<?= url('chat') ?>" class="md:hidden p-2 -ml-2 text-gray-500 hover:text-blue-600 rounded-full hover:bg-gray-100">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </a>
                    
                    <div class="flex items-center space-x-3">
                        <div class="relative">
                            <img src="<?= uploaded_asset($targetUser['profile_image'] ?? 'assets/images/default-avatar.png') ?>" 
                                 class="w-10 h-10 rounded-full object-cover ring-2 ring-white shadow-sm">
                            <div class="absolute bottom-0 right-0 w-2.5 h-2.5 bg-green-500 border-2 border-white rounded-full"></div>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-gray-900"><?= h($targetUser['full_name']) ?></h3>
                            <p class="text-xs text-green-600 font-medium">オンライン</p>
                        </div>
                    </div>
                </div>
                
                <div class="flex items-center space-x-2">
                    <button class="p-2 text-gray-400 hover:text-blue-600 rounded-full hover:bg-blue-50 transition-colors">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </button>
                    <!-- More options -->
                </div>
            </div>

            <!-- Messages Area -->
            <div id="messages-container" class="flex-1 overflow-y-auto p-4 md:p-6 space-y-6 custom-scrollbar bg-[url('assets/images/chat-pattern.png')] bg-repeat bg-opacity-5">
                <!-- Date Separator Example ->
                <div class="flex justify-center">
                    <span class="px-3 py-1 bg-gray-100 text-gray-400 text-xs rounded-full">昨日</span>
                </div>
                -->

                <?php if (empty($messages)): ?>
                    <div class="flex flex-col items-center justify-center h-full text-gray-400 space-y-4">
                        <div class="w-16 h-16 bg-blue-50 rounded-full flex items-center justify-center">
                            <svg class="w-8 h-8 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-3.582 8-8 8a8.955 8.955 0 01-4.126-.98L3 20l1.98-5.874A8.955 8.955 0 013 12c0-4.418 3.582-8 8-8s8 3.582 8 8z" />
                            </svg>
                        </div>
                        <p class="text-sm">メッセージを送って会話を始めましょう</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($messages as $msg): ?>
                        <?php 
                            $isMe = $msg['sender_id'] == $currentUser['id'];
                            $msgTime = date('H:i', strtotime($msg['created_at']));
                        ?>
                        <div class="flex <?= $isMe ? 'justify-end' : 'justify-start' ?> items-end space-x-2 animate-fade-in-up">
                            <?php if (!$isMe): ?>
                                <img src="<?= uploaded_asset($targetUser['profile_image'] ?? 'assets/images/default-avatar.png') ?>" 
                                     class="w-6 h-6 rounded-full object-cover mb-1 shadow-sm">
                            <?php endif; ?>
                            
                            <div class="flex flex-col <?= $isMe ? 'items-end' : 'items-start' ?> max-w-[70%]">
                                <?php if ($msg['file_path']): ?>
                                    <div class="mb-1">
                                        <?php if (preg_match('/\.(jpg|jpeg|png|gif)$/i', $msg['file_path'])): ?>
                                            <a href="<?= uploaded_asset($msg['file_path']) ?>" target="_blank" class="block overflow-hidden rounded-2xl shadow-md hover:shadow-lg transition-shadow">
                                                <img src="<?= uploaded_asset($msg['file_path']) ?>" alt="添付画像" class="max-w-xs max-h-60 object-cover">
                                            </a>
                                        <?php else: ?>
                                            <a href="<?= uploaded_asset($msg['file_path']) ?>" download class="flex items-center p-3 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors shadow-sm">
                                                <svg class="w-8 h-8 text-blue-500 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                </svg>
                                                <div class="text-left">
                                                    <div class="text-sm font-medium text-gray-900 truncate w-32"><?= basename($msg['file_path']) ?></div>
                                                    <div class="text-xs text-blue-500">ダウンロード</div>
                                                </div>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($msg['message']): ?>
                                    <div class="px-4 py-2.5 rounded-2xl shadow-sm <?= $isMe ? 'bg-gradient-to-br from-blue-600 to-blue-700 text-white rounded-br-none' : 'bg-white text-gray-800 border border-gray-100 rounded-bl-none' ?>">
                                        <p class="text-sm leading-relaxed whitespace-pre-wrap"><?= h($msg['message']) ?></p>
                                    </div>
                                <?php endif; ?>
                                
                                <span class="text-[10px] text-gray-400 mt-1 px-1"><?= $msgTime ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Input Area -->
            <div class="p-4 bg-white/90 backdrop-blur-lg border-t border-gray-100 sticky bottom-0 z-20">
                <form id="chat-form" class="flex items-end space-x-3" onsubmit="sendMessage(event)">
                    <input type="hidden" name="room_id" value="<?= $roomId ?>">
                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                    
                    <button type="button" onclick="document.getElementById('file-input').click()" 
                            class="p-3 text-gray-400 hover:text-blue-600 bg-gray-50 hover:bg-blue-50 rounded-full transition-all duration-200">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                        </svg>
                    </button>
                    <input type="file" id="file-input" name="file" class="hidden" onchange="handleFileSelect(event)">
                    
                    <div class="flex-1 bg-gray-50 rounded-2xl border border-gray-200 focus-within:ring-2 focus-within:ring-blue-500 focus-within:bg-white transition-all">
                        <!-- File Preview -->
                        <div id="file-preview" class="hidden px-4 pt-3 pb-0">
                            <div class="flex items-center bg-white p-2 rounded-lg border border-gray-200 shadow-sm">
                                <span id="file-name" class="text-sm text-gray-600 truncate max-w-[200px]"></span>
                                <button type="button" onclick="removeFilePreview()" class="ml-2 text-gray-400 hover:text-red-500">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <textarea name="message" id="message-input" rows="1" 
                                  class="w-full px-4 py-3 bg-transparent border-none focus:ring-0 resize-none max-h-32 text-sm"
                                  placeholder="メッセージを入力..."
                                  oninput="autoResize(this)"></textarea>
                    </div>
                    
                    <button type="submit" 
                            class="p-3 bg-blue-600 hover:bg-blue-700 text-white rounded-full shadow-lg shadow-blue-200 hover:shadow-blue-300 transition-all duration-200 transform hover:scale-105 active:scale-95">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                        </svg>
                    </button>
                </form>
            </div>
            
        <?php else: ?>
            <!-- Empty State for PC -->
            <div class="hidden md:flex flex-col items-center justify-center h-full bg-slate-50 text-center p-8">
                <div class="w-24 h-24 bg-white rounded-full flex items-center justify-center shadow-lg mb-6 animate-bounce-slow">
                    <svg class="w-12 h-12 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-3.582 8-8 8a8.955 8.955 0 01-4.126-.98L3 20l1.98-5.874A8.955 8.955 0 013 12c0-4.418 3.582-8 8-8s8 3.582 8 8z" />
                    </svg>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-2">チャットを選択</h3>
                <p class="text-gray-500 max-w-sm">左側のリストから会話したい相手を選んでください。<br>新しい繋がりが見つかるかもしれません。</p>
            </div>
            <!-- Mobile View checks are handled by CSS classes on main container -->
        <?php endif; ?>
    </main>
</div>

<!-- Custom Styles for Scrollbar & Animations -->
<style>
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background-color: rgba(203, 213, 225, 0.5);
    border-radius: 20px;
}
.custom-scrollbar:hover::-webkit-scrollbar-thumb {
    background-color: rgba(148, 163, 184, 0.8);
}
@keyframes fade-in-up {
    0% { opacity: 0; transform: translateY(10px); }
    100% { opacity: 1; transform: translateY(0); }
}
.animate-fade-in-up {
    animation: fade-in-up 0.3s ease-out forwards;
}
.animate-bounce-slow {
    animation: bounce 3s infinite;
}
</style>

<script>
// Auto resize textarea
function autoResize(textarea) {
    textarea.style.height = 'auto';
    textarea.style.height = textarea.scrollHeight + 'px';
}

// File handling
function handleFileSelect(event) {
    const file = event.target.files[0];
    if (file) {
        document.getElementById('file-preview').classList.remove('hidden');
        document.getElementById('file-name').textContent = file.name;
    }
}

function removeFilePreview() {
    document.getElementById('file-input').value = '';
    document.getElementById('file-preview').classList.add('hidden');
    document.getElementById('file-name').textContent = '';
}

// Scroll to bottom
function scrollToBottom() {
    const container = document.getElementById('messages-container');
    if (container) {
        container.scrollTop = container.scrollHeight;
    }
}

// Initial scroll
window.onload = scrollToBottom;

// API Endpoints
const SEND_MESSAGE_API = '<?= url("api/send-chat-message-v2.php") ?>';
const UPLOAD_FILE_API = '<?= url("api/upload-chat-file-v2.php") ?>';

// Message handling
async function sendMessage(event) {
    event.preventDefault();
    event.stopImmediatePropagation(); // Prevent global loading handler
    
    const form = event.target;
    const formData = new FormData(form);
    const messageInput = document.getElementById('message-input');
    const message = messageInput.value.trim();
    const fileInput = document.getElementById('file-input');
    const file = fileInput.files[0];
    
    if (!message && !file) return;
    
    // Check if it's a file upload
    if (file) {
        await sendFileMessage(formData, form);
        return;
    }
    
    const submitBtn = form.querySelector('button[type="submit"]');
    
    try {
        if (window.loadingManager) {
            window.loadingManager.setButtonLoading(submitBtn, { showSpinner: true, text: '' });
        } else {
            submitBtn.disabled = true;
            submitBtn.dataset.originalHtml = submitBtn.innerHTML;
            submitBtn.innerHTML = '<div class="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin"></div>';
        }
        
        const response = await fetch(SEND_MESSAGE_API, {
            method: 'POST',
            body: formData
        });
        
        const responseText = await response.text();
        let result;
        try {
            // Extract JSON if mixed with HTML
            let jsonText = responseText;
            const jsonStartIndex = responseText.indexOf('{');
            if (jsonStartIndex > -1) {
                jsonText = responseText.substring(jsonStartIndex);
                const jsonEndIndex = jsonText.lastIndexOf('}');
                if (jsonEndIndex > -1) {
                    jsonText = jsonText.substring(0, jsonEndIndex + 1);
                }
            }
            result = JSON.parse(jsonText);
        } catch (e) {
            console.error('JSON Parse Error:', e);
            console.error('Raw response:', responseText);
            const debugInfo = responseText.substring(0, 2000).replace(/</g, '&lt;').replace(/>/g, '&gt;');
            alert('サーバーからの応答を解析できませんでした。\n\n【応答内容の冒頭】\n' + debugInfo + '\n\n※このエラーはサーバー側で予期せぬ出力（警告やエラーメッセージ）が発生している場合に起こります。');
            throw new Error('サーバー応答解析エラー');
        }
        
        if (response.ok && result.success) {
            addMessageToChat(result.data);
            messageInput.value = '';
            messageInput.style.height = 'auto';
            scrollToBottom();
        } else {
            console.error('API Error:', result);
            const errorMsg = result.message || result.error || '原因不明のエラー';
            alert('送信に失敗しました: ' + errorMsg);
            throw new Error(errorMsg);
        }
        
    } catch (error) {
        console.error('Send error:', error);
        // Alert is already handled inside or will be shown here if not "解析エラー"
        if (error.message !== 'サーバー応答解析エラー' && !error.message.includes('送信に失敗しました')) {
            alert('チャット送信中にエラーが発生しました: ' + error.message);
        }
    } finally {
        if (window.loadingManager) {
            window.loadingManager.removeButtonLoading(submitBtn);
        } else {
            submitBtn.disabled = false;
            if (submitBtn.dataset.originalHtml) {
                submitBtn.innerHTML = submitBtn.dataset.originalHtml;
            }
        }
        messageInput.focus();
    }
}

async function sendFileMessage(formData, form) {
    const submitBtn = form ? form.querySelector('button[type="submit"]') : document.querySelector('button[type="submit"]');
    
    try {
        if (window.loadingManager) {
            window.loadingManager.setButtonLoading(submitBtn, { showSpinner: true, text: '' });
        } else {
            submitBtn.disabled = true;
            submitBtn.dataset.originalHtml = submitBtn.innerHTML;
            submitBtn.innerHTML = '<div class="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin"></div>';
        }

        const response = await fetch(UPLOAD_FILE_API, {
            method: 'POST',
            body: formData
        });

        const responseText = await response.text();
        let result;
        try {
            let jsonText = responseText;
            const jsonStartIndex = responseText.indexOf('{');
            if (jsonStartIndex > -1) {
                jsonText = responseText.substring(jsonStartIndex);
                const jsonEndIndex = jsonText.lastIndexOf('}');
                if (jsonEndIndex > -1) {
                    jsonText = jsonText.substring(0, jsonEndIndex + 1);
                }
            }
            result = JSON.parse(jsonText);
        } catch (e) {
            console.error('File Upload JSON Parse Error:', e);
            console.error('Raw response:', responseText);
            const debugInfo = responseText.substring(0, 2000).replace(/</g, '&lt;').replace(/>/g, '&gt;');
            alert('アップロード応答を解析できませんでした。\n\n【応答内容の冒頭】\n' + debugInfo + '\n\n※このエラーはサーバー側で予期せぬ出力（警告やエラーメッセージ）が発生している場合に起こります。');
            throw new Error('サーバー応答解析エラー');
        }

        if (response.ok && result.success) {
            addMessageToChat(result.data);
            document.getElementById('message-input').value = '';
            removeFilePreview();
            scrollToBottom();
        } else {
            console.error('File Upload API Error:', result);
            const errorMsg = result.message || result.error || 'アップロードに失敗しました';
            alert('アップロードに失敗しました: ' + errorMsg);
            throw new Error(errorMsg);
        }
    } catch (error) {
        console.error('Full Upload Error:', error);
        if (error.message !== 'サーバー応答解析エラー' && !error.message.includes('失敗しました')) {
            alert('アップロード中にエラーが発生しました\n詳細: ' + error.message);
        }
    } finally {
        if (window.loadingManager) {
            window.loadingManager.removeButtonLoading(submitBtn);
        } else {
            submitBtn.disabled = false;
            if (submitBtn.dataset.originalHtml) {
                submitBtn.innerHTML = submitBtn.dataset.originalHtml;
            }
        }
    }
}

function addMessageToChat(data) {
    const container = document.getElementById('messages-container');
    const isMe = data.sender_id == <?= $currentUser['id'] ?>;
    
    const div = document.createElement('div');
    div.className = `flex ${isMe ? 'justify-end' : 'justify-start'} items-end space-x-2 animate-fade-in-up`;
    
    let contentHtml = '';
    
    // Profile Image (if not me)
    if (!isMe) {
        contentHtml += `
            <img src="${data.sender_image}" class="w-6 h-6 rounded-full object-cover mb-1 shadow-sm">
        `;
    }
    
    // Message Content Wrapper
    contentHtml += `<div class="flex flex-col ${isMe ? 'items-end' : 'items-start'} max-w-[70%]">`;
    
    // File
    if (data.file_path) {
        const isImage = /\.(jpg|jpeg|png|gif)$/i.test(data.file_path);
        const assetUrl = uploadedAsset(data.file_path);
        const fileName = data.file_path.split('/').pop();
        
        contentHtml += `<div class="mb-1">`;
        if (isImage) {
            contentHtml += `
                <a href="${assetUrl}" target="_blank" class="block overflow-hidden rounded-xl shadow-sm hover:shadow-md transition-shadow">
                    <img src="${assetUrl}" alt="添付画像" class="max-w-xs max-h-60 object-cover">
                </a>
            `;
        } else {
            contentHtml += `
                <a href="${assetUrl}" download class="flex items-center p-3 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors shadow-sm">
                    <div class="text-sm font-medium text-gray-900 truncate w-32">${fileName}</div>
                </a>
            `;
        }
        contentHtml += `</div>`;
    }
    
    // Text Message
    if (data.message) {
        const bgClass = isMe ? 'bg-gradient-to-br from-blue-600 to-blue-700 text-white rounded-br-none' : 'bg-white text-gray-800 border border-gray-100 rounded-bl-none';
        contentHtml += `
            <div class="px-4 py-2.5 rounded-2xl shadow-sm ${bgClass}">
                <p class="text-sm leading-relaxed whitespace-pre-wrap">${escapeHtml(data.message)}</p>
            </div>
        `;
    }
    
    // Time
    contentHtml += `<span class="text-[10px] text-gray-400 mt-1 px-1">${data.time}</span>`;
    contentHtml += `</div>`; // Close wrapper
    
    div.innerHTML = contentHtml;
    container.appendChild(div);
}

// Helper for JS asset path (simplified)
function uploadedAsset(path) {
    if (path.startsWith('http')) return path;
    const baseUrl = '<?= rtrim(BASE_URL, "/") ?>';
    return baseUrl + '/storage/app/uploads/' + path;
}

function escapeHtml(text) {
    if (!text) return '';
    return text
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

function removeFilePreview() {
    const fileInput = document.getElementById('file-input');
    if (fileInput) fileInput.value = '';
    
    const previewContainer = document.getElementById('file-preview-container');
    if (previewContainer) {
        previewContainer.innerHTML = '';
        previewContainer.classList.add('hidden');
    }
}
        .replace(/'/g, "&#039;");
}
</script>

<?php include 'includes/footer.php'; ?>
