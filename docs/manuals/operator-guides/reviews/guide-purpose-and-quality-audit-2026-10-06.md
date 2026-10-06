# Operator Guide Purpose And Quality Review

Recommendation: hold the October 1 nineteen-guide batch as a replacement set
and recover from the preserved task layouts. Keep the necessary application
corrections, placing them where a reader performs the affected action. The
twenty-five guide subjects remain useful; their structure needs to match their
different purposes. No replacement PDFs were generated in this review.

The existing [guide system](../system.md), [layout recipes](../layouts.md),
[maintenance rules](../maintenance.md) and
[September owner feedback](2026-09-08-pilot-direction-feedback.md) already
require useful screenshot sizes, explicit alternatives, retained help and
task-dependent page structures. The October implementation failed to apply
that agreement. A different model did not authorize a new design direction.

## Review Decisions

| Item | Current decision | Consequence |
| --- | --- | --- |
| AC-01 v10 | Owner rejected on October 6. The owner's reference was "v1"; the latest October comparison and the described 2A/3A screenshots identify v10. | Preserve v10 as rejected history. AC-01 v9 remains the exact accepted login baseline. |
| SC-01 v13 | Owner requires rework: scan/search are alternatives and page 2 restarts at step 1. | Hold v13. Retain v12's useful composition while correcting its inherited route ambiguity as well. V12 was owner-cleared for user review; that is not a new exact acceptance. |
| Other seventeen October batch proposals | Editorial review hold recommended; no new owner accept/reject decisions. | Do not treat the batch as the next ready review set. Owner stopped reviewing after SC-01. |
| WF-01 v15 / WF-02 v13 | Existing working-draft status and prior owner feedback retained. | Preserve WF-01's requested natural Standard Diagnostics start and clearer wording. Check Dutch evidence and recognition sizes during their next focused revision. |
| CMP-01 v6 / CMP-04 v7 / USR-04 v6 / CAT-06 v2 | Unchanged by the October batch; prior decisions retained. | Keep their task structures. This audit does not newly accept them. |

The nineteen-guide hold includes the separately rejected AC-01 and SC-01
requiring rework; it is an editorial disposition, not a claim that the owner
rejected all nineteen. Frozen PDFs, HTML, manifests and comparison kits retain
their original bytes and generation-time wording. This dated review and the
per-version feedback records carry the later decisions.

## Confirmed Failures And Causes

**AC-01 lost recognition size and task focus.** Its three-column composition
was retained, but the replacement crops were fitted inside the old frames.
The controls in 2A became smaller. In 3A the dashboard image became about
23.5 mm wide inside a 48 mm frame, roughly half the previous dashboard image
width. Empty margins therefore hid a real loss of useful image size. The
account menu and recent-device rows compete with the intended completion
check: the reader has signed in and recognizes the dashboard. Recent devices
belong to finding/reopening an asset, not the login task.

The exact comparison is [accepted AC-01 v9](../../../../resources/manuals/operator-guides/review-rounds/2026-09-08/AC-01-login-v9-draft.pdf)
against [rejected AC-01 v10](../../../../resources/manuals/operator-guides/review-rounds/2026-10-01-aligned-guides/AC-01-login-v10-draft.pdf).
Preserve the accepted 2A/3A recognition sizes, captions, help and completion.
Recapture only a materially changed login/dashboard state, with Dutch selected
and an appropriate crop. A freshness date alone does not justify replacement.

**SC-01 makes alternatives look sequential.** V12 already numbered scanning
as 2 and manual searching as 3, although it at least said "Als scannen niet
lukt". V13 removed that condition and left both numbered cards. It then added
another route on page 2 with steps 1, 2 and 3 after page 1's completion check.
Recent-device reopening, camera fallback and identity verification are again
presented as a sequence, duplicating the same outcome.

The comparison is [SC-01 v12](../../../../resources/manuals/operator-guides/review-rounds/2026-09-10/owner-corrections/SC-01-asset-vinden-en-openen-v12-draft.pdf)
against [SC-01 v13](../../../../resources/manuals/operator-guides/review-rounds/2026-10-01-aligned-guides/SC-01-asset-vinden-en-openen-v13-draft.pdf).
Keep the asymmetric one-page composition and readable QR photo/search
controls. Group scan and manual search under one choice; converge on one
physical-identity check. Replace obsolete manual-search evidence at that
choice rather than appending a second guide to the first.

**The supplement generator imposed a procedure on every guide type.**
Fourteen of the nineteen revisions append a page. Each starts at 1 and uses
three fixed-size instruction cards with corresponding figures, regardless of
the original guide's final step or type. This produced 66 pages from 52 for the
nineteen affected guides. Including the workflow pair and four unchanged
guides, the comparison set grew from 61 to 75 pages.

AST-03 now runs registration/labeling steps 1-8, then returns to registration
steps 1-3 on page 3. AST-04/05 repeat checks after their original completion
blocks. HELP-01's problem lookup becomes numbered instructions. CAT-00's
conceptual reference becomes an apparent procedure. CAT-02/03/04 introduce
necessary information after an earlier Save/Klaar als, outside the existing
chapter navigation. CAT-05 legitimately contains independent maintenance
routes; local step numbering is appropriate there when the route title and
index clearly identify the independent task. A restart is not inherently wrong
in a named independent route, but it is wrong as an unexplained continuation.

**The Dutch capture requirement was bypassed.** A read-only October 6 check
of the mounted local application found:

| Setting | Observed value |
| --- | --- |
| Global application setting | `nl-NL` |
| Application configuration fallback | `en-US` |
| `admin` account preference | `nl-NL` |
| `demo_admin`, `demo_supervisor`, `demo_senior_refurbisher`, `demo_refurbisher` preferences | `en-US` |

[CheckLocale](../../../../app/Http/Middleware/CheckLocale.php) gives an
authenticated user's language precedence over the global setting. This
explains the Dutch signed-out login and English signed-in captures. The
capture accounts must use Dutch and the visible labels must be checked after
login. Global Dutch alone is insufficient. `Numeric Component Aggregation`
and its explanatory text also resolve to English under `nl-NL` in the mounted
build: translations are missing. Account settings alone cannot fix those
labels. Capture environments/accounts were not changed by this review.

**Available evidence replaced the intended journey.** AST-05's largest new
dialog shows Stand-by -> Ready for Sale with missing workflows and an override
button. Its subsequent status screenshot still shows Stand-by. These are
genuine cancelled-preview captures, but they do not teach the ordinary QA
review -> clean confirmation -> saved release outcome. Show that normal
route first; place cancel/repair and authorized exceptions in explicit branches.
AST-03 similarly needs a matched creation-result/label example, not a restarted
registration page with a different existing asset. CAT-03's aggregation-check
figure shows Enum rows with no numeric aggregation value; use an actual
relevant numeric row/result for that recognition purpose.

**QA checked artifact integrity more reliably than instruction quality.**
No overflow, matching hashes and correct page totals are useful checks. They
did not catch reduced targets, English evidence, duplicate completion blocks,
misleading alternatives or inappropriate page structures. The October 1
"Passed" visual-review record remains historical; these findings supersede it
as a usability assessment. The frozen HTML reproduces a proposal. It does not
override the agreed task specification or establish acceptance.

## Purpose And Appearance To Preserve

Floor guides should let an unfamiliar colleague complete one named job at the
device: identify the correct starting state, recognize the control, perform
the action, recover from a likely problem and verify the result. Start with a
real permitted action for the intended role. Keep prerequisite/exception
states attached to the relevant decision or help entry.

Use the established A4 portrait family colors, guide names, large step
numbers, smaller figure badges, captions, hints, bottom help and visible
completion. Preserve useful page width and the accepted practical text and
control sizes. A screenshot must answer where to act, what to choose or what
the result looks like. More screen area without a recognition purpose makes
the relevant control smaller. Matching frame dimensions do not establish
matching control size.

Sequential actions use consecutive numbers. Mutually exclusive routes share
one decision step and an explicit OF, with A/B labels and a named convergence.
A genuine continuation carries its numbering and shows a next-page handoff.
Independent maintenance tasks have their own clear route names and entry
points. Help remains a problem-to-action lookup; reference chapters use
concept headings. There is no universal three-card or screenshot-count target.

Keep short task cards compact. Preserve the necessary detail in multi-page
catalogue/admin guides. Extra pages need a task/legibility reason and clear
navigation; reducing page count is not a reason to shrink screenshots or drop
help. A complete field/choice explanation belongs before saving that choice.

## Disposition Of All Twenty Five Guides

The baseline column gives the next authoring starting point, not permission
to publish older instructions that conflict with changed application behavior.
The exact PDF paths and hashes are retained in the
[September kit manifest](../../../../output/manuals/current-guide-kit-2026-09-15/manifest.json)
and [October comparison manifest](../../../../output/manuals/aligned-guide-review-kit-2026-10-01/manifest.json).

| Guide | Needed purpose | Baseline -> October proposal | Recommended repair |
| --- | --- | --- | --- |
| AC-01 Login | Open Snipe-IT, sign in, recognize successful entry. | v9 -> v10; 1 -> 1 page | Keep accepted v9 composition and useful 2A/3A sizes. Remove recent-device instruction and unnecessary menu detail from the proposal. Only change evidence for a real login/dashboard difference. |
| AC-02 Eigen wachtwoord wijzigen | Replace the user's own local password and recognize success. | v4 -> v5; 1 -> 1 | Keep the three-action card. Capture the actual compact menu/three fields in Dutch at readable size. Explain repeated confirmation label and follow the visible password rule; verify success evidence. |
| SC-01 Asset vinden en openen | Find one asset by scanning or searching, open it and match the physical device. | v12 -> v13; 1 -> 2 | Rework as one page with an explicit scan OF manual-search choice. Integrate camera fallback and ambiguous-match help. Recent links may be an optional shortcut, followed by the same identity check. Remove duplicate page 2 route. |
| AST-02 Refurbishment-route | Identify the current phase and choose the correct task guide. | v8 -> v9; 1 -> 2 | Keep one-page route map. Update the short workflow/handoff descriptions and role cues. Put execution, QA notes and release dialogs in their owning guides. Remove the second walkthrough. |
| AST-03 Asset registreren en labelen | Register a physical asset once, print/place its label and verify by scan. | v15 -> v16; 2 -> 3 | Preserve the two-page sequence. Replace the entry/result/print evidence in the relevant steps; integrate creation rights and required status note before saving. Use one matching asset through creation and label check. Avoid the 1-8 -> 1-3 loop and unnecessary catalogue-creation prerequisite. |
| AST-04 Werk afronden en overdragen | Check completed current work and hand it to the supervisor with the required note. | v6 -> v7; 1 -> 2 | Integrate current-run readiness and QA-note entry in the existing checks/handoff. Show real note and saved handoff evidence. Keep quality/role exceptions short. Retain a second side only if the resulting task cannot remain readable. |
| AST-05 Asset beoordelen en vrijgeven | Review evidence/device, release OR return, confirm and verify the saved outcome. | v6 -> v7; 1 -> 2 | Important functional update. Continue numbering if a second side is needed. Show clean permitted release first, then cancellation/repair and authorized override as distinct branches. Use matching QA/start and saved end states. |
| WF-01 Workflow starten | Choose the next permitted workflow and start OR continue its run. | v13 -> v15; 1 -> 1 | Preserve the requested natural 2A Standard Diagnostics start with waiting follow-up and the clarified wording. Check Dutch capture labels, retained hints and recognition size. No exact acceptance is inferred. |
| WF-02 Workflow uitvoeren en afronden | Read the task, record truthful results/evidence, finish and return to the same asset. | v12 -> v13; 2 -> 2 | Keep its actual 1-6 continuation. Retain Pass/Fail versus Done/Not Done and saved-results/next-work distinctions. Use Dutch, legible evidence and clarify any comparison across different existing runs. |
| CMP-01 Bestaand component plaatsen | Install the correct already tracked tray/storage component and verify the link. | v6 unchanged; 1 page | Keep the existing task card. No new page justified by the October change list. Confirm affected controls before a later evidence-only revision. |
| CMP-02 Nieuw component registreren en plaatsen | Create/install one physical component, using a definition OR the approved custom route. | v6 -> v7; 1 -> 2 | Keep the explicit existing choice and one-page task. Put missing-definition help at the selection and a concise specification-result check at the end. Put aggregation-rule teaching in CAT-03/04. Do not silently withdraw the approved custom alternative. |
| CMP-04 Component naar tray | Remove the correct installed component and verify its tray location/unlinked state. | v7 unchanged; 1 page | Preserve the sequence, identity checks and complete Naar tray control. No batch-driven rewrite needed. |
| HELP-01 Problemen en hulp | Find a visible problem and its one appropriate recovery action/guide. | v7 -> v8; 1 -> 2 | Add/refine named lookup entries for blockers, notes, rights and printing. Keep no numbered sequence. A second side is legitimate only as another clearly indexed lookup area, without retelling CAT procedures. |
| USR-01 Gebruiker toevoegen | Create a local account with appropriate group/login state and hand over a temporary password. | v13 -> v14; 1 -> 1 | Keep the compact account task. Retain the useful Generate correction and remove the predictable fixed-password example. Use Dutch, readable form evidence and send detailed rights work to USR-02. |
| USR-02 Rol en rechten wijzigen | Give the intended user effective access for a specified task, then verify it. | v11 -> v12; 1 -> 2 | Place creation/quality/workflow rights before Save/test. Separate action permissions from status access and link to the relevant status/group control. A coherent continuation/reference side may be needed; remove the restart after completion. |
| USR-03 Wachtwoord resetten | Generate/save a temporary local password and hand it to the user for their own change. | v4 -> v5; 1 -> 1 | Preserve the single task and login-state boundary. Retain actual Generate/autofill behavior with Dutch evidence and a recognizable save/result step. Keep self-password change as the AC-02 handoff. |
| USR-04 Gebruiker uitschakelen | Disable login and verify the saved state while preserving the account identity. | v6 unchanged; 1 page | Keep the narrowed disable-login scope. Do not reintroduce deletion/restoration/check-in tasks. Previous review status still applies. |
| USR-05 Groepen beheren | Reuse/create/edit a reusable permission group and test effective ordinary-user access. | v2 -> v3; 3 -> 4 | Keep the reuse/edit/create routing and group sequence. Integrate general rights and effective-access checks; separate status-label configuration from group editing. Any extra reference side needs a clear task boundary and Dutch rights evidence. |
| CAT-00 Catalogus begrijpen | Explain identities, definitions, expected/physical records and which CAT task to choose. | v11 -> v12; 6 -> 7 | Keep its reference chapters. Add sum/distinct concepts in the specification/result explanation, with concept headings. Update titles and navigation. A seventh reference chapter can be justified; three procedural steps after completion cannot. |
| CAT-01 Model en modelnummer aanmaken | Find/reuse an exact catalogue identity, create only what is missing and choose the next object. | v7 -> v8; 5 -> 5 | Preserve the named A/B/C routes and readable destinations. Refresh materially changed Model Numbers search/entry evidence and Edit Spec handoff locally; no broader redesign. |
| CAT-02 Modelspecificatie opbouwen | Set a baseline's direct facts and expected components, resolve overlap and verify the saved specification. | v2 -> v3; 6 -> 7 | Integrate hidden/component-supplied attributes in the direct-fact choice and aggregation/source checks before saving. Replace affected old captures, not only add a warning later. Keep chapter navigation and coherent final verification. |
| CAT-03 Attributen beheren | Define a reusable fact and its allowed values, scope, behavior and numeric combination rule. | v2 -> v3; 5 -> 6 | Numeric aggregation is necessary new content. Place its conditional choice after datatype/behavior and before Save, with one real numeric example. Distinguish initial configuration from changing existing usage through CAT-05. |
| CAT-04 Componentdefinities beheren | Define a reusable component type with children, quantities and contributions, then verify the result. | v4 -> v5; 6 -> 7 | Integrate quantities/child precedence in the existing structure/contribution/overlap chapters. Link to CAT-03 for the combination rule. Remove the new post-completion search restart; preserve useful field explanations. |
| CAT-05 Bestaande catalogus aanpassen | Select one independent maintenance task and check the effect of changing an object in use. | v2 -> v3; 8 -> 9 | The aggregation-maintenance route is a valid separate task. Keep it indexed/named alongside other independent routes, using the established chapter appearance and local numbering. Include old rule, impact, actual numeric result and rollback/check outcome. |
| CAT-06 Productgegevens controleren | Check a doubtful identity/specification against a suitable source and return with the supported fact. | v2 unchanged; 3 pages | Keep the worked verification example and explicit confirmed/unconfirmed outcomes. Do not mix catalogue editing or aggregate configuration into this source-check task. |

## Concrete Recovery Brief

1. Retain AC-01 v9 as the login design reference and record v10's rejection.
   It needs no new page, recent-device instruction or automatic recapture.
2. Build one future SC-01 proof from v12 after the necessary Dutch capture
   settings are verified. Proposed flow: **1 Open de scanner; 2 Kies scannen
   OF handmatig zoeken; 3 Controleer de geopende asset.** Both choices reach
   the same tag/serial/model comparison. Keep the real QR photo, useful search
   evidence, captions, camera/no-result/mismatch help and visible completion.
   An optional recent-link hint must also lead directly to that identity check.
3. Compare that single proof with its baseline for purpose, branching,
   completeness, image/control size and A4 reading order. Record every intended
   difference before expanding the recovery to another guide.
4. Next prioritize AST-05's protected release, AST-04's required handoff note
   and AST-03's registration/result/printing changes. Older instructions about
   automatic release saving must not be presented as current behavior.
5. Integrate the permission and catalogue changes in their existing owning
   sections. Reuse established chapters, routes and Dutch canonical evidence.
   Scope any necessary new printer-admin instructions after the other agent's
   printer panel is ready in the intended capture build; avoid a new speculative
   floor-guide step about configuring CUPS.

## Gates Before Another PDF Delivery

- Write the job, starting state, choices, end state and page map before editing
  HTML. Every addition must have a named home in that task/reference structure.
- Verify global and account `nl-NL`, role and target application revision before
  capture. Inspect the visible controls; missing translations remain an
  application/evidence issue. Do not paint Dutch labels onto an English image.
- Inventory the baseline's text, images, captions, hints, help, completion and
  references. Account for each preservation/change/removal in the comparison.
- Compare actual control/text sizes after cropping and fitting, not only frame
  sizes. Preserve accepted target scale. Change/reflow the crop or local layout
  when necessary; do not silently reduce the whole guide's text or screenshots.
- Trace each alternative to its common next step or explicit return. Continue
  numbers across continuation pages; label independent routes and reference
  chapters. A completion block cannot precede required unfinished work.
- Show a genuine permitted normal state for the intended role first, then
  explicit exceptions. Use matching identity/state evidence through a claimed
  journey. Recognition examples must not imply a successful save/release that
  was never performed.
- Check actual A4 proofs, complete controls/callouts, references, help and end
  state alongside automated integrity checks. Do not call the set ready based
  solely on zero geometry errors or a contact-sheet pass.
- Deliver focused proofs and a short old/new difference list. Preserve exact
  version acceptance boundaries and historical artifacts.

## Review Evidence And Limits

All twenty-five October comparison PDFs / seventy-five pages were inventoried
through text and full-page contact renders. Complete 144 dpi AC-01 old/new and
SC-01 old/new pages were inspected, with enlarged checks of AC-02, AST-05 and
CAT-03's added page. The nineteen retained PDF hashes, their sixty-six final
page-raster hashes and all twenty-five packaged comparison PDFs were checked
against their frozen manifests. The sixteen-guide accepted package passed
its 369-file verifier; the prior twenty-five-guide kit passed 400 checksum
entries and remains sixty-one pages.

The mounted local application still comes from the refurbisher-followups
worktree, separate from this checkout's concurrent printer work. The locale
diagnosis used only language settings/accounts and translation resolution.
No operator/account/status form was saved. This is a documentation/visual
review; application tests, operational saves, physical printing and first-time
reader trials were not performed. Those limits do not prevent recording the
confirmed layout/branching/language failures above.
