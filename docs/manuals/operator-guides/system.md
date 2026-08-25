# Operator Guide System

Status: current shared content, production, and QA rules for generated guides.

## Document Precedence

Use the narrowest current document:

1. A guide specification controls that guide's scope, steps, evidence, and warnings.
2. [registry.md](registry.md) controls the current version, layout, generator,
   page model, artifact root, and review state.
3. [components.md](components.md) controls shared visual components and feedback promotion.
4. [layouts.md](layouts.md) controls reusable page and step structures.
5. This system document controls shared content and production behavior.
6. [maintenance.md](maintenance.md) controls change impact, versioning, and
   multi-guide propagation after feedback or application changes.
7. The decision log controls accepted and unresolved cross-guide decisions.
8. The project index controls current status and working order.
9. [HANDOFF.md](HANDOFF.md) controls the continuation checklist, creation-stage
   snapshot, and environment path mapping. It does not override sources above.
10. Older files under `docs/manuals/` are supporting history only.

## Production Model

```text
Specification -> Evidence ready -> Working draft -> Internal review candidate -> Third-party approved
```

Generated guides are the active production format. Affinity is deferred until every guide in the active set is confirmed or the user explicitly gives a green light. Do not spend Computer Use time on Affinity preparation during generated-guide production.

Every active guide declares a named layout recipe, page model, current version,
and generator. Accepted artifacts remain immutable; visible changes create new
versions according to [maintenance.md](maintenance.md).

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
- Choice wording should make equivalence clear without adding a large decorative `OF` block when the layout already communicates the choice.
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
- Keep enough surrounding context for users to recognize the application or physical device.
- Crop tighter only when the target remains understandable.
- Reuse the same canonical screenshot when multiple guides show the same application state. Guide-specific crops and callouts may differ, but the underlying evidence should remain identical.
- Register reusable evidence in [screenshots.md](screenshots.md) and reference its stable source ID from guide specifications and generators.
- Capture a replacement only when the state is materially different, the existing evidence is stale or unreadable, or a different device context is required.
- Red circles/rectangles are optional target annotations, not default decoration.
- Derive focus marks from measured source-pixel target bounds and add symmetric
  padding on all sides.
- Use the existing mobile camera/QR screenshot for scanner-camera evidence instead of the laptop camera.
- Do not fake missing application states. A development placeholder cannot survive into an approved base.

## Stop And Help

- Reserve a red `STOP` message for a genuine halt condition: continuing would
  create a meaningful safety, identity, irreversible-state, or data-integrity
  risk and the operator must not continue without correction or escalation.
- Use an amber inline warning for recoverable validation, duplicate, missing
  catalog, or incomplete-work conditions. Do not style every correction as a
  stop merely because the operator must resolve it before saving.
- Attach a real step-specific `STOP` to the first step where the risk matters.
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

- PDF page count matches the intended one- or two-sided guide;
- no unintended blank pages exist;
- no missing-evidence placeholders exist;
- no stale development URLs appear;
- steps, screenshot labels, and captions correspond;
- genuine stop conditions and recoverable warnings use the correct hierarchy
  and are attached to the relevant steps;
- `Klaar als` describes the visible end state;
- related-guide references point to real or explicitly planned guides;
- shared component geometry checks report no badge-center or reference-overflow errors;
- any printed QR is real and scannable;
- the guide is reviewed at actual A4 size;
- the exact version and review state are recorded in `decisions.md`.

Third-party approval is a later exact-version decision. Until that review is
recorded, an internally accepted guide remains an `Internal review candidate`.

## Feedback Records

Classify each review correction as global, family, guide, or version-specific.
Record version-specific changes under `reviews/`; promote reusable corrections
to [components.md](components.md), the shared module, and regression tests. This
includes small alignment, spacing, focus-target, reference, and wording nudges,
not only structural redesigns.
