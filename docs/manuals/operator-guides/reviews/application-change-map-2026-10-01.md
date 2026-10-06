# Application Change Map For Manual Revisions - 2026-10-01

Status: initial source/specification assessment for the new manuals session.
No guide PDF, version selection, or acceptance status changes in this record.

## Baseline And Ownership

Use [maintenance.md](../maintenance.md) for versioning and change propagation.
Preserve the exact [accepted September 10 package](../../../../resources/manuals/operator-guides/accepted-guides-v1/README.md):
16 current guides / 27 pages, plus seven historical accepted PDFs. Internal
review clearance does not prove current application alignment or user trials.

The September 15 portable current kit records 25 codes / 61 pages, combining
the 22-guide current review selection with USR-05/CAT-05/CAT-06 v2. It contains
unaccepted drafts and is not a replacement accepted package. See the
[v2 review](USR-05-CAT-05-CAT-06-v2-2026-09-15.md) for pending controlled-user,
disposable-data, print, and exact-version reviews.

Another agent is implementing project changes. This session begins with new
documentation notes; coordinate overlapping specifications, shared renderers,
evidence, and progress entries before further edits. Validate completed changes
against source and the intended release UI rather than treating backlog text
as implemented behavior.

## Implemented Changes To Reconcile First

These findings compare current source and guide specifications. Printed PDF
pages and live UI paths still need a focused audit before authoring revisions.

| Priority | Guides | Application behavior / current gap | Revision and evidence needed |
| --- | --- | --- | --- |
| 1 | WF-01; HELP-01; AST-02 references | September progression adds ordered applicable profiles, prerequisites, repeat policy, execution levels, and configuration blockers. WF-01 still describes profile selection followed by a simple start-or-continue choice using earlier evidence. | Capture permitted first start, continue, blocked prerequisite, insufficient execution level, repeat request, and Needs configuration. Explain visible blockers and the authorized help route; keep start and continue distinct. |
| 1 | WF-02; AST-04; AST-05 | Required Pass/Done results determine prerequisite success; optional items do not hold progression. A run can have all required answers yet fail prerequisite success. Current-run ordering uses newest start, and completed runs remain editable. WF-02's saved-count completion wording does not explain those distinctions. | Separate truthful recording, operational completion, prerequisite success, and sale readiness. Capture failed/completed and successfully completed results; explain edits, current-run identity, and the next permitted workflow without implying an automatic lock. |
| 1 | AST-05; HELP-01 | Protected Ready for Sale/Sold transitions require a cancel/confirm dialog, with combined workflow/component issues and an authorized reasoned override when issues exist. AST-05 currently says both release and return choices save automatically. | Revise the release branch to include dialog inspection, cancel/confirm, issue handling, and visible saved result. Capture clean confirmation, cancellation, blocked issues, and the authorized override route. Verify the ordinary return branch independently. |
| 2 | AST-03; SC-01; CMP-02; HELP-01 | September identifier changes introduce numeric-first reservations, explicit duplicate-tag/serial acceptance, and ambiguous scan selection. Determine coverage in the selected PDFs; this initialization does not establish which printed branches are missing. | Compare exact selected versions against reservation/gap behavior, duplicate warnings, and record selection. Preserve existing labels and distinguish physical device identity from a reused identifier. |
| 2 | USR-02; USR-05 and role context in WF/AST guides | Workflow execution levels and dedicated override rights alter what users can do. Existing group/direct-user precedence remains relevant. | Map actual permission controls and level restrictions to role context, permitted actions, denial/help paths, and controlled-user trials. Do not assume a general role name grants every action. |

Source anchors: [fork notes](../../../fork-notes.md),
[WorkflowProgressionService](../../../../app/Services/WorkflowProgressionService.php),
[WorkflowReadinessService](../../../../app/Services/WorkflowReadinessService.php),
[TestRun](../../../../app/Models/TestRun.php),
[WorkflowProfile](../../../../app/Models/WorkflowProfile.php),
[asset status guard](../../../../app/Services/Assets/AssetStatusTransitionGuardService.php),
[asset detail UI](../../../../resources/views/hardware/view.blade.php), and
[sequential identifiers](../../../sequential-identifiers.md).

Guide comparisons: [WF-01](../guides/WF-01.md), [WF-02](../guides/WF-02.md),
[AST-05](../guides/AST-05.md), and [AC-02](../guides/AC-02.md).

## Pending Refurbisher Changes: Track, Then Confirm Implementation

The [refurbisher backlog](../../../plans/refurbisher-review-followups-2026-09-17.md)
contains owner decisions and an explicit G-01 through G-09 guide-update list.
At initialization its recorded decisions are planning evidence; verify the
other agent's delivered source and configured environment before presenting
them as current instructions.

| Guides | Dependencies to verify before revision |
| --- | --- |
| AC-01 / AC-02 / USR-03 | Mobile login wording, retained Remember Me, compact refurbisher account menu, local-password exceptions, and actual enforced password requirements. AC-02 currently assumes account-menu access for every local account. |
| SC-01 / HELP-01 | Scan QR wording, camera permission/retry guidance, browser/device recovery, manual fallback, and successful open/selection behavior. Serial OCR is outside this backlog. |
| AST-03 / AST-02 | Ordinary versus Senior asset-creation rights, dashboard create entry, laptop step-1B mismatch, recent-device rows, post-create return, serial location, and QR printing. |
| AST-04 / AST-05 / USR-02 / USR-05 | Owner-configured Dutch labels, status visibility/use rights, required notes, quality permission, and protected final-state exits. Do not hard-code the planned role/status matrix or invent a QA status. |
| WF-01 / WF-02 / AST-04 | Disabled-until-complete Klaar, return to the same asset's Workflows tab, all-done message, failed-item evidence, and configured profile/item ordering and diagnostic content. |
| CAT-02 / CAT-03 / CAT-04 / CAT-05 and incoming CAT references | Complete attribute discoverability, manual assignment, lifecycle/visibility, component/manual conflicts, and preservation of administrator choices during foundation reseeding. Retain the separate CAT-05/CAT-06 title-reference follow-up. |

## Working Order And Verification

1. Freeze the intended application revision and identify which features the
   other agent has finished. Check the local capture UI against that revision.
2. Audit the exact current WF-01/WF-02 and AST-05 PDF pages, specification,
   generator, evidence IDs, and cross-guide references against the source gaps.
3. Revise the narrowest authoritative specification first, then capture agreed
   disposable states and create new, unused integer versions. Preserve useful
   screenshots, hints, help, and established task-dependent layouts.
4. Apply dependent AST/HELP/USR revisions and then the pending refurbisher
   branches as their implementation and owner configuration stabilize.
5. Run focused package/text/reference checks, render every changed PDF page,
   inspect A4 readability, and record operational trials and exact-version
   decisions. Integrity verification alone does not close usability or behavior.

Initialization verification: the accepted package's standalone verifier passed
for 16 guide codes, seven historical PDFs, and 369 files. No live application
trial, screenshot recapture, full PDF audit, or new acceptance occurred here.
