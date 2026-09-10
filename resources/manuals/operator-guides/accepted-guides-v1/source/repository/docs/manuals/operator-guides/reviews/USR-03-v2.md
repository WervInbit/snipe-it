# USR-03 v2 Review

| Field | Value |
| --- | --- |
| Previous version | v1 working draft |
| Generated | 2026-08-20 |
| PDF | `output/pdf/usr-03-wachtwoord-resetten-v2-draft.pdf` |
| Generator | `scripts/manuals/generate-user-account-guide-review.mjs` |
| Impact | Deployed reset boundary, navigation, and handoff |
| Status | Working draft; cold-start pass; exact-version review pending |

## Correction

- Remove the unsupported email-reset and LDAP alternatives.
- Start from canonical `Personen > Toon Alles` navigation and verify the
  correct account before editing.
- Generate one temporary password, save once, transfer it personally, and
  require immediate AC-02 self-change.

## QA

One A4 page regenerated. Required text and explicit absence of LDAP/reset-link
wording passed, together with PDF geometry, nonblank raster, margins, and
full-page visual inspection.

## Review Decision

USR-03 v2 passes the 2026-08-20 cold-start retest and awaits exact-version
review.
