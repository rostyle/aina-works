<?php
require_once __DIR__ . '/config/config.php';

// 404エラーのHTTPステータスコードを設定（403・500から呼ばれた場合も同じ画面）
if (http_response_code() < 400) {
    http_response_code(404);
}

$pageTitle = 'ページが見つかりません';
$noindex = true;
include __DIR__ . '/includes/header.php';
?>
<section class="min-h-[60vh] flex items-center justify-center py-12 px-4">
    <div class="max-w-md w-full space-y-6 text-center">
        <div class="mx-auto h-20 w-20 text-gray-400">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 12h6m-6-4h6m2 5.291A7.962 7.962 0 0112 15c-2.34 0-4.29-1.009-5.824-2.709M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
            </svg>
        </div>
        <h1 class="text-2xl font-extrabold text-gray-900">ページが見つかりません</h1>
        <p class="text-sm text-gray-600">URLが間違っているか、ページが削除・移動された可能性があります。ログインが必要なページの場合は、ログインしてからもう一度開いてください。</p>
        <div class="flex flex-col sm:flex-row gap-3 justify-center">
            <a href="<?= h(url()) ?>" class="inline-flex justify-center items-center px-5 py-3 rounded-lg text-white bg-blue-600 hover:bg-blue-700 font-semibold">トップに戻る</a>
            <a href="<?= h(url('jobs')) ?>" class="inline-flex justify-center items-center px-5 py-3 rounded-lg border border-gray-300 text-gray-700 bg-white hover:bg-gray-50 font-semibold">募集中の案件を見る</a>
        </div>
    </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
