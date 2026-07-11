# Mapa da Psiquê

Aplicação web para profissionais conduzirem a técnica do Mapa da Psiquê com pacientes, canvas versionado, uploads, exportação PDF e análise assistida por IA.

## Stack oficial

- Frontend: React + Vite + TypeScript
- Estilos: Tailwind CSS
- Backend: PHP 8.x puro
- Banco: MySQL/MariaDB da Hostinger
- Acesso ao banco: PDO
- Hospedagem alvo: Hostinger PHP/HTML tradicional
- Subdominio alvo: `mapapsique.orbisconect.com`

Nao usa Next.js, Drizzle, PostgreSQL, Docker, Docker Compose, JWT ou Node.js no servidor Hostinger.

## Contexto para novos chats

Antes de pedir alteracoes em um novo chat, leia ou peça para o Codex ler:

- `docs/project-context.md`
- `docs/checkpoints.md`
- `docs/architecture.md`
- `docs/security-baseline.md`
- `docs/deployment-hostinger.md`

O arquivo `docs/project-context.md` resume a arquitetura oficial, branches, deploy automatico, Hostinger e cuidados de seguranca. O arquivo `docs/checkpoints.md` registra marcos tecnicos validados.

## Status atual do projeto

Validado em produção: frontend, API PHP, MySQL/MariaDB, autenticação por sessão, CSRF, pacientes, mapas, canvas, histórico e restauração de versões, exportação PDF, upload de imagens e deploy automático.

Implementado, com validação clínica e de conformidade ainda necessária: análise textual e geração visual por IA, usando OpenAI como provedor primário e Anthropic como fallback de texto.

Pendências prioritárias: testes manuais dos fluxos críticos, testes de integração com banco, política LGPD, retenção e eliminação, proteção adicional de uploads e governança clínica da IA.

## Estrutura

```text
frontend/
  src/app/
  src/components/
  src/modules/
  src/shared/
  src/styles/

backend/
  public/
  src/
  migrations/
  storage/

docs/
```

## Ambiente

Copie os exemplos:

```bash
copy frontend\.env.example frontend\.env
copy backend\.env.example backend\.env
```

Configure `backend/.env` com as credenciais MySQL/MariaDB locais ou da Hostinger. Nunca commite `.env` real.

O frontend usa `/api` como URL base padrao. Em producao, mantenha `VITE_API_BASE_URL=/api` ou omita a variavel para que o build chame a API no mesmo dominio.

Exemplo de producao:

```text
APP_ENV=production
APP_URL=https://mapapsique.orbisconect.com
APP_ALLOWED_ORIGINS=https://mapapsique.orbisconect.com
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_database_user
DB_PASSWORD=your_database_password
DB_CHARSET=utf8mb4
SESSION_COOKIE_NAME=mapa_psique_session
SESSION_LIFETIME_MINUTES=120
CSRF_ENABLED=true
MAIL_FROM=no-reply@mapapsique.orbisconect.com
MAIL_FROM_NAME=Mapa da Psique
```

O backend aceita tambem os aliases comuns na Hostinger: `DB_NAME`, `DB_USER` e `DB_PASS`.

## Rodar frontend

```bash
cd frontend
npm install
npm run dev
```

Frontend: `http://localhost:5173`

Para desenvolvimento local contra outra origem de API, crie `frontend/.env` local e nao versionado com `VITE_API_BASE_URL` apontando para a URL desejada. Em producao, use `/api`.

```text
VITE_API_BASE_URL=/api
```

Nao commite `frontend/.env`.

## Rodar backend

```bash
cd backend
php -S localhost:8080 -t public
```

Backend: `http://localhost:8080`

## Testes manuais

Health check:

```text
GET http://localhost:8080/api/health
```

Database check:

```text
GET http://localhost:8080/api/db-check
```

Se nao houver banco local configurado, `/api/db-check` deve retornar erro controlado sem stack trace e sem credenciais.

## Autenticação

Endpoints iniciais:

- `GET /api/csrf-token`
- `POST /api/auth/register`
- `POST /api/auth/login`
- `POST /api/auth/forgot-password`
- `POST /api/auth/reset-password`
- `POST /api/auth/logout`
- `GET /api/auth/me`
- `GET /api/consents/active`
- `POST /api/consents/accept`
- `GET /api/dashboard/summary`
- `GET /api/patients`
- `POST /api/patients`
- `GET /api/patients/{id}`
- `PUT /api/patients/{id}`
- `DELETE /api/patients/{id}`
- `GET /api/maps`
- `POST /api/maps`
- `GET /api/maps/{id}`
- `PUT /api/maps/{id}`
- `DELETE /api/maps/{id}`

A sessao usa cookie HttpOnly, `SameSite=Lax` e `Secure` em producao. O frontend envia requisicoes com `credentials: 'include'`. Endpoints mutaveis usam CSRF via header `X-CSRF-Token`.

Roles previstas:

- `administrador`
- `profissional`
- `paciente`
- `auditor`

Há um dashboard inicial. O dashboard analítico completo ainda não foi implementado.

## Migration inicial

Importe as migrations nesta ordem:

1. `backend/migrations/001_initial_schema.sql`
2. `backend/migrations/002_complete_schema.sql`
3. `backend/migrations/003_seed_initial_data.sql`
4. `backend/migrations/004_password_resets.sql`
5. `backend/migrations/005_map_canvas_versions.sql`
6. `backend/migrations/006_map_ai_analysis.sql`
7. `backend/migrations/007_maps_image_path.sql`

No MySQL/MariaDB da Hostinger via phpMyAdmin, painel da Hostinger ou cliente SQL.

## Observação sobre migrations com triggers

Algumas migrations usam triggers e comandos `DELIMITER`. Execute preferencialmente via phpMyAdmin, painel Hostinger ou cliente MySQL/MariaDB. Caso futuramente seja criado um runner PHP/PDO, os blocos de trigger devem ser adaptados.

## Funcionalidades e limites atuais

Implementado:

- gestão de pacientes e mapas com isolamento por profissional;
- canvas clínico com histórico, prévia e restauração segura;
- upload autenticado de imagens JPG, PNG, WebP e GIF, limitado a 10 MB;
- exportação PDF do mapa atual e de versões históricas;
- análise assistida por OpenAI, com fallback de texto para Anthropic;
- relatório simplificado e infográfico gerados por IA;
- auditoria, consentimento inicial, rate limit, CSRF e RBAC.

Ainda pendente ou incompleto:

- gestão de materiais-base;
- testes automatizados de integração com MySQL/MariaDB;
- fluxo completo de direitos do titular, retenção e eliminação;
- validação jurídica do termo de consentimento;
- revisão clínica versionada dos prompts e aprovação humana formal;
- endurecimento do ciclo de vida dos uploads.

## Cuidados de versionamento

Nao versionar:

- `.git/`
- `frontend/node_modules/`
- `frontend/dist/`
- `frontend/*.tsbuildinfo`
- `.env`
- `backend/.env`
- `backend/vendor/`
- arquivos de runtime em `backend/storage`
- logs, temporarios, dumps, backups e arquivos com credenciais

Antes do commit inicial, confira:

```bash
git status --short --ignored
```

`frontend/node_modules/`, `frontend/dist/` e arquivos `.env` reais devem aparecer apenas como ignorados ou nao aparecer.

## Deploy Hostinger

Resumo do fluxo atual:

1. Manter o codigo-fonte na branch `main`.
2. Gerar o build do frontend com `npm run build`.
3. Atualizar a branch `deploy` com os artefatos prontos para publicacao.
4. Fazer push da branch `deploy`.
5. O GitHub Actions conecta na Hostinger por SSH e executa `git pull origin deploy`.
6. Manter `backend/.env` diretamente na Hostinger com credenciais reais.
7. Importar novas migrations manualmente no MySQL/MariaDB quando forem criadas.

Workflow:

```text
.github/workflows/deploy-hostinger.yml
```

O CI de fonte fica em `.github/workflows/ci.yml` e deve validar frontend e backend em pushes e pull requests para `main`. O deploy de produção continua separado e só é acionado por push em `deploy`.

Deploy manual de contingencia:

```bash
cd /home/u754689460/domains/mapapsique.orbisconect.com/public_html
git pull origin deploy
```

Mais detalhes em `docs/deployment-hostinger.md`.

## Como gerar ZIP limpo para revisão

Use PowerShell a partir da raiz do projeto:

```powershell
Compress-Archive -Path .\frontend,.\backend,.\docs,.\README.md,.\.gitignore -DestinationPath ..\mapa-psique-fundacao.zip
```

Antes de gerar o ZIP, confirme que nao estao incluidos:

- `.git/`
- `node_modules/`
- `dist/`
- `.env`
- logs
- temporarios
- `*.tsbuildinfo`
