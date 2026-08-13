# Operator Guide Capture Retry - 2026-08-04

## Context

- Re-read the current progress log, fork notes, and browser-control guidance before retrying the controlled workflow page.
- Current task remains the WF-01/WF-02 rebuild with neutral workflow states and deterministic screenshot annotations.

## Result

- `https://dev.inbit/hardware/1#tests` is reachable but displays an application-upgrade block because database migrations are incomplete.
- Browser capture stopped without running migrations or changing shared application data.
- Guide implementation remains paused until the development application loads normally.

## Recovery And Completion

- A separate code/database recovery block cleared the upgrade and authenticated asset-page errors; the screenshot account was restored and the controlled asset page loaded normally.
- The authenticated browser session created one blank `Standard Diagnostics` run (`run=11`) on hardware asset 1. No result, note text, or photo was submitted.
- Saved current mobile-width entry, neutral-card, expanded-instruction, open-note, and open-photo captures under `C:\Users\Gebruiker\Documents\snipe-it manuals\screenshot-source\2026-08-04-workflow-neutral`.
- Updated `scripts/manuals/generate-workflow-guide-review.mjs` to use deterministic SVG viewboxes and source-pixel targets instead of percentage/object-fit positioning.
- Generated WF-01 v7, WF-02 v5, and the three-page combined review PDF under `C:\Users\Gebruiker\Documents\snipe-it manuals\layout-proofs\2026-08-04-workflow-review-batch-v3`.
- Visually checked every rendered page and verified A4 page counts. Both guide versions remain generated drafts awaiting user review.
- Superseded that first neutral-state pass with review batch v4 after user feedback. WF-01 v8 delegates asset validation to SC-01, integrates continuation into step 3, and uses an unchopped card crop; WF-02 v6 uses a complete breadcrumb, aligned 2A/3A targets, and no duplicated low-risk warnings in steps 2/3.
- Batch v4 is under `C:\Users\Gebruiker\Documents\snipe-it manuals\layout-proofs\2026-08-04-workflow-review-batch-v4`; all three rendered pages were visually checked and retain the intended A4 counts.
- Batch v5 supersedes v4 for review: WF-01 v9 uses an `OF` divider and equal heading hierarchy for its step-3 alternative, while WF-02 v7 centers 2A/3A from measured source-pixel control bounds. Outputs are under `C:\Users\Gebruiker\Documents\snipe-it manuals\layout-proofs\2026-08-04-workflow-review-batch-v5`.
- Batch v6 keeps WF-01 v9 unchanged and advances WF-02 to v8 by centering 4A on `Notitie` and 5A on `Foto toevoegen` from measured source-pixel bounds. Outputs are under `C:\Users\Gebruiker\Documents\snipe-it manuals\layout-proofs\2026-08-04-workflow-review-batch-v6`.
- Batch v7 keeps WF-01 v9 unchanged and advances WF-02 to v9. Step 1 now uses `Valideer de actieve workflow`, and the front-page completion handoff says `Ga verder op de volgende pagina.` Outputs are under `C:\Users\Gebruiker\Documents\snipe-it manuals\layout-proofs\2026-08-04-workflow-review-batch-v7`; both WF-02 pages were visually checked and the 1/2/3 A4 page counts were verified.
- Recorded WF-01 v9 as explicitly approved for V1. Batch v8 keeps that PDF unchanged and advances WF-02 to v10: 4A retains the native yellow active state on `Notitie` and moves the red annotation to the note-entry field. Outputs are under `C:\Users\Gebruiker\Documents\snipe-it manuals\layout-proofs\2026-08-04-workflow-review-batch-v8`.
- Recorded WF-02 v10 as explicitly approved for V1 without regenerating the reviewed artifact. The verified in-app `Foto` and `Foto toevoegen` controls are accepted as sufficient V1 evidence; a device-native camera/file-picker capture is no longer an approval blocker.
- Captured the actual CMP-01 existing-component flow with controlled component `INBIT-C-UW4626` / serial `CMP01-RAM-0001`, then installed it back into `DEMO-001`. Generated one-page CMP-01 v4 under `C:\Users\Gebruiker\Documents\snipe-it manuals\layout-proofs\2026-08-04-component-review-batch-v1`; the prior five-step placeholder design is replaced by four real steps, pixel-measured target overlays, and a verified tracked-row end state.
- Recorded the user's exact acceptance of CMP-01 v4 as `Base approved for V1`.
- Captured and verified the live CMP-02/CMP-04 follow-up flow with controlled component `INBIT-C-HH9376` / `CMP02-RAM-0001`. The definition-backed record was installed once, the custom route was not submitted, and the record now ends in tray with no asset attached.
- Generated CMP-02 v2, CMP-04 v4, and HELP-01 v6 under `C:\Users\Gebruiker\Documents\snipe-it manuals\layout-proofs\2026-08-04-component-followup-review-batch-v1`; all are one-page A4 review drafts with source-pixel targets.
- Generated follow-up batch v2 with CMP-04 v5 after centering the 1B target on the measured `Naar tray` action. Documented the route boundary: CMP-01 installs an existing tracked tray/storage record; CMP-02 route 2A creates a new tracked component from a catalog definition.
- Copied the six exact V1-approved guide PDFs to `C:\Users\Gebruiker\Documents\snipe-it manuals\internal-review\accepted-guides-v1-2026-08-04` with an internal-review manifest. Verified hashes, A4 dimensions, and page counts; no draft-only guide was included.
- Added exact copies of the five generators used for the accepted guide set plus `scripts/SCRIPT-MAP.md`; the map records ownership, generation order, dependencies, and the non-portable absolute evidence paths.
