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



