# CMP-01 Bestaand Component Plaatsen

| Field | Current value |
| --- | --- |
| Status | Internal review candidate for V1 |
| Family | CMP |
| Type | Detail task |
| Current version | `CMP-01-install-existing-v4-draft` |
| Page model | One page |
| Layout recipe | `stacked-step-flow` with `single-visual`, `reused-evidence`, and `inline-stop` |
| Generator | `scripts/manuals/generate-component-guide-review.mjs` |
| Role | Authorized refurbisher (deployment role to confirm) |
| Needed | Correct open asset and a physical component with a component tag in tray/storage |
| Prerequisite | Asset verified (SC-01) |

## Purpose

Select an existing tracked component from tray/storage, verify its identity, install it physically, and confirm the same tracked identity appears on the asset.

## Steps

1. `Open Componenten` - open the Components tab on the verified asset and choose `Add / Install Component`.
2. `Kies hetzelfde onderdeel` - select the tray/storage record and compare its source, component tag, and name with the physical part.
3. `Plaats en installeer` - install the component physically, then choose `Install` once.
4. `Controleer de koppeling` - verify `Tracked`, the same component tag, and the same serial number appear on the asset.

## Stop

Inside step 2: stop when the component tag, name, or physical part does not match. Inside step 4: stop when the installed record does not match the physical part. A condition warning belongs in compact help and requires supervisor review before installation.

## Evidence Manifest

| Label | Job | Source | Status |
| --- | --- | --- | --- |
| 1A | Component tab and `Add / Install Component` action | `CMP-INSTALL-ENTRY-MOBILE-02` | Ready for draft |
| 2A | Selected tray record with source, component tag, and name | `CMP-INSTALL-SELECTED-MOBILE-02` | Ready for draft |
| 3A | One-time `Install` action after physical placement | `CMP-INSTALL-SELECTED-MOBILE-02` | Ready for draft; reused with a separate target |
| 4A | Installed tracked row with matching tag and serial | `CMP-INSTALL-RESULT-MOBILE-02` | Ready for draft |

## Evidence Note

Version 4 keeps the actual four-step interface flow and aligns every target mark to measured control bounds. Step 1 has separate targets for the component icon and `Add / Install Component`; the step 3 target has symmetric padding around `Install`; and step 4 separately identifies `Tracked` and the tag/serial values without crossing their headings. The controlled component `INBIT-C-UW4626` with serial `CMP01-RAM-0001` was moved to the Codex tray, set to condition `Good`, captured in the selector, and installed back into the controlled asset. Printed crops exclude the development URL, controlled asset label, and workflow-attention banner.

## Complete When

The physical component is installed and the same component tag and serial number appear as a tracked component on the correct asset.

## Related Guides

- SC-01 Asset vinden en openen
- CMP-02 Nieuw component registreren en plaatsen
- CMP-04 Component naar tray verplaatsen
- WF-02 Workflow uitvoeren en afronden
- HELP-01 Problemen en hulp
