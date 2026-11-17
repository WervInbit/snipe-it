---
name: snipeit-operator-guides
description: Create, revise, review and prepare editable Affinity operator guides for the Snipe-IT refurbishment fork, using its maintained standards, owner feedback and guide specifications.
---

# Snipe-IT Operator Guides

Produce a guide whose intended reader can find the controls, understand the
choices, complete the permitted task and recognize its result. Preserve useful
teaching and the established family appearance while bringing facts up to date.

## Establish The Task And Sources

Find the actual repository root from the current workspace; do not assume a
machine path or that the checkout is the running application. Start at
`docs/manuals/operator-guides/HANDOFF.md` and the requested guide specification
under `docs/manuals/operator-guides/guides/`. Follow repository AGENTS.md and
the checkpoint/backlog for session initialization and shared-file ownership.

Read `docs/manuals/operator-guides/authoring-method.md`. It remains the
authoritative reasoning method. Resolve applicable rules using system.md's
document precedence, the current family plan, specification, registry,
components/layouts/screenshots, decisions and linked owner reviews. This skill
routes their application; it does not replace them. A newer generator or a
successful conversion cannot supersede an owner decision. A dated restriction
must be read in its original scope and against current user authorization.

Choose the mode from the request:

- **Author or revise:** establish the reader outline before selecting captures
  or editing a generator. Read [feedback cases](references/feedback-cases.md)
  for the affected decision, including the limits of each lesson.
- **Review:** reconstruct the journey from what the rendered page communicates,
  then compare it with the specification and useful baseline support. Review
  findings can be the complete requested output; do not silently regenerate.
- **Convert or finish Affinity files:** preserve the selected content/version
  while proving native properties. Read [Affinity production](references/affinity-production.md).
  Separate a conversion repair from an editorial revision; describe either one.

## Make And Test Editorial Decisions

1. Establish the reader's starting screen/knowledge, exact access-group name,
   task or reference question, and observable success. Record required current
   behavior, desired future behavior, retired approaches and unresolved facts
   separately. Verify a runtime claim against the named target when needed;
   source presence or an old capture alone cannot establish deployment.
2. Trace the actions, alternatives, recovery and handoffs to completion. For an
   unfamiliar transition, supply the entry control, recognizable location and
   result at its point of use. Include the actual submission action. Explain a
   necessary term when first used. Independent tasks may have local numbering
   when their independence is visible; a continuation preserves its sequence.
3. Account for useful baseline support at the concrete level: named controls,
   alternate entries, field meanings, target sizes, examples, hints and recovery.
   Decide whether each affected item is retained, relocated, updated or retired
   and why. Another executable route does not by itself preserve that support.
   Compare required application updates with optional presentation improvements.
4. Give each visual a recognition job. Retain landmarks and judge the actual
   target at printed A4 size. Inspect the unmarked image first; add focus only
   for a remaining search or distinction, or an explicit local owner direction.
   Use genuine Dutch evidence for the intended role/state. Keep untranslated
   controls and unperformed actions explicit in provenance; never paint success
   or translated labels into a screenshot. Check the caption/instruction against
   the actual visible state, including checked/unchecked and before/after claims.
5. Compose for those relationships. Reuse established family components while
   adjusting local heights, crops and widths. After moving/removing a block,
   account for its useful guidance and reflow the recovered room. Use room to
   improve actual recognition or shorten the block when enlargement adds no
   benefit. Preserve calm ordinary instructions; condition exceptions on their
   observable trigger, consequence and recovery.
6. Read the rendered pages without relying on the intended outline. Identify
   the first implied action, what looks mandatory, what looks exclusive and
   what signals completion. Try a plausible wrong reading. Compare the complete
   revision with its useful baseline and one reasonable alternative, including
   any lost support. Keep a short reasoning brief in the specification/review.

Use the method's reader review criteria rather than quotas for marks, warnings,
pages, screenshots or occupied space. One comparison's mark count or page model
is evidence about that case. Exact group names and owner-required local controls
are requirements; a model's stylistic preference is not a reason to change them.

## Verify Claims Separately

Maintain distinct evidence for:

- **Artifact integrity:** exact inputs/hashes, complete source content, page
  dimensions, fonts, resources, rendering and warnings.
- **Editorial interpretation:** the rendered route, recognition, alternatives,
  recovery and concrete baseline comparison. Automated checks cannot establish
  these judgments; inspect full pages and relevant details at useful scale.
- **Native editing:** actual text/style/image/vector/group/master operations,
  save/reopen, native-derived PDF and relocation, where editable delivery is requested.
- **Observed use and acceptance:** real operator/physical trials and exact owner
  decisions. A simulated walkthrough is not a reader trial or acceptance.

For an IDML/PDF pair, run `scripts/verify-guide-pair.py --help`. This helper
compares source story fragments with extracted PDF text, page count, A4 and
optional layers; it can render pages. Use the current bundled Python with
pypdf and, for rendering, pypdfium2; locate its runtime rather than reusing an
old machine path. Read every failure and inspect the PNGs.
Text presence does not prove correct placement, reading order or legibility.

Use fresh output paths for proposals/proofs, preserving accepted artifacts,
frozen inputs and human-edited native masters. Keep active state in the project
handoff/backlog and dated review, not this reusable skill. Report remaining
gaps accurately; do not promote a draft or a converted file automatically.

## Refine The Skill

Turn feedback into symptom -> reader reason -> principle -> boundary, with a
linked source. Correct the narrow reasoning gap and retest affected examples
plus an earlier passing case with different needs. Test trigger selection and
actual outcomes; matching a checklist or expected wording is insufficient.
Keep normative project rules in their owning documents and mechanics in tested
helpers. Update this maintained project copy, validate it, then install its
exact copy using `scripts/manuals/install-operator-guide-skill.py` from the repo.
