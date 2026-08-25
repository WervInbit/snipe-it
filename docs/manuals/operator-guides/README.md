# Operator Guide Project

Status: current project entry point, updated 2026-08-20.

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

This package is a historical `Working draft`, not an approval. Its orange
`BEELD NOG VASTLEGGEN` areas recorded the evidence gaps that existed in that
version:

- AST-03 registration form and exact status/location fields.
- AST-04 physical review location and exact handoff status.
- AST-05 confirmed supervisor-release end state and exact status label.

The newer AST lifecycle review below replaces those digital placeholders.
The recognizable physical QA location remains a local process item for review.

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

CMP-02 and CMP-04 have new cold-start revisions; HELP-01 remains on its current
working draft:

- Working proof folder: `output/manuals/proofs/2026-08-20-cold-start-rework/component/`; latest draft PDFs are committed separately as unaccepted review artifacts.
- CMP-02 v4 names Senior Refurbisher, explains reusable definition versus one-off `Aangepast`, routes a missing type through a full CAT-04 guide handoff, and uses `Create And Install` once after physical placement.
- CMP-04 v6 names Senior Refurbisher and preserves the verified locked-serial removal flow, centered `Naar tray` target, and `Status: In Tray` / `Asset: N.v.t.` result.
- HELP-01 v6 contains twelve compact recovery routes, including component/tray failures and supervisor-only password reset.
- Target overlays use source-pixel coordinates with centered, symmetric padding. The generated pages contain no development URL or controlled asset tag.
- CMP-01 installs a physical component that already has a tracked tray/storage record. CMP-02 route 2A creates a new tracked item from an existing catalog definition; route 2B is only for an approved custom component.
- CMP-02 v4 passes the focused visual-correction gate and CMP-04 v6 passes the cold-start gate; both await exact-version review. HELP-01 v6 remains a working draft.

### Current User-Account Review

USR-01 through USR-04 and AC-02 now have a focused review batch built from
real Dutch desktop captures in the controlled development environment:

- Working proof folder: `output/manuals/proofs/2026-08-25-visual-corrections/user/`; latest draft PDFs are committed separately as unaccepted review artifacts.
- USR-01, USR-02, USR-03, and AC-02 are one page each; USR-04 is intentionally two-sided.
- Reusable evidence comes from one reversible fictional user state. USR-03 v3
  intentionally shows one unsaved generated temporary value so the output of
  `Genereer` is recognizable; it is not a retained or final user password.
- The batch generator is [generate-user-account-guide-review.mjs](../../../scripts/manuals/generate-user-account-guide-review.mjs); capture and state-preparation helpers remain separate for reproducibility.
- USR-01 v11, USR-02 v9, USR-03 v3, AC-02 v3, and two-sided USR-04 v3 pass
  the focused visual-correction gate and remain working drafts awaiting
  exact-version review.
  Frozen USR-01 v8 and USR-02 v7 remain internal review candidates.

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
- [AST-03 Asset registreren en labelen v14](../../../resources/manuals/operator-guides/pdf/AST-03-asset-registreren-en-labelen-v14.pdf)
- [WF-01 Workflow starten v9](../../../resources/manuals/operator-guides/pdf/WF-01-workflow-starten-v9.pdf)
- [WF-02 Workflow uitvoeren en afronden v10](../../../resources/manuals/operator-guides/pdf/WF-02-workflow-uitvoeren-en-afronden-v10.pdf)
- [CMP-01 Bestaand component plaatsen v4](../../../resources/manuals/operator-guides/pdf/CMP-01-bestaand-component-plaatsen-v4.pdf)
- [USR-01 Gebruiker toevoegen v8](../../../resources/manuals/operator-guides/pdf/USR-01-gebruiker-toevoegen-v8.pdf)
- [USR-02 Rol en rechten wijzigen v7](../../../resources/manuals/operator-guides/pdf/USR-02-rol-en-rechten-wijzigen-v7.pdf)

The repository [accepted PDF manifest](../../../resources/manuals/operator-guides/pdf/manifest.json) freezes all nine accepted hashes. The separate [unaccepted draft manifest](../../../resources/manuals/operator-guides/drafts/manifest.json) preserves the latest review PDFs without implying approval. The older six-guide workstation package remains historical only.

Internal acceptance freezes the reviewed page design and wording for third-party review. Any later content or layout change requires a new version and review.

### 2026-08-18 Feedback Drafts

Six focused replacements are under review while their accepted predecessors
remain frozen in the repository package:

- AC-01 v8 changes `Nodig` to `Inbit-telefoon + account`; v7 remains review
  history and accepted v6 remains frozen.
- AST-02 v6, WF-01 v10, and WF-02 v11 use the Refurbisher role.
- CMP-01 v5 uses the Senior Refurbisher role.
- USR-01 v9 explicitly opens `Optionele informatie` before locating
  `Groepen`.
- WF-01 v10 also centers the 3B target on `Bewerk`.

The focused generators now default to the latest working drafts. Explicit
version environment variables documented in [HANDOFF.md](HANDOFF.md) preserve
reproduction of earlier branches; accepted PDFs are never overwritten.

### Current AST Lifecycle Review

AST-03 through AST-05 now have focused visual-correction revisions. Earlier
branches remain review history; AST-03 v14 and AST-04/05 v5 are current:

- Working proof root: `output/manuals/proofs/2026-08-25-visual-corrections/asset/`.
- AST-03 v14 is a two-page registration and label guide. It starts on the
  dashboard, shows the `Apparaten` route, then groups both create controls as
  alternatives with contained, centered focus geometry. It retains the realistic HP-style S/N example, exact
  model-type checks, deployed `Status` label and complete save action, a
  four-field post-save check, continuous steps 1-8, split print evidence, and
  the real owner-supplied full-underside photo with lower-right QR placement.
  Damaged-label recovery uses manual search by unique Inbit asset tag or serial
  number instead of instructing the operator to print a replacement.
  The rejected generated underside visual and repeated scanner image remain
  excluded. Location is no longer a numbered primary step.
- AST-04 v5 is explicitly the final refurbishment-to-QA handoff. It reframes
  workflow evidence and treats 2B as applicable only to registered `Tracked`
  components while documenting automatic status saving.
- AST-05 v5 separates identity from incoming QA status, uses the same physical
  device and workflow evidence, documents automatic release/return saving, and
  uses complete styled references in help and related guides.
- Eleven new canonical captures replace the prior digital placeholders. The
  fictional asset identities are rendered only in screenshots; no server
  asset was created or renamed for this batch.
- AST-03 v14 is the exact internally accepted two-page candidate. AST-04 v5
  and AST-05 v5 remain working drafts; their local physical QA location and
  final status vocabulary still require operational confirmation.

### Current Catalogue Review

The catalogue-management family now has a standards-first foundation and two
extensive working drafts:

- CAT-00 v4 is an eight-page reference chapter. It first establishes the
  Basismodel/model-number/asset structure, then explains attribute definitions
  and values, component definitions, expected and placed components, model-
  number baselines, asset deviations, and effective-value precedence. The
  final page routes the reader to CAT-01 through CAT-06.
- CAT-01 v2 is a five-page Supervisor procedure with
  continuous steps 1-8, separate existing/new model routes, detailed field
  explanations, exact-code rules, verification, and the real limits of
  `Kopieer model`.
- Nine canonical catalogue captures and one reused component-roster capture
  are registered by stable evidence IDs. The source forms were not submitted
  and contain no production record changes.
- Both PDFs pass the cold-start, A4, page-count, text, component-geometry,
  context-column, and full-page raster gates. They remain working drafts;
  neither has been internally accepted yet.
- CAT-02 through CAT-06 have detailed specifications. CAT-02 is the next
  evidence-and-generation target; CAT-06 remains blocked from approval by the
  unresolved source-recording policy.

## Active Guide Set

| Guide | Type | Role | Draft objective |
| --- | --- | --- | --- |
| [AC-01 Login](guides/AC-01.md) | Compact access | Everyone | Open the site, log in, and recognize the dashboard. |
| [AC-02 Change Own Password](guides/AC-02.md) | Compact access | Everyone with a local account | Replace a temporary or current password with a private password. |
| [SC-01 Find And Open Asset](guides/SC-01.md) | Single task | Refurbisher | Scan or search, open the result, and verify the physical asset. |
| [AST-02 Refurbishment Route](guides/AST-02.md) | Route overview | Refurbisher | Show the complete route and point to the task guides. Build after child guides. |
| [AST-03 Register And Label Asset](guides/AST-03.md) | Two-sided detail task | Supervisor | Register known hardware, print/attach the label, and verify it. |
| [AST-04 Complete Work And Hand Off](guides/AST-04.md) | Detail task | Senior refurbisher | Confirm operator work is complete and hand the asset to review. |
| [AST-05 Review And Release Asset](guides/AST-05.md) | Detail task | Supervisor | Review evidence and set the approved release state. |
| [WF-01 Start Workflow](guides/WF-01.md) | Single task | Refurbisher | Continue the correct existing run or start the correct workflow once. |
| [WF-02 Run And Complete Workflow](guides/WF-02.md) | Two-sided detail task | Refurbisher | Record truthful results, notes, and image evidence and confirm the saved completion state. |
| [CMP-01 Install Existing Component](guides/CMP-01.md) | Detail task | Senior refurbisher | Select an existing tracked component and install it in the asset. |
| [CMP-02 Register And Install New Component](guides/CMP-02.md) | Detail task | Senior Refurbisher | Register a definition-backed or custom component and install it. |
| [CMP-04 Move Component To Tray](guides/CMP-04.md) | Detail task | Senior Refurbisher | Remove a component from the asset and confirm its tray destination. |
| [HELP-01 Problems And Help](guides/HELP-01.md) | Troubleshooting | Everyone | Route common failures to the correct recovery or escalation. |
| [USR-01 Add User](guides/USR-01.md) | Administration task | Admin | Create one account, assign its approved standard group, and verify it. |
| [USR-02 Change Role And Rights](guides/USR-02.md) | Administration task | Admin | Change groups where permitted and configure understandable direct user rights. |
| [USR-03 Reset Password](guides/USR-03.md) | Administration task | Admin | Issue one generated temporary password through personal handoff. |
| [USR-04 Disable Or Restore User](guides/USR-04.md) | Two-sided lifecycle task | Admin | Deactivate by default; delete or restore only after ownership and access checks. |
| [USR-05 Manage Groups](guides/USR-05.md) | Planned administration task | Superadmin | Create or edit a reusable group and verify its minimum required rights. |
| [CAT-00 Understand The Catalogue](guides/CAT-00.md) | Eight-page reference chapter | Supervisor | Understand identities, reusable definitions, model-number baselines, actual asset state, value precedence, and the correct follow-up guide. |
| [CAT-01 Create Model And Model Number](guides/CAT-01.md) | Five-page administration task | Supervisor | Reuse or create the correct Basismodel and add one exact manufacturer model number. |
| [CAT-02 Build Model Specification](guides/CAT-02.md) | Planned five-page administration task | Supervisor target | Build direct attributes and expected components without duplicate or conflicting values. |
| [CAT-03 Manage Attributes](guides/CAT-03.md) | Planned administration task | Supervisor target | Reuse, create, constrain, and retire attribute definitions safely. |
| [CAT-04 Manage Component Definitions](guides/CAT-04.md) | Planned administration task | Supervisor target | Configure reusable component identity, tracking, placement, attributes, and expected children. |
| [CAT-05 Manage Variants And Lifecycle](guides/CAT-05.md) | Planned administration task | Admin | Add variants and manage default, active, deprecated, delete, and partial-copy behavior. |
| [CAT-06 Verify Catalogue And Sources](guides/CAT-06.md) | Planned verification task | Supervisor target | Validate exact identifiers and specification facts against authoritative evidence. |

## Retired Or Deferred Scope

- `AST-01 Open Asset` is retired; SC-01 owns scan/search, opening, and identity verification.
- `CMP-03 Add Custom Component` is retired; its custom path is an alternative inside CMP-02.
- The old `AST-04 Add New Device From Scratch` belongs to later catalog/admin guidance.
- Broad remove/retire/sell asset guidance is not one floor guide. Operator handoff is AST-04 and supervisor release is AST-05; destructive lifecycle actions need separate later investigation.
- Duplicate merging, bulk user import/edit, two-factor reset, work orders, and catalog configuration remain later scopes. Reusable group management is now planned as USR-05 but has not yet been investigated or generated.

## Production Order

1. Review the 12 cold-start revisions recorded in the 2026-08-20 retest.
2. Preserve any exact version explicitly accepted during that review.
3. Investigate and generate USR-05 and CAT-02 through CAT-06 in small batches.
4. Repeat the cold-start gate and exact-version review for every new batch.
5. Consider Affinity only after the generated set is confirmed or an explicit green light is given.

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

The catalogue set follows this sequence:

1. Review CAT-00 and CAT-01 separately; preserve each exact working draft.
2. Capture and generate CAT-02 before CAT-03/CAT-04 so its actual missing-
   definition routes determine the downstream screenshots and wording.
3. Generate CAT-03 and CAT-04 as definition-management references.
4. Generate CAT-05 after the base-model and specification workflows settle.
5. Resolve the source-recording policy, then generate CAT-06.
6. Register physical assets through AST-03 only after CAT-01 and CAT-02 are
   complete; do not merge asset creation into the catalogue guides.

## Review Rule

Review state applies only to the named guide version. General comments such as
"close to done" set direction but do not silently mark a version an
`Internal review candidate`. Internal acceptance also does not imply
`Third-party approved`. The decision log and focused review records preserve
explicit decisions and small corrections.
