# CAT-03 Attributen beheren

Status: Planned specification; workflow behavior verified, evidence pending.

## Maintenance Metadata

- Family: `CAT`.
- Type: Extended administration task.
- Current version: planned.
- Expected page model: Four pages.
- Layout recipe: to be assigned after evidence capture.

## Purpose

Create or maintain a reusable attribute definition only when an existing active
definition cannot represent the required fact.

## Audience And Context

- Role: Admin / Superadmin. The implemented policy does not grant attribute
  definition management to ordinary operational groups.
- Needed: Definition purpose, normalized key, datatype, unit, category scope,
  constraints, and verified option list where applicable.
- Prerequisite: `CAT-00 Catalogus begrijpen`.

## Planned Pages

1. Search active and hidden definitions by label, key, category, and datatype;
   reuse an existing definition when semantics and unit match.
2. Create the definition: label, generated/manual key, immutable datatype
   (`enum`, `int`, `decimal`, `text`, or `bool`), unit, category scope, and
   required-for-category behavior.
3. Configure asset overrides, custom enum values, component-spec display mode,
   min/max/step/regex constraints, and enum options with examples.
4. Edit safely: datatype cannot change; in-use edits affect model-number values,
   asset overrides, component definitions/instances, and test data. Hide an
   obsolete definition; delete only when the application confirms it is unused.

## Mandatory Decisions

- One key has one stable meaning and unit.
- Blank category scope means all asset categories; it is not a temporary default.
- `allow_asset_override` is an exception mechanism, not a substitute for a
  correct model number or component definition.
- Enum options are controlled values; custom values are enabled only when the
  operational process genuinely needs them.
- In-use definitions are hidden rather than removed.

## Completion

`Klaar als`: the definition is reusable, scoped, constrained, and documented
without duplicating another active key.

## Related Guides

- `CAT-00 Catalogus begrijpen`.
- `CAT-02 Modelspecificatie opbouwen`.
- `CAT-04 Componentdefinities beheren`.
- `CAT-06 Catalogus controleren en bronnen`.
