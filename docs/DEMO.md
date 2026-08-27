# Demo Instructions

The maintained disposable-data instructions, safety preflight, opt-in flag, account list, and verification steps live in [Local Demo Environment](demo-guide.md).

Do not use `migrate:fresh`, `migrate:refresh`, `migrate:reset`, or `db:wipe` to create demo data. `DemoAssetsSeeder` and `DevelopmentDeviceScenarioSeeder` are blocked outside `local`/`testing` and require `SNIPEIT_ALLOW_DISPOSABLE_DATA_SEEDING=true` for the current process even in an allowed environment.
