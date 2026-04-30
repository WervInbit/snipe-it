# Plan: Component Hierarchy, Subcomponents, Attributes, and Traceability

## Audience
This document is a handoff-ready implementation plan for agents and contributors who do not have the preceding chat context.

Read these first:
- [AGENTS.md](C:\dev\snipe-it-fork\AGENTS.md)
- [PROGRESS.md](C:\dev\snipe-it-fork\PROGRESS.md)
- [docs/fork-notes.md](C:\dev\snipe-it-fork\docs\fork-notes.md)

Then read this document end-to-end before changing code.

## Planning Date
2026-04-30

## Purpose
The current fork has a first-generation flat component system:
- assets can have tracked components
- model numbers can declare expected components
- component definitions can contribute scalar attributes to effective specs
- assets can derive current specs from expected baseline plus tracked deviations

That system is already useful, but it is too flat for integrated repairable parts such as:
- USB ports
- HDMI ports
- RJ45 jacks
- SD readers
- fingerprint readers
- webcams
- touchpads when they are part of an assembly

The problem is that these parts are often:
- part of a larger assembly such as a motherboard, I/O board, or display assembly
- removable or replaceable
- relevant to traceability
- sometimes damaged but still physically present
- sometimes reused in another device

The existing flat model cannot express "this port belongs to that motherboard" cleanly. This plan adds one additional structural level and no more:
- asset
- component
- subcomponent

That is the hard maximum depth.

## Current Repository State

### Existing Relevant Tables and Code
- [database/migrations/2026_04_17_120000_create_component_traceability_tables.php](C:\dev\snipe-it-fork\database\migrations\2026_04_17_120000_create_component_traceability_tables.php)
- [database/migrations/2026_04_21_140000_create_component_definition_attributes_table.php](C:\dev\snipe-it-fork\database\migrations\2026_04_21_140000_create_component_definition_attributes_table.php)
- [app/Models/ComponentDefinition.php](C:\dev\snipe-it-fork\app\Models\ComponentDefinition.php)
- [app/Models/ComponentInstance.php](C:\dev\snipe-it-fork\app\Models\ComponentInstance.php)
- [app/Models/ComponentEvent.php](C:\dev\snipe-it-fork\app\Models\ComponentEvent.php)
- [app/Services/Components/AssetComponentRosterService.php](C:\dev\snipe-it-fork\app\Services\Components\AssetComponentRosterService.php)
- [app/Services/ModelAttributes/ComponentAttributeAggregator.php](C:\dev\snipe-it-fork\app\Services\ModelAttributes\ComponentAttributeAggregator.php)
- [app/Services/ModelAttributes/EffectiveAttributeResolver.php](C:\dev\snipe-it-fork\app\Services\ModelAttributes\EffectiveAttributeResolver.php)

### Current Limitation
The current data model assumes:
- a component instance can attach to an asset
- expected baseline exists at asset/model-number level
- calculated attributes are built from a flat asset-level roster

There is no parent-child component relation today. There is no concept of:
- a motherboard having child ports
- a display assembly having a child webcam
- a topcase having a child fingerprint reader

That is the main limitation this plan addresses.

## Outcome Sought
Support a hierarchy where:
- model numbers can still have direct attributes
- model numbers can still have expected direct components
- components can have direct structured attributes
- components can define expected subcomponents
- subcomponents can have direct structured attributes
- both components and subcomponents are fully traceable movable tracked instances
- custom items are allowed at both levels
- current effective specs come from what is currently attached
- damaged but still attached parts do not reduce specs
- damaged or needs-attention parts remain visibly flagged in summaries

## Non-Goals
This plan does not try to:
- model every screw, adhesive strip, or other tiny consumable by default
- support arbitrary recursive nesting beyond subcomponents
- force every default expected part to be a tracked physical instance from day one
- block overlapping parent/child contributions completely
- reduce current specs solely because a currently attached part is damaged

## Locked Decisions
These decisions were already made and should be treated as requirements.

### Structure
- Hard depth cap:
  - asset
  - component
  - subcomponent
- Components attach to assets.
- Subcomponents attach to components.
- No deeper nesting is allowed.

### Expected Baseline
- Expected components on model numbers are assumed present until explicit change.
- Expected subcomponents on component definitions are assumed present until explicit change.
- First explicit change materializes an expected item into a real tracked instance.

### Materialization Triggers
Explicit change includes:
- note creation or update
- status or condition change
- file upload
- removal or detach
- transfer
- repair action
- replacement action

### Custom Items
- Custom components are allowed.
- Custom subcomponents are allowed.
- Custom subcomponents can carry structured attributes.

### Movement
- Moving a parent component moves all currently attached children.
- Detached or removed children do not move with the parent.
- Newly attached and custom attached children do move with the parent.

### Attributes and Specs
- Model numbers can have direct attributes.
- Components can have attributes.
- Subcomponents can have attributes.
- Damaged but still attached parts do not reduce specs.
- Double-counting is allowed, but must warn.
- Lowest attached level wins for effective attribute contribution.

### Status and Condition
- "Needs attention" is the attention wording to use.
- "Unknown" and "needs verification" are to be treated as the same concept and collapsed into "needs attention".
- "Damaged" and "defective" are to be treated as the same concept and collapsed into "damaged".

### History
- A child must not keep inheriting future parent history after it is materialized or detached.
- Instead, the child gets a closed ancestry snapshot up to the point of materialization or detachment.

## Core Design Principle
Do not build a separate second-class subcomponent system.

Use the same underlying entity type for both components and subcomponents. Distinguish them by attachment context:
- attached directly to asset = component
- attached to a parent component = subcomponent

Reasons:
- avoids duplicated lifecycle logic
- avoids duplicated uploads/notes/history code
- avoids duplicated permission models
- keeps transfer, destruction, and reuse consistent
- lets a custom item exist at either level without inventing a second object type

## Terminology

### Component Definition
A catalog definition that can be expected on a model number and instantiated onto an asset.

### Component Instance
A tracked instance attached directly to an asset or otherwise existing in tray, stock, or destroyed states.

### Subcomponent Definition
Not a separate top-level type. It is still a component definition, but one used as an expected or actual child beneath a parent component.

### Subcomponent Instance
Not a separate top-level type. It is still a component instance, but one attached beneath a parent component.

### Expected Component
A direct expected child of a model number / asset.

### Expected Subcomponent
A direct expected child of a component definition / component instance.

### Materialized
An item that was previously assumed via expected baseline and now exists as a real tracked instance.

## Recommended Domain Modeling Rule
Only model parts at this level if they are meaningfully serviceable, transferable, or traceable.

Good candidates:
- motherboard
- I/O board
- display assembly
- panel
- webcam module
- fingerprint module
- SD reader
- USB port
- HDMI port
- RJ45 jack
- RAM
- storage
- battery

Usually not worth modeling by default:
- screws
- individual keycaps
- adhesives
- solder
- tiny passive parts

These may still be allowed as custom items in exceptional cases.

## Data Model Changes

### 1. Extend Component Definitions
Keep [component_definitions](C:\dev\snipe-it-fork\database\migrations\2026_04_17_120000_create_component_traceability_tables.php) as the core catalog table.

Add a placement field:
- `placement_mode`

Suggested allowed values:
- `asset_only`
- `subcomponent_only`
- `either`

Reason:
- some parts should only exist as top-level components
- some only make sense under a parent
- some can validly be both

Examples:
- motherboard = `asset_only`
- USB port = `subcomponent_only`
- I/O board = `either`
- webcam module = `either`

This should be validated in the service/UI layer, not solely trusted to DB constraints.

### 2. Extend Component Instances
Keep [component_instances](C:\dev\snipe-it-fork\database\migrations\2026_04_17_120000_create_component_traceability_tables.php) as the single tracked-instance table.

Add fields:
- `parent_component_instance_id` nullable FK to `component_instances`
- `root_asset_id` nullable FK to `assets`
- `is_materialized_expected` boolean default false
- `materialized_reason` nullable string
- `ancestry_parent_component_instance_id` nullable FK
- `ancestry_attached_through_at` nullable timestamp
- `ancestry_attached_through_event_id` nullable FK to `component_events`

Interpretation:
- `parent_component_instance_id = null` and attached to asset -> top-level component
- `parent_component_instance_id != null` -> subcomponent
- detached items can still keep ancestry snapshot fields even when no longer attached

`root_asset_id` is important for fast summary queries across attached trees.

### 3. Add Definition-Level Expected Subcomponent Templates
Keep `model_number_component_templates` for top-level expected components.

Add a new table:
- `component_definition_subcomponent_templates`

Suggested fields:
- `id`
- `parent_component_definition_id`
- `child_component_definition_id` nullable
- `expected_name`
- `expected_qty`
- `is_required`
- `sort_order`
- `metadata_json`
- `notes`
- timestamps

Purpose:
- describe the expected child structure of a component definition

Examples:
- motherboard expects:
  - USB-A 3.1 port x2
  - HDMI 2.0 port x1
  - RJ45 jack x1
- display assembly expects:
  - panel x1
  - webcam x1

This should mirror the current model-number expected component pattern rather than inventing a fully generic template abstraction in the first tranche.

### 4. Add Instance-Level Expected Subcomponent State
Keep `asset_expected_component_states` for top-level expected baseline depletion.

Add:
- `component_expected_subcomponent_states`

Suggested fields:
- `id`
- `component_instance_id`
- `component_definition_subcomponent_template_id`
- `removed_qty`
- timestamps
- unique on `component_instance_id + component_definition_subcomponent_template_id`

Purpose:
- expected child parts are assumed until changed
- if one is removed or materialized away, the parent instance must remember that expected baseline is no longer intact

### 5. Add Instance-Level Structured Attributes
Keep `component_definition_attributes` as definition-level default structured attributes.

Add:
- `component_instance_attributes`

Suggested fields:
- `id`
- `component_instance_id`
- `attribute_definition_id`
- `value`
- `raw_value`
- `attribute_option_id`
- `sort_order`
- timestamps

This is required because definition attributes alone are not enough.

Reasons:
- custom items need structured attributes even without a definition
- instance-specific deviations must be representable
- repair state may change a specific instance without mutating the catalog definition

### 6. Keep Uploads, Notes, and Events on the Same Instance Type
Do not create a second uploads/events system for subcomponents.

Reuse the existing `ComponentInstance` capability:
- notes
- uploads
- events
- lifecycle actions
- detail pages

Subcomponents should be first-class tracked instances with the same operational rigor as top-level components.

## Lifecycle and Condition Model
The current fork mixes placement/lifecycle and operational health in one status system. This plan should separate them.

### 1. Lifecycle / Placement
Use lifecycle values that answer "where is it / what state is it in operationally":
- `attached`
- `in_tray`
- `in_stock`
- `destruction_pending`
- `destroyed`
- optionally `sold_returned` later if still required

These determine whether the instance contributes to current effective specs.

### 2. Condition / Attention
Use a second field that answers "what is its current physical/verification state":
- `good`
- `needs_attention`
- `damaged`

Meaning:
- `needs_attention` replaces both `unknown` and `needs verification`
- `damaged` replaces both `damaged` and `defective`

Important nuance:
- a part can be `attached + damaged`
- a part can be `attached + needs_attention`
- a part can be `in_stock + good`

This is why lifecycle and condition must not be collapsed into one field.

## Movement Rules

### Parent Transfer
If a parent component moves to another asset, all currently attached children move with it.

This includes:
- materialized expected children
- custom attached children
- replacement children currently attached

This excludes:
- detached children
- children already in tray
- children already in stock
- destroyed children

### Detached Child Behavior
A detached child remains detached and keeps its own future history. It does not reattach implicitly when the old parent moves.

### Implementation Requirement
Parent transfer must be a subtree-aware transactional operation:
- update parent
- update all currently attached descendants
- emit correct event rows for parent and descendants
- update `root_asset_id` consistently

## Expected Baseline Rules

### Top Level
Keep the current model-number expected component concept:
- model number expects component
- asset assumes it exists until explicit change
- explicit change materializes it into a tracked instance

### Child Level
Mirror the same behavior one level down:
- component definition expects subcomponent
- component instance assumes it exists until explicit change
- explicit change materializes it into a tracked child instance

### Materialization Triggers
Materialize on first explicit change, including:
- note
- upload
- condition change
- detach
- move
- replacement
- repair action

If a note is added to an expected child that remains attached:
- it still materializes
- it remains attached
- its contribution to current effective specs stays the same
- it now has an identity, notes, and history

## Attribute and Contribution Rules

### Allowed Attribute Locations
Attributes are allowed on:
- model numbers
- component definitions
- component instances
- subcomponent definitions
- subcomponent instances
- custom components
- custom subcomponents

### Definition vs Instance Precedence
At the same level:
- instance attribute wins over definition attribute

Reason:
- definition says what this part type normally is
- instance says what this real tracked part currently is

### Level Precedence
Lowest attached level wins.

Examples:
- a child port subcomponent overrides a parent board-level port count
- a child webcam module overrides a display assembly-level webcam capability
- a top-level component overrides model-number direct fallback attributes

### Current Attachment Rule
Only currently attached items contribute to current effective specs.

Detached, in-stock, in-tray, and destroyed items contribute to history only, not current totals.

### No Spec Reduction for Damaged-but-Present Parts
If a part is damaged but still attached:
- it still contributes its normal spec/config presence
- the issue is surfaced by condition/status indicators on summary pages

This is intentional. Specs should describe current physical/configuration presence, not functional correctness.

### Double Count Warning
Double counting must be allowed but warned.

Examples:
- parent motherboard contributes `usb_a_count = 2`
- child USB-A port instances also imply two ports

Behavior:
- do not block save
- do not block attachment
- warn visibly
- effective rollup should use the lowest attached level

### Numeric, Bool, and Text Rollup
General scalar rollup can keep current patterns:
- `int` / `decimal`: sum across active winning-level contributors
- `bool`: any true at the active winning level
- `enum` / `text`: distinct merge/join unless a dedicated renderer exists

However, do not try to flatten all connectivity into generic attribute display. Ports especially deserve dedicated summary rendering.

## Port Modeling Guidance
Ports are a major reason this hierarchy is needed. Do not force them back into one generic text or scalar attribute.

Model ports as subcomponents where appropriate:
- USB-A port
- USB-C port
- HDMI port
- VGA port
- RJ45 jack
- SD card reader
- audio jack

Port definitions or custom port instances can carry structured attributes such as:
- connector family
- connector form
- standard/version
- charging capability
- display capability
- data capability

The hardware view can later render a dedicated connectivity summary from currently attached port subcomponents.

## History Model

### No Live Inheritance
A child must not continue to inherit parent history after materialization or detachment.

Reason:
- if a child leaves the parent, future parent moves are not relevant to the child

### Closed Ancestry Snapshot
When an expected child is materialized or detached:
- store its parent linkage up to that point
- create a synthetic event marking the snapshot

Suggested snapshot data:
- `ancestry_parent_component_instance_id`
- `ancestry_attached_through_at`
- `ancestry_attached_through_event_id`

The child detail page should be able to show:
- "Part of component X until <timestamp>"

After that:
- the child has only its own subsequent history
- future parent events are not copied down

### Parent With Existing History
If a child is removed from a parent that already has history:
- capture the parent linkage up to that point
- do not clone every old event row
- do not create ongoing inheritance

This avoids false or bloated audit trails.

## Summary and Warning Surfaces
Because damaged-but-present parts still contribute, summary pages must surface issues separately from specs.

Examples:
- hardware summary badge:
  - `1 attached part needs attention`
  - `2 attached subcomponents damaged`
- component detail summary:
  - `Attached child issues present`
- roster rows:
  - issue badge next to name

Warnings should also exist for:
- parent/child overlapping contributions
- duplicate active contributors for the same scalar
- expected baseline that has been reduced

## UI Changes

### Model Number Spec Page
Keep:
- direct model-number attributes
- expected top-level components

Add:
- ability to drill into each expected component definition's expected subcomponent preview

Do not try to fully inline-edit every nested child structure on one page. Use previews and links so the screen remains operable.

### Component Definition Edit Page
Expand the definition editor so it supports:
- definition-level structured attributes
- expected subcomponent templates
- overlap warnings when parent and child definitions contribute the same effective attribute

This page becomes the place where assemblies are structurally described.

### Asset Components Tab
Keep the asset tab list-first, but render hierarchy:
- direct components as primary rows
- child rows beneath their parent or behind a child expander
- assumed expected child rows visible beneath the parent
- removed child rows visible and distinguishable
- issue badges visible in-line

### Component Detail Page
This page becomes much more important.

It must show:
- current parent/asset context
- lifecycle
- condition
- notes
- uploads
- direct attributes
- expected child rows
- materialized attached child rows
- removed/detached child rows
- overlap warnings
- ancestry snapshot where relevant
- history

### Subcomponent Detail Page
Prefer using the same component detail page with context-sensitive rendering rather than building a separate second detail system.

## Migration Strategy
Do not replace the current flat system in one destructive pass. Build it in phases.

### Phase 1: Schema Foundation
Add:
- definition `placement_mode`
- instance parent and ancestry fields
- `root_asset_id`
- `component_definition_subcomponent_templates`
- `component_expected_subcomponent_states`
- `component_instance_attributes`

Existing flat asset-level component behavior should still continue to work after this phase.

### Phase 2: Service Layer Refactor
Refactor lifecycle services so they understand:
- attach to asset
- attach to parent component
- detach from parent
- materialize expected subcomponent
- subtree move
- ancestry snapshot creation

Do this before large UI changes.

### Phase 3: Hierarchical Tree Builder
Replace the flat roster assumption with a hierarchy builder that can produce:
- direct components
- attached child rows
- assumed expected child rows
- removed child rows
- parent/child warnings
- issue summaries

### Phase 4: Attribute Resolver Refactor
Refactor effective spec resolution so it understands:
- currently attached hierarchy
- definition vs instance precedence
- lowest attached level wins
- damaged-but-attached still contributes
- overlap warnings

Do not simply bolt child iteration onto the current flat numeric resolver.

### Phase 5: UI Rollout
Update:
- model number spec page
- component definition editor
- asset components tab
- component detail page
- child/subcomponent detail rendering

### Phase 6: Conversion Tools
Add admin helpers for migrating current real-world data into the new model:
- build component definitions from recurring patterns
- attach component definitions to model numbers
- define expected subcomponents on component definitions
- detect overlapping contributions
- preview migration outcomes before applying

This matters because production-like data is already being tested locally in the clone database.

## Testing Requirements
The executing agent should not ship this without focused tests around the new rules.

Must-have coverage:
- top-level expected components still behave correctly
- expected subcomponents are assumed until explicit change
- note-only on expected child materializes it without changing contribution
- moving a parent moves only currently attached descendants
- detached child does not move with parent
- damaged-but-attached child still contributes to spec
- damaged-but-attached child is visibly flagged in summaries
- instance attributes override definition attributes
- child level overrides parent level
- overlap warnings appear but do not block save
- custom subcomponents can carry structured attributes
- detached child stops inheriting future parent history
- closed ancestry snapshot is recorded at detachment/materialization
- effective summary excludes detached/in-stock/in-tray/destroyed children
- parent/child connectivity structures do not double count when child level is active

## Risks and Pitfalls
- Treating the model as still flat will break the feature.
- Skipping instance-level attributes will break custom parts and real-world deviations.
- Live inherited history will create false traceability.
- Failing to update `root_asset_id` across subtree moves will corrupt summaries.
- Counting both parent and child contributions without precedence will misreport ports immediately.
- Hiding assumed expected child rows will confuse operators about current state.
- Requiring physical stickers for every tiny tracked child will be operationally excessive; internal strict tracking is enough even if physical labeling remains optional.

## Recommended First Conversion Targets
Do not convert everything at once. Start with the categories that most clearly justify the hierarchy:
- motherboard
- I/O board
- display assembly
- panel
- webcam
- fingerprint reader
- SD reader
- USB ports
- HDMI ports
- RJ45 jack
- RAM
- storage
- battery

Keep screws, keycaps, and other tiny parts as custom-only unless a strong operational use case emerges later.

## Final Validation
This architecture is intended to cover nearly all of the practical cases that motivated the redesign:
- laptops with replaceable modules
- integrated but repairable ports/readers
- monitors, docks, and hubs with varied connectivity
- custom one-off tracked parts
- traceability when parts are reused across devices

It deliberately adds only one more structural layer, because that is enough to solve the real hardware cases without turning the system into an unrestricted recursive BOM engine.
