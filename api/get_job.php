<?php
/**
 * 案件詳細（管理画面のモーダル用）
 * 依頼者のメールアドレスを含むため管理者のみに返す。
 */
require_once __DIR__ . '/../config/config.php';

if (!isLoggedIn()) {
    ErrorHandler::jsonAuthError();
}
if (!isAdminUser()) {
    ErrorHandler::jsonPermissionError();
}

$jobId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($jobId <= 0) {
    ErrorHandler::jsonError('案件IDが正しくありません', 400);
}

try {
    $db = Database::getInstance();
    $job = $db->selectOne("
        SELECT j.*,
               u.full_name AS client_name,
               u.profile_image AS client_image,
               u.location AS client_location,
               u.email AS client_email,
               c.name AS category_name,
               c.color AS category_color
        FROM jobs j
        LEFT JOIN users u ON j.client_id = u.id
        LEFT JOIN categories c ON j.category_id = c.id
        WHERE j.id = ?
    ", [$jobId]);

    if (!$job) {
        ErrorHandler::jsonNotFoundError('案件');
    }

    $job['is_admin_view'] = true;
    $job['client_image_url'] = uploaded_asset($job['client_image'] ?? '');
    $job['budget_min_formatted'] = formatPrice($job['budget_min']);
    $job['budget_max_formatted'] = formatPrice($job['budget_max']);
    $job['deadline_formatted'] = $job['deadline'] ? formatDate($job['deadline']) : '指定なし';
    $job['created_at_formatted'] = timeAgo($job['created_at']);
    $job['description_html'] = nl2br(h($job['description']));

    // 管理画面JSは { success, job } を読む
    jsonResponse(['success' => true, 'job' => $job]);
} catch (Exception $e) {
    ErrorHandler::handleException($e);
}
