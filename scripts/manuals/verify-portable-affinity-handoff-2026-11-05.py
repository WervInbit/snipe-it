"""Independently check rebound resources/content without claiming native proof."""
from pathlib import Path
from urllib.parse import unquote, urlparse
import hashlib
import json
import sys
import xml.etree.ElementTree as ET
import zipfile

ROOT = Path(__file__).resolve().parents[2]
candidate = ROOT / 'output/manuals/affinity-idml-native-candidate-2026-11-05'
folder = Path(sys.argv[1]).resolve()
runs = sorted((folder / 'native-inputs').iterdir())
assert runs and all((run / 'READY.txt').exists() for run in runs)
results = []
for run in runs:
    manifest = json.loads((run / 'input-manifest.json').read_text(encoding='utf-8'))
    assert Path(manifest['root']).resolve() == folder
    assert Path(manifest['idmlRoot']).resolve() == run
    assert len(manifest['guides']) == 25 and sum(g['pages'] for g in manifest['guides']) == 68
    images, stories, master_pages = 0, 0, 0
    for guide in manifest['guides']:
        path = run / guide['idml']
        assert path.stat().st_size == guide['bytes']
        assert hashlib.sha256(path.read_bytes()).hexdigest() == guide['sha256']
        count = 0
        with zipfile.ZipFile(path) as current, zipfile.ZipFile(candidate / guide['idml']) as original:
            assert current.namelist() == original.namelist()
            assert current.testzip() is None
            assert current.infolist()[0].filename == 'mimetype' and current.infolist()[0].compress_type == 0
            for name in current.namelist():
                if not name.endswith('.xml'):
                    assert current.read(name) == original.read(name)
                    continue
                left, right = ET.fromstring(current.read(name)), ET.fromstring(original.read(name))
                current_links, original_links = list(left.iter('Link')), list(right.iter('Link'))
                assert len(current_links) == len(original_links)
                for link, old in zip(current_links, original_links):
                    uri = urlparse(link.get('LinkResourceURI'))
                    assert uri.scheme == 'file'
                    disk_path = unquote(uri.path).removeprefix('/')
                    image = Path(disk_path)
                    assert image.exists() and image.parent.resolve() == (folder / 'assets').resolve(), str(image)
                    expected = next(a for a in manifest['assets'] if Path(a['file']).name == image.name)
                    assert hashlib.sha256(image.read_bytes()).hexdigest() == expected['sha256']
                    link.set('LinkResourceURI', old.get('LinkResourceURI'))
                    count += 1
                assert ET.tostring(left) == ET.tostring(right), (guide['code'], name)
                if name.startswith('Stories/'):
                    stories += 1
                if name.startswith('MasterSpreads/'):
                    master_pages += sum(1 for _ in left.iter('Page'))
        assert count == guide['pictures']
        images += count
    generated = (run / 'convert-all-25-guides.js').read_text(encoding='utf-8')
    literal = generated.split('/* INPUTS_START */', 1)[1].split('/* INPUTS_END */', 1)[0].strip()
    assert literal.startswith('const INPUTS = ') and literal.endswith(';')
    assert json.loads(literal[len('const INPUTS = '):-1]) == manifest
    results.append({'run': run.name, 'guides': 25, 'pages': 68, 'imagePlacements': images,
                    'storyPartsPreserved': stories, 'masterPagesPreserved': master_pages})
for line in (folder / 'package-files.sha256').read_text(encoding='utf-8').splitlines():
    digest, filename = line.split('  ', 1)
    assert hashlib.sha256((folder / filename).read_bytes()).hexdigest() == digest
report = {'status': 'passed', 'runs': results, 'sourcePackageUnchanged': True,
          'nativeAffinityExecuted': False, 'desktopControlled': False,
          'scope': 'File relocation and exact XML/resource preservation; native relocation/editability still requires Affinity.'}
destination = folder.parent.parent / 'relocation-check.json'
destination.relative_to(ROOT / 'output/manuals')
destination.write_text(json.dumps(report, indent=2) + '\n', encoding='utf-8')
print(json.dumps(report))
