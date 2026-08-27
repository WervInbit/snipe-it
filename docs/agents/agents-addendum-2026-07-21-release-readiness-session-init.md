# Session Init - 2026-07-21 Release Readiness Audit

## Context

- Reinitialized on `AGENTS.md`, `PROGRESS.md`, `docs/fork-notes.md`, the current branch and worktree, and release-facing repository files.
- The fork has accumulated substantial workflow, component hierarchy, specification, production-seeding, permissions, scanning, and operator UX changes beyond upstream Snipe-IT.
- The user is testing a local production-like environment and wants an extensive gap analysis before calling the system V1.

## Audit Scope

- Inventory the shipped fork delta and map documentation claims to implementation and tests.
- Find explicit and implicit unfinished work, stale compatibility paths, missing validation, weak test coverage, and release hygiene risks.
- Compare this repository with current upstream Snipe-IT and identify inherited versus fork-owned maintenance concerns.
- Assess the existing README and define or implement a fork-specific replacement once the product and deployment claims are evidence-backed.
- Produce a prioritized V1 release gate with reproducible validation evidence.

## Coordination And Constraints

- Another agent is actively working on operator guides; do not edit `docs/manuals/operator-guides/`, its generated artifacts, or guide planning files.
- Preserve all pre-existing worktree changes and untracked local production/test artifacts.
- Do not inspect or expose the contents of untracked environment backup files.
- Do not run destructive database commands. Before any Docker PHPUnit run, clear Laravel caches and verify the isolated test database as required by `AGENTS.md`.

## Initial State

- Branch: `master`, aligned with `origin/master` at session start.
- Existing dirty state includes guide documentation/output work, upload placeholder changes, local database backups, and environment backup files; none are owned by this audit.
- The first phase is read-only inventory and targeted verification; material implementation fixes will be separated from findings to avoid masking release gaps.

## Outcome

- Release decision: no-go for V1 at audited commit `51208bff3166f37eac9452c646e7f89303d2321e`.
- Added `docs/v1-release-readiness-audit-2026-07-21.md` with prioritized findings, validation evidence, upstream comparison, deployment review, and release exit criteria.
- Replaced the inherited upstream-focused `README.md` and `SECURITY.md` with fork-specific pre-V1 documents.
- The highest remaining release risks are executable upload handling, critical/high dependency advisories, invalid workflow readiness state, authorization/tenant gaps, and unsafe production seed/migration behavior. The ineffective test-database guard and secret-bearing Docker build context found during the audit were remediated in this worktree; historical-image credential review and automated image secret scanning remain open.

## Test Safety Incident

- A separate Artisan `--env=testing` preflight reported SQLite, but direct PHPUnit resolved an unsafe effective local/MySQL configuration because the old PHPUnit bootstrap did not synchronize or verify the actual target before Laravel.
- `Tests\TestCase::guardAgainstUnsafeTestingConfig()` validates the contents of `.env.testing`, not the effective booted environment/connection. `LazilyRefreshDatabase` consequently refreshed `snipeit_prod_work`.
- A read-only check found `settings=0`, `users=0`, and `assets=0`. DB-dependent work stopped immediately.
- MariaDB binary logging is off. The newest local SQL snapshot found by filename and metadata is `prodbak/db-snapshots/snipeit_prod_work_pre_docker_update_20260609_094733.sql`, modified 2026-06-09 09:47:35 +02:00, size 781,490 bytes.
- The user explicitly chose a clean reseed instead of restoring the snapshot. `ProductionFoundationSeeder`, `ProductionDemoUserSeeder`, and `DemoAssetsSeeder` completed after the required preflight. Current counts are `settings=1`, `users=5`, `assets=10`, and `workflow_runs=10`; administrator login/dashboard browser smoke passed.
- Added a dependency-free PHPUnit safety bootstrap, process and booted-application guards, in-memory-only local SQLite boundary, explicit rejection of the persistent application SQLite file, explicit external-CI database marker/name, and a separate Dusk guard. A hostile `mysql/snipeit_prod_work` command now aborts before Laravel and guarded runs do not change local MySQL counts.
- Guarded SQLite reruns completed after remediation. Asset/workflow/scan: 71 tests, 222 assertions, 4 stale fixture errors and 7 failures. Work-order/component/catalog: 75 tests, 455 assertions, 1 failure. The original two redirect failures disappeared; local MySQL seed counts were unchanged after both batches.
- Repaired every failure surface from those guarded batches: current component-backed agent fixtures, controlled rejection of disabled legacy assignment fields, active model-number selection, explicit deprecated factory presets, locale-independent assertions/messages, and current-over-legacy component condition labels. The final consolidated repair slice passed 93 tests with 320 assertions at `testing|sqlite|sqlite|:memory:`; guard coverage includes missing markers, conflicting PHP environment representations, persistent SQLite rejection, clean-clone Dusk file creation, and PostgreSQL CI approval.
- Replaced demo asset truncation/FK disabling with constraint-aware deletion, coherent removal of runtime component hierarchies, preserved work-order snapshots with null live-asset links, and non-reused asset IDs; the new SQLite regression passed. The reseeded local database remains `settings=1`, `users=5`, `assets=10`, and `workflow_runs=10`.
- Expanded `.dockerignore`, rebuilt the audit image, corrected recursive database-file patterns found by the first inspection, and verified zero files in the checked environment, backup/database, certificate, `prodbak`, OAuth-key, and runtime-upload classes. Updated the local entrypoint and README clean-clone flow for development dependencies, Compose database settings, and Passport key bootstrap.
