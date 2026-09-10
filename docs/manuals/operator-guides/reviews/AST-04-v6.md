# AST-04 v6 Focused Review Candidate

Status: Internal review candidate; exact v6 accepted by the owner on 2026-09-10.

Round: 2026-09-08, separate from the prior Sol baseline and rejected pilot.
The owner approved the WF-01 v12 direction for propagation, not automatic
acceptance of every new PDF.

## Exact Artifacts

- [Candidate PDF](../../../../resources/manuals/operator-guides/review-rounds/2026-09-08/AST-04-complete-handoff-v6-draft.pdf) - 1 page(s), 809636 bytes.
- SHA-256: `de991dfcbc70b853f95135116e240e426d5582962accef6c3a26d7b40924b8c7`.
- [Frozen baseline v5](../../../../resources/manuals/operator-guides/checkpoints/2026-09-08-before-audit-revisions/drafts/AST-04-complete-handoff-v5-draft.pdf).
- Generator: `scripts/manuals/generate-guide-followups.mjs AST-04`.
- Original renderer: `generate-revised-guide-set.mjs` (unchanged).

## Changes And Tradeoffs

Audit references: AUD-08/09/17.

Step 1 checks all required profiles for the model. The screenshot is one example run. Step 2 identity mismatch means no edits, recheck via SC-01 and ask the supervisor. Preserve QA Hold, physical handoff, all images and help.

Benefit: make the next action and its conditions clearer while retaining the
recognizable task layout. Cost: some local wording and spacing differ from
the baseline; reviewers should use the exact linked pair rather than infer
approval from the version number.

The actual required profile set and QA location need local operational confirmation. Six retained references occupy three rows rather than removing useful routes.

## Validation And Decision

Same page count and 5 raster-image placements as the baseline;
help headings retained. Rendered pages visually inspected. PDF text bounds,
version/date, image inventory, and extracted text changes checked. Image count
is not a claim that every crop or source is identical. See the set validation
for the deliberate source/crop and spacing exceptions.

No physical A4 print, first-time-user trial, production configuration check,
or fresh complete live workflow was performed.
Decision: accepted. Reviewer: project owner. Date: 2026-09-10.
Acceptance applies to the exact version and SHA-256 above; the PDF was not changed.

The [dated selection record](current-set-review-2026-09-10.md) selects this
accepted version. Earlier files and frozen generation manifests remain intact
for comparison or a later explicit rollback. See [set comparison](guide-set-comparison-2026-09-08.md).
