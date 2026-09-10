"""Build the separate, explicitly schematic catalogue mapping review prototype."""
import hashlib
import json
from pathlib import Path
import subprocess

from PIL import Image
from reportlab.pdfgen import canvas
from reportlab.lib.units import mm
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont

ROOT = Path(__file__).resolve().parents[2]
PROOF = ROOT / 'output/manuals/proofs/owner-corrections-2026-09-10/catalog-field-map-v1'
OUT = ROOT / 'output/pdf/Catalogus-van-invoer-naar-resultaat-v1-draft.pdf'
assert not (ROOT/'resources/manuals/operator-guides/review-rounds/2026-09-10/owner-corrections'/OUT.name).exists(), 'Frozen prototype: make the next integer version.'
EVIDENCE = ROOT / 'resources/manuals/operator-guides/evidence'
PROOF.mkdir(parents=True, exist_ok=True)
pdfmetrics.registerFont(TTFont('Arial', 'C:/Windows/Fonts/arial.ttf'))
pdfmetrics.registerFont(TTFont('ArialBold', 'C:/Windows/Fonts/arialbd.ttf'))
c = canvas.Canvas(str(OUT), pagesize=(210*mm, 297*mm))
c.setTitle('Catalogus: van invoer naar resultaat - ontwerpvoorbeeld v1')
used = set()
P = '#7A4E9D'
O = '#B87300'
G = '#138A43'


def text(x, y, value, size=10, color='#102033', bold=False):
    c.setFillColor(color)
    c.setFont('ArialBold' if bold else 'Arial', size)
    c.drawString(x*mm, (297-y)*mm, value)


def box(x, y, w, h, color=P, fill='#F8F4FC'):
    c.setStrokeColor(color)
    c.setFillColor(fill)
    c.setLineWidth(.55*mm)
    c.roundRect(x*mm, (297-y-h)*mm, w*mm, h*mm, min(2,w/2,h/2)*mm, fill=1, stroke=1)


def guide_ref(y, code, title):
    box(17,y,176,7,P,'#F8F4FC')
    c.setStrokeColor(P)
    c.circle(21*mm,(293.5-y)*mm,2.3*mm,fill=0,stroke=1)
    text(19.2,y+4.4,'CAT',5,P,True)
    text(26,y+4.7,f'{code} {title}',9,P,True)


def shot(file, crop, x, y, w):
    # Clip in the PDF canvas; the original screenshot bytes remain untouched.
    used.add(file)
    left, top, right, bottom = crop
    iw, ih = Image.open(EVIDENCE/file).size
    scale = w/(right-left)
    h = (bottom-top)*scale
    c.saveState()
    p = c.beginPath()
    p.rect(x*mm, (297-y-h)*mm, w*mm, h*mm)
    c.clipPath(p, fill=0, stroke=0)
    c.drawImage(str(EVIDENCE/file), (x-left*scale)*mm,
                (297-y-(ih-top)*scale)*mm, iw*scale*mm, ih*scale*mm)
    c.restoreState()
    return h


def arrow(x, y1, y2, label, color=P):
    c.setStrokeColor(color)
    c.setFillColor(color)
    c.setLineWidth(.8*mm)
    c.line(x*mm, (297-y1)*mm, x*mm, (297-y2)*mm)
    p=c.beginPath()
    p.moveTo(x*mm,(297-y2)*mm)
    p.lineTo((x-1.8)*mm,(299.8-y2)*mm)
    p.lineTo((x+1.8)*mm,(299.8-y2)*mm)
    p.close()
    c.drawPath(p,fill=1,stroke=0)
    text(x+5,(y1+y2)/2+1,label,9,color)


def header(page, title, subtitle):
    box(12,12,2,22,P,P)
    text(19,20,'Catalogus: van invoer naar resultaat',18,bold=True)
    text(19,28,title,12,P,True)
    text(19,35,subtitle,9)
    text(12,285,'Ontwerpvoorbeeld v1 | 2026-09-10 | Los te beoordelen; nog geen werkinstructie',8,P)
    text(181,285,f'{page} van 2',8)


header(1,'Een waarde uit een component',
       'Voorbeeld: HP ProBook 450 G8 met Intel Core i5-1135G7. Lees van boven naar beneden.')
box(12,43,186,70,O,'#FFF8E9')
text(17,51,'1  Invoer op de componentdefinitie',12,O,True)
text(17,58,'Processor = Intel Core i5-1135G7. Show as asset spec staat aan.',10)
shot('CAT-COMPONENT-DEFINITION-CONTRIBUTIONS-DESKTOP-02.png',(75,214,1125,426),17,63,176)
text(17,103,'De waarde hoort bij de herbruikbare moederborddefinitie.',9)
guide_ref(105,'CAT-04','Componentdefinities beheren')
arrow(30,113,128,'De modelspecificatie gebruikt deze definitie',O)
box(12,128,186,68)
text(17,137,'2  Verwacht onderdeel op het modelnummer',12,P,True)
shot('CAT-MODEL-SPEC-ROSTER-DESKTOP-01.png',(304,218,1110,379),17,143,176)
text(17,182,'Stks = 1: dit modelnummer verwacht eenmaal dit moederbord.',9)
text(17,187,'Derived attributes toont dezelfde processorwaarde.',9)
guide_ref(188,'CAT-02','Modelspecificatie opbouwen')
arrow(30,196,211,'Een asset met dit modelnummer gebruikt die verwachting',G)
box(12,211,186,53,G,'#EFFBF3')
text(17,220,'3  Verwacht resultaat op het asset',12,G,True)
text(17,227,'Schematisch voorbeeld; dit vak is geen schermafbeelding.',9,G)
text(20,239,'Specification',11,bold=True)
text(20,248,'Processor',10,bold=True)
text(83,248,'Intel Core i5-1135G7',11)
text(17,258,'Dit voorbeeld heeft geen vervanging, extra onderdeel of eigen afwijking.',9)
c.showPage()

header(2,'Directe modelwaarde en een eigen apparaat',
       'Hetzelfde voorbeeldmodel. Onderscheid de gedeelde standaard van de fysieke toestand.')
box(12,43,186,60)
text(17,52,'A  Direct ingevoerd op de modelspecificatie',12,P,True)
text(17,59,'Introductiejaar = 2021. Dit is een waarde voor het modelnummer.',10)
shot('CAT-MODEL-SPEC-SAVED-DESKTOP-01.png',(576,443,1127,569),17,64,155)
arrow(30,103,118,'Verschijnt in de specificatie van het gekoppelde asset',P)
box(12,118,186,54,G,'#EFFBF3')
text(17,127,'B  Lees de uitkomst op de assetdetailpagina',12,G,True)
text(17,134,'Schematisch: zo hangen de waarden samen. De schermopname volgt.',9,G)
text(20,145,'Introductiejaar',10,bold=True)
text(83,145,'2021',11)
text(135,145,'Uit modelnummer',9,P)
text(20,155,'Processor',10,bold=True)
text(83,155,'Intel Core i5-1135G7',10)
text(135,163,'Via component',9,O)
box(12,182,186,53,O,'#FFF8E9')
text(17,191,'C  Later verandert alleen dit apparaat',12,O,True)
text(17,200,'Voorbeeld: de RAM-module van 8 GB wordt vervangen door 16 GB.',10)
text(17,208,'Registreer de werkelijke onderdelen bij dat asset. De fabrikantcode',10)
text(17,215,'blijft dezelfde; wijzig de gedeelde standaard niet voor dit ene apparaat.',10)
text(17,228,'Voer RAM niet nogmaals direct in als het al via componenten meetelt.',9,O,True)
box(12,244,186,27,'#53657A','#F8FAFC')
text(17,253,'Nog te toetsen voor opname in de gidsen',10,bold=True)
text(17,260,'Leg dezelfde asset vast met modelcode, componenten en Specification.',9)
text(17,267,'Controleer de bronlabels en het 8-naar-16-GB-resultaat in diezelfde opname.',9)
c.save()
subprocess.run(['pdftoppm','-png','-r','144',str(OUT),str(PROOF/'page')],check=True,capture_output=True)
report = {'version':1,'pages':2,'status':'Unaccepted visual prototype; schematic asset results',
          'sha256':hashlib.sha256(OUT.read_bytes()).hexdigest(),
          'evidence':[{ 'file':f,'sha256':hashlib.sha256((EVIDENCE/f).read_bytes()).hexdigest()} for f in sorted(used)],
          'remaining':'Capture and verify the same asset specification, component detail, source labels and replacement result before instructional integration.'}
(PROOF/'validation.json').write_text(json.dumps(report,indent=2)+'\n',encoding='utf-8')
print(json.dumps(report))
