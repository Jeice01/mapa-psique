# Requisitos — Mapa da Psiquê

> Status: primeira versão. Requisitos derivados do código, banco e informações da responsável.

## 1. Requisitos funcionais confirmados

### RF-01 — Cadastro de usuário
O sistema deve permitir cadastro público de usuário com perfil `profissional`.

### RF-02 — Autenticação
O sistema deve autenticar usuário ativo por e-mail e senha.

### RF-03 — Recuperação de senha
O sistema deve permitir solicitação e redefinição de senha por token temporário.

### RF-04 — Consentimento
O sistema deve exigir aceite do termo ativo antes de liberar módulos protegidos.

### RF-05 — Dashboard
O sistema deve exibir totais de pacientes e mapas do profissional autenticado.

### RF-06 — Pacientes
O profissional deve poder criar, listar, pesquisar, consultar, editar, inativar, arquivar e reativar seus pacientes.

### RF-07 — Isolamento
O profissional deve acessar somente registros vinculados ao seu `owner_user_id`.

### RF-08 — Mapas
O profissional deve poder criar, listar, filtrar, consultar, editar e arquivar mapas próprios.

### RF-09 — Vínculo com paciente
O sistema deve permitir vínculo opcional entre mapa e paciente do mesmo profissional.

### RF-10 — Paciente arquivado
O sistema não deve permitir novo mapa ou novo vínculo para paciente arquivado.

### RF-11 — Canvas
O profissional deve poder preencher e salvar o Canvas textual estruturado.

### RF-12 — Histórico
Cada salvamento do Canvas deve gerar uma versão histórica.

### RF-13 — Restauração
O profissional deve poder restaurar uma versão, com backup automático do estado atual.

### RF-14 — PDF
O sistema deve exportar o mapa atual e versões históricas em PDF.

### RF-15 — Auditoria
O sistema deve registrar eventos relevantes de autenticação, pacientes, mapas, versões e PDF.

## 2. Requisitos funcionais pendentes ou futuros

- gestão administrativa de usuários;
- acesso específico para paciente e auditor;
- Canvas gráfico com itens e setas;
- upload e gestão de arquivos;
- notas clínicas estruturadas;
- análise por inteligência artificial;
- governança e aprovação de prompts;
- restauração de mapas arquivados;
- atendimento a solicitações de titulares LGPD.

## 3. Requisitos não funcionais

### RNF-01 — Segurança
- HTTPS em produção;
- senhas com Argon2id ou bcrypt;
- sessão com cookie HttpOnly;
- cookie Secure em produção;
- SameSite=Lax;
- CSRF em operações mutáveis;
- prepared statements;
- validação e sanitização;
- rate limiting;
- headers de segurança.

### RNF-02 — Privacidade
- isolamento por proprietário;
- consentimento versionado;
- soft delete;
- trilha de auditoria;
- proteção de dados psicológicos e de saúde.

### RNF-03 — Integridade
- transações no salvamento e restauração do Canvas;
- chaves estrangeiras;
- restrição única de versões;
- auditoria append-only.

### RNF-04 — Compatibilidade
- PHP 8.1 ou superior no código;
- PHP 8.3 em produção;
- MySQL/MariaDB;
- navegadores modernos;
- build frontend estático.

### RNF-05 — Desempenho
- paginação de listagens;
- limite máximo de 50 registros por página;
- índices para proprietário, status, datas e vínculos;
- respostas da API sem exposição de dados de outros usuários.

### RNF-06 — Manutenibilidade
- separação em camadas;
- módulos por domínio;
- migrations versionadas;
- documentação técnica no repositório;
- commits granulares.

### RNF-07 — Disponibilidade
**[PENDENTE]** Definir meta formal de disponibilidade, monitoramento e alertas.

### RNF-08 — Backup
**[PENDENTE]** Definir frequência, retenção, criptografia e testes de restauração.

### RNF-09 — Observabilidade
**[PENDENTE]** Definir métricas, logs centralizados, alertas e correlação por request ID.

## 4. Critérios de aceitação principais

- usuário não autenticado recebe 401 em recurso protegido;
- usuário sem consentimento recebe 403;
- perfil não autorizado recebe 403;
- CSRF inválido recebe 419;
- profissional não acessa paciente ou mapa de outro proprietário;
- paciente arquivado não pode receber novo mapa;
- restauração cria backup automático;
- mapa e versão são persistidos na mesma transação;
- registros arquivados permanecem no banco.

## 5. Restrições

- produção em Hostinger;
- publicação por branch `deploy`;
- frontend e API na mesma origem;
- arquivos `.env`, uploads e temporários não podem ser versionados;
- não deve haver merge direto de `main` em `deploy`.

## 6. Pendências de validação

- metas de tempo de resposta;
- volume esperado de usuários e registros;
- política de retenção;
- requisitos formais de acessibilidade;
- compatibilidade móvel oficial;
- requisitos de homologação;
- RPO e RTO;
- SLA e suporte;
- critérios jurídicos definitivos do consentimento.
