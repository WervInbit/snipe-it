# USR-01 v7 Review Record

| Field | Value |
| --- | --- |
| Status | Working draft |
| Generated | 2026-08-13 |
| PDF | `output/pdf/usr-01-gebruiker-toevoegen-v7-draft.pdf` |
| Proof folder | `C:\Users\Gebruiker\Documents\snipe-it manuals\layout-proofs\2026-08-13-usr01-review-v7` |
| Generator | `scripts/manuals/generate-user-account-guide-review.mjs` |

## Included Corrections

- Both Admin and Superadmin are identified as account creators.
- The path to `Gebruikers` and `Nieuwe gebruiker` is explicit.
- Existing-user search is secondary to opening the create action.
- The first-login temporary password is username plus current four-digit year.
- Immediate self-change references `AC-02 Eigen wachtwoord wijzigen` with its
  access-family icon and color.
- Standard groups are identified at the bottom of the Information page.
- User-specific rights are identified on the top `Machtigingen` tab and route
  to `USR-02 Rol en rechten wijzigen`.
- Reusable group creation/editing routes to `USR-05 Groepen beheren`.
- The top login prerequisite uses the AC family icon, color, code, and name.
- The footer contains five full guide references over two rows.
- Circular labels use shared true-center rendering; step and image markers have
  distinct sizes and weights.

## Promoted Feedback

The central-label alignment, full reference names, two-row reference capacity,
symmetric focus padding, and marker hierarchy were promoted to the global
component contract in `components.md` and implemented in
`scripts/manuals/lib/guide-system.mjs`.

## QA

- Shared component unit assertions: passed.
- Renderer geometry checks: passed with 11 badges and 5 references.
- Related-guide text overflow checks: passed.
- PDF page count and A4 size: passed; one page at 209.89 x 297.01 mm.
- Extracted-text checks: passed; all five full guide references and `Draft v7`
  are present, with no `dev.inbit` or stale `Draft v6` text.
- Full-page PDF raster review at 180 DPI: passed.
