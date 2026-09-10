# CMP-02 v5 Focused Review Candidate

Status: unaccepted candidate; exact-version owner review pending.

Round: 2026-09-08, separate from the prior Sol baseline and rejected pilot.
The owner approved the WF-01 v12 direction for propagation, not automatic
acceptance of every new PDF.

## Exact Artifacts

- [Candidate PDF](../../../../resources/manuals/operator-guides/review-rounds/2026-09-08/CMP-02-register-install-v5-draft.pdf) - 1 page(s), 578388 bytes.
- SHA-256: `95a8769c83aa157b7deb20e56b18ac06bd7de8fdc3a7803744f2782e952d4681`.
- [Frozen baseline v4](../../../../resources/manuals/operator-guides/checkpoints/2026-09-08-before-audit-revisions/drafts/CMP-02-register-install-v4-draft.pdf).
- Generator: `scripts/manuals/generate-guide-followups.mjs CMP-02`.
- Original renderer: `generate-component-followup-guides.mjs` (unchanged).

## Changes And Tradeoffs

Audit references: AUD-15/18.

Step 2 is a framed 2A OR 2B choice. Use the canonical definition/custom form captures ending 04. Missing reusable type routes through a Supervisor/CAT-04 and returns to step 2A. Full guide names retained.

Benefit: make the next action and its conditions clearer while retaining the
recognizable task layout. Cost: some local wording and spacing differ from
the baseline; reviewers should use the exact linked pair rather than infer
approval from the version number.

The agreed-exception policy for Custom and the condition-warning handoff remain unchanged. New form sources replace misleading controls; these are evidence replacements, not pixel-identical images.

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
