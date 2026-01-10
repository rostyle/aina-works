<?php
/**
 * チャットメッセージ送信API
 * 依存関係なしで動作するシンプルなエラーハンドリング
 */

// JSONエラーレスポンスを返すヘルパー関数（クラスに依存しない）
function sendJsonError($message, $statusCode = 500, $debug = null) {
    if (ob_get_level()) ob_clean();
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    $response = [
        'success' => false,
        'message' => $message,
        'error_code' => 'API_ERROR'
    ];
    if ($debug) {
        $response['debug'] = $debug;
    }
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

// 出力バッファリング開始
ob_start();

// エラー表示設定
error_reporting(E_ALL);
ini_set('display_errors', 0);

// 致命的エラーをキャッチ
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        sendJsonError(
            '致命的なエラーが発生しました: ' . $error['message'],
            500,
            [
                'file' => basename($error['file']),
                'line' => $error['line'],
                'type' => $error['type']
            ]
        );
    }
});

// 依存関係を読み込む
require_once '../config/config.php';

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
    
    $roomId = (int)($_POST['room_id'] ?? 0);
    $message = trim($_POST['message'] ?? '');
    
    // バリデーション
    if ($roomId <= 0 || empty($message)) {
        ErrorHandler::jsonError('チャットルームまたはメッセージが正しくありません', 400);
    }
    
    if (mb_strlen($message) > 1000) {
        ErrorHandler::jsonError('メッセージは1000文字以内で入力してください', 400);
    }
    
    // チャットルームの存在確認と権限チェック
    $chatRoom = $db->selectOne("
        SELECT * FROM chat_rooms 
        WHERE id = ? AND (user1_id = ? OR user2_id = ?)
    ", [$roomId, $currentUser['id'], $currentUser['id']]);
    
    if (!$chatRoom) {
        ErrorHandler::jsonNotFoundError('チャットルーム');
    }
    
    // メッセージをデータベースに保存
    $messageId = $db->insert("
        INSERT INTO chat_messages (room_id, sender_id, message, created_at) 
        VALUES (?, ?, ?, NOW())
    ", [$roomId, $currentUser['id'], $message]);
    
    if (!$messageId) {
        ErrorHandler::jsonError('データベースへの保存に失敗しました', 500);
    }

    // チャットルームの更新時間を更新
    $db->update("UPDATE chat_rooms SET updated_at = NOW() WHERE id = ?", [$roomId]);
    
    // 送信したメッセージの詳細を取得
    $sentMessage = $db->selectOne("
        SELECT cm.*, u.full_name as sender_name, u.profile_image as sender_image
        FROM chat_messages cm
        JOIN users u ON cm.sender_id = u.id
        WHERE cm.id = ?
    ", [$messageId]);
    
    // 相手にメール通知を送信（失敗してもチャット送信自体は成功させる）
    try {
        $recipientId = ($chatRoom['user1_id'] == $currentUser['id']) ? $chatRoom['user2_id'] : $chatRoom['user1_id'];
        $recipient = $db->selectOne("SELECT email, full_name FROM users WHERE id = ?", [$recipientId]);
        
        if ($recipient) {
            $subject = "【AiNA Works】新しいチャットメッセージが届きました";
            $emailMessage = "こんにちは、{$recipient['full_name']}さん\n\n";
            $emailMessage .= "{$currentUser['full_name']}さんからチャットメッセージが届きました。\n\n";
            $emailMessage .= "メッセージ:\n";
            $emailMessage .= $message . "\n\n";
            $emailMessage .= "返信はチャットルームで行ってください。";
            
            $actionUrl = url('chats', true);
            sendNotificationMail($recipient['email'], $subject, $emailMessage, $actionUrl, 'チャットを確認する');
        }
    } catch (Throwable $e) {
        error_log('Chat notification mail error: ' . $e->getMessage());
    }
    
    // 成功レスポンス
    if (ob_get_level()) ob_clean();
    ErrorHandler::jsonSuccess('メッセージを送信しました', [
        'id' => $sentMessage['id'],
        'sender_id' => $sentMessage['sender_id'],
        'message' => $sentMessage['message'],
        'sender_name' => $sentMessage['sender_name'],
        'sender_image' => $sentMessage['sender_image'],
        'time' => date('H:i', strtotime($sentMessage['created_at'])),
        'is_read' => false
    ]);

} catch (Throwable $e) {
    if (ob_get_level()) ob_clean();
    ErrorHandler::handleException($e, 'API Send Message Error');
}