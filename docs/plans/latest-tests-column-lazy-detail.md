# Plan: Latest Tests Column Counts + Lazy Hover Detail

## Goal
Provide a numeric "completed/total" status in the Latest Tests column, with a lazy-loaded hover detail that lists missing/failed tests. The list page must remain fast and should not eagerly pull test details or images.

## Scope
- Assets list table: show `completed/total` and color by status.
- Hover detail: missing + failed tests, with optional note/photo indicators.
- No non-required tests are expected in this fork, so all tests are treated as required.
- No run exists: show `0/0` with a "No test run recorded" tooltip.

## Assumptions
- Test runs are created from test types and all current test types are required.
- "Not tested" is `status = nvt`.
- Existing test results can include notes and photos (single or multi-photo).
- We should avoid downloading images on the list page unless the user explicitly asks.

## Desired UX
- Column shows a compact ratio:
  - Grey: `0/0` (no run).
  - Warning: `X/Y` when any failed result exists.
  - Green: `Y/Y` when all tests passed.
- Tooltip behavior:
  - On hover, show "Loading..." then populate with missing and failed test names.
  - Include note/photo indicators as counts, not full media.
  - Add a "View run" or "View photos" link for deep details.
- Touch devices:
  - Provide a click fallback to open the same detail (popover or small modal).

## Data Strategy
Compute on read (no schema changes).
- Add query-time subselects for the latest run and its result counts.
- Keep the list payload small and avoid duplicating state in the database.
- Accept the tradeoff of heavier list queries; keep the subqueries lean and indexed.

## Recommended Plan (Compute on Read)
### Summary semantics
- Latest run = most recent by `COALESCE(finished_at, created_at)`.
- `latest_tests_total` = count of results in latest run.
- `latest_tests_completed` = count of results with status != `nvt`.
- `latest_tests_failed` = count of results with status == `fail`.
- If no run: treat totals as 0; UI shows `0/0` and "No test run recorded".

### Query strategy
- Add subselects in `AssetsController@index` to expose the summary fields:
  - `latest_test_run_id` as a subquery for the latest run.
  - `latest_tests_total`, `latest_tests_completed`, `latest_tests_failed` as counts by status.
- If aliases cannot be referenced in subqueries, use `leftJoinSub` for the latest run id and then count results in subqueries keyed by that id.
- Keep indexes on `test_runs.asset_id` and `test_results.test_run_id` in mind; add if missing.

## API + Transformer changes
- `AssetsTransformer` includes computed summary fields:
  - `latest_tests_total`, `latest_tests_completed`, `latest_tests_failed`, `latest_test_run_id`.
- Keep `tests_completed_ok` for existing logic (status transitions and gating).

## List UI (bootstrap table)
- Update `testsHealthFormatter`:
  - Use ratio from summary fields instead of "Pass/Needs attention".
  - Color by status:
    - Grey: `0/0` (no run or total = 0).
    - Warning: any failed.
    - Green: no failed and completed == total and total > 0.
- Add a `data-test-summary-url` attribute with the asset id for lazy fetch.
- Default tooltip text:
  - "No test run recorded" for 0/0.
  - "Loading..." for assets with a run.

## Lazy detail endpoint
- New endpoint: `GET /api/v1/assets/{asset}/latest-test-summary`.
- Response:
  - `run_id`, `total`, `completed`, `failed_count`, `missing_count`.
  - `failed`: list of `{label, note_present, note_excerpt, photo_count, updated_at}`.
  - `missing`: list of `{label}`.
- `note_excerpt` should be a short, plain-text snippet (e.g., 120 chars) to keep payload small.
- Auth: same permissions as viewing assets.
- Do not return image URLs. Instead return `photo_count` and a link to the run detail if needed.

## Frontend hover behavior
- Attach hover handler to the Latest Tests cell:
  - Debounce (100-150 ms).
  - Skip if already loaded or in-flight.
  - Fetch JSON, build tooltip HTML, then call `tooltip('fixTitle')`.
- Cache result per asset id in memory to avoid repeat fetches.
- On error: show a fallback message and keep existing label.

## Notes on images + notes
- Do not embed images in tooltips. Show a small "Photo" marker with a count.
- Show a short note excerpt (plain text) for failed tests to keep the hover light.
- Provide a run detail link for full notes/photos when needed.

## Testing
- Unit test for summary builder logic.
- Feature test for summary endpoint (permissions + payload).
- Feature test for list transformer fields.
- Manual UI check for hover behavior and touch fallback.

## Rollout steps
- Add migration and backfill.
- Update summary builder + call sites.
- Update transformer + list formatter.
- Add endpoint + JS hover fetch.
- Update docs (fork notes and PROGRESS).
