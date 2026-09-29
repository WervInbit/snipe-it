# Refurbisher Follow-Ups Production Deployment - 2026-09-29

## Release Identity

- Deployed application commit: `0df107a50b300bbd2e17e986ced4a3d230c9f0ca`.
- Release directory: `/srv/snipeit-v1/releases/0df107a50b`.
- App image:
  `127.0.0.1:5000/inbit/snipeit-app@sha256:c09546062cf19c3857b8c10fd7bc75794dfec55f50263b2a92951ed399757b40`.
- Web image:
  `127.0.0.1:5000/inbit/snipeit-web@sha256:128513a85a9fe21c9304389e3ca88f55b8dc1995357a5e53479053ed3900cac7`.
- Source bundle SHA-256:
  `82608f725435511e960985c572fd36767ffa7a9b8b6250d7467feab1a2b3f30d`.
- Image bundle SHA-256:
  `2b5930080842a5ff49e001fe3bda93957465fb9803244fbd85c35a3ff8c1ee5c`.

The feature branch and `master` were fast-forwarded without a merge commit.
This document is a later documentation-only record and does not change the
deployed application image identity.

## Pre-Deployment Gates

- Built the images from an exact clean LF checkout of the deployed commit.
- Verified both required framework patches by SHA-256.
- Passed 19 production contract tests with 272 assertions.
- Verified required PHP modules, `/usr/bin/lp`, Laravel 11.55.0, migration
  presence, Nginx configuration, and the absence of compiler/build installers.
- Trivy 0.66, with a current database on the deployment date, reported zero
  HIGH or CRITICAL findings for both app and web images.
- Validated and pulled the complete candidate Compose configuration before the
  live environment file was promoted.

## Backup And Rollback Anchors

- Maintenance-mode backup name: `pre-deploy-20260929T100343Z`.
- Server backup directory:
  `/srv/snipeit-v1/backups/pre-deploy-20260929T100343Z`.
- Server archive:
  `/srv/snipeit-v1/incoming/0df107a50b300bbd2e17e986ced4a3d230c9f0ca/pre-deploy-20260929T100343Z.tar.gz`.
- Restricted off-host copy:
  `C:\snipeit-production-backups\pre-deploy-20260929T100343Z`.
- Backup archive SHA-256:
  `071c3b418e9a00e9f83493e07b1db52c323431eb333721df1bd5b7d6d710d98a`.
- Previous release directory retained on the server:
  `/srv/snipeit-v1/releases/0c5ea2dc16`.
- Previous environment file retained as:
  `/srv/snipeit-v1/runtime/production.env.pre-refurbisher-20260929T100343Z`.

The backup includes a native transaction-consistent database dump, the
application backup ZIP, public/private uploads, Redis session state, runtime
configuration, secrets, TLS material, release state, data baseline, and
checksums. The outer archive hash, every internal file hash, gzip/tar structure,
and ZIP structure were verified both on-host and off-host.

For an application-only rollback, enter maintenance mode, restore the previous
environment/image selection and `current` release target, recreate only the
application writer services, verify health, and reopen traffic. Restore the
database/uploads from the complete backup only for a confirmed data rollback;
that is a separate downtime operation because it would discard valid changes
made after this deployment.

## Applied Changes

- Ran `2026_09_22_120000_create_user_recent_assets_table` once.
- Ran `2026_09_22_121000_add_status_access_controls` once.
- Ran `ProductionPermissionGroupSeeder` once. Ordinary Refurbishers retain
  explicit asset-create and quality-edit denies; Senior Refurbisher,
  Supervisor, and Admin have the approved grants.
- Set QA Hold to require a transition note.
- Added exactly one active Afgevoerd status using the destroyed lifecycle stage;
  it is archived/non-deployable and selectable by Supervisor and Admin.
- Allowed Supervisor to view and select the active Broken / Parts status.
  Refurbisher and Senior Refurbisher remain denied for Broken / Parts and
  Afgevoerd; Admin retains its policy bypass.
- Left the soft-deleted legacy `Broken/Spare Parts` record untouched.

No destructive database command ran. Migration and configuration writes were
performed once, with traffic closed and the writer services controlled.

## Post-Deployment Verification

- App, web, queue, scheduler, database, Redis, and TLS edge services are all
  healthy on the expected immutable images.
- `https://snipe.inbit/health` returns HTTP 200 with `{"status":"ok"}`.
- The certificate for `snipe.inbit` is valid through 2035-09-14; observed
  thumbprint: `3231FA7882E7E7263C15BC9751F289372F9D002F`.
- Default, mail, and reports Redis queues are empty.
- Recent app, web, queue, scheduler, and edge logs contain no unhandled/fatal,
  SQLSTATE, emergency, or critical entries after excluding one harmless failed
  read-only Tinker history-directory probe.
- Entity counts remain 19 users, 15 assets, 4 tracked component instances,
  6 workflow profiles, 37 workflow items, 17 workflow runs, and 14 statuses.
- Duplicate active asset tags, non-empty asset serials, and component tags all
  remain zero.
- Both intended migration records occur exactly once; `user_recent_assets`,
  `status_label_access_rules`, and `status_labels.requires_note` exist.
- Sent exactly one controlled QR label for existing asset `INBIT-AA0010` using
  the 25 x 25 mm template. CUPS job `dymo330-7` completed successfully and the
  queue returned to enabled/idle with no pending copy.

## Remaining Acceptance And Operator Work

- Physically inspect job `dymo330-7` for orientation/cropping and scan its QR
  code to confirm it resolves to `INBIT-AA0010`.
- Run the authenticated production UI matrix with existing Admin, Supervisor,
  Senior Refurbisher, and Refurbisher accounts. No production credentials were
  supplied to automation, so the visible browser correctly stopped at login;
  no temporary account or authentication bypass was created.
- On managed phones, disable automatic page translation, pre-allow camera use
  where the platform supports managed permission policy (otherwise accept the
  one-time browser request), and verify Scan QR.
- The owner will continue editable Dutch naming/matrix refinements and add the
  planned workflow content: Programmeerbare toets, Geschiedenis wissen,
  iGPU/CPU handling, and external/internal cleaning steps.
- The separate unchanged model-number image API baseline has four known test
  mismatches and is not caused by this release; it remains a follow-up rather
  than hidden release evidence.

## Post-Release Catalogue Update

After explicit owner approval, production received a catalogue-only Cisco
Aironet addition. No application image, migration, permission, attribute
definition, custom-field assignment, or physical asset changed.

- Backup: `pre-cisco-catalog-20260929T104432Z` on-host and off-host.
- Backup SHA-256:
  `e15375b3fbed5c2a116c83ca1a64d64472ec7c544468ce610c01a0b3b34e8acb`.
- Added asset category `Access Points` (ID 16).
- Added manufacturer `Cisco` (ID 7).
- Added model `Cisco Aironet 2702i` (ID 17).
- Added primary model number `AIR-CAP2702I-E-K9` (ID 17).
- Added component definition `RJ-45 Console Port` (ID 114), backed only by the
  existing `port_connector_type = rj45` attribute/option.
- Added four required expected-component rows: Wireless 802.11ac, PoE RJ-45
  1GbE, AUX RJ-45 1GbE, and Console RJ-45.

The resolved model specification reports `802.11ac` and
`2x RJ-45 1GbE, RJ-45 Console`. The model has no custom fieldset and no physical
asset. The production asset count remained 15, duplicate tag/serial checks
remained zero, all seven services remained healthy, HTTPS health returned 200,
and the post-write log scan was clean.
