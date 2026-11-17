# Operator Guide System

Status: current shared content, production and QA rules for operator manuals.

## Authoring Reasoning

Use [authoring-method.md](authoring-method.md) before creating or revising a
specification, choosing evidence or editing a generator. Establish the reader
outcome, relevant information and action/choice relationships first. Keep the
short reasoning brief in the specification or version review and read back
what the rendered page implies. This method applies across task, route,
troubleshooting, reference and independent-maintenance guides. The rules below
supply constraints; matching them mechanically does not choose a useful guide.

Use the method's [reader review criteria](authoring-method.md#reader-review-criteria)
as the concise review of purpose, relevance, task structure, recognition,
exceptions and the complete revision's benefit.

Accepted guides establish the direction to develop. Revisions must match current
behavior and may improve clarity and ease of use without a known defect. Keep
required updates distinct from chosen improvements, and assess their combined
effect on the complete guide against that direction.

Reconcile the current contracts and linked owner decisions before baseline
reuse. The reasoning brief names applicable requirements, desired targets,
retired approaches and unresolved gaps. Review the complete revision against
them as well as the reader outcome. Historical acceptance cannot restore a
superseded scope or rule; a desired future product state cannot be illustrated
as available merely because it is preferred.

## Document Precedence

The authoring method governs how decisions are made. The narrow documents
below govern their content. Current owner decisions and explicit session
instructions constrain every level. A generated HTML snapshot or proposal
header cannot establish instructional authority over those decisions.

Use the narrowest current document:

1. A current family plan controls information ownership and handoffs across
   guides in that family. The CAT family uses
   [catalog-guide-plan.md](catalog-guide-plan.md).
2. A guide specification controls that guide's exact scope, steps, evidence,
   and warnings within the family boundary.
3. [registry.md](registry.md) and [current-set.json](current-set.json) control the
   last delivered version, page model, artifact identity and review state.
   Compare actual shared files for later edits; baseline metadata is not a live
   NAS inventory. The specification/review retains layout and source provenance.
4. [components.md](components.md) controls shared visual components and feedback promotion.
5. [layouts.md](layouts.md) controls reusable page and step structures.
6. This system document controls shared content and production behavior.
7. [maintenance.md](maintenance.md) controls change impact, versioning, and
   multi-guide propagation after feedback or application changes.
8. The decision log controls accepted and unresolved cross-guide decisions.
9. The project index controls current status and working order.
10. [HANDOFF.md](HANDOFF.md) controls the continuation checklist, creation-stage
   snapshot, and environment path mapping. It does not override sources above.
11. Older files under `docs/manuals/` are supporting history only.

## Production Model

```text
Reader outcome and outline -> Specification -> Evidence ready -> Working draft -> Internal review candidate -> Third-party approved
```

The owner authorized and received all 25 native Affinity manuals and paired
PDFs, and reports copying the set to the NAS. Native preparation and packaging
are complete. The saved shared `.af` master owns subsequent human edits;
specifications and shared rules continue to govern instructional scope.
Generators and frozen HTML/IDML reproduce earlier proposals and provenance,
without replacing a newer native master. No conversion or app input is queued.

Every active manual records its page model, exact version, source provenance
and layout reasoning in the register/specification/review. Use a generator only
where it still represents the selected source; perform native edits in a new
copy when appropriate. Follow [shared updates](maintenance.md#shared-manual-updates)
to compare masters and synchronize PDF/metadata. Earlier accepted artifacts
remain immutable; visible changes create new versions under maintenance.md.
Conversion and distribution do not confer exact-version acceptance.

Use manual/handleiding in new user-facing wording. Existing internal paths and
historical artifact names retain their identifiers.

## Page Model

- A4 portrait.
- Prefer one side per guide.
- Use a second side when a real task cannot remain clear on one side.
- Keep header, context, main work, help, completion, references, and footer visually recognizable across guides.
- Do not force every guide into the same number of steps, screenshots, help items, columns, or card sizes.
- Select the base recipe and step patterns from [layouts.md](layouts.md). A
  recipe standardizes structure without forcing equal screenshot sizes or
  identical step heights.

## Guide Types

| Type | Purpose | Examples |
| --- | --- | --- |
| Compact access | Short prerequisite or entry sequence | AC-01 |
| Single task | One task with a small number of actions | SC-01, AST-01, WF-01 |
| Detail task | Dense execution with evidence and risky checks | WF-02, CMP-04, AST-03 |
| Route overview | Points to smaller guides without duplicating them | AST-02 |
| Troubleshooting | Non-linear problem lookup | HELP-01 |
| Reference chapter | Explains decisions and relationships before an administrative task | CAT-00 |
| Extended administration task | Multi-page procedure whose fields require explanation and verification | CAT-01 |

## Visual Identity

| Family | Role | Color direction | Marker |
| --- | --- | --- | --- |
| AC | Access/login | Blue | `AC` plus guide code |
| SC | Scan/search | Teal | `SC` plus guide code |
| AST | Assets | Green | `AST` plus guide code |
| WF | Workflows | Orange | `WF` plus guide code |
| CMP | Components | Amber | `CMP` plus guide code |
| USR | User management | Indigo | `USR` plus guide code |
| CAT | Catalog management | Violet | `CAT` plus guide code |
| HELP | Problems/help | Red | `HELP` plus guide code |

Color supports recognition but never replaces the family marker, code, and label.

## Page Anatomy

Normal floor guides use this reading order:

1. Guide code, title, purpose, and version.
2. Compact `Rol`, `Nodig`, and optional `Vooraf` context.
3. Numbered task steps.
4. Compact non-linear help when needed.
5. `Klaar als` with a visible end state.
6. Relevant guide references.
7. Source/version information and optional maintained digital-guide QR.

Keep bottom utilities anchored near the bottom so short and long guides remain recognizable.

Use existing access-group names exactly in role labels and instructions. Do
not translate, rename or append invented qualifications such as
`Supervisor met reviewrechten`. Record the verified group and effective rights
in authoring evidence; explain a permission in operator text only where it
changes the reader's action or choice. A generally available guide can keep
the established `Iedereen` label rather than listing all groups.

## Typography

- Use AC-01 v6 and SC-01 v10 as the practical visual baseline.
- External accessibility recommendations are guidance, not strict project minimums.
- Preserve clear differences between guide title, step title, body, caption, help, and footer.
- Do not make critical instructions smaller merely to force more content onto one side.
- Actual-size A4 review and user judgment take precedence over a universal point-size rule.

## Steps And Alternatives

- Large numbered circles identify real sequential steps.
- Smaller circular labels identify visuals belonging to a step.
- Equivalent choices inside one step use labels such as `1A` and `1B`.
- Choice wording and grouping must make alternatives explicit. The owner's
  [2026-09-08 feedback](reviews/2026-09-08-pilot-direction-feedback.md) favors
  a clear two-choice block in WF-01 step 3. Keep the action screenshots and
  existing step meanings; do not add an OF block to every guide by default.
- Screenshot labels partly overlap a corner without hiding important interface content.
- Step numbers remain visibly larger than screenshot labels.
- Circular labels use one shared true-center baseline; do not tune text with
  guide-specific vertical offsets.

## Screenshots And Photos

Do not require a fixed screenshot count. Every visual must answer a user question:

- where to start or tap;
- what screen to recognize;
- what alternative path is allowed;
- where a physical QR, label, port, or component is located;
- what evidence to verify;
- what visible state confirms completion.

Rules:

- Give every visual a step-linked label and short caption.
- Keep recognizable landmarks so the reader can locate the pictured screen,
  tab, panel or physical area, then identify the named control or detail.
- Check location and target recognition separately at actual A4 size. A crop
  must preserve both; a linked detail can use a nearby overview for orientation.
- Reuse the same canonical screenshot when multiple guides show the same application state. Guide-specific crops and callouts may differ, but the underlying evidence should remain identical.
- Register reusable evidence in [screenshots.md](screenshots.md) and reference its stable source ID from guide specifications and generators.
- Capture a replacement only when the state is materially different, the existing evidence is stale or unreadable, or a different device context is required.
- Review the crop without annotation first. Use a red circle/rectangle when
  it resolves remaining search, confusion with a competing item or an
  easy-to-miss location. Several visible items alone do not require marks.
  A crop that already isolates the complete action, joint check or result
  needs no extra outline on each member or nearly the whole crop. Use the
  [focus contract](components.md#focus-contract) and compare at the same A4 size.
- A focus mark means where to look; it does not communicate a halt condition
  or compensate for missing screen context or an unreadable target.
- Derive focus marks from measured source-pixel target bounds and add symmetric
  padding on all sides.
- Use the existing mobile camera/QR screenshot for scanner-camera evidence instead of the laptop camera.
- Do not fake missing application states. A development placeholder cannot survive into an approved base.

## Stop And Help

- Reserve a red `STOP` message for a genuine halt condition: continuing would
  create a meaningful safety, identity, irreversible-state, or data-integrity
  risk and the operator must not continue without correction or escalation.
- Write routine checks and ordinary required-field instructions as normal
  actions. Use amber inline warnings for recoverable exceptions that need
  attention, such as validation, duplicates or missing catalogue information.
  Do not turn each check into a warning or each correction into a stop merely
  because it must be resolved before saving.
- State the observable exception condition first, then what the reader should
  do and when work can resume. Use calm, factual wording. Reaching the step
  must not itself imply that the reader has made a mistake or triggered a risk.
- Attach a real step-specific `STOP` at the first point where the reader can
  recognize the condition and avoid the harmful action. Keep the unaffected
  route clear.
  Repeat it only for a distinct risk or an independently entered route that
  needs the warning; do not repeat it at every later step as a standing banner.
- Distinguish a locally correctable field discrepancy from uncertainty about
  which physical device or record is being worked on. The latter can require
  a genuine halt; the word "mismatch" alone does not determine severity.
- When a stop is the purpose of the step, concise inline red text is preferred
  over a large warning card.
- General fallback and recovery information belongs in compact help tiles.
- Help must not look like another required step.
- A task guide must not hide a step-specific stop condition in its help row.

## Guide References

- Show family marker, guide code, and the full registered guide name by default.
- Use the referenced guide's family color.
- A guide handoff inside a help tile uses the same family marker, full code,
  registered name, and family color; it is not left as unstyled body text.
- A prerequisite such as `Ingelogd` names its guide, for example `Ingelogd (AC-01 Login)`.
- List only guides the user may actually need before, during, or directly after the task.
- Footer references support zero through five real guides over at most two rows.

## QR Policy

- A decorative QR pattern is allowed only in clearly labelled development proofs.
- `Third-party approved` output must contain a real maintained destination or omit the QR.
- The digital QR is a helper and may not be required to complete the printed task.
- Final destination, per-guide versus index, remains an open decision.

## Application Sources

- The user-facing application URL remains `https://snipe.inbit/`.
- `https://dev.inbit/` is an approved controlled capture environment for missing evidence and state-changing workflows on this device.
- Never present `dev.inbit` as the URL an operator should use. Printed guide text continues to point to the live application.
- Record the exact capture environment in the internal screenshot catalog and generated summary. A printed footer may describe it as a controlled test capture without exposing the development URL.
- Prefer an existing canonical screenshot over recapturing an identical state from either environment.
- Captures must avoid credentials and unrelated personal data.
- If development and live interfaces differ materially, the development capture remains draft evidence and must be replaced or explicitly accepted before `Third-party approved`.

## Review Package

Each generated version should provide:

- PDF proof;
- full-page PNG proof;
- editable SVG/HTML source;
- prepared source crops;
- guide specification with visual manifest;
- generation summary and known gaps.

The review package also records the guide's registry metadata, recipe and step
patterns, previous version, and complete affected-guide list for a shared
change.

## QA Gate

Before a guide can be `Internal review candidate`:

- its reasoning brief establishes why the content belongs and how the reader
  reaches the intended outcome;
- the brief reconciles current requirements, desired targets and retired
  approaches with the baseline/specification, using the relevant decisions;
- the complete rendered guide follows those current requirements and does not
  reintroduce superseded points; remaining desired/open gaps are recorded;
- a read-back of the rendered page checks implied sequence, choices,
  completion and plausible wrong readings, with the findings recorded;
- revisions account for previously useful baseline support and recognition
  size; a necessary regression or tradeoff is explicit;
- PDF page count matches the declared one-page, two-sided, or multi-page guide;
- no unintended blank pages exist;
- no missing-evidence placeholders exist;
- no stale development URLs appear;
- steps, screenshot labels, and captions correspond;
- screenshots let the reader locate the pictured screen/detail and identify
  the named target or complete check at A4 size; each focus mark resolves a
  remaining search or distinction rather than repeating the crop's emphasis;
- genuine stop conditions and recoverable warnings use the correct hierarchy
  and are attached to the relevant steps, with explicit triggers, recovery and
  an unaffected normal route; routine checks are not styled as incidents;
- `Klaar als` describes the visible end state;
- related-guide references point to real or explicitly planned guides;
- shared component geometry checks report no badge-center or reference-overflow errors;
- any printed QR is real and scannable;
- the guide is reviewed at actual A4 size;
- the exact version and review state are recorded in `decisions.md`.

Third-party approval is a later exact-version decision. Until that review is
recorded, an internally accepted guide remains an `Internal review candidate`.
Record integrity checks, editorial judgment, observed reader trials and exact
acceptance separately. A script's `passed` result does not certify the other
three. See the [method review](reviews/authoring-method-repair-2026-10-06.md)
for how the reasoning handles both known failures and legitimate countercases.

## Feedback Records

Classify each review correction as global, family, guide, or version-specific.
Record version-specific changes under `reviews/`; promote reusable corrections
to [components.md](components.md), the shared module, and regression tests. This
includes small alignment, spacing, focus-target, reference, and wording nudges,
not only structural redesigns.
