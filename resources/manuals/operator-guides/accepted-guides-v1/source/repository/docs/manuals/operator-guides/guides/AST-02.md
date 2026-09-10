# AST-02 Refurbishment Route

## Latest Correction Candidate - 2026-09-10

Current review candidate: [v8](../reviews/AST-02-v8.md), owner-cleared for user review.
Styled all alternative and return references with family color, marker, code and full title.
This current correction takes precedence over conflicting historical version
notes below. Previous PDFs and exact acceptance records remain unchanged.

| Field | Current value |
| --- | --- |
| Status | Internal review candidate v8; owner cleared for user review 2026-09-10 |
| Family | AST |
| Type | Route overview |
| Current version | `AST-02-refurbishment-route-v8-draft` |
| Page model | One page |
| Layout recipe | `route-list` with `help-alternative` |
| Generator | `scripts/manuals/generate-owner-corrections.mjs AST-02` |
| Role | Refurbisher |
| Needed | Device, account, and the task guides referenced by the route |

## Purpose

Show the floor route from login to handoff without repeating detailed instructions from the task guides.

## Route

1. `Aanmelden` - use AC-01.
2. `Asset vinden en controleren` - use SC-01.
3. `Werk starten` - use WF-01 when the asset requires a workflow.
4. `Werk uitvoeren` - use WF-02 and a CMP guide only when component work is required.
5. `Werk overdragen` - use AST-04.
6. `Beoordelen en vrijgeven` - supervisor uses AST-05.

## Visual Model

- Use large guide-family chips and a simple route line rather than repeating application screenshots.
- Keep optional component work visibly branching from the main workflow route and returning before handoff.
- AST-03 is an entry route only for a physical device that is not yet registered.

## Complete When

The operator can identify the current route stage and the next task guide without interpreting a dense process chart.

## Related Guides

All active guides except HELP-01 may appear as route references. HELP-01 remains a persistent fallback.

## Review Notes

- Generate only after the child-guide titles and ownership boundaries are stable.
- Do not show product statuses or role permissions that are still unresolved.

## Version Notes

- v6 removes the senior-refurbisher role from this general refurbishment
  route. The route content and accepted compact-list layout remain unchanged.

## Focused Candidate - 2026-09-08

The separate [v7 review](../reviews/AST-02-v7.md)
records the focused follow-up to baseline v6, with the exact
PDF and checksum. The owner requested this pass across all existing guides
after reviewing WF-01 v12 positively. Earlier selections and their policies
remain traceable; this candidate is not automatically accepted. See the
[set comparison](../reviews/guide-set-comparison-2026-09-08.md) for scope,
source/crop exceptions, and remaining operational or evidence gaps.

## Owner Feedback Follow-Up - 2026-09-10

MR-02: v7 alternative/return routes need complete family-styled guide references.
See [owner review TODOs](../reviews/owner-review-todos-2026-09-10.md).
The existing PDF is unchanged; these are next-version or decision items.
