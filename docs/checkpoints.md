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
