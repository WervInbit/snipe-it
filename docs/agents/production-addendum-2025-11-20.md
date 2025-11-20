- Pulled latest origin/master (f3bc99e35), rebuilt stack (docker compose up -d --build), ran php artisan migrate --force --seed, and rebuilt assets (docker compose run --rm node npm ci && npm run prod).
- Label printing env set in .env: LABEL_PRINTER_QUEUE/ LABEL_PRINTER_QUEUES=dymo99010, LABEL_PRINT_COMMAND=lp, CUPS_SERVER=172.18.0.1; config cache cleared.
- Host CUPS opened to Docker subnet (172.18.0.0/16) on 631; app container sees queue via lpstat -a and test job succeeded.
- 99010 template adjusted to 89x28 mm in config/qr_templates.php; queue defaults synced with lpoptions -p dymo99010 -o media=99010 -o PageSize=w89h28.
- Tests page JS fixed: added Mix target for resources/js/tests-active.js public/js/dist/tests-active.js; rebuilt assets so buttons load.
- CUPS hardware error encountered: com.dymo.out-of-paper-error; jobs cleared (cancel -a dymo99010). Reseat/load 99010 roll and run echo test | lp -d dymo99010 before retrying app print. Keep 631/tcp open to containers.
- Config changes are local only (not committed): .env with the printer queues/ CUPS_SERVER. Open item: verify full-label print with 89x28 template; tune qr_size/padding if clipping persists; hard-refresh tests page after JS rebuild.

You can paste the above from docs/agents/production-addendum-2025-11-20.md to inform the dev environment.
