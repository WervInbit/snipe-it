# USR-04 v4 - Audit Comparison Proposal

Status: Audit revision round 2026-09-08 - Unaccepted working draft.
Decision: batch design direction rejected by the owner on 2026-09-08;
retain the exact PDF. Individual factual and operational proposals remain
undecided. See [owner feedback](2026-09-08-pilot-direction-feedback.md).
The changes and expected benefits below describe the rejected experiment.

- [Candidate PDF](../../../../resources/manuals/operator-guides/review-rounds/2026-09-08/USR-04-gebruiker-uitschakelen-of-herstellen-v4-draft.pdf): three A4 pages.
- SHA-256: `15370c2dba3db9631292aa4b5e1bd9de75c2000bbf8281c7159e3817e6c59cec`.
- Compare with [frozen v3 draft](../../../../resources/manuals/operator-guides/checkpoints/2026-09-08-before-audit-revisions/drafts/usr-04-gebruiker-uitschakelen-v3-draft.pdf).
  No internally accepted predecessor is recorded.
- In the [comparison PDF](../../../../resources/manuals/operator-guides/review-rounds/2026-09-08/Handleidingen-vergelijking-auditronde-2026-09-08.pdf): v3 pages 4-5, v4 pages 14-16.

## Changes And Tradeoffs

| Change | Expected benefit | Drawback or review question |
| --- | --- | --- |
| Page 1 chooses A=disable, B=delete or C=restore; each page is an independent route with its own steps 1-4 (AUD-03/16). | Stops deletion and restoration from reading as consecutive actions. | Three pages replace two. Page selection and repeated numbering need testing with the intended users. |
| Each route has an explicit ending and route B says not to restore afterwards. | Makes it clear when to put the guide down. | Repeated stopping language may feel excessive to an experienced administrator. |
| Route C step 2 states that restore preserves the earlier login setting (AUD-04). | Removes the false guarantee that restored login is always off. | The conservative proposal hands a retained active flag to the responsible Admin before restoration. This operational choice requires owner review; it is not a new accepted policy. |
| Larger body copy and separate image/title areas (AUD-11/12). | Avoids the earlier text/image collision and makes the ordered actions easier to scan. | Some navigation, assignment and final-state screenshots are replaced by explicit text. Test whether first-time administrators need those visuals restored. |
| Visible example identities use Mila de Boer / Miladb; the unrelated Demo identity is excluded from the checkbox crop (AUD-02). | Avoids showing a switch to a different user during the printed sequence. | This is a control-detail crop, not a new same-account end-to-end evidence capture. That evidence gap remains open. |

Route A retains the earlier ownership-before-disable order. It does not define
an urgent access-revocation procedure. Route B retains the separate deletion
decision and excludes bulk check-in/delete. Route C does not authorize login
reactivation or change group-assignment permission boundaries.

## Sources And Validation

- [Pilot specification](audit-pilot-2026-09-08-specification.md) and
  [isolated generator](../../../../scripts/manuals/generate-audit-pilot-review.mjs).
- Sources used: USR-DEACTIVATED-DESKTOP-01, USR-EDIT-ACTIVATED-DESKTOP-01
  (checkbox only), USR-DELETE-DESKTOP-01, USR-DELETED-LIST-DESKTOP-01,
  and USR-RESTORE-DESKTOP-01.
- Source-code checks: UsersController::getRestore(), user detail/edit views,
  sidebar deleted-user route and delete confirmation controls. Restore calls
  restore without forcing activated=false.
- [Validation record](../../../../resources/manuals/operator-guides/review-rounds/2026-09-08/validation.json): all three pages pass A4/page/text checks,
  component/focus/overlap checks and rendered review. Their comparison-package
  rasters match the standalone pages. Sample instruction text is 9.07 pt.
- No user account was changed, deleted or restored. Physical-print,
  same-account recapture, ownership handoff and observed-user tests remain open.

## Decision And Rollback

Review the three-page structure, restoration precheck and screenshot reduction
separately. The baseline v3 remains selected in the existing draft package;
rejection changes only this candidate's review decision. Keep v4 and its
source snapshot for comparison; do not reuse the version for another visible
revision.
