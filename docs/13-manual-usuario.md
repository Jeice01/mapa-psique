# Manual do Usuário — Mapa da Psiquê

> Público principal confirmado: profissional autenticado.

## 1. Acesso ao sistema

Produção:

```text
https://mapapsique.orbisconect.com
```

Ao abrir o sistema, a aplicação verifica se existe sessão válida.

Possibilidades:

- sessão ausente: tela de login;
- redefinição de senha: tela específica pelo token da URL;
- consentimento pendente: tela de aceite;
- sessão válida e consentimento aceito: área protegida.

## 2. Cadastro

Na tela de cadastro:

1. informe nome;
2. informe e-mail válido;
3. crie senha com ao menos 8 caracteres, uma letra e um número;
4. envie o formulário.

O perfil criado é `profissional`.

## 3. Login

1. informe e-mail e senha;
2. clique para entrar;
3. aceite o termo vigente quando solicitado.

Mensagens de credencial inválida não informam se o e-mail existe.

## 4. Recuperação de senha

1. selecione a opção de esquecimento de senha;
2. informe seu e-mail;
3. consulte a caixa de entrada;
4. use o link em até 1 hora;
5. defina nova senha.

A mensagem de solicitação é genérica por segurança.

## 5. Dashboard

O dashboard apresenta indicadores como:

```text
quantidade de pacientes
quantidade de mapas
mapas em rascunho
mapas analisados
```

Registros arquivados por soft delete não entram nas contagens confirmadas de mapas.

## 6. Pacientes

### 6.1 Listar

A tela permite:

- visualizar pacientes;
- buscar por nome;
- buscar por código interno;
- filtrar por status;
- navegar por páginas.

Status:

```text
Ativo
Inativo
Arquivado
```

### 6.2 Cadastrar

Campos identificados:

```text
nome
código interno
idade
observações
status
```

Regras:

- nome obrigatório;
- idade entre 0 e 120, quando informada;
- novo paciente é criado como ativo.

### 6.3 Editar

Abra o paciente e altere os campos permitidos.

Paciente arquivado corretamente por soft delete não deve ser editado.

### 6.4 Arquivar

Use a ação de arquivamento e confirme.

O paciente:

- não é apagado fisicamente;
- passa ao status arquivado;
- mantém seus mapas;
- não pode receber novo mapa.

### 6.5 Reativar

No filtro de arquivados, use a ação de reativação.

Após reativar:

- o status retorna a ativo;
- novos vínculos e operações voltam a ser permitidos.

## 7. Mapas

### 7.1 Listar

A listagem permite:

- buscar pelo título;
- filtrar por status;
- filtrar por paciente;
- abrir detalhes.

Status:

```text
Rascunho
Pronto para análise
Analisado
Arquivado
```

### 7.2 Criar

1. selecione a ação de novo mapa;
2. informe título;
3. selecione paciente, quando aplicável;
4. informe o motivo;
5. salve.

O mapa nasce como rascunho e com Canvas vazio.

Não é permitido criar mapa para paciente arquivado.

### 7.3 Editar dados do mapa

É possível alterar campos permitidos, como título, paciente, motivo e status operacional.

Não associe o mapa a paciente arquivado.

## 8. Canvas

O Canvas contém nove áreas:

1. Queixa ou demanda principal;
2. Contexto de vida atual;
3. História emocional relevante;
4. Padrões recorrentes;
5. Crenças centrais;
6. Estratégias de proteção ou defesa;
7. Potenciais e recursos internos;
8. Hipóteses reflexivas;
9. Próximos passos.

### 8.1 Salvamento

- edite os campos;
- observe o indicador de alterações não salvas;
- clique em **Salvar canvas**;
- aguarde a confirmação.

Cada salvamento cria uma nova versão histórica.

## 9. Histórico do Canvas

A seção de histórico permite:

- listar versões;
- filtrar snapshots e backups;
- abrir prévia somente leitura;
- exportar uma versão em PDF;
- restaurar uma versão.

### 9.1 Restaurar versão

1. abra os detalhes da versão;
2. revise o conteúdo;
3. clique em restaurar;
4. digite exatamente `RESTAURAR`;
5. confirme.

Antes de substituir o Canvas atual, o sistema cria backup automático.

## 10. Exportação PDF

No Canvas atual, use **Exportar PDF**.

No histórico, use **Exportar PDF** na versão desejada.

O arquivo histórico inclui número, data e resumo da versão.

## 11. Arquivamento de mapas

Mapas arquivados não podem ser atualizados.

**[PENDENTE DE VALIDAÇÃO FUNCIONAL]** O código permite consulta direta, histórico e PDF de mapa arquivado, mas a apresentação oficial desse modo somente leitura ainda deve ser validada.

## 12. Logout

Use a ação de sair. O sistema remove e destrói a sessão atual.

## 13. Boas práticas de uso

- não compartilhe credenciais;
- bloqueie o computador ao se ausentar;
- não use dados além do necessário;
- revise o paciente antes de criar o mapa;
- confirme alterações salvas;
- use hipóteses reflexivas sem diagnóstico automático;
- preserve a confidencialidade dos PDFs exportados;
- não envie PDFs clínicos por canais inseguros.

## 14. Limitações atuais

Não estão confirmados como funcionalidades ativas:

```text
perfil do paciente
perfil do auditor
administração de usuários
upload de arquivos
análise por IA
Canvas gráfico com itens e setas
```

Este manual deve ser revisado visualmente na interface antes da versão final.
