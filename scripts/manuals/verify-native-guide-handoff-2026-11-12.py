"""Verify assembled guide content/resources and seal an already verified handoff."""
import argparse
import hashlib
import importlib.util
import json
from pathlib import Path
import sys
import zipfile

from PIL import Image, ImageChops
import pypdfium2 as pdfium

ROOT = Path(__file__).resolve().parents[2]
SESSION = ROOT / 'output/manuals/affinity-native-2026-11-10'
OUT = ROOT / 'output/manuals/affinity-guide-handoff-2026-11-12'
sys.dont_write_bytecode = True


def digest(path):
    return hashlib.sha256(path.read_bytes()).hexdigest()


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--zip', action='store_true', help='Seal after successful verification; do not repeat native/content tests.')
    args = parser.parse_args()
    manifest = json.loads((OUT / 'manifest.json').read_text())
    verification = OUT / 'Evidence/handoff-verification.json'
    for g in manifest['guides']:
        assert digest(OUT / g['native']) == g['native_sha256'], f"Native master changed: {g['code']}"
        assert digest(OUT / g['pdf']) == g['pdf_sha256'], f"PDF changed: {g['code']}"
    if args.zip:
        checked = json.loads(verification.read_text())
        assert checked['passed'] and checked['guides'] == 25 and checked['pages'] == 68
        target = OUT.with_suffix('.zip')
        assert not target.exists(), 'Existing ZIP preserved'
        files = sorted(p for p in OUT.rglob('*') if p.is_file() and p.name != 'handoff.sha256')
        (OUT / 'handoff.sha256').write_text(''.join(f'{digest(p)}  {p.relative_to(OUT).as_posix()}\n' for p in files), encoding='utf-8')
        with zipfile.ZipFile(target, 'w', zipfile.ZIP_DEFLATED, compresslevel=6) as archive:
            for path in sorted(p for p in OUT.rglob('*') if p.is_file()):
                archive.write(path, OUT.name + '/' + path.relative_to(OUT).as_posix())
        with zipfile.ZipFile(target) as archive:
            assert archive.testzip() is None
        result = {'zip': str(target), 'bytes': target.stat().st_size, 'sha256': digest(target), 'files': len(files) + 1}
        target.with_suffix('.zip.sha256').write_text(result['sha256'] + '  ' + target.name + '\n')
        (SESSION / 'qa/final-handoff-archive.json').write_text(json.dumps(result, indent=2) + '\n')
        print(json.dumps(result))
        return
    assert not verification.exists(), 'Existing verification preserved'
    spec = importlib.util.spec_from_file_location('guide_pair', ROOT / 'docs/manuals/operator-guides/skill/scripts/verify-guide-pair.py')
    module = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(module)
    checks_dir = OUT / 'Evidence/guide-pairs'
    checks_dir.mkdir()
    fragments = 0
    image_files = 0
    for guide in manifest['guides']:
        check = module.inspect_pair(OUT / guide['idml'], OUT / guide['pdf'])
        assert check['passed'], guide['code'] + ': ' + json.dumps(check['checks'])
        (checks_dir / (guide['code'] + '.json')).write_text(json.dumps(check, indent=2) + '\n')
        fragments += check['source_fragments']
        for image in guide['images']:
            assert digest(OUT / image['file']) == image['sha256']
            image_files += 1
    assert not list((OUT / 'Packages').rglob('Fonts'))
    assert not any(p.suffix.lower() in ['.ttf', '.otf', '.woff', '.woff2'] for p in OUT.rglob('*') if p.is_file())
    frozen_count = 0
    frozen = OUT / 'Source-preparation-2026-11-04'
    for line in (frozen / 'preparation.sha256').read_text().splitlines():
        expected, relative = line.split('  ', 1)
        assert digest(frozen / relative) == expected, 'Frozen preparation changed: ' + relative
        frozen_count += 1
    relocated = []
    for code in ['AST-03', 'CAT-00', 'CAT-04', 'CAT-05']:
        proof = json.loads((SESSION / f'proofs/handoff-relocation/{code}.json').read_text())
        guide = next(g for g in manifest['guides'] if g['code'] == code)
        assert proof['version'] == guide['version'] and not proof['errors']
        assert proof['reopened'] == guide['native_counts']
        for item in proof['extracted']:
            assert digest(Path(item['path'])) == item['expectedSha256'], 'Relocated image bytes differ'
        text_check = module.inspect_pair(OUT / guide['idml'], Path(proof['pdf']))
        assert text_check['passed'], 'Relocated PDF text/page check failed: ' + code
        document = pdfium.PdfDocument(proof['pdf'])
        identical_pages = []
        try:
            for number in range(len(document)):
                page = document[number]
                try:
                    actual = page.render(scale=150 / 72).to_pil().convert('RGB')
                finally:
                    page.close()
                expected = Image.open(SESSION / f'qa/renders/{code}/pdfium-{number + 1}.png').convert('RGB')
                assert actual.size == expected.size and ImageChops.difference(actual, expected).getbbox() is None, f'Relocated rendered page differs: {code}/{number + 1}'
                identical_pages.append(number + 1)
        finally:
            document.close()
        summary = {'code': code, 'version': guide['version'], 'pages_pixel_identical': identical_pages,
                   'unique_full_images_verified': len(proof['extracted']), 'reopened_counts': proof['reopened'],
                   'limits': proof['limits']}
        relocated.append(summary)
        (OUT / f'Evidence/relocation-{code}.json').write_text(json.dumps(summary, indent=2) + '\n')
    result = {'passed': True, 'guides': len(manifest['guides']), 'pages': manifest['pages'],
              'source_fragments_found': fragments, 'packaged_image_files_verified': image_files,
              'frozen_files_verified': frozen_count, 'copied_font_files': 0,
              'relocated_representatives': relocated, 'limits': 'Same-machine filtered-package proof. Original source locations remain available. Operator, paper, fresh-device and exact acceptance checks remain separate.'}
    verification.write_text(json.dumps(result, indent=2) + '\n')
    manifest['relocation_verification'] = 'Four representative filtered packages reopen/save native/export with full-image hashes and23 pixel-identical pages; fresh-device trial remains open'
    (OUT / 'manifest.json').write_text(json.dumps(manifest, indent=2) + '\n')
    print(json.dumps({k: v for k, v in result.items() if k != 'relocated_representatives'}))


if __name__ == '__main__':
    main()
