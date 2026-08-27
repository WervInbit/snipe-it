# Production container deployment

This is the V1 production container path for the fork. It is separate from
`docker-compose.yml`, which remains the source-mounted local development stack.
The production profile has no bundled database or Redis service and contains no
credential defaults.

## What this profile guarantees

- PHP dependencies and versioned frontend assets are built into immutable
  images. There is no source bind mount, Node service, runtime Composer install,
  key generation, migration, or seeding.
- PHP-FPM, Nginx, the queue worker, and the scheduler run as separate services.
  The PHP-FPM master retains the upstream-required root identity while its
  request workers run as `www-data`; queue and scheduler processes also run as
  `www-data` after a short root-only secret and volume initialization step.
  Nginx runs as its unprivileged image user.
- The application root filesystems are read-only. Public uploads, private
  uploads, and application backups use separate durable volumes. Runtime caches,
  logs, and staged Passport keys use per-container `tmpfs` mounts.
- `APP_KEY`, database and Redis passwords, and both Passport keys are mounted
  from external files. Startup fails before PHP-FPM or Artisan work begins when
  a required secret is absent or malformed.
- The public listener binds to loopback by default. TLS must terminate at a
  separately managed reverse proxy.

The database, Redis, volume backend, reverse proxy, registry, monitoring, and
off-host backup destination remain operator-managed infrastructure.

## Prerequisites

- A clean release checkout and an immutable release tag, preferably a signed Git
  tag or commit SHA.
- Docker Engine with Compose v2 and BuildKit.
- A dedicated external MariaDB/MySQL database matching the release rehearsal.
  PostgreSQL remains outside the declared V1 support matrix until its
  repository-wide migration gaps are fixed and the same populated
  upgrade/rollback rehearsal passes there.
- A dedicated, authenticated Redis instance reachable only from the application
  network.
- A TLS reverse proxy on the same host or a private upstream network.
- An encrypted off-host backup destination and a tested restore environment.

Do not reuse the local development database, Redis instance, `.env`, APP key,
or Passport keys.

## Create the secret files

Keep the directory outside the checkout and back it up through a separate,
encrypted secret-management process:

```sh
sudo install -d -m 0700 /srv/snipeit/secrets
umask 077
printf 'base64:%s\n' "$(openssl rand -base64 32 | tr -d '\n')" \
  | sudo tee /srv/snipeit/secrets/app_key >/dev/null
openssl genpkey -algorithm RSA -pkeyopt rsa_keygen_bits:4096 \
  -out /tmp/snipeit-passport-private.pem
openssl pkey -in /tmp/snipeit-passport-private.pem -pubout \
  -out /tmp/snipeit-passport-public.pem
sudo install -m 0600 /tmp/snipeit-passport-private.pem \
  /srv/snipeit/secrets/passport_private.pem
sudo install -m 0600 /tmp/snipeit-passport-public.pem \
  /srv/snipeit/secrets/passport_public.pem
rm -f /tmp/snipeit-passport-private.pem /tmp/snipeit-passport-public.pem
```

Write the database and Redis passwords to `db_password` and `redis_password`
without a trailing comment or surrounding quotes. Create `agent_api_token` as
an empty file when agent reporting is
disabled (the default), or populate it with a long random token and set a narrow
`AGENT_ALLOWED_IPS` plus dedicated `AGENT_USER_ID` when the integration is
enabled. Keep all six files mode `0600`.

For an upgrade, retain the existing APP key and Passport key pair. Replacing the
APP key makes encrypted application data unreadable; replacing Passport keys
invalidates existing OAuth tokens.

After the release images have been built, scanned, and pushed, copy
`docker/production.env.example` to a protected path outside the repository.
Replace every `.invalid` hostname/address, CIDR, image repository, all-zero
image digest, and secret-file path. The two image digest
values must be the accepted `sha256:` registry digests, not tags. The env file
contains references only, never the secret values.

## Disabled external integrations in V1

The checked-in production profile fixes `LDAP_INTEGRATION_ENABLED=false`,
`MAIL_ENABLED=false`, and `MAIL_MAILER=array`. It mounts no SMTP password and
does not require SMTP host or sender settings. The array transport is an
in-memory sink, not the log transport, so reset links and notification content
are not written to application logs.

The LDAP runtime gate takes precedence over an older database setting. Even if
an upgraded settings row still contains `ldap_enabled=1`, login, import, sync,
troubleshooting, and connection code will not contact a directory while the
runtime gate is false. Local accounts, including a protected emergency
administrator, remain usable.

With mail disabled:

- the login page does not advertise self-service email reset;
- direct reset-email, inventory-email, and mail-test requests fail clearly;
- notification and direct-mail events are suppressed before transport;
- queue workers do not repeatedly fail against a missing relay; and
- an authorized administrator must use the protected user editor to assign a
  temporary local password and communicate it through the approved secure
  handoff process.

Do not set either integration flag to true for V1. Enabling LDAP requires the
real-directory checklist in the implementation plan. Enabling mail requires a
reviewed Compose override with SMTP settings/secret plus real relay, TLS,
delivery, reset, queue, and failure/retry evidence. Mock LDAP and local mail
capture are not sufficient to claim support.

## Build, publish, and validate

Build each target once in release CI from the reviewed commit, scan the images
and their SBOMs, push them to the registry under a commit-specific tag, and
record the registry digest for each target. The production Compose profile does
not contain a `build` section: it deliberately accepts only an explicit image
repository plus required digest.

For a local candidate build before publication, use direct BuildKit commands
with a non-production tag:

```sh
candidate="$(git rev-parse --verify HEAD)"
docker buildx build --load --target app \
  --file docker/production/Dockerfile \
  --tag "local/inbit-app:${candidate}" .
docker buildx build --load --target web \
  --file docker/production/Dockerfile \
  --tag "local/inbit-web:${candidate}" .
```

Do not deploy those local tags. Release CI must publish the accepted artifacts,
then populate `SNIPEIT_APP_IMAGE`, `SNIPEIT_APP_IMAGE_DIGEST`,
`SNIPEIT_WEB_IMAGE`, and `SNIPEIT_WEB_IMAGE_DIGEST` in the protected
environment file.

The examples below use `/etc/snipeit/production.env`. Validate the fully
resolved deployment and pull the exact artifacts before maintenance begins:

```sh
docker compose \
  --env-file /etc/snipeit/production.env \
  -f docker-compose.production.yml \
  --profile production \
  config --quiet

docker compose \
  --env-file /etc/snipeit/production.env \
  -f docker-compose.production.yml \
  --profile production \
  pull app web queue scheduler
```

Do not rebuild on deployment hosts and do not deploy a tag by itself. A
human-readable tag may remain in the registry for discovery, but Compose
resolves the accepted `repository@sha256:digest` identity.

The `queue` and `scheduler` services use the same application image as `app`.

## First deployment and upgrades

Never place migration or seeding commands in a container startup hook. Use one
controlled deployment job.

The production profile fixes `ALLOW_WEB_SETUP=false`. Until both a settings row
and an administrator exist, HTTP setup requests fail with `503` instead of
exposing the inherited browser setup wizard. On a first deployment, run the
reviewed, additive foundation seeder and create the first administrator from an
interactive deployment terminal after step 5:

```sh
docker compose --env-file /etc/snipeit/production.env \
  -f docker-compose.production.yml --profile production \
  run --rm app php artisan db:seed \
    --class=ProductionFoundationSeeder --force
docker compose --env-file /etc/snipeit/production.env \
  -f docker-compose.production.yml --profile production \
  run --rm app php artisan snipeit:create-admin --bootstrap
```

The administrator command prompts for the password without echoing it. Avoid
the `--password` option because command-line arguments can be retained in shell
or process history. `--bootstrap` refuses to run if any active or deleted user
already exists. Do not expose the web service until both commands succeed.

Do not run either first-deployment command during an upgrade. Existing
installations retain their settings and administrators.

1. Confirm the new database and Redis endpoints, secret files, durable volumes,
   free disk space, and off-host backup destination.
2. On an upgrade, put the current application in maintenance mode:

   ```sh
   docker compose --env-file /etc/snipeit/production.env \
     -f docker-compose.production.yml --profile production \
     exec app php artisan down --retry=60
   docker compose --env-file /etc/snipeit/production.env \
     -f docker-compose.production.yml --profile production \
     stop --timeout 180 queue scheduler
   ```

   The production profile stores the maintenance marker in the shared Redis
   cache, so it remains visible when the application container is replaced.
   The `stop` command waits for both writer services to exit. Confirm they are
   stopped before continuing; do not take the backup or run a migration while
   either service is active.

3. Create and export a pre-change database backup only after the queue and
   scheduler are stopped. The application backup command writes to the durable
   backup volume:

   ```sh
   docker compose --env-file /etc/snipeit/production.env \
     -f docker-compose.production.yml --profile production \
     run --rm app php artisan snipeit:backup \
       --filename="pre-deploy-$(date -u +%Y%m%dT%H%M%SZ)"
   ```

   Also snapshot/export the database with the database platform's native tool,
   snapshot both upload volumes, and copy the artifacts off-host. Verify the
   backup inventory before continuing.

   An upgrade must carry the complete production database forward, not recreate
   users through CSV, bootstrap commands, or seeders. The existing
   `users.password` values are one-way password hashes and must be copied
   unchanged with their user IDs, group membership, direct permissions, and
   related history. Users can continue signing in with their existing passwords;
   plaintext passwords are neither required nor recoverable. Keep the existing
   APP key so encrypted custom fields and other encrypted application values
   remain readable, and restore the matching public/private uploads. Decide
   separately whether browser sessions, remember-me state, and OAuth/API tokens
   should survive the cutover; preserving account password hashes does not
   require preserving those active sessions or tokens.

4. Inspect pending migrations without applying them:

   ```sh
   docker compose --env-file /etc/snipeit/production.env \
     -f docker-compose.production.yml --profile production \
     run --rm app php artisan migrate:status
   ```

5. Apply reviewed migrations once:

   ```sh
   docker compose --env-file /etc/snipeit/production.env \
     -f docker-compose.production.yml --profile production \
     run --rm app php artisan migrate --force
   ```

   On a first deployment only, run the two reviewed bootstrap commands shown
   above after the migration succeeds. On every upgrade, merge the release's
   required least-privilege grants into the four named foundation groups:

   ```sh
   docker compose --env-file /etc/snipeit/production.env \
     -f docker-compose.production.yml --profile production \
     run --rm app php artisan db:seed \
       --class=ProductionPermissionGroupSeeder --force
   ```

   This seeder is idempotent and additive: it does not remove custom grants
   already stored on those groups. It can deliberately restore a required
   baseline grant that was explicitly denied on a same-name foundation group;
   use a separately named custom group when a deployment needs a narrower role.
   Preserved legacy grants do not replace newly introduced split abilities. In
   particular, historical `models.delete` alone cannot delete/restore models or
   delete model numbers without the Admin-only `models.manage_lifecycle` grant.
   Do not run the complete foundation seeder during an upgrade, and never run
   demo/scenario seeders in production.

6. Start the exact release application and web images while the shared
   maintenance marker remains active:

   ```sh
   docker compose --env-file /etc/snipeit/production.env \
     -f docker-compose.production.yml --profile production \
     up -d --no-build app web
   docker compose --env-file /etc/snipeit/production.env \
     -f docker-compose.production.yml --profile production \
     ps app
   ```

   Wait for `app` and `web` to report healthy. The HTTP `/health` endpoint is
   deliberately exempt from maintenance mode so container and reverse-proxy
   health checks continue to test PHP and database connectivity during the
   deployment. Normal application routes continue to return the maintenance
   response until the next step.

7. Take the application out of maintenance mode, start the writer services,
   and wait for all four services to report healthy:

   ```sh
   docker compose --env-file /etc/snipeit/production.env \
     -f docker-compose.production.yml --profile production \
     exec app php artisan up
   docker compose --env-file /etc/snipeit/production.env \
     -f docker-compose.production.yml --profile production \
     up -d --no-build queue scheduler
   docker compose --env-file /etc/snipeit/production.env \
     -f docker-compose.production.yml --profile production \
     ps
   curl --fail --silent http://127.0.0.1:18081/health
   ```

8. Run authenticated smoke checks for login, scan, upload/download, a queue-
   delivered notification, and one scheduled-command heartbeat.

   Include at least one migrated local account whose password was not reset
   during the rehearsal. Verify its effective group/direct permissions as well
   as login. A sanitized rehearsal that replaces every password hash cannot by
   itself prove that production credentials survive the migration.

If any step after maintenance mode is enabled fails, leave maintenance mode
active and the queue/scheduler stopped until the release is repaired or the
rollback is complete.

## Reverse proxy and browser security

The production Nginx service is an internal FastCGI/static-file tier, not the
public TLS endpoint. The public reverse proxy must:

- offer TLS 1.2 or newer with managed certificate renewal and redirect HTTP to
  HTTPS;
- connect only to the loopback/private published port and overwrite, rather
  than append untrusted client values to, `X-Forwarded-For`, `Host`, `Proto`,
  and `Port`;
- have its address represented by the narrow
  `APP_TRUSTED_PROXIES` address/CIDR. The entrypoint rejects wildcard and
  zero-prefix trust, hostnames, malformed values, and whitespace-obscured
  entries; provide comma-separated literal IP addresses or CIDRs only;
- preserve the production profile's `Secure`, `HttpOnly`, and SameSite cookie
  behavior;
- preserve and monitor HSTS, CSP, frame, MIME-sniffing, and referrer headers;
- enforce request-size/time limits compatible with the configured 32 MB
  application upload ceiling.

The profile requires `APP_URL=https://...`, `APP_FORCE_TLS=true`,
`SECURE_COOKIES=true`, `ENABLE_HSTS=true`, and `ENABLE_CSP=true`. Verify HSTS
only after every production hostname is permanently HTTPS. Review the actual CSP
against enabled SAML, map, avatar, and object-storage integrations; do not
disable CSP to work around a missing source.

The bundled Nginx tier rejects legacy workflow evidence URLs and all executable
extensions under `/uploads`. Private files are never mounted into Nginx.

## Durability, backups, and restore drills

The named volumes are:

- `snipeit-production-public-uploads`
- `snipeit-production-private-uploads`
- `snipeit-production-backups`

The names can be overridden in the protected production env file. Use a volume
driver with host/disk redundancy and monitoring. Named volumes on a single host
are not a backup. The scheduler runs the application's weekly backup and daily
cleanup commands, but operators must export backups off-host, encrypt them,
monitor age/size/failure, and retain the APP key and Passport keys separately.

The inherited uploaded-backup web restore is destructive and non-atomic. It is
disabled by default through `ALLOW_BACKUP_RESTORE=false`, and the production
Compose profile fixes that value to `false`. Do not enable it for the V1
production profile. Restore the database and matching upload/key snapshots
through the isolated, rehearsed operator procedure instead. The opt-in flag is
only a containment boundary for inherited non-production use; enabling it does
not make an in-request restore atomic or replace a verified pre-restore backup.

Send container stderr/stdout to a central log collector with access controls,
retention, disk-pressure alerts, and alerting for repeated restarts or unhealthy
services. Runtime log tmpfs mounts are intentionally not a durable log archive.

At least once per release candidate, restore the database, both upload volumes,
backup archive, and keys into an isolated environment. Verify encrypted custom
fields, OAuth/API authentication, private attachments, public images, and a
representative workflow before declaring the backup usable.

## Rollback

Keep the previous application and web image digests until the release is
accepted.

- If no migration ran, switch both image tags back and restart all four
  services.
- If a forward-compatible migration ran, keep the database and deploy the prior
  image only when the migration review explicitly says that is supported.
- For an incompatible or partially failed migration, keep maintenance mode on,
  stop app/queue/scheduler, restore the verified pre-change database and upload
  snapshots, restore the matching APP/Passport keys, select the previous image
  digests, and start the stack. Do not improvise with `migrate:rollback` on the
  only production copy.

Record the image digests, migration list, backup identifiers, verification
results, and rollback decision in the release log.
