# Prioritized Product Backlog

## Legacy Workflow Evidence - 2026-11-10

- [x] Implement audited, item-level pre-Snipe-IT workflow attestations for the
  single-asset web create path, including per-item context invalidation,
  readiness/dependency integration, mixed real runs, revocation, history,
  summary API states, and asset-list filtering.
- [x] Keep label printing as a separate explicit post-create action and verify
  that intake creates no workflow run or result rows.
- [x] Complete the supported full non-LDAP SQLite gate and local Senior
  Refurbisher journey. The refreshed 2026-11-17 guarded gate passed with 2,290
  tests and 11,316 assertions (2 expected external-MariaDB skips and 2
  expected Windows symbolic-link skips); focused/adjacent coverage and the
  prior authenticated create/start/revoke plus Chrome AJAX journeys pass.
- [ ] After local acceptance, prepare the additive migration/permission-seeder
  production release and take a fresh verified backup. No production action is
  authorized by the implementation checkpoint.

## Phone Preparation - 2026-09-29

## Manual Work Checkpoint - 2026-11-17

The manual source/delivery checkpoint is now committed locally. All25
PDF/native pairs /68 pages are prepared; the owner reports the NAS copy.
Use [the current handoff](docs/manuals/operator-guides/HANDOFF.md) and
[exact delivered register](docs/manuals/operator-guides/registry.md).
These tasks supersede earlier per-version manual queues in this file;
older decisions and rejected artifacts remain in the manual history.

- [ ] **START-05 - Align daily operator manuals with the release and record decisions.**
  Native production and packaging are complete:25 PDF/.af pairs,68 A4 pages,
  full embedded images and an offline Handleidingen index. The owner confirms
  copying the set to the NAS. Use the [handoff](docs/manuals/operator-guides/HANDOFF.md)
  and [exact delivered register](docs/manuals/operator-guides/registry.md);
  compare actual shared files before edits because the local snapshot may be
  older. No conversion or Computer Use task remains scheduled.
  Remaining: check the selected daily route against the actual chosen application release,
  including AST-03 optional-note validation and workflow/QA/status handoffs;
  record exact-version review and coordinate real operator/A4/label trials
  with the staff-journey trial below. Fresh-device/fonts remain unqualified. Done when the chosen
  staff-route versions match the named target and have explicit decisions.
  Technical conversion or NAS distribution does not transfer older acceptance.
  Keep the bounded AC-01 export variation and all historical artifacts recorded.
- [ ] **START-06 - Observe and accept the complete staff journey.**
  Owner/intended users: sign in as each operational role; verify permitted and
  forbidden actions, workflow search/reordering, saving/evidence/private files,
  interruption/recovery, QA and the final device identity. Prepare every work
  phone's translation/camera settings; physically print and scan the intended
  stock and test the guides on A4. Done when actual users' results, wrong turns,
  fixes and remaining exceptions are recorded against exact app/guide versions.

- [ ] **NEXT-08 - Validate the delivered admin/catalogue manuals against the release.**
  Administrative/catalogue PDF/native production and packaging are complete
  within the same25-manual NAS delivery as START-05. Follow the same
  [handoff](docs/manuals/operator-guides/HANDOFF.md) and exact register; this
  is not a second conversion queue. Remaining: fresh USR-02 open-choice/UI
  evidence for clipped status labels, risky/interrupted routes on disposable
  data, the owner's source-recording policy, reader/fresh-device checks and exact
  version decisions. Preserve login-disable versus deletion/restoration scope,
  field-to-result support, newer NAS edits and all historical acceptance.
  Legacy generator/runtime metadata must not select or accept a newer native
  artifact by implication. Do not reconnect the cleaned NUC.

The unrelated sections below retain their previous committed contents.
The newer global backlog/onboarding reconciliation remains in the shared
working tree for its owning task; this manual commit does not publish it.

## Production Host And CUPS Migration - 2026-09-17

- [x] Move Snipe-IT to `10.10.10.249`. The immutable application deployment,
  recovery set, health checks, and production catalogue release completed on
  2026-10-01; see
  `docs/releases/workflow-catalog-search-production-2026-10-01.md`.
- [ ] Reconcile the remaining CUPS portion of
  `docs/plans/production-host-migration-2026-09-17.md` against the current
  printer-node setup. Verify the active production `CUPS_SERVER`, queue owners,
  and physical locations before moving anything; do not combine this with the
  legacy-evidence application release.

## Refurbisher Review Backlog - 2026-09-17

- [x] Implement the approved application portion of the consolidated
  refurbisher review backlog in
  `docs/plans/refurbisher-review-followups-2026-09-17.md`.
- [x] Record the product decisions for status rights, workflow completion,
  dashboard actions, quality edit, password baseline, and Remember Me.
- [ ] Use the document's Manual Administration Work section for approved system
  configuration and its Guide Updates section for newly versioned guides. The
  application handoff is in `docs/refurbisher-follow-up-configuration.md`.
- [x] Configure and verify the production status matrix: QA
  Hold requires a note; Supervisor/Admin can view and choose Afgevoerd;
  ordinary Refurbisher/Senior cannot; and Supervisor has explicit View plus
  Choose/use for Broken/Parts. The 2026-09-29 production release and four-role
  browser matrix verified this contract, including the Admin-only locked-status
  exit.
- [x] Restore a reliable single-command full PHPUnit gate: remove or recreate
  the stale `tests/Feature/Maintenances/Api` suite entry, ensure an isolated
  `.env.testing` supplies the example APP_KEY, and identify what recreates
  `bootstrap/cache/config.php` during broad shared-container runs. The stale
  suite entry and Windows LF-sensitive release checks are fixed; the guarded
  non-LDAP command completed cleanly without recreating unsafe cached config.
- [x] Fix the confirmed attribute lifecycle gap: existing hidden/deprecated
  component/model assignments remain editable/removable, while new assignments
  require current attributes. Foundation workflow reseeding no longer
  overwrites administrator-managed content/order.
- [x] Fix the reported 6400 MHz/3200 MHz component-derived specification error.
  The root cause was additive aggregation of repeated RAM speed; the distinct
  aggregation migration was deployed on 2026-09-29 and production asset
  `INBIT-AA0010` now resolves to 3200 MHz while retaining 16 GB.
- [ ] Reproduce and fix intermittent phone scrolling on hardware detail pages.
  Separately profile the slow History tab and add immediate loading/progress
  feedback so operators can distinguish a long request from a frozen page.

## Printer Operations - 2026-11-12

- [ ] Replace raw printer queue/network labels with stable operator-facing
  locations. Preserve queue IDs for routing, but present the approved mapping:
  LabelWriter 400 Turbo = `Office`; LabelWriter 330 and Twin Turbo = `Storage`.
  Record the mapping in configuration/runbooks rather than relying on chat
  context.
- [ ] Physically qualify each printer/stock/template combination before staff
  use. Keep the Zebra ZD421 25x25 path as an additional-printer investigation
  until media sensing, calibration, centering, and single-label test output are
  repeatable; avoid unbounded self-test/calibration runs that waste stock.

## Current Guide Review Clearance - 2026-09-10

- [x] Record owner clearance for SC-01 v12, AST-02 v8, WF-01 v13, CMP-02 v6,
  CMP-04 v7, USR-01 v13, USR-02 v11, USR-04 v6, CAT-00 v11 and CAT-01 v7.
- [ ] Collect feedback from other users against these exact versions, including
  page/step and the point where help was needed. Third-party approval is pending.
- [Clearance and exact hashes](docs/manuals/operator-guides/reviews/owner-review-readiness-2026-09-10.md).
  Earlier pending tasks below are historical where superseded by this exact
  clearance; unlisted versions, operational decisions and the prototype remain open.

- [ ] Execute the remaining V1 implementation and qualification plan in
  `docs/plans/v1-remaining-implementation-plan-2026-08-18.md`.
- [x] Grant Supervisor ordinary product/catalog/workflow setup while keeping
  destructive lifecycle and cleanup Admin-only, including migrated legacy-
  grant hardening and populated-rehearsal promotion.
- [x] Qualify and cold-restart the exact Supervisor-capable app/web images on
  the populated MariaDB rehearsal without row or upload-manifest drift.
- [x] Implement explicit fail-closed LDAP-disabled production behavior and
  document LDAP as unsupported until a real directory validation is possible.
- [x] Implement an explicit mail-disabled production profile, including UI,
  queue, password-recovery, health/readiness, and documentation behavior;
  keep SMTP unsupported until a real relay validation is possible.
- [ ] Post-V1: reassess PHPStan in an isolated environment. It is not a V1
  release gate; do not refresh its baseline or change runtime code for it as
  part of the current release work.
- [x] Accept the current QR label layout and templates for V1.
- [ ] Post-V1: build a QR/label design tool with printer-specific limits,
  physical sticker sizes, resolution validation, preview, and reusable templates.
- [ ] Replace remaining device catalog placeholder MPN/SKU codes.

- [x] Add ability to resume a closed test run (reopen/duplicate prior run and continue tests)
- [x] Improve mobile scan feedback and close-range behavior with explicit camera
  states, retry/refocus recovery, capability-gated continuous focus, framing
  guidance, and manual asset/component/serial lookup.
- [x] Document the agreed username convention: first name with an initial capital followed by the lowercase first letter of each last-name part, without periods or spaces.
- [ ] Decide the user email-address standard with the manager; do not invent addresses in operator guidance.
- [x] Rework all 12 conditional/failing cold-start guides and rerun the gate;
  the latest pass produced AC-02 v3, AST-03 v14, AST-04/05 v5, CMP-02 v4,
  CMP-04 v6, USR-01 v11, USR-02 v9, USR-03/04 v3, CAT-00 v8, and
  CAT-01 v5. Exact acceptance remains version-specific.
- [ ] Review and internally accept or revise the remaining user-account drafts:
  USR-03 v3, AC-02 v3, and two-sided USR-04 v3. USR-01 v8 and USR-02 v7
  remain accepted predecessors.
- [ ] Review AC-01 v8, AST-02 v6, CMP-01 v5, USR-01 v11, WF-01 v10,
  and WF-02 v11; prior accepted versions remain frozen until each replacement
  receives explicit exact-version acceptance.
- [x] Internally accept exact AST-03 v14 and freeze its two-page PDF.
- [ ] Review AST-04 v5 and AST-05 v5; confirm the local physical QA location
  while reviewing AST-04.
- [ ] Define operator-facing asset status names and in-application next-action
  cues for active work, waiting for QA, release, and return for correction;
  regenerate AST-03/04/05 if those labels or routes change.
- [ ] Migrate each guide generator to `scripts/manuals/lib/guide-system.mjs`
  when preparing that guide's next reviewed version; run component geometry and
  rendered-PDF QA before adding it to the internal review set.
- [ ] Extend runtime `GUIDE_REGISTRY` with the documented current version,
  page model, layout recipe, generator, and artifact metadata; add consistency
  tests before migrating the next accepted guide.
- [x] Replace hardcoded paths in maintained guide generators with the portable
  repository asset/runtime contract; keep superseded generators isolated under
  `scripts/manuals/archive/` as non-portable history.
- [x] Add the operator-guide handoff, authoritative guide sources, maintained
  generators, 111 canonical evidence files, locked baselines, and nine exact
  internal-review candidate PDFs to versioned repository storage.
- [ ] Move the superseded CAT-00 v7 and v8 drafts out of the live draft root
  after the external PDF reader releases them, then rerun the strict package
  verifier without a clean-mirror override.
- [x] Refresh the repository internal-review package to include USR-01 v8 and
  USR-02 v7 with a checksum manifest.
- [x] Investigate, capture, and draft USR-05 Groepen beheren v1 for reusable group creation and editing.
- [ ] Review USR-05 v2 as an exact three-page working draft and run its controlled-user required/forbidden-route test.
- [x] Define USR-03 password handoff as personal transfer followed immediately
  by AC-02; do not use chat, email, notes, tickets, or screenshots.
- [ ] Decide whether AC-02 needs a controlled success-message capture; the current draft deliberately stops at the empty form and save action.
- [x] Audit all 22 current generated guides / 48 pages for first-time usability,
  cognitive accessibility, consistency, and targeted local application truth:
  `docs/manuals/operator-guides/reviews/2026-09-08-independent-usability-audit.md`.
- [ ] Resolve the independent audit's role, identity, lifecycle-branch,
  product-code, workflow, and password-policy findings before independent use;
  then revise readability, evidence, and handoffs in new guide versions.
- [x] Generate a separate audit comparison batch: WF-01 v11, USR-04 v4,
  CAT-04 v3 and a 24-page comparison PDF with the preserved baseline versions.
- [x] Record the owner's rejection of the first audit pilot design direction;
  retain the PDFs and original selections. Only the clearer choice block was
  explicitly preferred; individual factual corrections remain undecided.
- [x] Generate WF-01 v12 (`WF-01-workflow-starten-v12-draft.pdf`) from v10
  with clearer step-3 alternatives, preserving 2A selection, 3A start,
  screenshots, hints, help, and page anatomy. Content and raster comparison pass.
- [ ] Review exact WF-01 v12, including physical A4 legibility and first-time
  use, before accepting or proposing wider changes.
- [ ] Review CAT-00 v9, CAT-01 v5, CAT-02 v1, CAT-03 v1, and CAT-04 v2 as
  separate exact working drafts; none is internally accepted yet.
- [x] Align, capture, and generate CAT-03 Attributen beheren v1 and CAT-04
  Componentdefinities beheren v1 from the verified Supervisor forms.
- [x] Align, capture, and generate CAT-02 Modelspecificatie opbouwen v1 using
  the concrete CAT-03/CAT-04 terminology and non-destructive evidence.
- [x] Capture 11 versioned replacement sources for the corrected shared
  checkbox/radio label layout without overwriting historical evidence.
- [x] Use corrected form-control evidence in the separate CAT-04 v3 audit pilot;
  remeasure focus annotations and retain the earlier v2 selection pending review.
- [ ] Generate new CAT-03 and CMP-02 draft versions from the corrected
  replacement sources and remeasure their source-pixel focus annotations.
- [ ] Review CAT-02 v1 as an exact six-page working draft; do not mark it
  accepted without explicit approval.
- [x] Generate CAT-05 v1 as five alternative Admin lifecycle/cleanup pages without repeating CAT-01 creation.
- [ ] Review CAT-05 v2 and execute each risky maintenance branch against disposable controlled data.
- [x] Generate CAT-06 v1 as a verification-only three-page draft without claiming durable source storage.
- [x] Revise USR-05, CAT-05 and CAT-06 as new v2 drafts from the cross-guide audit; preserve v1/accepted bytes.
- [ ] Review the exact CAT-06 v2 worked HP source comparison, including its explicitly unresolved exact-variant RAM.
- [ ] After owner review, revise incoming planned-guide wording in new versions of USR-01/02, CAT-00/02/03/04; never overwrite retained PDFs.
- [ ] Decide where catalogue source and verification evidence is recorded
  before CAT-06 can become an internal review candidate.
- [x] Defer battery-health automation to the post-V1 Windows inventory/diagnostic
  tool. The existing `scripts/hw-inventory.ps1` calculation is a prototype;
  validate its data sources and units before submitting results to workflows.
- [x] Use Workflow Profiles and Workflow Items as the configurable product
  vocabulary. Keep "test" where an individual diagnostic/result is genuinely a
  test and retain legacy `Test*` internals/routes for compatibility.
- [x] Restrict license keys, files, exports, reports, and seat operations with
  dedicated permissions and direct-route/API enforcement.
- [ ] Post-V1: add richer sold-device ownership-transfer and add-on software
  license flows without weakening the V1 entitlement/seat model.
- [x] Remove deprecated asset Devices/Apparaten tab; asset-to-asset attachment is replaced by Components in the current workflow.
- [ ] Post-V1: rework the asset Images tab into a unified device media view,
  preserving the V1 public-gallery/private-evidence boundary.
- [x] Remove Maintenance/Onderhoud from the current asset workflow and keep
  imported maintenance records/reports as permission-gated read-only history.
- [ ] Post-V1: rework asset Files/Bestanden, possibly combining its navigation
  with Images while preserving private storage and independent permissions.
- [ ] Post-V1: reconsider model-resource navigation; V1 keeps the separately
  permission-gated tab and labels it clearly as shared model-level content.
- [x] Make private asset files and model-level Extra files independently
  permission-gated for view, upload, and delete instead of inheriting ordinary
  asset/model view or edit rights.
- [x] Remove deprecated asset Send/Upload paperclip nav action; the authorized generic-file upload form now lives inside Files/Bestanden.


## Manual Focused Candidate Review - 2026-09-08
- [x] Cover all 22 existing guides using the WF-01 v12 approach; generate 20
  versioned candidates, retaining WF-01 v12 and CMP-04 v6 and all earlier files.
- [ ] Review exact versions using
  [the comparison index](docs/manuals/operator-guides/reviews/guide-set-comparison-2026-09-08.md).
- [ ] Trace representative routes with first-time users and on physical A4;
  retain the audit's unresolved operational policies and evidence gaps.

## Manual Review Bundle - 2026-09-10
- [x] Record exact owner acceptance: AC-01 v9, AC-02 v4, AST-03 v15, AST-04 v6.
- [x] Assemble all 22 current guides in a versioned scrolling PDF, retaining originals.
- [ ] Continue exact-version decisions for the remaining 18 guides, including
  WF-01 v12 (direction reviewed positively), using the
  [current review bundle](docs/manuals/operator-guides/reviews/current-set-review-2026-09-10.md).

## Owner Manual Corrections - 2026-09-10

Full scope, version targets, evidence, advice and pitfalls:
[owner review TODOs](docs/manuals/operator-guides/reviews/owner-review-todos-2026-09-10.md).

- [x] Record USR-04 v5 as explicitly not accepted; preserve earlier exact approvals.
- [x] MR-01: SC-01 step 2 / 2A badge clears description text.
- [x] MR-02: AST-02 alternative and return routes use complete styled references.
- [x] MR-03: WF-01 step 1 SC-01 reference uses family color/marker/code/title.
- [x] MR-04: CMP-02 2A/2B radio focus centres match actual screenshots.
- [x] MR-05: CMP-04 1B includes the whole Naar tray control and readable label.
- [x] MR-06: CAT-00 part 1 connector attaches to its target.
- [x] MR-07: CAT-01 page 2 routes name readable destinations instead of compressed codes.
- [ ] MR-08: Define and implement preferred Testing completed versus blocked/damaged handling; no extra Awaiting approval state.
- [x] MR-09: Redesign USR-04 as one task; remove check-in work and mixed OR lifecycle flow.
- [x] MR-10: Verify and explain relationship quantities, optional cases and nested components.
- [x] MR-11: Clarify/reposition CAT-01 page 4 single-asset deviation warning with a verified example.
- [ ] MR-12: Review the generated v1 field-to-result prototype; capture matching asset/component results and decide its guide placement.
- [x] MR-13: Desk-audit all 22 guides and put definitions first in CAT-00 learning cards; IDs remain stable. See prerequisite follow-ups.

### Owner Correction Follow-Up Evidence - 2026-09-10

- [Implementation and PDFs](docs/manuals/operator-guides/reviews/owner-corrections-2026-09-10.md).
- [ ] Observe a first-time-reader exercise, including an interrupted unsaved CAT/CMP form and return to the same record.
- [ ] Scope separate account reactivation, deletion and restoration instructions; USR-04 now only disables login.
- [x] Produce versioned USR-05, CAT-05 and verification-only CAT-06 working drafts.
- [ ] Confirm their profile/QA responsibilities, local help contacts, controlled execution checks, and exact-version review decisions.
