"""Write a separate native-compatibility IDML set using captured resolved colours."""
from pathlib import Path
import argparse
import hashlib
import json
import math
import re
import sys
import xml.etree.ElementTree as ET
import zipfile

ROOT = Path(__file__).resolve().parents[2]
SOURCE = ROOT / 'output/manuals/final-guide-set-2026-11-04'
OUT = ROOT / 'output/manuals/affinity-idml-compatibility-2026-11-05'
parser = argparse.ArgumentParser()
parser.add_argument('guide', nargs='?')
parser.add_argument('--output-directory', type=Path)
parser.add_argument('--layout-compatibility', action='store_true')
parser.add_argument('--baseline-compatibility', action='store_true')
parser.add_argument('--editing-defaults', action='store_true')
args = parser.parse_args()
if args.editing_defaults:
    assert args.layout_compatibility and args.baseline_compatibility, 'Use the corrected layout/baseline with editing defaults.'
if args.output_directory:
    OUT = args.output_directory.resolve()
    OUT.relative_to(ROOT / 'output/manuals')
assert OUT != SOURCE, 'Prepared source must remain unchanged.'
assert not OUT.with_suffix('.zip').exists(), 'Packaged proposal: use a fresh output directory.'
assert not (OUT / 'manifest.sha256').exists(), 'Sealed release: create another version.'
NS = 'http://ns.adobe.com/AdobeInDesign/idml/1.0/packaging'
ET.register_namespace('idPkg', NS)
PT = 72 / 25.4
DOM = '16.0'
assets = json.loads((SOURCE / 'source/assets.json').read_text(encoding='utf-8'))
inventory = json.loads((SOURCE / 'source/inventory.json').read_text(encoding='utf-8'))
(OUT / 'idml').mkdir(parents=True, exist_ok=True)
(OUT / 'qa').mkdir(parents=True, exist_ok=True)

def node(parent, tag, **attrs):
    return ET.SubElement(parent, tag, {k: str(v) for k, v in attrs.items()})

def number(v):
    return f'{float(v):.6f}'.rstrip('0').rstrip('.') or '0'

def point(x, y):
    return f'{number(x)} {number(y)}'

def package(kind):
    return ET.Element(f'{{{NS}}}{kind}', {'DOMVersion': DOM})

def xml(root):
    return b'<?xml version="1.0" encoding="UTF-8" standalone="yes"?>\n' + ET.tostring(root, encoding='utf-8')

def geometry(parent, points, opened=False):
    props = next((e for e in parent if e.tag == 'Properties'), None)
    if props is None:
        props = node(parent, 'Properties')
    pg = node(node(props, 'PathGeometry'), 'GeometryPathType', PathOpen=str(opened).lower())
    arr = node(pg, 'PathPointArray')
    for p in points:
        anchor, left, right = p if len(p) == 3 and isinstance(p[0], tuple) else (p, p, p)
        node(arr, 'PathPointType', Anchor=point(*anchor), LeftDirection=point(*left), RightDirection=point(*right))

def rectangle(parent, tag, identity, x, y, w, h, **attrs):
    item = node(parent, tag, Self=identity, ItemTransform=f'1 0 0 1 {number(x)} {number(y)}',
                FillColor='Swatch/None', StrokeColor='Swatch/None', StrokeWeight='0',
                ItemLayer='layer_content', **attrs)
    geometry(item, [(0, 0), (0, h), (w, h), (w, 0)])
    return item

def rgb(value):
    if not value or value in ('none', 'transparent'):
        return None
    if value.startswith('#'):
        text = value[1:]
        if len(text) == 3:
            text = ''.join(c * 2 for c in text)
        return tuple(int(text[i:i + 2], 16) for i in (0, 2, 4))
    if not re.fullmatch(r'rgba?\([\d., %]+\)', value):
        raise ValueError('Unresolved/unsupported colour: ' + repr(value))
    vals = re.findall(r'[\d.]+', value)
    if len(vals) < 3:
        raise ValueError('Incomplete RGB colour: ' + repr(value))
    return tuple(round(float(n)) for n in vals[:3])

def paragraphs(lines):
    """Join author-wrapped lines while keeping actual sentences/choices separate."""
    result, current = [], ''
    for raw in lines:
        text = raw['text'].strip()
        if not text:
            continue
        current = f'{current} {text}'.strip()
        if re.search(r'[.!?:;]$|https?://\S+$', text):
            result.append(current)
            current = ''
    if current:
        result.append(current)
    return result or ['']

def build(doc):
    parts, colors, styles, story_ids, hyperlinks = {}, {}, {}, [], []
    def color(value):
        c = rgb(value)
        if c is None:
            return 'Swatch/None'
        identity = 'Color/RGB_' + '_'.join(str(v) for v in c)
        colors[identity] = c
        return identity

    def style_for(e):
        a, b = e['attrs'], e['bounds']
        size = float(a.get('font-size', e['style']['fontSize'].removesuffix('px')))
        weight = int(a.get('font-weight', e['style']['fontWeight']))
        fill = color(e['style']['fill'])
        family = 'Arial'
        face = 'Bold' if weight >= 600 else 'Regular'
        group_components = [g.get('component') for g in e.get('groups', [])]
        if a.get('data-role') == 'badge-label':
            name = 'Stapnummer' if 'step-badge' in group_components else 'Beeldlabel'
        elif a.get('data-role') in ('guide-label', 'family-label'):
            name = 'Verwijzing'
        elif b['y'] < 32 and size > 4:
            name = 'Pagina titel'
        elif b['y'] > 292:
            name = 'Voettekst'
        elif b['y'] < 34:
            name = 'Doel en versie'
        elif a.get('data-component', '').startswith('context') or 36 <= b['y'] < 56:
            name = 'Context'
        elif size >= 3.3 and weight >= 600:
            name = 'Stap of sectie titel'
        elif size <= 2.25:
            name = 'Bijschrift'
        else:
            name = 'Kerntekst'
        # Variants preserve deliberate density while remaining reusable styles.
        leading = float(e['lines'][1].get('dy') or size * 1.35) if len(e['lines']) > 1 else size * 1.35
        anchor = a.get('text-anchor', e['style'].get('textAnchor', 'start'))
        align = {'middle': 'CenterAlign', 'end': 'RightAlign'}.get(anchor, 'LeftAlign')
        color_names = {'Color/RGB_16_32_51': 'Inkt', 'Color/RGB_83_101_122': 'Gedempt', 'Swatch/None': 'Geen'}
        variant = f'{name} | {size * PT:.2f} pt | {"Vet" if face == "Bold" else "Normaal"} | {color_names.get(fill, fill.rsplit("/", 1)[-1])} | {align}'
        identity = 'ParagraphStyle/' + variant
        styles[identity] = {'name': variant, 'size': size * PT, 'face': face, 'fill': fill,
                            'leading': leading * PT, 'align': align, 'family': family}
        return identity, size, leading

    def signature(e):
        attrs = {k: v for k, v in e['attrs'].items() if k not in ('id', 'data-scene-id')}
        return json.dumps([e['tag'], attrs, e.get('lines'), e['matrix']], sort_keys=True)

    def master_eligible(e):
        a = e['attrs']
        page_background = e['tag'] == 'rect' and all(
            abs(float(a.get(k, 0)) - v) < .001
            for k, v in [('x', 0), ('y', 0), ('width', 210), ('height', 297)])
        # Page backgrounds must not cover inherited master headers.
        return page_background or a.get('data-component') == 'page-footer-left' or any(
            g.get('component') == 'page-header' for g in e.get('groups', []))

    common = set.intersection(*[{signature(e) for e in p['elements'] if master_eligible(e)} for p in doc['pages']])
    master_elements = [e for e in doc['pages'][0]['elements'] if master_eligible(e) and signature(e) in common]
    jobs = [{'number': 'master', 'elements': master_elements}] + doc['pages']
    layer_names = {'header': 'Kop', 'context': 'Context', 'body': 'Inhoud', 'footer': 'Voet'}
    figure_count = 0
    for page in jobs:
        pnum = page['number']
        is_master = pnum == 'master'
        root = package('MasterSpread' if is_master else 'Spread')
        spread = node(root, 'MasterSpread' if is_master else 'Spread', Self='master' if is_master else f'spread_{pnum}', PageCount='1', BindingLocation='0',
                      ShowMasterItems='true', AllowPageShuffle='false', ItemTransform='1 0 0 1 0 0')
        if is_master:
            spread.set('Name', 'A-Basis'); spread.set('NamePrefix', 'A'); spread.set('BaseName', 'Basis')
        node(spread, 'Page', Self=f'page_{pnum}', Name='A' if is_master else str(pnum), AppliedMaster='n' if is_master else 'master',
             ItemTransform='1 0 0 1 0 0', GeometricBounds=f'0 0 {number(297 * PT)} {number(210 * PT)}',
             MasterPageTransform='1 0 0 1 0 0')
        frames = [e for e in page['elements'] if e['attrs'].get('data-component') in ('task-step', 'step-frame') and e['tag'] == 'rect']
        groups = {}
        def parent_for(e):
            b = e.get('clip', e['bounds']) if e['tag'] == 'image' else e['bounds']
            y = b['y'] + e['matrix'][5]
            components = e.get('groups', [])
            task = next((g for g in components if g.get('component') == 'task-step'), None)
            diagram = next((g for g in components if g.get('component') == 'relationship-diagram'), None)
            if task:
                n = task.get('attrs', {}).get('data-number', task.get('id', ''))
                key, name = 'task_' + str(n), 'Stap ' + str(n)
            elif diagram:
                key, name = 'diagram', 'Relatiediagram - losse tekst en verbindingen'
            elif y < 34:
                key, name = 'header', layer_names['header']
            elif y < 57:
                key, name = 'context', layer_names['context']
            elif y > 273:
                key, name = 'footer', layer_names['footer']
            else:
                fr = next((f for f in frames if float(f['attrs']['y']) - 4 <= y
                           < float(f['attrs']['y']) + float(f['attrs']['height'])), None)
                key = 'step_' + fr['id'] if fr else 'body'
                name = 'Stap ' + fr['attrs'].get('data-number', str(frames.index(fr) + 1)) if fr else 'Inhoud en schema'
            if key not in groups:
                groups[key] = node(spread, 'Group', Self=f'p{pnum}_{key}', Name=name,
                                   ItemTransform='1 0 0 1 0 0', ItemLayer='layer_content')
            parent = groups[key]
            fig = next((g for g in components if g.get('component') == 'figure'), None)
            if fig:
                fa = fig.get('attrs', {})
                fk = key + '_' + fa.get('data-source-key', '') + '_' + fa.get('data-label', '')
                if fk not in groups:
                    groups[fk] = node(parent, 'Group', Self=f'p{pnum}_{fk}', Name='Beeld ' + fa.get('data-label', '') + ' - ' + fa.get('data-source-key', ''),
                                      ItemTransform='1 0 0 1 0 0', ItemLayer='layer_content')
                parent = groups[fk]
            return parent

        for e in page['elements']:
            if not is_master and master_eligible(e) and signature(e) in common:
                continue
            a, tag, ident = e['attrs'], e['tag'], f'p{pnum}_{e["id"]}'
            parent = parent_for(e)
            m = e.get('matrix', [1, 0, 0, 1, 0, 0])
            if any(abs(v - t) > 1e-8 for v, t in zip(m, [1, 0, 0, 1, 0, 0])):
                parent = node(parent, 'Group', Self=ident + '_transform', Name='Bewaarde plaatsing', ItemLayer='layer_content',
                              ItemTransform=' '.join(number(v * PT if i >= 4 else v) for i, v in enumerate(m)))
            if tag == 'text':
                style_id, size, leading = style_for(e)
                story = 'story_' + ident
                story_ids.append(story)
                sr = package('Story')
                st = node(sr, 'Story', Self=story, AppliedTOCStyle='n', TrackChanges='false')
                for para in paragraphs(e['lines']):
                    ps = node(st, 'ParagraphStyleRange', AppliedParagraphStyle=style_id)
                    cs = node(ps, 'CharacterStyleRange', AppliedCharacterStyle='CharacterStyle/$ID/[No character style]')
                    if e.get('hyperlink'):
                        link_id = 'hyperlink_' + ident + '_' + str(len(hyperlinks))
                        source = node(cs, 'HyperlinkTextSource', Self=link_id + '_source', Name=para, Hidden='false', AppliedCharacterStyle='n')
                        node(source, 'Content').text = para
                        hyperlinks.append({'id': link_id, 'url': e['hyperlink'], 'text': para})
                    else:
                        node(cs, 'Content').text = para
                    node(cs, 'Br')
                parts[f'Stories/Story_{story}.xml'] = xml(sr)
                b = e['bounds']
                # Keep the observed glyph origin; leave small edit/metric tolerance.
                x, y = b['x'] * PT, b['y'] * PT
                w = max((b['w'] + .75) * PT, size * PT * 1.5)
                h = max((b['h'] + 1.0) * PT, (size * 1.5) * PT)
                if args.layout_compatibility:
                    anchor = a.get('text-anchor', e['style'].get('textAnchor', 'start'))
                    x = float(a.get('x', b['x'])) * PT
                    x -= w / 2 if anchor == 'middle' else w if anchor == 'end' else 0
                if args.baseline_compatibility:
                    assert args.layout_compatibility
                    # Arial OS/2 capHeight is 1467/2048 em. CapHeight is a
                    # deterministic first baseline, unlike imported AscentOffset.
                    cap_height = size * 1467 / 2048
                    baseline = float(a['y'])
                    if a.get('dominant-baseline') in ('central', 'middle'):
                        baseline += cap_height / 2
                    y = (baseline - cap_height) * PT
                tf = rectangle(parent, 'TextFrame', ident, x, y, w, h,
                               ParentStory=story, PreviousTextFrame='n', NextTextFrame='n', ContentType='TextType',
                               AppliedObjectStyle='ObjectStyle/$ID/[None]', Name=' '.join(e['text'].split())[:90])
                pref = node(tf, 'TextFramePreference', TextColumnCount='1', TextColumnGutter='0',
                            FirstBaselineOffset='CapHeight' if args.baseline_compatibility else 'AscentOffset' if args.layout_compatibility else 'FixedHeight',
                            MinimumFirstBaselineOffset='0' if args.layout_compatibility else number(e.get('fontMetrics', {}).get('ascent', size * .91) * PT),
                            VerticalJustification='TopAlign', IgnoreWrap='true')
                pp = node(pref, 'Properties')
                inset = node(pp, 'InsetSpacing', type='list')
                for _ in range(4):
                    node(inset, 'ListItem', type='unit').text = '0'
                continue
            if tag == 'image':
                b = e.get('clip') or {k: float(a[k]) for k in ('x', 'y')} | {'w': float(a['width']), 'h': float(a['height'])}
                fr = rectangle(parent, 'Rectangle', ident, b['x'] * PT, b['y'] * PT, b['w'] * PT, b['h'] * PT,
                               ContentType='GraphicType', Name='Beeld ' + (e['provenance'].get('key') or e['id']))
                figure_count += 1
                asset, im = assets[e['asset']], e['fullImageBounds']
                sx, sy = im['w'] * PT / asset['width'], im['h'] * PT / asset['height']
                image = node(fr, 'Image', Self=ident + '_image',
                             ItemTransform=f'{number(sx)} 0 0 {number(sy)} {number((im["x"] - b["x"]) * PT)} {number((im["y"] - b["y"]) * PT)}')
                node(node(image, 'Properties'), 'GraphicBounds', Left='0', Top='0', Right=asset['width'], Bottom=asset['height'])
                uri = (SOURCE / asset['file']).resolve().as_uri().replace('file:///', 'file:/')
                node(image, 'Link', Self=ident + '_link', LinkResourceURI=uri)
                continue
            fill = color(e['style']['fill'])
            stroke = color(e['style']['stroke'])
            sw = float(a.get('stroke-width', str(e['style']['strokeWidth']).removesuffix('px')) or 0) * PT
            if args.layout_compatibility and a.get('vector-effect') == 'non-scaling-stroke':
                sw = float(a.get('stroke-width', str(e['style']['strokeWidth']).removesuffix('px')) or 0) * .75
            if tag == 'rect':
                item = rectangle(parent, 'Rectangle', ident, float(a.get('x', 0)) * PT, float(a.get('y', 0)) * PT,
                                 float(a['width']) * PT, float(a['height']) * PT, ContentType='Unassigned', Name=a.get('data-target', 'Kader'))
                radius = float(a.get('rx', 0)) * PT
                if radius:
                    for corner in ('TopLeft', 'TopRight', 'BottomLeft', 'BottomRight'):
                        item.set(corner + 'CornerOption', 'RoundedCorner')
                        item.set(corner + 'CornerRadius', number(radius))
            elif tag in ('circle', 'ellipse'):
                cx, cy = float(a['cx']) * PT, float(a['cy']) * PT
                rx, ry = float(a.get('rx', a.get('r'))) * PT, float(a.get('ry', a.get('r'))) * PT
                item = node(parent, 'Oval', Self=ident, ItemTransform=f'1 0 0 1 {number(cx)} {number(cy)}', ItemLayer='layer_content', Name=a.get('data-target', 'Cirkel'))
                k = .5522847498307936
                geometry(item, [((0, -ry), (-k * rx, -ry), (k * rx, -ry)),
                                ((rx, 0), (rx, -k * ry), (rx, k * ry)),
                                ((0, ry), (k * rx, ry), (-k * rx, ry)),
                                ((-rx, 0), (-rx, k * ry), (-rx, -k * ry))])
            elif tag in ('line', 'polyline', 'polygon'):
                if tag == 'line':
                    pts = [(float(a['x1']) * PT, float(a['y1']) * PT), (float(a['x2']) * PT, float(a['y2']) * PT)]
                else:
                    nums = [float(v) * PT for v in re.findall(r'-?[\d.]+', a['points'])]
                    pts = list(zip(nums[::2], nums[1::2]))
                item = node(parent, 'Polygon' if tag == 'polygon' else 'GraphicLine', Self=ident,
                            ItemTransform='1 0 0 1 0 0', ItemLayer='layer_content', Name='Schema verbinding')
                geometry(item, pts, tag != 'polygon')
            else:
                raise ValueError('Unsupported vector: ' + tag)
            item.set('FillColor', fill)
            item.set('StrokeColor', stroke)
            item.set('StrokeWeight', number(sw))
            item.set('StrokeAlignment', 'CenterAlignment')
            opacity = float(a.get('opacity', 1))
            if opacity < 1:
                node(node(item, 'TransparencySetting'), 'BlendingSetting', Opacity=number(opacity * 100))
            fill_opacity = float(a.get('fill-opacity', 1))
            if fill_opacity < 1:
                node(node(item, 'FillTransparencySetting'), 'BlendingSetting', Opacity=number(fill_opacity * 100))
        parts['MasterSpreads/MasterSpread_master.xml' if is_master else f'Spreads/Spread_{pnum}.xml'] = xml(root)

    default_fill = color('#102033') if args.editing_defaults else None
    graphic = package('Graphic')
    node(graphic, 'Swatch', Self='Swatch/None', Name='$ID/None', ColorValue='0 0 0 0')
    for identity, c in colors.items():
        node(graphic, 'Color', Self=identity, Name=identity.rsplit('/', 1)[-1], Model='Process', Space='RGB', ColorValue=' '.join(map(str, c)))
    parts['Resources/Graphic.xml'] = xml(graphic)
    fonts = package('Fonts')
    family = node(fonts, 'FontFamily', Self='FontFamily/Arial', Name='Arial')
    for face, ps in [('Regular', 'ArialMT'), ('Bold', 'Arial-BoldMT')]:
        node(family, 'Font', Self='Font/Arial_' + face, FontFamily='Arial', Name='Arial ' + face,
             PostScriptName=ps, Status='Installed', FontStyleName=face, FontType='TrueType')
    parts['Resources/Fonts.xml'] = xml(fonts)
    sr = package('Styles')
    node(node(sr, 'RootCharacterStyleGroup'), 'CharacterStyle', Self='CharacterStyle/$ID/[No character style]', Name='$ID/[No character style]')
    root_styles = node(sr, 'RootParagraphStyleGroup')
    base_style = node(root_styles, 'ParagraphStyle', Self='ParagraphStyle/$ID/[No paragraph style]', Name='$ID/[No paragraph style]')
    if args.editing_defaults:
        base_style.set('PointSize', number(2.65 * PT))
        base_style.set('FontStyle', 'Regular')
        base_style.set('FillColor', default_fill)
        bp = node(base_style, 'Properties')
        node(bp, 'AppliedFont', type='string').text = 'Arial'
        node(bp, 'Leading', type='unit').text = number(3.7 * PT)
        normal = node(root_styles, 'ParagraphStyle', Self='ParagraphStyle/$ID/NormalParagraphStyle', Name='$ID/NormalParagraphStyle',
                      NextStyle='ParagraphStyle/$ID/NormalParagraphStyle', PointSize=number(2.65 * PT), FontStyle='Regular',
                      FillColor=default_fill, Hyphenation='false', SpaceBefore='0', SpaceAfter='0')
        np = node(normal, 'Properties')
        node(np, 'BasedOn', type='object').text = 'ParagraphStyle/$ID/[No paragraph style]'
        node(np, 'AppliedFont', type='string').text = 'Arial'
        node(np, 'Leading', type='unit').text = number(3.7 * PT)
    for identity, s in styles.items():
        p = node(root_styles, 'ParagraphStyle', Self=identity, Name=s['name'], PointSize=number(s['size']),
                 FontStyle=s['face'], FillColor=s['fill'], Leading=number(s['leading']), Justification=s['align'],
                 SpaceBefore='0', SpaceAfter='0', Hyphenation='false')
        props = node(p, 'Properties')
        if args.editing_defaults:
            p.set('NextStyle', identity)
            node(props, 'BasedOn', type='object').text = 'ParagraphStyle/$ID/[No paragraph style]'
        node(props, 'AppliedFont', type='string').text = 'Arial'
        if args.layout_compatibility:
            del p.attrib['Leading']
            node(props, 'Leading', type='unit').text = number(s['leading'])
    node(node(sr, 'RootObjectStyleGroup'), 'ObjectStyle', Self='ObjectStyle/$ID/[None]', Name='$ID/[None]',
         FillColor='Swatch/None', StrokeColor='Swatch/None', StrokeWeight='0')
    parts['Resources/Styles.xml'] = xml(sr)
    prefs = package('Preferences')
    node(prefs, 'DocumentPreference', PageHeight=number(297 * PT), PageWidth=number(210 * PT), FacingPages='false', PagesPerDocument=len(doc['pages']))
    if args.editing_defaults:
        td = node(prefs, 'TextDefault', AppliedParagraphStyle='ParagraphStyle/$ID/NormalParagraphStyle',
                  AppliedCharacterStyle='CharacterStyle/$ID/[No character style]', FontStyle='Regular', PointSize=number(2.65 * PT),
                  FillColor=default_fill, SpaceBefore='0', SpaceAfter='0', Hyphenation='false')
        tp = node(td, 'Properties')
        node(tp, 'AppliedFont', type='string').text = 'Arial'
        node(tp, 'Leading', type='unit').text = number(3.7 * PT)
    parts['Resources/Preferences.xml'] = xml(prefs)
    design = ET.Element('Document', {'DOMVersion': DOM, 'Self': 'document', 'StoryList': ' '.join(story_ids),
                                    'Name': doc['code'] + '.indd', 'RGBProfile': 'sRGB IEC61966-2.1'})
    node(design, 'Layer', Self='layer_content', Name='Handleiding', Visible='true', Locked='false', Printable='true')
    for kind, file in [('Graphic', 'Resources/Graphic.xml'), ('Fonts', 'Resources/Fonts.xml'), ('Styles', 'Resources/Styles.xml'),
                       ('Preferences', 'Resources/Preferences.xml'), ('MasterSpread', 'MasterSpreads/MasterSpread_master.xml')]:
        node(design, f'{{{NS}}}{kind}', src=file)
    for p in doc['pages']:
        node(design, f'{{{NS}}}Spread', src=f'Spreads/Spread_{p["number"]}.xml')
    for story in story_ids:
        node(design, f'{{{NS}}}Story', src=f'Stories/Story_{story}.xml')
    for index, link in enumerate(hyperlinks, 1):
        dest = link['id'] + '_destination'
        node(design, 'HyperlinkURLDestination', Self=dest, Name=link['url'], DestinationURL=link['url'], Hidden='false', DestinationUniqueKey=index)
        h = node(design, 'Hyperlink', Self=link['id'], Name=link['text'], Source=link['id'] + '_source', Visible='false', Highlight='None',
                 Width='Thin', BorderStyle='Solid', Hidden='false', DestinationUniqueKey=index)
        p = node(h, 'Properties')
        node(p, 'BorderColor', type='enumeration').text = 'Black'
        node(p, 'Destination', type='object').text = dest
    parts['designmap.xml'] = xml(design).replace(b'<Document ', b'<?aid style="50" type="document" readerVersion="6.0" featureSet="257" product="16.0(0)"?>\n<Document ', 1)
    parts['META-INF/container.xml'] = b'<?xml version="1.0" encoding="UTF-8"?><container version="1.0" xmlns="urn:oasis:names:tc:opendocument:xmlns:container"><rootfiles><rootfile full-path="designmap.xml" media-type="application/vnd.adobe.indesign-idml-package+xml"/></rootfiles></container>'
    dest = OUT / 'idml' / f'{doc["code"]}-v{doc["version"]}.idml'
    with zipfile.ZipFile(dest, 'w') as z:
        z.writestr('mimetype', b'application/vnd.adobe.indesign-idml-package', compress_type=zipfile.ZIP_STORED)
        for file, data in parts.items():
            ET.fromstring(data)
            z.writestr(file, data, compress_type=zipfile.ZIP_DEFLATED)
    return {'code': doc['code'], 'file': dest.relative_to(OUT).as_posix(), 'pages': len(doc['pages']),
            'textFrames': len(story_ids), 'styles': len(styles), 'pictureFrames': figure_count, 'masterObjects': len(master_elements), 'hyperlinks': len(hyperlinks),
            'nativeStatus': 'Import/edit/save/export proof pending'}

results = []
for entry in inventory['guides']:
    if args.guide and args.guide != entry['code']:
        continue
    final = SOURCE / 'source' / (entry['code'] + '.json')
    source = final if final.exists() else SOURCE / entry['source']
    doc = json.loads(source.read_text(encoding='utf-8'))
    results.append(build(doc))
    print(json.dumps(results[-1]))
(OUT / 'qa/idml-preparation.json').write_text(json.dumps(results, indent=2) + '\n', encoding='utf-8')
