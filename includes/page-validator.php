<?php
/**
 * ページ検証機能
 * 各ページの先頭でこのファイルをインクルードしてページの存在とアクセス権限をチェック
 */

// 現在のページ名を取得（URLから推測）
function getCurrentPageName() {
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    
    // クエリパラメータを除去
    $requestUri = strtok($requestUri, '?');
    
    // ベースパスを除去
    $basePath = dirname($scriptName);
    if ($basePath !== '/' && $basePath !== '') {
        $requestUri = substr($requestUri, strlen($basePath));
    }
    
    // 先頭のスラッシュを除去
    $requestUri = ltrim($requestUri, '/');
    
    // 末尾のスラッシュを除去
    $requestUri = rtrim($requestUri, '/');
    
    // 空の場合はindex
    if (empty($requestUri)) {
        return 'index';
    }
    
    // 拡張子を除去
    $requestUri = pathinfo($requestUri, PATHINFO_FILENAME);
    
    return $requestUri;
}

// ページ検証を実行
function validateCurrentPage() {
    $pageName = getCurrentPageName();
    
    // 特別なページは検証をスキップ
    $skipValidation = ['404', 'api'];
    if (in_array($pageName, $skipValidation)) {
        return true;
    }
    
    // ページアクセス制御を実行
    validatePageAccess($pageName);
    
    return true;
}

// 自動検証を実行（このファイルがインクルードされた時点で実行）
if (!defined('SKIP_PAGE_VALIDATION')) {
    validateCurrentPage();
}
?>
