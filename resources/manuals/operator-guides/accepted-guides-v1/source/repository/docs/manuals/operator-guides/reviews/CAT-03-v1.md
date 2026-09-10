# CAT-03 v1 Review

| Field | Value |
| --- | --- |
| Previous version | None; first rendered draft |
| Generated | 2026-09-01 |
| PDF | `resources/manuals/operator-guides/drafts/CAT-03-attributen-beheren-v1-draft.pdf` |
| Generator | `scripts/manuals/generate-catalog-guide-review.mjs` |
| Impact | New five-page attribute-definition administration guide |
| Status | Unaccepted working draft; exact-version review pending |

## Build

- Start from `Instellingen > Attributes`, search and compare before creating,
  and explain why reuse prevents competing definitions.
- Explain `Label`, `systeemnaam (Key)`, datatype, unit, scope, required state,
  asset overrides, custom Enum values, and component display behavior in
  operator language.
- Present numeric constraints and Enum options as alternatives rather than
  forcing both routes.
- Show the complete Save context and expected result-row columns, then route
  ordinary work back to CAT-02 or CAT-04 and lifecycle work to CAT-05.

## Evidence And QA

- Six canonical sources support labels 1A through 5B. Unsaved forms were not
  submitted; 5B is a screenshot-only DOM result-row example.
- Five unencrypted A4 pages generated with no shared-component or
  page-geometry errors.
- All final PDF pages were rendered through Poppler and inspected. Navigation,
  form context, captions, alternative routes, Save, result columns, and guide
  references remain legible and inside their frames.
- Extracted text contains `Draft v1`, contains no development URL, and contains
  no stale `systeemkey` wording.
- Portable PDF SHA-256:
  `050C5A748EF1B62F481F0272B1AFDAC15FBC1E696A944E316C87AAC43FAC504A`.
- The package verifier passes against a clean mirror containing the exact
  manifest contents: 85 evidence files, 9 accepted PDFs / 11 pages, 19
  unaccepted PDFs / 39 pages, 2 baselines, and 16 active scripts.
- Direct validation of the live draft directory remains blocked only because
  the superseded CAT-00 v7 PDF is one extra unlisted file held open outside
  this guide build.

## Known Boundaries

- Datatype immutability is taught before the first Save.
- Regex remains outside the ordinary Supervisor route.
- Activation, deactivation, saved-option removal, and deletion remain planned
  for CAT-05 and are not implied by this draft.

## Review Decision

CAT-03 v1 is the current portable working draft for user inspection. It is
explicitly unaccepted and does not change any accepted guide record.
