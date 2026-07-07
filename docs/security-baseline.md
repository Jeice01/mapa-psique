# Security Baseline

## Headers

O backend aplica:

- `X-Content-Type-Options: nosniff`
- `X-Frame-Options: DENY`
- `Referrer-Policy: strict-origin-when-cross-origin`
- `Permissions-Policy: geolocation=(), microphone=(), camera=()`
- CSP basica, permissiva apenas o suficiente para desenvolvimento local.

## CORS

As origens permitidas sao configuradas em `APP_ALLOWED_ORIGINS`. Nao use `*` em producao.

Como a sessao usa cookie, CORS permite credenciais apenas quando `HTTP_ORIGIN` esta na lista permitida. O frontend deve usar `credentials: 'include'`.

## Sessao

`SessionManager` centraliza cookie e ciclo da sessao:

- cookie HttpOnly;
- `Secure` em producao;
- `SameSite=Lax`;
- `Path=/`;
- expiracao por `SESSION_LIFETIME_MINUTES`;
- `session_regenerate_id(true)` apos login.

A sessao guarda apenas `user_id`, `role`, `authenticated_at` e `expires_at`.

## CSRF

`GET /api/csrf-token` emite o token. Endpoints mutaveis de autenticacao e consentimento exigem `X-CSRF-Token`.

`CSRF_ENABLED=true` deve permanecer habilitado por padrao, especialmente em producao.

## RBAC

Perfis iniciais:

- `administrador`
- `profissional`
- `paciente`
- `auditor`

`AuthMiddleware` valida sessao ativa. `RoleMiddleware` valida permissoes e registra `auth.forbidden`.

Rotas de dashboard, pacientes e mapas exigem usuário autenticado, consentimento ativo aceito e ownership por `owner_user_id`. Nesta etapa, `profissional` acessa pacientes/mapas próprios; `administrador` acessa apenas o resumo básico.

## Rate Limit

Ha um limitador inicial por IP e rota em `backend/storage/temp/rate-limit/`. Ele e simples, baseado em arquivo, e pode evoluir para Redis ou tabela dedicada.

## Senhas

`PasswordHasher` usa Argon2id quando disponivel e bcrypt como fallback.

`Csrf` está integrado aos endpoints mutáveis de autenticação e consentimento por meio do header `X-CSRF-Token`.

## Logs

`Logger` grava JSON em `backend/storage/logs/app.log` e remove campos sensiveis conhecidos. Nao logar senha, conteudo clinico, prompt completo ou resposta completa de IA.

Eventos de autenticacao e consentimento sao registrados em `audit_logs` com metadados tecnicos, sem senha, hash, token CSRF completo ou conteudo clinico.

Eventos de pacientes e mapas tambem usam apenas IDs, rota, metodo e status. Nao logar `patients.notes` nem `maps.reason`.
