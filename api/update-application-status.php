<?php
// エラー出力を抑制（JSONレスポンスを汚染しないように）
ini_set('display_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT & ~E_WARNING);

// 出力バッファをクリアしてクリーンなJSONレスポンスを確保
while (ob_get_level() > 0) {
    ob_end_clean();
}

require_once '../config/config.php';

// 再度バッファをクリア（config.phpでバッファが開始される可能性があるため）
while (ob_get_level() > 0) {
    ob_end_clean();
}

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
if ($applicationId <= 0 || !in_array($action, ['accept', 'reject', 'withdraw'], true)) {
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

    // Authorization check based on action
    if ($action === 'withdraw') {
        // Only the creator can withdraw their own application
        if ((int)$application['creator_id'] !== (int)$currentUser['id']) {
            jsonResponse(['error' => '権限がありません'], 403);
        }
    } else {
        // Only the job client can accept/reject
        if ((int)$application['client_id'] !== (int)$currentUser['id']) {
            jsonResponse(['error' => '権限がありません'], 403);
        }
    }

    // Only pending applications can be acted upon (except withdrawn, which creator can do even if pending)
    if ($action !== 'withdraw' && ($application['status'] ?? '') !== 'pending') {
        jsonResponse(['error' => 'この応募は処理済みです'], 400);
    }
    
    // Withdraw can only be done on pending applications
    if ($action === 'withdraw' && ($application['status'] ?? '') !== 'pending') {
        jsonResponse(['error' => '保留中の応募のみ撤回できます'], 400);
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

            // If is_recruiting becomes 0 (limit reached), change status to 'contracted'
            if ($hasHiringLimit && $hasRecruiting) {
                $row = $db->selectOne("SELECT IFNULL(hiring_limit,1) AS hiring_limit, IFNULL(accepted_count,0) AS accepted_count FROM jobs WHERE id = ?", [$application['job_id']]);
                if ($row && (int)$row['accepted_count'] >= (int)$row['hiring_limit']) {
                    $db->update("UPDATE jobs SET is_recruiting = 0 WHERE id = ?", [$application['job_id']]);
                    
                    // Also change status to 'contracted' only when limit is reached
                    if ($application['job_status'] === 'open') {
                        $db->update("UPDATE jobs SET status = 'contracted' WHERE id = ?", [$application['job_id']]);
                    }
                }
            }
        } catch (Exception $ignore) {
            // Non-critical counters; ignore if schema not present
        }

        // Ensure chat room exists
        error_log("チャットルーム確認開始 - currentUser: {$currentUser['id']}, creator: {$application['creator_id']}");
        $room = $db->selectOne(
            "SELECT id FROM chat_rooms WHERE (user1_id = ? AND user2_id = ?) OR (user1_id = ? AND user2_id = ?)",
            [$currentUser['id'], $application['creator_id'], $application['creator_id'], $currentUser['id']]
        );
        if (!$room) {
            error_log("新しいチャットルームを作成");
            $roomId = (int)$db->insert(
                "INSERT INTO chat_rooms (user1_id, user2_id, created_at) VALUES (?, ?, NOW())",
                [$currentUser['id'], $application['creator_id']]
            );
            error_log("チャットルーム作成完了 - roomId: {$roomId}");
        } else {
            $roomId = (int)$room['id'];
            error_log("既存のチャットルームを使用 - roomId: {$roomId}");
        }

        // Initial chat message with application details
        error_log("初期メッセージ作成開始");
        $initialMessage = "応募を受諾しました。ここからやり取りを開始しましょう。\n\n";
        $initialMessage .= "【案件】" . ($application['job_title'] ?? '案件') . "\n\n";
        $initialMessage .= "【応募内容】\n";
        $initialMessage .= "・提案金額: ¥" . number_format((int)($application['proposed_price'] ?? 0)) . "\n";
        $initialMessage .= "・提案期間: " . (int)($application['proposed_duration'] ?? 0) . "週間\n";
        if (!empty($application['cover_letter'])) {
            $initialMessage .= "・応募メッセージ:\n" . ($application['cover_letter'] ?? '') . "\n";
        }
        $initialMessage .= "\nよろしくお願いいたします。";
        
        error_log("初期メッセージ内容: " . substr($initialMessage, 0, 200) . "...");
        
        $messageId = $db->insert(
            "INSERT INTO chat_messages (room_id, sender_id, message, created_at) VALUES (?, ?, ?, NOW())",
            [$roomId, $currentUser['id'], $initialMessage]
        );
        
        if (!$messageId) {
            error_log("チャットメッセージ挿入失敗");
            throw new Exception("チャットメッセージの作成に失敗しました");
        }
        
        error_log("チャットメッセージ挿入完了 - messageId: {$messageId}");

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
    } else if ($action === 'reject') {
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
    } else if ($action === 'withdraw') {
        // Withdraw application (by creator)
        $db->update("UPDATE job_applications SET status = 'withdrawn' WHERE id = ?", [$applicationId]);
        $db->commit();

        // Notify client (best effort)
        try {
            $clientEmail = $db->selectOne("SELECT email, full_name FROM users WHERE id = ?", [$application['client_id']]);
            if ($clientEmail) {
                $subject = "【AiNA Works】応募が撤回されました - " . ($application['job_title'] ?? '案件');
                $message = "応募が撤回されました。\n";
                $message .= "案件: " . ($application['job_title'] ?? '案件') . "\n";
                $message .= "応募者: " . ($application['creator_name'] ?? '') . "\n\n";
                $message .= "他の応募者もご確認ください。";
                $actionUrl = url('job-applications?id=' . $application['job_id'], true);
                sendNotificationMail($clientEmail['email'], $subject, $message, $actionUrl, '応募管理を見る');
            }
        } catch (Exception $notifyErr) {
            error_log('撤回通知メール送信エラー: ' . $notifyErr->getMessage());
        }

        jsonResponse([
            'success' => true,
            'message' => '応募を撤回しました',
            'new_status' => 'withdrawn'
        ]);
    }
} catch (Exception $e) {
    if (isset($db)) {
        try { $db->rollback(); } catch (Exception $ignore) {}
    }
    error_log('Update application status error: ' . $e->getMessage());
    jsonResponse(['error' => 'システムエラーが発生しました'], 500);
}

