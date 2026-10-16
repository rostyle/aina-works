-- AiNA Works Database Schema
-- MySQL 8 / MariaDB 10.4+, utf8mb4
--
-- コードが使う全テーブル・全列をここに揃えた「再構成版」（2026-10-16）。
-- 本番DB（Lolipop LAA1678017-ainaworks）から SHOW CREATE TABLE で取り直したものではないため、
-- 型・既定値が本番と食い違う場合は本番を正とし、このファイルを合わせて直すこと。
-- ローカル検証の手順は tests/README.md。

SET NAMES utf8mb4;
SET time_zone = '+09:00';

CREATE TABLE IF NOT EXISTS users (
  id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  aina_user_id     VARCHAR(64) DEFAULT NULL,
  email            VARCHAR(191) NOT NULL,
  password_hash    VARCHAR(255) DEFAULT NULL,
  full_name        VARCHAR(191) DEFAULT NULL,
  profile_image    VARCHAR(255) DEFAULT NULL,
  bio              TEXT DEFAULT NULL,
  birthdate        DATE DEFAULT NULL,
  is_active        TINYINT(1) NOT NULL DEFAULT 1,
  is_creator       TINYINT(1) NOT NULL DEFAULT 0,
  is_client        TINYINT(1) NOT NULL DEFAULT 0,
  nickname         VARCHAR(100) DEFAULT NULL,
  location         VARCHAR(191) DEFAULT NULL,
  website          VARCHAR(255) DEFAULT NULL,
  twitter_url      VARCHAR(255) DEFAULT NULL,
  instagram_url    VARCHAR(255) DEFAULT NULL,
  facebook_url     VARCHAR(255) DEFAULT NULL,
  linkedin_url     VARCHAR(255) DEFAULT NULL,
  youtube_url      VARCHAR(255) DEFAULT NULL,
  tiktok_url       VARCHAR(255) DEFAULT NULL,
  response_time    INT DEFAULT 24,
  is_pro           TINYINT(1) NOT NULL DEFAULT 0,
  is_verified      TINYINT(1) NOT NULL DEFAULT 0,
  last_seen        DATETIME DEFAULT NULL,
  created_at       DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at       DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_email (email),
  KEY idx_users_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS user_roles (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id     INT UNSIGNED NOT NULL,
  role        VARCHAR(32) NOT NULL,
  is_enabled  TINYINT(1) NOT NULL DEFAULT 1,
  created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_user_role (user_id, role),
  CONSTRAINT fk_user_roles_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS categories (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name         VARCHAR(100) NOT NULL,
  description  TEXT DEFAULT NULL,
  color        VARCHAR(32) DEFAULT NULL,
  is_active    TINYINT(1) NOT NULL DEFAULT 1,
  sort_order   INT NOT NULL DEFAULT 0,
  created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at   DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS works (
  id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id          INT UNSIGNED NOT NULL,
  title            VARCHAR(191) NOT NULL,
  description      TEXT,
  category_id      INT UNSIGNED DEFAULT NULL,
  price_min        INT DEFAULT NULL,
  price_max        INT DEFAULT NULL,
  main_image       VARCHAR(255) DEFAULT NULL,
  duration_weeks   INT DEFAULT NULL,
  status           VARCHAR(32) NOT NULL DEFAULT 'published',
  is_featured      TINYINT(1) NOT NULL DEFAULT 0,
  like_count       INT NOT NULL DEFAULT 0,
  view_count       INT NOT NULL DEFAULT 0,
  created_at       DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at       DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_works_status (status),
  KEY idx_works_user (user_id),
  CONSTRAINT fk_works_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_works_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS work_likes (
  id        INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id   INT UNSIGNED NOT NULL,
  work_id   INT UNSIGNED NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_work_like (user_id, work_id),
  CONSTRAINT fk_work_likes_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_work_likes_work FOREIGN KEY (work_id) REFERENCES works(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS favorites (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id      INT UNSIGNED NOT NULL,
  target_type  ENUM('work','creator') NOT NULL,
  target_id    INT UNSIGNED NOT NULL,
  created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_favorite (user_id, target_type, target_id),
  CONSTRAINT fk_favorites_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS reviews (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  reviewer_id   INT UNSIGNED NOT NULL,
  reviewee_id   INT UNSIGNED NOT NULL,
  work_id       INT UNSIGNED DEFAULT NULL,
  rating        TINYINT NOT NULL,
  comment       TEXT DEFAULT NULL,
  created_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_reviews_reviewee (reviewee_id),
  CONSTRAINT fk_reviews_reviewer FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_reviews_reviewee FOREIGN KEY (reviewee_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_reviews_work FOREIGN KEY (work_id) REFERENCES works(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS jobs (
  id                   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  client_id            INT UNSIGNED NOT NULL,
  category_id          INT UNSIGNED DEFAULT NULL,
  title                VARCHAR(191) NOT NULL,
  description          TEXT,
  budget_min           INT DEFAULT NULL,
  budget_max           INT DEFAULT NULL,
  location             VARCHAR(191) DEFAULT NULL,
  urgency              VARCHAR(32) DEFAULT NULL,
  duration_weeks       INT DEFAULT NULL,
  required_skills      TEXT DEFAULT NULL,
  -- open / closed / contracted / delivered / completed / cancelled（in_progress・approved は旧データ互換）
  status               VARCHAR(32) NOT NULL DEFAULT 'open',
  hiring_limit         INT NOT NULL DEFAULT 1,
  is_recruiting        TINYINT(1) NOT NULL DEFAULT 1,
  applications_count   INT NOT NULL DEFAULT 0,
  deadline             DATE DEFAULT NULL,
  created_at           DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at           DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_jobs_status (status),
  CONSTRAINT fk_jobs_client FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_jobs_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS job_applications (
  id                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
  job_id             INT UNSIGNED NOT NULL,
  creator_id         INT UNSIGNED NOT NULL,
  cover_letter       TEXT,
  proposed_price     INT DEFAULT NULL,
  proposed_duration  VARCHAR(64) DEFAULT NULL,
  status             VARCHAR(32) NOT NULL DEFAULT 'pending',
  created_at         DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at         DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_job_app_job (job_id),
  KEY idx_job_app_creator (creator_id),
  CONSTRAINT fk_job_app_job FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
  CONSTRAINT fk_job_app_creator FOREIGN KEY (creator_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS chat_rooms (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user1_id   INT UNSIGNED NOT NULL,
  user2_id   INT UNSIGNED NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT NULL,
  PRIMARY KEY (id),
  KEY idx_chat_room_users (user1_id, user2_id),
  CONSTRAINT fk_chat_room_user1 FOREIGN KEY (user1_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_chat_room_user2 FOREIGN KEY (user2_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS chat_messages (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  room_id    INT UNSIGNED NOT NULL,
  sender_id  INT UNSIGNED NOT NULL,
  message    TEXT DEFAULT NULL,
  message_type VARCHAR(16) NOT NULL DEFAULT 'text',
  file_path  VARCHAR(255) DEFAULT NULL,
  is_read    TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_chat_msg_room (room_id),
  CONSTRAINT fk_chat_msg_room FOREIGN KEY (room_id) REFERENCES chat_rooms(id) ON DELETE CASCADE,
  CONSTRAINT fk_chat_msg_sender FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS user_bank_accounts (
  id                   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id              INT UNSIGNED NOT NULL,
  bank_name            VARCHAR(100) NOT NULL,
  branch_name          VARCHAR(100) DEFAULT NULL,
  account_type         VARCHAR(16) NOT NULL DEFAULT '普通',
  account_number       VARCHAR(32) NOT NULL,
  account_holder_name  VARCHAR(100) NOT NULL,
  account_holder_kana  VARCHAR(100) DEFAULT NULL,
  note                 VARCHAR(255) DEFAULT NULL,
  is_primary           TINYINT(1) NOT NULL DEFAULT 1,
  created_at           DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at           DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_user_primary (user_id, is_primary),
  CONSTRAINT fk_bank_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS password_resets (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  email       VARCHAR(191) NOT NULL,
  token       VARCHAR(64) NOT NULL,          -- SHA-256（平文トークンは保存しない）
  expires_at  DATETIME NOT NULL,
  used_at     DATETIME DEFAULT NULL,
  created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_password_resets_token (token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS messages (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  sender_id     INT UNSIGNED NOT NULL,
  recipient_id  INT UNSIGNED NOT NULL,
  subject       VARCHAR(255) DEFAULT NULL,
  message       TEXT,
  is_read       TINYINT(1) NOT NULL DEFAULT 0,
  created_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS job_requests (
  id                   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  client_id            INT UNSIGNED NOT NULL,
  creator_id           INT UNSIGNED NOT NULL,
  work_id              INT UNSIGNED DEFAULT NULL,
  project_title        VARCHAR(191) NOT NULL,
  project_description  TEXT,
  budget_min           INT DEFAULT NULL,
  budget_max           INT DEFAULT NULL,
  deadline             DATE DEFAULT NULL,
  requirements         TEXT DEFAULT NULL,
  status               VARCHAR(32) NOT NULL DEFAULT 'pending',
  created_at           DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS success_stories (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  title           VARCHAR(255) NOT NULL,
  category_name   VARCHAR(100) DEFAULT NULL,
  tag_type        VARCHAR(50) DEFAULT NULL,
  interview_date  DATE DEFAULT NULL,
  intro_text      TEXT DEFAULT NULL,
  main_image      VARCHAR(255) DEFAULT NULL,
  member_image    VARCHAR(255) DEFAULT NULL,
  member_name     VARCHAR(100) DEFAULT NULL,
  member_role     VARCHAR(100) DEFAULT NULL,
  result_badge    VARCHAR(100) DEFAULT NULL,
  status          VARCHAR(20) NOT NULL DEFAULT 'draft',
  created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS success_story_sections (
  id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
  success_story_id  INT UNSIGNED NOT NULL,
  heading           VARCHAR(255) DEFAULT NULL,
  body_text         TEXT DEFAULT NULL,
  display_order     INT NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  KEY idx_story_sections_story (success_story_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS skills (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name         VARCHAR(100) NOT NULL,
  category_id  INT UNSIGNED DEFAULT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS user_skills (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id      INT UNSIGNED NOT NULL,
  skill_id     INT UNSIGNED NOT NULL,
  proficiency  INT NOT NULL DEFAULT 1,
  PRIMARY KEY (id),
  KEY idx_user_skills_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ライブ講座（AiNA本体の講座データと共用の可能性あり。列の追加は本体側と合わせて行う）
CREATE TABLE IF NOT EXISTS course_books (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  course_name   VARCHAR(191) NOT NULL,
  description   TEXT DEFAULT NULL,
  start         DATETIME NOT NULL,
  end           DATETIME DEFAULT NULL,
  instructor    VARCHAR(100) DEFAULT NULL,
  method        VARCHAR(50) DEFAULT NULL,
  place         VARCHAR(191) DEFAULT NULL,
  point_amount  INT DEFAULT NULL,
  zoom_id       VARCHAR(64) DEFAULT NULL,
  zoom_password VARCHAR(64) DEFAULT NULL,
  category_id   INT UNSIGNED DEFAULT NULL,
  booked_count  VARCHAR(16) DEFAULT '0',
  updated_at    DATETIME DEFAULT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS course_user_bookings (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  book_id     INT UNSIGNED NOT NULL,
  user_id     INT UNSIGNED NOT NULL,
  created_at  DATETIME DEFAULT NULL,
  updated_at  DATETIME DEFAULT NULL,
  PRIMARY KEY (id),
  KEY idx_course_bookings_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
