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

## 3. Qualidade e testes

- testes unitários para services e sanitizadores;
- testes de integração com MySQL/MariaDB;
- testes de autorização e isolamento por owner;
- testes de CSRF e sessão;
- testes de arquivamento e restauração;
- testes de versionamento concorrente;
- testes de PDF;
- testes E2E dos fluxos principais.

## 4. Segurança

- invalidar sessões após redefinição de senha;
- melhorar política de senha;
- avaliar MFA;
- controlar múltiplos tokens CSRF ou concorrência entre abas;
- monitorar falhas de auditoria;
- revisar CORS, CSP e rate limit;
- implementar gestão de incidentes;
- revisar criptografia em repouso e backups;
- automatizar análise de dependências.

## 5. Banco de dados

- revisar índices duplicados;
- documentar rollback de migrations;
- tornar migrations futuras idempotentes quando aplicável;
- avaliar data de nascimento no lugar de idade;
- validar unicidade de `internal_code` por profissional;
- tratar concorrência de `version_number`;
- criar campo explícito `version_type`;
- definir limpeza de tokens expirados.

## 6. API

- padronizar envelopes e erros;
- documentar contratos com OpenAPI;
- retornar mensagens funcionais seguras;
- centralizar validação e tratamento de exceções;
- incluir request/correlation ID;
- definir versionamento da API.

## 7. Frontend e UX

- roteamento explícito;
- mensagens de erro mais específicas;
- modo somente leitura para arquivados;
- alerta para Canvas incompatível;
- confirmação e feedback acessíveis;
- testes de acessibilidade;
- recuperação de estado e prevenção de perda não salva.

## 8. Operação e observabilidade

- monitoramento de disponibilidade;
- logs centralizados;
- alertas de erro e segurança;
- métricas de API e banco;
- rotação e retenção de logs;
- rotina de backup com teste de restauração;
- health check seguro;
- runbook operacional.

## 9. CI/CD

- automatizar lint e build;
- executar testes antes da publicação;
- gerar artefato imutável;
- validar arquivos sensíveis;
- criar homologação separada;
- implantar aprovação manual para produção;
- automatizar smoke tests e rollback controlado.

## 10. LGPD e governança

- inventário de dados;
- bases legais e finalidades;
- política de retenção;
- direitos dos titulares;
- processo de incidente;
- registro de operadores e terceiros;
- revisão jurídica dos termos;
- governança de IA e revisão clínica.

## 11. Funcionalidades previstas no banco

Antes de ativar:

- Canvas gráfico;
- itens, setas e notas;
- arquivos de mapas;
- base de conhecimento;
- análises por IA;
- templates versionados;
- guardrails e rastreabilidade.

Cada evolução deve ter requisitos, análise de risco, testes, autorização, retenção e documentação próprios.
