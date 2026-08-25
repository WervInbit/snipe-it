# CAT-06 Catalogus controleren en bronnen

Status: Planned specification; operational source policy requires review.

## Maintenance Metadata

- Family: `CAT`.
- Type: Reference and verification task.
- Current version: planned.
- Expected page model: Three pages.
- Layout recipe: to be assigned after source-recording policy is confirmed.

## Purpose

Verify catalogue identity and specification values against trustworthy evidence,
and prevent demo placeholders or invented codes from becoming production data.

## Audience And Context

- Role: Admin / Superadmin.
- Needed: Physical product label and/or authoritative manufacturer documentation.
- Prerequisite: `CAT-00 Catalogus begrijpen`.

## Planned Pages

1. Evidence hierarchy: physical model/SKU label, manufacturer support/product
   documentation, then another approved authoritative source. Product ID,
   serial number, and asset tag are not model-number evidence.
2. Exact-code verification: preserve meaningful punctuation and regional
   suffixes, use normal uppercase behavior unless the printed code requires `Aa`,
   and distinguish verified production codes from clearly marked demo values.
3. Record the source and review result, resolve conflicts, and route uncertain
   records for correction rather than inventing uniqueness.

## Open Product Decision

The current catalogue UI has no dedicated verification-source field. Until an
implemented field or approved notes convention exists, the guide must not claim
that source evidence is stored structurally in Snipe-IT. This policy needs an
operational owner decision before CAT-06 can become an internal review candidate.

## Completion

`Klaar als`: the exact model number and each critical specification value can be
traced to approved evidence, or the uncertain record is explicitly withheld
from creation/correction.

## Related Guides

- `CAT-00 Catalogus begrijpen`.
- `CAT-01 Model en modelnummer aanmaken`.
- `CAT-02 Modelspecificatie opbouwen`.
- `CAT-05 Varianten en lifecycle beheren`.
