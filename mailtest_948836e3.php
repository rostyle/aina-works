<?php
/**
 * 一時メール送信テストページ（本番確認用）
 * ------------------------------------------------------------
 * - トークンで保護（第三者の悪用防止）
 * - 宛先は rostyle95@gmail.com に固定
 * - 確認後、必ずこのファイルを削除すること
 *
 * アクセス例:
 *   https://（本番ドメイン）/mailtest_948836e3.php?token=948836e30a2af06a26e5ad3448a78edd
 */

header('Content-Type: text/plain; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

// ---- トークン保護 ----
const TEST_TOKEN = '948836e30a2af06a26e5ad3448a78edd';
if (($_GET['token'] ?? '') !== TEST_TOKEN) {
    http_response_code(404);
    echo "Not Found\n";
    exit;
}

// ---- 宛先は固定（悪用防止）----
$to = 'rostyle95@gmail.com';

echo "=== メール送信テスト ===\n";
echo "実行日時: " . date('Y-m-d H:i:s') . "\n";
echo "サーバー: " . ($_SERVER['SERVER_NAME'] ?? '?') . " (" . php_uname('n') . ")\n\n";

// ---- .env を探索して読み込み ----
function findAndLoadEnv() {
    $candidates = [
        __DIR__ . '/.env',
        __DIR__ . '/../.env',
        __DIR__ . '/../../.env',
        __DIR__ . '/config/.env',
    ];
    foreach ($candidates as $path) {
        if (is_file($path)) {
            $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos(trim($line), '#') === 0 || strpos($line, '=') === false) continue;
                [$k, $v] = explode('=', $line, 2);
                $k = trim($k); $v = trim($v);
                if ($k !== '' && !array_key_exists($k, $_ENV)) $_ENV[$k] = $v;
            }
            return $path;
        }
    }
    return null;
}
$envPath = findAndLoadEnv();
echo ".env: " . ($envPath ? "読み込み成功 ({$envPath})" : "見つかりません（既定値を使用）") . "\n\n";

// ---- 設定値 ----
$host = $_ENV['MAIL_HOST'] ?? 'smtp.lolipop.jp';
$port = (int)($_ENV['MAIL_PORT'] ?? 465);
$user = $_ENV['MAIL_USERNAME'] ?? '';
$pass = $_ENV['MAIL_PASSWORD'] ?? '';
$enc  = $_ENV['MAIL_ENCRYPTION'] ?? 'smtps';
$from = $_ENV['MAIL_FROM_ADDRESS'] ?? 'info@aina-works.com';
$fromName = $_ENV['MAIL_FROM_NAME'] ?? 'AiNA Works';

echo "MAIL_HOST: {$host}\n";
echo "MAIL_PORT: {$port}\n";
echo "MAIL_USERNAME: {$user}\n";
echo "MAIL_PASSWORD: " . ($pass !== '' ? '(設定あり / ' . strlen($pass) . '文字)' : '(空！)') . "\n";
echo "MAIL_ENCRYPTION: {$enc}\n";
echo "MAIL_FROM: {$from} ({$fromName})\n\n";

// ---- PHPMailer を探索 ----
$autoloads = [
    __DIR__ . '/vendor/autoload.php',
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../../vendor/autoload.php',
];
$loaded = false;
foreach ($autoloads as $a) {
    if (is_file($a)) { require_once $a; $loaded = true; echo "PHPMailer: {$a}\n\n"; break; }
}
if (!$loaded || !class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
    echo "❌ PHPMailer が見つかりません。vendor/autoload.php の場所を確認してください。\n";
    exit;
}

// ---- 送信テスト ----
$mail = new PHPMailer\PHPMailer\PHPMailer(true);
$mail->SMTPDebug   = 2;
$mail->Debugoutput = 'echo';
try {
    $mail->isSMTP();
    $mail->Host       = $host;
    $mail->SMTPAuth   = true;
    $mail->Username   = $user;
    $mail->Password   = $pass;
    $mail->SMTPSecure = ($enc === 'tls')
        ? PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS
        : PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port    = $port;
    $mail->CharSet = 'UTF-8';
    $mail->setFrom($from, $fromName);
    $mail->addAddress($to);
    $mail->Subject = '【本番テスト】AiNA メール送信確認 ' . date('Y-m-d H:i:s');
    $mail->Body    = "本番環境からのメール送信テストです。\n送信元サーバー: " . php_uname('n') . "\n日時: " . date('Y-m-d H:i:s');
    $mail->send();
    echo "\n\n✅ 送信成功: {$to} に送信しました。受信箱（および迷惑メール）を確認してください。\n";
} catch (Exception $e) {
    echo "\n\n❌ 送信失敗: " . $mail->ErrorInfo . "\n";
}
