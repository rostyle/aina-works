-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- ホスト: 127.0.0.1
-- 生成日時: 2025-09-08 18:32:09
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
(8, 4, 5, 'ww', 'text', NULL, 1, '2025-09-06 13:34:05'),
(9, 5, 6, 'aaa', 'text', NULL, 1, '2025-09-08 09:50:06'),
(10, 6, 6, 'aa', 'text', NULL, 0, '2025-09-08 09:51:44'),
(11, 5, 4, '案件『ああ』の応募を受諾しました。ここからやり取りを開始しましょう。', 'text', NULL, 0, '2025-09-08 13:58:13'),
(12, 5, 4, 'あ', 'text', NULL, 0, '2025-09-08 17:55:35'),
(13, 5, 4, 'あああああ', 'text', NULL, 0, '2025-09-09 00:30:43'),
(14, 5, 4, 't', 'text', NULL, 0, '2025-09-09 00:35:21');

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
(4, 5, 4, '2025-09-06 13:24:41', '2025-09-06 13:34:05'),
(5, 6, 4, '2025-09-08 09:50:04', '2025-09-09 00:35:21'),
(6, 6, 2, '2025-09-08 09:51:42', '2025-09-08 09:51:44');

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

--
-- テーブルのデータのダンプ `favorites`
--

INSERT INTO `favorites` (`id`, `user_id`, `target_type`, `target_id`, `created_at`) VALUES
(2, 4, 'work', 4, '2025-09-06 13:08:43'),
(3, 4, 'work', 2, '2025-09-06 13:08:46'),
(5, 4, 'work', 5, '2025-09-06 13:11:15'),
(6, 5, 'work', 5, '2025-09-06 13:22:50'),
(7, 5, 'work', 2, '2025-09-06 15:03:28'),
(8, 5, 'creator', 4, '2025-09-06 16:19:03'),
(9, 5, 'work', 4, '2025-09-06 16:20:01'),
(10, 4, 'creator', 2, '2025-09-06 17:26:21'),
(11, 4, 'creator', 1, '2025-09-08 04:36:51'),
(12, 4, 'creator', 3, '2025-09-08 04:37:01');

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
  `status` enum('open','closed','contracted','delivered','in_progress','completed','cancelled') DEFAULT 'open',
  `deadline` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `hiring_limit` int(11) NOT NULL DEFAULT 1,
  `accepted_count` int(11) DEFAULT 0,
  `is_recruiting` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- テーブルのデータのダンプ `jobs`
--

INSERT INTO `jobs` (`id`, `client_id`, `title`, `description`, `category_id`, `budget_min`, `budget_max`, `duration_weeks`, `required_skills`, `location`, `remote_ok`, `urgency`, `applications_count`, `status`, `deadline`, `created_at`, `updated_at`, `hiring_limit`, `accepted_count`, `is_recruiting`) VALUES
(1, 3, 'ECサイトのUI/UXデザイン', 'オンラインショップのユーザーインターフェース設計', 3, 200000, 300000, 4, '[\"UI/UX\", \"Figma\", \"Webデザイン\"]', NULL, 1, 'medium', 1, 'open', NULL, '2025-08-31 16:48:47', '2025-09-07 07:34:16', 1, 0, 1),
(2, 2, '企業PR動画制作', '会社紹介動画の企画・制作', 4, 150000, 250000, 3, '[\"動画編集\", \"Premiere Pro\", \"企画\"]', NULL, 0, 'high', 1, 'open', NULL, '2025-08-31 16:48:47', '2025-09-07 04:28:19', 1, 0, 1),
(3, 4, 'ああ', 'AIスクール生と企業をつなぐ、新しいクリエイティブプラットフォーム。 才能あるクリエイターと素晴らしいプロジェクトのマッチングを支援します。AIスクール生と企業をつなぐ、新しいクリエイティブプラットフォーム。 才能あるクリエイターと素晴らしいプロジェクトのマッチングを支援します。', 3, 1000, 3000, 2, NULL, NULL, 1, 'medium', 2, 'delivered', '2025-09-13', '2025-09-07 00:04:58', '2025-09-08 12:17:37', 2, 1, 0);

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

--
-- テーブルのデータのダンプ `job_applications`
--

INSERT INTO `job_applications` (`id`, `job_id`, `creator_id`, `cover_letter`, `proposed_price`, `proposed_duration`, `status`, `created_at`, `updated_at`) VALUES
(1, 2, 4, 'aa', 1000, 1, 'pending', '2025-09-07 04:28:19', '2025-09-07 04:28:19'),
(3, 3, 5, 'aa', 1000, 2, 'rejected', '2025-09-07 04:58:53', '2025-09-08 04:58:13'),
(5, 1, 5, 'aa', 1000, 1, 'pending', '2025-09-07 07:34:15', '2025-09-07 07:34:15'),
(6, 3, 6, 'wwwww', 2000, 2, 'accepted', '2025-09-08 00:54:00', '2025-09-08 04:58:13');

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
  `rating` int(11) NOT NULL CHECK (`rating` >= 0 and `rating` <= 5),
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- テーブルのデータのダンプ `reviews`
--

INSERT INTO `reviews` (`id`, `reviewer_id`, `reviewee_id`, `job_id`, `work_id`, `rating`, `comment`, `created_at`) VALUES
(1, 5, 2, NULL, 2, 4, 'aa', '2025-09-06 15:02:55'),
(2, 5, 2, NULL, 2, 3, 'r', '2025-09-06 15:03:15'),
(3, 5, 4, NULL, 4, 3, 'a', '2025-09-06 15:06:36'),
(4, 6, 4, NULL, 5, 5, 'aaa', '2025-09-08 00:50:00');

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
  `aina_user_id` int(11) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `nickname` varchar(50) DEFAULT NULL,
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
  `is_pro` tinyint(1) DEFAULT 0,
  `is_verified` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `last_seen` datetime DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_creator` tinyint(1) DEFAULT 1,
  `is_client` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- テーブルのデータのダンプ `users`
--

INSERT INTO `users` (`id`, `aina_user_id`, `name`, `username`, `email`, `password_hash`, `full_name`, `nickname`, `profile_image`, `bio`, `location`, `website`, `twitter_url`, `instagram_url`, `facebook_url`, `linkedin_url`, `youtube_url`, `tiktok_url`, `response_time`, `experience_years`, `is_pro`, `is_verified`, `is_active`, `last_seen`, `created_at`, `updated_at`, `is_creator`, `is_client`) VALUES
(1, NULL, NULL, 'tanaka_misaki', 'tanaka@example.com', '$2y$10$example_hash', '田中 美咲', NULL, NULL, 'AI漫画クリエイターとして活動しています。Stable DiffusionやMidjourneyを使った作品制作が得意です。', '東京都', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, 3, 1, 1, 1, '2025-09-06 00:44:03', '2025-08-31 16:48:47', '2025-08-31 16:48:47', 1, 1),
(2, NULL, NULL, 'sato_kenta', 'sato@example.com', '$2y$10$example_hash', '佐藤 健太', NULL, NULL, 'グラフィックデザイナーです。ロゴ制作からブランディングまで幅広く対応します。', '大阪府', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, 5, 1, 1, 1, '2025-09-06 00:44:03', '2025-08-31 16:48:47', '2025-08-31 16:48:47', 1, 1),
(3, NULL, NULL, 'yamada_hanako', 'yamada@example.com', '$2y$10$example_hash', '山田 花子', NULL, NULL, 'Webデザイナー・フロントエンドエンジニアです。', '神奈川県', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 6, 4, 1, 0, 1, '2025-09-06 00:44:03', '2025-08-31 16:48:47', '2025-08-31 16:48:47', 1, 1),
(4, NULL, NULL, 'rostyle95', 'rostyle95@gmail.com', '$2y$10$3ILDllyesu0GgVvDJk8ezOW83fF1y9J3TV77uZMdoYKQ10NcHVU6i', '奥野隆太', 'Ryu', NULL, '', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 24, 0, 0, 0, 1, '2025-09-06 11:41:12', '2025-09-06 02:41:12', '2025-09-08 14:43:17', 1, 1),
(5, NULL, NULL, 'rostyle95+1', 'rostyle95+1@gmail.com', '$2y$10$euNCbQnmRM9LBDpmX6kbp.zUYPBIQDrtKcUjv65bVDa2iPkHBp26u', 'test+1', 't', '68bc5a6db95a3.png', 'aaaa', '大阪府大阪市中央区伏見町2-2-10 谷ビル5F(北浜駅6番出口)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 24, 0, 0, 0, 1, '2025-09-06 13:22:59', '2025-09-06 04:22:59', '2025-09-06 15:59:41', 1, 1),
(6, NULL, NULL, 'rostyle95+2', 'rostyle95+2@gmail.com', '$2y$10$XJ.V7cw08cajFRhpTVUJy.rysKfMcjre2def3YQjIH/BuqoTP088q', 'test+２', 'テストニックネーム', NULL, 'ああああ', '大阪府大阪市中央区伏見町2-2-10 谷ビル5F(北浜駅6番出口)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 24, 0, 0, 0, 1, '2025-09-08 09:49:38', '2025-09-08 00:49:38', '2025-09-08 00:49:38', 1, 1);

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
(1, 5, 'creator', 1, '2025-09-06 04:22:59'),
(2, 6, 'creator', 1, '2025-09-08 00:49:38'),
(3, 6, 'client', 1, '2025-09-08 00:49:38'),
(4, 1, 'creator', 1, '2025-09-08 14:38:35'),
(5, 2, 'creator', 1, '2025-09-08 14:38:35'),
(6, 3, 'creator', 1, '2025-09-08 14:38:35'),
(12, 4, 'creator', 1, '2025-09-08 14:43:17'),
(13, 4, 'client', 1, '2025-09-08 14:43:17');

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
(1, 1, 'オリジナルキャラクター制作', 'Stable DiffusionとPhotoshopを組み合わせて制作したオリジナルキャラクター', 5, 80000, 120000, 2, 'assets/images/sample-work-1.png', NULL, '[\"AI漫画\", \"キャラクター\", \"オリジナル\"]', '[\"Stable Diffusion\", \"Photoshop\", \"ComfyUI\"]', NULL, 1, 1531, 234, 'published', '2025-08-31 16:48:47', '2025-09-08 00:54:36'),
(2, 2, 'ブランドロゴデザイン', 'モダンで印象的なブランドロゴの制作', 1, 50000, 80000, 1, 'assets/images/sample-work-2.jpg', NULL, '[\"ロゴ\", \"ブランディング\", \"モダン\"]', '[\"Illustrator\", \"Photoshop\"]', NULL, 1, 1009, 191, 'published', '2025-08-31 16:48:47', '2025-09-08 01:35:35'),
(3, 3, 'コーポレートサイト制作', 'レスポンシブ対応のコーポレートサイト', 3, 150000, 250000, 6, 'assets/images/sample-work-3.jpg', NULL, '[\"Web制作\", \"レスポンシブ\", \"コーポレート\"]', '[\"HTML/CSS\", \"JavaScript\", \"WordPress\"]', NULL, 0, 762, 93, 'published', '2025-08-31 16:48:47', '2025-09-08 01:23:27'),
(4, 4, 'サムネイル作成', 'サムネイル作成します。', 1, 3300, 5000, 1, '68bba0547498a.jpg', NULL, NULL, NULL, NULL, 0, 23, 2, 'published', '2025-09-06 02:45:40', '2025-09-08 01:21:57'),
(5, 4, 'aaaaa', 'aaaa', 2, 1111, 111111, 0, '68bbfd680f317.jpg', NULL, NULL, NULL, NULL, 0, 23, 2, 'published', '2025-09-06 09:22:48', '2025-09-08 00:50:00');

-- --------------------------------------------------------

--
-- テーブルの構造 `work_likes`
--

CREATE TABLE `work_likes` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `work_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `idx_aina_user_id` (`aina_user_id`);

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
-- テーブルのインデックス `work_likes`
--
ALTER TABLE `work_likes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_like` (`user_id`,`work_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `work_id` (`work_id`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- テーブルの AUTO_INCREMENT `chat_rooms`
--
ALTER TABLE `chat_rooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- テーブルの AUTO_INCREMENT `favorites`
--
ALTER TABLE `favorites`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- テーブルの AUTO_INCREMENT `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- テーブルの AUTO_INCREMENT `job_applications`
--
ALTER TABLE `job_applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- テーブルの AUTO_INCREMENT `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- テーブルの AUTO_INCREMENT `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- テーブルの AUTO_INCREMENT `skills`
--
ALTER TABLE `skills`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- テーブルの AUTO_INCREMENT `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- テーブルの AUTO_INCREMENT `user_roles`
--
ALTER TABLE `user_roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- テーブルの AUTO_INCREMENT `user_skills`
--
ALTER TABLE `user_skills`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- テーブルの AUTO_INCREMENT `works`
--
ALTER TABLE `works`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- テーブルの AUTO_INCREMENT `work_likes`
--
ALTER TABLE `work_likes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

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

--
-- テーブルの制約 `work_likes`
--
ALTER TABLE `work_likes`
  ADD CONSTRAINT `work_likes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `work_likes_ibfk_2` FOREIGN KEY (`work_id`) REFERENCES `works` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
