# USR-01 v8 Review Record

| Field | Value |
| --- | --- |
| Status | Internal review candidate for V1; accepted 2026-08-13 |
| Generated | 2026-08-13 |
| PDF | `resources/manuals/operator-guides/pdf/USR-01-gebruiker-toevoegen-v8.pdf` |
| Proof folder | `C:\Users\Gebruiker\Documents\snipe-it manuals\layout-proofs\2026-08-13-usr01-review-v8` |
| Generator | `scripts/manuals/generate-user-account-guide-review.mjs` |

## Included Correction

- Step 1 now contains two linked visuals instead of relying on text alone.
- `1A` shows the expanded Dutch dashboard sidebar with `Personen` open and
  `Toon Alles` visible.
- `1B` retains the user-list toolbar and targets the add-user `+` action.
- The two visuals use unequal widths so the narrow navigation state remains
  recognizable without unnecessarily shrinking the wider list toolbar.
- The step wording now follows the same visible order: `Personen`, `Toon Alles`,
  then `+`.

## Evidence

- New source ID: `USR-DASHBOARD-PEOPLE-NAV-DESKTOP-01`.
- Source file:
  `resources/manuals/operator-guides/evidence/USR-DASHBOARD-PEOPLE-NAV-DESKTOP-01.png`.
- Captured from the controlled Dutch development interface on 2026-08-13.
- The source is unannotated; the focus mark is generated separately.

## QA

- Shared component geometry checks: passed with 12 badges and 5 guide
  references.
- PDF page count and A4 dimensions: passed; one page at 209.89 x 297.01 mm.
- Extracted text: passed for the v8 label, `Personen`, `Toon Alles`, and both
  screenshot captions; no development URL or stale v7 label is present.
- Full-page PDF raster review at 180 DPI: passed without overlap or clipping.

## Review Decision

USR-01 v8 was explicitly accepted on 2026-08-13. This exact PDF is now an
`Internal review candidate for V1`; later wording, evidence, or layout changes
require a new version and review.
