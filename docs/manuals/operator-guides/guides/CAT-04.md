# CAT-04 Componentdefinities beheren

Status: Working draft v1; workflow and evidence verified, exact-version review pending.

## Maintenance Metadata

- Family: `CAT`.
- Type: Extended administration task.
- Current version: draft v1.
- Expected page model: Six pages.
- Layout recipe: `extended-admin-flow`.

## Purpose

Reuse, create, or edit one reusable component definition, including its
identity, optional expected parts, and attribute contributions.

## Audience And Context

- Role: Supervisor with the minimum right
  `components.manage_definitions`. Admin access is not required for ordinary
  create/edit work.
- Needed: A verified physical-part type, its identifying source information,
  any existing component definitions for expected parts, and any existing attribute
  definitions that describe the part.
- Prerequisite: `CAT-00 Catalogus begrijpen`; use
  `CAT-03 Attributen beheren` first only when a reusable fact is missing.
- Return route: normally `CAT-02 Modelspecificatie opbouwen` or a CMP guide.

## Operator Outcome

One reusable component type can be selected in a model-number baseline or a
physical-component workflow without creating a duplicate or confusing the
definition with one tagged physical part.

## Page And Step Plan

### Page 1 - Search and reuse first

1. Open `Instellingen > Component Definitions`.
2. Search name, part code, model number, category, and manufacturer.
3. Compare the matching row before choosing `Create New`:
   - `Instances` counts physical component records based on the definition.
   - `Templates` counts model-number baselines that expect the definition.
4. Reuse or edit the existing definition when it describes the same physical
   part type. Use an attribute definition instead when the subject is only one
   recurring fact and not a part.

### Page 2 - Enter the reusable identity

Explain the visible fields in operator language:

- `Name`: the recognizable reusable part type; never an Inbit tag or one
  physical serial number.
- `Part Code`: a verified supplier/manufacturer part code when present.
- `Model Number`: the verified component model identifier when present.
- `Category`: the component category, not the category of the complete asset.
- `Manufacturer`: the actual maker when known; leave blank rather than guess.
- `Specification Summary`: short recognition information that is not already
  represented by reusable attributes.
- `Spec Display Label`: optional readable label used in asset specifications.
  Leave blank to use the generated component label.

`Active` is shown as current state but ordinary lifecycle changes are not part
of this guide.

### Page 3 - Add expected parts when relevant

1. Under `Expected Subcomponents`, search and select an existing `Component
   Definition` for the expected part.
2. Leave `Expected Name` blank to reuse the selected definition name, or enter
   a short recognition name only when operators need a clearer local label.
3. Enter `Quantity` and choose `Required` only when that part is normally
   expected within this component definition.
4. Use optional Notes for recognition or placement context, not as a substitute
   for a reusable attribute.
5. Keep the structure to one supported level: component definition -> expected
   part. Do not create an expected part for a descriptive fact.

### Page 4 - Add attribute contributions

1. Search and select an existing attribute definition.
2. Enter a value that follows that definition's datatype, unit, options, and
   constraints.
3. `Show as asset spec`: the component value contributes to the displayed
   model and asset specification.
4. `Use in component label`: the value helps build the readable component
   label when the attribute is configured to show component labels.
5. A component definition may use several attributes, and one attribute may be
   reused by several component definitions. Missing meanings route to CAT-03;
   do not create near-duplicate attributes in this form.

### Page 5 - Check overlap and save

1. Review contributions on the current definition and its expected parts
   before Save.
2. If the hierarchy warning names the same attribute on both levels, decide
   which definition level owns the fact. A value from the expected part
   overrides the value from the above definition in the calculated asset
   specification.
3. Remove or correct an unintended duplicate contribution before reusing the
   definition. This is an amber correction route, not a red STOP.
4. Choose `Create Definition` or `Save Changes` and verify the success state.

### Page 6 - Verify reuse and return

1. Search the saved definition and verify name, category, manufacturer,
   Instances, Templates, and Active state.
2. Return to the original task:
   - `CAT-02 Modelspecificatie opbouwen` to add the definition as an expected
     component on a model-number baseline.
   - `CMP-02 Nieuw component registreren en plaatsen` to create a physical
     component record from the definition.
   - `CAT-05 Varianten en lifecycle beheren` for deactivation or removal of
     saved contributions or expected-part rows.

## Mandatory Decisions

- A component definition is a reusable physical-part type, not one tagged
  component instance.
- Search before create; similar names, part codes, model numbers, categories,
  and manufacturer must be compared together.
- Expected parts are existing component definitions, not free-text facts.
- Attribute contributions reuse CAT-03 definitions and valid values.
- Overlap between definition levels is reviewed deliberately because values
  from expected parts take precedence in calculated asset specifications.

## Current Product Boundary

The data model contains serial-tracking and placement modes, but the current
browser form has no controls for them. This guide must not instruct an operator
to choose those modes. Controller defaults are application behavior, not an
operator step.

There is no browser Delete action for a component definition. Activation,
deactivation, and removal of saved contribution or expected-part rows require lifecycle
rights and belong in CAT-05.

## Exclusions

- Creating or installing a physical component instance.
- Adding the definition to a model-number baseline.
- Creating an attribute definition inline.
- Browser-inaccessible tracking or placement settings.
- Admin lifecycle cleanup or an invented Delete route.

## Completion

`Klaar als`: de herbruikbare componentdefinitie heeft de juiste identiteit,
bijdragen en eventuele verwachte onderdelen en is vindbaar voor hergebruik.

## Related Guides

- `CAT-00 Catalogus begrijpen`.
- `CAT-02 Modelspecificatie opbouwen`.
- `CAT-03 Attributen beheren`.
- `CAT-05 Varianten en lifecycle beheren`.
- `CMP-02 Nieuw component registreren en plaatsen`.

## Evidence Manifest

| Label | Source ID | Purpose |
| --- | --- | --- |
| 1A/1B/6A | `CAT-COMPONENT-DEFINITION-ENTRY-DESKTOP-01` | Find Component Definitions, compare use counts, and verify that the saved definition is reusable. |
| 2A | `CAT-COMPONENT-DEFINITION-IDENTITY-DESKTOP-01` | Explain reusable identity fields with a read-only existing definition. |
| 3A | `CAT-COMPONENT-DEFINITION-CHILDREN-DESKTOP-01` | Show complete Expected Subcomponents rows, quantity, required state, and notes. |
| 4A | `CAT-COMPONENT-DEFINITION-CONTRIBUTIONS-DESKTOP-01` | Show several complete attribute contributions and their display choices. |
| 5A | `CAT-COMPONENT-DEFINITION-OVERLAP-DESKTOP-01` | Show the implemented hierarchy-overlap warning without changing a saved record. |
| 5B | `CAT-COMPONENT-DEFINITION-SAVE-DESKTOP-01` | Show a complete contribution row and Save Changes on the read-only existing definition. |

## Validation Notes

- Steps remain 1-6 across six A4 pages; expected parts and attribute
  contributions retain full-width readable screenshots.
- No component-definition form was submitted. The overlap warning is a
  screenshot-only DOM example of implemented application behavior.
- Operator text describes definition levels and expected parts; native English
  field labels remain visible only where they occur in the application.
- Browser-inaccessible tracking/placement modes and lifecycle cleanup remain
  outside this ordinary Supervisor route.
