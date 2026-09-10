# Operator Guide Maintenance And Change Impact

## Owner Clearance For User Review - 2026-09-10

The owner cleared the ten current changed versions in the v5 pair for review
by other users. They are now Internal review candidates; the exact PDFs stay
unchanged. Use the [dated clearance and hashes](reviews/owner-review-readiness-2026-09-10.md)
and current-guide-selection-v4.json for current status. Earlier four exact
acceptances remain; the separate prototype is outside this clearance.
Other-user feedback and third-party approval are still pending. Earlier
generation-time draft/pending wording below is historical for these versions.

The [all-guide focused comparison](reviews/guide-set-comparison-2026-09-08.md)
is a separate candidate set. Preserve older renderers and retained PDFs. The
new runner writes to ignored output; `retain-guide-followups.py` freezes an
exact reviewed build and refuses replacements. Do not rerun retention over
existing artifacts to make a correction: use a later version after delivery.

Keep local fit repairs auditable. This round preserves page and image counts,
and records source/crop replacements and AST-03/05 padding repairs separately.
An unchanged screenshot count does not prove identical content or legibility.

Status: authoritative workflow for changing one guide, a family, or the whole
guide set after testing or stakeholder feedback.

This contract makes later changes to features, wording, colors, icons,
screenshots, roles, or layouts traceable and repeatable. It applies during
internal testing, user testing, management review, and later application
changes.

## Source Ownership

| Concern | Primary source | Required secondary updates |
| --- | --- | --- |
| Guide scope, steps, warnings, help, and completion | `guides/<CODE>.md` | Review record, generator data, affected references |
| Current version, layout, generator, and artifact | `registry.md` | Guide metadata, decision log, runtime registry when status/name changes |
| Base page and step structure | `layouts.md` | Components contract, recipe implementation, affected guide metadata |
| Colors, typography, badges, icons, references, focus marks, QR geometry | `components.md` and shared tokens | Component tests, visual proof, all affected guide versions |
| Screenshot source and reuse | `screenshots.md` | Guide evidence manifest, generator crop/target data |
| Portable screenshot/PDF bytes and checksums | `resources/manuals/operator-guides/` manifests | Registry, screenshot catalog, accepted-artifact links |
| Cross-guide policy or ownership boundary | `decisions.md` | Every affected specification and reference |
| Version feedback and exact correction | `reviews/<CODE>-vN.md` | Promote reusable feedback to the appropriate shared source |
| Current creation stage and environment mapping | `HANDOFF.md` | Registry, inventory, TODO, and progress checkpoint |

## Change Classes And Impact

| Change | Normal impact scope | Required action |
| --- | --- | --- |
| Global color, font, badge, icon, focus, footer, or QR rule | Every guide using that component | Change shared source; produce an affected-guide list; create new versions for every visibly changed artifact; run component and full-page QA. |
| Family color, marker, terminology, or icon | Every guide and reference in that family | Update family contract and all incoming references; regenerate and review affected guides. |
| Layout recipe behavior | Every guide assigned to that recipe | Update `layouts.md`, implementation, tests, and each assigned guide version. Do not alter accepted artifacts in place. |
| One guide's wording, step, warning, help, or role | That guide plus guides that reference or depend on it | Update the specification first, then generator, references, review record, and version. |
| Application feature or navigation change | Every guide using the changed state | Search evidence uses in `screenshots.md`; recapture once per materially changed state; update all crops, targets, captions, and affected versions. |
| Screenshot-only freshness replacement with unchanged UI | Every guide using the canonical source ID | Replace or version the canonical evidence deliberately; visually compare every current use before regeneration. |
| Management or operational policy change | Every guide implementing that policy | Record the decision, update role/needed/stop/help language, and request process-owner review. |
| Small alignment or focus correction | Exact guide version first; shared scope if reusable | Record in the version review. Promote repeated corrections to components or a recipe and add a regression check. |

## Versioning Rule

Any visible, instructional, behavioral, evidence, or review-status change to a
generated guide creates a new integer version. Do not overwrite an internally
accepted or third-party-approved artifact.

Recording an owner decision about an unchanged existing PDF does not regenerate
or renumber that PDF. Record the exact version/hash, reviewer and date in the
review ledger and current selection. A changed printed review label or other
visible PDF change does require a new integer version.

Examples:

- changing a focus circle in SC-01 v10 produces an SC-01 v11 draft;
- changing the global USR family color produces new draft versions of every
  generated USR guide, even when their words do not change;
- replacing a screenshot without changing its state still produces new guide
  versions when the printed pixels change;
- correcting only an internal path or non-rendered comment does not require a
  guide version change, but the tooling change is logged.

## Frozen Review Rounds

At the owner's request, the [2026-09-08 baseline](reviews/baseline-2026-09-08.md)
separates the existing guide work from the
[new-model audit revision round](reviews/audit-revision-round-2026-09-08.md).
Preserve checkpoint PDFs, source archive, manifest, and acceptance states.
This includes the current unaccepted drafts; they are comparison points, not
new approvals. Later candidates always receive a new unused integer version.

Every candidate identifies its round, frozen comparison version, accepted
predecessor if any, exact changes, benefits, drawbacks, and review decision.
Keep a visible round separator in review indexes and combined review packages.
Do not modify an existing PDF to add that separator. Keep model provenance in
review documentation rather than operator task instructions.

The audit does not supersede accepted policy. A proposed revision may be
accepted, rejected, or returned for changes independently of other proposals.
Retain rejected candidates and their version numbers. Restore only affected
source or active-selection references after comparison; do not reset the whole
working tree or overwrite unrelated changes to undo a guide proposal.

## Preserve Instructional Support During Audit Revisions

The owner [rejected the first audit pilot direction](reviews/2026-09-08-pilot-direction-feedback.md).
Start later revisions from the frozen baseline and repair one identified
problem at a time. Before generation, inventory the baseline's steps,
screenshots and recognition purposes, captions, hints, help, completion, and
handoffs. Account for each in the comparison; do not remove useful support or
shrink screen context to achieve a preferred font size, grid, or white space.
Use available page width and preserve task-dependent layouts and page models.
Record any necessary tradeoff explicitly. Geometry checks do not establish
instructional completeness or first-time-user usability.

## Change Workflow

1. Record the feedback with guide code, current version, page, step, image
   label, and reporter where applicable.
2. Classify it as global, family, recipe, guide, evidence, policy, or
   version-specific.
3. Use `registry.md` and `screenshots.md` to produce the complete impact list
   before editing a generator.
4. Update the narrowest authoritative document first.
5. Promote reusable feedback to `components.md`, `layouts.md`, or
   `system.md`; do not leave a shared rule only inside one generator.
6. Increment every visibly affected guide version and create its review record.
7. Change shared code or guide generator data without modifying historical
   output folders.
8. Generate focused proofs first. Generate a batch only after focused QA.
9. Run automated geometry, text, page-count, evidence, and reference checks.
10. Render every page and review it at full page and actual A4 size.
11. Record the exact review decision. Acceptance applies only to that version.
12. Refresh the internal review package only after the affected versions have
    regained `Internal review candidate` status.
13. Update `HANDOFF.md` when the current creation stage, next action, published
    artifact state, or environment dependency changes.

## Impact Search Checklist

Before a cross-guide change, search for:

- the guide code and full title in specifications, registry, decisions,
  reviews, generators, and related-guide chips;
- every affected evidence source ID in `screenshots.md` and generator data;
- the family token, icon, or component helper in all generators;
- guides assigned to the changed layout recipe;
- hard-coded colors, badge geometry, labels, titles, or reference widths that
  bypass shared components;
- accepted generator snapshots that must remain immutable.

## Review Record Additions

Every new review record should include:

- previous version and reason for change;
- feedback source: internal review, floor test, user test, management, or
  application change;
- impact classification and affected-guide list;
- exact content, evidence, layout, component, or code changes;
- promoted shared rules and regression tests;
- remaining open questions;
- PDF page count and size;
- text and stale-label checks;
- geometry and reference checks;
- full-page and actual-size visual review;
- decision and reviewer.

## Batch Change Safety

Global changes are never considered complete after checking one sample page.
Use one representative proof while developing the component, then regenerate
and inspect every affected guide because text lengths, screenshot ratios,
page density, help counts, and reference rows differ.

If a global change causes one guide to overflow, do not silently shrink all
text or images. Adjust that guide within its registered recipe or create a
documented recipe exception. The accepted reading order and task meaning take
priority over visual uniformity.

## Application Change Trigger

When the application changes, the feature owner should identify changed pages,
controls, labels, roles, and end states. Guide maintenance then maps those
changes through canonical evidence IDs. If the controlled capture environment
does not match the intended release UI, affected guides remain working drafts
until representative evidence is available or the difference is explicitly
accepted.


## Frozen Owner Corrections - 2026-09-10

Use the final current-guide-selection-v3.json with the plain v4 comparison
pair. The eight primary corrections and two dependent USR reference versions
are unaccepted. The v3 bundle pair remains an intermediate preserved assembly.
Do not reuse an integer after delivery or freeze. The generator refuses to
replace retained candidates; reproduce archived inputs in a separate workspace,
and create the next integer for visible changes. Restore prior selections by
exact path and hash, without deleting newer versions.
