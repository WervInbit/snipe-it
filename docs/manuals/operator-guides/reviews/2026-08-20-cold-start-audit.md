# Cold-Start Operator Guide Audit - 2026-08-20

Status: completed baseline review and focused retest of every generated active
guide. The baseline section below remains unchanged; the retest records the 12
new working drafts. No accepted artifact or production data changed.

## Scope

The test user knows the physical refurbishment work but has no prior Snipe-IT
or programming knowledge. The audit covers the 25 active registry entries:

- 19 current generated guides, totaling 29 A4 pages;
- 6 planned specifications without generated artifacts;
- the retired AST-01 guide is excluded.

Proposed CAT-07 through CAT-09 workflow-configuration guides are not registry
entries yet. Their absence is recorded as a planning gap, not as a failed
artifact test.

## Outcome Definitions

- `PASS`: the current task can be completed independently. Non-blocking polish
  may remain before third-party approval.
- `CONDITIONAL PASS`: the task structure works, but one named correction or
  prerequisite is required before independent use.
- `FAIL`: hidden knowledge, conflicting policy, missing action/result evidence,
  or an inconsistent example can cause the operator to take the wrong action.
- `NOT TESTABLE`: no generated artifact exists.

## Cold-Start Criteria

| ID | Criterion |
| --- | --- |
| `START` | The starting page is named or shown from a known prerequisite. |
| `UI` | Actions use the exact visible interface label. |
| `CONTEXT` | Screenshots retain enough page or device context for recognition. |
| `IDENTITY` | One example asset or user remains consistent through the task. |
| `CHOICE` | Unfamiliar choices explain meaning, use, and an example. |
| `SAVE` | A required save or confirm action is stated and visually locatable. |
| `DONE` | The final visible state proves completion. |
| `RISK` | Stops, warnings, and alternatives appear where first relevant. |
| `REF` | Handoffs use the family marker, code, color, and full guide name. |
| `SOLO` | The task can be completed without undocumented verbal knowledge. |

## Automated Evidence

- `npm test` from `scripts/manuals` passed after setting `GUIDE_PDFINFO_PATH` to the
  bundled Poppler executable. The first run stopped only because that executable
  was not on the resolver path.
- Shared checks reported 25 registry entries, 5 related-guide checks, 70
  evidence files, 8 frozen accepted PDFs, 9 accepted pages, 2 baselines, and 16
  active generator scripts.
- All 19 current PDFs rendered successfully to 29 nonblank A4 pages at
  992 x 1404 pixels. Every page retained a white boundary; no rendered content
  touched a page edge.
- Extracted-text checks found the guide code, role, needed items, completion
  statement, relevant-guide section, and source in every generated artifact.
- No current artifact contains `dev.inbit`, references retired `AST-01`, or has
  a missing-evidence/TODO marker.

These checks prove package and rendering integrity. They do not prove that the
operator instructions are correct; the manual outcomes below own that result.

## Guide Results

| Guide and current artifact | Result | Cold-start finding |
| --- | --- | --- |
| AC-01 Login v8 | PASS | Phone shortcut, login form, and dashboard form one recognizable sequence. Browser use remains a help route. Footer names are abbreviated and the final QR policy remains open, but neither blocks login. |
| AC-02 Eigen wachtwoord wijzigen v1 | CONDITIONAL PASS | The local-account task has exact controls, save action, and a visible end state. Remove or quarantine unsupported V1 LDAP wording, and do not rely on USR-03 until that guide matches the deployed mail boundary. |
| SC-01 Asset vinden en openen v10 | PASS | Both scanner entries, physical QR location, manual asset-tag/QR/serial lookup, and identity stop are usable. Serial lookup is implemented in `ScanController`. |
| AST-02 Refurbishment-route v6 | PASS | The route starts from a registered device, keeps the unregistered-device path in help, and makes the next task clear without duplicating task instructions. |
| AST-03 Asset registreren en labelen v12 | CONDITIONAL PASS | The complete dashboard-to-scan sequence is present, including save/result checks and the real label-placement photo. Replace `Supervisor / asset creator` with the approved minimum-rights role, enlarge the compressed model/result evidence where possible, and finish the remaining 1B/1C target review. |
| AST-04 Werk afronden en overdragen v3 | FAIL | Step 1 uses a Pixel 8 Pro while later steps use an HP ProBook, so identity is inconsistent. Step 3 instructs save without showing where the operator saves. Status wording is still under product review. Fails `IDENTITY`, `SAVE`, and `SOLO`. |
| AST-05 Asset beoordelen en vrijgeven v3 | FAIL | Step 1 asks for identity validation but shows only the QA Hold field. Step 4 instructs save without locating the action. Release/return status wording remains under review. Fails `CONTEXT`, `SAVE`, and `SOLO`. |
| WF-01 Workflow starten v10 | PASS | The open Tests tab, profile choice, one-time new run, optional existing-run route, and card recognition are clear and use one task flow. |
| WF-02 Workflow uitvoeren en afronden v11 | PASS | The current two-page PDF renders correctly. Breadcrumb validation, instruction, execution, note, photo, and final run count are shown in order with neutral result buttons before execution. |
| CMP-01 Bestaand component plaatsen v5 | PASS | The existing tracked-part path preserves component identity from selection through install and final asset check. The role and stops match the physical/digital mismatch risk. |
| CMP-02 Nieuw component registreren en plaatsen v2 | CONDITIONAL PASS | The visible path is executable, but `Bevoegde refurbisher`, `definitie`, and the approved one-off `Aangepast` route still depend on local verbal policy. Name the minimum role and explain the reusable-definition decision in operator language; demote recoverable uncertainty from STOP. |
| CMP-04 Component naar tray v5 | CONDITIONAL PASS | Selection, confirmation, physical removal, and final `In Tray` state are clear. Replace the ambiguous `Bevoegde refurbisher` role with the minimum role and complete normal focus-target polish. |
| HELP-01 Problemen en hulp v6 | PASS | The non-linear tiles give one safe recovery action per common problem and keep identity mismatch as the page-level stop. Full registered names should replace abbreviated footer references before external review. |
| USR-01 Gebruiker toevoegen v9 | CONDITIONAL PASS | Dashboard navigation, optional information, minimum rights, save/result, and immediate AC-02 handoff are present. Spell out the username capitalization/no-period rule instead of relying on `voornaam + initialen`, and keep the role label aligned with the final permissions model. |
| USR-02 Rol en rechten wijzigen v7 | CONDITIONAL PASS | Group selection and `Overnemen`/`Toestaan`/`Weigeren` are explained, including direct-right precedence. A zero-knowledge user is not shown how to reach the Users list from the dashboard; add the canonical Personen navigation or declare an open-user prerequisite. |
| USR-03 Wachtwoord resetten v1 | FAIL | The preferred emailed reset link conflicts with the current V1 in-memory mail boundary, where reset mail is suppressed. LDAP is also unsupported for V1. The guide starts on a user detail page without navigation. Fails `START`, `CHOICE`, and `SOLO`. |
| USR-04 Gebruiker uitschakelen of herstellen v1 | FAIL | The lifecycle sequence is understandable once on the account, but neither side shows how to reach the user page from the dashboard. `Goedgekeurde wijziging/eigenaar` is an unstated local process, so a new employee cannot start or decide independently. Fails `START` and `SOLO`. |
| USR-05 Groepen beheren | NOT TESTABLE | Workflow, evidence, exact controls, page model, layout, generator, and final minimum-rights boundary remain unverified. |
| CAT-00 Catalogus begrijpen v1 | FAIL | Pages 3-4 use programmer-facing concepts (`Instance-attribuut`, `Asset override`, `resolves_to_spec`, `Kind -> ouder`, aggregation) and include a browser-inaccessible component-instance route. The chapter does not yet function as the requested task index. Fails `CHOICE` and `SOLO`. |
| CAT-01 Model en modelnummer aanmaken v1 | FAIL | The five-page route is structurally reusable and starts correctly, but the role/access model does not allow the intended Supervisor flow, `Primary` is overemphasized, the requested Basismodel terminology is not implemented, and the RAM/storage variant rule still needs owner-approved wording. Fails `CHOICE` and `SOLO`. |
| CAT-02 Modelspecificatie opbouwen | NOT TESTABLE | Specification exists, but evidence and artifact are absent. Translate `resolves_to_spec`, aggregation, and overlap rules into visible operator decisions before generation. |
| CAT-03 Attributen beheren | NOT TESTABLE | Specification exists, but evidence and artifact are absent. Datatypes, constraints, scope, override behavior, and edit impact need a plain-language chooser and verified minimum role. |
| CAT-04 Componentdefinities beheren | NOT TESTABLE | Specification exists, but evidence and artifact are absent. Tracking, placement, contribution, and parent/child overlap choices need operator examples and verified Supervisor access. |
| CAT-05 Varianten en lifecycle beheren | NOT TESTABLE | Specification exists, but evidence and artifact are absent. Default/Primary behavior and destructive lifecycle permissions must be separated and explained before generation. |
| CAT-06 Catalogus controleren en bronnen | NOT TESTABLE | The source-recording policy is unresolved and the current UI has no dedicated verification-source field. This remains a real product blocker. |

## Totals

| Result | Count |
| --- | ---: |
| PASS | 7 |
| CONDITIONAL PASS | 6 |
| FAIL | 6 |
| NOT TESTABLE | 6 |
| Total active registry entries | 25 |

## Required Correction Order

1. Fix AST-04 and AST-05 identity, save-action evidence, and final status wording.
2. Replace USR-03's unsupported V1 mail/LDAP routes and add canonical Users
   navigation to USR-02 through USR-04 where needed.
3. Apply the focused role/terminology corrections to AST-03, CMP-02, CMP-04,
   USR-01, and USR-02 without redesigning their usable structure.
4. Rewrite CAT-00 in task language, then correct CAT-01 against the final
   Supervisor permission model and Basismodel UI terminology.
5. Investigate and generate USR-05 and CAT-02 through CAT-06. Add the proposed
   workflow-item/profile/sample-validation guides to the registry only after
   their real workflows and role boundaries are verified.
6. Run the same cold-start gate on each new exact version before changing its
   registry status. QR destinations and complete related-guide names remain a
   separate third-party-publication gate across the draft set.

## Focused Retest After Rework

The 12 conditional/failing generated guides were revised without changing the
seven baseline passes, six planned specifications, or any frozen accepted PDF.
The retest uses the same `START` through `SOLO` criteria.

| Revised guide | Result | Resolved finding |
| --- | --- | --- |
| AC-02 v2 | PASS | Local-account-only route; LDAP guidance removed; forgotten/current-password recovery points to supervisor-run USR-03. |
| AST-03 v13 | PASS | Supervisor role, readable registration/result evidence, reviewed create targets, continuous steps, and one controlled identity. |
| AST-04 v4 | PASS | One HP ProBook identity across workflow, physical, component, and handoff checks; `QA Hold` documents automatic saving and visible persistence. |
| AST-05 v4 | PASS | Identity and incoming status are separate; workflow and physical evidence use the same asset; release/return choices document automatic saving. |
| CMP-02 v3 | PASS | Senior Refurbisher is named; reusable definition versus one-off custom is explained; missing definitions route to CAT-04 without a false STOP. |
| CMP-04 v6 | PASS | Senior Refurbisher is named and the verified locked-serial removal/result route remains intact. |
| USR-01 v10 | PASS | Capitalization, lowercase surname initials, no-period/no-space username rule, and temporary-password convention are explicit. |
| USR-02 v8 | PASS | Canonical `Personen > Toon Alles`, search, and edit navigation precedes the accepted rights flow. |
| USR-03 v2 | PASS | Unsupported email/LDAP routes are removed; canonical navigation, one generated temporary password, personal handoff, and AC-02 continuation are complete. |
| USR-04 v2 | PASS | Canonical navigation, explicit new responsible owner, and continuous steps 1-8 make both sides independently executable. |
| CAT-00 v2 | PASS | The chapter is a task index in operator language; browser-inaccessible and developer-facing routes are removed. |
| CAT-01 v2 | PASS | Supervisor/Basismodel route, exact-code decision, automatic standard row, Save/result evidence, and CAT-02/AST-03 handoffs are explicit. |

### Retest Verification

- All five changed generators pass `node --check`.
- The complete guide package test passes with 25 registry entries, 70 evidence
  files, eight unchanged accepted PDFs, nine accepted pages, two baselines,
  and 16 active scripts.
- The 12 exact PDFs contain 21 A4 pages. All required title/version/action text
  extracts, and no page contains `dev.inbit`, stale QH identities, missing-
  evidence markers, or unsupported LDAP/reset-link wording.
- All 21 pages render nonblank at 827 x 1170 pixels with strong content inset
  from the page edge. Grouped full-page visual inspection found no clipping,
  overlap, misplaced focus target, unreadable screenshot crop, or footer drift.

### Current Totals

| Result | Count |
| --- | ---: |
| PASS | 19 |
| CONDITIONAL PASS | 0 |
| FAIL | 0 |
| NOT TESTABLE | 6 |
| Total active registry entries | 25 |

`PASS` is a cold-start usability result, not an acceptance state. All 12
revisions remain working drafts pending exact-version review. The six planned
guides remain `NOT TESTABLE` until real evidence and generated artifacts exist.
