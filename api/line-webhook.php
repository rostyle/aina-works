<?php
/**
 * LINE Webhook エンドポイント
 *
 * LINEグループに投稿された求人情報を受信し、
 * Gemini APIで金額変換を行い、変換後のテキストをLINEに返信する。
 */

// エラー出力を抑制（LINEにはHTTP 200を返す必要がある）
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once __DIR__ . '/../config/config.php';

// ログ出力用
function lineLog($message) {
    $logDir = BASE_PATH . '/logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0777, true);
    }
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents(
        $logDir . '/line-webhook.log',
        "[{$timestamp}] {$message}\n",
        FILE_APPEND | LOCK_EX
    );
}

// --- 署名検証 ---
function verifyLineSignature($body, $channelSecret) {
    $signature = $_SERVER['HTTP_X_LINE_SIGNATURE'] ?? '';
    if (empty($signature)) {
        return false;
    }
    $hash = hash_hmac('sha256', $body, $channelSecret, true);
    return hash_equals(base64_encode($hash), $signature);
}

// --- LINE Reply API ---
function lineReply($replyToken, $messages) {
    $url = 'https://api.line.me/v2/bot/message/reply';
    $body = json_encode([
        'replyToken' => $replyToken,
        'messages' => $messages,
    ], JSON_UNESCAPED_UNICODE);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . DEMI_LINE_CHANNEL_ACCESS_TOKEN,
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        lineLog("LINE Reply Error: {$error}");
        return false;
    }
    if ($httpCode !== 200) {
        lineLog("LINE Reply HTTP {$httpCode}: {$response}");
        return false;
    }
    return true;
}

// --- Gemini API 呼び出し ---
function callGeminiForPriceConversion($inputText) {
    $systemPrompt = <<<'EOT'
あなたは求人案件の単価調整アシスタントです。
入力された求人情報の金額を以下のルールに従って変換し、金額部分のみ変更した求人情報を出力してください。それ以外の文面は一切変更しないでください。

【変換ルール】
1. 時給の場合：600円を引く。ただし日当換算（時給×8時間）が12,000円を下回る場合は、時給 = 12,000 ÷ 8 = 1,500円 とする。
2. 日当の場合：600円×8時間＝4,800円を引く。ただし12,000円を下回らない。
3. 月給（一般職）の場合：70,000円を引く。ただし270,000円を下回らない。
4. 月給（営業職・販売職・獲得業務あり）の場合：70,000円を引く。ただし330,000円を下回らない。
5. 月額レンジ表記（例：38万〜42万）の場合：上限・下限それぞれに上記ルールを適用する。
6. インセンティブ・歩合・交通費の記載はそのまま残す。

【営業職の判定基準】
以下のいずれかに該当すれば営業職扱い：
- 「営業」「獲得」「推奨販売」「アポイント獲得」「クロージング」が業務内容に含まれる
- 光AD、通信販売、法人営業など売上成果が求められる案件

【出力形式】
- 元の投稿フォーマットをそのまま維持
- 変更した金額のみ差し替え
- 変更箇所の前後に余計な説明を加えない
- 変換後の求人テキストのみを出力すること（説明や前置きは不要）
EOT;

    $apiUrl = rtrim(GEMINI_API_BASE_URL, '/') . '/models/'
            . rawurlencode(GEMINI_MODEL)
            . ':generateContent?key=' . urlencode(GEMINI_API_KEY);

    $requestBody = [
        'contents' => [[
            'role' => 'user',
            'parts' => [['text' => $systemPrompt . "\n\n--- 以下の求人情報を変換してください ---\n\n" . $inputText]]
        ]],
        'generationConfig' => [
            'temperature' => 0.1, // 金額変換は正確性重視
            'maxOutputTokens' => (int)GEMINI_MAX_TOKENS,
        ]
    ];

    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestBody, JSON_UNESCAPED_UNICODE));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        lineLog("Gemini cURL Error: {$error}");
        return null;
    }
    if ($httpCode >= 400) {
        lineLog("Gemini HTTP {$httpCode}: {$response}");
        return null;
    }

    $decoded = json_decode($response, true);
    if (!$decoded) {
        lineLog("Gemini JSON decode failed: {$response}");
        return null;
    }

    $text = $decoded['candidates'][0]['content']['parts'][0]['text'] ?? null;
    if ($text === null) {
        lineLog("Gemini response has no text: " . json_encode($decoded, JSON_UNESCAPED_UNICODE));
        return null;
    }

    return trim($text);
}

// --- メイン処理 ---

// POSTのみ受け付ける
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(200);
    echo 'OK';
    exit;
}

// リクエストボディ取得
$body = file_get_contents('php://input');
if (empty($body)) {
    http_response_code(200);
    echo 'OK';
    exit;
}

lineLog("Received webhook: " . mb_substr($body, 0, 500));

// 署名検証
if (!empty(DEMI_LINE_CHANNEL_SECRET)) {
    if (!verifyLineSignature($body, DEMI_LINE_CHANNEL_SECRET)) {
        lineLog("Signature verification failed");
        http_response_code(200);
        echo 'OK';
        exit;
    }
}

// 設定チェック
if (empty(DEMI_LINE_CHANNEL_ACCESS_TOKEN) || empty(GEMINI_API_KEY)) {
    lineLog("Missing config: DEMI_LINE_CHANNEL_ACCESS_TOKEN or GEMINI_API_KEY");
    http_response_code(200);
    echo 'OK';
    exit;
}

// イベント解析
$events = json_decode($body, true);
if (!$events || !isset($events['events'])) {
    lineLog("Invalid event format");
    http_response_code(200);
    echo 'OK';
    exit;
}

foreach ($events['events'] as $event) {
    // テキストメッセージイベントのみ処理
    if ($event['type'] !== 'message' || $event['message']['type'] !== 'text') {
        continue;
    }

    $sourceType = $event['source']['type'] ?? '';
    $replyToken = $event['replyToken'] ?? '';
    $userText = $event['message']['text'] ?? '';
    $groupId = $event['source']['groupId'] ?? '';
    $userId = $event['source']['userId'] ?? '';

    lineLog("Message from {$sourceType}: " . mb_substr($userText, 0, 100));

    // グループ・個人チャットどちらも処理（テスト段階）
    // 本番ではグループのみに制限可能: if ($sourceType !== 'group') continue;

    if (empty($userText) || empty($replyToken)) {
        continue;
    }

    // 短いメッセージ（挨拶等）はスキップ（求人情報は通常ある程度の長さがある）
    if (mb_strlen($userText) < 30) {
        lineLog("Skipped: message too short (" . mb_strlen($userText) . " chars)");
        continue;
    }

    // Gemini APIで金額変換
    $converted = callGeminiForPriceConversion($userText);

    if ($converted === null) {
        lineReply($replyToken, [[
            'type' => 'text',
            'text' => '⚠️ 金額変換処理でエラーが発生しました。しばらくしてからもう一度お試しください。',
        ]]);
        continue;
    }

    // LINEメッセージは5000文字制限
    if (mb_strlen($converted) > 5000) {
        $converted = mb_substr($converted, 0, 4990) . "\n…（省略）";
    }

    // 変換後テキストを返信
    $success = lineReply($replyToken, [[
        'type' => 'text',
        'text' => $converted,
    ]]);

    lineLog("Reply " . ($success ? "sent" : "failed") . " for message from {$sourceType}");
}

// LINE Webhookは常に200を返す
http_response_code(200);
echo 'OK';
