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
  AC-02 v2, AST-03 v13, AST-04/05 v4, CMP-02 v3, CMP-04 v6, USR-01
  v10, USR-02 v8, USR-03/04 v2, and CAT-00/01 v2 now pass as working drafts.
- [ ] Review and internally accept or revise the remaining user-account drafts: USR-03 v2, AC-02 v2, and two-sided USR-04 v2. USR-01 v8 and USR-02 v7 remain accepted predecessors.
- [ ] Review AC-01 v8, AST-02 v6, CMP-01 v5, USR-01 v10, WF-01 v10,
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
  generators, 71 canonical evidence files, locked baselines, and nine exact
  internal-review candidate PDFs to versioned repository storage.
- [x] Refresh the repository internal-review package to include USR-01 v8 and
  USR-02 v7 with a checksum manifest.
- [ ] Investigate, capture, and draft USR-05 Groepen beheren for reusable group creation and editing.
- [x] Define USR-03 password handoff as personal transfer followed immediately
  by AC-02; do not use chat, email, notes, tickets, or screenshots.
- [ ] Decide whether AC-02 needs a controlled success-message capture; the current draft deliberately stops at the empty form and save action.
- [ ] Review CAT-00 v2 and CAT-01 v2 as separate exact working drafts; neither
  is internally accepted yet.
- [ ] Capture and generate CAT-02 Modelspecificatie opbouwen, then use its real
  missing-definition handoffs to generate CAT-03 and CAT-04.
- [ ] Generate CAT-05 after CAT-01/CAT-02 wording settles.
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

