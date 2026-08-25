# CMP-04 Component Naar Tray Verplaatsen

| Field | Current value |
| --- | --- |
| Status | Working draft v6; cold-start pass and awaiting exact-version review |
| Family | CMP |
| Type | Detail task |
| Current version | `CMP-04-component-to-tray-v6-draft` |
| Page model | One page |
| Layout recipe | `stacked-step-flow` with `single-visual`, `reused-evidence`, and `inline-stop` |
| Generator | `scripts/manuals/generate-component-followup-guides.mjs` |
| Role | Senior Refurbisher |
| Needed | Correct open asset, identified physical component, intended tray/storage destination |
| Prerequisite | Asset verified (SC-01) |

## Purpose

Remove the correct physical component from an asset, preserve its identity/serial decision, and confirm its intended tray/storage destination.

## Steps

1. `Kies het juiste onderdeel` - compare the component name, tag, and serial number with the physical part, then choose `Naar tray` on the same row.
2. `Controleer het venster` - verify the component name and locked serial number in the confirmation window.
3. `Verwijder en bevestig` - remove the physical part, place it in the operator's tray, and choose `Naar tray bevestigen` once.
4. `Controleer de eindstaat` - open the component and verify `Status: In Tray` and `Asset: N.v.t.`.

## Stop

Stop in step 1 or 2 when component identity does not match. Do not invent or overwrite a serial number. Stop in step 4 when the record remains attached to an asset.

## Evidence Manifest

| Label | Job | Source | Status |
| --- | --- | --- | --- |
| 1A | Tracked row identity | `CMP-NEW-INSTALLED-MOBILE-03` | Ready for draft; canonical reuse |
| 1B | `Naar tray` action on the same row | `CMP-NEW-INSTALLED-MOBILE-03` | Ready for draft; separate crop and target |
| 2A | Locked component and serial confirmation | `CMP-TRAY-CONFIRM-MOBILE-03` | Ready for draft |
| 3A | `Naar tray bevestigen` after physical removal | `CMP-TRAY-CONFIRM-MOBILE-03` | Ready for draft; separate crop and target |
| 4A | `Status: In Tray` | `CMP-TRAY-RESULT-MOBILE-03` | Ready for draft |
| 4B | `Asset: N.v.t.` | `CMP-TRAY-RESULT-MOBILE-03` | Ready for draft; separate crop and target |

## Evidence Note

Version 6 keeps the verified v5 four-step route and focus geometry while naming
the minimum operational role. The confirmation keeps the existing serial
locked. Controlled component `INBIT-C-HH9376` / `CMP02-RAM-0001` was removed
from the asset and now ends in `Status: In Tray` with no asset attached.

## Complete When

The physical component is in the intended tray/storage, is no longer listed on the asset, and its tracked identity is preserved.

## Related Guides

- SC-01 Asset vinden en openen
- CMP-01 Bestaand component plaatsen
- CMP-02 Nieuw component registreren en plaatsen
- WF-02 Workflow uitvoeren en afronden
- HELP-01 Problemen en hulp
