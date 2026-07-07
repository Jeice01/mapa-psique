# Mapa da Psiquê

Aplicacao web para profissionais conduzirem a tecnica do Mapa da Psiquê com pacientes, mapas, observacoes, materiais, analise futura por IA e geracao futura de relatorio.

## Stack oficial

- Frontend: React + Vite + TypeScript
- Estilos: Tailwind CSS
- Backend: PHP 8.x puro
- Banco: MySQL/MariaDB da Hostinger
- Acesso ao banco: PDO
- Hospedagem alvo: Hostinger PHP/HTML tradicional
- Subdominio alvo: `mapapsique.orbisconect.com`

Nao usa Next.js, Drizzle, PostgreSQL, Docker, Docker Compose, JWT ou Node.js no servidor Hostinger.

## Status atual do projeto

Etapa 1 — Fundação: aprovada.
Etapa 2 — Banco completo: aprovada para avanço, pendente validação real em MySQL/MariaDB.
Etapa 3 — Autenticação, sessão segura e RBAC: aprovada para avanço, pendente validação real com banco.
Etapa 4 — Dashboard inicial, pacientes e mapas: em revisão técnica.

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

No MySQL/MariaDB da Hostinger via phpMyAdmin, painel da Hostinger ou cliente SQL.

## Observação sobre migrations com triggers

Algumas migrations usam triggers e comandos `DELIMITER`. Execute preferencialmente via phpMyAdmin, painel Hostinger ou cliente MySQL/MariaDB. Caso futuramente seja criado um runner PHP/PDO, os blocos de trigger devem ser adaptados.

## Ainda nao implementado

- Canvas interativo
- Itens do mapa
- Setas
- Upload real
- Integração OpenAI
- PDF
- Relatório clínico
- Gestão de materiais-base

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

Resumo do fluxo planejado:

1. Manter o codigo-fonte na branch `main`.
2. Gerar o build do frontend com `npm run build`.
3. Criar futuramente a branch `deploy` apenas com os artefatos prontos para publicacao.
4. Publicar os arquivos estaticos de `frontend/dist` no subdominio.
5. Publicar o backend PHP mantendo somente `backend/public` exposto como pasta publica.
6. Criar `backend/.env` diretamente na Hostinger com credenciais reais.
7. Importar as migrations `001`, `002` e `003` no banco MySQL/MariaDB.

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
