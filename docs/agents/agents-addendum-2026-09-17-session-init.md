# Agent Addendum - 2026-09-17 Session Init

## Scope

- Debug reported behavior and implement small, focused bug fixes.
- Establish the exact reproduction and expected behavior before each edit.
- Prefer focused regression tests for touched paths and review documentation
  impact when behavior changes.

## Starting State

- Branch: `master`.
- Commit: `bda2d21e68` (`Document Workflow Production Deployment`).
- The worktree already contains a substantial uncommitted operator-guide batch,
  including documentation, scripts, evidence, and generated draft PDFs. Those
  changes are outside this session's default scope and must be preserved.

## Safety Boundary

- Do not revert, rewrite, or clean unrelated worktree changes.
- Do not run destructive database commands without explicit approval in the
  current user message and the required database preflight.
- Before Docker PHPUnit, clear Laravel caches and pass `APP_ENV=testing`,
  `DB_CONNECTION=sqlite`, and `DB_DATABASE=:memory:` explicitly at the command
  boundary. Keep the executable test database guard enabled.
- Production access, migrations, deployments, and live data changes are not
  authorized by this initialization.

## Initial Validation

- Initialization used read-only file and Git inspection only.
- No application tests, builds, services, database commands, or runtime checks
  were run because no defect has been specified yet.

## Workflow Dependency Select2 Fix

- Investigated the workflow-profile dependency picker in its long Bootstrap
  modal. Select2 4.0.13 was using the scrollable `.modal` as both dropdown
  positioning parent and a scroll watcher, causing an offset dropdown and a
  handler that repeatedly reset modal scrolling.
- Changed only the workflow-profile page: the dropdown is positioned relative
  to `.modal-content`, and its open event removes Select2's namespaced scroll
  lock from the modal. Focus containment and searchable multi-select behavior
  remain intact.
- Added a focused page-render regression test. Docker preflight resolved to
  `APP_ENV=testing`, `DB_CONNECTION=sqlite`, and `DB_DATABASE=:memory:` after
  cache clearing. `ManageWorkflowProfilesTest` passed 14 tests / 71 assertions;
  Blade view caching passed and compiled views were cleared afterward.
- `https://dev.inbit/admin/workflow-profiles` returns the expected unauthenticated
  redirect to `/login`. An authenticated hard-reload interaction check remains
  pending because this session has no browser authentication context.
