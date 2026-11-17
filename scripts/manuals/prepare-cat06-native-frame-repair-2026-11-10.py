"""Give the existing CAT-06 source address room without shrinking type or changing words."""
from pathlib import Path
import hashlib
import json
import xml.etree.ElementTree as ET
import zipfile

ROOT = Path(__file__).resolve().parents[2]
SESSION = ROOT / 'output/manuals/affinity-native-2026-11-10'
source = SESSION / 'idml-inputs/idml/CAT-06-v4.idml'
target = source.with_name('CAT-06-v4-url-frame.idml')
assert not target.exists(), 'Existing repair input preserved.'
ET.register_namespace('idPkg', 'http://ns.adobe.com/AdobeInDesign/idml/1.0/packaging')
with zipfile.ZipFile(source) as old:
    part = 'Spreads/Spread_2.xml'
    tree = ET.fromstring(old.read(part))
    frame = next(n for n in tree.iter('TextFrame') if n.get('Self') == 'p2_item-25')
    assert frame.get('ParentStory') == 'story_p2_item-25'
    points = list(frame.iter('PathPointType'))
    before = [dict(p.attrib) for p in points]
    width, height = 182 * 72 / 25.4, 28.0
    for p, (x, y) in zip(points, ((0, 0), (0, height), (width, height), (width, 0)), strict=True):
        value = f'{x:.6f} {y:.6f}'
        for key in ('Anchor', 'LeftDirection', 'RightDirection'):
            p.set(key, value)
    data = b'<?xml version="1.0" encoding="UTF-8" standalone="yes"?>\n' + ET.tostring(tree, encoding='utf-8')
    with zipfile.ZipFile(target, 'w') as new:
        for entry in old.infolist():
            new.writestr(entry, data if entry.filename == part else old.read(entry.filename))
with zipfile.ZipFile(source) as old, zipfile.ZipFile(target) as new:
    changed = [n for n in old.namelist() if old.read(n) != new.read(n)]
    assert changed == [part]
manifest = json.loads((SESSION / 'runner/input-manifest.json').read_text(encoding='utf-8'))
guide = next(g for g in manifest['guides'] if g['code'] == 'CAT-06').copy()
original_hash = guide['sha256']
guide.update(idml='idml/' + target.name, bytes=target.stat().st_size,
             sha256=hashlib.sha256(target.read_bytes()).hexdigest())
report = {'guide': guide, 'original_input_sha256': original_hash,
          'reason': 'The native URL frame clips its final line; use the available page width and a taller frame.',
          'changed_parts': changed, 'unchanged': ['All story text', 'Font sizes', 'Images and crops', 'Other frame geometry'],
          'before': before, 'after': [dict(p.attrib) for p in points], 'native_verification': 'Pending'}
(SESSION / 'runner/cat06-frame-repair.json').write_text(json.dumps(report, indent=2) + '\n', encoding='utf-8')
print(json.dumps({'input': str(target), 'changed_parts': changed, 'sha256': guide['sha256']}))
