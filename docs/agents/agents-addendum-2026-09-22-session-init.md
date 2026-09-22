# Agent Session Addendum - 2026-09-22

## Scope

- Implement the approved refurbisher follow-up features in an isolated worktree.
- Preserve the primary checkout's unrelated operator-guide batch.
- Leave server migration, production deployment, live configuration, and manual
  Dutch/workflow content to their separately assigned owners.

## Approved Boundaries

- Ordinary Refurbisher cannot create assets or edit quality. Senior
  Refurbisher, Supervisor, and Admin can do both.
- Status names and access rules are editable; locked exits use lifecycle stages.
- Sold, Broken/Parts, and manually created Afgevoerd are Admin-only exits.
- Klaar returns to the asset Workflows tab and completed workflow rows render
  correctly, followed by an all-workflows-complete message.
- Remember Me stays available on desktop and mobile.
- Dashboard scanner wording is `Scan QR`; serial OCR is outside scope.

## Safety

- Use focused tests with `APP_ENV=testing`, `DB_CONNECTION=sqlite`, and
  `DB_DATABASE=:memory:` at the command boundary.
- Do not run destructive database commands or modify production.

## Outcome

- Application work is implemented on `codex/refurbisher-followups-2026-09-22`
  in an isolated worktree. The primary checkout and the separately assigned
  server task were not changed.
- Added compact Refurbisher account controls, role-aware dashboard actions and
  recent devices, status access/note controls, locked-exit confirmation,
  dedicated quality authorization, workflow completion behavior, focused
  post-create results, password baseline rules, serial placement, and the
  confirmed hidden/deprecated attribute lifecycle repair.
- Kept Remember Me on every login layout. Reused the existing browser-camera
  scanner; the remaining camera work is device/browser permission setup, not a
  new scanning entry point.
- Kept Dutch status naming, the access matrix, Afgevoerd, and the local
  programmable-key/history/iGPU/CPU/cleaning content as manual configuration.
  See `docs/refurbisher-follow-up-configuration.md`.
- Guarded SQLite verification passed 192 focused tests / 1,048 assertions in
  the feature and asset API boundaries. PHP lint, focused new-file PSR-12, the
  production webpack build, and `git diff --check` pass. The generic Node suite
  retains one unrelated stale dependency-volume marker failure.
