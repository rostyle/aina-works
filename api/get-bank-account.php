<?php
// 出力バッファリングを開始してクリーンなJSONレスポンスを確保
ob_start();

// エラー表示を無効にしてHTMLエラーを防ぐ
error_reporting(E_ALL);
ini_set('display_errors', '0');

require_once '../config/config.php';

// 出力バッファを完全にクリアしてヘッダー問題を回避
while (ob_get_level()) {
    ob_end_clean();
}
ob_start();

// ヘッダーが送信済みでない場合のみ設定
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

		if (!$account || $account === false || !is_array($account)) {
			jsonResponse(['success' => false, 'error' => '振込先情報が登録されていません。プロフィールページから振込先情報を登録してください。'], 404);
		}

		jsonResponse(['success' => true, 'account' => $account]);
	}

	// 依頼者が特定クリエイターの口座を参照（納品後のみ）
	$jobId = (int)($_GET['job_id'] ?? 0);
	$creatorId = (int)($_GET['creator_id'] ?? 0);

	if (!$jobId || !$creatorId) {
		jsonResponse(['error' => '必要な情報が不足しています。ページを再読み込みして再度お試しください。'], 400);
	}

	// 案件の所有者と状態確認
	$job = $db->selectOne("SELECT id, client_id, status FROM jobs WHERE id = ?", [$jobId]);
	if (!$job) {
		jsonResponse(['error' => '案件が見つかりません。案件が削除された可能性があります。'], 404);
	}
	if ((int)$job['client_id'] !== (int)$currentUser['id']) {
		jsonResponse(['error' => 'この案件の振込先情報を確認する権限がありません。'], 403);
	}
	
	if (!in_array($job['status'], ['delivered','completed'], true)) {
		jsonResponse(['error' => '振込先情報は納品後に確認できます。現在の案件ステータスが「納品済み」または「完了」になっているかご確認ください。'], 403);
	}

	// 受諾済みの応募者か確認
	$accepted = $db->selectOne(
		"SELECT id FROM job_applications WHERE job_id = ? AND creator_id = ? AND status = 'accepted'",
		[$jobId, $creatorId]
	);
	if (!$accepted) {
		jsonResponse(['error' => 'このクリエイターはこの案件の受諾者ではありません。'], 403);
	}

	$account = $db->selectOne(
		"SELECT bank_name, branch_name, account_type, account_number, account_holder_name, account_holder_kana, note
		 FROM user_bank_accounts WHERE user_id = ? AND is_primary = 1",
		[$creatorId]
	);

	if (!$account || $account === false || !is_array($account) || empty($account['bank_name'])) {
		jsonResponse(['error' => 'クリエイターの振込先情報が登録されていません。クリエイターにプロフィールページから振込先情報を登録してもらう必要があります。'], 404);
	}

	jsonResponse(['success' => true, 'account' => $account]);

} catch (Exception $e) {
	error_log('Get bank account error: ' . $e->getMessage());
	// 出力バッファをクリアしてからエラーレスポンス
	while (ob_get_level()) {
		ob_end_clean();
	}
	if (!headers_sent()) {
		header('Content-Type: application/json');
	}
	jsonResponse(['error' => 'システムエラーが発生しました。しばらく時間をおいてから再度お試しください。'], 500);
}


