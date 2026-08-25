# USR-03 Wachtwoord Resetten

| Field | Current value |
| --- | --- |
| Status | Working draft v3; visual-correction pass and awaiting exact-version review |
| Family | USR |
| Type | Administration task |
| Current version | `usr-03-wachtwoord-resetten-v3-draft` |
| Page model | One page |
| Layout recipe | `stacked-step-flow` with `mixed-visual-widths` |
| Generator | `scripts/manuals/generate-user-account-guide-review.mjs` |
| Role | Admin |
| Needed | Correct local account and personal handoff route |
| Prerequisite | Ingelogd (AC-01 Login) |

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
