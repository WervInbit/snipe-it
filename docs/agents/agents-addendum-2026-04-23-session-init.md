# Agent Addendum (2026-04-23 Session Init)

## Context
- Re-read `AGENTS.md`, `PROGRESS.md`, and `docs/fork-notes.md`.
- Auditing the interrupted expected-baseline component redesign against the current repository state before resuming verification work.

## Focus
- Confirm what is implemented for:
- expected baseline component state per asset
- list-first asset component UX and dedicated add/install workflows
- direct asset-to-asset transfer via QR/manual destination
- numeric component-derived spec replacement and reduced-baseline notices
- Identify gaps, partial areas, and surfaces still using older component workflow semantics.

## Audit Notes
- The expected-baseline redesign is present in the tree, not lost:
- `AssetExpectedComponentState`, `AssetExpectedComponentService`, and `AssetComponentRosterService` implement baseline depletion, materialization, and row classification.
- The asset Components tab has been converted to the new list-first roster with dedicated add/install, storage, and transfer pages.
- The direct move workflow uses the scan resolver and manual asset fallback.
- Numeric component-derived values are wired into the effective resolver and reduced-baseline warnings are rendered on asset/detail surfaces.
- Remaining inconsistencies are mainly around stale UI/tests:
- `resources/views/models/spec.blade.php` still shows older copy indicating manual model values override derived totals.
- `tests/Feature/ComponentDerivedAttributeResolutionTest.php` and `tests/Feature/Models/ModelSpecificationComponentPreviewTest.php` still assert the older precedence/copy.
- Older tray/detail pages still expose the previous component workflow layout and have not been brought fully in line with the new asset-tab action model.

## Continuation Outcome
- Implemented the follow-up redesign work:
- model-number preview now renders numeric component-derived values as the effective value and no longer shows the old manual-override copy
- asset add/install now uses a single `New` form with an explicit definition/custom toggle
- tray and component detail pages now launch dedicated workflow screens instead of embedding lifecycle forms inline
- added GET workflow pages/routes for component install, to-tray, to-storage, verification, and destruction actions, with return-to redirects back to tray/detail screens
- fixed a Blade parse error on `hardware.show` caused by an inline `@php(...)` component-tab counter assignment
- patched the expected-baseline migration so sqlite test databases no longer fail on the MySQL-only `update ... join`
- Focused verification passed:
- `tests/Feature/ComponentDerivedAttributeResolutionTest.php`
- `tests/Feature/Components/Ui/ComponentWorkflowPagesTest.php`
- `tests/Feature/Components/Ui/ShowComponentTest.php`
- `tests/Feature/Models/ModelSpecificationComponentPreviewTest.php`
- `tests/Feature/Assets/Ui/ComponentHistoryTest.php`
- `13` tests passed, `73` assertions

## Follow-up Simplification
- Removed the model-number effective specification preview block entirely; expected components remain editable there, but the page no longer renders the derived preview table.
- Simplified the asset add/install page again:
- tray + storage installs are now one searchable `Install` picker with tray options rendered before storage options
- the picker no longer asks for install notes or installed-as/slot metadata
- the `New Component` form is hidden by default behind an explicit reveal button
- the new-component form no longer shows source type, condition, or installed-as/slot fields; new components default to manual source and unknown condition
- Kept the older tray/storage install POST routes as compatibility wrappers that now share the same simplified install behavior.
- Focused verification for this follow-up passed:
- `tests/Feature/Models/ModelSpecificationComponentPreviewTest.php`
- `tests/Feature/Components/Ui/ComponentWorkflowPagesTest.php`
- `tests/Feature/Components/Ui/ComponentBrowserWorkflowTest.php`
- `11` tests passed, `79` assertions

## Roster Split + Notes
- Refined the asset component roster presentation so non-default current components appear first:
- `Extra` / `Custom` rows now render above a slim `Expected baseline` separator
- expected rows stay grouped below that separator instead of mixing with tracked deviations
- Added a lightweight note-edit path directly on the component detail page:
- notes are now editable in place on `components.show`
- `ComponentsController::update()` now persists note-only updates instead of redirecting to the old “editing UI not implemented” stub
- Focused verification for this follow-up passed:
- `tests/Feature/Assets/Ui/ComponentHistoryTest.php`
- `tests/Feature/Components/Ui/ShowComponentTest.php`
- `tests/Feature/Components/Ui/ComponentWorkflowPagesTest.php`
- `9` tests passed, `56` assertions

## Breakdown + Slot Cleanup
- Clarified calculated numeric component specs on hardware/detail surfaces:
- calculated values now render as `Expected/default subtotal` plus `Extras/custom subtotal`
- calculated contributor summaries are split the same way so totals like `48 GB` show how much comes from expected baseline versus tracked deviations
- locked the chosen baseline semantics in coverage:
- matching tracked installed components stay `Extra` until expected baseline quantity is explicitly reduced
- once baseline is reduced, matching tracked components fill as `Expected (Tracked)`
- removed the remaining live `installed_as` / slot fields from the generic component install screen and the asset transfer screens
- removed `Installed As` display from the asset component roster and component detail page
- deleted the unreferenced legacy `resources/views/components/partials/actions.blade.php` partial
- updated the stale asset-override manager assertion to the current validation key shape used by `ModelAttributeManager`
- Focused verification for this follow-up passed:
- `tests/Feature/ComponentDerivedAttributeResolutionTest.php`
- `tests/Feature/Assets/Ui/ComponentHistoryTest.php`
- `tests/Feature/Components/Ui/ComponentWorkflowPagesTest.php`
- `tests/Feature/Components/Ui/ShowComponentTest.php`
- `tests/Feature/Components/Ui/ComponentBrowserWorkflowTest.php`
- `tests/Feature/Assets/AssetSpecificationOverrideTest.php`
- `22` tests passed, `132` assertions

## Stock Split + Removed Rows
- Separated stock-state changes from storage-location selection:
- asset and generic `To Storage` screens now move parts into stock without prompting for a location first
- the POST handlers still accept optional legacy location ids for compatibility, but the web screens no longer render those inputs
- added a storage-location editor on component detail so loose parts can be shelved later after they are already in stock
- changed the source-asset roster for expected-baseline parts:
- materialized expected components that have left the source asset now stay visible there as greyed `Removed` rows with only an `Open` action
- removed the old `Expected baseline reduced` alert from the asset component tab now that removed rows carry that context directly
- made follow-up detail work more discoverable on the component page:
- humanized the displayed component status label
- moved the file upload section above history and added helper copy so uploads are easier to find
- Focused verification for this follow-up passed:
- `tests/Feature/Components/Ui/ComponentWorkflowPagesTest.php`
- `tests/Feature/Components/Ui/ComponentBrowserWorkflowTest.php`
- `tests/Feature/Components/Ui/ShowComponentTest.php`
- `tests/Feature/Assets/Ui/ComponentHistoryTest.php`
- `18` tests passed, `138` assertions

## Asset Storage Modal
- Adjusted the asset component-tab storage UX again:
- `To Storage` on the asset page now opens a shared `Move To Stock` modal directly on the asset page instead of navigating to a separate confirmation screen
- the modal keeps the verification checkbox and note field inline and posts to the same tracked/expected stock-move endpoints used before
- generic component/tray storage workflow pages were left intact for non-asset flows
- Focused verification for this follow-up passed:
- `tests/Feature/Components/Ui/ComponentWorkflowPagesTest.php`
- `tests/Feature/Assets/Ui/ComponentHistoryTest.php`
- `9` tests passed, `78` assertions

## Component Detail Tray Modal
- Matched the component detail page to the newer inline workflow style:
- installed components on `components.show` now open a `Move To Tray` modal with the note field inline instead of sending users to the standalone remove-to-tray page first
- the modal still posts to the existing remove-to-tray endpoint, so the lifecycle and history trail stay unchanged
- Focused verification for this follow-up passed:
- `tests/Feature/Components/Ui/ShowComponentTest.php`
- `5` tests passed, `26` assertions

## Component Status Dropdown
- Converted component-detail status controls toward the asset-style model:
- `components.show` now uses a `Change Status` dropdown with modal-backed transitions instead of exposing each loose-state lifecycle as a separate button
- added a dedicated `Status History` table on the component detail page, driven by existing `from_status` / `to_status` event data
- added a direct `Defective` transition for loose components and loosened `Needs Verification -> In Stock` so it no longer requires choosing a storage location first
- tightened the detail-page install path so `Defective` and `Destruction Pending` components no longer show/install through that route
- Focused verification for this follow-up passed:
- `tests/Feature/Components/Ui/ShowComponentTest.php`
- `tests/Feature/Components/Ui/ComponentBrowserWorkflowTest.php`
- `11` tests passed, `80` assertions

- Follow-up polish:
- the closed status dropdown button on component detail now shows the current component status directly instead of the generic `Change Status` label
- Focused verification for this follow-up passed:
- `tests/Feature/Components/Ui/ShowComponentTest.php`
- `5` tests passed, `34` assertions

- Additional alignment pass:
- replaced the component-detail bootstrap status menu with a select-style control so the visible interaction is closer to the asset-side status selectors used elsewhere in the app
- the select still opens the same confirmation modals for each transition, so note/confirmation handling did not move into raw direct-post status changes
- Focused verification for this follow-up passed:
- `tests/Feature/Components/Ui/ShowComponentTest.php`
- `tests/Feature/Components/Ui/ComponentBrowserWorkflowTest.php`
- `11` tests passed, `80` assertions

## Removed Row Button Polish
- refined the removed-row styling on the asset Components tab so only the descriptive cells are muted; the `Open` action now stays visually normal instead of being dimmed with the rest of the row
- added a focused regression assertion to the removed-row asset-tab coverage so the old row-wide opacity styling does not creep back in
- Focused verification for this follow-up passed:
- `tests/Feature/Assets/Ui/ComponentHistoryTest.php`
- `5` tests passed, `23` assertions

## Asset Row Detail Links
- linked tracked component names and tags on the asset Components tab to the component detail page whenever the viewer can open that component
- extended that linking to greyed `Removed` rows as well, so historical/materialized rows no longer require using only the trailing `Open` button
- added focused coverage for both active tracked rows and removed rows
- Focused verification for this follow-up passed:
- `tests/Feature/Assets/Ui/ComponentHistoryTest.php`
- `6` tests passed, `28` assertions

## Expected Row Definition Links
- linked expected/default component names on the asset Components tab to the component-definition editor whenever the row has a catalog definition and the viewer can manage that definition
- left freeform/non-definition expected rows as plain text, since there is no separate tracked component or definition detail page to open for those
- added focused coverage for the expected-row link rendering
- Focused verification for this follow-up passed:
- `tests/Feature/Assets/Ui/ComponentHistoryTest.php`
- `7` tests passed, `30` assertions

## Component History Asset Links
- made `From asset:` and `To asset:` entries clickable on the component detail history table when the viewer can open those assets
- kept the rendering narrow to the existing component-detail history details; this did not change the separate asset-page component history layout
- added focused coverage for the linked from/to asset details
- Focused verification for this follow-up passed:
- `tests/Feature/Components/Ui/ShowComponentTest.php`
- `6` tests passed, `37` assertions
