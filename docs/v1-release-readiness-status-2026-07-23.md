# V1 Release Readiness Status - 2026-07-23

## Decision

**Current decision: no-go for a public V1 tag, but continue release-candidate
development.**

The high-risk implementation findings in the
[2026-07-21 audit](v1-release-readiness-audit-2026-07-21.md) now have concrete
repairs and focused regression coverage in the current worktree. The remaining
release blockers are predominantly integrated and operational evidence gates:
a complete isolated test/matrix result, a populated MariaDB/MySQL
upgrade/rollback rehearsal, and a disposable deployment/backup-restore
rehearsal of the production profile.

An item is not considered closed merely because documentation changed:

- **Implemented** means the code/configuration contract has changed.
- **Focused verified** means a regression covering that contract passed against
  the guarded in-memory test database.
- **Release verified** requires the relevant full suite, supported database,
  built artifact, and external operational evidence.

No destructive database command, reset, or reseed was used during the
2026-07-23 repair session. All PHPUnit commands explicitly forced
`APP_ENV=testing`, `DB_CONNECTION=sqlite`, and `DB_DATABASE=:memory:` at the
Docker process boundary.

## Scope Snapshot

- Fork committed base inspected: `51208bff3166`.
- Common base with official Snipe-IT: `4fe7bfb8510a`.
- Official master inspected on 2026-07-23: `7cc0131e4908`.
- Official release tags observed through `v8.6.3`.
- Committed divergence at this snapshot: 326 fork-only commits and 4,550
  upstream-only commits.
- Fork changes from the common base: 808 files, +89,319/-7,715.
- Upstream changes from the common base: 8,019 files, +421,935/-232,529.

The current worktree contains substantial uncommitted V1 repair work in
addition to the committed fork snapshot. Official master uses Laravel 12 and
PHPUnit 11; this fork currently uses Laravel 11 and PHPUnit 10. A bulk upstream
merge is not a release strategy.

## Historical Finding Disposition

| Finding | Current implementation state | Release evidence still required |
| --- | --- | --- |
| V1-01 workflow evidence upload | Implemented and focused verified: decoded images are bounded, re-encoded, stored privately, served through an authorized controller, and cleaned up atomically; executable upload locations are blocked in bundled web-server configurations. | Authenticated production-profile upload/download smoke and image/content scan. |
| V1-02 invalid agent reports/readiness | Implemented and focused verified: fail-closed token handling, complete validation before persistence, distinct/bounded results, transactionality, and no empty/partial passing run. | Included in the full isolated suite and external API smoke. |
| V1-03 critical/high dependencies | Implemented and focused verified: dependency upgrades remove the previously reported critical/high graph; two exact official Laravel fixes are checksum-pinned and behavior-tested; the abandoned HTML package has been removed. | Rerun audits and patch checks from the final release image/SBOM. |
| V1-04A Docker context exposure | Implemented and configuration verified: recursive exclusions cover environments, backups, database files, keys/certificates, logs, and runtime uploads; immutable production targets exist. | Content-aware secret scan of every image layer/final filesystem and credential rotation for any previously shared image. |
| V1-04B unsafe test target | Implemented and focused verified: pre-bootstrap and post-Laravel guards allow only local in-memory SQLite or explicitly marked exact-name disposable external CI databases; Dusk has a separate target guard. | Complete full suite/matrix evidence and a final read-only shared-database canary. |
| V1-05 stale sale readiness | Implemented and focused verified: readiness is bound to current model/profile/preset/items/components/expected state/lifecycle context through a current context hash and recomputation. | Full suite plus representative browser role/status-transition smoke. |
| V1-06 component API scope/lifecycle | Implemented and focused verified: company scope, lifecycle permissions, condition/serial invariants, and dedicated lifecycle services are enforced. | Full suite and production-role API smoke. |
| V1-07 model-number attribute deletion | Implemented and focused verified with explicit policy and nested scoping. | Full suite. |
| V1-08 nested workflow item binding | Implemented and focused verified with correct binding and same-asset scoping. | Full suite. |
| V1-08A attachment mutation | Implemented and focused verified with object-specific upload/manage authorization, company/visibility scope, and transformer capability flags. | Full suite and production-profile file smoke. |
| V1-09 work-order visibility | Implemented and focused verified for create/read/mutation visibility boundaries. | Full suite and browser role matrix. |
| V1-10 seeded operational roles | Implemented and focused verified through additive least-privilege production groups and a capability matrix. | Operator acceptance of final role assignments. |
| V1-11 component hierarchy integrity | Implemented and focused verified: transactional reparent/delete behavior, expected-state preservation, and a read-only `components:audit-integrity` command. | Run the read-only audit on a recent sanitized dataset and review every finding before release. |
| V1-12 foundation seeding | Implemented and focused verified: additive ownership-aware production seeders, separate guarded disposable seeders, preservation of operator data, and explicit catalog verification status. | Rehearse the exact foundation seed plan on a sanitized populated clone; migrate existing placeholder rows deliberately. |
| V1-13 workflow migration | Implemented and focused verified on SQLite: schema, verified copy, and FK cutover are separate/resumable; parity/checkpoints/fail-closed rollback are present; legacy tables are retained through a compatibility window. | Populated interruption/retry/upgrade/rollback rehearsal on the exact supported MariaDB/MySQL image. |
| V1-14 mutable English status semantics | Implemented and focused verified with stable lifecycle-stage data, API/UI exposure, and seeded semantics. | Upgrade rehearsal and operator review of existing custom status mapping. |
| V1-15 unsafe return redirects | Implemented and focused verified through a shared same-origin redirect boundary. | Full suite and reverse-proxy smoke. |

## Additional Contract Repairs

The repair pass also addressed contradictions that were not separated into
their own historical finding:

- legacy asset checkout/checkin/audit mutation routes and create/update
  assignment aliases are removed or rejected before mutation; compatibility
  cleanup must not create new check-in history;
- maintenance is retained only as authorized read-only historical data; its web
  and API mutation routes, UI actions, capability flags, and generic attachment
  mutation paths are removed;
- encrypted custom-field creation cannot store attacker-supplied plaintext when
  the creator lacks encrypted-field visibility, while encrypted defaults remain
  usable;
- accessory creation applies the fork's feature permission and company scope;
- image orientation no longer fails when the optional EXIF function is absent;
- branding favicon upload/persistence/deletion and setup-page HTTP checks have
  deterministic tests;
- the inherited `laravelcollective/html` dependency and macro provider were
  replaced by a small first-party escaped select helper;
- application/Composer/npm metadata and `config/version.php` now identify the
  Inbit fork as `v0.9.0-dev` rather than impersonating an old Snipe-IT beta
  build;
- PHPStan now loads the installed `larastan/larastan` extension path; stale
  Psalm/PHPMD expectations without installed executables were removed.

## Verification Snapshot

### Dependency and build checks

As run on 2026-07-23:

- `composer validate --strict`: valid.
- `composer patches-doctor`: usable patcher configuration, no plain HTTP patch
  URLs.
- Composer production and full audits: zero unignored advisories and zero
  abandoned packages.
- Three Laravel advisory identifiers are explicit, reasoned ignores only
  because the exact upstream fixes are stored under `patches/`,
  checksum-pinned in `patches.lock.json`, applied during Composer install, and
  covered by framework-level behavior tests.
- npm production audit: 0 critical, 0 high, 1 moderate, 4 low.
- npm full audit: 0 critical, 0 high, 7 moderate, 5 low.
- The remaining production findings are Bootstrap 3's moderate advisory plus
  four low transitive legacy browser-crypto findings. The safe npm update was
  applied; the remaining Bootstrap fix is a major UI migration.
- `npm run prod`: Laravel Mix reported a successful production asset compile.

These are point-in-time worktree results. Release CI must regenerate them from
the immutable candidate.

### Focused regression evidence

Guarded focused suites pass across the repaired areas, including:

- test-environment guard and hostile-target rejection;
- workflow evidence, agent ingestion, readiness, sale transitions, nested
  binding, and workflow migrations;
- component lifecycle, API mutation authorization, hierarchy integrity,
  expected-state deletion/reparenting, and the integrity command;
- attachment, asset-image, work-order, scan, accessory, model-number attribute,
  encrypted-field, status-semantic, seeder, and operational-role boundaries;
- production container/Composer-patch configuration and direct Laravel
  security-backport behavior;
- branding/setup settings behavior and the first-party form-select
  replacement.

The first-party form/dependency replacement was additionally verified with 45
tests and 140 assertions across the helper, user edit, modal, and setup-page
surfaces. The workflow migration suite passed 8 tests and 42 assertions. The
complete repository-wide guarded suite is still a release gate until its final
result is recorded below.

## Remaining Release Gates

### Gate 1 - Integrated automated evidence

- Run the complete PHPUnit suite against guarded in-memory SQLite and retain the
  report.
- Run the exact disposable `snipeit_test` MySQL/MariaDB job.
- Keep PostgreSQL CI as compatibility evidence, but do not declare PostgreSQL
  production support until its migration gaps and populated rehearsal pass.
- Run the production-role browser matrix for login, scan, workflow execution,
  evidence/media access, component moves, sale transitions, work orders, and
  denied legacy mutation URLs.
- Resolve any non-environment test skips. The two Dusk tests that require an
  explicit live URL must run in the browser job rather than silently counting
  as unit/feature evidence.
- Retain PHPStan output and either fix new errors or establish a reviewed
  inherited baseline; do not report a timed-out run as a pass.

### Gate 2 - Populated database upgrade and rollback

Use a recent sanitized clone and the exact intended MariaDB/MySQL image:

1. verify backup inventory and restore it in isolation;
2. run the workflow schema/copy/cutover migrations;
3. validate row/value/relationship/FK parity and readiness/status backfills;
4. interrupt after each stage and prove a retry converges;
5. rehearse application rollback and database/upload restore;
6. record timings, lock impact, image digest, migration list, and evidence.

Follow [workflow migration upgrade](workflow-migration-upgrade.md). Do not use a
shared development or production database for this rehearsal.

### Gate 3 - Disposable production-profile rehearsal

Deploy `docker-compose.production.yml` with:

- external MariaDB/MySQL and authenticated Redis;
- real TLS termination and narrow trusted-proxy addresses;
- file-backed APP/database/Redis/Passport secrets;
- durable public/private upload and backup storage;
- queue, scheduler, health, centralized logs, monitoring, and restart alerts;
- encrypted off-host database/upload/key backups.

Prove login, scan, workflow evidence, attachment access, queue delivery,
scheduler heartbeat, maintenance mode, backup, clean restore, and image
rollback. Retain artifact digests and smoke evidence.

### Gate 4 - Release artifact and security operations

- Scan source, image layers, final filesystems, dependencies, licenses, and
  SBOMs.
- Prove prohibited `.env`, key/certificate, dump, backup, and runtime upload
  paths are absent from the release images.
- Rotate any credential/key that may have entered an older shared image.
- Publish a private security contact and named incident owner.
- Revalidate the checksum-pinned Laravel backports whenever the framework lock
  changes.

### Gate 5 - Product/data decisions

- Replace or deliberately migrate legacy catalog placeholder MPN/SKU values;
  unverified placeholders are excluded from normal production seeding but
  existing rows are not silently destroyed.
- Decide the QR layout, mobile scan feedback, user naming/email convention,
  battery-health calculation, and tests-versus-tasks terminology.
- Complete the planned license/media/legacy-tab product decisions in `TODO.md`.
- Map existing custom status labels to stable lifecycle stages and review the
  production role-capability matrix with operators.

These are product decisions, not reasons to re-enable removed legacy mutation
paths.

### Gate 6 - Release identity and support boundary

- Select and freeze the candidate commit.
- Update `config/version.php` from `v0.9.0-dev` to the approved release
  candidate/release metadata through a reproducible build step.
- Produce an upgrade note/changelog and immutable image digests.
- Record supported MariaDB/MySQL, PHP, browser, and deployment versions.
- Define the post-V1 Laravel 12/upstream-porting roadmap.

## V1 Go/No-Go Checklist

A V1 tag is a **go** only when every item below has retained evidence:

- [ ] Clean guarded full PHPUnit result.
- [ ] Supported MariaDB/MySQL test and populated migration rehearsal.
- [ ] Required browser/operator role matrix.
- [ ] PHPStan result reviewed; no new unowned errors.
- [ ] Composer/npm audits and production asset build from the candidate.
- [ ] Production app/web images rebuilt from the candidate with patches proven.
- [ ] Image/SBOM/license/secret/content scans accepted.
- [ ] Disposable production deployment, backup, restore, and rollback accepted.
- [ ] Catalog/status/role migration decisions signed off.
- [ ] Security contact, release owner, version, changelog, and immutable digests
  published.
- [ ] Read-only shared-environment canary unchanged after test work.

Until then, use exact commit identifiers only for internal evaluation and do
not call a worktree or ad-hoc image “V1.”
