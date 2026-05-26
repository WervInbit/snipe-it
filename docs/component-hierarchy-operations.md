# Component Hierarchy Operations

This fork supports one tracked hierarchy level below an asset component:

- asset
- component
- subcomponent

No deeper nesting is supported. Subcomponents are still `ComponentInstance` records; they are not a separate model or second component system.

## Admin Setup

Component definitions describe reusable component types. A definition can be used directly on an asset, below another component, or both through `placement_mode`:

- `asset_only`
- `subcomponent_only`
- `either`

On the component definition edit page, admins can configure expected subcomponents. Expected child rows can reference another component definition or use a freeform expected name. These rows are definition-level templates; they do not create tracked child component instances until an explicit tracking action happens.

When a parent definition and an expected child definition both contribute the same numeric calculated spec, the editor shows an overlap warning. This warning is advisory. It does not block saving.

## Model Number Setup

Model-number specification pages still manage top-level expected components. When a selected component definition has expected child templates, the page previews that child structure inline and links to the component definition editor where the nested structure is managed.

The model-number page does not inline-edit expected subcomponents. Keep expected subcomponent management on component definitions so model-number screens remain focused on the top-level device structure.

## Operator Workflows

Asset Components tabs render hierarchy context under each top-level component row:

- attached child components
- expected child templates
- removed expected children based on closed ancestry snapshots
- damaged or needs-attention issue badges

Component detail pages show attached children, expected subcomponents, and removed expected children for that parent component. Tracking an expected child creates a real child `ComponentInstance` attached to the parent. Moving the parent to another asset moves currently attached children with it. Detached, tray, stock, destroyed, or otherwise non-attached children do not move with the parent.

Detached expected children keep ancestry snapshot fields so they can remain visible as removed children under the source parent without inheriting future parent movement or history.

## Condition And Warnings

Placement and condition are separate:

- lifecycle/placement says where the part is, such as attached, tray, stock, or destroyed
- condition says whether it is healthy, needs attention, damaged, or similar

Damaged, needs-attention, and sold/returned components are warning-confirmed, not hard-blocked, for normal install/attach flows. Selling-state asset transitions also warn when attached damaged or needs-attention parts remain present, then allow confirmation.

Destroyed or destruction-pending components are terminal/locked for normal reinstall and reattach flows. Destruction must carry audit evidence through the destruction workflow.

## Specs And Attributes

Component definitions and component instances can contribute structured attribute values. Instance attributes override definition attributes at the same component row. Across the hierarchy, attached child values suppress parent values for the same calculated numeric spec.

Only currently attached components and subcomponents contribute to current asset calculated specs. Detached, tray, stock, destroyed, and current-asset-null components do not contribute. Damaged-but-attached parts still contribute because they are still physically present.

## Conversion Tools

Conversion tooling is intentionally review-gated.

Preview command:

```bash
php artisan component-hierarchy:preview-conversion
php artisan component-hierarchy:preview-conversion --json
```

The preview scans component definitions, model-number expected-component templates, existing expected-subcomponent templates, and numeric calculated-spec overlaps. It does not write data.

Selected-pair apply command:

```bash
php artisan component-hierarchy:apply-conversion --pair=12:34
php artisan component-hierarchy:apply-conversion --pair=12:34 --apply
```

The apply command defaults to dry-run. It only writes when `--apply` is passed, and it only creates selected pairs that are still current preview suggestions. It records conversion provenance in `metadata_json` and prints rollback guidance for the exact created template IDs.

Run conversion commands against a production-like clone first. Do not run write-mode conversion against production without reviewing the dry-run output and confirming the selected parent/child pairs.

## Current Limits

- No arbitrary recursive BOM support beyond `asset -> component -> subcomponent`.
- No separate subcomponent model.
- No automatic production-data conversion.
- No dedicated connectivity renderer beyond generic component/spec rendering.
- No mandatory physical label requirement for every subcomponent.
