<?php
/**
 * LINE Webhook エンドポイント
 *
 * LINEグループに投稿された求人情報を受信し、
 * Gemini APIで求人判定＋構造化、PHPで金額変換してAiNA Worksに案件投稿する。
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once __DIR__ . '/../config/config.php';

// LINE Bot投稿者のユーザーID（.envで設定、未設定なら1）
define('LINE_BOT_CLIENT_ID', (int)($_ENV['LINE_BOT_CLIENT_ID'] ?? 1));
// LINE Bot投稿のデフォルトカテゴリID（.envで設定、未設定なら1）
define('LINE_BOT_DEFAULT_CATEGORY_ID', (int)($_ENV['LINE_BOT_DEFAULT_CATEGORY_ID'] ?? 1));

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
// 金額変換（PHP計算）
// =============================================================

function isSalesJob($text) {
    $keywords = ['営業', '獲得', '推奨販売', 'アポイント獲得', 'クロージング', '光AD', '通信販売', '法人営業'];
    foreach ($keywords as $kw) {
        if (mb_strpos($text, $kw) !== false) {
            return true;
        }
    }
    return false;
}

function convertHourly($amount) {
    return max($amount - 600, 1300);
}

function convertDaily($amount) {
    return max($amount - 3000, 12000);
}

function convertMonthly($amount, $isSales) {
    return max($amount - 70000, $isSales ? 320000 : 270000);
}

function formatJpnAmount($amount, $useMan = false) {
    if ($useMan && $amount >= 10000 && $amount % 10000 === 0) {
        return ($amount / 10000) . '万';
    }
    return number_format($amount);
}

/**
 * テキスト中の金額を変換する
 */
function convertPricesInText($text, $isSales) {
    // 時給レンジ
    $text = preg_replace_callback(
        '/時給\s*([0-9,]+)\s*円?\s*[〜~～ー―－]\s*([0-9,]+)\s*円/u',
        function ($m) {
            $min = convertHourly((int)str_replace(',', '', $m[1]));
            $max = convertHourly((int)str_replace(',', '', $m[2]));
            return '時給' . number_format($min) . '円〜' . number_format($max) . '円';
        },
        $text
    );
    // 時給単独
    $text = preg_replace_callback(
        '/時給\s*([0-9,]+)\s*円/u',
        function ($m) {
            return '時給' . number_format(convertHourly((int)str_replace(',', '', $m[1]))) . '円';
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
    // 日当単独
    $text = preg_replace_callback(
        '/(日当|日給)\s*([0-9,]+)\s*円/u',
        function ($m) {
            return $m[1] . number_format(convertDaily((int)str_replace(',', '', $m[2]))) . '円';
        },
        $text
    );

    // 月給万円レンジ
    $text = preg_replace_callback(
        '/(月給|月収)\s*([0-9]+)\s*万\s*円?\s*[〜~～ー―－]\s*([0-9]+)\s*万\s*円?/u',
        function ($m) use ($isSales) {
            $min = convertMonthly((int)$m[2] * 10000, $isSales);
            $max = convertMonthly((int)$m[3] * 10000, $isSales);
            return $m[1] . formatJpnAmount($min, true) . '円〜' . formatJpnAmount($max, true) . '円';
        },
        $text
    );
    // 月給万円単独
    $text = preg_replace_callback(
        '/(月給|月収)\s*([0-9]+)\s*万\s*円/u',
        function ($m) use ($isSales) {
            return $m[1] . formatJpnAmount(convertMonthly((int)$m[2] * 10000, $isSales), true) . '円';
        },
        $text
    );

    // 月給数字レンジ
    $text = preg_replace_callback(
        '/(月給|月収)\s*([0-9,]+)\s*円?\s*[〜~～ー―－]\s*([0-9,]+)\s*円/u',
        function ($m) use ($isSales) {
            $min = convertMonthly((int)str_replace(',', '', $m[2]), $isSales);
            $max = convertMonthly((int)str_replace(',', '', $m[3]), $isSales);
            return $m[1] . number_format($min) . '円〜' . number_format($max) . '円';
        },
        $text
    );
    // 月給数字単独
    $text = preg_replace_callback(
        '/(月給|月収)\s*([0-9,]+)\s*円/u',
        function ($m) use ($isSales) {
            return $m[1] . number_format(convertMonthly((int)str_replace(',', '', $m[2]), $isSales)) . '円';
        },
        $text
    );

    return $text;
}

/**
 * テキストから金額（円単位）を抽出する
 * budget_min / budget_max 用
 */
function extractBudgetFromText($text, $isSales) {
    $amounts = [];

    // 時給
    if (preg_match_all('/時給\s*([0-9,]+)\s*円/u', $text, $matches)) {
        foreach ($matches[1] as $m) {
            $amounts[] = convertHourly((int)str_replace(',', '', $m));
        }
    }
    // 日当
    if (preg_match_all('/(日当|日給)\s*([0-9,]+)\s*円/u', $text, $matches)) {
        foreach ($matches[2] as $m) {
            $amounts[] = convertDaily((int)str_replace(',', '', $m));
        }
    }
    // 月給（万）
    if (preg_match_all('/(月給|月収)\s*([0-9]+)\s*万/u', $text, $matches)) {
        foreach ($matches[2] as $m) {
            $amounts[] = convertMonthly((int)$m * 10000, $isSales);
        }
    }
    // 月給（数字）
    if (preg_match_all('/(月給|月収)\s*([0-9,]+)\s*円/u', $text, $matches)) {
        foreach ($matches[2] as $m) {
            $val = (int)str_replace(',', '', $m);
            if ($val > 10000) { // 万円表記と重複しないよう
                $amounts[] = convertMonthly($val, $isSales);
            }
        }
    }

    if (empty($amounts)) {
        return ['min' => 0, 'max' => 0];
    }

    return ['min' => min($amounts), 'max' => max($amounts)];
}

// =============================================================
// Gemini API 呼び出し（求人判定 + JSON構造化）
// =============================================================
function callGeminiForJobParse($inputText) {
    $systemPrompt = <<<'EOT'
あなたは求人案件の解析アシスタントです。

【ステップ1：求人判定】
入力テキストが「求人・募集案件の情報」かどうかを判定してください。
以下は求人情報ではありません：
- 雑談、挨拶、質問、相談
- ニュース、お知らせ、連絡事項
- 金額や給与の記載がないテキスト
求人情報でない場合は以下のJSONのみ出力：
{"is_job": false}

【ステップ2：求人情報を構造化】
求人情報と判定した場合、以下のJSON形式で出力してください。
金額は元の表記のまま変更せずに記載してください。
読み取れない項目はnullにしてください。

{
  "is_job": true,
  "title": "案件タイトル（簡潔に30文字以内）",
  "description": "以下の形式で整形した案件説明文（改行を含むテキスト）：\n\n【仕事内容】\n・業務内容1\n・業務内容2\n\n【給与・報酬】\n時給/日当/月給の情報（元の金額のまま）\n\n【勤務地】\n勤務地情報\n\n【勤務時間・期間】\n勤務時間や期間の情報\n\n【応募条件】\n必要なスキル・経験\n\n【待遇・その他】\n交通費・福利厚生など",
  "location": "勤務地（都道府県＋市区町村程度）",
  "duration_weeks": 4,
  "urgency": "medium"
}

【出力ルール】
- JSONのみ出力（説明・前置き・コードフェンス不要）
- descriptionの中は改行(\n)を使って整形
- 金額は一切変更しない（システムが後で変換する）
- titleは簡潔に（「募集」「スタッフ」など不要な語は省略可）
- duration_weeksは記載がなければ4（デフォルト）
- urgencyは「急募」等あればhigh、通常はmedium
- 元の情報を追加・創作しない
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
            'responseMimeType' => 'application/json',
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

    // JSON抽出
    $text = trim($text);
    if (strpos($text, '```') !== false) {
        $text = preg_replace('/```[a-zA-Z]*\n?|```/', '', $text);
    }
    $start = strpos($text, '{');
    $end = strrpos($text, '}');
    if ($start !== false && $end !== false && $end > $start) {
        $text = substr($text, $start, $end - $start + 1);
    }

    $parsed = json_decode($text, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        lineLog("Gemini JSON parse error: " . json_last_error_msg() . " / text: " . mb_substr($text, 0, 200));
        return null;
    }

    return $parsed;
}

// =============================================================
// DB投稿
// =============================================================
function postJobToDatabase($jobData) {
    try {
        $db = Database::getInstance();

        $title = mb_substr($jobData['title'] ?? '新規案件', 0, 200);
        $description = $jobData['description'] ?? '';
        $location = $jobData['location'] ?? null;
        $durationWeeks = (int)($jobData['duration_weeks'] ?? 4);
        $urgency = $jobData['urgency'] ?? 'medium';
        $budgetMin = (int)($jobData['budget_min'] ?? 0);
        $budgetMax = (int)($jobData['budget_max'] ?? 0);

        if ($durationWeeks < 1) $durationWeeks = 4;
        if ($durationWeeks > 52) $durationWeeks = 52;
        if (!in_array($urgency, ['low', 'medium', 'high'])) $urgency = 'medium';

        $db->beginTransaction();

        $jobId = $db->insert("
            INSERT INTO jobs (
                client_id, title, description, category_id,
                budget_min, budget_max, duration_weeks,
                required_skills, location, urgency
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ", [
            LINE_BOT_CLIENT_ID,
            $title,
            $description,
            LINE_BOT_DEFAULT_CATEGORY_ID,
            $budgetMin,
            $budgetMax,
            $durationWeeks,
            json_encode([]),
            $location,
            $urgency,
        ]);

        $db->commit();

        // Discord通知
        try {
            notifyNewJobToDiscord(
                $jobId, $title, $budgetMin, $budgetMax,
                $urgency, LINE_BOT_DEFAULT_CATEGORY_ID
            );
        } catch (Exception $e) {
            lineLog("Discord notify failed: " . $e->getMessage());
        }

        return $jobId;

    } catch (Exception $e) {
        if (isset($db)) {
            try { $db->rollback(); } catch (Exception $ex) {}
        }
        lineLog("DB insert failed: " . $e->getMessage());
        return null;
    }
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
if ($secretSet) {
    if (!verifyLineSignature($body, DEMI_LINE_CHANNEL_SECRET)) {
        lineLog("Signature verification FAILED");
        http_response_code(200);
        echo 'OK';
        exit;
    }
}

// 設定チェック
if (empty(DEMI_LINE_CHANNEL_ACCESS_TOKEN) || empty(GEMINI_API_KEY)) {
    lineLog("Missing config - aborting");
    http_response_code(200);
    echo 'OK';
    exit;
}

// イベント解析
$events = json_decode($body, true);
if (!$events || !isset($events['events'])) {
    http_response_code(200);
    echo 'OK';
    exit;
}

foreach ($events['events'] as $i => $event) {
    if (($event['type'] ?? '') !== 'message' || ($event['message']['type'] ?? '') !== 'text') {
        continue;
    }

    $replyToken = $event['replyToken'] ?? '';
    $userText = $event['message']['text'] ?? '';
    $sourceType = $event['source']['type'] ?? '';

    if (empty($userText) || empty($replyToken)) {
        continue;
    }

    if (mb_strlen($userText) < 30) {
        lineLog("Event[{$i}]: skipped (too short)");
        continue;
    }

    // ステップ1: Geminiで求人判定 + 構造化JSON取得
    $parsed = callGeminiForJobParse($userText);

    if ($parsed === null) {
        lineLog("Event[{$i}]: Gemini API error");
        continue;
    }

    if (empty($parsed['is_job'])) {
        lineLog("Event[{$i}]: skipped (NOT_JOB)");
        continue;
    }

    lineLog("Event[{$i}]: job detected - title: " . ($parsed['title'] ?? ''));

    // ステップ2: PHPで金額変換（description内のテキスト）
    $isSales = isSalesJob($userText);
    if (!empty($parsed['description'])) {
        $parsed['description'] = convertPricesInText($parsed['description'], $isSales);
    }

    // ステップ3: budget_min / budget_max を元テキストから抽出・変換
    $budget = extractBudgetFromText($userText, $isSales);
    $parsed['budget_min'] = $budget['min'];
    $parsed['budget_max'] = $budget['max'];

    lineLog("Event[{$i}]: budget min={$budget['min']} max={$budget['max']} isSales=" . ($isSales ? 'yes' : 'no'));

    // ステップ4: DBに案件投稿
    $jobId = postJobToDatabase($parsed);

    if ($jobId === null) {
        lineReply($replyToken, [[
            'type' => 'text',
            'text' => "案件の投稿に失敗しました。",
        ]]);
        continue;
    }

    $jobUrl = rtrim(BASE_URL, '/') . '/job-detail?id=' . $jobId;
    lineLog("Event[{$i}]: job posted - id={$jobId} url={$jobUrl}");

    // LINEに投稿完了通知
    lineReply($replyToken, [[
        'type' => 'text',
        'text' => "✅ 案件を投稿しました！\n\n📋 {$parsed['title']}\n🔗 {$jobUrl}",
    ]]);
}

http_response_code(200);
echo 'OK';
