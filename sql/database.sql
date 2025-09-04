-- AiNA Works データベース設計 - 統合版
-- このファイルは全てのテーブル定義とデータを含んでいます

CREATE DATABASE IF NOT EXISTS aina_works CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE aina_works;

-- ユーザーテーブル
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    nickname VARCHAR(50),
    user_type ENUM('creator', 'client', 'sales') NOT NULL, -- 主要ロール（後方互換性のため残す）
    active_role ENUM('creator', 'client', 'sales') NOT NULL, -- 現在アクティブなロール
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
    response_time INT DEFAULT 24, -- 時間単位
    experience_years INT DEFAULT 0,
    hourly_rate INT DEFAULT 0,
    is_pro BOOLEAN DEFAULT FALSE,
    is_verified BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ユーザーロールテーブル（複数ロール対応）
CREATE TABLE user_roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    role ENUM('creator', 'client', 'sales') NOT NULL,
    is_enabled BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_role (user_id, role)
);

-- パスワードリセット用テーブル
CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL,
    token VARCHAR(255) NOT NULL UNIQUE,
    expires_at TIMESTAMP NOT NULL,
    used_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_token (token),
    INDEX idx_expires_at (expires_at)
);

-- カテゴリテーブル
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    slug VARCHAR(50) UNIQUE NOT NULL,
    description TEXT,
    icon VARCHAR(100),
    color VARCHAR(7) DEFAULT '#3B82F6',
    sort_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- スキルテーブル
CREATE TABLE skills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    category_id INT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- ユーザースキルテーブル
CREATE TABLE user_skills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    skill_id INT NOT NULL,
    proficiency ENUM('beginner', 'intermediate', 'advanced', 'expert') DEFAULT 'intermediate',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (skill_id) REFERENCES skills(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_skill (user_id, skill_id)
);

-- 作品テーブル
CREATE TABLE works (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    category_id INT,
    price_min INT DEFAULT 0,
    price_max INT DEFAULT 0,
    duration_weeks INT DEFAULT 1,
    main_image VARCHAR(255),
    images JSON, -- 複数画像のパス配列
    tags JSON, -- タグ配列
    technologies JSON, -- 使用技術配列
    project_url VARCHAR(255),
    is_featured BOOLEAN DEFAULT FALSE,
    view_count INT DEFAULT 0,
    like_count INT DEFAULT 0,
    status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- 案件テーブル
CREATE TABLE jobs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    category_id INT,
    budget_min INT DEFAULT 0,
    budget_max INT DEFAULT 0,
    duration_weeks INT DEFAULT 1,
    required_skills JSON, -- 必要スキル配列
    location VARCHAR(100),
    remote_ok BOOLEAN DEFAULT TRUE,
    urgency ENUM('low', 'medium', 'high') DEFAULT 'medium',
    applications_count INT DEFAULT 0,
    status ENUM('open', 'in_progress', 'completed', 'cancelled') DEFAULT 'open',
    deadline DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- 案件応募テーブル
CREATE TABLE job_applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_id INT NOT NULL,
    creator_id INT NOT NULL,
    cover_letter TEXT,
    proposed_price INT,
    proposed_duration INT,
    status ENUM('pending', 'accepted', 'rejected', 'withdrawn') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
    FOREIGN KEY (creator_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_application (job_id, creator_id)
);

-- レビューテーブル
CREATE TABLE reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reviewer_id INT NOT NULL,
    reviewee_id INT NOT NULL,
    job_id INT,
    work_id INT,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewee_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE SET NULL,
    FOREIGN KEY (work_id) REFERENCES works(id) ON DELETE SET NULL
);

-- メッセージテーブル
CREATE TABLE messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    subject VARCHAR(200),
    content TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
);

-- お気に入りテーブル
CREATE TABLE favorites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    target_type ENUM('work', 'creator') NOT NULL,
    target_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_favorite (user_id, target_type, target_id)
);

-- 初期データ挿入
INSERT INTO categories (name, slug, description, icon, color, sort_order) VALUES
('ロゴ制作', 'logo-design', 'ブランドアイデンティティを表現するロゴデザイン', '🎨', '#EF4444', 1),
('ライティング', 'writing', 'コンテンツライティング・コピーライティング', '✍️', '#3B82F6', 2),
('Web制作', 'web-development', 'Webサイト・アプリケーション開発', '💻', '#10B981', 3),
('動画編集', 'video-editing', '動画制作・編集・モーション', '🎬', '#8B5CF6', 4),
('AI漫画', 'ai-manga', 'AI技術を活用した漫画・イラスト制作', '🤖', '#F59E0B', 5),
('音楽制作', 'music-production', '楽曲制作・音響デザイン', '🎵', '#EC4899', 6);

INSERT INTO skills (name, category_id) VALUES
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
('Logic Pro', 6), ('Ableton Live', 6), ('Pro Tools', 6), ('作詞・作曲', 6);

-- サンプルユーザー（active_roleを追加）
INSERT INTO users (username, email, password_hash, full_name, user_type, active_role, bio, location, response_time, experience_years, hourly_rate, is_pro, is_verified) VALUES
('tanaka_misaki', 'tanaka@example.com', '$2y$10$example_hash', '田中 美咲', 'creator', 'creator', 'AI漫画クリエイターとして活動しています。Stable DiffusionやMidjourneyを使った作品制作が得意です。', '東京都', 2, 3, 30000, TRUE, TRUE),
('sato_kenta', 'sato@example.com', '$2y$10$example_hash', '佐藤 健太', 'creator', 'creator', 'グラフィックデザイナーです。ロゴ制作からブランディングまで幅広く対応します。', '大阪府', 4, 5, 25000, TRUE, TRUE),
('yamada_hanako', 'yamada@example.com', '$2y$10$example_hash', '山田 花子', 'creator', 'creator', 'Webデザイナー・フロントエンドエンジニアです。', '神奈川県', 6, 4, 40000, TRUE, FALSE);

-- サンプルユーザーロール（複数ロール対応）
INSERT INTO user_roles (user_id, role) VALUES
-- 田中さん: クリエイター専業
(1, 'creator'),
-- 佐藤さん: クリエイターと依頼者の両方
(2, 'creator'),
(2, 'client'),
-- 山田さん: クリエイター、依頼者、営業の全ロール
(3, 'creator'),
(3, 'client'),
(3, 'sales');

-- サンプル作品
INSERT INTO works (user_id, title, description, category_id, price_min, price_max, duration_weeks, main_image, tags, technologies, is_featured, view_count, like_count, status) VALUES
(1, 'オリジナルキャラクター制作', 'Stable DiffusionとPhotoshopを組み合わせて制作したオリジナルキャラクター', 5, 80000, 120000, 2, 'assets/images/sample-work-1.png', '["AI漫画", "キャラクター", "オリジナル"]', '["Stable Diffusion", "Photoshop", "ComfyUI"]', TRUE, 1520, 234, 'published'),
(2, 'ブランドロゴデザイン', 'モダンで印象的なブランドロゴの制作', 1, 50000, 80000, 1, 'assets/images/sample-work-2.jpg', '["ロゴ", "ブランディング", "モダン"]', '["Illustrator", "Photoshop"]', TRUE, 980, 189, 'published'),
(3, 'コーポレートサイト制作', 'レスポンシブ対応のコーポレートサイト', 3, 150000, 250000, 6, 'assets/images/sample-work-3.jpg', '["Web制作", "レスポンシブ", "コーポレート"]', '["HTML/CSS", "JavaScript", "WordPress"]', FALSE, 756, 92, 'published');

-- サンプル案件
INSERT INTO jobs (client_id, title, description, category_id, budget_min, budget_max, duration_weeks, required_skills, remote_ok, urgency, status) VALUES
(3, 'ECサイトのUI/UXデザイン', 'オンラインショップのユーザーインターフェース設計', 3, 200000, 300000, 4, '["UI/UX", "Figma", "Webデザイン"]', TRUE, 'medium', 'open'),
(2, '企業PR動画制作', '会社紹介動画の企画・制作', 4, 150000, 250000, 3, '["動画編集", "Premiere Pro", "企画"]', FALSE, 'high', 'open');

-- SQLiteの場合のパスワードリセットテーブル（コメントアウト）
-- CREATE TABLE IF NOT EXISTS password_resets (
--     id INTEGER PRIMARY KEY AUTOINCREMENT,
--     email VARCHAR(100) NOT NULL,
--     token VARCHAR(255) NOT NULL UNIQUE,
--     expires_at DATETIME NOT NULL,
--     used_at DATETIME NULL,
--     created_at DATETIME DEFAULT CURRENT_TIMESTAMP
-- );
-- CREATE INDEX IF NOT EXISTS idx_email ON password_resets(email);
-- CREATE INDEX IF NOT EXISTS idx_token ON password_resets(token);
-- CREATE INDEX IF NOT EXISTS idx_expires_at ON password_resets(expires_at);
