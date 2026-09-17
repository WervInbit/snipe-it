# Refurbisher Review Follow-Ups

Date: 2026-09-17

Status: consolidated implementation backlog. No application, permission, live
configuration, workflow, status, or guide PDF change is authorized by this
document alone.

## Purpose

This document turns the September refurbisher review notes into one backlog for
a later implementation session. Repeated comments are combined, work that can
be performed through the existing administration UI is separated from code
work, and unresolved operational choices are called out before they can be
encoded incorrectly.

The current account-icon absence is caused by the dedicated Refurbisher header,
not by the password route. The current application also already has protected
Ready-for-Sale/Sold transitions and workflow progression. The work below should
extend those foundations rather than add a second permission or warning system.

## Consolidated Outcomes

- Refurbishers can find permitted account actions without restoring the full
  administrator navigation.
- Senior staff who may create devices get a clear dashboard creation icon, while
  ordinary refurbishers keep a simple device list.
- Users see only relevant status destinations. Entering or leaving protected
  lifecycle states is permission-gated, confirmed, reasoned, and audited on all
  write paths.
- Quality is read-only unless a dedicated quality permission is granted.
- Workflow execution ends with an obvious `Klaar` action and the workflow
  overview explains the next action after the final available workflow.
- Device identity is visible before workflow summaries, and recently affected
  devices are recoverable from the dashboard.
- Every attribute is discoverable and manually assignable from the backend;
  hard-coded and partially hard-coded catalogue rules cannot silently remove it
  from model-number configuration.
- Creation, scanning, login, password, diagnostic, and Dutch wording are simpler
  and consistent with revised guides.

## Decisions Needed First

- [ ] **D-01 Account access:** confirm that refurbishers must be able to change
  their own local password. The detailed review calls the missing account icon a
  defect, but the summary also says its absence is expected. Recommended:
  retain the compact refurbisher header and add a small account menu containing
  `Wachtwoord wijzigen` and `Uitloggen`.
- [ ] **D-02 Status vocabulary:** approve the exact Dutch names and descriptions.
  Candidate active-work label: `Wordt verwerkt`. Decide whether `QA Hold`
  becomes `Kwaliteitscontrole`, `Wacht op kwaliteitscontrole`, or the previously
  proposed `Testen voltooid`. Do not use one label for both blocked work and
  completed work awaiting review unless that is deliberate.
- [ ] **D-03 Status matrix:** provide the allowed from-status/to-status matrix
  for Refurbisher, Senior Refurbisher, Supervisor, and Admin. Identify every
  state that is locked after entry and every exceptional exit permission.
- [ ] **D-04 Destructive states:** identify what `destroyed` means in the current
  status set. There is no seeded status named Destroyed. Confirm whether this
  means `Defect / Onderdelen`, `Gearchiveerd`, a new Destroyed status, or all of
  them. Sold and destroyed-state exits should require high rights, a reason,
  and asset-tag entry or QR scan as verification.
- [ ] **D-05 Completion destination:** resolve the two review requests for
  `Klaar`: return to the device Tests/Workflows tab versus automatically return
  to the main dashboard. Recommended: `Klaar` returns to the device workflow
  overview; when all workflows are complete that overview offers a prominent
  next-action/dashboard button. Do not auto-navigate while a save is pending.
- [ ] **D-06 Paper order:** supply the current paper Standard Diagnostics list
  and exact order. It is not present in the repository, so the seed/configured
  order must not be guessed.
- [ ] **D-07 Diagnostic meanings:** define `Programmeerbare toets`, its device
  categories, instructions, and pass criteria. Confirm that `Geschiedenis
  wissen` means clearing history on the physical device and not deleting Snipe-IT
  workflow audit history.
- [ ] **D-08 CPU naming:** clarify the request to rename `iGPU` to `CPU-test`.
  Standard Diagnostics already contains `Processor` (`cpu`) and a separate
  `iGPU` test. Decide whether iGPU is removed, merged into CPU, or renamed to a
  different Dutch GPU term; do not leave two indistinguishable CPU tests.
- [ ] **D-09 Cleaning and repair:** supply the required expanded cleaning steps
  and define which hardware-repair failures, locations, notes, and photos must
  be shown to the next user.
- [ ] **D-10 Recent devices:** choose count and meaning. Recommended: the five
  most recently viewed or changed devices for the signed-in user, newest first,
  with the most recent device highlighted as `Verdergaan`.
- [ ] **D-11 Password shape:** approve a minimum length for lowercase letters
  and numbers. It must remain compatible with the security policy and should
  avoid ambiguous characters. Recommended minimum: 10 characters.
- [ ] **D-12 Scan wording:** complete the truncated review sentence `Scan QR code
  moet geconfigureerd zijn zodat gebruikers niet ...` and approve whether the
  dashboard tile should read `Scan ter controle` or another phrase.

## Manual Administration Work

These actions use existing application controls. Record current values before a
live change and test with one representative account/device. Do not run seeders
as a substitute for controlled administration.

### Safe After Approval

- [ ] **M-01 Senior creation grant:** in `Personen > Groepen`, grant
  `assets.create` only to the groups approved to create devices. The existing
  production group seeder preserves administrator-added grants, but the default
  should also be codified for new installations if Senior Refurbisher is meant
  to receive it everywhere.
- [ ] **M-02 Status descriptions:** in `Instellingen > Statuslabels`, update the
  notes, color, default choice, and navigation visibility after D-02/D-03 are
  approved. Notes can explain normal ownership and the next action.
- [ ] **M-03 Workflow trial:** in `Instellingen > Workflow Items` and
  `Workflow Profiles`, trial approved item wording, instructions, category
  applicability, required flags, and profile composition on development.
  Programmable-key, device-history, and expanded cleaning items can be
  prototyped here before their canonical seed definitions are added.
- [ ] **M-04 Workflow order trial:** reorder a development copy of Standard
  Diagnostics to match the supplied paper after D-06. A later foundation-seeder
  run can restore seeded names and order, so approved values still require the
  code work in W-04.
- [ ] **M-05 Password policy:** in `Instellingen > Beveiliging`, set the approved
  minimum and required character classes. The existing form can require letters
  and numbers and can stop requiring symbols/case differences. This does not by
  itself fix the administrator password generator.
- [ ] **M-06 Login note:** simplify or remove the configurable login note in
  `Instellingen > Algemeen`. The fixed heading, Remember Me control, and mobile
  layout require code.
- [ ] **M-07 Scanner environment:** verify HTTPS, camera permission, preferred
  rear-camera selection, and representative asset/component labels on each
  supported phone. Record the missing D-12 behavior before changing scanner
  code.

### Coordinated With Code

- [ ] **M-08 Status rename:** rename live `Being Processed`/`QA Hold` only in the
  same release that removes English-name coupling from dashboard filters and
  applies consistent translated display labels. The current dashboard locates
  these non-lifecycle statuses by English database name.
- [ ] **M-09 Production workflow promotion:** reproduce approved development
  workflow items/order in production only after seed definitions, rollback
  notes, and guide wording match. Preserve existing run history.

## Code Backlog

### A. Account, Dashboard, And Continuation

- [ ] **A-01 Compact account access (high):** add an account icon/menu to the
  Refurbisher header in `resources/views/layouts/default.blade.php`. Show the
  local-password route for non-LDAP users and logout; do not expose unrelated
  administrative navigation. Correct the duplicate `New password` confirmation
  label in `resources/views/account/change-password.blade.php`. Cover local,
  LDAP, locked-password, mobile, and keyboard-access cases.
- [ ] **A-02 Dashboard create-device action (high):** for every user authorized
  by `create` on `Asset`, show a clearly labelled dashboard icon linking to
  `hardware.create`. Give Senior Refurbisher `assets.create` in the canonical
  group seed only if D-03 approves it. Remove or de-emphasize the asset-list `+`
  for the simplified refurbisher view after the dashboard route is proven; all
  surfaces must use policy checks rather than group names.
- [ ] **A-03 Recent devices (medium):** add a permission-filtered dashboard list
  of the last few devices the signed-in user viewed or changed. Each row opens
  the device directly and shows identifier, serial/model where useful, current
  status, and recency. Decide whether tracking is session/local only or persisted
  per user; never leak devices the user can no longer view.
- [ ] **A-04 Return to device (medium):** audit refurbisher actions that currently
  return to the asset list and redirect safe single-device actions back to that
  device. Preserve explicit list/batch routes and validation-error input. This
  complements, rather than replaces, A-03.

### B. Status, QA, And Quality Rights

- [ ] **S-01 Consistent translated status display (high):** stop rendering raw
  English database names on some asset/detail/list/dashboard surfaces while
  translating only the sidebar fallback. Use one display-name projection and a
  stable identity for non-sale workflow statuses before M-08. Update Dutch
  translations, dashboard descriptions, seed defaults, filters, APIs where
  display text is returned, and affected guide evidence.
- [ ] **S-02 Relevant status choices (high):** introduce one server-side status
  transition policy based on D-03. It must independently answer whether a user
  may enter and leave each state. Use it to filter selectors and reject forged
  requests. Enforce it on detail update, full edit, create, bulk edit, API, and
  agent paths; hiding an option is not authorization.
- [ ] **S-03 Protected exit verification (high):** extend the existing
  `AssetStatusTransitionGuardService` instead of creating another warning flow.
  Leaving Sold or any D-04 destructive state requires the high-level capability,
  a non-empty reason, a server preview/fingerprint, and verification by typing
  the asset tag or scanning its QR. Cancel restores the stored selection. Save
  actor, from/to state, reason, verification method, and evaluated issues in the
  status audit event.
- [ ] **S-04 Complete warning integration (high):** finish the existing follow-up
  in `docs/plans/workflow-run-status-ux-implementation-2026-09-15.md`: full edit,
  bulk, API, and agent status writes must share the detail page's transactional
  guard rather than legacy warning/resubmit behavior.
- [ ] **S-05 QA reason (high):** when moving a device into the approved QA state,
  require a short reason/note and show the latest reason, actor, and date on the
  device page. The status description explains what QA means; the event note
  explains why this particular device entered QA. Translate all labels.
- [ ] **S-06 Dedicated quality permission (high):** add a narrowly scoped
  `assets.quality_grade.update` capability. Users without it see the current
  quality as text only. Enforce it in controllers/requests/API, not only Blade.
  Grant it only to the approved groups, audit changes, and ensure broad
  `assets.edit` no longer implicitly permits quality changes.

### C. Workflow Execution And Diagnostics

- [ ] **W-01 End-of-run `Klaar` action (high):** render the completion control
  already anticipated by `resources/js/tests-active.js`, supply its URL, and
  show it at the bottom when the user can leave the run. Wait for pending saves.
  Warn clearly about failed or incomplete required items. The recommended target
  is `hardware.show#tests`, with the Tests/Workflows tab activated.
- [ ] **W-02 All-done guidance (high):** on the device workflow overview, add a
  distinct final block when all applicable available workflows are complete.
  State that work is complete, identify the approved handoff/status/physical
  destination, and provide the D-05 next action. When work remains, highlight
  the next executable workflow and explain blockers instead of showing done.
- [ ] **W-03 Complete process visibility (high):** verify that every applicable
  workflow remains in the numbered overview, including complete, unavailable,
  and role-restricted rows. `Required workflows` on Info may remain a compact
  sale-readiness summary, but its `Open Workflows` action must lead to the full
  process. Reproduce and fix the report that completed rows after the first row
  do not turn green.
- [ ] **W-04 Canonical diagnostic content (high):** after D-06 through D-09,
  update `database/seeders/AttributeTestSeeder.php` and tests so approved names,
  instructions, applicability, required flags, and Standard Diagnostics order
  survive a foundation rerun without rewriting historical run snapshots. Include
  the approved programmable-key and device-history checks and expanded cleaning
  steps. Resolve CPU/iGPU semantics before changing labels.
- [ ] **W-05 Repair issue visibility (medium):** make failed hardware-repair
  workflow items identify what failed and where, with their latest note/photo
  evidence and a direct run/edit action when permitted. Keep sensitive history
  permission-filtered and avoid replacing evidence with a generic red badge.
- [ ] **W-06 Tests navigation (medium):** audit every `Testen uitvoeren`, Start,
  Continue, Done, and guide link. Controls intended to execute work open the
  active run; overview controls open the device Tests/Workflows tab. Add fragment
  activation tests so `#tests` does not silently land on Info.

### D. Device Creation, Identity, QR, And Scanning

- [ ] **C-01 Post-create actions (high):** reproduce the reported multiple
  pop-ups on production/dev first; the controller currently uses redirect flash
  messages rather than a dedicated creation modal. For a single successful
  creation, replace competing success notices with one focused action panel or
  modal containing a large primary `Print QR label` button and a large `Open
  device` button. Preserve error/partial-success details and define separate
  multi-create behavior. Give the QR action the requested green/red focus cue
  without relying on color alone.
- [ ] **C-02 Identity ordering (high):** on the Info tab, place Serial directly
  below the asset identifier/tag and before latest-workflow/required-workflow
  summaries. Preserve copy controls, empty-value handling, desktop/mobile order,
  and print layout.
- [ ] **C-03 Serial camera capture (medium):** implement the existing browser-
  local OCR plan in `docs/plans/inactivity-timeout-and-serial-ocr-2026-06-16.md`.
  Start with the asset-create serial field, use a still-image confirmation step,
  and preserve case/duplicate validation. Expand only after representative
  laptop labels pass mobile testing.
- [ ] **C-04 Scanner wording and routing (medium):** after D-12, replace icon-only
  or ambiguous dashboard scanner wording with the approved visible text. Verify
  camera/manual scans open the matching device directly and ambiguous serials
  still require selection. Do not weaken the existing duplicate-match guard.
- [ ] **C-05 AST-03 laptop mismatch (guide blocker):** reproduce why AST-03 step
  1B is absent for a laptop. Decide whether the application control is missing
  or the guide depicts a conditional/nonexistent field before changing either.

### E. Login And Generated Passwords

- [ ] **L-01 Mobile login (medium):** simplify fixed login wording, remove the
  Remember Me control and stop passing a persistent-login request, and make the
  unauthenticated mobile layout fill the usable viewport without unnecessary
  initial page scrolling. Preserve zoom, error visibility, password-manager
  support, software-keyboard reachability, and small-height landscape access.
- [ ] **L-02 Simple generated passwords (medium):** replace the current
  administrator generator's uppercase/lowercase/number/symbol default with the
  approved D-11 lowercase-letter-and-number format. Use a cryptographically
  secure generator, meet the active server policy, fill confirmation, display
  the unsaved generated value clearly, and retain the AC-02 private handoff.

### F. Attribute Visibility And Backend Control

- [ ] **AT-01 Complete attribute audit (high):** inventory every attribute and
  record whether its availability is database-controlled, category-filtered,
  hidden, deprecated, component-derived, migration-created, seeder-created,
  excluded by a hard-coded key list, or overwritten by a later seed run. Include
  attributes used by models, model numbers, components, assets, workflows,
  reports, imports, exports, and APIs. Ethernet is the reported example, not the
  full scope of this audit.
- [ ] **AT-02 Make all attributes discoverable (high):** the backend attribute
  catalogue and model-number specification editor must expose every attribute
  an authorized administrator may use. Do not silently omit attributes because
  they are component-backed, category-scoped, hidden by legacy policy, or listed
  in seed code. Show state/source badges and warnings where needed instead of
  making records impossible to find.
- [ ] **AT-03 Make attributes manually assignable (high):** allow an authorized
  administrator to add an attribute to a model number deliberately, including
  an attribute that can also be supplied by a component. Detect duplicate or
  conflicting sources and explain which value wins, but do not enforce the
  component route by removing the manual choice. Keep historical values intact.
- [ ] **AT-04 Remove hard-coded ownership (high):** replace hard-coded removed-
  attribute lists, implicit category suppression, and seeder-owned visibility
  decisions with explicit backend-managed state. Foundation seeders may add
  missing defaults but must not hide, deprecate, detach, rename, reorder, or
  overwrite an administrator-owned attribute configuration on rerun.
- [ ] **AT-05 Lifecycle controls (high):** provide backend controls to activate,
  deactivate, hide, show, deprecate, restore, scope, and assign attributes with
  clear permission and impact warnings. A deprecated record must remain
  inspectable, and restoration must be possible when its dependencies and key
  conflicts are valid.
- [ ] **AT-06 Regression coverage (high):** add an inventory test that fails when
  a known attribute disappears from backend discovery or manual model-number
  assignment. Cover category mismatch, hidden/deprecated state, component-
  resolved values, duplicate keys/versions, custom attributes, foundation
  reseeding, existing historical values, and permission enforcement.
- [ ] **AT-07 Asset/component specification integrity (high):** investigate the
  reported asset showing `6400 MHz` where `3200 MHz` is expected. Trace the
  displayed value to its exact model-number default, expected component,
  installed component definition/instance, child contribution, asset override,
  calculation, and formatting source before correcting data. Determine whether
  this is a mistaken entry, duplicate contribution, incorrect aggregation,
  unit/conversion problem, inherited component value, or display bug. Add clear
  value provenance to the backend, provide a permissioned and audited correction
  route for bad source values, preserve history, and add regression coverage for
  expected-versus-installed specification resolution.

## Guide Updates

Do not edit accepted PDFs in place. Implement and stabilize behavior first,
capture controlled evidence, then generate newly numbered guide versions and
run package/render verification.

- [ ] **G-01 AC-01:** simpler login text, no Remember Me, revised mobile full-
  screen evidence, and current recovery/help route.
- [ ] **G-02 AC-02:** compact refurbisher account icon, self-password route,
  corrected confirmation label, password rules, save/success state, and LDAP or
  locked-password exception.
- [ ] **G-03 SC-01:** approved `Scan ter controle` wording, camera permission,
  manual fallback, successful direct-open behavior, and D-12 clarification.
- [ ] **G-04 AST-03:** dashboard create icon and required role, laptop step-1B
  correction, serial camera/OCR confirmation, creation result, prominent QR
  printing, and direct device continuation.
- [ ] **G-05 AST-04/AST-05:** final Dutch active/QA/sale labels, QA reason,
  ownership/location, protected transition warnings, quality read-only behavior,
  and supervisor override route.
- [ ] **G-06 WF-01/WF-02:** full workflow visibility, corrected completion color,
  `Klaar`, failed-item evidence, all-done/next-action block, and return to the
  Tests/Workflows tab.
- [ ] **G-07 diagnostic guidance:** paper-approved Standard Diagnostics order,
  CPU/iGPU decision, programmable-key check, device-history check, expanded
  cleaning steps, and repair-failure detail. Decide whether this belongs in
  WF-02 or a new task guide before assigning a code.
- [ ] **G-08 USR guides:** if role grants or new status/quality permissions are
  added, update USR-02/USR-05 rights guidance and permission evidence.
- [ ] **G-09 CAT-02/CAT-03/CAT-04/CAT-05:** document the complete attribute
  catalogue, visibility/lifecycle controls, manual model-number assignment,
  component/manual conflict warnings, and the rule that backend choices survive
  foundation reseeding.

## Suggested Delivery Slices

1. Approve D-01 through D-12 and perform development-only M-01 through M-07
   trials. Capture the paper diagnostic order and current post-create behavior.
2. Implement A-01/A-02, C-01/C-02, and focused navigation tests. These are
   visible usability wins with limited domain change.
3. Implement S-01 through S-06 as one authorization/audit design. Do not deploy
   partial UI-only status restrictions.
4. Implement W-01 through W-06 and C-03/C-04, then run mobile and interrupted-
   save workflow trials.
5. Complete AT-01 and AT-07 before changing catalogue behavior or correcting
   affected records, then implement AT-02 through AT-06 as one backend ownership
   and compatibility change.
6. Implement L-01/L-02 and final production configuration changes.
7. Produce G-01 through G-09 as new guide versions after application and live
   configuration behavior are final.

## Minimum Verification

- Permission matrix tests for every role and status entry/exit, including direct
  requests, create, full edit, detail, bulk, API, and agent paths.
- Quality read/write tests with and without the dedicated permission.
- Status-preview fingerprint, typed/scanned verification, stale-state, cancel,
  audit-reason, and protected-exit tests.
- Workflow pending-save, incomplete/failure warning, Done destination, all-done,
  all-profile visibility, color-state, and repair-evidence tests.
- Dashboard creation/recent-device authorization and no-leakage tests.
- Single/multiple/partial asset-create result tests and QR print failure handling.
- Complete attribute inventory, discovery, manual assignment, conflict,
  lifecycle, permission, historical-data, and reseed-preservation tests.
- Model/expected-component/installed-component/asset-override provenance and
  specification resolution tests, including the reported 6400/3200 MHz case.
- Dutch rendering checks for status, QA, scanner, login, and workflow surfaces.
- Mobile browser checks for compact account access, dashboard, create/QR, serial
  camera, active workflow, Info ordering, scanner, and login with keyboard open.
- Newly numbered guide evidence, geometry, PDF, checksum, and package checks.

## Existing Foundations

- `docs/plans/workflow-run-status-ux-implementation-2026-09-15.md` defines the
  current workflow progression and protected sale-transition architecture.
- `docs/plans/inactivity-timeout-and-serial-ocr-2026-06-16.md` contains the
  browser-local serial OCR design.
- `database/seeders/ProductionPermissionGroupSeeder.php` defines canonical role
  permissions while preserving administrator-added grants.
- `database/seeders/ProductionStatusLabelSeeder.php` and
  `resources/lang/nl-NL/refurb.php` contain the current status defaults/labels.
- `database/seeders/AttributeTestSeeder.php` defines canonical workflow items,
  profile membership, and Standard Diagnostics order.
- `docs/manuals/operator-guides/` contains guide sources, evidence, generators,
  review state, and the rule that accepted PDFs remain immutable.
