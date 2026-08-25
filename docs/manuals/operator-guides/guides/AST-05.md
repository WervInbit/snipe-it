# AST-05 Asset Beoordelen En Vrijgeven

| Field | Current value |
| --- | --- |
| Status | Working draft v5; visual-correction pass and awaiting exact-version review |
| Family | AST |
| Type | Detail task |
| Current version | `AST-05-review-release-v5-draft` |
| Page model | One page |
| Layout recipe | `two-column-step-grid` with `reused-evidence`, `inline-warning`, and `inline-return` |
| Generator | `scripts/manuals/generate-revised-guide-set.mjs`; use `SNIPEIT_AST05_VERSION=5` explicitly when reproducing this version |
| Role | Supervisor |
| Needed | Asset awaiting review and access to status changes |
| Prerequisite | Operator handoff completed (AST-04) |

## Purpose

Review the transferred asset and its evidence, then record whether it is
released or returned for correction.

The v5 draft uses the currently deployed `QA Hold`, `Ready for Sale`, and
`Being Processed` labels. It explains each next route and the current automatic
save behavior instead of instructing the supervisor to find a separate Save
action.

Version 5 also renders every guide handoff in help and related guides with its
family color, symbol, code, and full name.

## Steps

1. `Open de wachtende beoordeling` - open the correct asset, verify its name
   and asset tag, and separately confirm that `QA Hold` means it is waiting for
   supervisor review.
2. `Controleer het bewijs` - review workflow results, notes, and warnings;
   release requires complete evidence and `0 Mislukt`.
3. `Controleer het apparaat` - compare label, S/N, model type, physical
   condition, registered components, destination, and included parts.
4. `Leg de uitkomst vast` - when approved, choose `Ready for Sale`; when
   rejected, choose `Being Processed` and return the asset through AST-04 or
   WF-02. Both choices save automatically; verify the visible resulting status.

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

All application captures use the controlled `INBIT-HG0421` / `HP ProBook 450
G8` / `5CD1234ABC` identity.

## Complete When

The supervisor's decision and next route are saved and understandable: release
or return for correction.

## Related Guides

- AST-04 Werk afronden en overdragen
- WF-02 Workflow uitvoeren en afronden
- SC-01 Asset vinden en openen
- HELP-01 Problemen en hulp
