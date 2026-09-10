"""Assemble the latest exact accepted guides and a standalone regeneration kit."""
from pathlib import Path
import hashlib
import json
import re
import shutil
import sys
import PIL
import pypdf

ROOT=Path(__file__).resolve().parents[2]
RESOURCE=ROOT/'resources/manuals/operator-guides'
ROUND=RESOURCE/'review-rounds/2026-09-10'
DEST=RESOURCE/'accepted-guides-v1'
assert not DEST.exists(),'Accepted kits are versioned snapshots. Choose a new kit version.'
sha=lambda p:hashlib.sha256(p.read_bytes()).hexdigest()
rel=lambda p:p.relative_to(ROOT).as_posix()
load=lambda p:json.loads(p.read_text(encoding='utf-8-sig'))
current=load(ROUND/'current-guide-selection-v4.json')['guides']
legacy=load(RESOURCE/'pdf/manifest.json')['artifacts']
accepted={e['code']:{**e,'path':rel(RESOURCE/'pdf'/e['file']),'acceptanceSource':rel(RESOURCE/'pdf/manifest.json')} for e in legacy}
for e in current:
    if e['ownerAccepted']:
        accepted[e['code']]={**e,'acceptanceSource':rel(ROUND/('owner-review-readiness-2026-09-10.json' if e.get('ownerAcceptedOn') else 'current-guide-selection-v1.json'))}
ordered=[accepted[e['code']] for e in current if e['code'] in accepted]
assert len(ordered)==16 and sum(e['pages'] for e in ordered)==27
history=[{**e,'path':rel(RESOURCE/'pdf'/e['file'])} for e in legacy if e['version']!=accepted[e['code']]['version']]
assert len(history)==7
owners={'SC-01','AST-02','WF-01','CMP-02','CMP-04','USR-01','USR-02','USR-04','CAT-00','CAT-01'}
html_paths={}
for e in ordered:
    code=e['code'];version=e['version'];name=Path(e['path']).with_suffix('.html').name
    if code in owners:p=ROOT/f'output/manuals/proofs/owner-corrections-2026-09-10/{code}-v{version}'/name
    elif code=='WF-02':p=ROOT/'output/manuals/final-validation/proofs/workflow-review-v8/WF-02-complete-workflow-v10-draft.html'
    elif code=='CMP-01':p=ROOT/'output/manuals/final-validation/proofs/CMP-01-v4/CMP-01-install-existing-v4-draft.html'
    else:p=ROOT/f'output/manuals/proofs/guide-followups-2026-09-08/{code}-v{version}'/name
    assert p.is_file(),p
    assert sha(ROOT/e['path'])==e['sha256'],e['code']
    html_paths[code]=p
for e in history:assert sha(ROOT/e['path'])==e['sha256']

DEST.mkdir()
(DEST/'.gitattributes').write_text('# Preserve the exact bytes recorded in manifest.json.\n* -text\n',encoding='utf-8')
(DEST/'.gitignore').write_text('/work/\n/scripts/node_modules/\n',encoding='utf-8')
def copy(source,destination):
    target=DEST/destination;target.parent.mkdir(parents=True,exist_ok=True)
    shutil.copyfile(source,target)
    assert sha(source)==sha(target)
    return destination

guides=[]
for e in ordered:
    pdf=copy(ROOT/e['path'],'pdf/'+Path(e['path']).name)
    html=copy(html_paths[e['code']],'source/html/'+Path(e['path']).with_suffix('.html').name)
    guides.append({'code':e['code'],'title':e['title'],'version':e['version'],'pages':e['pages'],
                   'status':'Internal review candidate','pdf':pdf,'sha256':e['sha256'],
                   'html':html,'htmlSha256':sha(html_paths[e['code']]),
                   'originalPdf':e['path'],'originalHtml':rel(html_paths[e['code']]),
                   'acceptanceSource':'source/repository/'+e['acceptanceSource']})
historical=[]
for e in history:
    pdf=copy(ROOT/e['path'],'history/pdf/'+Path(e['path']).name)
    historical.append({'code':e['code'],'version':e['version'],'pages':e['pages'],'pdf':pdf,'sha256':e['sha256'],
                       'originalPdf':e['path'],'status':'Previously internally accepted; newer accepted version in pdf/'})

# Repository sources retain their original relative layout for provenance and later authoring.
source_files=set((ROOT/'docs/manuals/operator-guides').rglob('*.md'))
source_files.update(p for p in (ROOT/'scripts/manuals').rglob('*') if p.is_file() and p.suffix in ('.mjs','.py','.json','.txt'))
source_files.update(p for p in (RESOURCE/'evidence').iterdir() if p.is_file())
source_files.update((RESOURCE/'baselines').iterdir())
source_files.update([ROOT/'AGENTS.md',RESOURCE/'pdf/manifest.json',ROUND/'current-guide-selection-v1.json',
                     ROUND/'current-guide-selection-v4.json',ROUND/'owner-review-readiness-2026-09-10.json'])
source_files.update((ROOT/'output/manuals/proofs/owner-corrections-2026-09-10/inputs').glob('*.html'))
for p in sorted(source_files):
    if p.is_file():copy(p,'source/repository/'+rel(p))
for name in ['verify.mjs','render.mjs','compare.py']:
    copy(ROOT/'scripts/manuals/accepted-guide-kit'/name,'scripts/'+name)
package=load(ROOT/'scripts/manuals/package.json')
package['scripts']={'verify':'node verify.mjs','render':'node render.mjs --all'}
(DEST/'scripts/package.json').write_text(json.dumps(package,indent=2)+'\n',encoding='utf-8')
copy(ROOT/'scripts/manuals/package-lock.json','scripts/package-lock.json')
(DEST/'scripts/requirements.txt').write_text(f'pypdf=={pypdf.__version__}\nPillow=={PIL.__version__}\n',encoding='utf-8')

highest={}
all_pdfs=list(RESOURCE.rglob('*.pdf'))+list((ROOT/'output/pdf').glob('*.pdf'))
for e in current:
    code=e['code'];versions=[e['version']]
    expression=re.compile(rf'^{re.escape(code)}-.*-v(\d+)(?:-.*)?\.pdf$',re.I)
    for p in all_pdfs:
        match=expression.match(p.name)
        if match:versions.append(int(match[1]))
    highest[code]=max(versions)
excluded=[{'code':e['code'],'version':e['version'],'reason':'No exact accepted version recorded'} for e in current if e['code'] not in accepted]
rows='\n'.join(f'| {e["code"]} | v{e["version"]} | {e["pages"]} | [{Path(e["pdf"]).name}]({e["pdf"]}) |' for e in guides)
readme=f'''# Accepted Operator Guides - Package v1

Snapshot: 2026-09-10. Start here before creating, editing or regenerating a guide.

The `pdf/` folder contains **16 guide codes / 27 pages**, using the latest
recorded accepted version of each guide. Accepted means **Internal review
candidate**, including the owner's clearance for review by other users. No
third-party approval or completed user trial is implied.

`history/pdf/` preserves seven older accepted PDFs for comparison and rollback.
Filenames, version numbers, printed draft labels and PDF bytes are unchanged.
The separate catalogue prototype and unaccepted guides are outside this set.

## Folder Map

- [manifest.json](manifest.json): exact selection, hashes, source HTML,
  acceptance evidence and highest known versions at this snapshot.
- `pdf/`: latest accepted PDF for each included guide; use these for review.
- `history/pdf/`: older accepted PDFs, kept out of the current review folder.
- `source/html/`: self-contained, editable final HTML for all 16 current guides.
- `scripts/`: portable regeneration and integrity/comparison tools, plus
  pinned Node and Python dependency lists.
- `source/repository/`: the original guide rules, specifications, review
  records, generators, shared libraries, canonical screenshots and baseline
  SVGs, preserved in their repository layout.
- `work/`: created by the renderer for new proofs and revisions. These files
  are never part of the accepted snapshot.

## Included Current Versions

| Guide | Version | Pages | Accepted PDF |
| --- | --- | ---: | --- |
{rows}

WF-02 v10 and CMP-01 v4 are deliberately selected: newer v12/v6 proposals
exist but do not have recorded acceptance. Their existing wording, screenshots
and references remain as accepted. Do not silently replace them with a newer
file based only on its number.

AST-05, HELP-01, USR-03, CAT-02, CAT-03 and CAT-04 have no exact accepted
version in the consulted records. They are still dependencies of some guides;
the source documents describe their draft/planned state. USR-05, CAT-05 and
CAT-06 are planned. Creating this folder does not approve any of them.

## Verify The Package

Run from this folder, using Node.js 22 or later:

```powershell
node scripts/verify.mjs
```

This checks the manifest, every packaged source/tool/document, all current
PDFs and all historical PDFs. Keep this snapshot intact. Work on copied HTML
under `work/`; after a later approval, create a new versioned package.
Keep `.gitattributes` with the folder so Git preserves the recorded file bytes.

## Regenerate Accepted Guides

The portable renderer uses final HTML with embedded screenshots. It does not
need Laravel, a database, a live account, network access or this checkout.

1. Install the pinned dependencies once. Node 22+, Chrome/Chromium and Arial
   are required. The Windows tests use system Chrome and Arial.

```powershell
cd scripts
npm ci
cd ..
```

If system Chrome is unavailable, run `npx playwright install chromium` from
`scripts/`. You can explicitly set `GUIDE_CHROME_PATH` to a browser executable.
In Codex, load the bundled workspace dependencies and set
`GUIDE_NODE_MODULES_ROOT` to its Node module directory instead of installing.
Use the bundled Python executable for the comparison step when available.

2. Rebuild all accepted guides, or one guide, into a new named run:

```powershell
node scripts/render.mjs --all --run baseline-check
node scripts/render.mjs --guide SC-01 --run sc01-check
```

Output is `work/<run>/pdf/`. Existing run directories are refused. The
accepted PDFs and their HTML sources cannot be overwritten by this command.
The render report records the browser version, source, output and page count.
All resource requests outside the supplied HTML are blocked.

3. Compare rendered pages with the accepted PDFs. Install Poppler's
`pdftoppm` on PATH, then use Python 3.10+ with the pinned requirements:

```powershell
python -m pip install -r scripts/requirements.txt
python scripts/compare.py work/baseline-check
```

Set `GUIDE_PDFTOPPM_PATH` if Poppler is not on PATH. The comparison checks
page counts and pixels at 96 dpi and records any differences. Inspect changed
pages at 144 dpi and at actual A4 size before a new version is reviewed.
Font/browser changes can affect layout. Regenerated PDF hashes can differ
because of metadata even when pages match. To restore exact accepted bytes,
copy the original from `pdf/` or `history/pdf/` and verify its manifest hash.

## Create A New Revision Or Guide

1. Read the [guide system](source/repository/docs/manuals/operator-guides/system.md),
   [components](source/repository/docs/manuals/operator-guides/components.md),
   [layouts](source/repository/docs/manuals/operator-guides/layouts.md),
   [maintenance rules](source/repository/docs/manuals/operator-guides/maintenance.md)
   and the relevant specification in `source/repository/docs/manuals/operator-guides/guides/`.
   The root manifest owns this package's exact accepted selection; original
   documents contain historical notes and newer unaccepted proposals too.
2. Copy the chosen accepted HTML into `work/CODE-vN/`. For a new guide,
   use an appropriate accepted layout as a starting point and check that its
   new code is free in the registry. Keep the established filename stem.
3. Pick an integer above `highestKnownVersions[CODE]` in the manifest and
   check the current repository for versions created after this snapshot.
   For example, a WF-02 revision must follow the existing v12 proposal even
   though v10 is the accepted PDF. Do not reuse a rejected version number.
4. Update the visible code/title, version/date and exact content in the copied
   HTML. A re-render does not automatically change its printed version or
   review label. New or visibly changed content needs a new owner decision.
5. Render the new HTML, for example after preparing SC-01 v13:

```powershell
node scripts/render.mjs --html work/SC-01-v13/SC-01-asset-vinden-en-openen-v13-draft.html --run sc01-v13-proof
```

This command rejects filenames that reuse known version numbers. Review the
new proof against its accepted predecessor. The exact-equality comparison
script is intended for regeneration, not for deliberately changed drafts.

Preserve screenshots, hints, bottom help, completion checks and useful page
width. Keep each task's layout; do not force the rejected sparse generic grid.
Write predictable steps for users who do not know the system. Use family
color, marker, code and title for handoffs. Check the whole badge/crop/control
boundary, not just its centre, after any screenshot or spacing change.
WF-01 keeps profile selection at 2A and starting at 3A. USR-04 is disable-login
only. Testing completed is a future QA status preference, not a live status
implemented by these files. The catalogue diagram prototype is still separate.

Update the guide specification, exact review record, affected incoming
references, registry and decision ledger in the main repository. Original
generator sources are retained under `source/repository/scripts/manuals/` for
larger changes; some expect the full repository and historical paths. They
are provenance/authoring references, not the portable entry points above.
Links in those original documents retain repository-relative context; app
code and unaccepted artifact links may require the original repository.

After approval, preserve the exact new PDF and hash, then build the next kit
version. Do not change this kit's acceptance records to make a proof accepted.

## Validation And Provenance

Assembly verifies source and copied PDF hashes and records all package files.
The regeneration test is recorded in [VALIDATION.md](VALIDATION.md).
Acceptance sources are linked per guide in the manifest. Exact full-set
selection is based on the fourteen owner-cleared current versions plus the
earlier WF-02/CMP-01 acceptances, not on filename dates or generation labels.
'''
(DEST/'README.md').write_text(readme,encoding='utf-8')
(DEST/'VALIDATION.md').write_text('# Package Validation\n\nRegeneration validation is pending. Original/copy hashes were verified at assembly.\n',encoding='utf-8')
manifest={'schemaVersion':1,'packageVersion':1,'createdOn':'2026-09-10','scope':'Latest recorded accepted version per guide code, plus prior accepted PDFs',
          'acceptanceMeaning':'Internal review candidate; no third-party approval implied',
          'guides':guides,'history':historical,'excludedUnaccepted':excluded,
          'highestKnownVersions':highest,'files':[]}
for p in sorted(DEST.rglob('*')):
    if p.is_file():manifest['files'].append({'path':p.relative_to(DEST).as_posix(),'sha256':sha(p),'bytes':p.stat().st_size})
(DEST/'manifest.json').write_text(json.dumps(manifest,indent=2)+'\n',encoding='utf-8')
(DEST/'manifest.sha256').write_text(sha(DEST/'manifest.json')+'  manifest.json\n',encoding='utf-8')
print(json.dumps({'folder':str(DEST),'acceptedGuides':len(guides),'acceptedPages':sum(g['pages'] for g in guides),'historicalPdfs':len(history),'files':len(manifest['files'])},indent=2))
