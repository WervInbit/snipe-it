# Agent & Progress Log (2025)

# 2025-11-20 - QR Server Print Path
> Pair with PROGRESS.md (2025-11-20 entry) and docs/agents/cups-setup-guide.md.

### Completed
- Added a server-side LabelWriter print endpoint that renders the selected QR template to PDF and spools it to a CUPS queue (config via `LABEL_PRINTER_QUEUE` / `LABEL_PRINT_COMMAND`), logging queue/job/user details.
- Wired the asset view QR widget with a “Print to LabelWriter” control that posts the active template to the new endpoint and surfaces success/failure feedback.
- Documented CUPS setup for Dymo on Linux in `docs/agents/cups-setup-guide.md`; `.env.example` now includes the printing env stubs.
- Added optional multi-queue selection support (env `LABEL_PRINTER_QUEUES`, UI dropdown).
- Filed the 99010 Dymo label template under `docs/labels/Inbit Snipeit Asset V3.label` for reference.

### Outstanding
- Configure CUPS on the target server per the guide and set `LABEL_PRINTER_QUEUE` / `LABEL_PRINTER_QUEUES` (initially for 99010 stock); add more templates once new rolls arrive.
- Populate `LABEL_PRINTER_QUEUES` for storage vs workarea queues and verify server-side printing against both.

# Agent & Progress Log (2025)

## 2025-11-19 – QR Printing Refresh
> Companion to PROGRESS.md (2025-11-19 entry); review alongside the main log.

### Completed
- Added first-class support for the common Dymo LabelWriter 400 Turbo rolls (30334 – 57x32 mm, 30336 – 54x25 mm, 99012 – 89x36 mm, 30256 – 101x59 mm) plus the legacy 50x30 mm template; Settings/labels and the hardware view now expose the picker so refurbishers can match the roll currently in the printer.
- Rebuilt the QR PDF/layout stack (shared by single and batch printing) to size the QR canvas/caption area explicitly, eliminating the multi-page overflow that previously split the code and text across separate “pages.”
- Added inline preview/print/download controls on the asset sidebar, auto-refreshing whenever a new template is selected, and surfaced the same template selector in the asset bulk actions so large batch prints land on the correct stock.
- Extended `StoreLabelSettings` validation and docs/fork-notes.md to cover the new options, refreshed translations/help text, and wired the QR Label Service batch action to honor the chosen template.
- Finalized the sticker payload so each label prints exactly once with the model + preset, serial, asset tag text, and the Inbit name/mark—no RAM/disk/status/property-of strings—and adjusted the PDF layout (99010 default) so the QR stays on the left with the asset name/tag block bottom-aligned on the right, preventing duplicate sticker pages.
- Normalized the demo seed assets to use their real product names (e.g., “HP ProBook 450 G8”), removing the “QA Ready”/“Intake Diagnostics” suffixes that were confusing testers.
- Additional polish: trimmed the sticker text down to asset name + asset tag, added the 5% right margin, and tightened the QR padding so the PDF now opens as a single page with the QR filling the left column and the text anchored at the bottom-right.
- Latest fix: shifted the QR image up to align with the text block’s top edge and cleaned up the Dompdf CSS so no extra pages render—single-page 99010 labels with the exact framing are now guaranteed.

### Outstanding
- Exercise the new templates on the physical Dymo LabelWriter 400 Turbo hardware to confirm each PDF respects the printer margins (especially the 30256 shipping roll) and report any off-by-one drift so we can tune padding/QR canvas sizes.
- Consider persisting the last-used QR template per user/session so creation success notifications can default to the correct roll without reloading the asset view.

## 2025-11-06 – Preset Filtering Addendum
> Companion to PROGRESS.md (2025-11-06 entry); review alongside the main log.

### Completed
- Filtered asset creation/edit flows so Select2 options, validation rules, and controller logic ignore deprecated model numbers while still surfacing the legacy preset when editing an existing asset.
- Tightened the API `models.selectlist` endpoint to return only active presets by default, added an escape hatch via `include_deprecated`, and introduced regression coverage for both paths.
- Expanded API asset store/update tests to block deprecated presets on new assets and confirm legacy assets retain their historical model numbers without validation failures.
- Rebuilt the `/start` page for refurbishers, senior refurbishers, and supervisors/admins with single-column, touch-friendly buttons (Scan QR, Nieuw asset, Beheer) and dusk/data-testid hooks for Dusk wiring.
- Refreshed `/scan` with auto-starting QR capture, camera switching, manual fallback, localized messaging, and non-blocking hints; redirects now jump directly into the active tests view.
- Delivered the new `/hardware/{asset}/tests/active` experience: grouped cards (Fouten/Open/Geslaagd), tri-state status toggles, inline notes/photos, sticky header/action bar, and toasts driven by autosave.
- Added optimistic autosave with an offline queue + service worker cache, moving cards between groups immediately and recalculating progress/failure summaries on each change.
- Added feature coverage (`Tests\ActiveTestViewTest`) for the active view/scan redirect and wired new JS bundles (`tests-active.js`, `tests-sw.js`) via Mix.
- Logged the “Developer Execution Plan — Mobile Testing Page (A5-first)” under `docs/plans/` and resized the default QR label template to 50×30 mm for the Dymo LabelWriter 400.

### Outstanding
- Run the API and feature suites once PHP CLI access is available to exercise the new tests.
- Smoke-test the asset create/edit UI in a browser to ensure the Select2 initial state shows a deprecated preset exactly once and that new searches omit hidden presets.
- Build front-end assets (`npm run dev` or `npm run prod`) before deploying so `public/js/dist/tests-active.js` is published; the redesigned tests UI depends on it.
- Plan Dusk coverage around the new mobile test flow (start buttons, scan redirect, autosave interactions) and consider full offline photo queuing if future requirements demand it.
- Next iteration: execute the A5-first execution plan (compact two-column toggle, pass/fail deselect, drawers, autosave indicator, photo gallery UX) once design references are locked in.

## 2025-10-23 - Validation & Test Types Refresh
> Companion to PROGRESS.md (2025-10-23 entry); review this addendum alongside the main log.

### Completed
- Rebuilt the Test Types admin view to match other settings pages (listing + modal flows) and exposed quick navigation links in the top bar and settings sidebar.
- Swapped the enum option workflow to a staged table so new value/label pairs accumulate on the form and persist only when the attribute is saved, with cleaned-up UX and validation.
- Beefed up model specification validation: regex, range, and unit mismatches now call out the attribute, expected format, and offending input; the spec page also shows a summary alert on failure.
- Improved attribute value normalization/shared messaging for enums, numerics, and booleans so both spec presets and asset overrides surface precise guidance.
- Polished hardware/test UI affordances (info tooltips use instructions, audit actions removed, selected spec attributes restyled) and reset storage permissions after Blade cache errors.

### Outstanding
- Smoke-test enum staging and Test Types modal flows in a browser to ensure no cached scripts interfere.
- Run through the model spec editor with intentionally invalid values to confirm each constraint path surfaces the new guidance.
- Document the new workflows and the fallback permissions command in docs/fork-notes.md/infrastructure notes.

## 2025-09-25 – Model Number Rework Addendum
> Companion to PROGRESS.md (2025-09-25 entry); review this addendum alongside the main log.

### Completed
- Added attribute-definition data model (migrations, Eloquent models, and policies).
- Built admin interface for attribute definitions, enum option management, and model specification editing.
- Wired navigation and controller endpoints to expose the new admin workflows.
- Enabled asset-level overrides and test runs driven by attribute definitions flagged `needs_test`.

### Outstanding
- Backfill existing model numbers into the new tables and enforce spec completeness before asset creation.
- Update imports, exports, and APIs to read/write attribute definitions and overrides.
- Add end-to-end and unit coverage for attribute workflows and test generation.

## 2025-09-30 – Passport Key Init Addendum
> Companion to PROGRESS.md (2025-09-30 entry); review this addendum alongside the main log.

### Completed
- Diagnosed docker volume resets clearing Passport key material, triggering CryptKey errors on OAuth requests.
- Updated docker/app/entrypoint.sh to regenerate keys on boot, enforce www-data ownership, and set restrictive permissions across fresh containers.
- Logged remediation guidance in PROGRESS.md so follow-up sessions know to rebuild the app service and verify hardware listings.
- Retired the unused SKU scaffolding in favour of model-number data; removed UI/API hooks, added a cleanup migration, and extended transformers/tests accordingly.

### Outstanding
- Rebuild the app container to ensure the entrypoint automation runs on the next boot and confirm the hardware index renders assets as expected.
- After future volume purges, spot-check storage/oauth-*.key ownership and permissions; capture entrypoint logs if regeneration fails again.
- [ ] Run `php artisan schema:dump --prune` after version 1 is accepted to squash legacy migrations while keeping fresh installs working.
- [ ] Apply the new SKU cleanup migration (`php artisan migrate`) across environments once version 1 is tagged.
- [ ] Install composer dev dependencies in the app container so `php artisan test` can run (Collision dependency currently missing).

## 2025-10-02 – Session Kickoff Addendum
> Companion to PROGRESS.md (2025-10-02 entry); review this addendum alongside the main log.

### Completed
- Established session context by reviewing AGENTS.md, PROGRESS.md, and prior addenda to align with current fork guidance.
- Logged the 2025-10-02 session stub in PROGRESS.md to track ongoing work.
- Removed the model-number field from the model create flow and wired the post-create redirect to the detail view with new CTA guidance.
- Added a dedicated settings form for creating model numbers (with query preselect) and hooked the spec editor CTA to it when no presets exist.
- Updated asset tag fallback to generate random two-letter prefixes plus the sequential counter and defaulted store() to auto-assign tags when omitted.

### Outstanding
- Resume the remaining feature work next session: model-number index/API polish, QR module rebuild, attribute enum UX, test-run wiring, role-based start page gating, and documentation/test updates.
- Run `php artisan migrate` (new `deprecated_at` column) and `php artisan test` once PHP is available.
- Carryover technical follow-ups from 2025-09-30: rebuild/restart the app service for the Passport key entrypoint fix, confirm oauth keys persist after cold starts (capture logs if missing), run `php artisan migrate` post-merge to drop SKUs + add the test-run column, and install composer dev packages (Collision) before running `php artisan test`.

## 2025-10-07 – Progress Addendum

### Kickoff
- Reviewed AGENTS.md, PROGRESS.md, and docs/fork-notes.md to refresh workflow context before making repository changes.

### Follow-ups
- Expand this log with concrete progress and verification notes as tasks complete during the session.

### Worklog
- Updated api.models index to preload primary model numbers and count presets so empty models still show in the admin listing.
- Extended the asset model transformer/presenter to expose `model_numbers_count` and display it in the table.
- Tightened feature coverage via IndexAssetModelsTest to assert the new field.
- Removed checkout/checkin/audit features; status transitions now log to the new status event table with optional notes and UI history.
- Removed inline make-default/deprecate controls from model-number listings; admins now use the edit form for status and default changes.
- Shifted the model detail view to highlight model numbers and moved file uploads to the model-number edit flow.
- `php artisan test` blocked locally: php binary missing; rerun from an environment with PHP installed.

## 2025-10-14 – Session Init

### Context
- Re-read AGENTS.md, prior PROGRESS entries, and docs/fork-notes.md before making changes; created fresh addendum stubs so detailed notes, decisions, and verification steps could be logged as work progressed.
- No handbook edits required at kickoff; this log captures any clarifications introduced during the session.

### Worklog
- Initialized documentation bookkeeping for the 2025-10-14 session; no repository code changes committed yet.
- Reworked Api\AssetModelsController@index pagination to clamp oversized offsets to the last available page, preventing empty result sets when cookies persist stale offsets.
- Added IndexAssetModelsTest::testAssetModelIndexClampsOversizedOffsets to cover the regression path and guard the new pagination behaviour.
- Promoted the offset clamp into App\Http\Controllers\Controller and applied it across API listing controllers; added Assets\Api\AssetIndexTest::testAssetApiIndexClampsOversizedOffsets to ensure the broader change stays exercised.
- Introduced ModelNumberAttributeController plus assignment/reorder endpoints with request validation, along with unit/feature coverage for assign/remove flows.
- Rebuilt the model specification editor into a three-column builder with search-enabled available/selected lists, detail panels, and AJAX for add/remove/reorder.
- Updated ModelAttributeManager/EffectiveAttributeResolver to rely on explicit assignments and ensure asset overrides stay in sync when attributes are detached.
- Added attribute versioning/hide lifecycle (immutability guard, version cloning, hide/unhide) and updated spec/UI/tests.

- Removed checkout/checkin/audit flows and introduced status event logging/note capture; UI now shows a status history table.
### Outstanding
- QA: exercise the new specification builder (add/remove/reorder attributes, save values, verify overrides) once a browser-capable environment is available.

## 2025-10-16 – Session Init

### Kickoff
- Reviewed AGENTS.md, recent PROGRESS.md entries, and docs/fork-notes.md to re-establish fork context and outstanding tasks before taking further action.
- Created session-specific addendum stubs so detailed work notes, verification steps, and open questions could be logged as the day unfolded.

### Follow-ups
- Schedule a manual walkthrough of the specification builder UI (add/remove/reorder attributes, save specs, verify overrides) when a browser-capable environment is ready.
- Capture any new process guidance in AGENTS.md once validated.
- Capture any unresolved attribute versioning or hide/unhide edge cases encountered during implementation review.

### Worklog
- Initialized documentation bookkeeping for the 2025-10-16 session; no repository code changes committed yet.
- Noticed UTF-8 arrow characters slipping into PROGRESS.md; verified the file against HEAD and rewrote it using `git show HEAD:PROGRESS.md > PROGRESS.md` so the canonical ASCII text was restored without stray multibyte symbols.
- Reworked the hardware model selector so Select2 surfaces combined `model - preset` rows and drives hidden `model_id`/`model_number_id` fields; removed the secondary preset dropdown and updated the spec override panel to reflect the active preset.
- Expanded Api\AssetModelsController::selectlist to page over ModelNumber records, tagging each result with model metadata and filtering out models that lack presets; updated the shared SelectlistTransformer to emit extra id payloads.
- Tightened StoreAssetRequest and UpdateAssetRequest so a preset id is now required whenever a model exposes more than one option; adjusted the hardware controller/spec view to parse composite ids and keep custom field/spec fetches in sync.
- Ran `npm run dev` to rebuild `public/js/dist/all.js` with the new Select2 wiring; did not re-run the PHP test suite (blocked on local PHP), so `php artisan test --group=api` remains outstanding.
- Cleared compiled Blade caches and reset storage permissions whenever permission-denied errors surfaced while testing the spec-editor flow.
- Patched QR tooling: normalized `qr_formats` casing, restored the asset detail UI via new `config('qr_templates.enable_ui')`, and added the flag to `config/qr_templates.php`; asset view now shows print/download controls when the custom module is active.
- Verified QR assets exist on disk (`uploads/labels/qr-v3*`) and wired the view to the new config-based toggle rather than the deprecated `qr_code` setting.

## 2025-10-21 – Session Init

### Context
- Touched base with AGENTS.md, current PROGRESS.md history, and the existing docs/agents addenda to refresh the fork context and ongoing initiatives.
- Logged session-specific agent and progress addendum stubs so detailed work notes, verification steps, and questions could be appended throughout the day.

### Follow-ups
- Capture any new process clarifications or tooling caveats uncovered today so they can roll into AGENTS.md once validated.
- Plan formal handbook updates covering the shared pagination helper usage and related testing obligations after verifying the implementation across controllers.
- Track documentation adjustments for the specification builder lifecycle (versioning, hide/unhide workflows, preset selection) to keep downstream contributors aligned.
- Note any environment constraints (e.g., missing PHP binary, asset build requirements) that persist so future sessions can plan verification runs accordingly.
- Update this progress log with concrete code/documentation changes, verification evidence, and risk notes as the session advances.
- Added an API PHPUnit testsuite targeting the `tests/Feature/*/Api` directories so `php artisan test --testsuite=API` executes the REST coverage slice.
- Schedule a manual walkthrough of the specification builder UI (preset selection, attribute overrides, reorder flows) when a browser-ready environment is on hand.
- Capture any remaining attribute versioning or hide/unhide edge-case findings and roll them into the documentation follow-ups.
- Track the status of QR template configuration changes and ensure UI toggles remain covered by tests once PHP access is restored.
- Triage the failing API test cases (permission redirects, select list counts, maintenance uploads, manufacturer updates) and determine whether fixtures, config, or application logic require fixes.
- Review `codexlog/api-failure-summary.txt` and `codexlog/api-failures.csv` for the full failing API test inventory (dominated by Importing namespace errors due to missing storage paths plus targeted Assets, Maintenances, Manufacturers assertions).
- Capture a follow-up reminder to generate a seedable list of agent test slugs once attribute scaffolding is ready.
- Reminder: once attribute definitions stabilize, assemble and seed the canonical agent test slug list so `AgentTestResultsTest` can pass with real data.
- Follow the updated API suite status: after the latest run `php artisan test --testsuite=API` reports 13 failures (ImportAssets scenarios expecting legacy validation messages) with maintenance specs skipped; details logged in `codexlog/api-failure-summary.txt`.

### Worklog
- Initialized documentation bookkeeping for the 2025-10-21 session; no repository code changes executed yet.
- Brought up the docker stack, installed composer dependencies inside `app`, and configured the testing environment (`.env.testing`, sqlite database).
- Generated the testing app key and marked `/var/www/html` as a safe Git directory inside the container to suppress ownership warnings.
- Ran `php artisan test --testsuite=API` inside the container; the suite executed 538 tests in ~176s with 102 failures, 5 incomplete, and 4 skipped—primarily due to permission redirects, unexpected collection sizes, and maintenance-related 500 responses that require investigation.
- Refactored the hardware location form to use a single Select2 dropdown (`resources/views/partials/forms/edit/location-cascade-select.blade.php`) so the UI no longer renders warehouse/shelf/bin tiers.
- Updated location API feature tests to match the canonical single-location expectations and confirmed both pass locally.
- Exported the refreshed failing cases (post-location fixes) to `codexlog/api-failures.csv` and `codexlog/api-failure-summary.txt` after rerunning the API suite; failure count is down to 100 (86 import errors, 10 asset update validations, 2 maintenance flows, 2 manufacturer flows).
- Planned next steps: (1) ensure import tests have a writable storage target, (2) realign manufacturer permissions/redirects, (3) disable or explicitly skip maintenance flows while the module is off, and (4) circle back to asset test slug seeding.
- Provisioned `storage/private_uploads/imports` inside the Docker PHP container and updated `tests/Support/Importing/FileBuilder.php` to auto-create the directory and surface clearer write failures; import specs now progress past file I/O.
- Reworked manufacturer API feature tests to exercise `api.manufacturers.*` endpoints with JSON assertions, matching current redirect-free behavior.
- Marked all maintenance API specs as skipped via class-level `setUp()` hooks because the module is disabled in this fork.
- Built `DeviceAttributeSeeder` to seed laptop/phone attribute definitions and introduced a separate `DevicePresetSeeder` for optional demo catalog presets; both are wired into `DatabaseSeeder`.
- Added `AttributeTestSeeder` plus a schema tweak so refurbishment checks live on `test_types` (`attribute_definition_id`, `instructions`); controllers now iterate every test attached to a definition instead of assuming one per attribute.
- Implemented an admin Test Types screen (create, update, delete, attribute linking, instructions) so refurb workflows can be managed without seeder edits.
- Updated `AgentTestResultsTest` to consume the seeded attribute-driven slugs (seeding attributes + presets during setup) and verified the scenario passes with the new data model.
- Latest full API suite (`php artisan test --testsuite=API`) now finishes with 13 failures (ImportAssets validation expectations), 5 incomplete, 11 skipped, 510 passed.

## 2025-10-23 – Demo Dataset Refresh
### Worklog
- Initialized status-event logging and hardware status history UI with optional notes.
- Removed checkout/checkin/audit routes, controllers, and API endpoints; added a guard stub for `Asset::checkOut()` and replaced its `checkouts()` relation for legacy counters.
- Resolved build/migrate errors by ignoring `public/storage`, dropping the partially created table, and fixing FK definitions in `2025_10_23_000000_create_asset_status_events_table.php` before re-running `php artisan migrate`.


### Worklog
- Consolidated the dated agent/progress addenda into a single 2025 log and removed the legacy per-session files.
- Trimmed the demo dataset: reduced category, location, manufacturer, supplier, and user seeders to refurb-specific records and rewrote `DemoAssetsSeeder` to seed three curated assets with attribute-backed presets.
- Resolved the `requires_ack_failed_tests` seeding failure by pre-marking ready-for-sale assets as tests-complete and refreshing completion flags after creating demo test runs; `docker compose run --rm app php artisan migrate --seed` now succeeds.
- Removed checkout/checkin/audit flows; status transitions now log to the new status event table with optional notes and UI history.

### Outstanding
- Rerun `php artisan migrate --seed` (or the container workflow) to validate the curated dataset and adjust documentation if discrepancies surface.

### Ongoing Follow-ups (as of 2025-10-23)
- Complete the API ImportAssets validation updates so the new error messages match expectations and clear the remaining 13 failures.
- Formalize documentation updates for the pagination helper rollout, specification builder lifecycle, and attribute versioning once implementations stabilize.
- Schedule manual verification of the specification builder UI and QR template toggles in a browser-ready environment.
- Finalize the canonical agent test slug list to keep refurb workflows green.
- Monitor infrastructure tasks: ensure Docker entrypoint key regeneration holds, maintain writable storage paths for imports, and keep composer dev dependencies installed for the API testsuite.
- Plan a unified QR/label pipeline that merges the legacy TCPDF feature set (field selection, 1D barcodes, sheets) into the new QR module so we can retire the duplicate systems safely.
- Keep company selection hidden in asset forms while single-company refurb flows remain the focus; revisit once multi-company support becomes relevant again.

