# Agent Addendum - 2026-08-11 V1 Audit MariaDB Continuation

## Session Context

- Resumed the V1 implementation/release audit from the user-requested
  2026-08-04 pause checkpoint.
- Re-read `AGENTS.md`, `PROGRESS.md`, `docs/fork-notes.md`, the current V1
  readiness status, and retained database-matrix evidence before acting.
- The operator-guide agent remains a separate scope. Existing manual files,
  screenshots, approvals, and generated artifacts will not be changed here.
- The worktree remains intentionally dirty on committed base
  `51208bff3166f37eac9452c646e7f89303d2321e`; no existing change will be
  reverted, staged, or committed implicitly.

## Recovered Audit Checkpoint

- Guarded non-LDAP in-memory SQLite: 2,120 tests and 10,153 assertions passed.
- Extension-enabled LDAP group: 18 tests and 75 assertions passed in a
  network-disabled one-shot container.
- The partial MariaDB 11.4.7 run reached feature tests before the pause and
  retained seven failing classes in
  `storage/logs/v1-mariadb-full-20260804.log`:
  `AssetImportTest`, `LdapTest`, `GetIdForCurrentUserTest`,
  `CreateAccessoryWithFullMultipleCompanySupportTest`, `DeleteAccessoryTest`,
  `ShowAccessoryTest`, and `UpdateAccessoryTest`.
- The partial run ended before PHPUnit printed complete exception traces or a
  final count. Focused MariaDB reproductions are required before restarting the
  full matrix.

## Runtime State and Safety Boundary

- Docker Desktop is currently stopped; the Docker engine socket is absent.
- No audit container, network, PHPUnit process, PHPStan process, or disposable
  database is active.
- Static diagnosis will be completed first. If Docker is started later, all
  database tests must use a private network, exact disposable database name
  `snipeit_test`, explicit external-test guard, no published database port, and
  no shared database volume.
- The shared screenshot database `snipeit_prod_work` is outside test scope. No
  reset, refresh, wipe, restore, seed, or destructive shared-database command
  is authorized.

## Planned Continuation

1. Inspect the seven failure classes and MariaDB transaction/DDL test harness.
2. Reproduce the failures in focused isolated MariaDB runs when Docker is
   available.
3. Fix product defects or test-isolation defects with focused coverage.
4. Restart the complete supported MariaDB matrix only after the focused set is
   green.
5. Update readiness and progress evidence without overstating incomplete
   operational gates.

## Implementation And Verification

- Reproduced the seven retained MariaDB failure classes on a fresh isolated
  MariaDB 11.4.7 database named exactly `snipeit_test`.
- Replaced foreign-key-incompatible status truncation in `AssetImportTest`
  with row deletion and hardened `tests/Support/Settings.php` so MariaDB DDL
  commits cannot leave stale settings rows or a cache missing database
  defaults. Added `SettingsIsolationTest` coverage.
- The original focused failure slice plus the settings regression passed 46
  tests with 121 assertions.
- The first complete strict rerun produced 63 failures. After refreshing the
  newly created settings model from the database, a 25-class reproduction
  slice reduced the remaining inventory to two `DemoAssetsSeederReadinessTest`
  failures and one read-only upload-fixture failure: 158 passed, 3 failed.
- Reworked `DemoAssetsSeeder` so Ready-for-Sale and Sold assets are staged in
  Being Processed, receive complete current workflow evidence, recompute their
  readiness flag, and only then transition through the real guarded model
  path. Added final-status assertions. With a writable upload tmpfs and a 512
  MB test-process limit, the residual slice passes 16 tests with 177
  assertions.

## Full-Run Harness Diagnosis

- An attempted full run using the stale local development image and an empty
  storage tmpfs completed 2,139 tests but was not valid release evidence: 626
  errors and five failures were Passport `Invalid key supplied` cascades, and
  seven errors plus two failures were caused by the image lacking PHP LDAP.
- Repository CI already documents both required conditions: the MariaDB job
  installs test-only Passport keys/clients, and the current app/production
  Dockerfiles install LDAP. The previously built extension-enabled image
  `snipe-it-fork:v1-audit-20260728` was therefore selected for the corrected
  local harness.
- The corrected harness passed the complete LDAP class plus representative
  Passport-protected API tests: 27 tests with 109 assertions.

## User-Requested Pause Checkpoint

- The definitive strict MariaDB run used a fresh private MariaDB 11.4.7
  container, exact database `snipeit_test`, no published database port or
  shared database volume, extension-enabled audit image, generated test-only
  Passport material, read-only source/dependencies, writable temporary runtime
  and upload mounts, and a 512 MB PHPUnit memory limit.
- The run was stopped for device shutdown at 488 of 2,139 tests (22%). All 488
  executed tests passed; no failure or error marker had appeared. Resume from
  the same command contract rather than from the invalid-key run. Evidence:
  `storage/logs/v1-mariadb-full-definitive-20260811.log`.
- Removed the active test app, all four disposable audit MariaDB containers,
  and `snipeit_audit_20260811` after verifying their private network and exact
  `snipeit_test` targets. Shared `snipeit_app`, `snipeit_web`, and
  `snipeit_db` remained running and were not modified.
