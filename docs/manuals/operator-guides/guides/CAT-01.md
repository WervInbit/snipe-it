# CAT-01 Model en modelnummer aanmaken

Status: Working draft v2; cold-start pass and awaiting exact-version review.

## Maintenance Metadata

- Family: `CAT`.
- Type: Extended administration task.
- Current version: v2 draft.
- Page model: Five-page procedure with continuous steps and page handoffs.
- Layout recipe: `extended-admin-flow` with `single-visual`,
  `inline-route-alternative`, `inline-warning`, `reused-evidence`, and
  `two-sided-continuation`.
- Generator: `scripts/manuals/generate-catalog-guide-review.mjs`.
- Artifact root: `output/manuals/proofs/2026-08-20-cold-start-rework/catalog/`.

## Purpose

Create a reusable base model and its exact model number, or add a missing exact
model number to an existing model, without creating a duplicate catalogue path.

## Audience And Context

- Role: Supervisor.
- Needed: Device/manufacturer, product name and generation, exact manufacturer
  model-number code, category, and an authoritative source.
- Prerequisite: Ingelogd (`AC-01 Login`) and the distinctions in
  `CAT-00 Catalogus begrijpen`.
- Primary path is desktop administration. Mobile screenshots are not required
  for this catalogue task.

## Page 1 - Open And Search

### Step 1: Open Basismodellen

From the dashboard, expand `Instellingen` and choose `Asset modellen`. This is
the current visible menu label for the Basismodel area; it does not list the
individual physical assets.

Visual `1A`: dashboard/sidebar with `Instellingen` and `Asset modellen` visible.
Caption: `Open Instellingen en kies Asset modellen.`

### Step 2: Search before creating

Search using:

- manufacturer plus product/generation, for example `HP ProBook 450 G8`;
- the exact model-number code, for example `2E9F8EA#ABH`;
- a distinctive part of the name only when spelling or spacing is uncertain.

Compare the `Naam`, `Model Nr.`, category, and existing `Model Numbers` count.

Visual `2A`: model list with search field, relevant rows, and create `+` visible.
Caption: `Zoek op naam en exact modelnummer voordat je de + gebruikt.`

Amber warning: `Maak geen tweede record voor een ander RAM- of opslagprofiel
wanneer het dezelfde productgeneratie is. Voeg dan meestal een modelnummer toe.`

Page handoff: `Volgende pagina: kies de bestaande of nieuwe route.`

## Page 2 - Choose The Route

### Step 3: Decide between two routes

#### Route A - Base model exists

1. Open the matching model.
2. Verify manufacturer, category, product name, and generation.
3. Check the `Model Numbers` table.
4. If the exact code is missing, choose `Create Model Number` and continue at
   step 5.

Visual `3A`: existing model detail with `Create Model Number`, the primary row,
and the model identity visible.
Caption: `Gebruik het bestaande basismodel als product en generatie gelijk zijn.`

#### Route B - Base model does not exist

Choose `+` on the model list and continue at step 4.

Visual `3B`: model-list create `+` with surrounding toolbar context.
Caption: `Gebruik de + alleen als het basismodel werkelijk ontbreekt.`

Amber warning: `Bij twijfel niet dupliceren. Vergelijk de fabrikant, volledige
productnaam, generatie en het exacte modelnummer.`

Page handoff: `Volgende pagina: vul het basismodel in.`

## Page 3 - Create The Base Model

### Step 4: Fill the base-model form

| Field | What to enter | Why |
| --- | --- | --- |
| Naam basismodel | Product family plus generation: `HP ProBook 450 G8` | Groups exact variants under one recognizable model. |
| Categorienaam | Correct asset category: `Laptops` | Controls available catalogue structure and reporting. |
| Fabrikant | Actual manufacturer: `HP` | Links support/manufacturer information and prevents ambiguous names. |
| Afschrijving | Approved finance policy or `Do Not Depreciate` | Do not guess a period from device age. |
| Veldverzameling | Only an approved legacy custom-field set | It is not a substitute for CAT attributes/components. |
| Notities | Optional administrative context, not an identifier | Keep serial, tag, and model-number code in their own fields. |
| Afbeelding | Optional generic model image | A model-number image can later provide a more exact variant image. |

Visual `4A`: unsaved create form populated with the example Basismodel,
category, and manufacturer. The compact field summary places each field name
directly before its example value; `Opslaan` remains visible in the form.
Caption: `Vul het algemene basismodel in; het exacte nummer volgt hierna.`

Before saving, check that the model name does not contain an asset tag, serial
number, or an invented configuration code. Choose `Opslaan` once.

The saved Basismodel is usable only after the exact model number is added.

Page handoff: `Volgende pagina: voeg het exacte modelnummer toe.`

## Page 4 - Add The Exact Model Number

### Step 5: Open Create Model Number

On the saved model detail page, choose `Create Model Number`.

### Step 6: Enter code and label

| Field | What to enter |
| --- | --- |
| Code | Exact manufacturer model/SKU code, including meaningful suffix: `2E9F8EA#ABH`. |
| `Aa` | Use only when the printed code intentionally contains lowercase; normal entry is capitalized automatically. |
| Label | Human-readable variant summary: `HP ProBook 450 G8 - i5-1135G7 - 8GB - 256GB`. |

Do not enter the serial number, Product ID, Inbit asset tag, or a made-up code.
The code must be unique within this base model.

Visual `6A`: unsaved model-number form with example code, label, `Aa`, and
`Opslaan` visible.
Caption: `Code is de exacte variant; Label helpt de refurbisher kiezen.`

Choose `Opslaan`. The system automatically marks the first exact model number
as the standard row. This is a system default, not an extra choice the
Supervisor must make in this task. A later default/lifecycle change belongs to
CAT-05.

Page handoff: `Volgende pagina: controleer en bouw de specificatie.`

## Page 5 - Verify And Continue

### Step 7: Verify the saved result

On the model detail page, check:

1. the base-model name, manufacturer, and category;
2. exact model-number code and complete suffix;
3. understandable variant label;
4. the row is active, and the first row is automatically shown as standard;
5. no duplicate row under the same model.

Visual `7A`: model detail showing the complete model-number row and primary
state.
Caption: `Controleer code, label en status op het opgeslagen basismodel.`

Visual `7B`: the model-information panel showing category and manufacturer.
Caption: `Controleer categorie en fabrikant naast de exacte variant.`

### Step 8: Optional follow-up - build the specification

The Basismodel and exact number are now ready. Use `CAT-02 Modelspecificatie
opbouwen` when specifications or expected components are needed. Then use
`AST-03 Asset registreren en labelen` for the physical device.

### Duplication limitation

`Kopieer model` copies the base-model form and optionally its base image. It
does **not** copy model numbers, specification values, expected components, or
model-number images. There is currently no complete model-number duplication
workflow. Verify every child item manually when the clone route is used.

## Help

- Exact code unavailable: use `CAT-06`; do not invent one.
- Category or manufacturer missing: create/repair the dependency, then return.
- Similar model found: use `CAT-00` and compare product generation and exact
  number before adding anything.
- Wrong primary or obsolete number: use `CAT-05`; do not delete an in-use row.

## Completion

`Klaar als`: one correct Basismodel contains the exact model number with the
right label and active/standard state, without a duplicate.

## Related Guides

- `CAT-00 Catalogus begrijpen`.
- `CAT-02 Modelspecificatie opbouwen`.
- `CAT-05 Varianten en lifecycle beheren`.
- `CAT-06 Catalogus controleren en bronnen`.
- `AST-03 Asset registreren en labelen`.

## Evidence Manifest

| Label | Source ID | Purpose |
| --- | --- | --- |
| 1A | `CAT-MODEL-LIST-DESKTOP-01` | Start from the dashboard/sidebar and find Asset modellen. |
| 2A | `CAT-MODEL-LIST-DESKTOP-01` | Search, compare existing rows, and locate the create control. |
| 3A | `CAT-MODEL-DETAIL-DESKTOP-01` | Existing model route and model-number table. |
| 3B | `CAT-MODEL-LIST-DESKTOP-01` | Reuse list evidence with a separate measured create target. |
| 4A | `CAT-MODEL-CREATE-DESKTOP-01` | Explain base-model fields using an unsaved example. |
| 6A | `CAT-MODEL-NUMBER-CREATE-DESKTOP-01` | Explain exact code, label, case behavior, and save action. |
| 7A | `CAT-MODEL-DETAIL-DESKTOP-01` | Verify code, label, active state, and system-assigned standard state. |
| 7B | `CAT-MODEL-DETAIL-DESKTOP-01` | Verify category and manufacturer without shrinking the model-number row. |

## Validation Notes

- Step numbers continue 1-8 across all five pages.
- Each page ends with an explicit `Volgende pagina` handoff except page 5.
- The `+` target, search field, and `Create Model Number` target are measured
  from their canonical source images and remain fully inside screenshot frames.
- Screenshots retain page headers, nearby controls, and table/form landmarks;
  no narrow viewfinder crops are acceptable.
- The duplicate message is amber, not a red stop.
- Printed operator text contains `https://snipe.inbit/` only; capture metadata
  may identify the controlled development environment internally.
