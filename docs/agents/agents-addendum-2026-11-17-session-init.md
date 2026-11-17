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
## Manual Guide Workstream Notes

## Operator Manual Status Review - November17

- User-authorized scope: status report for manuals and the flat shared delivery.
- Actual checkout: C:/dev/snipe-it-fork, master a766abedee, shared dirty changes.
  No application runtime/image/database target is needed or assumed for this
  file-only status check; do not treat dated operating evidence as freshly verified.
- Sources: developer checkpoint/TODO, current manual HANDOFF, November12 progress,
  fork notes and the shared folder's register/qualification/sealing evidence.
- Verification: read-only folder/registered-file/checksum and archive identity;
  preserve sealed output and any independent manual changes. No Computer Use.
- Unknowns: NAS copy/location, fresh-device/fonts, actual operator/A4 acceptance,
  and the previously documented application-target/content gaps.

### Verified Results

- 25 PDF files,25 .af files,68 registered pages and583 total delivery files.
  All582 recorded checksums match, with no missing/changed artifact.
- ZIP remains383665275 bytes and retains the November12 SHA256
  d62409d7bde326951ea241a6ff4b0e9c5071967d1197ecf4242f7966aab5119a.
- Installed reusable skill matches its four maintained files exactly.
- No new manual generation/content changes or Computer Use. NAS copy/location
  not verified; do not overwrite a potentially newer NAS master from this local
  snapshot. Existing reader/A4/fresh-device/acceptance and application gaps remain.

### Owner-Reported NAS Copy

- Owner confirms copying the delivered set to the NAS. Future work must compare
  that shared copy before overwriting from the local delivery snapshot. NAS path
  and file integrity remain unverified here. No artifact or NAS operation.

## Manual Repository Alignment - November17

- User-authorized follow-up: align repository status and maintenance sources
  with the finished native/PDF delivery and owner-reported NAS copy.
- Backlog: START-05/NEXT-08. Checkout remains C:/dev/snipe-it-fork,
  master a766abedee, with unrelated shared application changes preserved.
- Repository documentation/metadata only. No runtime/database target is
  selected or implied; no GUI work, conversion or NAS access is scheduled.
- Capture a lightweight last-delivered register, reconcile active documentation
  and distinguish completed production from remaining release/reader evidence.
  Preserve dated decisions and per-version history without treating old pending
  statements as current. Verify metadata, links and scoped changes afterward.
- NAS location/integrity, newer shared edits and exact-version acceptance remain
  unknown. The sealed local output is comparison evidence, not overwrite authority.

### Alignment Outcome And Verification

- Current manual entry points, all 25 specification headers, root navigation,
  checkpoint and backlog agree on completed PDF/native production and packaging.
  Former project/registry entries are preserved as history; no stale planning
  hold or conversion count controls current work.
- Added current-set.json with all exact delivered versions, roles, pages,
  relative filenames and hashes. NAS copy remains owner-reported, with no known
  path/integrity check. Native masters may contain newer human edits; the
  maintenance contract now requires comparison and synchronized native/PDF/
  specification/review/register updates without overwriting them from a generator.
- Verification passed: 25 exact register/specification matches, all prior
  specification bodies and both former indexes preserved, 130 current links,
  170 image placements, all 582 sealed file hashes and unchanged archive hash.
  Scoped working-file comparison found no unrelated modifications; HEAD stays
  a766abedee and whitespace checks pass. Local report:
  output/manuals/repository-alignment-2026-11-17/verification.json.
- Remaining release/app, USR-02/AST-03, fresh-device/Arial, operator/A4/labels and
  exact acceptance evidence is explicit. No manual content/output, NAS file,
  application/runtime/database/printer/NUC state, commit or push changed.
  Application tests skipped: documentation/metadata-only scope.

## Scoped Manual Publication Commits - November17

- Owner accepts the recommended scoped manual commits. Starting HEAD remains
  a766abedee on the dirty shared master checkout; the index is empty.
- Preserve manual methods/specifications/skill and useful production tooling,
  then current delivery/handoff metadata and only relevant shared-file sections.
  Application/printer work, attachments, scratch files, caches and generated
  delivery/ZIP files remain outside these commits.
- Verify source/dependency scope, external evidence availability and staged
  diffs. No push, pull/rebase, GUI, runtime/database or NAS write is authorized
  by this publication step. Retain uncommitted work belonging to other tasks.

### Source Commit And Validation

- f51593d906 commits156 manual source/tooling files. Current native/PDF bytes,
  sealed delivery, historical binary rounds and unrelated app/printer changes
  are excluded. The handoff commit uses selective shared-file staging, keeping
  the newer global backlog/onboarding/application reconciliation in the worktree.
- Exact1133-file historical archive is preserved at
  output/manuals/Manual-review-history-2026-11-17.zip,163554075 bytes, SHA256
  062078b6ce709a788006d59b59c0f3e851c1eea4b8f57746fe1ddcf7bbaa330f.
  The source manifest records every file/hash; no NAS transfer is claimed.
- Checks pass: skill structure, selected Python parsing,26 JavaScript syntax
  checks, shared primitives/registry,25 offline SDK fault cases, six real index
  filter cases and current AST-03 v20 source/PDF text/A4 check. No native app
  input or new guide version is involved.
- Legacy package check fails on three pre-existing unlisted CAT draft PDFs.
  All three and the checker match starting HEAD; retain the evidence and record
  this drift independently. Current delivery integrity remains unchanged.
- No push or synchronization of the divergent branch, runtime/database change,
  printer access or NAS write. Other tasks remain uncommitted and preserved.
- Final handoff verification passes for17 scoped paths, all582 delivery hashes
  and the unchanged ZIP, with zero outside-scope working modifications. Exact
  archived review-round directories are ignored to prevent accidental staging;
  their original files and the verified history archive remain preserved.

## Guide Checkpoint Rebase And Clean-Clone Verification - November17

- User reports the manual author has finished committing and requests repository
  reconciliation. The original root checkout remains dirty and divergent, so
  integration uses clean worktree `guide-checkpoint-rebase` and branch
  `codex/guide-checkpoint-rebased` from `origin/master` `c539b9cd18`.
- Replayed the three manual commits as `0c76e52e18`, `c894800423` and
  `19b9c81996`. Conflicts were limited to shared progress/backlog/fork-note and
  October1 addendum records; resolutions retain both product and manual history.
  No root working file, remote branch, runtime, database, NAS or device changed.

### Clean-Clone Findings And Repair

- `npm test` initially failed because the strict Markdown checker assumed the
  ignored1133-file review archive and generated output were present. The
  committed review-artifact manifest covers201 missing link occurrences; a new
  exact local-output manifest covers the remaining generated delivery targets.
  Undeclared missing links still fail. The current decision log now points a
  fresh-clone reader to source availability instead of a dead local link.
- The next failure confirmed the older recorded drift: three tracked CAT drafts
  were outside the package manifest. They are preserved and registered as
  unaccepted CAT-00 v7/v8 and CAT-01 v4 artifacts, producing29 draft PDFs /88
  pages with exact hashes. No frozen PDF byte or acceptance status changed.
- Windows `text=auto` conversion changed the worktree bytes of two hash-locked
  SVG baselines. The path-specific `-text` rule keeps repository bytes stable;
  both manifest hashes now verify.
- Sharp was updated from0.35.3 to0.35.5 because the first clean install reported
  one high-severity direct dependency advisory. The patched exact version has a
  clean `npm audit` result.
- Final evidence: `npm test` passes with25 registry guides,1549 checked relative
  links,251 explicitly unavailable historical/local links,119 evidence files,
  9 accepted PDFs/11 pages,29 unaccepted PDFs/88 pages,2 locked baselines and79
  active scripts. Remaining user/release/physical acceptance is unchanged.

## Combined Repository Reconciliation

- The qualified legacy feature is committed in three scoped changes:
  `f307df415d`, `da9a13d3bb`, and `2e48e63517`. The clean guide checkpoint was
  then transplanted in four commits ending at `057296d9f6`.
- Conflict resolution preserved the legacy, mobile/history, printer, CUPS, and
  manual workstreams together. One stale guide-side RAM-speed investigation
  entry was omitted because the repository's production record proves that
  correction complete.
- The combined guide gate initially exposed worktree-only CRLF conversion in
  the two locked baseline SVGs. Refreshing those files from their identical Git
  blobs under the committed `-text` policy restored the expected checksums; no
  tracked content changed.
- Final combined verification: `npm test` passes the registry and package
  verifier, and the guide package's `npm audit --audit-level=high` reports zero
  vulnerabilities.
  The previously completed PHP evidence remains 2,290 tests / 11,316
  assertions with four expected environment skips and zero failures.
- Worktree and index are clean. No push, immutable build, production deploy,
  database write, NAS change, printer/CUPS operation, or NUC access occurred.

## Application Root Dependency Audit

- A final audit at the repository root intentionally checked the application
  lock separately from `scripts/manuals`. It reports 34 total advisories: 3
  critical, 11 high, 15 moderate, and 5 low. With development dependencies
  omitted, the lock still reports 10: 1 critical, 2 high, and 7 moderate.
- The production-classified set includes a critical DOMPurify path through the
  optional jsPDF dependency and high findings in `probe-image-size` and
  `source-map-js`; npm indicates non-major fixes are available. Bootstrap 3 and
  AdminLTE 2 findings require semver-major replacements, while this fork also
  carries a targeted Bootstrap 3 security patch script.
- The audit classifies lockfile dependencies; it does not prove every package
  is reachable in the compiled browser assets. Treat the result as a release
  blocker pending reachability/bundle review, dependency updates, asset rebuild,
  Node/browser/export regression checks, and a repeated audit. No `npm audit
  fix`, forced major update, package file, or compiled asset change was made.
