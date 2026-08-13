# Operator Guide Planning

Current status (2026-07-21): historical planning source. Continue from `docs/manuals/operator-guides/README.md`; it contains the current guide register, production order, and links to active specifications. Retain this file for the broader catalog and decision history, but do not use it as the active status dashboard.

This document tracks brainstorming, draft guide ideas, tentative decisions, and open investigation for the laminated operator manuals.

Important: almost everything in this document is still open and should not be treated as foundational project truth. Future agents should treat guide codes, color choices, guide names, grouping, wording, and workflow recommendations as brainstorming unless a line is explicitly marked `Final`.

The current status is planning-only: guide scope, names, screenshots, and final wording are not locked yet.

## Goal

Create simple, referenceable A4 guides for Snipe-IT users who need to perform refurbishing, asset entry, component handling, user management, work order handling, and catalog/workflow configuration.

Target format:

- A4 portrait.
- Prefer one side per task.
- Maximum one double-sided A4 when the task cannot be made smaller.
- Multiple short guides may share one page when they naturally belong together, for example login plus scan asset.
- Designed for lamination and use by people with little or no system knowledge.

## Inputs Reviewed

- `C:\Users\Gebruiker\Downloads\refurbisher steps.pdf`
  - One-page A4 Affinity PDF.
  - Large numbered step blocks.
  - Thick dividers.
  - Screenshot placeholders.
  - Side notes and help callouts.
- `PROGRESS.md` and `docs/fork-notes.md`
  - Current fork concepts include workflow profiles/items, component hierarchy, QR labels, component tags, asset lifecycle statuses, sale-transition permission, Dutch operational labels, and work orders.

## Current Brainstorming Direction

### Referenceable Guide IDs

Every reusable guide gets a short code, color, and icon name. The code is the primary reference because it still works in black and white, over the phone, and when colors are copied badly.

Use guide references in other guides only when the user may need to switch context. Do not color every repeated word on a page.

Example:

```text
Complete [key icon] AC-01 Login first, then use [scan icon] SC-01 Scan Asset.
```

### Draft Code Families

| Family | Area | Example |
| --- | --- | --- |
| `AC` | Access and login | `AC-01 Login` |
| `SC` | Scan and search | `SC-01 Scan Asset` |
| `AST` | Assets | `AST-02 Existing Asset Refurbishment` |
| `CMP` | Components | `CMP-04 Remove Component to Tray` |
| `WF` | Workflows and tests | `WF-01 Start Workflow` |
| `USR` | Users and rights | `USR-01 Add User` |
| `WO` | Work orders | `WO-01 Start Work Order` |
| `CFG` | Admin/catalog setup | `CFG-01 System Concept Map` |
| `HELP` | Troubleshooting | `HELP-01 Common Problems` |

### Draft Color And Icon Rules

The final palette is still open. Current direction: the draft color/icon system is good enough for planning and should be kept unless later testing shows a readability or accessibility issue.

| Family | Color Role | Icon Name |
| --- | --- | --- |
| `AC` | Blue | key |
| `SC` | Teal | scan or qr-code |
| `AST` | Green | laptop or package |
| `CMP` | Amber | component or puzzle |
| `WF` | Orange | checklist or play |
| `USR` | Navy | user |
| `WO` | Gray | clipboard |
| `CFG` | Purple | settings |
| `HELP` | Red | alert-circle |

Do not rely on color alone. Always show the code and icon name or icon mark.

### Draft Guide Reference Appearance

Current direction: guide references should appear as a small visual marker with icon, code, and label, for example:

```text
[key icon] AC-01 Login
```

This may be rendered as an inline chip, a small callout, or a margin marker depending on the page layout. Keep the code visible even when color is present.

### Draft Language Direction

Current direction: floor/refurbisher guides should be Dutch for the first pass. Admin/catalog guides can remain open for now; they may be Dutch-only, bilingual, or English-heavy depending on who will maintain the catalog.

### Page Structure

Each guide block should use the same structure:

```text
[icon] CODE Title
Role: Refurbisher / Senior Refurbisher / Supervisor / Admin
Needed: phone, device, QR sticker printer, account, etc.
Stop and ask if: clear escalation triggers

1. Action
2. Action
3. Action
4. Action
5. Action

Finished when: exact expected end state
Related guides: CODE, CODE
```

For laminated guides, keep each block to 5-7 actions where possible. Use fewer, larger screenshot crops instead of many tiny screenshots.

## Draft Guide Index

### Floor / Refurbisher Guides

| Code | Guide | Notes |
| --- | --- | --- |
| `AC-01` | Login | May share a page with scan/search. |
| `SC-01` | Scan Asset | Covers QR scan and manual search fallback. |
| `AST-01` | Open Existing Asset | Confirm correct hardware before acting. |
| `AST-02` | Existing Asset Refurbishment | Main refurb flow from open asset to completed workflow. |
| `AST-03` | Add Existing Device and Print Sticker | For known hardware entering the system. |
| `AST-04` | Add New Device From Scratch | For unknown/new model-number cases. |
| `AST-05` | Change Asset Status or Attribute | Needs careful permission and sale-readiness wording. |
| `AST-06` | Remove, Retire, or Sell Asset | Needs investigation into final lifecycle wording and permission boundary. |
| `WF-01` | Start Workflow | Select workflow profile and optional extra checks. |
| `WF-02` | Complete Workflow | Pass/fail or done/not-done result cards, notes, and photos. |
| `CMP-01` | Add Existing Component | Tray/stock component to asset. |
| `CMP-02` | Create and Add New Component | Definition-backed new physical component. |
| `CMP-03` | Add Custom Component | One-off component that should not become catalog definition yet. |
| `CMP-04` | Remove Component to Tray | Includes locked serial capture/edit behavior. |
| `HELP-01` | Common Problems | No QR, wrong asset, duplicate serial, printer issue, permission denied, no workflow items. |

### Supervisor / User Management Guides

| Code | Guide | Notes |
| --- | --- | --- |
| `USR-01` | Add User | Create account and initial password/reset flow. |
| `USR-02` | Disable or Remove User | Clarify disable versus delete. |
| `USR-03` | Set User Rights | Explain groups and role boundaries. |
| `USR-04` | Refurbisher Account Recovery | Password forgotten and supervisor escalation. |

### Work Order Guides

Deferred for this pass. Keep these candidate guides in the planning document, but do not prioritize them for the first laminated set because work orders are not expected to be active in the next pass.

| Code | Guide | Notes |
| --- | --- | --- |
| `WO-01` | Start Work Order | Create work order, priority, visibility. |
| `WO-02` | Add Device to Work Order | Add asset/device and snapshot fields. |
| `WO-03` | Add Work Order Task | Device-specific or general task. |
| `WO-04` | Customer-Visible Work Order View | Needs investigation into what users/customers should see. |

### Admin / Catalog Guides

These should be separate from the floor guides. They are for people who manage hardware definitions, models, component definitions, attributes, and workflow setup.

| Code | Guide | Notes |
| --- | --- | --- |
| `CFG-01` | System Concept Map | Asset -> model -> attributes -> expected components -> actual components -> workflows. |
| `CFG-02` | Add or Edit Attribute Definition | Specs such as RAM size, storage type, screen size, connector type. |
| `CFG-03` | Add Asset Model | Product family, for example HP ProBook 450 G8. |
| `CFG-04` | Add Model / Variant | Exact hardware variant, for example i5 / 8GB / 256GB. Operator-facing terminology should avoid "model number" where possible. |
| `CFG-05` | Add Manual Model Specs | Only for values that are not better represented by expected components. |
| `CFG-06` | Add Component Definition | Reusable component catalog row. |
| `CFG-07` | Add Expected Components to Model | What a model/variant should normally contain. |
| `CFG-08` | Add Expected Subcomponents | Logic board/motherboard child components and integrated parts. |
| `CFG-09` | Add Workflow Item | One reusable check or task. |
| `CFG-10` | Add Workflow Profile | Checklist made from workflow items. |
| `CFG-11` | Workflow Applicability | How workflow items apply by asset category, component category, component definition, always-on, or manual extra item. |
| `CFG-12` | Test a New Catalog Setup | Create/open a sample asset and verify specs, expected components, and workflow items. |

## Tentative Concept Notes

### Attribute Versus Component Definition

Use an attribute when the system needs a reusable field or spec value.

Use a component definition when the part/spec belongs to a reusable hardware part, especially when:

- it should appear as an expected component;
- it can be installed, removed, tracked, or replaced;
- workflow checks should depend on its presence;
- it contributes to calculated asset/model specs.

Use a custom component when the part is one-off, unknown, or not worth making reusable yet.

### Model Versus Model Number

Open terminology issue: operator-facing guides should use "Model" rather than "Model Number" where possible.

The current system still has a distinction between model/product family and model-number/variant. That distinction may need a later UI/product wording pass, but the guides should avoid teaching new floor users the phrase "model number" unless the current screen forces it.

Tentative explanation for admin/catalog users:

Model is the exact configuration or product variant the operator needs to choose for an asset.

The underlying system may still use model number or variant internally for the exact configuration that drives expected specs and expected components.

Example:

```text
Model: HP ProBook 450 G8
Model number / variant: i5-1135G7 - 8GB - 256GB
```

### Workflow Item Versus Workflow Profile

A workflow item is one reusable check or task.

A workflow profile is an ordered checklist made from workflow items, for example:

- Standard diagnostics.
- Pre-sale.
- Cleaning.
- Shipping laptop.

Workflow items can appear because:

- the item is always included;
- it applies to an asset category;
- it applies to a component category;
- it applies to a specific component definition;
- the operator manually adds it as an extra item when starting a workflow.

## Product / System Follow-Up Outside This Guide Thread

The current guide planning surfaced a broader product question: workflow visibility should likely support more than one relevant workflow, not only a base/test workflow. Earlier test status behavior showed warnings and asset-list visibility when items were not ready; future product work may need to generalize that so multiple workflows can be visible and readiness-relevant where needed.

This is not part of the current guide-planning goal. Keep it as a generic product todo for another thread before finalizing any guide language around workflow readiness.

## Investigation Needed

### UI Walkthroughs

Capture exact current screen labels, button text, and page order for:

- login and scan/open asset;
- asset create and edit;
- asset QR label print/download;
- workflow start and active workflow result cards;
- component add/install/remove-to-tray/move-to-stock/verification/destruction;
- user creation and rights/group assignment;
- work order create/show/device/task flows, deferred for this pass;
- settings pages for attributes, models, current model-number/variant screens, component definitions, workflow items, and workflow profiles.

### Permissions And Roles

Confirm which guide blocks are safe for:

- Refurbisher.
- Senior Refurbisher.
- Supervisor.
- Admin.
- Customer/account work order viewer, if applicable.

Special attention:

- sale-transition rights;
- component lifecycle rights;
- user/rights management;
- workflow catalog administration;
- asset removal, sold, archived, destroyed, and ready-for-sale transitions.

### Terminology

Current terminology direction:

- use `Asset`;
- use `Workflow`;
- use `Component`;
- use `Model` instead of `Model Number` where possible;
- `Tray` and `Storage` are acceptable terms for now.

Still decide final operator-facing terms in Dutch and English for:

- stock, if it remains distinct from storage;
- remove, retire, sell, destroy;
- work order visibility and customer-visible task labels, deferred for this pass.

### Screenshot Standards

Define:

- screenshot device size for phone-oriented guides;
- screenshot device size for desktop/admin guides;
- crop style;
- highlight style;
- Affinity export settings;
- a visual-purpose plan per guide instead of a fixed screenshot count.

The visual-purpose plan should say which visuals are needed because they help the operator:

- find the next control;
- recognize the correct screen;
- choose between the main path and an allowed fallback;
- locate a physical label, QR code, port, or part;
- verify that the device/screen is correct before continuing;
- stop and ask at the first risky moment.

Do not set a required screenshot quantity up front. The amount of visual material should come from the task. If the needed visuals do not fit at readable size, split the guide or use the back side instead of shrinking everything.

Research-review focus: before guide drafting/layout begins, ask a research model to review the plan and example images for realistic print readability. Include questions about minimum screenshot sizes, crop ratios, text size, contrast, page density, A4 lamination constraints, black-and-white fallback, color-blind safety, QR-code size, and whether the example design direction is suitable.

### Physical Guide Index

Decide where the master index lives:

- one laminated index sheet;
- back side of each guide;
- small footer strip on every page;
- QR link to a digital latest-version index.

Current direction: include QR links to the latest digital guide or guide index. This requires a small upkeep process so laminated guides can be checked against the current digital version.

### Printing And Versioning

Decide:

- document version format;
- date placement;
- whether old laminated sheets should be physically retired;
- whether each guide should include a small "last checked with app version/date" line.
- how guide QR links resolve: per-guide latest URL, master index URL, or both.

### Affinity Production Notes

Current development block spec: `docs/manuals/affinity-development-blocks-2026-06-25.md`.
Current feedback replan: `docs/manuals/operator-guide-feedback-replan-2026-06-30.md`.
Current clean-build plan: `docs/manuals/operator-guide-clean-design-plan-2026-06-30.md`.

When the plan is ready for layout work, the assistant can help with:

- Affinity master-page recommendations;
- page grid, margins, styles, and reusable guide-block specifications;
- screenshot checklist and crop instructions;
- SVG/PDF/PNG assets that Affinity can place or import;
- printable PDF exports for review.

Native Affinity `.afpub` or `.afdesign` files are proprietary and should not be assumed directly creatable by automation. Use Affinity-friendly source assets and layout specifications unless a reliable Affinity export workflow is proven later.

June 30 correction: the failed native `operator-guides-ac01-ast01-native-affinity-v2-feedback-pass.af` should not be used as a base. The next Affinity attempt should be plan-first, clean-document-first, with exported proof inspection after each small pass.

### Research Review Package

Before producing real guide drafts, prepare a package for a research model / research review. Include:

- the current guide-planning document;
- the initial rough Affinity design sketch;
- a rendered/exported image of the rough design, if available;
- a photo of the actual printed page so print size, contrast, glare, and screenshot readability can be judged realistically;
- any rough guide mockups or screenshot crops available at that point;
- target output: laminated A4, preferably one side, maximum double-sided;
- target viewing context: operators standing at a bench, using phones/desktops, sometimes under imperfect lighting;
- print assumptions: real A4 print, lamination glare, possible black-and-white copies, and normal office-printer limits;
- specific review questions for design hierarchy, minimum text sizes, screenshot visibility, QR-code size, color use, icon/code recognizability, and whether multiple short guides on one page remains readable.

Do not treat the research review as a final authority. Use it to challenge the plan, surface missed best practices, and identify layout risks before investing time in Affinity production.

## Proposed Planning Phases

1. Lock the guide ID system and color/icon palette.
2. Prepare the research review package with the planning doc, example PDF/images, print constraints, and review questions.
3. Send the package through a research model / research review to compare against existing training/manual best practices and realistic printing constraints.
4. Walk through the current app with the real target roles.
5. Define a visual-purpose plan for each first-batch guide.
6. Capture only the screenshots/photos needed by those visual plans.
7. Draft the first Affinity template.
8. Build the first laminated pilot set.
9. Test with a person who does not know the system.
10. Revise wording and screenshot crops.
11. Expand into admin/catalog/workflow guides.

## First Pilot Batch

Recommended first pilot set:

- `AC-01 Login`
- `SC-01 Scan Asset`
- `AST-01 Open Existing Asset`
- `AST-02 Existing Asset Refurbishment`
- `AST-03 Add Existing Device and Print Sticker`
- `WF-01 Start Workflow`
- `WF-02 Complete Workflow`
- `CMP-04 Remove Component to Tray`
- `HELP-01 Common Problems`

These cover the most common floor flow and expose the biggest layout problems early.

## Decision Log

Decision statuses matter. `Draft accepted` means "reasonable for now, keep discussing", not final approval. Only entries marked `Final` should be treated as locked.

| Date | Decision | Status |
| --- | --- | --- |
| 2026-06-23 | Use laminated A4 guides, preferably one side, maximum double-sided. | Draft accepted |
| 2026-06-23 | Allow multiple short guides on one A4 page when related. | Draft accepted |
| 2026-06-23 | Use color, icon, and guide code for referenceable guide terms. | Draft accepted |
| 2026-06-23 | Split floor/refurbisher guides from admin/catalog guides. | Draft accepted |
| 2026-06-23 | Track guide planning in this document instead of only chat/session notes. | Accepted |
| 2026-06-25 | Keep the current draft color/icon system for now. | Draft accepted |
| 2026-06-25 | Use Dutch for the first floor/refurbisher guide pass. | Draft accepted |
| 2026-06-25 | Add QR links to latest digital guides or guide index. | Draft accepted |
| 2026-06-25 | Defer work order guides from the first pass. | Draft accepted |
| 2026-06-25 | Use Asset, Workflow, Component, Model, Tray, and Storage as current operator-facing terms. | Draft accepted |
| 2026-06-25 | Run the guide plan through a research-model/best-practice review before treating it as ready. | Draft accepted |
| 2026-06-30 | Treat the June 25 generated PDFs as proof artifacts only; next drafts should use a step-first layout with mid-sized per-step screenshot crops. | Draft accepted |
| 2026-06-30 | Move most stop/ask warnings into the first relevant step instead of keeping every warning in a large top strip. | Draft accepted |
| 2026-06-30 | Keep related-guide chips, version/source footer, and a larger bottom-right latest-guide QR. | Draft accepted |
| 2026-06-30 | Prefer a compact `AC-01 Login` block only if it fits and stays readable; this is not a hard page-size requirement. | Draft accepted |
| 2026-06-30 | Do not require a fixed screenshot count. Each visual must have a job: primary path, alternative path, physical context, verification, or stop support. | Draft accepted |

## Open Questions

- Should admin/catalog guides be Dutch-only, bilingual, or English-heavy?
- Should the visual guide code appear as a small chip, a left margin marker, or both in the final Affinity layout?
- Should guide QR codes link to a per-guide latest page, a master index, or both?
- Can `AC-01 Login` share a page with `SC-01 Scan Asset` while staying readable, or should it remain standalone?
- Which role names should appear exactly as printed: Refurbisher, Senior Refurbisher, Supervisor, Admin?
- What are the final safe escalation rules for removing/selling/destroying assets?
- When work orders become active, which work order fields matter to floor users versus supervisors?
- Which catalog/admin guides require a worked example using a real device model?
- How should the product/UI later represent multiple relevant workflows in readiness warnings and asset-list visibility?
- How should final guides describe serial-number search, given that it is wanted but not supported yet?
- Which first-batch guides need physical photos rather than app screenshots, especially for QR label locations and device-specific controls?
