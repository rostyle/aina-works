<?php
/**
 * 作品ページからクリエイターへ直接依頼する
 * 依頼内容はクリエイターとのチャットに届き（返信もそこで行う）、メールでも知らせる。
 */
require_once __DIR__ . '/../config/config.php';

if (!isLoggedIn()) {
    jsonResponse(['error' => 'ログインが必要です'], 401);
}
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    jsonResponse(['error' => 'POSTリクエストのみ受け付けます'], 405);
}
if (!verifyCsrfToken(csrfTokenFromRequest())) {
    jsonResponse(['error' => '画面の有効期限が切れました。ページを再読み込みしてからもう一度お試しください。'], 403);
}

try {
    $db = Database::getInstance();
    $currentUser = getCurrentUser();

    $creatorId = (int)($_POST['creator_id'] ?? 0);
    $workId = (int)($_POST['work_id'] ?? 0);
    $projectTitle = trim((string)($_POST['project_title'] ?? ''));
    $projectDescription = trim((string)($_POST['project_description'] ?? ''));
    $budgetMin = (int)($_POST['budget_min'] ?? 0);
    $budgetMax = (int)($_POST['budget_max'] ?? 0);
    $deadline = trim((string)($_POST['deadline'] ?? ''));
    $requirements = trim((string)($_POST['requirements'] ?? ''));

    if ($creatorId <= 0) {
        jsonResponse(['error' => 'クリエイターが指定されていません'], 400);
    }
    if ($projectTitle === '' || mb_strlen($projectTitle) > 100) {
        jsonResponse(['error' => '依頼のタイトルを100文字以内で入力してください'], 400);
    }
    if ($projectDescription === '' || mb_strlen($projectDescription) > 3000) {
        jsonResponse(['error' => '依頼の概要を3,000文字以内で入力してください'], 400);
    }
    if (mb_strlen($requirements) > 2000) {
        jsonResponse(['error' => '要件は2,000文字以内で入力してください'], 400);
    }
    if ($budgetMin < 100 || $budgetMax < 100) {
        jsonResponse(['error' => '予算は100円以上で入力してください'], 400);
    }
    if ($budgetMin > $budgetMax) {
        jsonResponse(['error' => '予算の上限は下限以上にしてください'], 400);
    }
    $d = DateTime::createFromFormat('Y-m-d', $deadline);
    if (!$d || $d->format('Y-m-d') !== $deadline) {
        jsonResponse(['error' => '希望納期を正しく入力してください'], 400);
    }
    if ($deadline < date('Y-m-d')) {
        jsonResponse(['error' => '希望納期は今日以降の日付にしてください'], 400);
    }
    if ((int)$currentUser['id'] === $creatorId) {
        jsonResponse(['error' => '自分自身には依頼できません'], 400);
    }
    if (!rateLimitAllow('job_request', (string)$currentUser['id'], 10, 3600)) {
        jsonResponse(['error' => '短時間に依頼が続いています。少し時間をおいてから送ってください。'], 429);
    }

    $creator = $db->selectOne("SELECT id, full_name, email FROM users WHERE id = ? AND is_active = 1", [$creatorId]);
    if (!$creator) {
        jsonResponse(['error' => 'クリエイターが見つかりません'], 404);
    }
    $work = null;
    if ($workId > 0) {
        $work = $db->selectOne("SELECT id, title FROM works WHERE id = ? AND user_id = ? AND status = 'published'", [$workId, $creatorId]);
        if (!$work) {
            $workId = 0; // 作品が見つからない・別人の作品なら紐づけない
        }
    }

    // 記録（テーブルが無い環境でも依頼自体は届ける）
    try {
        $db->insert("
            INSERT INTO job_requests (
                client_id, creator_id, work_id, project_title, project_description,
                budget_min, budget_max, deadline, requirements, status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
        ", [
            $currentUser['id'], $creatorId, $workId ?: null, $projectTitle, $projectDescription,
            $budgetMin, $budgetMax, $deadline, $requirements
        ]);
    } catch (Throwable $e) {
        error_log('request-job: job_requests insert skipped: ' . $e->getMessage());
    }

    // クリエイターとのチャットに依頼内容を届ける
    $chat  = "【お仕事のご依頼】\n";
    $chat .= "タイトル: {$projectTitle}\n";
    if ($work) {
        $chat .= "参考にした作品: {$work['title']}\n";
    }
    $chat .= "予算: " . formatPrice($budgetMin) . " 〜 " . formatPrice($budgetMax) . "\n";
    $chat .= "希望納期: " . $d->format('Y年n月j日') . "\n\n";
    $chat .= "概要:\n{$projectDescription}\n";
    if ($requirements !== '') {
        $chat .= "\n要件:\n{$requirements}\n";
    }
    $chat .= "\nこのチャットで条件を相談してください。";
    $roomId = postChatNotice((int)$currentUser['id'], $creatorId, $chat);
    if (!$roomId) {
        jsonResponse(['error' => '依頼を送れませんでした。時間をおいて再度お試しください。'], 500);
    }

    try {
        $subject = "【AiNA Works】お仕事の依頼が届きました - {$projectTitle}";
        $mail  = "{$currentUser['full_name']}さんからお仕事の依頼が届きました。\n\n";
        $mail .= "タイトル: {$projectTitle}\n";
        $mail .= "予算: " . formatPrice($budgetMin) . " 〜 " . formatPrice($budgetMax) . "\n";
        $mail .= "希望納期: " . $d->format('Y年n月j日') . "\n\n";
        $mail .= "チャットで内容を確認し、返信してください。";
        sendNotificationMail($creator['email'], $subject, $mail, url('chat?room_id=' . $roomId, true), 'チャットで返信する');
    } catch (Throwable $e) {
        error_log('request-job mail: ' . $e->getMessage());
    }

    jsonResponse([
        'success' => true,
        'message' => '依頼を送りました。続きは' . $creator['full_name'] . 'さんとのチャットでやり取りできます。',
        'chat_url' => url('chat?room_id=' . $roomId),
    ]);
} catch (Throwable $e) {
    error_log("Job request error: " . $e->getMessage());
    jsonResponse(['error' => 'システムエラーが発生しました。時間をおいて再度お試しください。'], 500);
}
