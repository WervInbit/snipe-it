# Agents Addendum - 2025-12-18 Session Init

## Context
- Reviewed `AGENTS.md`, `PROGRESS.md`, `docs/fork-notes.md`, and recent `docs/agents/*` entries before making changes.
- Started the 2025-12-18 stub in `PROGRESS.md` so today's work can be logged as it lands.

## Worklog
- Created this session addendum to centralize 2025-12-18 notes and link them from the progress log.
- Reset DB for verification (migrate:fresh + targeted seeds), ran DeviceAttributeSeeder/DevicePresetSeeder/AttributeTestSeeder, and demo seeds to restore test runs locally.
- Updated model-number select list to show the model number code; creating a model number now redirects to its detail page.
- Scan page: added camera selector and explicit permission request button; JS repopulates devices after permission.
- Marked *_test attribute definitions as needs_test by default in DeviceAttributeSeeder (local env); prod will need a mapping to real attributes.
- Enforced serial uniqueness unconditionally (removed the unique_serial bypass); TODO: clean any existing duplicate serials manually.

## Follow-ups
- Prod tasks: map/create test attributes for test types, set needs_test=1, and link test_types.attribute_definition_id; run a one-off script before starting new test runs.
- Prod tasks: ensure serial duplicates are cleaned if they already exist (validation now blocks new duplicates).

# Agents Addendum - 2025-12-09 Session Init

## Context
- Reviewed `AGENTS.md`, `PROGRESS.md`, `docs/fork-notes.md`, and existing `docs/agents/*` entries to align with current workflow guidance before making changes.
- Confirmed the 2025-12-09 stub in `PROGRESS.md` captures today's kickoff so work can be logged as it lands.

## Worklog
- Attached the Dymo LabelWriter 330 Turbo to WSL via `usbipd`, started systemd/cupsd in Ubuntu 24.04, and created queue `dymo25` with the 25x25 (S0929120) media size.
- Set `.env` to target the WSL CUPS host (`CUPS_SERVER=172.22.110.249`) and queue `dymo25`, then cleared Laravel config cache.
- Installed `cups-client` in the `snipeit_app` container and verified it sees `dymo25` (`lpstat -h 172.22.110.249 -p -d`).
- Sent a sample 25x25 PDF from the app container to the queue (`lp -d dymo25 /var/www/html/sample-label-25x25-20251209-091356.pdf`) to confirm end-to-end printing.
- Finalized the S0929120 template (v13) with `qr_left` 3.2mm, `text_left` 1.8mm, padding 1.8mm; cleared cached labels, regenerated samples, and printed via CUPS (`dymo25-25`) using zero-margin `Custom.W72H72` media.
- Hardware create form now shows the model number code instead of the label so presets are identifiable at a glance.
- Hardware list "Name" column now displays the model name (fallback to asset name only if no model) to match refurb workflows.
- Asset tag generator now always issues tags with `INBIT-` + two letters + four digits (setup default matches), no longer tied to the auto-increment branch.
- QR/scan flow now sends you to the asset detail page (no longer jumps to the tests.active view).
- Scan page redesigned: camera preview centered with two primary controls (refresh camera, toggle flashlight) and auto-scrolls into view.
- Scan auto-scroll offsets slightly so the navbar and scan header stay visible when the camera is focused.

## Follow-ups
- Expand this log with concrete updates, tests run, and any documentation touchpoints, mirroring user-facing changes into `PROGRESS.md` and `docs/fork-notes.md` when applicable.
