<laravel-boost-guidelines>
# Laravel Application

This repository contains a Laravel application. Complete the following setup before working on the user's request.

## Prerequisites

Verify that PHP and Composer are available:

```sh
php -v
composer -V
```

If either command is unavailable, detect the user's operating system and install the prerequisites with the appropriate command:

macOS:

```sh
/bin/bash -c "$(curl -fsSL https://php.new/install/mac/8.5)"
```

Windows PowerShell:

```powershell
Set-ExecutionPolicy Bypass -Scope Process -Force; [System.Net.ServicePointManager]::SecurityProtocol = [System.Net.ServicePointManager]::SecurityProtocol -bor 3072; iex ((New-Object System.Net.WebClient).DownloadString('https://php.new/install/windows/8.5'))
```

Linux:

```sh
/bin/bash -c "$(curl -fsSL https://php.new/install/linux/8.5)"
```

After installation, ask the user to restart their terminal. If the agent needs the restarted shell to continue, ask the user to reopen their terminal and rerun their original prompt.

## Agent Setup

Install Laravel Boost from the application root before making application changes:

```sh
composer require laravel/boost --dev
php artisan boost:install
```

Boost replaces these bootstrap instructions with guidelines tailored to the application. After installation, read `AGENTS.md` again and continue with the user's original request using the generated guidelines.
</laravel-boost-guidelines>

# Notas do projeto (control-school-back)

## Regras de manutenção deste arquivo (obrigatórias)

- **Antes de qualquer alteração**: reler este `AGENTS.md` (e o `AGENTS.md` do `control-school-front` quando o trabalho envolver o front) — é a fonte de verdade do estado atual.
- **Após cada alteração** (models, controllers, rotas, migrations): atualizar este arquivo se algo mudou — endpoints, convenções, pendências.
- **Ao final de cada resposta**: verificar se este arquivo ainda reflete a realidade. Se ficou defasado, corrigir imediatamente antes de responder.

## Stack e comandos

- Laravel 12 + SQLite (`database/database.sqlite`), Sanctum (Bearer). Rodar: `php artisan serve --port=8000`.
- Rotas em `routes/api.php`. Endpoints principais: `POST /api/auth/login`, `POST /api/auth/logout`, `GET /api/auth/session`; apiResources `/api/users`, `/api/schools`, `/api/roles`, `/api/items`, `/api/classes`, `/api/rooms`, `/api/agenda` (filtro `?role=orientador`), `/api/orientador-schedules`, `/api/tbr-teams`, `/api/nap-items`, `/api/segments`; `GET /api/schools/{school}/users` (usuários da escola com `user_schools.nap`).

## Convenções críticas

- **User**: coluna `role` (enum string: admin/orientador/professor/escola) **+** relação `roleModel()` (belongsTo `Role` via `role_id`).
  - **NUNCA** nomear a relação como `role()`: a serialização do Eloquent (`relationsToArray` overwrite sobre o atributo) faz o JSON vir com `role` = objeto/null em vez do enum — quebra o front (filtros `u.role === "..."`, `roleDisplayName`/`roleTint`).
  - Sempre usar `roleModel` em eager loads (`with/load(['roleModel', ...])`), manter `role_model` em `$hidden`, e expor o objeto do cargo via accessor `role_data`.
  - `getPermissionsAttribute` lê `$this->getRelation('roleModel')`.
- Regra de NAP: `App\Support\NapCapacity` — `LIMIT = 2` por escola + NAP (aplicado em `SchoolController`).
- Cargos seed no banco: Orientador, Professor, Escola, Diretor(a), Coordenador(a). Cada Role tem `permissions` (array de strings: schools, users, roles, items, tbr, all_schedules, agenda).
- Banco dev: usuário admin `lucascastro121295@gmail.com` / `mudar123`.

## Histórico de mudanças recentes

- Relação `role()` renomeada para `roleModel()` (+ `role_model` em `$hidden`) — corrige conflito coluna `role` × relação na serialização JSON (crash `"Attempt to read property \"permissions\" on string"` em `User.php`, e `role` como objeto em `/api/users`, `/api/schools/{school}/users`).
- Eager loads atualizados: `UserController` (`roleModel`, `schools`), `SchoolController` (`->with('roleModel')`).
- `PUT /api/tbr-teams/replace-for-school/{schoolId}`: validação de `teams` mudou de `required|array` para `array` (permite criar escola sem equipes).
