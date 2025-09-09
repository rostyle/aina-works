<?php
/**
 * 画像配信スクリプト
 * storage/app/uploads/ 内の画像を安全に配信
 */

// エラー出力を抑止（ヘッダー送信前の出力防止）
@ini_set('display_errors', '0');
@error_reporting(0);

// 既存の出力バッファを完全にクリア
while (function_exists('ob_get_level') && ob_get_level() > 0) {
	ob_end_clean();
}

// パラメータ取得
$file = $_GET['file'] ?? '';

if (empty($file)) {
	http_response_code(404);
	header('Content-Type: text/plain');
	echo '404 Not Found';
	exit;
}

// セキュリティチェック: ディレクトリトラバーサル攻撃を防ぐ
if (strpos($file, '..') !== false || strpos($file, '/') !== false || strpos($file, '\\') !== false) {
	http_response_code(403);
	header('Content-Type: text/plain');
	echo '403 Forbidden';
	exit;
}

// アップロードベースパス（configに依存しない）
$uploadBase = realpath(__DIR__ . '/storage/app/uploads');
if ($uploadBase === false) {
	http_response_code(404);
	header('Content-Type: text/plain');
	echo '404 Not Found';
	exit;
}

// 対象ファイルの実パス解決とベース配下チェック
$targetPath = $uploadBase . DIRECTORY_SEPARATOR . $file;
$realTargetPath = realpath($targetPath);
if ($realTargetPath === false || strpos($realTargetPath, $uploadBase) !== 0) {
	http_response_code(404);
	header('Content-Type: text/plain');
	echo '404 File Not Found';
	exit;
}

// ファイル存在確認
if (!is_file($realTargetPath)) {
	http_response_code(404);
	header('Content-Type: text/plain');
	echo '404 File Not Found';
	exit;
}

// MIMEタイプ取得
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $realTargetPath);
finfo_close($finfo);

// 画像ファイルのみ許可
$allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
if (!in_array($mimeType, $allowedMimeTypes, true)) {
	http_response_code(403);
	header('Content-Type: text/plain');
	echo '403 Invalid File Type';
	exit;
}

// キャッシュヘッダー設定（1日）
$lastModified = filemtime($realTargetPath);
$etag = md5_file($realTargetPath);

header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $lastModified) . ' GMT');
header('ETag: "' . $etag . '"');
header('Cache-Control: public, max-age=86400');

// If-Modified-Since ヘッダーチェック
if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
	$ifModifiedSince = strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']);
	if ($ifModifiedSince !== false && $ifModifiedSince >= $lastModified) {
		http_response_code(304);
		exit;
	}
}

// If-None-Match ヘッダーチェック
if (isset($_SERVER['HTTP_IF_NONE_MATCH'])) {
	$ifNoneMatch = trim($_SERVER['HTTP_IF_NONE_MATCH'], '"');
	if ($ifNoneMatch === $etag) {
		http_response_code(304);
		exit;
	}
}

// Content-Type設定
header('Content-Type: ' . $mimeType);
header('Content-Length: ' . filesize($realTargetPath));

// ファイル出力
readfile($realTargetPath);
exit;
