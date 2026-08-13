# USR-03 Wachtwoord Resetten

| Field | Current value |
| --- | --- |
| Status | Working draft v1 with captured real interface evidence; awaiting review |
| Family | USR |
| Type | Administration task with two alternatives |
| Current version | `usr-03-wachtwoord-resetten-v1-draft` |
| Page model | One page |
| Layout recipe | `stacked-step-flow` with `parallel-visual-choice` |
| Generator | `scripts/manuals/generate-user-account-guide-review.mjs` |
| Role | Admin or Superadmin |
| Needed | Correct active local account and an approved secure handoff route |
| Prerequisite | User identity verified |

## Purpose

Restore access to the correct local account without learning or retaining the
user's final private password.

## Steps

1. `Controleer het account` - open the user and verify name, username, active
   state, email, and whether the account is local or LDAP-managed.
2. `Kies een resetroute` - for an active local user with a valid email address,
   choose `Stuur een wachtwoordherstellink`. When that route is unavailable,
   choose Edit, use `Genereer`, confirm the generated password, and save once.
   Never reuse a temporary password.
3. `Draag veilig over` - send the reset link through the system or give the
   temporary password through the approved secure channel. Do not put it in
   ordinary email, chat, notes, tickets, screenshots, or the guide.
   When an administrator set a temporary password, the user follows AC-02
   immediately. This application has no force-change-at-next-login checkbox,
   so the handoff must be confirmed by procedure.

## Step-Specific Stops

- Step 1: stop when the identity is uncertain or the account is LDAP-managed.
  LDAP passwords are changed through IT or the directory service.
- Step 3: stop when no approved secure transfer channel is available. Never
  ask the user to disclose the final password as proof.

## Compact Help

- `Geen e-mailadres` - use the generated temporary-password route.
- `Resetlink komt niet aan` - verify the existing address; do not invent or
  silently replace it.
- `LDAP-account` - contact IT; do not set a local password.
- `Tijdelijk wachtwoord gebruikt` - continue with AC-02 immediately.

## Evidence Manifest

| Label | Job | Source ID | Status |
| --- | --- | --- | --- |
| 1A | User identity, active state, email, and local/LDAP context | `USR-DETAIL-DESKTOP-01` | Captured 2026-08-11 |
| 2A | Send-password-link action on the user page | `USR-RESET-LINK-DESKTOP-01` | Captured 2026-08-11; action not submitted |
| 2B | Edit-user Generate control with password value hidden | `USR-EDIT-PASSWORD-DESKTOP-01` | Captured 2026-08-11 |
| 3A | AC-02 guide reference and self-change start | `AC-ACCOUNT-MENU-DESKTOP-01` | Captured 2026-08-11; shared with AC-02 |

## Complete When

The correct user has received a reset link or one generated temporary password,
and a temporary-password route explicitly continues with AC-02.

## Related Guides

- AC-01 Login
- AC-02 Eigen wachtwoord wijzigen
- USR-01 Gebruiker toevoegen
- HELP-01 Problemen en hulp
