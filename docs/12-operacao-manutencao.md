# Operação e Manutenção — Mapa da Psiquê

## 1. Objetivo

Registrar rotinas operacionais, saúde da aplicação, logs, banco, backups, segurança e manutenção preventiva.

## 2. Verificações de saúde

### Site

```bash
curl -I https://mapapsique.orbisconect.com/
```

### API

```bash
curl -i https://mapapsique.orbisconect.com/api/health
```

### Banco

```bash
curl -i https://mapapsique.orbisconect.com/api/db-check
```

**[RECOMENDAÇÃO]** O endpoint de banco deve retornar apenas o mínimo necessário e, preferencialmente, ser restrito a contexto operacional.

## 3. Logs

Diretório local identificado:

```text
backend/storage/logs/
```

Produção esperada:

```text
api/_app/storage/logs/
```

**[PENDENTE DE VALIDAÇÃO]** Confirmar:

- nome dos arquivos;
- formato;
- rotação;
- retenção;
- permissões;
- volume máximo;
- presença de dados pessoais.

Nunca registrar:

- senhas;
- tokens de redefinição;
- cookies de sessão;
- segredos de ambiente;
- conteúdo clínico completo sem necessidade.

## 4. Auditoria

A tabela `audit_logs` é append-only por triggers de banco.

Rotinas recomendadas:

- revisar eventos `WARN`, `ERROR` e `CRITICAL`;
- acompanhar tentativas de login;
- acompanhar respostas 401, 403 e 419;
- verificar falhas de exportação;
- verificar falhas de restauração;
- investigar aumentos incomuns de rate limit.

## 5. Backup

**[PENDENTE DE VALIDAÇÃO]** A rotina real da Hostinger ainda precisa ser documentada.

O backup deve incluir:

```text
banco MySQL/MariaDB
api/_app/.env de forma protegida
api/_app/storage/uploads/
configurações relevantes
commit/tag correspondente da aplicação
```

O `.env` não deve ser incluído em repositório ou backup sem criptografia.

## 6. Restauração

Uma restauração deve ser testada periodicamente em ambiente separado.

Checklist:

1. confirmar ponto de recuperação;
2. preservar estado atual antes de restaurar;
3. restaurar banco;
4. restaurar uploads;
5. conferir variáveis de ambiente;
6. validar migrations;
7. testar login e API;
8. verificar pacientes, mapas e versões;
9. registrar responsável, data e resultado.

## 7. Manutenção do banco

Rotinas recomendadas:

- monitorar tamanho das tabelas;
- revisar índices duplicados;
- limpar tokens expirados conforme política;
- monitorar crescimento de `audit_logs`;
- monitorar crescimento de `map_canvas_versions`;
- executar `ANALYZE TABLE` quando apropriado;
- verificar integridade de chaves estrangeiras;
- revisar usuário e privilégios da aplicação.

Não apagar auditoria ou versões sem política aprovada.

## 8. Storage

Diretórios de runtime:

```text
storage/logs/
storage/temp/
storage/uploads/
```

Regras:

- não versionar;
- limitar permissões;
- impedir execução de scripts em uploads;
- validar tipo, tamanho e conteúdo de arquivos quando o upload for ativado;
- limpar temporários por rotina controlada;
- não apagar arquivos de rate limit durante deploy sem avaliação.

## 9. Atualização de dependências

Frontend:

```powershell
cd frontend
npm outdated
npm audit
```

Atualizações devem passar por:

```text
análise de impacto
lint
build
testes funcionais
homologação
release controlada
```

Backend atualmente não possui dependências externas obrigatórias confirmadas, mas `composer.json` e a versão do PHP devem ser revisados.

## 10. Segurança do SSH

Recomendações:

- utilizar chave SSH;
- proteger chave privada com senha;
- não compartilhar credenciais;
- restringir usuários;
- revisar chaves autorizadas;
- manter porta e host fora de documentação pública quando necessário;
- registrar e revogar acessos antigos.

## 11. Monitoramento

**[PENDENTE DE IMPLEMENTAÇÃO]** Recomenda-se monitorar:

- disponibilidade do site;
- latência da API;
- erros HTTP 5xx;
- uso de disco;
- uso do banco;
- falhas de e-mail;
- volume de logs;
- crescimento de histórico;
- falhas de backup;
- certificado TLS.

## 12. Manutenção preventiva

Periodicidade sugerida:

### Semanal

- testar `/api/health`;
- revisar erros recentes;
- conferir espaço em disco;
- confirmar backup.

### Mensal

- testar restauração parcial;
- revisar dependências;
- revisar acessos SSH;
- revisar eventos de auditoria;
- revisar crescimento do banco.

### Trimestral

- teste completo de restauração;
- revisão de segurança;
- revisão de retenção;
- atualização da documentação;
- avaliação de riscos LGPD.

## 13. Registro de mudanças

Cada manutenção relevante deve registrar:

```text
data
responsável
motivo
ambiente
arquivos afetados
comandos executados
resultado
rollback disponível
incidentes encontrados
```
