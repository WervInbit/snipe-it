# Operator Guide Components

Status: authoritative shared rendering contract for generated guide drafts.

This document defines the reusable visual components and the review-feedback
rules used by guide generators. Guide-specific content remains in
`guides/<CODE>.md`; shared rendering behavior belongs here.

## Implementation Sources

- Shared tokens, guide registry, SVG primitives, layout helpers, and geometry
  checks: `scripts/manuals/lib/guide-system.mjs`.
- Component contract tests: `scripts/manuals/test-guide-system.mjs`.
- Visual regression proof:
  `scripts/manuals/generate-guide-component-proof.mjs`.
- Current migrated guide examples:
  `scripts/manuals/generate-user-account-guide-review.mjs` (`USR-01` v8 and
  `USR-02` v7).

Do not copy a component into a new generator. Import it from the shared module.
Existing generators can be migrated guide by guide as their next reviewed
version is prepared; historical artifacts remain unchanged.

Shared components do not select a page structure. Base layouts and step
patterns are registered in [layouts.md](layouts.md). Until those recipes have
shared implementations, the exact accepted generators remain their reference
implementations.

## Shared Tokens

`GUIDE_TOKENS` is the source of truth for family colors, text colors, line
colors, type sizes, and component dimensions. A guide may choose its own step
heights and screenshot proportions, but it must not redefine common badge,
reference, focus, or completion geometry.

### Semantic Object Colors In Reference Diagrams

Guide-family color still owns the document header, section navigation, and
guide references. A reference diagram may additionally reuse an object
family's established color when that helps readers follow the same object
across definitions, baselines, and physical records:

- component definitions, expected components, and placed components use the
  `CMP` color;
- asset identity and asset-specific or instantiated state use the `AST` color;
- catalogue/model and attribute structures retain the `CAT` color unless a
  narrower object family applies.

Color identifies the object family, not whether a record is reusable or
physical. Distinguish a definition, expected baseline, and physical instance
with explicit labels, icons, border weight, or fill treatment. Do not assign a
different hue to a placed component than to its component definition, or to an
asset-specific value than to the asset it describes.

### Reference Diagram Geometry

Relationship diagrams must remain readable before they become visually dense:

- connector paths may not cross text, labels, badges, or another object;
- connector labels use a dedicated clear gap or an opaque background;
- arrowheads stop outside the destination object's border and point in the
  implemented relationship direction;
- multiplicity is written in plain operator language when it matters;
- a complex relationship is split across pages before lines are routed through
  instructional content.

Diagram generators should record object, text, and connector bounds and fail a
known connector/text intersection. Until that geometry check is implemented,
full-page visual review must treat any such intersection as a blocking defect.

## Guide Registry

`GUIDE_REGISTRY` owns each active guide's code, full Dutch display name,
family, and artifact status. References are created with `guideReference()` so
the header, prerequisite, inline handoff, help route, and footer use the same
name and family identity.

A reference normally contains:

- the family icon;
- the complete guide code;
- the full registered guide name;
- the referenced family's color.

Short labels are allowed only when a guide specification records a genuine
space constraint and the shortened wording remains unambiguous.

When a context-strip item supplies `guideReference()`, the renderer uses the
registered full label by default. Free-form context text may add a prefix or
suffix, but it may not replace the code and registered name. This rule applies
equally to prerequisites, inline route handoffs, help routes, and footer
references. Render these through the shared reference component instead of
manually pairing a family badge with plain text.

Rendered-component QA treats a context reference without its complete
registered label as an error. Guide-specific validation should also reject
plain cross-guide codes in operator instructions when the code represents an
actionable handoff rather than ordinary explanatory text.

## Centering Contract

Circular labels use their circle center as the text `y` coordinate and SVG
`dominant-baseline="central"`. Do not compensate with hand-tuned vertical
offsets. This applies to:

- family icons;
- step numbers;
- screenshot labels;
- help icons;
- guide-reference icons.

Step badges are visibly larger and heavier than image badges. Image badges use
a lighter fill and partly overlap the image's upper-left corner without
covering the named control or caption.

## Focus Contract

Focus marks are annotations, not decoration. A mark must name one target and
derive from measured source-pixel bounds. `normalizeFocusBounds()` adds equal
padding on all sides; hand-positioned asymmetric rings are not acceptable.
The complete focus stroke must remain visible inside the screenshot frame. If
a target continues beyond the visible crop, tighten the target to the visible
controls or revise the crop; do not accept a stroke clipped by the image edge.

Use a focus mark only when the screenshot contains multiple plausible actions
or the target is otherwise easy to miss. A screenshot that already isolates a
single clear state does not need a red mark.

## Flexible Layout Rules

The system is component-based, not a fixed page grid:

- guides can contain different numbers of steps and screenshots;
- alternatives can be stacked, adjacent, or embedded in one step;
- screenshots can use different aspect ratios when context requires it;
- help can contain fewer or more items;
- related guides can contain zero through five entries over at most two rows;
- a guide can be one or two A4 pages.

The reading order remains stable: header, context, steps, help, completion,
references, QR/source footer. Bottom utilities stay anchored near the bottom.

Use the named recipe and pattern IDs from [layouts.md](layouts.md) when a guide
needs one of these variations. Do not recreate an accepted structure as an
unnamed guide-specific grid.

## Shared Components

### Context Strip

Use `drawContextStrip()` for `Rol`, `Nodig`, and `Vooraf`. A guide prerequisite
uses the referenced family's icon, color, code, and name. The prerequisite that
contains a requirement, such as a phone, owns that requirement; downstream
guides reference it instead of repeating the complete prerequisite workflow.

Every context label/value pair must stay inside its assigned column. The
renderer records each column's right boundary and generation fails when either
the label or value crosses it. Shorten copy or change the deliberate column
count; do not hide an overflow by shrinking unrelated text.

### Step And Image Badges

Use `stepBadge()` and `imageBadge()`. Both overlap a corner, but step badges
remain clearly larger. Image labels such as `1A` and `1B` connect alternatives
to one real step; they are not additional sequential steps.

### Guide References

Use `familyBadge()`, `guideChip()`, and `drawRelatedGuideRows()`. The helper
supports up to five registered references in two rows and validates available
text width. References must not be added merely to fill a row.

A guide handoff inside a help tile follows the same identity contract. Render
the referenced family's marker and color together with the complete guide code
and registered name. Do not leave a guide code as unstyled help body text.
Give the handoff its own line and visible clearance from every tile border. If
the standard help height cannot contain it, grow the complete aligned help row
for that guide instead of clipping or overlapping the reference.

### Completion Row

Use `drawCompletionRow()`. The label and outcome text share one true vertical
center. The outcome describes a visible or verifiable end state.

### Inline Warning Hierarchy

Use amber inline warning text for recoverable duplicate, validation, missing
catalog, mismatch, or incomplete-work conditions. Use red `STOP` only when the
operator must halt because continuing creates a meaningful safety, identity,
irreversible-state, or data-integrity risk. A warning must not be promoted to a
stop merely to make it more visible.

Focus rectangles surround the named control with visible padding. Their stroke
must not cross a dropdown label, result count, icon, or other instructional
text.

### QR Area

The standard printed area is 22 mm. A proof placeholder must explicitly say
that the QR still follows. A third-party-approved artifact contains a real,
maintained QR destination or omits the QR.

## Feedback Promotion

Review comments are classified when they are received:

| Scope | Record and implementation |
| --- | --- |
| Global | Update this contract, shared tokens/components, and regression tests. |
| Family | Update the family rule or reusable family layout helper. |
| Guide | Update the guide specification and that guide's generator data. |
| Version | Record the requested adjustment in `reviews/<CODE>-vN.md`. |

A repeated version-level correction is promoted to a guide, family, or global
rule when it represents a reusable expectation. Examples already promoted to
the global contract include true circular-label centering, symmetric focus
padding, full guide names, two-row reference support, and distinct step versus
image marker hierarchy.

When feedback changes a recipe rather than a primitive component, promote it
to `layouts.md` and apply the impact workflow in `maintenance.md` to every
guide assigned to that recipe.

Review notes are not silently discarded after regeneration. Each focused
review record states what changed, what remains open, and which broader rules
were updated. Historical output is immutable; corrections create a new version.

## Artifact Status

Use only these production statuses:

| Status | Meaning |
| --- | --- |
| `Working draft` | Current editable version; evidence or review may still change it. |
| `Internal review candidate` | Internally accepted exact version ready for third-party review. |
| `Third-party approved` | Exact version approved by the external/internal third-party reviewer. |
| `Superseded` | Retained as history but replaced by a newer version. |

No guide is called final or approved solely because the project owner accepts
it for the current set. Until third-party review occurs, an accepted version is
an `Internal review candidate`.

## Regression Checks

Run these before publishing a migrated guide:

```powershell
node scripts/manuals/test-guide-system.mjs
node scripts/manuals/generate-guide-component-proof.mjs
```

For every generated page, also run `inspectRenderedGuideComponents()` and fail
generation on badge-center or reference-text overflow errors. Render the final
PDF to PNG and inspect it at full page and actual A4 scale; geometry checks do
not replace visual review.

Multi-page generators must also fail on title/version-panel overlap, context
column crossing, and any registered component outside the A4 bounds. Check
every final PDF page after rasterization, including pages that reuse the same
header and footer, because one longer title or additional reference row can
break only one page.
