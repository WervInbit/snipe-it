"""Make matching previous/current scrolling PDFs from unchanged guide pages."""

from concurrent.futures import ThreadPoolExecutor
import hashlib
import json
from pathlib import Path
import shutil
import subprocess

from PIL import Image
from pypdf import PdfReader, PdfWriter

ROOT = Path(__file__).resolve().parents[2]
ROUND = ROOT / 'resources/manuals/operator-guides/review-rounds/2026-09-10'
CHECKPOINT = ROOT / 'resources/manuals/operator-guides/checkpoints/2026-09-08-before-audit-revisions'
PROOF = ROOT / 'output/manuals/proofs/reviewbundel-v2'


def sha(path):
    return hashlib.sha256(path.read_bytes()).hexdigest()


def render(path, prefix):
    subprocess.run(['pdftoppm', '-r', '96', '-png', str(path), str(prefix)],
                   check=True, capture_output=True)


def pixels(path):
    with Image.open(path) as image:
        rgb = image.convert('RGB')
        return rgb.size, hashlib.sha256(rgb.tobytes()).hexdigest()


def main():
    current = json.loads((ROUND / 'current-guide-selection-v1.json').read_text())['guides']
    baseline = json.loads((CHECKPOINT / 'manifest.json').read_text())['artifacts']
    followups = json.loads((ROOT / 'resources/manuals/operator-guides/review-rounds/2026-09-08/guide-set-followups-manifest.json').read_text())
    old_versions = {e['code']: e['baselineVersion'] for e in followups['artifacts']}
    old_versions.update({'WF-01': 10, 'CMP-04': 6})
    previous = []
    for entry in current:
        matches = [e for e in baseline if e['code'] == entry['code'] and e['version'] == old_versions[entry['code']]]
        assert len(matches) == 1, entry['code']
        old = matches[0]
        previous.append({**old, 'path': (CHECKPOINT / old['checkpointPath']).relative_to(ROOT).as_posix()})
    assert len(current) == len(previous) == 22
    assert sum(e['pages'] for e in current) == sum(e['pages'] for e in previous) == 48
    PROOF.mkdir(parents=True, exist_ok=True)
    report = {'createdOn': '2026-09-10', 'bundleVersion': 2,
              'purpose': 'Plain previous/current PDFs requested by owner; no added contents, dividers or bookmarks.',
              'previousSelection': 'Frozen selected pre-audit baseline, including WF-01 v10; rejected pilots excluded.',
              'currentSelection': 'Current 2026-09-10 selection; exact acceptances unchanged.',
              'artifacts': []}
    for label, entries in [('vorig', previous), ('huidig', current)]:
        filename = f'Handleidingen-reviewbundel-v2-{label}.pdf'
        output = ROOT / 'output/pdf' / filename
        retained = ROUND / filename
        assert not output.exists() and not retained.exists(), 'Keep existing bundle versions immutable.'
        writer = PdfWriter()
        readers = []
        mapping = []
        for entry in entries:
            source = ROOT / entry['path']
            assert sha(source) == entry['sha256'], entry['code']
            reader = PdfReader(source)
            assert len(reader.pages) == entry['pages']
            mapping.append({'code': entry['code'], 'version': entry['version'],
                            'sourcePath': entry['path'], 'sourceSha256': entry['sha256'],
                            'startPage': len(writer.pages) + 1, 'pages': entry['pages']})
            readers.append(reader)
            writer.append(reader, import_outline=False)
        writer.page_mode = '/UseNone'
        with output.open('xb') as stream:
            writer.write(stream)
        merged = PdfReader(output)
        assert len(merged.pages) == 48 and not merged.outline
        cursor = 0
        for reader in readers:
            for page in reader.pages:
                target = merged.pages[cursor]
                assert target.get_contents().get_data() == page.get_contents().get_data()
                assert list(target.mediabox) == list(page.mediabox)
                assert list(target.cropbox) == list(page.cropbox)
                assert len(target.get('/Annots', [])) == len(page.get('/Annots', []))
                cursor += 1
        print(f'{label}: 22 guides, 48 unchanged pages assembled; checking rendered pages.', flush=True)
        render(output, PROOF / label)
        source_prefixes = {}
        render_jobs = []
        for entry in entries:
            prefix = PROOF / f"{label}-{entry['code']}"
            source_prefixes[entry['code']] = prefix
            render_jobs.append((ROOT / entry['path'], prefix))
        with ThreadPoolExecutor(max_workers=4) as pool:
            list(pool.map(lambda job: render(*job), render_jobs))
        for entry, mapped in zip(entries, mapping):
            prefix = source_prefixes[entry['code']]
            for page in range(entry['pages']):
                assert pixels(Path(f'{prefix}-{page + 1}.png')) == pixels(PROOF / f"{label}-{mapped['startPage'] + page:02d}.png"), (label, entry['code'], page)
            assert sha(ROOT / entry['path']) == entry['sha256']
        with retained.open('xb') as stream:
            stream.write(output.read_bytes())
        report['artifacts'].append({'file': filename, 'sha256': sha(output),
                                    'pages': 48, 'guides': 22, 'addedPages': 0, 'bookmarks': 0,
                                    'contentStreamsAndPageBoxesPreserved': True,
                                    'sourcePixelsIdenticalAt96dpi': 48, 'pageMap': mapping})
    assert [e['startPage'] for e in report['artifacts'][0]['pageMap']] == [e['startPage'] for e in report['artifacts'][1]['pageMap']]
    with (ROUND / 'Handleidingen-reviewbundel-v2-manifest.json').open('x', encoding='utf-8') as stream:
        stream.write(json.dumps(report, indent=2) + '\n')
    shutil.copyfile(Path(__file__), ROUND / 'Handleidingen-reviewbundel-v2-builder.py')
    print(json.dumps({'status': 'passed', 'artifacts': [e['file'] for e in report['artifacts']],
                      'identicalPageOrderAndBoundaries': True, 'pixelIdenticalPages': 96}, indent=2))


if __name__ == '__main__':
    main()
