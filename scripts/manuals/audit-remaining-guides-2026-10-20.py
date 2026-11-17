"""Read-only artifact checks; instructional usability still requires rendered review."""
import ast
import base64
import hashlib
from html.parser import HTMLParser
import json
from pathlib import Path
import re
from urllib.parse import unquote, urlsplit

from PIL import Image, ImageChops
from pypdf import PdfReader

ROOT = Path(__file__).resolve().parents[2]
ROUND = ROOT / 'resources/manuals/operator-guides/review-rounds/2026-10-20-remaining-guides'
read = lambda p: json.loads(p.read_text(encoding='utf-8-sig'))
sha = lambda p: hashlib.sha256(p.read_bytes()).hexdigest()
manifest = read(ROUND / 'authoring-manifest.json')
report = {'guides': [], 'sourceCrops': 0, 'historicalPackages': [], 'localLinks': 0, 'syntax': []}
class EmbeddedImages(HTMLParser):
    def __init__(self): super().__init__(); self.images=[]
    def handle_starttag(self, tag, attrs):
        if tag=='image':
            a=dict(attrs); self.images.append((a.get('data-recognition-source'), a.get('href') or a.get('xlink:href')))
source_hashes={s['key']:s.get('cropSha256',s['sourceSha256']) for s in manifest['sources']}
report['embeddedImages']=0
assert len(manifest['guides']) == 17
for g in manifest['guides']:
    p = ROOT / g['pdf']; pdf = PdfReader(p)
    parser=EmbeddedImages();parser.feed((ROOT/g['html']).read_text(encoding='utf-8'))
    for key,uri in parser.images:
        digest=hashlib.sha256(base64.b64decode(uri.split(',',1)[1])).hexdigest()
        if key:
            assert digest==source_hashes[key], (g['code'],key,'embedded source mismatch')
        else:
            assert g['code']=='CAT-06', (g['code'],'unmapped image')
        report['embeddedImages']+=1
    if g['code']=='CAT-06':
        old=next((ROUND/'inputs').glob('cat-06*.html'));baseline=EmbeddedImages();baseline.feed(old.read_text(encoding='utf-8'))
        assert [uri for _,uri in parser.images]==[uri for _,uri in baseline.images], 'Retained CAT-06 sources changed'
    assert len(pdf.pages) == g['pageCount']
    assert sha(p) == g['sha256'] == sha(ROOT / 'output/pdf' / p.name)
    for page in pdf.pages:
        # Chromium quantizes CSS paper dimensions; allow less than 0.2 mm.
        assert abs(float(page.mediabox.width) * 25.4 / 72 - 210) < .2
        assert abs(float(page.mediabox.height) * 25.4 / 72 - 297) < .2
        text = page.extract_text()
        assert g['code'] in text and len(text) > 200
        assert not re.search(r'\ufffd|QR volgt|In voorbereiding|Werkconcept', text, re.I)
    report['guides'].append({'code': g['code'], 'pages': len(pdf.pages), 'sha256': g['sha256'], 'a4': True, 'mediaBoxPoints': [float(pdf.pages[0].mediabox.width), float(pdf.pages[0].mediabox.height)], 'text': True})
assert sum(g['pages'] for g in report['guides']) == 56
for s in manifest['sources']:
    source = ROOT / s['source']; assert sha(source) == s['sourceSha256']
    if 'crop' not in s:
        continue
    assert s['locale'] == 'nl-NL' and s['role'] != 'Recorded capture actor'
    crop = ROOT / s['crop']; assert sha(crop) == s['cropSha256']
    b = s['cropPixels']
    with Image.open(source) as src, Image.open(crop) as dst:
        expected = src.crop((b['x'], b['y'], b['x']+b['w'], b['y']+b['h'])).convert('RGBA')
        assert dst.size == expected.size
        assert ImageChops.difference(expected, dst.convert('RGBA')).getbbox() is None, s['key']
    for m in s['marks']:
        pad = m.get('padding', 0)
        assert m['x']-pad >= 0 and m['y']-pad >= 0
        assert m['x']+m['w']+pad <= b['w'] and m['y']+m['h']+pad <= b['h']
    report['sourceCrops'] += 1
for folder in ['accepted-guides-v1','review-rounds/2026-10-08-test-guides','review-rounds/2026-10-08-focus-corrections','review-rounds/2026-10-20-operational-guides']:
    p = ROOT / 'resources/manuals/operator-guides' / folder; old = read(p / 'manifest.json')
    seal = (p / 'manifest.sha256').read_text().split()[0]
    assert sha(p / 'manifest.json') == seal
    for f in old['files']:
        assert sha(p / f['path']) == f['sha256'], str(p / f['path'])
    report['historicalPackages'].append({'package':folder,'files':len(old['files']),'unchanged':True})
class Links(HTMLParser):
    def __init__(self): super().__init__(); self.links=[]
    def handle_starttag(self, tag, attrs):
        for k,v in attrs:
            if k in ['href','src'] and v: self.links.append(v)
parser=Links(); parser.feed((ROUND / 'review.html').read_text(encoding='utf-8'))
for url in parser.links:
    u=urlsplit(url)
    if u.scheme or not u.path: continue
    assert (ROUND / unquote(u.path)).resolve().exists(), url
    report['localLinks'] += 1
docs=ROOT/'docs/manuals/operator-guides'
for g in manifest['guides']:
    for p in [docs/'guides'/f'{g["code"]}.md', docs/'reviews'/f'{g["code"]}-v{g["version"]}.md']:
        for url in re.findall(r'\]\(([^)]+)\)',p.read_text(encoding='utf-8')):
            u=urlsplit(url)
            if u.scheme or not u.path: continue
            assert (p.parent/unquote(u.path)).resolve().exists(), (p,url)
            report['localLinks'] += 1
for name in ['remaining-crop-plan','package-remaining-guides','audit-remaining-guides']:
    p=ROOT/'scripts/manuals'/f'{name}-2026-10-20.py'; ast.parse(p.read_text(encoding='utf-8')); report['syntax'].append(p.name)
report['visualReview'] = 'All 56 pages read back; affected pages rechecked after corrections. This is editorial review, not a novice-reader or physical A4 trial.'
destination = (ROOT / 'output/manuals/proofs/remaining-guides-2026-10-20/artifact-audit-postseal.json') if (ROUND / 'manifest.sha256').exists() else (ROUND / 'qa/artifact-audit.json')
destination.write_text(json.dumps(report,indent=2)+'\n',encoding='utf-8')
print(json.dumps({'guides':len(report['guides']),'pages':56,'sourceCrops':report['sourceCrops'],'historicalFiles':sum(p['files'] for p in report['historicalPackages']),'localLinks':report['localLinks'],'errors':0}))
