# Agents Addendum - 2026-05-07 Session Init

## Startup Context
- Re-read `AGENTS.md`, `PROGRESS.md`, and `docs/fork-notes.md`.
- Current branch at initialization: `master`.
- Current commit at initialization: `4ad83dd3d`.
- Current date/time context: 2026-05-07, Europe/Amsterdam.

## Repository State
- The working tree was already dirty at initialization.
- Pre-existing local changes include local Docker config, upload placeholder `.gitignore` files, prior addenda, prod-clone env files, `prodbak/`, and `storage/tmp-testtypes-reorder.js`.
- The 2026-05-06 initialization docs are also still uncommitted.
- No unrelated changes were reverted or normalized.

## Investigation Target
- Implement the planned component hierarchy/subcomponent architecture from `docs/plans/component-hierarchy-subcomponents-plan.md`.
- First step for this session is investigation only: compare the plan to the current code and identify misalignments, newly exposed constraints, useful suggestions, pitfalls, benefits, and detriments.

## Carry-Forward Safety Notes
- The last documented active local environment was switched to the production-key clone variant against `snipeit_prod_work`.
- Treat database operations as high risk until `.env`, cached Laravel config, and test database resolution are verified.
- Destructive database commands remain forbidden without explicit current-message approval and DB preflight output.

## Investigation Findings
- The subcomponent plan remains directionally sound: using `ComponentInstance` for both top-level components and subcomponents matches the current traceability foundation.
- The current implementation is still flat in the core assumptions:
- `Asset::trackedComponents()` uses `component_instances.current_asset_id`.
- `AssetComponentRosterService` produces flat `expected`, `expected_tracked`, `extra`, `custom`, and `removed` rows.
- `ComponentAttributeAggregator` sums flat roster rows and has no hierarchy/depth precedence.
- `ComponentLifecycleService` only installs into assets and removes from assets/tray/stock; it has no parent-component attach/detach path.
- The current `status` column mixes placement and attention/condition. `needs_verification` and `defective` are statuses today, while the plan needs attached parts to remain attached while carrying `needs_attention` or `damaged` condition state.
- `markDefective()` currently rejects installed components and detaches loose components. This conflicts with the plan's damaged-but-attached rule.
- `flagNeedsVerification()` currently clears `current_asset_id`, which conflicts with the planned attached-needs-attention state.
- The plan should probably add explicit parent linkage columns to `component_events` (`from_parent_component_instance_id`, `to_parent_component_instance_id`) rather than relying only on `payload_json` for parent attach/detach/move history.
- Existing legacy `App\Models\Component` / `components` table still coexists with the newer `ComponentInstance` / `component_instances` traceability model. Implementation should avoid ambiguous naming and route/API assumptions.
- Active `.env` check during investigation: `APP_ENV=local`, `DB_CONNECTION=mysql`, `DB_DATABASE=snipeit_prod_work`; `bootstrap/cache/config.php` was absent.

## Clarified Policy
- Damaged or needs-attention parts should not block install/attach flows.
- Damaged or needs-attention attached parts should not block moving an asset into selling/sold/ready-for-sale states.
- Those flows should show warnings and allow confirmation instead of rejecting the action.
- Destroyed components are the hard terminal case: they should be lockable from normal reinstall/reattach flows and carry a destruction note, verification event, or equivalent audit evidence.
- Updated `docs/plans/component-hierarchy-subcomponents-plan.md` to reflect this.

## Sprint Plan
- Created `docs/plans/component-hierarchy-sprint-implementation-plan.md`.
- The sprint plan is intended to be standalone and review-gated after each feature-sized increment.
- It preserves the original hierarchy intent while requiring user approval for design changes, conversion behavior, UI expansion, or blocking/warning policy changes.
- Follow-up review against the original hierarchy plan found no major conceptual mismatch.
- Tightened Sprint 3 so removed/detached rows are not promised before expected child state exists.
- Tightened Sprint 5/6 around closed ancestry snapshots and no parent history cloning/live inheritance.
- Moved lifecycle/condition field naming into the first review gate before schema work.

## Sprint 0 Baseline
- Created and switched to branch `codex/component-hierarchy-sprints`.
- Left existing dirty local environment/prod-clone artifacts in place per user direction.
- Verified active local env points to `APP_ENV=local`, `DB_CONNECTION=mysql`, `DB_DATABASE=snipeit_prod_work`.
- Confirmed `bootstrap/cache/config.php` was absent before cache clearing.
- Ran `docker compose exec app php artisan optimize:clear` successfully.
- Verified testing config resolves to `APP_ENV=testing`, `DB_CONNECTION=sqlite`, `DB_DATABASE=/var/www/html/database/database.sqlite`.
- Focused baseline suite passed:
- `tests/Feature/ComponentDerivedAttributeResolutionTest.php`
- `tests/Feature/Assets/Ui/ComponentHistoryTest.php`
- `tests/Feature/Components/Ui/ShowComponentTest.php`
- `tests/Feature/Components/Ui/ComponentWorkflowPagesTest.php`
- `tests/Feature/Components/Ui/ComponentBrowserWorkflowTest.php`
- `tests/Feature/Components/Domain/ComponentLifecycleServiceTest.php`
- Result: `31` tests passed, `210` assertions.

## Sprint 1 Persistence Foundation
- Implemented `database/migrations/2026_05_07_120000_add_component_hierarchy_foundation.php`.
- Added `component_instances.parent_component_instance_id`, `root_asset_id`, `is_materialized_expected`, `materialized_reason`, and ancestry snapshot fields.
- Added `component_definitions.placement_mode` with allowed values `asset_only`, `subcomponent_only`, and `either`; default remains `either`.
- Extended `ComponentInstance` with parent, child, root asset, and ancestry relations.
- Added model validation that enforces the hard depth cap of `asset -> component -> subcomponent`.
- Updated `ComponentLifecycleService` so current top-level asset install/remove/stock/verification/destruction flows maintain `root_asset_id` and clear parent linkage when a component leaves an attached tree.
- Added `tests/Feature/Components/Domain/ComponentHierarchyFoundationTest.php` for the new persistence foundation.
- Verification:
- `docker compose exec app php artisan optimize:clear` passed.
- testing preflight resolved to `APP_ENV=testing`, `DB_CONNECTION=sqlite`, `DB_DATABASE=/var/www/html/database/database.sqlite`.
- `docker compose exec app php artisan test --env=testing tests/Feature/Components/Domain/ComponentHierarchyFoundationTest.php` passed with `5` tests and `21` assertions.
- focused Sprint 0 baseline rerun passed with `31` tests and `210` assertions.
- MySQL migration SQL sanity check passed in pretend mode with `docker compose exec app php artisan migrate --pretend --path=database/migrations/2026_05_07_120000_add_component_hierarchy_foundation.php`.
- Review notes:
- no new blockers were found.
- parent movement/detach cascades remain deferred to the later movement sprint.
- parent context on `component_events` remains a pending design choice for the materialization/event-history sprint.
- `lifecycle_status` plus `condition_status` remains the recommended future split unless the user chooses a different naming option before that schema work starts.

## Sprint 2 Expected Subcomponents On Definitions
- Implemented `database/migrations/2026_05_07_130000_create_component_definition_subcomponent_templates.php`.
- Added `ComponentDefinitionSubcomponentTemplate` plus factory support.
- Added `ComponentDefinition::subcomponentTemplates()` and `ComponentDefinition::usedAsSubcomponentTemplates()`.
- Added `ComponentDefinitionSubcomponentTemplateManager` to sync expected child rows from the component definition editor.
- Extended the component definition create/edit form with expected subcomponent rows that support catalog-backed child definitions, freeform expected names, quantities, required flag, notes, row deletion, and row reordering.
- Existing model-number expected-component routes and management tests remain unchanged.
- Verification:
- `docker compose exec app php artisan optimize:clear` passed.
- testing preflight resolved to `APP_ENV=testing`, `DB_CONNECTION=sqlite`, `DB_DATABASE=/var/www/html/database/database.sqlite`.
- `docker compose exec app php artisan test --env=testing tests/Feature/Settings/ComponentDefinitionSettingsTest.php` passed with `12` tests and `57` assertions.
- `docker compose exec app php artisan view:cache` passed.
- `docker compose exec app php artisan test --env=testing tests/Feature/Models/ModelNumberComponentTemplateManagementTest.php tests/Feature/Components/Domain/ComponentHierarchyFoundationTest.php` passed with `7` tests and `36` assertions.
- focused Sprint 0 baseline rerun passed with `31` tests and `210` assertions.
- MySQL migration SQL sanity check passed in pretend mode with `docker compose exec app php artisan migrate --pretend --path=database/migrations/2026_05_07_130000_create_component_definition_subcomponent_templates.php`.
- Review notes:
- no new blockers were found.
- expected subcomponent management is currently only on component definition pages.
- read-only model-number previews remain a possible later option, not implemented in Sprint 2.
- freeform expected child rows are supported now, matching the sprint plan.
- asset/component-specific expected child state remains deferred to Sprint 4.

## Sprint 3 Component Detail Child Structure
- Updated `ComponentsController@show` to eager-load attached child components and definition-level expected subcomponent templates.
- Added a read-only inline `Child Structure` section to `resources/views/components/view.blade.php`.
- The section renders attached child components with links to the normal component detail page.
- The section renders assumed expected subcomponent rows from the parent component definition.
- No child operations, materialization, removed/detached state, or separate subcomponent detail page were added.
- Verification:
- `docker compose exec app php artisan optimize:clear` passed.
- testing preflight resolved to `APP_ENV=testing`, `DB_CONNECTION=sqlite`, `DB_DATABASE=/var/www/html/database/database.sqlite`.
- `docker compose exec app php artisan test --env=testing tests/Feature/Components/Ui/ShowComponentTest.php` passed with `8` tests and `53` assertions.
- `docker compose exec app php artisan view:cache` passed.
- `docker compose exec app php artisan test --env=testing tests/Feature/Components/Domain/ComponentHierarchyFoundationTest.php tests/Feature/Settings/ComponentDefinitionSettingsTest.php` passed with `17` tests and `78` assertions.
- focused Sprint 0 baseline rerun passed with `33` tests and `226` assertions.
- Review notes:
- no new blockers were found.
- child structure is inline and visible by default.
- expected rows are not matched against tracked children yet; that remains deferred until expected child state/materialization exists.

## Asset Add Definition-Backed Component Fix
- User reported a local `dev.inbit` error when creating a new component on an existing asset: MySQL rejected `component_instances.display_name = null`.
- Root cause: `ComponentInstance` creation checked the accessor value for `display_name`; the accessor could return the selected component definition name without writing that value to the raw non-null column.
- Updated the create hook to inspect the raw display-name attribute and copy the component definition name into the raw attribute before insert.
- Added a regression to `ComponentBrowserWorkflowTest` for asset-page creation of a definition-backed component without a custom name.
- Verification:
- targeted regression passed with `1` test and `5` assertions.
- `ComponentBrowserWorkflowTest` passed with `7` tests and `51` assertions.
- focused component/spec baseline passed with `34` tests and `231` assertions.

## Sprint 4 Materialize Expected Child
- Added `component_expected_subcomponent_states` with parent component/template uniqueness, `removed_qty`, and `materialized_qty`.
- Added `ComponentExpectedSubcomponentState`, factory support, and `ComponentExpectedSubcomponentService`.
- Materializing an expected child now creates a tracked installed child component under the parent, inherits asset/root context, records the materialization reason, and stores source-template metadata.
- Component detail expected-subcomponent rows now show expected/tracked/removed/remaining counts and expose an explicit `Track` form while remaining quantity exists.
- Existing top-level asset expected-component materialization was kept unchanged and covered by the broader workflow regression run.
- Applied the Sprint 4 migration to the local dev MySQL clone after MySQL pretend output looked correct.
- Verification:
- `docker compose exec app php artisan optimize:clear`
- testing preflight: `APP_ENV=testing`, `DB_CONNECTION=sqlite`, `DB_DATABASE=/var/www/html/database/database.sqlite`
- `docker compose exec app php artisan test --env=testing tests/Feature/Components/Domain/ComponentExpectedSubcomponentMaterializationTest.php tests/Feature/Components/Ui/ShowComponentTest.php` passed with `11` tests and `82` assertions.
- `docker compose exec app php artisan test --env=testing tests/Feature/Assets/Ui/ComponentHistoryTest.php tests/Feature/Components/Ui/ComponentWorkflowPagesTest.php tests/Feature/Components/Ui/ComponentBrowserWorkflowTest.php tests/Feature/Components/Domain/ComponentHierarchyFoundationTest.php tests/Feature/Settings/ComponentDefinitionSettingsTest.php` passed with `35` tests and `215` assertions.
- `docker compose exec app php artisan view:cache` passed.
- final `docker compose exec app php artisan optimize:clear` passed.
- Review notes:
- used explicit `Track` as the first UI trigger.
- added `materialized_qty` even though the likely-schema block only listed `removed_qty`; this matches the Sprint 4 requirement that state tracks removed/materialized quantity.
- no child detach/removal behavior was added; that remains Sprint 5.

## Sprint 5 Detach Child To Tray Or Stock
- Updated `ComponentLifecycleService::removeToTray()` and `moveToStock()` so attached children receive a closed ancestry snapshot when they detach.
- Updated `ComponentLifecycleService::installIntoAsset()` with the same snapshot handling for existing direct transfer routes.
- Detached children now retain `ancestry_parent_component_instance_id`, `ancestry_attached_through_at`, and `ancestry_attached_through_event_id` for traceability without copying parent event history.
- Materialized expected child detachment now transfers parent expected-subcomponent state from `materialized_qty` to `removed_qty`.
- Parent component detail now exposes `To Tray` and `To Stock` child actions.
- Parent component detail now shows detached expected child components in a `Removed Expected Child Components` section.
- Verification:
- `docker compose exec app php artisan optimize:clear`
- testing preflight: `APP_ENV=testing`, `DB_CONNECTION=sqlite`, `DB_DATABASE=/var/www/html/database/database.sqlite`
- `docker compose exec app php artisan test --env=testing tests/Feature/Components/Domain/ComponentChildDetachmentTest.php tests/Feature/Components/Ui/ShowComponentTest.php` passed with `16` tests and `110` assertions.
- `docker compose exec app php artisan test --env=testing tests/Feature/Assets/Ui/ComponentHistoryTest.php tests/Feature/Components/Ui/ComponentWorkflowPagesTest.php tests/Feature/Components/Ui/ComponentBrowserWorkflowTest.php tests/Feature/Components/Domain/ComponentHierarchyFoundationTest.php tests/Feature/Components/Domain/ComponentExpectedSubcomponentMaterializationTest.php tests/Feature/Settings/ComponentDefinitionSettingsTest.php` passed with `37` tests and `230` assertions.
- `docker compose exec app php artisan view:cache` passed.
- Review notes:
- no schema migration was needed.
- detach notes remain optional.
- Sprint 6 remains responsible for moving currently attached children with a parent move.

## Sprint 6 Parent Move Carries Attached Children
- Updated `ComponentLifecycleService::installIntoAsset()` so moving a top-level parent component to another asset carries currently attached installed children.
- Attached children keep `parent_component_instance_id` and update `current_asset_id`/`root_asset_id` to the destination asset.
- Detached tray/stock children remain detached and do not move with the old parent.
- Parent movement events now include `moved_child_component_ids` and `moved_child_count`.
- Each moved child gets its own `moved_with_parent` event pointing back to the parent component and parent movement event.
- Verification:
- `docker compose exec app php artisan optimize:clear`
- testing preflight: `APP_ENV=testing`, `DB_CONNECTION=sqlite`, `DB_DATABASE=/var/www/html/database/database.sqlite`
- `docker compose exec app php artisan test --env=testing tests/Feature/Components/Domain/ComponentParentMoveCascadeTest.php tests/Feature/Components/Domain/ComponentChildDetachmentTest.php` passed with `10` tests and `53` assertions.
- `docker compose exec app php artisan test --env=testing tests/Feature/Assets/Ui/ComponentHistoryTest.php tests/Feature/Components/Ui/ComponentWorkflowPagesTest.php tests/Feature/Components/Ui/ComponentBrowserWorkflowTest.php tests/Feature/Components/Ui/ShowComponentTest.php tests/Feature/Components/Domain/ComponentHierarchyFoundationTest.php tests/Feature/Components/Domain/ComponentExpectedSubcomponentMaterializationTest.php tests/Feature/Settings/ComponentDefinitionSettingsTest.php` passed with `47` tests and `307` assertions.
- `docker compose exec app php artisan view:cache` passed.
- Review notes:
- no schema migration was needed.
- selected event strategy is both parent summary and individual child movement events.
- no live inherited-history mechanism was introduced.

## Sprint 7 Split Placement From Condition
- Implemented `database/migrations/2026_05_07_150000_add_component_lifecycle_and_condition_statuses.php`.
- Added `component_instances.lifecycle_status` for placement and `component_instances.condition_status` for physical/attention state.
- Backfilled existing `status` values into lifecycle/condition:
- `installed` -> lifecycle `attached`
- `in_transfer` -> lifecycle `in_tray`
- `in_stock`, `needs_verification`, and `defective` -> lifecycle `in_stock`
- `needs_verification` -> condition `needs_attention`
- `defective` and poor/broken condition codes -> condition `damaged`
- Kept legacy `status` and `condition_code` as compatibility/history fields.
- Updated `ComponentInstance` with lifecycle/condition constants, labels, mapping helpers, effective status helpers, and normalization on save.
- Updated lifecycle service behavior:
- flagging needs verification now sets condition `needs_attention` without detaching attached components.
- marking defective now sets condition `damaged` without detaching attached/tray/stock components.
- moving to stock with needs-verification now results in stock placement plus needs-attention condition.
- damaged or needs-attention stock/tray components can still be installed.
- destroyed lifecycle states remain terminal for normal install/attach.
- Updated component detail and related workflow surfaces to display lifecycle and condition separately where useful.
- Updated API transformer/filter/create support for `lifecycle_status` and `condition_status`.
- Added `tests/Feature/Components/Domain/ComponentLifecycleConditionSplitTest.php`.
- Verification:
- `docker compose exec app php artisan optimize:clear` passed.
- testing preflight resolved to `APP_ENV=testing`, `DB_CONNECTION=sqlite`, `DB_DATABASE=/var/www/html/database/database.sqlite`.
- focused Sprint 7 suite passed with `31` tests and `209` assertions.
- broader hierarchy/component regression passed with `67` tests and `416` assertions.
- `docker compose exec app php artisan view:cache` passed.
- `docker compose exec app php artisan migrate --force` applied the Sprint 7 migration to local dev MySQL.
- final `docker compose exec app php artisan optimize:clear` passed.
- `git diff --check` passed with line-ending warnings only.
- Review notes:
- no new blockers were found.
- legacy `status` compatibility is currently durable, not temporary removal.
- warning confirmations for damaged/needs-attention install or selling-state transitions remain Sprint 8.

## Sprint 8 Install/Attach Warning Flow
- Finalized the shared install/attach condition warning guard around `ComponentLifecycleService::installIntoAsset()`.
- Damaged and needs-attention components now require explicit confirmation before normal install/attach proceeds.
- Confirmed damaged or needs-attention install/attach still succeeds and preserves the component condition.
- Sold/returned components now require explicit lifecycle warning confirmation before normal install/attach proceeds.
- Confirmed sold/returned install/attach succeeds and moves the component back to attached placement.
- Destroyed and destruction-pending lifecycle states remain hard-blocked for normal install/attach, even if warning confirmation flags are present.
- Web confirmation is exposed as checkboxes on:
- component install workflow pages
- asset add/install existing-component picker
- asset add new-component creation/install form
- asset tracked-component transfer workflow
- expected top-level component transfer workflow
- component-detail expected-subcomponent `Track` forms
- API install now accepts `condition_warning_confirmed` and `lifecycle_warning_confirmed`; missing confirmation for an affected component returns a structured warning response.
- Asset-page new component registration is wrapped in a transaction so a missing confirmation does not leave behind a loose newly-created component.
- Expected component and expected subcomponent materialization now pass through the same condition-warning policy; these start as `Needs Attention` until verified.
- Verification:
- `docker compose exec app php artisan optimize:clear` passed.
- testing preflight resolved to `APP_ENV=testing` and sqlite database.
- focused Sprint 8 suite passed with `28` tests and `202` assertions after the sold/returned correction.
- broader hierarchy/component regression passed with `83` tests and `509` assertions.
- `docker compose exec app php artisan view:cache` passed.
- Review notes:
- no new blockers were found.
- selected web and API behavior together rather than web-only first.
- selected explicit checkbox/boolean confirmation rather than a modal or second POST.
- sold/returned was corrected from hard-blocked to warning-confirmed installable; destroyed remains the hard lock.
- selling-state warnings remain Sprint 9 and were not implemented here.
- warning-confirmation notes remain optional.
