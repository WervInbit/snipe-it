# Agent Addendum - 2026-09-15 Workflow Feature

## Feature Change Continuation

- A new owner-requested working block is initialized for a feature update.
- The target feature, intended behavior, and acceptance criteria are pending;
  no feature implementation or validation has started.
- Preserve all existing uncommitted operator-guide work. Once the feature is
  identified, inspect its code, tests, configuration, and documentation
  touchpoints before changing it.

## Workflow Dependency Investigation Outcome

- Investigated the requested workflow dependency, ordering, repeat, warning,
  override, and list behavior without implementing it.
- Current profile/item order does not gate execution; web and agent paths can
  create repeated runs, and `finished_at` does not prove all results completed.
- The recommended design and open product decisions are recorded in
  `docs/plans/workflow-execution-dependencies-2026-09-15.md`.
- Existing operator-guide changes were preserved. No runtime, database,
  migration, application, production, or deployment action was taken.

## Workflow Dependency Implementation Outcome

- Implemented configurable cross-profile dependencies and repeat policies with
  cycle-safe administration. Existing workflows remain unconnected until an
  administrator deliberately prepares the operational graph, and later edits
  apply to future starts without rewriting historical snapshots.
- Added a shared numbered progression list and server-authoritative state
  evaluator. All applicable workflows remain visible; guarded workflows are
  muted with the exact unmet prerequisite. Applicable prerequisites are placed
  before dependents while preserving configured display order where possible.
- Serialized starts on the asset. Existing incomplete runs continue rather
  than duplicate; guarded repeats/early starts require the dedicated Supervisor
  or Admin permission plus warning confirmation and a required audited reason.
  An override authorizes only that exceptional start and cannot make a later
  dependent eligible. The bearer-token agent fails closed with structured 409
  blockers.
- Corrected completion so editable per-profile Required items gate progression:
  each must Pass/Done under the default dependency rule, while optional items
  do not delay the next workflow. Profiles with no required items fail safely
  by requiring every result; configured profiles with no applicable items stay
  visible as `Needs configuration` and cannot be started.
- Corrected configured profile sorting, added persistent mouse/touch drag
  ordering, kept agent default-profile selection independent, and versioned
  readiness hashes so presentation-only reordering preserves legacy runs.
- Fixed Bootstrap checkbox/radio label overlap through explicit input spacing.
- Added dependency, success-state, override, resume, repeat, cycle, agent, UI,
  and CSS coverage. Initial guarded SQLite passed 83 tests / 569 assertions;
  the required-item/order follow-up passed 85 tests / 536 assertions. Focused
  PHPStan and PSR-12 checks, the frontend production build, and the Node suite
  passed. The additive workflow-guard migration was subsequently applied to
  the local `https://dev.inbit` development database to resolve its expected
  missing-table asset-detail 500; caches were cleared and a read-only
  progression smoke check passed. Production was not accessed or migrated and
  no dependency graph was inferred.
- Supporting decisions and rollout boundaries are in
  `docs/plans/workflow-execution-dependencies-2026-09-15.md`. Existing manual
  artifacts were not edited; workflow guide revisions remain a release task.

## Workflow Run/Edit And Protected Status Implementation

- Implemented the clarified editable-run and protected-status UX. Completion is
  not an authorization lock; completed current and historical runs remain
  editable for users with the explicit workflow edit ability.
- Identified a correctness risk before allowing multiple editable runs:
  progression selects the newest-started run while readiness can promote an
  older run after its completion timestamp changes. Recommended a single
  newest-started current-run rule and stable completion timestamps.
- Added separate explicit capabilities for shared run editing, starting
  a new run, dependency override, profile execution level, sale transition,
  and sale-readiness override. Role labels should map to capabilities rather
  than hard-coded group names, and restricted results must be protected on
  update as well as start.
- Added compact visible rows for the full process, a Required-workflow
  Info summary including prerequisite closure, and one cancel/confirm status
  modal. The existing reload/resubmit flow evaluates workflow and component
  warnings sequentially and can lose clear acknowledgement context.
- The detailed findings and implementation boundaries are recorded in
  `docs/plans/workflow-execution-dependencies-2026-09-15.md`.
- Updated the concrete implementation contract at
  `docs/plans/workflow-run-status-ux-implementation-2026-09-15.md`. It covers
  searchable dependency selection, the three distinct Required concepts,
  consistent current-run selection, explicit execution/edit/override
  capabilities, an Info-tab Ready-for-Sale summary, and one transactional
  protected-status confirmation flow. Focused verification passes; production
  was not accessed or changed.
- Applied the new migration by exact path to local `snipeit_prod_work` and
  merged the production permission-group floor. This intentionally left the
  unrelated pending failed-jobs migration untouched. Schema/permission smoke
  checks and cache clearing passed; no production connection was used.
- The live development host now redirects an unauthenticated asset request to a
  healthy login page rather than returning 500. The Windows browser helper
  failed initialization twice, so authenticated visual smoke remains a manual
  check; feature coverage verifies the authenticated asset-detail render.
- Final guarded verification passed 157 focused PHP tests / 872 assertions,
  Blade compilation, the production frontend build, four Node checks, scoped
  PHPStan, and diff whitespace validation.
- Fixed a follow-up invisible-input blocker on the hardware page: nested status
  and workflow dialogs are now moved to `document.body` so Bootstrap's backdrop
  cannot sit above them. Blade compilation and focused asset/status UI coverage
  passed (23 tests, 132 assertions). The Windows inspection helper continued to
  fail at kernel initialization, leaving a hard-reload/manual browser smoke.
