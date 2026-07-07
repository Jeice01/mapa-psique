CREATE TABLE users (
  id CHAR(36) NOT NULL,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(255) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('administrador', 'profissional', 'paciente', 'auditor') NOT NULL DEFAULT 'profissional',
  status ENUM('active', 'inactive', 'blocked', 'pending') NOT NULL DEFAULT 'pending',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY users_email_unique (email),
  KEY users_status_idx (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE patients (
  id CHAR(36) NOT NULL,
  owner_user_id CHAR(36) NOT NULL,
  name VARCHAR(160) NOT NULL,
  notes TEXT NULL,
  status ENUM('active', 'archived') NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY patients_owner_idx (owner_user_id),
  KEY patients_status_idx (status),
  CONSTRAINT patients_owner_user_fk FOREIGN KEY (owner_user_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE maps (
  id CHAR(36) NOT NULL,
  patient_id CHAR(36) NOT NULL,
  created_by_user_id CHAR(36) NOT NULL,
  title VARCHAR(180) NOT NULL,
  status ENUM('draft', 'processing', 'completed', 'archived') NOT NULL DEFAULT 'draft',
  data JSON NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY maps_patient_idx (patient_id),
  KEY maps_created_by_idx (created_by_user_id),
  KEY maps_status_idx (status),
  CONSTRAINT maps_patient_fk FOREIGN KEY (patient_id) REFERENCES patients (id),
  CONSTRAINT maps_created_by_user_fk FOREIGN KEY (created_by_user_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE audit_logs (
  id CHAR(36) NOT NULL,
  actor_user_id CHAR(36) NULL,
  request_id CHAR(36) NULL,
  session_id VARCHAR(255) NULL,
  severity ENUM('INFO', 'WARN', 'ERROR', 'CRITICAL') NOT NULL DEFAULT 'INFO',
  action VARCHAR(100) NOT NULL,
  entity_type VARCHAR(50) NOT NULL,
  entity_id CHAR(36) NULL,
  metadata_json JSON NULL,
  ip_address VARCHAR(45) NULL,
  user_agent TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY audit_logs_created_at_idx (created_at),
  CONSTRAINT audit_logs_actor_user_fk FOREIGN KEY (actor_user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE consent_terms (
  id CHAR(36) NOT NULL,
  version VARCHAR(40) NOT NULL,
  title VARCHAR(180) NOT NULL,
  content TEXT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY consent_terms_version_unique (version)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE user_consents (
  id CHAR(36) NOT NULL,
  user_id CHAR(36) NOT NULL,
  consent_term_id CHAR(36) NOT NULL,
  status ENUM('accepted', 'revoked') NOT NULL DEFAULT 'accepted',
  accepted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  revoked_at DATETIME NULL,
  ip_address VARCHAR(45) NULL,
  user_agent VARCHAR(255) NULL,
  metadata JSON NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY user_consents_term_idx (consent_term_id),
  CONSTRAINT user_consents_user_fk FOREIGN KEY (user_id) REFERENCES users (id),
  CONSTRAINT user_consents_term_fk FOREIGN KEY (consent_term_id) REFERENCES consent_terms (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE ai_processing_logs (
  id CHAR(36) NOT NULL,
  map_id CHAR(36) NULL,
  map_file_id CHAR(36) NULL,
  provider VARCHAR(50) NOT NULL DEFAULT 'openai',
  provider_file_id VARCHAR(255) NULL,
  provider_vector_store_id VARCHAR(255) NULL,
  action ENUM(
    'upload',
    'analysis_generation',
    'file_deletion',
    'vector_store_creation',
    'vector_store_deletion'
  ) NOT NULL,
  status ENUM(
    'success',
    'failed',
    'pending_delete',
    'pending_retry',
    'processing'
  ) NOT NULL DEFAULT 'processing',
  error_message TEXT NULL,
  retry_count INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL,
  resolved_at DATETIME NULL,
  PRIMARY KEY (id),
  KEY ai_processing_logs_map_idx (map_id),
  CONSTRAINT ai_processing_logs_map_fk FOREIGN KEY (map_id) REFERENCES maps (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_audit_logs_actor_created
ON audit_logs(actor_user_id, created_at);

CREATE INDEX idx_audit_logs_entity
ON audit_logs(entity_type, entity_id);

CREATE INDEX idx_user_consents_user_status
ON user_consents(user_id, status);

CREATE INDEX idx_ai_processing_pending
ON ai_processing_logs(status, created_at);

DELIMITER $$

CREATE TRIGGER prevent_audit_update
BEFORE UPDATE ON audit_logs
FOR EACH ROW
BEGIN
  SIGNAL SQLSTATE '45000'
  SET MESSAGE_TEXT = 'audit_logs is append-only';
END$$

CREATE TRIGGER prevent_audit_delete
BEFORE DELETE ON audit_logs
FOR EACH ROW
BEGIN
  SIGNAL SQLSTATE '45000'
  SET MESSAGE_TEXT = 'audit_logs is append-only';
END$$

DELIMITER ;
