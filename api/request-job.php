<?php
require_once '../config/config.php';

header('Content-Type: application/json');

// ログイン確認
if (!isLoggedIn()) {
    jsonResponse(['error' => 'ログインが必要です'], 401);
}

// CSRFトークン検証
if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    jsonResponse(['error' => '不正なリクエストです'], 403);
}

try {
    $db = Database::getInstance();
    $currentUser = getCurrentUser();
    
    $creatorId = (int)($_POST['creator_id'] ?? 0);
    $workId = (int)($_POST['work_id'] ?? 0);
    $projectTitle = trim($_POST['project_title'] ?? '');
    $projectDescription = trim($_POST['project_description'] ?? '');
    $budgetMin = (int)($_POST['budget_min'] ?? 0);
    $budgetMax = (int)($_POST['budget_max'] ?? 0);
    $deadline = $_POST['deadline'] ?? '';
    $requirements = trim($_POST['requirements'] ?? '');
    
    // バリデーション
    if (!$creatorId) {
        jsonResponse(['error' => 'クリエイターが指定されていません'], 400);
    }
    
    if (empty($projectTitle)) {
        jsonResponse(['error' => 'プロジェクトタイトルを入力してください'], 400);
    }
    
    if (empty($projectDescription)) {
        jsonResponse(['error' => 'プロジェクト概要を入力してください'], 400);
    }
    
    if ($budgetMin < 100) {
        jsonResponse(['error' => '予算（最小）は100円以上で入力してください'], 400);
    }
    if ($budgetMax < 100) {
        jsonResponse(['error' => '予算（最大）は100円以上で入力してください'], 400);
    }
    if ($budgetMin >= 100 && $budgetMax >= 100 && $budgetMin > $budgetMax) {
        jsonResponse(['error' => '予算の上限は下限より大きい値を入力してください'], 400);
    }
    
    if (empty($deadline)) {
        jsonResponse(['error' => '希望納期を入力してください'], 400);
    }
    
    // クリエイターの存在確認
    $creator = $db->selectOne("SELECT id, full_name FROM users WHERE id = ? AND is_active = 1", [$creatorId]);
    if (!$creator) {
        jsonResponse(['error' => 'クリエイターが見つかりません'], 404);
    }
    
    // 自分自身への案件依頼を防ぐ
    if ($currentUser['id'] === $creatorId) {
        jsonResponse(['error' => '自分自身に案件を依頼することはできません'], 400);
    }
    
    // 案件依頼をデータベースに保存
    $jobRequestId = $db->insert("
        INSERT INTO job_requests (
            client_id, creator_id, work_id, project_title, project_description, 
            budget_min, budget_max, deadline, requirements, status, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
    ", [
        $currentUser['id'], $creatorId, $workId, $projectTitle, $projectDescription,
        $budgetMin, $budgetMax, $deadline, $requirements
    ]);
    
    if ($jobRequestId) {
        jsonResponse([
            'success' => true,
            'message' => '案件依頼を送信しました',
            'job_request_id' => $jobRequestId
        ]);
    } else {
        jsonResponse(['error' => '案件依頼の送信に失敗しました'], 500);
    }
    
} catch (Exception $e) {
    error_log("Job request error: " . $e->getMessage());
    jsonResponse(['error' => 'システムエラーが発生しました'], 500);
}