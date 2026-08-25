# CAT-04 Componentdefinities beheren

Status: Planned specification; workflow behavior verified, evidence pending.

## Maintenance Metadata

- Family: `CAT`.
- Type: Extended administration task.
- Current version: planned.
- Expected page model: Five pages.
- Layout recipe: to be assigned after evidence capture.

## Purpose

Create or maintain a reusable component definition and its specification
contributions, tracking rules, placement rules, and expected child structure.

## Audience And Context

- Role: Admin / Superadmin with `components.manage_definitions`.
- Needed: Physical part identity, category, tracking requirement, placement,
  attributes, and expected child structure.
- Prerequisite: `CAT-00 Catalogus begrijpen`; CAT-03 when an attribute is missing.

## Planned Pages

1. Search definitions by name/category/manufacturer and compare existing
   templates and instances before creating another definition.
2. Enter name, category, manufacturer, model/part code, summary/display label,
   and active state.
3. Choose serial tracking (`optional`, `required`, `not tracked`) and placement
   (`asset only`, `subcomponent only`, `either`) from the real operational use.
4. Add attribute contributions; explain `resolves_to_spec` and
   `include_in_component_label`, including quantity-based calculated values.
5. Add one-level expected subcomponents, review parent/child overlap warnings,
   save, and verify the definition can be selected in CAT-02.

## Mandatory Decisions

- A definition is a reusable catalogue type, not one physical part.
- A tracked component instance is created/installed later through CMP guides.
- Use `required` serial tracking only when every physical part must carry a
  traceable serial; use `not tracked` only when serial capture has no value.
- Parent and expected child definitions must not both contribute the same
  calculated fact unless the overlap behavior is intentional and reviewed.
- The implemented hierarchy is asset -> component -> subcomponent; do not
  document deeper nesting.

## Completion

`Klaar als`: the active component definition has correct identity, tracking,
placement, attributes, and child structure and is reusable from CAT-02/CMP.

## Related Guides

- `CAT-00 Catalogus begrijpen`.
- `CAT-02 Modelspecificatie opbouwen`.
- `CAT-03 Attributen beheren`.
- `CMP-01 Bestaand component plaatsen`.
- `CMP-02 Nieuw component registreren en plaatsen`.
