# USR-02 Rol En Rechten Wijzigen

| Field | Current value |
| --- | --- |
| Status | Internal review candidate for V1 |
| Family | USR |
| Type | Administration task with an exceptional route |
| Current version | `usr-02-rol-en-rechten-wijzigen-v7-draft` |
| Page model | One page |
| Layout recipe | `stacked-step-flow` with `mixed-visual-widths` and `single-visual` |
| Generator | `scripts/manuals/generate-user-account-guide-review.mjs` |
| Role | Admin / Superadmin |
| Needed | Correct user and the intended access |
| Prerequisite | User account already exists |

## Purpose

Change a user's operational access through group membership and, when needed,
direct user permissions.

## Steps

1. `Open het juiste account` - search by name or username, open the correct
   user, and choose `Gebruiker aanpassen` on that user's detail page.
2. `Wijzig de standaardrol via Groepen` - only a Superadmin can add groups to
   this user or remove them. On desktop, use `Ctrl+klik` to add another group
   or deselect a selected group. Admin can view group membership but cannot
   change it.
3. `Pas rechten per gebruiker aan` - open `Machtigingen` and choose per right:
   - `Overnemen`: use the combined result of all selected groups; no direct
     user-specific choice is stored for that right;
   - `Toestaan`: grant this right directly to the user, even when no selected
     group grants it;
   - `Weigeren`: block this right directly for the user, including when a
     selected group grants it.
   A direct `Toestaan` or `Weigeren` choice takes priority over group rights.
   Admin and Superadmin can manage ordinary direct rights. Only Superadmin can
   change `Global: Super User`.
4. `Sla op en controleer opnieuw` - verify the groups on the user page. Reopen
   `Machtigingen` and confirm that the direct choices produce the intended
   combined access.

## Decision Rule

Use one or more standard groups when they represent the work. When several
users need the same direct choices, follow USR-05 and place those rights in a
reusable group instead of repeating them per user. An unclear right remains on
`Overnemen` until its description and effect are understood.

## Compact Help

- `Groepen vergrendeld` - only Superadmin can change group memberships.
- `Effect van recht onduidelijk` - leave `Overnemen` selected and read the
  permission description before changing it.
- `Meerdere gebruikers` - follow USR-05 Groepen beheren for a reusable group
  instead of repeating direct choices. The help tile renders this handoff with
  the USR family marker, full guide code/name, and family color.
- `Toegang te ruim` - check for both an old group and direct Toestaan entries.

## Evidence Manifest

| Label | Job | Source ID | Status |
| --- | --- | --- | --- |
| 1A | User search and recognizable user result | `USR-LIST-DESKTOP-01` | Captured 2026-08-11; search targeted; shared with USR-01 |
| 1B | Correct user's `Gebruiker aanpassen` action | `USR-DETAIL-DESKTOP-01` | Captured 2026-08-11; edit action targeted |
| 2A | Editable group selector and standard role choices | `USR-GROUP-EDIT-DESKTOP-01` | Captured 2026-08-11 in controlled Superadmin session |
| 3A | Permissions tab and Toestaan/Weigeren/Overnemen columns | `USR-PERMISSIONS-DESKTOP-01` | Captured 2026-08-11 |
| 4A | Saved user role and account detail | `USR-DETAIL-DESKTOP-01` | Captured 2026-08-11; shared with USR-01 |

## Complete When

The correct user's selected groups and direct rights combine to give the
intended access, without obsolete groups or unexplained direct overrides.

## Related Guides

- USR-01 Gebruiker toevoegen
- USR-05 Groepen beheren
- USR-04 Gebruiker uitschakelen of herstellen
- HELP-01 Problemen en hulp

## Version Notes

- v2 preserves the verified v1 workflow and evidence while migrating the page
  to shared badge, prerequisite, focus, completion, and full-reference
  components. Long headings and image badges no longer overlap.
- v3 removes approval and redundant stop wording, documents multi-group
  selection/deselection, distinguishes Admin from Superadmin capabilities, and
  explains `Overnemen`, `Toestaan`, and `Weigeren` in full.
- v4 separates user search/opening from the real `Gebruiker aanpassen` action,
  states explicitly that only Superadmin can add or remove group memberships,
  explains that direct choices take priority over group rights, and clarifies
  the second help label.
- v5 renders the `Meerdere gebruikers` help handoff as a complete,
  family-colored `USR-05 Groepen beheren` reference instead of leaving an
  unstyled code inside body text.
- v6 increases the USR-02 help-row height and gives the USR-05 handoff a
  dedicated lower line with clear border spacing. The accepted USR-01 layout
  and other guide pages that share the generator remain unchanged.
- v7 shortens the 3A direct-rights focus area to the three permission rows
  actually visible in the screenshot. Its complete lower stroke now remains
  inside the image frame.
