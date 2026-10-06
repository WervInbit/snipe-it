# USR-05 / CAT-05 / CAT-06 Content Alignment Audit

Date: 2026-09-15  
Requested by: owner, after delivery of the three v1 drafts.  
Verdict: all three v1 PDFs need content revision. Preserve them as comparison
artifacts; the next authored versions must be v2.

## Summary

The three drafts inherit the guide colors and page furniture, but do not yet
provide the same instructional support as the current guides. General advice
often replaces the exact action, its control, the saved result, and the return
to the original task. Passing geometry and package checks did not establish
content alignment or independent usability.

| Guide | Principal defect | Required direction |
| --- | --- | --- |
| USR-05 v1 | Group rights are confused with effective user access; reuse/edit/create and testing are incomplete. | An executable group-management task that extends USR-02's permission model. |
| CAT-05 v1 | Maintenance rules are presented without complete action/save/result routes; alternative branches lead into one another. | Independent Admin maintenance procedures with explicit entry, impact checks, save, result, and return. |
| CAT-06 v1 | A second orientation/router replaces the source-verification task; internal drafting policy appears in operator copy. | One worked source check that resolves an uncertain identity or fact and returns to the requesting guide. |

Priority P1 means access or data-integrity wording that must be corrected
before operational use. P2 means an execution or consistency gap that must be
corrected before the next review candidate. Neither label implies a live
application defect.

## Comparison Baseline And Method

Use the exact selection in
[current-guide-selection-v4.json](../../../../resources/manuals/operator-guides/review-rounds/2026-09-10/current-guide-selection-v4.json),
checked against the [accepted package](../../../../resources/manuals/operator-guides/accepted-guides-v1/README.md).
The older portable `pdf/` manifest and historical registry paragraphs are not
the complete current comparison set.

| Reference | Version | Why it matters | Status |
| --- | --- | --- | --- |
| USR-01 Gebruiker toevoegen | v13 | Missing-group handoff; normal group assignment. | Owner-cleared for user review. |
| USR-02 Rol en rechten wijzigen | v11 | Inherited groups, direct exceptions, save/recheck, and USR-05 handoff. | Owner-cleared for user review. |
| CAT-00 Catalogus begrijpen | v11 | Object meanings, learning order, and maintenance/source ownership. | Owner-cleared for user review. |
| CAT-01 Model en modelnummer aanmaken | v7 | Concrete branch destinations, exact code, Primary handoff, and clone limits. | Owner-cleared for user review. |
| CAT-02 Modelspecificatie opbouwen | v2 | Saved-row cleanup handoff; direct/derived fact boundary; save/result. | Unaccepted comparison draft. |
| CAT-03 Attributen beheren | v2 | Attribute meaning, Enum options, lifecycle handoff, and saved result. | Unaccepted comparison draft. |
| CAT-04 Componentdefinities beheren | v4 | Saved contributions/children cleanup; Save Changes and reopen verification. | Unaccepted comparison draft. |

All ten pages of the three audited PDFs were read and visually inspected.
The seven reference PDFs were read in full; representative USR-02, CAT-01,
and CAT-04 pages were rendered and inspected for instructional presentation.
Behavior-sensitive findings were checked in repository views/controllers,
the permission resolver, and the operational role matrix. No browser mutation,
permission change, deletion, or operational user trial was performed.

The comparison drafts are useful references, not independent proof of product
correctness. Current application behavior and recorded owner decisions resolve
conflicts. Existing accepted pages are not silently revised by this audit.

## Findings: USR-05

### U1 - P1: distinguish group grants from effective user access

Location: page 2, introductory line, steps 4-6, especially caption 5B,
`Leden links; effectieve rechten rechts.`

That screen lists the group's stored permissions. It does not compute the
effective rights of each member. USR-02 v11, steps 3-4, explicitly teaches that
the user's other groups and direct Toestaan/Weigeren choices also determine
access. Group Weigeren is a zero grant from that group; it is not the direct
per-user denial described in USR-02. Another group may still grant the right.
Calling this result effective access can make a Superadmin believe a permission
has been removed from every member when it has not.

Evidence: [group detail view](../../../../resources/views/groups/view.blade.php),
which iterates `decodePermissions()`, and
[User::checkPermissionSection](../../../../app/Models/User.php), which checks
direct allow/deny before looking for a grant from any group. Broad administrator
and Super User abilities also need to be accounted for when testing.

Rework: label 5B `Rechten van deze groep`; explain group Weigeren beside its
first use. Keep per-user changes in USR-02. Specify a test account whose other
groups and direct exceptions are known, and demonstrate one allowed and one
disallowed task. A successful check on the Superadmin's own session is not
evidence of the group's access.

### U2 - P2: make reuse, edit, and create actual alternative routes

Location: page 1, steps 1-2 and the page handoff.

The title promises creating or changing a group. Step 2 offers only reuse or
creation, does not explain how to open/edit an existing group, and sends every
reader to naming and changing permissions on page 2. It never identifies where
the reuse-only reader finishes or returns to USR-01/02. The list itself does
not reveal enough permissions to establish the claimed exact equivalence.

Rework: open a candidate group and compare its rights and members; then choose
reuse unchanged, edit for all affected members, or create a missing group.
Show `Groep bewerken` (or the verified edit control), and give each choice a
specific destination. Return a missing-group caller to USR-01 step 3 or USR-02
step 2. Preserve unsaved account input when leaving the calling form, as the
CAT-02/04 handoffs already teach for their forms.

### U3 - P2: replace the generic rights checklist with a worked permission task

Location: page 2, steps 3-6 and images 4A/5B.

`Taaklijst + testgebruiker`, a task-based name, and required/forbidden routes
are named but never made concrete. Screenshot 4A shows a Models section,
without the Toestaan/Weigeren headings and without the caption's readable Users
example. The group-level Models radio can change every row in that section;
`Lees elke rij als een zelfstandige bevoegdheid` does not explain that control.
Screenshot 5B is the existing Admin group, not a demonstrated saved result for
the unnamed narrow group in the instructions.

Evidence: [group edit view](../../../../resources/views/groups/edit.blade.php)
has section-header bulk toggles. The
[operational role matrix](../../../v1-operational-role-capability-matrix.md)
also states that deployment seeding restores required grants on the foundation
groups; narrowing one of those groups is not a durable custom-role example.

Rework: use one clearly named custom example with a concrete work requirement,
show the exact individual rights and column headings, explain section-wide
toggles, and verify the same group's saved state. Distinguish an illustrative
screen from the actual result. Keep normal deployed role names and their
minimum-rights policy aligned with USR-01/02.

## Findings: CAT-05

### C5.1 - P1: Primary restoration does not match the named browser route

Location: page 2, Route A / Primary card.

The page starts at `Instellingen > Model Numbers` and says making a deprecated
row Primary restores it. The named list exposes Edit, Edit Spec, and delete;
its Edit form disables the default checkbox for a deprecated row and explicitly
says to restore the preset first. There is a separate controller action that
restores while making Primary, but that does not make it an available action
in the illustrated route.

Evidence: [Model Numbers list](../../../../resources/views/settings/model_numbers/index.blade.php),
[Edit form](../../../../resources/views/settings/model_numbers/edit.blade.php),
and [controller](../../../../app/Http/Controllers/Admin/ModelNumberSettingsController.php).

Rework: choose one real route and show it completely. For the illustrated
global edit form: restore Status to Active, save, reopen, set the default,
save, and confirm Primary. Explain Primary as the standard choice for new
assets, not approval. Explain how to choose another default before deprecating
the current Primary. Preserve CAT-01's ownership of exact-variant creation.

### C5.2 - P1: the destructive-cleanup prerequisite is not executable

Location: page 1 impact callout; page 3 Route B; pages 4-5 cleanup cards.

The reader must check assets, models, contributions, children, and overrides,
but is not told where to find them, what to compare, or when the result is
sufficient. Page 3 acknowledges irreversible loss but makes an old-value note
optional and then asks for a check of only one affected asset. Removing a
saved direct attribute also deletes that attribute's overrides on all assets
using the model number. A note of the model value alone cannot recover those
different per-asset values. The screenshot shows where to remove a row, not
how to satisfy the prerequisite for doing so.

Evidence: [ModelAttributeManager](../../../../app/Services/ModelAttributes/ModelAttributeManager.php)
removes both assignments and matching overrides. The
[attribute edit form](../../../../resources/views/attributes/edit.blade.php)
has a real usage summary which the draft does not show; it is not a complete
cross-record recovery or per-option usage report.

Rework: give each destructive branch a concrete pre-change checklist and the
actual screens available for those checks. Explain when Save commits removal
and what Cancel can still discard. Distinguish clearing a catalogue expectation
from physically removing a Tracked component. If the affected data cannot be
established, provide a clear unresolved-impact handoff before removal. Do not
invent an impact-report button or imply a usage count is a complete recovery
record. Verify any proposed preservation procedure on disposable data.

### C5.3 - P2: the five-page branch structure contradicts itself

Location: page 1 route cards; page 2-4 footers; page 5 completion.

Page 1 calls A-D alternatives but gives no page numbers. Route A then says
`Volgende pagina route B`, B sends the reader to C, and C sends the reader to D.
Only D has `Klaar als`. This makes the unrelated maintenance tasks look like
the mandatory sequence needed to finish. CAT-01 v7 page 2 is the direct
reference: its alternatives name page and step destinations, including skips.

Rework: map A/B/C/D to exact pages and actions. End each branch with its own
saved-result check and return to the original CAT-01/02/03/04 task. Use an
optional branch index when another task really is required; do not label it
`Volgende pagina` as a required continuation.

### C5.4 - P2: lifecycle cards do not contain the promised procedures

Location: pages 2-5, especially C/Enum and D/saved relationships.

The draft shows list views and removal controls but omits most edit forms,
state selectors, confirmation/save controls, restored/hidden/inactive results,
and reopened saved values. The page-2 example contains Primary rows with
Assets > 0, not a row eligible for the other actions. Route C never opens the
Enum option list or shows Remove/Active and Opslaan. Route D never shows a
saved contribution/child, its Remove control, Save Changes, or the result.
These are precisely the missing tasks sent here by CAT-02 v2 page 5, CAT-03 v2
page 5, and CAT-04 v4 pages 5-6.

Rework: extend each branch to navigation -> exact object -> meaning/impact ->
one action -> save/confirmation -> reopened result -> return. Reuse suitable
CAT-02/03/04 evidence and add only the missing lifecycle/after-state captures.
Keep a clear distinction between an option's Value and Label, and between
definition status and a physical component's state. Page count follows the
complete instructions; five pages is a target, not a reason to omit actions.

### C5.5 - P2: preserve established meanings instead of vague maintenance jargon

Location: pages 1-5, especially the copy note and final work-item instruction.

The draft uses `in-use`, `cleanup`, `systeemkey`, `kindstructuur`, `relaties`,
and `selectorzichtbaarheid` without the local explanations used in CAT-03/04.
The copy note replaces CAT-01 v7's explicit list of what is not copied with
`beperkte snelweg`. The last page introduces mandatory logging in `het werkitem`
without an established work-item prerequisite or a location to do it.

Rework: use plain Dutch with the exact UI label at the action. Keep CAT-01's
explicit copy limits or hand off to its full named guide. Retain the correct
absence of component-definition browser deletion as a short relevant note.
Do not introduce a new mandatory logging workflow without a defined process.

## Findings: CAT-06

### C6.1 - P2: show how to verify a source, not only which categories to trust

Location: pages 1-2, physical-label images 1A/2A and source footer.

Both evidence images are crops of the same label. There is no manufacturer
source title, exact-code match, example specification, or comparison showing
why a family document with several configurations is insufficient for one SKU.
The footer nevertheless says `Bron: fysiek label + fabrikantdocumentatie`.
The generator/evidence record contains only the physical-photo source, so that
wording overstates the retained evidence. CAT-01/02 and CAT-03 expect this guide
to resolve their source prerequisite, which the generic hierarchy does not do.

Rework: follow one actual manufacturer code from the readable physical label
to a documented authoritative source, and demonstrate an identity match and
one supported fact. Show what happens with a generic family sheet, regional
suffix mismatch, missing label, and conflicting facts. Distinguish a known
factory baseline from a later RAM/SSD change. Attribute the actual retained
source accurately. Source lookup in a later revision needs fresh verification;
this audit does not certify an unseen vendor page.

### C6.2 - P2: keep CAT-00's orientation and make the return path specific

Location: page 2 identity/fact cards and page 3 guide router.

The draft largely repeats CAT-00's identity, factory/asset distinction, and
guide selection. It does not turn them into a worked verification result.
`standaard RAM, standaard opslag of verwachte componenten` can be read as
alternative ways to record the same hardware, whereas CAT-00 v11 page 5 and
CAT-02 v2 page 2 route replaceable hardware through expected components and
warn against duplicate direct values. The final router uses shortened titles
and combines CAT-03/04 without an exact return point. Physical changes are
described but have no AST/CMP task handoff.

Rework: retain a brief identity reminder, then demonstrate the source check.
Return verified identity to CAT-01, a verified factory fact to CAT-02, missing
meaning/type to CAT-03/04, and existing lifecycle/cleanup to CAT-05. Use full
registered names, family markers, and specific resume points. Route actual
per-device hardware changes to the appropriate AST/CMP guide. Align exact-code
case handling with CAT-01's normal capitalization and exceptional Aa behavior;
CAT-06 should establish the source spelling rather than teach another entry
policy. Replace `punctuation` and `case` with ordinary Dutch.

### C6.3 - P2: remove authoring policy from the operator task and separate outcomes

Location: page 3, `Bekende grens van v1`, `Niet gokken`, and `Klaar als`.

`Deze v1 is daarom een verificatiegids; leg geen niet-bestaande
bronregistratieprocedure op` addresses the guide author. The unresolved
Inbit-approved fallback source is presented as though the operator has an
available list or decision procedure. `Laat het veld leeg of stop de taak`
does not distinguish an optional field from a required identity or specification.
Finally, the green completion sentence treats verified facts and an unresolved
halt as the same end state.

Rework: keep the absence of a source field and unresolved storage policy in
the review record. Operator copy should state the next concrete action when a
source is missing or conflicting. Leave only genuinely optional facts blank;
withhold an uncertain required identity or value and identify the information
to give the Supervisor/catalogue owner. Separate successful verification from
an unresolved handoff. A verification-only guide can be useful without
inventing a durable in-application source record.

## Shared Alignment Corrections

These are applications of existing rules, not a new visual redesign:

- Restore the familiar `Rol / Nodig / Vooraf` context. USR-05's login
  prerequisite and CAT-05/06's CAT-00 prerequisite exist in their specifications
  but are absent from the printed context. `Adres`, `Regel`, `Admin`, and
  `Doel` currently displace that information.
- Give each real handoff a full registered name and family marker. USR-05's
  inline USR-02, CAT-05's inline CAT-01/03, and CAT-06's abbreviated router do
  not consistently follow the reference contract. Footer chips alone do not
  identify where the caller resumes.
- Add compact recovery instructions where the reader can get stuck: missing
  group controls, unexpected access, unavailable lifecycle controls, protected
  deletion, conflicting sources, and interrupted forms. Preserve the existing
  help convention; generic warning cards do not replace task-specific help.
- Keep one worked identity per route. Label alternate screenshots honestly;
  do not present the existing Admin group or an unrelated component list as
  the saved result of the worked example.
- Retain useful screenshots and make their relevant controls readable.
  USR-05's narrow permission/result strips omit information needed by their
  captions. In CAT-05, many needed action/result states are absent altogether.
  New colors, a uniform grid, or a smaller page count will not fix this.

The existing [system](../system.md), [CAT family plan](../catalog-guide-plan.md),
[maintenance contract](../maintenance.md), and
[owner pilot feedback](2026-09-08-pilot-direction-feedback.md) already require
these behaviors. No change to shared visual components is justified by this
audit alone.

## Concrete V2 Content Brief

### USR-05

1. Begin from login and a defined group-wide task; show the Groups navigation.
2. Open/inspect a candidate; choose reuse, edit for all members, or create.
3. Use one named custom example; explain individual and section-wide controls.
4. Save and reopen that group; check its stored grants and affected members.
5. Use USR-02 to assign the group and inspect direct exceptions; test the named
   tasks with the controlled user; return to the original account task.

Keep the two-sided target if it accommodates the evidence and help legibly.

### CAT-05

1. An index with four independent branches, their page destinations, and the
   object each branch changes.
2. Route A: exact model number -> current use/default -> status or default
   action -> save -> Primary/Active/Deprecated result -> return to CAT-01.
3. Route B: exact saved specification -> direct/expected row and affected
   values -> remove -> Opslaan -> reopened specification/affected asset ->
   return to CAT-02's conflict check.
4. Route C: definition and actual usage -> hide/unhide or existing Enum-option
   maintenance -> correct save/result -> return to CAT-03/original selector.
5. Route D: definition and its uses -> status or saved contribution/child
   maintenance -> Save Changes where applicable -> reopen values/counts ->
   return to CAT-04/original selector.

Each branch needs its own completion and recovery. Investigate any inaccessible
usage detail before authoring a removal instruction that depends on it.

### CAT-06

1. Identify the question and read the actual label; explain which identifier
   to use for the worked example.
2. Find and compare an authoritative source for the exact variant; verify one
   concrete fact, including unit/configuration, and handle disagreement.
3. Produce either a supported identity/fact with a precise task return or an
   explicit unresolved handoff. Keep factory facts and physical changes apart.

Use space for actual evidence and decisions instead of repeating CAT-00's map.

## Dependency And Version Impact

- Author revised specifications before generating USR-05 v2, CAT-05 v2, and
  CAT-06 v2. Preserve the three exact v1 PDFs, hashes, and review history.
- Keep the current registered titles during the focused correction unless
  the owner also chooses the already-proposed CAT-05 title change. A title
  change affects every incoming reference.
- USR-01 v13 and USR-02 v11 still print `gids volgt` for USR-05. CAT-00 v11,
  CAT-02 v2, CAT-03 v2, and CAT-04 v4 contain planned-guide wording for these
  dependencies. When the replacements are ready, revise those references in
  new versions; do not patch their retained PDFs.
- CAT-01 v7 and AST/CMP routes are content alignment references. Do not widen
  this audit into a whole-set rewrite or treat the unaccepted field-to-result
  prototype as an approved replacement design.

## Re-review Criteria

Trace each route from its actual caller, through every printed action, to the
saved/verified result and back. Every required control must be recognizable;
every branch must finish without performing an unrelated branch. Check the
worked identities, full guide titles, minimum roles, ordinary Dutch, help,
optional/required data distinction, and effective-access semantics.

Then perform targeted controlled-user and disposable-data exercises for the
new operational instructions and visually review the new PDFs at A4 scale.
Do not claim those exercises from generation or geometry checks. No full
application test run is needed for this audit-only documentation pass.

## Audited Artifact Identity

| Guide | Pages | SHA-256 |
| --- | ---: | --- |
| USR-05 v1 | 2 | `7cd6c0b6ae1e7ab1bb8004165fe6de6f40635e079c2867fe53aeb744dd46807c` |
| CAT-05 v1 | 5 | `e8b8e2baff42956c01a060b3f01589e6722ebdce4512446ef0cd1c0910348921` |
| CAT-06 v1 | 3 | `1eee3e99c7bb7dfe789688abfe8b1e2f34023334aa6dd77b9f7f570f4fcad59a` |

Exact files and original validation evidence are in the individual
[USR-05 v1](USR-05-v1.md), [CAT-05 v1](CAT-05-v1.md), and
[CAT-06 v1](CAT-06-v1.md) review records.

Audit validation: all three subject PDFs and their portable copies match the
hashes above; all seven comparison PDFs match the selected manifest. The 45
local links in the audit/review/handoff/session documents resolve. Git's
whitespace check passes. These checks establish source identity and document
integrity, not completion of the proposed operational tests.
