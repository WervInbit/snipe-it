# AST-05 Asset Beoordelen En Vrijgeven

| Field | Current value |
| --- | --- |
| Status | Working draft; evidence incomplete |
| Family | AST |
| Type | Detail task |
| Current version | `AST-05-review-release-v1-draft` |
| Page model | One page |
| Layout recipe | `stacked-step-flow` with `single-visual` and `inline-stop` |
| Generator | `scripts/manuals/generate-revised-guide-set.mjs` |
| Role | Supervisor |
| Needed | Asset in the agreed review state and access to release controls |
| Prerequisite | Operator handoff completed (AST-04) |

## Purpose

Review the physical asset and recorded work, resolve exceptions, and apply the approved release state only when all criteria are met.

## Steps

1. `Open overdracht` - open the correct asset and confirm it is waiting for review.
2. `Controleer bewijs` - review workflow results, notes, warnings, identity, and component state.
3. `Controleer apparaat` - compare the physical condition, label, included parts, and intended destination.
4. `Beslis` - return the asset for correction or apply the approved release state.
5. `Controleer eindstatus` - confirm the saved state and destination are visible on the asset.

## Stop

Do not release an asset with missing evidence, unresolved warnings, identity mismatch, or an incorrect component/condition state.

## Evidence Needed

Controlled screenshots of the review state, workflow summary, status/release control, and final state. Exact status labels must be confirmed against the deployed configuration before approval.

## Complete When

The supervisor decision is saved, exceptions are routed back for correction, and a released asset shows the approved final state and destination.

## Related Guides

- SC-01 Asset vinden en openen
- AST-04 Werk afronden en overdragen
- WF-02 Workflow uitvoeren en afronden
- HELP-01 Problemen en hulp
