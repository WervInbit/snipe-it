# Agents Addendum - 2026-04-17 Session Init

## Context
- Reviewed `AGENTS.md`, `PROGRESS.md`, `docs/fork-notes.md`, and `TODO.md` before starting work.
- Re-read the recent 2026-04-16, 2026-04-09, 2026-04-07, and 2026-04-02 session addenda to reload the latest environment, testing, and mobile-flow context.
- Read `docs/plans/components-replacement-part-traceability-work-orders.md` end-to-end because that plan is the next major implementation stream.

## Files Reviewed
- `AGENTS.md`
- `PROGRESS.md`
- `docs/fork-notes.md`
- `TODO.md`
- `docs/agents/agents-addendum-2026-04-16-session-init.md`
- `docs/agents/agents-addendum-2026-04-09-session-init.md`
- `docs/agents/agents-addendum-2026-04-07-session-init.md`
- `docs/agents/agents-addendum-2026-04-02-session-init.md`
- `docs/agents/agents-addendum-2026-03-24-session-init.md`
- `docs/agents/agents-addendum-2026-03-19-session-init.md`
- `docs/plans/components-replacement-part-traceability-work-orders.md`
- `docker-compose.yml`
- `docker-compose.local.yml`
- `docker/nginx.conf`
- `docker/nginx.local.conf`
- `docker/app/entrypoint.sh`
- `phpunit.xml`
- `.env.example`
- `.env.testing.example`
- `.env`
- `tests/TestCase.php`
- `tests/DuskTestCase.php`
- `app/Models/Component.php`
- `app/Http/Controllers/Components/ComponentsController.php`
- `app/Http/Controllers/Components/ComponentCheckoutController.php`
- `app/Http/Controllers/Components/ComponentCheckinController.php`
- `app/Http/Controllers/Api/ComponentsController.php`
- `routes/web/components.php`
- `routes/api.php`
- `resources/views/components/index.blade.php`
- `resources/views/components/view.blade.php`
- `resources/views/hardware/view.blade.php`
- `app/Services/QrLabelService.php`
- `database/migrations/2016_03_08_225351_create_components_table.php`

## Session Initialization
- Created this addendum file for today.
- Added the 2026-04-17 session stub to `PROGRESS.md`.
- Treated the current machine as a clean-environment reinitialization rather than a continuation of an already-configured workstation.

## Environment Findings
- This workstation currently matches the local March setup path, not the later April LAN/TLS setup:
- `.env` currently points at the local HTTP dev URL.
- `docker-compose.local.yml` exposes nginx on the local HTTP path and `docker/nginx.local.conf` expects the local host aliases.
- The main compose/nginx stack still references the legacy internal hostname, not the newer internal hostname.
- Recent April notes say the intended mobile/dev hostname had moved to the newer internal HTTPS hostname, so URL setup now has a three-way split:
- localhost local compose path on this machine,
- legacy internal-hostname references still in code/test config,
- documented but not currently applied internal LAN/TLS path in recent addenda.
- `.env.testing` is currently missing.
- `tests/TestCase.php` now refuses to run if `.env.testing` does not exist, if `bootstrap/cache/config.php` exists, or if `.env.testing` does not point to the approved sqlite target.
- `phpunit.xml` already expects sqlite at `/var/www/html/database/database.sqlite`.
- `tests/DuskTestCase.php` and current browser checks still default to the legacy internal HTTPS hostname, so Dusk URL setup will need an explicit decision during environment bring-up.
- `docker/certs/` is not populated in this clean clone, so the documented April HTTPS path is not active yet on this workstation.

## Components Replacement Read-up
- The current `components` module is still the legacy pooled-stock implementation:
- `components.qty` holds pool quantity.
- `components_assets.assigned_qty` tracks partial quantities attached to assets.
- web/API flows are checkout/checkin oriented, not unique-instance oriented.
- the asset `Components` tab in `resources/views/hardware/view.blade.php` currently renders pooled component rows with `assigned_qty` and direct checkin actions.
- `QrLabelService::generateForComponent()` already provides a reusable QR-label pattern for component records.
- The new plan explicitly replaces this model with unique component definitions / instances / events, a persisted tray flow, expected model components, and later work orders / portal features.

## Immediate Carry-Over For Next Step
- Before test execution on this machine, create and validate `.env.testing` against the guarded sqlite path and keep Laravel config uncached (`php artisan optimize:clear`).
- Before Dusk/mobile URL setup, decide whether this workstation should stay on the local compose path or be moved to the documented internal HTTPS path.
- When implementation starts, the main replacement boundary is already identified in the current component model, controllers, routes, views, migration, asset tab, and QR label service.

## Additional Update (Local IP Access)
- Switched the active local dev URL from localhost-only access to LAN IP-based HTTP access for same-network testing.
- Updated `.env` `APP_URL` accordingly.
- Updated `docker/nginx.local.conf` so the local HTTP vhost accepts direct IP-host traffic.
- Fixed the local container bootstrap path in `docker/app/entrypoint.local.sh` to call the current repo-mounted `docker/app/entrypoint.sh` rather than the stale image-bundled `/usr/local/bin/entrypoint.sh`.
- Normalized `docker/app/entrypoint.sh` to LF so the Linux container no longer exits with `env: 'bash\r': No such file or directory`.
- Started the local compose stack under project name `snipeit-local` and verified the login page responds on the LAN-accessible HTTP URL.
- Windows firewall automation could not be completed from this session because `New-NetFirewallRule` returned `Access is denied`; if other LAN devices cannot reach the host, add an inbound TCP allow rule for the published local HTTP port with administrative privileges.

## Additional Update (Implementation Start)
- Started the actual component replacement implementation on branch `codex/components-traceability-foundation`.
- Landed the new traceability/work-order foundation schema:
- `component_definitions`
- `component_storage_locations`
- `component_instances`
- `component_events`
- `model_number_component_templates`
- `work_orders`
- `work_order_assets`
- `work_order_tasks`
- `work_order_user_access`
- Added new foundation models, policies, factories, and services for:
- component definitions / instances / events
- expected model-number component templates
- work orders / work-order assets / work-order tasks
- explicit work-order user visibility
- Added lifecycle service coverage for create, extract, remove-to-tray, install, move-to-stock, verification, and destruction flows.
- Added tray aging command `components:age-tray` and scheduled it in the console kernel.
- Added upload support aliases for `component-instances` and `work-orders`, including `Actionlog` path/url handling.
- Added component-instance QR label generation support and asset/model-number relations for tracked and expected components.
- Replaced the component permissions surface with the new semantics and added work-order / portal permissions in `config/permissions.php`.
- Added focused tests for component lifecycle behavior, component-instance uploads, and explicit work-order portal visibility.
- Created a local `.env.testing` and sqlite test DB file so testing commands can target the guarded isolated DB configuration on this machine.
- Verification completed:
- `php -l` across all changed/new PHP files (pass).
- `php artisan optimize:clear` in the local app container (pass).
- `php artisan migrate --env=testing --pretend --path=database/migrations/2026_04_17_120000_create_component_traceability_tables.php` in the local app container (pass).
- `php artisan about --env=testing` in the local app container confirmed `testing` + sqlite and uncached config.
- Focused PHPUnit execution is still blocked by the current container/dependency state:
- Laravel's `php artisan test` wrapper fails because `SebastianBergmann\Environment\Console` is not installed.
- direct `vendor/bin/phpunit` is unavailable because the binary is not present in the current container image.

## Additional Update (Component Cutover Continuation)
- Continued the replacement past foundation and into the active component web/API surface.
- Enforced global tag uniqueness across both assets and components:
- generated component tags now skip values already present in `assets.asset_tag`
- API validation now rejects manual component tags that collide with asset tags
- Added company-scope participation to `ComponentDefinition` and `ComponentInstance` so FMCS-style visibility continues to apply to the new registry and detail flows.
- Replaced the active component API/controller/presenter/transformer path with `ComponentInstance` semantics and lifecycle actions instead of pooled `qty` / checkout / checkin behavior.
- Corrected component resource routing so API and web routes now bind `{component_id}` consistently.
- Replaced the asset detail `Components` tab with:
- installed tracked components
- expected model-number component templates
- event-derived component history for the current asset
- Switched asset component counts and component-cost display away from the legacy pooled relation and onto tracked instance data.
- Removed component checkout/checkin from the active web route surface.
- Updated or replaced the key component test files to target the new instance model and lifecycle action endpoints, and deleted the obsolete component checkout/checkin tests tied to the old pooled mechanics.
- Verification for this continuation block:
- `php -l` across the newly changed PHP files (pass)
- `php artisan route:list --name=components` in the app container showed only the new component instance routes and action endpoints, with `{component_id}` route parameters

## Additional Update (Cutover Completion Pass)
- Finished the next cutover tranche before any work-order or portal UI expansion.
- Replaced asset component history assembly with an event-driven query in `AssetsController` so the asset page now reads immutable `component_events` directly instead of deriving lineage from current/sourced component relations.
- Included soft-deleted component instances in asset history rendering and preserved post-removal lifecycle events (for example stock/verification/destruction moves) for any component that touched the asset.
- Enforced company-scope propagation inside `ComponentLifecycleService`:
- component creation now derives `company_id` from explicit input, associated assets, definition scope, or actor scope
- install realigns component scope to the destination asset
- FMCS mode now rejects unscoped component creation when no valid fallback scope exists
- Completed the remaining operational component filter work for the new instance model:
- API component index now supports location-hierarchy filtering through component storage locations
- API component index now supports supplier and manufacturer filtering for tracked component instances
- component list location display now uses the new current-location text instead of the old pooled-component location assumption
- Updated the remaining visible operational tabs/counts that still pointed at legacy `Component` records:
- location detail
- company detail
- supplier detail
- manufacturer detail
- Disabled the old registry add button so operational component pages no longer imply a direct create/edit web UI that is intentionally deferred.
- Removed the dead legacy component checkout/checkin controllers and their unused Blade views after confirming they were no longer routed or exercised by tests.
- Added the admin settings catalog surface for the new component metadata:
- `settings.component_definitions.*`
- `settings.component_storage_locations.*`
- Added settings routes, controllers, sidebar entries, settings index cards, and list/create/edit/deactivate/activate pages for both catalogs.
- Added focused coverage for:
- asset component history surviving install/remove/move/delete flows
- FMCS company propagation and scope enforcement in the component lifecycle service
- instance-based component API filtering by location hierarchy, supplier, and manufacturer
- settings authorization and create flows for component definitions and component storage locations
- Verification for this completion pass:
- `php -l` across all changed PHP files and added tests (pass)
- `php artisan route:list --name=settings.component` in the app container (pass)
- `php artisan route:list --path=admin/settings/component-definitions` in the app container (pass)
- `php artisan route:list --path=admin/settings/component-storage-locations` in the app container (pass)
- PHPUnit remains blocked in this workstation/container state, so the new focused tests were added but not executed end-to-end here

## Additional Update (Work Orders + Portal Tranche)
- Built the internal work-order UI on top of the existing `WorkOrder`, `WorkOrderAsset`, `WorkOrderTask`, and `ComponentEvent` foundation instead of adding new tables or a second state model.
- Added internal authenticated route surface:
- `/work-orders`
- `/work-orders/create`
- `/work-orders/{workOrder}`
- `/work-orders/{workOrder}/edit`
- Added nested internal device/task mutation routes under the same work-order surface for add/update/remove actions.
- Added the internal work-order list, create, edit, and detail Blade screens.
- The work-order detail page now contains the four planned sections:
- summary
- devices
- tasks
- component activity
- Work-order devices now support both linked assets and freeform intake rows, with linked assets automatically copying current `asset_tag` and `serial` into snapshot fields on create/update.
- Work-order tasks now support assignment, status changes, customer visibility toggles, customer-facing labels, internal notes, customer notes, and optional device linkage.
- The internal component activity section reads from `component_events` using `related_work_order_id` and `related_work_order_task_id`; it links back to component detail and anchors task-related events to the correct task section.
- Added the authenticated read-only portal pages under:
- `/account/work-orders`
- `/account/work-orders/{workOrder}`
- Portal pages reuse the existing account shell and visibility contract:
- explicit visible-user access
- optional company match for users with `portal.view`
- primary-contact visibility where applicable
- `visibility_profile` behavior is now represented in the UI as:
- `full`: show component activity and customer notes
- `basic`: hide component activity but still show customer-visible tasks and device list
- `custom`: respect `portal_visibility_json.show_components` and `portal_visibility_json.show_notes_customer`
- Updated component detail history rendering so events linked to work orders/tasks now show navigable work-order references.
- Added navigation/entry-point updates:
- work-order item in the internal sidebar for users with `workorders.view`
- staff start-page `Manage` button now points to internal work orders when allowed
- account dropdown and account dashboard button for `My Work Orders` when `portal.view` is present
- Added focused tests covering:
- internal work-order authorization and summary create/update flow
- asset snapshot creation on linked work-order devices
- task create/update flow
- portal visibility filtering and customer-safe rendering
- component detail visibility of linked work-order/task references
- Verification:
- `php -l` across all newly changed PHP files and added tests (pass)
- `php artisan route:list --name=work-orders` in the app container (pass)
- `php artisan route:list --name=account.work-orders` in the app container (pass)
- `php artisan optimize:clear` in the app container (pass)
- targeted `php artisan test ... --env=testing` is still blocked by the existing missing `SebastianBergmann\Environment\Console` dependency in the container
- direct `vendor/bin/phpunit` is still unavailable because the current container image does not ship the binary under `vendor/bin`

## Additional Update (Gap Closure Pass)
- Closed the remaining identified implementation gap in the internal work-order UI by turning `fromAsset` / `toAsset` component activity references into clickable links back to asset detail pages.
- Added focused coverage for the remaining code-review gaps:
- unauthorized internal create/show/edit/update work-order access
- company-matched portal visibility without explicit visible-user entries
- internal sidebar/account-area work-order entry visibility
- start shortcut templates pointing the manage action at internal work orders
- Verification:
- `php -l` on the newly updated gap-closure tests (pass)

## Additional Update (Test Environment Repair + Verification)
- Repaired the container test runtime by installing the missing Composer dev dependencies inside the app container. This restored Laravel's test wrapper and `vendor/bin/phpunit`.
- Replaced the unstable mounted sqlite test target with in-memory sqlite for the PHPUnit path:
- `phpunit.xml` now uses `DB_DATABASE=:memory:`
- `.env.testing.example` now documents sqlite in-memory defaults
- `config/database.php` now honors `DB_DATABASE` for the `sqlite` connection instead of hardcoding the mounted sqlite file
- `TESTING.md` now matches the in-memory sqlite setup
- Fixed the affected work-order/settings tests to disable the app CSRF middleware class consistently for mutating requests.
- Fixed a real UI/runtime issue uncovered by the repaired suite: `ComponentInstance` now has a safe display-name fallback for inherited `display_name` access.
- Verification:
- `php artisan optimize:clear` in the app container (pass)
- `php artisan test tests/Feature/WorkOrders tests/Feature/Components/Ui/ShowComponentTest.php tests/Feature/Settings/ComponentDefinitionSettingsTest.php tests/Feature/Settings/ComponentStorageLocationSettingsTest.php --env=testing` (pass)
- Result: `23` tests passed, `82` assertions

## Additional Update (Broader Regression Continuation)
- Continued the regression pass into the full component feature suite and the broader asset UI suite.
- The browser-reported admin component-page 500s were traced to stale PHP-FPM state still serving the old `ComponentInstance::getDisplayNameAttribute($value)` fatal; the app container was restarted and Laravel caches were cleared after the corrected code was already on disk.
- Fixed a real FMCS bug by changing the component lifecycle/admin settings checks to use the actual settings column name `full_multiple_companies_support`.
- Fixed component web delete logging for `ComponentInstance`.
- Fixed `ModelNotFoundException` handling for `ComponentInstance` so missing/scoped-out web requests redirect to `components.index` instead of failing on the nonexistent `componentinstances.index` route.
- Updated the mutating component web tests to disable the app CSRF middleware class consistently.
- Verification:
- `php artisan test tests/Feature/Components --env=testing` (pass)
- Result: `35` tests passed, `134` assertions
- `php artisan test tests/Feature/Assets/Ui --env=testing` (run completed, not clean)
- Result: broad unrelated/pre-existing asset UI failure surface remains; the component-related asset history test is still passing inside that run

## Additional Update (Local Schema Rollout For Manual Testing)
- Manual browser testing exposed that the local dev MySQL schema had not applied the new component/work-order migration yet.
- Confirmed pending local migrations:
- `2026_04_16_110000_add_display_order_to_test_types_table`
- `2026_04_17_120000_create_component_traceability_tables`
- Patched the component/work-order migration to match this fork's live MySQL schema:
- legacy core FK targets use `INT UNSIGNED`, not `BIGINT UNSIGNED`, so the migration now uses matching integer FK columns for those references
- shortened the `model_number_component_templates` composite index name to fit MariaDB/MySQL identifier-length limits
- removed only the partial tables left by the failed migration attempts and reran the migration successfully
- verification:
- local migration status now shows both new migrations applied
- `php artisan optimize:clear` in the app container (pass)
- `php artisan test tests/Feature/WorkOrders tests/Feature/Components tests/Feature/Settings/ComponentDefinitionSettingsTest.php tests/Feature/Settings/ComponentStorageLocationSettingsTest.php --env=testing` (pass)
- result: `56` tests passed, `211` assertions
