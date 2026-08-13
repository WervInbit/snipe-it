# Operator Guide Design Foundation - 2026-07-02

Current status (2026-07-21): supporting design source. The active shared rules are consolidated in `docs/manuals/operator-guides/system.md`, and guide-specific rules live under `docs/manuals/operator-guides/guides/`. This file remains useful for detailed rationale and examples.

Status: working foundation for the next Affinity passes. Use this as the current design grammar for floor/operator guide drafts unless the user explicitly changes direction.

This document does not lock final guide wording. It locks how guide information should be structured so `AC-01`, `SC-01`, `AST-01`, `WF-01`, `CMP-04`, `HELP-01`, and later guides can be built in a consistent style.

## Why This Foundation Exists

The guide work was stagnating because the source information was spread across planning notes, feedback notes, Affinity block specs, research notes, rejected proofs, and live manual Affinity edits. The individual pieces were useful, but they did not yet form one repeatable system.

The immediate design problem is visible in `AST-01`:

- the clean v5 proof is calmer and usable as a base direction;
- the stop warning is now correctly attached to the verification block;
- the lower help area works better as small help tiles;
- but step 1 and step 2 are currently shown as sequential even though they are alternatives for opening the scanner;
- screenshots are not yet independently labelled or captioned enough for users who scan the page visually;
- the next guides will repeat these problems unless alternatives, screenshot numbering, captions, help, and stop rules are standardized.

## Inputs Reviewed

Source documents:

- `docs/manuals/operator-guide-planning.md`
- `docs/manuals/operator-guide-feedback-replan-2026-06-30.md`
- `docs/manuals/affinity-development-blocks-2026-06-25.md`
- `docs/manuals/operator-guide-clean-design-plan-2026-06-30.md`
- `docs/manuals/operator-guide-visual-fit-postmortem-2026-06-30.md`
- `C:\Users\Gebruiker\Downloads\affinity research deep-research-report.md`

Proofs/artifacts inspected:

- `AST-01-open-asset-clean-v5-proof.png`
- `AST-01-open-asset-clean-v4-proof.png`
- `AC-01-login-clean-v1-proof.png`
- rejected `operator-guide-visual-fit-proof-v3-rendered-1.png`
- the user's current Affinity screenshot showing two images placed in the step 1 area

Current screenshot source dimensions:

- `AC-01` phone screenshots: `780 x 1688`
- `AST-01` phone screenshots: `780 x 1688`
- these are adequate source captures for cropping, but full phone screenshots should not be placed raw unless they are intentionally used as orientation visuals.

## Document Precedence

Use documents in this order:

1. This design foundation: current cross-guide structure and visual grammar.
2. `operator-guide-feedback-replan-2026-06-30.md`: feedback rationale and guide-specific direction.
3. `affinity-development-blocks-2026-06-25.md`: reusable block inventory and build queue.
4. `operator-guide-clean-design-plan-2026-06-30.md`: clean Affinity build safety rules.
5. `operator-guide-planning.md`: guide inventory, broad brainstorming, and open questions.
6. Rejected proof docs and old PDFs: negative examples only.

If this document conflicts with an older brainstorming note, prefer this document for the next Affinity work.

## Core Design Principle

Each page must work as a bench-side work card, not as a miniature app manual.

At every point on the page, the operator should be able to answer four questions:

1. What do I do now?
2. What am I looking for on screen or on the physical device?
3. What do I do if this path does not work?
4. When must I stop and ask?

Screenshots support those answers. They are not decoration and they are not a screenshot gallery.

## Flexible Page Density Rules

This is a flexible design system, not a fixed `AST-01` page template.

The shell stays consistent:

- header;
- compact context strip;
- main work area;
- optional help layer;
- `Klaar als`;
- related guide chips;
- source/version line and digital guide QR.

The content density changes per guide:

- a guide may have 2-7 actual steps;
- a guide may have no screenshots, one screenshot, or many screenshots;
- a step may have one visual, multiple equivalent visual options, a main visual plus fallback, or no visual;
- a guide may have no help tiles, one help tile, or several help tiles;
- a compact guide may share a page only if print readability survives;
- a dense guide may use a back side or point to a related guide.

Do not force consistency by making every guide use the same number of steps, screenshots, or help items. Consistency comes from the shared grammar:

- actual steps use big numbers;
- alternatives use sublabels such as `1A` and `1B`;
- screenshots/photos are numbered and captioned;
- every visual has a purpose;
- `STOP` is attached to the relevant step;
- help remains visually secondary;
- footer/source/related-guide patterns stay recognizable.

If a guide becomes too dense, split the guide, use the back side, or move support content to `HELP-01`. Do not shrink body text, captions, or critical screenshots below useful print size just to keep a one-page shape.

## Guide Types

Use one of these guide types before starting layout.

| Type | Use | Example | Page Pattern |
| --- | --- | --- | --- |
| Compact access card | Short prerequisite flow | `AC-01 Login` | 2-3 state visuals, help block, finish box |
| Single task card | One floor task with 3-5 actions | `AST-01 Open Asset`, `SC-01 Scan Asset` | numbered steps, screenshots, help tiles |
| High-level route card | Overview that points to smaller guides | `AST-02 Existing Asset Refurbishment` front | 5 bands, reference chips, minimal detail |
| Detail task card | Dense execution flow | `WF-02 Complete Workflow`, `CMP-04 Remove Component` | step list plus visual rail or large action crops |
| Troubleshooting card | Non-linear problems | `HELP-01 Common Problems` | problem tiles, references, escalation |
| Admin/reference card | Configuration or catalog explanation | `CFG-*` | worked example, fewer screenshots, more structured labels |

Do not use the same layout for every guide. Use the same visual language, but choose the page pattern based on task shape.

## Page Anatomy

Use this order on normal floor guides:

1. Header.
2. Compact context strip.
3. Main step area.
4. Help layer, only for non-linear support.
5. `Klaar als`.
6. Related guide chips.
7. Footer/source/version and digital guide QR.

### Header

Keep:

- guide code;
- title;
- one-line purpose;
- draft/version cue.

Rules:

- code and title must be readable in the first glance;
- color is supporting identity only;
- do not make header height compete with the steps.

### Context Strip

Keep it compact. It answers: who is this for and what must they have?

Suggested fields:

- `Rol`
- `Nodig`
- optional `Voorbeeld` or `Adres` only when it helps the task

Prerequisites covered by another guide must reference that guide directly in the context strip using that guide's color, icon, and name. For example, do not write only `Ingelogd`; write `Ingelogd (AC-01 Login)` and make that text use the same color and icon language as the `AC-01 Login` reference.

For mobile scanning guides, make the phone requirement explicit in `Nodig`, such as `Telefoon met camera + apparaat met QR/asset tag`. Do not rely on screenshots alone to imply that a phone is required.

Do not put step-specific stop warnings here. Use a top stop only for a global precondition, such as "do not continue without an account".

### Main Step Area

This is the page's dominant area.

Rules:

- actual workflow steps use large numbers: `1`, `2`, `3`, `4`;
- alternatives inside a step use sublabels: `1A`, `1B`, `3A`, `3B`;
- screenshots or photos inside a step use those same sublabels;
- captions are mandatory when there is more than one visual in a step;
- step text must lead the screenshot, not trail behind it;
- image identifiers should be circular corner badges with a clear stroke and transparent but readable fill, centered on or near the image corner so they partly overlap the image without covering important app content;
- red rectangles or circles are optional callouts, not decoration. Do not use them by default on screenshots; reserve them for final manual annotation or cases where the tap target would otherwise be ambiguous.

### Help Layer

Use for non-linear support:

- no account;
- forgotten password;
- no phone/device;
- camera blocked;
- QR damaged;
- no result;
- printer/download fallback;
- permission denied.

Rules:

- help tiles must not look like required steps;
- help tiles must not contain step-specific stop rules;
- help tiles should be short enough to read as rescue options.

### `Klaar Als`

The finish box describes the visible end state.

Good:

- `De juiste assetpagina is open en komt overeen met het apparaat in je hand.`
- `Dashboard of Scan QR is zichtbaar.`
- `Alle verplichte workflow-items hebben een opgeslagen resultaat.`

Bad:

- `Alles is goed.`
- `Klaar.`
- `De taak is uitgevoerd.`

## Numbering System

This is the most important new rule after the latest AST-01 feedback.

### Big Numbers

Big green/blue/orange numbered circles are only for actual workflow steps.

Use:

- `1 Open de scanner`
- `2 Richt op QR`
- `3 Zoek handmatig`
- `4 Controleer asset`

Do not use a big step number for an alternative button that is not required.

### Subnumbered Visuals

Use small screenshot badges for visuals:

- `1A`
- `1B`
- `2A`
- `3A`
- `3B`

The screenshot badge should visually connect to its parent step. If the parent step is `1`, every visual option in that step must start with `1`.

### Alternatives

Use `OF` between alternatives.

When the step title already says to choose one of two ways, a standalone `OF` marker is optional. It may be clearer to put `Of ...` in the second visual caption instead of spending layout space on a separate `OF` element.

Good:

```text
1 Open de scanner
Kies een van deze twee manieren.

1A Dashboard
Tik op Scan QR.

OF

1B Bovenbalk
Tik op het camera-icoon.
```

Bad:

```text
1 Scan QR kaart
2 Camera icoon
```

That reads as "do both", which is wrong.

### Arrows

Use arrows only for sequence.

Use:

- arrow from login form to dashboard;
- arrow from filled form to `Inloggen`;
- arrow from search input to search result if both are part of the same fallback sequence.

Do not use arrows between alternatives. Use `OF`.

### Variants

Use letters for equivalent choices:

- `1A Dashboard`
- `1B Bovenbalk`

Use decimals only for true substeps:

- `3.1 Typ asset tag`
- `3.2 Kies het juiste resultaat`

For laminated floor guides, prefer `3A` / `3B` visual labels over decimal numbering unless the substeps are text-heavy.

## Screenshot And Photo Rules

Every visual must have a job before it is placed.

Allowed visual jobs:

| Job | Answers | Example |
| --- | --- | --- |
| Primary path | Where do I press or scan first? | dashboard `Scan QR` card |
| Alternative path | What do I do if the main path does not work? | search bar fallback |
| Physical context | Where is the thing on the device? | QR sticker location photo |
| Verification | How do I know this is correct? | asset title/tag/model crop |
| Stop support | What screen/device evidence triggers stop? | mismatch check area |
| End state | What should I see when done? | dashboard visible, asset detail open |

Do not place a visual if it only proves the app exists.

### Screenshot Captions

Screenshots must be numbered and must have tiny description text.

Caption format:

```text
1A Dashboard
Tik op Scan QR.
```

or, when space is tight:

```text
1A Dashboard - Tik op Scan QR.
```

Rules:

- 1-2 lines maximum;
- 2-7 words for the label;
- 4-10 words for the description;
- use a verb when the user must act;
- use a noun when the visual is only recognition or context.

Good labels:

- `1A Dashboard`
- `1B Bovenbalk`
- `2A QR-sticker`
- `3A Zoekbalk`
- `3B Resultaat`
- `4A Assettitel`

Good descriptions:

- `Tik op Scan QR.`
- `Tik op het camera-icoon.`
- `Houd de sticker in beeld.`
- `Typ asset tag of QR-code.`
- `Kies het juiste resultaat.`
- `Vergelijk met het apparaat.`

Bad captions:

- `Screenshot`
- `Voorbeeld`
- `Zie hier`
- long paragraphs under the image

### Crop Rules

Use tighter crops than full phone screenshots, but do not make controls unreadable.

Working size targets:

- minimum useful action crop width: about `38 mm`;
- normal action crop width: `45-58 mm`;
- large primary crop: `58-85 mm`, depending on page pattern;
- physical QR/location photos may be taller if device context matters;
- captions should be `8-9 pt`;
- critical body text should stay around `12 pt`.

Use a wider context crop only when the user would otherwise not know where the control is on the page.

### Highlight Rules

The user may manually add red guidelines around images. Keep the system consistent:

- use one red target outline per screenshot/photo when needed;
- target the button, QR sticker, input field, title, or mismatch evidence;
- do not add multiple arrows and boxes to the same image;
- use red only for visual targeting, not for normal family identity;
- if a red outline indicates a stop/risk area, pair it with an inline `STOP` block.

### Physical Photos

Physical photos are not optional decoration when the task depends on the physical device.

Use photos for:

- QR sticker location;
- ports;
- component positions;
- tray/storage labels if needed;
- printed label examples.

Photo requirements:

- include enough surrounding context for a human to recognize the device area;
- crop tighter only after the location is understandable;
- do not use generated or fake device drawings in a visual-quality proof;
- placeholders are acceptable only in wireframes and must be clearly labelled.

## Step Block Patterns

### Pattern A: Default Step

Use when a step has one action and one visual.

```text
[big 2] Richt op QR
Houd de sticker in beeld tot de asset opent.

[2A visual]
2A QR-sticker
Houd sticker in beeld.
```

### Pattern B: Choice Step

Use when two controls are equivalent ways to do the same thing.

```text
[big 1] Open de scanner
Kies een van deze twee manieren.

[1A visual]       OF       [1B visual]
1A Dashboard               1B Bovenbalk
Tik op Scan QR.            Tik camera-icoon.
```

Rules:

- the parent block has one big step number;
- every option gets a small `1A` / `1B` badge;
- use `OF`, not an arrow;
- make the two alternatives visually equal if they are equally valid.

### Pattern C: Main Path Plus Fallback

Use when one path is preferred and the other is recovery.

```text
[big 2] Scan QR
Houd de QR-sticker in beeld.

[larger primary visual]

[small fallback inset]
Als scannen niet lukt: zoek handmatig.
```

Rules:

- primary path gets more visual weight;
- fallback gets smaller visual weight;
- label fallback with `Als ...` or `Geen scan?`;
- do not imply the fallback is required when the primary path works.

### Pattern D: Multi-Visual Fallback

Use when the fallback itself has two small actions.

```text
[big 3] Zoek handmatig
Als scannen niet lukt: typ asset tag of QR-code.

3A Zoekbalk
Typ asset tag of QR-code.

3B Resultaat
Kies het juiste resultaat.
```

This is useful for `AST-01` if the search result row must be shown.

### Pattern E: Verification With Attached Stop

Use at the first risky check.

```text
[big 4] Controleer asset
Vergelijk titel, tag/model en apparaat.

[4A visual]
4A Assettitel
Vergelijk met sticker/device.

[attached STOP]
STOP: Klopt tag/model/apparaat niet?
Vraag hulp voor je wijzigt.
```

Rules:

- stop block must be inside or visibly attached to the verification card;
- do not place this stop in the help rail or lower help tile strip;
- keep the stop block compact. It should interrupt attention without taking more space than the step text or screenshot evidence it protects;
- if the stop condition is the central purpose of the step, prefer inline red text in the step description over a separate red block;
- use red/pink warning styling only for true stop conditions.

## Callout Types

Use distinct callout roles.

| Type | Meaning | Placement | Color |
| --- | --- | --- | --- |
| `STOP` | Do not continue until someone helps | inside/attached to relevant step | red/pink |
| `Vraag hulp` | User can continue only after help | relevant step or help tile | orange/red |
| `Let op` | Common mistake, not always blocking | near relevant step | amber |
| `Hulp` | Non-linear fallback | help tile/rail | orange/neutral |
| `Klaar als` | End state | bottom | green |

Do not mix these roles. A `STOP` tile in the generic help row will be missed by someone following the steps.

## Help Tiles

The v5 `AST-01` help row is a better direction than the v4 large bottom help block.

Use compact tiles:

```text
Camera
Gebruik zoeken.

QR beschadigd
Typ asset tag.

Geen resultaat
Controleer code. Vraag hulp.

Geen telefoon
Gebruik laptop of zoekbalk.
```

Rules:

- tiles should be visually consistent across documents;
- use 3-4 tiles on a full single-task page;
- use 1-2 tiles on compact guides;
- do not include long procedure text;
- do not include step-specific stop warnings;
- move broad content to `HELP-01` when the page is dense.

## Footer And Related Guides

Keep the footer pattern:

- `Klaar als` box above the footer;
- related guide chips bottom-left;
- source/version/date line;
- bottom-right digital guide QR.

QR size:

- preferred final print size: `22-25 mm`;
- minimum final print size: `18 mm`;
- label: `Digitale gids` or `Laatste versie`;
- QR must be a helper, not required to complete the task.

Related guide chips:

- include code and label;
- keep chips readable in black-and-white print;
- do not list every related guide, only guides the user may actually need next.

## Typography And Spacing

Use these as starting points for Affinity styles.

| Element | Target |
| --- | --- |
| Guide title | `18-22 pt`, bold |
| Purpose subtitle | `10-12 pt` |
| Context labels | `8-9 pt`, bold/muted |
| Context values | `10-12 pt`, bold |
| Step title | `13-15 pt`, bold |
| Step body | `11.5-12 pt` |
| Screenshot caption label | `8-9 pt`, bold |
| Screenshot caption body | `8-9 pt` |
| Help tile title | `9-10 pt`, bold |
| Help tile body | `8.5-9.5 pt` |
| Footer/source | `7.5-8.5 pt` |

Rules:

- critical body text should not drop below `11.5 pt`;
- captions may be smaller, but they must not carry the whole instruction;
- avoid long lines in compact cards;
- use frame insets instead of fake padding;
- use consistent card radius, around `2-4 mm`, not large decorative rounding.

## Color And Visual Identity

Family color remains useful, but code and text carry meaning.

Draft family roles:

| Family | Role | Suggested Color Use |
| --- | --- | --- |
| `AC` | access/login | blue |
| `SC` | scan/search | teal |
| `AST` | asset | green |
| `CMP` | component | amber |
| `WF` | workflow/test | orange |
| `HELP` | problems/help | red/pink |
| `CFG` | admin/config | purple, later |

Rules:

- do not rely on color alone;
- use near-black for core text;
- use muted gray only for metadata;
- use red/pink only for `STOP` or true risk;
- keep screenshot frames neutral unless the frame itself is the warning target.

## Affinity Build Rules

Continue using the clean-build rules.

Before layout:

1. Pick guide type.
2. Write the step skeleton.
3. Decide visual jobs.
4. Prepare screenshot/photo crops.
5. Decide if alternatives use `OF`, sublabels, or fallback treatment.

In Affinity:

- start from a clean A4 file or clean template, not a failed proof;
- use named text styles;
- use grouped step/card components;
- use picture frames or intentionally cropped PNGs;
- name layers/frames with guide code and visual label, for example `AST-01 1A Dashboard`;
- save a new checkpoint for each substantial pass;
- export PNG/PDF proof after each pass.

After layout:

- inspect the proof at full page;
- print test before treating a guide as a pattern;
- check black-and-white legibility;
- check that alternatives are not read as sequence;
- check that stop warnings are attached to the relevant step.

## Screenshot Preparation Workflow

Each visual should have a row in a visual-purpose plan before placement.

Suggested fields:

| Field | Example |
| --- | --- |
| Guide | `AST-01` |
| Visual label | `1A` |
| Visual job | Primary path |
| User question | Where do I tap to scan? |
| Source | dashboard phone screenshot |
| Crop target | `Scan QR` card |
| Caption | `1A Dashboard - Tik op Scan QR.` |
| Priority | primary / secondary |
| Status | final / recrop / missing |

File naming:

```text
AST-01-1A-dashboard-scan-qr.png
AST-01-1B-topbar-camera-icon.png
AST-01-2A-camera-qr-sticker.png
AST-01-3A-search-bar-asset-tag.png
AST-01-3B-search-result-asset.png
AST-01-4A-asset-title-tag-model.png
```

## Guide-Specific Direction

### AC-01 Login

Purpose: show how to reach the dashboard/Scan QR entry.

Recommended structure:

1. `Open dev.inbit`
2. `Vul login in`
3. `Controleer dashboard`

Visuals:

- optional phone/browser start or quicklink visual if captured;
- login form;
- filled login/login button;
- dashboard with Scan QR visible.

Notes:

- AC-01 may be compact, but only if the login button and dashboard endpoint remain readable;
- preserve help for no account, forgotten password, and no phone/device;
- do not spend a full page if a compact block prints well, but readability wins over density.

### SC-01 Scan Asset

Purpose: teach the scan page itself.

Recommended structure:

1. `Open scanner`
2. `Sta camera toe`
3. `Richt op QR`
4. `Controleer geopende asset`
5. optional `Zoek handmatig`

Visuals needed:

- dashboard or camera icon entry;
- browser/camera permission or allowed camera state;
- live camera/QR state;
- opened asset result;
- manual search fallback.

Notes:

- this guide owns camera permission and scanner behavior more than AST-01 does;
- if AST-01 becomes too dense, delegate scan detail to SC-01 and keep only an entry/path reference.

### AST-01 Asset Openen

Purpose: open the correct existing asset and verify it before changing anything.

Recommended v6 structure:

```text
1 Open de scanner
Kies een van deze twee manieren.

1A Dashboard
Tik op Scan QR.

OF

1B Bovenbalk
Tik op het camera-icoon.

2 Richt op QR
Houd de QR-sticker in beeld tot de asset opent.

3 Zoek handmatig
Als scannen niet lukt: typ asset tag of QR-code.

4 Controleer asset
Vergelijk titel, tag/model en apparaat.

STOP: Klopt tag/model/apparaat niet?
Vraag hulp voor je wijzigt.
```

Potential fallback expansion:

- `3A Zoekbalk - Typ asset tag of QR-code.`
- `3B Resultaat - Kies het juiste resultaat.`

Do not print serial-number search as current behavior until the product supports it.

### AST-02 Existing Asset Refurbishment

Purpose: high-level route through the whole refurb flow.

Direction:

- this should not duplicate AC-01, SC-01, AST-01, WF-01, WF-02, and CMP-04 in detail;
- use reference chips and one screenshot/visual landmark per main band;
- likely double-sided when workflow item detail is included.

### WF-01 Start Workflow

Purpose: get from asset page to the correct workflow/profile.

Visuals:

- Tests/Workflows tab;
- `Nieuwe workflow starten` action;
- workflow/profile selection;
- active/current workflow visible.

Warning:

- attach warning near the start/select step if starting the wrong workflow has consequences.

### WF-02 Complete Workflow

Purpose: complete workflow item results with notes/photos.

Visuals:

- active workflow item card;
- pass/fail or done/not-done controls;
- note control;
- photo control;
- saved/completed state.

Warning:

- attach `Klik niet zomaar alles op Geslaagd` near the result-button step.

### CMP-04 Remove Component To Tray

Purpose: move a component to tray/storage and handle serial safely.

Visuals:

- asset Components tab;
- `Naar tray` action;
- confirmation modal with locked serial;
- unlocked serial edit state, captured without submitting;
- moved component/detail end state if useful.

Warning:

- attach stop warning near component identity/serial confirmation, not at the bottom.

### HELP-01 Common Problems

Purpose: collect non-linear support so other pages stay readable.

Use tiles or rows:

- cannot log in;
- camera does not open;
- QR opens wrong asset;
- no workflow appears;
- printer issue;
- permission denied;
- wrong model/device.

STOP conditions in HELP-01 can be central content because HELP-01 is the troubleshooting guide. On task guides, keep STOP attached to the relevant step.

## Current Pitfalls To Avoid

1. Treating alternatives as separate numbered steps.
2. Using arrows between alternatives.
3. Moving stop warnings into generic help tiles.
4. Keeping screenshots too large and forcing text to the margins.
5. Overcorrecting into tiny screenshots that no one can read.
6. Using full phone screenshots where a crop would answer the user's question.
7. Letting the help rail compete with the main steps.
8. Teaching future product behavior, such as serial search, before it exists.
9. Reusing failed Affinity files as bases.
10. Making every guide follow AST-01's page shape.

## Acceptance Checklist For A New Guide Proof

A proof is acceptable enough to continue when:

- the guide type is clear;
- actual steps have big numbers;
- alternatives use sublabels and `OF`;
- every screenshot/photo is numbered;
- every screenshot/photo has a tiny description;
- every visual has a known job;
- step-specific STOP is inside or attached to the relevant step;
- help tiles contain only non-linear help;
- `Klaar als` describes a visible end state;
- related guides and digital QR exist but do not dominate;
- body text and key screenshot controls are readable at A4;
- the page still works in black-and-white;
- missing evidence is labelled as missing, not faked.

## Recommended Next Implementation Path

1. Update `AST-01` to v6 using the `1A` / `1B` choice-step system.
2. Add numbered screenshot badges and tiny captions to all AST-01 visuals.
3. Keep the stop block attached inside step 4.
4. Convert the lower help row into a reusable help-tile style.
5. Export and inspect v6 before changing other guides.
6. Apply the same grammar to `AC-01`:
   - step screenshots get numbered labels;
   - captions become consistent;
   - help remains compact.
7. Build `SC-01` next, because it owns scanner/camera permission detail that AST-01 should not over-explain.
8. Only after AST-01, AC-01, and SC-01 are stable, build `WF-01`, `WF-02`, and `CMP-04`.
