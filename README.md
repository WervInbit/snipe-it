# Inbit Device Refurbishment Platform

This repository is an independent, refurbishment-focused fork of
[Snipe-IT](https://github.com/grokability/snipe-it). It supports device intake,
identification, hardware-component traceability, diagnostic and operational
workflows, work orders, and evidence-backed sale-readiness decisions.

> [!WARNING]
> **Status: active pre-V1 development (`v0.9.0-dev`).** Automated application
> gates and the Supervisor product-setup authorization contract are green, but
> final candidate promotion and owner-operated release acceptance are not.
> No revision has been approved as a public or generally supported release.
> See the
> [current V1 readiness status](docs/v1-release-readiness-status-2026-08-25.md)
> for exact evidence and remaining blockers.

## Product Contract

The fork retains useful Snipe-IT inventory foundations while defining a
different operating model for refurbished devices:

- assets move through explicit lifecycle statuses; legacy asset
  checkout/checkin/audit mutation flows are not part of the refurbishment
  contract;
- model numbers, reusable presets, structured attributes, and expected
  components describe the intended device configuration;
- tracked components have serials, parent/child hierarchy, placement,
  condition, expected-state reconciliation, lifecycle events, and calculated
  specification contributions;
- workflow profiles combine diagnostics and operator tasks, snapshot their
  applicable definitions per run, retain controlled photo evidence, and feed
  sale-readiness decisions;
- readiness is tied to the current asset model, workflow profile, applicable
  items, expected/attached components, and lifecycle context rather than a
  stale pass flag;
- scan-first asset and component navigation supports shop-floor use;
- work orders, comments, files, visibility profiles, and role-specific
  permissions support operational coordination;
- production-foundation seeders are separated from explicitly disposable demo
  and development scenarios;
- English and Dutch operator surfaces coexist with inherited translations.

The chronological product delta is recorded in
[fork notes](docs/fork-notes.md). Component rules are documented in
[component hierarchy operations](docs/component-hierarchy-operations.md), and
the V1 role contract is summarized in the
[role-capability matrix](docs/v1-operational-role-capability-matrix.md).

### Sensitive records and media

- Licenses hold entitlements and seats for device-bound/OEM software,
  user- or device-assigned products such as Office, add-on software, and
  multi-seat/volume agreements. Product keys and license attachments are
  restricted independently from ordinary asset access.
- Asset and model-resource attachments are private application resources with
  separate view, upload, and manage abilities. A model resource applies to the
  model, not only to the asset page from which it was opened.
- Asset gallery images are public catalog/device media and may appear on
  public or webshop surfaces. Moving controlled workflow evidence into that
  gallery is an explicit permission-gated publish action.
- Workflow evidence stays in private storage and is served through authorized
  application routes. Deleting a workflow run removes its private evidence;
  soft-deleting and restoring an asset preserves both evidence and gallery
  media.
- Private attachments and evidence are not a secret vault. Passwords,
  recovery codes, customer credentials, and repair credentials must not be
  stored in current notes, files, keys, or photos.

## Current Readiness

The July/August V1 repair pass has implemented and tested the highest-risk
findings from the original audit, including:

- decoded-image validation and private, controlled workflow-evidence storage;
- transactional agent reports and context-bound workflow readiness;
- authorization and company scoping for component, attachment, model
  attribute, work-order, asset-image, and accessory paths;
- transactional component hierarchy mutation plus a read-only integrity audit;
- explicit lifecycle semantics, least-privilege foundation roles, and
  production/demo seeder boundaries;
- resumable workflow schema/copy/cutover migrations with parity checks and
  retained legacy tables;
- a fail-closed PHPUnit/Dusk database guard;
- an immutable production container profile with external secrets, database,
  Redis, TLS termination, and durable data volumes;
- critical/high dependency remediation, checksum-pinned upstream Laravel
  backports, and removal of the abandoned `laravelcollective/html` package.

The guarded repository-wide non-LDAP SQLite suite passes 2,166 tests with
10,553 assertions. The complete strict MariaDB 11.4.7 suite also passes 2,166
tests with 10,553 assertions on the guarded disposable `snipeit_test` database.
Real LDAP remains outside the V1 support and qualification boundary. PHPStan has a retained experimental
baseline, but its result is currently environment-dependent and the owner has
deferred it until after V1. It is not invoked by the V1 quality workflow and is
not a release gate.

The exact production app/web images have passed content and blocking security
checks and are running in the populated rehearsal after additive role upgrade
and a complete cold restart with unchanged database/upload fingerprints. This
is not yet a V1 release: the remaining gates require owner-operated evidence,
not another code-path assertion.

- complete the browser/operator matrix, including Supervisor product setup and
  Admin-only destructive lifecycle actions, and verify one migrated user's
  existing password without sharing it;
- repeat the now-green
  [local production-profile rehearsal](docs/v1-production-profile-rehearsal-2026-08-13.md)
  in the managed release environment with the explicit mail-disabled profile
  (or later validated real TLS/SMTP), monitoring, and off-host
  database/upload restore;
- resolve the remaining catalog placeholders and the product/operator
  decisions listed in `TODO.md`;
- cut release metadata from a reviewed immutable commit rather than from a
  dirty worktree.

Do not use the historical
[2026-07-21 audit](docs/v1-release-readiness-audit-2026-07-21.md) as the current
state: it records the defects as originally found. The dated current status
maps each finding to its repair or remaining release gate.

## Supported V1 Target

- PHP 8.2
- Laravel 11
- MariaDB/MySQL, using the exact version proven in the release rehearsal
- Redis for production cache/session/queue operation
- PHPUnit 10
- Laravel Mix with the inherited AdminLTE/Bootstrap front end
- Docker Compose v2 for the documented local and production container profiles

The current V1 production profile supports local accounts and keeps LDAP and
outgoing email explicitly disabled. LDAP login/synchronization and SMTP
delivery remain unsupported until their real-service acceptance rehearsals
pass. When email is disabled, self-service reset and email notification actions
are unavailable; an authorized administrator can set a temporary password in
the protected user editor.

PostgreSQL test infrastructure remains useful for compatibility work, but
PostgreSQL is outside the declared V1 production matrix until all
database-specific migrations and a populated upgrade/rollback rehearsal pass.

Current official Snipe-IT master has moved to Laravel 12 and PHPUnit 11. The
fork has thousands of upstream-only commits and hundreds of fork-only commits
since its common base, so upstream documentation and bulk-merge upgrade
instructions do not define this product. Security and correctness changes must
be reviewed and ported deliberately.

## Local Development

### Requirements

- Git
- Docker Engine or Docker Desktop
- Docker Compose v2
- sufficient disk and memory for PHP and front-end image builds

Never place production secrets, database exports, private certificates, or
production-derived uploads in the checkout.

### Clean local HTTP profile

These commands are for a clean clone and new Docker volumes. They are not a
reset procedure for an existing environment.

Build before creating `.env`, then create it only when it does not exist:

```powershell
docker compose -f docker-compose.yml -f docker-compose.localhost.yml build app
if (-not (Test-Path .env)) { Copy-Item .env.example .env }
```

Use local-only values in `.env`:

```dotenv
APP_ENV=local
APP_KEY=
APP_URL=http://127.0.0.1:18080
ALLOW_WEB_SETUP=true
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=snipeit
DB_USERNAME=snipeituser
DB_PASSWORD=snipeitpw
```

Initialize dependencies and keys, start the application, and apply
non-destructive migrations:

```powershell
docker compose -f docker-compose.yml -f docker-compose.localhost.yml up -d --wait db
docker compose -f docker-compose.yml -f docker-compose.localhost.yml run --rm --no-deps --entrypoint bash app -lc "mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache && composer install --prefer-dist --no-interaction --no-progress && php artisan key:generate --force && php artisan passport:keys --force"
docker compose -f docker-compose.yml -f docker-compose.localhost.yml up -d app web
docker compose -f docker-compose.yml -f docker-compose.localhost.yml exec -T app php artisan migrate --force
```

Open [http://127.0.0.1:18080](http://127.0.0.1:18080).

The default `docker-compose.yml` expects the local `dev.inbit` HTTPS setup,
bind-mounts the source tree, and includes development-only service defaults. It
is not the production profile.

For known disposable sample accounts and device scenarios, use the
[demo guide](docs/demo-guide.md). Demo/scenario seeders must never run against
a shared, production, or production-derived database.

> [!CAUTION]
> Never use `migrate:fresh`, `migrate:refresh`, `migrate:reset`, `db:wipe`, or a
> destructive demo reset against an environment whose exact database target
> has not been explicitly reviewed and approved.

## Testing

Feature tests refresh their database. In Docker, clear cached configuration and
force the isolated target at the process boundary:

```powershell
docker compose exec -T -e APP_ENV=testing -e DB_CONNECTION=sqlite -e DB_DATABASE=:memory: app php artisan optimize:clear --env=testing
docker compose exec -T -e APP_ENV=testing -e DB_CONNECTION=sqlite -e DB_DATABASE=:memory: app php artisan tinker --env=testing --execute="dump(app()->environment(), config('database.default'), config('database.connections.'.config('database.default').'.database'));"
docker compose exec -T -e APP_ENV=testing -e DB_CONNECTION=sqlite -e DB_DATABASE=:memory: app php artisan test --env=testing
```

The preflight must print `testing`, `sqlite`, and `:memory:`. The executable
guard rejects local PHPUnit targets other than in-memory SQLite. External
MySQL/PostgreSQL CI is allowed only with
`SNIPEIT_ALLOW_EXTERNAL_TEST_DATABASE=1` and the exact disposable database
name `snipeit_test`.

See [TESTING.md](TESTING.md) for focused runs, Dusk isolation, groups, and
release-evidence requirements.

## Production

`docker-compose.production.yml` is the only documented production container
path. It builds immutable app/web artifacts, uses external file-backed secrets,
does not bundle a database or Redis, separates PHP-FPM/queue/scheduler services,
and performs no migration, seeding, dependency install, or key generation at
startup.

It is a deployable foundation, not proof of release readiness. Follow the
[production deployment runbook](docs/production-deployment.md) and complete its
external rehearsal before using a V1 tag.

The inherited `install.sh`, `snipeit.sh`, `upgrade.php`, Vagrant, Heroku, and
alternate release-download Docker paths are not supported for this fork. The
remaining compatibility filenames fail closed where retaining a clear error is
safer than silently installing or pulling official Snipe-IT. Do not use an
upstream Snipe-IT installer or upgrader against this repository.

Release images must be built once from the reviewed commit, scanned, pushed by
immutable digest, and deployed with:

- a dedicated external MariaDB/MySQL database and authenticated Redis;
- managed TLS termination and narrowly trusted proxy addresses;
- externally stored APP, database, Redis, and Passport keys;
- durable public/private upload and backup volumes;
- off-host encrypted backups and a verified restore;
- queue/scheduler supervision, centralized logs, health monitoring, and a
  rollback owner.

## Dependency Policy

Production Composer audits currently report no unmitigated advisories and no
abandoned packages. Three Laravel 11 advisory identifiers remain explicitly
listed as ignored only because the exact official fixes are stored under
`patches/`, checksum-pinned in `patches.lock.json`, applied during production
builds, and covered by framework-level regressions. Changing the Laravel lock
or either patch requires revalidating those assumptions.

The JavaScript production graph currently has no critical/high findings; its
remaining moderate/low Bootstrap and inherited browser-build-chain debt is
tracked in the current V1 status. Release CI must rerun both Composer and npm
audits rather than relying on this snapshot.

## Documentation

- [Current V1 readiness status](docs/v1-release-readiness-status-2026-08-25.md)
- [Production-profile rehearsal evidence](docs/v1-production-profile-rehearsal-2026-08-13.md)
- [Historical V1 audit](docs/v1-release-readiness-audit-2026-07-21.md)
- [Fork notes](docs/fork-notes.md)
- [Production deployment](docs/production-deployment.md)
- [Workflow migration upgrade](docs/workflow-migration-upgrade.md)
- [Component hierarchy operations](docs/component-hierarchy-operations.md)
- [Component integrity audit](docs/component-integrity-audit.md)
- [Catalog model-number verification](docs/catalog-model-number-verification.md)
- [Demo guide](docs/demo-guide.md)
- [Testing guide](TESTING.md)
- [Open product work](TODO.md)
- [Session progress](PROGRESS.md)
- [Agent handbook](AGENTS.md)
- [Security policy](SECURITY.md)

Operator guides under `docs/manuals/operator-guides/` are being developed
separately and are not yet a versioned V1 manual.

## Contributing

Read [CONTRIBUTING.md](CONTRIBUTING.md) and [AGENTS.md](AGENTS.md) before
changing the fork. Start from the latest `PROGRESS.md` and `docs/fork-notes.md`,
preserve unrelated worktree changes, test the invariant you changed, and update
the user/operator documentation when behavior changes.

## Security

Fork-specific vulnerabilities belong to this repository's private maintainer
channel, not the upstream Snipe-IT issue tracker. Follow
[SECURITY.md](SECURITY.md). Never publish credentials, production data, or an
executable proof of concept.

## License And Attribution

This project derives from Snipe-IT and retains its contributor history and
GNU Affero General Public License obligations. It is licensed under
[AGPL-3.0-or-later](LICENSE).

Upstream resources remain useful for behavior this fork still inherits, but
they do not define this fork's support, security ownership, installation,
upgrade, or release policy.
