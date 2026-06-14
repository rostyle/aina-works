<?php
/**
 * AiNA Works DBバックアップ Web起動エンドポイント
 *
 * GitHub Actions のスケジュールワークフローから毎日叩かれ、本番サーバー上で
 * バックアップを実行する。トークン認証（タイミング攻撃耐性のある比較）で保護。
 *
 * トークンの渡し方（いずれか。ヘッダー推奨＝アクセスログに残らない）:
 *   - HTTPヘッダー: X-Backup-Token: <token>
 *   - クエリ文字列: ?token=<token>   ※ログに残るため非推奨
 *
 * 本番 .env に次を設定すること:
 *   BACKUP_TRIGGER_TOKEN=<十分に長いランダム文字列>
 *
 * トークン未設定（空）の場合は安全側に倒して常に拒否する。
 */

header('Content-Type: text/plain; charset=utf-8');

$basePath = dirname(__DIR__);
require __DIR__ . '/lib-backup.php';

// 期待トークンを .env から取得
$env = backup_load_env($basePath . '/.env');
$expected = $env['BACKUP_TRIGGER_TOKEN'] ?? '';

// 受信トークン（ヘッダー優先、なければクエリ）
$provided = '';
if (!empty($_SERVER['HTTP_X_BACKUP_TOKEN'])) {
    $provided = $_SERVER['HTTP_X_BACKUP_TOKEN'];
} elseif (isset($_GET['token'])) {
    $provided = $_GET['token'];
}

// 認証: トークン未設定 or 不一致は拒否（hash_equalsで定数時間比較）
if ($expected === '' || $provided === '' || !hash_equals($expected, $provided)) {
    http_response_code(403);
    exit("forbidden\n");
}

// Web実行でも止まらないように
@set_time_limit(0);
@ignore_user_abort(true);

try {
    $result = run_backup($basePath);
    http_response_code(200);
    echo "ok\n";
    foreach ($result['lines'] as $line) {
        echo $line;
    }
} catch (Throwable $e) {
    $log = $basePath . '/backup/backup.log';
    backup_log($log, 'ERROR(web): ' . $e->getMessage());
    http_response_code(500);
    echo "error: " . $e->getMessage() . "\n";
}
