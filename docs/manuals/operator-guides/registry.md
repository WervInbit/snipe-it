# Operator Guide Registry

## Guide Review Disposition On 2026-10-06

AC-01 v10: **owner-rejected**. SC-01 v13: **requires rework**. The
[twenty-five-guide audit](reviews/guide-purpose-and-quality-audit-2026-10-06.md)
recommends an editorial hold on the nineteen October batch replacements;
other seventeen have no new owner decision. WF-01 v15/WF-02 v13 and the four
unchanged guides retain their prior statuses. Preserved generation-time
manifests and the tables below are historical, not evidence of later approval.
Accepted/runtime selection remains unchanged. Reproduction HTML does not
override the established task specification, branching or image-size rules.

## Application-Aligned Review Drafts - 2026-10-01

19 affected guides now have separate unaccepted revisions: 66 A4 pages, with fourteen added instructional pages. The [batch comparison](reviews/aligned-guides-2026-10-01.md) lists each old/new PDF and its scope. A [25-guide review index](../../../output/manuals/aligned-guide-review-kit-2026-10-01/index.html) includes WF-01 v15/WF-02 v13 plus four unchanged guides. No accepted/runtime selection or historical PDF changed. Physical printing, controlled-user/save trials and exact-version review remain pending.


## Separate Unaccepted Workflow Candidates - 2026-10-01

| Guide | New authoring candidate | Pages | Review |
| --- | --- | ---: | --- |
| WF-01 | v15; Working draft, explicitly not accepted | 1 | [Exact artifact and review](reviews/WF-01-v15.md) |
| WF-02 | v13; Working draft, explicitly not accepted | 2 | [Exact artifact and review](reviews/WF-02-v13.md) |

Generator: `scripts/manuals/generate-workflow-revisions-2026-10-01.mjs`.
Artifact root: `resources/manuals/operator-guides/review-rounds/2026-10-01-workflows`.
WF-01 v15 uses `scripts/manuals/generate-wf01-v15.mjs` and the separate
`resources/manuals/operator-guides/review-rounds/2026-10-01-wf01-v15` round.
WF-01 v14 remains retained in the original paired round.
These candidates use the existing one-page two-column and two-sided stacked
recipes. Default accepted/runtime selections remain unchanged. The authoring
specifications describe these proposals; historical matrices below describe
their earlier selections. See the [paired comparison](reviews/workflow-revisions-2026-10-01.md).

## USR-05 / CAT-05 / CAT-06 V2 - 2026-09-15

The revised working drafts are USR-05 v2 (3 pages), CAT-05 v2 (8 pages), and
CAT-06 v2 (3 pages). See the [revision review](reviews/USR-05-CAT-05-CAT-06-v2-2026-09-15.md) for changes,
exact artifacts, checks and remaining operator trials. The three v1 PDFs and
all accepted artifacts are retained unchanged; v2 is not acceptance.


## Accepted Guide Folder - 2026-09-10

The [accepted-guides-v1 manifest](../../../resources/manuals/operator-guides/accepted-guides-v1/manifest.json)
selects 16 current accepted versions / 27 pages, with seven older accepted
PDFs kept separately. It combines the fourteen current owner-cleared versions
with the existing WF-02 v10 and CMP-01 v4 acceptances. Their newer v12/v6
proposals remain unaccepted. See the [package README](../../../resources/manuals/operator-guides/accepted-guides-v1/README.md)
for the exact file table and portable authoring/regeneration commands.

## Owner Clearance For User Review - 2026-09-10

The owner cleared the ten current changed versions in the v5 pair for review
by other users. They are now Internal review candidates; the exact PDFs stay
unchanged. Use the [dated clearance and hashes](reviews/owner-review-readiness-2026-09-10.md)
and current-guide-selection-v4.json for current status. Earlier four exact
acceptances remain; the separate prototype is outside this clearance.
Other-user feedback and third-party approval are still pending. Earlier
generation-time draft/pending wording below is historical for these versions.

## Implemented Owner Corrections - 2026-09-10

Use the [correction review and plain v4 pair](reviews/owner-corrections-2026-09-10.md)
for current review: ten revised guides, 22 guides total, previous 48 pages and
current 47 pages. USR-04 v6 is disable-login only and requires a new review.
Earlier exact acceptances remain. The field-to-result prototype is separate.
Testing completed is the preferred future QA label; no Awaiting approval
state or application status change is included. Older sections below are history.

Latest review: [owner TODOs - 2026-09-10](reviews/owner-review-todos-2026-09-10.md).
USR-04 v5 is explicitly not accepted and requires a scope/flow redesign.
Other noted fixes are future revisions; the general draft assessment does not
replace the exact acceptance records. Keep all current artifact bytes.

## Exact Acceptances And Current Bundle - 2026-09-10

AC-01 v9, AC-02 v4, AST-03 v15 and AST-04 v6 are accepted by the owner.
The [current selection and review bundle](reviews/current-set-review-2026-09-10.md)
contains all 22 latest review versions with exact hashes and acceptance states.
The production matrix below advances those four selections; other rows retain
their baseline selection while the separate current review versions await decisions.
Historical package and generation manifests are preserved, not republished.

## Separate Focused Candidates - 2026-09-08

The owner reviewed WF-01 v12 positively and authorized the same approach for
all existing guides. The [comparison index](reviews/guide-set-comparison-2026-09-08.md)
lists twenty newly versioned candidates, their baselines, hashes and review
records. WF-01 v12 and CMP-04 v6 are retained. Selected/accepted versions in
the historical manifests below remain unchanged; the 2026-09-10 decisions
above advance four live registry selections; the new round must not be confused with a blanket acceptance.

Status: authoritative human-readable map from guide scope to current version,
layout, generator, and review artifact.

The JavaScript `GUIDE_REGISTRY` remains the runtime source for names, families,
colors, references, and artifact status. This document owns production metadata
that is not yet represented in code. The two sources must agree on guide name,
family, and status.

## Field Contract

Every active guide specification declares these maintenance fields:

| Field | Meaning |
| --- | --- |
| `Status` | Review state of the exact current version. |
| `Family` | Visual and reference family. |
| `Type` | Operator-task classification from `system.md`. |
| `Current version` | Exact generated artifact, or `planned` before generation. |
| `Page model` | One page, two-sided, or unknown pending investigation. |
| `Layout recipe` | Base recipe and modifiers from `layouts.md`. |
| `Generator` | Script that reproduces the current draft or accepted version. |

Role, needed items, prerequisites, evidence, warnings, completion state, and
related guides remain in the individual guide specification.

The [CAT guide-set plan](catalog-guide-plan.md) controls topic placement and
handoffs across CAT-00 through CAT-06. It does not change a current version or
artifact status by itself; each CAT specification and registry row is updated
when its next exact version is prepared.

The [frozen 2026-09-08 baseline](reviews/baseline-2026-09-08.md) records the
exact versions and acceptance states before new-model audit revisions. New
candidates belong to the separate
[audit revision round](reviews/audit-revision-round-2026-09-08.md); this
checkpoint does not change any version or status in the matrix below.

## Current Production Matrix

The matrix includes the ten current correction candidates and the four exact acceptances above. Other rows retain their older production selections; use the final 22-guide selection for the complete current review set. Separate unaccepted audit
proposals are WF-01 v11 (one page), USR-04 v4 (three pages), and CAT-04 v3
(six pages), generated by `scripts/manuals/generate-audit-pilot-review.mjs`.
Their artifact root is `resources/manuals/operator-guides/review-rounds/2026-09-08`.
Use their [round ledger](reviews/audit-revision-round-2026-09-08.md) for exact
PDFs and decisions. The owner rejected this batch's design direction on
2026-09-08; these retained proposals do not switch this selection.

The later [WF-01 v12](reviews/WF-01-v12.md) is a one-page unaccepted proposal
from v10, generated by `scripts/manuals/generate-wf01-v12.mjs`. Its exact
`WF-01-workflow-starten-v12-draft.pdf` is retained in the same review-round
directory with a separate v12 manifest. It preserves the selected baseline's
content and layout while clarifying the step-3 choice; the owner reviewed the direction positively, with exact acceptance not yet recorded.

| Guide | Version and status | Pages | Layout | Generator |
| --- | --- | ---: | --- | --- |
| AC-01 Login | v9; Internal review candidate; accepted 2026-09-10 | 1 | `compact-horizontal-sequence` | `generate-guide-followups.mjs AC-01` (frozen round inputs) |
| AC-02 Eigen wachtwoord wijzigen | v4; Internal review candidate; accepted 2026-09-10 | 1 | `stacked-step-flow` | `generate-guide-followups.mjs AC-02` (frozen round inputs) |
| SC-01 Asset vinden en openen | v12; Internal review candidate; cleared for user review 2026-09-10 | 1 | `mixed-asymmetric-flow` | `generate-owner-corrections.mjs SC-01` |
| AST-02 Refurbishment route | v8; Internal review candidate; cleared for user review 2026-09-10 | 1 | `route-list` | `generate-owner-corrections.mjs AST-02` |
| AST-03 Asset registreren en labelen | v15; Internal review candidate; accepted 2026-09-10 | 2 | `stacked-step-flow` + captions + alternatives + `two-sided-continuation` | `generate-guide-followups.mjs AST-03` (frozen round inputs) |
| AST-04 Werk afronden en overdragen | v6; Internal review candidate; accepted 2026-09-10 | 1 | `stacked-step-flow` + captions | `generate-guide-followups.mjs AST-04` (frozen round inputs) |
| AST-05 Asset beoordelen en vrijgeven | v5 draft; visual-correction pass, awaiting exact-version review | 1 | `two-column-step-grid` + `inline-return` | `generate-revised-guide-set.mjs` |
| WF-01 Workflow starten | v13; Internal review candidate; cleared for user review 2026-09-10 | 1 | `two-column-step-grid` + `inline-route-alternative` | `generate-owner-corrections.mjs WF-01` |
| WF-02 Workflow uitvoeren en afronden | v11 draft; v10 remains Internal review candidate | 2 | `stacked-step-flow` + `two-sided-continuation` | `generate-workflow-guide-review.mjs` |
| CMP-01 Bestaand component plaatsen | v5 draft; v4 remains Internal review candidate | 1 | `stacked-step-flow` | `generate-component-guide-review.mjs` |
| CMP-02 Nieuw component registreren en plaatsen | v6; Internal review candidate; cleared for user review 2026-09-10 | 1 | `stacked-step-flow` + `parallel-visual-choice` | `generate-owner-corrections.mjs CMP-02` |
| CMP-04 Component naar tray verplaatsen | v7; Internal review candidate; cleared for user review 2026-09-10 | 1 | `stacked-step-flow` | `generate-owner-corrections.mjs CMP-04` |
| HELP-01 Problemen en hulp | v6 draft; Working draft | 1 | `troubleshooting-grid` | `generate-component-followup-guides.mjs` |
| USR-01 Gebruiker toevoegen | v13; Internal review candidate; cleared for user review 2026-09-10 | 1 | `stacked-step-flow` + `mixed-visual-widths` | `generate-owner-corrections.mjs USR-01` |
| USR-02 Rol en rechten wijzigen | v11; Internal review candidate; cleared for user review 2026-09-10 | 1 | `stacked-step-flow` + `mixed-visual-widths` | `generate-owner-corrections.mjs USR-02` |
| USR-03 Wachtwoord resetten | v3 draft; visual-correction pass, awaiting exact-version review | 1 | `stacked-step-flow` + `mixed-visual-widths` | `generate-user-account-guide-review.mjs` |
| USR-04 Gebruiker uitschakelen | v6; Internal review candidate; cleared for user review 2026-09-10 | 1 | `stacked-step-flow` | `generate-owner-corrections.mjs USR-04` |
| USR-05 Groepen beheren | v2 draft; unaccepted, controlled browser-user trial pending | 3 | `group-choice-permissions-test` | `generate-planned-guide-v2.mjs` |
| CAT-00 Catalogus begrijpen | v11; Internal review candidate; cleared for user review 2026-09-10 | 6 | `reference-chapter` + `reused-evidence` + `two-sided-continuation` | `generate-owner-corrections.mjs CAT-00` |
| CAT-01 Model en modelnummer aanmaken | v7; Internal review candidate; cleared for user review 2026-09-10 | 5 | `extended-admin-flow` + three-route decision + warnings + `two-sided-continuation` | `generate-owner-corrections.mjs CAT-01` |
| CAT-02 Modelspecificatie opbouwen | v1 draft; working draft, awaiting exact-version review | 6 | `extended-admin-flow` + decision alternatives + component conflict + `two-sided-continuation` | `generate-catalog-guide-review.mjs` |
| CAT-03 Attributen beheren | v1 draft; corrected `-02` form evidence captured for next revision | 5 | `extended-admin-flow` + alternatives + `two-sided-continuation` | `generate-catalog-guide-review.mjs` |
| CAT-04 Componentdefinities beheren | v2 draft; corrected `-02` form evidence captured for next revision | 6 | `extended-admin-flow` + hierarchy warning + `two-sided-continuation` | `generate-catalog-guide-review.mjs` |
| CAT-05 Bestaande catalogus aanpassen | v2 draft; unaccepted, disposable route execution/review pending | 8 | `independent-maintenance-routes` | `generate-planned-guide-v2.mjs` |
| CAT-06 Productgegevens controleren | v2 draft; unaccepted, source-retention/fallback policy pending | 3 | `worked-source-check` | `generate-planned-guide-v2.mjs` |

AST-01 is retired and replaced by SC-01. It remains in the historical guide
specification and evidence inventory but not in the active production matrix.

## Artifact Roots

| Guide or batch | Current repository artifact or generated root |
| --- | --- |
| AC-01 | `resources/manuals/operator-guides/review-rounds/2026-09-08/AC-01-login-v9-draft.pdf`; exact owner-accepted v9; earlier artifacts retained |
| AC-02 | `resources/manuals/operator-guides/review-rounds/2026-09-08/ac-02-eigen-wachtwoord-wijzigen-v4-draft.pdf`; exact owner-accepted v4; earlier artifacts retained |
| SC-01 | `resources/manuals/operator-guides/review-rounds/2026-09-10/owner-corrections/SC-01-asset-vinden-en-openen-v12-draft.pdf`; owner-cleared for user review; exact PDF unchanged |
| AST-02 | `resources/manuals/operator-guides/review-rounds/2026-09-10/owner-corrections/AST-02-refurbishment-route-v8-draft.pdf`; owner-cleared for user review; exact PDF unchanged |
| AST-03 | `resources/manuals/operator-guides/review-rounds/2026-09-08/AST-03-asset-registreren-en-labelen-v15-draft.pdf`; exact owner-accepted v15; earlier artifacts retained |
| AST-04 | `resources/manuals/operator-guides/review-rounds/2026-09-08/AST-04-complete-handoff-v6-draft.pdf`; exact owner-accepted v6; earlier artifacts retained |
| AST-05 | `resources/manuals/operator-guides/drafts/AST-05-review-release-v5-draft.pdf` |
| WF-01 | `resources/manuals/operator-guides/review-rounds/2026-09-10/owner-corrections/WF-01-workflow-starten-v13-draft.pdf`; owner-cleared for user review; exact PDF unchanged |
| WF-02 | `resources/manuals/operator-guides/drafts/WF-02-complete-workflow-v11-draft.pdf`; accepted v10 remains in repository resources |
| CMP-01 | `resources/manuals/operator-guides/drafts/CMP-01-install-existing-v5-draft.pdf`; accepted v4 remains in repository resources |
| CMP-02 | `resources/manuals/operator-guides/review-rounds/2026-09-10/owner-corrections/CMP-02-register-install-v6-draft.pdf`; owner-cleared for user review; exact PDF unchanged |
| CMP-04 | `resources/manuals/operator-guides/review-rounds/2026-09-10/owner-corrections/CMP-04-component-to-tray-v7-draft.pdf`; owner-cleared for user review; exact PDF unchanged |
| HELP-01 | `resources/manuals/operator-guides/drafts/HELP-01-problems-v6-draft.pdf` |
| USR-01 | `resources/manuals/operator-guides/review-rounds/2026-09-10/owner-reference-followups/usr-01-gebruiker-toevoegen-v13-draft.pdf`; owner-cleared for user review; exact PDF unchanged |
| USR-02 | `resources/manuals/operator-guides/review-rounds/2026-09-10/owner-reference-followups/usr-02-rol-en-rechten-wijzigen-v11-draft.pdf`; owner-cleared for user review; exact PDF unchanged |
| USR-03 | `resources/manuals/operator-guides/drafts/usr-03-wachtwoord-resetten-v3-draft.pdf` |
| USR-04 | `resources/manuals/operator-guides/review-rounds/2026-09-10/owner-corrections/USR-04-gebruiker-uitschakelen-of-herstellen-v6-draft.pdf`; owner-cleared for user review; exact PDF unchanged |
| USR-05 | `resources/manuals/operator-guides/drafts/usr-05-groepen-beheren-v2-draft.pdf`; unaccepted working draft |
| CAT-00 | `resources/manuals/operator-guides/review-rounds/2026-09-10/owner-corrections/CAT-00-catalogus-begrijpen-v11-draft.pdf`; owner-cleared for user review; exact PDF unchanged |
| CAT-01 | `resources/manuals/operator-guides/review-rounds/2026-09-10/owner-corrections/CAT-01-model-en-modelnummer-aanmaken-v7-draft.pdf`; owner-cleared for user review; exact PDF unchanged |
| CAT-03 | `resources/manuals/operator-guides/drafts/CAT-03-attributen-beheren-v1-draft.pdf`; unaccepted working draft |
| CAT-04 | `resources/manuals/operator-guides/drafts/CAT-04-componentdefinities-beheren-v2-draft.pdf`; unaccepted working draft |
| CAT-05 | `resources/manuals/operator-guides/drafts/cat-05-varianten-en-lifecycle-beheren-v2-draft.pdf`; unaccepted working draft |
| CAT-06 | `resources/manuals/operator-guides/drafts/cat-06-catalogus-controleren-en-bronnen-v2-draft.pdf`; verification-only unaccepted working draft; current visible title is Productgegevens controleren |

Committed binary inputs live under `resources/manuals/operator-guides/`.
Generated and superseded proofs remain under ignored output or the historical
workstation archive.

## Accepted Artifact Protection

An `Internal review candidate` or `Third-party approved` row refers to one
exact version, not the guide code in general. A visible change creates a new
version and leaves the accepted artifact unchanged.

The repository PDF manifest contains all nine exact internal-review candidates,
including USR-01 v8 and USR-02 v7. The older six-guide workstation package is
retained only as review history.

The runtime registry currently stores one code-level status rather than both a
latest draft and a last accepted version. Until version metadata is added, its
`Internal review candidate` status continues to identify the frozen accepted
artifact while this matrix identifies the newer working draft.

## Synchronization Checks

Before publishing a new version, verify:

- guide name, family, and status agree with runtime `GUIDE_REGISTRY`;
- version and layout agree with this matrix and the guide specification;
- generator output name agrees with the recorded current version;
- page count agrees with the page model;
- every evidence ID exists in `screenshots.md`;
- the review record identifies the previous version and all intentional
  differences;
- accepted artifacts and their generator snapshots remain untouched.
