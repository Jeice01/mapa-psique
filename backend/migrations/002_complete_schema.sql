ALTER TABLE users
  MODIFY name VARCHAR(255) NOT NULL,
  MODIFY role ENUM('administrador', 'profissional', 'paciente', 'auditor') NOT NULL DEFAULT 'profissional',
  MODIFY status ENUM('active', 'inactive', 'blocked', 'pending') NOT NULL DEFAULT 'pending',
  MODIFY updated_at DATETIME NULL,
  ADD COLUMN last_login_at DATETIME NULL AFTER status,
  ADD COLUMN deleted_at DATETIME NULL AFTER updated_at,
  ADD COLUMN deleted_by CHAR(36) NULL AFTER deleted_at,
  ADD CONSTRAINT users_deleted_by_fk FOREIGN KEY (deleted_by) REFERENCES users(id) ON DELETE SET NULL;

CREATE INDEX idx_users_role_status ON users(role, status);
CREATE INDEX idx_users_deleted_at ON users(deleted_at);

ALTER TABLE patients
  MODIFY name VARCHAR(255) NOT NULL,
  MODIFY notes TEXT NULL,
  MODIFY status ENUM('active', 'inactive', 'archived') NOT NULL DEFAULT 'active',
  MODIFY updated_at DATETIME NULL,
  ADD COLUMN internal_code VARCHAR(100) NULL AFTER name,
  ADD COLUMN age INT NULL AFTER internal_code,
  ADD COLUMN deleted_at DATETIME NULL AFTER updated_at,
  ADD COLUMN deleted_by CHAR(36) NULL AFTER deleted_at,
  ADD CONSTRAINT patients_deleted_by_fk FOREIGN KEY (deleted_by) REFERENCES users(id) ON DELETE SET NULL;

CREATE INDEX idx_patients_owner ON patients(owner_user_id);
CREATE INDEX idx_patients_internal_code ON patients(owner_user_id, internal_code);
CREATE INDEX idx_patients_status ON patients(status);
CREATE INDEX idx_patients_deleted_at ON patients(deleted_at);

ALTER TABLE maps
  DROP FOREIGN KEY maps_created_by_user_fk,
  DROP FOREIGN KEY maps_patient_fk;

ALTER TABLE maps
  ADD COLUMN owner_user_id CHAR(36) NULL AFTER id,
  MODIFY patient_id CHAR(36) NULL,
  MODIFY title VARCHAR(255) NOT NULL,
  CHANGE data canvas_json JSON NULL,
  MODIFY status ENUM('draft', 'ready_for_analysis', 'analyzed', 'archived') NOT NULL DEFAULT 'draft',
  MODIFY updated_at DATETIME NULL,
  ADD COLUMN reason TEXT NULL AFTER title,
  ADD COLUMN canvas_image_path VARCHAR(500) NULL AFTER canvas_json,
  ADD COLUMN canvas_version VARCHAR(30) NULL DEFAULT '1.0' AFTER canvas_image_path,
  ADD COLUMN coordinate_system_version VARCHAR(30) NULL DEFAULT '1.0' AFTER canvas_version,
  ADD COLUMN revealed_quadrants BOOLEAN NOT NULL DEFAULT FALSE AFTER coordinate_system_version,
  ADD COLUMN deleted_at DATETIME NULL AFTER updated_at,
  ADD COLUMN deleted_by CHAR(36) NULL AFTER deleted_at;

UPDATE maps SET owner_user_id = created_by_user_id WHERE owner_user_id IS NULL;

ALTER TABLE maps
  MODIFY owner_user_id CHAR(36) NOT NULL,
  DROP COLUMN created_by_user_id,
  ADD CONSTRAINT maps_owner_user_fk FOREIGN KEY (owner_user_id) REFERENCES users(id) ON DELETE RESTRICT,
  ADD CONSTRAINT maps_patient_fk FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE SET NULL,
  ADD CONSTRAINT maps_deleted_by_fk FOREIGN KEY (deleted_by) REFERENCES users(id) ON DELETE SET NULL;

CREATE INDEX idx_maps_owner ON maps(owner_user_id);
CREATE INDEX idx_maps_patient ON maps(patient_id);
CREATE INDEX idx_maps_status ON maps(status);
CREATE INDEX idx_maps_created_at ON maps(created_at);
CREATE INDEX idx_maps_deleted_at ON maps(deleted_at);

ALTER TABLE consent_terms
  MODIFY version VARCHAR(20) NOT NULL,
  MODIFY title VARCHAR(255) NOT NULL,
  ADD COLUMN active BOOLEAN NOT NULL DEFAULT TRUE AFTER content,
  ADD COLUMN updated_at DATETIME NULL AFTER created_at;

ALTER TABLE user_consents
  DROP FOREIGN KEY user_consents_user_fk,
  DROP FOREIGN KEY user_consents_term_fk;

ALTER TABLE user_consents
  ADD CONSTRAINT user_consents_user_fk FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,
  ADD CONSTRAINT user_consents_term_fk FOREIGN KEY (consent_term_id) REFERENCES consent_terms(id) ON DELETE RESTRICT;

CREATE INDEX idx_user_consents_term ON user_consents(consent_term_id);

CREATE TABLE map_items (
  id CHAR(36) NOT NULL,
  map_id CHAR(36) NOT NULL,
  type ENUM('pessoa', 'lugar', 'situacao') NOT NULL,
  label VARCHAR(255) NOT NULL,
  role VARCHAR(100) NULL,
  item_signal ENUM('positivo', 'negativo', 'neutro') NOT NULL DEFAULT 'neutro',
  quadrant ENUM('emocional', 'espiritual', 'passado', 'presente_fisico', 'centro', 'fora') NULL,
  distance_from_self ENUM('proximo', 'medio', 'longe', 'fora') NULL,
  x DECIMAL(8,3) NULL,
  y DECIMAL(8,3) NULL,
  is_outside_circle BOOLEAN NOT NULL DEFAULT FALSE,
  notes TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL,
  deleted_at DATETIME NULL,
  deleted_by CHAR(36) NULL,
  PRIMARY KEY (id),
  CONSTRAINT map_items_map_fk FOREIGN KEY (map_id) REFERENCES maps(id) ON DELETE RESTRICT,
  CONSTRAINT map_items_deleted_by_fk FOREIGN KEY (deleted_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_map_items_map ON map_items(map_id);
CREATE INDEX idx_map_items_type ON map_items(type);
CREATE INDEX idx_map_items_quadrant ON map_items(quadrant);
CREATE INDEX idx_map_items_signal ON map_items(item_signal);
CREATE INDEX idx_map_items_deleted_at ON map_items(deleted_at);

CREATE TABLE map_arrows (
  id CHAR(36) NOT NULL,
  map_id CHAR(36) NOT NULL,
  arrow_type ENUM('PS', 'PR', 'F') NOT NULL,
  quadrant ENUM('emocional', 'espiritual', 'passado', 'presente_fisico', 'centro', 'fora') NULL,
  size ENUM('pequena', 'media', 'grande') NOT NULL DEFAULT 'media',
  x DECIMAL(8,3) NULL,
  y DECIMAL(8,3) NULL,
  is_outside_circle BOOLEAN NOT NULL DEFAULT FALSE,
  placed_to_fill_empty_space BOOLEAN NOT NULL DEFAULT FALSE,
  notes TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL,
  deleted_at DATETIME NULL,
  deleted_by CHAR(36) NULL,
  PRIMARY KEY (id),
  UNIQUE KEY map_arrows_map_type_unique (map_id, arrow_type),
  CONSTRAINT map_arrows_map_fk FOREIGN KEY (map_id) REFERENCES maps(id) ON DELETE RESTRICT,
  CONSTRAINT map_arrows_deleted_by_fk FOREIGN KEY (deleted_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_map_arrows_map ON map_arrows(map_id);
CREATE INDEX idx_map_arrows_type ON map_arrows(arrow_type);
CREATE INDEX idx_map_arrows_deleted_at ON map_arrows(deleted_at);

CREATE TABLE map_notes (
  id CHAR(36) NOT NULL,
  map_id CHAR(36) NOT NULL,
  author_user_id CHAR(36) NULL,
  note_type ENUM('sessao', 'instrucao_ia', 'observacao_clinica', 'observacao_tecnica') NOT NULL DEFAULT 'sessao',
  content TEXT NOT NULL,
  visibility ENUM('private_professional', 'shared_with_patient', 'internal_system') NOT NULL DEFAULT 'private_professional',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL,
  deleted_at DATETIME NULL,
  deleted_by CHAR(36) NULL,
  PRIMARY KEY (id),
  CONSTRAINT map_notes_map_fk FOREIGN KEY (map_id) REFERENCES maps(id) ON DELETE RESTRICT,
  CONSTRAINT map_notes_author_fk FOREIGN KEY (author_user_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT map_notes_deleted_by_fk FOREIGN KEY (deleted_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_map_notes_map ON map_notes(map_id);
CREATE INDEX idx_map_notes_author ON map_notes(author_user_id);
CREATE INDEX idx_map_notes_type ON map_notes(note_type);
CREATE INDEX idx_map_notes_deleted_at ON map_notes(deleted_at);

CREATE TABLE map_files (
  id CHAR(36) NOT NULL,
  map_id CHAR(36) NOT NULL,
  uploaded_by CHAR(36) NULL,
  original_filename VARCHAR(255) NOT NULL,
  stored_filename VARCHAR(255) NOT NULL,
  file_path VARCHAR(500) NOT NULL,
  file_type VARCHAR(100) NOT NULL,
  file_size BIGINT NOT NULL,
  file_hash_sha256 CHAR(64) NULL,
  storage_status ENUM('stored', 'deleted', 'quarantined') NOT NULL DEFAULT 'stored',
  use_in_analysis BOOLEAN NOT NULL DEFAULT FALSE,
  openai_file_id VARCHAR(255) NULL,
  openai_vector_store_id VARCHAR(255) NULL,
  openai_uploaded_at DATETIME NULL,
  openai_deleted_at DATETIME NULL,
  openai_delete_status ENUM('not_uploaded', 'pending_delete', 'deleted', 'delete_failed') NOT NULL DEFAULT 'not_uploaded',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL,
  deleted_at DATETIME NULL,
  deleted_by CHAR(36) NULL,
  PRIMARY KEY (id),
  CONSTRAINT map_files_map_fk FOREIGN KEY (map_id) REFERENCES maps(id) ON DELETE RESTRICT,
  CONSTRAINT map_files_uploaded_by_fk FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT map_files_deleted_by_fk FOREIGN KEY (deleted_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_map_files_map ON map_files(map_id);
CREATE INDEX idx_map_files_uploaded_by ON map_files(uploaded_by);
CREATE INDEX idx_map_files_use_in_analysis ON map_files(use_in_analysis);
CREATE INDEX idx_map_files_openai_delete_status ON map_files(openai_delete_status);
CREATE INDEX idx_map_files_deleted_at ON map_files(deleted_at);

CREATE TABLE knowledge_files (
  id CHAR(36) NOT NULL,
  title VARCHAR(255) NOT NULL,
  description TEXT NULL,
  uploaded_by CHAR(36) NULL,
  original_filename VARCHAR(255) NOT NULL,
  stored_filename VARCHAR(255) NOT NULL,
  file_path VARCHAR(500) NOT NULL,
  file_type VARCHAR(100) NOT NULL,
  file_size BIGINT NOT NULL,
  file_hash_sha256 CHAR(64) NULL,
  category VARCHAR(100) NULL,
  active BOOLEAN NOT NULL DEFAULT TRUE,
  openai_file_id VARCHAR(255) NULL,
  openai_vector_store_id VARCHAR(255) NULL,
  openai_uploaded_at DATETIME NULL,
  openai_deleted_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL,
  deleted_at DATETIME NULL,
  deleted_by CHAR(36) NULL,
  PRIMARY KEY (id),
  CONSTRAINT knowledge_files_uploaded_by_fk FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT knowledge_files_deleted_by_fk FOREIGN KEY (deleted_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_knowledge_files_active ON knowledge_files(active);
CREATE INDEX idx_knowledge_files_category ON knowledge_files(category);
CREATE INDEX idx_knowledge_files_uploaded_by ON knowledge_files(uploaded_by);
CREATE INDEX idx_knowledge_files_deleted_at ON knowledge_files(deleted_at);

CREATE TABLE ai_prompt_templates (
  id CHAR(36) NOT NULL,
  name VARCHAR(100) NOT NULL,
  version INT NOT NULL,
  description TEXT NULL,
  system_prompt LONGTEXT NOT NULL,
  user_prompt_template LONGTEXT NOT NULL,
  clinical_review_status ENUM('pending', 'approved', 'rejected', 'deprecated') NOT NULL DEFAULT 'pending',
  active BOOLEAN NOT NULL DEFAULT FALSE,
  created_by CHAR(36) NULL,
  approved_by CHAR(36) NULL,
  approved_at DATETIME NULL,
  deprecated_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL,
  deleted_at DATETIME NULL,
  deleted_by CHAR(36) NULL,
  PRIMARY KEY (id),
  UNIQUE KEY ai_prompt_templates_name_version_unique (name, version),
  CONSTRAINT ai_prompt_templates_created_by_fk FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT ai_prompt_templates_approved_by_fk FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT ai_prompt_templates_deleted_by_fk FOREIGN KEY (deleted_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_prompt_templates_name_version ON ai_prompt_templates(name, version);
CREATE INDEX idx_prompt_templates_active ON ai_prompt_templates(active);
CREATE INDEX idx_prompt_templates_review_status ON ai_prompt_templates(clinical_review_status);

CREATE TABLE map_analyses (
  id CHAR(36) NOT NULL,
  map_id CHAR(36) NOT NULL,
  requested_by CHAR(36) NULL,
  analysis_version INT NOT NULL DEFAULT 1,
  analysis_text LONGTEXT NOT NULL,
  analysis_summary TEXT NULL,
  model_provider VARCHAR(50) NOT NULL DEFAULT 'openai',
  model_used VARCHAR(100) NOT NULL,
  prompt_template_id CHAR(36) NULL,
  prompt_template_version INT NULL,
  prompt_used LONGTEXT NULL,
  selected_files_json JSON NULL,
  guardrails_result_json JSON NULL,
  status ENUM('generated', 'failed', 'discarded') NOT NULL DEFAULT 'generated',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  deleted_by CHAR(36) NULL,
  PRIMARY KEY (id),
  UNIQUE KEY map_analyses_map_version_unique (map_id, analysis_version),
  CONSTRAINT map_analyses_map_fk FOREIGN KEY (map_id) REFERENCES maps(id) ON DELETE RESTRICT,
  CONSTRAINT map_analyses_requested_by_fk FOREIGN KEY (requested_by) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT map_analyses_prompt_template_fk FOREIGN KEY (prompt_template_id) REFERENCES ai_prompt_templates(id) ON DELETE SET NULL,
  CONSTRAINT map_analyses_deleted_by_fk FOREIGN KEY (deleted_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_map_analyses_map ON map_analyses(map_id);
CREATE INDEX idx_map_analyses_requested_by ON map_analyses(requested_by);
CREATE INDEX idx_map_analyses_created_at ON map_analyses(created_at);
CREATE INDEX idx_map_analyses_status ON map_analyses(status);

ALTER TABLE ai_processing_logs
  ADD CONSTRAINT ai_processing_logs_file_fk FOREIGN KEY (map_file_id) REFERENCES map_files(id) ON DELETE SET NULL;

CREATE INDEX idx_ai_processing_map ON ai_processing_logs(map_id);
CREATE INDEX idx_ai_processing_file ON ai_processing_logs(map_file_id);
CREATE INDEX idx_ai_processing_provider_file ON ai_processing_logs(provider_file_id);
CREATE INDEX idx_audit_logs_severity ON audit_logs(severity);
CREATE INDEX idx_audit_logs_created_at ON audit_logs(created_at);

DROP TRIGGER IF EXISTS prevent_audit_update;
DROP TRIGGER IF EXISTS prevent_audit_delete;

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
