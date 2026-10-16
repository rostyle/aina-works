<?php
/**
 * 統一されたエラーハンドリングクラス
 */

/**
 * エラーハンドラークラス
 */
class ErrorHandler {
    /**
     * エラーレスポンスを返す（JSON形式）
     */
    public static function jsonError($message, $statusCode = 400, $errors = null, $errorCode = null) {
        $response = [
            'success' => false,
            'message' => $message
        ];
        
        if ($errors !== null) {
            $response['errors'] = $errors;
        }
        
        if ($errorCode !== null) {
            $response['error_code'] = $errorCode;
        }
        
        jsonResponse($response, $statusCode); // 出力・ヘッダ・exit は jsonResponse に一本化
    }
    
    /**
     * 成功レスポンスを返す（JSON形式）
     */
    public static function jsonSuccess($message = null, $data = null, $statusCode = 200) {
        $response = [
            'success' => true
        ];
        
        if ($message !== null) {
            $response['message'] = $message;
        }
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        jsonResponse($response, $statusCode); // 出力・ヘッダ・exit は jsonResponse に一本化
    }
    
    /**
     * バリデーションエラーを返す
     */
    public static function jsonValidationError($validationResult, $statusCode = 422) {
        self::jsonError(
            '入力内容に誤りがあります',
            $statusCode,
            $validationResult->errors,
            'VALIDATION_ERROR'
        );
    }
    
    /**
     * 認証エラーを返す
     */
    public static function jsonAuthError($message = 'ログインが必要です') {
        self::jsonError($message, 401, null, 'AUTH_REQUIRED');
    }
    
    /**
     * 権限エラーを返す
     */
    public static function jsonPermissionError($message = 'この操作を実行する権限がありません') {
        self::jsonError($message, 403, null, 'PERMISSION_DENIED');
    }
    
    /**
     * リソース未検出エラーを返す
     */
    public static function jsonNotFoundError($resourceName = 'リソース') {
        self::jsonError("{$resourceName}が見つかりません", 404, null, 'NOT_FOUND');
    }
    
    /**
     * CSRFエラーを返す
     */
    public static function jsonCsrfError($message = '不正なリクエストです') {
        self::jsonError($message, 403, null, 'CSRF_ERROR');
    }
    
    /**
     * サーバーエラーを返す
     */
    public static function jsonServerError($message = 'サーバーエラーが発生しました', $logError = null) {
        if ($logError !== null) {
            error_log('Server Error: ' . $logError);
        }
        self::jsonError($message, 500, null, 'SERVER_ERROR');
    }
    
    /**
     * 例外をキャッチしてエラーレスポンスを返す
     */
    public static function handleException($exception, $logMessage = null) {
        $message = isset($logMessage) ? $logMessage : $exception->getMessage();
        error_log('Exception: ' . $message);
        error_log('Stack trace: ' . $exception->getTraceAsString());
        
        // デバッグモードの場合は詳細なエラーを返す
        if (defined('DEBUG') && DEBUG) {
            self::jsonError(
                'システムエラーが発生しました: ' . $exception->getMessage(),
                500,
                ['trace' => $exception->getTraceAsString()],
                'EXCEPTION'
            );
        } else {
            self::jsonServerError('システムエラーが発生しました。しばらく時間をおいて再度お試しください。', $message);
        }
    }
    
    /**
     * エラーをログに記録
     */
    public static function logError($message, $context = []) {
        $logData = [
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s'),
            'context' => $context
        ];
        
        error_log('Error: ' . json_encode($logData, JSON_UNESCAPED_UNICODE));
    }
}
