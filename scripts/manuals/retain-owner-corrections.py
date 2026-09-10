"""Retain exact owner-correction candidates; refuse to replace a prior round."""
import hashlib
import json
from pathlib import Path
import shutil

ROOT=Path(__file__).resolve().parents[2]
ROUND=ROOT/'resources/manuals/operator-guides/review-rounds/2026-09-10'
DEST=ROUND/'owner-corrections'
PROOF=ROOT/'output/manuals/proofs/owner-corrections-2026-09-10'
sha=lambda p:hashlib.sha256(p.read_bytes()).hexdigest()
selection=json.loads((ROUND/'current-guide-selection-v1.json').read_text())
previous=selection['guides']
for e in previous:
    assert sha(ROOT/e['path'])==e['sha256'],e['code']
assert not DEST.exists(),'Review rounds are immutable. Use a new version/round.'
DEST.mkdir()
records={}
for p in sorted(PROOF.glob('*-v*/validation.json')):
    r=json.loads(p.read_text())
    if 'code' not in r:
        continue
    pdf=ROOT/'output/pdf'/r['file']
    assert sha(pdf)==r['sha256']
    assert all(r['focused'].values()) and not any(i['outside'] or i['overflow'] for i in r['inventory'])
    shutil.copyfile(pdf,DEST/r['file'])
    records[r['code']]=r
assert len(records)==8
prototype='Catalogus-van-invoer-naar-resultaat-v1-draft.pdf'
shutil.copyfile(ROOT/'output/pdf'/prototype,DEST/prototype)
current=[]
for e in previous:
    if e['code'] in records:
        r=records[e['code']]
        e={**e,'version':r['version'],'pages':r['pages'],'sha256':r['sha256'],
           'path':(DEST/r['file']).relative_to(ROOT).as_posix(),'ownerAccepted':False,'reviewLabel':'Te beoordelen'}
        if e['code']=='USR-04':e['title']='Gebruiker uitschakelen'
    current.append(e)
assert sum(e['pages'] for e in current)==47
selection={**selection,'schemaVersion':2,'bundleVersion':3,
           'purpose':'Current owner-correction review candidates; prior acceptance stays attached to exact unchanged versions.',
           'decisionSource':'Owner authorized implementation on 2026-09-10; no acceptance of these new candidates yet.',
           'previousSelection':'current-guide-selection-v1.json','guides':current}
(ROUND/'current-guide-selection-v2.json').write_text(json.dumps(selection,indent=2)+'\n',encoding='utf-8')
manifest={'createdOn':'2026-09-10','status':'Unaccepted candidates',
          'previousSelection':'../current-guide-selection-v1.json','currentSelection':'../current-guide-selection-v2.json',
          'artifacts':[{k:r[k] for k in ['code','old','version','file','pages','sha256','bytes','input','inputSha256','focused']} for r in records.values()],
          'prototype':{'file':prototype,**json.loads((PROOF/'catalog-field-map-v1/validation.json').read_text())},
          'preservedPreviousGuideHashes':22,'acceptedVersionsUnchanged':['AC-01 v9','AC-02 v4','AST-03 v15','AST-04 v6']}
(DEST/'manifest.json').write_text(json.dumps(manifest,indent=2)+'\n',encoding='utf-8')
shutil.copyfile(Path(__file__),DEST/Path(__file__).name)
print('Retained 8 candidates, separate prototype, and 22-guide current selection (47 pages).')
