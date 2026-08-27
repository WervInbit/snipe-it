# V1 Release Readiness Status - 2026-08-04

## Decision

**Current decision: no-go for a public V1 tag; continue release-candidate
development.**

The guarded non-LDAP repository suite is now green. That is a material change
from the [2026-07-23 status](v1-release-readiness-status-2026-07-23.md), which
still listed the full SQLite run as open. It is not sufficient on its own for a
public V1: the supported MariaDB/MySQL job and populated upgrade rehearsal,
real-directory LDAP smoke, browser/operator matrix, production deployment and
restore rehearsal, artifact scans, static-analysis ownership, and release
ownership remain open.

This status distinguishes three evidence levels:

- **Implemented**: the code or configuration contract changed.
- **Test verified**: the relevant guarded automated test passed.
- **Release verified**: the candidate artifact, supported services, upgrade,
  deployment, and operator paths passed in an isolated release environment.

No destructive database command, reset, restore, or seed was used during this
audit continuation. Test commands explicitly forced `APP_ENV=testing`,
`DB_CONNECTION=sqlite`, and `DB_DATABASE=:memory:`. The live screenshot
database and running services were not restarted or modified by these tests.

## Current Evidence

### Repository test suite

- Initial non-LDAP full run: 2,087 passed and 31 failed. The failures were
  retained as diagnostic evidence rather than hidden or closed administratively.
- After implementation and test-contract repairs: **2,120 passed, 10,153
  assertions, zero failures, warnings, or risky tests** in 1,308.38 seconds.
- LDAP was explicitly excluded because the current application image does not
  contain the LDAP extension. Rebuilding that shared image would disrupt the
  separate screenshot/manual session, so LDAP remains an honest open gate.
- The executable database guard remained enabled. The final rate-limit tests
  also passed, confirming that suite-level cache cleanup did not weaken the
  within-test throttling contract.
- A one-shot extension-enabled, network-disabled container passed the complete
  LDAP group: **18 tests and 75 assertions**. It used the current source and
  dependencies read-only with temporary cache/storage mounts. The test was
  updated to verify the real extension's distinct DN and filter escaping rather
  than a namespace mock that PHP bypasses after loading the built-in function.

Retained logs:

- `storage/logs/v1-full-sqlite-nonldap-20260804.log`
- `storage/logs/v1-focused-failures-resolved-20260804.log`
- `storage/logs/v1-full-sqlite-nonldap-after-fixes-20260804.log`
- `storage/logs/v1-ldap-sqlite-20260804.log`

### Defects resolved by the full run

The 31 failures exposed both product defects and stale test assumptions. The
product fixes are:

- custom-field database names now receive a deterministic hashed fallback when
  transliteration produces an empty slug, including non-Latin-only names;
- QR/PDF bulk-asset responses have the correct controller return contract;
- partial component note updates validate the final persisted component
  definition/display name rather than requiring omitted fields;
- external-URL validation resolves Laravel's `:attribute` placeholder;
- the Slack settings Livewire component no longer declares page sections, and
  its page view now owns the title, header, and content sections;
- test setup clears shared cache and restores the configured locale after
  Laravel boots, preventing rate-limit and locale state from leaking between
  otherwise unrelated tests.

Stale tests were aligned with deliberate fork behavior: archived bulk statuses
clear legacy assignments, cloned assets retain their model while omitting the
retired asset-name create field, workflow run attributes use the definition
service, installation-complete authentication boundaries create their required
user, and translated API/UI assertions no longer depend on the factory's
intentional Dutch locale default.

### Checkout, identity, and company boundaries

Focused checkout and company-boundary coverage passes 79 tests with 220
assertions. The current implementation includes row-lock/recheck handling for
the supported checkout paths, rejects inactive or expired license checkout,
and prevents cross-company assignment. Identity, authentication, and storage
repairs from the interrupted audit are covered by the green repository run.

These results are SQLite application evidence. Database-engine concurrency
semantics still require the exact disposable MariaDB/MySQL job and production
rehearsal before release.

### Dependencies and assets

- Composer production audit: no unignored advisories and no abandoned package
  finding. Three Laravel advisories remain explicit reasoned ignores because
  their official fixes are checksum-pinned under `patches/` and covered by
  framework regressions.
- `guzzlehttp/guzzle` is locked at 7.15.2.
- npm production audit: 0 critical, 0 high, 1 moderate, and 4 low findings.
  The moderate item is the locally patched/tested inherited Bootstrap 3 line;
  the low items are inherited browser-crypto dependencies.
- `postcss` is 8.5.25, `fast-uri` is 3.1.5, and the brace-expansion adapter is
  5.0.9.
- `npm run prod` completes successfully.

All dependency and build results are worktree evidence. Release CI must repeat
them from the frozen candidate and retain the resulting SBOM and image digests.

### Static analysis

Full serial PHPStan analysis now completes instead of timing out or crashing.
It reports **5,887 errors across 549 files**. A focused run over the product
files changed by the full-suite repair reports 50 existing Eloquent/PHPDoc
baseline errors and did not identify a new error in the URL placeholder,
component calculation, QR response union, or custom-field fallback logic.

This is a measured backlog, not a pass. Before V1, the project must establish a
reviewed baseline and prevent new unowned errors, or reduce the candidate to an
accepted release threshold. Retained logs:

- `storage/logs/v1-phpstan-serial-after-refactor-20260804.log`
- `storage/logs/v1-phpstan-audit-fixes-20260804.log`

## Historical Finding Disposition

The implementation disposition recorded on 2026-07-23 remains current for
V1-01 through V1-15: workflow evidence, agent ingestion, dependency/container
boundaries, guarded tests, context-bound readiness, scoped component and
attachment operations, workflow nesting, work orders, operational roles,
component hierarchy integrity, foundation seeding, resumable workflow
migration, lifecycle semantics, and same-origin redirects are implemented and
covered by focused regressions.

The August full-suite result upgrades those findings from focused-only SQLite
evidence to integrated non-LDAP SQLite evidence. It does not replace the
per-finding production, browser, role, upload, migration, or supported-database
evidence listed in the July status and original
[V1 audit](v1-release-readiness-audit-2026-07-21.md).

## Remaining Release Gates

### 1. Supported automated matrix

- Run the complete test job against the exact disposable MariaDB/MySQL database
  named `snipeit_test` with the external-test guard explicitly enabled.
- Run a real-directory authentication smoke with the candidate image; the
  extension-enabled mocked LDAP group is green but is not an external service
  integration test.
- Retain PostgreSQL only as compatibility evidence until it is declared and
  proven as a supported production target.
- Run the browser/operator role matrix for login, scan, workflows, controlled
  media, component moves, sale transitions, work orders, and denied legacy
  mutation URLs.

### 2. Populated upgrade, restore, and rollback

On a recent sanitized clone and the exact intended MariaDB/MySQL image:

1. restore the database and uploads into isolation;
2. run schema, workflow copy, cutover, readiness, and lifecycle migrations;
3. validate row, value, relationship, foreign-key, and checkpoint parity;
4. interrupt between stages and prove retry convergence;
5. rehearse application rollback and database/upload restore;
6. retain timings, lock impact, migration list, image digest, and evidence.

Follow [workflow migration upgrade](workflow-migration-upgrade.md). Never use a
shared development or production database for this rehearsal.

### 3. Disposable production-profile rehearsal

Deploy `docker-compose.production.yml` with external MariaDB/MySQL,
authenticated Redis, TLS termination, narrow trusted proxies, file-backed
secrets, durable uploads/backups, and supervised queue/scheduler services.
Prove login, scan, workflow evidence, attachment authorization, queue delivery,
scheduler heartbeat, maintenance mode, monitoring, backup, clean restore, and
image rollback.

### 4. Artifact and security operations

- Scan source, image layers, final filesystems, dependencies, licenses, secrets,
  and SBOMs.
- Prove `.env`, key/certificate, dump, backup, and runtime-upload paths are not
  present in release images.
- Revalidate checksum-pinned Laravel backports from the candidate build.
- Name a private security contact, incident owner, release owner, and rollback
  owner.

### 5. Product, data, and release identity

- Resolve the remaining catalog placeholders and product decisions in
  `TODO.md`, including QR, scan feedback, naming/email convention,
  battery-health calculation, terminology, and legacy/media/license behavior.
- Review lifecycle mappings and the role-capability matrix with operators.
- Freeze a candidate commit, set approved release metadata, publish a changelog
  and upgrade note, and record supported PHP, database, browser, and deployment
  versions.
- Define the post-V1 Laravel 12 and upstream-porting roadmap.

## V1 Go/No-Go Checklist

- [x] Guarded non-LDAP in-memory SQLite suite: 2,120 tests and 10,153
  assertions.
- [x] Isolated extension-enabled LDAP group: 18 tests and 75 assertions.
- [ ] Exact supported MariaDB/MySQL automated job.
- [ ] Populated supported-database migration, interruption/retry, and rollback.
- [ ] Required browser/operator role matrix.
- [ ] PHPStan baseline owned and candidate delta accepted.
- [ ] Composer/npm audits and production asset build repeated from the frozen
  candidate.
- [ ] Candidate app/web images rebuilt with framework patches proven.
- [ ] Image, SBOM, license, secret, and content scans accepted.
- [ ] Disposable production deployment, backup, restore, and rollback accepted.
- [ ] Catalog, lifecycle, role, and remaining product decisions signed off.
- [ ] Security/release/rollback owners, version, changelog, supported matrix,
  and immutable digests published.

Until every unchecked item has retained evidence, use exact commit identifiers
for internal evaluation and do not call this worktree or an ad-hoc image V1.
