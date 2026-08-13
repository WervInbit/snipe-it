- [ ] Clean up QR code layout (confirm final sizing, margins, and templates).
- [ ] Replace remaining device catalog placeholder MPN/SKU codes.

- [x] Add ability to resume a closed test run (reopen/duplicate prior run and continue tests)
- [ ] Improve mobile scan feedback and close-range behavior (currently gets stuck with no feedback).
- [x] Document the agreed username convention: first name with an initial capital followed by the lowercase first letter of each last-name part, without periods or spaces.
- [ ] Decide the user email-address standard with the manager; do not invent addresses in operator guidance.
- [ ] Review and internally accept or revise the remaining user-account drafts: USR-03 v1, AC-02 v1, and two-sided USR-04 v1. USR-01 v8 and USR-02 v7 are accepted.
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
  generators, 48 canonical evidence files, locked baselines, and eight exact
  internal-review candidate PDFs to versioned repository storage.
- [x] Refresh the repository internal-review package to include USR-01 v8 and
  USR-02 v7 with a checksum manifest.
- [ ] Investigate, capture, and draft USR-05 Groepen beheren for reusable group creation and editing.
- [ ] Define the approved secure handoff channel and confirmation method for the USR-03 generated-password alternative; USR-01 now uses personal handoff and immediate AC-02 replacement.
- [ ] Decide whether AC-02 needs a controlled success-message capture; the current draft deliberately stops at the empty form and save action.
- [ ] Decide whether to auto-calculate battery health percent from max/current capacity (and which fields/units to standardize).
- [ ] Decide terminology in UI/docs: keep "tests" or rename user-facing wording to "tasks" where steps are execution actions (for example cleaning/installing drivers), while keeping technical model names stable unless explicitly migrated.
- [ ] Rework asset Licenses tab with restricted access and explicit sold-device/add-on software license flows.
- [ ] Remove deprecated asset Devices/Apparaten tab; asset-to-asset attachment is replaced by Components in the current workflow.
- [ ] Rework asset Images tab into a unified device media view including asset images, workflow/test images, and thumbnails.
- [ ] Deprecate/remove asset Maintenance/Onderhoud tab from the current asset workflow.
- [ ] Rework asset Files/Bestanden, possibly combining it with Images into one attachments/media area for QR codes, images, PDFs, and related files.
- [ ] Investigate/rework asset Extra files/Extra bestanden; model-level files likely should not be a separate top-level asset tab.
- [ ] Remove deprecated asset Send/Upload paperclip nav action after investigating upload ownership and moving upload actions into their relevant blocks.

