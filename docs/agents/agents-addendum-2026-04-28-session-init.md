## Session Init
- Date: 2026-04-28
- Purpose: reinitialize for a new week and continue from the recently shipped component/spec workflow tranche.

## Current Baseline
- Branch: `master`
- HEAD at session start: `12586f04c` (`Document Component Workflow Changes`)
- Latest pushed stack already on `origin/master`:
- `947ff2a11` `Harden Work Order And Component Access`
- `cf09e0baf` `Implement Component-Driven Specs And Workflows`
- `12586f04c` `Document Component Workflow Changes`

## Recovered Context
- Attribute versioning is no longer user-facing; datatype stays immutable while keys/options remain editable/correctable.
- Component definitions contribute shared specification values through the existing attribute system.
- Model-number specification editing and expected-component handling were unified in the recent tranche.
- Asset components now follow expected-baseline plus tracked-deviation semantics, with the large tray/stock/detail workflow redesign in place.
- Recent UI follow-ups completed around:
- removed-row styling and linking
- expected-row definition links
- component-detail history asset links
- status dropdown and modal-backed lifecycle flows

## Local-Only Dirt At Session Start
- `docker-compose.yml`
- `docker/nginx.conf`
- `docs/agents/agents-addendum-2026-03-19-session-init.md`
- `storage/tmp-testtypes-reorder.js`

## Notes
- No new code or DB changes were made during this reinitialization step.
- Next work should continue from the pushed 2026-04-23 component/spec workflow baseline unless redirected.
