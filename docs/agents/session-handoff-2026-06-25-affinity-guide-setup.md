# Session Handoff: Affinity Operator Guide Setup

## Purpose

Continue the laminated operator-guide planning in a fresh Codex session with Computer Use enabled from the start. The immediate goal is to check whether the research-guided Affinity Publisher setup works in the real Affinity UI before guide content is finalized.

## Why A Fresh Session Is Needed

Computer Use was enabled after the current thread had already started. This thread could see that the plugin was enabled, but it did not expose callable desktop actions such as screenshot/observe/click/type/mouse/keyboard. A fresh session is expected to receive the refreshed tool context.

If the fresh session still cannot call desktop control tools, fall back to guiding the user through Affinity setup manually and reviewing exported PDFs/screenshots/photos.

## Primary Files

- Planning tracker: `docs/manuals/operator-guide-planning.md`
- Research report: `C:\Users\Gebruiker\Downloads\affinity research deep-research-report.md`
- Example rough design PDF: `C:\Users\Gebruiker\Downloads\refurbisher steps.pdf`
- User may provide or attach the actual printed-page photo in the fresh session.

## Current Planning Status

Treat the operator guide plan as brainstorming unless a line is explicitly marked `Final`.

Current draft directions:

- First floor/refurbisher guide pass should be Dutch.
- Keep the current draft code/color/icon reference system for now.
- Add QR links to latest digital guides or a guide index.
- Defer work-order guides from the first pass.
- Use current operator-facing terms: `Asset`, `Workflow`, `Component`, `Model`, `Tray`, and `Storage`.
- The guide plan should be checked against research/best-practice guidance before real drafting/layout work is treated as ready.

Out-of-thread product follow-up:

- Workflow readiness warnings and asset-list visibility may need to support multiple relevant workflows, not only one base/test workflow. Do not implement that in the guide-planning thread unless explicitly redirected.

## Research Report Pointers

Use the research report as guidance for the Affinity proof, not as final project truth.

Important sections:

- `Samenvatting`
- `Gedeelde documentinstellingen`
- `Voorzijde`
- `Achterzijde`
- `Typografieschaal`
- `Beeldmaten en crops`
- `Spacingregels`
- `Affinity Publisher-stylesheet en setup`
- `Master pages`
- `Affinity Publisher-opzetchecklist`
- `Print-QA en testplan`

The most important research recommendation:

- Build `AST-02 Existing Asset Refurbishment` as a deliberate double-sided A4 proof rather than forcing it into one crowded side.
- Front side: vertical five-step main flow.
- Back side: detailed "workitem invullen" flow with larger crops and annotation blocks.

## Fresh Session Goal

In Affinity Publisher, test whether the recommended setup is feasible:

1. Create a new A4 portrait document, facing pages off.
2. Set margins to 10 mm.
3. Add the proposed column/grid guides: text column 118 mm, gutter 8 mm, image rail 64 mm inside the 190 mm live area.
4. Create or approximate master pages:
   - `A-Front`
   - `B-Back`
5. Create the key text styles from the research report:
   - `P-GuideTitle`
   - `P-GuideMeta`
   - `P-HelpStrip`
   - `P-StepBandTitle`
   - `P-StepBandBody`
   - `P-StepL1`
   - `P-StepL2`
   - `P-ExpectedState`
   - `P-SideNoteTitle`
   - `P-SideNoteBody`
   - `P-Caption`
   - `P-Footer`
6. Create placeholder geometry for a dummy `AST-02` front/back proof:
   - front header, help strip, five step bands, footer, QR placeholder;
   - back header, context strip, left text column, right image rail, finished-when box, footer.
7. Export or screenshot the proof if possible.
8. If a physical print/photo is available, compare against print readability concerns from the report.

## Practical Constraints

- Do not make app/code changes for this task.
- Do not treat the Affinity proof as final guide content.
- Native Affinity `.afpub`/`.afdesign` generation by automation is not assumed. If Computer Use works, use the real GUI. If not, produce a precise manual checklist and review artifacts exported by the user.
- Keep repo runtime noise untouched: upload placeholder `.gitignore` line-ending changes, `.env.before-prodclone.2026-04-30`, `.env.prodclone.prodkey`, and `prodbak/`.

## Suggested First Prompt For Fresh Session

```text
We are continuing from C:\dev\snipe-it-fork. Use Computer Use if available. Read docs/agents/session-handoff-2026-06-25-affinity-guide-setup.md, docs/manuals/operator-guide-planning.md, and C:\Users\Gebruiker\Downloads\affinity research deep-research-report.md. Goal: set up or validate an Affinity Publisher proof template for AST-02 using the research report as guidance. Treat guide planning as brainstorming unless marked Final. Do not change app code.
```
