# Roadmap — Mapa da Psiquê

## 1. Objetivo

Registrar melhorias identificadas durante a análise técnica. Este documento não representa promessa de prazo.

## 2. Prioridade alta

- impedir `archived` em atualizações comuns de pacientes e mapas;
- corrigir filtro de mapas arquivados;
- preservar nome e status do paciente arquivado no detalhe do mapa;
- definir comportamento oficial de mapas arquivados;
- revisar juridicamente o termo de consentimento;
- formalizar backup e restauração;
- validar privilégios mínimos do usuário MySQL;
- definir retenção de dados, auditoria e versões;
- revisar exposição de `/api/db-check`.

## 3. Concluído recentemente

- corrigidos os arquivos PHP truncados da análise por IA;
- ESLint e build do frontend validados;
- árvore de trabalho limpa e revisada;
- workflow de deploy restaurado e validado na Hostinger;
- CI básico criado para frontend e backend;
- validação Composer estrita estabilizada e 12 testes de segurança e acesso integrados ao CI;
- upload de imagem, geração assistida do canvas, exportação PDF e análise por IA implementados;
- migrations 005, 006 e 007 incorporadas ao projeto.

## 4. Qualidade e testes

- testes unitários para services e sanitizadores;
- testes de integração com MySQL/MariaDB;
- testes de autorização e isolamento por owner;
- testes de CSRF e sessão;
- testes de arquivamento e restauração;
- testes de versionamento concorrente;
- testes de PDF;
- testes E2E dos fluxos principais.

## 5. Segurança

- invalidar sessões após redefinição de senha;
- melhorar política de senha;
- avaliar MFA;
- controlar múltiplos tokens CSRF ou concorrência entre abas;
- monitorar falhas de auditoria;
- revisar CORS, CSP e rate limit;
- implementar gestão de incidentes;
- revisar criptografia em repouso e backups;
- automatizar análise de dependências.

## 6. Banco de dados

- revisar índices duplicados;
- documentar rollback de migrations;
- tornar migrations futuras idempotentes quando aplicável;
- avaliar data de nascimento no lugar de idade;
- validar unicidade de `internal_code` por profissional;
- tratar concorrência de `version_number`;
- criar campo explícito `version_type`;
- definir limpeza de tokens expirados.

## 7. API

- padronizar envelopes e erros;
- documentar contratos com OpenAPI;
- retornar mensagens funcionais seguras;
- centralizar validação e tratamento de exceções;
- incluir request/correlation ID;
- definir versionamento da API.

## 8. Frontend e UX

- roteamento explícito;
- mensagens de erro mais específicas;
- modo somente leitura para arquivados;
- alerta para Canvas incompatível;
- confirmação e feedback acessíveis;
- testes de acessibilidade;
- recuperação de estado e prevenção de perda não salva.

## 9. Operação e observabilidade

- monitoramento de disponibilidade;
- logs centralizados;
- alertas de erro e segurança;
- métricas de API e banco;
- rotação e retenção de logs;
- rotina de backup com teste de restauração;
- health check seguro;
- runbook operacional.

## 10. CI/CD

- ampliar o CI com testes de integração MySQL/MariaDB;
- executar testes antes da publicação;
- gerar artefato imutável;
- validar arquivos sensíveis;
- criar homologação separada;
- implantar aprovação manual para produção;
- automatizar smoke tests e rollback controlado.

## 11. LGPD e governança

- inventário de dados;
- bases legais e finalidades;
- política de retenção;
- direitos dos titulares;
- processo de incidente;
- registro de operadores e terceiros;
- revisão jurídica dos termos;
- governança de IA e revisão clínica.
- pseudonimização antes do envio aos provedores de IA;
- contratos, subprocessadores e transferência internacional;
- normalização, expurgo e remoção de metadados dos uploads;
- plano e teste de resposta a incidentes.

## 12. Funcionalidades implementadas com governança pendente

Já existem no produto, mas exigem validação e controles adicionais:

- canvas e versões históricas;
- arquivos de mapas;
- análises e relatórios por IA;
- infográficos gerados por IA;
- prompts clínicos definidos em código.

Ainda não implementado:

- gestão de materiais e base de conhecimento;
- templates clínicos versionados governando o prompt efetivo;
- guardrails, aprovação humana formal e rastreabilidade clínica completa.

Cada evolução deve ter requisitos, análise de risco, testes, autorização, retenção e documentação próprios.
