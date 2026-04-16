# Agents Addendum - 2026-04-09 Session Init

## Context
- Reviewed `AGENTS.md`, `PROGRESS.md`, `docs/fork-notes.md`, and `TODO.md` before resuming work.
- Re-read the 2026-04-07, 2026-04-02, and 2026-03-19 addenda to reload the latest hardware-detail, testing-flow, and mobile UX carry-over.
- Current working assumption remains unchanged: phone-first behavior still matters most for the ongoing refurb UX cleanup.

## Files Reviewed
- `AGENTS.md`
- `PROGRESS.md`
- `docs/fork-notes.md`
- `TODO.md`
- `docs/agents/agents-addendum-2026-04-07-session-init.md`
- `docs/agents/agents-addendum-2026-04-02-session-init.md`
- `docs/agents/agents-addendum-2026-03-19-session-init.md`

## Session Initialization
- Created this addendum file for today.
- Added the 2026-04-09 session stub to `PROGRESS.md`.
- Reconfirmed that the 2026-04-07 hardware/testing UX changes were already committed and pushed before this session restart.

## Carry-Over Summary
- Recent shipped state centers on the hardware detail page and testing UX:
- hardware detail status/quality updates save immediately.
- hardware detail uses a simplified action stack and a lower-positioned QR print/download module.
- the tests tab now uses a clipboard-check icon, a single-column run list, and mobile-first run-start affordances.
- the active test-run detail page no longer uses the large top header and instead keeps save/progress/history controls in the bottom action bar.
- Shared mobile breadcrumb/hamburger behavior was narrowed to a small xs-only float/padding fix rather than a layout rewrite.

## Open Items
- `TODO.md` still tracks:
- QR label layout cleanup.
- Replace remaining placeholder device catalog MPN/SKU codes.
- Improve mobile scan feedback and close-range behavior.
- Decide user naming/email convention.
- Decide battery-health auto-calculation behavior.
- Decide whether user-facing wording should remain `tests` or shift to `tasks`.
- `tests/Feature/Assets/Ui/ReadyForSaleWarningTest.php` remains the explicit unresolved failing test carried in recent handoffs.

## Worktree Notes
- Existing local-only changes were present at session start in:
- `docker-compose.yml`
- `docker/nginx.conf`
- `docs/agents/agents-addendum-2026-03-19-session-init.md`
- These were left intact during initialization.

## Environment Notes
- Focused PHPUnit execution is still environment-sensitive here:
- sqlite-backed test runs may fail with `database disk image is malformed`.
- some UI test paths can still be affected by the known Livewire support-file-uploads bootstrap issue.

## Session Updates
- fixed mobile overflow in the shared bootstrap-table bulk-action toolbars by removing hardcoded minimum widths from the reusable bulk-action partials and adding shared xs-friendly stacking/wrapping rules in the bootstrap-table include.
- tightened the hardware QR widget controls so the template selector, printer selector, and print button keep `max-width: 100%` and stay inside the page width on narrow screens.
- added focused view-level assertions in `AssetIndexTest` and `ShowAssetTest` for the responsive toolbar markup and constrained QR controls.
- investigated the destructive testing-env drift that had been hitting the live dev DB during PHPUnit runs.
- confirmed the core cause: the local Docker app entrypoint warmed cached Laravel config in `APP_ENV=local`, and that cache could override PHPUnit testing DB settings even when `.env.testing` and `phpunit.xml` pointed at sqlite.
- added a pre-boot guard in `tests/TestCase.php` that aborts tests while `bootstrap/cache/config.php` exists and validates that `.env.testing` targets the approved sqlite DB path before the application boots.
- updated `docker/app/entrypoint.sh` so local/testing containers clear caches but do not re-run `artisan optimize`; this keeps config uncached in dev and preserves PHPUnit DB isolation on future container starts.
- updated `AGENTS.md` with the explicit rule that Docker-based PHPUnit runs must start from `php artisan optimize:clear`, because cached config is unsafe for this repo's testing workflow.
- verification:
- `docker compose exec app php artisan view:cache` (pass)
- `docker compose exec app php artisan test tests/Feature/Assets/Ui/AssetIndexTest.php --env=testing` (blocked by current MySQL test DB migration-state drift: missing/unknown tables during reset and missing `migrations`)
- `docker compose exec app php artisan test tests/Feature/Assets/Ui/ShowAssetTest.php --env=testing` (10 passed, 2 failed; the new responsive assertions passed, remaining failures were unrelated environment/app-state issues around duplicate `users` migration setup and an existing checkout-date assertion mismatch)
- `docker compose exec app php artisan test tests/Feature/Assets/Ui/AssetIndexTest.php --env=testing --filter=testPageRenders` with config cache present (fast-failed via the new guard before DB work)
- `docker compose exec app php artisan optimize:clear` (pass)
- `docker compose exec app php artisan test tests/Feature/Assets/Ui/AssetIndexTest.php --env=testing --filter=testPageRenders` after cache clear (now reaches sqlite again and is blocked only by the pre-existing sqlite corruption: `database disk image is malformed`)
- recovered the empty local dev MySQL database after explicit preflight:
- `APP_ENV=local`
- `DB_CONNECTION=mysql`
- `DB_DATABASE=snipeit`
- ran `docker compose exec app php artisan db:seed --force` to restore the demo baseline.
- post-seed verification:
- `users=21`
- `assets=10`
- `settings=1`
- `test_runs=10`
- `models=10`
- `statuslabels=9`
- enlarged the hardware detail `Test uitvoeren` action with a dedicated scoped class so it is roughly twice as tall, uses larger type, and presents as a lighter blue CTA for easier discovery during operator testing.
- replaced the inherited `btn-social` alignment on that CTA with a centered flex layout so the icon and label now sit centered inside the taller button instead of using the old left-column icon treatment.
- adjusted that CTA again so it keeps the standard small social-button icon column width (`28px`) and left-aligned label flow, while still remaining taller and more prominent than the surrounding buttons.
- moved the `Test uitvoeren` action above `Asset bewerken` in the hardware detail action stack so the testing CTA is now the first primary action users see.
- broadened the shared breadcrumb/title mobile header fix so the same `content-header` / `pagetitle` treatment now applies through `<=991px`, not just `<=767px`, to address the missing top spacing seen in the intermediate viewport band on hardware detail and similar pages.
- verification:
- `docker compose exec app php artisan view:cache` (pass)
- `docker compose exec app php artisan test tests/Feature/Assets/Ui/ShowAssetTest.php --env=testing --filter=testDetailPageShowsRunTestButtonLinkingToTestsTab` (blocked by the existing sqlite testing DB corruption: `database disk image is malformed`)
- Test type slug workflow follow-up:
- kept the slug field visible for admins on the Test Types create/edit modals, but changed the default mode to auto-generated from the current name with the input disabled until `Manual slug override` is checked.
- moved slug generation into the request normalization path so create/update now always validate the final normalized slug value instead of generating it after validation.
- standardized all generated or manually overridden slugs to lowercase hyphenated values and added numeric collision suffixes before persistence (`battery-health`, `battery-health-2`, etc.).
- added focused feature coverage for auto-generation from name, duplicate-name suffixing, auto-sync on update when override is off, and sanitized manual override behavior.
- verification:
- `docker compose exec app php -l app/Models/TestType.php` (pass)
- `docker compose exec app php -l app/Http/Requests/TestType/StoreTestTypeRequest.php` (pass)
- `docker compose exec app php -l app/Http/Requests/TestType/UpdateTestTypeRequest.php` (pass)
- `docker compose exec app php -l app/Http/Controllers/Admin/TestTypeController.php` (pass)
- `docker compose exec app php -l tests/Feature/Settings/ManageTestTypesTest.php` (pass)
- `docker compose exec app php artisan view:cache` (pass)
- `docker compose exec app php artisan test tests/Feature/Settings/ManageTestTypesTest.php --env=testing` (blocked by the known sqlite corruption: `database disk image is malformed`)
- Attribute definition create-key workflow follow-up:
- added shared key helpers in `AttributeDefinition` so create paths now normalize to snake_case and auto-resolve collisions with numeric suffixes (`key`, `key_2`, `key_3`) against active (non-deprecated) records.
- moved attribute key generation into `AttributeDefinitionRequest::prepareForValidation()` for create requests using a new `manual_key_override` flag:
- default behavior derives key from `label`.
- manual override derives key from the submitted key field, falling back to label if blank.
- kept update semantics unchanged so key immutability remains enforced once an attribute exists.
- updated the attribute create form UX in `resources/views/attributes/edit.blade.php`:
- `key` input is now disabled by default on create.
- added `Manual key override` toggle.
- added live key normalization from label/manual input to the allowed snake_case format.
- kept edit/version flows read-only for key as before.
- added focused lifecycle test coverage for:
- create auto-generation from label.
- collision suffixing on auto-generated keys.
- sanitized manual override with collision suffixing.
- explicit key-immutability rejection on update.
- verification:
- `docker compose exec app php -l app/Models/AttributeDefinition.php` (pass)
- `docker compose exec app php -l app/Http/Requests/AttributeDefinitionRequest.php` (pass)
- `docker compose exec app php -l resources/views/attributes/edit.blade.php` (pass)
- `docker compose exec app php -l tests/Feature/AttributeDefinitionLifecycleTest.php` (pass)
- `docker compose exec app php artisan view:cache` (pass)
- `docker compose exec app php artisan test tests/Feature/AttributeDefinitionLifecycleTest.php --env=testing` (blocked by existing sqlite corruption: `database disk image is malformed`)
- Attribute enum-options ordering follow-up:
- replaced manual numeric sort fields in the attribute version options table with drag-and-drop ordering handles.
- each option row now stores `options[new][*][sort_order]` as a hidden field that is auto-updated from current row order (`0..n`) after add/remove/reorder actions.
- removed the add-panel `Sort order` number input; admins now only enter value/label/active and reorder directly in the list.
- added a save confirmation guard for typed-but-unadded option drafts so saving a version prompts users before discarding draft `new_option_value/new_option_label` input.
- localized this warning via `attributes.unsaved_option_confirm` in `resources/lang/en-US/attributes.php` and `resources/lang/nl-NL/attributes.php`.
- added lifecycle assertions for drag-handle rendering and sequential sort persistence when sort values are omitted from payloads.
- verification:
- `docker compose exec app php -l resources/views/attributes/partials/options.blade.php` (pass)
- `docker compose exec app php -l tests/Feature/AttributeDefinitionLifecycleTest.php` (pass)
- `docker compose exec app php artisan view:cache` (pass)
- `docker compose exec app php artisan test tests/Feature/AttributeDefinitionLifecycleTest.php --env=testing` (blocked by existing sqlite corruption: `database disk image is malformed`)
- Model create/edit form follow-up:
- hid `Minimum Quantity`, `EOL`, and `Requestable` controls on the model form for both create and edit flows, and marked them as deprecated UI inputs for future removal.
- updated model controller handling so these deprecated fields are only written when explicitly present in request payloads; hidden-form edits keep existing legacy values intact.
- added focused UI coverage in `CreateAssetModelsTest` and `UpdateAssetModelsTest` to assert model create/edit pages no longer expose `id="min_amt"`, `id="eol"`, or `id="requestable"`.
- added focused update coverage in `UpdateAssetModelsTest` to assert omitted deprecated fields do not overwrite existing stored values.
- verification:
- `docker compose exec app php -l resources/views/models/edit.blade.php` (pass)
- `docker compose exec app php -l tests/Feature/AssetModels/Ui/CreateAssetModelsTest.php` (pass)
- `docker compose exec app php artisan view:cache` (pass)
- `docker compose exec app php artisan test tests/Feature/AssetModels/Ui/CreateAssetModelsTest.php --env=testing` (blocked by known sqlite corruption: `database disk image is malformed`)
- shared layout spacing follow-up:
- traced the intermediate-width breadcrumb/title collision to fixed-layout offset drift: navbar height can exceed hardcoded offsets when top-nav content wraps.
- confirmed `.main-header { max-height: 150px; }` was also capping header growth at those widths, letting wrapped nav rows overflow into content.
- replaced the static fixed offset guess with `--fixed-header-offset` in `resources/views/layouts/default.blade.php`, and added runtime sync that measures `.main-header` and updates the offset for fixed layouts.
- removed the `<=991px` header height cap so the top nav can grow naturally instead of colliding with the content-header block.
- retained existing `<=991px` breadcrumb/title wrapping rules while removing fixed-wrapper magic numbers.
- verification:
- `docker compose exec app php artisan view:cache` (pass)
- model number create/edit follow-up:
- added serial-style code case controls (`Aa` override toggle + hidden override flag) to model number create/edit forms.
- normalized model number code server-side in `ModelNumberController` so code is uppercased by default unless `code_case_override` is set.
- removed the create/edit form checkbox for making a model number the default selection for new assets.
- verified selector behavior: model numbers are returned individually in `api.models.selectlist` (`model_id:model_number_id` id + explicit `model_number_id` meta), but primary model number fallback is still used in some model-only compatibility paths.
- added focused assertions in `ModelNumberManagementTest` for default uppercase normalization, override case preservation, and create-form markup changes.
- verification:
- `docker compose exec app php -l app/Http/Controllers/Admin/ModelNumberController.php` (pass)
- `docker compose exec app php -l tests/Feature/Models/ModelNumberManagementTest.php` (pass)
- `docker compose exec app php artisan view:cache` (pass)
- `docker compose exec app php artisan test tests/Feature/Models/ModelNumberManagementTest.php --env=testing` (blocked by existing sqlite testing DB corruption: `database disk image is malformed`)
- model number breadcrumb follow-up:
- added breadcrumbs for `models.numbers.edit` and `models.numbers.spec.edit` in `BreadcrumbsServiceProvider`, both parented from `models.show` so model context remains visible.
- confirmed this enables breadcrumbs on model-number edit and model-number specification edit pages without view-template restructuring.
- added focused feature assertions in `ModelNumberManagementTest` for breadcrumb text on both routes.
- verification:
- `docker compose exec app php -l app/Providers/BreadcrumbsServiceProvider.php` (pass)
- `docker compose exec app php -l tests/Feature/Models/ModelNumberManagementTest.php` (pass)
- `docker compose exec app php artisan view:cache` (pass)
- `docker compose exec app php artisan test tests/Feature/Models/ModelNumberManagementTest.php --env=testing --filter="breadcrumb"` (blocked by existing sqlite testing DB corruption: `database disk image is malformed`)
- hardware create/edit floating save follow-up:
- implemented a mobile-only floating save CTA on `resources/views/hardware/edit.blade.php` (for both create and edit pages since they share the same view).
- wired the CTA as a normal form submit button and added mobile bottom padding to keep lower form controls reachable above the fixed button.
- added focused assertions in `tests/Feature/Assets/Ui/EditAssetTest.php` that both create and edit pages render the floating save markers.
- verification:
- `docker compose exec app php -l resources/views/hardware/edit.blade.php` (pass)
- `docker compose exec app php -l tests/Feature/Assets/Ui/EditAssetTest.php` (pass)
- `docker compose exec app php artisan view:cache` (pass)
- `docker compose exec app php artisan test tests/Feature/Assets/Ui/EditAssetTest.php --env=testing --filter="testCreateAndEditPagesRenderMobileFloatingSaveButton"` (blocked by existing sqlite testing DB corruption: `database disk image is malformed`)
- hardware edit save crash follow-up:
- fixed an update-path type mismatch in `app/Http/Controllers/Assets/AssetsController.php` where `serials` array payloads were assigned directly to `$asset->serial` before extracting the first element.
- normalized incoming serial payload to scalar/null before assignment (`serials[1]` when array), preventing `Asset::normalizeIdentifier(?string ...)` from receiving an array.
- added focused regression coverage in `tests/Feature/Assets/Ui/EditAssetTest.php` for form-shaped `serials[1]` payloads.
- verification:
- `docker compose exec app php -l app/Http/Controllers/Assets/AssetsController.php` (pass)
- `docker compose exec app php -l tests/Feature/Assets/Ui/EditAssetTest.php` (pass)
- `docker compose exec app php artisan view:cache` (pass)
- `docker compose exec app php artisan test tests/Feature/Assets/Ui/EditAssetTest.php --env=testing --filter="testEditAcceptsSerialArrayInputFromFormShape"` (blocked by existing sqlite testing DB corruption: `database disk image is malformed`)
- hardware page runtime regression follow-up:
- traced `htmlspecialchars(): Argument #1 ($string) must be of type string, array given` on `/hardware` to `__('Attributes')` resolving to the newly added `resources/lang/*/attributes.php` group array.
- moved the unsaved enum-option warning copy to a non-conflicting group key (`attribute_definitions.unsaved_option_confirm`) and removed the conflicting top-level `attributes.php` translation files.
- verification:
- `docker compose exec app php artisan tinker --execute "dump(gettype(__('Attributes'))); dump(__('Attributes'));"` (pass: now `string`, `Attributes`)
- `docker compose exec app php artisan view:clear` (pass)
- `docker compose exec app php artisan view:cache` (pass)
- `docker compose exec app php artisan test tests/Feature/AttributeDefinitionLifecycleTest.php --env=testing` (blocked by existing sqlite testing DB corruption: `database disk image is malformed`)

## Active Testing Mobile Guard (2026-04-09)
- Reversed the temporary desktop-only restriction: `Start New Run` remains visible on all widths (including mobile) in both active and index test views.
- Normalized stable test markers to width-agnostic names: `tests-empty-start-run-form`, `tests-start-new-run-form`, and `tests-index-start-run-form`.
- Updated `tests/Feature/Tests/ActiveTestViewTest.php` and `tests/Feature/Assets/Ui/ShowAssetTest.php` assertions to match the width-agnostic markers.
- Verification:
- `docker compose exec app php -l resources/views/tests/active.blade.php` (pass)
- `docker compose exec app php -l resources/views/tests/index.blade.php` (pass)
- `docker compose exec app php -l tests/Feature/Tests/ActiveTestViewTest.php` (pass)
- `docker compose exec app php -l tests/Feature/Assets/Ui/ShowAssetTest.php` (pass)
- `docker compose exec app php artisan test tests/Feature/Tests/ActiveTestViewTest.php tests/Feature/Assets/Ui/ShowAssetTest.php --env=testing` (blocked by existing sqlite testing DB corruption: `database disk image is malformed`)

## Tests Index Row + Details Redesign (2026-04-09)
- Reworked `resources/views/tests/index.blade.php` so each test run is presented as a clear row header with:
- run identity/date/user
- pass/fail(/nvt) summary
- inline row actions (`Edit`, `Delete`)
- Added expandable details per row (collapsed by default) containing result statuses, notes, and photo thumbnails.
- Added chevron toggle affordance and row-header click delegation so users can open details by clicking the row or the dropdown icon.
- Kept existing permission gates, routes, and photo viewer modal behavior unchanged.
- Updated `tests/Feature/Assets/Ui/ShowAssetTest.php` to assert new run-row/toggle/details/action markers.
- Verification:
- `docker compose exec app php -l resources/views/tests/index.blade.php` (pass)
- `docker compose exec app php -l tests/Feature/Assets/Ui/ShowAssetTest.php` (pass)
- `docker compose exec app php artisan optimize:clear` (pass)
- `docker compose exec app php artisan test tests/Feature/Assets/Ui/ShowAssetTest.php --env=testing --filter=testTestsIndexUsesStructuredResultRows` (blocked by existing sqlite testing DB corruption: `database disk image is malformed`)

## Hardware Tests Tab Parity (2026-04-09)
- Applied the same row/dropdown redesign to the hardware detail tests tab in `resources/views/hardware/view.blade.php`.
- Run rows now expose inline `Edit`/`Delete` actions in the header and collapse/expand details under each run.
- Added row-header click delegation (excluding action-button clicks) for easier expand/collapse behavior.
- Added a new hardware tests-tab structure marker assertion in `tests/Feature/Assets/Ui/ShowAssetTest.php` (`data-testid="hardware-tests-run-list"`).
- Verification:
- `docker compose exec app php -l resources/views/hardware/view.blade.php` (pass)
- `docker compose exec app php -l tests/Feature/Assets/Ui/ShowAssetTest.php` (pass)
- `docker compose exec app php artisan view:clear` (pass)
- `docker compose exec app php artisan optimize:clear` (pass)
- `docker compose exec app php artisan test tests/Feature/Assets/Ui/ShowAssetTest.php --env=testing --filter=testDetailPageTestsTabUsesSingleColumnRunList` (blocked by existing sqlite testing DB corruption: `database disk image is malformed`)

## Tests Index One-Line Mobile Row Tuning (2026-04-09)
- Updated `resources/views/tests/index.blade.php` so small-width run rows stay compact/one-line high instead of wrapping into two stacked row blocks.
- Removed the forced small-screen header wrapping rules and switched the run identity/date/user text to a single truncating primary line.
- Verification:
- `docker compose exec app php -l resources/views/tests/index.blade.php` (pass)
- `docker compose exec app php artisan view:clear` (pass)

## Hardware Tests Tab One-Line Row Tuning (2026-04-09)
- Updated `resources/views/hardware/view.blade.php` so run headers in `/hardware/{id}#tests` remain a single compact row on small widths.
- Removed mobile wrap behavior that pushed summary/actions onto multiple lines; compressed mobile action button spacing and switched run identity text to a single truncating line.
- Verification:
- `docker compose exec app php -l resources/views/hardware/view.blade.php` (pass)
- `docker compose exec app php artisan view:clear` (pass)

## Tests Active Mobile Card Layout Tuning (2026-04-09)
- Updated `resources/views/tests/active.blade.php` mobile styles so the Note and Photo CTA buttons stay side-by-side instead of stacking vertically.
- Reduced mobile card body spacing/padding slightly to make cards flatter under the title area.
- Verification:
- `docker compose exec app php -l resources/views/tests/active.blade.php` (pass)
- `docker compose exec app php artisan view:clear` (pass)

## Asset Create Redirect Cleanup (2026-04-09)
- Removed redirect destination options on the asset create form in `resources/views/hardware/edit.blade.php` (asset edit still keeps redirect selector options).
- This removes the broken/clipping create redirect dropdown and keeps create-save flow defaulting to redirect-to-item (new asset detail page).
- Updated tests:
- `tests/Feature/Assets/Ui/EditAssetTest.php` now asserts no redirect select on create and still present on edit.
- `tests/Feature/Assets/Ui/StoreAssetWithMinimalDataTest.php` now asserts create redirects to `route('hardware.show', $asset)`.
- Verification:
- `docker compose exec app php -l resources/views/hardware/edit.blade.php` (pass)
- `docker compose exec app php -l tests/Feature/Assets/Ui/EditAssetTest.php` (pass)
- `docker compose exec app php -l tests/Feature/Assets/Ui/StoreAssetWithMinimalDataTest.php` (pass)
- `docker compose exec app php artisan optimize:clear` (pass)
- `docker compose exec app php artisan test tests/Feature/Assets/Ui/StoreAssetWithMinimalDataTest.php tests/Feature/Assets/Ui/EditAssetTest.php --env=testing --filter="asset_can_be_created_with_minimal_data|testCreateAndEditPagesRenderMobileFloatingSaveButton"` (blocked by existing sqlite testing DB corruption: `database disk image is malformed`)

## Asset Name Field On Create (2026-04-09)
- Investigated report that asset name field reappeared on hardware create page.
- Confirmed root source was explicit include in `resources/views/hardware/edit.blade.php` and not related to redirect dropdown changes.
- Scoped `name` field rendering to edit only (`$item->id`) so create page no longer shows asset-name input.
- Updated `tests/Feature/Assets/Ui/EditAssetTest.php` to assert create page omits `name="name"` while edit page still includes it.
- Verification:
- `docker compose exec app php -l resources/views/hardware/edit.blade.php` (pass)
- `docker compose exec app php -l tests/Feature/Assets/Ui/EditAssetTest.php` (pass)
- `docker compose exec app php artisan view:clear` (pass)
- `docker compose exec app php artisan optimize:clear` (pass)
- `docker compose exec app php artisan test tests/Feature/Assets/Ui/EditAssetTest.php --env=testing --filter=testCreateAndEditPagesRenderMobileFloatingSaveButton` (blocked by existing sqlite testing DB corruption: `database disk image is malformed`)

## Legacy QR Notification Cleanup (2026-04-09)
- Removed the old blue `Print QR` notification dropdown from `resources/views/notifications.blade.php` (session-based `qr_pdf`/`qr_png` UI).
- Removed `qr_pdf`/`qr_png` flash payload writes in `app/Http/Controllers/Assets/AssetsController.php` during single-asset create success path.
- Kept the supported QR actions in the hardware detail QR widget (`download_qr_label` / `Print to LabelWriter`) unchanged.
- Updated `tests/Feature/Assets/Ui/StoreAssetWithMinimalDataTest.php` to assert create flow no longer sets `qr_pdf` or `qr_png` in session.
- Verification:
- `docker compose exec app php -l app/Http/Controllers/Assets/AssetsController.php` (pass)
- `docker compose exec app php -l tests/Feature/Assets/Ui/StoreAssetWithMinimalDataTest.php` (pass)
- `docker compose exec app php artisan view:clear` (pass)
- `docker compose exec app php artisan optimize:clear` (pass)
- code search check for `qr_pdf`/`qr_png`/`qr-notification` in `resources/views` + `app` (no hits)
- `docker compose exec app php artisan test tests/Feature/Assets/Ui/StoreAssetWithMinimalDataTest.php --env=testing` (blocked by existing sqlite testing DB corruption: `database disk image is malformed`)
