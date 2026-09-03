# CAT-00 v8 Review

| Field | Value |
| --- | --- |
| Previous version | v7 unaccepted working draft |
| Generated | 2026-09-01 |
| PDF | `resources/manuals/operator-guides/drafts/CAT-00-catalogus-begrijpen-v8-draft.pdf` |
| Generator | `scripts/manuals/generate-catalog-guide-review.mjs` |
| Impact | Six-part orientation rebuild aligned to the CAT guide-set plan |
| Status | Unaccepted working draft; exact-version review pending |

## Rebuild

- Replace the eight-part compressed field manual with six orientation parts:
  overview, identity, reusable definitions, expected versus physical state,
  specification placement, and task-based follow-up routing.
- Show Basismodel, exact model number, and physical asset in one relationship
  map, including direct attribute values and expected components on a model
  number.
- Show that a Componentdefinitie uses one or more Attribuutdefinities and that
  a registered component is a physical record on one asset.
- Present Assumed and Tracked as separate outcomes of an expected component;
  do not imply that every Assumed item must become Tracked.
- Reuse CAT purple, component amber, and asset green consistently for both
  definitions and physical records.
- End with complete, family-styled references to CAT-01 through CAT-06 and the
  relevant AST/CMP physical-record guides.

## QA

- Six unencrypted A4 pages generated with no shared-component or page-geometry
  errors.
- All six final page renders were inspected at full-page scale. Connector
  targets, state alternatives, screenshot crops, captions, footer references,
  and page continuity remain within their frames.
- Extracted text contains `Draft v8` and no development or live URL.
- Portable PDF SHA-256:
  `4B401731FDFE5A5371F7BF180DDB5778CB1B391D2E1DDEA87F75D0B7A1231378`.
- The complete package verifier passes against a clean mirror containing the
  exact manifest contents: 73 evidence files, 9 accepted PDFs / 11 pages, 17
  unaccepted PDFs / 28 pages, 2 baselines, and 16 active scripts.
- Direct validation of the live draft directory remains blocked only because
  Foxit holds the superseded v7 portable copy open as one extra unlisted file.
  The unchanged v7 historical PDF remains available in `output/pdf`.

## Review Decision

CAT-00 v8 is the current portable working draft for exact-version review. It
is not approved and does not change any accepted guide record.
