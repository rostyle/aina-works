<?php
require_once '../config/config.php';

header('Content-Type: application/json');

try {
    if (!isLoggedIn()) {
        jsonResponse(['error' => 'ログインが必要です'], 401);
    }

    // POSTデータ取得（application/x-www-form-urlencoded 前提）
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrfToken)) {
        jsonResponse(['error' => '不正なリクエストです'], 403);
    }

    $jobId = (int)($_POST['job_id'] ?? 0);
    if ($jobId <= 0) {
        jsonResponse(['error' => '案件が指定されていません'], 400);
    }

    $db = Database::getInstance();
    $currentUser = getCurrentUser();

    // 案件取得 & 権限チェック
    $job = $db->selectOne("SELECT id, client_id, status FROM jobs WHERE id = ?", [$jobId]);
    if (!$job) {
        jsonResponse(['error' => '案件が見つかりません'], 404);
    }
    if ((int)$job['client_id'] !== (int)$currentUser['id']) {
        jsonResponse(['error' => '権限がありません'], 403);
    }

    // 編集画面ポリシーに合わせ、募集中のみ削除可
    if (($job['status'] ?? '') !== 'open') {
        jsonResponse(['error' => '募集中の案件のみ削除できます'], 422);
    }

    // 採用済みの応募がある案件は消さない（取引の記録を残すため「キャンセル」を使う）
    if (jobAcceptedCount($jobId) > 0) {
        jsonResponse(['error' => '採用済みの応募がある案件は削除できません。案件ページから「案件をキャンセルする」を選んでください'], 422);
    }

    // 応募者へ知らせるため、削除前に取得しておく
    $title = (string)($db->selectOne("SELECT title FROM jobs WHERE id = ?", [$jobId])['title'] ?? '案件');
    $applicants = $db->select("
        SELECT u.email FROM job_applications ja JOIN users u ON ja.creator_id = u.id
        WHERE ja.job_id = ? AND ja.status = 'pending' AND u.is_active = 1
    ", [$jobId]);

    $db->beginTransaction();
    $db->delete("DELETE FROM job_applications WHERE job_id = ?", [$jobId]);

    // 本体削除
    $deleted = $db->delete("DELETE FROM jobs WHERE id = ?", [$jobId]);
    if ($deleted <= 0) {
        throw new Exception('Job delete returned 0 rows');
    }

    $db->commit();

    foreach ($applicants as $a) {
        try {
            sendNotificationMail(
                $a['email'],
                "【AiNA Works】応募した案件が取り下げられました - {$title}",
                "ご応募いただいた案件「{$title}」は、依頼者により取り下げられました。\n\nほかの募集中の案件もぜひご覧ください。",
                url('jobs', true),
                '募集中の案件を見る'
            );
        } catch (Throwable $e) {
            error_log('delete-job notify: ' . $e->getMessage());
        }
    }

    jsonResponse([
        'success' => true,
        'message' => '案件を削除しました'
    ]);
} catch (Exception $e) {
    if (isset($db)) {
        try { $db->rollback(); } catch (Exception $ignore) {}
    }
    error_log('Delete job error: ' . $e->getMessage());
    jsonResponse(['error' => '削除に失敗しました'], 500);
}


