<?php
require_once '../config/config.php';

// 出力バッファを完全にクリア
while (ob_get_level()) {
    ob_end_clean();
}

// エラー出力を無効化（JSONレスポンス用）
ini_set('display_errors', '0');
error_reporting(0);

header('Content-Type: application/json');

// ログイン確認
if (!isLoggedIn()) {
	jsonResponse(['error' => 'ログインが必要です'], 401);
}

// CSRFトークン検証
$csrfToken = $_POST['csrf_token'] ?? '';
if (!verifyCsrfToken($csrfToken)) {
	jsonResponse(['error' => '不正なリクエストです'], 403);
}

// 入力取得・整形
$bankName = trim($_POST['bank_name'] ?? '');
$branchName = trim($_POST['branch_name'] ?? '');
$accountType = trim($_POST['account_type'] ?? '普通');
$accountNumber = preg_replace('/[^0-9\-]/u', '', (string)($_POST['account_number'] ?? ''));
$accountHolderName = trim($_POST['account_holder_name'] ?? '');
$accountHolderKana = trim($_POST['account_holder_kana'] ?? '');
$note = trim($_POST['note'] ?? '');

// バリデーション
$errors = [];
$allowedTypes = ['普通','当座','貯蓄','その他'];
if ($bankName === '') $errors[] = '銀行名は必須です';
if ($accountHolderName === '') $errors[] = '口座名義は必須です';
if ($accountNumber === '') $errors[] = '口座番号は必須です';
if (!in_array($accountType, $allowedTypes, true)) $accountType = '普通';
if (mb_strlen($bankName) > 100) $errors[] = '銀行名が長すぎます';
if (mb_strlen($branchName) > 100) $errors[] = '支店名が長すぎます';
if (mb_strlen($accountHolderName) > 100) $errors[] = '口座名義が長すぎます';
if (mb_strlen($accountHolderKana) > 100) $errors[] = '口座名義カナが長すぎます';
if (mb_strlen($accountNumber) > 32) $errors[] = '口座番号が長すぎます';
if (mb_strlen($note) > 255) $errors[] = '備考が長すぎます';

if (!empty($errors)) {
	jsonResponse(['success' => false, 'errors' => $errors], 422);
}

try {
	$db = Database::getInstance();
	$currentUser = getCurrentUser();

	// 既存の主口座を取得
	$existing = $db->selectOne(
		"SELECT id FROM user_bank_accounts WHERE user_id = ? AND is_primary = 1 LIMIT 1",
		[$currentUser['id']]
	);

	if ($existing && isset($existing['id'])) {
		$db->update(
			"UPDATE user_bank_accounts
			 SET bank_name = ?, branch_name = ?, account_type = ?, account_number = ?, account_holder_name = ?, account_holder_kana = ?, note = ?, updated_at = NOW()
			 WHERE id = ?",
			[$bankName, $branchName ?: null, $accountType, $accountNumber, $accountHolderName, $accountHolderKana ?: null, $note ?: null, (int)$existing['id']]
		);
		$accountId = (int)$existing['id'];
	} else {
		$accountId = (int)$db->insert(
			"INSERT INTO user_bank_accounts
			 (user_id, bank_name, branch_name, account_type, account_number, account_holder_name, account_holder_kana, note, is_primary, created_at, updated_at)
			 VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, NOW(), NOW())",
			[$currentUser['id'], $bankName, $branchName ?: null, $accountType, $accountNumber, $accountHolderName, $accountHolderKana ?: null, $note ?: null]
		);
	}

	jsonResponse([
		'success' => true,
		'message' => '振込先情報を保存しました',
		'account_id' => $accountId
	]);
} catch (Exception $e) {
	error_log('Save bank account error: ' . $e->getMessage());
	jsonResponse(['error' => 'システムエラーが発生しました'], 500);
}

