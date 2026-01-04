<?php
require_once '../config/config.php';

// Like API: always return clean JSON

if (!isLoggedIn()) {
    ErrorHandler::jsonAuthError();
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    ErrorHandler::jsonError('POSTリクエストのみ受け付けます', 405, null, 'METHOD_NOT_ALLOWED');
}

$user = getCurrentUser();
if (!$user) {
    ErrorHandler::jsonError(Messages::USER_NOT_FOUND, 401, null, 'USER_NOT_FOUND');
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    ErrorHandler::jsonError('無効なJSONデータです', 400, null, 'INVALID_JSON');
}

// バリデーション
$validation = new ValidationResult();
$workId = (int)($input['work_id'] ?? 0);

$error = validatePositiveInteger($workId, '作品ID');
if ($error) {
    $validation->addError('work_id', $error);
}

if (!$validation->isValid) {
    ErrorHandler::jsonValidationError($validation);
}

$db = Database::getInstance();

try {
    // 作品の存在確認（like_countはNULLでも0として扱う）
    $work = $db->selectOne(
        "SELECT id, user_id, IFNULL(like_count, 0) AS like_count FROM works WHERE id = ? AND status = 'published'",
        [$workId]
    );

    if (!$work) {
        ErrorHandler::jsonNotFoundError('作品');
    }

    // 自分の作品にはいいねできない
    if ((int)$work['user_id'] === (int)$user['id']) {
        ErrorHandler::jsonError(Messages::WORK_OWN_LIKE, 400, null, 'OWN_RESOURCE');
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

    ErrorHandler::jsonSuccess($message, [
        'is_liked' => $isLiked,
        'like_count' => $newLikeCount,
    ]);
} catch (Exception $e) {
    if (isset($db)) {
        try { $db->rollback(); } catch (Exception $e2) {}
    }
    ErrorHandler::handleException($e, 'Like API error: ' . $e->getMessage());
}

