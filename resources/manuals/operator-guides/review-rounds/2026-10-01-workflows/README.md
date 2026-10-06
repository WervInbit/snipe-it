# October Workflow Manual Comparison Drafts

Status: working drafts; **WF-01 v14 and WF-02 v13 are not accepted**.

- WF-01-workflow-starten-v14-draft.pdf: one A4 page.
- WF-02-complete-workflow-v13-draft.pdf: two A4 pages.
- Matching HTML: self-contained final render sources with embedded evidence.
- `inputs/`: exact prior v13/v12 HTML snapshots, retained for comparison.
- `evidence/`: six read-only local captures; complete raw JPEGs are retained.
- `source-audit.json`: development source identity and file hashes.
- `manifest.json`: candidate/evidence/input hashes, geometry inventory and
  explicit unaccepted state. It does not select a new accepted guide version.

See [the comparison review](../../../../../docs/manuals/operator-guides/reviews/workflow-revisions-2026-10-01.md)
for exact previous PDFs, benefits, tradeoffs, source context, and pending trials.
The accepted package and older review rounds remain unchanged.

From the repository root, regenerate separate proofs with the existing manual
Node dependencies or `GUIDE_NODE_MODULES_ROOT` pointing to the bundled modules:

```powershell
node scripts/manuals/generate-workflow-revisions-2026-10-01.mjs
```

Use `--out-dir <separate-proof-directory>` for another proof destination.
The `--retain` option refuses to replace this frozen round or delivery copies.
Chrome, Arial, pdfinfo and pdftoppm are needed for the generator's checks.

Verify retained candidates, delivery-copy equality, source/evidence hashes,
A4 dimensions and preservation of both previous comparison PDFs with:

```powershell
node scripts/manuals/verify-workflow-revisions-2026-10-01.mjs
```

For exact-layout reproduction from self-contained final HTML:

```powershell
node scripts/manuals/render-guide-html.mjs --html resources/manuals/operator-guides/review-rounds/2026-10-01-workflows/WF-01-workflow-starten-v14-draft.html --pdf output/manuals/proofs/wf01-reproduction.pdf --pages 1
```

Use the corresponding WF-02 HTML and `--pages 2` for the other guide.
PDF creation metadata can change bytes on regeneration; compare rendered
pages for layout and retain the manifest's exact reviewed PDF hashes.
