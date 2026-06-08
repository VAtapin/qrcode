ALTER TABLE qr_links
  ADD COLUMN locale VARCHAR(5) NOT NULL DEFAULT 'de' AFTER submitter_email;

ALTER TABLE admins
  ADD COLUMN locale VARCHAR(5) NOT NULL DEFAULT 'de' AFTER password_hash;
