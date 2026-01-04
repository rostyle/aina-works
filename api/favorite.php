<?php
require_once '../config/config.php';

// ログインチェック
if (!isLoggedIn()) {
    ErrorHandler::jsonAuthError();
}

// POSTリクエストのみ受け付け
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ErrorHandler::jsonError('POSTリクエストのみ受け付けます', 405, null, 'METHOD_NOT_ALLOWED');
}

$user = getCurrentUser();
if (!$user) {
    ErrorHandler::jsonError(Messages::USER_NOT_FOUND, 401, null, 'USER_NOT_FOUND');
}

// JSONデータを取得
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    ErrorHandler::jsonError('無効なJSONデータです', 400, null, 'INVALID_JSON');
}

$action = $input['action'] ?? '';
$targetType = $input['target_type'] ?? '';
$targetId = (int)($input['target_id'] ?? 0);

// バリデーション
$validation = new ValidationResult();

$error = validateIn($action, ['add', 'remove'], 'アクション');
if ($error) {
    $validation->addError('action', $error);
}

$error = validateIn($targetType, ['work', 'creator'], 'ターゲットタイプ');
if ($error) {
    $validation->addError('target_type', $error);
}

$error = validatePositiveInteger($targetId, 'ターゲットID');
if ($error) {
    $validation->addError('target_id', $error);
}

if (!$validation->isValid) {
    ErrorHandler::jsonValidationError($validation);
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
            ErrorHandler::jsonNotFoundError('対象');
        }
        
        // 自分自身をお気に入りに追加できないようにする
        if ($targetType === 'creator' && $targetId === $user['id']) {
            ErrorHandler::jsonError(Messages::USER_SELF_OPERATION, 400, null, 'OWN_RESOURCE');
        }
        
        if ($targetType === 'work') {
            $work = $db->selectOne("SELECT user_id FROM works WHERE id = ?", [$targetId]);
            if ($work && $work['user_id'] === $user['id']) {
                ErrorHandler::jsonError('自分の作品をお気に入りに追加することはできません', 400, null, 'OWN_RESOURCE');
            }
        }
        
        // 既にお気に入りに追加されているかチェック
        $existing = $db->selectOne(
            "SELECT id FROM favorites WHERE user_id = ? AND target_type = ? AND target_id = ?",
            [$user['id'], $targetType, $targetId]
        );
        
        if ($existing) {
            ErrorHandler::jsonSuccess(Messages::FAVORITE_ALREADY_ADDED, ['is_favorite' => true]);
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
                    
                    $actionUrl = url('profile', true);
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
                    
                    $actionUrl = url('works', true);
                    sendNotificationMail($workOwner['email'], $subject, $message, $actionUrl, 'あなたの作品を見る');
                }
            }
        } catch (Exception $e) {
            error_log('お気に入り通知メール送信エラー: ' . $e->getMessage());
            // メール送信エラーでもお気に入り追加は成功として処理
        }
        
        ErrorHandler::jsonSuccess('お気に入りに追加しました', ['is_favorite' => true]);
        
    } else {
        // お気に入りから削除
        $deleted = $db->update(
            "DELETE FROM favorites WHERE user_id = ? AND target_type = ? AND target_id = ?",
            [$user['id'], $targetType, $targetId]
        );
        
        if ($deleted > 0) {
            ErrorHandler::jsonSuccess('お気に入りから削除しました', ['is_favorite' => false]);
        } else {
            ErrorHandler::jsonSuccess('お気に入りに登録されていませんでした', ['is_favorite' => false]);
        }
    }
    
} catch (Exception $e) {
    ErrorHandler::handleException($e, 'Favorite API error: ' . $e->getMessage());
}

// 出力バッファリングを終了
ob_end_flush();