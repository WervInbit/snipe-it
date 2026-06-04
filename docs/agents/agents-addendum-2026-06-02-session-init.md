# Agents Addendum - 2026-06-02 Session Init

## Startup Context

- Re-read `AGENTS.md`, recent `PROGRESS.md`, `docs/fork-notes.md`, `docs/agents/session-handoff-2026-05-28.md`, and `docs/plans/catalog-clean-start-mapping-2026-05-28.md`.
- Current branch at initialization: `codex/component-hierarchy-sprints`.
- Current commit at initialization: `e72fc14b3`.
- Current date/time context: 2026-06-02, Europe/Amsterdam.

## Repository State

- The working tree is already dirty and should be treated as in-progress user/agent work.
- Dirty tracked files include the workflow/profile implementation, workflow settings UI, readiness warning changes, translations, tests, seeders, docs, local Docker config, and upload placeholder files.
- Untracked files include workflow profile implementation files, the workflow migration, the clean-start catalog mapping, the 2026-05-28 handoff, local production-clone env/backup artifacts, and workflow profile tests/views.
- No unrelated changes were reverted, normalized, staged, or stashed during reinitialization.

## Environment State

- Docker Desktop was not reachable during reinitialization, so `docker compose ps` and `php artisan migrate:status` could not run.
- Last known local app DB state from the 2026-05-28 handoff:
  - `APP_ENV=local`
  - `DB_CONNECTION=mysql`
  - `DB_DATABASE=snipeit_prod_work`
  - pending migration: `2026_05_26_120000_rename_tests_to_workflows_and_add_profiles`
- Destructive DB commands remain forbidden without explicit current approval and a DB preflight summary.

## Ready / Recently Verified

- Workflow/profile implementation was previously verified against isolated SQLite testing on 2026-05-26:
  - workflow/settings/status focused set: `11` tests, `53` assertions
  - broader workflow/profile regression set: `42` tests, `197` assertions
  - `ManageWorkflowProfilesTest`: `4` tests, `16` assertions
- Browser smoke on 2026-05-28 reached `https://dev.inbit` login, but protected component/workflow pages were not verified without an authenticated session.

## Accepted Decisions

- Surface Type Cover is a sale accessory/workflow item, not an expected hardware component.
- Pixel 8 Pro should seed with Google as manufacturer.
- `warranty_months` belongs to sale/policy handling and should not be seeded as a device attribute.
- Repeated port expectations should be grouped by quantity in seed/template/UI behavior, for example `USB-A Port - USB 3.1 Gen1 x3`.
- Phone cameras should be generic expected camera components with:
  - `camera_position`
  - `camera_role`
  - `camera_megapixels`
- HP ProBook 430 G3 battery capacity is deferred until actual battery scan/health handling.
- Product attributes should not exist only to make workflow items/tests applicable.
- Users should be migrated separately; assets, old tests, old test photos, and current example components should not be migrated.

## Open Decisions

- Decide whether to extend the resolver/UI so non-numeric component-derived specs appear in the main attributes panel, or duplicate critical non-numeric specs as manual model-number attributes for now.

## Open Implementation Blocks

- Clean attribute seed data:
  - remove present/test booleans
  - remove USB/video/audio summary dropdowns
  - remove/replace `battery_health_percent`
  - add structured component/port/camera attributes
- Component catalog seeder:
  - seed Memory, Storage, Display, Battery, Ports, Camera, Audio, Input, Network, and Power categories
  - seed generic spec component definitions
  - seed component-definition attributes
- Model/model-number catalog seeder:
  - seed the 11 real current model numbers
  - attach expected component templates
  - keep demo assets out of clean-start seed
- Workflow seed cleanup:
  - diagnostics
  - pre-sale
  - cleaning
  - shipping laptop
  - no dependency on present-style product attributes
- Correction workflow:
  - expose reparenting for an already-installed asset-level component under another installed component within the same asset
- Verification:
  - fix stale `ShowAssetTest` assertions
  - back up `snipeit_prod_work`
  - run pending workflow migration after approval
  - perform authenticated browser smoke checks

## Suggested Next Block

Start with seed/data implementation rather than touching the local production-work database:

1. Refactor clean attribute seed data.
2. Add component category and component definition seed data.
3. Add model-number catalog seed data and expected component templates.
4. Update focused tests around seed output and model/component template display.

## Implementation Update

- Clean-start seed implementation started and completed for the catalog foundation, without mutating the live/dev MySQL database.
- `DeviceAttributeSeeder` now seeds component-side categories and structured attributes for RAM speed, battery capacity, cameras, and ports.
- Removed legacy catalog keys are hidden/deprecated rather than hard deleted; the explicit audit list lives in `docs/plans/catalog-removed-attributes-2026-06-02.md`.
- `DeviceComponentCatalogSeeder` now seeds generic component definitions and expected model-number component templates for the 11 preserved model numbers.
- `DatabaseSeeder` now calls catalog attributes, model-number presets, component catalog templates, and workflow item/profile seeds, and no longer calls `DemoAssetsSeeder` by default.
- Expected component quantities now render grouped on the asset component roster and still multiply correctly for numeric calculated specs.
- Validation passed:
  - PHP syntax checks for touched seeders/services/tests.
  - `tests/Feature/ComponentDerivedAttributeResolutionTest.php`: 13 passed, 49 assertions.
  - `tests/Feature/Assets/Ui/ComponentHistoryTest.php`: 8 passed, 43 assertions.
  - `php artisan view:cache` passed.
  - Individual testing-SQLite seed smoke checks passed for `DeviceAttributeSeeder`, `DevicePresetSeeder`, `DeviceComponentCatalogSeeder`, and `AttributeTestSeeder`.
- Validation caveats:
  - Browser smoke reached the local login page, but authenticated asset component roster pages were not checked without a browser login session.
  - The testing SQLite database needed the pending workflow migration before `AttributeTestSeeder` could run because `workflow_items` was absent.
  - Full `DatabaseSeeder` smoke on SQLite is blocked by existing MySQL-only SQL in `UserSeeder`.
  - Existing settings write tests for workflow items/profiles still redirect to `/`; this reproduced outside the new catalog seeders and remains a separate workflow/settings test issue.

## Remaining Follow-ups

- Authenticated browser smoke of model-number/component/asset roster pages after the local app has a usable logged-in session.
- Reparenting UI for moving already-attached asset-level components under another installed component on the same asset.
- Decide whether non-numeric component-derived values should appear in the main effective attributes panel.
- Before touching the live/dev MySQL database, perform the required DB preflight and copy/backup step.

## Workflow Applicability Update

- Implemented workflow item applicability by component category, component definition, and explicit always-on tasks.
- Added a per-run extra workflow item picker for one-off checks on the asset workflow start page.
- Added same-asset component reparenting from the asset Components tab, with lifecycle-service validation and `reparented` events.
- Backed up `snipeit_prod_work` before applying the new migration/seed:
  - SQL dump: `prodbak/db-snapshots/snipeit_prod_work_pre_workflow_applicability_20260602_114937.sql`
  - clone schema: `snipeit_prod_work_pre_workflow_applicability_20260602_114937`
- Applied `2026_06_02_120000_add_workflow_item_applicability_rules`, reran component/workflow seeders, and verified asset `INBIT-QI0001` Standard Diagnostics run `#24` generated 15 model-specific items without VGA/Ethernet/SD reader.
- Focused tests passed: `TestTypeForAssetTest`, `StartNewTestRunTest`, `ComponentWorkflowPagesTest`, and `ManageTestTypesTest` (`22` tests, `127` assertions).
- Browser screenshots for this block are under `storage/app/codex-screenshots/`.
