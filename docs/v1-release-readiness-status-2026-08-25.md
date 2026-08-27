# V1 Release Readiness Status - 2026-08-25

## Decision

The repository and populated rehearsal are technically qualified release
candidates, but the project is not ready for a V1 tag. Automated application,
supported-database, dependency, production-image, promotion, and cold-restart
gates are green for the current Supervisor-capable source. The remaining gates
require owner-operated acceptance and release control.

## Exact Local Candidate

- Application image:
  `local/inbit-app@sha256:bc1e29d8d9a40a4048e3642419c2b7bbd2555b754bedff37bfc7c6456df8fe33`
- Web image:
  `local/inbit-web@sha256:c8bd31489fb6d22ade4579ecd911515ef676f292954c33757fb7c325073df4b2`
- Supported rehearsal database: MariaDB 11.4.7.
- `paragonie/sodium_compat`: v1.24.2. The v1.24.0 lock was updated after the
  August 18 Ed25519 public-key-validation advisory.
- Beta dump input SHA-256:
  `5e84a69c1e41cad015188e6951e25c73945efcff07bfaf47e30838be9d45db73`.

These are local evidence identities, not published release artifacts. A final
release must be rebuilt from a reviewed immutable commit and published by
registry digest.

## Green Gates

| Gate | Retained result |
| --- | --- |
| Guarded non-LDAP SQLite | 2,166 tests, 10,553 assertions, 14:33 |
| Guarded disposable MariaDB 11.4.7 | 2,166 tests, 10,553 assertions, 16:17 |
| Rehearsal configuration | 2 tests, 32 assertions |
| Node security regressions | 4 tests, 4 pass |
| Composer | strict validation, locked dry-run install, and current audit pass |
| npm | production and complete graphs have zero high/critical findings |
| Production images | builds, content policy, pinned Laravel patches, and production assets pass |
| Image security | current scan found zero fixable high/critical findings and zero embedded secrets |
| HTTPS profile | login 200; HSTS, CSP, anti-framing, MIME-sniffing, and referrer headers present |

Composer still reports the three deliberately ignored Laravel advisories whose
official fixes are stored under `patches/`, checksum-pinned in
`patches.lock.json`, applied during the production build, and covered by
framework-level tests. npm retains moderate/low Bootstrap and inherited build-
chain advisories; the shipped Bootstrap 3 runtime is locally patched and
covered by Node regressions. Neither gate has an unmitigated high/critical
finding.

## Populated Upgrade And Rollback

The checksum-verified beta export was restored outside the shared development
database into MariaDB 11.4.7 with its original application and Passport keys.

- Baseline: 468 migrations, 17 users, 12 assets, and 14 models.
- Forward upgrade: nine migrations applied as batch 2 in 2.654 seconds.
- Upgraded state: 477 migrations, none pending, and zero failed jobs.
- Exact batch rollback: nine migrations reverted in 2.024 seconds.
- Rollback parity: the users, assets, models, and settings row fingerprints
  returned exactly to baseline.
- Reapply: the same nine migrations completed in 3.078 seconds and restored
  477-ran/zero-pending state.
- Upload parity: 294 public and 14 private files retained identical manifest
  hashes through image promotion, rollback testing, and cold restart.
- The primary populated rehearsal retains 19 users because two clearly marked
  rehearsal-only accounts were added after baseline parity was recorded.

The disposable rollback clone was removed after verification. Its database,
uploads, and generated runtime secrets are recoverable from the unchanged
immutable export. The populated primary rehearsal is online on the exact
candidate digests above after additive role upgrade, promotion, and a complete
seven-service cold restart.

## V1 Blockers

1. **Browser/operator acceptance.** Run the supported Refurbisher, Senior
   Refurbisher, Supervisor, and Admin paths in a browser against the candidate,
   including Supervisor product setup and Admin-only lifecycle controls.
   Automated access was
   unavailable because the browser runner correctly refused private/local
   network targets.
2. **Migrated credential acceptance.** The owner must sign in with one real
   migrated account and its existing password without sharing the password.
3. **Managed release rehearsal.** Repeat the production profile in the final
   managed environment with off-host database/upload backup and restore,
   monitoring/log ownership, capacity checks, and the actual TLS/proxy setup.
4. **Release control.** Name release/security/incident/rollback owners, freeze
   a reviewed commit, publish version/changelog/support limitations and image
   digests, then record final go/no-go approval.

## Resolved In The Current Source

- Supervisor receives explicit ordinary model, model-number/specification,
  attribute-definition, component-definition, and workflow-definition setup
  abilities through the additive production permission seeder.
- Destructive catalog deletion, lifecycle/deprecation, option removal, and
  specification cleanup remain Admin-only and are guarded in policies,
  controllers, routes, and navigation.
- Existing custom grants on same-name foundation groups are preserved during
  upgrade, while required minimum grants are added.
- A migrated Supervisor's preserved legacy `models.delete` grant cannot bypass
  the new Admin-only model/model-number lifecycle permission. Runtime Gate
  probes on the populated rehearsal prove create/setup allowed and delete/
  restore denied for Supervisor while Admin retains lifecycle control.
- Direct-route denial and capability-matrix regressions pass for Refurbisher,
  Senior Refurbisher, Supervisor, Admin, and superuser boundaries.

## Explicit V1 Exclusions

- Real LDAP login/synchronization and real SMTP/TLS delivery are unsupported
  for V1. Production defaults keep both integrations disabled and fail closed;
  real-service qualification remains post-V1 unless the owner changes scope.
- PHPStan remains deferred because its schema-sensitive result is not
  reproducible. It is not a V1 gate and was not run in this qualification.
- The QR label designer and Windows battery/SMART diagnostic tooling remain
  post-V1 work.
- PostgreSQL remains outside the V1 production support matrix.
