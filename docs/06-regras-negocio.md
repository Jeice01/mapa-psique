# Regras de Negócio — Mapa da Psiquê

> Status: primeira versão consolidada.

## 1. Usuários e acesso

- cadastro público cria somente perfil `profissional`;
- apenas usuário com status `active` pode autenticar;
- usuário inexistente, inativo ou com senha incorreta recebe resposta genérica;
- sessão expirada ou usuário excluído logicamente perde acesso;
- módulos protegidos exigem consentimento vigente;
- pacientes e mapas exigem perfil `profissional`.

## 2. Consentimento

- deve existir termo ativo;
- o usuário deve aceitar o termo ativo antes da área protegida;
- nova versão de termo deve exigir novo aceite;
- termos já aceitos não devem ter seu texto alterado retroativamente.

A última regra é **[RECOMENDAÇÃO]**; o seed atual permite atualizar o conteúdo mantendo a versão.

## 3. Pacientes

### 3.1 Propriedade

Todo paciente pertence a um profissional por `owner_user_id`.

### 3.2 Criação

- nome obrigatório;
- status inicial `active`;
- idade opcional entre 0 e 120;
- código interno opcional;
- observações limitadas a 5.000 caracteres.

### 3.3 Status

```text
active
inactive
archived
```

### 3.4 Arquivamento

```text
status = archived
deleted_at = CURRENT_TIMESTAMP
deleted_by = usuário autenticado
```

O registro permanece no banco.

### 3.5 Reativação

```text
status = active
deleted_at = NULL
deleted_by = NULL
```

### 3.6 Paciente arquivado

- não deve ser editado;
- pode ser reativado;
- não pode receber novo mapa;
- não pode ser selecionado como novo vínculo;
- mapas já existentes permanecem vinculados;
- o nome deve continuar disponível nos mapas históricos.

### 3.7 Inconsistência atual

O endpoint comum de edição aceita `archived` sem preencher os campos de soft delete.

**[RECOMENDAÇÃO]** Reservar `archived` exclusivamente para a rota de arquivamento.

## 4. Mapas

### 4.1 Propriedade

Todo mapa pertence a um profissional por `owner_user_id`.

### 4.2 Criação

- título obrigatório;
- motivo opcional;
- paciente opcional;
- paciente, quando informado, deve pertencer ao mesmo profissional;
- paciente não pode estar arquivado;
- status inicial `draft`;
- Canvas inicial `null`.

### 4.3 Status

```text
draft
ready_for_analysis
analyzed
archived
```

### 4.4 Arquivamento

```text
status = archived
deleted_at = CURRENT_TIMESTAMP
deleted_by = usuário autenticado
```

### 4.5 Mapa arquivado

**[CONFIRMADO NO CÓDIGO]**

- não aparece na listagem normal;
- não pode ser atualizado;
- não pode receber restauração de Canvas;
- ainda pode ser consultado diretamente;
- ainda pode ter histórico consultado;
- ainda pode ser exportado em PDF.

**[PENDENTE DE DECISÃO]** Confirmar se o comportamento oficial será somente leitura ou ocultação total.

### 4.6 Inconsistências atuais

- edição comum aceita `archived` sem soft delete;
- filtro `archived` não retorna mapas arquivados corretamente;
- detalhe do mapa pode perder o nome do paciente arquivado.

## 5. Canvas

O Canvas possui nove campos textuais estruturados.

- JSON deve ser válido;
- salvamento do Canvas deve atualizar o mapa;
- cada salvamento deve criar versão;
- atualização e versão devem ocorrer na mesma transação;
- propriedades desconhecidas ainda não são bloqueadas pelo backend.

**[RECOMENDAÇÃO]** Criar validação formal do schema.

## 6. Versões

- numeração é sequencial por mapa;
- combinação `map_id + version_number` é única;
- versão pertence ao mapa informado;
- versões são somente leitura;
- não existe exclusão de versão na API atual;
- restauração substitui apenas `canvas_json`;
- antes de restaurar, o Canvas atual é salvo como backup;
- restauração ocorre em transação e com bloqueio `FOR UPDATE`.

## 7. PDF

- somente proprietário autenticado pode exportar;
- mapa atual e versão histórica podem ser exportados;
- exportação histórica deve informar número, data e resumo da versão;
- exportação é auditada.

## 8. Auditoria

- eventos críticos de autenticação e operação devem ser registrados;
- auditoria deve preservar ator, ação, entidade, data e contexto;
- registros não devem ser atualizados ou excluídos pela aplicação;
- falha da auditoria não interrompe atualmente a operação principal.

## 9. Recuperação de senha

- resposta da solicitação não revela se o e-mail existe;
- token original não é armazenado;
- token expira em 1 hora;
- token é de uso único;
- tokens anteriores são revogados;
- nova senha deve cumprir a política vigente.

## 10. Segurança transversal

- alteração de dados exige CSRF;
- recursos protegidos exigem autenticação;
- consultas devem aplicar proprietário no SQL;
- entrada deve ser validada e sanitizada;
- SQL deve usar prepared statements;
- informações técnicas internas não devem ser expostas ao usuário.

## 11. Regras futuras previstas

Ainda não implementadas de forma confirmada:

- análise por IA somente com prompt aprovado;
- prompt clínico versionado;
- rastreabilidade do modelo e arquivos usados;
- guardrails registrados;
- compartilhamento controlado de notas com paciente;
- quarentena de arquivos.

## 12. Decisões pendentes

- unicidade do código interno por profissional;
- data de nascimento versus idade;
- restauração de mapas arquivados;
- retenção de versões;
- retenção de auditoria;
- expurgo de tokens;
- política de exclusão definitiva;
- perfis administrador, paciente e auditor.
