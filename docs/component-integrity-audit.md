# Component integrity release gate

`php artisan components:audit-integrity` is a read-only release and upgrade
preflight. It never repairs data and exits with status `1` when it finds:

- live children linked to a missing or soft-deleted parent;
- parent/child `current_asset_id` or `root_asset_id` mismatches;
- attached child components without a live parent;
- expected-subcomponent states whose materialized plus removed quantity exceeds
  the configured expected quantity.

Run it against the current application database before applying release
migrations, after taking the pre-change backup:

```sh
php artisan components:audit-integrity
php artisan components:audit-integrity --json > component-integrity.json
```

For the production Compose profile:

```sh
docker compose --env-file /etc/snipeit/production.env \
  -f docker-compose.production.yml --profile production \
  run --rm app php artisan components:audit-integrity --json
```

Exit `0` means every audited category is clean. Exit `1` is a release gate:
preserve the JSON report, review the exact component/state IDs, and use a
separately reviewed backup-and-repair procedure. Do not proceed by deleting or
rewriting the reported rows ad hoc. Re-run the audit after any approved repair
and again after the migration rehearsal on the restored clone.
