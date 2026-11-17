# Operator Guide Layout Recipes

## Current Use - 2026-11-17

These recipes guide composition in both native and generated revisions. The
[current register](registry.md) selects the delivered versions/page counts;
the dated assignments below retain their original proposal context. All 25
manuals are converted and delivered. Compare actual shared native masters
before changes; a recipe or generator does not override later human artwork.

## AST-03 Pilot - 2026-11-04

AST-03 v19 retains `stacked-step-flow` and two-sided continuous numbering 1-8.
It uses `inline-route-alternative`, `reused-evidence`, `inline-warning` and
`two-sided-continuation`. Dashboard orientation and the list search are
successive support within step1; the plus and named Dashboard creation control
are alternatives only after the duplicate check. [Exact unaccepted pilot](reviews/AST-03-v19.md).

## Operational Set - 2026-10-20

AST-02 v10 uses one-side `route-list`; WF-01 v16 one-side
`two-column-step-grid` with an explicit route alternative; WF-02 v14 two-side
`stacked-step-flow` with continuous numbering; AST-04 v8 one-side
`stacked-step-flow`; HELP-01 v9 two independent `troubleshooting-grid` sides.
See the [per-guide reasoning](reviews/operational-guides-2026-10-20.md).
These are draft assignments. The help expansion supports sixteen current
problem groups without shrinking old topics into a numbered procedure.

## Reauthoring Test Assignments - 2026-10-08

The initial v11/v14/v8 examples below require rework following owner feedback.
The bounded v12/v15/v9 corrections retain these same recipe assignments,
page counts and frame dimensions. See the
[role and focus correction](reviews/emphasis-feedback-2026-10-08.md).
This correction does not approve the broader composition.

The [three unaccepted examples](reviews/reauthoring-test-2026-10-08.md) use:

| Guide | Base recipe | Step patterns | Page model |
| --- | --- | --- | --- |
| AC-01 v11 | `compact-horizontal-sequence` | `single-visual`, `reused-evidence` | One side |
| SC-01 v14 | `mixed-asymmetric-flow` | `parallel-visual-choice`, `reused-evidence`, `inline-stop` | One side |
| AST-05 v8 | `stacked-step-flow` | `inline-route-alternative`, `reused-evidence`, `inline-warning`, `inline-stop`, `two-sided-continuation` | Two continuous sides |

AST-05 changes from the earlier grid so current review, decision, confirmation
and saved-state evidence have readable space in one numbered task. SC-01's
asymmetric blocks give its alternative routes and shared check distinct jobs.
These assignments describe the new proposals; retained selections and older
recipe tables below do not change their acceptance state.

## USR-05 / CAT-05 / CAT-06 V2 - 2026-09-15

The revised working drafts are USR-05 v2 (3 pages), CAT-05 v2 (8 pages), and
CAT-06 v2 (3 pages). See the [revision review](reviews/USR-05-CAT-05-CAT-06-v2-2026-09-15.md) for changes,
exact artifacts, checks and remaining operator trials. The three v1 PDFs and
all accepted artifacts are retained unchanged; v2 is not acceptance.


Status: authoritative catalog of reusable page and step structures.

This catalog preserves the task-dependent layouts proven by the current guide
set. A recipe controls reading order and use of space; it does not replace the
shared colors, badges, references, focus marks, typography, or footer rules in
[components.md](components.md).

## Composition Model

Every generated guide declares:

1. one base layout recipe;
2. zero or more step patterns;
3. a one-page, two-sided, or explicitly declared multi-page model;
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
| `reference-chapter` | Reference chapter | Multi-page explanation using diagrams, decision tables, examples, and selected interface landmarks. | CAT-00 v9 |
| `extended-admin-flow` | Extended administration flow | A desktop-first procedure that preserves field explanations and recognizable screenshots over several continuously numbered pages. | CAT-01 v5 |
| `branching-admin-maintenance` | Branching administration maintenance | A decision page followed by independent high-impact maintenance branches; operators perform only the branch for their selected object. | CAT-05 v1 |
| `reference-checklist` | Verification reference checklist | A short multi-page evidence hierarchy and decision checklist that routes verified results into task guides without claiming to perform those tasks. | CAT-06 v1 |
| `unassigned` | Not yet selected | A planned guide whose real workflow has not been investigated. It may not be generated as a review candidate. | Reserved for future planned guides |

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
| USR-05 | `group-choice-permissions-test` | shared context, route choice, screenshots, permission warning and controlled-user completion | Three-page v2 working draft |
| CAT-00 | `reference-chapter` | `reused-evidence`, `two-sided-continuation` | Six-page chapter |
| CAT-01 | `extended-admin-flow` | `single-visual`, `inline-route-alternative`, `inline-warning`, `reused-evidence`, `two-sided-continuation` | Five-page procedure |
| CAT-02 | `extended-admin-flow` | `single-visual`, decision alternatives, component conflict, `reused-evidence`, `two-sided-continuation` | Six-page working draft |
| CAT-03 | `extended-admin-flow` | `single-visual`, parallel input alternatives, `reused-evidence`, `two-sided-continuation` | Five-page working draft |
| CAT-04 | `extended-admin-flow` | `single-visual`, hierarchy warning, `reused-evidence`, `two-sided-continuation` | Six-page working draft |
| CAT-05 | `independent-maintenance-routes` | page index, local completion/return, actual controls and impact stops | Eight-page v2 working draft |
| CAT-06 | `worked-source-check` | physical label, manufacturer-source crops, supported/unresolved outcomes | Three-page v2 working draft |

AST-01 is retired. Its historical layouts are evidence of earlier exploration,
not an active recipe assignment.

## Selection Rules

- Select a recipe only after the [authoring method](authoring-method.md) has
  resolved the reader's actions, choices or reference questions. A recipe
  realizes that structure; it does not choose information ownership or create
  three steps because three cards fit.
- Explain a changed recipe or page model through the affected reader decision,
  recognition job or necessary detail. Judge actual target size and reading
  relationships alongside preservation of useful baseline support.
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
- Size body text for the actual block density. When a large text region holds
  only a few short lines, increase its body size and line height before leaving
  excessive decorative white space. Dense blocks may use the recipe minimum,
  but that minimum must not become the default for sparse blocks.
- A reference chapter may use more than two pages when its decisions are reused
  by several task guides. Keep one question or concept family per page and use
  continuous page and section numbering.
- An extended administration task remains one guide when the operator completes
  one end-to-end outcome. Split supporting definition management into referenced
  guides instead of hiding it in compressed side notes.

## Preservation And Migration

The [2026-09-08 audit pilot specification](reviews/audit-pilot-2026-09-08-specification.md)
registers opt-in comparison variants: WF-01 v11 uses mixed-asymmetric-flow,
USR-04 v4 uses stacked-step-flow with three independent route pages, and
CAT-04 v3 keeps extended-admin-flow with larger instruction regions. These
proposals were rejected as a design direction by the owner on 2026-09-08.
They remain historical experiments, not templates for further guides. Follow
the [feedback](reviews/2026-09-08-pilot-direction-feedback.md): begin with the
baseline layout, retain useful screenshots and help, and use the available
page width. Consistent meanings do not require a single column arrangement.

The exact internally accepted versions remain immutable. Their current
generators are the reference implementations for their recipes until each
recipe is implemented in the shared module and proven visually equivalent.

Migrating a guide to shared code is a new draft version. Do not overwrite an
accepted PDF or claim equivalence from component tests alone. Compare the new
full-page proof with the accepted version at actual A4 size and record any
intentional differences in a version review record.
