# CAT-00 Catalogus begrijpen

Status: Working draft v7; semantic-color and audit correction pass awaiting
exact-version review.

## Maintenance Metadata

- Family: `CAT`.
- Type: Reference chapter and guide router.
- Current version: v7 draft.
- Page model: Eight-page reference chapter with continuous section numbering.
- Layout recipe: `reference-chapter` with `reused-evidence` and
  `two-sided-continuation`.
- Generator: `scripts/manuals/generate-catalog-guide-review.mjs`.
- Artifact root: `output/manuals/proofs/catalog-guide-review/cat-00-v7/`.
- Portable review package:
  `resources/manuals/operator-guides/drafts/CAT-00-catalogus-begrijpen-v7-draft.pdf`.
  Its manifest status is `Unaccepted working draft`.

## Purpose

Give a Supervisor a complete, non-programmer mental model of the catalogue:
which records are reusable definitions, which records identify products and
physical devices, where values are assigned, how expected and placed
components differ, and which source determines the effective asset
specification. Task-guide routing appears only after these relationships are
explained.

CAT-00 explains the model and does not save application data.

## Audience And Context

- Role: Supervisor.
- Needed: catalogue read rights. A product label or manufacturer source is
  needed only when the reader continues into a creation or verification task.
- Prerequisite: `AC-01 Login`.
- The body uses reference sections rather than sequential task steps.
- Screenshots identify real interface locations; diagrams explain
  relationships that cannot be understood from one screen.

## Operator Terminology

- `Basismodel`: product plus generation.
- `Modelnummer`: exact manufacturer variant or SKU beneath a Basismodel.
- `Asset`: one physical device with an Inbit asset tag and serial number.
- `Attribuutdefinitie`: reusable meaning and input contract for one fact,
  including datatype, unit, options, category scope, constraints, and whether
  an asset override is allowed.
- `Attribuutwaarde`: a value that uses an Attribuutdefinitie on a model number,
  component definition, asset override, component instance, or workflow/test.
- `Componentdefinitie`: reusable type of physical component, including
  identity, component attributes, display behavior, and expected child
  structure.
- `Verwachte component`: the model-number baseline linking one component
  definition and quantity to an exact manufacturer variant.
- `Geplaatst component`: the actual physical component in one asset or tray.
- `Asset override`: an explicitly allowed value for one asset when no
  component-derived value owns the same specification.
- `Workflowresultaat`: test, condition, note, or execution evidence. It is not
  a catalogue definition or automatic model specification.

Do not replace these terms with invented umbrella labels such as `eigenschap`,
`onderdeeltype`, `cataloguslaag`, or `catalogusobject` in operator-facing text.

## Section 1 - Complete Catalogue Map

Use a relationship graph rather than separate lanes:

1. Basismodel -> Modelnummer -> Asset is the product identity relationship.
2. Directe attribuutwaarde and Expected Component connect directly to the
   Modelnummer.
3. A direct attribute value uses an Attribuutdefinitie.
4. Expected Component uses a Componentdefinitie.
5. Componentdefinitie uses reusable Attribuutdefinities for its attribute
   contributions.
6. A Placed Component is an instance of Componentdefinitie and belongs to one
   physical Asset. Do not connect it to Expected Component as a mandatory
   sequence.

Semantic color follows the object, not only whether it is a definition or an
instance:

- purple represents catalogue/model and attribute structures;
- amber represents Componentdefinitie, Expected Component, and Placed
  Component;
- green represents Asset identity and asset-specific state.

Use labels and fill/border treatment to distinguish reusable definitions,
expected baselines, and physical records. The Componentdefinitie-to-Placed
Component connector must read in its actual direction: a definition can be
physically placed as a component; do not label a definition as an instance of
the placed component.

The page explicitly states: a definition describes meaning and form; a value,
expectation, or physical instance uses that definition.

## Section 2 - Basismodel, Model Number, And Asset

The identity chain uses two device examples:

| Basismodel | Exact modelnummer | Fysiek asset |
| --- | --- | --- |
| HP ProBook 450 G8 | 2E9F8EA#ABH | INBIT-HG0042 / S/N 5CD1234ABC |
| Samsung Galaxy A5 | SM-A520F | INBIT-TF0187 / S/N R58M1234ABC |

The operator-facing block heading is `Voorbeelden: elk apparaattype gebruikt
dezelfde structuur`. Layout instructions such as "two examples in three
columns" may never appear in the PDF.

One Basismodel can have several exact model numbers. One model number can be
used by many physical assets. Replacing RAM or storage changes the physical
asset and its component state; it does not invent a new manufacturer code.

Visual `2A` uses one wide crop to show the complete Basismodel breadcrumb and
exact model-number row. The old category/manufacturer visual 2B is removed
because it did not help distinguish the three identities.

## Section 3 - Attribute Definition And Attribute Value

An Attribuutdefinitie is the reusable contract. Use the exact labels visible in
the current form and give a concrete example for each:

- `Label`: `Werkgeheugen`;
- `Key`: `werkgeheugen`, normally generated automatically from Label;
- `Datatype`: `Int` for a whole-number capacity;
- `Unit (optional)`: `GB`;
- `Category Scope`: `Laptops, Desktops`;
- `Required for category`: enabled for a required laptop field;
- `Allow asset overrides`: disabled when one asset may not deviate;
- `Allow custom values (enum only)`: disabled when only defined choices apply;
- `Component Spec Display`: `Value labels` for values such as `8 GB`;
- `Constraints`: Minimum 1, Maximum 256, Step 1;
- `Options`: only for Enum, for example DDR3, DDR4, and DDR5.

The visible datatype choices are `Bool`, `Int`, `Decimal`, `Enum`, and `Text`.
Datatype cannot be changed after the definition is created. This lifecycle
rule is presented as a separate note rather than as another form field.

The same definition can be used by a direct model-number value, component
definition contribution, allowed asset override, or workflow/test. These are
different uses of one meaning, not separate definitions for every screen.

Visual `3A` shows the definition list and its name, key, datatype, category,
override, and option columns. Visual `3B` shows a value assigned to that
definition on one exact model number.

## Section 4 - Component Definition, Expected, And Placed

Explain one definition with two possible uses, not three mandatory steps:

1. Componentdefinitie: reusable group of attribute values, for example
   `Werkgeheugen = 8 GB` and `Geheugentype = DDR4` combined as `RAM 8GB DDR4`.
2. Expected Component: use of that definition and a quantity in the
   model-number baseline; this is not yet a unique physical part.
3. Placed Component: use on one physical asset or tray, optionally with tag,
   serial number, status, and condition.

A Componentdefinitie can also supply attribute contributions, decide which
contributions appear in the asset specification, and define one level of
expected subcomponents. A component should normally be modelled when it is
replaceable, traceable, transferable, or separately inspectable.

Visual `4A` shows that the definition list counts both physical instances and
model templates. Visual `4B` shows a definition and quantity attached to a
model number as an expected component.

## Section 5 - How A Model Number Is Built

Use one coherent HP example:

`Exact modelnummer = directe attribuutwaarden + verwachte componenten en hun
meetellende attribuutbijdragen`.

Direct values cover stable facts of the exact manufacturer variant, such as
introduction year, weight, colour, or screen size. Expected
component definitions cover the physical baseline, such as motherboard, RAM,
storage, battery, and their component attributes.

Do not record the same specification both directly and through a component
that is configured to contribute it. Model image and label aid recognition but
do not determine the effective values.

Visual `5A` shows direct model-number attributes. Visual `5B` shows expected
components and the derived values visible on the same Edit Spec page.

## Section 6 - Asset Reality Versus Model Baseline

The model number remains the factory variant while one physical asset can
change:

- an expected component can remain visible as `Assumed` without being a
  separately tagged physical component;
- a registered or changed component records the actual part in that asset;
- a RAM or storage upgrade keeps the same manufacturer model number;
- an Asset override is used only when the attribute allows it and no
  component-derived value owns the specification;
- workflow results record condition and evidence, not catalogue definitions;
- asset tag and serial number identify the physical asset, not the model
  specification.

Visual `6A` shows tracked 8 GB RAM. Visual `6B` shows 256 GB storage that is
still assumed from the model-number baseline. A separate hypothetical example
explains that replacing 8 GB with 16 GB changes the asset build but not its
manufacturer model number.

## Section 7 - Effective Value Rules

Explain the current implemented precedence without internal field names:

1. A current component contribution configured to show as an asset
   specification wins for the asset.
2. A permitted asset override is used only when no current component
   contribution owns the value.
3. At model-number level, a contributing expected component in the baseline
   wins over a competing direct model-number value. Do not label this source
   simply `Modelnummerbaseline`, because the direct value in rule 4 is also
   part of that baseline.
4. A direct model-number value is the fallback when no contributing component
   supplies the same fact.
5. A component-instance value, when recorded, takes precedence over its
   component-definition default.
6. A child component contribution takes precedence over a duplicate parent
   contribution for the same fact.

Use the RAM example to show that a model baseline of 8 GB and a current tracked
16 GB component still belong to the same exact manufacturer model number; the
asset displays 16 GB from its current component.

## Section 8 - Follow-Up Guides

Only after the conceptual sections, route the reader by object:

| Object or task | Guide |
| --- | --- |
| Basismodel or exact model number | `CAT-01 Model en modelnummer aanmaken` |
| Specification on one model number | `CAT-02 Modelspecificatie opbouwen` |
| Reusable attribute definition | `CAT-03 Attributen beheren` |
| Reusable component definition | `CAT-04 Componentdefinities beheren` |
| Variant/default/lifecycle | `CAT-05 Varianten en lifecycle beheren` |
| Source and verification | `CAT-06 Catalogus controleren en bronnen` |
| One physical asset | `AST-03 Asset registreren en labelen` |

Search existing names, exact codes, attributes, and component definitions
before creating. Do not create a record when the product label and
manufacturer source do not substantiate the value.

## Complete When

The Supervisor can identify the object, distinguish a definition from a value,
explain expected versus placed components, identify the effective-value source,
and choose the complete follow-up guide.

## Related Guides

- CAT-01 Model en modelnummer aanmaken
- CAT-02 Modelspecificatie opbouwen
- CAT-04 Componentdefinities beheren
- CAT-06 Catalogus controleren en bronnen

## Evidence Manifest

| Label | Source ID | Purpose |
| --- | --- | --- |
| 2A | `CAT-MODEL-DETAIL-DESKTOP-01` | Complete Basismodel breadcrumb and exact-number row. |
| 3A | `CAT-ATTRIBUTE-LIST-DESKTOP-01` | Reusable attribute-definition fields and usage columns. |
| 3B/5A | `CAT-MODEL-SPEC-DESKTOP-01` | Direct model-number value on Edit Spec. |
| 4A | `CAT-COMPONENT-DEFINITION-LIST-DESKTOP-01` | Reusable component definitions with instance/template counts. |
| 4B/5B | `CAT-MODEL-SPEC-COMPONENTS-DESKTOP-01` | Expected components and derived values on Edit Spec. |
| 6A/6B | `CMP-INSTALL-RESULT-MOBILE-02` | Actual tracked RAM and assumed storage rows in one asset roster. |

## Validation Notes

- Treat the eight numbered bodies as reference sections, not task steps.
- No internal layout prompt may appear as user-facing copy.
- Every cross-guide reference uses the registered family marker, colour,
  complete code, and full name.
- No screenshot may cut a named identity, field, component row, or caption.
- No operator-facing text may use `eigenschap`, `onderdeeltype`,
  `cataloguslaag`, `catalogusobject`, `resolves_to_spec`, or code-oriented
  relationship names.
- CAT-02 through CAT-06 remain planned guides. Mark those routes as
  `In voorbereiding` in the PDF. CAT-00 v7 remains a working
  draft until this exact PDF is reviewed.
