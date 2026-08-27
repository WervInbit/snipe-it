# Session Init - 2026-07-21

## Context

- Reinitialized on `AGENTS.md`, `PROGRESS.md`, `docs/fork-notes.md`, the operator-guide planning/design documents, the Affinity research report, and the current proof-output folders.
- Current focus is the laminated A4 operator-guide system and complete generated base guides.
- Review target: determine whether the current generated workflow is producing a stable reusable system or continuing to create guide-specific layout drift. Affinity is deferred.

## Current Constraints

- Use `https://snipe.inbit/` for current application screenshots; do not use `dev.inbit` in guide content.
- Use the existing mobile camera/QR screenshot for camera-scanning visuals because the current laptop camera path is not representative.
- Treat generated PDFs/SVGs as the active base-guide format. Do not prepare Affinity files until the active set is confirmed or explicitly green-lit.
- Keep operational warnings attached to the first relevant step and keep guide references identifiable by code, icon, and family color.

## Immediate Work

- Inspect the latest AC-01, SC-01, AST-01, and remaining initial-guide PDFs visually.
- Compare them against the design foundation, planning decisions, and research recommendations.
- Report the production-method strengths, failure modes, and recommended next implementation sequence before creating further guides.

## Reinitialization Findings

- The reusable design rules are substantially complete, but they are not enforced by a production gate.
- The latest AC-01 and SC-01 PDFs each export an unintended blank second page.
- Generated body and caption type is below the earlier research recommendation, but the user accepted the current AC-01/SC-01 visual scale as the project baseline rather than a strict external point-size target.
- AC-01, SC-01, and AST-01 are useful design pilots; the remaining v2 batch is not operator-ready because evidence was placed after layout decisions and several required states are missing.
- SC-01 and AST-01 need a clear ownership boundary before either becomes a template reference.
- The next reliable method is complete per-guide content/evidence packets, generated guide-by-guide implementation, visual/PDF/print QA, and explicit base approval. Affinity is outside the active sequence.

## Consolidation And First Fixes

- User superseded the earlier Affinity-first recommendation: generated bases are now the active method, and Affinity is deferred until the entire active guide set is confirmed or explicitly green-lit.
- Added `docs/manuals/operator-guides/` as the current project entry point, with shared rules, decisions, inventory, statuses, and nine active guide specifications.
- Preserved all older files and added status banners so historical guidance no longer competes with current instructions.
- Preserved the existing AC-01 v5 and SC-01 v6 designs while correcting their unintended blank second PDF pages.
- Poppler verification confirms both regenerated PDFs contain one A4 page; rendered pages retain the previous appearance.

## Complete Generated Review Set

- Consolidated the active scope to 12 guides; AST-01 is absorbed by SC-01 and CMP-03 is absorbed by CMP-02.
- Generated 12 individual draft guides across 13 A4 pages under `C:\Users\Gebruiker\Documents\snipe-it manuals\layout-proofs\2026-07-21-revised-guide-set`.
- Added one data-driven generator, a read-only evidence capture harness, a combined review PDF, rendered page proofs, a manifest, and an evidence-gap summary.
- Visually inspected every rendered page and corrected shared crop, title, marker, and footer defects before recording the set.
- Kept seven unavailable end-state/form captures as explicit evidence gaps. No guide was silently promoted to `Base approved`, and Affinity remains deferred.
- The first combined package was rejected during review because it over-normalized already-tested guides. A corrected v2 package locks AC-01 to v6, restores SC-01's asymmetric mobile-first layout as v10, converts AST-02 to a compact route list, and applies true corner-overlap screenshot markers to the remaining drafts.
- Final v2 checks: 13 unencrypted A4 pages, zero `dev.inbit` references across generated HTML, valid JavaScript syntax for both active generators, and matching SHA-256 hashes for the external and repo-local combined PDFs.
