# ローディング状態システム 使用例

## 基本的な使用方法

### 1. ボタンのローディング状態

```javascript
// ボタンにローディング状態を適用
const button = document.querySelector('button[type="submit"]');
loadingManager.setButtonLoading(button, { text: '送信中...' });

// 処理完了後に解除
loadingManager.removeButtonLoading(button);
```

### 2. フォーム全体のローディング状態

```javascript
// フォームにローディング状態を適用（自動的に送信ボタンも処理される）
const form = document.querySelector('form');
loadingManager.setFormLoading(form, { message: '送信中...' });

// 処理完了後に解除
loadingManager.removeFormLoading(form);
```

### 3. API呼び出し時の自動ローディング

```javascript
// withLoadingヘルパーを使用
const result = await withLoading(
    fetch('api/endpoint.php', { method: 'POST', body: formData }),
    { button: submitButton, message: '送信中...' }
);
```

### 4. fetchWithLoadingヘルパー

```javascript
// fetch APIのラッパー
const response = await fetchWithLoading(
    'api/endpoint.php',
    { method: 'POST', body: formData },
    { button: submitButton, message: '送信中...' }
);
```

### 5. グローバルローディング

```javascript
// ページ全体にローディングオーバーレイを表示
loadingManager.showGlobalLoading('読み込み中...');

// 解除
loadingManager.hideGlobalLoading();
```

### 6. スケルトンローディング

```javascript
// 要素にスケルトンローディングを適用
const element = document.querySelector('.content-area');
loadingManager.showSkeletonLoading(element, 'card');

// 解除
loadingManager.hideSkeletonLoading(element);
```

### 7. プログレスバー

```javascript
// プログレスバーを表示
const progress = loadingManager.showProgressBar(document.body, {
    value: 0,
    max: 100,
    showPercentage: true
});

// 進捗を更新
progress.update(50); // 50%

// 削除
progress.remove();
```

## 実装例

### フォーム送信

```javascript
document.querySelector('form').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const form = this;
    const formData = new FormData(form);
    
    try {
        // ローディング状態を開始
        loadingManager.setFormLoading(form, { message: '送信中...' });
        
        const response = await fetch('api/submit.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('送信しました', 'success');
        } else {
            showNotification(result.message || 'エラーが発生しました', 'error');
        }
    } catch (error) {
        showNotification('ネットワークエラーが発生しました', 'error');
    } finally {
        // ローディング状態を解除
        loadingManager.removeFormLoading(form);
    }
});
```

### ボタンクリック時のAPI呼び出し

```javascript
button.addEventListener('click', async function() {
    try {
        // ローディング状態を開始
        loadingManager.setButtonLoading(this, { text: '処理中...' });
        
        const response = await fetch('api/action.php', {
            method: 'POST',
            body: JSON.stringify({ action: 'doSomething' })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('処理が完了しました', 'success');
        }
    } catch (error) {
        showNotification('エラーが発生しました', 'error');
    } finally {
        // ローディング状態を解除
        loadingManager.removeButtonLoading(this);
    }
});
```

### withLoadingヘルパーを使用した簡潔な実装

```javascript
button.addEventListener('click', async function() {
    try {
        const result = await withLoading(
            fetch('api/action.php', {
                method: 'POST',
                body: JSON.stringify({ action: 'doSomething' })
            }).then(r => r.json()),
            { button: this, message: '処理中...' }
        );
        
        if (result.success) {
            showNotification('処理が完了しました', 'success');
        }
    } catch (error) {
        showNotification('エラーが発生しました', 'error');
    }
});
```

## 自動処理

すべてのフォームは自動的にローディング状態が適用されます。`data-loading-message`属性でメッセージをカスタマイズできます：

```html
<form data-loading-message="保存中...">
    <!-- フォーム内容 -->
    <button type="submit">保存</button>
</form>
```

## オプション

### setButtonLoading のオプション

- `text`: ローディング中のテキスト（デフォルト: '処理中...'）
- `disable`: ボタンを無効化するか（デフォルト: true）
- `showSpinner`: スピナーを表示するか（デフォルト: true）
- `preserveWidth`: ボタンの幅を保持するか（デフォルト: true）

### setFormLoading のオプション

- `disableAllFields`: すべてのフィールドを無効化するか（デフォルト: true）
- `showOverlay`: オーバーレイを表示するか（デフォルト: true）
- `message`: ローディングメッセージ（デフォルト: '送信中...'）
