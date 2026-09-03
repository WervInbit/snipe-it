# V1 Remaining Implementation Plan

Date: 2026-08-18
Status: active release-candidate plan
Decision: no-go until the V1 exit criteria in this document are satisfied

## Purpose

This plan turns the remaining audit findings into a bounded path to V1. It is
the controlling implementation sequence for release work; detailed audit
evidence remains in `docs/v1-release-readiness-status-2026-09-03.md` and
session history remains in `PROGRESS.md`.

The plan deliberately separates release blockers from useful post-V1 product
work. A work package is complete only when its code, automated coverage,
operator behavior, and documentation agree.

## Working V1 Boundary

Unless the owner changes one of these decisions, V1 will use the following
boundary:

- The current QR layout ships unchanged. A printer-, sticker-size-, and
  resolution-aware label designer is post-V1.
- Workflow Profiles and Workflow Items are the configurable workflow model.
  Legacy `Test*` implementation names remain where compatibility requires
  them; individual diagnostics may still be called tests.
- The existing Windows hardware inventory script is not a trusted diagnostic
  agent. Battery-health validation, SMART checks, installation automation,
  and workflow result ingestion are post-V1.
- Asset gallery images are non-sensitive public catalog/device media. Private
  workflow evidence and documents are not placed in that gallery.
- Asset Files and asset-model Extra files are private resources. Their
  independent view, upload, and manage permissions remain the V1 contract.
- Licenses represent software entitlements and seats, including device-bound,
  user-assigned, add-on, and multi-seat licenses. Keys and supporting files
  are sensitive.
- Passwords, recovery codes, and customer/repair credentials must not be
  stored in notes, gallery images, workflow evidence, license notes, or
  generic attachments. A dedicated vault-backed or encrypted, audited, and
  expiring secret flow is post-V1.
- Production upgrades migrate the complete database and upload stores. They
  do not recreate users through CSV or production seeders.
- MariaDB is the supported V1 production database. Other database engines are
  not implied by upstream test files unless explicitly added to the support
  matrix.
- PHPStan is deferred and is not a V1 release gate. Do not run it, refresh its
  baseline, or change application code to satisfy it during the current V1
  work. Runtime tests, PHP syntax checks, dependency audits, production builds,
  and operator/migration validation remain required.
- The current environment cannot validate a real LDAP directory or SMTP
  relay. V1 therefore implements and tests explicit disabled modes for both
  integrations and documents them as unsupported. If suitable infrastructure
  becomes available before the candidate freeze, the later live-validation
  checklist may promote either integration into the supported matrix.
- Silently shipping configured-but-untested LDAP or required SMTP settings is
  not acceptable. Disabled integrations must be visible to administrators and
  must not make startup, health checks, login, or queues look broken.

If public asset-gallery images need to contain sensitive material, stop and
revise the plan: private storage, authorized streaming, thumbnail handling,
and a production file migration become V1 work.

## Current Baseline

Already implemented and not to be redone:

- Direct login falls back to the dashboard, while deliberate protected deep
  links survive login, two-factor authentication/enrollment, and Google auth.
  Logout clears stale intended destinations.
- Private asset and model attachments have independent view/upload/manage
  permissions in policy, UI, and API paths.
- The asset license surface no longer offers check-in to a user without the
  license check-in ability.
- Workflow evidence is stored separately from public gallery media and served
  through controlled application routes.
- The release work has guarded SQLite and disposable MariaDB test paths,
  production container checks, dependency/security checks, and retained prior
  evidence. The current Supervisor-capable tree and its exact production
  images passed renewed full database, image, populated-promotion, and cold-
  restart gates on 2026-08-25. A 2026-09-03 dependency refresh updates
  Livewire and locked JavaScript transitive packages; its supported SQLite,
  dependency, asset-build, focused Livewire, browser, and CI-contract checks
  pass, but the dependency change deliberately invalidates the old MariaDB and
  production-image evidence for the eventual final candidate.

## Delivery Order

### WP0 - Freeze V1 support and privacy decisions

Objective: eliminate choices that would materially change implementation or
release claims.

Tasks:

1. Record the supported V1 matrix: MariaDB, local authentication, two-factor
   behavior, LDAP state, SMTP state, browsers, and production Compose profile.
2. Confirm that public gallery images contain only non-sensitive device media.
3. Confirm which production groups receive license and private-file
   permissions. Keep the current Admin-only defaults unless a broader role is
   explicitly approved.
4. Use explicit LDAP-disabled and mail-disabled production modes for the
   current candidate. A real integration can replace either disabled boundary
   only after the applicable WP4 external validation passes.
5. Decide whether existing API tokens and browser sessions must survive the
   V1 cutover. User accounts and password hashes survive regardless.

Acceptance criteria:

- `README.md`, `docs/production-deployment.md`, and the release status name the
  same supported and unsupported integrations.
- No open support choice silently defaults to an upstream Snipe-IT promise.
- The owner has approved the support matrix and privacy assumptions.

Dependencies: none. This is the first release decision gate.

### WP1 - Close license and private-resource authorization

Objective: make every sensitive license/file operation obey a clear role and
permission contract.

Implementation status (2026-08-18): complete at source/focused-test level.
License-key API/search/report/export paths, license-file controls, seat
check-in targets, bulk auditing, non-reassignable behavior, asset/model private
file permissions, and model-resource labeling are aligned. The final browser
role matrix remains part of WP5 rather than reopening this implementation
package.

Tasks:

1. Audit license list, detail, key display, seat assignment, check-out,
   check-in, file upload/download/delete, UI controls, direct web routes, and
   API routes against `LicensePolicy` and the operational role matrix.
2. Verify asset Files and model Extra files across list, download, upload,
   delete, tab visibility, direct URLs, and APIs for:
   - administrator;
   - normal asset operator;
   - a user with view-only file rights;
   - a user with upload but not manage rights;
   - an unauthorized user.
3. Make model resources clearly read as model-level resources when displayed
   from an asset page. Do not imply they belong to the individual asset.
4. Document the intended license patterns rather than redesigning the entire
   inherited license subsystem:
   - Windows/OEM or other device-bound entitlement;
   - Microsoft 365/Office or other user/device assignment;
   - add-on software;
   - volume/multi-seat entitlement.
5. Add authorization regressions for any route or UI mismatch found during
   the audit.

Acceptance criteria:

- Direct URLs and APIs never disclose a key or private file merely because a
  user can view the asset/model.
- UI visibility matches server-side authorization but is not relied on as the
  security boundary.
- The role capability matrix and README/operator-facing text match the tested
  behavior.
- Focused authorization suites pass on guarded in-memory SQLite.

Dependencies: WP0 role decision.

### WP2 - Verify the media and workflow-evidence boundary

Objective: ensure the accepted V1 public/private split is real in storage,
routing, and user guidance.

Implementation status (2026-08-18): complete at source/focused-test level.
Private evidence uses controlled reads, explicit gallery promotion requires
the image-upload ability, run deletion cleans its private files, asset soft
delete/restore preserves both media classes, and all three upload surfaces
state their privacy boundary. Final operator/browser confirmation remains in
WP5.

Tasks:

1. Trace public asset/model image upload, thumbnail, gallery, API, webshop,
   delete, and restore paths. Confirm they cannot expose private workflow or
   attachment storage.
2. Trace workflow evidence creation, viewing, download/streaming, deletion,
   asset-page visibility, and legacy paths. Keep authorization on every read
   path, including generated thumbnails if any.
3. Verify that promoting an image to the public gallery is an explicit user
   act, or remove/disable any implicit promotion behavior.
4. Add operator warnings that gallery media is public/non-sensitive and that
   passwords or customer secrets are forbidden in all current evidence and
   attachment surfaces.
5. Keep a unified media tab or private gallery redesign out of V1 unless the
   audit finds an actual disclosure or unusable workflow.

Acceptance criteria:

- Automated route/storage tests prove that private media is not reachable
  through public paths and unauthorized direct requests fail.
- Public-gallery deletion and asset restore do not orphan or cross-delete
  private evidence.
- The application help and fork documentation state the same storage/privacy
  contract.

Dependencies: WP0 privacy decision. May be performed alongside WP1.

### WP3 - Rehearse the production-data upgrade

Objective: prove that the existing production-like Snipe-IT environment can be
upgraded without losing users, password hashes, permissions, history, files,
or encryption continuity.

Implementation status (2026-09-03): database/upload restore, forward upgrade,
exact-batch rollback/reapply, image promotion, role upgrade, full-profile
cold-restart parity, and the real data-bearing server cutover are complete.
The owner confirmed login through `snipe.inbit` using an existing migrated
account and unchanged password. One representative migrated data/private-file
workflow remains an owner acceptance action.

Safety boundary:

- Use an isolated clone made from a recent backup. Never run `migrate:fresh`,
  `migrate:refresh`, `migrate:reset`, `db:wipe`, or destructive seeders on the
  shared environment.
- Do not print password hashes, application keys, API secrets, or private file
  contents into retained logs.

Tasks:

1. Inventory and back up the database, public uploads, private uploads,
   `.env` decisions, `APP_KEY`, and Passport keys if token continuity is in
   scope.
2. Restore them to an isolated rehearsal stack and record pre-upgrade counts
   and relationship checks for users, groups, permissions, assets, models,
   components, licenses/seats, workflows/results, action history, and files.
3. Run the normal forward migrations and production startup sequence.
4. Verify an actual migrated local account can log in with its existing
   password. Exercise administrator dashboard landing, an intended QR/deep
   link, and the applicable two-factor path.
5. Verify encrypted custom data, file downloads, license visibility, workflow
   evidence, group membership, direct permissions, and ownership/history links.
6. Exercise migration/startup interruption and retry without duplicating or
   corrupting data.
7. Restore the pre-upgrade backup and verify the rollback procedure and timing.
8. Record checksums/counts and sanitized evidence in a dated rehearsal report.

Acceptance criteria:

- Pre/post counts and sampled relationships reconcile, with every intentional
  transformation explained.
- A migrated user password works without reset or hash replacement.
- `APP_KEY`-protected data and required uploads remain readable.
- Forward migration is retry-safe and rollback by restore is proven.
- The runbook contains measured downtime and an explicit go/no-go checkpoint.

Dependencies: migrations and storage behavior must be frozen for the candidate
batch; WP0 decides session/API-token continuity.

### WP4 - Implement safe LDAP/SMTP boundaries and validation paths

Objective: complete everything that can be implemented without external
infrastructure, ship safe disabled behavior, and preserve an executable live
acceptance path for later.

Implementation status (2026-08-25): the runtime gates, disabled production
Compose defaults, non-logging mail sink, explicit UI/controller denials, local
administrator recovery guidance, and focused automated coverage are
implemented and passed the exact production-image/profile rehearsal. WP4 is
complete for the explicitly unsupported/disabled V1 boundary; the real-service
LDAP and SMTP checklists remain deferred.

Current limitation:

- No reachable representative LDAP directory or production SMTP relay is
  available. Mock LDAP, local mail capture, or Mailpit can validate application
  behavior but cannot prove directory compatibility, relay authentication,
  delivery, or production TLS trust.
- Until live validation succeeds, release documentation must say `disabled`
  or `unsupported`, never `supported but untested`.

LDAP implementation tasks now:

1. Make LDAP opt-in and disabled in the production example/profile by default.
2. Confirm local authentication, local user creation, password administration,
   two-factor flows, startup, health checks, and queues work without LDAP
   configuration or credentials.
3. Show a clear disabled/not-validated state in the administrator settings and
   diagnostics surfaces. Avoid background sync attempts while disabled.
4. Validate configuration before enabling it and fail closed on incomplete TLS,
   bind, base-DN, filter, or mapping settings.
5. Keep LDAP extension and mocked coverage green for authentication decisions,
   user mapping, group synchronization, deactivated/missing users, connection
   failures, and protection of local administrator access.
6. Document how LDAP-created users, local users, and emergency administrator
   accounts behave while the integration is disabled.

SMTP implementation tasks now:

1. Add an explicit production configuration switch and make mail-disabled the
   current unvalidated profile. Do not use the log mailer for messages
   containing reset links or other secrets.
2. Disable or clearly explain unavailable password reset, notifications, and
   test-mail actions in the UI.
3. Provide a controlled administrator recovery path for local accounts.
4. Ensure queue workers do not accumulate permanently failing mail jobs.
5. Make SMTP secrets optional only while mail is disabled and reject an
   incomplete configuration when mail is enabled.
6. Expose the disabled/not-validated state through administrator diagnostics
   and production readiness without marking the whole application unhealthy.
7. Use fakes and local mail capture to test notification selection, queued
   serialization, reset-flow gating, and absence of sensitive log output.
8. Document the exact user-visible and operational limitations.

Deferred LDAP live-validation checklist:

1. Test the intended directory type and version over the production TLS mode.
2. Verify certificate trust, bind behavior, base DN and filters, unique user
   mapping, group synchronization, disabled/deleted users, timeouts, and
   recovery when the directory is unavailable.
3. Verify a protected local emergency administrator remains usable.
4. Retain sanitized evidence and explicitly approve LDAP before changing the
   support matrix.

Deferred SMTP live-validation checklist:

1. Test authentication and TLS peer verification against the intended relay
   without retaining credentials.
2. Test queued delivery, password reset, test mail, failure/retry behavior,
   sender/reply-to handling, and at least one operational notification.
3. Verify queue supervision, bounce/failure visibility, and relay recovery in
   the production profile.
4. Retain sanitized evidence and explicitly approve SMTP before changing the
   support matrix.

Acceptance criteria:

- LDAP and SMTP are both visibly disabled and unsupported in the current V1
  profile unless their respective real-service checklist has passed.
- Disabled-mode behavior is covered in application tests and reflected in
  Compose examples, environment documentation, README, health/readiness, and
  applicable operator/admin guidance.
- Local authentication and administrator account recovery do not depend on
  either external service.
- Enabling either integration with incomplete configuration fails clearly and
  safely; startup cannot claim an enabled integration is usable when required
  configuration is absent.
- Passing mock/local-capture tests alone does not change the public support
  status.

Dependencies: WP0 support-matrix approval. Real-service validation may be
completed later without blocking the disabled V1 profile.

### WP5 - Run the operator and browser acceptance matrix

Objective: validate that the tested internals match the real V1 workflows and
manuals.

Implementation status (2026-09-03): temporary Refurbisher, Senior
Refurbisher, Supervisor, and Admin accounts completed the browser permission
and direct-route matrix during the controlled production migration. Dashboard
landing, supported/denied paths, and role-specific workflow visibility matched
the contract, and all temporary artifacts were removed with parity restored.
The remaining acceptance is one representative migrated-account data/file
workflow plus alignment with the separately maintained operator guides.

Tasks:

1. Use representative production-role accounts, not only a superuser.
2. Check direct login dashboard landing and deliberate intended destinations,
   including QR/scan links and two-factor flows.
3. Exercise asset registration, component setup, status/lifecycle changes,
   workflow selection/run/resume, issue and pass evidence, QA handoff, and
   release/return behavior.
4. Exercise license visibility and seat operations for allowed and denied
   roles.
5. Exercise public gallery images, private asset files, model resources, and
   workflow evidence with both normal navigation and direct URLs.
6. Confirm deprecated Devices, Maintenance mutation, paperclip upload, and
   other removed routes do not reappear through alternate UI/API paths.
7. Compare application labels and next actions with the accepted manuals.
   Log product defects separately from manual-only drift.

Acceptance criteria:

- Every supported role completes its primary workflow without superuser help.
- Denied roles cannot retrieve sensitive resources through direct URLs/APIs.
- Manual steps and screenshots match the frozen candidate, or the manual work
  records an explicit, approved exception.
- No unresolved severity-1 or severity-2 workflow defect remains.

Dependencies: WP1, WP2, and WP4 behavior must be stable. The separate manual
agent may continue independently, pausing only when application state changes
or new screenshots are required.

### WP6 - Freeze and qualify one release candidate

Objective: produce evidence for one exact source revision rather than combining
passes from different change batches.

Implementation status (2026-09-03): the deployed candidate passed guarded SQLite
and MariaDB, dependency/build, production-content, blocking image-security,
populated promotion, HTTPS, queue/scheduler, and seven-service cold-restart
gates. The browser role matrix, real cutover, and migrated-password login are
also complete. The owner designated that exact deployed source and image pair
as V1.0.0 on 2026-09-03. Newly disclosed dependency advisories required a
source and lockfile refresh after that cutover. The refreshed source passes the
supported SQLite, dependency, browser, and focused configuration gates, but is
post-V1 development and has not repeated MariaDB or production-image
qualification for a later release.

Freeze rules:

- Stop feature work for the candidate window.
- Record the commit/source identity, lockfiles, image digests, database engine,
  and test commands.
- Any code/config/migration change after a gate invalidates the affected gate.
  Documentation-only corrections require a documented impact judgment.
- Run the 40-50 minute MariaDB suite once per frozen candidate, not after every
  focused change.

Required gates, in order:

1. Focused tests for all changed areas and executable test-database guards.
2. Full guarded non-LDAP SQLite suite with no skipped/incomplete tests.
3. Full disposable MariaDB 11.4 suite using only the exact `snipeit_test`
   database and explicit external-test opt-in.
4. Composer validation, patch diagnostics, locked dependency audit, npm tests,
   production asset build, and production-only dependency audit.
5. Production app/web image build, content verification, high/critical
   vulnerability policy, secret scan, SBOM, and license report.
6. Production profile startup, health/readiness, queue behavior, backup, and
   selected SMTP branch.
7. Successful production-data upgrade rehearsal and browser/operator matrix.
8. README, deployment guide, support matrix, changelog/release notes, and
   rollback runbook review.

Acceptance criteria:

- Every checked gate refers to the same frozen candidate.
- No unresolved critical/high release-owned vulnerability, data-loss risk,
  authentication bypass, private-data disclosure, or severity-1/2 workflow
  defect remains.
- Known unfixed operating-system findings have an inventory, impact review,
  and explicit acceptance; they are not confused with fixable application
  findings.
- Release owner signs off on the support boundary, migration evidence,
  limitations, rollback, and exact candidate identity.

Dependencies: WP0-WP5 complete.

## Recommended Execution Sequence

1. Finish WP0 decisions.
2. Complete WP1 and WP2 while feature changes are still allowed.
3. Implement the WP4 disabled-integration boundary.
4. Freeze migrations/storage behavior and complete WP3 on a recent production
   clone.
5. Run WP5 against that release-candidate behavior and reconcile manuals.
6. Freeze the candidate and run WP6 once.
7. Fix only release blockers, then create a new candidate and repeat only the
   gates invalidated by those fixes. A code fix normally invalidates all full
   application/dependency/image gates; a migration or storage fix also
   invalidates the production-data rehearsal.

## V1 Designation And Follow-Up Checklist

- [x] V1 support and privacy matrix approved.
- [x] License/private-file authorization matrix complete at source and focused-test level.
- [x] Public gallery/private evidence boundary verified at source and focused-test level.
- [x] LDAP disabled-mode implementation complete and excluded from V1 support,
  or the full real-directory checklist passes.
- [x] SMTP mail-disabled implementation complete and excluded from V1 support,
  or the full real-relay checklist passes.
- [x] Recent production-clone upgrade and rollback rehearsal passes.
- [x] Migrated production user authenticates with the existing password hash.
- [x] Browser role/permission matrix passes with representative temporary
  accounts and cleanup parity.
- [x] Owner designates deployed commit `1c9131f4c9` and its recorded app/web
  digests as the internal V1.0.0 baseline.
- [ ] Post-V1: one representative migrated-account data/file workflow passes
  and the supported operator manuals match.
- [ ] Post-V1: the dependency-refreshed candidate passes SQLite, MariaDB,
  syntax, dependency, asset, container, health, backup, and production-profile
  gates before a later deployment.
- [x] README, deployment guide, limitations, release notes, and rollback steps
  record the V1.0.0 baseline and its accepted limitations.
- [ ] Post-V1: long-term release, rollback, and incident/security owners are
  named.

Unchecked items are accepted post-V1 work. They remain mandatory where stated
before a later deployment or broader support claim, but do not change the
immutable V1.0.0 designation.

## Post-V1 Backlog

These are valid improvements but should not delay V1 under the agreed scope:

- QR/label designer with physical sticker size, printer limits, DPI/resolution
  validation, preview, calibration, and reusable templates.
- Windows inventory/diagnostic agent with validated battery capacity/health,
  SMART data, explainable thresholds, signed/authenticated submissions, and
  workflow integration.
- Unified media/attachment UX and, if needed, a private device-gallery storage
  migration with generated thumbnails.
- Richer license UX for device sale/ownership transfer, OEM/add-on bundles,
  and clearer multi-seat assignment language.
- Dedicated repair/customer credential workflow backed by an external vault
  or encrypted, audited, access-limited, expiring storage.
- Real LDAP and SMTP integration validation, support promotion, and production
  enablement if either remains excluded from the initial V1.
- Reassess PHPStan in an isolated/disposable environment, determine why its
  schema-sensitive result is not reproducible, and decide whether to restore a
  deterministic no-new-error gate. Do not refresh its baseline or modify
  runtime code merely to reduce its count without a separate review.
- Migration snapshot/squash work for fresh installs only, after the V1 upgrade
  path and historical migration compatibility are frozen and proven.

## Plan Maintenance

- Use this document for sequencing and exit criteria.
- Use `TODO.md` for concise open/closed tracking.
- Use `PROGRESS.md` for dated evidence and commands/results.
- Use the release-readiness status document for the current go/no-go decision.
- Update the README and fork notes whenever a product boundary changes.
- Do not mark a work package complete from documentation or unit tests alone
  when its acceptance criteria require production-profile or browser evidence.
