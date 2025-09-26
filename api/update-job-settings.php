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
$newStatus = $_POST['status'] ?? null; // 'open' | 'closed' | 'in_progress' | 'contracted' | 'delivered' | 'approved' | 'completed' | 'cancelled'
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

    // 変更前ステータスを保持（チャット通知用）
    $previousStatus = (string)($job['status'] ?? '');
    $jobTitleForChat = (string)($job['title'] ?? '');

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

    // ステータスENUMの互換マイグレーション（本番環境でENUMの場合に不足値を追加）
    try {
        $statusCol = $db->selectOne("SHOW COLUMNS FROM jobs LIKE 'status'");
        if ($statusCol) {
            $typeDef = (string)($statusCol['Type'] ?? $statusCol['type'] ?? '');
            if ($typeDef !== '' && stripos($typeDef, 'enum(') === 0) {
                // e.g. enum('open','closed',...)
                $valuesStr = substr($typeDef, 5, -1); // remove "enum(" and trailing ")"
                $current = array_map(function($v){ return trim($v, " '" ); }, explode(',', $valuesStr));
                $required = ['open','closed','in_progress','contracted','delivered','approved','completed','cancelled'];
                $merged = array_values(array_unique(array_merge($current, $required)));
                if (count($merged) !== count($current)) {
                    $enumList = "'" . implode("','", $merged) . "'";
                    error_log('[jobs.status enum] current=' . json_encode($current, JSON_UNESCAPED_UNICODE) . ' -> new=' . $enumList);
                    $db->update("ALTER TABLE jobs MODIFY status ENUM($enumList) NOT NULL DEFAULT 'open'");
                }
            }
        }
    } catch (Exception $e) {
        error_log('[jobs.status enum] migration failed: ' . $e->getMessage());
        // 失敗しても本処理は続行（権限や権限外環境）
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
    // 新UIで status が指定されている場合は action を無視する
    if ($newStatus === null && ($recruitAction === 'open' || $recruitAction === 'close')) {
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
        $allowed = ['open','closed','in_progress','contracted','delivered','approved','completed','cancelled'];
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
            if (in_array($newStatus, ['closed','in_progress','contracted','delivered','approved','completed','cancelled'], true)) {
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

    // ステータス変更時のチャット自動通知（ベストエフォート）
    try {
        if ($newStatus !== null && $previousStatus !== '' && $previousStatus !== $newStatus) {
            // 受諾済みクリエイター全員に通知
            $acceptedCreators = $db->select(
                "SELECT DISTINCT creator_id FROM job_applications WHERE job_id = ? AND status = 'accepted'",
                [$jobId]
            );

            if (!empty($acceptedCreators)) {
                $statusLabels = [
                    'open' => '募集中',
                    'closed' => '募集終了',
                    'contracted' => '契約済み',
                    'delivered' => '納品済み',
                    'approved' => '検収済み',
                    'cancelled' => 'キャンセル',
                    'in_progress' => '進行中', // 互換
                    'completed' => '完了'       // 互換
                ];

                $labelOld = $statusLabels[$previousStatus] ?? $previousStatus;
                $labelNew = $statusLabels[$newStatus] ?? $newStatus;

                $systemMessage  = "【案件ステータス変更のお知らせ】\n";
                $systemMessage .= "案件: " . ($jobTitleForChat !== '' ? $jobTitleForChat : '案件') . "\n";
                $systemMessage .= "ステータス: {$labelOld} → {$labelNew}\n\n";
                $systemMessage .= "本メッセージはシステムからの自動通知です。";

                foreach ($acceptedCreators as $row) {
                    $creatorId = (int)$row['creator_id'];
                    if ($creatorId <= 0) { continue; }

                    // チャットルーム確保
                    $room = $db->selectOne(
                        "SELECT id FROM chat_rooms WHERE (user1_id = ? AND user2_id = ?) OR (user1_id = ? AND user2_id = ?)",
                        [$currentUser['id'], $creatorId, $creatorId, $currentUser['id']]
                    );
                    if (!$room) {
                        $roomId = (int)$db->insert(
                            "INSERT INTO chat_rooms (user1_id, user2_id, created_at) VALUES (?, ?, NOW())",
                            [$currentUser['id'], $creatorId]
                        );
                    } else {
                        $roomId = (int)$room['id'];
                    }

                    // システム通知を送信（送信者は依頼者）
                    $db->insert(
                        "INSERT INTO chat_messages (room_id, sender_id, message, created_at) VALUES (?, ?, ?, NOW())",
                        [$roomId, $currentUser['id'], $systemMessage]
                    );
                }
            }
        }
    } catch (Exception $e) {
        // 通知失敗は本処理に影響させない
        error_log('Job status change chat notify failed: ' . $e->getMessage());
    }

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