# Local Demo Environment

This guide rebuilds a disposable local environment with the fork's foundation catalog, five operational demo users, and curated assets/workflow history.

> [!WARNING]
> These seeders are for an empty or explicitly disposable local database only. Demo accounts use a known password, and `DemoAssetsSeeder` deletes all existing assets, runtime component instances/events, and workflow execution data before creating its curated dataset. Foreign keys remain enabled, work-order asset links retain their snapshots with a null live asset reference, and asset IDs are not reset or reused. Do not run this sequence against production or a shared database containing records that must be preserved.

## Preflight

Confirm the effective target before seeding:

```powershell
docker compose -f docker-compose.yml -f docker-compose.localhost.yml exec -T app php artisan tinker --execute="echo app()->environment().'|'.config('database.default').'|'.config('database.connections.'.config('database.default').'.database');"
```

State the database name and impact before continuing. Repository policy requires explicit current authorization for any destructive reset or reseed on a shared environment.

## Seed Sequence

Run these commands in order from the application container or a configured PHP environment:

```powershell
docker compose -f docker-compose.yml -f docker-compose.localhost.yml exec -T -e SNIPEIT_ALLOW_DISPOSABLE_DATA_SEEDING=true app php artisan db:seed --class=ProductionFoundationSeeder --force
docker compose -f docker-compose.yml -f docker-compose.localhost.yml exec -T app php artisan db:seed --class=ProductionDemoUserSeeder --force
docker compose -f docker-compose.yml -f docker-compose.localhost.yml exec -T -e SNIPEIT_ALLOW_DISPOSABLE_DATA_SEEDING=true app php artisan db:seed --class=DemoAssetsSeeder --force
```

The disposable opt-in also enables five clearly labeled synthetic model-number
placeholders used by demo/development scenarios. Normal production/additive
catalog seeding excludes them. See
[`catalog-model-number-verification.md`](catalog-model-number-verification.md)
for the exact codes and promotion requirements.

The stages provide:

1. settings, permission groups, statuses, suppliers, device/model-number specifications, component catalogs, and workflow profiles/items;
2. five local operational accounts;
3. ten curated assets and representative workflow runs/results.

This is a reseed, not a restore. It cannot recover local users, assets, workflow evidence, component history, or settings that were not exported beforehand.

## Demo Accounts

All accounts use the local-only password `password`.

| Username | Intended role |
| --- | --- |
| `admin` | Superuser / Production Admin |
| `demo_admin` | Admin group |
| `demo_supervisor` | Supervisor group |
| `demo_senior_refurbisher` | Senior Refurbisher group |
| `demo_refurbisher` | Refurbisher group |

The implemented operational boundaries are documented in the [V1 role-capability matrix](v1-operational-role-capability-matrix.md).

## Verification

Check the seeded row counts:

```powershell
docker compose -f docker-compose.yml -f docker-compose.localhost.yml exec -T app php artisan tinker --execute="echo 'settings='.DB::table('settings')->count().'|users='.DB::table('users')->count().'|assets='.DB::table('assets')->count().'|workflow_runs='.DB::table('workflow_runs')->count();"
```

For the current curated dataset, the expected baseline is:

```text
settings=1
users=5
assets=10
workflow_runs=10
```

Then log in locally as `admin` and confirm that the dashboard, asset list, workflow settings, scan page, component pages, and work-order pages load.

## Optional Component Scenarios

`DevelopmentDeviceScenarioSeeder` creates additional component hierarchy scenarios and removes/recreates rows using its `DEV-COMP-` identifiers. Review that seeder and obtain explicit approval before using it:

```powershell
docker compose -f docker-compose.yml -f docker-compose.localhost.yml exec -T -e SNIPEIT_ALLOW_DISPOSABLE_DATA_SEEDING=true app php artisan db:seed --class=DevelopmentDeviceScenarioSeeder --force
```

Both destructive seeders fail closed outside `local`/`testing` and also require the explicit process-level opt-in shown above. The opt-in is not a substitute for the database preflight or approval. Do not use `migrate:fresh`, `migrate:refresh`, `migrate:reset`, or `db:wipe` merely to rebuild demo data. Those commands drop schema/data and are forbidden on shared environments without explicit approval.
