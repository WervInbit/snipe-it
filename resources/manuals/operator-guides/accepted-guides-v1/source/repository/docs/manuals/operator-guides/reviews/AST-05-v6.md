# AST-05 v6 Focused Review Candidate

Status: unaccepted candidate; exact-version owner review pending.

Round: 2026-09-08, separate from the prior Sol baseline and rejected pilot.
The owner approved the WF-01 v12 direction for propagation, not automatic
acceptance of every new PDF.

## Exact Artifacts

- [Candidate PDF](../../../../resources/manuals/operator-guides/review-rounds/2026-09-08/AST-05-review-release-v6-draft.pdf) - 1 page(s), 697718 bytes.
- SHA-256: `2311d189b061d7251fe0e4eecc0ce542c1feb7d2ba2ff0a933f05a1ad698ee77`.
- [Frozen baseline v5](../../../../resources/manuals/operator-guides/checkpoints/2026-09-08-before-audit-revisions/drafts/AST-05-review-release-v5-draft.pdf).
- Generator: `scripts/manuals/generate-guide-followups.mjs AST-05`.
- Original renderer: `generate-revised-guide-set.mjs` (unchanged).

## Changes And Tradeoffs

Audit references: AUD-08/17.

Step 2 checks the required profile set and evidence. Step 4 visibly offers release OR return. Keep all six images, status instructions, and help. Reduce top padding in steps 2/4 so captions stay in their cards.

Benefit: make the next action and its conditions clearer while retaining the
recognizable task layout. Cost: some local wording and spacing differ from
the baseline; reviewers should use the exact linked pair rather than infer
approval from the version number.

No live production profile/readiness validation or physical release test. A failed control remains a truthful result; it must not be changed merely to permit release.

## Validation And Decision

Same page count and 6 raster-image placements as the baseline;
help headings retained. Rendered pages visually inspected. PDF text bounds,
version/date, image inventory, and extracted text changes checked. Image count
is not a claim that every crop or source is identical. See the set validation
for the deliberate source/crop and spacing exceptions.

No physical A4 print, first-time-user trial, production configuration check,
or fresh complete live workflow was performed. No new acceptance is recorded.
Decision: pending. Reviewer/date: not yet recorded.

To reject: leave current manifests unchanged and continue from the frozen
baseline. To accept: record the exact version/hash and selection separately;
keep earlier files. See [set comparison](guide-set-comparison-2026-09-08.md).
