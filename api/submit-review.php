<?php
/**
 * 作品へのレビュー（フォーム送信 → 作品ページへ戻る）
 * 同じ人が同じ作品に書けるのは1件。2回目以降は内容の更新になる。
 */
require_once __DIR__ . '/../config/config.php';

$workId = (int)($_POST['work_id'] ?? 0);
$back = url('work-detail?id=' . $workId);

if (!isLoggedIn()) {
    setFlash('error', 'レビューを書くにはログインしてください。');
    redirect(url('login?redirect=' . rawurlencode('work-detail?id=' . $workId)));
}
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST' || !verifyCsrfToken(csrfTokenFromRequest())) {
    setFlash('error', '画面の有効期限が切れました。もう一度お試しください。');
    redirect($back);
}

try {
    $db = Database::getInstance();
    $currentUser = getCurrentUser();

    $rating = (int)($_POST['rating'] ?? 0);
    $comment = trim((string)($_POST['comment'] ?? ''));

    if ($workId <= 0) {
        redirect(url('work'));
    }
    if ($rating < 1 || $rating > 5) {
        setFlash('error', '評価は1〜5で選んでください。');
        redirect($back);
    }
    if ($comment === '' || mb_strlen($comment) > 1000) {
        setFlash('error', 'コメントを1,000文字以内で入力してください。');
        redirect($back);
    }
    if (!rateLimitAllow('review_post', (string)$currentUser['id'], 10, 3600)) {
        setFlash('error', '短時間にレビューが続いています。少し時間をおいてから投稿してください。');
        redirect($back);
    }

    $work = $db->selectOne("
        SELECT w.id, w.title, w.user_id AS creator_id
        FROM works w
        WHERE w.id = ? AND w.status = 'published'
    ", [$workId]);
    if (!$work) {
        setFlash('error', Messages::WORK_NOT_FOUND);
        redirect(url('work'));
    }
    if ((int)$work['creator_id'] === (int)$currentUser['id']) {
        setFlash('error', Messages::WORK_REVIEW_OWN);
        redirect($back);
    }

    // 1人1作品1件（既にあれば更新）
    $existing = $db->selectOne(
        "SELECT id FROM reviews WHERE reviewer_id = ? AND work_id = ? ORDER BY id DESC LIMIT 1",
        [$currentUser['id'], $workId]
    );
    if ($existing) {
        $db->update("UPDATE reviews SET rating = ?, comment = ? WHERE id = ?", [$rating, $comment, $existing['id']]);
        setFlash('success', 'レビューを更新しました。');
        redirect($back);
    }

    $db->insert("
        INSERT INTO reviews (reviewer_id, reviewee_id, work_id, rating, comment, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ", [$currentUser['id'], $work['creator_id'], $workId, $rating, $comment]);

    try {
        $creator = $db->selectOne("SELECT email, full_name FROM users WHERE id = ? AND is_active = 1", [$work['creator_id']]);
        if ($creator) {
            $subject = "【AiNA Works】あなたの作品にレビューが投稿されました";
            $message  = "{$currentUser['full_name']}さんがあなたの作品「{$work['title']}」にレビューを投稿しました。\n\n";
            $message .= "評価: " . str_repeat('★', $rating) . str_repeat('☆', 5 - $rating) . " ({$rating}/5)\n\n";
            $message .= "コメント:\n" . $comment;
            sendNotificationMail($creator['email'], $subject, $message, url('work-detail?id=' . $workId, true), 'レビューを確認する');
        }
    } catch (Throwable $e) {
        error_log('レビュー通知メール送信エラー: ' . $e->getMessage());
    }

    setFlash('success', 'レビューを投稿しました。ありがとうございます！');
    redirect($back);
} catch (Throwable $e) {
    error_log('Review submission error: ' . $e->getMessage());
    setFlash('error', 'レビューを保存できませんでした。時間をおいて再度お試しください。');
    redirect($back);
}
