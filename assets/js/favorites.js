/**
 * お気に入り・いいね機能のJavaScript
 */

// いいね機能
async function toggleLike(workId, buttonElement) {
    try {
        // ローディング状態を適用
        if (window.loadingManager) {
            window.loadingManager.setButtonLoading(buttonElement, { 
                text: '処理中...',
                showSpinner: true,
                preserveWidth: false
            });
        } else {
            buttonElement.disabled = true;
        }
        
        const response = await fetch('./api/like.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                work_id: workId
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            // UIを更新
            updateLikeButton(buttonElement, result.is_liked, result.like_count);
        } else {
            if (typeof showNotification === 'function') {
                showNotification(result.message || 'エラーが発生しました', 'error');
            } else {
                alert('エラー: ' + (result.message || '不明なエラー'));
            }
        }
    } catch (error) {
        console.error('Like error:', error);
        if (typeof showNotification === 'function') {
            showNotification('いいね機能でエラーが発生しました', 'error');
        } else {
            alert('いいね機能でエラーが発生しました');
        }
    } finally {
        // ローディング状態を解除
        if (window.loadingManager) {
            window.loadingManager.removeButtonLoading(buttonElement);
        } else {
            buttonElement.disabled = false;
        }
    }
}

// いいねボタンのUI更新
function updateLikeButton(buttonElement, isLiked, likeCount) {
    const icon = buttonElement.querySelector('svg');
    const countElement = buttonElement.querySelector('.like-count');
    
    if (isLiked) {
        icon.classList.remove('text-gray-400');
        icon.classList.add('text-red-500');
        icon.setAttribute('fill', 'currentColor');
        buttonElement.classList.add('text-red-500');
        buttonElement.classList.remove('text-gray-500');
    } else {
        icon.classList.remove('text-red-500');
        icon.classList.add('text-gray-400');
        icon.setAttribute('fill', 'none');
        buttonElement.classList.remove('text-red-500');
        buttonElement.classList.add('text-gray-500');
    }
    
    if (countElement) {
        countElement.textContent = likeCount.toLocaleString();
    }
}

// お気に入り機能
async function toggleFavorite(targetType, targetId, buttonElement) {
    try {
        // ローディング状態を適用
        if (window.loadingManager) {
            window.loadingManager.setButtonLoading(buttonElement, { 
                text: '処理中...',
                showSpinner: true,
                preserveWidth: false
            });
        } else {
            buttonElement.disabled = true;
        }
        
        // 現在の状態を判定
        const isFavorited = buttonElement.classList.contains('favorited');
        const action = isFavorited ? 'remove' : 'add';
        
        const response = await fetch('./api/favorite.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                action: action,
                target_type: targetType,
                target_id: targetId
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            // UIを更新
            updateFavoriteButton(buttonElement, result.is_favorite);
        } else {
            if (typeof showNotification === 'function') {
                showNotification(result.message || 'エラーが発生しました', 'error');
            } else {
                alert('エラー: ' + (result.message || '不明なエラー'));
            }
        }
    } catch (error) {
        console.error('Favorite error:', error);
        if (typeof showNotification === 'function') {
            showNotification('お気に入り機能でエラーが発生しました', 'error');
        } else {
            alert('お気に入り機能でエラーが発生しました');
        }
    } finally {
        // ローディング状態を解除
        if (window.loadingManager) {
            window.loadingManager.removeButtonLoading(buttonElement);
        } else {
            buttonElement.disabled = false;
        }
    }
}

// お気に入りボタンのUI更新
function updateFavoriteButton(buttonElement, isFavorite) {
    const icon = buttonElement.querySelector('svg');
    
    if (isFavorite) {
        icon.classList.remove('text-gray-400');
        icon.classList.add('text-red-500');
        icon.setAttribute('fill', 'currentColor');
        buttonElement.classList.add('favorited', 'text-red-500');
        buttonElement.classList.remove('text-gray-500');
        buttonElement.title = 'お気に入りから削除';
    } else {
        icon.classList.remove('text-red-500');
        icon.classList.add('text-gray-400');
        icon.setAttribute('fill', 'none');
        buttonElement.classList.remove('favorited', 'text-red-500');
        buttonElement.classList.add('text-gray-500');
        buttonElement.title = 'お気に入りに追加';
    }
}

// いいねボタンのHTML生成
function createLikeButton(workId, isLiked = false, likeCount = 0) {
    const fillClass = isLiked ? 'currentColor' : 'none';
    const textClass = isLiked ? 'text-red-500' : 'text-gray-500';
    
    return `
        <button onclick="toggleLike(${workId}, this)" 
                class="flex items-center space-x-1 px-3 py-2 rounded-lg hover:bg-gray-100 transition-colors ${textClass}"
                title="${isLiked ? 'いいねを取り消す' : 'いいね'}">
            <svg class="h-5 w-5" fill="${fillClass}" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
            </svg>
            <span class="like-count">${likeCount.toLocaleString()}</span>
        </button>
    `;
}

// お気に入りボタンのHTML生成
function createFavoriteButton(targetType, targetId, isFavorite = false) {
    const fillClass = isFavorite ? 'currentColor' : 'none';
    const textClass = isFavorite ? 'text-red-500 favorited' : 'text-gray-500';
    const title = isFavorite ? 'お気に入りから削除' : 'お気に入りに追加';
    
    return `
        <button onclick="toggleFavorite('${targetType}', ${targetId}, this)" 
                class="p-2 rounded-lg hover:bg-gray-100 transition-colors ${textClass}"
                title="${title}">
            <svg class="h-5 w-5" fill="${fillClass}" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
            </svg>
        </button>
    `;
}
