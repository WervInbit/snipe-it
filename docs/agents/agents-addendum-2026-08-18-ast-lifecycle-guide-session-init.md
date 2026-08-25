# AST Lifecycle Guide Session Init - 2026-08-18

## Scope

- Create evidence-ready v2 review drafts for AST-03, AST-04, and AST-05.
- Preserve the accepted guide package and historical v1 generator defaults.
- Keep generated proofs under the ignored `output/manuals/` and `output/pdf/`
  trees until the user reviews each exact version.

## Operational Decisions

- AST-03 starts registered refurbishment work in `Being Processed`.
- AST-04 hands completed work to supervisor review with `QA Hold`.
- AST-05 releases approved work as `Ready for Sale`; rejected work returns to
  `Being Processed`.
- The local physical QA location remains an operational confirmation item.

## Evidence Safety

- Captures use the controlled development environment only.
- Fictional visible `INBIT-*` identities are screenshot-only DOM substitutions
  recorded in the evidence catalog and manifest.
- No asset was created, renamed, saved, or otherwise changed for these
  lifecycle captures.
- Credentials, cookies, and browser profiles are not stored in the repository.

## Maintained Paths

- Specifications: `docs/manuals/operator-guides/guides/AST-03.md` through
  `AST-05.md`.
- Reviews: `docs/manuals/operator-guides/reviews/AST-03-v2.md` through
  `AST-05-v2.md`.
- Generator: `scripts/manuals/generate-revised-guide-set.mjs`.
- Capture script: `scripts/manuals/capture-asset-lifecycle-guide-evidence.mjs`.
- Canonical evidence: `resources/manuals/operator-guides/evidence/`.
- Focused proofs:
  `output/manuals/proofs/2026-08-18-ast-lifecycle-review/`.

## Resume Point

Generate each guide with its `SNIPEIT_AST0N_VERSION=2` override, inspect every
rendered A4 page, run the portable guide package validation, and then present
the three exact PDFs for user review. Do not mark any version accepted without
the user's explicit decision.

## v3 Feedback Continuation

- Owner feedback moved AST-03, AST-04, and AST-05 to separate v3 review
  branches; v2 remains historical evidence and is not overwritten.
- AST-03 removes location from the primary path, treats duplicate identity as
  a recoverable warning, documents `Unlock`/`Aa` and automatic uppercase, and
  uses contextual lower-right QR placement evidence.
- AST-04 and AST-05 use next-action wording around the currently deployed
  `QA Hold`, `Ready for Sale`, and `Being Processed` labels. Final status names
  and in-application next-action cues remain open product work.
- The six refreshed AST-03 captures used an unsubmitted create form. No asset
  or other server record was created, renamed, or saved.
- Resume with `SNIPEIT_AST0N_VERSION=3` for the v3 review PDFs. Keep their
  status as working drafts until the user approves each exact version.

## AST-03 v4 Continuation

- Owner review rejected the v3 focus geometry for 1A/1B and the cropped
  physical placement pair.
- v4 enlarges the two create-control focus frames and replaces the placement
  pair with one full-device, front-facing underside illustration.
- `AST-LABEL-PLACEMENT-GENERATED-01` is explicitly generated instructional
  material, not live evidence, and its QR is not intended for scanning. The
  separate verification step still uses the real mobile scanner capture.
- Package validation now expects and verifies 60 canonical evidence sources.
- Generate this exact draft with `SNIPEIT_AST03_VERSION=4`. Replace the
  generated placement source if the owner supplies a clearer real photo.

## AST-03 v5 Continuation

- Owner review rejected the v4 generated underside image and the remaining
  focus alignment.
- v5 uses target rectangles calculated from the canonical 780 x 1688 source
  and its rendered crop. The 1B image badge moves to the opposite corner.
- The active draft no longer uses `AST-LABEL-PLACEMENT-GENERATED-01`; that file
  remains only so v4 can be reproduced. HP's official bottom-view document is
  a reference, not guide evidence, because it does not show Inbit QR placement.
- Step 3 reserves one bounded slot for the owner's real full-underside photo.
  Step 4 removes the repeated scanner image and keeps only the opened-asset
  verification capture.
- Resume with `SNIPEIT_AST03_VERSION=5`. Do not classify v5 as evidence ready
  until the owner photo has replaced the explicit gap.

## AST-03 v6 Continuation

- Owner review found that v5 used `werkstatus` instead of the deployed
  `Status` label and did not frame the complete save action or a sufficient
  post-save check.
- v6 frames `Status: Being Processed` and the complete `Opslaan` button in 4A.
- New canonical `AST-REGISTER-SAVED-CHECK-01` evidence shows asset tag, status,
  asset name/model, and serial number in 4B. Identity substitution and row
  filtering occurred only in the capture DOM; no server record changed.
- Generate this draft with `SNIPEIT_AST03_VERSION=6`. It remains photo-pending
  until the owner supplies the real page-2 step-3 underside image.

## AST-03 v7 Continuation

- Owner review identified a missing transition from the expected dashboard
  start to the hardware index.
- v7 keeps four numbered steps and expands step 1 into 1A-1C: dashboard
  `Apparaten`, top-bar `Nieuwe aanmaken`, and toolbar `+`.
- The latter two images are grouped alternatives rather than separate steps.
- Generate with `SNIPEIT_AST03_VERSION=7`. The v6 save/status corrections are
  retained and the real page-2 placement photo remains pending.

## AST-03 v8 Continuation

- Owner review found the three v7 step-1 focus targets misaligned after the
  dashboard entry was introduced.
- v8 keeps the same screenshots and corrects only their generated target
  geometry: full dashboard tile, contained top-bar control, and centered
  toolbar `+`.
- Generate with `SNIPEIT_AST03_VERSION=8`. The page-2 placement photo remains
  the only explicit evidence gap.

## AST-03 v9 Continuation

- Owner review accepted 1A but requested tighter 1B and 1C target frames.
- v9 reduces the `Nieuwe aanmaken` target and removes excess right/bottom
  padding from the toolbar `+` target.
- Generate with `SNIPEIT_AST03_VERSION=9`. The page-2 placement photo remains
  the only explicit evidence gap.

## AST-03 v10 Continuation

- Owner review identified that the second page reset its steps even though
  AST-03 is one continuous workflow.
- v10 continues page 2 as steps 5-8 and numbers the pending placement-photo
  slot as 7A.
- The rejected generated image remains historical only; wait for the real
  owner photo rather than substituting it.
- Generate with `SNIPEIT_AST03_VERSION=10`.

## AST-03 v11 Continuation

- The owner supplied the real full-underside photo requested by v5-v10.
- v11 catalogs it as `AST-LABEL-PLACEMENT-PHOTO-01`, uses it in 7A, and adds a
  generated focus frame around the lower-right QR label.
- The equal-row page layout and single 8A post-scan result remain in use.
- Generate with `SNIPEIT_AST03_VERSION=11`. This branch has no explicit
  evidence gap and awaits exact-version review.

## AST-03 v12 Continuation

- Owner review found the v11 `Nieuwe aanmaken` frame still overextended and
  the toolbar `+` frame horizontally offset; v12 corrects only 1B and 1C and
  leaves the accepted 1A target unchanged.
- Damaged-label recovery now directs operators to manual search by unique
  Inbit asset tag or serial number instead of printing a replacement label.
- The v11 real placement photo and all other established AST-03 behavior remain
  unchanged.
- Generate with `SNIPEIT_AST03_VERSION=12`; this is the current
  evidence-complete branch awaiting exact-version review.
