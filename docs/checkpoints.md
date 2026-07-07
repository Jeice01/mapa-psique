# Checkpoints tecnicos

Registro de marcos tecnicos validados no projeto. Use este arquivo como historico de estado antes de iniciar novas etapas.

## 2026-07-07 - Hostinger validada

Ambiente Hostinger validado com sucesso.

Dominio:

```text
https://mapapsique.orbisconect.com
```

Validacoes aprovadas:

- Webroot publico protegido
- Frontend Vite build publicado
- API PHP 8.3 respondendo
- Banco MySQL/MariaDB conectado
- CSRF ativo
- Sessao via cookie HttpOnly, Secure e SameSite=Lax
- Login real aprovado
- `/api/auth/me` aprovado
- `/api/patients` aprovado
- `/api/maps` aprovado
- Criacao real de paciente aprovada
- Criacao real de mapa aprovada

Pendencia:

- `/api/dashboard` retorna 404; verificar registro da rota futuramente.

Proxima etapa planejada:

- Implementar Prompt 05: Canvas inicial do Mapa da Psique.
