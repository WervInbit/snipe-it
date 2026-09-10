# CAT-05 Varianten en lifecycle beheren

Status: Planned specification; workflow behavior verified, evidence pending.

## Maintenance Metadata

- Family: `CAT`.
- Type: Extended administration task.
- Current version: planned.
- Expected page model: Five branching pages, aligned with the catalog guide plan.
- Layout recipe: to be assigned after evidence capture.

## Purpose

Maintain catalogue defaults, lifecycle, and saved rows without breaking assets
or misusing partial duplication. CAT-01 owns creation of exact variants. The
proposed title change in the catalog guide plan remains an owner decision.

## Audience And Context

- Role: Admin / Superadmin.
- Needed: Verified variant relationship and knowledge of assets already using
  each model number.
- Prerequisites: `CAT-00`, `CAT-01`, and `CAT-02`.

## Planned Pages

1. Choose the object and inspect its current use. Select only the needed
   lifecycle or cleanup branch; these pages are not a mandatory sequence.
2. Manage `Primary`, `Active`, and `Deprecated`: primary is the default for new
   assets; making a deprecated number primary restores it; primary cannot be
   deprecated.
   Primary cannot be deleted; a model number used by any asset cannot
   be deleted. Explain the limited `Kopieer model` behavior.
3. Remove saved model-specification rows only after impact review. Removing a
   direct value also removes related per-asset overrides. Verify the result.
4. Manage attribute lifecycle and saved Enum options without changing the
   meaning of in-use keys. Deletion requires the definition to be unused.
5. Activate/deactivate component definitions or remove saved contributions and
   expected-part rows after impact review. There is no browser Delete action
   for a component definition. Verify affected baselines and selectors.

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
