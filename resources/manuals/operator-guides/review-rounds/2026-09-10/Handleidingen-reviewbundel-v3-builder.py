"""Build plain v3 comparison PDFs, preserving every supplied guide page."""
from concurrent.futures import ThreadPoolExecutor
import hashlib
import json
from pathlib import Path
import shutil
import subprocess

from PIL import Image
from pypdf import PdfReader,PdfWriter

ROOT=Path(__file__).resolve().parents[2]
ROUND=ROOT/'resources/manuals/operator-guides/review-rounds/2026-09-10'
PROOF=ROOT/'output/manuals/proofs/reviewbundel-v3'
PROOF.mkdir(parents=True,exist_ok=True)
sha=lambda p:hashlib.sha256(p.read_bytes()).hexdigest()


def render(job):
    source,prefix=job
    subprocess.run(['pdftoppm','-png','-r','96',str(source),str(prefix)],check=True,capture_output=True)


def pixels(p):
    with Image.open(p) as im:
        return im.size,hashlib.sha256(im.convert('RGB').tobytes()).hexdigest()


previous=json.loads((ROUND/'current-guide-selection-v1.json').read_text())['guides']
current=json.loads((ROUND/'current-guide-selection-v2.json').read_text())['guides']
assert [e['code'] for e in previous]==[e['code'] for e in current]
report={'bundleVersion':3,'createdOn':'2026-09-10',
        'previousSelection':'Immediately preceding reviewed set, formerly v2-huidig; 48 pages.',
        'currentSelection':'Eight owner-correction candidates plus fourteen unchanged guides; 47 pages.',
        'identicalGuideOrder':True,'boundaryDifference':'USR-04 is one page shorter. CAT pages start one page earlier in current.',
        'artifacts':[]}
for label,entries in [('vorig',previous),('huidig',current)]:
    name=f'Handleidingen-reviewbundel-v3-{label}.pdf'
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
    report['artifacts'].append({'file':name,'pages':len(merged.pages),'guides':22,'sha256':sha(output),
                                'addedPages':0,'bookmarks':0,'sourcePixelsIdenticalAt96dpi':len(merged.pages),
                                'contentStreamsAndPageBoxesPreserved':True,'pageMap':mapping})
    print(f'{label}: {len(merged.pages)} pages, all pixel-identical to individual PDFs.',flush=True)
(ROUND/'Handleidingen-reviewbundel-v3-manifest.json').write_text(json.dumps(report,indent=2)+'\n',encoding='utf-8')
shutil.copyfile(Path(__file__),ROUND/'Handleidingen-reviewbundel-v3-builder.py')
