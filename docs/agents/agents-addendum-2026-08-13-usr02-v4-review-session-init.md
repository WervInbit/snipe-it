# Session Addendum - 2026-08-13 USR-02 v4 Review

## Context

- Continued from USR-02 v3 after focused review of user search/edit evidence,
  help wording, direct-right precedence, and the group-membership boundary.
- Verified in application code that adding or removing group membership is
  Superadmin-only for individual and bulk user updates. Admin and Superadmin
  can manage ordinary direct per-user rights within the enforced boundary.
- Inspected the unannotated user-list and user-detail sources before changing
  any crop or target.

## Scope

- Add separate search/open and edit-action visuals to step 1.
- Clarify the second help title.
- State direct-right priority over inherited group rights.
- Clarify exactly what Superadmin-only group changes mean.
- Generate and verify USR-02 v4 without changing v3 or accepted guides.

## Safety

- Existing screenshots are reused without visible credentials or new account
  changes.
- No application state, database, service, accepted artifact, Git index, or
  branch is changed.
