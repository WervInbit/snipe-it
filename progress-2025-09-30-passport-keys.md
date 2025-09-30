# Passport Key Init Addendum (2025-09-30)

## Completed
- Diagnosed docker volume resets clearing Passport key material, triggering CryptKey errors on OAuth requests.
- Updated docker/app/entrypoint.sh to regenerate keys on boot, enforce www-data ownership, and set restrictive permissions across fresh containers.
- Logged remediation guidance in PROGRESS.md so follow-up sessions know to rebuild the app service and verify hardware listings.
- Retired the unused SKU scaffolding in favour of model-number data; removed UI/API hooks, added a cleanup migration, and extended transformers/tests accordingly.

## Outstanding
- Rebuild the app container to ensure the entrypoint automation runs on the next boot and confirm the hardware index renders assets as expected.
- After future volume purges, spot-check storage/oauth-*.key ownership and permissions; capture entrypoint logs if regeneration fails again.
- [ ] Run `php artisan schema:dump --prune` after version 1 is accepted to squash legacy migrations while keeping fresh installs working.
- [ ] Apply the new SKU cleanup migration (`php artisan migrate`) across environments once version 1 is tagged.
- [ ] Install composer dev dependencies in the app container so `php artisan test` can run (Collision dependency currently missing).
