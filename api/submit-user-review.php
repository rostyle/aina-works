<?php
/**
 * 取引相手へのレビュー（フォーム送信 → プロフィールへ戻る）
 * AiNA Works上で採用（受諾）された取引がある相手にだけ書ける。1人につき1件で、2回目以降は更新。
 */
require_once __DIR__ . '/../config/config.php';

$revieweeId = (int)($_POST['reviewee_id'] ?? 0);
$back = url('creator-profile?id=' . $revieweeId);

if (!isLoggedIn()) {
    setFlash('error', 'レビューを書くにはログインしてください。');
    redirect(url('login?redirect=' . rawurlencode('creator-profile?id=' . $revieweeId)));
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

    $errors = [];
    if ($revieweeId <= 0) { $errors[] = 'レビュー対象が指定されていません'; }
    if ($rating < 1 || $rating > 5) { $errors[] = '評価は1〜5で選んでください'; }
    if ($comment === '') { $errors[] = 'コメントを入力してください'; }
    if (mb_strlen($comment) > 1000) { $errors[] = 'コメントは1,000文字以内で入力してください'; }
    if ($revieweeId === (int)$currentUser['id']) { $errors[] = '自分自身にはレビューできません'; }
    if (!empty($errors)) {
        setFlash('error', implode(' / ', $errors));
        redirect($back);
    }
    if (!rateLimitAllow('review_post', (string)$currentUser['id'], 10, 3600)) {
        setFlash('error', '短時間にレビューが続いています。少し時間をおいてから投稿してください。');
        redirect($back);
    }

    $reviewee = $db->selectOne("SELECT id, full_name, email FROM users WHERE id = ? AND is_active = 1", [$revieweeId]);
    if (!$reviewee) {
        setFlash('error', 'レビュー対象のユーザーが見つかりません');
        redirect(url('creators'));
    }

    // 取引（採用）の実績がある相手だけ
    $related = $db->selectOne("
        SELECT 1 FROM job_applications ja JOIN jobs j ON ja.job_id = j.id
        WHERE ja.status = 'accepted'
          AND ((j.client_id = ? AND ja.creator_id = ?) OR (j.client_id = ? AND ja.creator_id = ?))
        LIMIT 1
    ", [$currentUser['id'], $revieweeId, $revieweeId, $currentUser['id']]);
    if (!$related) {
        setFlash('error', 'レビューはAiNA Worksで取引（採用）した相手にだけ書けます。');
        redirect($back);
    }

    $existing = $db->selectOne(
        "SELECT id FROM reviews WHERE reviewer_id = ? AND reviewee_id = ? AND work_id IS NULL ORDER BY id DESC LIMIT 1",
        [$currentUser['id'], $revieweeId]
    );
    if ($existing) {
        $db->update("UPDATE reviews SET rating = ?, comment = ? WHERE id = ?", [$rating, $comment, $existing['id']]);
        setFlash('success', 'レビューを更新しました。');
        redirect($back);
    }

    $db->insert(
        "INSERT INTO reviews (reviewer_id, reviewee_id, work_id, rating, comment, created_at) VALUES (?, ?, NULL, ?, ?, NOW())",
        [$currentUser['id'], $revieweeId, $rating, $comment]
    );

    try {
        if (!empty($reviewee['email'])) {
            $subject = '【AiNA Works】あなたにレビューが投稿されました';
            $message  = ($currentUser['full_name'] ?? 'ユーザー') . "さんから、あなたのプロフィールにレビューが投稿されました。\n\n";
            $message .= "評価: " . str_repeat('★', $rating) . str_repeat('☆', 5 - $rating) . " ({$rating}/5)\n\n";
            $message .= "コメント:\n" . $comment;
            sendNotificationMail($reviewee['email'], $subject, $message, url('creator-profile?id=' . $revieweeId, true), 'レビューを確認する');
        }
    } catch (Throwable $e) {
        error_log('User review notify mail error: ' . $e->getMessage());
    }

    setFlash('success', 'レビューを投稿しました。ありがとうございます！');
    redirect($back);
} catch (Throwable $e) {
    error_log('User review error: ' . $e->getMessage());
    setFlash('error', 'レビューを保存できませんでした。時間をおいて再度お試しください。');
    redirect($back);
}
