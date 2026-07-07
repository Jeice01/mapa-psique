# Fluxo de Autenticação

## Register

1. Frontend chama `GET /api/csrf-token`.
2. Frontend envia `POST /api/auth/register` com `X-CSRF-Token`.
3. Backend valida senha minima, normaliza e-mail e impede cadastro como `administrador` ou `auditor`.
4. Senha e armazenada somente como hash seguro.
5. Evento `auth.register.success` ou `auth.register.failed` e auditado.

## Login

1. Frontend chama `GET /api/csrf-token`.
2. Frontend envia `POST /api/auth/login` com cookie e CSRF.
3. Backend valida credenciais com mensagem generica em falha.
4. Sessao e regenerada com `session_regenerate_id(true)`.
5. Evento `auth.login.success` ou `auth.login.failed` e auditado.
6. Resposta indica `requires_consent`.

## Consentimento

1. Frontend chama `GET /api/consents/active`.
2. Usuario le e aceita o termo.
3. Frontend envia `POST /api/consents/accept` com CSRF.
4. Backend registra `user_consents` e audita `consent.accepted`.

## Me

`GET /api/auth/me` retorna usuario seguro e `requires_consent`. `password_hash` nunca e retornado.

## Logout

1. Frontend chama `GET /api/csrf-token`.
2. Frontend envia `POST /api/auth/logout`.
3. Backend audita `auth.logout.success`, destroi a sessao e limpa o cookie.

## Auditoria

Eventos previstos:

- `auth.register.success`
- `auth.register.failed`
- `auth.login.success`
- `auth.login.failed`
- `auth.logout.success`
- `auth.me.access`
- `auth.unauthorized`
- `auth.forbidden`
- `consent.accepted`
- `csrf.token.issued`
- `dashboard.summary.viewed`
- `patient.created`
- `patient.updated`
- `patient.archived`
- `patient.viewed`
- `patient.listed`
- `map.created`
- `map.updated`
- `map.archived`
- `map.viewed`
- `map.listed`

Nao logar senha, hash, token CSRF completo, prompt, resposta de IA ou conteudo clinico.
