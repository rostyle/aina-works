<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/admin_layout.php';
requireAdmin();

$db = Database::getInstance();

// Collect high-level metrics
try {
    $metrics = [
        'users_total'      => (int)($db->selectOne("SELECT COUNT(*) AS c FROM users")['c'] ?? 0),
        'users_active'     => (int)($db->selectOne("SELECT COUNT(*) AS c FROM users WHERE is_active = 1")['c'] ?? 0),
        'works_published'  => (int)($db->selectOne("SELECT COUNT(*) AS c FROM works WHERE status = 'published'")['c'] ?? 0),
        'jobs_open'        => (int)($db->selectOne("SELECT COUNT(*) AS c FROM jobs WHERE status = 'open'")['c'] ?? 0),
        'jobs_progress'    => (int)($db->selectOne("SELECT COUNT(*) AS c FROM jobs WHERE status IN ('in_progress','contracted','delivered')")['c'] ?? 0),
        'jobs_completed'   => (int)($db->selectOne("SELECT COUNT(*) AS c FROM jobs WHERE status = 'completed'")['c'] ?? 0),
        'applications'     => (int)($db->selectOne("SELECT COUNT(*) AS c FROM job_applications")['c'] ?? 0),
        'reviews'          => (int)($db->selectOne("SELECT COUNT(*) AS c FROM reviews")['c'] ?? 0),
        'categories'       => (int)($db->selectOne("SELECT COUNT(*) AS c FROM categories")['c'] ?? 0),
        'chat_rooms'       => (int)($db->selectOne("SELECT COUNT(*) AS c FROM chat_rooms")['c'] ?? 0),
        'chat_messages'    => (int)($db->selectOne("SELECT COUNT(*) AS c FROM chat_messages")['c'] ?? 0),
        'success_stories'  => (int)($db->selectOne("SELECT COUNT(*) AS c FROM success_stories")['c'] ?? 0),
    ];
} catch (Exception $e) {
    $metrics = [];
}

renderAdminHeader('ダッシュボード', 'dashboard');
?>

<h1 class="text-2xl font-bold text-gray-900 mb-6">ダッシュボード</h1>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
    <div class="bg-white border rounded-xl p-5">
        <p class="text-gray-500 text-sm">ユーザー</p>
        <p class="text-3xl font-semibold text-gray-900"><?= number_format($metrics['users_total'] ?? 0) ?></p>
        <p class="text-xs text-green-600 mt-1">アクティブ: <?= number_format($metrics['users_active'] ?? 0) ?></p>
    </div>
    <div class="bg-white border rounded-xl p-5">
        <p class="text-gray-500 text-sm">公開作品</p>
        <p class="text-3xl font-semibold text-gray-900"><?= number_format($metrics['works_published'] ?? 0) ?></p>
    </div>
    <div class="bg-white border rounded-xl p-5">
        <p class="text-gray-500 text-sm">案件（募集中）</p>
        <p class="text-3xl font-semibold text-gray-900"><?= number_format($metrics['jobs_open'] ?? 0) ?></p>
        <p class="text-xs text-blue-600 mt-1">進行中含む: <?= number_format($metrics['jobs_progress'] ?? 0) ?></p>
    </div>
    <div class="bg-white border rounded-xl p-5">
        <p class="text-gray-500 text-sm">完了案件</p>
        <p class="text-3xl font-semibold text-gray-900"><?= number_format($metrics['jobs_completed'] ?? 0) ?></p>
    </div>
    <div class="bg-white border rounded-xl p-5">
        <p class="text-gray-500 text-sm">応募</p>
        <p class="text-3xl font-semibold text-gray-900"><?= number_format($metrics['applications'] ?? 0) ?></p>
    </div>
    <div class="bg-white border rounded-xl p-5">
        <p class="text-gray-500 text-sm">レビュー</p>
        <p class="text-3xl font-semibold text-gray-900"><?= number_format($metrics['reviews'] ?? 0) ?></p>
    </div>
    <div class="bg-white border rounded-xl p-5">
        <p class="text-gray-500 text-sm">カテゴリ</p>
        <p class="text-3xl font-semibold text-gray-900"><?= number_format($metrics['categories'] ?? 0) ?></p>
    </div>
    <div class="bg-white border rounded-xl p-5">
        <p class="text-gray-500 text-sm">チャット</p>
        <p class="text-3xl font-semibold text-gray-900"><?= number_format($metrics['chat_messages'] ?? 0) ?></p>
        <p class="text-xs text-gray-600 mt-1">ルーム: <?= number_format($metrics['chat_rooms'] ?? 0) ?></p>
    </div>
    <div class="bg-white border rounded-xl p-5">
        <p class="text-gray-500 text-sm">会員インタビュー</p>
        <p class="text-3xl font-semibold text-gray-900"><?= number_format($metrics['success_stories'] ?? 0) ?></p>
    </div>
</div>

<div class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-6">
    <a href="<?= h(adminUrl('users.php')) ?>" class="block bg-white border rounded-xl p-5 hover:border-blue-400 transition">
        <h2 class="font-semibold text-gray-900 mb-2">ユーザー管理</h2>
        <p class="text-gray-600 text-sm">ユーザーの検索・無効化・ロール付与（admin等）</p>
    </a>
    <a href="<?= h(adminUrl('works.php')) ?>" class="block bg-white border rounded-xl p-5 hover:border-blue-400 transition">
        <h2 class="font-semibold text-gray-900 mb-2">作品管理</h2>
        <p class="text-gray-600 text-sm">作品の公開状態・削除を管理</p>
    </a>
    <a href="<?= h(adminUrl('jobs.php')) ?>" class="block bg-white border rounded-xl p-5 hover:border-blue-400 transition">
        <h2 class="font-semibold text-gray-900 mb-2">案件管理</h2>
        <p class="text-gray-600 text-sm">案件の状態変更・削除を管理</p>
    </a>
    <a href="<?= h(adminUrl('reviews.php')) ?>" class="block bg-white border rounded-xl p-5 hover:border-blue-400 transition">
        <h2 class="font-semibold text-gray-900 mb-2">レビュー管理</h2>
        <p class="text-gray-600 text-sm">不適切なレビューの削除</p>
    </a>
    <a href="<?= h(adminUrl('applications.php')) ?>" class="block bg-white border rounded-xl p-5 hover:border-blue-400 transition">
        <h2 class="font-semibold text-gray-900 mb-2">応募管理</h2>
        <p class="text-gray-600 text-sm">応募の状態確認や調整</p>
    </a>
    <a href="<?= h(adminUrl('categories.php')) ?>" class="block bg-white border rounded-xl p-5 hover:border-blue-400 transition">
        <h2 class="font-semibold text-gray-900 mb-2">カテゴリ管理</h2>
        <p class="text-gray-600 text-sm">カテゴリの有効化や表示順を管理</p>
    </a>
    <a href="<?= h(adminUrl('success_stories.php')) ?>" class="block bg-white border rounded-xl p-5 hover:border-blue-400 transition">
        <h2 class="font-semibold text-gray-900 mb-2">インタビュー管理</h2>
        <p class="text-gray-600 text-sm">会員インタビュー記事の作成・編集</p>
    </a>
</div>

<?php renderAdminFooter();
