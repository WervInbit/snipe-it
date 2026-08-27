# V1 Qualification Resume - 2026-08-25

## Context

- Resumed the August 20 populated-data qualification after the workstation
  suspended the active MariaDB test containers.
- Preserved the immutable beta export, the populated primary rehearsal, the
  dirty worktree, and the separate manual team's artifacts.
- Continued to exclude real LDAP, real SMTP/TLS, and PHPStan from the V1 gate.

## Completed Evidence

- The exact-candidate non-LDAP SQLite suite passes 2,160 tests with 10,445
  assertions in 34:40.
- The exact-candidate disposable MariaDB 11.4.7 suite passes 2,160 tests with
  10,448 assertions in 48:28. The workstation suspension paused, rather than
  restarted, the containers; MariaDB remained healthy and the active run
  completed with exit code 0.
- Composer validation, locked dry-run installation, and the current advisory
  gate pass. The three reported Laravel advisories remain the documented,
  checksum-pinned framework-patch exceptions.
- Both npm high-severity gates pass. The Node regression suite passes four of
  four tests; moderate/low Bootstrap and legacy build-chain findings remain
  documented debt.
- The populated rehearsal runs the sodium-fixed immutable app/web digests,
  retains 477 applied migrations with none pending, and preserves database and
  upload parity across promotion and a controlled cold restart.
- A separately named clone proved nine-migration forward application, exact
  batch-2 rollback, baseline parity, and reapplication. Its disposable
  resources and secrets were removed after verification; the immutable export
  can recreate them.
- The rehearsal runtime generator now refuses to overwrite managed runtime or
  secret files unless `-Force` is supplied explicitly. Its focused test passes
  two tests with 32 assertions.

## Remaining Release Boundary

- The manual review records a newer owner requirement that Supervisor can
  complete the new-product/catalog setup. Current source does not satisfy it:
  the foundation role lacks model permissions, workflow configuration remains
  under a superuser route group, granular workflow-item permissions are not
  registered, and catalog lifecycle/destructive authority is not separated.
- A real browser/operator role matrix and one migrated user's existing-password
  login remain owner-operated acceptance gates.
- Managed-release deployment, off-host backup/restore, monitoring ownership,
  version/changelog metadata, and final release approval remain open.
