# Segurança, privacidade e LGPD — Mapa da Psiquê

## 1. Escopo e classificação

O sistema trata dados identificáveis, imagens, anotações e inferências relacionadas à saúde e ao estado psíquico. Esses conteúdos devem ser tratados como dados pessoais sensíveis.

Este documento descreve o estado técnico do projeto. Ele não substitui validação jurídica, clínica ou contratual.

## 2. Dados tratados

- conta do profissional: nome, e-mail, perfil, status e credenciais protegidas;
- dados do paciente: nome, código interno, idade e observações;
- mapas: motivo, canvas, versões históricas, imagens e PDFs;
- IA: prompts, imagem do mapa, análise profissional, relatório do paciente, resumo, modelos utilizados e infográfico;
- segurança e auditoria: IP, user agent, rota, método, IDs, status, sessões, consentimentos e eventos;
- operação: logs, erros técnicos, tokens de redefinição e arquivos temporários.

## 3. Controles implementados

- sessão PHP com regeneração de ID após login;
- cookie `HttpOnly`, `SameSite=Lax` e `Secure` em produção;
- expiração lógica da sessão;
- Argon2id com fallback para bcrypt;
- CSRF em operações mutáveis;
- CORS por allowlist e credenciais;
- prepared statements via PDO;
- RBAC e isolamento por `owner_user_id`;
- soft delete de pacientes e mapas;
- rate limit inicial por IP e rota;
- headers de segurança e CSP;
- auditoria append-only no banco;
- redação recursiva de chaves sensíveis conhecidas nos logs;
- uploads servidos somente por endpoint autenticado e com `Cache-Control: no-store`;
- bloqueio HTTP de `api/_app`, `.git`, `.env`, logs e arquivos operacionais;
- chaves de IA mantidas fora do Git.

## 4. Consentimento e base legal

O sistema possui termo versionado e registro de aceite do usuário autenticado. A versão inicial do termo permanece marcada como sujeita à validação jurídica.

Limites atuais:

- o aceite do profissional não representa automaticamente o consentimento do paciente;
- não há vínculo explícito entre paciente, finalidade e base legal;
- não há endpoint de revogação, embora o repositório suporte mudança para `revoked`;
- o termo não detalha suficientemente provedores, transferência internacional, retenção e eliminação;
- consentimento não deve ser tratado como base automática para todas as finalidades.

Antes de ampliar o uso em produção, definir controlador, operadores, encarregado ou canal equivalente, finalidades, bases legais e responsabilidades. Submeter o termo e o fluxo à validação jurídica.

## 5. Integração com IA

O fluxo atual pode enviar nome do paciente, motivo, observações, canvas e imagem do mapa à OpenAI. Se a geração textual falhar, o mesmo conteúdo textual pode ser enviado à Anthropic.

Requisitos obrigatórios para evolução:

- aplicar minimização e pseudonimização antes de cada chamada;
- não enviar nome, código interno ou identificadores desnecessários;
- informar ao usuário qual provedor processará os dados;
- documentar retenção padrão, subprocessadores e transferência internacional;
- avaliar contratos de tratamento e controles de retenção reduzida ou zero;
- registrar provedor, modelo e versão do prompt sem registrar conteúdo clínico bruto;
- manter saída como rascunho até revisão e aprovação humana;
- permitir correção e contestação;
- avaliar qualidade, vieses, falsos diagnósticos e linguagem determinista;
- impedir que a IA substitua avaliação profissional.

O prompt efetivamente usado é definido em código. O template de banco marcado como pendente e inativo não governa esse prompt; essa divergência deve ser eliminada.

## 6. Uploads

Controles atuais:

- autenticação, papel profissional, consentimento ativo e ownership;
- CSRF;
- limite de 10 MB;
- MIME detectado no arquivo temporário;
- aceitação de JPEG, PNG, WebP e GIF;
- nome final derivado de ID sanitizado;
- diretório interno bloqueado por `.htaccess`;
- resposta de download autenticada e sem cache.

Melhorias necessárias:

- remover GIF da lista ou justificar sua necessidade;
- decodificar e regravar a imagem em formato seguro;
- remover EXIF e metadados;
- validar dimensões e impor limites de pixels;
- aplicar quota por usuário e paciente;
- repetir `owner_user_id` na atualização do caminho da imagem;
- apagar o arquivo anterior quando houver substituição;
- apagar ou anonimizar arquivos conforme política de retenção;
- avaliar antimalware quando o ambiente permitir.

## 7. Retenção, eliminação e direitos do titular

Não há política implementada de retenção ou expurgo. Soft delete não equivale a eliminação: registros, imagens, versões, análises e backups podem continuar armazenados.

Definir prazos e fundamentos para:

- contas e sessões;
- pacientes e mapas;
- canvas e versões;
- imagens originais e infográficos;
- análises, relatórios e prompts;
- consentimentos e revogações;
- auditoria e logs;
- tokens expirados;
- backups.

Implementar processo autenticado para confirmação de tratamento, acesso, correção, portabilidade quando aplicável, revogação, anonimização, bloqueio e eliminação nos casos legalmente cabíveis.

## 8. Logs, erros e auditoria

Não registrar senha, token, chave, imagem, prompt, resposta de IA, observações, motivo da consulta ou canvas bruto.

Mensagens devolvidas por provedores externos não devem ser persistidas diretamente. Gravar apenas código interno, categoria, provedor, modelo, status e identificador de correlação.

Definir rotação, retenção, acesso e descarte seguro de logs. A característica append-only da auditoria deve ser conciliada com a política de retenção e as obrigações legais.

## 9. Resposta a incidentes

Criar runbook com:

- canal de comunicação e responsáveis;
- classificação e contenção;
- preservação de evidências;
- avaliação de risco ou dano relevante;
- comunicação à ANPD e aos titulares quando aplicável;
- revogação e rotação de credenciais;
- registro do incidente pelo prazo regulamentar;
- lições aprendidas e ações corretivas.

## 10. Checklist antes de ampliar o uso

- [ ] termo e bases legais validados;
- [ ] paciente titular identificado no fluxo de privacidade;
- [ ] inventário e registro das operações de tratamento;
- [ ] contratos e transferência internacional revisados;
- [ ] pseudonimização antes da IA;
- [ ] revisão humana obrigatória;
- [ ] retenção e eliminação implementadas;
- [ ] uploads normalizados e metadados removidos;
- [ ] direitos do titular operacionais;
- [ ] runbook de incidentes aprovado e testado;
- [ ] testes de segurança, acesso e integração executados;
- [ ] backups criptografados e restauração testada.
