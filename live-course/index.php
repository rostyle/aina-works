<?php
require_once '../config/config.php';

$pageTitle = 'ライブコース一覧';
$pageDescription = '利用可能なライブコースを確認';

// ログインチェック
if (!isLoggedIn()) {
    redirect(url('login'));
}

$user = getCurrentUser();
$db = Database::getInstance();

// 公開中のコース一覧を取得（course_booksテーブルから）
$courses = $db->select("
    SELECT 
        cb.*,
        c.name as category_name
    FROM course_books cb
    LEFT JOIN categories c ON cb.category_id = c.id
    WHERE cb.start > NOW()
    ORDER BY cb.start ASC
");

// ユーザーが予約済みのコースIDを取得
$bookedCourseIds = [];
if (!empty($courses)) {
    $courseIds = array_column($courses, 'id');
    $placeholders = str_repeat('?,', count($courseIds) - 1) . '?';
    $bookings = $db->select("
        SELECT book_id FROM course_user_bookings 
        WHERE user_id = ? AND book_id IN ($placeholders)
    ", array_merge([$user['id']], $courseIds));
    $bookedCourseIds = array_column($bookings, 'book_id');
}

include '../includes/header.php';
?>

<!-- Live Courses Section -->
<section class="py-8 bg-gray-50 min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">ライブコース</h1>
            <p class="text-gray-600 mt-2">参加可能なライブコースを確認できます</p>
        </div>

        <!-- Courses Grid -->
        <?php if (empty($courses)): ?>
            <div class="text-center py-12">
                <p class="text-gray-500 text-lg">現在、利用可能なライブコースはありません。</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($courses as $course): ?>
                    <?php
                    $isBooked = in_array($course['id'], $bookedCourseIds);
                    $scheduledDate = new DateTime($course['scheduled_at']);
                    $now = new DateTime();
                    $isPast = $scheduledDate < $now;
                    ?>
                    <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow">
                        <div class="p-6">
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex-1">
                                    <h3 class="text-xl font-semibold text-gray-900 mb-2">
                                        <?= h($course['course_name']) ?>
                                    </h3>
                                    <div class="flex items-center text-sm text-gray-600 mb-3">
                                        <span><?= h($course['instructor'] ?? '講師未設定') ?></span>
                                    </div>
                                </div>
                                <?php if (!empty($course['method']) && strtolower($course['method']) === 'zoom'): ?>
                                    <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs font-medium rounded">
                                        Zoom
                                    </span>
                                <?php endif; ?>
                            </div>

                            <?php if ($course['description']): ?>
                                <p class="text-gray-600 text-sm mb-4 line-clamp-2">
                                    <?= h($course['description']) ?>
                                </p>
                            <?php endif; ?>

                            <div class="space-y-2 mb-4">
                                <?php
                                $startDate = new DateTime($course['start']);
                                $endDate = new DateTime($course['end']);
                                $duration = $startDate->diff($endDate);
                                $isPast = $startDate < new DateTime();
                                ?>
                                <div class="flex items-center text-sm text-gray-600">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <span><?= $startDate->format('Y年m月d日 H:i') ?></span>
                                </div>
                                <div class="flex items-center text-sm text-gray-600">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span>約<?= ($duration->h * 60 + $duration->i) ?>分</span>
                                </div>
                                <?php if ($course['place']): ?>
                                    <div class="flex items-center text-sm text-gray-600">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        <span><?= h($course['place']) ?></span>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($course['booked_count']) && is_numeric($course['booked_count'])): ?>
                                    <div class="flex items-center text-sm text-gray-600">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.5-5.5l-3.5 3.5c-.83.83-1.5 1.5-2.5 2.5-1.5 1.5-3.5 3.5-5.5 5.5H3v-5h5l2-2v-2H5v-5h5l2-2v-2H5V2h5l2-2h5v5l-2 2v2h5v5l-2 2v2h5v5h-2z" />
                                        </svg>
                                        <span>予約数: <?= h($course['booked_count']) ?></span>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($course['point_amount']) && $course['point_amount'] > 0): ?>
                                    <div class="flex items-center text-sm font-semibold text-primary-600">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span><?= number_format($course['point_amount']) ?>ポイント</span>
                                    </div>
                                <?php else: ?>
                                    <div class="flex items-center text-sm text-gray-600">
                                        <span>無料</span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="mt-4">
                                <?php 
                                $isBooked = in_array($course['id'], $bookedCourseIds);
                                ?>
                                <?php if ($isBooked): ?>
                                    <a href="<?= url('live-course/detail?id=' . $course['id']) ?>" 
                                       class="block w-full text-center px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition-colors">
                                        予約済み - 詳細を見る
                                    </a>
                                <?php elseif ($isPast): ?>
                                    <button disabled 
                                            class="block w-full text-center px-4 py-2 bg-gray-300 text-gray-600 rounded-md cursor-not-allowed">
                                        終了済み
                                    </button>
                                <?php else: ?>
                                    <a href="<?= url('live-course/booking?id=' . $course['id']) ?>" 
                                       class="block w-full text-center px-4 py-2 bg-primary-600 text-white rounded-md hover:bg-primary-700 transition-colors">
                                        予約する
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include '../includes/footer.php'; ?>

