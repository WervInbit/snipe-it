"""Freeze the reviewed outputs without replacing earlier drafts or selections."""
from pathlib import Path
import hashlib
import json
import re
import zipfile

REPO = Path(__file__).resolve().parents[2]
PROOF = REPO / 'output/manuals/proofs/guide-followups-2026-09-08'
ROUND = REPO / 'resources/manuals/operator-guides/review-rounds/2026-09-08'
DOCS = REPO / 'docs/manuals/operator-guides'
sha = lambda data: hashlib.sha256(data).hexdigest()
records = json.loads((PROOF / 'qa-summary.json').read_text(encoding='utf-8'))
assert len(records) == 20 and sum(r['pages'] for r in records) == 46

notes = {
 'AC-01': ('AUD-21/17', 'Page 1: replace the decorative pseudo-QR with QR volgt; clarify that the supervisor is the support contact and an Admin performs a reset.', 'Short footer references remain where expanding them would crowd the established layout. Digital destinations are still deferred.'),
 'AC-02': ('AUD-14', 'Step 3: name the exact saved-success message, correct/resave after errors, and retain the other-session logout warning. Shorten the heading to clear image badge 3A.', 'The success state is explained in text; the retained image shows the Save action, not a newly captured success message.'),
 'SC-01': ('AUD-16/21', 'Step 2: successful scan goes directly to step 4. Replace the decorative pseudo-QR with QR volgt.', 'The existing asymmetric layout and short footer references remain. No new digital-guide endpoint is implied.'),
 'AST-02': ('AUD-07/15/17', 'Route 3: continue the correct unfinished run OR start once. The unregistered-asset branch names the Supervisor and return to SC-01. Complete guide titles wrap inside the existing route chips.', 'Required profile selection still depends on the operational task and supervisor; no production profile configuration was inspected.'),
 'AST-03': ('AUD-05/12/15/16', 'Page 1: 1A remains the prerequisite; 1B OR 1C are entry alternatives. Missing model type returns through CAT-01 to step 3. Keep the HP Product ID/P/N distinction and all label instructions. Recover five squeezed captions using header/row padding; retain every image band and both pages.', 'Local spacing exception: header row 24 to 18 mm, row vertical padding reduced, captions 2 mm. This is a fit repair, not the rejected global page redesign. Existing tags in evidence are historical examples; the separate identifier patch does not change them.'),
 'AST-04': ('AUD-08/09/17', 'Step 1 checks all required profiles for the model. The screenshot is one example run. Step 2 identity mismatch means no edits, recheck via SC-01 and ask the supervisor. Preserve QA Hold, physical handoff, all images and help.', 'The actual required profile set and QA location need local operational confirmation. Six retained references occupy three rows rather than removing useful routes.'),
 'AST-05': ('AUD-08/17', 'Step 2 checks the required profile set and evidence. Step 4 visibly offers release OR return. Keep all six images, status instructions, and help. Reduce top padding in steps 2/4 so captions stay in their cards.', 'No live production profile/readiness validation or physical release test. A failed control remains a truthful result; it must not be changed merely to permit release.'),
 'WF-02': ('AUD-14/17/19', 'Step 3 explains Geslaagd/Mislukt and task-card Gedaan/Niet gedaan. Step 4 waits for the saved-note message. Step 6 explains automatic result saving and return through the same asset > Test uitvoeren; correction warning is amber. Full reference names, both pages and six images retained.', 'No task-card screenshot or fresh end-to-end save/error capture was added; terminology and saving behavior were checked in local source.'),
 'CMP-01': ('AUD-17', 'Use the complete names of the destination guides in prerequisite/footer chips; retain the four-step tracked-component flow and all images/help.', 'This is a small reference consistency change. Existing tray and condition policies are preserved.'),
 'CMP-02': ('AUD-15/18', 'Step 2 is a framed 2A OR 2B choice. Use the canonical definition/custom form captures ending 04. Missing reusable type routes through a Supervisor/CAT-04 and returns to step 2A. Full guide names retained.', 'The agreed-exception policy for Custom and the condition-warning handoff remain unchanged. New form sources replace misleading controls; these are evidence replacements, not pixel-identical images.'),
 'HELP-01': ('AUD-17', 'Clarify supervisor contact versus Admin reset execution and complete the guide names in problem tiles/footer. Preserve all twelve problem tiles and the general identity stop.', 'No new reset authority, digital destination, or support policy is introduced.'),
 'USR-01': ('AUD-01/02/15', 'The full creation/group-assignment route names Superadmin. Missing groups are an explicit dependency; USR-05 is marked as forthcoming. Label the final detail image as a screen example.', 'Accepted first-login password convention is unchanged pending the owner policy decision (AUD-10). The final screenshot is not a same-person end-to-end capture.'),
 'USR-02': ('AUD-01/02/13/15', 'Step 2 opens Optionele informatie, states Superadmin-only group changes and the Admin handoff, while retaining Ctrl+click and direct permission meanings. Step 4 explicitly saves and checks groups/permissions. Mark the final image as an example and shorten an overflowing help title.', 'The saved-detail example has a different demonstration identity from the search capture; it is explicitly qualified, not claimed as a continuous capture.'),
 'USR-03': ('AUD-02/04/12', 'Keep the prior login state during reset. Shorten/wrap headings and body to clear the image column. Crop 2A to password controls and label it Bedieningsdetail, retaining all five frames and four help tiles.', 'A control-only crop removes the misleading Demo identity from the mutation illustration. It does not prove a same-person end-to-end reset. Disabled access and safe delivery still require the stated decision.'),
 'USR-04': ('AUD-02/03/04/12/14/15', 'Keep two pages and nine images. Page 1 corrects ownership wording, crops activation controls, and exposes Login Nee with contain fitting. Page 2 explicitly separates delete (5-6) from restore (6-8), preserves the previous login state, and verifies the actual restored access.', 'USR-04 v4 remains rejected. Full asset/license/management transfer procedures and urgent-disable timing are still operational gaps. The control crop is not a new same-person capture; restoration is not guaranteed to disable login.'),
 'CAT-00': ('AUD-15/20', 'Qualify page 1 as an installed, definition-backed component example. Page 5 labels the separate screen example. Page 6 distinguishes generated guides from absent CAT-05/06 and names the responsible role for those dependencies.', 'All diagrams and six pages remain. CAT-05/06 are still planned; existing conceptual examples are not one uninterrupted dataset.'),
 'CAT-01': ('AUD-05/13/16/20', 'Page 2 explicitly chooses A, B OR C with page destinations, and wraps text clear of screenshots. Page 4 distinguishes manufacturer Product ID/P/N from software ID/SN/tag and explicitly instructs Opslaan. Page 5 marks CAT-02 as a working concept, not absent.', 'Baseline pictures and five-page layout retained. Exact manufacturer/source verification still depends on suitable evidence; no new source-storage feature is implied.'),
 'CAT-02': ('AUD-15/20', 'Page 2 records unsaved input before leaving to create a definition, then reopens the same Edit Spec and resumes page 3/4. Page 5 labels the conflict as a separate example and routes saved-row deletion to an Admin.', 'This preserves six pages and twelve images. A single continuous creation/conflict/save dataset remains a future evidence improvement.'),
 'CAT-03': ('AUD-13/15/16/18', 'Use the four canonical 02 form sources. Field-summary badges are letters rather than extra step numbers. Page 4 chooses numeric OR Enum, skips both for Bool/Text, names Add to list, and shows its label. Saved-row/lifecycle work names Admin and the planned CAT-05.', 'The existing Enum capture ends at the bottom of the Add to list button; its label and inputs are visible but a fuller capture would improve it. Small print remains subject to physical A4/user review.'),
 'CAT-04': ('AUD-06/13/14/15/18', 'Name Add Expected Subcomponent and Add Attribute Contribution before filling rows. Use canonical 02 forms. Replace misleading Enum overlap evidence with a real numeric contribution example and accurate warning explanation. Reopen saved Edit to check values, then return to the original task.', 'CAT-04 v3 remains rejected. No verified live numeric hierarchy-warning capture was obtained: page 5 is explicitly a contribution example, not proof of a triggered warning. Add-row buttons may require scrolling below the shown rows. Saved cleanup remains an Admin dependency.'),
}

def write_new(path, data):
    path.parent.mkdir(parents=True, exist_ok=True)
    if path.exists():
        assert path.read_bytes() == data, f'Refusing to replace {path}'
    else:
        with path.open('xb') as handle: handle.write(data)

index = ['# Guide Set Comparison - 2026-09-08', '',
 'Twenty new versioned candidates use the owner-reviewed WF-01 v12 approach. All 22 existing guides were covered. WF-01 v12 and CMP-04 v6 are retained unchanged.', '',
 'Earlier accepted selections, drafts, rejected pilots, and checkpoints remain unchanged. These twenty candidates are pending exact-version owner review. Physical print and first-time-user validation remain pending.', '',
 '| Guide | Frozen baseline PDF | New candidate PDF | Changes and limitations |', '| --- | --- | --- | --- |']

for record in records:
    code, version = record['code'], record['version']
    final = REPO / 'output/pdf' / record['file']
    data = final.read_bytes()
    assert sha(data) == record['sha256'], f'QA is stale: {code}'
    assert not any(p['outsideCharacters'] for p in record['comparison']['pages'])
    write_new(ROUND / record['file'], data)
    old = record['comparison']['baseline']
    old_link = '../../../../resources/manuals/operator-guides/checkpoints/2026-09-08-before-audit-revisions/' + old['checkpointPath']
    new_link = '../../../../resources/manuals/operator-guides/review-rounds/2026-09-08/' + record['file']
    issues, changes, limitations = notes[code]
    image_count = sum(p['newImages'] for p in record['comparison']['pages'])
    body = f'''# {code} v{version} Focused Review Candidate

Status: unaccepted candidate; exact-version owner review pending.

Round: 2026-09-08, separate from the prior Sol baseline and rejected pilot.
The owner approved the WF-01 v12 direction for propagation, not automatic
acceptance of every new PDF.

## Exact Artifacts

- [Candidate PDF]({new_link}) - {record['pages']} page(s), {record['bytes']} bytes.
- SHA-256: `{record['sha256']}`.
- [Frozen baseline v{record['baselineVersion']}]({old_link}).
- Generator: `scripts/manuals/generate-guide-followups.mjs {code}`.
- Original renderer: `{record['originalGenerator']}` (unchanged).

## Changes And Tradeoffs

Audit references: {issues}.

{changes}

Benefit: make the next action and its conditions clearer while retaining the
recognizable task layout. Cost: some local wording and spacing differ from
the baseline; reviewers should use the exact linked pair rather than infer
approval from the version number.

{limitations}

## Validation And Decision

Same page count and {image_count} raster-image placements as the baseline;
help headings retained. Rendered pages visually inspected. PDF text bounds,
version/date, image inventory, and extracted text changes checked. Image count
is not a claim that every crop or source is identical. See the set validation
for the deliberate source/crop and spacing exceptions.

No physical A4 print, first-time-user trial, production configuration check,
or fresh complete live workflow was performed. No new acceptance is recorded.
Decision: pending. Reviewer/date: not yet recorded.

To reject: leave current manifests unchanged and continue from the frozen
baseline. To accept: record the exact version/hash and selection separately;
keep earlier files. See [set comparison](guide-set-comparison-2026-09-08.md).
'''
    review = f'{code}-v{version}.md'
    write_new(DOCS / 'reviews' / review, body.encode('utf-8'))
    index.append(f'| {code} | [v{record["baselineVersion"]}]({old_link}) | [v{version}]({new_link}) | [Review]({review}) |')

index.extend(['', '## Retained Without Revision', '',
 '- [WF-01 v12 review](WF-01-v12.md): owner-reviewed direction retained, exact PDF unchanged.',
 '- [CMP-04 v6 PDF](../../../../resources/manuals/operator-guides/drafts/CMP-04-component-to-tray-v6-draft.pdf): existing sequential tray flow and destination verification already fit.', '',
 '## Set-Level Limits', '',
 'The first-login password policy, precise required workflow profile sets, QA location, printer choice, custom-component authorization, urgent-disable versus ownership timing, and source-recording convention remain owner/operations matters. This pass does not claim that every audit finding is closed.', '',
 'The real numeric overlap-warning capture and continuous account/catalogue evidence remain incomplete. Corrections label examples honestly and preserve helpful screenshots.', '',
 'Reproduce with `node scripts/manuals/generate-guide-followups.mjs`. Frozen source inputs and validation are retained alongside the PDFs. Regeneration writes ignored output, not accepted or review-round files.', ''])
write_new(DOCS / 'reviews/guide-set-comparison-2026-09-08.md', '\n'.join(index).encode('utf-8'))

validation = {
 'guidesReviewed':22,'newPdfs':20,'newPages':46,'retainedGuides':2,'completeSetPages':48,
 'samePageCounts':True,'sameRasterPlacements':122,'helpHeadingsRetained':True,
 'visualInspection':'All rendered candidate pages inspected; changed regions rechecked after fit corrections.',
 'exceptions':{code:n[2] for code,n in notes.items()},
 'records':records,
}
write_new(ROUND/'guide-set-followups-validation.json', (json.dumps(validation,indent=2,ensure_ascii=False)+'\n').encode('utf-8'))

source_files = {REPO/'scripts/manuals/generate-guide-followups.mjs', REPO/'scripts/manuals/lib/guide-followup-patches.mjs', Path(__file__).resolve(), PROOF/'qa_followups.py'}
source_files.update(REPO/'scripts/manuals'/r['originalGenerator'] for r in records)
source_files.update((REPO/'scripts/manuals/lib').glob('*.mjs'))
source_files.update(DOCS.glob('*.md'))
source_files.update(DOCS.glob('guides/*.md'))
source_files.update(DOCS.glob('reviews/*.md'))
for record in records:
    directory = PROOF/f"{record['code']}-v{record['version']}"
    source_files.update([directory/record['originalGenerator'],REPO/record['outputHtml'],directory/'validation.json'])
    source_files.update(directory.glob('text-diff-page-*.txt'))
archive = ROUND/'guide-set-followups-source-inputs.zip'
assert not archive.exists(), 'Source archive is immutable; do not rerun retention after freezing.'
with zipfile.ZipFile(archive,'x',zipfile.ZIP_DEFLATED) as z:
    for file in sorted(source_files): z.write(file,file.relative_to(REPO).as_posix())
manifest = {
 'schemaVersion':1,'createdOn':'2026-09-08','status':'Unaccepted focused review candidates',
 'provenance':'Separate new-model review round; owner-reviewed WF-01 v12 direction applied to all existing guides.',
 'sourceArchive':{'file':archive.name,'sha256':sha(archive.read_bytes()),'files':len(source_files)},
 'artifacts':[{k:r[k] for k in ['code','baselineVersion','version','file','sha256','bytes','pages']} for r in records],
 'retained':[
  {'code':'WF-01','version':12,'path':'WF-01-workflow-starten-v12-draft.pdf'},
  {'code':'CMP-04','version':6,'path':'../../drafts/CMP-04-component-to-tray-v6-draft.pdf'}],
}
write_new(ROUND/'guide-set-followups-manifest.json',(json.dumps(manifest,indent=2)+'\n').encode('utf-8'))
print(json.dumps({'retainedPdfs':20,'pages':46,'sourceFiles':len(source_files),'manifest':str(ROUND/'guide-set-followups-manifest.json')}))
