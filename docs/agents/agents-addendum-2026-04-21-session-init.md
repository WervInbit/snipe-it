# Addendum (2026-04-21 Codex)

## Scope Of This Handoff
- This addendum is the current handoff for the replacement `Components` stream, including the later work-order and portal tranche.
- It is intended to let the next contributor resume from repo docs alone without reconstructing the branch from chat history.
- It reflects the branch state after the browser lifecycle/tray tranche and the follow-up manual-testing fixes.

## Current Product State
- The old pooled `components` model is no longer the active product direction on this branch.
- Operational `Components` now means tracked physical component instances with lineage, lifecycle state, and uploads/history.
- Internal work orders and a read-only authenticated customer portal are implemented on top of that new component/event model.
- The feature set is no longer just a backend foundation; the browser now has a usable operator flow for intake, tray, install, stock, verification, destruction, and delete.

## Implemented So Far

### Phase 1: Foundation
- New domain foundation exists for:
- `ComponentDefinition`
- `ComponentInstance`
- `ComponentEvent`
- `ComponentStorageLocation`
- `ModelNumberComponentTemplate`
- `WorkOrder`
- `WorkOrderAsset`
- `WorkOrderTask`
- The new event model is the traceability source of truth.
- Component tags are enforced to be globally unique across both component tags and asset tags.
- Component QR/label groundwork exists through the QR label service.

### Phase 2: Registry And Detail Replacement
- `/components` is instance-based, not pooled-stock based.
- Component detail pages show tracked instance data, uploads, and history from the new model.
- Component detail now supports browser lifecycle actions.
- The component-detail install picker was changed from AJAX asset search to a server-rendered asset dropdown after manual testing showed the original selectlist was not loading reliably.

### Phase 3: Asset Integration
- Asset `Components` tab is no longer read-only for the new module.
- The asset page shows:
- installed tracked components
- event-driven component history
- expected/default components from templates
- Asset history is assembled from `component_events`, not current relations alone.
- Soft-deleted component instances are still represented in asset history views.

### Phase 4: Browser Lifecycle And Tray Workspace
- Manual loose-component intake exists in the browser under `components.create`.
- Tray workspace exists under `components.tray`.
- Browser actions exist for:
- extract to tray
- remove to tray
- install into asset
- move to stock
- flag needs verification
- confirm verification
- mark destruction pending
- mark destroyed
- delete when not installed
- Asset pages expose operational component actions instead of only passive history.
- Tray badge/count is surfaced in the main layout.

### Phase 5: Tray Aging / Verification
- Tray aging command exists and is scheduled.
- Stale tray state is surfaced in the tray workflow and component state.
- Verification workflow is implemented in the browser and lifecycle model.
- This is not yet a full notification/reminder system; the current behavior is operational state plus scheduled aging, not a complete operator alerting product.

### Phase 6: Internal Work Orders
- Internal work-order routes, list/create/show/edit flows, asset linking, task management, and component activity rendering are implemented.
- Work orders can exist without a linked asset.
- Work-order activity already surfaces linked component events.
- Work orders are currently consumers of component activity, not yet the primary place where installs/removals are executed.

### Phase 7: Read-Only Customer Portal
- Authenticated account-area portal screens exist for work orders.
- Visibility uses normal users, optional company linkage, and explicit work-order access.
- Portal is read-only and intentionally narrower than the internal UI.

## Locked Planning Decisions Already Reflected In Code
- Do not preserve legacy pooled component semantics.
- Do not preserve component checkin/checkout behavior.
- Keep the user-facing name `Components`.
- Definitions are catalog records, not company-scoped inventory records.
- Scope/visibility belongs on tracked instances, work orders, and portal access rules, not on component definitions.
- Component tags and asset tags must never overlap.
- Serial tracking is deferred and hidden from admin UI until it is either properly enforced or intentionally removed.
- `verification` replaced the stronger `quarantine` wording as the active workflow language/default destination.
- The current portal/customer visibility model does not introduce a new customer/account entity yet.

## Remaining Gaps

### Still Needs Implementation
- Model-number/template management is not yet a complete operator/admin workflow on model-number screens.
- Work-order-driven component actions are still deferred:
- work orders show component activity
- work orders are not yet the action hub for remove/install/extract flows
- Component QR/mobile scan workflow is still only groundwork:
- QR generation exists
- there is no dedicated component scan journey for operators yet
- Portal is MVP-only:
- no comments
- no uploads
- no approvals
- no richer customer-side interactions

### Still Needs Design Decisions
- What the final model-number template management UX should look like on model-number create/edit/detail surfaces.
- Whether serial tracking should be fully implemented as a definition-driven rule or retired.
- How component actions should be embedded into work-order/task flows without duplicating lifecycle state.
- What the future customer/account visibility model should be once the fork moves beyond the current normal-user plus optional-company approach.
- What the component QR/mobile scan journey should be:
- scan target routes
- tray/install workflows
- label printing/operator expectations

### Still Needs Verification
- Full-project regression has not been run.
- Broader asset UI regression still has an older unrelated failure surface outside the new component feature area and needs triage before repo-wide stability can be claimed.
- Manual browser QA is still needed on:
- `/components`
- `/components/create`
- `/components/tray`
- `/components/{id}`
- asset `Components` tab
- `/work-orders`
- `/account/work-orders`
- admin settings for component definitions and storage locations

## Verification Completed
- Targeted Phase 4/settings verification passed:
- `php artisan test tests/Feature/Components/Ui tests/Feature/Settings/ComponentDefinitionSettingsTest.php --env=testing`
- result: `21` tests, `82` assertions
- Targeted work-order/asset-history verification passed:
- `php artisan test tests/Feature/WorkOrders tests/Feature/Assets/Ui/ComponentHistoryTest.php --env=testing`
- result: `16` tests, `69` assertions
- Focused component-detail regression after the install-picker fix passed:
- `php artisan test tests/Feature/Components/Ui/ShowComponentTest.php --env=testing`

## Recommended Next Tranche
- Do not reopen the core component data model unless a concrete bug forces it.
- The cleanest next tranche is:
- work-order-driven component action UX
- wider regression and asset-suite triage after those workflows settle

## Handoff Notes For The Next Contributor
- Read `AGENTS.md`, `PROGRESS.md`, `docs/fork-notes.md`, this addendum, and the updated component/work-order plan before making changes.
- Treat the main plan file as partially historical: the top-level plan direction is still useful, but several phases are now already implemented.
- If manual testing reports a broken selector or screen, prefer verifying whether it is a stale container/runtime issue before assuming the feature model is wrong; this branch already hit that problem during the component-detail asset picker work.

## Practical Notes For A Cold-Start Agent
- The component/work-order/portal stream is ahead of wider repo stability. Do not assume a broader asset/UI failure means the new component replacement is wrong.
- Start verification with the targeted component/work-order suites first, then widen outward only after those pass.
- After Docker restarts or stack recreation, verify that the local dev database actually has the latest migrations before debugging feature errors.
- After container rebuilds/restarts, verify Composer dev dependencies are present in the app container before assuming PHPUnit is broken in code.
- If CSS disappears after a restart, verify the effective container `APP_URL` and clear Laravel caches before chasing frontend/build issues.
- `php artisan optimize:clear` has been required multiple times on this branch to make runtime state line up with code on disk.
- Do not accidentally undo these branch-level product decisions:
- `Components` means tracked physical instances, not pooled stock.
- component tags and asset tags must remain globally unique.
- component definitions are global catalog records, not company-scoped records.
- serial tracking is intentionally deferred and hidden for now.
- work orders currently consume component activity; they are not yet the place where lifecycle actions are executed.
- the component-detail install picker is intentionally server-rendered because the AJAX asset picker proved unreliable in manual testing.
- The next clean tranche should focus on workflow/UI rather than more schema churn:
- work-order-driven component actions
- wider regression cleanup after those workflows land
- If starting cold on another device, read in this order:
- `AGENTS.md`
- `PROGRESS.md`
- this addendum
- `docs/plans/components-replacement-part-traceability-work-orders.md`

## 2026-04-21 Continuation: Gap-Closure Tranche
- The follow-up gap-closure tranche from the handoff above is now implemented.
- Closed items from the prior handoff:
- internal work-order FMCS hardening is in place, including scoped internal list/show and scoped work-order asset/task write validation for company-bound staff.
- standalone model-number expected-component management now exists on dedicated model-number routes/screens with create, update, delete, and reorder support.
- component QR/mobile scan groundwork is now wired into the existing scan page via a resolver route that opens component detail pages for `CMP:{qr_uid}` scans.
- asset `Components` tabs now keep Expected Components collapsed by default behind an explicit expand action.
- tray-aging history now distinguishes automatic aging escalation from manual verification flags.
- Additional hardening completed in the same tranche:
- the generic component API update endpoint is metadata-only and rejects direct lifecycle-field edits.
- tray-holder ownership is enforced for install paths, and public remove-to-tray no longer accepts arbitrary `held_by_user_id` reassignment.
- fixed the exception-handler alias for `WorkOrder` route-model binding misses so scoped/missing internal work orders no longer fall into a 500.
- Explicitly still deferred after this tranche:
- work-order-driven component lifecycle actions remain out of scope; work orders still consume activity rather than executing installs/removals directly.
- tray aging is still escalation-only; there is still no email/reminder UI expansion in this tranche.
- Focused verification completed after implementation:
- `tests/Feature/WorkOrders/Ui/WorkOrdersControllerTest.php`
- `tests/Feature/WorkOrders/Portal/PortalWorkOrdersTest.php`
- `tests/Feature/Components/Ui/ComponentBrowserWorkflowTest.php`
- `tests/Feature/Components/Api/ComponentLifecycleApiTest.php`
- `tests/Feature/Components/Console/AgeTrayComponentsTest.php`
- `tests/Feature/Assets/Ui/ComponentHistoryTest.php`
- `tests/Feature/Models/ModelNumberManagementTest.php`
- `tests/Feature/Models/ModelNumberComponentTemplateManagementTest.php`
- `tests/Feature/Scan/ScanResolverTest.php`
- result: `35` tests passed, `168` assertions

## 2026-04-21 Continuation: Attribute Simplification And Component-Driven Specs
- Implemented the follow-up spec-system tranche that unifies shared attributes and component-derived specs.
- Attribute admin changes:
- removed user-facing version workflow from the normal attribute admin UI
- datatype stays immutable after create
- keys are now editable in place
- in-use attributes now show an impact warning instead of pushing admins into a successor/version flow
- enum option value edits now propagate to current rows that reference the same option id across:
- `model_number_attributes`
- `asset_attribute_overrides`
- `component_definition_attributes`
- historical `test_results.expected_value` and `expected_raw_value` are intentionally untouched
- Component-definition changes:
- added `component_definition_attributes` as the shared-attribute contribution table/model
- component-definition create/edit now supports `Attribute Contributions` backed by the existing attribute normalization pipeline
- this keeps one shared attribute vocabulary instead of introducing a parallel component-only attribute registry
- Model-number/spec workflow changes:
- expected components are no longer a separate primary workflow screen in normal use
- the model-number specification page now contains:
- manual attributes
- expected components
- effective specification preview
- legacy expected-component routes still exist for compatibility but redirect to the unified specification screen anchor
- Effective resolver changes:
- model-number preview now aggregates linked expected-component templates
- asset effective specs now resolve with precedence:
- asset override
- installed-component-derived value
- manual model value
- expected-component-derived model value
- asset detail/spec UIs now surface provenance labels for manual model values, expected components, installed components, and asset overrides
- Remaining intentional deferrals after this tranche:
- work-order-driven component lifecycle actions are still deferred
- full-project regression was not run
- wider search/report denormalization for component-derived specs is still out of scope; the resolver remains runtime-driven
- Focused verification completed after implementation:
- `tests/Feature/AttributeDefinitionLifecycleTest.php`
- `tests/Feature/Settings/ComponentDefinitionSettingsTest.php`
- `tests/Feature/Models/ModelNumberManagementTest.php`
- `tests/Feature/Models/ModelNumberComponentTemplateManagementTest.php`
- `tests/Feature/Models/ModelSpecificationComponentPreviewTest.php`
- `tests/Feature/ComponentDerivedAttributeResolutionTest.php`
- `tests/Feature/AttributeTestRunGenerationTest.php`
- `tests/Feature/Assets/Ui/ComponentHistoryTest.php`
- `tests/Feature/Components/Ui/ComponentBrowserWorkflowTest.php`
- result: `38` tests passed, `171` assertions

## 2026-04-21 Continuation: Model Spec Error Visibility Follow-Up
- Follow-up manual-testing feedback reported a generic Dutch save banner (`Fout: Controleer het onderstaande formulier op fouten`) while editing the unified model-number specification screen.
- The root UX problem was that `models/spec` only built its detailed error navigator for `attributes.*` inputs; validation affecting expected-component rows was largely invisible beyond the generic banner.
- Fixed the unified spec screen so expected-component validation errors now:
- appear in the top error navigator with row/field labels
- render inline on the affected component row fields
- allow direct jump/focus from the navigator to the row
- This was an error-visibility/UI correction, not a schema change.
- Focused verification after the follow-up:
- `tests/Feature/Models/ModelSpecificationComponentPreviewTest.php`
- `tests/Feature/Models/ModelNumberManagementTest.php`
- result: `11` tests passed, `40` assertions

## 2026-04-21 Continuation: Component Definition Attribute Picker UX
- Follow-up UX work aligned the component-definition contribution rows with the newer model-spec editor behavior.
- Replaced the plain contribution attribute dropdown with a quicksearch/autocomplete picker that still posts the selected `attribute_definition_id`.
- Replaced the free-text contribution value field with datatype-aware controls/hints driven by the selected shared attribute definition:
- yes/no select for bool
- numeric inputs for int/decimal, including constraint hints
- enum autocomplete + option guidance matching the model-spec field behavior
- Added validation hardening so a typed-but-unselected attribute row now returns `attribute_contributions.{row}.attribute_definition_id` instead of being ignored, and contribution normalization errors now map back to the row-level `value` field.
- Focused verification after the UX hardening:
- `tests/Feature/Settings/ComponentDefinitionSettingsTest.php`
- result: `8` tests passed, `32` assertions

## 2026-04-21 Continuation: Contribution Picker Interaction Fix
- Manual retest found a remaining picker interaction issue on the component-definition contribution rows: typing an attribute name could auto-select on blur, but after clearing that field, clicking a search result did not reliably reapply the attribute.
- Hardened the front-end interaction by applying result selection on `mousedown` instead of waiting for `click`, and added `search` event handling so the browser's native search-input clear action resets the hidden attribute id/value state correctly.
- Focused verification after the interaction fix:
- `tests/Feature/Settings/ComponentDefinitionSettingsTest.php`
- result: `8` tests passed, `32` assertions

## 2026-04-21 Continuation: Fixed Enum Controls
- Manual retest on `ram_type` exposed a broader UX problem: fixed enum attributes on the model-spec and component-definition screens were still rendered as text inputs with `datalist`, which behaves like a suggestion list rather than a stable dropdown after a value has already been chosen.
- Replaced the fixed-option enum renderer with real `<select>` controls on:
- model-number specification attribute details
- component-definition attribute contribution rows, including the client-side re-render path after selecting an attribute
- Left custom-value enums on the text+suggestion flow; the change only applies where admins are supposed to choose from a fixed option list.
- Focused verification after the enum control update:
- `tests/Feature/Settings/ComponentDefinitionSettingsTest.php`
- `tests/Feature/Models/ModelSpecificationComponentPreviewTest.php`
- result: `12` tests passed, `52` assertions

## 2026-04-21 Continuation: Model Number Expected Components Simplification
- Follow-up UI cleanup on the unified model-number specification screen:
- moved the `Expected Components` editor block above the `Effective Specification Preview`
- removed `Slot Name` and `Notes` from the model-number expected-component editor rows
- updated both the unified model-spec save path and the compatibility expected-component controller so `slot_name` and `notes` are now nulled on save instead of surviving from older records
- Updated the explanatory copy so the expected-component section now correctly references the preview below it.
- Focused verification after the simplification:
- `tests/Feature/Models/ModelSpecificationComponentPreviewTest.php`
- `tests/Feature/Models/ModelNumberComponentTemplateManagementTest.php`
- `tests/Feature/Models/ModelNumberManagementTest.php`
- result: `14` tests passed, `62` assertions

## 2026-04-21 Continuation: Expected Component Definition-Backed Simplification
- Follow-up UX cleanup on the unified model-number specification screen:
- removed the `Required by default` field because expected components created there are now implicitly required
- removed the `Expected Name` field because the saved template name is now derived from the selected catalog component definition
- kept Up/Down controls and added drag-handle row reordering for expected components
- Updated both the unified model-spec save path and the compatibility expected-component controller so those rows now persist `expected_name` from the selected `component_definition` and force `is_required = true`.
- Focused verification after the definition-backed simplification:
- `tests/Feature/Models/ModelSpecificationComponentPreviewTest.php`
- `tests/Feature/Models/ModelNumberComponentTemplateManagementTest.php`
- `tests/Feature/Models/ModelNumberManagementTest.php`
- result: `14` tests passed, `65` assertions

## 2026-04-21 Continuation: Expected Component Row Layout Cleanup
- Final follow-up on the unified model-number expected-components editor:
- moved the drag handle into the row body, directly to the left of the catalog definition field, rather than keeping it in a separate header band
- removed the old auto-seeded blank row behavior when a model number has no expected components yet
- added a clear empty-state message; admins now add the first row explicitly with `Add Expected Component`
- Focused verification after the row layout cleanup:
- `tests/Feature/Models/ModelSpecificationComponentPreviewTest.php`
- `tests/Feature/Models/ModelNumberComponentTemplateManagementTest.php`
- `tests/Feature/Models/ModelNumberManagementTest.php`
- result: `15` tests passed, `68` assertions

## 2026-04-21 Continuation: Hardware Specification Provenance Cleanup
- Removed redundant plain-model provenance chrome from the hardware details specification list:
- rows whose effective source is the manual model spec no longer show a `Manual model value` badge or a duplicated `Contributors: Manual model value` line
- component-derived rows still show contributor summaries, and override rows still show inherited-baseline context
- Added a focused regression in `tests/Feature/Assets/AssetSpecificationOverrideTest.php` to keep the hardware details page quiet for manual-model-only assets.
- Focused verification after the provenance cleanup:
- `tests/Feature/Assets/AssetSpecificationOverrideTest.php --filter=hide_redundant_manual_model_meta`
- `tests/Feature/Assets/Ui/ComponentHistoryTest.php`
- result: `3` tests passed, `14` assertions
