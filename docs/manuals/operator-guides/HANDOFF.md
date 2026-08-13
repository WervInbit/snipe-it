# Operator Guide Continuation Handoff

Status: current continuation checkpoint, updated 2026-08-13.

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
| AC-01 Login | v6 | Internal review candidate | Freeze exact artifact; include in published set. |
| AC-02 Eigen wachtwoord wijzigen | v1 draft | Working draft | Review wording/evidence and decide whether a success-state capture is needed. |
| SC-01 Asset vinden en openen | v10 | Internal review candidate | Freeze exact artifact; include in published set. |
| AST-02 Refurbishment route | v5 | Internal review candidate | Freeze exact artifact; update only when child-guide routing changes. |
| AST-03 Asset registreren en labelen | v1 draft | Evidence incomplete | Confirm registration fields, status, quality, and location; capture the missing states. |
| AST-04 Werk afronden en overdragen | v1 draft | Evidence incomplete | Define the handoff status and recognizable review location, then capture them. |
| AST-05 Asset beoordelen en vrijgeven | v1 draft | Evidence incomplete | Define the approved release status and confirmed end state. |
| WF-01 Workflow starten | v9 | Internal review candidate | Freeze exact artifact; include in published set. |
| WF-02 Workflow uitvoeren en afronden | v10 | Internal review candidate | Freeze both pages; include in published set. |
| CMP-01 Bestaand component plaatsen | v4 | Internal review candidate | Freeze exact artifact; include in published set. |
| CMP-02 Nieuw component registreren en plaatsen | v2 draft | Working draft | Review current page and confirm deployed component permission labels before third-party approval. |
| CMP-04 Component naar tray verplaatsen | v5 draft | Working draft | Review the current focused page. |
| HELP-01 Problemen en hulp | v6 draft | Working draft | Review all recovery tiles and guide handoffs. |
| USR-01 Gebruiker toevoegen | v8 | Internal review candidate | Freeze exact artifact; include in published set. |
| USR-02 Rol en rechten wijzigen | v7 | Internal review candidate | Freeze exact artifact; include in published set. |
| USR-03 Wachtwoord resetten | v1 draft | Working draft | Review both reset routes; define secure temporary-password handoff and confirmation. |
| USR-04 Gebruiker uitschakelen of herstellen | v1 draft | Working draft, two-sided | Review deactivation, deletion, and restoration on both pages. |
| USR-05 Groepen beheren | Planned | Investigation required | Investigate group list, add/edit controls, permission behavior, and verification route. |

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
| `SNIPEIT_GUIDE_FILTER` | Generate one filtered user-account guide. |
| `SNIPEIT_GUIDE_DATE` | Override the generated guide date. |
| `SNIPEIT_GUIDE_OUT_DIR` | Override focused proof output. |
| `SNIPEIT_GUIDE_RESOURCE_ROOT` / `SNIPEIT_GUIDE_EVIDENCE_ROOT` | Override committed guide resources or evidence. |
| `SNIPEIT_GUIDE_BASELINE_ROOT` / `SNIPEIT_GUIDE_PUBLISHED_PDF_ROOT` | Override locked baseline or PDF roots. |
| `SNIPEIT_GUIDE_OUTPUT_ROOT` / `SNIPEIT_GUIDE_PDF_OUT_DIR` | Override ignored proof or compatibility PDF output. |

Do not overload capture URLs for published QR behavior. QR destination
customization remains a separate, deferred configuration task.

## New-Device Resume Checklist

1. Clone the repository and check out the intended branch.
2. Confirm this handoff, the registry, scripts, accepted PDFs, and manifests
   are committed before relying on a fresh clone.
3. Run `npm ci` from `scripts/manuals`, or set `GUIDE_NODE_MODULES_ROOT` to a
   compatible dependency bundle.
4. Install or locate Chrome/Chromium, Poppler, and PHP. Configure overrides
   only when automatic discovery does not find them.
5. Configure capture credentials only in the local environment. Confirm the
   target is the controlled environment before any state-changing capture.
6. Run `npm test` from `scripts/manuals`. This validates shared components,
   all committed hashes, accepted PDF pages/A4 dimensions, and portable paths.
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
- Commit source documentation, maintained generators, tests, and approved PDF
  assets intended for deployment.
- Preserve canonical evidence and internal review packages in a location that
  will move with the project.
- Record accepted PDF hashes and verify repository/published copies are
  byte-identical.
- Remove credentials and local browser-state references from handoff material.
- Record skipped QA or environment-specific blockers in `PROGRESS.md`.
