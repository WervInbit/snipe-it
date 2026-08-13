# USR-02 v2 Review Record

| Field | Value |
| --- | --- |
| Status | Superseded by v3 |
| Generated | 2026-08-13 |
| PDF | `output/pdf/usr-02-rol-en-rechten-wijzigen-v2-draft.pdf` |
| Proof folder | `C:\Users\Gebruiker\Documents\snipe-it manuals\layout-proofs\2026-08-13-usr02-review-v2` |
| Generator | `scripts/manuals/generate-user-account-guide-review.mjs` |

## Preserved Workflow

- Verify the correct user before editing.
- Use a standard group for the normal operational role.
- Do not treat Admin as Superadmin or grant Superadmin without explicit
  authorization.
- Keep direct rights on `Overnemen` unless a documented exception requires a
  deliberate `Toestaan` or `Weigeren` choice.
- Verify the saved group and reopen permissions to check for unexplained
  overrides.

## Presentation Corrections

- Migrated the page to the shared component system.
- Added the AC family icon to the AC-01 login prerequisite.
- Moved long-step screenshots far enough right that 2A and 3A do not cover
  their headings.
- Shortened the on-page step-3 heading to `Maatwerk alleen na goedkeuring` so
  its meaning remains explicit without entering the 3A badge area.
- Added measured focus targets with symmetric padding and target names.
- Replaced abbreviated footer labels with four full registered guide names
  over two rows.
- Corrected the completion text encoding.

## QA

- Shared component geometry checks: passed with 9 badges and 4 full guide
  references.
- PDF page count and A4 dimensions: passed; one page at 209.89 x 297.01 mm.
- Extracted text: passed for the v2 label, step-3 heading, and all four full
  guide references; no development URL or stale v1 label is present.
- Full-page PDF raster review at 180 DPI: passed without heading/badge overlap,
  clipping, or visible encoding defects.
