# Agent Addendum - 2026-08-13 V1 Audit MariaDB Resumption

## Session Context

- Resumed the V1 implementation and release audit from the clean 2026-08-11
  shutdown checkpoint.
- Re-read `AGENTS.md`, `PROGRESS.md`, `docs/fork-notes.md`, and the complete
  prior MariaDB continuation addendum before acting.
- The separate operator-guide/manual scope and the existing dirty worktree are
  preserved. No existing change will be reverted, staged, or committed.
- Committed base remains `51208bff3166` on `master`.

## Recovered Verification Checkpoint

- The importer/settings isolation repair slice passes 46 tests with 121
  assertions on MariaDB.
- The repaired demo-seeder and workflow-photo residual slice passes 16 tests
  with 177 assertions.
- The extension-enabled LDAP and representative Passport/API harness slice
  passes 27 tests with 109 assertions.
- The corrected strict MariaDB run was stopped for device shutdown at 488 of
  2,139 tests; all 488 executed tests passed. Evidence is retained in
  `storage/logs/v1-mariadb-full-definitive-20260811.log`.

## Runtime And Safety Boundary

- Docker is reachable after restart, but shared `snipeit_app`, `snipeit_web`,
  `snipeit_db`, and `snipeit_node` are stopped. They will remain untouched.
- The definitive MariaDB gate will be restarted from a fresh private network
  and ephemeral MariaDB 11.4.7 container, with no published database port, no
  shared database volume, and the exact disposable database `snipeit_test`.
- The application audit container will use the extension-enabled
  `snipe-it-fork:v1-audit-20260728` image, explicit external-test guard,
  generated test-only Passport material, read-only source/dependencies,
  writable temporary runtime/upload paths, and a 512 MB PHPUnit memory limit.
- No shared or production-like database command is authorized or required.

## Planned Continuation

1. Complete the strict supported MariaDB suite from a fresh isolated database.
2. Classify and reproduce any failures before further implementation.
3. Run focused SQLite regressions for the changed isolation/seeder paths.
4. Continue the outstanding V1 release-gate and documentation audit.
5. Record verified results and remove only the private audit resources.

## Completed Continuation

- The definitive private MariaDB 11.4.7 run passed 2,139 tests with 10,238
  assertions. The affected residual classes pass 28 tests with 227 assertions,
  and the current SQLite risk slice passes 50 tests with 421 assertions.
- Repaired a Faker-driven importer fixture flake and a one-second API throttle
  clock-boundary assertion without weakening either product contract.
- Adopted a 5,817-error PHPStan level 4 baseline, corrected the remaining
  throwing service return contract, passed the exact CI analyzer command, and
  proved with a removed negative-control file that new errors remain blocking.
- Remediated the newly published Composer and npm high-severity findings.
  Fresh dependency installs, strict Composer checks, focused application tests,
  Node tests, current audits, and the production browser build pass.
- Current release disposition and evidence are recorded in
  `docs/v1-release-readiness-status-2026-08-13.md`. Public V1 remains a no-go
  until the release-environment and owner gates listed there are complete.
- The follow-on isolated production-profile rehearsal is recorded in
  `docs/v1-production-profile-rehearsal-2026-08-13.md`. It completed first
  deployment, HTTPS/login, maintenance, backup/restore, rollback, queue, and
  scheduler checks after repairing PHP-FPM startup, maintenance health,
  queued-mail serialization, and the missing failed-job schema.
