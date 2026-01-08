# Fork Notes

Maintain this log to highlight differences between this fork and upstream Snipe-IT. Add a dated section whenever features change, regressions are fixed, or documentation diverges. Reference pull requests or issues when available.

## Update Log

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
- Consolidated per-session agent addenda into `docs/agents/agent-progress-2025.md` and trimmed the demo seed data to refurb-focused records and curated assets.
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
- Asset creation now allows custom asset tags, while serial entry warns on duplicates and can be overridden with an explicit allow-duplicate toggle (asset tags remain unique).


