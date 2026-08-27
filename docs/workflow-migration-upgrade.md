# Workflow Table Upgrade And Rollback

The legacy `test_*` to `workflow_*` transition is deliberately staged:

1. `2026_05_26_120000_rename_tests_to_workflows_and_add_profiles.php` creates only the destination schema.
2. `2026_05_26_120010_copy_legacy_tests_to_workflows.php` copies rows by primary key, verifies every mapped value and ID set, and records whether a legacy source existed.
3. `2026_05_26_120020_cutover_workflow_photo_foreign_key.php` repeats the parity check and only then retargets the optional `asset_images.source_photo_id` foreign key.

The copy phase is retryable after partial DDL or DML completion. Existing destination rows are compared rather than skipped, missing rows are inserted with their original IDs, and any conflicting row, relationship, or count stops the migration. Foreign-key enforcement is restored in a `finally` block even when cutover fails.

## Compatibility Window

Legacy tables are intentionally retained after a successful upgrade. Do not drop them manually. Their removal requires a future, explicit cleanup migration after:

- a database backup and restore rehearsal;
- a release-long compatibility window;
- a fresh parity check;
- confirmation that rollback to the legacy application is no longer supported.

An installation upgraded by the older all-in-one migration may already lack legacy tables. The new copy phase records that source state as `absent`, validates the workflow schema, and does not claim historical parity that can no longer be proven.

## Production Rehearsal

Before applying the upgrade:

1. Put the application in maintenance mode and stop queue/scheduler writers.
2. Take a database-native, transactionally consistent backup and a matching copy of workflow/photo storage.
3. Restore both into an isolated clone and run the complete migration there first.
4. Confirm that `workflow_migration_checkpoints` contains `legacy_copy_verified` and `asset_image_fk_cutover_verified`.
5. Compare legacy/workflow row counts and exercise a workflow run plus its evidence photo before migrating production.

The database backup is the primary recovery path. Keep it until the compatibility window and a production smoke test are complete.

## Rollback Boundary

The migrations support deterministic rollback when the workflow data still fits the legacy Standard Diagnostics model. Before dropping the workflow schema, rollback:

- copies current workflow rows back by ID;
- verifies mapped values and relationships;
- restores the exact foreign-key target that existed before cutover;
- records a verified rollback-copy checkpoint.

Rollback fails closed when data would be lost, including additional workflow profiles, category/profile assignments, non-standard run snapshots, result/profile-item mismatches, destination-only legacy rows, or a missing checkpoint. In those cases, restore the tested backup or write a reviewed data-conversion migration; do not force-drop tables.

Use `php artisan migrate:status` on the isolated clone to identify the exact migration batch before rehearsing Laravel rollback. Do not assume a fixed `--step` count, because later migrations may share the deployment batch.

## Database Coverage

Automated upgrade tests exercise clean, populated, interrupted/retried, mismatch, and rollback states on in-memory SQLite. The implementation also verifies foreign-key metadata through MySQL/MariaDB `information_schema` and PostgreSQL `information_schema`.

Before V1 production release, repeat the populated upgrade and rollback rehearsal on the exact supported MySQL/MariaDB image. PostgreSQL should remain outside the declared V1 support matrix until the repository's other PostgreSQL-specific migration gaps are resolved and the same rehearsal passes there.
