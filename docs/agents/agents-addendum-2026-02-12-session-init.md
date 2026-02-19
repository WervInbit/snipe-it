# Agents Addendum - 2026-02-12 Session Init

## Context
- Reviewed `AGENTS.md`, `PROGRESS.md`, and `docs/fork-notes.md` before starting work.
- This addendum captures session-specific updates and verification notes for 2026-02-12.

## Worklog
- Initialized the 2026-02-12 session records per the handbook workflow.
- Added the matching dated stub in `PROGRESS.md`.
- Ran a docs drift check against `README.md`, `CONTRIBUTING.md`, and `docs/*`.
- Confirmed core docs are aligned with current fork notes; identified one malformed legacy block in `PROGRESS.md` (literal `\\n` escapes) for later cleanup.
- Added a new dashboard camera quick-action card (same small-box style as top dashboard cards), permission-gated by `scanning`, linking to `route('scan')`, with no `View All` footer copy.
- Added a `camera` icon mapping in `app/Helpers/IconHelper.php` and used it in the dashboard card for clearer UX.
- Added feature coverage in `tests/Feature/DashboardTest.php` for scan-card visibility with and without `scanning` permission.
- Updated the existing dashboard access assertion test to reflect current behavior (non-admin users can view dashboard).
- Normalized the malformed historical `PROGRESS.md` block so literal `\\n` escapes are now proper line breaks.
- Validation run: `docker compose exec app php artisan test tests/Feature/DashboardTest.php` (pass).
- Recovered dev environment from setup/preflight state by running `docker compose exec app php artisan migrate:fresh --seed`; post-checks confirm `settings=1`, `users=16`, and `/` redirects to `/login`.
- Improved dev seed quality for UX testing:
- Updated `UserSeeder` so operational users include `assets.view` and added explicit demo personas (`demo_admin`, `demo_supervisor`, `demo_senior_refurbisher`, `demo_refurbisher`, `demo_user`).
- Updated group-level seed permissions in `DatabaseSeeder` and admin role defaults in `RolePermissionSeeder` to include asset visibility.
- Expanded `DemoAssetsSeeder` from 4 to 10 curated assets with broader refurb statuses and matching test fixtures.
- Adjusted demo seeding logic so `Sold` records seed with `tests_completed_ok` to satisfy model save guards.
- Updated `docs/demo-guide.md` to match actual seeded users and include the full reset command.
- Validation run: `docker compose exec app php artisan migrate:fresh --seed` (pass); post-checks show `assets=10`, `users=21`, `test_runs=10`.
- Investigated recurring "no assets visible after reseed/migrations" issue:
- Confirmed assets existed in DB (`assets=10`), indicating UI visibility/filter state rather than missing data.
- Root cause: Bootstrap-table state is persisted via long-lived cookies (`cookieExpire: 2y`) and can keep stale search/sort/filter state across DB resets, resulting in empty lists.
- Fix: versioned the hardware index table cookie/id key by app settings updated timestamp so resets invalidate stale table storage and restore default listing.
- Additional fix: the bootstrap-table `addrbar` (deeplink) feature can also restore stale filters from the URL; hardware index now disables `addrbar` per-table to avoid restoring hidden state across restarts/resets.
- Validation run: `docker compose exec app php artisan test tests/Feature/Assets/Ui/AssetIndexTest.php` (pass).

## Follow-ups
- Continue logging code, test, and documentation updates in this file and `PROGRESS.md`.

## Session Close (2026-02-12)
- Current dev stack status: containers running via Docker Compose; `/` redirects to `/login` when logged out and `/setup` is no longer the default when the DB is seeded.
- Seed expectations (after `php artisan migrate:fresh --seed`):
- DB counts observed: `users=21`, `settings=1`, `assets=10`.
- Seeded demo password is `password` (see `database/factories/UserFactory.php`); usernames are documented in `docs/demo-guide.md`.
- Resolved a hard blocker causing 500s on Assets: Blade view compilation failed with `file_put_contents(...storage/framework/views/...): Permission denied` when compiled views were root-owned.
- Fix applied: `docker/app/entrypoint.sh` now runs cache/view-related artisan commands as `www-data` (when startup user is root) and enforces `www-data` ownership + writable perms on `bootstrap/cache` and `storage/framework/*`. Image was rebuilt so `/usr/local/bin/entrypoint.sh` reflects these changes.
- Note: during `app` container recreation, startup `php artisan optimize` can delay php-fpm readiness; nginx may return temporary `502` until fpm is listening.
