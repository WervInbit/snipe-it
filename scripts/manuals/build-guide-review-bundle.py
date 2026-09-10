"""Combine a frozen guide selection without rendering or changing guide pages."""

import argparse
from concurrent.futures import ThreadPoolExecutor
import hashlib
from io import BytesIO
import json
from pathlib import Path
import shutil
import subprocess

from PIL import Image
from pypdf import PdfReader, PdfWriter
from pypdf.annotations import Link
from pypdf.generic import Fit
from reportlab.lib.colors import HexColor
from reportlab.lib.pagesizes import A4
from reportlab.pdfgen import canvas

ROOT = Path(__file__).resolve().parents[2]


def sha(path):
    return hashlib.sha256(path.read_bytes()).hexdigest()


def save_once(path, content):
    path.parent.mkdir(parents=True, exist_ok=True)
    with path.open('xb') as stream:
        stream.write(content)


def contents(selection):
    stream = BytesIO()
    pdf = canvas.Canvas(stream, pagesize=A4, invariant=1)
    width, height = A4
    ink, muted, blue = '#102033', '#53657A', '#2563EB'
    pdf.setFillColor(HexColor(blue))
    pdf.rect(36, height - 86, 5, 45, fill=1, stroke=0)
    pdf.setFillColor(HexColor(ink))
    pdf.setFont('Helvetica-Bold', 24)
    pdf.drawString(51, height - 60, f"Handleidingen - reviewbundel v{selection['bundleVersion']}")
    pdf.setFont('Helvetica', 10)
    pdf.setFillColor(HexColor(muted))
    pdf.drawString(51, height - 79, '22 handleidingen | 48 inhoudspagina\'s | 10 september 2026')
    pdf.setFillColor(HexColor('#EFF6FF'))
    pdf.roundRect(36, height - 144, width - 72, 43, 5, fill=1, stroke=0)
    pdf.setFillColor(HexColor(ink))
    pdf.setFont('Helvetica-Bold', 10)
    pdf.drawString(47, height - 118, 'Huidige reviewronde vanaf 8 september 2026')
    pdf.setFont('Helvetica', 9)
    pdf.drawString(47, height - 133, 'Klik op een regel of gebruik de bladwijzers. Alle originele gidsbladen zijn behouden.')
    pdf.setFont('Helvetica-Bold', 8)
    pdf.setFillColor(HexColor(muted))
    y = height - 168
    for x, label in [(42, 'CODE'), (90, 'HANDLEIDING'), (371, 'VERSIE'), (413, 'REVIEWSTATUS')]:
        pdf.drawString(x, y, label)
    pdf.drawRightString(width - 42, y, 'PAGINA')
    y -= 12
    groups = {
        'AC': 'Toegang', 'SC': 'Zoeken en scannen', 'AST': 'Assets',
        'WF': 'Workflows', 'CMP': 'Componenten', 'HELP': 'Hulp',
        'USR': 'Gebruikersbeheer', 'CAT': 'Catalogus',
    }
    previous = None
    links = []
    for entry in selection['guides']:
        family = entry['code'].split('-')[0]
        if family != previous:
            y -= 16
            pdf.setFillColor(HexColor('#F1F5F9'))
            pdf.rect(36, y - 3, width - 72, 14, fill=1, stroke=0)
            pdf.setFillColor(HexColor(ink))
            pdf.setFont('Helvetica-Bold', 8)
            pdf.drawString(42, y, groups[family])
            previous = family
        y -= 18
        pdf.setFillColor(HexColor(ink))
        pdf.setFont('Helvetica-Bold', 8.5)
        pdf.drawString(42, y, entry['code'])
        pdf.setFont('Helvetica', 8.5)
        assert pdf.stringWidth(entry['title'], 'Helvetica', 8.5) < 275
        pdf.drawString(90, y, entry['title'])
        pdf.drawString(376, y, f"v{entry['version']}")
        pdf.setFillColor(HexColor('#138A43' if entry['ownerAccepted'] else muted))
        pdf.setFont('Helvetica-Bold' if entry['ownerAccepted'] else 'Helvetica', 8)
        pdf.drawString(413, y, entry['reviewLabel'])
        pdf.setFillColor(HexColor(blue))
        pdf.setFont('Helvetica-Bold', 8.5)
        start, end = entry['bundleStartPage'], entry['bundleEndPage']
        pdf.drawRightString(width - 42, y, str(start) if start == end else f'{start}-{end}')
        links.append(((36, y - 4, width - 36, y + 11), start - 1))
    assert y > 115, f'Contents table too long: {y}'
    pdf.setStrokeColor(HexColor('#C8D5E2'))
    pdf.line(36, 105, width - 36, 105)
    pdf.setFillColor(HexColor(muted))
    pdf.setFont('Helvetica', 8.5)
    pdf.drawString(36, 90, 'Geaccepteerd = deze exacte versie is door de eigenaar geaccepteerd.')
    pdf.drawString(36, 76, 'Richting akkoord = aanpak positief beoordeeld; exacte acceptatie staat nog open.')
    pdf.drawString(36, 62, 'Paginanummers hierboven verwijzen naar deze bundel. Gidsen behouden hun eigen nummering.')
    pdf.setFont('Helvetica', 8)
    pdf.drawString(36, 36, 'Reviewexemplaar | Eerdere versies blijven afzonderlijk bewaard voor vergelijking.')
    pdf.drawRightString(width - 36, 36, '1 / 49')
    pdf.showPage()
    pdf.save()
    return stream.getvalue(), links


def render(path, prefix):
    subprocess.run(['pdftoppm', '-r', '96', '-png', str(path), str(prefix)],
                   check=True, capture_output=True)


def pixels(path):
    with Image.open(path) as image:
        rgb = image.convert('RGB')
        return rgb.size, hashlib.sha256(rgb.tobytes()).hexdigest()


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--selection', type=Path, required=True)
    args = parser.parse_args()
    selection = json.loads(args.selection.read_text(encoding='utf-8'))
    filename = f"Handleidingen-reviewbundel-v{selection['bundleVersion']}.pdf"
    output = ROOT / 'output/pdf' / filename
    retained = args.selection.parent / filename
    if output.exists() or retained.exists():
        raise FileExistsError('Use a new integer bundle version; existing bundles are immutable.')
    proof = ROOT / 'output/manuals/proofs' / f"reviewbundel-v{selection['bundleVersion']}"
    proof.mkdir(parents=True, exist_ok=True)
    readers = {}
    next_page = 2
    for entry in selection['guides']:
        source = ROOT / entry['path']
        assert sha(source) == entry['sha256'], entry['code']
        reader = PdfReader(source)
        assert len(reader.pages) == entry['pages'], entry['code']
        readers[entry['code']] = reader
        entry['bundleStartPage'] = next_page
        entry['bundleEndPage'] = next_page + entry['pages'] - 1
        next_page += entry['pages']
    assert len(readers) == 22 and next_page == 50
    writer = PdfWriter()
    cover, links = contents(selection)
    writer.append(PdfReader(BytesIO(cover)), import_outline=False)
    writer.add_outline_item('Inhoud', 0)
    for entry in selection['guides']:
        writer.append(readers[entry['code']], import_outline=False)
        writer.add_outline_item(f"{entry['code']} - {entry['title']} (v{entry['version']})",
                                entry['bundleStartPage'] - 1)
    for rectangle, destination in links:
        added = writer.add_annotation(0, Link(rect=rectangle, target_page_index=destination, fit=Fit.fit()))
        # Internal PDF destinations must point to the page object, not a page number.
        added['/Dest'][0] = writer.pages[destination].indirect_reference
    writer.add_metadata({'/Title': f"Handleidingen - reviewbundel v{selection['bundleVersion']}",
                         '/Subject': 'Huidige 22 handleidingen met versie- en reviewoverzicht',
                         '/Author': 'Inbit', '/CreationDate': 'D:20260910120000+02\'00\''})
    writer.page_mode = '/UseOutlines'
    output.parent.mkdir(parents=True, exist_ok=True)
    with output.open('xb') as stream:
        writer.write(stream)
    print(f'Created {filename}; verifying 48 preserved guide pages.', flush=True)
    merged = PdfReader(output)
    assert len(merged.pages) == 49
    assert len(merged.outline) == 23
    assert len(merged.pages[0]['/Annots']) == 22
    for item, entry in zip(merged.outline[1:], selection['guides']):
        assert merged.get_destination_page_number(item) == entry['bundleStartPage'] - 1
    for annotation, entry in zip(merged.pages[0]['/Annots'], selection['guides']):
        target = annotation.get_object()['/Dest'][0].get_object()
        assert target.indirect_reference.idnum == merged.pages[entry['bundleStartPage'] - 1].indirect_reference.idnum
    for entry in selection['guides']:
        for number, page in enumerate(readers[entry['code']].pages):
            combined = merged.pages[entry['bundleStartPage'] - 1 + number]
            assert page.get_contents().get_data() == combined.get_contents().get_data()
            assert list(page.mediabox) == list(combined.mediabox)
            assert list(page.cropbox) == list(combined.cropbox)
    render(output, proof / 'bundle')
    def render_source(entry):
        render(ROOT / entry['path'], proof / entry['code'])
    with ThreadPoolExecutor(max_workers=4) as pool:
        list(pool.map(render_source, selection['guides']))
    checked = []
    for entry in selection['guides']:
        for number in range(entry['pages']):
            source_png = proof / f"{entry['code']}-{number + 1}.png"
            bundle_page = entry['bundleStartPage'] + number
            merged_png = proof / f'bundle-{bundle_page:02d}.png'
            assert pixels(source_png) == pixels(merged_png), (entry['code'], number + 1)
            checked.append({'code': entry['code'], 'sourcePage': number + 1,
                            'bundlePage': bundle_page, 'pixelsIdenticalAt96dpi': True})
        assert sha(ROOT / entry['path']) == entry['sha256']
    save_once(retained, output.read_bytes())
    record = {**selection, 'bundleFile': filename, 'sha256': sha(output),
              'bytes': output.stat().st_size, 'totalPages': 49, 'contentPages': 48,
              'contentsLinks': 22, 'bookmarks': 23, 'pageVerification': checked,
              'contentStreamsAndPageBoxesPreserved': True,
              'visualReview': 'Contents PNG requires operator inspection; source pages are pixel-identical.'}
    save_once(args.selection.parent / f'Handleidingen-reviewbundel-v{selection["bundleVersion"]}-manifest.json',
              (json.dumps(record, indent=2) + '\n').encode('utf-8'))
    shutil.copyfile(Path(__file__), args.selection.parent / f'Handleidingen-reviewbundel-v{selection["bundleVersion"]}-builder.py')
    print(json.dumps({'file': str(output), 'guides': 22, 'pages': 49,
                      'pixelIdenticalGuidePages': len(checked), 'sha256': sha(output)}, indent=2))


if __name__ == '__main__':
    main()
