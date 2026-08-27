# Running The Test Suite

## Database Safety

Laravel feature tests use `LazilyRefreshDatabase`. If the test process boots with the local MySQL configuration, it can drop and recreate the current database.

Before testing:

1. copy `.env.testing.example` to `.env.testing`;
2. keep `APP_ENV=testing`, `DB_CONNECTION=sqlite`, and `DB_DATABASE=:memory:`;
3. clear Laravel's cached configuration;
4. explicitly override the Docker container's local environment;
5. stop immediately if any preflight reports MySQL or a local/production database name.

Do not run `migrate:fresh`, `migrate:refresh`, `migrate:reset`, or `db:wipe` as test preparation on a shared environment.

## Docker Preflight

From the repository root:

```powershell
docker compose exec -T -e APP_ENV=testing -e DB_CONNECTION=sqlite -e DB_DATABASE=:memory: app php artisan optimize:clear --env=testing
docker compose exec -T -e APP_ENV=testing -e DB_CONNECTION=sqlite -e DB_DATABASE=:memory: app php artisan tinker --env=testing --execute="dump(app()->environment(), config('database.default'), config('database.connections.'.config('database.default').'.database'));"
```

The second command must report:

```text
testing
sqlite
:memory:
```

The PHPUnit safety bootstrap synchronizes all PHP environment representations, then the project test base validates the booted Laravel configuration before the database-refresh trait runs. Local PHPUnit accepts only in-memory SQLite; the conventional persistent `database/database.sqlite` path is deliberately rejected. `phpunit.xml` forces the guard marker and `APP_ENV=testing`; database values remain non-forced so the deliberate MySQL/PostgreSQL CI jobs can supply their isolated targets. The explicit Docker SQLite overrides are therefore required.

## Run Tests

Full suite:

```powershell
docker compose exec -T -e APP_ENV=testing -e DB_CONNECTION=sqlite -e DB_DATABASE=:memory: app php artisan test --env=testing
```

Focused file:

```powershell
docker compose exec -T -e APP_ENV=testing -e DB_CONNECTION=sqlite -e DB_DATABASE=:memory: app php artisan test tests/Feature/Assets/StartNewTestRunTest.php --env=testing
```

Focused method:

```powershell
docker compose exec -T -e APP_ENV=testing -e DB_CONNECTION=sqlite -e DB_DATABASE=:memory: app php artisan test --env=testing --filter=test_start_new_run_requires_profile_selection tests/Feature/Assets/StartNewTestRunTest.php
```

Direct `vendor/bin/phpunit` calls are discouraged in the app container. If a tool requires one, pass the same explicit Docker environment overrides and keep the forced variables in `phpunit.xml`.

## MySQL And PostgreSQL Matrix

External database tests are rejected unless all of these conditions are true:

- `APP_ENV=testing`;
- connection is `mysql` or `pgsql`;
- database name is exactly `snipeit_test`;
- `SNIPEIT_ALLOW_EXTERNAL_TEST_DATABASE=1` is explicitly set.

The GitHub MySQL/PostgreSQL workflows use disposable service databases with those values. Do not set the external-database marker in a local `.env` or production environment.

## Dusk

Dusk calls `migrate:fresh` by design and has a separate fail-closed guard. It
is allowed to use only the dedicated `database/dusk.sqlite` target with
`SNIPEIT_DUSK_GUARD=1`. After validating the exact target, the guard creates
the empty SQLite file when a clean clone does not have one; symbolic links and
other targets are rejected.

Use the portable example profile and a local HTTP server in an isolated
workspace or process that is also loaded from `.env.dusk.example`:

```powershell
php artisan dusk:chrome-driver --detect
php artisan serve --host=127.0.0.1 --port=8000 # loaded from the Dusk profile
# In a second terminal:
php artisan dusk --env=example
```

Do not overwrite an active local `.env` to start the server. The browser
workflow is the canonical executable example: its ephemeral runner copies the
profile only after checkout, then uses the same dedicated database for the
server and the Dusk process. Local `.env.dusk` and `.env.dusk.local` files are
ignored and unsupported; recreate them from `.env.dusk.example` so they retain
the dedicated SQLite target and executable guard.

## Groups

Tests such as LDAP coverage can be included or excluded with PHPUnit groups:

```powershell
docker compose exec -T -e APP_ENV=testing -e DB_CONNECTION=sqlite -e DB_DATABASE=:memory: app php artisan test --env=testing --group=ldap
docker compose exec -T -e APP_ENV=testing -e DB_CONNECTION=sqlite -e DB_DATABASE=:memory: app php artisan test --env=testing --exclude-group=ldap
```

## Release Evidence

A targeted pass is not a release signal. Record:

- the exact command and commit;
- effective environment/driver/database from the preflight;
- test and assertion counts;
- skipped groups or extensions;
- static-analysis, dependency-audit, and asset-build results;
- any remaining failures and their owners.

The latest known V1 verification state is tracked in the
[current release-readiness status](docs/v1-release-readiness-status-2026-08-25.md).
