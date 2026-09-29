# Numeric Component Aggregation Production Plan

## Decision

Deploy the aggregation work as an application release plus one additive
database migration. Do not edit every component, component instance, model
number, or asset.

The migration adds `attribute_definitions.component_aggregation_mode` with the
backward-compatible default `sum`, then changes only the attribute definition
whose key is `ram_speed_mhz` to `distinct`. Existing catalogue values remain
unchanged:

- the NUC model-number template still requests two memory modules;
- the memory component still contributes `8 GB` and `3200 MHz`;
- RAM capacity still resolves as `8 GB x 2 = 16 GB`;
- RAM speed resolves as one distinct shared value, `3200 MHz`, instead of the
  invalid additive value `6400 MHz`.

The new application code is required. Adding or editing production data alone
cannot fix the issue because the current deployed resolver always multiplies
numeric component contributions by quantity.

## Existing Attribute Review

The production backup contains 49 attribute definitions, of which 12 are
numeric. The narrow release decision is:

| Attribute | Production mode | Reason |
| --- | --- | --- |
| `ram_speed_mhz` | `distinct` | Speed is shared, not additive; this fixes the known two-module NUC case. |
| `ram_size_gb` | `sum` | Module capacities must add together. |
| `storage_capacity_gb` | `sum` | Installed storage capacities are additive. |
| `cpu_core_count` | `sum` | Current catalogue derives this from one logic-board component per model. |
| `camera_megapixels` | `sum` for now | Camera specifications display component labels; different lenses are intentional and should not raise a mixed-value warning. |
| `battery_capacity_mah` | `sum` for now | Current templates contribute one relevant component at quantity one. |
| `battery_capacity_wh` | `sum` for now | Current templates contribute one relevant component at quantity one. |
| `display_refresh_rate_hz` | `sum` for now | Current templates contribute one display at quantity one. |
| `display_size_inches` | `sum` for now | Current templates contribute one display at quantity one. |
| `power_delivery_watts` | `sum` for now | It currently has no component contributions; a future capability-oriented mode may be more appropriate. |
| `release_year` | `sum` for now | It currently has no component contributions. |
| `weight_kg` | `sum` for now | It currently has no component contributions. |

Do not bulk-change every non-additive-looking numeric attribute to `distinct`.
The current distinct mode treats differing values as a conflict, which is
correct for mixed RAM speeds but not for intentionally different camera
lenses. If broader aggregation is needed later, add separately named semantics
such as `list unique`, `must match`, or `maximum` before changing those
attributes.

## Authorization and Future Catalogue Changes

No new permission key is introduced. Users with attribute edit permission can
choose the aggregation mode while defining an attribute. Changing the mode of
an attribute already used by a component definition or instance also requires
the existing attribute lifecycle-management permission because it immediately
changes resolved specifications. In the present permission matrix that keeps
in-use changes with Admin, rather than ordinary or Senior Refurbishers.

New numeric attributes default to `sum`. Catalogue administrators should
choose:

- `Sum values by quantity` for capacities and counts; or
- `Keep distinct values` for a shared property that should agree across
  repeated components, such as memory speed.

## Rehearsal Evidence

The change was rehearsed against an isolated MariaDB 11.4 restore of the
verified off-host production backup. Production was not connected to or
changed.

- Before migration, asset `INBIT-AA0010` resolved to `6400 MHz` and `16 GB`.
- Migration added the column, retained `sum` for 48 definitions, and set only
  `ram_speed_mhz` to `distinct`.
- After migration, the same unchanged catalogue data resolved to `3200 MHz`
  and `16 GB`.
- Attribute and asset counts remained 49 and 15; duplicate asset-tag and
  non-empty serial checks remained zero.
- Rollback and reapplication were exercised in the disposable restore, and a
  repeated migration correctly reported that there was nothing to migrate.

The saved backup predates the Cisco catalogue addition, but that addition
created no physical asset, introduced no attribute, and added no numeric
component contribution, so it does not affect this aggregation result.

## Production Procedure

Use the normal controlled upgrade procedure in `docs/production-deployment.md`:

1. Build, test, and publish immutable application and web images from the
   accepted commit; record their digests.
2. Validate and pull those exact digests before maintenance.
3. Enable maintenance mode and stop the queue and scheduler so no writers are
   active.
4. Take and verify a transaction-consistent database dump and the standard
   upload, key, configuration, and release-state backups; retain an off-host
   copy.
5. Confirm the expected pending migration, then run `php artisan migrate
   --force` exactly once with the new application image.
6. Run the standard additive `ProductionPermissionGroupSeeder` used on
   upgrades. This aggregation feature itself adds no permission.
7. Start the new app and web images while maintenance remains enabled, verify
   health, then reopen the application and start queue and scheduler.
8. Complete the checks below before accepting the release.

Do not run `ProductionFoundationSeeder`, demo seeders, or scenario seeders on
an upgrade. Do not manually edit component values to compensate for the old
calculation. Do not use `migrate:rollback` on the only production database.

The migration is additive, so the old application ignores the extra column if
an application-image rollback is required. A database rollback, if it becomes
necessary, must use the verified pre-change backup under maintenance mode.

## Acceptance Checks

- The new column exists and exactly one definition, `ram_speed_mhz`, is
  `distinct`; all other existing definitions remain `sum`.
- Existing entity counts are unchanged and duplicate asset-tag/non-empty
  serial checks remain zero.
- `INBIT-AA0010` shows `3200 MHz` memory speed and `16 GB` RAM.
- Other representative effective specifications remain unchanged, including
  CPU cores, storage capacity, display details, batteries, and camera labels.
- Admin can see and change aggregation settings. A non-lifecycle user cannot
  change the mode of an attribute already used by component data.
- A same-speed repeated-component test shows one value; deliberately mixed RAM
  speeds show both values and the operator warning.
- HTTPS health, authenticated login, queues, scheduler, logs, and the usual
  production smoke checks remain healthy.
