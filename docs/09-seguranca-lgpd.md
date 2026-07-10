# Segurança e LGPD — Mapa da Psiquê

## 1. Escopo

Este documento registra os controles de segurança confirmados, os dados tratados, riscos e recomendações de conformidade.

## 2. Controles confirmados

- autenticação por sessão PHP;
- regeneração do ID de sessão no login;
- cookie `HttpOnly`, `SameSite=Lax` e `Secure` em produção;
- expiração lógica da sessão;
- hash de senha com Argon2id e fallback para bcrypt;
- CSRF em operações mutáveis;
- CORS;
- rate limiting;
-