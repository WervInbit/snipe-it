# CAT-06 Productgegevens controleren

| Field | Current value |
| --- | --- |
| Status | Unaccepted working draft; catalogue-owner review and source-retention/fallback policy pending |
| Family | CAT |
| Type | Worked source-verification task |
| Current version | v2 draft |
| Page model | Three A4 pages |
| Layout recipe | worked-source-check |
| Generator | scripts/manuals/generate-planned-guide-v2.mjs |
| Artifact | resources/manuals/operator-guides/drafts/cat-06-catalogus-controleren-en-bronnen-v2-draft.pdf |
| Role | Supervisor |
| Needed | Physical manufacturer label and manufacturer source |
| Prerequisite | CAT-00 Catalogus begrijpen |

## Purpose And V2 Flow

Use this guide only before CAT-01 or CAT-02 when a complete model code or one
specification fact is uncertain. It verifies whether the evidence is strong
enough; it never creates or edits catalogue data itself. Do not confuse
supported family facts, optional configurations, maximum capacities and
current physical hardware.

1. Choose one concrete question: is the complete model code correct, does the
   source cover that exact variant, or does it support this one fact? Read the
   real HP label: ProBook 450 G8, ProdID 2E9F8EA#ABH. Distinguish
   the complete variant code/suffix from S/N, Inbit tag and software Product ID.
   Compare exact characters; input follows CAT-01's uppercase/Aa behavior.
2. Open the official HP ProBook 450 G8 product page. Its family and generation
   match, but it does not list the exact SKU. It supports the 15.6-inch family
   display diagonal, not optional FHD/Touch/Sure View or exact-variant RAM.
   Two narrow source crops show only the title and display line.
3. Follow one result: confirmed identity returns to CAT-01; a confirmed
   specification returns to CAT-02; an unconfirmed required identity stops
   creation, while an unconfirmed optional fact stays empty and is handed to
   the catalogue owner. Missing meanings go to CAT-03, missing part definitions
   to CAT-04, and maintenance of an existing object to CAT-05. Later per-asset
   hardware changes stay outside the factory baseline.

## Source And Applicability

[HP - HP ProBook 450 G8 official product page](https://www.hp.com/us-en/laptops/business/probook-400/product-card/hp-probook-450-g8.html),
checked 2026-09-15. Source image ID: CAT-SOURCE-HP-PROBOOK-450-G8-02.
Physical label: AST-LABEL-PLACEMENT-PHOTO-01.

The source's general 15.6-inch display statement is interpreted as a family
characteristic; it is not presented as an exact-SKU configuration lookup.
The worked result explicitly leaves RAM unresolved. No exact-SKU manufacturer
document is claimed. A vendor/distributor listing is not silently approved as
a fallback, and no device serial was transmitted to an external lookup.

## Completion And Unresolved Outcome

Successful verification and unresolved handoff are separate outcomes.
Required identity unresolved means stop creation without placeholder codes.
An optional fact unresolved means leave only that fact empty; do not invent a
baseline or revise it to match one modified device. Send the label, source
and precise open question to the catalogue owner for a decision.

The current UI has no dedicated durable catalogue-source field. Source
retention and fallback approval remain a review/product-policy issue, not
operator-facing instructions to use a fictitious field or record type.

See the [v2 review](../reviews/USR-05-CAT-05-CAT-06-v2-2026-09-15.md).
The [v1 review](../reviews/CAT-06-v1.md) and PDF remain unchanged.
Related: CAT-00 through CAT-05 and the appropriate AST/CMP physical task.
