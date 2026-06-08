ALTER TABLE qr_links
  ADD COLUMN locale VARCHAR(5) NOT NULL DEFAULT 'de' AFTER submitter_email;
