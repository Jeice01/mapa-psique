# Arquitetura do Mapa da Psiquê

## Stack oficial

- Frontend: React + Vite + TypeScript
- Backend: PHP 8.x puro
- Banco: MySQL/MariaDB da Hostinger
- Acesso ao banco: PDO

O projeto nao usa Next.js, Drizzle, PostgreSQL, Docker ou Docker Compose.

## Organizacao

```text
frontend/
  src/app/                 Entrada da aplicacao React
  src/components/          Componentes de UI e layout
  src/modules/             Modulos de dominio futuros
  src/shared/              API, tipos, utilitarios e constantes
  src/styles/              Organizacao futura de estilos

backend/
  public/                  Front controller exposto no servidor
  src/                     Codigo PHP PSR-4
  migrations/              SQL versionado
  storage/                 Arquivos privados fora de public
```

## Backend

`backend/public/index.php` carrega ambiente, aplica middlewares, registra rotas e devolve respostas JSON.

O acesso ao banco fica centralizado em `App\Database\Connection`, usando `PDO` com credenciais vindas de `.env`.

## Frontend

O frontend consome a API via `frontend/src/shared/api/httpClient.ts`. A URL base vem de `VITE_API_BASE_URL`.
