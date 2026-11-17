"""Prepare a bounded CAT-04 v8 with point-of-use controls; preserve frozen v7."""
import copy
import hashlib
import json
from pathlib import Path
import xml.etree.ElementTree as ET
import zipfile

ROOT = Path(__file__).resolve().parents[2]
SESSION = ROOT / 'output/manuals/affinity-native-2026-11-10'
SOURCE = SESSION / 'idml-inputs/idml/CAT-04-v7.idml'
TARGET = SESSION / 'idml-inputs/idml/CAT-04-v8-controls.idml'
PT = 72 / 25.4
NS = 'http://ns.adobe.com/AdobeInDesign/idml/1.0/packaging'
ET.register_namespace('idPkg', NS)


def xml(root):
    return ET.tostring(root, encoding='utf-8', xml_declaration=True)


def box(node, x, y, w, h):
    node.set('ItemTransform', f'1 0 0 1 {x * PT:.6f} {y * PT:.6f}')
    for point, (px, py) in zip(node.iter('PathPointType'), [(0, 0), (0, h * PT), (w * PT, h * PT), (w * PT, 0)]):
        for key in ('Anchor', 'LeftDirection', 'RightDirection'):
            point.set(key, f'{px:.6f} {py:.6f}')


def main():
    if TARGET.exists():
        raise RuntimeError('Existing candidate preserved')
    with zipfile.ZipFile(SOURCE) as archive:
        parts = {n: archive.read(n) for n in archive.namelist()}
    original = dict(parts)
    design = ET.fromstring(parts['designmap.xml'])
    changes = []
    for page, crop, dest in [(3, (360, 2305, 740, 140), (16, 149, 100)),
                             (4, (95, 2200, 500, 120), (122, 77, 74))]:
        root = ET.fromstring(parts[f'Spreads/Spread_{page}.xml'])
        image_frame = next(n for n in root.iter('Rectangle') if n.get('Self') == f'p{page}_item-14')
        badge = next(n for n in root.iter('Oval') if n.get('Self') == f'p{page}_item-16')
        label = next(n for n in root.iter('TextFrame') if n.get('Name') == f'{page}A')
        parent = next(n for n in root.iter() if image_frame in list(n))
        group = ET.SubElement(parent, 'Group', Self=f'p{page}_control_detail', Name=f'Beeld {page}B - toevoegen onder de lijst', ItemTransform='1 0 0 1 0 0', ItemLayer='layer_content')
        frame = copy.deepcopy(image_frame)
        for n in frame.iter():
            if n.get('Self'):
                n.set('Self', n.get('Self') + '_control')
        frame.set('Name', f'Beeld {page}B - originele volledige opname')
        frame.set('StrokeColor', 'Color/RGB_203_213_225')
        frame.set('StrokeWeight', '1.062992')
        x, y, w = dest
        sx, sy, cw, ch = crop
        h = w * ch / cw
        box(frame, x, y, w, h)
        scale = w * PT / cw
        next(frame.iter('Image')).set('ItemTransform', f'{scale:.8f} 0 0 {scale:.8f} {-sx * scale:.8f} {-sy * scale:.8f}')
        group.append(frame)
        center_x, center_y = x + w - 1, y + 1
        circle = copy.deepcopy(badge)
        circle.set('Self', f'p{page}_control_circle')
        circle.set('ItemTransform', f'1 0 0 1 {center_x * PT:.6f} {center_y * PT:.6f}')
        group.append(circle)
        text = copy.deepcopy(label)
        text.set('Self', f'p{page}_control_label')
        story_id = f'story_p{page}_control_label'
        text.set('ParentStory', story_id)
        text.set('Name', f'{page}B')
        old_matrix = [float(v) for v in label.get('ItemTransform').split()]
        old_center = [float(v) for v in badge.get('ItemTransform').split()]
        text.set('ItemTransform', f'1 0 0 1 {center_x * PT + old_matrix[4] - old_center[4]:.6f} {center_y * PT + old_matrix[5] - old_center[5]:.6f}')
        group.append(text)
        story = ET.fromstring(parts['Stories/Story_' + label.get('ParentStory') + '.xml'])
        next(story.iter('Story')).set('Self', story_id)
        next(story.iter('Content')).text = f'{page}B'
        story_path = f'Stories/Story_{story_id}.xml'
        parts[story_path] = xml(story)
        ET.SubElement(design, f'{{{NS}}}Story', src=story_path)
        intro = next(n for n in root.iter('TextFrame') if n.get('Self') == f'p{page}_item-12')
        intro_path = 'Stories/Story_' + intro.get('ParentStory') + '.xml'
        intro_story = ET.fromstring(parts[intro_path])
        content = next(intro_story.iter('Content'))
        if page == 3:
            content.text = content.text.replace('Klik op Add Expected Subcomponent.', 'Onder Expected Subcomponents: klik na de laatste kindrij op Add Expected Subcomponent (3B).')
            box(intro, 16, 79.03, 177, 17.5)
            caption = copy.deepcopy(next(n for n in root.iter('TextFrame') if n.get('Self') == 'p3_item-18'))
            caption.set('Self', 'p3_control_caption')
            caption.set('Name', '3B Onderaan Expected Subcomponents, na alle kindrijen.')
            caption_id = 'story_p3_control_caption'
            caption.set('ParentStory', caption_id)
            box(caption, 16, 170, 177, 5)
            group.append(caption)
            caption_story = ET.fromstring(parts['Stories/Story_story_p3_item-18.xml'])
            next(caption_story.iter('Story')).set('Self', caption_id)
            next(caption_story.iter('Content')).text = caption.get('Name')
            caption_path = f'Stories/Story_{caption_id}.xml'
            parts[caption_path] = xml(caption_story)
            ET.SubElement(design, f'{{{NS}}}Story', src=caption_path)
        else:
            content.text = content.text.replace('Klik op Add Attribute Contribution.', 'Onderaan Attribute Contributions: kies Add Attribute Contribution (4B).')
            box(intro, 16, 77, 100, 21)
        parts[intro_path] = xml(intro_story)
        parts[f'Spreads/Spread_{page}.xml'] = xml(root)
        changes.append({'page': page, 'source_crop_px': list(crop), 'frame_mm': [x, y, w, h], 'kept_existing_field_view': True})
    graphics = ET.fromstring(parts['Resources/Graphic.xml'])
    if not any(n.get('Self') == 'Color/RGB_203_213_225' for n in graphics.iter()):
        ET.SubElement(graphics, 'Color', Self='Color/RGB_203_213_225', Name='RGB_203_213_225', Model='Process', Space='RGB', ColorValue='203 213 225')
        parts['Resources/Graphic.xml'] = xml(graphics)
    parts['designmap.xml'] = xml(design)
    for name in list(parts):
        if name.startswith('Stories/') and name.endswith('.xml'):
            root = ET.fromstring(parts[name])
            changed = False
            for n in root.iter('Content'):
                old = n.text or ''
                new = old.replace('Versie 7', 'Versie 8').replace('CAT-04 v7', 'CAT-04 v8').replace('04-11-2026', '12-11-2026')
                if new != old:
                    n.text = new
                    changed = True
            if changed:
                parts[name] = xml(root)
    with zipfile.ZipFile(TARGET, 'w', zipfile.ZIP_DEFLATED) as archive:
        archive.writestr('mimetype', parts.pop('mimetype'), compress_type=zipfile.ZIP_STORED)
        for name, data in parts.items():
            archive.writestr(name, data)
    digest = hashlib.sha256(TARGET.read_bytes()).hexdigest()
    guide = {'code': 'CAT-04', 'version': 8, 'pages': 6, 'idml': 'idml/' + TARGET.name,
             'bytes': TARGET.stat().st_size, 'sha256': digest, 'bodyTextFrames': 138, 'pictures': 10}
    report = {'guide': guide, 'source': str(SOURCE), 'changes': changes,
              'changed_parts': [n for n in parts if original.get(n) != parts[n]],
              'limits': 'Unaccepted bounded candidate. Native/PDF/render comparison required.'}
    (SESSION / 'runner/cat04-controls.json').write_text(json.dumps(report, indent=2) + '\n')
    print(json.dumps(guide))


if __name__ == '__main__':
    main()
