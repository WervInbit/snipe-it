# AST-04 Werk Afronden En Overdragen

## QA Preference Follow-Up - 2026-09-10

The owner prefers Testing completed and no separate Awaiting approval state.
This is a future status direction; the accepted v6 PDF and current QA Hold
instructions remain byte-identical until the operational transition is defined.
See [implementation record](../reviews/owner-corrections-2026-09-10.md).

| Field | Current value |
| --- | --- |
| Status | Internal review candidate; exact v6 accepted by owner 2026-09-10 |
| Family | AST |
| Type | Detail task |
| Current version | `AST-04-complete-handoff-v6-draft` |
| Page model | One page |
| Layout recipe | `stacked-step-flow` with captions, `reused-evidence`, and `inline-warning` |
| Generator | `scripts/manuals/generate-guide-followups.mjs AST-04`; use frozen round inputs to reproduce |
| Role | Senior refurbisher |
| Needed | Verified asset and completed workflow |
| Prerequisite | Workflow completed (WF-02) |

## Purpose

Confirm that recorded and physical work is complete, then hand the asset to a
supervisor with an unmistakable next action.

The v5 draft uses the currently deployed `QA Hold` label and explicitly says it
means waiting for supervisor review. The guide also records the current
auto-save behavior and requires the operator to confirm that the status remains
visible after selection.

This is specifically the final refurbishment-to-QA handoff guide. Step 2B is
only the physical-versus-registered check for components marked `Tracked`; it
is not applicable when the asset has no tracked components. Version 5 reframes
1A and 1B, contains the 2B evidence inside its row, and uses complete styled
help references.

## Steps

1. `Bevestig dat de workflow klaar is` - confirm the correct HP ProBook, open
   Tests, inspect the latest required workflow, and confirm `0 Mislukt` with no
   required card still open.
2. `Vergelijk registratie met het apparaat` - compare tag, S/N, model type,
   QR label, registered components, and necessary notes with the physical item.
3. `Draag over aan de supervisor` - choose `QA Hold`; the change saves
   automatically. Confirm that `QA Hold` remains visible, place the asset at
   the agreed QA location, and identify AST-05 as the supervisor's next guide.

Incomplete work and mismatches are amber corrective warnings. They do not use
oversized red stop text.

## Evidence

| Label | Canonical source | Job |
| --- | --- | --- |
| 1A | `AST-ASSET-SAVED-MOBILE-01` | Correct asset identity and route to Tests |
| 1B | `AST-WORKFLOW-PASS-MOBILE-01` | Complete workflow summary row for the same controlled asset |
| 2A | `AST-LABEL-PLACEMENT-PHOTO-01` | Physical label, serial, and model-type comparison |
| 2B | `AST-COMPONENT-REVIEW-MOBILE-01` | Current registered-component state |
| 3A | `AST-QA-HANDOFF-MOBILE-01` | Current `QA Hold` selector and visible automatic result |

All application captures use the controlled `INBIT-HG0421` / `HP ProBook 450
G8` / `5CD1234ABC` identity. The exact physical QA location remains a local
operational choice; the guide names it but does not fabricate a location
photograph.

## Complete When

The workflow and physical asset are checked, the current handoff status is
saved, and the device is visibly waiting at the QA location for AST-05.

## Related Guides

- WF-02 Workflow uitvoeren en afronden
- CMP-04 Component naar tray verplaatsen
- AST-05 Asset beoordelen en vrijgeven
- HELP-01 Problemen en hulp

## Focused Candidate - 2026-09-08

The separate [v6 review](../reviews/AST-04-v6.md)
records the focused follow-up to baseline v5, with the exact
PDF and checksum. The owner requested this pass across all existing guides
after reviewing WF-01 v12 positively. Earlier selections and their policies
remain traceable; this candidate is not automatically accepted. See the
[set comparison](../reviews/guide-set-comparison-2026-09-08.md) for scope,
source/crop exceptions, and remaining operational or evidence gaps.

## Exact Acceptance - 2026-09-10

The owner accepted v6, selecting the unchanged PDF/hash in the
[v6 review](../reviews/AST-04-v6.md). The earlier candidate-pending
notes are historical. Preserve prior versions and the source checkpoint.

## Owner Feedback Follow-Up - 2026-09-10

MR-08: record the tentative testing-complete/awaiting-QA versus blocked/damaged status idea for later review. Exact v6 acceptance remains; do not invent a status in the guide.
See [owner review TODOs](../reviews/owner-review-todos-2026-09-10.md).
The existing PDF is unchanged; these are next-version or decision items.
