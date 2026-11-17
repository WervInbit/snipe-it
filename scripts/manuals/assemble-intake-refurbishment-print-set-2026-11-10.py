"""Assemble per-guide source PDFs with duplex-safe guide boundaries."""
from pathlib import Path
import hashlib
import html
import json
from pypdf import PdfReader, PdfWriter

ROOT = Path(__file__).resolve().parents[2]
OUT = ROOT / 'output/pdf/intake-refurbishment-2026-11-10'
manifest = json.loads((OUT / 'manifest.json').read_text(encoding='utf-8'))
guides = {g['code']: g for g in manifest['guides']}
for guide in guides.values():
    reader = PdfReader(guide['intermediatePdf'])
    assert len(reader.pages) == guide['pages'], guide['code']
    details = []
    for number, page in enumerate(reader.pages, 1):
        width, height = float(page.mediabox.width) * 25.4 / 72, float(page.mediabox.height) * 25.4 / 72
        assert abs(width - 210) < .5 and abs(height - 297) < .5, (guide['code'], width, height)
        text = page.extract_text()
        assert guide['code'] in text and len(text) > 100, (guide['code'], number)
        details.append({'page': number, 'widthMm': width, 'heightMm': height, 'textCharacters': len(text)})
    guide['pdfCheck'] = details

sets = []
for key, filename, label in [('core', '01-basis-inname-en-refurbishment.pdf', 'Basis: inname en refurbishment'),
                              ('full', '02-inname-en-refurbishment-met-componenten.pdf', 'Inname en refurbishment met componentwerk')]:
    writer = PdfWriter()
    starts, blank_pages, printed = [], [], 0
    for code in manifest[key]:
        guide = guides[code]
        reader = PdfReader(guide['intermediatePdf'])
        start = len(writer.pages) + 1
        assert start % 2 == 1
        writer.add_outline_item(code + ' - ' + guide['title'], start - 1)
        for page in reader.pages:
            writer.add_page(page)
        printed += guide['pages']
        if guide['pages'] % 2:
            writer.add_blank_page(width=float(reader.pages[0].mediabox.width), height=float(reader.pages[0].mediabox.height))
            blank_pages.append(len(writer.pages))
        starts.append({'code': code, 'version': guide['version'], 'startPage': start, 'contentPages': guide['pages']})
    writer.add_metadata({'/Title': label + ' - werkversies 10 november 2026',
                         '/Subject': 'Afdrukken op A4, ware grootte, dubbelzijdig over lange zijde. Lege achterzijden scheiden handleidingen.',
                         '/Creator': 'Guide source print preparation; Affinity masters pending'})
    path = OUT / filename
    assert not path.exists(), 'Existing print PDF preserved.'
    with path.open('xb') as f:
        writer.write(f)
    check = PdfReader(path)
    assert len(check.pages) == len(writer.pages) and len(check.pages) % 2 == 0
    for page_number in blank_pages:
        assert not check.pages[page_number - 1].extract_text().strip()
    sets.append({'key': key, 'file': filename, 'label': label, 'guides': starts, 'printedSides': printed,
                 'pdfPages': len(check.pages), 'duplexSheets': len(check.pages) // 2, 'blankPages': blank_pages,
                 'sha256': hashlib.sha256(path.read_bytes()).hexdigest()})
manifest['sets'] = sets
(OUT / 'manifest.json').write_text(json.dumps(manifest, ensure_ascii=False, indent=2) + '\n', encoding='utf-8')
readme = '''AFDRUKSET - INNAME EN REFURBISHMENT - 10 NOVEMBER 2026

Basis: 9 handleidingen,13 bedrukte A4-zijden,9 vellen dubbelzijdig.
Met componentwerk:12 handleidingen,17 bedrukte A4-zijden,12 vellen dubbelzijdig.

Afdrukinstellingen: A4, ware grootte/100%, dubbelzijdig over de lange zijde.
Elke handleiding begint op de voorkant van een nieuw vel. De lege achterzijden
zijn bewust ingevoegd. Bronpaginanummers en voorgestelde versies blijven intact.

De PDF's zijn afdrukken van de huidige werkversies. Er is geen nieuwe inhoud of
acceptatie verondersteld. Bewerkbare native Affinity-bestanden volgen apart.
AST-03 en AST-05 horen bij Supervisor. Componentwerk hoort bij Senior Refurbisher.

Bij ontbrekend model/modelnummer: CAT-01. Bij nieuwe standaard-specificaties en
verwachte componenten voor een modelnummer: CAT-02. Bij een ontbrekend herbruikbaar
componenttype: CAT-04. Deze catalogustaken zijn geen verplichte dagelijkse
refurbishstappen en zijn niet in deze basissets opgenomen.
'''
(OUT / 'README.txt').write_text(readme, encoding='utf-8')
rows = ''.join(f'<tr><td>{html.escape(code)}</td><td>{html.escape(guides[code]["title"])}</td><td>{guides[code]["pages"]}</td></tr>' for code in manifest['full'])
links = ''.join(f'<p><a href="{s["file"]}">{html.escape(s["label"])}</a> - {s["printedSides"]} bedrukte zijden, {s["duplexSheets"]} vellen dubbelzijdig</p>' for s in sets)
(OUT / 'REVIEW.html').write_text('<!doctype html><html lang="nl"><meta charset="utf-8"><title>Afdrukset inname en refurbishment</title>'
    '<style>body{font:17px/1.55 Arial;max-width:1000px;margin:35px auto;padding:0 22px;color:#102033}a{color:#075e91}td,th{text-align:left;padding:9px 15px;border-bottom:1px solid #ccd}table{border-collapse:collapse}</style>'
    '<h1>Afdrukset: inname en refurbishment</h1><p>Huidige werkversies. Native Affinity-bewerking volgt apart.</p>' + links +
    '<p><strong>A4 - ware grootte/100% - dubbelzijdig over de lange zijde.</strong> Lege achterzijden houden elke handleiding op een eigen vel.</p>'
    '<table><thead><tr><th>Code</th><th>Handleiding</th><th>Zijden</th></tr></thead><tbody>' + rows + '</tbody></table>'
    '<p>CMP-01/02/04 zijn opgenomen in de set met componentwerk. CAT-01/02 zijn alleen nodig als ook de catalogus moet worden aangevuld.</p>'
    '<p><a href="README.txt">Afdrukinstructies</a></p></html>', encoding='utf-8')
print(json.dumps({'sets': [{k: v for k, v in s.items() if k != 'guides'} for s in sets]}, ensure_ascii=True))
