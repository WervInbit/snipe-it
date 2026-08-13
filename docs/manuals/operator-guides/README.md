# Operator Guide Project

Status: current project entry point from 2026-07-21.

Use this directory to continue the laminated operator-guide work. Generated base guides are the active production format. Affinity remains a later finishing option after the complete set is confirmed or the user explicitly gives a green light.

## Current Direction

- Build one consistent generated review set before polishing individual guides.
- Use the current AC-01 and SC-01 visual scale and page anatomy as the baseline.
- Allow each guide to use the number and size of steps, screenshots, alternatives, and help items its task needs.
- Reuse canonical screenshots when guides show the same application state.
- Keep `https://snipe.inbit/` as the operator-facing URL; use `https://dev.inbit/` only for controlled draft captures.
- Review guide by guide after the complete draft set exists. Review state applies to an exact version.

## Current Documents

- [Continuation handoff](HANDOFF.md): current creation tracker, exact resume
  order, and environment/path migration checklist.
- [Guide system](system.md): shared design, content, production, and QA rules.
- [Component contract](components.md): reusable rendering primitives, registry,
  alignment rules, geometry checks, and review-feedback promotion.
- [Layout recipes](layouts.md): named page structures and step patterns proven
  by the current guide set.
- [Guide registry](registry.md): exact current versions, page models, layouts,
  generators, artifact roots, and review states.
- [Maintenance contract](maintenance.md): change classification, versioning,
  impact analysis, and propagation after testing or stakeholder feedback.
- [Decision log](decisions.md): accepted direction, open questions, and exact-version review records.
- [Source inventory](inventory.md): existing plans, outputs, screenshots, scripts, and Affinity files.
- [Screenshot catalog](screenshots.md): canonical shared evidence and capture-environment rules.
- [Guide specifications](guides/): one current content and evidence specification per active guide.

## Maintainability Rule

Colors, icons, typography, focus marks, screenshot sources, page structures,
and wording each have one documented owner. A shared change is mapped through
the registry before generation, and every visibly affected guide receives a
new version. Internally accepted and third-party-approved artifacts are never
overwritten in place.

## Portable Build

The guide generators, canonical evidence, locked baselines, and internal-review
candidate PDFs are versioned in this repository. From `scripts/manuals`, run
`npm ci` once and `npm test` to validate the package. Generated proofs go to
the ignored `output/manuals/` tree. Environment overrides and new-device steps
are documented in [HANDOFF.md](HANDOFF.md).

## Status Vocabulary

| Status | Meaning |
| --- | --- |
| `Planned` | Scope exists, but content and evidence are not prepared. |
| `Specification` | Steps, warnings, and visual requirements are defined. |
| `Evidence ready` | Required screenshots/photos are available and mapped. |
| `Working draft` | Current editable proof; evidence or review may still change it. |
| `Internal review candidate` | Exact version is internally accepted and ready for third-party review. |
| `Third-party approved` | Exact version is accepted by the third-party reviewer. |
| `Needs revision` | A reviewed guide requires structural or content changes. |
| `Retired` | Scope moved into another guide or out of this guide set. |

## Current Draft Package

The corrected generated review set was rebuilt on 2026-07-21 after the first combined package distorted the accepted AC-01/SC-01 layouts:

- 12 active guides across 13 A4 pages; AST-03 is intentionally two-sided.
- The combined working proof is regenerated under `output/manuals/proofs/revised-guide-set-v2/` and is not committed.
- Frozen internal-review PDFs: [AC-01 v6](../../../resources/manuals/operator-guides/pdf/AC-01-login-v6.pdf), [SC-01 v10](../../../resources/manuals/operator-guides/pdf/SC-01-asset-vinden-en-openen-v10.pdf), and [AST-02 v5](../../../resources/manuals/operator-guides/pdf/AST-02-refurbishment-route-v5.pdf).
- Reusable generator: [generate-revised-guide-set.mjs](../../../scripts/manuals/generate-revised-guide-set.mjs).
- AC-01 uses the tested v6 shared-frame SVG as a locked baseline instead of the generic step grid.
- SC-01 uses the tested asymmetric layout with mobile-only scanner-entry evidence and true corner-overlap image markers.
- AST-02 uses a compact ordered route list instead of six large route cards.
- Every generated PDF has the intended A4 page count and every rendered page received a visual layout check.
- No generated guide contains `dev.inbit`; operator-facing access uses `https://snipe.inbit/`.

This package is a historical `Working draft`, not an approval. Orange `BEELD NOG VASTLEGGEN` areas explicitly mark evidence that must be captured or confirmed before the affected guide can become `Third-party approved`:

- AST-03 registration form and exact status/location fields.
- AST-04 physical review location and exact handoff status.
- AST-05 confirmed supervisor-release end state and exact status label.

The CMP-02 and CMP-04 placeholders in that older combined package are superseded by the current component follow-up review batch below.

### Current Workflow Review

WF-01 and WF-02 have a newer focused review batch than the pages in the 2026-07-21 combined set:

- Working proof folder: `output/manuals/proofs/workflow-review-v8/`.
- Frozen PDFs: [WF-01 v9](../../../resources/manuals/operator-guides/pdf/WF-01-workflow-starten-v9.pdf) and [WF-02 v10](../../../resources/manuals/operator-guides/pdf/WF-02-workflow-uitvoeren-en-afronden-v10.pdf).
- WF-01 v9 is one page and is an internal review candidate for V1. SC-01 owns asset validation; step 3 separates new-run and existing-run routes with an `OF` divider and equal heading hierarchy.
- WF-02 v10 is intentionally two-sided and uses actual blank-run captures so results remain neutral before the execution step. Step 1 uses `Valideer de actieve workflow`; the front-page handoff says `volgende pagina`; and 4A uses the native yellow `Notitie` state plus a separate red target on the note-entry field.
- Screenshot crops and target marks use exact source-pixel SVG coordinates; the visible example tag is consistently anonymized as `INBIT-HG0421`.
- One blank `Standard Diagnostics` run was created on the controlled development asset for capture; no result, note text, or photo was submitted.
- WF-01 v9 and WF-02 v10 both have exact-version internal review records.

### Current Component Review

CMP-01 has a newer focused review proof than the placeholder page in the 2026-07-21 combined set:

- Working proof folder: `output/manuals/proofs/CMP-01-v4/`.
- Frozen PDF: [CMP-01 v4](../../../resources/manuals/operator-guides/pdf/CMP-01-bestaand-component-plaatsen-v4.pdf).
- CMP-01 v4 uses the actual four-step flow: open Componenten, choose the matching tray/storage record, install after physical placement, and verify the tracked tag and serial on the asset.
- Selection and installation intentionally reuse the same real mobile screenshot with different target marks.
- Target marks are aligned to the exact controls; step 1 and step 4 use separate marks where the instruction has two distinct visual checks.
- The controlled component was installed back into the development asset after capture.
- CMP-01 v4 is the exact internal review candidate for V1.

### Current Component Follow-up Review

CMP-02, CMP-04, and HELP-01 have a newer focused review batch than the pages in the 2026-07-21 combined set:

- Working proof folder: `output/manuals/proofs/component-followup-v2/`; draft PDFs are not committed.
- CMP-02 v2 follows the verified four-step definition/custom registration flow and uses `Create And Install` once after physical placement.
- CMP-04 v5 follows the verified four-step locked-serial removal flow, centers the 1B target on `Naar tray`, and ends at `Status: In Tray` with `Asset: N.v.t.`.
- HELP-01 v6 contains twelve compact recovery routes, including component/tray failures and supervisor-only password reset.
- Target overlays use source-pixel coordinates with centered, symmetric padding. The generated pages contain no development URL or controlled asset tag.
- CMP-01 installs a physical component that already has a tracked tray/storage record. CMP-02 route 2A creates a new tracked item from an existing catalog definition; route 2B is only for an approved custom component.
- All three versions are working drafts awaiting review.

### Current User-Account Review

USR-01 through USR-04 and AC-02 now have a focused review batch built from
real Dutch desktop captures in the controlled development environment:

- Working proof folder: `output/manuals/proofs/user-account-review/`; the combined draft PDF is not committed.
- USR-01, USR-02, USR-03, and AC-02 are one page each; USR-04 is intentionally two-sided.
- Reusable evidence comes from one reversible fictional user state and contains no visible password value.
- The batch generator is [generate-user-account-guide-review.mjs](../../../scripts/manuals/generate-user-account-guide-review.mjs); capture and state-preparation helpers remain separate for reproducibility.
- USR-01 v8 and USR-02 v7 are internal review candidates. USR-03 v1, AC-02
  v1, and two-sided USR-04 v1 remain working drafts awaiting review.

USR-01 has a newer focused review version than the page in that combined batch:

- Frozen PDF: [USR-01 v8](../../../resources/manuals/operator-guides/pdf/USR-01-gebruiker-toevoegen-v8.pdf).
- v8 adds a small expanded Dutch dashboard-sidebar visual for `Personen` and `Toon Alles`, followed by the existing add-user toolbar as 1B.
- It retains the v7 shared components, Admin/Superadmin role, AC-01 prerequisite icon, and two-row footer with five full guide names.
- USR-01 v8 is an `Internal review candidate for V1`; the combined v1 batch and earlier focused drafts remain unchanged.

USR-02 also has a newer focused version than the page in the combined batch:

- Frozen PDF: [USR-02 v7](../../../resources/manuals/operator-guides/pdf/USR-02-rol-en-rechten-wijzigen-v7.pdf).
- v7 retains the search/open/edit and rights corrections and presents the
  `USR-05 Groepen beheren` help handoff with its USR marker, full name, and
  family color inside a taller help row. The 3A focus rectangle is fully
  visible within its screenshot frame.
- USR-02 v7 is an `Internal review candidate for V1`.

### V1 Internal Review Candidates

The following exact generated versions are internally accepted for third-party review:

- [AC-01 Login v6](../../../resources/manuals/operator-guides/pdf/AC-01-login-v6.pdf)
- [SC-01 Asset vinden en openen v10](../../../resources/manuals/operator-guides/pdf/SC-01-asset-vinden-en-openen-v10.pdf)
- [AST-02 Refurbishment route v5](../../../resources/manuals/operator-guides/pdf/AST-02-refurbishment-route-v5.pdf)
- [WF-01 Workflow starten v9](../../../resources/manuals/operator-guides/pdf/WF-01-workflow-starten-v9.pdf)
- [WF-02 Workflow uitvoeren en afronden v10](../../../resources/manuals/operator-guides/pdf/WF-02-workflow-uitvoeren-en-afronden-v10.pdf)
- [CMP-01 Bestaand component plaatsen v4](../../../resources/manuals/operator-guides/pdf/CMP-01-bestaand-component-plaatsen-v4.pdf)
- [USR-01 Gebruiker toevoegen v8](../../../resources/manuals/operator-guides/pdf/USR-01-gebruiker-toevoegen-v8.pdf)
- [USR-02 Rol en rechten wijzigen v7](../../../resources/manuals/operator-guides/pdf/USR-02-rol-en-rechten-wijzigen-v7.pdf)

The repository [PDF manifest](../../../resources/manuals/operator-guides/pdf/manifest.json) freezes all eight hashes. The older six-guide workstation package remains historical only. Working drafts and evidence-incomplete guides remain excluded.

Internal acceptance freezes the reviewed page design and wording for third-party review. Any later content or layout change requires a new version and review.

## Active Guide Set

| Guide | Type | Role | Draft objective |
| --- | --- | --- | --- |
| [AC-01 Login](guides/AC-01.md) | Compact access | Everyone | Open the app, log in, and recognize the dashboard. |
| [AC-02 Change Own Password](guides/AC-02.md) | Compact access | Everyone with a local account | Replace a temporary or current password with a private password. |
| [SC-01 Find And Open Asset](guides/SC-01.md) | Single task | Refurbisher | Scan or search, open the result, and verify the physical asset. |
| [AST-02 Refurbishment Route](guides/AST-02.md) | Route overview | Refurbisher | Show the complete route and point to the task guides. Build after child guides. |
| [AST-03 Register And Label Asset](guides/AST-03.md) | Two-sided detail task | Supervisor / asset creator | Register known hardware, print/attach the label, and verify it. |
| [AST-04 Complete Work And Hand Off](guides/AST-04.md) | Detail task | Senior refurbisher | Confirm operator work is complete and hand the asset to review. |
| [AST-05 Review And Release Asset](guides/AST-05.md) | Detail task | Supervisor | Review evidence and set the approved release state. |
| [WF-01 Start Workflow](guides/WF-01.md) | Single task | Senior refurbisher | Continue the correct existing run or start the correct workflow once. |
| [WF-02 Run And Complete Workflow](guides/WF-02.md) | Two-sided detail task | Senior refurbisher | Record truthful results, notes, and image evidence and confirm the saved completion state. |
| [CMP-01 Install Existing Component](guides/CMP-01.md) | Detail task | Authorized refurbisher | Select an existing tracked component and install it in the asset. |
| [CMP-02 Register And Install New Component](guides/CMP-02.md) | Detail task | Authorized refurbisher | Register a definition-backed or custom component and install it. |
| [CMP-04 Move Component To Tray](guides/CMP-04.md) | Detail task | Authorized refurbisher | Remove a component from the asset and confirm its tray destination. |
| [HELP-01 Problems And Help](guides/HELP-01.md) | Troubleshooting | Everyone | Route common failures to the correct recovery or escalation. |
| [USR-01 Add User](guides/USR-01.md) | Administration task | Admin / Superadmin | Create one account, assign its approved standard group, and verify it. |
| [USR-02 Change Role And Rights](guides/USR-02.md) | Administration task | Admin / Superadmin | Change groups where permitted and configure understandable direct user rights. |
| [USR-03 Reset Password](guides/USR-03.md) | Administration task | Admin / Superadmin | Send a reset link or issue one generated temporary password safely. |
| [USR-04 Disable Or Restore User](guides/USR-04.md) | Two-sided lifecycle task | Admin / Superadmin | Deactivate by default; delete or restore only after ownership and access checks. |
| [USR-05 Manage Groups](guides/USR-05.md) | Planned administration task | Superadmin | Create or edit a reusable group and verify its minimum required rights. |

## Retired Or Deferred Scope

- `AST-01 Open Asset` is retired; SC-01 owns scan/search, opening, and identity verification.
- `CMP-03 Add Custom Component` is retired; its custom path is an alternative inside CMP-02.
- The old `AST-04 Add New Device From Scratch` belongs to later catalog/admin guidance.
- Broad remove/retire/sell asset guidance is not one floor guide. Operator handoff is AST-04 and supervisor release is AST-05; destructive lifecycle actions need separate later investigation.
- Duplicate merging, bulk user import/edit, two-factor reset, work orders, and catalog configuration remain later scopes. Reusable group management is now planned as USR-05 but has not yet been investigated or generated.

## Production Order

1. Generate AC-01 and SC-01 from the accepted baseline.
2. Generate WF-01 and WF-02.
3. Generate CMP-01, CMP-02, and CMP-04.
4. Generate AST-03, AST-04, and AST-05.
5. Generate HELP-01 from the actual recovery routes used by the task guides.
6. Generate AST-02 last so it references the settled child guides without duplicating them.
7. Review the complete set guide by guide and record exact-version decisions.
8. Consider Affinity only after the generated set is confirmed or an explicit green light is given.

The focused user-account set follows its own review sequence after controlled
desktop evidence is available:

1. Generate USR-01 account creation and USR-02 role/rights together because
   they reuse user-list, group, permission, and detail evidence.
2. Investigate and generate USR-05 group management after confirming the
   live group list, add/edit controls, permission model, and verification route.
3. Generate USR-03 administrator password reset and AC-02 user self-change
   together so the handoff is visually continuous.
4. Generate two-sided USR-04 after deactivated, deleted, and restored states
   are captured on one controlled fictional account.
5. Review each exact version separately; do not add new guides to the internal
   review package before explicit internal acceptance.

## Review Rule

Review state applies only to the named guide version. General comments such as
"close to done" set direction but do not silently mark a version an
`Internal review candidate`. Internal acceptance also does not imply
`Third-party approved`. The decision log and focused review records preserve
explicit decisions and small corrections.
