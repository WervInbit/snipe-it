# V1 Release Readiness Audit

- Date: 2026-07-21
- Audited revision: `51208bff3166f37eac9452c646e7f89303d2321e`
- Decision: **Not ready for a V1 release**

## Executive Summary

The fork now represents a distinct refurbishment and device-lifecycle product rather than a lightly customized Snipe-IT installation. Its workflow, component hierarchy, model-number specification, scan, work-order, production-seeding, and permission changes are substantial and valuable. The existing volume-backed local stack is healthy, its login/dashboard smoke test passes, and all migrations are applied. After correcting the focused defects found during this audit, a consolidated guarded slice passes 93 tests with 320 assertions. A clean-clone/new-volume installation and a complete release suite still require rehearsal.

At the start of the audit, five issues were immediate release blockers:

1. Workflow result photos can be uploaded without image validation into a public directory in which the bundled web-server configuration executes PHP.
2. An invalid agent report can persist an empty completed workflow run, and the sale-readiness calculation can interpret that run as having no failures.
3. The locked production dependency graph contains known critical and high-severity advisories in both PHP and JavaScript packages.
4. The Docker build context did not exclude local environment backups, certificates, uploads, database files, or production backup material. This was contained in the audit worktree; a rebuilt audit image reported zero files in each prohibited path class, while credential rotation and automated secret/content scanning remain release tasks.
5. The test safety guard could accept the local MySQL runtime and allow Laravel's database-refresh trait to erase local data. This item was remediated and regression-tested in the audit worktree; a clean full release suite is still outstanding.

The audit also found major authorization, tenant-boundary, lifecycle-integrity, seeding, migration, and test-isolation gaps. These do not erase the progress already made, but they mean the current build should remain an internal pre-V1 system until the release gate in this document is closed.

## Scope And Method

This audit covered:

- fork documentation claims versus controllers, models, policies, services, routes, migrations, seeders, views, and tests;
- explicit open work in `TODO.md`, `PROGRESS.md`, and operational documentation;
- a current comparison with official Snipe-IT;
- dependency and package security audits;
- PHP syntax checks across fork-changed PHP files;
- Docker runtime, route, migration, header, cookie, browser smoke, rebuilt-image, and prohibited-path checks;
- two focused PHPUnit batches that initially used an unsafe effective environment, followed by guarded reruns against forced in-memory SQLite.

The operator-guide tree was deliberately excluded because another active work session owns it. No destructive database command was intentionally invoked as part of the planned audit, but the focused PHPUnit invocation inherited the local database configuration and triggered Laravel's destructive test refresh against the local production-like database; the incident is documented below. A later custom diagnostic wrapper repeated the same unsafe bootstrap path. Production-derived environment backup files and database backups were not opened. The upload issue was confirmed by code and server-configuration review only; no exploit payload was submitted.

## Audit Environment Incident

The Docker app container exports `APP_ENV=local`. At incident time, `phpunit.xml` declared testing/SQLite variables without a forced test marker or a pre-Laravel safety bootstrap, so direct `vendor/bin/phpunit` runs did not produce the intended effective Laravel configuration. The preflight command used Artisan's `--env=testing` and correctly printed testing/SQLite, but that separate process did not prove the target of the later direct PHPUnit process.

`Tests\TestCase::guardAgainstUnsafeTestingConfig()` reads the text of `.env.testing` but never checks the application environment and database connection that are actually active after Laravel boots. The focused PHPUnit batch therefore reached `LazilyRefreshDatabase` under `APP_ENV=local`, `DB_CONNECTION=mysql`, and `DB_DATABASE=snipeit_prod_work`. A later diagnostic PHP wrapper instantiated the same test base directly and repeated the unsafe path before the mismatch was recognized.

A read-only check immediately afterward confirmed:

```text
settings=0
users=0
assets=0
migrations=147
```

The schema was migrated but the local database content was erased. DB-dependent work stopped immediately. MariaDB binary logging is off, so point-in-time recovery was not available from database binlogs.

The newest SQL backup found by filename/metadata scan is:

```text
prodbak/db-snapshots/snipeit_prod_work_pre_docker_update_20260609_094733.sql
modified 2026-06-09 09:47:35 +02:00
size 781,490 bytes
```

Older snapshots and an April production export also exist. Their contents were not opened. The user chose a clean reseed rather than restoration, accepting that unexported local records were lost.

The explicitly authorized recovery sequence ran `ProductionFoundationSeeder`, `ProductionDemoUserSeeder`, and `DemoAssetsSeeder`. Post-seed counts were `settings=1`, `users=5`, `assets=10`, and `workflow_runs=10`. Browser verification logged in as the seeded administrator and loaded the dashboard successfully.

## Remediation Completed During The Audit

The destructive test-bootstrap gap is now closed in the current worktree:

- `tests/phpunit-bootstrap.php` performs a dependency-free target check before Laravel or Composer bootstrap;
- the guard synchronizes `getenv()`, `$_ENV`, and `$_SERVER`;
- local/default PHPUnit runs are limited to in-memory SQLite; the conventional persistent `database/database.sqlite` path is rejected;
- MySQL/PostgreSQL CI requires `SNIPEIT_ALLOW_EXTERNAL_TEST_DATABASE=1` and the exact disposable database name `snipeit_test`;
- `Tests\CreatesApplication` rechecks the booted application before `LazilyRefreshDatabase` initializes;
- Dusk separately requires `SNIPEIT_DUSK_GUARD=1` and the exact non-symlink `database/dusk.sqlite` target before its `migrate:fresh`, creating that empty file for a clean clone only after validation;
- a malicious `DB_CONNECTION=mysql` / `DB_DATABASE=snipeit_prod_work` invocation was rejected in the PHPUnit bootstrap before Laravel started;
- forced in-memory SQLite verification passed with 17 tests and 42 assertions;
- a post-test read confirmed the reseeded local MySQL counts remained unchanged.

## Evidence Snapshot

### Runtime

- Docker `app`, `web`, and `db` services were healthy.
- PHP: 8.2.31.
- Laravel: 11.45.1.
- MariaDB image: 11.4.7.
- `php artisan migrate:status --pending`: no pending migrations.
- `GET /health`: HTTP 200 with a healthy JSON response.
- `GET /login`: HTTP 200; the page rendered in the in-app browser with no console errors.
- The checked local profile reported `APP_ENV=local` and debug disabled.

### Focused tests

Before each Docker PHPUnit batch, Laravel caches were cleared and a separate Artisan `--env=testing` process reported:

```text
APP_ENV=testing
DB_CONNECTION=sqlite
DB_DATABASE=/var/www/html/database/database.sqlite
```

That was not the effective environment of the original direct PHPUnit commands. Laravel log records from those runs are `local.ERROR`, proving that the batches inherited the local profile. Those original results remain useful for diagnosis but are not release evidence:

- Asset/workflow/scan batch: 71 tests, 215 assertions, 4 errors, 9 failures.
- Work-order/component/catalog batch: 75 tests, 455 assertions, 1 failure.
- A full-suite attempt produced no usable result within the audit window and was stopped; this is not recorded as a test failure, but a repeatable full CI run remains a V1 requirement.

Source review classified the first batch as four stale agent fixtures, two local-environment CSRF redirects, four real legacy-checkout API 500s, one model-number rule mismatch, one localization-coupled assertion, and one factory-fixture defect. The second batch's single failure is a real condition-label precedence issue: authoritative `condition_status=damaged` is displayed as the legacy `condition_code=broken` label.

After the test guard fix:

- `TestEnvironmentGuardTest` plus `StartNewTestRunTest`: 17 tests, 42 assertions, all passed.
- Guarded asset/workflow/scan rerun: 71 tests, 222 assertions, 4 errors, 7 failures.
- Guarded work-order/component/catalog rerun: 75 tests, 455 assertions, 1 failure.

The two original redirect failures disappeared, confirming that they were environment/CSRF artifacts. The remaining results are now trustworthy: four stale agent fixtures, four checkout-disabled API 500 paths, the model-number requirement mismatch, one localization-coupled assertion, one deprecated-model-number factory/fixture mismatch, and one damaged/broken component-label precedence defect.

After correcting those defects and adding demo-reset/database-guard regressions, the final consolidated repair slice passed **93 tests with 320 assertions**. It covers `TestEnvironmentGuardTest`, `AgentTestResultsTest`, all of `UpdateAssetTest`, `ComponentDerivedAttributeResolutionTest`, `ComponentLifecycleConditionSplitTest`, `DemoAssetsSeederResetSafetyTest`, and `StartNewTestRunTest`. Guard coverage includes missing-marker rejection, conflicting PHP environment representations, persistent SQLite rejection, clean-clone Dusk file creation, and the explicit PostgreSQL CI path. The preflight was `testing|sqlite|sqlite|:memory:` and a post-run read confirmed the local MySQL counts remained `settings=1`, `users=5`, `assets=10`, and `workflow_runs=10`.

### Static and package checks

- Fork-changed PHP lint: 586 files passed; tracked root file `token.php` failed syntax parsing.
- `composer validate --strict`: valid, with a warning for the exact development constraint `laravelcollective/html: 6.x-dev`; that package is abandoned.
- `composer audit --locked --no-dev`: 38 advisories across 18 production packages: 1 critical, 7 high, 24 medium, 3 low, and 3 without a severity.
- Including development packages: 41 advisories across 21 packages, including 1 critical and 8 high.
- `npm audit --omit=dev`: 26 vulnerable production packages: 3 critical, 9 high, 9 moderate, and 5 low.
- A full package-lock audit reported 53 vulnerable packages when development dependencies were included: 4 critical, 19 high, 20 moderate, and 10 low.
- `git diff --check`: no whitespace errors in the audit-owned changes.

Security advisory counts are a point-in-time result and must be regenerated for any release candidate.

## Release Blockers

### V1-01 - Executable workflow photo upload

Severity: **P0 / security**

`app/Http/Controllers/TestResultController.php:184-193` and `:349-369` accept an uploaded file without an image MIME/size allowlist, retain the client-supplied extension, and move it to `public/uploads/test_images`. The bundled nginx configurations route PHP files beneath the public tree to PHP-FPM (`docker/nginx.conf`, `docker/nginx/default.conf`, and `docker/nginx.local.conf`). An operator who owns a run and has `tests.execute` can reach the update path.

Exit criteria:

- validate decoded image content, MIME, size, and allowed extensions;
- generate the stored extension and filename server-side;
- store workflow evidence outside the executable public tree or serve it through a controlled response;
- deny script execution from every upload directory in nginx and documented Apache configurations;
- add rejection coverage for PHP, HTML/SVG, polyglot/non-image, oversized, and extension/MIME mismatch uploads.

### V1-02 - Empty invalid reports can appear ready

Severity: **P0 / business integrity**

`app/Http/Controllers/AgentReportController.php:91-102` saves a finished run before validating supplied workflow item slugs at `:129-139`. A rejected report can therefore leave an empty completed run. `app/Models/Asset.php:1402-1432` evaluates only existing required results; an empty collection produces neither failed nor incomplete results.

The endpoint bypasses Passport in favor of a static token/IP check. When `AGENT_API_TOKEN` is unset, a non-empty bearer value reaches `hash_equals(null, ...)` and can raise a `TypeError`. The payload also lacks an explicit result-count bound and distinct-slug requirement.

Exit criteria:

- validate the complete report before persistence and wrap run/result creation in one transaction;
- require every blocking applicable item to have one valid result;
- ensure empty or partial completed runs are never passing;
- add regression coverage proving a 4xx response leaves no run or results behind.

### V1-03 - Critical/high dependency advisories

Severity: **P0 / supply chain**

The locked production graph contains a critical advisory in `onelogin/php-saml`, high-severity advisories affecting Laravel and other PHP packages, and critical/high JavaScript advisories including the PDF/export and legacy front-end graph. Several fixes require more than a lock refresh; the Laravel advisories observed do not have a patched Laravel 11 range, while current upstream has moved to Laravel 12.

Exit criteria:

- produce an advisory-by-advisory triage with reachable/not-reachable evidence;
- upgrade all critical and high issues or document a temporary, reviewed mitigation and acceptance owner;
- remove or replace abandoned packages, beginning with `laravelcollective/html`;
- rerun Composer and npm production audits in CI and fail the release job on unaccepted critical/high findings.

### V1-04A - Docker builds include local secrets and backup material

Severity: **P0 / secret exposure**
Status: **Build-context containment remediated in the current worktree; rotation and automated release scanning remain open**

At audit start, `.dockerignore` did not exclude `.env*`, `prodbak`, `docker/certs`, database dumps, SQLite databases, OAuth keys, or upload trees. `docker/app/Dockerfile` copies the public tree and then the repository, and the root Dockerfiles use similarly broad copy patterns. A filename/count-only inspection of the pre-fix application image found 15 environment-named files, including the active environment file and a production-key clone filename, plus 2,079 files under `prodbak`. File contents were not displayed during this audit.

The worktree now applies recursive exclusions for these path classes. A fresh `snipeit-fork:v1-audit` build succeeded; final-filesystem inspection reported `0` environment files other than the template, `0` backup/database files, `0` certificate files, `0` `prodbak` files, `0` OAuth keys, and `0` non-allowlisted runtime upload files. The first verification build usefully caught four nested SQLite files that a non-recursive pattern missed; the recursive correction was rebuilt and rechecked. The legacy root Dockerfile was made tolerant of the filtered storage directories and passes BuildKit's `--check` validation.

Any image built before this correction must still be treated as potentially secret-bearing. The path check is containment evidence, not a content-aware secret scan.

Exit criteria:

- [x] replace the build context with a comprehensive shared `.dockerignore`;
- [x] exclude environment files, certificate/private keys, databases/dumps, backup trees, test evidence uploads, runtime storage files, and local tooling artifacts from the audited app image;
- rotate credentials/keys that were present in any shared or published image;
- scan image layers and the final filesystem by content signature and a maintained secret scanner;
- add a CI assertion that prohibited paths cannot exist in a release image.

### V1-04B - Test guard permitted destructive use of the local database

- Severity: **P0 / verification**
- Status: **Guard remediated in the current worktree; full release verification remains open**

The former safety guard validated the contents of `.env.testing`, not the effective Laravel environment/database. A direct PHPUnit run could pass that guard and run `LazilyRefreshDatabase` against MySQL. This defect erased the audit's `snipeit_prod_work` data.

There is still no complete, repeatable release-test result. The original direct focused suites ran under the wrong environment and cannot serve as release evidence. Their guarded reruns were valid and exposed the fixture/API/condition defects described below; those failing surfaces now pass in the consolidated 93-test repair slice. A complete suite did not finish during this audit.

The configured repository also provides no current release signal: no successful HEAD status/check run was observed, existing workflows do not combine the required audits, static analysis, asset build, migration rehearsal, and test matrix, and inherited image-publishing workflows do not publish this fork.

Completed containment:

- [x] fail closed before Laravel unless the process target matches the explicit test-only allowlist;
- [x] allow only in-memory SQLite for local PHPUnit and reject the conventional persistent application SQLite file;
- [x] recheck the booted Laravel environment, driver, connection, and database before refresh traits run;
- [x] add a canary proving a hostile local MySQL target is rejected in bootstrap;
- [x] protect Dusk and preserve explicit disposable MySQL/PostgreSQL CI coverage.

Remaining exit criteria:

- repeat the two complete focused batches, or their documented replacement release slice, from a clean disposable database and retain the report;
- establish a bounded, repeatable full-suite CI job with retained logs and test reports;
- prove no test uses the local production-like database;
- add negative tests for every P0/P1 fix in this report.
- restore PHPStan configuration (the referenced Larastan extension is missing) and install or remove stale Psalm/PHPMD expectations.

## Major Correctness And Authorization Gaps

### V1-05 - Sale readiness is not bound to the current device definition

`workflow_runs` snapshot `model_number_id`, but `Asset::latestTestIssueSummary()` filters by workflow profile and not the asset's current model number. Changing a device preset can leave an earlier pass valid. The asset edit flow changes `model_number_id` after warning evaluation and does not invalidate or recompute `tests_completed_ok`.

Workflow applicability also reads static expected templates and attached components without honoring reduced expected-component state. A physically removed expected camera or port can remain applicable. Conversely, detached expected top-level components can still contribute calculated specifications because roster aggregation adds their attributes before checking the removed state.

Exit criteria:

- bind readiness to the current model number, workflow profile revision, applicability inputs, and required-result set;
- invalidate/recompute cached readiness whenever any of those inputs changes;
- cover preset changes, removed expected hardware, detached top-level expected components, and profile edits.

### V1-06 - Component API bypasses lifecycle and company boundaries

`app/Http/Controllers/Api/ComponentsController.php:84-103` authorizes only component creation, while accepting company, lifecycle/status, asset, holder, storage, and condition fields. The company rule is not scoped to the acting user's company, and `ComponentLifecycleService::resolveInstanceCompanyId()` trusts an explicit company. Generic API updates can also change serials without the terminal-state guard and traceability event in `ComponentLifecycleService::updateSerial()`.

Exit criteria:

- scope foreign keys through policies and the acting user's company;
- require the relevant install/move/verify/destroy permissions for requested initial state;
- route serial and lifecycle changes through one audited domain service;
- add cross-company, terminal-state, inconsistent-field, and least-privilege API tests.

### V1-07 - Unprotected model-number attribute deletion

The DELETE route at `routes/web.php:197` is only behind authentication. `app/Http/Controllers/Admin/ModelNumberAttributeController.php:81-94` performs no authorization and deletes both the assignment and matching asset overrides. Store and reorder use authorizing requests, making destroy the outlier.

Exit criteria:

- add an explicit policy/FormRequest authorization check;
- scope the requested assignment to its model number;
- add denial tests for ordinary and company-scoped users and a positive least-privilege admin test.

### V1-08 - AssetTest member binding is broken and unscoped

Nested routes use `{assetTest}` while controller methods type-hint `AssetTest $test`. No explicit model binding was found, and the controller does not verify that the selected test belongs to the route's `{asset}`. This is both a functional defect and an insecure-direct-object-reference risk.

Exit criteria:

- align route parameter and controller argument names or add explicit scoped binding;
- enforce parent ownership on every show/update/delete endpoint;
- add CRUD and cross-asset denial tests for web and API routes.

### V1-08A - Attachment mutation requires only view permission

`app/Http/Controllers/Api/UploadedFilesController.php:95-118` uses the generic `view` ability for upload mutation. The generic API routes now include component instances and work orders, so a read-only or portal viewer can add attachments even though the component policy defines a stricter `files` ability.

Exit criteria:

- separate attachment read, create, and delete abilities for every supported parent type;
- use the parent policy's mutation permission, not `view`;
- add read-only, portal, company-scoped, cross-parent, and deletion denial tests.

### V1-09 - Work-order visibility permission is ineffective during creation

The permissions model separates creation from visibility management, but the shared create form exposes visibility controls and `WorkOrdersController::store()` persists them without requiring `manageVisibility`. Update does enforce the distinction. A creator can therefore select broad visibility at creation time.

Exit criteria:

- hide or disable visibility controls without permission;
- ignore/reject unauthorized visibility input server-side;
- add tests for a creator without visibility-management permission.

### V1-10 - Seeded operational roles cannot use the component workflow

`ProductionPermissionGroupSeeder` does not grant the component permissions required by `ComponentInstancePolicy` to the seeded Refurbisher, Senior Refurbisher, and Supervisor groups. The component tab is hidden and component scan targets can resolve to a 403 for intended operators.

Exit criteria:

- publish and implement an approved role-capability matrix;
- verify asset images, workflow execution, scanning, components, work orders, and sale transitions end-to-end for every seeded role;
- keep destructive component permissions explicitly separated.

### V1-11 - Component hierarchy deletion and expected counts can be corrupted

A parent moved off an asset may retain attached children. Web/API deletion only blocks when the parent itself is attached and then soft-deletes it. Database `nullOnDelete` does not run for soft deletion, leaving children linked to an invisible parent; later validation cannot resolve it. Expected-component materialization counters are not reconciled by deletion. Expected-child reparenting likewise does not reconcile the original parent's materialized count.

Exit criteria:

- define and enforce delete/reparent invariants for parents, children, and expected state;
- perform lifecycle operations transactionally;
- restore or transfer expected counters deterministically;
- cover attached children, soft-deleted parents, expected slots, rollback, and action logs.

### V1-12 - Production foundation seeding overwrites operator data

`DatabaseSeeder` always invokes `ProductionFoundationSeeder`. Several catalog seeders deactivate or delete rows not present in the code-owned seed set, including definition attributes, child templates, expected components, presets, and workflow items. The current idempotence test checks counts, not preservation of operator customizations.

The opt-in demo/scenario seeders are also insufficiently guarded: they use a known password, can reactivate/reset a matching superuser, and can delete operational-looking matching data without enforcing a local/testing environment. During this audit, `DemoAssetsSeeder` was changed from foreign-key-disabled truncation to constraint-aware deletion: runtime component hierarchies are removed coherently, work-order snapshots remain with null live-asset links, and asset IDs are not reused. That closes both silent numeric-ID reassociation and attached-without-asset defects, but does not make the seeder appropriate for a populated/shared database. `ProductionPermissionGroupSeeder` replaces complete permission JSON for same-named groups and can strip administrator-added permissions.

Exit criteria:

- define code-owned versus operator-owned records explicitly;
- make routine seeding additive/non-destructive for operator data;
- move pruning into a reviewed migration or explicit maintenance command with dry-run and backup guidance;
- add rerun tests that create operator customizations and prove they survive.
- hard-block demo/scenario seeders outside explicitly allowed disposable environments and require confirmation for destructive scenario cleanup.

### V1-13 - Workflow-table migration is not safely resumable

The tests-to-workflows migration creates, copies, conditionally skips, and drops legacy tables in one `up()`. MySQL DDL auto-commit and swallowed foreign-key retarget exceptions make partial failure/retry unsafe; a non-empty destination can skip a needed copy before the legacy tables are dropped.

Exit criteria:

- split schema creation, parity-checked data copy, cutover, and legacy removal;
- fail closed when counts/foreign keys differ;
- add upgrade tests for representative upstream/pre-fork databases and interrupted/retried states;
- require a tested backup and rollback procedure.

### V1-14 - Status semantics depend on mutable English names

Sale lifecycle helpers infer meaning from status-label names such as Sold, Broken, Parts, and Destroy, while broad status properties can classify other deployable states as pre-sale. Renaming or localizing labels can evade or over-trigger controls. Exception handling also contains redirect-first behavior that can violate API JSON contracts.

Exit criteria:

- use stable status capabilities/identifiers rather than display text;
- document migration/default mapping for existing databases;
- add localized/renamed-status and API error-contract tests.

### V1-15 - Open redirects in scan/component return paths

`ScanController` accepts `return_to` and redirects through `redirect()->to()` without restricting it to a local path. Component workflow validation uses an application-URL string prefix, which also accepts a lookalike hostname such as the application host followed by an attacker-controlled suffix.

Exit criteria:

- accept only normalized relative application paths or compare parsed scheme/host/port exactly;
- reject scheme-relative, encoded, backslash, user-info, and prefix-lookalike URLs;
- add regression tests to both redirect flows.

## Additional Pre-V1 Gaps

Correctness items repaired in the audit worktree:

- Asset update rejects every legacy assignment alias with a controlled 422 before mutation and no longer calls the disabled checkout path.
- Asset update requires a model number when model selection is submitted and active presets exist, while unrelated partial updates remain valid; the asset factory also preserves explicitly supplied deprecated presets.
- Component labels/badges use authoritative `condition_status` with legacy-code fallback, and agent-report fixtures now model current component-backed workflow applicability.
- Demo resets keep foreign keys enabled, delete runtime component hierarchies, preserve work-order snapshots with null live-asset links, and preserve the asset auto-increment so stale unkeyed records cannot attach to replacement assets by reused ID.

Open gaps:

- The asset table badge uses cached `tests_completed_ok` for color even when its tooltip fetches a current summary, so the two can disagree.
- Asset-to-asset component transfer checks `install` rather than the separately defined `move` permission.
- Browser component editing is still an explicit stub; operators can only edit a subset of instance fields.
- The component catalog still contains placeholder MPN/SKU values.
- Legacy/new component lifecycle fields remain dual durable fields, and the older `AssetTest` subsystem remains exposed alongside workflows. V1 needs a compatibility/deprecation policy.
- `config/version.php` reports `v2025.10.16-beta`, which is stale for the audited code.
- Root `token.php` is syntactically invalid and duplicates safer token-management capability. It should not ship.
- `TODO.md` still lists QR layout, scan feedback, naming/email rules, battery-health calculation, tests/tasks terminology, license handling, and removal/consolidation of several legacy asset tabs.
- `.gitignore` ignores the exact `.env` name but not common backup names. This worktree contains production-indicative environment backup filenames and database backups. Their contents were not inspected, but release packaging and accidental-commit controls are insufficient.
- Local TLS material is excluded through this clone's `.git/info/exclude`, not a shared repository rule.
- One model-number-nullability migration uses MySQL-specific SQL for every non-SQLite database; PostgreSQL must be fixed or explicitly removed from the supported V1 matrix.

## Deployment Readiness

The root `docker-compose.yml` is a development profile, not a production deployment:

- it bind-mounts the source tree;
- the application container runs as `0:0`;
- it hard-codes `APP_ENV=local` and `APP_URL=https://dev.inbit`;
- database credentials are static development values;
- it exposes local TLS files;
- no dedicated queue worker or scheduler service is defined.

The checked local HTTPS response did not set a Secure session cookie and did not send HSTS or CSP because the active configuration had `SECURE_COOKIES=false`, `ENABLE_HSTS=false`, `ENABLE_CSP=false`, and `APP_FORCE_TLS=false`. Those values are appropriate only for a deliberately local profile. A production runbook must require secure cookies, trusted-proxy validation, TLS enforcement, reviewed HSTS/CSP rollout, external secret management, durable storage, queue/scheduler supervision, backup/restore tests, mail, monitoring, and log retention.

The nginx version in the development compose file is pinned to the older `nginx:1.25-alpine` line. All runtime base images must be deliberately pinned and refreshed before release.

## Upstream Comparison

The audit fetched official Snipe-IT into temporary local references without changing the configured remotes or working tree.

As of 2026-07-21:

- fork HEAD: `51208bff3166f37eac9452c646e7f89303d2321e`;
- official master: `0099a1a975f4e6c98ece9a30743baa595a94323a`;
- latest observed official semantic release tag: `v8.6.3`;
- merge base: `4fe7bfb8510a03eb8987a0b0f6845ab6ecaafe6a` (2025-08-18);
- divergence from master: 326 fork-only commits and 4,477 upstream-only commits;
- fork changes since the base: 808 files, +89,319/-7,715;
- upstream changes since the base: 7,997 files, +417,057/-232,366;
- 287 paths were modified on both sides.

Current upstream uses Laravel 12 and PHPUnit 11; this fork is on Laravel 11 and PHPUnit 10. A direct bulk merge is therefore not a safe release strategy. Treat this as an independent product line:

1. freeze a supported upstream baseline for V1;
2. inventory upstream security and correctness commits continuously;
3. port changes in reviewed topic groups with fork regression tests;
4. separately plan the Laravel 12/PHP/dependency modernization;
5. never imply that upstream installation, upgrade, security, or support documentation applies unchanged to this fork.

## Documentation Findings

The previous README was almost entirely official Snipe-IT marketing/support text and sent users to upstream installation, issue, community, and security channels. It did not explain the fork's actual capabilities, limitations, or deployment model. It has been replaced as part of this audit.

`SECURITY.md` also claimed support for upstream 7.x/8.x and directed fork vulnerabilities to the upstream vendor. It has been replaced with a pre-V1 fork policy, but a concrete private security contact and response owner still must be published before V1.

`TESTING.md` has been rewritten with the executable test guard, mandatory cache clear, effective database preflight, disposable CI database rules, and Dusk boundary. `CONTRIBUTING.md` still needs fork-specific issue/branch/review and release-evidence guidance.

## Recommended V1 Sequence

### Phase 0 - Contain

- Disable workflow photo uploads or patch V1-01 immediately.
- Prevent empty/partial workflow runs from passing readiness.
- Remove environment backups, DB dumps, certificates, and invalid utility scripts from release packaging; add shared ignore/secret-scanning controls.

### Phase 1 - Make security and integrity enforceable

- Close V1-03 and all authorization/tenant gaps.
- Make status, readiness, component hierarchy, and seeding invariants explicit in domain services.
- Add regression tests for every boundary.

### Phase 2 - Establish a clean upgrade and deployment path

- Make migrations and seeders safe against a production clone.
- Define the supported upstream baseline and dependency modernization plan.
- Build a real production image/profile with workers, scheduler, health checks, external secrets, and restore-tested backups.

### Phase 3 - Prove the candidate

- Clean focused and full test suites in CI.
- Run browser acceptance for each seeded role against a production-like clone.
- Exercise one complete refurb journey: intake, scan, component changes, workflow evidence, readiness, sale transition, work order, and audit export.
- Run dependency, secret, static-analysis, migration-upgrade, backup/restore, and upload-negative tests.

### Phase 4 - Release contract

- Set a truthful version and changelog.
- Publish supported installation/upgrade paths, compatibility policy, security contact, data ownership/seeding rules, and known limitations.
- Resolve or explicitly defer every `TODO.md` item with an owner and target release.

## V1 Go/No-Go Checklist

A V1 candidate is a **go** only when all of the following are true:

- [ ] Every P0 issue in this report is fixed and regression-tested.
- [ ] Authorization and company scoping have a documented, passing role matrix.
- [ ] Readiness cannot survive stale model/profile/hardware applicability.
- [ ] Component hierarchy operations preserve parent/child and expected-count invariants.
- [ ] Foundation seeding preserves operator-owned data on rerun.
- [ ] Upgrade migrations pass clean, populated, interrupted, and rollback rehearsals.
- [ ] Composer and npm audits have no unaccepted critical/high production findings.
- [ ] Focused and full test suites pass in repeatable CI against an isolated database.
- [ ] A production profile and runbook have passed backup/restore and security-header checks.
- [ ] Release packaging contains no environment backup, database dump, private key, or invalid utility file.
- [ ] Version, README, security policy, test guide, upgrade notes, and known limitations describe this fork rather than upstream.

Until those conditions are met, the honest release label is **internal pre-V1 / evaluation build**.
