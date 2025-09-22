<?php
// 出力バッファリングを開始してクリーンなJSONレスポンスを確保
ob_start();

// エラー表示を無効にしてHTMLエラーを防ぐ
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once '../config/config.php';

// 出力バッファを完全にクリアしてヘッダー問題を回避
while (ob_get_level()) {
    ob_end_clean();
}
ob_start();

// ヘッダーが送信済みでない場合のみ設定
if (!headers_sent()) {
    header('Content-Type: application/json');
    header('X-Content-Type-Options: nosniff');
}

// メソッドチェック
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method Not Allowed'], 405);
}

// ログイン確認（案件投稿ページ用のため必須）
if (!isLoggedIn()) {
    jsonResponse(['error' => 'ログインが必要です'], 401);
}

// CSRFトークン取得（JSONボディ or フォーム or ヘッダー）
$raw = file_get_contents('php://input');
$isJson = false;
$payload = [];
if (!empty($raw)) {
    $decoded = json_decode($raw, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        $payload = $decoded;
        $isJson = true;
    }
}
if (!$isJson) {
    $payload = $_POST;
}

$csrfToken = $payload['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
if (!verifyCsrfToken($csrfToken)) {
    jsonResponse(['error' => '不正なリクエストです (CSRF)'], 403);
}

// 設定チェック
if (empty(GEMINI_API_KEY)) {
    jsonResponse([
        'error' => 'Gemini APIキーが未設定です。.env に GEMINI_API_KEY を設定してください。',
        'error_type' => 'config_error'
    ], 500);
}

// 入力の取得
$title = trim((string)($payload['title'] ?? ''));
$description = (string)($payload['description'] ?? '');
$categoryId = (int)($payload['category_id'] ?? 0);
$categoryName = trim((string)($payload['category_name'] ?? ''));
$budgetMin = (int)($payload['budget_min'] ?? 0);
$budgetMax = (int)($payload['budget_max'] ?? 0);
$durationWeeks = (int)($payload['duration_weeks'] ?? 0);
$urgency = (string)($payload['urgency'] ?? 'medium');

// 軽いバリデーション
if (mb_strlen($description) < 20) {
    jsonResponse(['error' => '説明文が短すぎます。最低20文字以上で入力してください。'], 400);
}

// カテゴリ名が未提供ならDBから補完
if ($categoryId && $categoryName === '') {
    try {
        $db = Database::getInstance();
        $row = $db->selectOne('SELECT name FROM categories WHERE id = ? AND is_active = 1', [$categoryId]);
        if ($row && !empty($row['name'])) {
            $categoryName = (string)$row['name'];
        }
    } catch (Exception $e) {
        // サイレントに進む（無くても致命的ではない）
    }
}

// Gemini へのプロンプト組み立て（システムノウハウを内包）
$systemPrompt = <<<EOT
あなたは AiNA Works の「AIディレクター」です。クラウドソーシングの募集文作成に精通したプロのライター兼ディレクターとして、ユーザーの募集文を添削し、改善サンプルを提供します。

## 役割:
1. **AIディレクターの添削**: 入力された募集文をクラウドソーシングのテンプレート基準で評価し、足りないものや曖昧なものを指摘
2. **改善サンプル**: 添削内容を踏まえて完成させた文章を提供

## 評価基準（クラウドソーシング募集文テンプレート）:
- 目的・背景の明確性
- 成果物の具体的な定義
- 予算・期間の明示
- スキル・経験要件の具体性
- コミュニケーション方法の明示
- 応募条件の明確性
- 魅力的で分かりやすい文章構成

## 出力要件（必ず JSON のみで出力）:
{
  "recommended_budget": {"min": number, "max": number, "currency": "JPY", "rationale": string},
  "ai_review": string,
  "improved_sample": string,
  "improvement_points": [string],
  "timeline_weeks": number,
  "tags": [string]
}

## 指針:
- ai_review: プロのライターとして募集文を詳細に添削し、問題点と改善点を具体的に指摘
- improved_sample: 添削内容を反映した完成版の募集文を作成
- 価格は日本の相場感（クラウドワークス/ランサーズ等）を参考に適正レンジを提案
- 文章は自然で魅力的なビジネス日本語で作成
- 出力は上記 JSON 構造のみ（解説やマークダウン、コードフェンスは一切出力しない）
EOT;

$jobData = [
    'title' => $title,
    'description' => $description,
    'category' => $categoryName,
    'budget_min' => $budgetMin,
    'budget_max' => $budgetMax,
    'duration_weeks' => $durationWeeks,
    'urgency' => $urgency,
];

$userMessage = "SYSTEM:\n" . $systemPrompt . "\n\nJOB_DATA:\n" . json_encode($jobData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

// Gemini API 呼び出し
$apiUrl = rtrim(GEMINI_API_BASE_URL, '/') . '/models/' . rawurlencode(GEMINI_MODEL) . ':generateContent?key=' . urlencode(GEMINI_API_KEY);

$requestBody = [
    'contents' => [[
        'role' => 'user',
        'parts' => [['text' => $userMessage]]
    ]],
    'generationConfig' => [
        'temperature' => (float)GEMINI_TEMPERATURE,
        'maxOutputTokens' => (int)GEMINI_MAX_TOKENS,
        'topP' => 0.9,
        'topK' => 40,
        // 一部環境では無視される場合があるが、対応クライアントではJSONを強制
        'responseMimeType' => 'application/json'
    ]
];

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestBody, JSON_UNESCAPED_UNICODE));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, (int)GEMINI_REQUEST_TIMEOUT);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

$response = curl_exec($ch);
$httpStatus = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
if ($response === false) {
    $err = curl_error($ch);
}
curl_close($ch);

if ($response === false) {
    jsonResponse(['error' => 'Gemini API呼び出しに失敗しました: ' . ($err ?? 'unknown')], 502);
}

$decoded = json_decode($response, true);
if (!$decoded) {
    jsonResponse(['error' => 'Gemini 応答のJSON解析に失敗しました', 'raw' => $response], 502);
}

if ($httpStatus >= 400) {
    jsonResponse(['error' => 'Gemini APIエラー', 'status' => $httpStatus, 'detail' => $decoded], 502);
}

// v1beta レスポンスからテキストを抽出
$text = $decoded['candidates'][0]['content']['parts'][0]['text'] ?? null;
if ($text === null) {
    // 安全側: 別形態（promptFeedbackのみ等）
    jsonResponse(['error' => 'Gemini 応答にテキストが含まれていません', 'detail' => $decoded], 502);
}

// モデルからのJSON文字列を抽出
$jsonString = trim($text);

// もし ``` で囲まれていれば除去
if (strpos($jsonString, '```') !== false) {
    $jsonString = preg_replace('/```[a-zA-Z]*\n?|```/', '', $jsonString);
}

// 先頭・末尾の余計な文字を削ってJSONを抽出
function extract_first_json($str) {
    $start = strpos($str, '{');
    $end = strrpos($str, '}');
    if ($start === false || $end === false || $end <= $start) return null;
    return substr($str, $start, $end - $start + 1);
}

$maybeJson = extract_first_json($jsonString) ?: $jsonString;
$ai = json_decode($maybeJson, true);
if (json_last_error() !== JSON_ERROR_NONE || !is_array($ai)) {
    jsonResponse([
        'error' => 'AI応答のJSON化に失敗しました',
        'raw_text' => $text
    ], 502);
}

// 追加のUX用メタ（フロントで演出に使用）
$meta = [
    'xp_awarded' => 25 + min(75, (int)(mb_strlen($description) / 40)),
    'combo' => ($urgency === 'high' ? 3 : 1),
    'latency_ms' => max(500, rand(800, 1600)),
];

jsonResponse([
    'success' => true,
    'data' => $ai,
    'meta' => $meta
]);


