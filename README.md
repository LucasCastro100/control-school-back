# control-school-back

API REST do sistema **Control School** — plataforma de gerenciamento de unidades escolares. Este é o backend em **Laravel** que concentra todo o domínio do negócio: escolas, usuários e cargos, turmas, salas, grades de horários, itens (tapetes/tecnologias) por NAP, agenda de atividades e os times TBR (categories/equipes por escola).

A API é consumida exclusivamente pelo frontend Next.js (`../control-school-front`) por meio de um proxy interno do Next. A autenticação usa **Laravel Sanctum** com token Bearer (`personal_access_tokens`), e o banco de dados em desenvolvimento é **SQLite** (arquivo `database/database.sqlite`), sem necessidade de servidor de banco externo.

## Stack

| Tecnologia | Versão | Observação |
|---|---|---|
| PHP | `^8.3` | requisito do `composer.json` |
| Laravel | `^13.17` (instalado: **13.32.0**) | framework |
| Laravel Sanctum | `^4.0` (instalado: 4.3.3) | autenticação por token Bearer |
| Laravel Tinker | `^3.0` | REPL |
| SQLite | — | `DB_CONNECTION=sqlite`, arquivo local |
| PHPUnit | `^12.5.12` | testes de feature |
| Laravel Pint | `^1.27` (dev) | formatação de código |
| Laravel Pail | `^1.2.5` (dev) | stream de logs em dev |
| Faker / Mockery / Collision | dev | suporte a testes e seeders |

## Funcionalidades

- **Autenticação** — `POST /api/auth/login` (devolve token), `POST /api/auth/logout`, `GET /api/auth/session` (e alias `GET /api/me`), tudo protegido por `auth:sanctum`.
- **Escolas** — CRUD completo, com endereço/região, cor, orientador vinculado, vínculo com usuários (`GET`/`PUT /api/schools/{school}/users`) e equipes TBR por escola.
- **Usuários** — CRUD, vínculo com escolas (`GET`/`PUT`/`POST`/`DELETE /api/users/{user}/schools...`) e leitura das credenciais MundoZ (`GET /api/users/{user}/mundoz`, restrita ao próprio usuário ou admin).
- **Cargos e permissões** — CRUD de `roles` com array de permissões (`schools`, `users`, `roles`, `items`, `tbr`, `all_schedules`, `agenda`); coluna enum `role` no usuário (`admin`/`orientador`/`professor`/`escola`) + relação `roleModel`.
- **Turmas, salas e horários** — `classes`, `rooms`, `schedules` (grade semanal) e `orientador-schedules` (agenda semanal do orientador, com remoção por escola).
- **Itens por NAP** — `items` (tapetes/tecnologias) e `nap-items` com `upsert` e remoção por escola/ano; **limite de 2 usuários por escola + NAP** (`App\Support\NapCapacity::LIMIT`).
- **Segmentos** — `segment-configs` com `upsert` e remoção por escola.
- **Agenda** — CRUD de atividades, com campos opcionais de registro no MundoZ (`registrar_mundoz`, `escola`, `ano`, `tipo`, `confirmado_por`).
- **TBR** — categorias (`tbr-categories`, seed: *Kid Power*, *Festival de Habilidades*) e times (`tbr-teams`) com `PUT /tbr-teams/replace-for-school/{schoolId}`.
- **Credenciais MundoZ por usuário** — colunas `mundoz_user` / `mundoz_password` (a senha fica em `$hidden` e nunca aparece no JSON padrão).

## Pré-requisitos

- PHP 8.3+ (`php -v`)
- Composer (`composer -V`)
- Nenhum servidor de banco necessário (SQLite local)

## Instalação e execução

```bash
# 1. Dependências + .env + chave da aplicação + migrações + seed
composer setup

# 2. Subir o servidor de desenvolvimento
composer dev
# ou simplesmente:
php artisan serve --port=8000

# 3. Testes
composer test
# ou
php artisan test
```

A API fica disponível em `http://localhost:8000`.

> O script `composer setup` já copia `.env.example` → `.env`, gera o `APP_KEY`, roda `migrate` e `db:seed`.

### Usuário admin criado pelo seed

| Campo | Valor (seed de desenvolvimento) |
|---|---|
| Email | `lucascastro121295@gmail.com` |
| Senha | `mudar123` |
| Role | `admin` |

> Apenas para ambiente local. Troque ou remova antes de qualquer uso em produção.

## Estrutura de pastas

```
control-school-back/
├── app/
│   ├── Http/Controllers/Api/   # 14 controllers (Auth, User, School, SchoolClass,
│   │                           #  Room, Schedule, OrientadorSchedule, SegmentConfig,
│   │                           #  Item, NapItem, Agenda, TbrCategory, TbrTeam, Role)
│   ├── Models/                 # 15 models (User, School, SchoolClass, Room, Schedule,
│   │                           #  OrientadorSchedule, SegmentConfig, Item, NapItem,
│   │                           #  Agenda, AgendaOrientador, TbrCategory, TbrTeam,
│   │                           #  Role, UserSchool)
│   ├── Providers/              # AppServiceProvider
│   └── Support/
│       └── NapCapacity.php     # limite de 2 usuários por escola + NAP
├── bootstrap/                  # boot da aplicação (app.php, providers)
├── config/                     # arquivos de configuração do Laravel
├── database/
│   ├── database.sqlite         # banco de desenvolvimento
│   ├── migrations/             # 24 migrations (tabelas + seeds de roles/cargos)
│   └── seeders/                # DatabaseSeeder (admin, categorias TBR, roles)
├── public/                     # entrypoint web (index.php)
├── routes/
│   ├── api.php                 # todas as rotas da API
│   └── console.php
├── storage/                    # logs, cache, uploads
├── tests/
│   └── Feature/ApiTest.php     # testes de autenticação e endpoints
├── artisan
├── composer.json               # scripts: setup, dev, test
└── AGENTS.md                   # notas do projeto / convenções (fonte de verdade)
```

## Variáveis de ambiente

Somente os **nomes** das chaves (valores em `.env`, que não deve ser versionado). Principais:

| Chave | Descrição |
|---|---|
| `APP_NAME`, `APP_ENV`, `APP_KEY`, `APP_DEBUG`, `APP_URL` | identidade/debug da aplicação |
| `APP_LOCALE`, `APP_FALLBACK_LOCALE`, `APP_FAKER_LOCALE` | idiomas |
| `APP_MAINTENANCE_DRIVER` | driver de manutenção |
| `BCRYPT_ROUNDS` | custo do hash de senha |
| `LOG_CHANNEL`, `LOG_STACK`, `LOG_DEPRECATIONS_CHANNEL`, `LOG_LEVEL` | logs |
| `DB_CONNECTION` | driver do banco (usado: `sqlite`) |
| `SESSION_DRIVER`, `SESSION_LIFETIME`, `SESSION_ENCRYPT`, `SESSION_PATH`, `SESSION_DOMAIN` | sessões |
| `BROADCAST_CONNECTION`, `FILESYSTEM_DISK`, `QUEUE_CONNECTION`, `CACHE_STORE` | infraestrutura |
| `REDIS_CLIENT`, `REDIS_HOST`, `REDIS_PASSWORD`, `REDIS_PORT` | Redis (opcional) |
| `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME` | e-mail |
| `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_DEFAULT_REGION`, `AWS_BUCKET`, `AWS_USE_PATH_STYLE_ENDPOINT` | storage (opcional) |
| `VITE_APP_NAME` | nome exibido no front de build |

> Dica: `cp .env.example .env` e rode `php artisan key:generate`.

## Banco de dados

- **Driver:** SQLite (`database/database.sqlite`), sessões/cache/filas também em banco (`SESSION_DRIVER=database`, `CACHE_STORE=database`, `QUEUE_CONNECTION=database`).
- **Migrations** (`database/migrations/`): usuários, cache, jobs, `personal_access_tokens`, `schools`, `school_classes`, `rooms`, `schedules`, `orientador_schedules`, `segment_configs`, `items`, `nap_items`, `agendas`, `tbr_categories`, `tbr_teams`, `user_schools`, `agenda_orientadores`, `roles` (+ `role_id` em users), coluna `nap` em `user_schools`, credenciais MundoZ em `users` e campos MundoZ em `agendas`.

```bash
php artisan migrate          # aplica pendências
php artisan migrate:fresh    # recria do zero
php artisan migrate:fresh --seed  # recria + DatabaseSeeder
php artisan db:seed          # só o seed
php artisan tinker           # inspeção interativa
```

### Seed (`DatabaseSeeder`)

- Usuário admin (`lucascastro121295@gmail.com` / `mudar123`)
- Categorias TBR: `Kid Power`, `Festival de Habilidades`
- Cargos padrão: **Orientador** (`schools`, `agenda`), **Professor** (`agenda`), **Escola** (`schools`)

## Endpoints da API

Base: `/api` — rotas abaixo (exceto login) exigem header `Authorization: Bearer <token>`.

| Método/Recurso | Rotas |
|---|---|
| Auth | `POST /auth/login`, `POST /auth/logout`, `GET /auth/session`, `GET /me` |
| Escolas | `apiResource schools` + `GET/PUT /schools/{school}/users` |
| Turmas | `apiResource classes` |
| Salas | `apiResource rooms` |
| Horários | `apiResource schedules` |
| Horários do orientador | `apiResource orientador-schedules` + `DELETE /orientador-schedules/by-school/{schoolId}` |
| Segmentos | `apiResource segment-configs` + `POST /segment-configs/upsert` + `DELETE /segment-configs/by-school/{schoolId}` |
| Itens | `apiResource items` |
| Itens por NAP | `apiResource nap-items` + `POST /nap-items/upsert` + `DELETE /nap-items/by-school/{schoolId}` + `DELETE /nap-items/by-school-and-year/{schoolId}/{year}` |
| Agenda | `apiResource agenda` |
| Categorias TBR | `apiResource tbr-categories` |
| Times TBR | `apiResource tbr-teams` + `PUT /tbr-teams/replace-for-school/{schoolId}` + `DELETE /tbr-teams/by-school/{schoolId}` |
| Usuários | `apiResource users` + `GET/PUT/POST/DELETE /users/{user}/schools...` + `GET /users/{user}/mundoz` |
| Cargos | `apiResource roles` |

## Observações importantes

- **Convenção crítica `roleModel`**: a relação de cargo em `User` se chama `roleModel()` (nunca `role()`) porque a coluna `role` já existe; nomear a relação como `role()` quebra a serialização JSON do Eloquent (o atributo vira objeto/null e o front falha). Use sempre `with('roleModel')` nos eager loads e exponha o cargo via accessor `role_data`.
- **Limite de NAP**: `App\Support\NapCapacity` valida no máximo 2 usuários por combinação escola + NAP (aplicado em `SchoolController` e no replace de vínculos).
- **Senha MundoZ em texto plano**: `users.mundoz_password` é necessária para a automação navegar na plataforma MundoZ; fica em `$hidden` e só é exposta no endpoint dedicado ao próprio usuário/admin.
- **Formato de resposta**: JSON em `snake_case` (o frontend converte para `camelCase` com `toCamel()`).
- **Testes**: `tests/Feature/ApiTest.php` cobre exigência de autenticação, login e operações principais — rode com `composer test`.
- **Guia de agentes**: `AGENTS.md` (e `CLAUDE.md`) documentam convenções e histórico de mudanças deste repositório — leia antes de alterar.
- O README anterior era o padrão do Laravel (genérico, em inglês) e não descrevia o projeto; foi substituído por este.
