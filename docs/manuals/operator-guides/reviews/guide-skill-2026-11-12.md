# Guide Skill Build And Initial Tests - 2026-11-12

Owner request: build/refine the skill from maintained rules and feedback, test
several guides before the remaining conversions, then continue Affinity work
with interruptions respected. START-05/NEXT-08, root master a766abedee; other
shared changes and all frozen guide inputs are preserved.

## Maintained And Installed Skill

[Source](../skill/SKILL.md): snipeit-operator-guides. The authoring method and
owning contracts/specifications remain authoritative. The skill routes reader
reasoning, retrospective comparison cases and native production separately.
`scripts/manuals/install-operator-guide-skill.py` installs the exact source in
the personal skill directory and protects independent installed edits.

Initial source/installed structural validation passes. The bundled validator
required PyYAML, absent from the bundled Python. PyYAML6.0.3 (MIT license read)
is installed only under ignored output/manuals/skill-tool-dependencies; no
application dependency or runtime changed. Installation is byte-verifiable.

## Editorial Exercises Before The Remaining Conversions

Applied the installed skill to the selected native-derived pages, relevant
specifications and actual earlier comparison images. These are retrospective
editorial exercises with known feedback, not independent blind generation,
operator trials or owner acceptance. No content revision was justified by
these three read-backs; preserving the selected content is intentional.

| Guide | Actual comparison/read-back | Result and limits |
| --- | --- | --- |
| AST-03 v20, both sides | Compared v15 page1 and the current two sides. The first action is Apparaten/search before creation. Both Dashboard tiles and the list plus are visible, and the text conditions creation on no matching record. A match goes to step5. Tag/serial/model, optional notes and actual Save are explained. Numbering continues5-8 through printer/size selection, physical placement and same-identity scan. Useful Unlock/Aa, duplicate, model, failed-print and damaged-label help is retained at use. | Retain. Larger fields and controls replace v15's small form fragments without losing the entry/Save/physical-label jobs. Existing source/root versus deployed note-validation mismatch stays a release gap; no fresh application or physical print trial. |
| SC-01 v16, one side | Compared rejected v14 image with current v16. Step2 explicitly presents scanning OF manual search in parallel; either leads to shared step3. Current manual form and complete identity check are unoutlined; scanner entries/physical QR retain useful focus. Refurbisher is exact. The single conditional mismatch/uncertain-identity halt gives recovery and resumption. | Retain. Reading only either route reaches the same check; no appended new step1. Removing all marks or requiring both routes would regress the guide. This is an editorial interpretation, not observed novice behavior. |
| AC-02 v8, one side | Compared v6 with current v8. Profile icon and Wachtwoord wijzigen have the explicitly requested red marks and header/menu context. The complete unoutlined form explains current/new/confirmation positions despite the repeated native label. Save and success are separately shown, with private-password and reset help. | Retain. Menu/form/result targets gain useful size; ordinary privacy guidance does not become a STOP. The success screenshot is retained evidence, not a password change performed today. |

Exact inputs/PDF hashes and150dpi page renders are in
output/manuals/skill-tests-2026-11-12/{AST-03,SC-01,AC-02}-before.json and their
same-code render folders. All source story fragments occur in each PDF; page
count, A4 portrait and no optional-layer checks pass. These checks do not
establish correct placement or practical native editing; the pages were also
visually inspected.

## Failure Case And Refinement

CAT-06 v4's original native-derived PDF fails the same source-fragment check:
the final part of the HP source address is absent. The rendered page2 visibly
ends at hp-probook- instead of the complete450-g8.html suffix. Import/reopen
object counts and a nonempty PDF had passed earlier, so neither is a sufficient
completion check. The new reusable checker detects this real loss while the
other three guide pairs pass.

The skill explicitly separates concrete teaching preservation, extraction
flags, rendered interpretation and demonstrated editing. Its comparison cases
pair each lesson with its boundary: no automatic focus/whitespace/STOP quotas,
no abstract-route substitution, and no assumption that a saved file proves
native editing. The URL repair uses available frame width/height without
changing words, font sizes, images/crops or other geometry. Native execution
and the repaired before/after result is recorded below before the six
remaining guide conversions.

The native repair completed in conversion-runs/2026-11-12T09-13-57-289Z-871560
under the current native session. Save/reopen gives89 live text frames,3 full
image placements and29 groups on3 A4 pages, with installed Arial fonts and no
warnings/errors. The repaired source/PDF check passes with zero missing story
fragments. Page2 was visually inspected: the full address is readable at the
unchanged type size.150dpi pixel comparison shows pages1/3 are identical to
the earlier native PDF; page2 changes only bounds583,1442-666,1458 at the
previously missing URL suffix. The original marker/artifacts are retained in
history; the current selection names the exact repair input. No content version
or screenshot changed. This exercises the skill's failure -> narrow repair ->
positive case and earlier-case preservation checks.

The first attempt to resume the batch stopped before import: the strict marker
guard correctly rejected the repair hash while the runner still selected the
original CAT-06 input. Updated that one selected input's exact path/bytes/hash
to the verified repair, retaining the guard and frozen original. Added this
promotion invariant to the skill's native reference; no other guide changed.

## Further Verification

CAT-06 repair and all six remaining conversions now pass. The complete native
audit covers25 guides/68 pages: no failed input/count/A4/layer checks, missing
source fragments or warnings/errors. All68 selected pages now have recorded
visual review. Native image replacement is now also proven through
Affinity's toolbar: isolated AST-03 copy,830x8854 ->1920x1650 pixels, retained
frame, successful recrop/undo and save/close/reopen, both extracted full-image
hashes equal the requested original. Evidence:
output/manuals/affinity-native-2026-11-10/proofs/image-ui-1794475416008/after.json.
The chosen guide remains unchanged. Updated the skill with this working route
while retaining ReplaceBitmap's failure boundary.

Applied the method to additional cases while reading the rest of the set:
CAT-04 v8 supplies missing Add controls at first use while preserving its large
field views; USR-04 v9 corrects a caption contradicting the empty checkbox.
Separate reviews record preservation and exact artifacts. The checker passed
their predecessors, so both repairs depend on rendered reader interpretation,
not just mechanical text preservation. Updated the skill with those limits.

CAT-00 text and an independent native PolyCurve connector were edited and
saved/reopened. A CAT-05 page2 route-step group moved independently while a
different route page stayed unchanged, and the edit persisted. Evidence:
proofs/catalogue-edit-1794475777517/proof.json under the current native session.
An earlier SDK group-rename failed after successful movement; the repeated
proof avoids that unnecessary operation and passes. This does not claim every
possible rename or connector-endpoint operation is qualified.

Shared native scaffold: template/Guide-basis-v2.af, one A4 page/master,18 live
text frames and23 declared paragraph styles. The text checker caught initially
undersized role/footer fields after placeholder changes; they were widened
without reducing type. Native reopen/overflow/PDF and visual checks pass.
The first preview used the wrong SDK method; corrected to the tested export
method and validated its actual result records. Failed probes remain separate.

Final handoff assembled with25 native/PDF/image packages, shared scaffold,
full source preparation and current skill/docs. No copied font binaries.
All25 pairs/68 pages preserve2580 story fragments;100 packaged image files
and712 frozen preparation hashes pass. Four representative relocated packages
(AST-03, CAT-00/04/05) save/reopen and export23 pixel-identical pages; all their
extracted full-image hashes match. Original source directories remained
available: this is a same-machine package test, not fresh-device proof.

Installer tests pass initial/repeated installation and refusal to overwrite an
independently edited installed copy. The maintained skill and installed copy
validate and match. Its helper ran against actual passing and failing guide
artifacts; neither frontmatter validation nor these cases prove general usability.

The handoff review has243 valid local links, including three exact before-fix
PDFs, and a working loopback HTTP/PDF
endpoint. Exact final evidence is in Evidence/handoff-verification.json under
output/manuals/affinity-guide-handoff-2026-11-12. The skill has initial
retrospective tests and new-case applications, not evidence of general
reliability across models.
Future unfamiliar guide tasks and owner/reader outcomes should refine the
specific criterion that failed, with earlier passing cases retained.

## Shared Folder And Embedded Native Images

Owner approved a flat shared Handleidingen delivery and manual/handleiding
terminology. Applied the skill's portability/source-authority rules to new
copies rather than moving linked masters and assuming their paths work.
Full compatible native bitmaps retain170 image placements and source pixels
inside original crop frames. The insertion builder needs the spread transform;
the rejected first isolated probe used the local transform. Saved node IDs are
session-local, so reopening is verified by geometry, dimensions/tree sequence
and counts instead of stale IDs.

All25 copies save/reopen with Embedded images and matching object counts.
Four .af files in separate folders containing only that native file open and
export23 exact pages, retaining full-image pixels and actual recrop/undo.
The full comparison preserves all text/full image pixels;67 of68 test-export
pages are pixel-identical at150dpi. AC-01's phone screenshot alone has a small
4/255 colour variation inside its image area. Native Resource Manager embedding
reproduces it, so it was not called a fixed bitmap-route defect. The generic
strict comparison remains non-identical. Current print PDFs stay byte-identical
to the previous delivery. A pinned technical-copy qualification covers this
specific reviewed variation; it creates no general tolerance or acceptance.
Canonical/installed production references record the method and its boundaries.

Root AGENTS.md/register, current text/specifications/rules, full original images,
image maps, optional rebased skill and dated application contracts support
models without the chat or original repository installation. The complete
earlier technical handoff is archived. Integrity,1311 Markdown file links and
six offline index-filter tests pass. Browser policy rejects file URLs, so no
browser visual test or bypass is claimed. Native app control has finished.
