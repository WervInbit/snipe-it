# CAT-02 v1 Review

| Field | Value |
| --- | --- |
| Previous version | None; first rendered draft |
| Generated | 2026-09-03 |
| PDF | `resources/manuals/operator-guides/drafts/CAT-02-modelspecificatie-opbouwen-v1-draft.pdf` |
| Generator | `scripts/manuals/generate-catalog-guide-review.mjs` |
| Impact | New six-page exact-model-number specification guide |
| Status | Unaccepted working draft; exact-version review pending |

## Build

- Start from the correct Basismodel and exact model-number row, choose
  `Edit Spec`, and validate both breadcrumb and selector before editing.
- Separate direct whole-variant facts from replaceable, countable, or
  inspectable expected components before either input route begins.
- Show the complete direct-attribute search/add/value route and the complete
  expected-component add/definition/quantity route.
- Explain derived attributes and expected child structure in operator terms;
  remove new duplicate input when a component already supplies the fact.
- Keep saved-row removal as an Admin CAT-05 route, then show `Opslaan`, the
  confirmation state, and the exact model-number verification.

## Evidence And QA

- Nine canonical sources support labels 1A through 6B. Unsaved form states
  were not submitted; conflict and saved-confirmation states were injected
  only into the screenshot DOM.
- Six unencrypted A4 pages generated with no shared-component or page-geometry
  errors.
- All six final PDF pages were rendered through Poppler and inspected.
  Screenshot context, focus marks, captions, decision cards, page navigation,
  guide references, and footer elements remain legible and inside their
  frames.
- Extracted text contains `Draft v1`, the required action labels, and no
  development or live URL.
- Portable PDF SHA-256:
  `7DB4AFCB4867486F49CB336C7E2C59D2DD4A7F3B86E5A47AAAE679AB14FA5713`.
- The package verifier passes against a clean mirror containing the exact
  manifest contents: 92 evidence files, 9 accepted PDFs / 11 pages, 20
  unaccepted PDFs / 45 pages, 2 baselines, and 16 active scripts.

## Known Boundaries

- Expected-component rows are required by default in the current model-spec
  interface; the guide does not invent a required/optional control.
- Saving the baseline does not create a physical component, tag, serial, or
  asset record.
- Removing already-saved direct values or expected-component rows remains an
  Admin cleanup route planned for CAT-05.
- CAT-06 source recording remains an unresolved operational decision; CAT-02
  therefore requires a source but does not claim that it is stored here.

## Review Decision

CAT-02 v1 is the current portable working draft for exact-version review. It
is explicitly unaccepted and does not change any accepted guide record.
