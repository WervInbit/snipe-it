# Operator Guide Clean Design Plan - 2026-06-30

Current status (2026-07-21): deferred Affinity reference. Do not perform Affinity preparation or handoff work until every active generated guide is confirmed or the user gives an explicit green light. Continue current guide production from `docs/manuals/operator-guides/README.md`.

Status: working design contract for the next Affinity pass. This is the plan to follow before creating another native Affinity file.

July 2 foundation: use `docs/manuals/operator-guide-design-foundation-2026-07-02.md` together with this clean-build plan. This file controls clean Affinity build hygiene; the foundation controls the reusable guide grammar for steps, screenshot labels, alternatives, help tiles, and warning placement.

## Decision

Create the design plan first, then create a clean Affinity file from scratch.

Do not continue editing the failed native Affinity draft. The next Affinity pass must start from a clean A4 document or a deliberately empty duplicate, not from an old proof page with inherited objects.

## Failed Files

Do not use these as bases:

- `C:\Users\Gebruiker\Documents\snipe-it manuals\layout-proofs\2026-06-30-ac01-ast01-native\operator-guides-ac01-ast01-native-affinity-v2-feedback-pass.af`
- `C:\Users\Gebruiker\Documents\snipe-it manuals\visual-fit-tests\2026-06-30\operator-guide-visual-fit-proof-v3.af`
- generated `operator-guide-visual-fit-proof-v3.*` PDF/render files

They may be kept as negative test artifacts only.

## Why The Clean Build Is Needed

The failed Affinity files became unusable because new content was added into an existing imported/original plan page with inherited object state:

- selected objects carried transforms into new text and image frames;
- text frames inherited wrong rotation or oversized styling;
- image placement sometimes replaced existing frames instead of creating controlled screenshot slots;
- old guide artwork and new rough content overlapped;
- raw full-page screenshots were placed before crop purpose and frame size were locked;
- no export proof was inspected before calling the file usable.

The clean pass must remove those causes, not attempt to repair the broken file.

## Source Material Rules

Allowed:

- real app screenshots from `https://dev.inbit/`;
- existing screenshots under `C:\Users\Gebruiker\Documents\snipe-it manuals\screenshot-source\2026-06-25-blocks`;
- new cropped PNGs made from real screenshots;
- real or staged photos for physical QR locations;
- clearly labelled placeholders only for missing real evidence.

Not allowed:

- generated design images as guide content;
- imported full-page generated PDFs as the layout base;
- old failed `.af` files as the next working source;
- decorative screenshots with no operator job.

## Clean Affinity Build Rules

Before placing content:

- Create a new A4 portrait document: 210 mm x 297 mm.
- Set 10 mm margins.
- Create text styles before typing:
  - title: 18-22 pt bold;
  - step heading: 13-15 pt bold;
  - body: 12 pt regular;
  - caption/help/footer: 8-9 pt.
- Use frame text for body blocks. Avoid artistic text for guide copy.
- Use picture frames or deliberately placed cropped PNGs. Do not place raw full screenshots unless they are intentionally a context visual.
- Turn off or avoid `Replace Existing` behavior when placing screenshots.
- If a placed item inherits wrong rotation, wrong scale, or overlaps unexpectedly, stop and revert to the last clean checkpoint. Do not keep stacking fixes in the same file.

After each pass:

- Save a named `.af` checkpoint.
- Export a PDF or PNG proof.
- Inspect the proof visually before expanding the design.
- Only continue if the proof is legible and matches the plan.

## Next Build Order

### PASS-00B Clean Style Strip

Purpose: prove the core visual language before making a full page.

Build only these pieces:

1. Compact header/context fragment.
2. Large primary scan block.
3. Smaller fallback search block.
4. Verification block with inline or attached `STOP`.

Do not include:

- footer QR;
- related guide strip;
- generic help rail;
- login sequence;
- workflow content;
- final full-page polish.

Acceptance:

- the strip looks good before screenshots are inserted;
- the primary scan visual has enough height to be useful;
- fallback search is visibly secondary;
- verification and `STOP` are the same visual unit or visibly attached;
- no unrelated login/workflow content is present.

### PASS-01 AC-01 Compact Login

Build only after `PASS-00B` is accepted.

Target: compact block, likely half page, but not forced if readability suffers.

Required visual jobs:

| Visual | Job | Status |
| --- | --- | --- |
| Phone/browser start or quicklink/URL | Show where the user begins | Missing; use placeholder until captured |
| Login form | Show the login screen and `Inloggen` control | Available |
| Dashboard or Scan QR visible | Show expected end state | Available |

Required help:

- no account: ask supervisor;
- forgotten password: reset/supervisor flow;
- no phone/device: use shared workstation or ask supervisor.

Finished when:

- dashboard or scan entry is visible.

### PASS-02 AST-01 Open Existing Asset

Build only after the style strip is accepted.

Primary goal: open the correct asset and verify it against the physical device before changing anything.

Required visual jobs:

| Visual | Job | Status |
| --- | --- | --- |
| Scanner entry / QR scan | Primary path | Current scan screenshot exists, but live-camera state still missing |
| Manual search field | Alternative path: asset tag or QR value | Needs tight crop from real app |
| Physical QR location | Physical context: where to find the label | Missing; needs real/staged photo |
| Asset header/detail | Verification: tag, title, model | Available |
| Mismatch warning | Stop support | Text/callout, no screenshot required |

Step rhythm:

1. Scan the QR code on the device.
2. If scanning does not work, search by asset tag or QR value.
3. Open the asset result.
4. Check asset tag, title, and model against the device.
5. Stop and ask if tag, model, or physical device does not match.

Do not print serial-number search as current behavior until the product supports it.

Help rail:

- camera permission blocked;
- QR damaged or missing;
- no matching result;
- wrong asset opened.

Finished when:

- the correct asset detail page is open and matches the device in hand.

## Screenshot Preparation Before Affinity

Prepare crops before placing them in Affinity. This keeps Affinity automation simple and avoids fighting with raw screenshot framing.

Each crop must have:

- guide code;
- visual job;
- source screenshot/photo;
- intended frame size;
- whether it is final, placeholder, or needs recapture.

Suggested crop folder:

`C:\Users\Gebruiker\Documents\snipe-it manuals\layout-proofs\2026-06-30-clean-build\crops`

## Acceptance Checklist

A proof is acceptable only if:

- it starts from a clean page;
- no old template/proof content is visible unless intentionally placed;
- all text is readable at A4 print size;
- screenshots are purposeful and not duplicated;
- screenshot controls or landmarks are readable;
- warnings appear at the relevant step;
- `AC-01` help is compact but present;
- `AST-01` mismatch stop is attached to verification;
- step-specific stop warnings are never detached into a lower help strip, help rail, or footer;
- missing real evidence is clearly labelled as missing, not faked;
- a PDF/PNG proof was exported and inspected.

## Open Inputs Before Final Draft

- Real phone/browser start visual for `AC-01`.
- Physical QR-location photo for common devices.
- Successful scan/camera state for `SC-01` / `AST-01`.
- Tight crop of manual search with example asset tag or QR value.
- Final decision whether `AC-01` shares a page with scan/search or remains standalone.
