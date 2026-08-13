# Operator Guide Feedback Replan - 2026-06-30

Current status (2026-07-21): historical feedback synthesis. Its retained decisions are consolidated in `docs/manuals/operator-guides/system.md` and `docs/manuals/operator-guides/decisions.md`.

Status: draft planning revision. This file sorts the June 30 feedback against the current guide plan and Affinity block spec. It is not final guide copy.

July 2 foundation: use `docs/manuals/operator-guide-design-foundation-2026-07-02.md` for the current reusable design grammar. In particular, alternatives inside one step should use sublabels such as `1A`/`1B` and an `OF` marker, screenshots/photos should be numbered and captioned, and step-specific `STOP` blocks must remain inside or visually attached to the referenced step.

## Current Problem

The current planning material and first draft PDFs proved useful, but they are drifting toward a brain dump:

- too much undifferentiated planning detail;
- too much page space spent on fixed metadata and large screenshot blocks;
- screenshots appear before the operator has been guided into the task;
- warnings are separated from the step where the warning matters;
- the login guide currently feels oversized for the amount of work it teaches;
- the draft does not preserve enough of the older right-side help information.

The next revision should not simply add more notes. It should reorganize the guide system into clearer layers and produce a more operator-facing page rhythm.

## Middle Ground

Keep these parts of the current plan:

- guide codes such as `AC-01`, `AST-01`, and `WF-01`;
- color/icon/code references;
- related guide chips at the bottom;
- version/date/source footer;
- a bottom-right QR area;
- role and needed-items context;
- finished-when box at the bottom;
- right-side extra information from the old drafts, especially account/password/device availability help.

Change these parts for the next draft:

- Use a step-first layout. The operator should see the action first, then the relevant screenshot for that action.
- Put screenshots beside or directly under each step. Avoid a large screenshot gallery before the steps.
- Use smaller, tighter screenshot crops. Full mobile screenshots are only useful as landmarks; action steps need focused crops.
- Make `Role` and `Nodig` much smaller. They are useful context, but they should not dominate the guide.
- Do not keep `Stop en vraag` as a large permanent top panel unless it applies before the first action. Most stop rules should appear inline at the first relevant step.
- Increase the latest-guide QR size at the bottom right.
- Prefer `AC-01 Login` as a compact access block if it fits and remains readable. This is a preference, not a hard requirement.

## Revised Page Rhythm

Use this order for most floor guides:

1. Compact header: code, title, icon, version cue.
2. Compact context strip: `Rol` and `Nodig`; no large stop panel by default.
3. Numbered step rows.
4. Each step row has:
   - step number;
   - short action;
   - one focused screenshot crop;
   - optional inline `Stop` or `Vraag` callout only if relevant at that step.
5. Optional right-side or lower-side help rail for non-linear help.
6. `Klaar als` box near the bottom.
7. Related guide chips, source/version text, and larger QR at bottom.

This is the main change from the current PDFs. The page should guide linearly instead of showing screenshots first and explanations later.

## Visual Evidence Rule

The old plan said fewer screenshots were preferred. The next correction is not to require more screenshots by count. The better rule is that every visual must have a clear operator job.

New direction:

- Do not set a required screenshot quantity before the workflow is understood.
- Classify each needed visual before layout:
  - primary path visual: the main action the operator should take;
  - alternative path visual: a fallback such as manual search instead of scan;
  - physical context photo: where to find a QR code, label, port, or part;
  - verification visual: what the correct screen/device match looks like;
  - stop/risk visual: the screen area that supports an inline stop warning.
- Use a screenshot only when it helps the user press, type, scan, recognize, verify, or stop.
- Crop tightly enough that the target control or physical cue is readable.
- Target a middle screenshot size: smaller than the current `AC-01` and `AST-01` proof screenshots, but larger than the smallest screenshot in the initial example photo.
- Use one wider context screenshot only when the screen location is otherwise confusing.
- Avoid decorative screenshots that only show the app exists.
- Omit duplicate screenshots unless the duplicate shows a genuinely different state, for example login page versus dashboard, error state, permission prompt, or an active workflow card.

Print risk:

- A fixed screenshot-per-step rule can make an A4 page unreadable.
- The solution is a visual-purpose plan, tighter crops, and fewer words.
- Do not overcorrect into tiny screenshots. If a user cannot read the control or recognize the screen, the crop is too small.
- If a task still needs several screenshot-heavy visuals, split it or use a back side instead of shrinking the evidence below useful size.

## Warning Placement Rule

The current top `Stop en vraag` block is useful but too detached from the moment where the user needs it.

New direction:

- Hard rule: a stop warning must be inside the step block it references or visually attached to that exact block. A separate bottom warning strip, detached help rail, or generic help tile is not acceptable for a step-specific stop.
- Use a top stop box only for a global precondition, for example "do not continue without an account".
- Put step-specific stop rules inside the relevant step row.
- For `AST-01`, the mismatch warning belongs at the "Controleer titel en model" step, not at the bottom.
- Keep `Klaar als` at the bottom because it describes the end state.

## Right-Side Help Information

The old designs had useful additional information that should not be lost.

Keep a compact help rail or help block for:

- no account;
- forgotten password;
- no phone or device available;
- camera permission not available;
- QR damaged or missing;
- printer/download fallback, where relevant.

Placement:

- If the guide has 3-4 steps, place help on the right rail.
- If the guide has 5 screenshot-heavy steps, put help as a compact bottom band above `Klaar als`, or move it to `HELP-01`.
- Do not let help text displace the main numbered steps.
- Do not put stop warnings in the help rail. Help rail content is for non-linear assistance and fallbacks; stop content belongs inside or attached to the relevant step.

## QR And Footer

Keep the footer pattern, but make the QR more useful:

- Bottom-right QR should be visibly larger than the current proof.
- Preferred QR size: 22-25 mm.
- Minimum QR size: 18 mm.
- Keep a short label: `Laatste versie` or `Digitale gids`.
- Related guide chips stay bottom-left.
- Version/source line stays small, but readable.

## Guide-Specific Replan

### AC-01 Login

Problem in current draft:

- The guide currently takes a full A4 page for a simple task, which may be more space than needed.
- The screenshots can be smaller.
- The account/password fallback information is missing or too weak.

Next draft direction:

- Try `AC-01` as a compact block, likely half-page or one-third page, if print readability holds.
- It can share a page with scan/search startup if print testing stays readable.
- Use visuals only for distinct jobs:
  - phone/device or browser entry point with quick link or URL note;
  - login screen with the `Inloggen` control visible;
  - dashboard or scan entry visible after login.
- Avoid a duplicate login screenshot unless it shows a different state that changes the user's expectation.
- Include a compact help block:
  - no account: ask supervisor;
  - forgotten password: use reset/supervisor flow;
  - no phone/device: use shared workstation or ask supervisor.

Pitfall:

- Do not over-compress the screenshot where the user must find `Inloggen`. If the button text becomes unreadable or the flow feels cramped, use more space, including a full page if that is what prints best.

### AST-01 Asset Openen

Problem in current draft:

- The step-first left-column direction is closer, but spacing is too large.
- The top metadata strip is too large.
- The stop warning is too late at the bottom.
- Step screenshots do not yet show enough of what the user must press or type.

Next draft direction:

- Build the page from visual jobs, not from a fixed screenshot count:
  - primary path: QR scan entry point and scanner action should be dominant;
  - alternative path: smaller search-bar crop with note that asset tag or QR value can be typed;
  - physical context: photo or staged photo of common QR label locations;
  - verification: asset header/model/device comparison crop;
  - stop support: mismatch warning inside or visibly attached to the verification block.
- Put the mismatch stop rule inside or attached to step 4:
  - `Stop: asset tag, model, or physical device does not match.`

Missing/replanning item:

- The user wants search to support serial number later. That is not currently supported. Do not print serial-number search as current instruction until the feature exists. Mark it as a product/planning dependency.
- QR code physical locations are not app screenshots. They need either real device photos or simple annotated photos.

### WF-01 Test Uitvoeren

Problem in current draft:

- It is closer to the desired step-first style, but it still depends on broad screenshots.
- The guide needs to show the inconsistent app flow more explicitly.

Next draft direction:

- Use screenshots for:
  - tab/icon to open Tests / workflows;
  - `Nieuwe workflow starten` or active workflow entry point;
  - pass/fail controls;
  - note/photo controls;
  - final expected saved state or status.
- Inline warning belongs near the result-button step:
  - `Klik niet zomaar alles op Geslaagd. Noteer fouten.`

Pitfall:

- Starting/completing workflows changes dev data. Use disposable/dev assets for screenshots or capture without submitting where possible.

### SC-01 Scan Asset

This guide becomes more important than the current block spec suggests.

Needed screenshots:

- where to press to open camera/scan;
- scanner page with camera allowed;
- manual search fallback;
- what the user should type: asset tag or QR payload; serial number only after support is implemented;
- scanned/opened asset result.

Pitfall:

- Browser/camera permission screenshots may need a real device or an interactive capture session. A headless screenshot can show a no-camera state, which is not enough for final operator guidance.

## Information Architecture Replan

Use these document roles to avoid another brain dump:

- `docs/manuals/operator-guide-planning.md`: decisions, open questions, guide inventory.
- `docs/manuals/operator-guide-feedback-replan-2026-06-30.md`: current feedback synthesis and next-draft direction.
- `docs/manuals/affinity-development-blocks-2026-06-25.md`: reusable layout/build blocks, updated by revision notes.
- Future per-guide specs: one small file per guide or one structured section per guide with final copy, visual-purpose plan, screenshot/photo sources, and layout notes.

Avoid putting all raw feedback, design theory, screenshot inventory, and final copy in one file.

## Revised Layout Blocks Needed

Add or prioritize these blocks for the next Affinity draft:

| Block | Purpose |
| --- | --- |
| `B14 Step Screenshot Row` | One numbered action, short text, one focused screenshot crop, optional inline warning. |
| `B15 Compact Context Strip` | Small role/needed strip; replaces the oversized role/needed/stop row for most pages. |
| `B16 Inline Stop` | Small warning attached to the step where the warning first matters. |
| `B17 Help Rail` | Keeps old right-side additional information without interrupting the main steps. |
| `B18 Compact Access Block` | Login-specific small block for half-page/shared-page layouts. |
| `B19 Larger Footer QR` | 22-25 mm latest-guide QR with label and enough quiet space. |
| `B20 Visual Purpose Plan` | Pre-layout worksheet that assigns each screenshot/photo a job before Affinity placement. |

## Screenshot Capture Backlog Changes

Add these capture needs:

- dashboard scan button / scan card as the entry point;
- search field with example asset tag typed;
- search field with example QR payload typed, if that is the fallback;
- serial number search only after product support exists;
- scan page with camera permission granted;
- physical QR label location on laptop/phone/device;
- asset result row/card before opening;
- asset header/model/status crop for comparison;
- workflow tab/icon crop;
- `Nieuwe workflow starten` button crop;
- result-card crop showing `Geslaagd`, `Mislukt`, `Notitie`, and `Foto`.

## Product Dependencies Surfaced

These are not guide-layout tasks, but they affect final wording:

- Serial-number search is desired but not supported yet.
- QR code or asset-tag fallback wording depends on what the search resolver accepts.
- Physical QR placement needs a policy or examples per device type.
- If the app flow remains visually inconsistent, guides must include stronger visual landmarks than a normal app would need.

## Next Draft Plan

1. Stop treating the June 25 generated PDFs as the active design direction. Keep them as proof artifacts only.
2. Update the Affinity block spec with a visual-purpose plan instead of a screenshot-count rule.
3. Treat `operator-guide-visual-fit-proof-v3.*` as a rejected negative test. It copied the liked concept's page skeleton but failed because raw screenshots, placeholder photos, help rail, footer, and mixed guide scope were all squeezed into one page.
4. Next run `PASS-00B Visual Style Strip`: a small native Affinity proof with only a header/context fragment, large primary scan block, smaller fallback search block, and verification block with inline stop.
5. Build a new `AST-01` proof only after the visual strip works, because `AST-01` needs QR scan, search fallback, physical QR location, and verification.
6. Try a compact `AC-01` block next, likely half-page or shared page, but keep it larger if readability suffers.
7. Build `SC-01` before expanding `AST-02`, because `AST-01` depends on scan/search clarity.
8. Only after these work, regenerate `WF-01`.

## Open Decisions

- Can `AC-01 Login` share a page with `SC-01 Scan Asset` while staying readable, or should it remain standalone?
- Should `AST-01` include both scan and search in one guide, or should scanning be delegated to `SC-01` with only a reference chip?
- What exact Dutch terms should be used for "QR payload", "asset tag", and future "serial number search"?
- Where should physical QR placement photos come from: real devices, staged photos, or simple annotated example images?
- Should guide QR links point to a guide index or a per-guide latest PDF?
