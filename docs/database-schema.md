# Database Schema

## Visao Geral

O banco usa MySQL/MariaDB com `InnoDB`, `utf8mb4` e acesso via PDO. As migrations devem ser executadas nesta ordem:

1. `backend/migrations/001_initial_schema.sql`
2. `backend/migrations/002_complete_schema.sql`
3. `backend/migrations/003_seed_initial_data.sql`

As migrations que criam triggers usam `DELIMITER`, portanto devem ser executadas via phpMyAdmin, cliente MySQL/MariaDB ou adaptadas caso futuramente sejam executadas por um runner PHP/PDO.

As migrations devem ser validadas em ambiente MySQL/MariaDB real antes da execução em produção.

## Entidades Principais

- `users`: profissionais, administradores, pacientes e auditores.
- `patients`: pacientes/clientes vinculados a um profissional.
- `maps`: registro central do mapa da psique.
- `map_items`: pessoas, lugares e situacoes posicionadas no mapa.
- `map_arrows`: setas de energia psiquica, com unicidade por mapa e tipo.
- `map_notes`: observacoes sensiveis feitas antes da IA.
- `map_files`: anexos privados vinculados ao mapa.
- `knowledge_files`: materiais-base do metodo.
- `map_analyses`: historico versionado de analises geradas.
- `ai_prompt_templates`: templates de prompt versionados e revisaveis.
- `consent_terms` e `user_consents`: base inicial de consentimento LGPD.
- `audit_logs`: trilha auditavel append-only.
- `ai_processing_logs`: rastreabilidade operacional de arquivos, vector stores e analises.

As setas de energia sao unicas por mapa e tipo. Em caso de alteracao, o registro deve ser atualizado/restaurado, nao recriado, para evitar conflito com soft delete.

Em `map_items`, o campo de sinal afetivo usa o nome `item_signal` para evitar conflito com a palavra-chave SQL `SIGNAL` em MySQL/MariaDB.

## Soft Delete

Tabelas operacionais usam `deleted_at` e `deleted_by`. Dados clinicos nao usam `ON DELETE CASCADE`; exclusao logica preserva historico, auditoria e possibilidade de revisao.

## Auditoria Append-Only

`audit_logs` possui triggers `prevent_audit_update` e `prevent_audit_delete`. Registros devem ser apenas inseridos. Nao armazenar conteudo clinico bruto em `metadata_json`.

## Ciclo OpenAI

`map_files` e `knowledge_files` armazenam IDs de arquivo e vector store do provedor. `ai_processing_logs` registra a acao executada, status, tentativas e pendencias de exclusao ou compensacao. A integracao real com OpenAI ainda nao foi implementada.

## Privacidade

Nao logar:

- conteudo clinico bruto;
- `map_notes.content`;
- `map_analyses.analysis_text`;
- `map_analyses.prompt_used`;
- nomes de arquivo quando puderem conter dados sensiveis.

Quando necessario, logar apenas IDs e metadados tecnicos.

## LGPD

`user_consents` permite aceite e revogacao inicial por `status` e `revoked_at`. Os textos e fluxos finais de consentimento precisam de validacao juridica antes de uso em producao.
