<?php
require_once '../config/config.php';

// Always return clean JSON
if (!isLoggedIn()) {
    jsonResponse(['error' => 'ログインが必要です'], 401);
}

// CSRF token validation
$csrfToken = $_POST['csrf_token'] ?? '';
if (!verifyCsrfToken($csrfToken)) {
    jsonResponse(['error' => '不正なリクエストです'], 403);
}

// Inputs
$applicationId = (int)($_POST['application_id'] ?? 0);
$action = trim((string)($_POST['action'] ?? ''));
if ($applicationId <= 0 || !in_array($action, ['accept', 'reject'], true)) {
    jsonResponse(['error' => '入力が不正です'], 400);
}

try {
    $db = Database::getInstance();
    $currentUser = getCurrentUser();

    // Load application with owning job and creator
    $application = $db->selectOne(
        "SELECT ja.*, j.client_id, j.status AS job_status, j.title AS job_title, j.id AS job_id,
                u.id AS creator_id, u.full_name AS creator_name
         FROM job_applications ja
         JOIN jobs j ON ja.job_id = j.id
         JOIN users u ON ja.creator_id = u.id
         WHERE ja.id = ?",
        [$applicationId]
    );

    if (!$application) {
        jsonResponse(['error' => '応募が見つかりません'], 404);
    }

    // Authorization: only the job client can act
    if ((int)$application['client_id'] !== (int)$currentUser['id']) {
        jsonResponse(['error' => '権限がありません'], 403);
    }

    // Only pending applications can be acted upon
    if (($application['status'] ?? '') !== 'pending') {
        jsonResponse(['error' => 'この応募は処理済みです'], 400);
    }

    $db->beginTransaction();

    if ($action === 'accept') {
        // Guard: cannot accept if job is already completed
        if (in_array($application['job_status'], ['completed'], true)) {
            $db->rollback();
            jsonResponse(['error' => 'この案件は受諾できない状態です'], 400);
        }

        // Accept the application
        $db->update("UPDATE job_applications SET status = 'accepted' WHERE id = ?", [$applicationId]);

        // Optional job counters and status updates
        try {
            $hasAcceptedCount = $db->selectOne("SHOW COLUMNS FROM jobs LIKE 'accepted_count'");
            if ($hasAcceptedCount) {
                $db->update("UPDATE jobs SET accepted_count = IFNULL(accepted_count,0) + 1 WHERE id = ?", [$application['job_id']]);
            }

            $hasHiringLimit = $db->selectOne("SHOW COLUMNS FROM jobs LIKE 'hiring_limit'");
            $hasRecruiting = $db->selectOne("SHOW COLUMNS FROM jobs LIKE 'is_recruiting'");
            if ($hasHiringLimit && $hasRecruiting) {
                $row = $db->selectOne("SELECT IFNULL(hiring_limit,1) AS hiring_limit, IFNULL(accepted_count,0) AS accepted_count FROM jobs WHERE id = ?", [$application['job_id']]);
                if ($row && (int)$row['accepted_count'] >= (int)$row['hiring_limit']) {
                    $db->update("UPDATE jobs SET is_recruiting = 0 WHERE id = ?", [$application['job_id']]);
                }
            }

            if ($application['job_status'] === 'open') {
                $db->update("UPDATE jobs SET status = 'contracted' WHERE id = ?", [$application['job_id']]);
            }
        } catch (Exception $ignore) {
            // Non-critical counters; ignore if schema not present
        }

        // Ensure chat room exists
        $room = $db->selectOne(
            "SELECT id FROM chat_rooms WHERE (user1_id = ? AND user2_id = ?) OR (user1_id = ? AND user2_id = ?)",
            [$currentUser['id'], $application['creator_id'], $application['creator_id'], $currentUser['id']]
        );
        if (!$room) {
            $roomId = (int)$db->insert(
                "INSERT INTO chat_rooms (user1_id, user2_id, created_at) VALUES (?, ?, NOW())",
                [$currentUser['id'], $application['creator_id']]
            );
        } else {
            $roomId = (int)$room['id'];
        }

        // Initial chat message
        $initialMessage = "応募を受諾しました。ここからやり取りを開始しましょう。案件: " . ($application['job_title'] ?? '案件');
        $db->insert(
            "INSERT INTO chat_messages (room_id, sender_id, message, created_at) VALUES (?, ?, ?, NOW())",
            [$roomId, $currentUser['id'], $initialMessage]
        );

        $db->commit();

        // Notify creator via mail (best effort)
        try {
            $creatorEmail = $db->selectOne("SELECT email, full_name FROM users WHERE id = ?", [$application['creator_id']]);
            if ($creatorEmail) {
                $subject = "【AiNA Works】応募が受諾されました - " . ($application['job_title'] ?? '案件');
                $message = "おめでとうございます！\n\n";
                $message .= "あなたの応募が受諾されました。\n";
                $message .= "案件: " . ($application['job_title'] ?? '案件') . "\n";
                $message .= "クライアント: " . ($currentUser['full_name'] ?? '') . "\n\n";
                $message .= "チャットルームが作成されましたので、詳細な打ち合わせを開始してください。\n";
                $actionUrl = url('chat?user_id=' . $currentUser['id'], true);
                sendNotificationMail($creatorEmail['email'], $subject, $message, $actionUrl, 'チャットを開く');
            }
        } catch (Exception $notifyErr) {
            error_log('受諾通知メール送信エラー: ' . $notifyErr->getMessage());
        }

        jsonResponse([
            'success' => true,
            'message' => '応募を受諾しました',
            'new_status' => 'accepted',
            'chat_room_id' => $roomId,
            'redirect_to_chat' => url('chat?user_id=' . $application['creator_id'])
        ]);
    } else {
        // Reject
        $db->update("UPDATE job_applications SET status = 'rejected' WHERE id = ?", [$applicationId]);
        $db->commit();

        // Notify creator (best effort)
        try {
            $creatorEmail = $db->selectOne("SELECT email, full_name FROM users WHERE id = ?", [$application['creator_id']]);
            if ($creatorEmail) {
                $subject = "【AiNA Works】応募結果のお知らせ - " . ($application['job_title'] ?? '案件');
                $message = "ご応募いただいた案件の結果をお知らせします。\n";
                $message .= "案件: " . ($application['job_title'] ?? '案件') . "\n";
                $message .= "結果: 今回は見送りとさせていただきました。\n\n";
                $message .= "他にも多数の案件がございますので、ぜひご検討ください。";
                $actionUrl = url('jobs', true);
                sendNotificationMail($creatorEmail['email'], $subject, $message, $actionUrl, '案件を見る');
            }
        } catch (Exception $notifyErr) {
            error_log('却下通知メール送信エラー: ' . $notifyErr->getMessage());
        }

        jsonResponse([
            'success' => true,
            'message' => '応募を却下しました',
            'new_status' => 'rejected'
        ]);
    }
} catch (Exception $e) {
    if (isset($db)) {
        try { $db->rollback(); } catch (Exception $ignore) {}
    }
    error_log('Update application status error: ' . $e->getMessage());
    jsonResponse(['error' => 'システムエラーが発生しました'], 500);
}

