<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'config/config.php';
$db = Database::getInstance();

// Mock session/login if needed
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    // 1. Get a client user
    $currentUser = $db->selectOne("SELECT * FROM users LIMIT 1");
    if (!$currentUser) throw new Exception("No users found");
    $_SESSION['user_id'] = $currentUser['id']; // Mock login
    
    echo "Current User ID: " . $currentUser['id'] . "\n";

    $db->beginTransaction();

    // 2. Create a job
    $jobTitle = "Test Job " . time();
    $jobId = $db->insert("INSERT INTO jobs (client_id, title, description, category_id, budget_min, budget_max, duration_weeks, status, created_at) VALUES (?, ?, 'Description', 1, 1000, 2000, 1, 'open', NOW())", 
        [$currentUser['id'], $jobTitle]);
    
    echo "Created Job ID: $jobId\n";

    // Ensure hiring_limit is 1 (simulate default or updated)
    $db->execute("UPDATE jobs SET hiring_limit = 1 WHERE id = ?", [$jobId]);

    // 3. Create an application (different user)
    $creator = $db->selectOne("SELECT * FROM users WHERE id != ? LIMIT 1", [$currentUser['id']]);
    if (!$creator) throw new Exception("Need another user for creator.\n");

    $appId = $db->insert("INSERT INTO job_applications (job_id, creator_id, cover_letter, proposed_price, proposed_duration, status, created_at) VALUES (?, ?, 'Cover', 1000, 1, 'pending', NOW())",
        [$jobId, $creator['id']]);
    
    echo "Created Application ID: $appId (Creator: {$creator['id']})\n";

    // 4. Accept Application
    echo "Accepting Application...\n";
    $db->update("UPDATE job_applications SET status = 'accepted' WHERE id = ?", [$appId]);
    
    // Logic from update-application-status.php
    $db->update("UPDATE jobs SET is_recruiting = 0 WHERE id = ?", [$jobId]); // because hiring_limit(1) <= accepted_count(1)
    $db->update("UPDATE jobs SET status = 'contracted' WHERE id = ?", [$jobId]);

    echo "Application Accepted. Job Status: contracted (simulated)\n";
    
    // 5. Close Job (Logic from update-job-settings.php)
    echo "Closing Job...\n";
    $newStatus = 'closed';
    $hiringLimit = 1;
    
    // Check current status
    $job = $db->selectOne("SELECT * FROM jobs WHERE id = ?", [$jobId]);
    echo "Current Job Status before close: " . $job['status'] . "\n";

    // Update status
    if ($job['status'] !== $newStatus) {
        $db->update("UPDATE jobs SET status = ? WHERE id = ?", [$newStatus, $jobId]);
        echo "Status updated to $newStatus\n";
    }
    
    // Update recruiting
    if (in_array($newStatus, ['closed','in_progress','contracted','delivered','approved','completed','cancelled'], true)) {
         $db->update("UPDATE jobs SET is_recruiting = 0 WHERE id = ?", [$jobId]);
         echo "is_recruiting set to 0\n";
    }
    
    // Re-fetch
    $jobFinal = $db->selectOne("SELECT * FROM jobs WHERE id = ?", [$jobId]);
    echo "Final Status: " . $jobFinal['status'] . "\n";
    
    $db->rollback();
    echo "Test Completed Successfully (Rolled back)\n";

} catch (Exception $e) {
    if (isset($db)) $db->rollback();
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
