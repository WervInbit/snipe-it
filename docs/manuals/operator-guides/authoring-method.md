# Operator Guide Authoring Method

Use this method when creating, correcting or adjusting any guide. Its purpose
is to make each editorial decision follow from what the reader needs to do
or understand. It controls the reasoning before specifications, evidence and
layouts are produced. [system.md](system.md), the family plan and the guide
specification supply the applicable content and visual constraints.

The reusable [snipeit-operator-guides skill](skill/SKILL.md) applies this method
with feedback comparisons and verified Affinity procedures. This document and
the owning standards/specifications remain authoritative. Install the maintained
copy with `scripts/manuals/install-operator-guide-skill.py`; its checks establish
artifact properties separately from editorial review and practical use.

The main quality question is: **How well does this guide help its reader reach
the intended outcome using what the page actually communicates?** Correct
application facts, recognizable controls, understandable decisions and useful
reading order take priority. Concision and visual consistency support that
outcome. Page count,
equal-sized cards, screenshot freshness and coverage of new features are not
independent reasons to change a guide.

## Reader Review Criteria

Use these questions as the concise review of the whole guide. The sections
below explain how to make the decisions; the shared contracts supply detail.

- Can the intended reader understand the purpose and starting situation in
  ordinary Dutch, without assumed application expertise?
- Does each explanation or visual help the reader recognize, decide, act or
  verify something relevant, at the point where that support is needed?
- Does the guide match the current permitted task for its role, with clear
  actions, alternatives, handoffs and a verifiable outcome?
- Can the reader recognize where the pictured screen or detail belongs and
  find the exact item or complete group to use or check at actual A4 size?
  Does a focus mark resolve a real search or distinction, or merely repeat
  what the crop, visible labels and native emphasis already make clear?
- Are exceptions conditional and actionable, with emphasis proportionate to
  the consequence? Can an unaffected reader continue without interpreting a
  routine check as evidence that something has gone wrong?
- Does the complete revision develop the accepted direction, with concrete
  reader benefits and the useful support of the baseline carried forward?

Crop, shape and wording still require judgment. Evaluate their effects through
these questions and rendered evidence; a fixed screenshot size, mark count or
warning quota cannot establish clarity. Editorial answers remain predictions
until observed reader behavior supports them.

## Reconcile Standards And Decisions Before Reusing A Baseline

Read the current standards as part of establishing the revision objective:
[system.md](system.md), [components.md](components.md), [layouts.md](layouts.md),
[screenshots.md](screenshots.md), [decisions.md](decisions.md), the relevant
family plan and guide specification, and their linked owner feedback/version
reviews. Follow the document precedence in system.md. A retained guide is
evidence of a useful approach, not a substitute for these current requirements.

For the affected subject, distinguish:

- Current requirements and accepted directions: apply them to the revision.
- Desired standards or preferences: aim for them and record any remaining gap.
  A target that requires a product change is not an available application state.
- Retired, rejected or superseded approaches: remove their inherited effect
  from the next revision. Retained artifacts preserve history, not permission
  to reintroduce an abandoned scope or design direction.
- Open decisions or missing evidence: keep their uncertainty explicit; do not
  turn a proposal into an instruction. Continue independent work and resolve
  only the uncertainty that materially determines the task.

Resolve decisions by subject, scope and status. A later explicit owner decision
can supersede an earlier requirement on that subject; a newer draft, generator
or review suggestion does not do so merely by being newer. An exact acceptance
does not permanently authorize every statement in that artifact. Older design
plans supply rationale where still applicable; they cannot restore rules that
the current contracts or owner decisions replaced.

Use this map to find the applicable standards; the linked contracts contain
their detail and guide-type exceptions:

| Concern | Current standard or direction | Source |
| --- | --- | --- |
| Reader and language | Explain the task in ordinary Dutch with exact visible controls and no assumed application expertise. Use Dutch UI evidence; expose missing translations as gaps. Keep authoring/model/implementation notes out of operator instructions. | [System](system.md), [screenshots](screenshots.md), family execution/terminology contract |
| Identity and navigation | Family color plus marker, code and label; complete registered names in relevant references and handoffs. Preserve recognizable context, help, completion and footer functions. | [System](system.md), [components](components.md) |
| Actions and choices | Real sequence numbers, explicit alternatives, appropriate continuation or independent-route numbering, and a verifiable end state. | [System](system.md), [layouts](layouts.md) |
| Evidence and emphasis | A named recognition job, correct role/state, useful A4 target scale and surrounding context, canonical reuse, measured crops and focus marks. | [Screenshots](screenshots.md), [components](components.md) |
| Warning and recovery | Routine checks use ordinary instructions; amber flags recoverable exceptions; red STOP marks a genuine halt. State the trigger and recovery where they matter, without implying an error on the normal route. | [System](system.md), [components](components.md) |
| Composition and legibility | Task-dependent recipes and useful page width; clear type hierarchy and actual-size review. Floor guides normally use one or two sides; declared administration/reference guides can be longer. No fixed screenshot count or universal font-size target. | [System](system.md), [layouts](layouts.md), family plan |
| Scope and operational policy | Current ownership, minimum role, prerequisites and product boundaries. Do not restore retired guides, combined tasks, legacy operations or superseded permission/status rules from an old artifact. | [Decisions](decisions.md), family plan, guide specification and linked feedback |

Compare the baseline and existing specification with the applicable decisions.
Record what remains useful, what needs correction to the current standard,
what must be retired, and what remains an unresolved target. Required standards
corrections join required application updates in the revision plan. Chosen
improvements then develop the guide within that direction. Recheck the complete
rendered revision against the same decisions, including any new page or reused
text, so an abandoned point cannot survive outside the edited area.

## Update And Improve The Accepted Direction

The accepted guides establish a successful direction and a starting point for
further improvement. Develop that approach as the application changes. The aim
is a current guide that is clearer and easier to use, with the strengths of the
accepted work carried forward. Acceptance does not freeze its composition or
set a ceiling on its quality. Carry forward the useful approach after the
standards reconciliation above; an inherited conflict still needs correction.

Distinguish required system alignment from chosen reader improvements. A new
confirmation may require a new instruction; its wording, placement and visual
treatment still need an editorial decision. An improvement may make a choice
clearer, a control easier to recognize or an explanation easier to understand
even when no known error prompted it. Name that expected benefit and the
existing strengths it could affect. A feature change establishes what must be
accurate; it does not justify every accompanying design change.

Judge the complete revision against the accepted direction. Consider the
reader's path, understanding, recognition and effort across the whole guide,
including the newly required work. If necessary new content disrupts that
experience, improve its integration. If a chosen enhancement causes a material
loss, revise or omit it. An obsolete instruction cannot be retained for its
clarity; carry its useful teaching approach into the current flow instead.

## Establish The Reader And Outcome

Describe the situation in ordinary language: who has picked up the guide,
what they already know, what is in front of them, what they need next, and how
they will recognize success. The existing floor-reader assumption is knowledge
of the physical work without prior Snipe-IT or programming knowledge. Adjust
the explanation for an administrative task where necessary; do not assume
that its reader already understands catalogue or permission terminology.

Identify the guide's job from that situation:

- A task guides actions toward an observable result.
- A route overview helps select the next task guide.
- Troubleshooting starts from a problem and offers the applicable recovery.
- A reference explains a question, concept or relationship.
- A collection of independent tasks starts with selection and follows only
  the chosen route.

One coherent reader outcome may require several actions or pages. Registering
and labeling a physical asset is one coherent outcome. A reference chapter or
maintenance collection can cover several related questions. Do not turn the
"one task" preference into a rule that destroys those useful structures.

## Decide What Information Belongs

Establish the relevant current application behavior and owner decisions.
Separate a verified fact from an editorial proposal. For each addition or
correction ask: **What does this help the reader recognize, understand, decide,
do or verify? What becomes easier, and what effort does it add?** Name the
concrete effect. Preventing an error is one benefit; reducing hesitation,
searching or unnecessary explanation can also improve an already usable guide.
Information with no relevant reader benefit does not earn space just because
the application changed.

Keep existing access-group names exactly when naming the role. Do not translate
them or append an invented qualification. The authoring brief still records
effective rights and the capture role. Explain a permission in operator text
only at an action or choice that depends on it; a universal guide can retain
the established `Iedereen` label.

Place information at its point of use. A condition needed to choose correctly
comes before the choice. A required confirmation comes before completion.
An occasional recovery belongs with the problem or in help. Detail owned by
another guide uses a handoff with a clear return point. Teach enough at the
handoff to let the reader choose it; avoid repeating the other guide's task.

The same feature can be relevant in one guide and irrelevant in another.
Relevance follows the reader's outcome, not the feature's name. Necessary
explanations and examples may be lengthy. Removing them for brevity can add
guesswork and make the task harder.

Account for the useful content of every inherited hint, tip and help block.
For each item, retain it, move its guidance to the point of use, update it for
current behavior, or retire it with a concrete reason. Removing a block's
visual treatment does not remove its teaching or recovery job. One irrelevant
tip does not justify dropping the other items in its block. Keep ordinary tips
and recovery available without turning them into repeated warnings.

Trace navigation between actions from the declared starting screen. Name the
menu, tab, row or button and show enough context to locate an unfamiliar entry
point. Check the actual primary submission control as well as the fields.
Explain a necessary term when the reader first needs its meaning; an exact UI
label alone does not establish that meaning. A prerequisite guide can own an
already-taught route, but a footer reference alone cannot fill an unexplained
transition inside the current task.

## Resolve Structure Before Writing Or Fitting Content

Outline what the reader must recognize, decide, do and check. For each action,
name its starting condition and result. For each decision, name the condition
for each route and where those routes converge, finish or hand off. Read the
outline from the declared starting situation to the outcome before choosing
a page layout or writing polished instructions.

Choose the presentation from those relationships. Sequential actions use
consecutive numbers; a conditional action states its condition before the
instruction. Alternatives deserve explicit grouping and choice wording.
A continuation carries its context and numbering forward.
Independent tasks can restart their local numbers when their route titles and
entry points make that independence visible. Reference concepts use headings;
problem lookups use problems. A required unfinished action cannot come after
the guide has declared completion.

Do not infer a valid structure from the old step numbers, a three-card template
or the order of the feature list. The old structure is evidence of what worked,
and may also contain an inherited defect. Repair the whole affected relationship
when necessary. The smallest coherent correction can be larger than the
smallest text patch.

## Plan Evidence And Visual Emphasis

Give each visual a recognition job before selecting its source: locate a
control, explain a choice, establish identity, show a physical location or
verify a result. Name what the reader must be able to recognize in it.
Choose or capture evidence that supports that job, for the intended role and
state. An available screenshot cannot choose the guide's flow for it.

Check orientation and target recognition separately. The reader must be able
to match the screenshot to the application's screen, tab or panel, then find
the named control or value. Retain recognizable landmarks such as a heading,
selected tab or neighboring controls. A generic isolated button is inadequate
when its location is unclear. A linked detail may rely on a nearby overview,
provided their relationship is explicit and the target stays readable.

Review the visual without focus marks first. Ask what the reader would have
to search for or could plausibly confuse with another item. Mark that target
when the crop, visible labels, native emphasis and nearby instruction do not
resolve the search or distinction. Also mark a small or easy-to-miss target
where its location would otherwise be uncertain. Several visible items alone
do not justify a mark. A crop containing only the jointly used fields, a
complete check or the result already isolates that group; do not outline each
field or the whole group again. Keep enough context to locate it in the app.

Use the least annotation that resolves the actual difficulty. Compare its
effect with the unmarked visual at the same A4 size; if removing the mark
loses no useful guidance, omit it. This is a judgment about searching and
recognition, not a quota of marks. A mark cannot restore lost orientation or
unreadable detail. Follow the measured
[focus contract](components.md#focus-contract) for marks that earn their place.

Use the ordinary permitted state for a normal task and explicit examples for
exceptions. In troubleshooting, the problem itself is the appropriate starting
state. A blocked or overridden example must not silently stand in for normal
execution. A claimed journey keeps its identity and states coherent; a
comparison across examples identifies the comparison.

Verify Dutch at both application and capture-account level and inspect the
visible labels. An untranslated application control remains an evidence/product
gap. Do not invent or paint its translation. Reuse good canonical evidence for
unchanged recognition jobs; capture when the evidence materially fails that job.

Select the established recipe that makes the relationships easiest to follow.
Keep an instruction, its target and its explanatory caption visually connected.
Give a decision enough space to compare its alternatives. Make primary actions,
conditions and recovery distinguishable. Preserve enough context for recognition
and enough target size to read or identify the actual control at A4 size.
Compare target size, not merely image-frame dimensions. Excess context can
shrink a useful control; an extreme crop can remove its meaning.

When space is insufficient, revisit ownership, placement and unnecessary
repetition first. Then reflow the local composition or use a coherent additional
page. A changed page count or recipe needs a reader benefit, such as readable
evidence or a new necessary decision. Uniformity or fitting a chosen template
does not supply that benefit. Never obtain fit by silently shrinking useful
type, targets or explanatory support.

When content is removed or moved, recompute the composition. Review the actual
image content and text inside each step, including blank bands above and below
them. Use recovered room for missing guidance, readable targets and useful
context; adjust step heights and neighboring content together. If enlarging
the image would no longer improve recognition, shorten or reflow the block.
Do not retain fixed card heights or maximum-image limits merely because they
belonged to the previous version. Useful reading separation can remain; empty
space must not coexist with lost help, unexplained navigation or needlessly
small controls. A percentage of occupied space is not a usability target.

## Use The Baseline And Feedback As Evidence

For a revision, identify what the accepted baseline already supports: starting
point, choices, screenshots, recognition sizes, explanations, hints, help and
outcome. Explain why these properties work for the reader, so their useful
effects survive even when their implementation improves. Acceptance is positive
evidence of the desired direction as well as a decision about that exact
artifact. It does not prove that every inherited branch is correct or that its
instructions still match changed behavior. Also compare the immediate previous
proposal where useful; its unaccepted changes do not redefine the accepted
direction. A new guide uses the closest useful recipe and examples, with its
own reader outline. Where a revision has no accepted predecessor, use the
relevant accepted family examples for direction and its retained draft for
comparison, without inferring acceptance.

Generalize feedback through **symptom -> reason -> principle -> boundary**.
Do not turn a local correction into an indiscriminate rule. Examples:

| Feedback | Transferable reasoning | Boundary |
| --- | --- | --- |
| A replacement screenshot became too small. | Preserve recognition of the needed control after crop and fitting. | Images can have different sizes; more context or a larger image is not always more useful. |
| Two numbered steps are alternatives. | Make the relationship visible through grouping, numbering and wording. | Two truly sequential actions still need separate steps. |
| Page 2 restarts at 1. | Match numbering and page handoffs to the actual task relationship. | An explicitly independent maintenance route can legitimately restart. |
| Recent devices does not help login. | Include information only when it supports this guide's reader outcome. | Reopening an asset can use recent devices, followed by the identity check. |
| The first workflow example is blocked. | Choose the example that represents the action the reader came to perform. | A guide about resolving that blockage should start from the blocked state. |
| Every field in a close crop is outlined. | Decide whether the crop already isolates the complete action or check; add a mark only for remaining search or confusion. | A small icon among similar icons, or one row among competing results, can still require a mark. |
| A role gains an invented qualification. | Keep the existing access-group name exactly; explain a relevant permission at the action it affects. | A guide available to everyone can retain `Iedereen`; it need not enumerate all groups. |
| Hints disappear but their old space remains. | Preserve each useful guidance job and recompose the affected step around its text and actual image content. | Whitespace can aid reading; stretching every image or reinstating every old box is not the objective. |
| A step says to open a screen without showing the route. | Supply the entry control, recognizable location and resulting screen at the point of use. | Do not duplicate a complete prerequisite task when the guide explicitly starts after it. |

Evaluate the complete proposed guide against the baseline and one plausible
alternative. Identify what must change for current behavior, what the chosen
improvements gain, and what becomes harder or less useful. Prefer the
composition that gives the reader the clearest coherent path with sufficient
support. Explain the tradeoff in the reader's terms. Accurate new facts and
good individual edits do not establish that the combined guide improved.
Avoid numerical quality scores that can hide a serious defect behind unrelated
improvements.

When several compositions support the reader equally well, prefer the
established family appearance, familiar help/reference placement and practical
text scale. Existing user judgments resolve that stylistic uncertainty. A new
model's preference or a desire for novelty supplies no reader benefit.

## Review What The Page Implies

Review the rendered guide using only what the reader can see and the declared
prerequisite knowledge. Set aside the intended outline and authoring source.
Identify the first action the page suggests, what appears mandatory, which
choices look exclusive, where a reader might hesitate, and what would make
them believe they are finished. Check every route, including recovery and
cross-guide returns, against the outline.

Test a plausible wrong reading as well as the intended reading. Could a reader
perform both alternatives, start another asset, confuse an example with their
own record, save before the new choice is explained, or treat an exception as
the ordinary route? Correct the cue that creates that interpretation. A sentence
somewhere on the page does not cancel a stronger contradictory visual cue.

For each screenshot, try identifying its location and target without relying
on the author's knowledge or PDF zoom. For each warning, read the ordinary
route and the exception separately. Does the visible condition make clear
whether the message applies? Does it explain the next action and when work can
continue? Remove emphasis or repetition that makes an unaffected reader infer
an incident merely from reaching the step. Routine checks belong in ordinary
instructions; apply the [warning hierarchy](system.md#stop-and-help) to actual
exceptions, including genuine halt conditions.

For each revision, also ask whether the complete guide now serves its reader
better, and which previously useful instruction or recognition job became
harder. Separate consequences of required new work from costs introduced by
the chosen presentation. A gain elsewhere does not excuse a material regression
without an explicit necessary tradeoff. Review actual A4 size and the affected
control or diagram detail; a contact sheet is useful for structure, not
sufficient for every recognition judgment.

Keep review claims separate. Artifact checks establish hashes, geometry and
rendering. Editorial review establishes a reasoned interpretation of the page.
A novice/operator trial establishes observed reader behavior. Exact acceptance
is the owner's decision. None of these substitutes for the others. If a real
reader's behavior contradicts the predicted interpretation, revise the outline
or presentation and record the reason.

## Keep A Short Reasoning Record

Before authoring a new guide or a substantive revision, put this brief in its
specification or existing version review. Write concrete answers; length follows
complexity. A local wording/alignment correction can reuse the established
outline and document only the affected reasoning and comparison.

```text
Reader and outcome: starting situation, assumed knowledge, guide type, success.
Applicable standards: requirements, desired targets, retired points and sources.
Baseline direction: useful properties, conflicts and unresolved targets.
Revision aims: required system/standards updates and chosen reader improvements.
Structure: required actions, choices, convergence, recovery and completion.
Evidence and emphasis: recognition jobs, landmarks/context, named targets,
                       states, locale, printed scale and necessary focus marks.
Exceptions: observable triggers, proportionate emphasis and recovery/resumption.
Chosen revision: intended gains, placement and material costs/tradeoffs.
Read-back: plausible interpretation of the rendered page, regression findings,
           standards reconciliation, complete-guide comparison and evidence.
```

Resolve purpose, ownership and structure before generator edits. Update the
specification from the reasoned decision; implement the generator from that
specification. Frozen HTML preserves an output for reproduction and is not
instructional authority. Dated generators can reproduce their retained rounds;
their content choices do not establish a production method for new versions.

Apply this reasoning per guide in a requested batch. Shared rendering components
can be reused after focused development; the reader outcome and interpretation
still require review for every affected guide. This method creates no automatic
acceptance and no requirement to ask the owner for permission at every stage.
Work within the existing authorization and resolve routine editorial choices.
Ask only when a missing operational decision cannot be resolved from the session
or sources and materially determines the instruction.
