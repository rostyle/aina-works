<?php
require_once '../config/config.php';

// JSON レスポンス
if (!headers_sent()) {
	header('Content-Type: application/json');
}

// ログイン必須
if (!isLoggedIn()) {
	jsonResponse(['error' => 'ログインが必要です'], 401);
}

try {
	$db = Database::getInstance();
	$currentUser = getCurrentUser();

	$mode = $_GET['mode'] ?? 'self';

	if ($mode === 'self') {
		// 自身の主口座を返す
		$account = $db->selectOne(
			"SELECT bank_name, branch_name, account_type, account_number, account_holder_name, account_holder_kana, note
			 FROM user_bank_accounts WHERE user_id = ? AND is_primary = 1",
			[$currentUser['id']]
		);

		jsonResponse(['success' => true, 'account' => $account]);
	}

	// 依頼者が特定クリエイターの口座を参照（納品後のみ）
	$jobId = (int)($_GET['job_id'] ?? 0);
	$creatorId = (int)($_GET['creator_id'] ?? 0);

	if (!$jobId || !$creatorId) {
		jsonResponse(['error' => 'パラメータが不正です'], 400);
	}

	// 案件の所有者と状態確認
	$job = $db->selectOne("SELECT id, client_id, status FROM jobs WHERE id = ?", [$jobId]);
	if (!$job) {
		jsonResponse(['error' => '案件が見つかりません'], 404);
	}
	if ((int)$job['client_id'] !== (int)$currentUser['id']) {
		jsonResponse(['error' => '権限がありません'], 403);
	}
	if (!in_array($job['status'], ['delivered','completed'], true)) {
		jsonResponse(['error' => '納品後に閲覧可能です'], 403);
	}

	// 受諾済みの応募者か確認
	$accepted = $db->selectOne(
		"SELECT id FROM job_applications WHERE job_id = ? AND creator_id = ? AND status = 'accepted'",
		[$jobId, $creatorId]
	);
	if (!$accepted) {
		jsonResponse(['error' => '対象のクリエイターはこの案件の受諾者ではありません'], 403);
	}

	$account = $db->selectOne(
		"SELECT bank_name, branch_name, account_type, account_number, account_holder_name, account_holder_kana, note
		 FROM user_bank_accounts WHERE user_id = ? AND is_primary = 1",
		[$creatorId]
	);

	jsonResponse(['success' => true, 'account' => $account]);

} catch (Exception $e) {
	error_log('Get bank account error: ' . $e->getMessage());
	jsonResponse(['error' => 'システムエラーが発生しました'], 500);
}


