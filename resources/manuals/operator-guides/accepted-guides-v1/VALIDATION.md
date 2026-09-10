# Package Validation

Validated on 2026-09-10 before delivery.

- 16 latest recorded accepted guides, 27 pages, plus seven historical accepted
  PDFs. Every copied PDF matches its recorded SHA-256 and original source.
- Copied the entire kit to a different directory whose name contains spaces,
  then ran the packaged renderer there without the application or database.
  All 16 self-contained HTML sources rendered with no missing image or
  external resource request. Accepted inputs and PDFs stayed unchanged.
- All **27 of 27 pages** are pixel-identical to their accepted PDFs at 96 dpi;
  PDF page counts match. PDF metadata can differ after regeneration.
- Visually inspected both WF-02 pages and the CMP-01 and SC-01 pages.
  No regeneration differences were found; accepted content was not redesigned.
- Existing proof-run reuse, known-version reuse and accepted-source-as-draft
  attempts were refused before output creation. Integrity passed afterward.
- The README's package links and each guide's acceptance-evidence path exist.
- Scoped documentation whitespace checks passed. Package Git attributes
  disable line-ending conversion to preserve checksums across checkouts.

Environment: Windows, Node v22.19.0, Playwright 1.62.1, Chrome
152.0.7977.83, installed Arial, bundled Python/Pillow/pypdf and Poppler.
Dependency versions are pinned under `scripts/`. Chrome/font versions on
another computer can affect page geometry; compare before using new proofs.
Installation on a clean operating system was not tested.

Reproduce the checks using the commands in [README.md](README.md).
The reports below refer to generated proof paths relative to
`work/baseline-check/`; they do not replace the accepted `pdf/` files.
Proof PDFs and comparison PNGs are excluded from this frozen package.

- [Render report](validation/render-report.json)
- [Page comparison report](validation/comparison-report.json)
- [Write-guard report](validation/guard-report.json)

The final package manifest covers this validation record and all listed files.
Run `node scripts/verify.mjs` to confirm the delivered snapshot's integrity.
Physical print, first-time-user trials and third-party review remain pending.
No application test suite or live-system mutation was needed for this package.
