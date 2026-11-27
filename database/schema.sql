-- ============================================================
-- SDMS — Secure Document Management System
-- database/schema.sql — creates ALL tables inside the CURRENT database.
--
-- HOW TO IMPORT:
--   1. Make sure the database exists and is SELECTED first:
--      · localhost: phpMyAdmin → New → create `sdms` (once, utf8mb4_unicode_ci)
--      · staging:   the database you made in the hosting panel (doc 09, Step 2)
--   2. phpMyAdmin: click the database name in the LEFT sidebar → Import → this file
--      (then import database/seed.sql right after)
--   Or CLI:      mysql -u root sdms -e "source D:/Projects/SDMS/database/schema.sql"
--
-- ⚠ There are no CREATE DATABASE / USE / DROP DATABASE statements here:
--   free hosts (InfinityFree) disable them, and one file must work on both
--   localhost and staging. Re-importing still RESETS everything: it drops and
--   recreates every table, so any data inside them is erased.
--
-- Design reference: docs/03-DATABASE-DESIGN.md (ERD + column explanations)
-- ============================================================

-- Drop children before parents so the foreign keys never complain.
DROP TABLE IF EXISTS document_access_logs;   -- child of documents
DROP TABLE IF EXISTS activity_logs;          -- child of users
DROP TABLE IF EXISTS documents;              -- child of users, document_categories
DROP TABLE IF EXISTS login_attempts;         -- standalone
DROP TABLE IF EXISTS users;                  -- child of roles
DROP TABLE IF EXISTS document_categories;
DROP TABLE IF EXISTS roles;

-- ------------------------------------------------------------
-- roles — the three roles from the project manual (§D)
-- ------------------------------------------------------------
CREATE TABLE roles (
  role_id     TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  role_name   ENUM('admin','staff','viewer') NOT NULL UNIQUE,
  description VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- users — everyone who logs in (SR-1 hash only, SR-9 status, SR-3 role)
-- ------------------------------------------------------------
CREATE TABLE users (
  user_id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username        VARCHAR(50)  NOT NULL UNIQUE,
  email           VARCHAR(100) NOT NULL UNIQUE,
  password_hash   VARCHAR(255) NOT NULL,
  full_name       VARCHAR(100) NOT NULL,
  role_id         TINYINT UNSIGNED NOT NULL,
  status          ENUM('active','inactive') NOT NULL DEFAULT 'active',
  failed_attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
  last_login      DATETIME NULL,
  created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles(role_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- document_categories — managed by the admin (FR-27);
-- requires_approval implements "if approval is required" from the manual
-- ------------------------------------------------------------
CREATE TABLE document_categories (
  category_id       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category_name     VARCHAR(80) NOT NULL UNIQUE,
  description       VARCHAR(255) NULL,
  requires_approval TINYINT(1) NOT NULL DEFAULT 0,
  created_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- documents — metadata only; file bytes live in storage/uploads/
-- ------------------------------------------------------------
CREATE TABLE documents (
  document_id       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title             VARCHAR(150) NOT NULL,
  description       TEXT NULL,
  category_id       INT UNSIGNED NOT NULL,
  uploaded_by       INT UNSIGNED NOT NULL,
  original_filename VARCHAR(255) NOT NULL,
  stored_filename   CHAR(64) NOT NULL,
  file_ext          VARCHAR(10)  NOT NULL,
  mime_type         VARCHAR(100) NOT NULL,
  file_size         BIGINT UNSIGNED NOT NULL,
  file_hash         CHAR(64) NOT NULL,
  status            ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  reviewed_by       INT UNSIGNED NULL,
  reviewed_at       DATETIME NULL,
  review_remarks    VARCHAR(255) NULL,
  is_deleted        TINYINT(1) NOT NULL DEFAULT 0,
  deleted_at        DATETIME NULL,
  created_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_docs_category FOREIGN KEY (category_id) REFERENCES document_categories(category_id),
  CONSTRAINT fk_docs_uploader FOREIGN KEY (uploaded_by)  REFERENCES users(user_id),
  CONSTRAINT fk_docs_reviewer FOREIGN KEY (reviewed_by)  REFERENCES users(user_id),
  INDEX idx_docs_status   (status),
  INDEX idx_docs_category (category_id),
  INDEX idx_docs_uploader (uploaded_by),
  INDEX idx_docs_title    (title)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- activity_logs — append-only audit trail (SR-8)
-- ------------------------------------------------------------
CREATE TABLE activity_logs (
  log_id      BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id     INT UNSIGNED NULL,
  action      VARCHAR(100) NOT NULL,
  entity_type VARCHAR(50)  NULL,
  entity_id   INT UNSIGNED NULL,
  details     VARCHAR(255) NULL,
  ip_address  VARCHAR(45)  NULL,
  user_agent  VARCHAR(255) NULL,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_logs_user FOREIGN KEY (user_id) REFERENCES users(user_id),
  INDEX idx_logs_created (created_at),
  INDEX idx_logs_action  (action),
  INDEX idx_logs_user    (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- document_access_logs — who viewed/downloaded what, incl. denied tries
-- ------------------------------------------------------------
CREATE TABLE document_access_logs (
  access_id   BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  document_id INT UNSIGNED NOT NULL,
  user_id     INT UNSIGNED NULL,
  action      ENUM('view','download') NOT NULL,
  granted     TINYINT(1) NOT NULL DEFAULT 1,
  ip_address  VARCHAR(45) NULL,
  user_agent  VARCHAR(255) NULL,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_alog_doc  FOREIGN KEY (document_id) REFERENCES documents(document_id),
  CONSTRAINT fk_alog_user FOREIGN KEY (user_id)     REFERENCES users(user_id),
  INDEX idx_alog_doc     (document_id),
  INDEX idx_alog_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- login_attempts — optional, for login throttling (SR-2 enhancement)
-- ------------------------------------------------------------
CREATE TABLE login_attempts (
  attempt_id     BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username_tried VARCHAR(100) NOT NULL,
  ip_address     VARCHAR(45)  NOT NULL,
  success        TINYINT(1)   NOT NULL DEFAULT 0,
  created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_attempt (username_tried, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
