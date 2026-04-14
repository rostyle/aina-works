<?php
/**
 * LINE Webhook エンドポイント
 *
 * LINEグループに投稿された求人情報を受信し、
 * Gemini APIで求人判定＋フォーマット整形、PHPで金額変換して返信する。
 */

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

// =============================================================
// 金額変換（PHP計算） - Geminiに任せずPHPで正確に計算
// =============================================================

/**
 * 営業職かどうかを判定
 */
function isSalesJob($text) {
    $keywords = ['営業', '獲得', '推奨販売', 'アポイント獲得', 'クロージング', '光AD', '通信販売', '法人営業'];
    foreach ($keywords as $kw) {
        if (mb_strpos($text, $kw) !== false) {
            return true;
        }
    }
    return false;
}

/**
 * 時給変換: -600円、最低1,300円
 */
function convertHourly($amount) {
    $converted = $amount - 600;
    return max($converted, 1300);
}

/**
 * 日当変換: -3,000円、最低12,000円
 */
function convertDaily($amount) {
    $converted = $amount - 3000;
    return max($converted, 12000);
}

/**
 * 月給変換: -70,000円、一般職は最低270,000円、営業職は最低320,000円
 */
function convertMonthly($amount, $isSales) {
    $converted = $amount - 70000;
    $min = $isSales ? 320000 : 270000;
    return max($converted, $min);
}

/**
 * 数値を日本語金額表記にフォーマット
 * 元が「万」表記なら万で返す、それ以外はカンマ区切り
 */
function formatJpnAmount($amount, $useMan = false) {
    if ($useMan && $amount >= 10000 && $amount % 10000 === 0) {
        return ($amount / 10000) . '万';
    }
    return number_format($amount);
}

/**
 * テキスト中の金額を変換する
 * 対応パターン:
 *   時給: 時給2,100円 / 時給2100円
 *   日当: 日当15,000円 / 日給15000円
 *   月給: 月給350,000円 / 月収35万円 / 月給35万〜42万
 */
function convertPricesInText($text, $isSales) {
    $original = $text;

    // --- 時給パターン ---
    // 時給2,100円 / 時給2100円
    $text = preg_replace_callback(
        '/時給\s*([0-9,]+)\s*円/u',
        function ($m) {
            $amount = (int)str_replace(',', '', $m[1]);
            $converted = convertHourly($amount);
            return '時給' . number_format($converted) . '円';
        },
        $text
    );
    // 時給レンジ: 時給1,800円〜2,100円
    $text = preg_replace_callback(
        '/時給\s*([0-9,]+)\s*円?\s*[〜~～ー―－]\s*([0-9,]+)\s*円/u',
        function ($m) {
            $min = convertHourly((int)str_replace(',', '', $m[1]));
            $max = convertHourly((int)str_replace(',', '', $m[2]));
            return '時給' . number_format($min) . '円〜' . number_format($max) . '円';
        },
        $text
    );

    // --- 日当・日給パターン ---
    $text = preg_replace_callback(
        '/(日当|日給)\s*([0-9,]+)\s*円/u',
        function ($m) {
            $amount = (int)str_replace(',', '', $m[2]);
            $converted = convertDaily($amount);
            return $m[1] . number_format($converted) . '円';
        },
        $text
    );
    // 日当レンジ
    $text = preg_replace_callback(
        '/(日当|日給)\s*([0-9,]+)\s*円?\s*[〜~～ー―－]\s*([0-9,]+)\s*円/u',
        function ($m) {
            $min = convertDaily((int)str_replace(',', '', $m[2]));
            $max = convertDaily((int)str_replace(',', '', $m[3]));
            return $m[1] . number_format($min) . '円〜' . number_format($max) . '円';
        },
        $text
    );

    // --- 月給・月収パターン（万円表記） ---
    // 月給35万〜42万 / 月収38万円〜42万円
    $text = preg_replace_callback(
        '/(月給|月収)\s*([0-9]+)\s*万\s*円?\s*[〜~～ー―－]\s*([0-9]+)\s*万\s*円?/u',
        function ($m) use ($isSales) {
            $min = convertMonthly((int)$m[2] * 10000, $isSales);
            $max = convertMonthly((int)$m[3] * 10000, $isSales);
            return $m[1] . formatJpnAmount($min, true) . '円〜' . formatJpnAmount($max, true) . '円';
        },
        $text
    );
    // 月給35万円（単独）
    $text = preg_replace_callback(
        '/(月給|月収)\s*([0-9]+)\s*万\s*円/u',
        function ($m) use ($isSales) {
            $amount = (int)$m[2] * 10000;
            $converted = convertMonthly($amount, $isSales);
            return $m[1] . formatJpnAmount($converted, true) . '円';
        },
        $text
    );

    // --- 月給・月収パターン（数字表記） ---
    // 月給350,000円〜420,000円
    $text = preg_replace_callback(
        '/(月給|月収)\s*([0-9,]+)\s*円?\s*[〜~～ー―－]\s*([0-9,]+)\s*円/u',
        function ($m) use ($isSales) {
            $min = convertMonthly((int)str_replace(',', '', $m[2]), $isSales);
            $max = convertMonthly((int)str_replace(',', '', $m[3]), $isSales);
            return $m[1] . number_format($min) . '円〜' . number_format($max) . '円';
        },
        $text
    );
    // 月給350,000円（単独）
    $text = preg_replace_callback(
        '/(月給|月収)\s*([0-9,]+)\s*円/u',
        function ($m) use ($isSales) {
            $amount = (int)str_replace(',', '', $m[2]);
            $converted = convertMonthly($amount, $isSales);
            return $m[1] . number_format($converted) . '円';
        },
        $text
    );

    return $text;
}

// =============================================================
// Gemini API 呼び出し（求人判定 + フォーマット整形のみ）
// =============================================================
function callGeminiForJobFormat($inputText) {
    $systemPrompt = <<<'EOT'
あなたは求人案件の整形アシスタントです。

【ステップ1：求人判定】
入力テキストが「求人・募集案件の情報」かどうかを判定してください。
以下は求人情報ではありません：
- 雑談、挨拶、質問、相談
- ニュース、お知らせ、連絡事項
- 金額や給与の記載がないテキスト
求人情報でない場合は「NOT_JOB」とだけ出力してください。

【ステップ2：AiNA Works案件フォーマットに整形】
求人情報と判定した場合、以下のフォーマットに整形してください。
金額は元の表記のまま変更せずに記載してください（金額変換は別途システムが行います）。
元の情報から読み取れる項目のみ記載し、不明な項目は省略してください。

--- 出力フォーマット ---
📋 {案件タイトル（簡潔に）}

【仕事内容】
{業務内容を箇条書きで整理}

【給与・報酬】
{金額は元の表記のまま記載。時給/日当/月給を明記}
{インセンティブ・歩合があればそのまま記載}

【勤務地】
{勤務地の情報}

【勤務時間・期間】
{勤務時間、期間、シフトなどの情報}

【応募条件】
{必要なスキル・経験・資格}

【待遇・その他】
{交通費、福利厚生、その他の条件}
---

【出力ルール】
- 上記フォーマットのテキストのみを出力
- 説明、前置き、補足は不要
- 情報が読み取れないセクションは省略
- 元の情報を追加・創作しない
- 金額は一切変更しない
EOT;

    $apiUrl = rtrim(GEMINI_API_BASE_URL, '/') . '/models/'
            . rawurlencode(GEMINI_MODEL)
            . ':generateContent?key=' . urlencode(GEMINI_API_KEY);

    $requestBody = [
        'contents' => [[
            'role' => 'user',
            'parts' => [['text' => $systemPrompt . "\n\n--- 以下のテキストを処理してください ---\n\n" . $inputText]]
        ]],
        'generationConfig' => [
            'temperature' => 0.1,
            'maxOutputTokens' => 4096,
        ]
    ];

    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
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

// =============================================================
// メイン処理
// =============================================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(200);
    echo 'OK';
    exit;
}

$body = file_get_contents('php://input');
if (empty($body)) {
    http_response_code(200);
    echo 'OK';
    exit;
}

lineLog("Received webhook (length=" . strlen($body) . ")");

// 署名検証
$secretSet = !empty(DEMI_LINE_CHANNEL_SECRET);
lineLog("Signature check: secret_set=" . ($secretSet ? 'yes' : 'no'));
if ($secretSet) {
    if (!verifyLineSignature($body, DEMI_LINE_CHANNEL_SECRET)) {
        lineLog("Signature verification FAILED");
        http_response_code(200);
        echo 'OK';
        exit;
    }
    lineLog("Signature verification OK");
}

// 設定チェック
$tokenSet = !empty(DEMI_LINE_CHANNEL_ACCESS_TOKEN);
$geminiSet = !empty(GEMINI_API_KEY);
lineLog("Config check: access_token=" . ($tokenSet ? 'yes' : 'no') . " gemini_key=" . ($geminiSet ? 'yes' : 'no'));
if (!$tokenSet || !$geminiSet) {
    lineLog("Missing config - aborting");
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

$eventCount = count($events['events']);
lineLog("Event count: {$eventCount}");

foreach ($events['events'] as $i => $event) {
    $eventType = $event['type'] ?? 'unknown';
    $msgType = $event['message']['type'] ?? 'none';
    lineLog("Event[{$i}]: type={$eventType}, msg_type={$msgType}");

    if ($event['type'] !== 'message' || $event['message']['type'] !== 'text') {
        lineLog("Event[{$i}]: skipped (not text message)");
        continue;
    }

    $sourceType = $event['source']['type'] ?? '';
    $replyToken = $event['replyToken'] ?? '';
    $userText = $event['message']['text'] ?? '';

    lineLog("Event[{$i}]: source={$sourceType}, text_len=" . mb_strlen($userText));

    if (empty($userText) || empty($replyToken)) {
        lineLog("Event[{$i}]: skipped (empty text or replyToken)");
        continue;
    }

    if (mb_strlen($userText) < 30) {
        lineLog("Event[{$i}]: skipped (too short: " . mb_strlen($userText) . " chars)");
        continue;
    }

    // ステップ1: Geminiで求人判定 + フォーマット整形（金額はそのまま）
    $formatted = callGeminiForJobFormat($userText);

    if ($formatted === null) {
        lineLog("Event[{$i}]: Gemini API error");
        continue;
    }

    if (trim($formatted) === 'NOT_JOB') {
        lineLog("Event[{$i}]: skipped (NOT_JOB)");
        continue;
    }

    // ステップ2: PHPで金額変換
    $isSales = isSalesJob($userText);
    lineLog("Event[{$i}]: isSales=" . ($isSales ? 'yes' : 'no'));
    $converted = convertPricesInText($formatted, $isSales);

    lineLog("Event[{$i}]: price conversion done");

    // LINEメッセージは5000文字制限
    if (mb_strlen($converted) > 5000) {
        $converted = mb_substr($converted, 0, 4990) . "\n…（省略）";
    }

    // 返信
    $success = lineReply($replyToken, [[
        'type' => 'text',
        'text' => $converted,
    ]]);

    lineLog("Event[{$i}]: reply " . ($success ? "sent" : "failed"));
}

http_response_code(200);
echo 'OK';
