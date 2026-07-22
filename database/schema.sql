-- Central Library — raw PHP + MySQL rebuild
-- Schema: books, members, loans

CREATE DATABASE IF NOT EXISTS central_library
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE central_library;

CREATE TABLE IF NOT EXISTS books (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title           VARCHAR(255) NOT NULL,
  author          VARCHAR(255) NOT NULL DEFAULT 'Unknown',
  isbn            VARCHAR(20)  DEFAULT NULL,
  total_copies    INT UNSIGNED NOT NULL DEFAULT 1,
  available_copies INT UNSIGNED NOT NULL DEFAULT 1,
  created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT chk_copies CHECK (available_copies <= total_copies),
  UNIQUE KEY uq_books_title_author (title, author)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS members (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(255) NOT NULL,
  email       VARCHAR(255) DEFAULT NULL,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_members_email (email)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS loans (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  book_id      INT UNSIGNED NOT NULL,
  member_id    INT UNSIGNED NOT NULL,
  borrowed_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  due_at       DATE NOT NULL,
  returned_at  TIMESTAMP NULL DEFAULT NULL,
  KEY idx_loans_book (book_id),
  KEY idx_loans_member (member_id),
  KEY idx_loans_active (returned_at),
  CONSTRAINT fk_loans_book   FOREIGN KEY (book_id)   REFERENCES books(id)   ON DELETE CASCADE,
  CONSTRAINT fk_loans_member FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB;
