# Form-Control Evidence Recapture - 2026-09-03

Status: replacement evidence captured; guide regeneration remains pending.

## Trigger

The shared Bootstrap checkbox/radio layout was corrected after older guide
captures showed 1.8em controls colliding with adjacent label text. Historical
source files remain unchanged so exact guide versions stay reproducible.

## Replacement Sources

| Existing source | Replacement source | Next guide use |
| --- | --- | --- |
| `CAT-ATTRIBUTE-CREATE-IDENTITY-DESKTOP-01` | `CAT-ATTRIBUTE-CREATE-IDENTITY-DESKTOP-02` | CAT-03 2A and 3A |
| `CAT-ATTRIBUTE-CONSTRAINTS-NUMERIC-DESKTOP-01` | `CAT-ATTRIBUTE-CONSTRAINTS-NUMERIC-DESKTOP-02` | CAT-03 4A |
| `CAT-ATTRIBUTE-OPTIONS-ENUM-DESKTOP-01` | `CAT-ATTRIBUTE-OPTIONS-ENUM-DESKTOP-02` | CAT-03 4B |
| `CAT-ATTRIBUTE-SAVE-DESKTOP-01` | `CAT-ATTRIBUTE-SAVE-DESKTOP-02` | CAT-03 5A |
| `CAT-COMPONENT-DEFINITION-IDENTITY-DESKTOP-01` | `CAT-COMPONENT-DEFINITION-IDENTITY-DESKTOP-02` | CAT-04 2A |
| `CAT-COMPONENT-DEFINITION-CHILDREN-DESKTOP-01` | `CAT-COMPONENT-DEFINITION-CHILDREN-DESKTOP-02` | CAT-04 3A |
| `CAT-COMPONENT-DEFINITION-CONTRIBUTIONS-DESKTOP-01` | `CAT-COMPONENT-DEFINITION-CONTRIBUTIONS-DESKTOP-02` | CAT-04 4A |
| `CAT-COMPONENT-DEFINITION-OVERLAP-DESKTOP-01` | `CAT-COMPONENT-DEFINITION-OVERLAP-DESKTOP-02` | CAT-04 5A |
| `CAT-COMPONENT-DEFINITION-SAVE-DESKTOP-01` | `CAT-COMPONENT-DEFINITION-SAVE-DESKTOP-02` | CAT-04 5B |
| `CMP-NEW-DEFINITION-MOBILE-03` | `CMP-NEW-DEFINITION-MOBILE-04` | CMP-02 2A and 3A |
| `CMP-NEW-CUSTOM-MOBILE-03` | `CMP-NEW-CUSTOM-MOBILE-04` | CMP-02 2B |

## Capture Method

- Desktop catalogue sources were recaptured at 1365 x 900 through
  `capture-catalog-guide-evidence.mjs` against the controlled development
  environment. Forms were populated without submission; read-only component
  definition records were not changed.
- Mobile component alternatives were recaptured at their existing 465 x 930
  and 480 x 960 source sizes. The definition/custom choice, example serial,
  condition, and warning state were prepared without submitting the form.
- Source images remain unannotated. Crops and focus marks still belong to the
  generators.

## Audit Boundary

The complete catalogue capture was rerun before selecting replacements.
Model lists, model detail, model specification, model-number lifecycle, and
saved-result evidence were byte-identical or did not expose an affected
checkbox/radio label pair. They were not duplicated.

CMP install and tray evidence does not show a colliding label in its registered
state, so it remains current. User, workflow-execution, asset-registration, and
physical-label evidence was visually checked only where it contained radio or
checkbox controls; no replacement was promoted from this bug report.

No canonical evidence currently exists for workflow-profile administration,
workflow-item administration, custom-fieldset requirements, or work-order
visibility. Those future captures must use the corrected interface when their
guides are created.

## Required Follow-Up

1. Create a new CAT-03 draft version using the four `-02` attribute sources.
2. Create a new CAT-04 draft version using the five `-02` component-definition
   sources.
3. Create a new CMP-02 draft version using the two `-04` mobile sources.
4. Remeasure every focus rectangle or circle against the new source pixels.
5. Render and visually inspect all affected pages before exact-version review.
