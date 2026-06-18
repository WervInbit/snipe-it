# Follow-Up Investigation - 2026-06-16

## Scope

This investigation covers the current follow-up plan items before the next implementation pass:

- 30-minute inactivity logout with client warning.
- Browser-camera serial OCR.
- Deferred QR login cards.
- Asset Tests / Workflows start button behavior.
- Dutch localization pass.
- Quick-search tags and synonyms.
- Concurrent scanning/server load.
- Photo normalization and optimization.
- Printer configuration for the future Proxmox/Docker server.

No implementation changes were made as part of this investigation.

## 1. Inactivity Logout

### Current State

- Browser sessions are controlled by `config/session.php`.
- `.env.example` sets `SESSION_LIFETIME=12000`, which is effectively an all-day idle session.
- `EXPIRE_ON_CLOSE=false` is already present.
- Logout is handled by `App\Http\Controllers\Auth\LoginController::logout()`.
- The logout method intentionally accepts GET only for SAML SLO; ordinary logout should still use POST.
- `resources/views/layouts/default.blade.php` already provides CSRF metadata and a logout form pattern that can be reused by client-side timeout code.

### AJAX / Polling Audit

No global heartbeat or permanent background poller was found. Existing frontend requests are mostly user-triggered or short debounce/action flows:

- asset serial duplicate checks while editing hardware;
- active workflow result/photo saves;
- scanner redirect and hint timers;
- QR label print requests;
- component/model definition live-search and reorder actions;
- bootstrap-table requests on page load or table interaction.

This means there is no current blocker to adding a client inactivity timer. The rule should still be that passive AJAX completion does not reset the client timer; only real user activity and the explicit "Stay signed in" action should.

### Recommendation

Implement this directly in the next pass:

- Set the production idle lifetime to `SESSION_LIFETIME=30`.
- Add config keys for warning behavior, for example `SESSION_IDLE_CLIENT_WARNING=true` and `SESSION_IDLE_WARNING_SECONDS=60`.
- Add an authenticated `POST /session/keepalive` endpoint that only refreshes the session when the user clicks "Stay signed in".
- Add a small authenticated-layout script that tracks real interaction events and shows a modal shortly before expiry.
- Use existing logout POST flow for "Log out now" and for the final timeout path.

### Pitfalls

- Sleeping browser tabs can drift from the server session. Server expiry must remain authoritative.
- Long forms can lose unsaved work, so the modal should be visible and direct.
- Do not convert existing AJAX into a silent keepalive.
- Keep SAML logout behavior intact.

### Tests

- Feature test: keepalive requires auth and returns success for an authenticated web session.
- Feature/view test: the timeout config is present in the authenticated layout when enabled.
- Manual/browser: warning appears, stay-signed-in refreshes session, ignored warning logs out, passive AJAX does not reset the timer.

## 2. Serial OCR

### Current State

- QR scanning is implemented by `resources/views/scan/index.blade.php` and `resources/js/scan/index.js`.
- The JS scanner is currently monolithic: camera setup, camera list, torch, ZXing decode, overlay, timers, errors, and redirect all live in one module.
- The app already uses `@zxing/browser` and `@zxing/library`; `tesseract.js` is not installed.
- `SecurityHeaders` already allows same-origin camera access through the current Feature-Policy header.
- CSP uses same-origin scripts/connects, so OCR worker/core assets should be bundled or served locally, not loaded from CDN.
- Asset serial fields already have useful behavior: uppercase normalization unless "Preserve case" is active, duplicate serial checks through `/api/v1/hardware/serial-check`, and an "allow duplicate" override.

### Recommendation

Use browser-local OCR and refactor the scanner first:

- `camera-session`: getUserMedia, device enumeration, selected camera, torch, stop/start, visibility cleanup.
- `qr-scanner`: ZXing decode and redirect-only behavior.
- `serial-ocr-scanner`: capture a still frame, preprocess, run OCR, extract serial candidates.
- `serial-ocr-modal`: field-focused UI that returns the selected/edited candidate to the existing serial input.

Start with the asset create/edit serial field, because filling that input will automatically reuse existing case normalization and duplicate checks. After that works, expand to dynamic extra serial rows and component serial controls.

### OCR Engine Direction

Use `tesseract.js` as the first engine:

- load it only for serial OCR, not the normal scan page;
- keep OCR browser-local so serial images are not uploaded;
- create a worker when the modal opens, reuse it for repeated reads, terminate it on close/success;
- constrain recognition toward serial-like characters where possible;
- present candidate values to the user instead of auto-saving.

Native `BarcodeDetector` is not a replacement for OCR. It is still useful as an optional fallback for serial barcodes, but browser support is limited and laptop serials often have no barcode.

### Pitfalls

- Camera access requires HTTPS or localhost and a user permission grant.
- OCR is CPU/memory heavy, especially on older phones.
- Accuracy depends on focus, glare, crop, font, label wear, and lighting.
- The camera must always stop on cancel, success, page hide, and modal close.
- Do not log raw OCR text by default; it can contain serial identifiers.

### Tests

- Unit test the candidate extractor with realistic OCR strings.
- Browser/manual tests for asset create, asset edit, dynamic serial row, and component serial field.
- Confirm duplicate serial warnings still fire after OCR fills the input.
- Confirm cancel and success both stop camera tracks.

## 3. QR Login Cards

This remains a later feature. The recommended security shape is unchanged:

- printed QR identifies a login card or badge token;
- user also enters a short PIN;
- only a hash of the badge token is stored;
- cards can be revoked/reissued;
- rate limits are separate from normal password login;
- the session is regenerated after successful login.

Avoid QR-only login, reusable magic URLs, printed passwords, API tokens, or shared workshop accounts if user-level audit history matters.

## 4. Asset Tests / Workflows Start Button

### Current State

- The backend `TestRunController::store()` already requires `workflow_profile_id`, verifies it is active and applicable to the asset, creates the run, and redirects to the active workflow page.
- The asset detail Tests tab already has a real form with the selected workflow profile.
- The mobile floating start button currently only scrolls/focuses the form through JS in `resources/views/hardware/view.blade.php`.

### Recommendation

This is a small implementation:

- Change the floating Tests-tab button to submit the existing form with `requestSubmit()` and a `submit()` fallback.
- If the form or selected profile is missing/invalid, keep the current scroll/focus fallback.
- Do not create a second hidden form; one source of selected profile state is safer.

### Tests

- Update the existing `ShowAssetTest` coverage to assert the FAB targets/submits the same form.
- Keep backend `StartNewTestRunTest` coverage as the main behavior proof.
- Browser-check mobile and desktop asset Tests tab.

## 5. Dutch Localization Pass

### Current State

Fork-specific missing Dutch top-level keys found by comparing `en-US` and `nl-NL`:

- `resources/lang/en-US/tests.php`: 12 keys missing in `nl-NL`.
- `resources/lang/en-US/general.php`: 35 keys missing in `nl-NL`.
- `resources/lang/en-US/button.php`: 1 key missing in `nl-NL`.

Examples include QR print/download text, image upload/gallery messages, latest workflow status labels, duplicate/confirmation copy, and test-run completion prompts.

Hardcoded English remains in several fork areas:

- scanner page and scanner JS camera errors;
- component lifecycle/controller success messages;
- QR print widget JS fallback messages;
- serial duplicate/case-preserve controls;
- component definition/spec management labels;
- workflow extra-item helper text.

### Recommendation

Do this as a structured UI pass, not a whole-repo word-for-word translation:

- Add missing Dutch keys first.
- Replace fork-introduced hardcoded strings with translation keys.
- Keep accepted IT terms literal where clearer: hardware, workflow, administrator, USB-C, Wi-Fi, NVMe, HDMI, serial/model identifiers.
- Prioritize screens refurb staff use daily: asset detail, asset create/edit serials, Tests / Workflows, components, scanner, model-number/spec settings, QR labels.

### Tests

- Translation-key smoke: no missing keys for touched fork files.
- `php artisan view:cache`.
- Browser smoke in Dutch on the main user journeys.

## 6. Quick-Search Tags And Synonyms

### Current State

There is no shared alias/tag system today.

Current quick searches are split:

- `ComponentDefinitionSettingsController@index` searches component definition fields and category/manufacturer server-side.
- Component definition subcomponent pickers preload definitions and match a client-side `search_text`.
- Attribute contribution pickers preload attribute definitions and match a client-side `search_text`.
- Model-number expected component editing uses the same component definition catalog but does not have a shared alias source.
- `ComponentDefinition` has `metadata_json`, but no search-specific relation/table.

### Recommendation

If aliases are seed-only and short-lived, `metadata_json.search_aliases` would work. If they should be reusable, admin-editable, ranked, localized, and shared across components/attributes/model pickers, add a normalized table.

Recommended durable approach:

- `search_aliases` table with `searchable_type`, `searchable_id`, `alias`, optional `locale`, `source`, and `weight`.
- `CatalogSearchAliasService` to build normalized search text for component definitions and attribute definitions.
- Exact/name/part-code matches should rank before alias-only matches.
- Seed common aliases: `wifi`, `wi-fi`, `wireless`, `draadloos`, `connector`, `poort`, `aansluiting`, `verbinding`, `usb`, `hdmi`, `displayport`, `rj45`.

### Pitfalls

- Too many broad synonyms will make quick searches noisy.
- Dutch and English aliases should help discovery without changing canonical labels.
- Client-side preloaded pickers and server-side paginated indexes must use the same alias source or results will feel inconsistent.

## 7. Concurrent Scanning And Server Load

### Current State

Camera preview and QR decode happen in the browser. Server load starts when a scan resolves or when users save/edit/search.

Scan resolve itself is light:

- `CMP:{qr_uid}` looks up `component_instances.qr_uid`;
- visible component tags look up `component_instances.component_tag`;
- asset fallback goes to the existing asset-tag flow.

The heavier work is after redirect:

- asset detail loads tests, workflow results/photos/audits, component roster, expected components, resolved attributes, component history, workflow profiles, current user tray items, storage locations, and component definitions;
- component detail loads component hierarchy, events, uploads, removed expected subcomponents, locations, child definitions, and all installable assets;
- active workflow saves can upload photos and audit result changes;
- serial duplicate checks fire during typing.

Index review on the local Docker DB showed the scan-critical fields are indexed: asset QR UID, asset tag, component QR UID, component tag, component current/source/storage/user fields, workflow run/result relations, model-number component templates, and component-event relations.

### Recommendation

Do not cache scan resolve blindly. Measure first.

Add temporary observability for a test window:

- request duration and query count for scan resolve, asset detail, component detail, serial-check, workflow partial update, component quick searches, and asset/component label printing;
- DB slow query logging during a manual production-style test;
- browser network traces for scan-to-workflow and scan-to-component flows.

Likely optimization targets after measuring:

- component detail `installableAssets` loading all assets;
- asset detail component history subquery and broad eager loads;
- stable catalog dropdown data;
- thumbnail/display image loading after photo optimization;
- debounce/rate limits for serial checks and quick searches.

### Safety

Do not run load tests against production without an explicit test window. Local synthetic tests should avoid destructive DB commands.

## 8. Photo Normalization And Optimization

### Current State

The app already has two image behaviors:

- older single-image fields use `ImageUploadRequest`, Intervention Image, orientation handling, and height-limited resize;
- newer fork galleries and workflow photos store uploaded files directly.

Raw/direct paths found:

- `AssetImageController::store()` stores asset gallery images directly under `storage/app/public/assets/{asset_id}` with a 5 MB validation limit;
- `ModelNumberImageSyncService` stores model-number images directly under `storage/app/public/model_numbers/{id}`;
- `TestResultController` stores workflow/test photos directly under `public/uploads/test_images`;
- promoted workflow photos are copied as-is into asset images.

Current DB tables mostly store one `file_path`; there are no first-class original/display/thumbnail fields for asset images, model-number images, or workflow result photos.

### Recommendation

Add a shared image normalization service before changing upload paths:

- generate a display image, around 1600-2048 px on the long edge;
- generate a thumbnail, around 300-600 px depending on UI use;
- strip EXIF/GPS metadata by default for new normalized derivatives;
- keep originals only where evidence/retention policy requires it;
- store dimensions, mime type, byte sizes, and paths where useful.

Prefer explicit derivative columns over only naming conventions if backfill and UI selection are expected:

- `original_path`;
- `display_path`;
- `thumbnail_path`;
- `width`, `height`, `size_bytes`, `mime_type`;
- optional `metadata_json`.

### Pitfalls

- Workflow/test photos may be evidence; decide whether originals are retained before stripping or deleting anything.
- Existing views use `file_path` directly, so migration should be backward-compatible.
- Backfill must be idempotent and should start with copied sample images.
- If storage moves to S3 later, the service should use Laravel disks instead of `public_path()` where possible.

## 9. Printer Configuration And Proxmox Migration

### Current State

- Asset and component QR labels now use the shared `QrLabelPrintService`.
- Queues come from env/config: `LABEL_PRINTER_QUEUE`, `LABEL_PRINTER_QUEUES`, `LABEL_PRINT_COMMAND`, `LABEL_PRINT_OPTIONS`, and optional `CUPS_SERVER`.
- `QrLabelPrintService::printPdf()` shells out to `lp -d {queue}` with a 15-second timeout.
- The QR widget reads `config('qr_templates.queues')` directly to render the printer dropdown.
- There is an existing CUPS setup guide under `docs/agents/cups-setup-guide.md`.

### Recommendation

Do not hardcode the new server printer behavior yet. Add an abstraction that preserves the current env behavior and can later discover live queues:

- `PrinterQueueService` returns normalized queue objects: `id`, `name`, `display_name`, `status`, `source`, `is_available`, optional `last_checked_at`.
- Providers:
  - config/env provider for current behavior;
  - CUPS provider using `lpstat` or a CUPS endpoint when the Proxmox path is known;
  - later DB/manual provider if operators need friendly labels or template compatibility.
- Cache live discovery briefly, for example 30-120 seconds.
- Make asset/component label widgets consume queue objects instead of raw config strings.
- Keep print dispatch and queue discovery separate so a visible queue can still fail with a clear spool error.

### Open Environment Decisions

- Is the Dymo USB-attached to Proxmox, network-attached, or attached to another machine?
- Will CUPS run on the host, in the app container, or in a sidecar?
- Will each roll/side be a separate queue?
- Should template compatibility be tied to queue records?
- How should operators diagnose failed jobs: immediate toast only, app log, admin diagnostics page, or all three?

## Recommended Follow-Up Plan

### Direct Implementation Candidates

1. Asset Tests mobile FAB submits the selected workflow form.
2. Inactivity timeout server config, keepalive endpoint, and warning modal.
3. Dutch missing-key pass for current fork strings.

### Staged Implementation Candidates

1. Scanner camera refactor, then serial OCR for one asset serial input, then expand to more fields.
2. Printer queue service with config provider first, CUPS/live provider after the Proxmox print path is confirmed.
3. Photo normalization service and schema migration after original-retention policy is decided.

### Research / Measurement Before Code

1. Concurrent-use profiling with request timings and query counts.
2. Production or production-clone upload size distribution for photo optimization.
3. Printer attachment/CUPS topology on the new server.

## External References Checked

- Laravel session configuration: https://laravel.com/docs/11.x/session
- Browser camera access requirements: https://developer.mozilla.org/en-US/docs/Web/API/MediaDevices/getUserMedia
- Native BarcodeDetector support status: https://developer.mozilla.org/en-US/docs/Web/API/BarcodeDetector
- Tesseract.js browser OCR worker usage: https://github.com/naptha/tesseract.js/
