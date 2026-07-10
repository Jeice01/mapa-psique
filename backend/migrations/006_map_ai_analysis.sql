-- Migration 006: AI Analysis for Mapa da Psiquê
-- Stores the generated psychoanalytic analysis, patient report and infographic image.

CREATE TABLE IF NOT EXISTS map_ai_analyses (
    id              CHAR(36)     NOT NULL,
    map_id          CHAR(36)     NOT NULL,

    -- Text outputs
    professional_analysis  LONGTEXT NULL COMMENT 'JSON with structured psychoanalytic sections (Freud + Jung)',
    patient_report         LONGTEXT NULL COMMENT 'Simplified patient-facing report in plain language',

    -- Image output
    image_path      VARCHAR(500) NULL COMMENT 'Filename of the AI-generated infographic stored in storage/uploads/ai/',
    image_prompt    LONGTEXT     NULL COMMENT 'DALL-E prompt used to generate the infographic',

    -- Metadata
    model_text      VARCHAR(100) NULL COMMENT 'AI model used for text (e.g. gpt-4o, claude-opus-4-8)',
    model_image     VARCHAR(100) NULL COMMENT 'AI model used for image (e.g. dall-e-3)',

    -- Status tracking
    status          ENUM('pending','processing','completed','failed') NOT NULL DEFAULT 'pending',
    error_message   TEXT         NULL,
    generated_at    TIMESTAMP    NULL,

    created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE  KEY uq_map_ai_analyses_map_id (map_id),
    CONSTRAINT fk_map_ai_analyses_map
        FOREIGN KEY (map_id) REFERENCES maps(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
