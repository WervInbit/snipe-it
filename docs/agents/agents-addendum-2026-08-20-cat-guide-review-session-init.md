# CAT Guide Review Session - 2026-08-20

## Objective

Review CAT-00 and CAT-01 against owner feedback and the current implementation
before changing the guides or renaming application navigation.

## Investigation Scope

- Confirm the minimum operational role for entering hardware catalogue data.
- Map `Asset modellen` to the requested `Basismodellen` terminology and find
  every affected UI/documentation surface.
- Verify model-number reuse, per-asset customization, component-derived values,
  Primary behavior, and what catalogue choices can be changed later.
- Review CAT-00 page order, chapter navigation, terminology, screenshots, and
  precedence explanations.
- Review CAT-01 field presentation, especially the page-3 `Verplicht` block.

## Constraint

This is an investigation and recommendation pass. Preserve the current v1
draft PDFs and application labels until the proposed corrections are reviewed.

## Verified Findings

- `AssetModel` has many model-number records, and each model number has many
  physical assets. A model number identifies a manufacturer/factory variant;
  it is not the permanent current RAM/storage state of every asset using it.
- Installed components can change a physical asset's effective specification.
  Component-derived specification values take precedence over competing manual
  model values, and an installed component's own value takes precedence over
  its reusable component-definition default.
- `Primary` is the one default/fallback model number for a basismodel. The first
  number becomes primary automatically. It is not an approval or lifecycle
  status. It still affects asset-form initialization, imports, model-level
  specification resolution, component rosters, and model-number image fallback.
  The guide should de-emphasize it as a system-managed default rather than ask
  Supervisors to manage it during normal product setup.
- The seeded Supervisor role can register assets but lacks `models.view`,
  `models.create`, and `models.edit`. Attribute-definition management is
  currently Admin/Superadmin-only. Component-definition management is gated by
  `components.manage_definitions`.
- Adding model numbers, changing their lifecycle, editing specifications, and
  removing model-number attributes currently share the broad basismodel update
  authorization. A narrower lifecycle permission is needed if Supervisors must
  create catalogue entries without receiving cleanup/deprecation authority.
- Component-instance attributes are supported by the API/service layer but no
  browser form currently exposes them. CAT-00 must not point GUI operators to
  that route until a UI exists.
- Workflow-item/profile configuration is not currently available to the seeded
  Supervisor role. The routes are inside the `authorize:superuser` admin group,
  the `TestTypePolicy` expects unregistered `test_types.*` keys, and attribute
  definitions remain Admin-only. The old CFG-09 through CFG-12 records are
  planning notes, not generated or registered operator guides.
- Removing a direct model-number attribute also removes per-asset overrides for
  that attribute on assets using that number. Attribute datatype cannot change
  after creation. Catalogue placement can therefore be corrected later, but it
  is not an automatic or impact-free conversion.

## Recommended Revision Direction

1. Make CAT-00 task-oriented: start/index, identity hierarchy, data-placement
   decision, then effective-value/change-impact rules.
2. Repeat a compact four-part chapter strip on every CAT-00 page so a printed or
   digital reader can locate the relevant concept without reading linearly.
3. Move duplicate-search execution to CAT-01 and identifier-source policy to
   CAT-06; CAT-00 should link to those tasks instead of duplicating them.
4. Replace developer-facing labels such as `Instance-attribuut`,
   `resolves_to_spec`, and `Kind -> ouder` with user-facing terms and place the
   exact interface location beside each available route.
5. Split CAT-00 visual 1A into model identity/context and a complete
   model-number row, or use one wide visual with two measured targets. Explain
   `Standaard` next to that evidence.
6. Change CAT-01's `Verplicht` block to compact bold label/value pairs and use
   `Naam basismodel` after the Dutch application terminology is updated.
7. Use `Admin`, not `Admin / Superadmin`, in operator-facing role labels.
   Target CAT-01 through CAT-04 and workflow setup at Supervisors once explicit
   minimum permissions and route access exist; reserve destructive catalogue
   lifecycle actions for Admin.
8. Extend the current CAT set with workflow-item/applicability, workflow-
   profile composition, and complete sample-asset validation guides. Do not
   revive the parallel CFG list as a second source of truth.

## First-Time Operator Review

- Reviewed the active guide specifications and the current generated/accepted
  PDFs as instructions for an employee who knows the work but has no prior
  Snipe-IT or programming knowledge.
- The practical cold-start contract is: name or show the starting screen, use
  the exact visible interface label for every action, keep one example identity
  consistent through the task, show Save/confirmation where the user must act,
  explain unfamiliar choices in plain operational language, and end on a
  visible result plus the complete next-guide reference.
- AC-01, SC-01, AST-02, WF-01, CMP-01, USR-01, and USR-02 have a sound task
  structure. They still need normal version-specific wording/role checks, but
  they are not candidates for structural redesign.
- WF-02 v10 has a usable task structure; the current v11 page-2 export is
  visibly shifted/clipped and must be regenerated without changing the
  accepted v10 artifact.
- AST-03, AST-04, AST-05, CMP-02, CMP-04, AC-02, USR-03, USR-04, and HELP-01
  need focused cold-start corrections. Recurring defects include missing
  navigation, missing Save controls, inconsistent example assets/users,
  unresolved role labels, unexplained catalogue terms, and current draft
  export clipping.
- CAT-00 requires a content rewrite rather than cosmetic adjustment: remove
  GUI-inaccessible instance attributes and developer labels such as
  `resolves_to_spec`, translate source precedence into operator decisions, and
  make the chapter an index to concrete tasks. CAT-01's sequence is reusable,
  but its terminology, role, Primary emphasis, and evidence need correction.
- CAT-02 through CAT-06 and the proposed CAT-07 through CAT-09 must be drafted
  against the cold-start contract from the outset. USR-05 still requires live
  workflow investigation before it can be written as an independent task.
- No guide specification, generator, accepted PDF, or application behavior was
  changed during this review.

## Full Cold-Start Gate Result

- The initial visual scan above was followed by an exact-artifact render of all
  19 generated current guides: 29 of 29 A4 pages rendered, all were nonblank,
  and none touched a page edge. The previously suspected WF-02 v11, CMP-04,
  AC-02, and USR-04 clipping was not reproduced and is superseded by this exact
  render result.
- The guide package tests pass with the bundled Poppler `pdfinfo` path. Static
  artifact checks also pass for required structure, stale development URLs,
  retired AST-01 references, and missing-evidence markers.
- Manual cold-start outcomes across all 25 active registry entries are 7 pass,
  6 conditional pass, 6 fail, and 6 not testable because no artifact exists.
- The complete criteria, artifact versions, evidence, and per-guide findings
  are recorded in
  `docs/manuals/operator-guides/reviews/2026-08-20-cold-start-audit.md`.
- No guide, generator, accepted PDF, application behavior, or production data
  changed during the gate.

## Rework And Retest Result

- Reworked the 12 conditional/failing generated guides as new exact versions:
  AC-02 v2, AST-03 v13, AST-04/05 v4, CMP-02 v3, CMP-04 v6, USR-01 v10,
  USR-02 v8, USR-03/04 v2, and CAT-00/01 v2.
- User/account corrections remove unsupported mail/LDAP reset routes, add the
  canonical People navigation where missing, make username/password rules
  explicit, and preserve personal AC-02 handoff.
- Asset lifecycle corrections use one controlled HP ProBook identity, real
  physical placement evidence, explicit status meaning, and verified
  automatic status persistence.
- Component corrections name Senior Refurbisher and explain reusable
  definition versus one-off custom without a false stop.
- CAT-00 is now a plain-language task index; CAT-01 uses the Supervisor,
  Basismodel, exact-code, system-standard-row, Save/result, and follow-up model.
- Updated generator defaults, registry/specifications, evidence hashes,
  screenshots, reviews, handoff, inventory, decisions, README, and TODOs.
- The complete package gate, required/forbidden text checks, 21-page A4 check,
  nonblank raster/margin check, and grouped full-page visual inspection pass.
  Current totals are 19 PASS and 6 NOT TESTABLE planned guides.
- No accepted PDF or production record changed. All 12 replacements remain
  working drafts until exact-version review.
