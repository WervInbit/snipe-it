# Agent Addendum - 2026-08-11 Operator Guide Continuation

## Scope

- Re-establish the exact guide inventory and approval state.
- Identify review-ready guides, evidence gaps, and the next efficient
  generation batch.
- Do not change application state or regenerate artifacts during this status
  pass.

## Recovered State

- Approved: AC-01 v6, SC-01 v10, AST-02 v5, WF-01 v9, WF-02 v10, CMP-01 v4.
- Awaiting review with complete evidence: CMP-02 v2, CMP-04 v5, HELP-01 v6.
- Evidence/decision incomplete: AST-03 v1, AST-04 v1, AST-05 v1.
- Retired: AST-01 into SC-01; CMP-03 into CMP-02.
- Deferred beyond the first floor set: user management, work orders,
  catalog/configuration guides, and broad destructive asset lifecycle actions.

No screenshot, PDF, generator, application, database, test, staging, branch, or
commit operation was performed.

## USR-01 Continuation

- Added the focused two-sided USR-01 specification for account creation,
  Refurbisher/Admin group assignment, approved direct-rights exceptions, and
  password reset/replacement.
- Recorded the username convention as first name with an initial capital plus
  the lowercase first letter of every last-name part, with no periods or
  spaces. Username collisions require an approved exception.
- Confirmed from application code that group membership is Superadmin-only,
  direct permissions default to inheritance, direct denial overrides group
  grants, reset links require an active local user with email, and no
  force-change-at-next-login control exists.
- The controlled capture site refused connections in both the in-app browser
  and a direct HTTP check. The active Docker containers belong to a separate
  MariaDB audit, so they were not stopped or repurposed.
- No screenshot, PDF, generator, user account, permission, password, database,
  container, application, Git index, branch, or commit was changed. USR-01 is
  specification-complete but evidence-blocked.

## Focused User-Account Guide Split

- Replaced the overloaded combined USR-01 contract with five focused
  specifications: USR-01 add user, USR-02 change role/rights, USR-03
  administrator password reset, AC-02 user self-change, and two-sided USR-04
  deactivate/delete/restore.
- Reused planned evidence IDs across guides for the user list, group selector,
  account detail, and account password menu. Added exact planned states for
  permissions, reset links, generated temporary passwords, deactivation,
  deletion, and restoration.
- Verified the exact Dutch reset-link and check-in/delete labels, the
  self-service route and three password fields, logout-other-devices behavior,
  deletion guards for assignments and management relationships, and the
  restore action.
- `https://dev.inbit/` still refused connections and only the separate MariaDB
  audit database container was running. No shared service was started and no
  placeholder PDF was produced.

## User-Account Review Batch

- After the local development stack was restored, created one reversible
  fictional account state and captured the real Dutch user-management
  interface. No real account, visible password value, migration, database
  reset, or seeder was used.
- Added separate state-preparation, screenshot-capture, and guide-generation
  scripts under `scripts/manuals/` so evidence and layout can be reproduced
  independently.
- Generated USR-01, USR-02, USR-03, AC-02, and two-sided USR-04 as a six-page
  review batch at `output/pdf/operator-guides-user-account-review-v1.pdf`.
  Individual editable and proof artifacts are in
  `C:\Users\Gebruiker\Documents\snipe-it manuals\layout-proofs\2026-08-11-user-account-review-batch-v1`.
- Recovered from the desktop app/browser interruption without losing captured
  evidence. Replaced the unstable final browser capture path with a local
  reproducible Playwright capture helper using the same controlled service.
- Final evidence state: the fictional user is restored with login disabled;
  the administrator locale is back to `en-US`; the development services remain
  running for review or subsequent guide work.
- Syntax, page-count, A4 geometry, text, forbidden-string, rasterization, and
  full visual checks passed. All five guides remain `Generated draft; awaiting
  review`; none received an approval record.
