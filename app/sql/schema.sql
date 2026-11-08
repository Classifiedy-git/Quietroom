-- Classifiedy: MySQL schema (InnoDB / utf8mb4)
-- Safe to run repeatedly: every statement uses IF NOT EXISTS.

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  email VARCHAR(190) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  display_name VARCHAR(60) NOT NULL,
  recovery_question VARCHAR(255) NULL,
  recovery_hash VARCHAR(255) NULL,
  country VARCHAR(4) NOT NULL DEFAULT 'US',
  timezone VARCHAR(64) NOT NULL DEFAULT 'UTC',
  theme VARCHAR(20) NOT NULL DEFAULT 'calm',
  mode VARCHAR(10) NOT NULL DEFAULT 'light',
  reduce_motion TINYINT(1) NOT NULL DEFAULT 0,
  ai_enabled TINYINT(1) NOT NULL DEFAULT 1,
  ai_style VARCHAR(20) NOT NULL DEFAULT 'supportive',
  support_contact_name VARCHAR(120) NULL,
  support_contact_phone VARCHAR(60) NULL,
  support_contact_email VARCHAR(190) NULL,
  reminder_enabled TINYINT(1) NOT NULL DEFAULT 0,
  reminder_time VARCHAR(5) NULL,
  chat_cleared_at DATETIME NULL,
  streak INT NOT NULL DEFAULT 0,
  longest_streak INT NOT NULL DEFAULT 0,
  last_checkin_date DATE NULL,
  is_admin TINYINT(1) NOT NULL DEFAULT 0,
  consented_at DATETIME NULL,
  created_at DATETIME NOT NULL,
  last_login_at DATETIME NULL,
  deleted_at DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_email (email),
  KEY idx_users_admin (is_admin),
  KEY idx_users_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mood_entries (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
  entry_date DATE NOT NULL,
  mood TINYINT NOT NULL DEFAULT 5,
  anxiety TINYINT NOT NULL DEFAULT 5,
  energy TINYINT NOT NULL DEFAULT 5,
  sleep_hours DECIMAL(3,1) NULL,
  triggers VARCHAR(255) NULL,
  note TEXT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_mood_user_date (user_id, entry_date),
  KEY idx_mood_user_date (user_id, entry_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS journal_entries (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
  title VARCHAR(200) NOT NULL DEFAULT '',
  body MEDIUMTEXT NOT NULL,
  entry_type VARCHAR(20) NOT NULL DEFAULT 'free',
  prompt VARCHAR(255) NULL,
  mood TINYINT NULL,
  ai_reflection MEDIUMTEXT NULL,
  ai_reflection_at DATETIME NULL,
  is_favorite TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_journal_user (user_id, created_at),
  KEY idx_journal_fav (user_id, is_favorite)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS thought_records (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
  situation TEXT NULL,
  emotions VARCHAR(200) NULL,
  emotion_intensity TINYINT NULL,
  automatic_thought TEXT NULL,
  evidence_for TEXT NULL,
  evidence_against TEXT NULL,
  alternative_thought TEXT NULL,
  re_rate TINYINT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_thought_user (user_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS behaviors (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
  title VARCHAR(200) NOT NULL,
  category VARCHAR(60) NOT NULL DEFAULT 'Pleasure',
  difficulty TINYINT NOT NULL DEFAULT 5,
  pleasure TINYINT NOT NULL DEFAULT 5,
  status VARCHAR(16) NOT NULL DEFAULT 'planned',
  scheduled_date DATE NULL,
  completed_at DATETIME NULL,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_behavior_user (user_id, status, scheduled_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS chat_messages (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
  role VARCHAR(16) NOT NULL,
  content MEDIUMTEXT NOT NULL,
  crisis_level VARCHAR(16) NOT NULL DEFAULT 'none',
  model VARCHAR(60) NULL,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_chat_user (user_id, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS exercise_logs (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
  exercise_key VARCHAR(60) NOT NULL,
  duration_seconds INT NOT NULL DEFAULT 0,
  completed TINYINT(1) NOT NULL DEFAULT 0,
  mood_before TINYINT NULL,
  mood_after TINYINT NULL,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_exercise_user (user_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS achievements (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
  badge_key VARCHAR(50) NOT NULL,
  earned_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_badge (user_id, badge_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS remember_tokens (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT Unsigned NOT NULL,
  selector CHAR(18) NOT NULL,
  validator_hash CHAR(64) NOT NULL,
  expires_at DATETIME NOT NULL,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_selector (selector),
  KEY idx_remember_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS settings (
  k VARCHAR(60) NOT NULL,
  v TEXT NULL,
  updated_at DATETIME NULL,
  PRIMARY KEY (k)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ai_usage (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NULL,
  model VARCHAR(60) NULL,
  prompt_tokens INT NOT NULL DEFAULT 0,
  response_tokens INT NOT NULL DEFAULT 0,
  latency_ms INT NOT NULL DEFAULT 0,
  status VARCHAR(16) NOT NULL DEFAULT 'ok',
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_usage_user (user_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
