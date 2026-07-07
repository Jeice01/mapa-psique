# Deploy na Hostinger

## Frontend

Em producao, o frontend deve chamar a API de forma relativa no mesmo dominio. Mantenha `VITE_API_BASE_URL=/api` ou omita a variavel ao gerar o build.

Gere o build:

```bash
cd frontend
npm install
npm run build
```

Publique o conteudo de `frontend/dist` no document root do subdominio `mapapsique.orbisconect.com` ou na pasta configurada para arquivos estaticos.

## Backend

Publique a pasta `backend` no servidor PHP tradicional da Hostinger e aponte o document root da API para `backend/public`, quando o painel permitir. Se o painel exigir uma pasta publica unica, mantenha os arquivos sensiveis fora do document root e exponha apenas o conteudo de `backend/public`.

Crie `backend/.env` diretamente no servidor com os dados reais do MySQL/MariaDB da Hostinger. Nunca envie esse arquivo ao GitHub.

Variaveis esperadas:

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

Tambem sao aceitos os aliases comuns na Hostinger: `DB_NAME`, `DB_USER` e `DB_PASS`.

## Banco

Importe as migrations nesta ordem no MySQL/MariaDB usando o painel da Hostinger, phpMyAdmin ou cliente SQL:

1. `backend/migrations/001_initial_schema.sql`
2. `backend/migrations/002_complete_schema.sql`
3. `backend/migrations/003_seed_initial_data.sql`
4. `backend/migrations/004_password_resets.sql`

As migrations com triggers usam `DELIMITER`; rode pelo phpMyAdmin ou por cliente MySQL/MariaDB que suporte esse comando.

## Permissoes

Garanta escrita para:

- `backend/storage/logs`
- `backend/storage/temp`
- `backend/storage/uploads`

Nunca exponha `backend/storage` como pasta publica.

## Branches

- `main`: codigo-fonte revisado.
- `deploy`: build pronto para publicacao na Hostinger, criada apenas quando o fluxo de deploy estiver definido.
