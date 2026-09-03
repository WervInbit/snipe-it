# CAT-04 v2 Review

| Field | Value |
| --- | --- |
| Previous version | CAT-04 v1 working draft |
| Generated | 2026-09-03 |
| PDF | `resources/manuals/operator-guides/drafts/CAT-04-componentdefinities-beheren-v2-draft.pdf` |
| Generator | `scripts/manuals/generate-catalog-guide-review.mjs` |
| Impact | Guide wording and page-2/3 explanatory-card typography |
| Status | Unaccepted working draft; exact-version review pending |

## Feedback And Investigation

- Enlarge the body copy in the six page-2 identity cards instead of leaving
  large unused card interiors.
- Verify whether `Quantity` and `Required` are both mandatory and explain when
  an expected subcomponent would not be required.
- Application review found that Quantity controls the expected count and
  materialization slots. A meaningful row defaults to one when Quantity is
  omitted.
- `Required` is stored, shown, and copied into workflow context, but it does
  not currently enforce a separate readiness check. Unchecked rows still
  contribute to calculated specifications and applicability as expected
  subcomponents.

## Changes

- Increased the identity-card body size and made each description more
  explicit.
- Kept Quantity as the normal expected count.
- Instructed operators to leave `Required` selected and not add optional or
  per-device varying parts to this expected structure.
- Preserved all six pages, evidence, focus marks, hierarchy handling, Save,
  and return routes from v1.
- Promoted the recurring sparse-card feedback to the shared component
  contract.

## Evidence And QA

- Generator syntax check passed.
- Shared component and page geometry checks report no errors.
- Six unencrypted A4 pages render without blank pages or elements outside the
  page bounds.
- All six full-page PNGs were inspected; the enlarged copy remains inside its
  cards and the new Required guidance remains clear of the footer.
- Extracted text contains the v2 Required guidance and contains no development
  URL.
- Portable PDF SHA-256:
  `36635d649cc49c1a99942ea7d08eca65fd3378df56913c778f32561859e1d30a`.

## Review Decision

CAT-04 v2 is the current portable working draft. It remains unaccepted until
the user explicitly approves this exact version.
