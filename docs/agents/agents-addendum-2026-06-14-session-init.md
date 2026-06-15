# 2026-06-14 Session Init

## Startup Context
- Continued on `master` at `fe5d71faf` after reviewing `AGENTS.md`, `PROGRESS.md`, `docs/fork-notes.md`, and the 2026-06-13 session addendum.
- Current focus is an opt-in development device scenario seeder for exercising the new component hierarchy with realistic local data.

## Implementation Notes
- Added `DevelopmentDeviceScenarioSeeder` as a local/dev-only seeder that is not wired into `DatabaseSeeder` or `ProductionFoundationSeeder`.
- The seeder calls `ProductionFoundationSeeder` first, then creates only `DEV-COMP-*` assets and development-marked component instances.
- Reruns clean only the dev scenario rows they own, leaving production foundation catalog rows and unrelated runtime data intact.
- The scenario set is intended to exercise baseline expected components, tracked expected components, damaged children, removed expected children, extra components, custom vague parts, phone camera readability, tablet/integrated-board edge cases, tray parts, stock parts, and verification states.

## Validation
- Local PHP syntax checks passed for `DevelopmentDeviceScenarioSeeder` and `DevelopmentDeviceScenarioSeederTest`.
- Docker test preflight after `php artisan optimize:clear` confirmed `APP_ENV=testing`, `DB_CONNECTION=sqlite`, and `DB_DATABASE=:memory:`.
- `php artisan test tests/Feature/DevelopmentDeviceScenarioSeederTest.php --env=testing` passed with `1` test and `22` assertions.
- Ran `php artisan db:seed --class=DevelopmentDeviceScenarioSeeder --force` against the local Docker `snipeit` database. Verification showed `4` `DEV-COMP-*` assets, `12` dev component instances, and `23` dev component events.
- Host smoke check for `http://127.0.0.1:18080/login` returned HTTP 200.
