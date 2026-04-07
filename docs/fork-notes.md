# Fork Notes

Maintain this log to highlight differences between this fork and upstream Snipe-IT. Add a dated section whenever features change, regressions are fixed, or documentation diverges. Reference pull requests or issues when available.

## Update Log

### 2026-04-02
- Hardware detail page status editing now renders quality grading as its own row instead of bundling it into the same status control block.
- Removed checkout-oriented hardware detail UI for the refurb flow: no checked-out-to side panel, no assigned/deployed rendering inside the status row, and no checkout-date detail line on the asset page.
- Hardware detail delete action no longer uses `Checkin and Delete` wording; the page now consistently shows a plain delete action.
- Hardware edit page no longer exposes the collapsed optional-information section; asset name was moved into the main visible form and notes now appear directly below status.
- Hardware detail QR widget now exposes a single download action for the full rendered label PNG image and no longer shows a `Print PDF` action or a raw-QR download button.
- Hardware detail tests tab icon now uses a clipboard-check symbol instead of a vial, and the history panel heading now has a dedicated translated `status_history` label.
- Hardware detail upload tab no longer uses a special right float, so the paperclip/upload action stays aligned with the rest of the tab list on narrow screens.
- Hardware detail now includes a `Test uitvoeren` shortcut button under the edit action that opens the existing Tests tab in place.
- Hardware detail Tests tab now uses responsive new-run controls: desktop shows the action at the upper left, while phones/tablets get a lower-right floating plus-action button that appears only when the Tests tab is active.
- Hardware detail latest-tests warning is now foldable via the full callout surface and shows a right-side disclosure icon; the mobile tests FAB was also enlarged for thumb reach.
- Hardware detail latest-tests warning now also shows muted helper copy indicating that the block can be unfolded.
- Hardware detail Tests-tab run history now stays in a single full-width column instead of splitting into two columns on desktop.
- Test-run history rows now use a stable label/status/note grid so entries align cleanly under each other on the tests list page.
- On small screens, the shared page title/breadcrumb header no longer keeps its left float, so it can wrap beside the existing floated sidebar hamburger instead of wasting a separate row below the navbar.
- On small screens, the shared content header now preserves a small amount of side padding for the breadcrumb/title block instead of collapsing flush to the edge.

### 2026-03-17
- Added admin UI management for model-number default images on model-number edit screens (upload, caption update, sort-order update, replacement, and delete actions).
- Added web routes/controller flow for model-number image CRUD in the authenticated settings/model management UX.
- Updated model-number image ordering UX to drag-and-drop (with save action) instead of manual numeric order inputs, using a pointer-event handle that supports both mouse and touch interactions.
- Added client-side image previews for model-number image uploads and replacement file selection in admin UI.
- Hardened model-number image ordering integrity: appended uploads now start at sort order `0`, and reorder submissions must include the full image ID set for the model number.
- Reworked model-number image editing into a single-save UX on edit screens: image captions, replacements, reorder state, staged removals, and new uploads now persist with the main model-number save instead of separate image-level save buttons.
- Removed the now-obsolete standalone admin model-number image CRUD/reorder route path from the web UI, keeping the single-save model-number update flow as the only admin write path.
- API model-number image creation now defaults the first created image to `sort_order = 0` when no explicit order is supplied.
- Added explicit destructive-command governance to `AGENTS.md`: shared dev DB destructive actions require in-message approval and preflight context output before execution.

### 2026-03-12
- Added an image-source workflow for hardware with explicit override control: assets now support `image_override_enabled` to switch between model-number defaults and asset-specific override images.
- Added ordered metadata to asset images (`sort_order`) plus source tracing (`source`, optional `source_photo_id`) so downstream consumers can reliably render image galleries.
- Added `model_number_images` to store ordered default image sets per model number (with migration-time backfill from existing model image values).
- Added webshop-oriented API endpoint `GET /api/v1/hardware/{asset}/images` that returns the active image source and ordered image payload.
- Added API CRUD endpoints for model-number default images: `GET/POST/PUT/DELETE /api/v1/model-numbers/{modelNumber}/images`.
- Added test-photo promotion route for refurb flows: `POST /hardware/{asset}/tests/{testRun}/results/{result}/photos/{photo}/promote`, allowing a captured test photo to become an asset override image.

### 2026-02-17
- Quality grading is now tracked directly on assets via a dedicated hardware-detail dropdown (`Kwaliteit A` to `Kwaliteit D`) instead of being handled through the testing/spec workflow.
- Added an asset `quality_grade` field with migration-time backfill from legacy `condition_grade` attribute overrides/model defaults.
- Hardware specification override views now hide the legacy `condition_grade` attribute, and test-type resolution excludes that attribute so grading stays outside test runs.
- Device catalog condition-grade option labels were renamed to `Kwaliteit A` through `Kwaliteit D`.

### 2026-02-12
- Dashboard now includes a camera quick-action card (same style as other summary cards) that links directly to the scan page.
- The dashboard camera card is permission-gated by `scanning` and intentionally uses direct action copy instead of a `View All` footer.
- Dev seeding now includes a broader refurb dataset (10 demo assets with status spread + test runs) and richer demo user personas with asset visibility enabled for operational roles.
- Demo guide documentation now matches seeded account names and includes the full `migrate:fresh --seed` reset workflow.
- Hardware asset list now invalidates stale persisted bootstrap-table state after resets by versioning the table cookie key, preventing "empty assets list" confusion when old filters survive a DB refresh.

### 2026-02-05
- QR preview on the hardware detail page now renders the same label layout used for printed PDFs so on-screen previews match the final output.
- Test run edit links can now target a specific prior run; editing an older run updates its finished timestamp so it becomes the latest run.

### 2026-02-03
- Dashboard widgets now respect permissions: unauthorized summary blocks and charts are hidden, and counts are only computed when permitted.
- Hardware list tables no longer show Checked Out To, Purchase Cost, or Current Value columns in the refurb flow.
- Asset tags and serial numbers now default to uppercase on entry/save, with per-field override toggles to preserve original casing.

### 2025-09-25
- Added contributor guide (AGENTS.md) describing fork workflows and documentation expectations for the fork.
- Expanded the agent handbook with workflow reminders and linked it from README.md and CONTRIBUTING.md.
- Completed the model-number attribute rework (definitions/options admin, model spec editor, asset overrides, and test-run generation from needs-test attributes).

### 2025-09-26
- Converted test run generation and agent ingestion to derive diagnostics from needs_test attributes (with expected spec values).
- Persist asset specification overrides on edit and exposed formatted spec readouts on asset/model pages.
- Added feature and unit coverage around the attribute specification pipeline.
- Hardened asset overrides and test runs: reject override payloads on non-overrideable attributes and block new runs until required model specs are complete.
- Normalized numeric attribute inputs entered with alternate units (e.g., TB, GHz) while preserving the original entry for audit context.
- Added `attribute:promote-custom` artisan command to list recurring custom enum values and promote them to formal options on demand.

### 2025-09-27
- Landed multi-model-number support: schema migrations are in place with backfill, `ModelNumber` Eloquent model, and admin CRUD for adding, updating, deleting, and promoting presets (with primary selection).
- Model specification editor and asset create/edit flows now require/model-number selection; spec and override UIs reload based on the chosen preset, and asset/test views display asset-specific model numbers.
- Added feature coverage for model-number management and refreshed documentation to outline the multi-preset workflow.
- Models can now be created without an initial model number; presets are attached from the Model Numbers panel, and spec/asset flows prompt when a preset is required.
- Migration skips altering the column when running on SQLite (tests already operate with the nullable default schema in memory).

### 2025-09-30
- Removed the unfinished SKU layer in favour of multi-model-number workflows; dropped SKU routes/UI, and added a migration to prune the table/foreign keys.
- Updated asset API responses to expose model number strings and IDs, and aligned factories/tests with the model-number requirement.
- Linked test runs to model numbers so diagnostics follow the selected preset.

### 2025-10-23
- Consolidated per-session agent addenda into `docs/agents/old/agent-progress-2025.md` and trimmed the demo seed data to refurb-focused records and curated assets.
- Hid the legacy hardware-page “Generate Label” button so only the new QR module controls remain visible while we plan the long-term QR/label unification.
- Removed company selectors from the asset form for the current single-company refurb workflow (companies stay in the data model for future reinstatement).
 - Removed checkout/checkin/audit flows; status transitions now drive lifecycle tracking with status event history and notes.

### 2025-11-19
- Refreshed the QR label system for the Dymo LabelWriter 400 Turbo: added dedicated templates for 30334 (57x32 mm), 30336 (54x25 mm), 99012 (89x36 mm), 30256 (101x59 mm), plus the legacy 50x30 mm option, and exposed the picker on the asset page and bulk actions so refurbishers can match whatever roll is loaded.
- Rebuilt the PDF/layout renderer so QR codes and captions scale within a single label (no more text spilling onto extra pages) and added an inline preview/print/download widget that regenerates whenever a new template is selected.
- QR stickers now include a single block of text beside the QR containing the model + preset, serial number, asset tag, and the Inbit company line (no mutable specs/status/property-of text). The default template is now the Dymo 99010 (89×36 mm) roll, the QR column consumes ~90% of the label height, and the asset name/tag block is bottom-aligned so only one sticker prints per asset.
- Demo assets use the actual product names (e.g., “HP ProBook 450 G8”) instead of QA/Intake suffixes to keep the dataset intuitive for testers.
- Latest QR layout polish: only the asset name + asset tag render on the text column, which sticks to the bottom-right with a 5% inner margin while the QR honors the same top/bottom padding—PDFs now open with exactly one page and match the requested framing.
- Raised the QR column so it shares the same top alignment as the text block and hardened the PDF styles to eliminate the stray blank pages; 99010 labels now render as a single page with the QR on the left and asset name/tag on the lower-right.

### 2025-12-23
- Test runs are now generated from configured Test Types (with optional tests and category scoping), and attribute definitions no longer drive test creation via a needs-test flag.

### 2026-01-07
- Asset detail now highlights latest test health (failed/incomplete/missing) and includes a compact latest-tests badge.
- Asset list tables show a new Tests column reflecting latest run health, backed by test run counts in asset list APIs.
- Status changes to Ready for Sale/Sold require confirmation when tests are missing or failed, and the tests active page now prompts before finishing with open failures.

### 2026-01-08
- Latest Tests list column now shows completed/total counts, with lazy hover details for failed/missing tests including note excerpts and photo markers.
- Asset creation now allows custom asset tags (unlocking the auto-generated tag on create), while serial entry warns on duplicates and can be overridden with an explicit allow-duplicate toggle (asset tags remain unique).

### 2026-04-07
- The active test-run screen now removes the large top testing header and keeps save/progress/history controls in the bottom action bar so operators stay focused on the test cards themselves.
- The hardware detail QR print/download panel now renders below the main action buttons instead of sitting mid-stack inside the primary action group.


