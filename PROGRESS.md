# Session Progress (2026-04-09)
- Re-read `AGENTS.md`, `PROGRESS.md`, `docs/fork-notes.md`, `TODO.md`, and the latest session addenda to resume the current workstream.
- Fixed mobile overflow in shared bulk-action toolbars and QR widget controls so list-page dropdowns/buttons stay inside the viewport on narrow screens.
- Hardened the Docker/PHPUnit workflow against hitting the live dev DB by preventing cached config from being used during test runs and documenting the required `optimize:clear` preflight.
- Reseeded the empty local dev MySQL database after explicit preflight and restored the demo baseline (`users=21`, `assets=10`, `settings=1`, `test_runs=10`, `models=10`, `statuslabels=9`).
- Enlarged the hardware detail `Test uitvoeren` call-to-action with a scoped style so it is roughly twice as tall, uses larger text, and reads as a lighter blue button for easier operator discovery.
- Verification:
- `docker compose exec app php artisan view:cache` (pass)
- `docker compose exec app php artisan test tests/Feature/Assets/Ui/ShowAssetTest.php --env=testing --filter=testDetailPageShowsRunTestButtonLinkingToTestsTab` (blocked by the existing sqlite testing DB corruption: `database disk image is malformed`)
- Model create/edit form cleanup:
- removed `Minimum Quantity`, `EOL`, and `Requestable` controls from the model form UI in both create and edit flows; these fields are now treated as deprecated UI inputs for future removal.
- added a focused create-page UI assertion to ensure the create form no longer exposes `min_amt`, `eol`, or `requestable` fields.
- updated model save behavior so deprecated fields are only changed when explicitly present in request payloads; hidden-form updates now preserve existing legacy values.
- added focused edit-page and update-flow assertions in `UpdateAssetModelsTest` to ensure hidden deprecated fields stay hidden and omitted payloads do not overwrite existing values.
- Attribute enum options ordering UX:
- replaced manual numeric sort entry on attribute version option lists with drag-and-drop row ordering.
- option rows now keep `sort_order` in hidden inputs that are auto-synchronized from row position (`0..n`) on add/remove/reorder.
- removed the standalone `Sort order` entry input from the add-option panel.
- added a submit-time confirmation warning when admins typed a new enum option in the entry row but did not click `Add to list` before saving.
- warning copy is now localized via `attribute_definitions.unsaved_option_confirm` in `en-US` and `nl-NL`.
- added lifecycle coverage to assert the version form renders drag handles and that version creation still assigns sequential sort order when option sort values are omitted.
- Verification:
- `docker compose exec app php -l resources/views/models/edit.blade.php` (pass)
- `docker compose exec app php -l tests/Feature/AssetModels/Ui/CreateAssetModelsTest.php` (pass)
- `docker compose exec app php artisan view:cache` (pass)
- `docker compose exec app php artisan test tests/Feature/AssetModels/Ui/CreateAssetModelsTest.php --env=testing` (blocked by existing sqlite testing DB corruption: `database disk image is malformed`)
- `docker compose exec app php -l resources/views/attributes/partials/options.blade.php` (pass)
- `docker compose exec app php -l tests/Feature/AttributeDefinitionLifecycleTest.php` (pass)
- `docker compose exec app php artisan view:cache` (pass)
- `docker compose exec app php artisan test tests/Feature/AttributeDefinitionLifecycleTest.php --env=testing` (blocked by existing sqlite testing DB corruption: `database disk image is malformed`)
- Hardware page runtime fix:
- fixed `htmlspecialchars(): Argument #1 ($string) must be of type string, array given` by removing translation-group key collisions for `__('Attributes')`.
- moved unsaved-option warning copy from `attributes.unsaved_option_confirm` to `attribute_definitions.unsaved_option_confirm` and deleted the conflicting top-level `attributes.php` lang files.
- verification:
- `docker compose exec app php artisan tinker --execute "dump(gettype(__('Attributes'))); dump(__('Attributes'));"` (pass)
- `docker compose exec app php artisan view:clear` (pass)
- `docker compose exec app php artisan view:cache` (pass)
- `docker compose exec app php artisan test tests/Feature/AttributeDefinitionLifecycleTest.php --env=testing` (blocked by existing sqlite testing DB corruption: `database disk image is malformed`)

# Session Progress (2026-04-07)

## Addendum (2026-04-07 Codex)
- Session kickoff: reviewed `AGENTS.md`, `PROGRESS.md`, `docs/fork-notes.md`, `TODO.md`, and the latest dated session addendum before resuming work.
- Created `docs/agents/agents-addendum-2026-04-07-session-init.md` for this session.
- Reinitialized carry-over context from the 2026-04-02 workstream:
- local dev hostname/cert flow is set up around `https://dev.inbit` for LAN/mobile testing.
- hardware detail/edit cleanup pass 1 is in progress locally, including checkout-UI removal, hardware edit simplification, QR widget reduction, and follow-up layout fixes for status/quality rows.
- the hardware detail Blade parse regression from the last session was resolved by replacing inline `@php(...)` shorthand with block `@php ... @endphp`.
- Current known open items remain:
- `TODO.md` backlog: QR label layout cleanup, placeholder MPN/SKU replacement, mobile scan feedback, naming/email convention, battery-health decision, and `tests` vs `tasks` wording.
- explicit unresolved test from prior handoffs: `tests/Feature/Assets/Ui/ReadyForSaleWarningTest.php`.
- environment-level testing blockers for the touched UI work remain unchanged:
- sqlite testing DB corruption (`database disk image is malformed`) in the current container workflow.
- existing Livewire support-file-uploads bootstrap error affecting `EditAssetTest`.
- Existing local worktree changes were detected in the dev-host config files, hardware detail/edit/QR Blade files, translations, tests, and prior session docs; initialization is continuing without reverting or overwriting unrelated edits.
- Hardware detail follow-up:
- removed the current status-change note field from both the hardware detail page and the shared edit-status partial so asset notes can be redesigned later around a single consolidated note surface.
- changed the hardware detail status and quality dropdowns to submit immediately on change rather than relying on a separate quality save button.
- replaced the QR label download path again so the single download action now serves a full rendered label PNG image instead of a PDF or raw QR image.
- verification:
- `docker compose exec app php -l app/Services/QrCodeService.php` (pass)
- `docker compose exec app php -l app/Services/QrLabelService.php` (pass)
- `docker compose exec app php artisan view:cache` (pass)
- `docker compose exec app php artisan optimize:clear` (pass)
- Hardware detail tabs follow-up:
- changed the tests tab icon from a vial to the existing clipboard-check icon to better match the refurb task flow.
- added the missing `general.status_history` translation key in both English and Dutch so the history panel no longer renders the raw translation key.
- reverted the experimental phone-tab layout changes after review because they made the hardware page feel less responsive; mobile tab UX is being left for a later dedicated design pass.
- removed the upload tab's special `pull-right` float on the hardware detail page so the paperclip/upload action stays aligned with the rest of the tab list.
- added a `Test uitvoeren` / `Run Test` button directly under the hardware edit action that activates the existing Tests tab instead of navigating away from the asset page.
- restructured the test-runs index result rows into a simple grid so test labels, statuses, and notes stay vertically aligned instead of drifting as one inline-flex blob.
- replaced the hardware Tests-tab top-right `Start New Run` control with responsive actions: a desktop text button aligned upper-left and a mobile/tablet lower-right floating plus-action button that only appears while the Tests tab is active.
- increased the hardware Tests-tab mobile floating plus-action size and converted the latest-tests warning callout into a click-to-expand block with a right-side disclosure icon.
- added muted helper copy to the latest-tests warning callout so it explicitly says it can be unfolded.
- changed the hardware Tests-tab run list to a single full-width column so test runs no longer split into two columns on wide screens.
- updated `ShowAssetTest` coverage to assert the clipboard-check icon and translated status-history heading.
- Shared mobile header fix:
- reverted the temporary content-header wrapper experiment after it introduced new xs layout issues.
- restored the original standalone mobile sidebar toggle under the navbar and switched the shared header fix to a smaller xs-only rule: `h1.pagetitle` no longer keeps `pull-left` on narrow screens, so the breadcrumb/title can wrap beside the floated hamburger instead of dropping onto its own row.
- adjusted the shared content-header on xs so the section keeps a small real side padding instead of letting the inner Bootstrap row cancel it out, preserving breathing room around the breadcrumb block on narrow screens.
- verification:
- `docker compose exec app php artisan view:cache` (pass)
- `docker compose exec app php artisan test tests/Feature/Assets/Ui/ShowAssetTest.php --env=testing` (blocked by existing sqlite testing DB corruption: `database disk image is malformed`)

# Session Progress (2026-04-02)

## Addendum (2026-04-02 Codex)
- Session kickoff: reviewed `AGENTS.md`, `PROGRESS.md`, `docs/fork-notes.md`, `TODO.md`, and the most recent session addenda before starting work.
- Created `docs/agents/agents-addendum-2026-04-02-session-init.md` for this session.
- Reinitialized carry-over context from the recent March workstream:
- 2026-03-19 closed out active-tests/mobile scan follow-up work and documented one remaining local UI change in `resources/views/tests/active.blade.php`.
- 2026-03-17 finalized the single-save model-number image admin flow and removed obsolete standalone web image-admin routes.
- 2026-03-12 introduced ordered model-number default images, asset image override behavior, webshop/read APIs, and test-photo promotion into asset images.
- Current known carry-over items from repo docs remain:
- unresolved failing test noted in prior handoffs: `tests/Feature/Assets/Ui/ReadyForSaleWarningTest.php`.
- backlog items from `TODO.md` and recent handoffs include QR label layout cleanup, placeholder MPN/SKU catalog replacement, mobile scan feedback, naming/email convention, battery-health auto-calculation, and the `tests` vs `tasks` wording decision.
- Current user direction for this session:
- an initial live showcase surfaced multiple UX/content cleanup items,
- the system is being exercised primarily on a Samsung Galaxy A5,
- mobile-first usability should be treated as the main validation target for upcoming changes.
- Existing local worktree changes were detected in `PROGRESS.md`, `docs/agents/agents-addendum-2026-03-19-session-init.md`, and `resources/views/tests/active.blade.php`; initialization was logged without reverting or overwriting those unrelated edits.
- Dev host/certificate setup:
- extracted a `dev.inbit` server certificate and private key from the newly exported `dev+environment+snipe-it.p12` into `docker/certs/`.
- updated local dev hostname references from `dev.snipe.inbit` to `dev.inbit` in Docker/nginx/local environment config so the stack can serve the pfSense-backed internal hostname with the matching cert.
- verification and restart steps are being handled against the local stack only; no production environment changes were made.
- Hardware detail/edit cleanup pass 1:
- gave quality grading its own dedicated row on the hardware detail page while keeping status updates on the existing `hardware.status.update` endpoint.
- removed checkout-oriented hardware detail UI: the checked-out-to side panel, deployed/assignee rendering inside the status row, checkout date display, and the conditional `checkin_and_delete` delete-button copy.
- simplified the hardware edit page by removing the collapsed optional-information section, moving asset `name` into the visible main form, and moving general `notes` directly below status.
- adjusted the shared hardware status form partial so the status note stays aligned with the status control column.
- reduced the QR widget on the hardware detail page to a single download action that targets the rendered label PNG instead of the raw QR image, and removed the `Print PDF` action.
- Added focused UI regression coverage in `ShowAssetTest` and `EditAssetTest` for the new detail/edit expectations.
- Verification:
- `docker compose exec app php artisan view:cache` (pass)
- `docker compose exec app php artisan optimize:clear` (pass)
- `docker compose exec app php artisan test tests/Feature/Assets/Ui/ShowAssetTest.php --env=testing` (blocked by existing sqlite testing DB corruption: `database disk image is malformed`)
- `docker compose exec app php artisan test tests/Feature/Assets/Ui/QualityGradeDetailUpdateTest.php --env=testing` (blocked by the same existing sqlite testing DB corruption)
- `docker compose exec app php artisan test tests/Feature/Assets/Ui/EditAssetTest.php --env=testing` (blocked in current environment by an existing Livewire support-file-uploads bootstrap error before reaching the new assertions)
- Follow-up polish after manual review:
- changed the hardware detail status form wiring to use a detached form id with `form=""` attributes so the status row and quality row remain true separate rows within the page's table-like `row-new-striped` layout.
- switched the single QR download action to the generated label PDF so the downloaded file matches the actual printed output path instead of the plain QR PNG.
- added width constraints on the QR widget controls so the printer dropdown no longer overflows the panel on narrow screens.
- Verification:
- `docker compose exec app php artisan view:cache` (pass)
- `docker compose exec app php artisan optimize:clear` (pass)
- Blade parse-error follow-up:
- fixed a hardware detail view regression caused by inline `@php(...)` shorthand in the touched Blade files; replaced the shorthand with block `@php ... @endphp` in the asset detail view, QR widget partial, and shared status partial.
- Verification:
- `docker compose exec app php artisan view:clear` (pass)
- `docker compose exec app php artisan view:cache` (pass)

# Session Progress (2026-03-19)

## Addendum (2026-03-19 Codex)
- Session kickoff: reviewed `AGENTS.md`, `PROGRESS.md`, `docs/fork-notes.md`, and the most recent session addenda before starting work.
- Created `docs/agents/agents-addendum-2026-03-19-session-init.md` for this session.
- Reinitialized carry-over context from the recent March workstream:
- 2026-03-17 finalized the single-save model-number image admin flow and removed obsolete standalone web image-admin routes.
- 2026-03-12 introduced ordered model-number default images, asset image override behavior, webshop/read APIs, and test-photo promotion into asset images.
- Current known carry-over items remain documentation/decision oriented unless new implementation scope is provided:
- unresolved failing test noted in prior handoffs: `tests/Feature/Assets/Ui/ReadyForSaleWarningTest.php`.
- backlog items from recent handoffs include QR label layout cleanup, placeholder MPN/SKU catalog replacement, mobile scan feedback, naming/email convention, battery-health auto-calculation, and the `tests` vs `tasks` wording decision.
- Mobile dashboard refinement:
- restored the subtle dashboard tile icons on screens below `768px` instead of letting the AdminLTE mobile rule hide them entirely.
- adjusted the mobile dashboard card layout to keep tile copy readable while leaving room for the icon (`text-align: left`, extra right padding, slightly smaller/lighter icon treatment).
- rebuilt frontend assets with `npm run dev` so the override is present in compiled CSS.
- Follow-up refinement after visual feedback:
- added dedicated `dashboard-tile` classes in the dashboard markup so the mobile override targets dashboard cards explicitly instead of generic `small-box` cards.
- strengthened the mobile icon rule to `display: block !important`, reduced icon size, and tightened tile footer sizing so the icons stay visible on mobile.
- shortened the scan card footer copy from `Scan QR` to `Scan` so the action tile scales more consistently with the count tiles.
- Active tests reliability fix:
- aligned backend test-run update authorization with the active-tests UI so non-admin asset editors and run owners with `tests.execute` can persist pass/fail toggles, notes, and photo uploads instead of seeing the optimistic UI revert after a 403 from `TestResultController::partialUpdate`.
- added focused feature coverage for asset-editor updates on foreign runs and for non-refurbisher run owners with `tests.execute`.
- hardened the active-tests progress action bar for phones by switching the bottom progress bar to a fixed mobile layout with extra page bottom padding, avoiding the occasional sticky/top overlap behavior reported on mobile browsers.
- Verification:
- `docker compose exec app php -l app/Policies/TestRunPolicy.php` (pass)
- `docker compose exec app php -l app/Http/Controllers/TestResultController.php` (pass)
- `docker compose exec app php -l tests/Feature/Assets/PartialUpdateTestResultTest.php` (pass)
- `docker compose exec app php -l tests/Feature/Tests/ActiveTestViewTest.php` (pass)
- `docker compose exec app php artisan test tests/Feature/Assets/PartialUpdateTestResultTest.php --env=testing` (blocked by existing testing DB migration-state conflicts in the container)
- `docker compose exec app php artisan test tests/Feature/Tests/ActiveTestViewTest.php --env=testing` (blocked by the same existing testing DB migration-state conflicts)
- Testing environment repair:
- confirmed the container was incorrectly booting `--env=testing` with cached MySQL config even though `phpunit.xml` and `.env.testing` both specify sqlite.
- cleared Laravel bootstrap caches in the app container so `testing` resolves back to sqlite.
- identified the sqlite testing database file as corrupted (`database disk image is malformed`), reset only the test sqlite file after a testing DB preflight, and rebuilt the schema with `php artisan migrate --env=testing --force`.
- reran the focused test suites serially to avoid shared-sqlite corruption:
- `docker compose exec app php artisan test tests/Feature/Assets/PartialUpdateTestResultTest.php --env=testing` (pass, 8 tests / 48 assertions).
- `docker compose exec app php artisan test tests/Feature/Tests/ActiveTestViewTest.php --env=testing` (6 passed, 1 failed).
- remaining failure after environment repair is unrelated to the authorization patch:
- `scan route redirects to active tests for testers` currently redirects to `/hardware/{id}` instead of `/hardware/{id}/tests/active`.
- Practical takeaway for this environment:
- stale config cache can make testing hit MySQL instead of sqlite.
- parallel PHPUnit runs against the shared sqlite test DB are unsafe here and can corrupt the file; run sqlite-backed test commands serially.
- Scan viewport stabilization:
- kept the `/scan` camera frame visually fixed by switching `#scan-area` to a stable `4:3` aspect-ratio box instead of recalculating height from the active stream metadata and viewport height.
- removed the runtime `resizeScanArea()` logic from `resources/js/scan/index.js`; scan quality fallback still applies higher camera constraints after repeated misses, but it no longer changes the visible camera box size.
- retained overlay/canvas syncing against the rendered scan-area dimensions so scan guidance stays aligned with the fixed frame.
- rebuilt frontend assets with `npm run dev` so the updated scan bundle and view styling are present in compiled output.
- Coverage note:
- no PHPUnit coverage added for the scan viewport change; verification was limited to source review plus `npm run dev`.
- Dev DB recovery:
- investigated a fresh `/setup`-style state reported during manual testing and confirmed the live `local` MySQL database was an empty-but-migrated schema (`migrations=454`, but `settings=0`, `users=0`, `assets=0`, `companies=0`).
- verified this was distinct from the repaired sqlite testing DB: `php artisan about` still resolved `local` to MySQL and `--env=testing` to sqlite.
- confirmed the app code paths only auto-run `migrate` when setup/passport prerequisites are missing; no automatic `migrate:fresh` / `db:wipe` path was found in the current Docker app entrypoint or setup controllers.
- recovered the shared dev DB non-destructively with `docker compose exec app php artisan db:seed --force` instead of dropping tables.
- post-seed verification:
- `settings_count=1`
- `users_count=21`
- `assets_count=10`
- `test_runs_count=10`
- `models_count=10`
- `statuslabels_count=9`
- Test expectation cleanup:
- updated `tests/Feature/Tests/ActiveTestViewTest.php` so the scan-tag redirect assertion matches the intended product flow: scan lookup lands on the asset detail page (`/hardware/{id}`), not directly on the active tests screen.
- Verification:
- `docker compose exec app php artisan test tests/Feature/Tests/ActiveTestViewTest.php --env=testing` (pass, 7 tests / 23 assertions).
- Active tests mobile follow-up:
- removed the sticky/fixed positioning from the `general.progress` block in `resources/views/tests/active.blade.php` after device feedback; it now stays in normal flow below the test list instead of pinning to the viewport.
- Session handoff:
- latest pushed commit is `6b5ff364e` (`Fix Test Permissions And Scan Viewport`).
- one local follow-up remains uncommitted at session end: the `resources/views/tests/active.blade.php` change that removes sticky/fixed positioning from the bottom progress block so it stays at the end of the list on mobile.

# Session Progress (2026-03-17)

## Addendum (2026-03-17 Codex)
- Session kickoff: reviewed `AGENTS.md`, `PROGRESS.md`, and `docs/fork-notes.md` before starting work.
- Created `docs/agents/agents-addendum-2026-03-17-session-init.md` for this session.
- Re-synced carry-over context from the prior 2026-03-12 image-source/admin workstream and confirmed commit `1e4af1570` only contained UX/icon/spec layout scope.
- Verified in-progress image-source and model-number image admin UI work:
- `docker compose exec app php artisan test tests/Feature/Assets/Api/AssetImagesApiTest.php` (pass).
- `docker compose exec app php artisan test tests/Feature/Assets/PromoteTestResultPhotoToAssetImageTest.php` (pass).
- `docker compose exec app php artisan test tests/Feature/Settings/ModelNumberImageManagementTest.php` (pass).
- `docker compose exec app php artisan test tests/Unit/AssetTest.php --filter GetImageUrl` (pass).
- Ran `php -l` against touched controller/model PHP files for image-source/admin changes (all pass, no syntax errors).
- Follow-up UX update for model-number image admin:
- replaced manual order-number entry with drag-and-drop ordering UI and a dedicated reorder action.
- added client-side preview for upload input and replacement file inputs.
- added backend reorder endpoint `PATCH model-numbers/{modelNumber}/images/reorder`.
- updated `ModelNumberImageManagementTest` to validate reorder payload behavior (pass after cache clear).
- Added policy-based guardrail for destructive database commands in `AGENTS.md`:
- destructive DB commands on shared dev require explicit user approval in-message.
- mandatory DB preflight output (`APP_ENV`, `DB_CONNECTION`, `DB_DATABASE`) before any destructive execution.
- Updated `docs/demo-guide.md` and `docs/DEMO.md` to reflect explicit-approval usage rather than wrapper tooling.
- Follow-up hardening on the model-number image admin UI:
- replaced brittle table-row native drag behavior with a pointer-event drag handle flow so reorder works for both mouse and touch interactions.
- fixed stale test coverage to exercise the real web routes instead of calling controller methods directly.
- corrected first-image append ordering to start at `sort_order = 0`.
- tightened reorder validation so partial/mismatched image ID payloads are rejected instead of leaving ambiguous ordering.
- Validation rerun:
- `docker compose exec app php artisan test tests/Feature/Settings/ModelNumberImageManagementTest.php` (pass, route-level coverage).
- `docker compose exec app php artisan test tests/Feature/Assets/Api/AssetImagesApiTest.php` (pass, rerun serially).
- Reworked the model-number image admin UX into a single-save flow tied to the main model-number edit form:
- removed per-row save, save-order, and immediate upload actions from the page UX.
- image captions, replacements, reorder state, staged removals, and new-image upload now submit together with the main model-number save.
- added `ModelNumberImageSyncService` so both settings and model-context edit screens share the same image sync behavior.
- model-number image removals are now staged as `Remove` / `Undo Remove` toggles instead of immediate destructive actions.
- Validation:
- `docker compose exec app php artisan test tests/Feature/Settings/ModelNumberImageManagementTest.php` (pass, 6 tests / 31 assertions).
- `docker compose exec app php -l app/Services/ModelNumberImageSyncService.php` (pass).
- `docker compose exec app php -l app/Http/Controllers/Admin/ModelNumberController.php` (pass).
- `docker compose exec app php -l app/Http/Controllers/Admin/ModelNumberSettingsController.php` (pass).
- Follow-up cleanup after production-scope review:
- removed the obsolete standalone admin model-number image controller/routes because the UI now saves image changes only through the main model-number update flow.
- kept the API-side first-image ordering fix so API-created model-number images default to `sort_order = 0`.
- added focused API regression coverage for model-number image creation ordering.
- Validation:
- `docker compose exec app php artisan test tests/Feature/Assets/Api/ModelNumberImagesApiTest.php` (pass, serial run).
- `docker compose exec app php -l app/Http/Controllers/Api/ModelNumberImagesController.php` (pass).
- `docker compose exec app php -l routes/web.php` (pass).

# Session Progress (2026-03-12)

## Addendum (2026-03-12 Codex)
- Session kickoff: reviewed `AGENTS.md`, `PROGRESS.md`, and `docs/fork-notes.md` before starting work.
- Created `docs/agents/agents-addendum-2026-03-12-session-init.md` for this session.
- Current task: initialize from recent session files and list open TODOs/unresolved handoffs for continuation.
- Reviewed `TODO.md`, recent `docs/agents/agents-addendum-2026-*.md`, and `docs/plans/latest-tests-column-lazy-detail.md` to capture active carry-over items.
- Documented the consolidated open-point backlog in today's addendum for implementation planning.
- Updated the scan icon mapping to camera and changed the tests/test-types icon mapping from vial to clipboard in `IconHelper`.
- Added a documentation decision item to evaluate whether user-facing wording should stay "tests" or shift to "tasks" for refurb execution steps (for example cleaning and driver installation) without renaming technical internals yet.
- Updated Dutch translation `general.assets` from `Activa` to `Apparaten` for clearer hardware wording on dashboard and shared labels.
- Updated hardware detail specification mobile CSS so label/value cells stay side-by-side on small screens while values wrap aggressively, preventing overflow without forcing stacked rows.
- Expanded the hardware detail specification section to full-width (`col-md-12`) so specification values have more horizontal space and are less likely to wrap into unreadable fragments.
- Replaced the specification table layout with a separator-style stacked list (label on top, value below) to improve readability and avoid narrow two-column squeezing on small screens.
- Aligned the specification section back to the page's standard `col-md-3/col-md-9` detail-row layout while keeping each spec item vertically stacked (label line, then value line) to prevent side-by-side rendering.
- Hardened the spec layout against CSS collisions by renaming to unique classes (`asset-spec-*`) and enforcing full-width block stacking for each spec row/label/value; cleared Laravel caches with `docker compose exec app php artisan optimize:clear` to ensure updated Blade/CSS render.
- Replaced the custom spec-list approach entirely with standard detail rows per specification item (`row` + `col-md-3/9`) so rendering matches the rest of the hardware detail page and values stack predictably under one another; cleared caches again via `php artisan optimize:clear`.
- Implemented image-source architecture for webshop/read APIs:
- Added `assets.image_override_enabled` so asset-specific photos can explicitly override model-number defaults.
- Added ordered/source-aware metadata on `asset_images` (`sort_order`, `source`, `source_photo_id`) and created `model_number_images` for per-model-number default image sets.
- Backfilled defaults from existing model images into `model_number_images` and backfilled override flag for existing assets with an image.
- Added ordered resolved-image API endpoint `GET /api/v1/hardware/{asset}/images` (`api.assets.images`) returning active source + ordered image payload for webshop usage.
- Added model-number default image management API endpoints:
- `GET/POST/PUT/DELETE /api/v1/model-numbers/{modelNumber}/images` (`api.model-numbers.images.*`).
- Added test-photo promotion flow to asset overrides:
- New route `POST /hardware/{asset}/tests/{testRun}/results/{result}/photos/{photo}/promote` (`test-results.photos.promote`) copies the test photo to asset storage, creates ordered `asset_images` entry, and can enable override + set as cover.
- Updated `Asset::getImageUrl()` to resolve in this order:
- model-number defaults when override is disabled,
- asset override image when enabled (or when no defaults exist),
- then legacy model/category fallback.
- Added regression coverage:
- `tests/Feature/Assets/Api/AssetImagesApiTest.php`
- `tests/Feature/Assets/PromoteTestResultPhotoToAssetImageTest.php`
- `tests/Unit/AssetTest.php::testGetImageUrlPrefersModelNumberDefaultWhenOverrideDisabled`
- Validation run:
- `docker compose exec app php artisan migrate --force` (pass; applied `2026_03_12_130000_add_image_override_and_model_number_images`).
- `docker compose exec app php -l ...` on all touched PHP files (pass; no syntax errors).
- Targeted tests passing when run serially:
- `AssetImagesApiTest`, `PromoteTestResultPhotoToAssetImageTest`, and `AssetTest --filter GetImageUrl`.
- Note: running sqlite-backed test commands in parallel corrupts `database/database.sqlite` in this environment; reruns were executed serially after resetting that file.

# Session Progress (2026-03-05)

## Addendum (2026-03-05 Codex)
- Session kickoff: reviewed `AGENTS.md`, `PROGRESS.md`, and `docs/fork-notes.md` before starting work.
- Created `docs/agents/agents-addendum-2026-03-05-session-init.md` for this session.
- Current task: initialize context and list unresolved open points, TODOs, and handoffs from prior sessions.
- Completed open-point sweep across `TODO.md`, recent `docs/agents/agents-addendum-2026-*.md`, and `PROGRESS.md`; unresolved carry-overs are now documented in today's handoff summary.

# Session Progress (2026-03-03)

## Addendum (2026-03-03 Codex)
- Session kickoff: reviewed `AGENTS.md`, `PROGRESS.md`, and `docs/fork-notes.md` before starting work.
- Created `docs/agents/agents-addendum-2026-03-03-session-init.md` for this session.
- Current task: reinitialize context and summarize open points, TODOs, and in-progress items from recent sessions.

# Session Progress (2026-02-24)

## Addendum (2026-02-24 Codex)
- Session kickoff: reviewed `AGENTS.md`, `PROGRESS.md`, and `docs/fork-notes.md` before starting work.
- Created `docs/agents/agents-addendum-2026-02-24-session-init.md` for this session.
- Pending: confirm today's implementation scope and begin logging outcomes.

# Session Progress (2026-02-19)

## Addendum (2026-02-19 Codex)
- Session kickoff: reviewed `AGENTS.md`, `PROGRESS.md`, and `docs/fork-notes.md` before starting work.
- Created `docs/agents/agents-addendum-2026-02-19-session-init.md` for this session.
- Re-read recent addenda (`2026-02-17`, `2026-02-12`, `2026-02-10`, `2026-02-05`) to reinitialize context and carry-forward blockers.
- Current known carry-over: `tests/Feature/Assets/Ui/ReadyForSaleWarningTest.php` still fails on missing `warning` session key; empty-hardware regressions were mitigated via seed/UI/API fixes in prior session.
- Revalidated current phone test scope from runtime (`TestType::forAsset`) for seeded phone assets (`DEMO-003`, `DEMO-004`): `battery`, `bluetooth`, `display`, `front_camera`, `microphone`, `rear_camera`, `speaker`, `wifi`.
- Confirmed `face_unlock` exists as a test type but is not active for current seeded phone assets because those model capabilities do not include it.
- Product-direction decision captured: stop relying on seeders for production parity for phone checks; move to deploy-safe, idempotent sync of attribute/test definitions.
- Proposed next phone additions:
- Data fields: `imei_1`, optional `imei_2`, `has_knox`, `knox_tripped`, keep/apply `quality_grade` (`Kwaliteit A-D`).
- Test steps: `charge_port`, `sim_port`, `power_button`, `volume_buttons`, optional `home_button` only for models that actually have one.
- Tests not run in this documentation-only update block.

# Session Progress (2026-02-17)

## Addendum (2026-02-17 Codex)
- Session kickoff: reviewed `AGENTS.md`, `PROGRESS.md`, and `docs/fork-notes.md` before implementing the quality-grade workflow split.
- Created `docs/agents/agents-addendum-2026-02-17-session-init.md` for detailed session notes.
- Added `assets.quality_grade` (migration + backfill) and moved grading to the hardware detail status form as a dedicated dropdown.
- Quality choices are now standardized as `Kwaliteit A`, `Kwaliteit B`, `Kwaliteit C`, and `Kwaliteit D`.
- Legacy `condition_grade` is filtered out from spec override/detail displays and excluded from test-type scoping so grading is no longer part of test runs.
- Added feature coverage for detail-page quality updates (`tests/Feature/Assets/Ui/QualityGradeDetailUpdateTest.php`).
- Updated `docs/fork-notes.md` for this fork-level workflow change.
- Validation: `docker compose exec app php artisan migrate --force` (pass, migration `2026_02_17_090000_add_quality_grade_to_assets` applied).
- Validation: `docker compose exec app php artisan test tests/Feature/Assets/Ui/QualityGradeDetailUpdateTest.php` (pass, 2 tests).
- Additional check: `docker compose exec app php artisan test tests/Feature/Assets/Ui/ReadyForSaleWarningTest.php` still fails on missing `warning` session key (appears unrelated to this change).
- Hardened reseed UX guard for the hardware list: `DemoAssetsSeeder` now bumps `settings.updated_at` after seeding so the hardware index table key rotates on every demo reseed (including `db:seed --class=DemoAssetsSeeder`), preventing stale bootstrap-table state from hiding rows.
- Verification: `docker compose exec app php artisan db:seed --class=DemoAssetsSeeder` (pass), `settings.updated_at` timestamp changed, and `assets=10` confirmed after reseed.
- Verification: `docker compose exec app php artisan test tests/Feature/Assets/Ui/AssetIndexTest.php` (pass).
- Hardened `api.assets.index` against stale status filters after reseeds: invalid/nonexistent `status_id` values are now ignored (treated as no status filter) instead of returning an empty list.
- Added regression coverage in `tests/Feature/Assets/Api/AssetIndexTest.php::testAssetApiIndexIgnoresInvalidStatusIdFilter`.
- Validation: simulated API request as admin with `status_id=999` now returns `total=8` (previously `total=0`), confirming hardware remains visible even when stale links/bookmarks carry old status IDs.
- Fixed a frontend blocker that still caused an empty hardware table despite healthy API/data: `resources/lang/nl-NL/tests.php` contained a UTF-8 BOM that was injected into the `data-columns` attribute, triggering a jQuery/bootstrap-table init crash (`Cannot create property 'colspanIndex' on string '﻿'`).
- Validation: captured browser console before/after with headless Playwright (`SEVERE_COUNT` from `1` to `0`) and confirmed `data-columns` no longer starts with `EF BB BF`.
- Session close: products are visible again; detailed problems/solutions and handoff notes are documented in `docs/agents/agents-addendum-2026-02-17-session-init.md`.

# Session Progress (2026-02-12)

## Addendum (2026-02-12 Codex)
- Session kickoff: reviewed `AGENTS.md`, `PROGRESS.md`, and `docs/fork-notes.md` to align with current workflow before starting work.
- Created `docs/agents/agents-addendum-2026-02-12-session-init.md` for this session's detailed notes.
- Current task: documentation drift check (`README.md`, `CONTRIBUTING.md`, and `docs/*`) and report findings.
- Docs audit result: `README.md`, `CONTRIBUTING.md`, and `docs/fork-notes.md` are aligned with current fork workflow/deltas.
- Follow-up needed: `PROGRESS.md` still contains one malformed historical pasted block with literal `\\n` escapes that should be normalized in a dedicated cleanup pass.
- Dashboard UX update: added a permission-gated camera quick-action card on the home dashboard that links directly to the scan page and uses a camera glyph (no `View All` footer copy).
- Added a dedicated `camera` icon mapping in `IconHelper` for dashboard use.
- Added dashboard scan-card feature coverage and refreshed the existing dashboard access assertion to match current behavior.
- Validation: `docker compose exec app php artisan test tests/Feature/DashboardTest.php` (pass).
- Environment recovery: reseeded dev DB via `docker compose exec app php artisan migrate:fresh --seed` after the app redirected to `/setup`; verified `settings_count=1`, `users_count=16`, and root now redirects to `/login` (not `/setup`).
- Improved dev seeding for daily UX testing:
- Added assets visibility (`assets.view`) to operational seeded users and aligned role/group seed permissions to include asset visibility.
- Expanded demo user accounts (`demo_admin`, `demo_supervisor`, `demo_senior_refurbisher`, `demo_refurbisher`, `demo_user`) while keeping existing operational users.
- Expanded demo asset dataset from 4 to 10 assets across refurbishment statuses (Stand-by, Being Processed, QA Hold, Ready for Sale, Sold, Broken/Parts, Internal Use, Archived, Returned/RMA) with corresponding test runs.
- Updated `docs/demo-guide.md` so seeded account list and reset commands match actual behavior.
- Validation: `docker compose exec app php artisan migrate:fresh --seed` (pass), resulting in `assets_count=10`, `users_count=21`, `test_runs_count=10`.
- Fixed recurring dev UX issue where the assets page can appear empty after reseed/reset despite data existing: bootstrap-table persists state in long-lived cookies, so we now version the hardware index table cookie key to invalidate stale filters after DB resets.
- Investigated recurring 500s on `/hardware` after reseeds/resets and found a container-level permissions failure writing compiled Blade views (`storage/framework/views/*` permission denied). Root cause was root-owned cache artifacts created during container startup.
- Implemented a docker dev fix: update `docker/app/entrypoint.sh` to run artisan cache/view operations as `www-data` when the container starts as root and to `chown/chmod` cache directories afterwards; rebuild the `app` image so `/usr/local/bin/entrypoint.sh` matches.
- Verified current expected dev state: DB seeded (`users=21 settings=1 assets=10`) and `www-data` can write `storage/framework/views` (no more `file_put_contents` permission errors in view compilation).

# Session Progress (2026-02-10)

## Addendum (2026-02-10 Codex)
- Session kickoff: reviewed `AGENTS.md`, `PROGRESS.md`, and `docs/fork-notes.md` to align with current workflow before starting work.
- Created `docs/agents/agents-addendum-2026-02-10-session-init.md` for this session's detailed notes.
- Pending: confirm today's implementation scope and begin logging outcomes.

# Session Progress (2026-02-05)

## Addendum (2026-02-05 Codex)
- Session kickoff: reviewed AGENTS.md, PROGRESS.md, and docs/fork-notes.md to align with current workflow before resuming work.
- Hardware QR preview now renders the same label layout used for printed PDFs, so on-screen previews match print output.
- Removed the completed Latest Tests hover-column task from AGENTS.md.
- Noted that empty hardware lists still point to API/auth or persisted filters; capture `/api/v1/hardware` responses if the issue resurfaces.
- Test run edit links now open the specific run in the active tests view, and edits update its finished timestamp so it becomes the latest run.
- Marked the “resume closed test run” TODO as done after enabling targeted run editing.
- Tests not run in this environment.

# Session Progress (2026-02-03)

## Addendum (2026-02-03 Codex)
- Session kickoff: reviewed `AGENTS.md`, `PROGRESS.md`, and `docs/fork-notes.md` to align with current workflow before starting work.
- Diagnosed local access failure: `dev.snipe.inbit` resolved to a stale host entry (10.10.10.123), so requests never reached the local nginx container.
- Restored local dev host: mapped `dev.snipe.inbit` to `127.0.0.1` in the Windows hosts file, flushed DNS, and restarted app/web containers.
- Reverted local overrides so `APP_URL` and nginx match `dev.snipe.inbit` again.
- Normalized storage/cache permissions to avoid Blade view cache write errors.
- Dashboard now hides unauthorized resource blocks; dashboard counts only compute for permitted resources, and activity/status chart sections are gated by their permissions to avoid 403-visible widgets.
- Hardware list: removed the Checked Out To, Purchase Cost, and Current Value columns from the assets table layout.
- Asset tags and serials now normalize to uppercase on save with per-field override toggles in the asset form; API endpoints honor override flags and the UI enforces uppercase while typing unless overridden.
- Asset creation no longer renders checkout-to selectors in the refurb flow.
- Asset edit/create no longer show manufacturer selection; model-level manufacturer stays authoritative.
- Hardware detail no longer shows manufacturer block in the refurb flow.
- Hardware assets list no longer includes the Requestable column.
- Consolidated historical session logs into `docs/agents/agent-progress-consolidated.md`, updated `AGENTS.md` to reference the archive, and removed the duplicate `docs/agents/agents.md`.
- Archived old yearly logs and session addenda under `docs/agents/old/`.
- Pushed the above updates to `origin/master` (commit `fccab82d5`).

# Session Progress (2026-01-15)

## Addendum (2026-01-15 Codex)
- Session kickoff: reviewed `AGENTS.md`, `PROGRESS.md`, and `docs/fork-notes.md` to align with current workflow before starting work.
- Pending: confirm today's scope and start tracking outcomes.

# Session Progress (2026-01-08)

## Addendum (2026-01-08 Codex - Asset Tag/Serial Duplicates)
- Kickoff: reviewed `PROGRESS.md` and `docs/fork-notes.md` to align with current workflow before starting asset tag/serial changes.
- Asset creation now honors custom asset tags and keeps asset tags uniquely enforced, while serials can be overridden only with an explicit allow-duplicate flag.
- Added a serial-duplicate check API endpoint and wired the asset form UI to warn on conflicts, show existing matches, and allow a deliberate duplicate toggle.
- Updated request/model validation to drop serial uniqueness only when a duplicate override is requested.
- Added an unlock button to enable editing the auto-generated asset tag on create.
- Tests not run in this environment.
- Pushed changes to origin/master.

## Addendum (2026-01-08 Codex)
- Kickoff: reviewed `AGENTS.md`, `PROGRESS.md`, and `docs/fork-notes.md` to align with current workflow before starting work.
- Drafted a detailed implementation plan for the Latest Tests column counts + lazy hover detail and linked it from `AGENTS.md`.
- Updated the plan to compute counts on read and to show photo markers plus truncated note excerpts in hover details.
- Implemented compute-on-read Latest Tests counts in the assets API, added a latest-test-summary endpoint, and updated the list UI to show ratios with lazy hover details (including note excerpts and photo markers).
- Tests not run in this environment.
- Fixed MariaDB incompatibility in the assets list query by switching latest-run subqueries from IN + LIMIT to scalar subqueries.
- Added CSRF headers to the hover summary request so the API auth guard accepts the lazy-load calls.
- Pointed the hover summary request to a relative `/api/v1/hardware/` base so APP_URL mismatches do not break hover calls.
- Updated the Latest Tests hover tooltip to use per-item fail/open badges with inline note excerpts and photo markers for better readability.
- Adjusted tests-active mobile layout: hide native file inputs and keep CTA indicators right-aligned on small screens.
- Logged a TODO to align user naming + email standards after manager discussion.
- Noted status updates: tests-active graphics are done, direct printing from the asset view works, and only a few device catalog placeholders remain.

# Session Progress (2026-01-07)

## Addendum (2026-01-07 Codex)
- Kickoff: reviewed `AGENTS.md`, `PROGRESS.md`, `docs/fork-notes.md`, and recent `docs/agents/*` logs to align with current workflow before making changes.
- Logged today's session stub here and created `docs/agents/old/agents-addendum-2026-01-07-session-init.md` for detailed tracking.
- Fixed hardware image uploads to redirect back to the asset view with a flash message (non-AJAX form submissions were previously showing raw JSON).
- Fixed asset image thumbnails in the Images tab by using the public disk URL for each stored path (consistent with cover image rendering).
- Removed the temporary legacy path normalization for asset images so the Images tab reflects the current storage layout only.
- Cleaned up orphaned asset image row(s) for asset 5 where the file was missing from the public disk.
- Follow-up: after front-end changes, ensure storage/cache permissions are refreshed to avoid view cache write errors (e.g., `storage/framework/views` permission denied).
- Adjusted attribute version create flow so the browser back button returns to the attributes list after saving a new version.
- Enum options are now read-only on existing attributes; the new-version flow surfaces editable option rows (including active state) and saves them onto the new version.
- Tweaked mobile tests active CTAs to left-align the note/photo controls and keep the indicator on the right edge.
- Improved scan UX: added try-harder/inverted QR hints, faster fallback to higher resolution, reduced scan interval, simplified focus handling, and show a success overlay before redirect.
- Clearing the assets list search storage now runs after a successful scan so the hardware list is not left filtered by the scanned tag.
- Tests tab on the hardware detail page now renders each result's photos directly under its line item instead of a single strip per run.
- Asset detail now highlights failed/incomplete latest tests, and status changes to Ready for Sale/Sold prompt for confirmation with the issue list.
- Added a latest-test status badge on the asset detail view and a Tests column in asset listings, backed by test run counts in asset list APIs.
- Preserved redirect selection when status-change confirmations are required so saving after the confirm returns to the intended page.
- Confirmation submit now forces the redirect option to the asset detail page and uses requestSubmit when available.
- Tests active completion now prompts when required tests are incomplete or any tests failed, without disabling the button.
- Added `tests-active.js` to the Mix build so the tests execution UI uses the latest JS bundle.
- Tests not run yet in this environment.

# Session Progress (2025-12-30)

## Addendum (2025-12-30 Codex)
- Kickoff: reviewed `AGENTS.md`, `PROGRESS.md`, `docs/fork-notes.md`, and recent `docs/agents/*` logs to align with the current workflow before resuming work.
- Logged today's session stub here and created the `docs/agents/old/agents-addendum-2025-12-30-session-init.md` note for detailed tracking.
- Fixed attribute definition versioning validation so new versions reuse the same key without triggering the model-level unique rule (uniqueness now scopes by key + version); DB constraint already matched this behaviour.
- Tests not run yet in this environment.

# Session Progress (2025-12-23)

## Addendum (2025-12-23 Codex)
- Kickoff: reviewed `AGENTS.md`, `PROGRESS.md`, `docs/fork-notes.md`, and recent `docs/agents/*` logs before starting changes.
- Test generation now runs off Test Types (attribute-linked or category-scoped), with new optional/required support and category scoping via `category_test_type`.
- Added `is_required` to test types, surfaced it in the Test Types admin UI, and adjusted active-test progress so optional failures warn but do not block completion.
- Removed the `needs_test` field from attribute definition requests/UI and stripped it from resolver/test generation logic.
- Updated seeders, factories, and test coverage to use category-scoped test types and the new optional logic; refreshed fork notes.
- Tests not run in this environment (no PHP CLI invoked).
- Tests index: moved photo thumbnails to render under their respective result rows instead of a single strip at the bottom of each run.
- Tests active: removed the send-to-repair button and allow the completion action regardless of unfinished or failed checks (warnings handled elsewhere).
- Hardware edit: replaced the status Select2 control with a plain select so mobile does not open the keyboard.
- Session end note: context compression occurred mid-session; some conversational details were lost, but all implemented changes were captured in code/docs and committed (`Refactor test type scoping and optional tests`).
- Follow-up: renamed the former *_test attributes to capability fields (wifi, bluetooth, etc.), disabled asset overrides on them, and added a migration to rename existing records; default slugs/keys now drop the `_test` suffix.

# Session Progress (2025-12-18)

## Addendum (2025-12-18 Codex)
- Kickoff: reviewed `AGENTS.md`, `PROGRESS.md`, `docs/fork-notes.md`, and recent `docs/agents/*` logs to align with current guidance before making changes.
- Logged today's session in `docs/agents/old/agent-progress-2025.md` and started the dated addendum in `docs/agents/old/agents-addendum-2025-12-18-session-init.md` for detailed notes.
- Added the 2025-12-18 entry to `docs/agents/old/agent-progress-2025.md` to keep the consolidated log current.
- Reset DB with core seeds (categories, manufacturers, attributes, presets, tests) and re-seeded demo assets when verifying; noted that prod will need a one-off to mark test attributes and link test types.
- Updated model-number select list to show the model-number code; model-number creation now redirects to the new model number detail.
- Scan page: added camera selector, permission request button, and refreshed the JS to populate devices after permission.
- Enforced serial uniqueness unconditionally (removed the `unique_serial` setting bypass).
- Marked `_test` attribute definitions as `needs_test` by default in `DeviceAttributeSeeder`; identified prod needs a mapping/creation of `_test` defs to populate runs.

# Session Progress (2025-12-09)

## Addendum (2025-12-09 Codex)
- Kickoff: initialized session per `AGENTS.md` workflow; reviewed PROGRESS.md, docs/fork-notes.md, and docs/agents/old/agent-progress-2025.md to refresh current context.
- Created this dated stub to track today's work; ready for task assignments.
- Logged session kickoff in `docs/agents/old/agent-progress-2025.md` so today's notes have a dedicated addendum.
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
- Logged `docs/agents/old/agents-addendum-2025-12-02-session-init.md` to track this session; no code or config changes yet.
- Seeded latest hardware variants (430 G3/G6, Surface Pro 4/5) and reset dev DB via `php artisan migrate:fresh --seed` to validate; QR/scan refinements shipped (refocus/torch, tighter spacing); model list now shows actual model-number codes/labels.

# Session Progress (2025-11-25)

## Addendum (2025-11-25 Codex)
- Kickoff: re-read `AGENTS.md`, `docs/fork-notes.md`, and all `docs/agents/*` logs to align with the latest guidance before making changes.
- Logged this dated stub and created `docs/agents/old/agents-addendum-2025-11-25-session-init.md` to capture work for today; no code changes yet.

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
- Updated translations, validation (`StoreLabelSettings`), docs/fork-notes.md, and docs/agents/old/agent-progress-2025.md to capture the new workflow and guidance for future sessions.

## Notes for Follow-up Agents
- Run the refreshed PDFs through real Dymo LabelWriter 400 Turbo hardware for each template (especially the larger 30256 shipping roll) and tweak `config/qr_templates.php` padding if any QR codes still get cropped.
- Consider persisting a per-user "last template" preference so success notifications and other entry points can default to the roll most recently used without forcing a page reload.
- Once hardware verification is done, grab screenshots of the new sidebar widget and bulk picker for inclusion in README/docs to help downstream contributors understand the workflow without diffing code.
- TODO: configure and validate multiple print queues (storage vs workarea) via `LABEL_PRINTER_QUEUES` and the asset-page dropdown.

# Session Progress (2025-11-13)

## Addendum (2025-11-13 Codex)
- Re-read `AGENTS.md`, `PROGRESS.md`, `docs/fork-notes.md`, `docs/agents/old/agent-progress-2025.md`, and every existing `docs/agents/old/agents-addendum-*` log so today's work begins with the latest workflow rules and carry-over issues.
- Logged this dated stub and created `docs/agents/old/agents-addendum-2025-11-13-session-init.md` to capture detailed context before touching code or tests.
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
- Logged today's documentation stubs at `docs/agents/old/agents-addendum-2025-10-21-session-init.md` and `docs/agents/progress-addendum-2025-10-21-session-init.md` to capture detailed notes as work progresses.
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
- Logged new documentation stubs in docs/agents/old/agents-addendum-2025-10-14-session-init.md and docs/agents/progress-addendum-2025-10-14-session-init.md for detailed notes as the day advances.
- Fixed the asset model index API so persisted table offsets clamp to the last available page instead of returning an empty dataset, and added regression coverage for the scenario.
- Promoted the offset clamp into the shared API controller base and rolled it out across list endpoints (assets, accessories, locations, etc.), with fresh assets index coverage to guard the shared helper.
- Introduced attribute definition versioning, hide/unhide workflows, and supporting UI/actions/tests so teams can migrate specs safely.
- Delivered a model-number specification builder: new assignment/reorder endpoints, a three-column search-enabled UI, updated attribute resolution logic, and accompanying feature/unit coverage for assign/remove flows.

## Notes for Follow-up Agents
- Detailed worklog: docs/agents/progress-addendum-2025-10-14-session-init.md (extend with concrete updates and test evidence).
- Handbook updates: docs/agents/old/agents-addendum-2025-10-14-session-init.md (record any process clarifications introduced today).
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

# Session Progress (2026-04-09)
- Session kickoff: re-read `AGENTS.md`, `PROGRESS.md`, `docs/fork-notes.md`, `TODO.md`, and the latest dated agent addenda to reinitialize current fork context after the 2026-04-07 push.
- Added `docs/agents/agents-addendum-2026-04-09-session-init.md` for today and reconfirmed the active carry-over state before new work begins.
- Known environment blockers remain unchanged at session start:
- sqlite-backed PHPUnit runs are still vulnerable to `database disk image is malformed` in the current container workflow.
- `EditAssetTest` and related UI paths may still hit the existing Livewire support-file-uploads bootstrap issue.
- Existing local-only changes present at session start were left untouched:
- `docker-compose.yml`
- `docker/nginx.conf`
- `docs/agents/agents-addendum-2026-03-19-session-init.md`
- Fixed mobile overflow for shared list-page bulk-action toolbars by removing hardcoded 400-500px minimum widths and making the shared toolbar/select/button layout stack within the viewport on narrow screens.
- Tightened the hardware QR widget controls so the template/printer selects and print button stay within the panel width on small screens.
- Added focused view-level assertions for the responsive bulk-toolbar markup and QR printer control constraints.
- Investigated the test-environment safety failure that had been hitting the live dev MySQL database during PHPUnit runs.
- Root cause: the local Docker app entrypoint was warming cached Laravel config in `APP_ENV=local`, and that cached config could override PHPUnit testing DB settings.
- Added a hard pre-boot test guard in `tests/TestCase.php` that refuses to run tests while `bootstrap/cache/config.php` exists and validates that `.env.testing` is configured for the approved sqlite test DB target.
- Updated the active Docker app entrypoint to keep `local` / `testing` containers uncached (`optimize:clear` only, no `optimize` warmup); production-like environments can still warm caches.
- Updated `AGENTS.md` to require clearing cached config before PHPUnit runs inside Docker because cached config is not a safe testing baseline in this repo.
- Verification:
- `docker compose exec app php artisan view:cache` (pass)
- `docker compose exec app php artisan test tests/Feature/Assets/Ui/AssetIndexTest.php --env=testing` (blocked by current MySQL test DB migration-state drift: unknown/drop-missing tables and missing `migrations`)
- `docker compose exec app php artisan test tests/Feature/Assets/Ui/ShowAssetTest.php --env=testing` (10 passed, 2 failed; new responsive assertions passed, remaining failures were unrelated pre-existing environment/app-state issues around duplicate `users` migration setup and the existing checkout-date assertion mismatch)
- `docker compose exec app php artisan test tests/Feature/Assets/Ui/AssetIndexTest.php --env=testing --filter=testPageRenders` with config cache present (expected fast-fail via new guard before DB work)
- `docker compose exec app php artisan optimize:clear` (pass)
- `docker compose exec app php artisan test tests/Feature/Assets/Ui/AssetIndexTest.php --env=testing --filter=testPageRenders` after cache clear (now resolves to sqlite again; blocked only by the existing sqlite corruption: `database disk image is malformed`)
- Dev DB recovery:
- preflight before reseed confirmed:
- `APP_ENV=local`
- `DB_CONNECTION=mysql`
- `DB_DATABASE=snipeit`
- restored the empty local dev database with `docker compose exec app php artisan db:seed --force`.
- post-seed verification restored the expected demo baseline:
- `users=21`
- `assets=10`
- `settings=1`
- `test_runs=10`
- `models=10`
- `statuslabels=9`
- Test type slug workflow cleanup:
- test type create/edit now defaults slugs to an auto-generated normalized value from the current name while keeping a manual override checkbox available for admins.
- manual overrides are sanitized to the standard lowercase hyphenated slug format before save, so punctuation and other odd characters do not persist in stored slugs.
- auto-generated and manual override slugs now resolve collisions by appending a numeric suffix (`-2`, `-3`, etc.) before validation/save instead of surfacing a raw unique-key failure path.
- added focused feature coverage for create auto-generation, duplicate-name suffixing, update auto-sync from name, and sanitized manual override behavior.
- verification:
- `docker compose exec app php -l app/Models/TestType.php` (pass)
- `docker compose exec app php -l app/Http/Requests/TestType/StoreTestTypeRequest.php` (pass)
- `docker compose exec app php -l app/Http/Requests/TestType/UpdateTestTypeRequest.php` (pass)
- `docker compose exec app php -l app/Http/Controllers/Admin/TestTypeController.php` (pass)
- `docker compose exec app php -l tests/Feature/Settings/ManageTestTypesTest.php` (pass)
- `docker compose exec app php artisan view:cache` (pass)
- `docker compose exec app php artisan test tests/Feature/Settings/ManageTestTypesTest.php --env=testing` (blocked by the pre-existing sqlite testing DB corruption: `database disk image is malformed`)
- Attribute definition create-key workflow cleanup:
- added centralized key helpers on `AttributeDefinition` so keys are normalized to snake_case and auto-suffixed on collisions (`_2`, `_3`, ...) against active attribute records.
- moved create key generation into `AttributeDefinitionRequest::prepareForValidation()` with `manual_key_override` support.
- default create behavior now derives key from `label`; manual override uses submitted key input (with label fallback if blank).
- kept update/version key immutability semantics unchanged.
- updated the attribute create form so key is disabled by default, manual override is explicit, and key text is normalized live while typing.
- added focused feature coverage in `AttributeDefinitionLifecycleTest` for:
- create auto-generated key from label.
- create collision suffixing (`battery_health_2` pattern).
- sanitized manual override plus suffixing on collision.
- explicit key-change rejection on update.
- verification:
- `docker compose exec app php -l app/Models/AttributeDefinition.php` (pass)
- `docker compose exec app php -l app/Http/Requests/AttributeDefinitionRequest.php` (pass)
- `docker compose exec app php -l resources/views/attributes/edit.blade.php` (pass)
- `docker compose exec app php -l tests/Feature/AttributeDefinitionLifecycleTest.php` (pass)
- `docker compose exec app php artisan view:cache` (pass)
- `docker compose exec app php artisan test tests/Feature/AttributeDefinitionLifecycleTest.php --env=testing` (blocked by existing sqlite testing DB corruption: `database disk image is malformed`)

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


## Notes for Next Session (2025-11-19)
- TODO: Clean up the QR label sizing/margins once more and validate on hardware (short-term).
- TODO: Implement one-click direct printing from the asset view to connected/network LabelWriter printers (long-term).

# Session Progress (2025-12-17)
- Reviewed AGENTS.md, fork-notes, and recent agent addenda before making changes; started this session log for traceability.
- Investigated the specification details table overflow on narrow mobile widths; identified Bootstrap's responsive table rule forcing `white-space: nowrap` as the cause of horizontal overflow.
- Added a targeted mobile override to allow spec table cells to wrap within their parent so the block stays inside the asset view at ~327 px widths.
- Made the scan camera viewport dynamically size itself to the incoming stream aspect ratio while staying within the page frame; height now adapts per device instead of staying at a fixed width/aspect.
- Removed leftover manual-entry hooks from the scan script that were throwing a runtime error and blocking camera startup after the manual form was dropped.
- Follow-up: verify the asset view on an A5/phone viewport after cache clears; rerun Laravel view/config caches if needed once deployed.

# Session Progress (2026-01-13)

## Addendum (2026-01-13 Codex)
- Session kickoff: reviewed `AGENTS.md`, recent `PROGRESS.md` entries, and `docs/fork-notes.md` to refresh context before new work.
- Pending: confirm today's scope and start tracking outcomes.
- Open-point sweep: reviewed TODO.md, PROGRESS.md, docs/agents/*, and docs/plans/* for outstanding items.
- Updated login landing for non-admin/refurbisher users: start now redirects them to the dashboard, and the dashboard no longer falls back to the account view.
- Defaulted the new-user language selection to the creator/app locale so fresh accounts inherit the expected language when none is set explicitly.
- Simplified asset creation: removed manufacturer and requestable fields on create, and moved the status selector above spec overrides.
- Redirected all roles away from the start shortcuts to the main dashboard, and made logins land on `/` instead of `/start`.
- Hid requestable on asset edit and added a status-only update form on the hardware detail page.
- Hid requestable items from user navigation and asset detail, and disabled the requestable assets index with a 404 to keep the UI aligned with the no-checkout workflow.
- Investigated report that non-admin users with asset permissions see an empty hardware list: web `hardware.index` loads `api.assets.index`, which only requires `assets.view` and has no company scoping when FMCS is off, so the likely causes are API 401/403 or missing/denied `assets.view` in the user's permissions.

## Notes for Follow-up Agents (2026-01-13)
- Reproduce as the affected user and capture the `/api/v1/hardware` response (status + payload) to see if it is auth (401/403) or an empty dataset.
- Verify the user has `assets.view` granted (not inherited or explicitly denied) in the `users.permissions` JSON and that no group sets `assets.view` to `-1`.
- If the API is 401/403, check Passport cookie flow for web sessions and confirm the request is authenticated; if it is 200 with no rows, inspect any persisted table filters (`status`, `status_id`, search) and remove them.

# Session Progress (2026-04-07)
- Simplified the active test-run detail screen by removing the large top testing header and moving the live save/progress/history/start-run controls into the existing bottom action bar.
- Disabled the old hidden two-column preference on the active testing screen so users now stay on the single-column card flow after the header/toggle removal.
- Updated the dedicated active testing view feature test to assert the header is gone and the bottom-bar summary controls remain present.
- Moved the hardware detail QR print/download panel below the primary action buttons (`Edit`, `Run Test`, `Add Note`, `Clone`, `Delete`) so the main action stack stays grouped before the print module.
- Verification:
- `docker compose exec app php artisan view:cache` (pass)
- `docker compose exec app php artisan test tests/Feature/Tests/ActiveTestViewTest.php --env=testing` (blocked by existing sqlite testing DB corruption: `database disk image is malformed`)

# Session Progress (2026-04-09)
- traced the page-title overlap in intermediate widths to fixed-layout header offset drift: the shared layout relied on hardcoded top offsets while the custom navbar can grow in height as nav content wraps.
- confirmed a second contributing factor in that viewport band: `.main-header { max-height: 150px; }` capped header growth, so wrapped nav rows could spill into the content area instead of pushing it down.
- replaced the ad hoc fixed-offset override in `resources/views/layouts/default.blade.php` with a CSS-variable-based offset (`--fixed-header-offset`) plus runtime sync from the actual `.main-header` height.
- added a `<=991px` override to remove the header max-height cap so the top nav can expand naturally when content wraps.
- kept the existing `<=991px` content-header/pagetitle wrapping behavior so breadcrumb/title flow remains unchanged, while removing static fixed-offset guesses.
- verification:
- `docker compose exec app php artisan view:cache` (pass)
- model number create/edit UX follow-up:
- added serial-style case handling to model number code inputs (`Aa` toggle with hidden override flag) on model-number create/edit pages; uppercase is now the default behavior in the form unless override is enabled.
- enforced the same behavior server-side in `ModelNumberController` by normalizing code to uppercase unless `code_case_override` is true, so direct/manual posts follow the same rules as the UI.
- removed the model-number form checkbox for "Make this the default selection for new assets."
- verification of your default-selection question:
- model numbers are exposed individually in the selector API (`id` is `model_id:model_number_id` with explicit `model_number_id` meta).
- primary model number is still used as fallback in legacy/model-only flows, so backend primary logic remains in place for compatibility.
- verification:
- `docker compose exec app php -l app/Http/Controllers/Admin/ModelNumberController.php` (pass)
- `docker compose exec app php -l tests/Feature/Models/ModelNumberManagementTest.php` (pass)
- `docker compose exec app php artisan view:cache` (pass)
- `docker compose exec app php artisan test tests/Feature/Models/ModelNumberManagementTest.php --env=testing` (blocked by existing sqlite testing DB corruption: `database disk image is malformed`)
- model number breadcrumbs follow-up:
- added route-level breadcrumbs for `models.numbers.edit` and `models.numbers.spec.edit` in `BreadcrumbsServiceProvider`, parented under `models.show`.
- model-number edit and spec-edit pages now render breadcrumb trails automatically via the shared default layout.
- added focused breadcrumb assertions to `ModelNumberManagementTest`.
- verification:
- `docker compose exec app php -l app/Providers/BreadcrumbsServiceProvider.php` (pass)
- `docker compose exec app php -l tests/Feature/Models/ModelNumberManagementTest.php` (pass)
- `docker compose exec app php artisan view:cache` (pass)
- `docker compose exec app php artisan test tests/Feature/Models/ModelNumberManagementTest.php --env=testing --filter="breadcrumb"` (blocked by existing sqlite testing DB corruption: `database disk image is malformed`)
- hardware create/edit save action follow-up:
- added a mobile-standard floating save CTA on hardware create/edit (`visible-xs`/`visible-sm`) that submits the existing `create-form`.
- added xs/sm bottom content padding so the fixed save button does not cover lower form inputs.
- added a focused UI assertion in `EditAssetTest` for the floating save button markers on both create and edit routes.
- verification:
- `docker compose exec app php -l resources/views/hardware/edit.blade.php` (pass)
- `docker compose exec app php -l tests/Feature/Assets/Ui/EditAssetTest.php` (pass)
- `docker compose exec app php artisan view:cache` (pass)
- `docker compose exec app php artisan test tests/Feature/Assets/Ui/EditAssetTest.php --env=testing --filter="testCreateAndEditPagesRenderMobileFloatingSaveButton"` (blocked by existing sqlite testing DB corruption: `database disk image is malformed`)
- hardware edit save crash follow-up:
- fixed a backend type crash in `AssetsController@update` where `serials` (array form payload) was being assigned directly to `$asset->serial` before extracting index `1`.
- update now normalizes serial input to a scalar (`serials[1]` when array, otherwise scalar/null) before assignment so `Asset::normalizeIdentifier(?string ...)` no longer receives an array.
- added focused regression coverage in `EditAssetTest` for serial array payload shape used by the hardware edit form.
- verification:
- `docker compose exec app php -l app/Http/Controllers/Assets/AssetsController.php` (pass)
- `docker compose exec app php -l tests/Feature/Assets/Ui/EditAssetTest.php` (pass)
- `docker compose exec app php artisan view:cache` (pass)
- `docker compose exec app php artisan test tests/Feature/Assets/Ui/EditAssetTest.php --env=testing --filter="testEditAcceptsSerialArrayInputFromFormShape"` (blocked by existing sqlite testing DB corruption: `database disk image is malformed`)

- test pages start-run visibility follow-up:
- kept `Start New Run` available on all widths (including mobile) on:
- `resources/views/tests/active.blade.php` (empty-state CTA + active-run floating-bar secondary action)
- `resources/views/tests/index.blade.php` (history-page top CTA)
- normalized the temporary test markers to width-agnostic names (`tests-empty-start-run-form`, `tests-start-new-run-form`, `tests-index-start-run-form`).
- updated `tests/Feature/Tests/ActiveTestViewTest.php` and `tests/Feature/Assets/Ui/ShowAssetTest.php` assertions to match the width-agnostic markers.
- verification:
- `docker compose exec app php -l resources/views/tests/active.blade.php` (pass)
- `docker compose exec app php -l resources/views/tests/index.blade.php` (pass)
- `docker compose exec app php -l tests/Feature/Tests/ActiveTestViewTest.php` (pass)
- `docker compose exec app php -l tests/Feature/Assets/Ui/ShowAssetTest.php` (pass)
- `docker compose exec app php artisan test tests/Feature/Tests/ActiveTestViewTest.php tests/Feature/Assets/Ui/ShowAssetTest.php --env=testing` (blocked by existing sqlite testing DB corruption: `database disk image is malformed`)

- tests index UX redesign follow-up:
- redesigned `resources/views/tests/index.blade.php` into expandable run rows so users see a clearer run list first (date/user/status summary), with edit/delete actions directly in each row.
- added dropdown chevron + row-header click behavior to expand/collapse per-run details (results, notes, photos) without leaving the page.
- kept existing routes, permissions, and photo modal behavior unchanged; this is a view-only interaction/layout update.
- extended `tests/Feature/Assets/Ui/ShowAssetTest.php` assertions to cover the new run-row/toggle/details/action structure markers.
- verification:
- `docker compose exec app php -l resources/views/tests/index.blade.php` (pass)
- `docker compose exec app php -l tests/Feature/Assets/Ui/ShowAssetTest.php` (pass)
- `docker compose exec app php artisan optimize:clear` (pass)
- `docker compose exec app php artisan test tests/Feature/Assets/Ui/ShowAssetTest.php --env=testing --filter=testTestsIndexUsesStructuredResultRows` (blocked by existing sqlite testing DB corruption: `database disk image is malformed`)

- hardware detail tests-tab UX parity follow-up:
- ported the same row + dropdown test-run layout into `resources/views/hardware/view.blade.php` so the tests list inside `/hardware/{id}#tests` now matches the redesigned dedicated tests page interaction.
- moved run-level `Edit`/`Delete` actions into the row header and made details (results/photos/notes) collapse under each row.
- added row-header click delegation on the hardware tests tab to toggle details when users click anywhere on the row except the action buttons.
- added a new page marker assertion in `tests/Feature/Assets/Ui/ShowAssetTest.php` (`hardware-tests-run-list`) for the hardware tests-tab structure.
- verification:
- `docker compose exec app php -l resources/views/hardware/view.blade.php` (pass)
- `docker compose exec app php -l tests/Feature/Assets/Ui/ShowAssetTest.php` (pass)
- `docker compose exec app php artisan view:clear` (pass)
- `docker compose exec app php artisan optimize:clear` (pass)
- `docker compose exec app php artisan test tests/Feature/Assets/Ui/ShowAssetTest.php --env=testing --filter=testDetailPageTestsTabUsesSingleColumnRunList` (blocked by existing sqlite testing DB corruption: `database disk image is malformed`)

- tests index compact-row refinement follow-up:
- adjusted `resources/views/tests/index.blade.php` mobile row-header layout to stay one-line/high on small widths (removed forced wrap behavior and collapsed run identity/date/user into a single truncating primary line).
- kept inline row actions and toggle behavior intact.
- verification:
- `docker compose exec app php -l resources/views/tests/index.blade.php` (pass)
- `docker compose exec app php artisan view:clear` (pass)

- hardware tests-tab compact-row follow-up:
- adjusted `resources/views/hardware/view.blade.php` run-row header for small widths to keep each run in one compact row (no forced stacked summary/action rows).
- merged run id/date/user into a single truncating primary line, tightened mobile spacing/button sizing, and removed mobile wrap rules that were pushing headers into multiple lines.
- verification:
- `docker compose exec app php -l resources/views/hardware/view.blade.php` (pass)
- `docker compose exec app php artisan view:clear` (pass)

- tests active mobile card-density follow-up:
- updated `resources/views/tests/active.blade.php` mobile (`max-width: 576px`) card styles so Note and Photo CTAs remain side-by-side (2 columns) instead of stacking.
- slightly reduced mobile card-body spacing/padding below the title area to flatten each card visually while preserving existing interaction behavior.
- verification:
- `docker compose exec app php -l resources/views/tests/active.blade.php` (pass)
- `docker compose exec app php artisan view:clear` (pass)

- asset create redirect UX follow-up:
- removed the redirect destination dropdown from the asset **create** form in `resources/views/hardware/edit.blade.php` while keeping redirect options on asset **edit**.
- this eliminates the broken/clipping create-form redirect selector and keeps create flow defaulting to the new asset detail page (`item`) via existing controller behavior.
- added focused coverage updates:
- `tests/Feature/Assets/Ui/EditAssetTest.php`: create page does not render redirect select; edit page still does.
- `tests/Feature/Assets/Ui/StoreAssetWithMinimalDataTest.php`: create request now explicitly asserts redirect to `hardware.show` for the created asset.
- verification:
- `docker compose exec app php -l resources/views/hardware/edit.blade.php` (pass)
- `docker compose exec app php -l tests/Feature/Assets/Ui/EditAssetTest.php` (pass)
- `docker compose exec app php -l tests/Feature/Assets/Ui/StoreAssetWithMinimalDataTest.php` (pass)
- `docker compose exec app php artisan optimize:clear` (pass)
- `docker compose exec app php artisan test tests/Feature/Assets/Ui/StoreAssetWithMinimalDataTest.php tests/Feature/Assets/Ui/EditAssetTest.php --env=testing --filter="asset_can_be_created_with_minimal_data|testCreateAndEditPagesRenderMobileFloatingSaveButton"` (blocked by existing sqlite testing DB corruption: `database disk image is malformed`)

- asset name field scope fix follow-up:
- traced create-page `name` field to direct include in `resources/views/hardware/edit.blade.php` (`@include('partials.forms.edit.name', ...)`), introduced in commit `5a291ec80c` (2026-04-07), not by the redirect dropdown fix.
- updated the view so `name` renders only for existing assets (`$item->id`), removing it from hardware create flow where naming is model-driven.
- extended `tests/Feature/Assets/Ui/EditAssetTest.php` assertion: create page does not render `name="name"` while edit page still does.
- verification:
- `docker compose exec app php -l resources/views/hardware/edit.blade.php` (pass)
- `docker compose exec app php -l tests/Feature/Assets/Ui/EditAssetTest.php` (pass)
- `docker compose exec app php artisan view:clear` (pass)
- `docker compose exec app php artisan optimize:clear` (pass)
- `docker compose exec app php artisan test tests/Feature/Assets/Ui/EditAssetTest.php --env=testing --filter=testCreateAndEditPagesRenderMobileFloatingSaveButton` (blocked by existing sqlite testing DB corruption: `database disk image is malformed`)

- legacy post-create QR notification removal follow-up:
- removed the old blue `Print QR` session-notification dropdown from `resources/views/notifications.blade.php` (the non-labelwriter path that was misleading/non-working).
- removed asset-create flash payload of `qr_pdf`/`qr_png` from `AssetsController@store` so the deprecated notification path is no longer fed.
- kept the hardware detail QR label widget flow unchanged (download QR label / print to labelwriter remains the supported path).
- extended `tests/Feature/Assets/Ui/StoreAssetWithMinimalDataTest.php` to assert `qr_pdf` and `qr_png` are absent from session after asset create.
- verification:
- `docker compose exec app php -l app/Http/Controllers/Assets/AssetsController.php` (pass)
- `docker compose exec app php -l tests/Feature/Assets/Ui/StoreAssetWithMinimalDataTest.php` (pass)
- `docker compose exec app php artisan view:clear` (pass)
- `docker compose exec app php artisan optimize:clear` (pass)
- code search check for `qr_pdf`/`qr_png`/`qr-notification` in `resources/views` + `app` (no remaining hits)
- `docker compose exec app php artisan test tests/Feature/Assets/Ui/StoreAssetWithMinimalDataTest.php --env=testing` (blocked by existing sqlite testing DB corruption: `database disk image is malformed`)

## 2026-04-16
- session re-init and docs sync:
- created `docs/agents/agents-addendum-2026-04-16-session-init.md`.
- re-read `AGENTS.md`, `PROGRESS.md`, and `docs/fork-notes.md`.

- hardware list mobile toolbar alignment follow-up:
- fixed bootstrap-table mobile toolbar icon fragmentation where controls wrapped one-per-row.
- adjusted `resources/views/partials/bootstrap-table.blade.php` mobile rules so toolbar icon groups remain auto-width and wrap in compact rows.
- verification:
- `docker compose exec app php -l resources/views/partials/bootstrap-table.blade.php` (pass)
- `docker compose exec app php artisan view:clear` (pass)

- hardware detail tests FAB consistency follow-up:
- changed mobile tests floating action in `resources/views/hardware/view.blade.php` from icon-only circle to save-style pill with visible label (`tests.start_new_run`) to align with the hardware mobile save CTA pattern.
- added marker assertion in `tests/Feature/Assets/Ui/ShowAssetTest.php` for the FAB label.
- verification:
- `docker compose exec app php -l resources/views/hardware/view.blade.php` (pass)
- `docker compose exec app php -l tests/Feature/Assets/Ui/ShowAssetTest.php` (pass)
- `docker compose exec app php artisan optimize:clear` (pass)
- `docker compose exec app php artisan test tests/Feature/Assets/Ui/ShowAssetTest.php --env=testing --filter=testDetailPageRendersResponsiveTestsStartRunActions` (blocked by existing sqlite testing DB corruption: `database disk image is malformed`)

- scan page viewport expansion follow-up:
- widened the scan page container in `resources/views/scan/index.blade.php` by removing 720px width caps and switching to fluid wrapper layout for larger viewport usage.
- increased scan area minimum height and reduced outer padding so camera occupies more screen space.
- adjusted small-screen scan viewport toward portrait usage (`aspect-ratio: 3 / 4` with higher vh-driven minimum height) to reduce left/right letterboxing and make the camera area visibly taller on phones.
- verification:
- `docker compose exec app php -l resources/views/scan/index.blade.php` (pass)
- `docker compose exec app php artisan view:clear` (pass)

- model specification validation visibility follow-up:
- improved `resources/views/models/spec.blade.php` error UX with a top-level attribute error navigator that lists failing fields and allows one-click jump/focus to the related attribute detail panel.
- added invalid-state highlighting on selected attribute rows and detail panels via `resources/views/models/model_numbers/partials/selected-attribute-item.blade.php` and `resources/views/models/model_numbers/partials/attribute-detail.blade.php`.
- updated spec page JS initialization to auto-open the first invalid attribute when errors are present instead of always opening the first selected attribute.
- updated `app/Services/ModelAttributes/ModelAttributeManager.php` required-attribute validation to emit both summary (`attributes`) and per-field (`attributes.{id}`) errors so all failing fields can be surfaced/highlighted in one pass.
- added focused UI coverage in `tests/Feature/Models/ModelSpecificationUiTest.php` for:
- navigator rendering on attribute validation errors.
- per-field required-attribute error emission.
- verification:
- `docker compose exec app php -l app/Services/ModelAttributes/ModelAttributeManager.php` (pass)
- `docker compose exec app php -l resources/views/models/spec.blade.php` (pass)
- `docker compose exec app php -l resources/views/models/model_numbers/partials/selected-attribute-item.blade.php` (pass)
- `docker compose exec app php -l resources/views/models/model_numbers/partials/attribute-detail.blade.php` (pass)
- `docker compose exec app php -l tests/Feature/Models/ModelSpecificationUiTest.php` (pass)
- `docker compose exec app php artisan optimize:clear` (pass)
- `docker compose exec app php artisan test tests/Feature/Models/ModelSpecificationUiTest.php --env=testing` (blocked by existing sqlite testing DB corruption: `database disk image is malformed`)

- model spec parse error hotfix:
- fixed a Blade parse regression in `resources/views/models/spec.blade.php` by replacing one-line `@php(...)` assignment with a standard `@php ... @endphp` block at the top of the section.
- cleared and rebuilt compiled views in container; linted the compiled spec view file to confirm no syntax errors remain.
- verification:
- `docker compose exec app php artisan view:clear` (pass)
- `docker compose exec app php artisan view:cache` (pass)
- `docker compose exec app sh -lc "grep -R -n 'assignedDefinitionIds' storage/framework/views | head"` (pass)
- `docker compose exec app php -l storage/framework/views/38ebbffe634f906cee186992fa90cb21.php` (pass)

- test types/task ordering support:
- added persistent `display_order` to `test_types` via migration (`2026_04_16_110000_add_display_order_to_test_types_table.php`) with backfill from current alphabetical order.
- added admin reorder API endpoint `PATCH admin/testtypes/reorder` and request validation (`ReorderTestTypesRequest`) to save drag-and-drop ordering safely.
- updated test type management UI (`resources/views/settings/testtypes.blade.php`) with draggable row handles and client-side persistence calls to the reorder endpoint.
- switched test type selection/query ordering to `display_order` (with `name/id` fallback) and updated active run result ordering to follow configured test order.
- updated test run creation flow so new run tasks are created in configured `display_order`.
- added feature coverage:
- `ManageTestTypesTest::test_admin_can_reorder_test_types`
- `StartNewTestRunTest::test_start_new_run_uses_display_order_for_created_results`
- verification:
- `docker compose exec app php -l app/Models/TestType.php` (pass)
- `docker compose exec app php -l app/Models/TestRun.php` (pass)
- `docker compose exec app php -l app/Http/Controllers/Admin/TestTypeController.php` (pass)
- `docker compose exec app php -l app/Http/Controllers/TestRunController.php` (pass)
- `docker compose exec app php -l app/Http/Controllers/TestResultController.php` (pass)
- `docker compose exec app php -l app/Http/Requests/TestType/ReorderTestTypesRequest.php` (pass)
- `docker compose exec app php -l database/migrations/2026_04_16_110000_add_display_order_to_test_types_table.php` (pass)
- `docker compose exec app php -l database/factories/TestTypeFactory.php` (pass)
- `docker compose exec app php -l tests/Feature/Settings/ManageTestTypesTest.php` (pass)
- `docker compose exec app php -l tests/Feature/Assets/StartNewTestRunTest.php` (pass)
- `docker compose exec app php artisan route:list --name=settings.testtypes.reorder` (pass)
- `docker compose exec app php artisan view:clear` (pass)
- `docker compose exec app php artisan view:cache` (pass)
- `docker compose exec app php artisan test tests/Feature/Settings/ManageTestTypesTest.php tests/Feature/Assets/StartNewTestRunTest.php --env=testing` (blocked by existing sqlite testing DB corruption: `database disk image is malformed`)

- test type drag reorder interaction fix:
- replaced HTML5 table-row drag handling on `resources/views/settings/testtypes.blade.php` with jQuery UI `sortable()` using the drag handle as the reorder handle.
- retained the same reorder persistence endpoint (`settings.testtypes.reorder`) and rollback-on-failure behavior.
- fixed a script-stack wiring bug where the page used `@push('scripts')` while the layout renders `@stack('js')`; moved the reorder script to `@push('js')` so drag behavior initializes.
- adjusted drag handle visuals to be larger and centered in the reorder column.
- verification:
- `docker compose exec app php -l resources/views/settings/testtypes.blade.php` (pass)
- `docker compose exec app php artisan view:clear` (pass)
- `docker compose exec app php artisan view:cache` (pass)

- components replacement / traceability planning:
- added a full handoff-ready implementation plan at `docs/plans/components-replacement-part-traceability-work-orders.md`.
- plan scope covers:
- replacing the old pooled `components` module with unique component definitions/instances/events.
- persisted tray flow with stale-item verification escalation.
- asset-page expected/default components vs installed/history separation.
- mobile-first QR/search remove/install workflows.
- customer work-order and read-only portal foundation.
- no code execution verification was required for the planning document itself.

- test type drag reorder compatibility follow-up:
- replaced pointer-only drag handling in `resources/views/settings/testtypes.blade.php` with a dual-path implementation:
- pointer events path for modern browsers.
- explicit mouse/touch fallback path for browsers with incomplete pointer support.
- added permissive primary-pointer detection and non-`fetch` AJAX fallback for reorder persistence.
- retained rollback behavior when reorder persistence fails.
- verification:
- `node --check storage/tmp-testtypes-reorder.js` (pass; extracted script syntax check)
- `docker compose exec app php artisan view:clear` (pass)
- `docker compose exec app php artisan view:cache` (pass)
- `docker compose exec app php artisan test tests/Feature/Settings/ManageTestTypesTest.php --env=testing` (blocked by existing sqlite testing DB corruption: `database disk image is malformed`)


