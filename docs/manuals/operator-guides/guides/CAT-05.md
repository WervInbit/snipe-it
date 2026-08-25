# CAT-05 Varianten en lifecycle beheren

Status: Planned specification; workflow behavior verified, evidence pending.

## Maintenance Metadata

- Family: `CAT`.
- Type: Extended administration task.
- Current version: planned.
- Expected page model: Four pages.
- Layout recipe: to be assigned after evidence capture.

## Purpose

Add or maintain model-number variants, choose the primary/default number, and
retire obsolete numbers without breaking assets or misusing partial duplication.

## Audience And Context

- Role: Admin / Superadmin.
- Needed: Verified variant relationship and knowledge of assets already using
  each model number.
- Prerequisites: `CAT-00`, `CAT-01`, and `CAT-02`.

## Planned Pages

1. Decide whether a difference is another model number beneath the same base
   model or a genuinely different product/generation requiring a new model.
2. Add a new active model number, then build its own specification and expected
   components. There is no implemented model-number duplicate action.
3. Manage `Primary`, `Active`, and `Deprecated`: primary is the default for new
   assets; making a deprecated number primary restores it; primary cannot be
   deprecated.
4. Delete/cleanup safely: primary cannot be deleted; a model number used by any
   asset cannot be deleted. Explain the limited `Kopieer model` behavior.

## Duplication Contract

`Kopieer model` pre-fills the base-model form and can copy its base image. It
does not clone model numbers, model-number images, attribute values, or expected
component templates. Every missing child item must be recreated and verified.

## Completion

`Klaar als`: every variant is under the correct base model, the intended default
is primary, obsolete in-use variants are deprecated, and no partial clone is
mistaken for a complete catalogue copy.

## Related Guides

- `CAT-00 Catalogus begrijpen`.
- `CAT-01 Model en modelnummer aanmaken`.
- `CAT-02 Modelspecificatie opbouwen`.
- `CAT-06 Catalogus controleren en bronnen`.
