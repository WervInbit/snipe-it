"""Package the two corrected examples; do not promote the remaining batch."""
from pathlib import Path
import base64
import hashlib
import html
from html.parser import HTMLParser
import json
import os
import shutil
import xml.etree.ElementTree as ET
import zipfile

from PIL import Image, ImageChops
from pypdf import PdfReader

ROOT=Path(__file__).resolve().parents[2]
ROUND=ROOT/'resources/manuals/operator-guides/review-rounds/2026-10-27-navigation-and-space'
OLD=ROOT/'resources/manuals/operator-guides/review-rounds/2026-10-20-remaining-guides'
DOCS=ROOT/'docs/manuals/operator-guides'
assert not (ROUND/'manifest.sha256').exists(), 'Sealed round: create a new revision.'
read=lambda p:json.loads(p.read_text(encoding='utf-8-sig'))
sha=lambda p:hashlib.sha256(p.read_bytes()).hexdigest()
write=lambda p,s:p.write_text(s,encoding='utf-8',newline='\n')
relative=lambda p,start:Path(os.path.relpath(p,start)).as_posix()
manifest=read(ROUND/'authoring-manifest.json'); previous={g['code']:g for g in read(OLD/'authoring-manifest.json')['guides']}
assert read(ROUND/'qa/recognition-review.json')['findings']==[]

changes={
 'AC-02':['Rode focus om het profielicoon en Wachtwoord wijzigen, met een volledige herkenbare menuregio.','Groter volledig formulier; huidig, nieuw en bevestiging staan uitgelegd bij de velden.','De echte succesmelding is tot de volledige tekst bijgesneden en op leesbare schaal geplaatst; reset/privacyhulp blijft staan.'],
 'AST-03':['Dashboard > Apparaten > alles weergeven, echte toegepaste zoekactie met Enter, en de + boven de lijst zijn afgebeeld.','Een bestaand resultaat gaat naar dezelfde identiteitscontrole; alleen een ontbrekend apparaat wordt nieuw geregistreerd.','Assettag, S/N, fabrikantcode en het deel na # zijn uitgelegd. Notities bij aanmaken zijn optioneel; de echte Opslaan-knop is zichtbaar.','Zelfde opgeslagen identiteit, Asset openen, printer/formaat, QR-preview, grotere volledige onderzijde en scancontrole.','Dubbele registratie, ontbrekend model, Unlock/Aa, onduidelijke printuitkomst, beschadigd label en verkeerde identiteit behouden praktische hulp.']}
support={
 'AC-02':{'current/new/confirmation':'Retained at step2, including the repeated native label.','password rule and privacy':'Retained beside the complete form.','failed save and other sessions':'Retained at step3.','missing current password':'Retained full USR-03 handoff.'},
 'AST-03':{'duplicate':'Step1 searches/applies Enter before creation; found records converge on step5.','missing model/code':'Step3 explains the manufacturer code and returns after CAT-01.','Unlock and Aa':'Ordinary guidance at step2.','printing failure':'Step6 recovery checks printer/paper/job and local support. Blind retry on ambiguous outcome retired because it can duplicate a pending print.','damaged label':'Step8 manual tag/serial search through SC-01.','wrong identity':'Step8: change nothing and ask Supervisor.','physical placement':'Full original photo retained; clearly separate physical example.'}}
for g in manifest['guides']:
 old=previous[g['code']];link=relative(ROOT/g['pdf'],DOCS/'reviews')
 text=f'''# {g['code']} v{g['version']} - Navigation And Space Correction

Status: working draft; not accepted. {g['pageCount']} A4 page(s).

[New PDF]({link}) | [Previous v{old['version']}]({relative(ROUND/'baselines'/Path(old['pdf']).name,DOCS/'reviews')}) | [Visual comparison]({relative(ROUND/'review.html',DOCS/'reviews')}#{g['code']})

Reader outcome and task ownership remain as specified. The owner feedback
changes the implementation of guidance, navigation and usable space; it does
not authorize dropping useful support or mechanically stretching every image.
The [shared feedback/audit](navigation-and-space-feedback-2026-10-27.md) records
the general reasoning and concrete remaining work for the other fifteen guides.

## Changes

'''+''.join('- '+c+'\n' for c in changes[g['code']])+'''
## Preserved Guidance And Placement

'''+''.join('- '+k+': '+v+'\n' for k,v in support[g['code']].items())+'''
## Evidence, Review And Limits

Genuine nl-NL captures use Refurbisher for the own-password form and Supervisor
for registration. The account-success proof is exact genuine retained evidence
from the earlier owned account trial, cropped as an anonymous detail. Asset20,
INBIT-AA0024 / GUIDE271027A, was created once for this round. Its form, saved
result and QR preview share the same identity; physical placement is a separate
canonical example. No physical device work or print was performed.

The current root StoreAssetRequest makes registration notes nullable. The
separate mounted follow-up checkout still has an older requires_note creation
validator. A demo note was used only for its actual saved fixture; empty-note
form screenshots are genuine prepared states, not proof of a successful
empty-note save in that checkout. No shared application source or permissions
were changed. See retained source fingerprints and provenance.

All three final pages were read visually at 144dpi and compared with the three
previous pages. Checks cover PDF sizes/text/delivery hashes, exact crop pixels,
focus bounds, embedded-source hashes, text/image overlap and step containment.
Named SVG groups retain live text, images and focus as separate objects.
Native Affinity import, unfamiliar-reader and printed-paper trials remain
unperformed. Technical checks do not establish instructional usability or
owner acceptance. The other fifteen October20 drafts were audited, not revised.
'''
 write(DOCS/'reviews'/f'{g["code"]}-v{g["version"]}.md',text)

# Equal A4 comparison widths; keep previous PDFs and images byte-exact.
sections=[]
for g in manifest['guides']:
 code=g['code'];old=previous[code];rows=[]
 for n in range(1,g['pageCount']+1):
  rows.append(f'<div class="pair"><figure><figcaption>Vorige v{old["version"]}, {n}/{g["pageCount"]}</figcaption><a href="baselines/{code}-{n}.png"><img src="baselines/{code}-{n}.png" alt="{code} vorige pagina {n}"></a></figure><figure><figcaption>Nieuw v{g["version"]}, {n}/{g["pageCount"]} - concept</figcaption><a href="renders/{code}-{n}.png"><img src="renders/{code}-{n}.png" alt="{code} nieuwe pagina {n}"></a></figure></div>')
 sections.append(f'<section id="{code}"><h2>{code} v{g["version"]}</h2><p><a href="{Path(g["pdf"]).name}">Nieuwe PDF</a> | <a href="baselines/{Path(old["pdf"]).name}">Vorige PDF</a> | <a href="{Path(g["html"]).name}">HTML</a></p><ul>'+''.join('<li>'+html.escape(c)+'</li>' for c in changes[code])+'</ul>'+''.join(rows)+'</section>')
write(ROUND/'review.html','''<!doctype html><html lang="nl"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Navigatie en ruimte - 27-10-2026</title><style>body{font:16px/1.5 system-ui,sans-serif;background:#eef2f6;color:#172434;margin:0}main{max-width:1600px;margin:auto;padding:24px}section{background:white;border:1px solid #c7d2de;border-radius:8px;padding:20px;margin:24px 0}.pair{display:grid;grid-template-columns:1fr 1fr;gap:20px}figure{margin:16px 0}img{width:100%;height:auto;display:block;border:1px solid #bac7d4}figcaption{font-weight:650;padding-bottom:8px}a{color:#145d93}@media(max-width:750px){.pair{grid-template-columns:1fr}}</style><main><h1>AC-02 en AST-03: navigatie, begeleiding en ruimte</h1><p>27-10-2026. Twee nieuwe werkconcepten, drie A4-pagina's. Nog niet geaccepteerd. Vorige versies blijven ongewijzigd.</p><p><a href="#AC-02">AC-02</a> | <a href="#AST-03">AST-03</a> | <a href="../../../../../docs/manuals/operator-guides/reviews/navigation-and-space-feedback-2026-10-27.md">Volledige feedback en audit van alle 17 gidsen</a> | <a href="editable-sources.zip">Bewerkbare SVG-bronnen</a></p><p>De andere vijftien gidsen zijn beoordeeld op dezelfde problemen en blijven hun vorige versie. De SVG-bronnen bevatten afzonderlijke tekst-, beeld- en focusobjecten; native Affinity-import is nog niet getest. Klik op een pagina voor volledige resolutie. Beide kolommen hebben dezelfde A4-breedte.</p>'''+''.join(sections)+'</main></html>')

# The SVGs retain live text and embedded images; also retain exact image assets.
editable=ROUND/'editable';(editable/'images').mkdir(parents=True,exist_ok=True)
outline=[]
for g in manifest['guides']:
 folder=editable/g['code'];folder.mkdir(exist_ok=True)
 outline.append(f'# {g["code"]} v{g["version"]}')
 for n in range(1,g['pageCount']+1):
  svg=ROUND/f'{g["stem"]}-page-{n}.svg';shutil.copyfile(svg,folder/f'page-{n}.svg')
  doc=ET.fromstring(svg.read_text());texts=[' '.join(t.itertext()).strip() for t in doc.iter() if t.tag.endswith('}text')]
  outline.append(f'\nPage {n}\n\n'+'\n\n'.join(texts))
for s in manifest['sources']:
 file=ROOT/s.get('crop',s['source']);shutil.copyfile(file,editable/'images'/(s['key']+file.suffix))
write(editable/'CONTENT.md','\n\n'.join(outline)+'\n')
write(editable/'README.txt','''Editable review sources - 27-10-2026

AC-02 v7 and AST-03 v18, three A4 pages; working drafts, not accepted.
Each page is an SVG with live Arial/Helvetica/sans-serif text, embedded raster
screenshots and separate named header, context, step, figure, focus and
completion groups. Exact image assets and the full text are supplied alongside.
Do not replace the pages with flattened images or outline all text for editing.

The SVGs were rendered and inspected in Chromium. Native Affinity opening,
font substitution, line placement, clipping and layer interpretation have not
been tested. This is an editable interchange package, not a native .afpub or
.afdesign file. Compare any imported page against the clean final PDF before
calling it ready. The remaining fifteen guides need their content pass first.
''')
with zipfile.ZipFile(ROUND/'editable-sources.zip','w',zipfile.ZIP_DEFLATED) as z:
 for p in sorted(editable.rglob('*')):
  if p.is_file():z.write(p,p.relative_to(editable).as_posix())

index=read(OLD/'current-authoring-index.json');updates={g['code']:g for g in manifest['guides']};index['guides']=[updates.get(g['code'],g) for g in index['guides']];index['status']='25 current proposals; AC-02/AST-03 corrected October27; other15 October20 batch subjects need the recorded content pass; no acceptance/runtime promotion'
write(ROUND/'current-authoring-index.json',json.dumps(index,indent=2)+'\n')
write(ROUND/'README.md','''# Navigation, Guidance And Space Corrections - 2026-10-27

AC-02 v7 and AST-03 v18 are new unaccepted working drafts, three A4 pages.
Use [review.html](review.html) for same-scale previous/new comparisons and
[the batch audit](../../../../../docs/manuals/operator-guides/reviews/navigation-and-space-feedback-2026-10-27.md)
for the concrete remaining work in the other fifteen October20 guides.

Retained: exact previous PDFs/HTML/renders/specifications, genuine screenshots
and state, measured crops, live-text SVGs, self-contained HTML, final PDFs,
144dpi PNGs, editable-sources.zip, source fingerprints, reasoning and QA.
The note rule follows the current root project and explicit owner direction;
the older mounted create validator is a recorded source mismatch.

Frozen HTML can be rendered into a separate destination with the shared
scripts/manuals/render-guide-html.mjs. Set GUIDE_NODE_MODULES_ROOT,
GUIDE_PDFINFO_PATH and GUIDE_PDFTOPPM_PATH for the bundled runtime. Dated
capture/generation/packaging scripts refuse to overwrite a sealed round.
Current owner decisions and specifications govern any future revision.

Editable interchange is provided for the two examples. Native Affinity import
and fonts/layers remain untested; no native Affinity document is claimed.
No physical print/device work, novice-reader trial or owner acceptance.
''')

# Exact artifacts, crops, historical packages and local comparison links.
report={'guides':[],'sourceCrops':0,'embeddedImages':0,'historicalFiles':0,'localLinks':0,'editablePages':3}
source_hashes={s['key']:s.get('cropSha256',s['sourceSha256']) for s in manifest['sources']}
for g in manifest['guides']:
 pdf=ROOT/g['pdf'];reader=PdfReader(pdf);assert len(reader.pages)==g['pageCount'];assert sha(pdf)==g['sha256']==sha(ROOT/'output/pdf'/pdf.name)
 for n,p in enumerate(reader.pages,1):
  assert abs(float(p.mediabox.width)*25.4/72-210)<.2 and abs(float(p.mediabox.height)*25.4/72-297)<.2
  assert g['code'] in p.extract_text()
  svg=ET.fromstring((ROUND/f'{g["stem"]}-page-{n}.svg').read_text())
  assert sum(1 for e in svg.iter() if e.tag.endswith('}text'))>20
  for e in svg.iter():
   if e.tag.endswith('}image'):
    digest=hashlib.sha256(base64.b64decode(e.attrib['href'].split(',',1)[1])).hexdigest();assert digest==source_hashes[e.attrib['data-recognition-source']];report['embeddedImages']+=1
 report['guides'].append({'code':g['code'],'version':g['version'],'pages':g['pageCount'],'sha256':g['sha256'],'visualReview':'All final pages read at144dpi; native controls and annotations rechecked.'})
for s in manifest['sources']:
 source=ROOT/s['source'];assert sha(source)==s['sourceSha256']
 if 'crop' not in s:continue
 target=ROOT/s['crop'];assert sha(target)==s['cropSha256'];b=s['cropPixels']
 with Image.open(source) as original,Image.open(target) as cropped:
  expected=original.crop((b['x'],b['y'],b['x']+b['w'],b['y']+b['h'])).convert('RGBA');assert expected.size==cropped.size;assert ImageChops.difference(expected,cropped.convert('RGBA')).getbbox() is None
 for mark in s['marks']:
  pad=mark.get('padding',0);assert mark['x']-pad>=0 and mark['y']-pad>=0 and mark['x']+mark['w']+pad<=b['w'] and mark['y']+mark['h']+pad<=b['h']
 report['sourceCrops']+=1
for package in ['accepted-guides-v1','review-rounds/2026-10-08-test-guides','review-rounds/2026-10-08-focus-corrections','review-rounds/2026-10-20-operational-guides','review-rounds/2026-10-20-remaining-guides']:
 p=ROOT/'resources/manuals/operator-guides'/package;m=read(p/'manifest.json');assert sha(p/'manifest.json')==(p/'manifest.sha256').read_text().split()[0]
 for f in m['files']:assert sha(p/f['path'])==f['sha256']
 report['historicalFiles']+=len(m['files'])
class Links(HTMLParser):
 def handle_starttag(self,tag,attrs):
  for key,value in attrs:
   if key in ['href','src'] and value and not value.startswith(('#','data:','http:','https:')):
    assert (ROUND/value.split('#')[0]).resolve().exists(),value
    report['localLinks']+=1
Links().feed((ROUND/'review.html').read_text())
write(ROUND/'qa/artifact-audit.json',json.dumps(report,indent=2)+'\n')
print(json.dumps({'guides':2,'pages':3,'sourceCrops':report['sourceCrops'],'historicalFilesUnchanged':report['historicalFiles'],'localLinks':report['localLinks'],'editablePages':3}))
