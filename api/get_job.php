<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

// Check if job ID is provided
$jobId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($jobId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid job ID']);
    exit;
}

try {
    $db = Database::getInstance();
    
    // Check for admin privilege
    $isAdmin = isAdminUser(); // Assumes function exists and session is started

    // Build Query
    // Normal users: active jobs only
    // Admins: view all
    $whereClause = "j.id = ?";
    if (!$isAdmin) {
        $whereClause .= " AND j.status != 'archived' AND j.status != 'closed'"; // Adjust user visibility as needed
    }

    $sql = "
        SELECT j.*, 
               u.full_name as client_name, 
               u.profile_image as client_image,
               u.location as client_location,
               u.email as client_email,
               c.name as category_name,
               c.color as category_color
        FROM jobs j
        LEFT JOIN users u ON j.client_id = u.id
        LEFT JOIN categories c ON j.category_id = c.id
        WHERE j.id = ?
    ";
    
    $job = $db->selectOne($sql, [$jobId]);
    
    if (!$job) {
        http_response_code(404);
        echo json_encode(['error' => 'Job not found']);
        exit;
    }

    // Admin specific data
    if ($isAdmin) {
        $job['is_admin_view'] = true;
    }

    
    // Format numeric values
    $job['budget_min_formatted'] = formatPrice($job['budget_min']);
    $job['budget_max_formatted'] = formatPrice($job['budget_max']);
    
    // Format dates
    $job['deadline_formatted'] = $job['deadline'] ? formatDate($job['deadline']) : '指定なし';
    $job['created_at_formatted'] = timeAgo($job['created_at']);
    
    // Format full description with line breaks
    $job['description_html'] = nl2br(h($job['description']));
    
    // Return success response
    echo json_encode(['success' => true, 'job' => $job]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
