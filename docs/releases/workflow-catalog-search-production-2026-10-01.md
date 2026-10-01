# Workflow Catalogue And Search Production Deployment - 2026-10-01

## Release Identity

- Deployed application commit:
  `2f703760d8649d3a75e519c9a7083a34cc249d0f`.
- Release directory: `/srv/snipeit-v1/releases/2f703760d8`.
- App image:
  `127.0.0.1:5000/inbit/snipeit-app@sha256:a36f1eefd73377919eae520ffb3878769620d8eee07081696a6f15455b6c10d9`.
- Web image:
  `127.0.0.1:5000/inbit/snipeit-web@sha256:985c9d1dbd9a6ec040463ae264e8a4db397194aa995d5a0134398b000b369bc5`.
- Source bundle SHA-256:
  `a1a4c5f662bdcd60a0591fecebcf369999076d224f926e1184e2878e64e7c758`.
- Image bundle SHA-256:
  `b7703639972cc04e2627efbbe5c248e20b038dbe95adf6a69c93f4c02435b359`.

The later documentation-only commit `35bdbcbd44` records candidate evidence but
does not change the deployed application identity.

## Qualification

- The exact normalized release passed 79 tests / 794 assertions across the
  catalogue/search, production-container, framework-backport, and release
  policy boundaries.
- Both exact app and web images passed the production content verifier.
- Trivy 0.66 with the current 2026-10-01 database and repository exception
  policy reported zero unsuppressed HIGH or CRITICAL findings for both images.
- The complete managed-dependencies, TLS-edge, and loopback-registry Compose
  configuration validated before maintenance and again after promotion.

## Backup And Rollback Anchors

- Backup name: `pre-workflow-catalog-20261001T110153Z`.
- On-host directory:
  `/srv/snipeit-v1/backups/pre-workflow-catalog-20261001T110153Z`.
- On-host archive:
  `/srv/snipeit-v1/incoming/2f703760d8649d3a75e519c9a7083a34cc249d0f/pre-workflow-catalog-20261001T110153Z.tar.gz`.
- Off-host directory:
  `C:\snipeit-production-backups\pre-workflow-catalog-20261001T110153Z`.
- Off-host archive:
  `C:\snipeit-production-backups\pre-workflow-catalog-20261001T110153Z.tar.gz`.
- Backup archive SHA-256:
  `159cd2a9edbad8b2063e531f72f1add779823158813ce6d521ee4849c61d8a51`.
- Previous release retained at `/srv/snipeit-v1/releases/4751683ac0`.
- Previous environment retained as
  `/srv/snipeit-v1/runtime/production.env.pre-workflow-catalog-20261001T110153Z`.

The recovery set contains the application backup ZIP, native
transaction-consistent database dump, public/private uploads, a fresh Redis
snapshot, runtime configuration and secrets, TLS state, release state, and the
pre-change data baseline. Internal hashes and archive structures passed on the
production host and after the off-host copy; the outer archive hash is
identical in both locations.

For an application-only rollback, enter maintenance mode, stop queue and
scheduler, restore the previous environment and `current` target, and recreate
the application services. The catalogue additions are compatible with the
previous image. Restore the database/uploads from the complete recovery set
only for a confirmed data rollback because that discards valid changes made
after this release.

## Applied Changes

- No migration was pending or executed.
- Ran only `DeviceAttributeSeeder`, `DevicePresetSeeder`, and
  `DeviceComponentCatalogSeeder`. No foundation, demo, scenario, workflow, or
  permission seeder ran.
- Preserved attribute ID 49 and its internal key `programeerbare_toets`, while
  correcting only the visible label to `Programmeerbare toets`.
- Set the attribute to true on HP ProBook 450 G8 `2E9F8EA#ABH` and HP ProBook
  450 G9 `6A140EA#ABH`.
- Added active, asset-only `Optical Drive - DVD-ROM` and
  `Optical Drive - DVD+/-RW` definitions under `Optical Drives`. Neither has a
  model-number template or component instance.
- The targeted catalogue seed also reconciled four previously documented but
  missing generic definitions: `RAM - Generic`, `Camera - Selfie - 10.5MP`,
  `Wireless - 802.11n`, and `Wireless - 802.11be`. All four are active,
  unassigned, and unique.
- Compared the existing programmable-key workflow item byte-for-byte before
  and after seeding. Its name, slug, instructions, applicability, membership,
  timestamps, and history were unchanged.
- Deployed the Workflow Items and workflow-profile Included/Available live
  search UI. Reordering is disabled while a filter is active.

## Acceptance Evidence

- App, web, queue, scheduler, database, Redis, and TLS edge are healthy. All
  recreated services have zero restarts.
- External HTTPS `/health` and `/login` return HTTP 200. The three Redis queues
  and failed-job list are empty, and Laravel reports no pending migration.
- Production retains 17 active users, 17 active assets, four active component
  instances, 17 model numbers, 49 active attribute definitions, six workflow
  profiles, 39 workflow items, 20 workflow runs, and ten active statuses.
- Active component definitions increased from 118 to 124 only through the six
  unassigned catalogue rows listed above.
- Duplicate active asset tags, non-empty serials, component tags, model-number
  codes, and component-definition names are all zero.
- The production configuration validator passed. The running app, queue, and
  scheduler resolve to the accepted app digest; web and edge resolve to the
  accepted web digest.
- Fresh app, web, queue, scheduler, edge, database, and Redis logs from the
  cutover contain no fatal, SQLSTATE, critical, panic, authentication, or
  permission findings.

No production password, temporary user, or authentication bypass was created.
The remaining operator smoke check is to sign in and confirm quick filtering
on Workflow Items and both workflow-profile lists, including that drag handles
are unavailable while a filter is active.
