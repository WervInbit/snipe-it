# CAT-04 v3 - Audit Comparison Proposal

Status: Audit revision round 2026-09-08 - Unaccepted working draft.
Decision: batch design direction rejected by the owner on 2026-09-08;
retain the exact PDF. Individual factual and operational proposals remain
undecided. See [owner feedback](2026-09-08-pilot-direction-feedback.md).
The changes and expected benefits below describe the rejected experiment.

- [Candidate PDF](../../../../resources/manuals/operator-guides/review-rounds/2026-09-08/CAT-04-componentdefinities-beheren-v3-draft.pdf): six A4 pages.
- SHA-256: `3b1d399440dade1107087fc8437c97d8b744687a1ff3a0b6500c3b056ae705af`.
- Compare with [frozen v2 draft](../../../../resources/manuals/operator-guides/checkpoints/2026-09-08-before-audit-revisions/drafts/CAT-04-componentdefinities-beheren-v2-draft.pdf).
  No internally accepted predecessor is recorded.
- In the [comparison PDF](../../../../resources/manuals/operator-guides/review-rounds/2026-09-08/Handleidingen-vergelijking-auditronde-2026-09-08.pdf): v2 pages 6-11, v3 pages 17-22.

## Changes And Tradeoffs

| Change | Expected benefit | Drawback or review question |
| --- | --- | --- |
| Page 1 separates reuse, Edit and Create New, with explicit page destinations. | Gives each search outcome a concrete action. | Reuse jumps to page 6, so the page handoff needs testing. |
| Page 2 explains seven identity fields separately, using larger copy (AUD-11). | Makes short field explanations readable without relying on tiny cards. | The full-form screenshot is smaller than individual field crops; users may need zoom for its native text. |
| Pages 3-4 name and show Add Expected Subcomponent and Add Attribute Contribution before field entry (AUD-13). | Fills the missing action between an empty form and a populated row. | More written instructions; this may feel repetitive to an experienced Supervisor. |
| Page 5 replaces the misleading simulated Enum warning with a labeled numeric explanation (AUD-06). | Describes the implemented numeric/resolves-to-spec boundary without claiming a fake application alert is real evidence. | Operators no longer see a real warning-state screenshot. A valid numeric warning capture remains useful before acceptance. |
| Replacement form-control evidence from 2026-09-03 is used for identity, children, contributions and save (AUD-18). | Removes the old checkbox-label collision from these views. | This does not update CAT-03 or CMP-02; their recapture follow-up remains open. |
| Page 6 reopens saved fields and routes cleanup to an Admin while identifying CAT-05 as planned (AUD-15). | Makes the result check and unavailable dependency explicit. | Cleanup still depends on an administrator; there is no executable CAT-05 procedure yet. |
| Missing-attribute recovery records unsaved input and distinguishes reopening an existing definition from re-entering a new one. | Avoids promising that an unsaved new definition can simply be reopened. | Re-entering a new form costs time and should be exercised in a user trial. |

Supervisor scope, always-Required expected rows, one-level expected structure,
and the no-tag/serial identity rule remain as in the existing process. The
typography and layout are pilot choices, not global rule changes.

## Sources And Validation

- [Pilot specification](audit-pilot-2026-09-08-specification.md) and
  [isolated generator](../../../../scripts/manuals/generate-audit-pilot-review.mjs).
- Sources: CAT-COMPONENT-DEFINITION-ENTRY-DESKTOP-01 plus IDENTITY,
  CHILDREN, CONTRIBUTIONS and SAVE variants ending DESKTOP-02. Neither
  OVERLAP-DESKTOP-01 nor OVERLAP-DESKTOP-02 is used.
- ComponentDefinitionHierarchyWarningService filters both levels to numeric
  attributes with resolves_to_spec enabled. The warning Blade partial and
  component-definition form confirm the rendered labels and add/save actions.
- The list screenshot is explicitly an existing-record illustration. No new
  save was submitted and it is not proof of a save performed during this session.
- [Validation record](../../../../resources/manuals/operator-guides/review-rounds/2026-09-08/validation.json): all six A4 pages pass text/page/component/focus
  and text/image/badge separation checks; every rendered page was inspected.
  Comparison-package rasters match. Sample instruction text measures 9.21 pt.
- Physical A4 review, valid warning recapture, first-time-user execution,
  full datatype variation and actual missing-definition recovery remain untested.

## Decision And Rollback

Review the action additions, numeric explanation, larger type and screenshot
crops separately. Existing draft v2 stays selected and no accepted policy is
superseded. Retain this v3 and the round source snapshot if rejected; later
visible corrections receive a new version.
