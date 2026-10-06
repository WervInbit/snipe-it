# USR-03 Wachtwoord Resetten

## Application Alignment Revision - 2026-10-01

Current authoring proposal: **v5, working draft; not accepted**, 1 A4 page. [Exact-version review](../reviews/USR-03-v5.md) and [batch comparison](../reviews/aligned-guides-2026-10-01.md).

Actual Generate control and automatic confirmation, secure handoff before leaving reset form, compact self-password menu.

The frozen new HTML preserves this generated proposal for reproduction. It does not define approved instructional scope or supersede the guide specification, [authoring method](../authoring-method.md), or owner decisions. Resolve conflicting walkthroughs before a new revision. Earlier artifacts and acceptance decisions are unchanged. Generator: scripts/manuals/generate-aligned-guides-2026-10-01.mjs; hash-locked v4 input.

## Purpose

Restore access to the correct local account without learning or retaining the
user's final private password.

Version 3 shows the unsaved generated temporary value below the password field,
uses a rectangular focus around `Genereer`, tightens the 3A account-menu focus,
and presents the AC-02 handoff as a complete guide reference.

## Steps

1. `Vind en controleer de gebruiker` - open `Personen > Toon Alles`, search the
   user, compare name and username, open the correct account, and choose
   `Gebruiker aanpassen`.
2. `Maak één tijdelijk wachtwoord` - use `Genereer` once in the password
   fields, leave `Deze gebruiker kan inloggen` enabled, and choose `Opslaan`
   once. Do not create or retain a permanent password for the user.
3. `Draag veilig over` - give the temporary password to the user personally.
   Do not put it in chat, email, notes, tickets, screenshots, or this guide.
   The user follows AC-02 immediately and chooses the final private password.

## Step-Specific Stops

- Step 1: stop when the account identity is uncertain.
- Step 3: stop when no approved secure transfer channel is available. Never
  ask the user to disclose the final password as proof.

## Compact Help

- `Account staat uit` - enable login only after verifying the correct user.
- `Genereer werkt niet` - do not save a partial change; ask system management.
- `Geen veilig kanaal` - do not transfer the password until personal handoff
  is arranged.
- `Wachtwoord gewijzigd` - continue with AC-02 for the private password.

## Evidence Manifest

| Label | Job | Source ID | Status |
| --- | --- | --- | --- |
| 1A | Canonical `Personen > Toon Alles` navigation | `USR-DASHBOARD-PEOPLE-NAV-DESKTOP-01` | Captured 2026-08-13 |
| 1B | User search and correct result | `USR-LIST-DESKTOP-01` | Captured 2026-08-11 |
| 1C | Correct user's `Gebruiker aanpassen` action | `USR-DETAIL-DESKTOP-01` | Captured 2026-08-11 |
| 2A | Edit-user `Genereer`, login, and Save controls with password values hidden | `USR-EDIT-PASSWORD-DESKTOP-01` | Captured 2026-08-11 |
| 3A | AC-02 guide reference and self-change start | `AC-ACCOUNT-MENU-DESKTOP-01` | Captured 2026-08-11; shared with AC-02 |

## Complete When

The correct user has received one generated temporary password personally and
continues immediately with AC-02.

## Related Guides

- AC-01 Login
- AC-02 Eigen wachtwoord wijzigen
- USR-01 Gebruiker toevoegen
- HELP-01 Problemen en hulp

## Focused Candidate - 2026-09-08

The separate [v4 review](../reviews/USR-03-v4.md)
records the focused follow-up to baseline v3, with the exact
PDF and checksum. The owner requested this pass across all existing guides
after reviewing WF-01 v12 positively. Earlier selections and their policies
remain traceable; this candidate is not automatically accepted. See the
[set comparison](../reviews/guide-set-comparison-2026-09-08.md) for scope,
source/crop exceptions, and remaining operational or evidence gaps.
