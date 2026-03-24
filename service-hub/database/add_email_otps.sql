-- ============================================================
--  OTP Verification Table
--  Run this in phpMyAdmin on the `service_hub` database
-- ============================================================

CREATE TABLE IF NOT EXISTS `email_otps` (
  `id`         INT(11)       NOT NULL AUTO_INCREMENT,
  `email`      VARCHAR(100)  NOT NULL,
  `otp`        VARCHAR(6)    NOT NULL,
  `expires_at` DATETIME      NOT NULL,
  `created_at` TIMESTAMP     NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_email` (`email`),   -- one active OTP per email
  KEY `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
