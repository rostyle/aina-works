<?php
require_once '../config/config.php';

// Like API: always return clean JSON

if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => 'ログインが必要です'], 401);
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'POSTリクエストのみ受け付けます'], 405);
}

$user = getCurrentUser();
if (!$user) {
    jsonResponse(['success' => false, 'message' => 'ユーザー情報を取得できません'], 401);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    jsonResponse(['success' => false, 'message' => '無効なJSONデータです'], 400);
}

$workId = (int)($input['work_id'] ?? 0);
if ($workId <= 0) {
    jsonResponse(['success' => false, 'message' => '無効な作品IDです'], 400);
}

$db = Database::getInstance();

try {
    // 作品の存在確認（like_countはNULLでも0として扱う）
    $work = $db->selectOne(
        "SELECT id, user_id, IFNULL(like_count, 0) AS like_count FROM works WHERE id = ? AND status = 'published'",
        [$workId]
    );

    if (!$work) {
        jsonResponse(['success' => false, 'message' => '作品が見つかりません'], 404);
    }

    // 自分の作品にはいいねできない
    if ((int)$work['user_id'] === (int)$user['id']) {
        jsonResponse(['success' => false, 'message' => '自分の作品にいいねすることはできません'], 400);
    }

    // 現在のいいね状態
    $existingLike = $db->selectOne(
        "SELECT id FROM work_likes WHERE user_id = ? AND work_id = ?",
        [$user['id'], $workId]
    );

    $db->beginTransaction();

    if ($existingLike) {
        // いいね取り消し（負数にならないようにガード）
        $db->update("DELETE FROM work_likes WHERE user_id = ? AND work_id = ?", [$user['id'], $workId]);
        $db->update("UPDATE works SET like_count = GREATEST(COALESCE(like_count, 0) - 1, 0) WHERE id = ?", [$workId]);

        $current = (int)($work['like_count'] ?? 0);
        $newLikeCount = max(0, $current - 1);
        $isLiked = false;
        $message = 'いいねを取り消しました';
    } else {
        // いいね追加
        $db->insert("INSERT INTO work_likes (user_id, work_id) VALUES (?, ?)", [$user['id'], $workId]);
        $db->update("UPDATE works SET like_count = COALESCE(like_count, 0) + 1 WHERE id = ?", [$workId]);

        $current = (int)($work['like_count'] ?? 0);
        $newLikeCount = $current + 1;
        $isLiked = true;
        $message = 'いいねしました';
    }

    $db->commit();

    jsonResponse([
        'success' => true,
        'message' => $message,
        'is_liked' => $isLiked,
        'like_count' => $newLikeCount,
    ], 200);
} catch (Exception $e) {
    if (isset($db)) {
        try { $db->rollback(); } catch (Exception $e2) {}
    }
    error_log('Like API error: ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'サーバーエラーが発生しました'], 500);
}

