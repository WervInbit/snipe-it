# Operator Guide Layout Recipes

Status: authoritative catalog of reusable page and step structures.

This catalog preserves the task-dependent layouts proven by the current guide
set. A recipe controls reading order and use of space; it does not replace the
shared colors, badges, references, focus marks, typography, or footer rules in
[components.md](components.md).

## Composition Model

Every generated guide declares:

1. one base layout recipe;
2. zero or more step patterns;
3. a one-page or two-sided page model;
4. its exact version and generator in [registry.md](registry.md).

The same recipe may use different step heights, screenshot proportions, help
counts, and related-guide counts. Do not create a new recipe only because one
guide needs more text or a wider screenshot.

## Base Recipes

| ID | Name | Use | Preserved example |
| --- | --- | --- | --- |
| `compact-horizontal-sequence` | Compact horizontal sequence | A short entry task with a small, fixed sequence that benefits from one shared frame. | AC-01 v6 |
| `stacked-step-flow` | Stacked step flow | The normal task layout: vertically ordered steps with text and one or more attached visuals. | CMP-01 v4, USR-01 v8 |
| `mixed-asymmetric-flow` | Mixed asymmetric flow | A task whose steps need materially different widths or grouping, such as a wide choice step followed by paired and full-width steps. | SC-01 v10 |
| `two-column-step-grid` | Two-column step grid | Four or similarly balanced steps where two columns improve visual size and comparison. | WF-01 v9 |
| `route-list` | Route list | A process overview that points to task guides without repeating their instructions. | AST-02 v5 |
| `troubleshooting-grid` | Troubleshooting grid | Non-linear problem lookup using compact independent recovery routes. | HELP-01 v6 |
| `reference-chapter` | Reference chapter | Multi-page explanation using diagrams, decision tables, examples, and selected interface landmarks. | CAT-00 v4 |
| `extended-admin-flow` | Extended administration flow | A desktop-first procedure that preserves field explanations and recognizable screenshots over several continuously numbered pages. | CAT-01 v2 |
| `unassigned` | Not yet selected | A planned guide whose real workflow has not been investigated. It may not be generated as a review candidate. | USR-05 |

## Step Patterns

Step patterns modify a base recipe. They can be combined when the task requires
it.

| ID | Meaning | Rules | Preserved example |
| --- | --- | --- | --- |
| `single-visual` | One visual supports one step. | Keep enough interface context; attach its label and caption to that visual. | CMP-01 steps 2-4 |
| `parallel-visual-choice` | Two or more visuals are equivalent ways to complete one step. | Use one step number and image labels such as `1A` and `1B`; choice wording makes equivalence clear. | SC-01 step 1 |
| `inline-route-alternative` | A normal route and a separate alternative occur inside one numbered decision step. | Keep the alternative subordinate to the step. Use a compact divider only when needed for comprehension. | WF-01 step 3 |
| `mixed-visual-widths` | A step contains visuals with deliberately different widths. | Width follows recognition needs, not equal-card symmetry. | USR-01 step 1 |
| `reused-evidence` | One canonical source supports multiple labels or guides. | Use different measured crops or targets without duplicating the source file. | CMP-01 2A/3A; AC-01 3A and SC-01 1A |
| `inline-stop` | A risk is stated in the first relevant step. | Prefer concise red text when the warning is part of the step purpose. | SC-01 step 4 |
| `inline-warning` | A recoverable validation or correction belongs in its relevant step. | Use amber text and state the corrective action; do not imply an emergency halt. | AST-03 v3/v4 duplicate/model checks |
| `help-alternative` | An exceptional or recovery route belongs below the main path. | It must not look like a successful final step. | AST-02 unregistered-asset route |
| `two-sided-continuation` | One task continues on a second A4 side. | Side 1 ends with a visible `volgende pagina` handoff; numbering and context continue on side 2. | WF-02 v10 |

## Current Recipe Assignments

| Guide | Base recipe | Step patterns | Page model |
| --- | --- | --- | --- |
| AC-01 | `compact-horizontal-sequence` | `single-visual` | One page |
| AC-02 | `stacked-step-flow` | `single-visual` | One page |
| SC-01 | `mixed-asymmetric-flow` | `parallel-visual-choice`, `reused-evidence`, `inline-stop` | One page |
| AST-02 | `route-list` | `help-alternative` | One page |
| AST-03 | `stacked-step-flow` | `parallel-visual-choice`, `reused-evidence`, `inline-warning`, `two-sided-continuation` | Two-sided |
| AST-04 | `stacked-step-flow` | `reused-evidence`, `inline-warning` | One page |
| AST-05 | `two-column-step-grid` | `reused-evidence`, `inline-warning`, `inline-route-alternative` | One page |
| WF-01 | `two-column-step-grid` | `inline-route-alternative`, `reused-evidence` | One page |
| WF-02 | `stacked-step-flow` | `single-visual`, `reused-evidence`, `two-sided-continuation` | Two-sided |
| CMP-01 | `stacked-step-flow` | `single-visual`, `reused-evidence`, `inline-stop` | One page |
| CMP-02 | `stacked-step-flow` | `parallel-visual-choice`, `reused-evidence`, `inline-stop` | One page |
| CMP-04 | `stacked-step-flow` | `single-visual`, `reused-evidence`, `inline-stop` | One page |
| HELP-01 | `troubleshooting-grid` | None | One page |
| USR-01 | `stacked-step-flow` | `mixed-visual-widths`, `single-visual` | One page |
| USR-02 | `stacked-step-flow` | `mixed-visual-widths`, `single-visual` | One page |
| USR-03 | `stacked-step-flow` | `parallel-visual-choice` | One page |
| USR-04 | `stacked-step-flow` | `single-visual`, `two-sided-continuation` | Two-sided |
| USR-05 | `unassigned` | To be determined from the verified workflow | Unknown |
| CAT-00 | `reference-chapter` | `reused-evidence`, `two-sided-continuation` | Eight-page chapter |
| CAT-01 | `extended-admin-flow` | `single-visual`, `inline-route-alternative`, `inline-warning`, `reused-evidence`, `two-sided-continuation` | Five-page procedure |
| CAT-02 | `unassigned` | Specification and expected-component workflow | Unknown |
| CAT-03 | `unassigned` | Attribute-definition workflow | Unknown |
| CAT-04 | `unassigned` | Component-definition workflow | Unknown |
| CAT-05 | `unassigned` | Variant and lifecycle workflow | Unknown |
| CAT-06 | `unassigned` | Verification and source workflow | Unknown |

AST-01 is retired. Its historical layouts are evidence of earlier exploration,
not an active recipe assignment.

## Selection Rules

- Begin with the real operator decision structure, not a preferred visual grid.
- Use `compact-horizontal-sequence` only when every step can remain legible at
  the same height and the complete sequence benefits from one frame.
- Use `stacked-step-flow` by default for task execution.
- Use `mixed-asymmetric-flow` when forcing equal cards would shrink or detach
  the evidence from its step.
- Use `two-column-step-grid` only when the reading order remains obvious.
- Use `route-list` only for navigation between guides, never for detailed task
  instructions.
- Add a second side before reducing screenshots below recognizable context or
  shrinking critical instructions.
- A reference chapter may use more than two pages when its decisions are reused
  by several task guides. Keep one question or concept family per page and use
  continuous page and section numbering.
- An extended administration task remains one guide when the operator completes
  one end-to-end outcome. Split supporting definition management into referenced
  guides instead of hiding it in compressed side notes.

## Preservation And Migration

The exact internally accepted versions remain immutable. Their current
generators are the reference implementations for their recipes until each
recipe is implemented in the shared module and proven visually equivalent.

Migrating a guide to shared code is a new draft version. Do not overwrite an
accepted PDF or claim equivalence from component tests alone. Compare the new
full-page proof with the accepted version at actual A4 size and record any
intentional differences in a version review record.
