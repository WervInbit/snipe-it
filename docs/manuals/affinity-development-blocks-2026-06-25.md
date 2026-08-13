# Affinity Development Blocks - 2026-06-25

Current status (2026-07-21): deferred Affinity reference. Do not use Computer Use or build Affinity files until every active generated guide is confirmed or the user gives an explicit green light. Current guide production starts at `docs/manuals/operator-guides/README.md`.

Status: draft development spec for building the laminated operator guides in Affinity. This file is not final guide copy.

Purpose: split the large guide plan into small, repeatable Affinity blocks so Computer Use can build one page or one side at a time without needing to hold the whole manual system in context.

July 2 foundation: use `docs/manuals/operator-guide-design-foundation-2026-07-02.md` as the current cross-guide design grammar. It defines the reusable rules for step numbering, alternative labels such as `1A`/`1B`, screenshot captions, help tiles, warning placement, and proof acceptance.

June 30 feedback revision: use `docs/manuals/operator-guide-feedback-replan-2026-06-30.md` for the next draft direction. The June 25 generated PDFs are proof artifacts only. Next guide drafts should be step-first, use smaller screenshot crops per step, keep help information from older designs, move most stop warnings inline to the relevant step, and try `AC-01 Login` as a compact block if readability holds. Step-specific stop warnings must be inside or visibly attached to the step block they reference, not placed in a detached help rail or bottom strip.

Clean-build correction: use `docs/manuals/operator-guide-clean-design-plan-2026-06-30.md` before creating another Affinity file. Do not continue from the failed native `operator-guides-ac01-ast01-native-affinity-v2-feedback-pass.af`; start from a clean A4 document, prepare screenshot crops first, and export/inspect a proof after each small pass.

## Source Inputs

- Main guide plan: `docs/manuals/operator-guide-planning.md`
- Affinity research report: `C:\Users\Gebruiker\Downloads\affinity research deep-research-report.md`
- Rough Affinity sketches: `C:\Users\Gebruiker\Downloads\refurbisher steps.pdf` and `C:\Users\Gebruiker\Downloads\refurbisher steps v2.pdf`
- Current proof artifact: `C:\Users\Gebruiker\Documents\snipe-it manuals\AST-02 Affinity proof template.af`
- Screenshot source folder: `C:\Users\Gebruiker\Documents\snipe-it manuals\screenshot-source\2026-06-25-blocks`
- Dev app used for screenshots: `https://dev.inbit/`

## Global Page Rules

- Page: A4 portrait, 210 mm x 297 mm.
- Margins: 10 mm on all sides.
- Live area: 190 mm x 277 mm.
- Default two-column working grid: 118 mm text column, 8 mm gutter, 64 mm image rail.
- Minimum printed body text: 12 pt.
- Step headings: 13-15 pt, bold.
- Guide title: 18-22 pt, bold, depending on title length.
- Footer text: 7.5-8.5 pt, only for version/date/source labels.
- Keep first floor/refurbisher guide pass in Dutch.
- Always keep the guide code visible. Color and icons help, but the code is the stable reference.
- For floor guides, create a visual-purpose plan before layout. Use visuals for primary path, alternative path, physical context, verification, and stop support; do not require a fixed screenshot count.
- Put screenshots next to or directly below the step they explain. Avoid screenshot galleries before the steps unless the guide is specifically a sequence overview.
- Screenshot crop size target: smaller than the current `AC-01` and `AST-01` proof screenshots, but larger than the smallest screenshot in the initial example photo. Printed readability wins over density.
- Every guide side must include: guide code, title, role/needed context, finished-when rule, related guide references, and version/date area.
- Place stop-and-ask rules inline at the first relevant step unless the rule is a global precondition.
- Keep the bottom-right latest-guide QR large enough to scan after lamination; target 22-25 mm, minimum 18 mm.

## Affinity Style Tokens

These should become named styles/swatches/assets before page building begins.

| Token | Value / Use |
| --- | --- |
| `Page/Background` | White page, no decorative background. |
| `Ink/Primary` | Near black for body text. |
| `Ink/Muted` | Gray for labels, captions, and metadata. |
| `Line/Subtle` | Light gray strokes around screenshot frames and dividers. |
| `AC/Blue` | Access and login blocks. |
| `SC/Teal` | Scan and search blocks. |
| `AST/Green` | Asset blocks. |
| `CMP/Amber` | Component blocks. |
| `WF/Orange` | Workflow blocks. |
| `HELP/Red` | Help and escalation blocks. |
| `Text/GuideTitle` | 18-22 pt bold. |
| `Text/SectionLabel` | 9-10 pt uppercase or small bold label. |
| `Text/StepTitle` | 13-15 pt bold. |
| `Text/Body` | 12 pt regular. |
| `Text/Caption` | 8-9 pt regular. |
| `Text/Footer` | 7.5-8.5 pt regular. |

## Reusable Block Catalogue

### B00 Page Shell

Use on every page or side.

- A4 portrait with 10 mm margins.
- Header strip: `x=10`, `y=10`, `w=190`, `h=16`.
- Content start: `y=30`.
- Footer strip: `x=10`, `y=282`, `w=190`, `h=5`.
- Footer contents: guide code, draft version, date, optional latest-guide QR.

Affinity build:

- Create as a master page.
- Keep footer placeholders unlocked enough that each guide can update code/version/date.
- Do not put screenshots on the master.

### B01 Guide Header

Use once per guide.

- Left family stripe: 4 mm wide.
- Icon box: 10 mm square.
- Code: bold, visible, for example `AC-01`.
- Title: concise task name.
- Optional language status: `Draft NL` only if needed during development, not final print.

Affinity build:

- Group stripe, icon, code, and title.
- Convert to an asset named `B01 Guide Header`.
- Color is driven by guide family.

### B02 Role / Needed / Stop Strip

Use near the top of every guide side.

June 30 revision: this block is too large for most next drafts. Prefer `B15 Compact Context Strip` plus `B16 Inline Stop` unless a global stop rule applies before the first action.

- Three compact panels in one horizontal strip.
- Suggested size: `x=10`, `y=30`, `w=190`, `h=24`.
- Columns: Role 45 mm, Needed 72 mm, Stop and ask 73 mm.
- Stop-and-ask panel gets a stronger warning accent.

Content rules:

- Role should be one role or a small set, not a paragraph.
- Needed should list physical items and account/setup requirements.
- Stop-and-ask should be one concrete escalation trigger.

### B03 Main Step Band

Use for process cards such as `AST-02`.

- One step per horizontal band.
- Suggested size per band: `x=10`, `w=190`, `h=34-39`.
- Text region: 118 mm.
- Image crop region: 64 mm.
- Step number: 9-11 mm circle or square marker.

Content rules:

- One operator action per band.
- Body text should be 1-2 short lines.
- If a screenshot is too small to read in the rail, crop harder instead of shrinking the whole screenshot.

### B04 Three Screenshot Sequence

Use for login or other expected-state sequences.

- Three side-by-side phone panels.
- Suggested size: `x=10`, `y=56`, `w=190`, `h=74`.
- Panel width: 58 mm.
- Gaps: 8 mm.
- Each panel has a 1-line caption and optional arrow between panels.

Best use:

- `AC-01 Login`: login form -> filled credentials -> dashboard visible.
- If a real phone/home-screen step is captured later, replace the filled-credentials panel or make it the first panel.

### B05 Right Image Rail

Use when the main text must remain readable and screenshots are supporting evidence.

- Rail position: `x=136`, `w=64`.
- Preferred crop sizes from research:
  - Small rail crop: 56 mm x 34 mm.
  - Main crop: 58 mm x 72 mm.
  - Action crop: 58 mm x 28 mm.
- Keep critical UI text inside at least 38-58 mm physical width.

Affinity build:

- Use picture frames, not free-floating images.
- Name frames by slot, for example `WF-01 S1 Tests tab`.
- Use captions only when the screenshot is not self-evident.

### B06 Detail Step List

Use on back sides or dense workflow detail pages.

- Left column: numbered list, true Affinity list style.
- Right rail: screenshots or callouts.
- Max 7 steps per side.
- Use hanging indents so wrapped lines align cleanly.

Content rules:

- Each step begins with a verb.
- Avoid teaching system concepts inside the list. Put concept warnings in B07.

### B07 Callout Note

Use for warnings, common mistakes, or non-obvious UI state.

- Keep to 1-3 lines.
- Use family color at low tint plus a stronger left border.
- Do not use more than 2 warning callouts on one side unless the guide is `HELP-01`.

Examples:

- `Stop: asset tag does not match the device in your hand.`
- `Ask: no workflow appears for this model.`
- `Note: camera permission must be allowed before scanning works.`

### B08 Finished When Box

Use near the bottom of every guide side.

- Strong completion state, usually green or neutral with a check icon.
- Suggested size: `x=10`, `y=246`, `w=118`, `h=24` for two-column layouts, or `w=190` for single-column sequence pages.

Content rules:

- Describe the visible end state.
- Avoid vague phrasing like "done correctly".

### B09 Related Guides Strip

Use in footer area or just above footer.

- 2-5 guide chips.
- Each chip shows icon, code, and short label.
- Suggested chip height: 7-8 mm.

Examples:

- `[key] AC-01 Login`
- `[scan] SC-01 Scan Asset`
- `[checklist] WF-01 Start Workflow`

### B10 Latest Guide QR

Use on final printable pages, optional during draft proofs.

- Minimum QR size for print testing: 16 mm x 16 mm.
- Preferred: 18-22 mm if the destination URL is long.
- Label must say whether it opens the exact guide or the guide index.

Open decision:

- Final URL target is not locked yet. Use placeholder `latest guide/index QR` until the index exists.

### B11 Screenshot Frame

Use around every placed screenshot.

- Stroke: subtle gray, 0.5-0.75 pt.
- Corner radius: 1.5-2 mm maximum.
- Caption: 8-9 pt, directly below or above frame.
- Optional highlight: single rectangle or arrow. Do not stack many annotations.

### B12 Inline Reference Chip

Use inside body copy when one guide depends on another.

- Shows icon, code, and label.
- Height: 5-6 mm inline or 7-8 mm standalone.
- Keep code readable in black-and-white print.

### B13 Missing Screenshot Placeholder

Use only during development.

- Light gray screenshot frame with label: `SCREENSHOT NEEDED`.
- Include exact capture request in the caption.
- Remove before final print.

### B14 Step Screenshot Row

Use as the default floor-guide step block when the step needs a visual.

- One numbered action.
- One short action sentence.
- One focused screenshot/photo crop showing where to press, type, scan, physically look, or verify.
- Optional inline stop/help note attached to that step.
- Step height target: 32-42 mm depending on screenshot crop.

Content rules:

- The text must lead the screenshot, not the other way around.
- Crop screenshots around the control or visual landmark the user needs.
- Do not use this block for broad concept explanations.

If a step does not need visual evidence, use the same text rhythm without forcing an empty screenshot frame.

### B15 Compact Context Strip

Replacement for the oversized role/needed/stop strip in most pages.

- Contains only `Rol` and `Nodig` by default.
- Height target: 10-14 mm.
- Stop rules are moved to `B16 Inline Stop` unless global.

### B16 Inline Stop

Use at the first step where an escalation rule matters.

- Short warning attached to one step row.
- Must be inside the relevant step block or visually locked to that block.
- Do not place step-specific stop warnings in `B17 Help Rail`, a lower help tile strip, or a separate footer band.
- Example for `AST-01`: `Stop: asset tag, model, or physical device does not match.`
- Do not repeat the same warning at the top and bottom unless there is a safety reason.

### B17 Help Rail

Preserves additional information from older guide versions.

- Use for non-linear help: no account, forgotten password, no phone/device, camera permission, damaged QR, printer fallback.
- Keep it visually secondary to the numbered steps.
- Do not use this block for stop rules. If a warning tells the user to stop before acting, use `B16 Inline Stop` inside or attached to the relevant step.
- If the page is already dense, move the help rail content into `HELP-01` and leave one related-guide chip.

### B18 Compact Access Block

Use for `AC-01 Login`.

- Half-page or one-third-page target if readable; this is a preference, not a hard size requirement.
- Uses 2-3 small screenshots: login form, login button/filled state, dashboard or scan entry visible.
- Includes account/password/no-device help.
- May share a page with `SC-01` if print testing stays readable.

### B19 Larger Footer QR

Use on every final printable page.

- Preferred QR size: 22-25 mm.
- Minimum QR size: 18 mm.
- Label should say `Laatste versie` or `Digitale gids`.
- Keep enough quiet space around the QR for laminated scanning.

### B20 Visual Purpose Plan

Use before opening Affinity for each guide.

Purpose:

- Decide which visuals are needed and why.
- Prevent duplicate screenshots.
- Mark whether a visual is an app screenshot, browser/device context shot, physical device photo, or placeholder.
- Decide visual priority before choosing frame size.

Suggested fields:

| Field | Examples |
| --- | --- |
| Visual job | Primary path, alternative path, physical context, verification, stop support. |
| User question answered | Where do I tap? What can I type? Is this the right device? |
| Source | `dev.inbit` screenshot, real phone photo, staged device photo, placeholder. |
| Priority size | Large, medium, small. |
| Guide placement | Main step row, right rail, help rail, footer, back side. |

## Page Development Blocks

### AC-01 Login

Goal: show a phone user what to expect from login until the dashboard is visible.

June 30 revision: do not default this to a full A4 page. Try it as `B18 Compact Access Block`, but use more space if print testing shows the screenshots or help text are cramped.

Recommended Affinity blocks:

- `B00 Page Shell`
- `B01 Guide Header`
- `B02 Role / Needed / Stop Strip`
- `B04 Three Screenshot Sequence`
- `B07 Callout Note`
- `B08 Finished When Box`
- `B09 Related Guides Strip`
- `B10 Latest Guide QR`

Screenshot slots:

| Slot | Use | Current source |
| --- | --- | --- |
| `AC-01 S1` | Empty login form | `AC-01-01-login-form-mobile.png` |
| `AC-01 S2` | Credentials filled, before tapping login | `AC-01-02-login-filled-mobile.png` |
| `AC-01 S3` | Dashboard visible after login | `AC-01-03-dashboard-mobile.png` |
| Optional replacement | Phone/browser start state before the login form | Missing. Needs real device or staged phone screenshot. |

Draft step copy, to translate/refine in Affinity:

1. Open de Snipe-IT loginpagina.
2. Vul je gebruikersnaam en wachtwoord in.
3. Tik op `Inloggen`.
4. Controleer dat het dashboard of de scan knop zichtbaar is.

Finished when:

- Dashboard is visible and the user can start `SC-01 Scan Asset`.

Development note:

- Do not combine with `SC-01` until the three screenshots are proven readable on printed A4.
- Preserve help information for no account, forgotten password, and no phone/device.

### SC-01 Scan Asset

Goal: scan a QR label and open the correct asset, with a manual search fallback.

Recommended Affinity blocks:

- `B00`, `B01`, `B02`, `B03` or `B04`, `B07`, `B08`, `B09`.

Screenshot slots:

| Slot | Use | Current source |
| --- | --- | --- |
| `SC-01 S1` | Scanner page opened | `SC-01-01-scan-page-mobile.png` |
| `SC-01 S2` | Live camera permission granted | Missing. Current capture shows the no-camera/permission state. |
| `SC-01 S3` | Scanned result opens asset | Missing. Needs a safe scan test or staged QR. |
| `SC-01 S4` | Manual asset tag search fallback | Can be cropped from dashboard/search bar if needed. |

Finished when:

- The correct asset detail page is open and the asset tag matches the physical device.

Stop and ask if:

- The QR opens a different asset, the screen says permission denied, or no matching asset is found.

### AST-01 Open Existing Asset

Goal: find an existing asset and confirm it is the correct physical device before changing anything.

June 30 revision: use `B14 Step Screenshot Row` as the main layout. The mismatch stop rule belongs inside or visibly attached to the title/model/device check step, not at the bottom.

Recommended Affinity blocks:

- `B00`, `B01`, `B02`, `B03`, `B05`, `B08`, `B09`.

Screenshot slots:

| Slot | Use | Current source |
| --- | --- | --- |
| `AST-01 S1` | Scan/search entry point: camera button and/or search field | Needs tighter capture/crop. |
| `AST-01 S2` | What to type or scan: asset tag / QR code payload / future serial number | Serial search is future product work; do not print as current behavior yet. |
| `AST-01 S3` | Hardware index/search result row/card | `AST-01-01-hardware-index-mobile.png` |
| `AST-01 S4` | Asset detail top, asset tag and title | `AST-01-02-asset-detail-top-mobile.png` |
| `AST-01 S5` | Physical QR sticker location | Missing; needs real or staged device photo. |

Finished when:

- The asset page title and asset tag match the sticker/device in hand.

### AST-02 Existing Asset Refurbishment

Goal: the main high-level refurbishing card. This should reference smaller guides instead of duplicating all details.

Recommended Affinity structure:

- Double-sided A4.
- Front: 5 large `B03 Main Step Band` blocks.
- Back: `B06 Detail Step List` for workflow-result details, with `B05 Right Image Rail`.

Front-side block plan:

| Band | Action | Reference chips | Screenshot strategy |
| --- | --- | --- | --- |
| 1 | Login and open scanner | `AC-01`, `SC-01` | Small dashboard/scan crop only. |
| 2 | Open the correct asset | `AST-01` | Asset title/asset tag crop. |
| 3 | Check status and warnings | `AST-01`, `HELP-01` | Workflow warning/status crop. |
| 4 | Start or continue workflow | `WF-01`, `WF-02` | Tests/workflows tab crop. |
| 5 | Handle parts if needed | `CMP-04`, later `CMP-01/02/03` | Component tab or tray action crop. |

Back-side block plan:

- Detail how to fill a workflow result.
- Show pass/fail or done/not-done result cards.
- Show where notes/photos belong.
- Show final completion state.

Current screenshot sources:

- Dashboard: `AST-02-01-dashboard-mobile.png`
- Asset detail/status: `AST-01-02-asset-detail-top-mobile.png`
- Correct asset tests tab: `WF-01-01-asset-tests-tab-mobile.png`
- Correct asset components tab: `CMP-04-01-asset-components-tab-mobile.png`

Do not use:

- `AST-02-02-asset-workflows-tab-mobile.png` for the asset workflow tab. It captured the admin Workflow Profiles page.
- `CMP-01-asset-components-tab-mobile.png` for asset component handling. It captured the global Components list, not the asset tab.

Finished when:

- Required workflows are complete, blocking warnings are resolved or escalated, and any removed components were handled through the right component guide.

### WF-01 Start Workflow

Goal: select/start the correct workflow from the asset Tests / Workflows tab.

Recommended Affinity blocks:

- `B00`, `B01`, `B02`, `B03`, `B05`, `B07`, `B08`, `B09`.

Screenshot slots:

| Slot | Use | Current source |
| --- | --- | --- |
| `WF-01 S1` | Asset Tests / Workflows tab | `WF-01-01-asset-tests-tab-mobile.png` |
| `WF-01 S2` | Floating `Nieuwe workflow starten` button | Crop from `WF-01-01-asset-tests-tab-mobile.png`. |
| `WF-01 S3` | Active workflow/test cards with result buttons | `WF-01-02-start-workflow-form-mobile.png`; use only if the test-run state is acceptable for the draft. |
| `WF-01 S4` | Note/photo evidence controls | Crop from `WF-01-02-start-workflow-form-mobile.png`. |

Finished when:

- The chosen workflow is open or visible as the current workflow to complete.

### WF-02 Complete Workflow

Goal: record workflow item results with notes/photos where needed.

Recommended Affinity blocks:

- `B00`, `B01`, `B02`, `B06`, `B05`, `B07`, `B08`, `B09`.

Screenshot slots:

| Slot | Use | Current source |
| --- | --- | --- |
| `WF-02 S1` | Active workflow item/result card | Missing. |
| `WF-02 S2` | Pass/fail or done/not-done controls | Missing. |
| `WF-02 S3` | Note/photo evidence control | Missing. |
| `WF-02 S4` | Completed workflow summary | Missing. |

Capture rule:

- Use a disposable dev asset or seeded example before capturing, because completing workflow items can change test-run data.

Finished when:

- All required workflow items show a saved result and the workflow no longer blocks the asset.

### CMP-04 Remove Component To Tray

Goal: move an installed/expected component to tray while capturing serial information safely.

Recommended Affinity blocks:

- `B00`, `B01`, `B02`, `B03`, `B05`, `B07`, `B08`, `B09`.

Screenshot slots:

| Slot | Use | Current source |
| --- | --- | --- |
| `CMP-04 S1` | Asset Components tab | `CMP-04-01-asset-components-tab-mobile.png` |
| `CMP-04 S2` | `Naar tray` button crop | Crop from `CMP-04-01-asset-components-tab-mobile.png`. |
| `CMP-04 S3` | Remove-to-tray confirmation modal with locked serial | Missing. Automated capture did not open the modal. |
| `CMP-04 S4` | Serial unlocked/edit state | Missing. Capture later without submitting. |

Stop and ask if:

- The component has an unexpected serial, the wrong component is selected, or the target is not tray/storage as expected.

Finished when:

- The component is in tray/storage and the event records the serial decision.

### HELP-01 Common Problems

Goal: a compact troubleshooting card for the floor.

Recommended Affinity blocks:

- `B00`, `B01`, `B02`, repeated `B07`, `B09`, `B10`.

Candidate rows:

- Cannot log in: use `AC-01`, ask supervisor if password reset is needed.
- Camera does not open: allow camera permission, refresh, or use manual search.
- QR opens wrong asset: stop and ask before changing anything.
- No workflow appears: ask senior refurbisher/supervisor.
- Printer does not print: use download fallback or ask supervisor.
- Permission denied: do not work around it; ask for the right role.

## Computer Use Build Queue

Use these as separate Affinity sessions or save checkpoints. Each pass should export a PDF proof before moving to the next pass.

0. `PASS-00A Visual Fit Proof`
   - Build one disposable A4 side or duplicate page that follows the current example image more closely.
   - Test one large primary visual, one smaller fallback visual, one physical-photo placeholder, one inline stop at the relevant step, compact help information, related guides, and a 22-25 mm QR placeholder.
   - Use this to validate screenshot height and visual rhythm before rebuilding a full guide.
   - Output proof: `operator-guide-visual-fit-proof.pdf`.
   - June 30 result: rejected. The generated/imported PDF proof copied the liked concept's skeleton but not its visual quality. Do not reuse `operator-guide-visual-fit-proof-v3.*` as a pattern.
   - Correction: the next proof should be a smaller native Affinity `Visual Style Strip`, not a full A4 guide. Test only the header/context fragment, a large primary scan block, a smaller fallback search block, and a verification block with inline stop. Add footer/help rail only after the core visual language works.

0b. `PASS-00B Visual Style Strip`
   - Build directly in Affinity, not as a generated full-page PDF.
   - Use the clean-build rules in `docs/manuals/operator-guide-clean-design-plan-2026-06-30.md`.
   - Start from a clean A4 document or an intentionally empty duplicate, not an old proof/template page with inherited objects.
   - Use neutral placeholder frames first, then place one or two real crops after spacing is acceptable.
   - Do not include login, dashboard orientation, workflow start, generic help rail, or footer QR yet.
   - Output proof: `operator-guide-visual-style-strip.pdf`.

1. `PASS-00 Library Setup`
   - Create document presets, master page, swatches, text styles, and reusable block assets `B00` through `B13`.
   - Output: `operator-guide-block-library.afpub` or equivalent Affinity file.

2. `PASS-01 AC-01 Login`
   - Build one A4 side from `B04 Three Screenshot Sequence`.
   - Verify three screenshots are readable at print size.
   - Output proof: `AC-01-login-draft.pdf`.

3. `PASS-02 SC-01 Scan Asset`
   - Build one A4 side with scanner page and placeholders for live camera/result screenshots.
   - Output proof: `SC-01-scan-asset-draft.pdf`.

4. `PASS-03 AST-01 Open Existing Asset`
   - Build one A4 side with search result and asset-detail confirmation crops.
   - Output proof: `AST-01-open-existing-asset-draft.pdf`.

5. `PASS-04 AST-02 Front`
   - Build only the high-level five-band front side.
   - Use reference chips instead of detailed screenshots.
   - Output proof: `AST-02-existing-asset-refurbishment-front-draft.pdf`.

6. `PASS-05 AST-02 Back`
   - Build workflow-detail side after missing `WF-02` screenshots exist.
   - Output proof: `AST-02-existing-asset-refurbishment-back-draft.pdf`.

7. `PASS-06 WF-01 Start Workflow`
   - Build a one-side workflow start guide.
   - Requires profile/start form screenshot before final proof.

8. `PASS-07 WF-02 Complete Workflow`
   - Build after active workflow/result screenshots are captured from a disposable dev asset.

9. `PASS-08 CMP-04 Remove Component To Tray`
   - Build with current components tab and missing modal placeholders first.
   - Replace placeholders after modal screenshots are captured.

10. `PASS-09 HELP-01 Common Problems`
    - Build as a text/callout-heavy card after wording is reviewed.

## Screenshot Capture Backlog

| Need | Priority | Notes |
| --- | --- | --- |
| Real phone/browser start state for `AC-01` | Medium | Needed only if the login guide must show "from phone home screen". |
| No account / forgotten password / no phone help cues | High | Preserve old right-side extra information in the compact login block. |
| Dashboard scan button / scan card entry point | High | Needed for `AST-01` and `SC-01` step screenshots. |
| Scanner with camera permission granted | High | Current scanner capture shows no live camera. |
| Scan result opening asset | High | Use safe QR or disposable test asset. |
| Search field with example asset tag typed | High | Needed because users do not know where/what to type. |
| Search field with serial number typed | Blocked | Desired, but serial-number search is not supported yet. |
| Physical QR label location on real/staged devices | High | Needed because app screenshots do not show where the sticker is on the device. |
| Workflow start form/profile selection | High | Capture without submitting, unless using disposable asset. |
| Active workflow result card | High | Requires test-run state. |
| Workflow note/photo evidence control | Medium | Needed for `WF-02` and `AST-02` back. |
| Remove-to-tray confirmation modal | High | Need locked serial state. |
| Remove-to-tray serial unlocked state | Medium | Capture without submit. |
| Printer/download QR label state | Medium | Needed for `AST-03`, not first blocks unless added. |

## Proof Acceptance Checklist

Before using a block/page as an Affinity pattern:

- The page prints legibly at A4 without zooming.
- Each screenshot has one clear purpose.
- The visual-purpose plan explains why each screenshot/photo exists.
- No duplicate screenshot is present unless it shows a different operator state.
- Screenshot text is still readable or the crop is intentionally treated as a visual landmark only.
- The guide code and title are visible in the first glance.
- `Stop and ask if` is concrete.
- `Finished when` describes a visible system state.
- Related guide chips include code plus label.
- Version/date/QR area exists, even if the QR is still a placeholder.
- The page works in black-and-white print.
- The page does not depend on a screenshot known to be from the wrong screen.
