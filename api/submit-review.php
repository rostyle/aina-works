<?php
require_once '../config/config.php';

header('Content-Type: application/json');

// ログイン確認
if (!isLoggedIn()) {
    ErrorHandler::jsonAuthError();
}

// CSRFトークン検証
if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    ErrorHandler::jsonCsrfError();
}

try {
    $db = Database::getInstance();
    $currentUser = getCurrentUser();
    
    $workId = (int)($_POST['work_id'] ?? 0);
    $rating = (int)($_POST['rating'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');
    
    // バリデーション
    $validation = new ValidationResult();
    
    $error = validatePositiveInteger($workId, '作品');
    if ($error) {
        $validation->addError('work_id', $error);
    }
    
    $error = validateRange($rating, 1, 5, '評価');
    if ($error) {
        $validation->addError('rating', $error);
    }
    
    $error = validateRequired($comment, 'コメント');
    if ($error) {
        $validation->addError('comment', $error);
    }
    
    $error = validateLength($comment, null, 1000, 'コメント');
    if ($error) {
        $validation->addError('comment', $error);
    }
    
    if (!$validation->isValid) {
        error_log("Review submission errors: " . $validation->getErrorsAsString());
        $_SESSION['flash_message'] = $validation->getErrorsAsString();
        $_SESSION['flash_type'] = 'error';
        header('Location: ../work-detail?id=' . $workId);
        exit;
    }
    
    // 作品の存在確認
    $work = $db->selectOne("
        SELECT w.*, u.id as creator_id, u.full_name as creator_name
        FROM works w
        JOIN users u ON w.user_id = u.id
        WHERE w.id = ? AND w.status = 'published'
    ", [$workId]);
    
    if (!$work) {
        $_SESSION['flash_message'] = Messages::WORK_NOT_FOUND;
        $_SESSION['flash_type'] = 'error';
        header('Location: ../work');
        exit;
    }
    
    // 自分の作品にはレビューできない
    if ($work['creator_id'] == $currentUser['id']) {
        $_SESSION['flash_message'] = Messages::WORK_REVIEW_OWN;
        $_SESSION['flash_type'] = 'error';
        header('Location: ../work-detail?id=' . $workId);
        exit;
    }
    
    // 何回でもレビュー可能（既存レビューチェックを削除）
    
    // レビューを保存
    $reviewId = $db->insert("
        INSERT INTO reviews (reviewer_id, reviewee_id, work_id, rating, comment, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ", [
        $currentUser['id'],
        $work['creator_id'],
        $workId,
        $rating,
        $comment
    ]);
    
    if ($reviewId) {
        // 作品の平均評価とレビュー数を再計算
        $stats = $db->selectOne("
            SELECT AVG(rating) as avg_rating, COUNT(*) as review_count
            FROM reviews 
            WHERE work_id = ?
        ", [$workId]);
        
        // 作品の作者にメール通知を送信
        try {
            $creator = $db->selectOne("
                SELECT email, full_name FROM users WHERE id = ?
            ", [$work['creator_id']]);
            
            if ($creator) {
                $subject = "【AiNA Works】あなたの作品にレビューが投稿されました";
                $message = "こんにちは、{$creator['full_name']}さん\n\n";
                $message .= "{$currentUser['full_name']}さんがあなたの作品「{$work['title']}」にレビューを投稿しました。\n\n";
                $message .= "評価: " . str_repeat('★', $rating) . str_repeat('☆', 5 - $rating) . " ({$rating}/5)\n\n";
                $message .= "コメント:\n";
                $message .= $comment . "\n\n";
                $message .= "素晴らしいフィードバックをいただきました！\n";
                $message .= "今後の作品制作の参考にしてください。";
                
                $actionUrl = url('work-detail?id=' . $workId, true);
                sendNotificationMail($creator['email'], $subject, $message, $actionUrl, 'レビューを確認する');
            }
        } catch (Exception $e) {
            error_log('レビュー通知メール送信エラー: ' . $e->getMessage());
            // メール送信エラーでもレビュー投稿は成功として処理
        }
        
        // 成功時はリダイレクト
        $_SESSION['flash_message'] = 'レビューを投稿しました。ありがとうございます！';
        $_SESSION['flash_type'] = 'success';
        header('Location: ../work-detail?id=' . $workId);
        exit;
    } else {
        ErrorHandler::jsonError('レビューの保存に失敗しました', 500, null, 'SAVE_FAILED');
    }
    
} catch (Exception $e) {
    ErrorHandler::handleException($e, 'Review submission error: ' . $e->getMessage());
}
