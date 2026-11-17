"""Build the flat, portable Handleidingen delivery from verified native copies."""
import argparse
import hashlib
import html
import json
import os
from pathlib import Path
import re
import shutil
import unicodedata
from urllib.parse import quote, unquote, urlsplit

from PIL import Image
from pypdf import PdfReader

REPO = Path(__file__).resolve().parents[2]
TEMPLATES = Path(__file__).with_name('shared-folder')
RULE_FILES = ['authoring-method.md', 'system.md', 'components.md', 'layouts.md',
              'screenshots.md', 'decisions.md', 'maintenance.md', 'catalog-guide-plan.md']


def digest(path):
    with path.open('rb') as stream:
        return hashlib.file_digest(stream, 'sha256').hexdigest()


def dump(path, value):
    path.parent.mkdir(parents=True, exist_ok=True)
    path.write_text(json.dumps(value, indent=2, ensure_ascii=False) + '\n', encoding='utf-8')


def text(path, value):
    path.parent.mkdir(parents=True, exist_ok=True)
    path.write_text(value, encoding='utf-8')


def relative(source, target):
    return Path(os.path.relpath(target, source.parent)).as_posix()


def normalize(value):
    return unicodedata.normalize('NFKC', value or '')


def category(code):
    return {'AC': 'Account', 'AST': 'Apparaten', 'SC': 'Apparaten',
            'WF': 'Workflows', 'CMP': 'Componenten', 'CAT': 'Catalogus',
            'USR': 'Gebruikersbeheer', 'HELP': 'Hulp'}[code.split('-')[0]]


def create_index(manuals):
    cards = []
    for manual in manuals:
        esc = html.escape
        title, code = esc(manual['title']), manual['code']
        cards.append(f'''<article data-category="{esc(manual['category'])}" data-search="{esc((code+' '+manual['title']+' '+manual['role']+' '+manual['category']).lower())}">
<div class="meta"><span class="code">{code}</span><span>{esc(manual['category'])}</span></div>
<h2>{title}</h2><p class="details">{esc(manual['role'])} <span aria-hidden="true">&middot;</span> {manual['pages']} {'pagina' if manual['pages']==1 else "pagina's"}</p>
<div class="actions"><a class="pdf" href="{quote(manual['pdf']['file'])}">PDF openen</a><a href="{quote(manual['native']['file'])}">Bewerken in Affinity</a></div></article>''')
    options = ''.join(f'<option>{html.escape(item)}</option>' for item in ['Account', 'Apparaten', 'Workflows', 'Componenten', 'Catalogus', 'Gebruikersbeheer', 'Hulp'])
    return '''<!doctype html><html lang="nl"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Handleidingen - Inbit Snipe-IT</title><style>
:root{font-family:Arial,sans-serif;color:#20332f;background:#f3f6f4}*{box-sizing:border-box}body{margin:0}main{max-width:1160px;margin:auto;padding:38px 26px}header{margin-bottom:26px}.eyebrow{color:#49645a;font-size:13px;letter-spacing:.08em;text-transform:uppercase}h1{font-size:40px;letter-spacing:-1px;margin:9px 0 12px}header p{max-width:760px;line-height:1.6;color:#475b53}.tools{display:flex;gap:18px;margin:26px 0 14px;align-items:end}.field{display:flex;flex-direction:column;gap:8px;flex:1}.field.small{flex:0 1 250px}label{font-size:14px;font-weight:bold}input,select{font:inherit;padding:13px 14px;border:1px solid #b0bfb7;border-radius:7px;background:white;color:inherit;width:100%}input:focus,select:focus,a:focus{outline:3px solid #80b5a0;outline-offset:3px}.count{font-size:13px;color:#50665d;margin:0 0 15px}.grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px}article{display:flex;flex-direction:column;background:white;border:1px solid #d8e1dc;border-radius:10px;padding:22px;min-height:218px}article[hidden]{display:none}.meta{display:flex;justify-content:space-between;gap:12px;font-size:12px;color:#5a6c64}.code{font-weight:bold;color:#176844}h2{font-size:20px;line-height:1.3;margin:18px 0 9px}.details{font-size:13px;color:#5a6c64;line-height:1.5;margin:0 0 20px}.actions{display:flex;flex-wrap:wrap;align-items:center;gap:13px;margin-top:auto}a{color:#176844;text-underline-offset:3px}.actions a{font-size:13px}.actions .pdf{background:#176844;color:white;border-radius:5px;padding:10px 13px;text-decoration:none;font-weight:bold}footer{margin-top:30px;border-top:1px solid #ccd8d1;padding-top:19px;display:flex;flex-wrap:wrap;gap:18px;font-size:13px;color:#50665d}.empty{padding:28px;background:white;border-radius:8px}.instructions{font-size:13px!important} @media(max-width:900px){.grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:580px){main{padding:26px 16px}.grid{grid-template-columns:1fr}.tools{flex-direction:column;align-items:stretch}.field.small{flex:auto}h1{font-size:34px}}
</style><main><header><div class="eyebrow">Inbit Snipe-IT</div><h1>Handleidingen</h1>
<p>Zoek de taak die je wilt uitvoeren. Open de PDF om te lezen of af te drukken. Kies Affinity om de handleiding te bewerken.</p>
<p class="instructions">Je kunt de bestanden ook direct vinden in de mappen <strong>PDF</strong> en <strong>Affinity</strong>. Afdrukken: A4 op 100%.</p></header>
<div class="tools"><div class="field"><label for="search">Zoek op taak of code</label><input id="search" type="search" placeholder="Bijvoorbeeld wachtwoord, apparaat of WF-01" autocomplete="off"></div>
<div class="field small"><label for="category">Onderwerp</label><select id="category"><option value="">Alle onderwerpen</option>''' + options + '''</select></div></div>
<p class="count" id="count" role="status">25 handleidingen</p><section class="grid" aria-label="Handleidingen">''' + '\n'.join(cards) + '''</section>
<p id="empty" class="empty" hidden>Geen handleiding gevonden. Probeer een andere taaknaam of kies Alle onderwerpen.</p>
<footer><span>25 handleidingen &middot; 68 pagina's</span><a href="_Beheer/LEESMIJ.html">Bewerken en afdrukken</a><a href="_Beheer/OPEN-PUNTEN.html">Versiestatus en open punten</a></footer>
</main><script>
const search=document.getElementById('search'),category=document.getElementById('category'),cards=[...document.querySelectorAll('article')];
function filter(){const terms=search.value.toLocaleLowerCase('nl').trim().split(' ').filter(Boolean);let count=0;for(const card of cards){const matches=(!category.value||category.value===card.dataset.category)&&terms.every(t=>card.dataset.search.includes(t));card.hidden=!matches;if(matches)count++;}document.getElementById('count').textContent=count+' van 25 handleidingen';document.getElementById('empty').hidden=count!==0;}
search.addEventListener('input',filter);category.addEventListener('change',filter);
</script></html>'''


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--source', required=True, type=Path)
    parser.add_argument('--work', required=True, type=Path)
    parser.add_argument('--verification', required=True, type=Path)
    parser.add_argument('--archive', required=True, type=Path)
    parser.add_argument('--output', required=True, type=Path)
    args = parser.parse_args()
    out = args.output.resolve()
    assert not out.exists(), 'Existing delivery/human edits preserved; choose a new output'
    source = args.source.resolve()
    manifest = json.loads((source/'manifest.json').read_text(encoding='utf-8'))
    proof = json.loads(args.verification.read_text(encoding='utf-8'))
    assert proof['qualified'] and proof['manuals'] == 25 and proof['pages'] == 68
    proven = {r['code']: r for r in proof['results']}
    changes = {r['code']: r for r in json.loads((source/'Source-preparation-2026-11-04/review/changes.json').read_text(encoding='utf-8'))}
    for manual in manifest['guides']:
        checked = proven[manual['code']]
        assert digest(Path(checked['native'])) == checked['native_sha256']
        assert digest(Path(checked['pdf'])) == checked['pdf_sha256']
        assert digest(source/manual['native']) == manual['native_sha256']
        assert digest(source/manual['pdf']) == manual['pdf_sha256']
    out.mkdir(parents=True)

    def copy(src, destination):
        destination = Path(destination)
        target = out/destination
        target.parent.mkdir(parents=True, exist_ok=True)
        if target.exists():
            assert digest(src) == digest(target), f'Conflicting destination {target}'
        else:
            shutil.copy2(src, target)
        return destination.as_posix()

    for name, target in [('AGENTS.md','AGENTS.md'), ('LEESMIJ.md','_Beheer/LEESMIJ.md'), ('controleer-map.py','_Beheer/hulpmiddelen/controleer-map.py')]:
        copy(TEMPLATES/name, target)
    copy(TEMPLATES/'controleer-index.js','_Beheer/hulpmiddelen/controleer-index.js')
    assert digest(args.archive) == '196807dc8c2166b174a2709047a52f3d698c3a6e1daa1adce023b7977378b66e', 'Unexpected historical archive'
    copy(args.archive, '_Beheer/archief/Technische-overdracht-2026-11-12.zip')
    copy(args.verification, '_Beheer/controle/ingebedde-manuals.json')
    copy(args.work.parent/'embedding-diagnostic-comparison.json','_Beheer/controle/strikte-vergelijking.json')
    copy(args.work.parent/'standalone-verification.json','_Beheer/controle/losse-bestanden.json')
    copy(args.work.parent/'embedded-ui/proof.json','_Beheer/controle/AC-01-resource-manager-proef.json')
    copy(source/'Evidence/handoff-verification.json', '_Beheer/controle/eerdere-overdracht.json')
    copy(source/'Evidence/visual-review.json', '_Beheer/controle/visuele-controle.json')
    for original, renamed in [('Guide-basis.af','Handleiding-basis.af'), ('Guide-basis-preview.pdf','Handleiding-basis-preview.pdf'), ('styles.json','stijlen.json')]:
        copy(source/'Template'/original, Path('_Beheer/sjabloon')/renamed)
    original_notes = (source/'OPEN-POINTS.md').read_text(encoding='utf-8')
    text(out/'_Beheer/OPEN-PUNTEN.md', """# Versiestatus en open punten

Alle 25 handleidingen zijn native omgezet en technisch/redactioneel gecontroleerd.
De huidige versies hebben geen automatische exacte eigenaarsacceptatie. Alle
170 beeldplaatsingen zijn ingebed; de 25 afdruk-PDFs blijven byte voor byte gelijk.
Bij nieuwe native proefexports zijn 67 van 68 pagina's pixel-identiek op 150 dpi.
Alleen het telefoonscreenshot in AC-01 geeft een kleine kleurvariatie, maximaal
4 op 255 per kanaal. Volledige bronpixels, tekst, afmetingen en kaders zijn gelijk;
ook inbedding via Resource Manager geeft die variatie. Dit is afzonderlijk
vastgelegd, geen algemene tolerantieregel voor latere wijzigingen.
De map is zelfstandig verplaatsbaar qua beeldbronnen. Een andere computer/
lettertype-installatie en operator-/papierproeven blijven afzonderlijke controles.
De inhoudelijke versies zijn ongewijzigd.

"""+original_notes)
    text(out/'_Beheer/WIJZIGINGEN.md', """# Handleidingen en wijzigingen

Deze gedeelde map gebruikt twee vlakke bestandsmappen en beschrijvende namen.
De native beeldplaatsingen zijn zelfstandig gemaakt met volledige ingebedde
pixels; teksten, uitsneden, markeringen en inhoudelijke versies blijven gelijk.
De 25 bestaande afdruk-PDFs zijn byte voor byte behouden. Nieuwe native
proefexports behouden de tekst en indeling: 67 pagina's zijn pixel-identiek
op 150 dpi, met een afzonderlijk gemeten kleine kleurvariatie in het AC-01
telefoonscreenshot. Zie OPEN-PUNTEN en controle voor de precieze grens.
Volledige originele beeldbestanden zijn apart behouden.

"""+(source/'CHANGES.md').read_text(encoding='utf-8').replace('gidsen','handleidingen').replace('gids','handleiding'))

    manuals = []
    for old in manifest['guides']:
        code, version = old['code'], old['version']
        checked = proven[code]
        native_proof = json.loads((args.work/'reports'/f'{code}.json').read_text(encoding='utf-8'))
        stem = code+' - '+old['title']
        native = copy(Path(checked['native']), Path('Affinity')/(stem+'.af'))
        pdf = copy(source/old['pdf'], Path('PDF')/(stem+'.pdf'))
        copy(args.work/'reports'/f'{code}.json', f'_Beheer/controle/native/{code}.json')
        idml = copy(source/old['idml'], Path('_Beheer/bronmateriaal/idml')/Path(old['idml']).name)
        pages = [normalize(page.extract_text()) for page in PdfReader(out/pdf).pages]
        role_match = re.search(r'(?:^|\n)Rol\s*\n([^\n]+)', pages[0])
        assert role_match, f'No exact role label found for {code}'
        role = role_match.group(1).strip()
        images = []
        for image in old['images']:
            file = copy(source/image['file'], Path('_Beheer/bronmateriaal/beelden')/Path(image['file']).name)
            with Image.open(out/file) as original:
                dimensions = list(original.size)
            images.append({'file':file,'sha256':image['sha256'],'pixels':dimensions})
        layout_sources = copy(source/'Source-preparation-2026-11-04/source'/f'{code}.json', f'_Beheer/bronmateriaal/compositie/{code}.json')
        metadata = {'code':code,'title':old['title'],'category':category(code),'role':role,
                    'version':version,'pages':old['pages'],'status':'Native werkversie; exacte acceptatie open',
                    'native':{'file':native,'sha256':checked['native_sha256'],'embedded_image_placements':checked['embedded_images']},
                    'pdf':{'file':pdf,'sha256':old['pdf_sha256'],'unchanged_from_previous_delivery':True},
                    'native_export_verification':{'sha256':checked['pdf_sha256'],'pixel_identical':all(p['equal'] for p in checked['rendered_pages'])},
                    'text':f'_Beheer/handleidingen/{code}.md','specification':f'_Beheer/regels/handleidingen/{code}.md',
                    'images':images,'idml_provenance':idml,'prior_composition':layout_sources,
                    'verification':f'_Beheer/controle/native/{code}.json',
                    'native_image_map':[{'index':i,'source':next(x['file'] for x in images if Path(x['file']).name==Path(n['source']).name),
                                         'transform':n['transform'],'parent_transform':n['parentTransform'],'pixels':[n['width'],n['height']]} for i,n in enumerate(native_proof['images'])]}
        manuals.append(metadata)
        path = out/metadata['text']
        link = lambda target: quote(relative(path,out/target))
        lines = [f'# {code} - {old["title"]}', '', f'Versie {version}; {old["pages"]} pagina(s); rol: {role}.', '',
                 metadata['status']+'.', '', f'[PDF]({link(pdf)}) | [Affinity]({link(native)}) | [Onderhoudsspecificatie]({link(metadata["specification"])})', '',
                 'De tekst hieronder is uit de huidige PDF gehaald en dient voor zoeken en vragen.',
                 'Controleer voor leesvolgorde, zichtbare knoppen en screenshotstatus ook de PDF.', '', '## Doel en behoud', '',
                 changes[code]['outcome'], '', 'Behouden ondersteuning: '+changes[code]['preserved'], '',
                 '## Bronnen en onderhoud', '',
                 f'[Register]({link("_Beheer/handleidingen.json")}) bevat volledige bronbeelden, native beeldkaders en exacte checksums.',
                 f'[IDML-herkomst]({link(idml)}) en [eerdere compositie]({link(layout_sources)}) zijn herkomst; de huidige .af is de bewerkingsmaster.',
                 'Historische absolute beeldpaden in deze bronnen zijn geen vereiste maplocatie.', '']
        for number, page_text in enumerate(pages,1):
            lines += [f'## Tekst pagina {number}', '', page_text, '']
        text(path, '\n'.join(lines)+'\n')
    register = {'schema_version':1,'set':'Handleidingen','prepared':'2026-11-12','manual_count':25,'pages':68,
                'editing_authority':'Saved native .af after human edits; update paired PDF and register together',
                'status':'Native working candidates; no automatic owner acceptance',
                'fonts':['Arial'],'image_resources':'All current native images are embedded at full pixel dimensions',
                'manuals':manuals}
    for original in (source/'Source-preparation-2026-11-04/assets').iterdir():
        if original.is_file():
            copy(original,Path('_Beheer/bronmateriaal/beelden')/original.name)
    dump(out/'_Beheer/handleidingen.json', register)
    text(out/'START HIER.html',create_index(manuals))
    finish_sources(out, source, copy, manuals)
    for name in ['LEESMIJ','OPEN-PUNTEN']:
        text(out/f'_Beheer/{name}.html', readable_html((out/f'_Beheer/{name}.md').read_text(encoding='utf-8')))
    sums = [f'{digest(path)}  {path.relative_to(out).as_posix()}' for path in sorted(out.rglob('*')) if path.is_file()]
    text(out/'_Beheer/controle/bestanden.sha256','\n'.join(sums)+'\n')
    print(json.dumps({'output':str(out),'manuals':len(manuals),'pages':68,'files':len(sums)+1}))


def readable_html(body):
    def inline(value):
        value = html.escape(value)
        value = re.sub(r'\[([^\]]+)\]\(([^)]+)\)',r'<a href="\2">\1</a>',value)
        value = re.sub(r'\*\*([^*]+)\*\*',r'<strong>\1</strong>',value)
        return re.sub(r'`([^`]+)`',r'<code>\1</code>',value)
    blocks = []
    for block in body.split('\n\n'):
        block = block.strip()
        if not block:
            continue
        if block.startswith('# '):
            blocks.append('<h1>'+inline(block[2:])+'</h1>')
        elif block.startswith('## '):
            blocks.append('<h2>'+inline(block[3:])+'</h2>')
        elif block.startswith('- '):
            items = re.split(r'\n(?=- )',block)
            blocks.append('<ul>'+''.join('<li>'+inline(item[2:].replace('\n',' '))+'</li>' for item in items)+'</ul>')
        else:
            blocks.append('<p>'+inline(block.replace('\n',' '))+'</p>')
    return '<!doctype html><html lang="nl"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Handleidingen - onderhoud</title><style>body{font:16px/1.7 Arial,sans-serif;color:#20332f;background:#f3f6f4;max-width:850px;margin:30px auto;padding:25px}h1{line-height:1.25}h2{margin-top:30px}a{color:#176844}code{background:#e6ede8;padding:2px 5px}li{margin-bottom:8px}</style><a href="../START%20HIER.html">Terug naar de handleidingen</a>'+''.join(blocks)+'</html>'


def finish_sources(out, source, copy, manuals):
    owning = REPO/'docs/manuals/operator-guides'
    mappings = {}
    for name in RULE_FILES:
        mappings[(owning/name).resolve()] = out/'_Beheer/regels'/name
    for path in (owning/'guides').glob('*.md'):
        mappings[path.resolve()] = out/'_Beheer/regels/handleidingen'/path.name
    for path in (owning/'reviews').glob('*.md'):
        if path.name != 'README.md':
            mappings[path.resolve()] = out/'_Beheer/regels/reviews'/path.name
    application_sources = []
    for name in ['v1-operational-role-capability-matrix.md','sequential-identifiers.md',
                 'component-hierarchy-operations.md','catalog-model-number-verification.md',
                 'label-printer-settings.md','workflow-migration-upgrade.md',
                 'releases/refurbisher-followups-production-2026-09-29.md',
                 'releases/workflow-catalog-search-production-2026-10-01.md']:
        original = (REPO/'docs'/name).resolve()
        if original.is_file():
            application_sources.append(original)
            mappings[original] = out/'_Beheer/bronmateriaal/toepassing'/name
    mappings[(owning/'registry.md').resolve()] = out/'_Beheer/regels/registry.md'
    for name in ['HANDOFF.md','README.md','inventory.md']:
        mappings[(owning/name).resolve()] = out/'_Beheer/LEESMIJ.md'
    mappings[(owning/'reviews/README.md').resolve()] = out/'_Beheer/LEESMIJ.md'
    mappings[(owning/'skill/SKILL.md').resolve()] = out/'_Beheer/skill/SKILL.md'
    unresolved = {}

    def rewrite(body, original, target):
        def replace(match):
            label, link = match.group(1), match.group(2)
            url = urlsplit(link.strip('<>'))
            if not url.path or (url.scheme and url.scheme != 'file') or url.netloc:
                return match.group(0)
            resolved = (original.parent/unquote(url.path)).resolve()
            if resolved in mappings:
                mapped = quote(relative(target,mappings[resolved]))
                if url.fragment:
                    mapped += '#'+url.fragment
            else:
                key = 'ref-'+hashlib.sha256(str(resolved).encode()).hexdigest()[:12]
                try:
                    original_name = resolved.relative_to(REPO).as_posix()
                except ValueError:
                    original_name = str(resolved)
                unresolved[key] = {'original':original_name,'reference_from':original.relative_to(REPO).as_posix(),
                                   'exists_in_working_tree':resolved.exists(),
                                   'status':'External to the active portable sources; use archive or named application repository when needed'}
                mapped = quote(relative(target,out/'_Beheer/bronmateriaal/context-links.md'))+'#'+key
            return f'[{label}]({mapped})'
        return re.sub(r'\[([^\]]+)\]\(([^\s)]+)\)',replace,body)

    original_files = [p for p in mappings if p.name in RULE_FILES or p.parent.name in ('guides','reviews')]+application_sources
    for original in original_files:
        if original.name == 'README.md':
            continue
        target = mappings[original]
        body = original.read_text(encoding='utf-8')
        prefix = '> Portable source copy. Relative links are mapped for this delivery.\n'
        if original.parent.name in ('guides','reviews'):
            prefix += '> Earlier version/conversion states are dated history. The current register selects\n> the delivered files; current scoped specifications/owner decisions still control content.\n'
        if original in application_sources:
            prefix += '> Dated application contract/release context, not a fresh runtime check.\n> Verify the named current application before changing or asserting deployed behavior.\n'
        text(target,prefix+'\n'+rewrite(body,original,target))
    index = ['# Current Manual Selection', '',
             'This register view replaces the copied historical generation-selection status.',
             'Exact file hashes, full image sources and remaining gaps are in [handleidingen.json](../handleidingen.json).',
             'All current versions are technically converted candidates; no new owner acceptance is inferred.', '',
             '| Code | Manual | Version | Role | Pages |', '| --- | --- | --- | --- | --- |']
    for manual in manuals:
        index.append(f'| [{manual["code"]}](../handleidingen/{manual["code"]}.md) | {manual["title"]} | {manual["version"]} | {manual["role"]} | {manual["pages"]} |')
    text(out/'_Beheer/regels/registry.md','\n'.join(index)+'\n')
    text(out/'_Beheer/skill/SKILL.md','''---
name: snipeit-operator-manuals
description: Read, review, revise and maintain the portable Snipe-IT refurbishment operator manuals, including editable Affinity delivery, using current specifications and owner feedback.
---

# Snipe-IT Operator Manuals

Locate the delivery root by AGENTS.md and _Beheer/handleidingen.json. Read that
entry point and _Beheer/LEESMIJ.md before work. No personal filesystem path,
specific model or original repository installation is assumed.

For a question, select the current manual by code/title, read its extracted text
under _Beheer/handleidingen and inspect the relevant PDF/images. For a revision,
read _Beheer/regels/authoring-method.md and the individual maintenance specification;
follow system.md precedence and current scoped owner decisions. Current file
selection lives in handleidingen.json, not historical proposal status headings.

Establish the reader outcome, relevance, navigation, actions versus alternatives
and recognizable completion before choosing screenshots or composing frames.
Compare useful concrete support with the baseline. Keep exact access-group names,
genuine Dutch evidence, actual A4 recognition size and calm ordinary checks.
Use focus only for a real search/distinction or an explicit local owner request.
Read the rendered result for plausible wrong readings; machine checks are partial.

Read [feedback cases](references/feedback-cases.md) for the applicable lesson and
its boundary. They do not prescribe universal mark/page/screenshot quotas.
Read [Affinity production](references/affinity-production.md) for proven native
operations and their limitations. Native masters own later human changes;
archived generation inputs cannot overwrite those edits automatically.

Keep artifact integrity, editorial interpretation, actual native editing and
operator/physical acceptance as separate evidence. Use the read-only delivery
checker and, for an IDML/PDF pair, ../hulpmiddelen/verify-guide-pair.py with
Python/pypdf and optional pypdfium2. Locate the current runtime; do not assume a
dated machine path. Inspect actual renders and native edits, not only report text.

On feedback, record symptom -> reader reason -> principle -> boundary. Correct
the affected reasoning and compare an earlier passing case with different needs.
Maintain normative rules in their owning sources, mechanics in tested procedures
and active state in the register/maintenance log. Skill presence grants no app,
account, runtime or computer-control permission.
''')
    for name in ['feedback-cases.md','affinity-production.md']:
        body = (owning/'skill/references'/name).read_text(encoding='utf-8')
        body = body.replace('../scripts/verify-guide-pair.py','../../hulpmiddelen/verify-guide-pair.py')
        body = body.replace('repository root','delivery root')
        body = body.replace('docs/manuals/operator-guides/guides/','_Beheer/regels/handleidingen/')
        body = body.replace('docs/manuals/operator-guides/','_Beheer/regels/').replace('`guides/','`_Beheer/regels/handleidingen/')
        prefix = '> Portable manual reference: begin at root AGENTS.md and _Beheer/LEESMIJ.md.\n> Repository procedure/session paths below are historical context; the current\n> register selects this delivery. Use _Beheer/regels for mapped specifications\n> and _Beheer/hulpmiddelen for the included checkers.\n\n'
        if name == 'affinity-production.md':
            prefix += '''## Embedded Image Delivery - 2026-11-12

The flat native masters use full native embedded ImageNodes within unchanged
crop parents. The tested SDK insertion builder expects a spread transform:
use the old image's baseToSpreadTransform, disable forced pixel alignment and
insert inside the existing crop frame. A local image transform alone moved the
new image in the first isolated probe; that probe was rejected. New definitions
use the old image's complete compatible bitmap, preserving every original pixel.
Node IDs are session-local: after reopening, verify the image sequence and its
dimensions/transforms/parent frames instead of treating IDs as persistent keys.

Compare decoded full image pixels against the separately retained original
files. An embedded PNG may encode differently from the original JPEG, so byte
hashes across those formats are not interchangeable. Preserve original byte
hashes separately. Native reopen must report Embedded for every image and retain
text/image/group/page counts; export and compare all page renders. This delivery
has those checks. Fresh-device/font and operator/physical trials remain separate.

'''
        text(out/'_Beheer/skill/references'/name,prefix+body)
    copy(owning/'skill/scripts/verify-guide-pair.py','_Beheer/hulpmiddelen/verify-guide-pair.py')
    for path in (source/'Source-preparation-2026-11-04/inputs/evidence-metadata').glob('*.json'):
        copy(path,Path('_Beheer/bronmateriaal/beeldherkomst')/path.name)
    for script in ['verify-embedded-manuals-2026-11-12.py','assemble-shared-manuals-2026-11-12.py']:
        copy(REPO/'scripts/manuals'/script,Path('_Beheer/hulpmiddelen/herkomst')/script)
    copy(REPO/'output/manuals/affinity-native-2026-11-10/runner/embed-shared-manuals.js','_Beheer/hulpmiddelen/herkomst/embed-shared-manuals.js')
    text(out/'_Beheer/hulpmiddelen/LEESMIJ.md','''# Controlehulpmiddelen

`controleer-map.py` gebruikt alleen de standaardbibliotheek van Python 3.10+
en controleert bestaande bestanden, geregistreerde hashes en startpaginalinks.
Het is alleen-lezen. Run vanaf de hoofdmap: `python _Beheer/hulpmiddelen/controleer-map.py`.

`verify-guide-pair.py --help` gebruikt pypdf en voor optionele renders pypdfium2.
Het vergelijkt een expliciet gekozen IDML/PDF-paar; het wijzigt geen master.
Dit bewijst geen leesvolgorde, screenshotherkenning of praktische bruikbaarheid.

De map herkomst bewaart de daadwerkelijk gebruikte productie-/controlescripts.
Die bevatten de oorspronkelijke invoerlocaties en zijn geen opdracht om een
installatie op die paden te maken. Lees de SDK-versie/parameters en pas een
gecontroleerde nieuwe proef toe wanneer een andere omgeving dat vereist.
Affinifty-scriptuitvoering gebeurt via de eigen Script Editor; lees eerst de
referentie in ../skill/references/affinity-production.md. Exporteer niet over
handmatige wijzigingen. Deze map bevat geen persoonlijke sleutels of login.
'''.replace('Affinifty','Affinity'))
    lines = ['# Contextlinks en bronbeschikbaarheid', '',
             'Actieve navigatie gebruikt relatieve paden. De bronnen hieronder verwijzen',
             'naar historisch of toepassingsmateriaal buiten de vlakke huidige set.',
             'De volledige eerdere technische overdracht staat in',
             '[archief](../archief/Technische-overdracht-2026-11-12.zip). Bestanden uit de',
             'oorspronkelijke applicatierepository zijn niet allemaal meegeleverd. Gebruik',
             'de exacte bron wanneer een taak daarvan afhangt; vul ontbrekende feiten niet in.', '']
    for key, record in sorted(unresolved.items()):
        lines += [f'<a id="{key}"></a>', f'## {key}', '', '`'+record['original']+'`', '',
                  'Verwijzing uit: `'+record['reference_from']+'`.', '',
                  'Niet als actieve losse bron gekopieerd. Raadpleeg het archief of de genoemde applicatierepository.', '']
    text(out/'_Beheer/bronmateriaal/context-links.md','\n'.join(lines)+'\n')
    dump(out/'_Beheer/bronmateriaal/context-links.json',unresolved)
    text(out/'_Beheer/ONDERHOUD.md','''# Onderhoudslog

## 2026-11-12 - Gedeelde Handleidingen-map

- 25 huidige handleidingen, 68 pagina's, beschrijvende gelijke PDF/.af-namen.
- Volledige beeldpixels in de native masters ingebed; losse bronbestanden behouden.
- De 25 afdruk-PDFs zijn byte voor byte behouden. Nieuwe native proefexports zijn
  op 67 pagina's pixel-identiek; een kleine AC-01-telefoonbeeldvariatie is afzonderlijk gemeten.
- AGENTS.md, handleidingen.json en doorzoekbare tekst maken de set navigeerbaar
  voor onbekende modellen. Canonieke regels/specs en historische context zijn gescheiden.
- Geen inhoudelijke versieverhoging: de wijziging betreft bestandsverpakking en
  niet-zichtbare beeldopslag. Geen automatische eigenaarsacceptatie.

Voeg een nieuwe gedateerde sectie toe bij een wijziging en vermeld de exacte
handleidingversie, reden, betrokken bestanden, vergelijking, controles en open punten.
''')


if __name__ == '__main__':
    main()
