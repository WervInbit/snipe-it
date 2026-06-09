# Docker E2E Rehearsal - 2026-06-08

## Environment
- Local Docker URL: `http://127.0.0.1:18080`.
- DB preflight before destructive reset: `APP_ENV=local`, `DB_CONNECTION=mysql`, `DB_DATABASE=snipeit`.
- Pre-reset SQL snapshot: `prodbak/db-snapshots/snipeit_pre_e2e_rehearsal_20260608_222456.sql`.
- Reset command completed successfully on rerun: `php artisan migrate:fresh --seed --force`.
- Bootstrap superuser was created outside the normal UI because production seeding creates settings and intentionally does not create users.

## Seed Baseline
- Production foundation seeded settings, status labels, attribute definitions, asset models, model numbers, component definitions, workflow profiles, workflow items, and permission groups.
- No demo users or demo assets were present after reset.
- Suppliers were still empty after the production foundation seed; `ProductionSupplierSeeder` is wired but currently has no production supplier names.

## UI Setup Performed
- Created UI users:
  - `rhea-refurb` in `Senior Refurbisher`.
  - `sam-supervisor` in `Supervisor`.
- Created manufacturer `Lenovo` through the UI.
- Created asset model `ThinkPad T480 Rehearsal` through the UI under `Laptops`.
- Created model number `20L6-SAMPLE` with label `ThinkPad T480 i5-8350U 8GB 256GB 14in`.
- Filled model-number specifications through the unified spec UI:
  - Introductiejaar: `2018`
  - Processor: `Intel Core i5-8350U`
  - Werkgeheugen: `8`
  - Opslagcapaciteit: `256`
  - Schermgrootte: `14`
  - Schermresolutie: `1920x1080`
  - OS-versie: `Windows 10 Pro`
  - Toetsenbordindeling: `QWERTY US International`
- Added expected components:
  - `RAM - Generic`
  - `SSD - Generic`
  - `Battery - Generic`
  - `Keyboard - Generic`
  - `USB-A Port - Generic`
  - `USB-C Port - Generic`
  - `HDMI Port`
- Created asset `E2E-OLD-LAPTOP-001` with serial `E2E-SERIAL-001` and status `Being Processed`.

## QR And Workflow Results
- Asset QR simulation `/scan/resolve/E2E-OLD-LAPTOP-001` resolved to `/hardware/1` for both bootstrap superuser and `rhea-refurb`.
- No component QR simulation was possible because expected components did not materialize tracked component instances for the new asset.
- `rhea-refurb` can scan and view the asset but receives `403 Forbidden` when starting a workflow and when opening `/hardware/1/edit`.
- Bootstrap superuser successfully ran and completed:
  - `Standard Diagnostics`: 8/8 passed.
  - `Pre-Sale Check`: 3/3 passed, including the `Done / Not Done` item.
- A workflow note persisted on the first Standard Diagnostics result.
- `tests_completed_ok` became `1` only after both sale-readiness-blocking workflows were completed.

## Lifecycle Results
- Ready for Sale saved successfully as bootstrap superuser.
- Ready for Sale did not set `is_sellable=1`; the asset remained not sellable unless the separate sale checkbox is used.
- Sold saved successfully as bootstrap superuser.
- Sold set `assets.archived=1` while leaving `deleted_at` null. Active list behavior should therefore be evaluated against the legacy `archived` flag, not soft deletion.
- Action log rows were created for asset updates, but the notes were generic/null rather than status-specific.

## Issues And Follow-Ups
- Seeded operational groups are too weak for real workflow execution: `Senior Refurbisher` has `tests.execute` but not `assets.edit`, while starting workflows requires asset update access.
- The seeded `Admin` group is named Admin but still lacks the actual `admin` permission; Ready for Sale/Sold role checks use `isAdmin()` or `isSuperUser()`.
- Asset create showed the spec panel message `Add a model number to this model before configuring specifications or overrides` even though the model-number preset was selected.
- `Being Refurbished` was not available in the asset create status dropdown, so the rehearsal used `Being Processed`.
- Optional enum spec `Opslagtype` failed to persist from the UI with `Use one of the defined options`, despite selecting `NVMe-SSD`.
- After saving the model spec, the page briefly still showed `No expected components added yet`, although the expected component templates were persisted.
- Ready for Sale status and sale listing are not the same operation today; decide whether the status transition should automatically make an asset sellable.
