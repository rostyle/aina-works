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

    $db->beginTransaction();

    // 依存データの整理（存在すれば）
    try {
        $db->delete("DELETE FROM job_applications WHERE job_id = ?", [$jobId]);
    } catch (Exception $e) {
        // テーブルが無い等は無視（本体削除を優先）
        error_log('job_applications cleanup failed: ' . $e->getMessage());
    }

    try {
        // お気に入りに job が対象として存在する場合のクリーンアップ
        $db->delete("DELETE FROM favorites WHERE target_type = 'job' AND target_id = ?", [$jobId]);
    } catch (Exception $e) {
        error_log('favorites cleanup failed: ' . $e->getMessage());
    }

    // 本体削除
    $deleted = $db->delete("DELETE FROM jobs WHERE id = ?", [$jobId]);
    if ($deleted <= 0) {
        throw new Exception('Job delete returned 0 rows');
    }

    $db->commit();

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


