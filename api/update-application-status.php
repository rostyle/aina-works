<?php
require_once '../config/config.php';

header('Content-Type: application/json');

// ログイン確認
if (!isLoggedIn()) {
    jsonResponse(['error' => 'ログインが必要です'], 401);
}

// CSRFトークン検証
$csrfToken = $_POST['csrf_token'] ?? '';
if (!verifyCsrfToken($csrfToken)) {
    jsonResponse(['error' => '不正なリクエストです'], 403);
}

// 入力取得
$applicationId = (int)($_POST['application_id'] ?? 0);
$action = trim($_POST['action'] ?? ''); // 'accept' | 'reject'

if (!$applicationId || !in_array($action, ['accept', 'reject'], true)) {
    jsonResponse(['error' => '入力が不正です'], 400);
}

try {
    $db = Database::getInstance();
    $currentUser = getCurrentUser();

    // 応募と案件を取得（所有者チェック用）
    $application = $db->selectOne(
        "SELECT ja.*, j.client_id, j.status AS job_status, j.title AS job_title, j.id AS job_id, u.id AS creator_id, u.full_name AS creator_name
         FROM job_applications ja
         JOIN jobs j ON ja.job_id = j.id
         JOIN users u ON ja.creator_id = u.id
         WHERE ja.id = ?",
        [$applicationId]
    );

    if (!$application) {
        jsonResponse(['error' => '応募が見つかりません'], 404);
    }

    // 権限チェック：案件の依頼者のみ操作可能
    if ((int)$application['client_id'] !== (int)$currentUser['id']) {
        jsonResponse(['error' => '権限がありません'], 403);
    }

    // 状態ガード
    if ($application['status'] !== 'pending') {
        jsonResponse(['error' => 'この応募は処理済みです'], 400);
    }

    $db->beginTransaction();

    if ($action === 'accept') {
        // 複数採用を許可するため、受諾済みチェックは行わない
        // 受諾可能な状態か（完了済み/完全キャンセルは不可）
        if (in_array($application['job_status'], ['completed'], true)) {
            $db->rollback();
            jsonResponse(['error' => 'この案件は受諾できない状態です'], 400);
        }

        // 応募を受諾
        $db->update(
            "UPDATE job_applications SET status = 'accepted' WHERE id = ?",
            [$applicationId]
        );

        // 採用数の追跡（カラムがあれば更新）。なければスキップ
        try {
            // カラム存在チェック
            $hasAcceptedCount = $db->selectOne("SHOW COLUMNS FROM jobs LIKE 'accepted_count'");
            if ($hasAcceptedCount) {
                $db->update("UPDATE jobs SET accepted_count = IFNULL(accepted_count,0) + 1 WHERE id = ?", [$application['job_id']]);
            }
            // 募集人数に達したら募集停止（カラムがあれば）
            $hasHiringLimit = $db->selectOne("SHOW COLUMNS FROM jobs LIKE 'hiring_limit'");
            $hasRecruiting = $db->selectOne("SHOW COLUMNS FROM jobs LIKE 'is_recruiting'");
            if ($hasHiringLimit && $hasRecruiting) {
                $row = $db->selectOne("SELECT IFNULL(hiring_limit,1) as hiring_limit, IFNULL(accepted_count,0) as accepted_count FROM jobs WHERE id = ?", [$application['job_id']]);
                if ($row && (int)$row['accepted_count'] >= (int)$row['hiring_limit']) {
                    $db->update("UPDATE jobs SET is_recruiting = 0 WHERE id = ?", [$application['job_id']]);
                }
            }
            // open なら contracted へ（契約成立）
            if ($application['job_status'] === 'open') {
                $db->update("UPDATE jobs SET status = 'contracted' WHERE id = ?", [$application['job_id']]);
            }
        } catch (Exception $e) {
            // カラム未整備でも処理続行
        }

        // チャットルームの取得/作成
        $room = $db->selectOne(
            "SELECT id FROM chat_rooms WHERE (user1_id = ? AND user2_id = ?) OR (user1_id = ? AND user2_id = ?)",
            [$currentUser['id'], $application['creator_id'], $application['creator_id'], $currentUser['id']]
        );
        if (!$room) {
            $roomId = $db->insert(
                "INSERT INTO chat_rooms (user1_id, user2_id, created_at) VALUES (?, ?, NOW())",
                [$currentUser['id'], $application['creator_id']]
            );
        } else {
            $roomId = (int)$room['id'];
        }

        // 初回メッセージを送信
        $initialMessage = "案件『" . ($application['job_title'] ?? '案件') . "』の応募を受諾しました。ここからやり取りを開始しましょう。";
        $db->insert(
            "INSERT INTO chat_messages (room_id, sender_id, message, created_at) VALUES (?, ?, ?, NOW())",
            [$roomId, $currentUser['id'], $initialMessage]
        );

        $db->commit();

        jsonResponse([
            'success' => true,
            'message' => '応募を受諾しました',
            'new_status' => 'accepted',
            'chat_room_id' => $roomId,
            'redirect_to_chat' => url('chat.php?user_id=' . $application['creator_id'])
        ]);
    } else { // reject
        $db->update(
            "UPDATE job_applications SET status = 'rejected' WHERE id = ?",
            [$applicationId]
        );

        $db->commit();

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
?>


