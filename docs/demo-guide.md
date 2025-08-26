# Demo Guide

This guide walks through exploring the demo data seeded with the application.

## Seeding demo assets

Run the demo asset seeder to populate assets with sample test runs, results, and QR labels:

```
php artisan db:seed --class=DemoAssetsSeeder
```

## Demo users

The seeders create demo accounts to showcase permissions:

- **demo_super** – superuser access
- **demo_admin** – administrative access
- **demo_user** – read-only asset access

All demo accounts use the default password `password`.

Use these accounts to explore how permissions affect the interface.
