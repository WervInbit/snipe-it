# CAT-02 Modelspecificatie opbouwen

Status: Unaccepted working draft v1 generated on 2026-09-03.

## Maintenance Metadata

- Family: `CAT`.
- Type: Extended administration task.
- Current version: v1.
- Page model: Six pages with one continuous procedure.
- Layout recipe: `extended-admin-flow`.
- Generator: `scripts/manuals/generate-catalog-guide-review.mjs` with
  `SNIPEIT_GUIDE_FILTER=CAT-02`.
- Output: `resources/manuals/operator-guides/drafts/`
  `CAT-02-modelspecificatie-opbouwen-v1-draft.pdf`.

## Purpose

Build the reusable specification baseline for one exact model number. The
operator decides which facts belong directly to the manufacturer variant and
which facts come from separate expected components, then saves and verifies
the result without recording the same fact twice.

## Audience And Context

- Role: Supervisor.
- Needed: the verified exact model number and an authoritative product
  specification or product source.
- Prerequisite: `CAT-01 Model en modelnummer aanmaken`.
- Orientation when the catalogue structure is unclear:
  `CAT-00 Catalogus begrijpen`.

## Operator Terms

- **Direct attribuut**: a stable fact about the whole exact model-number
  variant. It is not a separate replaceable or countable part. Example:
  `Introductiejaar = 2021`.
- **Verwacht component**: a separate replaceable, countable, or inspectable
  part in the factory baseline. Example: one `RAM 8GB DDR4` component.
- **Afgeleide waarde**: a specification value supplied by an expected
  component. The operator does not enter the same fact directly as well.
- **Baseline**: what an asset with this exact model number is expected to
  contain. Saving a baseline does not create a physical asset, component tag,
  or serial-number record.

## Page And Step Flow

### Page 1 - Openen

1. Open the saved Basismodel from CAT-01, or navigate through
   `Instellingen > Asset modellen`.
2. Compare the Basismodel, complete manufacturer code including suffix, and
   recognition label.
3. Choose `Edit Spec` on the correct exact model-number row.
4. Validate the breadcrumb and `Model Number` selector before changing any
   value.

Do not compensate for a wrong selection by changing the specification. Return
to the correct exact model-number row first.

### Page 2 - Kiezen

1. Decide where each verified fact belongs.
2. Use a direct attribute for a fact of the whole variant.
3. Use an expected component for a separate factory-baseline part.
4. If the reusable meaning is missing, use
   `CAT-03 Attributen beheren` and return.
5. If the reusable part type is missing, use
   `CAT-04 Componentdefinities beheren` and return.

A fact must have one source. For example, do not enter RAM directly when the
expected RAM component already supplies the RAM value.

### Page 3 - Attribuut

1. Search `Available Attributes` for the existing reusable definition.
2. Choose the plus action next to the exact definition.
3. Select the new row under `Selected Attributes`.
4. Enter or select the value in `Attribute Details` using the displayed input
   type.
5. Use `Up` and `Down` only when the display order should change.

A newly added, unsaved row can be removed from the current edit with its red
X. Removing an already-saved row is not taught as a Supervisor action.

### Page 4 - Component

1. Scroll to `Expected Components`.
2. Choose `Add Expected Component`.
3. Select the existing `Catalog Definition`.
4. Enter the expected quantity under `Stks`.
5. Use drag, `Up`, or `Down` only to change display order.

Rows added on this page are required by default in the current interface.
There is no separate required/optional choice in this model-specification
route.

### Page 5 - Controleren

1. Review `Derived attributes` on every expected component.
2. Review any `Expected child structure` shown for that component.
3. If a conflict reports both a manual model value and a component value,
   keep the intended source and remove the new duplicate input before saving.
4. If the wrong component definition was selected, replace it with the
   correct reusable definition.

The component-derived value is used when it conflicts with a direct value.
Removing an already-saved direct value or expected-component row is an Admin
cleanup route planned for `CAT-05 Varianten en lifecycle beheren`.

### Page 6 - Opslaan

1. Recheck the exact model number, direct values, component definitions, and
   quantities.
2. Choose `Opslaan`.
3. Confirm the success message and recheck the selected exact model number.
4. Reopen or scroll through the saved baseline and verify the direct values,
   expected components, quantities, derived values, and child structure.
5. Continue to `AST-03 Asset registreren en labelen` only when a physical
   device must now be registered.

## Permission Boundary

- A Supervisor can add and update direct model-number values and expected
  component rows.
- A Supervisor can remove a row added during the current unsaved edit.
- Removing an already-saved direct value or expected-component row requires
  the Admin cleanup permission and belongs in CAT-05.
- Creating missing reusable definitions belongs in CAT-03 or CAT-04, not in
  this guide.

## Completion

`Klaar als`: the correct exact model number has saved direct values and an
expected component baseline, every fact has one intended source, and the
saved result has been verified.

## Evidence

- `CAT-MODEL-DETAIL-DESKTOP-01`.
- `CAT-MODEL-SPEC-DESKTOP-01`.
- `CAT-MODEL-SPEC-ATTRIBUTE-ADD-DESKTOP-01`.
- `CAT-MODEL-SPEC-EXPECTED-START-DESKTOP-01`.
- `CAT-MODEL-SPEC-EXPECTED-ADD-DESKTOP-01`.
- `CAT-MODEL-SPEC-CONFLICT-DESKTOP-01`.
- `CAT-MODEL-SPEC-SAVE-DESKTOP-01`.
- `CAT-MODEL-SPEC-ROSTER-DESKTOP-01`.
- `CAT-MODEL-SPEC-SAVED-DESKTOP-01`.

## Related Guides

- `CAT-00 Catalogus begrijpen`.
- `CAT-01 Model en modelnummer aanmaken`.
- `CAT-03 Attributen beheren`.
- `CAT-04 Componentdefinities beheren`.
- `CAT-05 Varianten en lifecycle beheren`.
- `CAT-06 Catalogus controleren en bronnen`.
- `AST-03 Asset registreren en labelen`.

## Review Status

CAT-02 v1 is a first unaccepted working draft. It must not be marked approved
until explicit review confirms the wording, focus marks, crop context, and
workflow against the intended Supervisor process.
