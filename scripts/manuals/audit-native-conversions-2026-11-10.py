"""Check native conversion evidence and render each newly exported guide PDF."""
from collections import Counter
import argparse
import hashlib
import json
from pathlib import Path
import re
import shutil
import subprocess
import unicodedata
import xml.etree.ElementTree as ET
import zipfile

from pypdf import PdfReader
import pypdfium2 as pdfium

ROOT = Path(__file__).resolve().parents[2]
parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('--session', type=Path, default=ROOT / 'output/manuals/affinity-native-2026-11-10')
parser.add_argument('--poppler', type=Path, help='Exact pdftoppm executable from the current bundled runtime.')
args = parser.parse_args()
SESSION = args.session.resolve()
QA = SESSION / 'qa'
poppler_command = args.poppler or shutil.which('pdftoppm')
if not poppler_command or not Path(poppler_command).is_file():
    parser.error('Supply --poppler with the current runtime pdftoppm path.')
POPLER = Path(poppler_command)
QA.mkdir(exist_ok=True)


def digest(path):
    return hashlib.sha256(path.read_bytes()).hexdigest()


def normalize(text):
    return re.sub(r'\s+', '', unicodedata.normalize('NFKC', text)).replace('\u00ad', '')


results = []
for marker in sorted((SESSION / 'converted').glob('*.json')):
    record = json.loads(marker.read_text(encoding='utf-8'))
    report = json.loads(Path(record['report']).read_text(encoding='utf-8'))
    guide = next(g for g in report['guides'] if g['code'] == record['code'])
    code = record['code']
    idml = Path(record['inputIdml']) if record.get('inputIdml') else SESSION / 'idml-inputs/idml' / f"{code}-v{record['version']}.idml"
    pdf = Path(record['pdf'])
    native = Path(record['native'])
    source_parts = []
    with zipfile.ZipFile(idml) as archive:
        for name in archive.namelist():
            if name.startswith('Stories/') and name.endswith('.xml'):
                source_parts.extend(n.text or '' for n in ET.fromstring(archive.read(name)).iter('Content'))
    reader = PdfReader(pdf)
    pdf_text = normalize('\n'.join(p.extract_text() for p in reader.pages))
    missing = [s for s in source_parts if len(normalize(s)) >= 5 and normalize(s) not in pdf_text]
    image_files = list((Path(record['package']).parent / 'Images').glob('*')) if record.get('package') else []
    item = {'code': code, 'version': record['version'], 'pages': len(reader.pages),
            'native': str(native), 'pdf': str(pdf), 'native_sha256': digest(native), 'pdf_sha256': digest(pdf),
            'input_hash_matches': digest(idml) == record['inputSha256'],
            'native_import_reopen_counts_match': all(guide['import'][n] == guide['reopened'][n]
                for n in ('pages', 'liveTextFrames', 'imageNodes', 'groups')),
            'native_counts': {n: guide['reopened'][n] for n in ('pages', 'liveTextFrames', 'imageNodes', 'groups')},
            'installed_fonts': guide['reopened']['fonts'], 'conversion_warnings': guide['warnings'],
            'optional_pdf_layers': '/OCProperties' in reader.trailer['/Root'],
            'page_dimensions_mm': [[float(p.mediabox.width)*25.4/72, float(p.mediabox.height)*25.4/72] for p in reader.pages],
            'a4_tolerance_mm': .8,
            'a4_pages': all(abs(float(p.mediabox.width)*25.4/72 - 210) < .8 and abs(float(p.mediabox.height)*25.4/72 - 297) < .8 for p in reader.pages),
            'source_text_parts': len(source_parts), 'source_text_parts_not_found': missing,
            'packaged_images': len(image_files), 'package_has_font_folder': (Path(record['package']).parent / 'Fonts').exists() if record.get('package') else False}
    render = QA / 'renders' / code
    render.mkdir(parents=True, exist_ok=True)
    identity = render / 'pdf.sha256'
    if not identity.exists() or identity.read_text().strip() != item['pdf_sha256']:
        subprocess.run([str(POPLER), '-r', '150', '-png', str(pdf), str(render / 'poppler')], check=True, capture_output=True)
        document = pdfium.PdfDocument(str(pdf))
        try:
            for number in range(len(document)):
                document[number].render(scale=150 / 72).to_pil().save(render / f'pdfium-{number + 1}.png')
        finally:
            document.close()
        identity.write_text(item['pdf_sha256'] + '\n', encoding='ascii')
    item['rendered_with'] = ['Poppler 150dpi', 'PDFium 150dpi']
    results.append(item)

audit = {'converted_guides': len(results), 'pages': sum(g['pages'] for g in results),
         'results': results, 'visual_review': 'Required; generated renders do not establish usability or owner acceptance.'}
(QA / 'native-artifact-audit.json').write_text(json.dumps(audit, indent=2, ensure_ascii=False) + '\n', encoding='utf-8')
print(json.dumps({'guides': len(results), 'pages': audit['pages'],
    'checks_failed': [g['code'] for g in results if not g['input_hash_matches'] or not g['native_import_reopen_counts_match'] or not g['a4_pages'] or g['optional_pdf_layers']],
    'text_parts_to_review': {g['code']: len(g['source_text_parts_not_found']) for g in results if g['source_text_parts_not_found']},
    'conversion_warnings': {g['code']: g['conversion_warnings'] for g in results if g['conversion_warnings']}}))
