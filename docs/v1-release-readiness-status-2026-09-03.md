# V1 Release Readiness Status - 2026-09-03

## Decision

The repository owner designated the exact application revision and image
digests deployed on 2026-09-01 as the internal V1.0.0 production baseline on
2026-09-03. No unresolved severity-1 or severity-2 application defect was known
at that boundary. The owner accepts the remaining small fixes, additions,
manual work, representative private-file check, and named operational ownership
as follow-up work rather than reasons to keep the deployed system labeled
pre-V1.

A refreshed dependency audit on 2026-09-03 found advisories disclosed after
the V1.0.0 images were built. The current source tree contains those fixes and
passes its supported local gates, but it is post-V1 development and is not part
of the immutable V1.0.0 artifact. It must be separately committed, qualified,
and versioned before a later deployment.

LDAP and SMTP are deliberately disabled and unsupported for V1. PHPStan is
deferred and is not a V1 gate.

## Candidate And Repository Identities

- V1.0.0 was built from source commit
  `1c9131f4c92a02ba75a230cf57ede3b58c5a19ac`.
- The annotated source tag `v1.0.0` points to that exact commit locally; remote
  publication remains a separate repository action.
- Reusable production deployment profiles were added and pushed at
  `fe4d0b4fe85c28b9f7e23db116e1e9212c6ffaa3` after the successful cutover and
  are therefore post-V1 repository improvements.
- The local branch currently includes the separate guide-owned commit
  `cf13808744` and an uncommitted dependency/CI/release-record refresh. Neither
  changes the V1.0.0 identity, and `origin/master` remains at `fe4d0b4fe8`.
- The temporary production system is intentionally treated as production
  because its 17 migrated users and operational data must survive the later
  move to the final server.

The authoritative V1.0.0 runtime identities are:

- app:
  `127.0.0.1:5000/inbit/snipeit-app@sha256:3ee7aa742c2644c67379f1647b54b3f557cc8ab027eb47dd2ec84b165d8e28cc`;
- web:
  `127.0.0.1:5000/inbit/snipeit-web@sha256:7ecca509adb8293590fdbe4b9368bbafde2a9d961b9c1223a9d1385feb8f2fc9`.

The deployed images retain the earlier `v0.9.0-dev` value embedded in
`config/version.php`. This historical display metadata does not change the
tag/digest identity and will not be rewritten on the protected production
server.

## Completed Qualification

### Retained deployed-candidate gates

The Supervisor-capable application source passed:

| Gate | Retained result |
| --- | --- |
| Guarded non-LDAP SQLite | 2,166 tests, 10,553 assertions |
| Guarded disposable MariaDB 11.4.7 | 2,166 tests, 10,553 assertions |
| Composer | strict validation, locked install/audit, patch checks green |
| npm | production and complete graphs have zero high/critical findings |
| Production images | build, content, asset, SBOM, license, and blocking scans green |
| Image security | zero fixable high/critical findings and zero embedded secrets |
| HTTPS profile | login, TLS redirect, headers, cookies, health, and cold restart green |

The three ignored Laravel advisory identifiers remain covered by the exact
checksum-pinned official backports in `patches/`. Moderate/low inherited
front-end findings remain recorded but do not include an unmitigated
high/critical release-owned issue.

These results qualify the source and images deployed on 2026-09-01 and now
designated V1.0.0. Later lockfile and published Livewire-client changes belong
to the post-V1 development line and do not alter that historical artifact.

### Post-V1 dependency-refreshed source gates

The current worktree includes the following remediation:

- `livewire/livewire` was updated from 3.6.4 to 3.8.7, above the 3.8.3 patched
  minimum for GHSA-g3hc-697w-wm82, and its published client assets were
  regenerated.
- Locked `fast-uri` was updated from 3.1.5 to 3.1.7 and both locked
  `postcss-selector-parser` lines were updated to 6.1.4/7.1.5.
- The complete SQLite, MariaDB/MySQL, PostgreSQL, and combined V1 CI jobs now
  install PHP's LDAP extension explicitly before running mocked LDAP tests.

Current results:

| Gate | Refreshed result |
| --- | --- |
| Guarded non-LDAP SQLite | 2,170 tests, 10,641 assertions |
| Focused Livewire | 30 tests, 516 assertions |
| CI infrastructure contract after CI edit | 8 tests, 154 assertions |
| Composer | strict validation, patch diagnostics, and locked audit green |
| npm | clean install; complete and production graphs have zero high/critical findings |
| Node/browser assets | 4 tests pass; production asset build passes |
| Browser Livewire smoke | importer and non-persistent category update pass; no warning/error |
| Provisional production images | app/web build and content verification pass; both contain Livewire 3.8.7 assets |

The unfiltered local PHPUnit invocation passed 2,178 tests and failed ten LDAP
group cases solely because the five-week-old local app image predates the LDAP
extension addition and therefore lacks `LDAP_OPT_REFERRALS`. All LDAP cases are
grouped, the supported non-LDAP gate is green, and the CI workflows now declare
the extension rather than relying on runner defaults. This does not promote
real LDAP integration into the V1 support matrix.

The production-image result is a build smoke test from the current worktree,
not a release artifact. It has no committed source identity, SBOM, blocking
image scan, or publishable digest and must not be deployed as a later release
until separately qualified.

### Populated migration and live cutover

On 2026-09-01 the data-bearing legacy environment was backed up, restored into
an isolated candidate stack, migrated, tested, and cut over under a rollback
boundary.

- The database advanced from 468 to 477 migrations with none pending.
- The migration retained 17 users/15 active users, 12 assets, 14 models,
  6 workflow profiles, 29 workflow items, and zero failed jobs.
- Public/private upload parity remained exact at 294/14 files.
- Preflight, maintenance-mode final, and post-cutover backup sets have verified
  SHA-256 inventories and protected off-host copies.
- The old app/web deployment is stopped and restart-disabled, but its database,
  volumes, checkout, configuration, and rollback record were not deleted.
- No Snipe-IT data or Docker volume was pruned.

Temporary Refurbisher, Senior Refurbisher, Supervisor, and Admin accounts were
used for the browser permission/route matrix before cutover. All four landed on
the dashboard, supported and denied routes matched the role contract, workflow
visibility was correct, and the test accounts plus their exact audit/login and
generated-file artifacts were removed with count/sequence parity restored.

After cutover, the owner successfully signed in through `snipe.inbit` using an
existing migrated account. This closes the unchanged-password-hash acceptance
gate without exposing or resetting the password.

### Last authorized production health check

A read-only check on 2026-09-03 found the app, web, queue, scheduler, MariaDB,
Redis, and TLS edge healthy. HTTPS login and health returned successfully,
there were no pending migrations or failed jobs, and the retained database
counts still matched the post-cutover baseline. Recent service logs before the
check contained no runtime errors. The queue worker's approximately hourly
restart is expected from its configured `--max-time=3600` lifecycle and restart
policy, not a crash loop.

One read-only diagnostic invocation attempted to start PsySH as root and was
refused because its read-only filesystem could not create `/root/.config`.
That probe produced one known log error, changed no database/application data,
and was replaced with a direct read-only MariaDB query. Per owner direction on
2026-09-03, no further production access, inspection, browser check, query, or
mutation is permitted without new explicit permission.

## Accepted Post-V1 Follow-Up

1. **Representative migrated-data acceptance.** Use an existing migrated role
   account to complete one representative operational workflow, including a
   private file/evidence read where applicable, and confirm the expected
   history and permissions. The password-login portion is already complete.
2. **Operator-manual alignment.** Finish or explicitly defer the separate
   operator-guide acceptance work, and confirm that instructions for supported
   V1 paths match the frozen application. This work is owned by the separate
   guide session and is not mixed into implementation commits here.
3. **Next exact candidate.** Select one reviewed post-V1 commit; run the full
   SQLite and MariaDB quality workflow for that exact revision; build, scan,
   publish, deploy, and record new immutable app/web digests. The dependency-
   refreshed source has not yet repeated MariaDB or production-image gates.
4. **Release control maturity.** Name the long-term release, rollback, and
   incident/security owners and publish the internal support process.
5. **Final-server launch acceptance.** Before moving the retained data again,
   validate the final host profile, TLS/proxy, monitoring/log ownership,
   capacity, off-host backup, and restore path. This is a deployment gate for
   the final server, not evidence of a current application defect.

## Non-Blocking And Deferred Work

- Real LDAP directory and SMTP/TLS relay validation are post-V1 unless the
  owner deliberately changes the support boundary. Both integrations remain
  disabled and fail closed in the V1 production profile.
- PHPStan is deferred because its schema-sensitive result is not reproducible.
  It must not drive V1 application changes.
- The QR/label designer, Windows battery/SMART/install tooling, richer media
  UX, vault-backed repair credentials, and migration snapshot/squash work are
  post-V1.
- Five known synthesized demo model-number codes survived from the legacy
  database. A read-only 2026-09-03 check found zero assets on all five, although
  the rows are not deprecated and retain old seed-owned attributes/templates.
  Current production seeding already excludes these codes. Replacing them
  requires authoritative manufacturer identifiers; deprecating the unused
  legacy rows is an optional, explicitly approved data-cleanup action and is
  not a V1 code blocker.

## Release Decision

- **V1.0.0 baseline:** approved by owner direction on 2026-09-03 for the exact
  source commit and image digests recorded above.
- **Distribution status:** internal operational release, not a generally
  supported public distribution.
- **Known severity-1/2 defect at designation:** none.
- **Current source:** post-V1 development; not approved for deployment.
- **Next engineering action:** commit and qualify the dependency/CI refresh as
  a separate patch or minor release candidate.
- **Production boundary:** do not access or change production without new
  explicit owner permission.
