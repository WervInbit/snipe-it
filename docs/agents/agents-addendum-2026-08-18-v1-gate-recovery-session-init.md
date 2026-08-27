# V1 Gate Recovery Session Init - 2026-08-18

## Recovery context

- The previous V1 validation session ended unexpectedly when the workstation
  lost power during PHPStan classification.
- The repository remains on `master` at `64407b05d89c189c2013bcb491c97d3006dd5841`.
- The intentionally broad dirty worktree is preserved. No unrelated guide,
  application, database, or runtime changes will be reverted.

## Retained verified evidence

- Guarded in-memory SQLite excluding the `ldap` group: 2,128 tests and 10,198
  assertions passed.
- Guarded private MariaDB 11.4.7 database named exactly `snipeit_test`, also
  excluding the `ldap` group: 2,128 tests and 10,198 assertions passed.
- Composer strict validation, patch diagnostics, and locked advisory audit
  passed.
- The full PHPStan rerun produced 97 findings and retained JSON/text evidence;
  classification was in progress at interruption.

## Safety and continuation boundary

- Real LDAP and SMTP/TLS validation remain explicitly deferred by the user.
- The shared `snipeit_db` database must not be migrated, reset, refreshed,
  wiped, or seeded.
- The private MariaDB gate has no published port and exists only for retained
  evidence/cleanup. It will be removed after recovery work no longer needs it.
- Continue from PHPStan classification, then rerun Node/audit/assets and final
  production-image verification/scans. Repeat the long database suites only if
  relevant source changed after their retained green runs.

## Completed recovery

- Confirmed no relevant source file changed after the retained green SQLite
  and MariaDB runs, so the 2,128-test/10,198-assertion results remain current.
- Removed orphaned PHPStan workers left by the interrupted session. A clean
  PHP 8.2.32 reproduction confirmed stale baseline mappings; baseline-free
  analysis now measures 2,779 errors instead of 5,817. The refreshed exact
  gate is green and a removed negative control fails as required.
- Composer, npm, Node, production assets, source scanning, final production
  image builds, content verification, image inventories, SBOMs, license
  reports, and blocking image scans all completed.
- The final app/web blocking scans report no fixable high/critical findings and
  no secrets. The complete app inventory retains 71 unfixed Debian findings;
  the web inventory has none.
- The final release/infrastructure regression passes 42 tests with 848
  assertions. Real LDAP and SMTP/TLS remain deferred, as requested.
- Removed all private audit containers, networks, and dependency/source
  volumes, including the isolated `snipeit_test` MariaDB and the orphaned
  no-port production-test container. The three shared development services
  remain running and were not restarted or modified.
