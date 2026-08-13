# Operator Guide Decisions

Status: current cross-guide decisions and unresolved questions.

## Status Meanings

| Status | Meaning |
| --- | --- |
| `Accepted` | Use this direction until the user explicitly changes it. |
| `Accepted direction` | Preserve the approach, but the exact artifact is not approved. |
| `Open` | Requires a later decision or evidence. |
| `Deferred` | Intentionally excluded from current work. |
| `Superseded` | Replaced by a newer decision. |

These decision statuses describe project direction. Artifact review status is
separate: `Working draft`, `Internal review candidate`, `Third-party approved`,
or `Superseded`. `Accepted` therefore does not mean third-party approval.

## Current Decisions

| Date | Decision | Status |
| --- | --- | --- |
| 2026-06-23 | Use laminated A4 portrait guides, normally one side and at most double-sided when necessary. | Accepted |
| 2026-06-25 | Use Dutch for the first floor/refurbisher guide set. | Accepted |
| 2026-06-25 | Use family color, marker, code, and label for guide references; do not rely on color alone. | Accepted |
| 2026-06-30 | Use a step-first layout with visuals attached to their relevant steps. | Accepted |
| 2026-06-30 | Do not require a fixed screenshot quantity; each visual needs an operator purpose. | Accepted |
| 2026-06-30 | Put step-specific stop instructions inside or visibly attached to the relevant step. | Accepted |
| 2026-06-30 | Preserve compact help, `Klaar als`, relevant guides, version/source, and a bottom digital-guide area. | Accepted |
| 2026-07-02 | Use larger step numbers and smaller, subtler screenshot labels that overlap image corners. | Accepted |
| 2026-07-02 | For mobile scanner evidence, use the earlier mobile QR-camera screenshot rather than the laptop camera. | Accepted |
| 2026-07-07 | Use `https://snipe.inbit/` for current guide references and live captures. | Accepted |
| 2026-07-21 | Generated guides are the active base-production method; Affinity is optional finishing after base confirmation. | Accepted |
| 2026-07-21 | Do not work on Affinity until every guide in the active set is confirmed or the user gives an explicit green light. | Accepted |
| 2026-07-21 | AC-01 v5 and SC-01 v6 are close to done and should be corrected, not redesigned. | Accepted direction |
| 2026-07-21 | Use the current AC-01/SC-01 text scale as the practical baseline; external point-size recommendations are not hard requirements. | Accepted |
| 2026-07-21 | Reuse one canonical screenshot wherever guides show the same application state; crops and callouts may vary without duplicating the source. | Accepted |
| 2026-07-21 | Use `https://dev.inbit/` as the controlled capture environment for missing or state-changing evidence while keeping `https://snipe.inbit/` as the operator-facing URL. | Accepted |
| 2026-07-21 | SC-01 owns scan/search, opening the result, and verifying the physical asset. AST-01 is retired and its useful verification evidence is reused in SC-01. | Accepted |
| 2026-07-21 | CMP-02 contains two alternatives: register a definition-backed component or register a custom component. CMP-03 is retired as a separate guide. | Accepted |
| 2026-08-04 | CMP-01 is for a physical component that already has a tracked tray/storage record. CMP-02 route 2A creates a new tracked record from an existing catalog definition; route 2B is the approved custom alternative. | Accepted |
| 2026-07-21 | Split end-of-work guidance into AST-04 operator handoff and AST-05 supervisor review/release. Do not combine destructive remove, retire, or sell actions into either guide. | Accepted |
| 2026-07-21 | AST-03 is intentionally two-sided: registration on the front and label printing/placement/verification on the back. | Accepted |
| 2026-07-21 | Build AST-02 last as a route overview that references confirmed task guides and does not repeat their instructions. | Accepted |
| 2026-07-23 | AC-01 v6, SC-01 v10, and AST-02 v5 are the exact internally accepted generated candidates for the V1 guide set. | Accepted |
| 2026-07-23 | WF-01 checks for and visually explains an existing run before the new-workflow path; use the label `Doorgaan met bestaande workflow`. | Superseded |
| 2026-07-23 | WF-02 is intentionally two-sided so instructions, result controls, notes, and photo evidence retain recognizable vertical context. | Accepted direction |
| 2026-07-23 | WF-02 shows the live in-app `Foto` and `Foto toevoegen` controls; do not substitute the unrelated QR scanner camera screen for device-native evidence capture. | Accepted direction |
| 2026-08-04 | WF-01 keeps the normal new-run path as four numbered steps. `Doorgaan met bestaande workflow` is an unnumbered alternative, not a default step. | Accepted direction |
| 2026-08-04 | Before WF-02 step 3, result buttons must remain neutral; use actual blank-run captures rather than recoloring a selected state. | Accepted direction |
| 2026-08-04 | Workflow screenshots use exact source-pixel SVG viewboxes and source-pixel targets; do not return to percentage `object-fit` callouts. | Accepted |
| 2026-08-04 | Controlled example asset tags may be anonymized consistently in the generator; the current workflow drafts use `INBIT-HG0421`. | Accepted direction |
| 2026-08-04 | WF-01 does not repeat asset validation; SC-01 owns that prerequisite. WF-01 step 1 only locates Tests, and continuation is an alternative inside step 3. | Accepted direction |
| 2026-08-04 | WF-02 steps 2 and 3 omit non-critical stop warnings already covered by compact help; targets must align directly with the named controls. | Accepted direction |
| 2026-08-04 | WF-02 v10 is the exact internally accepted generated candidate for V1. The verified in-app `Foto` and `Foto toevoegen` states are sufficient; a device-native camera/file-picker capture is not required for this version. | Accepted |
| 2026-08-04 | CMP-01 follows the actual four-step interface flow. There is no separate existing-component branch page: `Add / Install Component` directly lists tray and storage records when available. | Accepted direction |
| 2026-08-04 | CMP-01 selection and installation may reuse one real screenshot with separate target marks; the final step must show the tracked tag and serial on the asset. | Accepted direction |
| 2026-08-04 | CMP-01 v4 is the exact internally accepted generated candidate for V1. | Accepted |
| 2026-08-11 | USR-01 is a two-sided administration guide: account creation and standard role assignment on side 1; approved custom-rights exceptions and password maintenance on side 2. | Superseded |
| 2026-08-11 | Usernames use the first name with an initial capital followed by the lowercase first letter of each last-name part, without periods or spaces; collisions require an approved exception. | Accepted |
| 2026-08-11 | Assign standard access through the deployed groups. Refurbisher and Admin are the first documented choices; do not use Superadmin as an Admin shortcut. | Accepted |
| 2026-08-11 | Prefer a password-reset link. When an administrator must set a password, use the built-in generator, treat it as temporary, transfer it securely, and require the user to change it immediately. The application does not enforce this handoff at next login. | Accepted |
| 2026-08-11 | Split user management by role and outcome: USR-01 add user, USR-02 change role/rights, USR-03 administrator password reset, AC-02 user self-change, and USR-04 deactivate/delete/restore. | Accepted direction |
| 2026-08-11 | USR-04 treats deactivation as the normal access-removal route. Deletion is a separate lifecycle decision after assignments and management relationships are resolved; restoration reuses the existing record. | Accepted direction |
| 2026-08-11 | Capture user-management evidence only in the controlled development environment with a reversible fictional account and no visible password values. | Accepted direction |
| 2026-08-13 | USR-01 uses username plus the current four-digit year as the first-login temporary password. It is handed over personally and replaced immediately through AC-02. | Accepted direction |
| 2026-08-13 | In USR-01, standard groups are selected at the bottom of the Information page. Direct rights and `Global: Super User` are on the second top tab, `Machtigingen`; incorrect selections can activate otherwise unused functions. | Accepted direction |
| 2026-08-13 | USR-01 uses the lowest group that permits the person's work. Account creators do not request separate permission for routine assignment; user-specific additions are made through `Machtigingen` and are documented by USR-02. | Accepted direction |
| 2026-08-13 | Reusable group creation and editing is a separate administration task, USR-05. USR-02 remains responsible for assigning groups and direct-rights exceptions to one user. | Accepted direction |
| 2026-08-13 | Both Admin and Superadmin can create user accounts. USR-01 names both roles instead of treating account creation as Superadmin-only. | Accepted direction |
| 2026-08-13 | Shared guide components own colors, circular-label centering, focus padding, full guide references, two-row related-guide layout, and component geometry checks. Review feedback is recorded by version and promoted when reusable. | Accepted |
| 2026-08-13 | Internally accepted guide versions are `Internal review candidate`; only an exact version accepted by a third-party reviewer is `Third-party approved`. | Accepted |
| 2026-08-13 | USR-02 is performed by Admin or Superadmin without a separate approval step. Only Superadmin can change group membership or `Global: Super User`; both roles can manage ordinary direct rights within the application's enforced boundary. | Accepted direction |
| 2026-08-13 | USR-02 explains multi-group selection and the effective behavior of `Overnemen`, `Toestaan`, and `Weigeren`; redundant stop text is omitted. | Accepted direction |
| 2026-08-13 | In USR-02, group membership means adding or removing a group on one user and is Superadmin-only. Direct per-user `Toestaan` or `Weigeren` choices take priority over inherited group rights. | Accepted direction |
| 2026-08-13 | Preserve the task-dependent structures from accepted guides as named layout recipes and step patterns; do not force later guides through one generic step grid. | Accepted |
| 2026-08-13 | Every active guide records its exact version, page model, layout recipe, generator, and artifact root. Visible shared changes create new versions for every affected guide; accepted artifacts remain immutable. | Accepted |

## Open Decisions

| Question | When it blocks work |
| --- | --- |
| Should digital-guide QR codes open a per-guide latest page, a guide index, or be omitted initially? | Before a version can be `Third-party approved` with a printed QR. |
| Is serial-number search reliable in the release target? | Before SC-01 can be `Third-party approved`; draft wording may show it only when verified in the controlled environment. |
| Which exact component permissions/role labels will be deployed? | Before CMP-02 or CMP-04 can be `Third-party approved`; CMP-01 v4 retains its internally accepted wording unless a later version is requested. |
| Which exact fields, status, quality, and location values belong in asset registration? | Before AST-03 can be `Third-party approved`. |
| Which exact status represents operator handoff and which status represents supervisor release? | Before AST-04 or AST-05 can be `Third-party approved`. |
| What is the agreed physical review location and its recognizable visual marker? | Before AST-04 can be `Third-party approved`. |
| What is the approved secure channel for the USR-03 generated temporary-password alternative, and how is immediate self-change confirmed? | Before USR-03 can be `Third-party approved`. |
| What email-address convention should administrators use? | Before USR-01 can instruct administrators to enter anything beyond an existing verified address. |

## Version Review Record

Review state is version-specific. Later changes require a new version record.

| Guide | Version | Decision | Date | Notes |
| --- | --- | --- | --- | --- |
| AC-01 | v6 | Internal review candidate for V1 | 2026-07-23 | Exact tested shared-frame layout; do not substitute the generic three-card layout. |
| SC-01 | v10 | Internal review candidate for V1 | 2026-07-23 | Exact asymmetric mobile-first layout with corner-overlap image labels. |
| AST-01 | v13 draft | Retired | 2026-07-21 | Scope absorbed into SC-01; useful evidence remains canonical. |
| AST-02 | v5 | Internal review candidate for V1 | 2026-07-23 | Exact compact route-list layout with the unregistered-asset branch under the yellow alternatives. |
| AST-03 | v1 draft | Working draft; evidence incomplete | 2026-07-21 | Two-sided registration and labeling guide; registration-form captures remain explicit gaps. |
| AST-04 | v1 draft | Working draft; evidence incomplete | 2026-07-21 | Handoff status and physical review-location evidence remain unresolved. |
| AST-05 | v1 draft | Working draft; evidence incomplete | 2026-07-21 | Exact approved release status and confirmed end state remain unresolved. |
| WF-01 | v9 | Internal review candidate for V1 | 2026-08-04 | Exact one-page workflow-opening guide internally accepted; SC-01 owns asset validation, step 3 separates start/continue with an `OF` divider, and step 4 names WF-02 fully. |
| WF-02 | v10 | Internal review candidate for V1 | 2026-08-04 | Exact two-sided workflow-execution guide internally accepted. In 4A, native yellow identifies the active `Notitie` section and the red target identifies the note-entry field; the verified in-app photo controls are sufficient for V1. |
| CMP-01 | v4 | Internal review candidate for V1 | 2026-08-04 | Exact four-step flow with pixel-measured targets, selected tray record, and confirmed tracked tag/serial end state. |
| CMP-02 | v2 draft | Working draft; awaiting review | 2026-08-04 | Verified four-step create-and-install flow with definition/custom alternatives and confirmed tracked-row result. |
| CMP-04 | v5 draft | Working draft; awaiting review | 2026-08-04 | Verified locked-serial confirmation, centered 1B action target, and confirmed `In Tray` / no-asset end state. |
| HELP-01 | v6 draft | Working draft; awaiting review | 2026-08-04 | Twelve recovery tiles, supervisor-only password reset, component/tray routes, and updated guide references. |
| USR-01 | v8 | Internal review candidate for V1 | 2026-08-13 | Exact accepted version: expanded Dutch `Personen` / `Toon Alles` navigation as 1A, add-user action as 1B, shared components, and five full footer references. |
| USR-02 | v7 | Internal review candidate for V1 | 2026-08-13 | Exact accepted version: behavior/evidence and help-row corrections retained; complete 3A focus stroke visible inside its screenshot frame. |
| USR-03 | v1 draft | Working draft; awaiting review | 2026-08-11 | Reset-link and generated temporary-password alternatives with secure handoff to AC-02. |
| AC-02 | v1 draft | Working draft; awaiting review | 2026-08-11 | Empty self-service form and save action are shown; no real password change was submitted for evidence. |
| USR-04 | v1 draft | Working draft; awaiting review | 2026-08-11 | Two-sided deactivation, controlled deletion, and restoration flow using one reversible fictional account. |
| USR-05 | planned | Workflow and evidence investigation required | 2026-08-13 | Create or edit reusable group definitions; distinct from per-user group assignment and direct rights in USR-02. |
