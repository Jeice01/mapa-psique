# API — Mapa da Psiquê

> Base URL em produção: `https://mapapsique.orbisconect.com/api`

## 1. Convenções

- autenticação por cookie de sessão;
- frontend envia `credentials: include`;
- operações mutáveis usam `X-CSRF-Token`;
- respostas de erro usam códigos HTTP;
- envelopes ainda não estão totalmente padronizados.

## 2. Códigos relevantes

```text
200 sucesso
204 preflight CORS
400 dados ou filtros inválidos
401 não autenticado
403 perfil ou consentimento insuficiente
404 recurso não encontrado
419 CSRF inválido
429 limite de requisições
500 falha interna
```

## 3. Infraestrutura

### GET `/health`
Verifica saúde básica da API.

### GET `/db-check`
Verifica conexão com o banco.

**[RECOMENDAÇÃO]** Revisar exposição pública e conteúdo da resposta.

### GET `/csrf-token`
Retorna:

```json
{"status":"ok","csrf_token":"..."}
```

## 4. Autenticação

### POST `/auth/register`
CSRF: sim. Público.

Corpo:

```json
{"name":"Nome","email":"email@exemplo.com","password":"senha123"}
```

O backend força `role=profissional` e `status=active`.

### POST `/auth/login`
CSRF: sim. Público.

```json
{"email":"email@exemplo.com","password":"senha123"}
```

Resposta contém usuário e `requires_consent`.

### GET `/auth/me`
Autenticação: sim.

Retorna sessão atual e necessidade de consentimento.

### POST `/auth/logout`
Autenticação: sim. CSRF: sim.

### POST `/auth/forgot-password`
CSRF: sim. Público.

```json
{"email":"email@exemplo.com"}
```

Resposta sempre genérica.

### POST `/auth/reset-password`
CSRF: sim. Público.

```json
{"token":"...","password":"novaSenha123"}
```

## 5. Consentimentos

### GET `/consents/active`
Retorna o termo ativo.

### POST `/consents/accept`
Autenticação: sim. CSRF: sim.

```json
{"consent_term_id":"uuid"}
```

## 6. Dashboard

### GET `/dashboard/summary`
Autenticação, consentimento e perfil profissional.

Resposta esperada:

```json
{
  "summary": {
    "patients_count": 0,
    "maps_count": 0,
    "draft_maps_count": 0,
    "analyzed_maps_count": 0
  }
}
```

## 7. Pacientes

### GET `/patients`
Query:

```text
q
status
page
per_page
```

Status aceitos: `active`, `inactive`, `archived`.

### POST `/patients`
CSRF: sim.

Campos:

```text
name obrigatório
internal_code opcional
age opcional, 0 a 120
notes opcional
```

### GET `/patients/{id}`
Consulta paciente do proprietário.

### PUT `/patients/{id}`
CSRF: sim.

Campos editáveis:

```text
name
internal_code
age
notes
status
```

**[RECOMENDAÇÃO]** Não aceitar `archived` por esta rota.

### DELETE `/patients/{id}`
CSRF: sim. Executa soft delete.

### POST `/patients/{id}/restore`
CSRF: sim. Reativa paciente arquivado.

## 8. Mapas

### GET `/maps`
Query:

```text
q
status
patient_id
page
per_page
```

### POST `/maps`
CSRF: sim.

Campos:

```text
title obrigatório
patient_id opcional
reason opcional
```

Status e Canvas são definidos pelo backend.

### GET `/maps/{id}`
Consulta mapa do proprietário.

### PUT `/maps/{id}`
CSRF: sim.

Campos:

```text
title
patient_id
reason
status
canvas_json
```

Enviar `canvas_json` cria nova versão.

### DELETE `/maps/{id}`
CSRF: sim. Executa soft delete.

### GET `/maps/{id}/export/pdf`
Retorna `application/pdf`.

## 9. Versões do Canvas

### GET `/maps/{id}/canvas-versions`
Lista versões.

### GET `/maps/{id}/canvas-versions/{versionId}`
Retorna detalhes e conteúdo.

### POST `/maps/{id}/canvas-versions/{versionId}/restore`
CSRF: sim.

Resposta:

```json
{
  "success": true,
  "data": {
    "map_id": "uuid",
    "restored_version_id": "uuid",
    "restored_version_number": 1,
    "backup_version_id": "uuid",
    "backup_version_number": 2
  }
}
```

### GET `/maps/{id}/canvas-versions/{versionId}/export/pdf`
Retorna PDF da versão histórica.

## 10. Autorização

Pacientes, mapas, dashboard e versões exigem:

```text
autenticação
consentimento
perfil profissional
propriedade do recurso
```

## 11. Rate limit

Configuração observada:

```text
20 para rotas sensíveis
120 para demais rotas
```

**[PENDENTE]** Confirmar janela temporal e resposta exata.

## 12. Problemas de consistência

- envelopes `status`, `success` e `data` variam;
- mensagens funcionais são às vezes genéricas;
- filtro de mapas arquivados precisa de correção;
- `archived` deve ser removido da edição comum;
- `/db-check` precisa de revisão de exposição.

## 13. Exemplo de chamada

```javascript
fetch('/api/patients', {
  credentials: 'include',
  headers: { Accept: 'application/json' }
});
```

Operação mutável:

```javascript
fetch('/api/patients', {
  method: 'POST',
  credentials: 'include',
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
    'X-CSRF-Token': token
  },
  body: JSON.stringify(payload)
});
```

## 14. Pendências

- documentar schemas completos de resposta;
- listar mensagens de erro por endpoint;
- padronizar envelope;
- gerar OpenAPI futuramente;
- documentar exemplos de PDF e headers;
- confirmar CORS e rate limit.
