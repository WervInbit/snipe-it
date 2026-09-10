# USR-02 v3 Review Record

| Field | Value |
| --- | --- |
| Status | Superseded by v4 |
| Generated | 2026-08-13 |
| PDF | `output/pdf/usr-02-rol-en-rechten-wijzigen-v3-draft.pdf` |
| Proof folder | `C:\Users\Gebruiker\Documents\snipe-it manuals\layout-proofs\2026-08-13-usr02-review-v3` |
| Generator | `scripts/manuals/generate-user-account-guide-review.mjs` |

## Included Corrections

- Removed the redundant identity stop from step 1.
- Step 2 explains that several groups can be selected simultaneously and that
  `Ctrl+klik` adds or deselects a group.
- Removed the Superadmin/unclear-role stop from step 2.
- Replaced approval-oriented context and wording: Admin and Superadmin perform
  the permission task as part of their role and do not request separate
  approval from this guide.
- Preserved the actual application boundary: only Superadmin can change group
  memberships or `Global: Super User`; Admin can manage ordinary direct rights.
- Expanded step 3 with operator-facing definitions:
  - `Overnemen` uses the combined result of the selected groups and creates no
    user-specific override;
  - `Toestaan` grants the named right directly to this user;
  - `Weigeren` blocks the named right for this user, including when a group
    grants it.
- Removed the step-3 approval/uncertainty stop. The normal fallback is to leave
  an unclear right on `Overnemen` and read its description.
- Step 4 and `Klaar als` now verify the combined effect of groups and direct
  rights rather than only one group.

## QA

- Shared component geometry checks: passed with 9 badges and 4 full guide
  references.
- PDF page count and A4 dimensions: passed; one page at 209.89 x 297.01 mm.
- Extracted text: passed for the Admin/Superadmin role, multi-group control,
  all three permission definitions, Superadmin boundary, and combined end
  state. Removed stop/approval wording and stale v2 text are absent.
- Full-page PDF raster review at 180 DPI: passed without overlap, clipping, or
  unreadable permission text.
