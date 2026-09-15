# Workflow Run And Protected Status UX - Implementation Plan

Date: 2026-09-15

Status: implemented in the working tree on 2026-09-15 for workflow execution,
configuration, run editing, and the asset-detail protected-status selector. The
implementation uses the additive `2026_09_15_130000` migration because the
first execution-guard migration was already recorded on the development
database. Consolidating the existing full-edit, bulk, and API status-warning
paths into the same modal/response contract remains a follow-up.

## Outcome

Keep workflow completion as a calculated process state without ever using it
as an automatic edit lock. Make another run an explicit privileged override,
keep a stable and understandable current run, add capability-based execution
levels, expose a compact Ready-for-Sale workflow summary on the asset Info tab,
and replace warning/reload/resubmit status changes with one server-verified
confirmation dialog.

## Confirmed Product Direction

1. An answered or completed run stays editable. Leaving the page and answering
   the last Required item do not close or finalize it.
2. An incomplete current run is continued; it is not duplicated.
3. Starting another run is a separate `Start new...` action and always requires
   an authorized, reasoned confirmation. The previous run remains editable.
4. Every workflow profile stays visible in the main numbered process list,
   including unavailable and role-restricted profiles.
5. Dependency configuration remains editable after rollout. Changes affect
   live/future progression without rewriting historical snapshots.
6. Every dependency requires all Required items in the prerequisite workflow
   to be Pass/Done. Optional items do not block.
7. A dependency override authorizes only that exceptional start. It never marks
   the dependency as successful or cascades into permission for later steps.
8. A configured profile with no applicable items stays visible as `Needs
   configuration` and cannot start or satisfy sale readiness.
7. A protected lifecycle change must be confirmed or cancelled in a dialog
   before any status write occurs.
8. Dependency selection should use a searchable multi-select rather than one
   checkbox block per possible prerequisite.

## Three Different Meanings Of Required

These concepts are related but are not interchangeable:

- `workflow_items.is_required` is the default when an item is added to a
  profile. Its UI label should become `Required by default`.
- `workflow_profile_items.is_required` is authoritative for that item in that
  particular profile and is snapshotted onto a run. Its UI label should become
  `Required in this workflow`. It determines when the profile is complete and
  whether a dependency can pass.
- `workflow_profiles.blocks_sale_readiness` means the profile is a required
  milestone before Ready for Sale. Its UI label should remain explicit, such
  as `Required before Ready for Sale`; it must not be shortened to `Required`.

The item-level flag cannot replace the profile-level sale flag. Required items
explain how a profile completes, but they do not say whether that profile must
be executed for this asset before a lifecycle transition. Keep both levels,
with clearer labels.

For the operational graph, mark only the terminal sale milestone profiles as
direct Ready-for-Sale blockers where practical. Their applicable transitive
prerequisites become part of the effective sale requirement automatically.
This avoids redundant manual flags while retaining workflows such as Shipping
that occur after Ready for Sale.

## Searchable Dependency Multi-select

Replace the nested checkbox payload with one local Select2 multiple select:

- label: `Must be completed before this workflow`;
- placeholder: `Search workflows`;
- selected values render as removable name chips;
- option text includes process number, profile name, active state, and required
  execution level;
- the current profile is excluded;
- options follow configured process order;
- help text explains that these are direct prerequisites and every selected
  workflow must pass;
- inactive selected profiles stay visible with an `Inactive` suffix rather
  than disappearing.

Use the already-loaded profile collection rather than an AJAX endpoint for the
expected tens of profiles. Add a paginated endpoint only if real profile counts
grow beyond roughly 100. Since the field is inside Bootstrap modals, initialize
Select2 when each modal is shown, with a unique ID, `width: 100%`, and that
modal as `dropdownParent`; relying only on global hidden-field initialization
can produce a zero-width control, clipped menu, or unusable search focus.

Submit a flat `dependency_profile_ids[]` array. Validate `array`, integer,
distinct, existing profile, and not self on the server. Keep cycle detection
server-side. Serialize all graph mutations by locking workflow-profile rows in
a consistent order before reading the graph and syncing edges; otherwise two
simultaneous individually valid edits can commit a cycle together.

Do not infer dependency order from the order in which chips were selected. A
multi-select represents a set of prerequisites. The graph plus profile display
order determines the numbered process. Redundant transitive edges may receive
an administrator warning but should not be silently rewritten.

## Current And Historical Runs

Use one rule everywhere: the current run for an asset/profile is the newest
`started_at`, then highest ID. Sale readiness, progression, Info summaries,
agent responses, and history labels must use that same selector. A historical
edit never promotes an older run.

`finished_at` remains a calculated completion timestamp, not a lock:

- incomplete to complete: set it;
- complete to incomplete: clear it;
- complete to complete after note/photo/optional edits: preserve it;
- incomplete edits that remain incomplete: keep it null.

The current run gets `Edit run` in every state. A historical run gets `Edit`
plus a `Historical - does not control current progression` label. If no newer
run exists, editing and reopening the run immediately changes dependency and
sale-readiness state. If a newer run exists, only that newer run controls live
state.

When any run already exists, ordinary executors cannot create another one.
Replace the `allowed` repeat policy with confirmed override during the new
migration. Retain `never` as an optional stricter policy. A stale run also
counts as an existing run: only an authorized user can create its replacement.

## Explicit Capabilities And Execution Levels

Do not inspect group names. Add a constrained profile `execution_level` with:

- `operator` -> `tests.execute`;
- `senior` -> `tests.execute.senior`;
- `supervisor` -> `tests.execute.supervisor`.

The seeded hierarchy should grant Senior both operator and senior execution,
Supervisor all three, and Admin all abilities. Use the following separate
capabilities:

- `tests.edit_runs`: edit an otherwise authorized run;
- `tests.start_new_run`: start another run when history already exists;
- `tests.override_dependencies`: start despite unmet prerequisites;
- `assets.sale_transition`: perform a clean protected transition;
- `assets.override_sale_readiness`: proceed despite workflow/component issues.

Recommended initial grants: all operational roles receive `tests.edit_runs`;
Supervisor and Admin receive the two workflow override abilities and the two
sale abilities. Senior does not receive `tests.start_new_run` initially, but an
administrator can grant it deliberately.

Starting and every result mutation must check the profile execution level in
addition to the general ability. All users with asset view rights may see the
row and result history. A user without the execution capability sees the
required-level label and a disabled action. Prefer a separate profile whenever
responsibility changes; per-item execution permissions inside one run are out
of scope until a real workflow cannot be split cleanly.

The bearer-token agent remains unable to confirm a human override. It returns a
structured 409 for repeats, stale replacement, unmet dependencies, or an
execution level its token user lacks.

## Main Workflow List

Keep the current four-block presentation for small lists. Make each block
progressively compact as the list grows:

- fixed process number and state icon;
- profile name and optional execution-level badge;
- one short blocker or progress line;
- `Edit run` or `Continue` as the ordinary primary action;
- privileged `Start new...` as a separate secondary action;
- descriptions, all blocker details, and override history behind expansion.

All rows remain present and keep the same numbers for every user. Highlight the
current or next actionable row. At higher counts, collapse verbose details, not
the rows themselves. This preserves process awareness without turning the page
into a wall of warnings.

## Asset Info Summary

Add one read-only `Required before Ready for Sale` row to the existing Info
striped list. It should show:

- `N of M complete`;
- a Ready-for-Sale ready/blocked label;
- compact rows for direct blocking profiles plus their applicable transitive
  prerequisite closure;
- the same process numbers and state labels as the full workflow list;
- required execution-level badges where useful;
- one `Open Workflows` link, with no start or override controls on the Info tab.

Initially expand the rows when the count is small. At more than six effective
required profiles, show the count and first incomplete/current row, with `Show
all`. The complete main process remains visible in the Workflows tab.

Use one shared state projection for progression and sale readiness to prevent
different answers and duplicate queries. The current code evaluates progression
in one eager-loaded query set but separately queries sale-blocking profiles and
runs; consolidate this before adding another summary consumer.

Effective sale requirements are the applicable profiles marked
`blocks_sale_readiness` plus their applicable prerequisite closure. An inactive
or empty prerequisite of an active dependent must be a visible configuration
blocker, not silently treated as success. A category-inapplicable prerequisite
is waived for that asset and recorded as not applicable. Administrators must
disconnect an intentionally retired prerequisite before deactivating it.

## Protected Status Confirmation

Replace immediate dropdown submission with a preview-and-confirm flow for
Ready for Sale and Sold:

1. Selecting a protected target makes no database change.
2. Request a server-side transition preview.
3. Show old status, target status, required workflow states, failed/incomplete
   items, and component issues together in one Bootstrap modal.
4. Cancel closes the modal and resets the selector to the stored status.
5. A clean transition uses a simple Confirm button.
6. A transition with issues requires `assets.override_sale_readiness` and a
   non-empty reason.
7. Confirm performs one transactional server request and then refreshes the
   displayed asset state.

Bind confirmation to the exact evaluated state with a signed fingerprint that
includes asset/status version, target status, current workflow run IDs and
states, and relevant component issue identities. Lock the asset row and
recompute the guard during the write. If the state differs, return 409 and
refresh the modal; never let a confirmation for one risk set acknowledge a
newer one.

The implemented slice uses the transition-guard service for the detail selector
and retains the existing model-save defense for other write paths. Full asset
edit, bulk changes, and API requests still use their legacy warning/acknowledge
contracts; consolidating those paths into the same structured guard is future
work. Persist override actor, reason, target, fingerprint, and issue snapshot
on the status event. Existing free-text status notes remain separate.

## Additive Schema And Compatibility

The first workflow-guard migration is already recorded on `dev.inbit`; do not
edit it. Add a new forward-only migration for:

- `workflow_profiles.execution_level`, default `operator`;
- normalization of legacy `repeat_policy = allowed` to `override_required`;
- structured protected-transition confirmation/override fields on
  `asset_status_events`.

No migration should infer dependencies, change process order, or lock existing
runs. Existing run/result evidence and audit rows remain intact. Execution level
is an authorization rule and should not be added to the run definition/readiness
hash. Required-item edits remain definition changes and continue to stale a run.

Before production, report assets with multiple runs where newest-started
selection differs from the old completion-time selection, and assets whose
Ready-for-Sale outcome changes when prerequisite closure is enforced.

## Implementation Sequence

1. Add focused characterization tests for current editable-run, repeat,
   readiness selection, dependency graph, status warning, and permission
   behavior before refactoring.
2. Add the new migration, permission definitions/seeds, execution-level model
   helpers, and migration tests for SQLite and disposable MariaDB.
3. Introduce a shared current-run selector/state projection and switch
   progression/readiness to it. Correct completion timestamp transitions.
4. Replace dependency checkboxes with the modal-safe searchable Select2 field;
   add flat validation, graph locking, cycle/concurrency tests, and clearer
   Required labels.
5. Enforce profile execution levels on web and agent starts and every result
   mutation. Add explicit edit/current/history and privileged Start-new actions.
6. Compact the full workflow rows and add the read-only Info summary backed by
   the shared state projection.
7. Build the shared protected-transition guard, preview/fingerprint contract,
   status-event audit fields, and detail/full-edit/bulk/API integrations.
8. Run focused PHP, policy, migration, JS, accessibility, mobile/browser, and
   production asset-build checks. Perform read-only development and production
   impact reports before enabling new permissions or readiness closure.
9. Update README, fork notes, permission/API/migration docs, and create new
   workflow/operator-guide versions without modifying accepted PDFs.

## Required Test Coverage

- dependency multi-select create/edit/empty persistence, modal search/focus,
  self/unknown/duplicate IDs, direct/indirect/concurrent cycles;
- inactive, empty, category-inapplicable, redundant, and later-edited
  prerequisites;
- per-item Required default versus per-profile Required override and sale-level
  requirement labels;
- current run selection with two or more runs and edits to each historical run;
- completed run note/photo edits, reopen, recomplete, stale replacement, and
  repeat-never behavior;
- Operator/Senior/Supervisor/Admin visibility, start, edit, forged requests,
  direct grants, and cross-user editing;
- agent role/repeat/dependency structured 409 responses and no partial writes;
- Info summary count/order/closure/stale/failure/in-progress/success and query
  count regression coverage;
- clean protected transition, cancel, warning override, missing permission,
  missing reason, combined workflow/component issues, changed fingerprint,
  concurrent status change, full edit, bulk, model hook, and API paths;
- keyboard, screen-reader labels, 320px layout, Select2 modal behavior, and the
  unchanged four-profile visual baseline.

## Decisions Still Open

Only these points require product confirmation before implementation; the
recommended defaults are safe enough to proceed if the owner delegates them:

1. Whether Senior receives `tests.start_new_run` by default. Recommendation:
   no; Supervisor/Admin only.
2. Whether every clean Ready-for-Sale transition should still show a simple
   confirmation. Recommendation: yes, because the dropdown currently writes
   immediately and sale release is consequential.
3. Whether ordinary operators may edit another operator's run during shift
   handoff. Recommendation: yes through explicit `tests.edit_runs`, while the
   audit log records the actual editor and execution-level checks still apply.
4. Whether deactivating a prerequisite should waive it automatically.
   Recommendation: no; block the dependent as a configuration error until the
   edge is deliberately removed or the prerequisite is reactivated.
