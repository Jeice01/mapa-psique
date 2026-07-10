# Banco de Dados — Mapa da Psiquê

> Banco: MySQL/MariaDB, InnoDB, `utf8mb4_unicode_ci`.

## 1. Migrations

```text
001_initial_schema.sql
002_complete_schema.sql
003_seed_initial_data.sql
004_password_resets.sql
005_map_canvas_versions.sql
```

A ordem é obrigatória. A migration 002 depende da 001.

## 2. Entidades principais

### users

Campos relevantes:

```text
id
name
email
password_hash
role
status
last_login_at
created_at
updated_at
deleted_at
deleted_by
```

Perfis previstos:

```text
administrador
profissional
paciente
auditor
```

Status:

```text
active
inactive
blocked
pending
```

### patients

```text
id
owner_user_id
name
internal_code
age
notes
status
created_at
updated_at
deleted_at
deleted_by
```

Status: `active`, `inactive`, `archived`.

### maps

```text
id
owner_user_id
patient_id
title
reason
status
canvas_json
canvas_image_path
canvas_version
coordinate_system_version
revealed_quadrants
created_at
updated_at
deleted_at
deleted_by
```

Status: `draft`, `ready_for_analysis`, `analyzed`, `archived`.

### map_canvas_versions

```text
id
map_id
user_id
version_number
canvas_data
summary
created_at
```

Restrição única:

```text
(map_id, version_number)
```

### audit_logs

```text
id
actor_user_id
request_id
session_id
severity
action
entity_type
entity_id
metadata_json
ip_address
user_agent
created_at
```

Triggers impedem `UPDATE` e `DELETE`.

### consent_terms

```text
id
version
title
content
active
created_at
updated_at
```

### user_consents

```text
id
user_id
consent_term_id
status
accepted_at
revoked_at
ip_address
user_agent
metadata
created_at
```

### password_reset_tokens

```text
id
user_id
token_hash
expires_at
used_at
created_at
request_ip
user_agent
```

## 3. Entidades previstas para evolução

```text
map_items
map_arrows
map_notes
map_files
knowledge_files
ai_prompt_templates
map_analyses
ai_processing_logs
```

Essas tabelas não devem ser tratadas como funcionalidades ativas sem validação adicional.

## 4. Relacionamentos principais

```text
users 1:N patients
users 1:N maps
patients 1:N maps
maps 1:N map_canvas_versions
users 1:N user_consents
consent_terms 1:N user_consents
users 1:N audit_logs
```

Regras relevantes:

- `maps.patient_id` usa `ON DELETE SET NULL`;
- `maps.owner_user_id` usa `ON DELETE RESTRICT`;
- versões usam `ON DELETE RESTRICT` para mapas;
- tokens de senha usam `ON DELETE CASCADE` para usuários;
- autoria de versão usa `ON DELETE SET NULL`.

## 5. Soft delete

Aplicado em:

```text
users
patients
maps
map_items
map_arrows
map_notes
map_files
knowledge_files
ai_prompt_templates
map_analyses
```

O padrão é:

```text
deleted_at
deleted_by
```

## 6. Índices

Índices confirmados para:

- e-mail;
- status de usuário;
- proprietário de paciente e mapa;
- código interno;
- status;
- datas de criação;
- soft delete;
- entidade e ator da auditoria;
- versões do Canvas;
- tokens e expiração.

**[RECOMENDAÇÃO]** Revisar índices duplicados criados entre as migrations 001 e 002.

## 7. Integridade

Controles:

- chaves estrangeiras;
- enums;
- índices únicos;
- transações no código;
- prepared statements;
- triggers append-only;
- `UNIQUE(map_id, version_number)`.

## 8. Dados sensíveis

Podem existir:

- identificação do usuário e paciente;
- observações;
- motivo do mapa;
- conteúdo psicológico do Canvas;
- consentimentos;
- IP e user agent;
- trilha de auditoria;
- futuramente arquivos e análises de IA.

## 9. Seeds

A migration 003 cria:

- termo de consentimento versão 1.0;
- template de IA pendente e inativo.

**[RECOMENDAÇÃO]** Não alterar retroativamente o conteúdo de termo já aceito. Criar nova versão.

## 10. Limitações identificadas

- idade é armazenada como número, não data de nascimento;
- código interno não é único por proprietário;
- `canvas_data` histórico é LONGTEXT, não JSON;
- migrations principais não são idempotentes;
- não há rollback versionado;
- não há política de retenção documentada;
- não há rotina confirmada de limpeza de tokens.

## 11. Comandos de inventário recomendados

```sql
SHOW TABLES;
SHOW CREATE TABLE users;
SHOW CREATE TABLE patients;
SHOW CREATE TABLE maps;
SHOW CREATE TABLE map_canvas_versions;
SHOW INDEX FROM patients;
SHOW INDEX FROM maps;
```

Nunca publicar credenciais ou dados reais na documentação.

## 12. Pendências

- comparar migrations com schema real de produção;
- documentar tamanho do banco;
- documentar backup;
- confirmar versão do MySQL/MariaDB;
- confirmar criptografia em repouso oferecida pela hospedagem;
- revisar usuário e privilégios da aplicação;
- definir retenção e anonimização.
