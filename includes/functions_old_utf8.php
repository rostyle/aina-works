<?php
/**
 * 蜈ｱ騾夐未謨ｰ
 */

/**
 * HTML繧ｨ繧ｹ繧ｱ繝ｼ繝・ */
function h($str) {
    if ($str === null) {
        return '';
    }
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

/**
 * URL逕滓・
 */
function url($path = '', $absolute = false) {
    // 譌｢縺ｫ邨ｶ蟇ｾURL縺ｮ蝣ｴ蜷医・縺昴・縺ｾ縺ｾ霑斐☆
    if (!empty($path) && (strpos($path, 'http://') === 0 || strpos($path, 'https://') === 0)) {
        return $path;
    }

    // 繧ｯ繧ｨ繝ｪ繧貞・髮｢
    $basePath = $path;
    $query = '';
    if (!empty($path) && strpos($path, '?') !== false) {
        [$basePath, $query] = explode('?', $path, 2);
    }

    // 諡｡蠑ｵ蟄舌↑縺励・縺ｨ縺阪∝ｯｾ蠢懊☆繧・.php 縺悟ｭ伜惠縺吶ｌ縺ｰ莉倅ｸ・    if (!empty($basePath) && strpos($basePath, '.') === false) {
        $candidate = BASE_PATH . '/' . ltrim($basePath, '/');
        if (file_exists($candidate . '.php')) {
            $basePath .= '.php';
        }
    }

    // 蜀咲ｵ仙粋
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
 * 繧｢繧ｻ繝・ヨURL逕滓・
 */
function asset($path) {
    return './assets/' . ltrim($path, '/');
}

/**
 * 繧｢繝・・繝ｭ繝ｼ繝峨＆繧後◆繧｢繧ｻ繝・ヨ縺ｮURL逕滓・
 */
function uploaded_asset($path) {
    if (empty($path)) {
        return asset('images/default-avatar.png');
    }
    
    // 螟夜ΚURL縺ｮ蝣ｴ蜷医・縺昴・縺ｾ縺ｾ霑斐☆
    if (strpos($path, 'http') === 0) {
        return $path;
    }
    
    // 繝・ヵ繧ｩ繝ｫ繝医い繧ｻ繝・ヨ縺ｮ蝣ｴ蜷医・assets驟堺ｸ九・繝代せ繧定ｿ斐☆
    if (strpos($path, 'assets/') === 0) {
        return './' . $path;
    }
    
    // 繧｢繝・・繝ｭ繝ｼ繝峨＆繧後◆繝輔ぃ繧､繝ｫ縺ｯ逶ｴ謗･繧｢繧ｯ繧ｻ繧ｹ
    // storage/app/uploads/縺ｧ蟋九∪繧句ｴ蜷医・縺昴・縺ｾ縺ｾ菴ｿ逕ｨ
    if (strpos($path, 'storage/app/uploads/') === 0) {
        return './' . $path;
    }
    
    // 逶ｸ蟇ｾ繝代せ縺ｮ蝣ｴ蜷医・storage/app/uploads/繧定ｿｽ蜉
    return './storage/app/uploads/' . $path;
}

/**
 * 迴ｾ蝨ｨ縺ｮURL蜿門ｾ・ */
function getCurrentUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    return $protocol . '://' . $host . $uri;
}

/**
 * 繝ｪ繝繧､繝ｬ繧ｯ繝・ */
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

if (!function_exists('jsonResponse')) {
    /**
     * JSON繝ｬ繧ｹ繝昴Φ繧ｹ
     */
    function jsonResponse($data, $status = 200) {
        // 蜃ｺ蜉帙ヰ繝・ヵ繧｡繧偵け繝ｪ繧｢縺励※繧ｯ繝ｪ繝ｼ繝ｳ縺ｪ繝ｬ繧ｹ繝昴Φ繧ｹ繧堤｢ｺ菫・        while (ob_get_level()) {
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
}

/**
 * 繝輔Λ繝・す繝･繝｡繝・そ繝ｼ繧ｸ險ｭ螳・ */
function setFlash($type, $message) {
    $_SESSION['flash'][$type] = $message;
}

/**
 * 繝輔Λ繝・す繝･繝｡繝・そ繝ｼ繧ｸ蜿門ｾ・ */
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
 * CSRF繝医・繧ｯ繝ｳ逕滓・
 */
function generateCsrfToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * CSRF繝医・繧ｯ繝ｳ讀懆ｨｼ
 */
function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * 繝ｭ繧ｰ繧､繝ｳ迥ｶ諷狗｢ｺ隱・ */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * 迴ｾ蝨ｨ縺ｮ繝ｦ繝ｼ繧ｶ繝ｼ蜿門ｾ・ */
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
        $user = $result ?: null; // false縺ｮ蝣ｴ蜷医・null縺ｫ螟画鋤
    }
    return $user;
}

/**
 * 繝ｦ繝ｼ繧ｶ繝ｼ縺ｮ蛻ｩ逕ｨ蜿ｯ閭ｽ繝ｭ繝ｼ繝ｫ蜿門ｾ・ */
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
 * 迴ｾ蝨ｨ縺ｮ繧｢繧ｯ繝・ぅ繝悶Ο繝ｼ繝ｫ蜿門ｾ暦ｼ育ｰ｡邏蛹也沿・・ */
function getCurrentRole() {
    if (!isLoggedIn()) {
        return null;
    }
    
    // API隱崎ｨｼ繝ｦ繝ｼ繧ｶ繝ｼ縺ｯ繝・ヵ繧ｩ繝ｫ繝医〒'member'
    return 'member';
}

/**
 * 繝ｭ繝ｼ繝ｫ蛻・ｊ譖ｿ縺・ */
function switchRole($newRole) {
    if (!isLoggedIn()) {
        return false;
    }
    
    $availableRoles = getUserRoles();
    if (!in_array($newRole, $availableRoles)) {
        return false;
    }
    
    // 繧ｻ繝・す繝ｧ繝ｳ縺ｮ繝ｭ繝ｼ繝ｫ繧呈峩譁ｰ・・B繧ｫ繝ｩ繝縺ｯ蟒・ｭ｢縺ｮ縺溘ａ繧ｻ繝・す繝ｧ繝ｳ縺ｮ縺ｿ・・    $_SESSION['active_role'] = $newRole;

    return true;
}

/**
 * 讓ｩ髯舌メ繧ｧ繝・け
 */
function hasPermission($permission) {
    if (!isLoggedIn()) {
        return false;
    }
    
    $currentRole = getCurrentRole();
    if (!$currentRole) return false;
    
    // 蝓ｺ譛ｬ逧・↑讓ｩ髯舌メ繧ｧ繝・け・育樟蝨ｨ縺ｮ繧｢繧ｯ繝・ぅ繝悶Ο繝ｼ繝ｫ縺ｫ蝓ｺ縺･縺擾ｼ・    switch ($permission) {
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
 * 繝ｦ繝ｼ繧ｶ繝ｼ縺後け繝ｪ繧ｨ繧､繧ｿ繝ｼ縺ｨ縺励※讖溯・縺吶ｋ縺九メ繧ｧ繝・け
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
 * 繝ｦ繝ｼ繧ｶ繝ｼ縺御ｾ晞ｼ閠・→縺励※讖溯・縺吶ｋ縺九メ繧ｧ繝・け
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
 * 繧ｯ繝ｪ繧ｨ繧､繧ｿ繝ｼ繝励Ο繝輔ぅ繝ｼ繝ｫ繧定｡ｨ遉ｺ縺吶ｋ縺九メ繧ｧ繝・け
 */
function shouldShowCreatorProfile($userId = null) {
    return isUserCreator($userId);
}

/**
 * 萓晞ｼ閠・・繝ｭ繝輔ぅ繝ｼ繝ｫ繧定｡ｨ遉ｺ縺吶ｋ縺九メ繧ｧ繝・け
 */
function shouldShowClientProfile($userId = null) {
    return isUserClient($userId);
}

/**
 * 繝ｭ繝ｼ繝ｫ蜷阪ｒ譌･譛ｬ隱槭↓螟画鋤
 */
function getRoleDisplayName($role) {
    if ($role === null) {
        return '繧ｲ繧ｹ繝・;
    }
    
    switch ($role) {
        case 'creator':
            return '繧ｯ繝ｪ繧ｨ繧､繧ｿ繝ｼ';
        case 'client':
            return '萓晞ｼ閠・;
        case 'sales':
            return '蝟ｶ讌ｭ';
        default:
            return (string)$role;
    }
}

/**
 * 繝壹・繧ｸ繝阪・繧ｷ繝ｧ繝ｳ險育ｮ・ */
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
 * 萓｡譬ｼ繝輔か繝ｼ繝槭ャ繝・ */
function formatPrice($price) {
    return 'ﾂ･' . number_format($price);
}

/**
 * 譌･莉倥ヵ繧ｩ繝ｼ繝槭ャ繝・ */
function formatDate($date, $format = 'Y蟷ｴm譛・譌･') {
    return date($format, strtotime($date));
}

/**
 * 逶ｸ蟇ｾ譎る俣陦ｨ遉ｺ
 */
function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return '1蛻・悴貅蜑・;
    if ($time < 3600) return floor($time / 60) . '蛻・燕';
    if ($time < 86400) return floor($time / 3600) . '譎る俣蜑・;
    if ($time < 2592000) return floor($time / 86400) . '譌･蜑・;
    if ($time < 31536000) return floor($time / 2592000) . '繝ｶ譛亥燕';
    return floor($time / 31536000) . '蟷ｴ蜑・;
}

/**
 * 譏溯ｩ穂ｾ｡HTML逕滓・
 */
function renderStars($rating, $maxRating = 5) {
    $rating = $rating ?? 0; // null縺ｮ蝣ｴ蜷医・0縺ｨ縺励※謇ｱ縺・    $html = '';
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
 * 繝輔ぃ繧､繝ｫ繧｢繝・・繝ｭ繝ｼ繝牙・逅・ */
function uploadFile($file) {
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        throw new Exception('繝輔ぃ繧､繝ｫ縺後い繝・・繝ｭ繝ｼ繝峨＆繧後※縺・∪縺帙ｓ縲・);
    }
    
    if ($file['size'] > UPLOAD_MAX_SIZE) {
        throw new Exception('繝輔ぃ繧､繝ｫ繧ｵ繧､繧ｺ縺悟､ｧ縺阪☆縺弱∪縺吶・);
    }
    
    // MIME繧ｿ繧､繝玲､懆ｨｼ
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, ALLOWED_MIME_TYPES)) {
        throw new Exception('險ｱ蜿ｯ縺輔ｌ縺ｦ縺・↑縺・ヵ繧｡繧､繝ｫ蠖｢蠑上〒縺吶・);
    }

    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, ALLOWED_EXTENSIONS)) {
        throw new Exception('險ｱ蜿ｯ縺輔ｌ縺ｦ縺・↑縺・ヵ繧｡繧､繝ｫ蠖｢蠑上〒縺吶・);
    }
    
    $uploadDir = UPLOAD_PATH;
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $filename = uniqid() . '.' . $extension;
    $filepath = $uploadDir . '/' . $filename;
    
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        throw new Exception('繝輔ぃ繧､繝ｫ縺ｮ菫晏ｭ倥↓螟ｱ謨励＠縺ｾ縺励◆縲・);
    }
    
    return $filename;
}

/**
 * 逕ｻ蜒上い繝・・繝ｭ繝ｼ繝会ｼ医い繧ｹ繝壹け繝域ｯ皮ｶｭ謖√・繝ｪ繧ｵ繧､繧ｺ・句悸邵ｮ・・ *
 * @param array $file $_FILES 縺ｮ1隕∫ｴ
 * @param array $options ['max_width' => int, 'max_height' => int, 'quality' => int, 'strict' => bool]
 * @return string 菫晏ｭ倥＠縺溘ヵ繧｡繧､繝ｫ蜷・ * @throws Exception
 */
function uploadImage($file, $options = []) {
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        throw new Exception('繝輔ぃ繧､繝ｫ縺後い繝・・繝ｭ繝ｼ繝峨＆繧後※縺・∪縺帙ｓ縲・);
    }

    if ($file['size'] > UPLOAD_MAX_SIZE) {
        throw new Exception('繝輔ぃ繧､繝ｫ繧ｵ繧､繧ｺ縺悟､ｧ縺阪☆縺弱∪縺吶・);
    }

    // MIME繧ｿ繧､繝玲､懆ｨｼ
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    // 逕ｻ蜒丈ｻ･螟悶・謇ｱ縺・    $imageMimes = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($mimeType, $imageMimes, true)) {
        if (!empty($options['strict'])) {
            throw new Exception('逕ｻ蜒上ヵ繧｡繧､繝ｫ縺ｮ縺ｿ繧｢繝・・繝ｭ繝ｼ繝牙庄閭ｽ縺ｧ縺吶・);
        }
        return uploadFile($file);
    }

    // 逕ｻ蜒乗僑蠑ｵ蟄舌・蜴ｳ譬ｼ繝√ぉ繝・け・・LLOWED_EXTENSIONS 縺ｫ PDF 縺悟性縺ｾ繧後※縺・※繧ゅ√％縺薙〒縺ｯ逕ｻ蜒上・縺ｿ險ｱ蜿ｯ・・    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedImageExtensions = ['jpg', 'jpeg', 'png', 'gif'];
    if (!in_array($extension, $allowedImageExtensions, true)) {
        throw new Exception('逕ｻ蜒上・諡｡蠑ｵ蟄舌・縺ｿ險ｱ蜿ｯ縺輔ｌ縺ｦ縺・∪縺呻ｼ・pg, jpeg, png, gif・峨・);
    }

    // 譛螟ｧ繧ｵ繧､繧ｺ縺ｨ蜩∬ｳｪ・医ョ繝輔か繝ｫ繝亥､・・    $maxWidth = (int)($options['max_width'] ?? 1600);
    $maxHeight = (int)($options['max_height'] ?? 1600);
    $jpegQuality = (int)($options['quality'] ?? 82); // 0-100
    $pngCompression = 6; // 0(辟｡蝨ｧ邵ｮ) - 9(譛螟ｧ蝨ｧ邵ｮ)

    // GD諡｡蠑ｵ縺梧悴繧､繝ｳ繧ｹ繝医・繝ｫ/譛ｪ譛牙柑縺ｮ蝣ｴ蜷医・縲∝刈蟾･縺帙★縺昴・縺ｾ縺ｾ菫晏ｭ・    if (!extension_loaded('gd')) {
        if (function_exists('error_log')) {
            error_log('[uploadImage] GD extension not loaded. Saving original file without processing.');
        }
        return uploadFile($file);
    }

    // 逕ｻ蜒剰ｪｭ縺ｿ霎ｼ縺ｿ
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
            // GIF縺ｯ繧｢繝九Γ繝ｼ繧ｷ繝ｧ繝ｳ遐ｴ螢翫・諱舌ｌ縺後≠繧九◆繧√√Μ繧ｵ繧､繧ｺ縺帙★縺昴・縺ｾ縺ｾ菫晏ｭ・            return uploadFile($file);
        default:
            return uploadFile($file);
    }

    if (!$srcImage) {
        throw new Exception('逕ｻ蜒上・隱ｭ縺ｿ霎ｼ縺ｿ縺ｫ螟ｱ謨励＠縺ｾ縺励◆縲・);
    }

    $srcWidth = imagesx($srcImage);
    $srcHeight = imagesy($srcImage);

    // 繝ｪ繧ｵ繧､繧ｺ荳崎ｦ√↑繧牙・繧ｨ繝ｳ繧ｳ繝ｼ繝峨・縺ｿ陦後≧・亥悸邵ｮ逶ｮ逧・ｼ・    $needResize = ($srcWidth > $maxWidth) || ($srcHeight > $maxHeight);

    if ($needResize) {
        // 遲画ｯ斐〒邵ｮ蟆・        $ratio = min($maxWidth / $srcWidth, $maxHeight / $srcHeight);
        $dstWidth = max(1, (int)floor($srcWidth * $ratio));
        $dstHeight = max(1, (int)floor($srcHeight * $ratio));

        $dstImage = imagecreatetruecolor($dstWidth, $dstHeight);

        // PNG 縺ｮ繧｢繝ｫ繝輔ぃ菫晄戟
        if ($mimeType === 'image/png') {
            imagealphablending($dstImage, false);
            imagesavealpha($dstImage, true);
        }

        imagecopyresampled($dstImage, $srcImage, 0, 0, 0, 0, $dstWidth, $dstHeight, $srcWidth, $srcHeight);
    } else {
        $dstImage = $srcImage;
    }

    // 菫晏ｭ伜・貅門ｙ
    $uploadDir = UPLOAD_PATH;
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // 諡｡蠑ｵ蟄舌・蜈・・蠖｢蠑上ｒ邯ｭ謖・ｼ・PEG/PNG・・    $saveExtension = ($mimeType === 'image/png') ? 'png' : 'jpg';
    $filename = uniqid() . '.' . $saveExtension;
    $filepath = $uploadDir . '/' . $filename;

    // 菫晏ｭ・    $saved = false;
    if ($saveExtension === 'jpg') {
        // JPEG縺ｨ縺励※菫晏ｭ假ｼ・NG萓帷ｵｦ譎ゅ・JPEG螟画鋤繝ｻ騾城℃縺ｯ逋ｽ閭梧勹蛹厄ｼ・        if ($mimeType === 'image/png') {
            // 騾城℃繧堤區縺ｫ螟画鋤
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
        // PNG縺ｨ縺励※菫晏ｭ假ｼ磯城℃菫晄戟・・        $saved = imagepng($dstImage, $filepath, $pngCompression);
    }

    if ($dstImage !== $srcImage) {
        imagedestroy($dstImage);
    }
    imagedestroy($srcImage);

    if (!$saved) {
        throw new Exception('逕ｻ蜒上・菫晏ｭ倥↓螟ｱ謨励＠縺ｾ縺励◆縲・);
    }

    return $filename;
}

/**
 * 讀懃ｴ｢譚｡莉ｶ讒狗ｯ・ */
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
 * 404繧ｨ繝ｩ繝ｼ陦ｨ遉ｺ
 */
function show404Error($message = '繝壹・繧ｸ縺瑚ｦ九▽縺九ｊ縺ｾ縺帙ｓ') {
    http_response_code(404);
    
    // 繝ｭ繧ｰ縺ｫ險倬鹸
    error_log("404 Error: " . $_SERVER['REQUEST_URI'] . " - " . $message);
    
    // 404繝壹・繧ｸ繧定｡ｨ遉ｺ
    include BASE_PATH . '/404.php';
    exit;
}

/**
 * 403繧ｨ繝ｩ繝ｼ陦ｨ遉ｺ
 */
function show403Error($message = '繧｢繧ｯ繧ｻ繧ｹ縺梧拠蜷ｦ縺輔ｌ縺ｾ縺励◆') {
    http_response_code(403);
    
    // 繝ｭ繧ｰ縺ｫ險倬鹸
    error_log("403 Error: " . $_SERVER['REQUEST_URI'] . " - " . $message);
    
    // 404繝壹・繧ｸ繧定｡ｨ遉ｺ・・03繧ょ酔縺倥・繝ｼ繧ｸ縺ｧ蜃ｦ逅・ｼ・    include BASE_PATH . '/404.php';
    exit;
}

/**
 * 500繧ｨ繝ｩ繝ｼ陦ｨ遉ｺ
 */
function show500Error($message = '繧ｵ繝ｼ繝舌・繧ｨ繝ｩ繝ｼ縺檎匱逕溘＠縺ｾ縺励◆') {
    http_response_code(500);
    
    // 繝ｭ繧ｰ縺ｫ險倬鹸
    error_log("500 Error: " . $_SERVER['REQUEST_URI'] . " - " . $message);
    
    // 404繝壹・繧ｸ繧定｡ｨ遉ｺ・・00繧ょ酔縺倥・繝ｼ繧ｸ縺ｧ蜃ｦ逅・ｼ・    include BASE_PATH . '/404.php';
    exit;
}

/**
 * 繝壹・繧ｸ縺ｮ蟄伜惠遒ｺ隱・ */
function checkPageExists($pageName) {
    $pageFile = BASE_PATH . '/' . $pageName . '.php';
    return file_exists($pageFile);
}

/**
 * 險ｱ蜿ｯ縺輔ｌ縺溘・繝ｼ繧ｸ縺九メ繧ｧ繝・け
 */
function isAllowedPage($pageName) {
    // 險ｱ蜿ｯ縺輔ｌ縺溘・繝ｼ繧ｸ縺ｮ繝ｪ繧ｹ繝・    $allowedPages = [
        'index', 'login', 'dashboard', 'profile', 'works', 'jobs',
        'creators', 'creator-profile', 'job-detail', 'work-detail', 'post-job',
        'edit-job', 'edit-work', 'job-applications', 'favorites', 'chat', 'chats',
        'success-stories', 'terms', 'privacy', 'forgot-password', 'reset-password',
        'logout', 'switch-role', 'serve'
    ];
    
    return in_array($pageName, $allowedPages);
}

/**
 * 繝壹・繧ｸ繧｢繧ｯ繧ｻ繧ｹ蛻ｶ蠕｡
 */
function validatePageAccess($pageName) {
    // 繝壹・繧ｸ縺悟ｭ伜惠縺励↑縺・ｴ蜷・    if (!checkPageExists($pageName)) {
        show404Error('謖・ｮ壹＆繧後◆繝壹・繧ｸ縺ｯ蟄伜惠縺励∪縺帙ｓ');
    }
    
    // 險ｱ蜿ｯ縺輔ｌ縺ｦ縺・↑縺・・繝ｼ繧ｸ縺ｮ蝣ｴ蜷・    if (!isAllowedPage($pageName)) {
        show403Error('縺薙・繝壹・繧ｸ縺ｸ縺ｮ繧｢繧ｯ繧ｻ繧ｹ縺ｯ險ｱ蜿ｯ縺輔ｌ縺ｦ縺・∪縺帙ｓ');
    }
    
    return true;
}

/**
 * 繝｡繝ｼ繝ｫ騾∽ｿ｡髢｢謨ｰ・・HPMailer菴ｿ逕ｨ・・ */
function sendMail($to, $subject, $body, $isHtml = false) {
    // Composer縺ｮautoloader繧定ｪｭ縺ｿ霎ｼ縺ｿ
    require_once BASE_PATH . '/vendor/autoload.php';
    
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    
    try {
        // 繧ｵ繝ｼ繝舌・險ｭ螳・        $mail->isSMTP();
        $mail->Host       = 'smtp.lolipop.jp';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'info@aina-works.com';
        $mail->Password   = '_V1E-WLL0eAX1pw_';
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;
        $mail->CharSet    = 'UTF-8';
        
        // 騾∽ｿ｡閠・ｨｭ螳・        $mail->setFrom('info@aina-works.com', 'AiNA Works');
        
        // 蜿嶺ｿ｡閠・ｨｭ螳・        if (is_array($to)) {
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
        
        // 繝｡繝ｼ繝ｫ蜀・ｮｹ險ｭ螳・        $mail->isHTML($isHtml);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        
        // HTML繝｡繝ｼ繝ｫ縺ｮ蝣ｴ蜷医・繝・く繧ｹ繝育沿繧りｨｭ螳・        if ($isHtml) {
            $mail->AltBody = strip_tags($body);
        }
        
        $mail->send();
        return true;
        
    } catch (PHPMailer\PHPMailer\Exception $e) {
        error_log("繝｡繝ｼ繝ｫ騾∽ｿ｡繧ｨ繝ｩ繝ｼ: " . $mail->ErrorInfo);
        return false;
    }
}

/**
 * 騾夂衍繝｡繝ｼ繝ｫ騾∽ｿ｡・亥・騾壹ユ繝ｳ繝励Ξ繝ｼ繝茨ｼ・ */
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
        // 繝｡繝ｼ繝ｫ蜀・・繝ｪ繝ｳ繧ｯ縺ｯ邨ｶ蟇ｾURL繧剃ｽｿ逕ｨ
        // 譌｢縺ｫ邨ｶ蟇ｾURL縺ｮ蝣ｴ蜷医・縺昴・縺ｾ縺ｾ菴ｿ逕ｨ縲√◎縺・〒縺ｪ縺代ｌ縺ｰ邨ｶ蟇ｾURL縺ｫ螟画鋤
        $absoluteActionUrl = (strpos($actionUrl, 'http') === 0) ? $actionUrl : url($actionUrl, true);
        $body .= "
            <div style=\"text-align: center;\">
                <a href=\"{$absoluteActionUrl}\" class=\"button\">{$actionText}</a>
            </div>";
    }
    
    $body .= "
        </div>
        <div class=\"footer\">
            <p>縺薙・繝｡繝ｼ繝ｫ縺ｯ AiNA Works 縺九ｉ閾ｪ蜍暮∽ｿ｡縺輔ｌ縺ｦ縺・∪縺吶・/p>
            <p>驕句霧・壽ｪ蠑丈ｼ夂､ｾAiNA</p>
            <p><a href=\"" . url('', true) . "\">AiNA Works</a> | <a href=\"" . url('privacy', true) . "\">繝励Λ繧､繝舌す繝ｼ繝昴Μ繧ｷ繝ｼ</a> | <a href=\"" . url('terms', true) . "\">蛻ｩ逕ｨ隕冗ｴ・/a></p>
            <p>縺雁ｿ・ｽ薙◆繧翫・縺ｪ縺・ｴ蜷医・縲√％縺ｮ繝｡繝ｼ繝ｫ繧堤ｴ譽・＠縺ｦ縺上□縺輔＞縲・/p>
        </div>
    </div>
</body>
</html>";
    
    return sendMail($to, $subject, $body, true);
}

/**
 * AiNA API隱崎ｨｼ・域眠API蟇ｾ蠢懶ｼ・ */
function authenticateWithAinaApi($email, $password = null) {
    // 蜈･蜉帛､縺ｮ繝医Μ繝溘Φ繧ｰ・亥燕蠕檎ｩｺ逋ｽ繝ｻ謾ｹ陦後ｒ蜑企勁・・    $email = trim($email);
    if ($password !== null) {
        $password = trim($password);
    }
    
    // 譁ｰ縺励＞API繧ｨ繝ｳ繝峨・繧､繝ｳ繝・ /ainaglam/verify-login
    $apiUrl = AINA_API_BASE_URL . '/ainaglam/verify-login';
    $apiKey = AINA_API_KEY;
    
    if (empty($apiKey)) {
        error_log('AiNA API隱崎ｨｼ繧ｨ繝ｩ繝ｼ: API繧ｭ繝ｼ縺瑚ｨｭ螳壹＆繧後※縺・∪縺帙ｓ');
        return [
            'success' => false, 
            'message' => '繧ｷ繧ｹ繝・Β險ｭ螳壹お繝ｩ繝ｼ縺ｧ縺吶らｮ｡逅・・↓縺雁撫縺・粋繧上○縺上□縺輔＞縲・,
            'error_type' => 'config_error'
        ];
    }
    
    $postData = json_encode([
        'email' => $email,
        'password' => $password
    ]);
    
    // 繝・ヰ繝・げ繝ｭ繧ｰ・医Γ繝ｼ繝ｫ繧｢繝峨Ξ繧ｹ縺ｨ繝代せ繝ｯ繝ｼ繝峨・髟ｷ縺輔・縺ｿ險倬鹸・・    error_log('AiNA API隱崎ｨｼ髢句ｧ・ Email=' . $email . ', Email髟ｷ=' . strlen($email) . ', Password髟ｷ=' . strlen($password));
    
    $response = null;
    $httpStatus = 0;
    
    // cURL 繧貞━蜈井ｽｿ逕ｨ
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
        // 迺ｰ蠅・↓繧医ｊ閾ｪ蟾ｱ鄂ｲ蜷崎ｨｼ譏取嶌縺ｧ螟ｱ謨励☆繧句ｴ蜷医′縺ゅｋ縺溘ａ縲∵､懆ｨｼ縺ｯ繝・ヵ繧ｩ繝ｫ繝域怏蜉ｹ縺ｮ縺ｾ縺ｾ
        // 蠢・ｦ√〒縺ゅｌ縺ｰ譛ｬ逡ｪ莉･螟悶〒縺ｮ縺ｿ辟｡蜉ｹ蛹悶☆繧九↑縺ｩ縺ｮ蛻ｶ蠕｡繧定ｿｽ蜉
        $response = curl_exec($ch);
        if ($response === false) {
            error_log('AiNA API cURL繧ｨ繝ｩ繝ｼ: ' . curl_error($ch));
        }
        $httpStatus = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
    }
    
    // 繝輔か繝ｼ繝ｫ繝舌ャ繧ｯ: allow_url_fopen縺梧怏蜉ｹ縺ｪ繧映ile_get_contents
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
        // HTTP繧ｹ繝・・繧ｿ繧ｹ縺ｯ蜿門ｾ励〒縺阪↑縺・こ繝ｼ繧ｹ縺悟､壹＞
    }
    
    if ($response === false || $response === null) {
        $error = error_get_last();
        error_log('AiNA API謗･邯壹お繝ｩ繝ｼ: ' . ($error['message'] ?? 'Unknown error'));
        return [
            'success' => false, 
            'message' => 'AiNA 繧ｵ繝ｼ繝舌・縺ｫ謗･邯壹〒縺阪∪縺帙ｓ縺ｧ縺励◆縲ゅ＠縺ｰ繧峨￥譎る俣繧偵♀縺・※蜀榊ｺｦ縺願ｩｦ縺励￥縺縺輔＞縲・,
            'error_type' => 'connection_error'
        ];
    }
    
    if ($httpStatus >= 400) {
        error_log('AiNA API HTTP繧ｨ繝ｩ繝ｼ: Status ' . $httpStatus . ' Response: ' . $response);
        
        // 401繧ｨ繝ｩ繝ｼ縺ｮ蝣ｴ蜷医〒繧ゅ√Θ繝ｼ繧ｶ繝ｼ縺ｫ縺ｯ荳譎ら噪縺ｪ蝠城｡後→縺励※蜀崎ｩｦ陦後ｒ菫・☆
        if ($httpStatus === 401) {
            error_log('AiNA API隱崎ｨｼ繧ｨ繝ｩ繝ｼ(401): 隧ｳ邏ｰ縺ｯ繝ｭ繧ｰ繧堤｢ｺ隱阪＠縺ｦ縺上□縺輔＞ - Email: ' . $email);
            return [
                'success' => false,
                'message' => '繝ｭ繧ｰ繧､繝ｳ縺ｫ螟ｱ謨励＠縺ｾ縺励◆縲ゅΓ繝ｼ繝ｫ繧｢繝峨Ξ繧ｹ縺ｨ繝代せ繝ｯ繝ｼ繝峨ｒ遒ｺ隱阪＠縺ｦ縲√＠縺ｰ繧峨￥譎る俣繧偵♀縺・※蜀榊ｺｦ縺願ｩｦ縺励￥縺縺輔＞縲・,
                'error_type' => 'api_auth_error'
            ];
        }
        
        return [
            'success' => false,
            'message' => '繧ｵ繝ｼ繝舌・繧ｨ繝ｩ繝ｼ縺檎匱逕溘＠縺ｾ縺励◆縲よ凾髢薙ｒ縺翫＞縺ｦ蜀崎ｩｦ陦後＠縺ｦ縺上□縺輔＞縲・,
            'error_type' => 'http_error'
        ];
    }
    
    $data = json_decode($response, true);
    
    if (!$data) {
        error_log('AiNA API蠢懃ｭ斐お繝ｩ繝ｼ: JSON繝・さ繝ｼ繝峨↓螟ｱ謨・- ' . $response);
        return [
            'success' => false, 
            'message' => '繧ｵ繝ｼ繝舌・縺九ｉ縺ｮ蠢懃ｭ斐′豁｣縺励￥縺ゅｊ縺ｾ縺帙ｓ縲らｮ｡逅・・↓縺雁撫縺・粋繧上○縺上□縺輔＞縲・,
            'error_type' => 'response_error'
        ];
    }
    
    if (!isset($data['result'])) {
        error_log('AiNA API蠢懃ｭ斐お繝ｩ繝ｼ: result繝輔ぅ繝ｼ繝ｫ繝峨′蟄伜惠縺励∪縺帙ｓ - ' . json_encode($data));
        return [
            'success' => false, 
            'message' => '繧ｵ繝ｼ繝舌・縺九ｉ縺ｮ蠢懃ｭ泌ｽ｢蠑上′豁｣縺励￥縺ゅｊ縺ｾ縺帙ｓ縲・,
            'error_type' => 'response_format_error'
        ];
    }
    
    if ($data['result'] !== 'success') {
        $errorMessage = $data['message'] ?? '繝ｭ繧ｰ繧､繝ｳ縺ｫ螟ｱ謨励＠縺ｾ縺励◆縲・;
        error_log('AiNA API隱崎ｨｼ螟ｱ謨・ ' . $errorMessage);
        
        // 繧ｨ繝ｩ繝ｼ繝｡繝・そ繝ｼ繧ｸ繧偵Θ繝ｼ繧ｶ繝ｼ繝輔Ξ繝ｳ繝峨Μ繝ｼ縺ｫ螟画鋤
        if (strpos($errorMessage, 'password') !== false || strpos($errorMessage, '繝代せ繝ｯ繝ｼ繝・) !== false) {
            return [
                'success' => false, 
                'message' => '繝｡繝ｼ繝ｫ繧｢繝峨Ξ繧ｹ縺ｾ縺溘・繝代せ繝ｯ繝ｼ繝峨′豁｣縺励￥縺ゅｊ縺ｾ縺帙ｓ縲・,
                'error_type' => 'auth_failed'
            ];
        } elseif (strpos($errorMessage, 'user not found') !== false || strpos($errorMessage, '繝ｦ繝ｼ繧ｶ繝ｼ縺瑚ｦ九▽縺九ｊ縺ｾ縺帙ｓ') !== false) {
            return [
                'success' => false, 
                'message' => '蜈･蜉帙＆繧後◆繝｡繝ｼ繝ｫ繧｢繝峨Ξ繧ｹ縺ｯ逋ｻ骭ｲ縺輔ｌ縺ｦ縺・∪縺帙ｓ縲・iNA 繝槭う繝壹・繧ｸ縺ｧ繧｢繧ｫ繧ｦ繝ｳ繝医ｒ遒ｺ隱阪＠縺ｦ縺上□縺輔＞縲・,
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
    
    // 譁ｰAPI: authenticated繝輔ぅ繝ｼ繝ｫ繝峨・繝√ぉ繝・け
    if (!isset($data['authenticated']) || !$data['authenticated']) {
        error_log('AiNA API隱崎ｨｼ螟ｱ謨・ authenticated=false');
        return [
            'success' => false, 
            'message' => '繝｡繝ｼ繝ｫ繧｢繝峨Ξ繧ｹ縺ｾ縺溘・繝代せ繝ｯ繝ｼ繝峨′豁｣縺励￥縺ゅｊ縺ｾ縺帙ｓ縲・,
            'error_type' => 'auth_failed'
        ];
    }
    
    // 譁ｰAPI: user繧ｪ繝悶ず繧ｧ繧ｯ繝茨ｼ磯・蛻励〒縺ｯ縺ｪ縺丞腰菴薙が繝悶ず繧ｧ繧ｯ繝茨ｼ・    if (empty($data['user']) || !is_array($data['user'])) {
        error_log('AiNA API蠢懃ｭ斐お繝ｩ繝ｼ: user繝・・繧ｿ縺悟ｭ伜惠縺励↑縺・°荳肴ｭ｣縺ｧ縺・- ' . json_encode($data));
        return [
            'success' => false, 
            'message' => '繝ｦ繝ｼ繧ｶ繝ｼ諠・ｱ繧貞叙蠕励〒縺阪∪縺帙ｓ縺ｧ縺励◆縲・,
            'error_type' => 'user_data_error'
        ];
    }
    
    $user = $data['user'];
    
    // 繝・ヰ繝・げ: API繝ｬ繧ｹ繝昴Φ繧ｹ蜈ｨ菴薙ｒ繝ｭ繧ｰ縺ｫ險倬鹸・・ame繝輔ぅ繝ｼ繝ｫ繝峨・譛臥┌繧堤｢ｺ隱搾ｼ・    error_log('AiNA API繝ｬ繧ｹ繝昴Φ繧ｹ - user繝・・繧ｿ: ' . json_encode($user, JSON_UNESCAPED_UNICODE));
    
    // 蠢・ｦ√↑繝輔ぅ繝ｼ繝ｫ繝峨・蟄伜惠遒ｺ隱搾ｼ域眠API縺ｯid, email, name, status_id, plan_id繧定ｿ斐☆・・    $requiredFields = ['id', 'email'];
    foreach ($requiredFields as $field) {
        if (!isset($user[$field])) {
            error_log('AiNA API蠢懃ｭ斐お繝ｩ繝ｼ: 蠢・ｦ√↑繝輔ぅ繝ｼ繝ｫ繝・' . $field . ' 縺悟ｭ伜惠縺励∪縺帙ｓ - ' . json_encode($user));
            return [
                'success' => false, 
                'message' => '繝ｦ繝ｼ繧ｶ繝ｼ諠・ｱ縺御ｸ榊ｮ悟・縺ｧ縺吶らｮ｡逅・・↓縺雁撫縺・粋繧上○縺上□縺輔＞縲・,
                'error_type' => 'incomplete_user_data'
            ];
        }
    }
    
    // name繝輔ぅ繝ｼ繝ｫ繝峨′蟄伜惠縺励↑縺・√∪縺溘・遨ｺ縺ｮ蝣ｴ蜷医・譌｢蟄倥・full_name縺ｾ縺溘・繝輔か繝ｼ繝ｫ繝舌ャ繧ｯ蛟､繧剃ｽｿ逕ｨ
    if (empty($user['name'])) {
        // 譌｢蟄倥Θ繝ｼ繧ｶ繝ｼ縺ｮfull_name繧貞叙蠕・        try {
            $db = Database::getInstance();
            $existingUser = $db->selectOne(
                "SELECT full_name FROM users WHERE aina_user_id = ? OR email = ?",
                [$user['id'], $user['email']]
            );
            
            if (!empty($existingUser['full_name'])) {
                $user['name'] = $existingUser['full_name'];
                error_log('AiNA API隴ｦ蜻・ name繝輔ぅ繝ｼ繝ｫ繝峨′遨ｺ縺ｮ縺溘ａ縲∵里蟄倥・full_name "' . $user['name'] . '" 繧剃ｽｿ逕ｨ縺励∪縺吶・);
            } else {
                // 繝輔か繝ｼ繝ｫ繝舌ャ繧ｯ: 繝｡繝ｼ繝ｫ繧｢繝峨Ξ繧ｹ縺ｮ@繧医ｊ蜑阪・驛ｨ蛻・ｒ菴ｿ逕ｨ
                $emailParts = explode('@', $user['email']);
                $user['name'] = $emailParts[0] ?? '繝ｦ繝ｼ繧ｶ繝ｼ';
                error_log('AiNA API隴ｦ蜻・ name繝輔ぅ繝ｼ繝ｫ繝峨′遨ｺ縺ｮ縺溘ａ縲√ヵ繧ｩ繝ｼ繝ｫ繝舌ャ繧ｯ蛟､ "' . $user['name'] . '" 繧剃ｽｿ逕ｨ縺励∪縺吶・);
            }
        } catch (Exception $e) {
            // 繝・・繧ｿ繝吶・繧ｹ繧ｨ繝ｩ繝ｼ縺ｮ蝣ｴ蜷医ｂ繝輔か繝ｼ繝ｫ繝舌ャ繧ｯ蛟､繧剃ｽｿ逕ｨ
            $emailParts = explode('@', $user['email']);
            $user['name'] = $emailParts[0] ?? '繝ｦ繝ｼ繧ｶ繝ｼ';
            error_log('AiNA API隴ｦ蜻・ name繝輔ぅ繝ｼ繝ｫ繝峨′遨ｺ縺ｧ縲．B蜿門ｾ励お繝ｩ繝ｼ - 繝輔か繝ｼ繝ｫ繝舌ャ繧ｯ蛟､ "' . $user['name'] . '" 繧剃ｽｿ逕ｨ縺励∪縺吶・);
        }
    }
    
    // 譁ｰAPI縺ｮ繝ｬ繧ｹ繝昴Φ繧ｹ縺ｫ縺ｯstatus_id, plan_id, plan, plan_display縺ｪ縺ｩ縺悟性縺ｾ繧後ｋ
    // 縺薙ｌ繧峨・譌｢縺ｫAPI繝ｬ繧ｹ繝昴Φ繧ｹ縺ｫ蜷ｫ縺ｾ繧後※縺・ｋ縺溘ａ縲√ョ繝輔か繝ｫ繝亥､縺ｮ險ｭ螳壹・荳崎ｦ・    // 縺溘□縺励∝商縺БPI繝ｬ繧ｹ繝昴Φ繧ｹ縺ｨ縺ｮ莠呈鋤諤ｧ縺ｮ縺溘ａ縲∝ｭ伜惠縺励↑縺・ｴ蜷医・縺ｿ繝・ヵ繧ｩ繝ｫ繝亥､繧定ｨｭ螳・    if (!isset($user['status_id'])) {
        error_log('AiNA API隴ｦ蜻・ status_id縺悟性縺ｾ繧後※縺・∪縺帙ｓ縲ゅョ繝輔か繝ｫ繝亥､繧剃ｽｿ逕ｨ縺励∪縺吶・);
        $user['status_id'] = 3; // 繧｢繧ｯ繝・ぅ繝厄ｼ医ヵ繧ｩ繝ｼ繝ｫ繝舌ャ繧ｯ・・    }
    if (!isset($user['plan_id'])) {
        error_log('AiNA API隴ｦ蜻・ plan_id縺悟性縺ｾ繧後※縺・∪縺帙ｓ縲ゅョ繝輔か繝ｫ繝亥､繧剃ｽｿ逕ｨ縺励∪縺吶・);
        $user['plan_id'] = 2; // 繝｡繝ｳ繝舌・繝励Λ繝ｳ・医ヵ繧ｩ繝ｼ繝ｫ繝舌ャ繧ｯ・・    }
    
    // 蠕梧婿莠呈鋤諤ｧ縺ｮ縺溘ａ縲〉ank繝輔ぅ繝ｼ繝ｫ繝峨ｂ險ｭ螳・    if (!isset($user['rank'])) {
        $user['rank'] = $user['plan_display'] ?? '繝｡繝ｳ繝舌・繝励Λ繝ｳ';
    }
    
    // 隱崎ｨｼ謌仙粥譎ゅ・隧ｳ邏ｰ繝ｭ繧ｰ
    error_log('AiNA API隱崎ｨｼ謌仙粥: User ID=' . $user['id'] . ', Email=' . $user['email'] . ', Plan ID=' . ($user['plan_id'] ?? 'null') . ', Status ID=' . ($user['status_id'] ?? 'null'));
    
    return ['success' => true, 'user' => $user];
}

/**
 * 繝ｦ繝ｼ繧ｶ繝ｼ隱崎ｨｼ縺ｮ讀懆ｨｼ
 */
function validateUserAccess($user) {
    // 螳壽焚縺梧悴螳夂ｾｩ縺ｮ蝣ｴ蜷医・繝輔か繝ｼ繝ｫ繝舌ャ繧ｯ・域悽逡ｪ迺ｰ蠅・ｯｾ蠢懶ｼ・    if (!defined('ALLOWED_STATUSES')) {
        define('ALLOWED_STATUSES', [3]);
    }
    if (!defined('ALLOWED_PLAN_IDS')) {
        define('ALLOWED_PLAN_IDS', [2, 3, 4, 5, 6, 7, 8, 9, 11, 12, 13, 14, 15]);
        error_log('隴ｦ蜻・ ALLOWED_PLAN_IDs縺梧悴螳夂ｾｩ縺ｧ縺励◆縲ゅョ繝輔か繝ｫ繝亥､繧剃ｽｿ逕ｨ縺励※縺・∪縺吶Ｄonfig/config.php繧呈峩譁ｰ縺励※縺上□縺輔＞縲・);
    }
    
    // 繧ｹ繝・・繧ｿ繧ｹ遒ｺ隱搾ｼ医い繧ｯ繝・ぅ繝紋ｼ壼藤縺ｮ縺ｿ・・    $userStatus = (int)($user['status_id'] ?? 0);
    if (!in_array($userStatus, ALLOWED_STATUSES, true)) {
        $statusNames = [
            1 => '莉ｮ逋ｻ骭ｲ',
            2 => '螂醍ｴ・ｸ医∩',
            3 => '繧｢繧ｯ繝・ぅ繝紋ｼ壼藤',
            4 => '繧ｨ繝ｪ繝ｼ繝井ｼ壼藤',
            5 => '譛ｪ謇輔＞',
            6 => '騾莨・,
            7 => '髮ｻ蟄千ｽｲ蜷榊ｮ御ｺ・,
            8 => '謾ｯ謇輔＞蠕・■',
            9 => '繧ｯ繝ｼ繝ｪ繝ｳ繧ｰ繧ｪ繝・
        ];
        $statusName = $statusNames[$userStatus] ?? '荳肴・';
        
        error_log('繝ｦ繝ｼ繧ｶ繝ｼ繧｢繧ｯ繧ｻ繧ｹ諡貞凄: 髱槭い繧ｯ繝・ぅ繝悶せ繝・・繧ｿ繧ｹ - User ID: ' . ($user['id'] ?? 'unknown') . ', Status ID: ' . $userStatus . ' (' . $statusName . ')');
        return [
            'valid' => false, 
            'message' => '縺泌茜逕ｨ縺・◆縺縺代↑縺・い繧ｫ繧ｦ繝ｳ繝育憾諷九〒縺呻ｼ・ . $statusName . '・峨・iNA 繝槭う繝壹・繧ｸ縺ｧ繧｢繧ｫ繧ｦ繝ｳ繝育憾豕√ｒ遒ｺ隱阪＠縺ｦ縺上□縺輔＞縲・,
            'error_type' => 'inactive_account'
        ];
    }
    
    // 繝励Λ繝ｳID遒ｺ隱搾ｼ医Γ繝ｳ繝舌・繝励Λ繝ｳ莉･荳奇ｼ・    $userPlanId = (int)($user['plan_id'] ?? 0);
    if (!in_array($userPlanId, ALLOWED_PLAN_IDS, true)) {
        $planDisplay = $user['plan_display'] ?? $user['plan'] ?? '繝輔Μ繝ｼ繝励Λ繝ｳ';
        
        // 隧ｳ邏ｰ縺ｪ繝・ヰ繝・げ繝ｭ繧ｰ
        error_log('繝ｦ繝ｼ繧ｶ繝ｼ繧｢繧ｯ繧ｻ繧ｹ諡貞凄: 荳埼←蛻・↑繝励Λ繝ｳ - User ID: ' . ($user['id'] ?? 'unknown') . ', Plan ID: ' . $userPlanId . ' (' . $planDisplay . ')');
        error_log('險ｱ蜿ｯ縺輔ｌ縺ｦ縺・ｋ繝励Λ繝ｳID: ' . json_encode(ALLOWED_PLAN_IDS));
        error_log('蜿嶺ｿ｡縺励◆繝ｦ繝ｼ繧ｶ繝ｼ諠・ｱ: ' . json_encode($user));
        
        return [
            'valid' => false, 
            'message' => '繝｡繝ｳ繝舌・繝励Λ繝ｳ莉･荳翫・莨壼藤縺ｮ縺ｿ縺泌茜逕ｨ縺・◆縺縺代∪縺吶ら樟蝨ｨ縺ｮ繝励Λ繝ｳ: ' . $planDisplay . '縲ゅ・繝ｩ繝ｳ縺ｮ繧｢繝・・繧ｰ繝ｬ繝ｼ繝峨↓縺､縺・※縺ｯ AiNA 繝槭う繝壹・繧ｸ繧偵＃遒ｺ隱阪￥縺縺輔＞縲・,
            'error_type' => 'insufficient_plan'
        ];
    }
    
    return ['valid' => true];
}

/**
 * 繝ｦ繝ｼ繧ｶ繝ｼ縺ｮ蟷ｴ鮨｢繧定ｨ育ｮ・ */
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
 * 繝ｦ繝ｼ繧ｶ繝ｼ縺・3豁ｳ莉･荳翫°繝√ぉ繝・け
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
        return false; // 逕溷ｹｴ譛域律縺梧悴險ｭ螳壹・蝣ｴ蜷医・辟｡蜉ｹ
    }
    
    $age = calculateAge($result['birthdate']);
    return $age !== null && $age >= 13;
}

/**
 * 繝ｦ繝ｼ繧ｶ繝ｼ縺ｮ菴懈・縺ｾ縺溘・譖ｴ譁ｰ・・PI隱崎ｨｼ縺ｮ縺ｿ・・ */
function createOrUpdateUser($apiUser) {
    try {
        $db = Database::getInstance();
        
        // 蠢・ｦ√↑繝輔ぅ繝ｼ繝ｫ繝峨・遒ｺ隱・        if (empty($apiUser['id']) || empty($apiUser['email'])) {
            error_log('繝ｦ繝ｼ繧ｶ繝ｼ菴懈・繧ｨ繝ｩ繝ｼ: 蠢・ｦ√↑繝輔ぅ繝ｼ繝ｫ繝峨′荳崎ｶｳ - ' . json_encode($apiUser));
            return false;
        }
        
        // 譌｢蟄倥Θ繝ｼ繧ｶ繝ｼ縺ｮ遒ｺ隱搾ｼ・ina_user_id縺ｾ縺溘・email縺ｧ・・ id縺ｨfull_name繧貞酔譎ゅ↓蜿門ｾ・        $existingUser = $db->selectOne(
            "SELECT id, full_name FROM users WHERE aina_user_id = ? OR email = ?",
            [$apiUser['id'], $apiUser['email']]
        );
        
        // name繝輔ぅ繝ｼ繝ｫ繝峨′遨ｺ縺ｮ蝣ｴ蜷医・譌｢蟄倥・full_name縺ｾ縺溘・繝輔か繝ｼ繝ｫ繝舌ャ繧ｯ蛟､繧剃ｽｿ逕ｨ
        if (empty($apiUser['name'])) {
            if (!empty($existingUser['full_name'])) {
                $apiUser['name'] = $existingUser['full_name'];
                error_log('繝ｦ繝ｼ繧ｶ繝ｼ菴懈・隴ｦ蜻・ name繝輔ぅ繝ｼ繝ｫ繝峨′遨ｺ縺ｮ縺溘ａ縲∵里蟄倥・full_name "' . $apiUser['name'] . '" 繧剃ｽｿ逕ｨ縺励∪縺吶・);
            } else {
                // 繝輔か繝ｼ繝ｫ繝舌ャ繧ｯ: 繝｡繝ｼ繝ｫ繧｢繝峨Ξ繧ｹ縺ｮ@繧医ｊ蜑阪・驛ｨ蛻・ｒ菴ｿ逕ｨ
                $emailParts = explode('@', $apiUser['email']);
                $apiUser['name'] = $emailParts[0] ?? '繝ｦ繝ｼ繧ｶ繝ｼ';
                error_log('繝ｦ繝ｼ繧ｶ繝ｼ菴懈・隴ｦ蜻・ name繝輔ぅ繝ｼ繝ｫ繝峨′遨ｺ縺ｮ縺溘ａ縲√ヵ繧ｩ繝ｼ繝ｫ繝舌ャ繧ｯ蛟､ "' . $apiUser['name'] . '" 繧剃ｽｿ逕ｨ縺励∪縺吶・);
            }
        }
        
        if ($existingUser) {
            // 譌｢蟄倥Θ繝ｼ繧ｶ繝ｼ縺ｮ譖ｴ譁ｰ
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
                error_log('繝ｦ繝ｼ繧ｶ繝ｼ譖ｴ譁ｰ隴ｦ蜻・ 譖ｴ譁ｰ蟇ｾ雎｡縺瑚ｦ九▽縺九ｉ縺ｪ縺・- User ID: ' . $existingUser['id']);
            }
            
            $userId = $existingUser['id'];
            error_log('繝ｦ繝ｼ繧ｶ繝ｼ譖ｴ譁ｰ謌仙粥: User ID: ' . $userId . ', AiNA ID: ' . $apiUser['id']);
        } else {
            // 譁ｰ隕上Θ繝ｼ繧ｶ繝ｼ縺ｮ菴懈・
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
                error_log('繝ｦ繝ｼ繧ｶ繝ｼ菴懈・繧ｨ繝ｩ繝ｼ: INSERT縺悟､ｱ謨・- ' . json_encode($apiUser));
                return false;
            }
            
            error_log('譁ｰ隕上Θ繝ｼ繧ｶ繝ｼ菴懈・謌仙粥: User ID: ' . $userId . ', AiNA ID: ' . $apiUser['id']);
        }
        
        // 邂｡逅・・・繝ｯ繧､繝医Μ繧ｹ繝茨ｼ・env縺ｮADMIN_EMAILS・峨↓隧ｲ蠖薙☆繧句ｴ蜷医∥dmin繝ｭ繝ｼ繝ｫ繧剃ｻ倅ｸ・        try {
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
        error_log('繝ｦ繝ｼ繧ｶ繝ｼ菴懈・萓句､悶お繝ｩ繝ｼ: ' . $e->getMessage() . ' - ' . json_encode($apiUser));
        return false;
    }
}

/**
 * API繝ｭ繧ｰ繧､繝ｳ蜃ｦ逅・ｼ育ｰ｡邏蛹也沿・・ */
function performApiLogin($email, $password) {
    // 蜈･蜉帛､縺ｮ繝医Μ繝溘Φ繧ｰ・亥燕蠕檎ｩｺ逋ｽ繝ｻ謾ｹ陦後ｒ蜑企勁・・    $email = trim($email);
    $password = trim($password);
    
    // 蜈･蜉帛､縺ｮ讀懆ｨｼ
    if (empty($email) || empty($password)) {
        return [
            'success' => false, 
            'message' => '繝｡繝ｼ繝ｫ繧｢繝峨Ξ繧ｹ縺ｨ繝代せ繝ｯ繝ｼ繝峨ｒ蜈･蜉帙＠縺ｦ縺上□縺輔＞縲・,
            'error_type' => 'validation_error'
        ];
    }
    
    // 縺ｾ縺壹Ο繝ｼ繧ｫ繝ｫDB縺ｧ縺ｮ隱崎ｨｼ繧定ｩｦ陦・    $localAuthResult = performLocalLogin($email, $password);
    if ($localAuthResult['success']) {
        return $localAuthResult;
    }
    
    // 繝ｭ繝ｼ繧ｫ繝ｫ隱崎ｨｼ縺悟､ｱ謨励＠縺溷ｴ蜷医、PI隱崎ｨｼ繧定ｩｦ陦・    $authResult = authenticateWithAinaApi($email, $password);
    if (!$authResult['success']) {
        return [
            'success' => false, 
            'message' => $authResult['message'],
            'error_type' => $authResult['error_type'] ?? 'auth_error'
        ];
    }
    
    $apiUser = $authResult['user'];
    
    // 繝ｦ繝ｼ繧ｶ繝ｼ繧｢繧ｯ繧ｻ繧ｹ讀懆ｨｼ
    $validation = validateUserAccess($apiUser);
    if (!$validation['valid']) {
        return [
            'success' => false, 
            'message' => $validation['message'],
            'error_type' => $validation['error_type'] ?? 'access_denied'
        ];
    }
    
    // 繝ｦ繝ｼ繧ｶ繝ｼ菴懈・/譖ｴ譁ｰ
    $userId = createOrUpdateUser($apiUser);
    if (!$userId) {
        error_log('繝ｭ繧ｰ繧､繝ｳ螟ｱ謨・ 繝ｦ繝ｼ繧ｶ繝ｼ諠・ｱ菫晏ｭ倥お繝ｩ繝ｼ - Email: ' . $email . ', AiNA ID: ' . $apiUser['id']);
        return [
            'success' => false, 
            'message' => '繝ｦ繝ｼ繧ｶ繝ｼ諠・ｱ縺ｮ菫晏ｭ倥↓螟ｱ謨励＠縺ｾ縺励◆縲ゅ＠縺ｰ繧峨￥譎る俣繧偵♀縺・※蜀榊ｺｦ縺願ｩｦ縺励￥縺縺輔＞縲・,
            'error_type' => 'database_error'
        ];
    }
    
    // 繧ｻ繝・す繝ｧ繝ｳ險ｭ螳夲ｼ医ヱ繧ｹ繝ｯ繝ｼ繝峨・菫晄戟縺励↑縺・ｼ・    try {
        $_SESSION['user_id'] = $userId;
        $_SESSION['aina_user_id'] = $apiUser['id'];
        $_SESSION['user_rank'] = $apiUser['rank'] ?? $apiUser['plan_display'] ?? '';
        $_SESSION['user_status'] = $apiUser['status_id'];
        $_SESSION['user_plan_id'] = $apiUser['plan_id'] ?? null;
        $_SESSION['user_plan'] = $apiUser['plan'] ?? null;
        $_SESSION['login_time'] = time();
        
        error_log('繝ｭ繧ｰ繧､繝ｳ謌仙粥: User ID: ' . $userId . ', Email: ' . $email . ', AiNA ID: ' . $apiUser['id'] . ', Plan: ' . ($apiUser['plan_display'] ?? 'unknown') . ', Status: ' . $apiUser['status_id']);
        
        return ['success' => true, 'user_id' => $userId];
    } catch (Exception $e) {
        error_log('繧ｻ繝・す繝ｧ繝ｳ險ｭ螳壹お繝ｩ繝ｼ: ' . $e->getMessage() . ' - User ID: ' . $userId);
        return [
            'success' => false, 
            'message' => '繝ｭ繧ｰ繧､繝ｳ蜃ｦ逅・ｸｭ縺ｫ繧ｨ繝ｩ繝ｼ縺檎匱逕溘＠縺ｾ縺励◆縲ょ・蠎ｦ縺願ｩｦ縺励￥縺縺輔＞縲・,
            'error_type' => 'session_error'
        ];
    }
}

/**
 * 繝ｭ繝ｼ繧ｫ繝ｫDB隱崎ｨｼ蜃ｦ逅・ */
function performLocalLogin($email, $password) {
    try {
        // 蜈･蜉帛､縺ｮ繝医Μ繝溘Φ繧ｰ・亥燕蠕檎ｩｺ逋ｽ繝ｻ謾ｹ陦後ｒ蜑企勁・・        $email = trim($email);
        $password = trim($password);
        
        $db = Database::getInstance();
        
        // 繝ｭ繝ｼ繧ｫ繝ｫDB縺九ｉ繝ｦ繝ｼ繧ｶ繝ｼ諠・ｱ繧貞叙蠕・        $user = $db->selectOne(
            "SELECT * FROM users WHERE email = ? AND is_active = 1",
            [$email]
        );
        
        if (!$user) {
            return [
                'success' => false,
                'message' => '繝｡繝ｼ繝ｫ繧｢繝峨Ξ繧ｹ縺ｾ縺溘・繝代せ繝ｯ繝ｼ繝峨′豁｣縺励￥縺ゅｊ縺ｾ縺帙ｓ縲・,
                'error_type' => 'user_not_found'
            ];
        }
        
        // 繝代せ繝ｯ繝ｼ繝峨ワ繝・す繝･縺悟ｭ伜惠縺吶ｋ蝣ｴ蜷医・縺ｿ讀懆ｨｼ
        if (!empty($user['password_hash'])) {
            if (!password_verify($password, $user['password_hash'])) {
                error_log('繝ｭ繝ｼ繧ｫ繝ｫ隱崎ｨｼ螟ｱ謨・ 繝代せ繝ｯ繝ｼ繝我ｸ堺ｸ閾ｴ - Email: ' . $email);
                return [
                    'success' => false,
                    'message' => '繝｡繝ｼ繝ｫ繧｢繝峨Ξ繧ｹ縺ｾ縺溘・繝代せ繝ｯ繝ｼ繝峨′豁｣縺励￥縺ゅｊ縺ｾ縺帙ｓ縲・,
                    'error_type' => 'password_mismatch'
                ];
            }
        } else {
            // 繝代せ繝ｯ繝ｼ繝峨ワ繝・す繝･縺悟ｭ伜惠縺励↑縺・ｴ蜷医・API隱崎ｨｼ縺ｫ蟋斐・繧・            return [
                'success' => false,
                'message' => 'API隱崎ｨｼ縺悟ｿ・ｦ√〒縺吶・,
                'error_type' => 'no_password_hash'
            ];
        }
        
        // 繝ｭ繝ｼ繧ｫ繝ｫ隱崎ｨｼ謌仙粥蠕後、DMIN_EMAILS 縺ｫ蜷ｫ縺ｾ繧後※縺・ｌ縺ｰ admin 繝ｭ繝ｼ繝ｫ繧剃ｻ倅ｸ・譛牙柑蛹・        try {
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

        // 繧ｻ繝・す繝ｧ繝ｳ險ｭ螳・        $_SESSION['user_id'] = $user['id'];
        $_SESSION['aina_user_id'] = $user['aina_user_id'] ?? null;
        $_SESSION['user_rank'] = '';
        $_SESSION['user_status'] = 3; // 繝・ヵ繧ｩ繝ｫ繝医〒繧｢繧ｯ繝・ぅ繝・        $_SESSION['user_plan_id'] = null; // 繝ｭ繝ｼ繧ｫ繝ｫ隱崎ｨｼ縺ｧ縺ｯ繝励Λ繝ｳID縺ｪ縺・        $_SESSION['user_plan'] = null;
        $_SESSION['login_time'] = time();

        error_log('繝ｭ繝ｼ繧ｫ繝ｫ隱崎ｨｼ謌仙粥: User ID: ' . $user['id'] . ', Email: ' . $email);
        
        return ['success' => true, 'user_id' => $user['id']];
        
    } catch (Exception $e) {
        error_log('繝ｭ繝ｼ繧ｫ繝ｫ隱崎ｨｼ繧ｨ繝ｩ繝ｼ: ' . $e->getMessage());
        return [
            'success' => false,
            'message' => '隱崎ｨｼ蜃ｦ逅・ｸｭ縺ｫ繧ｨ繝ｩ繝ｼ縺檎匱逕溘＠縺ｾ縺励◆縲・,
            'error_type' => 'local_auth_error'
        ];
    }
}

/**
 * 繝・く繧ｹ繝亥・縺ｮURL繧定・蜍慕噪縺ｫ繝上う繝代・繝ｪ繝ｳ繧ｯ縺ｫ螟画鋤
 */
function autolink($text) {
    // URL繝代ち繝ｼ繝ｳ・・ttp, https・・    $pattern = '/(https?:\/\/[^\s<>"\']+)/i';
    
    // URL繧偵Μ繝ｳ繧ｯ縺ｫ螟画鋤
    $text = preg_replace_callback($pattern, function($matches) {
        $url = $matches[1];
        // URL縺ｮ譛ｫ蟆ｾ縺ｮ蜿･隱ｭ轤ｹ繧帝勁螟・        $url = rtrim($url, '.,;:!?');
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
        echo '<h1 class="text-2xl font-bold text-gray-900 mb-2">繧｢繧ｯ繧ｻ繧ｹ讓ｩ髯舌′縺ゅｊ縺ｾ縺帙ｓ</h1>';
        echo '<p class="text-gray-600 mb-6">邂｡逅・・・縺ｿ縺後い繧ｯ繧ｻ繧ｹ縺ｧ縺阪∪縺吶ょｿ・ｦ√↑蝣ｴ蜷医・邂｡逅・・↓讓ｩ髯蝉ｻ倅ｸ弱ｒ萓晞ｼ縺励※縺上□縺輔＞縲・/p>';
        echo '<div class="flex gap-3 justify-center">';
        echo '<a class="px-4 py-2 rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200" href="' . h(url('')) . '">繝医ャ繝励∈</a>';
        echo '<a class="px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700" href="' . h(url('logout')) . '">繝ｭ繧ｰ繧｢繧ｦ繝・/a>';
        echo '</div></div></body></html>';
        exit;
    }
}

