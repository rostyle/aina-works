<?php
require_once '../config/config.php';

header('Content-Type: application/json');

// ログイン確認
if (!isLoggedIn()) {
    jsonResponse(['error' => 'ログインが必要です'], 401);
}

// CSRF
$csrfToken = $_POST['csrf_token'] ?? '';
if (!verifyCsrfToken($csrfToken)) {
    jsonResponse(['error' => '不正なリクエストです'], 403);
}

$jobId = (int)($_POST['job_id'] ?? 0);
$recruitAction = $_POST['action'] ?? null; // 'open' | 'close' | null (互換用)
$newStatus = $_POST['status'] ?? null; // 'open' | 'closed' | 'contracted' | 'delivered' | 'cancelled'
$hiringLimit = isset($_POST['hiring_limit']) ? (int)$_POST['hiring_limit'] : null;

// デバッグログ
error_log('Job settings update - jobId: ' . $jobId . ', newStatus: ' . $newStatus . ', hiringLimit: ' . $hiringLimit);

if (!$jobId) {
    jsonResponse(['error' => '案件が指定されていません'], 400);
}

try {
    $db = Database::getInstance();
    $currentUser = getCurrentUser();

    // 案件取得 & 権限チェック
    $job = $db->selectOne("SELECT * FROM jobs WHERE id = ?", [$jobId]);
    if (!$job) {
        jsonResponse(['error' => '案件が見つかりません'], 404);
    }
    if ((int)$job['client_id'] !== (int)$currentUser['id']) {
        jsonResponse(['error' => '権限がありません'], 403);
    }

    $db->beginTransaction();

    // カラム存在チェック + ソフトマイグレーション
    $hasRecruiting = $db->selectOne("SHOW COLUMNS FROM jobs LIKE 'is_recruiting'");
    $hasHiringLimit = $db->selectOne("SHOW COLUMNS FROM jobs LIKE 'hiring_limit'");
    $hasAcceptedCount = $db->selectOne("SHOW COLUMNS FROM jobs LIKE 'accepted_count'");

    try {
        if (!$hasHiringLimit) {
            // 募集人数
            $db->update("ALTER TABLE jobs ADD COLUMN hiring_limit INT NOT NULL DEFAULT 1");
            $hasHiringLimit = true;
        }
    } catch (Exception $e) {
        // 権限等で失敗しても続行
    }
    try {
        if (!$hasRecruiting) {
            // 募集状態（ON/OFF）
            $db->update("ALTER TABLE jobs ADD COLUMN is_recruiting TINYINT(1) NOT NULL DEFAULT 1");
            $hasRecruiting = true;
        }
    } catch (Exception $e) {
        // 続行
    }

    $updates = 0;

    // 募集人数の更新
    if ($hiringLimit !== null) {
        if ($hiringLimit <= 0) {
            $hiringLimit = 1;
        }
        if ($hasHiringLimit) {
            $currentLimit = isset($job['hiring_limit']) ? (int)$job['hiring_limit'] : 1;
            error_log('Current hiring_limit: ' . $currentLimit . ', New hiring_limit: ' . $hiringLimit);
            
            if ($currentLimit !== $hiringLimit) {
                $updates += $db->update("UPDATE jobs SET hiring_limit = ? WHERE id = ?", [$hiringLimit, $jobId]);
                error_log('Hiring limit updated from ' . $currentLimit . ' to ' . $hiringLimit);
            } else {
                error_log('Hiring limit unchanged: ' . $hiringLimit);
            }
        } else {
            error_log('hiring_limit column does not exist');
        }
    }

    // 募集の終了/再開（互換）
    if ($recruitAction === 'open' || $recruitAction === 'close') {
        if ($hasRecruiting) {
            $updates += $db->update(
                "UPDATE jobs SET is_recruiting = ? WHERE id = ?",
                [$recruitAction === 'open' ? 1 : 0, $jobId]
            );
        } else {
            // フォールバック：ステータスで表現（close -> closed, open -> open）
            if ($recruitAction === 'open') {
                if (!in_array($job['status'], ['completed', 'cancelled'], true)) {
                    $updates += $db->update("UPDATE jobs SET status = 'open' WHERE id = ?", [$jobId]);
                }
            } else {
                $updates += $db->update("UPDATE jobs SET status = 'closed' WHERE id = ?", [$jobId]);
            }
        }
    }

    // ステータス直接更新（新UI）
    if ($newStatus !== null) {
        $allowed = ['open','closed','contracted','delivered','cancelled'];
        if (!in_array($newStatus, $allowed, true)) {
            throw new Exception('不正なステータス');
        }
        
        error_log('Current status: ' . $job['status'] . ', New status: ' . $newStatus);
        
        // 現在と異なる場合のみ更新
        if ($job['status'] !== $newStatus) {
            $updates += $db->update("UPDATE jobs SET status = ? WHERE id = ?", [$newStatus, $jobId]);
            error_log('Status updated from ' . $job['status'] . ' to ' . $newStatus);
        } else {
            error_log('Status unchanged: ' . $newStatus);
        }

        // ステータスと募集状態の整合性（カラムがある場合のみ）
        if ($hasRecruiting) {
            if ($newStatus === 'open') {
                $db->update("UPDATE jobs SET is_recruiting = 1 WHERE id = ?", [$jobId]);
            }
            if (in_array($newStatus, ['closed','contracted','delivered','cancelled'], true)) {
                $db->update("UPDATE jobs SET is_recruiting = 0 WHERE id = ?", [$jobId]);
            }
        }
    }

    // 受諾数の再計算（カラムがあれば同期）
    if ($hasAcceptedCount) {
        $countRow = $db->selectOne("SELECT COUNT(*) as cnt FROM job_applications WHERE job_id = ? AND status = 'accepted'", [$jobId]);
        $db->update("UPDATE jobs SET accepted_count = ? WHERE id = ?", [(int)($countRow['cnt'] ?? 0), $jobId]);
    }

    $db->commit();

    // 最新状態を返却
    $job = $db->selectOne("SELECT * FROM jobs WHERE id = ?", [$jobId]);
    $acceptedRow = $db->selectOne("SELECT COUNT(*) as cnt FROM job_applications WHERE job_id = ? AND status = 'accepted'", [$jobId]);
    $acceptedCount = (int)($acceptedRow['cnt'] ?? 0);
    $limit = $hasHiringLimit ? (int)($job['hiring_limit'] ?? 1) : null;
    $isRecruiting = $hasRecruiting ? (int)($job['is_recruiting'] ?? 1) : null;

    $message = $updates > 0 ? '設定を更新しました' : '変更はありません';
    if ($newStatus !== null) {
        // フロントの意図がステータス変更の場合は、文言を明確化
        $message = ($job['status'] === $newStatus) ? 'ステータスを更新しました' : $message;
    }
    if ($hiringLimit !== null && $limit !== null) {
        if ($limit === (int)$hiringLimit) {
            // 掲載人数が反映された場合
            $message = '募集人数を更新しました';
        }
    }

    jsonResponse([
        'success' => true,
        'message' => $message,
        'job' => [
            'status' => $job['status'],
            'is_recruiting' => $isRecruiting,
            'hiring_limit' => $limit,
            'accepted_count' => $acceptedCount
        ]
    ]);
} catch (Exception $e) {
    if (isset($db)) {
        try { $db->rollback(); } catch (Exception $ignore) {}
    }
    error_log('Update job settings error: ' . $e->getMessage());
    jsonResponse(['error' => 'システムエラーが発生しました'], 500);
}
?>



