# Operator Guide Registry

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

## Current Production Matrix

| Guide | Version and status | Pages | Layout | Generator |
| --- | --- | ---: | --- | --- |
| AC-01 Login | v8 draft; v6 remains Internal review candidate | 1 | `compact-horizontal-sequence` | `generate-ac01-snipe-proof.mjs` |
| AC-02 Eigen wachtwoord wijzigen | v3 draft; visual-correction pass, awaiting exact-version review | 1 | `stacked-step-flow` | `generate-user-account-guide-review.mjs` |
| SC-01 Asset vinden en openen | v10; Internal review candidate | 1 | `mixed-asymmetric-flow` | `generate-sc01-snipe-proof.mjs` |
| AST-02 Refurbishment route | v6 draft; v5 remains Internal review candidate | 1 | `route-list` | `generate-revised-guide-set.mjs` |
| AST-03 Asset registreren en labelen | v14; Internal review candidate | 2 | `stacked-step-flow` + captions + alternatives + `two-sided-continuation` | `generate-revised-guide-set.mjs` |
| AST-04 Werk afronden en overdragen | v5 draft; visual-correction pass, awaiting exact-version review | 1 | `stacked-step-flow` + captions | `generate-revised-guide-set.mjs` |
| AST-05 Asset beoordelen en vrijgeven | v5 draft; visual-correction pass, awaiting exact-version review | 1 | `two-column-step-grid` + `inline-return` | `generate-revised-guide-set.mjs` |
| WF-01 Workflow starten | v10 draft; v9 remains Internal review candidate | 1 | `two-column-step-grid` + `inline-route-alternative` | `generate-workflow-guide-review.mjs` |
| WF-02 Workflow uitvoeren en afronden | v11 draft; v10 remains Internal review candidate | 2 | `stacked-step-flow` + `two-sided-continuation` | `generate-workflow-guide-review.mjs` |
| CMP-01 Bestaand component plaatsen | v5 draft; v4 remains Internal review candidate | 1 | `stacked-step-flow` | `generate-component-guide-review.mjs` |
| CMP-02 Nieuw component registreren en plaatsen | v4 draft; visual-correction pass, awaiting exact-version review | 1 | `stacked-step-flow` + `parallel-visual-choice` | `generate-component-followup-guides.mjs` |
| CMP-04 Component naar tray verplaatsen | v6 draft; cold-start pass, awaiting exact-version review | 1 | `stacked-step-flow` | `generate-component-followup-guides.mjs` |
| HELP-01 Problemen en hulp | v6 draft; Working draft | 1 | `troubleshooting-grid` | `generate-component-followup-guides.mjs` |
| USR-01 Gebruiker toevoegen | v11 draft; visual-correction pass; v8 remains Internal review candidate | 1 | `stacked-step-flow` + `mixed-visual-widths` | `generate-user-account-guide-review.mjs` |
| USR-02 Rol en rechten wijzigen | v9 draft; visual-correction pass; v7 remains Internal review candidate | 1 | `stacked-step-flow` + `mixed-visual-widths` | `generate-user-account-guide-review.mjs` |
| USR-03 Wachtwoord resetten | v3 draft; visual-correction pass, awaiting exact-version review | 1 | `stacked-step-flow` + `mixed-visual-widths` | `generate-user-account-guide-review.mjs` |
| USR-04 Gebruiker uitschakelen of herstellen | v3 draft; visual-correction pass, awaiting exact-version review | 2 | `stacked-step-flow` + `two-sided-continuation` | `generate-user-account-guide-review.mjs` |
| USR-05 Groepen beheren | planned | ? | `unassigned` | Not assigned |
| CAT-00 Catalogus begrijpen | v7 draft; semantic-color and audit correction pass, awaiting exact-version review | 8 | `reference-chapter` + `reused-evidence` + `two-sided-continuation` | `generate-catalog-guide-review.mjs` |
| CAT-01 Model en modelnummer aanmaken | v3 draft; CAT-00-aligned structural rewrite, awaiting exact-version review | 5 | `extended-admin-flow` + three-route decision + warnings + `two-sided-continuation` | `generate-catalog-guide-review.mjs` |
| CAT-02 Modelspecificatie opbouwen | planned; specification ready | 5 | `extended-admin-flow` | `generate-catalog-guide-review.mjs` after evidence capture |
| CAT-03 Attributen beheren | planned; specification ready | 4 | `unassigned` | Not assigned |
| CAT-04 Componentdefinities beheren | planned; specification ready | 5 | `unassigned` | Not assigned |
| CAT-05 Varianten en lifecycle beheren | planned; specification ready | 4 | `unassigned` | Not assigned |
| CAT-06 Catalogus controleren en bronnen | planned; source policy unresolved | 3 | `unassigned` | Not assigned |

AST-01 is retired and replaced by SC-01. It remains in the historical guide
specification and evidence inventory but not in the active production matrix.

## Artifact Roots

| Guide or batch | Current repository artifact or generated root |
| --- | --- |
| AC-01 | `resources/manuals/operator-guides/drafts/AC-01-login-v8-draft.pdf`; accepted v6 remains in repository resources |
| AC-02 | `resources/manuals/operator-guides/drafts/ac-02-eigen-wachtwoord-wijzigen-v3-draft.pdf` |
| SC-01 | `resources/manuals/operator-guides/pdf/SC-01-asset-vinden-en-openen-v10.pdf` |
| AST-02 | `resources/manuals/operator-guides/drafts/AST-02-refurbishment-route-v6-draft.pdf`; accepted v5 remains in repository resources |
| AST-03 | `resources/manuals/operator-guides/pdf/AST-03-asset-registreren-en-labelen-v14.pdf`; exact accepted copy, with focused proof under `output/manuals/proofs/2026-08-25-visual-corrections/asset` |
| AST-04 | `resources/manuals/operator-guides/drafts/AST-04-complete-handoff-v5-draft.pdf` |
| AST-05 | `resources/manuals/operator-guides/drafts/AST-05-review-release-v5-draft.pdf` |
| WF-01 | `resources/manuals/operator-guides/drafts/WF-01-start-workflow-v10-draft.pdf`; accepted v9 remains in repository resources |
| WF-02 | `resources/manuals/operator-guides/drafts/WF-02-complete-workflow-v11-draft.pdf`; accepted v10 remains in repository resources |
| CMP-01 | `resources/manuals/operator-guides/drafts/CMP-01-install-existing-v5-draft.pdf`; accepted v4 remains in repository resources |
| CMP-02 | `resources/manuals/operator-guides/drafts/CMP-02-register-install-v4-draft.pdf` |
| CMP-04 | `resources/manuals/operator-guides/drafts/CMP-04-component-to-tray-v6-draft.pdf` |
| HELP-01 | `resources/manuals/operator-guides/drafts/HELP-01-problems-v6-draft.pdf` |
| USR-01 | `resources/manuals/operator-guides/drafts/usr-01-gebruiker-toevoegen-v11-draft.pdf`; accepted v8 remains in repository resources |
| USR-02 | `resources/manuals/operator-guides/drafts/usr-02-rol-en-rechten-wijzigen-v9-draft.pdf`; accepted v7 remains in repository resources |
| USR-03 | `resources/manuals/operator-guides/drafts/usr-03-wachtwoord-resetten-v3-draft.pdf` |
| USR-04 | `resources/manuals/operator-guides/drafts/usr-04-gebruiker-uitschakelen-v3-draft.pdf` |
| CAT-00 | `resources/manuals/operator-guides/drafts/CAT-00-catalogus-begrijpen-v7-draft.pdf`; unaccepted working draft |
| CAT-01 | `resources/manuals/operator-guides/drafts/CAT-01-model-en-modelnummer-aanmaken-v3-draft.pdf`; unaccepted working draft |

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
