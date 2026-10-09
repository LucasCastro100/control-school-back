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
- Rotas em `routes/api.php`. Endpoints principais: `POST /api/auth/login`, `POST /api/auth/logout`, `GET /api/auth/session`; apiResources `/api/users`, `/api/schools`, `/api/roles`, `/api/items`, `/api/classes`, `/api/rooms`, `/api/agenda` (filtro `?role=orientador`), `/api/orientador-schedules`, `/api/tbr-teams`, `/api/nap-items`, `/api/segments`; `GET /api/schools/{school}/users` (usuários da escola **com eager load `roleModel` + `schools`** — o NAP do vínculo sai em `schools[].pivot.nap`; não selecionar `user_schools.nap` avulso, o front lê via `userNap()`).

## Convenções críticas

- **User**: coluna `role` (enum string: admin/orientador/professor/escola) **+** relação `roleModel()` (belongsTo `Role` via `role_id`).
  - **NUNCA** nomear a relação como `role()`: a serialização do Eloquent (`relationsToArray` overwrite sobre o atributo) faz o JSON vir com `role` = objeto/null em vez do enum — quebra o front (filtros `u.role === "..."`, `roleDisplayName`/`roleTint`).
  - Sempre usar `roleModel` em eager loads (`with/load(['roleModel', ...])`), manter `role_model` em `$hidden`, e expor o objeto do cargo via accessor `role_data`.
  - `getPermissionsAttribute` lê `$this->getRelation('roleModel')`.
- Regra de NAP: `App\Support\NapCapacity` — `LIMIT = 2` por escola + NAP (aplicado em `SchoolController`).
- Cargos seed no banco: Orientador, Professor, Diretor(a), Coordenador(a) (cargo "Escola" **removido** em 08/10/2026 — ver histórico). Cada Role tem `permissions` (array de strings: schools, users, roles, items, tbr, all_schedules, agenda).
- Banco dev: usuário admin `lucascastro121295@gmail.com` / `mudar123`.
- **Rotas**: após qualquer edição em `routes/api.php`, rodar `php -l routes/api.php`. Um `use` **duplicado** gera `PHP Fatal error: ... because the name is already in use` e o `artisan serve` morre — **todas** as rotas fora (sintoma no front: `fetch failed / ECONNREFUSED`).
- **Teste de endpoint via curl**: sem `-H 'Accept: application/json'`, requisição sem token volta `500 Route [login] not defined` (o handler tenta redirect web). Com o header, volta o esperado `401`. Teste autenticado: login → Bearer token → `200`.
- Smoke test mínimo p/ subir: 15 rotas principais (`auth/session`, `schools`, `items`, `roles`, `users`, `nap-items`, `school-segments`, `segment-configs`, `agenda`, `classes`, `rooms`, `schedules`, `tbr-categories`, `tbr-teams`, `orientador-schedules`) devem responder `200` com token.

## Estado atual

- **Segmentos por escola**: migrations `2026_10_08_000001_create_school_segments_table` e `2026_10_08_000002_drop_material_type_from_schools_table`. Model `SchoolSegment`, controller `SchoolSegmentController` (CRUD limitado: index/store/destroy), rotas `api/school-segments`. `POST /api/school-segments` aceita `years[]` (opcional): cria uma turma (`SchoolClass`) por ano/série marcado (`firstOrCreate` school_id+nap+name+year) e remove as turmas auto-criadas do segmento cujo ano foi desmarcado (tudo em `DB::transaction`; só mexe em `name` que casa exatamente com `Segments::yearsForLabel()`). `School` sem `material_type`. `app/Support/Segments.php` com helpers de mapeamento legado NAP↔segmentos (`LABELS`, `yearsForLabel()`, `labelForYear()`, `forClass()`). `ScheduleController::applyBusinessRules` valida por **segmento** (usa `SchoolSegment` para material) e checa sobreposição no mesmo (escola, segmento, dia, quinzena) — fortnight-aware (0 aplica a ambas).

## Histórico de mudanças recentes

- **Criação de turmas junto com o segmento (08/10/2026)**: `SchoolSegmentController::store` ganhou o campo opcional `years` (`sometimes|array`, `years.*` string). Após upsert do `SchoolSegment`, `syncClasses()` (privado) roda em `DB::transaction`: para cada ano selecionado faz `SchoolClass::firstOrCreate` (nap = `segment_name`, name = ano, year = `year` do segmento) e apaga as turmas do mesmo segmento/ano cujo `name` casa exatamente com um ano válido e não foi selecionado. Só considera os anos de `Segments::yearsForLabel()` (novo helper público) — turmas com nome composto ("6 ano A") não são tocadas. Sem `years` no payload o comportamento antigo é mantido. Testado via tinker (create/update/empty com rollback): cria 3º+4º, troca para 4º+5º (remove 3º), `[]` limpa tudo.

- **`AuthController::session` carrega `roleModel` (08/10/2026)**: `$request->user()->load('roleModel')` — sem isso `permissions`/`role_data` (appends do `User`) não saíam no JSON da sessão e o front não conseguia montar o menu por permissão. O front usa `userData.permissions` + `role_id` daqui. **Pendência**: nenhuma authorização por permissão no Laravel (só `auth:sanctum`) — qualquer token válido acessa qualquer endpoint; a restrição de páginas/ações é front-only até agora.

- **`items.category` ganhou a 3ª categoria "materiais" (08/10/2026)**: `ItemController` (store e update) valida `in:tapete,tecnologia,materiais`. Coluna `items.category` é `string` simples (migration `2026_09_19_013306`) — **não** é enum do banco, então não há migration nova. Front define a lista em `src/lib/item-categories.ts` (manter em sincronia com o `in:` da validação).

- **Fix fatal error em `routes/api.php` (08/10/2026)**: `use App\Http\Controllers\Api\SchoolSegmentController;` estava duplicado (linhas 11 e 14, no merge das rotas novas de segmentos) → `PHP Fatal error: ... name is already in use` → servidor inteiro caído. Linha 14 removida; validado com `php -l` + smoke test dos 15 endpoints com token (todos 200).

- **Cargo "Escola" removido da tabela `roles`** (0 usuários vinculados; pedido do usuário — vague ao lado de Professor/Coordenador(a)/Diretor(a)/Orientador). Cargos restantes: Orientador, Professor, Diretor(a), Coordenador(a). A enum `role = escola` (coluna users) continua existindo e NÃO foi alterada.

- **`schools.material_type`** (migration `2026_10_08_000000_add_material_type_to_schools_table`): estrutura/material da escola, valores `jornada_z` | `epc` (nullable). Em `School::$fillable` e nas regras de store **e** update do `SchoolController` (`in:jornada_z,epc,`). Front envia `material_type` no create/update da escola (modal Nova/Editar Escola, campo "Estrutura").

- **`GET/PUT /api/schools/{school}/users` eager load `schools`**: antes retornava só `['users.*', 'user_schools.nap']` (coluna avulsa) sem a relação `schools` → o front (`userNap()` em `school-accounts-view`) nunca via o NAP do vínculo, contava 0 por NAP e liberava adicionar além do limite (422 de `NapCapacity` depois). Agora: `$school->users()->with(['roleModel', 'schools'])->get()`.

- **Credenciais MundoZ por usuário**: migration `2026_09_22_000000_add_mundoz_credentials_to_users_table` adiciona `users.mundoz_user` + `users.mundoz_password` (senha em texto puro — necessária pra automação navegar na plataforma). `User::$fillable` ganhou os campos; `mundoz_password` está em `$hidden` (não sai no JSON padrão de usuário). Endpoint novo `GET /api/users/{user}/mundoz` devolve `{mundoz_user, mundoz_password}` apenas para o próprio usuário ou admin (`UserController::mundoz`). Regras de store/update do `UserController` aceitam `mundoz_user`/`mundoz_password` (nullable, max 255).
- Relação `role()` renomeada para `roleModel()` (+ `role_model` em `$hidden`) — corrige conflito coluna `role` × relação na serialização JSON (crash `"Attempt to read property \"permissions\" on string"` em `User.php`, e `role` como objeto em `/api/users`, `/api/schools/{school}/users`).
- Eager loads atualizados: `UserController` (`roleModel`, `schools`), `SchoolController` (`->with('roleModel')`).
- `PUT /api/tbr-teams/replace-for-school/{schoolId}`: validação de `teams` mudou de `required|array` para `array` (permite criar escola sem equipes).
