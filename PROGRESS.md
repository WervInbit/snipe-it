# Session Progress (2025-10-02)

## Addendum (2025-10-02 Codex)
- Session initiated: reviewed AGENTS.md guidance and recent PROGRESS entries to align with fork expectations.

## Addendum (2025-10-02 Codex - Follow-up)
- Re-reviewed AGENTS.md, PROGRESS.md, and docs/fork-notes.md to confirm carryover work before resuming.
- Current focus: process feedback and update the project based on the latest review inputs.

- Removed the model-number input from the model create flow and redirected post-create to the detail view with guidance to add presets.
- Added a dedicated model-number create page under settings and wired the spec editor CTA to it when no presets exist.
- Updated build/runtime handling for Passport keys (generate during image build, validate only at runtime) and preserved dev-cache clearing guidance.
- Default asset tag generation now uses random two-letter prefixes plus the sequential counter (e.g., ASSET-XY0001) and auto-assigns when tags are omitted; minimal-create test updated accordingly.
- Session paused: finish the outstanding feature list (deprecated preset filtering in index/API, QR module rebuild, attribute enum UX, test-run wiring, role-based start page, docs/tests) and run `php artisan migrate && php artisan test` once PHP is available.


## Notes for Follow-up Agents
- Track ongoing details in docs/agents/progress-addendum-2025-10-02-session-kickoff.md during this session (review alongside this log).
- Pending detailed updates once work completes this session.
- Carryover reminders: rebuild/restart the app service for the Passport key entrypoint fix, verify oauth key files persist after a cold start (capture logs if missing), run `php artisan migrate` post-merge to drop SKUs + add the test-run column, and install composer dev packages (Collision) before running `php artisan test`.

# Session Progress (2025-09-30)

## Addendum (2025-09-30 Codex)
- Detailed notes: docs/agents/progress-addendum-2025-09-30-passport-keys.md (review alongside this summary).
- Investigated the recurring Passport key failure after docker volume resets and confirmed the storage mount starts without oauth key material.
- Extended docker/app/entrypoint.sh to auto-run php artisan passport:keys --force, chown the generated files to www-data, and lock permissions so HTTP requests can decrypt tokens immediately after boot.
- Shared a stopgap for the current stack: execute docker compose exec app php artisan passport:keys --force once to repopulate keys until the container restarts with the updated entrypoint.
- Finished retiring the orphaned SKU scaffolding: removed UI/API references, added a schema cleanup migration, and exposed model-number metadata in asset/test APIs and transformers.

## Notes for Follow-up Agents
- Rebuild or restart the app service (docker compose up -d --build app) to pick up the entrypoint change and verify the hardware index loads without manual key generation.
- After the next cold start, confirm storage/oauth-public.key and storage/oauth-private.key exist on the shared volume; if not, capture container logs for the entrypoint to debug further.
- Run `php artisan migrate` inside the app container once this branch lands to drop the legacy SKU tables and add the test-run model-number column.
- Composer dev packages are missing in the container (`Collision` dependency); install them before attempting `php artisan test` so the new assertions can be exercised.
# Session Progress (2025-09-28)

## Addendum (2025-09-28 Codex)
- Session restarted after prior context drop; reviewed AGENTS/PROGRESS/fork notes to re-establish scope on model number settings work.
- Restored attribute creation form by passing the expected layout context so the form renders without `$item` errors.
- Exposed the Model Numbers admin page in the settings side nav so superusers can reach the new CRUD screen.
- Remapped Model Numbers settings auth to rely on the existing asset-model permissions so admins with model access can enter without 403s.
- Escaped the spec editor alert copy so Blade compiles cleanly when model categories message includes an apostrophe.
- Flagged a UX follow-up: enum options should support list-based entry instead of delimiter-separated input in quick-add flows.
- Hardened the asset detail spec table rendering to avoid nested Blade expressions, preventing parse errors on environments sensitive to inline helpers.
- Reframed webshop visibility as an allow-list toggle, added matched internal-use control on asset show/edit, and default new assets to stay off-sale until explicitly approved.

## Notes for Follow-up Agents
- Smoke-test the Admin → Settings → Model Numbers page once PHP/JS assets are recompiled to confirm new CRUD + search interactions behave as expected.
- Continue wiring specification flows to respect the selected model number and fill in outstanding documentation updates once core pages stabilize.

# Session Progress (2025-09-26)

# Session Progress (2025-09-27)

## Addendum (2025-09-27 Codex)
- Session initiated on Raspberry Pi environment; reviewing WIP multi-model-number migrations and related services.
- Goal: confirm migration drafts, scope remaining refactors (relationships, UI/API), and plan data backfill + documentation updates.
- Implemented data-layer shift to `model_numbers` (service layer, resolver, factories) and began wiring asset create/update flows and spec UI around selectable model numbers.
- Added admin CRUD + UI for model number presets, wired spec editor + asset forms to respect the selected preset, refreshed display helpers, and captured regression coverage/documentation updates.
- Enabled creating models without an initial model number (schema change, validation, controller + API updates), reworked spec/asset views to guard when no presets exist, and updated docs/tests for the new workflow.

## Summary
- Confirmed workflow requirements now call for multiple model numbers per model with dropdown selection for refurbishers.
- Scaffolded the new data layer: drafted migrations for `model_numbers`, backfill, and the accompanying Eloquent model.
- Began refactoring attribute storage to reference `model_number_id` instead of `model_id`.

## Notes for Follow-up Agents
- Work paused due to environment usage limits before migrations were finalized—double-check the two new migration files for consistency and run them once access is restored.
- Continue porting relationships and services (`ModelAttributeManager`, resolvers, controllers, UI) to the multi-number schema.
- Update `model_number_rework.txt` and fork docs to reflect the new workflow once implementation resumes.
- Planned next steps: finish data migration backfill, build admin CRUD for model numbers + specs, update asset create/edit (web & API) to require model + model number selection.

## Summary
- Finished staging attribute-driven test generation so new runs and agent uploads build from needs_test model specs.
- Persisted asset specification overrides on updates and exposed formatted spec details on asset and model views.
- Polished spec/override UIs (required flags, bool labels) and added targeted PHPUnit coverage for the flow.
- Added guard rails for asset overrides and test runs (reject disallowed overrides and require complete model specs before launching tests).
- Added unit-aware numeric normalization (e.g., TB, GHz) for model attributes while preserving the original input for audit context.
- Introduced `attribute:promote-custom` artisan command to surface and promote recurring custom enum values.

## Notes for Follow-up Agents
- PHP CLI is unavailable in this environment, so rerun the new PHPUnit cases once a PHP binary is present.
- Keep an eye on legacy files already modified in the worktree (public uploads, package-lock) when preparing commits.
- Manually exercise asset update and test-run flows to verify the new validation rules; automated tests were not executed this pass.
- To test: attempt an asset override on a non-overrideable attribute and confirm the request is rejected with a validation error.
- To test: start a test run with a required model attribute missing and ensure the run is blocked with the missing attributes listed.
- To test: enter values such as "0.5 TB" or "2.5 GHz" when editing model specs and confirm they are converted into the attribute's canonical unit while retaining the raw input for reference.
- To test: run `php artisan attribute:promote-custom <attribute_key>` (with and without `--apply`) to verify the command reports custom values and optionally creates options.

# Session Progress (2025-09-25)

## Summary
- Detailed notes: docs/agents/progress-addendum-2025-09-25-model-number.md (review alongside this summary).
- Created `AGENTS.md` to consolidate contributor guidance tailored to this fork.
- Linked the agent documentation from README.md and CONTRIBUTING.md for quicker discovery.
- Hardened EULA fallback and asset visibility logic so category listings work before settings seeding.
- Delivered the model-number attribute infrastructure (migrations, Eloquent models, admin UI, and resolver services).
- Wired asset create/edit flows for specification overrides and test runs that honor needs-test attributes.
- Updated contributor guide with documentation alignment requirements for this fork.
- Switched `.env` to local debug mode and recycled docker stack for troubleshooting.
- Fixed Passport key permissions so API endpoints load under the web container user.

## Notes for Follow-up Agents
- Review `AGENTS.md` for contributor guidance updates before expanding documentation.
- Backfill existing model data into the new attribute tables before enforcing required specs in production.
- Extend import/API layers to read/write the new attribute structures and add regression tests.
- If additional work occurs in a new session, create a dated addendum (e.g., `progress-2025-09-26.md`) and reference this log.
- Keep `docs/fork-notes.md` focused on high-level feature deltas; log incremental fixes here in `PROGRESS.md`.
- Keep README.md and CONTRIBUTING.md references in sync if the agent docs move or get renamed.
- Session closed for 2025-09-25; resume outstanding work next shift.

# Session Progress (2025-09-28)
You dropped in the middle of removing sku's and we stopped the session:
your last message:
SKUs are still doing real work today; they’re more than a relic of the single-model-number era:

Variant labelling & filters – AssetModel::skus() powers the Admin → SKUs screens, usage dashboards, and the “SKU and Model reports” in app/Http/Controllers/ReportsController. Shops tag outgoing stock (“T480 – 16GB/512GB”) and filter inventory by that label; dropping SKUs removes those grouping/report pivots.
Test history joins – test_runs.sku_id links test results to the exact outgoing variant. Ops reports (and alerting) use it to pinpoint which SKU failed diagnostics, even when multiple trims share the same base model number.
Legacy reporting & API contracts – Existing LiveSlides/BI exports (and API clients) call GET /api/v1/skus or pass sku_id= to the assets endpoint. Removing the entity is a breaking change for those consumers.
The newer model-number system overlaps with SKU duties (spec presets; attribute overrides), so if you’re committed to consolidating on model numbers only, here’s what the SKU layer currently offers that you’d need to rehome:

Variant-specific naming visible in asset lists, exports, and queue labels.
Reporting filters and API params tied to sku_id.
Historical analytics that assume test_runs and assets reference SKUs.
If you’re ready to deprecate SKUs, we can map each of those behaviors onto the model-number + attribute pathway (e.g., expose model-number labels where we display SKU names, move the test_runs foreign key to model_number_id, add report filters for presets, etc.). That’s more involved than a straight delete but keeps workflows intact. Let me know if you want a concrete migration plan.
my last message:
there are multiple duplicate functions that still need to be removed, sku will be one of them. and sku doesnt fail diagnostics, a specific model does.

you had made hcanges in the following files before crashing:
18 files changed
+9
-167

Undo

View all changes
AgentReportController.php
+1
-1

AssetsController.php
+0
-2

TestRunController.php
+1
-1

StoreAssetRequest.php
+0
-14

UpdateAssetRequest.php
+0
-12

Asset.php
+0
-9

AssetModel.php
+0
-6

TestRun.php
+6
-5

AssetPresenter.php
+0
-7

AuthServiceProvider.php
+0
-3

RouteServiceProvider.php
+0
-1

permissions.php
+0
-27

AssetFactory.php
+0
-5

general.php
+0
-2

edit.blade.php
+1
-35

view.blade.php
+0
-4

default.blade.php
+0
-8

api.php
+0
-25
















