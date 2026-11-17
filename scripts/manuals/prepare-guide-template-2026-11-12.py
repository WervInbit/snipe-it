"""Prepare an editable A4 scaffold from the verified common guide structure."""
import copy
import argparse
import json
from pathlib import Path
import xml.etree.ElementTree as ET
import zipfile

ROOT = Path(__file__).resolve().parents[2]
SESSION = ROOT / 'output/manuals/affinity-native-2026-11-10'
OUT = SESSION / 'template'
NS = 'http://ns.adobe.com/AdobeInDesign/idml/1.0/packaging'
ET.register_namespace('idPkg', NS)


def xml(root):
    return ET.tostring(root, encoding='utf-8', xml_declaration=True)


def main():
    OUT.mkdir(exist_ok=True)
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--name', default='Guide-basis', choices=['Guide-basis', 'Guide-basis-v2'])
    args = parser.parse_args()
    target = OUT / (args.name + '.idml')
    if target.exists():
        raise RuntimeError('Existing template source preserved')
    with zipfile.ZipFile(SESSION / 'idml-inputs/idml/AC-02-v8.idml') as archive:
        parts = {n: archive.read(n) for n in archive.namelist()}
    root = ET.fromstring(parts['Spreads/Spread_1.xml'])
    spread = next(root.iter('Spread'))
    for child in list(spread):
        if child.tag == 'Group' and child.get('Name') not in ('Context', 'Stap 1', 'Voet'):
            spread.remove(child)
    step = next(n for n in root.iter('Group') if n.get('Name') == 'Stap 1')
    children = list(step)
    frame = copy.deepcopy(next(n for n in step.iter('Rectangle') if list(n.iter('Image'))))
    for child in list(frame):
        if child.tag == 'Image':
            frame.remove(child)
    frame.set('ContentType', 'Unassigned')
    frame.set('Name', 'Beeldplaats - voeg eigen herkenbaar beeld toe')
    frame.set('FillColor', 'Color/RGB_248_250_252')
    frame.set('StrokeColor', 'Color/RGB_203_213_225')
    frame.set('StrokeWeight', '1.062992')
    for child in children[5:]:
        step.remove(child)
    step.append(frame)
    role = next(n for n in root.iter('TextFrame') if n.get('Name') == 'Iedereen')
    for point in role.iter('PathPointType'):
        for key in ('Anchor', 'LeftDirection', 'RightDirection'):
            x, y = map(float, point.get(key).split())
            if x > 0:
                point.set(key, f'{52 * 72 / 25.4:.6f} {y:.6f}')
    parts['Spreads/Spread_1.xml'] = xml(root)
    master = ET.fromstring(parts['MasterSpreads/MasterSpread_master.xml'])
    footer = next(n for n in master.iter('TextFrame') if n.get('Name', '').startswith('Inbit Snipe-IT |'))
    for point in footer.iter('PathPointType'):
        for key in ('Anchor', 'LeftDirection', 'RightDirection'):
            x, y = map(float, point.get(key).split())
            if x > 0:
                point.set(key, f'{145 * 72 / 25.4:.6f} {y:.6f}')
    parts['MasterSpreads/MasterSpread_master.xml'] = xml(master)
    replacements = {
        'AC-02 Eigen wachtwoord wijzigen': 'CODE-00 Titel van de handleiding',
        'Wijzig het wachtwoord van je lokale account en controleer de opslag.': 'Beschrijf het resultaat dat de lezer met deze handleiding bereikt.',
        'Versie 8': 'Versie 1', '04-11-2026 | 1/1': '12-11-2026 | 1/1',
        'Inbit Snipe-IT | AC-02 v8 | 04-11-2026': 'Inbit Snipe-IT | CODE-00 v1 | 12-11-2026',
        'Iedereen': 'Refurbisher', 'Huidig of tijdelijk wachtwoord': 'Apparaat + account',
        'Open je accountmenu': 'Beschrijf de handeling',
        'Klik rechtsboven op het profielicoon.': 'Begin bij het herkenbare startscherm.',
        'Kies daarna Wachtwoord wijzigen (1A).': 'Noem het menu, de knop en de handeling.',
        'Je opent het formulier van je eigen account.': 'Leg uit welk zichtbaar resultaat volgt.',
        'De wijziging is opgeslagen; alleen jij kent je eigen wachtwoord.': 'Beschrijf het zichtbare resultaat waarmee de taak klaar is.',
    }
    referenced = set()
    for name in ['Spreads/Spread_1.xml', 'MasterSpreads/MasterSpread_master.xml']:
        doc = ET.fromstring(parts[name])
        referenced.update(n.get('ParentStory') for n in doc.iter('TextFrame'))
    for name in list(parts):
        if name.startswith('Stories/') and name.endswith('.xml'):
            doc = ET.fromstring(parts[name])
            if next(doc.iter('Story')).get('Self') not in referenced:
                del parts[name]
                continue
            for n in doc.iter('Content'):
                n.text = replacements.get(n.text, n.text)
            parts[name] = xml(doc)
    design = ET.fromstring(parts['designmap.xml'])
    for child in list(design):
        if child.tag == f'{{{NS}}}Story' and child.get('src') not in parts:
            design.remove(child)
    parts['designmap.xml'] = xml(design)
    with zipfile.ZipFile(target, 'w', zipfile.ZIP_DEFLATED) as archive:
        archive.writestr('mimetype', parts.pop('mimetype'), compress_type=zipfile.ZIP_STORED)
        for name, value in parts.items():
            archive.writestr(name, value)
    styles = ET.fromstring(parts['Resources/Styles.xml'])
    inventory = [{'name': n.get('Name'), 'points': n.get('PointSize'), 'leading': n.get('Leading'), 'face': n.get('FontStyle')}
                 for n in styles.iter('ParagraphStyle') if n.get('PointSize')]
    (OUT / 'styles.json').write_text(json.dumps({'font': 'Arial', 'styles': inventory}, indent=2) + '\n')
    print(json.dumps({'template': str(target), 'referenced_stories': len(referenced), 'styles': len(inventory)}))


if __name__ == '__main__':
    main()
