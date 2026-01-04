<?php
/**
 * 統一されたエラーメッセージ定義
 */

class Messages {
    // 認証関連
    const AUTH_REQUIRED = 'ログインが必要です';
    const AUTH_FAILED = 'メールアドレスまたはパスワードが正しくありません';
    const AUTH_SESSION_EXPIRED = 'セッションの有効期限が切れました。再度ログインしてください';
    const AUTH_INVALID_TOKEN = '認証トークンが無効です';
    
    // 権限関連
    const PERMISSION_DENIED = 'この操作を実行する権限がありません';
    const PERMISSION_INSUFFICIENT = 'この機能を使用するには、より高い権限が必要です';
    const PERMISSION_OWN_RESOURCE = '自分のリソースに対してこの操作は実行できません';
    
    // CSRF関連
    const CSRF_INVALID = '不正なリクエストです。ページを再読み込みして再度お試しください';
    const CSRF_EXPIRED = 'セキュリティトークンの有効期限が切れました。ページを再読み込みしてください';
    
    // バリデーション関連
    const VALIDATION_REQUIRED = '必須項目です';
    const VALIDATION_INVALID_FORMAT = '入力形式が正しくありません';
    const VALIDATION_TOO_SHORT = '文字数が不足しています';
    const VALIDATION_TOO_LONG = '文字数が多すぎます';
    const VALIDATION_OUT_OF_RANGE = '指定された範囲外の値です';
    const VALIDATION_INVALID_VALUE = '無効な値が入力されています';
    
    // リソース関連
    const RESOURCE_NOT_FOUND = 'リソースが見つかりません';
    const RESOURCE_ALREADY_EXISTS = '既に存在するリソースです';
    const RESOURCE_DELETED = 'リソースは既に削除されています';
    const RESOURCE_UNAVAILABLE = 'リソースは現在利用できません';
    
    // ファイル関連
    const FILE_NOT_UPLOADED = 'ファイルがアップロードされていません';
    const FILE_TOO_LARGE = 'ファイルサイズが大きすぎます';
    const FILE_INVALID_TYPE = '許可されていないファイル形式です';
    const FILE_UPLOAD_FAILED = 'ファイルのアップロードに失敗しました';
    const FILE_NOT_FOUND = 'ファイルが見つかりません';
    
    // データベース関連
    const DB_CONNECTION_ERROR = 'データベースに接続できませんでした';
    const DB_QUERY_ERROR = 'データの取得に失敗しました';
    const DB_SAVE_ERROR = 'データの保存に失敗しました';
    const DB_UPDATE_ERROR = 'データの更新に失敗しました';
    const DB_DELETE_ERROR = 'データの削除に失敗しました';
    
    // サーバー関連
    const SERVER_ERROR = 'サーバーエラーが発生しました。しばらく時間をおいて再度お試しください';
    const SERVER_TIMEOUT = 'リクエストがタイムアウトしました。再度お試しください';
    const SERVICE_UNAVAILABLE = 'サービスが一時的に利用できません。しばらく時間をおいて再度お試しください';
    
    // 外部API関連
    const API_CONNECTION_ERROR = '外部サービスに接続できませんでした。しばらく時間をおいて再度お試しください';
    const API_ERROR = '外部サービスでエラーが発生しました';
    const API_TIMEOUT = '外部サービスへのリクエストがタイムアウトしました';
    
    // ビジネスロジック関連
    const OPERATION_NOT_ALLOWED = 'この操作は実行できません';
    const OPERATION_FAILED = '操作に失敗しました';
    const OPERATION_CONFLICT = '操作が競合しています。再度お試しください';
    
    // ユーザー向けメッセージ（具体的な操作）
    const WORK_NOT_FOUND = '作品が見つかりません';
    const WORK_ALREADY_LIKED = '既にいいねしています';
    const WORK_OWN_LIKE = '自分の作品にいいねすることはできません';
    const WORK_REVIEW_OWN = '自分の作品にはレビューできません';
    
    const JOB_NOT_FOUND = '案件が見つかりません';
    const JOB_ALREADY_APPLIED = '既に応募済みです';
    const JOB_NOT_OPEN = 'この案件は現在応募を受け付けていません';
    const JOB_APPLICATION_NOT_FOUND = '応募情報が見つかりません';
    
    const USER_NOT_FOUND = 'ユーザーが見つかりません';
    const USER_INACTIVE = 'このユーザーは現在利用できません';
    const USER_SELF_OPERATION = '自分自身に対してこの操作は実行できません';
    
    const CHAT_ROOM_NOT_FOUND = 'チャットルームが見つかりません';
    const CHAT_MESSAGE_EMPTY = 'メッセージを入力してください';
    const CHAT_MESSAGE_TOO_LONG = 'メッセージが長すぎます';
    
    const FAVORITE_ALREADY_ADDED = '既にお気に入りに追加されています';
    const FAVORITE_NOT_FOUND = 'お気に入りが見つかりません';
    
    const BANK_ACCOUNT_NOT_SET = '振込先情報が登録されていません';
    const BANK_ACCOUNT_INVALID = '振込先情報が正しくありません';
    
    /**
     * メッセージを取得（カスタマイズ可能）
     */
    public static function get($key, $replacements = []) {
        $message = constant('self::' . $key);
        
        if (!empty($replacements)) {
            foreach ($replacements as $search => $replace) {
                $message = str_replace('{' . $search . '}', $replace, $message);
            }
        }
        
        return $message;
    }
    
    /**
     * ユーザーフレンドリーなエラーメッセージを生成
     */
    public static function userFriendly($technicalMessage, $context = []) {
        // 技術的なエラーメッセージをユーザーフレンドリーなメッセージに変換
        $mappings = [
            'SQLSTATE' => self::DB_CONNECTION_ERROR,
            'Duplicate entry' => self::RESOURCE_ALREADY_EXISTS,
            'Data too long' => self::VALIDATION_TOO_LONG,
            'Invalid date' => self::VALIDATION_INVALID_FORMAT,
            'Unknown column' => self::DB_QUERY_ERROR,
            'Connection refused' => self::DB_CONNECTION_ERROR,
            'timeout' => self::SERVER_TIMEOUT,
        ];
        
        foreach ($mappings as $pattern => $message) {
            if (stripos($technicalMessage, $pattern) !== false) {
                return $message;
            }
        }
        
        // デフォルトのサーバーエラーメッセージ
        return self::SERVER_ERROR;
    }
}
