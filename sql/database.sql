-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- ホスト: 127.0.0.1
-- 生成日時: 2025-09-06 06:34:26
-- サーバのバージョン： 10.4.32-MariaDB
-- PHP のバージョン: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- データベース: `aina_works`
--

-- --------------------------------------------------------

--
-- テーブルの構造 `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `slug` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(100) DEFAULT NULL,
  `color` varchar(7) DEFAULT '#3B82F6',
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- テーブルのデータのダンプ `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `icon`, `color`, `sort_order`, `is_active`, `created_at`) VALUES
(1, 'ロゴ制作', 'logo-design', 'ブランドアイデンティティを表現するロゴデザイン', '🎨', '#EF4444', 1, 1, '2025-08-31 16:48:47'),
(2, 'ライティング', 'writing', 'コンテンツライティング・コピーライティング', '✍️', '#3B82F6', 2, 1, '2025-08-31 16:48:47'),
(3, 'Web制作', 'web-development', 'Webサイト・アプリケーション開発', '💻', '#10B981', 3, 1, '2025-08-31 16:48:47'),
(4, '動画編集', 'video-editing', '動画制作・編集・モーション', '🎬', '#8B5CF6', 4, 1, '2025-08-31 16:48:47'),
(5, 'AI漫画', 'ai-manga', 'AI技術を活用した漫画・イラスト制作', '🤖', '#F59E0B', 5, 1, '2025-08-31 16:48:47'),
(6, '音楽制作', 'music-production', '楽曲制作・音響デザイン', '🎵', '#EC4899', 6, 1, '2025-08-31 16:48:47');

-- --------------------------------------------------------

--
-- テーブルの構造 `chat_messages`
--

CREATE TABLE `chat_messages` (
  `id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `message_type` varchar(20) DEFAULT 'text',
  `file_path` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- テーブルのデータのダンプ `chat_messages`
--

INSERT INTO `chat_messages` (`id`, `room_id`, `sender_id`, `message`, `message_type`, `file_path`, `is_read`, `created_at`) VALUES
(1, 1, 4, 'あああ', 'text', NULL, 0, '2025-09-06 11:46:05'),
(2, 2, 4, 'test', 'text', NULL, 0, '2025-09-06 11:54:05'),
(3, 3, 4, 'ああ', 'text', NULL, 0, '2025-09-06 13:21:45'),
(4, 2, 4, 'あ', 'text', NULL, 0, '2025-09-06 13:22:00'),
(5, 4, 5, 'aaaaa', 'text', NULL, 1, '2025-09-06 13:24:44'),
(6, 4, 5, 'r', 'text', NULL, 1, '2025-09-06 13:24:53'),
(7, 4, 4, 'a', 'text', NULL, 1, '2025-09-06 13:25:18'),
(8, 4, 5, 'ww', 'text', NULL, 0, '2025-09-06 13:34:05');

-- --------------------------------------------------------

--
-- テーブルの構造 `chat_rooms`
--

CREATE TABLE `chat_rooms` (
  `id` int(11) NOT NULL,
  `user1_id` int(11) NOT NULL,
  `user2_id` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- テーブルのデータのダンプ `chat_rooms`
--

INSERT INTO `chat_rooms` (`id`, `user1_id`, `user2_id`, `created_at`, `updated_at`) VALUES
(1, 4, 4, '2025-09-06 11:45:58', '2025-09-06 11:46:05'),
(2, 4, 1, '2025-09-06 11:54:00', '2025-09-06 13:22:00'),
(3, 4, 2, '2025-09-06 13:21:42', '2025-09-06 13:21:45'),
(4, 5, 4, '2025-09-06 13:24:41', '2025-09-06 13:34:05');

-- --------------------------------------------------------

--
-- テーブルの構造 `favorites`
--

CREATE TABLE `favorites` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `target_type` enum('work','creator') NOT NULL,
  `target_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `jobs`
--

CREATE TABLE `jobs` (
  `id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `budget_min` int(11) DEFAULT 0,
  `budget_max` int(11) DEFAULT 0,
  `duration_weeks` int(11) DEFAULT 1,
  `required_skills` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`required_skills`)),
  `location` varchar(100) DEFAULT NULL,
  `remote_ok` tinyint(1) DEFAULT 1,
  `urgency` enum('low','medium','high') DEFAULT 'medium',
  `applications_count` int(11) DEFAULT 0,
  `status` enum('open','in_progress','completed','cancelled') DEFAULT 'open',
  `deadline` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- テーブルのデータのダンプ `jobs`
--

INSERT INTO `jobs` (`id`, `client_id`, `title`, `description`, `category_id`, `budget_min`, `budget_max`, `duration_weeks`, `required_skills`, `location`, `remote_ok`, `urgency`, `applications_count`, `status`, `deadline`, `created_at`, `updated_at`) VALUES
(1, 3, 'ECサイトのUI/UXデザイン', 'オンラインショップのユーザーインターフェース設計', 3, 200000, 300000, 4, '[\"UI/UX\", \"Figma\", \"Webデザイン\"]', NULL, 1, 'medium', 0, 'open', NULL, '2025-08-31 16:48:47', '2025-08-31 16:48:47'),
(2, 2, '企業PR動画制作', '会社紹介動画の企画・制作', 4, 150000, 250000, 3, '[\"動画編集\", \"Premiere Pro\", \"企画\"]', NULL, 0, 'high', 0, 'open', NULL, '2025-08-31 16:48:47', '2025-08-31 16:48:47');

-- --------------------------------------------------------

--
-- テーブルの構造 `job_applications`
--

CREATE TABLE `job_applications` (
  `id` int(11) NOT NULL,
  `job_id` int(11) NOT NULL,
  `creator_id` int(11) NOT NULL,
  `cover_letter` text DEFAULT NULL,
  `proposed_price` int(11) DEFAULT NULL,
  `proposed_duration` int(11) DEFAULT NULL,
  `status` enum('pending','accepted','rejected','withdrawn') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `subject` varchar(200) DEFAULT NULL,
  `content` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `reviewer_id` int(11) NOT NULL,
  `reviewee_id` int(11) NOT NULL,
  `job_id` int(11) DEFAULT NULL,
  `work_id` int(11) DEFAULT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `skills`
--

CREATE TABLE `skills` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- テーブルのデータのダンプ `skills`
--

INSERT INTO `skills` (`id`, `name`, `category_id`, `is_active`, `created_at`) VALUES
(1, 'Illustrator', 1, 1, '2025-08-31 16:48:47'),
(2, 'Photoshop', 1, 1, '2025-08-31 16:48:47'),
(3, 'Figma', 1, 1, '2025-08-31 16:48:47'),
(4, 'ブランディング', 1, 1, '2025-08-31 16:48:47'),
(5, 'SEOライティング', 2, 1, '2025-08-31 16:48:47'),
(6, 'コピーライティング', 2, 1, '2025-08-31 16:48:47'),
(7, '記事執筆', 2, 1, '2025-08-31 16:48:47'),
(8, '翻訳', 2, 1, '2025-08-31 16:48:47'),
(9, 'HTML/CSS', 3, 1, '2025-08-31 16:48:47'),
(10, 'JavaScript', 3, 1, '2025-08-31 16:48:47'),
(11, 'React', 3, 1, '2025-08-31 16:48:47'),
(12, 'PHP', 3, 1, '2025-08-31 16:48:47'),
(13, 'WordPress', 3, 1, '2025-08-31 16:48:47'),
(14, 'Premiere Pro', 4, 1, '2025-08-31 16:48:47'),
(15, 'After Effects', 4, 1, '2025-08-31 16:48:47'),
(16, 'Final Cut Pro', 4, 1, '2025-08-31 16:48:47'),
(17, 'DaVinci Resolve', 4, 1, '2025-08-31 16:48:47'),
(18, 'Stable Diffusion', 5, 1, '2025-08-31 16:48:47'),
(19, 'Midjourney', 5, 1, '2025-08-31 16:48:47'),
(20, 'ComfyUI', 5, 1, '2025-08-31 16:48:47'),
(21, 'Clip Studio', 5, 1, '2025-08-31 16:48:47'),
(22, 'Logic Pro', 6, 1, '2025-08-31 16:48:47'),
(23, 'Ableton Live', 6, 1, '2025-08-31 16:48:47'),
(24, 'Pro Tools', 6, 1, '2025-08-31 16:48:47'),
(25, '作詞・作曲', 6, 1, '2025-08-31 16:48:47');

-- --------------------------------------------------------

--
-- テーブルの構造 `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `nickname` varchar(50) DEFAULT NULL,
  `user_type` enum('creator','client','sales') NOT NULL,
  `active_role` enum('creator','client','sales','admin') DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `twitter_url` varchar(255) DEFAULT NULL,
  `instagram_url` varchar(255) DEFAULT NULL,
  `facebook_url` varchar(255) DEFAULT NULL,
  `linkedin_url` varchar(255) DEFAULT NULL,
  `youtube_url` varchar(255) DEFAULT NULL,
  `tiktok_url` varchar(255) DEFAULT NULL,
  `response_time` int(11) DEFAULT 24,
  `experience_years` int(11) DEFAULT 0,
  `hourly_rate` int(11) DEFAULT 0,
  `is_pro` tinyint(1) DEFAULT 0,
  `is_verified` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `is_online` tinyint(1) DEFAULT 0,
  `last_seen` datetime DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- テーブルのデータのダンプ `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `full_name`, `nickname`, `user_type`, `active_role`, `profile_image`, `bio`, `location`, `website`, `twitter_url`, `instagram_url`, `facebook_url`, `linkedin_url`, `youtube_url`, `tiktok_url`, `response_time`, `experience_years`, `hourly_rate`, `is_pro`, `is_verified`, `is_active`, `is_online`, `last_seen`, `created_at`, `updated_at`) VALUES
(1, 'tanaka_misaki', 'tanaka@example.com', '$2y$10$example_hash', '田中 美咲', NULL, 'creator', NULL, NULL, 'AI漫画クリエイターとして活動しています。Stable DiffusionやMidjourneyを使った作品制作が得意です。', '東京都', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, 3, 30000, 1, 1, 1, 0, '2025-09-06 00:44:03', '2025-08-31 16:48:47', '2025-08-31 16:48:47'),
(2, 'sato_kenta', 'sato@example.com', '$2y$10$example_hash', '佐藤 健太', NULL, 'creator', NULL, NULL, 'グラフィックデザイナーです。ロゴ制作からブランディングまで幅広く対応します。', '大阪府', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, 5, 25000, 1, 1, 1, 0, '2025-09-06 00:44:03', '2025-08-31 16:48:47', '2025-08-31 16:48:47'),
(3, 'yamada_hanako', 'yamada@example.com', '$2y$10$example_hash', '山田 花子', NULL, 'creator', NULL, NULL, 'Webデザイナー・フロントエンドエンジニアです。', '神奈川県', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 6, 4, 40000, 1, 0, 1, 0, '2025-09-06 00:44:03', '2025-08-31 16:48:47', '2025-08-31 16:48:47'),
(4, 'rostyle95', 'rostyle95@gmail.com', '$2y$10$3ILDllyesu0GgVvDJk8ezOW83fF1y9J3TV77uZMdoYKQ10NcHVU6i', '奥野隆太', 'Ryu', 'creator', NULL, NULL, '', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 24, 0, 0, 0, 0, 1, 0, '2025-09-06 11:41:12', '2025-09-06 02:41:12', '2025-09-06 02:41:12'),
(5, 'rostyle95+1', 'rostyle95+1@gmail.com', '$2y$10$euNCbQnmRM9LBDpmX6kbp.zUYPBIQDrtKcUjv65bVDa2iPkHBp26u', 'test+1', 't', 'creator', 'creator', NULL, '', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 24, 0, 0, 0, 0, 1, 0, '2025-09-06 13:22:59', '2025-09-06 04:22:59', '2025-09-06 04:22:59');

-- --------------------------------------------------------

--
-- テーブルの構造 `user_roles`
--

CREATE TABLE `user_roles` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role` enum('creator','client','sales','admin') NOT NULL,
  `is_enabled` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- テーブルのデータのダンプ `user_roles`
--

INSERT INTO `user_roles` (`id`, `user_id`, `role`, `is_enabled`, `created_at`) VALUES
(1, 5, 'creator', 1, '2025-09-06 04:22:59');

-- --------------------------------------------------------

--
-- テーブルの構造 `user_skills`
--

CREATE TABLE `user_skills` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `skill_id` int(11) NOT NULL,
  `proficiency` enum('beginner','intermediate','advanced','expert') DEFAULT 'intermediate',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `works`
--

CREATE TABLE `works` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `price_min` int(11) DEFAULT 0,
  `price_max` int(11) DEFAULT 0,
  `duration_weeks` int(11) DEFAULT 1,
  `main_image` varchar(255) DEFAULT NULL,
  `images` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`images`)),
  `tags` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tags`)),
  `technologies` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`technologies`)),
  `project_url` varchar(255) DEFAULT NULL,
  `is_featured` tinyint(1) DEFAULT 0,
  `view_count` int(11) DEFAULT 0,
  `like_count` int(11) DEFAULT 0,
  `status` enum('draft','published','archived') DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- テーブルのデータのダンプ `works`
--

INSERT INTO `works` (`id`, `user_id`, `title`, `description`, `category_id`, `price_min`, `price_max`, `duration_weeks`, `main_image`, `images`, `tags`, `technologies`, `project_url`, `is_featured`, `view_count`, `like_count`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'オリジナルキャラクター制作', 'Stable DiffusionとPhotoshopを組み合わせて制作したオリジナルキャラクター', 5, 80000, 120000, 2, 'assets/images/sample-work-1.png', NULL, '[\"AI漫画\", \"キャラクター\", \"オリジナル\"]', '[\"Stable Diffusion\", \"Photoshop\", \"ComfyUI\"]', NULL, 1, 1523, 234, 'published', '2025-08-31 16:48:47', '2025-09-06 04:22:17'),
(2, 2, 'ブランドロゴデザイン', 'モダンで印象的なブランドロゴの制作', 1, 50000, 80000, 1, 'assets/images/sample-work-2.jpg', NULL, '[\"ロゴ\", \"ブランディング\", \"モダン\"]', '[\"Illustrator\", \"Photoshop\"]', NULL, 1, 981, 189, 'published', '2025-08-31 16:48:47', '2025-09-06 04:22:07'),
(3, 3, 'コーポレートサイト制作', 'レスポンシブ対応のコーポレートサイト', 3, 150000, 250000, 6, 'assets/images/sample-work-3.jpg', NULL, '[\"Web制作\", \"レスポンシブ\", \"コーポレート\"]', '[\"HTML/CSS\", \"JavaScript\", \"WordPress\"]', NULL, 0, 756, 92, 'published', '2025-08-31 16:48:47', '2025-08-31 16:48:47'),
(4, 4, 'サムネイル作成', 'サムネイル作成します。', 1, 3300, 5000, 1, '68bba0547498a.jpg', NULL, NULL, NULL, NULL, 0, 7, 0, 'published', '2025-09-06 02:45:40', '2025-09-06 04:24:50');

--
-- ダンプしたテーブルのインデックス
--

--
-- テーブルのインデックス `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- テーブルのインデックス `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_chat_messages_room` (`room_id`,`created_at`),
  ADD KEY `idx_chat_messages_sender` (`sender_id`),
  ADD KEY `idx_chat_messages_unread` (`room_id`,`is_read`,`created_at`);

--
-- テーブルのインデックス `chat_rooms`
--
ALTER TABLE `chat_rooms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_users` (`user1_id`,`user2_id`),
  ADD KEY `fk_chat_rooms_user2` (`user2_id`),
  ADD KEY `idx_chat_rooms_users` (`user1_id`,`user2_id`);

--
-- テーブルのインデックス `favorites`
--
ALTER TABLE `favorites`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_favorite` (`user_id`,`target_type`,`target_id`);

--
-- テーブルのインデックス `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `category_id` (`category_id`);

--
-- テーブルのインデックス `job_applications`
--
ALTER TABLE `job_applications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_application` (`job_id`,`creator_id`),
  ADD KEY `creator_id` (`creator_id`);

--
-- テーブルのインデックス `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `receiver_id` (`receiver_id`);

--
-- テーブルのインデックス `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reviewer_id` (`reviewer_id`),
  ADD KEY `reviewee_id` (`reviewee_id`),
  ADD KEY `job_id` (`job_id`),
  ADD KEY `work_id` (`work_id`);

--
-- テーブルのインデックス `skills`
--
ALTER TABLE `skills`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- テーブルのインデックス `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- テーブルのインデックス `user_roles`
--
ALTER TABLE `user_roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_role` (`user_id`,`role`),
  ADD KEY `user_id` (`user_id`);

--
-- テーブルのインデックス `user_skills`
--
ALTER TABLE `user_skills`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_skill` (`user_id`,`skill_id`),
  ADD KEY `skill_id` (`skill_id`);

--
-- テーブルのインデックス `works`
--
ALTER TABLE `works`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `category_id` (`category_id`);

--
-- ダンプしたテーブルの AUTO_INCREMENT
--

--
-- テーブルの AUTO_INCREMENT `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- テーブルの AUTO_INCREMENT `chat_messages`
--
ALTER TABLE `chat_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- テーブルの AUTO_INCREMENT `chat_rooms`
--
ALTER TABLE `chat_rooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- テーブルの AUTO_INCREMENT `favorites`
--
ALTER TABLE `favorites`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- テーブルの AUTO_INCREMENT `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- テーブルの AUTO_INCREMENT `job_applications`
--
ALTER TABLE `job_applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- テーブルの AUTO_INCREMENT `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- テーブルの AUTO_INCREMENT `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- テーブルの AUTO_INCREMENT `skills`
--
ALTER TABLE `skills`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- テーブルの AUTO_INCREMENT `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- テーブルの AUTO_INCREMENT `user_roles`
--
ALTER TABLE `user_roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- テーブルの AUTO_INCREMENT `user_skills`
--
ALTER TABLE `user_skills`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- テーブルの AUTO_INCREMENT `works`
--
ALTER TABLE `works`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- ダンプしたテーブルの制約
--

--
-- テーブルの制約 `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD CONSTRAINT `fk_chat_messages_room` FOREIGN KEY (`room_id`) REFERENCES `chat_rooms` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_chat_messages_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- テーブルの制約 `chat_rooms`
--
ALTER TABLE `chat_rooms`
  ADD CONSTRAINT `fk_chat_rooms_user1` FOREIGN KEY (`user1_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_chat_rooms_user2` FOREIGN KEY (`user2_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- テーブルの制約 `favorites`
--
ALTER TABLE `favorites`
  ADD CONSTRAINT `favorites_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- テーブルの制約 `jobs`
--
ALTER TABLE `jobs`
  ADD CONSTRAINT `jobs_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `jobs_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- テーブルの制約 `job_applications`
--
ALTER TABLE `job_applications`
  ADD CONSTRAINT `job_applications_ibfk_1` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `job_applications_ibfk_2` FOREIGN KEY (`creator_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- テーブルの制約 `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- テーブルの制約 `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`reviewer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`reviewee_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_3` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `reviews_ibfk_4` FOREIGN KEY (`work_id`) REFERENCES `works` (`id`) ON DELETE SET NULL;

--
-- テーブルの制約 `skills`
--
ALTER TABLE `skills`
  ADD CONSTRAINT `skills_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- テーブルの制約 `user_roles`
--
ALTER TABLE `user_roles`
  ADD CONSTRAINT `user_roles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- テーブルの制約 `user_skills`
--
ALTER TABLE `user_skills`
  ADD CONSTRAINT `user_skills_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_skills_ibfk_2` FOREIGN KEY (`skill_id`) REFERENCES `skills` (`id`) ON DELETE CASCADE;

--
-- テーブルの制約 `works`
--
ALTER TABLE `works`
  ADD CONSTRAINT `works_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `works_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
