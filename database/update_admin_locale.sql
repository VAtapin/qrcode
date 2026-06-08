ALTER TABLE admins
  ADD COLUMN locale VARCHAR(5) NOT NULL DEFAULT 'de' AFTER password_hash;
