# CAT-01 v3 Review

| Field | Value |
| --- | --- |
| Previous version | v2 working draft |
| Generated | 2026-08-27 |
| PDF | `output/pdf/CAT-01-model-en-modelnummer-aanmaken-v3-draft.pdf` |
| Generator | `scripts/manuals/generate-catalog-guide-review.mjs` |
| Impact | Structure, route logic, terminology, screenshot framing, and follow-up |
| Status | Working draft; exact-version review pending |

## Correction

- Apply the CAT-00 v7 visual language, semantic CAT/AST references, and
  reusable stage/context components to the CAT-01 procedure.
- Start from `Instellingen > Asset modellen`, then search product, generation,
  and complete manufacturer code before any create action.
- Present three exclusive outcomes: exact code exists, Basismodel exists but
  its exact code is missing, or the Basismodel itself is missing.
- Teach only the active Basismodel identity fields: name, category, and
  manufacturer. Do not teach legacy depreciation or field-set controls.
- Separate exact manufacturer/SKU code from its readable label, explain the
  automatic first standard row, and keep serial number, Product ID, and Inbit
  tag out of catalogue identity.
- End with a readable saved-record check and explicit CAT-02 specification or
  AST-03 physical-asset follow-up.

## QA

- Five A4 pages generated with continuous steps 1-8.
- Generator component and geometry reports contain no errors.
- All final PDF pages were rasterized and reviewed at print scale.
- Screenshot crops retain navigation and field context while excluding the
  legacy EOL and field-set rows from the taught flow.
- Full guide references use the registered icon, color, code, and name.
- Shared guide-system and accepted/unaccepted package validation passed.
- PDF SHA-256:
  `2A96601B7606792EF2FDD4C3DA56E7A4E32EB4DD7EBBE048222731565EF6810A`.

## Review Decision

CAT-01 v3 is the active, portable working draft. It is explicitly unaccepted
and does not alter any accepted guide record.
