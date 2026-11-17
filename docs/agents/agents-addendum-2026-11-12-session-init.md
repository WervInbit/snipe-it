# Agent Addendum - 2026-11-12 - Status And Backlog Audit

## Session boundary

- Worktree: `C:\Users\Gebruiker\.codex\worktrees\legacy-workflow-attestations\snipe-it-fork`.
- Branch: `codex/legacy-workflow-attestations`; HEAD and `origin/master` remain
  `c539b9cd18`.
- This was a read-oriented reconciliation of implementation, test evidence,
  deployment records, and active backlog. Application code and production were
  not changed.

## Current implementation state

- The legacy workflow evidence slice is fully implemented in the worktree but
  remains uncommitted. It includes its additive migration, permission changes,
  domain services, intake UI, readiness/progression/API/list integration,
  English/Dutch strings, documentation, and feature coverage.
- Latest qualification is unchanged from 2026-11-10: 2,281 guarded non-LDAP
  SQLite tests, 11,251 assertions, two expected external-MariaDB skips, zero
  failures, authenticated local create/start/revoke coverage, and a real Chrome
  AJAX interaction pass.
- Docker Desktop was unavailable during this audit. No local runtime or DB
  state was changed; current container/migration counts must be rechecked after
  Docker restarts.

## Backlog reconciliation

- Closed stale TODO claims for the completed Snipe-IT host move, production
  status matrix, and the deployed RAM-speed distinct-aggregation fix.
- Kept CUPS relocation open and separated it from the already-completed
  application-host move. Current queue owners/endpoints must be rediscovered
  before acting because the printer topology has changed since the original
  migration plan.
- Added missing operator issues: intermittent mobile hardware-page scrolling,
  slow History feedback, stable printer location labels, and bounded Zebra
  ZD421 media/alignment qualification.

## Immediate next decision

Choose between:

1. release the already-qualified legacy-evidence slice (review, commit,
   immutable build, verified backup, additive migration, permission seeder,
   production smoke); or
2. first fix the phone scrolling/History feedback issues in a separate branch
   so they do not expand the legacy-evidence release.

The safer default is option 1 with strict scope separation, followed by a new
UX branch.
