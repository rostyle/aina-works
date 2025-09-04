<?php
/**
 * SQLite用データベース初期化スクリプト
 */

require_once 'config/config.php';

try {
    $db = Database::getInstance();
    $pdo = $db->getPdo();
    
    echo "SQLiteデータベース初期化を開始します...\n";
    
    // 外部キー制約を有効化
    $pdo->exec('PRAGMA foreign_keys = ON');
    
    // ユーザーテーブル
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            full_name VARCHAR(100) NOT NULL,
            nickname VARCHAR(50),
            user_type TEXT NOT NULL CHECK (user_type IN ('creator', 'client', 'sales')),
            profile_image VARCHAR(255),
            bio TEXT,
            location VARCHAR(100),
            website VARCHAR(255),
            twitter_url VARCHAR(255),
            instagram_url VARCHAR(255),
            facebook_url VARCHAR(255),
            linkedin_url VARCHAR(255),
            youtube_url VARCHAR(255),
            tiktok_url VARCHAR(255),
            response_time INTEGER DEFAULT 24,
            experience_years INTEGER DEFAULT 0,
            hourly_rate INTEGER DEFAULT 0,
            is_pro INTEGER DEFAULT 0,
            is_verified INTEGER DEFAULT 0,
            is_active INTEGER DEFAULT 1,
            created_at DATETIME DEFAULT (datetime('now')),
            updated_at DATETIME DEFAULT (datetime('now'))
        )
    ");
    echo "✓ usersテーブル作成完了\n";
    
    // パスワードリセット用テーブル
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS password_resets (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            email VARCHAR(100) NOT NULL,
            token VARCHAR(255) NOT NULL UNIQUE,
            expires_at DATETIME NOT NULL,
            used_at DATETIME NULL,
            created_at DATETIME DEFAULT (datetime('now'))
        )
    ");
    echo "✓ password_resetsテーブル作成完了\n";
    
    // カテゴリテーブル
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS categories (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(50) NOT NULL,
            slug VARCHAR(50) UNIQUE NOT NULL,
            description TEXT,
            icon VARCHAR(100),
            color VARCHAR(7) DEFAULT '#3B82F6',
            sort_order INTEGER DEFAULT 0,
            is_active INTEGER DEFAULT 1,
            created_at DATETIME DEFAULT (datetime('now'))
        )
    ");
    echo "✓ categoriesテーブル作成完了\n";
    
    // スキルテーブル
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS skills (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(50) NOT NULL,
            category_id INTEGER,
            is_active INTEGER DEFAULT 1,
            created_at DATETIME DEFAULT (datetime('now')),
            FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
        )
    ");
    echo "✓ skillsテーブル作成完了\n";
    
    // ユーザースキルテーブル
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS user_skills (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            skill_id INTEGER NOT NULL,
            proficiency TEXT DEFAULT 'intermediate' CHECK (proficiency IN ('beginner', 'intermediate', 'advanced', 'expert')),
            created_at DATETIME DEFAULT (datetime('now')),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (skill_id) REFERENCES skills(id) ON DELETE CASCADE,
            UNIQUE(user_id, skill_id)
        )
    ");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_user_skills_user_id ON user_skills(user_id);");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_user_skills_skill_id ON user_skills(skill_id);");
    echo "✓ user_skillsテーブル作成完了\n";
    
    // 作品テーブル
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS works (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            title VARCHAR(200) NOT NULL,
            description TEXT,
            category_id INTEGER,
            price_min INTEGER DEFAULT 0,
            price_max INTEGER DEFAULT 0,
            duration_weeks INTEGER DEFAULT 1,
            main_image VARCHAR(255),
            images TEXT,
            tags TEXT,
            technologies TEXT,
            project_url VARCHAR(255),
            is_featured INTEGER DEFAULT 0,
            view_count INTEGER DEFAULT 0,
            like_count INTEGER DEFAULT 0,
            status TEXT DEFAULT 'draft' CHECK (status IN ('draft', 'published', 'archived')),
            created_at DATETIME DEFAULT (datetime('now')),
            updated_at DATETIME DEFAULT (datetime('now')),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
        )
    ");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_works_user_id ON works(user_id);");
    echo "✓ worksテーブル作成完了\n";
    
    // 案件テーブル
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS jobs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            client_id INTEGER NOT NULL,
            title VARCHAR(200) NOT NULL,
            description TEXT NOT NULL,
            category_id INTEGER,
            budget_min INTEGER DEFAULT 0,
            budget_max INTEGER DEFAULT 0,
            duration_weeks INTEGER DEFAULT 1,
            required_skills TEXT,
            location VARCHAR(100),
            remote_ok INTEGER DEFAULT 1,
            urgency TEXT DEFAULT 'medium' CHECK (urgency IN ('low', 'medium', 'high')),
            applications_count INTEGER DEFAULT 0,
            status TEXT DEFAULT 'open' CHECK (status IN ('open', 'in_progress', 'completed', 'cancelled')),
            deadline DATE,
            created_at DATETIME DEFAULT (datetime('now')),
            updated_at DATETIME DEFAULT (datetime('now')),
            FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
        )
    ");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_jobs_client_id ON jobs(client_id);");
    echo "✓ jobsテーブル作成完了\n";
    
    // 案件応募テーブル
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS job_applications (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            job_id INTEGER NOT NULL,
            creator_id INTEGER NOT NULL,
            cover_letter TEXT,
            proposed_price INTEGER,
            proposed_duration INTEGER,
            status TEXT DEFAULT 'pending' CHECK (status IN ('pending', 'accepted', 'rejected', 'withdrawn')),
            created_at DATETIME DEFAULT (datetime('now')),
            updated_at DATETIME DEFAULT (datetime('now')),
            FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
            FOREIGN KEY (creator_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE(job_id, creator_id)
        )
    ");
    echo "✓ job_applicationsテーブル作成完了\n";
    
    // レビューテーブル
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS reviews (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            reviewer_id INTEGER NOT NULL,
            reviewee_id INTEGER NOT NULL,
            job_id INTEGER,
            work_id INTEGER,
            rating INTEGER NOT NULL CHECK (rating >= 1 AND rating <= 5),
            comment TEXT,
            created_at DATETIME DEFAULT (datetime('now')),
            FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (reviewee_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE SET NULL,
            FOREIGN KEY (work_id) REFERENCES works(id) ON DELETE SET NULL
        )
    ");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_reviews_reviewee_id ON reviews(reviewee_id);");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_reviews_reviewer_id ON reviews(reviewer_id);");
    echo "✓ reviewsテーブル作成完了\n";
    
    // メッセージテーブル
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS messages (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            sender_id INTEGER NOT NULL,
            receiver_id INTEGER NOT NULL,
            subject VARCHAR(200),
            content TEXT NOT NULL,
            is_read INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT (datetime('now')),
            FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    echo "✓ messagesテーブル作成完了\n";
    
    // お気に入りテーブル
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS favorites (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            target_type TEXT NOT NULL CHECK (target_type IN ('work', 'creator')),
            target_id INTEGER NOT NULL,
            created_at DATETIME DEFAULT (datetime('now')),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE(user_id, target_type, target_id)
        )
    ");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_favorites_user_id ON favorites(user_id);");
    echo "✓ favoritesテーブル作成完了\n";
    
    // 初期データ挿入
    echo "\n初期データを挿入中...\n";
    
    // カテゴリ初期データ
    $pdo->exec("
        INSERT OR IGNORE INTO categories (name, slug, description, icon, color, sort_order) VALUES
        ('ロゴ制作', 'logo-design', 'ブランドアイデンティティを表現するロゴデザイン', '🎨', '#EF4444', 1),
        ('ライティング', 'writing', 'コンテンツライティング・コピーライティング', '✍️', '#3B82F6', 2),
        ('Web制作', 'web-development', 'Webサイト・アプリケーション開発', '💻', '#10B981', 3),
        ('動画編集', 'video-editing', '動画制作・編集・モーション', '🎬', '#8B5CF6', 4),
        ('AI漫画', 'ai-manga', 'AI技術を活用した漫画・イラスト制作', '🤖', '#F59E0B', 5),
        ('音楽制作', 'music-production', '楽曲制作・音響デザイン', '🎵', '#EC4899', 6)
    ");
    echo "✓ カテゴリ初期データ挿入完了\n";
    
    // スキル初期データ
    $pdo->exec("
        INSERT OR IGNORE INTO skills (name, category_id) VALUES
        -- ロゴ制作
        ('Illustrator', 1), ('Photoshop', 1), ('Figma', 1), ('ブランディング', 1),
        -- ライティング
        ('SEOライティング', 2), ('コピーライティング', 2), ('記事執筆', 2), ('翻訳', 2),
        -- Web制作
        ('HTML/CSS', 3), ('JavaScript', 3), ('React', 3), ('PHP', 3), ('WordPress', 3),
        -- 動画編集
        ('Premiere Pro', 4), ('After Effects', 4), ('Final Cut Pro', 4), ('DaVinci Resolve', 4),
        -- AI漫画
        ('Stable Diffusion', 5), ('Midjourney', 5), ('ComfyUI', 5), ('Clip Studio', 5),
        -- 音楽制作
        ('Logic Pro', 6), ('Ableton Live', 6), ('Pro Tools', 6), ('作詞・作曲', 6)
    ");
    echo "✓ スキル初期データ挿入完了\n";
    
    echo "\n✅ データベース初期化が完了しました！\n";
    echo "テーブル一覧:\n";
    
    // テーブル一覧を表示
    $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($tables as $table) {
        $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
        echo "- $table ($count 件)\n";
    }
    
} catch (Exception $e) {
    echo "❌ エラー: " . $e->getMessage() . "\n";
    exit(1);
}
?>
