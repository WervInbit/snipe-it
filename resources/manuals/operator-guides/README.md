# Operator-Guide Repository Assets

This directory contains the portable binary inputs and frozen review artifacts
for the operator-guide system.

- `evidence/` contains unannotated canonical screenshots and their checksums,
  including the evidence used by accepted candidates and current drafts.
- `baselines/` contains the locked AC-01 v6 and SC-01 v10 SVG sources used by
  the current batch generator.
- `pdf/` contains the nine exact internal-review candidate PDFs and their
  checksums.
- `drafts/` contains the latest generated but explicitly unaccepted PDFs used
  for review and environment transfer. Its manifest records any accepted
  predecessor separately.

Do not edit accepted PDFs or baselines in place. A visible guide change creates
a new version, review record, and artifact. Drafts may move to `pdf/` only after
explicit exact-version acceptance. Other generated proofs remain under the
ignored `output/` tree.
