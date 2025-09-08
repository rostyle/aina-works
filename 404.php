<?php
require_once 'config/config.php';

// 404エラーのHTTPステータスコードを設定
http_response_code(404);

$pageTitle = 'ページが見つかりません - ' . SITE_NAME;
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($pageTitle) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="<?= asset('css/custom.css') ?>" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <?php include 'includes/header.php'; ?>
    
    <main class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8 text-center">
            <div>
                <div class="mx-auto h-24 w-24 text-gray-400">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 12h6m-6-4h6m2 5.291A7.962 7.962 0 0112 15c-2.34 0-4.29-1.009-5.824-2.709M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                </div>
                <h2 class="mt-6 text-3xl font-extrabold text-gray-900">
                    404 - ページが見つかりません
                </h2>
                <p class="mt-2 text-sm text-gray-600">
                    お探しのページは存在しないか、移動された可能性があります。
                </p>
            </div>
            
            <div class="space-y-4">
                <div class="text-left bg-white p-4 rounded-lg shadow">
                    <h3 class="text-lg font-medium text-gray-900 mb-2">考えられる原因：</h3>
                    <ul class="text-sm text-gray-600 space-y-1">
                        <li>• URLが間違っている</li>
                        <li>• ページが削除された</li>
                        <li>• ページが移動された</li>
                        <li>• アクセス権限がない</li>
                    </ul>
                </div>
                
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="<?= url() ?>" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        ホームに戻る
                    </a>
                    <button onclick="history.back()" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        前のページに戻る
                    </button>
                </div>
            </div>
            
            <div class="text-xs text-gray-500">
                <p>エラーコード: 404</p>
                <p>リクエストURL: <?= h($_SERVER['REQUEST_URI'] ?? '') ?></p>
                <p>時刻: <?= date('Y-m-d H:i:s') ?></p>
            </div>
        </div>
    </main>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>
