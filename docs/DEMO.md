# Demo Instructions

This guide walks through the demo data included with the seeders.

## Populate Demo Data

Run the database seeds to create demo assets, users and supporting data:

```
php artisan migrate:fresh --seed
```

This will also generate QR labels and baseline test runs for the demo assets.

## Demo Accounts

All demo accounts use the default password `password`.

| Username | Role | Capabilities |
|----------|------|--------------|
| `demo_super` | Superuser | full access |
| `demo_admin` | Admin | admin access |
| `demo_supervisor` | Supervisor | scanning, create/delete assets and tests |
| `demo_senior_refurbisher` | Senior Refurbisher | scanning and execute tests |
| `demo_refurbisher` | Refurbisher | scanning |
| `demo_user` | User | read-only asset access |

Log in with these accounts to explore how permissions affect the interface and to view test run audit logs and QR labels generated for each asset.
