# Operator Guide Continuation Handoff

## Accepted Guide Folder - 2026-09-10

Latest delivery: [accepted-guides-v1](../../../resources/manuals/operator-guides/accepted-guides-v1/README.md),
with 16 current accepted PDFs / 27 pages and seven older accepted PDFs.
Start with its README to regenerate from embedded HTML or create a new
version. Its scripts preserve accepted files and put proofs under `work/`.
All 16 guides regenerated from a relocated copy with all 27 pages identical
at 96 dpi. The exact manifest keeps WF-02 v10 and CMP-01 v4 because their
newer proposals lack acceptance. Other-user review remains pending.
Earlier delivery requests below describe previous stages.

## Owner Clearance For User Review - 2026-09-10

The owner cleared the ten current changed versions in the v5 pair for review
by other users. They are now Internal review candidates; the exact PDFs stay
unchanged. Use the [dated clearance and hashes](reviews/owner-review-readiness-2026-09-10.md)
and current-guide-selection-v4.json for current status. Earlier four exact
acceptances remain; the separate prototype is outside this clearance.
Other-user feedback and third-party approval are still pending. Earlier
generation-time draft/pending wording below is historical for these versions.


Latest delivery scope: the owner requested **only the ten changed guides**.
Use the plain v5 previous/current pair (20/19 pages) in the
[correction review](reviews/owner-corrections-2026-09-10.md).
The v4 pair below remains the full 22-guide comparison.

## Implemented Owner Corrections - 2026-09-10

Use the [correction review and plain v4 pair](reviews/owner-corrections-2026-09-10.md)
for current review: ten revised guides, 22 guides total, previous 48 pages and
current 47 pages. USR-04 v6 is disable-login only and requires a new review.
Earlier exact acceptances remain. The field-to-result prototype is separate.
Testing completed is the preferred future QA label; no Awaiting approval
state or application status change is included. Older sections below are history.

Status: current continuation checkpoint, updated 2026-09-10.

Latest owner feedback: read the [review TODOs](reviews/owner-review-todos-2026-09-10.md)
before generating anything further. USR-04 v5 is explicitly not accepted;
the general draft direction is suitable with the listed corrections. Make
local visual fixes separately from USR scope, QA status and catalogue redesign
decisions. No PDF or application change was made during this feedback pass.

Use this document when resuming guide creation in another task, on another
device, or after a long interruption. It records the resume order, current
creation stage, and environment-specific dependencies. It does not replace the
authoritative specifications, registry, component rules, or review records.

## Exact Acceptances And Scrolling Bundle - 2026-09-10

Latest clarification: use the **plain v2 previous/current pair** in the
[current-set review record](reviews/current-set-review-2026-09-10.md), 48 pages
each, same order, no added contents or bookmarks. The v1 presentation below
is retained history and is not the requested comparison format.

The owner accepted AC-01 v9, AC-02 v4, AST-03 v15, and AST-04 v6.
Use [the current-set review record](reviews/current-set-review-2026-09-10.md)
for the combined v1 PDF, all 22 current versions, exact hashes and page map.
The bundle contains 48 unchanged guide pages plus clickable contents.
The four creation-tracker rows below now reflect these decisions. Other rows
retain the earlier baseline; use the current selection for ongoing PDF review.
Earlier dated sections describe the state at that time. Keep every prior file.

## Frozen Baseline And New Review Round - 2026-09-08

The owner requested a separator between prior guide work and the new-model
audit revisions, with easy comparison and rollback if proposals are rejected.
The [frozen baseline](reviews/baseline-2026-09-08.md) preserves all 29 selected
PDFs (9 internally accepted, 20 drafts), source inputs, original manifests, and
checksums. Do not replace checkpoint files. Use the separate
[audit revision ledger](reviews/audit-revision-round-2026-09-08.md) for new
candidates, changed steps, tradeoffs, and exact-version decisions. The first
comparison batch now contains WF-01 v11 (one page), USR-04 v4 (three pages),
and CAT-04 v3 (six pages). All are unaccepted and kept in a separate review-
round directory; the creation tracker and default draft selections below
still describe the earlier baseline. Existing acceptance is unchanged.

The owner rejected this batch's design direction on 2026-09-08. Read the
[feedback and revised approach](reviews/2026-09-08-pilot-direction-feedback.md)
before further generation. Keep the rejected artifacts. The latest proposal is
WF-01 v12, with filename `WF-01-workflow-starten-v12-draft.pdf` and review record
`WF-01-v12.md`, as explicitly requested by the owner. Keep this filename stem
and increment the version for later revisions; do not introduce naming variants.
V12 is now generated from WF-01 v10, preserving 2A selection, 3A start, all
screenshots, hints and bottom help, and improving only the step-3 choice
grouping. See [v12 review and exact PDF](reviews/WF-01-v12.md). The owner
reviewed the direction positively and authorized applying it to all existing
guides. Reproduce the retained example with
`node scripts/manuals/generate-wf01-v12.mjs`; the previous selections remain.
Do not propagate the rejected pilot layout or page model. Physical-print and
first-time-user validation remain pending.

## All-Guide Focused Candidates - 2026-09-08

All 22 existing guides were covered. Twenty new PDFs preserve the baseline
page models and screenshot counts; WF-01 v12 and CMP-04 v6 are retained.
Use the [comparison index](reviews/guide-set-comparison-2026-09-08.md) for
the exact old/new pairs and individual review records. These candidates do
not replace the selected/accepted manifests or the creation tracker below.

Generate with `node scripts/manuals/generate-guide-followups.mjs` and the
portable Node/Python dependency settings below. The runner copies historical
renderers into isolated output, applies focused patches, and finalizes the
new visible version. Historical renderers remain unchanged. Review-round
retention is separate and refuses to replace existing files.

The retained `guide-set-followups-manifest.json`, validation and source ZIP
sit beside the existing round artifacts. Proof PNGs, text diffs and HTML are
under `output/manuals/proofs/guide-followups-2026-09-08`. Review exact versions
with the owner, then perform physical/user validation. Do not recreate the
rejected sparse pilot or describe every audit issue as closed.

## Independent Audit Checkpoint - 2026-09-08

The owner requested an independent audit for first-time users, including
autistic users who need explicit, predictable steps. The
[completed audit](reviews/2026-09-08-independent-usability-audit.md) covers all
22 current generated guides / 48 pages and records 22 findings. Consult its
correction order before further generation: role/identity/branching and
application-truth issues precede visual polishing. The audit is an inspection,
not a physical user-test pass or an acceptance change. Existing guide versions
and the creation tracker below remain unchanged.

The strict package verifier currently also reports unlisted CAT-01 v4,
alongside the already noted CAT-00 v7/v8. A fresh manifest-only draft mirror
passes; the actual-root package has not regained a clean pass.

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
6. For CAT work, read
   [catalog-guide-plan.md](catalog-guide-plan.md) before any CAT specification
   or generator. It controls topic ownership across CAT-00 through CAT-06.
7. Read the target `guides/<CODE>.md`, its latest `reviews/<CODE>-vN.md`, and
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
| AC-01 Login | v9 | Internal review candidate; owner accepted 2026-09-10 | Preserve exact PDF; continue reviewing the remaining guides using the combined bundle. |
| AC-02 Eigen wachtwoord wijzigen | v4 | Internal review candidate; owner accepted 2026-09-10 | Preserve exact PDF; continue reviewing the remaining guides using the combined bundle. |
| SC-01 Asset vinden en openen | v10 | Internal review candidate | Freeze exact artifact; include in published set. |
| AST-02 Refurbishment route | v6 draft | Working draft; v5 remains accepted | Review the Refurbisher-only role line. |
| AST-03 Asset registreren en labelen | v15 | Internal review candidate; owner accepted 2026-09-10 | Preserve exact PDF; continue reviewing the remaining guides using the combined bundle. |
| AST-04 Werk afronden en overdragen | v6 | Internal review candidate; owner accepted 2026-09-10 | Preserve exact PDF; continue reviewing the remaining guides using the combined bundle. |
| AST-05 Asset beoordelen en vrijgeven | v5 draft | Visual-correction pass; awaiting exact-version review | Review full family-styled help and related-guide handoffs. |
| WF-01 Workflow starten | v10 draft | Working draft; v9 remains accepted | Review Refurbisher role and centered 3B `Bewerk` target. |
| WF-02 Workflow uitvoeren en afronden | v11 draft | Working draft; v10 remains accepted | Review Refurbisher role on both pages. |
| CMP-01 Bestaand component plaatsen | v5 draft | Working draft; v4 remains accepted | Review Senior Refurbisher role. |
| CMP-02 Nieuw component registreren en plaatsen | v4 draft | Visual-correction pass; awaiting exact-version review | Build the next draft from the `-04` definition/custom form captures and remeasure both choice targets. |
| CMP-04 Component naar tray verplaatsen | v6 draft | Cold-start pass; awaiting exact-version review | Review Senior Refurbisher role and the unchanged verified removal path. |
| HELP-01 Problemen en hulp | v6 draft | Working draft | Review all recovery tiles and guide handoffs. |
| USR-01 Gebruiker toevoegen | v11 draft | Visual-correction pass; v8 remains accepted | Review the full USR-02 help handoff; account flow is unchanged. |
| USR-02 Rol en rechten wijzigen | v9 draft | Visual-correction pass; v7 remains accepted | Review the separate `Machtigingen` and rights-grid focus areas. |
| USR-03 Wachtwoord resetten | v3 draft | Visual-correction pass; awaiting exact-version review | Review the visible unsaved generated value, `Genereer` focus, and full AC-02 handoff. |
| USR-04 Gebruiker uitschakelen of herstellen | v3 draft | Visual-correction pass; awaiting exact-version review | Review rectangular actions, contained warning text, and full references on both pages. |
| USR-05 Groepen beheren | Planned | Investigation required | Investigate group list, add/edit controls, permission behavior, and verification route. |
| CAT-00 Catalogus begrijpen | v9 draft | Portable unaccepted diagram-correction revision | Review the exact six-page overview, especially the unobstructed object-map connectors, page 3 definition branches, page 4 state branch, and guide routing. |
| CAT-01 Model en modelnummer aanmaken | v5 draft | Portable unaccepted administration flow | Review the two-page duplicate check, action-named Basismodel creation route, default-configuration label rule, complete save flow, and CAT-02/AST-03 handoffs. |
| CAT-02 Modelspecificatie opbouwen | v1 draft | Portable unaccepted six-page working draft | Review exact-model validation, direct/component choice, both add routes, derived-value conflict handling, Save, and final verification. |
| CAT-03 Attributen beheren | v1 draft | Portable unaccepted five-page working draft | Build the next draft from the four `-02` attribute-form captures, then review scope/behavior, numeric/Enum alternatives, and the saved-row check. |
| CAT-04 Componentdefinities beheren | v2 draft | Portable unaccepted six-page working draft | Build the next draft from the five `-02` component-definition captures, then review Required guidance, contributions, hierarchy correction, and Save. |
| CAT-05 Varianten en lifecycle beheren | Planned | Scope/title review required | Narrow to branching Admin lifecycle and cleanup; do not repeat variant creation from CAT-01 or invent component-definition deletion. |
| CAT-06 Catalogus controleren en bronnen | Planned | Verification scope ready; recording policy unresolved | Decide whether v1 is verification-only; do not claim durable source storage without an implemented field or approved convention. |

AST-01 is retired into SC-01 and remains historical evidence only.

CAT-02 v1 is generated and awaits exact-version review. The next production
task is CAT-05 lifecycle and saved-row cleanup; CAT-06 remains dependent on
the source-recording decision. CAT-03 and CAT-04 are already generated
definition-management references and must not be replanned as downstream work
from CAT-02.

The 2026-09-03 form-control recapture added 11 replacement evidence sources
without overwriting historical files. Before further CAT-03, CAT-04, or CMP-02
review, generate a new version from those sources and remeasure focus bounds.
See `reviews/evidence-form-control-recapture-2026-09-03.md`.

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
| `SNIPEIT_GUIDE_BASE_URL` | Controlled user-account or catalogue capture base URL; this is not a canonical QR setting. |
| `SNIPEIT_GUIDE_USER` / `SNIPEIT_GUIDE_PASSWORD` | Controlled capture credentials; never commit values. |
| `SNIPEIT_CATALOG_MODEL_ID` / `SNIPEIT_CATALOG_MODEL_NUMBER_ID` / `SNIPEIT_CATALOG_COMPONENT_DEFINITION_ID` | Existing controlled records used by catalogue evidence capture; no record is created by the capture script. |
| `SNIPEIT_GUIDE_FILTER` | Generate one guide from a generator that supports focused output. |
| `SNIPEIT_GUIDE_DATE` | Override the generated guide date. |
| `SNIPEIT_AC01_VERSION` / `SNIPEIT_AST02_VERSION` | Generate an explicit AC-01 or AST-02 review version while accepted defaults remain unchanged. |
| `SNIPEIT_AST03_VERSION` / `SNIPEIT_AST04_VERSION` / `SNIPEIT_AST05_VERSION` | Generate explicit AST lifecycle versions. Current defaults are AST-03 v14 and AST-04/05 v5; older branches remain reproducible. |
| `SNIPEIT_GUIDE_CAPTURE_MODE` | AST capture supports `identity-only`, `ast03-only`, and `ast03-saved-check`; catalogue capture supports `all`, `core`, `number-search`, `spec`, `model-spec`, `definitions`, `catalog-admin`, `attribute-definitions`, and `component-definitions`. |
| `SNIPEIT_CMP01_VERSION` / `SNIPEIT_CMP02_VERSION` / `SNIPEIT_CMP04_VERSION` | Generate explicit component review versions. |
| `SNIPEIT_USR01_VERSION` / `SNIPEIT_USR02_VERSION` / `SNIPEIT_USR03_VERSION` / `SNIPEIT_USR04_VERSION` / `SNIPEIT_AC02_VERSION` | Generate explicit user/access review versions; current defaults are the 2026-08-25 visual-correction revisions. |
| `SNIPEIT_CAT00_VERSION` / `SNIPEIT_CAT01_VERSION` / `SNIPEIT_CAT02_VERSION` / `SNIPEIT_CAT03_VERSION` / `SNIPEIT_CAT04_VERSION` | Generate explicit catalogue review versions; current versions are CAT-00 v9, CAT-01 v5, CAT-02 v1, CAT-03 v1, and CAT-04 v2. |
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
