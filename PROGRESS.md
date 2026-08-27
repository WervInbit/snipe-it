# Session Progress (2026-08-27)

## Addendum (2026-08-27 CAT-01 Structural Rewrite)
- Rebuilt CAT-01 v3 from the CAT-00 v7 semantic and layout foundation as a
  five-page Supervisor procedure with continuous steps 1-8.
- Replaced the old existing/new split with three explicit search outcomes:
  reuse an exact code, add a missing exact code to an existing Basismodel, or
  create a Basismodel only when product and generation are absent.
- Limited Basismodel creation to the active name, category, and manufacturer
  fields; separated exact code from its readable label; and clarified that a
  later RAM/storage replacement changes the physical asset, not catalogue
  identity.
- Added final verification and styled CAT-02/AST-03 handoffs. Tightened the
  final evidence crops so deprecated EOL and field-set controls are not shown
  as part of the procedure.
- Generated `output/pdf/CAT-01-model-en-modelnummer-aanmaken-v3-draft.pdf`.
  Five A4 pages passed generator component/geometry checks and full-page raster
  review. CAT-01 v3 remains an unaccepted working draft.
- Packaged CAT-00 v7 and CAT-01 v3 under
  `resources/manuals/operator-guides/drafts/` for environment transfer. The
  draft manifest identifies both as `Unaccepted working draft`; no accepted
  PDF, accepted hash, or approval record changed.

## Addendum (2026-08-27 Workflow Capability Review)
- Began a read-only review of the implemented workflow engine, seeded workflow
  catalogue, and current environments for Windows installation, device wiping,
  diagnostic execution, and ordered workflow items.
- No database mutation or implementation change is part of this review.
- Session notes:
  `docs/agents/agents-addendum-2026-08-27-workflow-capability-review-session-init.md`.
- Development and populated rehearsal each contain four active profiles:
  Standard Diagnostics, Pre-Sale Check, Cleaning, and Shipping Laptop. Neither
  contains a Windows-installation or secure-wipe profile/item.
- Standard Diagnostics contains 20 ordered, asset-applicability-filtered
  pass/fail items. The engine records external/manual diagnostic results,
  notes, and private photo evidence; it does not launch the diagnostic tools.
- Workflow items and per-profile membership have persisted drag-and-drop order,
  and new runs snapshot that order. The current run screen does not enforce
  strict step prerequisites: operators may complete cards out of order.
- Release-control check: `master`, cached `origin/master`, and the live remote
  `master` all point to
  `91a6db797496`, but the qualified runtime candidate is not represented by
  that commit. The worktree has zero staged paths, 953 tracked changes, and
  204 untracked paths; roughly 1,148 of those paths are outside the manuals.
  Production deployment from repository history is therefore blocked until
  the runtime, migrations, container profile, and tests are reviewed and
  captured in a reproducible release commit. No commit or deployment was made.

# Session Progress (2026-08-25)

## Addendum (2026-08-25 Portable Unaccepted Review Package)
- Added `resources/manuals/operator-guides/drafts/` as a committed, portable
  collection for the latest unaccepted guide PDFs. Its README and checksum
  manifest state explicitly that these artifacts are for review and
  environment transfer only and are not approved operator instructions.
- Preserved 17 latest unaccepted PDFs across 30 A4 pages. Seven entries record
  their exact accepted predecessor while the accepted nine-PDF package remains
  isolated and unchanged under `resources/manuals/operator-guides/pdf/`.
- Kept the 24 newly added canonical screenshots in the existing evidence
  package; the evidence manifest now verifies 72 portable sources.
- Extended portable path handling, handoff documentation, registry paths, and
  package validation for the draft root. Validation passes checksums, explicit
  status, predecessor links, page counts, A4 dimensions, text extraction, URL
  hygiene, and Markdown/evidence integrity for both accepted and unaccepted
  artifacts.

## Addendum (2026-08-25 CAT-00 Structural Rewrite)
- Rebuilt CAT-00 v4 as an eight-page reference chapter instead of a pseudo-
  task sequence. It now teaches identities, attribute definitions and values,
  component definitions, expected and placed components, model-number
  baselines, physical asset deviations, and effective-value precedence before
  presenting the CAT-01 through CAT-06 route map.
- Added a canonical scrolled model-specification capture for the expected-
  component explanation and registered its stable evidence ID and checksum.
- Strengthened the shared context-reference component so prerequisite and
  contextual handoffs render the complete registered guide name and fail QA
  when a shorthand label is incomplete.
- Removed the leaked layout prompt from the example area and replaced invented
  umbrella terminology with the operator-facing attribute/component terms.
- Generated `output/pdf/CAT-00-catalogus-begrijpen-v4-draft.pdf` as the active
  working draft. CAT-01 and all exact accepted guide artifacts were left
  unchanged.

## Addendum (2026-08-25 Supervisor Product Setup Authorization)
- Implemented the V1 Supervisor product/catalog setup contract.
- Scope: explicit registered permissions, least-privilege foundation-role
  upgrades, policy-backed routes and navigation, negative authorization tests,
  aligned role/readiness documentation, and renewed release-candidate gates.
- Destructive catalog lifecycle and cleanup remain Admin-only; the shared
  development database and populated rehearsal will not be destructively reset.
- Session notes:
  `docs/agents/agents-addendum-2026-08-25-supervisor-product-setup-session-init.md`.
- Supervisor now has explicit ordinary model, model-number/specification,
  attribute-definition, component-definition, and workflow-definition setup
  permissions. Destructive lifecycle/delete/cleanup operations remain
  Admin-only, and existing custom grants survive additive role upgrades.
- Policies, controller guards, routes, navigation, forms, the production
  upgrade runbook, and the operational role matrix now share that contract.
- Promotion exposed a migrated legacy edge: Supervisor already carried
  `models.delete`. Asset-model/model-number deletion and model restoration now
  require both that legacy grant and the new Admin-only lifecycle grant, so
  additive upgrades cannot silently widen destructive access.
- Validation is green: focused surfaces (79 tests/503 assertions; 97/1,205;
  17/200; legacy-grant 11/204; broader catalog 88/586), guarded full SQLite
  (2,166/10,553 in 14:32.95), guarded full MariaDB 11.4.7 (2,166/10,553 in
  16:17.02), syntax, Blade compilation, routes, Composer, npm high/critical
  audits, four Node security regressions, production assets, and
  `git diff --check`.
- Built, content-verified, and blocking-scanned exact app/web images at
  `sha256:bc1e29d8d9a40a4048e3642419c2b7bbd2555b754bedff37bfc7c6456df8fe33`
  and
  `sha256:c8bd31489fb6d22ade4579ecd911515ef676f292954c33757fb7c325073df4b2`;
  both have zero fixable high/critical findings and zero embedded secrets.
- Promoted the exact digests into the populated rehearsal, ran the additive
  role seeder, and proved the actual migrated Supervisor/Admin Gate boundary.
  Full-profile cold restart retained 17 active / 19 total users, 12 assets, 14
  models, 477 migrations, zero failed jobs, and identical 294-public / 14-
  private upload manifests. All seven services are healthy and HTTPS login is
  200 with the required security headers.
- PHPStan and real LDAP/SMTP were intentionally not run. Remaining V1 gates
  are owner browser/credential acceptance, managed-environment rehearsal, and
  release ownership/metadata.

# Session Progress (2026-08-25)

## Addendum (2026-08-25 Operator Guide Visual Corrections)
- Started a focused correction pass for AC-02, USR-01 through USR-04,
  AST-03 through AST-05, and CMP-02 based on owner review.
- CAT guides remain unchanged in this pass and will be reviewed separately.
- Planned checks cover focus alignment and shape, inline warnings, reusable
  guide-reference styling, text containment, screenshot framing, and AST-04
  task clarity before the revised PDFs are offered for review.
- Completed nine corrected drafts across eleven A4 pages: AC-02 v3, USR-01
  v11, USR-02 v9, USR-03 v3, USR-04 v3, AST-03 v14, AST-04 v5, AST-05 v5,
  and CMP-02 v4. Existing accepted versions and all CAT artifacts remain
  unchanged.
- Added the unsaved `USR-EDIT-PASSWORD-GENERATED-DESKTOP-01` evidence source
  so USR-03 shows what `Genereer` produces without retaining a final password.
- Updated the guide specifications, registry, screenshot catalog, inventory,
  decisions, handoff tracker, project index, and per-version review records.
- Validation passed: generator syntax, shared guide-system tests, evidence and
  accepted-package verification, exact A4/page counts, required-text checks,
  and full-page raster inspection of all eleven output pages.
- User explicitly accepted exact AST-03 v14. Preserved its two-page PDF in the
  repository internal-review package with SHA-256
  `e557fc77c7e4b5b2249cdc3b4a9ec2dd3e67a6fcfdea368664d585d7eca74390`;
  the package now contains nine accepted PDFs across eleven pages.

# Session Progress (2026-08-25)

## Addendum (2026-08-25 V1 Qualification Resume And Populated Rehearsal Completion)
- Resumed the August 20 MariaDB qualification after workstation suspension;
  the paused test and database containers remained healthy and the exact-
  candidate suite completed green: 2,160 tests, 10,448 assertions, 48:28.
- The exact-candidate guarded SQLite suite passes 2,160 tests with 10,445
  assertions in 34:40. Focused rehearsal configuration coverage passes 2 tests
  with 32 assertions, and Node security regressions pass 4 of 4.
- Updated `paragonie/sodium_compat` from v1.24.0 to v1.24.2 for the August 18
  Ed25519 validation advisory. Strict Composer validation, locked dry-run
  installation, and the current audit pass; only the three checksum-patched
  Laravel exceptions remain ignored.
- Rebuilt and verified sodium-fixed local production images at app digest
  `sha256:530c699587cfbc9c1f309ef63a4312cafeb20e1cd1e667eeec2c52598d429e0c`
  and web digest
  `sha256:a824b2e5fdbf045d043f17c9e4ce3f141373920519b2a0de8c57293f0c2d165c`.
  Production content policy, pinned framework patches, assets, and the current
  zero-fixable-high/critical plus zero-secret image gate pass.
- Promoted those digests into the populated beta-derived rehearsal. All seven
  production-profile services are healthy; HTTPS login is 200 with the
  required security headers; 477 migrations are applied with none pending.
- Verified exact promotion and cold-restart parity: 19 users, 12 assets, 14
  models, zero failed jobs, 294 public uploads, 14 private uploads, and stable
  upload manifest hashes.
- Restored the immutable export into a separately named clone at its 468-
  migration/17-user baseline. Applied nine migrations in 2.654 seconds,
  rolled back exact batch 2 in 2.024 seconds with row/upload fingerprint parity,
  and reapplied in 3.078 seconds. Removed the disposable clone afterward.
- Added a fail-closed overwrite guard to `prepare-runtime.ps1`; existing
  managed runtime/secret files now require explicit `-Force` before rotation.
- Removed 16 disposable test volumes, the final MariaDB test container/network,
  the rollback clone and its generated secrets. The immutable export and
  populated primary rehearsal remain intact.
- Current no-go: owner direction now requires Supervisor to complete normal
  new-product/catalog setup, but the seeded role, model permissions, workflow
  configuration routes/keys, and destructive lifecycle separation do not yet
  implement that contract. Browser role acceptance, one migrated-password
  login, managed-environment rehearsal, release metadata, and owner sign-off
  also remain open.
- Current status: `docs/v1-release-readiness-status-2026-08-25.md`.
- Session notes:
  `docs/agents/agents-addendum-2026-08-25-v1-qualification-resume-session-init.md`.

# Session Progress (2026-08-20)

## Addendum (2026-08-20 CAT Guide Review)
- Reworked all 12 guides that failed or conditionally passed the cold-start
  audit: AC-02 v2, AST-03 v13, AST-04/05 v4, CMP-02 v3, CMP-04 v6, USR-01
  v10, USR-02 v8, USR-03/04 v2, and CAT-00/01 v2.
- Kept all eight exact internal-review PDFs byte-protected. USR-01 v8 and
  USR-02 v7 remain accepted predecessors; every revised artifact is still a
  working draft pending exact-version review.
- Standardized refreshed AST evidence on the screenshot-only
  `INBIT-HG0421` / `HP ProBook 450 G8` / `5CD1234ABC` identity and updated all
  affected manifest hashes. No server record was created or renamed.
- Updated latest-version generator defaults and gave filtered AST/component
  runs distinct aggregate filenames, so ordinary regeneration now matches the
  registry without overwriting another focused proof.
- Synchronized the registry, guide specifications, evidence catalog, decision
  log, README, inventory, layouts, handoff, TODOs, and 12 exact-version review
  records with the corrected drafts.
- Validation passed: five changed scripts parse; `scripts/manuals/npm test`
  reports 25 registry entries, 70 evidence files, eight accepted PDFs, nine
  accepted pages, two baselines, and 16 active scripts; all 12 PDFs contain
  the expected 21 A4 pages and required text; all pages render nonblank at
  827 x 1170 with inset content.
- Full grouped visual inspection passed with no clipped title, overlapping
  target, unreadable crop, broken page transition, or footer drift. The retest
  result is 19 PASS, 0 conditional, 0 fail, and 6 not testable planned guides.
- Started a review-only pass on CAT-00/CAT-01 after owner feedback about the
  Supervisor role, minimum-rights wording, basismodel terminology, catalogue
  object decisions, source precedence, navigation, and screenshot clarity.
- Verified that one basismodel can have several manufacturer model numbers and
  that many physical assets can share one model number; current RAM/storage is
  represented by installed components and does not redefine that identifier.
- Confirmed that `Primary` is a single default/fallback model number rather
  than a lifecycle status, component-derived specifications take precedence
  over manual values, and installed-component values take precedence over
  component-definition defaults.
- Found that the seeded Supervisor role can create assets but cannot currently
  create/edit basismodellen. Global attribute definitions remain Admin-only,
  while component definitions require `components.manage_definitions`.
- Found no browser form for component-instance attributes; the current write
  path is API-only, so CAT-00 must not present it as an ordinary operator route.
- Confirmed that `Primary` still has runtime effects as the fallback for asset
  forms, imports, model-level specification resolution, component rosters, and
  model-number images. It should be de-emphasized as a system-managed default,
  not described as an obsolete or operator-facing lifecycle decision.
- Owner direction: operator guides use `Admin`, not `Superadmin`, and
  Supervisors must be able to complete the full new-product setup process with
  explicit minimum permissions rather than a broad administrator marker.
- Identified a missing guide tranche for reusable workflow items,
  applicability, workflow profiles, profile-item linking, and final sample-
  asset validation. The old CFG-09 through CFG-12 entries are brainstorming
  only and are not active guides.
- Found implementation blockers for that Supervisor workflow: workflow routes
  are currently inside a superuser-only route group, `test_types.*` permissions
  are not registered, and attribute-definition policy remains Admin-only.
- Recommended a UI-only Dutch terminology pass (`Basismodellen`, `Basismodel`,
  `Naam basismodel`), a task-oriented CAT-00 index and chapter strip, clearer
  user-facing precedence language, and compact label/value pairs in CAT-01.
- Preserved the current v1 draft PDFs and application labels during this
  review. No runtime tests were required because no behavior was changed.
- Completed a first-time-operator pass across the active guide set. The review
  now separates structurally sound task guides, focused cold-start corrections,
  major catalogue rewrites, and visible current-draft export regressions.
- Defined the cold-start usability boundary for the next revision pass: an
  employee may know the physical work but is assumed to have no Snipe-IT or
  programming knowledge; each guide must expose its starting screen, exact UI
  actions, consistent example identity, save/result state, plain-language
  choices, and complete next-guide handoff.
- No guide, generator, accepted artifact, application behavior, or production
  data changed during the cross-guide review.
- Completed the exact cold-start gate for all 25 active registry entries. All
  19 current PDFs rendered as 29 nonblank A4 pages with intact boundaries, and
  the guide package plus structural checks passed after resolving the bundled
  Poppler executable path.
- Manual outcomes are 7 pass, 6 conditional pass, 6 fail, and 6 not testable.
  The exact versions, criteria, and correction order are recorded in
  `docs/manuals/operator-guides/reviews/2026-08-20-cold-start-audit.md`.
- Exact rerendering did not reproduce the earlier suspected current-draft
  clipping in WF-02, CMP-04, AC-02, or USR-04; the full audit supersedes that
  initial visual-scan finding.
- Session notes:
  `docs/agents/agents-addendum-2026-08-20-cat-guide-review-session-init.md`.

# Session Progress (2026-08-18)

## Addendum (2026-08-20 Populated V1 Rehearsal And Qualification)
- Started the authorized full V1 rehearsal using the verified beta export in
  `C:\dev\snipeit-rehearsal-data`, outside Git and Docker build contexts.
- The shared development database and source beta server remain out of scope
  for writes. All migration, account creation, browser mutation, interruption,
  and rollback work will use uniquely named disposable rehearsal resources.
- Real LDAP and SMTP/TLS remain post-V1; V1 qualification will prove their
  disabled modes and all current supported local paths instead.
- Verified all six export checksums, gzip readability, and safe archive paths;
  the immutable export has not been changed or imported yet.
- Added an isolated production-profile rehearsal overlay, TLS edge proxy,
  outside-repository secret/runtime generator, and focused configuration test.
  The focused gate passes 2 tests with 22 assertions under explicit in-memory
  SQLite isolation.
- Built current-source immutable candidate images successfully:
  `local/inbit-app@sha256:e4add80570154436b6107f25e80f7ecbec8e3f85ada3b1110c7fe1ee7513541f`
  and
  `local/inbit-web@sha256:ec379d8fe46e26d66d7bdc0a39b326f9d1be8ed8c54be161a748b9f365f0731c`.
- Compose preflight found that PowerShell array expression precedence emitted
  secret path keys and values on separate lines. The generator has been fixed
  and regression assertions added, but regeneration and Compose validation
  remain the first checkpoint after the device restart.
- Paused cleanly before any rehearsal container, Docker volume, database
  restore, migration, account mutation, or browser mutation was created.
- Session notes:
  `docs/agents/agents-addendum-2026-08-20-populated-v1-rehearsal-session-init.md`.

## Addendum (2026-08-18 Narrow Dashboard Header Regression)
- Reproduced the reported dashboard header overlap at the 768px narrow-desktop
  breakpoint and confirmed the adjacent 767px mobile and 900px desktop layouts
  remain outside the correction.
- Wrapped the combined-brand site name in a dedicated element, hid only that
  redundant text between 768px and 899px, and normalized the asset-search input
  and button to the same 34px border-box height in that range.
- Rebuilt production assets successfully. Live browser evidence at 768px shows
  the duplicate brand text removed and both search controls ending at the same
  pixel; the focused dashboard suite passes 5 tests with 24 assertions, Blade
  compilation succeeds, and focused diff hygiene passes.
- This is a source-level responsive regression, not a database or security
  side effect of the audit. The audit's asset rebuild may only have exposed a
  previously stale compiled stylesheet.

## Addendum (2026-08-18 Catalog Guide Foundation)
- Started the catalogue-management guide tranche with a standards-first audit.
- Initial implementation scope is `CAT-00 Catalogus begrijpen` and `CAT-01
  Model en modelnummer aanmaken`; existing accepted guide artifacts remain
  untouched.
- Session notes:
  `docs/agents/agents-addendum-2026-08-18-catalog-guide-session-init.md`.
- Verified the deployed base-model, exact model-number, attribute, expected
  component, component-definition, precedence, lifecycle, and partial-copy
  behavior before writing the guides.
- Added detailed CAT-00 through CAT-06 specifications, shared CAT family
  registration, a controlled read-only capture script, eight canonical
  evidence files, and the reusable catalogue guide generator.
- Generated `CAT-00 Catalogus begrijpen` v1 as a four-page reference chapter
  and `CAT-01 Model en modelnummer aanmaken` v1 as a five-page procedure with
  continuous steps 1-8. Both are working drafts awaiting exact-version review.
- Validation passed for generator syntax, shared component tests, A4 page
  count/dimensions, context-column and title/version geometry, component
  bounds, extracted-text/stale-URL checks, and full-page raster inspection of
  all nine final PDF pages.
- CAT-02 is the next evidence-and-generation target. CAT-06 remains dependent
  on an operational decision about where catalogue source verification is
  recorded.

## Addendum (2026-08-18 AST-03 v12 Focus And Recovery Correction)
- Preserved AST-03 v11 as review history and created v12 after owner review of
  the remaining step-1 target drift and damaged-label recovery wording.
- Kept 1A unchanged, contained 1B around `Nieuwe aanmaken`, and shifted 1C
  left to center it on the toolbar `+`.
- Replaced the incorrect replacement-print instruction with manual search by
  the unique Inbit asset tag or serial number when a label is not scannable.
- Retained the real 7A underside photo, continuous steps 1-8, and all prior
  registration, save, and post-save verification corrections.

## Addendum (2026-08-18 AST-03 v11 Real Placement Photo)
- Preserved AST-03 v10 as review history and created v11 after the owner
  supplied a real full-underside placement photo.
- Added canonical `AST-LABEL-PLACEMENT-PHOTO-01` and replaced the numbered 7A
  gap. The photo keeps the full device, front edge, ventilation, service
  markings, and lower-right QR label visible.
- Added one generated focus frame around the physical QR label and retained
  the established single post-scan result as 8A.
- AST-03 now has no explicit evidence gap and is ready for exact-version review.

## Addendum (2026-08-18 V1 Authorization And Media Implementation)
- Continued the release implementation plan without changing the shared
  database or the separate manual agent's artifacts.
- Hardened license keys, reports/exports, file controls, and seat check-in
  behavior; focused license/resource coverage is green.
- Separated private workflow evidence from explicit public gallery publishing,
  added user-facing privacy warnings, cleaned private evidence when its run is
  deleted, and preserved both gallery and evidence files across asset soft
  delete/restore.
- Guarded in-memory SQLite evidence is recorded in
  `docs/agents/agents-addendum-2026-08-18-v1-implementation-session-init.md`.
- Final consolidation passes 110 tests with 1,122 assertions; Blade cache
  compilation, focused diff hygiene, and the running HTTPS health check pass.
- PHPStan remains deferred by owner decision and was not run.

## Addendum (2026-08-18 AST-03 v10 Continuous Numbering)
- Preserved AST-03 v9 as review history and created v10 after owner feedback
  that the second page incorrectly restarted a continuous guide at step 1.
- Page 2 now continues with steps 5-8 and image identifiers 5A, 6A/6B, 7A,
  and 8A.
- Kept the rejected generated underside image out of the guide. Its replacement
  remains an explicit numbered 7A slot for the owner-supplied real photo.
- Retained all v9 target geometry and v6 save/status corrections.

## Addendum (2026-08-18 AST-03 v9 Tight Focus Targets)
- Preserved AST-03 v8 as review history and created v9 after owner feedback on
  the remaining 1B/1C target padding.
- Kept the correct 1A target unchanged, tightened 1B around the blue
  `Nieuwe aanmaken` control, and reduced/recentered 1C around the toolbar `+`.
- All later guide content remains unchanged. The real underside placement
  photo remains pending on page 2.

## Addendum (2026-08-18 AST-03 v8 Step-1 Target Alignment)
- Preserved AST-03 v7 as review history and created v8 after owner feedback on
  the dashboard-to-create focus geometry.
- Recalculated the 1A target to enclose the complete `Apparaten` tile, inset
  1B so its stroke remains visible around `Nieuwe aanmaken`, and tightened 1C
  around the toolbar `+` button.
- Kept the v7 step sequence, v6 save/status checks, and all later evidence
  unchanged. The real underside placement photo remains pending on page 2.

## Addendum (2026-08-18 AST-03 v7 Dashboard Entry)
- Preserved AST-03 v6 as review history and created v7 after identifying that
  the guide assumed the user had already opened the hardware index.
- Step 1 now starts from the dashboard: 1A highlights `Apparaten`, while 1B
  and 1C show `Nieuwe aanmaken` and the toolbar `+` as grouped alternatives.
- Reused canonical dashboard and hardware-index evidence. No new capture or
  server-side change was required.
- Retained the v6 `Status`, complete `Opslaan`, and four-field post-save
  verification corrections. The real underside placement photo remains
  pending on page 2.

## Addendum (2026-08-18 AST-03 v6 Save And Verification)
- Preserved AST-03 v5 as review history and created v6 after owner feedback on
  the final registration step.
- Replaced `werkstatus` with the deployed interface label `Status`; 4A now
  includes `Being Processed` and the complete `Opslaan` button.
- Added canonical `AST-REGISTER-SAVED-CHECK-01` evidence from an existing
  development record. Screenshot-only identity substitution and row filtering
  produced a compact 4B check without changing server data.
- 4B now visibly verifies asset tag, status, asset name/model, and serial
  number. The step body and caption name the same checks.
- Published the photo-pending review artifact as
  `output/pdf/AST-03-register-label-v6-draft.pdf`; page 2 still waits for the
  owner-supplied full-underside placement photo.

## Addendum (2026-08-18 AST-03 v5 Alignment And Photo Reset)
- Preserved AST-03 v4 as rejected review history and created v5 after owner
  feedback.
- Recalculated the 1A toolbar `+` and 1B `Nieuwe aanmaken` focus rectangles
  from the canonical source dimensions and rendered crop geometry. The 1B
  image badge now uses the opposite corner so it does not obscure the target.
- Removed the rejected generated underside image from the active draft. The
  official HP ProBook 450 G8 parts locator confirms the bottom orientation and
  service-tag area, but not Inbit QR placement, so v5 keeps a bounded slot for
  the owner's real placement photo.
- Removed the repeated scanner photo from step 4. The remaining result capture
  verifies the asset opened after scanning.
- Published the photo-pending review artifact as
  `output/pdf/AST-03-register-label-v5-draft.pdf`; exact-version review waits
  for the real placement photo.

## Addendum (2026-08-18 AST-03 v4 Focus And Placement)
- Preserved AST-03 v3 as review history and created v4 after owner feedback.
- Enlarged/recentered the 1A toolbar `+` and 1B `Nieuwe aanmaken` focus frames
  so each frame surrounds the complete control.
- Generated and cataloged `AST-LABEL-PLACEMENT-GENERATED-01` as an explicit
  instructional example showing the entire laptop underside, front edge
  facing the reader, and safe lower-right QR placement. It is not live
  evidence and its QR is not intended for scanning; step 4 retains the real
  mobile scanner capture.
- Regenerated and visually inspected both v4 A4 pages. Package validation
  passes with 18 guide records, 60 evidence hashes, 8 accepted PDFs over 9
  pages, 2 baselines, and 14 active scripts. PDF-content checks also pass.
- Published the review artifact as
  `output/pdf/AST-03-register-label-v4-draft.pdf`; exact-version owner review
  remains pending.

## Addendum (2026-08-18 AST Lifecycle v3 Feedback)
- Created new AST-03/04/05 v3 branches from owner review without overwriting
  the v2 proof history or any accepted artifact.
- AST-03 now shows both create controls, explains `Unlock`, automatic uppercase
  behavior and `Aa`, uses a realistic unsubmitted `5CD1234ABC` S/N example,
  separates exact category/model/type selection, removes location from the
  primary path, splits print evidence, and shows lower-right QR placement with
  more physical context.
- AST-04 now uses three full-width rows with separate workflow context/result
  and asset/component checks. AST-05 retains its two-column decision layout.
  Status and workflow focus rectangles now surround controls instead of
  crossing their text.
- Promoted warning hierarchy to the shared guide system: amber for recoverable
  correction and red `STOP` only for genuine halt conditions.
- Reopened the operator-facing status/next-action design as product work. v3
  describes the currently deployed labels but does not treat them as final.
- Refreshed six AST-03 captures without submitting the create form or changing
  a server asset.
- Regenerated and visually inspected all four v3 A4 pages, then published the
  exact review PDFs under `output/pdf/`. The guide-system/package checks pass
  with 18 registry entries, 59 verified evidence files, 8 accepted PDFs over
  9 pages, 2 baselines, and 14 active scripts. PDF text checks confirm the
  expected page counts and required terminology, with no development URL,
  placeholder capture text, provisional-status placeholder, or `STOP` label.

## Addendum (2026-08-18 AST Lifecycle Guide Drafts)
- Replaced the AST-03, AST-04, and AST-05 placeholder branches with focused
  evidence-ready v2 review drafts while preserving the historical v1 defaults.
- AST-03 is two A4 pages; AST-04 and AST-05 are one page each. The lifecycle
  state route is now explicit: register as `Being Processed`, hand off as
  `QA Hold`, release as `Ready for Sale`, or return rejected work to
  `Being Processed`.
- Added 11 canonical mobile captures and a reusable controlled capture script.
  The fictional `INBIT-QH0001`/`INBIT-QH0002` identities are screenshot-only
  DOM substitutions; no application record was created or renamed.
- Updated guide specifications, review records, registry, handoff, screenshot
  catalog, evidence manifest, decisions, inventory, README, and TODO tracking.
- Generated four A4 pages with no missing evidence source. Full-page raster
  inspection, extracted-text checks, lifecycle-term checks, and placeholder /
  development-URL checks passed for all three PDFs.
- The portable guide package passed: 18 registry entries, 59 canonical
  evidence files, eight frozen review PDFs across nine pages, two baselines,
  and 14 maintained scripts.
- Supporting notes:
  `docs/agents/agents-addendum-2026-08-18-ast-lifecycle-guide-session-init.md`.

## Addendum (2026-08-18 Operator Guide Feedback)
- Revised AC-01 again as v8 after follow-up wording feedback: `Nodig` now says
  `Inbit-telefoon + account`. This keeps the expected phone explicit while the
  guide specification still identifies Snipe-IT as a browser shortcut rather
  than an installed application. AC-01 v7 remains review history and accepted
  v6 remains unchanged.
- Started a focused six-guide correction set from floor/user feedback.
- New review versions are AC-01 v8, AST-02 v6, CMP-01 v5, USR-01 v9,
  WF-01 v10, and WF-02 v11; the accepted repository PDFs remain unchanged.
- Reused canonical evidence is sufficient. No browser capture, application
  state, database, service, or accepted artifact will be changed.
- Generated the six review PDFs under `output/pdf/` and updated the guide
  specifications, review records, registry, decisions, inventory, handoff,
  README, TODO, and reusable generators.
- PDF checks passed for page count, A4 size, required and stale wording, and
  full-page rendering. USR-01 geometry passed for 12 badges and five guide
  chips; WF-01 image 3B now targets the first unfinished-run `Bewerk` button.
- Regenerated all six accepted defaults and compared seven rendered pages;
  every page is byte-identical to its committed accepted PDF. Accepted
  artifacts and checksums remain unchanged.
- Generator syntax checks passed. The operator-guide package `npm test` passed
  from `scripts/manuals` with the bundled Poppler `pdfinfo` and Python runtime:
  18 registry entries, 48 evidence files, eight accepted PDFs across nine
  pages, two baselines, and 13 active scripts.
- Supporting notes:
  `docs/agents/agents-addendum-2026-08-18-operator-guide-feedback-session-init.md`.

## Addendum (2026-08-18 V1 Gate Recovery)
- Recovered the interrupted 2026-08-13 V1 gate session after the workstation
  lost power. Retained logs confirm the guarded non-LDAP SQLite and disposable
  MariaDB suites both passed 2,128 tests with 10,198 assertions.
- Retained Composer validation, patch-doctor, and locked-audit evidence is
  green. The interruption occurred while classifying 97 PHPStan findings;
  Node/asset gates and final production-image verification still need reruns.
- No application, configuration, database, test, or documentation file has a
  filesystem modification time newer than the retained 2026-08-13 PHPStan
  evidence. Shared development services remain up and were not restarted or
  migrated during recovery.
- Recovery notes:
  `docs/agents/agents-addendum-2026-08-18-v1-gate-recovery-session-init.md`.
- Reproduced the PHPStan failure on a clean PHP 8.2.32 native-Linux container.
  A baseline-free measurement showed current level 4 debt had fallen from
  5,817 to 2,779 errors; refreshing those stale mappings makes the exact CI
  command green, and a temporary removed negative control still fails.
- Composer strict validation, patch diagnostics, locked audit, full/production
  npm high-severity audits, four Node tests, and the production asset build
  pass. The clean release-context source scan reports zero high/critical
  dependency findings, secrets, or unignored misconfigurations.
- Rebuilt final local production targets as app
  `sha256:9413662024b27618c52932c42b435e131d43bc5c34e177654b8c73ec77e59a80`
  and web
  `sha256:cd6162c6b0c397aae770d87b744c9397a04bd6b462de808107c55b8a3af662d4`.
  Both pass content verification and blocking fixable-high/critical scans.
  Full inventories retain 71 unfixed app findings and zero web findings, with
  no image secrets; SBOM and license reports were retained.
- Scoped Trivy's root-user exception to legacy, unsupported, and local-dev
  Dockerfiles only. The production Dockerfile remains checked without an
  exception. The final release configuration slice passes 42 tests with 848
  assertions.
- Removed all verified `snipeit-v1-*` containers, volumes, and networks plus
  the orphaned no-port production-test container. Shared `snipeit_app`,
  `snipeit_web`, and healthy `snipeit_db` remain running and unchanged; final
  app/web candidate images remain available locally.
- Investigated the intermittent report that an administrator can appear to
  land in Settings after login. The current default and fallback routes both
  resolve to the dashboard; no role-based Settings redirect remains. Laravel
  only records `/admin/settings` as `url.intended` after an unauthenticated
  browser requests that protected URL, so a bookmark, restored tab, or expired
  session on Settings can legitimately produce that result.
- Read-only nginx history for the current development container contains 18
  successful login redirect sequences: 17 landed on `/` and one resumed the
  deliberately requested `/hardware/1`; none landed on `/admin/settings`.
  No redirect behavior was changed and no test/database command was run.
- Fixed intended redirects without changing the direct-login contract. Direct
  regular-user and superuser logins still fall back to the dashboard;
  deliberately requested protected Settings and QR/scan URLs resume after
  login. The two-factor middleware now preserves interactive GET targets until
  either challenge or enrollment completes, successful 2FA and Google login
  consume the intended target with a dashboard fallback, and logout clears
  obsolete intended/2FA session state.
- The guarded in-memory SQLite authentication directory passes 44 tests with
  158 assertions. The new intended-redirect regression class passes 6 tests
  with 41 assertions and covers direct user/admin login, protected Settings,
  QR plus required 2FA, 2FA enrollment, and logout cleanup. PHP syntax checks
  pass for every changed PHP file; no shared database operation was run.
- Static-analysis follow-up: the exact PHPStan command was rerun in both the
  shared development container and a read-only, network-isolated testing
  container. Both reported 3,037 pre-existing ORM dynamic-property findings
  that are not represented by the 2,779-entry baseline; focused output found
  no redirect-specific type/control-flow error. Treat baseline reproducibility
  as open and standardize its schema-aware execution context before V1 rather
  than refreshing the baseline from this unrelated authentication change.
- Because authentication source changed after the retained full-suite and
  production-image gates, repeat those expensive gates once the current code
  change batch is frozen; do not run the 40-50 minute MariaDB suite after each
  small development change.
- Reconciled the V1 go/no-go checklist after the authentication delta. The
  retained SQLite and MariaDB passes remain useful prior evidence, but their
  frozen-candidate entries and the non-reproducible PHPStan gate are now
  correctly unchecked rather than overstating current release readiness.

## Addendum (2026-08-18 V1 Product Scope And Private Attachments)
- Verified that the configurable domain is Workflow Profiles plus reusable
  Workflow Items. Legacy `Test*` models/routes and diagnostic-specific "test"
  labels remain for compatibility; there is no separate general task subsystem
  except the unrelated work-order task feature.
- Confirmed `scripts/hw-inventory.ps1` already contains a preliminary battery
  health calculation based on full-charge/design capacity. The owner deferred
  validation, smarter diagnostics, and agent/workflow ingestion to post-V1,
  alongside the printer/sticker/resolution-aware QR label builder. The current
  QR layout is accepted for V1.
- Classified inherited/fork asset surfaces: Licenses manage software
  entitlements and seats; Images are the public device gallery; Files are
  private asset attachments; Extra files are private asset-model resources;
  workflow photos/results/notes remain run-bound evidence.
- Added independent `assets.files.view/upload/manage` and
  `models.files.view/upload/manage` permissions. Ordinary asset/model view or
  edit no longer grants attachment access through direct UI/API routes. The
  asset detail tab and upload form use the same abilities, and the license tab
  hides check-in unless `licenses.checkin` is allowed.
- Guarded in-memory SQLite attachment, license, group-permission, role-matrix,
  and asset-page coverage passes 45 tests with 456 assertions after one focused
  expectation correction. A final asset-page follow-up covering the model-file
  tab passes 4 tests with 33 assertions. PHP syntax passes for every changed PHP
  file. No shared database, migration, seed, or service mutation occurred.
- Documented that production upgrades preserve the complete user table,
  password hashes, IDs, groups, direct permissions, history, matching APP key,
  and uploads. CSV recreation or production reseeding would not preserve that
  contract. Active session/token continuity remains a separate cutover choice.
- Clarified that real LDAP can be deferred only by excluding and disabling it
  in the V1 support matrix. The current production profile requires SMTP; SMTP
  can be deferred only after an explicit mail-disabled profile is implemented,
  rehearsed, and documented with password-reset/notification limitations.
- Repair/customer passwords are excluded from generic notes, photos, workflow
  evidence, and attachments. Any future storage needs a dedicated encrypted,
  audited, expiring secret flow or an external vault.
- Consolidated the remaining release work into
  `docs/plans/v1-remaining-implementation-plan-2026-08-18.md`. The plan keeps
  the accepted QR and diagnostic work post-V1, defines the license/media/file
  boundaries, and makes support decisions, a recent production-clone migration
  rehearsal, browser role acceptance, and a single frozen-candidate gate the
  V1 critical path.
- Expanded the plan for the current lack of representative LDAP and SMTP
  infrastructure. Both integrations now have implementable disabled-mode and
  automated-test work, separate deferred real-service acceptance checklists,
  and an explicit rule that mocks or local mail capture cannot promote an
  integration into the supported V1 matrix.
- Per owner direction, removed PHPStan from the V1 critical path, candidate
  gates, and go/no-go checklist. Its existing configuration and baseline were
  not changed, and no analyzer command was run. Reproducibility investigation
  is now isolated post-V1 work; runtime, migration, browser, dependency, build,
  and production-profile gates remain required.
- Started WP4 implementation. The production profile now explicitly disables
  LDAP and outgoing mail, uses the non-logging array mail transport, and no
  longer requires SMTP host/sender/password inputs. Runtime LDAP guards cover
  login, connections, settings, imports, sync/troubleshooting, and related UI;
  mail guards cover notifications, direct mail, reset, inventory, test-mail,
  and visible actions while preserving protected administrator password edits.
- The new disabled-integration suite passes 5 tests with 38 assertions. A
  broader focused batch passed 45 tests with 386 assertions; its sole attempted
  failure was the known shared container's missing PHP LDAP constants in an
  extension-dependent mocked test, not a disabled-mode regression. PHP and
  production-entrypoint syntax checks pass. No shared database, migration,
  seed, or external LDAP/SMTP connection was used.

# Session Progress (2026-08-13)

## Addendum (2026-08-13 Portable Operator Guide Package)
- Moved the complete 48-file active canonical evidence set into versioned
  repository resources with stable source-ID filenames and SHA-256 metadata.
- Added the eight exact internal-review candidate PDFs, including USR-01 v8
  and USR-02 v7, plus the locked AC-01/SC-01 SVG baselines and manifests.
- Added a shared portable path/runtime layer for maintained generators,
  repository-local Playwright/Sharp dependency metadata, and package checks
  for hashes, status, pages, A4 dimensions, development URLs, and absolute
  workstation paths.
- Isolated four superseded non-portable generators under
  `scripts/manuals/archive/`; generated proof history remains ignored.
- Updated the handoff, registry, inventory, screenshot catalog, accepted
  review records, README, and TODO to use the repository package.
- Final validation passed: all eight maintained non-capture generator targets
  rebuilt from committed assets; the nine accepted pages rendered cleanly and
  pixel-identically; package checks passed for 48 evidence files, eight PDFs,
  two baselines, A4/page counts, extractable text, and path portability.

## Addendum (2026-08-13 Operator Guide Continuation Handoff)
- Added `docs/manuals/operator-guides/HANDOFF.md` as the current cross-task and
  cross-device resume checkpoint for guide creation.
- Recorded every active guide's current version, creation state, and next
  action, together with the records that must change during a new version.
- Documented current Windows paths, intended logical roots, hardcoded Chrome,
  Poppler, Node, evidence, and output dependencies, existing environment
  variables, and new-device/before-leaving checklists.
- Linked the handoff from the project index, system precedence, inventory, and
  maintenance workflow; added portability and commit-preparation TODO items.
- Documentation-only work; no guide PDF or generator was changed.
- Supporting notes:
  `docs/agents/agents-addendum-2026-08-13-operator-guide-continuation-handoff-session-init.md`.

## Addendum (2026-08-13 USR-02 v7 Internal Acceptance)
- Recorded the user's explicit acceptance of the exact USR-02 v7 PDF as an
  `Internal review candidate for V1`.
- Updated the runtime guide registry, generator metadata, guide specification,
  review decision, production registry, decision log, project index, source
  inventory, and remaining user-guide review list.
- Added USR-02 v7 to the exact internal review candidate list. The older
  six-guide package remains unchanged; USR-01 v8 and USR-02 v7 will be added
  only when that package is deliberately refreshed.
- Status-only work: the accepted v7 PDF was not regenerated or modified.
- Supporting notes:
  `docs/agents/agents-addendum-2026-08-13-usr02-v7-acceptance-session-init.md`.

## Addendum (2026-08-13 V1 Production Profile Rehearsal)
- Ran an isolated production-profile rehearsal under project
  `snipeit-v1-prodtest-20260813`; the existing development containers and
  database were not restarted, migrated, seeded, or modified.
- Built the final local app candidate at
  `sha256:495763177d2270b8df50fa452c9d9d2a929d097d10db65d72a89096601664c63`
  and reused the verified web candidate at
  `sha256:341f2e0b5993145dc20e1af8a1cc2105764bc6f174aac52ee95f2cede3f9846d`.
- A pristine disposable MariaDB database migrated in 63.47 seconds, the
  production foundation seeder and bootstrap-admin flow succeeded, HTTPS
  login/dashboard and security headers passed, and public/private volumes
  persisted across forced app/web replacement.
- The application backup produced a readable 20-entry archive containing the
  database dump and both upload markers. Its dump restored into a separate
  MariaDB volume with 1 user, 1 settings row, and all 476 pre-change
  migrations; both upload trees restored into new volumes with byte-identical
  markers.
- Shared Redis maintenance mode, writer shutdown, maintenance-safe health,
  forward deployment, immediate prior-image rollback, and return to the final
  image all passed. Root stayed 503 in maintenance while `/health` stayed 200.
- An actual queued expiry email was consumed by the `www-data` worker and
  delivered to Mailpit with an empty queue and zero failed jobs. The scheduler
  loaded all seven expected entries and ran as `www-data`.
- Fixed three release defects found by the rehearsal: PHP-FPM master startup,
  dead custom maintenance middleware/503 health checks, and queued mailable
  serialization plus the absent `failed_jobs` table. Focused regression
  evidence is 47 tests with 213 assertions; PHP syntax and image-content
  verification pass.
- Trivy's blocking high/critical `ignore-unfixed` scan passes for both images.
  The app report still lists the checksum-pinned Laravel mail-header advisory
  as scanner status `fixed`; the image verifier proves the maintained backport
  is present. The web image reports zero high/critical findings.
- A final maintenance/documentation regression slice passes 25 tests with 593
  assertions, including a live middleware test proving `/health` remains 200
  while `/login` returns 503. Before cleanup all eight rehearsal services,
  including queue and scheduler, reported healthy.
- Full evidence, limitations, and remaining gates are recorded in
  `docs/v1-production-profile-rehearsal-2026-08-13.md`. V1 remains no-go until
  the current tree receives full SQLite/MariaDB gates and the external LDAP,
  real SMTP/TLS, recent production-clone interruption/restore, frozen artifact,
  product, and owner gates close.

## Addendum (2026-08-13 USR-02 v7 Focus Containment)
- Generated USR-02 v7 after tightening the 3A direct-rights target to the three
  permission rows actually visible in its screenshot frame.
- The complete lower red stroke is now visibly inset from the image edge;
  screenshot crop, step content, and the v6 help-row correction are unchanged.
- Promoted complete focus-stroke visibility to the component contract so a
  clipped ring or rectangle is corrected through the target or crop.
- Preserved v6 as review history and left accepted USR-01 v8 unchanged.
- Supporting notes:
  `docs/agents/agents-addendum-2026-08-13-usr02-v7-focus-containment-session-init.md`.

## Addendum (2026-08-13 USR-02 v6 Help Containment)
- Generated USR-02 v6 with a guide-specific 19 mm help row so the complete
  `USR-05 Groepen beheren` reference remains fully inside its help tile.
- Kept all four help tiles equal in height and moved the reference line down
  slightly, preserving a visible lower border margin without moving the
  completion or related-guide rows off the one-page A4 layout.
- Preserved v5 as review history and left accepted USR-01 v8 unchanged even
  though it shares the generator.
- Promoted the containment rule to the component contract: guide handoffs get
  a dedicated line and the aligned help row grows when the normal height is
  insufficient.
- Supporting notes:
  `docs/agents/agents-addendum-2026-08-13-usr02-v6-help-containment-session-init.md`.

## Addendum (2026-08-13 USR-02 v5 Help Reference)
- Generated USR-02 v5 after converting the `Meerdere gebruikers` help handoff
  from unstyled body text into a complete `USR-05 Groepen beheren` reference
  with the USR marker and family color.
- Promoted the treatment to the shared guide-reference contract so future help
  tiles do not reduce guide handoffs to plain codes.
- Preserved USR-02 v4 as a superseded review artifact and updated the guide
  specification, registry, review record, decisions, project index, inventory,
  and TODO entry to the focused v5 draft.
- Generation geometry passed with 11 badges and four footer references; the
  one-page A4 proof was inspected at full resolution without overlap or
  crowding.
- Supporting notes:
  `docs/agents/agents-addendum-2026-08-13-usr02-v5-help-reference-session-init.md`.

## Addendum (2026-08-13 USR-02 v4 Review)
- Verified from `UserPrivilegeService`, individual user updates, and bulk user
  updates that adding or removing group membership is Superadmin-only; ordinary
  direct per-user permissions remain available to Admin and Superadmin within
  the enforced boundary.
- Generated USR-02 v4 with separate 1A search/user-result evidence and 1B
  `Gebruiker aanpassen` evidence. Search and edit use separate measured focus
  targets rather than implying an edit control exists on the user-list page.
- Clarified that direct `Toestaan` or `Weigeren` choices take priority over
  inherited group rights and renamed the second help item to
  `Effect van recht onduidelijk`.
- Updated the guide specification, layout assignment, registry, evidence
  catalog, review history, decisions, project index, inventory, and TODO entry.
- Existing screenshots were reused. No application state, database, service,
  accepted artifact, Git index, or branch was changed.
- Supporting notes:
  `docs/agents/agents-addendum-2026-08-13-usr02-v4-review-session-init.md`.

## Addendum (2026-08-13 Operator Guide Maintenance Contract)
- Audited all seven Internal review candidates and confirmed that their exact
  accepted layouts remain reproducible through preserved guide-specific
  generators, while the shared component system did not yet register those
  layout variations.
- Added named base layout recipes and step patterns covering compact horizontal
  sequences, stacked and asymmetric flows, two-column grids, route lists,
  troubleshooting grids, alternatives, mixed screenshot widths, reused
  evidence, inline stops, help alternatives, and two-sided continuation.
- Added a human-readable production registry mapping every active guide to its
  current version, review state, page model, layout, generator, and artifact
  root.
- Added a maintenance and change-impact contract for global styles, family
  identity, layouts, evidence, application behavior, policy, guide content,
  and small review corrections. Visible changes now explicitly require new
  versions for every affected guide while accepted artifacts remain immutable.
- Standardized maintenance metadata across all guide specifications and linked
  the new sources into the system precedence, project index, component
  contract, review instructions, decision log, inventory, and TODO list.
- Documentation-only work: no guide PDF, screenshot, generator, application,
  database, service, accepted artifact, Git index, or branch was changed.
- Supporting notes:
  `docs/agents/agents-addendum-2026-08-13-operator-guide-maintenance-contract-session-init.md`.

## Addendum (2026-08-13 V1 Audit MariaDB Resumption)
- Resumed from the clean 2026-08-11 shutdown checkpoint after re-reading the
  agent handbook, current progress, fork notes, and retained MariaDB evidence.
- Confirmed committed base `51208bff3166` on `master` and preserved all 1,078
  dirty-worktree entries, including the separate operator-guide scope.
- Docker is reachable, but all shared application/database services are
  stopped and will remain untouched. The complete strict MariaDB gate will be
  recreated only on a private ephemeral MariaDB 11.4.7 `snipeit_test` target.
- Resume evidence and safety contract:
  `docs/agents/agents-addendum-2026-08-13-v1-audit-mariadb-resumption-session-init.md`.
- Completed the definitive clean supported-database run on private disposable
  MariaDB 11.4.7: 2,139 tests and 10,238 assertions pass in 14:01. No shared
  database was migrated, reset, seeded, or modified.
- Reproduced the preceding run's two residual failures. The importer custom
  mapping fixture failed nondeterministically when Faker selected `Archived`;
  it now selects `Ready to Deploy`. The throttle failure was a valid 59-second
  clock-boundary result; the test now accepts a positive value no greater than
  60 and requires JSON/header equality. The affected MariaDB classes pass 28
  tests with 227 assertions, and the current SQLite risk slice passes 50 tests
  with 421 assertions.
- Generated and adopted `phpstan-baseline.neon` for 5,817 level 4 errors across
  3,635 counted patterns. Corrected the two unbaselined return-contract errors
  by declaring `AttributeValueService::fail()` as `never` and added focused
  exception tests. The exact CI PHPStan command passes with no errors; a
  temporary negative control proved a new return-type error still fails.
- Refreshed dependency advisories. Updated `league/commonmark` from 2.8.3 to
  2.10.0 and PHP_CodeSniffer from 3.13.2 to 3.13.6 after seven new Composer
  advisories appeared. Composer audit now has no unignored findings, the lock
  installs cleanly, strict validation/patch diagnostics pass, and the affected
  42 application tests pass with 253 assertions.
- Updated Less/less-loader, DOMPurify, and Nano ID and replaced AdminLTE's
  obsolete transitive `slimscroll` with the existing `jquery-slimscroll`
  implementation. Full and production npm audits have no critical/high
  findings; clean `npm ci`, Node tests, and the production asset build pass.
- Exercised the CommonMark-backed fork help page after the Composer update.
  Its documentation boundary exposed stale August 4 current-status links in
  CONTRIBUTING, SECURITY, TESTING, and the test itself; all now point to the
  August 13 status. The help/boundary slice passes 12 tests with 454 assertions,
  bringing post-Composer focused evidence to 54 tests and 707 assertions.
- Added `docs/v1-release-readiness-status-2026-08-13.md` and refreshed the
  README/fork notes. The public V1 decision remains no-go pending real LDAP,
  browser/operator, populated upgrade/rollback, production-profile, frozen
  artifact scan, product-decision, and release-owner evidence.
- Removed only the verified private MariaDB, PHPStan, and Node audit containers,
  the private audit network, and all audit-only dependency/work volumes.
  Shared `snipeit_app`, `snipeit_web`, and `snipeit_db` remain running and were
  not restarted or modified by cleanup.
- `git diff --check` is clean for this session's code, test, dependency, and
  documentation paths. The repository-wide command still reports the
  pre-existing unrelated blank line at EOF in
  `resources/views/users/confirm-bulk-delete.blade.php`; it was preserved.
- Continued the V1 audit with production-image builds and scans. The app image
  now refreshes the PHP 8.2 Bookworm index and purges retained compiler and
  development packages after compiling extensions; required PHP modules,
  PHP-FPM, MariaDB client, fail-closed startup, and image-content checks pass.
- The app image's high/critical scan count fell from 286 to 69, all without a
  vendor-fixed version. Composer/Node metadata and secret checks are clean.
  The V1 workflow now uploads complete app/web security JSON reports and uses
  `ignore-unfixed` only for its blocking image scans, so fixable findings and
  secrets remain release failures while unfixed OS risk stays reviewable.
- Replaced the vulnerable pinned NGINX 1.27 / Alpine 3.21 web base with
  immutable NGINX 1.30.4 / Alpine 3.24.1 inputs. The rebuilt web target passes
  repository-content and live-upstream `nginx -t` checks and has zero current
  high/critical Trivy findings.
- CycloneDX SBOM and full-license inventory generation also completes for both
  local candidate images. Local smoke output was discarded; the workflow owns
  retention for frozen-candidate reports.
- The final combined production/release configuration, workflow-upgrade,
  required-schema, and fork-documentation slice passes 41 tests with 772
  assertions. Workflow YAML also parses successfully.
- Read-only inspection of the populated running MariaDB environment confirmed
  the already-cut-over upgrade path: absent legacy source/checkpoint, previous
  and current asset-image photo FK target `workflow_result_photos`, and
  preserved counts of 29 workflow items, 11 runs, and 51 results. This does not
  close the isolated recent production-clone rehearsal gate.

# Session Progress (2026-08-11)

## Addendum (2026-08-11 V1 Audit MariaDB Continuation)
- Resumed from the 2026-08-04 supported-database pause checkpoint after
  re-reading `AGENTS.md`, current progress, fork notes, readiness status, and
  the retained SQLite/LDAP/MariaDB evidence.
- Preserved the separate operator-guide scope and all existing dirty-worktree
  changes. No branch, index, commit, database, container, or application change
  was made during initialization.
- Committed base remains `51208bff3166` on `master`. The retained MariaDB log
  still identifies seven failure classes before the paused run ended:
  importer safe-status selection, LDAP, company ID resolution, and four
  accessory company-boundary UI classes.
- Docker Desktop is currently stopped, so no shared or isolated service is
  running or reachable. Static failure analysis will precede any decision to
  start Docker and recreate the exact disposable `snipeit_test` environment.
- Supporting session notes:
  `docs/agents/agents-addendum-2026-08-11-v1-audit-continuation-session-init.md`.
- Reproduced the retained MariaDB failure slice on a clean disposable database
  and repaired two test-isolation defects: importer tests now delete status
  rows without violating the status-event foreign key, and the test settings
  helper replaces stale singleton rows while caching database-loaded defaults.
  The original seven-class slice plus the new settings regression passed 46
  tests with 121 assertions.
- A strict full MariaDB run then exposed 63 failures. A focused rerun after
  refreshing the created settings model reduced this to three: two real demo
  seeder readiness failures and one read-only audit-upload fixture failure.
- Changed `DemoAssetsSeeder` to create sale-lifecycle demo assets in `Being
  Processed`, build current complete readiness runs, recompute the readiness
  flag, and only then perform the guarded `Ready for Sale`/`Sold` transition.
  The residual seeder/photo slice passes 16 tests with 177 assertions using a
  writable temporary upload mount.
- Diagnosed a subsequent full-run harness attempt: 626 errors and five
  assertion failures were caused by missing test-only Passport keys, while
  seven errors and two failures came from using the stale local image without
  the LDAP extension. The extension-enabled audit image plus generated
  test-only Passport keys passes the complete LDAP class and representative
  protected API classes: 27 tests with 109 assertions.
- The corrected definitive MariaDB run was intentionally stopped for device
  shutdown at 488 of 2,139 tests (22%); every executed test was passing. Resume
  by recreating a private MariaDB 11.4.7 container with the exact database
  `snipeit_test`, using `snipe-it-fork:v1-audit-20260728`, running
  `passport:install`, and retaining the writable `public/uploads` tmpfs and
  512 MB PHPUnit memory limit. Evidence:
  `storage/logs/v1-mariadb-full-definitive-20260811.log`.
- Pause cleanup removed only the five explicitly verified audit containers and
  private `snipeit_audit_20260811` network. Shared `snipeit_app`,
  `snipeit_web`, and `snipeit_db` remain running and untouched.

## Addendum (2026-08-11 Operator Guide Continuation)
- Reinitialized from the current operator-guide index, decisions, evidence
  catalog, generated proofs, and guide specifications.
- Confirmed six exact V1-approved bases: AC-01 v6, SC-01 v10, AST-02 v5,
  WF-01 v9, WF-02 v10, and CMP-01 v4.
- Confirmed three evidence-complete drafts awaiting explicit review: CMP-02 v2,
  CMP-04 v5, and HELP-01 v6.
- Confirmed AST-03, AST-04, and AST-05 remain generated v1 placeholders with
  missing controlled captures and unresolved handoff/release status wording.
- No generator, screenshot, PDF, application state, database, test, Git index,
  branch, or commit was changed during this status pass.
- Continued with USR-01 as the first focused user-management guide. Added a
  two-sided specification covering duplicate checks, the approved username
  convention, generated initial access, Refurbisher/Admin group assignment,
  approved direct-rights exceptions, reset links, generated temporary
  passwords, and immediate user self-change.
- Verified from source that group membership is Superadmin-only, direct
  `Weigeren` overrides group grants, reset links require an active local user
  with email, LDAP passwords remain external, and the application has no
  force-change-at-next-login setting.
- Real screenshot capture is blocked because `https://dev.inbit/` refused
  connections repeatedly. The only running Docker services belong to the
  separate MariaDB audit; they were left untouched. No placeholder PDF,
  account, permission, password, database, container, or Git operation was
  performed.
- Split the overloaded user-management scope into five focused specifications:
  USR-01 add user, USR-02 change role/rights, USR-03 administrator password
  reset, AC-02 user self-change, and two-sided USR-04
  deactivate/delete/restore. Updated the active guide index, decision record,
  capture manifest, and production order.
- Source verification confirmed the exact reset-link and destructive
  check-in/delete labels, self-service password fields, logout of other
  devices after self-change, deletion guards for assigned records and managed
  relationships, and restoration behavior. The controlled site remained
  offline, so no real screenshot or generated PDF was attempted.

# Session Progress (2026-08-06)

## Addendum (2026-08-06 Commitability Audit)
- Inspected the complete dirty worktree without staging or committing changes.
- The tree began this review with 890 tracked changes (815 modified and 75
  deleted), 213 untracked files, and no staged files or merge conflicts.
- Identified the operator-guide documentation as a coherent candidate scope,
  but its generators still contain workstation-specific absolute paths and
  should be made portable or explicitly classified as local tooling before a
  repository commit.
- The repository-wide V1 audit remains a separate high-risk scope. Its retained
  status records an incomplete supported-MariaDB matrix, and `git diff --check`
  still reports the known extra blank line at EOF in
  `resources/views/users/confirm-bulk-delete.blade.php`.
- No application code, generator, PDF, database, service, test, Git index, or
  branch was changed during this audit.

# Session Progress (2026-08-04)

## Addendum (2026-08-04 Operator Guide Capture Retry)
- Retried the controlled `dev.inbit` workflow page before resuming WF-01/WF-02 screenshot work.
- The previous generic 500 response is gone, but the application now blocks all content with `Application upgrade required: database migrations are incomplete`.
- Stopped browser work immediately. No migration, workflow run, screenshot substitution, database write, or guide-file change was attempted.
- The V1 audit session diagnosed seven pending fork migrations against
  `local|mysql|snipeit_prod_work`. Read-only preflight found no SAML nonce
  duplicates, no legacy test tables, no asset photo references requiring
  cutover, no legacy status-history rows, and no configured webhook endpoint.
- Created the verified pre-migration SQL snapshot
  `C:\dev\snipe-it-fork-db-backups\snipeit_prod_work-pre-migrate-20260804-094302.sql`
  (346,890 bytes; SHA-256
  `06647D7325F6594AB1D4521FAD0FA87CEE4545D89DED44447498C7B80A45018A`).
- Applied all seven pending migrations normally with `php artisan migrate
  --force --no-interaction`, then cleared Laravel caches. No reset, refresh,
  wipe, restore, or seed command was used.
- Postflight verified all seven migration records, the required readiness,
  lifecycle, and legacy-history columns, the nonce unique index, and workflow
  migration checkpoints. The database canary remained
  `settings=1|users=5|assets=10|workflow_runs=10|workflow_results=36`.
- HTTP verification now reaches `https://dev.inbit/login` with status 200 and
  no upgrade-required message. Operator-guide screenshot work may resume.
- The expected `codex` screenshot account was absent rather than inactive or
  soft-deleted. Recreated it as an active non-personal user, attached the
  existing `Admin` permission group, retained the established screenshot
  credentials, and verified an application-level authentication attempt. The
  active user canary is now 6 because this intentionally adds one account.
- Diagnosed the subsequent authenticated `/hardware/1` HTTP 500 as a remaining
  Laravel Collective `link_to_route()` call in the new status-event actor row.
  The package/helper had already been retired while 20 call sites remained.
- Replaced all remaining helper calls across Blade views and presenters with
  standard escaped anchors backed by `App\Support\RouteLink`; added focused
  escaping and asset status-event actor rendering regressions. Both tests pass
  against guarded in-memory SQLite (2 tests, 6 assertions), and all changed PHP
  files pass syntax checks.
- Live browser verification with the active `codex` session now renders
  `https://dev.inbit/hardware/1` as `Bekijk Asset DEMO-001 :: Snipe-IT`, with
  the asset heading present and no server-error response. The asset page was
  left open for operator-guide screenshot handoff.
- Resumed the authenticated browser handoff and created exactly one blank
  `Standard Diagnostics` workflow run (`run=11`) on the controlled asset.
  Captured neutral mobile states for the card list, expanded instructions,
  open note panel, open photo panel, and the Tests/profile/run-list entry view
  under `C:\Users\Gebruiker\Documents\snipe-it manuals\screenshot-source\2026-08-04-workflow-neutral`.
- Rebuilt the focused workflow batch with
  `scripts/manuals/generate-workflow-guide-review.mjs`. The current review
  folder is `C:\Users\Gebruiker\Documents\snipe-it manuals\layout-proofs\2026-08-04-workflow-review-batch-v3`:
  WF-01 v7 is one A4 page and WF-02 v5 is two A4 pages.
- WF-01 now keeps the new-run path as four numbered steps and presents
  `Doorgaan met bestaande workflow` as an unnumbered alternative. WF-02 uses
  breadcrumb-only validation, complete neutral cards before result selection,
  collapsed-instruction note/photo states, and a complete saved run row.
- Replaced percentage/object-fit annotations with exact source-pixel SVG
  viewboxes and source-pixel targets. The controlled `DEMO-001` title is
  consistently shown as synthetic example `INBIT-HG0421` in the generated
  proofs; printed guide content contains no development URL.
- Visually inspected all three rendered pages. Poppler reports WF-01=1 A4
  page, WF-02=2 A4 pages, and combined=3 A4 pages; extracted PDF text is clean
  of `dev.inbit`, `DEMO-001`, and obsolete English action labels.
- Applied the next workflow review corrections as batch v4. WF-01 v8 removes
  the redundant asset-validation screenshot because SC-01 owns that
  prerequisite, places `Doorgaan met bestaande workflow` inside step 3, shows
  complete cards in 4A, and names `WF-02 Workflow uitvoeren` in full.
- WF-02 v6 replaces the chopped breadcrumb crop with one complete readable
  context, aligns the 2A/3A targets directly to their controls, and removes
  non-critical stop text from steps 2 and 3 because the help blocks already
  carry escalation guidance.
- Generated batch v4 under
  `C:\Users\Gebruiker\Documents\snipe-it manuals\layout-proofs\2026-08-04-workflow-review-batch-v4`
  and copied `workflow-guides-review-batch-v4-2026-08-04.pdf` to `output/pdf`.
  Visual inspection and Poppler verification again confirm 1/2/3 A4 pages;
  extracted text remains clean of development URLs, the controlled source tag,
  and obsolete English action labels.
- Generated workflow review batch v5 after the next focused correction. WF-01
  v9 now separates the two routes inside step 3 with a centered `OF` divider
  and gives `Doorgaan met bestaande workflow` the same heading hierarchy as
  the primary route. WF-02 v7 centers 2A and 3A on measured source-pixel
  control bounds rather than approximate card coordinates.
- Batch v5 is under
  `C:\Users\Gebruiker\Documents\snipe-it manuals\layout-proofs\2026-08-04-workflow-review-batch-v5`;
  the combined PDF was copied to `output/pdf`. All three pages were visually
  inspected after rendering.
- Generated batch v6 with WF-01 v9 unchanged and WF-02 advanced to v8. The
  page-two 4A and 5A targets now use measured bounds for the active `Notitie`
  control and complete `Foto toevoegen` action instead of approximate offsets.
- Batch v6 is under
  `C:\Users\Gebruiker\Documents\snipe-it manuals\layout-proofs\2026-08-04-workflow-review-batch-v6`;
  the combined PDF was copied to `output/pdf` and the corrected second page
  received a fresh visual check.
- Generated batch v7 with WF-01 v9 unchanged and WF-02 advanced to v9. WF-02
  step 1 now says `Valideer de actieve workflow`, and the front-page
  completion handoff says `Ga verder op de volgende pagina.`
- Batch v7 is under
  `C:\Users\Gebruiker\Documents\snipe-it manuals\layout-proofs\2026-08-04-workflow-review-batch-v7`;
  both WF-02 pages were visually inspected, Poppler confirmed 1/2/3 A4 page
  counts, and extracted text remained clean of development-only labels.
- Verified the exact approved bases remain saved as AC-01 v6, SC-01 v10, and
  AST-02 v5. WF-01 v9 and WF-02 v9 are saved review drafts and are not marked
  approved without exact-version confirmation.
- Recorded the user's exact approval of WF-01 v9 as `Base approved for V1`.
  The generated PDF remains unchanged and saved with the batch.
- Generated workflow batch v8 with WF-02 v10. In screenshot 4A, the native
  yellow outline continues to identify `Notitie` as active, while the red
  target now identifies the note-entry field instead of duplicating the tab
  outline. WF-01 v9 remains unchanged in the combined batch.
- Recorded the user's exact approval of WF-02 v10 as `Base approved for V1`.
  The existing two-page artifact is unchanged. The verified in-app `Foto` and
  `Foto toevoegen` states are accepted as sufficient evidence for this base;
  a device-native picker capture is not required.
- Created controlled CMP-01 evidence by materializing expected RAM 8GB DDR4
  into the Codex tray as component `INBIT-C-UW4626`, assigning serial
  `CMP01-RAM-0001`, setting condition `Good`, and installing it back into
  controlled asset `DEMO-001`. The component remains installed on that asset.
- Generated CMP-01 v2 with
  `scripts/manuals/generate-component-guide-review.mjs` under
  `C:\Users\Gebruiker\Documents\snipe-it manuals\layout-proofs\2026-08-04-component-review-batch-v1`.
  The one-page draft replaces two placeholders and five planned steps with the
  actual four-step interface flow. Selection and installation reuse one real
  mobile screenshot with different target marks; the final image shows the
  matching tracked tag and serial on the asset.
- Generated CMP-01 v3 in the same focused review folder after visual feedback.
  Target marks now use measured source-pixel bounds: step 1 separately marks
  the component icon and add/install action, steps 2 and 3 tightly mark their
  controls, and step 4 separates the tracked state from the tag/serial check.
- Generated CMP-01 v4 after exact pixel-bound review. The component icon is
  horizontally centered, the Install target uses symmetric vertical padding,
  the Tracked target is centered on the badge, and the tag/serial target no
  longer crosses either heading.
- Recorded the user's exact acceptance of CMP-01 v4 as `Base approved for V1`.
- Verified the current live CMP-02 and CMP-04 interfaces with one controlled
  component. Definition-backed RAM 4GB DDR4 was created and installed once as
  `INBIT-C-HH9376` / `CMP02-RAM-0001`; the custom route was opened for evidence
  but not submitted. The same tracked component was then moved to tray and now
  ends in `Status: In Tray` with no asset attached.
- Generated the component follow-up review batch with CMP-02 v2, CMP-04 v4,
  and HELP-01 v6 under
  `C:\Users\Gebruiker\Documents\snipe-it manuals\layout-proofs\2026-08-04-component-followup-review-batch-v1`.
  Each guide is one A4 page; the combined review PDF is three pages. All target
  overlays use source-pixel coordinates, and every rendered page was visually
  inspected before handoff.
- Superseded that batch with component follow-up review batch v2. CMP-04 v5
  centers the 1B target on the measured `Naar tray` action; CMP-02 v2 and
  HELP-01 v6 are unchanged. Clarified that CMP-01 installs an already tracked
  tray/storage record, while CMP-02 route 2A creates a new tracked component
  from an existing catalog definition.
- Packaged the six exact `Base approved for V1` PDFs for internal review under
  `C:\Users\Gebruiker\Documents\snipe-it manuals\internal-review\accepted-guides-v1-2026-08-04`.
  The package contains AC-01 v6, SC-01 v10, AST-02 v5, WF-01 v9, WF-02 v10,
  CMP-01 v4, and a manifest. Hash and A4 page-count checks confirmed every
  PDF is an exact copy of its approved source; unapproved guides are excluded.
- Added exact copies of the five generator scripts used for the accepted set to
  the package's `scripts` folder. Added a guide-to-script map, required
  generation order, and a warning that absolute evidence/runtime paths make
  the script bundle traceable but not self-contained.
- Supporting session notes: `docs/agents/agents-addendum-2026-08-04-operator-guides-retry-session-init.md`.

## Addendum (2026-08-04 V1 Audit Status Recovery)
- Resumed in read-only status-review mode after the user-requested July 28
  pause; re-read `AGENTS.md`, current progress, fork notes, and the exact pause
  checkpoint before evaluating drift.
- Current task focus: report implemented, verified, failing, and open V1 work
  before implementation resumes. No test, migration, reset, restore, seed, or
  application change is part of this opening status review.
- Supporting session notes:
  `docs/agents/agents-addendum-2026-08-04-session-init.md`.
- Status result: committed HEAD is unchanged at `51208bff3166`; audited source,
  configuration, and test files have not changed since the July 28 pause. The
  preserved worktree has 1,067 status entries and is not yet a clean candidate.
- Runtime safety is unchanged: no audit process/subagent is active; Docker
  `app`, `db`, and `web` are running; the read-only shared database canary is
  still `local|mysql|snipeit_prod_work` with
  `settings=1|users=5|assets=10|workflow_runs=10|workflow_results=36`.
- Release status remains no-go. The 56-test matrix slice is green, while the
  storage/import corrections need a narrow rerun and the identity lane retains
  15 failures. Checkout concurrency, inactive-license behavior, root focused
  tests, PHPStan, clean SQLite/MariaDB suites, image scans/SBOMs, deployment and
  restore rehearsal, current docs, and product/ownership decisions remain.
- No implementation or test was run during this status review.

## Addendum (2026-08-04 V1 Audit Implementation and Full-Suite Recovery)
- Continued the interrupted implementation audit while preserving the shared
  screenshot runtime. No live database migration, reset, restore, seed,
  container restart, or image rebuild was performed by this audit block.
- Completed the supported checkout and company-boundary repair lane, including
  row-lock/recheck behavior, inactive/expired license rejection, and
  cross-company assignment denial. The focused lane passes 79 tests with 220
  assertions against guarded in-memory SQLite.
- Ran the repository-wide non-LDAP SQLite suite. The diagnostic run exposed 31
  failures after 2,087 passes; each failure was classified as a product defect,
  stale fork test contract, or cross-test state leak rather than suppressed.
- Fixed deterministic custom-field column naming for non-transliterable names,
  the bulk asset QR/PDF response union, partial component note validation,
  ExternalUrl validation placeholders, Slack Livewire/page Blade section
  ownership, and suite-level cache/locale isolation.
- Updated stale tests to the current fork contract for archived assignments,
  cloning, workflow definition generation, installation/authentication
  boundaries, component notes, and locale-independent translated responses.
- The resolved focused failure set passes 161 tests with 724 assertions.
- The final full non-LDAP run passes **2,120 tests with 10,153 assertions** in
  1,308.38 seconds with no failures, warnings, or risky tests. LDAP remains a
  separate gate because the current shared image lacks its extension and was
  intentionally not rebuilt during manual/screenshot work.
- Full serial PHPStan now completes and reports a measured baseline of 5,887
  errors across 549 files. A focused audit over the four product repair files
  completes with 50 existing Eloquent/PHPDoc baseline errors and no new issue
  in the repaired contracts. Static analysis is therefore measured but not a
  release pass.
- Rechecked current dependency state: Composer has no unignored production
  advisories (three checksum-pinned/reasoned Laravel patch ignores), npm
  production has 0 critical/high, 1 moderate, and 4 low findings, and the
  production asset build passes.
- Refreshed the fork README and published
  `docs/v1-release-readiness-status-2026-08-04.md`. The release decision remains
  no-go pending LDAP/MariaDB, populated upgrade/rollback, browser-role,
  production-profile/restore, artifact-scan, static-baseline, product-decision,
  and release-ownership evidence.
- Retained evidence:
  `storage/logs/v1-full-sqlite-nonldap-20260804.log`,
  `storage/logs/v1-focused-failures-resolved-20260804.log`,
  `storage/logs/v1-full-sqlite-nonldap-after-fixes-20260804.log`,
  `storage/logs/v1-phpstan-serial-after-refactor-20260804.log`, and
  `storage/logs/v1-phpstan-audit-fixes-20260804.log`.
- Proved a non-disruptive LDAP path with the extension-enabled audit image,
  read-only source/dependency mounts, temporary cache/storage, no network, and
  guarded in-memory SQLite. After replacing an extension-incompatible
  namespace mock with real DN/filter escape expectations, the complete LDAP
  group passes 18 tests with 75 assertions. Evidence:
  `storage/logs/v1-ldap-sqlite-20260804.log`.
- Started the exact MariaDB 11.4.7 matrix in a private disposable network with
  no published port, no shared named database volume, and only `snipeit_test`.
  Migration/bootstrap completed and the strict suite advanced into feature
  tests before the user-requested pause.
- The preserved partial MariaDB log contains 70 passing test classes and seven
  failing classes: `AssetImportTest`, `LdapTest`,
  `GetIdForCurrentUserTest`, and four accessory company-boundary UI classes.
  The run was stopped before PHPUnit emitted final stack traces, so these are a
  resumption inventory, not a complete matrix result. Evidence:
  `storage/logs/v1-mariadb-full-20260804.log`.
- Pause cleanup removed only the temporary test app/database containers, their
  anonymous database volume, and private network. Shared `app`, `db`, and
  `web` services remain running; `snipeit_prod_work` was never connected to or
  mutated by the matrix run.

# Session Progress (2026-07-28)

## Addendum (2026-07-28 V1 Audit Recovery)
- Recovered the interrupted July 23 V1 implementation audit after the development device lost power.
- Re-read `AGENTS.md`, the current progress log, `docs/fork-notes.md`, the V1 readiness status, and the surviving dirty worktree before resuming.
- Confirmed that the route, retired-lifecycle, user-integrity, raster-image, attachment, signature, migration, production-profile, and release-document changes remain present in the worktree; no reset, wipe, migration, seed, or shared-database write was used during recovery.
- Docker Desktop is offline at recovery time, so PHPUnit and runtime verification are paused until the engine is available. Static review and non-container checks can continue meanwhile.
- Supporting session notes: `docs/agents/agents-addendum-2026-07-28-v1-audit-recovery-session-init.md`.
- Docker later became available. The recovered diagnostic suite completed with
  1,873 passes, 61 failures, and 11,839 assertions, but remains non-release
  evidence because it loaded the old overlapping Feature/API suite definition
  and implementation changed while it ran.
- Guarded focused evidence before the user-requested pause: the
  category/license/mail/report/branding/user-import slice passed 56 tests and
  342 assertions; the storage/import slice ran 115 tests and 570 assertions
  with one error/seven failures followed by unverified corrections; the
  identity/auth slice ran 77 tests and 218 assertions with 62 passes/15
  failures awaiting triage.
- Hardened the release path further: immutable Dockerfile inputs and
  repository-plus-digest Compose deployment, pinned database CI services,
  production SMTP and optional agent-token secret plumbing, strict trusted
  proxy parsing, bounded CI suites, source/image scans, and retained
  SBOM/license inventories.
- Pause safety check: all subagents were interrupted, no test/static-analysis
  process remains active, and the read-only shared database canary is unchanged
  at `local|mysql|snipeit_prod_work`,
  `settings=1|users=5|assets=10|workflow_runs=10|workflow_results=36`.
- Detailed remaining failures and exact resume order are recorded in the
  supporting session addendum. No implementation continued after the pause
  request.

# Session Progress (2026-07-23)

## Addendum (2026-07-23 HP ProBook 450 Comparison)
- Session kickoff: reviewed `AGENTS.md`, current progress, `docs/fork-notes.md`, and the dirty worktree before researching the exact Dutch HP ProBook 450 G8/G9/G10 product numbers.
- Current task focus: compare `2E9F9EA#ABH`, `6A140EA#ABH`, and `816H4EA#ABH` from verified configuration-level sources and provide a practical buying recommendation.
- Supporting session notes: `docs/agents/agents-addendum-2026-07-23-hp-probook-450-comparison-session-init.md`.
- Research outcome: all three exact SKUs are 15.6-inch FHD IPS 250-nit, 8 GB single-DIMM, 256 GB NVMe business configurations. The important progression is i5-1135G7 (4C/8T) to i5-1235U and i5-1335U (10C/12T), Wi-Fi 6 to Wi-Fi 6E, HDMI 1.4 to 2.1, and a G10 change from the older 3x USB-A/1x USB-C layout to 2x USB-A/2x USB-C.
- Recommendation: G10 is the best overall choice when condition and price are close; G9 is the strongest value because it captures the large G8-to-G9 CPU improvement and retains an IR camera/privacy shutter; G8 remains suitable for basic office work when materially cheaper. A matching second 8 GB SODIMM is the highest-value upgrade on every model.
- No application, database, deployment, or network state was changed. No tests were applicable to this research-only task.

## Addendum (2026-07-23 HP 430 G8 Network Boot Guidance)
- Session kickoff: reviewed `AGENTS.md`, current progress, `docs/fork-notes.md`, and the dirty worktree before researching the HP 430 G8 USB-Ethernet preboot path.
- Current task focus: explain the firmware/adapter boundary and provide a practical UEFI PXE/iPXE/TFTP setup that works with FOG now and a raw iPXE environment later.
- Supporting session notes: `docs/agents/agents-addendum-2026-07-23-hp-430-g8-network-boot-session-init.md`.
- Research outcome: the 430 G8 should use x86-64 UEFI network boot rather than legacy PXE. Preboot networking first requires a firmware-recognized/PXE-capable USB Ethernet adapter; operating-system or FOG kernel drivers cannot create that initial network path.
- Recommended staged path: update the HP T70-family BIOS, validate a PXE-advertised HP USB-C or USB-A Ethernet G2 adapter in the F9 UEFI IPv4 menu, serve `snponly.efi` to x64 UEFI clients, and use a locally booted full/driver-specific iPXE EFI image only as the fallback for an adapter the firmware cannot start.
- Driver boundaries and troubleshooting were documented in the response: firmware/SNP before TFTP, iPXE after the EFI loader starts, FOS/Linux after the FOG menu, and WinPE/full-OS drivers only inside their respective environments. No network, application, database, or deployment configuration was changed.
- Follow-up covered OS-independent BIOS servicing: create an HP BIOS recovery/update USB on another computer, then apply the matching image through Esc/F2 Hardware Diagnostics UEFI `Firmware Management`/`BIOS Management`; the 430 G8's HP Sure Start generation makes the normal F2 update path preferable to Windows+B crisis-recovery instructions.
- Proposed the raw deployment layout: TFTP only for the small UEFI iPXE loader, HTTP(S) for menus/kernels/initramfs/WinPE and installation repositories, native kernel/initrd or `wimboot` entries instead of assuming arbitrary ISO SAN boot will work, and an authorization-gated Linux wipe environment that selects device-native sanitize capabilities and records verifiable results.
- User correction: YouWipe, not HP Secure Erase or a custom Linux wiping implementation, is the required erasure standard. Revised the recommendation around YouWipe's vendor-provided iPXE package and WipeCenter/reporting flow; HP tooling is limited to the offline BIOS update needed for reliable UEFI PXE.

## Addendum (2026-07-23 Operator Guides)
- Session kickoff: reviewed `AGENTS.md`, current progress, `docs/fork-notes.md`, and the active AST-02 generator/specification before continuing the generated-guide review.
- Current task focus: move the unregistered-asset branch below AST-02's yellow alternative routes so it cannot be read as a successful final step in the green primary path.
- Supporting session notes: `docs/agents/agents-addendum-2026-07-23-operator-guides-session-init.md`.
- Revised AST-02 to v5: the six-step registered-asset sequence is now the complete primary path, while `Asset nog niet geregistreerd?` is a full-width yellow alternative beneath the component/mismatch alternatives and points to AST-03.
- Regenerated the individual proof and combined v2 review package. Poppler reports one A4 page for AST-02 and 13 A4 pages for the combined PDF; generated HTML has no `dev.inbit` references, and the external/repo-local combined PDFs have matching SHA-256 hashes.
- User explicitly approved AC-01 v6, SC-01 v10, and AST-02 v5 as the exact generated bases for the V1 guide set. Updated the guide specifications, project index, and approval record accordingly.
- Created focused workflow review folder `C:\Users\Gebruiker\Documents\snipe-it manuals\layout-proofs\2026-07-23-workflow-review-batch` containing WF-01 v5, WF-02 v3, their PNG proofs, and the two-page `workflow-guides-review-batch-2026-07-23.pdf`; copied the combined batch to `output/pdf/`.
- Visually inspected both rendered batch pages. Poppler reports two unencrypted A4 pages, the external/repo-local combined PDFs have matching SHA-256 hashes, and PDF text extraction contains no `dev.inbit` reference.
- A second agent is concurrently changing application code. No browser navigation, live capture, or application-state change was attempted for this batch; any unavailable site or broken screenshot source is now a hard stop requiring user notification.
- Subsequent user-authorized capture retry succeeded against the controlled development environment. Saved current read-only mobile states under `C:\Users\Gebruiker\Documents\snipe-it manuals\screenshot-source\2026-07-23-workflow-refresh`: workflow entry/profile/existing-run, active cards, expanded instructions, open note, and open photo panel. No result, note, photo, or workflow-run data was changed.
- Added `scripts/manuals/generate-workflow-guide-review.mjs` and generated the superseding focused batch `C:\Users\Gebruiker\Documents\snipe-it manuals\layout-proofs\2026-07-23-workflow-review-batch-v2`.
- WF-01 v6 is one A4 page with stacked 1A/1B entry visuals, a targeted Tests icon, explicit `Doorgaan met bestaande workflow` branch, targeted profile/start controls, and no orange workflow-attention banner.
- WF-02 v4 is intentionally two-sided. Its three A4 pages across the combined batch use vertically contextual screenshots for the active run, expanded instructions, result controls, note field, photo/Add photo action, and saved run counts.
- Visually inspected all three rendered pages after two correction passes. Poppler reports WF-01=1 A4 page, WF-02=2 A4 pages, and combined=3 A4 pages; extracted text contains no `dev.inbit`, workflow-attention banner copy, stale photo-omission note, or missing-evidence placeholder.
- Updated the WF-01/WF-02 specifications, canonical screenshot catalog, decision log, project index, and source inventory. Both workflow versions remain generated drafts awaiting user review; neither is marked approved.

## Addendum (2026-07-23 V1 Release Sequencing)
- Session kickoff: reviewed `AGENTS.md`, the current release-readiness audit, `PROGRESS.md`, `docs/fork-notes.md`, `TODO.md`, and the working-tree state.
- Current task focus: turn the open V1 findings into a dependency-ordered implementation and verification sequence without touching the separately owned operator-guide work.
- No application, database, or runtime changes were made during this planning step.
- Supporting session notes: `docs/agents/agents-addendum-2026-07-23-session-init.md`.

# Session Progress (2026-07-21)

## Addendum (2026-07-21 Release Readiness Audit)
- Session kickoff: reinitialized on `AGENTS.md`, current progress, fork notes, repository state, and release-facing documentation before a broad V1 implementation audit.
- Current task focus: validate implemented fork behavior, identify incomplete or undocumented work, compare the fork with current upstream Snipe-IT, and establish the evidence needed for a fork-specific README and V1 release gate.
- Scope coordination: another active agent owns the operator-guide work; this audit will not edit `docs/manuals/operator-guides/` or its generated assets.
- Initial hygiene finding: the worktree contains untracked environment backup files whose names indicate production-derived configuration. Their contents will not be inspected or logged; repository exclusion/removal from the release workspace is a security/release follow-up.
- Supporting session notes: `docs/agents/agents-addendum-2026-07-21-release-readiness-session-init.md`.
- Release decision: the audited revision is not V1-ready. Added `docs/v1-release-readiness-audit-2026-07-21.md` with the evidence, P0/P1 findings, upstream divergence, deployment gaps, and an explicit go/no-go checklist.
- Replaced the inherited upstream README with a fork-specific pre-V1 README covering actual product scope, local-only Docker setup, safe test preflight, production requirements, documentation ownership, upstream relationship, and AGPL attribution. Replaced the inaccurate upstream `SECURITY.md` support/contact claims with a fork-specific pre-V1 policy.
- Confirmed P0 release blockers at audit start: executable workflow-photo upload under the bundled PHP-capable public upload tree; invalid agent reports persisting empty completed runs that readiness can treat as passing; critical/high Composer and npm advisories; Docker build contexts that include local environment, certificate, upload, database, and backup material; and a test guard that could accept the local MySQL runtime before `LazilyRefreshDatabase`. The test guard and Docker build-context containment were remediated in this worktree; the first three remain open, while historical-image credential review and automated image secret scanning are still required.
- The original focused commands produced diagnostic but invalid release evidence: the separate Artisan preflight reported testing/SQLite, while the old direct PHPUnit bootstrap resolved the local profile. Asset/workflow/scan batch: 71 tests, 215 assertions, 4 errors, 9 failures. Work-order/component/catalog batch: 75 tests, 455 assertions, 1 failure. A full-suite attempt did not complete within the audit window and was stopped without a result.
- Guarded reruns supplied trustworthy evidence: asset/workflow/scan batch had 71 tests and 222 assertions with 4 stale agent-fixture errors plus 7 failures; the two earlier redirect failures disappeared under the correct test environment. Work-order/component/catalog batch had 75 tests and 455 assertions with one damaged/broken component-label failure. Local MySQL counts remained unchanged after both runs.
- Upstream audit baseline: fork HEAD `51208bff3166f37eac9452c646e7f89303d2321e`, official master `0099a1a975f4e6c98ece9a30743baa595a94323a`, merge base `4fe7bfb8510a03eb8987a0b0f6845ab6ecaafe6a`; divergence was 326 fork-only and 4,477 upstream-only commits, with 287 paths modified on both sides. Direct bulk merging is not considered a safe V1 strategy.
- Audit incident: the direct focused PHPUnit invocation inherited an unsafe effective local/MySQL configuration; `Tests\TestCase::guardAgainstUnsafeTestingConfig()` checked the text of `.env.testing` rather than the effective runtime, then Laravel's `LazilyRefreshDatabase` implicitly refreshed `snipeit_prod_work`. A later custom diagnostic wrapper repeated the unsafe bootstrap path before the mismatch was recognized. A read-only check confirmed the data was erased. DB-dependent work stopped immediately.
- Recovery: MariaDB binary logging was off and the newest SQL snapshot found by filename/metadata was from 2026-06-09. The user explicitly chose a clean reseed instead of restoring it. After the required preflight, `ProductionFoundationSeeder`, `ProductionDemoUserSeeder`, and `DemoAssetsSeeder` completed; counts are `settings=1`, `users=5`, `assets=10`, and `workflow_runs=10`. Seeded administrator login and dashboard rendering passed in the browser.
- Test-safety remediation: added the dependency-free `tests/phpunit-bootstrap.php` and shared environment guard, synchronized `getenv`/`$_ENV`/`$_SERVER`, rechecked booted Laravel config before refresh traits, restricted local PHPUnit to in-memory SQLite, explicitly rejected the persistent `database/database.sqlite`, restricted external CI databases to explicit `snipeit_test`, and guarded Dusk's `migrate:fresh` behind its dedicated SQLite target. The Dusk guard now creates that exact empty non-symlink file for clean clones after validation. A hostile `mysql/snipeit_prod_work` invocation aborted in bootstrap; forced SQLite verification passed and the reseeded MySQL counts remained unchanged afterward.
- Documentation alignment: replaced the stale demo reset instructions with the actual three-stage local seed sequence and five demo accounts; rewrote `TESTING.md` around the executable guard, forced Docker SQLite preflight, CI external-database marker, and Dusk boundary.
- Correctness remediation: updated the agent-report fixtures to current component applicability, rejected disabled legacy assignment fields with a controlled asset API 422, removed the dead update checkout call, enforced active model-number selection without breaking unrelated PATCH requests, preserved explicit factory presets, localized API expectations/messages, and made current component condition status authoritative over its legacy code.
- Demo reset safety: replaced foreign-key-disabled truncation with constraint-aware deletion, removed runtime component hierarchies instead of leaving invalid attached-without-asset rows, retained work-order snapshots with null live-asset links, preserved non-reused asset IDs, and added a regression proving SQLite and current schema behavior.
- Final consolidated guarded repair slice: explicit preflight `testing|sqlite|sqlite|:memory:`; 93 tests and 320 assertions passed across the environment guard, agent reports, the complete asset update class, component resolution/condition lifecycle, demo reset, and workflow start. Guard coverage includes missing markers, conflicting PHP environment representations, persistent SQLite rejection, clean-clone Dusk file creation, and the explicit PostgreSQL CI path. A read-only post-test check confirmed `local|mysql|snipeit_prod_work|settings=1|users=5|assets=10|workflow_runs=10`.
- Docker release containment: expanded `.dockerignore`, rebuilt `snipeit-fork:v1-audit`, caught and corrected non-recursive SQLite patterns, then verified zero environment, backup/database, certificate, `prodbak`, OAuth-key, and non-allowlisted runtime-upload files in the rebuilt image. The legacy root Dockerfile now removes filtered storage paths defensively. Local/test entrypoints retain Composer development dependencies while other environments use `--no-dev`.
- README installation correction: documented a non-overwriting clean-clone sequence with the actual Compose database settings, one-off Composer/application/Passport key initialization, and the local HTTP profile. This sequence still needs a clean new-volume rehearsal before V1.

## Addendum (2026-07-21 Codex)
- Session kickoff: reinitialized on the Affinity/operator-guide work, reviewed the guide planning and design-foundation documents, and began a visual comparison of the current AC-01, SC-01, AST-01, and remaining initial-guide proof outputs.
- Current task focus: assess whether the present proof-to-Affinity production method is converging on a reusable guide system, identify remaining structural risks, and recommend a guide-by-guide implementation method before further layout work.
- Review outcome: the design grammar is broadly sound, but the current generated-proof workflow is not yet a production system. AC-01/SC-01/AST-01 show a coherent visual direction; the later six-page batch is still a wireframe set with missing or unusably cropped evidence.
- PDF-level inspection found unintended blank second pages in the current AC-01 v5 and SC-01 v6 exports. The remaining combined batch has the intended six pages, while AST-01 v12 has one page.
- The SVG generators commonly use body/caption sizes around 2.25-2.9 mm (roughly 6.4-8.2 pt), below the 11.5-12 pt critical-body target in the design foundation and Affinity research. This is a print-readability blocker even when screen previews look orderly.
- Current scope also drifts: SC-01 and AST-01 duplicate most of the same scan/search/verification flow; AST-02B duplicates unfinished WF-02 detail; several pages were laid out before their required captures existed. AST-01 still prints stale `dev.inbit` source text, and current QR marks are placeholders rather than maintained, scannable guide links.
- Recommended production correction: use generated artifacts for content/crop planning and QA only; create one clean native Affinity template with master/page shell, named text styles, swatches, and reusable block assets; require a complete content brief and visual-purpose manifest before layout; export and render-check each guide before starting the next.
- Recommended build order: AC-01 baseline/template, then resolve SC-01 versus AST-01 ownership and finish both, then HELP-01, WF-01, WF-02, CMP-04, and finally AST-02 as the route/overview guide. Live-state changes needed for final workflow/component screenshots require a controlled test record rather than an unplanned production mutation.
- Direction correction from the user: generated base guides are now the active production method, and Affinity is fully deferred until every guide in the active set is confirmed or the user gives an explicit green light. AC-01 v5 and SC-01 v6 are near-final bases whose current appearance/text scale should be preserved rather than redesigned.
- Consolidated guide work under `docs/manuals/operator-guides/`: added one project/status index, current shared system rules, a decision/approval log, a classified source inventory, and specifications for the nine active guides (`AC-01`, `SC-01`, `AST-01`, `AST-02`, `AST-03`, `WF-01`, `WF-02`, `CMP-04`, and `HELP-01`).
- Added current-status banners to the six older planning/design/Affinity documents. No historical document, proof, screenshot, or Affinity file was deleted or moved; older sources now identify themselves as historical, supporting, or Affinity-deferred.
- Fixed the unintended blank second PDF page in the AC-01 v5 and SC-01 v6 generators by constraining the HTML/SVG print box and using the CSS A4 page size. Regenerated both current output sets without changing their guide layout or wording.
- PDF verification with Poppler now reports exactly one A4 page for both `AC-01-login-snipe-v5-proof.pdf` and `SC-01-scan-asset-snipe-v6-proof.pdf`; rendered-page visual inspection found no layout shift from the export correction. Remaining near-final review items are real QR destination/omission, footer/source wording, and user review at actual A4 size.
- Revised AC-01 to v6 after focused review: the 1A app callout ring is slightly larger with a substantially thinner stroke, and the `Wachtwoord kwijt` help tile now directs the user to ask their supervisor for a password reset. The existing open v5 PDF was preserved as version history.
- Revised SC-01 to v7 after focused review: image 1A now reuses AC-01 3A with the Scan QR card outlined, image 1B targets the camera icon using source-image coordinates, step 2 keeps its header/copy/note above one contained image, duplicate scan copy was removed, and manual search now includes serial numbers.
- Revised SC-01 to v8 by moving the QR-location hint out of the step instructions and attaching it as a subtle caption directly below image 2A.
- Established canonical screenshot reuse for the remaining guides. Identical application states should use the same source evidence with guide-specific crops/callouts; `https://dev.inbit/` is approved for controlled missing or state-changing captures, while printed operator instructions continue to use `https://snipe.inbit/`.
- Added the canonical screenshot catalog and remapped AC-01, SC-01, and the pending AST-01 rebuild to stable source IDs so shared dashboard, scanner, search, and asset-detail evidence is reused intentionally.
- Generated the next three guide drafts with `scripts/manuals/generate-next-guide-drafts.mjs`: `AST-01-open-asset-v13-draft`, `HELP-01-problems-v3-draft`, and `WF-01-start-workflow-v3-draft`. Outputs are under `C:\Users\Gebruiker\Documents\snipe-it manuals\layout-proofs\2026-07-21-next-guide-drafts`.
- AST-01 now reuses the canonical AC/SC scanner evidence and visibly supports title/model, asset tag, and status verification inside the attached stop step. HELP-01 uses non-sequential troubleshooting tiles and supervisor-only password reset wording. WF-01 uses existing controlled workflow captures without exposing the development URL to operators.
- Verified all three exported PDFs with Poppler: each is exactly one A4 page. Rendered-PDF inspection found no page overflow or layout shift; guide versions remain `Generated draft` pending user review, live WF-01 label validation, and final QR handling.
- Revised HELP-01 to `HELP-01-problems-v4-draft` by raising the related-guide chips within each problem tile so their bottom margin no longer appears cramped.
- Revised WF-01 to `WF-01-start-workflow-v4-draft`: step 1 now uses separate asset-title and Tests-tab crops, step 2 begins below the workflow-attention banner, and no guide screenshot contains the orange `Workflowstatus heeft aandacht nodig` banner.
- Confirmed the current SC-01 and AST-01 drafts duplicate both acquisition and verification. Recorded the recommended boundary for approval: SC-01 owns scan/search through opening the asset, while AST-01 begins after opening and owns identity/status verification; merging remains the alternative decision.
- Poppler verification reports one A4 page for both corrected v4 PDFs, and rendered-page inspection matches the PNG proofs.
- Replanned the active guide set to 12 guides: AC-01, SC-01, AST-02 through AST-05, WF-01/WF-02, CMP-01/CMP-02/CMP-04, and HELP-01. Retired AST-01 into SC-01 and CMP-03 into CMP-02; split operator handoff from supervisor release as AST-04 and AST-05.
- Added current specifications for the expanded set and the reusable generators `scripts/manuals/generate-revised-guide-set.mjs` and `scripts/manuals/capture-revised-guide-evidence.mjs`.
- Generated the complete review package under `C:\Users\Gebruiker\Documents\snipe-it manuals\layout-proofs\2026-07-21-revised-guide-set`: 12 individual guides, 13 A4 pages, a combined PDF/HTML, rendered PNGs, manifest, and review summary. A repo-local combined PDF is available at `output/pdf/operator-guides-revised-set-draft.pdf`.
- The generated set uses the shared page shell, overlapping step/image markers, attached stop text, compact help blocks, related-guide chips, 22 mm draft QR area, canonical screenshot reuse, and the live operator URL. SC-01 1A reuses AC-01 3A as agreed; no generated HTML contains `dev.inbit`.
- Visual inspection covered all 13 rendered pages and corrected title overflow, HELP-01 footer overlap, scanner/camera callouts, workflow crop targets, orange warning-banner leakage, and the CMP-04 `Naar tray` action crop. Poppler reports the intended page count and A4 dimensions for every individual and combined PDF.
- Seven evidence gaps remain explicit in orange rather than being fabricated: AST-03 registration fields, AST-04 handoff status/review location, AST-05 release end state, WF-02 completion state, CMP-01 install form/end state, CMP-02 definition/custom form, and CMP-04 post-confirm tray state. No version is marked `Base approved`.
- The read-only capture harness could not authenticate with the previously supplied controlled account, and the Computer Use browser session was stopped when its current URL could not be established confidently. No state-changing submission was made.
- User review rejected the first combined package at page one: the generic grid stretched AC-01 away from its tested v6 layout, crowded SC-01, moved image labels inside screenshot bounds, and used a desktop-specific camera crop; AST-02's route-card concept was useful but too card-heavy.
- Built corrected package `2026-07-21-revised-guide-set-v2`. AC-01 now embeds the tested v6 SVG baseline, SC-01 v10 restores the accepted asymmetric layout with mobile dashboard evidence for both scanner choices and corner-overlap labels, and AST-02 v4 is a compact ordered route list. The original full-set folder is retained as a superseded review artifact.
- Verified the corrected v2 package as 13 unencrypted A4 pages with no `dev.inbit` references in its generated HTML. Both generators pass `node --check`, and the external combined PDF is byte-identical to `output/pdf/operator-guides-revised-set-v2-draft.pdf`.
- Updated the generic screenshot marker implementation so later draft pages also place their smaller translucent image identifiers across screenshot corners instead of clipping them inside the image frame.

# Session Progress (2026-06-09)

## Addendum (2026-06-09 Codex)
- Recovered cleanly from a rejected patch hunk: `app/Models/Asset.php` was unchanged by the failed patch, and the successful edits were limited to the intended permission/config seed path before continuing.
- Added `assets.sale_transition` and seeded it for production `Supervisor` and `Admin` groups; `Admin` now also receives the real `admin` permission while `Supervisor` intentionally does not receive the broad legacy `supervisor` permission.
- Updated asset lifecycle guards so authenticated users need `assets.sale_transition` to move assets into deployable pre-sale or Sold statuses. Moving to Sold, archived, broken/parts, or destroyed-style statuses forces `is_sellable=0`; Ready for Sale/pre-sale does not automatically set `is_sellable=1`.
- Updated asset detail, full edit, and bulk edit status dropdowns to hide Ready/Sold lifecycle statuses from users without `assets.sale_transition`. Detail status updates now preserve `quality_grade` unless it is explicitly submitted, and sale-transition-only users cannot alter quality grade through the status endpoint.
- Kept workflow execution decoupled from asset editing: users with `assets.view` plus `tests.execute` can start workflow runs without requiring `assets.edit`.
- Verification in Docker passed after test preflight (`APP_ENV=testing`, `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`) and cache clear: `php artisan test tests/Feature/Assets/Ui/PreSalePermissionTest.php tests/Feature/Assets/StartNewTestRunTest.php tests/Feature/DeviceComponentCatalogSeederTest.php --env=testing` passed with `17` tests and `117` assertions.
- Local workspace continuation: pulled the remote-device commit by fetching `origin/codex/component-hierarchy-sprints` and fast-forwarding local `codex/component-hierarchy-sprints` from `cadb75f62` to `7bf1affcb`; no merge commit was created.
- Local branch now tracks `origin/codex/component-hierarchy-sprints`. The local setup-only notes created before the pull were folded into this progress entry and `docs/agents/agents-addendum-2026-06-09-session-init.md`; pre-existing local Docker/upload placeholder/env backup/`prodbak` dirt remains untouched. No tests or DB commands were run during the local pull/setup step.
- Updated the current Docker stack for full-system manual testing without resetting the database. Recreated app/web with the localhost compose profile, added the missing `docker/nginx.local.conf` required by `docker-compose.localhost.yml`, and confirmed app env is `APP_ENV=local`, `DB_DATABASE=snipeit_prod_work`, `APP_URL=http://127.0.0.1:18080`.
- Before updating the production-work Docker DB, created snapshot `prodbak/db-snapshots/snipeit_prod_work_pre_docker_update_20260609_094733.sql`; then applied pending additive migration `2026_06_06_120000_add_component_spec_display_settings` and reran `ProductionFoundationSeeder` without running any destructive reset.
- Post-update checks: `php artisan migrate:status --pending` reports no pending migrations; production foundation counts are present (`permission_groups=4`, `status_labels=9`, `attribute_definitions=40`, `component_definitions=94`, `workflow_items=29`); Supervisor/Admin permission rows include `assets.sale_transition`, with Admin also carrying `admin`; `http://127.0.0.1:18080/login` returns HTTP 200 and browser smoke shows the Inbit Snipe-IT login form.
- Restored the running Docker stack back to the default SSL `dev.inbit` profile for user testing. `.env` was unchanged and already had `APP_URL=https://dev.inbit`, `DB_DATABASE=snipeit_prod_work`; the previous issue was the running containers, which had been recreated with the localhost override. Recreated app/web with `docker-compose.yml`, cleared caches, verified web publishes `80/443`, Laravel reports `https://dev.inbit`, and `https://dev.inbit/login` returns HTTP 200.

# Session Progress (2026-06-06)

## Addendum (2026-06-06 Codex)
- Session kickoff: resumed on `codex/component-hierarchy-sprints` after the 2026-06-05 branch sync/reinitialization; reviewed current `PROGRESS.md` and `docs/fork-notes.md` context before catalog inventory work.
- Current user focus: understand which seeded component definitions/port variants already exist and where generic quick-entry fallback definitions are still needed for vague device specifications.
- Added clean-start generic fallback component definitions as catalog-only options, not automatic model-number expected components: `USB-A Port - Generic`, `USB-C Port - Generic`, broad HDMI/DisplayPort/eSATA connector entries, RAM, SSD, HDD, battery, camera, keyboard, wireless, and Bluetooth.
- Kept existing `Wireless Module` because it is currently referenced by seeded model templates and workflow applicability; added the new wireless/Bluetooth generic definitions as manual fallback options.
- Verification: PHP syntax checks passed for `DeviceComponentCatalogSeeder`, `AttributeTestSeeder`, and `DeviceComponentCatalogSeederTest`; `git diff --check` passed with line-ending warnings only.
- Docker Desktop was available, but the Snipe-IT app container initially failed on a CRLF entrypoint (`bash\r`). Hardened the app Dockerfile to normalize the entrypoint during image build, rebuilt `snipeit-app`, and confirmed the app container now stays up.
- Cleaned the local Docker MySQL database after preflight (`APP_ENV=local`, `DB_CONNECTION=mysql`, `DB_DATABASE=snipeit`) by running `php artisan migrate:fresh --seed --force`; snapshot saved first at `prodbak/db-snapshots/snipeit_docker_pre_clean_20260606_011032.sql`.
- Post-clean Docker DB verification: all migrations ran, runtime/demo tables are empty (`assets=0`, `components=0`, `component_instances=0`, `workflow_runs=0`), production seed rows exist (`users=22`, `models=11`, `model_numbers=11`, `workflow_items=29`), and the generic fallback component definitions exist without automatic model-number template assignments.
- Reran `DeviceComponentCatalogSeeder` and `AttributeTestSeeder` idempotently against the cleaned Docker DB; `component_definitions=91`, `generic_fallback_definitions=13`, and `generic_auto_templates=0`.
- Added a localhost compose override that keeps the cleaned `snipeit` database volume while serving the app through `docker/nginx.local.conf`; verified `http://127.0.0.1:18080/login` returns HTTP 200.
- Fixed the asset detail specification metadata layout regression where `Calculated from components` and `Expected/default parts` inherited `row-new-striped` table-cell styling and forced long vertical wrapping. The spec metadata rows now explicitly render as block-level metadata, with browser verification on `http://127.0.0.1:18080/hardware/1`.

# Session Progress (2026-06-05)

## Addendum (2026-06-05 Codex)
- Session kickoff: reviewed the agent handbook provided for this workspace, recent `PROGRESS.md`, and `docs/fork-notes.md` before continuing Snipe-IT work.
- Synced remotes and confirmed the previous `codex/components-traceability-foundation` branch was even with its tracking branch but behind newer component hierarchy work.
- Fast-forwarded local `master` to `origin/master` and created local tracking branch `codex/component-hierarchy-sprints` from `origin/codex/component-hierarchy-sprints`.
- Current branch for upcoming work: `codex/component-hierarchy-sprints`, even with its upstream and 2 commits ahead of `origin/master`.
- Existing untracked local file remains untouched: `storage/debug-workorder.php`.
- Reinitialized on the repo `AGENTS.md`, `docs/agents/session-handoff-2026-06-04.md`, recent 2026-06-04/06-02/05-28 session files, `docs/plans/component-hierarchy-sprint-implementation-plan.md`, `docs/plans/component-hierarchy-subcomponents-plan.md`, clean catalog mapping/removal docs, and `docs/component-hierarchy-operations.md`.
- Current handoff direction: either implement generic quick-entry fallback component definitions (`Wireless Module`, generic USB-A/USB-C, possibly unknown-speed RJ45) or start user testing against the cleaned `snipeit_prod_work` work DB.

# Session Progress (2026-06-04)

## Addendum (2026-06-04 Codex)
- Session kickoff: re-read `AGENTS.md`, recent `PROGRESS.md`, `docs/fork-notes.md`, `docs/agents/agents-addendum-2026-06-02-session-init.md`, `docs/agents/agents-addendum-2026-05-28-session-init.md`, `docs/agents/session-handoff-2026-05-28.md`, `docs/plans/catalog-clean-start-mapping-2026-05-28.md`, and `docs/plans/catalog-removed-attributes-2026-06-02.md`; created `docs/agents/agents-addendum-2026-06-04-session-init.md`.
- Current task focus: reinitialize after the workflow applicability/component reparenting implementation block and report current status for user testing/planning.
- Current non-destructive environment check: Docker app/db/web services are up; app config reports `APP_ENV=local`, `DB_CONNECTION=mysql`, `DB_DATABASE=snipeit_prod_work`; workflow migrations `2026_05_26_120000_rename_tests_to_workflows_and_add_profiles` and `2026_06_02_120000_add_workflow_item_applicability_rules` are both marked ran.
- Current work DB counts checked: `workflow_profiles=4`, `workflow_items=29`, `workflow_runs=25`, `component_definitions=63`, `model_number_component_templates=146`.
- Asset detail Tests / Workflows tab now exposes a workflow profile selector before starting a run; the mobile floating action scrolls/focuses that selector instead of submitting an implicit default run. Backend workflow-run creation now requires `workflow_profile_id`, and focused tests passed: `tests/Feature/Assets/Ui/ShowAssetTest.php`, `tests/Feature/Assets/StartNewTestRunTest.php`, and `tests/Feature/AttributeTestRunGenerationTest.php` (`20` tests, `106` assertions).
- Investigated model number spec duplication on asset `INBIT-QI0001` / `HP ProBook 450 G8 - i5-1135G7 - 8GB - 256GB`: RAM size and storage capacity resolve from expected components, but stale manual DB rows remain; display size, resolution, panel type, refresh rate, RAM type, storage type, and keyboard layout are still manual model attributes even though expected component rows also display matching component contributions.
- Investigated the local work DB cleanup scope for a production-like testing baseline. Current target is `APP_ENV=local`, `DB_CONNECTION=mysql`, `DB_DATABASE=snipeit_prod_work`; runtime/test residue includes 8 assets, 26 workflow runs, 397 workflow results, 9 workflow photos, 3,458 workflow audits, 1 tracked component instance, 1 legacy component row, 5 asset attribute overrides, 10 asset status events, 2 asset images, 2 test licenses, one manually added Vaio model/model-number, and upload artifacts under `public/uploads/assets`, `public/uploads/test_images`, and `public/uploads/labels`. Seed/config rows such as users, settings, categories, statuses, component catalog definitions, model-number templates, workflow profiles/items, and component storage locations should be preserved or reseeded rather than treated as runtime data.
- Cleaned the local work DB in-place after creating `prodbak/db-snapshots/snipeit_prod_work_pre_clean_20260604_114229.sql`. Cleared asset/runtime rows, workflow execution rows, local test component instances, legacy Snipe component/test license rows, generated model-number image references, the ad-hoc Vaio model/model-number, and old ad-hoc component definitions; also purged hidden/deprecated legacy attribute definitions from this work DB. Removed 951 generated files from `public/uploads/assets`, `public/uploads/test_images`, `public/uploads/labels`, and `storage/app/codex-screenshots`, plus `storage/tmp-testtypes-reorder.js`.
- Reran `DeviceAttributeSeeder`, `DevicePresetSeeder`, `DeviceComponentCatalogSeeder`, and `AttributeTestSeeder`, then cleared caches. Post-clean counts: `assets=0`, `workflow_runs=0`, `workflow_results=0`, `workflow_audits=0`, `component_instances=0`, `components=0`, `licenses=0`, `model_number_images=0`, `users=18`, `models=11`, `model_numbers=11`, `attribute_definitions=37`, `component_definitions=60`, `workflow_profiles=4`, `workflow_items=29`, `workflow_profile_items=28`; hidden/deprecated attributes, old component definitions, Vaio rows, and legacy workflow item slugs all verified at `0`.
- Verification after cleanup: `php artisan view:cache` passed and was cleared; logged-in browser smoke loaded dashboard, hardware list, hardware create, Workflow Items, Workflow Profiles, Workflow Profile Items, and models pages without server-error text. A direct browser navigation to the hardware API hit the app/browser redirect handling, so the empty asset list was verified from DB counts rather than that API URL.
- Created a post-clean baseline SQL backup at `prodbak/db-snapshots/snipeit_prod_work_clean_baseline_20260604_115225.sql` for the current `snipeit_prod_work` database.
- Tightened model-number specification behavior so any component definition attribute marked `resolves_to_spec` is treated as component-backed, including text/enum/bool fields such as RAM type, storage type, display resolution, keyboard layout, camera role, and structured port capabilities.
- Updated component-definition and component-instance attribute managers so non-numeric component values can resolve to shared model/asset specs; numeric reduced-baseline comparison remains limited to numeric values.
- Updated clean-start catalog seeding so RAM, storage, display, battery, keyboard, camera, and port component definitions provide the relevant model specs directly, and `DevicePresetSeeder`/`DeviceComponentCatalogSeeder` remove stale manual `model_number_attributes` for those component-backed spec keys.
- Created `prodbak/db-snapshots/snipeit_prod_work_pre_component_spec_cleanup_20260604_120653.sql`, reseeded `DeviceComponentCatalogSeeder`, `DevicePresetSeeder`, `DeviceComponentCatalogSeeder`, and `AttributeTestSeeder`, then verified exact overlap checks return no manual selected model-number attributes that are backed by expected-component spec values.
- Post-cleanup DB check: `model_number_attributes` dropped to `84`; the only remaining `keyboard_layout` manual rows are Surface Pro model numbers where keyboard covers are intentionally treated as sale accessories rather than expected components.
- Focused verification passed: PHP syntax checks for touched spec/attribute services, seeders, and tests; `php artisan test tests/Feature/ComponentDerivedAttributeResolutionTest.php tests/Unit/Services/ModelAttributeManagerTest.php --env=testing` passed with `21` tests and `68` assertions; `php artisan view:cache` passed and was cleared; browser smoke loaded `https://dev.inbit/models/1/model-numbers/1/spec` without console errors and showed only manual product attributes in Selected Attributes.
- Investigated motherboard/logic-board modeling. Current catalog has no motherboard definitions and no seeded expected subcomponent templates; all component definitions remain `placement_mode=either`. Existing hierarchy support can already express motherboard expected subcomponents and suppress overlapping parent/child spec contributions. Current CPU/GPU specs are manual model-number attributes, while RAM/storage/display/battery/keyboard/camera/ports are component-derived. Recommended next catalog split: add model-specific motherboard definitions carrying CPU/core/GPU specs; move soldered/onboard items such as LPDDR RAM, UFS storage, wireless modules, fixed ports, and phone/tablet camera/display assemblies under motherboard/logic board where appropriate; keep removable SO-DIMM RAM, M.2/SATA storage, batteries, displays, keyboards/touchpads, webcams, speakers, and microphones as asset-level components unless they are intentionally board-integrated.
- Follow-up motherboard investigation: `component_definition_subcomponent_templates` is currently empty, and expected subcomponents are tied to the parent component definition rather than individual model-number templates, so motherboard definitions should be model-number-specific to avoid sharing the wrong port/subcomponent set across different models. Workflow applicability already includes expected child component definitions, so port/camera/storage/RAM tests should still resolve if those definitions move under a motherboard. Implementation needs a new component category such as `Logic Board`, motherboard definitions for each preserved model number, seeder support for component-definition subcomponent templates, and removal of moved children from the flat `model_number_component_templates` list.
- Implemented the logic-board catalog split. `DeviceAttributeSeeder` now seeds a `Logic Board` component category, and `DeviceComponentCatalogSeeder` now seeds model-number-specific motherboard/logic-board definitions plus expected child subcomponent templates. HP laptop boards carry CPU/core/GPU attributes and own physical ports; Surface logic boards additionally own LPDDR RAM and wireless; phone logic boards own LPDDR RAM, UFS storage, wireless, and charge/data ports. Removable laptop RAM/storage, displays, batteries, keyboards/touchpads, webcams, speakers, and microphones remain top-level expected components.
- Added focused coverage for the new catalog shape: `DeviceComponentCatalogSeederTest` verifies the HP ProBook 450 G8 motherboard, child USB template, CPU component attribute, and pruned manual CPU model-number row; `TestTypeForAssetTest` now verifies component-category workflow items still apply through expected subcomponents.
- Created `prodbak/db-snapshots/snipeit_prod_work_pre_logic_board_catalog_20260604_124522.sql`, then reseeded `DeviceAttributeSeeder`, `DevicePresetSeeder`, `DeviceComponentCatalogSeeder`, and `AttributeTestSeeder` against `snipeit_prod_work`.
- Post-reseed DB verification: `component_definitions=71`, `logic_board_definitions=11`, `component_definition_subcomponent_templates=54`, `model_number_component_templates=103`, `model_number_attributes=60`; exact overlap checks returned no manual selected attributes that are component-backed, and CPU/core/GPU manual model-number rows are gone.
- Browser smoke loaded `https://dev.inbit/models/1/model-numbers/1/spec`; Selected Attributes now show only release year, weight, OS, OS version, and color for HP ProBook 450 G8, while the expected motherboard row shows CPU/GPU derived attributes and child USB/HDMI/audio-port rows without console errors.
- Focused verification passed after test DB preflight (`APP_ENV=testing`, `DB_CONNECTION=sqlite`, `DB_DATABASE=/var/www/html/database/database.sqlite`): `php artisan test tests/Feature/DeviceComponentCatalogSeederTest.php tests/Unit/Models/TestTypeForAssetTest.php tests/Feature/ComponentDerivedAttributeResolutionTest.php tests/Unit/Services/ModelAttributeManagerTest.php --env=testing` passed with `28` tests and `85` assertions; `php artisan view:cache` passed and was cleared.
- Component Definition settings index now progressively filters while typing. The existing GET search remains as the full server-side search, while JavaScript filters the current rows immediately and submits the search after a short debounce. Focused verification passed: `ComponentDefinitionSettingsTest` (`15` tests, `74` assertions), `php artisan view:cache`, and browser smoke on `https://dev.inbit/admin/settings/component-definitions` including a `Motherboard` live-search interaction with no console errors.
- Refined the Component Definition live search loading state: when the current page has no matching rows but the debounced server search is still pending, the table now shows a spinner row instead of the empty/no-results row. Focused verification passed: `ComponentDefinitionSettingsTest` (`15` tests, `76` assertions), `php artisan view:cache`, and browser smoke for typing `mother`, which showed the spinner while pending and then returned motherboard rows with no console errors.
- Investigated the expected-subcomponent picker and processor location. Expected subcomponents currently use a long native `<select>` of subcomponent-capable component definitions, unlike Attribute Contributions which already use a typeahead result list. Processor was not seeded as a component or freeform subcomponent; it is stored as `cpu_model`, `cpu_core_count`, and `gpu_model` attribute contributions on motherboard/logic-board component definitions, marked `resolves_to_spec`. The expected-subcomponent picker currently has 61 subcomponent-capable component definitions; 11 logic-board definitions are `asset_only` and intentionally excluded from child selection.
- Refined 3.5mm audio port cataloging: `port_connector_type=audio_3_5mm` now represents only the physical 3.5mm connector, while new port attributes `audio_port_role` and `audio_jack_standard` capture headset combo, headphone out, microphone in, line in, line out, and TRS/TRRS details. Clean laptop/tablet motherboard templates now use `3.5mm Port - Headset Combo`, and the obsolete `3.5mm Audio Jack` definition is remapped from old expected-subcomponent templates before being soft-deleted when no tracked component instances depend on it.
- Created `prodbak/db-snapshots/snipeit_prod_work_pre_audio_port_roles_20260604_143553.sql`, reseeded `DeviceAttributeSeeder` and `DeviceComponentCatalogSeeder`, and verified the local work DB has five active role-specific 3.5mm port definitions with the old generic row soft-deleted. Focused verification passed: PHP syntax checks, `DeviceComponentCatalogSeederTest`, `ComponentDerivedAttributeResolutionTest`, and `TestTypeForAssetTest` (`21` tests, `75` assertions); `php artisan view:cache` passed and caches were cleared afterward.
- Component Definition expected-subcomponent rows now use a searchable component-definition picker instead of a long native select. The picker searches definition name, part code, model number, category, and manufacturer, while preserving the same hidden `child_component_definition_id` save payload and freeform expected-name behavior.
- Expected-subcomponent notes in the Component Definition editor now live behind a per-row Bootstrap collapse button. Rows with existing notes or validation errors open the notes panel by default; empty rows stay compact.
- Focused verification for the expected-subcomponent UI passed: PHP syntax checks for touched Blade/test files, `ComponentDefinitionSettingsTest` (`15` tests, `86` assertions), `php artisan view:cache`, and browser smoke on `https://dev.inbit/admin/settings/component-definitions/64/edit`. Browser smoke confirmed five searchable child rows, no native child-definition selects, search results for `HDMI`, selection updating the hidden component ID, notes collapse opening, and no console errors.
- Attribute Definition settings now use the same live-search behavior as Component Definitions. The attribute index filters currently loaded rows immediately while typing, shows a spinner/no-match state while debounced paginated results load, and still submits the existing GET search so full result sets remain searchable.
- Broadened attribute index server-side search from label/key only to label, key, datatype, unit, and category name, matching the visible row metadata more closely.
- Focused verification for attribute live search passed: PHP syntax checks, `AttributeDefinitionLifecycleTest` (`13` tests, `44` assertions), `php artisan view:cache`, and browser smoke on `https://dev.inbit/attributes` typing `RAM`, which navigated to `?search=RAM`, narrowed results, and produced no console errors. Caches were cleared afterward.

# Session Progress (2026-06-02)

## Addendum (2026-06-02 Codex)
- Session kickoff: re-read `AGENTS.md`, `PROGRESS.md`, `docs/fork-notes.md`, `docs/agents/session-handoff-2026-05-28.md`, and `docs/plans/catalog-clean-start-mapping-2026-05-28.md`; created `docs/agents/agents-addendum-2026-06-02-session-init.md`.
- Implemented the clean-start catalog seed foundation without mutating the live/dev MySQL database.
- `DeviceAttributeSeeder` now seeds component-oriented attribute categories, adds structured RAM speed, battery capacity, camera, and port capability attributes, and hides/deprecates removed legacy catalog keys instead of hard-deleting them.
- Added `docs/plans/catalog-removed-attributes-2026-06-02.md` as the explicit audit trail for removed keys and their replacement paths.
- Added `DeviceComponentCatalogSeeder` to seed generic Memory, Storage, Display, Battery, Port, Camera, Audio, Input, Network, and Power component definitions plus expected component templates for the 11 real model numbers.
- Updated the model-number catalog seed to prefer the live `SM-A520F` Samsung code, add Google as Pixel 8 Pro manufacturer, filter removed attributes out of model-number attributes, and keep demo assets out of the default `DatabaseSeeder`.
- Updated workflow item/profile seeding so diagnostics and operational tasks no longer depend on removed present-style product attributes.
- Expected component quantities now group into one asset component row with `xN` display, while derived numeric specs still multiply by quantity.
- Validation completed in Docker after `php artisan optimize:clear` and testing DB preflight (`APP_ENV=testing`, `DB_CONNECTION=sqlite`, `DB_DATABASE=/var/www/html/database/database.sqlite`):
- `docker compose exec -T app php -l` passed for touched PHP seeders/services and the updated component-derived attribute test.
- `docker compose exec -T app php artisan test tests/Feature/ComponentDerivedAttributeResolutionTest.php --env=testing` passed: `13` tests, `49` assertions.
- `docker compose exec -T app php artisan test tests/Feature/Assets/Ui/ComponentHistoryTest.php --env=testing` passed: `8` tests, `43` assertions.
- `docker compose exec -T app php artisan view:cache` passed.
- Browser smoke reached `https://dev.inbit/login`; authenticated asset component roster pages were not checked because no browser login session was available.
- Follow-up authenticated browser check after user logged in: dashboard and hardware list load, but Workflow Settings errors on missing `workflow_profiles`, and asset detail errors on missing `workflow_runs`; the local MySQL database still has `2026_05_26_120000_rename_tests_to_workflows_and_add_profiles` pending.
- Backup comparison check: `snipeit_prod_raw` remains present alongside `snipeit_prod_work`, and file backup material exists under `prodbak/snipe-it-prod-export-20260428`. Compared with raw, the work DB has 10 extra local migrations through component instance attributes, 14 additional structural tables, 3 example component definitions, 1 attached example component instance, 1 extra test run with 18 results and 135 audit rows, and 8 extra login attempts. Core counts for assets, users, models, model numbers, and attribute definitions match raw.
- Individual seed smoke checks passed on testing SQLite for `DeviceAttributeSeeder`, `DevicePresetSeeder`, `DeviceComponentCatalogSeeder`, and `AttributeTestSeeder` after applying the pending workflow migration to the testing SQLite database.
- Full `DatabaseSeeder` smoke on SQLite is still blocked by the existing `UserSeeder` MySQL-only `SET FOREIGN_KEY_CHECKS=0` statement before it reaches the new catalog seeders.
- Earlier settings write-test redirects were narrowed during the workflow applicability block; `ManageTestTypesTest` now disables CSRF like the workflow profile settings tests and passes in the focused suite.
- `git diff --check` passed for touched tracked files with line-ending warnings only.
- Implemented workflow item applicability sources for the clean workflow foundation:
- `workflow_items.applies_to_all` marks intentional always-on tasks, while `component_category_workflow_item` and `component_definition_workflow_item` attach workflow items to expected or installed hardware.
- `TestType::forAsset()` now considers model-number expected components, expected child definitions, and attached tracked components; asset categories narrow specific sources and remain a standalone source only when no attribute/component/always source is selected.
- Workflow item settings and workflow profile settings now show/edit applicability sources across attributes, asset categories, component categories, component definitions, and always-on items.
- Clean `AttributeTestSeeder` now moves HDMI/VGA/SD/webcam/USB/ports and similar checks to component-backed sources, keeps face-unlock out of the standard profile until a real source exists, marks operational tasks as always-on, and explicitly prunes the old `install-update-windows` and `wipen` workflow item slugs.
- Added a per-run extra workflow item picker on the asset workflow start page for one-off checks that still use the same workflow result/note/photo path.
- Added same-asset component hierarchy correction from the asset Components tab: tracked components can be moved under another attached top-level component or back to the asset root, with lifecycle-service validation and a `reparented` component event.
- Work DB backup before applying the new migration/seed:
- SQL dump: `prodbak/db-snapshots/snipeit_prod_work_pre_workflow_applicability_20260602_114937.sql`.
- Clone schema: `snipeit_prod_work_pre_workflow_applicability_20260602_114937`.
- Applied `2026_06_02_120000_add_workflow_item_applicability_rules` to `snipeit_prod_work`, reran `DeviceComponentCatalogSeeder` and `AttributeTestSeeder`, and cleared Laravel caches.
- Work DB verification after seed: old `install-update-windows` and `wipen` items are removed; asset `INBIT-QI0001` Standard Diagnostics resolves to 15 checks and excludes VGA, Ethernet, and SD reader for model number `2E9F8EA#ABH`.
- Browser smoke while logged into `https://dev.inbit/`:
- `https://dev.inbit/hardware/1/tests` loads with Standard Diagnostics, Pre-Sale Check, Cleaning, Shipping Laptop, plus the extra item picker.
- Started Standard Diagnostics run `#24`; active page loads without errors and includes HDMI/USB/Webcam while excluding VGA/Ethernet/SD reader.
- `https://dev.inbit/admin/testtypes` loads without errors, shows applicability source summaries, and no longer shows the old install/wipe items.
- `https://dev.inbit/hardware/1` exposes the new Move Within Device action; `https://dev.inbit/hardware/1/components/1/reparent` loads without errors and correctly reports no alternate parent candidates because asset 1 currently has only one tracked component.
- Browser screenshots saved under `storage/app/codex-screenshots/`: `workflow-start-extra-items-20260602.png`, `workflow-standard-active-20260602.png`, `workflow-item-applicability-settings-20260602.png`, and `component-reparent-empty-candidates-20260602.png`.
- Focused verification passed after `php artisan optimize:clear` and testing DB preflight (`APP_ENV=testing`, `DB_CONNECTION=sqlite`, `DB_DATABASE=/var/www/html/database/database.sqlite`):
- PHP syntax checks passed for the touched workflow/component controllers, model, service, migration, seeder, and tests.
- `docker compose exec -T app php artisan test tests/Unit/Models/TestTypeForAssetTest.php tests/Feature/Assets/StartNewTestRunTest.php tests/Feature/Components/Ui/ComponentWorkflowPagesTest.php tests/Feature/Settings/ManageTestTypesTest.php --env=testing` passed: `22` tests, `127` assertions.
- `docker compose exec -T app php artisan view:cache` passed.

# Session Progress (2026-05-26)

## Addendum (2026-05-26 Codex)
- Session kickoff: re-read `AGENTS.md`, `PROGRESS.md`, `docs/fork-notes.md`, plus the README/CONTRIBUTING fork pointers for initial documentation drift context.
- Created `docs/agents/agents-addendum-2026-05-26-session-init.md` for this session.
- Current branch baseline at initialization: `codex/component-hierarchy-sprints` on commit `4ad83dd3d`.
- Existing local worktree changes remain present, including the uncommitted component hierarchy sprint implementation, prior addenda/docs, Docker/local environment changes, prod-clone artifacts, upload placeholder files, and `storage/tmp-testtypes-reorder.js`; these were left untouched.
- Current task focus: initialize on `AGENTS.md` and relevant fork context files; no implementation request has been started yet.
- `docs/fork-notes.md` latest entry is 2026-05-19 and documents the completed component hierarchy, warning policy, conversion tooling, and operations reference.
- `README.md` and `CONTRIBUTING.md` still point contributors to `AGENTS.md`, `PROGRESS.md`, and `docs/fork-notes.md`; no immediate drift edit was needed during initialization.
- `rg` is still unavailable in this workspace because `rg.exe` returns `Access denied`; PowerShell file reads/searches are the current fallback.
- Additional handoff context read after kickoff: recent addenda from 2026-05-19, 2026-05-07, 2026-05-06, 2026-04-30, 2026-04-28, 2026-04-23, 2026-04-21, and 2026-04-20.
- Product-attribute/component context recovered: shared attribute definitions drive model-number specs, component-definition contributions, asset overrides, component instance attributes, and hierarchy-aware component-derived calculated specs.
- Carry-forward implementation posture: component definitions are global catalog records; component instances are the physical traceability records; hierarchy depth remains capped at asset -> component -> subcomponent; warnings are preferred over blocking for damaged/needs-attention flows, while destroyed/destruction-pending remains locked.
- Attribute/component design check for RAM re-entry:
- component definition attributes are browser-editable and are the right place for reusable/default structured values such as RAM size/type/speed.
- component instance attributes are implemented through service/API sync and participate in spec resolution, but component detail browser editing was intentionally deferred; the browser currently exposes component notes for per-tracked-part specifics.
- notes are suitable for brand, exact part number, batch, or other non-calculated details; notes do not participate in calculated specs, filtering, or structured attribute aggregation.
- Added explicit browser support for arbitrary child component creation from a component detail page:
- `components.children.store` now creates a definition-backed or custom child under an installed top-level component.
- the child form uses active definitions whose placement mode allows subcomponent use, or a custom tracked component name.
- new child components attach directly to the parent asset, start as `Needs Attention`, require warning confirmation, preserve the parent/root asset hierarchy fields, and store freeform specifics in `notes`.
- Focused verification passed after Docker `php artisan optimize:clear` and test DB preflight (`APP_ENV=testing`, `DB_CONNECTION=sqlite`, `DB_DATABASE=/var/www/html/database/database.sqlite`):
- `docker compose run --rm --no-deps -e APP_ENV=testing app sh -lc 'php artisan optimize:clear && echo Test_DB_preflight && grep APP_ENV .env.testing && grep DB_CONNECTION .env.testing && grep DB_DATABASE .env.testing && ./vendor/bin/phpunit --filter ComponentDetail tests/Feature/Components/Ui/ShowComponentTest.php'`
- result: `10` tests passed, `92` assertions.
- Top-level asset add page regression also passed with the same cache-clear/test-DB preflight pattern:
- `docker compose run --rm --no-deps -e APP_ENV=testing app sh -lc 'php artisan optimize:clear && echo Test_DB_preflight && grep APP_ENV .env.testing && grep DB_CONNECTION .env.testing && grep DB_DATABASE .env.testing && ./vendor/bin/phpunit --filter AssetAddPage tests/Feature/Components/Ui/ComponentWorkflowPagesTest.php'`
- result: `1` test passed, `13` assertions.
- A route-list diagnostic was attempted after cache clear, but the local non-testing route bootstrap hit a pre-existing missing settings row (`Attempt to read property "saml_enabled" on null`); the browser tests still resolved the new child route successfully.
- Workflow/test implementation block 1 started after the product-attribute planning discussion:
- added workflow-named tables and profile support through `2026_05_26_120000_rename_tests_to_workflows_and_add_profiles`, copying existing test types/runs/results/photos/audits into `workflow_*` tables and retaining rollback back to legacy `test_*` tables.
- added `WorkflowProfile` and `WorkflowProfileItem` models/factories while keeping existing `TestRun`, `TestResult`, `TestType`, `TestResultPhoto`, and `TestAudit` PHP class names as compatibility wrappers over the new workflow tables.
- asset workflow start screens now choose an active workflow profile, persist profile snapshots on runs, and create result rows from ordered profile items with required/label-mode snapshots.
- active workflow cards keep the same two-button, note, and photo flow; done/not-done profile items display `Done` / `Not Done` labels while reusing the existing pass/fail statuses internally.
- agent report ingestion now accepts both `test_results` and `workflow_results`, stores workflow run/profile metadata, and returns `workflow_run_id` plus the legacy `test_run_id` field for compatibility.
- cleaned and expanded the workflow seed foundation: standard diagnostics, pre-sale check, cleaning, and shipping-laptop profiles are seeded; operational task items use done/not-done labels where appropriate.
- updated test assertions and affected workflow/audit/photo relationships to use the workflow table and column names; retained user-facing compatibility names where the API/list columns still expect them.
- fixed the promoted-test-photo source foreign key retargeting so `asset_images.source_photo_id` points at `workflow_result_photos` after the table rename, including the SQLite migration path used by PHPUnit.
- No live dev/prod-clone database migration was run in this block; code was verified only against the isolated SQLite testing database.
- Focused workflow/profile verification passed after Docker cache clear and testing DB preflight (`APP_ENV=testing`, `DB_CONNECTION=sqlite`, `DB_DATABASE=/var/www/html/database/database.sqlite`):
- `docker compose run --rm --no-deps -e APP_ENV=testing app sh -lc "php artisan optimize:clear && echo Test_DB_preflight && grep '^APP_ENV=' .env.testing && grep '^DB_CONNECTION=' .env.testing && grep '^DB_DATABASE=' .env.testing && ./vendor/bin/phpunit tests/Feature/Assets/StartNewTestRunTest.php tests/Feature/AgentAttributeReportTest.php tests/Feature/AttributeTestRunGenerationTest.php tests/Feature/Settings/ManageTestTypesTest.php"`
- result: `12` tests passed, `61` assertions.
- Broader workflow/audit/photo/status regression passed with the same cache-clear/test-DB preflight:
- `docker compose run --rm --no-deps -e APP_ENV=testing app sh -lc "php artisan optimize:clear && echo Test_DB_preflight && grep '^APP_ENV=' .env.testing && grep '^DB_CONNECTION=' .env.testing && grep '^DB_DATABASE=' .env.testing && ./vendor/bin/phpunit tests/Feature/Assets/Api/AgentTestResultsTest.php tests/Feature/Assets/PartialUpdateTestResultTest.php tests/Feature/Assets/PromoteTestResultPhotoToAssetImageTest.php tests/Feature/Assets/Ui/AllTestsPassedIndicatorTest.php tests/Feature/Assets/Ui/ReadyForSaleWarningTest.php tests/Feature/Assets/TestAuditLoggingTest.php tests/Unit/TestAuditLogsTest.php tests/Unit/TestRelationshipsTest.php"`
- result: `25` tests passed, `113` assertions.
- PHP syntax checks passed for the changed workflow controllers/models/migration/seeders/factories and focused tests.
- `git diff --check` passed with line-ending warnings only.

# Session Progress (2026-05-19)

## Addendum (2026-05-19 Codex)
- Session kickoff: re-read `AGENTS.md`, `PROGRESS.md`, `docs/fork-notes.md`, `docs/plans/component-hierarchy-sprint-implementation-plan.md`, and relevant warning-policy sections of `docs/plans/component-hierarchy-subcomponents-plan.md`.
- Created `docs/agents/agents-addendum-2026-05-19-session-init.md` for this session.
- Current branch baseline at initialization: `codex/component-hierarchy-sprints` on commit `4ad83dd3d`.
- Existing local worktree changes remain present, including the uncommitted component hierarchy sprint work from 2026-05-07 and older local environment/prod-clone artifacts; these were left untouched.
- Current task focus: reinitialize after the later date and prepare to continue the review-gated component hierarchy sprint plan.
- Plan status confirmed: Sprints 0 through 8 are documented as implemented; the next planned increment is Sprint 9, `Selling-State Warnings`.
- Alignment check: the sprint plan and original hierarchy plan both require warnings, not hard blocking, when moving assets into ready-for-sale/selling/sold states while damaged or needs-attention parts remain attached.
- Carry-forward policy remains unchanged: damaged, needs-attention, and sold/returned install paths are warning-confirmed; destroyed/destruction-pending components remain locked from normal reinstall/reattach.
- No implementation or verification has been run yet in this session beyond documentation initialization and repo-state inspection.
- Broad investigation before continuing implementation:
- `rg` is unavailable in this workspace due `Access denied`, so PowerShell file discovery/search was used.
- Sprint 9 warning surfaces are `AssetsController::updateStatus`, `AssetsController::update`, `BulkAssetsController::update`, and possibly `AssetsController::toggleSaleAvailability`; API asset updates remain a review choice because the API controller currently saves through the model without a structured warning response.
- Existing ready-for-sale/sold failed-test warnings already use a two-submit confirmation pattern with `ack_failed_tests`; attached component issue warnings should use a separate confirmation flag so failed-test and component warnings can coexist.
- Attached component issue detection should include top-level and child `component_instances` with `current_asset_id` on the asset and attached/installed lifecycle, and should exclude tray, stock, destroyed, and detached rows.
- Current asset component roster/spec aggregation is still flat: attached child components appear through `Asset::trackedComponents()` and will be treated as ordinary top-level extras until the hierarchy-aware roster/spec sprints are implemented.
- No instance-level component attribute table/model exists yet, so Sprint 10 has not been partially implemented.
- Existing hierarchy/lifecycle tests cover parent-child persistence, expected child materialization, child detach ancestry, parent move cascade, lifecycle/condition split, and install warnings for damaged, needs-attention, and sold/returned parts.
- Docker stack was started after Docker Desktop became available: `snipeit_db`, `snipeit_app`, and `snipeit_web` are running, and `https://localhost` redirects to `https://dev.inbit/login` with the expected local certificate warning.
- Sprint 9 test check: no Sprint 9-specific attached-component selling-state warning tests were found. The closest existing status-warning test is `tests/Feature/Assets/Ui/ReadyForSaleWarningTest.php`.
- Test attempt after `docker compose exec app php artisan optimize:clear` and testing DB preflight (`DB_CONNECTION=sqlite`, `DB_DATABASE=/var/www/html/database/database.sqlite`):
- `docker compose exec app php artisan test --env=testing tests/Feature/Assets/Ui/ReadyForSaleWarningTest.php`
- result: failed, `1` test, `3` assertions; missing expected session key `warning` at `tests/Feature/Assets/Ui/ReadyForSaleWarningTest.php:37`.
- Sprint 9 selling-state warnings implemented:
- added `AttachedComponentIssueService` to find currently attached top-level and child components whose condition is `Damaged` or `Needs Attention`.
- hardware detail status updates, hardware edit status updates, bulk status updates, and the hardware available-for-sale toggle now warn before selling-state transitions when affected attached parts remain present.
- confirmations use `ack_component_issues` and do not reuse `ack_failed_tests`, so failed-test warnings and component warnings remain separate.
- detached, tray, stock, destroyed, and current-asset-null components do not trigger current attached-part selling warnings.
- API asset status changes were intentionally left for a later explicit review choice.
- updated the stale ready-for-sale failed-test warning test fixture to disable CSRF for the mutating request, use a unique status name, and use a superuser actor so it matches the current supervisor/admin sale-status rule.
- Sprint 9 focused verification passed:
- `docker compose exec app php artisan test --env=testing tests/Feature/Assets/Ui/SellingStateComponentWarningTest.php`
- result: `5` tests passed, `39` assertions.
- related status-warning regression passed:
- `docker compose exec app php artisan test --env=testing tests/Feature/Assets/Ui/SellingStateComponentWarningTest.php tests/Feature/Assets/Ui/ReadyForSaleWarningTest.php`
- result: `6` tests passed, `46` assertions.
- `docker compose exec app php artisan view:cache` passed.
- broader asset UI regression attempt found pre-existing/stale CSRF test issues, not Sprint 9 failures:
- `docker compose exec app php artisan test --env=testing tests/Feature/Assets/Ui/QualityGradeDetailUpdateTest.php tests/Feature/Assets/Ui/BulkEditAssetsTest.php tests/Feature/Assets/Ui/EditAssetTest.php`
- result: `QualityGradeDetailUpdateTest` passed; `BulkEditAssetsTest` and several mutating `EditAssetTest` cases failed because legacy tests do not disable CSRF and requests redirect before controller logic runs.
- final `docker compose exec app php artisan optimize:clear` passed.
- `git diff --check` passed with line-ending warnings only.
- Sprint 10 instance-level attributes implemented:
- added `component_instance_attributes` plus `ComponentInstanceAttribute` relations on component instances and attribute definitions.
- added `ComponentInstanceAttributeManager` to sync rows, validate values through the shared attribute value rules, reject duplicate attributes per instance, inherit definition-level spec-resolution behavior for overrides when no explicit flag is provided, and remove rows omitted from a sync payload.
- API component create/update now accepts `instance_attributes`; omitted payload leaves existing instance attributes untouched and an empty array clears them.
- `ComponentsTransformer` now returns synced instance attributes on component API responses.
- `ComponentAttributeAggregator` now resolves same-row component values from instance attributes first and falls back to component-definition attributes when no instance override exists.
- custom component instances, including custom child rows already present in the flat roster, can carry spec-resolving structured attributes.
- attribute option value propagation, usage summaries, and delete safeguards now include component instance attribute rows.
- Component detail UI editing was intentionally left as a later review choice; Sprint 10 used the service/API-first path.
- Sprint 10 focused verification passed after `docker compose exec app php artisan optimize:clear` and testing DB preflight (`DB_CONNECTION=sqlite`, `DB_DATABASE=/var/www/html/database/database.sqlite`):
- `docker compose exec app php artisan test --env=testing tests/Unit/Services/ComponentInstanceAttributeManagerTest.php tests/Feature/Components/Api/ComponentLifecycleApiTest.php tests/Feature/ComponentDerivedAttributeResolutionTest.php tests/Feature/AttributeDefinitionLifecycleTest.php`
- result: `27` tests passed, `90` assertions.
- Sprint 11 hierarchy-aware spec resolver implemented:
- `AssetComponentRosterService` now filters calculated spec roster input to attached component instances and treats attached child components as extra/custom rather than letting them satisfy asset-level expected component slots.
- `ComponentAttributeAggregator` now suppresses parent component records for an attribute when an attached child under that parent contributes the same calculated spec attribute; child values are retained and parent values are kept as overlap-warning metadata.
- `EffectiveAttributeResolver` now preserves aggregate metadata on calculated asset attributes so overlap warnings reach the UI.
- hardware detail and hardware edit specification areas now show a generic parent/child overlap warning when child values override parent-level values.
- damaged-but-attached child components still contribute to current specs and continue to appear in attached component issue warnings.
- detached, in-tray, in-stock, destroyed, and current-asset-null parts are excluded from calculated asset specs through the attached roster filter.
- Sprint 11 focused verification passed:
- `docker compose exec app php artisan test --env=testing tests/Feature/ComponentDerivedAttributeResolutionTest.php`
- result: `12` tests passed, `43` assertions.
- Related regression verification passed:
- `docker compose exec app php artisan test --env=testing tests/Unit/Services/ComponentInstanceAttributeManagerTest.php tests/Feature/Components/Api/ComponentLifecycleApiTest.php tests/Feature/Assets/Ui/SellingStateComponentWarningTest.php tests/Feature/ComponentDerivedAttributeResolutionTest.php`
- result: `26` tests passed, `109` assertions.
- `docker compose exec app php artisan view:cache` passed.
- Sprint 12 asset Components tab tree implemented:
- the asset Components tab now keeps top-level roster rows primary and renders attached child component rows directly below their parent rows.
- expected child template rows are visible under parent rows with expected/tracked/removed/remaining counts.
- detached expected child components remain visible under their parent using the child ancestry snapshot.
- damaged and needs-attention component rows now show inline issue badges on the asset Components tab.
- existing top-level `Expected`, `Expected (Tracked)`, `Extra`, `Custom`, and `Removed` classifications remain unchanged; child rows add child-context text and indentation.
- implemented the Sprint 12 review choice as expanded-by-default child rows so validation targets are visible without adding a new collapse UI.
- Sprint 12 focused verification passed:
- `docker compose exec app php artisan test --env=testing tests/Feature/Assets/Ui/ComponentHistoryTest.php --filter=AssetComponentsTabRendersHierarchy`
- result: `1` test passed, `13` assertions.
- Existing asset component tab regression passed:
- `docker compose exec app php artisan test --env=testing tests/Feature/Assets/Ui/ComponentHistoryTest.php`
- result: `8` tests passed, `43` assertions.
- Related hierarchy/spec regression passed:
- `docker compose exec app php artisan test --env=testing tests/Feature/Components/Ui/ShowComponentTest.php tests/Feature/Components/Domain/ComponentExpectedSubcomponentMaterializationTest.php tests/Feature/ComponentDerivedAttributeResolutionTest.php`
- result: `25` tests passed, `143` assertions.
- `git diff --check` passed with line-ending warnings only.
- Sprint 13 component definition and model-number preview polish implemented:
- added static hierarchy overlap warnings when a parent component definition and an expected child definition both contribute the same numeric calculated spec.
- component definition edit pages now show non-blocking overlap warnings near expected subcomponent management.
- model-number specification rows now preview expected child structure for selected component definitions, including child/freeform label, quantity, part code, and component-definition links where permitted.
- model-number specification rows also show overlap warnings for selected definitions.
- implemented the preview/link review choice only; no inline nested expected-subcomponent editor was added to model-number specification pages.
- Sprint 13 focused verification passed:
- `docker compose exec app php artisan test --env=testing tests/Feature/Models/ModelSpecificationComponentPreviewTest.php tests/Feature/Settings/ComponentDefinitionSettingsTest.php --filter='expected_component_child_preview|HierarchyOverlapWarning'`
- result: `2` tests passed, `20` assertions.
- adjacent model/spec regression passed:
- `docker compose exec app php artisan test --env=testing tests/Feature/Models/ModelSpecificationComponentPreviewTest.php tests/Feature/Settings/ComponentDefinitionSettingsTest.php`
- result: `18` tests passed, `96` assertions.
- related spec resolver regression passed:
- `docker compose exec app php artisan test --env=testing tests/Feature/ComponentDerivedAttributeResolutionTest.php`
- result: `12` tests passed, `43` assertions.
- broader model-spec UI regression initially exposed a stale CSRF fixture in `ModelSpecificationUiTest`; the fixture now disables `VerifyCsrfToken` for mutating test requests.
- broader model-spec UI regression passed after the fixture update:
- `docker compose exec app php artisan test --env=testing tests/Feature/Models/ModelSpecificationUiTest.php tests/Feature/Models/ModelNumberComponentTemplateManagementTest.php`
- result: `4` tests passed, `26` assertions.
- Sprint 14 read-only conversion preview implemented:
- added `ComponentHierarchyConversionPreviewService` to scan component definitions, model-number expected-component templates, existing expected-subcomponent templates, and numeric calculated-spec overlap evidence.
- added the `component-hierarchy:preview-conversion` Artisan command with table output and `--json` full-report output.
- detection is conservative: parents need existing children or top-level expected usage plus parent/assembly naming, children can come from existing child usage, `subcomponent_only`, or embedded/serviceable child naming, and suggestions require same-model-number co-occurrence as flat expected components.
- existing parent/child templates are not suggested again, but existing numeric calculated-spec overlaps are reported.
- no write mode was added; Sprint 15 remains the review gate for any optional conversion write path.
- Sprint 14 focused verification passed after cache clear and testing DB preflight:
- `docker compose exec app php artisan test --env=testing tests/Feature/Components/Console/ComponentHierarchyConversionPreviewTest.php`
- result: `3` tests passed, `21` assertions.
- related Sprint 13 overlap-warning regression passed:
- `docker compose exec app php artisan test --env=testing tests/Feature/Models/ModelSpecificationComponentPreviewTest.php tests/Feature/Settings/ComponentDefinitionSettingsTest.php --filter='expected_component_child_preview|HierarchyOverlapWarning'`
- result: `2` tests passed, `20` assertions.
- local clone validation ran against `local|mysql|snipeit_prod_work`; the preview scanned `2` active component definitions and `0` model-number component templates, so it emitted no conversion suggestions from that clone.
- manual before/after counts around the local clone preview command stayed unchanged at `2|1|0` for `component_definitions|component_definition_subcomponent_templates|model_number_component_templates`.
- Sprint 15 selected-pair conversion write tooling implemented:
- added `ComponentHierarchyConversionApplyService` and `component-hierarchy:apply-conversion`.
- the command requires explicit `--pair=parent_definition_id:child_definition_id` selections and never applies all preview suggestions automatically.
- the command defaults to dry-run and only writes when `--apply` is passed.
- apply mode creates only selected pairs that are still current preview suggestions; stale, already-existing, unsupported, or filtered pairs are reported as unavailable.
- created templates store conversion provenance in `metadata_json`, including source model-number evidence, confidence, reasons, and `applied_at`.
- apply output includes created template IDs and a rollback tinker example for deleting those exact `component_definition_subcomponent_templates` rows before dependent expected-subcomponent states are created.
- Sprint 15 focused verification passed after cache clear and testing DB preflight:
- `docker compose exec app php artisan test --env=testing tests/Feature/Components/Console/ComponentHierarchyConversionPreviewTest.php`
- result: `6` tests passed, `35` assertions.
- local clone validation remained dry-run only against `local|mysql|snipeit_prod_work`; because the clone has no current conversion suggestions, `component-hierarchy:apply-conversion --pair=2:1` reported the pair unavailable, created `0` templates, and before/after counts stayed `2|1|0`.
- focused conversion/settings/spec regression passed:
- `docker compose exec app php artisan test --env=testing tests/Feature/Components/Console/ComponentHierarchyConversionPreviewTest.php tests/Feature/Settings/ComponentDefinitionSettingsTest.php tests/Feature/ComponentDerivedAttributeResolutionTest.php`
- result: `31` tests passed, `143` assertions.
- Sprint 16 documentation and regression wrap-up completed:
- added `docs/component-hierarchy-operations.md` as the operator/admin reference for hierarchy setup, model-number preview behavior, operator workflows, warnings, spec precedence, conversion commands, and current limits.
- updated `docs/fork-notes.md`, `docs/plans/component-hierarchy-sprint-implementation-plan.md`, and this progress log for Sprint 16 completion.
- corrected stale adjacent coverage in `ComponentCompanyScopingTest`; component definitions no longer have `company_id`, so the fixture no longer writes the removed column.
- focused hierarchy/domain/spec/conversion verification passed after cache clear and testing DB preflight:
- `docker compose exec app php artisan test --env=testing tests/Feature/Components/Domain/ComponentHierarchyFoundationTest.php tests/Feature/Components/Domain/ComponentExpectedSubcomponentMaterializationTest.php tests/Feature/Components/Domain/ComponentChildDetachmentTest.php tests/Feature/Components/Domain/ComponentParentMoveCascadeTest.php tests/Feature/Components/Domain/ComponentLifecycleConditionSplitTest.php tests/Feature/Components/Domain/ComponentInstallConditionWarningTest.php tests/Feature/Components/Domain/ComponentLifecycleServiceTest.php tests/Unit/Services/ComponentInstanceAttributeManagerTest.php tests/Feature/ComponentDerivedAttributeResolutionTest.php tests/Feature/Components/Console/ComponentHierarchyConversionPreviewTest.php`
- result: `55` tests passed, `246` assertions.
- focused UI/API/model/settings hierarchy verification passed:
- `docker compose exec app php artisan test --env=testing tests/Feature/Assets/Ui/ComponentHistoryTest.php tests/Feature/Assets/Ui/SellingStateComponentWarningTest.php tests/Feature/Assets/Ui/ReadyForSaleWarningTest.php tests/Feature/Components/Ui/ShowComponentTest.php tests/Feature/Components/Ui/ComponentBrowserWorkflowTest.php tests/Feature/Components/Ui/ComponentWorkflowPagesTest.php tests/Feature/Components/Api/ComponentLifecycleApiTest.php tests/Feature/Components/Api/ComponentLifecycleActionTest.php tests/Feature/Settings/ComponentDefinitionSettingsTest.php tests/Feature/Models/ModelSpecificationComponentPreviewTest.php tests/Feature/Models/ModelNumberComponentTemplateManagementTest.php tests/Feature/Models/ModelSpecificationUiTest.php`
- result: `68` tests passed, `470` assertions.
- broader adjacent component registry/file/company-scope and work-order verification passed:
- `docker compose exec app php artisan test --env=testing tests/Feature/Components/Api/ComponentFileTest.php tests/Feature/Components/Api/ComponentIndexTest.php tests/Feature/Components/Api/ComponentInstanceFilesTest.php tests/Feature/Components/Api/DeleteComponentTest.php tests/Feature/Components/Console/AgeTrayComponentsTest.php tests/Feature/Components/Domain/ComponentCompanyScopingTest.php tests/Feature/Components/Ui/ComponentIndexTest.php tests/Feature/Components/Ui/DeleteComponentTest.php tests/Feature/Components/Ui/EditComponentTest.php tests/Feature/Components/Ui/StoreComponentWithFullMultipleCompanySupportTest.php tests/Feature/WorkOrders/Domain/WorkOrderVisibilityTest.php tests/Feature/WorkOrders/Portal/PortalWorkOrdersTest.php tests/Feature/WorkOrders/Ui/WorkOrderAssetsAndTasksTest.php tests/Feature/WorkOrders/Ui/WorkOrderNavigationTest.php tests/Feature/WorkOrders/Ui/WorkOrdersControllerTest.php`
- result: `45` tests passed, `186` assertions.
- no additional conversion `--apply` command was run against `snipeit_prod_work`; write-mode conversion remains limited to isolated PHPUnit coverage unless explicitly approved against a selected clone subset.
- Review follow-up fixes completed for the full-implementation findings:
- parent moves to tray/stock now carry attached child rows off the old asset while preserving the parent-child relationship; destruction-pending parent moves lock attached children into destruction-pending, and final parent destruction cascades destroyed state to those children.
- calculated specs now include assumed expected subcomponent template contributions until those children are materialized, so a note-only Track action does not change the calculated value.
- `placement_mode` is now enforced for top-level asset expectations/install paths and expected subcomponent templates: `subcomponent_only` definitions cannot be expected or installed directly on assets, and `asset_only` definitions cannot be selected as subcomponents.
- final `markDestroyed()` now requires the component to already be destruction-pending and requires either a destruction note or verification payload; API calls receive a 422 instead of bypassing the guard.
- focused review-regression verification passed:
- `docker compose exec app php artisan test --env=testing tests/Feature/Components/Domain/ComponentParentMoveCascadeTest.php tests/Feature/Components/Domain/ComponentLifecycleConditionSplitTest.php tests/Feature/Components/Domain/ComponentHierarchyFoundationTest.php tests/Feature/ComponentDerivedAttributeResolutionTest.php tests/Feature/Components/Api/ComponentLifecycleApiTest.php tests/Feature/Models/ModelNumberComponentTemplateManagementTest.php`
- result: `45` tests passed, `187` assertions.
- adjacent hierarchy/UI/API verification passed:
- `docker compose exec app php artisan test --env=testing tests/Feature/Components/Domain/ComponentExpectedSubcomponentMaterializationTest.php tests/Feature/Components/Domain/ComponentInstallConditionWarningTest.php tests/Feature/Components/Api/ComponentLifecycleActionTest.php tests/Feature/Components/Ui/ComponentBrowserWorkflowTest.php tests/Feature/Settings/ComponentDefinitionSettingsTest.php tests/Feature/Models/ModelSpecificationComponentPreviewTest.php tests/Feature/Models/ModelSpecificationUiTest.php`
- result: `40` tests passed, `242` assertions.
- `docker compose exec app php artisan view:cache` passed.
- `git diff --check` passed with line-ending warnings only.
- Local dev MySQL schema follow-up:
- `https://dev.inbit/hardware/1` exposed `SQLSTATE[42S02]` because `snipeit_prod_work.component_instance_attributes` had not been created outside the testing DB.
- preflight confirmed `APP_ENV=local`, `DB_CONNECTION=mysql`, `DB_DATABASE=snipeit_prod_work`; `migrate:status` showed only `2026_05_19_100000_create_component_instance_attributes_table` pending.
- ran `docker compose exec app php artisan migrate`; the migration completed and is now batch `[7] Ran`.
- cleared Laravel caches and a CLI HTTP check against `https://dev.inbit/hardware/1` now reaches the app and redirects to login instead of throwing the missing-table error.

# Session Progress (2026-05-07)

## Addendum (2026-05-07 Codex)
- Session kickoff: re-read `AGENTS.md`, `PROGRESS.md`, and `docs/fork-notes.md` before starting the component hierarchy implementation investigation.
- Created `docs/agents/agents-addendum-2026-05-07-session-init.md` for this session.
- Current branch baseline at initialization: `master` on commit `4ad83dd3d`.
- Existing local worktree changes remain present from prior environment/prod-clone work plus the uncommitted 2026-05-06 initialization docs; these were left untouched.
- Current task focus: compare `docs/plans/component-hierarchy-subcomponents-plan.md` against the implemented flat component workflow and identify misalignments, risks, and implementation guidance before coding.
- Investigation outcome before implementation:
- the subcomponent plan is still directionally aligned with the current codebase, especially the decision to reuse `ComponentInstance` rather than creating a separate subcomponent entity.
- current code remains strongly flat: asset attachment is represented by `component_instances.current_asset_id`, asset rosters come from `Asset::trackedComponents()`, and calculated attributes aggregate flat roster rows.
- the biggest implementation misalignment is lifecycle/status semantics: current `status` mixes placement and operational attention (`installed`, `in_stock`, `needs_verification`, `defective`), while the plan requires separate lifecycle placement and condition/attention state.
- current `markDefective()` and `flagNeedsVerification()` flows detach components from assets, which conflicts with the planned rule that attached damaged/needs-attention parts should stay attached and still contribute to specs.
- the plan should add parent context to component events, not only instance ancestry fields, so parent attach/detach/move history can be queried without relying entirely on JSON payloads.
- environment check: active `.env` is `APP_ENV=local`, `DB_CONNECTION=mysql`, `DB_DATABASE=snipeit_prod_work`; `bootstrap/cache/config.php` is currently absent.
- User clarified hierarchy policy: damaged or needs-attention parts must not block install/attach or selling-state transitions; those flows should warn and allow confirmation, while destroyed components should be lockable with destruction note or verification evidence.
- Updated `docs/plans/component-hierarchy-subcomponents-plan.md` with the warning-not-blocking policy and destroyed-component lock/audit expectations.
- Created `docs/plans/component-hierarchy-sprint-implementation-plan.md` as a standalone review-gated sprint plan so future implementation can proceed in small validatable increments without relying on chat history.
- Reviewed sprint plan against the original hierarchy plan and tightened it:
- Sprint 3 now only promises attached and assumed expected child display, leaving removed/detached rows for later state-aware sprints.
- Sprint 5/6 now explicitly forbid cloning old parent event history or introducing live inherited-history behavior.
- lifecycle/condition field naming is now a review choice before schema work instead of being deferred until later implementation.
- Sprint 0 baseline started on branch `codex/component-hierarchy-sprints` with existing dirty local env/prod-clone artifacts left in place.
- Environment/config preflight:
- active `.env`: `APP_ENV=local`, `DB_CONNECTION=mysql`, `DB_DATABASE=snipeit_prod_work`
- `bootstrap/cache/config.php` was absent before cache clearing
- `docker compose exec app php artisan optimize:clear` passed
- testing Laravel config resolves to `APP_ENV=testing`, `DB_CONNECTION=sqlite`, `DB_DATABASE=/var/www/html/database/database.sqlite`
- Focused baseline verification passed:
- `docker compose exec app php artisan test --env=testing tests/Feature/ComponentDerivedAttributeResolutionTest.php tests/Feature/Assets/Ui/ComponentHistoryTest.php tests/Feature/Components/Ui/ShowComponentTest.php tests/Feature/Components/Ui/ComponentWorkflowPagesTest.php tests/Feature/Components/Ui/ComponentBrowserWorkflowTest.php tests/Feature/Components/Domain/ComponentLifecycleServiceTest.php`
- result: `31` tests passed, `210` assertions
- Sprint 1 persistence foundation implemented:
- added `component_instances.parent_component_instance_id`, `root_asset_id`, materialized-expected flags/reason, and ancestry snapshot fields.
- added `component_definitions.placement_mode` with model-level allowed values `asset_only`, `subcomponent_only`, and `either`; default remains `either` per the approved sprint direction.
- extended `ComponentInstance` with parent/child/root/ancestry relations plus hard one-level hierarchy validation.
- updated `ComponentLifecycleService` so current top-level install/remove/stock/verification/destruction flows keep `root_asset_id` and parent linkage consistent for flat components.
- added `ComponentHierarchyFoundationTest` covering `asset -> component -> subcomponent`, rejection of deeper nesting, rejection of reparenting a component that already has children, top-level lifecycle root maintenance, and placement mode validation.
- Sprint 1 verification passed after `docker compose exec app php artisan optimize:clear` and testing DB preflight:
- `docker compose exec app php artisan test --env=testing tests/Feature/Components/Domain/ComponentHierarchyFoundationTest.php`
- result: `5` tests passed, `21` assertions
- focused baseline rerun passed:
- `docker compose exec app php artisan test --env=testing tests/Feature/ComponentDerivedAttributeResolutionTest.php tests/Feature/Assets/Ui/ComponentHistoryTest.php tests/Feature/Components/Ui/ShowComponentTest.php tests/Feature/Components/Ui/ComponentWorkflowPagesTest.php tests/Feature/Components/Ui/ComponentBrowserWorkflowTest.php tests/Feature/Components/Domain/ComponentLifecycleServiceTest.php`
- result: `31` tests passed, `210` assertions
- MySQL migration SQL sanity check passed in pretend mode:
- `docker compose exec app php artisan migrate --pretend --path=database/migrations/2026_05_07_120000_add_component_hierarchy_foundation.php`
- Sprint 1 review notes:
- no new blockers were found.
- parent move/detach cascades are intentionally still deferred to the later movement sprint.
- parent context on component events remains a review choice for Sprint 4 rather than being silently added here.
- lifecycle/condition split naming remains planned as `lifecycle_status` plus `condition_status` unless changed at the next relevant review gate.
- Sprint 2 expected subcomponents implemented:
- added `component_definition_subcomponent_templates` for definition-level expected child templates.
- added `ComponentDefinitionSubcomponentTemplate` model, factory, and `ComponentDefinition` relations.
- added same-form component definition editor support for expected subcomponent create/update/delete/reorder.
- expected child rows can reference another component definition or use a freeform expected name.
- deleting an expected subcomponent template does not delete tracked component instances.
- existing model-number expected-component management remains unchanged.
- Sprint 2 verification passed after `docker compose exec app php artisan optimize:clear` and testing DB preflight:
- `docker compose exec app php artisan test --env=testing tests/Feature/Settings/ComponentDefinitionSettingsTest.php`
- result: `12` tests passed, `57` assertions
- `docker compose exec app php artisan view:cache` passed
- `docker compose exec app php artisan test --env=testing tests/Feature/Models/ModelNumberComponentTemplateManagementTest.php tests/Feature/Components/Domain/ComponentHierarchyFoundationTest.php`
- result: `7` tests passed, `36` assertions
- focused baseline rerun passed:
- `docker compose exec app php artisan test --env=testing tests/Feature/ComponentDerivedAttributeResolutionTest.php tests/Feature/Assets/Ui/ComponentHistoryTest.php tests/Feature/Components/Ui/ShowComponentTest.php tests/Feature/Components/Ui/ComponentWorkflowPagesTest.php tests/Feature/Components/Ui/ComponentBrowserWorkflowTest.php tests/Feature/Components/Domain/ComponentLifecycleServiceTest.php`
- result: `31` tests passed, `210` assertions
- MySQL migration SQL sanity check passed in pretend mode:
- `docker compose exec app php artisan migrate --pretend --path=database/migrations/2026_05_07_130000_create_component_definition_subcomponent_templates.php`
- Sprint 2 review notes:
- no new blockers were found.
- Sprint 2 uses the component definition editor as the only management surface for expected subcomponents; read-only previews from model-number screens remain a possible later choice, not implemented.
- freeform expected child rows are implemented now, matching the sprint plan.
- expected child templates are definition-level only; no asset/component instance child state exists until Sprint 4.
- Sprint 3 component detail child structure implemented:
- component detail now eager-loads attached child components and definition-level expected subcomponent templates.
- component detail now renders a read-only inline `Child Structure` section with `Attached Child Components` and `Expected Subcomponents`.
- tracked child rows link to the normal component detail page; no separate subcomponent detail page was added.
- expected child rows remain assumed definition rows only; removed/detached/materialized child state remains deferred to Sprint 4.
- Sprint 3 verification passed after `docker compose exec app php artisan optimize:clear` and testing DB preflight:
- `docker compose exec app php artisan test --env=testing tests/Feature/Components/Ui/ShowComponentTest.php`
- result: `8` tests passed, `53` assertions
- `docker compose exec app php artisan view:cache` passed
- `docker compose exec app php artisan test --env=testing tests/Feature/Components/Domain/ComponentHierarchyFoundationTest.php tests/Feature/Settings/ComponentDefinitionSettingsTest.php`
- result: `17` tests passed, `78` assertions
- focused baseline rerun passed:
- `docker compose exec app php artisan test --env=testing tests/Feature/ComponentDerivedAttributeResolutionTest.php tests/Feature/Assets/Ui/ComponentHistoryTest.php tests/Feature/Components/Ui/ShowComponentTest.php tests/Feature/Components/Ui/ComponentWorkflowPagesTest.php tests/Feature/Components/Ui/ComponentBrowserWorkflowTest.php tests/Feature/Components/Domain/ComponentLifecycleServiceTest.php`
- result: `33` tests passed, `226` assertions
- Sprint 3 review notes:
- no new blockers were found.
- the child structure is inline and visible by default; no collapsible/toggle UI was added.
- no operational child actions were added in this sprint.
- expected rows are not matched against tracked children yet, so seeing both an expected row and an attached tracked child is possible until the materialization/state sprint adds explicit state.
- Asset add definition-backed component follow-up:
- user reported `SQLSTATE[23000]: Integrity constraint violation: 1048 Column 'display_name' cannot be null` when creating a new component from an existing asset.
- root cause: `ComponentInstance` creation checked the `display_name` accessor, which could return the component definition name without setting the raw non-null `display_name` database attribute.
- fixed the create hook to inspect/set the raw `display_name` attribute from the selected component definition before insert.
- added regression coverage for creating and installing a definition-backed component from the asset add page without a custom name.
- verification passed:
- `docker compose exec app php artisan test --env=testing tests/Feature/Components/Ui/ComponentBrowserWorkflowTest.php --filter=testAssetAddPageCanCreateDefinitionBackedComponentWithoutCustomName`
- result: `1` test passed, `5` assertions
- `docker compose exec app php artisan test --env=testing tests/Feature/Components/Ui/ComponentBrowserWorkflowTest.php`
- result: `7` tests passed, `51` assertions
- focused baseline rerun passed:
- `docker compose exec app php artisan test --env=testing tests/Feature/ComponentDerivedAttributeResolutionTest.php tests/Feature/Assets/Ui/ComponentHistoryTest.php tests/Feature/Components/Ui/ShowComponentTest.php tests/Feature/Components/Ui/ComponentWorkflowPagesTest.php tests/Feature/Components/Ui/ComponentBrowserWorkflowTest.php tests/Feature/Components/Domain/ComponentLifecycleServiceTest.php`
- result: `34` tests passed, `231` assertions
- Sprint 4 materialize expected child implemented:
- added `component_expected_subcomponent_states` with parent component/template uniqueness, `removed_qty`, and `materialized_qty`.
- added `ComponentExpectedSubcomponentState`, factory support, and `ComponentExpectedSubcomponentService::materializeAttachedChild()`.
- materializing an expected child now creates an installed child `ComponentInstance` attached to the parent component, inherits current/root asset context, stores `is_materialized_expected`, records `materialized_reason`, and writes source-template metadata into the created event payload.
- component detail expected-subcomponent rows now show expected/tracked/removed/remaining counts and expose an explicit `Track` form while remaining quantity exists.
- applied the Sprint 4 migration to the local dev MySQL clone (`snipeit_prod_work`) after the MySQL pretend output was checked.
- Sprint 4 verification passed after `docker compose exec app php artisan optimize:clear` and testing DB preflight:
- `docker compose exec app php artisan test --env=testing tests/Feature/Components/Domain/ComponentExpectedSubcomponentMaterializationTest.php tests/Feature/Components/Ui/ShowComponentTest.php`
- result: `11` tests passed, `82` assertions
- broader component workflow/settings regression passed:
- `docker compose exec app php artisan test --env=testing tests/Feature/Assets/Ui/ComponentHistoryTest.php tests/Feature/Components/Ui/ComponentWorkflowPagesTest.php tests/Feature/Components/Ui/ComponentBrowserWorkflowTest.php tests/Feature/Components/Domain/ComponentHierarchyFoundationTest.php tests/Feature/Settings/ComponentDefinitionSettingsTest.php`
- result: `35` tests passed, `215` assertions
- `docker compose exec app php artisan view:cache` passed.
- final `docker compose exec app php artisan optimize:clear` passed.
- Sprint 4 review notes:
- no new blockers were found.
- the Sprint 4 state schema intentionally includes `materialized_qty` in addition to the likely-schema `removed_qty` because the feature requirements call for both removed and materialized accounting.
- the first UI trigger is explicit `Track`, not note-only or service-only.
- detaching/removing child components and keeping removed expected-child rows visible remains Sprint 5.
- current materialization only requires the parent to be installed; poor/broken/damaged condition values are not blocked.
- Sprint 5 detach child to tray or stock implemented:
- `ComponentLifecycleService::removeToTray()` and `moveToStock()` now detect attached child components and close their ancestry snapshot before clearing `parent_component_instance_id`.
- `ComponentLifecycleService::installIntoAsset()` now uses the same snapshot handling if an existing child is moved directly to another asset through an existing transfer route.
- detached children keep `ancestry_parent_component_instance_id`, `ancestry_attached_through_at`, and `ancestry_attached_through_event_id` pointing to the detach event.
- materialized expected children that are detached transfer parent expected-subcomponent state from `materialized_qty` to `removed_qty`.
- parent component detail now exposes child row actions for `To Tray` and `To Stock`.
- parent component detail now keeps detached expected child components visible in a `Removed Expected Child Components` section.
- Sprint 5 verification passed after `docker compose exec app php artisan optimize:clear` and testing DB preflight:
- `docker compose exec app php artisan test --env=testing tests/Feature/Components/Domain/ComponentChildDetachmentTest.php tests/Feature/Components/Ui/ShowComponentTest.php`
- result: `16` tests passed, `110` assertions
- broader component workflow/settings regression passed:
- `docker compose exec app php artisan test --env=testing tests/Feature/Assets/Ui/ComponentHistoryTest.php tests/Feature/Components/Ui/ComponentWorkflowPagesTest.php tests/Feature/Components/Ui/ComponentBrowserWorkflowTest.php tests/Feature/Components/Domain/ComponentHierarchyFoundationTest.php tests/Feature/Components/Domain/ComponentExpectedSubcomponentMaterializationTest.php tests/Feature/Settings/ComponentDefinitionSettingsTest.php`
- result: `37` tests passed, `230` assertions
- `docker compose exec app php artisan view:cache` passed.
- Sprint 5 review notes:
- no new blockers were found.
- no schema migration was needed for Sprint 5.
- detach notes remain optional because existing workflow routes already treat notes as optional.
- the implemented UI path is parent detail for both tray and stock; child detail still has the existing generic tray workflow.
- direct child transfer to another asset is covered defensively so existing transfer routes do not bypass ancestry snapshots.
- Sprint 6 still owns moving attached children when a parent moves.
- Sprint 6 parent move carries attached children implemented:
- `ComponentLifecycleService::installIntoAsset()` now captures currently attached installed child components when a top-level parent moves to another asset.
- attached children keep `parent_component_instance_id` and move to the destination asset with updated `current_asset_id` and `root_asset_id`.
- detached tray/stock children remain detached and do not move when the old parent later moves.
- parent `installed` events include `moved_child_component_ids` and `moved_child_count` in the payload.
- each moved child receives an individual `moved_with_parent` event with a pointer to the parent component and parent event.
- Sprint 6 verification passed after `docker compose exec app php artisan optimize:clear` and testing DB preflight:
- `docker compose exec app php artisan test --env=testing tests/Feature/Components/Domain/ComponentParentMoveCascadeTest.php tests/Feature/Components/Domain/ComponentChildDetachmentTest.php`
- result: `10` tests passed, `53` assertions
- broader component workflow/settings regression passed:
- `docker compose exec app php artisan test --env=testing tests/Feature/Assets/Ui/ComponentHistoryTest.php tests/Feature/Components/Ui/ComponentWorkflowPagesTest.php tests/Feature/Components/Ui/ComponentBrowserWorkflowTest.php tests/Feature/Components/Ui/ShowComponentTest.php tests/Feature/Components/Domain/ComponentHierarchyFoundationTest.php tests/Feature/Components/Domain/ComponentExpectedSubcomponentMaterializationTest.php tests/Feature/Settings/ComponentDefinitionSettingsTest.php`
- result: `47` tests passed, `307` assertions
- `docker compose exec app php artisan view:cache` passed.
- Sprint 6 review notes:
- no schema migration was needed.
- selected event strategy is both parent summary and individual child movement events.
- no live inherited-history mechanism was introduced; child event history only gets its own movement event.
- Sprint 7 placement/condition split implemented:
- added `component_instances.lifecycle_status` and `condition_status` with backfill from existing `status` and `condition_code` values.
- kept the old `status` and `condition_code` columns as compatibility/history fields while new code uses lifecycle/condition helpers for placement and physical/attention state.
- `needs_verification` now maps to lifecycle `in_stock` plus condition `needs_attention`; `defective` now maps to lifecycle `in_stock` plus condition `damaged`.
- damaging or flagging an attached component now keeps it attached to its asset/parent instead of detaching it.
- damaging a tray or stock component now keeps its tray/stock placement instead of converting placement into a defect status.
- damaged or needs-attention stock/tray components can still be installed; destroyed lifecycle states remain terminal for normal install/attach.
- component detail and install/add workflows now show lifecycle and condition separately where relevant, with `Needs Attention` and `Damaged` wording for the new condition states.
- applied the additive Sprint 7 migration to the local dev MySQL clone (`snipeit_prod_work`).
- Sprint 7 verification passed after `docker compose exec app php artisan optimize:clear` and testing DB preflight:
- `docker compose exec app php artisan test --env=testing tests/Feature/Components/Domain/ComponentLifecycleConditionSplitTest.php tests/Feature/Components/Domain/ComponentLifecycleServiceTest.php tests/Feature/Components/Ui/ComponentBrowserWorkflowTest.php tests/Feature/Components/Ui/ShowComponentTest.php tests/Feature/Components/Api/ComponentLifecycleActionTest.php tests/Feature/Components/Console/AgeTrayComponentsTest.php`
- result: `31` tests passed, `209` assertions
- broader hierarchy/component regression passed:
- `docker compose exec app php artisan test --env=testing tests/Feature/Assets/Ui/ComponentHistoryTest.php tests/Feature/Components/Ui/ComponentWorkflowPagesTest.php tests/Feature/Components/Ui/ComponentBrowserWorkflowTest.php tests/Feature/Components/Ui/ShowComponentTest.php tests/Feature/Components/Domain/ComponentHierarchyFoundationTest.php tests/Feature/Components/Domain/ComponentExpectedSubcomponentMaterializationTest.php tests/Feature/Components/Domain/ComponentChildDetachmentTest.php tests/Feature/Components/Domain/ComponentParentMoveCascadeTest.php tests/Feature/Components/Domain/ComponentLifecycleConditionSplitTest.php tests/Feature/Components/Api/ComponentLifecycleActionTest.php tests/Feature/Components/Console/AgeTrayComponentsTest.php tests/Feature/Settings/ComponentDefinitionSettingsTest.php`
- result: `67` tests passed, `416` assertions
- `docker compose exec app php artisan view:cache` passed.
- `docker compose exec app php artisan migrate --force` applied `2026_05_07_150000_add_component_lifecycle_and_condition_statuses` to dev MySQL.
- final `docker compose exec app php artisan optimize:clear` passed.
- `git diff --check` passed with line-ending warnings only.
- Sprint 7 review notes:
- no new blockers were found.
- compatibility with old `status` values is implemented as a durable mapping layer for now, not a removal of the old column.
- warning confirmations for installing/attaching damaged or needs-attention components remain Sprint 8.
- selling-state warnings remain unimplemented because component-to-asset sale readiness integration is a separate warning-flow sprint.

## Sprint 8 Install/Attach Warning Flow
- Added shared condition-warning confirmation behavior for normal component install/attach flows.
- Damaged and needs-attention components now warn and require explicit confirmation before installation or attachment proceeds.
- Confirmed damaged or needs-attention installs proceed and keep the component condition state.
- Sold/returned components now warn and require explicit lifecycle confirmation before installation or attachment proceeds.
- Confirmed sold/returned installs proceed and move the component back to attached placement.
- Destroyed and destruction-pending lifecycle states remain hard-blocked for normal install/attach even when confirmation is present.
- Web confirmation checkboxes were added to component install, asset add/install, asset new-component install, asset transfer, expected top-level transfer, and expected-subcomponent tracking flows.
- API install now accepts `condition_warning_confirmed` and `lifecycle_warning_confirmed`; missing confirmation for an affected component returns a structured warning response.
- Asset new-component registration is now wrapped in an outer transaction so a missing confirmation does not leave behind an unattached component.
- Expected component and expected subcomponent materialization now use the same warning policy because materialized expected parts start as `Needs Attention` until verified.
- Verification:
- `docker compose exec app php artisan optimize:clear` passed.
- testing preflight resolved to `APP_ENV=testing`, `DB_CONNECTION=sqlite`, `DB_DATABASE=/var/www/html/database/database.sqlite`.
- focused Sprint 8 suite passed with `28` tests and `202` assertions after the sold/returned correction.
- broader hierarchy/component regression passed with `83` tests and `509` assertions.
- `docker compose exec app php artisan view:cache` passed.
- Review notes:
- no new blockers were found.
- selected web and API behavior together rather than web-only first.
- selected explicit checkbox/boolean confirmation rather than a modal or second POST.
- sold/returned was corrected from hard-blocked to warning-confirmed installable; destroyed remains the hard lock.
- selling-state warnings remain Sprint 9 and were not implemented here.
- notes remain optional for condition-warning confirmations.

# Session Progress (2026-05-06)

## Addendum (2026-05-06 Codex)
- Session kickoff: re-read `AGENTS.md`, `PROGRESS.md`, and `docs/fork-notes.md` to recover the current fork context before making further changes.
- Created `docs/agents/agents-addendum-2026-05-06-session-init.md` for this session.
- Current branch baseline at initialization: `master` on commit `4ad83dd3d`.
- Existing local worktree changes were detected in local Docker config, upload placeholder files, prior addenda, production-clone env backups, `prodbak/`, and `storage/tmp-testtypes-reorder.js`; these were left untouched.
- Last documented active environment state from 2026-04-30 remains the production-key clone against `snipeit_prod_work`, so destructive database commands remain off-limits without explicit current-message approval and DB preflight.
- No implementation or verification has been run yet in this session.

# Session Progress (2026-04-23)

## Addendum (2026-04-23 Codex)
- Session kickoff: re-read `AGENTS.md`, `PROGRESS.md`, `docs/fork-notes.md`, and inspected the current worktree to audit the in-progress expected-baseline component redesign after the interrupted implementation/test recovery work.
- Current task focus:
- determine what parts of the expected-baseline component + calculated-spec redesign are actually implemented in code
- identify what remains partial or inconsistent before resuming verification and cleanup
- Audit result: the expected-baseline redesign is substantially implemented in code.
- Confirmed implemented surfaces:
- persistent per-asset expected baseline state (`asset_expected_component_states`) and materialization of expected rows into tracked instances
- merged asset component roster service with `Expected`, `Expected (Tracked)`, `Extra`, and `Custom` classification
- list-first asset Components tab with `Add / Install Component`, `To Tray`, `To Storage`, and `Move To Other Device` actions
- dedicated asset-scoped add/install, storage-confirmation, and direct transfer workflow pages
- direct asset-to-asset transfer flow that uses the scan resolver with manual destination fallback
- numeric component-derived attribute aggregation and reduced-baseline notices on the asset/hardware surfaces
- model-number spec workflow remains unified and component-driven
- Still partial or inconsistent:
- the model-number effective preview and related tests still reflect older wording/expectations where manual model values override derived totals, while the asset resolver now gives numeric calculated component values precedence
- older tray and component detail pages still use the previous workflow/action layout rather than the new asset-row action model
- verification is behind the code: focused tests for the expected-baseline redesign were interrupted earlier, and several current tests still assert pre-redesign behavior
- Continuation tranche implemented and verified:
- unified model-number spec preview now treats numeric component-derived values as authoritative and no longer renders the old manual-override copy
- asset `Add / Install Component` now keeps tray/storage sections but changes `New` into a single definition/custom toggle workflow on the same page
- tray and component detail pages now use dedicated workflow launch pages instead of embedded inline lifecycle forms
- component workflow routes now include GET screens for install, to-tray, to-storage, verification, and destruction flows, with safe return-to redirects back to tray/detail surfaces
- hardware detail component-tab badge parse error was fixed by replacing the inline one-line `@php(...)` assignment with a full Blade block
- sqlite test migrations are now compatible with the expected-baseline tranche by replacing the MySQL-only `update ... join` statement in `2026_04_21_180000_add_expected_baseline_asset_component_state` with a cross-database query-builder update
- Focused verification passed in Docker after `php artisan optimize:clear` plus explicit removal of `bootstrap/cache/config.php`:
- `tests/Feature/ComponentDerivedAttributeResolutionTest.php`
- `tests/Feature/Components/Ui/ComponentWorkflowPagesTest.php`
- `tests/Feature/Components/Ui/ShowComponentTest.php`
- `tests/Feature/Models/ModelSpecificationComponentPreviewTest.php`
- `tests/Feature/Assets/Ui/ComponentHistoryTest.php`
- result: `13` tests passed, `73` assertions
- Asset install UX simplified further:
- removed the model-number `Effective Specification Preview` block from the spec page and updated the remaining expected-components copy to stop referencing that preview
- merged asset add-page tray/storage installs into a single searchable `Install` picker with tray items listed before storage items
- removed `installed_as` and install-note inputs from the asset add-page install workflow
- hid the asset add-page `New Component` form behind an explicit reveal button instead of showing it by default
- stripped the asset add-page new-component form down to definition/custom choice, serial, and optional notes; source type now defaults to `manual`, condition defaults to `unknown`, and installed-as/slot fields are omitted
- kept the legacy tray/storage install POST routes as compatibility wrappers, but they now reuse the same simplified install behavior
- Focused verification passed in Docker after `php artisan optimize:clear` plus explicit removal of `bootstrap/cache/config.php`:
- `tests/Feature/Models/ModelSpecificationComponentPreviewTest.php`
- `tests/Feature/Components/Ui/ComponentWorkflowPagesTest.php`
- `tests/Feature/Components/Ui/ComponentBrowserWorkflowTest.php`
- result: `11` tests passed, `79` assertions

# Session Progress (2026-04-21)

## Addendum (2026-04-21 Codex)
- Created a full handoff update for the current component/work-order/portal stream and aligned the main plan with the actual repository state.
- Published the detailed handoff in `docs/agents/agents-addendum-2026-04-21-session-init.md` so the next contributor can resume without reconstructing prior chat history.
- Components gap-closure tranche implemented:
- hardened internal work-order FMCS behavior so internal index/show and nested write flows are company-scoped while the portal keeps explicit cross-company visibility behavior.
- narrowed the generic component API update endpoint to metadata-only edits and rejected direct lifecycle-field mutations.
- enforced tray-holder ownership on install paths and removed arbitrary tray reassignment from the public remove-to-tray API.
- added standalone model-number expected-component management routes/UI with create, update, delete, and reorder flows.
- updated the asset Components tab so Expected Components is collapsed by default behind an explicit toggle and leaves installed/history surfaces visible first.
- extended the existing scan flow with a resolver that opens component QR labels (`CMP:{qr_uid}`) while preserving asset-tag lookup behavior.
- made tray-aging escalations explicitly visible in component/event history as an automatic verification escalation rather than looking identical to a manual flag.
- fixed an exception-handler route alias bug so hidden/scoped work orders no longer explode into a 500 when route model binding fails.
- New focused verification for this tranche passed:
- `tests/Feature/WorkOrders/Ui/WorkOrdersControllerTest.php`
- `tests/Feature/WorkOrders/Portal/PortalWorkOrdersTest.php`
- `tests/Feature/Components/Ui/ComponentBrowserWorkflowTest.php`
- `tests/Feature/Components/Api/ComponentLifecycleApiTest.php`
- `tests/Feature/Components/Console/AgeTrayComponentsTest.php`
- `tests/Feature/Assets/Ui/ComponentHistoryTest.php`
- `tests/Feature/Models/ModelNumberManagementTest.php`
- `tests/Feature/Models/ModelNumberComponentTemplateManagementTest.php`
- `tests/Feature/Scan/ScanResolverTest.php`
- result: `35` tests passed, `168` assertions
- Earlier handoff snapshot before the gap-closure continuation:
- component replacement foundation is in place (`ComponentDefinition`, `ComponentInstance`, `ComponentEvent`, storage locations, expected component templates)
- global `/components` registry and detail pages are instance-based, not pooled-stock based
- asset `Components` tab is operational for tracked components
- tray state, tray aging command, and browser tray workspace are implemented
- component browser lifecycle actions are implemented:
- manual intake
- extract to tray
- remove to tray
- install
- move to stock
- verification flag/confirm
- destruction pending / destroyed
- delete when not installed
- component tags are enforced to be unique across both component tags and asset tags
- definition-level company scoping was removed
- non-functional serial-tracking UI was hidden and deferred
- internal work-order UI and authenticated read-only customer portal UI are implemented
- Remaining gaps that existed at the earlier handoff:
- work-order-driven component actions are still deferred; work orders show component activity but are not yet the operational action hub for installs/removals
- model-number component template management is still not a full operator/admin workflow on the model-number screens
- component QR/mobile scan flow exists only as groundwork; there is no dedicated component scan journey yet
- tray aging currently escalates by command/schedule and logs reminders, but there is not yet a full user-facing reminder/notification system
- broader project regression remains incomplete; only targeted suites around the touched surfaces were run
- asset UI outside the new component surfaces still has an older unrelated failure surface in the wider asset suite
- Additional reliability fix completed during handoff:
- replaced the component-detail `Install Into Asset` AJAX asset picker with a server-rendered asset dropdown after the browser showed that the selectlist was not loading on `/components/{id}`
- added regression coverage so the component detail page now proves installable assets render in the HTML
- Verification state at handoff:
- focused Phase 4 + settings suite: `21` tests passed, `82` assertions
- adjacent work-order + asset component-history suite: `16` tests passed, `69` assertions
- targeted `ShowComponentTest` rerun after the asset-picker fix: pass
- full-project regression was not run in this handoff block
- Handoff docs updated:
- `docs/agents/agents-addendum-2026-04-21-session-init.md`
- `docs/plans/components-replacement-part-traceability-work-orders.md`
- Recommended next tranche at that earlier handoff:
- move component actions into work-order/task-centered flows
- run broader regression once those workflows settle

# Session Progress (2026-04-20)

## Addendum (2026-04-20 Codex)
- Session kickoff: resumed from the component/work-order tranche and current local Docker validation work.
- CSS/asset delivery follow-up after a restart:
- confirmed the compiled CSS bundles and `public/mix-manifest.json` were present on disk and nginx could serve the CSS files directly.
- traced the unstyled pages to a stale container-level `APP_URL` inherited from the base Docker compose file, which caused Laravel to emit secure/dev-host asset URLs after container recreation.
- updated the local compose override so the app container now explicitly inherits the repo/local `APP_URL` value instead of the base dev-host default.
- recreated the local app/web containers and verified the login page was again rendering HTTP CSS links that resolve successfully.
- Database/schema follow-up after restart:
- confirmed the restarted stack was pointing at the expected local MySQL database but still had the two latest local-feature migrations pending.
- applied `2026_04_16_110000_add_display_order_to_test_types_table` and `2026_04_17_120000_create_component_traceability_tables` in the local container.
- verified the new `component_*` and `work_order*` tables now exist and the login route responds normally again.
- Component definition scope cleanup:
- removed definition-level company scoping from the model, settings controller, and settings UI so component definitions are now global catalog records rather than visibility-scoped records.
- removed the local `company_id` column from `component_definitions` via a follow-up migration and updated the instance lifecycle fallback so instance company scope is no longer inherited from definitions.
- added focused settings coverage so the component-definition create form no longer exposes a `company_id` field.
- current manual verification target remains the restarted local Docker stack; broader UI/manual validation is still in progress.

# Session Progress (2026-04-09)
- Re-read `AGENTS.md`, `PROGRESS.md`, `docs/fork-notes.md`, `TODO.md`, and the latest session addenda to resume the current workstream.
- Fixed mobile overflow in shared bulk-action toolbars and QR widget controls so list-page dropdowns/buttons stay inside the viewport on narrow screens.
- Hardened the Docker/PHPUnit workflow against hitting the live dev DB by preventing cached config from being used during test runs and documenting the required `optimize:clear` preflight.
- Reseeded the empty local dev MySQL database after explicit preflight and restored the demo baseline (`users=21`, `assets=10`, `settings=1`, `test_runs=10`, `models=10`, `statuslabels=9`).
- Enlarged the hardware detail `Test uitvoeren` call-to-action with a scoped style so it is roughly twice as tall, uses larger text, and reads as a lighter blue button for easier operator discovery.
- Verification:
- `docker compose exec app php artisan view:cache` (pass)
- `docker compose exec app php artisan test tests/Feature/Assets/Ui/ShowAssetTest.php --env=testing --filter=testDetailPageShowsRunTestButtonLinkingToTestsTab` (blocked by the existing sqlite testing DB corruption: `database disk image is malformed`)
- Model create/edit form cleanup:
- removed `Minimum Quantity`, `EOL`, and `Requestable` controls from the model form UI in both create and edit flows; these fields are now treated as deprecated UI inputs for future removal.
- added a focused create-page UI assertion to ensure the create form no longer exposes `min_amt`, `eol`, or `requestable` fields.
- updated model save behavior so deprecated fields are only changed when explicitly present in request payloads; hidden-form updates now preserve existing legacy values.
- added focused edit-page and update-flow assertions in `UpdateAssetModelsTest` to ensure hidden deprecated fields stay hidden and omitted payloads do not overwrite existing values.
- Attribute enum options ordering UX:
- replaced manual numeric sort entry on attribute version option lists with drag-and-drop row ordering.
- option rows now keep `sort_order` in hidden inputs that are auto-synchronized from row position (`0..n`) on add/remove/reorder.
- removed the standalone `Sort order` entry input from the add-option panel.
- added a submit-time confirmation warning when admins typed a new enum option in the entry row but did not click `Add to list` before saving.
- warning copy is now localized via `attribute_definitions.unsaved_option_confirm` in `en-US` and `nl-NL`.
- added lifecycle coverage to assert the version form renders drag handles and that version creation still assigns sequential sort order when option sort values are omitted.
- Verification:
- `docker compose exec app php -l resources/views/models/edit.blade.php` (pass)
- `docker compose exec app php -l tests/Feature/AssetModels/Ui/CreateAssetModelsTest.php` (pass)
- `docker compose exec app php artisan view:cache` (pass)
- `docker compose exec app php artisan test tests/Feature/AssetModels/Ui/CreateAssetModelsTest.php --env=testing` (blocked by existing sqlite testing DB corruption: `database disk image is malformed`)
- `docker compose exec app php -l resources/views/attributes/partials/options.blade.php` (pass)
- `docker compose exec app php -l tests/Feature/AttributeDefinitionLifecycleTest.php` (pass)
- `docker compose exec app php artisan view:cache` (pass)
- `docker compose exec app php artisan test tests/Feature/AttributeDefinitionLifecycleTest.php --env=testing` (blocked by existing sqlite testing DB corruption: `database disk image is malformed`)
- Hardware page runtime fix:
- fixed `htmlspecialchars(): Argument #1 ($string) must be of type string, array given` by removing translation-group key collisions for `__('Attributes')`.
- moved unsaved-option warning copy from `attributes.unsaved_option_confirm` to `attribute_definitions.unsaved_option_confirm` and deleted the conflicting top-level `attributes.php` lang files.
- verification:
- `docker compose exec app php artisan tinker --execute "dump(gettype(__('Attributes'))); dump(__('Attributes'));"` (pass)
- `docker compose exec app php artisan view:clear` (pass)
- `docker compose exec app php artisan view:cache` (pass)
- `docker compose exec app php artisan test tests/Feature/AttributeDefinitionLifecycleTest.php --env=testing` (blocked by existing sqlite testing DB corruption: `database disk image is malformed`)

# Session Progress (2026-04-07)

## Addendum (2026-04-07 Codex)
- Session kickoff: reviewed `AGENTS.md`, `PROGRESS.md`, `docs/fork-notes.md`, `TODO.md`, and the latest dated session addendum before resuming work.
- Created `docs/agents/agents-addendum-2026-04-07-session-init.md` for this session.
- Reinitialized carry-over context from the 2026-04-02 workstream:
- local dev hostname/cert flow is set up around the internal HTTPS dev hostname for LAN/mobile testing.
- hardware detail/edit cleanup pass 1 is in progress locally, including checkout-UI removal, hardware edit simplification, QR widget reduction, and follow-up layout fixes for status/quality rows.
- the hardware detail Blade parse regression from the last session was resolved by replacing inline `@php(...)` shorthand with block `@php ... @endphp`.
- Current known open items remain:
- `TODO.md` backlog: QR label layout cleanup, placeholder MPN/SKU replacement, mobile scan feedback, naming/email convention, battery-health decision, and `tests` vs `tasks` wording.
- explicit unresolved test from prior handoffs: `tests/Feature/Assets/Ui/ReadyForSaleWarningTest.php`.
- environment-level testing blockers for the touched UI work remain unchanged:
- sqlite testing DB corruption (`database disk image is malformed`) in the current container workflow.
- existing Livewire support-file-uploads bootstrap error affecting `EditAssetTest`.
- Existing local worktree changes were detected in the dev-host config files, hardware detail/edit/QR Blade files, translations, tests, and prior session docs; initialization is continuing without reverting or overwriting unrelated edits.
- Hardware detail follow-up:
- removed the current status-change note field from both the hardware detail page and the shared edit-status partial so asset notes can be redesigned later around a single consolidated note surface.
- changed the hardware detail status and quality dropdowns to submit immediately on change rather than relying on a separate quality save button.
- replaced the QR label download path again so the single download action now serves a full rendered label PNG image instead of a PDF or raw QR image.
- verification:
- `docker compose exec app php -l app/Services/QrCodeService.php` (pass)
- `docker compose exec app php -l app/Services/QrLabelService.php` (pass)
- `docker compose exec app php artisan view:cache` (pass)
- `docker compose exec app php artisan optimize:clear` (pass)
- Hardware detail tabs follow-up:
- changed the tests tab icon from a vial to the existing clipboard-check icon to better match the refurb task flow.
- added the missing `general.status_history` translation key in both English and Dutch so the history panel no longer renders the raw translation key.
- reverted the experimental phone-tab layout changes after review because they made the hardware page feel less responsive; mobile tab UX is being left for a later dedicated design pass.
- removed the upload tab's special `pull-right` float on the hardware detail page so the paperclip/upload action stays aligned with the rest of the tab list.
- added a `Test uitvoeren` / `Run Test` button directly under the hardware edit action that activates the existing Tests tab instead of navigating away from the asset page.
- restructured the test-runs index result rows into a simple grid so test labels, statuses, and notes stay vertically aligned instead of drifting as one inline-flex blob.
- replaced the hardware Tests-tab top-right `Start New Run` control with responsive actions: a desktop text button aligned upper-left and a mobile/tablet lower-right floating plus-action button that only appears while the Tests tab is active.
- increased the hardware Tests-tab mobile floating plus-action size and converted the latest-tests warning callout into a click-to-expand block with a right-side disclosure icon.
- added muted helper copy to the latest-tests warning callout so it explicitly says it can be unfolded.
- changed the hardware Tests-tab run list to a single full-width column so test runs no longer split into two columns on wide screens.
- updated `ShowAssetTest` coverage to assert the clipboard-check icon and translated status-history heading.
- Shared mobile header fix:
- reverted the temporary content-header wrapper experiment after it introduced new xs layout issues.
- restored the original standalone mobile sidebar toggle under the navbar and switched the shared header fix to a smaller xs-only rule: `h1.pagetitle` no longer keeps `pull-left` on narrow screens, so the breadcrumb/title can wrap beside the floated hamburger instead of dropping onto its own row.
- adjusted the shared content-header on xs so the section keeps a small real side padding instead of letting the inner Bootstrap row cancel it out, preserving breathing room around the breadcrumb block on narrow screens.
- verification:
- `docker compose exec app php artisan view:cache` (pass)
- `docker compose exec app php artisan test tests/Feature/Assets/Ui/ShowAssetTest.php --env=testing` (blocked by existing sqlite testing DB corruption: `database disk image is malformed`)

# Session Progress (2026-04-02)

## Addendum (2026-04-02 Codex)
- Session kickoff: reviewed `AGENTS.md`, `PROGRESS.md`, `docs/fork-notes.md`, `TODO.md`, and the most recent session addenda before starting work.
- Created `docs/agents/agents-addendum-2026-04-02-session-init.md` for this session.
- Reinitialized carry-over context from the recent March workstream:
- 2026-03-19 closed out active-tests/mobile scan follow-up work and documented one remaining local UI change in `resources/views/tests/active.blade.php`.
- 2026-03-17 finalized the single-save model-number image admin flow and removed obsolete standalone web image-admin routes.
- 2026-03-12 introduced ordered model-number default images, asset image override behavior, webshop/read APIs, and test-photo promotion into asset images.
- Current known carry-over items from repo docs remain:
- unresolved failing test noted in prior handoffs: `tests/Feature/Assets/Ui/ReadyForSaleWarningTest.php`.
- backlog items from `TODO.md` and recent handoffs include QR label layout cleanup, placeholder MPN/SKU catalog replacement, mobile scan feedback, naming/email convention, battery-health auto-calculation, and the `tests` vs `tasks` wording decision.
- Current user direction for this session:
- an initial live showcase surfaced multiple UX/content cleanup items,
- the system is being exercised primarily on a Samsung Galaxy A5,
- mobile-first usability should be treated as the main validation target for upcoming changes.
- Existing local worktree changes were detected in `PROGRESS.md`, `docs/agents/agents-addendum-2026-03-19-session-init.md`, and `resources/views/tests/active.blade.php`; initialization was logged without reverting or overwriting those unrelated edits.
- Dev host/certificate setup:
- extracted the internal dev server certificate and private key from the exported environment bundle into `docker/certs/`.
- updated local dev hostname references in Docker/nginx/local environment config so the stack can serve the internal hostname with the matching cert.
- verification and restart steps are being handled against the local stack only; no production environment changes were made.
- Hardware detail/edit cleanup pass 1:
- gave quality grading its own dedicated row on the hardware detail page while keeping status updates on the existing `hardware.status.update` endpoint.
- removed checkout-oriented hardware detail UI: the checked-out-to side panel, deployed/assignee rendering inside the status row, checkout date display, and the conditional `checkin_and_delete` delete-button copy.
- simplified the hardware edit page by removing the collapsed optional-information section, moving asset `name` into the visible main form, and moving general `notes` directly below status.
- adjusted the shared hardware status form partial so the status note stays aligned with the status control column.
- reduced the QR widget on the hardware detail page to a single download action that targets the rendered label PNG instead of the raw QR image, and removed the `Print PDF` action.
- Added focused UI regression coverage in `ShowAssetTest` and `EditAssetTest` for the new detail/edit expectations.
- Verification:
- `docker compose exec app php artisan view:cache` (pass)
- `docker compose exec app php artisan optimize:clear` (pass)
- `docker compose exec app php artisan test tests/Feature/Assets/Ui/ShowAssetTest.php --env=testing` (blocked by existing sqlite testing DB corruption: `database disk image is malformed`)
- `docker compose exec app php artisan test tests/Feature/Assets/Ui/QualityGradeDetailUpdateTest.php --env=testing` (blocked by the same existing sqlite testing DB corruption)
- `docker compose exec app php artisan test tests/Feature/Assets/Ui/EditAssetTest.php --env=testing` (blocked in current environment by an existing Livewire support-file-uploads bootstrap error before reaching the new assertions)
- Follow-up polish after manual review:
- changed the hardware detail status form wiring to use a detached form id with `form=""` attributes so the status row and quality row remain true separate rows within the page's table-like `row-new-striped` layout.
- switched the single QR download action to the generated label PDF so the downloaded file matches the actual printed output path instead of the plain QR PNG.
- added width constraints on the QR widget controls so the printer dropdown no longer overflows the panel on narrow screens.
- Verification:
- `docker compose exec app php artisan view:cache` (pass)
- `docker compose exec app php artisan optimize:clear` (pass)
- Blade parse-error follow-up:
- fixed a hardware detail view regression caused by inline `@php(...)` shorthand in the touched Blade files; replaced the shorthand with block `@php ... @endphp` in the asset detail view, QR widget partial, and shared status partial.
- Verification:
- `docker compose exec app php artisan view:clear` (pass)
- `docker compose exec app php artisan view:cache` (pass)

# Session Progress (2026-03-19)

## Addendum (2026-03-19 Codex)
- Session kickoff: reviewed `AGENTS.md`, `PROGRESS.md`, `docs/fork-notes.md`, and the most recent session addenda before starting work.
- Created `docs/agents/agents-addendum-2026-03-19-session-init.md` for this session.
- Reinitialized carry-over context from the recent March workstream:
- 2026-03-17 finalized the single-save model-number image admin flow and removed obsolete standalone web image-admin routes.
- 2026-03-12 introduced ordered model-number default images, asset image override behavior, webshop/read APIs, and test-photo promotion into asset images.
- Current known carry-over items remain documentation/decision oriented unless new implementation scope is provided:
- unresolved failing test noted in prior handoffs: `tests/Feature/Assets/Ui/ReadyForSaleWarningTest.php`.
- backlog items from recent handoffs include QR label layout cleanup, placeholder MPN/SKU catalog replacement, mobile scan feedback, naming/email convention, battery-health auto-calculation, and the `tests` vs `tasks` wording decision.
- Mobile dashboard refinement:
- restored the subtle dashboard tile icons on screens below `768px` instead of letting the AdminLTE mobile rule hide them entirely.
- adjusted the mobile dashboard card layout to keep tile copy readable while leaving room for the icon (`text-align: left`, extra right padding, slightly smaller/lighter icon treatment).
- rebuilt frontend assets with `npm run dev` so the override is present in compiled CSS.
- Follow-up refinement after visual feedback:
- added dedicated `dashboard-tile` classes in the dashboard markup so the mobile override targets dashboard cards explicitly instead of generic `small-box` cards.
- strengthened the mobile icon rule to `display: block !important`, reduced icon size, and tightened tile footer sizing so the icons stay visible on mobile.
- shortened the scan card footer copy from `Scan QR` to `Scan` so the action tile scales more consistently with the count tiles.
- Active tests reliability fix:
- aligned backend test-run update authorization with the active-tests UI so non-admin asset editors and run owners with `tests.execute` can persist pass/fail toggles, notes, and photo uploads instead of seeing the optimistic UI revert after a 403 from `TestResultController::partialUpdate`.
- added focused feature coverage for asset-editor updates on foreign runs and for non-refurbisher run owners with `tests.execute`.
- hardened the active-tests progress action bar for phones by switching the bottom progress bar to a fixed mobile layout with extra page bottom padding, avoiding the occasional sticky/top overlap behavior reported on mobile browsers.
- Verification:
- `docker compose exec app php -l app/Policies/TestRunPolicy.php` (pass)
- `docker compose exec app php -l app/Http/Controllers/TestResultController.php` (pass)
- `docker compose exec app php -l tests/Feature/Assets/PartialUpdateTestResultTest.php` (pass)
- `docker compose exec app php -l tests/Feature/Tests/ActiveTestViewTest.php` (pass)
- `docker compose exec app php artisan test tests/Feature/Assets/PartialUpdateTestResultTest.php --env=testing` (blocked by existing testing DB migration-state conflicts in the container)
- `docker compose exec app php artisan test tests/Feature/Tests/ActiveTestViewTest.php --env=testing` (blocked by the same existing testing DB migration-state conflicts)
- Testing environment repair:
- confirmed the container was incorrectly booting `--env=testing` with cached MySQL config even though `phpunit.xml` and `.env.testing` both specify sqlite.
- cleared Laravel bootstrap caches in the app container so `testing` resolves back to sqlite.
- identified the sqlite testing database file as corrupted (`database disk image is malformed`), reset only the test sqlite file after a testing DB preflight, and rebuilt the schema with `php artisan migrate --env=testing --force`.
- reran the focused test suites serially to avoid shared-sqlite corruption:
- `docker compose exec app php artisan test tests/Feature/Assets/PartialUpdateTestResultTest.php --env=testing` (pass, 8 tests / 48 assertions).
- `docker compose exec app php artisan test tests/Feature/Tests/ActiveTestViewTest.php --env=testing` (6 passed, 1 failed).
- remaining failure after environment repair is unrelated to the authorization patch:
- `scan route redirects to active tests for testers` currently redirects to `/hardware/{id}` instead of `/hardware/{id}/tests/active`.
- Practical takeaway for this environment:
- stale config cache can make testing hit MySQL instead of sqlite.
- parallel PHPUnit runs against the shared sqlite test DB are unsafe here and can corrupt the file; run sqlite-backed test commands serially.
- Scan viewport stabilization:
- kept the `/scan` camera frame visually fixed by switching `#scan-area` to a stable `4:3` aspect-ratio box instead of recalculating height from the active stream metadata and viewport height.
- removed the runtime `resizeScanArea()` logic from `resources/js/scan/index.js`; scan quality fallback still applies higher camera constraints after repeated misses, but it no longer changes the visible camera box size.
- retained overlay/canvas syncing against the rendered scan-area dimensions so scan guidance stays aligned with the fixed frame.
- rebuilt frontend assets with `npm run dev` so the updated scan bundle and view styling are present in compiled output.
- Coverage note:
- no PHPUnit coverage added for the scan viewport change; verification was limited to source review plus `npm run dev`.
- Dev DB recovery:
- investigated a fresh `/setup`-style state reported during manual testing and confirmed the live `local` MySQL database was an empty-but-migrated schema (`migrations=454`, but `settings=0`, `users=0`, `assets=0`, `companies=0`).
- verified this was distinct from the repaired sqlite testing DB: `php artisan about` still resolved `local` to MySQL and `--env=testing` to sqlite.
- confirmed the app code paths only auto-run `migrate` when setup/passport prerequisites are missing; no automatic `migrate:fresh` / `db:wipe` path was found in the current Docker app entrypoint or setup controllers.
- recovered the shared dev DB non-destructively with `docker compose exec app php artisan db:seed --force` instead of dropping tables.
- post-seed verification:
- `settings_count=1`
- `users_count=21`
- `assets_count=10`
- `test_runs_count=10`
- `models_count=10`
- `statuslabels_count=9`
- Test expectation cleanup:
- updated `tests/Feature/Tests/ActiveTestViewTest.php` so the scan-tag redirect assertion matches the intended product flow: scan lookup lands on the asset detail page (`/hardware/{id}`), not directly on the active tests screen.
- Verification:
- `docker compose exec app php artisan test tests/Feature/Tests/ActiveTestViewTest.php --env=testing` (pass, 7 tests / 23 assertions).
- Active tests mobile follow-up:
- removed the sticky/fixed positioning from the `general.progress` block in `resources/views/tests/active.blade.php` after device feedback; it now stays in normal flow below the test list instead of pinning to the viewport.
- Session handoff:
- latest pushed commit is `6b5ff364e` (`Fix Test Permissions And Scan Viewport`).
- one local follow-up remains uncommitted at session end: the `resources/views/tests/active.blade.php` change that removes sticky/fixed positioning from the bottom progress block so it stays at the end of the list on mobile.

# Session Progress (2026-03-17)

## Addendum (2026-03-17 Codex)
- Session kickoff: reviewed `AGENTS.md`, `PROGRESS.md`, and `docs/fork-notes.md` before starting work.
- Created `docs/agents/agents-addendum-2026-03-17-session-init.md` for this session.
- Re-synced carry-over context from the prior 2026-03-12 image-source/admin workstream and confirmed commit `1e4af1570` only contained UX/icon/spec layout scope.
- Verified in-progress image-source and model-number image admin UI work:
- `docker compose exec app php artisan test tests/Feature/Assets/Api/AssetImagesApiTest.php` (pass).
- `docker compose exec app php artisan test tests/Feature/Assets/PromoteTestResultPhotoToAssetImageTest.php` (pass).
- `docker compose exec app php artisan test tests/Feature/Settings/ModelNumberImageManagementTest.php` (pass).
- `docker compose exec app php artisan test tests/Unit/AssetTest.php --filter GetImageUrl` (pass).
- Ran `php -l` against touched controller/model PHP files for image-source/admin changes (all pass, no syntax errors).
- Follow-up UX update for model-number image admin:
- replaced manual order-number entry with drag-and-drop ordering UI and a dedicated reorder action.
- added client-side preview for upload input and replacement file inputs.
- added backend reorder endpoint `PATCH model-numbers/{modelNumber}/images/reorder`.
- updated `ModelNumberImageManagementTest` to validate reorder payload behavior (pass after cache clear).
- Added policy-based guardrail for destructive database commands in `AGENTS.md`:
- destructive DB commands on shared dev require explicit user approval in-message.
- mandatory DB preflight output (`APP_ENV`, `DB_CONNECTION`, `DB_DATABASE`) before any destructive execution.
- Updated `docs/demo-guide.md` and `docs/DEMO.md` to reflect explicit-approval usage rather than wrapper tooling.
- Follow-up hardening on the model-number image admin UI:
- replaced brittle table-row native drag behavior with a pointer-event drag handle flow so reorder works for both mouse and touch interactions.
- fixed stale test coverage to exercise the real web routes instead of calling controller methods directly.
- corrected first-image append ordering to start at `sort_order = 0`.
- tightened reorder validation so partial/mismatched image ID payloads are rejected instead of leaving ambiguous ordering.
- Validation rerun:
- `docker compose exec app php artisan test tests/Feature/Settings/ModelNumberImageManagementTest.php` (pass, route-level coverage).
- `docker compose exec app php artisan test tests/Feature/Assets/Api/AssetImagesApiTest.php` (pass, rerun serially).
- Reworked the model-number image admin UX into a single-save flow tied to the main model-number edit form:
- removed per-row save, save-order, and immediate upload actions from the page UX.
- image captions, replacements, reorder state, staged removals, and new-image upload now submit together with the main model-number save.
- added `ModelNumberImageSyncService` so both settings and model-context edit screens share the same image sync behavior.
- model-number image removals are now staged as `Remove` / `Undo Remove` toggles instead of immediate destructive actions.
- Validation:
- `docker compose exec app php artisan test tests/Feature/Settings/ModelNumberImageManagementTest.php` (pass, 6 tests / 31 assertions).
- `docker compose exec app php -l app/Services/ModelNumberImageSyncService.php` (pass).
- `docker compose exec app php -l app/Http/Controllers/Admin/ModelNumberController.php` (pass).
- `docker compose exec app php -l app/Http/Controllers/Admin/ModelNumberSettingsController.php` (pass).
- Follow-up cleanup after production-scope review:
- removed the obsolete standalone admin model-number image controller/routes because the UI now saves image changes only through the main model-number update flow.
- kept the API-side first-image ordering fix so API-created model-number images default to `sort_order = 0`.
- added focused API regression coverage for model-number image creation ordering.
- Validation:
- `docker compose exec app php artisan test tests/Feature/Assets/Api/ModelNumberImagesApiTest.php` (pass, serial run).
- `docker compose exec app php -l app/Http/Controllers/Api/ModelNumberImagesController.php` (pass).
- `docker compose exec app php -l routes/web.php` (pass).

# Session Progress (2026-03-12)

## Addendum (2026-03-12 Codex)
- Session kickoff: reviewed `AGENTS.md`, `PROGRESS.md`, and `docs/fork-notes.md` before starting work.
- Created `docs/agents/agents-addendum-2026-03-12-session-init.md` for this session.
- Current task: initialize from recent session files and list open TODOs/unresolved handoffs for continuation.
- Reviewed `TODO.md`, recent `docs/agents/agents-addendum-2026-*.md`, and `docs/plans/latest-tests-column-lazy-detail.md` to capture active carry-over items.
- Documented the consolidated open-point backlog in today's addendum for implementation planning.
- Updated the scan icon mapping to camera and changed the tests/test-types icon mapping from vial to clipboard in `IconHelper`.
- Added a documentation decision item to evaluate whether user-facing wording should stay "tests" or shift to "tasks" for refurb execution steps (for example cleaning and driver installation) without renaming technical internals yet.
- Updated Dutch translation `general.assets` from `Activa` to `Apparaten` for clearer hardware wording on dashboard and shared labels.
- Updated hardware detail specification mobile CSS so label/value cells stay side-by-side on small screens while values wrap aggressively, preventing overflow without forcing stacked rows.
- Expanded the hardware detail specification section to full-width (`col-md-12`) so specification values have more horizontal space and are less likely to wrap into unreadable fragments.
- Replaced the specification table layout with a separator-style stacked list (label on top, value below) to improve readability and avoid narrow two-column squeezing on small screens.
- Aligned the specification section back to the page's standard `col-md-3/col-md-9` detail-row layout while keeping each spec item vertically stacked (label line, then value line) to prevent side-by-side rendering.
- Hardened the spec layout against CSS collisions by renaming to unique classes (`asset-spec-*`) and enforcing full-width block stacking for each spec row/label/value; cleared Laravel caches with `docker compose exec app php artisan optimize:clear` to ensure updated Blade/CSS render.
- Replaced the custom spec-list approach entirely with standard detail rows per specification item (`row` + `col-md-3/9`) so rendering matches the rest of the hardware detail page and values stack predictably under one another; cleared caches again via `php artisan optimize:clear`.
- Implemented image-source architecture for webshop/read APIs:
- Added `assets.image_override_enabled` so asset-specific photos can explicitly override model-number defaults.
- Added ordered/source-aware metadata on `asset_images` (`sort_order`, `source`, `source_photo_id`) and created `model_number_images` for per-model-number default image sets.
- Backfilled defaults from existing model images into `model_number_images` and backfilled override flag for existing assets with an image.
- Added ordered resolved-image API endpoint `GET /api/v1/hardware/{asset}/images` (`api.assets.images`) returning active source + ordered image payload for webshop usage.
- Added model-number default image management API endpoints:
- `GET/POST/PUT/DELETE /api/v1/model-numbers/{modelNumber}/images` (`api.model-numbers.images.*`).
- Added test-photo promotion flow to asset overrides:
- New route `POST /hardware/{asset}/tests/{testRun}/results/{result}/photos/{photo}/promote` (`test-results.photos.promote`) copies the test photo to asset storage, creates ordered `asset_images` entry, and can enable override + set as cover.
- Updated `Asset::getImageUrl()` to resolve in this order:
- model-number defaults when override is disabled,
- asset override image when enabled (or when no defaults exist),
- then legacy model/category fallback.
- Added regression coverage:
- `tests/Feature/Assets/Api/AssetImagesApiTest.php`
- `tests/Feature/Assets/PromoteTestResultPhotoToAssetImageTest.php`
- `tests/Unit/AssetTest.php::testGetImageUrlPrefersModelNumberDefaultWhenOverrideDisabled`
- Validation run:
- `docker compose exec app php artisan migrate --force` (pass; applied `2026_03_12_130000_add_image_override_and_model_number_images`).
- `docker compose exec app php -l ...` on all touched PHP files (pass; no syntax errors).
- Targeted tests passing when run serially:
- `AssetImagesApiTest`, `PromoteTestResultPhotoToAssetImageTest`, and `AssetTest --filter GetImageUrl`.
- Note: running sqlite-backed test commands in parallel corrupts `database/database.sqlite` in this environment; reruns were executed serially after resetting that file.

# Session Progress (2026-03-05)

## Addendum (2026-03-05 Codex)
- Session kickoff: reviewed `AGENTS.md`, `PROGRESS.md`, and `docs/fork-notes.md` before starting work.
- Created `docs/agents/agents-addendum-2026-03-05-session-init.md` for this session.
- Current task: initialize context and list unresolved open points, TODOs, and handoffs from prior sessions.
- Completed open-point sweep across `TODO.md`, recent `docs/agents/agents-addendum-2026-*.md`, and `PROGRESS.md`; unresolved carry-overs are now documented in today's handoff summary.

# Session Progress (2026-03-03)

## Addendum (2026-03-03 Codex)
- Session kickoff: reviewed `AGENTS.md`, `PROGRESS.md`, and `docs/fork-notes.md` before starting work.
- Created `docs/agents/agents-addendum-2026-03-03-session-init.md` for this session.
- Current task: reinitialize context and summarize open points, TODOs, and in-progress items from recent sessions.

# Session Progress (2026-02-24)

## Addendum (2026-02-24 Codex)
- Session kickoff: reviewed `AGENTS.md`, `PROGRESS.md`, and `docs/fork-notes.md` before starting work.
- Created `docs/agents/agents-addendum-2026-02-24-session-init.md` for this session.
- Pending: confirm today's implementation scope and begin logging outcomes.

# Session Progress (2026-02-19)

## Addendum (2026-02-19 Codex)
- Session kickoff: reviewed `AGENTS.md`, `PROGRESS.md`, and `docs/fork-notes.md` before starting work.
- Created `docs/agents/agents-addendum-2026-02-19-session-init.md` for this session.
- Re-read recent addenda (`2026-02-17`, `2026-02-12`, `2026-02-10`, `2026-02-05`) to reinitialize context and carry-forward blockers.
- Current known carry-over: `tests/Feature/Assets/Ui/ReadyForSaleWarningTest.php` still fails on missing `warning` session key; empty-hardware regressions were mitigated via seed/UI/API fixes in prior session.
- Revalidated current phone test scope from runtime (`TestType::forAsset`) for seeded phone assets (`DEMO-003`, `DEMO-004`): `battery`, `bluetooth`, `display`, `front_camera`, `microphone`, `rear_camera`, `speaker`, `wifi`.
- Confirmed `face_unlock` exists as a test type but is not active for current seeded phone assets because those model capabilities do not include it.
- Product-direction decision captured: stop relying on seeders for production parity for phone checks; move to deploy-safe, idempotent sync of attribute/test definitions.
- Proposed next phone additions:
- Data fields: `imei_1`, optional `imei_2`, `has_knox`, `knox_tripped`, keep/apply `quality_grade` (`Kwaliteit A-D`).
- Test steps: `charge_port`, `sim_port`, `power_button`, `volume_buttons`, optional `home_button` only for models that actually have one.
- Tests not run in this documentation-only update block.

# Session Progress (2026-02-17)

## Addendum (2026-02-17 Codex)
- Session kickoff: reviewed `AGENTS.md`, `PROGRESS.md`, and `docs/fork-notes.md` before implementing the quality-grade workflow split.
- Created `docs/agents/agents-addendum-2026-02-17-session-init.md` for detailed session notes.
- Added `assets.quality_grade` (migration + backfill) and moved grading to the hardware detail status form as a dedicated dropdown.
- Quality choices are now standardized as `Kwaliteit A`, `Kwaliteit B`, `Kwaliteit C`, and `Kwaliteit D`.
- Legacy `condition_grade` is filtered out from spec override/detail displays and excluded from test-type scoping so grading is no longer part of test runs.
- Added feature coverage for detail-page quality updates (`tests/Feature/Assets/Ui/QualityGradeDetailUpdateTest.php`).
- Updated `docs/fork-notes.md` for this fork-level workflow change.
- Validation: `docker compose exec app php artisan migrate --force` (pass, migration `2026_02_17_090000_add_quality_grade_to_assets` applied).
- Validation: `docker compose exec app php artisan test tests/Feature/Assets/Ui/QualityGradeDetailUpdateTest.php` (pass, 2 tests).
- Additional check: `docker compose exec app php artisan test tests/Feature/Assets/Ui/ReadyForSaleWarningTest.php` still fails on missing `warning` session key (appears unrelated to this change).
- Hardened reseed UX guard for the hardware list: `DemoAssetsSeeder` now bumps `settings.updated_at` after seeding so the hardware index table key rotates on every demo reseed (including `db:seed --class=DemoAssetsSeeder`), preventing stale bootstrap-table state from hiding rows.
- Verification: `docker compose exec app php artisan db:seed --class=DemoAssetsSeeder` (pass), `settings.updated_at` timestamp changed, and `assets=10` confirmed after reseed.
- Verification: `docker compose exec app php artisan test tests/Feature/Assets/Ui/AssetIndexTest.php` (pass).
- Hardened `api.assets.index` against stale status filters after reseeds: invalid/nonexistent `status_id` values are now ignored (treated as no status filter) instead of returning an empty list.
- Added regression coverage in `tests/Feature/Assets/Api/AssetIndexTest.php::testAssetApiIndexIgnoresInvalidStatusIdFilter`.
- Validation: simulated API request as admin with `status_id=999` now returns `total=8` (previously `total=0`), confirming hardware remains visible even when stale links/bookmarks carry old status IDs.
- Fixed a frontend blocker that still caused an empty hardware table despite healthy API/data: `resources/lang/nl-NL/tests.php` contained a UTF-8 BOM that was injected into the `data-columns` attribute, triggering a jQuery/bootstrap-table init crash (`Cannot create property 'colspanIndex' on string '﻿'`).
- Validation: captured browser console before/after with headless Playwright (`SEVERE_COUNT` from `1` to `0`) and confirmed `data-columns` no longer starts with `EF BB BF`.
- Session close: products are visible again; detailed problems/solutions and handoff notes are documented in `docs/agents/agents-addendum-2026-02-17-session-init.md`.

# Session Progress (2026-02-12)

## Addendum (2026-02-12 Codex)
- Session kickoff: reviewed `AGENTS.md`, `PROGRESS.md`, and `docs/fork-notes.md` to align with current workflow before starting work.
- Created `docs/agents/agents-addendum-2026-02-12-session-init.md` for this session's detailed notes.
- Current task: documentation drift check (`README.md`, `CONTRIBUTING.md`, and `docs/*`) and report findings.
- Docs audit result: `README.md`, `CONTRIBUTING.md`, and `docs/fork-notes.md` are aligned with current fork workflow/deltas.
- Follow-up needed: `PROGRESS.md` still contains one malformed historical pasted block with literal `\\n` escapes that should be normalized in a dedicated cleanup pass.
- Dashboard UX update: added a permission-gated camera quick-action card on the home dashboard that links directly to the scan page and uses a camera glyph (no `View All` footer copy).
- Added a dedicated `camera` icon mapping in `IconHelper` for dashboard use.
- Added dashboard scan-card feature coverage and refreshed the existing dashboard access assertion to match current behavior.
- Validation: `docker compose exec app php artisan test tests/Feature/DashboardTest.php` (pass).
- Environment recovery: reseeded dev DB via `docker compose exec app php artisan migrate:fresh --seed` after the app redirected to `/setup`; verified `settings_count=1`, `users_count=16`, and root now redirects to `/login` (not `/setup`).
- Improved dev seeding for daily UX testing:
- Added assets visibility (`assets.view`) to operational seeded users and aligned role/group seed permissions to include asset visibility.
- Expanded demo user accounts (`demo_admin`, `demo_supervisor`, `demo_senior_refurbisher`, `demo_refurbisher`, `demo_user`) while keeping existing operational users.
- Expanded demo asset dataset from 4 to 10 assets across refurbishment statuses (Stand-by, Being Processed, QA Hold, Ready for Sale, Sold, Broken/Parts, Internal Use, Archived, Returned/RMA) with corresponding test runs.
- Updated `docs/demo-guide.md` so seeded account list and reset commands match actual behavior.
- Validation: `docker compose exec app php artisan migrate:fresh --seed` (pass), resulting in `assets_count=10`, `users_count=21`, `test_runs_count=10`.
- Fixed recurring dev UX issue where the assets page can appear empty after reseed/reset despite data existing: bootstrap-table persists state in long-lived cookies, so we now version the hardware index table cookie key to invalidate stale filters after DB resets.
- Investigated recurring 500s on `/hardware` after reseeds/resets and found a container-level permissions failure writing compiled Blade views (`storage/framework/views/*` permission denied). Root cause was root-owned cache artifacts created during container startup.
- Implemented a docker dev fix: update `docker/app/entrypoint.sh` to run artisan cache/view operations as `www-data` when the container starts as root and to `chown/chmod` cache directories afterwards; rebuild the `app` image so `/usr/local/bin/entrypoint.sh` matches.
- Verified current expected dev state: DB seeded (`users=21 settings=1 assets=10`) and `www-data` can write `storage/framework/views` (no more `file_put_contents` permission errors in view compilation).

# Session Progress (2026-02-10)

## Addendum (2026-02-10 Codex)
- Session kickoff: reviewed `AGENTS.md`, `PROGRESS.md`, and `docs/fork-notes.md` to align with current workflow before starting work.
- Created `docs/agents/agents-addendum-2026-02-10-session-init.md` for this session's detailed notes.
- Pending: confirm today's implementation scope and begin logging outcomes.

# Session Progress (2026-02-05)

## Addendum (2026-02-05 Codex)
- Session kickoff: reviewed AGENTS.md, PROGRESS.md, and docs/fork-notes.md to align with current workflow before resuming work.
- Hardware QR preview now renders the same label layout used for printed PDFs, so on-screen previews match print output.
- Removed the completed Latest Tests hover-column task from AGENTS.md.
- Noted that empty hardware lists still point to API/auth or persisted filters; capture `/api/v1/hardware` responses if the issue resurfaces.
- Test run edit links now open the specific run in the active tests view, and edits update its finished timestamp so it becomes the latest run.
- Marked the “resume closed test run” TODO as done after enabling targeted run editing.
- Tests not run in this environment.

# Session Progress (2026-02-03)

## Addendum (2026-02-03 Codex)
- Session kickoff: reviewed `AGENTS.md`, `PROGRESS.md`, and `docs/fork-notes.md` to align with current workflow before starting work.
- Diagnosed local access failure: the internal dev hostname resolved to a stale host entry, so requests never reached the local nginx container.
- Restored local dev host by correcting the Windows hosts entry, flushing DNS, and restarting app/web containers.
- Reverted local overrides so `APP_URL` and nginx match the internal dev hostname again.
- Normalized storage/cache permissions to avoid Blade view cache write errors.
- Dashboard now hides unauthorized resource blocks; dashboard counts only compute for permitted resources, and activity/status chart sections are gated by their permissions to avoid 403-visible widgets.
- Hardware list: removed the Checked Out To, Purchase Cost, and Current Value columns from the assets table layout.
- Asset tags and serials now normalize to uppercase on save with per-field override toggles in the asset form; API endpoints honor override flags and the UI enforces uppercase while typing unless overridden.
- Asset creation no longer renders checkout-to selectors in the refurb flow.
- Asset edit/create no longer show manufacturer selection; model-level manufacturer stays authoritative.
- Hardware detail no longer shows manufacturer block in the refurb flow.
- Hardware assets list no longer includes the Requestable column.
- Consolidated historical session logs into `docs/agents/agent-progress-consolidated.md`, updated `AGENTS.md` to reference the archive, and removed the duplicate `docs/agents/agents.md`.
- Archived old yearly logs and session addenda under `docs/agents/old/`.
- Pushed the above updates to `origin/master` (commit `fccab82d5`).

# Session Progress (2026-01-15)

## Addendum (2026-01-15 Codex)
- Session kickoff: reviewed `AGENTS.md`, `PROGRESS.md`, and `docs/fork-notes.md` to align with current workflow before starting work.
- Pending: confirm today's scope and start tracking outcomes.

# Session Progress (2026-01-08)

## Addendum (2026-01-08 Codex - Asset Tag/Serial Duplicates)
- Kickoff: reviewed `PROGRESS.md` and `docs/fork-notes.md` to align with current workflow before starting asset tag/serial changes.
- Asset creation now honors custom asset tags and keeps asset tags uniquely enforced, while serials can be overridden only with an explicit allow-duplicate flag.
- Added a serial-duplicate check API endpoint and wired the asset form UI to warn on conflicts, show existing matches, and allow a deliberate duplicate toggle.
- Updated request/model validation to drop serial uniqueness only when a duplicate override is requested.
- Added an unlock button to enable editing the auto-generated asset tag on create.
- Tests not run in this environment.
- Pushed changes to origin/master.

## Addendum (2026-01-08 Codex)
- Kickoff: reviewed `AGENTS.md`, `PROGRESS.md`, and `docs/fork-notes.md` to align with current workflow before starting work.
- Drafted a detailed implementation plan for the Latest Tests column counts + lazy hover detail and linked it from `AGENTS.md`.
- Updated the plan to compute counts on read and to show photo markers plus truncated note excerpts in hover details.
- Implemented compute-on-read Latest Tests counts in the assets API, added a latest-test-summary endpoint, and updated the list UI to show ratios with lazy hover details (including note excerpts and photo markers).
- Tests not run in this environment.
- Fixed MariaDB incompatibility in the assets list query by switching latest-run subqueries from IN + LIMIT to scalar subqueries.
- Added CSRF headers to the hover summary request so the API auth guard accepts the lazy-load calls.
- Pointed the hover summary request to a relative `/api/v1/hardware/` base so APP_URL mismatches do not break hover calls.
- Updated the Latest Tests hover tooltip to use per-item fail/open badges with inline note excerpts and photo markers for better readability.
- Adjusted tests-active mobile layout: hide native file inputs and keep CTA indicators right-aligned on small screens.
- Logged a TODO to align user naming + email standards after manager discussion.
- Noted status updates: tests-active graphics are done, direct printing from the asset view works, and only a few device catalog placeholders remain.

# Session Progress (2026-01-07)

## Addendum (2026-01-07 Codex)
- Kickoff: reviewed `AGENTS.md`, `PROGRESS.md`, `docs/fork-notes.md`, and recent `docs/agents/*` logs to align with current workflow before making changes.
- Logged today's session stub here and created `docs/agents/old/agents-addendum-2026-01-07-session-init.md` for detailed tracking.
- Fixed hardware image uploads to redirect back to the asset view with a flash message (non-AJAX form submissions were previously showing raw JSON).
- Fixed asset image thumbnails in the Images tab by using the public disk URL for each stored path (consistent with cover image rendering).
- Removed the temporary legacy path normalization for asset images so the Images tab reflects the current storage layout only.
- Cleaned up orphaned asset image row(s) for asset 5 where the file was missing from the public disk.
- Follow-up: after front-end changes, ensure storage/cache permissions are refreshed to avoid view cache write errors (e.g., `storage/framework/views` permission denied).
- Adjusted attribute version create flow so the browser back button returns to the attributes list after saving a new version.
- Enum options are now read-only on existing attributes; the new-version flow surfaces editable option rows (including active state) and saves them onto the new version.
- Tweaked mobile tests active CTAs to left-align the note/photo controls and keep the indicator on the right edge.
- Improved scan UX: added try-harder/inverted QR hints, faster fallback to higher resolution, reduced scan interval, simplified focus handling, and show a success overlay before redirect.
- Clearing the assets list search storage now runs after a successful scan so the hardware list is not left filtered by the scanned tag.
- Tests tab on the hardware detail page now renders each result's photos directly under its line item instead of a single strip per run.
- Asset detail now highlights failed/incomplete latest tests, and status changes to Ready for Sale/Sold prompt for confirmation with the issue list.
- Added a latest-test status badge on the asset detail view and a Tests column in asset listings, backed by test run counts in asset list APIs.
- Preserved redirect selection when status-change confirmations are required so saving after the confirm returns to the intended page.
- Confirmation submit now forces the redirect option to the asset detail page and uses requestSubmit when available.
- Tests active completion now prompts when required tests are incomplete or any tests failed, without disabling the button.
- Added `tests-active.js` to the Mix build so the tests execution UI uses the latest JS bundle.
- Tests not run yet in this environment.

# Session Progress (2025-12-30)

## Addendum (2025-12-30 Codex)
- Kickoff: reviewed `AGENTS.md`, `PROGRESS.md`, `docs/fork-notes.md`, and recent `docs/agents/*` logs to align with the current workflow before resuming work.
- Logged today's session stub here and created the `docs/agents/old/agents-addendum-2025-12-30-session-init.md` note for detailed tracking.
- Fixed attribute definition versioning validation so new versions reuse the same key without triggering the model-level unique rule (uniqueness now scopes by key + version); DB constraint already matched this behaviour.
- Tests not run yet in this environment.

# Session Progress (2025-12-23)

## Addendum (2025-12-23 Codex)
- Kickoff: reviewed `AGENTS.md`, `PROGRESS.md`, `docs/fork-notes.md`, and recent `docs/agents/*` logs before starting changes.
- Test generation now runs off Test Types (attribute-linked or category-scoped), with new optional/required support and category scoping via `category_test_type`.
- Added `is_required` to test types, surfaced it in the Test Types admin UI, and adjusted active-test progress so optional failures warn but do not block completion.
- Removed the `needs_test` field from attribute definition requests/UI and stripped it from resolver/test generation logic.
- Updated seeders, factories, and test coverage to use category-scoped test types and the new optional logic; refreshed fork notes.
- Tests not run in this environment (no PHP CLI invoked).
- Tests index: moved photo thumbnails to render under their respective result rows instead of a single strip at the bottom of each run.
- Tests active: removed the send-to-repair button and allow the completion action regardless of unfinished or failed checks (warnings handled elsewhere).
- Hardware edit: replaced the status Select2 control with a plain select so mobile does not open the keyboard.
- Session end note: context compression occurred mid-session; some conversational details were lost, but all implemented changes were captured in code/docs and committed (`Refactor test type scoping and optional tests`).
- Follow-up: renamed the former *_test attributes to capability fields (wifi, bluetooth, etc.), disabled asset overrides on them, and added a migration to rename existing records; default slugs/keys now drop the `_test` suffix.

# Session Progress (2025-12-18)

## Addendum (2025-12-18 Codex)
- Kickoff: reviewed `AGENTS.md`, `PROGRESS.md`, `docs/fork-notes.md`, and recent `docs/agents/*` logs to align with current guidance before making changes.
- Logged today's session in `docs/agents/old/agent-progress-2025.md` and started the dated addendum in `docs/agents/old/agents-addendum-2025-12-18-session-init.md` for detailed notes.
- Added the 2025-12-18 entry to `docs/agents/old/agent-progress-2025.md` to keep the consolidated log current.
- Reset DB with core seeds (categories, manufacturers, attributes, presets, tests) and re-seeded demo assets when verifying; noted that prod will need a one-off to mark test attributes and link test types.
- Updated model-number select list to show the model-number code; model-number creation now redirects to the new model number detail.
- Scan page: added camera selector, permission request button, and refreshed the JS to populate devices after permission.
- Enforced serial uniqueness unconditionally (removed the `unique_serial` setting bypass).
- Marked `_test` attribute definitions as `needs_test` by default in `DeviceAttributeSeeder`; identified prod needs a mapping/creation of `_test` defs to populate runs.

# Session Progress (2025-12-09)

## Addendum (2025-12-09 Codex)
- Kickoff: initialized session per `AGENTS.md` workflow; reviewed PROGRESS.md, docs/fork-notes.md, and docs/agents/old/agent-progress-2025.md to refresh current context.
- Created this dated stub to track today's work; ready for task assignments.
- Logged session kickoff in `docs/agents/old/agent-progress-2025.md` so today's notes have a dedicated addendum.
- Restored dev printing path: attached the Dymo LabelWriter 330 Turbo to WSL, brought CUPS back up with queue `dymo25` (25x25 S0929120), updated `.env` to target it, installed `cups-client` in the `snipeit_app` container, and printed a sample 25x25 PDF via `lp` to verify end-to-end.
- Reviewed QR label work from 2025-11-19 through 2025-12-02: server-side CUPS printing, multi-queue support, template consolidation.
- Locked in the S0929120 (25x25) template as default (v13) with final offsets (qr_left 3.2mm, text_left 1.8mm, padding 1.8mm), cleared caches/labels, and validated printing via CUPS queue `dymo25` (job dymo25-25) using zero-margin Custom.W72H72 media.
- Navigation: added a Scan button (route `scan`) to the top nav alongside Assets/Licenses for easier mobile access; new `scan` icon added.
- Scanner: swapped QR decoding to ZXing (@zxing/browser), default to low-res 640x480 with QR-only hints, keeps torch/switch/refocus controls, and falls back to 1280x720 after consecutive failures; rebuilt assets via `npm run prod`.
- Fixed S0929120 (25x25mm) label template for LabelWriter 330 Turbo (300 DPI): increased font from 8.5pt to 11pt, expanded text band from 2mm to 4mm, reduced QR box from 20mm to 18.5mm for better text visibility.
- Removed arbitrary font-size reduction in CSS (was using `fontSize - 1`), now uses configured size directly with semibold weight for better readability on thermal printers.
- Corrected template key from `dymo-s0929120-57x32` to `dymo-s0929120-25x25` to match actual dimensions.
- Reconfigured 25x25mm template with 2mm physical margins: 21x21mm QR code, 2mm text band at bottom (9pt font), no gap between QR and text, 248px QR resolution (matches 300 DPI exactly).
- Fixed square-stack layout rendering: QR now positions at top-left with padding, text band at bottom (was incorrectly using side-by-side layout).
- Hardware create form now shows the model number code (not the display label) so the selected preset is unambiguous during asset creation.
- Hardware list now shows the model name in the “Name” column (falls back to asset name only if no model is present) for clarity.
- Asset tag generator now prefixes new tags with `INBIT-` (two letters + four digits) as the sole generator for new asset tags, independent of auto-increment settings; setup defaults to the same prefix.
- Scanning a tag now routes to the asset detail page instead of the active tests view.
- Updated the scan page layout to focus on the camera preview with two primary controls (camera refresh, flashlight) and auto-scroll into view.
- Camera auto-scroll now offsets slightly so the navbar and scan header remain visible when focusing the preview.
- Updated to user-specified dimensions: 2.5mm margins, 20x20mm QR (236px at 300 DPI), 2.5mm text band with 5pt font, 0.1mm gap.
- Changed S0929120 template text to show asset tag only (no serial number) to match the QR code identifier.
- Created HTML visual designer (label-designer.html) for iterative label layout design without regenerating PDFs.
- Debugged website printing: fixed controller to pass CUPS_SERVER environment variable to the lp process; identified CUPS scheduler not running on WSL (172.22.110.249).

# Session Progress (2025-12-02)

## Addendum (2025-12-02 Codex)
- Kickoff: re-read `AGENTS.md` and every `docs/agents/*` log so today's work starts with the latest workflow/context.
- Logged `docs/agents/old/agents-addendum-2025-12-02-session-init.md` to track this session; no code or config changes yet.
- Seeded latest hardware variants (430 G3/G6, Surface Pro 4/5) and reset dev DB via `php artisan migrate:fresh --seed` to validate; QR/scan refinements shipped (refocus/torch, tighter spacing); model list now shows actual model-number codes/labels.

# Session Progress (2025-11-25)

## Addendum (2025-11-25 Codex)
- Kickoff: re-read `AGENTS.md`, `docs/fork-notes.md`, and all `docs/agents/*` logs to align with the latest guidance before making changes.
- Logged this dated stub and created `docs/agents/old/agents-addendum-2025-11-25-session-init.md` to capture work for today; no code changes yet.

# Session Progress (2025-11-20)

## Addendum (2025-11-20 Codex)
- Reviewed QR printing architecture and added a server-side print path that renders the selected template to PDF and spools it to a CUPS queue (configurable via `LABEL_PRINTER_QUEUE` / `LABEL_PRINT_COMMAND`).
- New asset-page control sends the current template to the server print endpoint and surfaces job feedback; preview/download remain unchanged.
- Added a CUPS setup guide under `docs/agents/cups-setup-guide.md` and stubbed the new env vars in `.env.example`.
- Added optional multi-queue support (`LABEL_PRINTER_QUEUES`) plus a printer dropdown on the asset QR widget for selecting storage/workarea queues.

# Session Progress (2025-11-19)

## Addendum (2025-11-19 Codex)
- Re-read `AGENTS.md`, the latest PROGRESS entries, and all `docs/agents/*` addenda so today’s QR printing fix started with the current workflow/state of play; logged the new docs/agents stub before coding.
- Audited the QR label stack (config, QrCodeService, hardware view, bulk actions, label settings) to trace why the Dymo LabelWriter 400 Turbo output spilled the QR and caption across multiple “pages” and why users couldn’t easily pick the roll currently in the printer.
- Added first-class templates for the common Dymo rolls (30334 57x32 mm, 30336 54x25 mm, 99012 89x36 mm, 30256 101x59 mm, plus the legacy 50x30 mm option) and exposed the picker in settings, the hardware sidebar, and the bulk action toolbar so refurbishers can match the printer stock without editing config files.
- Rebuilt the PDF/layout helper shared by single/batch QR prints so we explicitly size the QR canvas and caption area; Dompdf now keeps both elements on a single page and batch runs honor the chosen template.
- Delivered a new sidebar widget on the asset view (preview + template dropdown + print/download buttons) and wired the bulk "Generate QR Codes" action to pass the selected template to `QrLabelService::batchPdf`, improving the end-to-end printing experience.
- Updated the sticker content so each label prints exactly once with the model + preset, serial number, asset tag text, and the Inbit company line; RAM/disk/status/property-of strings were intentionally left off per the request, and the tests now lock in the new caption formatting.
- Refined the PDF layout so the QR stays large on the left while the text stack sits on the right, eliminating the extra second page that previously appeared on Dymo printers.
- Switched the default template to the Dymo 99010 (89×36 mm) roll, introduced per-template QR column widths, and reworked the label HTML/CSS so the QR consumes ~90% of the vertical space with the asset name/tag block locked to the lower-right corner.
- Cleaned up the demo seed data so curated assets use the actual product names (no more “Intake Diagnostics”/“QA Ready” suffixes) and remain less confusing for testers verifying the refurb flows.
- Trimmed the sticker copy to just asset name + asset tag, anchored the text column to the bottom-right with a 5% internal margin, and ensured the QR respects the same top/bottom padding so each PDF displays as a single page with the requested framing.
- Latest tweak: lifted the QR column so its top edge aligns with the text block, tightened the DOMPDF CSS, and removed the remaining blank pages—the PDF now renders a single 99010 label with the QR left and text bottom-right.
- Updated translations, validation (`StoreLabelSettings`), docs/fork-notes.md, and docs/agents/old/agent-progress-2025.md to capture the new workflow and guidance for future sessions.

## Notes for Follow-up Agents
- Run the refreshed PDFs through real Dymo LabelWriter 400 Turbo hardware for each template (especially the larger 30256 shipping roll) and tweak `config/qr_templates.php` padding if any QR codes still get cropped.
- Consider persisting a per-user "last template" preference so success notifications and other entry points can default to the roll most recently used without forcing a page reload.
- Once hardware verification is done, grab screenshots of the new sidebar widget and bulk picker for inclusion in README/docs to help downstream contributors understand the workflow without diffing code.
- TODO: configure and validate multiple print queues (storage vs workarea) via `LABEL_PRINTER_QUEUES` and the asset-page dropdown.

# Session Progress (2025-11-13)

## Addendum (2025-11-13 Codex)
- Re-read `AGENTS.md`, `PROGRESS.md`, `docs/fork-notes.md`, `docs/agents/old/agent-progress-2025.md`, and every existing `docs/agents/old/agents-addendum-*` log so today's work begins with the latest workflow rules and carry-over issues.
- Logged this dated stub and created `docs/agents/old/agents-addendum-2025-11-13-session-init.md` to capture detailed context before touching code or tests.
- Reconfirmed the lingering blockers from the 2025-11-06 and 2025-11-11 sessions (new `/hardware/{asset}/tests/active` view not rendering, targeted PHPUnit/Dusk suites pending) and queued them for follow-up today.
- Kickoff/context refresh captured before code changes resumed; all subsequent bullets reflect today's work.
- Fixed the `bootstrap.Modal` runtime error on `/hardware/{asset}/tests/active` by adding compatibility helpers in `resources/js/tests-active.js` that prefer Bootstrap 5 components but gracefully fall back to the existing jQuery plugins (modal/collapse) when the namespace lacks constructors; rebuilt assets via `npm run dev` so the updated bundle is ready for verification.
- Rewired the tests UI permission gate: `TestResultController@active` now derives `canUpdate` from the `TestRun` policy (run owners with refurbisher/supervisor/admin access) instead of the asset policy, and the view config reflects that so front-end buttons stay active for refurbishers who own the run even without asset-edit rights; added two feature tests covering the positive/negative scenarios. `php artisan test tests/Feature/Tests/ActiveTestViewTest.php` could not run locally because `php` is unavailable in this shell.
- Restored asset-edit access to the tests UI: `TestResultController@active` now allows `canUpdate` when the viewer can edit the asset _or_ the test run, preserving the previous behavior for asset managers while keeping the new refurbisher permissions; extended feature coverage so a user with only asset-edit rights still sees `canUpdate: true`. Running `php artisan test tests/Feature/Tests/ActiveTestViewTest.php` still fails immediately because PHP CLI is unavailable here—rerun inside Docker/WSL to verify.
- Fixed the missing page-level styles by switching both `resources/views/tests/active.blade.php` and `resources/views/tests/edit.blade.php` to use the layout’s `@push('css')` stack instead of the non-existent `@push('styles')`, so the new cards/layout now match the spec reference (two-column blocks, note/photo buttons at the bottom).
- Delivered the new A5-first visual system for `/hardware/{asset}/tests/active`: sticky two-tier header, responsive CSS grid that switches between masonry and compact modes, elevated cards with segmented controls, and a glassy floating action bar that mirrors the screenshot in `docs/plans/Schermafbeelding 2025-11-13 093717.png`. The `tests/partials/active-card.blade.php` template now exposes status pills, 50/50 note/photo actions, and drawers styled for the new aesthetic.
- Polished the cards per design feedback: removed the stale “Expected” copy, enlarged titles, centered/larger Geslaagd/Mislukt buttons, dropped the redundant status pill text, and replaced the note/photo labels with icon+indicator pills so it’s easy to scan which cards contain attachments.
- Reworked `resources/js/tests-active.js` to drive the new icon indicators (note/photo chips now toggle classes instead of “Ja/Nee” text) and kept the multi-location progress counters in sync; recompiled assets via `npm run dev`.
- Re-verified the feature targets inside Docker after each set of visual tweaks: `docker compose exec app php artisan test tests/Feature/Tests/ActiveTestViewTest.php` (6 tests) and `docker compose exec app php artisan test tests/Feature/Assets/PartialUpdateTestResultTest.php` (5 tests) both pass; API suite and Dusk runs remain pending.
- Additional polish round: hid the lingering “Toon instructies” label, centered the note/photo text with icons, moved the indicator chip to the far right, widened/tallened the Geslaagd/Mislukt controls, and introduced localized copy for the note/photo CTAs plus the note field label. Assets rebuilt via `npm run dev`, and the same two Docker PHPUnit suites were re-run successfully.
- Follow-up: repositioned the note/photo indicator chips using CSS grid so they float on the far right (independent of the centered icon/text), confirmed no lingering `general.*` strings remain, and recompiled assets. Re-ran the two Docker feature suites again to ensure the markup/JS changes are safe.
- Added full multi-photo support for test results: new `test_result_photos` table/migration (with backfill), `TestResultPhoto` model/relations, multi-photo upload/delete logic in `TestResultController@partialUpdate`, Blade galleries, and JS handling (stacked thumbnails w/ horizontal scroll + per-photo delete). The `tests/Feature/Assets/PartialUpdateTestResultTest.php` suite now covers upload/removal flows; ran it plus `tests/Feature/Tests/ActiveTestViewTest.php` inside Docker after `php artisan migrate`.
- Extended the seed catalog with factual presets for HP ProBook 450 G7/G6, 430 G6/G3, and Microsoft Surface Pro 4/5, keeping the existing four demo assets untouched while giving future seeds a richer dataset (`ProvidesDeviceCatalogData` + `DemoAssetsSeeder` now load both the demo and expansion sets).

## Notes for Follow-up Agents
- Expand this section as concrete work lands today and mirror behaviour/process changes into `docs/fork-notes.md` plus supporting docs.
- Highest priority: re-test `/hardware/{asset}/tests/active` now that the Bootstrap compatibility helpers are in place; if the legacy Blade/JS bundle still appears, inspect caches and ensure `public/js/dist/tests-active.js` is synced inside the runtime container (nginx/php-fpm).
- Once the new UI renders correctly, rerun `php artisan test tests/Feature/Tests/ActiveTestViewTest.php tests/Feature/Assets/PartialUpdateTestResultTest.php`, `php artisan test --testsuite=API`, and `php artisan dusk --filter=TestsActiveDrawersTest`, then log the outcomes (the targeted feature test command currently fails because PHP CLI is unavailable in this shell).
- Continue the A5-first testing UI execution plan and expand Dusk coverage after the environment reliably serves the refreshed assets.
- TODO: tighten typography/layout on `/hardware/{asset}/tests/active` for ≤430 px devices so body copy no longer overflows the card; increase base font size/line height and ensure each card reflows without clipping.
- TODO: replace the placeholder `code` fields in `ProvidesDeviceCatalogData` (e.g., `HP-450G8-I5-16-512`) with factual MPN/SKU values pulled from vendor datasheets before running seeds in production; research is still pending.

# Session Progress (2025-11-11)

## Addendum (2025-11-11 Codex)
- Reviewed `AGENTS.md`, `PROGRESS.md`, and every `docs/agents` addendum so today's work starts with the latest ground rules and outstanding follow-ups in mind.
- Logged this dated entry plus a companion docs/agents addendum to capture context before code changes begin, keeping the documentation trail intact.
- Reconfirmed the 2025-11-06 follow-ups (A5-first testing UI plan, select list/API test runs, expanded Dusk/browser coverage) so they remain front-of-mind for this session's prioritization.
- Implemented the A5-first testing UI plan: rebuilt `tests.active` with the sticky save-indicator header, layout toggle, card drawers, modals, and fixed action bar; added the new `tests/partials/active-card.blade.php`, rewrote `resources/js/tests-active.js` for the new interactions (pass/fail deselect, autosave notes, photo modal/delete), and extended translations + feature tests. (`php artisan test tests/Feature/Tests/ActiveTestViewTest.php tests/Feature/Assets/PartialUpdateTestResultTest.php` was attempted but `php` is unavailable in this shell.)
- Follow-up polish: darkened the card backgrounds and added vertical/column spacing so each block is visually separated, wired the collapse toggles (instructions/note/photo) to Bootstrap’s data attributes for a non-JS fallback, loosened the update gate to match the start-run permission, expanded Dusk coverage (`TestsActiveDrawersTest` now seeds its own run and walks pass/fail/note/photo flows), and rebuilt assets with `npm run dev`. `docker compose exec app php artisan test tests/Feature/Tests/ActiveTestViewTest.php tests/Feature/Assets/PartialUpdateTestResultTest.php` passes; `docker compose exec app php artisan dusk --filter=TestsActiveDrawersTest` still times out because the refreshed UI fails to load inside Dusk (page renders the legacy template/no cards), screenshot saved under `tests/Browser/screenshots/failure-Tests_Browser_TestsActiveDrawersTest_test_note_and_photo_drawers_toggle-0.png`.
- Reconfigured Dusk to use the same MariaDB engine as dev (new `snipeit_dusk` schema via `docker compose exec db …`, updated `.env.dusk*` to point at it) so browser tests no longer rely on SQLite. Post-change, `php artisan dusk --filter=TestsActiveDrawersTest` fails later in the flow (waiting for `/start`) instead of choking on SQLite-specific SQL, which confirms it now targets the MySQL schema; further UI fixes can proceed on that baseline.

## Notes for Follow-up Agents
- Use this stub to record concrete work as it lands today; mirror any user-facing or process changes into docs/fork-notes.md and supporting docs.
- Highest priority: /hardware/{asset}/tests/active still renders the old UI (no cards/drawers) in both browsers and Dusk even after clearing caches, reinstalling dependencies, and recreating Docker volumes. Track down why php-fpm/nginx is serving the legacy Blade/JS and fix it.
- When you start coding, run the targeted test suites (php artisan test --testsuite=API, php artisan dusk) relevant to your changes and log the results here.
- Continue the Developer Execution Plan (A5-first testing UI) once blockers clear, and call out any new risks or doc needs in this section.
- Dusk coverage is still pending: docker compose exec app php artisan dusk --filter=TestsActiveDrawersTest times out because the test harness still sees the legacy page; once the UI issue above is resolved, rerun the suite.


# Session Progress (2025-11-06)

## Addendum (2025-11-06 Codex)
- Revisited AGENTS.md and prior addenda, then blocked deprecated model numbers from end-user flows by filtering Select2 options, store/update validation, and controller helpers while still surfacing an asset's legacy preset when editing.
- Tightened the API `models.selectlist` endpoint to return only active presets (with an opt-in for deprecated), and added feature coverage for the filtered select list plus asset store/update validation edge cases.
- Logged today's work in docs/agents and noted outstanding feature-list items (QR workflow, enum UX, role-based start gating) for subsequent passes.
- Rebuilt the `/start` experience for refurbisher, senior refurbisher, and supervisor/admin roles with single-column, touch-friendly actions (`Scan QR`, `Nieuw asset`, `Beheer`) and consistent data-testid hooks.
- Refreshed `/scan` with auto-starting camera, device switching, manual fallback, accessibility hints, and jsQR-based decoding tuned for mobile devices.
- Delivered a new `/hardware/{asset}/tests/active` page: sticky context header, grouped test cards (Failures/Open/Passed), tri-state segmented toggles, inline notes/photos, Bootstrap toasts, and a bottom bar with progress + contextual CTAs.
- Implemented optimistic autosave with offline queuing (local queue + lightweight service worker cache), success/error toasts, and rebalancing logic that moves cards between groups plus recalculates completion counts.
- Added feature tests for the active test view redirect and ensured the scan redirect targets the new active test route; introduced documentation for the new flow.
- Captured the “Developer Execution Plan — Mobile Testing Page (A5-first)” under `docs/plans/` for the next iteration of the testing UI, and updated the default QR label template to a 50×30 mm Dymo LabelWriter 400 size.

## Notes for Follow-up Agents
- Run `php artisan test --testsuite=API` (and the new select list coverage) once PHP CLI access is restored to verify the added tests.
- Manually smoke-test the asset create/edit UI to confirm the Select2 initial state shows a deprecated preset once and that new searches omit it.
- Continue the outstanding feature list from 2025-10-02 (QR module rebuild, enum quick-add UX, role-based start page gating, documentation updates) now that preset filtering is in place.
- Next session: follow the Developer Execution Plan (A5-first) document to rebuild the testing page (compact two-column mode, pass/fail toggles with deselect, drawers, autosave status indicators, photo gallery UX).
- Compile front-end assets with `npm run dev` (or `npm run prod`) so `public/js/dist/tests-active.js` is available for the redesigned test UI; assets remain functional without the bundle, but the interactive experience depends on it.
- Extend Dusk coverage for the new flows (start buttons, scan redirect, autosave interactions) and consider full offline photo queuing if future requirements demand it.

# Session Progress (2025-11-05)

## Addendum (2025-11-05 Codex)
- Brought the repo in line with the Dusk harness: reviewed AGENTS.md/doc logs, installed `laravel/dusk`, and scaffolded the browser testing assets inside the PHP container.
- Updated `docker/app/Dockerfile` to install Chromium/Chromedriver so headless runs execute inside Docker; added `.env.dusk*` files plus sqlite backing and force-set Dusk bootstrap to the internal Nginx host.
- Hardened `tests/DuskTestCase.php` to seed/migrate per test, launch Chrome with container-safe flags, and normalise configured app URLs before requests.
- Added `tests/Browser/ExampleTest.php` (login inputs) and `tests/Browser/DashboardRefurbFiltersTest.php` (dashboard refurb chips via real login), wiring the Start-page shortcut into the dashboard view and getting the full Dusk suite green.
- Follow-up: extend Dusk coverage beyond the smoke checks (refurb flow interactions, QR/camera handling) and continue using `scripts/check-storage-permissions.sh` after environment resets so compiled views remain writable.

# Session Progress (2025-10-30)

## Addendum (2025-10-30 Codex)
- Session kickoff: reviewed AGENTS.md, docs/agents addenda, and prepared a fresh session log for 2025-10-30 under docs/agents/.
- Dashboard assets dropdown now pulls refurb filters through localized labels so Dutch users see `Stand-by`, `In verwerking`, `QA-wacht`, etc.; seeding defaults to locale `nl-NL` for fresh datasets.
- Added `scripts/check-storage-permissions.sh` to sanity-check writable cache directories after code changes without baking fixes into container entrypoints.
- Resolved Blade cache permission errors by running the remediation commands in the container and clearing compiled views.
- Normalized refurb status translations via `App\Support\RefurbStatus`, ensuring slug-based keys in `resources/lang/*/refurb.php` map canonical status names to Dutch labels.
- Switched user seeding defaults to `nl-NL` (`UserFactory`) and updated `.env.example` so freshly provisioned demos and logins inherit the Dutch locale; reseeded with `php artisan migrate:fresh --seed` to apply.
- Refreshed demo hardware seeders: retired the MacBook/XPS examples, introduced HP ProBook 450 G8 and 430 G7 plus a Samsung Galaxy A5 handset, and expanded manufacturer seeding for HP/Samsung.
- Enabled asset-level overrides for `condition_grade`, `charger_included`, `storage_capacity_gb`, and `ram_size_gb` in the attribute blueprints so per-device refurb variations are supported; reviewed all `*_test` indicators and confirmed each maps to a distinct hardware check, so none were removed.
- Follow-up: spot-check the dashboard sidebar and a freshly seeded environment to confirm locale/label translations look correct, and mirror any substantive documentation updates into docs/fork-notes.md if needed.

# Session Progress (2025-10-28)

## Addendum (2025-10-28 Codex)
- Realigned the refurbishment status taxonomy with the Stand-by -> Returned / RMA flow, updating dashboard filters, `StatuslabelSeeder`, the upsert migration, and demo asset fixtures to share the new Dutch labels and colour cues.
- Audited the device attribute presets after the catalog rewrite to ensure no legacy keys (brand/device class/carrier lock etc.) lingered in the MacBook/XPS/Pixel blueprints.
- Reran `docker compose exec app php artisan migrate --seed` inside the stack to validate the refreshed seeders; confirmed the nine refurbished states seed without errors.
- Hernieuwde het assets-zijmenu zodat alleen de nieuwe refurb statuslabels zichtbaar zijn en rechtstreeks naar `hardware.index?status_id=` linken; oude Deployed/RTD/Archived-links zijn verwijderd.
- Verwijderde het legacy “All tests passed”-lint op de assetdetailpagina in afwachting van de nieuwe testrun-UX.
- Modelnummerbeheer toont nu een verwijderknop (met bevestiging en blokkerende toestand voor primaire of toegewezen nummers) zodat opschonen niet meer via losse routes hoeft.
- Spec-builder wijst attributen voortaan op categorie-type i.p.v. alleen exacte categorie-ID, zodat alle laptop/phone-velddefinities weer verschijnen bij modellen met subcategorieën (`AttributeDefinition::scopeForCategory` + call-sites).
- Dashboard-refurbfilters vertalen nu naar Nederlandse labels en beschrijvingen terwijl de statuskoppeling intact blijft (`DashboardController@buildRefurbFilters`).

## Notes for Follow-up Agents
- Smoke-test the dashboard status chips in a browser to confirm the new labels filter hardware as expected.
- Resume the PHPUnit cleanup for checkout/merge retirement once PHP CLI access is available locally.

# Session Progress (2025-10-23)

## Addendum (2025-10-23 Codex)
- Reworked the Test Types admin experience so it aligns with other settings views: the listing now has inline action buttons, creation happens through a modal, and quick links were added to the top navigation and settings sidebar.
- Converted enum option editing into a staged workflow; value/label pairs are queued on the form, reviewed in a consolidated table, and saved alongside the attribute definition with improved validation feedback.
- Hardened model specification validation by surfacing field-level errors that spell out regex requirements, numeric ranges, and acceptable units, and added a summary alert when a spec save fails so issues are easy to spot.
- Updated attribute value normalization to emit clearer guidance for enums, booleans, numerics, and unit conversions, and routed the messages to the correct inputs for both presets and asset overrides.
- Polished hardware/test UIs: instructions now power the info icon tooltip, the audit button and legacy quick actions were fully removed, and selected attributes in the spec builder have readable highlight styling.
- Reset container permissions on `storage/` and `bootstrap/cache` after view compilation failures to unblock Blade caching.

## Notes for Follow-up Agents
- QA: exercise the Test Types screen (create, edit, delete) and the staged enum workflow to ensure queued options persist and restore correctly on reload.
- QA: in the model spec editor, attempt values that violate regex/min/max/step/unit constraints and confirm the inline messaging points to the offending field with clear remediation text.
- Documentation follow-up: capture the new Test Types workflow, staged enum guidance, and enhanced spec validation behaviour in `docs/fork-notes.md`/handbook material.
- Monitor storage permissions in future sessions; the remediation command is `docker compose exec --user root app chown -R www-data:www-data storage bootstrap/cache && docker compose exec --user root app chmod -R ug+rwX storage bootstrap/cache`.

# Session Progress (2025-10-21)

## Addendum (2025-10-21 Codex)
- Session kickoff: revisited `AGENTS.md`, prior `PROGRESS.md` entries, and the existing `docs/agents/` logs to confirm workflow expectations before making changes.
- Logged today's documentation stubs at `docs/agents/old/agents-addendum-2025-10-21-session-init.md` and `docs/agents/progress-addendum-2025-10-21-session-init.md` to capture detailed notes as work progresses.
- Brought up the docker stack, installed dependencies inside the `app` container, prepared `.env.testing` for sqlite, and ran the newly-added `API` PHPUnit testsuite via `php artisan test --testsuite=API` (538 tests in ~176s; 102 failed, 5 incomplete, 4 skipped—failures driven by permission redirects, select-list counts, maintenance uploads, manufacturer update flows, and missing import storage paths).
- Simplified the hardware location selector to a single Select2 dropdown (`resources/views/partials/forms/edit/location-cascade-select.blade.php`) and realigned the location API feature tests with the new single-location expectation; reran `php artisan test --testsuite=API` (now 100 failures, 5 incomplete, 4 skipped) and refreshed the failure inventory at `codexlog/api-failures.csv`.
- Provisioned `storage/private_uploads/imports`, hardened `tests/Support/Importing/FileBuilder.php` to create the directory automatically, migrated manufacturer API specs to JSON endpoints, and marked maintenance API flows as skipped while the module is disabled.
- Split device catalog seeding: `DeviceAttributeSeeder` now seeds only attribute metadata, while the new `DevicePresetSeeder` populates optional demo presets; `AgentTestResultsTest` relies on the attributes/presets for seeded slugs.
- Refactored refurbishment tests so dedicated entries live in `test_types` (`attribute_definition_id`, `instructions` columns added; new `AttributeTestSeeder` seeds the test catalog); controllers now resolve all tests attached to each attribute when creating runs or ingesting agent payloads.
- Built an admin Test Types UI (CRUD + attribute linking + instructions) so refurb checks can be managed without modifying seed data.
- Latest `php artisan test --testsuite=API` completes with 13 failures, 5 incomplete, 11 skipped, 510 passed—remaining failures sit in the ImportAssets validation expectations.

## Notes for Follow-up Agents
- Extend `docs/agents/progress-addendum-2025-10-21-session-init.md` with code updates, verification evidence, and risk notes as the session advances.
- Outstanding verification: triage and resolve the remaining API test cases (ImportAssets validation assertions and the agent test slug seeding) before re-running `php artisan test --testsuite=API`; refer to the refreshed `codexlog/api-failure-summary.txt` for the detailed list.
- Manual QA: walk through the specification builder UI (preset selection, attribute overrides, reorder flows) in a browser-capable environment.
- Documentation backlog: roll pagination helper guidance, specification builder UX workflows, and attribute versioning lifecycle notes into `AGENTS.md`/`docs/fork-notes.md` after validation.
- Monitor the QR template toggle follow-ups and ensure related tests/config documentation stay aligned when PHP access returns.

# Session Progress (2025-10-14)

## Addendum (2025-10-14 Codex)
- Session kicked off: reviewed AGENTS.md, prior PROGRESS entries, and docs/fork-notes.md to align with current fork expectations before making changes.
- Logged new documentation stubs in docs/agents/old/agents-addendum-2025-10-14-session-init.md and docs/agents/progress-addendum-2025-10-14-session-init.md for detailed notes as the day advances.
- Fixed the asset model index API so persisted table offsets clamp to the last available page instead of returning an empty dataset, and added regression coverage for the scenario.
- Promoted the offset clamp into the shared API controller base and rolled it out across list endpoints (assets, accessories, locations, etc.), with fresh assets index coverage to guard the shared helper.
- Introduced attribute definition versioning, hide/unhide workflows, and supporting UI/actions/tests so teams can migrate specs safely.
- Delivered a model-number specification builder: new assignment/reorder endpoints, a three-column search-enabled UI, updated attribute resolution logic, and accompanying feature/unit coverage for assign/remove flows.

## Notes for Follow-up Agents
- Detailed worklog: docs/agents/progress-addendum-2025-10-14-session-init.md (extend with concrete updates and test evidence).
- Handbook updates: docs/agents/old/agents-addendum-2025-10-14-session-init.md (record any process clarifications introduced today).
- Testing follow-up: run the API feature suite (`php artisan test --group=api`) when PHP is available to exercise the new pagination helper under real execution.
- QA follow-up: walk through the new specification builder end-to-end (add/remove/reorder attributes, save specs, verify overrides) once a UI-capable environment is available.

# Session Progress (2025-10-07)

## Addendum (2025-10-07 Codex)
- Session initiated: reviewed AGENTS.md guidance, PROGRESS.md history, and docs/fork-notes.md to re-establish fork context before starting new work.
- Created docs/agents/progress-addendum-2025-10-07-session-init.md to capture detailed notes for this block.
- Refined the model index API/transformer to surface model-number counts and fallback to the primary code so the admin listing shows every model even when presets are missing.
- Simplified model-number listings so default/deprecate actions live on the edit form instead of inline tables.
- Replaced the model detail asset list with a model-number dashboard and shifted file/spec management onto individual presets.
- Investigated the model select list (Werckerman search) but work deferred to next session; no code changes committed.

## Notes for Follow-up Agents
- Working notes: docs/agents/progress-addendum-2025-10-07-session-init.md (update as tasks advance).
- Pending outcomes: summarize deliverables in this section before closing the session.
- Testing blocked: php binary not available on host; rerun `php artisan test tests/Feature/AssetModels/Api/IndexAssetModelsTest.php` once PHP is installed.
- Verify model-number edit screen still handles primary assignment and status flips after removing inline controls.

# Session Progress (2025-10-02)

## Addendum (2025-10-02 Codex)
- Session initiated: reviewed AGENTS.md guidance and recent PROGRESS entries to align with fork expectations.

## Addendum (2025-10-02 Codex - Follow-up)
- Re-reviewed AGENTS.md, PROGRESS.md, and docs/fork-notes.md to confirm carryover work before resuming.
- Current focus: process feedback and update the project based on the latest review inputs.

- Removed the model-number input from the model create flow and redirected post-create to the detail view with guidance to add presets.
- Added a dedicated model-number create page under settings and wired the spec editor CTA to it when no presets exist.
- Updated build/runtime handling for Passport keys (generate during image build, validate only at runtime) and preserved dev-cache clearing guidance.
- Default asset tag generation now uses random two-letter prefixes plus the sequential counter (e.g., ASSET-XY0001) and auto-assigns when tags are omitted; minimal-create test updated accordingly.
- Session paused: finish the outstanding feature list (deprecated preset filtering in index/API, QR module rebuild, attribute enum UX, test-run wiring, role-based start page, docs/tests) and run `php artisan migrate && php artisan test` once PHP is available.


## Notes for Follow-up Agents
- Track ongoing details in docs/agents/progress-addendum-2025-10-02-session-kickoff.md during this session (review alongside this log).
- Pending detailed updates once work completes this session.
- Carryover reminders: rebuild/restart the app service for the Passport key entrypoint fix, verify oauth key files persist after a cold start (capture logs if missing), run `php artisan migrate` post-merge to drop SKUs + add the test-run column, and install composer dev packages (Collision) before running `php artisan test`.

# Session Progress (2025-09-30)

## Addendum (2025-09-30 Codex)
- Detailed notes: docs/agents/progress-addendum-2025-09-30-passport-keys.md (review alongside this summary).
- Investigated the recurring Passport key failure after docker volume resets and confirmed the storage mount starts without oauth key material.
- Extended docker/app/entrypoint.sh to auto-run php artisan passport:keys --force, chown the generated files to www-data, and lock permissions so HTTP requests can decrypt tokens immediately after boot.
- Shared a stopgap for the current stack: execute docker compose exec app php artisan passport:keys --force once to repopulate keys until the container restarts with the updated entrypoint.
- Finished retiring the orphaned SKU scaffolding: removed UI/API references, added a schema cleanup migration, and exposed model-number metadata in asset/test APIs and transformers.

## Notes for Follow-up Agents
- Rebuild or restart the app service (docker compose up -d --build app) to pick up the entrypoint change and verify the hardware index loads without manual key generation.
- After the next cold start, confirm storage/oauth-public.key and storage/oauth-private.key exist on the shared volume; if not, capture container logs for the entrypoint to debug further.
- Run `php artisan migrate` inside the app container once this branch lands to drop the legacy SKU tables and add the test-run model-number column.
- Composer dev packages are missing in the container (`Collision` dependency); install them before attempting `php artisan test` so the new assertions can be exercised.
# Session Progress (2025-09-28)

# Session Progress (2026-04-09)
- Session kickoff: re-read `AGENTS.md`, `PROGRESS.md`, `docs/fork-notes.md`, `TODO.md`, and the latest dated agent addenda to reinitialize current fork context after the 2026-04-07 push.
- Added `docs/agents/agents-addendum-2026-04-09-session-init.md` for today and reconfirmed the active carry-over state before new work begins.
- Known environment blockers remain unchanged at session start:
- sqlite-backed PHPUnit runs are still vulnerable to `database disk image is malformed` in the current container workflow.
- `EditAssetTest` and related UI paths may still hit the existing Livewire support-file-uploads bootstrap issue.
- Existing local-only changes present at session start were left untouched:
- `docker-compose.yml`
- `docker/nginx.conf`
- `docs/agents/agents-addendum-2026-03-19-session-init.md`
- Fixed mobile overflow for shared list-page bulk-action toolbars by removing hardcoded 400-500px minimum widths and making the shared toolbar/select/button layout stack within the viewport on narrow screens.
- Tightened the hardware QR widget controls so the template/printer selects and print button stay within the panel width on small screens.
- Added focused view-level assertions for the responsive bulk-toolbar markup and QR printer control constraints.
- Investigated the test-environment safety failure that had been hitting the live dev MySQL database during PHPUnit runs.
- Root cause: the local Docker app entrypoint was warming cached Laravel config in `APP_ENV=local`, and that cached config could override PHPUnit testing DB settings.
- Added a hard pre-boot test guard in `tests/TestCase.php` that refuses to run tests while `bootstrap/cache/config.php` exists and validates that `.env.testing` is configured for the approved sqlite test DB target.
- Updated the active Docker app entrypoint to keep `local` / `testing` containers uncached (`optimize:clear` only, no `optimize` warmup); production-like environments can still warm caches.
- Updated `AGENTS.md` to require clearing cached config before PHPUnit runs inside Docker because cached config is not a safe testing baseline in this repo.
- Verification:
- `docker compose exec app php artisan view:cache` (pass)
- `docker compose exec app php artisan test tests/Feature/Assets/Ui/AssetIndexTest.php --env=testing` (blocked by current MySQL test DB migration-state drift: unknown/drop-missing tables and missing `migrations`)
- `docker compose exec app php artisan test tests/Feature/Assets/Ui/ShowAssetTest.php --env=testing` (10 passed, 2 failed; new responsive assertions passed, remaining failures were unrelated pre-existing environment/app-state issues around duplicate `users` migration setup and the existing checkout-date assertion mismatch)
- `docker compose exec app php artisan test tests/Feature/Assets/Ui/AssetIndexTest.php --env=testing --filter=testPageRenders` with config cache present (expected fast-fail via new guard before DB work)
- `docker compose exec app php artisan optimize:clear` (pass)
- `docker compose exec app php artisan test tests/Feature/Assets/Ui/AssetIndexTest.php --env=testing --filter=testPageRenders` after cache clear (now resolves to sqlite again; blocked only by the existing sqlite corruption: `database disk image is malformed`)
- Dev DB recovery:
- preflight before reseed confirmed:
- `APP_ENV=local`
- `DB_CONNECTION=mysql`
- `DB_DATABASE=snipeit`
- restored the empty local dev database with `docker compose exec app php artisan db:seed --force`.
- post-seed verification restored the expected demo baseline:
- `users=21`
- `assets=10`
- `settings=1`
- `test_runs=10`
- `models=10`
- `statuslabels=9`
- Test type slug workflow cleanup:
- test type create/edit now defaults slugs to an auto-generated normalized value from the current name while keeping a manual override checkbox available for admins.
- manual overrides are sanitized to the standard lowercase hyphenated slug format before save, so punctuation and other odd characters do not persist in stored slugs.
- auto-generated and manual override slugs now resolve collisions by appending a numeric suffix (`-2`, `-3`, etc.) before validation/save instead of surfacing a raw unique-key failure path.
- added focused feature coverage for create auto-generation, duplicate-name suffixing, update auto-sync from name, and sanitized manual override behavior.
- verification:
- `docker compose exec app php -l app/Models/TestType.php` (pass)
- `docker compose exec app php -l app/Http/Requests/TestType/StoreTestTypeRequest.php` (pass)
- `docker compose exec app php -l app/Http/Requests/TestType/UpdateTestTypeRequest.php` (pass)
- `docker compose exec app php -l app/Http/Controllers/Admin/TestTypeController.php` (pass)
- `docker compose exec app php -l tests/Feature/Settings/ManageTestTypesTest.php` (pass)
- `docker compose exec app php artisan view:cache` (pass)
- `docker compose exec app php artisan test tests/Feature/Settings/ManageTestTypesTest.php --env=testing` (blocked by the pre-existing sqlite testing DB corruption: `database disk image is malformed`)
- Attribute definition create-key workflow cleanup:
- added centralized key helpers on `AttributeDefinition` so keys are normalized to snake_case and auto-suffixed on collisions (`_2`, `_3`, ...) against active attribute records.
- moved create key generation into `AttributeDefinitionRequest::prepareForValidation()` with `manual_key_override` support.
- default create behavior now derives key from `label`; manual override uses submitted key input (with label fallback if blank).
- kept update/version key immutability semantics unchanged.
- updated the attribute create form so key is disabled by default, manual override is explicit, and key text is normalized live while typing.
- added focused feature coverage in `AttributeDefinitionLifecycleTest` for:
- create auto-generated key from label.
- create collision suffixing (`battery_health_2` pattern).
- sanitized manual override plus suffixing on collision.
- explicit key-change rejection on update.
- verification:
- `docker compose exec app php -l app/Models/AttributeDefinition.php` (pass)
- `docker compose exec app php -l app/Http/Requests/AttributeDefinitionRequest.php` (pass)
- `docker compose exec app php -l resources/views/attributes/edit.blade.php` (pass)
- `docker compose exec app php -l tests/Feature/AttributeDefinitionLifecycleTest.php` (pass)
- `docker compose exec app php artisan view:cache` (pass)
- `docker compose exec app php artisan test tests/Feature/AttributeDefinitionLifecycleTest.php --env=testing` (blocked by existing sqlite testing DB corruption: `database disk image is malformed`)

## Addendum (2025-09-28 Codex)
- Session restarted after prior context drop; reviewed AGENTS/PROGRESS/fork notes to re-establish scope on model number settings work.
- Restored attribute creation form by passing the expected layout context so the form renders without `$item` errors.
- Exposed the Model Numbers admin page in the settings side nav so superusers can reach the new CRUD screen.
- Remapped Model Numbers settings auth to rely on the existing asset-model permissions so admins with model access can enter without 403s.
- Escaped the spec editor alert copy so Blade compiles cleanly when model categories message includes an apostrophe.
- Flagged a UX follow-up: enum options should support list-based entry instead of delimiter-separated input in quick-add flows.
- Hardened the asset detail spec table rendering to avoid nested Blade expressions, preventing parse errors on environments sensitive to inline helpers.
- Reframed webshop visibility as an allow-list toggle, added matched internal-use control on asset show/edit, and default new assets to stay off-sale until explicitly approved.

## Notes for Follow-up Agents
- Smoke-test the Admin → Settings → Model Numbers page once PHP/JS assets are recompiled to confirm new CRUD + search interactions behave as expected.
- Continue wiring specification flows to respect the selected model number and fill in outstanding documentation updates once core pages stabilize.

# Session Progress (2025-09-26)

# Session Progress (2025-09-27)

## Addendum (2025-09-27 Codex)
- Session initiated on Raspberry Pi environment; reviewing WIP multi-model-number migrations and related services.
- Goal: confirm migration drafts, scope remaining refactors (relationships, UI/API), and plan data backfill + documentation updates.
- Implemented data-layer shift to `model_numbers` (service layer, resolver, factories) and began wiring asset create/update flows and spec UI around selectable model numbers.
- Added admin CRUD + UI for model number presets, wired spec editor + asset forms to respect the selected preset, refreshed display helpers, and captured regression coverage/documentation updates.
- Enabled creating models without an initial model number (schema change, validation, controller + API updates), reworked spec/asset views to guard when no presets exist, and updated docs/tests for the new workflow.

## Summary
- Confirmed workflow requirements now call for multiple model numbers per model with dropdown selection for refurbishers.
- Scaffolded the new data layer: drafted migrations for `model_numbers`, backfill, and the accompanying Eloquent model.
- Began refactoring attribute storage to reference `model_number_id` instead of `model_id`.

## Notes for Follow-up Agents
- Work paused due to environment usage limits before migrations were finalized—double-check the two new migration files for consistency and run them once access is restored.
- Continue porting relationships and services (`ModelAttributeManager`, resolvers, controllers, UI) to the multi-number schema.
- Update `model_number_rework.txt` and fork docs to reflect the new workflow once implementation resumes.
- Planned next steps: finish data migration backfill, build admin CRUD for model numbers + specs, update asset create/edit (web & API) to require model + model number selection.

## Summary
- Finished staging attribute-driven test generation so new runs and agent uploads build from needs_test model specs.
- Persisted asset specification overrides on updates and exposed formatted spec details on asset and model views.
- Polished spec/override UIs (required flags, bool labels) and added targeted PHPUnit coverage for the flow.
- Added guard rails for asset overrides and test runs (reject disallowed overrides and require complete model specs before launching tests).
- Added unit-aware numeric normalization (e.g., TB, GHz) for model attributes while preserving the original input for audit context.
- Introduced `attribute:promote-custom` artisan command to surface and promote recurring custom enum values.

## Notes for Follow-up Agents
- PHP CLI is unavailable in this environment, so rerun the new PHPUnit cases once a PHP binary is present.
- Keep an eye on legacy files already modified in the worktree (public uploads, package-lock) when preparing commits.
- Manually exercise asset update and test-run flows to verify the new validation rules; automated tests were not executed this pass.
- To test: attempt an asset override on a non-overrideable attribute and confirm the request is rejected with a validation error.
- To test: start a test run with a required model attribute missing and ensure the run is blocked with the missing attributes listed.
- To test: enter values such as "0.5 TB" or "2.5 GHz" when editing model specs and confirm they are converted into the attribute's canonical unit while retaining the raw input for reference.
- To test: run `php artisan attribute:promote-custom <attribute_key>` (with and without `--apply`) to verify the command reports custom values and optionally creates options.

# Session Progress (2025-09-25)

## Summary
- Detailed notes: docs/agents/progress-addendum-2025-09-25-model-number.md (review alongside this summary).
- Created `AGENTS.md` to consolidate contributor guidance tailored to this fork.
- Linked the agent documentation from README.md and CONTRIBUTING.md for quicker discovery.
- Hardened EULA fallback and asset visibility logic so category listings work before settings seeding.
- Delivered the model-number attribute infrastructure (migrations, Eloquent models, admin UI, and resolver services).
- Wired asset create/edit flows for specification overrides and test runs that honor needs-test attributes.
- Updated contributor guide with documentation alignment requirements for this fork.
- Switched `.env` to local debug mode and recycled docker stack for troubleshooting.
- Fixed Passport key permissions so API endpoints load under the web container user.

## Notes for Follow-up Agents
- Review `AGENTS.md` for contributor guidance updates before expanding documentation.
- Backfill existing model data into the new attribute tables before enforcing required specs in production.
- Extend import/API layers to read/write the new attribute structures and add regression tests.
- If additional work occurs in a new session, create a dated addendum (e.g., `progress-2025-09-26.md`) and reference this log.
- Keep `docs/fork-notes.md` focused on high-level feature deltas; log incremental fixes here in `PROGRESS.md`.
- Keep README.md and CONTRIBUTING.md references in sync if the agent docs move or get renamed.
- Session closed for 2025-09-25; resume outstanding work next shift.

# Session Progress (2025-09-28)
You dropped in the middle of removing sku's and we stopped the session:
your last message:
SKUs are still doing real work today; they’re more than a relic of the single-model-number era:

Variant labelling & filters – AssetModel::skus() powers the Admin → SKUs screens, usage dashboards, and the “SKU and Model reports” in app/Http/Controllers/ReportsController. Shops tag outgoing stock (“T480 – 16GB/512GB”) and filter inventory by that label; dropping SKUs removes those grouping/report pivots.
Test history joins – test_runs.sku_id links test results to the exact outgoing variant. Ops reports (and alerting) use it to pinpoint which SKU failed diagnostics, even when multiple trims share the same base model number.
Legacy reporting & API contracts – Existing LiveSlides/BI exports (and API clients) call GET /api/v1/skus or pass sku_id= to the assets endpoint. Removing the entity is a breaking change for those consumers.
The newer model-number system overlaps with SKU duties (spec presets; attribute overrides), so if you’re committed to consolidating on model numbers only, here’s what the SKU layer currently offers that you’d need to rehome:

Variant-specific naming visible in asset lists, exports, and queue labels.
Reporting filters and API params tied to sku_id.
Historical analytics that assume test_runs and assets reference SKUs.
If you’re ready to deprecate SKUs, we can map each of those behaviors onto the model-number + attribute pathway (e.g., expose model-number labels where we display SKU names, move the test_runs foreign key to model_number_id, add report filters for presets, etc.). That’s more involved than a straight delete but keeps workflows intact. Let me know if you want a concrete migration plan.
my last message:
there are multiple duplicate functions that still need to be removed, sku will be one of them. and sku doesnt fail diagnostics, a specific model does.





**Session closed** — 2025-10-28 13:38


## Notes for Next Session (2025-11-19)
- TODO: Clean up the QR label sizing/margins once more and validate on hardware (short-term).
- TODO: Implement one-click direct printing from the asset view to connected/network LabelWriter printers (long-term).

# Session Progress (2025-12-17)
- Reviewed AGENTS.md, fork-notes, and recent agent addenda before making changes; started this session log for traceability.
- Investigated the specification details table overflow on narrow mobile widths; identified Bootstrap's responsive table rule forcing `white-space: nowrap` as the cause of horizontal overflow.
- Added a targeted mobile override to allow spec table cells to wrap within their parent so the block stays inside the asset view at ~327 px widths.
- Made the scan camera viewport dynamically size itself to the incoming stream aspect ratio while staying within the page frame; height now adapts per device instead of staying at a fixed width/aspect.
- Removed leftover manual-entry hooks from the scan script that were throwing a runtime error and blocking camera startup after the manual form was dropped.
- Follow-up: verify the asset view on an A5/phone viewport after cache clears; rerun Laravel view/config caches if needed once deployed.

# Session Progress (2026-01-13)

## Addendum (2026-01-13 Codex)
- Session kickoff: reviewed `AGENTS.md`, recent `PROGRESS.md` entries, and `docs/fork-notes.md` to refresh context before new work.
- Pending: confirm today's scope and start tracking outcomes.
- Open-point sweep: reviewed TODO.md, PROGRESS.md, docs/agents/*, and docs/plans/* for outstanding items.
- Updated login landing for non-admin/refurbisher users: start now redirects them to the dashboard, and the dashboard no longer falls back to the account view.
- Defaulted the new-user language selection to the creator/app locale so fresh accounts inherit the expected language when none is set explicitly.
- Simplified asset creation: removed manufacturer and requestable fields on create, and moved the status selector above spec overrides.
- Redirected all roles away from the start shortcuts to the main dashboard, and made logins land on `/` instead of `/start`.
- Hid requestable on asset edit and added a status-only update form on the hardware detail page.
- Hid requestable items from user navigation and asset detail, and disabled the requestable assets index with a 404 to keep the UI aligned with the no-checkout workflow.
- Investigated report that non-admin users with asset permissions see an empty hardware list: web `hardware.index` loads `api.assets.index`, which only requires `assets.view` and has no company scoping when FMCS is off, so the likely causes are API 401/403 or missing/denied `assets.view` in the user's permissions.

## Notes for Follow-up Agents (2026-01-13)
- Reproduce as the affected user and capture the `/api/v1/hardware` response (status + payload) to see if it is auth (401/403) or an empty dataset.
- Verify the user has `assets.view` granted (not inherited or explicitly denied) in the `users.permissions` JSON and that no group sets `assets.view` to `-1`.
- If the API is 401/403, check Passport cookie flow for web sessions and confirm the request is authenticated; if it is 200 with no rows, inspect any persisted table filters (`status`, `status_id`, search) and remove them.

# Session Progress (2026-04-07)
- Simplified the active test-run detail screen by removing the large top testing header and moving the live save/progress/history/start-run controls into the existing bottom action bar.
- Disabled the old hidden two-column preference on the active testing screen so users now stay on the single-column card flow after the header/toggle removal.
- Updated the dedicated active testing view feature test to assert the header is gone and the bottom-bar summary controls remain present.
- Moved the hardware detail QR print/download panel below the primary action buttons (`Edit`, `Run Test`, `Add Note`, `Clone`, `Delete`) so the main action stack stays grouped before the print module.
- Verification:
- `docker compose exec app php artisan view:cache` (pass)
- `docker compose exec app php artisan test tests/Feature/Tests/ActiveTestViewTest.php --env=testing` (blocked by existing sqlite testing DB corruption: `database disk image is malformed`)

# Session Progress (2026-04-09)
- traced the page-title overlap in intermediate widths to fixed-layout header offset drift: the shared layout relied on hardcoded top offsets while the custom navbar can grow in height as nav content wraps.
- confirmed a second contributing factor in that viewport band: `.main-header { max-height: 150px; }` capped header growth, so wrapped nav rows could spill into the content area instead of pushing it down.
- replaced the ad hoc fixed-offset override in `resources/views/layouts/default.blade.php` with a CSS-variable-based offset (`--fixed-header-offset`) plus runtime sync from the actual `.main-header` height.
- added a `<=991px` override to remove the header max-height cap so the top nav can expand naturally when content wraps.
- kept the existing `<=991px` content-header/pagetitle wrapping behavior so breadcrumb/title flow remains unchanged, while removing static fixed-offset guesses.
- verification:
- `docker compose exec app php artisan view:cache` (pass)
- model number create/edit UX follow-up:
- added serial-style case handling to model number code inputs (`Aa` toggle with hidden override flag) on model-number create/edit pages; uppercase is now the default behavior in the form unless override is enabled.
- enforced the same behavior server-side in `ModelNumberController` by normalizing code to uppercase unless `code_case_override` is true, so direct/manual posts follow the same rules as the UI.
- removed the model-number form checkbox for "Make this the default selection for new assets."
- verification of your default-selection question:
- model numbers are exposed individually in the selector API (`id` is `model_id:model_number_id` with explicit `model_number_id` meta).
- primary model number is still used as fallback in legacy/model-only flows, so backend primary logic remains in place for compatibility.
- verification:
- `docker compose exec app php -l app/Http/Controllers/Admin/ModelNumberController.php` (pass)
- `docker compose exec app php -l tests/Feature/Models/ModelNumberManagementTest.php` (pass)
- `docker compose exec app php artisan view:cache` (pass)
- `docker compose exec app php artisan test tests/Feature/Models/ModelNumberManagementTest.php --env=testing` (blocked by existing sqlite testing DB corruption: `database disk image is malformed`)
- model number breadcrumbs follow-up:
- added route-level breadcrumbs for `models.numbers.edit` and `models.numbers.spec.edit` in `BreadcrumbsServiceProvider`, parented under `models.show`.
- model-number edit and spec-edit pages now render breadcrumb trails automatically via the shared default layout.
- added focused breadcrumb assertions to `ModelNumberManagementTest`.
- verification:
- `docker compose exec app php -l app/Providers/BreadcrumbsServiceProvider.php` (pass)
- `docker compose exec app php -l tests/Feature/Models/ModelNumberManagementTest.php` (pass)
- `docker compose exec app php artisan view:cache` (pass)
- `docker compose exec app php artisan test tests/Feature/Models/ModelNumberManagementTest.php --env=testing --filter="breadcrumb"` (blocked by existing sqlite testing DB corruption: `database disk image is malformed`)
- hardware create/edit save action follow-up:
- added a mobile-standard floating save CTA on hardware create/edit (`visible-xs`/`visible-sm`) that submits the existing `create-form`.
- added xs/sm bottom content padding so the fixed save button does not cover lower form inputs.
- added a focused UI assertion in `EditAssetTest` for the floating save button markers on both create and edit routes.
- verification:
- `docker compose exec app php -l resources/views/hardware/edit.blade.php` (pass)
- `docker compose exec app php -l tests/Feature/Assets/Ui/EditAssetTest.php` (pass)
- `docker compose exec app php artisan view:cache` (pass)
- `docker compose exec app php artisan test tests/Feature/Assets/Ui/EditAssetTest.php --env=testing --filter="testCreateAndEditPagesRenderMobileFloatingSaveButton"` (blocked by existing sqlite testing DB corruption: `database disk image is malformed`)
- hardware edit save crash follow-up:
- fixed a backend type crash in `AssetsController@update` where `serials` (array form payload) was being assigned directly to `$asset->serial` before extracting index `1`.
- update now normalizes serial input to a scalar (`serials[1]` when array, otherwise scalar/null) before assignment so `Asset::normalizeIdentifier(?string ...)` no longer receives an array.
- added focused regression coverage in `EditAssetTest` for serial array payload shape used by the hardware edit form.
- verification:
- `docker compose exec app php -l app/Http/Controllers/Assets/AssetsController.php` (pass)
- `docker compose exec app php -l tests/Feature/Assets/Ui/EditAssetTest.php` (pass)
- `docker compose exec app php artisan view:cache` (pass)
- `docker compose exec app php artisan test tests/Feature/Assets/Ui/EditAssetTest.php --env=testing --filter="testEditAcceptsSerialArrayInputFromFormShape"` (blocked by existing sqlite testing DB corruption: `database disk image is malformed`)

- test pages start-run visibility follow-up:
- kept `Start New Run` available on all widths (including mobile) on:
- `resources/views/tests/active.blade.php` (empty-state CTA + active-run floating-bar secondary action)
- `resources/views/tests/index.blade.php` (history-page top CTA)
- normalized the temporary test markers to width-agnostic names (`tests-empty-start-run-form`, `tests-start-new-run-form`, `tests-index-start-run-form`).
- updated `tests/Feature/Tests/ActiveTestViewTest.php` and `tests/Feature/Assets/Ui/ShowAssetTest.php` assertions to match the width-agnostic markers.
- verification:
- `docker compose exec app php -l resources/views/tests/active.blade.php` (pass)
- `docker compose exec app php -l resources/views/tests/index.blade.php` (pass)
- `docker compose exec app php -l tests/Feature/Tests/ActiveTestViewTest.php` (pass)
- `docker compose exec app php -l tests/Feature/Assets/Ui/ShowAssetTest.php` (pass)
- `docker compose exec app php artisan test tests/Feature/Tests/ActiveTestViewTest.php tests/Feature/Assets/Ui/ShowAssetTest.php --env=testing` (blocked by existing sqlite testing DB corruption: `database disk image is malformed`)

- tests index UX redesign follow-up:
- redesigned `resources/views/tests/index.blade.php` into expandable run rows so users see a clearer run list first (date/user/status summary), with edit/delete actions directly in each row.
- added dropdown chevron + row-header click behavior to expand/collapse per-run details (results, notes, photos) without leaving the page.
- kept existing routes, permissions, and photo modal behavior unchanged; this is a view-only interaction/layout update.
- extended `tests/Feature/Assets/Ui/ShowAssetTest.php` assertions to cover the new run-row/toggle/details/action structure markers.
- verification:
- `docker compose exec app php -l resources/views/tests/index.blade.php` (pass)
- `docker compose exec app php -l tests/Feature/Assets/Ui/ShowAssetTest.php` (pass)
- `docker compose exec app php artisan optimize:clear` (pass)
- `docker compose exec app php artisan test tests/Feature/Assets/Ui/ShowAssetTest.php --env=testing --filter=testTestsIndexUsesStructuredResultRows` (blocked by existing sqlite testing DB corruption: `database disk image is malformed`)

- hardware detail tests-tab UX parity follow-up:
- ported the same row + dropdown test-run layout into `resources/views/hardware/view.blade.php` so the tests list inside `/hardware/{id}#tests` now matches the redesigned dedicated tests page interaction.
- moved run-level `Edit`/`Delete` actions into the row header and made details (results/photos/notes) collapse under each row.
- added row-header click delegation on the hardware tests tab to toggle details when users click anywhere on the row except the action buttons.
- added a new page marker assertion in `tests/Feature/Assets/Ui/ShowAssetTest.php` (`hardware-tests-run-list`) for the hardware tests-tab structure.
- verification:
- `docker compose exec app php -l resources/views/hardware/view.blade.php` (pass)
- `docker compose exec app php -l tests/Feature/Assets/Ui/ShowAssetTest.php` (pass)
- `docker compose exec app php artisan view:clear` (pass)
- `docker compose exec app php artisan optimize:clear` (pass)
- `docker compose exec app php artisan test tests/Feature/Assets/Ui/ShowAssetTest.php --env=testing --filter=testDetailPageTestsTabUsesSingleColumnRunList` (blocked by existing sqlite testing DB corruption: `database disk image is malformed`)

- tests index compact-row refinement follow-up:
- adjusted `resources/views/tests/index.blade.php` mobile row-header layout to stay one-line/high on small widths (removed forced wrap behavior and collapsed run identity/date/user into a single truncating primary line).
- kept inline row actions and toggle behavior intact.
- verification:
- `docker compose exec app php -l resources/views/tests/index.blade.php` (pass)
- `docker compose exec app php artisan view:clear` (pass)

- hardware tests-tab compact-row follow-up:
- adjusted `resources/views/hardware/view.blade.php` run-row header for small widths to keep each run in one compact row (no forced stacked summary/action rows).
- merged run id/date/user into a single truncating primary line, tightened mobile spacing/button sizing, and removed mobile wrap rules that were pushing headers into multiple lines.
- verification:
- `docker compose exec app php -l resources/views/hardware/view.blade.php` (pass)
- `docker compose exec app php artisan view:clear` (pass)

- tests active mobile card-density follow-up:
- updated `resources/views/tests/active.blade.php` mobile (`max-width: 576px`) card styles so Note and Photo CTAs remain side-by-side (2 columns) instead of stacking.
- slightly reduced mobile card-body spacing/padding below the title area to flatten each card visually while preserving existing interaction behavior.
- verification:
- `docker compose exec app php -l resources/views/tests/active.blade.php` (pass)
- `docker compose exec app php artisan view:clear` (pass)

- asset create redirect UX follow-up:
- removed the redirect destination dropdown from the asset **create** form in `resources/views/hardware/edit.blade.php` while keeping redirect options on asset **edit**.
- this eliminates the broken/clipping create-form redirect selector and keeps create flow defaulting to the new asset detail page (`item`) via existing controller behavior.
- added focused coverage updates:
- `tests/Feature/Assets/Ui/EditAssetTest.php`: create page does not render redirect select; edit page still does.
- `tests/Feature/Assets/Ui/StoreAssetWithMinimalDataTest.php`: create request now explicitly asserts redirect to `hardware.show` for the created asset.
- verification:
- `docker compose exec app php -l resources/views/hardware/edit.blade.php` (pass)
- `docker compose exec app php -l tests/Feature/Assets/Ui/EditAssetTest.php` (pass)
- `docker compose exec app php -l tests/Feature/Assets/Ui/StoreAssetWithMinimalDataTest.php` (pass)
- `docker compose exec app php artisan optimize:clear` (pass)
- `docker compose exec app php artisan test tests/Feature/Assets/Ui/StoreAssetWithMinimalDataTest.php tests/Feature/Assets/Ui/EditAssetTest.php --env=testing --filter="asset_can_be_created_with_minimal_data|testCreateAndEditPagesRenderMobileFloatingSaveButton"` (blocked by existing sqlite testing DB corruption: `database disk image is malformed`)

- asset name field scope fix follow-up:
- traced create-page `name` field to direct include in `resources/views/hardware/edit.blade.php` (`@include('partials.forms.edit.name', ...)`), introduced in commit `5a291ec80c` (2026-04-07), not by the redirect dropdown fix.
- updated the view so `name` renders only for existing assets (`$item->id`), removing it from hardware create flow where naming is model-driven.
- extended `tests/Feature/Assets/Ui/EditAssetTest.php` assertion: create page does not render `name="name"` while edit page still does.
- verification:
- `docker compose exec app php -l resources/views/hardware/edit.blade.php` (pass)
- `docker compose exec app php -l tests/Feature/Assets/Ui/EditAssetTest.php` (pass)
- `docker compose exec app php artisan view:clear` (pass)
- `docker compose exec app php artisan optimize:clear` (pass)
- `docker compose exec app php artisan test tests/Feature/Assets/Ui/EditAssetTest.php --env=testing --filter=testCreateAndEditPagesRenderMobileFloatingSaveButton` (blocked by existing sqlite testing DB corruption: `database disk image is malformed`)

- legacy post-create QR notification removal follow-up:
- removed the old blue `Print QR` session-notification dropdown from `resources/views/notifications.blade.php` (the non-labelwriter path that was misleading/non-working).
- removed asset-create flash payload of `qr_pdf`/`qr_png` from `AssetsController@store` so the deprecated notification path is no longer fed.
- kept the hardware detail QR label widget flow unchanged (download QR label / print to labelwriter remains the supported path).
- extended `tests/Feature/Assets/Ui/StoreAssetWithMinimalDataTest.php` to assert `qr_pdf` and `qr_png` are absent from session after asset create.
- verification:
- `docker compose exec app php -l app/Http/Controllers/Assets/AssetsController.php` (pass)
- `docker compose exec app php -l tests/Feature/Assets/Ui/StoreAssetWithMinimalDataTest.php` (pass)
- `docker compose exec app php artisan view:clear` (pass)
- `docker compose exec app php artisan optimize:clear` (pass)
- code search check for `qr_pdf`/`qr_png`/`qr-notification` in `resources/views` + `app` (no remaining hits)
- `docker compose exec app php artisan test tests/Feature/Assets/Ui/StoreAssetWithMinimalDataTest.php --env=testing` (blocked by existing sqlite testing DB corruption: `database disk image is malformed`)

## 2026-04-16
- session re-init and docs sync:
- created `docs/agents/agents-addendum-2026-04-16-session-init.md`.
- re-read `AGENTS.md`, `PROGRESS.md`, and `docs/fork-notes.md`.

- hardware list mobile toolbar alignment follow-up:
- fixed bootstrap-table mobile toolbar icon fragmentation where controls wrapped one-per-row.
- adjusted `resources/views/partials/bootstrap-table.blade.php` mobile rules so toolbar icon groups remain auto-width and wrap in compact rows.
- verification:
- `docker compose exec app php -l resources/views/partials/bootstrap-table.blade.php` (pass)
- `docker compose exec app php artisan view:clear` (pass)

- hardware detail tests FAB consistency follow-up:
- changed mobile tests floating action in `resources/views/hardware/view.blade.php` from icon-only circle to save-style pill with visible label (`tests.start_new_run`) to align with the hardware mobile save CTA pattern.
- added marker assertion in `tests/Feature/Assets/Ui/ShowAssetTest.php` for the FAB label.
- verification:
- `docker compose exec app php -l resources/views/hardware/view.blade.php` (pass)
- `docker compose exec app php -l tests/Feature/Assets/Ui/ShowAssetTest.php` (pass)
- `docker compose exec app php artisan optimize:clear` (pass)
- `docker compose exec app php artisan test tests/Feature/Assets/Ui/ShowAssetTest.php --env=testing --filter=testDetailPageRendersResponsiveTestsStartRunActions` (blocked by existing sqlite testing DB corruption: `database disk image is malformed`)

- scan page viewport expansion follow-up:
- widened the scan page container in `resources/views/scan/index.blade.php` by removing 720px width caps and switching to fluid wrapper layout for larger viewport usage.
- increased scan area minimum height and reduced outer padding so camera occupies more screen space.
- adjusted small-screen scan viewport toward portrait usage (`aspect-ratio: 3 / 4` with higher vh-driven minimum height) to reduce left/right letterboxing and make the camera area visibly taller on phones.
- verification:
- `docker compose exec app php -l resources/views/scan/index.blade.php` (pass)
- `docker compose exec app php artisan view:clear` (pass)

- model specification validation visibility follow-up:
- improved `resources/views/models/spec.blade.php` error UX with a top-level attribute error navigator that lists failing fields and allows one-click jump/focus to the related attribute detail panel.
- added invalid-state highlighting on selected attribute rows and detail panels via `resources/views/models/model_numbers/partials/selected-attribute-item.blade.php` and `resources/views/models/model_numbers/partials/attribute-detail.blade.php`.
- updated spec page JS initialization to auto-open the first invalid attribute when errors are present instead of always opening the first selected attribute.
- updated `app/Services/ModelAttributes/ModelAttributeManager.php` required-attribute validation to emit both summary (`attributes`) and per-field (`attributes.{id}`) errors so all failing fields can be surfaced/highlighted in one pass.
- added focused UI coverage in `tests/Feature/Models/ModelSpecificationUiTest.php` for:
- navigator rendering on attribute validation errors.
- per-field required-attribute error emission.
- verification:
- `docker compose exec app php -l app/Services/ModelAttributes/ModelAttributeManager.php` (pass)
- `docker compose exec app php -l resources/views/models/spec.blade.php` (pass)
- `docker compose exec app php -l resources/views/models/model_numbers/partials/selected-attribute-item.blade.php` (pass)
- `docker compose exec app php -l resources/views/models/model_numbers/partials/attribute-detail.blade.php` (pass)
- `docker compose exec app php -l tests/Feature/Models/ModelSpecificationUiTest.php` (pass)
- `docker compose exec app php artisan optimize:clear` (pass)
- `docker compose exec app php artisan test tests/Feature/Models/ModelSpecificationUiTest.php --env=testing` (blocked by existing sqlite testing DB corruption: `database disk image is malformed`)

- model spec parse error hotfix:
- fixed a Blade parse regression in `resources/views/models/spec.blade.php` by replacing one-line `@php(...)` assignment with a standard `@php ... @endphp` block at the top of the section.
- cleared and rebuilt compiled views in container; linted the compiled spec view file to confirm no syntax errors remain.
- verification:
- `docker compose exec app php artisan view:clear` (pass)
- `docker compose exec app php artisan view:cache` (pass)
- `docker compose exec app sh -lc "grep -R -n 'assignedDefinitionIds' storage/framework/views | head"` (pass)
- `docker compose exec app php -l storage/framework/views/38ebbffe634f906cee186992fa90cb21.php` (pass)

- test types/task ordering support:
- added persistent `display_order` to `test_types` via migration (`2026_04_16_110000_add_display_order_to_test_types_table.php`) with backfill from current alphabetical order.
- added admin reorder API endpoint `PATCH admin/testtypes/reorder` and request validation (`ReorderTestTypesRequest`) to save drag-and-drop ordering safely.
- updated test type management UI (`resources/views/settings/testtypes.blade.php`) with draggable row handles and client-side persistence calls to the reorder endpoint.
- switched test type selection/query ordering to `display_order` (with `name/id` fallback) and updated active run result ordering to follow configured test order.
- updated test run creation flow so new run tasks are created in configured `display_order`.
- added feature coverage:
- `ManageTestTypesTest::test_admin_can_reorder_test_types`
- `StartNewTestRunTest::test_start_new_run_uses_display_order_for_created_results`
- verification:
- `docker compose exec app php -l app/Models/TestType.php` (pass)
- `docker compose exec app php -l app/Models/TestRun.php` (pass)
- `docker compose exec app php -l app/Http/Controllers/Admin/TestTypeController.php` (pass)
- `docker compose exec app php -l app/Http/Controllers/TestRunController.php` (pass)
- `docker compose exec app php -l app/Http/Controllers/TestResultController.php` (pass)
- `docker compose exec app php -l app/Http/Requests/TestType/ReorderTestTypesRequest.php` (pass)
- `docker compose exec app php -l database/migrations/2026_04_16_110000_add_display_order_to_test_types_table.php` (pass)
- `docker compose exec app php -l database/factories/TestTypeFactory.php` (pass)
- `docker compose exec app php -l tests/Feature/Settings/ManageTestTypesTest.php` (pass)
- `docker compose exec app php -l tests/Feature/Assets/StartNewTestRunTest.php` (pass)
- `docker compose exec app php artisan route:list --name=settings.testtypes.reorder` (pass)
- `docker compose exec app php artisan view:clear` (pass)
- `docker compose exec app php artisan view:cache` (pass)
- `docker compose exec app php artisan test tests/Feature/Settings/ManageTestTypesTest.php tests/Feature/Assets/StartNewTestRunTest.php --env=testing` (blocked by existing sqlite testing DB corruption: `database disk image is malformed`)

- test type drag reorder interaction fix:
- replaced HTML5 table-row drag handling on `resources/views/settings/testtypes.blade.php` with jQuery UI `sortable()` using the drag handle as the reorder handle.
- retained the same reorder persistence endpoint (`settings.testtypes.reorder`) and rollback-on-failure behavior.
- fixed a script-stack wiring bug where the page used `@push('scripts')` while the layout renders `@stack('js')`; moved the reorder script to `@push('js')` so drag behavior initializes.
- adjusted drag handle visuals to be larger and centered in the reorder column.
- verification:
- `docker compose exec app php -l resources/views/settings/testtypes.blade.php` (pass)
- `docker compose exec app php artisan view:clear` (pass)
- `docker compose exec app php artisan view:cache` (pass)

- components replacement / traceability planning:
- added a full handoff-ready implementation plan at `docs/plans/components-replacement-part-traceability-work-orders.md`.
- plan scope covers:
- replacing the old pooled `components` module with unique component definitions/instances/events.
- persisted tray flow with stale-item verification escalation.
- asset-page expected/default components vs installed/history separation.
- mobile-first QR/search remove/install workflows.
- customer work-order and read-only portal foundation.
- no code execution verification was required for the planning document itself.

- test type drag reorder compatibility follow-up:
- replaced pointer-only drag handling in `resources/views/settings/testtypes.blade.php` with a dual-path implementation:
- pointer events path for modern browsers.
- explicit mouse/touch fallback path for browsers with incomplete pointer support.
- added permissive primary-pointer detection and non-`fetch` AJAX fallback for reorder persistence.
- retained rollback behavior when reorder persistence fails.
- verification:
- `node --check storage/tmp-testtypes-reorder.js` (pass; extracted script syntax check)
- `docker compose exec app php artisan view:clear` (pass)
- `docker compose exec app php artisan view:cache` (pass)
- `docker compose exec app php artisan test tests/Feature/Settings/ManageTestTypesTest.php --env=testing` (blocked by existing sqlite testing DB corruption: `database disk image is malformed`)

## 2026-04-17
- session re-init and environment baseline:
- created `docs/agents/agents-addendum-2026-04-17-session-init.md`.
- re-read `AGENTS.md`, `PROGRESS.md`, `docs/fork-notes.md`, `TODO.md`, the recent 2026-04-16 / 2026-04-09 / 2026-04-07 / 2026-04-02 addenda, and `docs/plans/components-replacement-part-traceability-work-orders.md`.
- confirmed this cleaned workstation is currently on the local HTTP dev path (`docker-compose.local.yml`, `docker/nginx.local.conf`, local `.env`) rather than the later internal HTTPS hostname path.
- identified environment drift that must be reconciled during setup:
- `.env.testing` is missing, while `tests/TestCase.php` now hard-fails without it and requires sqlite-only test DB targets.
- the main compose/nginx stack and Dusk/browser tests still reference the legacy internal hostname.
- recent April session notes describe the intended mobile/LAN hostname as the newer internal HTTPS hostname.
- reviewed the current legacy components surface (`Component` model, component web/API controllers, routes, views, asset components tab, and component QR label generation) to prepare for the planned pooled-components replacement.
- no code/runtime behavior was changed in this session block; initialization and context sync only.
- local IP access follow-up:
- detected the active LAN interface and switched the local stack to IP-based HTTP access for same-network testing.
- updated local app URL in `.env` to the active LAN address and published local port.
- widened the local nginx vhost in `docker/nginx.local.conf` so the HTTP dev stack accepts direct IP-host requests instead of only `localhost` / `127.0.0.1`.
- fixed the local bootstrap handoff in `docker/app/entrypoint.local.sh` to execute the repo-mounted `docker/app/entrypoint.sh` instead of the stale image-baked copy.
- normalized `docker/app/entrypoint.sh` to LF line endings so the Linux container no longer fails with `env: 'bash\\r': No such file or directory`.
- started the isolated local stack under compose project `snipeit-local`; the local HTTP dev URL now uses the machine's active LAN address.
- verification:
- `docker ps --filter "name=snipeit-local"` shows `app`, `web`, and `db` up, with `web` publishing the configured local HTTP port.
- `Invoke-WebRequest <local-ip-based-login-url>` (pass, HTTP 200).
- attempted to add a Windows inbound firewall rule for the published local HTTP port, but `New-NetFirewallRule` failed with `Access is denied`; LAN access from other devices may still require a manual admin-side firewall allow rule if the current Windows profile blocks inbound traffic.
- components traceability / work-order foundation implementation:
- added new component/work-order foundation schema via `2026_04_17_120000_create_component_traceability_tables.php`:
- `component_definitions`
- `component_storage_locations`
- `component_instances`
- `component_events`
- `model_number_component_templates`
- `work_orders`
- `work_order_assets`
- `work_order_tasks`
- `work_order_user_access`
- added new core models and relations for the replacement domain:
- `ComponentDefinition`
- `ComponentStorageLocation`
- `ComponentInstance`
- `ComponentEvent`
- `ModelNumberComponentTemplate`
- `WorkOrder`
- `WorkOrderAsset`
- `WorkOrderTask`
- added component lifecycle services for instance creation, asset extraction, tray removal, install, stock moves, verification, and destruction-state transitions.
- added random asset-style component tag generation (`INBIT-XX0000` family) and component-instance QR label generation support.
- added tray aging command `components:age-tray` and scheduled it every 15 minutes, with stale transfer escalation to `needs_verification`.
- added optional portal/work-order visibility foundation through explicit `work_order_user_access` plus nullable `company_id` compatibility on work orders.
- added upload route/controller mapping support for `component-instances` and `work-orders`, plus `Actionlog` path/url support for those new upload types.
- extended `Asset` with tracked/sourced component relations and `ModelNumber` with expected component template relations.
- replaced component permission definitions with the new semantics:
- `components.view`
- `components.create`
- `components.update`
- `components.delete`
- `components.extract`
- `components.install`
- `components.move`
- `components.verify`
- `components.manage_definitions`
- `components.manage_storage_locations`
- added new permissions:
- `workorders.view`
- `workorders.create`
- `workorders.update`
- `workorders.manage_visibility`
- `portal.view`
- added factories for the new component/work-order models and focused tests for:
- component lifecycle transitions
- component-instance file upload route
- explicit work-order portal visibility
- local testing env follow-up:
- created a local `.env.testing` pointing at the isolated sqlite testing DB path and created the sqlite file so guarded test commands can target the safe testing database.
- verification:
- `php -l` pass on all changed and new PHP files.
- `php artisan optimize:clear` inside the local app container (pass).
- `php artisan migrate --env=testing --pretend --path=database/migrations/2026_04_17_120000_create_component_traceability_tables.php` inside the local app container (pass; SQL emitted for the full new schema and seeded default component storage locations).
- `php artisan about --env=testing` inside the local app container confirmed uncached `testing` environment with sqlite driver.
- focused PHPUnit execution remains blocked in this environment:
- `php artisan test ... --env=testing` fails because `SebastianBergmann\Environment\Console` is not present.
- direct `vendor/bin/phpunit` execution is also unavailable because the current container image does not include a phpunit binary under `vendor/bin`.

- continued the component replacement cutover beyond foundation:
- enforced global uniqueness between `component_instances.component_tag` and `assets.asset_tag`; generated component tags now skip any value already used by an asset.
- added company-scope participation for the new component definition / component instance models so FMCS-style query scoping still applies where the new registry replaces old component lists.
- replaced the API `components` controller surface with instance-based behavior:
- registry/detail/update/delete now operate on `ComponentInstance`
- new action endpoints cover `remove-to-tray`, `install`, `move-to-stock`, `flag-needs-verification`, `confirm-verification`, `mark-destruction-pending`, and `mark-destroyed`
- `components/{component_id}/assets` now returns lineage events instead of pooled pivot rows
- corrected resource route parameter mapping so both API and web component routes use `{component_id}` and match controller model binding.
- replaced the component presenter/transformer and web controller/detail view with instance-based data instead of pooled quantity/checkin-checkout semantics.
- removed the misleading add button from the components registry until direct create/edit UI exists.
- replaced the asset detail `Components` tab with:
- installed tracked components
- expected model-number component templates
- per-asset component history derived from immutable component events
- switched asset component counts and component cost display to tracked-instance data instead of legacy pooled relations.
- removed component checkout/checkin routes from the active web route surface.
- replaced or removed the old pooled-component test surface:
- updated component API/UI tests to target `ComponentInstance`
- added API coverage for cross-entity tag collisions and lifecycle action endpoints
- removed obsolete component checkout/checkin tests tied to the old pooled mechanics
- verification:
- `php -l` pass on all newly changed PHP files in this continuation block.
- `php artisan route:list --name=components` inside the local app container confirmed the live surface now only exposes the new component instance routes and action endpoints, with `{component_id}` parameter binding.

- continued the component replacement cutover to remove the remaining mixed old/new operational surfaces before starting work-order or portal UI:
- replaced asset component history assembly with a direct `ComponentEvent` query in `AssetsController`, including soft-deleted component instances and post-removal events for any component that touched the asset.
- updated the asset Components tab to render event-driven lineage instead of deriving history from current/sourced relations, so removed or deleted tracked parts still remain visible on the asset page.
- enforced company-scope propagation in `ComponentLifecycleService`:
- instance creation now derives `company_id` from explicit input, current/source asset, component definition, or actor company
- install now realigns the component scope with the destination asset
- FMCS mode now throws if a tracked component would otherwise be created without a company scope
- completed the operational filter cutover for tracked components:
- API component index now supports location-hierarchy filtering via component storage locations
- API component index now supports supplier and manufacturer filters for the new instance model
- component list location display now uses the new current-location text instead of the old location-link assumption
- updated visible remaining operational component counts/tabs to the new instance model on:
- location detail
- company detail
- supplier detail
- manufacturer detail
- disabled the registry add button in the bootstrap-table component surface so operational screens no longer suggest a direct create UI that is intentionally deferred.
- removed the now-dead legacy component checkout/checkin controllers and their unused Blade views after confirming no live route or test references remained.
- added the admin settings catalog for the new component metadata:
- `settings.component_definitions.*`
- `settings.component_storage_locations.*`
- added settings routes, controllers, menu/sidebar entries, settings index cards, reusable forms, and list/detail pages for component definitions and component storage locations.
- added focused tests for:
- asset history retaining component lineage after removal, stock move, and soft delete
- FMCS company propagation and scope enforcement in component lifecycle flows
- component API filtering by location hierarchy, supplier, and manufacturer
- settings authorization and CRUD entry points for component definitions and component storage locations
- verification:
- `php -l` pass on all changed PHP files and added focused test files in this tranche.
- `php artisan route:list --name=settings.component` inside the local app container confirmed the new settings route surface.
- `php artisan route:list --path=admin/settings/component-definitions` and `--path=admin/settings/component-storage-locations` inside the local app container confirmed the catalog routes.
- focused PHPUnit execution remains blocked on this workstation by the current container/dependency state, so the new tests were added but not run end-to-end here.

- implemented the next tranche on top of the stabilized component/event model: internal work-order UI and authenticated read-only portal UI.
- added internal work-order routes and server-rendered screens:
- `/work-orders`
- `/work-orders/create`
- `/work-orders/{workOrder}`
- `/work-orders/{workOrder}/edit`
- added nested internal mutations for device and task management on the work-order detail page:
- `work-orders.assets.store|update|destroy`
- `work-orders.tasks.store|update|destroy`
- the internal work-order detail page now acts as the operational hub with:
- summary
- devices
- tasks
- component activity sourced directly from `component_events`
- linked assets now snapshot `asset_tag` and `serial` automatically when a real asset is selected on work-order devices.
- added read-only authenticated portal pages under:
- `/account/work-orders`
- `/account/work-orders/{workOrder}`
- portal visibility continues to use the existing `WorkOrder::scopeVisibleTo()` / `isVisibleTo()` rules with:
- explicit visible users via `work_order_user_access`
- optional company match for users with `portal.view`
- `visibility_profile` behavior implemented as:
- `full`: shows component activity and customer notes
- `basic`: hides component activity while keeping visible tasks and customer-safe notes
- `custom`: uses `portal_visibility_json.show_components` and `portal_visibility_json.show_notes_customer`
- surfaced work-order/task links back into component history on the component detail page so the new work-order UI is connected into the existing traceability chain.
- added navigation/entry points:
- internal sidebar entry for work orders
- staff start-page `Manage` button now points to internal work orders when permitted
- account dropdown and account dashboard button for `My Work Orders` when `portal.view` is present
- added focused tests for:
- internal work-order route authorization and summary create/update flow
- asset snapshot capture on linked work-order devices
- task create/update flow
- portal visibility filtering, customer-safe task rendering, and `basic` vs `full` component activity behavior
- component detail rendering of linked work order/task history references
- verification:
- `php -l` across all newly changed PHP files and added tests (pass)
- `php artisan route:list --name=work-orders` in the local app container (pass)
- `php artisan route:list --name=account.work-orders` in the local app container (pass)
- `php artisan optimize:clear` in the local app container (pass)
- targeted `php artisan test ... --env=testing` remains blocked by the current container image because Laravel's test wrapper still fails on missing `SebastianBergmann\Environment\Console`
- direct `vendor/bin/phpunit` remains unavailable in the same container because the binary is not present under `vendor/bin`

- completed the post-implementation gap-closure pass for the work-order/portal tranche:
- internal work-order component activity now links `fromAsset` and `toAsset` entries back to asset detail pages
- added focused test coverage for:
- unauthorized internal create/show/edit/update work-order access
- company-matched portal visibility without explicit visible-user access
- internal sidebar and account-area work-order entry visibility
- start shortcut templates pointing their manage action at internal work orders
- verification:
- `php -l` on the newly added/updated gap-closure tests (pass)

- repaired the PHPUnit/test runtime for this workstation/container:
- installed missing Composer dev dependencies inside the app container so Laravel's test runner and `vendor/bin/phpunit` are available again
- normalized test DB configuration to use in-memory sqlite instead of the mounted sqlite file:
- `phpunit.xml` now sets `DB_DATABASE=:memory:`
- `.env.testing.example` now documents sqlite in-memory defaults
- `config/database.php` now respects `DB_DATABASE` for the `sqlite` connection instead of hardcoding `database/database.sqlite`
- `TESTING.md` now documents `DB_CONNECTION=sqlite` with `DB_DATABASE=:memory:`
- fixed the newly added work-order/settings tests to disable the app CSRF middleware class consistently for mutating requests
- hardened `ComponentInstance` display-name fallback so work-order/component UI rendering no longer trips over inherited `display_name` access
- added/retained focused coverage for:
- work-order authorization and CRUD flow
- work-order asset/task mutations
- portal visibility paths
- work-order navigation visibility
- component detail history links to work orders/tasks
- component definition settings
- component storage location settings
- verification:
- `php artisan optimize:clear` in the app container (pass)
- combined targeted suite in the app container (pass):
- `php artisan test tests/Feature/WorkOrders tests/Feature/Components/Ui/ShowComponentTest.php tests/Feature/Settings/ComponentDefinitionSettingsTest.php tests/Feature/Settings/ComponentStorageLocationSettingsTest.php --env=testing`
- result: `23` tests passed, `82` assertions

- continued regression testing beyond the initial targeted tranche:
- restarted the local app container and cleared Laravel caches after the browser surfaced stale 500s on admin component pages
- container logs showed the browser failures were still serving the earlier `ComponentInstance::getDisplayNameAttribute($value)` fatal; the app container was restarted after the fix so PHP-FPM uses the corrected code
- fixed a real FMCS settings-key bug in the component lifecycle/admin settings path:
- `ComponentLifecycleService` and `ComponentDefinitionSettingsController` now read `full_multiple_companies_support` consistently
- fixed component web delete behavior to log deletions for `ComponentInstance`
- fixed the exception-handler route mapping for `ComponentInstance` model-not-found cases so hidden/missing component records redirect to `components.index` instead of throwing `Route [componentinstances.index] not defined`
- aligned component web tests with the real app middleware/redirect behavior by disabling the app CSRF middleware class in the mutating web tests
- verification:
- full component feature suite in the app container (pass):
- `php artisan test tests/Feature/Components --env=testing`
- result: `35` tests passed, `134` assertions
- broader asset UI suite also executed:
- `php artisan test tests/Feature/Assets/Ui --env=testing`
- result: large pre-existing/non-tranche failure surface remains in asset UI (`49` failed, `26` passed)
- relevant note:
- the new component-related asset history test remains green inside that broader asset UI run
- the remaining asset UI failures are not confined to the new component/work-order tranche and include route mismatches, redirect expectation drift, and existing asset form/delete flows

- cleared the final local manual-testing blocker after the browser hit `SQLSTATE[42S02]` for missing `component_definitions`
- confirmed the local dev MySQL schema was behind on:
- `2026_04_16_110000_add_display_order_to_test_types_table`
- `2026_04_17_120000_create_component_traceability_tables`
- patched `2026_04_17_120000_create_component_traceability_tables` for live MySQL compatibility in this fork:
- replaced `foreignId()` references to legacy core tables with matching `unsignedInteger()` foreign keys
- shortened the `model_number_component_templates` composite index name to fit MariaDB/MySQL identifier limits
- cleaned up only the partial tables left by failed local migration attempts and reran the migration successfully
- local migration status now shows both new migrations as applied
- final verification after the migration compatibility patch:
- `php artisan optimize:clear` in the app container (pass)
- `php artisan test tests/Feature/WorkOrders tests/Feature/Components tests/Feature/Settings/ComponentDefinitionSettingsTest.php tests/Feature/Settings/ComponentStorageLocationSettingsTest.php --env=testing` (pass)
- result: `56` tests passed, `211` assertions

- implemented Phase 4 browser lifecycle and tray workspace for tracked components:
- `components.create` is now a real manual stock-intake form for loose components
- added browser lifecycle routes/actions for:
- remove to tray
- install into asset
- move to stock
- flag needs verification
- confirm verification
- mark destruction pending
- mark destroyed
- existing non-installed delete flow remains on component detail
- added a dedicated tray workspace at `components.tray`:
- current-user `in_transfer` components only
- held duration + warning state using tray-aging thresholds
- inline tray actions for install handoff, move to stock, needs verification, destruction pending, and open
- upgraded the asset Components tab from read-only to operational:
- installed rows now expose `Open` and `Remove To Tray`
- expected rows now expose `Install From Tray`, `Install Existing`, and `Register Component`
- added asset-context forms for:
- install from tray
- install existing loose component
- register-and-install component
- extract untracked component directly to tray
- component detail pages now expose actionable lifecycle panels instead of history-only rendering
- main layout now shows a persistent tray badge/count and a sidebar `My Tray` entry
- hid the deferred/non-functional serial-tracking control from the component-definition admin UI (form and list)
- verification after Phase 4 implementation:
- `php artisan optimize:clear` in the app container (pass)
- `php artisan route:list --name=components` (pass)
- `php artisan route:list --name=hardware.components` (pass)
- test runtime had to be restored after restart with `composer install` in the app container so `sebastian/environment` and the PHPUnit binaries were present again
- focused Phase 4 + settings suite (pass):
- `php artisan test tests/Feature/Components/Ui tests/Feature/Settings/ComponentDefinitionSettingsTest.php --env=testing`
- result: `21` tests passed, `82` assertions
- adjacent regression suite for touched shared surfaces (pass):
- `php artisan test tests/Feature/WorkOrders tests/Feature/Assets/Ui/ComponentHistoryTest.php --env=testing`
- result: `16` tests passed, `69` assertions
- scope note:
- full-project regression was not run in this block; verification covered the new browser workflow, component settings, work-order/portal linkage, and asset component history surfaces touched by the phase

- implemented the attribute simplification and component-driven spec tranche:
- removed user-facing attribute versioning from admin UX:
- no version column on the attribute index
- no `New Version` action or version-create flow in normal admin screens
- attribute keys are now editable in place
- datatype remains immutable after create
- enum option value edits now cascade current rows that reference the same option id across:
- `model_number_attributes`
- `asset_attribute_overrides`
- `component_definition_attributes`
- historical `test_results.expected_value` and `expected_raw_value` remain unchanged
- added shared attribute contributions on component definitions:
- new `component_definition_attributes` table and model
- component-definition admin forms now manage shared attribute contributions validated through the existing attribute normalization pipeline
- unified model-number specification editing:
- the model-number spec page now contains manual attributes, expected components, and an effective-spec preview on one screen
- legacy expected-component routes remain as compatibility entry points but redirect to the unified spec screen anchor
- added runtime component-derived spec resolution:
- model-number previews aggregate linked expected-component templates
- asset effective specs now resolve with precedence:
- asset override
- installed-component-derived value
- manual model value
- expected-component-derived model value
- derived provenance/source labels are exposed in the model preview and asset detail/spec override UI
- verification after the attribute/component tranche (pass):
- `php artisan optimize:clear` in the app container
- `php artisan test --env=testing tests/Feature/AttributeDefinitionLifecycleTest.php tests/Feature/Settings/ComponentDefinitionSettingsTest.php tests/Feature/Models/ModelNumberManagementTest.php tests/Feature/Models/ModelNumberComponentTemplateManagementTest.php tests/Feature/Models/ModelSpecificationComponentPreviewTest.php tests/Feature/ComponentDerivedAttributeResolutionTest.php tests/Feature/AttributeTestRunGenerationTest.php tests/Feature/Assets/Ui/ComponentHistoryTest.php tests/Feature/Components/Ui/ComponentBrowserWorkflowTest.php`
- result: `38` tests passed, `171` assertions

- tightened the unified model-number specification UX after manual save confusion:
- expected-component validation errors on `models/spec` are now surfaced in the top error navigator and inline on the affected component row instead of only showing the generic “check the form” banner
- quick regression re-run after the UI fix:
- `php artisan test --env=testing tests/Feature/Models/ModelSpecificationComponentPreviewTest.php tests/Feature/Models/ModelNumberManagementTest.php`
- result: `11` tests passed, `40` assertions

- refined component-definition attribute contribution editing to match the model-spec workflow more closely:
- replaced the raw contribution attribute select with a quicksearch/autocomplete picker keyed by shared attribute definitions
- replaced the free-text contribution value box with datatype-aware inputs and hints:
- bool attributes now use yes/no selection
- int/decimal attributes now use numeric inputs with min/max/step hints when constraints exist
- enum attributes now expose the same option guidance and autocomplete list used on model-spec values
- added validation so partially typed contribution rows now fail clearly instead of being silently ignored when no valid attribute is selected
- remapped contribution normalization errors back onto the edited row so invalid values show on `attribute_contributions.{row}.value`
- focused verification after the component-definition form UX update:
- `php artisan optimize:clear` in the app container
- `php artisan test --env=testing tests/Feature/Settings/ComponentDefinitionSettingsTest.php`
- result: `8` tests passed, `32` assertions

- follow-up fix for the component-definition attribute picker after manual retest feedback:
- selecting a search result now applies on `mousedown` instead of relying only on `click`, which avoids losing the selection after clearing/retyping the search field
- the picker now also handles the native `<input type="search">` clear action through the `search` event, so clearing a picked attribute resets the hidden id/value field consistently
- focused verification after the picker interaction fix:
- `php artisan optimize:clear` in the app container
- `php artisan test --env=testing tests/Feature/Settings/ComponentDefinitionSettingsTest.php`
- result: `8` tests passed, `32` assertions

- replaced the broken fixed-enum `datalist` pattern with real selects on shared spec-entry surfaces:
- model-spec manual attribute editing now renders fixed enum attributes as `<select>` controls instead of free-text + datalist
- component-definition attribute contribution rows now render fixed enum values as `<select>` controls both on first paint and when the row updates client-side after attribute selection
- custom-value enums still keep the text + suggestion flow; only fixed-option enums were changed
- focused verification after the enum control fix:
- `php artisan optimize:clear` in the app container
- `php artisan test --env=testing tests/Feature/Settings/ComponentDefinitionSettingsTest.php tests/Feature/Models/ModelSpecificationComponentPreviewTest.php`
- result: `12` tests passed, `52` assertions

- simplified the model-number expected-components editor:
- moved the `Expected Components` block above `Effective Specification Preview` on the unified model spec page
- removed `slot_name` and `notes` from the model-number expected-component UI
- updated the unified model-spec save path and compatibility component-template controller so those fields are cleared to `null` instead of lingering from older edits
- adjusted the expected-components help copy to point at the preview below the editor
- focused verification after the model-number expected-component simplification:
- `php artisan optimize:clear` in the app container
- `php artisan test --env=testing tests/Feature/Models/ModelSpecificationComponentPreviewTest.php tests/Feature/Models/ModelNumberComponentTemplateManagementTest.php tests/Feature/Models/ModelNumberManagementTest.php`
- result: `14` tests passed, `62` assertions

- tightened the model-number expected-components workflow further around catalog definitions:
- removed the `required by default` checkbox from the unified model-number spec UI; saved expected components are now always required by default
- removed the `expected_name` field from the unified model-number spec UI; template names are now derived from the selected component definition
- added drag-handle reordering on expected-component rows while keeping the existing Up/Down buttons as fallback controls
- updated both the unified model-spec save path and the compatibility expected-component controller so definition-backed template rows automatically persist:
- `expected_name = component_definition.name`
- `is_required = true`
- focused verification after the expected-component definition/drag update:
- `php artisan optimize:clear` in the app container
- `php artisan test --env=testing tests/Feature/Models/ModelSpecificationComponentPreviewTest.php tests/Feature/Models/ModelNumberComponentTemplateManagementTest.php tests/Feature/Models/ModelNumberManagementTest.php`
- result: `14` tests passed, `65` assertions

- refined the unified model-number expected-components row layout:
- moved the drag/sort handle into the row itself, positioned left of the catalog definition field instead of in a separate header strip
- stopped seeding an empty expected-component row when the model number has no templates yet
- added an explicit empty-state message instead, and kept row creation behind the `Add Expected Component` action only
- focused verification after the expected-component row layout cleanup:
- `php artisan optimize:clear` in the app container
- `php artisan test --env=testing tests/Feature/Models/ModelSpecificationComponentPreviewTest.php tests/Feature/Models/ModelNumberComponentTemplateManagementTest.php tests/Feature/Models/ModelNumberManagementTest.php`
- result: `15` tests passed, `68` assertions

- cleaned up redundant provenance noise on the hardware details specification section:
- plain model-backed attributes no longer render repeated `Manual model value` source badges or `Contributors: Manual model value` helper text
- component-derived rows still keep contributor detail, and override rows still keep their inherited-baseline context
- added a hardware-details regression test covering a manual-model-only asset
- focused verification after the hardware details provenance cleanup:
- `php artisan optimize:clear` in the app container
- `php artisan test --env=testing tests/Feature/Assets/AssetSpecificationOverrideTest.php --filter=hide_redundant_manual_model_meta`
- `php artisan test --env=testing tests/Feature/Assets/Ui/ComponentHistoryTest.php`
- result: `3` tests passed, `14` assertions

- refined the expected-baseline asset component roster and component detail flow:
- asset component lists now render non-default rows (`Extra` / `Custom`) first, then insert a slim `Expected baseline` separator before the expected rows
- component detail pages now include a small note editor instead of leaving notes read-only behind the unimplemented full edit flow
- focused verification after the roster split + note editor update:
- `php artisan optimize:clear` in the app container
- `php artisan test --env=testing tests/Feature/Assets/Ui/ComponentHistoryTest.php tests/Feature/Components/Ui/ShowComponentTest.php tests/Feature/Components/Ui/ComponentWorkflowPagesTest.php`
- result: `9` tests passed, `56` assertions

- stabilized the expected-baseline component UX and semantics around calculated numeric specs:
- hardware detail and asset spec-override surfaces now show calculated totals as an explicit split between `Expected/default subtotal` and `Extras/custom subtotal`, with matching contributor summaries instead of one ambiguous contributors line
- kept the current baseline rule intact and locked it in tests: matching tracked installed components stay `Extra` until the expected baseline has been explicitly reduced, after which they fill as `Expected (Tracked)`
- removed the remaining web `installed_as` / slot inputs and display from component install/transfer screens, the asset component roster, and the component detail page while keeping lower-level lifecycle compatibility in place
- deleted the dead legacy `components/partials/actions.blade.php` form-first partial that was no longer referenced anywhere in the repo
- aligned the asset override manager regression test with the current validation key shape (`attributes.{id}`)
- focused verification after the stabilization pass:
- `php artisan optimize:clear` in the app container
- `php artisan test --env=testing tests/Feature/ComponentDerivedAttributeResolutionTest.php tests/Feature/Assets/Ui/ComponentHistoryTest.php tests/Feature/Components/Ui/ComponentWorkflowPagesTest.php tests/Feature/Components/Ui/ShowComponentTest.php tests/Feature/Components/Ui/ComponentBrowserWorkflowTest.php tests/Feature/Assets/AssetSpecificationOverrideTest.php`
- result: `22` tests passed, `132` assertions

- separated stock-state changes from storage-location assignment in the component workflows:
- `To Storage` / `Move To Storage` flows now move components into stock by default without requiring a location picker up front
- the POST handlers still accept optional legacy `storage_location_id` / `verification_location_id` inputs for compatibility, but the web screens no longer expose those fields
- component detail pages now include a dedicated storage-location editor for loose components so operators can shelve parts later after the stock move
- made removed expected-baseline components stay visible on the source asset roster:
- source assets now render materialized expected-baseline parts that have left the asset as greyed `Removed` rows with only an `Open` action, instead of only shrinking the expected baseline list
- asset component tabs no longer show the old `Expected baseline reduced` alert banner now that the removed rows themselves carry that context
- made component detail management more obvious:
- component detail now shows a clearer human status label, surfaces the storage-location editor for loose parts, and moves the file upload box ahead of history with helper copy so uploads are easier to find
- focused verification after the stock/location split + removed-row update:
- `php artisan optimize:clear` in the app container
- `php artisan test --env=testing tests/Feature/Components/Ui/ComponentWorkflowPagesTest.php tests/Feature/Components/Ui/ComponentBrowserWorkflowTest.php tests/Feature/Components/Ui/ShowComponentTest.php tests/Feature/Assets/Ui/ComponentHistoryTest.php`
- result: `18` tests passed, `138` assertions

- moved the asset-page `To Storage` confirmation back onto the asset page as a modal instead of navigating away:
- tracked and expected component rows on the asset component tab now launch a shared `Move To Stock` modal with the verification checkbox and note field inline on the page
- the asset-page modal posts to the existing stock-move endpoints, while the generic component/tray storage pages remain available for non-asset flows
- focused verification after the asset-page storage modal change:
- `php artisan optimize:clear` in the app container
- `php artisan test --env=testing tests/Feature/Components/Ui/ComponentWorkflowPagesTest.php tests/Feature/Assets/Ui/ComponentHistoryTest.php`
- result: `9` tests passed, `78` assertions

- added the same inline confirmation pattern for `To Tray` on the component detail page:
- installed components on `components.show` now open a `Move To Tray` modal with the note field inline instead of navigating to the separate remove-to-tray page first
- the modal still posts to the existing remove-to-tray endpoint, so lifecycle/history behavior is unchanged underneath
- focused verification after the component-detail tray modal change:
- `php artisan optimize:clear` in the app container
- `php artisan test --env=testing tests/Feature/Components/Ui/ShowComponentTest.php`
- result: `5` tests passed, `26` assertions

- converted component-detail status handling toward the asset-style pattern:
- `components.show` now exposes a `Change Status` dropdown with status-specific confirmation modals instead of a row of separate lifecycle buttons
- added a dedicated component `Status History` table that traces from/to status changes using the existing component event log
- kept install and move-to-other-device as separate actions, but tightened install gating so `Defective` and `Destruction Pending` components cannot be installed from the detail-page path
- added an explicit `Defective` lifecycle transition for loose components and made `Needs Verification -> In Stock` work without forcing a storage location first
- focused verification after the component status dropdown/history change:
- `php artisan optimize:clear` in the app container
- `php artisan test --env=testing tests/Feature/Components/Ui/ShowComponentTest.php tests/Feature/Components/Ui/ComponentBrowserWorkflowTest.php`
- result: `11` tests passed, `80` assertions

- polished the component-detail status dropdown label so the closed button now shows the current component status instead of the generic `Change Status`
- focused verification after the current-status label update:
- `php artisan optimize:clear` in the app container
- `php artisan test --env=testing tests/Feature/Components/Ui/ShowComponentTest.php`
- result: `5` tests passed, `34` assertions

- aligned the component-detail status control more closely with the asset-side status UI:
- replaced the bootstrap dropdown menu on `components.show` with a real form-select status control that shows the current status as the selected/default entry
- kept the modal-backed status transitions underneath so note/confirmation flows still work, but the visible control now behaves more like other status selectors in the app
- focused verification after the select-style status alignment:
- `php artisan optimize:clear` in the app container
- `php artisan test --env=testing tests/Feature/Components/Ui/ShowComponentTest.php tests/Feature/Components/Ui/ComponentBrowserWorkflowTest.php`
- result: `11` tests passed, `80` assertions

- refined the removed-row styling on the asset Components tab so only the descriptive cells are muted; the `Open` action now stays visually normal instead of being faded with the rest of the row
- focused verification after the removed-row button styling fix:
- `php artisan optimize:clear` in the app container
- `php artisan test --env=testing tests/Feature/Assets/Ui/ComponentHistoryTest.php`
- result: `5` tests passed, `23` assertions

- linked tracked component names and tags on the asset Components tab to the component detail page when the viewer can open that component; this now also applies to greyed `Removed` rows, not just active tracked rows
- focused verification after the asset-row name/tag linking change:
- `php artisan optimize:clear` in the app container
- `php artisan test --env=testing tests/Feature/Assets/Ui/ComponentHistoryTest.php`
- result: `6` tests passed, `28` assertions

- linked expected component names on the asset Components tab to the component-definition editor when the row is definition-backed and the viewer can manage that definition; assumed rows without a catalog definition still render as plain text
- focused verification after the expected-row definition-link change:
- `php artisan optimize:clear` in the app container
- `php artisan test --env=testing tests/Feature/Assets/Ui/ComponentHistoryTest.php`
- result: `7` tests passed, `30` assertions

- made `From asset:` and `To asset:` entries clickable in the component detail history when the viewer can open those devices; work-order/task history links continue to behave the same way
- focused verification after the component-history asset-link change:
- `php artisan optimize:clear` in the app container
- `php artisan test --env=testing tests/Feature/Components/Ui/ShowComponentTest.php`
- result: `6` tests passed, `37` assertions

### 2026-04-28
- Session reinitialized for the new week.
- Reviewed `AGENTS.md`, the latest `PROGRESS.md` entries, `docs/fork-notes.md`, and the 2026-04-23 session addendum to recover the current component/spec workflow state.
- Current branch baseline at reinit: `master` on commit `12586f04c` (`Document Component Workflow Changes`), with the recent feature stack already pushed to `origin/master`.
- Remaining local-only dirt at session start:
- `docker-compose.yml`
- `docker/nginx.conf`
- `docs/agents/agents-addendum-2026-03-19-session-init.md`
- `storage/tmp-testtypes-reorder.js`
- Created a fresh session addendum at `docs/agents/agents-addendum-2026-04-28-session-init.md` for new-week continuation notes.

### 2026-04-30
- Session resumed to prepare a local production-data clone for component migration/testing.
- Planned setup path: import the production dump into `snipeit_prod_raw`, clone it into `snipeit_prod_work`, point local `.env` at `snipeit_prod_work`, and run current local migrations forward on the work copy.
- Dev `APP_KEY` is kept for now unless encrypted production fields prove relevant during testing; auth remains local/dev-oriented.
- Created a fresh session addendum at `docs/agents/agents-addendum-2026-04-30-session-init.md`.
- Brought the Docker stack back up locally before the clone work (`snipeit_db`, `snipeit_node`, `snipeit_app`, `snipeit_web`).
- Backed up the current local `.env` to `.env.before-prodclone.2026-04-30`.
- Backed up the current `public/uploads` tree to `prodbak/local-uploads-before-prodclone-2026-04-30`.
- Unpacked the provided production dump bundle from `prodbak/snipe-it-prod-export-20260428/dev-clone-20260428-105640/database.sql.gz`.
- Created and populated two local clone databases:
- `snipeit_prod_raw` = untouched imported production snapshot
- `snipeit_prod_work` = working copy cloned from `snipeit_prod_raw`
- Repointed local `.env` from `DB_DATABASE=snipeit` to `DB_DATABASE=snipeit_prod_work`.
- Kept the current dev `APP_KEY` in place for now; the bundled `prod-app-key.env` was not applied during this pass.
- Mirrored the provided production uploads bundle into `public/uploads`.
- Cleared Laravel caches and migrated the working clone forward successfully:
- `2026_04_16_110000_add_display_order_to_test_types_table`
- `2026_04_17_120000_create_component_traceability_tables`
- `2026_04_20_223648_remove_company_id_from_component_definitions_table`
- `2026_04_21_140000_create_component_definition_attributes_table`
- `2026_04_21_180000_add_expected_baseline_asset_component_state`
- Verification after setup:
- app container sees `DB_DATABASE=snipeit_prod_work`
- `snipeit_prod_work` currently contains imported data (`assets=7`, `users=18`)
- Created a separate local-only prod-key env file at `.env.prodclone.prodkey`:
- keeps `DB_DATABASE=snipeit_prod_work`
- swaps in the production `APP_KEY` from the bundle
- leaves the active `.env` untouched for now so we can opt into the prod-key clone only when needed
- Swapped the active `.env` over to the prod-key clone variant and cleared Laravel caches so local testing now uses the imported production bundle key against `snipeit_prod_work`.
- Documented the next-stage hierarchical component/subcomponent architecture in `docs/plans/component-hierarchy-subcomponents-plan.md`.
- The new plan explicitly replaces the flat-only component assumption for repairable integrated parts and captures the locked rules for:
- `asset -> component -> subcomponent` depth cap
- expected subcomponents assumed until first explicit change
- parent moves carrying currently attached descendants only
- instance and child-level attribute precedence
- no spec reduction for damaged-but-attached items
- closed ancestry snapshots instead of live inherited parent history
- Swapped the active `.env` over to `.env.prodclone.prodkey`.
- Cleared Laravel caches after the swap with `php artisan optimize:clear`.
- Active clone state after the swap:
- `DB_DATABASE=snipeit_prod_work`
- active `.env` now uses the production app key from the bundle

### 2026-05-26
- Continued the workflow/profile implementation after the initial database/model/run-flow slice.
- Added a dedicated Workflow Profiles settings page under `admin/workflow-profiles`:
- admins can create/edit/delete profiles, scope profiles to asset categories, mark active/default/sale-blocking flags, and configure which workflow items belong to each profile
- profile item configuration stores per-profile order, requiredness, and pass/fail versus done/not-done label mode
- the main settings card and admin navigation now lead to Workflow Profiles, with a cross-link back to Workflow Items
- Sale-readiness checks now honor active applicable profiles with `blocks_sale_readiness=1`: every blocking profile needs a latest clean run before `tests_completed_ok` becomes true or Ready for Sale/Sold can proceed without warning.
- Existing Ready for Sale warning locations continue to use the same acknowledgment flow, with missing blocking profile runs called out by profile name.
- Verification against `.env.testing` SQLite after Docker `php artisan optimize:clear`:
- workflow/settings/status focused set: `11` tests, `53` assertions
- broader workflow/profile regression set: `42` tests, `197` assertions
- `ManageWorkflowProfilesTest`: `4` tests, `16` assertions
- Page-oriented smoke tests still have two stale `ShowAssetTest` assertions unrelated to HTTP 500s: one QR-label copy expectation and one old run-row expectation on an asset with no run.
- No live dev/prod-clone DB migration was run; local dev `snipeit_prod_work` still needs backup and migration before browser testing the current workflow code against that database.

### 2026-05-28
- Session reinitialized after a one-day pause.
- Reviewed `AGENTS.md`, recent `PROGRESS.md`, `docs/fork-notes.md`, the 2026-05-26 addendum, git state, local DB migration status, and remaining present/summary-style seed references.
- Current branch at reinit: `codex/component-hierarchy-sprints` on commit `e72fc14b3`.
- Working tree remains dirty with the workflow/profile implementation plus pre-existing local environment/upload artifacts.
- Local dev DB preflight still reports `APP_ENV=local`, `DB_CONNECTION=mysql`, `DB_DATABASE=snipeit_prod_work`; workflow migration `2026_05_26_120000_rename_tests_to_workflows_and_add_profiles` is still pending.
- Confirmed remaining implementation blocks before continuing:
- present/summary-style catalog attribute cleanup
- structured ports foundation
- component-vs-attribute decisions for webcam/battery/ports
- workflow seed refinement
- stale page test cleanup
- backup/migrate/browser-smoke of the local production clone
- Investigated component hierarchy correction support:
- asset-level arbitrary component creation is exposed through `hardware.components.register`
- child component creation is exposed through `components.children.store`
- parent moves already cascade attached child components
- existing lifecycle flows clear parent linkage when installing/removing/moving to stock
- no UI/service endpoint currently re-parents an already-installed asset-level component under another installed parent component
- Refined structured ports direction:
- ports should be seeded as component definitions/instances usable at asset, component, and subcomponent levels
- USB-C capability should be modeled with separate attributes such as USB standard, connector type, DisplayPort alt-mode, Power Delivery, Thunderbolt support/version, and optional PD wattage/DP version
- Wi-Fi should be either a component when a physical/replaceable module is tracked, or a capability/spec attribute when integrated; functional Wi-Fi verification remains a workflow item
- Captured follow-up decisions:
- seed common component/attribute definitions and preserve the current model/model-number catalog as seedable data
- current work-copy data has 11 asset models, 11 model numbers, 411 model-number attributes, 18 users, and 7 assets
- current component catalog data is not a useful seed source yet: 3 component definitions, 0 model-number component templates, 1 subcomponent template
- component reparenting correction should stay within the same asset
- expected-template matching can be deferred; first-pass reparenting can attach a manual child without trying to satisfy an expected slot
- product attributes should not be created just so workflow items/tests can attach to them
- future production cleanup should migrate users, but not old workflow/test runs/photos; assets will be manually recreated against the new catalog foundation
- Investigated current live component definitions in `snipeit_prod_work`:
- `Werckermann`: RAM category, Microsoft manufacturer, 16GB DDR4 attributes, no instances/templates
- `Test main component 1`: RAM category, model `modelnumber123`, part `12345`, DDR4 + 4GB attributes, one installed instance on asset `INBIT-QI0001`, one freeform expected child template named `Test subcomponent 1`
- `samsungh powerram 3000`: RAM category, 8GB LPDDR4X attributes, no instances/templates
- Conclusion: current component-definition rows are ad hoc/test data, not enough to use as the main seed source. Seed work should create a clean common component catalog and optionally preserve any rows only if the user explicitly wants them.
- User confirmed the current component-definition rows are examples/tests and should not be migrated into the clean seed/catalog.
- Created the clean-start catalog mapping investigation at `docs/plans/catalog-clean-start-mapping-2026-05-28.md`.
- The mapping preserves the 11 current real models/model numbers, excludes current example component definitions, maps present/test booleans and summary dropdowns out of the product attribute catalog, and proposes generic component definitions/templates for RAM, storage, displays, batteries, cameras, ports, audio, input, and network capability.
- Important implementation caveat captured: current component attribute aggregation only rolls up numeric `resolves_to_spec` values into the effective attribute list. Non-numeric component details such as RAM type, storage type, display resolution, USB standard, and port connector type need either manual model-number attributes or a resolver/display enhancement.
- Browser smoke check reached the local app at `https://dev.inbit` and confirmed the login page loads. Protected asset/internal pages require an authenticated session; no browser verification of component/workflow pages was completed because no test credentials/session were available and no database mutation was made.
- Resolved follow-up catalog decisions in the mapping doc: Surface Type Cover is a sale accessory/workflow item, Pixel 8 Pro manufacturer should seed as Google, phone cameras should be generic multi-camera components with position/role/megapixel attributes, and HP ProBook 430 G3 battery capacity is deferred until actual scan/health handling.
- Created next-session handoff at `docs/agents/session-handoff-2026-05-28.md`.

### 2026-06-02
- Session reinitialized after the 2026-05-28 handoff.
- Reviewed `AGENTS.md`, recent `PROGRESS.md`, `docs/fork-notes.md`, `docs/agents/session-handoff-2026-05-28.md`, and `docs/plans/catalog-clean-start-mapping-2026-05-28.md`.
- Current branch at reinit: `codex/component-hierarchy-sprints` on commit `e72fc14b3`.
- Working tree remains dirty with the workflow/profile implementation, clean-start mapping docs, local environment artifacts, Docker/upload placeholder changes, and untracked workflow files.
- Docker Desktop was not reachable during reinit, so local migration status and app/browser checks could not be refreshed. Last known DB state from the handoff: `DB_DATABASE=snipeit_prod_work` with workflow migration `2026_05_26_120000_rename_tests_to_workflows_and_add_profiles` pending.
- Immediate continuation target remains the seed/data foundation: clean attributes, component catalog, model-number expected component templates, and workflow seed cleanup.
- User resolved `warranty_months`: it belongs to sale/policy handling and should not be seeded as a device attribute.
- User wants repeated port expectations grouped by quantity, for example `USB-A Port - USB 3.1 Gen1 x3`, instead of showing several identical expected rows when the repetition is just quantity.
- Live work DB rehearsal completed against `snipeit_prod_work` after a fresh backup:
- SQL dump: `prodbak/db-snapshots/snipeit_prod_work_pre_workflow_catalog_20260602_102947.sql`
- clone schema: `snipeit_prod_work_pre_workflow_catalog_20260602_102947`
- DB preflight before migration: `APP_ENV=local`, `DB_CONNECTION=mysql`, `DB_DATABASE=snipeit_prod_work`
- Ran pending workflow migration `2026_05_26_120000_rename_tests_to_workflows_and_add_profiles`.
- Reran focused seeders: `DeviceAttributeSeeder`, `DevicePresetSeeder`, `DeviceComponentCatalogSeeder`, and `AttributeTestSeeder`.
- Adjusted `AttributeTestSeeder` so seeded workflow profiles sync/remediate stale profile item assignments from copied databases; Standard Diagnostics now contains only diagnostic checks, while Pre-Sale, Cleaning, and Shipping remain separate profiles.
- Browser smoke while logged in at `https://dev.inbit`:
- asset `INBIT-QI0001` loads without SQL/page errors after disabling local debugbar via `.env` `APP_DEBUG=false`
- asset component tab shows the expected component roster with grouped quantities and component-derived specs including `RAM 8GB DDR4`, `Storage 256GB NVMe`, `USB-A Port`, `Assumed x2`, and `Calculated from components`
- workflow history page lists `Standard Diagnostics`, `Pre-Sale Check`, `Cleaning`, and `Shipping Laptop` as selectable profiles
- started `Cleaning #23`, verified the active workflow created 2 ordered done/not-done results, marked the first task done, and saved a note through the browser
- ready/attention warning still appears on the asset page when the latest blocking workflow state needs attention
- Fixed missing localization keys found during browser smoke: `tests.failure_count`, `general.progress`, and `general.total`.
- Added CSRF middleware disabling to the workflow feature tests, matching other web-form tests in the repo.
- Focused validation passed after `php artisan optimize:clear` and testing DB preflight (`testing|sqlite|/var/www/html/database/database.sqlite`):
- `ManageWorkflowProfilesTest`
- `StartNewTestRunTest`
- `AttributeTestRunGenerationTest`
- `ComponentDerivedAttributeResolutionTest`
- combined result: 22 tests, 97 assertions.
- Current remaining caveat: copied legacy workflow item rows such as `install-update-windows` and `wipen` still exist in `workflow_items` and are visible as available settings items, but are no longer assigned to the seeded Standard profile. Decide whether future seeders should prune unseeded legacy workflow items from copied databases; deleting them would affect old migrated workflow result references.

### 2026-06-04
- Split Workflow Profiles item management into per-profile subpages instead of rendering every profile's item matrix on the index page.
- `admin/workflow-profiles` now stays compact with profile metadata, counts, flags, and an `Items` action for each profile.
- Each profile item page shows separate `Included Items` and `Available Items` tables; included items now support drag-and-drop ordering backed by the profile-item reorder endpoint, while the form still saves enabled/required/result-label settings together.
- Focused validation passed after `php artisan optimize:clear` and testing DB preflight (`APP_ENV=testing`, `DB_CONNECTION=sqlite`, `DB_DATABASE=/var/www/html/database/database.sqlite`):
- `tests/Feature/Settings/ManageWorkflowProfilesTest.php`
- `tests/Feature/Settings/ManageTestTypesTest.php`
- result: 11 tests, 49 assertions.
- Additional sanity checks passed: `php artisan view:cache` and `git diff --check` (line-ending normalization warnings only).
- Browser smoke while logged in at `https://dev.inbit`: `admin/workflow-profiles` renders compact profile rows with item subpage links, and `admin/workflow-profiles/1/items` renders Included/Available sections with drag handles and no console warnings.
- Split workflow configuration responsibilities further:
- `Workflow Items` now has its own settings card and settings side-menu entry instead of being discoverable only through Workflow Profiles.
- workflow items now store a default `result_label_mode` so reusable items decide whether they use `Pass / Fail` or `Done / Not Done`; run creation and agent ingestion now prefer the workflow item defaults for requiredness/button mode, including one-off extra items.
- workflow profile item pages now behave as composition pages: included rows show read-only item defaults plus explicit `Remove`, available rows use explicit `Add`, item order remains draggable, and the old inline `Use`/`Required`/result-button controls are no longer front-and-center.
- Added and applied migration `2026_06_04_120000_add_result_label_mode_to_workflow_items`; the dev `snipeit_prod_work` migration backfilled item button modes from existing profile item assignments.
- Focused validation passed after testing DB preflight and migrations:
- `tests/Feature/Settings/ManageWorkflowProfilesTest.php`
- `tests/Feature/Settings/ManageTestTypesTest.php`
- `tests/Feature/Assets/StartNewTestRunTest.php`
- result: 19 tests, 79 assertions.
- Browser smoke while logged in at `https://dev.inbit`: settings index shows separate Workflow Profiles and Workflow Items cards, `admin/testtypes` renders the Result Buttons field/options, and `admin/workflow-profiles/1/items` renders Add/Remove controls with no `Use` header or profile-level result-button selects.
- Fixed existing enum attribute option editing: edit pages now render the pending-option row hooks used by the `Add to list` button, preserve unsaved new options after validation errors, and clarify that adding a new option only makes it available for future selections while renaming/removing existing options can affect current rows.
- Browser smoke on `https://dev.inbit/attributes/63/edit` (`port_connector_type`) confirmed the attribute is in use by 24 component definitions, the contextual options warning renders near the options section, and adding an unsaved `eSATA` row now creates `options[new][0]` inputs without saving.
- Focused validation passed after testing DB preflight (`APP_ENV=testing`, `DB_CONNECTION=sqlite`, `DB_DATABASE=/var/www/html/database/database.sqlite`): `tests/Feature/AttributeDefinitionLifecycleTest.php` result: 15 tests, 56 assertions.
- Implemented speed-specific RJ45 port cataloging. `port_connector_type` remains the physical connector and now includes seeded `esata`/`eSATA`; new `ethernet_speed_max` enum options seed `1GbE`, `2.5GbE`, `5GbE`, and `10GbE`. Component definitions now include `RJ-45 Ethernet Port - 1GbE`, `2.5GbE`, `5GbE`, and `10GbE`, with the HP ProBook RJ45 motherboard templates moved to the 1GbE definition.
- Created `prodbak/db-snapshots/snipeit_prod_work_pre_rj45_speeds_20260604_155034.sql`, then reran `DeviceAttributeSeeder` and `DeviceComponentCatalogSeeder` against `snipeit_prod_work`.
- Post-reseed DB verification confirmed active `rj45` and `esata` connector options, active Ethernet speed options, the old generic `RJ-45 Ethernet Port` soft-deleted, `RJ-45 Ethernet Port - 1GbE` carrying `port_connector_type=rj45` and `ethernet_speed_max=1gbe`, and the HP ProBook 430 G6 motherboard owning the 1GbE RJ45 child template.
- Focused validation passed: PHP syntax checks, `DeviceComponentCatalogSeederTest`, `ComponentDerivedAttributeResolutionTest`, and `TestTypeForAssetTest` (`21` tests, `82` assertions); `php artisan view:cache` passed and caches were cleared afterward. Browser smoke on `https://dev.inbit/admin/settings/component-definitions?search=RJ-45` showed all four speed-specific RJ45 definitions without server errors.
- TODO for next session: add quick-entry generic fallback component definitions for partially known hardware, especially `Wireless Module`, `USB-A Port`, `USB-C Port`, and possibly an explicit `RJ-45 Ethernet Port - Unknown Speed` rather than reusing the retired generic RJ45 name. These should set only known physical/capability data, leave version attributes blank/unknown, and allow later refinement by swapping the expected component definition or adding component attributes.
- Created handoff for continuing on a new device: `docs/agents/session-handoff-2026-06-04.md`.

### 2026-06-06
- Added catalog-only generic fallback component definitions for partially known hardware, including generic USB-A/USB-C, memory, SSD/HDD, battery, camera, keyboard, wireless, and Bluetooth entries. These are intentionally not seeded into model-number expected templates so one-off or end-of-life assets can stay lightweight until more detail is known.
- Local Docker cleanup was completed against the isolated local `snipeit` database after the required DB preflight and backup snapshot, and the app is being served through the localhost override at `http://127.0.0.1:18080`.
- Asset detail specification rows now keep component-derived metadata compact for routine rows: the repeated calculated-source label and default breakdown are collapsed into a small tooltip icon next to the value, and routine tooltips show only `Parts: ...` instead of provenance or subtotal copy. Expanded inline detail remains visible for exceptions such as extras/custom components, reduced default baselines, hierarchy overlap warnings, and overrides.
- Browser verification on `http://127.0.0.1:18080/hardware/1` confirmed 29 spec rows, 24 compact indicators, `Parts: 3.5mm Port - Headset Combo` as the first runtime tooltip, zero visible calculated-source text, and zero visible `Expected/default` text.
- Added configurable component-label display for component-derived specs: attribute definitions choose between raw value labels and component labels, component definitions can store an editable spec display label, and component-definition attributes can opt into generated labels based on their display order.
- Seeded `port_connector_type` to use component-label display and updated port catalog data so USB/HDMI/audio capability details appear in the asset overview through editable component labels instead of hardcoded view logic. Audio jack standard no longer resolves to asset specs, audio role is label-only, and camera position/role details no longer resolve as top-level specs.
- Applied the additive local Docker migration `2026_06_06_120000_add_component_spec_display_settings`, then reran `DeviceAttributeSeeder` and `DeviceComponentCatalogSeeder` against the local `snipeit` database so browser testing reflects the new production seed behavior.
- Validation passed after repairing the Docker vendor dev dependencies with `composer install --no-interaction --prefer-dist`: testing preflight confirmed `APP_ENV=testing`, `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`; targeted PHPUnit passed for attribute lifecycle, component-definition settings, component-derived resolution, device component catalog seeding, asset detail UI, and component history UI (`71` tests, `384` assertions). `git diff --check` passed with line-ending warnings only.

### 2026-06-07
- Session restarted after a Windows reboot. Docker Desktop initially had running UI/backend processes but the Docker engine API calls hung; stopped stale `docker.exe`/`docker-compose.exe` clients, restarted Docker Desktop, and waited for the engine to answer.
- Restarted the local stack with `docker compose -f docker-compose.yml -f docker-compose.localhost.yml up -d`; `snipeit_db` recovered and became healthy, then `snipeit_app` and `snipeit_web` started on `127.0.0.1:18080`.
- Verified the local app setup: `http://127.0.0.1:18080` returns HTTP 200 with title `Snipe-IT Demo`, Laravel preflight reports `APP_ENV=local`, `DB_CONNECTION=mysql`, `DB_DATABASE=snipeit`, the component-label display columns exist, and `php artisan migrate:status --pending` reports no pending migrations.
- MariaDB logs show crash recovery after the reboot and leftover ignored `#sql-alter` temp tablespace notices, but the current schema check and migration status passed. No destructive database command was run.
- Asset specification display now appends units for numeric attributes through the central resolved-attribute formatter, so hardware detail values render as `256 GB`, `15.6"`, `60 Hz`, `8 GB`, and `1.74 kg` while raw stored values remain numeric for calculations and tests.
- Focused validation passed after Docker testing preflight (`APP_ENV=testing`, `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`): `ShowAssetTest`, `ComponentHistoryTest`, and `ComponentDerivedAttributeResolutionTest` passed (`39` tests, `193` assertions). Browser verification on `http://127.0.0.1:18080/hardware/1` confirmed the seeded asset spec rows show the expected units.
- Tightened production seed behavior: default `DatabaseSeeder` now seeds the catalog/workflow foundation and permission groups without calling destructive/demo-oriented seeders for users, locations, departments, suppliers, status labels, depreciation, or role mutation. `SettingsSeeder` now creates default settings only when missing and uses `Snipe-IT` instead of `Snipe-IT Demo`.
- Hardware attribute labels no longer repeat units already shown in values. Seeded labels now read like `Werkgeheugen`, `Opslagcapaciteit`, `Schermgrootte`, `Verversingssnelheid`, and `Gewicht`, while their `unit` columns drive value display such as `8 GB`, `256 GB`, `15.6"`, `60 Hz`, and `1.74 kg`.
- Catalog seed helpers now avoid factory side effects and restore matching soft-deleted catalog categories, manufacturers, and asset models instead of creating duplicate foundation rows. Local Docker DB was reseeded with `DeviceAttributeSeeder` only, after preflight (`APP_ENV=local`, `DB_CONNECTION=mysql`, `DB_DATABASE=snipeit`), so `hardware/1` reflects the cleaner labels.
- Validation passed after Docker testing preflight (`APP_ENV=testing`, `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`): focused catalog/unit/component/UI/workflow batch passed (`91` tests, `488` assertions). Browser verification on `http://127.0.0.1:18080/hardware/1` confirmed labels now show units on values rather than in labels. `git diff --check` passed with line-ending warnings only.

### 2026-06-08
- Split the default seed path into an explicit `ProductionFoundationSeeder`. `DatabaseSeeder` now delegates to that production entry point instead of carrying inline orchestration.
- Added production-safe seeders for permission groups, status labels, and suppliers. They avoid truncation and factories, update/restore known foundation rows, and do not create demo users, demo companies, or fake suppliers.
- `ProductionSupplierSeeder` is wired but intentionally empty until real production supplier names are provided. The existing demo `SupplierSeeder` still contains sample suppliers and is not called by the production foundation path.
- Added tests proving the production foundation creates settings, status labels, permission groups, attributes, model presets, component definitions, and workflow catalog data without demo users/companies/suppliers, and that rerunning the production foundation does not duplicate those foundation rows.
- Validation passed after Docker testing preflight (`APP_ENV=testing`, `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`): focused catalog/unit/component/UI/workflow batch passed (`92` tests, `498` assertions). PHP syntax checks passed for the changed seeders and catalog seeder test. `git diff --check` passed with line-ending warnings only.
- Completed the local Docker end-to-end rehearsal after preflight and snapshot `prodbak/db-snapshots/snipeit_pre_e2e_rehearsal_20260608_222456.sql`; the final evidence note is `docs/agents/e2e-rehearsal-2026-06-08.md`.
- Rehearsal setup was performed through the UI after the production foundation reset: created `Lenovo`, `ThinkPad T480 Rehearsal`, model number `20L6-SAMPLE`, model specs, expected generic components, asset `E2E-OLD-LAPTOP-001`, and operational/supervisor users.
- QR simulation passed for the asset tag as both bootstrap superuser and `rhea-refurb`; component QR simulation was not applicable because expected components did not create tracked component instances.
- Confirmed a production permission blocker: `rhea-refurb` can scan/view the asset but receives `403 Forbidden` when starting a workflow or editing the asset because seeded operational groups do not grant `assets.edit`.
- Bootstrap superuser completed both sale-readiness-blocking workflows (`Standard Diagnostics` and `Pre-Sale Check`), which set `tests_completed_ok=1`; Ready for Sale and Sold transitions then saved successfully.
- Lifecycle caveats found: Ready for Sale does not automatically set `is_sellable=1`; Sold archives via `assets.archived=1` rather than soft delete; action-log update notes are generic/null.
- Additional UX/data issues found: asset create spec panel did not recognize the selected model number, `Being Refurbished` was unavailable on asset create, optional `Opslagtype` failed to persist with an enum validation hint, and the spec page briefly showed a stale `No expected components added yet` message after expected components saved.

### 2026-06-09
- Implemented manual model/spec conflict warnings for component-derived specification values. Component-resolved values still take precedence, but `ResolvedAttribute` now detects when a saved manual model value differs and exposes a warning message with formatted labels, including enum labels such as `NVMe-SSD` versus `SATA-SSD`.
- Added conflict warning display to the model specification page, asset create/edit specification override partial, and asset detail specification rows. The model spec page uses a top alert so conflicts remain visible even when the component-backed attribute is hidden from manual editing.
- Focused validation passed after `php artisan optimize:clear` and testing-environment preflight (`artisan env --env=testing` reported `testing`; `phpunit.xml` sets `APP_ENV=testing`, `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`): `ComponentDerivedAttributeResolutionTest`, `ModelSpecificationComponentPreviewTest`, and `ShowAssetTest` passed (`38` tests, `191` assertions).
- PHP syntax checks passed for the touched resolver/test files. `php artisan view:cache` passed and compiled views were cleared afterward for normal local development.
- Renamed seeded component definitions from `Webcam Module` to `Webcam` and `Wireless Module` to `Wireless`, including expected component templates and workflow item applicability. `DeviceComponentCatalogSeeder` now renames existing legacy rows before normal seeding so the local production-clone DB updates instead of creating duplicates.
- Reran `ProductionFoundationSeeder` against local Docker after preflight (`APP_ENV=local`, `DB_CONNECTION=mysql`, `DB_DATABASE=snipeit_prod_work`) to apply the names in `dev.inbit`. Verification showed only `Webcam|Wireless` among those names, expected templates count `14|0` for new versus old labels, and workflow applicability now maps `webcam -> Webcam`, `wifi -> Wireless, Wireless - Generic`, and `bluetooth -> Bluetooth - Generic, Wireless, Wireless - Generic`.
- Focused validation passed after `php artisan optimize:clear` and testing preflight: `DeviceComponentCatalogSeederTest`, `ManageWorkflowProfilesTest`, `TestTypeForAssetTest`, `ComponentBrowserWorkflowTest`, and `ComponentWorkflowPagesTest` passed (`31` tests, `269` assertions).
- Next-session TODOs from production testing:
- QR printing is currently not available for a moved component; investigate component action availability after component transfer/reparent/move workflows and restore a print/download QR path for the moved component.
- The asset detail Components tab is not usable enough on mobile; redesign the tab rows/actions for small screens so component status, hierarchy, and actions remain scannable without horizontal crowding.
- Asset test history entries currently all show the generic label `Testronde`; change history display to show each entry's actual workflow/profile name.
- Reinitialized the next manual-testing workspace on `master` at `cc510d859` after fetching `origin`; no tracked diff exists between local `HEAD` and `origin/master`.
- Local Docker is running through `docker-compose.localhost.yml`; Laravel cache was cleared, `/login` returned HTTP 200, and `php artisan migrate:status --pending` reports no pending migrations.
- Current local runtime differs from the previous `dev.inbit` production-clone setup: `.env` reports `APP_URL=http://192.168.178.79:18080` and `DB_DATABASE=snipeit`.
- LAN access for physical-device testing was enabled by publishing nginx as `0.0.0.0:18080->80` and setting the compose override `APP_URL` to `http://192.168.178.79:18080`; both `http://127.0.0.1:18080/login` and `http://192.168.178.79:18080/login` return HTTP 200 from the host.
- Camera testing should still account for browser secure-context rules: a physical phone using the LAN IP may need HTTPS or a trusted local hostname before `getUserMedia` works reliably, even after the port is reachable.

### 2026-06-10
- Reinitialized on `master` at `cc510d859` after reviewing `AGENTS.md`, current `PROGRESS.md`, `docs/fork-notes.md`, the 2026-06-09 addendum, and the 2026-06-08 E2E rehearsal notes.
- Current tracked local edits at session start are the 2026-06-09/2026-06-10 documentation updates and `docker-compose.localhost.yml` LAN binding; untracked local-only material remains `prodbak/` and `storage/debug-workorder.php`.
- Docker was stopped at reinit. Restarted with `docker compose -f docker-compose.yml -f docker-compose.localhost.yml up -d --no-build`, cleared Laravel caches, and confirmed `APP_ENV=local` with no pending migrations.
- Nginx initially returned `502` after startup until the `web` container was restarted; afterward both `http://127.0.0.1:18080/login` and `http://192.168.178.79:18080/login` returned HTTP 200.
- Active local test runtime remains the LAN-enabled `snipeit` database setup, not the earlier `dev.inbit`/`snipeit_prod_work` clone. Physical camera testing may still require HTTPS/trusted-host setup if the phone browser rejects camera access on plain LAN HTTP.

### 2026-06-11
- Session reinitialized on `master` after production-test follow-up notes were committed and pushed. Current `master` and `origin/master` both point to `cc510d859` (`Document Production Test Followups`).
- Reviewed `AGENTS.md`, recent `PROGRESS.md`, `docs/fork-notes.md`, latest addenda for 2026-06-07 through 2026-06-09, `docs/agents/session-handoff-2026-06-04.md`, and `docs/agents/e2e-rehearsal-2026-06-08.md`.
- Working tree still has local-only dirty state from before this session: Docker config edits, upload placeholder `.gitignore` line-ending changes, `.env.before-prodclone.2026-04-30`, `.env.prodclone.prodkey`, `docker/nginx.local.conf`, and `prodbak/`. Do not commit or revert these unless explicitly requested.
- Active production-test follow-ups to start with: moved-component QR printing, mobile usability of the asset Components tab, and replacing generic `Testronde` labels in asset test history with the actual workflow/profile names.
- This chat is also initialized for device hardware-detail research. For device lookups, capture exact model identifiers, manufacturer/spec source links, confidence/ambiguity notes, and any fields that map cleanly to the fork's model specifications or component catalog.
- Implemented tracked-component QR label support through the same server-print workflow used by assets. `QrLabelService` now exposes target-aware render methods for assets and component instances, while `QrLabelPrintService` centralizes queue resolution and CUPS `lp` dispatch for future print targets.
- Added authenticated component label routes for PNG download and printer dispatch, and added the shared QR label widget to component detail pages so moved/reparented/stock/tray components can still download or print their own stable `CMP:<qr_uid>` label.
- Clarified the shared QR widget by visibly labeling the label template selector and reserving `Printer location` for the actual queue picker; the asset page keeps its existing QR panel behavior through the same widget.
- Validation passed after Docker `php artisan optimize:clear` and testing DB preflight (`APP_ENV=testing`, `DB_CONNECTION=sqlite`, `DB_DATABASE=/var/www/html/database/database.sqlite`): PHP syntax checks for the changed PHP files, `QrLabelServiceTest`, `ComponentLabelTest`, `ShowAssetTest`, and `ShowComponentTest` passed (`36` tests, `228` assertions).
- Clarified component condition behavior without changing the existing verification process language. User-facing and accepted condition choices are now `Unknown`, `Good`, `Poor`, and `Broken`; `Fair` is not offered as a condition.
- Missing or `Unknown` component condition derives `Needs Attention`; `Poor` and `Broken` derive the damaged condition status but display as their concrete condition labels instead of a generic `Damaged` badge. `Good` displays without an issue badge.
- Asset-side component flows now expose the condition field instead of silently creating new tracked/manual child components as `Unknown`. The asset Components tab also exposes an inline condition selector for tracked components and writes a `condition_updated` component event when changed.
- Fixed model specification value-field overflow by constraining the spec builder form controls and their flex parents (`min-width: 0`, `max-width: 100%`, `box-sizing: border-box`) on manual attribute and expected-component rows, then added a scoped `.form-group` margin reset after the screenshot showed Bootstrap horizontal-form gutters were still pushing active value controls outside the panel.
- Validation passed after Docker `php artisan optimize:clear` and testing DB preflight (`APP_ENV=testing`, `DB_CONNECTION=sqlite`, `DB_DATABASE=/var/www/html/database/database.sqlite`): condition/asset/component UI and domain tests passed (`44` tests, `304` assertions), selling-state/component-warning/API adjacent tests passed (`13` tests, `85` assertions), `ModelSpecificationComponentPreviewTest` and `ModelSpecificationUiTest` passed (`9` tests, `52` assertions), and `php artisan view:cache` passed with compiled views cleared. Browser smoke on `https://dev.inbit/models/1/spec` at the screenshot-sized viewport confirmed the active value control stayed within its parent panel; browser smoke on `https://dev.inbit/hardware/1#components` confirmed the Components tab loads and current DOM does not expose a `Fair` condition option, but the local production-clone DB currently has no attached tracked component rows to visually exercise the inline condition selector without creating test data.
- Added structured hardware-research catalog fields to the seed path: camera aperture/autofocus/OIS, Wi-Fi max standard using IEEE identifiers, 2.4/5/6 GHz band flags, Bluetooth version, NFC, and max cellular generation. Added reusable `Wireless - 802.11n/ac/ax/be` definitions and expanded phone camera definitions including 64MP main, 8MP ultrawide, 5MP macro/depth, 20MP selfie, and 10.5MP selfie.
- Camera megapixel specs now use component-label display so multi-camera phones can show grouped camera labels instead of only a summed numeric megapixel value. Wi-Fi/Bluetooth workflow applicability now follows all `Wireless*` definitions, and rear-camera applicability includes macro/depth cameras.
- Validation passed in Docker after `php artisan optimize:clear` and testing DB preflight (`testing|sqlite|/var/www/html/database/database.sqlite`): PHP syntax checks passed for the changed seeders/test, `DeviceComponentCatalogSeederTest` passed (`7` tests, `97` assertions), and `TestTypeForAssetTest` plus `ManageWorkflowProfilesTest` passed (`11` tests, `42` assertions). `git diff --check` passed with line-ending warnings only.
- Added the Samsung Galaxy A51 `SM-A515F/DSN-4GB-128GB` production catalog preset using the researched 128GB variant details: 4GB LPDDR4X, 128GB UFS, 6.5-inch 1080 x 2400 AMOLED display, 4000 mAh battery, `802.11ac` wireless, Bluetooth 5.0, NFC, 4G LTE, and 32/48/12/5/5MP camera components. Kept reusable camera definitions role/megapixel-only so aperture differences such as A51 depth f/2.2 versus other 5MP depth modules do not contaminate shared component definitions.
- Validation after the A51 update: Docker PHP syntax checks passed for `ProvidesDeviceCatalogData`, `DeviceComponentCatalogSeeder`, and `DeviceComponentCatalogSeederTest`. After `php artisan optimize:clear` and testing DB preflight (`APP_ENV=testing`, `DB_CONNECTION=sqlite`, `DB_DATABASE=/var/www/html/database/database.sqlite`), `DeviceComponentCatalogSeederTest` passed (`8` tests, `107` assertions).
- Removed the active workflow page's lower pill/floating summary bar, progress strip, active-run start-new-workflow form, and `Mark as Tested OK` confirmation modal so the page focuses on test blocks only. The no-active-run start form remains for the empty state, and result saves still drive backend completion state.
- Validation passed in Docker after `php artisan optimize:clear` and testing DB preflight from `phpunit.xml` (`DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`): `tests/Feature/Tests/ActiveTestViewTest.php` passed (`8` tests, `35` assertions). `git diff --check` passed for the touched files with line-ending warnings only.
- Reworked the asset detail Components tab for mobile/tablet by adding card-based current component and history layouts alongside the existing desktop tables. Mobile cards keep model-baseline components quiet, reserve badges for tracked/extra/custom/removed/condition states, show `To Tray` as the default action, and move secondary actions into a Bootstrap `More` menu. Condition controls now use table/mobile-specific IDs to avoid duplicate DOM IDs.
- Validation passed in Docker after `php artisan optimize:clear` and testing DB preflight from `phpunit.xml` (`APP_ENV=testing`, `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`): `ComponentHistoryTest` and `ComponentWorkflowPagesTest` passed (`17` tests, `172` assertions). `php artisan view:cache` passed and compiled views were cleared. Browser smoke at a 390px viewport on `https://dev.inbit/hardware/1#components` confirmed the mobile card list and history cards render, desktop tables are hidden, no expected mobile badges render, no horizontal overflow remains, the `More` menu opens, and `To Storage` still populates the existing modal.

### 2026-06-13
- Reinitialized on `master` after reviewing `AGENTS.md`, recent `PROGRESS.md`, `docs/fork-notes.md`, and the latest addenda for 2026-06-10 and 2026-06-11.
- Pulled `origin/master` from `cc510d859` to `fe5d71faf` (`Implement Component Followups`), bringing in component QR labels, mobile Components cards, condition cleanup, Galaxy A51/wireless-camera catalog seed updates, and active-workflow page cleanup.
- Preserved local June 9-10 LAN/runtime notes by temporarily stashing the local session files, fast-forwarding master, then resolving the `PROGRESS.md` append conflict so June 9, June 10, and June 11 notes remain in chronological order.
- Local Docker was restarted with `docker-compose.localhost.yml`; Laravel caches were cleared, `php artisan migrate --force` reported nothing to migrate, and the app reports `APP_ENV=local`.
- Nginx again returned a startup `502` until the `web` container was restarted; afterward both `http://127.0.0.1:18080/login` and `http://192.168.178.79:18080/login` returned HTTP 200.
- Added a non-destructive `ProductionDemoUserSeeder` for production-style local testing accounts. It updates/restores named demo users, resets their password to `password`, and attaches them to the current production permission groups instead of using the old destructive `UserSeeder`.
- Ran `php artisan db:seed --class=ProductionDemoUserSeeder --force` against the local Docker `snipeit` database. Verified the accounts exist and are assigned to `Admin`, `Supervisor`, `Senior Refurbisher`, and `Refurbisher` groups.

### 2026-06-14
- Added opt-in `DevelopmentDeviceScenarioSeeder` for local component-hierarchy development. It is intentionally not wired into `DatabaseSeeder` or `ProductionFoundationSeeder`; it calls the production foundation first, then creates only `DEV-COMP-*` assets and dev-marked component instances.
- The seeded scenarios cover a baseline-only HP ProBook, a complex HP ProBook with expected-tracked board/child/removed/extra/custom parts, a Pixel phone camera scenario, a Surface tablet/integrated-board edge case, and loose stock/tray/verification/damaged parts.
- Added `DevelopmentDeviceScenarioSeederTest` to prove the seeder is rerunnable, creates the expected asset spread, produces expected/tracked/extra/custom/removed roster classifications, creates QR-capable component instances, and does not duplicate `DEV-COMP-*` assets.
- Validation passed after Docker `php artisan optimize:clear` and testing DB preflight (`APP_ENV=testing`, `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`): `php artisan test tests/Feature/DevelopmentDeviceScenarioSeederTest.php --env=testing` passed with `1` test and `22` assertions. Local PHP syntax checks passed for the new seeder and test, and `git diff --check` reported only existing CRLF warnings.
- Ran `php artisan db:seed --class=DevelopmentDeviceScenarioSeeder --force` against the local Docker `snipeit` database. Verification showed `4` `DEV-COMP-*` assets, `12` dev component instances, and `23` dev component events; `http://127.0.0.1:18080/login` returns HTTP 200.

### 2026-06-15
- Fixed the mobile asset Info tab layout so asset details/specification rows stack inside the viewport instead of pushing value cells off to the right. The change is scoped to `hardware/view.blade.php` page CSS and leaves the shared `.row-new-striped` helper unchanged for other pages.
- Browser verification on `http://127.0.0.1:18080/hardware/7` at a 390px viewport confirmed the `Poortconnector` row renders as stacked full-width blocks and document width stays inside the viewport. Desktop verification confirmed the normal two-column asset detail layout still renders at wide width.
- Investigated per-port workflow testing. Current workflow generation intentionally creates one `workflow_result` per applicable workflow item, and the seeded `USB-poorten` item matches USB-A/USB-C component definitions as one grouped check. No code change was made; keeping one grouped USB test remains the current preference to avoid unnecessary test detail for users.
- Validation passed in Docker: PHP syntax checks passed for `ProductionDemoUserSeeder`, `DevelopmentDeviceScenarioSeeder`, and `DevelopmentDeviceScenarioSeederTest`; `php artisan view:cache` passed with compiled views cleared afterward; testing preflight confirmed `APP_ENV=testing`, `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`; `php artisan test tests/Feature/DevelopmentDeviceScenarioSeederTest.php --env=testing` passed with `1` test and `22` assertions.
- Reinitialized the local workspace after a few days away and fast-forwarded `master` from `fe5d71faf` to `eaaf32726` (`Add Development Test Seeders And Mobile Asset Fix`).
- Pulled changes include the mobile asset Info-tab layout fix, `ProductionDemoUserSeeder`, opt-in `DevelopmentDeviceScenarioSeeder`, related focused test coverage, and updated session/fork notes.
- Local-only dirty runtime artifacts remain intentionally untouched: Docker/nginx config edits, upload placeholder `.gitignore` line-ending noise, `.env.before-prodclone.2026-04-30`, `.env.prodclone.prodkey`, `docker/nginx.local.conf`, and `prodbak/`.
- No Docker, database, browser, or PHPUnit verification was run during this reinitialization step.

### 2026-06-16
- Reinitialized without pulling or fetching `master`; local `master` remains at `eaaf32726` (`Add Development Test Seeders And Mobile Asset Fix`) and matches the last known `origin/master` ref.
- Reviewed `PROGRESS.md`, `docs/fork-notes.md`, and recent addenda for 2026-06-11, 2026-06-13, 2026-06-14, and 2026-06-15.
- Current tracked local edits are documentation/session notes plus existing local Docker/nginx and upload placeholder line-ending changes. Untracked local runtime artifacts remain `.env.before-prodclone.2026-04-30`, `.env.prodclone.prodkey`, `docker/nginx.local.conf`, and `prodbak/`.
- No Docker, database, browser, PHPUnit, fetch, or pull action was run during this quick setup check.
- Created planning branch `codex/inactivity-timeout-serial-ocr-plan` and added `docs/plans/inactivity-timeout-and-serial-ocr-2026-06-16.md` for the proposed 30-minute inactivity logout/client warning and modular browser-camera serial OCR flow.
- Initial timeout planning scan found no obvious global long-running frontend polling except short-lived confetti animation; existing AJAX/fetch usage appears user-triggered or debounced. Any real polling/keepalive found during implementation should be reported before changing it.
- Deferred the proposed QR login-card workflow to a later feature. The current recommendation is a printed QR card plus short PIN, not QR-only authentication, and it is out of scope for the current inactivity timeout/serial OCR implementation slice.
- Added a deferred asset Tests / Workflows tab UX follow-up: the visible `Start new workflow` button should start the currently selected workflow profile instead of only moving/focusing the user toward the start-new-workflow area.
- Added a deferred broad Dutch localization pass, with guidance to translate operational UI text while leaving common/natural IT terms and technical identifiers in English where appropriate.
- Added a later quick-search tags/synonyms plan so searches like `wifi` can find `Wireless`, and broader connector/connection terms can surface USB, HDMI, RJ-45, audio, and related port catalog items.
- Added a research block for concurrent scanning/server load: investigate realistic 20-user scan/workflow/search usage, request volume, query cost, and safe caching/debounce/indexing opportunities before optimizing.
- Added a research block for photo normalization/optimization: inspect image upload paths, storage/page-weight impact, derivative sizing, metadata handling, and safe backfill constraints before changing upload behavior.
- Added a printer/server-migration research block for the upcoming Docker-on-Proxmox move: printer options should become selectable live queues from an as-yet-unknown source, reused by asset/component QR label printing.
- Added scoped Samsung phone catalog seeding through `SamsungGalaxyPhoneCatalogSeeder`, wired into `ProductionFoundationSeeder` while still runnable directly for production delta updates.
- The Samsung seeder creates/updates black Galaxy A32 `SM-A325F/DS-6GB-128GB`, Galaxy A50 `SM-A505FN/DS-4GB-128GB`, and Galaxy A51 `SM-A515F/DSN-4GB-128GB` model-number variants with expected logic-board, display, battery, camera, speaker, microphone, wireless, USB-C, and 3.5mm components.
- Added `emmc` / `eMMC-opslag` to the storage type enum so the A32 128GB storage component can be represented without forcing it into UFS/SATA/NVMe/HDD.
- The Samsung seeder marks its expected-component templates with seed metadata and only prunes templates owned by the same seeder, preserving manual expected components added in production. Direct runs also refresh workflow item component applicability when workflow tables exist so new camera definitions are linked to existing workflow items.
- Validation passed inside Docker after `php artisan optimize:clear` and testing DB preflight (`testing|sqlite|/var/www/html/database/database.sqlite`): PHP syntax checks passed for the new/changed seeders and test, `SamsungGalaxyPhoneCatalogSeederTest` passed (`3` tests, `64` assertions), and `DeviceComponentCatalogSeederTest` passed (`8` tests, `107` assertions). `git diff --check` reported only existing CRLF warnings.
- Changed component tag generation to the explicit `INBIT-C-AB1234` namespace and added migration `2026_06_16_150000_namespace_component_tags` to backfill existing dev-phase component tags while avoiding asset-tag collisions.
- Kept component QR payloads as stable `CMP:{qr_uid}` values, but component labels now print `Component tag`, visible component tags resolve to component detail pages through the scan resolver, and the QR label cache version is bumped to `v14`.
- Added a locked serial capture/edit control to removal-to-tray flows. The serial field starts disabled, can be unlocked with `Add serial`/`Change serial`, and changing a non-empty existing serial requires explicit confirmation. Removal events record serial changes in `payload_json`; expected baseline components moved to tray can capture serial at materialization time.
- Updated the asset Components tab desktop rows and mobile cards so `To Tray` opens a confirmation modal with the serial control. Older component-detail child `To Tray` inline posts now route to the dedicated confirmation page so serial capture cannot be bypassed.
- Applied the component-tag namespace migration to the local Docker dev DB after confirming `APP_ENV=local`, `DB_CONNECTION=mysql`, `DB_DATABASE=snipeit_prod_work`.
- Validation passed inside Docker after `php artisan optimize:clear` and testing DB preflight (`testing|sqlite|/var/www/html/database/database.sqlite`): PHP syntax checks passed for changed PHP files and migration; targeted tests passed (`46` tests, `345` assertions); `php artisan view:cache` passed and compiled views were cleared. Browser smoke on `https://dev.inbit/hardware/1#components` confirmed the tray modal serial field starts locked, unlocks correctly, migrated `INBIT-C-...` tags render, and no console errors appeared. `git diff --check` reported only existing CRLF warnings.
- Follow-up: removal-to-tray redirects now land on the moved component detail page, including expected baseline components materialized during the move. Focused validation passed: PHP syntax checks for the two changed controllers and `ComponentBrowserWorkflowTest` (`12` tests, `98` assertions).
- Follow-up planning investigation completed in `docs/plans/follow-up-investigation-2026-06-16.md`. It covers inactivity logout, serial OCR, deferred QR login cards, asset Tests / Workflows start behavior, Dutch localization, quick-search aliases, concurrent scanning/server load, photo normalization, and printer/server migration. The direct implementation candidates are the asset Tests mobile start button, inactivity timeout, and the first Dutch localization pass; OCR, printer discovery, and image optimization should be staged behind shared services. This was read-only investigation only; no migrations, seeders, Docker writes, browser tests, or PHPUnit runs were executed for the investigation block.

### 2026-06-18
- Reinitialized on `codex/inactivity-timeout-serial-ocr-plan` without pulling or fetching. Current `HEAD` is `48e03d9b0` (`Add Samsung Phone Seeder And Dev Host Config`) and matches `origin/codex/inactivity-timeout-serial-ocr-plan`.
- Reviewed `PROGRESS.md`, `docs/fork-notes.md`, the 2026-06-16 session addendum, and the 2026-06-16 inactivity/OCR and follow-up investigation plans. Created `docs/agents/agents-addendum-2026-06-18-session-init.md`.
- Docker runtime is still up: `snipeit_web`, `snipeit_app`, and healthy `snipeit_db`; web publishes ports `80` and `443`, consistent with the `dev.inbit` SSL profile. No Laravel commands, migrations, seeders, browser tests, or PHPUnit runs were executed during reinitialization.
- Working tree remains intentionally dirty with prior in-progress component QR/tag/serial-removal changes, planning docs, session docs, local runtime artifacts, `prodbak/`, and upload placeholder `.gitignore` line-ending noise. Do not commit or revert unrelated local artifacts unless explicitly requested.
- Current direct implementation candidates remain the asset Tests mobile start button, inactivity timeout, and first Dutch localization pass. OCR, printer discovery, photo optimization, and concurrent-load work remain staged/research items from the investigation plan.
- Broad implementation completed for the ready follow-up slice: the asset Tests / Workflows mobile floating action now submits the currently selected workflow profile, session config defaults to a 30-minute idle lifetime with an authenticated explicit keepalive endpoint and client warning modal, and scan/component/QR/serial/workflow operational strings were moved through locale keys with a first Dutch pass.
- Scanner text is now passed through `window.scanConfig.text` so the QR scanner can share translated labels and remains easier to modularize for the later serial OCR work. `npm run dev` was run through the Docker node service to rebuild assets.
- Validation passed after Docker `php artisan optimize:clear` and testing DB preflight (`APP_ENV=testing`, `DB_CONNECTION=sqlite`, `DB_DATABASE=/var/www/html/database/database.sqlite`): PHP syntax checks passed for changed PHP/controllers/language/test files; duplicate top-level locale key scan passed; focused feature tests passed (`52` tests, `408` assertions); `php artisan view:cache` passed and compiled views were cleared.
- Fixed the Workflow Items create/edit modal Select2 controls so attribute/category/component pickers initialize and reopen at full modal width instead of collapsing when hidden modal markup is initialized. Multi-select removals remain handled by the Select2 item `x` controls.
- Validation passed after Docker `php artisan optimize:clear` and testing DB preflight (`APP_ENV=testing`, `DB_CONNECTION=sqlite`, `DB_DATABASE=/var/www/html/database/database.sqlite`): PHP syntax check passed for `ManageTestTypesTest`, `ManageTestTypesTest` passed (`8` tests, `30` assertions), and `php artisan view:cache` passed with compiled views cleared.
- Committed the component QR/tag/serial-removal work, inactivity timeout/UI follow-ups, and planning/session documentation locally on `codex/inactivity-timeout-serial-ocr-plan`.
- Browser/production-like testing is intentionally deferred to the remote environment before merging: verify component QR print/scan, moved-component detail redirects, removal serial capture, inactivity timeout/keepalive behavior, asset Tests selected-workflow start, Workflow Items Select2 controls, and Dutch UI wording.

### 2026-06-23
- Reinitialized on `codex/inactivity-timeout-serial-ocr-plan` and fetched remote refs without pulling or changing branches. Current `HEAD` is `7cb67cacc` (`Document Remote Testing Handoff`) and matches `origin/codex/inactivity-timeout-serial-ocr-plan`.
- Reviewed `PROGRESS.md`, `docs/fork-notes.md`, `TODO.md`, the 2026-06-18 session addendum, and the 2026-06-16 follow-up/OCR planning docs for current status.
- The branch is still awaiting remote/browser validation before merging. Priority checks remain component QR print/scan, moved-component detail redirects, remove-to-tray serial capture, inactivity timeout/keepalive behavior, selected-workflow start on the asset Tests tab, Workflow Items Select2 controls, and Dutch UI wording.
- Local-only dirty state remains intentionally untouched: upload placeholder `.gitignore` line-ending changes, `.env.before-prodclone.2026-04-30`, `.env.prodclone.prodkey`, and `prodbak/`.
- Added a dedicated component-detail serial edit flow so serials can be captured or corrected after a component is already in tray/stock, not only during removal. The details panel now always shows the serial row, offers `Add serial`/`Change serial` for users with component update permission, keeps the serial field locked until explicitly enabled, and records changes as `serial_updated` component events with previous/new serial payload data and an optional note.
- Validation passed in Docker after `php artisan optimize:clear` and testing DB preflight (`APP_ENV=testing`, `DB_CONNECTION=sqlite`, `DB_DATABASE=/var/www/html/database/database.sqlite`): PHP syntax checks passed for changed service/controller/route/test files; `ShowComponentTest` passed (`16` tests, `145` assertions); `ComponentBrowserWorkflowTest` and `ComponentLifecycleServiceTest` passed (`16` tests, `123` assertions); `php artisan view:cache` passed with compiled views cleared.
- Started planning laminated one-page/A4 operator manuals for the refurb workflow. Reviewed the Affinity-generated `refurbisher steps.pdf` example and current fork notes; no app code, Docker, database, browser, or PHPUnit actions were run for this planning-only block.
- Reworked asset workflow history/status consistency. Test-run rows now display the stored workflow profile snapshot instead of generic `Testronde`, shared issue-list rendering names missing sale-readiness workflow profiles, and asset-list workflow badges now use `missing`/`attention`/`ok` status instead of single-latest-run counts.
- The `latest-test-summary` API now uses the same per-blocking-workflow readiness summary as asset detail/status checks, so a newer unrelated workflow run cannot hide a missing or failed required workflow. Tooltip payloads include missing workflow profile names and workflow-prefixed failed/incomplete result labels.
- Validation passed in Docker after cache clear and testing DB preflight (`APP_ENV=testing`, `DB_CONNECTION=sqlite`, `DB_DATABASE=/var/www/html/database/database.sqlite`): PHP syntax checks passed for changed PHP/test files; `ShowAssetTest` passed (`17` tests, `93` assertions); `LatestTestSummaryTest` and `AssetIndexTest` passed (`14` tests, `87` assertions); `ReadyForSaleWarningTest` passed (`2` tests, `14` assertions); `php artisan view:cache` passed with compiled views cleared.
- Recorded asset-page tab cleanup decisions in `docs/plans/asset-page-tab-cleanup-2026-06-23.md` and `TODO.md`: Licenses, Images, Files, and Extra files need later feature planning/rework; Devices, Maintenance, and the Send/Upload paperclip nav action are marked for deprecation/removal planning.
- Created `docs/manuals/operator-guide-planning.md` to track guide decisions, guide-code families, draft guide inventory, open questions, and follow-up investigation needed before producing final laminated manuals.
- Clarified that `docs/manuals/operator-guide-planning.md` is brainstorming/planning material, not foundational truth; future agents should only treat entries marked `Final` as locked decisions.

### 2026-06-25
- Reinitialized on `codex/inactivity-timeout-serial-ocr-plan` and fetched remote refs without pulling or switching branches.
- Current `HEAD` is `c4a5f8128` (`Clarify Operator Guide Planning Status`) and matches `origin/codex/inactivity-timeout-serial-ocr-plan`.
- `origin/master` has advanced to `51208bff3` (`Merge pull request #66 from WervInbit/codex/inactivity-timeout-serial-ocr-plan`), so this branch's committed work is already included upstream. Local `master` has not been fast-forwarded yet.
- Reviewed `PROGRESS.md`, `docs/fork-notes.md`, `TODO.md`, the 2026-06-18 and 2026-06-23 session addenda, `docs/plans/follow-up-investigation-2026-06-16.md`, `docs/plans/asset-page-tab-cleanup-2026-06-23.md`, and `docs/manuals/operator-guide-planning.md`.
- Local-only dirty state remains intentionally untouched: upload placeholder `.gitignore` line-ending changes, `.env.before-prodclone.2026-04-30`, `.env.prodclone.prodkey`, and `prodbak/`.
- No Docker, database, browser, Laravel, PHPUnit, migration, seeder, or asset-build commands were run during this reinitialization.

### 2026-06-25
- Reinitialized on `codex/inactivity-timeout-serial-ocr-plan` for continued laminated operator-guide planning. Current `HEAD` is `c4a5f8128` (`Clarify Operator Guide Planning Status`) and matches `origin/codex/inactivity-timeout-serial-ocr-plan`.
- Reviewed `AGENTS.md`, recent `PROGRESS.md`, `docs/fork-notes.md`, and `docs/manuals/operator-guide-planning.md`; the manual planning document remains brainstorming-only unless entries are explicitly marked `Final`.
- Startup doc drift scan found the manual planning doc under `docs/manuals/` and related planning references; no README/CONTRIBUTING updates were made during reinitialization.
- Local-only dirty state remains intentionally untouched: upload placeholder `.gitignore` line-ending changes, `.env.before-prodclone.2026-04-30`, `.env.prodclone.prodkey`, and `prodbak/`.
- No fetch, pull, branch switch, Docker, database, browser, PHPUnit, or asset-build commands were run during this reinitialization step.
- Continued guide brainstorming in `docs/manuals/operator-guide-planning.md`: kept the draft color/icon/reference style, set first-pass floor guides to Dutch, added QR links to latest digital guides/index as a draft direction, deferred work-order guides from the first pass, recorded operator-facing terms (`Asset`, `Workflow`, `Component`, `Model`, `Tray`, `Storage`), and added a later research-model/best-practice review phase.
- Added an out-of-thread product follow-up to the guide planning doc: workflow readiness/asset-list visibility may need to support multiple relevant workflows beyond a single base/test workflow before final guide language is locked.
- Clarified Affinity production expectations in the guide planning doc: automation can help with master-page recommendations, layout specs, screenshot checklists, importable SVG/PDF/PNG assets, and review PDFs, but native Affinity `.afpub`/`.afdesign` output should not be assumed unless a reliable workflow is proven.
- Moved the research-model/best-practice review earlier in the guide planning flow: before real guide drafting/layout, prepare a package with the planning doc, example PDF/images, print constraints, target viewing context, and questions about A4 print readability, screenshot sizing, QR sizing, contrast, color-blind/black-and-white fallback, and lamination glare.
- Updated the research-review package inputs to include the operator planning guide, the initial rough Affinity design sketch, and a photo of the actual printed page for realistic print/readability analysis.
- Created `docs/agents/session-handoff-2026-06-25-affinity-guide-setup.md` for a fresh Computer Use session. The current thread could see the Computer Use plugin after enablement but did not receive callable desktop screenshot/click/type tools, so Affinity GUI setup should continue in a refreshed session.
- Continued the Affinity proof in a Computer Use-enabled session. Current local checkout was verified on `master` at `51208bff3` (`Merge pull request #66 from WervInbit/codex/inactivity-timeout-serial-ocr-plan`).
- Created a two-page `AST-02` proof/template from the operator guide plan and Affinity research report: `C:\Users\Gebruiker\Documents\snipe-it manuals\AST-02 Affinity proof template.pdf` and imported/saved it through Affinity as `C:\Users\Gebruiker\Documents\snipe-it manuals\AST-02 Affinity proof template.af`.
- The Affinity proof is layout/template validation only, not final guide copy. It uses the research-recommended double-sided A4 structure, 10 mm live margins, five front-side step bands, back-side workitem detail flow, image rail placeholders, finished-when boxes, and QR placeholders.
- No app code, Docker, database, Laravel, PHPUnit, migrations, seeders, browser tests, or asset-build commands were run during the Affinity proof creation.
- Created `docs/manuals/affinity-development-blocks-2026-06-25.md` to split the operator-guide/Affinity work into reusable page blocks, page-specific build chunks, a Computer Use build queue, screenshot source mapping, and a missing-screenshot backlog.
- Captured additional read-only Playwright screenshots in `C:\Users\Gebruiker\Documents\snipe-it manuals\screenshot-source\2026-06-25-blocks`, including the filled login form, asset-level Tests / Workflows tab, and asset-level Components tab. The earlier admin Workflow Profiles and global Components captures were marked as unsuitable for the asset-tab guide blocks.
- No Snipe-IT app code, Docker, database, Laravel, PHPUnit, migrations, seeders, or asset-build commands were run for the development-block spec.

### 2026-06-30
- Reinitialized on `master` at `51208bff3` (`Merge pull request #66 from WervInbit/codex/inactivity-timeout-serial-ocr-plan`) for continued operator-guide creation/planning feedback.
- Reviewed `AGENTS.md`, recent `PROGRESS.md`, `docs/fork-notes.md`, `docs/manuals/operator-guide-planning.md`, `docs/manuals/affinity-development-blocks-2026-06-25.md`, and the 2026-06-25 guide addendum.
- Current guide-planning status remains draft/brainstorming unless entries are explicitly marked `Final`; the smaller Affinity block spec remains the working implementation guide for page-by-page guide creation.
- Existing local guide artifacts remain under `C:\Users\Gebruiker\Documents\snipe-it manuals`, including the AST-02 Affinity proof, screenshot source folder, and the generated `AC-01`, `AST-01`, `WF-01`, and combined draft PDFs under `draft-guides\2026-06-25-login-asset-test`.
- Local dirty state remains intentionally untouched: guide planning/session docs, upload placeholder `.gitignore` line-ending changes, `.env.before-prodclone.2026-04-30`, `.env.prodclone.prodkey`, and `prodbak/`.
- No Docker, database, browser, Laravel, PHPUnit, migrations, seeders, asset-build commands, fetch, pull, or branch changes were run during this reinitialization.
- Sorted the first draft guide feedback into `docs/manuals/operator-guide-feedback-replan-2026-06-30.md`. The new direction keeps guide codes, related-guide chips, footer/source/version, larger bottom-right QR, and old right-side help information, but changes the next drafts to step-first layouts with smaller screenshot crops per action and inline stop warnings at the relevant step.
- Updated the guide planning and Affinity block docs to reference the June 30 replan. `AC-01 Login` should become a compact block instead of a full-page default; `AST-01 Asset openen` should put the mismatch warning at the title/model/device check step; serial-number search is recorded as desired but blocked until supported by the product.
- Clarified that compacting `AC-01 Login` is a preference, not a hard requirement; use more space if print readability suffers. Screenshot crops should be smaller than the current `AC-01`/`AST-01` proofs but not as small as the smallest screenshot in the initial example photo.
- Follow-up broad reinitialization fetched remote refs while staying on `master`; local `HEAD` remains `51208bff3` and matches `origin/master`. Reviewed `AGENTS.md`, `TODO.md`, `docs/fork-notes.md`, recent progress/addenda, and the current operator-guide planning/replan docs before the planned ChatGPT-website research alignment.
- Prepared the next guide-layout iteration by rereading the operator guide plan, June 30 feedback replan, Affinity block spec, Affinity research report, and guide session addenda. Updated the guide docs so screenshot use is no longer defined by a fixed quantity or screenshot-per-step rule; each visual should instead have a job such as primary path, alternative path, physical context, verification, or stop support.
- Confirmed Computer Use is reachable in this session and can target the running Affinity app. The existing `AST-02 Affinity proof template` window was inspected successfully without changing the document. Next Affinity proof should be a small visual-fit test before rebuilding a full guide.
- Created the `PASS-00A Visual Fit Proof` artifacts under `C:\Users\Gebruiker\Documents\snipe-it manuals\visual-fit-tests\2026-06-30`. The final test files are `operator-guide-visual-fit-proof-v3.pdf`, `operator-guide-visual-fit-proof-v3-rendered-1.png`, and the imported/saved Affinity file `operator-guide-visual-fit-proof-v3.af`.
- The proof follows the liked concept structure while testing revised visual priorities: a larger primary QR-scan visual, smaller manual-search fallback, physical QR-location placeholder, inline mismatch stop, retained help rail, related guides/footer, and larger latest-version QR. The physical QR photo and live camera state remain placeholders, not final guide evidence.
- User rejected the `PASS-00A Visual Fit Proof` as visually unacceptable. Treat `operator-guide-visual-fit-proof-v3.*` as a negative test artifact only, not as a pattern for future guide pages.
- Follow-up correction: stopped the generated-image/PDF proof route and used Computer Use inside the original-plan Affinity working copy instead. Saved the native checkpoint to `C:\Users\Gebruiker\Documents\snipe-it manuals\layout-proofs\2026-06-30-ac01-ast01-native\operator-guides-ac01-ast01-native-affinity-v2-feedback-pass.af`, with `operator-guides-ac01-ast01-native-affinity-v1-before-feedback-pass.af` kept as the pre-edit backup.
- The native Affinity pass now adds a rough combined `AST-01 Asset openen v2` / `AC-01 Login v2 compact` block on page 2 over the original plan artwork, with real placed screenshots for the login form, dashboard, scan page, and hardware index/search fallback. This is a direction marker, not a polished guide; missing evidence remains the physical QR-location photo, a phone/browser start state, and successful live-camera scan state.
- User reviewed the native Affinity output and correctly flagged it as unusable. Created `docs/manuals/operator-guide-clean-design-plan-2026-06-30.md` to make the next step plan-first and clean-document-first: do not continue from the failed native `.af`, prepare real screenshot crops before Affinity placement, build only `PASS-00B Clean Style Strip` first, and export/inspect a proof after each small pass.
- Built a focused clean `AC-01 Login` test from fresh `https://dev.inbit/` mobile-sized screenshots using the `codex` / `codexcodex` dev account. The login flow verified successfully: the capture summary records `Dashboard :: Inbit Snipe-IT` at `https://dev.inbit/`.
- Created and inspected the clean proof artifacts under `C:\Users\Gebruiker\Documents\snipe-it manuals\layout-proofs\2026-06-30-ac01-clean-login`: `AC-01-login-clean-v1-proof.png`, `AC-01-login-clean-v1-proof.pdf`, and source `AC-01-login-clean-v1.svg` / `.html`. The proof uses three distinct phone-state visuals: login page, filled login form, and dashboard/Scan QR visible after login.
- Opened the clean SVG in Affinity through Computer Use and saved it as native file `C:\Users\Gebruiker\Documents\snipe-it manuals\layout-proofs\2026-06-30-ac01-clean-login\AC-01-login-clean-v1.af`. Self-check note: the dashboard screenshot still shows the app's real untranslated `general.scan` footer on the Scan QR card; this is product UI evidence to fix separately, not a guide-layout fabrication.
- Built the matching clean `AST-01 Asset openen` proof from fresh mobile-sized `https://dev.inbit/` screenshots using real asset `INBIT-HG0001` / `HP ProBook 450 G8`. The proof artifacts are under `C:\Users\Gebruiker\Documents\snipe-it manuals\layout-proofs\2026-06-30-ast01-clean-open-asset`, with final proof `AST-01-open-asset-clean-v3-proof.png` / `.pdf` and native Affinity file `AST-01-open-asset-clean-v3.af`.
- AST-01 self-check corrections: shortened the fallback and verification captions after proof inspection to remove column overlap, kept the mismatch warning inline with the verification step, and labelled physical QR-location evidence as `Foto nodig` instead of faking a device photo. The screenshot still shows the real app's `general.scan` footer on the dashboard Scan QR card.
- Updated the clean `AST-01 Asset openen` proof after receiving a real phone camera/QR screenshot. The new `v4` splits the scan entry into separate dashboard `Scan QR` card and top camera-icon visual blocks, uses the submitted camera/QR photo as the scan/QR-location evidence, keeps search fallback and asset verification blocks, and saves proof/native files as `AST-01-open-asset-clean-v4-proof.png`, `AST-01-open-asset-clean-v4-proof.pdf`, and `AST-01-open-asset-clean-v4.af` under `C:\Users\Gebruiker\Documents\snipe-it manuals\layout-proofs\2026-06-30-ast01-clean-open-asset`.

### 2026-07-02
- Reinitialized for continued operator-guide planning/detail refinement. Reviewed recent `PROGRESS.md`, `docs/fork-notes.md`, and the active manual planning docs before updating guide rules; created `docs/agents/agents-addendum-2026-07-02-session-init.md`.
- Tightened the guide planning docs so step-specific stop warnings are a hard attached rule: the stop block must be inside or visibly attached to the step block it references, and must not be moved into a detached help rail, lower help tile strip, or footer area. This applies directly to the `AST-01` verification/mismatch warning.
- Created the clean `AST-01 Asset openen` v5 proof/native Affinity pass under `C:\Users\Gebruiker\Documents\snipe-it manuals\layout-proofs\2026-06-30-ast01-clean-open-asset`: `AST-01-open-asset-clean-v5-proof.png`, `.pdf`, `.svg`, `.html`, and `AST-01-open-asset-clean-v5.af`. This pass keeps the lower area as small help info tiles and moves the mismatch `STOP` warning into the referenced verification block so it cannot be missed while following the steps.
- Completed a deeper design-foundation investigation across the guide planning docs, Affinity research report, rejected visual-fit proof, clean `AC-01` proof, clean `AST-01` v4/v5 proofs, and the user's current manual Affinity edits. Created `docs/manuals/operator-guide-design-foundation-2026-07-02.md` as the current reusable guide grammar for all next proofs, including actual step numbering, `1A`/`1B` screenshot labels for alternatives, `OF` instead of arrows for equivalent choices, mandatory tiny screenshot captions, attached stop warnings, help-tile behavior, visual-purpose planning, and the recommended `AST-01` v6 structure.
- Clarified the foundation as a flexible design system rather than a fixed page template: guides may vary in step count, screenshot count, split-step patterns, and help-tile count, while consistency comes from the shared shell, numbering/caption grammar, visual-purpose rule, attached warnings, and footer/reference patterns.
- Recreated `AST-01 Asset openen` as a v6 test of the new flexible design foundation. The v6 proof uses four actual workflow steps, an explicit `1A`/`1B` `OF` choice for opening the scanner, numbered/captioned screenshots, the submitted physical QR/camera photo, manual search fallback limited to currently supported asset tag/QR-code behavior, and the mismatch `STOP` warning attached inside the verification step.
- Generated v6 artifacts under `C:\Users\Gebruiker\Documents\snipe-it manuals\layout-proofs\2026-06-30-ast01-clean-open-asset`: `AST-01-open-asset-clean-v6.svg`, `.html`, `-proof.png`, `-proof.pdf`, and native Affinity file `AST-01-open-asset-clean-v6.af`. The imported Affinity tab was saved separately, leaving the older edited AST-01 tab open and untouched.
- Added a proof generator so the `AST-01` layout can be regenerated consistently while iterating on the shared guide layout grammar; the current script is `scripts/manuals/generate-ast01-v8-proof.mjs`.
- Iterated the `AST-01` proof to v8 after feedback on prerequisite references, image badges, and warning density. The v8 proof makes `Telefoon met camera` explicit in `Nodig`, renders `Ingelogd (AC-01 Login)` as blue text with an `AC` icon in the `Vooraf` area, uses much more transparent image identifier circles, and changes the verification warning from a separate stop column into inline red step text.
- Generated v8 artifacts under `C:\Users\Gebruiker\Documents\snipe-it manuals\layout-proofs\2026-06-30-ast01-clean-open-asset`: `AST-01-open-asset-clean-v8.svg`, `.html`, `-proof.png`, and `-proof.pdf`. The v8 Affinity-native file was not created in this pass because Computer Use had been stopped earlier by the user.
- Iterated the `AST-01` proof to v9 after feedback on wasted image space and help copy. The v9 proof enlarges the two `Open de scanner` option screenshots, keeps the `AC` icon plus blue `Ingelogd` reference with smaller `(AC-01 Login)` bracket text, removes duplicate step 2 wording, enlarges/reframes the physical QR photo, keeps the digital guide QR at a 22 mm target size in the lower-right corner, and replaces placeholder help text with concrete short draft text.
- Generated v9 artifacts under `C:\Users\Gebruiker\Documents\snipe-it manuals\layout-proofs\2026-06-30-ast01-clean-open-asset`: `AST-01-open-asset-clean-v9.svg`, `.html`, `-proof.png`, and `-proof.pdf`. The current generator is `scripts/manuals/generate-ast01-v9-proof.mjs`.
- Iterated the `AST-01` proof to v10 after feedback that the step 1 scanner-option crops still lacked enough surrounding page context and that screenshot identifiers should behave as corner markers. The v10 proof widens the `Dashboard` and `Bovenbalk` option crops so surrounding UI is visible, enlarges the image identifier circles, uses thicker outlines with very transparent fill, and centers those identifiers on image corners.
- Generated v10 artifacts under `C:\Users\Gebruiker\Documents\snipe-it manuals\layout-proofs\2026-06-30-ast01-clean-open-asset`: `AST-01-open-asset-clean-v10.svg`, `.html`, `-proof.png`, and `-proof.pdf`. The current generator is `scripts/manuals/generate-ast01-v10-proof.mjs`.
- Iterated the `AST-01` proof to v11 after feedback that step 1 still used red screenshot target lines and the image identifiers did not read as corner overlays. The v11 proof removes the red callouts from the scanner-option screenshots, loosens the step 1 crops so context is visible, and uses larger/thicker transparent corner identifier rings centered on the screenshot corners.
- Generated v11 artifacts under `C:\Users\Gebruiker\Documents\snipe-it manuals\layout-proofs\2026-06-30-ast01-clean-open-asset`: `AST-01-open-asset-clean-v11.svg`, `.html`, `-proof.png`, and `-proof.pdf`. The current generator is `scripts/manuals/generate-ast01-v11-proof.mjs`.
- Iterated the `AST-01` proof to v12 after feedback that step 2 wasted space beside the QR photo and that the corner identifier rings were too faint. The v12 proof moves the `QR-locatie` hint into the upper-right of the step 2 card, widens the physical QR photo across most of the card, and increases the screenshot identifier ring size, stroke, and fill opacity.
- Generated v12 artifacts under `C:\Users\Gebruiker\Documents\snipe-it manuals\layout-proofs\2026-06-30-ast01-clean-open-asset`: `AST-01-open-asset-clean-v12.svg`, `.html`, `-proof.png`, and `-proof.pdf`. The current generator is `scripts/manuals/generate-ast01-v12-proof.mjs`.
- Opened the v12 SVG in Affinity through Computer Use and saved it as native file `C:\Users\Gebruiker\Documents\snipe-it manuals\layout-proofs\2026-06-30-ast01-clean-open-asset\AST-01-open-asset-clean-v12.af`.
- Built the first-batch operator-guide draft proofs with reusable generator `scripts/manuals/generate-first-batch-guides.mjs`. Outputs are under `C:\Users\Gebruiker\Documents\snipe-it manuals\layout-proofs\2026-07-02-first-batch`, including individual PDF/PNG/SVG/HTML drafts for `AC-01`, `SC-01`, `AST-01`, `AST-02` front/back, `WF-01`, `WF-02`, `CMP-04`, and `HELP-01`, plus combined proof `first-batch-operator-guides-proof.pdf`, contact sheet `first-batch-contact-sheet.png`, and `first-batch-summary.md`.
- First-batch self-check: real screenshots are used where available; placeholders remain for the completed workflow summary, `WF-02` final saved state, and `CMP-04` confirmed tray/storage result. No Docker, database, Laravel, PHPUnit, migration, seeder, or asset-build commands were run.
- Captured a live `https://dev.inbit/` screenshot refresh set under `C:\Users\Gebruiker\Documents\snipe-it manuals\screenshot-source\2026-07-02-first-batch-refresh` using the `codex` dev account. The refresh includes live dashboard, scan, asset detail, Tests/Workflow, Components, and remove-to-tray modal states; no workflow start/complete or tray confirmation was submitted.
- Regenerated the first-batch drafts from the refresh set and corrected the most visible crop/position problems in `WF-01` and `CMP-04`. Remaining generator-reported capture gaps are states that would require changing dev data: completed workflow summary, final saved workflow result, and post-confirm tray/storage result.
- Created a new Affinity-ready first-batch pass under `C:\Users\Gebruiker\Documents\snipe-it manuals\layout-proofs\2026-07-02-first-batch-affinity-v1` after correcting additional workflow crop behavior in `scripts/manuals/generate-first-batch-guides.mjs`. The batch includes regenerated individual SVG/HTML/PDF/PNG proofs, combined PDF/contact sheet, and native Affinity file `first-batch-operator-guides-affinity-v1.af` saved through Computer Use from a 9-page PDF import.

### 2026-07-07
- Reinitialized on `master` at `51208bff3` (`Merge pull request #66 from WervInbit/codex/inactivity-timeout-serial-ocr-plan`) to continue operator-guide file creation.
- Reviewed `AGENTS.md`, recent `PROGRESS.md`, `docs/fork-notes.md`, `TODO.md`, `docs/manuals/operator-guide-planning.md`, `docs/manuals/operator-guide-design-foundation-2026-07-02.md`, and the latest first-batch Affinity summary.
- Current guide source of truth for layout grammar remains `docs/manuals/operator-guide-design-foundation-2026-07-02.md`; the latest generated/native batch is under `C:\Users\Gebruiker\Documents\snipe-it manuals\layout-proofs\2026-07-02-first-batch-affinity-v1`.
- Known guide capture gaps remain: `AST-02B` completed workflow summary, `WF-02` final saved/completed workflow state, and `CMP-04` post-confirm tray/storage result. Capturing these requires either approved dev-data changes or intentionally labelled placeholders.
- Investigated the current first-batch Affinity/generator output against the planning docs. Conclusion: the batch is useful as a rough inventory/proof artifact, but most pages should not be treated as usable laminated guides because it expanded from available screenshot slices before each guide had a locked visual-purpose plan; several crops are too narrow/contextless, later workflow/component pages still contain missing-state placeholders, and the native Affinity file was created from a 9-page PDF import rather than a clean per-guide Affinity layout.
- Created a focused `AC-01 Login` proof for the guide-by-guide rebuild using the live `https://snipe.inbit/` login page plus the existing phone launcher screenshot. Outputs are under `C:\Users\Gebruiker\Documents\snipe-it manuals\layout-proofs\2026-07-07-ac01-snipe-login`: `AC-01-login-snipe-v1-proof.png`, `.pdf`, `.svg`, `.html`, summary, and live login capture. The proof starts with the phone shortcut circled, includes a browser fallback with `https://snipe.inbit/`, then login and dashboard checks; the output folder was checked for old development URL references.
- Revised `AC-01 Login` to `v2` after feedback: the normal step flow now starts only from the phone shortcut, the browser/no-phone fallback moved into the `Geen telefoon` help tile, the context strip no longer lists browser as a normal requirement, and the main step numbers now overlap the step-card corners like the screenshot identifier circles. Updated outputs are `AC-01-login-snipe-v2-proof.png`, `.pdf`, `.svg`, `.html`, and summary in the same proof folder.
- Tuned the `AC-01` marker hierarchy in the `v2` proof: main step-number circles are larger and heavier, while image-number circles are smaller, lighter, and more transparent so they no longer compete visually.
- Reworked `AC-01` step 1 to remove leftover browser-fallback spacing and duplicate copy. The step now uses a larger portrait phone screenshot showing most of the phone interface, with the Inbit Snipe-IT shortcut circled, and keeps the instruction in the step line.
- Reworked `AC-01` into a compact `v3` proof after feedback that step 1 still consumed too much empty width. The three workflow steps now sit side by side in equal-width cards: phone shortcut, login form, and dashboard check. Browser access remains only in the `Geen telefoon` help tile, and the generated summary/HTML were checked for stale `dev.inbit` references.
- Reworked `AC-01` into a `v4` proof using one shared workflow frame with three internal mini-steps instead of three separate cards. This keeps the short login guide closer to the earlier compact design, reduces the visible empty-card effect, preserves the consistent help/footer area, and keeps the browser URL only in the `Geen telefoon` help tile. Outputs are `AC-01-login-snipe-v4.svg`, `.html`, `-proof.png`, `-proof.pdf`, and summary under `C:\Users\Gebruiker\Documents\snipe-it manuals\layout-proofs\2026-07-07-ac01-snipe-login`.
- Revised `AC-01` to `v5` after feedback that the footer/support area floated too high. The shared workflow frame now sits slightly lower in the middle of the page, while help tiles, `Klaar als`, related guides, digital guide QR, and source text are anchored back near the bottom for consistency with longer guides. Outputs are `AC-01-login-snipe-v5.svg`, `.html`, `-proof.png`, `-proof.pdf`, and summary in the same proof folder.
- Created a focused `SC-01 Scan asset` proof generator, `scripts/manuals/generate-sc01-snipe-proof.mjs`, and iterated the proof to `v6`. The guide uses the live `https://snipe.inbit/` dashboard capture for scanner entry, the earlier mobile camera/QR screenshot for the scan step because the current laptop camera prompt is not useful guide evidence, clean existing search/result/detail screenshots for fallback and verification, and keeps the stop warning attached to the verification step. Outputs are under `C:\Users\Gebruiker\Documents\snipe-it manuals\layout-proofs\2026-07-07-sc01-snipe-scan`: `SC-01-scan-asset-snipe-v6.svg`, `.html`, `-proof.png`, `-proof.pdf`, and summary. The v6 summary/HTML were checked for stale `dev.inbit` references.
- Created `scripts/manuals/generate-initial-guides-v2.mjs` to regenerate the remaining initial guide drafts after the focused `AC-01` and `SC-01` passes. Outputs are under `C:\Users\Gebruiker\Documents\snipe-it manuals\layout-proofs\2026-07-07-initial-guides-v2`, with individual drafts for `AST-02` front/back, `WF-01`, `WF-02`, `CMP-04`, and `HELP-01`, plus `initial-guides-v2-remaining-proof.pdf`, `initial-guides-v2-contact-sheet.png`, and `initial-guides-v2-summary.md`.
- Fixed the obvious `HELP-01` draft layout defects in the v2 batch: the title now fits and the `QR beschadigd` related-guide chip no longer overflows its tile. The v2 batch summary/HTML and generator source were checked for stale `dev.inbit` references.
- Current initial guide set for review is `AC-01 Login` v5, `SC-01 Scan asset` v6, `AST-01 Asset openen` v12, and the remaining v2 batch. Remaining evidence gaps are still `AST-02B` completed workflow summary, `WF-02` final saved/completed workflow state, and `CMP-04` post-confirm tray/storage result.
- No Docker, database, Laravel, PHPUnit, migration, seeder, asset-build, pull, or branch-change commands were run during this reinitialization.

### 2026-08-11 - User-Account Guide Review Batch
- Continued the focused operator-guide work after the desktop app/browser interruption and brought the local `db`, `app`, and `web` services online without running migrations, resets, or seeders.
- Added idempotent evidence-state helper `scripts/manuals/prepare-user-account-guide-evidence.php`, reproducible real-UI capture helper `scripts/manuals/capture-user-account-guide-evidence.mjs`, and focused guide generator `scripts/manuals/generate-user-account-guide-review.mjs`.
- Captured unannotated Dutch desktop evidence for user creation, standard groups, direct permissions, password reset/self-change entry, assignment review, deactivation, deletion, and restoration under `C:\Users\Gebruiker\Documents\snipe-it manuals\screenshot-source\2026-08-11-user-management`.
- Used only the reversible fictional `Mila de Boer` / `Miladb` record for lifecycle evidence. Final state is restored, not deleted, with login disabled. Returned the demo administrator locale from temporary Dutch capture mode to `en-US`.
- Generated five review guides across six A4 pages: USR-01 add user, USR-02 role/rights, USR-03 password reset, AC-02 own password change, and two-sided USR-04 deactivate/delete/restore. The combined review PDF is `output/pdf/operator-guides-user-account-review-v1.pdf`; editable SVG/HTML, individual PDFs, and PNG proofs are under `C:\Users\Gebruiker\Documents\snipe-it manuals\layout-proofs\2026-08-11-user-account-review-batch-v1`.
- Corrected combined-PDF SVG clip ID collisions, aligned the USR-03 reset-link target, preserved the full USR-01 identity/password form crop, and corrected USR-02 to show the verified Superadmin prerequisite.
- Updated the guide specifications, project index, decision record, screenshot catalog, and TODO list from evidence-blocked specifications to generated drafts awaiting review. No user-account guide was marked approved.
- Verification passed: both Node scripts parse, the PHP helper passes `php -l`, individual PDFs have page counts `1/1/1/1/2`, the combined PDF has six A4 pages, all expected titles extract, no `dev.inbit` or test credential appears in generated outputs, and every latest combined-PDF page was rendered and visually inspected.

### 2026-08-27 - CAT-00 v5 Review
- Started a focused CAT-00 v5 pass from the immutable v4 working draft.
- Scope is limited to the owner-reviewed overview, identity, attribute,
  component, and asset-comparison sections; pages 5, 7, and 8 remain
  conceptually unchanged unless required for layout consistency.
- Session notes:
  `docs/agents/agents-addendum-2026-08-27-cat00-v5-session-init.md`.
- Generated `output/pdf/CAT-00-catalogus-begrijpen-v5-draft.pdf` as an
  eight-page A4 working draft while preserving the portable v4 review copy.
- Rebuilt parts 1, 2, 3, 4, and 6 around one integrated catalogue map, the
  current attribute-form labels, a branching component-definition model, and
  an evidence-aligned baseline-versus-asset comparison.
- Validation passes generator component/geometry checks, shared component
  tests, the complete portable guide-package verifier, PDF metadata and text
  checks, and a full eight-page 180-DPI visual inspection.
- Replaced the v5 page 1 lanes with the v6 relationship graph after owner
  feedback. Direct attributes and expected components now connect to the
  model number, definitions connect to their uses, and orange distinguishes
  physical Asset/Placed Component instances from reusable catalogue records.
- Preserved v6 as review history and generated CAT-00 v7 after the complete
  visual/flow audit. The chapter now uses CMP amber consistently for component
  definitions, expected components, and placed components, and AST green for
  asset identity and asset-specific state. Labels and fill/border treatment
  distinguish definitions, baselines, and physical records.
- Corrected the page 1 relationship direction and connector readability,
  clarified page 2 multiplicity, enlarged and reorganized page 3, replaced the
  mutable operating-system example, cleaned page 6 evidence crops, rebuilt the
  page 7 priority flow, and marked planned CAT routes as `In voorbereiding`.
- Generated `output/pdf/CAT-00-catalogus-begrijpen-v7-draft.pdf`. Focused
  component/geometry checks, the complete shared guide-package tests, PDFInfo,
  extracted-text checks, and full eight-page 180-DPI visual inspection pass.
  CAT-00 v7 remains a working draft awaiting exact-version review; v4 remains
  the portable review copy.

### 2026-08-13 - USR-01 Review Corrections
- Started a focused USR-01 review pass for step hierarchy, temporary-password policy, group/permissions guidance, and image-label/title alignment.
- Preserve the other four user-account guide drafts while producing a separately reviewable USR-01 v2 draft.
- Extended `scripts/manuals/generate-user-account-guide-review.mjs` with an optional focused-guide mode so one guide can be regenerated without overwriting the combined v1 review batch.
- Generated `output/pdf/usr-01-gebruiker-toevoegen-v2-draft.pdf` and editable/proof artifacts under `C:\Users\Gebruiker\Documents\snipe-it manuals\layout-proofs\2026-08-13-usr01-review-v2`.
- USR-01 v2 makes the add-user action primary and duplicate search secondary, replaces the generated-password wording with username plus current year, requires personal handoff and immediate AC-02 replacement, identifies `Groepen` at the bottom, and identifies the second top `Machtigingen` tab plus `Global: Super User` for Superadmin.
- Repositioned the visual columns and shortened step headings so 1A through 4A no longer cover title text. Tightened the login-checkbox target and shortened the step 3 warning so focus marks, warnings, and screenshot captions do not collide.
- Updated the USR-01 specification, project index, decision record, and TODO entries. USR-01 v2 remains a generated draft awaiting review; the earlier v1 combined PDF and the other four user-account guides were not regenerated.
- Verification passed: generator syntax, one-page A4 geometry, required extracted text, forbidden development URL/credential/old `Genereer` wording checks, final PDF rasterization, and full-resolution visual inspection.
- Refined USR-01 v2 after detailed review: recentered the 1A add-user target, removed the broad 2A identity/password focus while retaining the login target, rendered the step 2 AC-02 handoff with its blue family icon and full guide name, converted the step 3 warning into ordinary bold minimum-rights guidance, removed the 4A detail focus, and added the exact navigation from Save through the user list.
- Replaced the irrelevant LDAP and incorrect role-permission help tiles with `Minimale rechten` and `Maatwerk nodig`. USR-02 is now explicitly named as the guide for user-specific rights through `Machtigingen`.
- Prepared USR-01 v4 with explicit navigation through the left-side person icon to `Gebruikers`, a vertically centered completion row, and a separate planned USR-05 reference for creating or editing reusable groups. USR-02 remains the guide for per-user group assignment and direct-rights exceptions.
- Generated and visually verified the one-page A4 `usr-01-gebruiker-toevoegen-v4-draft.pdf`. PDF text and geometry checks passed; the repository PDF render has no header, step, completion-row, related-guide, or QR overlap.
- Prepared USR-01 v5 after review: account creation now names Admin and Superadmin, the AC-01 prerequisite includes its blue family icon, and the footer uses two full guide names instead of forcing three abbreviated references.
- Generated and visually verified `usr-01-gebruiker-toevoegen-v5-draft.pdf`; the one-page A4, required-text, stale-version, and URL checks passed, and full-resolution header/footer crops show no clipping or overlap.
- Prepared USR-01 v6 with a guide-specific second reference row. The footer now offers five full guide names while retaining the AC-01 prerequisite only in the top context row.
- Generated and visually verified `usr-01-gebruiker-toevoegen-v6-draft.pdf`. The five complete references fit in two rows without clipping, QR overlap, source-line overlap, stale version text, or development URLs.
- Re-rendered and inspected the final one-page PDF at 180 DPI; required content and absence of removed help/stop wording were verified from the final PDF.
- Exported the refined page under a new immutable v3 path after the v2 filename was found to be ambiguous for review caching. The new deliverable is `output/pdf/usr-01-gebruiker-toevoegen-v3-draft.pdf`; the earlier v2 file remains untouched.
- Implemented `scripts/manuals/lib/guide-system.mjs` as the reusable generated-guide component layer. It now owns guide statuses and registry data, visual tokens, true-center family/step/image badges, symmetric focus normalization, context/completion/reference components, zero-to-five related guides over two rows, definition validation, and rendered geometry checks.
- Added `scripts/manuals/test-guide-system.mjs` and `scripts/manuals/generate-guide-component-proof.mjs`. The regression proof exposed and then verified the correction of a USR-03 chip overflow; the final component proof passes 10 badge and 5 reference checks.
- Migrated focused USR-01 to shared components as v7 and generated `output/pdf/usr-01-gebruiker-toevoegen-v7-draft.pdf`. Its rendered geometry report passes 11 badge checks and all 5 full related-guide references without overflow.
- Added `docs/manuals/operator-guides/components.md` and versioned review records so minor review corrections are classified as global, family, guide, or version-specific and promoted into shared rules when reusable.
- Replaced ambiguous current artifact status wording in the operator-guide documentation: the six internally accepted versions are `Internal review candidate`; no guide becomes `Third-party approved` without an exact-version third-party review record.
- Added the missing USR-01 navigation evidence as v8. Captured the controlled Dutch dashboard with expanded `Personen` and `Toon Alles`, registered it as `USR-DASHBOARD-PEOPLE-NAV-DESKTOP-01`, and rendered it as 1A beside the existing add-user toolbar relabelled 1B. The layout supports unequal visual widths so a narrow sidebar route and wider toolbar can coexist without forcing both into the same crop size.
- User explicitly accepted USR-01 v8. Updated its specification, review record, decision record, registry status, project index, and TODO entry to `Internal review candidate for V1`; acceptance remains exact-version only.
- Prepared focused USR-02 v2 as the next user-account review page. The role/rights policy and real v1 evidence remain unchanged; the page now uses shared components, true-center badges, an AC-01 prerequisite marker, measured focus padding, non-overlapping long headings, and four full related-guide references over two rows.
- Revised focused USR-02 to v3 from detailed review. Removed redundant identity and Superadmin/unclear-role stops, removed the separate-approval model, documented selecting/deselecting multiple groups with Ctrl+click, changed the audience to Admin / Superadmin, and explained the effective Overnemen/Toestaan/Weigeren semantics. Local code verification confirmed only Superadmin can sync groups or change `Global: Super User`, while Admin can manage ordinary direct user permissions.
