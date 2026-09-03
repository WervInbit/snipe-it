# Agent Addendum - 2026-09-03 V1 Continuation

## Objective

Resume the V1 release, deployment, and implementation audit without mixing it
with the concurrent operator-guide work.

## Starting state

- The qualified application candidate remains commit
  `1c9131f4c92a02ba75a230cf57ede3b58c5a19ac`.
- Reusable production dependency, TLS edge, registry, validation, and runbook
  improvements were committed and pushed as `fe4d0b4fe8`.
- The data-bearing temporary production server completed backup, migration,
  role/browser checks, cutover, and post-cutover verification on 2026-09-01.
- LDAP and SMTP remain explicitly deferred because their external systems are
  unavailable for qualification.
- Destructive cleanup of the retained legacy deployment remains blocked until
  migrated-user and representative data/file acceptance are complete.
- The separate guide session created local commit `cf13808744` after this
  session began; `origin/master` remains at `fe4d0b4fe8`. The guide commit is
  preserved and excluded from this session's staging/push boundary.

## Initial checks

- Confirm local and remote repository refs and identify non-manual drift.
- Recheck the running temporary-production service/migration/backup boundary
  read-only while the dedicated migration access is still valid.
- Reconcile README, readiness, TODO, and progress statements with completed
  production evidence.
- Produce a short ordered V1 completion list before making another functional
  change.

## Read-only production check

- Reverified the supplied ED25519 host key before using the dedicated expiring
  migration account.
- App, web, queue, scheduler, MariaDB, Redis, and TLS edge are healthy. HTTPS
  login and health succeed, 477 migrations are applied with none pending, and
  retained application counts match the post-cutover baseline.
- Recent logs before the check contained no runtime errors. The queue worker's
  hourly recycle is explained by its `--max-time=3600` lifecycle plus restart
  policy rather than a crash loop.
- One root-owned read-only Tinker probe could not create PsySH state under the
  read-only root filesystem. It changed no database/application data, and the
  database check was completed through direct read-only MariaDB access.
- The three backup inventory files remain present with protected permissions.

## V1 reconciliation

- Owner confirmation that `snipe.inbit` login works with an existing migrated
  account closes the migrated-password gate.
- The four-role browser permission/direct-route matrix was already completed
  during the production migration. One representative migrated-account
  data/private-file workflow remains open.
- Five known legacy demo model-number codes are present but each has zero
  assets. They retain old seed-owned metadata and are not deprecated. Current
  production seeding excludes them; no row was changed during this check.
- Added `docs/v1-release-readiness-status-2026-09-03.md` and
  `docs/releases/v1.0.0-draft.md`. Updated the root documentation pointers,
  implementation-plan statuses, and documentation-boundary test.
- The remaining release gates are manual alignment, representative data/file
  acceptance, an exact final commit and published image digests, named release
  control, and final-server deployment acceptance. No known severity-1/2
  implementation defect currently blocks controlled user testing.

## Validation

- `git diff --check`: pass.
- Local Markdown link check for the changed release documents: pass.
- Guarded Docker PHPUnit with explicit in-memory SQLite:
  `ForkDocumentationBoundaryTest` plus `ReleaseVersionConfigurationTest` pass
  9 tests with 438 assertions.
- PHPStan was not run by owner decision. The full database/image suite was not
  repeated because this block changes documentation and one documentation
  contract assertion only; it remains required for the exact final candidate.

## Dependency refresh and requalification

- A new locked Composer audit identified GHSA-g3hc-697w-wm82 in Livewire
  3.6.4. Updated the root constraint to `^3.8.3`, locked Livewire 3.8.7, and
  republished its client assets.
- Refreshed npm audit findings were removed by locking `fast-uri` 3.1.7 and
  `postcss-selector-parser` 6.1.4/7.1.5. The complete and production npm graphs
  now have no high/critical finding; the inherited moderate/low inventory
  remains recorded.
- Composer strict validation, patch diagnostics, locked audit, npm clean
  install/audits, four Node tests, and the production asset build pass.
- The complete local invocation passed 2,178 tests before reporting ten LDAP-
  group failures caused solely by the five-week-old local image lacking PHP's
  LDAP constants. The documented supported run excluding the LDAP group passes
  2,170 tests with 10,641 assertions.
- Added explicit LDAP extension installation to every complete database CI job
  and protected it with `ReleaseInfrastructureConfigurationTest`; the updated
  test class passes 8 tests with 154 assertions.
- Browser smoke testing loaded the republished Livewire asset hash `bd51a0a6`,
  rendered the importer, and completed a non-persistent category-form update
  with the expected UI state and no console warning/error.
- Provisional production app/web targets built successfully from the worktree
  and passed the repository image-content verifier. The app reports Livewire
  3.8.7 and both images contain asset manifest hash `bd51a0a6`. This is build
  evidence only, not a frozen or publishable image identity; local Trivy is not
  installed, so the blocking image scans remain an exact-candidate CI gate.
- Because the lockfiles and published client changed, the August MariaDB/image
  evidence and the images deployed on 2026-09-01 do not qualify the refreshed
  final candidate. No live service or production row was changed in this work.
- Final checkpoint hygiene passes: local links across the ten changed release
  documents, YAML parsing for all four edited test workflows, published versus
  packaged Livewire asset parity, Composer validation/patch/audit, complete and
  production npm audits, and `git diff --check`. Provisional app/web image
  contents were verified but are not release artifacts.

## V1 designation decision

- By explicit owner direction, retrospectively designated the exact source and
  images deployed on 2026-09-01 as the internal V1.0.0 production baseline.
- The authoritative source is `1c9131f4c9`; exact app/web digests were read
  from the retained off-host post-cutover snapshot without accessing the
  production server.
- The deployed image retains its historical `v0.9.0-dev` display metadata. The
  Git tag/source commit/image digests define V1.0.0; production will not be
  altered merely to rewrite that label.
- The current branch moves to `v1.1.0-dev`. Dependency/CI fixes, remaining
  small defects, additions, manual alignment, representative private-file
  acceptance, and named operational ownership are post-V1 work.
- Hard boundary: no production access, inspection, browser test, query, or
  mutation without new explicit owner permission.
- Created local annotated tag `v1.0.0` at exact deployed commit
  `1c9131f4c9`; it has not been pushed. Updated the working version to
  `v1.1.0-dev`. Guarded in-memory SQLite release-version/documentation tests
  pass 9 tests with 440 assertions, local links across ten release-facing
  documents pass, the tag dereferences to the expected commit, and
  `git diff --check` passes.

## Checkbox and radio layout correction

- A source-wide form audit traced overlapping labels to Bootstrap 3's fixed
  20px, absolutely positioned checkbox/radio gutter conflicting with the
  fork's 1.8em custom controls.
- Standard checkbox/radio groups, direct inline labels, and nested inline
  labels now use normal inline-flex flow with a size-aware gap. Removed the
  attribute-editor inline workaround and the custom-fieldset's duplicate span
  padding so both use the shared rule.
- Rebuilt the production CSS assets. On `dev.inbit`, attribute 15's three
  reported controls each render with a 7.8px measured gap and no overlap;
  enum option-table checkboxes, the component workflow's inline radios, and
  the custom-fieldset nested-inline checkbox also use static-positioned,
  visible 23.4px controls with zero Bootstrap gutter padding.
- The focused guarded SQLite suite passes 38 tests with 241 assertions, the
  new style contract passes independently, all four Node tests pass, and the
  production asset build completes successfully. Production was not accessed.

## Production QR printer diagnosis

- The owner explicitly restored permission to investigate the temporary
  production server for the QR-printer regression. The stored ED25519 host key
  matched the supplied `SHA256:SlRpl66+HouJcotDmGRwKbtx5GA3htJEQy2+ai69rQI`
  fingerprint before access.
- Read-only inspection found the host CUPS service active, listening on port
  631, and exposing an enabled, idle `dymo330` queue. The current application
  network can reach the host CUPS endpoint successfully (HTTP 200).
- The V1 application container has no `LABEL_PRINTER_QUEUE`,
  `LABEL_PRINTER_QUEUES`, `LABEL_PRINT_COMMAND`, `LABEL_PRINT_OPTIONS`, or
  `CUPS_SERVER` environment values and contains neither `lp` nor `lpstat`.
  The legacy environment retained `dymo330`, its 72x72 media options, and a
  Docker-host CUPS address.
- Root cause: the hardened production Dockerfile omitted `cups-client`, while
  the production Compose profile and environment template omitted the printer
  variables during migration. The UI can still render its print button, but
  the controller resolves no queue and returns its not-configured response.
- No production configuration, container, service, queue, print job, or data
  was changed. No test label was submitted. Repair requires a reviewed image
  rebuild plus Compose/environment wiring; use a host-gateway alias instead of
  copying the legacy network-specific `172.18.0.1` address.
