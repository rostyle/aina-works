-- AiNA Works Database Schema (draft to match current code usage)
-- MySQL/MariaDB, utf8mb4

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
  status               VARCHAR(32) NOT NULL DEFAULT 'open',
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
  file_path  VARCHAR(255) DEFAULT NULL,
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

