# Módulos — Mapa da Psiquê

> Status: primeira versão consolidada.

## 1. Autenticação

Responsabilidades:

- cadastro de profissional;
- login;
- consulta da sessão atual;
- logout;
- recuperação e redefinição de senha;
- emissão e validação de CSRF;
- auditoria de eventos de acesso.

Arquivos principais:

```text
backend/src/Modules/Auth/AuthController.php
backend/src/Modules/Auth/AuthService.php
backend/src/Modules/Auth/AuthMiddleware.php
backend/src/Modules/Auth/RoleMiddleware.php
backend/src/Security/SessionManager.php
backend/src/Security/PasswordHasher.php
backend/src/Security/Csrf.php
frontend/src/modules/auth/
```

## 2. Consentimentos

Responsabilidades:

- localizar termo ativo;
- apresentar termo;
- registrar aceite;
- impedir acesso à área protegida sem aceite vigente.

Arquivos:

```text
backend/src/Modules/Consents/
backend/src/Database/Repositories/ConsentRepository.php
frontend/src/modules/consents/ConsentPage.tsx
```

**[PENDENTE]** Documentar revogação, múltiplos termos e evidências completas após análise específica.

## 3. Dashboard

Responsabilidades:

- contar pacientes;
- contar mapas não arquivados;
- contar mapas em rascunho;
- contar mapas analisados.

Arquivos:

```text
backend/src/Modules/Dashboard/
frontend/src/modules/dashboard/
```

## 4. Pacientes

Responsabilidades:

- criação;
- listagem;
- busca por nome e código interno;
- filtro por status;
- consulta;
- atualização;
- arquivamento;
- reativação;
- isolamento por proprietário;
- auditoria.

Status:

```text
active
inactive
archived
```

Arquivos:

```text
backend/src/Modules/Patients/
backend/src/Database/Repositories/PatientRepository.php
frontend/src/modules/patients/
```

## 5. Mapas

Responsabilidades:

- criação;
- vínculo opcional com paciente;
- listagem e filtros;
- consulta;
- atualização;
- status;
- arquivamento;
- Canvas;
- versões;
- PDF;
- auditoria.

Status:

```text
draft
ready_for_analysis
analyzed
archived
```

Arquivos:

```text
backend/src/Modules/Maps/
backend/src/Database/Repositories/MapRepository.php
frontend/src/modules/maps/
```

## 6. Canvas

Campos confirmados:

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

O Canvas é armazenado em `maps.canvas_json`.

Cada salvamento gera um snapshot histórico.

## 7. Versões do Canvas

Responsabilidades:

- numerar versões por mapa;
- guardar conteúdo integral;
- listar histórico;
- consultar versão;
- restaurar versão;
- criar backup antes da restauração;
- exportar versão em PDF.

Tabela:

```text
map_canvas_versions
```

## 8. PDF

Responsabilidades:

- gerar PDF do mapa atual;
- gerar PDF de versão histórica;
- enviar resposta binária;
- sugerir nome de arquivo;
- registrar auditoria da exportação.

Arquivos:

```text
backend/src/Modules/Maps/MapPdfExporter.php
backend/src/Http/BinaryResponse.php
```

## 9. Auditoria

Responsabilidades:

- registrar ator;
- ação;
- entidade;
- rota;
- método;
- status;
- IP;
- user agent;
- metadados.

Tabela protegida por triggers append-only:

```text
audit_logs
```

## 10. Infraestrutura HTTP

### Router
Mapeia método e caminho para controllers.

### CORS
Controla origens autorizadas e preflight.

### Security Headers
Adiciona headers de proteção.

### Rate Limit
Limita requisições por cliente e rota.

### Respostas

```text
JsonResponse
BinaryResponse
ResponseInterface
```

## 11. Módulos adicionais

Ativos:

- upload autenticado de imagem por mapa;
- geração assistida do canvas por visão;
- análise textual por OpenAI com fallback Anthropic;
- relatório simplificado para o paciente;
- infográfico gerado por IA;
- persistência de modelo, status, prompt de imagem e erro técnico.

Previstos ou incompletos:

- itens e setas como entidades estruturadas;
- notas estruturadas independentes;
- base de conhecimento e materiais;
- template de prompt versionado governando o código efetivo;
- aprovação clínica formal e rastreabilidade da revisão humana;
- política de retenção e expurgo de arquivos e análises.

## 12. Dependências entre módulos

```text
Auth → Users, Sessions, Audit
Consents → Users, Consent Terms
Dashboard → Patients, Maps
Patients → Users, Audit
Maps → Patients, Versions, PDF, Audit
Versions → Maps, Users
AI Analysis → Maps, OpenAI, Anthropic, Uploads
```

## 13. Pendências

- detalhar módulo de consentimentos;
- detalhar PDF;
- confirmar rate limit;
- confirmar logs;
- definir módulos futuros que serão mantidos no roadmap;
- separar claramente funcionalidade pronta de estrutura apenas prevista.
