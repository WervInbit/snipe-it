# Agents Addendum - 2026-06-04 Session Init

## Startup Context

- Re-read `AGENTS.md`, recent `PROGRESS.md`, `docs/fork-notes.md`, `docs/agents/agents-addendum-2026-06-02-session-init.md`, `docs/agents/agents-addendum-2026-05-28-session-init.md`, `docs/agents/session-handoff-2026-05-28.md`, `docs/plans/catalog-clean-start-mapping-2026-05-28.md`, and `docs/plans/catalog-removed-attributes-2026-06-02.md`.
- Current branch at initialization: `codex/component-hierarchy-sprints`.
- Current commit at initialization: `e72fc14b3`.
- Current date/time context: 2026-06-04, Europe/Amsterdam.

## Repository State

- The working tree is intentionally dirty and contains the ongoing component hierarchy, workflow/profile, clean catalog seed, local Docker, upload placeholder, and local production-work backup changes.
- No unrelated changes were reverted, normalized, staged, or stashed during reinitialization.
- Local-only artifacts remain present, including `.env.before-prodclone.2026-04-30`, `.env.prodclone.prodkey`, `prodbak/`, and `storage/tmp-testtypes-reorder.js`.

## Current Implemented State

- Workflow/profile tables and compatibility models are implemented through `2026_05_26_120000_rename_tests_to_workflows_and_add_profiles`.
- Clean catalog seed foundation is implemented:
  - component-oriented attributes
  - hidden/deprecated removed legacy attribute keys
  - generic component definitions and expected model-number templates for the preserved real catalog
  - grouped expected quantities
  - demo assets removed from default seed flow
- Workflow item applicability is implemented by:
  - explicit always-on tasks
  - model-number expected components
  - attached tracked components
  - component categories
  - component definitions
  - existing attribute/category sources
- Asset workflow start supports profile selection and per-run extra workflow items.
- Same-asset component hierarchy correction is exposed through the asset Components tab via `Move Within Device`.

## Last Verified State

- Work DB `snipeit_prod_work` was backed up before the latest workflow applicability migration/seed:
  - SQL dump: `prodbak/db-snapshots/snipeit_prod_work_pre_workflow_applicability_20260602_114937.sql`
  - clone schema: `snipeit_prod_work_pre_workflow_applicability_20260602_114937`
- `2026_06_02_120000_add_workflow_item_applicability_rules` was applied to `snipeit_prod_work`, then `DeviceComponentCatalogSeeder` and `AttributeTestSeeder` were rerun.
- Browser smoke on `https://dev.inbit/` passed for:
  - asset workflow start page and profile list
  - Standard Diagnostics run `#24`
  - workflow item settings applicability summaries
  - asset component Move Within Device link and empty-parent reparent screen for asset `1`
- Last focused test run passed:
  - `TestTypeForAssetTest`
  - `StartNewTestRunTest`
  - `ComponentWorkflowPagesTest`
  - `ManageTestTypesTest`
  - result: `22` tests, `127` assertions
- `php artisan view:cache` passed.

## Current Testing Guidance

- User can test current dev flow on `https://dev.inbit/`.
- Primary areas to test:
  - Standard Diagnostics on several models, especially port/camera/network differences
  - per-run extra workflow items
  - same-asset component reparenting with at least two attached tracked components
  - Workflow Item Settings applicability summaries
  - clean expected-component roster display with grouped quantities

## Known Open Items

- Persistent extra workflow items at model-number or work-order level are not implemented; current extra items are per-run only.
- Browser reparent smoke has not tested a real parent selection on asset `1` because that asset currently has one tracked component; automated tests cover the move-under and move-back behavior.
- Full `DatabaseSeeder` smoke on SQLite remains blocked by existing MySQL-only SQL in `UserSeeder`.
- Production cleanup/rebuild is still separate from the work DB rehearsal. Do not run destructive DB commands without explicit current approval and DB preflight.

## Session Notes

- Fixed the existing enum attribute option editor after `port_connector_type` showed only the top in-use warning when trying to add `eSATA`.
- Root cause: the edit-form options table did not have the pending-row hooks used by the `Add to list` JavaScript, while create-form options did.
- The edit page now supports pending `options[new]` rows, preserves those rows after validation errors, and shows scoped copy explaining that adding a new option only makes it available for future selections; renaming/removing existing options can update current rows.
- Browser smoke on `https://dev.inbit/attributes/63/edit` confirmed `port_connector_type` has 11 existing options, 24 component-definition references, and an unsaved `eSATA` row can now be added without clicking Save.
- Focused validation passed: `tests/Feature/AttributeDefinitionLifecycleTest.php` with 15 tests and 56 assertions.
- Implemented speed-specific RJ45 cataloging and seeded `eSATA` as a `port_connector_type` option. Added `ethernet_speed_max` with `1GbE`, `2.5GbE`, `5GbE`, and `10GbE`; seeded `RJ-45 Ethernet Port - 1GbE`, `2.5GbE`, `5GbE`, and `10GbE`; retired the old generic `RJ-45 Ethernet Port` to the 1GbE replacement.
- Created DB backup `prodbak/db-snapshots/snipeit_prod_work_pre_rj45_speeds_20260604_155034.sql` before rerunning `DeviceAttributeSeeder` and `DeviceComponentCatalogSeeder` on `snipeit_prod_work`.
- Browser smoke on `https://dev.inbit/admin/settings/component-definitions?search=RJ-45` showed all four speed-specific RJ45 definitions with no server errors. Focused tests passed: `DeviceComponentCatalogSeederTest`, `ComponentDerivedAttributeResolutionTest`, and `TestTypeForAssetTest` with 21 tests and 82 assertions.
- TODO carried into handoff: seed quick-entry generic fallback definitions for vague/partially known hardware. Use generic `Wireless Module`, `USB-A Port`, `USB-C Port`, and possibly `RJ-45 Ethernet Port - Unknown Speed`; leave detailed version attributes unset until known, and refine later by swapping component definitions or adding attributes.
- End-of-session handoff created at `docs/agents/session-handoff-2026-06-04.md`.
