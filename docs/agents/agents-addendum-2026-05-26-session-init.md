# Agents Addendum - 2026-05-26 Session Init

## Startup Context
- Re-read `AGENTS.md`, `PROGRESS.md`, and `docs/fork-notes.md`.
- Also checked the top of `README.md` and `CONTRIBUTING.md` for fork documentation pointers.
- Current branch at initialization: `codex/component-hierarchy-sprints`.
- Current commit at initialization: `4ad83dd3d`.
- Current date/time context: 2026-05-26, Europe/Amsterdam.

## Repository State
- The working tree was already dirty at initialization.
- Dirty changes include the uncommitted component hierarchy sprint implementation and related docs/tests from earlier sessions.
- Older local environment and prod-clone artifacts remain present, including `.env.before-prodclone.2026-04-30`, `.env.prodclone.prodkey`, `prodbak/`, Docker config changes, upload placeholder `.gitignore` files, and `storage/tmp-testtypes-reorder.js`.
- No unrelated changes were reverted, normalized, staged, or stashed during initialization.

## Current Fork Context
- `docs/fork-notes.md` latest entry is 2026-05-19 and records the completed component hierarchy work, selling-state warning policy, hierarchy-aware spec behavior, conversion preview/apply commands, and `docs/component-hierarchy-operations.md`.
- The current `PROGRESS.md` log says Sprints 9 through 16 plus review follow-up fixes were completed in the previous working block.
- The local dev MySQL schema follow-up for `component_instance_attributes` was also logged as completed in the previous session.

## Documentation Drift Check
- `README.md` still points contributors to `AGENTS.md`, `PROGRESS.md`, and `docs/fork-notes.md`.
- `CONTRIBUTING.md` still asks contributors to review the fork docs before contributing.
- No documentation drift edit was needed during this initialization pass.

## Additional Session Files Read
- Read recent handoff addenda from 2026-05-19, 2026-05-07, 2026-05-06, 2026-04-30, 2026-04-28, 2026-04-23, 2026-04-21, and 2026-04-20.
- 2026-04-21 established the shared-attribute and component-driven specification model: attribute definitions are the common vocabulary for model-number specs, asset overrides, and component-definition contributions.
- 2026-04-23 captured the expected-baseline component UX, component-derived spec cleanup, workflow pages/modals, and component detail/list polish.
- 2026-04-30 and 2026-05-07 shifted from flat component workflows into hierarchy/subcomponent planning and implementation.
- 2026-05-19 completed the later hierarchy sprints, including component instance attributes, hierarchy-aware spec resolution, asset component tree rendering, definition/model previews, conversion tooling, documentation, and regression follow-ups.
- Carry-forward posture: component definitions remain global catalog records, component instances are physical traceability records, product/model specs resolve through shared attributes, and hierarchy depth is capped at asset -> component -> subcomponent.

## Attribute And Component Design Check
- Reviewed docs/plans and code around shared attributes, component-definition contributions, component instance attributes, component notes, and effective spec resolution.
- Component definition attributes are browser-editable in the component definition form and use the same shared attribute definitions as model-number specs and asset overrides.
- Component instance attributes exist in schema/service/API and can override definition attributes for the same tracked component row, including custom components and custom child components.
- Component instance attribute editing in the browser is not implemented; the implemented browser path for tracked-part-specific detail is the component `notes` field on the component detail page.
- Notes are appropriate for brand/part-number/freeform specifics that should not calculate into asset specs. Structured attributes are still required for anything that should aggregate, override, or appear as a resolved product spec.

## Tooling Notes
- `bash` is unavailable in this Windows environment (`/bin/bash` cannot be started).
- `rg.exe` is still unavailable due `Access denied`, so PowerShell commands are the fallback for file reads and search.
- Destructive database commands remain forbidden unless explicitly approved in the current user message with the required DB preflight summary.
- Before Docker PHPUnit, clear Laravel cached config and verify the test database resolves to the isolated testing DB.

## Reinitialization Outcome
- Initialization docs were updated, then the session moved into the component/attribute capability check and child-component creation request.
- Clarified that top-level asset component creation already exists in the worktree through the asset Components tab / `hardware.components.add` flow, while arbitrary child-component creation from a parent component detail page was not exposed as a general browser workflow.
- Added `components.children.store` and an `Add Child Component` form on installed top-level component detail pages.
- The new workflow supports either an active subcomponent-capable component definition or a custom tracked component name, starts the child as `Needs Attention`, requires warning confirmation, attaches it under the same current/root asset as the parent, and stores specific freeform details in the child `notes` field.
- Focused verification passed after Docker cache clear and testing DB preflight (`APP_ENV=testing`, `DB_CONNECTION=sqlite`, `DB_DATABASE=/var/www/html/database/database.sqlite`):
- `docker compose run --rm --no-deps -e APP_ENV=testing app sh -lc 'php artisan optimize:clear && echo Test_DB_preflight && grep APP_ENV .env.testing && grep DB_CONNECTION .env.testing && grep DB_DATABASE .env.testing && ./vendor/bin/phpunit --filter ComponentDetail tests/Feature/Components/Ui/ShowComponentTest.php'`
- result: `10` tests passed, `92` assertions.
- Top-level asset add page regression also passed:
- `docker compose run --rm --no-deps -e APP_ENV=testing app sh -lc 'php artisan optimize:clear && echo Test_DB_preflight && grep APP_ENV .env.testing && grep DB_CONNECTION .env.testing && grep DB_DATABASE .env.testing && ./vendor/bin/phpunit --filter AssetAddPage tests/Feature/Components/Ui/ComponentWorkflowPagesTest.php'`
- result: `1` test passed, `13` assertions.
- A route-list diagnostic was attempted but did not complete because non-testing route bootstrap hit the existing missing settings row around SAML settings; no route-cache file exists in `bootstrap/cache`.

## Workflow Implementation Block 1
- Added the first workflow/profile implementation slice after the attribute/component planning discussion.
- New workflow-named tables are introduced through `database/migrations/2026_05_26_120000_rename_tests_to_workflows_and_add_profiles.php`, with migration copy paths from legacy `test_*` tables and rollback copy paths back to legacy names.
- Added `WorkflowProfile` and `WorkflowProfileItem` models/factories. Existing `TestRun`, `TestResult`, `TestType`, `TestResultPhoto`, and `TestAudit` classes now point at workflow tables to keep the first slice smaller and preserve controller/API compatibility.
- Asset workflow starts now require/select an active workflow profile, snapshot profile identity on runs, and create ordered results from profile items with `is_required`, `result_label_mode`, and `sort_order` snapshots.
- Active workflow cards still use the existing two-button, notes, and photos interaction model. Task-style profile items can display `Done` / `Not Done` labels while reusing pass/fail values internally.
- Seed foundation now creates standard diagnostics, pre-sale check, cleaning, and shipping-laptop workflow profiles, with operational task items separated from diagnostic tests.
- Agent reports accept `test_results` and `workflow_results`, write workflow run/profile metadata, and return `workflow_run_id` plus legacy `test_run_id`.
- Retargeted `asset_images.source_photo_id` from legacy result photos to `workflow_result_photos`; SQLite PHPUnit exposed and verified this migration edge.
- No live dev/prod-clone DB migration was run in this block.
- Verification passed against `.env.testing` SQLite after Docker `php artisan optimize:clear` and test DB preflight:
- workflow/profile focused set: `12` tests, `61` assertions.
- agent/photo/audit/status/relationship regression set: `25` tests, `113` assertions.
- changed-file PHP syntax checks passed.
- `git diff --check` passed with line-ending warnings only.

## Workflow Profiles UI And Readiness Block
- Added `Admin\WorkflowProfileController`, `settings.workflow-profiles.*` routes, and the `settings/workflow-profiles` Blade page.
- Workflow Profiles can now be created, edited, deleted, category-scoped, marked active/default, marked as sale-readiness blocking, and assigned ordered workflow items.
- Per-profile item settings now expose `sort_order`, `is_required`, and `result_label_mode`, so diagnostic checks can keep Pass/Fail labels while operational task lists can show Done/Not Done labels.
- The Settings index and admin nav now point to Workflow Profiles; Workflow Items remain available as the reusable item vocabulary from a cross-link.
- `Asset::latestTestIssueSummary()` and `refreshTestCompletionFlag()` now evaluate every active applicable profile with `blocks_sale_readiness=true`; legacy latest-run behavior remains the fallback when no blocking profiles are configured.
- Asset update/status and bulk status warnings continue to use the same Ready for Sale/Sold acknowledgment locations, now including named missing workflow profile runs.
- Added feature coverage in `ManageWorkflowProfilesTest` and expanded Ready for Sale warning coverage for missing blocking profile runs.
- Verification passed against `.env.testing` SQLite after Docker cache clear:
- `ManageWorkflowProfilesTest`: `4` tests, `16` assertions.
- workflow/settings/status focused set: `11` tests, `53` assertions.
- broader workflow/profile regression set: `42` tests, `197` assertions.
- Page-oriented smoke still reports two stale `ShowAssetTest` expectations, not workflow HTTP failures: old QR-label copy and an old run-row assertion for an asset without a run.
- Live dev database migration/browser testing remains intentionally pending until `snipeit_prod_work` is backed up and migrated.
