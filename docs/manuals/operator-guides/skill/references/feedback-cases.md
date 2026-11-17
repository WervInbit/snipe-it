# Feedback Cases And Comparison Tests

Paths below are relative to the repository root. Read the relevant source;
these cases explain how to apply it rather than create a second rulebook.
Exact acceptance belongs to the version review/decisions, not this summary.

## Concrete Navigation And Preservation

Source: `docs/manuals/operator-guides/reviews/navigation-and-space-feedback-2026-10-27.md`,
`reviews/AST-03-v19.md`, `guides/AST-03.md` (all under the same operator-guides root).

- Symptom: adding duplicate-search support dropped the named Dashboard creation
  control. A list plus still opened the form, so route execution checks passed.
- Reason: the useful concrete control and entry option had been substituted
  without evaluating what the reader lost. Search and creation were complementary.
- Principle: compare preserved teaching at the level the reader uses it:
  visible controls, orientation, explanation and choices, not just abstract
  capability such as "can create an asset". Trace both entering and submitting.
- Boundary: every possible entry need not appear in every guide. Preserve
  owner-required/useful entries here; justify other omissions by task scope.

Exercise: review the currently selected AST-03 with its accepted/retained
comparison. Can a reader search before creating, reuse a match, find both
specified creation entries, understand tag/serial/model, leave notes optional,
save, select printing and verify the label on the same device? Read both sides
as one task. Record any deployed-validation mismatch separately from policy.

Forward application in another family: CAT-04 v8's review on November12 found
that Add Expected Subcomponent and Add Attribute Contribution were named before
their controls were shown. Added linked bottom-of-section details from retained
full captures while preserving the readable row views. Read
`docs/manuals/operator-guides/reviews/CAT-04-v8.md`. The source/PDF text checker
passed v7: it cannot detect a control missing inside a raster crop. Point-of-use
visual review supplied different evidence. Showing the second button later on
page5 did not resolve page4's instruction. Do not generalize this into a button
detail for every action; choose sufficient contextual support for the reader.

State consistency: `docs/manuals/operator-guides/reviews/USR-04-v9.md` corrects
one caption that said a visibly empty checkbox was checked. Keep the genuine
image and describe the shown unsaved target state accurately. The comparison
changes no route, crop or account state; it demonstrates that text/image claims
need review even when source words and pixels survive conversion perfectly.

## Alternatives, Continuations And Independent Routes

Source: `guides/SC-01.md`, `reviews/SC-01-v13.md`,
`reviews/reauthoring-test-2026-10-08.md`, `catalog-guide-plan.md`, `guides/CAT-05.md`.

- Symptom: scanning and manual search read as successive required steps; an
  appended page restarted at step 1 after the apparent end.
- Reason: numbering and placement contradicted the intended route relationship.
- Principle: show the condition/choice before the action and make convergence
  and completion visible. Review stronger visual cues as well as the prose.
- Boundary: independent maintenance routes can restart their own numbering.
  SC-01's shared identity check and CAT-05's route index require different structures.

Exercise: follow one SC-01 route while ignoring the other. Does it lead to one
shared physical/digital identity check? On CAT-05 choose one indexed route:
can it start, finish and return without implying that all branches are mandatory?

## Focus, Locale And Access Groups

Source: `reviews/emphasis-feedback-2026-10-08.md`,
`reviews/navigation-and-space-feedback-2026-10-27.md`, `components.md`, `screenshots.md`.

- Symptom: complete tightly cropped forms/checks gained redundant red outlines;
  role names were translated or embellished; English account preferences
  overrode the application's Dutch default.
- Reason: visible-item counts and authoring permission notes were mistaken for
  reader needs. Global locale alone did not establish screenshot language.
- Principle: compare marked/unmarked images at the same final size. Keep marks
  that resolve locating or distinguishing, exact existing access-group names,
  and genuine account/global Dutch evidence with visible-label inspection.
- Boundary: AC-02 explicitly needs red focus on profile and Wachtwoord wijzigen.
  Small scanner/history icons or a physical label can still need marks. A whole
  jointly checked form need not. Genuine missing translations remain product
  gaps. `Iedereen` remains valid for universal guides.

Exercise: compare current AC-02 and SC-01 with their earlier rejected and
corrected examples. Identify the job of each retained mark. Do not "fix" the
comparison by applying a universal no-focus or focus-every-control rule.

## Guidance, Space And Warnings

Source: `authoring-method.md`, `system.md#stop-and-help`,
`reviews/navigation-and-space-feedback-2026-10-27.md`, owner feedback in `decisions.md`.

- Symptom: hints disappeared, unused bands remained and actions assumed prior
  knowledge. Repeated STOP blocks made routine checks look like incidents.
- Reason: outer geometry and brevity were treated as complete reading support.
- Principle: preserve each useful guidance job, place it where needed, and
  recompose text and actual image content together. For an exception explain
  when it applies and how work resumes; ordinary checks remain ordinary.
- Boundary: whitespace can separate content; not every screenshot benefits
  from enlargement. Genuine identity/risk halts remain prominent. Neither
  eliminating every hint box nor filling every space is the objective.

Exercise: read the normal route without triggering any exception, then the
specific failure route. Can an unaffected reader continue calmly? Can the
affected reader act on the recovery rather than merely see a warning?

## Affinity Fidelity And Practical Editing

Source: `reviews/final-set-and-affinity-plan-2026-11-04.md`,
`reviews/native-conversion-2026-11-10.md`, current native proof reports in HANDOFF.

- Symptom: a native file opened but inserted text inherited the wrong font;
  a source address lost its last line; a successful image API call retained the
  original bitmap; packaging unexpectedly included font files.
- Reason: save/open/API success did not establish the property being promised.
- Principle: prove the actual change and saved result, compare source text to
  PDF text and inspect the render, and check delivered package contents.
- Boundary: fragment checks can flag ligatures, split extraction or an exact
  different input; investigate against the exact files. Screenshot pixels are
  raster evidence even when guide copy/annotations are independently editable.

Exercise: verify CAT-06's original PDF detects the missing address fragment;
compare its separate frame repair, retaining wording/type size and full source.
Reopen an isolated native proof and demonstrate an image replacement rather
than counting an API call. Keep that proof separate from the chosen guide.

## Record A Test Without Overclaiming

Use a realistic request, exact input/version and available baseline. Record
the actual route/read-back, preserved or lost support, recognition findings,
plausible wrong reading, resulting action and evidence limits. Keep expected
case lessons separate from new observed results. Passing these familiar cases
is an initial retrospective test; future unfamiliar tasks and real reader
feedback are still needed to establish broader performance.
