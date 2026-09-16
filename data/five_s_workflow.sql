-- Run once after importing the main database dump.
-- This migration supplies the tables and fields used by the operational 5S flow.

CREATE TABLE IF NOT EXISTS notifications (
  id int NOT NULL AUTO_INCREMENT,
  user_id int NOT NULL,
  title varchar(150) NOT NULL,
  message text NOT NULL,
  link varchar(255) DEFAULT NULL,
  is_read tinyint(1) NOT NULL DEFAULT 0,
  created_at datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (id),
  KEY idx_notifications_user_read (user_id, is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS five_s_schedules (
  id int NOT NULL AUTO_INCREMENT,
  zone_id int NOT NULL,
  inspector_id int NOT NULL,
  schedule_date date NOT NULL,
  status enum('pending','completed') NOT NULL DEFAULT 'pending',
  created_at datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (id),
  UNIQUE KEY uq_five_s_schedule (zone_id, inspector_id, schedule_date),
  KEY idx_five_s_schedule_inspector_date (inspector_id, schedule_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE five_s_zones ADD COLUMN qr_code_hash varchar(255) DEFAULT NULL;
ALTER TABLE five_s_zones ADD COLUMN ok_reference_image varchar(255) DEFAULT NULL;
ALTER TABLE five_s_zones ADD COLUMN ng_reference_image varchar(255) DEFAULT NULL;
ALTER TABLE five_s_audits ADD COLUMN checklist_json text DEFAULT NULL;
ALTER TABLE five_s_audits ADD COLUMN actual_image varchar(255) DEFAULT NULL;

ALTER TABLE five_s_assignments ADD UNIQUE KEY uq_five_s_assignment (zone_id, month_year);

-- Until real QR/NFC tokens are provisioned, the printed zone code is accepted as the token.
UPDATE five_s_zones SET qr_code_hash = zone_code WHERE qr_code_hash IS NULL OR qr_code_hash = '';