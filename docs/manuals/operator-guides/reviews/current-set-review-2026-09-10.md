# Current Guide Set Review - 2026-09-10

## Owner Clearance For User Review - 2026-09-10

The owner cleared the ten current changed versions in the v5 pair for review
by other users. They are now Internal review candidates; the exact PDFs stay
unchanged. Use the [dated clearance and hashes](owner-review-readiness-2026-09-10.md)
and current-guide-selection-v4.json for current status. Earlier four exact
acceptances remain; the separate prototype is outside this clearance.
Other-user feedback and third-party approval are still pending. Earlier
generation-time draft/pending wording below is historical for these versions.

## Implemented Owner Corrections - 2026-09-10

Use the [correction review and plain v4 pair](owner-corrections-2026-09-10.md)
for current review: ten revised guides, 22 guides total, previous 48 pages and
current 47 pages. USR-04 v6 is disable-login only and requires a new review.
Earlier exact acceptances remain. The field-to-result prototype is separate.
Testing completed is the preferred future QA label; no Awaiting approval
state or application status change is included. Older sections below are history.

## Subsequent Owner Feedback

The [owner review TODOs](owner-review-todos-2026-09-10.md) record the latest
assessment: generally suitable working drafts with the listed corrections,
and **USR-04 v5 explicitly not accepted**. The frozen bundle/selection manifests
below record generation-time status. This dated feedback supersedes their
pending-review wording for USR-04, without changing any PDF or earlier exact
acceptance. The tentative QA-status idea does not revoke AST-04 v6 acceptance.

The owner accepted AC-01 v9, AC-02 v4, AST-03 v15, and AST-04 v6 from the
latest delivered batch. These exact PDFs are now internally accepted selections.
No individual PDF was renamed, regenerated, or edited. The existing `-draft`
filenames and printed generation labels remain provenance; this dated decision
records their current acceptance. No third-party or physical/user test is claimed.

## Current Scrolling Comparison - Plain v2 Pair

The owner clarified that review needs two plain concatenated PDFs: previous
versions and current versions, with no added contents, dividers, or bookmarks.
Use these in place of the earlier v1 presentation:

- [Previous guide set](../../../../resources/manuals/operator-guides/review-rounds/2026-09-10/Handleidingen-reviewbundel-v2-vorig.pdf):
  the 22 selected pre-audit baseline versions, including WF-01 v10.
- [Current guide set](../../../../resources/manuals/operator-guides/review-rounds/2026-09-10/Handleidingen-reviewbundel-v2-huidig.pdf):
  the same 22 guides in their current versions, including WF-01 v12.

Each PDF contains 48 original guide pages, in the same order with matching
guide start pages. CMP-04 v6 is unchanged in both. Rejected pilots are excluded.
The [v2 manifest](../../../../resources/manuals/operator-guides/review-rounds/2026-09-10/Handleidingen-reviewbundel-v2-manifest.json)
records all source versions/hashes, page positions and render-equality checks.
Acceptance decisions and all individual guide files remain unchanged.

## Earlier v1 Bundle - Retained History

[Handleidingen reviewbundel v1](../../../../resources/manuals/operator-guides/review-rounds/2026-09-10/Handleidingen-reviewbundel-v1.pdf)
contains all 22 current guides: 48 unchanged content pages plus one clickable
contents page, with 23 bookmarks. The four accepted versions are marked;
WF-01 v12 retains direction approval and all other exact-version reviews stay open.
No rejected pilot, retired guide, or ungenerated planned guide is included.

The [selection manifest](../../../../resources/manuals/operator-guides/review-rounds/2026-09-10/current-guide-selection-v1.json)
records every exact source version, path, hash and status. The
[bundle manifest](../../../../resources/manuals/operator-guides/review-rounds/2026-09-10/Handleidingen-reviewbundel-v1-manifest.json)
maps every guide to its bundle pages and records preservation validation.

The [completed validation](../../../../resources/manuals/operator-guides/review-rounds/2026-09-10/Handleidingen-reviewbundel-v1-validation.json)
confirms all 48 guide pages match their originals pixel-for-pixel at 96 dpi,
with identical content streams/page boxes and correct links/bookmarks. The new
contents page was visually inspected. Earlier PDF and archive hashes also pass.

## Exact Owner Decisions

| Guide | Version | Decision | SHA-256 |
| --- | --- | --- | --- |
| [AC-01](../guides/AC-01.md) | [v9](AC-01-v9.md) | Accepted by owner, 2026-09-10 | `c9f77eaf5461cb952036eb9ffa9400da0295a9171534f5db3074b574b8fc6785` |
| [AC-02](../guides/AC-02.md) | [v4](AC-02-v4.md) | Accepted by owner, 2026-09-10 | `923f185a1b0b594083b507c630c1b54783e50d13fe22a04a79d3b8e5f794f12d` |
| [AST-03](../guides/AST-03.md) | [v15](AST-03-v15.md) | Accepted by owner, 2026-09-10 | `5e838b9e57e868f81c2b7920ea5e4d76cde487a39fb406736912f7082ebe875d` |
| [AST-04](../guides/AST-04.md) | [v6](AST-04-v6.md) | Accepted by owner, 2026-09-10 | `de991dfcbc70b853f95135116e240e426d5582962accef6c3a26d7b40924b8c7` |

## Preservation And Continuation

The frozen 2026-09-08 checkpoint, prior accepted/draft package manifests, rejected
pilots, candidate-generation manifests and source ZIPs remain historical records.
This dated selection supersedes their pending state for only these four versions.
It does not promote the other candidates or republish the application package.
The live runtime registry updates AC-02 and AST-04 to Internal review candidate;
their frozen source copies remain available to reproduce earlier versions.
Use the unchanged individual files in this bundle when commenting on a guide.
Any later visible change needs a new guide version; any later bundle uses the
same `Handleidingen-reviewbundel-vN` stem and a new integer version; the plain
comparison pair uses `-vorig.pdf` and `-huidig.pdf` suffixes.

Reproduce into an unused output version with `scripts/manuals/build-guide-review-bundle.py`
and `--selection` pointing to the selection manifest. Existing bundles are not overwritten.
