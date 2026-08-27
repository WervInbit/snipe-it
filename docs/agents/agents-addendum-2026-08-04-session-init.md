# Agent Addendum - 2026-08-04 V1 Audit Status Recovery

## Session Context

- Resumed in status-review mode after the user-requested pause on 2026-07-28.
- Re-read `AGENTS.md`, `PROGRESS.md`, `docs/fork-notes.md`, and the July 28
  recovery checkpoint before inspecting current state.
- This opening block is read-only apart from the required session log. No
  implementation, test, migration, reset, restore, or seed is authorized by
  the status request.
- Operator-guide files remain outside this audit scope.

## Status Review Scope

- Compare the current commit and dirty worktree with the July 28 checkpoint.
- Confirm that no audit process remains active and recheck the shared database
  canary without mutation.
- Report implemented, verified, failing, and still-open V1 gates before
  implementation resumes.

## Recovered Status

- Branch and committed base remain `master` at
  `51208bff3166f37eac9452c646e7f89303d2321e`. No audited source, configuration,
  or test file has a write time later than the 2026-07-28 pause.
- The intentionally dirty worktree currently has 1,067 status entries: 789
  modified, 75 deleted, and 203 added/untracked. This is preserved work, not a
  clean or immutable release candidate.
- No subagent, PHPUnit process, or PHPStan process is active. Docker `app`,
  `db`, and `web` services are running; the database service is healthy.
- The read-only shared database canary remains exactly
  `local|mysql|snipeit_prod_work` with
  `settings=1|users=5|assets=10|workflow_runs=10|workflow_results=36`.
- Retained evidence is intact: diagnostic full run 1,873 passed/61 failed;
  storage/import lane 115 tests with one error/seven failures followed by
  unverified corrections; test-matrix lane 56/56 green; identity lane 62
  passed/15 failed.
- Repairs for the original high-risk audit findings and additional July 28
  deployment/security gaps remain implemented in the worktree, but root
  release-infrastructure changes and several post-failure corrections still
  need focused verification.
- Supported accessory/component/consumable legacy checkout controllers still
  lack the upstream row-lock/recheck concurrency guard. The newer component
  lifecycle service already uses row locks. Inactive/expired license checkout
  semantics are also not implemented.
- All eleven dated V1 go/no-go evidence items remain unchecked. Nine product
  decisions remain open in `TODO.md`. The fork-specific README is in place but
  still points to the 2026-07-23 status and must be refreshed after current
  evidence is produced.
- `git diff --check` still reports only the known extra blank line at EOF in
  `resources/views/users/confirm-bulk-delete.blade.php`.
- No implementation or test was run during this status review.

## Screenshot Runtime Recovery

- Investigated the operator-guide agent's `Application upgrade required:
  database migrations are incomplete` response. The guard was accurate: seven
  fork migrations were pending in `local|mysql|snipeit_prod_work`, including
  the required lifecycle-stage migration.
- Read all pending migrations and their workflow upgrade support code before
  changing the database. The read-only data preflight found zero nonce rows,
  an absent legacy test schema, an already-correct workflow photo foreign key,
  zero asset image photo references, zero readiness flags set true, zero
  legacy asset status-history rows, and zero configured webhook endpoints.
- Saved a pre-migration SQL snapshot outside the repository at
  `C:\dev\snipe-it-fork-db-backups\snipeit_prod_work-pre-migrate-20260804-094302.sql`.
  It is 346,890 bytes with SHA-256
  `06647D7325F6594AB1D4521FAD0FA87CEE4545D89DED44447498C7B80A45018A`.
- Applied the seven pending migrations with the standard forward-only Laravel
  migration command and cleared application caches. No destructive database
  lifecycle command or seed was run.
- Postflight confirmed all seven records, the new schema columns and nonce
  unique index, the expected workflow checkpoints, and five lifecycle label
  mappings. Core canaries remained unchanged; `asset_status_events` remained
  at 10 rows and `workflow_audits` remained at 476 rows.
- A fresh anonymous HTTP request followed the application redirect to
  `https://dev.inbit/login`, returned status 200, and did not contain the
  upgrade-required response. Screenshot work is unblocked.
- Follow-up inspection found that the expected `codex` screenshot user did not
  exist, including among soft-deleted records. Recreated it as an active local
  screenshot account, attached the existing `Admin` group, and preserved the
  established credentials requested by the user. Password-hash validation and
  an application-level authentication attempt both succeeded. The active user
  count therefore increased intentionally from 5 to 6.
- The first authenticated asset request then exposed an unrelated application
  regression: `resources/views/hardware/view.blade.php` called the removed
  Laravel Collective `link_to_route()` helper while rendering a status-event
  actor. A repository scan found 20 remaining call sites despite the package
  having already been retired.
- Replaced all remaining legacy calls. Blade templates now render standard
  escaped anchors, while presenters use the new escaped
  `App\Support\RouteLink` renderer. Added a label-escaping unit regression and
  an asset detail regression with a status-event actor.
- PHP syntax checks passed for all changed PHP files; the two focused tests
  passed with six assertions against guarded in-memory SQLite. Live browser
  verification using the active screenshot session rendered asset `DEMO-001`
  successfully with no server-error response, and the page remains open for
  screenshot handoff.

## Audit Implementation and Verification Continuation

- Resumed implementation only after the status checkpoint. Preserved the
  separate operator-guide runtime: this audit block did not migrate, reset,
  restore, seed, restart, or rebuild the live screenshot environment.
- Completed checkout concurrency, inactive-license, identity/storage, and
  company-boundary repairs. The checkout/company lane passes 79 tests with 220
  assertions against the guarded in-memory SQLite database.
- The first repository-wide non-LDAP run completed with 2,087 passes and 31
  failures. All failures were classified and addressed; none were skipped or
  closed merely to improve the count.
- Product repairs cover non-Latin custom-field database names, bulk asset QR
  responses, partial component note updates, external-URL validation messages,
  and Slack settings Blade/Livewire composition. Test setup now also removes
  cache and locale leakage between test cases without changing within-test
  rate-limit behavior.
- Stale tests were aligned with deliberate fork behavior and translated output
  was made locale-independent. The focused repair set passes 161 tests with
  724 assertions.
- Final repository-wide non-LDAP result: 2,120 tests passed with 10,153
  assertions in 1,308.38 seconds, with no failures, warnings, or risky tests.
  LDAP remains open because the shared app image lacks the extension and an
  image rebuild would disrupt the screenshot session.
- Full serial PHPStan completes with 5,887 errors across 549 files. This is a
  measured inherited/fork baseline, not release-clean evidence. The focused
  repair-file run reports 50 existing Eloquent/PHPDoc baseline errors and no
  newly identified repair-contract defect.
- Current production dependency evidence remains 0 unignored Composer
  advisories, 0 npm critical/high, 1 moderate, and 4 low; the production asset
  build passes.
- Published `docs/v1-release-readiness-status-2026-08-04.md` and updated the
  README to point to it. Public V1 remains no-go until the supported
  MariaDB/MySQL, LDAP, browser/operator, upgrade/rollback, deployment/restore,
  artifact scan, static-baseline, product decision, and ownership gates pass.

## Matrix Continuation and Pause Checkpoint

- Used the already-built extension-enabled audit image in a one-shot,
  network-disabled container with read-only source/dependencies and temporary
  runtime mounts. The LDAP group initially exposed an extension-specific test
  harness issue: once PHP loads the built-in `ldap_escape`, the namespace mock
  is not used consistently across the suite.
- Replaced that mock with exact real-extension DN and filter escape
  expectations. The complete LDAP group passes 18 tests with 75 assertions;
  evidence is retained in `storage/logs/v1-ldap-sqlite-20260804.log`.
- Provisioned an isolated MariaDB 11.4.7 container on a private network with no
  host port and only the exact disposable `snipeit_test` schema. Preflight
  confirmed `testing|mysql|snipeit_test`; all migrations and Passport
  bootstrap completed before the strict suite began.
- At the user's pause, the partial run had recorded 70 passing classes and
  seven failing classes: importer safe-status selection, LDAP state/harness,
  company ID resolution, and four accessory company-boundary UI classes. The
  process had not reached a final summary or complete stack traces.
- Stopped the test process and removed the exact temporary app/database
  containers, anonymous database volume, and audit network. The shared
  screenshot services remain up and their database was outside the audit
  network. Resume from `storage/logs/v1-mariadb-full-20260804.log` with focused
  MariaDB reproductions before restarting the full matrix.
