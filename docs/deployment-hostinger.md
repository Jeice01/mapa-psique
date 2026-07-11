# Deploy na Hostinger

## Modelo atual de publicação

- `main`: código-fonte e documentação;
- `deploy`: pacote runtime servido pela Hostinger;
- `.github/workflows/ci.yml`: validações de fonte em pushes e pull requests para `main`;
- `.github/workflows/deploy-hostinger.yml`: conexão SSH e `git pull origin deploy` na produção.

O runtime publicado mantém `index.html`, `assets/`, `.htaccess` e `api/`. O backend privado fica em `api/_app` e deve permanecer bloqueado para acesso HTTP direto.

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
OPENAI_API_KEY=<chave somente no servidor>
OPENAI_TEXT_MODEL=gpt-4o
OPENAI_IMAGE_MODEL=dall-e-3
ANTHROPIC_API_KEY=<opcional; chave somente no servidor>
ANTHROPIC_TEXT_MODEL=claude-opus-4-8
```

Tambem sao aceitos os aliases comuns na Hostinger: `DB_NAME`, `DB_USER` e `DB_PASS`.

## Banco

Importe as migrations nesta ordem no MySQL/MariaDB usando o painel da Hostinger, phpMyAdmin ou cliente SQL:

1. `backend/migrations/001_initial_schema.sql`
2. `backend/migrations/002_complete_schema.sql`
3. `backend/migrations/003_seed_initial_data.sql`
4. `backend/migrations/004_password_resets.sql`
5. `backend/migrations/005_map_canvas_versions.sql`
6. `backend/migrations/006_map_ai_analysis.sql`
7. `backend/migrations/007_maps_image_path.sql`

As migrations com triggers usam `DELIMITER`; rode pelo phpMyAdmin ou por cliente MySQL/MariaDB que suporte esse comando.

## Permissoes

Garanta escrita para:

- `backend/storage/logs`
- `backend/storage/temp`
- `backend/storage/uploads`

Nunca exponha `backend/storage` como pasta publica.

## Branches

- `main`: código-fonte revisado.
- `deploy`: build e backend runtime prontos para publicação na Hostinger.

Não fazer merge cego entre as branches. A `deploy` possui estrutura diferente e deve ser montada a partir de um commit conhecido da `main`, mantendo o workflow de deploy dentro da própria branch.

## Validação e contingência

Antes de publicar:

1. confirmar árvore de trabalho limpa;
2. executar ESLint, build e sintaxe PHP;
3. registrar o commit de origem da `main`;
4. revisar o diff do pacote runtime;
5. fazer push em `deploy`;
6. acompanhar o GitHub Actions até a conclusão;
7. executar smoke tests de autenticação, pacientes, mapas e arquivos.

Contingência no servidor:

```bash
cd /home/u754689460/domains/mapapsique.orbisconect.com/public_html
git pull origin deploy
```
