# Inactivity Timeout And Serial OCR Plan - 2026-06-16

## Scope

This plan covers two proposed workflow improvements for the refurb environment:

- Automatic logout after a set period of inactivity.
- Browser-camera OCR for reading device serial numbers into asset/component serial fields.

No application code has been changed for either feature yet.

Authentication-card login was discussed as a related scanner use case, but is explicitly out of scope for this implementation slice. If added later, the preferred direction is a QR login card that identifies the user or badge plus a short PIN, not a static QR code that logs the user in by itself.

Follow-up investigation details for all active planning items are captured in `docs/plans/follow-up-investigation-2026-06-16.md`.

## Current System Context

### Sessions And Logout

- Laravel session expiry is already configured through `config/session.php`.
- `.env.example` currently sets `SESSION_LIFETIME=12000`, which is effectively an all-day idle window.
- `EXPIRE_ON_CLOSE=false` is also available.
- The existing logout flow is handled by `App\Http\Controllers\Auth\LoginController::logout()`.
- The web middleware stack already starts sessions globally and applies authenticated route middleware normally.

Reference: Laravel sessions are configured through `config/session.php` and support multiple backing drivers: https://laravel.com/docs/13.x/session

### Camera Scanning

- The current scanner UI lives in `resources/views/scan/index.blade.php`.
- The scanner behavior lives in `resources/js/scan/index.js`.
- The scan bundle is built through `webpack.mix.js` into `public/js/dist/scan.js`.
- `ScanController` currently resolves QR payloads into asset tag lookups or `CMP:{qr_uid}` component detail redirects.
- `SecurityHeaders` already allows same-origin camera access with `camera 'self'`.
- The scan page already stops camera streams when the page is hidden or unloaded.

### Serial Fields

- Asset serial inputs use `resources/views/partials/forms/edit/serial.blade.php`.
- Asset create/edit already uppercases serials unless the user enables the case-preserve toggle.
- Asset serial duplicate checks already exist through `/api/v1/hardware/serial-check`.
- The OCR result should feed the existing serial input instead of bypassing current normalization, duplicate warnings, or submit validation.

## Decision Notes

### Inactivity Timeout

- Preferred idle timeout: 30 minutes.
- Server-side session expiry remains authoritative.
- A client-side warning window should be added for usability, but it should not be the security boundary.
- Do not disable or alter background AJAX/polling behavior without reporting it first and getting approval.
- Any "stay signed in" action should be explicit user activity, not a silent recurring heartbeat.

### Serial OCR

- OCR is required because many laptops do not have usable barcode/QR labels for serial numbers.
- Barcode decoding can remain available when a serial barcode exists, but OCR should be the primary expected path for laptop serial labels.
- The camera block should be made modular enough to support QR scans and serial OCR without duplicating the camera/device/torch lifecycle.
- When a serial read is accepted, the camera stream should stop and the OCR UI should close/remove itself.

## Inactivity Timeout Implementation Plan

### Phase 1: Configuration

Set environment configuration:

```dotenv
SESSION_LIFETIME=30
EXPIRE_ON_CLOSE=false
```

Operational notes:

- Clear Laravel config cache after changing production or local env values.
- Confirm this does not disrupt long-running active test pages where users are still interacting.
- Confirm API/passport behavior is acceptable, because browser session expiry and API token expiry are separate concerns.

### Phase 2: Polling And AJAX Audit

Before adding client-side warning logic, audit frontend requests that might refresh the session.

Initial source scan on 2026-06-16 found:

- No obvious global long-running `setInterval` except short-lived confetti animation.
- Existing AJAX/fetch usage appears mostly user-triggered or debounced, including menu preference saves, serial duplicate checks, QR label printing, active workflow result saves, settings reorder saves, and search/debounce flows.
- The scan page uses timers for hints and redirect delay, not a session heartbeat.

Implementation rule:

- If later review finds real background polling or keepalive behavior, report it before changing it.
- Do not silently turn off polling. Decide per caller whether it should count as activity, continue without touching session state, or be left unchanged.

### Phase 3: Client-Side Warning

Add a small global timeout helper, preferably as a separate bundled script included by the authenticated default layout.

Recommended behavior:

- Track real user activity events such as `pointerdown`, `keydown`, `touchstart`, and meaningful form/input events.
- Do not treat passive AJAX completion as user activity.
- Show a modal warning around 28 or 29 minutes.
- Provide:
  - `Stay signed in`
  - `Log out now`
- `Stay signed in` should call a small authenticated keepalive endpoint only when clicked.
- `Log out now` should submit the existing logout POST flow with CSRF.
- If the user ignores the warning until the idle deadline, redirect or submit logout.

Suggested configurable values:

```dotenv
SESSION_IDLE_WARNING_SECONDS=60
SESSION_IDLE_CLIENT_WARNING=true
```

Use config defaults so the feature is easy to disable if it conflicts with kiosk-style or testing devices.

### Phase 4: Tests

Suggested coverage:

- Server config test proving the generated client config uses the configured session lifetime and warning window.
- Feature test for keepalive endpoint requiring auth.
- Browser/manual smoke:
  - warning appears before expiry
  - `Stay signed in` keeps the session alive
  - ignored warning logs out
  - passive AJAX does not reset the client timer

## Serial OCR Implementation Plan

### Phase 1: Scanner Modularization

Split the current scanner responsibilities so QR and OCR can share camera plumbing.

Recommended modules:

| Module | Responsibility |
| --- | --- |
| `camera-session` | Own `getUserMedia`, device enumeration, camera selection, torch, lifecycle stop/start, visibility cleanup. |
| `qr-scanner` | Use ZXing against the active video stream and resolve QR/asset/component payloads. |
| `serial-ocr-scanner` | Capture still frames from the active video stream, preprocess, OCR, extract candidates, and return confirmed text. |
| `scanner-ui` | Shared controls, status messages, camera picker, and cleanup hooks. |

Keep the existing scan page behavior working while extracting the reusable camera pieces.

### Phase 2: Serial Field Integration

Add a `Scan serial` control next to serial inputs where it is useful:

- Asset create/edit serial field.
- Dynamically added asset serial rows.
- Component manual serial fields where tracked component serials are entered.

Expected user flow:

1. User taps `Scan serial`.
2. Modal opens with camera preview.
3. User points camera at the serial label and taps `Read serial`.
4. OCR runs on a captured still.
5. UI shows one or more candidate serial values.
6. User chooses or edits a candidate.
7. Confirmed value fills the existing serial input.
8. Existing uppercase/case-preserve and duplicate-check logic runs.
9. Camera stream stops and the modal closes.

Do not auto-save the asset/component from OCR.

### Phase 3: OCR Engine

Use browser-local OCR so serial images do not leave the browser.

Recommended library:

- `tesseract.js`, bundled through npm and Laravel Mix, loaded only for the serial OCR path.
- Current package metadata reports Apache-2.0 licensing.
- Official docs state that Tesseract.js can run in the browser or Node.js and recommends reusing a worker for multiple images before terminating it.

References:

- https://github.com/naptha/tesseract.js/
- https://tesseract.projectnaptha.com/

Implementation details:

- Do not use CDN assets for OCR.
- Lazy-load the OCR bundle or isolate it from the main app bundle so normal pages do not pay the OCR cost.
- Reuse a worker while the modal is open, then terminate it when the scan completes or the modal closes.
- Restrict OCR to English/alphanumeric recognition where possible.
- Consider Tesseract character whitelist parameters for serial-like text: `A-Z`, `0-9`, hyphen, slash, underscore.

### Phase 4: Image Preprocessing And Candidate Extraction

OCR accuracy depends heavily on image quality. Use a still capture rather than continuous OCR.

Preprocessing targets:

- Crop to a centered guide rectangle.
- Convert to grayscale.
- Increase contrast.
- Try thresholding/binarization.
- Optionally run OCR on two variants: original crop and high-contrast crop.

Candidate extraction:

- Normalize whitespace.
- Remove obvious OCR separators when appropriate.
- Prefer uppercase alphanumeric runs with serial-like length.
- Keep hyphen/slash only when likely part of the serial.
- Present multiple candidates instead of guessing when confidence is low.

Suggested candidate rules:

- Minimum length: 5 characters.
- Prefer strings containing both letters and numbers.
- Penalize generic label words such as `SERIAL`, `S/N`, `SN`, `PRODUCT`, `MODEL`, `TYPE`, and `WARRANTY`.
- Show nearby raw OCR text for manual correction.

### Phase 5: Tests And Manual Verification

Suggested automated coverage:

- Unit tests for serial candidate extraction from OCR text.
- JS tests if the project test setup supports them, otherwise keep extraction logic small enough to test via a PHP-backed fixture parser or documented manual cases.
- Feature tests for any OCR helper endpoint only if a server endpoint is introduced. Browser-local OCR should not need one.

Manual verification:

- Asset create serial input.
- Asset edit serial input.
- Additional dynamically added serial row.
- Component manual serial field.
- Mobile browser camera permission flow over `https://dev.inbit`.
- OCR cancel path stops the camera.
- Successful OCR confirm stops the camera.
- Case-preserve toggle still works.
- Duplicate serial warning still fires after OCR fill.

## Risks And Tradeoffs

### Auto Logout

- A strict 30-minute lifetime is simple and secure, but users may lose unsaved form work if warning behavior is not clear.
- Client-side warning can drift from server session state if browser tabs sleep. Server expiry must remain the source of truth.
- Silent AJAX can accidentally extend sessions if it writes to the session. This must be audited before implementation.

### Serial OCR

- OCR accuracy will vary by label wear, camera focus, glare, angle, and font.
- Browser-side OCR can be CPU and memory heavy on older phones or laptops.
- A modular camera layer reduces duplication but increases the first implementation size.
- Reusing the QR page directly for OCR would be faster short-term, but a shared camera module is cleaner because serial OCR needs a modal/field-return flow instead of redirect-on-scan.

## Recommended First Implementation Slice

1. Create the inactivity timeout config and warning design behind env toggles.
2. Add the authenticated keepalive endpoint and client warning modal.
3. Refactor scanner camera lifecycle into a reusable module without changing QR behavior.
4. Add `Scan serial` to one asset serial input.
5. Add browser-local OCR with candidate confirmation.
6. Expand `Scan serial` to dynamic asset rows and component serial fields after the first field proves usable.

## Deferred Follow-Up: Login Cards

Easier login via printed scan cards is useful for the workshop environment but should be handled as a separate feature after inactivity timeout and serial OCR.

Preferred later design:

- Printed QR identifies a user login card or badge token.
- QR alone does not log the user in.
- User scans the card on the login page, then enters a short PIN.
- Store only a hash of the badge token.
- Allow admins to revoke/reissue a card without changing the user's main password.
- Rate-limit scan-card login attempts separately from normal username/password login.
- Regenerate the session after successful card/PIN login.

Avoid:

- Static printed QR codes that directly authenticate.
- QR codes containing a password, API token, or reusable magic login URL.
- Shared workshop accounts if per-user audit history matters.

This deferred feature can reuse the modular camera layer proposed for QR scanning and serial OCR, but it should use an unauthenticated scanner route with stricter security controls.

## Deferred Follow-Up: Asset Tests Workflow Start UX

On the asset Tests / Workflows tab, the `Start new workflow` control currently moves/focuses the user toward the start-new-workflow area instead of directly starting the workflow profile selected in the visible workflow-profile dropdown.

Preferred behavior:

- The visible `Start new workflow` button should submit/start the currently selected workflow profile.
- If a workflow profile is selected, no extra jump/scroll step should be required.
- If no valid workflow profile is selected, keep the user near the selector and show the existing validation/error state.
- Preserve the existing `All workflows`/history navigation behavior.

Implementation notes to investigate later:

- Identify whether there are duplicate start controls for desktop/mobile/FAB layouts and ensure they share the selected profile value.
- Confirm the button does not accidentally start a default profile when the selector is stale or disabled.
- Add a feature test proving the selected `workflow_profile_id` is posted when starting from the asset tab control.
- Browser-check the asset Tests tab at desktop and mobile widths.

## Deferred Follow-Up: Dutch Localization Pass

There are still many user-facing labels, buttons, helper texts, warnings, and workflow/catalog strings that need a broad Dutch translation pass.

Translation policy:

- Translate operational UI text where Dutch improves clarity for refurb staff.
- Do not force-translate common IT or role terms when English is more natural in the environment, for example `hardware`, `administrator`, `workflow`, or similar accepted terms.
- Keep product/model/component names literal.
- Keep seeded technical values literal where translation would reduce recognizability, for example `USB-C`, `NVMe`, `RJ-45`, `Wi-Fi`, and serial/model identifiers.
- Prefer consistent Dutch terms for recurring refurb concepts such as status, condition, tray/storage movement, tests, workflows, components, and sale readiness.

Implementation notes to investigate later:

- Search Blade/PHP/JS for hardcoded user-facing strings introduced by the fork.
- Compare `resources/lang/en-*`, `resources/lang/nl-NL`, and fork-specific language keys for missing Dutch coverage.
- Add or reuse translation keys instead of embedding one-off Dutch text directly in views/controllers.
- Check mixed-language screens in the asset detail page, Tests / Workflows pages, component lifecycle pages, model-number/spec pages, scanner pages, and settings screens.
- Include browser smoke checks for the main Dutch user journeys after translation changes.

## Deferred Follow-Up: Quick Search Tags And Synonyms

Quick-search dropdowns should eventually support tags/synonyms so users can find related catalog items even when they do not know the exact stored label.

Example goals:

- Searching `wifi` should also find `Wireless` component definitions.
- Searching `connections`, `connectors`, or Dutch equivalents should surface port-related items such as USB, HDMI, DisplayPort, RJ-45, and audio ports.
- Searching broader concepts should match related model-spec attributes and component definitions without changing the canonical labels.

Recommended direction:

- Add structured searchable aliases/tags to catalog objects instead of hardcoding string matches in each dropdown.
- Start with component definitions and attribute definitions, because those are the current quick-search-heavy screens.
- Keep canonical labels unchanged and use aliases only for matching.
- Seed common English and Dutch aliases where useful, for example `wifi`, `wi-fi`, `draadloos`, `wireless`, `connector`, `poort`, `aansluiting`, and `verbinding`.
- Consider whether aliases belong in JSON metadata on catalog rows or a small normalized search-alias table.

Implementation notes to investigate later:

- Inventory all quick-search dropdowns and live-search endpoints.
- Decide whether aliases should be admin-editable or seed-only.
- Ensure alias matches do not make result lists noisy; exact label matches should still rank first.
- Add focused tests for alias matching on component-definition and attribute-definition searches.

## Research Block: Concurrent Scanning And Server Load

Investigate the practical server weight of live scanning and scan-adjacent usage in the refurb environment.

Target scenario:

- Around 20 people using the system at the same time.
- Users may be scanning QR/component labels, opening asset pages, editing serials/specs, running workflows, moving components, and using quick searches in different parts of the building.
- Camera preview itself should be browser-local, but each resolved QR, asset lookup, serial duplicate check, workflow save, and search request can still hit the server.

Research goals:

- Identify which scanning-related actions generate server requests.
- Measure request volume and latency under realistic concurrent use.
- Identify expensive queries or repeated data loads on scan, asset detail, component roster, workflow start, and quick-search paths.
- Decide whether caching, eager-loading, result trimming, rate limiting, or client-side debounce changes are worthwhile.
- Avoid optimizing blindly before measuring.

Initial hypotheses to verify:

- QR camera decoding is client-side and should not load the server until a code is resolved.
- Serial OCR should be browser-local and should not send images to the server.
- Asset detail pages may be heavier than the scan itself because they load specs, components, images, workflow history, and action state.
- Quick-search endpoints and serial duplicate checks may need debounce/rate-limit review if many users type/search continuously.

Possible optimization areas:

- Cache stable catalog data used by dropdowns and quick searches.
- Cache or memoize asset component rosters where safe, with invalidation on component moves/condition changes.
- Add stricter debounce to search fields that currently fire too aggressively.
- Trim scan resolve responses/redirect paths so they do minimal work before sending the user to the target page.
- Review indexes for asset tag, serial, component QR UID, workflow run lookups, and component roster queries.
- Use browser-side OCR/barcode processing so camera frames never hit the server.

Suggested evidence to collect:

- Web server access logs around active testing sessions.
- Laravel request timings for scan resolve, asset detail, component detail, serial-check, workflow save, and quick-search routes.
- Database query counts and slow-query logs for those routes.
- Browser network traces from a realistic scan-to-workflow flow.
- Optional local load test script that simulates 20 users hitting common read/write paths without destructive DB actions.

Safety constraints:

- Do not run destructive database commands for this investigation.
- Do not run load tests against production without explicit approval and a narrow test window.
- Do not disable existing AJAX/polling behavior without first reporting findings and getting approval.

## Research Block: Photo Normalization And Optimization

Investigate image upload/storage behavior and define a safe normalization pipeline for photos.

Problem statement:

- Current photo uploads can store raw camera images directly.
- Modern phones may upload very large images, including high-resolution/8K-class photos.
- Most in-app usage does not need full raw resolution for thumbnails, asset views, test photos, or workflow review.
- Large originals increase storage use, page weight, backup size, and image processing cost.

Research goals:

- Inventory all photo/image upload paths used by the fork.
- Identify where originals are needed versus where normalized derivatives are enough.
- Define target sizes, formats, quality settings, and thumbnail variants.
- Preserve enough detail for repair/refurb evidence without overloading storage.
- Avoid breaking existing asset images, promoted test photos, QR labels, model images, and uploaded file behavior.

Upload paths to inspect:

- Asset image uploads.
- Model-number default images.
- Test/workflow result photos.
- Promoted test result photos to asset images.
- Component or catalog images if present.
- Generic uploaded files where image normalization may not be appropriate.

Possible normalization strategy:

- Keep an original only when the upload type explicitly needs archival evidence.
- Generate a normalized display image, for example max 1600-2048 px on the long edge.
- Generate thumbnails, for example 300-600 px depending on list/card usage.
- Strip unnecessary metadata by default, especially GPS/EXIF, unless there is a defined reason to keep it.
- Use JPEG/WebP quality settings appropriate for device photos.
- Preserve PNG only where transparency or exact graphics matter.
- Make the process idempotent so existing images can be backfilled safely.

Implementation questions to investigate:

- Which image library is currently used for uploads and resizing; `intervention/image` is already present in Composer.
- Whether normalization should happen synchronously during upload or asynchronously through a queue/job.
- Whether the database needs derivative path fields or can follow a naming convention.
- How to handle files already in storage without disrupting existing URLs.
- Whether storage disks are local-only in production or may later move to S3.
- Whether uploaded evidence photos have legal/audit requirements for keeping originals.

Suggested evidence to collect:

- Current upload directories and file size distribution.
- Largest image dimensions and byte sizes in production/dev storage.
- Which views request full-size images versus thumbnails.
- Page weight impact for asset detail, workflow history, and photo viewer pages.
- Backup size contribution from uploaded images.

Safety constraints:

- Do not delete or overwrite existing original images during research.
- Do not run batch compression/backfill without an explicit backup and approval.
- Do not strip metadata from existing files until retention requirements are agreed.
- Any future optimizer should be tested on copied sample images before touching production uploads.

## Research Block: Printer Configuration And New Server Migration

Investigate how label printing should work after migrating to the new server.

Known context:

- The future production host will be a Docker instance running on a Proxmox server.
- The current way of using printers is expected to change after this move.
- Printer choices should become actual selectable live options.
- The source of those printer options is currently unknown.
- Asset and component QR label printing already use shared server-side print services, so printer discovery/config should be designed once and reused across label targets.

Research goals:

- Identify how printers will be exposed to the Docker application on the Proxmox host.
- Decide whether printer options come from CUPS, host-mounted config, database-managed settings, environment variables, a network print service, or another source.
- Ensure the UI lists only real currently usable printer queues.
- Keep asset and component label printing consistent through the shared QR label print path.
- Make printer configuration observable and debuggable for operators.

Possible printer option sources:

- CUPS queues visible inside the app container.
- CUPS queues on the Proxmox host exposed over the network.
- A dedicated print sidecar container.
- Static environment/config entries that map friendly labels to queue names.
- Database-managed printer records with periodic availability checks.

Implementation questions to investigate:

- Will the app container have direct access to CUPS and the `lp` command?
- Will the printer be USB-attached to the Proxmox host, network-attached, or attached to another machine?
- Should printer discovery happen live on every page load, be cached briefly, or be refreshed by an admin action?
- Should unavailable printers be hidden, disabled, or shown with a warning?
- Should label template compatibility be tied to printers/rolls, or should users still select template and printer independently?
- How should failed print jobs be surfaced: immediate error, action-log entry, notification, or admin diagnostics page?

Suggested implementation direction after research:

- Add a `PrinterProvider`/`PrinterQueueService` abstraction rather than calling queue discovery directly from controllers/views.
- Reuse it from the existing QR label print service.
- Return normalized queue objects such as `id`, `name`, `display_name`, `status`, `source`, and optional `last_checked_at`.
- Cache live discovery briefly to avoid slow page loads.
- Keep a manual config fallback for environments where discovery is unavailable.

Safety constraints:

- Do not assume the new Proxmox/Docker server exposes the same printer queues as the current environment.
- Do not hardcode a production queue name into views.
- Do not remove the current printer behavior until the new environment path is confirmed and tested.
- Test printer discovery and actual print dispatch separately.

## Open Questions

- Should the client warning show at 28 minutes with a 2-minute grace, or at 29 minutes with a 1-minute grace?
- Should `SESSION_LIFETIME=30` apply in all environments, or should local development keep a longer value?
- Should serial OCR be available only to users with asset/component create/update permissions, or is the existing page permission enough because it only fills fields?
- Should raw OCR text ever be logged for debugging? Default recommendation is no, because serials are asset identifiers.
