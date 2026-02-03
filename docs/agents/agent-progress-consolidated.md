# Agent Session Log (Consolidated)

This file consolidates historical session notes from `PROGRESS.md`, `docs/agents/old/agent-progress-2025.md`, `docs/agents/old/agent-progress-2026.md`, and all `docs/agents/old/agents-addendum-*.md` files (including the former `docs/agents/agents.md`). It is a historical archive; do not append new sessions here. Start new work by adding a dated stub to `PROGRESS.md` and creating a per-session addendum at `docs/agents/agents-addendum-YYYY-MM-DD-session-init.md`.

## 2025-09-25
Executed:
- Created `AGENTS.md` to consolidate contributor guidance for the fork.
- Linked agent documentation from `README.md` and `CONTRIBUTING.md`.
- Hardened EULA fallback and asset visibility logic so category listings work before settings seeding.
- Delivered the model-number attribute infrastructure (migrations, Eloquent models, admin UI, resolver services).
- Wired asset create/edit flows for specification overrides and test runs driven by needs-test attributes.
- Updated contributor guide with documentation alignment requirements.
- Switched `.env` to local debug mode and recycled the docker stack for troubleshooting.
- Fixed Passport key permissions so API endpoints load under the web container user.
Notes:
- Backfill existing model data into new attribute tables before enforcing required specs.
- Extend import/API layers to read/write attribute structures and add regression tests.
- Keep `docs/fork-notes.md` focused on high-level deltas; log incremental fixes in `PROGRESS.md`.

## 2025-09-26
Notes:
- No recorded tasks (placeholder session heading only).

## 2025-09-27
Executed:
- Implemented data-layer shift to `model_numbers` and began wiring asset create/update flows and spec UI around selectable presets.
- Added admin CRUD + UI for model number presets; refreshed display helpers; captured regression coverage/docs updates.
- Enabled creating models without an initial model number (schema + validation + controller/API updates) and guarded spec/asset views when no presets exist.
- Drafted migrations and backfill for `model_numbers`; began refactoring attribute storage to `model_number_id`.
- Staged attribute-driven test generation from needs-test model specs; persisted asset specification overrides; exposed formatted spec details on asset/model views.
- Polished spec/override UIs and added targeted PHPUnit coverage.
- Added guard rails for asset overrides/test runs (reject disallowed overrides; require complete model specs before runs).
- Added unit-aware numeric normalization (TB, GHz) while preserving raw input for audit context.
- Introduced `attribute:promote-custom` artisan command.
Notes:
- PHP CLI unavailable; rerun PHPUnit cases once available.
- Continue wiring relationships/services and finish migrations/backfill.

## 2025-09-28
Executed:
- Restored attribute creation form by passing expected layout context.
- Exposed Model Numbers admin page in settings side nav.
- Remapped Model Numbers settings auth to asset-model permissions.
- Escaped spec editor alert copy to avoid Blade parse errors.
- Hardened asset detail spec table rendering to avoid nested Blade expressions.
- Reframed webshop visibility as allow-list; added internal-use toggle; defaulted new assets off-sale.
Notes:
- UX follow-up: enum options should support list-based entry in quick-add flows.
- Discussion logged on SKU removal: SKUs still power variant labels/reporting/test history; deprecation would require mapping those behaviors to model numbers.

## 2025-09-30
Executed:
- Investigated Passport key failures after docker volume resets.
- Updated `docker/app/entrypoint.sh` to run `php artisan passport:keys --force` and set permissions on boot.
- Provided stopgap command for current stack to repopulate keys.
- Finished retiring orphaned SKU scaffolding (removed UI/API refs, added cleanup migration, exposed model-number metadata in asset/test APIs).
Notes:
- Rebuild/restart app service to pick up entrypoint change; verify oauth key files after cold start.
- Run migrations to drop legacy SKU tables and add test-run model-number column.

## 2025-10-02
Executed:
- Removed model-number input from model create flow and redirected post-create to the detail view with guidance to add presets.
- Added a dedicated model-number create page under settings and wired spec editor CTA to it when no presets exist.
- Updated build/runtime handling for Passport keys (generate during image build, validate at runtime).
- Default asset tag generation now uses random two-letter prefixes plus the sequential counter; minimal-create test updated.
Notes:
- Outstanding feature list: deprecated preset filtering in index/API, QR module rebuild, attribute enum quick-add UX, test-run wiring, role-based start gating, docs/tests.

## 2025-10-07
Executed:
- Refined model index API/transformer to surface model-number counts and fallback to primary code.
- Simplified model-number listings by moving default/deprecate actions to the edit form.
- Replaced model detail asset list with a model-number dashboard and shifted file/spec management to presets.
- Investigated model select list search (no code changes committed).
Notes:
- PHP CLI unavailable; rerun model API tests once available.

## 2025-10-14
Executed:
- Fixed asset model index API so persisted table offsets clamp to the last page; added regression coverage.
- Promoted offset clamp into shared API base and rolled it out across list endpoints.
- Introduced attribute definition versioning plus hide/unhide workflows with UI/actions/tests.
- Delivered a model-number specification builder with assignment/reorder endpoints, search UI, updated attribute resolution logic, and coverage.
Notes:
- Run API suite when PHP is available; QA specification builder end-to-end once UI-capable.

## 2025-10-21
Executed:
- Bootstrapped docker stack, installed dependencies, prepared `.env.testing`, and ran the API testsuite; logged failures.
- Simplified hardware location selector to a single Select2 dropdown; realigned location API tests and reran API suite.
- Provisioned `storage/private_uploads/imports` and hardened importing FileBuilder to auto-create it.
- Migrated manufacturer API specs to JSON endpoints and skipped maintenance API flows while module disabled.
- Split device catalog seeding (attributes vs presets) and refactored refurbishment tests into `test_types` with new columns.
- Built admin Test Types UI (CRUD + attribute linking + instructions).
- Reduced API suite failures to the ImportAssets validation cases.
Notes:
- Triage remaining API failures and add documentation updates.

## 2025-10-23
Executed:
- Collated dated addenda into `docs/agents/old/agent-progress-2025.md` and removed legacy per-session files.
- Reworked demo seeders for refurb flow (curated assets, tailored user/location/manufacturer datasets).
- Removed checkout/checkin/audit controllers, routes, and UI; status changes now log to status event table with notes.
- Fixed `requires_ack_failed_tests` DomainException by priming ready-for-sale assets with passing flags and recomputing statuses after seeding.
- Hid hardware form company selector for single-company refurb runs.
- Refreshed Test Types admin view and exposed shortcuts in header/settings sidebar.
- Converted enum option editing to staged workflow.
- Enhanced model spec validation messaging and added a summary alert for spec editor failures.
- Updated attribute value normalization error messaging and restyled selected attributes in spec builder.
Notes:
- Validate new seed dataset and watch for storage permission errors after code changes.

## 2025-10-28
Executed:
- Locked asset creation tags (read-only in UI; server regenerates tag on store).
- Fixed PHPUnit skips missing `$this` so the suite loads.
- Removed legacy "Begin Testing / Pass / Fail" buttons and helper JS from asset edit.
- Reworked dashboard asset block with refurb status chips, label colors, and clean summary layout.
- Refreshed status-label seeding/migration for nine refurb states; updated demo assets.
- Rebuilt device attribute catalog in Dutch; merged battery capacity, split camera tests, refreshed seed data.
- Ran `docker compose exec app php artisan migrate --seed` to validate refreshed catalog and status labels.
- Cleaned hardware side menu to remove old status filters; rebuilt around refurb labels with icons/colors.
- Removed "Alle tests geslaagd" ribbon from asset detail page.
- Added model-number delete button with server-side checks and confirmations.
- Fixed spec-builder category-type fallback so parent-category attributes load for subcategories.
- Localized dashboard refurb pill labels and tooltips to Dutch.
Notes:
- Session closed 2025-10-28; SKU removal discussion flagged dependencies (variant labels/reporting/test history).

## 2025-10-30
Executed:
- Hooked dashboard asset filters into localized labels via `resources/lang/*/refurb.php`.
- Set default locale to `nl-NL` in SettingsSeeder, `.env.example`, and user factory defaults.
- Added `scripts/check-storage-permissions.sh` and documented storage/cache permission recovery.
- Introduced `App\Support\RefurbStatus` with slugged translation keys.
- Allowed asset-level overrides for select attributes (`condition_grade`, `charger_included`, `storage_capacity_gb`, `ram_size_gb`).
- Replaced demo hardware with HP ProBook 450 G8/430 G7 and Samsung Galaxy A5; extended manufacturer seed data.
- Rebuilt refurb testing UI around autosave (status toggles, note/photo badges, partial update endpoint, toast feedback).
- Added feature coverage for partial update endpoint; installed composer dev tooling; tests passed.
- Added EN/NL strings for new attachment labels and validated storage permissions.
Notes:
- Complete Dusk onboarding when GitHub auth is configured; run `npm run dev` for new JS bundles before deploy.

## 2025-11-05
Executed:
- Installed Laravel Dusk and scaffolded harness.
- Extended PHP image to include Chromium/Chromedriver; added `.env.dusk*` for dockerized testing.
- Updated DuskTestCase to run headless Chrome in container and clear compiled views.
- Added Dusk hooks to Start page; updated refurb dashboard test to use Start shortcut.
- Confirmed docker-compose exposes `dev.snipe.inbit`; Dusk suite passes.
Notes:
- Add more Dusk coverage (refurb test flows, asset index).

## 2025-11-06
Executed:
- Filtered deprecated model numbers from new asset flows while allowing legacy presets on existing assets; tightened API selectlist and added tests.
- Rebuilt `/start` views for refurb roles with single-column, touch-friendly layout and dusk hooks.
- Rebuilt `/scan` with auto-starting QR capture, camera switching, manual fallback, localization, and hints.
- Introduced `/hardware/{asset}/tests/active` UI with grouped cards, segmented toggles, inline notes/photos, sticky header, and CTAs.
- Added optimistic autosave with offline queue + service worker caching and live progress updates.
- Added `Tests\ActiveTestViewTest` and updated JS bundles (`tests-active.js`, `tests-sw.js`).
- Logged "Developer Execution Plan - Mobile Testing Page (A5-first)" and resized default QR template to 50x30 mm.
Notes:
- Build assets before deploy; plan Dusk coverage for new mobile workflows.

## 2025-11-11
Executed:
- Delivered first pass A5-first testing UI (new active view, card partial, JS interactions, translations, coverage).
- Improved UX (card styling, spacing, collapse fallback, relaxed canUpdate gate, refreshed strings).
- Rebuilt assets and added Dusk `TestsActiveDrawersTest`.
- Ran feature tests `ActiveTestViewTest` and `PartialUpdateTestResultTest` successfully.
- Dusk test still failed due to legacy UI loading; documented failure and screenshot.
- Switched Dusk suite to MariaDB (`snipeit_dusk`) and updated `.env.dusk*`.
Notes:
- Clear view caches and rebuild JS inside container before re-running Dusk.

## 2025-11-13
Executed:
- Added Bootstrap 3/5 compatibility helpers in tests-active JS to avoid modal/collapse errors.
- Updated `TestResultController@active` to derive `canUpdate` from TestRun policy while preserving asset-edit access; added feature coverage.
- Fixed page-level styles by switching `@push('styles')` to `@push('css')`.
- Delivered full A5-first testing UI (sticky header, responsive grid, floating action bar).
- Updated tests-active JS for live indicators and layout persistence; rebuilt assets.
- Applied feedback: removed redundant helper copy, enlarged controls, icon-based note/photo buttons.
- Ran targeted feature tests via Docker (pass) and migrations.
- Implemented multi-photo storage for test results (new table/model, backfill, controller/Blade/JS updates, tests).
- Seed catalog enrichment: added HP ProBook 450/430 variants and Surface Pro 4/5 presets, updated DemoAssetsSeeder to include new keys.
Notes:
- API/Dusk suites pending; verify UI in browser and Dusk once assets are served.

## 2025-11-19
Executed:
- Added dedicated QR templates for Dymo rolls (30334, 30336, 99012, 30256) plus legacy 50x30.
- Rebuilt QR PDF renderer to prevent multi-page overflow and share layout helper across single/batch.
- Added asset sidebar widget with preview/template dropdown and print/download controls; bulk QR action honors template.
- Locked sticker content to model/preset, serial, asset tag, and company line; removed extra specs.
- Cleaned demo asset names to real products; refined padding/margins for single-page output.
- Updated docs/fork-notes and agent-progress log with QR improvements.
Notes:
- Validate on physical Dymo printers (especially 30256) and adjust padding if needed.

## 2025-11-20
Executed:
- Added server-side LabelWriter print endpoint spooling QR labels to CUPS with queue config.
- Added asset view "Print to LabelWriter" control with feedback.
- Documented CUPS setup in `docs/agents/cups-setup-guide.md`; added env stubs to `.env.example`.
- Added optional multi-queue support and stored label template reference.

## 2025-11-25
Notes:
- Session logged; no code or config changes.

## 2025-11-27
Executed:
- Added hardware inventory folders and SKU detail files for HP ProBook 450/430 variants.
- Updated seeders with real SKUs, ports, Wi-Fi/Bluetooth, and camera details for those devices.
- Added inventory scripts (`scripts/hw-inventory.ps1`, `scripts/hw-inventory.cmd`) and used them for data capture.
Notes:
- Remaining placeholders: HP ProBook 430 G6/G3, Surface Pro 4/5, Samsung Galaxy A5, iPhone 12, Pixel 8 Pro.

## 2025-12-02
Executed:
- Added Dymo test label documentation and printing guidance for 99010 calibration.
- Installed `cups-client` in app container and verified WSL CUPS queue visibility.
- Asset model list now shows actual model-number code/label (primary/first when multiple).
- Seeded refreshed hardware presets (430 G3/G6, Surface Pro 4/5) and ran `php artisan migrate:fresh --seed`.
Notes:
- TODO: add "copy model number" workflow.

## 2025-12-09
Executed:
- Attached Dymo LabelWriter 330 Turbo via usbipd and configured CUPS queue `dymo25` for 25x25 labels.
- Set `.env` to point at WSL CUPS host and queue; cleared config cache.
- Installed `cups-client` in app container and verified `dymo25` queue.
- Printed sample 25x25 PDF from container to confirm end-to-end printing.
- Finalized S0929120 template (v13) and regenerated samples; zero-margin media config.
- Hardware create form shows model-number code; hardware list Name column shows model name.
- Asset tag generator now always issues tags with `INBIT-` + two letters + four digits.
- QR/scan flow now redirects to asset detail page; scan page redesigned with centered preview and primary controls; auto-scroll adjusted.

## 2025-12-17
Executed:
- Fixed spec table overflow on narrow mobile screens by overriding Bootstrap nowrap rule.
- Made scan camera viewport adapt to incoming stream aspect ratio with clamped height.
- Removed leftover manual-entry hooks that caused scan runtime errors.
Notes:
- Verify on small screens after cache clears.

## 2025-12-18
Notes:
- Session init only; no code changes logged.

## 2025-12-23
Executed:
- Shifted test creation to Test Types with category scoping and optional/required flags.
- Added `is_required` to test types, updated admin UI, seeders, translations.
- Tweaked tests UI (per-row thumbnails, completion flow, hardware edit status uses native select).
- Renamed *_test attributes to capability fields, disabled overrides, and added migration to carry existing data forward.
Notes:
- Run migrations and rebuild assets after deploy.

## 2025-12-30
Executed:
- Fixed attribute definition versioning validation to scope uniqueness by key + version.
Notes:
- Tests not run in this environment.

## 2026-01-07
Executed:
- Fixed hardware image uploads to redirect back with flash message; thumbnails use public disk URLs.
- Removed legacy image path normalization; cleaned orphaned asset image rows.
- Updated attribute version creation so browser back returns to list; enum options read-only on existing attributes.
- Adjusted mobile tests-active CTAs to align note/photo controls left.
- Improved scan UX (decode hints, faster fallback, shorter interval, success overlay).
- Scan success clears assets list search storage if it matches scanned tag.
- Hardware tests tab shows photos under each result line item.
- Asset detail highlights latest test failures/incomplete runs; status changes to Ready for Sale/Sold require confirmation.
- Added latest-test status badge and Tests column in asset lists.
- Preserved redirect selection when confirmations are required; confirmation submit uses requestSubmit.
- Tests-active completion prompts on missing/failed required tests.
- Added tests-active JS to Mix build.
Notes:
- Tests not run in this environment.

## 2026-01-08
Executed:
- Implemented Latest Tests list column counts with lazy hover details; added summary endpoint and UI hover tooltip.
- Added CSRF headers and relative API base for hover requests; fixed MariaDB subquery compatibility.
- Updated tests-active mobile layout; noted TODO for user naming/email standards.
- Added serial duplicate check API and UI warning; allow duplicate serials only with explicit override.
- Asset creation now allows custom asset tags via unlock; tags remain unique.
- Updated validation to drop serial uniqueness only when override requested.
- Pushed changes to origin/master.
Notes:
- Tests not run in this environment.

## 2026-01-13
Executed:
- Redirected non-admin/refurbisher logins to dashboard and removed dashboard fallback to account view.
- Defaulted new-user language selection to creator/app locale.
- Simplified asset creation (removed manufacturer/requestable; moved status selector).
- Redirected all roles away from start shortcuts to dashboard; login now lands on `/`.
- Hid requestable on asset edit; added status-only update form on hardware detail.
- Hid requestable items from navigation and asset detail; disabled requestable assets index with 404.
- Investigated report of empty hardware list for non-admins (likely API auth or permission issue).
Notes:
- Verify `/api/v1/hardware` response for affected users and check `assets.view` permissions.

## 2026-01-15
Notes:
- Session kickoff only; no code changes logged.

## 2026-02-03
Executed:
- Restored local access to `dev.snipe.inbit` by fixing hosts mapping and flushing DNS; restarted app/web containers.
- Re-synced `APP_URL` and nginx redirect to `dev.snipe.inbit`; installed TLS cert into Windows Trusted Root.
- Normalized storage/cache permissions and cleared view cache.
- Dashboard hides unauthorized blocks; counts/charts only compute when permitted.
- Hardware list hides Checked Out To, Purchase Cost, and Current Value.
- Asset tags and serials now normalize to uppercase with per-field override toggles (UI + API).
- Asset creation no longer renders checkout-to selectors.
- Manufacturer selection hidden on asset create/edit; manufacturer block removed from asset detail.
- Hardware list no longer includes Requestable column.
- Added TODO to decide battery health auto-calculation from max/current capacity.
Notes:
- Tests not run in this environment.

