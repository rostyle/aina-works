<?php
/**
 * ローカル検証用の初期データ（sql/database.sql を流した直後に実行）
 *   php tests/seed.php
 * 本番の古いスキーマ（jobs.status が4値のENUM・募集人数の列なし）を再現し、
 * 採用時の ensureJobWorkflowSchema() が列を整える経路も一緒に試す。
 */
$pdo = new PDO('mysql:host=127.0.0.1;dbname=aina_works;charset=utf8mb4', getenv('DB_USER') ?: 'aina', getenv('DB_PASS') ?: 'aina', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$pdo->exec("ALTER TABLE jobs DROP COLUMN hiring_limit, DROP COLUMN is_recruiting");
$pdo->exec("ALTER TABLE jobs MODIFY status ENUM('open','closed','completed','cancelled') NOT NULL DEFAULT 'open'");

$pw = password_hash('Passw0rd!', PASSWORD_DEFAULT);
$pdo->exec("INSERT INTO categories (name,color,sort_order) VALUES ('画像・デザイン','#3B82F6',1),('動画編集・制作','#10B981',2)");
$ins = $pdo->prepare("INSERT INTO users (email,full_name,aina_user_id,password_hash,is_creator,is_client,birthdate) VALUES (?,?,?,?,?,?,?)");
$ins->execute(['admin@example.com', '管理者', null, $pw, 0, 0, '1990-01-01']);            // 1 ローカル専用の管理者
$ins->execute(['creator@example.com', 'クリエイター太郎', '1001', null, 1, 0, '1990-01-01']); // 2 会員・クリエイター
$ins->execute(['client@example.com', '依頼者花子', '1002', null, 0, 1, '1985-01-01']);      // 3 会員・依頼者
$ins->execute(['norole@example.com', '未設定次郎', '1003', null, 0, 0, null]);             // 4 会員・役割未設定
$ins->execute(['quit@example.com', '退会三郎', '1004', $pw, 1, 0, '1990-01-01']);           // 5 退会済み会員（ローカルパスワードあり）
$ins->execute(['localclient@example.com', 'ローカル依頼社', '', $pw, 0, 1, '1980-01-01']);   // 6 ローカル専用の依頼者
$pdo->exec("INSERT INTO user_roles (user_id,role,is_enabled) VALUES (1,'admin',1)");
$pdo->exec("INSERT INTO works (user_id,title,description,category_id,price_min,price_max,main_image,status) VALUES (2,'バナー制作','AIで作るバナー https://example.com',1,1000,5000,'sample.jpg','published')");
$pdo->exec("INSERT INTO jobs (client_id,category_id,title,description,budget_min,budget_max,urgency,status,deadline) VALUES (3,1,'LPのバナー作成','新商品LPのバナーを3枚作ってほしいです。サイズは1200x628。',10000,30000,'medium','open',DATE_ADD(CURDATE(), INTERVAL 14 DAY))");
$pdo->exec("INSERT INTO jobs (client_id,category_id,title,description,budget_min,budget_max,urgency,status,deadline) VALUES (3,1,'締切なし案件','説明文説明文説明文説明文説明文説明文説明文説明文説明文説明文説明文説明文説明文',5000,10000,'low','open',NULL)");
$pdo->exec("INSERT INTO jobs (client_id,category_id,title,description,budget_min,budget_max,urgency,status,deadline) VALUES (3,1,'完了済み案件','説明文説明文説明文説明文説明文説明文説明文説明文説明文説明文説明文説明文説明文',5000,10000,'low','completed',NULL)");
$pdo->exec("INSERT INTO success_stories (title,category_name,tag_type,interview_date,intro_text,member_name,status) VALUES ('未経験から3ヶ月で初受注','デザイン・画像','副業からスタート','2026-09-01','1行目\n2行目','山田','published')");
echo "seeded\n";
