"""Check a separate compatibility proposal and package it for the paired NUC."""
from pathlib import Path
import argparse
import hashlib
import json
import xml.etree.ElementTree as ET
import zipfile

ROOT = Path(__file__).resolve().parents[2]
SOURCE = ROOT / 'output/manuals/final-guide-set-2026-11-04'
OUT = ROOT / 'output/manuals/affinity-idml-compatibility-2026-11-05'
parser = argparse.ArgumentParser()
parser.add_argument('--directory', type=Path)
args = parser.parse_args()
if args.directory:
    OUT = args.directory.resolve()
    OUT.relative_to(ROOT / 'output/manuals')
checks = []
for line in (SOURCE / 'preparation.sha256').read_text(encoding='utf-8').splitlines():
    digest, filename = line.split('  ', 1)
    assert hashlib.sha256((SOURCE / filename).read_bytes()).hexdigest() == digest, filename
    checks.append(filename)
reports = []
for path in sorted((OUT / 'idml').glob('*.idml')):
    with zipfile.ZipFile(path) as revised, zipfile.ZipFile(SOURCE / 'idml' / path.name) as original:
        assert revised.testzip() is None
        before = {name: ET.fromstring(original.read(name)) for name in original.namelist() if name.endswith('.xml')}
        after = {name: ET.fromstring(revised.read(name)) for name in revised.namelist() if name.endswith('.xml')}
        stories = [name for name in before if name.startswith('Stories/')]
        for name in stories:
            assert [e.text for e in before[name].iter('Content')] == [e.text for e in after[name].iter('Content')], name
        images = 0
        for name in before:
            if name.startswith('Spreads/'):
                for tag in ['Image', 'Link']:
                    assert [ET.tostring(e) for e in before[name].iter(tag)] == [ET.tostring(e) for e in after[name].iter(tag)], (path.name, name, tag)
                images += sum(1 for _ in after[name].iter('Image'))
        master = after['MasterSpreads/MasterSpread_master.xml']
        canvas = next(master.iter('Rectangle'))
        assert canvas.get('FillColor') == 'Color/RGB_255_255_255'
        reports.append({'file': path.name, 'textStoriesPreserved': len(stories), 'imagePlacementsPreserved': images})
assert len(reports) == 25
report = {'originalPreparedFilesVerified': len(checks), 'guides': reports,
          'nativeAcceptance': 'Pending corrected AST-03 import and complete native QA',
          'changes': ['Use captured computed SVG fill/stroke colors; reject unsupported colors.',
                      'Put common white page background on the master behind its header/footer.',
                      'See writer options and native review for typography/focus/baseline probe status.'],
          'unchanged': ['Original prepared source and IDML', 'Operator copy', 'Image placement/crop/links', 'Page dimensions']}
(OUT / 'qa/compatibility-check.json').write_text(json.dumps(report, indent=2) + '\n', encoding='utf-8')
(OUT / 'README.txt').write_text('Affinity compatibility proposal - 5 November 2026\n'
    'Contains 25 corrected IDMLs. Native import/edit/export validation is pending.\n'
    'Open AST-03-v20.idml first. This is not an accepted final release.\n'
    'AST-03-only native proof does not establish other guides or full editability.\n'
    'Inspect PDF exports with Poppler and verify optional-layer compatibility.\n'
    'Image links intentionally use the original C:/dev/snipe-it-fork/output/manuals/final-guide-set-2026-11-04/assets.\n'
    'Do not run the original conversion script against this folder without adapting its input manifest.\n', encoding='utf-8')
archive = OUT.with_suffix('.zip')
assert not archive.exists(), 'Refusing archive overwrite'
with zipfile.ZipFile(archive, 'x', compression=zipfile.ZIP_DEFLATED) as z:
    for file in sorted(OUT.rglob('*')):
        if file.is_file():
            z.write(file, file.relative_to(OUT.parent).as_posix())
print(json.dumps({'path': str(archive), 'bytes': archive.stat().st_size,
                  'sha256': hashlib.sha256(archive.read_bytes()).hexdigest(),
                  'guides': len(reports), 'originalFilesVerified': len(checks)}))
