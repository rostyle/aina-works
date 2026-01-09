/**
 * 統一されたローディング状態管理システム
 */

// ローディング状態管理クラス
class LoadingManager {
    constructor() {
        this.activeLoadings = new Set();
        this.globalLoading = null;
    }

    /**
     * ボタンにローディング状態を適用
     */
    setButtonLoading(button, options = {}) {
        if (!button) return null;

        const {
            text = '処理中...',
            disable = true,
            showSpinner = true,
            preserveWidth = true
        } = options;

        // 既にローディング中の場合は何もしない
        if (button.dataset.loading === 'true') {
            return null;
        }

        // 元の状態を保存
        const originalState = {
            innerHTML: button.innerHTML,
            disabled: button.disabled,
            width: preserveWidth ? button.offsetWidth + 'px' : null,
            text: button.textContent.trim()
        };

        button.dataset.loading = 'true';
        button.dataset.originalState = JSON.stringify(originalState);

        if (disable) {
            button.disabled = true;
        }

        // ローディング状態のHTMLを生成
        let loadingHTML = '';
        if (showSpinner) {
            loadingHTML += '<svg class="animate-spin -ml-1 mr-2 h-4 w-4 inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">';
            loadingHTML += '<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>';
            loadingHTML += '<path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>';
            loadingHTML += '</svg>';
        }
        loadingHTML += text;

        if (preserveWidth && originalState.width) {
            button.style.minWidth = originalState.width;
        }

        button.innerHTML = loadingHTML;
        button.classList.add('loading-active');

        return originalState;
    }

    /**
     * ボタンのローディング状態を解除
     */
    removeButtonLoading(button) {
        if (!button || button.dataset.loading !== 'true') return;

        try {
            const originalState = JSON.parse(button.dataset.originalState || '{}');

            button.innerHTML = originalState.innerHTML || originalState.text || '';
            button.disabled = originalState.disabled || false;
            button.dataset.loading = 'false';
            delete button.dataset.originalState;
            button.classList.remove('loading-active');

            if (button.style.minWidth) {
                button.style.minWidth = '';
            }
        } catch (e) {
            console.error('Failed to restore button state:', e);
            button.disabled = false;
            button.dataset.loading = 'false';
        }
    }

    /**
     * フォーム全体にローディング状態を適用
     */
    setFormLoading(form, options = {}) {
        if (!form) return;

        const {
            disableAllFields = true,
            showOverlay = true,
            message = '送信中...'
        } = options;

        form.dataset.loading = 'true';

        // すべての入力フィールドを無効化
        if (disableAllFields) {
            const fields = form.querySelectorAll('input, textarea, select, button');
            fields.forEach(field => {
                if (!field.disabled && field.type !== 'submit' && field.type !== 'hidden') {
                    field.dataset.originalDisabled = field.disabled;
                    field.disabled = true;
                }
            });
        }

        // 送信ボタンにローディング状態を適用
        const submitButton = form.querySelector('button[type="submit"], input[type="submit"]');
        if (submitButton) {
            this.setButtonLoading(submitButton, { text: message });
        }

        // オーバーレイを表示
        if (showOverlay) {
            this.showFormOverlay(form, message);
        }
    }

    /**
     * フォームのローディング状態を解除
     */
    removeFormLoading(form) {
        if (!form || form.dataset.loading !== 'true') return;

        form.dataset.loading = 'false';

        // すべての入力フィールドを再有効化
        const fields = form.querySelectorAll('input, textarea, select, button');
        fields.forEach(field => {
            if (field.dataset.originalDisabled !== undefined) {
                field.disabled = field.dataset.originalDisabled === 'true';
                delete field.dataset.originalDisabled;
            }
        });

        // 送信ボタンのローディング状態を解除
        const submitButton = form.querySelector('button[type="submit"], input[type="submit"]');
        if (submitButton) {
            this.removeButtonLoading(submitButton);
        }

        // オーバーレイを非表示
        this.hideFormOverlay(form);
    }

    /**
     * フォームオーバーレイを表示
     */
    showFormOverlay(form, message = '処理中...') {
        const overlayId = 'form-loading-overlay-' + Date.now();
        const overlay = document.createElement('div');
        overlay.id = overlayId;
        overlay.className = 'form-loading-overlay';
        overlay.innerHTML = `
            <div class="form-loading-content">
                <div class="form-loading-spinner"></div>
                <p class="form-loading-message">${message}</p>
            </div>
        `;

        form.style.position = 'relative';
        form.appendChild(overlay);
        form.dataset.overlayId = overlayId;
    }

    /**
     * フォームオーバーレイを非表示
     */
    hideFormOverlay(form) {
        const overlayId = form.dataset.overlayId;
        if (overlayId) {
            const overlay = document.getElementById(overlayId);
            if (overlay) {
                overlay.remove();
            }
            delete form.dataset.overlayId;
        }
    }

    /**
     * グローバルローディングを表示
     */
    showGlobalLoading(message = '読み込み中...') {
        if (this.globalLoading) {
            this.hideGlobalLoading();
        }

        const overlay = document.createElement('div');
        overlay.id = 'global-loading-overlay';
        overlay.className = 'global-loading-overlay';
        overlay.innerHTML = `
            <div class="global-loading-content">
                <div class="global-loading-spinner"></div>
                <p class="global-loading-message">${message}</p>
            </div>
        `;

        document.body.appendChild(overlay);
        document.body.style.overflow = 'hidden';
        this.globalLoading = overlay;
    }

    /**
     * グローバルローディングを非表示
     */
    hideGlobalLoading() {
        if (this.globalLoading) {
            this.globalLoading.remove();
            this.globalLoading = null;
            document.body.style.overflow = '';
        }
    }

    /**
     * 要素にスケルトンローディングを適用
     */
    showSkeletonLoading(element, type = 'default') {
        if (!element) return;

        const skeletonClass = `skeleton-loading skeleton-${type}`;
        element.classList.add(skeletonClass);
        element.dataset.skeletonActive = 'true';
    }

    /**
     * スケルトンローディングを解除
     */
    hideSkeletonLoading(element) {
        if (!element) return;

        element.classList.remove('skeleton-loading');
        const skeletonTypes = ['default', 'text', 'image', 'card', 'list'];
        skeletonTypes.forEach(type => {
            element.classList.remove(`skeleton-${type}`);
        });
        element.dataset.skeletonActive = 'false';
    }

    /**
     * プログレスバーを表示
     */
    showProgressBar(container, options = {}) {
        const {
            value = 0,
            max = 100,
            showPercentage = true,
            animated = true
        } = options;

        const progressId = 'progress-bar-' + Date.now();
        const progressBar = document.createElement('div');
        progressBar.id = progressId;
        progressBar.className = 'progress-bar-container';
        progressBar.innerHTML = `
            <div class="progress-bar-track">
                <div class="progress-bar-fill" style="width: ${value}%"></div>
            </div>
            ${showPercentage ? `<div class="progress-bar-text">${value}%</div>` : ''}
        `;

        if (animated) {
            progressBar.querySelector('.progress-bar-fill').classList.add('animated');
        }

        const targetContainer = container || document.body;
        targetContainer.appendChild(progressBar);

        return {
            element: progressBar,
            update: (newValue) => {
                const fill = progressBar.querySelector('.progress-bar-fill');
                const text = progressBar.querySelector('.progress-bar-text');
                if (fill) {
                    fill.style.width = newValue + '%';
                }
                if (text) {
                    text.textContent = newValue + '%';
                }
            },
            remove: () => {
                progressBar.remove();
            }
        };
    }
}

// グローバルインスタンス
const loadingManager = new LoadingManager();

/**
 * フォーム送信時の自動ローディング処理
 */
function initFormLoadingHandlers() {
    document.querySelectorAll('form').forEach(form => {
        // 既に処理済みのフォームはスキップ
        if (form.dataset.loadingHandler === 'true') {
            return;
        }

        form.dataset.loadingHandler = 'true';

        form.addEventListener('submit', function (e) {
            // バリデーションエラーがある場合はローディングを表示しない
            if (!form.checkValidity()) {
                return;
            }

            // フォームにローディング状態を適用
            loadingManager.setFormLoading(form, {
                message: form.dataset.loadingMessage || '送信中...',
                disableAllFields: false // 同期送信時にデータが送信されるようにフィールド無効化を行わない
            });

            // タイムアウト時のフォールバック（10秒後に自動解除）
            setTimeout(() => {
                if (form.dataset.loading === 'true') {
                    loadingManager.removeFormLoading(form);
                    console.warn('Form loading timeout - auto removed');
                }
            }, 10000);
        });
    });
}

/**
 * API呼び出し用のローディングヘルパー
 */
async function withLoading(promise, options = {}) {
    const {
        button = null,
        form = null,
        global = false,
        message = '処理中...',
        showError = true
    } = options;

    let loadingState = null;

    try {
        // ローディング状態を開始
        if (button) {
            loadingState = loadingManager.setButtonLoading(button, { text: message });
        } else if (form) {
            loadingManager.setFormLoading(form, { message });
        } else if (global) {
            loadingManager.showGlobalLoading(message);
        }

        // プロミスを実行
        const result = await promise;

        return result;
    } catch (error) {
        if (showError) {
            console.error('API call error:', error);
            // エラーノーティフィケーションは別途実装
            if (typeof showNotification === 'function') {
                showNotification('エラーが発生しました。再度お試しください。', 'error');
            }
        }
        throw error;
    } finally {
        // ローディング状態を解除
        if (button) {
            loadingManager.removeButtonLoading(button);
        } else if (form) {
            loadingManager.removeFormLoading(form);
        } else if (global) {
            loadingManager.hideGlobalLoading();
        }
    }
}

/**
 * fetch API のラッパー（自動ローディング対応）
 */
async function fetchWithLoading(url, options = {}, loadingOptions = {}) {
    const {
        button = null,
        form = null,
        global = false,
        message = '通信中...'
    } = loadingOptions;

    return withLoading(
        fetch(url, options),
        { button, form, global, message }
    );
}

// DOMContentLoaded時に初期化
document.addEventListener('DOMContentLoaded', function () {
    initFormLoadingHandlers();
});

// グローバルに公開
window.loadingManager = loadingManager;
window.withLoading = withLoading;
window.fetchWithLoading = fetchWithLoading;
