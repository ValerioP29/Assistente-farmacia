
/* Tabella pazienti */
CREATE TABLE jta_patients (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pharmacy_id INT UNSIGNED NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    birth_date DATE NULL,
    codice_fiscale VARCHAR(16) NULL,
    phone VARCHAR(30) NULL,
    email VARCHAR(150) NULL,
    notes TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_patient_pharma FOREIGN KEY (pharmacy_id) REFERENCES jta_pharmas(id) ON DELETE SET NULL,
    INDEX idx_patients_cf (codice_fiscale),
    INDEX idx_patients_name (last_name, first_name),
    INDEX idx_patients_pharmacy (pharmacy_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/* Tabella per Familiari e Caregiver */
CREATE TABLE jta_assistants (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pharma_id INT UNSIGNED NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NULL,
    phone VARCHAR(30) NULL,
    email VARCHAR(150) NULL,
    type ENUM('caregiver','familiare') NOT NULL DEFAULT 'familiare',
    notes TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_assistant_pharma FOREIGN KEY (pharma_id) REFERENCES jta_pharmas(id) ON DELETE CASCADE,
    INDEX idx_assistants_pharma (pharma_id),
    INDEX idx_assistants_name (last_name, first_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/* Tabella pivot per associazione molti a molti paziente farmacia */
CREATE TABLE jta_pharma_patient (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pharma_id INT UNSIGNED NOT NULL,
    patient_id INT UNSIGNED NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    active_flag TINYINT(1) AS (CASE WHEN deleted_at IS NULL THEN 1 ELSE 0 END) STORED,
    CONSTRAINT fk_pp_pharma FOREIGN KEY (pharma_id) REFERENCES jta_pharmas(id) ON DELETE CASCADE,
    CONSTRAINT fk_pp_patient FOREIGN KEY (patient_id) REFERENCES jta_patients(id) ON DELETE CASCADE,
    UNIQUE KEY uq_pp_active (pharma_id, patient_id, active_flag),
    INDEX idx_pp_pharma (pharma_id),
    INDEX idx_pp_patient (patient_id),
    INDEX idx_pp_deleted (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/* TABELLA TERAPIE - DEVE ESSERE CREATA PRIMA DELLE TABELLE CHE LA REFERENZIANO */
CREATE TABLE jta_therapies (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pharmacy_id INT UNSIGNED NOT NULL,
    patient_id INT UNSIGNED NOT NULL,
    therapy_title VARCHAR(255) NOT NULL,
    therapy_description TEXT NULL,
    status ENUM('active','planned','completed','suspended') NOT NULL DEFAULT 'active',
    start_date DATE NULL,
    end_date DATE NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_therapy_pharma FOREIGN KEY (pharmacy_id) REFERENCES jta_pharmas(id) ON DELETE CASCADE,
    CONSTRAINT fk_therapy_patient FOREIGN KEY (patient_id) REFERENCES jta_patients(id) ON DELETE CASCADE,
    INDEX idx_therapy_pharma (pharmacy_id),
    INDEX idx_therapy_patient (patient_id),
    INDEX idx_therapy_status (status, start_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/* ASSISTENTI TERAPIE */
CREATE TABLE jta_therapy_assistant (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    therapy_id INT UNSIGNED NOT NULL,
    assistant_id INT UNSIGNED NOT NULL,
    role ENUM('caregiver','familiare') NOT NULL,
    preferences_json JSON NULL,
    consents_json JSON NULL,
    contact_channel VARCHAR(20) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ta_therapy FOREIGN KEY (therapy_id) REFERENCES jta_therapies(id) ON DELETE CASCADE,
    CONSTRAINT fk_ta_assistant FOREIGN KEY (assistant_id) REFERENCES jta_assistants(id) ON DELETE CASCADE,
    UNIQUE KEY uq_ta_unique (therapy_id, assistant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/* CONSENSI */
CREATE TABLE jta_therapy_consents (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    therapy_id INT UNSIGNED NOT NULL,
    signer_name VARCHAR(150) NOT NULL,
    signer_relation ENUM('patient','caregiver','familiare') NOT NULL,
    consent_text TEXT NOT NULL,
    signed_at DATETIME NOT NULL,
    ip_address VARCHAR(45) NULL,
    signature_image LONGBLOB NULL,
    scopes_json JSON NULL,
    signer_role VARCHAR(20) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_tc_therapy FOREIGN KEY (therapy_id) REFERENCES jta_therapies(id) ON DELETE CASCADE,
    INDEX idx_tc_therapy (therapy_id),
    INDEX idx_tc_signer (signer_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/* CRONICO: PRESA IN CARICO */
CREATE TABLE jta_therapy_chronic_care (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    therapy_id INT UNSIGNED NOT NULL,
    primary_condition VARCHAR(50) NOT NULL,
    care_context JSON NULL,
    general_anamnesis JSON NULL,
    detailed_intake JSON NULL,
    adherence_base JSON NULL,
    risk_score INT NULL,
    flags JSON NULL,
    notes_initial TEXT NULL,
    follow_up_date DATE NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_tcc_therapy FOREIGN KEY (therapy_id) REFERENCES jta_therapies(id) ON DELETE CASCADE,
    INDEX idx_tcc_therapy (therapy_id),
    INDEX idx_tcc_condition (primary_condition)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/* QUESTIONARI PER PATOLOGIA */
CREATE TABLE jta_therapy_condition_surveys (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    therapy_id INT UNSIGNED NOT NULL,
    condition_type VARCHAR(50) NOT NULL,
    level ENUM('base','approfondito') NOT NULL,
    answers JSON NULL,
    compiled_at DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    CONSTRAINT fk_tcs_therapy FOREIGN KEY (therapy_id)
        REFERENCES jta_therapies(id) ON DELETE CASCADE,

    INDEX idx_tcs_therapy (therapy_id),
    INDEX idx_tcs_condition (condition_type, level)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/* FOLLOW-UP CLINICI */
CREATE TABLE jta_therapy_followups (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    therapy_id INT UNSIGNED NOT NULL,
    risk_score INT NULL,
    pharmacist_notes TEXT NULL,
    education_notes TEXT NULL,
    snapshot JSON NULL,
    follow_up_date DATE NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_tf_therapy FOREIGN KEY (therapy_id) REFERENCES jta_therapies(id) ON DELETE CASCADE,
    INDEX idx_tf_therapy (therapy_id),
    INDEX idx_tf_followup (follow_up_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/* REPORTS */
CREATE TABLE jta_therapy_reports (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    therapy_id INT UNSIGNED NOT NULL,
    pharmacy_id INT UNSIGNED NOT NULL,
    content JSON NOT NULL,
    share_token VARCHAR(64) NULL,
    pin_code VARCHAR(255) NULL,
    valid_until DATETIME NULL,
    recipients VARCHAR(255) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_r_therapy FOREIGN KEY (therapy_id) REFERENCES jta_therapies(id) ON DELETE CASCADE,
    CONSTRAINT fk_r_pharma FOREIGN KEY (pharmacy_id) REFERENCES jta_pharmas(id) ON DELETE CASCADE,
    INDEX idx_r_therapy (therapy_id),
    INDEX idx_r_token (share_token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/* REMINDERS */
CREATE TABLE jta_therapy_reminders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    therapy_id INT UNSIGNED NOT NULL,

    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,

    type ENUM('one-shot','daily','weekly','monthly') NOT NULL DEFAULT 'one-shot',

    scheduled_at DATETIME NOT NULL,

    channel ENUM('email','sms','push') DEFAULT 'email',
    status ENUM('scheduled','sent','canceled') DEFAULT 'scheduled',

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_tr_therapy FOREIGN KEY (therapy_id) REFERENCES jta_therapies(id) ON DELETE CASCADE,

    INDEX idx_tr_therapy (therapy_id),
    INDEX idx_tr_schedule (scheduled_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
