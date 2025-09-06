<?php
/**
 * 共通関数
 */

/**
 * HTMLエスケープ
 */
function h($str) {
    if ($str === null) {
        return '';
    }
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

/**
 * URL生成
 */
function url($path = '') {
    if (empty($path)) {
        return './';
    }
    return './' . ltrim($path, '/');
}

/**
 * アセットURL生成
 */
function asset($path) {
    return './assets/' . ltrim($path, '/');
}

/**
 * アップロードされたアセットのURL生成
 */
function uploaded_asset($path) {
    if (empty($path)) {
        return asset('images/default-avatar.png');
    }
    
    // 外部URLの場合はそのまま返す
    if (strpos($path, 'http') === 0) {
        return $path;
    }
    
    // デフォルトアセットの場合はassets配下のパスを返す
    if (strpos($path, 'assets/images/') === 0) {
        return asset(substr($path, strlen('assets/images/')));
    }
    
    // アップロードされたファイルの場合はserve.php経由で返す
    return url('serve.php?file=' . rawurlencode($path));
}

/**
 * 現在のURL取得
 */
function getCurrentUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    return $protocol . '://' . $host . $uri;
}

/**
 * リダイレクト
 */
function redirect($url) {
    header('Location: ' . $url);
    exit;
}

/**
 * JSONレスポンス
 */
function jsonResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * フラッシュメッセージ設定
 */
function setFlash($type, $message) {
    $_SESSION['flash'][$type] = $message;
}

/**
 * フラッシュメッセージ取得
 */
function getFlash($type = null) {
    if ($type) {
        $message = $_SESSION['flash'][$type] ?? null;
        unset($_SESSION['flash'][$type]);
        return $message;
    }
    
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

/**
 * CSRFトークン生成
 */
function generateCsrfToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * CSRFトークン検証
 */
function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * ログイン状態確認
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * 現在のユーザー取得
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    static $user = null;
    if ($user === null) {
        $db = Database::getInstance();
        $result = $db->selectOne(
            "SELECT * FROM users WHERE id = ? AND is_active = 1",
            [$_SESSION['user_id']]
        );
        $user = $result ?: null; // falseの場合はnullに変換
    }
    return $user;
}

/**
 * ユーザーの利用可能ロール取得
 */
function getUserRoles($userId = null) {
    if ($userId === null) {
        if (!isLoggedIn()) {
            return [];
        }
        $userId = $_SESSION['user_id'];
    }
    
    $db = Database::getInstance();
    $roles = $db->select(
        "SELECT role FROM user_roles WHERE user_id = ? AND is_enabled = 1 ORDER BY role",
        [$userId]
    );
    
    return array_column($roles, 'role');
}

/**
 * 現在のアクティブロール取得
 */
function getCurrentRole() {
    if (!isLoggedIn()) {
        return null;
    }
    
    // セッションにactive_roleが設定されていればそれを使用
    if (isset($_SESSION['active_role']) && !empty($_SESSION['active_role'])) {
        return $_SESSION['active_role'];
    }
    
    // なければユーザーのactive_roleを取得
    $user = getCurrentUser();
    if ($user && isset($user['active_role']) && !empty($user['active_role'])) {
        $_SESSION['active_role'] = $user['active_role'];
        return $user['active_role'];
    }
    
    // デフォルトロールを設定
    if ($user && isset($user['user_type'])) {
        $_SESSION['active_role'] = $user['user_type'];
        return $user['user_type'];
    }
    
    return null;
}

/**
 * ロール切り替え
 */
function switchRole($newRole) {
    if (!isLoggedIn()) {
        return false;
    }
    
    $availableRoles = getUserRoles();
    if (!in_array($newRole, $availableRoles)) {
        return false;
    }
    
    // セッションのロールを更新
    $_SESSION['active_role'] = $newRole;
    
    // データベースのactive_roleも更新
    $db = Database::getInstance();
    $db->update(
        "UPDATE users SET active_role = ? WHERE id = ?",
        [$newRole, $_SESSION['user_id']]
    );
    
    return true;
}

/**
 * 権限チェック
 */
function hasPermission($permission) {
    if (!isLoggedIn()) {
        return false;
    }
    
    $currentRole = getCurrentRole();
    if (!$currentRole) return false;
    
    // 基本的な権限チェック（現在のアクティブロールに基づく）
    switch ($permission) {
        case 'create_work':
            return $currentRole === 'creator';
        case 'post_job':
            return in_array($currentRole, ['client', 'sales']);
        case 'admin':
            return $currentRole === 'admin';
        default:
            return false;
    }
}

/**
 * ロール名を日本語に変換
 */
function getRoleDisplayName($role) {
    if ($role === null) {
        return 'ゲスト';
    }
    
    switch ($role) {
        case 'creator':
            return 'クリエイター';
        case 'client':
            return '依頼者';
        case 'sales':
            return '営業';
        default:
            return (string)$role;
    }
}

/**
 * ページネーション計算
 */
function calculatePagination($total, $perPage, $currentPage) {
    $totalPages = ceil($total / $perPage);
    $currentPage = max(1, min($currentPage, $totalPages));
    $offset = ($currentPage - 1) * $perPage;
    
    return [
        'total' => $total,
        'per_page' => $perPage,
        'current_page' => $currentPage,
        'total_pages' => $totalPages,
        'offset' => $offset,
        'has_prev' => $currentPage > 1,
        'has_next' => $currentPage < $totalPages,
        'prev_page' => $currentPage > 1 ? $currentPage - 1 : null,
        'next_page' => $currentPage < $totalPages ? $currentPage + 1 : null,
    ];
}

/**
 * 価格フォーマット
 */
function formatPrice($price) {
    return '¥' . number_format($price);
}

/**
 * 日付フォーマット
 */
function formatDate($date, $format = 'Y年m月d日') {
    return date($format, strtotime($date));
}

/**
 * 相対時間表示
 */
function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return '1分未満前';
    if ($time < 3600) return floor($time / 60) . '分前';
    if ($time < 86400) return floor($time / 3600) . '時間前';
    if ($time < 2592000) return floor($time / 86400) . '日前';
    if ($time < 31536000) return floor($time / 2592000) . 'ヶ月前';
    return floor($time / 31536000) . '年前';
}

/**
 * 星評価HTML生成
 */
function renderStars($rating, $maxRating = 5) {
    $rating = $rating ?? 0; // nullの場合は0として扱う
    $html = '';
    for ($i = 1; $i <= $maxRating; $i++) {
        if ($i <= $rating) {
            $html .= '<svg class="h-4 w-4 text-yellow-400 fill-current" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>';
        } else {
            $html .= '<svg class="h-4 w-4 text-gray-300 fill-current" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>';
        }
    }
    return $html;
}

/**
 * ファイルアップロード処理
 */
function uploadFile($file) {
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        throw new Exception('ファイルがアップロードされていません。');
    }
    
    if ($file['size'] > UPLOAD_MAX_SIZE) {
        throw new Exception('ファイルサイズが大きすぎます。');
    }
    
    // MIMEタイプ検証
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, ALLOWED_MIME_TYPES)) {
        throw new Exception('許可されていないファイル形式です。');
    }

    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, ALLOWED_EXTENSIONS)) {
        throw new Exception('許可されていないファイル形式です。');
    }
    
    $uploadDir = UPLOAD_PATH;
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $filename = uniqid() . '.' . $extension;
    $filepath = $uploadDir . '/' . $filename;
    
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        throw new Exception('ファイルの保存に失敗しました。');
    }
    
    return $filename;
}

/**
 * 検索条件構築
 */
function buildSearchConditions($params, $allowedFields) {
    $conditions = [];
    $values = [];
    
    foreach ($params as $key => $value) {
        if (!in_array($key, $allowedFields) || empty($value)) {
            continue;
        }
        
        switch ($key) {
            case 'keyword':
                $conditions[] = "(title LIKE ? OR description LIKE ?)";
                $values[] = "%{$value}%";
                $values[] = "%{$value}%";
                break;
            case 'category_id':
                $conditions[] = "category_id = ?";
                $values[] = $value;
                break;
            case 'price_min':
                $conditions[] = "price_min >= ?";
                $values[] = $value;
                break;
            case 'price_max':
                $conditions[] = "price_max <= ?";
                $values[] = $value;
                break;
            default:
                $conditions[] = "{$key} = ?";
                $values[] = $value;
                break;
        }
    }
    
    return [
        'conditions' => $conditions,
        'values' => $values,
        'where' => !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : ''
    ];
}
?>

