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

## Checkpoint tecnico - Prompt 06 Refinamento visual e funcional do Canvas

**Data:** 07/07/2026
**Commit main:** `ea3d157 feat: refine map canvas ux`
**Commit deploy:** `2a3eed9 deploy: publish prompt 06 canvas refinements`
**Ambiente:** Producao Hostinger
**Dominio:** https://mapapsique.orbisconect.com

Status: aprovado em producao.

Validacoes:

- Frontend publicado com build Vite atualizado.
- Assets novos publicados:
  - `assets/index-C5rDb0JQ.css`
  - `assets/index-Cc5H1XcY.js`
- Backend runtime atualizado em:
  - `api/_app/src/Database/Repositories/MapRepository.php`
- `.git/config` publico retorna `403 Forbidden`.
- Login com sessao por cookie HttpOnly validado.
- `GET /api/maps/{id}` retorna `200 OK`.
- `patient_name` retornando corretamente no detalhe do mapa.
- `canvas_json` preservado apos deploy.
- Interface exibe paciente pelo nome.
- Status exibido como `Rascunho`, mantendo valor interno `draft`.
- Canvas exibido com campos reflexivos.
- Indicador inicial `Sem alteracoes` validado.
- Alteracao de campo muda indicador para `Alteracoes nao salvas`.
- Botao `Salvar canvas` habilita durante edicao.
- Apos salvar, indicador retorna para `Sem alteracoes`.
- Dados permanecem preenchidos apos salvamento.

Observacoes:

- Sem IA, PDF, upload, relatorio ou novas dependencias nesta etapa.
- Estrutura protegida da API mantida em `api/_app`.

## Checkpoint tecnico - Prompt 07 Historico/versionamento simples do canvas

**Data/hora da validacao:** 07/07/2026 20:13:53 UTC
**Commit main:** `8a23f4e feat: add canvas version history`
**Commit deploy:** `a97e601 deploy: publish canvas version history`
**Ambiente:** Producao Hostinger
**Dominio:** https://mapapsique.orbisconect.com

Status final: PROMPT 07 VALIDADO EM PRODUCAO.

Resumo implementado:

- Historico simples de versoes do canvas por mapa.
- Migration criada em `backend/migrations/005_map_canvas_versions.sql`.
- Tabela `map_canvas_versions` com snapshot completo do canvas em `canvas_data`.
- Versionamento automatico no salvamento de `canvas_json`.
- Criacao de versao dentro do fluxo transacional de atualizacao do mapa.
- Endpoint criado: `GET /api/maps/{id}/canvas-versions`.
- Listagem retorna apenas metadados, sem expor `canvas_data`.

Mapa usado na validacao:

```text
d4926974-e4f2-4050-8cf8-cae8aebed730
```

Validacoes em producao:

- Endpoint de historico publicado e respondendo `HTTP/1.1 200 OK`.
- Antes do salvamento, o endpoint retornou `{"success":true,"data":[]}`.
- Salvamento feito via `PUT /api/maps/{id}` retornou `HTTP/1.1 200 OK`.
- Snapshot criado apos salvamento com `canvas_json`.
- `version_number` gerado corretamente.
- `canvas_data` nao aparece na listagem do historico.
- Mapa principal continuou respondendo `HTTP/1.1 200 OK`.
- Sem restauracao de versao, IA, PDF, upload ou comparacao visual nesta etapa.

Primeira versao criada:

```text
id=a8842fcc-b15c-47ae-a8df-3170be80940f
version_number=1
summary=Snapshot do canvas
created_at=2026-07-07 20:13:36
```

## Checkpoint tecnico - Prompt 08 Recuperar versao especifica do canvas

**Data/hora da validacao:** 07/07/2026 20:40:48 -03:00
**Commit main:** `809e6ed feat: add canvas version detail endpoint`
**Commit deploy:** `31f478f deploy: publish canvas version detail endpoint`
**Ambiente:** Producao Hostinger
**Dominio:** https://mapapsique.orbisconect.com

Status final: PROMPT 08 VALIDADO EM PRODUCAO.

Resumo implementado:

- Endpoint especifico criado: `GET /api/maps/{id}/canvas-versions/{versionId}`.
- Recuperacao somente leitura de uma versao especifica do canvas.
- Retorno do snapshot completo em `canvas_data` apenas no endpoint especifico.
- Listagem geral do historico preservada sem exposicao de `canvas_data`.
- Sem restauracao de versao, IA, PDF, upload ou alteracao de banco nesta etapa.

Dados usados na validacao:

```text
map_id=d4926974-e4f2-4050-8cf8-cae8aebed730
version_id=a8842fcc-b15c-47ae-a8df-3170be80940f
```

Validacoes em producao:

- Login via CSRF funcionando.
- Sessao renovada com sucesso.
- `/api/auth/me` retornou `HTTP/1.1 200 OK`.
- Endpoint especifico retornou `HTTP/1.1 200 OK`.
- Endpoint especifico retornou `success: true`.
- Endpoint especifico retornou `canvas_data` completo.
- Endpoint de listagem retornou `HTTP/1.1 200 OK`.
- Endpoint de listagem retornou `success: true`.
- Endpoint de listagem retornou apenas metadados.
- Endpoint de listagem nao retornou `canvas_data`.

## Checkpoint tecnico - Prompt 09 Preview visual de versao historica

**Data/hora da validacao:** 07/07/2026 21:27:34 -03:00
**Commit main:** `2a341d7 feat: add visual preview for canvas versions`
**Commit deploy:** `0310b4f deploy: publish visual preview for canvas versions`
**Ambiente:** Producao Hostinger
**Dominio:** https://mapapsique.orbisconect.com

Status final: PROMPT 09 VALIDADO EM PRODUCAO.

Objetivo do prompt:

- Exibir uma previa visual e organizada de uma versao historica do canvas.
- Usar o endpoint existente `GET /api/maps/{id}/canvas-versions/{versionId}`.
- Manter a visualizacao em modo somente leitura.
- Nao alterar o canvas atual e nao implementar restauracao.

Arquivo alterado:

```text
frontend/src/modules/maps/MapCanvas.tsx
```

Dados usados na validacao:

```text
map_id=d4926974-e4f2-4050-8cf8-cae8aebed730
version_id=a8842fcc-b15c-47ae-a8df-3170be80940f
```

Resultado visual validado em producao:

- A interface exibiu corretamente a area `PREVIA DA VERSAO HISTORICA`.
- A previa mostrou `Versao 1`.
- A previa mostrou a data `2026-07-07 20:13:36`.
- A previa mostrou o resumo `Snapshot do canvas`.
- A previa mostrou aviso claro de somente leitura.
- A previa mostrou o botao `Fechar previa`.
- Os campos do canvas foram renderizados em cards legiveis.
- Campos vazios apareceram como `Nao preenchido`.
- Nao existe botao `Restaurar`.
- O canvas atual nao foi alterado.
- Backend nao foi alterado.
- Sem migration, IA, PDF, upload ou restauracao nesta etapa.

## Checkpoint tecnico - Prompt 10A Backend de restauracao segura de versao historica do canvas

**Data/hora da validacao:** 07/07/2026 22:18:27 -03:00
**Commit main:** `c95bf43 feat: add safe canvas version restore endpoint`
**Commit deploy:** `7cadf7e deploy: publish safe canvas version restore endpoint`
**Ambiente:** Producao Hostinger
**Dominio:** https://mapapsique.orbisconect.com

Status final: PROMPT 10A VALIDADO EM PRODUCAO.

Objetivo do prompt:

- Criar backend seguro para restaurar o canvas atual a partir de uma versao historica.
- Exigir autenticacao, perfil profissional e CSRF.
- Validar ownership do mapa antes da restauracao.
- Criar snapshot automatico do canvas atual antes de substituir o conteudo.
- Manter a operacao atomica com transacao e rollback em caso de falha.

Endpoint criado:

```text
POST /api/maps/{id}/canvas-versions/{versionId}/restore
```

Dados usados na validacao:

```text
map_id=d4926974-e4f2-4050-8cf8-cae8aebed730
version_id_restaurado=a8842fcc-b15c-47ae-a8df-3170be80940f
backup_version_id=84b127ad-de61-4df9-b902-2b9121a87a60
restored_version_number=1
backup_version_number=2
```

Resultado validado em producao:

- `POST /restore` retornou `HTTP/1.1 200 OK`.
- Resposta retornou `success: true`.
- Resposta retornou `message: Versao restaurada com sucesso.`.
- Canvas atual passou a corresponder a versao restaurada.
- Snapshot automatico pre-restauracao foi criado antes da restauracao.
- Historico passou de 1 para 2 versoes.
- Backup criado com `version_number=2`.
- Summary do backup: `Snapshot automatico antes da restauracao`.
- Listagem do historico continua sem retornar `canvas_data`.
- Transacao segura validada: snapshot e update ocorreram juntos.
- Endpoint exige autenticacao/CSRF.
- Frontend ainda nao tem botao `Restaurar`.
- Sem migration, IA, PDF, upload ou Prompt 10B nesta etapa.

## Checkpoint tecnico - Prompt 10B Botao Restaurar com confirmacao na interface

**Data/hora da validacao:** 08/07/2026 09:04:37 -03:00
**Commit main:** `2b393b1 feat: add restore confirmation for canvas versions`
**Commit deploy:** `539dc51 deploy: publish restore confirmation for canvas versions`
**Ambiente:** Producao Hostinger
**Dominio:** https://mapapsique.orbisconect.com

Status final: PROMPT 10B VALIDADO EM PRODUCAO.

Objetivo do prompt:

- Adicionar na interface a acao de restaurar uma versao historica do canvas.
- Exigir confirmacao explicita antes de chamar o endpoint de restore.
- Informar ao usuario que o canvas atual sera substituido.
- Informar ao usuario que um backup automatico sera criado.
- Atualizar canvas e historico somente apos sucesso da API.

Arquivos alterados:

```text
frontend/src/modules/maps/MapCanvas.tsx
frontend/src/shared/api/httpClient.ts
```

Endpoint usado:

```text
POST /api/maps/{id}/canvas-versions/{versionId}/restore
```

Resultado visual validado em producao:

- A previa historica exibiu o botao `Restaurar esta versao`.
- O primeiro clique nao executou a restauracao.
- Foi exibida confirmacao inline.
- A confirmacao informou que o canvas atual seria substituido.
- A confirmacao informou que um backup automatico seria criado.
- A confirmacao informou que o historico seria preservado.
- Botao `Confirmar restauracao` presente.
- Botao `Cancelar` presente.

Resultado real validado em producao:

- Usuaria clicou em `Confirmar restauracao`.
- Interface exibiu: `Versao restaurada com sucesso. Backup automatico criado como versao 3.`
- Historico passou a exibir a versao 3.
- A versao 3 corresponde ao backup automatico criado antes da restauracao.
- Backend nao foi alterado nesta etapa.
- Sem migration, IA, PDF ou upload nesta etapa.

## Checkpoint tecnico - Prompt 11 Polimento UX e seguranca operacional do fluxo de versoes/restauracao

**Data/hora da validacao:** 08/07/2026 09:33:48 -03:00
**Commit main:** `0323642 feat: polish canvas version restore ux`
**Commit deploy:** `f33de6f deploy: publish canvas version restore ux polish`
**Ambiente:** Producao Hostinger
**Dominio:** https://mapapsique.orbisconect.com

Status final: PROMPT 11 VALIDADO EM PRODUCAO.

Objetivo do prompt:

- Melhorar a clareza visual e operacional do fluxo de historico, previa e restauracao de versoes do canvas.
- Reduzir risco de restauracao acidental.
- Melhorar mensagens de loading, erro e sucesso.
- Manter as regras de backend ja validadas sem alteracoes.

Arquivo alterado:

```text
frontend/src/modules/maps/MapCanvas.tsx
```

Melhorias feitas:

- Historico do canvas ficou mais legivel.
- Historico passou a exibir versao, data formatada, resumo e tipo da versao.
- Versao selecionada para previa passou a receber destaque `Em previa`.
- Versoes de backup passaram a ser identificadas como `Backup automatico`.
- Snapshots comuns continuam identificados como `Snapshot do canvas`.
- Estado vazio passou a informar que o historico sera criado automaticamente ao salvar o canvas.
- Mensagens de loading ficaram especificas para historico, previa e restauracao.
- Mensagens de erro e sucesso ficaram amigaveis, sem stack trace.
- Previa historica passou a reforcar que e uma visualizacao somente leitura.
- Previa historica reforca que abrir a previa nao altera o canvas atual.
- Confirmacao de restauracao ficou mais segura.
- Confirmacao passou a explicar que o canvas atual sera substituido.
- Confirmacao passou a explicar que um backup automatico sera criado antes da restauracao.
- Botao final de confirmacao so habilita apos digitar exatamente `RESTAURAR`.
- Botao `Cancelar` limpa a confirmacao e o texto digitado.
- Protecao contra duplo POST foi mantida.
- Canvas local continua sendo atualizado somente apos sucesso da API.

Validacao visual em producao:

- Historico do canvas exibiu versao, data, resumo e tipo.
- Versoes de backup apareceram como `Backup automatico`.
- Versao selecionada apareceu com destaque `Em previa`.
- Area de previa historica reforcou visualizacao somente leitura.
- Clique em `Restaurar esta versao` abriu confirmacao inline sem executar POST.
- Botao `Confirmar restauracao` permaneceu desabilitado antes de digitar `RESTAURAR`.
- Apos digitar `RESTAURAR`, o botao final habilitou.
- Botao `Cancelar` limpou a confirmacao.
- Mensagens de loading, erro e sucesso ficaram mais amigaveis.
- Nao foi executada restauracao real automatica durante o deploy.

Publicacao na deploy:

```text
index.html
assets/index-D7rilL_M.css
assets/index-wcxDDnn0.js
```

Confirmacoes:

- Backend nao foi alterado.
- `api/_app` nao foi alterado.
- `frontend/src/shared/api/httpClient.ts` nao foi alterado.
- Nao houve migration.
- Nao houve IA, PDF ou upload.
- Nao houve merge completo da `main` na `deploy`.

---

## Prompt 12A — Backend de exportação PDF do mapa

**Status:** PROMPT 12A VALIDADO EM PRODUÇÃO

### Objetivo

Implementar endpoint backend para exportação em PDF do mapa atual, com autorização por usuário/profissional, sem alterar frontend, sem criar migration e sem adicionar dependência externa.

### Endpoint criado

```text
GET /api/maps/{id}/export/pdf
---

## Prompt 12B — Botão Exportar PDF no frontend

**Status:** PROMPT 12B VALIDADO EM PRODUÇÃO

### Objetivo

Adicionar na interface do mapa/canvas um botão para exportar o mapa atual em PDF, usando o endpoint backend já implementado e validado no Prompt 12A.

### Endpoint utilizado

```text
GET /api/maps/{id}/export/pdf