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
- model-number expected-component template management UI
- work-order-driven component action UX
- component QR/mobile scan journey
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
- model-number expected-component template management
- work-order-driven component actions
- component QR/mobile scan workflow
- wider regression cleanup after those workflows land
- If starting cold on another device, read in this order:
- `AGENTS.md`
- `PROGRESS.md`
- this addendum
- `docs/plans/components-replacement-part-traceability-work-orders.md`
