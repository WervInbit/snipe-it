# Operator-Guide Repository Assets

This directory contains the portable binary inputs and frozen review artifacts
for the operator-guide system.

- `evidence/` contains unannotated canonical screenshots and their checksums.
- `baselines/` contains the locked AC-01 v6 and SC-01 v10 SVG sources used by
  the current batch generator.
- `pdf/` contains the eight exact internal-review candidate PDFs and their
  checksums.

Do not edit accepted PDFs or baselines in place. A visible guide change creates
a new version, review record, and artifact. Generated proofs remain under the
ignored `output/` tree.
