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
        
        // 対象者にメール通知を送信
        try {
            if ($targetType === 'creator') {
                $targetUser = $db->selectOne(
                    "SELECT email, full_name FROM users WHERE id = ?",
                    [$targetId]
                );
                
                if ($targetUser) {
                    $subject = "【AiNA Works】あなたがお気に入りに追加されました";
                    $message = "こんにちは、{$targetUser['full_name']}さん\n\n";
                    $message .= "{$user['full_name']}さんがあなたをお気に入りに追加しました。\n\n";
                    $message .= "これは、あなたのプロフィールや作品が評価されている証拠です。\n";
                    $message .= "今後も素晴らしい作品を作り続けて、多くのクライアントにアピールしましょう！";
                    
                    $actionUrl = url('profile.php');
                    sendNotificationMail($targetUser['email'], $subject, $message, $actionUrl, 'プロフィールを確認する');
                }
            } elseif ($targetType === 'work') {
                $workOwner = $db->selectOne(
                    "SELECT u.email, u.full_name, w.title FROM users u 
                     INNER JOIN works w ON u.id = w.user_id 
                     WHERE w.id = ?",
                    [$targetId]
                );
                
                if ($workOwner) {
                    $subject = "【AiNA Works】あなたの作品がお気に入りに追加されました";
                    $message = "こんにちは、{$workOwner['full_name']}さん\n\n";
                    $message .= "{$user['full_name']}さんがあなたの作品「{$workOwner['title']}」をお気に入りに追加しました。\n\n";
                    $message .= "素晴らしい作品が評価されています！\n";
                    $message .= "引き続き魅力的な作品を投稿して、さらに多くの人にアピールしましょう。";
                    
                    $actionUrl = url('works.php');
                    sendNotificationMail($workOwner['email'], $subject, $message, $actionUrl, 'あなたの作品を見る');
                }
            }
        } catch (Exception $e) {
            error_log('お気に入り通知メール送信エラー: ' . $e->getMessage());
            // メール送信エラーでもお気に入り追加は成功として処理
        }
        
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
