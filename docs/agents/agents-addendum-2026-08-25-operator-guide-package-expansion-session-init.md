# Operator Guide Package Expansion - 2026-08-25

## Scope

- Preserve the latest unaccepted guide PDFs for work on another device.
- Keep accepted and unaccepted PDFs visibly and mechanically separate.
- Include all canonical evidence required to regenerate and review the guides.
- Avoid staging unrelated application, release, infrastructure, or test work.

## Result

- Nine accepted internal-review PDFs remain under
  `resources/manuals/operator-guides/pdf/`.
- Seventeen explicitly unaccepted review PDFs are stored under
  `resources/manuals/operator-guides/drafts/` with checksums, page counts, and
  accepted-predecessor metadata.
- The canonical evidence package contains 72 sources, including 24 additions
  used by the latest AST, user, and catalogue drafts.
- The portable verifier checks both PDF collections for status, hashes, A4
  geometry, page counts, development URLs, and predecessor consistency.

## Validation

- `npm test` from `scripts/manuals` passes.
- Package summary: 72 evidence sources, 9 accepted PDFs / 11 pages,
  17 unaccepted PDFs / 30 pages, 2 baselines, and 16 active scripts.
- `git diff --check` reports no whitespace errors in the guide-package scope.
