# Demo Guide

This guide walks through exploring the demo data seeded with the application.

## Seeding demo assets

Run the demo asset seeder to populate assets with sample test runs, results, and QR labels:

```bash
php artisan db:seed --class=DemoAssetsSeeder
```

For a full dev reset (schema + settings + users + assets + test runs), use:

```bash
php artisan migrate:fresh --seed
```

## Demo users

The seeders create demo accounts to showcase permissions:

- `admin` - superuser access
- `demo_admin` - admin-style operational access
- `demo_supervisor` - supervisor access with scan + test + asset management
- `demo_senior_refurbisher` - senior refurbisher with scan + test execution
- `demo_refurbisher` - refurbisher with scan + test execution
- `qa_manager` - QA-focused scan + test + audit access
- `inventory_clerk` - asset creation/edit + scan access
- `bench_tech` - refurb bench scan + test access
- `support_viewer` - read-only asset visibility
- `demo_user` - basic read-only asset visibility

All demo accounts use the default password `password`.

Use these accounts to explore how permissions affect the interface.
