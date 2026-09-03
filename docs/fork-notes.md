# Fork Notes

Maintain this log to highlight differences between this fork and upstream Snipe-IT. Add a dated section whenever features change, regressions are fixed, or documentation diverges. Reference pull requests or issues when available.

## Update Log

### 2026-09-03
- Designated the exact source and image pair deployed on 2026-09-01 as the
  internal V1.0.0 production baseline. The annotated `v1.0.0` tag identifies
  commit `1c9131f4c9`; the release record contains the immutable app/web
  digests. Current dependency, CI, documentation, and feature work is post-V1.
- The V1 designation accepts the remaining representative private-file check,
  manual alignment, and named operational ownership as follow-up work. It does
  not turn the fork into a generally supported public distribution.
- Reconciled the V1 release status with the completed data-bearing production
  migration, four-role browser permission matrix, owner-confirmed migrated
  password login, and current seven-service health check.
- Added V1.0.0 release notes and separated remaining release-control,
  representative workflow, manual-alignment, and final-host deployment gates
  from already-complete application implementation.
- Confirmed read-only that the five inherited demo model-number placeholders
  have no assigned assets in the migrated database. Current production seeding
  already excludes them; any deprecation/replacement is explicit data cleanup,
  not a V1 code blocker. No live row was changed.
- Updated Livewire from 3.6.4 to 3.8.7 after GHSA-g3hc-697w-wm82 and refreshed
  the published Livewire client assets. Updated the locked `fast-uri` and
  `postcss-selector-parser` transitive packages to patched releases after the
  refreshed npm audit. Composer and npm high/critical policy checks are green.
- Made the complete SQLite, MariaDB/MySQL, PostgreSQL, and V1 GitHub Actions
  test jobs install the LDAP extension explicitly. This keeps mocked LDAP
  coverage deterministic instead of depending on runner defaults; real LDAP
  integration remains outside the V1 support boundary.
- The refreshed post-V1 source passed 2,170 supported non-LDAP tests with 10,641
  assertions, focused Livewire coverage, the affected CI contract suite, the
  production asset build, and a browser Livewire interaction. The deployed
  V1.0.0 images predate these dependency changes; a later deployment requires
  a separately committed and qualified release.
- Corrected Bootstrap 3 checkbox and radio label gutters for the fork's larger
  custom controls. Stacked, inline, table, and nested-inline form variants now
  keep controls in normal flex flow so labels cannot overlap them as widths or
  translations change.

### 2026-09-01
- Converted the successful data-bearing single-host migration arrangement into
  reusable, secret-free production configuration. The immutable base profile
  still supports external database, Redis, and TLS infrastructure.
- Added an optional production dependency overlay with digest-pinned MariaDB
  and Redis, file-backed database/root/Redis credentials, internal-only
  networking, durable volumes, service health checks, and app/writer
  dependency wiring. Production no longer needs to borrow the isolated
  rehearsal overlay for a self-contained host.
- Added an optional public TLS edge overlay and generic Nginx configuration
  based on the qualified deployment. TLS material is staged through a one-shot
  root container into a restricted volume; the long-running edge remains
  unprivileged, read-only, health-checked, and overwrites forwarded headers.
- Added the qualified loopback-only registry as an optional offline artifact
  transfer profile. It retains repository-plus-digest deployment identities
  without exposing a registry beyond localhost and does not replace transfer
  hashes, image-content verification, scanning, or an archive of record.
- Expanded the production environment template and runbook with overlay
  selection, configuration validation, managed-database native backups,
  certificate renewal, volume inventory, and the rule that host-specific
  secondary proxies and all live secrets/data remain outside the repository.

### 2026-08-25
- Implemented the owner-approved Supervisor product-setup contract. Supervisor
  can perform ordinary model, model-number/specification, attribute,
  component-definition, and workflow setup, while lifecycle transitions,
  destructive removal, and specification cleanup remain Admin-only. The
  additive foundation-role upgrade preserves existing custom grants and is
  now an explicit production-upgrade step.
- Renewed guarded full-suite evidence after the permission split: SQLite and
  MariaDB 11.4.7 each pass 2,166 tests with 10,553 assertions. Focused route,
  policy, navigation, seeder, and denial coverage is included in those totals.
- Hardened upgrades from legacy role data: a preserved Supervisor
  `models.delete` grant is no longer sufficient for model or model-number
  deletion/restoration without the new Admin-only lifecycle grant.
- Updated `paragonie/sodium_compat` to v1.24.2 after the August 18 Ed25519
  public-key-validation advisory. Rebuilt production images and reran current
  Composer, npm, SQLite, MariaDB, production-content, and image-security gates.
- Completed the first populated beta-data migration and rollback rehearsal on
  MariaDB 11.4.7. The 468-migration baseline applies nine fork migrations,
  rolls exact batch 2 back with row/upload parity, and reapplies to 477 ran and
  zero pending migrations.
- Proved candidate image promotion and controlled cold-restart persistence
  without changing users, assets, models, failed-job state, or public/private
  upload manifests.
- Made rehearsal secret generation fail closed: rerunning the runtime preparer
  against managed files now requires explicit `-Force`, preventing accidental
  database/Redis secret rotation.
- Recorded the updated V1 no-go boundary. Supervisor product setup is now
  implemented, automated application/image gates are green, and the populated
  rehearsal is promoted and cold-restart clean. The browser role matrix,
  migrated-password login, managed release rehearsal, and release ownership/
  metadata remain open.

### 2026-08-18
- Recovered the interrupted V1 gate session without repeating already-green
  database work. Retained current-tree evidence confirms both guarded
  non-LDAP SQLite and private MariaDB runs pass 2,128 tests with 10,198
  assertions; real LDAP and SMTP/TLS remain deferred.
- Refreshed the PHPStan level 4 baseline after the old 5,817-error baseline
  produced stale message/count mappings. A baseline-free measurement now
  finds 2,779 errors, the exact CI command is green, and a removed negative
  control proves new errors still fail.
- Rebuilt browser assets and final local production targets, reran Composer
  and npm audits, and retained source/image security, SBOM, and license
  evidence. Blocking app/web image scans have zero fixable high/critical
  findings and zero secrets; the complete app inventory retains 71 unfixed
  Debian findings while the web inventory remains at zero.
- Scoped the root-user Dockerfile misconfiguration exception to legacy,
  unsupported, and local-development images only. The releasable production
  Dockerfile remains unignored and clean.
- Preserved Laravel intended redirects across the complete authentication
  flow. Direct regular-user and administrator logins still use the dashboard,
  while protected Settings, QR/scan, and other deep links resume after local,
  Google, or two-factor authentication; logout clears obsolete redirect state.
- Follow-up PHPStan reruns in both the shared container and a clean locked
  container exposed 3,037 existing ORM dynamic-property findings that are not
  covered by the refreshed 2,779-entry baseline. The static-analysis gate is
  therefore open until its schema-aware execution context is reproducible; the
  retained earlier pass remains evidence, not the current frozen-candidate gate.
- Owner follow-up deferred PHPStan until after V1 because its result is not
  reproducible. The analyzer step was removed from the V1 workflow without
  changing its configuration or baseline; runtime tests, syntax checks,
  dependency/build checks, migration rehearsal, and operator validation remain
  release gates.
- Split private asset and asset-model attachments from ordinary record access.
  New `assets.files.view/upload/manage` and `models.files.view/upload/manage`
  permissions independently gate list/download, upload, and delete behavior in
  the API and asset page. License-tab check-in controls now follow the existing
  `licenses.checkin` permission instead of appearing to every license viewer.
- Confirmed V1 product boundaries: ship the current QR layout; build the
  printer/sticker/resolution-aware label tool and harden Windows battery/smart
  diagnostics after V1. Workflow Profiles and Workflow Items are the canonical
  configurable vocabulary while diagnostic-specific "test" wording and legacy
  `Test*` compatibility remain where useful.
- Implemented the unvalidated-integration V1 boundary. Production now defaults
  to LDAP disabled and an in-memory mail sink with no SMTP secret requirement.
  Runtime guards prevent directory contact even when an upgraded settings row
  still enables LDAP; mail/reset/inventory/test actions are suppressed or
  rejected without logging private message content. Real LDAP and SMTP remain
  unsupported pending their external acceptance rehearsals.
- Hardened sensitive license surfaces. Product keys no longer leak through
  API list output, general search, forbidden key filters/sorts, reports, or CSV
  exports without the key ability. License attachments use the dedicated file
  ability, and web/API seat check-in behavior now agrees on assignment targets
  and non-reassignable entitlements.
- Made the accepted media boundary explicit in policy and UI. Publishing
  workflow evidence into the public asset gallery requires the dedicated image
  upload ability; private evidence is cleaned when its workflow run is deleted;
  asset soft-delete/restore preserves gallery images and private evidence; and
  upload surfaces warn that galleries are public while attachments/evidence
  are controlled but are not credential vaults.
- Corrected the dashboard header only at the affected 768-899px narrow-desktop
  range: combined logo-and-text branding suppresses its redundant text there,
  while the asset-search input and action now share an exact 34px height. The
  mobile layout below 768px and wider desktop layout from 900px are unchanged.

### 2026-08-13
- Ran the first isolated end-to-end V1 production-profile rehearsal with
  disposable MariaDB 11.4.7, authenticated Redis, file-backed secrets,
  durable volumes, HTTPS termination, Mailpit, and immutable local image
  digests. First deployment, CLI bootstrap, authenticated login, maintenance,
  backup/restore, volume persistence, image rollback, queue delivery, and the
  scheduler were exercised without changing the development stack.
- Fixed production PHP-FPM startup: the master must retain root long enough to
  open its configured stderr/access-log descriptors, while request workers
  remain explicitly configured as `www-data`.
- Kept `/health` available during shared Redis maintenance mode and registered
  the fork middleware that owns that exception. Normal application routes
  continue returning 503, allowing Compose to replace and validate app/web
  containers before activation as the deployment runbook requires.
- Declared queued mailable payload properties so they survive Laravel queue
  serialization on PHP 8.2, and added the missing `failed_jobs` migration
  required by the configured `database-uuids` failed-job driver.
- Completed the guarded supported-database gate on disposable MariaDB 11.4.7:
  the strict repository suite passes 2,139 tests with 10,238 assertions.
- Removed two sources of nondeterministic test failure by fixing the importer
  custom-mapping status fixture and accepting the valid clock-boundary range in
  the API rate-limit retry contract.
- Established an owned PHPStan level 4 baseline for 5,817 measured errors,
  corrected the remaining `AttributeValueService` throwing return contract,
  and proved both a green exact CI run and a failing new-error negative control.
- Remediated newly published Composer advisories by updating
  `league/commonmark` and PHP_CodeSniffer, and removed current npm high findings
  by updating Less tooling and replacing AdminLTE's obsolete transitive
  `slimscroll` package. Clean installs, affected tests, Node tests, production
  assets, and current high-severity advisory gates pass.
- Refreshed the fork README and current V1 readiness status. V1 remains a
  no-go pending external LDAP/browser evidence, populated upgrade and rollback,
  production rehearsal and artifact scans, and product/owner approvals.
- Aligned README, CONTRIBUTING, SECURITY, TESTING, and their automated
  documentation boundary with the August 13 readiness status after the
  CommonMark-backed local help regression exposed stale August 4 links.
- Hardened the production application image by refreshing its PHP 8.2 base and
  removing compiler/development packages after extension compilation. Updated
  the web image from NGINX 1.27 / Alpine 3.21 to immutable NGINX 1.30.4 /
  Alpine 3.24.1 inputs, eliminating all current high/critical web-image scan
  findings.
- Production-image CI now retains complete high/critical security reports and
  blocks on fixable vulnerabilities or secrets. Unfixed distribution findings
  remain explicit release evidence rather than being silently ignored or
  making the gate permanently unactionable.

### 2026-08-04
- Removed the remaining Laravel Collective `link_to_route()` calls after the
  retired helper caused authenticated asset detail pages with status-event
  actors to return HTTP 500. Blade views now use standard escaped anchors and
  presenters use the shared escaped `RouteLink` renderer.
- Completed the guarded repository-wide non-LDAP SQLite suite after repairing
  31 diagnostic failures: 2,120 tests and 10,153 assertions now pass. LDAP and
  the supported MariaDB/MySQL job remain explicit separate release gates.
- Hardened supported checkout paths with row-lock/recheck behavior,
  inactive/expired license rejection, and company-boundary enforcement.
- Added deterministic hashed custom-field column fallbacks for names that
  cannot be transliterated, corrected partial component note validation and
  QR/PDF controller response typing, resolved external-URL message
  placeholders, and repaired Slack settings page/component Blade ownership.
- Isolated PHPUnit cache and locale state between cases, while retaining the
  configured locale and within-test API rate-limit behavior.
- Completed full serial PHPStan measurement at 5,887 errors across 549 files;
  this is tracked as an owned-baseline release gate rather than reported as a
  pass. Refreshed the fork README and current V1 readiness status accordingly.
- Verified the complete LDAP test group with the real extension in isolated
  guarded SQLite (18 tests, 75 assertions). The escape regression now checks
  the extension's distinct DN and filter encodings instead of relying on a
  namespace mock that is bypassed when the built-in function is loaded.
- Began the exact disposable MariaDB 11.4.7 matrix and retained its partial
  failure inventory at the user-requested pause; the supported database gate
  remains open.

### 2026-07-23
- Added a separate production Compose profile with immutable PHP/frontend artifacts, external database/Redis and file-backed secrets, read-only service filesystems, durable public/private upload and backup volumes, dedicated PHP-FPM/queue/scheduler services, and service health/restart policies. Production startup now fails closed for missing or placeholder APP/Passport keys and never installs dependencies, generates keys, migrates, or seeds.
- Removed automatic Passport key generation during application-provider boot and automatic Passport installation/database migration from dashboard requests. Missing production signing keys now return a controlled service-unavailable response.
- Added a production deployment runbook covering CI image builds, TLS/reverse-proxy and browser-header requirements, controlled backup/migration order, off-host retention, restore drills, and image/database rollback.

### 2026-07-21
- Added a fail-closed PHPUnit bootstrap that validates and synchronizes the effective test environment before Laravel starts, then rechecks the booted database target before refresh traits can run. Local/default tests are restricted to in-memory SQLite and explicitly reject the persistent `database/database.sqlite`; external MySQL/PostgreSQL CI requires an explicit marker and the exact disposable database name `snipeit_test`.
- Added a separate Dusk database guard so its intentional `migrate:fresh` path can run only against the dedicated `database/dusk.sqlite` target; after validating the exact non-symlink path, it creates the empty file for clean clones.
- Removed legacy checkout behavior from the asset-update API contract: assignment fields now return a controlled 422 before mutation, model-number selection is required when an active preset applies, explicitly supplied factory presets are preserved, and localized API messages no longer depend on the current locale.
- Component display labels and badges now treat the current lifecycle condition as authoritative while retaining legacy-code fallback, and stale agent-report fixtures now use component-backed workflow applicability.
- Demo asset resets now keep foreign keys enabled, remove runtime component hierarchies coherently, retain work-order snapshots with null live-asset links, and delete rather than truncate assets so old numeric IDs cannot be reused by the new demo dataset.
- Docker build filtering now excludes local environment files, backups/dumps/databases, TLS/OAuth keys, runtime uploads, and local storage. A rebuilt audit image contained zero prohibited files in the checked path classes, and the legacy root Dockerfile now handles filtered storage directories defensively. Local/test entrypoints install Composer development dependencies; other environments retain `--no-dev`.
- Replaced the inherited upstream README and security policy with fork-specific pre-V1 documents, added a dated V1 release-readiness audit, and aligned the local demo/testing guides with the actual seed sequence and safety boundary.

### 2026-06-23
- Component detail pages now support adding or changing a component serial after the part has already been removed to tray or otherwise opened later. The serial row is always visible, the edit modal keeps the field locked until explicitly enabled, changing a non-empty serial still requires confirmation, and changes are recorded as `serial_updated` component events.
- Asset workflow history now labels runs by their stored workflow profile snapshot instead of a generic test-run label, and asset workflow status surfaces name missing required workflow profiles consistently.
- The asset table workflow-status tooltip now uses the same per-blocking-workflow readiness summary as asset detail/status changes, so a newer unrelated workflow run does not hide missing or failed required workflows.
- Asset-page tab cleanup planning is documented in `docs/plans/asset-page-tab-cleanup-2026-06-23.md`: Licenses, Images, Files, and Extra files are marked for later rework/investigation, while Devices, Maintenance, and the Send/Upload paperclip nav action are marked for deprecation/removal planning.

### 2026-06-18
- Browser sessions now default to a 30-minute idle lifetime with a client-side warning modal and explicit `Stay signed in` keepalive action. Passive AJAX is not treated as user activity.
- On mobile, the asset Tests / Workflows floating action now starts the currently selected workflow profile instead of only scrolling/focusing the profile selector.
- The fork-specific scan, component workflow, QR label, serial-change, and Tests / Workflows labels now use locale keys, with a first Dutch pass for operational UI text.
- Scanner page text is now supplied through `window.scanConfig.text`, preserving current QR behavior while preparing the camera block for later serial OCR modularization.

### 2026-06-16
- Added a scoped `SamsungGalaxyPhoneCatalogSeeder` for repeatable production-safe Samsung phone catalog additions. It can be run directly for the Samsung phone batch, refreshes workflow component applicability when workflow tables exist, is wired into the production foundation path, and only prunes expected-component templates previously marked as owned by that seeder so manual production additions stay intact.
- Samsung phone catalog seeding now covers black A32 `SM-A325F/DS-6GB-128GB`, A50 `SM-A505FN/DS-4GB-128GB`, and A51 `SM-A515F/DSN-4GB-128GB` variants with model-number specs and expected logic-board, display, battery, camera, audio, wireless, and port components.
- Added `eMMC-opslag` as an `Opslagtype` option for lower/midrange phone storage cataloging, used by the Galaxy A32 128GB storage component.
- Tracked component tags now use an explicit component namespace (`INBIT-C-AB1234` style) instead of looking like asset tags. Existing dev-phase component tags are migrated into the new namespace, generated tags still avoid asset-tag collisions, visible component tags resolve to component detail pages from the scan resolver, and component QR labels say `Component tag` while keeping the stable `CMP:{qr_uid}` QR payload.
- Component removal-to-tray flows now expose a locked serial field. Users must click `Add serial`/`Change serial` before editing; changing an existing non-empty serial requires confirmation and records the old/new serial in the removal event payload. Expected baseline components moved to tray can capture a serial at materialization time.
- Moving a component to tray now redirects to the moved component detail page, including expected baseline rows that become tracked components during the move.

### 2026-06-15
- Asset detail Info rows now stack safely on mobile so specification values stay inside the viewport while preserving the wider two-column layout for desktop/tablet widths.

### 2026-06-14
- Added an opt-in `DevelopmentDeviceScenarioSeeder` for local component-hierarchy development. It seeds `DEV-COMP-*` assets plus tracked parts through the component lifecycle services, covering baseline-only assets, expected-tracked parts, damaged children, removed expected parts, extra/custom components, phone camera cases, tablet edge cases, tray parts, stock parts, and verification states without wiring dev data into production/default seed paths.

### 2026-06-11
- Component catalog seeding now includes structured wireless/network attributes for reusable device research: max Wi-Fi standard using IEEE identifiers (`802.11n`, `802.11ac`, `802.11ax`, `802.11be`), 2.4/5/6 GHz band flags, Bluetooth version, NFC, and max cellular generation.
- Seeded wireless component definitions now include standard-specific selectable entries (`Wireless - 802.11n`, `Wireless - 802.11ac`, `Wireless - 802.11ax`, `Wireless - 802.11be`) while leaving device/region-specific band and cellular details explicit on attributes.
- Camera component seeding now includes optional aperture, autofocus, and OIS attributes plus more phone-oriented camera definitions such as 64MP main, 8MP ultrawide, 5MP macro/depth, 20MP selfie, and 10.5MP selfie. Camera megapixel specs now display grouped component labels instead of relying only on a summed numeric value.
- Wi-Fi and Bluetooth workflow applicability now follows all `Wireless*` component definitions, and rear-camera workflow applicability includes macro and depth camera definitions.
- Added a Samsung Galaxy A51 `SM-A515F/DSN-4GB-128GB` catalog preset with expected display, battery, logic-board, wireless, audio-port, camera, speaker, and microphone components. Shared camera definitions now stay role/megapixel focused so model-specific aperture differences do not leak across reused camera components.

### 2026-06-09
- Added `assets.sale_transition` for Ready for Sale and Sold lifecycle transitions. Production Supervisors receive this permission without the broad legacy `supervisor` override; production Admins receive the actual `admin` permission. The same permission can be granted to experienced refurbishers without making them broad admins.
- Asset status changes now keep sale listing separate from lifecycle state: pre-sale/Ready for Sale does not automatically set `is_sellable`, while Sold, archived, broken/parts, and destroy-style statuses force `is_sellable=0`.
- Asset detail, edit, and bulk status controls now filter Ready/Sold options by the sale-transition permission boundary, and workflow runs can be started with `assets.view` plus `tests.execute` without requiring asset edit rights.
- Component catalog naming now uses `Webcam` and `Wireless` instead of `Webcam Module` and `Wireless Module`; the production foundation seeder renames existing legacy component definition rows before reseeding expected templates and workflow applicability.

### 2026-06-07
- Default seeding is now production-foundation oriented: `DatabaseSeeder` seeds the device catalog, expected component catalog, workflow item/profile catalog, and permission groups without calling demo/destructive seeders for users, companies, locations, departments, suppliers, status labels, depreciation, or demo assets.
- `SettingsSeeder` now only creates settings when none exist and uses the neutral `Snipe-IT` site name instead of `Snipe-IT Demo`.
- Numeric device attribute labels no longer include parenthesized units; units live on the attribute `unit` column and are rendered with numeric values in resolved specs, for example `Werkgeheugen: 8 GB`, `Opslagcapaciteit: 256 GB`, `Schermgrootte: 15.6"`, and `Verversingssnelheid: 60 Hz`.
- Catalog seed helpers avoid factory side effects and restore matching soft-deleted foundation categories, manufacturers, and asset models instead of creating duplicate catalog rows on reseed.

### 2026-06-06
- Clean-start component seeding now includes manually selectable generic fallback definitions for vague or one-off hardware: `USB-A Port - Generic`, `USB-C Port - Generic`, broad HDMI/DisplayPort/eSATA connector entries, RAM, SSD, HDD, battery, camera, keyboard, wireless, and Bluetooth. These are catalog options only and are not automatically assigned to seeded model-number expected components.
- Generic SSD/HDD definitions contribute only storage type, while generic ports contribute only connector type. Version/capability details remain blank until operators refine the expected component or replace it with a more specific definition.
- The Docker app image now normalizes the copied PHP-FPM entrypoint line endings during build so Windows checkouts do not produce a `bash\r` startup failure.

### 2026-06-04
- Component-backed model-number specs now cover all attribute datatypes marked `resolves_to_spec`, not only numeric totals, so seeded RAM/storage/display/battery/keyboard/camera/port values stay on expected components instead of being duplicated as manual selected attributes.
- Clean-start device seeding prunes stale manual model-number attribute rows whenever an expected component already supplies that spec, while leaving intentionally manual policy/product fields such as CPU, OS, release year, color, and Surface keyboard-layout entries intact.
- Clean-start component seeding now includes model-number-specific motherboard/logic-board definitions with expected child subcomponents. HP laptop boards carry CPU/core/GPU specs and own physical ports, Surface boards additionally own soldered LPDDR memory and wireless, and phone boards own LPDDR memory, UFS storage, wireless, and charge/data ports while removable/serviceable parts remain top-level expected components.
- The 3.5mm audio connector catalog is now split into physical connector and role data: `port_connector_type=audio_3_5mm` captures the jack itself, while `audio_port_role` and `audio_jack_standard` distinguish headset combo, headphone out, microphone in, line in, line out, and TRS/TRRS variants on the port component.
- Workflow item applicability still follows components moved under boards because expected subcomponent definitions are included when resolving which workflow items apply to an asset.
- Component Definition settings search now filters as the user types, shows a spinner while debounced paginated results are loading, and still falls back to the existing server-side GET search for full result sets.
- Component Definition expected-subcomponent editing is more compact: child definitions are selected through a searchable picker, and row notes are hidden behind a per-row collapse control unless notes or validation errors are present.
- Attribute Definition settings search now follows the same live-search pattern as Component Definitions, with immediate row filtering, loading/no-match feedback, and broader paginated GET search across label, key, datatype, unit, and category.

### 2026-06-02
- Clean-start catalog seeding now separates reusable product attributes from expected hardware components: removed present-style booleans and old summary attributes are hidden/deprecated, while structured RAM speed, battery capacity, camera, and port capability attributes are seeded for component use.
- Added a component catalog seeder for generic memory, storage, display, battery, port, camera, audio, input, network, and power definitions, plus expected component templates for the real preserved model-number catalog.
- Repeated expected components now display as grouped rows such as `USB-A Port - USB 3.1 Gen1 x3` while still contributing the correct quantity to numeric calculated specs.
- Default seeding no longer calls demo asset creation; demo inventory remains available only through the dedicated demo seeder.
- Removed catalog attributes are tracked in `docs/plans/catalog-removed-attributes-2026-06-02.md` so future reliance can be audited and restored or replaced intentionally.
- Workflow item applicability now supports explicit always-on tasks, component categories, and component definitions, so standard diagnostics can be filtered by the actual expected/attached hardware instead of broad present-style booleans or laptop/mobile category shortcuts.
- Asset workflow start screens can add per-run extra workflow items for one-off checks, while same-asset component hierarchy corrections can move tracked components under another top-level component or back to the asset root.

### 2026-05-26
- Began the test-to-workflow foundation: legacy test tables are migrated into workflow-named tables with workflow profiles, ordered profile items, run/profile snapshots, required-item snapshots, and result label modes for pass/fail versus done/not-done task flows.
- Asset workflow start screens now expose profile selection, and active workflow result cards keep the existing two-button, note, and photo workflow while broadening the labels for task-style profiles.
- Seeded workflow profiles now separate standard diagnostics from pre-sale, cleaning, and shipping-laptop task lists, keeping task-style operational steps out of the standard diagnostic set.
- Agent report ingestion accepts both `test_results` and `workflow_results`, stores workflow run/profile metadata, and returns both `workflow_run_id` and the legacy `test_run_id` field for compatibility.

### 2026-05-19
- Asset selling-state web flows now warn before proceeding when attached damaged or needs-attention components remain on the asset. The warning covers hardware detail status changes, hardware edit status changes, bulk status changes, and the explicit available-for-sale toggle; confirmed actions proceed, while detached parts do not trigger the warning.
- Component instances now support structured instance-level attributes through service/API sync. Instance attributes override component-definition attributes for the same component row during calculated component-derived spec resolution, while definition attributes remain the fallback when no instance override exists. Attribute option propagation and usage/delete safeguards include these instance rows.
- Asset calculated specs are now hierarchy-aware: attached child component values suppress parent component values for the same attribute, detached/non-attached parts do not contribute, and generic specification screens warn when a parent value is ignored in favor of attached child values.
- Asset Components tabs now render hierarchy context inline: attached child components, expected child templates, and removed expected children appear under their parent rows, with damaged/needs-attention issue badges visible while preserving existing expected/default/extra/custom classifications.
- Model-number specification screens now preview nested expected child structure for selected component definitions and link to definition editors, while component-definition and model-number edit surfaces show non-blocking warnings when parent and expected child definitions both contribute the same numeric calculated spec.
- Added a read-only `component-hierarchy:preview-conversion` command that scans production-like data for candidate parent/child component definitions, suggested expected-subcomponent templates, and numeric calculated-spec overlaps without applying any writes.
- Added selected-pair conversion tooling via `component-hierarchy:apply-conversion`: it defaults to dry-run, requires explicit `--pair=parent_id:child_id` selections, only writes with `--apply`, records conversion provenance on created templates, and prints rollback guidance for the exact created rows.
- Added `docs/component-hierarchy-operations.md` as the operator/admin reference for the completed asset/component/subcomponent hierarchy, warning policy, spec precedence, and conversion command workflow.
- Review follow-up hardened hierarchy behavior: parent tray/stock/destruction transitions now carry attached children off the old asset, expected subcomponent templates contribute to calculated specs until materialized children replace them, placement modes are enforced on direct asset vs subcomponent usage, and final destruction now requires destruction-pending state plus a note or verification payload.

### 2026-05-07
- Added the component hierarchy persistence foundation: component instances can now reference one parent component, track the root attached asset, and keep materialization/ancestry fields for later expected-subcomponent workflows.
- Component definitions now carry a `placement_mode` (`asset_only`, `subcomponent_only`, or `either`), with the current default kept at `either` for compatibility while later UI and validation sprints refine behavior.
- Component definitions can now define expected subcomponents, including catalog-backed child definitions or freeform expected child names, with quantity, required flag, notes, and editor-side reorder/delete support.
- Component detail pages now show a read-only child structure with attached child component links and assumed expected subcomponent rows from the component definition.
- Expected subcomponent rows on component detail can now be explicitly tracked, creating an installed child component attached to the parent and decrementing the expected remaining quantity through parent-specific state.
- Attached child components can now be detached from the parent detail page to tray or stock; detached/directly transferred expected children keep a closed ancestry snapshot and remain visible on the parent as removed expected child components.
- Moving a parent component to another asset now carries currently attached child components with it and writes both a parent movement summary and individual child movement events.
- Component instances now split placement from condition through `lifecycle_status` and `condition_status`: attached/tray/stock placement can coexist with `Needs Attention` or `Damaged`, damaged/needs-attention components can still be installed, and destroyed/destruction-pending remains blocked for normal attachment.
- Installing or attaching damaged, needs-attention, and sold/returned components now requires an explicit warning confirmation across web and API install paths; confirmed actions proceed, while destroyed terminal components remain blocked.
- Creating a definition-backed component from an asset now persists the definition name into `component_instances.display_name`, avoiding null-name insert failures when no custom component name is entered.

### 2026-04-23
- Finished the follow-up expected-baseline component UX cleanup: model-number spec previews now treat numeric component-derived values as authoritative, and the stale “manual model value overrides the derived total” messaging was removed.
- Asset `Add / Install Component` pages now keep tray/storage flows intact but convert `New` into a single toggle-driven definition/custom creation form on the same page.
- Tray and component detail pages now use dedicated workflow launch screens instead of embedding install/storage/verification/destruction forms inline.
- Added explicit GET workflow pages for tracked component lifecycle actions (to tray, install, to storage, verification, destruction) with safe return-to redirects back to tray/detail surfaces.
- Hardened test-environment support for the expected-baseline tranche by making the `component_definition_attributes.resolves_to_spec` backfill in `2026_04_21_180000_add_expected_baseline_asset_component_state` cross-database instead of MySQL-only.
- Simplified the asset add/install UX again: tray and storage installs are now merged into one searchable picker, the add-page no longer asks for install notes or installed-as/slot metadata, and the new-component form is hidden by default.
- The asset add-page new-component form now defaults source type to manual and condition to unknown without exposing either field, and it no longer asks for installed-as/slot metadata.
- Removed the model-number `Effective Specification Preview` block entirely so model-number spec pages only show manual attributes and expected-component editing.

### 2026-04-21
- Internal work orders now respect FMCS company scoping in the staff UI: internal list/show and nested asset/task writes are company-scoped for company-bound staff, while the authenticated portal keeps its explicit visibility rules.
- The generic component API update endpoint is now metadata-only; direct lifecycle fields such as `status`, install location, tray holder, verification timestamps, and destruction timestamps are rejected and must go through dedicated lifecycle endpoints.
- Tray-install flows now enforce holder ownership, and the public remove-to-tray API no longer accepts arbitrary `held_by_user_id` reassignment.
- Model-number screens now link to a dedicated Expected Components management page for each model number, with create/update/delete/reorder support for `model_number_component_templates`.
- Asset detail `Components` tabs now keep Expected Components collapsed by default behind an explicit toggle so installed components and history remain the primary visible surfaces.
- The existing scan page now resolves both asset tags and tracked component QR labels (`CMP:{qr_uid}`), redirecting to the correct detail page from the same camera flow.
- Tray-aging escalations now render distinctly in component history/detail as automatic verification escalation events instead of looking identical to manual verification flags.

### 2026-04-16
- Model specification edit now surfaces validation issues in one pass: a top error navigator lists failing attributes with click-to-jump behavior, invalid selected rows/detail panels are visibly highlighted, and required-missing validation now emits per-field errors (`attributes.{id}`) in addition to summary copy.
- Scan page viewport now uses a fluid-width layout (removed fixed 720px caps) with taller portrait-oriented small-screen sizing so camera framing fills more of the phone screen.

### 2026-04-09
- Fixed a runtime regression where `__('Attributes')` could resolve to a translation array and crash shared layout rendering (`htmlspecialchars(): ... array given`), by moving the new attribute-definition warning copy into a non-conflicting translation group (`attribute_definitions.*`).

### 2026-04-02
- Test Type settings now keep the slug field admin-visible but default it to an auto-generated normalized value from the test name, with an explicit manual-override toggle.
- Test Type slugs are now normalized and de-duplicated before validation/save; collisions automatically get numeric suffixes (`-2`, `-3`, etc.) instead of relying on users to hand-author unique slugs.
- Hardware detail page status editing now renders quality grading as its own row instead of bundling it into the same status control block.
- Removed checkout-oriented hardware detail UI for the refurb flow: no checked-out-to side panel, no assigned/deployed rendering inside the status row, and no checkout-date detail line on the asset page.
- Hardware detail delete action no longer uses `Checkin and Delete` wording; the page now consistently shows a plain delete action.
- Hardware edit page no longer exposes the collapsed optional-information section; asset name was moved into the main visible form and notes now appear directly below status.
- Hardware detail QR widget now exposes a single download action for the full rendered label PNG image and no longer shows a `Print PDF` action or a raw-QR download button.
- Hardware detail tests tab icon now uses a clipboard-check symbol instead of a vial, and the history panel heading now has a dedicated translated `status_history` label.
- Hardware detail upload tab no longer uses a special right float, so the paperclip/upload action stays aligned with the rest of the tab list on narrow screens.
- Hardware detail now includes a `Test uitvoeren` shortcut button under the edit action that opens the existing Tests tab in place.
- Hardware detail Tests tab now uses responsive new-run controls: desktop shows the action at the upper left, while phones/tablets get a lower-right floating plus-action button that appears only when the Tests tab is active.
- Hardware detail latest-tests warning is now foldable via the full callout surface and shows a right-side disclosure icon; the mobile tests FAB was also enlarged for thumb reach.
- Hardware detail latest-tests warning now also shows muted helper copy indicating that the block can be unfolded.
- Hardware detail Tests-tab run history now stays in a single full-width column instead of splitting into two columns on desktop.
- Test-run history rows now use a stable label/status/note grid so entries align cleanly under each other on the tests list page.
- On small screens, the shared page title/breadcrumb header no longer keeps its left float, so it can wrap beside the existing floated sidebar hamburger instead of wasting a separate row below the navbar.
- On small screens, the shared content header now preserves a small amount of side padding for the breadcrumb/title block instead of collapsing flush to the edge.

### 2026-03-17
- Added admin UI management for model-number default images on model-number edit screens (upload, caption update, sort-order update, replacement, and delete actions).
- Added web routes/controller flow for model-number image CRUD in the authenticated settings/model management UX.
- Updated model-number image ordering UX to drag-and-drop (with save action) instead of manual numeric order inputs, using a pointer-event handle that supports both mouse and touch interactions.
- Added client-side image previews for model-number image uploads and replacement file selection in admin UI.
- Hardened model-number image ordering integrity: appended uploads now start at sort order `0`, and reorder submissions must include the full image ID set for the model number.
- Reworked model-number image editing into a single-save UX on edit screens: image captions, replacements, reorder state, staged removals, and new uploads now persist with the main model-number save instead of separate image-level save buttons.
- Removed the now-obsolete standalone admin model-number image CRUD/reorder route path from the web UI, keeping the single-save model-number update flow as the only admin write path.
- API model-number image creation now defaults the first created image to `sort_order = 0` when no explicit order is supplied.
- Added explicit destructive-command governance to `AGENTS.md`: shared dev DB destructive actions require in-message approval and preflight context output before execution.

### 2026-03-12
- Added an image-source workflow for hardware with explicit override control: assets now support `image_override_enabled` to switch between model-number defaults and asset-specific override images.
- Added ordered metadata to asset images (`sort_order`) plus source tracing (`source`, optional `source_photo_id`) so downstream consumers can reliably render image galleries.
- Added `model_number_images` to store ordered default image sets per model number (with migration-time backfill from existing model image values).
- Added webshop-oriented API endpoint `GET /api/v1/hardware/{asset}/images` that returns the active image source and ordered image payload.
- Added API CRUD endpoints for model-number default images: `GET/POST/PUT/DELETE /api/v1/model-numbers/{modelNumber}/images`.
- Added test-photo promotion route for refurb flows: `POST /hardware/{asset}/tests/{testRun}/results/{result}/photos/{photo}/promote`, allowing a captured test photo to become an asset override image.

### 2026-02-17
- Quality grading is now tracked directly on assets via a dedicated hardware-detail dropdown (`Kwaliteit A` to `Kwaliteit D`) instead of being handled through the testing/spec workflow.
- Added an asset `quality_grade` field with migration-time backfill from legacy `condition_grade` attribute overrides/model defaults.
- Hardware specification override views now hide the legacy `condition_grade` attribute, and test-type resolution excludes that attribute so grading stays outside test runs.
- Device catalog condition-grade option labels were renamed to `Kwaliteit A` through `Kwaliteit D`.

### 2026-02-12
- Dashboard now includes a camera quick-action card (same style as other summary cards) that links directly to the scan page.
- The dashboard camera card is permission-gated by `scanning` and intentionally uses direct action copy instead of a `View All` footer.
- Dev seeding now includes a broader refurb dataset (10 demo assets with status spread + test runs) and richer demo user personas with asset visibility enabled for operational roles.
- Demo guide documentation now matches seeded account names and includes the full `migrate:fresh --seed` reset workflow.
- Hardware asset list now invalidates stale persisted bootstrap-table state after resets by versioning the table cookie key, preventing "empty assets list" confusion when old filters survive a DB refresh.

### 2026-02-05
- QR preview on the hardware detail page now renders the same label layout used for printed PDFs so on-screen previews match the final output.
- Test run edit links can now target a specific prior run; editing an older run updates its finished timestamp so it becomes the latest run.

### 2026-02-03
- Dashboard widgets now respect permissions: unauthorized summary blocks and charts are hidden, and counts are only computed when permitted.
- Hardware list tables no longer show Checked Out To, Purchase Cost, or Current Value columns in the refurb flow.
- Asset tags and serial numbers now default to uppercase on entry/save, with per-field override toggles to preserve original casing.

### 2025-09-25
- Added contributor guide (AGENTS.md) describing fork workflows and documentation expectations for the fork.
- Expanded the agent handbook with workflow reminders and linked it from README.md and CONTRIBUTING.md.
- Completed the model-number attribute rework (definitions/options admin, model spec editor, asset overrides, and test-run generation from needs-test attributes).

### 2025-09-26
- Converted test run generation and agent ingestion to derive diagnostics from needs_test attributes (with expected spec values).
- Persist asset specification overrides on edit and exposed formatted spec readouts on asset/model pages.
- Added feature and unit coverage around the attribute specification pipeline.
- Hardened asset overrides and test runs: reject override payloads on non-overrideable attributes and block new runs until required model specs are complete.
- Normalized numeric attribute inputs entered with alternate units (e.g., TB, GHz) while preserving the original entry for audit context.
- Added `attribute:promote-custom` artisan command to list recurring custom enum values and promote them to formal options on demand.

### 2025-09-27
- Landed multi-model-number support: schema migrations are in place with backfill, `ModelNumber` Eloquent model, and admin CRUD for adding, updating, deleting, and promoting presets (with primary selection).
- Model specification editor and asset create/edit flows now require/model-number selection; spec and override UIs reload based on the chosen preset, and asset/test views display asset-specific model numbers.
- Added feature coverage for model-number management and refreshed documentation to outline the multi-preset workflow.
- Models can now be created without an initial model number; presets are attached from the Model Numbers panel, and spec/asset flows prompt when a preset is required.
- Migration skips altering the column when running on SQLite (tests already operate with the nullable default schema in memory).

### 2025-09-30
- Removed the unfinished SKU layer in favour of multi-model-number workflows; dropped SKU routes/UI, and added a migration to prune the table/foreign keys.
- Updated asset API responses to expose model number strings and IDs, and aligned factories/tests with the model-number requirement.
- Linked test runs to model numbers so diagnostics follow the selected preset.

### 2025-10-23
- Consolidated per-session agent addenda into `docs/agents/old/agent-progress-2025.md` and trimmed the demo seed data to refurb-focused records and curated assets.
- Hid the legacy hardware-page “Generate Label” button so only the new QR module controls remain visible while we plan the long-term QR/label unification.
- Removed company selectors from the asset form for the current single-company refurb workflow (companies stay in the data model for future reinstatement).
 - Removed checkout/checkin/audit flows; status transitions now drive lifecycle tracking with status event history and notes.

### 2025-11-19
- Refreshed the QR label system for the Dymo LabelWriter 400 Turbo: added dedicated templates for 30334 (57x32 mm), 30336 (54x25 mm), 99012 (89x36 mm), 30256 (101x59 mm), plus the legacy 50x30 mm option, and exposed the picker on the asset page and bulk actions so refurbishers can match whatever roll is loaded.
- Rebuilt the PDF/layout renderer so QR codes and captions scale within a single label (no more text spilling onto extra pages) and added an inline preview/print/download widget that regenerates whenever a new template is selected.
- QR stickers now include a single block of text beside the QR containing the model + preset, serial number, asset tag, and the Inbit company line (no mutable specs/status/property-of text). The default template is now the Dymo 99010 (89×36 mm) roll, the QR column consumes ~90% of the label height, and the asset name/tag block is bottom-aligned so only one sticker prints per asset.
- Demo assets use the actual product names (e.g., “HP ProBook 450 G8”) instead of QA/Intake suffixes to keep the dataset intuitive for testers.
- Latest QR layout polish: only the asset name + asset tag render on the text column, which sticks to the bottom-right with a 5% inner margin while the QR honors the same top/bottom padding—PDFs now open with exactly one page and match the requested framing.
- Raised the QR column so it shares the same top alignment as the text block and hardened the PDF styles to eliminate the stray blank pages; 99010 labels now render as a single page with the QR on the left and asset name/tag on the lower-right.

### 2025-12-23
- Test runs are now generated from configured Test Types (with optional tests and category scoping), and attribute definitions no longer drive test creation via a needs-test flag.

### 2026-01-07
- Asset detail now highlights latest test health (failed/incomplete/missing) and includes a compact latest-tests badge.
- Asset list tables show a new Tests column reflecting latest run health, backed by test run counts in asset list APIs.
- Status changes to Ready for Sale/Sold require confirmation when tests are missing or failed, and the tests active page now prompts before finishing with open failures.

### 2026-01-08
- Latest Tests list column now shows completed/total counts, with lazy hover details for failed/missing tests including note excerpts and photo markers.
- Asset creation now allows custom asset tags (unlocking the auto-generated tag on create), while serial entry warns on duplicates and can be overridden with an explicit allow-duplicate toggle (asset tags remain unique).

### 2026-04-07
- The active test-run screen now removes the large top testing header and keeps save/progress/history controls in the bottom action bar so operators stay focused on the test cards themselves.
- The hardware detail QR print/download panel now renders below the main action buttons instead of sitting mid-stack inside the primary action group.

### 2026-04-09
- Shared bootstrap-table bulk-action toolbars now collapse within the viewport on mobile instead of forcing 400-500px minimum widths that pushed bulk-edit selects and action buttons off-screen.
- The hardware QR widget now constrains its template/printer selects and print button so those controls stay inside the page width on narrow screens.
- The hardware detail `Test uitvoeren` button is now intentionally oversized with larger text and a lighter blue treatment so refurbishers can find the testing action faster.
- Attribute creation now defaults the `key` field to an auto-generated snake_case value derived from label text, with an explicit manual-override toggle for admins who need to set a custom key.
- Attribute keys are now de-duplicated during create by appending numeric suffixes (`_2`, `_3`, etc.) before validation/save, while key immutability on existing attributes remains unchanged.
- Attribute version option editing for enum datatypes now uses drag-and-drop row ordering instead of manual sort-number entry; sort order is synchronized from row position automatically when saving.
- Attribute version save now warns when an enum option draft is filled in but not added to the list, with localized confirmation copy for English and Dutch.
- Model create/edit now hide legacy model-level inventory/request fields (`min_amt`, `eol`, `requestable`) for the refurb flow; these fields are now treated as deprecated UI inputs and kept only for backward-compatible payload handling.
- Model-number create/edit now use serial-style code casing controls: code is uppercased by default with an explicit `Aa` preserve-case override, and server-side normalization enforces the same behavior for direct/manual posts.
- The model-number "default selection" checkbox was removed from create/edit forms; model-number selectors already return individual model-number entries, while primary-model fallback remains in backend compatibility paths.
- Model-number edit and model-number specification edit pages now have route-level breadcrumbs (via `models.numbers.edit` and `models.numbers.spec.edit`) so navigation context matches other model-management screens.
- Hardware create/edit now includes a mobile floating save CTA (fixed bottom) so operators can save without scrolling to form actions on small screens.
- Test types now support persistent drag-and-drop ordering in admin settings; test runs and active test result cards follow this configured order through a new `display_order` workflow.

### 2026-04-21
- Attribute versioning is no longer a user-facing workflow: the admin attribute index/edit UI no longer shows version columns or `New Version`, while datatype remains immutable and attribute keys can now be corrected in place.
- Editing an enum option value now propagates that corrected value to current model-number attributes, asset overrides, and component-definition attribute contributions that reference the same option id; historical test-result expected values remain unchanged.
- Component definitions can now contribute shared specification values through the same `AttributeDefinition` vocabulary used by model specs and asset overrides.
- Model-number specification editing is now unified: manual attributes, expected components, and an effective-spec preview live on the same screen, and legacy expected-component routes redirect to that unified screen anchor.
- Effective spec resolution now understands component-derived values:
- model-number previews aggregate linked expected-component templates
- asset specs resolve with precedence `asset override -> installed components -> manual model value -> expected-component-derived model value`
- asset detail/spec surfaces now show component-derived provenance/source labels instead of treating every inherited value as a plain model value

### 2026-04-23
- The asset Components tab now prioritizes tracked deviations: `Extra` and `Custom` rows render first, followed by a slim `Expected baseline` divider and the expected/default rows.
- Component detail pages now support note editing directly on the detail screen instead of leaving notes trapped behind the old unimplemented full-edit flow.
- Calculated numeric component specs now explain themselves on hardware/detail surfaces by showing separate `Expected/default` and `Extras/custom` subtotals instead of a single opaque total.
- Matching tracked installed components intentionally remain `Extra` until expected baseline quantity is explicitly reduced; only then do matching tracked parts render as `Expected (Tracked)`.
- Remaining web `installed_as` / slot inputs have been removed from component install/transfer workflows and related read surfaces, while lifecycle internals keep backward-compatible support underneath.
- `To Storage` now means a stock-state change first: the web workflows no longer force a storage-location picker up front, and loose components can be assigned to a specific storage location later from the component detail page.
- On the asset Components tab specifically, `To Storage` now opens a confirmation modal on the same page instead of navigating away; the modal keeps the verification checkbox and note field inline while still posting to the stock-move endpoints.
- On the component detail page, installed components now use the same inline confirmation pattern for `To Tray`: the move opens a modal with the note field inline instead of bouncing out to a separate confirmation page.
- Expected-baseline components that have been materialized away from the source asset now remain visible on that source asset as greyed `Removed` rows with only an `Open` action, instead of only shrinking the expected/default list.
- Component detail now makes follow-up handling more explicit by showing a storage-location editor for loose parts, humanized status labels, and a more visible file-upload section above the history table.
- Component detail status handling now follows the asset-style pattern more closely: there is a `Change Status` dropdown with modal confirmations plus a dedicated `Status History` table built from the existing component event log.
- The closed status dropdown on component detail now shows the component’s current status directly instead of a generic `Change Status` label.
- The component-detail status control now uses a select-style field rather than a bootstrap action menu, so it behaves more like other status selectors in the app while still opening the same confirmation modals underneath.
- Loose components can now be explicitly marked `Defective`, and `Needs Verification -> In Stock` no longer forces a storage-location choice first; storage location can still be assigned afterward on the detail page.
- Removed rows on the asset Components tab now keep the row content muted while leaving the `Open` action visually normal, so the follow-up detail action still reads like an active control.
- On the asset Components tab, tracked component names and tags now link directly to the component detail page when the viewer has access, including greyed `Removed` rows.
- On the asset Components tab, expected/default row names now link to the component-definition editor when they are backed by a catalog definition and the viewer has rights to manage that definition.
- On the component detail history, `From asset:` and `To asset:` entries now link to the corresponding hardware detail pages when the viewer can access those assets.

### 2026-05-26
- Workflow execution is being broadened from single standard test runs into selectable workflow profiles.
- The first workflow slice introduces workflow-named tables, profile/profile-item models, migration copy paths from legacy `test_*` data, profile selection when starting an asset workflow, and profile item snapshots for requiredness/order/button labels.
- Admin settings now include a Workflow Profiles page where profiles can be category-scoped, sorted, sale-readiness-blocking, and assigned ordered workflow items.
- Ready for Sale/Sold warnings now evaluate every active applicable profile marked as sale-readiness blocking, while retaining the same acknowledgment flow and legacy latest-run fallback when no blocking profiles are configured.
- Workflow Items remain the reusable vocabulary of checks/tasks and keep compatibility through the existing TestType-backed admin surface for now.

### 2026-06-04
- Workflow Profiles settings now use per-profile item subpages: the index stays compact, each profile has an `Items` action, and included profile items can be reordered with drag-and-drop instead of manual order fields.
- Workflow Items now have their own Settings entry and own the default result button mode (`Pass / Fail` vs `Done / Not Done`); Workflow Profile item pages now focus on adding, removing, and ordering reusable items instead of exposing `Use`/`Required`/button-mode toggles inline.
- Existing enum attribute edit pages now support adding new option values in place, retain pending new options on validation errors, and show clearer in-use warning copy so new values such as `eSATA` are distinguished from edits that update existing model/component rows.
- RJ45 ports are now modeled like other structured ports: `port_connector_type` stays `RJ-45`, `ethernet_speed_max` stores 1GbE/2.5GbE/5GbE/10GbE capability, and seeded RJ45 component definitions are speed-specific while the old generic row is retired.

### 2026-06-06
- Generic fallback component definitions now support quick catalog entries for vague or partially known hardware without automatically adding those fallbacks to seeded model-number expected templates.
- Asset detail pages now collapse routine component-derived provenance into a compact tooltip icon beside the resolved value. Routine tooltips show only the contributing parts, while inline expanded detail is reserved for exception cases such as extras/custom components, reduced default baselines, hierarchy overlap warnings, and overrides.
- Component-derived specs now have a configurable display mode per attribute: admins can keep the old distinct value labels or display grouped component labels for overview attributes such as `port_connector_type`.
- Component definitions now support an editable spec display label, and individual component-definition attributes can be marked for generated label composition in their configured order. This keeps USB/HDMI/audio port summaries maintainable through seed data and settings UI instead of hardcoded asset-page formatting.
- Port catalog seeding now uses the component-label display path for `Poortconnector`: USB-A/USB-C labels include USB standard, DP alt-mode, Power Delivery, Thunderbolt, and sleep/charge details when configured; HDMI includes its version; headset-combo audio is represented from the port role while the lower-level audio jack standard stays out of asset specs.
- Camera position and camera role are no longer seeded as top-level asset specs, leaving camera overview output closer to practical asset-view needs while still allowing detailed component data where needed.

### 2026-06-07
- Asset and model specification readouts now append configured units for numeric attributes while preserving numeric stored values for calculation and comparison. Inch values display with the `"` suffix; other units such as `GB`, `Hz`, and `kg` display beside the value.

### 2026-06-08
- Default seeding now uses an explicit `ProductionFoundationSeeder` for first production setup. It seeds settings, permission groups, status labels, attribute definitions, model-number presets, component definitions, and workflow catalog data without destructive truncation or demo user/company creation.
- Production status labels and permission groups are idempotent foundation rows. Production suppliers have their own seeder, but it is intentionally empty until real supplier names are provided; the old demo `SupplierSeeder` remains separate and is not part of the production foundation path.
