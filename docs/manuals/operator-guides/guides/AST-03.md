# AST-03 Asset Registreren En Labelen

| Field | Current value |
| --- | --- |
| Status | Internal review candidate for V1; exact v14 accepted 2026-08-25 |
| Family | AST |
| Type | Two-sided detail task |
| Current version | `AST-03-asset-registreren-en-labelen-v14` |
| Page model | Two-sided |
| Layout recipe | `stacked-step-flow` with captions, `parallel-visual-choice`, `reused-evidence`, `inline-warning`, and `two-sided-continuation` |
| Generator | `scripts/manuals/generate-revised-guide-set.mjs`; use `SNIPEIT_AST03_VERSION=14` explicitly when reproducing this version |
| Role | Supervisor |
| Needed | Physical device, asset tag, S/N, exact model/type details, and label printer |
| Prerequisite | Ingelogd (AC-01 Login) |

## Purpose

Register known hardware once, verify its exact identity and model type, assign
the current work status, print and attach its QR label, and confirm that the
label opens the same asset.

Work location is optional registration metadata. It is not a numbered primary
step in this guide.

Version 14 removes the step 4 lower-left text collision and keeps both 4A and
4B captions inside their image block without changing the approved two-page
flow.

## Page 1: Register

1. `Ga naar Nieuwe aanmaken` - start on the dashboard, open `Apparaten`, then
   use either `Nieuwe aanmaken` in the top bar or the `+` action in the
   hardware toolbar.
2. `Vul asset tag en serienummer in` - keep the proposed tag unless an assigned
   tag requires `Unlock`; copy the value after `S/N` from the device label.
   Example: `5CD1234ABC`. Do not enter Product ID or `ProdID` as the serial.
3. `Kies categorie, model en type` - choose the category and exact model, then
   compare the type/model code after the model name. On an HP device, Product
   ID or P/N may identify that model type.
4. `Kies status en sla op` - select `Being Processed` in the interface field
   named `Status`, save once, and verify the saved asset tag, status, asset
   name/model, and serial number.

Tag and serial fields uppercase values automatically. `Unlock` only makes the
generated asset tag editable. `Aa` preserves an intentionally different case
and is not part of the normal path.

A duplicate warning is recoverable guidance, not a red stop: open and compare
the existing record before deciding whether another asset should exist.

## Page 2: Label And Verify

5. `Open de opgeslagen asset` - confirm the title and asset tag before printing.
6. `Kies label en print eenmaal` - inspect the QR preview and template, choose
   the correct printer location, and print once.
7. `Plaats het label rechtsonder` - use the real full-device underside photo
   with the front edge facing the reader. Place the label on a flat area at the
   lower right without covering vents, ports, screws, or service labels. The
   7A focus frame identifies the physical QR label in the owner-supplied photo.
8. `Scan ter controle` - follow SC-01 and confirm that the label opens the same
   asset. The repeated scanner photo is omitted; only the opened asset result
   is shown. A mismatched result routes to help without an oversized stop block.

If an existing label is damaged and cannot be scanned, search manually by the
unique Inbit asset tag or serial number. Do not instruct the operator to print
a replacement as the recovery action for this guide.

## Evidence

| Label | Canonical source | Job |
| --- | --- | --- |
| 1A | `DASH-MOBILE-01` | Dashboard `Apparaten` entry before the create alternatives |
| 1B/1C | `AST-REGISTER-ENTRY-MOBILE-01` | Top-bar `Nieuwe aanmaken` and toolbar `+` alternatives with source-measured target frames |
| 2A | `AST-REGISTER-IDENTITY-MOBILE-01` | Tag, `Unlock`, `Aa`, and realistic unsubmitted `S/N` example |
| 3A/3B | `AST-REGISTER-IDENTITY-MOBILE-01` | Category and exact model/type selection |
| 4A | `AST-REGISTER-STATUS-MOBILE-01` | Actual `Status` field with `Being Processed` and the complete `Opslaan` action |
| Page 1 4B | `AST-REGISTER-SAVED-CHECK-01` | Saved asset tag, status, asset name/model, and serial number in one compact detail context |
| Page 2 5A/8A | `AST-ASSET-SAVED-MOBILE-01` | Saved title and navigation with the controlled fictional identity |
| Page 2 6A/6B | `AST-LABEL-CONTROL-MOBILE-01` | QR preview/template and printer location/action |
| Page 2 7A | `AST-LABEL-PLACEMENT-PHOTO-01` | Real full underside, front edge facing the reader, with the attached QR at lower right |

The controlled create form was not submitted. Saved, label, workflow,
component, and status evidence use the same documented screenshot-only
`INBIT-HG0421` / `HP ProBook 450 G8` / `5CD1234ABC` identity. No server record
was created or renamed for that substitution.

The rejected v4 generated placement image remains historical evidence only and
is not used by v5-v14. HP's official ProBook 450 G8 parts locator confirms the
underside orientation and service-tag area, but it does not show Inbit's QR
placement and is therefore reference material rather than guide evidence:
<https://h10032.www1.hp.com/ctg/Manual/c06974341.pdf>.

## Complete When

The asset exists once, has the verified tag, S/N, category, model type, and
current work status, and its safely placed QR label opens that same record.

## Related Guides

- AC-01 Login
- SC-01 Asset vinden en openen
- AST-02 Refurbishment-route
- HELP-01 Problemen en hulp
