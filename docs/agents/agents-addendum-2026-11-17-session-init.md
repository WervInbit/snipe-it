# Agent Addendum - 2026-11-17 - Weekly Status Recheck

## State

- At session start, branch `codex/legacy-workflow-attestations` remained at
  `c539b9cd18` with the complete legacy-evidence feature uncommitted.
- Fresh 2026-11-17 qualification now passes 2,290 non-LDAP SQLite tests and
  11,316 assertions. Four expected skips cover two disposable-MariaDB
  concurrency cases and two Windows environments where symbolic links cannot
  be created.
- No release commit, immutable build, remote push, or production deployment
  has occurred for this feature.

## Requested UX issues

- Phone scrolling: open and not reproduced/fixed.
- History feedback/performance: open and not fixed.
- They need separate evidence. History currently combines synchronous status
  history with an AJAX Bootstrap table. The table defines a loading message,
  while shared CSS lowers its loading overlay to `z-index: 0`; the API also
  performs a count and paged relationship-rich action-log query.
- A proper fix should first expose immediate loading/error state, then measure
  API/database timing. Mobile scroll work should capture modal/body overflow
  state on the affected phone before changing CSS globally.

## Next recommended block

1. Commit the now-qualified legacy-evidence release scope separately from the
   guide checkpoint and from the mobile/history UX investigation.
2. Open a new mobile-history UX branch.
3. Add a deterministic phone-width browser regression for tab navigation and
   scrolling, plus API timing/query-count coverage for asset History.

## Repository reconciliation

- Refreshed `origin`; `origin/master` remains `c539b9cd18`, which is also this
  branch's committed HEAD. The legacy feature remains entirely unstaged.
- The guide checkpoint was replayed onto current `origin/master` in clean
  branch `codex/guide-checkpoint-rebased`. Its four commits end at
  `49b3eee1c3`; the branch is clean and its guide verifier and audit pass.
- Keep the guide commits separate until the qualified runtime commits exist,
  then reconcile shared progress/TODO files while preserving both histories.
- The working diff passes `git diff --check`. `.dockerignore`, both example
  environment files, `docker/production/Dockerfile`, and the two maintained
  patch files have worktree hashes identical to the index; their modified
  status is line-ending/stat noise rather than content to commit.
- Commit boundaries should remain: legacy runtime/migration/UI/tests;
  release-test/line-ending cleanup; feature documentation; then shared
  checkpoint/TODO reconciliation after the guide transplant.
- Added focused coverage for agent ingestion, summary/list API behavior, API
  intake rejection, revocation authorization, and production permission
  seeding. The focused boundary passes 64 tests / 477 assertions; adjacent
  workflow/readiness/result/index coverage passes 87 tests / 406 assertions.

## Qualification and reconciliation outcome

- Docker Desktop remains unavailable because its stale `dockerInference`
  endpoint prevents startup. A verified portable PHP 8.3.36 runtime and
  Composer 2.11.2 were therefore used from the user temp directory without a
  system install. PHPUnit still ran with explicit `testing`, `sqlite`, and
  `:memory:` process values and the executable database guard enabled.
- Fixed two gaps found by the new assertions: stale attestations now retain a
  specific `legacy_stale` API issue instead of collapsing to a generic missing
  profile, and component-scoped attestations hash only relevant component
  definitions/categories rather than unrelated laptop components.
- Legacy revocation now locks the asset before the attestation so concurrent
  run creation and evidence revocation serialize on the same asset boundary.
- Added Dutch and English labels for the asset-list legacy-evidence filter and
  evidence date/status text.
- The first full portable-Windows run exposed one unrelated guard-test path
  spelling mismatch (DOS short path versus canonical long path). The test now
  asserts the canonical path and releases its PDO handle before cleanup. The
  isolated guard test and complete rerun are green.
- PHP/Blade parser: 41 changed files parsed, zero failures. `git diff --check`
  has no errors. Ephemeral Passport keys and generated test uploads were
  removed after the run.
