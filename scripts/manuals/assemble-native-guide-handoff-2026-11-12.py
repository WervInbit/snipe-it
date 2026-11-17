"""Assemble selected native guides and filtered resources in a fresh review folder."""
import hashlib
import html
import json
from pathlib import Path
import shutil
from urllib.parse import quote

ROOT = Path(__file__).resolve().parents[2]
SESSION = ROOT / 'output/manuals/affinity-native-2026-11-10'
FROZEN = ROOT / 'output/manuals/final-guide-set-2026-11-04'
OUT = ROOT / 'output/manuals/affinity-guide-handoff-2026-11-12'


def digest(path):
    return hashlib.sha256(path.read_bytes()).hexdigest()


def copy(source, relative):
    target = OUT / relative
    target.parent.mkdir(parents=True, exist_ok=True)
    shutil.copy2(source, target)
    return relative.as_posix() if isinstance(relative, Path) else relative


def main():
    if OUT.exists():
        raise RuntimeError('Existing handoff preserved; use a separate revision')
    records = [json.loads(p.read_text()) for p in sorted((SESSION / 'converted').glob('*.json'))]
    assert len(records) == 25 and len({r['code'] for r in records}) == 25
    audit = json.loads((SESSION / 'qa/native-artifact-audit.json').read_text())
    audited = {g['code']: g for g in audit['results']}
    assert audit['converted_guides'] == 25 and audit['pages'] == 68
    OUT.mkdir()
    shutil.copytree(FROZEN, OUT / 'Source-preparation-2026-11-04')
    shutil.copytree(ROOT / 'docs/manuals/operator-guides', OUT / 'Authoring',
                    ignore=shutil.ignore_patterns('__pycache__', '*.pyc'))
    shutil.copytree(ROOT / 'docs/manuals/operator-guides/skill', OUT / 'Skill/snipeit-operator-guides',
                    ignore=shutil.ignore_patterns('__pycache__', '*.pyc'))
    changes = json.loads((FROZEN / 'review/changes.json').read_text())
    by_code = {g['code']: g for g in changes}
    guides = []
    for record in records:
        code, version = record['code'], record['version']
        result = audited[code]
        assert result['version'] == version and result['pdf_sha256'] == digest(Path(record['pdf']))
        assert result['input_hash_matches'] and result['native_import_reopen_counts_match'] and result['a4_pages']
        assert not result['source_text_parts_not_found'] and not result['conversion_warnings']
        base = f'{code}-v{version}'
        native = copy(Path(record['native']), Path('Affinity') / f'{base}.af')
        pdf = copy(Path(record['pdf']), Path('PDF') / f'{base}.pdf')
        package_source = Path(record['package']).parent
        package_dir = Path('Packages') / base
        package = copy(Path(record['package']), package_dir / Path(record['package']).name)
        images = []
        image_dir = package_source / 'Images'
        if image_dir.exists():
            for image in sorted(image_dir.iterdir()):
                if image.is_file():
                    relative = copy(image, package_dir / 'Images' / image.name)
                    images.append({'file': relative, 'sha256': digest(image)})
        idml_source = Path(record['inputIdml']) if record.get('inputIdml') else SESSION / 'idml-inputs/idml' / f'{base}.idml'
        idml = copy(idml_source, Path('IDML-import-inputs') / idml_source.name)
        report = copy(Path(record['report']), Path('Evidence/conversion-reports') / f'{base}.json')
        baseline_version = by_code[code]['baselineVersion']
        baseline = f'Source-preparation-2026-11-04/inputs/baselines/{code}-v{baseline_version}.pdf'
        assert (OUT / baseline).is_file()
        previous_native_pdf = None
        if record.get('replaces'):
            previous = json.loads(Path(record['replaces']).read_text())
            previous_native_pdf = copy(Path(previous['pdf']), Path('Previous-native') / f"{code}-v{previous['version']}-before-correction.pdf")
        renders = []
        for number in range(1, result['pages'] + 1):
            renders.append(copy(SESSION / f'qa/renders/{code}/poppler-{number}.png', Path('Previews') / f'{base}-{number}.png'))
        guides.append({'code': code, 'title': by_code[code]['title'], 'version': version,
                       'pages': result['pages'], 'native': native, 'pdf': pdf, 'package': package,
                       'idml': idml, 'input_sha256': digest(idml_source), 'baseline_pdf': baseline,
                       'previous_native_pdf': previous_native_pdf,
                       'previews': renders, 'images': images, 'conversion_report': report,
                       'native_counts': result['native_counts'], 'native_sha256': digest(OUT / native),
                       'pdf_sha256': digest(OUT / pdf)})
    for source_name, target_name in [('Guide-basis-v2.af', 'Guide-basis.af'), ('Guide-basis-v2.idml', 'Guide-basis.idml'),
                                    ('Guide-basis-v2-preview.pdf', 'Guide-basis-preview.pdf'),
                                    ('native-template-proof-v2.json', 'native-template-proof.json'), ('styles.json', 'styles.json')]:
        copy(SESSION / 'template' / source_name, Path('Template') / target_name)
    for name in ['native-artifact-audit.json', 'visual-review.json']:
        copy(SESSION / 'qa' / name, Path('Evidence') / name)
    manifest = {'guides': guides, 'pages': sum(g['pages'] for g in guides),
                'status': 'Editable native working candidates; not owner-accepted release',
                'packaging': 'Native files, native-derived PDFs and image packages; copied font files excluded',
                'relocation_verification': 'Pending representative native package reopen in this assembled folder'}
    (OUT / 'manifest.json').write_text(json.dumps(manifest, indent=2) + '\n')
    (OUT / 'EDITING.md').write_text('''# Bewerken in Affinity

Open een bestand uit Affinity. Bij verplaatsen naar een andere computer:
kopieer de hele map van de betreffende gids uit Packages en open het
.afpackage-bestand. Bewaar vervolgens een eigen .af-kopie. Laat Images naast
het pakket staan. Arial moet beschikbaar zijn; lettertypebestanden zijn niet
meegeleverd.

Tekst, stijlen, markeringen, schema's en lokale groepen zijn afzonderlijk
bewerkbaar. Screenshots blijven rasterbeelden met hun volledige bronpixels.
Selecteer het beeld binnen het kader en gebruik Replace image om te vervangen;
pas daarna de uitsnede en eventuele markeringen aan. Een andere uitsnede volgt
markeringen niet automatisch. Tekst loopt binnen het eigen kader door; de hele
pagina herschikt zich niet automatisch.

De kop en linker voettekst staan op master A-Basis. Benoemde tekststijlen
beschrijven hun rol, grootte, gewicht en kleur. Template/Guide-basis.af is een
A4-startbestand met dezelfde master/stijlen en een lege beeldplaats. Kies per
taak een passende indeling; de voorbeeldstap is geen verplicht paginapatroon.

Exporteer na wijzigingen vanuit de opgeslagen .af naar PDF/X-3:2003, alle
bedoelde A4-pagina's. Controleer overlopende tekst, lettertypen, beeldkaders,
markeringen en de volledige nieuwe PDF. Print op A4 op100%, zonder passend
schalen; voor tweezijdige taakgidsen omslaan langs de lange zijde.

De native bestanden worden de masters voor handmatige wijzigingen. Overschrijf
ze niet met een nieuwe generatie zonder die wijzigingen eerst te vergelijken.
IDML-import-inputs en Source-preparation bewaren de herkomst. Hun historische
absolute beeldpaden zijn geen zelfstandige verhuisprocedure; gebruik Packages.

De set is technisch omgezet en redactioneel beoordeeld, maar niet automatisch
door de eigenaar geaccepteerd. Zie OPEN-POINTS.md voor toepassings- en praktijkgaten.
''', encoding='utf-8')
    (OUT / 'OPEN-POINTS.md').write_text('''# Open punten voor vrijgave

- USR-02: de toepassing kort de geselecteerde statuswaarden af. Nieuwe geopende
  keuze-/UI-evidence tegen de gekozen release is nodig; groter uitsnijden van
  dezelfde pixels herstelt die tekst niet.
- AST-03: optionele registratienotities zijn de eigenaarskeuze; het eerder
  vastgelegde verschil met de gedeployde validator vereist controle tegen de
  daadwerkelijke release.
- Workflow-/QA-/statusnamen, fysieke overdracht en bronregistratiebeleid houden
  hun bestaande eigenaars- en releasebeslissingen. Deze conversie wijzigt ze niet.
- Een nieuwe gebruiker, echte A4-afdruk, fysiek label/scan en een tweede computer
  blijven praktijkproeven. Een bewerkingsproef of tekstaudit bewijst die niet.
- Bewerkings- en verhuisproeven zijn representatieve steekproeven, geen bewijs
  van iedere mogelijke Affinity-bewerking of elke toekomstige applicatieversie.
''', encoding='utf-8')
    extra = {'CAT-04': 'v8: Add Expected Subcomponent en Add Attribute Contribution staan op hun eerste gebruik; bestaande veldbeelden blijven even groot.',
             'CAT-06': 'v4: alleen het bronadreskader is ruimer; de volledige URL blijft zichtbaar zonder kleiner lettertype.',
             'USR-04': 'v9: bijschrift beschrijft het daadwerkelijk uitgevinkte loginvakje; beelden en indeling blijven gelijk.'}
    lines = ['# Wijzigingen en behoud', '', 'Alle25 gidsen zijn naar native Affinity/PDF omgezet. Dit is geen automatische acceptatie.', '',
             '## Aanvullingen op12 november', '']
    lines.extend(f'- {code}: {text}' for code, text in extra.items())
    for guide in guides:
        old = by_code[guide['code']]
        lines += ['', f"## {guide['code']} - {guide['title']} - v{guide['version']}", '', old['outcome'], '']
        lines += ['- ' + text for text in old['changes']]
        if guide['code'] in extra:
            lines += ['- ' + extra[guide['code']]]
        lines += ['', 'Behouden: ' + old['preserved'], '', 'Afweging: ' + old['tradeoff']]
    (OUT / 'CHANGES.md').write_text('\n'.join(lines) + '\n', encoding='utf-8')
    cards = []
    for guide in guides:
        def link(path, text):
            return f'<a href="{quote(path, safe="/#")}">{html.escape(text)}</a>'
        links = ' | '.join([link(guide['pdf'], 'PDF'), link(guide['native'], 'Affinity'),
                            link(guide['package'], 'Pakket met beelden'), link(guide['baseline_pdf'], 'Vorige PDF')])
        if guide.get('previous_native_pdf'):
            links += ' | ' + link(guide['previous_native_pdf'], 'Voor deze correctie')
        details = html.escape(extra.get(guide['code'], 'Gekozen inhoud behouden; native tekst, beelden, groepen en stijlen.'))
        previews = ''.join(f'<a href="{quote(guide["pdf"])}"><img loading="lazy" src="{quote(p)}" alt="{guide["code"]} pagina{n}"></a>'
                           for n, p in enumerate(guide['previews'], 1))
        cards.append(f'<article id="{guide["code"]}"><h2>{guide["code"]} {html.escape(guide["title"])} <small>v{guide["version"]} - {guide["pages"]} A4</small></h2><p>{details}</p><p>{links}</p><div class="pages">{previews}</div></article>')
    document = '''<!doctype html><html lang="nl"><meta charset="utf-8"><title>Affinity handleidingen -25 gidsen</title>
<style>body{font:16px/1.5 system-ui;color:#172335;background:#edf1f5;margin:0}main{max-width:1200px;margin:auto;padding:24px}header,article{background:white;border:1px solid #cbd5e1;border-radius:8px;padding:22px;margin-bottom:24px}h1,h2{line-height:1.2}small{font-size:16px;color:#53657a}a{color:#23569d}.pages{display:flex;gap:16px;overflow:auto}.pages a{flex:0 0 420px}.pages img{display:block;width:100%;border:1px solid #cbd5e1}input{font:inherit;padding:10px;width:min(80%,500px)}.note{background:#fff7dd;padding:12px}</style>
<main><header><h1>25 bewerkbare gidsen -68 A4-pagina's</h1><p class="note">Werkversies voor bewerken en beoordeling. Technische conversie is geen automatische acceptatie.</p><p><a href="CHANGES.md">Alle wijzigingen</a> | <a href="EDITING.md">Bewerken en afdrukken</a> | <a href="OPEN-POINTS.md">Open punten</a> | <a href="Template/Guide-basis.af">Native basisbestand</a></p><label>Zoek een gids <input id="search" placeholder="Code of titel"></label></header>'''
    document += ''.join(cards) + '''</main><script>document.getElementById('search').addEventListener('input',e=>{let q=e.target.value.toLowerCase();document.querySelectorAll('article').forEach(a=>a.hidden=!a.textContent.toLowerCase().includes(q))})</script></html>'''
    (OUT / 'REVIEW.html').write_text(document, encoding='utf-8')
    print(json.dumps({'directory': str(OUT), 'guides': len(guides), 'pages': manifest['pages'],
                      'fonts_copied': list(str(p) for p in (OUT / 'Packages').rglob('Fonts'))}))


if __name__ == '__main__':
    main()
