"""Correct the USR-04 image-state caption without changing its screenshot/layout."""
import hashlib
import json
from pathlib import Path
import xml.etree.ElementTree as ET
import zipfile

ROOT = Path(__file__).resolve().parents[2]
SESSION = ROOT / 'output/manuals/affinity-native-2026-11-10'
SOURCE = SESSION / 'idml-inputs/idml/USR-04-v8.idml'
TARGET = SESSION / 'idml-inputs/idml/USR-04-v9-caption.idml'
ET.register_namespace('idPkg', 'http://ns.adobe.com/AdobeInDesign/idml/1.0/packaging')


def main():
    if TARGET.exists():
        raise RuntimeError('Existing candidate preserved')
    with zipfile.ZipFile(SOURCE) as archive:
        parts = {n: archive.read(n) for n in archive.namelist()}
    changed = []
    count = 0
    for name in list(parts):
        if not name.startswith('Stories/') or not name.endswith('.xml'):
            continue
        root = ET.fromstring(parts[name])
        dirty = False
        for n in root.iter('Content'):
            old = n.text or ''
            new = old.replace('Het complete loginvakje; hier nog aangevinkt.', 'Het loginvakje staat uit; sla deze stand op.')
            count += old != new
            new = new.replace('Versie 8', 'Versie 9').replace('USR-04 v8', 'USR-04 v9').replace('04-11-2026', '12-11-2026')
            if new != old:
                n.text = new
                dirty = True
        if dirty:
            parts[name] = ET.tostring(root, encoding='utf-8', xml_declaration=True)
            changed.append(name)
    assert count == 1
    with zipfile.ZipFile(TARGET, 'w', zipfile.ZIP_DEFLATED) as archive:
        archive.writestr('mimetype', parts.pop('mimetype'), compress_type=zipfile.ZIP_STORED)
        for name, data in parts.items():
            archive.writestr(name, data)
    guide = {'code': 'USR-04', 'version': 9, 'pages': 1, 'idml': 'idml/' + TARGET.name,
             'bytes': TARGET.stat().st_size, 'sha256': hashlib.sha256(TARGET.read_bytes()).hexdigest(),
             'bodyTextFrames': 33, 'pictures': 5}
    (SESSION / 'runner/usr04-caption.json').write_text(json.dumps({'guide': guide, 'changed_parts': changed,
        'preserved': 'All screenshot pixels/crops, frame geometry, styles and other instructions'}, indent=2) + '\n')
    print(json.dumps(guide))


if __name__ == '__main__':
    main()
