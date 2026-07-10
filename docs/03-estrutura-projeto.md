# Estrutura do Projeto — Mapa da Psiquê

> Status: primeira versão consolidada.

## 1. Objetivo

Este documento descreve os diretórios, arquivos principais e responsabilidades do repositório.

## 2. Estrutura da branch `main`

```text
.github/
backend/
docs/
frontend/
.gitignore
README.md
```

### `.github/workflows/`

Contém automações de repositório. O arquivo identificado é:

```text
.github/workflows/deploy-hostinger.yml
```

**[PENDENTE DE VALIDAÇÃO]** Confirmar se o workflow ainda é parte do processo operacional atual ou se o deploy efetivo permanece manual.

## 3. Backend

```text
backend/
├── migrations/
├── public/
├── src/
├── storage/
├── .env.example
└── composer.json
```

### `backend/public/`

Ponto de entrada HTTP do backend.

```text
backend/public/index.php
backend/public/.htaccess
```

### `backend/src/`

```text
Config/       configuração
Controllers/  endpoints técnicos gerais
Database/     conexão e repositories
Http/         router e respostas
Middleware/   CORS, rate limit e headers
Modules/      regras funcionais por domínio
Security/     sessão, CSRF, senhas e sanitização
Support/      ambiente, logs, e-mail e UUID
bootstrap.php autoload da aplicação
```

### `backend/src/Modules/`

```text
Auth/         autenticação e autorização
Consents/     termos e aceite
Dashboard/    indicadores
Maps/         mapas, Canvas, versões e PDF
Patients/     pacientes
Shared/       guardas, request e auditoria
```

### `backend/src/Database/Repositories/`

Repositories confirmados:

```text
UserRepository.php
PatientRepository.php
MapRepository.php
ConsentRepository.php
AuditLogRepository.php
PasswordResetTokenRepository.php
AiProcessingLogRepository.php
```

### `backend/migrations/`

```text
001_initial_schema.sql
002_complete_schema.sql
003_seed_initial_data.sql
004_password_resets.sql
005_map_canvas_versions.sql
```

As migrations devem ser executadas na ordem numérica.

### `backend/storage/`

```text
logs/
temp/
uploads/
```

Diretórios de runtime não devem ser tratados como código-fonte.

## 4. Frontend

```text
frontend/
├── src/
├── .env.example
├── index.html
├── package.json
├── package-lock.json
├── vite.config.ts
├── tsconfig.json
├── eslint.config.js
├── tailwind.config.ts
└── postcss.config.js
```

### `frontend/src/`

```text
app/         componente raiz
components/  componentes compartilhados
modules/     módulos funcionais
shared/      API, tipos, constantes e utilitários
styles/      estilos
main.tsx     ponto de entrada
styles.css   estilos globais
```

### Módulos frontend

```text
auth/
consents/
dashboard/
maps/
patients/
protected/
```

Diretórios previstos, ainda sem funcionalidade confirmada completa:

```text
ai-analysis/
audit/
files/
```

## 5. Documentação

Documentos existentes antes desta consolidação:

```text
architecture.md
auth-flow.md
checkpoints.md
dashboard-flow.md
database-schema.md
deployment-hostinger.md
project-context.md
security-baseline.md
```

Nova estrutura documental:

```text
01-visao-geral.md
02-arquitetura.md
03-estrutura-projeto.md
04-requisitos.md
05-modulos.md
06-regras-negocio.md
07-api.md
08-banco-de-dados.md
09-seguranca-lgpd.md
10-desenvolvimento-local.md
11-deploy-hostinger.md
12-operacao-manutencao.md
13-manual-usuario.md
14-troubleshooting.md
15-roadmap.md
```

## 6. Estrutura da branch `deploy`

```text
index.html
assets/
api/
.htaccess
```

A branch `deploy` contém artefatos preparados para produção, não o projeto de desenvolvimento completo.

Estrutura backend publicada:

```text
api/index.php
api/.htaccess
api/_app/public/index.php
api/_app/src/
api/_app/storage/
api/_app/.env
```

## 7. Responsabilidades por camada

| Camada | Responsabilidade |
|---|---|
| Frontend | Interface, formulários, navegação e consumo da API |
| Controller | Entrada HTTP, autenticação, CSRF e resposta |
| Service | Regras de negócio e transações |
| Repository | SQL e persistência |
| Middleware | Controles transversais globais |
| Security | Sessão, senha, CSRF e sanitização |
| Support | Infraestrutura auxiliar |
| Migration | Evolução do schema |

## 8. Padrões identificados

- arquitetura em camadas;
- modularização por domínio;
- repositories com PDO;
- prepared statements;
- soft delete;
- auditoria transversal;
- isolamento por `owner_user_id`;
- respostas JSON;
- frontend SPA;
- build estático;
- separação entre código-fonte e artefato de produção.

## 9. Convenções recomendadas

- controllers sem regra de negócio extensa;
- services responsáveis por validações funcionais;
- repositories sem decisões de interface;
- migrations numeradas e imutáveis após uso em produção;
- arquivos de runtime fora do Git;
- commits separados por finalidade;
- documentação atualizada junto com mudanças funcionais.

## 10. Pendências

- confirmar a função atual do workflow de deploy;
- revisar documentos antigos e eliminar divergências;
- confirmar diretórios realmente utilizados em produção;
- criar testes automatizados;
- documentar padrão oficial de nomes e mensagens de commit.
