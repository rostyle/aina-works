<?php
/**
 * AiNA Works データベース自動バックアップスクリプト
 *
 * 想定環境: ロリポップ！共用レンタルサーバー（cron / 定期実行）
 *
 * 動作:
 *   1. mysqldump が利用可能ならそれでダンプ（推奨・高速・確実）
 *   2. 利用不可なら純PHP（PDO）でダンプにフォールバック
 *   3. 出力を gzip 圧縮して backup/db/ に保存
 *   4. 保持世代（既定14日）を超えた古いバックアップを自動削除
 *   5. 結果を backup/backup.log に記録
 *
 * cron 登録例（ロリポップ 管理パネル > cron設定）:
 *   実行コマンド: /usr/bin/php /home/users/x/アカウント名/web/aina-works/scripts/backup-db.php
 *   実行頻度    : 毎日 1回（例: 午前4時）
 *   ※ PHPのパス・プロジェクトの絶対パスは環境に合わせて変更してください。
 *
 * 手動テスト（SSH / ローカル）:
 *   php scripts/backup-db.php
 *
 * セキュリティ:
 *   - CLI（コマンドライン）からのみ実行可能。Web経由のアクセスは拒否します。
 *   - 保存先 backup/ には .htaccess を自動設置し、Web公開を遮断します。
 */

// ── Web経由の実行を禁止（cron/CLI専用） ─────────────────────────
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('This script can only be run from the command line.');
}

// ── 設定 ─────────────────────────────────────────────────────
const RETENTION_DAYS = 14;          // 保持世代（日数）。これより古いバックアップは削除
const DB_CHARSET     = 'utf8mb4';

$BASE_PATH   = dirname(__DIR__);
$BACKUP_DIR  = $BASE_PATH . '/backup/db';
$LOG_FILE    = $BASE_PATH . '/backup/backup.log';

// ── 簡易ロガー ───────────────────────────────────────────────
function backup_log($logFile, $message) {
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;
    @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
    echo $line; // cron のメール通知にも出力される
}

// ── .env を最小構成で読み込む（config.php の副作用を避けるため自前パース） ──
function load_env($path) {
    $env = [];
    if (!is_file($path)) {
        return $env;
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
            continue;
        }
        [$name, $value] = explode('=', $line, 2);
        $env[trim($name)] = trim($value);
    }
    return $env;
}

// ── バックアップ保存先の準備＆Web公開遮断 ──────────────────────
function prepare_backup_dir($backupDir, $basePath) {
    if (!is_dir($backupDir) && !mkdir($backupDir, 0700, true) && !is_dir($backupDir)) {
        throw new RuntimeException('バックアップディレクトリを作成できません: ' . $backupDir);
    }
    // backup/ 直下に .htaccess と index.html を設置してWebアクセスを遮断
    $protectDir = dirname($backupDir);
    $htaccess = $protectDir . '/.htaccess';
    if (!is_file($htaccess)) {
        @file_put_contents($htaccess, "Require all denied\nDeny from all\n");
    }
    $indexHtml = $protectDir . '/index.html';
    if (!is_file($indexHtml)) {
        @file_put_contents($indexHtml, '');
    }
}

// ── 方法A: mysqldump によるダンプ ─────────────────────────────
function dump_with_mysqldump($env, $tmpSqlFile) {
    // exec が使えるか
    if (!function_exists('exec')) {
        return [false, 'exec関数が無効です'];
    }
    $disabled = array_map('trim', explode(',', (string)ini_get('disable_functions')));
    if (in_array('exec', $disabled, true)) {
        return [false, 'exec関数がdisable_functionsで無効化されています'];
    }
    // mysqldump の存在確認
    $verOut = [];
    $verRet = 1;
    @exec('mysqldump --version 2>/dev/null', $verOut, $verRet);
    if ($verRet !== 0) {
        return [false, 'mysqldumpコマンドが見つかりません'];
    }

    // パスワードをコマンドラインに出さないよう、一時の defaults-extra-file を使う
    $cnfFile = tempnam(sys_get_temp_dir(), 'aina_my_');
    if ($cnfFile === false) {
        return [false, '一時設定ファイルを作成できません'];
    }
    @chmod($cnfFile, 0600);
    $cnf = "[client]\n"
         . 'host=' . ($env['DB_HOST'] ?? 'localhost') . "\n"
         . 'user=' . ($env['DB_USER'] ?? 'root') . "\n"
         . 'password="' . str_replace('"', '\"', ($env['DB_PASS'] ?? '')) . "\"\n";
    file_put_contents($cnfFile, $cnf);

    $dbName = $env['DB_NAME'] ?? 'aina_works';

    $cmd = 'mysqldump'
         . ' --defaults-extra-file=' . escapeshellarg($cnfFile)
         . ' --single-transaction'      // InnoDBをロックせず一貫性のあるダンプ
         . ' --quick'                    // 行を逐次取得（メモリ節約）
         . ' --no-tablespaces'           // PROCESS権限不要（共用サーバー向け）
         . ' --routines --triggers --events'
         . ' --default-character-set=' . escapeshellarg(DB_CHARSET)
         . ' --result-file=' . escapeshellarg($tmpSqlFile)
         . ' ' . escapeshellarg($dbName)
         . ' 2>&1';

    $out = [];
    $ret = 1;
    @exec($cmd, $out, $ret);
    @unlink($cnfFile);

    if ($ret !== 0) {
        // routines/events の権限が無い環境向けに、オプションを落として再試行
        $cnfFile2 = tempnam(sys_get_temp_dir(), 'aina_my_');
        @chmod($cnfFile2, 0600);
        file_put_contents($cnfFile2, $cnf);
        $cmd2 = 'mysqldump'
             . ' --defaults-extra-file=' . escapeshellarg($cnfFile2)
             . ' --single-transaction --quick --no-tablespaces'
             . ' --default-character-set=' . escapeshellarg(DB_CHARSET)
             . ' --result-file=' . escapeshellarg($tmpSqlFile)
             . ' ' . escapeshellarg($dbName)
             . ' 2>&1';
        $out = [];
        $ret = 1;
        @exec($cmd2, $out, $ret);
        @unlink($cnfFile2);
        if ($ret !== 0) {
            @unlink($tmpSqlFile);
            return [false, 'mysqldump失敗: ' . trim(implode(' / ', $out))];
        }
    }

    if (!is_file($tmpSqlFile) || filesize($tmpSqlFile) === 0) {
        return [false, 'mysqldumpの出力が空です'];
    }
    return [true, 'mysqldump'];
}

// ── 方法B: 純PHP（PDO）によるダンプ（フォールバック） ──────────
function dump_with_php($env, $gzFile) {
    $host = $env['DB_HOST'] ?? 'localhost';
    $name = $env['DB_NAME'] ?? 'aina_works';
    $user = $env['DB_USER'] ?? 'root';
    $pass = $env['DB_PASS'] ?? '';

    $dsn = "mysql:host=$host;dbname=$name;charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_NUM,
    ]);

    $gz = gzopen($gzFile, 'wb9');
    if ($gz === false) {
        throw new RuntimeException('gzファイルを開けません: ' . $gzFile);
    }

    $header = "-- AiNA Works DB backup (PHP fallback)\n"
            . '-- Database: ' . $name . "\n"
            . '-- Generated: ' . date('Y-m-d H:i:s') . "\n\n"
            . "SET NAMES " . DB_CHARSET . ";\n"
            . "SET FOREIGN_KEY_CHECKS=0;\n\n";
    gzwrite($gz, $header);

    // テーブル一覧
    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $table) {
        $quoted = '`' . str_replace('`', '``', $table) . '`';

        // CREATE TABLE
        $create = $pdo->query("SHOW CREATE TABLE $quoted")->fetch(PDO::FETCH_NUM);
        gzwrite($gz, "DROP TABLE IF EXISTS $quoted;\n");
        gzwrite($gz, $create[1] . ";\n\n");

        // データ行を逐次取得してINSERT文を生成（バッチ書き込み）
        $stmt = $pdo->query("SELECT * FROM $quoted");
        $batch = [];
        $batchSize = 100;
        while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
            $vals = [];
            foreach ($row as $v) {
                if ($v === null) {
                    $vals[] = 'NULL';
                } elseif (is_numeric($v) && !preg_match('/^0[0-9]/', (string)$v)) {
                    // 先頭ゼロの文字列（電話番号等）は数値化しない
                    $vals[] = $v;
                } else {
                    $vals[] = $pdo->quote($v);
                }
            }
            $batch[] = '(' . implode(',', $vals) . ')';
            if (count($batch) >= $batchSize) {
                gzwrite($gz, "INSERT INTO $quoted VALUES\n" . implode(",\n", $batch) . ";\n");
                $batch = [];
            }
        }
        if ($batch) {
            gzwrite($gz, "INSERT INTO $quoted VALUES\n" . implode(",\n", $batch) . ";\n");
        }
        gzwrite($gz, "\n");
    }

    gzwrite($gz, "SET FOREIGN_KEY_CHECKS=1;\n");
    gzclose($gz);

    if (!is_file($gzFile) || filesize($gzFile) === 0) {
        throw new RuntimeException('PHPダンプの出力が空です');
    }
}

// ── .sql を gzip 圧縮（メモリ節約のためストリーム処理） ──────────
function gzip_file($srcFile, $gzFile) {
    $in = fopen($srcFile, 'rb');
    if ($in === false) {
        throw new RuntimeException('一時SQLファイルを開けません');
    }
    $gz = gzopen($gzFile, 'wb9');
    if ($gz === false) {
        fclose($in);
        throw new RuntimeException('gzファイルを開けません');
    }
    while (!feof($in)) {
        $chunk = fread($in, 1 << 20); // 1MBずつ
        if ($chunk === false) {
            break;
        }
        gzwrite($gz, $chunk);
    }
    fclose($in);
    gzclose($gz);
}

// ── 古いバックアップの削除（保持世代を超えたもの） ──────────────
function rotate_backups($backupDir, $retentionDays, $logFile) {
    $threshold = time() - ($retentionDays * 86400);
    $deleted = 0;
    foreach (glob($backupDir . '/*.sql.gz') ?: [] as $file) {
        if (filemtime($file) < $threshold) {
            if (@unlink($file)) {
                $deleted++;
                backup_log($logFile, '削除（保持期限超過）: ' . basename($file));
            }
        }
    }
    return $deleted;
}

// ── メイン処理 ───────────────────────────────────────────────
$startedAt = microtime(true);
try {
    prepare_backup_dir($BACKUP_DIR, $BASE_PATH);

    $env = load_env($BASE_PATH . '/.env');
    if (empty($env['DB_NAME'])) {
        backup_log($LOG_FILE, 'WARN: .envにDB設定が見つかりません。既定値で続行します。');
    }

    $timestamp = date('Y-m-d_His');
    $gzFile    = $BACKUP_DIR . '/aina_works_' . $timestamp . '.sql.gz';
    $tmpSql    = $BACKUP_DIR . '/.tmp_' . $timestamp . '.sql';

    // 方法A → 失敗時は方法B
    [$ok, $info] = dump_with_mysqldump($env, $tmpSql);
    if ($ok) {
        gzip_file($tmpSql, $gzFile);
        @unlink($tmpSql);
        $method = 'mysqldump';
    } else {
        @unlink($tmpSql);
        backup_log($LOG_FILE, 'mysqldump不可のためPHPダンプにフォールバック（理由: ' . $info . '）');
        dump_with_php($env, $gzFile);
        $method = 'php-pdo';
    }

    @chmod($gzFile, 0600);
    $sizeMb = round(filesize($gzFile) / 1048576, 2);
    $elapsed = round(microtime(true) - $startedAt, 1);
    backup_log($LOG_FILE, sprintf(
        'OK: %s 方式=%s サイズ=%sMB 所要=%ss',
        basename($gzFile), $method, $sizeMb, $elapsed
    ));

    $deleted = rotate_backups($BACKUP_DIR, RETENTION_DAYS, $LOG_FILE);
    backup_log($LOG_FILE, sprintf('完了: 保持%d日 / 今回削除%d件', RETENTION_DAYS, $deleted));

    exit(0);
} catch (Throwable $e) {
    backup_log($LOG_FILE, 'ERROR: ' . $e->getMessage());
    exit(1);
}
