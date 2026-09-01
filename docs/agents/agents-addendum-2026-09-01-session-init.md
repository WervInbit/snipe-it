# Agent Session Addendum - 2026-09-01

## Objective

Prepare and, after a safe discovery and backup boundary, migrate the
data-bearing temporary Snipe-IT server to the qualified V1 candidate at commit
`1c9131f4c92a02ba75a230cf57ede3b58c5a19ac`.

## Safety boundary

- Treat the temporary target as production because its database and uploaded
  files must survive and remain usable.
- Verify the SSH host fingerprint before authentication and keep the dedicated
  migration credential outside the repository.
- Begin with read-only inventory of the host, Docker deployment, storage,
  database, uploads, configuration shape, and available rollback mechanisms.
- Capture and verify database, file, and deployment backups before maintenance
  mode, image replacement, forward migrations, permission activation, or
  cleanup.
- Do not run `migrate:fresh`, `migrate:refresh`, `migrate:reset`, `db:wipe`,
  reseeding, or speculative cleanup. Any cleanup must be scoped to the Snipe-IT
  deployment and preserve a recoverable pre-change copy.
- Keep unrelated containers and host services outside the migration scope.

## Initial state

- Candidate commit: `1c9131f4c92a02ba75a230cf57ede3b58c5a19ac`.
- Target: dedicated temporary migration account on the existing Ubuntu/Docker
  beta server; address and credentials are intentionally omitted from tracked
  documentation.
- Live LDAP and SMTP integration remain outside the V1 qualification scope.

## Migration checkpoint

- The supplied ED25519 server fingerprint was verified before credentials were
  offered. The dedicated account authenticates by the temporary migration key
  and has passwordless sudo until its stated expiry.
- The legacy stack initially remained live on ports 80/443. Its dirty checkout
  at `eaaf32726` and named volumes were not overwritten, reused, reset, or
  pruned.
- A preflight backup at `preflight-20260901T084607Z` contains the native
  transaction-consistent database dump, deployment configuration, complete
  public uploads, legacy application storage, source diff/status, database
  baseline, file manifests, and SHA-256 inventory. The independently verified
  off-host copy is under `C:\snipeit-production-backups`, outside the repo.
- Exact candidate source and image archives passed transfer hashes. A
  loopback-only registry restored repository-plus-digest identities without
  rebuilding on the deployment host. Candidate image content verification
  passes on the target.
- Separate staging MariaDB, Redis, upload, backup, and TLS volumes were created
  on a non-conflicting Docker subnet. The copied database and upload sets match
  the preflight baseline before migration.
- Nine pending migrations applied cleanly, producing 477 ran and zero pending.
  The additive permission-group seeder completed without changing the recorded
  user, asset, model, or workflow counts.
- Candidate app, internal web, and replacement edge containers were qualified
  on staging ports. `snipe.inbit` and the preserved `frigate.inbit` proxy
  returned HTTP 200, both existing certificate fingerprints matched, expected
  security headers/cookies were present, and no candidate runtime errors were
  observed.
- A second read-only drift check found the live database plus public/private
  upload manifests identical to the staged preflight source. The authoritative
  maintenance-mode backup and drift recheck must still precede cutover.
- Browser login rendered successfully at the staging URL. After explicit
  action-time approval, temporary Refurbisher, Senior Refurbisher, Supervisor,
  and Admin accounts completed the role-route matrix. All four landed on the
  dashboard, permissions matched the operational contract, applicable workflow
  counts were correct, and no browser warnings/errors were recorded.

## Migration result

- Removed all four temporary users after testing and verified 17 total/15
  active users. Also removed the exact eight user create/delete audit rows,
  four login-attempt rows, two generated QR-label files, and reset affected
  sequences to the untouched source values. No OAuth tokens were created.
- Entered maintenance mode on the legacy app and created
  `final-20260901T092030Z`. Its canonical database hash and public/private
  upload-manifest hashes exactly match the rehearsed preflight. All ten backup
  files and the transfer archive pass independent SHA-256 verification in the
  protected off-host copy outside the repository.
- Cut over 80/443 under an automatic rollback trap. The digest-pinned V1 app,
  web, queue, scheduler, database, Redis, and edge are healthy. Snipe health and
  login, the Frigate proxy, HTTPS redirect, certificate fingerprints, security
  headers/cookies, migrations, and recent logs all pass post-cutover checks.
- The active database contains 477 migrations with zero pending, 17 users/15
  active, 12 assets, 14 models, 6 workflow profiles, 29 workflow items, and
  zero failed jobs. Public/private file sets are exact at 294/14.
- The legacy app/web are stopped and restart-disabled, but the legacy database,
  volumes, source checkout, configuration, and final backup remain available.
  No Snipe-IT data or volumes were pruned. A root-readable rollback record is
  stored beside the deployment.
- Captured and independently verified the upgraded database plus manifests in
  `post-cutover-20260901T093703Z`, with a protected off-host copy under
  `C:\snipeit-production-backups`.
- Remaining human acceptance: use an existing migrated account to verify its
  unchanged password and one representative data/file workflow. LDAP and SMTP
  remain disabled and deferred; legacy cleanup remains blocked pending explicit
  acceptance.

## Reusable deployment follow-up

- Added optional, secret-free production overlays for a durable single-host
  MariaDB/Redis pair and an unprivileged public TLS edge. The base production
  profile continues to support externally managed infrastructure.
- Added a digest-pinned, loopback-only registry profile for verified offline
  artifact transfer. It preserves repository-plus-digest deployment identities
  without importing the target host's live registry data or configuration.
- Added `scripts/production/validate-config.sh` to check protected file
  references, placeholder digests, HTTPS origin/trusted-proxy boundaries,
  selected overlays, and Compose v2 resolution without reading secret values
  into output. Both Docker-integrated and standalone Compose v2 installations
  are supported.
- Updated the production environment template, root/Docker readmes, deployment
  runbook, and fork notes. One selected Compose command now remains consistent
  through every documented lifecycle command, preventing accidental loss of
  dependency or edge overlays during an upgrade.
- Local validation passes for the base, dependency, edge, combined single-host,
  registry-only, and complete profiles. The complete set resolves `db`,
  `redis`, `app`, `web`, `queue`, `scheduler`, `edge_tls_init`, `edge`, and
  `registry` under project `snipeit-production`. Shell syntax, whitespace,
  host-neutrality, and plaintext-password scans pass; 23 focused PHPUnit tests
  pass with 292 assertions using the guarded in-memory SQLite database.
- The validator also resolved the real Ubuntu deployment inputs in read-only
  mode with its standalone Compose v2 binary. Its temporary validation copy was
  removed automatically; no running service, data volume, secret, or live
  configuration changed.
