-- ----------------------------------------------------------
-- jta_assistants (DB reale)
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `jta_assistants` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `pharma_id` int UNSIGNED NOT NULL,
  `first_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `preferred_contact` enum('phone','email','whatsapp') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` enum('caregiver','familiare') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'familiare',
  `relation_to_patient` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `extra_info` json DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_assistants_pharma` (`pharma_id`),
  KEY `idx_assistants_name` (`last_name`,`first_name`),
  CONSTRAINT `fk_assistant_pharma`
    FOREIGN KEY (`pharma_id`) REFERENCES `jta_pharmas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- jta_therapies (DB reale)
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `jta_therapies` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `pharmacy_id` int UNSIGNED NOT NULL,
  `patient_id` int UNSIGNED NOT NULL,
  `therapy_title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `therapy_description` text COLLATE utf8mb4_unicode_ci,
  `status` enum('active','planned','completed','suspended') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_therapy_pharma` (`pharmacy_id`),
  KEY `idx_therapy_patient` (`patient_id`),
  KEY `idx_therapy_status` (`status`,`start_date`),
  CONSTRAINT `fk_therapy_patient`
    FOREIGN KEY (`patient_id`) REFERENCES `jta_patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_therapy_pharma`
    FOREIGN KEY (`pharmacy_id`) REFERENCES `jta_pharmas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- jta_therapy_assistant (DB reale)
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `jta_therapy_assistant` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `therapy_id` int UNSIGNED NOT NULL,
  `assistant_id` int UNSIGNED NOT NULL,
  `role` enum('caregiver','familiare') COLLATE utf8mb4_unicode_ci NOT NULL,
  `preferences_json` json DEFAULT NULL,
  `consents_json` json DEFAULT NULL,
  `contact_channel` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ta_unique` (`therapy_id`,`assistant_id`),
  KEY `fk_ta_assistant` (`assistant_id`),
  CONSTRAINT `fk_ta_assistant`
    FOREIGN KEY (`assistant_id`) REFERENCES `jta_assistants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ta_therapy`
    FOREIGN KEY (`therapy_id`) REFERENCES `jta_therapies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- jta_therapy_chronic_care (DB reale)
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `jta_therapy_chronic_care` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `therapy_id` int UNSIGNED NOT NULL,
  `primary_condition` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `care_context` json DEFAULT NULL,
  `doctor_info` json DEFAULT NULL,
  `general_anamnesis` json DEFAULT NULL,
  `biometric_info` json DEFAULT NULL,
  `detailed_intake` json DEFAULT NULL,
  `adherence_base` json DEFAULT NULL,
  `risk_score` int DEFAULT NULL,
  `flags` json DEFAULT NULL,
  `notes_initial` text COLLATE utf8mb4_unicode_ci,
  `follow_up_date` date DEFAULT NULL,
  `consent` json DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tcc_therapy` (`therapy_id`),
  KEY `idx_tcc_condition` (`primary_condition`),
  CONSTRAINT `fk_tcc_therapy`
    FOREIGN KEY (`therapy_id`) REFERENCES `jta_therapies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- jta_therapy_condition_surveys (DB reale)
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `jta_therapy_condition_surveys` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `therapy_id` int UNSIGNED NOT NULL,
  `condition_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `level` enum('base','approfondito') COLLATE utf8mb4_unicode_ci NOT NULL,
  `answers` json DEFAULT NULL,
  `compiled_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tcs_therapy` (`therapy_id`),
  KEY `idx_tcs_condition` (`condition_type`,`level`),
  CONSTRAINT `fk_tcs_therapy`
    FOREIGN KEY (`therapy_id`) REFERENCES `jta_therapies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- jta_therapy_followups (DB reale + PATCH: entry_type)
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `jta_therapy_followups` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `therapy_id` int UNSIGNED NOT NULL,
  `entry_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'followup',
  `risk_score` int DEFAULT NULL,
  `pharmacist_notes` text COLLATE utf8mb4_unicode_ci,
  `education_notes` text COLLATE utf8mb4_unicode_ci,
  `snapshot` json DEFAULT NULL,
  `follow_up_date` date DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tf_therapy` (`therapy_id`),
  KEY `idx_tf_followup` (`follow_up_date`),
  KEY `idx_tf_entry_type` (`entry_type`),
  CONSTRAINT `fk_tf_therapy`
    FOREIGN KEY (`therapy_id`) REFERENCES `jta_therapies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- jta_therapy_reminders (DB reale + PATCH: status include shown)
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `jta_therapy_reminders` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `therapy_id` int UNSIGNED NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('one-shot','daily','weekly','monthly') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'one-shot',
  `scheduled_at` datetime NOT NULL,
  `channel` enum('email','sms','push') COLLATE utf8mb4_unicode_ci DEFAULT 'email',
  `status` enum('scheduled','shown','sent','canceled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'scheduled',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tr_therapy` (`therapy_id`),
  KEY `idx_tr_schedule` (`scheduled_at`),
  CONSTRAINT `fk_tr_therapy`
    FOREIGN KEY (`therapy_id`) REFERENCES `jta_therapies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- jta_therapy_reports (DB reale)
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `jta_therapy_reports` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `therapy_id` int UNSIGNED NOT NULL,
  `pharmacy_id` int UNSIGNED NOT NULL,
  `content` json NOT NULL,
  `share_token` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pin_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `valid_until` datetime DEFAULT NULL,
  `recipients` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_r_pharma` (`pharmacy_id`),
  KEY `idx_r_therapy` (`therapy_id`),
  KEY `idx_r_token` (`share_token`),
  CONSTRAINT `fk_r_pharma`
    FOREIGN KEY (`pharmacy_id`) REFERENCES `jta_pharmas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_r_therapy`
    FOREIGN KEY (`therapy_id`) REFERENCES `jta_therapies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- jta_therapy_consents (DB reale)
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `jta_therapy_consents` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `therapy_id` int UNSIGNED NOT NULL,
  `signer_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `signer_relation` enum('patient','caregiver','familiare') COLLATE utf8mb4_unicode_ci NOT NULL,
  `consent_text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `signed_at` datetime NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `signature_image` longblob,
  `scopes_json` json DEFAULT NULL,
  `signer_role` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tc_therapy` (`therapy_id`),
  KEY `idx_tc_signer` (`signer_name`),
  CONSTRAINT `fk_tc_therapy`
    FOREIGN KEY (`therapy_id`) REFERENCES `jta_therapies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ==========================================================
-- MIGRAZIONI DA APPLICARE SU DB ESISTENTE (se già creato)
-- (Esegui SOLO se il tuo DB live è ancora “senza patch”)
-- ==========================================================

-- 1) jta_therapy_reminders: aggiunge 'shown' allo status (DB live dump aveva solo scheduled/sent/canceled)
-- ALTER TABLE `jta_therapy_reminders`
--   MODIFY `status` enum('scheduled','shown','sent','canceled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'scheduled';

-- 2) jta_therapy_followups: aggiunge entry_type e backfill
-- ALTER TABLE `jta_therapy_followups`
--   ADD COLUMN `entry_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'followup' AFTER `therapy_id`,
--   ADD KEY `idx_tf_entry_type` (`entry_type`);
--
-- UPDATE `jta_therapy_followups`
-- SET entry_type = CASE
--   WHEN snapshot IS NOT NULL AND JSON_LENGTH(snapshot) IS NOT NULL THEN 'check'
--   ELSE 'followup'
-- END
-- WHERE entry_type IS NULL OR entry_type = '' OR entry_type = 'followup';