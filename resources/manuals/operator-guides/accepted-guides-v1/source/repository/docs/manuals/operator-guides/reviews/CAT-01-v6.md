# CAT-01 v6 Focused Review Candidate

Status: unaccepted candidate; exact-version owner review pending.

Round: 2026-09-08, separate from the prior Sol baseline and rejected pilot.
The owner approved the WF-01 v12 direction for propagation, not automatic
acceptance of every new PDF.

## Exact Artifacts

- [Candidate PDF](../../../../resources/manuals/operator-guides/review-rounds/2026-09-08/CAT-01-model-en-modelnummer-aanmaken-v6-draft.pdf) - 5 page(s), 455558 bytes.
- SHA-256: `494d1eee598f21ff51749a2e72006c45a31d667bbd64be8648d19ceae9b8388a`.
- [Frozen baseline v5](../../../../resources/manuals/operator-guides/checkpoints/2026-09-08-before-audit-revisions/drafts/CAT-01-model-en-modelnummer-aanmaken-v5-draft.pdf).
- Generator: `scripts/manuals/generate-guide-followups.mjs CAT-01`.
- Original renderer: `generate-catalog-guide-review.mjs` (unchanged).

## Changes And Tradeoffs

Audit references: AUD-05/13/16/20.

Page 2 explicitly chooses A, B OR C with page destinations, and wraps text clear of screenshots. Page 4 distinguishes manufacturer Product ID/P/N from software ID/SN/tag and explicitly instructs Opslaan. Page 5 marks CAT-02 as a working concept, not absent.

Benefit: make the next action and its conditions clearer while retaining the
recognizable task layout. Cost: some local wording and spacing differ from
the baseline; reviewers should use the exact linked pair rather than infer
approval from the version number.

Baseline pictures and five-page layout retained. Exact manufacturer/source verification still depends on suitable evidence; no new source-storage feature is implied.

## Validation And Decision

Same page count and 10 raster-image placements as the baseline;
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
