<?php
// php -S 用ルーター（.htaccess の拒否・拡張子なしURL・/work/・sitemap.xml を再現）
$root = dirname(__DIR__);
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$rel = ltrim($path, '/');
if (preg_match('#^(includes|config|sql|vendor|logs|backup|tests)/#', $rel) || preg_match('#^storage/(logs|framework|app/uploads/chat)/#', $rel) || preg_match('#^storage/.*\.(php\d?|phtml|phar|pht)$#i', $rel) || preg_match('#(^|/)\.#', $rel)) { http_response_code(403); echo "403"; return true; }
if ($rel === 'sitemap.xml') { $_SERVER['SCRIPT_NAME']='/sitemap.php'; require $root.'/sitemap.php'; return true; }
if (preg_match('#^works?/?$#', $rel)) { $_SERVER['SCRIPT_NAME']='/works.php'; require $root.'/works.php'; return true; }
if ($rel === '' ) { $_SERVER['SCRIPT_NAME']='/index.php'; require $root.'/index.php'; return true; }
$file = $root.'/'.$rel;
if (is_file($file) && substr($file,-4) !== '.php') return false;
if (is_dir($file) && is_file($file.'/index.php')) { $file = rtrim($file,'/').'/index.php'; }
elseif (!is_file($file) && is_file(rtrim($file,'/').'.php')) { $file = rtrim($file,'/').'.php'; }
if (is_file($file) && substr($file,-4)==='.php') { chdir(dirname($file)); $_SERVER['SCRIPT_NAME'] = substr($file, strlen($root)); $_SERVER['SCRIPT_FILENAME']=$file; require $file; return true; }
http_response_code(404); chdir($root); require $root.'/404.php'; return true;
