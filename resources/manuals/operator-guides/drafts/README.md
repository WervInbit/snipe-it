# Unaccepted Operator-Guide Drafts

Every PDF in this directory is an **unaccepted working draft**. These files are
kept for review and transfer between environments; they are not approved
operator instructions and must not be published as the current accepted set.

Acceptance is exact-version only. After explicit acceptance, copy the exact PDF
to `../pdf/`, remove `draft` from its filename, update both manifests, and rerun
`scripts/manuals/verify-guide-package.mjs`.

`manifest.json` records the version, review status, page count, checksum, and
accepted predecessor where one exists.

The 2026-09-15 manifest retains 26 versions / 69 A4 pages across 23 guide
codes. USR-05, CAT-05 and CAT-06 each retain v1 and v2; code + version is the
unique artifact identity. Their v2 revisions remain unaccepted. See the
[v2 review](../../../../docs/manuals/operator-guides/reviews/USR-05-CAT-05-CAT-06-v2-2026-09-15.md).
