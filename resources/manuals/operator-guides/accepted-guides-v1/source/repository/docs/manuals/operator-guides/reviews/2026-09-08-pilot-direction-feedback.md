# Audit Pilot Direction Feedback - 2026-09-08

Status: owner rejected the direction of the first comparison batch.
Applies to WF-01 v11, USR-04 v4, and CAT-04 v3 as replacement proposals.
Retain their PDFs, source snapshot, checksums, and historical review notes.

## Owner Feedback And Decision Boundary

The owner identified WF-01 v10's profile selection at 2A and workflow start
at 3A as the intended structure. The new batch felt too sparse and too
concentrated on the left, with screenshots and information removed. Missing
hints and bottom help were regressions. The only element explicitly praised
was the clearer block identifying two alternative choices.

This rejects the pilot design direction; it is not a separate verdict on every
factual audit finding. The clearer choice treatment is a preferred element,
not acceptance of v11 or permission to propagate its route order, layout,
typography, or page count. Existing baseline acceptance remains unchanged.

## What The Comparison Confirms

- WF-01 v10 shows five labeled visuals: 1A, 2A, 3A, 3B, and 4A. V11 has
  four, changes the label meanings, and drops the dedicated start-button crop.
- V11 moves profile selection out of step 2 and into the new-start branch
  in step 3, while adding a separate existing-workflow inspection step.
- V10 retains four help items at the bottom. V11 replaces that area with
  one no-cards warning and some inline stop wording; this loses the familiar
  help lookup and its full content.
- V10's result visual includes a full card and the next card. V11 shows a
  smaller single-card example with less surrounding context.
- The pilot combined route, wording, typography, screenshot, layout, and
  sometimes page-count changes. Passing geometry checks did not establish
  that those combined changes improved task guidance.

The existing component contract already allows different screenshot sizes,
adjacent steps, and task-dependent layouts. The existing typography contract
gives actual A4 review and user judgment precedence over a universal font size.
The sparse redesign was an implementation choice, not a project requirement.

## Revised Approach

Start each future revision from its preserved baseline. Use the audit to
identify a specific defect and repair it locally. Consistency means stable
meanings, recognizable actions, explicit choices, help, and completion. It does
not require every guide to use the same column structure or screenshot count.

Before editing, inventory every instruction, image and its recognition
purpose, caption, hint, help item, completion check, and handoff. The comparison
must account for each item. Do not remove instructional support or shrink
recognizable screen context to accommodate a preferred font, grid, or white
space target. Preserve useful right-side content and the baseline page model.
If a necessary correction cannot fit legibly, show that specific tradeoff.

Keep factual corrections separate from design experiments. Incorrect labels,
account identity changes, misleading warning evidence, or unclear exclusive
actions still merit focused correction. Rejection of this pilot does not make
the earlier content automatically correct or approve any proposed policy.

## Next Revision: WF-01 v12, Based On v10

The owner explicitly assigned the next revision v12 and requested consistent
versioned naming to avoid confusing variants. Its PDF filename is
`WF-01-workflow-starten-v12-draft.pdf`, continuing the v11 filename stem;
its review record will be `WF-01-v12.md`. Keep this name in the PDF, review
ledger, and handoff. Do not add labels such as aligned, revised, final, or
model names to create competing filenames. Future revisions increment the
version; retain historical filenames and artifacts unchanged.

V12 was subsequently generated at the owner's request using this v10
composition and screenshot mapping, leaving rejected v11 intact. See the
[v12 review](WF-01-v12.md) for the exact PDF, changes, and validation.

| Preserve from v10 | Purpose |
| --- | --- |
| Step 1 / 1A: open Tests | Recognize the tab in its surrounding icon row. |
| Step 2 / 2A: choose the workflow profile | Keep selection distinct from starting the workflow. |
| Step 3 / 3A: start once | Retain the actual start-button screenshot and action. |
| Step 3 / 3B: continue with Bewerk | Keep the existing-run alternative in the same decision step. |
| Step 4 / 4A: inspect cards | Preserve the full card and next-card context and WF-02 handoff. |
| Four bottom help items | Keep Geen test-icoon, Run onduidelijk, Verkeerd profiel, and Geen kaarten with their existing recovery instructions. |
| Context, captions, completion, references, and footer | Preserve orientation, recognition, help lookup, and the visible end state. |
| Two-column page anatomy and useful image scale | Use the page width for task support. |

Make only the two alternatives in step 3 clearer as one choice. V10 already
contains an OF divider; improve the grouping and state that only one action
is performed. Place any short condition needed to avoid an unintended new
run before either action within step 3. Keep 2A as selection, 3A as start,
and 3B as continue. Do not introduce a new inspection step or silently change
accepted workflow policy as part of this visual correction.

Compare that single proof with v10 for completeness, action order, image
recognition, help lookup, reading order, and actual-size legibility. Record
every intentional difference. Keep other guides at their baselines until the
owner has assessed this direction. Later user trials should check where a
new user hesitates, selects both alternatives, cannot find a control, or
cannot recover using the help area; no such trial is claimed here.

## Retention

The [round ledger](audit-revision-round-2026-09-08.md) records these decisions
against the exact retained candidate files. The
[frozen baseline](baseline-2026-09-08.md) remains the recovery reference.
No PDF, generator, canonical evidence, source archive, or active manifest was
changed while initially recording this feedback. The later v12 generation
adds separate files and retains those earlier bytes. No active-package
rollback is needed.
