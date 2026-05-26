# Agents Addendum - 2026-05-19 Session Init

## Startup Context
- Re-read `AGENTS.md`, `PROGRESS.md`, `docs/fork-notes.md`, `docs/plans/component-hierarchy-sprint-implementation-plan.md`, and relevant warning-policy sections of `docs/plans/component-hierarchy-subcomponents-plan.md`.
- Current branch at initialization: `codex/component-hierarchy-sprints`.
- Current commit at initialization: `4ad83dd3d`.
- Current date/time context: 2026-05-19, Europe/Amsterdam.

## Repository State
- The working tree was already dirty at initialization.
- Dirty changes include the uncommitted component hierarchy sprint implementation from 2026-05-07.
- Older local environment and prod-clone artifacts are also still present, including Docker config changes, upload placeholder `.gitignore` files, prod-clone env files, `prodbak/`, and `storage/tmp-testtypes-reorder.js`.
- No unrelated changes were reverted, normalized, or stashed during initialization.

## Plan Status
- `docs/plans/component-hierarchy-sprint-implementation-plan.md` documents Sprints 0 through 8 as implemented.
- The next planned increment is Sprint 9: `Selling-State Warnings`.
- Remaining planned increments after Sprint 9 are instance-level attributes, hierarchy-aware spec resolution, asset component tree UI, definition/model preview polish, conversion preview, optional approved conversion write mode, and final documentation/regression.

## Sprint 9 Starting Point
- Sprint 9 must warn when an asset is moved into ready-for-sale, selling, or sold states while currently attached damaged or needs-attention components or subcomponents remain attached.
- The warning must not hard-block the transition after explicit confirmation.
- Detached, tray, stock, destroyed, or otherwise non-attached parts should not create current attached-part warnings.
- Top-level attached components and attached child components both need to be considered.
- Review choice remains open before implementation: apply warnings to web status forms only first, or include API status changes in the same sprint.
- Notes on confirmed warnings remain optional unless the user explicitly changes that policy.

## Carry-Forward Safety Notes
- The local environment has previously used the production-key clone against `snipeit_prod_work`; destructive database commands remain forbidden without explicit current-message approval and DB preflight output.
- Before PHPUnit in Docker, clear Laravel config/cache with `docker compose exec app php artisan optimize:clear`.
- Verify testing config resolves to the isolated sqlite testing database before running focused suites.
- Preserve existing dirty worktree changes unless the user explicitly asks to clean or commit them.

## Reinitialization Outcome
- No feature implementation was started during this reinitialization.
- No tests were run during this reinitialization.
- Ready to investigate Sprint 9 status-transition surfaces before making code changes.

## Broad Investigation Notes
- `rg` could not be used in this workspace because `rg.exe` returns `Access denied`; searches were done with PowerShell instead.
- Sprint 9 should hook into the existing web warning pattern used for failed tests before ready-for-sale/sold status changes.
- Primary web mutation surfaces are `AssetsController::updateStatus`, `AssetsController::update`, and `BulkAssetsController::update`.
- `AssetsController::toggleSaleAvailability` only toggles `is_sellable`; include it in Sprint 9 only if "setting to selling states" is intended to include the sellable flag in addition to status labels.
- API asset update support is possible but should be a review decision: `Api\AssetsController::update` currently saves directly and the model-level `DomainException` path is not shaped into a structured API warning response.
- The existing failed-test warning flag is `ack_failed_tests`; component issue warnings need their own confirmation flag to avoid collapsing separate warnings into one acknowledgement.
- Attached issue detection should inspect currently attached top-level components and attached child components. Detached, tray, stock, destroyed, destruction-pending, or current-asset-null rows should not trigger a current attached-part warning.
- `ComponentLifecycleService` now allows damaged and needs-attention installs after confirmation, and allows sold/returned install after lifecycle confirmation. Destroyed and destruction-pending remain blocked from normal install.
- `AssetComponentRosterService`, `ComponentAttributeAggregator`, and `EffectiveAttributeResolver` still aggregate flat roster rows. Attached child components will currently be counted and displayed as ordinary asset-level rows until hierarchy-aware roster/spec work is added.
- No component instance attribute persistence exists yet. Sprint 10 starts from a clean schema/service surface.
- Tests already present for the implemented hierarchy work include parent-child persistence, expected child materialization, child detach ancestry, parent move cascade, lifecycle/condition split, API install warnings, and UI/domain install warnings.

## Sprint 9 Outcome
- Implemented web selling-state warnings for attached damaged or needs-attention components.
- Covered hardware detail status updates, hardware edit status updates, bulk status updates, and the available-for-sale toggle.
- Confirmation uses `ack_component_issues`; API asset status changes remain a later review choice.
- Added `tests/Feature/Assets/Ui/SellingStateComponentWarningTest.php`.
- Focused verification passed with `5` Sprint 9 tests and `39` assertions.
- Related ready-for-sale failed-test warning regression passed after updating its stale test fixture.

## Sprint 10 Outcome
- Implemented component instance attributes using the service/API-first review path.
- Added `component_instance_attributes`, `ComponentInstanceAttribute`, and relations from `ComponentInstance` and `AttributeDefinition`.
- Added `ComponentInstanceAttributeManager` for normalized sync, duplicate prevention, shared datatype validation, replacement/deletion semantics, and inherited spec-resolution behavior when overriding a definition attribute.
- API component create/update accepts `instance_attributes`; omitted payload keeps existing rows and an empty array clears them.
- Component API responses now include `instance_attributes`.
- Component-derived spec aggregation now prefers instance attributes over definition attributes for the same component row and still falls back to definition attributes when no instance override exists.
- Custom component instances and custom child instances can carry structured attributes through the existing flat roster path; Sprint 11 still owns hierarchy-aware precedence and attached-tree filtering.
- Attribute option value propagation, usage summaries, and delete safeguards now include component instance attribute rows.
- Component detail UI editing remains a later review choice.
- Focused verification passed: `docker compose exec app php artisan test --env=testing tests/Unit/Services/ComponentInstanceAttributeManagerTest.php tests/Feature/Components/Api/ComponentLifecycleApiTest.php tests/Feature/ComponentDerivedAttributeResolutionTest.php tests/Feature/AttributeDefinitionLifecycleTest.php` returned `27` passing tests and `90` assertions.

## Sprint 11 Outcome
- Implemented hierarchy-aware calculated asset spec resolution.
- Attached top-level components and attached child components contribute to current calculated specs; detached, tray, stock, destroyed, and current-asset-null parts are filtered out.
- Attached child component values suppress parent component values for the same calculated attribute, preserving the lower-level value while keeping the parent as overlap-warning metadata.
- Damaged-but-attached child components still contribute and remain visible to the attached-issue warning service.
- Attached child components are classified as extra/custom in the asset-level roster rather than satisfying top-level expected component slots.
- Kept generic specification rendering and added parent/child overlap warnings to hardware detail and hardware edit specification areas.
- Focused verification passed: `docker compose exec app php artisan test --env=testing tests/Feature/ComponentDerivedAttributeResolutionTest.php` returned `12` passing tests and `43` assertions.
- Related regression verification passed: `docker compose exec app php artisan test --env=testing tests/Unit/Services/ComponentInstanceAttributeManagerTest.php tests/Feature/Components/Api/ComponentLifecycleApiTest.php tests/Feature/Assets/Ui/SellingStateComponentWarningTest.php tests/Feature/ComponentDerivedAttributeResolutionTest.php` returned `26` passing tests and `109` assertions.
- `docker compose exec app php artisan view:cache` passed.

## Sprint 12 Outcome
- Implemented the asset Components tab hierarchy tree.
- Top-level roster rows remain primary; attached child components render directly below the current parent row when that parent is present on the asset.
- Expected child templates render under parent rows with expected/tracked/removed/remaining quantity detail.
- Removed expected child components render under the parent by using the detached-child ancestry snapshot.
- Damaged and needs-attention rows now show inline issue badges on the asset Components tab.
- Existing `Expected`, `Expected (Tracked)`, `Extra`, `Custom`, and `Removed` classifications remain unchanged; child rows add indentation and child-context text.
- Implemented child rows expanded by default, matching the Sprint 12 validation requirement that child, expected, and removed rows are visible.
- Focused verification passed: `docker compose exec app php artisan test --env=testing tests/Feature/Assets/Ui/ComponentHistoryTest.php --filter=AssetComponentsTabRendersHierarchy` returned `1` passing test and `13` assertions.
- Existing asset component tab regression passed: `docker compose exec app php artisan test --env=testing tests/Feature/Assets/Ui/ComponentHistoryTest.php` returned `8` passing tests and `43` assertions.
- Related hierarchy/spec regression passed: `docker compose exec app php artisan test --env=testing tests/Feature/Components/Ui/ShowComponentTest.php tests/Feature/Components/Domain/ComponentExpectedSubcomponentMaterializationTest.php tests/Feature/ComponentDerivedAttributeResolutionTest.php` returned `25` passing tests and `143` assertions.
- `git diff --check` passed with line-ending warnings only.

## Sprint 13 Outcome
- Implemented component definition and model-number preview polish.
- Added `ComponentDefinitionHierarchyWarningService` to compute advisory overlap warnings when a parent definition and one of its expected child definitions both contribute the same numeric calculated spec.
- Component definition edit pages now show hierarchy overlap warnings near the expected subcomponent editor.
- Model-number specification rows now preview expected child structure for selected component definitions, including child/freeform label, quantity, part code, and links to component definition editors when permitted.
- Model-number specification rows also show the same overlap warnings for selected component definitions.
- Implemented the approved preview/link path only; no inline nested expected-subcomponent editor was added to model-number specification pages.
- Overlap warnings remain non-blocking and do not prevent definition saves, model-number expected component editing, install/attach flows, or selling-state transitions.
- Focused Sprint 13 verification passed: `docker compose exec app php artisan test --env=testing tests/Feature/Models/ModelSpecificationComponentPreviewTest.php tests/Feature/Settings/ComponentDefinitionSettingsTest.php --filter='expected_component_child_preview|HierarchyOverlapWarning'` returned `2` passing tests and `20` assertions.
- Adjacent model/spec regression passed: `docker compose exec app php artisan test --env=testing tests/Feature/Models/ModelSpecificationComponentPreviewTest.php tests/Feature/Settings/ComponentDefinitionSettingsTest.php` returned `18` passing tests and `96` assertions.
- Related spec resolver regression passed: `docker compose exec app php artisan test --env=testing tests/Feature/ComponentDerivedAttributeResolutionTest.php` returned `12` passing tests and `43` assertions.
- Broader model-spec UI verification initially surfaced a stale CSRF setup in `ModelSpecificationUiTest`; the fixture was updated to disable `VerifyCsrfToken` for its mutating test requests, matching the neighboring model component-template test pattern.
- Broader model-spec UI verification then passed: `docker compose exec app php artisan test --env=testing tests/Feature/Models/ModelSpecificationUiTest.php tests/Feature/Models/ModelNumberComponentTemplateManagementTest.php` returned `4` passing tests and `26` assertions.

## Sprint 14 Outcome
- Implemented the read-only component hierarchy conversion preview.
- Added `ComponentHierarchyConversionPreviewService` to scan component definitions, model-number expected-component templates, existing expected-subcomponent templates, and numeric calculated-spec overlap evidence.
- Added `component-hierarchy:preview-conversion` with table output for manual review and `--json` for full report output.
- Detection is intentionally conservative:
  - candidate parents require existing expected-child templates or top-level model-number usage plus parent/assembly naming evidence.
  - candidate children include definitions already used as children, `subcomponent_only` definitions, and definitions matching embedded/serviceable child naming.
  - suggested templates are emitted only when a candidate parent and candidate child co-occur as top-level expected components on the same model number.
  - existing parent/child templates are not suggested again.
- Numeric calculated spec overlaps are reported for suggested templates and existing expected-subcomponent templates.
- The command does not include any write mode; Sprint 15 remains the explicit review gate for any optional write-mode conversion.
- Focused Sprint 14 verification passed after cache clear and testing DB preflight (`testing|sqlite|/var/www/html/database/database.sqlite`): `docker compose exec app php artisan test --env=testing tests/Feature/Components/Console/ComponentHierarchyConversionPreviewTest.php` returned `3` passing tests and `21` assertions.
- Related Sprint 13 overlap-warning regression passed: `docker compose exec app php artisan test --env=testing tests/Feature/Models/ModelSpecificationComponentPreviewTest.php tests/Feature/Settings/ComponentDefinitionSettingsTest.php --filter='expected_component_child_preview|HierarchyOverlapWarning'` returned `2` passing tests and `20` assertions.
- Local clone validation ran against `local|mysql|snipeit_prod_work`; the preview scanned `2` active component definitions and `0` model-number component templates, so it emitted no conversion suggestions from that clone.
- Manual no-write check around the local clone command kept `component_definitions|component_definition_subcomponent_templates|model_number_component_templates` counts unchanged at `2|1|0`.

## Sprint 15 Outcome
- Implemented selected-pair conversion write tooling.
- Added `ComponentHierarchyConversionApplyService` and `component-hierarchy:apply-conversion`.
- The command requires explicit `--pair=parent_definition_id:child_definition_id` selections and never applies all preview suggestions automatically.
- The command defaults to dry-run; no database writes occur unless `--apply` is passed.
- Apply mode only creates selected pairs that are still current preview suggestions. Existing templates, stale suggestions, pairs without same-model-number evidence, and filtered inactive pairs are rejected as unavailable.
- Created templates store provenance in `metadata_json`, including source model-number evidence, confidence, reasons, and `applied_at`.
- Apply output includes created template IDs and a rollback tinker example for deleting those exact `component_definition_subcomponent_templates` rows before dependent expected-subcomponent states are created.
- Focused Sprint 15 verification passed after cache clear and testing DB preflight (`testing|sqlite|/var/www/html/database/database.sqlite`): `docker compose exec app php artisan test --env=testing tests/Feature/Components/Console/ComponentHierarchyConversionPreviewTest.php` returned `6` passing tests and `35` assertions.
- Local clone validation remained dry-run only against `local|mysql|snipeit_prod_work`. Because the clone has `0` current preview suggestions, `component-hierarchy:apply-conversion --pair=2:1` reported the pair unavailable, created `0` templates, and before/after counts for `component_definitions|component_definition_subcomponent_templates|model_number_component_templates` stayed `2|1|0`.
- Focused conversion/settings/spec regression passed: `docker compose exec app php artisan test --env=testing tests/Feature/Components/Console/ComponentHierarchyConversionPreviewTest.php tests/Feature/Settings/ComponentDefinitionSettingsTest.php tests/Feature/ComponentDerivedAttributeResolutionTest.php` returned `31` passing tests and `143` assertions.

## Sprint 16 Outcome
- Completed documentation and regression wrap-up for the hierarchy sprint plan.
- Added `docs/component-hierarchy-operations.md` as the operator/admin reference for the completed asset/component/subcomponent hierarchy.
- The new operations doc covers admin setup, model-number preview behavior, operator workflows, damaged/needs-attention warning policy, destroyed-component locking, spec precedence, conversion commands, and current limits.
- Updated `docs/fork-notes.md`, `docs/plans/component-hierarchy-sprint-implementation-plan.md`, and `PROGRESS.md` for Sprint 16 completion.
- Corrected stale adjacent coverage in `ComponentCompanyScopingTest`: component definitions no longer have `company_id`, so the test fixture now verifies instance company fallback without writing the removed definition column.
- Focused hierarchy/domain/spec/conversion verification passed after cache clear and testing DB preflight (`testing|sqlite|/var/www/html/database/database.sqlite`): `55` tests and `246` assertions.
- Focused UI/API/model/settings hierarchy verification passed: `68` tests and `470` assertions.
- Broader adjacent component registry/file/company-scope plus work-order verification passed: `45` tests and `186` assertions.
- No additional conversion apply command was run against `snipeit_prod_work`; local clone write-mode remains unexercised outside PHPUnit's isolated test database.
