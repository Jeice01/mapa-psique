CREATE TABLE IF NOT EXISTS map_canvas_versions (
  id CHAR(36) NOT NULL,
  map_id CHAR(36) NOT NULL,
  user_id CHAR(36) NULL,
  version_number INT NOT NULL,
  canvas_data LONGTEXT NOT NULL,
  summary VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY map_canvas_versions_map_version_unique (map_id, version_number),
  KEY idx_map_canvas_versions_map (map_id),
  KEY idx_map_canvas_versions_created_at (created_at),
  CONSTRAINT map_canvas_versions_map_fk FOREIGN KEY (map_id) REFERENCES maps(id) ON DELETE RESTRICT,
  CONSTRAINT map_canvas_versions_user_fk FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
