# USR-04 v5 Focused Review Candidate

Status: explicitly not accepted by the owner on 2026-09-10; scope and flow redesign required.

The owner rejected the mixed disable/delete/restore purpose, the top OR route,
and check-in work in this guide. See [MR-09 and the full review TODOs](owner-review-todos-2026-09-10.md).
Keep this exact PDF for comparison; the earlier baseline is not automatically approved.

Round: 2026-09-08, separate from the prior Sol baseline and rejected pilot.
The owner approved the WF-01 v12 direction for propagation, not automatic
acceptance of every new PDF.

## Exact Artifacts

- [Candidate PDF](../../../../resources/manuals/operator-guides/review-rounds/2026-09-08/USR-04-gebruiker-uitschakelen-of-herstellen-v5-draft.pdf) - 2 page(s), 789406 bytes.
- SHA-256: `460d508ca86972b86926f3285491e970071acc0b020ebf4ecf18771f7c570665`.
- [Frozen baseline v3](../../../../resources/manuals/operator-guides/checkpoints/2026-09-08-before-audit-revisions/drafts/usr-04-gebruiker-uitschakelen-v3-draft.pdf).
- Generator: `scripts/manuals/generate-guide-followups.mjs USR-04`.
- Original renderer: `generate-user-account-guide-review.mjs` (unchanged).

## Changes And Tradeoffs

Audit references: AUD-02/03/04/12/14/15.

Keep two pages and nine images. Page 1 corrects ownership wording, crops activation controls, and exposes Login Nee with contain fitting. Page 2 explicitly separates delete (5-6) from restore (6-8), preserves the previous login state, and verifies the actual restored access.

Benefit: make the next action and its conditions clearer while retaining the
recognizable task layout. Cost: some local wording and spacing differ from
the baseline; reviewers should use the exact linked pair rather than infer
approval from the version number.

USR-04 v4 remains rejected. Full asset/license/management transfer procedures and urgent-disable timing are still operational gaps. The control crop is not a new same-person capture; restoration is not guaranteed to disable login.

## Validation And Decision

Same page count and 9 raster-image placements as the baseline;
help headings retained. Rendered pages visually inspected. PDF text bounds,
version/date, image inventory, and extracted text changes checked. Image count
is not a claim that every crop or source is identical. See the set validation
for the deliberate source/crop and spacing exceptions.

No physical A4 print, first-time-user trial, production configuration check,
or fresh complete live workflow was performed. No new acceptance is recorded.
Decision: not accepted. Reviewer: project owner. Date: 2026-09-10.
The PDF/hash above is unchanged. A replacement requires a new version and review.

To reject: leave current manifests unchanged and continue from the frozen
baseline. To accept: record the exact version/hash and selection separately;
keep earlier files. See [set comparison](guide-set-comparison-2026-09-08.md).
