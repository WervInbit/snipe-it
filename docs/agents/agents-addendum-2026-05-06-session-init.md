# Agents Addendum - 2026-05-06 Session Init

## Startup Context
- Re-read `AGENTS.md`, `PROGRESS.md`, and `docs/fork-notes.md`.
- Current branch at initialization: `master`.
- Current commit at initialization: `4ad83dd3d`.
- Current date/time context: 2026-05-06, Europe/Amsterdam.

## Repository State
- Existing local changes are present and were not modified during initialization:
- local Docker config (`docker-compose.yml`, `docker/nginx.conf`)
- prior addendum file (`docs/agents/agents-addendum-2026-03-19-session-init.md`)
- upload placeholder `.gitignore` files under `public/uploads/`
- local production-clone environment backups (`.env.before-prodclone.2026-04-30`, `.env.prodclone.prodkey`)
- local production backup/import material under `prodbak/`
- `storage/tmp-testtypes-reorder.js`

## Carry-Forward Notes
- The latest progress entry says the active local environment was switched to the production-key clone variant against `snipeit_prod_work`.
- Treat database operations as high risk until the active `.env` and Laravel cache are verified.
- Destructive database commands remain forbidden unless explicitly approved in the current user message, preceded by DB preflight output for `APP_ENV`, `DB_CONNECTION`, and `DB_DATABASE`.
- Before PHPUnit in Docker, clear Laravel config/cache with `php artisan optimize:clear` and verify tests resolve to the isolated testing database.

## Open Context
- The 2026-04-30 session documented the next-stage hierarchical component/subcomponent plan in `docs/plans/component-hierarchy-subcomponents-plan.md`.
- No code changes or test runs were made during this initialization beyond creating this addendum and updating `PROGRESS.md`.
