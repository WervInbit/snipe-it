# Audit Revision Round - 2026-09-08

Status: owner rejected the first pilot direction on 2026-09-08; candidates
are retained for comparison. See the [feedback and revised approach](2026-09-08-pilot-direction-feedback.md).

---

**Existing guide round: preserved.** Use the
[frozen baseline](baseline-2026-09-08.md) for exact current and internally
accepted versions, unchanged PDFs, source recovery, and SHA-256 checksums.

**New-model audit round: proposals for review.** The owner explicitly requested
this separation because earlier guides may already be accepted and the new
changes may be rejected. The audit is an additional assessment; its findings
do not automatically replace accepted guide content or policy.

---

## Review Boundary

- Keep all 29 checkpoint PDFs and their recorded acceptance states intact.
  The checkpoint also preserves unaccepted drafts; freezing is not approval.
- Give every changed guide a new unused integer version. Identify it as
  `Audit revision round 2026-09-08 - Unaccepted working draft` in its review
  record and review-package index. Use the usual versioned draft filename.
- When combining PDFs for review, put a separate divider before the new
  candidates: `Nieuwe beoordelingsronde - 2026-09-08 - Niet geaccepteerd`.
  Label baseline and candidate versions clearly. Keep model provenance in the
  review notes; operator instructions retain their task-focused layout.
- Record which exact frozen draft and accepted predecessor were compared.
  Acceptance of a predecessor does not transfer to a candidate.
- Judge each proposed change on task correctness, predictability, readability,
  and user evidence. Greater uniformity can add length or obscure a useful
  task-specific layout. Record that tradeoff instead of assuming the audit
  recommendation is always better.
- Discuss proposed changes to accepted operational policies as decisions.
  Preserve the prior policy record until the owner explicitly changes it.
- Prototype a focused guide before propagating a shared change. Keep the
  affected-guide list and source changes separable enough to reject a proposal
  without losing unrelated improvements.
- Record acceptance, rejection, or a revision request against exact candidate
  bytes. An empty decision field means pending, never accepted. Keep rejected
  candidates and their review records; do not reuse their version numbers.

## Candidate Ledger

The owner requested a small PDF comparison batch. The
[comparison package](../../../../resources/manuals/operator-guides/review-rounds/2026-09-08/Handleidingen-vergelijking-auditronde-2026-09-08.pdf)
contains the frozen earlier drafts, a visible new-round divider on page 12,
ten new guide pages, and accepted WF-01 v9 as an appendix. Existing draft and
accepted selections remain unchanged. These candidates do not close the audit
or establish first-time-user usability.

| Guide / candidate | Frozen draft / accepted predecessor | Review record and PDF | Decision / reviewer / date |
| --- | --- | --- | --- |
| WF-01 v11 | v10 draft / v9 accepted | [Review and exact PDF](WF-01-v11.md) | Direction rejected / owner / 2026-09-08 |
| USR-04 v4 | v3 draft / none | [Review and exact PDF](USR-04-v4.md) | Batch direction rejected / owner / 2026-09-08 |
| CAT-04 v3 | v2 draft / none | [Review and exact PDF](CAT-04-v3.md) | Batch direction rejected / owner / 2026-09-08 |
| WF-01 v12 | v10 draft / v9 accepted | [Review and exact PDF](WF-01-v12.md) | Direction reviewed positively; propagation authorized / owner / 2026-09-08 |

Only the clearer choice block was explicitly preferred; individual factual
fixes remain undecided. Start a later focused proof from the baseline, keeping
screenshots, hints, help, and task-dependent layout. Do not propagate this batch.

The owner-requested **WF-01 v12** is generated from v10 as
`WF-01-workflow-starten-v12-draft.pdf`; see [its review](WF-01-v12.md).
All original screenshots, instructions, help, and page anatomy are retained.
Only the step-3 choice frame/introduction and version/date changed. V12 is a
separate one-page draft, reviewed positively for this direction; the earlier 24-page comparison
package remains unchanged. Use the same filename stem and increment versions
for later revisions, without extra naming variants.

The owner subsequently confirmed coverage of **all existing guides**. The
[new comparison index](guide-set-comparison-2026-09-08.md) contains twenty
separately versioned candidates; WF-01 v12 and CMP-04 v6 remain unchanged.
Their individual acceptance remains pending. See the
[scope and fit exceptions](guide-set-followups-2026-09-08.md).

Reproduce v12 with `node scripts/manuals/generate-wf01-v12.mjs`. Proofs stay
under `output/manuals/proofs/WF-01-v12`, and the final build output is under
`output/pdf`. The reviewed copy, source snapshot, validation, and
`WF-01-v12-manifest.json` are retained separately beside the earlier round
files; the first batch's manifest and sources are not overwritten.

The [pilot specification](audit-pilot-2026-09-08-specification.md) records these
historical candidate changes. The [round manifest](../../../../resources/manuals/operator-guides/review-rounds/2026-09-08/manifest.json)
records all four PDF checksums, comparison-page mapping, and a source snapshot.
[Validation](../../../../resources/manuals/operator-guides/review-rounds/2026-09-08/validation.json)
includes rendering, geometry and integrity evidence with explicit limitations.

Reproduce into ignored output directories from the repository root:

```text
node scripts/manuals/generate-audit-pilot-review.mjs
python scripts/manuals/build-audit-pilot-comparison.py
```

Use the existing portable dependency settings in HANDOFF.md if Node/Python
dependencies are not installed locally. Generated proofs use
`output/manuals/proofs/audit-pilot-2026-09-08`; final build output uses
`output/pdf/audit-pilot-2026-09-08`. Retained reviewed copies live under
`resources/manuals/operator-guides/review-rounds/2026-09-08` and must not be
overwritten by regeneration. Preserve rejected candidates and their decisions.

Each candidate review record must include:

1. Round ID, guide/version, PDF path and SHA-256, and comparison versions.
2. Audit finding IDs and exact changed pages, steps, choices, or screenshots.
3. Reason for the change, expected benefit, and possible drawback or pitfall.
4. Evidence and validation, including what remains untested with actual users.
5. Affected guides, shared rules, source files, and any policy decision needed.
6. Exact decision, reviewer, date, and a way to restore the prior selection.

Use [maintenance.md](../maintenance.md) for normal generation and review checks.
The [baseline recovery instructions](baseline-2026-09-08.md#compare-or-reject-a-later-proposal)
explain how to compare or recover artifacts and source without replacing
unrelated work.

## Exact Acceptance Update - 2026-09-10

Owner accepted AC-01 v9, AC-02 v4, AST-03 v15 and AST-04 v6. Their PDF bytes
remain unchanged. See [current set review](current-set-review-2026-09-10.md)
for exact hashes and the all-guide scrolling bundle; all other decisions remain separate.
