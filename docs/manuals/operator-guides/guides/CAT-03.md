# CAT-03 Attributen beheren

Status: Working draft v1; workflow and evidence verified, exact-version review pending.

## Maintenance Metadata

- Family: `CAT`.
- Type: Extended administration task.
- Current version: draft v1.
- Expected page model: Five pages.
- Layout recipe: `extended-admin-flow`.

## Purpose

Reuse, create, or edit one attribute definition so one recurring fact has one
stable meaning, datatype, unit, scope, and set of input rules.

## Audience And Context

- Role: Supervisor with the minimum rights `attributes.view`,
  `attributes.create`, and `attributes.edit` for the route being performed.
  Admin access is not required for ordinary create/edit work.
- Needed: The fact to record, a source or operational agreement for its unit
  and allowed values, and the asset categories that use it.
- Prerequisite: `CAT-00 Catalogus begrijpen`.
- Return route: normally `CAT-02 Modelspecificatie opbouwen` or
  `CAT-04 Componentdefinities beheren`.

## Operator Outcome

One current definition can be selected from the relevant catalogue form
without duplicating an existing meaning or requiring programmer knowledge.

## Page And Step Plan

### Page 1 - Search and reuse first

1. Open `Instellingen > Attributes`.
2. Search by visible label and generated key.
3. Compare the meaning, datatype, unit, category scope, required state,
   asset-override state, and option count.
4. Reuse or edit an existing definition when those items represent the same
   fact. Choose `New Attribute` only when the meaning is genuinely missing.

The guide must state the duplicate-prevention reason next to the search step;
it is not a STOP condition.

### Page 2 - Name the fact and choose the datatype

1. Enter a short human-readable `Label` that describes one fact.
2. Leave `Manual key override` off in the normal route. The application creates
   the stable lowercase `Key` from the label. The guide calls this the
   `systeemnaam (Key)`, not an API or database field.
3. Choose the datatype before Save. Datatype cannot be changed after creation:
   - `Bool`: a yes/no fact, for example whether a touchscreen is present.
   - `Int`: a whole number, for example the number of memory slots.
   - `Decimal`: a measured value that may contain decimals, for example a
     screen diagonal.
   - `Enum`: one value from a controlled list, for example DDR3, DDR4, or DDR5.
   - `Text`: free text only when a controlled list or number is unsuitable.
4. Add `Unit` only when the value needs one, for example `MHz`, `GB`, or `inch`.
   Do not repeat the unit inside every saved value.

### Page 3 - Set scope and allowed behavior

Explain every visible choice in operator language and give a safe default:

- `Category Scope`: select the categories that use the fact. Blank means every
  asset category, so leave it blank only for a genuinely universal fact.
- `Required for category`: use only when a model specification for the scoped
  category may not be saved without this value.
- `Allow asset overrides`: allow only when one physical asset may legitimately
  differ from its model-number baseline. It is not a repair for a wrong model
  number or component definition.
- `Allow custom values (enum only)`: normally off. Enable only when the agreed
  controlled list intentionally permits an exceptional value.
- `Component Spec Display`: `Value labels` shows the contributed values;
  `Component labels` shows readable component labels. It does not choose how
  values are calculated.

### Page 4 - Set constraints or Enum options

The two routes are alternatives, not a sequence every definition follows:

- Numeric route: use `Minimum`, `Maximum`, and `Step` only when they reject
  impossible values. Example: memory-slot count `0` through `8`, step `1`.
- Enum route: add one stable machine value and one readable label per option,
  then set the intended order. Example values `ddr4` / `ddr5` with labels
  `DDR4` / `DDR5`.
- Leave `Regex` blank in the ordinary Supervisor route unless an agreed pattern
  has been supplied by the system owner. The guide does not teach regular
  expressions.

### Page 5 - Save, verify, and return

1. Review label, generated key, datatype, unit, scope, behavior, constraints,
   and options before choosing Save/Create.
2. Verify the resulting list row: label, key, datatype, active status,
   categories, required state, asset-override state, and option count.
3. State that editing a reused definition affects every model number,
   component definition, physical asset override, or workflow that uses it.
4. Route activation/deactivation, removal of saved options, and deletion to
   `CAT-05 Varianten en lifecycle beheren`.
5. Return to the guide that required the definition.

## Mandatory Decisions

- One key keeps one meaning and one unit.
- Datatype is selected before the first Save and is immutable afterwards.
- An attribute describes one reusable fact. A physical part type belongs in a
  component definition.
- Category Scope, Required, and asset override are deliberate behavior, not
  defaults to enable without a reason.
- Existing Enum values are not renamed or removed in this ordinary route.

## Exclusions

- Entering model-number values, component values, asset overrides, or workflow
  expected values.
- Admin lifecycle actions: hide, unhide, deactivate, delete, or remove saved
  options/relationships.
- Tutorials on APIs, databases, source code, or regular-expression syntax.

## Completion

`Klaar als`: de attribuutdefinitie heeft een duidelijke betekenis, kan zonder
duplicaat worden hergebruikt en accepteert alleen de bedoelde waarden.

## Related Guides

- `CAT-00 Catalogus begrijpen`.
- `CAT-02 Modelspecificatie opbouwen`.
- `CAT-04 Componentdefinities beheren`.
- `CAT-05 Varianten en lifecycle beheren`.
- `CAT-06 Catalogus controleren en bronnen`.

## Evidence Manifest

| Label | Source ID | Purpose |
| --- | --- | --- |
| 1A/1B | `CAT-ATTRIBUTE-ENTRY-DESKTOP-01` | Find Attributes from Settings, search the list, and compare the existing result before creating. |
| 2A/3A | `CAT-ATTRIBUTE-CREATE-IDENTITY-DESKTOP-01` | Show the unsaved identity, datatype, unit, scope, and behavior controls. |
| 4A | `CAT-ATTRIBUTE-CONSTRAINTS-NUMERIC-DESKTOP-01` | Show the numeric minimum, maximum, and step alternative. |
| 4B | `CAT-ATTRIBUTE-OPTIONS-ENUM-DESKTOP-01` | Show ordered Enum Value and Label pairs as the other input alternative. |
| 5A | `CAT-ATTRIBUTE-SAVE-DESKTOP-01` | Review the complete unsaved form and Create control. |
| 5B | `CAT-ATTRIBUTE-RESULT-DESKTOP-01` | Verify the expected resulting list-row fields without changing the server. |

## Validation Notes

- Steps remain 1-5 across five A4 pages; the two page-4 input routes are
  explicit alternatives.
- Source forms were not submitted. The result row is a screenshot-only DOM
  example of the implemented list shape.
- Operator copy uses `systeemnaam (Key)` and explains every behavior control
  without requiring API, database, or regular-expression knowledge.
- Lifecycle changes remain outside this ordinary Supervisor route.
