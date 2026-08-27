# 2026-08-20 Populated V1 rehearsal and qualification

## Scope

- Restore the checksum-verified beta database and public/private uploads into
  an isolated local rehearsal stack.
- Preserve the original export as immutable input and retain the legacy
  `APP_KEY` and Passport keys without logging or committing them.
- Run forward migration, parity, interruption/retry, rollback, full guarded
  SQLite and MariaDB suites, production/build gates, and active browser paths.
- Create clearly marked rehearsal-only accounts only after baseline parity is
  recorded. An existing migrated password will be verified manually by the
  owner without sharing it.
- Fix defects found in supported paths and rerun every invalidated gate.

## Boundaries

- Do not modify, migrate, reset, seed, or restore into the shared development
  database or the source beta database.
- Do not use destructive database reset commands. Rebuild disposable rehearsal
  state only by restoring the immutable export into newly named resources.
- Real LDAP and SMTP/TLS are post-V1. Their disabled production behavior must
  remain functional and visible; mock/local behavior does not promote support.
- PHPStan remains deferred and will not be run.
- Deliberately removed, hidden, or disabled legacy mutations need only remain
  inaccessible; they are not positive V1 workflows.
- Do not change the separate manual agent's artifacts except for an explicit
  application/manual compatibility record if a supported workflow differs.

## Imported evidence

- Export:
  `C:\dev\snipeit-rehearsal-data\snipeit-export-20260820T085448Z`.
- Six files match the supplied SHA-256 manifest: database dump, public upload
  archive, private upload archive, legacy environment, and both Passport keys.
- Sensitive contents remain outside the repository and Docker build context.

## Restart checkpoint

- The focused rehearsal configuration test passes: 2 tests, 22 assertions.
- Current-source app and web production images built successfully at immutable
  local digests recorded in `PROGRESS.md`.
- The first Compose preflight stopped before launch because the runtime
  generator separated several `KEY=path` expressions into two lines. The
  PowerShell generator and its regression test are patched; the corrected
  runtime file has not yet been regenerated or validated.
- Resume by rerunning `prepare-runtime.ps1`, checking every generated path
  variable has a non-empty value, and running `docker compose ... config
  --quiet`. Only then create the uniquely named rehearsal database, Redis, and
  upload volumes.
- There are no rehearsal containers or volumes to stop or recover. The shared
  development stack remains running and its database was not modified.

## Completion

- The corrected runtime was generated outside the repository and the immutable
  export was restored into a uniquely named MariaDB 11.4.7 production-profile
  rehearsal without touching the shared development database.
- The populated rehearsal was upgraded to 477 migrations, promoted to the
  sodium-fixed candidate images, and passed data/upload parity plus a controlled
  cold restart.
- A second disposable clone passed forward migration, exact batch-2 rollback,
  baseline fingerprint parity, reapplication, full service health, and HTTPS
  smoke. It was removed after verification.
- Final exact-candidate evidence and remaining release blockers are recorded in
  `docs/v1-release-readiness-status-2026-08-25.md` and the August 25 session
  addendum.
