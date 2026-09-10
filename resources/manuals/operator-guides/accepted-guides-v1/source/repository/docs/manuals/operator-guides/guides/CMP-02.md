# CMP-02 Nieuw Component Registreren En Plaatsen

## Latest Correction Candidate - 2026-09-10

Current review candidate: [v6](../reviews/CMP-02-v6.md), owner-cleared for user review.
Recentered radio targets using the selected-control pixels in the canonical -04 screenshots.
This current correction takes precedence over conflicting historical version
notes below. Previous PDFs and exact acceptance records remain unchanged.

| Field | Current value |
| --- | --- |
| Status | Internal review candidate v6; owner cleared for user review 2026-09-10 |
| Family | CMP |
| Type | Detail task with alternatives |
| Current version | `CMP-02-register-install-v6-draft` |
| Page model | One page |
| Layout recipe | `stacked-step-flow` with `parallel-visual-choice`, `reused-evidence`, and targeted `inline-stop` |
| Generator | `scripts/manuals/generate-owner-corrections.mjs CMP-02` |
| Role | Senior Refurbisher |
| Needed | Correct open asset and the new physical component |
| Prerequisite | Asset verified (SC-01) |

## Purpose

Register a new physical component using either an approved component definition or the custom-component path, then install it on the asset.

This guide creates a new tracked component record. When the physical component already has a tracked record in tray/storage, use CMP-01 instead.

Version 4 presents the missing-definition handoff as the full CAT-04 guide
reference and keeps the route text in operator language rather than using an
unexplained guide code in the step body.

## Steps

1. `Open Nieuw component` - open Components, choose `Add / Install Component`, then `Show New Component Form`.
2. `Kies een registratieroute` - use `Gebruik definitie` for a known reusable
   component type. Use `Aangepast` only for one agreed exception that will not
   be reused. If the reusable type is missing, a supervisor follows CAT-04;
   do not choose `Aangepast` automatically. Enter the definition/name, serial
   number, and condition in the selected route.
3. `Plaats en maak aan` - install the component physically, compare the entered identity, then choose `Create And Install` once.
4. `Controleer het asset` - verify `Tracked`, the generated component tag, and the entered serial number appear on the correct asset.

## Stop

- Stop in step 3 for duplicate feedback or a physical/digital identity mismatch.
- Stop in step 4 when the resulting tracked record does not match the physical component.

## Evidence Manifest

| Label | Job | Source | Status |
| --- | --- | --- | --- |
| 1A | Components tab and `Add / Install Component` | `CMP-INSTALL-ENTRY-MOBILE-02` | Ready for draft; canonical reuse |
| 1B | `Show New Component Form` entry | `CMP-NEW-ENTRY-MOBILE-03` | Ready for draft |
| 2A | Definition-backed route with serial and condition | `CMP-NEW-DEFINITION-MOBILE-03` | Ready for draft |
| 2B | Custom route with custom name, serial, and condition | `CMP-NEW-CUSTOM-MOBILE-03` | Ready for draft; no custom record submitted |
| 3A | `Create And Install` after physical placement | `CMP-NEW-DEFINITION-MOBILE-03` | Ready for draft; reused with a separate target |
| 4A | Installed tracked row with generated tag and entered serial | `CMP-NEW-INSTALLED-MOBILE-03` | Ready for draft |

## Evidence Note

Version 3 retains the verified four-step interface flow and evidence from v2,
names the minimum operational role, explains reusable definition versus one-off
custom in operator language, routes missing definitions to CAT-04, and removes
the recoverable route-choice STOP. The controlled definition-backed record
`INBIT-C-HH9376` / `CMP02-RAM-0001` was created and installed once. The custom
route was opened and filled only for evidence; it was not submitted. CMP-04
capture then moved the controlled record to tray, which is its final
development state.

## Complete When

The physical component is installed and one correctly identified component record appears on the correct asset.

## Related Guides

- SC-01 Asset vinden en openen
- CMP-01 Bestaand component plaatsen
- CMP-04 Component naar tray verplaatsen
- HELP-01 Problemen en hulp

## Focused Candidate - 2026-09-08

The separate [v5 review](../reviews/CMP-02-v5.md)
records the focused follow-up to baseline v4, with the exact
PDF and checksum. The owner requested this pass across all existing guides
after reviewing WF-01 v12 positively. Earlier selections and their policies
remain traceable; this candidate is not automatically accepted. See the
[set comparison](../reviews/guide-set-comparison-2026-09-08.md) for scope,
source/crop exceptions, and remaining operational or evidence gaps.

## Owner Feedback Follow-Up - 2026-09-10

MR-04: remeasure v5 image 2A/2B radio focus centres against the actual recaptured sources.
See [owner review TODOs](../reviews/owner-review-todos-2026-09-10.md).
The existing PDF is unchanged; these are next-version or decision items.
