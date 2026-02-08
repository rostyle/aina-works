<?php
// 本番環境デバッグ用スクリプト (Production Debug Script v2)
// このファイルをFTPで本番環境のルートディレクトリ（index.phpと同じ場所）にアップロードし、
// ブラウザで https://your-domain.com/debug_production_stories_v2.php にアクセスしてください。

// エラーの詳細表示を有効化
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>body{font-family:sans-serif;padding:20px;line-height:1.6}h2{border-bottom:2px solid #333;padding-bottom:10px;margin-top:30px}.pass{color:green;font-weight:bold}.fail{color:red;font-weight:bold}.warn{color:orange;font-weight:bold}pre{background:#f4f4f4;padding:10px;border:1px solid #ddd;overflow-x:auto}</style></head><body>';
echo '<h1>Production Environment Diagnostic</h1>';
echo '<p>Server Time: ' . date('r') . '</p>';

// 1. Check Configuration File
echo '<h2>1. Configuration File Check</h2>';
if (file_exists('config/config.php')) {
    echo '<p class="pass">✅ config/config.php exists.</p>';
    require_once 'config/config.php';
} else {
    echo '<p class="fail">❌ config/config.php NOT FOUND!</p>';
    die('Cannot proceed without config file.');
}

// 2. Database Connection Test
echo '<h2>2. Database Connection Test</h2>';
try {
    require_once 'includes/database.php';
    $db = Database::getInstance();
    echo '<p class="pass">✅ Database connection successful.</p>';
} catch (Exception $e) {
    echo '<p class="fail">❌ Database connection failed: ' . htmlspecialchars($e->getMessage()) . '</p>';
    die('Cannot proceed without database connection.');
}

// 3. Table Schema & Data Check
echo '<h2>3. Data Verification</h2>';
try {
    // Total Count
    $total = $db->selectOne("SELECT COUNT(*) as c FROM success_stories");
    echo "<p>Total articles in table: <strong>{$total['c']}</strong></p>";

    // Status Distribution
    $statuses = $db->select("SELECT status, COUNT(*) as c FROM success_stories GROUP BY status");
    echo '<p><strong>Status Distribution:</strong></p><ul>';
    $published_count = 0;
    foreach ($statuses as $row) {
        echo "<li>Status '{$row['status']}': {$row['c']} articles</li>";
        if ($row['status'] === 'published') {
            $published_count = $row['c'];
        }
    }
    echo '</ul>';

    if ($published_count === 0) {
        echo '<p class="fail">⚠️  CRITICAL: No articles have status "published"!</p>';
        echo '<p>Please go to Admin Panel > Success Stories and check the "Status" field. It might be empty or "draft".</p>';
    } else {
        echo "<p class=\"pass\">✅ Found {$published_count} published articles.</p>";
    }

} catch (Exception $e) {
    echo '<p class="fail">❌ SQL Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
}

// 4. Test Public Query (Crucial Step)
echo '<h2>4. Public Page Query Test</h2>';
try {
    $sql = "SELECT id, title, status, interview_date FROM success_stories WHERE status = 'published' ORDER BY interview_date DESC";
    echo "<pre>Running Query: {$sql}</pre>";
    
    $results = $db->select($sql);
    $count = count($results);
    
    if ($count > 0) {
        echo "<p class=\"pass\">✅ Query returned {$count} results.</p>";
        echo '<table border="1" cellpadding="5" cellspacing="0">';
        echo '<tr><th>ID</th><th>Title</th><th>Status</th><th>Date</th></tr>';
        foreach ($results as $row) {
            echo "<tr>";
            echo "<td>{$row['id']}</td>";
            echo "<td>" . htmlspecialchars($row['title']) . "</td>";
            echo "<td>{$row['status']}</td>";
            echo "<td>{$row['interview_date']}</td>";
            echo "</tr>";
        }
        echo '</table>';
    } else {
        echo '<p class="warn">⚠️  Query returned 0 results. The page will show "No articles found".</p>';
    }

} catch (Exception $e) {
    echo '<p class="fail">❌ SQL Query Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
}

// 5. File Integrity Check
echo '<h2>5. File Check (success-stories.php)</h2>';
$target_file = 'success-stories.php';
if (file_exists($target_file)) {
    echo "<p class=\"pass\">✅ {$target_file} exists.</p>";
    echo "<p>Last Modified: " . date('Y-m-d H:i:s', filemtime($target_file)) . "</p>";
    echo "<p>Size: " . filesize($target_file) . " bytes</p>";
    
    // Check content for query mismatch
    $content = file_get_contents($target_file);
    if (strpos($content, "WHERE status = 'published'") === false) {
        echo '<p class="warn">⚠️  WARNING: The query in the file might be different from the verified query.</p>';
    } else {
         echo '<p class="pass">✅ Query pattern found in file.</p>';
    }
} else {
    echo "<p class=\"fail\">❌ {$target_file} NOT FOUND!</p>";
}

echo '</body></html>';
