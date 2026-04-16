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

// LINE Bot投稿者のユーザーID
define('LINE_BOT_CLIENT_ID', 112);
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

/** 全角数字・全角カンマを正規化（給与表記パース用） */
function salary_normalize_text($text) {
    static $fw = '０１２３４５６７８９';
    static $as = '0123456789';
    $text = strtr($text, $fw, $as);
    return str_replace('，', ',', $text);
}

/** 年収（万円）→ 月額（円・概算） */
function annualManToMonthlyYen($man) {
    return (int)round((float)$man * 10000 / 12);
}

/**
 * テキスト中の金額を変換する
 */
function convertPricesInText($text, $isSales) {
    $text = salary_normalize_text($text);

    // --- 金額が先・単位が後（レンジは単独より先） ---
    // 〇〇円/時
    $text = preg_replace_callback(
        '/([0-9,]+)\s*円?\s*[〜~～ー―－]\s*([0-9,]+)\s*円\s*[/／]\s*(?:時|ｈ|H)/u',
        function ($m) {
            $min = convertHourly((int)str_replace(',', '', $m[1]));
            $max = convertHourly((int)str_replace(',', '', $m[2]));
            return '時給' . number_format($min) . '円〜' . number_format($max) . '円';
        },
        $text
    );
    $text = preg_replace_callback(
        '/([0-9,]+)\s*円\s*[/／]\s*(?:時|ｈ|H)/u',
        function ($m) {
            return '時給' . number_format(convertHourly((int)str_replace(',', '', $m[1]))) . '円';
        },
        $text
    );
    // 〇〇円/日
    $text = preg_replace_callback(
        '/([0-9,]+)\s*円?\s*[〜~～ー―－]\s*([0-9,]+)\s*円\s*[/／]\s*日/u',
        function ($m) {
            $min = convertDaily((int)str_replace(',', '', $m[1]));
            $max = convertDaily((int)str_replace(',', '', $m[2]));
            return '日給' . number_format($min) . '円〜' . number_format($max) . '円';
        },
        $text
    );
    $text = preg_replace_callback(
        '/([0-9,]+)\s*円\s*[/／]\s*日/u',
        function ($m) {
            return '日給' . number_format(convertDaily((int)str_replace(',', '', $m[1]))) . '円';
        },
        $text
    );
    // 〇〇万/月（円省略）
    $text = preg_replace_callback(
        '/([0-9]+)\s*万\s*[/／]\s*月/u',
        function ($m) use ($isSales) {
            return '月給' . formatJpnAmount(convertMonthly((int)$m[1] * 10000, $isSales), true) . '円';
        },
        $text
    );
    // 〇〇円/月
    $text = preg_replace_callback(
        '/([0-9,]+)\s*円?\s*[〜~～ー―－]\s*([0-9,]+)\s*円\s*[/／]\s*月/u',
        function ($m) use ($isSales) {
            $min = convertMonthly((int)str_replace(',', '', $m[1]), $isSales);
            $max = convertMonthly((int)str_replace(',', '', $m[2]), $isSales);
            return '月給' . number_format($min) . '円〜' . number_format($max) . '円';
        },
        $text
    );
    $text = preg_replace_callback(
        '/([0-9,]+)\s*円\s*[/／]\s*月/u',
        function ($m) use ($isSales) {
            return '月給' . number_format(convertMonthly((int)str_replace(',', '', $m[1]), $isSales)) . '円';
        },
        $text
    );
    $text = preg_replace_callback(
        '/([0-9]+)\s*万\s*円\s*[/／]\s*月/u',
        function ($m) use ($isSales) {
            return '月給' . formatJpnAmount(convertMonthly((int)$m[1] * 10000, $isSales), true) . '円';
        },
        $text
    );

    // ＠1,500円（アルバイト表記の時給）
    $text = preg_replace_callback(
        '/[@＠]\s*([0-9,]+)\s*円/u',
        function ($m) {
            return '時給' . number_format(convertHourly((int)str_replace(',', '', $m[1]))) . '円';
        },
        $text
    );

    // --- ラベルが先（コロン・全角スペース可） ---
    $lblHour = '(時給|時間額)';
    $lblDay = '(日当|日給|日額)';
    $lblMonth = '(月給|月収|月額|基本給|支給額)';

    // 時給レンジ・単独
    $text = preg_replace_callback(
        '/' . $lblHour . '[：:\s]*([0-9,]+)\s*円?\s*[〜~～ー―－]\s*([0-9,]+)\s*円/u',
        function ($m) {
            $min = convertHourly((int)str_replace(',', '', $m[2]));
            $max = convertHourly((int)str_replace(',', '', $m[3]));
            return $m[1] . number_format($min) . '円〜' . number_format($max) . '円';
        },
        $text
    );
    $text = preg_replace_callback(
        '/' . $lblHour . '[：:\s]*([0-9,]+)\s*円/u',
        function ($m) {
            return $m[1] . number_format(convertHourly((int)str_replace(',', '', $m[2]))) . '円';
        },
        $text
    );

    // 日当レンジ・単独
    $text = preg_replace_callback(
        '/' . $lblDay . '[：:\s]*([0-9,]+)\s*円?\s*[〜~～ー―－]\s*([0-9,]+)\s*円/u',
        function ($m) {
            $min = convertDaily((int)str_replace(',', '', $m[2]));
            $max = convertDaily((int)str_replace(',', '', $m[3]));
            return $m[1] . number_format($min) . '円〜' . number_format($max) . '円';
        },
        $text
    );
    $text = preg_replace_callback(
        '/' . $lblDay . '[：:\s]*([0-9,]+)\s*円/u',
        function ($m) {
            return $m[1] . number_format(convertDaily((int)str_replace(',', '', $m[2]))) . '円';
        },
        $text
    );

    // 年収・年俸（万円）→ 月換算で表示
    $text = preg_replace_callback(
        '/(?:想定|見込み|予定)?(?:年収|年俸)[：:\s]*([0-9]+)\s*万\s*円?\s*[〜~～ー―－]\s*([0-9]+)\s*万\s*円?/u',
        function ($m) use ($isSales) {
            $min = convertMonthly(annualManToMonthlyYen($m[1]), $isSales);
            $max = convertMonthly(annualManToMonthlyYen($m[2]), $isSales);
            return '月給（年収換算）' . number_format($min) . '円〜' . number_format($max) . '円';
        },
        $text
    );
    $text = preg_replace_callback(
        '/(?:想定|見込み|予定)?(?:年収|年俸)[：:\s]*([0-9]+)\s*万(?:\s*円)?/u',
        function ($m) use ($isSales) {
            $y = convertMonthly(annualManToMonthlyYen($m[1]), $isSales);
            return '月給（年収換算）' . number_format($y) . '円';
        },
        $text
    );

    // 月給万円レンジ・単独（「万」の後ろに円省略可）
    $text = preg_replace_callback(
        '/' . $lblMonth . '[：:\s]*([0-9]+)\s*万\s*円?\s*[〜~～ー―－]\s*([0-9]+)\s*万\s*円?/u',
        function ($m) use ($isSales) {
            $min = convertMonthly((int)$m[2] * 10000, $isSales);
            $max = convertMonthly((int)$m[3] * 10000, $isSales);
            return $m[1] . formatJpnAmount($min, true) . '円〜' . formatJpnAmount($max, true) . '円';
        },
        $text
    );
    $text = preg_replace_callback(
        '/' . $lblMonth . '[：:\s]*([0-9]+)\s*万(?:\s*円)?/u',
        function ($m) use ($isSales) {
            return $m[1] . formatJpnAmount(convertMonthly((int)$m[2] * 10000, $isSales), true) . '円';
        },
        $text
    );

    // 月給数字レンジ・単独（円単位）
    $text = preg_replace_callback(
        '/' . $lblMonth . '[：:\s]*([0-9,]+)\s*円?\s*[〜~～ー―－]\s*([0-9,]+)\s*円/u',
        function ($m) use ($isSales) {
            $min = convertMonthly((int)str_replace(',', '', $m[2]), $isSales);
            $max = convertMonthly((int)str_replace(',', '', $m[3]), $isSales);
            return $m[1] . number_format($min) . '円〜' . number_format($max) . '円';
        },
        $text
    );
    $text = preg_replace_callback(
        '/' . $lblMonth . '[：:\s]*([0-9,]+)\s*円/u',
        function ($m) use ($isSales) {
            return $m[1] . number_format(convertMonthly((int)str_replace(',', '', $m[2]), $isSales)) . '円';
        },
        $text
    );

    // 給与：25万円（ラベル別）
    $text = preg_replace_callback(
        '/給与[：:\s]*([0-9]+)\s*万\s*円/u',
        function ($m) use ($isSales) {
            return '給与' . formatJpnAmount(convertMonthly((int)$m[1] * 10000, $isSales), true) . '円';
        },
        $text
    );
    $text = preg_replace_callback(
        '/給与[：:\s]*([0-9,]+)\s*円/u',
        function ($m) use ($isSales) {
            return '給与' . number_format(convertMonthly((int)str_replace(',', '', $m[1]), $isSales)) . '円';
        },
        $text
    );

    return $text;
}

/**
 * テキストから金額（円単位）を抽出する
 * budget_min / budget_max 用（convertPricesInText と同系統の表記を網羅）
 */
function extractBudgetFromText($text, $isSales) {
    $text = salary_normalize_text($text);
    $amounts = [];

    // 時給・時間額（レンジ→単独）
    if (preg_match_all('/(?:時給|時間額)[：:\s]*([0-9,]+)\s*円?\s*[〜~～ー―－]\s*([0-9,]+)\s*円/u', $text, $matches)) {
        foreach ($matches[1] as $i => $_) {
            $amounts[] = convertHourly((int)str_replace(',', '', $matches[1][$i]));
            $amounts[] = convertHourly((int)str_replace(',', '', $matches[2][$i]));
        }
    }
    if (preg_match_all('/(?:時給|時間額)[：:\s]*([0-9,]+)\s*円/u', $text, $matches)) {
        foreach ($matches[1] as $m) {
            $amounts[] = convertHourly((int)str_replace(',', '', $m));
        }
    }
    // 〇〇円〜〇〇円/時・〇〇円/時
    if (preg_match_all('/([0-9,]+)\s*円?\s*[〜~～ー―－]\s*([0-9,]+)\s*円\s*[/／]\s*(?:時|ｈ|H)/u', $text, $matches)) {
        foreach ($matches[1] as $i => $_) {
            $amounts[] = convertHourly((int)str_replace(',', '', $matches[1][$i]));
            $amounts[] = convertHourly((int)str_replace(',', '', $matches[2][$i]));
        }
    }
    if (preg_match_all('/([0-9,]+)\s*円\s*[/／]\s*(?:時|ｈ|H)/u', $text, $matches)) {
        foreach ($matches[1] as $m) {
            $amounts[] = convertHourly((int)str_replace(',', '', $m));
        }
    }
    // ＠〇〇円
    if (preg_match_all('/[@＠]\s*([0-9,]+)\s*円/u', $text, $matches)) {
        foreach ($matches[1] as $m) {
            $amounts[] = convertHourly((int)str_replace(',', '', $m));
        }
    }

    // 日当・日給・日額（レンジ→単独）
    if (preg_match_all('/(?:日当|日給|日額)[：:\s]*([0-9,]+)\s*円?\s*[〜~～ー―－]\s*([0-9,]+)\s*円/u', $text, $matches)) {
        foreach ($matches[1] as $i => $_) {
            $amounts[] = convertDaily((int)str_replace(',', '', $matches[1][$i]));
            $amounts[] = convertDaily((int)str_replace(',', '', $matches[2][$i]));
        }
    }
    if (preg_match_all('/(?:日当|日給|日額)[：:\s]*([0-9,]+)\s*円/u', $text, $matches)) {
        foreach ($matches[1] as $m) {
            $amounts[] = convertDaily((int)str_replace(',', '', $m));
        }
    }
    // 〇〇円〜〇〇円/日・〇〇円/日
    if (preg_match_all('/([0-9,]+)\s*円?\s*[〜~～ー―－]\s*([0-9,]+)\s*円\s*[/／]\s*日/u', $text, $matches)) {
        foreach ($matches[1] as $i => $_) {
            $amounts[] = convertDaily((int)str_replace(',', '', $matches[1][$i]));
            $amounts[] = convertDaily((int)str_replace(',', '', $matches[2][$i]));
        }
    }
    if (preg_match_all('/([0-9,]+)\s*円\s*[/／]\s*日/u', $text, $matches)) {
        foreach ($matches[1] as $m) {
            $amounts[] = convertDaily((int)str_replace(',', '', $m));
        }
    }

    // 月：ラベル＋万（レンジ→単独）
    if (preg_match_all('/(?:月給|月収|月額|基本給|支給額)[：:\s]*([0-9]+)\s*万\s*円?\s*[〜~～ー―－]\s*([0-9]+)\s*万/u', $text, $matches)) {
        foreach ($matches[1] as $i => $_) {
            $amounts[] = convertMonthly((int)$matches[1][$i] * 10000, $isSales);
            $amounts[] = convertMonthly((int)$matches[2][$i] * 10000, $isSales);
        }
    }
    if (preg_match_all('/(?:月給|月収|月額|基本給|支給額)[：:\s]*([0-9]+)\s*万/u', $text, $matches)) {
        foreach ($matches[1] as $m) {
            $amounts[] = convertMonthly((int)$m * 10000, $isSales);
        }
    }
    // 〇万/月・〇万円/月
    if (preg_match_all('/([0-9]+)\s*万\s*[/／]\s*月/u', $text, $matches)) {
        foreach ($matches[1] as $m) {
            $amounts[] = convertMonthly((int)$m * 10000, $isSales);
        }
    }
    if (preg_match_all('/([0-9]+)\s*万\s*円\s*[/／]\s*月/u', $text, $matches)) {
        foreach ($matches[1] as $m) {
            $amounts[] = convertMonthly((int)$m * 10000, $isSales);
        }
    }
    // 〇〇円〜〇〇円/月・〇〇円/月
    if (preg_match_all('/([0-9,]+)\s*円?\s*[〜~～ー―－]\s*([0-9,]+)\s*円\s*[/／]\s*月/u', $text, $matches)) {
        foreach ($matches[1] as $i => $_) {
            $amounts[] = convertMonthly((int)str_replace(',', '', $matches[1][$i]), $isSales);
            $amounts[] = convertMonthly((int)str_replace(',', '', $matches[2][$i]), $isSales);
        }
    }
    if (preg_match_all('/([0-9,]+)\s*円\s*[/／]\s*月/u', $text, $matches)) {
        foreach ($matches[1] as $m) {
            $amounts[] = convertMonthly((int)str_replace(',', '', $m), $isSales);
        }
    }
    // 月給・月収…の円レンジ→単独（万表記と二重にならないよう大きい額のみ）
    if (preg_match_all('/(?:月給|月収|月額|基本給|支給額)[：:\s]*([0-9,]+)\s*円?\s*[〜~～ー―－]\s*([0-9,]+)\s*円/u', $text, $matches)) {
        foreach ($matches[1] as $i => $_) {
            $amounts[] = convertMonthly((int)str_replace(',', '', $matches[1][$i]), $isSales);
            $amounts[] = convertMonthly((int)str_replace(',', '', $matches[2][$i]), $isSales);
        }
    }
    if (preg_match_all('/(?:月給|月収|月額|基本給|支給額)[：:\s]*([0-9,]+)\s*円/u', $text, $matches)) {
        foreach ($matches[1] as $m) {
            $val = (int)str_replace(',', '', $m);
            if ($val > 10000) {
                $amounts[] = convertMonthly($val, $isSales);
            }
        }
    }

    // 給与：25万円 / 給与250000円
    if (preg_match_all('/給与[：:\s]*([0-9]+)\s*万\s*円/u', $text, $matches)) {
        foreach ($matches[1] as $m) {
            $amounts[] = convertMonthly((int)$m * 10000, $isSales);
        }
    }
    if (preg_match_all('/給与[：:\s]*([0-9,]+)\s*円/u', $text, $matches)) {
        foreach ($matches[1] as $m) {
            $val = (int)str_replace(',', '', $m);
            if ($val > 10000) {
                $amounts[] = convertMonthly($val, $isSales);
            }
        }
    }

    // 年収・年俸（万円→月換算）レンジ→単独の順
    if (preg_match_all('/(?:想定|見込み|予定)?(?:年収|年俸)[：:\s]*([0-9]+)\s*万\s*円?\s*[〜~～ー―－]\s*([0-9]+)\s*万/u', $text, $matches)) {
        foreach ($matches[1] as $i => $_) {
            $amounts[] = convertMonthly(annualManToMonthlyYen($matches[1][$i]), $isSales);
            $amounts[] = convertMonthly(annualManToMonthlyYen($matches[2][$i]), $isSales);
        }
    }
    if (preg_match_all('/(?:想定|見込み|予定)?(?:年収|年俸)[：:\s]*([0-9]+)\s*万/u', $text, $matches)) {
        foreach ($matches[1] as $m) {
            $amounts[] = convertMonthly(annualManToMonthlyYen($m), $isSales);
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
