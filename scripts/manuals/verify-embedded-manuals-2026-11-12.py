"""Compare native embedded copies with their selected source PDFs and full images."""
import argparse
import hashlib
import json
from pathlib import Path
import unicodedata

from PIL import Image, ImageChops
from pypdf import PdfReader
import pypdfium2 as pdfium


def digest(path):
    return hashlib.file_digest(path.open('rb'), 'sha256').hexdigest()


def normalize(text):
    return ' '.join(unicodedata.normalize('NFKC', text or '').replace('\u00ad', '').split())


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--source', required=True, type=Path)
    parser.add_argument('--work', required=True, type=Path)
    parser.add_argument('--report', required=True, type=Path)
    args = parser.parse_args()
    assert not args.report.exists(), 'Existing comparison evidence is preserved'
    manifest = json.loads((args.source / 'manifest.json').read_text(encoding='utf-8'))
    results = []
    for manual in manifest['guides']:
        code = manual['code']
        record = json.loads((args.work / 'reports' / f'{code}.json').read_text(encoding='utf-8'))
        assert record['passed'] and not record['errors'], code
        assert record['version'] == manual['version']
        assert record['inputSha256'] == manual['native_sha256']
        assert digest(args.source / manual['native']) == manual['native_sha256']
        assert digest(args.source / manual['pdf']) == manual['pdf_sha256']
        assert record['reopened'] == manual['native_counts']
        for image in record['images']:
            source = args.source / image['source']
            assert digest(source) == image['sourceSha256'], source
            with Image.open(source) as before, Image.open(image['extracted']) as after:
                before, after = before.convert('RGBA'), after.convert('RGBA')
                assert before.size == after.size == (image['width'], image['height'])
                assert before.tobytes() == after.tobytes(), f'{code}: full image pixels differ'
        old_path, new_path = args.source / manual['pdf'], Path(record['pdf'])
        old_reader, new_reader = PdfReader(old_path), PdfReader(new_path)
        assert len(old_reader.pages) == len(new_reader.pages) == manual['pages']
        assert new_reader.trailer['/Root'].get('/OCProperties') is None
        for old_page, new_page in zip(old_reader.pages, new_reader.pages):
            assert normalize(old_page.extract_text()) == normalize(new_page.extract_text()), code
            assert all(abs(float(a) - float(b)) < 0.01 for a, b in zip(old_page.mediabox, new_page.mediabox))
        before_doc, after_doc = pdfium.PdfDocument(old_path), pdfium.PdfDocument(new_path)
        page_results = []
        try:
            for number in range(manual['pages']):
                old_page, new_page = before_doc[number], after_doc[number]
                a_bitmap, b_bitmap = old_page.render(scale=150/72), new_page.render(scale=150/72)
                a, b = a_bitmap.to_pil().convert('RGB'), b_bitmap.to_pil().convert('RGB')
                equal = a.size == b.size and a.tobytes() == b.tobytes()
                difference = None if equal else ImageChops.difference(a,b)
                page_results.append({'page': number+1, 'equal':equal,
                                     'pixel_sha256': hashlib.sha256(a.tobytes()).hexdigest(),
                                     'new_pixel_sha256': hashlib.sha256(b.tobytes()).hexdigest(),
                                     'changed_bounds':None if equal else difference.getbbox(),
                                     'max_channel_change':0 if equal else max(hi for lo,hi in difference.getextrema())})
                a.close(); b.close(); a_bitmap.close(); b_bitmap.close(); old_page.close(); new_page.close()
        finally:
            before_doc.close(); after_doc.close()
        results.append({'code': code, 'version': manual['version'], 'pages': manual['pages'],
                        'native': record['native'], 'pdf': record['pdf'],
                        'native_sha256': digest(Path(record['native'])), 'pdf_sha256': digest(new_path),
                        'embedded_images': len(record['images']), 'full_image_pixels_equal': True,
                        'source_text_equal': True, 'rendered_pages': page_results})
        different = [p for p in page_results if not p['equal']]
        print(f'{code}: images/text match; {manual["pages"]-len(different)}/{manual["pages"]} rendered pages exact'+(' '+json.dumps(different) if different else ''), flush=True)
    report = {'passed': all(p['equal'] for r in results for p in r['rendered_pages']), 'manuals': len(results), 'pages': sum(x['pages'] for x in results),
              'embedded_image_placements': sum(x['embedded_images'] for x in results),
              'render_dpi': 150, 'results': results,
              'limits': 'Exact sampled-resolution PDF renders and full image pixels; fresh-device/fonts and operator/physical acceptance remain separate.'}
    args.report.parent.mkdir(parents=True, exist_ok=True)
    args.report.write_text(json.dumps(report, indent=2) + '\n', encoding='utf-8')
    print(json.dumps({k: report[k] for k in ['passed', 'manuals', 'pages', 'embedded_image_placements']}))
    raise SystemExit(0 if report['passed'] else 1)


if __name__ == '__main__':
    main()
