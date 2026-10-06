# WF-01 / WF-02 Comparison Round - 2026-10-01

Status: **two new working drafts, explicitly not accepted**.
Latest WF-01 correction: [v15](WF-01-v15.md) replaces v14's blocked first
example with Standard Diagnostics Start followed by waiting Cleaning, and
replaces "Kies de afgesproken rij". The original paired artifacts below stay
frozen; WF-02 v13 remains unchanged.
The owner's request for new-model revisions does not confer acceptance or
establish that this model produces better manuals. Review the exact outputs.

## Previous And New PDFs

| Guide | Previous comparison version | New proposal | Pages |
| --- | --- | --- | ---: |
| WF-01 | [v13](../../../../resources/manuals/operator-guides/review-rounds/2026-09-10/owner-corrections/WF-01-workflow-starten-v13-draft.pdf), owner-cleared | [v14](../../../../resources/manuals/operator-guides/review-rounds/2026-10-01-workflows/WF-01-workflow-starten-v14-draft.pdf), not accepted | 1 -> 1 |
| WF-02 | [v12](../../../../resources/manuals/operator-guides/review-rounds/2026-09-08/WF-02-complete-workflow-v12-draft.pdf), unaccepted | [v13](../../../../resources/manuals/operator-guides/review-rounds/2026-10-01-workflows/WF-02-complete-workflow-v13-draft.pdf), not accepted | 2 -> 2 |

WF-02's accepted predecessor remains v10. The September 10 accepted package,
September 15 portable kit, current-selection manifests, all older PDFs, and
their acceptance records are retained. New drafts are in their own round;
no combined review PDF or refreshed accepted package was requested or made.

Detailed changes, hashes and open trials:
[WF-01 v14](WF-01-v14.md) and [WF-02 v13](WF-02-v13.md).

## What To Compare

| Question | New draft's intended improvement | Remaining limit |
| --- | --- | --- |
| Can I identify the correct entry/action? | WF-01 shows numbered profile rows and Start/Continue/Edit meaning. | Fresh start and Continue need a controlled live trial. |
| Do I know why a row is unavailable? | Prerequisite/definition warnings are visible; permissions and exceptions have help routes. | Different execution levels/configuration/override states were source-inspected, not all captured. |
| Can I recognize result types without guessing? | WF-02 shows both control and task-card labels. | Required/optional and failure paths need representative user trials. |
| Do I know whether my evidence saved? | Updated note timestamp and photo-thumbnail checks are explicit. | New note/photo writes and error recovery were not exercised. |
| Do I know where Done takes me? | Both button states are shown; the same-asset Workflows return was observed. | Pending saves, failed-result warnings, and all-done process handoff need trials. |
| Is the instruction still readable? | Original page counts, steps, useful screen context, help, and handoffs are retained. | Physical A4 and first-time-reader review are pending. |

## Source And Capture Boundaries

The local Docker app is mounted from the other agent's
`codex/refurbisher-followups-2026-09-22` worktree, not this checkout's master.
Initial inspection found head `731bb43c88`; the end-of-block source audit found
`5ee57f58db77`. The seven audited workflow files have no tracked changes
between those commits. Concurrent uncommitted work exists; exact file hashes
are recorded in [source-audit.json](../../../../resources/manuals/operator-guides/review-rounds/2026-10-01-workflows/source-audit.json).
These drafts target the observed development snapshot. No production access,
deployment, or production-alignment claim is part of this manual revision.

Six real JPEG sources use existing `INBIT-AA0003 / HP ProBook 430 G3` data.
Pre-Sale Check run 12 supplies neutral cards and drawer states; Cleaning run
14 supplies the already-answered Done state. The same asset's Shipping Laptop
row supplies Start without submitting it. Existing changed-definition warnings
are deliberately retained rather than replaced with fictional success states.

Browser work was navigation and drawer opening plus the existing answered
run's Done return. No run/result, note, upload, permission, profile, status,
or catalogue mutation was submitted. The normal recent-device view history
may update on asset navigation. English UI labels were not translated inside
screenshots or replaced by injected text. There are no identity overlays.

## Reproduction And Review Decision

The [round README](../../../../resources/manuals/operator-guides/review-rounds/2026-10-01-workflows/README.md)
describes frozen HTML, source inputs, evidence and generation commands.
All new outputs pass focused geometry/text/page checks and final 144-dpi
visual inspection. Shared component checks pass. These are artifact checks,
not completed operational or usability trials.

Record a separate exact-version decision for each guide after review. Rejecting
one draft does not require rolling back the other or deleting any files.
Until then, accepted selections and known prior clearances remain unchanged.
