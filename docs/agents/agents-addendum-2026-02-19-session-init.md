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
- Re-checked current phone test implementation in code and runtime output.
- Verified active phone test slugs for seeded phone assets (`DEMO-003`, `DEMO-004`) are:
- `battery`
- `bluetooth`
- `display`
- `front_camera`
- `microphone`
- `rear_camera`
- `speaker`
- `wifi`
- Confirmed `face_unlock` test type exists but is not currently active for seeded Samsung/Pixel fixtures.
- Captured implementation direction for production parity: use an idempotent deploy sync (migration and/or dedicated artisan sync command), not environment-specific seed runs.

## Handoff (Next Session)
- Objective: implement production-safe parity for phone attributes + tests across all environments without requiring `db:seed`.
- Implement:
- Add phone data attributes: `imei_1`, `imei_2` (optional), `has_knox`, `knox_tripped`.
- Add phone test attributes/test types: `charge_port`, `sim_port`, `power_button`, `volume_buttons`, optional `home_button`.
- Keep grade as non-test workflow step on hardware detail (`quality_grade`: `Kwaliteit A-D`).
- Delivery pattern:
- Add idempotent sync logic that `updateOrCreate`s attribute definitions, category links, options, test types, and category-test links.
- Run via deploy path (`php artisan migrate --force` and, if used, a deterministic sync command called in deployment).
- Do not depend on local/demo seeders for production updates.
- Model capability rule:
- Only assign `home_button` to model numbers/devices that physically have one, so runs do not generate irrelevant tests.

## Validation Checklist For Next Session
- `php artisan migrate --force` completes.
- New/updated phone definitions and test types appear in admin UI.
- Starting a test run on a phone includes new tests.
- `home_button` appears only where capability is assigned.
- Hardware detail grade dropdown still shows `Kwaliteit A-D` and remains separate from test completion.
- Existing seeded/demo behavior remains stable (no empty hardware regression).

## Follow-ups
- Continue logging code, tests, and decisions in this file and `PROGRESS.md`.
