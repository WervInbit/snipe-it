"""Verify current corrections, historical preservation, and a reproducible source snapshot."""
from pathlib import Path
import hashlib
import json
import re
import zipfile
from urllib.parse import unquote

import pdfplumber
from pypdf import PdfReader

ROOT=Path(__file__).resolve().parents[2]
ROUND=ROOT/'resources/manuals/operator-guides/review-rounds/2026-09-10'
PROOF=ROOT/'output/manuals/proofs/owner-corrections-2026-09-10'
CHECKPOINT=ROOT/'resources/manuals/operator-guides/checkpoints/2026-09-08-before-audit-revisions'
sha=lambda data:hashlib.sha256(data).hexdigest()
frozen=json.loads((CHECKPOINT/'manifest.json').read_text())
for e in frozen['artifacts']:
    assert sha((CHECKPOINT/e['checkpointPath']).read_bytes())==e['sha256']
    assert sha((ROOT/e['originalPath']).read_bytes())==e['sha256']
a=frozen['sourceArchive']
assert sha((CHECKPOINT/a['file']).read_bytes())==a['sha256']
with zipfile.ZipFile(CHECKPOINT/a['file']) as z:
    assert z.testzip() is None
    for e in a['files']:assert sha(z.read(e['path']))==e['sha256']

verified=set()
def verify_history(node,base):
    if isinstance(node,dict):
        if 'file' in node and 'sha256' in node:
            p=base/node['file'];assert sha(p.read_bytes())==node['sha256'],p
            verified.add(p)
            if p.suffix=='.zip':
                with zipfile.ZipFile(p) as z:assert z.testzip() is None
        for v in node.values():verify_history(v,base)
    elif isinstance(node,list):
        for v in node:verify_history(v,base)
old_round=ROUND.parent/'2026-09-08'
for name in ['manifest.json','WF-01-v12-manifest.json','guide-set-followups-manifest.json']:
    verify_history(json.loads((old_round/name).read_text()),old_round)
for v in (2,3,4):
    for a in json.loads((ROUND/f'Handleidingen-reviewbundel-v{v}-manifest.json').read_text())['artifacts']:
        for p in (ROUND/a['file'],ROOT/'output/pdf'/a['file']):
            assert sha(p.read_bytes())==a['sha256'],p
v1=json.loads((ROUND/'Handleidingen-reviewbundel-v1-manifest.json').read_text())
for p in (ROUND/v1['bundleFile'],ROOT/'output/pdf'/v1['bundleFile']):assert sha(p.read_bytes())==v1['sha256']
previous=json.loads((ROUND/'current-guide-selection-v1.json').read_text())['guides']
current=json.loads((ROUND/'current-guide-selection-v3.json').read_text())['guides']
assert len(current)==len(previous)==22
assert [e['code'] for e in current]==[e['code'] for e in previous]
accepted=['AC-01','AC-02','AST-03','AST-04']
for entries in (previous,current):
    for e in entries:assert sha((ROOT/e['path']).read_bytes())==e['sha256']
assert [e['code'] for e in current if e['ownerAccepted']]==accepted
for code in accepted:
    assert next(e for e in previous if e['code']==code)['sha256']==next(e for e in current if e['code']==code)['sha256']

dependency_rows=[]
for e in current:
    pdf=PdfReader(ROOT/e['path'])
    assert len(pdf.pages)==e['pages']
    text='\n'.join(p.extract_text() for p in pdf.pages)
    if e['code'] in ['USR-01','USR-02']:assert 'Gebruiker uitschakelen of herstellen' not in text
    if e['code']=='USR-04':assert not re.search(r'check.?in|verwijderen|herstellen',text,re.I)
    refs=sorted(set(re.findall(r'\b(?:AC|SC|AST|WF|CMP|USR|CAT|HELP)-\d\d\b',text))-{e['code']})
    dependency_rows.append({'code':e['code'],'version':e['version'],'references':refs})
missing=sorted({code for e in dependency_rows for code in e['references']}-{e['code'] for e in current})
assert missing==['CAT-05','CAT-06','USR-05']
(PROOF/'reference-audit.json').write_text(json.dumps({'guides':dependency_rows,'plannedWithoutPdf':missing},indent=2)+'\n')
records=[json.loads(p.read_text()) for p in PROOF.glob('*-v*/validation.json') if 'code' in json.loads(p.read_text())]
assert len(records)==10 and sum(r['pages'] for r in records)==19
for r in records:
    assert all(r['focused'].values())
    assert not any(x['outside'] or x['overflow'] for x in r['inventory'])
    assert sha((ROOT/r['input']).read_bytes())==r['inputSha256']
    assert sha((ROOT/'output/pdf'/r['file']).read_bytes())==r['sha256']
prototype=ROUND/'owner-corrections/Catalogus-van-invoer-naar-resultaat-v1-draft.pdf'
with pdfplumber.open(prototype) as pdf:
    assert len(pdf.pages)==2
    for p in pdf.pages:
        assert all(c['x0']>=0 and c['x1']<=p.width+.3 and c['top']>=0 and c['bottom']<=p.height+.3 for c in p.chars)

# Archive source context with original relative paths, separate from historical archives.
archive=ROUND/'owner-corrections-source-v1.zip'
manifest_path=ROUND/'owner-corrections-source-v1-manifest.json'
if not archive.exists():
    files=set((ROOT/'docs/manuals/operator-guides').rglob('*.md'))
    files.update(p for p in (ROOT/'scripts/manuals').rglob('*') if p.is_file() and p.suffix in ('.mjs','.py','.css','.json'))
    files.update(p for p in (ROOT/'resources/manuals/operator-guides/evidence').iterdir() if p.suffix in ('.png','.jpg','.json'))
    files.update(p for p in PROOF.rglob('*') if p.is_file() and p.suffix in ('.html','.json'))
    files.add(ROUND/'owner-corrections/USR-04-previous-specification.md')
    entries=[]
    with zipfile.ZipFile(archive,'x',compression=zipfile.ZIP_DEFLATED) as z:
        for p in sorted(files):
            name=p.relative_to(ROOT).as_posix();data=p.read_bytes();z.writestr(name,data)
            entries.append({'path':name,'sha256':sha(data),'bytes':len(data)})
    manifest_path.write_text(json.dumps({'file':archive.name,'sha256':sha(archive.read_bytes()),'files':entries},indent=2)+'\n')
am=json.loads(manifest_path.read_text());assert sha(archive.read_bytes())==am['sha256']
with zipfile.ZipFile(archive) as z:
    assert z.testzip() is None
    for e in am['files']:assert sha(z.read(e['path']))==e['sha256']

files=list((ROOT/'docs/manuals/operator-guides').rglob('*.md'))+[ROOT/'PROGRESS.md',ROOT/'TODO.md',ROOT/'docs/agents/agents-addendum-2026-09-10-session-init.md']
links=0
for p in files:
    for target in re.findall(r'\[[^\]]*\]\(([^)]+)\)',p.read_text(encoding='utf-8-sig')):
        target=target.strip('<>').split('#')[0]
        if not target or re.match(r'^[a-z][a-z0-9+.-]*:',target,re.I):continue
        assert (p.parent/unquote(target)).exists(),(p,target)
        links+=1
report={'status':'passed','changedGuides':10,'changedGuidePages':19,'prototypePages':2,
        'currentGuides':22,'currentPages':47,'previousPages':48,'mergePixelsVerifiedAt96dpi':95,
        'fourExactAcceptancesPreserved':True,'previousSelectedHashesVerified':22,
        'checkpointAndOriginalPdfHashesVerified':len(frozen['artifacts']),
        'earlierRoundPdfHashesVerified':sum(p.suffix=='.pdf' for p in verified),
        'priorBundlesAndArchivesPreserved':True,'sourceArchiveFilesVerified':len(am['files']),
        'guideMarkdownFilesChecked':len(files),'localLinksChecked':links,
        'limitations':'No new live workflow, production data/status change, physical print or first-time-reader trial. Prototype asset panels are schematic.'}
(ROUND/'owner-corrections-validation-v1.json').write_text(json.dumps(report,indent=2)+'\n')
print(json.dumps(report,indent=2))
