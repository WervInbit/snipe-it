# CAT-01 Model en modelnummer aanmaken

Status: Working draft v3; structural rewrite aligned with CAT-00 v7 and
awaiting exact-version review.

## Maintenance Metadata

- Family: `CAT`.
- Type: Extended administration task.
- Current version: v3 draft.
- Page model: Five-page procedure with continuous steps and page handoffs.
- Layout recipe: `extended-admin-flow` with `single-visual`,
  `inline-route-alternative`, `inline-warning`, `reused-evidence`, and
  `two-sided-continuation`.
- Generator: `scripts/manuals/generate-catalog-guide-review.mjs`.
- Artifact root: `output/manuals/proofs/catalog-guide-review/cat-01-v3/`.
- Portable review package:
  `resources/manuals/operator-guides/drafts/CAT-01-model-en-modelnummer-aanmaken-v3-draft.pdf`.
  Its manifest status is `Unaccepted working draft`.

## Purpose

Reuse or create the correct Basismodel and add one exact manufacturer
model-number code without duplicating an existing catalogue route or confusing
a physical asset change with a new manufacturer variant.

## Audience And Context

- Role: Supervisor.
- Needed: catalogue-management rights, the product label, and a verified
  manufacturer source for the product name and exact code.
- Prerequisites: `AC-01 Login` and `CAT-00 Catalogus begrijpen`.
- Primary path is desktop administration. Mobile screenshots are not required
  for this catalogue task.
- The application menu and historical screenshots currently show
  `Asset modellen`. In operator explanations, each reusable product and
  generation record is called a `Basismodel`.

## Page 1 - Find The Existing Catalogue Route

### Step 1: Open Asset modellen

From the dashboard, expand `Instellingen` and choose `Asset modellen`. This
opens reusable Basismodellen, not the individual physical assets under
`Apparaten`.

Visual `1A`: expanded dashboard navigation with `Instellingen` and
`Asset modellen` visible.

Caption: `Open Instellingen en kies Asset modellen.`

### Step 2: Search name and exact code

Search before using the create control:

1. Search manufacturer, product family, and generation, for example
   `HP ProBook 450 G8`.
2. Search or compare the complete printed model-number code, including its
   suffix, for example `2E9F8EA#ABH`.
3. Compare the visible Basismodel name, `Model Nr.`, `Model Numbers` count,
   and category.

Visual `2A`: model list with search field, matching rows, exact codes, and
create `+` in recognizable surrounding context.

Caption: `Zoek naam en exacte code voordat je de + gebruikt.`

Amber warning: `Een vergelijkbare naam is niet genoeg. Controleer ook de
volledige fabrikantcode en generatie.`

Page handoff: `Volgende pagina: kies de juiste bestaande of nieuwe route.`

## Page 2 - Choose The Correct Route

### Step 3: Choose one of three outcomes

#### Route A - Exact code already exists

Open the matching Basismodel and exact row. Verify the code, label, category,
and manufacturer. Do not create another record. Continue at step 7.

#### Route B - Basismodel exists, exact code is missing

Open the matching Basismodel. Verify that manufacturer, product family, and
generation agree, then choose `Create Model Number`. Continue at step 5.

Visual `3A`: existing Basismodel with its identity, exact-number table, and
`Create Model Number` action.

Caption: `Gebruik het bestaande basismodel als product en generatie gelijk
zijn.`

#### Route C - Basismodel is missing

Use `+` on the model list only when the manufacturer plus product family and
generation are genuinely absent. Continue at step 4.

Visual `3B`: model-list create `+` with toolbar and list context.

Caption: `Gebruik de + alleen als het basismodel werkelijk ontbreekt.`

Decision rule:

- A different printed manufacturer/SKU code is another exact model number,
  even when it represents a factory RAM or storage configuration.
- RAM or storage changed later in one physical device remains a component
  change on that asset; it does not create a new manufacturer code.

Page handoff: `Volgende pagina: maak alleen een ontbrekend Basismodel.`

## Page 3 - Create A Missing Base Model

### Step 4: Enter the reusable product identity

Enter only the active catalogue identity fields:

| Field | Example | Meaning |
| --- | --- | --- |
| Naam basismodel | `HP ProBook 450 G8` | Product family plus generation shared by exact variants. |
| Categorienaam | `Laptops` | Correct device type used for applicable catalogue and workflow content. |
| Fabrikant | `HP` | Actual manufacturer; do not substitute a similar brand. |

Visual `4A`: the unsaved example form cropped to the three active identity
fields and `Opslaan`. Deprecated finance/custom-field controls are not taught
as part of the refurbishment catalogue route.

Caption: `Vul product + generatie, categorie en fabrikant in.`

An optional generic image may aid recognition, but it is not an identifier and
must not delay creation of the exact model number.

Before saving, confirm that the Basismodel name contains no asset tag, serial
number, exact SKU suffix, or invented configuration code. Choose `Opslaan`
once. A newly saved Basismodel is not complete until it contains the exact
model number.

Amber warning: `Ontbreekt de juiste categorie of fabrikant, kies dan geen
bijna-gelijke vervanger.`

Page handoff: `Volgende pagina: voeg het exacte modelnummer toe.`

## Page 4 - Add The Exact Manufacturer Variant

### Step 5: Open Create Model Number

On the saved or reused Basismodel detail page, choose
`Create Model Number`.

Visual `5A`: Basismodel detail with the exact-number table and create action.

Caption: `Open de exacte variant vanaf het juiste Basismodel.`

### Step 6: Enter code and recognition label

| Field | What to enter |
| --- | --- |
| Code | Complete manufacturer/SKU code from the label or verified source: `2E9F8EA#ABH`. Preserve meaningful suffixes. |
| `Aa` | Use only when the printed code intentionally contains lowercase. Normal input is capitalized automatically. |
| Label | Readable recognition text, for example `HP ProBook 450 G8 - i5-1135G7 - 8GB - 256GB`. |

The code is the exact identity. The label helps a refurbisher recognize the
variant but is not a replacement for CAT-02 specification values.

Visual `6A`: unsaved model-number form with code, `Aa`, label, and `Opslaan`
visible.

Caption: `Code is exact; Label helpt de variant herkennen.`

Do not enter a serial number, Product ID, Inbit asset tag, or self-created code.
Choose `Opslaan` once. The first exact number becomes the system standard
automatically; changing that lifecycle choice belongs to CAT-05.

Page handoff: `Volgende pagina: controleer de opgeslagen identiteit.`

## Page 5 - Verify And Continue

### Step 7: Verify the saved catalogue identity

Check the Basismodel and exact row together:

1. Basismodel name is product plus generation.
2. Category and manufacturer match the source.
3. Exact code includes every meaningful suffix.
4. Label is understandable but does not replace the exact code.
5. Row is active; the first row may be shown as the automatic standard.
6. No duplicate Basismodel or exact-code row exists.

Visual `7A`: wide exact-number row with code, label, status, and actions.

Caption: `Controleer code, label en actieve/standaardstatus.`

Visual `7B`: readable information panel with category and manufacturer.

Caption: `Controleer categorie en fabrikant.`

### Step 8: Choose the next object

- Use `CAT-02 Modelspecificatie opbouwen` when direct attributes or expected
  components still need to be configured. CAT-02 is in preparation.
- Use `AST-03 Asset registreren en labelen` only when the reusable catalogue
  identity is ready and one physical device must be registered.

### Copy Limitation

`Kopieer model` copies only the Basismodel form and optionally its base image.
It does not copy exact model numbers, model-number images, direct specification
values, or expected components. Verify every child record manually when this
route is used.

## Help

- Exact code unavailable: use `CAT-06 Catalogus controleren en bronnen`; do
  not invent a code.
- Similar product found: return to CAT-00 and compare product generation and
  complete exact number.
- Wrong standard row or obsolete exact number: use `CAT-05 Varianten en
  lifecycle beheren`; do not delete an in-use record.

## Completion

`Klaar als`: one correct Basismodel contains the verified exact manufacturer
model number, with the right category, manufacturer, code, and recognition
label, without a duplicate.

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
| 3A | `CAT-MODEL-DETAIL-DESKTOP-01` | Existing Basismodel and exact-code routes. |
| 3B | `CAT-MODEL-LIST-DESKTOP-01` | Create a genuinely missing Basismodel. |
| 4A | `CAT-MODEL-CREATE-DESKTOP-01` | Explain only the active product-identity fields and save action. |
| 5A | `CAT-MODEL-DETAIL-DESKTOP-01` | Open the exact-number route from the correct Basismodel. |
| 6A | `CAT-MODEL-NUMBER-CREATE-DESKTOP-01` | Enter exact code, case behavior, recognition label, and save. |
| 7A | `CAT-MODEL-DETAIL-DESKTOP-01` | Verify the complete exact-number row at readable size. |
| 7B | `CAT-MODEL-DETAIL-DESKTOP-01` | Verify category and manufacturer at readable size. |

## Validation Notes

- Step numbers continue 1-8 across all five pages.
- Every page has a named task stage and an explicit next-page handoff.
- Cross-guide references use their registered full name, symbol, and family
  color.
- CAT purple identifies Basismodel/model-number content, CMP amber identifies
  physical component changes, and AST green identifies the physical-asset
  follow-up.
- Deprecated `Afschrijving` and `Veldverzameling` fields are not presented as
  normal refurbishment catalogue inputs.
- Focus marks remain symmetric and fully contained in their screenshot frame.
- Screenshots retain recognizable navigation, form, table, and action context;
  no narrow viewfinder crops are acceptable.
- Duplicate and missing-dependency messages are amber recoverable warnings,
  not red stops.
- Printed operator text contains `https://snipe.inbit/` only; capture metadata
  may identify the controlled development environment internally.
