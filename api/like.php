<?php
require_once '../config/config.php';

// JSONレスポンスのヘッダー設定
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

// ログインチェック
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'ログインが必要です']);
    exit;
}

// POSTリクエストのみ受け付け
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'POSTリクエストのみ受け付けます']);
    exit;
}

$user = getCurrentUser();
if (!$user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'ユーザー情報を取得できません']);
    exit;
}

// JSONデータを取得
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => '無効なJSONデータです']);
    exit;
}

$workId = (int)($input['work_id'] ?? 0);

// バリデーション
if ($workId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => '無効な作品IDです']);
    exit;
}

$db = Database::getInstance();

try {
    // 作品の存在確認
    $work = $db->selectOne("SELECT id, user_id, like_count FROM works WHERE id = ? AND status = 'published'", [$workId]);
    
    if (!$work) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => '作品が見つかりません']);
        exit;
    }
    
    // 自分の作品にはいいねできない
    if ($work['user_id'] === $user['id']) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => '自分の作品にいいねすることはできません']);
        exit;
    }
    
    // 既にいいねしているかチェック
    $existingLike = $db->selectOne(
        "SELECT id FROM work_likes WHERE user_id = ? AND work_id = ?",
        [$user['id'], $workId]
    );
    
    $db->beginTransaction();
    
    if ($existingLike) {
        // いいねを取り消し
        $db->update("DELETE FROM work_likes WHERE user_id = ? AND work_id = ?", [$user['id'], $workId]);
        $db->update("UPDATE works SET like_count = like_count - 1 WHERE id = ?", [$workId]);
        
        $newLikeCount = max(0, $work['like_count'] - 1);
        $isLiked = false;
        $message = 'いいねを取り消しました';
    } else {
        // いいねを追加
        $db->insert("INSERT INTO work_likes (user_id, work_id) VALUES (?, ?)", [$user['id'], $workId]);
        $db->update("UPDATE works SET like_count = like_count + 1 WHERE id = ?", [$workId]);
        
        $newLikeCount = $work['like_count'] + 1;
        $isLiked = true;
        $message = 'いいねしました';
    }
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => $message,
        'is_liked' => $isLiked,
        'like_count' => $newLikeCount
    ]);
    
} catch (Exception $e) {
    $db->rollback();
    error_log("Like API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'サーバーエラーが発生しました']);
}
?>