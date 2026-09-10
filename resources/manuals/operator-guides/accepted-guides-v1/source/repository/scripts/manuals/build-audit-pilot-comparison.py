"""Package preserved PDFs and audit candidates without changing their pages."""
from pathlib import Path
from io import BytesIO
import hashlib
import json
import os

from pypdf import PdfReader, PdfWriter
from reportlab.lib.colors import HexColor
from reportlab.lib.pagesizes import A4
from reportlab.pdfgen import canvas

ROOT = Path(__file__).resolve().parents[2]
OUTPUT = Path(os.environ.get('SNIPEIT_GUIDE_PDF_OUT_DIR', ROOT / 'output/pdf/audit-pilot-2026-09-08'))
CHECKPOINT = ROOT / 'resources/manuals/operator-guides/checkpoints/2026-09-08-before-audit-revisions'
baseline = json.loads((CHECKPOINT / 'manifest.json').read_text(encoding='utf-8'))
manifest = json.loads((OUTPUT / 'manifest.json').read_text(encoding='utf-8'))
guides = manifest['artifacts']

def digest(file):
    return hashlib.sha256(file.read_bytes()).hexdigest()

for entry in baseline['artifacts']:
    assert digest(CHECKPOINT / entry['checkpointPath']) == entry['sha256']
    assert digest(ROOT / entry['originalPath']) == entry['sha256']
for entry in guides:
    assert digest(OUTPUT / entry['file']) == entry['sha256']

def old_for(guide, version_key):
    return next(item for item in baseline['artifacts']
                if item['code'] == guide['code'] and item['version'] == guide[version_key])

old_pages = sum(old_for(guide, 'baselineVersion')['pages'] for guide in guides)
new_pages = sum(guide['pages'] for guide in guides)
old_start = 3
new_divider = old_start + old_pages
new_start = new_divider + 1
accepted_divider = new_start + new_pages
accepted_start = accepted_divider + 1
positions = []
old_cursor, new_cursor = old_start, new_start
for guide in guides:
    previous = old_for(guide, 'baselineVersion')
    positions.append({**guide, 'oldPage': old_cursor, 'oldPages': previous['pages'], 'newPage': new_cursor})
    old_cursor += previous['pages']
    new_cursor += guide['pages']

def page_range(start, count):
    return str(start) if count == 1 else f'{start}-{start + count - 1}'

def sheet(title, subtitle, lines, color='#4F46E5', table=False):
    stream = BytesIO()
    pdf = canvas.Canvas(stream, pagesize=A4, invariant=1)
    width, height = A4
    pdf.setFillColor(HexColor(color))
    pdf.rect(42, height - 120, 6, 69, fill=1, stroke=0)
    pdf.setFillColor(HexColor('#102033'))
    pdf.setFont('Helvetica-Bold', 24)
    y = height - 70
    for line in title:
        pdf.drawString(61, y, line)
        y -= 30
    pdf.setFont('Helvetica', 11)
    pdf.setFillColor(HexColor('#53657A'))
    pdf.drawString(61, y - 7, subtitle)
    y -= 72
    if table:
        pdf.setFont('Helvetica-Bold', 11)
        for x, label in [(48, 'Gids'), (154, 'Eerder concept'), (313, 'Nieuw concept')]:
            pdf.drawString(x, y, label)
        y -= 13
        pdf.setStrokeColor(HexColor('#C8D5E2'))
        pdf.line(48, y, width - 48, y)
        for item in positions:
            y -= 30
            pdf.setFont('Helvetica-Bold', 11)
            pdf.drawString(48, y, item['code'])
            pdf.setFont('Helvetica', 11)
            pdf.drawString(154, y, f"v{item['baselineVersion']} | p. {page_range(item['oldPage'], item['oldPages'])}")
            pdf.drawString(313, y, f"v{item['version']} | p. {page_range(item['newPage'], item['pages'])}")
        y -= 45
    pdf.setFillColor(HexColor('#102033'))
    for line in lines:
        if not line:
            y -= 12
            continue
        pdf.setFont('Helvetica', 11)
        pdf.drawString(48, y, line)
        y -= 18
    pdf.setFillColor(HexColor(color))
    pdf.setFont('Helvetica-Bold', 11)
    pdf.drawString(48, 79, 'Nieuwe voorstellen zijn niet geaccepteerd.')
    pdf.setFillColor(HexColor('#53657A'))
    pdf.setFont('Helvetica', 9)
    pdf.drawString(48, 53, 'Vergelijkingspakket | Beoordelingsronde 2026-09-08 | Geen wijziging van eerdere acceptatie')
    pdf.showPage()
    pdf.save()
    stream.seek(0)
    return PdfReader(stream).pages[0]

writer = PdfWriter()
writer.add_page(sheet(['Handleidingen vergelijken'], 'Drie gerichte voorstellen na de onafhankelijke audit', [
    'Gebruik de paginanummers van dit PDF-bestand of de bladwijzers.',
    f'Het eerder intern geaccepteerde WF-01 v9 staat apart op pagina {accepted_start}.',
    '',
    'WF-01: eerst bestaande workflows controleren; daarna een route kiezen.',
    'Afweging: minder kans op dubbel starten, maar extra controle vooraf.',
    '',
    'USR-04: uitschakelen, verwijderen en herstellen hebben elk een eigen route.',
    'Afweging: duidelijker eindpunten; drie pagina\'s in plaats van twee.',
    '',
    'CAT-04: expliciete toevoegknoppen, grotere tekst en een eerlijk overlapvoorbeeld.',
    'Afweging: zes pagina\'s blijven nodig; sommige beelden zijn strakker uitgesneden.',
    '',
    'Vergelijk leesbaarheid op 100% of geprint op A4, routekeuze en eindcontrole.',
    'Geef per wijziging aan: behouden, terugdraaien of aanpassen.',
    'Deze proef vervangt geen praktijktest met een nieuwe gebruiker.',
], table=True))
writer.add_outline_item('Leeswijzer en vergelijking', 0)
writer.add_page(sheet(['Eerdere concepten'], 'Bevroren versies van voor de nieuwe auditvoorstellen', [
    'De volgende pagina\'s zijn de bestaande WF-01 v10, USR-04 v3 en CAT-04 v2.',
    'Deze drie versies waren nog niet geaccepteerd.',
    '',
    'De oorspronkelijke bestanden en hun controlesommen zijn bewaard.',
    'Een kopie in dit pakket verandert hun eerdere status niet.',
    '',
    f'De nieuwe beoordelingsronde begint op pagina {new_divider}.',
], color='#53657A'))
old_parent = writer.add_outline_item('Eerdere concepten - bevroren', 1)
for guide, position in zip(guides, positions):
    previous = old_for(guide, 'baselineVersion')
    writer.append(str(CHECKPOINT / previous['checkpointPath']), import_outline=False)
    writer.add_outline_item(f"{guide['code']} v{guide['baselineVersion']} - eerder", position['oldPage'] - 1, old_parent)

assert len(writer.pages) + 1 == new_divider
writer.add_page(sheet(['Nieuwe beoordelingsronde', '2026-09-08'], 'Niet geaccepteerd - los beoordelen van de eerdere versies', [
    'Hier beginnen WF-01 v11, USR-04 v4 en CAT-04 v3.',
    'De versie en conceptstatus staan op iedere nieuwe gidspagina.',
    '',
    'Deze voorstellen mogen afzonderlijk worden afgewezen of aangepast.',
    'Een auditbevinding wijzigt niet automatisch een eerder geaccepteerde regel.',
    '',
    'Beoordeel vooral:',
    '1. Is zonder systeemkennis duidelijk waar je begint?',
    '2. Is duidelijk welke keuze je moet maken en welke route je overslaat?',
    '3. Kun je tekst en belangrijke schermdetails lezen op A4?',
    '4. Is duidelijk wanneer je klaar bent of hulp moet vragen?',
    '',
    'Bestaande goedkeuringen en de bevroren bestanden blijven intact.',
], color='#C66A00'))
new_parent = writer.add_outline_item('Nieuwe beoordelingsronde - niet geaccepteerd', new_divider - 1)
for guide, position in zip(guides, positions):
    writer.append(str(OUTPUT / guide['file']), import_outline=False)
    writer.add_outline_item(f"{guide['code']} v{guide['version']} - nieuw concept", position['newPage'] - 1, new_parent)

assert len(writer.pages) + 1 == accepted_divider
writer.add_page(sheet(['Eerder intern geaccepteerd'], 'WF-01 v9 - aanvullende vergelijkingsbasis', [
    'Deze exacte versie is een Internal review candidate.',
    'Dat is interne acceptatie, geen goedkeuring door een externe beoordelaar.',
    '',
    'WF-01 v10 was een later, nog niet geaccepteerd concept.',
    'WF-01 v11 is het nieuwe auditvoorstel in dit pakket.',
    '',
    'Vergelijk dus zowel met het laatste concept als met deze geaccepteerde versie.',
], color='#138A43'))
accepted = old_for(guides[0], 'acceptedPredecessorVersion')
writer.append(str(CHECKPOINT / accepted['checkpointPath']), import_outline=False)
writer.add_outline_item('WF-01 v9 - eerder intern geaccepteerd', accepted_start - 1)
writer.add_metadata({'/Title': 'Handleidingen vergelijken - auditronde 2026-09-08',
                     '/Subject': 'Bevroren bestaande versies en drie ongeaccepteerde voorstellen',
                     '/Author': 'Operator guide project'})
writer.page_mode = '/UseOutlines'
file = OUTPUT / 'Handleidingen-vergelijking-auditronde-2026-09-08.pdf'
with file.open('wb') as stream:
    writer.write(stream)
comparison = {'file': file.name, 'pages': len(writer.pages), 'sha256': digest(file),
              'newRoundDividerPage': new_divider, 'acceptedWf01Page': accepted_start,
              'comparisonPages': [{key: item[key] for key in ('code', 'oldPage', 'oldPages', 'newPage', 'pages')}
                                  for item in positions]}
manifest['comparison'] = comparison
(OUTPUT / 'manifest.json').write_text(json.dumps(manifest, indent=2) + '\n', encoding='ascii')
print(json.dumps(comparison, indent=2))
