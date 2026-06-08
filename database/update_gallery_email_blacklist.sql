ALTER TABLE qr_links
  ADD COLUMN is_public TINYINT(1) NOT NULL DEFAULT 0 AFTER status,
  ADD COLUMN submitter_email VARCHAR(190) NULL AFTER is_public,
  ADD INDEX idx_gallery (status, is_public, created_at);

CREATE TABLE IF NOT EXISTS blacklist_words (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  word VARCHAR(50) NOT NULL UNIQUE,
  created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO blacklist_words (word, created_at) VALUES
('admin', NOW()),
('login', NOW()),
('logout', NOW()),
('api', NOW()),
('config', NOW()),
('root', NOW()),
('system', NOW());
