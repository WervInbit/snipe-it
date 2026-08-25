# Operator Guide Continuation Handoff

Status: current continuation checkpoint, updated 2026-08-20.

Use this document when resuming guide creation in another task, on another
device, or after a long interruption. It records the resume order, current
creation stage, and environment-specific dependencies. It does not replace the
authoritative specifications, registry, component rules, or review records.

## Read First

1. Read the repository `AGENTS.md`, `PROGRESS.md`, and `docs/fork-notes.md`.
2. Read [README.md](README.md) for the project direction.
3. Read [registry.md](registry.md) for the exact current version, status,
   layout, generator, and artifact root of every guide.
4. Read [decisions.md](decisions.md) and the repository `TODO.md` for accepted
   policy and unresolved work.
5. Read [system.md](system.md), [components.md](components.md),
   [layouts.md](layouts.md), and [maintenance.md](maintenance.md) before
   changing shared behavior.
6. Read the target `guides/<CODE>.md`, its latest `reviews/<CODE>-vN.md`, and
   every evidence entry it uses in [screenshots.md](screenshots.md).

The registry is authoritative for current status. This handoff is a concise
resume snapshot and must be updated when the creation stage or next action
changes.

## Current Direction

- Generated guides are the production baseline. Affinity remains deferred
  until the active guide set is confirmed or the user explicitly reopens it.
- Review and acceptance apply to one exact version. Never overwrite an
  `Internal review candidate` or `Third-party approved` artifact.
- Reuse canonical evidence when two guides show the same application state.
- Keep `https://snipe.inbit/` operator-facing. Use `https://dev.inbit/` only
  for controlled evidence capture and never expose it in printed guide copy.
- Custom QR destinations are deferred. A locally customized QR build is not a
  canonical accepted artifact unless it receives a new version and review.
- Published PDFs are intended to become Snipe-IT documentation assets later.
  Application routes and QR delivery are owned by the separate implementation
  work, not by the current guide-generation task.

## Creation Tracker

| Guide | Current version | Creation state | Next action |
| --- | --- | --- | --- |
| AC-01 Login | v8 draft | Working draft; v6 remains accepted | Review `Inbit-telefoon + account` in the required-items strip. |
| AC-02 Eigen wachtwoord wijzigen | v3 draft | Visual-correction pass; awaiting exact-version review | Review focused actions, inline no-sharing warning, and full USR-03 handoffs. |
| SC-01 Asset vinden en openen | v10 | Internal review candidate | Freeze exact artifact; include in published set. |
| AST-02 Refurbishment route | v6 draft | Working draft; v5 remains accepted | Review the Refurbisher-only role line. |
| AST-03 Asset registreren en labelen | v14 | Internal review candidate | Preserve the exact accepted two-page PDF and include it in the published review set. |
| AST-04 Werk afronden en overdragen | v5 draft | Visual-correction pass; awaiting exact-version review | Review the final refurbishment-to-QA scope, reframed step 1, and optional tracked-component check. |
| AST-05 Asset beoordelen en vrijgeven | v5 draft | Visual-correction pass; awaiting exact-version review | Review full family-styled help and related-guide handoffs. |
| WF-01 Workflow starten | v10 draft | Working draft; v9 remains accepted | Review Refurbisher role and centered 3B `Bewerk` target. |
| WF-02 Workflow uitvoeren en afronden | v11 draft | Working draft; v10 remains accepted | Review Refurbisher role on both pages. |
| CMP-01 Bestaand component plaatsen | v5 draft | Working draft; v4 remains accepted | Review Senior Refurbisher role. |
| CMP-02 Nieuw component registreren en plaatsen | v4 draft | Visual-correction pass; awaiting exact-version review | Review the full CAT-04 help handoff and unchanged definition/custom choice. |
| CMP-04 Component naar tray verplaatsen | v6 draft | Cold-start pass; awaiting exact-version review | Review Senior Refurbisher role and the unchanged verified removal path. |
| HELP-01 Problemen en hulp | v6 draft | Working draft | Review all recovery tiles and guide handoffs. |
| USR-01 Gebruiker toevoegen | v11 draft | Visual-correction pass; v8 remains accepted | Review the full USR-02 help handoff; account flow is unchanged. |
| USR-02 Rol en rechten wijzigen | v9 draft | Visual-correction pass; v7 remains accepted | Review the separate `Machtigingen` and rights-grid focus areas. |
| USR-03 Wachtwoord resetten | v3 draft | Visual-correction pass; awaiting exact-version review | Review the visible unsaved generated value, `Genereer` focus, and full AC-02 handoff. |
| USR-04 Gebruiker uitschakelen of herstellen | v3 draft | Visual-correction pass; awaiting exact-version review | Review rectangular actions, contained warning text, and full references on both pages. |
| USR-05 Groepen beheren | Planned | Investigation required | Investigate group list, add/edit controls, permission behavior, and verification route. |
| CAT-00 Catalogus begrijpen | v2 draft | Cold-start pass; awaiting exact-version review | Review the task index, plain-language data placement, source priority, and follow-up map. |
| CAT-01 Model en modelnummer aanmaken | v2 draft | Cold-start pass; awaiting exact-version review | Review the Supervisor Basismodel route, exact-code rules, automatic standard row, and optional CAT-02 handoff. |
| CAT-02 Modelspecificatie opbouwen | Planned | Specification ready; evidence pending | Capture the complete specification workflow and generate the next extensive CAT draft. |
| CAT-03 Attributen beheren | Planned | Specification ready; evidence pending | Generate after CAT-02 establishes which missing-definition handoffs are needed. |
| CAT-04 Componentdefinities beheren | Planned | Specification ready; evidence pending | Generate with definition, tracking, placement, contribution, and child-template evidence. |
| CAT-05 Varianten en lifecycle beheren | Planned | Specification ready; evidence pending | Generate after CAT-01/CAT-02 review confirms variant and lifecycle wording. |
| CAT-06 Catalogus controleren en bronnen | Planned | Specification ready; product policy unresolved | Decide where verification sources are recorded before generating an approval candidate. |

AST-01 is retired into SC-01 and remains historical evidence only.

## What Must Be Tracked For Every New Version

Before generation:

- Update `guides/<CODE>.md` with scope, steps, help, completion state, evidence,
  page model, layout recipe, and next version.
- Update `registry.md` with the new version, generator, artifact root, and
  working status.
- Register every new or replaced screenshot in `screenshots.md` before using
  it in a generator.
- Create `reviews/<CODE>-vN.md` and identify the previous version, feedback
  source, impact class, and intended correction.

During generation and review:

- Keep source screenshots unannotated; generate focus marks separately.
- Generate a focused proof before any combined batch.
- Record PDF page count, A4 dimensions, extracted-text checks, stale-label
  checks, component geometry, focus containment, and full-page raster review.
- Record every correction in the version review. Promote reusable corrections
  to `components.md`, `layouts.md`, or `system.md`.
- Do not mark a version accepted until the user explicitly accepts that exact
  artifact.

After a decision:

- Update the guide specification, version review, registry, decision log,
  project README, inventory, this creation tracker, `TODO.md`, and
  `PROGRESS.md`.
- When accepted, preserve the exact PDF and its SHA-256. Add it to the internal
  review candidate list without changing its bytes.
- Refresh the published PDF manifest or internal review package only after the
  exact version has regained `Internal review candidate` status.
- A later visible change always creates a new version and review record.

## Environment Path Map

Use the logical names below when moving to another device. Maintained
generators resolve repository assets through `scripts/manuals/lib/guide-paths.mjs`.

| Logical path | Portable default | Migration requirement |
| --- | --- | --- |
| `REPO_ROOT` | Detected from `guide-paths.mjs` | Clone anywhere; do not edit scripts for the new checkout path. |
| `EVIDENCE_ROOT` | `resources/manuals/operator-guides/evidence` | Committed with hashes; no external archive is required. |
| `BASELINE_ROOT` | `resources/manuals/operator-guides/baselines` | Committed locked AC-01/SC-01 inputs; do not edit in place. |
| `PUBLISHED_PDF_ROOT` | `resources/manuals/operator-guides/pdf` | Committed exact internal-review candidates with hashes. |
| `DRAFT_PDF_ROOT` | `resources/manuals/operator-guides/drafts` | Committed latest unaccepted review PDFs with explicit status and hashes. |
| `PROOF_ROOT` | `output/manuals/proofs` | Ignored and recreated locally. |
| `CAPTURE_ROOT` | `output/manuals/captures` | Ignored staging area; promote a capture only through the evidence workflow. |
| `PDF_OUTPUT_ROOT` | `output/pdf` | Ignored compatibility output; never treat it as accepted storage. |
| `HISTORICAL_MANUALS_ROOT` | Former workstation manuals archive | Optional history only; maintained scripts no longer require it. |
| `AFFINITY_RESEARCH` | Former Downloads research file | Optional supporting research; not required for generated-guide work. |
| `CHROME_PATH` | Auto-detected or `GUIDE_CHROME_PATH` | Install Chrome/Chromium when generating rendered proofs. |
| `POPPLER_TOOLS` | Resolved from `PATH` or tool variables | Install `pdfinfo`, `pdftoppm`, and `pdftotext` for complete QA. |
| `NODE_DEPENDENCIES` | `scripts/manuals/package-lock.json` | Run `npm ci` in `scripts/manuals`, or set `GUIDE_NODE_MODULES_ROOT`. |

Do not commit credentials, cookies, `.env` files, production data, or browser
profiles. Browser login state does not transfer between devices.

## Existing Environment Variables

The following variables already work in at least one current script:

| Variable | Current use |
| --- | --- |
| `GUIDE_CAPTURE_URL` | Controlled revised-guide capture base URL. |
| `GUIDE_CAPTURE_USER` / `GUIDE_CAPTURE_PASSWORD` | Controlled capture credentials; never commit values. |
| `GUIDE_CAPTURE_ASSET_TAG` | Asset selected for revised-guide evidence. |
| `GUIDE_CAPTURE_DIR` | Revised-guide evidence output directory. |
| `GUIDE_NODE_MODULES_ROOT` | Optional external directory containing Playwright and Sharp. |
| `GUIDE_CHROME_PATH` | Override Chrome/Chromium executable. |
| `GUIDE_PDFINFO_PATH` / `GUIDE_PDFTOPPM_PATH` / `GUIDE_PDFTOTEXT_PATH` | Override Poppler executables. |
| `GUIDE_PYTHON_PATH` | Override Python used for merged review PDFs. |
| `SNIPEIT_GUIDE_BASE_URL` | User-account capture base URL; this is not yet a canonical QR setting. |
| `SNIPEIT_GUIDE_USER` / `SNIPEIT_GUIDE_PASSWORD` | User-account capture credentials; never commit values. |
| `SNIPEIT_CATALOG_MODEL_ID` / `SNIPEIT_CATALOG_MODEL_NUMBER_ID` | Existing controlled records used by the catalogue evidence capture; no record is created by the capture script. |
| `SNIPEIT_GUIDE_FILTER` | Generate one filtered user-account guide. |
| `SNIPEIT_GUIDE_DATE` | Override the generated guide date. |
| `SNIPEIT_AC01_VERSION` / `SNIPEIT_AST02_VERSION` | Generate an explicit AC-01 or AST-02 review version while accepted defaults remain unchanged. |
| `SNIPEIT_AST03_VERSION` / `SNIPEIT_AST04_VERSION` / `SNIPEIT_AST05_VERSION` | Generate explicit AST lifecycle versions. Current defaults are AST-03 v14 and AST-04/05 v5; older branches remain reproducible. |
| `SNIPEIT_GUIDE_CAPTURE_MODE` | AST capture supports `identity-only`, `ast03-only`, and `ast03-saved-check`; catalogue capture supports `all`, `core`, `definitions`, and `lifecycle`. |
| `SNIPEIT_CMP01_VERSION` / `SNIPEIT_CMP02_VERSION` / `SNIPEIT_CMP04_VERSION` | Generate explicit component review versions. |
| `SNIPEIT_USR01_VERSION` / `SNIPEIT_USR02_VERSION` / `SNIPEIT_USR03_VERSION` / `SNIPEIT_USR04_VERSION` / `SNIPEIT_AC02_VERSION` | Generate explicit user/access review versions; current defaults are the 2026-08-25 visual-correction revisions. |
| `SNIPEIT_CAT00_VERSION` / `SNIPEIT_CAT01_VERSION` | Generate explicit catalogue review versions; current review versions are CAT-00 v4 and CAT-01 v2. |
| `SNIPEIT_WF01_VERSION` / `SNIPEIT_WF02_VERSION` | Generate explicit workflow review versions while accepted defaults remain unchanged. |
| `SNIPEIT_GUIDE_OUT_DIR` | Override focused proof output. |
| `SNIPEIT_GUIDE_RESOURCE_ROOT` / `SNIPEIT_GUIDE_EVIDENCE_ROOT` | Override committed guide resources or evidence. |
| `SNIPEIT_GUIDE_BASELINE_ROOT` / `SNIPEIT_GUIDE_PUBLISHED_PDF_ROOT` | Override locked baseline or PDF roots. |
| `SNIPEIT_GUIDE_DRAFT_PDF_ROOT` | Override the committed unaccepted-draft PDF root. |
| `SNIPEIT_GUIDE_OUTPUT_ROOT` / `SNIPEIT_GUIDE_PDF_OUT_DIR` | Override ignored proof or compatibility PDF output. |

Do not overload capture URLs for published QR behavior. QR destination
customization remains a separate, deferred configuration task.

## New-Device Resume Checklist

1. Clone the repository and check out the intended branch.
2. Confirm this handoff, the registry, scripts, accepted PDFs, unaccepted
   review PDFs, evidence, and manifests are committed before relying on a
   fresh clone.
3. Run `npm ci` from `scripts/manuals`, or set `GUIDE_NODE_MODULES_ROOT` to a
   compatible dependency bundle.
4. Install or locate Chrome/Chromium, Poppler, and PHP. Configure overrides
   only when automatic discovery does not find them.
5. Configure capture credentials only in the local environment. Confirm the
   target is the controlled environment before any state-changing capture.
6. Run `npm test` from `scripts/manuals`. This validates shared components,
   all committed hashes, accepted and unaccepted PDF pages/A4 dimensions,
   explicit review status, and portable paths.
7. Generate the component regression proof and inspect its rendered PDF before
   changing a guide generator on the new machine.
8. Verify source image dimensions and filenames before reusing crops or focus
   bounds. A changed screenshot scale invalidates source-pixel target data.
9. Do not regenerate an accepted artifact merely to test the environment.
   Generate into a temporary path and compare output deliberately.
10. Resume from the target guide's `Next action` in the creation tracker and
    update all required records when that stage changes.

## Before Leaving A Device

- Update this tracker and the authoritative registry.
- Record all latest review decisions and unresolved evidence gaps.
- Commit source documentation, maintained generators, tests, approved PDF
  assets, and the latest explicitly unaccepted review PDFs.
- Preserve canonical evidence and internal review packages in a location that
  will move with the project.
- Record accepted PDF hashes and verify repository/published copies are
  byte-identical.
- Remove credentials and local browser-state references from handoff material.
- Record skipped QA or environment-specific blockers in `PROGRESS.md`.
