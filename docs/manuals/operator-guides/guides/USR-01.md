# USR-01 Gebruiker Toevoegen

| Field | Current value |
| --- | --- |
| Status | Internal review candidate for V1 |
| Family | USR |
| Type | Administration task |
| Current version | `usr-01-gebruiker-toevoegen-v8-draft` |
| Page model | One page |
| Layout recipe | `stacked-step-flow` with `mixed-visual-widths` and `single-visual` |
| Generator | `scripts/manuals/generate-user-account-guide-review.mjs` |
| Role | Admin / Superadmin |
| Needed | Approved identity, standard role, verified email when available, and a secure handoff route |
| Prerequisite | Ingelogd (AC-01 Login) with Admin or Superadmin access |

## Purpose

Create one active local account, assign its approved standard group, and verify
the saved identity before giving the user access.

## Username Convention

Use the first name with an initial capital, followed immediately by the
lowercase first letter of each last-name part. Do not use periods or spaces.

Example: `Jan de Vries` becomes `Jandv`.

If that username already exists, stop and ask for the approved exception. Do
not invent a number or suffix.

## Temporary Password Convention

Use the exact username followed immediately by the current four-digit year,
without a separator. Example: username `Jandv` becomes temporary password
`Jandv2026` in 2026.

This is only a first-login password. Give it to the user personally and have
the user change it immediately through AC-02. Do not keep it as the user's
private password.

## Steps

1. `Open Nieuwe gebruiker` - open `Personen` in the expanded left navigation,
   choose `Toon Alles`, then choose the add-user action above the user list.
   When the person may already exist, first search by name, username, and
   email and check removed users. Reuse or restore an existing record instead
   of creating a duplicate.
2. `Vul account en wachtwoord in` - enter first name, last name, the agreed
   username, and the existing verified email address. Enter the temporary
   password as username plus current year and select
   `Deze gebruiker kan inloggen`. Do not invent an email address.
3. `Kies groep en eventuele rechten` - find `Groepen` at the bottom of the
   Information page and choose the approved standard group. `Refurbisher` is
   normal. The second tab at the top is `Machtigingen`; it contains direct
   rights including `Global: Super User` for Superadmin. Use the minimum rights
   needed for the person's work. When the standard group is insufficient, add
   only the required user-specific rights; USR-02 documents that route. Use
   USR-05 to create or edit a reusable group definition.
4. `Controleer en draag over` - choose Save, return to Users, and open the
   saved user. Verify name, username, active state, email, and group. Give the
   temporary password to the user personally and have the user log in and
   follow AC-02 immediately.

## Step-Specific Stops

- Step 1: stop when an active or removed matching account exists; the search
  is a secondary safeguard before opening a duplicate account.
- Step 2: stop when the identity, username exception, or email is uncertain.
- Step 4: the temporary password is not complete handoff until the user has
  been directed to AC-02 for immediate replacement.

Step 3 is not a stop condition. Start with the lowest group that permits the
work, then use the `Machtigingen` tab only for the specific extra rights that
user needs.

## Compact Help

- `Gebruikersnaam bestaat` - reuse/restore or ask for the approved exception.
- `Geen e-mailadres` - creation may continue only when locally approved; the
  reset-link route will not be available.
- `Minimale rechten` - choose the lowest group that permits the work.
- `Maatwerk nodig` - add only the required direct rights through
  `Machtigingen`; follow USR-02.

## Evidence Manifest

| Label | Job | Source ID | Status |
| --- | --- | --- | --- |
| 1A | Expanded Dutch dashboard sidebar with `Personen` and `Toon Alles` visible | `USR-DASHBOARD-PEOPLE-NAV-DESKTOP-01` | Captured 2026-08-13 |
| 1B | User list with add action; search remains visible as a secondary safeguard | `USR-LIST-DESKTOP-01` | Captured 2026-08-11 |
| 2A | Create form with identity, password, and login controls; only login is targeted | `USR-CREATE-FORM-DESKTOP-01` | Captured 2026-08-11; no password visible |
| 3A | Editable group selector with standard operational groups | `USR-GROUP-EDIT-DESKTOP-01` | Captured 2026-08-11 in controlled Superadmin session |
| 4A | Saved fictional user identity and group without an unnecessary focus overlay | `USR-DETAIL-DESKTOP-01` | Captured 2026-08-11 |

## Complete When

One active account exists for the correct person, with the agreed username,
verified email when available, approved standard group, no unintended direct
permissions, and an immediate AC-02 handoff for the temporary password.

## Related Guides

- AC-02 Eigen wachtwoord wijzigen
- USR-02 Rol en rechten wijzigen
- USR-03 Wachtwoord resetten
- USR-04 Gebruiker uitschakelen of herstellen
- USR-05 Groepen beheren

## Version Notes

- v7 migrates this page to the shared component system. It retains the v6
  content and two-row footer while centralizing badge alignment, family and
  guide references, focus padding, completion alignment, and geometry QA.
- v8 adds a small expanded-sidebar visual for the route through `Personen` and
  `Toon Alles`, then relabels the existing add-user toolbar visual as `1B`.
