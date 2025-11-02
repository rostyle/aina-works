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
function url($path = '', $absolute = false) {
    // 既に絶対URLの場合はそのまま返す
    if (!empty($path) && (strpos($path, 'http://') === 0 || strpos($path, 'https://') === 0)) {
        return $path;
    }

    // クエリを分離
    $basePath = $path;
    $query = '';
    if (!empty($path) && strpos($path, '?') !== false) {
        [$basePath, $query] = explode('?', $path, 2);
    }

    // 拡張子なしのとき、対応する .php が存在すれば付与
    if (!empty($basePath) && strpos($basePath, '.') === false) {
        $candidate = BASE_PATH . '/' . ltrim($basePath, '/');
        if (file_exists($candidate . '.php')) {
            $basePath .= '.php';
        }
    }

    // 再結合
    $finalPath = ltrim($basePath, '/');
    if ($query !== '') {
        $finalPath .= '?' . $query;
    }

    if ($absolute) {
        $baseUrl = rtrim(BASE_URL, '/');
        if ($finalPath === '') {
            return $baseUrl . '/';
        }
        return $baseUrl . '/' . $finalPath;
    }

    if ($finalPath === '') {
        return './';
    }
    return './' . $finalPath;
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
    if (strpos($path, 'assets/') === 0) {
        return './' . $path;
    }
    
    // アップロードされたファイルは直接アクセス
    // storage/app/uploads/で始まる場合はそのまま使用
    if (strpos($path, 'storage/app/uploads/') === 0) {
        return './' . $path;
    }
    
    // 相対パスの場合はstorage/app/uploads/を追加
    return './storage/app/uploads/' . $path;
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
    if (function_exists('ob_get_level')) {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
    }
    if (!headers_sent()) {
        header('Location: ' . $url, true, 302);
        exit;
    }
    echo '<!DOCTYPE html><html><head><meta http-equiv="refresh" content="0;url=' . h($url) . '"/></head><body><script>location.replace(' . json_encode($url) . ');</script></body></html>';
    exit;
}

/**
 * JSONレスポンス
 */
function jsonResponse($data, $status = 200) {
    // 出力バッファをクリアしてクリーンなレスポンスを確保
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    if (!headers_sent()) {
        http_response_code($status);
        header('Content-Type: application/json');
        header('X-Content-Type-Options: nosniff');
    }
    
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
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
function getCurrentUser($forceRefresh = false) {
    if (!isLoggedIn()) {
        return null;
    }
    
    static $user = null;
    if ($forceRefresh) {
        $user = null;
    }
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
 * 現在のアクティブロール取得（簡素化版）
 */
function getCurrentRole() {
    if (!isLoggedIn()) {
        return null;
    }
    
    // API認証ユーザーはデフォルトで'member'
    return 'member';
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
    
    // セッションのロールを更新（DBカラムは廃止のためセッションのみ）
    $_SESSION['active_role'] = $newRole;

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
            return isUserCreator();
        case 'post_job':
            return isUserClient() || $currentRole === 'sales';
        case 'admin':
            return $currentRole === 'admin';
        default:
            return false;
    }
}

/**
 * ユーザーがクリエイターとして機能するかチェック
 */
function isUserCreator($userId = null) {
    if ($userId === null) {
        if (!isLoggedIn()) {
            return false;
        }
        $userId = $_SESSION['user_id'];
    }
    
    $db = Database::getInstance();
    $result = $db->selectOne(
        "SELECT is_creator FROM users WHERE id = ? AND is_active = 1",
        [$userId]
    );
    
    return $result && $result['is_creator'] == 1;
}

/**
 * ユーザーが依頼者として機能するかチェック
 */
function isUserClient($userId = null) {
    if ($userId === null) {
        if (!isLoggedIn()) {
            return false;
        }
        $userId = $_SESSION['user_id'];
    }
    
    $db = Database::getInstance();
    $result = $db->selectOne(
        "SELECT is_client FROM users WHERE id = ? AND is_active = 1",
        [$userId]
    );
    
    return $result && $result['is_client'] == 1;
}

/**
 * クリエイタープロフィールを表示するかチェック
 */
function shouldShowCreatorProfile($userId = null) {
    return isUserCreator($userId);
}

/**
 * 依頼者プロフィールを表示するかチェック
 */
function shouldShowClientProfile($userId = null) {
    return isUserClient($userId);
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
 * 画像アップロード（アスペクト比維持のリサイズ＋圧縮）
 *
 * @param array $file $_FILES の1要素
 * @param array $options ['max_width' => int, 'max_height' => int, 'quality' => int, 'strict' => bool]
 * @return string 保存したファイル名
 * @throws Exception
 */
function uploadImage($file, $options = []) {
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

    // 画像以外の扱い
    $imageMimes = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($mimeType, $imageMimes, true)) {
        if (!empty($options['strict'])) {
            throw new Exception('画像ファイルのみアップロード可能です。');
        }
        return uploadFile($file);
    }

    // 画像拡張子の厳格チェック（ALLOWED_EXTENSIONS に PDF が含まれていても、ここでは画像のみ許可）
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedImageExtensions = ['jpg', 'jpeg', 'png', 'gif'];
    if (!in_array($extension, $allowedImageExtensions, true)) {
        throw new Exception('画像の拡張子のみ許可されています（jpg, jpeg, png, gif）。');
    }

    // 最大サイズと品質（デフォルト値）
    $maxWidth = (int)($options['max_width'] ?? 1600);
    $maxHeight = (int)($options['max_height'] ?? 1600);
    $jpegQuality = (int)($options['quality'] ?? 82); // 0-100
    $pngCompression = 6; // 0(無圧縮) - 9(最大圧縮)

    // GD拡張が未インストール/未有効の場合は、加工せずそのまま保存
    if (!extension_loaded('gd')) {
        if (function_exists('error_log')) {
            error_log('[uploadImage] GD extension not loaded. Saving original file without processing.');
        }
        return uploadFile($file);
    }

    // 画像読み込み
    switch ($mimeType) {
        case 'image/jpeg':
            if (!function_exists('imagecreatefromjpeg')) { return uploadFile($file); }
            $srcImage = imagecreatefromjpeg($file['tmp_name']);
            break;
        case 'image/png':
            if (!function_exists('imagecreatefrompng')) { return uploadFile($file); }
            $srcImage = imagecreatefrompng($file['tmp_name']);
            break;
        case 'image/gif':
            // GIFはアニメーション破壊の恐れがあるため、リサイズせずそのまま保存
            return uploadFile($file);
        default:
            return uploadFile($file);
    }

    if (!$srcImage) {
        throw new Exception('画像の読み込みに失敗しました。');
    }

    $srcWidth = imagesx($srcImage);
    $srcHeight = imagesy($srcImage);

    // リサイズ不要なら再エンコードのみ行う（圧縮目的）
    $needResize = ($srcWidth > $maxWidth) || ($srcHeight > $maxHeight);

    if ($needResize) {
        // 等比で縮小
        $ratio = min($maxWidth / $srcWidth, $maxHeight / $srcHeight);
        $dstWidth = max(1, (int)floor($srcWidth * $ratio));
        $dstHeight = max(1, (int)floor($srcHeight * $ratio));

        $dstImage = imagecreatetruecolor($dstWidth, $dstHeight);

        // PNG のアルファ保持
        if ($mimeType === 'image/png') {
            imagealphablending($dstImage, false);
            imagesavealpha($dstImage, true);
        }

        imagecopyresampled($dstImage, $srcImage, 0, 0, 0, 0, $dstWidth, $dstHeight, $srcWidth, $srcHeight);
    } else {
        $dstImage = $srcImage;
    }

    // 保存先準備
    $uploadDir = UPLOAD_PATH;
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // 拡張子は元の形式を維持（JPEG/PNG）
    $saveExtension = ($mimeType === 'image/png') ? 'png' : 'jpg';
    $filename = uniqid() . '.' . $saveExtension;
    $filepath = $uploadDir . '/' . $filename;

    // 保存
    $saved = false;
    if ($saveExtension === 'jpg') {
        // JPEGとして保存（PNG供給時はJPEG変換・透過は白背景化）
        if ($mimeType === 'image/png') {
            // 透過を白に変換
            $bg = imagecreatetruecolor(imagesx($dstImage), imagesy($dstImage));
            $white = imagecolorallocate($bg, 255, 255, 255);
            imagefilledrectangle($bg, 0, 0, imagesx($bg), imagesy($bg), $white);
            imagecopy($bg, $dstImage, 0, 0, 0, 0, imagesx($dstImage), imagesy($dstImage));
            $saved = imagejpeg($bg, $filepath, $jpegQuality);
            imagedestroy($bg);
        } else {
            $saved = imagejpeg($dstImage, $filepath, $jpegQuality);
        }
    } else {
        // PNGとして保存（透過保持）
        $saved = imagepng($dstImage, $filepath, $pngCompression);
    }

    if ($dstImage !== $srcImage) {
        imagedestroy($dstImage);
    }
    imagedestroy($srcImage);

    if (!$saved) {
        throw new Exception('画像の保存に失敗しました。');
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

/**
 * 404エラー表示
 */
function show404Error($message = 'ページが見つかりません') {
    http_response_code(404);
    
    // ログに記録
    error_log("404 Error: " . $_SERVER['REQUEST_URI'] . " - " . $message);
    
    // 404ページを表示
    include BASE_PATH . '/404.php';
    exit;
}

/**
 * 403エラー表示
 */
function show403Error($message = 'アクセスが拒否されました') {
    http_response_code(403);
    
    // ログに記録
    error_log("403 Error: " . $_SERVER['REQUEST_URI'] . " - " . $message);
    
    // 404ページを表示（403も同じページで処理）
    include BASE_PATH . '/404.php';
    exit;
}

/**
 * 500エラー表示
 */
function show500Error($message = 'サーバーエラーが発生しました') {
    http_response_code(500);
    
    // ログに記録
    error_log("500 Error: " . $_SERVER['REQUEST_URI'] . " - " . $message);
    
    // 404ページを表示（500も同じページで処理）
    include BASE_PATH . '/404.php';
    exit;
}

/**
 * ページの存在確認
 */
function checkPageExists($pageName) {
    $pageFile = BASE_PATH . '/' . $pageName . '.php';
    return file_exists($pageFile);
}

/**
 * 許可されたページかチェック
 */
function isAllowedPage($pageName) {
    // 許可されたページのリスト
    $allowedPages = [
        'index', 'login', 'dashboard', 'profile', 'works', 'jobs',
        'creators', 'creator-profile', 'job-detail', 'work-detail', 'post-job',
        'edit-job', 'edit-work', 'job-applications', 'favorites', 'chat', 'chats',
        'success-stories', 'terms', 'privacy', 'forgot-password', 'reset-password',
        'logout', 'switch-role', 'serve'
    ];
    
    return in_array($pageName, $allowedPages);
}

/**
 * ページアクセス制御
 */
function validatePageAccess($pageName) {
    // ページが存在しない場合
    if (!checkPageExists($pageName)) {
        show404Error('指定されたページは存在しません');
    }
    
    // 許可されていないページの場合
    if (!isAllowedPage($pageName)) {
        show403Error('このページへのアクセスは許可されていません');
    }
    
    return true;
}

/**
 * メール送信関数（PHPMailer使用）
 */
function sendMail($to, $subject, $body, $isHtml = false) {
    // Composerのautoloaderを読み込み
    require_once BASE_PATH . '/vendor/autoload.php';
    
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    
    try {
        // サーバー設定
        $mail->isSMTP();
        $mail->Host       = 'smtp.lolipop.jp';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'info@aina-works.com';
        $mail->Password   = '_V1E-WLL0eAX1pw_';
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;
        $mail->CharSet    = 'UTF-8';
        
        // 送信者設定
        $mail->setFrom('info@aina-works.com', 'AiNA Works');
        
        // 受信者設定
        if (is_array($to)) {
            foreach ($to as $email => $name) {
                if (is_numeric($email)) {
                    $mail->addAddress($name);
                } else {
                    $mail->addAddress($email, $name);
                }
            }
        } else {
            $mail->addAddress($to);
        }
        
        // メール内容設定
        $mail->isHTML($isHtml);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        
        // HTMLメールの場合はテキスト版も設定
        if ($isHtml) {
            $mail->AltBody = strip_tags($body);
        }
        
        $mail->send();
        return true;
        
    } catch (PHPMailer\PHPMailer\Exception $e) {
        error_log("メール送信エラー: " . $mail->ErrorInfo);
        return false;
    }
}

/**
 * 通知メール送信（共通テンプレート）
 */
function sendNotificationMail($to, $subject, $message, $actionUrl = null, $actionText = null) {
    $body = "
<!DOCTYPE html>
<html>
<head>
    <meta charset=\"UTF-8\">
    <title>{$subject}</title>
    <style>
        body { font-family: 'Hiragino Sans', 'Yu Gothic', 'Meiryo', sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #3B82F6; color: white; padding: 20px; text-align: center; }
        .content { background: #f8f9fa; padding: 30px; }
        .footer { background: #6B7280; color: white; padding: 15px; text-align: center; font-size: 14px; }
        .button { display: inline-block; background: #3B82F6; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
        .message { background: white; padding: 20px; border-radius: 5px; margin: 20px 0; }
        a { color: #3B82F6; text-decoration: underline; }
        .footer a { color: #E5E7EB; text-decoration: underline; }
        .button a { color: white; text-decoration: none; }
    </style>
</head>
<body>
    <div class=\"container\">
        <div class=\"header\">
            <h1>AiNA Works</h1>
        </div>
        <div class=\"content\">
            <div class=\"message\">
                " . nl2br(h($message)) . "
            </div>";
            
    if ($actionUrl && $actionText) {
        // メール内のリンクは絶対URLを使用
        // 既に絶対URLの場合はそのまま使用、そうでなければ絶対URLに変換
        $absoluteActionUrl = (strpos($actionUrl, 'http') === 0) ? $actionUrl : url($actionUrl, true);
        $body .= "
            <div style=\"text-align: center;\">
                <a href=\"{$absoluteActionUrl}\" class=\"button\">{$actionText}</a>
            </div>";
    }
    
    $body .= "
        </div>
        <div class=\"footer\">
            <p>このメールは AiNA Works から自動送信されています。</p>
            <p>運営：株式会社AiNA</p>
            <p><a href=\"" . url('', true) . "\">AiNA Works</a> | <a href=\"" . url('privacy', true) . "\">プライバシーポリシー</a> | <a href=\"" . url('terms', true) . "\">利用規約</a></p>
            <p>お心当たりのない場合は、このメールを破棄してください。</p>
        </div>
    </div>
</body>
</html>";
    
    return sendMail($to, $subject, $body, true);
}

/**
 * AiNA API認証（新API対応）
 */
function authenticateWithAinaApi($email, $password = null) {
    // 新しいAPIエンドポイント: /ainaglam/verify-login
    $apiUrl = AINA_API_BASE_URL . '/ainaglam/verify-login';
    $apiKey = AINA_API_KEY;
    
    if (empty($apiKey)) {
        error_log('AiNA API認証エラー: APIキーが設定されていません');
        return [
            'success' => false, 
            'message' => 'システム設定エラーです。管理者にお問い合わせください。',
            'error_type' => 'config_error'
        ];
    }
    
    $postData = json_encode([
        'email' => $email,
        'password' => $password
    ]);
    
    $response = null;
    $httpStatus = 0;
    
    // cURL を優先使用
    if (function_exists('curl_init')) {
        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'X-API-KEY: ' . $apiKey,
            'Content-Type: application/json',
            'Accept: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        // 環境により自己署名証明書で失敗する場合があるため、検証はデフォルト有効のまま
        // 必要であれば本番以外でのみ無効化するなどの制御を追加
        $response = curl_exec($ch);
        if ($response === false) {
            error_log('AiNA API cURLエラー: ' . curl_error($ch));
        }
        $httpStatus = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
    }
    
    // フォールバック: allow_url_fopenが有効ならfile_get_contents
    if ($response === null && ini_get('allow_url_fopen')) {
        $options = [
            'http' => [
                'method' => 'POST',
                'header' => [
                    'X-API-KEY: ' . $apiKey,
                    'Content-Type: application/json',
                    'Accept: application/json'
                ],
                'content' => $postData,
                'timeout' => 30
            ]
        ];
        $context = stream_context_create($options);
        $response = @file_get_contents($apiUrl, false, $context);
        // HTTPステータスは取得できないケースが多い
    }
    
    if ($response === false || $response === null) {
        $error = error_get_last();
        error_log('AiNA API接続エラー: ' . ($error['message'] ?? 'Unknown error'));
        return [
            'success' => false, 
            'message' => 'AiNA サーバーに接続できませんでした。しばらく時間をおいて再度お試しください。',
            'error_type' => 'connection_error'
        ];
    }
    
    if ($httpStatus >= 400) {
        error_log('AiNA API HTTPエラー: Status ' . $httpStatus . ' Response: ' . $response);
        
        // 401エラーの場合でも、ユーザーには一時的な問題として再試行を促す
        if ($httpStatus === 401) {
            error_log('AiNA API認証エラー(401): 詳細はログを確認してください - Email: ' . $email);
            return [
                'success' => false,
                'message' => 'ログインに失敗しました。メールアドレスとパスワードを確認して、しばらく時間をおいて再度お試しください。',
                'error_type' => 'api_auth_error'
            ];
        }
        
        return [
            'success' => false,
            'message' => 'サーバーエラーが発生しました。時間をおいて再試行してください。',
            'error_type' => 'http_error'
        ];
    }
    
    $data = json_decode($response, true);
    
    if (!$data) {
        error_log('AiNA API応答エラー: JSONデコードに失敗 - ' . $response);
        return [
            'success' => false, 
            'message' => 'サーバーからの応答が正しくありません。管理者にお問い合わせください。',
            'error_type' => 'response_error'
        ];
    }
    
    if (!isset($data['result'])) {
        error_log('AiNA API応答エラー: resultフィールドが存在しません - ' . json_encode($data));
        return [
            'success' => false, 
            'message' => 'サーバーからの応答形式が正しくありません。',
            'error_type' => 'response_format_error'
        ];
    }
    
    if ($data['result'] !== 'success') {
        $errorMessage = $data['message'] ?? 'ログインに失敗しました。';
        error_log('AiNA API認証失敗: ' . $errorMessage);
        
        // エラーメッセージをユーザーフレンドリーに変換
        if (strpos($errorMessage, 'password') !== false || strpos($errorMessage, 'パスワード') !== false) {
            return [
                'success' => false, 
                'message' => 'メールアドレスまたはパスワードが正しくありません。',
                'error_type' => 'auth_failed'
            ];
        } elseif (strpos($errorMessage, 'user not found') !== false || strpos($errorMessage, 'ユーザーが見つかりません') !== false) {
            return [
                'success' => false, 
                'message' => '入力されたメールアドレスは登録されていません。AiNA マイページでアカウントを確認してください。',
                'error_type' => 'user_not_found'
            ];
        } else {
            return [
                'success' => false, 
                'message' => $errorMessage,
                'error_type' => 'auth_failed'
            ];
        }
    }
    
    // 新API: authenticatedフィールドのチェック
    if (!isset($data['authenticated']) || !$data['authenticated']) {
        error_log('AiNA API認証失敗: authenticated=false');
        return [
            'success' => false, 
            'message' => 'メールアドレスまたはパスワードが正しくありません。',
            'error_type' => 'auth_failed'
        ];
    }
    
    // 新API: userオブジェクト（配列ではなく単体オブジェクト）
    if (empty($data['user']) || !is_array($data['user'])) {
        error_log('AiNA API応答エラー: userデータが存在しないか不正です - ' . json_encode($data));
        return [
            'success' => false, 
            'message' => 'ユーザー情報を取得できませんでした。',
            'error_type' => 'user_data_error'
        ];
    }
    
    $user = $data['user'];
    
    // 必要なフィールドの存在確認（新APIはid, email, name, status_id, plan_idを返す）
    $requiredFields = ['id', 'email', 'name'];
    foreach ($requiredFields as $field) {
        if (!isset($user[$field])) {
            error_log('AiNA API応答エラー: 必要なフィールド ' . $field . ' が存在しません - ' . json_encode($user));
            return [
                'success' => false, 
                'message' => 'ユーザー情報が不完全です。管理者にお問い合わせください。',
                'error_type' => 'incomplete_user_data'
            ];
        }
    }
    
    // 新APIのレスポンスにはstatus_id, plan_id, plan, plan_displayなどが含まれる
    // これらは既にAPIレスポンスに含まれているため、デフォルト値の設定は不要
    // ただし、古いAPIレスポンスとの互換性のため、存在しない場合のみデフォルト値を設定
    if (!isset($user['status_id'])) {
        error_log('AiNA API警告: status_idが含まれていません。デフォルト値を使用します。');
        $user['status_id'] = 3; // アクティブ（フォールバック）
    }
    if (!isset($user['plan_id'])) {
        error_log('AiNA API警告: plan_idが含まれていません。デフォルト値を使用します。');
        $user['plan_id'] = 2; // メンバープラン（フォールバック）
    }
    
    // 後方互換性のため、rankフィールドも設定
    if (!isset($user['rank'])) {
        $user['rank'] = $user['plan_display'] ?? 'メンバープラン';
    }
    
    // 認証成功時の詳細ログ
    error_log('AiNA API認証成功: User ID=' . $user['id'] . ', Email=' . $user['email'] . ', Plan ID=' . ($user['plan_id'] ?? 'null') . ', Status ID=' . ($user['status_id'] ?? 'null'));
    
    return ['success' => true, 'user' => $user];
}

/**
 * ユーザー認証の検証
 */
function validateUserAccess($user) {
    // 定数が未定義の場合のフォールバック（本番環境対応）
    if (!defined('ALLOWED_STATUSES')) {
        define('ALLOWED_STATUSES', [3, 4]);
    }
    if (!defined('ALLOWED_PLAN_IDS')) {
        define('ALLOWED_PLAN_IDS', [2, 3, 4, 5, 6, 7, 8, 9, 11, 12, 13, 14, 15]);
        error_log('警告: ALLOWED_PLAN_IDsが未定義でした。デフォルト値を使用しています。config/config.phpを更新してください。');
    }
    
    // ステータス確認（アクティブ会員、エリート会員のみ）
    $userStatus = (int)($user['status_id'] ?? 0);
    if (!in_array($userStatus, ALLOWED_STATUSES, true)) {
        $statusNames = [
            1 => '仮登録',
            2 => '契約済み',
            3 => 'アクティブ会員',
            4 => 'エリート会員',
            5 => '未払い',
            6 => '退会',
            7 => '電子署名完了',
            8 => '支払い待ち',
            9 => 'クーリングオフ'
        ];
        $statusName = $statusNames[$userStatus] ?? '不明';
        
        error_log('ユーザーアクセス拒否: 非アクティブステータス - User ID: ' . ($user['id'] ?? 'unknown') . ', Status ID: ' . $userStatus . ' (' . $statusName . ')');
        return [
            'valid' => false, 
            'message' => 'ご利用いただけないアカウント状態です（' . $statusName . '）。AiNA マイページでアカウント状況を確認してください。',
            'error_type' => 'inactive_account'
        ];
    }
    
    // プランID確認（メンバープラン以上）
    $userPlanId = (int)($user['plan_id'] ?? 0);
    if (!in_array($userPlanId, ALLOWED_PLAN_IDS, true)) {
        $planDisplay = $user['plan_display'] ?? $user['plan'] ?? 'フリープラン';
        
        // 詳細なデバッグログ
        error_log('ユーザーアクセス拒否: 不適切なプラン - User ID: ' . ($user['id'] ?? 'unknown') . ', Plan ID: ' . $userPlanId . ' (' . $planDisplay . ')');
        error_log('許可されているプランID: ' . json_encode(ALLOWED_PLAN_IDS));
        error_log('受信したユーザー情報: ' . json_encode($user));
        
        return [
            'valid' => false, 
            'message' => 'メンバープラン以上の会員のみご利用いただけます。現在のプラン: ' . $planDisplay . '。プランのアップグレードについては AiNA マイページをご確認ください。',
            'error_type' => 'insufficient_plan'
        ];
    }
    
    return ['valid' => true];
}

/**
 * ユーザーの年齢を計算
 */
function calculateAge($birthdate) {
    if (empty($birthdate)) {
        return null;
    }
    
    $birthdateObj = DateTime::createFromFormat('Y-m-d', $birthdate);
    if (!$birthdateObj) {
        return null;
    }
    
    $today = new DateTime();
    return $today->diff($birthdateObj)->y;
}

/**
 * ユーザーが13歳以上かチェック
 */
function isUserAgeValid($userId = null) {
    if ($userId === null) {
        if (!isLoggedIn()) {
            return false;
        }
        $userId = $_SESSION['user_id'];
    }
    
    $db = Database::getInstance();
    $result = $db->selectOne(
        "SELECT birthdate FROM users WHERE id = ? AND is_active = 1",
        [$userId]
    );
    
    if (!$result || empty($result['birthdate'])) {
        return false; // 生年月日が未設定の場合は無効
    }
    
    $age = calculateAge($result['birthdate']);
    return $age !== null && $age >= 13;
}

/**
 * ユーザーの作成または更新（API認証のみ）
 */
function createOrUpdateUser($apiUser) {
    try {
        $db = Database::getInstance();
        
        // 必要なフィールドの確認
        if (empty($apiUser['id']) || empty($apiUser['email']) || empty($apiUser['name'])) {
            error_log('ユーザー作成エラー: 必要なフィールドが不足 - ' . json_encode($apiUser));
            return false;
        }
        
        // 既存ユーザーの確認（aina_user_idまたはemailで）
        $existingUser = $db->selectOne(
            "SELECT id FROM users WHERE aina_user_id = ? OR email = ?",
            [$apiUser['id'], $apiUser['email']]
        );
        
        if ($existingUser) {
            // 既存ユーザーの更新
            $updateSql = "UPDATE users SET 
                email = ?,
                full_name = ?, 
                aina_user_id = ?, 
                password_hash = NULL,
                is_active = 1,
                updated_at = NOW()
                WHERE id = ?";
            
            $affected = $db->update($updateSql, [
                $apiUser['email'],
                $apiUser['name'],
                $apiUser['id'],
                $existingUser['id']
            ]);
            
            if ($affected === 0) {
                error_log('ユーザー更新警告: 更新対象が見つからない - User ID: ' . $existingUser['id']);
            }
            
            $userId = $existingUser['id'];
            error_log('ユーザー更新成功: User ID: ' . $userId . ', AiNA ID: ' . $apiUser['id']);
        } else {
            // 新規ユーザーの作成
            $insertSql = "INSERT INTO users (
                email, 
                full_name, 
                aina_user_id, 
                password_hash,
                is_active,
                created_at,
                updated_at
            ) VALUES (?, ?, ?, NULL, 1, NOW(), NOW())";
            
            $userId = $db->insert($insertSql, [
                $apiUser['email'],
                $apiUser['name'],
                $apiUser['id']
            ]);
            
            if (!$userId) {
                error_log('ユーザー作成エラー: INSERTが失敗 - ' . json_encode($apiUser));
                return false;
            }
            
            error_log('新規ユーザー作成成功: User ID: ' . $userId . ', AiNA ID: ' . $apiUser['id']);
        }
        
        // 管理者ホワイトリスト（.envのADMIN_EMAILS）に該当する場合、adminロールを付与
        try {
            $adminListRaw = $_ENV['ADMIN_EMAILS'] ?? '';
            if ($adminListRaw !== '') {
                $adminList = array_filter(array_map('trim', explode(',', $adminListRaw)));
                if (!empty($adminList) && in_array((string)$apiUser['email'], $adminList, true)) {
                    $exists = $db->selectOne("SELECT id, is_enabled FROM user_roles WHERE user_id = ? AND role = 'admin'", [$userId]);
                    if ($exists) {
                        if ((int)($exists['is_enabled'] ?? 0) !== 1) {
                            $db->update("UPDATE user_roles SET is_enabled = 1 WHERE id = ?", [$exists['id']]);
                        }
                    } else {
                        $db->insert("INSERT INTO user_roles (user_id, role, is_enabled) VALUES (?, 'admin', 1)", [$userId]);
                    }
                }
            }
        } catch (Exception $e) {
            error_log('Admin role ensure error: ' . $e->getMessage());
        }

        return $userId;
    } catch (Exception $e) {
        error_log('ユーザー作成例外エラー: ' . $e->getMessage() . ' - ' . json_encode($apiUser));
        return false;
    }
}

/**
 * APIログイン処理（簡素化版）
 */
function performApiLogin($email, $password) {
    // 入力値の検証
    if (empty($email) || empty($password)) {
        return [
            'success' => false, 
            'message' => 'メールアドレスとパスワードを入力してください。',
            'error_type' => 'validation_error'
        ];
    }
    
    // まずローカルDBでの認証を試行
    $localAuthResult = performLocalLogin($email, $password);
    if ($localAuthResult['success']) {
        return $localAuthResult;
    }
    
    // ローカル認証が失敗した場合、API認証を試行
    $authResult = authenticateWithAinaApi($email, $password);
    if (!$authResult['success']) {
        return [
            'success' => false, 
            'message' => $authResult['message'],
            'error_type' => $authResult['error_type'] ?? 'auth_error'
        ];
    }
    
    $apiUser = $authResult['user'];
    
    // ユーザーアクセス検証
    $validation = validateUserAccess($apiUser);
    if (!$validation['valid']) {
        return [
            'success' => false, 
            'message' => $validation['message'],
            'error_type' => $validation['error_type'] ?? 'access_denied'
        ];
    }
    
    // ユーザー作成/更新
    $userId = createOrUpdateUser($apiUser);
    if (!$userId) {
        error_log('ログイン失敗: ユーザー情報保存エラー - Email: ' . $email . ', AiNA ID: ' . $apiUser['id']);
        return [
            'success' => false, 
            'message' => 'ユーザー情報の保存に失敗しました。しばらく時間をおいて再度お試しください。',
            'error_type' => 'database_error'
        ];
    }
    
    // セッション設定（パスワードは保持しない）
    try {
        $_SESSION['user_id'] = $userId;
        $_SESSION['aina_user_id'] = $apiUser['id'];
        $_SESSION['user_rank'] = $apiUser['rank'] ?? $apiUser['plan_display'] ?? '';
        $_SESSION['user_status'] = $apiUser['status_id'];
        $_SESSION['user_plan_id'] = $apiUser['plan_id'] ?? null;
        $_SESSION['user_plan'] = $apiUser['plan'] ?? null;
        $_SESSION['login_time'] = time();
        
        error_log('ログイン成功: User ID: ' . $userId . ', Email: ' . $email . ', AiNA ID: ' . $apiUser['id'] . ', Plan: ' . ($apiUser['plan_display'] ?? 'unknown') . ', Status: ' . $apiUser['status_id']);
        
        return ['success' => true, 'user_id' => $userId];
    } catch (Exception $e) {
        error_log('セッション設定エラー: ' . $e->getMessage() . ' - User ID: ' . $userId);
        return [
            'success' => false, 
            'message' => 'ログイン処理中にエラーが発生しました。再度お試しください。',
            'error_type' => 'session_error'
        ];
    }
}

/**
 * ローカルDB認証処理
 */
function performLocalLogin($email, $password) {
    try {
        $db = Database::getInstance();
        
        // ローカルDBからユーザー情報を取得
        $user = $db->selectOne(
            "SELECT * FROM users WHERE email = ? AND is_active = 1",
            [$email]
        );
        
        if (!$user) {
            return [
                'success' => false,
                'message' => 'メールアドレスまたはパスワードが正しくありません。',
                'error_type' => 'user_not_found'
            ];
        }
        
        // パスワードハッシュが存在する場合のみ検証
        if (!empty($user['password_hash'])) {
            if (!password_verify($password, $user['password_hash'])) {
                error_log('ローカル認証失敗: パスワード不一致 - Email: ' . $email);
                return [
                    'success' => false,
                    'message' => 'メールアドレスまたはパスワードが正しくありません。',
                    'error_type' => 'password_mismatch'
                ];
            }
        } else {
            // パスワードハッシュが存在しない場合はAPI認証に委ねる
            return [
                'success' => false,
                'message' => 'API認証が必要です。',
                'error_type' => 'no_password_hash'
            ];
        }
        
        // ローカル認証成功後、ADMIN_EMAILS に含まれていれば admin ロールを付与/有効化
        try {
            $adminListRaw = $_ENV['ADMIN_EMAILS'] ?? '';
            if ($adminListRaw !== '') {
                $adminList = array_filter(array_map('trim', explode(',', $adminListRaw)));
                if (!empty($adminList) && in_array((string)$email, $adminList, true)) {
                    $exists = $db->selectOne(
                        "SELECT id, is_enabled FROM user_roles WHERE user_id = ? AND role = 'admin'",
                        [$user['id']]
                    );
                    if ($exists) {
                        if ((int)($exists['is_enabled'] ?? 0) !== 1) {
                            $db->update("UPDATE user_roles SET is_enabled = 1 WHERE id = ?", [$exists['id']]);
                        }
                    } else {
                        $db->insert("INSERT INTO user_roles (user_id, role, is_enabled) VALUES (?, 'admin', 1)", [$user['id']]);
                    }
                }
            }
        } catch (Exception $e) {
            error_log('Admin role ensure (local) error: ' . $e->getMessage());
        }

        // セッション設定
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['aina_user_id'] = $user['aina_user_id'] ?? null;
        $_SESSION['user_rank'] = '';
        $_SESSION['user_status'] = 3; // デフォルトでアクティブ
        $_SESSION['user_plan_id'] = null; // ローカル認証ではプランIDなし
        $_SESSION['user_plan'] = null;
        $_SESSION['login_time'] = time();

        error_log('ローカル認証成功: User ID: ' . $user['id'] . ', Email: ' . $email);
        
        return ['success' => true, 'user_id' => $user['id']];
        
    } catch (Exception $e) {
        error_log('ローカル認証エラー: ' . $e->getMessage());
        return [
            'success' => false,
            'message' => '認証処理中にエラーが発生しました。',
            'error_type' => 'local_auth_error'
        ];
    }
}

/**
 * テキスト内のURLを自動的にハイパーリンクに変換
 */
function autolink($text) {
    // URLパターン（http, https）
    $pattern = '/(https?:\/\/[^\s<>"\']+)/i';
    
    // URLをリンクに変換
    $text = preg_replace_callback($pattern, function($matches) {
        $url = $matches[1];
        // URLの末尾の句読点を除外
        $url = rtrim($url, '.,;:!?');
        return '<a href="' . h($url) . '" target="_blank" rel="noopener noreferrer" class="text-blue-600 hover:text-blue-800 underline">' . h($url) . '</a>';
    }, $text);
    
    return $text;
}

/**
 * Admin URL helper (relative to admin/)
 */
function adminUrl(string $path = ''): string {
    $path = ltrim($path, '/');
    return './' . ($path === '' ? '' : $path);
}

/**
 * Check if current user has admin role (local DB)
 */
function isAdminUser(): bool {
    if (!isLoggedIn()) {
        return false;
    }
    static $cached = null;
    if ($cached !== null) return $cached;
    try {
        $db = Database::getInstance();
        $row = $db->selectOne(
            "SELECT 1 FROM user_roles WHERE user_id = ? AND role = 'admin' AND is_enabled = 1 LIMIT 1",
            [$_SESSION['user_id']]
        );
        $cached = (bool)$row;
        return $cached;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Require admin access (redirect or 403 card in Tailwind)
 */
function requireAdmin(): void {
    if (!isLoggedIn()) {
        redirect('./login.php?redirect=index.php');
    }
    if (!isAdminUser()) {
        http_response_code(403);
        echo '<!DOCTYPE html><html lang="ja"><head><meta charset="utf-8"><title>403 Forbidden</title>';
        echo '<script src="https://cdn.tailwindcss.com"></script></head><body class="min-h-screen bg-gray-50 flex items-center justify-center">';
        echo '<div class="bg-white p-8 rounded-xl shadow-sm border border-gray-200 max-w-lg text-center">';
        echo '<div class="mx-auto mb-4 w-12 h-12 rounded-full bg-red-100 text-red-600 flex items-center justify-center">';
        echo '<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>';
        echo '</div>';
        echo '<h1 class="text-2xl font-bold text-gray-900 mb-2">アクセス権限がありません</h1>';
        echo '<p class="text-gray-600 mb-6">管理者のみがアクセスできます。必要な場合は管理者に権限付与を依頼してください。</p>';
        echo '<div class="flex gap-3 justify-center">';
        echo '<a class="px-4 py-2 rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200" href="' . h(url('')) . '">トップへ</a>';
        echo '<a class="px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700" href="' . h(url('logout')) . '">ログアウト</a>';
        echo '</div></div></body></html>';
        exit;
    }
}

