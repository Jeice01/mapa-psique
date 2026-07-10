# Visão Geral do Sistema — Mapa da Psiquê

> **Documento:** `docs/01-visao-geral.md`  
> **Status:** Primeira versão para validação  
> **Sistema:** Mapa da Psiquê  
> **Ambiente de produção:** `https://mapapsique.orbisconect.com`

---

## 1. Objetivo deste documento

Este documento apresenta a visão geral do sistema **Mapa da Psiquê**, incluindo:

- finalidade da plataforma;
- problema que busca resolver;
- público usuário;
- escopo funcional atual;
- principais fluxos;
- limites de uso;
- contexto técnico e operacional;
- funcionalidades previstas para evolução futura.

As informações são classificadas como:

- **[CONFIRMADO NO CÓDIGO]**: comprovado nos arquivos analisados;
- **[CONFIRMADO NO BANCO]**: comprovado nas migrations;
- **[CONFIRMADO EM PRODUÇÃO]**: validado no ambiente publicado;
- **[INFORMADO PELA RESPONSÁVEL]**: fornecido pela responsável pelo projeto;
- **[PENDENTE DE VALIDAÇÃO]**: ainda precisa ser confirmado;
- **[RECOMENDAÇÃO]**: melhoria sugerida.

---

## 2. Identificação do sistema

| Item | Informação |
|---|---|
| Nome | Mapa da Psiquê |
| Tipo | Aplicação web |
| Ambiente de produção | `https://mapapsique.orbisconect.com` |
| Repositório | `https://github.com/Jeice01/mapa-psique.git` |
| Hospedagem | Hostinger |
| Backend | PHP 8.3 em produção |
| Banco de dados | MySQL/MariaDB |
| Frontend | React, TypeScript e Vite |

---

## 3. Finalidade do sistema

**[INFORMADO PELA RESPONSÁVEL]**

O Mapa da Psiquê é uma plataforma voltada à organização estruturada de informações relacionadas ao acompanhamento e à reflexão sobre pacientes e seus mapas.

A aplicação permite que um profissional autenticado:

- cadastre pacientes;
- organize mapas vinculados a esses pacientes;
- registre informações em um Canvas estruturado;
- acompanhe diferentes estados dos mapas;
- preserve versões anteriores do Canvas;
- restaure versões históricas;
- exporte mapas em PDF;
- mantenha histórico e rastreabilidade das principais ações.

O sistema foi projetado para apoiar organização, acompanhamento, registro reflexivo e continuidade do trabalho profissional.

---

## 4. Problema que o sistema busca resolver

**[INFORMADO PELA RESPONSÁVEL]**

Sem uma plataforma centralizada, registros de pacientes e mapas podem ficar distribuídos entre documentos, planilhas, arquivos locais, mensagens e anotações sem versionamento.

Isso pode gerar:

- perda de contexto;
- dificuldade de localizar informações;
- ausência de histórico das alterações;
- risco de sobrescrever conteúdos anteriores;
- dificuldade para recuperar versões;
- baixa rastreabilidade;
- vínculos frágeis entre pacientes e seus mapas;
- processos manuais para geração de documentos.

O sistema busca reduzir esses problemas por meio de:

- cadastro centralizado;
- organização por proprietário;
- vínculo entre paciente e mapa;
- Canvas estruturado;
- histórico de versões;
- restauração com backup automático;
- exportação em PDF;
- auditoria das ações.

---

## 5. Público usuário

### 5.1 Usuário atualmente confirmado

**[CONFIRMADO NO CÓDIGO]**

O perfil funcional utilizado nas rotas de pacientes e mapas é:

```text
profissional
```

Esse perfil pode, de acordo com as permissões implementadas:

- acessar o Dashboard;
- cadastrar pacientes;
- consultar pacientes próprios;
- editar pacientes não arquivados;
- arquivar e reativar pacientes;
- criar mapas;
- consultar mapas próprios;
- editar mapas não arquivados;
- salvar o Canvas;
- consultar versões históricas;
- restaurar versões;
- exportar PDFs.

### 5.2 Perfis previstos no banco

**[CONFIRMADO NO BANCO]**

O banco prevê:

```text
administrador
profissional
paciente
auditor
```

**[PENDENTE DE VALIDAÇÃO]**

Ainda não foram confirmadas funcionalidades completas para:

- administrador;
- paciente;
- auditor.

Esses perfis não devem ser apresentados como disponíveis até que existam fluxos, permissões e interfaces validados.

---

## 6. Escopo funcional atual

## 6.1 Autenticação

**[CONFIRMADO NO CÓDIGO]**

O sistema possui:

- cadastro de usuário profissional;
- login;
- consulta da sessão atual;
- logout;
- recuperação de senha;
- redefinição de senha;
- controle de sessão por cookie seguro.

## 6.2 Consentimento

**[CONFIRMADO NO CÓDIGO E NO BANCO]**

Antes de acessar a área protegida, o usuário precisa aceitar o termo de consentimento ativo.

O aceite pode registrar:

- usuário;
- versão do termo;
- data;
- IP;
- user agent;
- metadados.

## 6.3 Dashboard

**[CONFIRMADO NO CÓDIGO]**

O Dashboard apresenta indicadores resumidos relacionados a:

- quantidade de pacientes;
- quantidade de mapas;
- mapas em rascunho;
- mapas analisados.

## 6.4 Pacientes

**[CONFIRMADO NO CÓDIGO E EM PRODUÇÃO]**

O módulo permite:

- listar pacientes;
- pesquisar por nome ou código interno;
- filtrar por status;
- criar;
- consultar;
- editar;
- arquivar;
- reativar.

Status:

```text
active
inactive
archived
```

O arquivamento utiliza soft delete, preservando o registro no banco.

## 6.5 Mapas

**[CONFIRMADO NO CÓDIGO E EM PRODUÇÃO]**

O módulo permite:

- listar mapas;
- pesquisar por título;
- filtrar por status;
- filtrar por paciente;
- criar;
- consultar;
- editar;
- arquivar;
- exportar em PDF.

Status:

```text
draft
ready_for_analysis
analyzed
archived
```

## 6.6 Canvas

**[CONFIRMADO NO CÓDIGO]**

O Canvas atual é textual e estruturado nos seguintes campos:

```text
main_demand
current_context
emotional_history
recurring_patterns
core_beliefs
defense_strategies
internal_resources
reflective_hypotheses
next_steps
```

Na interface, esses campos representam:

- queixa ou demanda principal;
- contexto de vida atual;
- história emocional relevante;
- padrões recorrentes;
- crenças centrais;
- estratégias de proteção ou defesa;
- potenciais e recursos internos;
- hipóteses reflexivas;
- próximos passos.

## 6.7 Histórico de versões

**[CONFIRMADO NO CÓDIGO E NO BANCO]**

Cada salvamento do Canvas cria uma versão histórica.

O usuário pode:

- listar versões;
- visualizar detalhes;
- exportar uma versão em PDF;
- restaurar uma versão anterior.

Antes da restauração, o sistema cria automaticamente um backup do Canvas atual.

## 6.8 Exportação em PDF

**[CONFIRMADO NO CÓDIGO]**

O sistema exporta:

- o mapa atual;
- versões históricas do Canvas.

## 6.9 Auditoria

**[CONFIRMADO NO CÓDIGO E NO BANCO]**

As principais ações são registradas em trilha de auditoria, incluindo eventos de:

- autenticação;
- acesso não autorizado;
- cadastro e consulta de pacientes;
- atualização, arquivamento e reativação;
- criação e consulta de mapas;
- histórico do Canvas;
- restauração;
- exportação de PDF.

---

## 7. Regras gerais de negócio

### 7.1 Propriedade dos dados

**[CONFIRMADO NO CÓDIGO]**

Pacientes e mapas são isolados por:

```text
owner_user_id
```

Um profissional não deve acessar registros pertencentes a outro profissional pelas rotas analisadas.

### 7.2 Pacientes arquivados

**[CONFIRMADO NO CÓDIGO E INFORMADO PELA RESPONSÁVEL]**

Pacientes arquivados:

- permanecem armazenados;
- mantêm os mapas já existentes;
- podem ser reativados;
- não podem receber novos mapas;
- não podem ser selecionados como novo vínculo de um mapa;
- não devem ser editados enquanto arquivados.

### 7.3 Mapas vinculados a pacientes arquivados

**[CONFIRMADO NO CÓDIGO]**

Os mapas antigos permanecem vinculados ao paciente.

A listagem preserva:

- `patient_id`;
- nome do paciente;
- status do paciente.

**[PENDENTE DE CORREÇÃO]**

Na consulta detalhada, o nome do paciente arquivado pode não ser retornado por causa de uma condição no `JOIN`.

### 7.4 Mapas arquivados

**[CONFIRMADO NO CÓDIGO]**

Mapas arquivados:

- não podem ser atualizados;
- não recebem restauração de versão;
- podem continuar acessíveis diretamente para leitura e PDF.

**[PENDENTE DE VALIDAÇÃO FUNCIONAL]**

Definir oficialmente se o comportamento correto é mantê-los disponíveis em modo somente leitura.

---

## 8. Segurança e privacidade

**[CONFIRMADO NO CÓDIGO]**

Controles existentes:

- sessão autenticada;
- regeneração do ID da sessão no login;
- cookie `HttpOnly`;
- cookie `Secure` em produção;
- `SameSite=Lax`;
- CSRF;
- CORS;
- rate limiting;
- Security Headers;
- autorização por perfil;
- consentimento obrigatório;
- prepared statements;
- validação e sanitização;
- isolamento por proprietário;
- soft delete;
- auditoria;
- hash de senha com Argon2id ou bcrypt;
- token de redefinição armazenado apenas como hash.

### 8.1 Dados tratados

O sistema pode armazenar dados pessoais e potencialmente sensíveis, incluindo:

- nome;
- e-mail;
- idade;
- observações;
- motivo do mapa;
- história emocional;
- crenças;
- estratégias de defesa;
- hipóteses reflexivas;
- registros de auditoria;
- IP e user agent.

**[RECOMENDAÇÃO]**

A operação deve considerar obrigações aplicáveis da LGPD, especialmente quanto a:

- finalidade;
- necessidade;
- acesso;
- retenção;
- descarte;
- segurança;
- resposta a incidentes;
- direitos do titular.

---

## 9. Limites do sistema

### 9.1 Não substituição de acompanhamento profissional

**[CONFIRMADO NO BANCO]**

O termo inicial informa que a ferramenta:

- possui finalidade de apoio reflexivo e autoconhecimento;
- não substitui acompanhamento psicológico, médico ou terapêutico.

### 9.2 Não emissão de diagnóstico por IA

**[CONFIRMADO NO BANCO]**

O template inicial de IA determina:

- não emitir diagnóstico;
- não substituir acompanhamento psicológico;
- não gerar análise antes de revisão e aprovação.

O template está:

```text
pending
active = false
```

### 9.3 Funcionalidades ainda não confirmadas

Embora o banco tenha estruturas para expansão, não foram confirmadas como disponíveis:

- Canvas gráfico com itens e coordenadas;
- setas e quadrantes;
- upload de arquivos;
- base de conhecimento;
- análise ativa por IA;
- vector store;
- administração completa de usuários;
- acesso de paciente;
- painel de auditor.

---

## 10. Arquitetura operacional resumida

```text
Navegador
→ Frontend React
→ API PHP em /api
→ Controllers
→ Services
→ Repositories
→ MySQL/MariaDB
```

O frontend e a API utilizam o mesmo domínio em produção.

A aplicação é publicada na Hostinger com:

```text
index.html
assets/
api/
```

O código-fonte fica na branch `main` e a versão preparada para produção fica na branch `deploy`.

---

## 11. Ambientes e branches

### `main`

Contém:

```text
backend/
frontend/
docs/
migrations/
arquivos de desenvolvimento
```

### `deploy`

Contém:

```text
index.html
assets/
api/
.htaccess
```

**[INFORMADO PELA RESPONSÁVEL]**

Não deve ser feito merge direto de `main` em `deploy`.

O conteúdo é preparado manualmente após build e validação.

---

## 12. Funcionalidades previstas para evolução

**[CONFIRMADO NO BANCO, MAS NÃO CONFIRMADO NA APLICAÇÃO]**

O schema prevê expansão para:

- itens visuais no mapa;
- setas;
- quadrantes;
- coordenadas;
- notas clínicas;
- arquivos vinculados;
- base de conhecimento;
- templates de prompt;
- revisão clínica de prompts;
- análises de IA versionadas;
- guardrails;
- rastreabilidade do modelo e prompt utilizados;
- logs de processamento externo.

Esses recursos devem passar por análise funcional, clínica, jurídica e de segurança antes da ativação.

---

## 13. Pontos de atenção atuais

### Alta prioridade

1. corrigir o filtro de mapas arquivados;
2. impedir status `archived` na edição comum de pacientes e mapas;
3. preservar o nome do paciente arquivado no detalhe do mapa;
4. definir o comportamento oficial de mapas arquivados;
5. revisar juridicamente o termo de consentimento;
6. documentar backup e restauração;
7. definir política de retenção dos dados;
8. validar privilégios mínimos no banco.

### Média prioridade

1. padronizar respostas da API;
2. validar o schema completo do Canvas;
3. criar rotina de limpeza de tokens expirados;
4. monitorar falhas de auditoria;
5. invalidar sessões após redefinição de senha;
6. revisar índices duplicados;
7. ampliar testes automatizados;
8. implementar observabilidade.

---

## 14. Escopo atual consolidado

**[CONFIRMADO]**

A versão atual do Mapa da Psiquê contempla:

```text
autenticação
recuperação de senha
consentimento
Dashboard
pacientes
mapas
Canvas textual
histórico de versões
restauração com backup
exportação PDF
auditoria
soft delete
isolamento por proprietário
```

**[NÃO CONFIRMADO COMO ATIVO]**

```text
Canvas gráfico
arquivos
base de conhecimento
análise por IA
painéis específicos para administrador, paciente e auditor
```

---

## 15. Resumo executivo

O **Mapa da Psiquê** é uma aplicação web voltada à organização de pacientes, mapas e registros reflexivos estruturados.

A solução oferece autenticação, consentimento, isolamento por profissional, soft delete, histórico de versões, restauração segura, exportação em PDF e auditoria.

O sistema já possui uma base arquitetural preparada para futuras evoluções, especialmente em Canvas gráfico, arquivos e inteligência artificial. Entretanto, essas estruturas ainda não devem ser consideradas funcionalidades disponíveis.

A prioridade atual é consolidar a documentação, corrigir inconsistências de arquivamento, formalizar requisitos de segurança e LGPD, definir backup e retenção e ampliar testes e observabilidade.

---

## 16. Histórico do documento

| Versão | Data | Descrição |
|---|---|---|
| 0.1 | 10/07/2026 | Primeira versão da visão geral do sistema |
