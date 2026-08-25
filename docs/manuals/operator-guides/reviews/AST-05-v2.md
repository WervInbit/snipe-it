# AST-05 v2 Review

| Field | Value |
| --- | --- |
| Previous version | v1, working draft with evidence and release-state gaps |
| Feedback source | Current application and role-capability audit, 2026-08-18 |
| Impact | Release decision, evidence, wording, and layout |
| Status | Working draft; awaiting exact-version review |

## Correction

- Use `QA Hold` as the incoming state and `Ready for Sale` as the approved
  supervisor release state.
- Route a rejected asset back to `Being Processed` and AST-04/WF-02.
- Combine decision, save, and final-status verification into one step rather
  than duplicating the same status screenshot.
- Use the current workflow summary, physical label, component list, and status
  evidence in a compact two-column grid.

## QA

- Generated successfully as one A4 page with no missing evidence source.
- Full-page raster review passed for focus alignment, evidence containment,
  help, done, related guides, and footer containment.
- Extracted-text QA found `QA Hold`, `Ready for Sale`, and `Being Processed`
  and no development URL or unresolved evidence label.
- Portable package validation passed with 59 canonical evidence files and 14
  maintained scripts.
