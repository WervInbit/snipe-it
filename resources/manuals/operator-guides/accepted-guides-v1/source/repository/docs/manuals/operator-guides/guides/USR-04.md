# USR-04 Gebruiker Uitschakelen

| Field | Current value |
| --- | --- |
| Status | Internal review candidate v6; owner cleared for user review 2026-09-10 |
| Family | USR |
| Type | Single administration task: disable login |
| Current version | `USR-04-gebruiker-uitschakelen-of-herstellen-v6-draft` |
| Page model | One page |
| Layout recipe | `stacked-step-flow` with task-specific evidence sizes |
| Generator | `scripts/manuals/generate-owner-corrections.mjs USR-04` |
| Role | Admin |
| Needed | Decision to disable login for the identified account |
| Prerequisite | AC-01 Login |

The stable historical filename stem is retained for version sorting. The
printed title, instructions and runtime reference title are disable-login only.
See [v6 review](../reviews/USR-04-v6.md): owner-cleared for user review. v5 remains explicitly not accepted.

## Task

1. Open Personen > Toon Alles. Search name or username, compare both and open
   the correct account. Stop and ask for help if identity is uncertain.
2. Choose Gebruiker aanpassen. Clear Deze gebruiker kan inloggen and choose
   Opslaan. Leave password, group and permissions as they are.
3. Open the Info tab after saving. Check name and username again and verify
   Login ingeschakeld is Nee. The existing account remains available.

Help covers uncertain identity, failed saving and a login flag still set to Ja.
Completion: the correct account still exists and the saved login flag is Nee.
The guide does not claim termination of every session or token.

## Evidence And Related Guides

- 1A: USR-DASHBOARD-PEOPLE-NAV-DESKTOP-01.
- 1B: USR-LIST-DESKTOP-01.
- 2A: USR-EDIT-ACTIVATED-DESKTOP-01.
- 3A: USR-DEACTIVATED-DESKTOP-01; explicitly an example screen.
- AC-01, USR-01, USR-02 and HELP-01 use registered styled references.

## Separate Future Scope

Re-enabling login, deleting a record, restoring a deleted record and assignment
cleanup are separate tasks. No new guide IDs are allocated; USR-05 is reserved
for groups. Check-in work and the delete/restore OR route are removed from v6.
The [previous specification](../../../../resources/manuals/operator-guides/review-rounds/2026-09-10/owner-corrections/USR-04-previous-specification.md)
and earlier PDFs remain for historical comparison.
