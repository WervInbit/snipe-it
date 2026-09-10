# USR-05 Groepen Beheren

| Field | Current value |
| --- | --- |
| Status | Planned; workflow and evidence still need investigation |
| Family | USR |
| Type | Administration task |
| Current version | `planned` |
| Page model | Unknown until workflow investigation |
| Layout recipe | `unassigned` |
| Generator | Not assigned |
| Role | Superadmin |
| Needed | Approved reusable role definition and minimum required rights |
| Prerequisite | Ingelogd (AC-01 Login) with Superadmin access |

## Purpose

Create a reusable user group or change an existing group's rights without
granting unrelated functions to every member of that group.

## Scope To Verify Before Drafting

1. Open the group-management page from the administration navigation.
2. Check whether an equivalent group already exists before creating another.
3. Name the group according to the approved role or responsibility.
4. Grant only the rights required by that reusable role.
5. Save and verify the group definition.
6. Test the resulting access with a controlled user before wider assignment.

## Boundaries

- USR-02 owns assigning a group or direct-rights exception to one user.
- USR-05 owns the reusable group definition shared by multiple users.
- Do not duplicate a direct-rights exception across several users when an
  approved reusable group is the intended policy.
- Exact controls, help routes, stop conditions, screenshots, and final wording
  remain unapproved until the live workflow is investigated.

## Related Guides

- USR-01 Gebruiker toevoegen
- USR-02 Rol en rechten wijzigen
- AC-01 Login
- HELP-01 Problemen en hulp
