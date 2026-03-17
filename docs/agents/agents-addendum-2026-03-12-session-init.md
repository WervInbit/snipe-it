# Agents Addendum - 2026-03-12 Session Init

## Context
- Reviewed `AGENTS.md`, `PROGRESS.md`, and `docs/fork-notes.md` before starting work.
- This addendum captures session-specific initialization notes and carry-over items for 2026-03-12.

## Files Reviewed (Recent + Relevant)
- `PROGRESS.md`
- `TODO.md`
- `docs/fork-notes.md`
- `docs/agents/agents-addendum-2026-03-05-session-init.md`
- `docs/agents/agents-addendum-2026-03-03-session-init.md`
- `docs/agents/agents-addendum-2026-02-24-session-init.md`
- `docs/agents/agents-addendum-2026-02-19-session-init.md`
- `docs/agents/agents-addendum-2026-02-17-session-init.md`
- `docs/agents/agents-addendum-2026-02-12-session-init.md`
- `docs/agents/agents-addendum-2026-02-10-session-init.md`
- `docs/agents/agents-addendum-2026-02-05-session-init.md`
- `docs/plans/latest-tests-column-lazy-detail.md`

## Open TODOs and Unresolved Items
- `TODO.md`: clean up QR code layout (final sizing/margins/templates) and replace remaining placeholder device catalog MPN/SKU codes.
- `TODO.md`: improve mobile scan feedback and close-range behavior (current scans can stall without clear feedback).
- `TODO.md`: decide and document user naming/email convention with manager.
- `TODO.md`: decide whether battery health percentage should be auto-calculated from max/current capacity and standardize source fields/units.
- Known unresolved test issue from 2026-02-17 and 2026-02-19: `tests/Feature/Assets/Ui/ReadyForSaleWarningTest.php` fails due to missing `warning` session key.
- 2026-02-19 handoff not yet delivered: deploy-safe/idempotent sync for phone attributes and phone test types (`imei_1`, optional `imei_2`, `has_knox`, `knox_tripped`, `charge_port`, `sim_port`, `power_button`, `volume_buttons`, optional `home_button` by capability).
- Follow-up from 2026-02-05: validate QR preview parity against printed output across templates (especially S0929120/narrow label).
- Plan follow-up from `docs/plans/latest-tests-column-lazy-detail.md`: confirm touch-device fallback for latest-tests detail hover (click fallback/popover/modal) is implemented and tested.

## Next Session Focus
- Prioritize implementation of the phone parity handoff and the failing Ready-for-Sale warning test.
- Keep `quality_grade` as a non-test workflow step (`Kwaliteit A-D`) while extending phone checks.
- Log all code, validation, and documentation updates in this file and `PROGRESS.md`.

## Session Updates (2026-03-12)
- Updated icon mapping so scan uses a camera icon and tests/test-types use a clipboard icon.
- Added a tracked decision point in `TODO.md` to determine whether user-facing terminology should remain "tests" or become "tasks" for non-diagnostic refurb steps (for example cleaning or driver installation), while keeping technical model naming unchanged unless a dedicated migration is approved.
- Updated Dutch `general.assets` translation from `Activa` to `Apparaten`.
- Adjusted hardware detail specification table CSS (`resources/views/hardware/view.blade.php`) so mobile keeps label/value side-by-side with fixed table layout and strong wrapping safeguards to avoid page overflow.
- Changed the hardware detail specification block itself to full width so the spec table has more usable width and long values wrap less aggressively.
- Reworked the hardware detail specification rendering to a separator-style list (label above value per item) after mobile table-based attempts produced unreadable wrapping; this now prioritizes vertical readability and available width.
- Finalized spec layout direction for now: restored the section to standard detail-row alignment (`3/9` columns) and enforced per-item vertical stacking (label above value) so values no longer appear beside labels/items.
- Follow-up hardening: renamed specification classes to unique `asset-spec-*` selectors to avoid external/custom CSS collisions that were still forcing horizontal layout, and ran `php artisan optimize:clear` in the app container so the latest Blade/CSS changes are served.
- Switched to the most conservative rendering: each specification attribute is now output as a normal detail `row` with `col-md-3` label and `col-md-9` value (same pattern as surrounding fields), plus per-row separators/override highlights; caches cleared again after the template update.
- Mapped current image flows end-to-end (`assets.image`, `asset_images`, `test_result_photos`, model images, API transformers/routes) to define a default+override implementation that does not break existing consumers.
- Shipped schema for ordered/default/override image behavior:
- new `assets.image_override_enabled`,
- new `asset_images.sort_order`, `asset_images.source`, `asset_images.source_photo_id`,
- new `model_number_images` table for ordered defaults by model number.
- Added migration backfills:
- existing asset image rows get deterministic order,
- assets with an image are flagged as override-enabled,
- existing model image values are seeded into `model_number_images` (primary model number fallback).
- Extended backend behavior:
- `Asset::getImageUrl()` now supports model-number defaults and explicit override selection,
- `Asset::resolvedImagePayload()` provides ordered source-aware image payloads for API consumers.
- Added API endpoints for webshop and image admin flows:
- `GET /api/v1/hardware/{asset}/images` for ordered resolved images,
- `GET/POST/PUT/DELETE /api/v1/model-numbers/{modelNumber}/images` for default-image management.
- Added test-photo promotion to hardware override images:
- new route `POST /hardware/{asset}/tests/{testRun}/results/{result}/photos/{photo}/promote`,
- copies test image into asset storage, creates ordered `asset_images` row with source metadata, and can enable/set override cover.
- Validation snapshot:
- migration applied successfully (`2026_03_12_130000_add_image_override_and_model_number_images`),
- syntax checks (`php -l`) passed for all touched PHP files,
- targeted tests passing when run serially:
- `tests/Feature/Assets/Api/AssetImagesApiTest.php`,
- `tests/Feature/Assets/PromoteTestResultPhotoToAssetImageTest.php`,
- `tests/Unit/AssetTest.php --filter GetImageUrl`.
- Environment note: sqlite-backed test DB at `database/database.sqlite` is shared and can corrupt when tests are executed in parallel; reset + serial reruns were used for reliable verification.
