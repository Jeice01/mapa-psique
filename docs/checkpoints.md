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

## 2026-07-07 - Prompt 05 validado em producao

Canvas inicial do Mapa da Psique implementado e validado em producao.

Commit:

```text
914f8f1 feat: implement initial map canvas
```

Dominio:

```text
https://mapapsique.orbisconect.com
```

Validacoes aprovadas:

- Frontend com componente inicial de canvas publicado
- Frontend buildado com `MapCanvas.tsx`
- Runtime PHP em `api/_app` atualizado
- `PUT /api/maps/{id}` aceita `canvas_json`
- `MapRepository` persiste `canvas_json`
- `canvas_json` persiste no banco
- `GET /api/maps/{id}` retorna `canvas_json` preenchido
- CSRF validado no `PUT`
- Sessao por cookie validada
- Ownership/RBAC mantidos
- Webroot runtime mantido protegido
- Sem IA, PDF, upload ou dependencias novas

Status:

- Backend em producao aprovado
- Teste visual pendente/aprovado conforme validacao no navegador

Mapa usado na validacao:

```text
d4926974-e4f2-4050-8cf8-cae8aebed730
```

Campos validados no retorno:

```text
main_demand=Teste canvas apos atualizar runtime
current_context=Runtime api _app atualizado
next_steps=Confirmar retorno preenchido no GET
```

Proxima etapa planejada:

- Evoluir o canvas visual/interativo conforme proximo prompt, sem IA, PDF ou upload ate que essas etapas sejam explicitamente solicitadas.
