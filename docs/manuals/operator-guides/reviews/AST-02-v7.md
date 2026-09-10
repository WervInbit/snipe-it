# AST-02 v7 Focused Review Candidate

Status: unaccepted candidate; exact-version owner review pending.

Round: 2026-09-08, separate from the prior Sol baseline and rejected pilot.
The owner approved the WF-01 v12 direction for propagation, not automatic
acceptance of every new PDF.

## Exact Artifacts

- [Candidate PDF](../../../../resources/manuals/operator-guides/review-rounds/2026-09-08/AST-02-refurbishment-route-v7-draft.pdf) - 1 page(s), 104542 bytes.
- SHA-256: `76da3cb06057b70336103db61747a26564493a6aea6c67973f00db519f9ccef0`.
- [Frozen baseline v6](../../../../resources/manuals/operator-guides/checkpoints/2026-09-08-before-audit-revisions/drafts/AST-02-refurbishment-route-v6-draft.pdf).
- Generator: `scripts/manuals/generate-guide-followups.mjs AST-02`.
- Original renderer: `generate-revised-guide-set.mjs` (unchanged).

## Changes And Tradeoffs

Audit references: AUD-07/15/17.

Route 3: continue the correct unfinished run OR start once. The unregistered-asset branch names the Supervisor and return to SC-01. Complete guide titles wrap inside the existing route chips.

Benefit: make the next action and its conditions clearer while retaining the
recognizable task layout. Cost: some local wording and spacing differ from
the baseline; reviewers should use the exact linked pair rather than infer
approval from the version number.

Required profile selection still depends on the operational task and supervisor; no production profile configuration was inspected.

## Validation And Decision

Same page count and 0 raster-image placements as the baseline;
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
