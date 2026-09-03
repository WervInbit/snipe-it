# CAT-00 Catalogus begrijpen

Status: Working draft v8; six-part orientation rebuild awaiting exact-version
review.

## Maintenance Metadata

- Family: `CAT`.
- Type: Reference chapter and guide router.
- Current version: v8 draft.
- Page model: Six-page reference chapter with continuous section numbering.
- Layout recipe: `reference-chapter` with `reused-evidence` and
  `two-sided-continuation`.
- Generator: `scripts/manuals/generate-catalog-guide-review.mjs`.
- Artifact root: `output/manuals/proofs/catalog-guide-review/cat-00-v8/`.
- Portable review package:
  `resources/manuals/operator-guides/drafts/CAT-00-catalogus-begrijpen-v8-draft.pdf`.
  Its manifest status is `Unaccepted working draft`.

## Purpose

Give a Supervisor an approachable map of catalogue identity, reusable
definitions, model-number baselines, and physical asset state. The chapter
must make the next task guide predictable without becoming a compressed field
manual for every CAT form.

CAT-00 explains and routes. It does not create, edit, or save application data.

## Audience And Context

- Role: Supervisor.
- Needed: catalogue read rights.
- Prerequisite: `AC-01 Login`.
- Reader knowledge: physical product work, but no Snipe-IT, database, or
  programming knowledge is assumed.
- Diagrams explain relationships; selected screenshots identify real
  interface landmarks.

## Part 1 - The Catalogue In One View

Show these relationships without crossing connectors:

1. `Basismodel -> Modelnummer -> Asset` is the identity chain.
2. A direct attribute value and an expected component belong to the exact
   Modelnummer baseline.
3. A direct value uses one Attribuutdefinitie.
4. A Componentdefinitie uses one or more Attribuutdefinities.
5. A registered component is physical state on exactly one Asset and uses a
   reusable Componentdefinitie.

CAT purple identifies model and attribute structures, CMP amber identifies
component structures and states, and AST green identifies physical asset
identity/state. Headings and border/fill treatment distinguish definitions,
baseline uses, and physical records.

The closing note states: a definition describes what is reusable, a baseline
describes what is expected, and a physical record describes what is actually
present.

## Part 2 - Which Identity Am I Looking At?

Use two aligned examples:

| Basismodel | Exact modelnummer | Fysiek asset |
| --- | --- | --- |
| HP ProBook 450 G8 | 2E9F8EA#ABH | INBIT-HG0042 / S/N 5CD1234ABC |
| Samsung Galaxy A5 | SM-A520F | INBIT-TF0187 / S/N R58M1234ABC |

Explain:

- Basismodel is product plus generation and can contain several exact codes.
- Modelnummer is the complete manufacturer variant/SKU code and can be reused
  by many physical assets.
- Asset is one physical device with its own Inbit tag and serial number.
- Another printed manufacturer code is another exact model number.
- RAM or storage replaced later on one device changes physical asset state; it
  does not invent another manufacturer code.

Visual `2A` shows the Basismodel breadcrumb and complete exact-number row in
one recognizable crop.

## Part 3 - Reusable Building Blocks

### Attribuutdefinitie

Describe it as the reusable meaning and input rules for one fact. Use the
small example `Werkgeheugen`, datatype `Int`, unit `GB`. Do not reproduce the
complete CAT-03 field form.

### Componentdefinitie

Describe it as a reusable physical-part type with identity, attribute values,
display information, and optional expected children. Use `RAM 8 GB DDR4`,
which uses `Werkgeheugen = 8 GB` and `Geheugentype = DDR4`.

The relationship diagram explicitly shows one Componentdefinitie using several
Attribuutdefinities. It also states that one Attribuutdefinitie can be reused by
many component definitions, model numbers, or workflow features.

Visual `3A` shows the attribute-definition list. Visual `3B` shows the
component-definition list. Neither visual teaches its complete create form.

## Part 4 - Expectation Versus Reality

Explain the states around one expected component:

- `Verwachte component`: component definition plus quantity on the exact
  model-number baseline.
- `Aangenomen (Assumed)`: the asset still uses that baseline expectation
  without a separately registered physical record.
- `Geregistreerd (Tracked)`: a component registration or movement has created
  a physical record on the asset.
- `Verwijderd (Removed)`: expected quantity is no longer merely assumed in its
  original place.

Do not present the states as mandatory sequential work. A baseline row may
remain Assumed. A generic status change does not materialize a Tracked record.

Visual `4A` shows a tracked RAM row. Visual `4B` shows storage still assumed
from the baseline.

## Part 5 - How A Model Specification Is Formed

Use one decision:

- Direct value: stable intrinsic fact of this manufacturer variant, such as
  introduction year, weight, colour, or screen size.
- Expected component: hardware that is replaceable, countable, transferable,
  or separately inspectable, such as RAM, storage, battery, or motherboard.

Use the HP example throughout. State that component contributions may determine
the displayed model and asset specification. The same fact must not be entered
again as a competing direct value.

Visual `5A` shows a direct value on `Edit Spec`. Visual `5B` shows expected
components and quantities on the same specification route.

Detailed datatype fields, contribution controls, overlap resolution, and value
precedence belong to CAT-02 through CAT-04, not this orientation page.

## Part 6 - Choose The Correct Follow-Up Guide

Route by the object or question:

| Need | Guide |
| --- | --- |
| Missing product/generation or exact manufacturer code | `CAT-01 Model en modelnummer aanmaken` |
| Missing baseline for one exact code | `CAT-02 Modelspecificatie opbouwen` |
| Missing reusable meaning or input rule | `CAT-03 Attributen beheren` |
| Missing reusable physical-part type | `CAT-04 Componentdefinities beheren` |
| Existing default/lifecycle/cleanup state | `CAT-05 Varianten en lifecycle beheren` |
| Uncertain identity, code, or source | `CAT-06 Catalogus controleren en bronnen` |
| One physical device after the catalogue is complete | `AST-03 Asset registreren en labelen` |
| Register or place a physical component | `CMP-01` or `CMP-02` |

Mark CAT-02 through CAT-06 as `In voorbereiding` until their artifacts exist.
Do not infer anything about the reader's suitability from completion.

## Completion

`Klaar als`: the reader can identify the object and choose the guide that owns
the required change.

## Related Guides

- `CAT-01 Model en modelnummer aanmaken`.
- `CAT-02 Modelspecificatie opbouwen`.
- `CAT-04 Componentdefinities beheren`.
- `CAT-06 Catalogus controleren en bronnen`.

## Evidence Manifest

| Label | Source ID | Purpose |
| --- | --- | --- |
| 2A | `CAT-MODEL-DETAIL-DESKTOP-01` | Basismodel breadcrumb and exact-number row. |
| 3A | `CAT-ATTRIBUTE-LIST-DESKTOP-01` | Reusable attribute definitions. |
| 3B | `CAT-COMPONENT-DEFINITION-LIST-DESKTOP-01` | Reusable component definitions. |
| 4A/4B | `CMP-INSTALL-RESULT-MOBILE-02` | Tracked and assumed states on one asset roster. |
| 5A | `CAT-MODEL-SPEC-DESKTOP-01` | Direct value on one exact model number. |
| 5B | `CAT-MODEL-SPEC-COMPONENTS-DESKTOP-01` | Expected components and quantities. |

## Validation Notes

- The six numbered bodies are reference sections, not execution steps.
- Every relationship arrow has a clear path and no text intersection.
- Operator copy does not expose internal field names or source-code terms.
- Cross-guide references use family colour, icon, code, and complete title.
- Screenshots retain recognizable application context and short captions.
- CAT-00 v8 remains a working draft until this exact PDF is explicitly
  accepted.
