"""Package and validate the AST-03 editorial pilot without changing older rounds."""
from pathlib import Path
import base64
import hashlib
from html.parser import HTMLParser
import json
import shutil
import subprocess
import sys
import xml.etree.ElementTree as ET
import zipfile

from PIL import Image, ImageChops
from pypdf import PdfReader

ROOT = Path(__file__).resolve().parents[2]
ROUND = ROOT / 'resources/manuals/operator-guides/review-rounds/2026-11-04-ast03-pilot'
PRIOR = ROOT / 'resources/manuals/operator-guides/review-rounds/2026-10-27-navigation-and-space'
DOCS = ROOT / 'docs/manuals/operator-guides'
read = lambda p: json.loads(p.read_text(encoding='utf-8-sig'))
sha = lambda p: hashlib.sha256(p.read_bytes()).hexdigest()
write = lambda p, s: p.write_text(s, encoding='utf-8', newline='\n')
dump = lambda p, data: write(p, json.dumps(data, indent=2) + '\n')
assert len(sys.argv) == 2 and sys.argv[1] in ('prepare', 'seal')
assert not (ROUND / 'manifest.sha256').exists(), 'Sealed round: create another version.'
mode = sys.argv[1]
m = read(ROUND / 'authoring-manifest.json')
g = m['guide']
stem = g['stem']
poppler = Path.home() / '.cache/codex-runtimes/codex-primary-runtime/dependencies/native/poppler/Library/bin/pdftoppm.exe'

if mode == 'prepare':
    accepted = ROOT / 'resources/manuals/operator-guides/review-rounds/2026-09-08/AST-03-asset-registreren-en-labelen-v15-draft.pdf'
    assert sha(accepted) == '5e838b9e57e868f81c2b7920ea5e4d76cde487a39fb406736912f7082ebe875d'
    shutil.copyfile(accepted, ROUND / 'baselines' / accepted.name)
    subprocess.run([str(poppler), '-r', '144', '-png', str(accepted), str(ROUND / 'baselines/accepted-v15')], check=True, capture_output=True)
    for ext in ('pdf', 'html'):
        p = PRIOR / f'AST-03-asset-registreren-en-labelen-v18-draft.{ext}'
        shutil.copyfile(p, ROUND / 'baselines' / p.name)
    for n in (1, 2):
        shutil.copyfile(PRIOR / f'renders/AST-03-{n}.png', ROUND / f'baselines/previous-v18-{n}.png')
    subprocess.run([str(poppler), '-r', '96', '-png', str(ROOT / g['pdf']), str(ROUND / 'qa/a4-size')], check=True, capture_output=True)
    changes = [
        ('Start en aanmaken', 'Dashboard toont Apparaten en Nieuwe Asset samen. Eerst zoeken; bij geen passend record de + gebruiken of met Terug naar Dashboard en Nieuwe Asset. De zoekbalk blijft 130 mm breed.'),
        ('Gevonden apparaat', 'Klik op de assettag van het passende apparaat. Een bestaand record gaat naar dezelfde identiteitscontrole bij stap 5; er wordt geen tweede registratie aangemaakt.'),
        ('Identiteit en model', 'De volledige tag/S/N- en categorie/modelvelden groeien van 79 naar 84 mm. Unlock, Aa, Product ID/P/N en het deel na # blijven uitgelegd.'),
        ('Ontbrekend model', 'De volledige CAT-01-verwijzing staat nu bij de ontbrekende modelcode, met terugkeer naar stap 3.'),
        ('Notities en Opslaan', 'Statusnotitie en Notities zijn beide optioneel bij registratie. De echte Opslaan-knop blijft zichtbaar met locatie bovenaan het formulier. Duplicaatmeldingen leiden terug naar vergelijken.'),
        ('Controle en afdrukken', 'Opgeslagen identiteit, Asset openen, standaardkeuzes van snelprinten, QR-preview, template, formaat, printer en afdrukknop blijven aanwezig. Stap 5 noemt waar Status staat.'),
        ('Plaatsen en scannen', 'Volledige onderzijde blijft 80 mm breed. Nieuw beeld 8A toont echte tag/S/N-velden in de geopende asset; het model staat bovenaan. Scan-, beschadigd-label- en verkeerde-identiteitshulp blijven staan.'),
        ('Rust en compositie', 'Alleen de +, Asset openen en het fysieke QR-label hebben een focusmarkering. De zoekbalk heeft al een native gele markering. Geen herhaalde STOP-blokken; acht doorlopende stappen over twee zijden.'),
    ]
    dump(ROUND / 'changes.json', {'date': '2026-11-04', 'guide': 'AST-03 v19', 'changes': changes})
    rows = ''.join(f'<tr><th>{title}</th><td>{detail}</td></tr>' for title, detail in changes)
    pairs = ''.join(f'''<section><h2>Bladzijde {n}</h2><div class="pair"><article><h3 class="old-label">Vorige v18</h3><a class="old-image-link" href="baselines/previous-v18-{n}.png"><img class="old-image" data-page="{n}" src="baselines/previous-v18-{n}.png" alt="Vergelijkingsversie bladzijde {n}"></a></article><article><h3>Nieuwe v19 - concept</h3><a href="renders/AST-03-{n}.png"><img src="renders/AST-03-{n}.png" alt="AST-03 v19 bladzijde {n}"></a></article></div></section>''' for n in (1, 2))
    write(ROUND / 'review.html', f'''<!doctype html><html lang="nl"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>AST-03 v19 - vergelijking</title><style>
body{{font:16px/1.5 Arial,sans-serif;background:#e8edf2;color:#102033;margin:0}}main{{max-width:1680px;padding:24px;margin:auto}}header,.notes{{background:white;border:1px solid #c8d5e2;border-radius:10px;padding:20px;margin-bottom:22px}}h1{{font-size:27px;margin:0 0 10px}}h2{{margin:24px 0 10px}}h3{{font-size:16px;margin:5px 0 10px}}a{{color:#135eaa}}nav{{display:flex;gap:20px;flex-wrap:wrap}}label{{display:inline-block;margin:16px 20px 0 0}}select,button{{font:inherit;padding:6px}}.pair{{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:24px}}article{{min-width:0}}img{{width:100%;height:auto;max-width:210mm;display:block;background:white;box-shadow:0 2px 6px #0002}}table{{border-collapse:collapse;width:100%}}th,td{{padding:10px;border-bottom:1px solid #dde4eb;text-align:left;vertical-align:top}}th{{width:180px}}.actual .pair{{grid-template-columns:repeat(2,210mm);width:max-content}}.actual section{{overflow-x:auto}}.small{{color:#53657a;font-size:14px}}@media(max-width:720px){{main{{padding:12px}}.pair{{gap:12px}}th{{width:110px}}}}
</style><main><header><h1>AST-03 v19: registreren en labelen</h1><p>Twee A4-zijden. Werkconcept voor beoordeling; geen acceptatie.</p><nav><a href="{stem}.pdf">Nieuwe v19 PDF</a><a href="baselines/AST-03-asset-registreren-en-labelen-v18-draft.pdf">Vorige v18 PDF</a><a href="baselines/AST-03-asset-registreren-en-labelen-v15-draft.pdf">Geaccepteerde v15 PDF</a><a href="editable-sources.zip">SVG-bronnen</a></nav><label>Vergelijk met <select id="baseline"><option value="18">Vorige v18 (afgekeurde instap)</option><option value="15">Geaccepteerde v15</option></select></label><button id="scale" type="button">Toon op 100% A4</button><p class="small">Beide kolommen hebben dezelfde papierbreedte. Klik op een pagina voor de volledige afbeelding. De inhoud is herzien met behouden echte Nederlandse opnamen; een verse controle in de live applicatie is nog niet uitgevoerd.</p></header><details class="notes" open><summary>Wat is veranderd en behouden?</summary><table>{rows}</table></details>{pairs}</main><script>
document.querySelector('#baseline').addEventListener('change',e=>{{const old=e.target.value;document.querySelectorAll('.old-label').forEach(n=>n.textContent=old==='15'?'Geaccepteerde v15':'Vorige v18');document.querySelectorAll('.old-image').forEach(n=>{{n.src='baselines/'+(old==='15'?'accepted-v15-':'previous-v18-')+n.dataset.page+'.png';n.parentElement.href=n.src;}});}});document.querySelector('#scale').addEventListener('click',e=>{{const active=document.body.classList.toggle('actual');e.target.textContent=active?'Pas naast elkaar in venster':'Toon op 100% A4';}});
</script></html>''')
    index = read(PRIOR / 'current-authoring-index.json')
    index['guides'] = [g if old['code'] == 'AST-03' else old for old in index['guides']]
    index['status'] = '25 authoring proposals; AST-03 v19 is an unaccepted pilot; other 24 proposals and accepted/runtime selections unchanged'
    dump(ROUND / 'current-authoring-index.json', index)
    asset_dir = ROUND / 'editable/images'
    asset_dir.mkdir(exist_ok=True)
    for s in m['sources']:
        source = ROOT / s.get('crop', s['source'])
        shutil.copyfile(source, asset_dir / (s['key'] + source.suffix))
    text = '\n\n'.join(f'Bladzijde {n}\n\n' + '\n'.join(' '.join(t.itertext()) for t in ET.parse(ROUND / f'{stem}-page-{n}.svg').getroot().iter() if t.tag.endswith('}text')) for n in (1, 2))
    write(ROUND / 'editable/CONTENT.txt', text + '\n')
    write(ROUND / 'editable/README.txt', 'AST-03 v19 working draft, two A4 SVG pages. Live text and separate raster/focus objects. Exact raster images and text are supplied alongside. Native Affinity import and font behavior are untested. This is interchange source, not a native Affinity document.\n')
    with zipfile.ZipFile(ROUND / 'editable-sources.zip', 'w', zipfile.ZIP_DEFLATED) as z:
        for p in sorted((ROUND / 'editable').rglob('*')):
            if p.is_file(): z.write(p, p.relative_to(ROUND / 'editable').as_posix())
    write(ROUND / 'README.md', '''# AST-03 Pilot - 2026-11-04

Working draft v19, two continuous A4 sides; not accepted. Open
[review.html](review.html) for equal-width comparisons against rejected v18
and accepted v15, with the concrete change list on the same page.

The brief and preservation basis were recorded before composition in
[the version review](../../../../../docs/manuals/operator-guides/reviews/AST-03-v19.md).
Initial inputs, complete genuine Dutch captures and state, exact measured
crops, live-text SVG/HTML, final PDF/renders, editable interchange and QA are
retained. Original captures are from October27. The current root and captured
checkout differ; see evidence-provenance.json. There was no fresh live UI,
database/fixture mutation, physical print, novice-reader trial or Affinity
import. The other 24 proposals and accepted/runtime selections are unchanged.

Generator: scripts/manuals/generate-ast03-pilot-2026-11-04.mjs. Packaging and
artifact validation: scripts/manuals/package-ast03-pilot-2026-11-04.py.
Frozen inputs reproduce this proposal and do not override owner decisions.
''')
    write(ROUND / '.gitattributes', '** -text\n')

report = {'pdfPages': 0, 'sourceCrops': 0, 'embeddedImages': 0, 'priorRoundFiles': 0, 'localLinks': 0}
pdf = ROOT / g['pdf']
assert sha(pdf) == g['sha256'] == sha(ROOT / 'output/pdf' / pdf.name)
reader = PdfReader(pdf)
assert len(reader.pages) == 2
body = '\n'.join(p.extract_text() for p in reader.pages)
for required in ['Nieuwe Asset', 'Dashboard', 'Serienummer', 'optioneel', 'Opslaan', 'Afdrukformaat', 'Printerlocatie', 'Supervisor', 'Scan het label']:
    assert required in body, required
assert 'STOP' not in body
source_hashes = {s['key']: s.get('cropSha256', s['sourceSha256']) for s in m['sources']}
for n, p in enumerate(reader.pages, 1):
    assert abs(float(p.mediabox.width) * 25.4 / 72 - 210) < .2
    assert abs(float(p.mediabox.height) * 25.4 / 72 - 297) < .2
    svg = ET.parse(ROUND / f'{stem}-page-{n}.svg').getroot()
    assert sum(1 for e in svg.iter() if e.tag.endswith('}text')) > 20
    for e in svg.iter():
        if e.tag.endswith('}image'):
            href = e.attrib.get('href', e.attrib.get('{http://www.w3.org/1999/xlink}href'))
            payload = base64.b64decode(href.split(',', 1)[1])
            assert hashlib.sha256(payload).hexdigest() in source_hashes.values()
            report['embeddedImages'] += 1
    report['pdfPages'] += 1
for s in m['sources']:
    source = ROOT / s['source']
    assert sha(source) == s['sourceSha256']
    if s.get('crop'):
        cropped = ROOT / s['crop']
        assert sha(cropped) == s['cropSha256']
        b = s['cropPixels']
        with Image.open(source) as im, Image.open(cropped) as actual:
            expected = im.crop((b['x'], b['y'], b['x'] + b['w'], b['y'] + b['h'])).convert('RGB')
            assert ImageChops.difference(expected, actual.convert('RGB')).getbbox() is None, s['key']
        report['sourceCrops'] += 1

# Validate the lost concrete control against original measured source bounds.
dashboard = next(s for s in m['sources'] if s['key'] == 'ast-dashboard')
ds = read(ROUND / 'evidence/AST-DASH.json')
for href in ('https://dev.inbit/hardware', 'https://dev.inbit/hardware/create'):
    target = next(e for e in ds['elements'] if e['tag'] == 'A' and e['href'] == href and (href.endswith('create') or 'alles weergeven' in e['text']))['bounds']
    b = dashboard['cropPixels']
    assert target['x'] * 1.5 >= b['x'] and target['y'] * 1.5 >= b['y']
    assert (target['x'] + target['w']) * 1.5 <= b['x'] + b['w']
    assert (target['y'] + target['h']) * 1.5 <= b['y'] + b['h']
old = read(PRIOR / 'authoring-manifest.json')
deltas = []
for key in ('ast-search', 'ast-identity', 'ast-model', 'ast-saved', 'ast-print', 'ast-preview', 'placement'):
    prev = next(f for f in old['figures'] if f['key'] == key)
    now = next(f for f in m['figures'] if f['key'] == key)
    assert now['frameMm']['w'] >= prev['frameMm']['w'], key
    deltas.append({'recognitionJob': key, 'v18WidthMm': prev['frameMm']['w'], 'v19WidthMm': now['frameMm']['w']})
dump(ROUND / 'qa/recognition-comparison.json', {'comparisons': deltas, 'bothDashboardControlsFullyRetained': True,
    'marks': {s['key']: len(s.get('marks', [])) for s in m['sources']},
    'interpretation': 'These checks preserve pixels and useful scale. Reader usability still requires editorial/reader review.'})
old_frozen = read(PRIOR / 'manifest.json')
for item in old_frozen['files']:
    assert sha(PRIOR / item['path']) == item['sha256'], item['path']
    report['priorRoundFiles'] += 1
assert sha(ROUND / 'baselines/AST-03-asset-registreren-en-labelen-v15-draft.pdf') == '5e838b9e57e868f81c2b7920ea5e4d76cde487a39fb406736912f7082ebe875d'

class Links(HTMLParser):
    def handle_starttag(self, tag, attrs):
        for k, v in attrs:
            if k in ('src', 'href') and v and not v.startswith(('http', 'data:', '#')):
                assert (ROUND / v.split('#')[0]).exists(), v
                report['localLinks'] += 1
Links().feed((ROUND / 'review.html').read_text())
geometry = read(ROUND / 'qa/geometry-review.json')
for p in geometry['pages']:
    for key in ('outside', 'textImageOverlap', 'textOverlap', 'textBadgeOverlap', 'stepTextOutside'):
        assert not p[key], (p['page'], key)
dump(ROUND / 'qa/artifact-review.json', report)
if mode == 'seal':
    editorial = read(ROUND / 'qa/editorial-review.json')
    assert editorial['completePagesRead'] == [1, 2]
    assert editorial['pdfSha256'] == g['sha256'] and editorial['blockingFindings'] == []
    assert read(ROUND / 'qa/browser-review.json')['failures'] == []
    for file in ('generate-ast03-pilot-2026-11-04.mjs', 'package-ast03-pilot-2026-11-04.py', 'verify-ast03-pilot-2026-11-04.mjs'):
        shutil.copyfile(ROOT / 'scripts/manuals' / file, ROUND / 'inputs' / file)
    for file in ('guides/AST-03.md', 'reviews/AST-03-v19.md'):
        out = ROUND / 'inputs/final-context' / file
        out.parent.mkdir(parents=True, exist_ok=True)
        shutil.copyfile(DOCS / file, out)
    files = [{'path': p.relative_to(ROUND).as_posix(), 'bytes': p.stat().st_size, 'sha256': sha(p)}
             for p in sorted(ROUND.rglob('*')) if p.is_file() and p.name not in ('manifest.json', 'manifest.sha256')]
    dump(ROUND / 'manifest.json', {'date': '2026-11-04', 'status': 'Working draft; not accepted',
        'ownerAcceptance': False, 'guide': g, 'files': files})
    write(ROUND / 'manifest.sha256', sha(ROUND / 'manifest.json') + '  manifest.json\n')
print(json.dumps({'mode': mode, **report}))
