# Deploy na Hostinger — Mapa da Psiquê

> Atualizado em 11/07/2026: o deploy automático está ativo na branch `deploy`; o workflow precisa permanecer presente nessa própria branch. A `main` possui CI separado e aprovado para lint, build, Composer, sintaxe PHP e testes de segurança e acesso.

## 1. Ambientes e repositório

Repositório:

```text
https://github.com/Jeice01/mapa-psique.git
```

Produção:

```text
https://mapapsique.orbisconect.com
```

Diretório no servidor:

```text
/home/u754689460/domains/mapapsique.orbisconect.com/public_html
```

## 2. Branches

### `main`

Contém código-fonte e documentação:

```text
backend/
frontend/
docs/
.github/
README.md
```

### `deploy`

Contém os artefatos prontos para produção:

```text
index.html
assets/
api/
.htaccess
```

Não realizar merge direto de `main` em `deploy`.

## 3. Preparação na `main`

```powershell
git checkout main
git pull --ff-only origin main

git status

cd frontend
npm ci
npm run lint
npm run build
cd ..
```

Valide os arquivos PHP alterados:

```powershell
php -l backend\public\index.php
php -l backend\src\Modules\Maps\MapController.php
```

Adapte a lista aos arquivos modificados.

## 4. Preparação da `deploy`

```powershell
git checkout deploy
git pull --ff-only origin deploy
```

Copie os artefatos necessários:

```text
frontend/dist/index.html → index.html
frontend/dist/assets/ → assets/
backend/public/index.php → api/_app/public/index.php
backend/src/... → api/_app/src/...
```

O arquivo abaixo deve permanecer como ponto de entrada externo:

```text
api/index.php
```

Conteúdo esperado:

```php
<?php

require __DIR__ . '/_app/public/index.php';
```

Também preserve os arquivos `.htaccess` necessários.

## 5. Conferência antes do push

```powershell
git status
git diff --stat
git diff
```

Verifique especialmente se não houve remoção ou inclusão indevida de:

```text
api/_app/.env
api/_app/storage/uploads/
api/_app/storage/temp/rate-limit/
```

Commit e push:

```powershell
git add -A
git commit -m "deploy: publish approved build"
git push origin deploy
```

## 6. Atualização no servidor

Acesso SSH informado:

```text
ssh -p 65002 u754689460@82.25.67.163
```

No servidor:

```bash
cd /home/u754689460/domains/mapapsique.orbisconect.com/public_html

git status
git checkout deploy
git pull --ff-only origin deploy
```

## 7. Arquivos de runtime

Nunca devem ser versionados, sobrescritos ou apagados durante deploy:

```text
api/_app/.env
api/_app/storage/temp/rate-limit/*.json
api/_app/storage/uploads/
```

No servidor, são ignorados localmente por:

```text
.git/info/exclude
```

Valide após o pull:

```bash
test -s api/_app/.env \
  && echo ".env restaurado e não está vazio" \
  || echo "ERRO: .env ausente ou vazio"

ls -l api/_app/.env
```

## 8. Validações pós-deploy

### Sintaxe PHP

```bash
php -l api/_app/public/index.php
php -l api/_app/src/Modules/Maps/MapController.php
```

### Site

```bash
curl -I https://mapapsique.orbisconect.com/
```

### Saúde da API

```bash
curl -i https://mapapsique.orbisconect.com/api/health
```

### Banco

```bash
curl -i https://mapapsique.orbisconect.com/api/db-check
```

**[RECOMENDAÇÃO]** Restringir ou revisar a exposição pública de `/api/db-check` caso a resposta revele informações operacionais.

## 9. Testes funcionais mínimos

Após publicar:

1. abrir a tela de login;
2. autenticar com usuário de teste autorizado;
3. verificar dashboard;
4. listar e pesquisar pacientes;
5. criar ou editar registro de teste, quando apropriado;
6. abrir um mapa;
7. salvar Canvas;
8. verificar nova versão;
9. exportar PDF;
10. validar arquivamento e restauração de paciente quando a mudança envolver essas áreas.

Não utilizar dados clínicos reais em testes desnecessários.

## 10. Rollback

Identifique o commit anterior da `deploy`:

```bash
git log --oneline -10
```

Forma recomendada para preservar histórico:

```powershell
git checkout deploy
git pull --ff-only origin deploy
git revert <commit-do-deploy-com-problema>
git push origin deploy
```

Depois, no servidor:

```bash
git pull --ff-only origin deploy
```

**[PENDENTE DE VALIDAÇÃO]** Criar procedimento de rollback de banco quando houver migrations.

## 11. Diagnóstico de falhas

### `git pull` bloqueado

```bash
git status
```

Não use `git reset --hard` antes de confirmar que os arquivos de runtime estão protegidos e que não há alteração legítima no servidor.

### API 500

- validar sintaxe PHP;
- conferir `.env`;
- conferir permissões;
- verificar log técnico;
- testar conexão com banco;
- comparar arquivos publicados com a `deploy`.

### Frontend antigo

- confirmar novos hashes em `assets/`;
- confirmar `index.html` da build atual;
- limpar cache do navegador;
- conferir se o push foi feito na `deploy`.

## 12. Melhorias recomendadas

- automatizar cópia controlada dos artefatos;
- criar checklist versionado de release;
- criar ambiente de homologação;
- criar backup pré-deploy;
- adicionar smoke tests automáticos;
- documentar permissões de arquivos;
- não expor segredos em logs ou comandos compartilhados.
