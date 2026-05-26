# Component Hierarchy Sprint Implementation Plan

## Purpose
This document is the review-gated implementation plan for adding one hierarchy level to the current tracked component system.

The target hierarchy is:
- asset
- component
- subcomponent

No deeper nesting is allowed.

This plan implements the intent from `docs/plans/component-hierarchy-subcomponents-plan.md`, but breaks it into smaller sprint-sized features. Each sprint must leave the application in a validatable state and must stop for review before the next sprint starts.

## Operating Rule
Do not silently expand scope.

At the end of each sprint, report:
- what changed
- which tests passed or failed
- what new information was uncovered
- whether the original plan still fits
- specific choices for the user when a design adjustment is possible

Do not automatically apply new design choices without user approval. Discoveries become review options, not hidden scope changes.

## Implementation Progress
As of 2026-05-19, Sprints 0 through 16 have been implemented in this branch.

Completed increments:
- Sprint 1 persisted one-level parent/child component hierarchy and root asset tracking.
- Sprint 2 added expected subcomponent templates on component definitions.
- Sprint 3 rendered attached and expected child structure on component detail.
- Sprint 4 materialized expected subcomponents as tracked child component instances.
- Sprint 5 detached child components to tray or stock with closed ancestry snapshots.
- Sprint 6 moved attached children with the parent component.
- Sprint 7 split placement from condition with `lifecycle_status` and `condition_status`.
- Sprint 8 added warning-and-confirmation handling for installing or attaching damaged, needs-attention, and sold/returned components.
- Sprint 9 added warning-and-confirmation handling for web selling-state transitions when damaged or needs-attention components remain attached.
- Sprint 10 added component instance attributes with service/API sync and same-level override behavior for calculated component-derived specs.
- Sprint 11 made asset calculated specs hierarchy-aware, so attached child component values suppress parent values for the same attribute while detached/non-attached parts do not contribute.
- Sprint 12 made the asset Components tab hierarchy-aware, rendering attached children, expected child templates, removed expected children, and issue badges under parent component rows.
- Sprint 13 added definition/model-number preview polish: model-number spec rows preview expected child structure, definition-backed rows link to the component definition editor, and definition/model edit surfaces show non-blocking hierarchy overlap warnings.
- Sprint 14 added a read-only conversion preview command/report for production-like data, including candidate parent/child definition detection, suggested expected-subcomponent templates, and overlap-warning evidence without writes.
- Sprint 15 added selected-pair conversion tooling with dry-run output by default, explicit `--apply` writes only for reviewed parent/child definition pairs, and rollback guidance for created templates.
- Sprint 16 added the operator/admin hierarchy reference and completed focused hierarchy/component/spec plus broader component/work-order regression verification.

No sprint increments remain in this plan. The "Current Starting Point" below is retained as the historical baseline from when this plan was created, not the current implementation state.

## Current Starting Point
The current implemented system is a flat component system:
- assets have tracked components through `component_instances.current_asset_id`
- model numbers define expected components through `model_number_component_templates`
- per-asset expected baseline depletion is stored in `asset_expected_component_states`
- component definitions contribute attribute/spec values through `component_definition_attributes`
- asset specs aggregate flat roster rows through `ComponentAttributeAggregator`
- component lifecycle flows are implemented through `ComponentLifecycleService`

Subcomponents are not implemented yet.

Known current mismatch:
- current `status` mixes placement and attention/condition
- current statuses include `installed`, `in_stock`, `in_transfer`, `needs_verification`, `defective`, `destruction_pending`, `destroyed_recycled`, and `sold_returned`
- the target model needs placement and condition/attention to be independent concepts

## Locked Domain Decisions
These are requirements unless the user explicitly changes them.

### Structure
- Use the existing `ComponentInstance` model for both components and subcomponents.
- A component attached directly to an asset is a top-level component.
- A component attached to another component is a subcomponent.
- Do not create a separate `Subcomponent` model.
- Enforce a hard depth cap of `asset -> component -> subcomponent`.

### Expected Baseline
- Model numbers can define expected top-level components.
- Component definitions can define expected subcomponents.
- Expected components and expected subcomponents are assumed present until an explicit change happens.
- First explicit change materializes the expected item into a real tracked `ComponentInstance`.

Explicit change includes:
- note creation or update
- status/condition change
- upload
- removal/detach
- transfer
- repair/replacement action
- explicit track/materialize action if added by UI

### Movement
- Moving a parent component to another asset moves all currently attached children.
- Detached, in-tray, in-stock, destroyed, or otherwise non-attached children do not move with the parent.
- Attached custom children move with the parent.
- `root_asset_id` must remain consistent across the attached subtree.

### Condition, Damage, And Warnings
Damaged or needs-attention parts must not hard-block normal work.

Allowed after warning/confirmation:
- installing or attaching a damaged part
- installing or attaching a needs-attention part
- moving an asset into selling, sold, or ready-for-sale states while damaged or needs-attention parts remain attached

Required behavior:
- show clear warnings for affected install/attach flows
- show clear warnings for selling-state transitions with affected attached parts
- allow the user to confirm and proceed

Destroyed components are different:
- destroyed is a terminal locked state
- destroyed components must not be reinstalled or reattached through normal flows
- destruction must carry a destruction note, verification event, or equivalent audit evidence

### Specs And Attributes
- Model numbers can have direct attributes.
- Component definitions can have attributes.
- Component instances can have attributes.
- Custom components and custom subcomponents can have attributes.
- At the same hierarchy level, instance attributes override definition attributes.
- Across hierarchy levels, the lowest attached level wins.
- Damaged-but-attached parts still contribute to current physical/configuration specs.
- Detached, in-tray, in-stock, and destroyed parts do not contribute to current specs.
- Overlapping parent/child contributions should warn, not block.

## Sprint 0: Baseline Safety

### Feature
Establish a known-good starting point before hierarchy changes.

### Requirements
- Current branch, commit, dirty worktree, and environment are documented.
- Current active DB config is known before any test or migration work.
- Laravel cached config is cleared before Docker PHPUnit.
- Current focused component/spec tests are run or pre-existing blockers are documented.
- No functional code changes are made in this sprint.

### Suggested Validation
- `php artisan optimize:clear` in the app container before PHPUnit.
- Focused current suites:
  - `tests/Feature/ComponentDerivedAttributeResolutionTest.php`
  - `tests/Feature/Assets/Ui/ComponentHistoryTest.php`
  - `tests/Feature/Components/Ui/ShowComponentTest.php`
  - `tests/Feature/Components/Ui/ComponentWorkflowPagesTest.php`
  - `tests/Feature/Components/Ui/ComponentBrowserWorkflowTest.php`
  - `tests/Feature/Components/Domain/ComponentLifecycleServiceTest.php`

### Review Choices
- Continue on current branch.
- Create a feature branch.
- Clean or isolate local-only environment/data artifacts first.
- Pause if existing failures make the baseline unclear.
- Decide lifecycle/condition field naming before schema work:
  - `lifecycle_status` and `condition_status`
  - `placement_status` and `condition_status`
  - keep `status` for placement and rename/rework `condition_code`

## Sprint 1: Persist Parent/Child Components

### Feature
The database and model layer can represent one component attached below another component.

### Requirements
- Add parent attachment support to `component_instances`.
- Add `root_asset_id` to identify the asset at the root of the currently attached tree.
- Add ancestry snapshot fields needed for detached/materialized child history.
- Add `placement_mode` to `component_definitions`.
- Enforce hard depth cap in service/model validation, not only by UI.
- Existing flat asset-component behavior continues to work.
- No operator UI for subcomponents is required yet.

### Likely Schema
Add to `component_instances`:
- `parent_component_instance_id` nullable FK to `component_instances`
- `root_asset_id` nullable FK to `assets`
- `is_materialized_expected` boolean default false
- `materialized_reason` nullable string
- `ancestry_parent_component_instance_id` nullable FK to `component_instances`
- `ancestry_attached_through_at` nullable timestamp
- `ancestry_attached_through_event_id` nullable FK to `component_events`

Add to `component_definitions`:
- `placement_mode` with allowed values:
  - `asset_only`
  - `subcomponent_only`
  - `either`

### Backfill
- Existing installed components should get `root_asset_id = current_asset_id`.
- Existing flat components should have `parent_component_instance_id = null`.
- Existing definitions should default to `either` unless a better explicit default is approved.

### Suggested Validation
- Test can create `asset -> motherboard -> USB port`.
- Test rejects `asset -> component -> subcomponent -> deeper child`.
- Existing flat roster and lifecycle tests still pass.
- Existing installed components still appear on the asset Components tab.

### Review Choices
- Keep `placement_mode = either` as default.
- Use stricter default such as `asset_only`.
- Add parent context to events now or defer to Sprint 4.
- Confirm the selected lifecycle/condition naming still fits the schema before moving on.

## Sprint 2: Expected Subcomponents On Definitions

### Feature
Component definitions can define expected child parts.

### Requirements
- Add `component_definition_subcomponent_templates`.
- Component definition editor can create/update/delete/reorder expected subcomponent templates.
- Expected subcomponents can reference a child component definition or use a freeform expected name.
- Existing model-number expected-component flows stay unchanged.
- Expected subcomponents are definition-level templates, not asset-specific state yet.

### Likely Schema
Create `component_definition_subcomponent_templates`:
- `id`
- `parent_component_definition_id`
- `child_component_definition_id` nullable
- `expected_name`
- `expected_qty`
- `is_required`
- `sort_order`
- `metadata_json`
- `notes`
- timestamps

### Suggested Validation
- Component definition settings test can add expected USB ports under a motherboard definition.
- Expected child rows can be reordered.
- Deleting a template removes only the template, not tracked component instances.
- Existing `ComponentDefinitionSettingsTest` behavior still passes.
- Existing `ModelNumberComponentTemplateManagementTest` behavior still passes.

### Review Choices
- Manage expected subcomponents only on component definition pages.
- Also show read-only previews from model-number spec pages.
- Allow freeform expected child rows immediately or require catalog definitions first.

## Sprint 3: Component Detail Shows Child Structure

### Feature
Component detail page shows the child structure for one component.

### Requirements
- Parent component detail shows attached child components.
- Parent component detail shows assumed expected child rows from its component definition.
- Tracked children link to the normal component detail page.
- No separate subcomponent detail page is created.
- Operational child actions can be minimal or absent in this sprint.
- Removed/detached expected child rows are not required in this sprint because instance-level expected child state is introduced later.

### Suggested Validation
- UI test renders expected child rows under a motherboard component.
- UI test renders attached child component links.
- UI test confirms the page remains stable when no child state records exist.
- Existing component detail note/storage/status history behavior still works.

### Review Choices
- Render child rows inline under parent details.
- Render child rows in a collapsible section.
- Show expected children by default or behind a toggle.

## Sprint 4: Materialize Expected Child

### Feature
An assumed expected subcomponent can become a real tracked instance.

### Requirements
- Add `component_expected_subcomponent_states`.
- Materialization creates a `ComponentInstance`.
- Materialized child remains attached to the parent by default.
- Materialization records why it happened.
- Parent expected-child state tracks removed/materialized quantity.
- Existing top-level expected component materialization still works.

### Likely Schema
Create `component_expected_subcomponent_states`:
- `id`
- `component_instance_id`
- `component_definition_subcomponent_template_id`
- `removed_qty`
- timestamps
- unique on `component_instance_id + component_definition_subcomponent_template_id`

### Suggested Validation
- Test: note-only on expected child materializes it.
- Test: materialized child remains attached to parent.
- Test: expected child quantity is reduced correctly.
- Test: materialized child has `root_asset_id` inherited from parent.
- Test: materialized child records source template in metadata or explicit fields.

### Review Choices
- First UI materialization trigger is a note action.
- First UI materialization trigger is explicit `Track`.
- First implementation supports service/API only, UI follows later.

## Sprint 5: Detach Child To Tray Or Stock

### Feature
A child component can be removed from its parent.

### Requirements
- Detaching clears `parent_component_instance_id`.
- Detached child no longer moves with parent.
- Detached child keeps closed ancestry snapshot.
- Do not clone old parent event history onto the child.
- After detachment, the child keeps only its own future history plus the closed ancestry reference.
- Detached child can move to tray or stock.
- Parent detail keeps the removed expected child visible when it came from expected baseline.
- Existing top-level remove-to-tray and move-to-stock flows still work.

### Suggested Validation
- Test detach child to tray.
- Test detach child to stock.
- Test detached child has ancestry snapshot.
- Test parent historical events are not copied onto the detached child.
- Test detached child does not move when parent later moves.
- Test parent expected child row remains visible as removed/reduced.

### Review Choices
- Expose detach from parent detail only.
- Expose detach from child detail too.
- Require note on detach or keep optional.

## Sprint 6: Parent Move Carries Attached Children

### Feature
Moving a parent component carries its attached child subtree.

### Requirements
- Moving parent from Asset A to Asset B moves currently attached children.
- Detached children do not move.
- Children in tray, stock, destruction pending, destroyed, or otherwise not attached do not move.
- `root_asset_id` updates for the parent and all attached children.
- Events provide enough traceability for parent and child movement.
- Child history should record the move event for the child if child events are approved, but must not inherit future unrelated parent events after detachment.
- Operation is transactional.

### Suggested Validation
- Test parent transfer carries attached child.
- Test detached child remains detached.
- Test in-stock child remains in stock.
- Test root asset is updated across attached subtree.
- Test events can explain what moved.

### Review Choices
- Write individual child movement events.
- Write parent event with child summary.
- Write both individual child events and parent summary.
- Confirm no live inherited-history mechanism is being introduced.

## Sprint 7: Split Placement From Condition

### Feature
Placement and condition/attention become independent.

### Requirements
- A component can be attached and damaged.
- A component can be attached and needs attention.
- A component can be removed and damaged.
- Damaged/needs-attention does not block attach/install.
- Destroyed is locked from normal install/attach.
- Existing status values are migrated or compatibility-mapped safely.

### Likely Direction
Add or formalize:
- placement/lifecycle field for where the component is
- condition/attention field for physical or verification state

The exact names should be approved before implementation.

### Suggested Validation
- Test attached damaged component remains attached.
- Test attached needs-attention component remains attached.
- Test removed damaged component keeps damaged state.
- Test destroyed component cannot be reinstalled/reattached.
- Existing lifecycle tests are updated without losing old guarantees.

### Review Choices
- Field names:
  - `lifecycle_status` and `condition_status`
  - `placement_status` and `condition_status`
  - keep `status` for placement and rename/rework `condition_code`
- Whether warning confirmations require notes.
- Whether compatibility with old `status` values is temporary or permanent.

## Sprint 8: Install/Attach Warning Flow

### Feature
Installing or attaching affected parts warns but does not block.

### Requirements
- Installing/attaching damaged parts shows a warning.
- Installing/attaching needs-attention parts shows a warning.
- User can confirm and proceed.
- Destroyed parts are blocked from normal install/attach.
- Confirmation behavior is available wherever install/attach is exposed.

### Suggested Validation
- Test damaged component install first shows warning or requires confirmation flag.
- Test confirmed damaged install succeeds.
- Test needs-attention install warning and confirmation.
- Test destroyed install fails.

### Review Choices
- Web warning first, API later.
- Web and API warning behavior together.
- Confirmation via checkbox, modal, or explicit second POST.

### Implemented 2026-05-07
- Selected web and API warning behavior together.
- Selected explicit confirmation controls: web checkboxes and API boolean `condition_warning_confirmed`.
- `ComponentLifecycleService::installIntoAsset()` now rejects damaged or needs-attention installs unless confirmation is present.
- Confirmed damaged or needs-attention installs proceed and preserve condition state.
- Sold/returned installs proceed after explicit lifecycle warning confirmation.
- Destroyed and destruction-pending lifecycle states remain hard-blocked for normal install/attach, even when confirmation is present.
- Component install workflow, asset add/install workflow, asset transfer workflow, and expected-subcomponent `Track` materialization expose the confirmation behavior.
- API component install returns a structured warning response when confirmation is missing, and succeeds with the relevant confirmation flag.
- New asset-created components and expected materializations start as `Needs Attention`, so those attach paths also require confirmation.

### Validation Result
- Focused Sprint 8 regression passed with `28` tests and `202` assertions after the sold/returned correction.
- Broader hierarchy/component regression passed with `83` tests and `509` assertions.
- Blade compilation passed with `php artisan view:cache`.

### Review Notes
- No new blockers were found.
- Sold/returned was corrected from hard-blocked to warning-confirmed installable; destroyed remains the hard lock.
- Selling-state warnings remain Sprint 9 and were not implemented in Sprint 8.
- Notes remain optional for confirmed condition warnings.

## Sprint 9: Selling-State Warnings

### Feature
Asset selling-state transitions warn about affected attached parts.

### Requirements
- Moving asset to selling, sold, or ready-for-sale state warns if attached damaged parts exist.
- Same for attached needs-attention parts.
- Warning does not block after confirmation.
- Detached damaged/needs-attention parts do not create current attached warnings.
- Destroyed detached parts do not create current attached warnings.

### Suggested Validation
- Test ready-for-sale warning with attached damaged child.
- Test confirmation allows transition.
- Test no warning for detached damaged child.
- Test top-level attached damaged component also warns.

### Review Choices
- Apply warnings to web status forms only first.
- Apply warnings to API status changes too.
- Require note on confirmed warning or keep optional.

### Implementation Notes
- Implemented web status and sale-toggle warnings first; API asset status changes remain a later explicit choice.
- Confirmed warnings remain note-optional.
- The explicit sale toggle is included as a selling-state transition.
- The warning detector includes both top-level attached components and attached child components through `current_asset_id`.
- Detached tray, stock, destroyed, or otherwise non-attached parts are excluded from current attached-part warnings.

## Sprint 10: Instance-Level Attributes

### Feature
Individual component instances can override or add structured attributes.

### Requirements
- Add `component_instance_attributes`.
- Instance attributes can be edited or synced through service/API.
- Instance attributes override definition attributes at the same level.
- Custom components and custom subcomponents can carry structured attributes.
- Existing definition-level attributes still work.

### Likely Schema
Create `component_instance_attributes`:
- `id`
- `component_instance_id`
- `attribute_definition_id`
- `value`
- `raw_value`
- `attribute_option_id`
- `sort_order`
- timestamps

### Suggested Validation
- Test instance attribute overrides definition attribute.
- Test custom child component contributes an attribute.
- Test definition fallback remains when no instance override exists.
- Test enum/int/decimal/bool validation follows existing attribute rules.

### Review Choices
- Add component detail UI for instance attributes now.
- Service/API only first, UI later.
- Reuse model-spec/component-definition attribute editor patterns.

### Implementation Notes
- Implemented the service/API-first path; component detail UI editing remains a later explicit review choice.
- Added `component_instance_attributes` with `resolves_to_spec` so instance-only custom components can opt into calculated specs.
- Added `ComponentInstanceAttributeManager` for sync, duplicate prevention, existing attribute validation, definition-resolution inheritance for overrides, and replacement/deletion semantics.
- API component create/update accepts `instance_attributes`; omitted payload leaves rows untouched, while an empty array clears them.
- `ComponentAttributeAggregator` now uses instance attributes before component-definition attributes for the same component row.
- Definition-level component attributes continue to contribute when no instance override exists.
- Attribute option value propagation, usage summaries, and delete safeguards include component instance attribute rows.
- Current custom child contribution behavior still flows through the existing flat `Asset::trackedComponents()` roster. Sprint 11 remains responsible for hierarchy-aware precedence and detached/attached tree semantics.

## Sprint 11: Hierarchy-Aware Spec Resolver

### Feature
Asset specs resolve from attached hierarchy.

### Requirements
- Current attached top-level components contribute.
- Current attached subcomponents contribute.
- Detached, in-tray, in-stock, and destroyed parts do not contribute.
- Damaged-but-attached parts still contribute.
- At the same level, instance attributes override definition attributes.
- Across hierarchy levels, lowest attached level wins.
- Parent/child overlaps warn but do not block.
- Existing flat expected/default and extra/custom semantics continue to work.

### Suggested Validation
- Test motherboard contributes USB count when no child ports are materialized.
- Test child USB ports override parent board-level USB count.
- Test damaged attached USB port still contributes and shows issue warning.
- Test detached USB port no longer contributes.
- Test extra/custom child contribution is counted correctly.
- Existing `ComponentDerivedAttributeResolutionTest` expectations are updated intentionally.

### Review Choices
- Keep generic attribute/spec rendering for now.
- Add a dedicated connectivity summary later.
- Decide how overlap warnings are shown in UI.

### Implementation Notes
- Kept generic attribute/spec rendering and added parent/child overlap warnings to the existing hardware detail and hardware edit specification areas.
- `AssetComponentRosterService` now filters current spec rows to attached components and treats child components as extra/custom rather than satisfying asset-level expected component slots.
- `ComponentAttributeAggregator` now applies hierarchy precedence to calculated asset roster rows: for the same attribute on an attached parent and attached child, the child row is kept and the parent row is suppressed.
- Suppressed parent contributors are retained as metadata so the UI can warn that child values are being used.
- Damaged-but-attached children still contribute to current calculated specs and still trigger attached-issue warnings through the existing selling-state warning service.
- Detached, in-tray, in-stock, destroyed, and current-asset-null parts are excluded through the attached roster filter.

## Sprint 12: Asset Components Tab Tree

### Feature
Asset Components tab becomes hierarchy-aware.

### Requirements
- Top-level components remain primary rows.
- Child components render below parent rows.
- Assumed expected child rows are visible.
- Removed child rows are visible.
- Issue badges show damaged/needs-attention state.
- Existing expected/default/extra/custom behavior remains understandable.
- Operator actions remain visible but not crowded.

### Suggested Validation
- UI test renders `asset -> motherboard -> USB port`.
- UI test shows expected child rows.
- UI test shows removed child rows.
- UI test shows issue badges.
- Existing asset component tab tests still pass or are intentionally updated.

### Review Choices
- Child rows expanded by default.
- Child rows collapsed by default.
- Separate parent and child sections.

### Implementation Notes
- Implemented child rows expanded by default so the Sprint 12 validation targets are visible without adding a new collapse/toggle control.
- The asset Components tab now keeps top-level roster rows as primary rows and renders attached child component rows directly underneath their current parent when that parent is present on the asset.
- Assumed expected child rows render from the parent component definition's expected subcomponent templates, including quantity detail for expected, tracked, removed, and remaining counts.
- Removed expected child components are grouped under the parent using the existing detached-child ancestry snapshot.
- Damaged and needs-attention component rows now display inline issue badges on the asset Components tab.
- Existing `Expected`, `Expected (Tracked)`, `Extra`, `Custom`, and `Removed` classifications remain unchanged; child rows add child-context text rather than introducing new lifecycle behavior.

## Sprint 13: Component Definition And Model Preview Polish

### Feature
Definition and model-number screens explain expected child structure.

### Requirements
- Component definition edit page clearly manages expected subcomponents.
- Model-number spec page can preview nested expected structure without becoming a giant inline editor.
- Overlap warnings are visible where definitions/specs are edited.
- No unexpected blocking behavior.

### Suggested Validation
- Test model-number page shows expected component with expected child preview.
- Test component definition page shows overlap warning when parent and child contribute same attribute.
- Existing model-number spec tests still pass or are intentionally updated.

### Review Choices
- Model-number page preview only.
- Model-number page links to component definition editor.
- Add limited inline editing only if approved.

### Implementation Notes (2026-05-19)
- Implemented the model-number page as preview-only with links to the component definition editor; no inline nested expected-subcomponent editor was added.
- Model-number expected component rows now preview the selected component definition's expected child templates, including child definition name/code, freeform expected name, and quantity.
- Component definition edit pages now show non-blocking hierarchy overlap warnings when the parent definition and an expected child definition both contribute the same numeric calculated spec.
- Model-number spec pages also show those definition overlap warnings next to selected expected component definitions.
- Overlap warnings are advisory only; they do not prevent saving definitions, selecting expected components, attaching parts, or selling-state transitions.

## Sprint 14: Conversion Preview

### Feature
Safe read-only tooling for production-like data.

### Requirements
- Add preview-only command/report.
- Detect candidate parent component definitions.
- Detect candidate child/subcomponent definitions.
- Detect overlapping contributions.
- Suggest expected subcomponent templates.
- Do not write data.

### Suggested Validation
- Run against local `snipeit_prod_work`.
- Inspect report manually.
- Confirm no database writes.

### Review Choices
- Approve a specific conversion subset.
- Adjust detection rules.
- Defer write-mode conversion.

### Implementation Notes (2026-05-19)
- Added `component-hierarchy:preview-conversion` as a read-only Artisan command.
- The command supports table output for manual review and `--json` for the full report.
- Detection rules are intentionally conservative:
  - candidate parents are active definitions already used as top-level model-number expected components and matching assembly/board/display/dock/hub naming, plus definitions that already have expected children.
  - candidate children are active definitions marked `subcomponent_only`, already used as expected children, or matching embedded/serviceable child naming such as ports, readers, modules, RAM, storage, or battery.
  - suggested templates are only emitted when a candidate parent and candidate child currently co-occur as top-level expected components on the same model number.
  - existing parent/child templates are skipped as suggestions.
- Numeric calculated spec overlaps are reported for suggested templates and existing expected-subcomponent templates.
- Local `snipeit_prod_work` validation found no model-number component-template evidence yet, so no conversion suggestions were emitted from that clone during this sprint.
- Before/after local clone counts for `component_definitions`, `component_definition_subcomponent_templates`, and `model_number_component_templates` stayed unchanged at `2|1|0`.

## Sprint 15: Optional Conversion Write Mode

### Feature
Approved conversion tooling can apply selected changes.

### Requirements
- Write mode is not built or run unless explicitly approved.
- Write mode must have dry-run output.
- Write mode must target selected definitions/templates, not everything.
- Writes must be reversible or backed by clear rollback guidance.
- Production-like clone should be used first.

### Suggested Validation
- Run dry-run.
- Run against clone only.
- Compare before/after counts.
- Run focused component/spec tests afterward.

### Review Choices
- Keep conversion manual.
- Allow command write mode for approved subset.
- Add admin UI for conversion later.

### Implementation Notes (2026-05-19)
- Added `component-hierarchy:apply-conversion` as selected-pair conversion tooling.
- The command requires one or more `--pair=parent_definition_id:child_definition_id` options and never targets all suggestions automatically.
- Without `--apply`, the command is dry-run only and prints the templates that would be created.
- With `--apply`, the command creates only selected pairs that are current preview suggestions; stale, already-existing, or unsupported pairs are rejected.
- Created templates store conversion provenance in `metadata_json`, including source model-number evidence, confidence, reasons, and `applied_at`.
- Apply output includes created template IDs and a rollback tinker example for deleting those exact `component_definition_subcomponent_templates` rows before dependent expected-subcomponent states are created.
- Local `snipeit_prod_work` validation remained dry-run only. The clone has no current conversion suggestions, so a selected unavailable pair reported `0` templates to create and before/after counts stayed unchanged at `2|1|0`.

## Sprint 16: Documentation And Regression

### Feature
The completed hierarchy behavior is documented and covered.

### Requirements
- Update `docs/fork-notes.md`.
- Update `PROGRESS.md`.
- Update operator/admin docs if UI changed.
- Run focused hierarchy/component/spec tests.
- Run broader adjacent tests when feasible.
- Document skipped coverage or residual risk.

### Suggested Validation
- Full focused component hierarchy suite passes.
- Existing component UI/API/domain suites pass.
- Asset spec resolver tests pass.
- Work-order component history tests still pass.

### Review Choices
- Commit after focused tests.
- Run broader regression first.
- Defer work-order operational integration to a separate plan.

### Implementation Notes (2026-05-19)
- Added `docs/component-hierarchy-operations.md` as the operator/admin reference for hierarchy setup, model-number preview behavior, operator workflows, warning policy, spec precedence, conversion tooling, and current limits.
- Updated `docs/fork-notes.md`, `PROGRESS.md`, and the 2026-05-19 agent addendum with Sprint 16 outcomes.
- Full focused hierarchy/domain/spec/conversion verification passed: `55` tests and `246` assertions.
- Focused UI/API/model/settings hierarchy verification passed: `68` tests and `470` assertions.
- Broader adjacent component registry/file/company-scope and work-order verification passed: `45` tests and `186` assertions.
- One stale adjacent fixture was corrected during regression: `ComponentCompanyScopingTest` no longer writes the removed `component_definitions.company_id` column.
- No additional write-mode conversion was run against `snipeit_prod_work`.

## Out Of Scope Unless Approved
Do not add these during the hierarchy implementation unless the user explicitly approves them:
- arbitrary recursive BOM support beyond one subcomponent level
- a separate `Subcomponent` model
- automatic production-data conversion
- mandatory physical labels for every subcomponent
- hard blocking sale/ready/sold because damaged or needs-attention parts are attached
- automatic work-order operational action hub
- dedicated connectivity renderer beyond generic attribute/spec rendering

## Final Acceptance
The hierarchy implementation is complete when:
- an asset can have a top-level component
- that component can have expected and tracked subcomponents
- expected subcomponents can materialize into tracked instances
- parent movement carries attached children only
- detached children stop following parent movement
- damaged/needs-attention parts warn but do not block install/attach/selling-state transitions
- destroyed components are locked from normal reinstall/reattach with audit evidence
- instance attributes work and override definition attributes at the same level
- hierarchy-aware specs use currently attached parts and lowest-level precedence
- asset and component UI surfaces show the hierarchy clearly
- conversion tooling, if built, starts as preview-only
- focused tests cover the rules above
