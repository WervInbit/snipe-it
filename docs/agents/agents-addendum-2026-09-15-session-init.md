# Agent Addendum - 2026-09-15 Session Init

## Manuals Focus Continuation

- Read the operator-guide README, continuation handoff, authoritative registry,
  owner-review readiness/TODO records, root TODO entries, and the portable
  accepted-guide package README.
- Treat `resources/manuals/operator-guides/accepted-guides-v1/` as the frozen
  current accepted package: 16 guide codes / 27 pages. Acceptance is exact-
  version internal-review status, not third-party approval.
- Preserve accepted artifacts byte-for-byte. Any visible correction receives a
  new version, focused proof, review record, registry/specification update, and
  rendered PDF QA.
- Open work includes other-user and physical A4 tracing; draft review for
  AST-05, HELP-01, USR-03, CAT-02, CAT-03, and CAT-04; careful handling of newer
  unaccepted WF-02/CMP-01 proposals; and planned USR-05, CAT-05, and CAT-06.
- The root README's statement that the guides are not yet a versioned V1 manual
  now lags the accepted package and should be reconciled in a manuals
  documentation pass.

## Manuals Batch Outcome

Later owner feedback: all three v1 drafts require content alignment rework.
The [content audit](../manuals/operator-guides/reviews/USR-05-CAT-05-CAT-06-content-audit-2026-09-15.md)
records eleven guide-specific findings, shared corrections, exact comparison
versions, and v2 briefs. All ten v1 pages were inspected; application behavior
was checked read-only. The original PDFs and their hashes remain comparison
artifacts. No v2 or operational acceptance test was produced by the audit.

- Created unaccepted USR-05 v1 (2 pages), CAT-05 v1 (5 branching pages), and
  verification-only CAT-06 v1 (3 pages), with exact review records and portable
  draft-manifest entries.
- Captured eight new read-only/unsaved UI evidence sources without submitting
  forms or changing application data; accepted guide bytes remain unchanged.
- Added `generate-planned-guide-drafts.mjs`, updated the guide specs, layouts,
  registry, evidence catalog, inventory, handoff, decisions, README, and TODO.
- Automated component/geometry/A4/page/title/text checks and visual inspection
  passed. Package verification passed against a clean manifest-only draft
  mirror; the live draft root retains the three known unlisted historical CAT
  files tracked in `TODO.md`. Operational acceptance tests and policy approvals
  remain open as recorded in each guide review.

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
## Manuals V2 Revision Outcome

- Revised USR-05, CAT-05 and CAT-06 from the owner-requested content audit.
  New v2 PDFs contain 3, 8 and 3 pages, respectively; all remain unaccepted.
- Corrected permissions, branch/save/result sequencing, affected-override
  warnings, actual Enum/lifecycle controls and a worked HP source check.
- Retained all v1 and accepted PDF bytes. Updated specifications, current
  index/registry metadata, reviews, manifests and version-aware checks.
- Eight real read-only captures were added. The only fixture was an owned
  memberless local group; five resolver cases passed and the fixture was
  removed. No user membership or catalogue content was changed.
- All 14 final PDF pages passed independent raster inspection; metadata,
  text, source-link, artifact-hash, shared component tests and helper lint pass.
  The manifest-only package mirror passes at 119 evidence / 26 drafts / 69
  pages. Three pre-existing unlisted draft PDFs were preserved.
- No application suite, destructive catalogue trial or deployment was run.
  Controlled operator trials, source policy and exact-version acceptance are
  still required. Existing Docker services and unrelated concurrent code work
  were left alone. See
  [v2 review](../manuals/operator-guides/reviews/USR-05-CAT-05-CAT-06-v2-2026-09-15.md).

## Manuals Owner Readability Follow-up

- Updated the current USR-05/CAT-05/CAT-06 v2 drafts in response to direct
  owner review. USR-05 now names the left-sidebar route to Groepen and spells
  out conflicting group/direct-user permission precedence.
- CAT-05 is now titled `Bestaande catalogus aanpassen` and begins with a
  screen-based task finder. CAT-06 is now titled `Productgegevens controleren`
  and explicitly serves only as an evidence check before CAT-01/CAT-02.
- Regenerated and visually inspected all 14 A4 pages; focused PDF, registry,
  hash, link and package validation passed. Historical v1 and accepted PDFs
  were preserved; no application or data mutation was performed.

## Portable Current Guide Kit

- Exported all 25 active current guide PDFs / 61 pages to
  `output/manuals/current-guide-kit-2026-09-15/`, using the authoritative
  22-guide v4 selection plus USR-05/CAT-05/CAT-06 v2.
- Added exact final HTML snapshots, full manuals tooling, evidence, baselines,
  guide documentation, correction inputs, provenance manifests, a guide map,
  checksums and context-free agent instructions. Historical rendered proof
  debris was excluded; the inputs needed to reproduce current correction
  layers remain present.
- Standalone verification from inside the kit passed all 400 checksum entries,
  25 PDF hashes and byte counts, 25 HTML hashes, required path checks and all
  61 `pdfinfo` page counts. No current artifact or approval state changed.
