# Contributing To The Inbit Fork

This repository is an independent pre-V1 refurbishment product derived from
Snipe-IT. Contributions must follow this fork's contracts and release plan;
upstream Snipe-IT contribution or installation guidance is not authoritative
for fork-specific behavior.

The [Agent Handbook](AGENTS.md) is the canonical working agreement. The rules
below summarize the contributor path.

## Before Starting

1. Read the latest entry in [PROGRESS.md](PROGRESS.md).
2. Read [fork notes](docs/fork-notes.md) and the
   [current V1 readiness status](docs/v1-release-readiness-status-2026-08-25.md).
3. Check `git status` and preserve changes you did not author.
4. Add the required dated session stub/addendum described in `AGENTS.md`.
5. Identify the user-visible invariant, affected permissions/company scope,
   migration or seeding impact, and documentation touchpoints before editing.

Do not modify the operator-guide tree when another session owns it. Coordinate
any overlapping files rather than reverting or replacing concurrent work.

## Implementation Expectations

- Follow PSR-12 and existing Laravel conventions.
- Prefer policies, scoped model binding, validated requests, and domain
  services over controller-only assumptions.
- Treat lifecycle, company ownership, readiness, and evidence provenance as
  data-integrity boundaries.
- Make mutations transactional when partial persistence would create an
  invalid business state.
- Preserve historical compatibility only where it is intentional. Removed
  asset checkout/checkin/audit and maintenance mutation paths should fail
  explicitly rather than silently reviving inherited behavior.
- Never place secrets, production exports, private certificates, or
  production-derived uploads in the repository or Docker build context.
- Review licenses and the Composer/npm audit impact of every dependency change.

User-visible changes must update the relevant README, fork notes, runbook, or
feature documentation in the same working block.

## Database Safety

Destructive database commands are forbidden on shared or production-like
environments unless the user explicitly authorizes the exact action in the
current message. This includes:

- `migrate:fresh`
- `migrate:refresh`
- `migrate:reset`
- `db:wipe`
- destructive demo reset paths

Before any authorized destructive operation, print `APP_ENV`,
`DB_CONNECTION`, and `DB_DATABASE`, then state the impact in plain language.

Migrations must support upgrade and retry behavior for populated data. For
schema or data-copy changes, document backup, rollback, interruption, parity,
and supported-database assumptions.

Production foundation seeders must be idempotent and additive. Demo or
development scenario seeders must fail closed outside an explicitly disposable
environment.

## Tests

Use focused tests for the changed invariant, then run the proportionate
regression slice. Inside Docker, always clear cached Laravel configuration and
force the test target at the command boundary:

```powershell
docker compose exec -T -e APP_ENV=testing -e DB_CONNECTION=sqlite -e DB_DATABASE=:memory: app php artisan optimize:clear --env=testing
docker compose exec -T -e APP_ENV=testing -e DB_CONNECTION=sqlite -e DB_DATABASE=:memory: app php artisan tinker --env=testing --execute="dump(app()->environment(), config('database.default'), config('database.connections.'.config('database.default').'.database'));"
docker compose exec -T -e APP_ENV=testing -e DB_CONNECTION=sqlite -e DB_DATABASE=:memory: app php artisan test tests/Feature/Path/ToTest.php --env=testing
```

The preflight must report `testing`, `sqlite`, and `:memory:`. Do not disable
the executable test guard. External MySQL/PostgreSQL test jobs require
`SNIPEIT_ALLOW_EXTERNAL_TEST_DATABASE=1` and the exact disposable database
name `snipeit_test`.

For release-affecting work, record:

- the exact test command and result;
- lint/static-analysis/build/audit evidence that applies;
- skipped or environment-dependent coverage;
- a read-only shared-database canary when local production-like services were
  running.

See [TESTING.md](TESTING.md) for the complete safety and evidence workflow.

## Pull Requests And Commits

Keep changes focused and explain why the invariant is needed. Commit subjects
use Title Case and stay under 70 characters.

A pull request should include:

- concise behavior and risk summary;
- permission/company-scope implications;
- migration, seed, backup, and rollback impact;
- exact test/build/audit evidence;
- documentation/configuration changes;
- screenshots for meaningful UI changes;
- known residual risk and follow-up owner.

Do not claim V1 readiness from focused tests alone. Release approval also
requires the integrated suite, supported-database migration rehearsal,
production environment/restore rehearsal, and release artifact scans described
in the current V1 status.

## Upstream Changes

The fork and official Snipe-IT have diverged substantially. Port upstream
security and correctness commits deliberately:

1. identify the exact upstream commit and affected invariant;
2. compare it with fork-modified code;
3. adapt the smallest safe change;
4. add fork-level regression coverage;
5. document the port and any intentional difference.

Do not bulk-merge upstream or assume an upstream migration/upgrade guide applies
to this product without a dedicated review and populated-data rehearsal.

## Conduct And License

Participation is governed by the [Code of Conduct](CODE_OF_CONDUCT.md).
Contributions are accepted under the repository's
[AGPL-3.0-or-later license](LICENSE) and must be compatible with third-party
license obligations.
