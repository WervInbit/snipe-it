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
| AC-01 Login | v6; Internal review candidate | 1 | `compact-horizontal-sequence` | `generate-ac01-snipe-proof.mjs` |
| AC-02 Eigen wachtwoord wijzigen | v1 draft; Working draft | 1 | `stacked-step-flow` | `generate-user-account-guide-review.mjs` |
| SC-01 Asset vinden en openen | v10; Internal review candidate | 1 | `mixed-asymmetric-flow` | `generate-sc01-snipe-proof.mjs` |
| AST-02 Refurbishment route | v5; Internal review candidate | 1 | `route-list` | `generate-revised-guide-set.mjs` |
| AST-03 Asset registreren en labelen | v1 draft; evidence incomplete | 2 | `stacked-step-flow` + `two-sided-continuation` | `generate-revised-guide-set.mjs` |
| AST-04 Werk afronden en overdragen | v1 draft; evidence incomplete | 1 | `stacked-step-flow` | `generate-revised-guide-set.mjs` |
| AST-05 Asset beoordelen en vrijgeven | v1 draft; evidence incomplete | 1 | `stacked-step-flow` | `generate-revised-guide-set.mjs` |
| WF-01 Workflow starten | v9; Internal review candidate | 1 | `two-column-step-grid` + `inline-route-alternative` | `generate-workflow-guide-review.mjs` |
| WF-02 Workflow uitvoeren en afronden | v10; Internal review candidate | 2 | `stacked-step-flow` + `two-sided-continuation` | `generate-workflow-guide-review.mjs` |
| CMP-01 Bestaand component plaatsen | v4; Internal review candidate | 1 | `stacked-step-flow` | `generate-component-guide-review.mjs` |
| CMP-02 Nieuw component registreren en plaatsen | v2 draft; Working draft | 1 | `stacked-step-flow` + `parallel-visual-choice` | `generate-component-followup-guides.mjs` |
| CMP-04 Component naar tray verplaatsen | v5 draft; Working draft | 1 | `stacked-step-flow` | `generate-component-followup-guides.mjs` |
| HELP-01 Problemen en hulp | v6 draft; Working draft | 1 | `troubleshooting-grid` | `generate-component-followup-guides.mjs` |
| USR-01 Gebruiker toevoegen | v8; Internal review candidate | 1 | `stacked-step-flow` + `mixed-visual-widths` | `generate-user-account-guide-review.mjs` |
| USR-02 Rol en rechten wijzigen | v7; Internal review candidate | 1 | `stacked-step-flow` + `mixed-visual-widths` | `generate-user-account-guide-review.mjs` |
| USR-03 Wachtwoord resetten | v1 draft; Working draft | 1 | `stacked-step-flow` + `parallel-visual-choice` | `generate-user-account-guide-review.mjs` |
| USR-04 Gebruiker uitschakelen of herstellen | v1 draft; Working draft | 2 | `stacked-step-flow` + `two-sided-continuation` | `generate-user-account-guide-review.mjs` |
| USR-05 Groepen beheren | planned | ? | `unassigned` | Not assigned |

AST-01 is retired and replaced by SC-01. It remains in the historical guide
specification and evidence inventory but not in the active production matrix.

## Artifact Roots

| Guide or batch | Current repository artifact or generated root |
| --- | --- |
| AC-01 | `resources/manuals/operator-guides/pdf/AC-01-login-v6.pdf` |
| SC-01 | `resources/manuals/operator-guides/pdf/SC-01-asset-vinden-en-openen-v10.pdf` |
| AST-02 | `resources/manuals/operator-guides/pdf/AST-02-refurbishment-route-v5.pdf` |
| AST-03 through AST-05 | `output/manuals/proofs/revised-guide-set-v2` |
| WF-01 | `resources/manuals/operator-guides/pdf/WF-01-workflow-starten-v9.pdf` |
| WF-02 | `resources/manuals/operator-guides/pdf/WF-02-workflow-uitvoeren-en-afronden-v10.pdf` |
| CMP-01 | `resources/manuals/operator-guides/pdf/CMP-01-bestaand-component-plaatsen-v4.pdf` |
| CMP-02, CMP-04, and HELP-01 | `output/manuals/proofs/component-followup-v2` |
| USR-01 | `resources/manuals/operator-guides/pdf/USR-01-gebruiker-toevoegen-v8.pdf` |
| USR-02 | `resources/manuals/operator-guides/pdf/USR-02-rol-en-rechten-wijzigen-v7.pdf` |
| USR-03, AC-02, and USR-04 | `output/manuals/proofs/user-account-review` |

Committed binary inputs live under `resources/manuals/operator-guides/`.
Generated and superseded proofs remain under ignored output or the historical
workstation archive.

## Accepted Artifact Protection

An `Internal review candidate` or `Third-party approved` row refers to one
exact version, not the guide code in general. A visible change creates a new
version and leaves the accepted artifact unchanged.

The repository PDF manifest contains all eight exact internal-review candidates,
including USR-01 v8 and USR-02 v7. The older six-guide workstation package is
retained only as review history.

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
