# 2026-06-11 Session Init

## Startup Context
- Reinitialized on `master` after the production-test follow-up notes were committed and pushed.
- `master` and `origin/master` both point to `cc510d859` (`Document Production Test Followups`).
- Reviewed `AGENTS.md`, recent `PROGRESS.md`, `docs/fork-notes.md`, latest addenda for 2026-06-07 through 2026-06-09, `docs/agents/session-handoff-2026-06-04.md`, and `docs/agents/e2e-rehearsal-2026-06-08.md`.

## Local Workspace State
- Working tree is intentionally not clean before new work begins.
- Pre-existing local-only/dirty state remains: Docker config edits, upload placeholder `.gitignore` line-ending changes, `.env.before-prodclone.2026-04-30`, `.env.prodclone.prodkey`, `docker/nginx.local.conf`, and `prodbak/`.
- Do not commit or revert those local artifacts unless explicitly requested.

## Current Follow-Up Queue
- QR printing is currently unavailable for a moved component. Investigate component action availability after component transfer/reparent/move workflows and restore a print/download QR path for moved components.
- The asset detail Components tab is poor on mobile. Redesign the rows/actions for small screens so component status, hierarchy, and actions remain scannable without horizontal crowding.
- Asset test history entries currently all show the generic label `Testronde`; change history display to show each entry's actual workflow/profile name.

## QR Label Follow-Up
- Implemented tracked-component QR labels through a target-aware `QrLabelService`; assets retain their existing wrapper methods, and component instances now render stable `CMP:<qr_uid>` labels with component tag/name/serial captions.
- Added `QrLabelPrintService` so queue resolution, temporary PDF handling, CUPS `lp` dispatch, and job-id parsing are shared by asset and component print controllers.
- Added component label routes for authenticated PNG download and server-side printer dispatch, and placed the shared QR widget on component detail pages. This gives moved, reparented, tray, stock, and installed components a direct download/print path from `components.show`.
- The shared widget now has a visible label-template selector label, so the only control labeled as printer is the actual printer queue selector.
- Validation evidence: PHP syntax checks passed in the Docker app container for the changed PHP files. After `php artisan optimize:clear` and testing DB preflight (`APP_ENV=testing`, `DB_CONNECTION=sqlite`, `DB_DATABASE=/var/www/html/database/database.sqlite`), `QrLabelServiceTest`, `ComponentLabelTest`, `ShowAssetTest`, and `ShowComponentTest` passed (`36` tests, `228` assertions).

## Component Condition And Model Spec Layout
- Kept the existing verification process language intact after user clarification; the condition update is now modeled separately from verification.
- User-facing and accepted condition choices are now `Unknown`, `Good`, `Poor`, and `Broken`. `Fair` is not offered as a condition.
- Missing or `Unknown` component condition derives `Needs Attention`; `Poor` and `Broken` derive damaged status but display as concrete `Poor`/`Broken` labels instead of a generic `Damaged` badge. `Good` displays without an issue badge.
- Asset-side component flows now expose the condition field instead of silently creating new tracked/manual child components as `Unknown`. The asset Components tab exposes an inline condition selector for tracked components and writes a `condition_updated` event when changed.
- Fixed model specification value-field overflow by constraining the spec builder form controls and their flex parents so long values/options stay within manual attribute and expected-component fields. After the screenshot still showed overflow, added a scoped `.form-group` margin reset to neutralize Bootstrap horizontal-form gutters inside the model spec builder.
- Validation evidence: after `php artisan optimize:clear` and testing DB preflight (`APP_ENV=testing`, `DB_CONNECTION=sqlite`, `DB_DATABASE=/var/www/html/database/database.sqlite`), condition/asset/component UI and domain tests passed (`44` tests, `304` assertions), selling-state/component-warning/API adjacent tests passed (`13` tests, `85` assertions), `ModelSpecificationComponentPreviewTest` and `ModelSpecificationUiTest` passed (`9` tests, `52` assertions), and `php artisan view:cache` passed with compiled views cleared. Browser smoke on `https://dev.inbit/models/1/spec` at the screenshot-sized viewport confirmed the active value control stayed within its parent panel; browser smoke on `https://dev.inbit/hardware/1#components` confirmed the Components tab loads and current DOM does not expose a `Fair` condition option, but the local production-clone DB currently has no attached tracked component rows to visually exercise the inline condition selector without creating test data.

## Hardware Research Scope
- This chat is initialized for researching hardware details of devices before mapping them into model specifications and expected components.
- For each device lookup, prefer manufacturer/service-manual/spec-sheet sources where available, record exact model identifiers, note ambiguous variants, and capture source URLs with the extracted hardware facts.
- Keep uncertain values explicit instead of forcing catalog entries. Use generic fallback components when only partial details are known.

## Hardware Catalog Seed Updates
- Added component-level wireless/network attributes for upcoming device research: `wifi_standard_max`, 2.4/5/6 GHz band flags, `bluetooth_version`, `nfc`, and `cellular_generation_max`.
- Added component-level camera detail attributes: `camera_aperture`, `camera_autofocus`, and `camera_ois`; `camera_megapixels` now uses component-label display to keep multi-camera phone specs readable.
- Added reusable standard-specific wireless definitions (`Wireless - 802.11n`, `Wireless - 802.11ac`, `Wireless - 802.11ax`, `Wireless - 802.11be`) and expanded phone camera component definitions for common main/ultrawide/macro/depth/selfie megapixel combinations.
- Validation evidence: Docker PHP syntax checks passed for touched seeders/test. After `php artisan optimize:clear` and testing DB preflight (`testing|sqlite|/var/www/html/database/database.sqlite`), `DeviceComponentCatalogSeederTest`, `TestTypeForAssetTest`, and `ManageWorkflowProfilesTest` passed.
- Added Samsung Galaxy A51 `SM-A515F/DSN-4GB-128GB` to the production catalog preset path with expected logic-board children, display, battery, cameras, speaker, and microphone. The seeded wireless facts use `802.11ac`, 2.4/5 GHz true, 6 GHz false, Bluetooth 5.0, NFC true, and 4G LTE.
- Reusable phone camera definitions now stay role/megapixel focused; aperture/autofocus/OIS remain available attributes but are not forced onto shared definitions where variants disagree. Validation after this update: Docker PHP syntax checks passed for the changed seeder/test files, and `DeviceComponentCatalogSeederTest` passed (`8` tests, `107` assertions) after testing DB preflight.

## Environment Notes
- Use `https://dev.inbit/` as the local SSL test URL unless the user asks for the localhost override.
- Before Docker PHPUnit, run `php artisan optimize:clear` and verify testing DB isolation.
- Destructive database commands remain forbidden unless explicitly approved in the current user message with a DB preflight.
