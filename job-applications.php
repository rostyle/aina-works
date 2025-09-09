<?php
require_once 'config/config.php';

// ログイン確認
if (!isLoggedIn()) {
    redirect(url('login'));
}

$user = getCurrentUser();
$pageTitle = '応募管理';
$pageDescription = '案件への応募を管理します';

// データベース接続
$db = Database::getInstance();

// 案件ID取得（クライアント用）
$jobId = (int)($_GET['job_id'] ?? 0);

try {
    // クリエイター：自分の応募一覧
    try {
        $applications = $db->select("
            SELECT ja.*, j.title as job_title, j.budget_min, j.budget_max, j.client_id,
                   u.full_name as client_name, u.profile_image as client_image, c.name as category_name
            FROM job_applications ja
            JOIN jobs j ON ja.job_id = j.id
            JOIN users u ON j.client_id = u.id
            LEFT JOIN categories c ON j.category_id = c.id
            WHERE ja.creator_id = ?
            ORDER BY ja.created_at DESC
        ", [$user['id']]);

        // JOINが失敗する場合のフォールバック
        if (empty($applications)) {
            $basicApplications = $db->select("
                SELECT * FROM job_applications WHERE creator_id = ? ORDER BY created_at DESC
            ", [$user['id']]);

            if (!empty($basicApplications)) {
                $applications = [];
                foreach ($basicApplications as $app) {
                    $job = $db->selectOne("SELECT * FROM jobs WHERE id = ?", [$app['job_id']]);
                    if ($job) {
                        $client = $db->selectOne("SELECT full_name, profile_image FROM users WHERE id = ?", [$job['client_id']]);
                        $category = $db->selectOne("SELECT name FROM categories WHERE id = ?", [$job['category_id']]);

                        $app['job_title'] = $job['title'];
                        $app['budget_min'] = $job['budget_min'];
                        $app['budget_max'] = $job['budget_max'];
                        $app['client_id'] = $job['client_id'];
                        $app['client_name'] = $client['full_name'] ?? 'Unknown';
                        $app['client_image'] = $client['profile_image'] ?? null;
                        $app['category_name'] = $category['name'] ?? null;

                        $applications[] = $app;
                    }
                }
            }
        }
    } catch (Exception $e) {
        $applications = [];
    }

    // クライアント：自分の案件への応募一覧
    try {
        if ($jobId) {
            // 特定の案件への応募
            $job = $db->selectOne("
                SELECT * FROM jobs WHERE id = ? AND client_id = ?
            ", [$jobId, $user['id']]);

            if (!$job) {
                setFlash('error', '案件が見つかりません。');
                redirect(url('dashboard'));
            }

            $clientApplications = $db->select("
                SELECT ja.*, u.full_name as creator_name, u.profile_image as creator_image,
                       u.bio as creator_bio, u.experience_years
                FROM job_applications ja
                JOIN users u ON ja.creator_id = u.id
                WHERE ja.job_id = ?
                ORDER BY ja.created_at DESC
            ", [$jobId]);

        } else {
            // 全ての案件への応募
            $clientApplications = $db->select("
                SELECT ja.*, j.title as job_title, u.full_name as creator_name,
                       u.profile_image as creator_image
                FROM job_applications ja
                JOIN jobs j ON ja.job_id = j.id
                JOIN users u ON ja.creator_id = u.id
                WHERE j.client_id = ?
                ORDER BY ja.created_at DESC
            ", [$user['id']]);
        }
    } catch (Exception $e) {
        $clientApplications = [];
    }

} catch (Exception $e) {
    $applications = [];
    $clientApplications = $clientApplications ?? [];
    setFlash('error', 'データの取得に失敗しました。');
}

include 'includes/header.php';
?>

<!-- Applications Management Section -->
<section class="py-8 bg-gray-50 min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">📝 応募管理</h1>
                    <p class="text-gray-600 mt-2">応募した案件と応募された案件を管理できます</p>
                </div>

                <?php if (empty($user['is_creator'])): ?>
                    <div class="flex space-x-3">
                        <?php if ($jobId): ?>
                            <a href="<?= url('job-applications') ?>"
                               class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors">全ての応募を見る</a>
                        <?php endif; ?>
                        <a href="<?= url('dashboard') ?>"
                           class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">ダッシュボード</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Statistics -->
        <?php if (!empty($applications) || !empty($clientApplications)): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <?php if (!empty($applications)): ?>
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                                    <svg class="h-5 w-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-500">応募した案件</p>
                                <p class="text-2xl font-semibold text-gray-900"><?= count($applications) ?>件</p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($clientApplications)): ?>
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                                    <svg class="h-5 w-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-500">応募された案件</p>
                                <p class="text-2xl font-semibold text-gray-900"><?= count($clientApplications) ?>件</p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Empty -->
        <?php if (empty($applications) && empty($clientApplications)): ?>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-12 text-center">
                <svg class="h-16 w-16 text-gray-400 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <h3 class="text-lg font-medium text-gray-900 mb-2">応募がありません</h3>
                <p class="text-gray-600">
                    まだ応募した案件も応募された案件もありません。
                    <a href="<?= url('jobs') ?>" class="text-blue-600 hover:text-blue-500">案件を探す</a>か
                    <a href="<?= url('post-job') ?>" class="text-blue-600 hover:text-blue-500">案件を投稿</a>してみましょう。
                </p>
            </div>
        <?php else: ?>

            <div class="space-y-10">
                <!-- 応募した案件 -->
                <?php if (!empty($applications)): ?>
                    <section aria-labelledby="applied-heading" role="region">
                        <h2 id="applied-heading" class="text-xl font-semibold text-gray-900 mb-4 flex items-center">
                            <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-medium mr-3">応募した案件</span>
                        </h2>

                        <div class="space-y-4">
                            <?php foreach ($applications as $app): ?>
                                <article class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 border-l-4 border-l-blue-500" role="article">
                                    <div class="flex items-start space-x-4">
                                        <div class="flex flex-col items-center space-y-2">
                                            <img src="<?= uploaded_asset($app['client_image'] ?? 'assets/images/default-avatar.png') ?>"
                                                 alt="<?= h($app['client_name'] ?? '依頼者') ?>"
                                                 class="w-16 h-16 rounded-full object-cover" />
                                            <div class="bg-blue-50 border border-blue-200 rounded-lg px-2 py-1">
                                                <div class="flex items-center space-x-1">
                                                    <svg class="h-3 w-3 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                                    </svg>
                                                    <span class="text-xs font-medium text-blue-700">応募した</span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="flex-1">
                                            <h3 class="text-lg font-semibold text-gray-900 mb-2">
                                                <a href="<?= url('job-detail?id=' . $app['job_id']) ?>" class="hover:text-blue-600">
                                                    <?= h($app['job_title'] ?? '案件') ?>
                                                </a>
                                            </h3>

                                            <p class="text-sm text-gray-600 mb-2">
                                                依頼者:
                                                <a href="<?= url('creator-profile?id=' . ($app['client_id'] ?? 0)) ?>"
                                                   class="text-blue-600 hover:text-blue-500 font-medium">
                                                    <?= h($app['client_name'] ?? 'Unknown') ?>
                                                </a>
                                            </p>

                                            <p class="text-sm text-gray-900 font-medium mb-2">
                                                予算: <?= formatPrice($app['budget_min']) ?> - <?= formatPrice($app['budget_max']) ?>
                                            </p>

                                            <p class="text-sm text-gray-600 mb-2">
                                                提案金額: <span class="font-medium text-green-600"><?= formatPrice($app['proposed_price']) ?></span> |
                                                提案期間: <span class="font-medium"><?= (int)$app['proposed_duration'] ?>週間</span>
                                            </p>

                                            <p class="text-xs text-gray-500 mb-4">
                                                応募日: <?= formatDate($app['created_at'], 'Y年m月d日 H:i') ?>
                                            </p>

                                            <div class="flex flex-wrap gap-2" data-application-actions>
                                                <a href="<?= url('job-detail?id=' . $app['job_id']) ?>"
                                                   class="inline-flex items-center px-3 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors">
                                                    案件詳細
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>

                <!-- 応募された案件 -->
                <?php if (!empty($clientApplications)): ?>
                    <section aria-labelledby="received-heading" role="region">
                        <h2 id="received-heading" class="text-xl font-semibold text-gray-900 mb-4 flex items-center">
                            <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-medium mr-3">応募された案件</span>
                        </h2>

                        <div class="space-y-4">
                            <?php foreach ($clientApplications as $app): ?>
                                <article class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 border-l-4 border-l-green-500" role="article">
                                    <div class="flex items-start space-x-4">
                                        <div class="flex flex-col items-center space-y-2">
                                            <img src="<?= uploaded_asset($app['creator_image'] ?? 'assets/images/default-avatar.png') ?>"
                                                 alt="<?= h($app['creator_name'] ?? '応募者') ?>"
                                                 class="w-16 h-16 rounded-full object-cover" />
                                            <div class="bg-green-50 border border-green-200 rounded-lg px-2 py-1">
                                                <div class="flex items-center space-x-1">
                                                    <svg class="h-3 w-3 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 7.89a2 2 0 002.83 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                                    </svg>
                                                    <span class="text-xs font-medium text-green-700">応募された</span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="flex-1">
                                            <h3 class="text-lg font-semibold text-gray-900 mb-2">
                                                <a href="<?= url('creator-profile?id=' . ($app['creator_id'] ?? 0)) ?>" class="hover:text-blue-600">
                                                    <?= h($app['creator_name'] ?? 'Unknown') ?>
                                                </a>
                                            </h3>

                                            <?php if (!$jobId): ?>
                                                <p class="text-sm text-gray-600 mb-2">
                                                    案件:
                                                    <a href="<?= url('job-detail?id=' . $app['job_id']) ?>" class="text-blue-600 hover:text-blue-500">
                                                        <?= h($app['job_title'] ?? '案件') ?>
                                                    </a>
                                                </p>
                                            <?php endif; ?>

                                            <p class="text-sm text-gray-900 font-medium mb-2">
                                                提案金額: <span class="font-medium text-green-600"><?= formatPrice($app['proposed_price']) ?></span> |
                                                提案期間: <span class="font-medium"><?= (int)$app['proposed_duration'] ?>週間</span>
                                            </p>

                                            <div class="bg-gray-50 rounded-lg p-3 mb-3">
                                                <h4 class="text-sm font-medium text-gray-700 mb-1">応募メッセージ</h4>
                                                <p class="text-sm text-gray-600"><?= nl2br(h($app['cover_letter'])) ?></p>
                                            </div>

                                            <p class="text-xs text-gray-500 mb-4">
                                                応募日: <?= formatDate($app['created_at'], 'Y年m月d日 H:i') ?>
                                            </p>

                                            <div class="flex flex-wrap gap-2">
                                                <a href="<?= url('creator-profile?id=' . ($app['creator_id'] ?? 0)) ?>"
                                                   class="inline-flex items-center px-3 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors">
                                                    プロフィール
                                                </a>
                                                <?php if (!$jobId): ?>
                                                    <a href="<?= url('job-detail?id=' . $app['job_id']) ?>"
                                                       class="inline-flex items-center px-3 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors">
                                                        案件詳細
                                                    </a>
                                                <?php endif; ?>
                                                <a href="<?= url('chat?user_id=' . ($app['creator_id'] ?? 0)) ?>"
                                                   class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors">
                                                    <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                                    </svg>
                                                    応募者とチャット
                                                </a>

                                                <?php if (($app['status'] ?? 'pending') === 'pending'): ?>
                                                    <button type="button"
                                                            data-application-id="<?= $app['id'] ?>"
                                                            data-application-action="accept"
                                                            class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                                                        受諾
                                                    </button>
                                                    <button type="button"
                                                            data-application-id="<?= $app['id'] ?>"
                                                            data-application-action="reject"
                                                            class="inline-flex items-center px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition-colors">
                                                        却下
                                                    </button>
                                                <?php else: ?>
                                                    <span class="inline-flex items-center px-3 py-1 text-xs font-medium rounded-full <?= ($app['status'] === 'accepted') ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700' ?>">
                                                        <?= ($app['status'] === 'accepted') ? '受諾済み' : (($app['status'] === 'rejected') ? '却下済み' : '処理済み') ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>
            </div>

        <?php endif; ?>
    </div>
</section>

<?php
$additionalJs = <<<'JS'
<script>
document.addEventListener('click', async function(e) {
    const target = e.target.closest('[data-application-action]');
    if (!target) return;

    const action = target.getAttribute('data-application-action');
    const applicationId = target.getAttribute('data-application-id');
    if (!applicationId) return;

    if (action === 'reject') {
        const confirmed = confirm('この応募を却下しますか？');
        if (!confirmed) return;
    }

    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const formData = new FormData();
    formData.append('application_id', applicationId);
    formData.append('action', action);
    formData.append('csrf_token', csrf);

    // ボタンの二重送信防止
    target.disabled = true;

    try {
        const res = await fetch('api/update-application-status.php', {
            method: 'POST',
            body: formData
        });
        const result = await res.json();

        if (result && result.success) {
            if (typeof showNotification === 'function') {
                showNotification(result.message || '操作が完了しました', 'success');
            }

            if (action === 'accept' && result.redirect_to_chat) {
                setTimeout(() => { window.location.href = result.redirect_to_chat; }, 800);
                return;
            }

            // UI更新：ボタン群をバッジに差し替え
            const container = target.closest('[data-application-actions]');
            if (container) {
                container.querySelectorAll('[data-application-action]').forEach(btn => btn.remove());
                const badge = document.createElement('span');
                const accepted = action === 'accept';
                badge.className = 'inline-flex items-center px-3 py-1 text-xs font-medium rounded-full ' + (accepted ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700');
                badge.textContent = accepted ? '受諾済み' : '却下済み';
                container.appendChild(badge);
            } else {
                location.reload();
            }
        } else {
            if (typeof showNotification === 'function') {
                showNotification((result && result.error) || '操作に失敗しました', 'error');
            }
            target.disabled = false;
        }
    } catch (err) {
        console.error('update application status error', err);
        if (typeof showNotification === 'function') {
            showNotification('ネットワークエラーが発生しました', 'error');
        }
        target.disabled = false;
    }
});
</script>
JS;
?>

<?php include 'includes/footer.php'; ?>
