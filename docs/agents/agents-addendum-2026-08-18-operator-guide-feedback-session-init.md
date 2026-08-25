# Session Addendum: Operator Guide Feedback (2026-08-18)

## Scope

- Apply focused operator feedback to AC-01, AST-02, CMP-01, USR-01, WF-01,
  and WF-02.
- Produce new review versions without modifying the eight committed internal
  review candidate PDFs.
- Reuse the committed canonical evidence; no live capture or application
  change is required for this correction set.

## Requested Corrections

- USR-01 step 3 must explicitly remain on `Informatie`, open the collapsed
  `Optionele informatie` bar, and then locate `Groepen`.
- AC-01 requires an Inbit phone and an account; Inbit Snipe-IT is a browser
  shortcut, not an installed phone application.
- CMP-01 is performed by a senior refurbisher.
- AST-02, WF-01, and WF-02 use the Refurbisher role without a senior-role
  requirement.
- WF-01 image 3B must center its focus target on `Bewerk`.

## Safety

- Preserve all unrelated working-tree changes and the committed accepted
  guide package.
- Generate only under ignored proof/output directories until the user accepts
  an exact new version.
- Do not use or modify the live or development application for this task.

## Outcome

- Generated review drafts AC-01 v8, AST-02 v6, CMP-01 v5, USR-01 v9, WF-01
  v10, and WF-02 v11 under `output/pdf/`.
- Preserved accepted-version defaults behind opt-in version environment
  variables in each generator.
- Updated the guide specifications, registry, decisions, inventory, reviews,
  handoff, README, and TODO records for this correction set.

## Validation

- All six outputs passed PDF page-count, A4-size, required-text, stale-text,
  and full-page rendered visual checks.
- USR-01 component geometry passed for 12 badges and five guide chips.
- Regenerated accepted defaults for all six guides. Their seven rendered pages
  are byte-identical to the committed accepted PDFs.
- `npm test` passed after the generator changes; final repository validation is
  recorded in `PROGRESS.md`.
