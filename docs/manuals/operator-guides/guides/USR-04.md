# USR-04 Gebruiker Uitschakelen Of Herstellen

| Field | Current value |
| --- | --- |
| Status | Working draft v3; visual-correction pass and awaiting exact-version review |
| Family | USR |
| Type | Two-sided administration and lifecycle task |
| Current version | `usr-04-gebruiker-uitschakelen-v3-draft` |
| Page model | Two-sided |
| Layout recipe | `stacked-step-flow` with `single-visual` and `two-sided-continuation` |
| Generator | `scripts/manuals/generate-user-account-guide-review.mjs` |
| Role | Admin |
| Needed | Stop decision and a new responsible owner |
| Prerequisite | Ingelogd (AC-01 Login) |

## Purpose

Stop future login safely, check responsibilities and assigned records, and use
deletion or restoration only when the lifecycle decision requires it.

Version 3 keeps warning text with the step body, replaces circular action
marks with rectangular focus areas, and uses complete styled guide references
in help and footer handoffs.

## Side 1 - Deactivate Safely

1. `Vind en controleer het account` - open `Personen > Toon Alles`, search by
   name or username, open the correct account, and verify both values. Review the Assets,
   Licenses, Accessories, Consumables, Managed Users, and Managed Locations
   counts before changing the account.
2. `Verwerk open eigendom` - check in or transfer physical and licensed items,
   and assign managed users or locations to the approved replacement. Do not
   remove history merely to make deletion possible.
3. `Schakel inloggen uit` - choose Edit, clear
   `Deze gebruiker kan inloggen`, and save.
4. `Controleer deactivering` - verify that the account remains in the system
   for history but is no longer active.

Deactivation is the default route when access must stop but the account and
history still need to remain available.

## Side 2 - Delete Or Restore Only When Required

5. `Controleer voor verwijderen` - deletion requires the agreed lifecycle
   decision and no assigned assets, accessories, licences, managed users, or
   managed locations. Never delete your own account. Choose `Verwijder` only
   after this full check.
6. `Vind het verwijderde account` - after deletion, open removed users and find
   the existing identity. Do not create a duplicate replacement account.
7. `Herstel een verwijderd account` - open the removed record and choose
   Restore. Do not create a replacement account with the same identity.
8. `Controleer na herstel` - verify username, active state, group, and direct
   rights. Restoration returns the record but does not prove that login and
   permissions are appropriate.

`Check Alles In / Verwijder Gebruiker` is a destructive bulk-cleanup route and
is not the normal offboarding shortcut.

## Step-Specific Stops

- Side 1 step 1: stop when the account identity or ownership counts are unclear.
- Side 1 step 2: stop when an item, user, or location has no approved owner.
- Side 2 step 5: stop when deletion is requested only to disable access.
- Side 2 step 5: do not use `Check Alles In / Verwijder Gebruiker` without a
  separately reviewed bulk-cleanup decision.
- Side 2 step 8: stop before reactivation when the current role is uncertain.

## Compact Help

- `Verwijderen is geblokkeerd` - inspect remaining assignments and management
  relationships; do not bypass the protection.
- `Alleen toegang stoppen` - deactivate; do not delete.
- `Per ongeluk verwijderd` - restore the existing record.
- `Dubbel account` - do not merge or delete casually; use later duplicate-user
  guidance.

## Evidence Manifest

| Label | Job | Source ID | Status |
| --- | --- | --- | --- |
| Side 1 - 1A/1B | Canonical People navigation and user search | `USR-DASHBOARD-PEOPLE-NAV-DESKTOP-01`; `USR-LIST-DESKTOP-01` | Captured 2026-08-11/13 |
| Side 1 - 2A | User detail with assignment and management tabs | `USR-ASSIGNMENTS-DESKTOP-01` | Captured 2026-08-11 |
| Side 1 - 3A | Edit-user activated checkbox | `USR-EDIT-ACTIVATED-DESKTOP-01` | Captured 2026-08-11 |
| Side 1 - 4A | Deactivated account state | `USR-DEACTIVATED-DESKTOP-01` | Captured 2026-08-11 |
| Side 2 - 5A | Delete and bulk check-in/delete controls | `USR-DELETE-DESKTOP-01` | Captured 2026-08-11 |
| Side 2 - 6A | Removed-user list and existing identity | `USR-DELETED-LIST-DESKTOP-01` | Captured 2026-08-11 |
| Side 2 - 7A | Removed-user Restore action | `USR-RESTORE-DESKTOP-01` | Captured 2026-08-11 |
| Side 2 - 8A | Restored identity, group, and login state | `USR-RESTORED-DESKTOP-01` | Captured 2026-08-11 |

## Complete When

The verified account is safely deactivated, or the explicitly approved
delete/restore route is complete with ownership, role, and active state checked.

## Related Guides

- USR-01 Gebruiker toevoegen
- USR-02 Rol en rechten wijzigen
- USR-03 Wachtwoord resetten
- HELP-01 Problemen en hulp
