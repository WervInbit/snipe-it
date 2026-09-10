# Audit Pilot Specification - 2026-09-08

Status: historical specification of the first comparison batch. The owner
rejected its design direction on 2026-09-08; all changes remain unaccepted.
Use the [feedback and revised approach](2026-09-08-pilot-direction-feedback.md)
for future work. Preserve this specification as the record of what was built.

This specification owns only WF-01 v11, USR-04 v4, and CAT-04 v3 in the
[separate audit round](audit-revision-round-2026-09-08.md). The existing guide
specifications, manifests, and accepted policy remain the baseline while these
proposals are evaluated. Use the shared SVG components through the isolated
`scripts/manuals/generate-audit-pilot-review.mjs` generator. Do not change the
historical generators or global rendering tokens for this experiment.

## WF-01 v11

- Compare with frozen draft v10 and internally accepted v9; one A4 page.
- Recipe: mixed-asymmetric-flow, one explicit inline decision, reused evidence.
- Role: Refurbisher; prerequisite: correct asset verified through SC-01.
- Proposed sequence: open Tests; inspect existing workflows for the agreed
  profile; choose exactly one route (continue an unfinished run or select the
  profile and start once); verify cards and hand off to WF-02.
- Existing or resumed results need not be neutral. Never interpret an uncertain
  or completed row as permission to start another workflow; ask the supervisor.
- The check-before-start order is an AUD-07 proposal for review, not a silent
  replacement of the previously accepted primary-route layout.
- Reuse WF-ENTRY-MOBILE-03 and WF-NEUTRAL-MOBILE-03 without changing screenshots.
- Enlarge instruction text and use full shared guide references. Omit an
  unfinished digital QR from this review candidate.

## USR-04 v4

- Compare with frozen draft v3; no accepted predecessor; three A4 pages.
- Recipe: stacked-step-flow with independent route pages (proposal).
- Role: Admin. The first page routes A=stop login, B=delete only after a
  lifecycle decision, C=restore an already deleted account. Each route ends
  explicitly; page 2 does not lead into page 3 as an ordinary sequence.
- A: verify identity; resolve assigned items and management ownership; edit
  login checkbox and save; verify Login ingeschakeld = Nee.
- B: verify identity and deletion decision; check assignments and management;
  use only the ordinary Delete button and confirmation; verify the identity in
  deleted users and finish. Keep bulk check-in/delete outside this route.
- C: find the existing deleted identity; inspect retained login state and
  intended access before restoration; restore only when that assessment is
  clear; re-open the current record and verify identity, role and login state.
  Restore preserves the activated flag; do not claim it always leaves login
  off. Uncertain access or a retained active flag routes to the responsible
  administrator before restore in this conservative comparison proposal.
- Reuse existing Mila de Boer / Miladb detail, deleted-list and restore images.
  The checkbox-only crop comes from an existing Demo Refurbisher form capture:
  show the control detail without the unrelated identity, and disclose this
  limitation in the review record. Do not claim a newly captured same-account
  end-to-end execution.
- Page 1 uses a compact route index; later pages repeat route identity and
  finish conditions. Larger text trades off against one additional page.

## CAT-04 v3

- Compare with frozen draft v2; no accepted predecessor; six A4 pages.
- Recipe: extended-admin-flow; preserve the six stages and ordinary Supervisor
  scope. Expected rows remain Required under the existing process.
- 1: Search, compare the exact type, choose reuse/Edit/Create New explicitly.
- 2: Explain the seven identity fields separately and retain the no-tag/serial
  rule. Do not invent a manufacturer or change lifecycle state.
- 3: Choose whether expected parts apply. Name Add Expected Subcomponent before
  the field instructions; select an existing definition and a normal quantity.
- 4: Name Add Attribute Contribution, select the attribute and valid value,
  and explain both display checkboxes. Missing definitions route to responsible
  preparation through CAT-03 with an explicit unsaved-form return boundary.
- 5: Remove the simulated Enum overlap screenshot. Explain that the actual
  warning covers numeric attributes contributing to asset specs on both levels.
  Use a labeled explanatory example, not a manufactured application alert.
  Provide save, error recovery and saved-row cleanup ownership explicitly.
- 6: Re-open and verify saved fields/contributions, inspect warnings again, and
  return to the original task. CAT-05 is planned: refer the operator to an Admin
  for cleanup instead of treating that guide as currently executable.
- Use the 2026-09-03 replacement identity/children/contributions/save evidence
  with measured crops. Do not use either simulated overlap capture. The list
  image is an existing-record illustration, not evidence of a new save today.

## Validation And Review Boundary

Check component geometry, text/image separation, crop/target containment,
every rendered A4 page, extracted text, page counts, and new output checksums.
Verify all 29 frozen PDF checksums remain intact. Include the frozen prior
drafts and new candidates in a comparison PDF with a visible separator.
Record changed steps, benefits, drawbacks and unresolved user/evidence checks
for each exact candidate. Physical-print review and observed first-time user
testing remain pending; generation cannot establish independent usability.
