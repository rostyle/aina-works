<?php
require_once 'config/config.php';

$db = Database::getInstance();
$job = $db->fetch("
    SELECT j.id, j.title, j.budget_min, j.budget_max, j.urgency, j.category_id
    FROM jobs j
    ORDER BY j.created_at DESC
    LIMIT 1
");

if (!$job) {
    echo "案件が見つかりません。\n";
    exit(1);
}

echo "送信中: [{$job['id']}] {$job['title']}\n";

notifyNewJobToDiscord(
    $job['id'],
    $job['title'],
    $job['budget_min'],
    $job['budget_max'],
    $job['urgency'],
    $job['category_id']
);

echo "送信完了！\n";
