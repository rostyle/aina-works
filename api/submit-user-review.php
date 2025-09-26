<?php
require_once '../config/config.php';

// Always respond with redirects for form posts; use JSON only for fatal errors
if (!isLoggedIn()) {
    jsonResponse(['error' => 'ログインが必要です'], 401);
}

// CSRF
$csrfToken = $_POST['csrf_token'] ?? '';
if (!verifyCsrfToken($csrfToken)) {
    jsonResponse(['error' => '不正なリクエストです'], 403);
}

try {
    $db = Database::getInstance();
    $currentUser = getCurrentUser();

    $revieweeId = (int)($_POST['reviewee_id'] ?? 0);
    $rating = (int)($_POST['rating'] ?? 0);
    $comment = trim((string)($_POST['comment'] ?? ''));

    // Basic validation
    $errors = [];
    if ($revieweeId <= 0) { $errors[] = 'レビュー対象が指定されていません'; }
    if ($rating < 1 || $rating > 5) { $errors[] = '評価は1〜5の範囲で入力してください'; }
    if ($comment === '') { $errors[] = 'コメントを入力してください'; }
    if (mb_strlen($comment) > 1000) { $errors[] = 'コメントは1000文字以内で入力してください'; }
    if ($revieweeId === (int)$currentUser['id']) { $errors[] = '自分自身にはレビューできません'; }

    if (!empty($errors)) {
        $_SESSION['flash'] = $_SESSION['flash'] ?? [];
        $_SESSION['flash']['error'] = implode(' / ', $errors);
        redirect('../creator-profile.php?id=' . $revieweeId);
    }

    // Ensure reviewee exists and is active
    $reviewee = $db->selectOne("SELECT id, full_name, email FROM users WHERE id = ? AND is_active = 1", [$revieweeId]);
    if (!$reviewee) {
        $_SESSION['flash'] = $_SESSION['flash'] ?? [];
        $_SESSION['flash']['error'] = 'レビュー対象のユーザーが見つかりません';
        redirect('../creators.php');
    }

    // Optional: relationship check (at least one related job with relationship)
    // Not enforced strictly to keep UX simple

    // Insert review (work_id is NULL for user-to-user reviews)
    $db->insert(
        "INSERT INTO reviews (reviewer_id, reviewee_id, work_id, rating, comment, created_at) VALUES (?, ?, NULL, ?, ?, NOW())",
        [$currentUser['id'], $revieweeId, $rating, $comment]
    );

    // Notify reviewee via email (best effort)
    try {
        if (!empty($reviewee['email'])) {
            $subject = '【AiNA Works】あなたにレビューが投稿されました';
            $message  = ($currentUser['full_name'] ?? 'ユーザー') . " さんから、あなたのプロフィールにレビューが投稿されました。\n\n";
            $message .= "評価: " . str_repeat('★', max(1, min(5, $rating))) . " (" . $rating . "/5)\n\n";
            $message .= "コメント:\n" . $comment . "\n\n";
            $message .= "プロフィールでレビューを確認できます。";
            $actionUrl = url('creator-profile?id=' . $revieweeId, true);
            sendNotificationMail($reviewee['email'], $subject, $message, $actionUrl, 'レビューを確認する');
        }
    } catch (Exception $e) {
        error_log('User review notify mail error: ' . $e->getMessage());
    }

    $_SESSION['flash'] = $_SESSION['flash'] ?? [];
    $_SESSION['flash']['success'] = 'レビューを投稿しました。ありがとうございます！';
    redirect('../creator-profile.php?id=' . $revieweeId);
} catch (Exception $e) {
    error_log('Submit user review error: ' . $e->getMessage());
    jsonResponse(['error' => 'システムエラーが発生しました'], 500);
}


