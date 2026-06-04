# Session Handoff - 2026-05-28

## Resume Context

Branch: `codex/component-hierarchy-sprints`

Primary planning artifact from this session:

- `docs/plans/catalog-clean-start-mapping-2026-05-28.md`

Read first next session:

- `AGENTS.md`
- `PROGRESS.md`
- `docs/fork-notes.md`
- `docs/agents/agents-addendum-2026-05-28-session-init.md`
- `docs/plans/catalog-clean-start-mapping-2026-05-28.md`

## Current State

The working tree is intentionally dirty. Do not revert unrelated changes.

Existing dirty work includes the workflow/profile implementation, workflow settings UI, readiness warning changes, translations, tests, local Docker/upload placeholder changes, and local-only prod clone artifacts.

Local app DB state:

- `APP_ENV=local`
- `DB_CONNECTION=mysql`
- `DB_DATABASE=snipeit_prod_work`
- Pending migration: `2026_05_26_120000_rename_tests_to_workflows_and_add_profiles`

Destructive DB commands remain forbidden without explicit current approval and a DB preflight summary.

## Completed This Session

- Reinitialized repo/session context after a pause.
- Investigated current production-work catalog data.
- Confirmed the current component definitions are examples/tests and should not be migrated.
- Created the clean-start mapping document:
  - preserves 11 current real models/model numbers
  - excludes the current example component definitions
  - removes present/test booleans from the product attribute foundation
  - replaces USB/video/audio summary dropdowns with expected component/port templates
  - maps current assets only as manual recreation references
- Browser smoke check:
  - `https://dev.inbit` loads and reaches login
  - `http://localhost` and `http://127.0.0.1` were blocked by the in-app browser
  - protected internal pages could not be verified without an authenticated session

No DB mutation, migration, seed run, or implementation block was performed in this final investigation pass.

## Accepted Decisions

- Surface Type Cover is a sale accessory/workflow item, not an expected hardware component.
- Pixel 8 Pro should seed with Google as manufacturer.
- Phone cameras should become generic camera components:
  - `camera_position`
  - `camera_role`
  - `camera_megapixels`
- This supports multiple rear cameras, such as main, ultrawide, and telephoto.
- HP ProBook 430 G3 battery capacity is deferred. Expected capacity versus current capacity should come later from actual battery scan/health work.
- Product attributes should not exist only to make workflow items/tests applicable.
- Users should be migrated separately; assets, old tests, old test photos, and current example components should not be migrated.

## Important System Caveat

Current component attribute aggregation only rolls up numeric attributes marked `resolves_to_spec` into the effective attributes list.

This means:

- RAM size and storage capacity can roll up from expected/installed components.
- RAM type, storage type, USB standard, connector type, display resolution, and other enum/text values stay on component views unless duplicated as manual model-number attributes or the resolver/display is enhanced.

This needs a decision before or during implementation:

- keep critical non-numeric specs duplicated as model-number attributes for now, or
- extend the resolver/UI to surface non-numeric component-derived specs.

## Still Open

- Decide whether to extend the resolver/UI for non-numeric component-derived specs.
- Decide whether `warranty_months` remains a model-number default or moves to sale/policy handling.
- Implement clean attribute seeds:
  - remove present/test booleans
  - remove summary dropdowns
  - remove/replace `battery_health_percent`
  - add structured component/port/camera attributes
- Implement component catalog seeder:
  - categories for Memory, Storage, Display, Battery, Ports, Camera, Audio, Input, Network, Power
  - generic spec component definitions
  - component-definition attributes
- Implement model/model-number catalog seeder:
  - seed the 11 real model numbers
  - attach expected component templates
  - keep demo assets out
- Clean workflow seeds:
  - diagnostics
  - pre-sale
  - cleaning
  - shipping laptop
  - no dependency on present-style product attributes
- Add or expose a correction workflow for reparenting an already-installed asset-level component under another installed component within the same asset.
- Fix stale `ShowAssetTest` assertions.
- Back up `snipeit_prod_work`, run pending migration, and do authenticated browser smoke checks when implementation reaches DB rehearsal.

## Suggested Next Block

Start with the seed/data foundation, not the live work-copy DB:

1. Refactor clean attribute seed data.
2. Add component category and component definition seed data.
3. Add model-number catalog seed data and expected component templates.
4. Update tests around seed output and model/component template display.

Do not run `migrate:fresh`, `migrate:refresh`, `migrate:reset`, or `db:wipe` on the local production-work DB.
