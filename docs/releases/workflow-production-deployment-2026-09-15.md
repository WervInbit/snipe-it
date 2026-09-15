# Workflow production deployment - 2026-09-15

## Outcome

The workflow dependency, execution guard, profile ordering, status confirmation,
and shared checkbox-layout changes were deployed to `https://snipe.inbit` on
2026-09-15. The release retained the existing production database, workflow
profiles, items, assignments, runs, results, uploads, application key, Passport
keys, and container topology.

The production dependency graph intentionally started empty. Administrators can
connect the existing production workflows after deployment without recreating
them. Existing runs remain editable and are not automatically locked when all
items are complete.

## Release identity

- Release source commit: `0c5ea2dc16d080d4f2af24d16a2c974cda719007`
- Release branch: `codex/production-workflows-2026-09-15`
- Mainline runtime-library follow-up: `d3ad3ddc38`
- Application registry digest:
  `sha256:549a51e901f7de0f70d4534f83da00d1ad51e812c6277590831eed9775454dbc`
- Web/edge registry digest:
  `sha256:7deaed23895dbced50626ba2c5d845bd49fee92916ad5f522e0cae58ce69aca5`

The app, queue, and scheduler use the application digest. The internal web and
public TLS edge use the web digest.

## Qualification evidence

- Production frontend build passed.
- Focused workflow/status/UI suite passed: 124 tests, 738 assertions.
- Node tests passed: 4 tests.
- Blade compilation and production-image content verification passed.
- Trivy 0.74.0 reported zero unsuppressed high or critical findings for both
  final images with `--ignore-unfixed` and exit-code enforcement. The existing
  Laravel advisory exception remains checksum-pinned to the reviewed backport
  and expires on 2026-10-01 through `.trivyignore.yaml`.
- The web image was rebuilt with fixed Alpine runtime packages:
  `libexpat 2.8.4-r0`, `libuuid 2.42.3-r1`, and OpenSSL `3.5.8-r0`.
- PHPStan reported only five pre-existing undefined-property findings in the
  unchanged legacy `TestResultController`; the changed services and migrations
  were clean in the scoped run.

## Database change and preservation checks

The deployment required exactly these two pending migrations:

- `2026_09_15_120000_add_workflow_execution_guards`
- `2026_09_15_130000_add_workflow_execution_levels_and_status_guard_audits`

The migration count advanced from 479 to 481. The additive
`ProductionPermissionGroupSeeder` was then applied to the four foundation
groups.

Post-migration validation confirmed:

- 17 users and 12 assets retained;
- 6 workflow profiles, 36 workflow items, and 35 profile-item assignments
  retained;
- 10 workflow runs, 130 results, 0 result photos, and 1,518 workflow audits
  retained;
- all 10 existing runs received a profile-order snapshot;
- all 6 profiles defaulted to repeat policy `override_required` and execution
  level `operator`;
- the dependency table exists with zero edges, ready for manual configuration;
- the required-item totals per profile remained `20, 3, 2, 3, 2, 1`; and
- `failed_jobs` remained zero.

The production profiles and their existing order values were preserved:

1. Laptop wipen (`0`)
2. Windows Installeren en Updaten (`1`)
3. Cleaning (`2`)
4. Standard Diagnostics (`3`)
5. Pre-Sale Check (`5`)
6. Shipping Laptop (`6`)

The numbered UI uses dependency-safe order with the stored display order as its
stable preference. Administrators can drag profiles to change those stored
values and can connect dependencies independently afterward.

## Backups and rollback evidence

The authoritative pre-change recovery point is:

- Host: `/srv/snipeit-v1/backups/pre-deploy-20260915T160632Z`
- Off-host: `C:\snipeit-production-backups\pre-deploy-20260915T160632Z`
- Transport SHA-256:
  `4033c4f531bae976ff5d8b8adaa74a701ab50d6a0ed79c98d0c6db3c8299e500`

It contains a native MariaDB dump, application-managed backup set, public and
private uploads, protected deployment configuration and keys, runtime state,
and workflow-specific baselines. The transport hash, every internal checksum,
and every compressed archive were verified off-host before migration.

A fresh authoritative backup was deliberately taken after a preflight
`optimize:clear` was found to clear the Redis maintenance marker. No migration
had run, the queue and scheduler remained stopped, maintenance was immediately
restored, and the cache-clearing step was removed. The newer backup above was
then created and verified before the deployment continued.

The verified post-cutover recovery/evidence point is:

- Host: `/srv/snipeit-v1/backups/post-cutover-20260915T161937Z`
- Off-host: `C:\snipeit-production-backups\post-cutover-20260915T161937Z`
- Transport SHA-256:
  `375ceeda0f1ed259fb555c25fadf9f5283221d25117b76c9c93007acfae17c86`

Rollback after these forward migrations must use the complete verified
pre-change database/upload/config backup and old image digests. Do not improvise
with `migrate:rollback` on the production database.

## Production smoke checks

- App, internal web, queue, scheduler, and public edge were healthy with zero
  restarts after cutover.
- Public `/health` and `/login` returned HTTP 200 with verified certificate
  chain (Windows revocation lookup unavailable, so the workstation used
  `--ssl-no-revoke`, not insecure certificate bypass).
- HSTS, CSP, frame denial, MIME-sniffing protection, and referrer policy headers
  remained present.
- Queue depth was zero and fresh service logs had no fatal, SQLSTATE,
  production-error, critical Nginx, or unhealthy signatures.
- Workflow progression and required-workflow summaries rendered against all 12
  real assets: 50 applicable steps total.
- All 24 real asset/protected-status combinations generated valid confirmation
  decisions and hashes.
- A corrected in-memory web-middleware harness rendered all 12 complete asset
  detail pages (11.5 MB total) with workflow progression, status-confirmation
  modal, and modal-to-body input handling present.
- The running web image contains the normal-flow checkbox spacing fix.

Three retained runs currently evaluate as stale (two on asset ID 1 and one on
asset ID 8), reflecting changed workflow/readiness definitions. They were not
deleted or locked; authorized users can still edit the existing runs, while a
new run follows the new override rules.

## Operator follow-up

Configure the dependency graph manually in production after reviewing the six
profile relationships. No dependency is inferred solely from display order.
After configuration, perform one signed-in browser interaction check for drag
ordering, searchable dependency selection, editing an old run, starting a new
run with an authorized override, and confirming/denying a protected status
change. The server-side paths and complete asset views passed, but this final
check verifies browser interaction with the operator's real permissions.
