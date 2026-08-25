# CAT-02 Modelspecificatie opbouwen

Status: Planned specification; workflow behavior verified, evidence pending.

## Maintenance Metadata

- Family: `CAT`.
- Type: Extended administration task.
- Current version: planned.
- Expected page model: Five pages with continuous steps.
- Layout recipe: to be assigned after evidence capture; likely
  `extended-admin-flow`.
- Generator: `scripts/manuals/generate-catalog-guide-review.mjs` after CAT-01.

## Purpose

Build the specification for one exact model number using direct attributes and
expected component templates, while preserving calculated-spec precedence.

## Audience And Context

- Role: Admin / Superadmin.
- Needed: A verified model number, approved attribute/component definitions,
  and authoritative specification evidence.
- Prerequisites: `CAT-00` and `CAT-01`.

## Planned Pages

1. Open `Edit Spec` from the exact model-number row and verify the selected
   model number in the page header/selector.
2. Search and add direct attributes; enter datatype-appropriate values and
   explain which values are locked by component-derived specifications.
3. Add expected components; select the reusable definition, quantity, required
   baseline, and display order.
4. Review derived attributes, expected child structure, and hierarchy-overlap
   warnings without double-counting parent and child values.
5. Save and verify the visible specification and expected-component roster;
   route missing definitions to CAT-03 or CAT-04.

## Mandatory Decisions

- Direct attribute: stable intrinsic fact about the variant.
- Expected component: separate, replaceable, countable, or inspectable part.
- An expected template describes the baseline and does not create a tracked
  physical component.
- `resolves_to_spec` component values are authoritative for that attribute and
  prevent a competing manual model/asset value.
- Numeric contributions sum by quantity; enum/text keep distinct values; bool
  is true when any contributor is true.

## Completion

`Klaar als`: the exact model number has a verified, non-duplicated specification
and expected component baseline with no unresolved overlap warning.

## Related Guides

- `CAT-00 Catalogus begrijpen`.
- `CAT-03 Attributen beheren`.
- `CAT-04 Componentdefinities beheren`.
- `CAT-06 Catalogus controleren en bronnen`.
- `AST-03 Asset registreren en labelen`.
