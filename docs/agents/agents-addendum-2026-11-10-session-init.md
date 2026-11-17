# Agent Addendum - 2026-11-10 - Legacy Workflow Evidence

## Session boundary

- Source: clean managed worktree
  `C:\Users\Gebruiker\.codex\worktrees\legacy-workflow-attestations\snipe-it-fork`.
- Branch: `codex/legacy-workflow-attestations` from `origin/master`
  `c539b9cd18`.
- Production is out of scope until local implementation and qualification are
  complete. No production host, database, CUPS node, or printer was changed.

## Implemented contract

- Only the single-asset web create form accepts legacy workflow evidence.
- The fieldset is collapsed by default, model-dependent, and selects exact
  profile items with one required source/reason and optional historical date.
- Asset/evidence persistence is atomic. No `workflow_runs` or
  `workflow_results` rows are created at intake.
- Per-item hashes bind evidence to model/model-number, requirement content,
  expected values, and relevant component context without invalidating an
  existing item merely because an unrelated item is added.
- One resolver owns readiness, progression, mixed runs, summaries, and visible
  legacy/mixed/pending/failure states. Real results supersede legacy evidence;
  revocation is non-destructive and audited.
- QR artifact generation is after the database commit. Printing remains the
  existing separate explicit post-create action.

## Verification checkpoint

- Docker preflight: `APP_ENV=testing`, `DB_CONNECTION=sqlite`,
  `DB_DATABASE=:memory:`.
- PHP syntax: all changed PHP files pass `php -l`.
- Blade compilation: `php artisan view:cache --env=testing` passes.
- New focused suite initially passed 7/8; the only failure was a missing local
  Mix manifest while rendering a 403 page. After the local asset build and
  JSON authorization assertion, the endpoint test passed.
- New domain/create tests cover legacy-only readiness, dependency satisfaction,
  mixed runs, unrelated-item stability, requirement staleness, permanent
  supersession, revocation materialization, authorization, atomic web creation,
  and rollback of stale selections.
- Adjacent workflow, agent, readiness, partial-update, and latest-summary tests
  were run. Two compatibility differences were fixed: profile-less historical
  runs keep result-only completion, and a profile with no run/evidence retains
  the existing profile-level missing API payload.
- Final guarded non-LDAP SQLite gate: `2,281` passed, `11,251` assertions,
  zero failures, and 2 expected skips for tests that deliberately require the
  disposable external MariaDB concurrency target.
- The single-command gate is reliable again: the nonexistent maintenance API
  suite entry was removed, Windows LF-sensitive release fixtures have explicit
  LF policy, cached Laravel configuration stayed cleared, and the stale
  full-edit form assertion now matches the intentional `status_change_note`
  field.
- Local web-stack preflight was `APP_ENV=local`, `DB_CONNECTION=mysql`,
  `DB_DATABASE=snipeit`. The local schema was brought forward and foundation
  plus demo role/account seeders were applied; no destructive demo asset seeder
  was run.
- Authenticated Senior Refurbisher qualification created asset 16
  (`LOCAL-LEGACY-20261110`) with two legacy attestations, no intake run/results,
  and the expected actor/source snapshots. Starting the profile produced an
  open mixed run with two evidence snapshots and results only for uncovered
  applicable items. Revoking attestation 1 left one active attestation,
  retained the reason/audits, and added one pending `nvt` row to the open run.
- Installed Chrome executed the real intake JavaScript: collapsed/open state,
  AJAX option loading (3 profiles/22 items), per-profile select/clear controls,
  and stale selection/source/date clearing on model change all passed. The
  desktop control plugin itself could not initialize because its kernel-assets
  path was missing; no temporary Chrome state was retained.

## Release boundary

1. Keep 25x25 QR printing as a separate explicit operator action after the
   saved identity is checked. Intake itself produced no print job.
2. Prepare a release document with backup, additive migration, permission
   seeder, smoke checks, and rollback limits before any production change.
3. Build and qualify the immutable release image before requesting production
   deployment approval.
## Manual Guide Workstream Notes

This scoped committed view preserves the manual sections. Other
application/printer session notes remain in the shared working file
for their owning task and are not published by the manual commits.

## Guide Progress Review And Permission Clarification

- Backlog START-05/NEXT-08. Owner asks for progress first and says the laptop is
  in use. Earlier November5 idle-device instruction was intended as permission
  to use Affinity. That historical scope uncertainty is resolved; no fresh
  desktop operation or NUC access was performed for this progress review.
- Actual checkout C:/dev/snipe-it-fork, HEAD a766abedeeaddd0d5e0452c3dc0bc48749993099.
  Shared dirty documentation preserved. No branch, staging, commit or rollback.
- Read checkpoint/backlog/recent progress/fork notes and current guide handoff.
  Current preparation is25 guide sources/68 pages,82 full original images.
  Portable-v2 ZIP exists at38366997 bytes and rehashes to its recorded SHA256
  9c930a4ca47206e0c6a62fac0ce11ed7ad9bb767707db19814be0edfd0397345.
- Native file discovery still identifies only AST-03 guide variants. They prove
  native save/reopen/export for one pilot, not complete text/style/image/vector/
  master editing or portable native resources. Other24 native conversions,
  shared template, final native-derived PDF set and release review remain open.
- Handoff/TODO now distinguish the resolved old authorization from the current
  in-use device. Historical November5 reports/artifacts remain unchanged.
- No app/runtime/database target used; no GUI/app, printer or deployment changes.
  File/status verification was appropriate; no application test suite run.

## Review Access Repair

- REVIEW.html local link failed for the owner. File/images are intact. Started
  a temporary loopback-only static preview for the exact portable guide folder,
  not the repository. No directory listing, app control, network/firewall change
  or source rewrite. Automatically expires after12 hours.
- Actual HTTP checks: index200,25 sections and68 available image resources.
  Codex panel-open requests returned queued; did not claim observed browser
  rendering or take desktop control. Current URL and checks are recorded in
  output/manuals/review-access-2026-11-10; all original artifacts unchanged.

## Intake And Refurbishment Print Preparation

- Owner chooses printing before Affinity. Selected9 core guides/13 A4 content
  sides; optional CMP-01/02/04 bring this to12 guides/17 content sides. AST-03
  and AST-05 are Supervisor tasks; component work uses Senior Refurbisher.
  Catalogue additions CAT-01/02/04 are conditional, outside these daily packets.
- Created2 PDFs from hash-verified current source SVGs without reauthoring.
  Each guide begins on an odd page: core18 PDF pages/9 duplex sheets, expanded
  24 pages/12 sheets. Intentional blank backs preserve independent guide sheets.
  Source text/content streams/page sizes match the intermediate guide PDFs.
- Poppler150dpi rendered all24 expanded pages. Visually inspected17 content
  pages and verified7 white blank backs. Page headers/backgrounds retained;
  no new text/image clipping observed. Final hashes and exact selection are in
  output/pdf/intake-refurbishment-2026-11-10/manifest.json; verification alongside.
- Second temporary loopback-only print index/PDFs pass HTTP200. Original guide
  preview remains available; no frozen portable source or ZIP changed. No UI
  control, Affinity operation, physical print, runtime/DB/deployment mutation.
- No app tests required. Existing source/live-target/status-note gaps, novice
  use and paper-print trials remain unqualified. This print preparation does
  not complete the25-guide native master/template/export work.

## Laptop Native Affinity Conversion Authorized

- Owner now explicitly authorizes Computer Use on this device for native guide
  creation. This supersedes the earlier laptop desktop-control exclusion for
  the current conversion work. Interruptions mean stop app input and wait for
  an explicit instruction to continue; do not resume automatically.
- Scope remains all25 prepared guide versions, native editable masters/shared
  template and exports. Preserve words, roles, original screenshots, crops and
  design direction. Prove text/style/image/vector/master editing before batch
  conversion; earlier technical A/B inputs are not qualified final masters.
- Read the Computer Use skill, guidance, API and confirmation rules. Connected
  Document Control currently reports no sessions. Affinity is present/running
  on this laptop. No NUC access, security-setting toggle or other app work.
- Saved genuine AST-03 text-defaults native proof locally (968394 bytes).
  Direct key insertion, type_text paste and Enter preserve Arial Regular7.5pt
  and10.5pt leading. Added text reflows/overflows normally; test edits undone.
  Actual style/image/vector/master, relocation and native export proof pending.
- Owner interrupted twice with physical Escape; stopped app input immediately
  and resumed only after each explicit continue. Script Editor is now visible
  with the read-only font/resource inspector loaded. Its Run action reached
  Affinity Scripting disabled; no inspection report exists. Asked the owner
  to enable the toggle, with the exact Computer Use security-setting restriction
  quoted/linked. No security setting changed; app input paused for the handoff.
- After the owner reported enabled, a fresh Run still gave Scripting disabled.
  Opened the dialog's Settings page read-only and directly observed the top
  switch OFF, default file access OFF and no permitted folders. Requested the
  two switches plus output/manuals folder access together; changed none.
- Prepared separate25-guide/68-page text-default candidate IDML inputs under
  affinity-native-2026-11-10/idml-inputs. Preservation checks compare all ZIP
  parts to the baseline candidate and rehash the frozen source preparation;
  generation is not a native run or a completed editing qualification.
- Later user setup visibly enabled scripting and default file access, with no
  permitted folder listed. Verified Script Editor Run evaluates top-level code:
  adding main() opened the inspection dialog. Exported main alone did nothing.
  Confirmed the inspection, then received PERMISSION DENIED at report-directory
  access. Requested only the missing output/manuals folder grant. No agent
  permission change or report/native export was performed.
-712 original frozen-file hashes pass. All25 candidate IDMLs differ only in
  Styles/Preferences; AST-03 ZIP parts match the actual typed proof input.
  Prepared a separate one-guide-per-Run converter with explicit main invocation,
  fresh per-run outputs and recorded next-guide defaults. Syntax passes; no
  native converter execution. It avoids automatic progression during pauses.
- Owner explicitly directs the agent to add the Affinity folder grant and set
  up scripting. Applied that specific instruction over the skill guideline,
  consistent with the developer's user-over-skill precedence. Added only
  C:/dev/snipe-it-fork/output/manuals; verified the listed path. Existing global
  scripting/file access were already ON; no network/AI permission was enabled.
  Creating a fresh script to inherit defaults before native inspection resumes.
- Fresh script plus explicit main saved the real report:90 live frames,11 full
  original-image placements and installed Arial fonts, with named paragraph
  styles and no inspection errors. Local module loading through the Script
  Editor works. A copied native file reopens;218 scene objects are audited.
- Actual Text Style Editor opened on the isolated copy. Changed Beeldlabel to
  6pt with the spinner, saved and preserved a separate style-change native file.
  Its propagation/reopen check is prepared, not yet executed. Image/vector/
  master/replacement/package proof script has passed syntax only.
- Capture/activation first recovered after the owner restored the desktop, then
  Windows visibly relocked at14:49. Affinity is running/responding. Stopped app
  input, requested unlock and kept the exact next script/evidence documented in
  docs/manuals/operator-guides/reviews/native-conversion-2026-11-10.md.
- Owner also requested AC-02 for printing. Created/reviewed its current v8
  one-page source-rendered A4 PDF and delivered it; HTTP200/Poppler150dpi checks
  pass. It is separate from the nine-guide intake packet and native conversion.

## Refurbisher Guide Login Observation

- Owner reports a live guide tester assigned Refurbisher appeared to land on a
  404 after login and appeared able to log in after being changed to Senior.
  Diagnosis was read-only; local Docker is unavailable, so the actual production
  NUC runtime and retained source/release contracts were checked rather than
  assuming this root checkout served the browser.
- Exact web sequence from the tester device: unauthenticated GET
  `/admin/security` ->302 `/login`; valid POST `/login` ->302 remembered target;
  GET `/admin/security` ->403. The route is inside the authenticated
  `authorize:superuser` group, so neither Refurbisher nor Senior Refurbisher is
  entitled to it. The displayed error was not a login failure or404.
- The intended target was then consumed. A subsequent POST `/login` returned302
  to `/`, followed by dashboard200. This explains the apparent Senior fix without
  attributing it to the role change. Production health remained200, no matching
  app exception appeared and the current role contract grants Refurbisher the
  dashboard/asset/scanning baseline.
- No account, permission, session, database, runtime or deployment mutation was
  performed. Retest with Refurbisher after explicit logout and navigation to the
  base URL. Record any guide/browser step that first navigates to Security; the
  current operator-guide search found no `/admin/security` link.

## Production Duplicate-Serial Guide Observation

- Owner entered new-product serial `5cd142ffm3` and received the Dutch warning
  that one other registration uses the serial. `IdentifierDuplicateService`
  normalizes case/outer whitespace, searches asset and component serials, and
  includes soft-deleted records by design.
- Exact read-only production match is deleted asset ID19 / `INBIT-AA0015`, stored
  serial `5CD142FFM3`, HP ProBook450 G8, status Being Processed. It was created at
  10:26:26 and soft-deleted at10:31:51 today by the same administrative actor.
  This is the product exercised in the immediately preceding guide session, not
  evidence of an unrelated active duplicate.
- Recommended operator recovery is to review/restore `INBIT-AA0015` from
  `/hardware?status=Deleted`, not accept a duplicate or create a second history.
  No production row, identifier, account, workflow or runtime was changed.
- Diagnostic transparency: three preliminary read-only app-container probes at
  11:07-11:09 wrote error log entries because PsySH could not write its history,
  then because the probe used nonexistent `statuslabel`, `user_id` and
  `test_run_id` names. No write query ran. A direct bootstrapped Laravel lookup
  using verified `assetstatus` and `action_logs.created_by` returned the facts
  above. Preserve these timestamps when reviewing production error logs.
