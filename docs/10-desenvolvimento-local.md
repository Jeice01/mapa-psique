# Desenvolvimento Local — Mapa da Psiquê

## 1. Pré-requisitos

- Git;
- Node.js e npm compatíveis com o frontend;
- PHP 8.1 ou superior;
- MySQL/MariaDB;
- PowerShell no Windows;
- acesso ao repositório.

## 2. Clonar e acessar

```powershell
git clone https://github.com/Jeice01/mapa-psique.git
cd mapa-psique
```

## 3. Frontend

```powershell
cd frontend
npm ci
npm run lint
npm run build
```

Scripts confirmados:

```text
npm run dev
npm run lint
npm run build
```

O build executa validação TypeScript e Vite.

Saída:

```text
frontend/dist/
```

Variável principal:

```text
VITE_API_BASE_URL=/api
```

## 4. Backend

Copie o exemplo de ambiente:

```powershell
Copy-Item backend\.env.example backend\.env
```

Configure sem versionar credenciais reais.

Variáveis identificadas:

```text
APP_ENV
APP_URL
APP_ALLOWED_ORIGINS
DB_HOST
DB_PORT
DB_DATABASE ou DB_NAME
DB_USERNAME ou DB_USER
DB_PASSWORD ou DB_PASS
DB_CHARSET
SESSION_COOKIE_NAME
SESSION_LIFETIME_MINUTES
CSRF_ENABLED
MAIL_FROM
MAIL_FROM_NAME
```

## 5. Banco

Execute as migrations na ordem:

```text
001_initial_schema.sql
002_complete_schema.sql
003_seed_initial_data.sql
004_password_resets.sql
005_map_canvas_versions.sql
```

**Atenção:** a migration 002 depende da 001 e não é idempotente.

## 6. Execução local

**[PENDENTE DE VALIDAÇÃO]** Registrar o comando oficial para servir o backend local e a configuração de proxy/origem adotada pela equipe.

Uma alternativa comum do PHP é:

```powershell
php -S localhost:8000 -t backend/public
```

Esse comando é uma recomendação de desenvolvimento e deve ser validado no projeto.

## 7. Validações antes de commit

```powershell
cd frontend
npm ci
npm run lint
npm run build
cd ..

php -l backend\public\index.php
```

Para arquivos PHP alterados, execute `php -l` individualmente.

## 8. Git

Desenvolvimento ocorre na `main`.

```powershell
git checkout main
git pull --ff-only origin main
git status
```

Faça commits granulares e não inclua:

```text
.env
node_modules/
frontend/dist/
vendor/
storage/temp/
storage/uploads/
logs com dados sensíveis
```

## 9. Segurança local

- nunca reutilizar senha de produção;
- preferir banco local separado;
- não copiar dados clínicos reais sem base legal;
- anonimizar massas de teste;
- manter `APP_ENV=local` apenas localmente;
- não desativar CSRF fora do ambiente local.

## 10. Testes

**[PENDENTE DE VALIDAÇÃO]** Não foi confirmada suíte automatizada ativa. Até sua implementação, lint, build, análise PHP e testes manuais são obrigatórios.
