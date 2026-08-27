# V1 Release Readiness Status - 2026-08-13

## Decision

**Current decision: no-go for a public V1 tag; continue release-candidate
development.**

The ordered remaining work and release exit criteria are maintained in the
[V1 remaining implementation plan](plans/v1-remaining-implementation-plan-2026-08-18.md).

The retained supported MariaDB application suite is green. Current Composer
and npm advisory checks exposed and then closed newly published high-severity
dependency findings. PHPStan results are not reproducible in a clean matching
container, but the owner has deferred that analyzer and it is no longer a V1
release gate. These results remove major code-owned blockers from the
[2026-08-04 status](v1-release-readiness-status-2026-08-04.md), but they do not
replace the remaining frozen-candidate, migration, release-environment, and
owner approvals.

No shared database was reset, migrated, seeded, or otherwise modified for this
continuation. MariaDB verification used private ephemeral 11.4.7 containers,
the exact disposable database `snipeit_test`, and the executable external-test
guard. The shared application services used by the separate manual session
were not restarted or changed.

## Current Automated Evidence

### 2026-08-18 recovery rerun

- The interrupted post-rehearsal gate was recovered from retained logs. No
  application, configuration, database, test, or documentation file changed
  after the green database evidence was produced.
- Guarded in-memory SQLite and private MariaDB 11.4.7 `snipeit_test` runs,
  both explicitly excluding the unavailable `ldap` group, each pass **2,128
  tests with 10,198 assertions**. Neither run accepts skipped or incomplete
  tests, and neither contacts SMTP.
- Composer strict validation, patch diagnostics, and the locked advisory audit
  pass. Full and production npm audits have no high/critical findings, all four
  Node contract tests pass, and a clean production asset build succeeds.
- The exact PHPStan command is green after refreshing stale baseline mappings.
  A baseline-free measurement records **2,779 level 4 errors**, down from
  5,817; a removed negative control still fails with one return-type error.
- The source snapshot has zero high/critical vulnerabilities, secrets, or
  unignored misconfigurations. Root-user exceptions apply only to the legacy,
  unsupported, and local-development Dockerfiles; the production Dockerfile
  remains unignored.
- Final local production images pass repository-content verification, SBOM and
  license generation, complete high/critical inventory, and blocking
  `ignore-unfixed` scans. The application inventory contains 71 unfixed Debian
  findings and no secrets or fixable high/critical findings; the web image has
  zero high/critical findings and zero secrets.
- The final release-configuration regression passes 42 tests with 848
  assertions. Retained recovery logs and reports use the `20260818` suffix
  under `storage/logs/`.

### 2026-08-18 post-recovery authentication delta

- Intended redirects now survive required two-factor challenges and initial
  two-factor enrollment. Direct regular-user and administrator login still
  falls back to the dashboard; deliberately requested protected Settings and
  QR/scan links resume after authentication. Google login now observes the
  same intended-target contract, and logout clears obsolete redirect state.
- Guarded in-memory SQLite authentication coverage passes **44 tests with 158
  assertions**. The dedicated redirect regression passes **6 tests with 41
  assertions**. No shared database was migrated, seeded, or otherwise changed.
- Historical note: the exact PHPStan command is not currently reproducible
  outside the retained green run: both the shared development container and a read-only,
  network-isolated testing container report 3,037 existing ORM
  dynamic-property findings that are absent from the 2,779-entry baseline.
  No redirect-specific type/control-flow finding was identified. The owner has
  deferred analyzer work until after V1; do not run it, refresh the baseline,
  or change runtime code for this discrepancy during current release work.
- This source delta postdates the retained full SQLite/MariaDB and final-image
  evidence. Those expensive gates remain useful prior evidence but must be
  repeated after the current code-change batch is frozen.

### 2026-08-18 attachment authorization and scope delta

- Private asset files and asset-model resources now use independent
  view/upload/delete permissions instead of inheriting ordinary asset/model
  access. The asset page and API enforce the same policy, and the license tab
  no longer offers check-in without `licenses.checkin`.
- The guarded attachment, license, group-permission, role-matrix, and asset-page
  suite passes **45 tests with 456 assertions**. No shared database or service
  was changed. A final model-file-tab follow-up passes **4 tests with 33
  assertions**.
- V1 ships the current QR layout. The configurable printer/sticker/resolution
  label builder and hardened Windows battery/smart diagnostic ingestion are
  post-V1. Workflow Profiles and Workflow Items are the canonical configurable
  vocabulary; legacy `Test*` compatibility remains.
- The production cutover must migrate the complete database and both upload
  stores. Existing `users.password` hashes, IDs, group membership, direct
  permissions, and history are preserved; no plaintext passwords or user
  reseeding is involved. The matching APP key remains required for encrypted
  application data.
- This source delta also postdates the retained full-suite/image evidence and
  is part of the batch that must be frozen before the final gates run.

### 2026-08-18 disabled LDAP/mail implementation delta

- The production profile now explicitly sets
  `LDAP_INTEGRATION_ENABLED=false`, `MAIL_ENABLED=false`, and
  `MAIL_MAILER=array`; SMTP host, sender, and password-secret requirements were
  removed from the disabled candidate profile.
- The LDAP runtime gate overrides a stale enabled database setting across
  login, connection, import, sync, troubleshooting, tests, and administrator
  UI. Local login remains available and attempts to enable LDAP while the
  environment gate is closed fail validation.
- Mail-disabled mode suppresses notification and direct-mail delivery without
  using the log transport. Self-service reset, administrator reset email,
  inventory email, and mail-test actions are hidden or rejected with an
  explicit unavailable response. Administrators retain the protected user
  editor for controlled temporary-password recovery.
- The new disabled-integration suite passes **5 tests with 38 assertions**.
  Existing LDAP settings, password-reset authorization, inventory-email,
  mail-address security, and production-configuration coverage contributed 45
  passes with 386 assertions. The only attempted broader failure was the known
  shared-image absence of PHP LDAP constants in an extension-dependent mocked
  test; no real directory was contacted.
- This is source-level implementation evidence. A production-image/profile
  rehearsal and the frozen-candidate suites remain required before the LDAP
  and mail disabled-mode checklist entries can close.

### 2026-08-18 license and media-boundary delta

- License product keys are now removed from unauthorized API list output,
  general search, exact filters, sorting, reports, and both CSV export paths.
  License attachment controls use the dedicated file permission, and direct
  routes remain policy-gated.
- License seat check-in now records the actual asset target when a legacy seat
  contains both assignment columns. Bulk check-in locks and clears every seat
  exactly once with one audit record per seat; API check-in now rejects a
  non-reassignable entitlement like the web path.
- Public gallery upload and workflow-evidence promotion use the same explicit
  image-publish ability. Private evidence retains controlled reads and is
  removed when its workflow run is deleted. Asset soft-delete/restore no longer
  destroys the cover/gallery file and preserves private evidence.
- Application warnings distinguish public gallery media, controlled private
  attachments, and controlled workflow evidence, and forbid use of these
  surfaces as password/customer-credential storage.
- Guarded focused SQLite evidence passes 26 license/file tests (291
  assertions), 15 license-seat tests (56 assertions), and 46 combined
  media/evidence/asset-page tests (251 assertions). A delete/restore follow-up
  passes 27 tests with 151 assertions, followed by a corrected 7-test/26-
  assertion isolated rerun. Final consolidation passes 110 tests with 1,122
  assertions; Blade compilation, focused diff hygiene, and the running HTTPS
  health check pass. The frozen full SQLite/MariaDB and browser role gates
  remain open.

### Supported MariaDB suite

- The definitive clean run passed **2,139 tests with 10,238 assertions** in
  14:01 against MariaDB 11.4.7.
- Two failures in the preceding full run were reproduced and classified as
  test defects rather than hidden: a randomly archived importer fixture and a
  rate-limit assertion that rejected a valid one-second clock-boundary shift.
- The importer fixture now selects a deterministic deployable status. The
  rate-limit test accepts the documented positive 1-60 second interval and
  verifies that the JSON value equals the `Retry-After` header.
- The affected MariaDB classes pass 28 tests with 227 assertions after the
  fixes. Repeated reproduction proved the importer failure was nondeterministic.

Retained logs:

- `storage/logs/v1-mariadb-full-definitive-20260813.log`
- `storage/logs/v1-mariadb-two-residual-reproduction-20260813.log`
- `storage/logs/v1-mariadb-residual-classes-after-fix-20260813.log`
- `storage/logs/v1-mariadb-full-final-green-candidate-20260813.log`

### SQLite and LDAP evidence

- The prior complete guarded non-LDAP SQLite suite remains green at 2,120
  tests and 10,153 assertions.
- The post-rehearsal recovery rerun supersedes that local SQLite count at
  **2,128 tests and 10,198 assertions**, with the LDAP group excluded by the
  current user-approved boundary.
- The extension-enabled isolated LDAP group remains green at 18 tests and 75
  assertions from the earlier isolated run; LDAP was not rerun on August 18
  and real-directory integration remains open.
- A current SQLite regression slice covering settings isolation, importer
  behavior, demo readiness, workflow-photo security, asset import, and API
  throttling passes **50 tests with 421 assertions**.
- The new attribute-value exception contract and its callers pass 14 tests
  with 26 assertions using the freshly resolved Composer graph.
- The CommonMark-backed local documentation route and fork-document boundary
  pass 12 tests with 454 assertions on that graph. This check found and fixed
  stale August 4 status links in CONTRIBUTING, SECURITY, TESTING, and the
  boundary test after the README moved to the current August 13 status.

Retained current log:

- `storage/logs/v1-sqlite-focused-after-mariadb-fixes-20260813.log`

### Static analysis

Current status: informational historical evidence only. PHPStan is deferred by
owner decision and is not part of the V1 go/no-go gate. Its configuration and
baseline remain unchanged for possible isolated investigation after V1.

- PHPStan level 4 debt is captured in `phpstan-baseline.neon`: **2,779 errors**
  after the August 18 baseline-free remeasurement replaced stale message and
  count mappings from the earlier 5,817-error baseline.
- `phpstan.neon.dist` owns the baseline and allows resolved baseline entries to
  disappear without breaking focused analysis.
- Two errors that could not be baselined exposed a real missing return contract
  in `AttributeValueService`. Its throwing helper now declares `never`, with
  focused tests proving invalid booleans and unknown data types fail with
  field-scoped validation errors.
- The exact CI command, `vendor/bin/phpstan analyse --memory-limit=1G
  --no-progress`, passes with no errors.
- A temporary, subsequently removed negative-control class returned an integer
  from a declared string method; PHPStan rejected it. The baseline therefore
  owns existing debt without permitting new unowned errors.

The baseline is an enforcement boundary, not a claim that the historical debt
has been fixed. New code must remain clean and existing entries should be
reduced as touched areas are improved.

The later production-profile rehearsal added mail, migration, and maintenance
changes. Its focused 47-test/213-assertion regression is green. The August 18
recovery reran the exact full analyzer command successfully and repeated the
failing negative control. That result is retained as history; V1 qualification
does not require another analyzer run.

### Dependencies and browser assets

- A refreshed Composer advisory query found seven newly unignored advisories
  affecting `league/commonmark` 2.8.3 and `squizlabs/php_codesniffer` 3.13.2.
  The lock now resolves them to 2.10.0 and 3.13.6 respectively.
- The exact Composer lock installs in a fresh disposable vendor volume.
  `composer validate --strict`, patch diagnostics, the updated PHPCS
  executable, and the affected 54 application/documentation tests (707
  assertions) all pass.
- Composer audit now has no unignored advisories or abandoned-package failure.
  Three Laravel advisories remain explicitly ignored only because their
  official fixes are checksum-pinned under `patches/` and regression tested.
- A refreshed npm audit initially found four high findings. Less and
  `less-loader` were upgraded, DOMPurify and Nano ID resolved to fixed versions,
  and AdminLTE's obsolete `slimscroll` dependency was replaced with the
  already-used `jquery-slimscroll` implementation.
- Full and production npm audits now have **zero critical/high findings**.
  The inherited Bootstrap 3 finding remains moderate and locally patched; the
  remaining full-graph findings are moderate/low build-tool debt.
- A clean disposable `npm ci`, Node regression suite, and `npm run prod`
  completed successfully.
- The clean install still warns about Morris.js' obsolete Node engine and
  deprecated inherited build/UI packages. They do not currently fail the V1
  high-severity gate, but they remain explicit post-V1 modernization debt and
  must be reconsidered if their code becomes exposed or unmaintainable.

All dependency results are current worktree evidence. Release CI must repeat
them from the frozen candidate and retain the resulting artifacts and digests.

### Production images and current upgrade-path evidence

- The production application and web targets build from the filtered 1.15 MB
  context, pass the repository-content verifier, and preserve fail-closed app
  startup plus a valid unprivileged NGINX configuration.
- The PHP 8.2 Bookworm base index was refreshed. The final application image
  now purges the compiler toolchain, versioned compiler packages, and
  development headers left by the official build image after extension
  compilation. Required PHP modules, PHP-FPM configuration, and the MariaDB
  client still pass runtime checks.
- The application-image scan fell from 286 high/critical package findings to
  71 Debian findings with no vendor-fixed version in the August 18 advisory
  database. Composer and bundled Node
  metadata report zero high/critical findings, and the secret scan is clean.
- The obsolete NGINX 1.27 / Alpine 3.21 web base was replaced with immutable
  NGINX 1.30.4 / Alpine 3.24.1 inputs. Its 33 fixable high/critical findings
  fell to zero; the rebuilt image also passes the content and `nginx -t`
  checks.
- CI now retains complete app/web high/critical security reports as JSON. Its
  blocking image scans use `ignore-unfixed` so a vendor-fixable vulnerability
  or secret fails the gate while unfixed distribution findings remain visible
  for owner acceptance and future rescans instead of making the gate
  permanently unactionable.
- CycloneDX SBOM and full-license inventory generation succeeds for both local
  candidate images. The local smoke discarded those reports after validating
  generation; release CI remains responsible for retaining them against the
  frozen commit and image digests.
- Focused production-container, release-infrastructure, workflow-upgrade, and
  required-schema tests pass. The final combined production, release,
  migration, schema, and fork-documentation slice passes 41 tests with 772
  assertions.
- The running local MariaDB environment provides a populated already-cut-over
  upgrade sample: the legacy source is absent, the copy checkpoint records
  `absent`, both previous and current photo foreign-key targets are
  `workflow_result_photos`, and 29 workflow items, 11 runs, and 51 results
  remain present. This confirms that path on MariaDB but does not replace the
  required recent production-clone migration, interruption, and rollback
  rehearsal.
- The isolated production-profile rehearsal documented in
  [V1 production profile rehearsal](v1-production-profile-rehearsal-2026-08-13.md)
  now proves first deployment, CLI bootstrap, HTTPS login, persistent upload
  volumes, maintenance-safe replacement, application backup, independent
  database/upload restore, prior-image rollback, queue delivery, and scheduler
  startup. It found and fixed PHP-FPM startup, maintenance health, queued mail
  serialization, and failed-job schema defects.
- Final local rehearsal digests are
  `sha256:495763177d2270b8df50fa452c9d9d2a929d097d10db65d72a89096601664c63`
  for app and
  `sha256:341f2e0b5993145dc20e1af8a1cc2105764bc6f174aac52ee95f2cede3f9846d`
  for web. They are not yet frozen registry artifacts.

## Remaining Release Gates

### External and browser integration

- Either run authentication against a real LDAP directory with the candidate
  image, or explicitly exclude LDAP from the V1 support matrix and keep it
  disabled. The extension-enabled mocked group proves code behavior, not real
  service integration.
- Execute and retain the browser/operator role matrix for login, scan,
  workflows, controlled media, component moves, lifecycle transitions, work
  orders, and denied legacy mutation routes.

### Populated upgrade, interruption, and rollback

On a recent sanitized database/upload clone and the exact release image:

1. restore the database and uploads into isolation;
2. run the schema, workflow copy, cutover, readiness, and lifecycle migrations;
3. validate row, value, relationship, foreign-key, and checkpoint parity,
   including unchanged user password hashes and effective permissions;
4. interrupt between stages and prove retry convergence;
5. prove application rollback and database/upload restore;
6. retain timings, lock impact, migration list, image digest, and evidence.

Follow [workflow migration upgrade](workflow-migration-upgrade.md). Do not use
a shared development or production database for this rehearsal.

### Production profile and artifacts

- Repeat the now-green disposable production-profile flow against the intended
  managed MariaDB, authenticated Redis, real TLS certificate lifecycle,
  monitoring, and off-host backup destination.
- Rehearse the newly implemented LDAP-disabled and mail-disabled profile.
  Confirm local/emergency administrator login, hidden/rejected email actions,
  healthy queue operation without a relay, and absence of unintended LDAP/SMTP
  network attempts. LDAP and SMTP remain outside the supported V1 matrix.
- Retain owner acceptance of maintenance, clean restore, alerting, and image
  rollback timings from that release environment.
- Rebuild the frozen app/web images and retain accepted source/image
  vulnerability, secret, misconfiguration, content, SBOM, and license scans.
- Review and accept or remediate the retained unfixed OS-package findings for
  the exact frozen application-image digest; keep the full JSON report with
  the release evidence.
- Confirm runtime secrets, dumps, backups, and uploads are absent from images.

### Product and release ownership

- Resolve or explicitly assign the remaining product decisions in `TODO.md`,
  including catalog identifiers, email/handoff rules, and the later
  license/media/file UX. QR layout, battery timing, workflow terminology, and
  the minimum private attachment authorization boundary are now decided.
- Review lifecycle mappings and the role-capability matrix with operators.
- Name security, incident, release, and rollback owners.
- Freeze a candidate commit, publish version/changelog/upgrade notes, declare
  the supported matrix, and record immutable image digests.

## V1 Go/No-Go Checklist

- [ ] Frozen-candidate guarded non-LDAP SQLite suite. The retained pre-auth
  delta run passed 2,128 tests with 10,198 assertions.
- [x] Isolated extension-enabled LDAP group: 18 tests, 75 assertions.
- [ ] Frozen-candidate supported MariaDB 11.4.7 suite. The retained pre-auth
  delta run passed 2,139 tests with 10,238 assertions.
- [x] Current Composer/npm high-severity gates and production asset build green.
- [ ] Real-directory LDAP authentication smoke, or explicit owner-approved V1
  exclusion with LDAP disabled and omitted from the supported matrix. The
  source-level runtime gate is implemented; production rehearsal remains.
- [ ] Required browser/operator role matrix.
- [ ] Populated migration, interruption/retry, backup, restore, and rollback.
- [ ] Frozen candidate images rebuilt with framework patches proven.
- [ ] Source/image/SBOM/license/secret/content scans accepted.
- [x] Disposable local production deployment and operational rehearsal run.
- [ ] Managed release-environment production rehearsal accepted.
- [ ] Real SMTP/TLS delivery accepted, or an explicit mail-disabled production
  profile implemented, tested, and documented with its missing features. The
  source profile is implemented; production rehearsal remains.
- [ ] Product, lifecycle, catalog, and role decisions signed off.
- [ ] Owners, version, changelog, supported matrix, and immutable digests
  published.

Until every unchecked item has retained evidence, use exact commit identifiers
for internal evaluation and do not publish a V1 tag.
