<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-TOKEN');

require_once '../config/config.php';

// POSTリクエストのみ受け付け
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// CSRFトークンを検証
$token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!verifyCsrfToken($token)) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid CSRF token']);
    exit;
}

// リクエストデータを取得
$input = json_decode(file_get_contents('php://input'), true);
$targetType = $input['target_type'] ?? '';
$targetId = (int)($input['target_id'] ?? 0);

// ログイン状態をチェック
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'ログインが必要です']);
    exit;
}

$userId = $_SESSION['user_id'];

// バリデーション
if (!in_array($targetType, ['work', 'creator']) || $targetId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid parameters']);
    exit;
}

$db = Database::getInstance();

try {
    $db->beginTransaction();
    
    // 既にいいねしているかチェック
    $existing = $db->selectOne(
        "SELECT id FROM favorites WHERE user_id = ? AND target_type = ? AND target_id = ?",
        [$userId, $targetType, $targetId]
    );
    
    if ($existing) {
        // いいねを取り消す
        $db->delete(
            "DELETE FROM favorites WHERE user_id = ? AND target_type = ? AND target_id = ?",
            [$userId, $targetType, $targetId]
        );
        
        // 作品のいいね数を更新
        if ($targetType === 'work') {
            $db->update("UPDATE works SET like_count = like_count - 1 WHERE id = ? AND like_count > 0", [$targetId]);
        }
        
        $liked = false;
        $message = 'いいねを取り消しました';
    } else {
        // いいねを追加
        $db->insert(
            "INSERT INTO favorites (user_id, target_type, target_id) VALUES (?, ?, ?)",
            [$userId, $targetType, $targetId]
        );
        
        // 作品のいいね数を更新
        if ($targetType === 'work') {
            $db->update("UPDATE works SET like_count = like_count + 1 WHERE id = ?", [$targetId]);
        }
        
        $liked = true;
        $message = 'いいねしました';
    }
    
    $db->commit();
    
    // 現在のいいね数を取得
    if ($targetType === 'work') {
        $likeCount = $db->selectOne("SELECT like_count FROM works WHERE id = ?", [$targetId])['like_count'] ?? 0;
    } else {
        $likeCount = $db->selectOne(
            "SELECT COUNT(*) as count FROM favorites WHERE target_type = 'creator' AND target_id = ?",
            [$targetId]
        )['count'] ?? 0;
    }
    
    echo json_encode([
        'success' => true,
        'liked' => $liked,
        'like_count' => (int)$likeCount,
        'message' => $message
    ]);
    
} catch (Exception $e) {
    $db->rollBack();
    http_response_code(500);
    error_log($e->getMessage()); // エラーをログに記録
    echo json_encode(['error' => 'サーバーエラーが発生しました']);
}
?>
