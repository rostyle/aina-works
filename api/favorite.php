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

$action = $input['action'] ?? '';
$targetType = $input['target_type'] ?? '';
$targetId = (int)($input['target_id'] ?? 0);

// バリデーション
if (!in_array($action, ['add', 'remove'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => '無効なアクションです']);
    exit;
}

if (!in_array($targetType, ['work', 'creator'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => '無効なターゲットタイプです']);
    exit;
}

if ($targetId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => '無効なターゲットIDです']);
    exit;
}

$db = Database::getInstance();

try {
    if ($action === 'add') {
        // お気に入りに追加
        
        // 対象の存在確認
        if ($targetType === 'work') {
            $target = $db->selectOne("SELECT id FROM works WHERE id = ? AND status = 'published'", [$targetId]);
        } else {
            $target = $db->selectOne("SELECT id FROM users WHERE id = ? AND is_active = 1", [$targetId]);
        }
        
        if (!$target) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => '対象が見つかりません']);
            exit;
        }
        
        // 自分自身をお気に入りに追加できないようにする
        if ($targetType === 'creator' && $targetId === $user['id']) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => '自分自身をお気に入りに追加することはできません']);
            exit;
        }
        
        if ($targetType === 'work') {
            $work = $db->selectOne("SELECT user_id FROM works WHERE id = ?", [$targetId]);
            if ($work && $work['user_id'] === $user['id']) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => '自分の作品をお気に入りに追加することはできません']);
                exit;
            }
        }
        
        // 既にお気に入りに追加されているかチェック
        $existing = $db->selectOne(
            "SELECT id FROM favorites WHERE user_id = ? AND target_type = ? AND target_id = ?",
            [$user['id'], $targetType, $targetId]
        );
        
        if ($existing) {
            echo json_encode(['success' => true, 'message' => '既にお気に入りに追加されています', 'is_favorite' => true]);
            exit;
        }
        
        // お気に入りに追加
        $db->insert(
            "INSERT INTO favorites (user_id, target_type, target_id) VALUES (?, ?, ?)",
            [$user['id'], $targetType, $targetId]
        );
        
        echo json_encode(['success' => true, 'message' => 'お気に入りに追加しました', 'is_favorite' => true]);
        
    } else {
        // お気に入りから削除
        $deleted = $db->update(
            "DELETE FROM favorites WHERE user_id = ? AND target_type = ? AND target_id = ?",
            [$user['id'], $targetType, $targetId]
        );
        
        if ($deleted > 0) {
            echo json_encode(['success' => true, 'message' => 'お気に入りから削除しました', 'is_favorite' => false]);
        } else {
            echo json_encode(['success' => true, 'message' => 'お気に入りに登録されていませんでした', 'is_favorite' => false]);
        }
    }
    
} catch (Exception $e) {
    error_log("Favorite API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'サーバーエラーが発生しました']);
}
?>
