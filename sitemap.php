<?php
/**
 * sitemap.xml（.htaccess で /sitemap.xml → このファイル）
 * 公開してよいページだけ：トップ・一覧・公開作品・募集中の案件・クリエイター・公開インタビュー
 */
require_once __DIR__ . '/config/config.php';

$base = rtrim(BASE_URL, '/');
$urls = [
    ['loc' => $base . '/', 'changefreq' => 'daily'],
    ['loc' => $base . '/jobs', 'changefreq' => 'daily'],
    ['loc' => $base . '/work/', 'changefreq' => 'daily'],
    ['loc' => $base . '/creators', 'changefreq' => 'weekly'],
    ['loc' => $base . '/success-stories', 'changefreq' => 'weekly'],
    ['loc' => $base . '/lp-outsourcing', 'changefreq' => 'monthly'],
    ['loc' => $base . '/terms', 'changefreq' => 'yearly'],
    ['loc' => $base . '/privacy', 'changefreq' => 'yearly'],
];

// updated_at が無いテーブルでも出せるよう、取れなければIDだけで出す
$rowsFor = function (string $table, string $where) {
    $db = Database::getInstance();
    try {
        return $db->select("SELECT id, updated_at FROM {$table} WHERE {$where} ORDER BY id DESC LIMIT 5000");
    } catch (Throwable $e) {
        try {
            return $db->select("SELECT id FROM {$table} WHERE {$where} ORDER BY id DESC LIMIT 5000");
        } catch (Throwable $e2) {
            error_log('sitemap ' . $table . ': ' . $e2->getMessage());
            return [];
        }
    }
};
foreach ([
    ['works', "status = 'published'", '/work-detail?id='],
    ['jobs', "status = 'open'", '/job-detail?id='],
    ['users', "is_active = 1 AND is_creator = 1", '/creator-profile?id='],
    ['success_stories', "status = 'published'", '/success-story-detail?id='],
] as [$table, $where, $path]) {
    foreach ($rowsFor($table, $where) as $r) {
        $urls[] = ['loc' => $base . $path . (int)$r['id'], 'lastmod' => $r['updated_at'] ?? null];
    }
}

while (ob_get_level()) {
    ob_end_clean();
}
header('Content-Type: application/xml; charset=utf-8');
header('Cache-Control: public, max-age=3600');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($urls as $u) {
    echo '  <url><loc>' . htmlspecialchars($u['loc'], ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</loc>';
    if (!empty($u['lastmod']) && ($t = strtotime($u['lastmod']))) {
        echo '<lastmod>' . date('Y-m-d', $t) . '</lastmod>';
    }
    if (!empty($u['changefreq'])) {
        echo '<changefreq>' . $u['changefreq'] . '</changefreq>';
    }
    echo "</url>\n";
}
echo "</urlset>\n";
