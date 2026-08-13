# AST-04 Werk Afronden En Overdragen

| Field | Current value |
| --- | --- |
| Status | Working draft; evidence incomplete |
| Family | AST |
| Type | Detail task |
| Current version | `AST-04-complete-handoff-v1-draft` |
| Page model | One page |
| Layout recipe | `stacked-step-flow` with `single-visual` and `inline-stop` |
| Generator | `scripts/manuals/generate-revised-guide-set.mjs` |
| Role | Senior refurbisher |
| Needed | Correct open asset and completed work evidence |
| Prerequisite | Asset verified (SC-01); workflow completed when required (WF-02) |

## Purpose

Check that operator work and recorded evidence are complete, then place the asset in the agreed review state for a supervisor.

## Steps

1. `Controleer workflow` - confirm every required item has a saved result and no required work remains open.
2. `Controleer asset en onderdelen` - compare the physical device, installed components, notes, and visible warnings.
3. `Maak klaar voor overdracht` - close the operator work using the agreed handoff action/status.
4. `Draag over` - place the device in the review location and notify the supervisor using local practice.

## Stop

- Stop in step 1 for missing or contradictory workflow results.
- Stop in step 2 when a component, serial, label, or physical condition does not match.
- Do not select a sale/release state reserved for supervisors.

## Evidence Needed

Workflow completion and asset status/location controls from a controlled record. The exact handoff label remains a release-blocking wording decision.

## Complete When

The digital record and physical device are complete, the asset is in the agreed review state/location, and a supervisor can start AST-05.

## Related Guides

- SC-01 Asset vinden en openen
- WF-02 Workflow uitvoeren en afronden
- CMP-04 Component naar tray verplaatsen
- AST-05 Asset beoordelen en vrijgeven
- HELP-01 Problemen en hulp
