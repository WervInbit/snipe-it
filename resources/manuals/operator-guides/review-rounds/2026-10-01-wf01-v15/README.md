# WF-01 v15 Correction Round

Status: **working draft; not accepted**. One A4 page.

Addresses owner feedback on v14: 2A now shows Standard Diagnostics with Start,
followed by blocked Cleaning. "Bekijk de workflowvolgorde" replaces the
unexplained agreement about a row. 3A starts the same Standard Diagnostics
example. WF-02 v13 and all prior artifacts are unchanged.

See [the review](../../../../../docs/manuals/operator-guides/reviews/WF-01-v15.md)
for changes, exact hash, capture context and outstanding trials. The manifest
records immutable PDF/HTML, generator/input/evidence hashes, crops and QA.
The final HTML embeds all five visuals, including the retained 3B/4A evidence.

From the repository root, with existing manual dependencies available (or
`GUIDE_NODE_MODULES_ROOT` pointing to bundled Node modules), generate proofs:

```powershell
node scripts/manuals/generate-wf01-v15.mjs
```

Proofs go to `output/manuals/proofs/wf01-v15`. `--retain` refuses to replace
this frozen round or its delivery copy. For exact retained-layout reproduction:

```powershell
node scripts/manuals/render-guide-html.mjs --html resources/manuals/operator-guides/review-rounds/2026-10-01-wf01-v15/WF-01-workflow-starten-v15-draft.html --pdf output/manuals/proofs/wf01-v15-reproduction.pdf --pages 1
```

PDF creation metadata can change regeneration hashes; retain the exact reviewed
PDF. No accepted package or current-selection manifest is updated by this round.
