# AST-03 Asset Registreren En Labelen

| Field | Current value |
| --- | --- |
| Status | Working draft; evidence incomplete |
| Family | AST |
| Type | Two-sided detail task |
| Current version | `AST-03-register-label-v1-draft` |
| Page model | Two-sided |
| Layout recipe | `stacked-step-flow` with `reused-evidence`, `inline-stop`, and `two-sided-continuation` |
| Generator | `scripts/manuals/generate-revised-guide-set.mjs` |
| Role | Supervisor / asset creator |
| Needed | Physical device, approved asset tag, serial/model details, label printer |
| Prerequisite | Ingelogd (AC-01 Login) |

## Purpose

Register known hardware, save the correct asset record, print and attach its QR label, then verify the label by opening the asset.

## Front: Register

1. `Open Nieuw asset` - start from the asset list or approved create shortcut.
2. `Vul identiteit in` - enter asset tag and serial; select the correct category, model, and variant.
3. `Vul plaatsing in` - select the agreed status/location and add only required notes.
4. `Sla op` - save once and recognize the created asset page.

Inline stop in step 2: stop when the model/variant or asset tag is unclear; do not guess or create a duplicate.

## Back: Label And Verify

1. `Open het opgeslagen asset` - confirm the title and asset tag.
2. `Print het QR-label` - use the asset label control and the approved printer.
3. `Plaats het label` - place it at the agreed visible location without covering vents, ports, screws, or service labels.
4. `Scan ter controle` - use SC-01 and confirm the label opens the same asset.

## Evidence Needed

| Label | Job | Status |
| --- | --- | --- |
| F1A | New asset entry/button | Controlled capture required |
| F2A | Asset identity fields with surrounding form context | Controlled capture required |
| F3A | Status/location portion of form | Controlled capture required |
| F4A | Saved asset title/tag | Reusable asset-detail evidence available |
| B1A | Saved asset page and label control | Controlled capture required |
| B2A | Print/label widget | Controlled capture required |
| B3A | Physical QR placement example | `SCAN-CAMERA-QR-01` can be reused with a placement crop |
| B4A | Successful opened-asset check | Reuse SC-01 verification evidence |

## Complete When

The asset exists once, the physical QR label is attached safely, and scanning it opens the same verified record.

## Related Guides

- AC-01 Login
- SC-01 Asset vinden en openen
- HELP-01 Problemen en hulp
