# Plan: Components Replacement, Part Traceability, Tray Flow, and Customer Work Orders

## Audience
This document is a handoff-ready implementation plan for agents working in this fork who may only have:
- `AGENTS.md`
- this plan file
- the current repository state

Read `AGENTS.md`, `PROGRESS.md`, and `docs/fork-notes.md` first, then read this file end-to-end before changing code.

## Planning Date
2026-04-16

## Status Update (2026-04-21)
This document started as the forward-looking replacement plan. It is now partially historical because a large portion of the plan has already been implemented on the current branch.

### Implemented Status By Tranche
- Phase 1 foundation: implemented
- Phase 2 registry/detail replacement: implemented
- Phase 3 asset integration and event-driven history: implemented
- Phase 4 browser lifecycle and tray workspace: implemented
- Phase 5 tray aging and verification: partially implemented
- Phase 6 internal work-order UI: implemented
- Phase 7 read-only customer portal MVP: implemented

### What Is Implemented
- new component foundation (`ComponentDefinition`, `ComponentInstance`, `ComponentEvent`, storage locations, expected component templates)
- instance-based `/components` registry and detail pages
- event-driven asset component history with soft-delete-aware lineage visibility
- operational asset `Components` tab for tracked components
- browser lifecycle flow:
- manual intake
- extract to tray
- remove to tray
- install
- move to stock
- verification flag/confirm
- destruction pending / destroyed
- delete when not installed
- persistent tray workspace and tray badge
- internal work-order UI and authenticated read-only customer portal UI

### Deltas From The Original Plan
- `verification` replaced the earlier `quarantine` wording as the active workflow language/default destination.
- Component definitions are global catalog records and are no longer company-scoped.
- Serial tracking is intentionally deferred and hidden from the admin settings UI until it is either properly enforced or removed.
- Work-order and portal UI now exist; the remaining work is deeper integration, not initial scaffolding.
- Component tags and asset tags must never overlap and are treated as globally unique identifiers.

### Remaining Scope / Gaps
- model-number expected-component template management UI is still not implemented on model-number screens
- work-order-driven component action UX is still deferred
- component QR/mobile scan workflow is still only groundwork
- tray aging exists, but there is not yet a richer operator reminder/notification system
- portal remains read-only MVP
- full-project regression has not been run
- broader asset UI still has an unrelated older failure surface outside the new component feature area

### Required Design Work
- model-number template management UX
- work-order/task-centered component action UX
- component QR/mobile scan journey
- future customer/account visibility abstraction beyond the current normal-user plus optional-company approach
- final decision on serial tracking: implement or retire

### Verification Status
- `php artisan test tests/Feature/Components/Ui tests/Feature/Settings/ComponentDefinitionSettingsTest.php --env=testing`
- result: `21` tests, `82` assertions
- `php artisan test tests/Feature/WorkOrders tests/Feature/Assets/Ui/ComponentHistoryTest.php --env=testing`
- result: `16` tests, `69` assertions
- `php artisan test tests/Feature/Components/Ui/ShowComponentTest.php --env=testing`
- result: pass
- full-project regression: not yet run

### Recommended Next Tranche
- build model-number expected-component template management UI
- move component actions into work-order/task workflows
- implement the component QR/mobile scan journey
- run broader regression and asset-suite triage after those workflows settle

## Executive Summary
Replace the current Snipe-IT `components` module with a new `Components` experience built around:
- unique physical component instances
- immutable lineage and movement history
- model/PIM-defined expected components
- a persistent operator `Tray` for remove/install flows
- mobile-first QR/search workflows
- later integration with customer-facing work orders/tasks

Do not preserve the old quantity-based component checkin/checkout behavior. Reuse the user-facing `Components` name, route family where useful, shared visuals, shared navigation slots, shared upload patterns, and QR/label infrastructure patterns, but do not keep the old component domain model.

## Locked Decisions
These decisions were made during planning and should be treated as implementation constraints unless the user changes them explicitly.

### Product decisions
- Keep the user-facing module name `Components`.
- Do not keep legacy pooled `components` semantics.
- Do not preserve legacy component checkin/checkout behavior.
- Every extracted physical component must become its own tracked instance.
- Components may originate from:
  - asset extraction
  - purchased stock
  - external/manual intake
- Components may end up:
  - installed in an asset
  - in stock
  - in tray/in transfer
  - needing verification
  - defective
  - destruction pending
  - destroyed/recycled
- The target asset must show component lineage.
- Removing one part from an asset must not automatically change the asset status.
- `Default components` means model/PIM-defined expected components, not real tracked component instances.
- The asset `Components` tab should show:
  - installed tracked components
  - removed/history
  - expected/default components hidden by default
- Operators need a persistent `Tray` workflow:
  - remove component from asset into tray
  - carry it while navigating
  - install into another asset, move to stock, or move to verification
- Tray is real persisted state, not browser-only state.
- Tray aging must be monitored so stale floating components become visible and require verification.
- QR labels are desired for component instances, but the system must work before the printer exists.
- Customer work orders/tasks are required, but customer access is read-only for now.
- Customer visibility should default to broad visibility now, while remaining configurable later.

### Implementation decisions
- Reuse the `Components` menu slot and general visual shell.
- Replace old domain logic instead of extending it.
- Prefer new internal classes such as `ComponentDefinition`, `ComponentInstance`, and `ComponentEvent` over mutating the old pooled `Component` meaning.
- It is acceptable to keep route names or view folder names user-facing as `components`, even if the internal domain model changes.
- Do not auto-move stale tray items into stock.
- Use server-side persisted timestamps plus a scheduled job/command for tray aging.

## Legal / Compliance Caveat
This plan supports traceability and auditability, but it does not by itself make the fork legally compliant.

The legal concern appears to be in the Dutch WEEE/AEEA / reuse / destruction area. The exact regulation is still not pinned down. Build the system so it can support inspections and chain-of-custody, but do not claim full legal compliance in code, docs, or UI without a verified legal reference.

Implementation should therefore support:
- reuse vs destruction distinction
- origin and destination tracking
- timestamps and actor tracking
- lineage visibility
- destruction / recycling states

## Current Repository Context
At planning time, the repository was on `master` and the worktree was dirty with unrelated in-flight changes, including:
- mobile UI work
- test type drag reordering work
- attribute-definition drag ordering work
- scan page UI changes
- docs/progress updates

Do not revert unrelated changes. Assume the worktree may be dirty when implementation starts.

Known environment constraints at planning time:
- sqlite test DB corruption may block focused PHPUnit runs in Docker
- see `PROGRESS.md` / session addenda for current test-environment notes

## Current Module to Replace
The existing `components` implementation is quantity-based pooled stock, not unique instance tracking.

Key files to inspect before replacement:
- `app/Models/Component.php`
- `app/Http/Controllers/Components/ComponentsController.php`
- `app/Http/Controllers/Components/ComponentCheckoutController.php`
- `app/Http/Controllers/Components/ComponentCheckinController.php`
- `app/Http/Controllers/Api/ComponentsController.php`
- `app/Http/Transformers/ComponentsTransformer.php`
- `resources/views/components/*`
- `resources/views/hardware/view.blade.php`
- `routes/web/components.php`
- `routes/api.php`
- `database/migrations/2016_03_08_225351_create_components_table.php`

Important existing reusable patterns:
- menu/nav/breadcrumbs under `components.*`
- file uploads / history patterns on object detail pages
- QR label generation patterns in `app/Services/QrLabelService.php`
- account/company infrastructure in `app/Models/Company.php` and `app/Http/Controllers/ViewAssetsController.php`

## Goals

### Primary goals
- Replace old pooled components with traceable physical component instances.
- Support full component lineage:
  - source asset or intake source
  - current state/location
  - installed target asset
  - history of movements
- Support model/PIM-defined expected components on assets.
- Deliver mobile-first operator flows that feel natural during refurb work.
- Add a persistent tray flow for removal/install work.
- Make asset and component pages useful for both operations and audits.
- Create a foundation for customer-facing work orders/tasks tied to assets and component movements.

### Secondary goals
- Reuse useful visuals and navigation from the current components area.
- Avoid introducing a warehouse-complexity burden in MVP.
- Keep the new model extensible for QR labels, work orders, destruction tracking, and visibility controls.

## Non-Goals
- Preserving legacy component data semantics.
- Preserving legacy component checkin/checkout routes or APIs.
- Implementing a full warehouse/bin management system in phase 1.
- Implementing customer-created requests/comments in phase 1.
- Finalizing legal wording/compliance claims in code.
- Automatically mutating asset status because a component was removed.

## Terminology

### Component Definition
Catalog-level description of a component type.
Examples:
- `16GB DDR4 SODIMM`
- `256GB NVMe SSD`
- `iPhone 12 screen`

### Component Instance
One physical component item with its own identity.

### Expected Component
A model/PIM-derived expected part on a model number / device template. Not a real physical component by itself.

### Tray
A persisted temporary working state for component instances that have been removed and are currently held by an operator while in transfer.

### Component Event
Immutable history record describing what happened to a component instance.

### Work Order
Customer-facing umbrella record for a single job or batch of jobs.

### Work Order Task
A concrete unit of requested work under a work order.

## Domain Model

### New core tables

#### `component_definitions`
Purpose:
- reusable catalog templates for known part types

Recommended fields:
- `id`
- `uuid`
- `name`
- `category_id` or dedicated `component_family`
- `manufacturer_id` nullable
- `model_number` nullable
- `part_code` nullable
- `spec_summary` nullable
- `metadata_json` nullable
- `serial_tracking_mode` enum (`required`, `optional`, `none`)
- `is_active`
- `created_by`
- `updated_by`
- timestamps
- soft deletes

Notes:
- This is where predefined RAM/storage/screen variants live.
- Do not overload model attributes/spec definitions for physical tracking.
- If component-specific custom fields are needed later, add them to definitions or instances in a future phase. For MVP, `metadata_json` is acceptable.

#### `component_instances`
Purpose:
- one physical tracked component per row

Recommended fields:
- `id`
- `uuid`
- `component_tag` unique human-readable identifier
- `qr_uid` unique identifier for QR encoding
- `component_definition_id` nullable
- `display_name` snapshot/fallback name
- `serial` nullable
- `status` enum
- `condition_code` enum (`unknown`, `good`, `fair`, `poor`, `broken`)
- `source_type` enum (`extracted`, `purchased`, `external_intake`, `manual`)
- `source_asset_id` nullable
- `current_asset_id` nullable
- `storage_location_id` nullable
- `held_by_user_id` nullable
- `transfer_started_at` nullable
- `needs_verification_at` nullable
- `last_verified_at` nullable
- `installed_as` nullable
- `supplier_id` nullable
- `purchase_cost` nullable
- `received_at` nullable
- `destroyed_at` nullable
- `metadata_json` nullable
- `notes` nullable
- `created_by`
- `updated_by`
- timestamps
- soft deletes

Rules:
- `component_tag` is required on every component instance.
- `qr_uid` should be created even before printed QR labels exist.
- `status` and location fields must remain internally consistent.

#### `component_events`
Purpose:
- immutable audit/event trail for every state change and movement

Recommended fields:
- `id`
- `component_instance_id`
- `event_type`
- `performed_by`
- `from_status` nullable
- `to_status` nullable
- `from_asset_id` nullable
- `to_asset_id` nullable
- `from_storage_location_id` nullable
- `to_storage_location_id` nullable
- `held_by_user_id` nullable
- `related_work_order_id` nullable
- `related_work_order_task_id` nullable
- `note` nullable
- `payload_json` nullable
- `created_at`

Recommended event types:
- `created`
- `received`
- `extracted`
- `removed_to_tray`
- `installed`
- `moved_to_stock`
- `moved_to_quarantine`
- `flagged_needs_verification`
- `verification_confirmed`
- `marked_defective`
- `marked_destruction_pending`
- `destroyed_recycled`
- `returned_to_supplier`
- `sold`

#### `component_storage_locations`
Purpose:
- simple storage/quarantine/destruction areas without full warehouse complexity

Recommended fields:
- `id`
- `name`
- `code` nullable
- `site_location_id` nullable reference to existing `locations`
- `type` enum (`stock`, `quarantine`, `destruction`, `general`)
- `is_active`
- timestamps

Notes:
- Do not model full bins/shelves in MVP unless needed.
- `code` can later represent tray/cabinet/shelf labels.

#### `model_number_component_templates`
Purpose:
- expected/default components per model number / PIM model

Recommended fields:
- `id`
- `model_number_id`
- `component_definition_id` nullable
- `expected_name`
- `slot_name` nullable
- `expected_qty`
- `is_required`
- `sort_order`
- `metadata_json` nullable
- `notes` nullable
- timestamps

Rules:
- These are templates only.
- They do not imply physical existence.
- They should drive the expected/default section on asset component tabs.

### Customer work-order tables

#### `work_orders`
Purpose:
- customer-facing umbrella record for a single device or batch

Recommended fields:
- `id`
- `uuid`
- `work_order_number`
- `company_id`
- `primary_contact_user_id` nullable
- `title`
- `description` nullable
- `status`
- `priority` nullable
- `visibility_profile` enum (`full`, `basic`, `custom`)
- `portal_visibility_json` nullable
- `intake_date` nullable
- `due_date` nullable
- `created_by`
- `updated_by`
- timestamps
- soft deletes

#### `work_order_assets`
Purpose:
- assets/devices in scope for the work order

Recommended fields:
- `id`
- `work_order_id`
- `asset_id` nullable
- `customer_label` nullable
- `asset_tag_snapshot` nullable
- `serial_snapshot` nullable
- `qr_reference` nullable
- `status`
- `sort_order`
- timestamps

#### `work_order_tasks`
Purpose:
- task-level units of work under the work order

Recommended fields:
- `id`
- `work_order_id`
- `work_order_asset_id` nullable
- `task_type`
- `title`
- `description` nullable
- `status`
- `customer_visible`
- `customer_status_label` nullable
- `assigned_to` nullable
- `started_at` nullable
- `completed_at` nullable
- `sort_order`
- `notes_internal` nullable
- `notes_customer` nullable
- timestamps

### Recommended indexes
- `component_instances.component_tag`
- `component_instances.qr_uid`
- `component_instances.status`
- `component_instances.current_asset_id`
- `component_instances.source_asset_id`
- `component_instances.held_by_user_id`
- `component_instances.storage_location_id`
- `component_events.component_instance_id, created_at`
- `model_number_component_templates.model_number_id, sort_order`
- `work_orders.company_id, status`
- `work_order_assets.work_order_id`
- `work_order_tasks.work_order_id, work_order_asset_id, status`

## State Model

### Component instance statuses
Use these exact starter states unless the user changes them:
- `installed`
- `in_stock`
- `in_transfer`
- `needs_verification`
- `quarantine`
- `defective`
- `destruction_pending`
- `destroyed_recycled`
- `sold_returned`

Rules:
- `installed` requires `current_asset_id`.
- `in_stock` should point to a `storage_location_id`.
- `in_transfer` requires `held_by_user_id` and `transfer_started_at`.
- `needs_verification` is unresolved physical uncertainty and must stay visible in filters and banners.

### Tray aging behavior
MVP defaults:
- first reminder threshold: `2 hours`
- `needs_verification` threshold: `24 hours`

Implementation notes:
- thresholds should live in config and be easy to change
- scheduled job/command runs periodically and:
  - identifies stale `in_transfer` items
  - reminds the holding user
  - escalates to `needs_verification` after the long threshold

Do not auto-relocate items when aging triggers.

## UX / Screen Plan

### Global `Components` page
Repurpose the current global components page into the component instance registry.

Primary uses:
- find loose components
- find installed components
- inspect stale transfer items
- inspect lineage and traceability

Default filters:
- show `in_stock`, `in_transfer`, and `needs_verification` first

Recommended columns:
- component tag
- short name
- category/family
- serial
- status
- current location or asset
- source asset / source type
- held by
- updated at

Recommended filters:
- status
- family/category
- location
- source type
- source asset
- current asset
- held by
- needs verification
- search by tag / serial / name / asset tag

### Component detail page
Repurpose the current component detail page into a lineage/action page for one component instance.

Sections:
- identity
- current state
- source
- current location/asset
- tray/holder info if in transfer
- linked work order/task if present
- full event history
- attachments

Quick actions:
- install into asset
- move to stock
- move to quarantine
- resolve verification
- mark defective
- mark destruction pending

### Asset `Components` tab
Repurpose the asset components tab into three clear sections:

#### `Installed Components`
- real tracked component instances currently linked to the asset
- each row should offer actions like:
  - remove to tray
  - open
  - view lineage

#### `Removed / History`
- components previously linked to the asset
- show current status/location with link
- never hide lineage after removal

#### `Expected Components`
- hidden by default
- clearly labeled as model/PIM-derived expected components
- not real physical instances
- use a collapsed section or explicit `Show Expected Components` switch

Expected component rows should support:
- install existing component
- install from tray
- search component
- create/register component instance

### Tray UX
Tray is a view over `component_instances` where:
- `status = in_transfer`
- `held_by_user_id = current user`

Desktop:
- right-side panel or persistent secondary drawer

Mobile:
- sticky bottom pill/bar with count and warning states

Tray item content:
- component tag
- short name
- source asset
- time in tray
- warning badge if aging

Tray actions:
- install into current asset
- move to stock
- move to quarantine
- open component

### Mobile operator flow
This is a priority flow and should feel natural.

#### Remove flow
1. scan asset QR
2. open asset page
3. open `Components`
4. pick installed component
5. tap `Remove to Tray`
6. confirm source slot, condition, and optional note
7. component enters `in_transfer`
8. tray badge increments

#### Install flow
1. scan/search target asset
2. open asset page
3. open tray from sticky control
4. choose component
5. tap `Install Here`
6. confirm slot/role, optional note, optional work-order link
7. component becomes `installed`

#### Loose stock flow
From tray, operator can:
- move to stock
- move to quarantine

From global `Components` page, operator can:
- search loose components
- scan/open a component
- install into asset by search or QR

## Expected Components / PIM Integration
Do not fake expected components as real instances.

Use `model_number_component_templates` as a separate reference layer.

Visual guidance:
- expected components must look different from real installed component instances
- real installed component instances should show tags/status/lineage
- expected components should show only template info and guidance actions

MVP guidance:
- expected component templates may be managed from model-number/spec screens in a later sub-phase
- if time is constrained, first render them read-only from seed/manual data shape, then add full management UI after the instance flow is stable

## Customer Work Orders / Portal Plan
This is the second large initiative and should be built on top of the component foundation, not merged into it.

### Portal principles
- read-only for customers in MVP
- build on existing `users` + `companies` rather than inventing a second auth system
- customers should be able to view work order progress for one device or a batch
- visibility must be configurable at work-order/task level

### MVP portal behavior
- company-scoped users can view work orders tied to their company
- work orders show:
  - high-level status
  - in-scope devices/assets
  - visible tasks
  - visible notes
  - visible replaced components if enabled

### Later portal expansion
- per-user overrides
- account groups
- portal comments/approvals
- AI-assisted inbound task triage

## Repository Integration Plan

### Files/routes likely to be replaced or heavily reworked
- `app/Models/Component.php`
- `app/Http/Controllers/Components/*`
- `app/Http/Controllers/Api/ComponentsController.php`
- `app/Http/Transformers/ComponentsTransformer.php`
- `resources/views/components/*`
- `resources/views/hardware/view.blade.php`
- `routes/web/components.php`
- component sections in `routes/api.php`

### Files/routes likely to be extended
- `app/Services/QrLabelService.php`
- `resources/views/layouts/default.blade.php`
- `app/Providers/BreadcrumbsServiceProvider.php`
- `app/Providers/AuthServiceProvider.php`
- `resources/views/dashboard.blade.php`
- `resources/views/scan/index.blade.php` or related scan JS if component QR support is added

### Recommendation on naming
User-facing:
- keep `Components`

Internal:
- use explicit names:
  - `ComponentDefinition`
  - `ComponentInstance`
  - `ComponentEvent`

This avoids semantic confusion during implementation.

## Permissions
Add or redefine component permissions around the new semantics:
- `components.view`
- `components.create`
- `components.update`
- `components.delete`
- `components.extract`
- `components.install`
- `components.move`
- `components.verify`
- `components.manage_definitions`
- `components.manage_storage_locations`

Portal/work-order permissions:
- `workorders.view`
- `workorders.create`
- `workorders.update`
- `workorders.manage_visibility`
- `portal.view`

## Phased Implementation Plan

### Phase 0: Replace Planning Surface
Goal:
- establish the new internal naming and retire the old behavioral assumptions

Tasks:
- create this plan file
- align agents on:
  - no legacy checkin/checkout preservation
  - `Components` stays as the user-facing module name
  - tray/expected components/work-order sequencing

### Phase 1: New Component Foundation
Goal:
- land the new component data model and basic domain services

Tasks:
- create migrations for:
  - `component_definitions`
  - `component_instances`
  - `component_events`
  - `component_storage_locations`
  - `model_number_component_templates`
- create Eloquent models and policies
- add service layer for:
  - create component instance
  - extract component
  - remove to tray
  - install into asset
  - move to stock
  - move to quarantine
  - flag needs verification
- create audit/event writer
- add tests for domain rules and state transitions

Exit criteria:
- component instances can be created and moved through basic states without any UI

### Phase 2: Replace Global `Components` Registry
Goal:
- make `/components` a useful component instance registry

Tasks:
- replace old components index API and UI
- replace presenter/transformer logic to show instance fields
- implement filters for status, source, asset, holder, location
- add component detail page with history timeline
- wire attachments/history if needed

Exit criteria:
- `/components` is no longer a quantity-pool page
- operators can find loose and installed components

### Phase 3: Asset Components Tab and Expected Components
Goal:
- make asset detail the primary operational hub for component work

Tasks:
- replace current asset `Components` tab behavior
- show:
  - installed tracked components
  - removed/history
  - expected components collapsed by default
- add row actions:
  - remove to tray
  - open
  - view lineage
  - install existing component
  - install from tray

Exit criteria:
- asset pages support real component tracking and expected/default guidance

### Phase 4: Tray Flow and Mobile-first Navigation
Goal:
- deliver the natural operator flow

Tasks:
- implement tray UI on mobile and desktop
- persist tray state by component instance status/holder
- add quick actions from asset page and tray
- ensure back-and-forth navigation is smooth
- optionally add QR-driven shortcuts

Exit criteria:
- operator can remove a component from one asset and install it into another without admin-style stock screens

### Phase 5: Tray Aging and Verification
Goal:
- prevent floating unresolved components

Tasks:
- add config for thresholds
- create scheduled command/service for stale transfers
- notify holding user
- transition stale items to `needs_verification`
- add visible warnings in tray and global registry

Exit criteria:
- stale tray items cannot silently disappear from operational awareness

### Phase 6: Work Order Foundation
Goal:
- build the customer work-order backbone

Tasks:
- create:
  - `work_orders`
  - `work_order_assets`
  - `work_order_tasks`
- add admin/internal CRUD and list/detail screens
- link tasks to assets and later component events
- allow batch and single-asset work orders

Exit criteria:
- internal users can create and track customer work orders

### Phase 7: Customer Portal
Goal:
- expose read-only progress to customers

Tasks:
- reuse existing auth/account/company patterns
- add portal views for:
  - work order list
  - work order detail
  - device/task progress
- implement visibility profile support
- default to broad visibility in MVP while preserving configuration hooks

Exit criteria:
- customer users can log in and view their work orders and device progress

## Suggested Parallelization for Multiple Agents
If multiple agents are used, use disjoint ownership. Do not split tightly coupled migration/domain work across agents.

### Agent A: Domain and Migrations
Own:
- new tables
- models
- policies
- state/event services
- scheduled tray-aging logic

Files likely owned:
- `app/Models/*`
- `app/Policies/*`
- `app/Services/*`
- `database/migrations/*`
- `tests/Feature/*domain*`
- `tests/Unit/*`

### Agent B: Global Components UI
Own:
- global components index/detail screens
- API transformers/controllers for the new registry
- breadcrumbs/menu cleanup

Files likely owned:
- `resources/views/components/*`
- `app/Http/Controllers/Components/*`
- `app/Http/Controllers/Api/*`
- `app/Http/Transformers/*`
- `app/Presenters/*`

### Agent C: Asset Components Tab and Tray UX
Own:
- asset page integration
- tray panel / sticky mobile controls
- expected/default components rendering
- scan-to-asset integration if included

Files likely owned:
- `resources/views/hardware/view.blade.php`
- tray partials
- related JS/CSS
- focused asset UI tests

### Agent D: Work Orders / Portal
Own:
- work-order schema/UI
- portal views
- customer visibility logic

Files likely owned:
- new work-order models/controllers/views
- `app/Http/Controllers/ViewAssetsController.php` extensions or parallel portal controllers
- company/user visibility code

## Testing Strategy

### Domain tests
- component instance creation
- extraction from asset
- remove to tray
- install into target asset
- move to stock
- move to quarantine
- tray aging transition to `needs_verification`
- lineage rendering inputs

### Feature tests
- `/components` registry filters and search
- component detail timeline
- asset `Components` tab sections
- expected component visibility toggle
- tray install flow
- work-order creation and detail pages
- portal visibility and read-only access

### UI checks
- mobile tray control
- asset QR to components flow
- stale tray badge/alert
- expected components collapsed by default

### Practical verification notes
- use focused tests where possible
- if sqlite corruption blocks runtime tests, still run `php -l` and focused view/controller lint checks
- record blocked verification explicitly in `PROGRESS.md`

## Migration / Replacement Strategy
- Do not attempt to preserve old component checkin/checkout behavior.
- Do not attempt to create a hybrid pooled/instance model.
- Replace old `components` routes/views progressively but decisively.
- It is acceptable to delete or repurpose old component-specific tests when the old semantics are intentionally removed.
- If old component data remains in the DB temporarily, do not design around it unless the user later asks for migration.

## Risks and Pitfalls
- Confusing expected components with real component instances.
- Implementing tray as browser-only state.
- Allowing stale tray items to linger with no escalation.
- Reusing old quantity semantics anywhere in the new core flow.
- Overcomplicating storage-location modeling in MVP.
- Coupling customer portal logic too tightly to internal component events.
- Breaking unrelated dirty worktree changes while replacing the module.

## Open Questions
These do not block phase-1 foundation work, but they should be revisited during implementation:
- final tray reminder thresholds
- exact drive-wipe / destruction metadata for storage devices
- exact portal visibility profiles beyond the permissive MVP default
- whether component-specific fieldsets are needed beyond `metadata_json`
- final work-order numbering format

## First Files an Implementing Agent Should Read
- `AGENTS.md`
- `PROGRESS.md`
- `docs/fork-notes.md`
- `docs/agents/agents-addendum-2026-04-16-session-init.md`
- `app/Models/Component.php`
- `app/Http/Controllers/Components/ComponentsController.php`
- `app/Http/Controllers/Api/ComponentsController.php`
- `resources/views/components/index.blade.php`
- `resources/views/components/view.blade.php`
- `resources/views/hardware/view.blade.php`
- `app/Services/QrLabelService.php`
- `app/Http/Controllers/ViewAssetsController.php`
- `app/Models/Company.php`

## Implementation Order Recommendation
Do this in order:
1. new component schema and domain services
2. global components registry replacement
3. asset components tab replacement
4. tray/mobile flow
5. tray aging/verification
6. work orders
7. customer portal

Do not start with portal work before the component foundation exists.

