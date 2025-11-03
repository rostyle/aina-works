<?php
require_once '../config/config.php';

$pageTitle = 'ライブコース予約';
$pageDescription = 'ライブコースの予約';

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
    SELECT * FROM course_books WHERE id = ? AND start > NOW()
", [$courseId]);

if (!$course) {
    redirect(url('live-course'));
}

// 既に予約済みかチェック
$existingBooking = $db->selectOne("
    SELECT * FROM course_user_bookings 
    WHERE book_id = ? AND user_id = ?
", [$courseId, $user['id']]);

if ($existingBooking) {
    redirect(url('live-course/detail?id=' . $courseId));
}

// 予約処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = '不正なリクエストです';
    } else {
        try {
            $db->beginTransaction();

            // 予約を登録
            $bookingId = $db->insert("
                INSERT INTO course_user_bookings (book_id, user_id, created_at, updated_at)
                VALUES (?, ?, NOW(), NOW())
            ", [$courseId, $user['id']]);

            // booked_countを増やす
            $currentBookedCount = (int)($course['booked_count'] ?? 0);
            $newBookedCount = $currentBookedCount + 1;
            $db->update("
                UPDATE course_books 
                SET booked_count = ?, updated_at = NOW()
                WHERE id = ?
            ", [(string)$newBookedCount, $courseId]);

            $db->commit();

            // 予約完了ページへリダイレクト
            redirect(url('live-course/detail?id=' . $courseId . '&booked=1'));
        } catch (Exception $e) {
            $db->rollBack();
            $error = $e->getMessage() ?: '予約に失敗しました';
        }
    }
}

$startDate = new DateTime($course['start']);
$endDate = new DateTime($course['end']);
$duration = $startDate->diff($endDate);

include '../includes/header.php';
?>

<!-- Booking Section -->
<section class="py-8 bg-gray-50 min-h-screen">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">ライブコース予約</h1>
        </div>

        <?php if (isset($error)): ?>
            <div class="mb-6 p-4 bg-red-100 border border-red-400 text-red-700 rounded-md">
                <?= h($error) ?>
            </div>
        <?php endif; ?>

        <!-- Course Info Card -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-2xl font-semibold text-gray-900 mb-4">
                <?= h($course['course_name']) ?>
            </h2>

            <div class="flex items-center mb-4">
                <span class="text-gray-700 font-medium">講師: <?= h($course['instructor'] ?? '講師未設定') ?></span>
            </div>

            <?php if ($course['description']): ?>
                <div class="mb-4">
                    <p class="text-gray-600 whitespace-pre-wrap"><?= h($course['description']) ?></p>
                </div>
            <?php endif; ?>

            <div class="space-y-2 mb-4">
                <div class="flex items-center text-gray-700">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span class="font-medium">開催日時: </span>
                    <span><?= $startDate->format('Y年m月d日 H:i') ?></span>
                </div>
                <div class="flex items-center text-gray-700">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="font-medium">所要時間: </span>
                    <span>約<?= ($duration->h * 60 + $duration->i) ?>分</span>
                </div>
                <?php if ($course['place']): ?>
                    <div class="flex items-center text-gray-700">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <span class="font-medium">開催場所: </span>
                        <span><?= h($course['place']) ?></span>
                    </div>
                <?php endif; ?>
                <?php if (!empty($course['point_amount']) && $course['point_amount'] > 0): ?>
                    <div class="flex items-center text-primary-600 font-semibold">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>参加費: <?= number_format($course['point_amount']) ?>ポイント</span>
                    </div>
                <?php else: ?>
                    <div class="flex items-center text-gray-700">
                        <span class="font-medium">参加費: </span>
                        <span>無料</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Booking Form -->
        <form method="POST" class="bg-white rounded-lg shadow-md p-6">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">

            <div class="mb-6">
                <label for="booking_note" class="block text-sm font-medium text-gray-700 mb-2">
                    メッセージ（任意）
                </label>
                <textarea 
                    id="booking_note" 
                    name="booking_note" 
                    rows="4" 
                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500"
                    placeholder="講師へのメッセージや質問があればご記入ください"></textarea>
            </div>

            <div class="flex gap-4">
                <a href="<?= url('live-course') ?>" 
                   class="flex-1 text-center px-6 py-3 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 transition-colors">
                    キャンセル
                </a>
                <button type="submit" 
                        class="flex-1 px-6 py-3 bg-primary-600 text-white rounded-md hover:bg-primary-700 transition-colors">
                    予約を確定する
                </button>
            </div>
        </form>
    </div>
</section>

<?php include '../includes/footer.php'; ?>

