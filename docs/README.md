# Documentação — Mapa da Psiquê

Este diretório reúne a documentação técnica, funcional, operacional e de segurança do sistema.

## Documentos principais

1. [Visão geral](01-visao-geral.md)
2. [Arquitetura](02-arquitetura.md)
3. [Estrutura do projeto](03-estrutura-projeto.md)
4. [Requisitos](04-requisitos.md)
5. [Módulos](05-modulos.md)
6. [Regras de negócio](06-regras-negocio.md)
7. [API](07-api.md)
8. [Banco de dados](08-banco-de-dados.md)
9. [Segurança e LGPD](09-seguranca-lgpd.md)
10. [Desenvolvimento local](10-desenvolvimento-local.md)
11. [Deploy na Hostinger](11-deploy-hostinger.md)
12. [Operação e manutenção](12-operacao-manutencao.md)
13. [Manual do usuário](13-manual-usuario.md)
14. [Troubleshooting](14-troubleshooting.md)
15. [Roadmap](15-roadmap.md)

## Classificação das informações

Os documentos podem usar as seguintes marcações:

- **[CONFIRMADO NO CÓDIGO]**: comprovado nos arquivos analisados;
- **[CONFIRMADO NO BANCO]**: comprovado nas migrations;
- **[CONFIRMADO EM PRODUÇÃO]**: validado no ambiente publicado;
- **[INFORMADO PELA RESPONSÁVEL]**: informação fornecida pela responsável pelo sistema;
- **[PENDENTE DE VALIDAÇÃO]**: ainda requer comprovação;
- **[RECOMENDAÇÃO]**: melhoria proposta, ainda não implementada.

## Estado atual

A documentação representa o estado conhecido em 10/07/2026. Ela deve ser atualizada sempre que houver alteração relevante em arquitetura, regras de negócio, API, banco, segurança, deploy ou operação.

## Regra de manutenção

Toda mudança funcional relevante deve avaliar a necessidade de atualização em pelo menos:

```text
05-modulos.md
06-regras-negocio.md
07-api.md
08-banco-de-dados.md
09-seguranca-lgpd.md
11-deploy-hostinger.md
13-manual-usuario.md
15-roadmap.md
```

Não registrar segredos, senhas, tokens, chaves privadas ou valores reais do arquivo `.env` na documentação.