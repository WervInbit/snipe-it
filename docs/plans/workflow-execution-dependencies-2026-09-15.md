# Workflow Execution Dependencies Investigation

Date: 2026-09-15

Status: implemented in the working tree after the investigation. The additive
migration was applied to the local `https://dev.inbit` development database on
2026-09-15 after the unmigrated schema caused an asset-detail 500. Production
has not been migrated, and no dependency graph has been inferred or enabled.

## Executive Conclusion

The requested behavior is not present today. Workflow profiles and their items
have display order, but neither order is an execution rule. Every active,
category-compatible profile is offered in an enabled selector, and every valid
start request creates another run. The web start path has no prerequisite,
existing-open-run, or completed-rerun check. The agent report path is a second
run-creation path with the same gap.

Dependencies should be explicit profile-to-profile relationships, not inferred
from `display_order`. A single progression service should calculate each
applicable profile's state for one asset and enforce the same decision in the
web controller and agent endpoint. The operator UI should render one numbered
process list with every applicable profile visible. Locked entries remain
readable but visually muted, explain which prerequisite is missing, and have no
normal start action.

An override must be a server-authorized, explicitly confirmed, audited action.
It should permit starting out of order; it should not silently mark the missing
prerequisite complete or make the overall chain ready for sale.

## Terminology And Scope

This codebase uses two nested concepts:

- A `WorkflowProfile` is the selectable workflow, such as Standard Diagnostics
  or Pre-Sale Check.
- A `WorkflowProfileItem` is an ordered result/check inside that workflow.

The requested cross-workflow dependency belongs between workflow profiles.
Item `sort_order` should continue to control the order inside a run. Component
lifecycle controllers and legacy `AssetTest` repeat operations also use
workflow-like language, but they are separate features and should not be pulled
into this change accidentally.

## Current Behavior

### Profile And Item Ordering

- `workflow_profiles.display_order` exists and is editable as a non-unique
  integer.
- Profile queries order the default profile first, then `display_order`, name,
  and ID. Therefore the displayed order is not always raw `display_order`.
- `workflow_profile_items.sort_order` is persisted, supports drag-and-drop
  administration, and is copied to result snapshots when a run starts.
- Neither value is consulted to decide whether a workflow or item may execute.

### Starting A Workflow

`TestRunController::store` currently checks only:

1. `tests.execute` and asset-view authorization;
2. a selected active profile that is compatible with the asset category;
3. required model specification values;
4. whether the profile has at least one applicable item.

It then unconditionally creates a new `workflow_runs` row and its result rows.
It does not inspect earlier profiles, earlier runs of the same profile, or other
unfinished runs. Two users can also pass the checks concurrently and create
duplicate runs.

The current operator manual already says to start once and continue the correct
unfinished run, but that is guidance only. The application does not enforce it.

### Operator Surfaces

The start selector is duplicated in three places:

- the asset Tests tab;
- the all-workflows/history page;
- the empty active-workflow page.

All queried profiles are enabled options. The all-workflows page primarily
lists historical runs newest first; it does not present one current process row
per profile. A progression list must be shared across all three surfaces to
avoid different lock states and actions.

### Completion Is Not A Reliable Timestamp

New result rows start with status `nvt`. The active page autosaves each result,
note, and photo. Every successful partial update sets `finished_at`, including a
note-only or photo-only update and the first changed result. The full update
path also sets it without verifying that all results were executed.

Consequently, `finished_at` currently means "the run was saved" rather than
"the workflow was fully executed." Dependency enforcement must not use this
column as it behaves today.

`WorkflowReadinessService` performs a stronger, separate sale-readiness check:

- it selects the newest run for each blocking profile;
- requires a non-null `finished_at`;
- requires the current model and 64-character context hash to match;
- requires exactly one current result for every required applicable item;
- requires every required item to pass.

This is why a partially saved run does not incorrectly make an asset ready for
sale, despite its premature `finished_at`. Optional items do not block current
readiness. A newer unfinished run intentionally masks an older passing run.

### Required And Not-Applicable Semantics

There are two issues to settle before "all steps executed" can be implemented:

- `nvt` is currently both the initial/open value and the user-visible N.v.t.
  concept. The active UI also uses it when a selected status is toggled off.
  The database cannot distinguish pending from explicitly not applicable.
- `workflow_profile_items.is_required` is editable, but run creation and current
  readiness use the underlying workflow item's `is_required` value. The
  per-profile required flag is therefore not authoritative at execution time.

These ambiguities should be resolved rather than embedded in dependency rules.

### Agent Reports

`AgentReportController` can create a completed workflow run directly. It uses a
global bearer token, may attach a configured user for audit attribution, and
does not apply `tests.execute` or any prerequisite permission to that user. It
validates required results only for sale-blocking profiles, then creates another
run without checking dependencies or repeats.

The server must apply progression rules to this path too. A browser-only lock
would be bypassable and would produce inconsistent history.

### Current Permissions And Audit

- `tests.execute` permits starting runs.
- `tests.delete` permits deleting runs.
- `workflows.*` permissions manage workflow configuration.
- There is no dependency/repeat override permission.
- Run and result field changes are written to `workflow_audits`, but no
  structured override reason or prerequisite snapshot exists.

### Current Seeded Shape

The source seeder creates four profiles in this display order:

1. Standard Diagnostics
2. Pre-Sale Check
3. Cleaning
4. Shipping Laptop

The earlier read-only development/rehearsal review found the same four active
profiles. Production records have also reported more profiles at other
checkpoints, so rollout must use the actual target configuration rather than
assuming that the current seeder is the complete operational process.

The seeded order should not automatically become a dependency chain. For
example, placing Pre-Sale before Cleaning may be presentation history rather
than the intended business sequence.

The owner subsequently confirmed that the current production profile list is:

1. Laptop wipen
2. Windows installeren en updaten
3. Standard Diagnostics
4. Cleaning
5. Pre-Sale Check
6. Shipping Laptop

This list is recorded for rollout verification only. No dependency edges are
created automatically; the owner will configure and test the graph manually.

## Recommended Domain Model

### Explicit Dependency Configuration

Add an additive `workflow_profile_dependencies` table with at least:

- `workflow_profile_id` - the workflow being guarded;
- `prerequisite_workflow_profile_id` - the required earlier workflow;
- timestamps;
- a unique pair and indexes for both directions.

Use self-referencing foreign keys and reject self-dependencies and cycles in
application validation. Prefer preventing deletion of a referenced profile
until the administrator deliberately removes or rewires the dependency; a
cascading delete would silently weaken execution rules.

Multiple prerequisites should use AND semantics. This supports a chain and
later branching without encoding business rules in PHP. `display_order` remains
presentation order. Dependency validation should warn or reject a prerequisite
that appears after its dependent profile because the numbered UI would
otherwise contradict the rule.

The owner clarified that a dependency always means every Required profile item
is Pass/Done. A failure-tolerant dependency setting was deliberately rejected:
it would let configuration silently bypass the warning and audit requirement.
When work must proceed despite a failure, an authorized operator uses the
confirmed, reasoned override for that specific run.

### Repeat Policy

Do not treat dependency order and repeat behavior as one switch. Add a
per-profile policy with explicit values such as:

- `allowed` - completed runs may be repeated normally;
- `override_required` - a completed workflow can be repeated only with the
  dedicated permission, warning, confirmation, and reason;
- `never` - no new run after valid completion without configuration change.

Independently of that policy, permit only one open run per asset/profile.
Starting an already-open profile should open/continue that run, not create a
second one. A completed rerun should be a distinct, clearly labelled action.

The implementation defaults existing and new profiles to
`override_required` with no dependencies. This deliberately stops an
unconfirmed repeat after the migration while leaving the operational graph
unconnected until an administrator configures it. For most operational
workflows, `override_required` is safer than `never` because retesting after
repair or a mistaken result remains possible with a recorded reason.

### Completion State

Create one shared run evaluator with at least these derived states:

- `not_started`;
- `in_progress`;
- `completed_with_issues`;
- `completed_successfully`;
- `stale` (asset/profile definition no longer matches).

Separate pending from explicitly handled outcomes. The clean long-term model is
to add a pending state for new result rows and retain an explicit not-applicable
or skipped outcome. Decide whether skipping is allowed for required items and
whether it requires a note. Existing `nvt` data cannot be backfilled perfectly
because its intent is ambiguous; legacy runs should be grandfathered under the
old interpretation or migrated with an explicitly accepted policy.

Keep autosave if desired, but set `finished_at` only when the evaluator says all
required/applicable steps are terminal. Clear it again if a result is reset to
pending. Alternatively, add an explicit Complete Workflow action. The former
preserves the current low-friction UI; the latter gives a clearer audit moment.
Either choice must replace the current "any save means finished" behavior.

### Execution Snapshots And Overrides

When a run starts, persist which configured prerequisites were evaluated, which
specific runs satisfied them, and their state at that moment. A normalized
`workflow_run_prerequisites` table remains preferable if detailed dependency
reporting or deletion protection is added later. The first implementation uses
an immutable JSON prerequisite snapshot on the run because the dependency
configuration remains normalized, the snapshot is small, and historical runs
must survive later configuration edits without adding another mutable
relationship.

Record overrides separately or alongside those snapshots with:

- guard type (`dependency` or `repeat`);
- blocking profile/run IDs and states;
- confirming user;
- required reason;
- confirmation timestamp.

Historical run rows should display an override badge with the actor and reason
to authorized audit viewers.

Add dedicated execution permissions, for example:

- `tests.override_dependencies`;
- `tests.repeat_workflows` if repeat authorization needs to differ.

Seed these only for the intended Supervisor/Admin groups. Do not infer the
right from `workflows.edit`, `tests.delete`, or a hard-coded role name.

## Recommended Progression Service

Introduce one service, for example `WorkflowProgressionService`, that receives
an asset and returns every applicable profile with:

- sequential display position;
- latest/open run;
- derived completion state;
- unmet prerequisite details;
- normal start/continue/repeat eligibility;
- whether the current user may request an override.

The service should batch-load profiles, dependencies, runs, and results. The
current readiness service queries the newest run inside a profile loop; copying
that pattern into three UI surfaces would add avoidable N+1 work.

Use the same evaluator from sale readiness, but keep policies distinct:

- execution dependencies decide whether a new run may start;
- sale readiness decides whether the asset may enter a sale lifecycle state.

An early-start override changes only the first decision. The missing
prerequisite remains visible and the overall process/readiness remains
incomplete until it is genuinely satisfied.

Every write path must re-evaluate inside a transaction. Lock a stable per-asset
row while checking and creating a run so concurrent web or agent requests
cannot both create the "only" open run. UI disabling is advisory; the server is
authoritative.

## Recommended Operator Experience

Replace the profile selector as the primary workflow navigation with a shared
numbered list. The history list remains separate and continues to show actual
runs newest first.

Example states:

1. `1 Standard Diagnostics` - Complete, with date and user.
2. `2 Cleaning` - Available, with Start action.
3. `3 Pre-Sale Check` - Locked; waiting for #2 Cleaning.
4. `4 Shipping Laptop` - Locked; waiting for #3 Pre-Sale Check.

All applicable profiles stay visible. A locked row should use more than low
opacity: retain readable contrast, include a lock icon and text reason, and
expose the reason to assistive technology. A disabled control alone does not
communicate why it is disabled.

For a user with override permission, a locked row can offer `Start anyway`.
That action opens a warning modal listing the exact unmet dependencies and the
consequence. Require a reason and an explicit confirmation. The normal POST
must fail closed; a crafted request without permission, confirmation, or reason
must not create a run.

For the agent API, return a structured conflict response with blockers. Do not
allow a configured agent identity to bypass merely because a bearer token is
valid. If automated overrides are ever required, give the service identity an
explicit permission and require a machine-readable reason; default to no
override.

## Consequences And Pitfalls

### Reordering And Existing Readiness

The prior `readiness_context_hash` included profile `display_order`. The
implementation removes that presentation-only field from the new versioned
hash. The additive migration snapshots each existing run's current profile
order, and validation accepts the exact legacy hash calculated with that
snapshot. Administrators can therefore reorder profiles without reopening
otherwise current work. Dependency configuration remains separately
snapshotted and does not silently rewrite historical runs.

### Applicability Can Make A Dependency Impossible

Profiles and individual items are category/component scoped. Today a profile
with configured items can appear in the selector even when none of its items is
applicable; start then fails. The progression service should classify that
profile as not applicable, not as an eternally locked prerequisite.

Administration should detect obviously incompatible category scopes. Runtime
evaluation still has to handle component-dependent applicability. Define
whether a non-applicable prerequisite is skipped/satisfied; the recommended
behavior is to exclude it from that asset's chain and show an N/A state if it is
shown at all.

### Newer Runs And Downstream State

Current readiness deliberately lets a newer unfinished rerun mask an older
passing run. A repeat of an early workflow can therefore make the asset no
longer ready even after later workflows completed. That is safe but may surprise
operators. The UI should warn that starting a rerun can reopen downstream work,
and the process owner must decide whether later runs become stale, remain valid,
or require explicit revalidation.

### Deleting Or Editing History

Supervisors/admins can currently delete runs, and authorized users can edit old
results. Deleting or changing a run that satisfied a downstream dependency can
invalidate the chain. Snapshot links should make that impact discoverable.
Consider refusing deletion while a downstream run references it unless a
separate confirmed override records the consequence.

### Definition Changes

Current readiness invalidates runs after relevant model, component, profile, or
item definition changes. Dependencies should respect `stale` rather than
counting such a run as complete. Profile dependency edits should not silently
rewrite history; apply new rules to future starts and make the current-versus-
snapshot distinction visible.

### Configuration Graph Safety

Reject direct and indirect cycles. Also test duplicate order values, branches,
inactive prerequisites, deleted profiles, empty profiles, and profiles that are
not applicable to a given asset. For a modest profile count, cycle detection in
the application is sufficient; the database unique pair prevents duplicates.

### Agent And Other Entry Points

The web controller and agent report controller are both run creators. Future
imports or jobs must use the same progression service. Keep legacy asset-test
repeat routes and component lifecycle workflows out of scope unless the owner
explicitly includes them.

### Documentation And Evidence

The change materially alters WF-01 and WF-02 behavior and screenshots. Existing
accepted/manual PDFs must remain byte-for-byte unchanged; new guide versions,
evidence captures, review records, registry entries, and rendered QA are needed.
README, fork notes, permissions documentation, seed behavior, API documentation,
and migration/rollback notes also need updates when implementation begins.

## Suggested Implementation Sequence

1. Agree the operational profile graph, required outcome per dependency,
   repeat policy, override roles, and pending/N/A semantics.
2. Add additive schema, models, graph validation, permissions, and factories
   with current-compatible defaults. Do not enable production dependencies yet.
3. Build and characterize the shared run evaluator and progression service.
4. Correct completion semantics and enforce one open run per asset/profile,
   including concurrent web/API starts.
5. Replace the three selectors with one shared numbered status list and a
   separate history section.
6. Add server-enforced, reasoned override confirmation and audit display.
7. Apply the same guard to agent reports and return structured blocker errors.
8. Run a warning-only rehearsal against a production clone, review which assets
   would be locked or reopened, then configure/enforce dependencies deliberately.
9. Update manuals and release documentation, and qualify SQLite plus the
   supported MariaDB image before deployment.

## Minimum Test Matrix

- dependency graph create/update validation, including cycles and deletion;
- required Pass/Done, failure, pending, and override outcomes;
- all applicable result states, pending/N/A, optional and failed results;
- all profiles visible, sequentially numbered, and locked reasons rendered;
- normal start, continue existing open run, completed rerun, and repeat policy;
- unauthorized forged override, authorized unconfirmed override, confirmed
  override with reason, and audit snapshot;
- category/component applicability and no-applicable-item profiles;
- profile/item/asset/component changes that make a run stale;
- two simultaneous start requests for the same asset/profile;
- agent report blocked/allowed cases and no partial persistence;
- run edit/delete effects on downstream dependency snapshots;
- sale-readiness behavior with out-of-order overrides;
- additive migration and rollback on guarded SQLite and disposable MariaDB;
- mobile layout, keyboard navigation, readable locked states, and screen-reader
  labels.

## Implemented Decisions And Remaining Configuration

1. The actual operational dependency graph remains a configuration decision.
   Existing profiles are intentionally left disconnected so the seeded display
   order is not mistaken for business policy. Administrators can connect or
   rewire them after migration; applicable prerequisites are numbered before
   their dependents even when display-order values disagree.
2. Every dependency requires each Required profile item to be Pass/Done. A
   failure remains blocked unless an authorized operator confirms and explains
   an override for that run.
3. Optional results do not gate the next workflow. `nvt` remains the legacy
   pending value for Required results. A profile with no Required results falls
   back to all results so it cannot complete immediately when started. A
   distinct explicit N/A/skipped state remains recommended future work.
4. Completion remains automatic: `finished_at` is set when every gating result
   is answered and cleared when a gating result returns to pending.
5. Repeat behavior is editable per profile. The safe default is confirmed
   override; `allowed` and `never` are also available.
6. `tests.override_dependencies` covers early starts and guarded repeats.
   Admins pass the gate; the production Supervisor/Admin defaults include the
   permission. Confirmation and a non-empty reason are always required.
7. An early-start override permits only that new run. It does not complete or
   waive the missing prerequisite, and its blockers, actor, time, and reason are
   snapshotted. Existing open runs are grandfathered if dependencies are edited
   after they started, avoiding stranded work.
8. A later dependency edit applies to future starts and does not rewrite or
   retroactively invalidate completed run history. Existing readiness behavior
   for definition changes and newer reruns remains unchanged.
9. Profile lists use configured display order without promoting the default
   profile. The settings list supports persistent mouse/touch drag ordering;
   default agent selection is handled separately. Reordering does not stale
   either new or legacy run hashes.

## Advice

Implement explicit dependencies and repeat policy together, but ship them in a
non-enforcing configuration first. The largest risks are not the lock styling;
they are ambiguous completion, the unguarded agent entry point, concurrent
duplicate starts, and invalidating current ready assets when profile order or
hash inputs change. The implementation addresses those code paths; enable the
manually configured graph profile by profile after a clone/rehearsal report
shows its impact.

## Follow-up Run And Status UX Investigation

The owner clarified that an answered workflow run must remain editable. Its
calculated completion state may change automatically, but completion must never
act as a lock or finalization action. Leaving the page, answering the last
Required item, or later finding an omission must not prevent an authorized user
from reopening the same run. The progression row should therefore keep an
explicit `Edit run` action for the current run in every state. Historical runs
should remain editable as well and be labelled as historical when a newer run
exists.

Starting another run is a separate privileged action. The safe UI is `Start
new...`, visible only to a user with an explicit repeat/guard permission, which
opens the warning/reason/confirmation modal. Ordinary executors continue or
edit the current run and cannot create a parallel history by mistake. The
current `allowed` repeat policy conflicts with that decision and should be
removed or migrated to confirmed override; `never` can remain as the stricter
profile option. A distinct permission such as `tests.start_new_run` is clearer
than using `tests.override_dependencies` for both repeats and dependency
overrides.

Before enabling editable history with multiple runs, current-run selection must
be made consistent. Progression selects by newest `started_at`/ID, while sale
readiness currently orders by `COALESCE(finished_at, created_at)`. Editing a
completed historical run can update `finished_at` and incorrectly make that old
run authoritative for sale readiness. All current-state consumers should use
the newest-started run (or an explicit current-run pointer); edits to older runs
must never promote them. `finished_at` should be a completion timestamp only:
preserve it on note/photo edits while still complete, clear it when a Required
answer is reopened, and set it when the run becomes complete again.

Current TestRun authorization also lets any asset editor update another user's
run. That happens to include every seeded operational role. Shared shift work
may justify that outcome, but it should be an explicit workflow edit capability
rather than an accidental consequence of `assets.edit`. The actor is retained
in workflow audit rows. Any future execution restriction must be enforced on
both run creation and every result edit, or an operator could edit a
Senior/Supervisor run after being prevented from starting it.

Role-restricted work should be configured by capability, not group name. Prefer
profile-level execution capabilities for Operator, Senior, and Supervisor
workflows. All users should see the same numbered process and the required-role
badge, while users without the capability get a disabled action. Split work
into separate profiles when responsibility changes; per-item permissions inside
one run should be added only if a real workflow cannot be split cleanly.

For scale, keep every numbered row visible but make it a compact one-line
tracker: state icon, name, role badge, short blocker, and one primary action.
Put descriptions and detailed blockers behind row expansion, and highlight the
current/next actionable row. The asset Info tab should show a smaller read-only
`Required workflows` summary. It should include profiles marked
`blocks_sale_readiness` plus their transitive prerequisite closure, otherwise a
non-blocking prerequisite such as Cleaning can fail after Pre-Sale Check while
the asset still appears ready.

The current detail-page status selector submits immediately. When required
workflow or component issues exist, the controller rejects the first request,
reloads the page with orange session messages and hidden acknowledgement fields,
and expects another submission. This is easy to miss and handles the two
warning classes sequentially; acknowledgement state can alternate when both
classes exist. Replace it with one protected-status transition modal that shows
the old and new status and all current workflow/component issues together.
Cancel must reset the selector without a request. Confirm should submit once,
with a reason required only when overriding issues.

The server remains authoritative. A shared transition-guard service should be
used by the detail form and full edit path, return all warnings in one result,
and re-evaluate inside a transaction when Confirm is submitted. Bind the
confirmation to a hash/version of the exact warning set so a concurrent result,
component, or status change forces the modal to refresh rather than silently
acknowledging a different risk. Normal Ready for Sale/Sold transitions should
require `assets.sale_transition`; proceeding despite unmet readiness should use
a separate override capability and persist the actor, reason, target status,
and confirmed issue snapshot.

The original workflow-guard migration has already run on `dev.inbit`. Any new
execution-capability columns, permission data, or repeat-policy normalization
must therefore be delivered in a new forward-only additive migration. Editing
the already-recorded migration would leave development and fresh installs with
different schemas.
