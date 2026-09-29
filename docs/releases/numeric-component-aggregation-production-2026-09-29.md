# Numeric Component Aggregation Production Deployment - 2026-09-29

## Release identity

- Deployed application commit:
  `4751683ac066941f99021ebc2eb1484b7860e67a`.
- Release directory: `/srv/snipeit-v1/releases/4751683ac0`.
- App image:
  `127.0.0.1:5000/inbit/snipeit-app@sha256:98e094a8065bbd75f472e351f05cf97c753da42b5b55ba2b324addaaf30198d6`.
- Web image:
  `127.0.0.1:5000/inbit/snipeit-web@sha256:128513a85a9fe21c9304389e3ca88f55b8dc1995357a5e53479053ed3900cac7`.
- Source bundle SHA-256:
  `6031d8f0e8276044d0d686717117e254ee36da2a9f84ed71b5c6d2d92affb170`.
- Image bundle SHA-256:
  `5acb716a0bec43580230efbfad21156568229380b73b7105464663d76543f357`.

The web digest is unchanged because this release does not change web-runtime
content. The application digest carries the migration, resolver, settings UI,
authorization, and regression coverage. A later documentation-only commit does
not alter the deployed application identity.

## Qualification

- Focused aggregation, lifecycle, catalogue, and authorization coverage passed
  55 tests / 357 assertions against guarded in-memory SQLite.
- The clean-LF production contract suite passed 42 tests / 570 assertions.
- Both exact app and web images passed the production content verifier. The
  verifier itself was corrected to attach standard input to the verification
  container, and the release was requalified after that correction.
- Trivy 0.66 with the current database, `--ignore-unfixed`, and the repository
  ignore policy reported zero HIGH or CRITICAL findings for both exact images.
- The complete candidate Compose configuration validated and pulled before
  maintenance mode began.

## Backup and rollback anchors

- Backup name: `pre-aggregation-20260929T120552Z`.
- On-host directory:
  `/srv/snipeit-v1/backups/pre-aggregation-20260929T120552Z`.
- On-host archive:
  `/srv/snipeit-v1/incoming/4751683ac066941f99021ebc2eb1484b7860e67a/pre-aggregation-20260929T120552Z.tar.gz`.
- Off-host copy:
  `C:\snipeit-production-backups\pre-aggregation-20260929T120552Z`.
- Backup archive SHA-256:
  `5779b1a1b3e516a05215daf225b3c137f740c29d5b17b61d99e86cc3dcf2c47f`.
- Previous release retained at `/srv/snipeit-v1/releases/0df107a50b`.
- Previous environment retained as
  `/srv/snipeit-v1/runtime/production.env.pre-aggregation-20260929T120552Z`.

The recovery set contains a native transaction-consistent database dump,
application backup ZIP, public and private uploads, a fresh Redis snapshot,
runtime configuration, secrets, TLS material, release state, and the exact
pre-change data baseline. Redis `SAVE` returned `OK`; all internal checksums and
archive structures passed on-host and again after the off-host copy.

For an application-only rollback, enter maintenance mode, stop queue and
scheduler, restore the previous environment and `current` release target, and
recreate the application services. The added column is backward compatible
with the previous image. Do not run `migrate:rollback` against the only
production database. Use the complete recovery set only for a confirmed data
rollback, because restoring it discards valid changes made after this release.

## Applied change

- Applied exactly one migration:
  `2026_09_29_120000_add_component_aggregation_mode_to_attribute_definitions`.
- Ran `ProductionPermissionGroupSeeder` once as the standard idempotent upgrade
  step. No complete foundation, demo, or scenario seeder ran.
- Existing attribute definitions defaulted to `sum`; only `ram_speed_mhz` was
  changed to `distinct`. Final distribution is 48 `sum`, 1 `distinct`.
- No component definition, component instance, template, model number, asset,
  or existing attribute row was manually rewritten.

## Acceptance evidence

- `INBIT-AA0010` (`SWNUC12WSKI5000`) changed from the incorrect `6400 MHz` to
  `3200 MHz`; aggregate RAM capacity remains `16 GB`.
- Counts remained 19 users, 17 assets, 4 component instances, 6 workflow
  profiles, 38 workflow items, 20 workflow runs, 14 statuses, and 49 attribute
  definitions.
- Duplicate active asset tags, non-empty asset serials, and component tags are
  all zero.
- The Cisco Aironet model number remains unique and both physical Cisco assets
  created after the catalogue update remain present.
- Laravel reports no pending migrations. Failed jobs and the default, mail, and
  reports queues are empty.
- App, web, queue, scheduler, MariaDB, Redis, and TLS edge are healthy with zero
  container restarts after cutover.
- External HTTPS `/health` and `/login` return HTTP 200. Fresh app, web, queue,
  scheduler, edge, database, and Redis log scans are clean.

Authenticated visual confirmation of the attribute settings selector and the
NUC asset detail remains a normal operator smoke check; no production password,
temporary user, or authentication bypass was introduced for automation.
