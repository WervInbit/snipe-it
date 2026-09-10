# Independent Operator Guide Usability And Consistency Audit

Date: 2026-09-08. Baseline: local commit `158d1b32cf`.
Status: completed inspection; corrections and user validation remain open.

## Verdict

The set has a useful visual foundation, but I would not release the current
set for independent first-time use. Several instructions require prior system
knowledge, some screenshots contradict the intended action, and a few routes
cannot be completed by the printed role. These are instructional problems,
not merely cosmetic differences.

Retain the family markers, recognizable context strips, attached screenshots,
physical/digital identity checks, and separation of catalogue definitions from
physical component records. Correct the task logic and evidence before another
round of visual polishing. Passing package checks or an earlier internal review
does not resolve the findings below.

This is an expert inspection for first-time users, including autistic users
who benefit from explicit steps and predictable presentation. It is not a
claim that all autistic users have the same needs, a WCAG certification, or a
substitute for observed use by the intended readers.

## Scope And Method

- Read all 48 pages of the 22 current generated guides, using extracted text
  and freshly rendered Poppler page images. Inspected all pages in contact
  sheets and enlarged the ambiguous identity, form, warning, and layout areas.
- Selected current artifacts from the draft manifest, falling back to the
  accepted manifest for SC-01 v10 and AST-03 v14. Checked all 29 stored manifest
  PDFs for hashes, page counts, and dimensions. The seven other accepted
  predecessors received integrity/text checks, not a separate full visual audit.
- Reviewed the shared system, components, layouts, maintenance contract,
  registry, decisions, handoff, CAT family plan, relevant specifications and
  review records, including the August cold-start audit and September recapture.
- Reviewed planned USR-05, CAT-05, and CAT-06 as dependencies. No PDF exists for
  these three; a planned specification is not an executable operator handoff.
- Cross-checked high-risk claims against local application views, controllers,
  services, seed definitions, and existing test contracts. This was targeted
  source inspection, not a complete permission or runtime test matrix.
- No application login, production access, database mutation, capture session,
  guide regeneration, policy change, or review acceptance occurred.
- Physical print legibility, actual user performance, assistive-technology
  behavior, and parity with the deployed interface were not tested. A4 point
  sizes were measured from PDF text, not inferred from on-screen enlargement.

The accompanying [audit evidence inventory](2026-09-08-audit-evidence.json)
records exact PDF hashes, page dimensions, current-version selection, and
measured text samples. Local scratch renders and extraction are under
`output/manuals/audit-2026-09-08/` and are not published artifacts.

W3C guidance supports making the current step and important choices clear,
including after interruption: [Make Each Step Clear](https://www.w3.org/WAI/WCAG2/supplemental/patterns/o1p04-clear-steps/).
It also supports stable visual meaning across pages:
[Use a Consistent Visual Design](https://www.w3.org/WAI/WCAG2/supplemental/patterns/o1p03-consistent-design/).
The plain-language assessment uses short, unambiguous instructions and clear
images as practical criteria:
[Use Clear and Understandable Content](https://www.w3.org/WAI/WCAG2/supplemental/objectives/o3-clear-content/).
These are supplementary web accessibility patterns applied by analogy to the
printed guides; they do not impose a particular A4 font-size threshold.

## Severity

- **P1:** correct before independent use of the affected route; an ordinary
  documented scenario can lead to the wrong record/action or prevent completion.
- **P2:** a material comprehension, navigation, evidence, or readability issue;
  correct before declaring the affected guide suitable for first-time use.
- **P3:** maintenance/distribution weakness; address before packaging the set.

Severity describes the guide's user impact, not a software vulnerability score.
Known gaps are identified explicitly; this audit does not count them as newly
discovered application defects.

## Findings

### AUD-01 - P1 - The account-creation role cannot assign the instructed group

**Where:** USR-01 v11 p1, context and step 3; USR-02 v9 p1, step 2.

USR-01 names Admin and requires choosing the standard group. USR-02 correctly
says only Superadmin can change group membership, but still presents that
action as a normal numbered step under an Admin context. The
[user form](../../../../resources/views/users/edit.blade.php) makes the group
selector unavailable to non-superusers; the
[privilege service](../../../../app/Services/Users/UserPrivilegeService.php)
rejects their submitted group changes. The
[controller](../../../../app/Http/Controllers/Users/UsersController.php)
syncs groups only for a superuser. The foundation Admin group grants `admin`,
not `superuser`.

**Impact:** the instructed Admin cannot finish the standard-role outcome.
**Correction:** distinguish account creation from group assignment. Either
use a Superadmin route for the complete task or provide an explicit handoff
and verified return state. Mark USR-02's group branch by role before its action.
Changing application permissions is a separate product decision, not an audit fix.

### AUD-02 - P1 - User screenshots change identity within the same task

**Where:** USR-03 v3 p1 images 1B/2A;
USR-04 v3 p1 images 1B/2A/3A/4A.

The search/list evidence shows Mila de Boer / Miladb. USR-03's password form
shows `demo_refurbisher`; USR-04's deactivation form shows Demo Refurbisher /
`demo_refurbisher`, then its result returns to Mila. USR-01's saved result also
shows Demo Refurbisher while its prose uses Jan as an example; that is a
secondary example-coherence issue, not proof of changing the wrong record
during creation. A creation list containing other people is not itself wrong.
The [screenshot catalog](../screenshots.md) describes the account
batch as using a fictional Mila identity; the actual PDFs do not consistently
preserve it.

**Impact:** the visual sequence teaches accepting a different person at the
moment of password or access mutation, despite the identity-check instruction.
**Correction:** use one fictional identity throughout each end-to-end sequence,
including the form and saved result. Mark a different illustrative name as a
separate example. Audit all uses of the shared user evidence before recapture.

### AUD-03 - P1 - Delete and restore are presented as one required sequence

**Where:** USR-04 v3 p2, steps 5-8, prerequisite, and completion row.

The page says 'Verwijderen of herstellen', but the numbered flow deletes,
finds the deleted account, restores it, then declares success only when it is
restored. A reader following every step undoes the deletion. A reader who
needs to restore an already deleted account is told that page 1 must first
be completed, although that account is no longer in the normal active route.
Image 5A highlights both ordinary Delete and the discouraged bulk-delete action.

**Correction:** make separate branches for deactivate, delete, and restore,
each with its own starting condition and completion. The deletion branch must
end at verified deletion. The restore branch starts from deleted users and
checks the proposed access before restoration. Focus the action to use; mark
the bulk action separately as a prohibition if it must be shown.

### AUD-04 - P1 - Password recovery can bypass a separate access decision

**Where:** USR-03 v3 p1 help 'Account staat uit'; USR-04 v3 p2 steps 7-8.

USR-03 allows enabling login when this is the correct user. Identity alone
does not establish why the account was disabled or whether access should
resume; USR-04 explicitly requires a separate access decision. USR-04 also says
restoration does not automatically activate login and illustrates login still
off. That is true for its deactivated example, but not a guarantee for every
deleted account: the local restore handler restores the record without forcing
`activated` to false.

**Correction:** password reset must preserve a disabled state pending a
reactivation decision. Explain that restore preserves the prior activation
state, verify it before relying on the restored account, and distinguish
successful identity verification from permission to resume access. These are
inspection findings; no live account was tested.

### AUD-05 - P1 - Product ID guidance contradicts the registration guide

**Where:** CAT-01 v5 p4 step 6 warning; AST-03 v14 p1 steps 2-3;
CAT-06 planned 'Evidence hierarchy'.

AST-03 correctly separates S/N from the product/type code and explicitly tells
the operator to use an HP Product ID/P/N to verify model type. CAT-01 then says
'Geen serienummer, Product ID, Inbit-tag' under the exact manufacturer-code
field. CAT-06 repeats the blanket Product ID exclusion. A user carrying the
same physical label from registration to catalogue setup receives conflicting
instructions.

**Correction:** distinguish the manufacturer's product/SKU/P/N field from
unrelated software Product IDs and the serial number. Use one annotated
physical-label example and one shared terminology rule across AST-03, CAT-00,
CAT-01, and CAT-06. Do not exclude a label solely because it says Product ID.

### AUD-06 - P1 - The illustrated hierarchy warning cannot be produced as shown

**Where:** CAT-04 v2 p5 image 5A and its correction text.

The screenshot says Motherboard and RAM both contribute `Geheugentype`, while
the visible child row is a USB port. The
[capture helper](../../../../scripts/manuals/capture-catalog-guide-evidence.mjs)
injects that alert into the DOM. The actual
[warning service](../../../../app/Services/Components/ComponentDefinitionHierarchyWarningService.php)
filters to numeric specification contributions; the
[catalogue seed definition](../../../../database/seeders/Concerns/ProvidesDeviceCatalogData.php)
defines `Geheugentype` as Enum. This is more than an anonymized identity: the
example does not demonstrate the application's warning conditions.

**Correction:** use a coherent numeric example with matching parent, child,
attribute, alert, correction action, and final result. Revalidate the mechanism
before promoting a simulated state. The September checkbox recapture alone
does not repair this semantic error.

### AUD-07 - P1 - The existing-run decision follows the new-run instruction

**Where:** WF-01 v10 p1 step 3; AST-02 v6 p1 workflow-start row.

The primary instruction is to click 'Nieuwe workflow starten' once. Only below
that button does the reader encounter the alternative for a correct unfinished
run. The title promises to continue or start, but the visual order exposes the
mutation before the decision. A literal sequential reader can create a new run
before learning that the existing one should be used.

**Correction:** place the existing-run check before either action. Explicitly
state the condition for each route and rejoin at the card check. Preserve the
approved ability to start a genuinely new run; this is a proposed revision to
the current primary/alternative layout decision, not a hidden application fix.

### AUD-08 - P1 - One successful latest workflow is not the release criterion

**Where:** AST-04 v5 p1 step 1; AST-05 v5 p1 step 2; WF-01/WF-02 handoff.

AST-04 asks for 'de laatste verplichte workflow'. AST-05 illustrates one
`5 Geslaagd, 0 Mislukt` row as the evidence check. The application evaluates
every active applicable sale-blocking profile and verifies that its run matches
the current model and readiness context. A latest successful run can coexist
with another missing profile or outdated evidence. See
[WorkflowReadinessService](../../../../app/Services/WorkflowReadinessService.php)
and [Asset::blockingSaleReadinessProfiles](../../../../app/Models/Asset.php).

**Correction:** show how to identify the required profile set and its current
evidence, then show the missing/outdated/failure warning route. Explain that
truthfully completing a run with failures differs from meeting release
criteria. Never teach changing failures to passes simply to clear a count.
No production profile configuration was inspected.

### AUD-09 - P1 - Identity mismatch has incompatible recovery instructions

**Where:** AST-04 v5 p1 step 2; HELP-01 v6 p1 STOP; SC-01 v10 p1 step 4;
AST-05 v5 p1 identity help.

After comparing tag, serial, model type, and physical device, AST-04 says to
correct the record or component before handoff. HELP-01 and SC-01 say to change
nothing and escalate an identity mismatch; AST-05 says not to change data to
make it fit. AST-04 does not first distinguish opening the wrong valid record
from a verified error in the correct record.

**Correction:** first stop mutation, revalidate the physical device and record,
then distinguish a selection error from an authorized correction. Use that
same recovery order in every family. Component repair can still have its own
route after identity is established.

### AUD-10 - P1 - Reconsider the accepted predictable first-login password policy

**Where:** USR-01 v11 p1 step 2; USR-03 v3 p1 step 2;
[decision log](../decisions.md), 2026-08-13 temporary-password decision.

USR-01 instructs username plus the year (`Jandv2026`), whereas reset uses
`Genereer` and rejects a fixed self-invented password. The creation rule is an
explicitly accepted project policy, not an accidental rendering defect. It
makes the initial secret predictable from the username and year. The decision
log also records that immediate replacement is not enforced at next login.

**Correction proposed for policy review:** use one generated temporary-password
route for creation and reset, with personal transfer and immediate private
replacement. Update the policy before revising guides. This audit has not
changed that accepted policy or any account.

### AUD-11 - P2 - Critical instructions are too small to accept on screen alone

**Where:** cross-set typography; measured samples below.

| Exact instruction sample | Guide/page | PDF size |
| --- | --- | ---: |
| 'Tik op de snelkoppeling.' | AC-01 v8 p1 | 7.22 pt |
| 'Houd de QR rustig in beeld.' | SC-01 v10 p1 | 6.66 pt |
| 'Kies het afgesproken profiel.' | WF-01 v10 p1 | 7.22 pt |
| Username convention | USR-01 v11 p1 | 6.66 pt |
| Duplicate-search instruction starting '2A Zoek bij Asset' | CAT-01 v5 p1 | 5.61 pt |
| 'Exacte fabrikant-/SKU-code' | CAT-01 v5 p4 | 5.52 pt |
| 'Bekijk Derived...' | CAT-02 v1 p5 | 5.80 pt |
| Numeric constraint explanation | CAT-03 v1 p4 | 5.15 pt |
| Hierarchy correction instruction | CAT-04 v2 p5 | 5.15 pt |

These are instruction sizes, not just footer metadata. Several occupy roomy
cards. Large step badges and substantial whitespace do not compensate for
small decision text or tiny screenshot controls.

**Correction:** prioritize instruction and target readability when allocating
space. Start a larger-body prototype, print at 100% A4 without fit-to-page
shrinkage, and test at the working distance and lighting. Apply the existing
sparse-region enlargement rule to every affected guide. Do not declare a
universal point-size compliance threshold from this inspection.

### AUD-12 - P2 - Screenshots cover instruction text in user guides

**Where:** USR-03 v3 p1 step 1; USR-04 v3 p1 step 1 and p2 step 7.

On the enlarged rendered pages, the left screenshot and its badge overlap the
end of the step heading; USR-03's first body line also extends behind the
navigation screenshot. USR-04's restoration heading reaches the 7A badge.
This is actual source-page overlap, not a contact-sheet crop.

**Correction:** keep the complete heading/body region clear of the screenshot
and badge region, wrap the text, or put the heading above both columns. Add
text-versus-image intersection checks; existing badge/reference geometry
checks can pass while instructions remain covered.

### AUD-13 - P2 - Required form actions are left for the reader to infer

**Where:** CAT-01 v5 p4 step 6; CAT-03 v1 p4 image 4B;
CAT-04 v2 p3/p4; USR-02 v9 p1 step 2.

- CAT-01 highlights Opslaan but does not explicitly instruct that click before
  the next page checks an already saved identity.
- CAT-03 says to add each Enum value but never names `Add to list`; the button
  is cut at the bottom of the example crop. Bool/Text readers have no explicit
  instruction to skip both the numeric and Enum alternatives.
- CAT-04 shows populated child/contribution rows and explains their fields,
  but does not teach the initial add-row action at the point it is needed.
- USR-02 omits opening `Optionele informatie`, although USR-01 explicitly
  teaches it. The form starts collapsed unless the browser remembers it open.

**Correction:** state each required action and its result in reading order,
including Add, select, enter, confirm, and Save. Make skip/return destinations
explicit. A control appearing in an image does not replace an instruction.

### AUD-14 - P2 - Some completion checks are assertions without visible proof

**Where:** AC-02 v3 p1 step 3; USR-04 v3 p1 image 4A and p2 image 8A;
CAT-04 v2 p6; WF-02 v11 p2 step 6.

AC-02 says to read the message, but does not name or show success versus error.
USR-04 says `Login ingeschakeld` must be `Nee`, while its result crops show
name, username, group, email, and last login, not the named activation field.
CAT-04's index verifies identity and counts, not the actual saved contribution
values and child quantities in its completion statement. WF-02 jumps from an
active card to a history row without saying how to return to that view or what
save feedback to wait for.

**Correction:** name a visible saved state, show the relevant field, and reopen
the edited data where the index cannot prove it. Teach navigation back to the
history view and the save indicator. Local code autosaves result updates; this
audit does not assert that a missing final Save click prevents persistence.

### AUD-15 - P2 - Several handoffs have no executable destination or return point

**Where:** USR-01/02 references to USR-05; CAT-01/02/03/04 references to
CAT-05/06; CAT-02 p2 missing-definition branches; CMP-02 p1 missing-definition help.

USR-05, CAT-05, and CAT-06 have no generated guides. Current rules allow
references to explicitly planned guides even in a review candidate, but that
does not make the route usable. A Senior Refurbisher sent from CMP-02 to the
Supervisor CAT-04 is not explicitly told who takes over or where to resume.
CAT-02 can send the reader away from an unfinished form to create a definition
without specifying how unsaved work is preserved or how to return.

**Correction:** provide an actionable interim person/role handoff while a guide
is absent. Name the return guide, step, record, and expected state. Resolve
missing definitions before data entry where possible; verify the application's
safe save/cancel/new-tab behavior before prescribing a way to retain work.

### AUD-16 - P2 - Navigation labels do not always mean the same thing

**Where:** SC-01 v10 p1 steps 2-3; AST-03 v14 p1 images 1A-1C;
CAT-01 v5 p2 branches/footer; CAT-03 v1 p3 numbered field cards.

SC-01's manual fallback is a full numbered step after scanning, without an
explicit successful-scan jump to step 4. In AST-03, 1A is a prerequisite and
1B/1C are alternatives, although the shared rules associate lettered images
with equivalent choices. CAT-01 gives branch-specific jumps but then has a
general next-page instruction to create a missing base model. CAT-03 restarts
numbered circles at 1-5 for field descriptions inside step 3.

**Correction:** preserve flexible layouts while standardizing semantic cues:
numbered action, labelled choice with a condition, supporting image, optional
field, and next destination. State 'scan gelukt: ga naar stap 4' explicitly.
Use field labels instead of a second apparent task sequence. Make branch
footers conditional rather than competing with the chosen route.

### AUD-17 - P2 - Warning and guide-reference conventions drift across families

**Where:** CMP-02 v4 p1 step 3, WF-02 v11 p2 step 6, HELP-01 v6;
AC-01 v8, SC-01 v10, AST-02 v6, WF-01 v10, WF-02 v11, CMP-01 v5 references.

Recoverable duplicates/incomplete results still receive STOP styling, while
other guides use amber corrections. CAT-04 p5 even explains that the warning
is 'geen STOP', exposing the author's classification instead of the user's
next action. Many older-family references shorten names to Openen, Start,
Route, or Hulp despite the full-name contract. Multi-page headings also rename
the task without always retaining its full registered name.

**Correction:** reserve STOP for the defined halt conditions, name the actual
recovery, and use complete registered task names consistently. Preserve the
guide title on both sides and use a separate page subtitle. Check existing
record/identity risks separately from ordinary validation errors.

### AUD-18 - P2 - Known corrected evidence has not reached the current drafts

**Where:** CAT-03 v1, CAT-04 v2, CMP-02 v4; the
[2026-09-03 recapture review](evidence-form-control-recapture-2026-09-03.md).

Four attribute, five component-definition, and two component-registration
replacement sources already exist, but the current PDFs use the earlier
checkbox/radio layouts. Their labels visibly crowd the enlarged controls.
This is a documented unfinished maintenance task, not a new application bug.

**Correction:** use the replacements in new versions and remeasure every
target. Combine this with the identity/semantic audit of evidence; copying
new pixels alone does not resolve AUD-02 or AUD-06.

### AUD-19 - P2 - Workflow instructions omit a supported result vocabulary

**Where:** WF-02 v11 p1 step 3 and p2 completion; WF-01 v10 card check.

The generic workflow guide only teaches Geslaagd/Mislukt. The active-card
controller also renders Gedaan/Niet gedaan for task-mode workflow items:
[TestResultController](../../../../app/Http/Controllers/TestResultController.php)
and [Dutch translations](../../../../resources/lang/nl-NL/tests.php).
A reader following a task-mode profile will not see the expected buttons.

**Correction:** show both modes, explain their truthful meanings beside the
first decision, and keep saving/notes/photo behavior common. Name how to
recognize required cards and handle an impossible task without inventing a
pass. Do not broaden a diagnostic-only example into a universal workflow claim.

### AUD-20 - P3 - The governing rules and generated status cues have drifted

**Where:** system.md, components.md, layouts.md, decisions.md, registry.md,
CAT-05/06 specifications; CAT-00 v9 p6 and CAT-01 v5 p5.

- The shared QA gate still describes one/two-sided page counts while the
  current family has five/six-page procedures. Some shared metadata rules
  retain the same outdated limit.
- CAT-05's specification still owns adding variants and targets four pages;
  the family plan excludes ordinary variant creation and the registry targets
  five. CAT-06 still names Admin/Superadmin while the family verification route
  names Supervisor. These planned specs were explicitly awaiting alignment.
- CAT-00/01 still mark generated CAT-02/03/04 destinations 'In voorbereiding',
  making them visually indistinguishable from genuinely absent CAT-05/06.
- Full references and badge geometry are checked more tightly than executable
  choices, saved-result evidence, or text/image overlap. Earlier visual passes
  should not be treated as proof of independent usability.

**Correction:** reconcile the contracts, then add a human task-trace gate
covering action, expected state, exception, role, and return destination.
Distinguish drafted/unaccepted from not yet generated in reviewer-facing status.

### AUD-21 - P2 - Two digital-guide codes are decorative, not usable destinations

**Where:** AC-01 v8 p1 and accepted SC-01 v10 p1, lower-right digital-guide area.

These guides show a QR-like pattern labelled 'Digitale gids', while most others
say 'QR volgt'. Their generators call `qrPlaceholder()` and draw fixed cells
instead of encoding a destination. A user can waste time repeatedly scanning
what appears to be a real helper. This also conflicts with the real/scannable
QR acceptance gate, despite SC-01's historical accepted status.

**Correction:** omit the pattern or visibly label it unavailable until the
destination policy is decided. Keep accepted bytes frozen and make any visible
correction a new version. Do not replace the code with an unapproved live URL.

### AUD-22 - P3 - Strict verification fails on three unlisted historical PDFs

**Where:** `resources/manuals/operator-guides/drafts/`.

The direct verifier fails on CAT-00 v7, CAT-00 v8, and CAT-01 v4 PDFs that are
not in the current draft manifest. A fresh manifest-only draft mirror passes
the complete verifier. The existing TODO mentions the CAT-00 files; the current
failure additionally includes CAT-01 v4.

**Correction:** archive those exact historical files through the normal
maintenance process when they are no longer held open, then run the verifier
against the actual roots. Do not let a folder listing choose the current set.
This audit did not move, delete, or overwrite those files.

## Coverage By Current Guide

All listed pages received text and visual inspection. 'No separate task blocker'
means none was found in that guide's ordinary flow during this inspection;
shared findings and physical user testing still apply.

| Current artifact | Pages | Primary findings / assessment |
| --- | ---: | --- |
| [AC-01 v8](../../../../resources/manuals/operator-guides/drafts/AC-01-login-v8-draft.pdf) | 1 | Basic login sequence recognizable; AUD-11/17/21, plus recovery-role decision below. |
| [AC-02 v3](../../../../resources/manuals/operator-guides/drafts/ac-02-eigen-wachtwoord-wijzigen-v3-draft.pdf) | 1 | Clear three-field sequence; success proof needs AUD-14. |
| [SC-01 v10](../../../../resources/manuals/operator-guides/pdf/SC-01-asset-vinden-en-openen-v10.pdf) | 1 | Strong identity stop; AUD-11/16/17/21. Accepted bytes remain frozen. |
| [AST-02 v6](../../../../resources/manuals/operator-guides/drafts/AST-02-refurbishment-route-v6-draft.pdf) | 1 | Useful route overview; inherits AUD-07/08/15/17 and must identify role handoffs. |
| [AST-03 v14](../../../../resources/manuals/operator-guides/pdf/AST-03-asset-registreren-en-labelen-v14.pdf) | 2 | Physical/digital label sequence is useful; AUD-05/15/16 and local printer decision below. |
| [AST-04 v5](../../../../resources/manuals/operator-guides/drafts/AST-04-complete-handoff-v5-draft.pdf) | 1 | AUD-08/09; QA-place/process decision remains open. |
| [AST-05 v5](../../../../resources/manuals/operator-guides/drafts/AST-05-review-release-v5-draft.pdf) | 1 | AUD-08; explicit release/return decision is useful but incomplete profile evidence is insufficient. |
| [WF-01 v10](../../../../resources/manuals/operator-guides/drafts/WF-01-start-workflow-v10-draft.pdf) | 1 | AUD-07/11/15/17/19. |
| [WF-02 v11](../../../../resources/manuals/operator-guides/drafts/WF-02-complete-workflow-v11-draft.pdf) | 2 | Good honest-result and evidence intent; AUD-08/11/14/17/19. |
| [CMP-01 v5](../../../../resources/manuals/operator-guides/drafts/CMP-01-install-existing-v5-draft.pdf) | 1 | No separate task blocker found in existing tracked-part flow; improve shared readability/references. |
| [CMP-02 v4](../../../../resources/manuals/operator-guides/drafts/CMP-02-register-install-v4-draft.pdf) | 1 | Good physical-before-digital order; AUD-15/17/18 and custom-route decision below. |
| [CMP-04 v6](../../../../resources/manuals/operator-guides/drafts/CMP-04-component-to-tray-v6-draft.pdf) | 1 | No separate task blocker found in illustrated tracked-part route; explicit destination check is useful. |
| [HELP-01 v6](../../../../resources/manuals/operator-guides/drafts/HELP-01-problems-v6-draft.pdf) | 1 | Good non-sequential format; AUD-09/15/17 and recovery-role decision below. |
| [USR-01 v11](../../../../resources/manuals/operator-guides/drafts/usr-01-gebruiker-toevoegen-v11-draft.pdf) | 1 | AUD-01/02/10/11/15. |
| [USR-02 v9](../../../../resources/manuals/operator-guides/drafts/usr-02-rol-en-rechten-wijzigen-v9-draft.pdf) | 1 | Useful direct-right definitions; AUD-01/13/15. |
| [USR-03 v3](../../../../resources/manuals/operator-guides/drafts/usr-03-wachtwoord-resetten-v3-draft.pdf) | 1 | AUD-02/04/12; transfer intent is good but identity and disabled-account route need correction. |
| [USR-04 v3](../../../../resources/manuals/operator-guides/drafts/usr-04-gebruiker-uitschakelen-v3-draft.pdf) | 2 | AUD-02/03/04/12/14; ownership handling also needs an executable route. |
| [CAT-00 v9](../../../../resources/manuals/operator-guides/drafts/CAT-00-catalogus-begrijpen-v9-draft.pdf) | 6 | Useful orientation chapter; AUD-15/20. Qualify installed-record diagrams for custom/unattached records. |
| [CAT-01 v5](../../../../resources/manuals/operator-guides/drafts/CAT-01-model-en-modelnummer-aanmaken-v5-draft.pdf) | 5 | Good three-route reuse structure; AUD-05/11/13/15/16/20. |
| [CAT-02 v1](../../../../resources/manuals/operator-guides/drafts/CAT-02-modelspecificatie-opbouwen-v1-draft.pdf) | 6 | Useful direct/component distinction; AUD-11/15 and coherent example/result follow-up below. |
| [CAT-03 v1](../../../../resources/manuals/operator-guides/drafts/CAT-03-attributen-beheren-v1-draft.pdf) | 5 | Datatype chooser is useful; AUD-11/13/15/16/18. |
| [CAT-04 v2](../../../../resources/manuals/operator-guides/drafts/CAT-04-componentdefinities-beheren-v2-draft.pdf) | 6 | AUD-06/11/13/14/15/18. |
| USR-05 / CAT-05 / CAT-06 | No PDF | Dependency review only; AUD-15/20. No usability pass is possible. |

## Decisions And Further Checks

These are unresolved prerequisites or narrower observations, not invented
product requirements. Do not silently choose local policy while rewriting.

- **Profile assignment:** WF-01 says 'afgesproken profiel'. Record where the
  worker finds that assignment and what to do if it is absent. An explicit
  supervisor assignment can be a valid starting condition.
- **Label setup:** AST-03 p2 step 6 needs the actual format/printer selection
  rule. Its placement photo is a laptop; other device shapes need a defined
  exception or a clearly limited device scope.
- **QA:** confirm the recognizable physical location, exact status terms, and
  who receives a return for correction. Asking a named role is acceptable when
  the action and resume state are clear.
- **Support roles:** AC-01 asks the supervisor to reset; HELP-01 says only a
  supervisor can arrange reset; USR-03 names Admin. Decide whether Supervisor is
  the contact who escalates to Admin, then say so consistently. A contact role
  and an execution permission are different things. Likewise, the operational
  Senior Refurbisher restriction on CMP work may intentionally be narrower
  than the application's capabilities; do not label that a software defect.
- **Custom components:** name who agrees to a one-off custom route and what
  evidence the operator needs; preserve the rule against using custom as a
  workaround for a missing reusable definition.
- **Account ownership:** USR-04 p1 says to check in or transfer items without
  explaining the actions. Asset checkout/check-in is retired in this fork;
  license/accessory check-in remains separate. Use type-specific executable
  handling, not a generic inherited Snipe-IT instruction. Also decide how an
  urgent access stop relates to completing ownership transfers.
- **CAT examples:** CAT-02 p2 uses introduction year, p3 switches to a 5G fact,
  and p5 introduces motherboard/RAM conflict evidence absent from the earlier
  one-RAM example. Label these as distinct examples or maintain a single
  progressive dataset through input, correction, and saved verification.
- **CAT concepts:** CAT-00 p1's installed record uses a definition and belongs
  to one asset. Explicitly scope that diagram to the installed definition-backed
  example, since CMP-02 permits custom and CMP-04 ends with no linked asset.
- **Digital delivery:** none of the 22 current PDFs has clickable Link
  annotations; 16 lack a structure tree. Six having one does not prove correct
  reading order. For the future digital guide route, test keyboard/navigation,
  text reflow/readout and actual reference links, and consider an accessible
  HTML/text companion. This does not block the current print-only objective
  on its own.

## Proposed Common Task Flow

Use this as a proposed revision to the shared contract, not an automatic
replacement for all layouts or for the reference/troubleshooting chapters:

1. **Start:** task purpose, role, device/interface, prerequisites, and exact
   starting page or handoff.
2. **Identify:** match the physical item/person and the record before mutation.
3. **Choose:** state each branch condition before its button or action.
4. **Act:** one visible action at a time; distinguish physical work from UI work.
5. **Check:** name the expected visible result and what to do when it differs.
6. **Save/confirm:** state when saving is explicit or automatic and what proves
   completion. Do not repeat a mutation just because the result is uncertain.
7. **Finish or hand off:** clear success state, next responsible role, next
   guide/step, and a safe interruption/recovery route.

Explain unfamiliar Dutch terms at first use and pair exact English interface
labels with their meaning. Use the same marker for the same purpose. Optional
steps and alternative routes need explicit skip and rejoin instructions.
Keep critical instruction text out of captions, footers, and image overlays.
Do not force a fixed number of pages or actions to achieve visual uniformity.

## Correction Order And Acceptance Evidence

1. Resolve the role, password, product-code, reactivation, and release-policy
   questions. Reconcile the shared source documents before generator changes.
2. Correct high-risk USR and WF/AST task logic and the false CAT-04 example.
   Keep all accepted artifacts unchanged; prepare new exact versions.
3. Repair shared text/image boundaries, instruction size, reference semantics,
   and required add/save/return actions. Promote the eleven known replacement
   images only after checking their identity and behavioral truth.
4. Supply missing dependency routes or explicit interim handoffs, then rebuild
   the route overview and exact current distribution package.
5. Run the focused geometry/package checks and inspect all changed pages.
   Follow with observed first-time-user tasks at actual print size.

The observed tasks should include: scanning successfully and skipping manual
search; resuming an existing run; using Gedaan/Niet gedaan; a missing profile;
a correct device with the wrong open record; missing definitions mid-task;
Admin group assignment; an intentionally disabled reset request; deletion
without restoration; and restoration of an already deleted account. Include
users with differing autistic support needs and allow their normal aids and
breaks. Record wrong actions, facilitator prompts, lost place, recovery, and
whether the reader can explain the completion state. Agree acceptance criteria
before testing rather than assigning a pass from visual confidence alone.

## Validation Results

- Shared guide-system test: pass, 25 registry entries and 5 reference checks.
- All 29 manifest PDFs: SHA-256 match; expected page counts and A4 dimensions.
- Current set: 22 PDFs / 48 pages rendered and inspected.
- Strict verifier on actual roots: **fails** on the three unlisted historical
  draft PDFs in AUD-22.
- Complete verifier with a newly copied manifest-only draft root: **passes**,
  103 evidence files, 9 accepted PDFs / 11 pages, 20 drafts / 45 pages,
  2 baselines, 16 active scripts. This is not reported as a clean actual-root pass.
- No guide accepted, rewritten, regenerated, or overwritten. No application
  tests were run for this inspection-only task. Code-backed findings identify
  local source behavior; production parity remains unverified.
