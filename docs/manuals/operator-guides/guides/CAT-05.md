# CAT-05 Bestaande catalogus aanpassen

## Application Alignment Revision - 2026-10-01

Current authoring proposal: **v3, working draft; not accepted**, 9 A4 pages. [Exact-version review](../reviews/CAT-05-v3.md) and [batch comparison](../reviews/aligned-guides-2026-10-01.md).

Add maintenance route for changing numeric aggregation on definitions already in use, with broad impact validation.

Added page: Wijzig numerieke aggregatie bij bestaand gebruik. Steps: Bepaal de juiste betekenis en alle geraakte bronnen; Kies de bedoelde regel en sla bewust op; Heropen en controleer echte voorbeelden.

The frozen new HTML preserves this generated proposal for reproduction. It does not define approved instructional scope or supersede the guide specification, [authoring method](../authoring-method.md), or owner decisions. Resolve conflicting walkthroughs before a new revision. Earlier artifacts and acceptance decisions are unchanged. Generator: scripts/manuals/generate-aligned-guides-2026-10-01.mjs; hash-locked v2 input.

## Purpose And Route Index

Use this guide only to change something that already exists. Page 1 starts
from the screen or action the operator recognizes, without requiring the
technical object name in advance. Normal creation remains in CAT-01/02/03/04.

| Route | Pages | Entry and completion |
| --- | --- | --- |
| Task finder | 1 | Match the visible screen: Model Numbers, Edit Spec, Attributes, or Component Definitions. Unknown screen or impact means stop. |
| Code in Model Numbers | 2-3 | Active/Deprecated is page 2. Primary or deleting an unused code is page 3. Each page repeats the exact left-menu route. |
| Row in Edit Spec | 4 | Remove one direct attribute or expected component only after the stated impact check; save, reopen and inspect affected records. |
| Field or choice in Attributes | 5-6 | Hide/restore an entire field on page 5; maintain one existing Enum choice on page 6. |
| Reusable part type | 7-8 | Activate/deactivate a Component Definition on page 7; remove one stored child/contribution on page 8. |
| Numeric Component Aggregation | 9 | Inspect all affected sources, choose sum or distinct with lifecycle rights, save/reopen and compare existing examples. |

## Behavior Contract

- A deprecated model number must first be saved Active and reopened before
  selecting the global edit form's default checkbox. Do not teach the separate
  controller shortcut as this UI route.
- Primary cannot be deprecated/deleted. First choose another valid active code
  of the same model as the default. Assets must be zero before deletion;
  needed specification/images/history may still justify retention.
- Kopieer model copies base fields and optionally the base-model image, not
  model numbers, variant images, specification values or expected components.
- Direct attribute removal also deletes all associated asset overrides for
  that model number. Open each affected asset's Specification section and
  inspect Override values; preserve its identity/value before action. If the
  complete associated-asset list is uncertain, stop and ask the administrator
  to establish impact. One inspected asset is not an exhaustive impact check.
- Expected-component removal changes catalogue expectations, not physical
  Tracked inventory. Preserve counts, required state and derived expectations.
- Attribute definition use counts are not per-option usage counts. Keep Value
  and Label distinct; do not rely on an automatic per-option-use deletion guard.
- The browser exposes no attribute-definition or component-definition delete
  action. Do not invent one.
- Component Templates counts model-number templates, not all child relations.
  Check child relationships and contributions separately.
- X/Remove in forms is unsaved until Opslaan/Save Changes. List lifecycle
  actions are immediate. Cancel is not save.

## Evidence And Review

V2 adds real read-only model edit, in-use attribute/options, and saved component
relationship controls. Earlier evidence is reused only for the controls shown,
not as a newly executed success state. No destructive catalogue action was run.

The [v2 review](../reviews/USR-05-CAT-05-CAT-06-v2-2026-09-15.md)
maps audit findings to pages and lists the remaining disposable-data trials.
Every branch has its own result and return; other branches are not next steps.

V1 is retained unchanged in its [review record](../reviews/CAT-05-v1.md).
The [content audit](../reviews/USR-05-CAT-05-CAT-06-content-audit-2026-09-15.md)
supersedes its incomplete procedure. Related: CAT-00 through CAT-04, CAT-06,
and the appropriate physical-component CMP task.
