# Agent Handbook

This guide keeps automation agents and human contributors aligned on the expectations for this fork. Review it whenever workflows change, and update the related docs listed below when you ship notable changes.

## Daily Workflow Checklist
- Read `PROGRESS.md` and `docs/fork-notes.md` before starting to confirm current context and open follow-ups.
- Scan outstanding docs for drift (README, CONTRIBUTING, `docs/*`); plan updates alongside code changes.
- When a new work session begins, add a dated stub to `PROGRESS.md` so the log captures context as the day unfolds.
- Prefer focused tests that exercise touched code; call out skipped coverage in `PROGRESS.md` when risk remains.
- Close the loop by logging your work in `PROGRESS.md` (new dated addendum for a new day) and linking any supporting docs.

## Automation Environment Notes
- Work from the repo root; run shell commands via `bash -lc` when possible and lean on `rg` for file and text discovery.
- Default to ASCII when editing files; only add clarifying comments when logic is non-obvious.
- Never revert uncommitted changes you did not author; coordinate if you encounter unexpected diffs.
- Maintain sandbox hygiene: avoid privileged commands unless the task explicitly requires them.

## Documentation Touchpoints
- `AGENTS.md` (this file) captures the ground rules for agents and contributors working in the fork.
- `PROGRESS.md` tracks session-level outcomes; update it at the end of each working block.
- `docs/fork-notes.md` summarizes fork-level feature deltas; reference PRs or issues when logging changes.

## Repository Guidelines

### Project Structure & Module Organization
- `app/` hosts Laravel 11 application logic (controllers, jobs, models), with HTTP entry points declared in `routes/`.
- `resources/` contains Blade views, JavaScript, and LESS; compiled assets output to `public/` via Laravel Mix.
- `database/` holds migrations, seeders, and factories; demo CSVs live in `sample_csvs/` and scripts in `scripts/`.
- `tests/` splits into `Feature/`, `Unit/`, `Concerns/`, and `Support/`; mirror production namespaces when adding coverage.
- Deployment and infrastructure aids sit in `docker/`, `docker-compose.yml`, `ansible/`, and `heroku/`; update related docs in `docs/` when flows change.

### Build, Test, and Development Commands
- `composer install` followed by `npm install` to bootstrap PHP and front-end dependencies.
- `cp .env.example .env` then `php artisan key:generate` to initialize local config.
- `npm run dev` for watchable asset builds; use `npm run prod` before packaging releases.
- `php artisan migrate --seed` seeds baseline data; `php artisan serve` exposes the app on http://localhost:8000.
- `docker-compose up -d` spins up the MySQL/PHP/queue stack defined in `docker/` when you prefer containers.

### Coding Style & Naming Conventions
- PHP follows PSR-12 with 4-space indentation; classes live under the App namespace in StudlyCase, methods in camelCase, config keys snake_case.
- Blade templates mirror controller names and use 4-space indents; JavaScript modules under `resources/js` stay ES modules with camelCase exports.
- Run `vendor/bin/phpstan analyse` and `vendor/bin/psalm` on complex changes; honor `phpmd.xml` for legacy hot spots.
- Localized strings belong in `resources/lang/<locale>/`; reuse existing keys before adding new ones.

### Testing Guidelines
- Copy `.env.testing.example` to `.env.testing` and point it at sqlite or your MySQL test database.
- Execute `php artisan test` for the full suite, or target classes (`php artisan test tests/Feature/AssetTest.php`).
- Use PHPUnit `@group` annotations to toggle expensive suites (e.g., `--exclude-group ldap`).
- Name new test files `*Test.php`, keep assertions focused, and prefer factories over fixtures in `database/factories`.

### Commit & Pull Request Guidelines
- Write concise, Title Case commit subjects (<70 chars) reflecting the change scope; body lines explain why, not what.
- Reference related issues using `Refs #123` and note any migrations, seeds, or breaking changes.
- Pull requests include a summary, test evidence (`php artisan test`, asset build status), and UI screenshots when views change.
- Request at least one reviewer and keep revisions rebased; update docs or config samples when behavior shifts.

### Documentation & Fork Alignment
- Maintain `PROGRESS.md` within the repo; new-day sessions add dated addenda and summarize functional differences from upstream.
- Update `AGENTS.md`, `README.md`, and relevant files in `docs/` whenever the fork gains, alters, or drops features so downstream readers see changes without diffing code.
- Call out new configuration or UX behavior in a short note under `docs/` (e.g., `docs/fork-notes.md`) and link PRs or issues for traceability.
- When merging upstream Snipe-IT, document conflicts or overrides before closing the work to preserve context for future triage.

### Security & Configuration Tips
- Never commit `.env*` files; use `.env.testing.example` and document overrides in `docs/`.
- Secrets stay in your runtime environment; rotate tokens before sharing logs.
- When adding third-party packages, vet licenses and update `SECURITY.md` if new attack surfaces appear.
