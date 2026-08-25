# AST-04 Werk Afronden En Overdragen

| Field | Current value |
| --- | --- |
| Status | Working draft v5; visual-correction pass and awaiting exact-version review |
| Family | AST |
| Type | Detail task |
| Current version | `AST-04-complete-handoff-v5-draft` |
| Page model | One page |
| Layout recipe | `stacked-step-flow` with captions, `reused-evidence`, and `inline-warning` |
| Generator | `scripts/manuals/generate-revised-guide-set.mjs`; use `SNIPEIT_AST04_VERSION=5` explicitly when reproducing this version |
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
