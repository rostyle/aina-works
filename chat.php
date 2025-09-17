<?php
require_once 'config/config.php';

// ログイン確認
if (!isLoggedIn()) {
    redirect(url('login'));
}

$currentUser = getCurrentUser();
$db = Database::getInstance();

// チャット相手のIDを取得
$otherUserId = (int)($_GET['user_id'] ?? 0);

if (!$otherUserId) {
    redirect(url('work'));
}


// 相手のユーザー情報を取得
$otherUser = $db->selectOne("
    SELECT id, full_name, profile_image, last_seen
    FROM users 
    WHERE id = ? AND is_active = 1
", [$otherUserId]);

if (!$otherUser) {
    redirect(url('work'));
}

// チャットルームを取得または作成
$chatRoom = $db->selectOne("
    SELECT * FROM chat_rooms 
    WHERE (user1_id = ? AND user2_id = ?) OR (user1_id = ? AND user2_id = ?)
", [$currentUser['id'], $otherUserId, $otherUserId, $currentUser['id']]);

if (!$chatRoom) {
    // 新しいチャットルームを作成（自分自身とのチャットは既に上でブロック済み）
    $roomId = $db->insert("
        INSERT INTO chat_rooms (user1_id, user2_id, created_at) 
        VALUES (?, ?, NOW())
    ", [$currentUser['id'], $otherUserId]);
    
    $chatRoom = $db->selectOne("SELECT * FROM chat_rooms WHERE id = ?", [$roomId]);
}

// メッセージを取得
$messages = $db->select("
    SELECT cm.*, u.full_name as sender_name, u.profile_image as sender_image
    FROM chat_messages cm
    JOIN users u ON cm.sender_id = u.id
    WHERE cm.room_id = ?
    ORDER BY cm.created_at ASC
", [$chatRoom['id']]);

// 未読メッセージを既読に更新
$db->update("
    UPDATE chat_messages 
    SET is_read = 1 
    WHERE room_id = ? AND sender_id != ? AND is_read = 0
", [$chatRoom['id'], $currentUser['id']]);

$pageTitle = h($otherUser['full_name']) . ' とのチャット';
$pageDescription = 'チャット';

// URLをハイパーリンクに変換する関数
function convertUrlsToLinks($text) {
    // URLの正規表現パターン（http、https、wwwで始まるURLを検出）
    $urlRegex = '/(https?:\/\/[^\s]+|www\.[^\s]+)/i';
    
    return preg_replace_callback($urlRegex, function($matches) {
        $url = $matches[0];
        // wwwで始まる場合はhttps://を追加
        $href = $url;
        if (strtolower(substr($url, 0, 4)) === 'www.') {
            $href = 'https://' . $url;
        }
        
        return '<a href="' . h($href) . '" target="_blank" rel="noopener noreferrer" class="text-blue-100 hover:text-white underline font-medium">' . h($url) . '</a>';
    }, $text);
}

include 'includes/header.php';
?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- チャットヘッダー -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <a href="<?= url('work') ?>" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </a>
                <img src="<?= uploaded_asset($otherUser['profile_image'] ?? 'assets/images/default-avatar.png') ?>" 
                     alt="<?= h($otherUser['full_name']) ?>" 
                     class="w-12 h-12 rounded-full">
                <div>
                    <h1 class="text-xl font-semibold text-gray-900"><?= h($otherUser['full_name']) ?></h1>
                    <div class="flex items-center space-x-2">
                        <?php 
                        $isOnline = isset($otherUser['last_seen']) && (time() - strtotime($otherUser['last_seen'])) <= 300; // 5分以内
                        ?>
                        <div class="w-2 h-2 rounded-full <?= $isOnline ? 'bg-green-500' : 'bg-gray-400' ?>"></div>
                        <span class="text-sm text-gray-500">
                            <?= $isOnline ? 'オンライン' : ('最終: ' . timeAgo($otherUser['last_seen'])) ?>
                        </span>
                    </div>
                </div>
            </div>
            <div class="flex items-center space-x-2">
                <button onclick="scrollToBottom()" class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-full">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- チャットメッセージエリア -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 h-[calc(100vh-200px)] min-h-[600px] flex flex-col">
        <!-- メッセージリスト -->
        <div id="messages-container" class="flex-1 overflow-y-auto p-4 space-y-4">
            <?php if (empty($messages)): ?>
                <div class="text-center text-gray-500 py-8">
                    <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-3.582 8-8 8a8.955 8.955 0 01-4.126-.98L3 20l1.98-5.874A8.955 8.955 0 013 12c0-4.418 3.582-8 8-8s9 3.582 9 8z" />
                    </svg>
                    <p>まだメッセージがありません</p>
                    <p class="text-sm">メッセージを送信して会話を始めましょう</p>
                </div>
            <?php else: ?>
                <?php foreach ($messages as $message): ?>
                    <div class="flex <?= $message['sender_id'] === $currentUser['id'] ? 'justify-end' : 'justify-start' ?>" data-message-id="<?= $message['id'] ?>">
                        <div class="flex space-x-2 max-w-xs lg:max-w-md">
                            <?php if ($message['sender_id'] !== $currentUser['id']): ?>
                                <img src="<?= uploaded_asset($message['sender_image'] ?? 'assets/images/default-avatar.png') ?>" 
                                     alt="<?= h($message['sender_name']) ?>" 
                                     class="w-8 h-8 rounded-full flex-shrink-0">
                            <?php endif; ?>
                            <div class="flex flex-col <?= $message['sender_id'] === $currentUser['id'] ? 'items-end' : 'items-start' ?>">
                                <div class="px-4 py-2 rounded-lg <?= $message['sender_id'] === $currentUser['id'] ? 'bg-blue-500 text-white' : 'bg-gray-100 text-gray-900' ?>">
                                    <?php if ($message['message_type'] === 'image' && $message['file_path']): ?>
                                        <div class="mb-2">
                                            <img src="<?= uploaded_asset($message['file_path']) ?>" 
                                                 alt="<?= h($message['message']) ?>" 
                                                 class="max-w-xs max-h-64 rounded-lg cursor-pointer hover:opacity-90 transition-opacity"
                                                 onclick="openImageModal('<?= uploaded_asset($message['file_path']) ?>', '<?= h($message['message']) ?>')">
                                        </div>
                                    <?php elseif ($message['message_type'] === 'document' && $message['file_path']): ?>
                                        <div class="mb-2">
                                            <a href="<?= uploaded_asset($message['file_path']) ?>" 
                                               target="_blank" 
                                               class="inline-flex items-center space-x-2 px-3 py-2 bg-white bg-opacity-20 rounded-lg hover:bg-opacity-30 transition-colors">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                </svg>
                                                <span class="text-sm"><?= h($message['message']) ?></span>
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                    <p class="text-sm chat-message"><?= nl2br(convertUrlsToLinks(h($message['message']))) ?></p>
                                </div>
                                <div class="flex items-center space-x-1 mt-1">
                                    <span class="text-xs text-gray-500">
                                        <?= date('H:i', strtotime($message['created_at'])) ?>
                                    </span>
                                    <?php if ($message['sender_id'] === $currentUser['id']): ?>
                                        <svg class="w-3 h-3 <?= $message['is_read'] ? 'text-blue-500' : 'text-gray-400' ?>" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                        </svg>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- メッセージ入力エリア -->
        <div class="border-t border-gray-200 p-4">
            <!-- ファイルプレビューエリア -->
            <div id="file-preview" class="mb-3 hidden">
                <div class="flex items-center space-x-2 p-2 bg-gray-50 rounded-lg">
                    <div id="file-preview-content"></div>
                    <button type="button" onclick="removeFilePreview()" class="text-gray-500 hover:text-gray-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
            
            <form id="message-form" onsubmit="sendMessage(event)" class="flex space-x-3">
                <input type="hidden" name="room_id" value="<?= $chatRoom['id'] ?>">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                <input type="file" id="file-input" name="file" accept="image/*,.pdf" class="hidden" onchange="handleFileSelect(event)">
                
                <div class="flex-1">
                    <textarea id="message-input" name="message" rows="1" 
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"
                              placeholder="メッセージを入力..."
                              onkeydown="handleKeyDown(event)"></textarea>
                </div>
                
                <!-- ファイルアップロードボタン -->
                <button type="button" onclick="document.getElementById('file-input').click()" 
                        class="px-3 py-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-md transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path>
                    </svg>
                </button>
                
                <button type="submit" 
                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                    </svg>
                </button>
            </form>
        </div>
    </div>
</div>

<!-- 画像モーダル -->
<div id="image-modal" class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50 hidden" onclick="closeImageModal()">
    <div class="max-w-4xl max-h-full p-4" onclick="event.stopPropagation()">
        <div class="bg-white rounded-lg overflow-hidden">
            <div class="flex justify-between items-center p-4 border-b">
                <h3 id="image-modal-title" class="text-lg font-semibold"></h3>
                <button onclick="closeImageModal()" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="p-4">
                <img id="image-modal-img" src="" alt="" class="max-w-full max-h-96 mx-auto">
            </div>
        </div>
    </div>
</div>

<script>
const roomId = <?= $chatRoom['id'] ?>;
const currentUserId = <?= $currentUser['id'] ?>;
const otherUserId = <?= $otherUserId ?>;

// 表示済みメッセージIDを追跡
const displayedMessageIds = new Set();

// URLをハイパーリンクに変換する関数
function convertUrlsToLinks(text) {
    // URLの正規表現パターン（http、https、wwwで始まるURLを検出）
    const urlRegex = /(https?:\/\/[^\s]+|www\.[^\s]+)/gi;
    
    return text.replace(urlRegex, function(url) {
        // wwwで始まる場合はhttps://を追加
        let href = url;
        if (url.toLowerCase().startsWith('www.')) {
            href = 'https://' + url;
        }
        
        return `<a href="${href}" target="_blank" rel="noopener noreferrer" class="text-blue-100 hover:text-white underline font-medium">${url}</a>`;
    });
}

// ファイル選択処理
function handleFileSelect(event) {
    const file = event.target.files[0];
    if (!file) return;
    
    // ファイルサイズチェック（10MB）
    const maxSize = 10 * 1024 * 1024;
    if (file.size > maxSize) {
        showNotification('ファイルサイズは10MB以下にしてください', 'error');
        event.target.value = '';
        return;
    }
    
    // ファイル形式チェック
    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'application/pdf'];
    if (!allowedTypes.includes(file.type)) {
        showNotification('画像（JPG, PNG, GIF, WebP）またはPDFファイルのみアップロードできます', 'error');
        event.target.value = '';
        return;
    }
    
    // プレビュー表示
    showFilePreview(file);
}

// ファイルプレビュー表示
function showFilePreview(file) {
    const preview = document.getElementById('file-preview');
    const content = document.getElementById('file-preview-content');
    
    if (file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = function(e) {
            content.innerHTML = `
                <img src="${e.target.result}" alt="${file.name}" class="w-16 h-16 object-cover rounded-lg">
                <span class="text-sm text-gray-600 ml-2">${file.name}</span>
            `;
        };
        reader.readAsDataURL(file);
    } else {
        content.innerHTML = `
            <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <span class="text-sm text-gray-600 ml-2">${file.name}</span>
        `;
    }
    
    preview.classList.remove('hidden');
}

// ファイルプレビュー削除
function removeFilePreview() {
    document.getElementById('file-preview').classList.add('hidden');
    document.getElementById('file-input').value = '';
}

// 画像モーダル表示
function openImageModal(src, title) {
    document.getElementById('image-modal-img').src = src;
    document.getElementById('image-modal-img').alt = title;
    document.getElementById('image-modal-title').textContent = title;
    document.getElementById('image-modal').classList.remove('hidden');
}

// 画像モーダル閉じる
function closeImageModal() {
    document.getElementById('image-modal').classList.add('hidden');
}

// メッセージ送信
async function sendMessage(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    const messageInput = document.getElementById('message-input');
    const message = messageInput.value.trim();
    const fileInput = document.getElementById('file-input');
    const file = fileInput.files[0];
    
    // メッセージとファイルの両方が空の場合は送信しない
    if (!message && !file) return;
    
    // ファイルがある場合はファイルアップロードAPIを使用
    if (file) {
        await sendFileMessage(formData);
        return;
    }
    
    // 送信ボタンを無効化
    const submitBtn = form.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    
    try {
        const response = await fetch('api/send-chat-message.php', {
            method: 'POST',
            body: formData
        });
        
        if (!response.ok) {
            const errorText = await response.text();
            console.error('HTTP Error Response:', errorText);
            throw new Error(`HTTP error! status: ${response.status}, response: ${errorText}`);
        }
        
        const responseText = await response.text();
        console.log('Raw API Response:', responseText);
        
        let result;
        try {
            // HTMLエラーメッセージが混在している場合、JSON部分だけを抽出
            let jsonText = responseText;
            const jsonStartIndex = responseText.indexOf('{');
            if (jsonStartIndex > 0) {
                jsonText = responseText.substring(jsonStartIndex);
                console.log('Extracted JSON from mixed response:', jsonText);
            }
            result = JSON.parse(jsonText);
            console.log('Parsed API Response:', result);
        } catch (parseError) {
            console.error('JSON Parse Error:', parseError);
            console.error('Response text that failed to parse:', responseText);
            throw new Error('Invalid JSON response from server');
        }
        
        if (result.success) {
            // メッセージを即座に表示
            addMessageToChat(result.message);
            messageInput.value = '';
            messageInput.style.height = 'auto';
            scrollToBottom();
            
            // 通知カウントを更新（他のユーザーが受信する）
            if (typeof window.updateNotificationCount === 'function') {
                window.updateNotificationCount();
            }
        } else {
            const errorMessage = result.error || result.message || 'メッセージの送信に失敗しました';
            showNotification(errorMessage, 'error');
        }
        
    } catch (error) {
        console.error('Message send error:', error);
        console.error('Error details:', {
            name: error.name,
            message: error.message,
            stack: error.stack
        });
        showNotification('ネットワークエラーが発生しました: ' + error.message, 'error');
    } finally {
        submitBtn.disabled = false;
        messageInput.focus();
    }
}

// ファイルメッセージ送信
async function sendFileMessage(formData) {
    const submitBtn = document.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    
    try {
        const response = await fetch('api/upload-chat-file.php', {
            method: 'POST',
            body: formData
        });
        
        if (!response.ok) {
            const errorText = await response.text();
            console.error('HTTP Error Response:', errorText);
            throw new Error(`HTTP error! status: ${response.status}, response: ${errorText}`);
        }
        
        const responseText = await response.text();
        console.log('Raw API Response:', responseText);
        
        let result;
        try {
            let jsonText = responseText;
            const jsonStartIndex = responseText.indexOf('{');
            if (jsonStartIndex > 0) {
                jsonText = responseText.substring(jsonStartIndex);
            }
            result = JSON.parse(jsonText);
        } catch (parseError) {
            console.error('JSON Parse Error:', parseError);
            throw new Error('Invalid JSON response from server');
        }
        
        if (result.success) {
            addMessageToChat(result.message);
            document.getElementById('message-input').value = '';
            document.getElementById('file-input').value = '';
            removeFilePreview();
            scrollToBottom();
            
            if (typeof window.updateNotificationCount === 'function') {
                window.updateNotificationCount();
            }
        } else {
            const errorMessage = result.error || result.message || 'ファイルの送信に失敗しました';
            showNotification(errorMessage, 'error');
        }
        
    } catch (error) {
        console.error('File send error:', error);
        showNotification('ネットワークエラーが発生しました: ' + error.message, 'error');
    } finally {
        submitBtn.disabled = false;
        document.getElementById('message-input').focus();
    }
}

// メッセージをチャットに追加
function addMessageToChat(message) {
    console.log('Adding message to chat:', message);
    
    // メッセージオブジェクトの検証
    if (!message || !message.id) {
        console.error('Invalid message object:', message);
        return;
    }
    
    // 既に表示済みのメッセージかチェック
    if (displayedMessageIds.has(message.id)) {
        return;
    }
    
    const messagesContainer = document.getElementById('messages-container');
    
    // 空のメッセージ表示を削除
    const emptyMessage = messagesContainer.querySelector('.text-center');
    if (emptyMessage) {
        emptyMessage.remove();
    }
    
    const messageElement = document.createElement('div');
    messageElement.className = `flex ${message.sender_id === currentUserId ? 'justify-end' : 'justify-start'}`;
    messageElement.dataset.messageId = message.id;
    
    const isOwnMessage = message.sender_id === currentUserId;
    const readIcon = isOwnMessage ? 
        `<svg class="w-3 h-3 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
        </svg>` : '';
    
    // ファイル添付の表示部分
    let fileContent = '';
    if (message.message_type === 'image' && message.file_path) {
        // 画像ファイルのパス処理
        let imageUrl = message.file_path;
        if (imageUrl.startsWith('storage/app/uploads/')) {
            imageUrl = './' + imageUrl;
        } else {
            imageUrl = './storage/app/uploads/' + imageUrl;
        }
        
        fileContent = `
            <div class="mb-2">
                <img src="${imageUrl}" 
                     alt="${message.message}" 
                     class="max-w-xs max-h-64 rounded-lg cursor-pointer hover:opacity-90 transition-opacity"
                     onclick="openImageModal('${imageUrl}', '${message.message}')">
            </div>
        `;
    } else if (message.message_type === 'document' && message.file_path) {
        // PDFファイルのパス処理
        let pdfUrl = message.file_path;
        if (pdfUrl.startsWith('storage/app/uploads/')) {
            pdfUrl = './' + pdfUrl;
        } else {
            pdfUrl = './storage/app/uploads/' + pdfUrl;
        }
        
        fileContent = `
            <div class="mb-2">
                <a href="${pdfUrl}" 
                   target="_blank" 
                   class="inline-flex items-center space-x-2 px-3 py-2 bg-white bg-opacity-20 rounded-lg hover:bg-opacity-30 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <span class="text-sm">${message.message}</span>
                </a>
            </div>
        `;
    }

    messageElement.innerHTML = `
        <div class="flex space-x-2 max-w-xs lg:max-w-md">
            ${!isOwnMessage ? `<img src="${message.sender_image || 'assets/images/default-avatar.png'}" alt="${message.sender_name}" class="w-8 h-8 rounded-full flex-shrink-0">` : ''}
            <div class="flex flex-col ${isOwnMessage ? 'items-end' : 'items-start'}">
                <div class="px-4 py-2 rounded-lg ${isOwnMessage ? 'bg-blue-500 text-white' : 'bg-gray-100 text-gray-900'}">
                    ${fileContent}
                    <p class="text-sm chat-message">${convertUrlsToLinks(message.message).replace(/\n/g, '<br>')}</p>
                </div>
                <div class="flex items-center space-x-1 mt-1">
                    <span class="text-xs text-gray-500">${message.time}</span>
                    ${readIcon}
                </div>
            </div>
        </div>
    `;
    
    messagesContainer.appendChild(messageElement);
    
    // 表示済みメッセージIDに追加
    displayedMessageIds.add(message.id);
}

// キーボードイベント処理
function handleKeyDown(event) {
    if (event.key === 'Enter' && !event.shiftKey) {
        event.preventDefault();
        sendMessage(event);
    }
}

// 自動スクロール
function scrollToBottom() {
    const messagesContainer = document.getElementById('messages-container');
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
}

// テキストエリアの自動リサイズ
document.getElementById('message-input').addEventListener('input', function() {
    this.style.height = 'auto';
    this.style.height = Math.min(this.scrollHeight, 120) + 'px';
});

// ページ読み込み時に最下部にスクロール
window.addEventListener('load', function() {
    // 既存のメッセージIDを追跡リストに追加
    const existingMessages = document.querySelectorAll('#messages-container > div[data-message-id]');
    existingMessages.forEach(element => {
        const messageId = element.dataset.messageId;
        if (messageId) {
            displayedMessageIds.add(parseInt(messageId));
        }
    });
    
    scrollToBottom();
    
    // チャットページを開いたので通知カウントを更新（未読が既読になったため）
    if (typeof window.updateNotificationCount === 'function') {
        setTimeout(() => {
            window.updateNotificationCount();
        }, 1000); // 1秒後に更新（既読更新の処理が完了してから）
    }
});

// 定期的に新しいメッセージをチェック
setInterval(checkNewMessages, 3000);

async function checkNewMessages() {
    try {
        const lastMessageId = getLastMessageId();
        console.log('Checking for new messages, last message ID:', lastMessageId);
        
        const response = await fetch(`api/get-chat-messages.php?room_id=${roomId}&last_message_id=${lastMessageId}`);
        
        if (!response.ok) {
            const errorText = await response.text();
            console.error('Failed to fetch new messages, status:', response.status);
            console.error('Error response:', errorText);
            return;
        }
        
        const responseText = await response.text();
        console.log('Raw response for new messages:', responseText);
        
        let result;
        try {
            // HTMLエラーメッセージが混在している場合、JSON部分だけを抽出
            let jsonText = responseText;
            const jsonStartIndex = responseText.indexOf('{');
            if (jsonStartIndex > 0) {
                jsonText = responseText.substring(jsonStartIndex);
                console.log('Extracted JSON from mixed response:', jsonText);
            }
            result = JSON.parse(jsonText);
        } catch (parseError) {
            console.error('JSON Parse Error for new messages:', parseError);
            console.error('Response text that failed to parse:', responseText);
            return;
        }
        console.log('New messages check result:', result);
        
        if (result.success && result.messages.length > 0) {
            console.log('Found', result.messages.length, 'new messages');
            result.messages.forEach(message => {
                addMessageToChat(message);
            });
            scrollToBottom();
            
            // 新しいメッセージを受信したので通知カウントを更新
            if (typeof window.updateNotificationCount === 'function') {
                window.updateNotificationCount();
            }
        }
    } catch (error) {
        console.error('Check messages error:', error);
    }
}

function getLastMessageId() {
    const messages = document.querySelectorAll('#messages-container > div');
    if (messages.length === 0) return 0;
    
    const lastMessage = messages[messages.length - 1];
    return lastMessage.dataset.messageId || 0;
}

// 通知表示機能
function showNotification(message, type = 'info') {
    const existing = document.querySelector('.notification');
    if (existing) {
        existing.remove();
    }
    
    const notification = document.createElement('div');
    notification.className = `notification fixed top-4 right-4 px-4 py-2 rounded-md text-white z-50 transition-all duration-300 ${
        type === 'success' ? 'bg-green-500' : 
        type === 'error' ? 'bg-red-500' : 
        'bg-blue-500'
    }`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        if (notification.parentNode) {
            notification.style.opacity = '0';
            notification.style.transform = 'translateY(-10px)';
            setTimeout(() => notification.remove(), 300);
        }
    }, 3000);
}
</script>

<?php include 'includes/footer.php'; ?>
