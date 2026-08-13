# Operator Guide Visual Fit Postmortem - 2026-06-30

Current status (2026-07-21): historical rejected-proof analysis. Use it to understand failure modes, not as an active build plan. Current production rules are in `docs/manuals/operator-guides/system.md`.

Status: rejected proof analysis. This document explains why `PASS-00A Visual Fit Proof` failed and what should change before another Affinity attempt.

Rejected artifacts:

- `C:\Users\Gebruiker\Documents\snipe-it manuals\visual-fit-tests\2026-06-30\operator-guide-visual-fit-proof-v3.pdf`
- `C:\Users\Gebruiker\Documents\snipe-it manuals\visual-fit-tests\2026-06-30\operator-guide-visual-fit-proof-v3.af`
- `C:\Users\Gebruiker\Documents\snipe-it manuals\visual-fit-tests\2026-06-30\operator-guide-visual-fit-proof-v3-rendered-1.png`

Do not use these as layout patterns. Keep them only as a negative test artifact.

## What Went Wrong

### The Liked Concept Was Treated As A Geometry Spec

The liked generated image worked because it had a calm, simplified visual language. It used clean synthetic UI crops, balanced white space, consistent screenshot styling, and visual hierarchy that made the steps easy to scan.

The rejected proof copied the skeleton but replaced the clean synthetic UI with raw application screenshots. That kept the outer structure but lost the design quality.

Fix:

- Treat the liked image as a style and hierarchy reference, not as exact geometry.
- First build the visual language with simple placeholder frames and labels.
- Add real screenshots only after the frame rhythm works.

### Raw Screenshots Were Not Ready For Layout

The current screenshot sources are tall mobile page captures. They include repeated blue app chrome, navigation bars, and unrelated page sections. Cropping them into short wide frames often showed the wrong information.

Examples from the rejected proof:

- the dashboard crop became a decorative dashboard-card strip instead of a clear "scan here" instruction;
- the scanner crop showed a large black camera area but not a successful camera state;
- the search crop showed the location, but still carried too much app chrome;
- the verification crop was the best crop, but it still needed a stronger highlight around the actual title/tag/model evidence;
- the final workflow row was unrelated to opening an asset and was too cramped.

Fix:

- Make a screenshot crop sheet before layout.
- For each crop, define the target control or visual landmark first.
- Crop out repeated app chrome unless it is needed for orientation.
- Do not place a crop until it answers a specific operator question.

### The Page Scope Drifted

The proof tried to show login/dashboard, scan, search fallback, QR location, verification, help rail, next workflow, related guides, version/source, and large QR on one page.

That is too many jobs for a first visual test. It also mixed `AC-01`, `SC-01`, `AST-01`, and `WF-01` content into one page. The result looked cramped and conceptually noisy.

Fix:

- For `AST-01`, assume login is already done. Keep `AC-01` as a related guide, not a step.
- Decide whether `AST-01` owns scan/search or references `SC-01`.
- If QR scanning is the primary path, make the scan/search portion dominant and remove unrelated workflow content.
- Use the footer only for completion, related guides, version/source, and latest-version QR.

### The Help Rail Became Filler

The help rail copied useful old information, but the proof made every help item the same size and gave it too much vertical presence. It competed with the main guide instead of supporting it.

Fix:

- Use help only where it changes the action on that page.
- Move generic account/password/no-phone help to `AC-01` or `HELP-01` unless the current guide actually needs it.
- For `AST-01`, keep only search/scan-related help such as QR damaged, no camera, or wrong asset.

### The Physical QR Placeholder Was Not Good Enough

The user asked for a photo of common QR locations. The proof used a crude drawn placeholder. That may be acceptable in an internal checklist, but it makes the visual proof look unfinished and undermines trust in the design.

Fix:

- Use a real staged photo or omit the physical-photo block from the next design proof.
- Do not use placeholder device drawings in a visual-quality proof unless the goal is only wireframing.

### The Test Was Built In The Wrong Order

The proof was generated as a PDF and imported into Affinity. That tested whether Affinity could open the file, but not whether Affinity could be used naturally to tune the design.

Fix:

- Next test should start inside Affinity with a small native layout fragment.
- Test only one or two block types: primary visual block and fallback/verification block.
- Export a PDF after the native spacing looks acceptable.

## Better Next Test

Do not build another full A4 guide first.

Build a small `Visual Style Strip` proof:

- one header/context fragment;
- one large primary QR-scan block;
- one smaller fallback search block;
- one verification block with inline stop;
- no footer, no help rail, no related guide chips yet.

Acceptance criteria:

- the page fragment looks good before screenshots are inserted;
- real screenshots improve the block instead of dominating it;
- the primary visual has enough height to understand the action;
- the fallback visual is visibly secondary;
- the inline stop is tied to the verification block;
- no unrelated workflow/login content appears in `AST-01`.

Only after that works should a full A4 page be attempted.

## Revised AST-01 Direction

Recommended content hierarchy for the next real proof:

1. Primary path: scan QR code.
2. Alternative path: search by asset tag or QR value.
3. Physical context: common QR label location photo.
4. Verification: check asset title/tag/model against the device.
5. Stop: mismatch warning attached to verification.

Leave login, dashboard orientation, workflow start, and generic account help to related guides unless the guide explicitly combines multiple guide blocks on one page.
