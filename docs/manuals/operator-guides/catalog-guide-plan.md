# CAT Guide Set Information Architecture

Status: current working family plan for the next CAT guide versions. This plan
does not approve an artifact, change a registered guide title, or replace an
exact guide specification.

## Purpose And Ownership

This document decides **which CAT guide owns each concept, decision, and
operator action**. It prevents the overview from becoming a compressed manual
and prevents task guides from teaching the same subject in different ways.

The plan inherits the current contracts in:

- [system.md](system.md) for content, production, and QA rules;
- [components.md](components.md) for colors, references, focus marks, and
  diagram behavior;
- [layouts.md](layouts.md) for page and step structures;
- [maintenance.md](maintenance.md) for versioning and change impact;
- [decisions.md](decisions.md) for accepted direction and open policy;
- [registry.md](registry.md) for exact versions, artifacts, and review state;
- [screenshots.md](screenshots.md) for canonical evidence;
- `docs/v1-operational-role-capability-matrix.md` for deployed minimum rights.

The individual `guides/CAT-*.md` files still own exact wording, numbered steps,
warnings, evidence labels, and completion criteria. Before another CAT artifact
is generated, its specification must be aligned with this family plan. CAT-00
v9 and CAT-01 v5 implement the current aligned pair and remain unaccepted
working drafts. CAT-00 v7/v8 and CAT-01 v3/v4 remain historical review
artifacts.

## Reader And Execution Contract

CAT guides are for people who understand the physical product work but may not
know Snipe-IT, database concepts, or programming terminology.

Every CAT task guide must:

- start from the dashboard or a clearly named handoff from the previous guide;
- use the exact visible interface label for each action;
- explain an unfamiliar choice in ordinary Dutch beside the first use;
- use one consistent worked product through the complete task;
- show the control that saves data and the visible saved result;
- state what the user should do when the required reusable record is missing;
- end with a verifiable outcome and the complete next-guide reference;
- avoid assuming that the reader knows where a menu, tab, selector, or result
  is located;
- avoid internal field names, code terms, database relationships, and generic
  approval language that is not part of the real process.

Rendered operator copy is Dutch. Exact English interface labels may be quoted
when the application still displays them, followed by a short Dutch
explanation of their effect.

CAT administration chapters may exceed two A4 sides. Page count follows the
amount of information needed for independent execution; text and screenshots
must not be compressed to preserve an arbitrary page limit. Multi-page guides
use continuous numbering, a clear `Deel x van y` marker, and an explicit next
page handoff.

## Minimum-Role Boundary

| Work | Minimum role | Guide behavior |
| --- | --- | --- |
| Understand and verify catalogue information | Supervisor | Normal CAT route. |
| Create/edit Basismodellen and model numbers | Supervisor | CAT-01. |
| Add/edit model specifications | Supervisor | CAT-02. |
| Create/edit reusable attribute definitions | Supervisor | CAT-03. |
| Create/edit reusable component definitions | Supervisor | CAT-04. |
| Change defaults/lifecycle or remove saved catalogue rows | Admin | CAT-05 only. |
| Register one physical asset | Supervisor | AST-03, outside CAT. |

Do not print `Superadmin` as the normal CAT role. Use the minimum deployed
foundation role. When an Admin-only control is absent for a Supervisor, the
guide routes to CAT-05 instead of suggesting a workaround.

## Operator Route

The normal new-product route is:

```text
CAT-00 Catalogus begrijpen
    |
    +-- source or exact identity uncertain --> CAT-06
    |
    v
CAT-01 Basismodel and exact model number
    |
    v
CAT-02 Modelspecificatie opbouwen
    |                       |
    | missing attribute     | missing component definition
    v                       v
CAT-03 (then return)     CAT-04 (then return)
    \                       /
     +-------- CAT-02 ------+
                  |
                  v
        AST-03 Asset registreren en labelen
```

CAT-05 is not a normal final step. It is a separate Admin-only maintenance
route used when an existing record must be hidden, deprecated, restored,
changed as the default, or cleaned up.

Workflow-item and workflow-profile configuration are not added as CAT-07,
CAT-08, or CAT-09. They manage workflow objects and should become separate
WF-family administration guides after their own investigation. CAT guides may
reference those guides once they are registered, but must not duplicate their
steps.

## Information Ownership Matrix

| Information or action | Primary owner | Allowed summary elsewhere |
| --- | --- | --- |
| Complete catalogue relationship map | CAT-00 | One-line reminders in task guides. |
| Basismodel, model number, and physical asset distinction | CAT-00 | CAT-01 repeats the decision at creation time. |
| Attribute definition versus attribute value | CAT-00 | CAT-02/03 use the distinction while acting. |
| Component definition, expected baseline, Assumed, and Tracked | CAT-00 | CAT-02/04 repeat only the state needed for their action. |
| Decide direct value versus expected component | CAT-02 | CAT-00 gives one orientation example. |
| Search/reuse/create a Basismodel or exact model number | CAT-01 | CAT-00 only routes to it. |
| Add direct model-number values | CAT-02 | CAT-03 only creates the reusable definition. |
| Add expected components to a model number | CAT-02 | CAT-04 only creates the reusable component definition. |
| Create datatype, unit, scope, options, and constraints | CAT-03 | CAT-00 does not list form fields. |
| Create component identity, contributions, and expected parts | CAT-04 | CAT-00 explains the relationship only. |
| Primary/default, active/deprecated, hide/restore, and saved-row cleanup | CAT-05 | CAT-01 only says the first model number becomes the default automatically. |
| Evidence hierarchy and exact-source checks | CAT-06 | CAT-01/02 use a short `verified source` prerequisite. |
| Physical asset identity, label, and per-asset state | AST-03 and later AST/CMP guides | CAT-00 explains the boundary only. |
| Install, remove, move, or track a physical component | CMP guides | CAT-00 explains Assumed versus Tracked only. |
| Create workflow items, profiles, or product applicability | Future WF administration guides | CAT-03 may state that an attribute can be reused by a workflow. |

If information has a primary owner, another guide links to that owner instead
of reproducing its field table or full procedure.

## Shared Worked Examples

Use the same example identities across CAT guides unless a real screenshot
requires another controlled record:

| Object | Canonical example |
| --- | --- |
| Basismodel | `HP ProBook 450 G8` |
| Exact model number | `2E9F8EA#ABH` |
| Recognition label | `HP ProBook 450 G8 - i5-1135G7 - 8GB - 256GB` |
| Physical asset | `INBIT-HG0042` |
| Serial number | `5CD1234ABC` |
| Direct stable fact | `Schermdiagonaal = 15,6 inch` |
| Component definition | `RAM 8 GB DDR4` |
| Component attributes | `Werkgeheugen = 8 GB`; `Geheugentype = DDR4` |
| Expected component | `1 x RAM 8 GB DDR4` on the exact model number |

CAT-00 may use a second device type, such as a Samsung phone, to show that the
same identity structure is not laptop-specific. Do not silently change codes,
serials, capacities, or labels between pages.

## CAT-00 Catalogus Begrijpen

### Outcome

The reader can distinguish the six core objects and choose the correct task
guide. CAT-00 does not teach every form field or ask the reader to save data.

### Proposed Next Structure

Target: six-page `reference-chapter`. Six pages are a planning target, not a
hard limit.

1. **De catalogus in een beeld**
   - Show the primary identity chain `Basismodel -> Modelnummer -> Asset`.
   - Attach direct attribute values and expected components to the model
     number.
   - Show that a component definition can use several attribute definitions.
   - Show a tracked component as physical asset state, not as another catalogue
     identity.
   - Use definitions, baselines, and physical records as different visual
     treatments without inventing another color family.
2. **Welke identiteit bekijk ik?**
   - Define Basismodel, exact manufacturer model number, and physical asset.
   - Use the HP and phone examples in three aligned columns.
   - Explain that a manufacturer-issued factory SKU can be another model
     number, while RAM/storage replaced later on one device does not change its
     manufacturer code.
3. **Herbruikbare bouwstenen**
   - Explain an attribute definition as reusable meaning plus input rules.
   - Explain a component definition as a reusable physical-part type that may
     combine several attribute values, identity, display information, and
     expected parts.
   - State that one attribute definition can be reused by many component
     definitions and other application features.
   - Do not list the complete CAT-03 or CAT-04 forms.
4. **Verwachting tegenover werkelijkheid**
   - `Expected Component` belongs to the exact model-number baseline.
   - On an asset, an unmaterialized baseline row appears as `Assumed`.
   - A component movement or registration action creates a physical `Tracked`
     record; a generic status change does not.
   - `Removed` represents expected baseline quantity that is no longer merely
     assumed in the original place.
   - Do not imply that every expected component must become individually
     tracked.
5. **Hoe een modelspecificatie ontstaat**
   - Stable intrinsic facts can be direct values on the exact model number.
   - Replaceable, countable, or separately inspectable hardware belongs in
     expected components.
   - Component contributions can determine the displayed asset specification;
     do not enter the same fact again as a competing direct value.
   - Keep precedence to one operational example. The detailed priority ladder
     and implementation terminology do not belong in the orientation guide.
6. **Welke gids heb ik nodig?**
   - Route to CAT-01 through CAT-06, AST-03, and relevant CMP guides by the
     object the reader needs to change.
   - Mark unavailable guides as `In voorbereiding`.
   - End with the neutral comprehension outcome, not an inference about the
     reader's suitability.

### CAT-00 Exclusions

- Complete datatype, constraint, option, component-contribution, or lifecycle
  field tables.
- A developer-facing effective-value algorithm.
- Instructions for creating, editing, or saving records.
- Source-recording claims that the application cannot support.
- A dense graph with crossing arrows or connector labels over text.

## CAT-01 Model En Modelnummer Aanmaken

### Outcome

One correct Basismodel contains one verified exact manufacturer model number,
without creating a duplicate.

### Page Plan

Target: five-page `extended-admin-flow` with continuous steps.

1. **Open and search**
   - Start at the dashboard and show the Settings route.
   - Search product family plus generation in the Basismodel list.
   - Search the complete exact code in the global model-number list or another
     verified global result, not only in one model row.
2. **Choose one of three routes**
   - Exact code exists: reuse and verify it.
   - Basismodel exists but exact code is missing: add the number there.
   - Product plus generation is missing: create the Basismodel, then add the
     exact number.
3. **Create only a missing Basismodel**
   - Name, category, manufacturer, and optional recognition image.
   - The name contains product plus generation, not serial, asset tag, Product
     ID, or factory configuration invented by the operator.
4. **Add the exact manufacturer number**
   - Exact code, normal automatic capitalization, exceptional `Aa` case
     preservation, and readable label.
   - A representative model-number image may help recognition but is not an
     identifier or specification source.
5. **Verify and continue**
   - Verify Basismodel, category, manufacturer, exact code, label, and absence
     of a duplicate.
   - Explain only that the first exact number becomes the system default
     automatically.
   - Route to CAT-02 for specifications or AST-03 when the reusable catalogue
     is already complete.

### CAT-01 Exclusions

- Direct attributes and expected components.
- Manual Primary/default, deprecation, restore, deletion, or cleanup actions.
- Physical serial number, Inbit asset tag, or asset status entry.
- Manufacturer/category administration when the required global record is
  missing. That is an Admin/product-maintenance dependency, not a near-match
  choice.

## CAT-02 Modelspecificatie Opbouwen

### Outcome

The selected exact model number has a non-duplicated baseline made from direct
attribute values and expected component definitions, and the saved preview
matches the intended product.

### Page Plan

Target: six pages. The direct-value/component decision is kept separate from
both input procedures so the guide remains usable without shrinking its
screenshots or field explanations.

1. **Open the correct exact model number**
   - Start from CAT-01's saved Basismodel or show the complete dashboard route.
   - Open `Edit Spec` and validate the Basismodel, exact code, and model-number
     selector before editing.
2. **Choose where each fact belongs**
   - Direct value: stable fact of the manufacturer variant.
   - Expected component: separate, replaceable, countable, or inspectable
     hardware in the factory baseline.
   - Route a missing reusable meaning to CAT-03 and a missing physical-part
     type to CAT-04, then return to this step.
3. **Add direct attributes**
   - Search available definitions, add one, select it, and enter the value in
     the datatype-specific control.
   - Explain reorder behavior and the visible locked/component-owned state.
   - Do not teach removal as a Supervisor action.
4. **Add expected components**
   - Select the catalogue component definition, set quantity, and order rows.
   - Explain that rows are required by default in the current UI; there is no
     separate required/optional choice here.
   - Explain that saving the baseline does not create a tagged physical
     component.
5. **Review derived values and conflicts**
   - Review derived attributes and overlap/conflict messages.
   - Remove duplicate input before saving; do not hide a conflict by entering
     the same fact twice.
   - Route saved-row removal to Admin CAT-05.
6. **Save and verify**
   - Show the save control, success state, and final model-number
     specification/expected-component roster.
   - Route physical registration to AST-03.

### CAT-02 Exclusions

- Creating an attribute or component definition inline.
- Physical component tags, serials, lifecycle, or installation.
- Admin-only removal of an existing direct value or expected-component row.
- Internal names such as `resolves_to_spec` or an operator-selectable
  `aggregation` mode that does not exist in the UI.

## CAT-03 Attributen Beheren

### Outcome

One reusable attribute definition has a unique meaning, correct datatype,
scope, units, constraints, and options and can be reused without duplicating an
existing definition.

### Page Plan

Target: five-page `extended-admin-flow`.

1. **Search and reuse first**
   - Open Attributes from Settings.
   - Search label and key; compare meaning, datatype, unit, and category scope.
   - Create only when no current definition represents the same fact.
2. **Name the fact and choose its datatype**
   - `Bool`: yes/no fact.
   - `Int`: whole number, for example memory capacity in GB.
   - `Decimal`: measured value that can contain decimals.
   - `Enum`: controlled list such as DDR3/DDR4/DDR5.
   - `Text`: genuinely free text.
   - Keep manual key override off in the normal route; the system generates a
     normalized key from the label.
   - State before Save that datatype cannot be changed later.
3. **Set scope and allowed behavior**
   - Unit, Category Scope, Required for category, Allow asset overrides,
     Allow custom values for Enum, and Component Spec Display.
   - Explain each choice with a safe default and one concrete example.
   - `Component Spec Display` chooses value labels versus component labels; it
     does not choose how values are calculated.
4. **Set constraints or Enum options**
   - Use minimum, maximum, and step only where they constrain numeric input.
   - Use ordered value/label pairs for Enum.
   - Leave Regex blank in the ordinary Supervisor route unless an agreed
     pattern is supplied; the guide does not teach programming expressions.
5. **Save, verify, and reuse**
   - Show Save and the resulting list row with label, key, datatype, status,
     categories, required state, override state, and options.
   - Explain that ordinary edits affect every place that reuses the
     definition.
   - Route lifecycle, option removal, and deletion to CAT-05.
   - Route back to CAT-02, CAT-04, or a future WF administration guide.

### CAT-03 Exclusions

- Model-number values, component values, and workflow expected values.
- Admin-only hide/unhide/delete and removal of saved options.
- A tutorial on API keys, regular expressions, or database schemas.

## CAT-04 Componentdefinities Beheren

### Outcome

One reusable component definition has a clear physical-part identity,
appropriate attribute contributions, optional expected parts, and a saved
result that can be selected from CAT-02 or CMP workflows.

### Page Plan

Target: six pages. Do not compress the contribution and expected-part sections
until full-page evidence proves they remain readable.

1. **Search and reuse first**
   - Open Component Definitions from Settings.
   - Search name, part code, model number, category, and manufacturer.
   - Compare the existing definition's template and instance use before
     creating another definition.
   - Use an attribute definition instead when the subject is only one reusable
     fact and not a physical part type.
2. **Enter component identity**
   - Name, Part Code, Model Number, Category, Manufacturer, Specification
     Summary, and optional Spec Display Label.
   - Explain part code versus component model number with the worked example.
   - State that the definition is reusable and is not one tagged physical
     part.
3. **Add expected parts when relevant**
   - Select an existing component definition, its normal quantity, and
     optional recognition name/notes.
   - Keep `Required` selected in the current operational process. Unchecked
     rows are not separately enforced and still affect calculated
     specifications, so optional or per-device varying parts do not belong in
     this expected structure.
   - The current supported hierarchy is one level for the operator guide.
   - Do not add an expected part merely to represent another descriptive fact.
4. **Add attribute contributions**
   - Search an existing attribute definition and enter a datatype-valid value.
   - Explain `Show as asset spec` as: this component value contributes to the
     displayed model and asset specification.
   - Explain `Use in component label` as: this value helps build the readable
     component label.
   - A component definition can use several attribute definitions; each
     attribute definition can be reused by several component definitions.
5. **Resolve overlap and save**
   - Review hierarchy-overlap warnings and remove unintended duplicate
     contributions.
   - Show Save and the resulting definition row/details.
6. **Return to the original task when a sixth page is needed**
   - CAT-02 to add the definition to a model-number baseline.
   - CMP-02 to create and install a new physical record from the definition.
   - CAT-05 for deactivation or removal of saved contributions or expected-part
     rows.

### Current Product Boundary

The data model contains serial-tracking and placement modes, but the current
browser form has no controls for them. CAT-04 must not instruct an operator to
choose `optional`, `required`, `not tracked`, `asset only`, `subcomponent only`,
or `either` until those controls exist and are verified. Current controller
defaults are application behavior, not an operator step.

There is also no browser delete route for a component definition. CAT-05 may
teach activate/deactivate and Admin-only saved-row cleanup, but it must not show
an invented Delete action.

## CAT-05 Varianten En Lifecycle Beheren

### Recommended Scope Correction

CAT-01 already owns adding exact variants. CAT-05 should not repeat that task.
Its next title should be reviewed as `Catalogusstatus en opschoning beheren`,
with the current code retained.

### Outcome

An Admin changes only the intended default/lifecycle/cleanup state, understands
the affected records, and verifies that active catalogue routes remain usable.

### Page Plan

Target: five-page branching Admin guide. The branches are alternatives, not a
sequence that every reader performs.

1. **Choose the object and inspect its use**
   - Identify Basismodel/model number, specification row, attribute definition,
     or component definition.
   - Check current use and determine whether the goal is hide, deactivate,
     deprecate, restore, change default, or remove an unused saved row.
2. **Model-number lifecycle and default**
   - Explain Primary as the system fallback/default, not approval.
   - First number becomes Primary automatically.
   - Primary cannot be deprecated or deleted; making a deprecated number
     Primary restores it.
   - In-use model numbers cannot be deleted.
3. **Model-specification cleanup**
   - Remove an existing direct attribute or expected-component row only after
     reviewing impact.
   - Removing a direct attribute also removes per-asset overrides for that
     definition on assets using the model number.
   - Verify the model specification and an affected asset after cleanup.
4. **Attribute-definition lifecycle**
   - Hide/unhide, remove an obsolete Enum option, or delete only when unused.
   - Do not change the meaning of an in-use key or simulate a datatype change.
5. **Component-definition lifecycle**
   - Activate/deactivate a definition and remove saved attribute contributions
     or expected-part rows only after impact review.
   - Verify selectors, model baselines, and affected component displays.
   - State the current absence of a browser Delete action.

`Kopieer model` limitations belong in a compact CAT-01/CAT-05 note: it copies
the Basismodel form and optionally its base image, not model numbers,
model-number images, direct specification values, or expected components.

## CAT-06 Catalogus Controleren En Bronnen

### Recommended V1 Boundary

Make CAT-06 a verification guide that can work now. Do not claim that a source
or review result is stored in Snipe-IT while no dedicated field or approved
notes convention exists. A later source-recording feature creates a new CAT-06
version.

### Outcome

The exact identifier and critical specification facts are supported by a
trustworthy source, or the uncertain data is withheld from creation or routed
for correction.

### Page Plan

Target: three-page `reference-checklist` or other verified reference recipe.

1. **Choose the strongest evidence**
   - Physical manufacturer model/SKU label.
   - Manufacturer support or product documentation.
   - Another explicitly accepted authoritative source when the first two are
     unavailable.
   - Serial number, Product ID, and Inbit asset tag are not model-number
     evidence.
2. **Verify identity and facts**
   - Manufacturer, product family, generation, exact code, punctuation,
     regional suffix, and case.
   - Separate factory configuration facts from hardware changed later on one
     physical asset.
   - For conflicting sources, use the physical exact label for identity and
     resolve specification differences before entering data.
3. **Apply the result**
   - Correct identity route: CAT-01.
   - Correct specification route: CAT-02.
   - Missing reusable definition: CAT-03 or CAT-04.
   - Lifecycle/cleanup correction: CAT-05.
   - No defensible source: do not invent a unique code or value.

### Open Product Decision

Where the verification source and result should be recorded remains open. It
does not block a verification-only working draft, but it blocks any guide
claiming a durable in-application audit trail.

## Terminology Contract

| Use in guides | Meaning | Avoid as operator wording |
| --- | --- | --- |
| Basismodel | Product family plus generation | Generic `model` when the exact level matters. |
| Modelnummer | Exact manufacturer variant/SKU code | Serial number, Product ID, Inbit tag. |
| Asset | One physical device | Treating an asset as a catalogue definition. |
| Attribuutdefinitie | Reusable meaning and input rules | `Eigenschap`, schema, field type. |
| Attribuutwaarde | One value using a definition | Calling the value a definition. |
| Componentdefinitie | Reusable physical-part type | `Onderdeeltype` when the UI/guide family uses component. |
| Verwachte component | Model-number baseline template and quantity | A unique physical part. |
| Aangenomen (`Assumed`) | Expected asset baseline not yet individually tracked | Saved physical component. |
| Geregistreerd (`Tracked`) | Physical component record with its own state | `Placed Component` as a universal operator term. |
| Verwijderd (`Removed`) | Expected baseline quantity removed/materialized from its prior place | A generic deleted catalogue definition. |
| Standaard (`Primary`) | System fallback/default model number | Approval, quality state, or latest variant. |

If the UI exposes a technical English label, show it exactly once and explain
the effect. Do not teach internal names such as `resolves_to_spec`,
`instance-attribuut`, `cataloguslaag`, `Kind -> ouder`, or code-oriented
aggregation rules.

## Diagram And Visual Rules For CAT

- CAT purple identifies catalogue/model/attribute structures, CMP amber
  identifies component definitions and component states, and AST green
  identifies physical assets and asset-specific state.
- Color follows the object family. Definition, baseline, and physical instance
  are distinguished with headings, icons, borders, and fill treatment.
- Relationship arrows may not cross text, labels, badges, or another node.
- Connector labels use a dedicated clear gap or an opaque background.
- Arrow direction and multiplicity must match the implemented relationship.
- Use diagrams for relationships and real screenshots for locations/actions.
- CAT-00 uses few screenshots; CAT-01 through CAT-05 show every required
  navigation, action, Save, and result state.
- Do not place a focus mark on an already isolated state. Where needed, derive
  it from measured source-pixel target bounds and keep the complete stroke
  inside the image.
- Reuse one canonical source image for the same screen. Different crops and
  focus targets may serve different guide questions.

## Evidence Plan

| Guide | Existing reusable evidence | Evidence still required |
| --- | --- | --- |
| CAT-00 | Model detail, model spec, component-definition list, component roster | Revised diagrams; no new screenshots unless current evidence cannot support the simplified pages. |
| CAT-01 | Model list/detail/create and model-number create | Global exact-code search path; dashboard navigation after the Basismodel label decision; saved recognition-image state if retained. |
| CAT-02 | Current model-spec and expected-component captures | Full selector context, direct-attribute add/value state, expected-component add/order state, conflict/derived preview, Save and saved result. |
| CAT-03 | Attribute-definition list plus six new captures | Complete: dashboard route, create core fields, datatype examples, scope/behavior fields, constraints/Enum options, Save, and resulting list row. |
| CAT-04 | Component-definition list plus six new captures | Complete: dashboard route, identity form, expected-part rows, contribution rows, overlap warning, Save, and resulting reusable definition. |
| CAT-05 | Model-number lifecycle capture | Admin-only specification cleanup, attribute lifecycle/options, and component-definition deactivate/cleanup states. |
| CAT-06 | Owner-provided device underside/label photos may be reused when relevant | Readable exact product/SKU label and an approved source example; no fabricated source-storage screen. |

All captures use `https://dev.inbit/` only as controlled evidence input. Printed
access remains `https://snipe.inbit/`. If the release UI differs materially,
the evidence remains draft.

## Application Gaps The Guides Must Expose, Not Hide

1. The Dutch navigation still says `Asset modellen`; the requested
   `Basismodellen` rename is not implemented. Until it is, actions quote the
   visible label and explanations use `Basismodel`. A later rename affects all
   CAT navigation evidence and creates new guide versions.
2. Component-definition serial-tracking and placement controls are not exposed
   in the browser. Remove those planned operator steps unless the product adds
   and verifies the controls.
3. No structural source/verification field exists. CAT-06 can verify but
   cannot promise source storage.
4. Expected model-number components are required by default in the current
   form; there is no operator required/optional toggle there.
5. A Supervisor can add/edit model specifications but cannot remove saved rows.
   The screenshots and wording must reflect that role-specific UI.
6. Workflow configuration and complete sample-product validation still need a
   separate WF-family plan and guides.
7. Missing manufacturer/category creation is an Admin/global-catalog
   dependency. CAT-01 must not tell a Supervisor to select a near match.

## Production Order

This is the recommended creation order, not the operator execution order:

1. Review and freeze this information-placement plan.
2. Rewrite CAT-00 as the six-part orientation and generate CAT-00 v8.
3. Align CAT-01 with the plan and current navigation as a new v4 draft; retain
   the reusable three-route structure from v3.
4. Revise CAT-03 and CAT-04 specifications against the real forms, capture
   their evidence, and generate their first drafts.
5. Revise and generate CAT-02 after CAT-03/CAT-04 terminology and return paths
   are concrete.
6. Generate CAT-06 as verification-only unless source recording is implemented
   first.
7. Investigate each Admin-only lifecycle branch and generate CAT-05 last.
8. Run one end-to-end cold-start exercise across the CAT set and its AST/CMP
   handoffs before any CAT artifact becomes an Internal review candidate.

Steps 1 through 4 were completed on 2026-09-01 as CAT-00 v8, CAT-01 v4,
CAT-03 v1, and CAT-04 v1. CAT-00 v9 supersedes v8 for diagram connector and
alignment corrections. CAT-01 v5 supersedes v4 for duplicate-route clarity,
action naming, sparse-text sizing, and the default-configuration label rule.
CAT-02 v1 completed production step 5 on 2026-09-03 as a six-page Supervisor
workflow with controlled, non-destructive evidence. CAT-04 v2 supersedes v1
for sparse-card readability and the current-process Required rule. All exact
PDFs remain unaccepted; production continues with CAT-05 and the CAT-06
source-policy decision.

## Set-Level Acceptance Tests

The CAT set is ready for exact-version review only when:

- a new Supervisor can start from the dashboard and complete the ordinary
  product route without undocumented navigation or programmer knowledge;
- a reader can assign six example questions to the correct CAT guide without
  opening every guide;
- CAT-01 never creates a duplicate in its normal test scenario;
- CAT-02 contains no value entered both directly and through a contributing
  component in its worked result;
- CAT-03 datatype, scope, constraints, and options can be chosen from examples
  without explaining source code or regex syntax;
- CAT-04 distinguishes a reusable definition from a physical tracked part and
  shows only controls that exist;
- CAT-05 exposes only controls available to Admin and verifies every changed
  object after the action;
- CAT-06 never treats serial, Product ID, or Inbit asset tag as model-number
  proof and does not claim unsupported source storage;
- every cross-guide handoff uses the registered family icon, color, code, and
  full name;
- every required action has recognizable screenshot context, a measured target
  when needed, a Save action, and a visible result;
- no connector, focus mark, badge, caption, or body text overlaps another
  instructional element at actual A4 size;
- the exact versions remain `Working draft` until the user explicitly accepts
  them for internal review.

## Decisions Needed Before Generation

- Confirm the narrower CAT-05 maintenance scope and whether its printed title
  changes from `Varianten en lifecycle beheren`.
- Confirm whether CAT-06 v1 is verification-only or waits for a source field.
- Decide whether the application will be renamed from `Asset modellen` to
  `Basismodellen` before new CAT evidence is captured.
- Create a separate WF-family plan for workflow items, profiles,
  applicability, and sample-product validation.
