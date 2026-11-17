"""Verify and package the unsealed preparation; do not claim native completion."""
from pathlib import Path
import hashlib
import json
import shutil
import urllib.parse
import xml.etree.ElementTree as ET
import zipfile
from PIL import Image, ImageOps, ImageDraw

ROOT = Path(__file__).resolve().parents[2]
OUT = ROOT / 'output/manuals/final-guide-set-2026-11-04'
assert not (OUT / 'manifest.sha256').exists(), 'Sealed release.'
read = lambda p: json.loads(p.read_text(encoding='utf-8'))
digest = lambda p: hashlib.sha256(p.read_bytes()).hexdigest()
inventory = read(OUT / 'source/inventory.json')
assets = read(OUT / 'source/assets.json')
assert len(inventory['guides']) == 25
assert inventory['totalPages'] == 68
geometry = read(OUT / 'qa/geometry.json')
components = read(OUT / 'qa/components.json')
assert all(not g['overflow'] and not g['textImageCollisions'] and not g['textCollisions'] for g in geometry)
assert all(not c['errors'] for c in components)
assert len(geometry) == len(components) == 68
for sha, asset in assets.items():
    assert digest(OUT / asset['file']) == sha

metadata = OUT / 'inputs/evidence-metadata'
metadata.mkdir(parents=True, exist_ok=True)
for sha, asset in assets.items():
    if asset.get('originalSource'):
        source = (ROOT / asset['originalSource']).with_suffix('.json')
        if source.exists():
            shutil.copyfile(source, metadata / (sha[:12] + '-' + source.name))
for b in read(OUT / 'inputs/baseline-index.json'):
    old = OUT / 'inputs/baselines' / f'{b["code"]}-v{b["version"]}.pdf'
    assert digest(old) == b['sha256']
    parent = (ROOT / b['sourcePdf']).parent
    for name in ('manifest.json', 'authoring-manifest.json'):
        source = parent / name
        if source.exists():
            dest = OUT / 'inputs/frozen-manifests' / parent.name / name
            dest.parent.mkdir(parents=True, exist_ok=True)
            shutil.copyfile(source, dest)

verification, style_inventory = [], []
for g in inventory['guides']:
    doc = read(OUT / g['source'])
    for page in doc['pages']:
        footer = [e['text'] for e in page['elements'] if e['tag'] == 'text' and e['bounds']['y'] > 285
                  and e['text'].startswith('Inbit Snipe-IT |')]
        assert footer == [f'Inbit Snipe-IT | {g["code"]} v{g["version"]} | 04-11-2026'], (g['code'], 'version footer')
        for image in (e for e in page['elements'] if e['tag'] == 'image'):
            source, crop = assets[image['asset']], image['sourceCrop']
            assert crop['x'] >= -.01 and crop['y'] >= -.01, (g['code'], 'negative crop')
            assert crop['x'] + crop['w'] <= source['width'] + .01, (g['code'], 'crop width')
            assert crop['y'] + crop['h'] <= source['height'] + .01, (g['code'], 'crop height')
    idml = OUT / 'idml' / f'{g["code"]}-v{g["version"]}.idml'
    with zipfile.ZipFile(idml) as z:
        assert z.namelist()[0] == 'mimetype'
        assert z.getinfo('mimetype').compress_type == zipfile.ZIP_STORED
        assert z.read('mimetype') == b'application/vnd.adobe.indesign-idml-package'
        trees = {name: ET.fromstring(z.read(name)) for name in z.namelist() if name.endswith('.xml')}
        ids = {}
        for name, tree in trees.items():
            for node in tree.iter():
                if node.get('Self'):
                    identity = node.get('Self')
                    assert identity not in ids, (g['code'], identity, name)
                    ids[identity] = node
        refs = ('ParentStory', 'AppliedParagraphStyle', 'AppliedCharacterStyle', 'AppliedObjectStyle',
                'ItemLayer', 'AppliedMaster', 'FillColor', 'StrokeColor', 'Source')
        for name, tree in trees.items():
            for node in tree.iter():
                for ref in refs:
                    target = node.get(ref)
                    if target and target != 'n':
                        assert target in ids, (g['code'], name, ref, target)
        stories = [n for t in trees.values() for n in t.iter('Story')]
        texts = [n.text or '' for t in trees.values() for n in t.iter('Content')]
        assert not any('\ufffd' in text or '\u00c3\u00a9' in text for text in texts), g['code'] + ' encoding'
        links = [n for t in trees.values() for n in t.iter('Link')]
        for link in links:
            uri = link.get('LinkResourceURI')
            parsed = urllib.parse.urlparse(uri)
            assert parsed.scheme == 'file'
            local = Path(urllib.parse.unquote(parsed.path).lstrip('/'))
            assert local.exists(), (g['code'], local)
        page_count = len([name for name in trees if name.startswith('Spreads/')])
        assert page_count == g['pages'] == len(doc['pages'])
        expected = sum(e['tag'] == 'image' for p in doc['pages'] for e in p['elements'])
        assert len(links) == expected
        verification.append({'code': g['code'], 'pages': page_count, 'stories': len(stories),
                             'pictureFrames': len(links), 'xmlReferences': 'passed',
                             'nativeProof': 'pending', 'sha256': digest(idml)})
        style_inventory.append({'code': g['code'], 'font': 'Arial',
                                'styles': [n.attrib for t in trees.values() for n in t.iter('ParagraphStyle')],
                                'master': 'A-Basis', 'nativeTemplate': 'pending native proof'})

for name in ('prepare-final-set-2026-11-04.mjs', 'revise-final-set-2026-11-04.mjs',
             'review-final-set-2026-11-04.mjs', 'export-final-set-idml-2026-11-04.py',
             'package-final-set-preparation-2026-11-04.py', 'update-final-guide-specs-2026-11-04.mjs',
             'lib/final-guide-scenes-2026-11-04.mjs', 'lib/final-catalog-scenes-2026-11-04.mjs',
             'lib/final-guide-changes-2026-11-04.mjs', 'lib/guide-system.mjs', 'lib/guide-paths.mjs'):
    source = ROOT / 'scripts/manuals' / name
    dest = OUT / 'source-generator' / name
    dest.parent.mkdir(parents=True, exist_ok=True)
    shutil.copyfile(source, dest)
for g in inventory['guides']:
    dest = OUT / 'source/specifications' / (g['code'] + '.md')
    dest.parent.mkdir(parents=True, exist_ok=True)
    shutil.copyfile(ROOT / 'docs/manuals/operator-guides/guides' / (g['code'] + '.md'), dest)
(OUT / 'source/style-inventory.json').write_text(json.dumps(style_inventory, indent=2) + '\n', encoding='utf-8')
(OUT / 'source/template-specification.json').write_text(json.dumps({
    'page': {'widthMm': 210, 'heightMm': 297, 'marginMm': 12},
    'font': 'Arial', 'master': 'A-Basis: common title, family/version shapes and left footer',
    'paragraphRoles': ['Title', 'Purpose/version', 'Context', 'Step/section', 'Body', 'Caption', 'Reference', 'Footer'],
    'picture': 'Full original raster retained behind editable crop; annotations remain independent vectors.',
    'nativeTemplateStatus': 'Create and verify shared .af template after native proof; no native file yet.'
}, indent=2) + '\n', encoding='utf-8')

report = {'status': 'Prepared source; final native/PDF delivery incomplete', 'guides': verification,
          'totalPages': 68, 'originalAssets': len(assets),
          'nativeAfFiles': len(list((OUT / 'affinity').glob('*.af'))),
          'finalPdfFiles': len(list((OUT / 'pdf').glob('*.pdf'))),
          'checks': 'Original hashes, 68-page geometry/components, IDML ZIP/XML/object references, UTF-8 text, local image links and picture counts.',
          'notProven': ['Affinity import/edit/save/reopen/export', 'Native resource relocation',
                        'Production/configuration alignment', 'Physical and unfamiliar-reader trials']}
(OUT / 'qa/package-verification.json').write_text(json.dumps(report, indent=2) + '\n', encoding='utf-8')
names = [(g['code'], p) for g in inventory['guides'] for p in range(1, g['pages'] + 1)]
sheet_dir = OUT / 'qa/sheets'
sheet_dir.mkdir(exist_ok=True)
for offset in range(0, len(names), 8):
    sheet = Image.new('RGB', (1600, 1180), '#dfe5ec')
    draw = ImageDraw.Draw(sheet)
    for i, (code, page) in enumerate(names[offset:offset + 8]):
        x, y = (i % 4) * 400, (i // 4) * 590
        source = Image.open(OUT / 'previews' / f'{code}-{page}.png')
        sheet.paste(ImageOps.contain(source, (390, 555)), (x, y + 24))
        draw.text((x + 10, y + 5), f'{code} / {page}', fill='black')
    sheet.save(sheet_dir / f'{offset // 8 + 1}.png')
files = sorted(p for p in OUT.rglob('*') if p.is_file() and p.name != 'preparation.sha256')
(OUT / 'preparation.sha256').write_text(''.join(digest(p) + '  ' + p.relative_to(OUT).as_posix() + '\n' for p in files), encoding='utf-8')
archive = OUT.parent / 'final-guides-preparation-2026-11-04.zip'
with zipfile.ZipFile(archive, 'w', compression=zipfile.ZIP_DEFLATED, compresslevel=6) as z:
    for p in sorted(p for p in OUT.rglob('*') if p.is_file()):
        z.write(p, p.relative_to(OUT.parent).as_posix())
archive.with_suffix('.zip.sha256').write_text(digest(archive) + '  ' + archive.name + '\n', encoding='utf-8')
print(json.dumps({'guides': len(verification), 'pages': 68, 'assets': len(assets),
                  'nativeAfFiles': report['nativeAfFiles'], 'finalPdfFiles': report['finalPdfFiles'],
                  'archive': str(archive), 'bytes': archive.stat().st_size}))
