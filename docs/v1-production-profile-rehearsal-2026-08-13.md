# V1 Production Profile Rehearsal - 2026-08-13

## Result

The isolated local production profile completed successfully after three
release defects were found and repaired. This closes the first disposable
profile implementation rehearsal, but it is not approval to publish V1.

The development stack was left untouched. All database writes targeted the
disposable `snipeit_rehearsal` database in Compose project
`snipeit-v1-prodtest-20260813`.

## Candidate artifacts

- Application image:
  `local/inbit-app@sha256:495763177d2270b8df50fa452c9d9d2a929d097d10db65d72a89096601664c63`
- Web image:
  `local/inbit-web@sha256:341f2e0b5993145dc20e1af8a1cc2105764bc6f174aac52ee95f2cede3f9846d`
- MariaDB: immutable 11.4.7 image used by the release matrix.
- Redis: authenticated Redis 7.4 with persistent append-only storage.
- Public edge: pinned Nginx reverse proxy with a rehearsal-only self-signed
  certificate on loopback.
- SMTP substitute: pinned Mailpit on the private rehearsal network.

These are local candidate digests, not registry-published frozen-release
artifacts.

## Passed evidence

- Compose resolved app/web images by digest and kept the application root
  filesystems read-only.
- Required secrets were supplied through mounted files. Narrow proxy trust,
  forced TLS, secure cookies, HSTS, CSP, clickjacking, content-type, and
  referrer headers were observed at the HTTPS edge.
- A pristine database migrated through the complete history in 63.47 seconds.
  `ProductionFoundationSeeder` completed in 6.48 seconds and the CLI bootstrap
  administrator could authenticate to a 200 dashboard response.
- PHP-FPM ran with a root master and `www-data` request workers. Queue and
  scheduler application processes ran as `www-data`.
- Public and private upload markers survived forced app/web replacement. The
  public marker was retrievable through HTTPS; the private marker returned
  404 over HTTP.
- Redis-backed maintenance survived container replacement. During maintenance,
  `/health` returned 200 while the normal application returned 503. Activation
  restored the expected 302 login redirect.
- The application backup archive opened successfully and contained 20 entries,
  including the MariaDB dump plus both volume markers.
- The dump restored into a second empty MariaDB volume and produced 1 user, 1
  settings row, and 476 pre-change migration rows. Public and private uploads
  restored into new empty volumes with the expected marker contents.
- The immediately previous working application digest started successfully in
  maintenance mode with the database and uploads intact. Redeployment of the
  final digest and activation also succeeded.
- A real Redis queue job delivered an expiry-report message into Mailpit. The
  queue returned to zero and `failed_jobs` remained empty. The scheduler listed
  all seven configured entries.
- Repository image-content verification passed for app and web. Blocking
  high/critical Trivy scans with `ignore-unfixed` passed. The web result was
  clean; the app scanner displayed the known checksum-pinned Laravel advisory
  as status `fixed`, and the verifier confirmed its backport in the image.
- The final focused regression run passed 47 tests with 213 assertions.
- A separate final maintenance and documentation slice passed 25 tests with
  593 assertions, including functional maintenance-mode HTTP coverage. All
  eight rehearsal services reported healthy before cleanup.

## Defects found and fixed

1. The entrypoint dropped the PHP-FPM master to `www-data`, preventing it from
   opening `/proc/self/fd/2`. The master now remains root while the pool is
   explicitly configured to run requests as `www-data`.
2. Maintenance mode made the web health check return 503, blocking the
   documented pre-activation deployment sequence. The fork health exception
   is now active, and the HTTP kernel registers the fork middleware instead of
   Laravel's vendor class directly.
3. Expiry mailables used PHP 8.2 dynamic properties. Laravel queue
   serialization dropped those payloads, so delivery failed. Mailable state is
   now declared, serialization tests cover both expiry reports, and the other
   current mailables no longer rely on dynamic properties.
4. The configured `database-uuids` failed-job provider had no `failed_jobs`
   table. An additive migration and schema contract test now provide it.

## Limitations and remaining gates

- The TLS certificate was self-signed and the SMTP target was Mailpit with TLS
  disabled in the rehearsal override. A real certificate lifecycle and real
  SMTP relay with peer verification remain unproven.
- The database began empty. This does not replace the recent sanitized
  production-clone migration, interruption/retry, lock-impact, and matching
  database/upload rollback rehearsal.
- The rollback used the immediately previous compatible app image against the
  current disposable database. Database and upload restoration were proven in
  separate replacement volumes, not as a timed production cutover.
- External LDAP and the retained browser/operator role matrix remain open.
- The final code and migration changes post-date the last complete SQLite and
  MariaDB runs. The focused suite is green, but both complete guarded suites
  must be rerun on the frozen candidate.
- Source/image reports, SBOMs, and license inventories must be regenerated and
  retained by release CI for the published registry digests, then accepted by
  the named release and security owners.
- Focused PHPStan analysis exceeded its three-minute local execution limit
  without emitting a diagnostic. The earlier exact repository-wide CI command
  was green, but frozen-candidate CI must run it again.

## Disposition

Keep the public V1 decision at **no-go**. The next release step is a complete
guarded test run of the current tree, followed by the recent sanitized
production-clone upgrade/interruption/restore rehearsal and external service
checks.
