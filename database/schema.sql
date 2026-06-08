CREATE TABLE admins (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  login VARCHAR(80) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at DATETIME NOT NULL,
  last_login_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE qr_links (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  short_code VARCHAR(50) NOT NULL UNIQUE,
  title VARCHAR(190) NOT NULL,
  target_url TEXT NOT NULL,
  qr_color CHAR(7) NOT NULL DEFAULT '#000000',
  qr_path VARCHAR(255) NULL,
  status ENUM('pending','approved','rejected','blocked') NOT NULL DEFAULT 'pending',
  is_public TINYINT(1) NOT NULL DEFAULT 0,
  submitter_email VARCHAR(190) NULL,
  locale VARCHAR(5) NOT NULL DEFAULT 'de',
  comment TEXT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  approved_at DATETIME NULL,
  rejected_at DATETIME NULL,
  created_ip_hash CHAR(64) NOT NULL,
  admin_note TEXT NULL,
  INDEX idx_status_created (status, created_at),
  INDEX idx_gallery (status, is_public, created_at),
  INDEX idx_short_code_status (short_code, status),
  INDEX idx_created_ip_time (created_ip_hash, created_at),
  INDEX idx_target_url (target_url(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE blacklist_words (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  word VARCHAR(50) NOT NULL UNIQUE,
  created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO blacklist_words (word, created_at) VALUES
('admin', NOW()),
('login', NOW()),
('logout', NOW()),
('api', NOW()),
('config', NOW()),
('root', NOW()),
('system', NOW());

CREATE TABLE qr_clicks (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  link_id INT UNSIGNED NOT NULL,
  clicked_at DATETIME NOT NULL,
  ip_hash CHAR(64) NOT NULL,
  user_agent VARCHAR(1000) NULL,
  referer VARCHAR(1000) NULL,
  INDEX idx_link_clicked (link_id, clicked_at),
  CONSTRAINT fk_qr_clicks_link FOREIGN KEY (link_id) REFERENCES qr_links(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE admin_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  admin_id INT UNSIGNED NOT NULL,
  action VARCHAR(80) NOT NULL,
  link_id INT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  ip_hash CHAR(64) NOT NULL,
  INDEX idx_admin_created (admin_id, created_at),
  INDEX idx_link_created (link_id, created_at),
  CONSTRAINT fk_admin_logs_admin FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE,
  CONSTRAINT fk_admin_logs_link FOREIGN KEY (link_id) REFERENCES qr_links(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
