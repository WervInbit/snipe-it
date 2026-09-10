# Owner Review And Action List - 2026-09-10

## Owner Clearance For User Review - 2026-09-10

The owner cleared the ten current changed versions in the v5 pair for review
by other users. They are now Internal review candidates; the exact PDFs stay
unchanged. Use the [dated clearance and hashes](owner-review-readiness-2026-09-10.md)
and current-guide-selection-v4.json for current status. Earlier four exact
acceptances remain; the separate prototype is outside this clearance.
Other-user feedback and third-party approval are still pending. Earlier
generation-time draft/pending wording below is historical for these versions.

## Implementation Update

The original planning notes below are retained as history. The owner then
authorized implementation. MR-01 through MR-07, MR-09's guide redesign,
MR-10 and MR-11 are implemented in the [new candidates](owner-corrections-2026-09-10.md).
MR-13's desk audit and learning-card order are implemented; practical novice
and interrupted-form exercises remain. USR-01/02 also have new reference titles.

MR-08 preference: **Testing completed**, without another **Awaiting approval**
state. App status, transition rules and live evidence remain open.
MR-12 now has a separate two-page prototype. Matched asset/component detail
captures, a verified replacement result and its permanent guide placement remain
open. Account reactivation/deletion/restoration coverage also remains separate.
See [remaining prerequisite work](prerequisite-followups-2026-09-10.md).

Status: recorded owner feedback and implementation plan; no guide PDF changes
or new application statuses in this pass.

## Review Decision

The owner considers the guides generally good enough as working drafts, with
the exceptions below. **USR-04 v5 is explicitly not accepted and needs a scope
and flow redesign.** Earlier exact acceptance of AC-01 v9, AC-02 v4, AST-03 v15
and AST-04 v6 remains recorded. The AST-04 status idea is a future consideration,
not a withdrawal of v6 acceptance or authorization to add an application status.
The general draft assessment does not identify further exact-version approvals.

The [dated machine-readable decision](../../../../resources/manuals/operator-guides/review-rounds/2026-09-10/owner-feedback-2026-09-10.json)
records the unchanged USR-04 v5 hash and its explicit non-acceptance separately
from the preserved generation-time manifests.

Review source: the current half of the plain v2 comparison pair, recorded in
[current set review](current-set-review-2026-09-10.md). Preserve both bundles,
their manifests, all individual PDFs and the frozen baseline. No silent rollback
to USR-04 v3: its overlapping lifecycle scope also needs reconsideration.

## Local Corrections Ready For The Next Version

| ID | Current guide | Change | Verification and pitfall |
| --- | --- | --- | --- |
| MR-01 | SC-01 v11, step 2 / 2A | Move the image badge or adjust local spacing so the circle clears the instruction above the image. | Verify the full badge boundary against every line of instruction at final A4 size. Preserve the photo, caption, help and current asymmetric layout; checking the badge centre alone misses this defect. |
| MR-02 | AST-02 v7, alternative routes | Replace plain CMP-01, CMP-02, CMP-04, WF-02, HELP-01 and SC-01 handoffs with the shared family color, marker, code and title reference. | Fit references beside explicit actions and return points. Expand/reflow this local area if necessary; do not shrink the type or remove useful routes. Plain codes must not become an unexplained row of buttons. |
| MR-03 | WF-01 v12, step 1 | Render its SC-01 reference with the shared complete guide styling. | Keep profile selection at 2A, starting at 3A, the existing choice block, screenshots and help. Check that the larger reference does not push 1A into text. |
| MR-04 | CMP-02 v5, step 2 / 2A and 2B | Remeasure both radio centres against the actual replacement screenshots and reposition the focus circles. | Inspect both final cropped/scaled images. Reusing old coordinates after a recapture is insufficient; keep circles off the option label and identify the intended radio unambiguously. |
| MR-05 | CMP-04 v6, step 1 / 1B | Expand/reposition the crop to include the complete Naar tray button, its label and a small context margin. | CMP-04 was intentionally unchanged in the prior pass. Use the existing source if it contains the whole button; recapture only if the source itself is incomplete. Check the focus outline does not cover the label or clip the button. |
| MR-06 | CAT-00 v10, part 1 | Attach the green Asset-to-registered-component arrow to the target box. | Derive the endpoint from the actual box boundary; inspect the arrowhead and all other connectors, not just whether a path exists. |
| MR-07 | CAT-01 v6, page 2 | Replace the compressed A/B/C page-routing footer with readable named destinations tied to each selected route. | Keep the existing three legitimate alternatives. Proposed wording: A - exact code already exists: page 5, step 7; B - base model exists, code missing: page 4, step 5; C - base model missing: page 3, step 4. Replace the misleading generic Volgende pagina label with a route-specific handoff; verify each target. |

These local fixes do not require new operational policy. Proposed next integers,
subject to checking for newly created versions before generation: SC-01 v12,
AST-02 v8, WF-01 v13, CMP-02 v6, CMP-04 v7, CAT-00 v11 and CAT-01 v7.
Keep each existing filename stem. No versions are generated or accepted here.

## Changes Requiring A Content Or Process Decision

### MR-08 - AST-04: Testing Complete Versus QA Hold

- [ ] Reconsider whether testing-complete/awaiting-review needs a separate
  recognizable state from blocked work, missing accessories, cosmetic damage
  or a failed test. This is the owner's first idea, deliberately deferred.
- [ ] Define what makes testing complete, whether every result must pass,
  who owns the next action, and which physical location matches each state.
- [ ] Decide whether completion is represented by workflow results plus a clear
  handoff explanation, a renamed status, or an additional lifecycle status.

Evidence: AST-04 v6 step 3 and its caption use QA Hold for awaiting supervisor
review. [ProductionStatusLabelSeeder.php](../../../../database/seeders/ProductionStatusLabelSeeder.php)
describes QA Hold as blocked until accessories/cosmetics are ready, while Ready
for Sale means fully tested and ready for sale. The local canonical status map
has no separate Testing Complete label; this is code evidence, not an inspection
of operator-owned live labels. Existing labels can be deliberately renamed.

Advice: distinguish completed testing from sale approval and from a blockage.
Do not publish a guide-only status that operators cannot select. A real status
change can affect AST-02, AST-04, AST-05, dashboards/queues, filters, translations,
screenshots and operational ownership. Benefits are clearer handoff and triage;
costs are an extra state and transition that people must maintain consistently.

### MR-09 - USR-04: One Task And No Check-In Instructions

- [ ] Remove check-in instructions from the replacement USR-04, as requested.
- [ ] Remove the top delete/restore OR banner and the mixed numbered lifecycle
  route; do not only delete the banner while leaving an ambiguous sequence.
- [ ] Recommend that USR-04 own disabling login only: identify account, open
  edit, turn login off, save, verify the saved state and retained identity.
- [ ] Decide where re-enabling a disabled account, restoring a deleted record,
  and deleting a record belong. These are different tasks and outcomes.
- [ ] Keep ownership/entitlement cleanup in separately scoped administration
  instructions when applicable, rather than making it a prerequisite for the
  basic stop-login guide. Verify actual role/control/session behaviour during
  the redesign; do not promise token/session revocation from the checkbox alone.

The prior guide grew from a combined account-offboarding/lifecycle scope and
retained that scope during the layout-preservation pass. That explains the
three outcomes but does not justify keeping them in the rejected design.

Application evidence: [V1 retirement record](../../../v1-release-readiness-status-2026-07-23.md)
records removal/rejection of legacy asset checkout/check-in mutation flows.
However, [licence routes](../../../../routes/web/licenses.php) and
[accessory routes](../../../../routes/web/accessories.php) still expose their own
check-ins. [User detail](../../../../resources/views/users/view.blade.php) still
contains these controls and management/assignment tabs.
[DeleteUserRequest](../../../../app/Http/Requests/DeleteUserRequest.php) blocks
deletion with assigned assets/licences/accessories or managed users/locations.
[UsersController](../../../../app/Http/Controllers/Users/UsersController.php)
separately updates activation and restores deleted records.

Therefore remove check-in work from this guide without claiming every such
capability or deletion dependency has vanished from the application. Do not
invent an asset check-in route to resolve a historical deletion blocker.
Use separate task guides if deletion/restoration are retained in the manual set.
Benefits: one clear start and finish, less accidental traversal into deletion.
Costs: additional guides/handoffs and an explicit decision about lifecycle scope.
USR-05 is already reserved for Groups; do not reuse its code for these tasks.

### MR-10 - CAT-00: Show How Many And What Is Reused

- [ ] Add plain-language quantity labels to the relationship map: an exact
  model number can be used by multiple assets, an asset can have multiple
  component records/attributes, and definitions can contribute several values.
- [ ] Verify every minimum, maximum, optional relationship and nested component
  case before choosing 1, 1 or more, or none/one/more.
- [ ] Distinguish expected component definitions/quantities from installed
  component records, and direct attributes from values supplied by components.

Do not label every arrow 1 or more: a newly created model number can have no
assets yet, a model specification can have no component rows yet, and a tray
component is not installed on an asset. Some physical components can contain
subcomponents; a custom component may not use a reusable definition.

Local evidence: [ModelNumber](../../../../app/Models/ModelNumber.php) relates
separately to assets, direct attributes and expected component templates;
[ModelSpecificationRequest](../../../../app/Http/Requests/ModelSpecificationRequest.php)
makes those lists optional but requires a positive quantity for a supplied
expected quantity. [ComponentDefinition](../../../../app/Models/ComponentDefinition.php)
and [ComponentInstance](../../../../app/Models/ComponentInstance.php) distinguish
reusable contributions, expected parts, instance attributes and physical children.

Advice: use phrases such as kan meerdere ... hebben, with none-yet explained
where useful, rather than database notation. Benefit: users understand reuse
and multiplicity. Pitfall: incorrect minimums teach nonexistent prerequisites;
many extra arrows/labels can also make an overview harder to follow.

### MR-11 - CAT-01 Page 4: Explain Or Relocate Asset Deviations

- [ ] Replace the vague Wijkt een asset af warning with a concrete distinction.
  Example proposal: a later change from 8 GB to 16 GB RAM on one device is a
  change to that physical device; its printed manufacturer code does not change.
- [ ] Keep only the identity warning beside model-number creation. Put the
  procedure for recording actual installed parts/specification overrides with
  the appropriate asset/component task and give a precise return/handoff.
- [ ] Verify the chosen example against the actual model-spec calculation and
  component flow before including it. Do not invent a missing guide destination.

Pitfalls: telling the operator to change a shared model baseline for one device,
or entering RAM both directly and through a component so it is counted twice.
A different genuine manufacturer code can still require a different model
number. Content placement can be revised locally after this distinction is agreed.

## Larger Catalogue Follow-Up - Keep Separate From Local Repairs

### MR-12 - Input-To-Result Visual Map

- [ ] Prototype one consistent worked example showing where entered information
  appears on model specification, component definition/detail, and asset detail.
- [ ] Visually connect selected input fields with their saved displayed result.
  Show shared/model values, component-derived values and one-asset differences
  distinctly; reuse the same identity and values throughout the example.
- [ ] Start with a small number of fields and a readable spread or companion
  reference. Decide its home only after reviewing the prototype: CAT-00,
  an added reference page, or relevant task pages.

This is an explicitly deferred reconsideration item, not authorization for a
whole-family redesign. It may change CAT-00/01/02/03/04 and AST/CMP guidance.
Benefits: operators can connect data entry with a visible outcome. Costs:
more pages, maintained evidence, and possible cross-guide rewrites. Pitfalls:
crossing lines, tiny screenshots, mismatched examples, or implying that a label
field automatically generates calculated specifications.

### MR-13 - Learning Order And Missing Prerequisites

- [ ] Review the catalogue learning order so definitions are understood before
  they are used, while reusing existing definitions during everyday work.
- [ ] Test a proposed learning order of overview -> attributes -> component
  definitions -> model/model number -> model specification -> physical asset.
  In codes: CAT-00 -> CAT-03 -> CAT-04 -> CAT-01 -> CAT-02 -> AST-03.
- [ ] Keep ordinary execution conditional: reuse existing records; create only
  missing definitions. A learning order must not force unnecessary creation.
- [ ] Walk all guides from a novice's starting point and list other concepts,
  permissions, records, guide dependencies and return points introduced late.
  Include USR-05/CAT-05/CAT-06 references, interrupted forms and unsaved input.

Advice: reorder presentation/learning routes before considering code renumbering.
Stable IDs preserve references and prevent the naming confusion the owner raised.
Benefit: fewer unexplained prerequisites. Cost: changing the reading route and
possibly several transitions. Pitfall: a strict creation sequence can cause
duplicate definitions or overwhelm people who only need to reuse existing data.

## Delivery Order And Checks

1. Make MR-01 through MR-07 as focused new guide versions, retaining screenshots,
   full useful page width, hints, completion/help and established task layouts.
2. Agree USR-04's single-task scope and CAT-01's deviation wording; investigate
   exact relationship minimums for CAT-00. Do not block unrelated visual fixes.
3. Keep the QA status idea and catalogue visual-map/order work as explicit
   decision/prototype items. Do not quietly apply a broad redesign.
4. Check full badge/control/caption bounds, target alignment on actual source
   pixels, complete controls after cropping, styled references everywhere, and
   connected diagram endpoints. Trace every branch to a named destination and
   a clear completion state. Preserve same-example evidence where applicable.
5. Deliver newly numbered individual PDFs and, when requested, the next plain
   previous/current pair. Both bundles retain guide order, but changed guide
   page counts may change later start pages; document that in the page map.

Validation of this feedback pass: inspected the eleven relevant current PDF
pages for SC-01, AST-02, AST-04, WF-01, CMP-02, CMP-04, both USR-04 pages,
CAT-00 part 1 and CAT-01 pages 2/4; checked the cited local application paths.
No live environment, new screenshots, application data, PDF source bytes,
renderers or operational statuses were changed. Prior merge pixel equality
proves preservation of source pages, not that all source layouts/flows are correct.
