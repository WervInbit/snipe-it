# Production host migration assessment - 2026-09-17

## Status and boundary

This is a read-only assessment for relocating the current Snipe-IT production
deployment from `10.10.10.33` to the new Debian host at `10.10.10.249`. No
package, network, firewall, Docker, application, database, CUPS, DNS, or host
configuration was changed during the investigation.

Confirmed operator decisions:

- Move Snipe-IT and CUPS to `10.10.10.249`.
- Leave Frigate on `10.10.10.33`.
- The operator will repoint `snipe.inbit` to `10.10.10.249` in pfSense.
- Start with an empty Redis instance; users may need to sign in again.
- Do not combine this relocation with an application, schema, permission, or
  catalogue change.

## Verified source state

The source is Ubuntu 24.04 on an HP ProBook 450 G5 with an i5-8250U, 7.6 GiB
RAM, and 49 GiB free on its root filesystem. The current Snipe-IT application
stack is healthy.

| Item | Verified state |
| --- | --- |
| Application image | `sha256:74b290997593c5efec05c2ad62346ce8f5d7df824a49cc002d125be2abc1500f` |
| Web/edge image | `sha256:7deaed23895dbced50626ba2c5d845bd49fee92916ad5f522e0cae58ce69aca5` |
| Database | MariaDB 11.4.7, 92 tables, 481 migrations, 6.83 MB logical size |
| Core records | 15 assets, 19 users, 4 component instances, 17 workflow runs |
| Queues | zero default, delayed, reserved, and failed jobs |
| Redis | AOF healthy, one live key, approximately 38 MB on disk |
| Public uploads | approximately 38 MB |
| Private uploads | approximately 104 KB |
| Application backups | approximately 218 MB |
| Database volume | approximately 182 MB; use a logical dump, not raw copying |
| Loopback registry | approximately 648 MB |
| Deployment tree | `/srv/snipeit-v1`, approximately 2.6 GB |

The current app, web, queue, scheduler, database, Redis, and edge containers are
healthy with zero restarts except the queue worker's expected periodic restarts.
The app image's `--max-time=3600` worker lifecycle explains those queue restarts.

Production uses externally mounted APP, Passport, database, Redis, agent, and
TLS secrets under `/srv/snipeit-v1/runtime`. Those exact files are part of the
recovery set. The Snipe certificate is valid through 2035. The current release
is `0c5ea2dc16`, with a one-file Select2 hotfix layered onto its app image.

The live app/dependency project still uses `docker-compose.production.yml` plus
the historical rehearsal overlay. The destination must instead validate the
same service behavior with the supported
`docker-compose.production.dependencies.yml` overlay. This is a Compose-only
ownership correction: it must not change images, database schema, data, volume
names, subnet, or application behavior. The exact resolved destination config
must be reviewed during rehearsal.

The source has a usable standalone Docker Compose v2.29.7 binary at
`/srv/snipeit-v1/incoming/0c5ea2dc16/docker-compose-linux-x86_64`; `docker
compose` is not globally available to the migration account. Use the verified
standalone binary for source maintenance and backup commands.

## Verified destination state

The destination is Debian 13 on an Intel NUC12WSKi5 with an i5-1240P, 16 logical
CPUs, 15 GiB RAM, 12 GiB swap, and 198 GiB free on a single 250 GB NVMe. SMART
reports passed health, 9% life used, no media/data-integrity errors, and 168
unsafe shutdowns. The single disk provides no redundancy; a UPS and monitored,
encrypted off-host backups remain operator responsibilities.

The following are not yet production-ready:

- `10.10.10.249` is supplied by DHCP with a two-hour lease. Create a pfSense
  reservation for NIC `48:21:0b:50:5c:88`, or configure an approved static
  address, before deployment.
- The hostname is the generic `debian`; select the permanent production name
  before issuing host-specific monitoring or certificates.
- Docker Engine and Docker Compose are not installed. Debian offers Docker
  26.1.5 and Compose 2.26.1; select and pin the supported package source, then
  run the repository production validator.
- No nftables rules are loaded. Define least-privilege rules for SSH, HTTP,
  HTTPS, and CUPS while keeping MariaDB, Redis, the registry, PHP-FPM, and the
  internal web listener private.
- CUPS is installed but `printer-driver-dymo` is not. Debian 13 provides
  `printer-driver-dymo` 1.4.0-12+b1 and the required `libcupsimage2t64` package.
- The destination currently discovers proxy queues from the old host. These
  suffixed queues are not replacements for the required direct `dymo330` queue.
- SSH currently permits the deliberately weak temporary migration password.
  Replace or lock that account immediately after acceptance.

NTP and the Europe/Amsterdam timezone are correct. The destination can resolve
`snipe.inbit` to the current source and can reach source CUPS on port 631 and
Frigate on port 8971.

## Coupled host services

### Frigate edge

The old edge currently serves both `snipe.inbit` and `frigate.inbit`; its
Frigate route proxies to `https://10.10.10.33:8971`. Because Frigate remains on
the source host:

- keep the old edge running for `frigate.inbit`;
- deploy a Snipe-only edge on `10.10.10.249`;
- do not copy the existing dual-host edge configuration unchanged;
- do not repoint `frigate.inbit`; and
- verify Frigate before and after the Snipe DNS change.

### CUPS and DYMO printers

The source CUPS server listens on port 631 and has no queued jobs. Snipe-IT uses
`CUPS_SERVER=10.10.10.33` and `LABEL_PRINTER_QUEUE=dymo330`.

The source has these direct queues:

- `dymo330`: DYMO LabelWriter 330 Turbo, USB serial `03042604033875`, currently
  connected, shared, and using the `raster2dymolw` filter.
- `dymo99010`: DYMO LabelWriter 400 Turbo, USB serial `05053114095777`, shared
  in CUPS but not currently visible on the USB bus.

Do not migrate CUPS by copying the complete Ubuntu `/etc/cups` tree onto Debian.
Install Debian's DYMO package, preserve the source queue/PPD/options as evidence,
and recreate direct queues with the same names and device serials. Restrict CUPS
to the host, production Docker subnet, and approved administrator network. Move
the physical USB device only after the destination driver and queue are ready.

Migrate CUPS after Snipe-IT is stable. Until then, the relocated app can continue
using source CUPS at `.33`. After a local destination test label succeeds, change
`CUPS_SERVER` to a stable destination endpoint, recreate only the affected app,
queue, and scheduler containers, and print a Snipe-generated canary label.

The disconnected LabelWriter 400 cannot receive an end-to-end migration test.
Recreate its queue only if its location and future use are confirmed; otherwise
retain its exported configuration for later recovery.

## Required preparation

- [ ] Reserve `10.10.10.249` in pfSense and verify the lease survives reboot.
- [ ] Extend or replace the source `codex-migrate-20260901` account for the
  approved window; it is currently documented to expire on 2026-09-18. Disable
  both hosts' temporary migration access after acceptance.
- [ ] Set the permanent hostname and confirm NTP, DNS, gateway, and outbound
  package/registry access.
- [ ] Install a supported Docker Engine and Compose v2, then configure daemon
  startup, logging limits, disk-pressure monitoring, and firewall rules.
- [ ] Create protected `/srv/snipeit-v1` runtime, release, secret, TLS, backup,
  and transfer paths with the runbook permissions.
- [ ] Transfer and checksum the exact current app/web images. Populate the
  destination loopback registry and verify the same repository digests before
  starting Compose. Preserve the current and previous rollback images.
- [ ] Transfer the current release, supported dependency/edge/registry
  overlays, Snipe-only edge config, environment file, APP key, Passport key
  pair, database/Redis passwords, and Snipe TLS files. Do not transfer Frigate's
  private key to the destination unless a separately approved dependency needs
  it.
- [ ] Preserve the source's `172.31.209.0/24` production subnet and matching
  trusted-proxy value unless a reviewed destination conflict requires a change.
- [ ] Decide and configure encrypted off-host backup storage and alerting.
- [ ] Install Debian's DYMO driver and preserve source CUPS queue definitions,
  PPDs, options, and device serials as rollback evidence.
- [ ] Confirm the maintenance window, source retention period, rollback owner,
  and the physical printer-move time.

## Rehearsal before cutover

1. Create a non-authoritative rehearsal snapshot while the source remains live,
   or use a verified recent recovery set.
2. Restore a native MariaDB dump into a fresh destination MariaDB 11.4.7 volume.
3. Restore complete public and private upload trees and the exact APP/Passport
   keys. Restore historical application backups if retained.
4. Start a new empty Redis. Do not restore the source AOF.
5. Validate the destination Compose configuration with the production base,
   dependency, Snipe-only edge, and loopback-registry overlays.
6. Keep queue and scheduler stopped. Keep the destination isolated from normal
   DNS and test it using a client host override or explicit address resolution.
7. Confirm 481 migrations and the source record counts. Do not run migrations
   or any seeder.
8. Verify local-account login, effective permissions, encrypted values, public
   images, private attachments, upload/download, workflows, and a reversible
   canary record on the restored clone.
9. Verify Snipe TLS/headers on `.249` and confirm `frigate.inbit` remains served
   by `.33`.
10. Destroy or isolate the rehearsal data after evidence is captured so it
    cannot be confused with the final synchronized restore.

## Final Snipe-IT cutover

Reserve a 60-minute maintenance window; the expected application interruption
is 20-40 minutes because the live data is small and images/configuration will be
pre-staged.

1. Confirm destination rehearsal evidence, free space, image digests, backup
   destination, DNS change access, and rollback authority.
2. Put source Snipe-IT in maintenance mode. Stop queue and scheduler, wait for
   clean exits, and reconfirm all queue states are empty. Do not run
   `optimize:clear`, which previously removed the Redis-backed maintenance flag.
3. Create a fresh application backup and native transaction-consistent MariaDB
   dump with routines, triggers, events, and binary-safe output.
4. Export matching public/private uploads, backup history, protected runtime
   configuration, APP/Passport keys, and TLS material. Generate and verify
   SHA-256 inventories on both hosts.
5. Restore the final dump and files to fresh destination volumes. Do not copy
   live MariaDB files, run migrations, or run foundation/demo seeders.
6. Start database, empty Redis, app, web, and the Snipe-only edge. Keep queue and
   scheduler stopped and keep normal users in maintenance mode.
7. Validate source counts, migrations, keys, uploads, TLS, headers, and an
   authenticated migrated-user login directly against `.249`.
8. The operator repoints `snipe.inbit` to `.249` in pfSense. Confirm resolution
   from representative clients and account for cached DNS answers.
9. Leave maintenance mode, then start exactly one queue worker and one
   scheduler on the destination. Keep source writers stopped.
10. Run authenticated login, scan, workflow, upload/download, queue, scheduler,
    and health checks. Confirm Frigate remains healthy on `.33`.

Keep the source Snipe database and uploads frozen and retain its maintenance
state throughout the acceptance period. The old edge stays up for Frigate.

## CUPS cutover

Reserve a separate 60-minute printing window. Expected printing interruption is
15-45 minutes.

1. Confirm old and new queues have no pending jobs and retain source queue/PPD
   evidence.
2. Install and validate `printer-driver-dymo` on Debian 13.
3. Move the LabelWriter 330 USB cable to the destination.
4. Create a direct destination queue named exactly `dymo330`, bound to serial
   `03042604033875`, with the approved page/media options.
5. Print a local CUPS test label. Do not proceed on clipping, scaling, media,
   filter, permission, or device errors.
6. Set Snipe-IT's CUPS endpoint to the stable destination endpoint, preserve
   `LABEL_PRINTER_QUEUE=dymo330`, validate Compose, and recreate only the
   affected application services.
7. Print one Snipe-generated canary label and verify its content, size, and
   audit result before reopening normal printing.

Do not replay jobs with an ambiguous completion state. CUPS rollback is
independent: hold new jobs, move USB back, restore the `.33` endpoint, recreate
the affected application services, and test once.

## Acceptance gates

- Destination IP is stable and survives reboot.
- All destination services restart automatically and report healthy.
- Exact app/web repository digests match the source.
- MariaDB remains 11.4.7 with 481 migrations and matching core record counts.
- APP-key encrypted data and Passport-dependent behavior remain readable.
- Public/private files and application backup history have matching checksums.
- Redis starts clean; queue/delayed/reserved/failed counts are zero before
  reopening, and users can sign in again.
- `snipe.inbit` serves the expected internal certificate, redirect, headers,
  login, and `/health` from `.249`.
- Authenticated scan, workflow, upload/download, queue, and scheduler checks pass.
- `frigate.inbit` remains available from `.33`.
- A direct `dymo330` test and Snipe-generated label both succeed from `.249`.
- Off-host backup, monitoring, disk-pressure, certificate-expiry, unhealthy-
  container, and restart alerts are active.

## Rollback boundary

Before destination maintenance mode is removed, rollback is straightforward:
leave destination stopped, restore pfSense resolution to `.33` if changed,
take the unchanged source out of maintenance mode, and restart its queue and
scheduler.

After the first destination write, do not point users back to the stale source
database. Prefer roll-forward. An emergency reverse cutover requires destination
maintenance mode, stopped writers, a new destination database dump and upload
sync, a verified restore to the source, and only then a DNS reversal. The frozen
source, complete recovery set, image archives, and logs should be retained for
at least one agreed business-cycle soak period.

## Outstanding operator choices

- Permanent destination hostname.
- Off-host backup target, retention, and monitoring owner.
- UPS or explicit acceptance of the destination's power-loss risk.
- Whether and when the absent LabelWriter 400 Turbo is made available for
  `dymo99010` validation.
- Exact Snipe and CUPS maintenance windows.
- Temporary source-account validity through the maintenance and rollback window.
- Source Snipe retention/soak period before its containers and data are retired.
