# Agent Addendum - 2026-09-08 Session Initialization

## Objective

Load repository instructions and the current manual checkpoint so operator-
guide work can continue from the existing evidence and review decisions.

## Context Loaded

- Root AGENTS.md, recent PROGRESS.md and docs/fork-notes.md entries, README.md,
  CONTRIBUTING.md, and the manual-related TODO.md entries.
- Operator-guide README, HANDOFF, registry, decisions, system precedence,
  CAT family plan, and the 2026-09-03 guide session and recapture records.
- scripts/manuals/package.json identifies the shared guide-system test and
  package verifier as the manual package test command.

## Continuation Checkpoint

- Initial working tree: clean.
- CAT-00 v9, CAT-01 v5, CAT-02 v1, CAT-03 v1, and CAT-04 v2 are working
  drafts. Nine accepted PDFs are immutable exact-version review candidates.
- Before further CAT-03, CAT-04, or CMP-02 review, create new versions using
  the 11 replacement evidence files recorded in
  [the recapture review](../manuals/operator-guides/reviews/evidence-form-control-recapture-2026-09-03.md).
  Remeasure focus bounds and visually inspect every affected rendered page.
- CAT-05 is the next new CAT guide, with Admin lifecycle/cleanup scope and
  title still to settle. CAT-06 has an unresolved source-recording policy;
  a verification-only draft cannot claim durable source storage.
- Generated guides remain the production format. Affinity remains deferred.
- Consult the target specification, review, and evidence entries before
  editing; read full shared component/layout/maintenance contracts before
  changing shared rendering behavior.
- Preserve the recorded production-access boundary. This initialization
  does not access any application environment.

## Validation

- Initialization changes only session documentation; no PDFs, evidence,
  application code, or review acceptance records changed.
- git diff --check passes. Application and PDF generation tests were not
  run because no implementation or artifact changed.

## Independent Audit Follow-Up

- Owner approved a full guide audit after reviewing the proposed criteria.
- Audited all 22 current generated guides / 48 pages and the three planned
  dependency guides; inspected shared rules and high-risk local code paths.
- Recorded 22 findings and a proposed common task flow in the
  [audit report](../manuals/operator-guides/reviews/2026-09-08-independent-usability-audit.md),
  with an exact-version/hash evidence inventory alongside it.
- Shared guide-system checks and all 29 manifest PDF hashes pass. The actual
  package verifier fails on unlisted CAT-00 v7/v8 and CAT-01 v4; a new
  manifest-only draft mirror passes the complete package verification.
- No guide generation, acceptance, policy change, application mutation, or
  production access. No runtime application or physical user test was run.

## Owner-Requested Round Boundary

- Preserve the prior guide work separately because the new-model audit
  proposals may be rejected. This preference persists for subsequent sessions.
- Created the [frozen baseline](../manuals/operator-guides/reviews/baseline-2026-09-08.md)
  with copies of all 29 manifest PDFs, original statuses/checksums, and an
  archive of 245 supporting source/evidence files. Exact Git source anchor is
  recorded in the checkpoint manifest; working-file snapshots also include
  this session's audit notes.
- Created a separate [audit revision ledger](../manuals/operator-guides/reviews/audit-revision-round-2026-09-08.md).
  Every later candidate needs a new version, baseline comparison, changes and
  tradeoffs, and an exact review decision. No audit correction is implemented
  by this checkpoint, and no previous approval is withdrawn or transferred.
- Existing PDFs remain unchanged. Review-package dividers are separate from
  those PDFs; rollback uses preserved bytes and selective source comparison.
- Verified all 29 original/copy PDF hash pairs, 245 archive entries, archive
  integrity, and 187 local links. Original generators, evidence, and manifests
  remain byte-identical. Shared guide-system checks and whitespace checks pass;
  no application test or environment access was required for this checkpoint.

## Audit Pilot Generation

- Owner requested concrete new PDFs for comparison. Generated WF-01 v11,
  USR-04 v4 and CAT-04 v3, plus a 24-page comparison PDF with a visible divider
  before the new round and an accepted WF-01 v9 appendix.
- The [round ledger](../manuals/operator-guides/reviews/audit-revision-round-2026-09-08.md)
  links exact PDFs, review notes, checksums, validation and the pilot source
  snapshot. All proposals are unaccepted. The original manifests, baseline
  selections, accepted artifacts and shared rendering tokens remain intact.
- Larger body instructions and independent route endpoints are experiments.
  USR-04 grows from two to three pages; some imagery is more tightly cropped.
  Preserve these tradeoffs and evidence limitations when reviewing or revising.
- Ten new A4 pages and four index/divider pages were rendered and inspected.
  Geometry/content checks and all 29 baseline PDF hash pairs pass; new pages
  match their comparison-package rasters. No physical or observed-user test,
  live capture or form submission was performed.
- Unrelated tag-generation application edits appeared in the shared workspace
  during this work. They were not modified, staged, reset or tested by this
  manual session.
- Final shared guide checks, 223 local links, scoped whitespace and four
  retained PDF hashes pass. The manifest-only package verifier passes with
  17 active scripts; the known three unlisted actual-root drafts remain.

## Pilot Direction Rejected

- Owner rejected the new batch's direction: preserve WF-01 v10's 2A profile
  selection and 3A start, screenshots, hints, bottom help, and useful page width.
  Only the clearer two-choice block was explicitly preferred.
- Compared the v10/v11 renders and recorded the [feedback and revised approach](../manuals/operator-guides/reviews/2026-09-08-pilot-direction-feedback.md).
  Candidate decisions now record rejection of the design direction; individual
  factual changes remain undecided. Historical artifacts and acceptance remain.
- Next recommended proof begins from WF-01 v10 and changes only step-3 choice
  grouping/local wording. No new PDF, generator, evidence, or application change
  was made in this feedback turn. Preserve the unrelated shared-workspace work.
- Validation: 29 baseline PDF pairs, 245 source-archive files, 241 local links,
  four pilot/comparison PDF hashes, and scoped whitespace checks pass. Original
  generators, evidence, and manifests remain unchanged; no application tests.

## WF-01 v12 Naming

- Owner assigned the next revision v12. Use
  `WF-01-workflow-starten-v12-draft.pdf` and review record `WF-01-v12.md`;
  preserve the filename stem and increment versions without extra variants.
- Updated the feedback, ledger, handoff, guide notes, and TODO. V12 is planned,
  not generated. Historical filenames and PDF bytes remain unchanged.

## WF-01 v12 Generation

- At the owner's request, generated `WF-01-workflow-starten-v12-draft.pdf`
  from v10 with a local step-3 choice frame/introduction and updated version/date.
  All five screenshots, original text, four help items, and page anatomy remain.
- The baseline reproduction is pixel-identical to frozen v10; the v12 raster
  differs only in step 3 and version/date regions. Full-page inspection,
  PDF/content/geometry checks, shared tests, and manifest-only package checks
  pass. No physical-print or observed-user pass is claimed.
- The [v12 review](../manuals/operator-guides/reviews/WF-01-v12.md) links the
  exact PDF, validation, and eight-file source snapshot. V12 is unaccepted;
  selected baselines, original generators, evidence, and earlier PDFs remain.
- No application operation or test was needed. Unrelated identifier work in
  the shared workspace was left untouched.

## Guide Set Focused Revisions

- Owner reviewed WF-01 v12 positively and confirmed that the follow-up covers
  all existing guides. Preserve baseline page layouts, screenshots, hints/help,
  versioned names, and historical PDFs while applying local corrections.
- Work in progress; coverage and intended versions are recorded in the
  [set revision plan](../manuals/operator-guides/reviews/guide-set-followups-2026-09-08.md).

## Addendum (2026-09-08 All-Guide Focused Candidates)
- Owner confirmed all existing guides after reviewing WF-01 v12 positively.
- Generated 20 new PDFs / 46 pages. Retained WF-01 v12 and CMP-04 v6: all
  22 current guides / 48 pages covered. Earlier accepted, draft and rejected
  PDFs, selected manifests, and historical renderers remain unchanged.
- Kept all 122 raster placements in the revised subset and the help headings.
  Applied local route, role, save/check, handoff, terminology, and reference
  fixes. Logged explicit crop/evidence and AST-03/05 spacing exceptions.
- Comparison and exact-version records:
  docs/manuals/operator-guides/reviews/guide-set-comparison-2026-09-08.md
- No application code, migration, database, production, acceptance, or
  deployment change. A read-only dev account inspection made no data changes.
- Physical A4/user tracing and owner decisions remain pending. Predictable
  initial-password policy, operational locations/profile sets, source storage,
  and missing continuous/numeric-warning evidence remain explicitly open.

- Final checks passed: all 20 new PDF hashes match retained copies; all 25
  review-round PDF hashes (including rejected pilots and WF-01 v12) verified.
  The new 259-file source ZIP passed integrity checks. All 29 earlier PDF
  hashes and the 245-file baseline ZIP remain unchanged; 431 local links pass.
- Shared guide-system checks passed (25 registry entries, 5 reference placements).
  The selected baseline package passes using the manifest-only draft mirror
  (103 evidence, 9 accepted PDFs, 20 drafts, 2 baselines). The actual draft
  folder retains the three previously documented unlisted historical PDFs.
- Scoped whitespace checks passed. The application test suite was not run
  because no application code changed. No commit, push, or deployment.
