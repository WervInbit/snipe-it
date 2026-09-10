# AST-03 v3 Review

| Field | Value |
| --- | --- |
| Previous version | v2 evidence-ready working draft |
| Feedback source | Owner review, 2026-08-18 |
| Impact | Registration content, warnings, evidence crops, label placement, and layout |
| Status | Working draft; awaiting exact-version review |

## Correction

- Add the hardware-toolbar `+` as an equal create alternative to the top-bar
  `Nieuwe aanmaken` action.
- Replace the forced identity stop with an amber duplicate warning.
- Explain `Unlock`, automatic uppercase behavior, and the `Aa` preserve-case
  exception.
- Use a realistic HP-style `S/N` example (`5CD1234ABC`) and explicitly exclude
  Product ID/`ProdID` from the serial field.
- Give category, model, and model type their own detailed step.
- Remove location from the numbered primary path.
- Split the QR-print evidence and show the physical label at the lower-right
  underside with more device context.
- Remove the red stop from scan verification and route a mismatch to help.

## Evidence Safety

- The create-form capture was refreshed with `5CD1234ABC` but was not
  submitted.
- Saved-asset and label evidence retain the screenshot-only fictional
  `INBIT-QH0001` identity; no server record was created or renamed.

## QA

- The two-page v3 proof was regenerated after the evidence and documentation
  updates and visually inspected at full-page scale.
- Final package and PDF-content validation are recorded in `PROGRESS.md`.
- Exact-version owner review remains pending.
