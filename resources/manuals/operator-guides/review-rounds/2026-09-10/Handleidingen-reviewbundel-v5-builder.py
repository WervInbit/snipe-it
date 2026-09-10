"""Build versioned plain comparisons, optionally containing changed guides only."""
from concurrent.futures import ThreadPoolExecutor
import hashlib
import json
from pathlib import Path
import shutil
import subprocess
import sys

from PIL import Image
from pypdf import PdfReader,PdfWriter

ROOT=Path(__file__).resolve().parents[2]
ROUND=ROOT/'resources/manuals/operator-guides/review-rounds/2026-09-10'
VERSION=int(sys.argv[1]) if len(sys.argv)>1 else 3
assert VERSION in (3,4,5)
PROOF=ROOT/f'output/manuals/proofs/reviewbundel-v{VERSION}'
PROOF.mkdir(parents=True,exist_ok=True)
sha=lambda p:hashlib.sha256(p.read_bytes()).hexdigest()


def render(job):
    source,prefix=job
    subprocess.run(['pdftoppm','-png','-r','96',str(source),str(prefix)],check=True,capture_output=True)


def pixels(p):
    with Image.open(p) as im:
        return im.size,hashlib.sha256(im.convert('RGB').tobytes()).hexdigest()


previous=json.loads((ROUND/'current-guide-selection-v1.json').read_text())['guides']
current=json.loads((ROUND/f'current-guide-selection-v{min(VERSION-1,3)}.json').read_text())['guides']
assert [e['code'] for e in previous]==[e['code'] for e in current]
if VERSION==5:
    changed={old['code'] for old,new in zip(previous,current) if old['sha256']!=new['sha256']}
    previous=[e for e in previous if e['code'] in changed]
    current=[e for e in current if e['code'] in changed]
    assert len(previous)==len(current)==10
    assert sum(e['pages'] for e in previous)==20 and sum(e['pages'] for e in current)==19
report={'bundleVersion':VERSION,'createdOn':'2026-09-10',
        'scope':'Changed guides only' if VERSION==5 else 'Full set',
        'previousSelection':f'Immediately preceding reviewed versions; {sum(e["pages"] for e in previous)} pages.',
        'currentSelection':f'{8 if VERSION==3 else 10} owner-correction candidates; {sum(e["pages"] for e in current)} pages.',
        'identicalGuideOrder':True,'boundaryDifference':'USR-04 is one page shorter. CAT pages start one page earlier in current.',
        'artifacts':[]}
for label,entries in [('vorig',previous),('huidig',current)]:
    name=f'Handleidingen-reviewbundel-v{VERSION}-{label}.pdf'
    output=ROOT/'output/pdf'/name
    assert not output.exists() and not (ROUND/name).exists(),'Keep earlier PDF versions immutable.'
    writer=PdfWriter()
    readers=[]
    mapping=[]
    for e in entries:
        p=ROOT/e['path']
        assert sha(p)==e['sha256']
        reader=PdfReader(p)
        assert len(reader.pages)==e['pages']
        mapping.append({'code':e['code'],'version':e['version'],'startPage':len(writer.pages)+1,'pages':e['pages'],
                        'sourcePath':e['path'],'sourceSha256':e['sha256']})
        readers.append(reader)
        writer.append(reader,import_outline=False)
    writer.page_mode='/UseNone'
    with output.open('xb') as stream:writer.write(stream)
    merged=PdfReader(output)
    assert len(merged.pages)==sum(e['pages'] for e in entries) and not merged.outline
    index=0
    for reader in readers:
        for p in reader.pages:
            target=merged.pages[index]
            assert target.get_contents().get_data()==p.get_contents().get_data()
            assert list(target.mediabox)==list(p.mediabox) and list(target.cropbox)==list(p.cropbox)
            index+=1
    render((output,PROOF/label))
    with ThreadPoolExecutor(max_workers=4) as pool:
        list(pool.map(render,[(ROOT/e['path'],PROOF/f"{label}-{e['code']}") for e in entries]))
    for e,m in zip(entries,mapping):
        for i in range(e['pages']):
            assert pixels(PROOF/f"{label}-{e['code']}-{i+1}.png")==pixels(PROOF/f"{label}-{m['startPage']+i:02d}.png"),(label,e['code'],i)
        assert sha(ROOT/e['path'])==e['sha256']
    shutil.copyfile(output,ROUND/name)
    report['artifacts'].append({'file':name,'pages':len(merged.pages),'guides':len(entries),'sha256':sha(output),
                                'addedPages':0,'bookmarks':0,'sourcePixelsIdenticalAt96dpi':len(merged.pages),
                                'contentStreamsAndPageBoxesPreserved':True,'pageMap':mapping})
    print(f'{label}: {len(merged.pages)} pages, all pixel-identical to individual PDFs.',flush=True)
(ROUND/f'Handleidingen-reviewbundel-v{VERSION}-manifest.json').write_text(json.dumps(report,indent=2)+'\n',encoding='utf-8')
shutil.copyfile(Path(__file__),ROUND/f'Handleidingen-reviewbundel-v{VERSION}-builder.py')
