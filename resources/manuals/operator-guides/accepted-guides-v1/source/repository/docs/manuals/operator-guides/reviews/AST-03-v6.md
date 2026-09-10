# AST-03 v6 Review

| Field | Value |
| --- | --- |
| Previous version | v5 working draft |
| Feedback source | Owner review, 2026-08-18 |
| Impact | Step 4 terminology, save-action framing, and post-save verification |
| Status | Working draft; real placement photo pending |

## Correction

- Use the deployed field label `Status` instead of the invented term
  `werkstatus` in step 4.
- Frame 4A so `Status: Being Processed` and the complete `Opslaan` button are
  both visible.
- Replace the generic saved-title crop in 4B with one compact detail capture
  containing the saved asset tag, status, asset name/model, and serial number.
- State the same four checks in the step body and 4B caption.
- Keep the v5 create-button geometry and page-2 real-photo requirement.

## Evidence Method

`AST-REGISTER-SAVED-CHECK-01` was captured from an existing development asset
in `Being Processed`. The guide identity was substituted only in the capture
DOM, unrelated rows were hidden only for the screenshot, and no server record
was created or changed.

## QA

- 4A includes the actual `Status` label, selected value, and full save button.
- 4B contains all four named post-save checks without extending into the help
  section.
- Both A4 pages remain within their page bounds.
- Exact-version review remains blocked only by the real page-2 step-3 placement
  photo.
