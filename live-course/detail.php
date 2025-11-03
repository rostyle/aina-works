<?php
require_once '../config/config.php';

$pageTitle = 'ライブコース詳細';
$pageDescription = 'ライブコースの詳細情報';

// ログインチェック
if (!isLoggedIn()) {
    redirect(url('login'));
}

$user = getCurrentUser();
$db = Database::getInstance();

$courseId = (int)($_GET['id'] ?? 0);

if (!$courseId) {
    redirect(url('live-course'));
}

// コース情報を取得（course_booksテーブルから）
$course = $db->selectOne("
    SELECT * FROM course_books WHERE id = ?
", [$courseId]);

if (!$course) {
    redirect(url('live-course'));
}

// 予約状況を確認
$booking = $db->selectOne("
    SELECT * FROM course_user_bookings 
    WHERE book_id = ? AND user_id = ?
", [$courseId, $user['id']]);

$isBooked = !empty($booking);
$startDate = new DateTime($course['start']);
$endDate = new DateTime($course['end']);
$now = new DateTime();
$isPast = $startDate < $now;
$duration = $startDate->diff($endDate);
$isZoom = !empty($course['method']) && strtolower($course['method']) === 'zoom';

// 予約完了メッセージ
$bookingSuccess = isset($_GET['booked']) && $_GET['booked'] == '1';

include '../includes/header.php';
?>

<!-- Course Detail Section -->
<section class="py-8 bg-gray-50 min-h-screen">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <a href="<?= url('live-course') ?>" class="inline-flex items-center text-primary-600 hover:text-primary-700 mb-4">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                ライブコース一覧に戻る
            </a>
            <h1 class="text-3xl font-bold text-gray-900"><?= h($course['course_name']) ?></h1>
        </div>

        <?php if ($bookingSuccess): ?>
            <div class="mb-6 p-4 bg-green-100 border border-green-400 text-green-700 rounded-md">
                <p class="font-semibold">予約が完了しました！</p>
                <p class="text-sm mt-1">下記のZoom情報をご確認ください。</p>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Course Info Card -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">コース詳細</h2>
                    
                    <?php if ($course['description']): ?>
                        <div class="mb-6">
                            <p class="text-gray-700 whitespace-pre-wrap"><?= h($course['description']) ?></p>
                        </div>
                    <?php endif; ?>

                    <div class="space-y-3">
                            <div class="flex items-start">
                            <svg class="w-5 h-5 mr-3 text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <div>
                                <p class="text-sm font-medium text-gray-500">開催日時</p>
                                <p class="text-gray-900"><?= $startDate->format('Y年m月d日 H:i') ?> ～ <?= $endDate->format('H:i') ?></p>
                            </div>
                        </div>

                        <div class="flex items-start">
                            <svg class="w-5 h-5 mr-3 text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <div>
                                <p class="text-sm font-medium text-gray-500">所要時間</p>
                                <p class="text-gray-900">約<?= ($duration->h * 60 + $duration->i) ?>分</p>
                            </div>
                        </div>

                        <?php if ($course['place']): ?>
                            <div class="flex items-start">
                                <svg class="w-5 h-5 mr-3 text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <div>
                                    <p class="text-sm font-medium text-gray-500">開催場所</p>
                                    <p class="text-gray-900"><?= h($course['place']) ?></p>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($course['point_amount']) && $course['point_amount'] > 0): ?>
                            <div class="flex items-start">
                                <svg class="w-5 h-5 mr-3 text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <div>
                                    <p class="text-sm font-medium text-gray-500">参加費</p>
                                    <p class="text-gray-900 font-semibold"><?= number_format($course['point_amount']) ?>ポイント</p>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="flex items-start">
                                <svg class="w-5 h-5 mr-3 text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <div>
                                    <p class="text-sm font-medium text-gray-500">参加費</p>
                                    <p class="text-gray-900">無料</p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Zoom Meeting Info (予約済みの場合のみ表示) -->
                <?php if ($isBooked && $isZoom): ?>
                    <div class="bg-blue-50 border border-blue-200 rounded-lg shadow-md p-6">
                        <div class="flex items-center mb-4">
                            <svg class="w-6 h-6 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                            <h3 class="text-xl font-semibold text-blue-900">Zoomミーティング情報</h3>
                        </div>

                        <?php if ($course['place'] && (strpos($course['place'], 'http') === 0 || strpos($course['place'], 'zoom.us') !== false)): ?>
                            <div class="mb-4">
                                <p class="text-sm font-medium text-blue-700 mb-2">ミーティングURL</p>
                                <div class="flex items-center bg-white rounded-md p-3 border border-blue-200">
                                    <a href="<?= h($course['place']) ?>" 
                                       target="_blank" 
                                       rel="noopener noreferrer"
                                       class="flex-1 text-blue-600 hover:text-blue-800 break-all">
                                        <?= h($course['place']) ?>
                                    </a>
                                    <button onclick="copyToClipboard('<?= h($course['place']) ?>')" 
                                            class="ml-2 px-3 py-1 bg-blue-600 text-white text-sm rounded hover:bg-blue-700">
                                        コピー
                                    </button>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($course['zoom_id'])): ?>
                            <div class="mb-4">
                                <p class="text-sm font-medium text-blue-700 mb-2">ミーティングID</p>
                                <div class="flex items-center bg-white rounded-md p-3 border border-blue-200">
                                    <span class="flex-1 font-mono text-gray-900"><?= h($course['zoom_id']) ?></span>
                                    <button onclick="copyToClipboard('<?= h($course['zoom_id']) ?>')" 
                                            class="ml-2 px-3 py-1 bg-blue-600 text-white text-sm rounded hover:bg-blue-700">
                                        コピー
                                    </button>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($course['zoom_password'])): ?>
                            <div class="mb-4">
                                <p class="text-sm font-medium text-blue-700 mb-2">パスコード</p>
                                <div class="flex items-center bg-white rounded-md p-3 border border-blue-200">
                                    <span class="flex-1 font-mono text-gray-900"><?= h($course['zoom_password']) ?></span>
                                    <button onclick="copyToClipboard('<?= h($course['zoom_password']) ?>')" 
                                            class="ml-2 px-3 py-1 bg-blue-600 text-white text-sm rounded hover:bg-blue-700">
                                        コピー
                                    </button>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ($course['place'] && (strpos($course['place'], 'http') === 0 || strpos($course['place'], 'zoom.us') !== false)): ?>
                            <div class="mt-4">
                                <a href="<?= h($course['place']) ?>" 
                                   target="_blank" 
                                   rel="noopener noreferrer"
                                   class="inline-flex items-center px-6 py-3 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                    Zoomミーティングに参加
                                </a>
                            </div>
                        <?php elseif ($course['place']): ?>
                            <div class="mt-4">
                                <p class="text-sm font-medium text-blue-700 mb-2">開催場所</p>
                                <p class="text-gray-900"><?= h($course['place']) ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Instructor Info -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">講師</h3>
                    <div class="mb-4">
                        <p class="font-semibold text-gray-900"><?= h($course['instructor'] ?? '講師未設定') ?></p>
                    </div>
                    <?php if ($course['description']): ?>
                        <p class="text-sm text-gray-600"><?= h($course['description']) ?></p>
                    <?php endif; ?>
                </div>

                <!-- Booking Status -->
                <?php if ($isBooked): ?>
                    <div class="bg-green-50 border border-green-200 rounded-lg shadow-md p-6">
                        <div class="flex items-center mb-2">
                            <svg class="w-6 h-6 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <p class="font-semibold text-green-900">予約済み</p>
                        </div>
                        <p class="text-sm text-green-700">
                            このコースへの予約が完了しています。
                        </p>
                    </div>
                <?php elseif (!$isPast): ?>
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <a href="<?= url('live-course/booking?id=' . $courseId) ?>" 
                           class="block w-full text-center px-6 py-3 bg-primary-600 text-white rounded-md hover:bg-primary-700 transition-colors">
                            予約する
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        alert('クリップボードにコピーしました');
    }, function(err) {
        console.error('コピーに失敗しました:', err);
        // フォールバック: テキストエリアを使用
        const textarea = document.createElement('textarea');
        textarea.value = text;
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
        alert('クリップボードにコピーしました');
    });
}
</script>

<?php include '../includes/footer.php'; ?>

