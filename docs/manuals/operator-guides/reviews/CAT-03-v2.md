# CAT-03 v2 Focused Review Candidate

Status: unaccepted candidate; exact-version owner review pending.

Round: 2026-09-08, separate from the prior Sol baseline and rejected pilot.
The owner approved the WF-01 v12 direction for propagation, not automatic
acceptance of every new PDF.

## Exact Artifacts

- [Candidate PDF](../../../../resources/manuals/operator-guides/review-rounds/2026-09-08/CAT-03-attributen-beheren-v2-draft.pdf) - 5 page(s), 458919 bytes.
- SHA-256: `8adb74b5d3d535def133a9f6987a921acad64d3c68641c8a6076f38a2d0e415b`.
- [Frozen baseline v1](../../../../resources/manuals/operator-guides/checkpoints/2026-09-08-before-audit-revisions/drafts/CAT-03-attributen-beheren-v1-draft.pdf).
- Generator: `scripts/manuals/generate-guide-followups.mjs CAT-03`.
- Original renderer: `generate-catalog-guide-review.mjs` (unchanged).

## Changes And Tradeoffs

Audit references: AUD-13/15/16/18.

Use the four canonical 02 form sources. Field-summary badges are letters rather than extra step numbers. Page 4 chooses numeric OR Enum, skips both for Bool/Text, names Add to list, and shows its label. Saved-row/lifecycle work names Admin and the planned CAT-05.

Benefit: make the next action and its conditions clearer while retaining the
recognizable task layout. Cost: some local wording and spacing differ from
the baseline; reviewers should use the exact linked pair rather than infer
approval from the version number.

The existing Enum capture ends at the bottom of the Add to list button; its label and inputs are visible but a fuller capture would improve it. Small print remains subject to physical A4/user review.

## Validation And Decision

Same page count and 8 raster-image placements as the baseline;
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
