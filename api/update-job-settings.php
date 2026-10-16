<?php
/**
 * 案件の設定・状態を変える API
 *  - 依頼者: 募集人数、募集の終了/再開、状態の変更（決められた順番のみ）
 *  - 採用されたクリエイター: 「納品を報告」（status=delivered）だけ
 */
ob_start();
require_once __DIR__ . '/../config/config.php';
ini_set('display_errors', '0');

if (!isLoggedIn()) {
    jsonResponse(['error' => 'ログインが必要です'], 401);
}
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    jsonResponse(['error' => 'POSTリクエストのみ受け付けます'], 405);
}
if (!verifyCsrfToken(csrfTokenFromRequest())) {
    jsonResponse(['error' => '画面の有効期限が切れました。ページを再読み込みしてからもう一度お試しください。'], 403);
}

$jobId = (int)($_POST['job_id'] ?? 0);
$recruitAction = $_POST['action'] ?? null; // 'open' | 'close'（旧UI互換）
$newStatus = isset($_POST['status']) && is_string($_POST['status']) && $_POST['status'] !== '' ? $_POST['status'] : null;
$hiringLimit = isset($_POST['hiring_limit']) && $_POST['hiring_limit'] !== '' ? (int)$_POST['hiring_limit'] : null;
$note = mb_substr(trim((string)($_POST['note'] ?? '')), 0, 1000);

if ($jobId <= 0) {
    jsonResponse(['error' => '案件が指定されていません'], 400);
}
if ($newStatus !== null && !in_array($newStatus, JOB_STATUSES, true)) {
    jsonResponse(['error' => '不正なステータスです'], 400);
}

// 列の用意はトランザクションの外で一度だけ
ensureJobWorkflowSchema();

try {
    $db = Database::getInstance();
    $currentUser = getCurrentUser();
    if (!$currentUser) {
        jsonResponse(['error' => 'ログインが必要です'], 401);
    }

    $job = $db->selectOne("SELECT * FROM jobs WHERE id = ?", [$jobId]);
    if (!$job) {
        jsonResponse(['error' => '案件が見つかりません'], 404);
    }

    $isClient = (int)$job['client_id'] === (int)$currentUser['id'];
    $isAcceptedCreator = !$isClient && (bool)$db->selectOne(
        "SELECT 1 FROM job_applications WHERE job_id = ? AND creator_id = ? AND status = 'accepted' LIMIT 1",
        [$jobId, $currentUser['id']]
    );
    if (!$isClient && !$isAcceptedCreator) {
        jsonResponse(['error' => 'この案件を操作する権限がありません'], 403);
    }
    // クリエイターは「納品を報告」以外できない
    if ($isAcceptedCreator && ($newStatus !== 'delivered' || $hiringLimit !== null || $recruitAction !== null)) {
        jsonResponse(['error' => 'この操作は依頼者のみ行えます'], 403);
    }

    $previousStatus = (string)($job['status'] ?? '');
    $hasHiringLimit = array_key_exists('hiring_limit', $job);
    $hasRecruiting = array_key_exists('is_recruiting', $job);

    $db->beginTransaction();
    // 同時操作で状態が食い違わないよう行をロックして読み直す
    $job = $db->selectOne("SELECT * FROM jobs WHERE id = ? FOR UPDATE", [$jobId]);
    $previousStatus = (string)($job['status'] ?? '');
    $acceptedCount = jobAcceptedCount($jobId);
    $updates = 0;

    // 募集人数（採用済み人数より少なくはできない）
    if ($hiringLimit !== null && $isClient) {
        $hiringLimit = max(1, min(100, $hiringLimit));
        if ($hiringLimit < $acceptedCount) {
            $db->rollback();
            jsonResponse(['error' => "すでに{$acceptedCount}人を採用しているため、募集人数を{$acceptedCount}人より少なくできません"], 400);
        }
        if ($hasHiringLimit && (int)($job['hiring_limit'] ?? 1) !== $hiringLimit) {
            $updates += $db->update("UPDATE jobs SET hiring_limit = ? WHERE id = ?", [$hiringLimit, $jobId]);
        }
    }

    // 募集の終了/再開（旧UI互換）→ 状態変更に読み替える
    if ($newStatus === null && $isClient && ($recruitAction === 'open' || $recruitAction === 'close')) {
        $newStatus = $recruitAction === 'open' ? 'open' : 'closed';
        if ($newStatus === $previousStatus) {
            $newStatus = null;
        }
    }

    if ($newStatus !== null && $newStatus !== $previousStatus) {
        $allowed = jobNextStatuses($previousStatus, $isClient ? 'client' : 'creator', $acceptedCount);
        if (!in_array($newStatus, $allowed, true)) {
            $db->rollback();
            $msg = '「' . jobStatusLabel($previousStatus) . '」から「' . jobStatusLabel($newStatus) . '」には変更できません';
            if ($newStatus === 'delivered' && $isClient) {
                $msg = '「納品済み」はクリエイターが納品を報告したときに切り替わります';
            }
            jsonResponse(['error' => $msg], 400);
        }
        if (!jobStatusWritable($newStatus)) {
            $db->rollback();
            error_log('update-job-settings: jobs.status cannot store ' . $newStatus);
            jsonResponse(['error' => 'いまはこの状態に変更できません。時間をおいて再度お試しください。'], 503);
        }
        // 再募集は募集人数に空きがあるときだけ
        if ($newStatus === 'open' && $acceptedCount >= (int)($hiringLimit ?? $job['hiring_limit'] ?? 1)) {
            $db->rollback();
            jsonResponse(['error' => '募集人数に達しているため再募集できません。先に募集人数を増やしてください'], 400);
        }
        $updates += $db->update("UPDATE jobs SET status = ? WHERE id = ?", [$newStatus, $jobId]);
        if ($hasRecruiting) {
            $db->update("UPDATE jobs SET is_recruiting = ? WHERE id = ?", [$newStatus === 'open' ? 1 : 0, $jobId]);
        }
    }

    $db->commit();

    // 状態が変わったら相手に知らせる（チャット＋メール。失敗しても本処理は成功）
    if ($newStatus !== null && $newStatus !== $previousStatus) {
        notifyJobStatusChange($job, $previousStatus, $newStatus, $currentUser, $isClient, $note);
    }

    $job = $db->selectOne("SELECT * FROM jobs WHERE id = ?", [$jobId]);
    $acceptedCount = jobAcceptedCount($jobId);
    $message = ($newStatus !== null && $newStatus !== $previousStatus)
        ? '「' . jobStatusLabel($job['status']) . '」に変更しました'
        : ($updates > 0 ? '設定を更新しました' : '変更はありません');
    if ($hiringLimit !== null && $newStatus === null) {
        $message = '募集人数を更新しました';
    }

    jsonResponse([
        'success' => true,
        'message' => $message,
        'job' => [
            'status' => $job['status'],
            'status_label' => jobStatusLabel($job['status']),
            'is_recruiting' => $hasRecruiting ? (int)($job['is_recruiting'] ?? 1) : null,
            'hiring_limit' => $hasHiringLimit ? (int)($job['hiring_limit'] ?? 1) : null,
            'accepted_count' => $acceptedCount,
        ],
    ]);
} catch (Throwable $e) {
    if (isset($db)) {
        try { $db->rollback(); } catch (Throwable $ignore) {}
    }
    error_log('Update job settings error: ' . $e->getMessage());
    jsonResponse(['error' => 'システムエラーが発生しました。時間をおいて再度お試しください。'], 500);
}

/**
 * 状態変更の通知
 */
function notifyJobStatusChange(array $job, string $from, string $to, array $actor, bool $actorIsClient, string $note): void {
    try {
        $db = Database::getInstance();
        $jobId = (int)$job['id'];
        $title = (string)($job['title'] ?? '案件');
        $jobUrl = url('job-detail?id=' . $jobId, true);

        if ($actorIsClient) {
            $recipients = array_map('intval', array_column($db->select(
                "SELECT DISTINCT creator_id FROM job_applications WHERE job_id = ? AND status = 'accepted'",
                [$jobId]
            ), 'creator_id'));
        } else {
            $recipients = [(int)$job['client_id']];
        }
        if (empty($recipients)) {
            return;
        }

        if ($to === 'delivered') {
            $chat = "【納品のご報告】\n案件: {$title}\n{$actor['full_name']}さんが納品を報告しました。\n内容を確認し、問題なければ案件ページの「検収して完了にする」を押してください。修正が必要な場合は「修正を依頼する」を押してください。";
            $subject = "【AiNA Works】納品の報告が届きました - {$title}";
            $mail = "{$actor['full_name']}さんから納品の報告が届きました。\n\n案件: {$title}\n\n内容を確認し、問題なければ案件ページで「検収して完了にする」を押してください。";
            $action = '案件を確認する';
        } elseif ($to === 'contracted' && $from === 'delivered') {
            $chat = "【修正のご依頼】\n案件: {$title}\n依頼者から修正の依頼がありました。チャットで内容を確認し、対応後にもう一度「納品を報告する」を押してください。";
            $subject = "【AiNA Works】修正の依頼が届きました - {$title}";
            $mail = "依頼者から修正の依頼がありました。\n\n案件: {$title}\n\nチャットで内容を確認し、対応後にもう一度「納品を報告する」を押してください。";
            $action = '案件を確認する';
        } elseif ($to === 'completed') {
            $chat = "【検収完了】\n案件: {$title} は完了しました。お疲れさまでした。\n\nお互いのプロフィールからレビューをお願いします。レビューは次のお仕事につながります。\n依頼者プロフィール: " . url('creator-profile?id=' . (int)$job['client_id'], true);
            $subject = "【AiNA Works】案件が完了しました - {$title}";
            $mail = "依頼者が検収を完了し、案件が完了しました。\n\n案件: {$title}\n\n報酬の受け取りは、依頼者からのお振込をご確認ください。よろしければ依頼者へのレビューもお願いします。";
            $action = '案件を見る';
        } else {
            $chat = "【案件ステータス変更のお知らせ】\n案件: {$title}\nステータス: " . jobStatusLabel($from) . ' → ' . jobStatusLabel($to);
            $subject = "【AiNA Works】案件のステータスが変わりました - {$title}";
            $mail = "案件のステータスが「" . jobStatusLabel($from) . "」から「" . jobStatusLabel($to) . "」に変わりました。\n\n案件: {$title}";
            $action = '案件を見る';
        }
        if ($note !== '') {
            $chat .= "\n\nメッセージ:\n" . $note;
            $mail .= "\n\nメッセージ:\n" . $note;
        }

        foreach ($recipients as $rid) {
            if ($rid <= 0 || $rid === (int)$actor['id']) {
                continue;
            }
            postChatNotice((int)$actor['id'], $rid, $chat);
            $u = $db->selectOne("SELECT email FROM users WHERE id = ? AND is_active = 1", [$rid]);
            if ($u && !empty($u['email'])) {
                sendNotificationMail($u['email'], $subject, $mail, $jobUrl, $action);
            }
        }
    } catch (Throwable $e) {
        error_log('notifyJobStatusChange: ' . $e->getMessage());
    }
}
