INSERT INTO consent_terms (
  id,
  version,
  title,
  content,
  active,
  created_at,
  updated_at
) VALUES (
  '00000000-0000-4000-8000-000000000001',
  '1.0',
  'Termo de Consentimento para Uso do Gerador do Mapa da Psiquê',
  'Este termo informa que dados fornecidos podem ser usados para geração do mapa e podem ser processados por recursos de inteligência artificial. A ferramenta tem finalidade de autoconhecimento e apoio reflexivo, não substitui acompanhamento psicológico, médico ou terapêutico. Dados sensíveis devem ser tratados com cuidado. Esta versão é inicial e sujeita à validação jurídica definitiva.',
  TRUE,
  CURRENT_TIMESTAMP,
  NULL
)
ON DUPLICATE KEY UPDATE
  title = VALUES(title),
  content = VALUES(content),
  active = VALUES(active),
  updated_at = CURRENT_TIMESTAMP;

INSERT INTO ai_prompt_templates (
  id,
  name,
  version,
  description,
  system_prompt,
  user_prompt_template,
  clinical_review_status,
  active,
  created_at,
  updated_at
) VALUES (
  '00000000-0000-4000-8000-000000000002',
  'mapa_psique_clinical_analysis',
  1,
  'Template inicial pendente de revisão clínica.',
  'Template inicial pendente de revisão clínica. Não emitir diagnóstico. Não substituir acompanhamento psicológico.',
  'Template inicial pendente de revisão clínica. Gerar análise apenas após revisão e aprovação do prompt definitivo.',
  'pending',
  FALSE,
  CURRENT_TIMESTAMP,
  NULL
)
ON DUPLICATE KEY UPDATE
  description = VALUES(description),
  system_prompt = VALUES(system_prompt),
  user_prompt_template = VALUES(user_prompt_template),
  clinical_review_status = VALUES(clinical_review_status),
  active = VALUES(active),
  updated_at = CURRENT_TIMESTAMP;
