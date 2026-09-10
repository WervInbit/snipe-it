"""Compare a regeneration proof with every matching accepted PDF; never change originals."""
from concurrent.futures import ThreadPoolExecutor
import hashlib
import json
from pathlib import Path
import os
import subprocess
import sys
from PIL import Image,ImageChops
from pypdf import PdfReader

ROOT=Path(__file__).resolve().parents[1]
run=Path(sys.argv[1]) if len(sys.argv)>1 else None
assert run,'Usage: python scripts/compare.py work/RUN'
run=(ROOT/run).resolve()
assert run.is_relative_to(ROOT/'work')
manifest=json.loads((ROOT/'manifest.json').read_text(encoding='utf-8'))
rendered=json.loads((run/'render-report.json').read_text(encoding='utf-8'))
assert rendered['mode']=='regeneration proof','Review a new draft visually against its predecessor; equality is not expected.'
accepted={g['code']:g for g in manifest['guides']}
proof=run/'comparison';proof.mkdir(exist_ok=True)

def render(job):
    pdf,prefix=job
    subprocess.run([os.environ.get('GUIDE_PDFTOPPM_PATH','pdftoppm'),'-png','-r','96',str(pdf),str(prefix)],check=True,capture_output=True)

jobs=[]
for g in rendered['results']:
    a=accepted[g['code']]
    assert len(PdfReader(run/g['pdf']).pages)==a['pages']
    jobs.extend([(ROOT/a['pdf'],proof/(g['code']+'-accepted')),(run/g['pdf'],proof/(g['code']+'-rendered'))])
with ThreadPoolExecutor(max_workers=4) as pool:list(pool.map(render,jobs))
results=[]
for g in rendered['results']:
    a=accepted[g['code']];same=0;differences=[]
    for page in range(1,a['pages']+1):
        with Image.open(proof/f'{g["code"]}-accepted-{page}.png') as x,Image.open(proof/f'{g["code"]}-rendered-{page}.png') as y:
            if x.size==y.size and x.convert('RGB').tobytes()==y.convert('RGB').tobytes():same+=1
            else:
                bbox=ImageChops.difference(x.convert('RGB'),y.convert('RGB')).getbbox() if x.size==y.size else None
                differences.append({'page':page,'differentBounds':bbox,'acceptedSize':x.size,'renderedSize':y.size})
    results.append({'code':g['code'],'version':a['version'],'pages':a['pages'],'pixelIdenticalPages':same,'differences':differences})
report={'status':'passed' if all(not r['differences'] for r in results) else 'differences require review',
        'dpi':96,'guides':results,'pixelIdenticalPages':sum(r['pixelIdenticalPages'] for r in results)}
(run/'comparison-report.json').write_text(json.dumps(report,indent=2)+'\n',encoding='utf-8')
print(json.dumps(report,indent=2))
sys.exit(0 if report['status']=='passed' else 1)
