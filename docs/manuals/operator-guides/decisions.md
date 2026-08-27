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
| 2026-08-18 | AST-03 registers refurbishment work in `Being Processed` and uses the configured refurbishment work location before the record is saved and labeled. | Superseded by v3 feedback |
| 2026-08-18 | AST-04 hands completed operator work to supervisor review with `QA Hold`; AST-05 releases approved work as `Ready for Sale` and returns rejected work to `Being Processed`. | Under product review |
| 2026-08-18 | AST-03 treats location as optional metadata rather than an important numbered registration step. | Accepted direction |
| 2026-08-18 | AST lifecycle drafts must explain the next action represented by a status. The current labels may remain in working proofs, but their final names and in-application next-action cues are unresolved product work. | Accepted direction |
| 2026-08-18 | Red `STOP` is reserved for genuine halt conditions. Recoverable duplicate, model, mismatch, or incomplete-work corrections use amber inline warnings. | Accepted direction |
| 2026-08-18 | Controlled guide captures may substitute one consistent fictional `INBIT-*` identity in the rendered browser DOM when the substitution is recorded in the evidence manifest and no application record is created or renamed. | Accepted direction |
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
| 2026-08-18 | USR-01 step 3 must name the full route through `Informatie` and the collapsed `Optionele informatie` section before directing an administrator to `Groepen`; `Onderaan` alone is insufficient. | Accepted direction |
| 2026-08-18 | AC-01 requires a general browser-capable device and an account. The Inbit Snipe-IT phone item is a shortcut link, not an installed application requirement. | Superseded |
| 2026-08-18 | AC-01 names an `Inbit-telefoon + account` in `Nodig` so the expected starting device is explicit; Snipe-IT remains a browser shortcut, not an installed application. | Accepted direction |
| 2026-08-18 | AST-02, WF-01, and WF-02 are Refurbisher guides without a senior-role requirement. CMP-01 component placement is assigned to Senior Refurbisher. | Accepted direction |
| 2026-08-18 | WF-01 3B must center its focus target directly around the existing-run `Bewerk` action. | Accepted direction |
| 2026-08-18 | Catalogue administration is a separate `CAT` family. CAT-00 owns concepts and decision rules; CAT-01 owns base-model/exact-number creation; CAT-02 through CAT-06 own specification, reusable definitions, variants/lifecycle, and source verification. | Accepted direction |
| 2026-08-18 | A base model is the product and generation, a model number is the exact manufacturer variant/SKU, and an asset is one physical device with its own Inbit tag and serial. These identifiers are not interchangeable. | Accepted direction |
| 2026-08-18 | Catalogue work searches and reuses before creating. Do not invent model numbers, replace a missing manufacturer/category with a near match, or create another base model merely for a RAM/storage variant. | Accepted direction |
| 2026-08-27 | CAT-01 makes the search result an explicit three-route decision: reuse an existing exact code, add a missing exact code to an existing Basismodel, or create a Basismodel only when product and generation are absent. A different printed SKU is a different model number; a later RAM/storage replacement is an asset component change. | Accepted direction |
| 2026-08-18 | Stable variant facts use model-number attributes; separate, replaceable, countable parts use expected components; reusable part facts use component-definition attributes; physical exceptions use instance values or permitted asset overrides; test and condition evidence remains a workflow result. | Accepted direction |
| 2026-08-18 | Component-derived specification values take priority over competing manual values; physical instance values take priority over definition defaults; child contributions replace parent contributions for the same fact. Numeric contributions sum by quantity, bool uses any true, and enum/text retain distinct values. | Accepted direction |
| 2026-08-27 | CAT reference diagrams use semantic object colors: component definitions, expected components, and placed components share CMP amber; asset identity and asset-specific state share AST green; labels and fill/border treatment distinguish definitions, baselines, and physical records. | Accepted direction |
| 2026-08-18 | `Kopieer model` copies only the base-model form and optionally its image. It does not copy model numbers, model-number images, specification values, or expected components; no complete model-number duplicate action exists. | Accepted direction |
| 2026-08-13 | Shared guide components own colors, circular-label centering, focus padding, full guide references, two-row related-guide layout, and component geometry checks. Review feedback is recorded by version and promoted when reusable. | Accepted |
| 2026-08-13 | Internally accepted guide versions are `Internal review candidate`; only an exact version accepted by a third-party reviewer is `Third-party approved`. | Accepted |
| 2026-08-25 | AST-03 v14 is the exact internally accepted two-page candidate for V1. | Accepted |
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
| What is the agreed physical review location and its recognizable visual marker? | Before AST-04 can be `Third-party approved`. |
| Which operator-facing status names and in-application next-action cues should represent active work, waiting for QA, release, and return for correction? | Before AST-04 or AST-05 can be internally accepted. |
| What is the approved secure channel for the USR-03 generated temporary-password alternative, and how is immediate self-change confirmed? | Before USR-03 can be `Third-party approved`. |
| What email-address convention should administrators use? | Before USR-01 can instruct administrators to enter anything beyond an existing verified address. |
| Where must a catalogue administrator record the source and verification result for an exact model number or critical specification? | Before CAT-06 can become an internal review candidate; the current UI has no dedicated source field. |

## Version Review Record

Review state is version-specific. Later changes require a new version record.

| Guide | Version | Decision | Date | Notes |
| --- | --- | --- | --- | --- |
| AC-01 | v6 | Internal review candidate for V1 | 2026-07-23 | Exact tested shared-frame layout; do not substitute the generic three-card layout. |
| SC-01 | v10 | Internal review candidate for V1 | 2026-07-23 | Exact asymmetric mobile-first layout with corner-overlap image labels. |
| AST-01 | v13 draft | Retired | 2026-07-21 | Scope absorbed into SC-01; useful evidence remains canonical. |
| AST-02 | v5 | Internal review candidate for V1 | 2026-07-23 | Exact compact route-list layout with the unregistered-asset branch under the yellow alternatives. |
| AST-03 | v14 | Internal review candidate for V1 | 2026-08-25 | Exact accepted two-page flow with contained step-4 text and image captions. |
| AST-04 | v5 draft | Visual-correction pass; awaiting exact-version review | 2026-08-25 | Defines the guide as the final refurbishment-to-QA handoff; 2B applies only to registered Tracked components and all evidence remains contained. |
| AST-05 | v5 draft | Visual-correction pass; awaiting exact-version review | 2026-08-25 | Preserves the review/release route and renders every guide handoff with full family styling. |
| WF-01 | v9 | Internal review candidate for V1 | 2026-08-04 | Exact one-page workflow-opening guide internally accepted; SC-01 owns asset validation, step 3 separates start/continue with an `OF` divider, and step 4 names WF-02 fully. |
| WF-02 | v10 | Internal review candidate for V1 | 2026-08-04 | Exact two-sided workflow-execution guide internally accepted. In 4A, native yellow identifies the active `Notitie` section and the red target identifies the note-entry field; the verified in-app photo controls are sufficient for V1. |
| CMP-01 | v4 | Internal review candidate for V1 | 2026-08-04 | Exact four-step flow with pixel-measured targets, selected tray record, and confirmed tracked tag/serial end state. |
| CMP-02 | v4 draft | Visual-correction pass; awaiting exact-version review | 2026-08-25 | Preserves the definition/custom choice and renders the CAT-04 missing-definition handoff as a full guide reference. |
| CMP-04 | v6 draft | Cold-start pass; awaiting exact-version review | 2026-08-20 | Names Senior Refurbisher and retains the verified identity, locked-serial, confirmation, and `In Tray` result path. |
| HELP-01 | v6 draft | Working draft; awaiting review | 2026-08-04 | Twelve recovery tiles, supervisor-only password reset, component/tray routes, and updated guide references. |
| USR-01 | v11 draft | Visual-correction pass; v8 remains Internal review candidate | 2026-08-25 | Retains the accepted account flow and presents the USR-02 help handoff as a full styled guide reference. |
| USR-02 | v9 draft | Visual-correction pass; v7 remains Internal review candidate | 2026-08-25 | Adds a separate rectangular focus around `Machtigingen` while preserving the rights-grid focus. |
| USR-03 | v3 draft | Visual-correction pass; awaiting exact-version review | 2026-08-25 | Shows the unsaved generated temporary value, focuses `Genereer`, and presents the AC-02 handoff as a full guide reference. |
| AC-02 | v3 draft | Visual-correction pass; awaiting exact-version review | 2026-08-25 | Uses tight action focus, leaves the fields unobscured, keeps the no-sharing warning with step 3, and uses full USR-03 handoffs. |
| USR-04 | v3 draft | Visual-correction pass; awaiting exact-version review | 2026-08-25 | Uses rectangular action focus, contained warnings, full references, and continuous steps 1-8. |
| USR-05 | planned | Workflow and evidence investigation required | 2026-08-13 | Create or edit reusable group definitions; distinct from per-user group assignment and direct rights in USR-02. |
| CAT-00 | v7 draft | Semantic-color and audit correction pass; awaiting exact-version review | 2026-08-27 | Preserves the eight-part mental model, applies consistent component/asset colors, corrects graph direction and precedence wording, enlarges dense evidence, cleans component crops, and marks planned CAT routes as in preparation. |
| CAT-01 | v3 draft | CAT-00-aligned structural rewrite; awaiting exact-version review | 2026-08-27 | Five-page Supervisor procedure with a three-route reuse decision, only active Basismodel identity fields, exact code/label guidance, legacy-field-free evidence, final verification, and CAT-02/AST-03 handoffs. |
| CAT-02 | planned | Specification ready; evidence pending | 2026-08-18 | Next CAT generation target: direct attributes, expected components, calculated-spec behavior, and final verification. |
| CAT-03 | planned | Specification ready; evidence pending | 2026-08-18 | Reusable attribute lifecycle, scoping, constraints, and safe retirement. |
| CAT-04 | planned | Specification ready; evidence pending | 2026-08-18 | Reusable component identity, tracking, placement, contributions, and one-level child structure. |
| CAT-05 | planned | Specification ready; evidence pending | 2026-08-18 | Variant choice, primary/active/deprecated lifecycle, deletion rules, and duplication limits. |
| CAT-06 | planned | Specification ready; source-recording policy unresolved | 2026-08-18 | Evidence hierarchy and exact-code verification are defined; storage of the verification result still needs an owner decision. |
