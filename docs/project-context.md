# Contexto do projeto para novos chats

Este arquivo resume o estado atual, a arquitetura e o fluxo de deploy do projeto.
Ao abrir um novo chat, peça para ler este arquivo antes de propor mudanças.

Historico tecnico validado:

```text
docs/checkpoints.md
```

## Projeto

Nome: Gerador do Mapa da Psique.

Aplicação web para profissionais conduzirem a técnica do Mapa da Psiquê com pacientes, canvas versionado, upload de mapas, exportação PDF e análise assistida por IA.

Subdominio de producao:

```text
https://mapapsique.orbisconect.com
```

Diretorio de publicacao na Hostinger:

```text
/home/u754689460/domains/mapapsique.orbisconect.com/public_html
```

Repositorio GitHub:

```text
https://github.com/Jeice01/mapa-psique
```

## Stack oficial

- Frontend: React + Vite + TypeScript + Tailwind CSS
- Backend: PHP 8.x puro
- Banco: MySQL/MariaDB da Hostinger
- Acesso ao banco: PDO
- Hospedagem: Hostinger PHP/HTML tradicional
- API servida em `/api`

Nao usar:

- Next.js
- Drizzle
- PostgreSQL
- Docker
- Docker Compose
- JWT
- `localStorage` para sessao
- Node.js no servidor Hostinger

## Branches

- `main`: codigo-fonte do projeto.
- `deploy`: pacote publicado na Hostinger.

A branch `deploy` contem os artefatos prontos para publicacao, incluindo `index.html`, `assets/` e backend PHP necessario para producao.

No webroot publico de producao, o pacote runtime deve manter apenas:

```text
api/
assets/
.htaccess
index.html
.git
```

Observacao: `.git` permanece no servidor porque o deploy atual usa `git pull origin deploy`. O acesso HTTP a `.git` deve permanecer bloqueado por `.htaccess`.

## Deploy

O deploy automatico esta configurado com GitHub Actions em:

```text
.github/workflows/deploy-hostinger.yml
```

Quando houver push na branch `deploy`, o workflow conecta na Hostinger por SSH e executa:

```bash
cd /home/u754689460/domains/mapapsique.orbisconect.com/public_html
git pull origin deploy
```

O deploy manual continua funcionando como plano B:

```bash
cd /home/u754689460/domains/mapapsique.orbisconect.com/public_html
git pull origin deploy
```

## Secrets do GitHub Actions

Os dados sensiveis ficam em GitHub Repository Secrets, nunca no codigo:

```text
HOSTINGER_SSH_HOST
HOSTINGER_SSH_PORT
HOSTINGER_SSH_USER
HOSTINGER_SSH_PRIVATE_KEY
```

Valores operacionais devem ser consultados nos Secrets do GitHub, nao no repositorio:

```text
HOSTINGER_SSH_HOST=<cadastrado no GitHub Secret>
HOSTINGER_SSH_PORT=<cadastrado no GitHub Secret>
HOSTINGER_SSH_USER=<cadastrado no GitHub Secret>
```

Nunca registrar a chave privada no repositorio, README, docs, logs ou chat.

## Ambiente de producao

O arquivo `.env` real da Hostinger deve ficar no servidor, fora do Git:

```text
/home/u754689460/domains/mapapsique.orbisconect.com/public_html/api/_app/.env
```

O repositorio deve manter apenas:

```text
backend/.env.example
frontend/.env.example
```

Variaveis esperadas em producao:

```text
APP_ENV=production
APP_URL=https://mapapsique.orbisconect.com
APP_ALLOWED_ORIGINS=https://mapapsique.orbisconect.com
DB_HOST=localhost
DB_PORT=3306
DB_NAME=placeholder
DB_USER=placeholder
DB_PASS=placeholder
DB_CHARSET=utf8mb4
SESSION_COOKIE_NAME=mapa_psique_session
SESSION_LIFETIME_MINUTES=120
CSRF_ENABLED=true
MAIL_FROM=no-reply@mapapsique.orbisconect.com
MAIL_FROM_NAME=Mapa da Psique
OPENAI_API_KEY=<cadastrado somente no servidor>
OPENAI_TEXT_MODEL=gpt-4o
OPENAI_IMAGE_MODEL=dall-e-3
ANTHROPIC_API_KEY=<opcional; cadastrado somente no servidor>
ANTHROPIC_TEXT_MODEL=claude-opus-4-8
```

Use placeholders na documentacao. Credenciais reais nunca devem ser versionadas.

## Segurança obrigatoria

- Sessao por cookie HttpOnly
- `SameSite=Lax`
- `Secure` em producao
- CSRF via header `X-CSRF-Token`
- CORS com allowlist e credentials
- PDO com prepared statements
- Nunca logar senhas, tokens, chaves, prompts, respostas de IA ou dados clinicos
- Nunca versionar `.env` real, dumps, backups, logs ou arquivos com credenciais

## Estado funcional atual

Validado em producao:

- Frontend carrega
- `/api/health` funciona
- `/api/db-check` funciona
- `/api/csrf-token` funciona
- `/api/auth/me` funciona
- `/api/patients` funciona
- `/api/maps` funciona
- Banco conectado
- Tabelas existem
- Cadastro/login com sessao por cookie
- Criacao real de paciente
- Criacao real de mapa
- Canvas inicial salva e recupera `canvas_json`
- Fluxo de esqueci senha com token por e-mail
- Deploy automatico via GitHub Actions e SSH
- Webroot publico protegido
- Upload autenticado de imagem do mapa
- Geração assistida do canvas por visão
- Exportação PDF do mapa e de versões
- Análise textual por IA com fallback de provedor
- Infográfico gerado por IA
- Workflow de CI para `main`

Pendências conhecidas:

- validar juridicamente o termo de consentimento e a base legal de cada tratamento;
- implementar direitos do titular, revogação, retenção, anonimização e eliminação;
- reduzir dados identificáveis enviados aos provedores de IA;
- formalizar revisão humana e aprovação clínica das saídas de IA;
- endurecer uploads e excluir arquivos órfãos;
- ampliar testes automatizados com integração MySQL/MariaDB e fluxos E2E;
- executar testes manuais completos dos fluxos críticos.

## Como orientar novos chats

Mensagem recomendada ao iniciar uma nova conversa:

```text
Leia docs/project-context.md, docs/checkpoints.md, README.md, docs/architecture.md, docs/security-baseline.md e docs/deployment-hostinger.md antes de alterar qualquer coisa. Respeite a stack oficial: React/Vite/TypeScript/Tailwind, PHP puro, PDO e MySQL/MariaDB Hostinger. Nao use Next.js, Docker, PostgreSQL, Drizzle, JWT ou localStorage para sessao.
```
