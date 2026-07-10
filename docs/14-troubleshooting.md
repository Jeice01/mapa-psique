# Troubleshooting — Mapa da Psiquê

## 1. Site não abre

Verifique:

```bash
curl -I https://mapapsique.orbisconect.com/
```

Confirme `index.html`, `.htaccess`, permissões e branch `deploy`.

## 2. API retorna 404

```bash
curl -i https://mapapsique.orbisconect.com/api/health
```

Verifique:

```text
api/index.php
api/.htaccess
api/_app/public/index.php
api/_app/public/.htaccess
```

O arquivo `api/index.php` deve conter:

```php
<?php

require __DIR__ . '/_app/public/index.php';
```

## 3. Erro 500 na API

- validar sintaxe com `php -l`;
- conferir `.env`;
- conferir logs;
- testar conexão com banco;
- conferir arquivos copiados para `api/_app/src`.

```bash
php -l api/_app/public/index.php
```

## 4. Banco indisponível

- conferir `DB_HOST`, `DB_PORT`, `DB_DATABASE`/`DB_NAME`, `DB_USERNAME`/`DB_USER`, `DB_PASSWORD`/`DB_PASS`;
- validar usuário e privilégios;
- usar `/api/db-check` somente em diagnóstico controlado.

## 5. Sessão não persiste

- confirmar HTTPS;
- `APP_ENV=production`;
- domínio e caminho do cookie;
- `credentials: include` no frontend;
- permissões e configuração de sessões do PHP.

## 6. CSRF 419

- buscar novo token em `/api/csrf-token`;
- enviar `X-CSRF-Token`;
- preservar o cookie da mesma sessão;
- observar conflito entre abas ou requisições paralelas.

## 7. CORS ou preflight

- validar `APP_ALLOWED_ORIGINS`;
- confirmar método e cabeçalhos permitidos;
- testar resposta `OPTIONS`.

## 8. Rate limit 429

- aguardar a janela expirar;
- verificar arquivos em `storage/temp/rate-limit`;
- não apagar o diretório durante deploy;
- investigar automações ou loops no cliente.

## 9. Login falha

- conferir usuário ativo;
- verificar hash da senha;
- confirmar conexão com banco;
- observar auditoria `auth.login.failed` sem expor senha ou e-mail em texto puro.

## 10. E-mail de redefinição não chega

- validar configuração de e-mail;
- conferir logs do `Mailer`;
- verificar spam;
- confirmar `APP_URL`;
- confirmar validade de uma hora do token.

## 11. Paciente arquivado não aparece

Use o filtro `archived`. O filtro `Todos` inclui todos os status na implementação analisada.

## 12. Paciente arquivado não abre em detalhe

A consulta individual exclui registros com `deleted_at`. Esse comportamento está documentado como ponto pendente de validação funcional.

## 13. Nome do paciente some no detalhe do mapa

A consulta detalhada exclui pacientes arquivados no `JOIN`. Correção recomendada: preservar o nome e o status em leitura.

## 14. Filtro de mapas arquivados vazio

Problema confirmado: a consulta combina `status=archived` com `deleted_at IS NULL`. Requer correção no `MapRepository`.

## 15. Canvas não salva

- conferir token CSRF;
- validar JSON;
- conferir status do mapa;
- mapa arquivado não pode ser atualizado;
- consultar logs e resposta HTTP.

## 16. Restauração de versão falha

- confirmar que o mapa não está arquivado;
- confirmar a versão e propriedade;
- verificar transação e restrição única de versões;
- evitar operações simultâneas.

## 17. PDF não baixa

- testar endpoint autenticado;
- verificar `MapPdfExporter`;
- conferir `Content-Type` e `Content-Disposition`;
- verificar dados incompatíveis no Canvas.

## 18. `git pull --ff-only` falha

```bash
git status
git branch --show-current
git log --oneline -5
```

Não usar `reset --hard` sem preservar `.env`, uploads e runtime.

## 19. `.env` aparece como untracked

No servidor, manter em `.git/info/exclude` e confirmar:

```bash
test -s api/_app/.env && echo OK || echo ERRO
```

## 20. Escalonamento

Para incidente grave:

1. preservar logs e evidências;
2. limitar acesso;
3. interromper deploys;
4. registrar horário e impacto;
5. avaliar dados pessoais afetados;
6. executar rollback ou contenção validada;
7. documentar causa e correção.
