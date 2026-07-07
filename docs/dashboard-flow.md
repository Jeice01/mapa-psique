# Dashboard, Pacientes e Mapas

## Rotas

Dashboard:

- `GET /api/dashboard/summary`

Pacientes:

- `GET /api/patients`
- `POST /api/patients`
- `GET /api/patients/{id}`
- `PUT /api/patients/{id}`
- `DELETE /api/patients/{id}`

Mapas:

- `GET /api/maps`
- `POST /api/maps`
- `GET /api/maps/{id}`
- `PUT /api/maps/{id}`
- `DELETE /api/maps/{id}`

## Segurança

Todas as rotas exigem autenticação. Dashboard, pacientes e mapas também exigem consentimento ativo aceito.

Métodos mutáveis exigem `X-CSRF-Token`.

## Ownership

Pacientes e mapas sempre são filtrados por `owner_user_id`. Um profissional não deve listar, abrir, editar ou arquivar registros de outro profissional.

## RBAC

- `profissional`: gerencia próprios pacientes e mapas.
- `administrador`: acessa resumo básico nesta etapa.
- `paciente`: negado para rotas administrativas clínicas.
- `auditor`: negado para conteúdo clínico.

## Auditoria

Eventos registrados:

- `dashboard.summary.viewed`
- `patient.created`
- `patient.updated`
- `patient.archived`
- `patient.viewed`
- `patient.listed`
- `map.created`
- `map.updated`
- `map.archived`
- `map.viewed`
- `map.listed`

Metadados devem conter apenas IDs, rota, método e status. Não logar `notes`, `reason`, conteúdo clínico, prompts ou respostas de IA.

## Fora do Escopo

Ainda não há canvas interativo, upload, OpenAI, PDF, relatório clínico, itens do mapa ou setas.
