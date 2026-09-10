# USR-03 v4 Focused Review Candidate

Status: unaccepted candidate; exact-version owner review pending.

Round: 2026-09-08, separate from the prior Sol baseline and rejected pilot.
The owner approved the WF-01 v12 direction for propagation, not automatic
acceptance of every new PDF.

## Exact Artifacts

- [Candidate PDF](../../../../resources/manuals/operator-guides/review-rounds/2026-09-08/usr-03-wachtwoord-resetten-v4-draft.pdf) - 1 page(s), 724811 bytes.
- SHA-256: `b71a9279faa78ebc8b04ede3674067f73c563c0caac9cdc4b89c9cbb51ad7e06`.
- [Frozen baseline v3](../../../../resources/manuals/operator-guides/checkpoints/2026-09-08-before-audit-revisions/drafts/usr-03-wachtwoord-resetten-v3-draft.pdf).
- Generator: `scripts/manuals/generate-guide-followups.mjs USR-03`.
- Original renderer: `generate-user-account-guide-review.mjs` (unchanged).

## Changes And Tradeoffs

Audit references: AUD-02/04/12.

Keep the prior login state during reset. Shorten/wrap headings and body to clear the image column. Crop 2A to password controls and label it Bedieningsdetail, retaining all five frames and four help tiles.

Benefit: make the next action and its conditions clearer while retaining the
recognizable task layout. Cost: some local wording and spacing differ from
the baseline; reviewers should use the exact linked pair rather than infer
approval from the version number.

A control-only crop removes the misleading Demo identity from the mutation illustration. It does not prove a same-person end-to-end reset. Disabled access and safe delivery still require the stated decision.

## Validation And Decision

Same page count and 5 raster-image placements as the baseline;
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
