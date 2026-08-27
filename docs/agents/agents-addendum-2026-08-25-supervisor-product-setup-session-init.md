# Agent Addendum - 2026-08-25 Supervisor Product Setup Authorization

## Starting Context

- The exact August 25 candidate passed guarded SQLite and MariaDB 11.4.7,
  dependency, production-image, populated-upgrade, rollback, and cold-restart
  checks.
- Owner review established that Supervisor must be able to complete ordinary
  new-product/catalog and workflow setup before V1.
- The current foundation role lacks model/catalog permissions, workflow
  configuration remains superuser-only, and `TestTypePolicy` references
  permission keys absent from the registered permission catalogue.

## Implementation Boundary

- Grant Supervisor the minimum explicit abilities needed for ordinary model,
  model-number/specification, attribute-definition, component-definition, and
  workflow definition setup.
- Keep deletion, irreversible lifecycle transitions, storage-location catalog
  administration, global configuration, and other destructive cleanup with
  Admin.
- Preserve custom permissions when upgrading existing same-name foundation
  groups.
- Add route and policy denial coverage so operators below Supervisor cannot
  reach setup actions by direct URL or API request.
- Do not run PHPStan, real LDAP, or real SMTP acceptance in this block.

## Data Safety

- No destructive database command is authorized.
- Focused PHPUnit runs must clear Laravel caches and use explicit in-memory
  SQLite at the Docker command boundary unless an isolated `snipeit_test`
  MariaDB gate is deliberately started later.
- The populated rehearsal stays on its current image until the replacement
  candidate passes the renewed automated gates.

## Implemented Result

- Registered explicit normal-setup and destructive-lifecycle permission keys
  for models, attributes, component definitions, and workflow definitions.
- Granted Supervisor ordinary setup abilities while retaining destructive
  lifecycle, delete, option-removal, and specification-cleanup abilities for
  Admin; existing custom role grants are preserved by additive upgrades.
- Moved workflow configuration out of the superuser-only route group and
  aligned policies, controllers, navigation, and Blade controls with the new
  contract.
- Added positive and negative authorization coverage across all foundation
  roles, including direct-route requests that bypass hidden controls.
- Closed the migrated-role edge found during promotion: an existing
  Supervisor group retained its historical `models.delete` grant, so asset-
  model and model-number deletion/restoration now additionally require the
  explicit Admin-only lifecycle ability.

## Automated Evidence

- Focused changed surface: 79 tests, 503 assertions.
- Adjacent authorization/catalog surface: 97 tests, 1,205 assertions.
- Final regression slice: 17 tests, 200 assertions.
- Migrated legacy-grant regression slice: 11 tests, 204 assertions; broader
  model/catalog confirmation: 88 tests, 586 assertions.
- Guarded full SQLite: 2,166 tests, 10,553 assertions in 14:32.95.
- Guarded full MariaDB 11.4.7: 2,166 tests, 10,553 assertions in 16:17.02.
- PHP syntax, Blade compilation, route inspection, Composer validation/audit,
  npm high/critical audits, four Node security regressions, the production
  asset build, and `git diff --check` are green.
- PHPStan and real LDAP/SMTP acceptance were intentionally not run, matching
  the V1 boundary above.

## Exact Image And Populated Rehearsal Evidence

- Built and content-verified application image
  `local/inbit-app@sha256:bc1e29d8d9a40a4048e3642419c2b7bbd2555b754bedff37bfc7c6456df8fe33`
  and web image
  `local/inbit-web@sha256:c8bd31489fb6d22ade4579ecd911515ef676f292954c33757fb7c325073df4b2`.
- Blocking Trivy vulnerability/secret scans report zero fixable high/critical
  findings and zero detected embedded secrets on both exact images.
- Forward migration reported nothing pending; the additive production role
  seeder completed and preserved the rehearsal Supervisor's legacy custom
  grant without granting lifecycle authority.
- Runtime Gate probes confirm Supervisor model/workflow creation is allowed,
  model/model-number deletion is denied, and Admin lifecycle deletion remains
  allowed.
- Promotion and a complete seven-service cold restart retained 17 active / 19
  total users, 12 assets, 14 models, 477 migrations, zero failed jobs, 294
  public uploads, and 14 private uploads with identical pre/post manifests.
- All seven long-running services are healthy, the TLS initializer exited 0,
  and the HTTPS login returns 200 with HSTS, CSP, anti-framing, MIME-sniffing,
  and referrer headers.
