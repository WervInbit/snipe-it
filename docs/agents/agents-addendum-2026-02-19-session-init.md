# Agents Addendum - 2026-02-19 Session Init

## Context
- Reviewed `AGENTS.md`, `PROGRESS.md`, and `docs/fork-notes.md` before starting work.
- Reinitialized from recent session addenda:
- `docs/agents/agents-addendum-2026-02-17-session-init.md`
- `docs/agents/agents-addendum-2026-02-12-session-init.md`
- `docs/agents/agents-addendum-2026-02-10-session-init.md`
- `docs/agents/agents-addendum-2026-02-05-session-init.md`

## Carry-Forward Summary
- Quality grading was split from testing and moved to hardware detail as `Kwaliteit A` through `Kwaliteit D`.
- Empty hardware list regressions were previously addressed in three layers:
- seed state invalidation (`DemoAssetsSeeder` bumps `settings.updated_at`)
- API stale filter tolerance (`api.assets.index` ignores invalid `status_id`)
- frontend bootstrap-table crash fix (removed UTF-8 BOM from `resources/lang/nl-NL/tests.php`)
- Known unresolved item: `tests/Feature/Assets/Ui/ReadyForSaleWarningTest.php` failing on missing `warning` session key.

## Worklog
- Session started and documentation context refreshed.
- New-day session stub added to `PROGRESS.md`.

## Follow-ups
- Continue logging code, tests, and decisions in this file and `PROGRESS.md`.
