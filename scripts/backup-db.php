<?php
/**
 * AiNA Works データベース自動バックアップ（CLI / cron 用エントリ）
 *
 * 想定環境: ロリポップ！共用レンタルサーバーの cron（定期実行）
 * 本体ロジックは lib-backup.php に集約。Web起動版は backup-trigger.php。
 *
 * cron 登録例（ロリポップ 管理パネル > cron設定）:
 *   /usr/local/bin/php /home/users/＜番号＞/＜アカウント＞/web/aina-works/scripts/backup-db.php
 *   実行頻度: 毎日1回（例: 午前4時）
 *
 * 手動テスト:
 *   php scripts/backup-db.php
 */

// Web経由の実行を禁止（cron/CLI専用）
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('This script can only be run from the command line.');
}

require __DIR__ . '/lib-backup.php';

try {
    $result = run_backup(dirname(__DIR__));
    foreach ($result['lines'] as $line) {
        echo $line; // cron のメール通知に出力
    }
    exit(0);
} catch (Throwable $e) {
    $log = dirname(__DIR__) . '/backup/backup.log';
    echo backup_log($log, 'ERROR: ' . $e->getMessage());
    exit(1);
}
