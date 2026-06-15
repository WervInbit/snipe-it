# 2026-06-10 Session Init

## Startup Context
- Reinitialized on `master` at `cc510d859` after reviewing `AGENTS.md`, current `PROGRESS.md`, `docs/fork-notes.md`, `docs/agents/agents-addendum-2026-06-09-session-init.md`, and `docs/agents/e2e-rehearsal-2026-06-08.md`.
- Local `master` still matches `origin/master` from the last pull; no new remote movement was detected during this reinit pass.
- Current tracked local edits at session start are documentation notes from the 2026-06-09/2026-06-10 reinit and the LAN-enabled `docker-compose.localhost.yml` override.
- Existing local-only untracked material remains `prodbak/` and `storage/debug-workorder.php`; these should stay out of commits unless explicitly requested.

## Runtime State
- Docker was stopped at reinit, so the project was not reachable at first.
- Restarted the local stack with `docker compose -f docker-compose.yml -f docker-compose.localhost.yml up -d --no-build`.
- Cleared Laravel caches and verified the app reports `APP_ENV=local` with no pending migrations.
- Nginx initially returned `502 Bad Gateway` after startup until the `web` container was restarted. After that restart, both `http://127.0.0.1:18080/login` and `http://192.168.178.79:18080/login` returned HTTP 200.
- The active local runtime is the LAN-enabled `snipeit` database setup. It is not the earlier `dev.inbit` / `snipeit_prod_work` production-clone profile.

## Immediate Testing Focus
- Continue detailed manual testing through the interface, including scan/QR and physical camera behavior where possible.
- Watch for browser secure-context restrictions: physical phone camera access may be blocked on plain LAN HTTP even when the page loads. If that happens, set up HTTPS or a trusted local hostname before treating scanner behavior as an app bug.
- Carry forward known follow-ups from the rehearsal: asset-create model-number spec preload, optional enum persistence for `Opslagtype`, stale expected-component empty-state after save, audio/camera catalog wording cleanup, component QR printing after movement, mobile Components tab usability, and workflow history labels showing actual profile names instead of generic `Testronde`.
