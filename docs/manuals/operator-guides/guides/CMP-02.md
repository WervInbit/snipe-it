# CMP-02 Nieuw Component Registreren En Plaatsen

| Field | Current value |
| --- | --- |
| Status | Working draft; awaiting review |
| Family | CMP |
| Type | Detail task with alternatives |
| Current version | `CMP-02-register-install-v2-draft` |
| Page model | One page |
| Layout recipe | `stacked-step-flow` with `parallel-visual-choice`, `reused-evidence`, and `inline-stop` |
| Generator | `scripts/manuals/generate-component-followup-guides.mjs` |
| Role | Authorized refurbisher (deployment role to confirm) |
| Needed | Correct open asset and the new physical component |
| Prerequisite | Asset verified (SC-01) |

## Purpose

Register a new physical component using either an approved component definition or the custom-component path, then install it on the asset.

This guide creates a new tracked component record. When the physical component already has a tracked record in tray/storage, use CMP-01 instead.

## Steps

1. `Open Nieuw component` - open Components, choose `Add / Install Component`, then `Show New Component Form`.
2. `Kies een registratieroute` - use `Use Component Definition` for a known catalog component; use `Custom Component` only for an approved one-off component. Enter the definition/name, serial number, and condition in the selected route.
3. `Plaats en maak aan` - install the component physically, compare the entered identity, then choose `Create And Install` once.
4. `Controleer het asset` - verify `Tracked`, the generated component tag, and the entered serial number appear on the correct asset.

## Stop

- Stop in step 2 when it is unclear whether a catalog definition should exist; ask a supervisor before choosing custom.
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

Version 2 replaces the placeholder five-step page with the verified four-step interface flow. The controlled definition-backed record `INBIT-C-HH9376` / `CMP02-RAM-0001` was created and installed once. The custom route was opened and filled only for evidence; it was not submitted. CMP-04 capture then moved the controlled record to tray, which is its final development state.

## Complete When

The physical component is installed and one correctly identified component record appears on the correct asset.

## Related Guides

- SC-01 Asset vinden en openen
- CMP-01 Bestaand component plaatsen
- CMP-04 Component naar tray verplaatsen
- WF-02 Workflow uitvoeren en afronden
- HELP-01 Problemen en hulp
