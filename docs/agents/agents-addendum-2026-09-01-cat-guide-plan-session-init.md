# Agent Addendum - 2026-09-01 CAT Guide Set Plan

## Objective

Create one maintained information architecture for CAT-00 through CAT-06 so
the catalogue guides can be revised and generated without repeating concepts,
preserving stale role rules, or teaching controls that the application does
not expose.

## Starting Context

- CAT-00 v7 and CAT-01 v3 are portable, explicitly unaccepted working drafts.
- CAT-02 through CAT-06 have specifications but no generated artifacts.
- Owner feedback has repositioned CAT-00 as an orientation and router rather
  than a compressed catalogue manual.
- The implemented least-privilege contract now gives Supervisor ordinary
  model, model-number/specification, attribute-definition, and
  component-definition setup rights. Admin retains lifecycle and cleanup.
- The repository also contains an unrelated controlled-migration session.
  Its `PROGRESS.md` and session-addendum changes must remain intact.

## Investigation Boundary

- Read the current guide-system, component, layout, maintenance, registry,
  handoff, decision, CAT specification, and CAT review documents.
- Verify planned CAT actions against current routes, policies, forms, models,
  and services.
- Plan documentation only. Do not regenerate a PDF, change an accepted
  artifact, modify application behavior, or capture new evidence in this
  session.

## Verified Planning Constraints

- Expected components are model-number baseline templates. On an asset they
  can remain `Assumed`; a physical tracked record is materialized by a
  component movement/registration action, not by a generic status change.
- The model-specification screen lets Supervisor add and edit direct values and
  expected components, but removing saved rows is Admin-only cleanup.
- Attribute datatype is immutable after creation. Attribute lifecycle and
  saved-option removal are Admin-only.
- Component-definition tracking and placement values exist in the data model,
  but the current browser form does not expose controls for them. Guides may
  not instruct an operator to configure those values.
- Component-definition deactivation and removal of saved contributions or
  child rows are Admin-only. There is no browser delete route for a component
  definition.
- The current Dutch interface still says `Asset modellen`; the requested
  `Basismodellen` application rename has not been implemented.
- The application has no dedicated catalogue source/verification field.
- Workflow-item and workflow-profile configuration is a workflow-family task,
  not part of the CAT information model.

## Output

- `docs/manuals/operator-guides/catalog-guide-plan.md` owns CAT-family topic
  placement and next-version planning.
- Supporting system, component, project-index, inventory, and handoff files
  will link to the plan without changing current guide versions or statuses.

## Completed Result

- Added the family plan with the operator route, information-ownership matrix,
  page-level CAT-00 through CAT-06 plans, terminology, evidence requirements,
  application gaps, production order, and set-level acceptance tests.
- Promoted connector/text intersection prevention into the shared diagram
  component contract.
- Linked the plan from the guide system, project index, inventory, registry,
  decisions, and continuation handoff.
- Preserved CAT-00 v7 and CAT-01 v3 as unaccepted working drafts. CAT-02 through
  CAT-06 remain planned and require specification alignment before generation.
- `git diff --check`, local Markdown-link validation, and the complete
  `scripts/manuals` test command pass. The package still contains 25 registry
  entries, 72 evidence files, 9 accepted PDFs across 11 pages, and 17
  explicitly unaccepted PDFs across 30 pages.

## Follow-On Implementation

- Rebuilt CAT-00 as v8: six orientation pages covering the object map,
  identity, reusable definitions, expected versus physical component state,
  specification placement, and follow-up guide ownership.
- Rebuilt CAT-01 as v4: five continuously numbered task pages with dashboard
  navigation, Basismodel search, global exact-code search, the three-route
  reuse/create decision, both Save actions, final verification, and CAT-02 /
  AST-03 handoffs.
- Added and registered one read-only global model-number-search capture. The
  evidence package now contains 73 canonical files.
- Generated portable PDFs remain `Unaccepted working draft`; no accepted guide
  status or artifact was changed.
- The generator reports zero component and geometry errors, PDF metadata and
  extracted-text checks pass, and all eleven final pages were inspected.
- The complete package verifier passes against a clean mirror containing the
  exact manifest: 73 evidence files, 9 accepted PDFs / 11 pages, 17 unaccepted
  PDFs / 28 pages, 2 baselines, and 16 active scripts.
- One live-directory cleanup remains: Foxit currently holds the old portable
  CAT-00 v7 file open as an extra unlisted file. After it is closed, move that
  duplicate out of the current draft directory and rerun the verifier without
  the temporary root override. The unchanged v7 historical PDF already
  remains under `output/pdf`.

## CAT-03 And CAT-04 Draft Implementation

- Aligned CAT-03 and CAT-04 with the verified ordinary-Supervisor permission
  boundary and the real browser forms before rendering.
- Added twelve canonical read-only evidence captures. Unsaved forms were not
  submitted; the CAT-03 result row and CAT-04 hierarchy warning are explicitly
  recorded as screenshot-only DOM examples.
- Generated `CAT-03 Attributen beheren` v1 as five A4 pages and `CAT-04
  Componentdefinities beheren` v1 as six A4 pages. Both remain unaccepted
  working drafts awaiting exact-version review.
- Normalized operator terminology to `systeemnaam (Key)`, expected parts, and
  definition levels. Browser-inaccessible tracking/placement controls and
  Admin lifecycle cleanup remain excluded.
- Generator component and geometry checks, script syntax, shared component
  tests, PDF metadata/text extraction, and final Poppler raster review pass.
- The complete package verifier passes against a clean mirror with 85 evidence
  files, 9 accepted PDFs / 11 pages, 19 unaccepted PDFs / 39 pages, 2
  baselines, and 16 active scripts. The live root still reports only the known
  extra locked CAT-00 v7 draft.
