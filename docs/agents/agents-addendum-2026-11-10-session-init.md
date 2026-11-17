# Agent Addendum - 2026-11-10 - Legacy Workflow Evidence

## Session boundary

- Source: clean managed worktree
  `C:\Users\Gebruiker\.codex\worktrees\legacy-workflow-attestations\snipe-it-fork`.
- Branch: `codex/legacy-workflow-attestations` from `origin/master`
  `c539b9cd18`.
- Production is out of scope until local implementation and qualification are
  complete. No production host, database, CUPS node, or printer was changed.

## Implemented contract

- Only the single-asset web create form accepts legacy workflow evidence.
- The fieldset is collapsed by default, model-dependent, and selects exact
  profile items with one required source/reason and optional historical date.
- Asset/evidence persistence is atomic. No `workflow_runs` or
  `workflow_results` rows are created at intake.
- Per-item hashes bind evidence to model/model-number, requirement content,
  expected values, and relevant component context without invalidating an
  existing item merely because an unrelated item is added.
- One resolver owns readiness, progression, mixed runs, summaries, and visible
  legacy/mixed/pending/failure states. Real results supersede legacy evidence;
  revocation is non-destructive and audited.
- QR artifact generation is after the database commit. Printing remains the
  existing separate explicit post-create action.

## Verification checkpoint

- Docker preflight: `APP_ENV=testing`, `DB_CONNECTION=sqlite`,
  `DB_DATABASE=:memory:`.
- PHP syntax: all changed PHP files pass `php -l`.
- Blade compilation: `php artisan view:cache --env=testing` passes.
- New focused suite initially passed 7/8; the only failure was a missing local
  Mix manifest while rendering a 403 page. After the local asset build and
  JSON authorization assertion, the endpoint test passed.
- New domain/create tests cover legacy-only readiness, dependency satisfaction,
  mixed runs, unrelated-item stability, requirement staleness, permanent
  supersession, revocation materialization, authorization, atomic web creation,
  and rollback of stale selections.
- Adjacent workflow, agent, readiness, partial-update, and latest-summary tests
  were run. Two compatibility differences were fixed: profile-less historical
  runs keep result-only completion, and a profile with no run/evidence retains
  the existing profile-level missing API payload.
- Final guarded non-LDAP SQLite gate: `2,281` passed, `11,251` assertions,
  zero failures, and 2 expected skips for tests that deliberately require the
  disposable external MariaDB concurrency target.
- The single-command gate is reliable again: the nonexistent maintenance API
  suite entry was removed, Windows LF-sensitive release fixtures have explicit
  LF policy, cached Laravel configuration stayed cleared, and the stale
  full-edit form assertion now matches the intentional `status_change_note`
  field.
- Local web-stack preflight was `APP_ENV=local`, `DB_CONNECTION=mysql`,
  `DB_DATABASE=snipeit`. The local schema was brought forward and foundation
  plus demo role/account seeders were applied; no destructive demo asset seeder
  was run.
- Authenticated Senior Refurbisher qualification created asset 16
  (`LOCAL-LEGACY-20261110`) with two legacy attestations, no intake run/results,
  and the expected actor/source snapshots. Starting the profile produced an
  open mixed run with two evidence snapshots and results only for uncovered
  applicable items. Revoking attestation 1 left one active attestation,
  retained the reason/audits, and added one pending `nvt` row to the open run.
- Installed Chrome executed the real intake JavaScript: collapsed/open state,
  AJAX option loading (3 profiles/22 items), per-profile select/clear controls,
  and stale selection/source/date clearing on model change all passed. The
  desktop control plugin itself could not initialize because its kernel-assets
  path was missing; no temporary Chrome state was retained.

## Release boundary

1. Keep 25x25 QR printing as a separate explicit operator action after the
   saved identity is checked. Intake itself produced no print job.
2. Prepare a release document with backup, additive migration, permission
   seeder, smoke checks, and rollback limits before any production change.
3. Build and qualify the immutable release image before requesting production
   deployment approval.
