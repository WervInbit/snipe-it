# AST-05 Asset Beoordelen En Vrijgeven

## Application Alignment Revision - 2026-10-01

Current authoring proposal: **v7, working draft; not accepted**, 2 A4 pages. [Exact-version review](../reviews/AST-05-v7.md) and [batch comparison](../reviews/aligned-guides-2026-10-01.md).

Protected release confirmation, explicit override handling, required status notes and quality permission.

Added page: Lees de bevestiging voordat je vrijgeeft. Steps: Controleer bewijs, onderdelen en kwaliteit; Kies Ready for Sale en lees het dialoogvenster; Leg het vervolg vast en controleer de opgeslagen status.

The frozen new HTML preserves this generated proposal for reproduction. It does not define approved instructional scope or supersede the guide specification, [authoring method](../authoring-method.md), or owner decisions. Resolve conflicting walkthroughs before a new revision. Earlier artifacts and acceptance decisions are unchanged. Generator: scripts/manuals/generate-aligned-guides-2026-10-01.mjs; hash-locked v6 input.

## Purpose

Review the transferred asset and its evidence, then record whether it is
released or returned for correction.

The v7 draft uses `QA Hold`, `Ready for Sale`, and `Being Processed`.
Protected release requires reading and confirming the displayed dialog.
Missing work follows Cancel and repair; an authorized override is a separate,
reasoned exception. Every status decision ends with checking the saved result.

## Steps

1. `Open de wachtende beoordeling` - open the correct asset, verify its name
   and asset tag, and separately confirm that `QA Hold` means it is waiting for
   supervisor review.
2. `Controleer het bewijs` - review current required runs, results, notes and
   warnings. Historical Pass does not satisfy Changed or blocked requirements.
3. `Controleer het apparaat` - compare label, S/N, model type, physical
   condition, registered components, destination, and included parts.
4. `Kies vrijgeven OF terugsturen` - when approved, choose `Ready for Sale`
   and follow page 2's confirmation. When rejected, choose `Being Processed`,
   supply the requested note and return through AST-04 or WF-02. Verify the
   saved status after the application response.

The proof currently names those routes `Ready for Sale` and `Being Processed`.
The focus rectangles surround the status and workflow controls instead of
crossing their labels.

## Evidence

| Label | Canonical source | Job |
| --- | --- | --- |
| 1A | `AST-ASSET-SAVED-MOBILE-01` | Asset name and asset-tag identity |
| 1B | `AST-QA-HANDOFF-MOBILE-01` | Incoming `QA Hold` review state |
| 2A | `AST-WORKFLOW-PASS-MOBILE-01` | Complete workflow summary row without a redundant target |
| 3A | `AST-LABEL-PLACEMENT-PHOTO-01` | Physical device, label, S/N, and model-type comparison |
| 3B | `AST-COMPONENT-REVIEW-MOBILE-01` | Registered-component comparison |
| 4A | `AST-READY-STATUS-MOBILE-01` | Current release-state control with corrected focus padding |

`AST-WORKFLOW-ISSUE-MOBILE-01` remains canonical exception evidence but is not
needed in the compact main path.

The retained first-page captures use `INBIT-HG0421` / `HP ProBook 450 G8` /
`5CD1234ABC`. Page 2 adds separate local AA0003/AA0004 examples for current
guards and a cancelled release dialog. These are recognition examples, not
one continuous release trial. See the batch provenance for hashes and crops.

## Complete When

The supervisor's decision and next route are saved and understandable: release
or return for correction.

## Related Guides

- AST-04 Werk afronden en overdragen
- WF-02 Workflow uitvoeren en afronden
- SC-01 Asset vinden en openen
- HELP-01 Problemen en hulp

## Focused Candidate - 2026-09-08

The separate [v6 review](../reviews/AST-05-v6.md)
records the focused follow-up to baseline v5, with the exact
PDF and checksum. The owner requested this pass across all existing guides
after reviewing WF-01 v12 positively. Earlier selections and their policies
remain traceable; this candidate is not automatically accepted. See the
[set comparison](../reviews/guide-set-comparison-2026-09-08.md) for scope,
source/crop exceptions, and remaining operational or evidence gaps.
