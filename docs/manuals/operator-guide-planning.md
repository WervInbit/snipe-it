# Operator Guide Planning

This document tracks decisions, draft guide ideas, and open investigation for the laminated operator manuals. It is intentionally a working document: guide scope, names, screenshots, and final wording are not locked yet.

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

## Current Design Decisions

### Referencable Guide IDs

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

The final palette is still open. For now:

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

| Code | Guide | Notes |
| --- | --- | --- |
| `WO-01` | Start Work Order | Create work order, priority, visibility. |
| `WO-02` | Add Device to Work Order | Add asset/device and snapshot fields. |
| `WO-03` | Add Work Order Task | Device-specific or general task. |
| `WO-04` | Customer-Visible Work Order View | Needs investigation into what users/customers should see. |

### Admin / Catalog Guides

These should be separate from the floor guides. They are for people who manage hardware definitions, model numbers, component definitions, attributes, and workflow setup.

| Code | Guide | Notes |
| --- | --- | --- |
| `CFG-01` | System Concept Map | Asset -> model -> model number -> attributes -> expected components -> actual components -> workflows. |
| `CFG-02` | Add or Edit Attribute Definition | Specs such as RAM size, storage type, screen size, connector type. |
| `CFG-03` | Add Asset Model | Product family, for example HP ProBook 450 G8. |
| `CFG-04` | Add Model Number / Variant | Exact hardware variant, for example i5 / 8GB / 256GB. |
| `CFG-05` | Add Manual Model Specs | Only for values that are not better represented by expected components. |
| `CFG-06` | Add Component Definition | Reusable component catalog row. |
| `CFG-07` | Add Expected Components to Model Number | What a model-number variant should normally contain. |
| `CFG-08` | Add Expected Subcomponents | Logic board/motherboard child components and integrated parts. |
| `CFG-09` | Add Workflow Item | One reusable check or task. |
| `CFG-10` | Add Workflow Profile | Checklist made from workflow items. |
| `CFG-11` | Workflow Applicability | How workflow items apply by asset category, component category, component definition, always-on, or manual extra item. |
| `CFG-12` | Test a New Catalog Setup | Create/open a sample asset and verify specs, expected components, and workflow items. |

## Important Concept Decisions

### Attribute Versus Component Definition

Use an attribute when the system needs a reusable field or spec value.

Use a component definition when the part/spec belongs to a reusable hardware part, especially when:

- it should appear as an expected component;
- it can be installed, removed, tracked, or replaced;
- workflow checks should depend on its presence;
- it contributes to calculated asset/model specs.

Use a custom component when the part is one-off, unknown, or not worth making reusable yet.

### Model Versus Model Number

Model is the product family.

Model number or variant is the exact configuration that drives expected specs and expected components.

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

## Investigation Needed

### UI Walkthroughs

Capture exact current screen labels, button text, and page order for:

- login and scan/open asset;
- asset create and edit;
- asset QR label print/download;
- workflow start and active workflow result cards;
- component add/install/remove-to-tray/move-to-stock/verification/destruction;
- user creation and rights/group assignment;
- work order create/show/device/task flows;
- settings pages for attributes, models, model numbers, component definitions, workflow items, and workflow profiles.

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

Decide final operator-facing terms in Dutch and English for:

- asset versus device;
- workflow versus test;
- component versus part;
- model number versus variant;
- tray, stock, storage;
- remove, retire, sell, destroy;
- work order visibility and customer-visible task labels.

### Screenshot Standards

Define:

- screenshot device size for phone-oriented guides;
- screenshot device size for desktop/admin guides;
- crop style;
- highlight style;
- Affinity export settings;
- maximum screenshot count per A4 side.

### Physical Guide Index

Decide where the master index lives:

- one laminated index sheet;
- back side of each guide;
- small footer strip on every page;
- QR link to a digital latest-version index.

### Printing And Versioning

Decide:

- document version format;
- date placement;
- whether old laminated sheets should be physically retired;
- whether each guide should include a small "last checked with app version/date" line.

## Proposed Planning Phases

1. Lock the guide ID system and color/icon palette.
2. Walk through the current app with the real target roles.
3. Capture screenshots for the first floor guide batch.
4. Draft the first Affinity template.
5. Build the first laminated pilot set.
6. Test with a person who does not know the system.
7. Revise wording and screenshot crops.
8. Expand into admin/catalog/workflow guides.

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

| Date | Decision | Status |
| --- | --- | --- |
| 2026-06-23 | Use laminated A4 guides, preferably one side, maximum double-sided. | Draft accepted |
| 2026-06-23 | Allow multiple short guides on one A4 page when related. | Draft accepted |
| 2026-06-23 | Use color, icon, and guide code for referencable guide terms. | Draft accepted |
| 2026-06-23 | Split floor/refurbisher guides from admin/catalog guides. | Draft accepted |
| 2026-06-23 | Track guide planning in this document instead of only chat/session notes. | Accepted |

## Open Questions

- Should guide text be Dutch-only for floor users, or bilingual for some admin guides?
- Should the visual guide code appear as a small chip, a left margin marker, or both?
- Should guide pages include QR codes to a digital latest version?
- Which role names should appear exactly as printed: Refurbisher, Senior Refurbisher, Supervisor, Admin?
- What are the final safe escalation rules for removing/selling/destroying assets?
- Which work order fields matter to floor users versus supervisors?
- Which catalog/admin guides require a worked example using a real device model?
