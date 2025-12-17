# Session Progress (2025-12-09)

## Addendum (2025-12-09 Codex)
- Kickoff: initialized session per `AGENTS.md` workflow; reviewed PROGRESS.md, docs/fork-notes.md, and docs/agents/agent-progress-2025.md to refresh current context.
- Created this dated stub to track today's work; ready for task assignments.
- Logged session kickoff in `docs/agents/agents.md` so today's notes have a dedicated addendum.
- Restored dev printing path: attached the Dymo LabelWriter 330 Turbo to WSL, brought CUPS back up with queue `dymo25` (25x25 S0929120), updated `.env` to target it, installed `cups-client` in the `snipeit_app` container, and printed a sample 25x25 PDF via `lp` to verify end-to-end.
- Reviewed QR label work from 2025-11-19 through 2025-12-02: server-side CUPS printing, multi-queue support, template consolidation.
- Locked in the S0929120 (25x25) template as default (v13) with final offsets (qr_left 3.2mm, text_left 1.8mm, padding 1.8mm), cleared caches/labels, and validated printing via CUPS queue `dymo25` (job dymo25-25) using zero-margin Custom.W72H72 media.
- Navigation: added a Scan button (route `scan`) to the top nav alongside Assets/Licenses for easier mobile access; new `scan` icon added.
- Scanner: swapped QR decoding to ZXing (@zxing/browser), default to low-res 640x480 with QR-only hints, keeps torch/switch/refocus controls, and falls back to 1280x720 after consecutive failures; rebuilt assets via `npm run prod`.
- Fixed S0929120 (25x25mm) label template for LabelWriter 330 Turbo (300 DPI): increased font from 8.5pt to 11pt, expanded text band from 2mm to 4mm, reduced QR box from 20mm to 18.5mm for better text visibility.
- Removed arbitrary font-size reduction in CSS (was using `fontSize - 1`), now uses configured size directly with semibold weight for better readability on thermal printers.
- Corrected template key from `dymo-s0929120-57x32` to `dymo-s0929120-25x25` to match actual dimensions.
- Reconfigured 25x25mm template with 2mm physical margins: 21x21mm QR code, 2mm text band at bottom (9pt font), no gap between QR and text, 248px QR resolution (matches 300 DPI exactly).
- Fixed square-stack layout rendering: QR now positions at top-left with padding, text band at bottom (was incorrectly using side-by-side layout).
- Hardware create form now shows the model number code (not the display label) so the selected preset is unambiguous during asset creation.
- Hardware list now shows the model name in the “Name” column (falls back to asset name only if no model is present) for clarity.
- Asset tag generator now prefixes new tags with `INBIT-` (two letters + four digits) as the sole generator for new asset tags, independent of auto-increment settings; setup defaults to the same prefix.
- Scanning a tag now routes to the asset detail page instead of the active tests view.
- Updated the scan page layout to focus on the camera preview with two primary controls (camera refresh, flashlight) and auto-scroll into view.
- Camera auto-scroll now offsets slightly so the navbar and scan header remain visible when focusing the preview.
- Updated to user-specified dimensions: 2.5mm margins, 20x20mm QR (236px at 300 DPI), 2.5mm text band with 5pt font, 0.1mm gap.
- Changed S0929120 template text to show asset tag only (no serial number) to match the QR code identifier.
- Created HTML visual designer (label-designer.html) for iterative label layout design without regenerating PDFs.
- Debugged website printing: fixed controller to pass CUPS_SERVER environment variable to the lp process; identified CUPS scheduler not running on WSL (172.22.110.249).

# Session Progress (2025-12-02)

## Addendum (2025-12-02 Codex)
- Kickoff: re-read `AGENTS.md` and every `docs/agents/*` log so today's work starts with the latest workflow/context.
- Logged `docs/agents/agents-addendum-2025-12-02-session-init.md` to track this session; no code or config changes yet.
- Seeded latest hardware variants (430 G3/G6, Surface Pro 4/5) and reset dev DB via `php artisan migrate:fresh --seed` to validate; QR/scan refinements shipped (refocus/torch, tighter spacing); model list now shows actual model-number codes/labels.

# Session Progress (2025-11-25)

## Addendum (2025-11-25 Codex)
- Kickoff: re-read `AGENTS.md`, `docs/fork-notes.md`, and all `docs/agents/*` logs to align with the latest guidance before making changes.
- Logged this dated stub and created `docs/agents/agents-addendum-2025-11-25-session-init.md` to capture work for today; no code changes yet.

# Session Progress (2025-11-20)

## Addendum (2025-11-20 Codex)
- Reviewed QR printing architecture and added a server-side print path that renders the selected template to PDF and spools it to a CUPS queue (configurable via `LABEL_PRINTER_QUEUE` / `LABEL_PRINT_COMMAND`).
- New asset-page control sends the current template to the server print endpoint and surfaces job feedback; preview/download remain unchanged.
- Added a CUPS setup guide under `docs/agents/cups-setup-guide.md` and stubbed the new env vars in `.env.example`.
- Added optional multi-queue support (`LABEL_PRINTER_QUEUES`) plus a printer dropdown on the asset QR widget for selecting storage/workarea queues.

# Session Progress (2025-11-19)

## Addendum (2025-11-19 Codex)
- Re-read `AGENTS.md`, the latest PROGRESS entries, and all `docs/agents/*` addenda so today’s QR printing fix started with the current workflow/state of play; logged the new docs/agents stub before coding.
- Audited the QR label stack (config, QrCodeService, hardware view, bulk actions, label settings) to trace why the Dymo LabelWriter 400 Turbo output spilled the QR and caption across multiple “pages” and why users couldn’t easily pick the roll currently in the printer.
- Added first-class templates for the common Dymo rolls (30334 57x32 mm, 30336 54x25 mm, 99012 89x36 mm, 30256 101x59 mm, plus the legacy 50x30 mm option) and exposed the picker in settings, the hardware sidebar, and the bulk action toolbar so refurbishers can match the printer stock without editing config files.
- Rebuilt the PDF/layout helper shared by single/batch QR prints so we explicitly size the QR canvas and caption area; Dompdf now keeps both elements on a single page and batch runs honor the chosen template.
- Delivered a new sidebar widget on the asset view (preview + template dropdown + print/download buttons) and wired the bulk "Generate QR Codes" action to pass the selected template to `QrLabelService::batchPdf`, improving the end-to-end printing experience.
- Updated the sticker content so each label prints exactly once with the model + preset, serial number, asset tag text, and the Inbit company line; RAM/disk/status/property-of strings were intentionally left off per the request, and the tests now lock in the new caption formatting.
- Refined the PDF layout so the QR stays large on the left while the text stack sits on the right, eliminating the extra second page that previously appeared on Dymo printers.
- Switched the default template to the Dymo 99010 (89×36 mm) roll, introduced per-template QR column widths, and reworked the label HTML/CSS so the QR consumes ~90% of the vertical space with the asset name/tag block locked to the lower-right corner.
- Cleaned up the demo seed data so curated assets use the actual product names (no more “Intake Diagnostics”/“QA Ready” suffixes) and remain less confusing for testers verifying the refurb flows.
- Trimmed the sticker copy to just asset name + asset tag, anchored the text column to the bottom-right with a 5% internal margin, and ensured the QR respects the same top/bottom padding so each PDF displays as a single page with the requested framing.
- Latest tweak: lifted the QR column so its top edge aligns with the text block, tightened the DOMPDF CSS, and removed the remaining blank pages—the PDF now renders a single 99010 label with the QR left and text bottom-right.
- Updated translations, validation (`StoreLabelSettings`), docs/fork-notes.md, and docs/agents/agent-progress-2025.md to capture the new workflow and guidance for future sessions.

## Notes for Follow-up Agents
- Run the refreshed PDFs through real Dymo LabelWriter 400 Turbo hardware for each template (especially the larger 30256 shipping roll) and tweak `config/qr_templates.php` padding if any QR codes still get cropped.
- Consider persisting a per-user "last template" preference so success notifications and other entry points can default to the roll most recently used without forcing a page reload.
- Once hardware verification is done, grab screenshots of the new sidebar widget and bulk picker for inclusion in README/docs to help downstream contributors understand the workflow without diffing code.
- TODO: configure and validate multiple print queues (storage vs workarea) via `LABEL_PRINTER_QUEUES` and the asset-page dropdown.

# Session Progress (2025-11-13)

## Addendum (2025-11-13 Codex)
- Re-read `AGENTS.md`, `PROGRESS.md`, `docs/fork-notes.md`, `docs/agents/agent-progress-2025.md`, and every existing `docs/agents/agents-addendum-*` log so today's work begins with the latest workflow rules and carry-over issues.
- Logged this dated stub and created `docs/agents/agents-addendum-2025-11-13-session-init.md` to capture detailed context before touching code or tests.
- Reconfirmed the lingering blockers from the 2025-11-06 and 2025-11-11 sessions (new `/hardware/{asset}/tests/active` view not rendering, targeted PHPUnit/Dusk suites pending) and queued them for follow-up today.
- Kickoff/context refresh captured before code changes resumed; all subsequent bullets reflect today's work.
- Fixed the `bootstrap.Modal` runtime error on `/hardware/{asset}/tests/active` by adding compatibility helpers in `resources/js/tests-active.js` that prefer Bootstrap 5 components but gracefully fall back to the existing jQuery plugins (modal/collapse) when the namespace lacks constructors; rebuilt assets via `npm run dev` so the updated bundle is ready for verification.
- Rewired the tests UI permission gate: `TestResultController@active` now derives `canUpdate` from the `TestRun` policy (run owners with refurbisher/supervisor/admin access) instead of the asset policy, and the view config reflects that so front-end buttons stay active for refurbishers who own the run even without asset-edit rights; added two feature tests covering the positive/negative scenarios. `php artisan test tests/Feature/Tests/ActiveTestViewTest.php` could not run locally because `php` is unavailable in this shell.
- Restored asset-edit access to the tests UI: `TestResultController@active` now allows `canUpdate` when the viewer can edit the asset _or_ the test run, preserving the previous behavior for asset managers while keeping the new refurbisher permissions; extended feature coverage so a user with only asset-edit rights still sees `canUpdate: true`. Running `php artisan test tests/Feature/Tests/ActiveTestViewTest.php` still fails immediately because PHP CLI is unavailable here—rerun inside Docker/WSL to verify.
- Fixed the missing page-level styles by switching both `resources/views/tests/active.blade.php` and `resources/views/tests/edit.blade.php` to use the layout’s `@push('css')` stack instead of the non-existent `@push('styles')`, so the new cards/layout now match the spec reference (two-column blocks, note/photo buttons at the bottom).
- Delivered the new A5-first visual system for `/hardware/{asset}/tests/active`: sticky two-tier header, responsive CSS grid that switches between masonry and compact modes, elevated cards with segmented controls, and a glassy floating action bar that mirrors the screenshot in `docs/plans/Schermafbeelding 2025-11-13 093717.png`. The `tests/partials/active-card.blade.php` template now exposes status pills, 50/50 note/photo actions, and drawers styled for the new aesthetic.
- Polished the cards per design feedback: removed the stale “Expected” copy, enlarged titles, centered/larger Geslaagd/Mislukt buttons, dropped the redundant status pill text, and replaced the note/photo labels with icon+indicator pills so it’s easy to scan which cards contain attachments.
- Reworked `resources/js/tests-active.js` to drive the new icon indicators (note/photo chips now toggle classes instead of “Ja/Nee” text) and kept the multi-location progress counters in sync; recompiled assets via `npm run dev`.
- Re-verified the feature targets inside Docker after each set of visual tweaks: `docker compose exec app php artisan test tests/Feature/Tests/ActiveTestViewTest.php` (6 tests) and `docker compose exec app php artisan test tests/Feature/Assets/PartialUpdateTestResultTest.php` (5 tests) both pass; API suite and Dusk runs remain pending.
- Additional polish round: hid the lingering “Toon instructies” label, centered the note/photo text with icons, moved the indicator chip to the far right, widened/tallened the Geslaagd/Mislukt controls, and introduced localized copy for the note/photo CTAs plus the note field label. Assets rebuilt via `npm run dev`, and the same two Docker PHPUnit suites were re-run successfully.
- Follow-up: repositioned the note/photo indicator chips using CSS grid so they float on the far right (independent of the centered icon/text), confirmed no lingering `general.*` strings remain, and recompiled assets. Re-ran the two Docker feature suites again to ensure the markup/JS changes are safe.
- Added full multi-photo support for test results: new `test_result_photos` table/migration (with backfill), `TestResultPhoto` model/relations, multi-photo upload/delete logic in `TestResultController@partialUpdate`, Blade galleries, and JS handling (stacked thumbnails w/ horizontal scroll + per-photo delete). The `tests/Feature/Assets/PartialUpdateTestResultTest.php` suite now covers upload/removal flows; ran it plus `tests/Feature/Tests/ActiveTestViewTest.php` inside Docker after `php artisan migrate`.
- Extended the seed catalog with factual presets for HP ProBook 450 G7/G6, 430 G6/G3, and Microsoft Surface Pro 4/5, keeping the existing four demo assets untouched while giving future seeds a richer dataset (`ProvidesDeviceCatalogData` + `DemoAssetsSeeder` now load both the demo and expansion sets).

## Notes for Follow-up Agents
- Expand this section as concrete work lands today and mirror behaviour/process changes into `docs/fork-notes.md` plus supporting docs.
- Highest priority: re-test `/hardware/{asset}/tests/active` now that the Bootstrap compatibility helpers are in place; if the legacy Blade/JS bundle still appears, inspect caches and ensure `public/js/dist/tests-active.js` is synced inside the runtime container (nginx/php-fpm).
- Once the new UI renders correctly, rerun `php artisan test tests/Feature/Tests/ActiveTestViewTest.php tests/Feature/Assets/PartialUpdateTestResultTest.php`, `php artisan test --testsuite=API`, and `php artisan dusk --filter=TestsActiveDrawersTest`, then log the outcomes (the targeted feature test command currently fails because PHP CLI is unavailable in this shell).
- Continue the A5-first testing UI execution plan and expand Dusk coverage after the environment reliably serves the refreshed assets.
- TODO: tighten typography/layout on `/hardware/{asset}/tests/active` for ≤430 px devices so body copy no longer overflows the card; increase base font size/line height and ensure each card reflows without clipping.
- TODO: replace the placeholder `code` fields in `ProvidesDeviceCatalogData` (e.g., `HP-450G8-I5-16-512`) with factual MPN/SKU values pulled from vendor datasheets before running seeds in production; research is still pending.

# Session Progress (2025-11-11)

## Addendum (2025-11-11 Codex)
- Reviewed `AGENTS.md`, `PROGRESS.md`, and every `docs/agents` addendum so today's work starts with the latest ground rules and outstanding follow-ups in mind.
- Logged this dated entry plus a companion docs/agents addendum to capture context before code changes begin, keeping the documentation trail intact.
- Reconfirmed the 2025-11-06 follow-ups (A5-first testing UI plan, select list/API test runs, expanded Dusk/browser coverage) so they remain front-of-mind for this session's prioritization.
- Implemented the A5-first testing UI plan: rebuilt `tests.active` with the sticky save-indicator header, layout toggle, card drawers, modals, and fixed action bar; added the new `tests/partials/active-card.blade.php`, rewrote `resources/js/tests-active.js` for the new interactions (pass/fail deselect, autosave notes, photo modal/delete), and extended translations + feature tests. (`php artisan test tests/Feature/Tests/ActiveTestViewTest.php tests/Feature/Assets/PartialUpdateTestResultTest.php` was attempted but `php` is unavailable in this shell.)
- Follow-up polish: darkened the card backgrounds and added vertical/column spacing so each block is visually separated, wired the collapse toggles (instructions/note/photo) to Bootstrap’s data attributes for a non-JS fallback, loosened the update gate to match the start-run permission, expanded Dusk coverage (`TestsActiveDrawersTest` now seeds its own run and walks pass/fail/note/photo flows), and rebuilt assets with `npm run dev`. `docker compose exec app php artisan test tests/Feature/Tests/ActiveTestViewTest.php tests/Feature/Assets/PartialUpdateTestResultTest.php` passes; `docker compose exec app php artisan dusk --filter=TestsActiveDrawersTest` still times out because the refreshed UI fails to load inside Dusk (page renders the legacy template/no cards), screenshot saved under `tests/Browser/screenshots/failure-Tests_Browser_TestsActiveDrawersTest_test_note_and_photo_drawers_toggle-0.png`.
- Reconfigured Dusk to use the same MariaDB engine as dev (new `snipeit_dusk` schema via `docker compose exec db …`, updated `.env.dusk*` to point at it) so browser tests no longer rely on SQLite. Post-change, `php artisan dusk --filter=TestsActiveDrawersTest` fails later in the flow (waiting for `/start`) instead of choking on SQLite-specific SQL, which confirms it now targets the MySQL schema; further UI fixes can proceed on that baseline.

## Notes for Follow-up Agents
- Use this stub to record concrete work as it lands today; mirror any user-facing or process changes into docs/fork-notes.md and supporting docs.
- Highest priority: /hardware/{asset}/tests/active still renders the old UI (no cards/drawers) in both browsers and Dusk even after clearing caches, reinstalling dependencies, and recreating Docker volumes. Track down why php-fpm/nginx is serving the legacy Blade/JS and fix it.
- When you start coding, run the targeted test suites (php artisan test --testsuite=API, php artisan dusk) relevant to your changes and log the results here.
- Continue the Developer Execution Plan (A5-first testing UI) once blockers clear, and call out any new risks or doc needs in this section.
- Dusk coverage is still pending: docker compose exec app php artisan dusk --filter=TestsActiveDrawersTest times out because the test harness still sees the legacy page; once the UI issue above is resolved, rerun the suite.


# Session Progress (2025-11-06)

## Addendum (2025-11-06 Codex)
- Revisited AGENTS.md and prior addenda, then blocked deprecated model numbers from end-user flows by filtering Select2 options, store/update validation, and controller helpers while still surfacing an asset's legacy preset when editing.
- Tightened the API `models.selectlist` endpoint to return only active presets (with an opt-in for deprecated), and added feature coverage for the filtered select list plus asset store/update validation edge cases.
- Logged today's work in docs/agents and noted outstanding feature-list items (QR workflow, enum UX, role-based start gating) for subsequent passes.
- Rebuilt the `/start` experience for refurbisher, senior refurbisher, and supervisor/admin roles with single-column, touch-friendly actions (`Scan QR`, `Nieuw asset`, `Beheer`) and consistent data-testid hooks.
- Refreshed `/scan` with auto-starting camera, device switching, manual fallback, accessibility hints, and jsQR-based decoding tuned for mobile devices.
- Delivered a new `/hardware/{asset}/tests/active` page: sticky context header, grouped test cards (Failures/Open/Passed), tri-state segmented toggles, inline notes/photos, Bootstrap toasts, and a bottom bar with progress + contextual CTAs.
- Implemented optimistic autosave with offline queuing (local queue + lightweight service worker cache), success/error toasts, and rebalancing logic that moves cards between groups plus recalculates completion counts.
- Added feature tests for the active test view redirect and ensured the scan redirect targets the new active test route; introduced documentation for the new flow.
- Captured the “Developer Execution Plan — Mobile Testing Page (A5-first)” under `docs/plans/` for the next iteration of the testing UI, and updated the default QR label template to a 50×30 mm Dymo LabelWriter 400 size.

## Notes for Follow-up Agents
- Run `php artisan test --testsuite=API` (and the new select list coverage) once PHP CLI access is restored to verify the added tests.
- Manually smoke-test the asset create/edit UI to confirm the Select2 initial state shows a deprecated preset once and that new searches omit it.
- Continue the outstanding feature list from 2025-10-02 (QR module rebuild, enum quick-add UX, role-based start page gating, documentation updates) now that preset filtering is in place.
- Next session: follow the Developer Execution Plan (A5-first) document to rebuild the testing page (compact two-column mode, pass/fail toggles with deselect, drawers, autosave status indicators, photo gallery UX).
- Compile front-end assets with `npm run dev` (or `npm run prod`) so `public/js/dist/tests-active.js` is available for the redesigned test UI; assets remain functional without the bundle, but the interactive experience depends on it.
- Extend Dusk coverage for the new flows (start buttons, scan redirect, autosave interactions) and consider full offline photo queuing if future requirements demand it.

# Session Progress (2025-11-05)

## Addendum (2025-11-05 Codex)
- Brought the repo in line with the Dusk harness: reviewed AGENTS.md/doc logs, installed `laravel/dusk`, and scaffolded the browser testing assets inside the PHP container.
- Updated `docker/app/Dockerfile` to install Chromium/Chromedriver so headless runs execute inside Docker; added `.env.dusk*` files plus sqlite backing and force-set Dusk bootstrap to the internal Nginx host.
- Hardened `tests/DuskTestCase.php` to seed/migrate per test, launch Chrome with container-safe flags, and normalise APP_URL/asset URLs before requests.
- Added `tests/Browser/ExampleTest.php` (login inputs) and `tests/Browser/DashboardRefurbFiltersTest.php` (dashboard refurb chips via real login), wiring the Start-page shortcut into the dashboard view and getting the full Dusk suite green.
- Follow-up: extend Dusk coverage beyond the smoke checks (refurb flow interactions, QR/camera handling) and continue using `scripts/check-storage-permissions.sh` after environment resets so compiled views remain writable.

# Session Progress (2025-10-30)

## Addendum (2025-10-30 Codex)
- Session kickoff: reviewed AGENTS.md, docs/agents addenda, and prepared a fresh session log for 2025-10-30 under docs/agents/.
- Dashboard assets dropdown now pulls refurb filters through localized labels so Dutch users see `Stand-by`, `In verwerking`, `QA-wacht`, etc.; seeding defaults to locale `nl-NL` for fresh datasets.
- Added `scripts/check-storage-permissions.sh` to sanity-check writable cache directories after code changes without baking fixes into container entrypoints.
- Resolved Blade cache permission errors by running the remediation commands in the container and clearing compiled views.
- Normalized refurb status translations via `App\Support\RefurbStatus`, ensuring slug-based keys in `resources/lang/*/refurb.php` map canonical status names to Dutch labels.
- Switched user seeding defaults to `nl-NL` (`UserFactory`) and updated `.env.example` so freshly provisioned demos and logins inherit the Dutch locale; reseeded with `php artisan migrate:fresh --seed` to apply.
- Refreshed demo hardware seeders: retired the MacBook/XPS examples, introduced HP ProBook 450 G8 and 430 G7 plus a Samsung Galaxy A5 handset, and expanded manufacturer seeding for HP/Samsung.
- Enabled asset-level overrides for `condition_grade`, `charger_included`, `storage_capacity_gb`, and `ram_size_gb` in the attribute blueprints so per-device refurb variations are supported; reviewed all `*_test` indicators and confirmed each maps to a distinct hardware check, so none were removed.
- Follow-up: spot-check the dashboard sidebar and a freshly seeded environment to confirm locale/label translations look correct, and mirror any substantive documentation updates into docs/fork-notes.md if needed.

# Session Progress (2025-10-28)

## Addendum (2025-10-28 Codex)
- Realigned the refurbishment status taxonomy with the Stand-by -> Returned / RMA flow, updating dashboard filters, `StatuslabelSeeder`, the upsert migration, and demo asset fixtures to share the new Dutch labels and colour cues.
- Audited the device attribute presets after the catalog rewrite to ensure no legacy keys (brand/device class/carrier lock etc.) lingered in the MacBook/XPS/Pixel blueprints.
- Reran `docker compose exec app php artisan migrate --seed` inside the stack to validate the refreshed seeders; confirmed the nine refurbished states seed without errors.
- Hernieuwde het assets-zijmenu zodat alleen de nieuwe refurb statuslabels zichtbaar zijn en rechtstreeks naar `hardware.index?status_id=` linken; oude Deployed/RTD/Archived-links zijn verwijderd.
- Verwijderde het legacy “All tests passed”-lint op de assetdetailpagina in afwachting van de nieuwe testrun-UX.
- Modelnummerbeheer toont nu een verwijderknop (met bevestiging en blokkerende toestand voor primaire of toegewezen nummers) zodat opschonen niet meer via losse routes hoeft.
- Spec-builder wijst attributen voortaan op categorie-type i.p.v. alleen exacte categorie-ID, zodat alle laptop/phone-velddefinities weer verschijnen bij modellen met subcategorieën (`AttributeDefinition::scopeForCategory` + call-sites).
- Dashboard-refurbfilters vertalen nu naar Nederlandse labels en beschrijvingen terwijl de statuskoppeling intact blijft (`DashboardController@buildRefurbFilters`).

## Notes for Follow-up Agents
- Smoke-test the dashboard status chips in a browser to confirm the new labels filter hardware as expected.
- Resume the PHPUnit cleanup for checkout/merge retirement once PHP CLI access is available locally.

# Session Progress (2025-10-23)

## Addendum (2025-10-23 Codex)
- Reworked the Test Types admin experience so it aligns with other settings views: the listing now has inline action buttons, creation happens through a modal, and quick links were added to the top navigation and settings sidebar.
- Converted enum option editing into a staged workflow; value/label pairs are queued on the form, reviewed in a consolidated table, and saved alongside the attribute definition with improved validation feedback.
- Hardened model specification validation by surfacing field-level errors that spell out regex requirements, numeric ranges, and acceptable units, and added a summary alert when a spec save fails so issues are easy to spot.
- Updated attribute value normalization to emit clearer guidance for enums, booleans, numerics, and unit conversions, and routed the messages to the correct inputs for both presets and asset overrides.
- Polished hardware/test UIs: instructions now power the info icon tooltip, the audit button and legacy quick actions were fully removed, and selected attributes in the spec builder have readable highlight styling.
- Reset container permissions on `storage/` and `bootstrap/cache` after view compilation failures to unblock Blade caching.

## Notes for Follow-up Agents
- QA: exercise the Test Types screen (create, edit, delete) and the staged enum workflow to ensure queued options persist and restore correctly on reload.
- QA: in the model spec editor, attempt values that violate regex/min/max/step/unit constraints and confirm the inline messaging points to the offending field with clear remediation text.
- Documentation follow-up: capture the new Test Types workflow, staged enum guidance, and enhanced spec validation behaviour in `docs/fork-notes.md`/handbook material.
- Monitor storage permissions in future sessions; the remediation command is `docker compose exec --user root app chown -R www-data:www-data storage bootstrap/cache && docker compose exec --user root app chmod -R ug+rwX storage bootstrap/cache`.

# Session Progress (2025-10-21)

## Addendum (2025-10-21 Codex)
- Session kickoff: revisited `AGENTS.md`, prior `PROGRESS.md` entries, and the existing `docs/agents/` logs to confirm workflow expectations before making changes.
- Logged today's documentation stubs at `docs/agents/agents-addendum-2025-10-21-session-init.md` and `docs/agents/progress-addendum-2025-10-21-session-init.md` to capture detailed notes as work progresses.
- Brought up the docker stack, installed dependencies inside the `app` container, prepared `.env.testing` for sqlite, and ran the newly-added `API` PHPUnit testsuite via `php artisan test --testsuite=API` (538 tests in ~176s; 102 failed, 5 incomplete, 4 skipped—failures driven by permission redirects, select-list counts, maintenance uploads, manufacturer update flows, and missing import storage paths).
- Simplified the hardware location selector to a single Select2 dropdown (`resources/views/partials/forms/edit/location-cascade-select.blade.php`) and realigned the location API feature tests with the new single-location expectation; reran `php artisan test --testsuite=API` (now 100 failures, 5 incomplete, 4 skipped) and refreshed the failure inventory at `codexlog/api-failures.csv`.
- Provisioned `storage/private_uploads/imports`, hardened `tests/Support/Importing/FileBuilder.php` to create the directory automatically, migrated manufacturer API specs to JSON endpoints, and marked maintenance API flows as skipped while the module is disabled.
- Split device catalog seeding: `DeviceAttributeSeeder` now seeds only attribute metadata, while the new `DevicePresetSeeder` populates optional demo presets; `AgentTestResultsTest` relies on the attributes/presets for seeded slugs.
- Refactored refurbishment tests so dedicated entries live in `test_types` (`attribute_definition_id`, `instructions` columns added; new `AttributeTestSeeder` seeds the test catalog); controllers now resolve all tests attached to each attribute when creating runs or ingesting agent payloads.
- Built an admin Test Types UI (CRUD + attribute linking + instructions) so refurb checks can be managed without modifying seed data.
- Latest `php artisan test --testsuite=API` completes with 13 failures, 5 incomplete, 11 skipped, 510 passed—remaining failures sit in the ImportAssets validation expectations.

## Notes for Follow-up Agents
- Extend `docs/agents/progress-addendum-2025-10-21-session-init.md` with code updates, verification evidence, and risk notes as the session advances.
- Outstanding verification: triage and resolve the remaining API test cases (ImportAssets validation assertions and the agent test slug seeding) before re-running `php artisan test --testsuite=API`; refer to the refreshed `codexlog/api-failure-summary.txt` for the detailed list.
- Manual QA: walk through the specification builder UI (preset selection, attribute overrides, reorder flows) in a browser-capable environment.
- Documentation backlog: roll pagination helper guidance, specification builder UX workflows, and attribute versioning lifecycle notes into `AGENTS.md`/`docs/fork-notes.md` after validation.
- Monitor the QR template toggle follow-ups and ensure related tests/config documentation stay aligned when PHP access returns.

# Session Progress (2025-10-14)

## Addendum (2025-10-14 Codex)
- Session kicked off: reviewed AGENTS.md, prior PROGRESS entries, and docs/fork-notes.md to align with current fork expectations before making changes.
- Logged new documentation stubs in docs/agents/agents-addendum-2025-10-14-session-init.md and docs/agents/progress-addendum-2025-10-14-session-init.md for detailed notes as the day advances.
- Fixed the asset model index API so persisted table offsets clamp to the last available page instead of returning an empty dataset, and added regression coverage for the scenario.
- Promoted the offset clamp into the shared API controller base and rolled it out across list endpoints (assets, accessories, locations, etc.), with fresh assets index coverage to guard the shared helper.
- Introduced attribute definition versioning, hide/unhide workflows, and supporting UI/actions/tests so teams can migrate specs safely.
- Delivered a model-number specification builder: new assignment/reorder endpoints, a three-column search-enabled UI, updated attribute resolution logic, and accompanying feature/unit coverage for assign/remove flows.

## Notes for Follow-up Agents
- Detailed worklog: docs/agents/progress-addendum-2025-10-14-session-init.md (extend with concrete updates and test evidence).
- Handbook updates: docs/agents/agents-addendum-2025-10-14-session-init.md (record any process clarifications introduced today).
- Testing follow-up: run the API feature suite (`php artisan test --group=api`) when PHP is available to exercise the new pagination helper under real execution.
- QA follow-up: walk through the new specification builder end-to-end (add/remove/reorder attributes, save specs, verify overrides) once a UI-capable environment is available.

# Session Progress (2025-10-07)

## Addendum (2025-10-07 Codex)
- Session initiated: reviewed AGENTS.md guidance, PROGRESS.md history, and docs/fork-notes.md to re-establish fork context before starting new work.
- Created docs/agents/progress-addendum-2025-10-07-session-init.md to capture detailed notes for this block.
- Refined the model index API/transformer to surface model-number counts and fallback to the primary code so the admin listing shows every model even when presets are missing.
- Simplified model-number listings so default/deprecate actions live on the edit form instead of inline tables.
- Replaced the model detail asset list with a model-number dashboard and shifted file/spec management onto individual presets.
- Investigated the model select list (Werckerman search) but work deferred to next session; no code changes committed.

## Notes for Follow-up Agents
- Working notes: docs/agents/progress-addendum-2025-10-07-session-init.md (update as tasks advance).
- Pending outcomes: summarize deliverables in this section before closing the session.
- Testing blocked: php binary not available on host; rerun `php artisan test tests/Feature/AssetModels/Api/IndexAssetModelsTest.php` once PHP is installed.
- Verify model-number edit screen still handles primary assignment and status flips after removing inline controls.

# Session Progress (2025-10-02)

## Addendum (2025-10-02 Codex)
- Session initiated: reviewed AGENTS.md guidance and recent PROGRESS entries to align with fork expectations.

## Addendum (2025-10-02 Codex - Follow-up)
- Re-reviewed AGENTS.md, PROGRESS.md, and docs/fork-notes.md to confirm carryover work before resuming.
- Current focus: process feedback and update the project based on the latest review inputs.

- Removed the model-number input from the model create flow and redirected post-create to the detail view with guidance to add presets.
- Added a dedicated model-number create page under settings and wired the spec editor CTA to it when no presets exist.
- Updated build/runtime handling for Passport keys (generate during image build, validate only at runtime) and preserved dev-cache clearing guidance.
- Default asset tag generation now uses random two-letter prefixes plus the sequential counter (e.g., ASSET-XY0001) and auto-assigns when tags are omitted; minimal-create test updated accordingly.
- Session paused: finish the outstanding feature list (deprecated preset filtering in index/API, QR module rebuild, attribute enum UX, test-run wiring, role-based start page, docs/tests) and run `php artisan migrate && php artisan test` once PHP is available.


## Notes for Follow-up Agents
- Track ongoing details in docs/agents/progress-addendum-2025-10-02-session-kickoff.md during this session (review alongside this log).
- Pending detailed updates once work completes this session.
- Carryover reminders: rebuild/restart the app service for the Passport key entrypoint fix, verify oauth key files persist after a cold start (capture logs if missing), run `php artisan migrate` post-merge to drop SKUs + add the test-run column, and install composer dev packages (Collision) before running `php artisan test`.

# Session Progress (2025-09-30)

## Addendum (2025-09-30 Codex)
- Detailed notes: docs/agents/progress-addendum-2025-09-30-passport-keys.md (review alongside this summary).
- Investigated the recurring Passport key failure after docker volume resets and confirmed the storage mount starts without oauth key material.
- Extended docker/app/entrypoint.sh to auto-run php artisan passport:keys --force, chown the generated files to www-data, and lock permissions so HTTP requests can decrypt tokens immediately after boot.
- Shared a stopgap for the current stack: execute docker compose exec app php artisan passport:keys --force once to repopulate keys until the container restarts with the updated entrypoint.
- Finished retiring the orphaned SKU scaffolding: removed UI/API references, added a schema cleanup migration, and exposed model-number metadata in asset/test APIs and transformers.

## Notes for Follow-up Agents
- Rebuild or restart the app service (docker compose up -d --build app) to pick up the entrypoint change and verify the hardware index loads without manual key generation.
- After the next cold start, confirm storage/oauth-public.key and storage/oauth-private.key exist on the shared volume; if not, capture container logs for the entrypoint to debug further.
- Run `php artisan migrate` inside the app container once this branch lands to drop the legacy SKU tables and add the test-run model-number column.
- Composer dev packages are missing in the container (`Collision` dependency); install them before attempting `php artisan test` so the new assertions can be exercised.
# Session Progress (2025-09-28)

## Addendum (2025-09-28 Codex)
- Session restarted after prior context drop; reviewed AGENTS/PROGRESS/fork notes to re-establish scope on model number settings work.
- Restored attribute creation form by passing the expected layout context so the form renders without `$item` errors.
- Exposed the Model Numbers admin page in the settings side nav so superusers can reach the new CRUD screen.
- Remapped Model Numbers settings auth to rely on the existing asset-model permissions so admins with model access can enter without 403s.
- Escaped the spec editor alert copy so Blade compiles cleanly when model categories message includes an apostrophe.
- Flagged a UX follow-up: enum options should support list-based entry instead of delimiter-separated input in quick-add flows.
- Hardened the asset detail spec table rendering to avoid nested Blade expressions, preventing parse errors on environments sensitive to inline helpers.
- Reframed webshop visibility as an allow-list toggle, added matched internal-use control on asset show/edit, and default new assets to stay off-sale until explicitly approved.

## Notes for Follow-up Agents
- Smoke-test the Admin → Settings → Model Numbers page once PHP/JS assets are recompiled to confirm new CRUD + search interactions behave as expected.
- Continue wiring specification flows to respect the selected model number and fill in outstanding documentation updates once core pages stabilize.

# Session Progress (2025-09-26)

# Session Progress (2025-09-27)

## Addendum (2025-09-27 Codex)
- Session initiated on Raspberry Pi environment; reviewing WIP multi-model-number migrations and related services.
- Goal: confirm migration drafts, scope remaining refactors (relationships, UI/API), and plan data backfill + documentation updates.
- Implemented data-layer shift to `model_numbers` (service layer, resolver, factories) and began wiring asset create/update flows and spec UI around selectable model numbers.
- Added admin CRUD + UI for model number presets, wired spec editor + asset forms to respect the selected preset, refreshed display helpers, and captured regression coverage/documentation updates.
- Enabled creating models without an initial model number (schema change, validation, controller + API updates), reworked spec/asset views to guard when no presets exist, and updated docs/tests for the new workflow.

## Summary
- Confirmed workflow requirements now call for multiple model numbers per model with dropdown selection for refurbishers.
- Scaffolded the new data layer: drafted migrations for `model_numbers`, backfill, and the accompanying Eloquent model.
- Began refactoring attribute storage to reference `model_number_id` instead of `model_id`.

## Notes for Follow-up Agents
- Work paused due to environment usage limits before migrations were finalized—double-check the two new migration files for consistency and run them once access is restored.
- Continue porting relationships and services (`ModelAttributeManager`, resolvers, controllers, UI) to the multi-number schema.
- Update `model_number_rework.txt` and fork docs to reflect the new workflow once implementation resumes.
- Planned next steps: finish data migration backfill, build admin CRUD for model numbers + specs, update asset create/edit (web & API) to require model + model number selection.

## Summary
- Finished staging attribute-driven test generation so new runs and agent uploads build from needs_test model specs.
- Persisted asset specification overrides on updates and exposed formatted spec details on asset and model views.
- Polished spec/override UIs (required flags, bool labels) and added targeted PHPUnit coverage for the flow.
- Added guard rails for asset overrides and test runs (reject disallowed overrides and require complete model specs before launching tests).
- Added unit-aware numeric normalization (e.g., TB, GHz) for model attributes while preserving the original input for audit context.
- Introduced `attribute:promote-custom` artisan command to surface and promote recurring custom enum values.

## Notes for Follow-up Agents
- PHP CLI is unavailable in this environment, so rerun the new PHPUnit cases once a PHP binary is present.
- Keep an eye on legacy files already modified in the worktree (public uploads, package-lock) when preparing commits.
- Manually exercise asset update and test-run flows to verify the new validation rules; automated tests were not executed this pass.
- To test: attempt an asset override on a non-overrideable attribute and confirm the request is rejected with a validation error.
- To test: start a test run with a required model attribute missing and ensure the run is blocked with the missing attributes listed.
- To test: enter values such as "0.5 TB" or "2.5 GHz" when editing model specs and confirm they are converted into the attribute's canonical unit while retaining the raw input for reference.
- To test: run `php artisan attribute:promote-custom <attribute_key>` (with and without `--apply`) to verify the command reports custom values and optionally creates options.

# Session Progress (2025-09-25)

## Summary
- Detailed notes: docs/agents/progress-addendum-2025-09-25-model-number.md (review alongside this summary).
- Created `AGENTS.md` to consolidate contributor guidance tailored to this fork.
- Linked the agent documentation from README.md and CONTRIBUTING.md for quicker discovery.
- Hardened EULA fallback and asset visibility logic so category listings work before settings seeding.
- Delivered the model-number attribute infrastructure (migrations, Eloquent models, admin UI, and resolver services).
- Wired asset create/edit flows for specification overrides and test runs that honor needs-test attributes.
- Updated contributor guide with documentation alignment requirements for this fork.
- Switched `.env` to local debug mode and recycled docker stack for troubleshooting.
- Fixed Passport key permissions so API endpoints load under the web container user.

## Notes for Follow-up Agents
- Review `AGENTS.md` for contributor guidance updates before expanding documentation.
- Backfill existing model data into the new attribute tables before enforcing required specs in production.
- Extend import/API layers to read/write the new attribute structures and add regression tests.
- If additional work occurs in a new session, create a dated addendum (e.g., `progress-2025-09-26.md`) and reference this log.
- Keep `docs/fork-notes.md` focused on high-level feature deltas; log incremental fixes here in `PROGRESS.md`.
- Keep README.md and CONTRIBUTING.md references in sync if the agent docs move or get renamed.
- Session closed for 2025-09-25; resume outstanding work next shift.

# Session Progress (2025-09-28)
You dropped in the middle of removing sku's and we stopped the session:
your last message:
SKUs are still doing real work today; they’re more than a relic of the single-model-number era:

Variant labelling & filters – AssetModel::skus() powers the Admin → SKUs screens, usage dashboards, and the “SKU and Model reports” in app/Http/Controllers/ReportsController. Shops tag outgoing stock (“T480 – 16GB/512GB”) and filter inventory by that label; dropping SKUs removes those grouping/report pivots.
Test history joins – test_runs.sku_id links test results to the exact outgoing variant. Ops reports (and alerting) use it to pinpoint which SKU failed diagnostics, even when multiple trims share the same base model number.
Legacy reporting & API contracts – Existing LiveSlides/BI exports (and API clients) call GET /api/v1/skus or pass sku_id= to the assets endpoint. Removing the entity is a breaking change for those consumers.
The newer model-number system overlaps with SKU duties (spec presets; attribute overrides), so if you’re committed to consolidating on model numbers only, here’s what the SKU layer currently offers that you’d need to rehome:

Variant-specific naming visible in asset lists, exports, and queue labels.
Reporting filters and API params tied to sku_id.
Historical analytics that assume test_runs and assets reference SKUs.
If you’re ready to deprecate SKUs, we can map each of those behaviors onto the model-number + attribute pathway (e.g., expose model-number labels where we display SKU names, move the test_runs foreign key to model_number_id, add report filters for presets, etc.). That’s more involved than a straight delete but keeps workflows intact. Let me know if you want a concrete migration plan.
my last message:
there are multiple duplicate functions that still need to be removed, sku will be one of them. and sku doesnt fail diagnostics, a specific model does.





**Session closed** — 2025-10-28 13:38


\n## Notes for Next Session (2025-11-19)\n- TODO: Clean up the QR label sizing/margins once more and validate on hardware (short-term).\n- TODO: Implement one-click direct printing from the asset view to connected/network LabelWriter printers (long-term).\n\n# Session Progress (2025-12-17)\n- Reviewed AGENTS.md, fork-notes, and recent agent addenda before making changes; started this session log for traceability.\n- Investigated the specification details table overflow on narrow mobile widths; identified Bootstrap's responsive table rule forcing `white-space: nowrap` as the cause of horizontal overflow.\n- Added a targeted mobile override to allow spec table cells to wrap within their parent so the block stays inside the asset view at ~327 px widths.\n- Made the scan camera viewport dynamically size itself to the incoming stream aspect ratio while staying within the page frame; height now adapts per device instead of staying at a fixed width/aspect.\n- Follow-up: verify the asset view on an A5/phone viewport after cache clears; rerun Laravel view/config caches if needed once deployed.
