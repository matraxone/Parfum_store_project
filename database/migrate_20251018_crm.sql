USE `parfum_shop`;
-- Make column additions idempotent (safe to run multiple times)
ALTER TABLE users ADD COLUMN IF NOT EXISTS phone VARCHAR(30) NULL AFTER parent_consent;
ALTER TABLE users ADD COLUMN IF NOT EXISTS company VARCHAR(160) NULL AFTER phone;
ALTER TABLE users ADD COLUMN IF NOT EXISTS crm_status ENUM('lead','prospect','customer','inactive') NOT NULL DEFAULT 'customer' AFTER company;
ALTER TABLE users ADD COLUMN IF NOT EXISTS last_contact_at DATETIME NULL AFTER crm_status;

CREATE TABLE IF NOT EXISTS crm_notes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  author_id INT NULL,
  note TEXT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_crmnotes_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_crmnotes_author FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;