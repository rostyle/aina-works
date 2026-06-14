<?php
/**
 * AiNA Works DBバックアップ 共通ロジック
 *
 * CLI（scripts/backup-db.php）とWeb起動（scripts/backup-trigger.php）の
 * 双方から利用される。直接アクセスされても何も実行しない（関数定義のみ）。
 */

if (!defined('AINA_BACKUP_RETENTION_DAYS')) {
    define('AINA_BACKUP_RETENTION_DAYS', 14);   // 保持世代（日数）。これより古いものは削除
}
if (!defined('AINA_BACKUP_CHARSET')) {
    define('AINA_BACKUP_CHARSET', 'utf8mb4');
}

// ── 簡易ロガー ───────────────────────────────────────────────
function backup_log($logFile, $message) {
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;
    @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
    return $line;
}

// ── .env を最小構成で読み込む（config.php の副作用を避ける自前パース） ──
function backup_load_env($path) {
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
function backup_prepare_dir($backupDir) {
    if (!is_dir($backupDir) && !mkdir($backupDir, 0700, true) && !is_dir($backupDir)) {
        throw new RuntimeException('バックアップディレクトリを作成できません: ' . $backupDir);
    }
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
function backup_dump_with_mysqldump($env, $tmpSqlFile) {
    if (!function_exists('exec')) {
        return [false, 'exec関数が無効です'];
    }
    $disabled = array_map('trim', explode(',', (string)ini_get('disable_functions')));
    if (in_array('exec', $disabled, true)) {
        return [false, 'exec関数がdisable_functionsで無効化されています'];
    }
    $verOut = [];
    $verRet = 1;
    @exec('mysqldump --version 2>/dev/null', $verOut, $verRet);
    if ($verRet !== 0) {
        return [false, 'mysqldumpコマンドが見つかりません'];
    }

    // パスワードをコマンドラインに出さないよう一時 defaults-extra-file を使用
    $cnf = "[client]\n"
         . 'host=' . ($env['DB_HOST'] ?? 'localhost') . "\n"
         . 'user=' . ($env['DB_USER'] ?? 'root') . "\n"
         . 'password="' . str_replace('"', '\"', ($env['DB_PASS'] ?? '')) . "\"\n";
    $dbName = $env['DB_NAME'] ?? 'aina_works';

    $base = ' --single-transaction --quick --no-tablespaces'
          . ' --default-character-set=' . escapeshellarg(AINA_BACKUP_CHARSET);

    // 1回目: ルーチン/トリガー/イベント込み → 失敗時は外して再試行
    foreach ([' --routines --triggers --events', ''] as $extra) {
        $cnfFile = tempnam(sys_get_temp_dir(), 'aina_my_');
        if ($cnfFile === false) {
            return [false, '一時設定ファイルを作成できません'];
        }
        @chmod($cnfFile, 0600);
        file_put_contents($cnfFile, $cnf);

        $cmd = 'mysqldump'
             . ' --defaults-extra-file=' . escapeshellarg($cnfFile)
             . $base . $extra
             . ' --result-file=' . escapeshellarg($tmpSqlFile)
             . ' ' . escapeshellarg($dbName)
             . ' 2>&1';
        $out = [];
        $ret = 1;
        @exec($cmd, $out, $ret);
        @unlink($cnfFile);

        if ($ret === 0 && is_file($tmpSqlFile) && filesize($tmpSqlFile) > 0) {
            return [true, 'mysqldump'];
        }
        @unlink($tmpSqlFile);
        $lastErr = trim(implode(' / ', $out));
    }
    return [false, 'mysqldump失敗: ' . ($lastErr ?? '不明')];
}

// ── 方法B: 純PHP（PDO）によるダンプ（フォールバック） ──────────
function backup_dump_with_php($env, $gzFile) {
    $host = $env['DB_HOST'] ?? 'localhost';
    $name = $env['DB_NAME'] ?? 'aina_works';
    $user = $env['DB_USER'] ?? 'root';
    $pass = $env['DB_PASS'] ?? '';

    $dsn = "mysql:host=$host;dbname=$name;charset=" . AINA_BACKUP_CHARSET;
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_NUM,
    ]);

    $gz = gzopen($gzFile, 'wb9');
    if ($gz === false) {
        throw new RuntimeException('gzファイルを開けません: ' . $gzFile);
    }

    gzwrite($gz, "-- AiNA Works DB backup (PHP fallback)\n"
        . '-- Database: ' . $name . "\n"
        . '-- Generated: ' . date('Y-m-d H:i:s') . "\n\n"
        . 'SET NAMES ' . AINA_BACKUP_CHARSET . ";\n"
        . "SET FOREIGN_KEY_CHECKS=0;\n\n");

    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $table) {
        $quoted = '`' . str_replace('`', '``', $table) . '`';

        $create = $pdo->query("SHOW CREATE TABLE $quoted")->fetch(PDO::FETCH_NUM);
        gzwrite($gz, "DROP TABLE IF EXISTS $quoted;\n");
        gzwrite($gz, $create[1] . ";\n\n");

        $stmt = $pdo->query("SELECT * FROM $quoted");
        $batch = [];
        while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
            $vals = [];
            foreach ($row as $v) {
                if ($v === null) {
                    $vals[] = 'NULL';
                } elseif (is_numeric($v) && !preg_match('/^0[0-9]/', (string)$v)) {
                    $vals[] = $v; // 先頭ゼロの文字列（電話番号等）は数値化しない
                } else {
                    $vals[] = $pdo->quote($v);
                }
            }
            $batch[] = '(' . implode(',', $vals) . ')';
            if (count($batch) >= 100) {
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

// ── .sql を gzip 圧縮（ストリーム処理） ───────────────────────
function backup_gzip_file($srcFile, $gzFile) {
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
        $chunk = fread($in, 1 << 20);
        if ($chunk === false) {
            break;
        }
        gzwrite($gz, $chunk);
    }
    fclose($in);
    gzclose($gz);
}

// ── 古いバックアップの削除 ─────────────────────────────────────
function backup_rotate($backupDir, $retentionDays, $logFile, &$out) {
    $threshold = time() - ($retentionDays * 86400);
    $deleted = 0;
    foreach (glob($backupDir . '/*.sql.gz') ?: [] as $file) {
        if (filemtime($file) < $threshold && @unlink($file)) {
            $deleted++;
            $out[] = backup_log($logFile, '削除（保持期限超過）: ' . basename($file));
        }
    }
    return $deleted;
}

/**
 * バックアップ本体を実行する。
 *
 * @param string $basePath プロジェクトルート
 * @return array ['lines'=>string[], 'file'=>string, 'method'=>string, 'sizeMb'=>float, 'deleted'=>int]
 * @throws Throwable 失敗時
 */
function run_backup($basePath) {
    $backupDir = $basePath . '/backup/db';
    $logFile   = $basePath . '/backup/backup.log';
    $out = [];

    $startedAt = microtime(true);
    backup_prepare_dir($backupDir);

    $env = backup_load_env($basePath . '/.env');
    if (empty($env['DB_NAME'])) {
        $out[] = backup_log($logFile, 'WARN: .envにDB設定が見つかりません。既定値で続行します。');
    }

    $timestamp = date('Y-m-d_His');
    $gzFile    = $backupDir . '/aina_works_' . $timestamp . '.sql.gz';
    $tmpSql    = $backupDir . '/.tmp_' . $timestamp . '.sql';

    [$ok, $info] = backup_dump_with_mysqldump($env, $tmpSql);
    if ($ok) {
        backup_gzip_file($tmpSql, $gzFile);
        @unlink($tmpSql);
        $method = 'mysqldump';
    } else {
        @unlink($tmpSql);
        $out[] = backup_log($logFile, 'mysqldump不可のためPHPダンプにフォールバック（理由: ' . $info . '）');
        backup_dump_with_php($env, $gzFile);
        $method = 'php-pdo';
    }

    @chmod($gzFile, 0600);
    $sizeMb  = round(filesize($gzFile) / 1048576, 2);
    $elapsed = round(microtime(true) - $startedAt, 1);
    $out[] = backup_log($logFile, sprintf(
        'OK: %s 方式=%s サイズ=%sMB 所要=%ss',
        basename($gzFile), $method, $sizeMb, $elapsed
    ));

    $deleted = backup_rotate($backupDir, AINA_BACKUP_RETENTION_DAYS, $logFile, $out);
    $out[] = backup_log($logFile, sprintf('完了: 保持%d日 / 今回削除%d件', AINA_BACKUP_RETENTION_DAYS, $deleted));

    return [
        'lines'  => $out,
        'file'   => basename($gzFile),
        'method' => $method,
        'sizeMb' => $sizeMb,
        'deleted'=> $deleted,
    ];
}
