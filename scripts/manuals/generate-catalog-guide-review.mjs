import fs from 'node:fs';
import path from 'node:path';
import { pathToFileURL } from 'node:url';
import {
    GUIDE_FAMILIES,
    GUIDE_TOKENS,
    SvgGuideDocument,
    drawCompletionRow,
    drawContextStrip,
    drawRelatedGuideRows,
    guideReference,
    inspectRenderedGuideComponents,
    validateGuideDefinition,
} from './lib/guide-system.mjs';
import {
    browserLaunchOptions,
    evidencePath,
    guideOutputDir,
    loadGuideDependency,
    repoPdfOutputRoot,
} from './lib/guide-paths.mjs';

const { chromium } = loadGuideDependency('playwright');

const generatedOn = process.env.SNIPEIT_GUIDE_DATE ?? '2026-08-27';
const outputRoot = process.env.SNIPEIT_GUIDE_OUT_DIR ?? guideOutputDir('catalog-guide-review');
const selectedGuide = process.env.SNIPEIT_GUIDE_FILTER?.trim().toUpperCase() || null;
const cat00Version = process.env.SNIPEIT_CAT00_VERSION ?? '7';
const cat01Version = process.env.SNIPEIT_CAT01_VERSION ?? '3';
const colors = GUIDE_TOKENS.colors;
const catColor = GUIDE_FAMILIES.CAT.color;
const catSoft = GUIDE_FAMILIES.CAT.fill;
const componentColor = GUIDE_FAMILIES.CMP.color;
const componentSoft = GUIDE_FAMILIES.CMP.fill;
const assetColor = GUIDE_FAMILIES.AST.color;
const assetSoft = GUIDE_FAMILIES.AST.fill;

const sourceIds = {
    list: 'CAT-MODEL-LIST-DESKTOP-01',
    detail: 'CAT-MODEL-DETAIL-DESKTOP-01',
    create: 'CAT-MODEL-CREATE-DESKTOP-01',
    numberCreate: 'CAT-MODEL-NUMBER-CREATE-DESKTOP-01',
    spec: 'CAT-MODEL-SPEC-DESKTOP-01',
    specComponents: 'CAT-MODEL-SPEC-COMPONENTS-DESKTOP-01',
    attributeList: 'CAT-ATTRIBUTE-LIST-DESKTOP-01',
    componentDefinitionList: 'CAT-COMPONENT-DEFINITION-LIST-DESKTOP-01',
    componentInstallResult: 'CMP-INSTALL-RESULT-MOBILE-02',
};

function imageSize(buffer) {
    if (buffer[0] === 0x89 && buffer[1] === 0x50 && buffer[2] === 0x4e && buffer[3] === 0x47) {
        return { width: buffer.readUInt32BE(16), height: buffer.readUInt32BE(20), mime: 'image/png' };
    }
    if (buffer[0] === 0xff && buffer[1] === 0xd8) {
        const startOfFrameMarkers = new Set([0xc0, 0xc1, 0xc2, 0xc3, 0xc5, 0xc6, 0xc7, 0xc9, 0xca, 0xcb, 0xcd, 0xce, 0xcf]);
        let offset = 2;
        while (offset + 8 < buffer.length) {
            if (buffer[offset] !== 0xff) {
                offset += 1;
                continue;
            }
            const marker = buffer[offset + 1];
            if (startOfFrameMarkers.has(marker)) {
                return {
                    width: buffer.readUInt16BE(offset + 7),
                    height: buffer.readUInt16BE(offset + 5),
                    mime: 'image/jpeg',
                };
            }
            if (marker === 0xd8 || marker === 0xd9) {
                offset += 2;
                continue;
            }
            const segmentLength = buffer.readUInt16BE(offset + 2);
            if (segmentLength < 2) break;
            offset += segmentLength + 2;
        }
    }
    throw new Error('Catalog guide evidence must contain PNG or JPEG image data.');
}

function loadImage(sourceId) {
    const file = evidencePath(sourceId);
    const buffer = fs.readFileSync(file);
    const size = imageSize(buffer);
    return {
        ...size,
        href: `data:${size.mime};base64,${buffer.toString('base64')}`,
        file,
        sourceId,
    };
}

const images = Object.fromEntries(
    Object.entries(sourceIds).map(([key, sourceId]) => [key, loadImage(sourceId)]),
);

const related = {
    cat00: [
        guideReference('CAT-01', { width: 63 }),
        guideReference('CAT-02', { width: 57 }),
        guideReference('CAT-04', { width: 63, row: 2 }),
        guideReference('CAT-06', { width: 69, row: 2 }),
    ],
    cat01: [
        guideReference('CAT-00', { width: 52 }),
        guideReference('CAT-02', { width: 57 }),
        guideReference('CAT-06', { width: 69, row: 2 }),
        guideReference('AST-03', { width: 56, row: 2 }),
    ],
};

const definitions = {
    'CAT-00': {
        code: 'CAT-00',
        family: 'CAT',
        title: 'Catalogus begrijpen',
        version: `Draft v${cat00Version}`,
        purpose: 'Begrijp hoe modellen, attributen en componenten samen een assetspecificatie vormen',
        related: related.cat00,
        steps: [
            { number: '1', title: 'De catalogus in één beeld', visuals: [] },
            { number: '2', title: 'Basismodel, modelnummer en asset', visuals: [
                { label: '2A', caption: 'Basismodel en exacte modelnummerrij.' },
            ] },
            { number: '3', title: 'Attribuutdefinitie en attribuutwaarde', visuals: [
                { label: '3A', caption: 'Herbruikbare attribuutdefinities.' },
                { label: '3B', caption: 'Waarde op een exact modelnummer.' },
            ] },
            { number: '4', title: 'Componentdefinitie, verwacht en geplaatst', visuals: [
                { label: '4A', caption: 'Herbruikbare componentdefinities.' },
                { label: '4B', caption: 'Verwachte component op een modelnummer.' },
            ] },
            { number: '5', title: 'Zo wordt een modelnummer opgebouwd', visuals: [
                { label: '5A', caption: 'Directe attribuutwaarden.' },
                { label: '5B', caption: 'Verwachte componenten en bijdragen.' },
            ] },
            { number: '6', title: 'Een asset kan van de baseline afwijken', visuals: [
                { label: '6A', caption: 'Werkelijk geplaatst en getrackt component.' },
                { label: '6B', caption: 'Aangenomen component uit de baseline.' },
            ] },
            { number: '7', title: 'Welke waarde wordt getoond', visuals: [] },
            { number: '8', title: 'Kies de juiste vervolggids', visuals: [] },
        ],
    },
    'CAT-01': {
        code: 'CAT-01',
        family: 'CAT',
        title: 'Model en modelnummer aanmaken',
        version: `Draft v${cat01Version}`,
        purpose: 'Hergebruik of maak de juiste productroute en exacte fabrikantvariant',
        related: related.cat01,
        steps: [
            { number: '1', title: 'Open Asset modellen', visuals: [{ label: '1A', caption: 'Instellingen > Asset modellen.' }] },
            { number: '2', title: 'Zoek naam en exacte code', visuals: [{ label: '2A', caption: 'Zoek product, generatie en code.' }] },
            { number: '3', title: 'Kies een van drie routes', visuals: [{ label: '3A', caption: 'Bestaand basismodel.' }, { label: '3B', caption: 'Nieuw basismodel.' }] },
            { number: '4', title: 'Vul het basismodel in', visuals: [{ label: '4A', caption: 'Actieve productidentiteit.' }] },
            { number: '5', title: 'Open Create Model Number', visuals: [{ label: '5A', caption: 'Open de exacte-variantroute.' }] },
            { number: '6', title: 'Vul code en herkenningslabel in', visuals: [{ label: '6A', caption: 'Exacte code en leesbaar label.' }] },
            { number: '7', title: 'Controleer het resultaat', visuals: [
                { label: '7A', caption: 'Volledige modelnummerrij.' },
                { label: '7B', caption: 'Categorie en fabrikant.' },
            ] },
            { number: '8', title: 'Kies het volgende object', visuals: [] },
        ],
    },
};

Object.values(definitions).forEach(validateGuideDefinition);

function docFor(pageId) {
    return new SvgGuideDocument(images, pageId.toLowerCase());
}

function header(doc, definition, pageNumber, pageCount, context) {
    const title = `${definition.code} ${definition.title}`;
    const titleSize = title.length > 34 ? 4.4 : 8.3;
    doc.rect(12, 13, 2, 16, catColor);
    doc.text(18, 22, title, { size: titleSize, weight: 900, data: { component: 'guide-title' } });
    doc.text(18, 29, definition.purpose, { size: 3, fill: colors.muted });
    doc.rect(164, 12, 34, 14, colors.greenSoft, '#86EFAC', 0.45, 2);
    doc.centeredText(181, 16.5, `${definition.version} ${generatedOn}`, { size: 2.05, weight: 800, fill: colors.green });
    doc.centeredText(181, 22, `Pagina ${pageNumber} van ${pageCount}`, { size: 2.05, weight: 800, fill: colors.green });
    drawContextStrip(doc, context);
}

function pageHeading(doc, number, title, subtitle = null) {
    const chapters = ['Overzicht', 'Identiteit', 'Attributen', 'Componenten', 'Modelnummer', 'Asset', 'Waarden', 'Vervolg'];
    chapters.forEach((label, index) => {
        const x = 12 + index * 23.25;
        const active = index + 1 === number;
        doc.rect(x, 60, 21.5, 5.5, active ? catSoft : colors.faint, active ? catColor : colors.line, 0.35, 1.2);
        doc.centeredText(x + 10.75, 62.9, `${index + 1} ${label}`, { size: 1.35, weight: active ? 900 : 700, fill: active ? catColor : colors.muted });
    });
    doc.familyBadge(15.5, 70, 'CAT', { radius: 3.5, fontSize: 1.9, fill: catSoft });
    doc.text(22, 71.2, `Deel ${number}: ${title}`, { size: 5.1, weight: 900 });
    if (subtitle) doc.text(22, 77, subtitle, { size: 2.35, fill: colors.muted });
}

function card(doc, x, y, w, h, options = {}) {
    doc.rect(x, y, w, h, options.fill ?? colors.white, options.stroke ?? colors.line, options.sw ?? 0.45, options.r ?? 1.8, 1, {
        component: options.component ?? 'content-card',
    });
}

function stepCard(doc, number, title, y, h, options = {}) {
    const x = options.x ?? 12;
    const w = options.w ?? 186;
    card(doc, x, y, w, h, { component: 'step-card' });
    doc.stepBadge(x, y, String(number), catColor);
    doc.text(x + 11, y + 8, title, { size: 3.85, weight: 900 });
    return { x, y, w, h, bodyX: x + 11 };
}

function visual(doc, imageKey, label, caption, frame, crop, marks = [], options = {}) {
    const captionHeight = options.captionHeight ?? 6;
    const imageFrame = { x: frame.x, y: frame.y, w: frame.w, h: frame.h - captionHeight };
    const placement = doc.image(imageKey, imageFrame, crop, { fit: options.fit ?? 'cover', r: 1.2 });
    doc.rect(imageFrame.x, imageFrame.y, imageFrame.w, imageFrame.h, 'none', 'none', 0, 1.2, 0, { component: 'evidence-frame' });
    marks.forEach((mark) => doc.focusMark(placement, mark));
    doc.imageBadge(imageFrame.x + 1.2, imageFrame.y + 1.2, label, options.badgeColor ?? catColor);
    doc.text(frame.x + 1, frame.y + frame.h - 1.7, caption, { size: 2.05, weight: 700, fill: options.captionColor ?? colors.muted });
}

function callout(doc, x, y, w, lines, options = {}) {
    const height = options.height ?? 19;
    const color = options.color ?? colors.orange;
    const fill = options.fill ?? colors.orangeSoft;
    card(doc, x, y, w, height, { fill, stroke: options.stroke ?? '#FDBA74', component: 'callout-card' });
    doc.circle(x + 5.5, y + 6, 2.8, color, 0.6, colors.white);
    doc.centeredText(x + 5.5, y + 6, options.icon ?? '!', { size: 2.5, weight: 900, fill: color });
    doc.text(x + 11, y + 5.6, options.title ?? 'Let op', { size: 2.4, weight: 900, fill: color });
    doc.text(x + 4, y + 11, lines, { size: 2.05, fill: options.textColor ?? colors.ink, lh: 2.75 });
}

function continuationFooter(doc, nextText, references, pageNumber, pageCount) {
    card(doc, 12, 250, 150, 13, { fill: catSoft, stroke: catColor, component: 'continuation-row' });
    doc.centeredText(17, 256.5, 'Volgende pagina', { size: 2.7, weight: 900, fill: catColor, anchor: 'start' });
    doc.centeredText(47, 256.5, nextText, { size: 2.2, fill: catColor, anchor: 'start' });
    drawRelatedGuideRows(doc, references);
    doc.qrPlaceholder(176, 263, 22);
    doc.centeredText(187, 289, 'Digitale gids', { size: 2.15, weight: 800 });
    doc.text(12, 294, `Bron: gecontroleerde testomgeving | ${generatedOn}`, { size: 1.75, fill: colors.muted });
    doc.text(198, 294, `Pagina ${pageNumber} van ${pageCount}`, { size: 1.75, fill: colors.muted, anchor: 'end' });
}

function finalFooter(doc, completion, references, pageNumber, pageCount) {
    drawCompletionRow(doc, completion);
    drawRelatedGuideRows(doc, references);
    doc.qrPlaceholder(176, 263, 22);
    doc.centeredText(187, 289, 'Digitale gids', { size: 2.15, weight: 800 });
    doc.text(12, 294, `Bron: gecontroleerde testomgeving | ${generatedOn}`, { size: 1.75, fill: colors.muted });
    doc.text(198, 294, `Pagina ${pageNumber} van ${pageCount}`, { size: 1.75, fill: colors.muted, anchor: 'end' });
}

function simpleRow(doc, x, y, w, label, lines, options = {}) {
    const h = options.h ?? 18;
    const bodyLines = Array.isArray(lines) ? lines : [lines];
    const labelSize = options.labelSize ?? 2.5;
    const bodySize = options.bodySize ?? 2;
    const bodyLineHeight = options.lh ?? 2.65;
    card(doc, x, y, w, h, { fill: options.fill ?? colors.faint, stroke: options.stroke ?? colors.line, component: 'definition-row' });
    if (options.family) {
        doc.familyBadge(x + 5, y + h / 2, options.family, { radius: 2.6, fontSize: 1.5, fill: colors.white });
    } else {
        doc.circle(x + 5, y + h / 2, 2.7, options.color ?? catColor, 0.6, colors.white);
        doc.centeredText(x + 5, y + h / 2, options.icon ?? label.slice(0, 1), { size: 2.1, weight: 900, fill: options.color ?? catColor });
    }
    let labelY = y + 6;
    let bodyY = y + 11.2;
    if (options.centered) {
        const groupHeight = labelSize + 1.5 + bodyLines.length * bodyLineHeight;
        const groupTop = y + Math.max(2, (h - groupHeight) / 2);
        labelY = groupTop + labelSize;
        bodyY = labelY + 4;
    }
    doc.text(x + 10, labelY, label, { size: labelSize, weight: 900, fill: options.color ?? colors.ink });
    doc.text(x + 10, bodyY, bodyLines, { size: bodySize, fill: colors.muted, lh: bodyLineHeight });
}

function arrow(doc, x1, y1, x2, y2, color = catColor) {
    doc.line(x1, y1, x2, y2, color, 0.8);
    doc.raw(`<polygon points="${x2},${y2} ${x2 - 2.2},${y2 - 1.4} ${x2 - 2.2},${y2 + 1.4}" fill="${color}"/>`);
}

function downArrow(doc, x, y1, y2, color = catColor) {
    doc.line(x, y1, x, y2, color, 0.8);
    doc.raw(`<polygon points="${x},${y2} ${x - 1.4},${y2 - 2.2} ${x + 1.4},${y2 - 2.2}" fill="${color}"/>`);
}

function graphArrow(doc, x1, y1, x2, y2, color = catColor) {
    const dx = x2 - x1;
    const dy = y2 - y1;
    const length = Math.hypot(dx, dy) || 1;
    const ux = dx / length;
    const uy = dy / length;
    const px = -uy;
    const py = ux;
    const arrowLength = 2.2;
    const arrowWidth = 1.35;
    const backX = x2 - ux * arrowLength;
    const backY = y2 - uy * arrowLength;
    doc.line(x1, y1, x2, y2, color, 0.8);
    doc.raw(`<polygon points="${x2},${y2} ${backX + px * arrowWidth},${backY + py * arrowWidth} ${backX - px * arrowWidth},${backY - py * arrowWidth}" fill="${color}"/>`);
}

function graphNode(doc, x, y, w, h, label, lines, options = {}) {
    const color = options.color ?? catColor;
    const fill = options.fill ?? colors.faint;
    const bodyLines = Array.isArray(lines) ? lines : [lines];
    card(doc, x, y, w, h, { fill, stroke: color, sw: options.sw ?? 0.55, r: 1.8, component: options.component ?? 'catalog-graph-node' });
    doc.circle(x + 5, y + h / 2, 2.65, color, 0.65, colors.white);
    doc.centeredText(x + 5, y + h / 2, options.icon ?? label.slice(0, 1), { size: 1.95, weight: 900, fill: color });
    doc.text(x + 10, y + 7, label, { size: options.labelSize ?? 2.3, weight: 900, fill: options.labelColor ?? colors.ink });
    doc.text(x + 10, y + 12, bodyLines, { size: options.bodySize ?? 1.65, fill: options.bodyColor ?? colors.muted, lh: options.lh ?? 2.25 });
}

function routeRow(doc, y, title, lines, reference) {
    card(doc, 12, y, 186, 25, { fill: colors.white, stroke: colors.line, component: 'guide-route-row' });
    doc.rect(18, y + 5, 1.4, 15, catColor, catColor, 0, 0.7);
    doc.text(24, y + 7, title, { size: 2.75, weight: 900 });
    doc.text(24, y + 13.4, lines, { size: 2.05, fill: colors.muted, lh: 2.65 });
    doc.guideChip(125, y + 8.7, 67, reference);
}

function cat00Page1() {
    const def = definitions['CAT-00'];
    const doc = docFor('cat00-page1');
    header(doc, def, 1, 8, [
        { label: 'Rol', value: 'Supervisor' },
        { label: 'Nodig', value: 'Leesrechten catalogus' },
        { label: 'Vooraf', value: 'AC-01 Login', guide: guideReference('AC-01') },
    ]);
    pageHeading(doc, 1, 'De catalogus in één beeld', 'Volg de lijnen om te zien welke definities, baselines en fysieke exemplaren aan elkaar gekoppeld zijn.');

    card(doc, 12, 82, 186, 143, { fill: colors.white, stroke: catColor, component: 'catalog-relationship-graph' });
    doc.text(17, 89, 'Relatiekaart: catalogus en fysieke werkelijkheid', { size: 2.8, weight: 900, fill: catColor });
    doc.circle(116, 89, 2.1, catColor, 0.5, catSoft);
    doc.text(120, 90, 'model / attribuut', { size: 1.65, weight: 800, fill: catColor });
    doc.circle(151, 89, 2.1, componentColor, 0.5, componentSoft);
    doc.text(155, 90, 'component', { size: 1.65, weight: 800, fill: componentColor });
    doc.circle(178, 89, 2.1, assetColor, 0.5, assetSoft);
    doc.text(182, 90, 'asset', { size: 1.65, weight: 800, fill: assetColor });

    graphNode(doc, 17, 101, 42, 23, 'Basismodel', ['Product + generatie', 'HP ProBook 450 G8'], { icon: 'M', fill: catSoft, labelColor: catColor });
    graphNode(doc, 84, 101, 44, 23, 'Modelnummer', ['Fabrikantvariant', '2E9F8EA#ABH'], { icon: '#', fill: catSoft, labelColor: catColor });
    graphNode(doc, 154, 101, 39, 23, 'Asset', ['Fysiek apparaat', 'tag + serienummer'], { icon: 'I', color: assetColor, fill: assetSoft, labelColor: assetColor, bodySize: 1.55, sw: 0.9 });
    graphArrow(doc, 59, 112.5, 84, 112.5);
    graphArrow(doc, 128, 112.5, 154, 112.5, assetColor);
    doc.centeredText(71.5, 109.5, 'heeft', { size: 1.75, weight: 700, fill: catColor });
    doc.centeredText(141, 109.5, 'gebruikt door', { size: 1.65, weight: 700, fill: assetColor });

    graphNode(doc, 49, 138, 57, 22, 'Directe attribuutwaarde', ['Waarde op dit modelnummer', 'bijvoorbeeld Introductiejaar'], { icon: 'W', fill: catSoft, labelColor: catColor, bodySize: 1.55 });
    graphNode(doc, 119, 138, 58, 22, 'Expected Component', ['Component + aantal in', 'de modelnummerbaseline'], { icon: 'V', color: componentColor, fill: componentSoft, labelColor: componentColor, bodySize: 1.55 });
    graphArrow(doc, 101, 124, 77.5, 138);
    graphArrow(doc, 111, 124, 148, 138, componentColor);
    doc.text(65, 132, 'directe waarden', { size: 1.65, weight: 700, fill: catColor });
    doc.text(124, 132, 'verwachte onderdelen', { size: 1.6, weight: 700, fill: componentColor });

    graphNode(doc, 49, 176, 57, 22, 'Attribuutdefinitie', ['Label, datatype en regels', 'worden hergebruikt'], { icon: 'A', fill: catSoft, labelColor: catColor, bodySize: 1.55 });
    graphNode(doc, 119, 176, 58, 22, 'Componentdefinitie', ['Groep van attribuutwaarden', 'bijvoorbeeld RAM 8 GB DDR4'], { icon: 'C', color: componentColor, fill: componentSoft, labelColor: componentColor, bodySize: 1.55 });
    graphArrow(doc, 77.5, 160, 77.5, 176);
    graphArrow(doc, 148, 160, 148, 176, componentColor);
    graphArrow(doc, 119, 187, 106, 187, componentColor);
    doc.text(80, 168, 'gebruikt definitie', { size: 1.65, weight: 700, fill: catColor });
    doc.text(151, 168, 'gebruikt definitie', { size: 1.65, weight: 700, fill: componentColor });
    doc.centeredText(112.5, 183.5, 'attributen', { size: 1.6, weight: 700, fill: componentColor });

    graphNode(doc, 133, 204, 52, 18, 'Placed Component', ['Fysiek component'], { icon: 'G', color: componentColor, fill: componentSoft, labelColor: componentColor, bodySize: 1.5, lh: 2, sw: 0.95 });
    graphArrow(doc, 154, 204, 148, 198, componentColor);
    doc.line(173.5, 124, 190, 124, assetColor, 0.8);
    doc.line(190, 124, 190, 213, assetColor, 0.8);
    graphArrow(doc, 190, 213, 185, 213, assetColor);
    doc.text(158, 199.5, 'instantie van', { size: 1.6, fill: componentColor, weight: 800 });
    doc.text(180, 166, ['bevat dit', 'component'], { size: 1.6, fill: assetColor, weight: 800, lh: 2.05 });

    callout(doc, 12, 230, 186, [
        'Paars = model of attribuut. Amber = component. Groen = asset. De woorden definitie, verwacht en fysiek benoemen de rol.',
    ], { height: 14, title: 'Kleur volgt het object; de tekst vertelt welke rol het record heeft', icon: 'i', color: catColor, fill: catSoft, stroke: catColor });

    continuationFooter(doc, 'bekijk de identiteit van basismodel, modelnummer en asset.', [
        guideReference('CAT-01', { width: 63 }),
        guideReference('CAT-02', { width: 57 }),
    ], 1, 8);
    return doc.render();
}

function cat00Page2() {
    const def = definitions['CAT-00'];
    const doc = docFor('cat00-page2');
    header(doc, def, 2, 8, [
        { label: 'Rol', value: 'Supervisor' },
        { label: 'Vraag', value: 'Welk object bekijk ik?' },
        { label: 'Vooraf', value: 'Deel 1: overzicht' },
    ]);
    pageHeading(doc, 2, 'Basismodel, modelnummer en asset', 'Lees van product naar exacte fabrikantvariant en daarna naar één fysiek apparaat.');

    const widths = [56, 56, 56];
    const xs = [12, 77, 142];
    simpleRow(doc, xs[0], 82, widths[0], 'Basismodel', ['Productfamilie + generatie', 'kan meerdere codes hebben'], { h: 24, icon: 'M', centered: true, color: catColor, fill: catSoft, stroke: catColor });
    simpleRow(doc, xs[1], 82, widths[1], 'Modelnummer', ['Exacte fabrikantcode', 'kan door meerdere assets worden gebruikt'], { h: 24, icon: '#', centered: true, color: catColor, fill: catSoft, stroke: catColor, bodySize: 1.85 });
    simpleRow(doc, xs[2], 82, widths[2], 'Asset', ['Eén fysiek apparaat', 'unieke tag + serienummer'], { h: 24, icon: 'I', centered: true, color: assetColor, fill: assetSoft, stroke: assetColor });
    arrow(doc, 68.5, 94, 76, 94);
    arrow(doc, 133.5, 94, 141, 94, assetColor);

    card(doc, 12, 112, 186, 47, { fill: catSoft, stroke: catColor, component: 'example-hierarchy' });
    doc.rect(135, 122, 58, 33, assetSoft, assetColor, 0.3, 1.2);
    doc.text(17, 119, 'Voorbeelden: elk apparaattype gebruikt dezelfde volgorde', { size: 2.8, weight: 900, fill: catColor });
    const columnXs = [17, 78, 139];
    ['Basismodel', 'Exact modelnummer', 'Fysiek asset'].forEach((label, index) => doc.text(columnXs[index], 126, label, { size: 2.35, weight: 900, fill: index === 2 ? assetColor : catColor }));
    doc.line(17, 129, 193, 129, catColor, 0.45);
    doc.line(73, 122, 73, 155, catColor, 0.3);
    doc.line(134, 122, 134, 155, catColor, 0.3);
    const exampleRows = [
        [['HP ProBook 450 G8', 'Laptop, generatie G8'], ['2E9F8EA#ABH', 'i5 / 8 GB / 256 GB'], ['INBIT-HG0042', 'S/N 5CD1234ABC']],
        [['Samsung Galaxy A5', 'Telefoon, modeljaar 2017'], ['SM-A520F', '32 GB / Black Sky'], ['INBIT-TF0187', 'S/N R58M1234ABC']],
    ];
    exampleRows.forEach((row, rowIndex) => row.forEach((cell, columnIndex) => {
        const y = 135 + rowIndex * 14;
        doc.text(columnXs[columnIndex], y, cell[0], { size: 2.35, weight: 900, fill: columnIndex === 2 ? assetColor : colors.ink });
        doc.text(columnXs[columnIndex], y + 5, cell[1], { size: 1.95, fill: colors.muted });
    }));
    doc.line(17, 143, 193, 143, colors.line, 0.35);

    visual(doc, 'detail', '2A', 'Het kruimelpad toont het basismodel; de rij eronder toont het exacte modelnummer en zijn label.',
        { x: 12, y: 165, w: 186, h: 77 },
        { x: 45, y: 92, w: 1015, h: 290 },
        [
            { x: 390, y: 112, w: 245, h: 38, padding: 4, target: 'Naam basismodel' },
            { x: 72, y: 235, w: 500, h: 54, padding: 4, target: 'Exact modelnummer en label' },
        ], { fit: 'contain' });

    continuationFooter(doc, 'leer het verschil tussen een attribuutdefinitie en een waarde.', [
        guideReference('CAT-01', { width: 63 }),
        guideReference('AST-03', { width: 56 }),
    ], 2, 8);
    return doc.render();
}

function cat00Page3() {
    const def = definitions['CAT-00'];
    const doc = docFor('cat00-page3');
    header(doc, def, 3, 8, [
        { label: 'Rol', value: 'Supervisor' },
        { label: 'Onderwerp', value: 'Attributen' },
        { label: 'Vooraf', value: 'Deel 2: identiteit' },
    ]);
    pageHeading(doc, 3, 'Attribuutdefinitie en attribuutwaarde', 'De definitie bepaalt wat een waarde betekent en welke invoer geldig is.');

    card(doc, 12, 82, 186, 65, { fill: catSoft, stroke: catColor, component: 'attribute-definition-explanation' });
    doc.text(17, 90, 'Attribuutdefinitie = herbruikbare afspraak', { size: 2.9, weight: 900, fill: catColor });
    const definitionFields = [
        [
            ['Label', 'Werkgeheugen - zichtbare naam'],
            ['Key', 'werkgeheugen - automatisch uit Label'],
            ['Datatype', 'Int - een geheel getal'],
            ['Unit (optional)', 'GB'],
            ['Category Scope', 'Leeg = alle; voorbeeld: Laptops, Desktops'],
            ['Required for category', 'Aan voor een verplicht laptopveld'],
        ],
        [
            ['Allow asset overrides', 'Uit als het asset niet mag afwijken'],
            ['Allow custom values (enum only)', 'Uit: kies alleen bestaande opties'],
            ['Component Spec Display', 'Value labels voor bijvoorbeeld 8 GB'],
            ['Constraints', 'Minimum 1, Maximum 256, Step 1'],
            ['Options', 'Alleen bij Enum: DDR3, DDR4, DDR5'],
        ],
    ];
    definitionFields.forEach((column, columnIndex) => column.forEach((row, rowIndex) => {
        const x = columnIndex === 0 ? 17 : 108;
        const y = 98 + rowIndex * 7.3;
        doc.text(x, y, `${row[0]}:`, { size: 1.95, weight: 900 });
        doc.text(x, y + 3.2, row[1], { size: 1.8, fill: colors.muted });
    }));
    doc.rect(108, 135.2, 84, 7.8, colors.white, catColor, 0.35, 1.2);
    doc.text(112, 140, 'Na aanmaken:', { size: 1.85, weight: 900, fill: catColor });
    doc.text(137, 140, 'Datatype kan niet meer wijzigen.', { size: 1.75, fill: colors.muted });

    card(doc, 12, 151, 186, 27, { component: 'attribute-value-locations' });
    doc.text(17, 158, 'Dezelfde definitie kan op verschillende plekken een waarde krijgen', { size: 2.7, weight: 900, fill: catColor });
    const valuePlaces = [
        ['Modelnummer', ['directe vaste waarde'], catColor, catSoft],
        ['Componentdefinitie', ['herbruikbare', 'componentwaarde'], componentColor, componentSoft],
        ['Asset', ['toegestane', 'uitzondering'], assetColor, assetSoft],
        ['Workflow', ['verwachting/resultaat', 'buiten catalogus'], GUIDE_FAMILIES.WF.color, GUIDE_FAMILIES.WF.fill],
    ];
    valuePlaces.forEach((row, index) => {
        const x = 17 + index * 44;
        doc.rect(x, 162, 40, 12, row[3], row[2], 0.35, 1.2);
        doc.text(x + 3, 166.4, row[0], { size: 1.8, weight: 900, fill: row[2] });
        doc.text(x + 3, 170.1, row[1], { size: 1.5, fill: colors.muted, lh: 1.8 });
    });

    card(doc, 12, 181, 186, 18, { fill: colors.faint, stroke: colors.line, component: 'datatype-table' });
    doc.text(17, 188, 'Datatype in het systeem bepaalt de invoer', { size: 2.35, weight: 900, fill: catColor });
    const datatypes = [
        ['Bool', '5G: ja/nee'], ['Int', '8 GB'], ['Decimal', '1,74 kg'], ['Enum', 'DDR4'], ['Text', 'DisplayPort 1.4b'],
    ];
    datatypes.forEach((row, index) => {
        const x = 17 + index * 35;
        doc.text(x, 195, row[0], { size: 1.85, weight: 900 });
        doc.text(x + 9, 195, row[1], { size: 1.65, fill: colors.muted });
    });

    visual(doc, 'attributeList', '3A', 'De lijst toont naam, sleutel, datatype, categorie en gebruiksregels.',
        { x: 12, y: 203, w: 90, h: 40 },
        { x: 60, y: 145, w: 1180, h: 430 },
        [{ x: 72, y: 198, w: 930, h: 68, padding: 3, target: 'Kolommen van attribuutdefinities' }]);
    visual(doc, 'spec', '3B', 'Op Edit Spec krijgt de definitie een waarde voor dit modelnummer.',
        { x: 108, y: 203, w: 90, h: 40 },
        { x: 235, y: 185, w: 865, h: 460 },
        [{ x: 575, y: 425, w: 265, h: 70, padding: 4, target: 'Geselecteerd attribuut en waarde' }]);

    continuationFooter(doc, 'bekijk hoe componentdefinities, verwachtingen en fysieke onderdelen verschillen.', [
        guideReference('CAT-03', { width: 48 }),
        guideReference('CAT-02', { width: 57 }),
    ], 3, 8);
    return doc.render();
}

function cat00Page4() {
    const def = definitions['CAT-00'];
    const doc = docFor('cat00-page4');
    header(doc, def, 4, 8, [
        { label: 'Rol', value: 'Supervisor' },
        { label: 'Onderwerp', value: 'Componenten' },
        { label: 'Vooraf', value: 'Deel 3: attributen' },
    ]);
    pageHeading(doc, 4, 'Componentdefinitie, verwacht en geplaatst', 'Eén herbruikbare definitie kan een modelbaseline beschrijven of op een fysiek asset worden geregistreerd.');

    card(doc, 12, 82, 186, 70, { fill: colors.white, stroke: componentColor, component: 'component-relationship' });
    doc.text(17, 89, 'Een componentdefinitie is een herbruikbare groep van attributen', { size: 2.8, weight: 900, fill: componentColor });
    simpleRow(doc, 58, 94, 94, 'Componentdefinitie', ['Bundelt bijvoorbeeld Werkgeheugen = 8 GB', 'en Geheugentype = DDR4 als één herkenbaar RAM-type.'], { h: 24, icon: 'D', centered: true, bodySize: 1.85, color: componentColor, fill: componentSoft, stroke: componentColor });
    doc.line(105, 118, 105, 123, componentColor, 0.8);
    doc.line(57, 123, 153, 123, componentColor, 0.8);
    downArrow(doc, 57, 123, 128, componentColor);
    downArrow(doc, 153, 123, 128, componentColor);
    simpleRow(doc, 17, 128, 80, 'Expected Component', ['Gebruik in de modelnummerbaseline.', 'Definitie + aantal; nog geen uniek onderdeel.'], { h: 20, icon: 'V', centered: true, bodySize: 1.75, lh: 2.35, color: componentColor, fill: componentSoft, stroke: componentColor });
    simpleRow(doc, 113, 128, 80, 'Placed Component', ['Werkelijk onderdeel op één asset.', 'Kan tag, serienummer, conditie en status hebben.'], { h: 20, icon: 'G', centered: true, bodySize: 1.75, lh: 2.35, color: componentColor, fill: componentSoft, stroke: componentColor });

    card(doc, 12, 157, 186, 27, { fill: componentSoft, stroke: componentColor, component: 'component-details' });
    doc.text(17, 164, 'Wat wordt in de definitie vastgelegd?', { size: 2.55, weight: 900, fill: componentColor });
    doc.text(17, 171, ['Name, Part Code, Model Number, Category en Manufacturer.', 'Attribute Contributions bepalen de waarden; Show as asset spec laat een bijdrage meetellen.'], { size: 1.85, fill: colors.ink, lh: 2.5 });
    doc.text(112, 171, ['Expected Subcomponents zijn optionele onderliggende onderdelen.', 'De twee toepassingen hierboven zijn geen verplichte opeenvolgende stappen.'], { size: 1.85, fill: colors.ink, lh: 2.5 });

    visual(doc, 'componentDefinitionList', '4A', 'Component Definitions: herbruikbare typen met aantallen Instances en Templates.',
        { x: 12, y: 188, w: 90, h: 56 },
        { x: 50, y: 105, w: 1260, h: 620 },
        [{ x: 410, y: 205, w: 550, h: 74, padding: 3, target: 'Instances en Templates' }], { badgeColor: componentColor });
    visual(doc, 'specComponents', '4B', 'Expected Components koppelt een definitie en aantal aan één modelnummer.',
        { x: 108, y: 188, w: 90, h: 56 },
        { x: 265, y: 92, w: 875, h: 600 },
        [{ x: 380, y: 210, w: 735, h: 100, padding: 4, target: 'Verwachte componentdefinitie en aantal' }], { badgeColor: componentColor });

    continuationFooter(doc, 'zie hoe een exact modelnummer zijn volledige baseline krijgt.', [
        guideReference('CAT-04', { width: 63 }),
        guideReference('CAT-02', { width: 57 }),
    ], 4, 8);
    return doc.render();
}

function cat00Page5() {
    const def = definitions['CAT-00'];
    const doc = docFor('cat00-page5');
    header(doc, def, 5, 8, [
        { label: 'Rol', value: 'Supervisor' },
        { label: 'Onderwerp', value: 'Modelnummerbaseline' },
        { label: 'Vooraf', value: 'Deel 3 en 4 begrepen' },
    ]);
    pageHeading(doc, 5, 'Zo wordt een modelnummer opgebouwd', 'Het modelnummer combineert directe productfeiten met de verwachte fysieke opbouw.');

    card(doc, 12, 82, 186, 23, { fill: catSoft, stroke: catColor, component: 'model-number-formula' });
    doc.text(17, 90, 'Exact modelnummer', { size: 2.8, weight: 900, fill: catColor });
    doc.text(52, 90, '=', { size: 2.8, weight: 900, fill: catColor });
    doc.text(62, 90, 'directe attribuutwaarden', { size: 2.5, weight: 900, fill: catColor });
    doc.text(111, 90, '+', { size: 2.8, weight: 900, fill: catColor });
    doc.text(121, 90, 'verwachte componenten en hun bijdragen', { size: 2.5, weight: 900, fill: componentColor });
    doc.text(17, 100, 'Een modelafbeelding en leesbaar label helpen herkennen, maar bepalen de specificatiewaarden niet.', { size: 1.95, fill: colors.muted });

    card(doc, 12, 110, 186, 63, { component: 'model-number-example' });
    doc.text(17, 118, 'Voorbeeld: HP ProBook 450 G8 - 2E9F8EA#ABH', { size: 2.8, weight: 900, fill: catColor });
    doc.text(17, 126, 'Direct op het modelnummer', { size: 2.15, weight: 900, fill: catColor });
    doc.text(75, 126, 'Via verwachte componentdefinities', { size: 2.15, weight: 900, fill: componentColor });
    doc.line(68, 122, 68, 167, colors.line, 0.35);
    const directRows = [
        ['Introductiejaar', '2021'], ['Gewicht', '1,74 kg'], ['Kleur', 'Silver'], ['Beeldscherm', '15,6 inch'],
    ];
    directRows.forEach((row, index) => {
        const y = 135 + index * 8;
        doc.text(17, y, row[0], { size: 1.95, weight: 800 });
        doc.text(51, y, row[1], { size: 1.95, fill: colors.muted });
    });
    const componentRows = [
        ['Moederbord', 'processor + grafische chip + poorten'],
        ['RAM 8GB DDR4', 'werkgeheugen + geheugentype'],
        ['Storage 256GB NVMe', 'opslagcapaciteit + opslagtype'],
        ['Battery 45 Wh', 'batterijcapaciteit'],
    ];
    componentRows.forEach((row, index) => {
        const y = 135 + index * 8;
        doc.text(75, y, row[0], { size: 1.95, weight: 800, fill: componentColor });
        doc.text(117, y, row[1], { size: 1.85, fill: colors.muted });
    });

    visual(doc, 'spec', '5A', 'Directe attributen staan bovenaan op Edit Spec voor het gekozen modelnummer.',
        { x: 12, y: 179, w: 90, h: 64 },
        { x: 235, y: 185, w: 865, h: 460 },
        [
            { x: 505, y: 286, w: 475, h: 48, padding: 4, target: 'Geselecteerd modelnummer' },
            { x: 575, y: 425, w: 265, h: 70, padding: 4, target: 'Direct attribuut en waarde' },
        ], { fit: 'contain' });
    visual(doc, 'specComponents', '5B', 'Verwachte componenten staan lager op dezelfde Edit Spec-pagina.',
        { x: 108, y: 179, w: 90, h: 64 },
        { x: 265, y: 92, w: 875, h: 600 },
        [{ x: 380, y: 210, w: 735, h: 100, padding: 4, target: 'Componentbijdragen in de modelbaseline' }], { badgeColor: componentColor });

    continuationFooter(doc, 'vergelijk de modelbaseline met de werkelijkheid van één asset.', [
        guideReference('CAT-02', { width: 57 }),
        guideReference('CAT-04', { width: 63 }),
    ], 5, 8);
    return doc.render();
}

function cat00Page6() {
    const def = definitions['CAT-00'];
    const doc = docFor('cat00-page6');
    header(doc, def, 6, 8, [
        { label: 'Rol', value: 'Supervisor' },
        { label: 'Onderwerp', value: 'Eén fysiek asset' },
        { label: 'Vooraf', value: 'Deel 5: baseline' },
    ]);
    pageHeading(doc, 6, 'Een asset kan van de baseline afwijken', 'Het modelnummer blijft de fabrikantvariant; componentwijzigingen beschrijven de actuele hardware.');

    card(doc, 12, 82, 186, 66, { fill: colors.white, stroke: assetColor, component: 'asset-baseline-comparison' });
    doc.text(17, 90, 'Hetzelfde modelnummer: fabrieksbaseline tegenover actuele werkelijkheid', { size: 2.75, weight: 900, fill: assetColor });
    doc.text(17, 98, 'Onderdeel', { size: 1.9, weight: 900, fill: colors.muted });
    doc.text(53, 98, 'Modelnummerbaseline', { size: 1.9, weight: 900, fill: catColor });
    doc.text(116, 98, 'Dit fysieke asset', { size: 1.9, weight: 900, fill: assetColor });
    doc.line(17, 101, 193, 101, assetColor, 0.4);
    const comparisonRows = [
        ['RAM', 'Verwacht 8 GB DDR4', '8 GB DDR4 - Tracked'],
        ['Storage', 'Verwacht 256 GB NVMe', '256 GB NVMe - Assumed'],
        ['Batterij', 'Verwacht 45 Wh', 'Pas afwijkend na registratie'],
        ['Identiteit', 'Modelnummer 2E9F8EA#ABH', 'Dezelfde fabrikantvariant'],
    ];
    comparisonRows.forEach((row, index) => {
        const y = 108 + index * 8.5;
        doc.text(17, y, row[0], { size: 1.95, weight: 900 });
        doc.text(53, y, row[1], { size: 1.9, fill: colors.ink });
        doc.text(116, y, row[2], { size: 1.9, fill: index < 2 ? componentColor : (index === 3 ? assetColor : colors.ink), weight: index < 2 || index === 3 ? 800 : 500 });
        if (index < comparisonRows.length - 1) doc.line(17, y + 3.2, 193, y + 3.2, colors.line, 0.25);
    });

    callout(doc, 12, 153, 186, [
        'Voorbeeld: vervang 8 GB door 16 GB en registreer 16 GB als Tracked. Het modelnummer blijft gelijk; de assetopbouw verandert.',
    ], { height: 16, title: 'Baseline is verwachting; Tracked is werkelijk geregistreerd', icon: 'i', color: assetColor, fill: assetSoft, stroke: assetColor });

    visual(doc, 'componentInstallResult', '6A', 'Tracked: werkelijk geregistreerd RAM op dit asset.',
        { x: 12, y: 175, w: 90, h: 68 },
        { x: 44, y: 225, w: 365, h: 240 },
        [], { fit: 'contain', badgeColor: componentColor });
    visual(doc, 'componentInstallResult', '6B', 'Assumed: alleen overgenomen uit de modelnummerbaseline.',
        { x: 108, y: 175, w: 90, h: 68 },
        { x: 44, y: 625, w: 326, h: 205 },
        [], { fit: 'contain', badgeColor: componentColor });

    continuationFooter(doc, 'bekijk welke bron de getoonde waarde bepaalt.', [
        guideReference('CMP-01', { width: 66 }),
        guideReference('WF-02', { width: 67 }),
    ], 6, 8);
    return doc.render();
}

function cat00Page7() {
    const def = definitions['CAT-00'];
    const doc = docFor('cat00-page7');
    header(doc, def, 7, 8, [
        { label: 'Rol', value: 'Supervisor' },
        { label: 'Vraag', value: 'Welke waarde zie ik?' },
        { label: 'Vooraf', value: 'Baseline en asset begrepen' },
    ]);
    pageHeading(doc, 7, 'Welke waarde wordt getoond', 'Niet elke ingevulde waarde telt mee: de plek en instellingen bepalen de effectieve assetspecificatie.');

    card(doc, 12, 82, 112, 119, { component: 'precedence-ladder' });
    doc.text(18, 91, 'Prioriteit voor de getoonde assetspecificatie', { size: 2.8, weight: 900, fill: catColor });
    const precedence = [
        ['1', 'Actueel geplaatst component', ['Alleen bijdragen met Toon als assetspecificatie.', 'De fysieke componentwaarde gaat vóór de definitiestandaard.'], componentColor, componentSoft],
        ['2', 'Toegestane asset override', ['Alleen als geen componentbijdrage de waarde bepaalt', 'en de attribuutdefinitie overrides toestaat.'], assetColor, assetSoft],
        ['3', 'Verwachte componentbijdrage', ['Een meetellende bijdrage in de modelbaseline gaat', 'vóór een dubbele directe modelnummerwaarde.'], componentColor, componentSoft],
        ['4', 'Directe modelnummerwaarde', ['Wordt gebruikt wanneer geen meetellende component', 'dezelfde specificatie levert.'], catColor, catSoft],
    ];
    precedence.forEach((row, index) => simpleRow(doc, 18, 98 + index * 24.2, 100, row[1], row[2], { h: 21, icon: row[0], color: row[3], fill: row[4], stroke: row[3] }));

    card(doc, 132, 82, 66, 119, { fill: colors.white, stroke: catColor, component: 'precedence-example' });
    doc.text(138, 91, 'Voorbeeld: werkgeheugen', { size: 2.7, weight: 900, fill: catColor });
    const exampleRows = [
        ['Direct modelnummer', '8 GB', catColor, catSoft],
        ['Verwachte RAM-definitie', '8 GB · telt mee', componentColor, componentSoft],
        ['Geplaatst RAM', '16 GB · telt mee', componentColor, componentSoft],
        ['Getoond voor asset', '16 GB uit geplaatst RAM', assetColor, assetSoft],
    ];
    exampleRows.forEach((row, index) => {
        const y = 97 + index * 19;
        card(doc, 137, y, 56, 15, { fill: row[3], stroke: row[2], component: 'precedence-example-row' });
        doc.text(141, y + 5.3, row[0], { size: 1.9, weight: 900, fill: row[2] });
        doc.text(141, y + 10.5, row[1], { size: 1.85, fill: colors.ink });
        if (index < exampleRows.length - 1) downArrow(doc, 165, y + 15, y + 19, row[2]);
    });
    card(doc, 137, 176, 56, 18, { fill: assetSoft, stroke: assetColor, component: 'precedence-source-note' });
    doc.text(141, 182, ['Geen dubbele handmatige correctie nodig.', 'Pas de bron aan die de waarde werkelijk bezit.'], { size: 1.8, weight: 800, fill: assetColor, lh: 2.5 });

    card(doc, 12, 207, 90, 32, { fill: componentSoft, stroke: componentColor, component: 'precedence-instance-rule' });
    doc.text(18, 215, 'Fysiek component vóór definitiestandaard', { size: 2.35, weight: 900, fill: componentColor });
    doc.text(18, 223, ['Een vastgelegde waarde van het fysieke component', 'gaat vóór de standaard van zijn componentdefinitie.'], { size: 2.05, fill: colors.ink, lh: 3 });
    card(doc, 108, 207, 90, 32, { fill: componentSoft, stroke: componentColor, component: 'precedence-child-rule' });
    doc.text(114, 215, 'Onderliggend component vóór ouder', { size: 2.35, weight: 900, fill: componentColor });
    doc.text(114, 223, ['Levert een onderliggend component dezelfde specificatie,', 'dan vervangt die bijdrage de dubbele bijdrage van de ouder.'], { size: 2.05, fill: colors.ink, lh: 3 });

    continuationFooter(doc, 'kies de taakgids die past bij wat je moet veranderen.', [
        guideReference('CAT-02', { width: 57 }),
        guideReference('CAT-03', { width: 48 }),
        guideReference('CAT-04', { width: 63, row: 2 }),
    ], 7, 8);
    return doc.render();
}

function cat00Page8() {
    const def = definitions['CAT-00'];
    const doc = docFor('cat00-page8');
    header(doc, def, 8, 8, [
        { label: 'Rol', value: 'Supervisor' },
        { label: 'Nodig', value: 'Productlabel of fabrikantbron' },
        { label: 'Vooraf', value: 'Deel 1 tot en met 7' },
    ]);
    pageHeading(doc, 8, 'Kies de juiste vervolggids', 'Kies op basis van het object dat ontbreekt of gewijzigd moet worden. In voorbereiding betekent: nog niet beschikbaar.');

    const routes = [
        ['CAT-01', 'Basismodel of exact modelnummer', 'Maak een ontbrekende product/generatie of fabrikantvariant.'],
        ['CAT-02', 'Specificatie van één modelnummer', 'Koppel directe attributen en verwachte componenten.'],
        ['CAT-03', 'Attribuutdefinitie', 'Maak of onderhoud de herbruikbare betekenis, invoer en regels.'],
        ['CAT-04', 'Componentdefinitie', 'Maak of onderhoud een herbruikbare componentdefinitie en haar bijdragen.'],
        ['CAT-05', 'Variant of lifecycle', 'Beheer standaard, actief, verouderd en veilige verwijdering.'],
        ['CAT-06', 'Bron of controle', 'Controleer codes en specificaties tegen betrouwbare productinformatie.'],
    ];
    routes.forEach((route, index) => {
        const column = index % 2;
        const row = Math.floor(index / 2);
        const x = 12 + column * 95;
        const y = 82 + row * 41;
        card(doc, x, y, 91, 35, { fill: column ? colors.white : catSoft, stroke: catColor, component: 'guide-route-card' });
        doc.guideChip(x + 5, y + 5, 81, guideReference(route[0]));
        doc.text(x + 5, y + 18, route[1], { size: 2.15, weight: 900, fill: catColor });
        doc.text(x + 5, y + 25, route[2], { size: 1.8, fill: colors.muted, lh: 2.4 });
        if (route[0] !== 'CAT-01') doc.text(x + 86, y + 31, 'In voorbereiding', { size: 1.55, weight: 800, fill: colors.orange, anchor: 'end' });
    });

    card(doc, 12, 208, 186, 27, { component: 'asset-route-card' });
    doc.guideChip(17, 214, 76, guideReference('AST-03'));
    doc.text(99, 217, 'Fysiek asset', { size: 2.25, weight: 900, fill: GUIDE_FAMILIES.AST.color });
    doc.text(99, 224, 'Gebruik dit pas wanneer basismodel, exact modelnummer en benodigde baseline bestaan.', { size: 1.9, fill: colors.muted });
    doc.text(17, 231, 'Zoek eerst naar bestaande namen, codes en definities. Maak geen record wanneer productlabel en fabrikantbron de waarde niet bevestigen.', { size: 1.85, weight: 800, fill: colors.orange });

    finalFooter(doc, 'Je kunt het object, de definitie, de waarde en de juiste vervolggids van elkaar onderscheiden.', related.cat00, 8, 8);
    return doc.render();
}

function cat01Context() {
    return [
        { label: 'Rol', value: 'Supervisor' },
        { label: 'Nodig', value: 'Productlabel + bron' },
        { label: 'Vooraf', value: 'CAT-00 Catalogus begrijpen', guide: guideReference('CAT-00') },
    ];
}

function cat01StageHeading(doc, number, title, subtitle) {
    const stages = ['Zoeken', 'Route', 'Basismodel', 'Modelnummer', 'Controleren'];
    stages.forEach((label, index) => {
        const x = 12 + index * 37.2;
        const active = index + 1 === number;
        doc.rect(x, 60, 35.5, 5.5, active ? catSoft : colors.faint, active ? catColor : colors.line, 0.35, 1.2);
        doc.centeredText(x + 17.75, 62.9, `${index + 1} ${label}`, { size: 1.45, weight: active ? 900 : 700, fill: active ? catColor : colors.muted });
    });
    doc.familyBadge(15.5, 71, 'CAT', { radius: 3.5, fontSize: 1.9, fill: catSoft });
    doc.text(22, 72.2, title, { size: 4.7, weight: 900 });
    doc.text(22, 78, subtitle, { size: 2.2, fill: colors.muted });
}

function cat01Page1() {
    const def = definitions['CAT-01'];
    const doc = docFor('cat01-page1');
    header(doc, def, 1, 5, cat01Context());
    cat01StageHeading(doc, 1, 'Zoek de bestaande catalogusroute', 'Open de juiste lijst en controleer naam, generatie en volledige fabrikantcode.');

    const first = stepCard(doc, 1, 'Open Asset modellen', 83, 62);
    doc.text(first.bodyX, 98, [
        'Open vanaf het dashboard:',
        'Instellingen > Asset modellen.',
        '',
        'Hier staan herbruikbare Basismodellen;',
        'fysieke assets staan onder Apparaten.',
    ], { size: 2.2, fill: colors.muted, lh: 3 });
    visual(doc, 'list', '1A', 'Open Instellingen en kies Asset modellen.',
        { x: 101, y: 90, w: 87, h: 48 },
        { x: 0, y: 520, w: 520, h: 260 },
        [{ x: 13, y: 681, w: 170, h: 33, padding: 4, target: 'Asset modellen' }]);

    const second = stepCard(doc, 2, 'Zoek naam en exacte code', 152, 89);
    doc.text(second.bodyX, 168, [
        '1. Zoek fabrikant + product + generatie.',
        '   Voorbeeld: HP ProBook 450 G8.',
        '2. Vergelijk de volledige code inclusief suffix.',
        '   Voorbeeld: 2E9F8EA#ABH.',
        '3. Controleer naam, Model Nr., aantal',
        '   Model Numbers en categorie.',
    ], { size: 2.12, fill: colors.muted, lh: 3 });
    visual(doc, 'list', '2A', 'Zoek naam en exacte code voordat je de + gebruikt.',
        { x: 91, y: 161, w: 97, h: 59 },
        { x: 230, y: 145, w: 900, h: 500 },
        [{ x: 782, y: 185, w: 186, h: 36, padding: 4, target: 'Model search' }]);
    doc.text(91, 230, ['Vergelijkbare naam gevonden? Controleer eerst de volledige fabrikantcode en generatie.', 'Maak pas daarna een nieuw record.'], { size: 1.9, weight: 800, fill: colors.orange, lh: 2.55 });

    continuationFooter(doc, 'kies de juiste bestaande of nieuwe route.', [
        guideReference('CAT-00', { width: 52 }),
        guideReference('CAT-06', { width: 69 }),
    ], 1, 5);
    return doc.render();
}

function cat01Page2() {
    const def = definitions['CAT-01'];
    const doc = docFor('cat01-page2');
    header(doc, def, 2, 5, cat01Context());
    cat01StageHeading(doc, 2, 'Kies een van drie routes', 'Wat al bestaat bepaalt waar je verdergaat; niet iedere variant vraagt een nieuw Basismodel.');

    const step = stepCard(doc, 3, 'Kies het juiste resultaat van je zoekactie', 83, 160);

    card(doc, 23, 96, 166, 27, { fill: catSoft, stroke: catColor, component: 'route-card' });
    doc.text(29, 104, 'A Exacte code bestaat al', { size: 2.75, weight: 900, fill: catColor });
    doc.text(29, 111, ['Open en controleer de bestaande rij. Maak niets nieuws.', 'Ga verder bij stap 7.'], { size: 2.05, fill: colors.muted, lh: 2.7 });

    card(doc, 23, 129, 166, 58, { fill: catSoft, stroke: catColor, component: 'route-card' });
    doc.text(29, 137, 'B Basismodel bestaat; exacte code ontbreekt', { size: 2.75, weight: 900, fill: catColor });
    doc.text(29, 144, ['Controleer fabrikant, product en generatie.', 'Kies daarna Create Model Number en ga naar stap 5.'], { size: 2.05, fill: colors.muted, lh: 2.7 });
    visual(doc, 'detail', '3A', 'Gebruik het bestaande Basismodel als product en generatie gelijk zijn.',
        { x: 104, y: 136, w: 79, h: 43 },
        { x: 0, y: 80, w: 1050, h: 426 },
        [{ x: 870, y: 177, w: 142, h: 31, padding: 4, target: 'Create Model Number' }],
        { captionHeight: 5.5, fit: 'contain' });

    card(doc, 23, 193, 166, 38, { fill: colors.faint, stroke: colors.line, component: 'route-card' });
    doc.text(29, 201, 'C Basismodel ontbreekt', { size: 2.75, weight: 900, fill: catColor });
    doc.text(29, 208, ['Gebruik de + alleen als fabrikant, product en generatie', 'werkelijk ontbreken. Ga daarna naar stap 4.'], { size: 2.05, fill: colors.muted, lh: 2.7 });
    visual(doc, 'list', '3B', 'De + maakt een nieuw Basismodel.',
        { x: 106, y: 199, w: 77, h: 25 },
        { x: 660, y: 155, w: 705, h: 110 },
        [{ x: 1135, y: 185, w: 39, h: 36, padding: 4, target: 'Create model plus' }],
        { captionHeight: 5, fit: 'contain' });

    doc.text(23, 237, 'Andere geprinte SKU-code = ander exact modelnummer.', { size: 1.9, weight: 900, fill: catColor });
    doc.text(105, 237, 'Later vervangen RAM/opslag = componentwijziging op het asset.', { size: 1.82, weight: 900, fill: componentColor });

    continuationFooter(doc, 'maak alleen een ontbrekend Basismodel.', [
        guideReference('CAT-00', { width: 52 }),
        guideReference('CAT-06', { width: 69 }),
    ], 2, 5);
    return doc.render();
}

function cat01Page3() {
    const def = definitions['CAT-01'];
    const doc = docFor('cat01-page3');
    header(doc, def, 3, 5, cat01Context());
    cat01StageHeading(doc, 3, 'Maak alleen een ontbrekend Basismodel', 'Leg de herbruikbare productfamilie en generatie vast; de exacte code volgt daarna.');

    const step = stepCard(doc, 4, 'Vul de herbruikbare productidentiteit in', 83, 158);
    doc.text(step.bodyX, 98, 'Gebruik alleen de drie actieve velden voor deze catalogusroute.', { size: 2.2, fill: colors.muted });

    card(doc, 23, 106, 166, 39, { fill: catSoft, stroke: catColor, component: 'field-summary' });
    const requiredFields = [
        ['Naam basismodel', 'HP ProBook 450 G8', 'product + generatie'],
        ['Categorienaam', 'Laptops', 'juiste apparaattype'],
        ['Fabrikant', 'HP', 'werkelijke producent'],
    ];
    requiredFields.forEach((row, index) => {
        const y = 115 + index * 10;
        if (index) doc.line(29, y - 4.2, 183, y - 4.2, '#D8C7E5', 0.3);
        doc.text(29, y, row[0], { size: 2.05, weight: 900, fill: catColor });
        doc.text(64, y, row[1], { size: 2.05, weight: 800, fill: colors.ink });
        doc.text(112, y, row[2], { size: 1.9, fill: colors.muted });
    });

    visual(doc, 'create', '4A', 'Vul product + generatie, categorie en fabrikant in; kies daarna Opslaan.',
        { x: 23, y: 152, w: 166, h: 62 },
        { x: 280, y: 145, w: 850, h: 270 },
        [
            { x: 500, y: 255, w: 505, h: 145, padding: 4, target: 'Actieve basismodelvelden' },
            { x: 1018, y: 177, w: 93, h: 34, padding: 4, target: 'Opslaan' },
        ],
        { fit: 'contain' });

    doc.text(23, 222, ['Een algemene afbeelding is optioneel en helpt alleen bij herkenning.', 'Naam bevat geen asset tag, serienummer, exacte SKU-suffix of zelfgemaakte configuratiecode.'], { size: 1.95, fill: colors.muted, lh: 2.65 });
    doc.text(23, 235, 'Juiste categorie of fabrikant ontbreekt? Kies geen bijna-gelijke vervanger.', { size: 2, weight: 900, fill: colors.orange });

    continuationFooter(doc, 'voeg het exacte modelnummer toe.', [
        guideReference('CAT-00', { width: 52 }),
        guideReference('CAT-06', { width: 69 }),
    ], 3, 5);
    return doc.render();
}

function cat01Page4() {
    const def = definitions['CAT-01'];
    const doc = docFor('cat01-page4');
    header(doc, def, 4, 5, cat01Context());
    cat01StageHeading(doc, 4, 'Voeg de exacte fabrikantvariant toe', 'De geprinte code is de identiteit; het label helpt mensen de variant herkennen.');

    const step5 = stepCard(doc, 5, 'Open Create Model Number', 83, 46);
    doc.text(step5.bodyX, 98, ['Open het opgeslagen of hergebruikte Basismodel.', 'Kies Create Model Number boven de exacte rijen.'], { size: 2.15, fill: colors.muted, lh: 2.9 });
    visual(doc, 'detail', '5A', 'Open de exacte variant vanaf het juiste Basismodel.',
        { x: 105, y: 90, w: 83, h: 32 },
        { x: 0, y: 80, w: 1050, h: 426 },
        [{ x: 870, y: 177, w: 142, h: 31, padding: 4, target: 'Create Model Number' }],
        { captionHeight: 5.5 });

    const step6 = stepCard(doc, 6, 'Vul code en herkenningslabel in', 136, 105);
    card(doc, 23, 151, 63, 59, { fill: catSoft, stroke: catColor, component: 'field-summary' });
    doc.text(29, 160, 'Code', { size: 2.25, weight: 900, fill: catColor });
    doc.text(29, 166, ['Exacte fabrikant-/SKU-code', 'inclusief suffix.', 'Voorbeeld: 2E9F8EA#ABH'], { size: 1.95, fill: colors.muted, lh: 2.55 });
    doc.text(29, 179, 'Aa', { size: 2.25, weight: 900, fill: catColor });
    doc.text(39, 179, 'alleen voor bewust kleine letters', { size: 1.85, fill: colors.muted });
    doc.text(29, 188, 'Label', { size: 2.25, weight: 900, fill: catColor });
    doc.text(29, 194, ['Leesbare herkenning, bijvoorbeeld', 'product + CPU + RAM + opslag.', 'Specificaties worden apart vastgelegd.'], { size: 1.9, fill: colors.muted, lh: 2.5 });

    visual(doc, 'numberCreate', '6A', 'Code is exact; Label helpt de variant herkennen.',
        { x: 92, y: 145, w: 96, h: 70 },
        { x: 380, y: 150, w: 760, h: 380 },
        [
            { x: 508, y: 301, w: 470, h: 35, padding: 4, target: 'Exacte code' },
            { x: 508, y: 385, w: 470, h: 35, padding: 4, target: 'Herkenningslabel' },
            { x: 1018, y: 478, w: 93, h: 34, padding: 4, target: 'Opslaan' },
        ],
        { fit: 'contain' });
    callout(doc, 92, 216, 96, ['Geen serienummer, Product ID, Inbit-tag', 'of zelfgemaakte fabrikantcode.'], { height: 15, title: 'Identiteiten niet verwisselen' });
    doc.text(23, 235, 'De eerste exacte code wordt automatisch de standaardrij.', { size: 1.95, weight: 900, fill: catColor });
    doc.guideChip(119, 232, 69, guideReference('CAT-05'));

    continuationFooter(doc, 'controleer de opgeslagen identiteit.', [
        guideReference('CAT-00', { width: 52 }),
        guideReference('CAT-05', { width: 69 }),
    ], 4, 5);
    return doc.render();
}

function cat01Page5() {
    const def = definitions['CAT-01'];
    const doc = docFor('cat01-page5');
    header(doc, def, 5, 5, cat01Context());
    cat01StageHeading(doc, 5, 'Controleer en kies het volgende object', 'Bekijk Basismodel, exacte rij en informatiepaneel voordat je specificaties of assets toevoegt.');

    const step7 = stepCard(doc, 7, 'Controleer de opgeslagen catalogusidentiteit', 83, 105);
    doc.text(step7.bodyX, 98, ['Basismodel: product + generatie; juiste categorie en fabrikant.', 'Modelnummer: complete code + suffix; duidelijk label; actief; geen duplicaat.'], { size: 2.05, fill: colors.muted, lh: 2.8 });
    visual(doc, 'detail', '7A', 'Controleer code, label en actieve/standaardstatus.',
        { x: 23, y: 111, w: 112, h: 67 },
        { x: 45, y: 92, w: 1015, h: 290 },
        [{ x: 72, y: 235, w: 930, h: 54, padding: 4, target: 'Exact modelnummer en label' }],
        { fit: 'contain' });
    visual(doc, 'detail', '7B', 'Controleer categorie en fabrikant.',
        { x: 140, y: 111, w: 49, h: 67 },
        { x: 1035, y: 140, w: 330, h: 230 },
        [{ x: 1050, y: 163, w: 300, h: 200, padding: 4, target: 'Categorie en fabrikant' }],
        { fit: 'contain' });
    doc.text(23, 183, 'De eerste rij kan automatisch als standaard worden getoond; dit is geen extra invoerveld.', { size: 1.88, weight: 800, fill: catColor });

    const step8 = stepCard(doc, 8, 'Kies het volgende object', 195, 38);
    doc.text(step8.bodyX, 210, 'Specificatie ontbreekt? Gebruik CAT-02. Eén fysiek apparaat registreren? Gebruik AST-03.', { size: 2.05, fill: colors.muted });
    doc.guideChip(23, 217, 74, guideReference('CAT-02'));
    doc.text(99, 221, 'In voorbereiding', { size: 1.75, weight: 900, fill: colors.orange });
    doc.guideChip(124, 217, 64, guideReference('AST-03'));

    doc.text(12, 239, ['Kopieer model kopieert geen modelnummers, modelnummerbeelden, specificatiewaarden of verwachte componenten.', 'Controleer ieder onderliggend record handmatig.'], { size: 1.78, weight: 800, fill: colors.orange, lh: 2.35 });

    finalFooter(doc, 'Eén juist Basismodel bevat de geverifieerde exacte code zonder duplicaat.', related.cat01, 5, 5);
    return doc.render();
}

const guides = {
    'CAT-00': {
        definition: definitions['CAT-00'],
        pages: [
            cat00Page1(),
            cat00Page2(),
            cat00Page3(),
            cat00Page4(),
            cat00Page5(),
            cat00Page6(),
            cat00Page7(),
            cat00Page8(),
        ],
        outputFile: `CAT-00-catalogus-begrijpen-v${cat00Version}-draft.pdf`,
        evidence: [
            sourceIds.detail,
            sourceIds.spec,
            sourceIds.specComponents,
            sourceIds.attributeList,
            sourceIds.componentDefinitionList,
            sourceIds.componentInstallResult,
        ],
    },
    'CAT-01': {
        definition: definitions['CAT-01'],
        pages: [cat01Page1(), cat01Page2(), cat01Page3(), cat01Page4(), cat01Page5()],
        outputFile: `CAT-01-model-en-modelnummer-aanmaken-v${cat01Version}-draft.pdf`,
        evidence: [sourceIds.list, sourceIds.detail, sourceIds.create, sourceIds.numberCreate],
    },
};

function htmlFor(pages) {
    return `<!doctype html><html><head><meta charset="utf-8"><style>
        @page { size: A4; margin: 0; }
        html, body { margin: 0; padding: 0; background: #dfe3ea; }
        .page { width: 210mm; height: 297mm; overflow: hidden; background: #fff; break-after: page; page-break-after: always; }
        .page:last-child { break-after: auto; page-break-after: auto; }
        .page > svg { display: block; width: 210mm; height: 297mm; }
    </style></head><body>${pages.map((svg) => `<div class="page">${svg}</div>`).join('')}</body></html>`;
}

async function inspectPageGeometry(page) {
    const result = await page.evaluate(() => [...document.querySelectorAll('.page')].map((pageNode, index) => {
        const errors = [];
        const svg = pageNode.querySelector('svg');
        if (!svg) return { page: index + 1, errors: ['Missing SVG page.'] };
        [...svg.querySelectorAll('text')].forEach((node) => {
            const box = node.getBBox();
            if (box.x < -0.1 || box.y < -0.1 || box.x + box.width > 210.1 || box.y + box.height > 297.1) {
                errors.push(`Text outside A4 bounds: ${node.textContent.trim().slice(0, 60)}`);
            }
        });
        [...svg.querySelectorAll('[data-component]')].forEach((node) => {
            if (node.dataset.component === 'focus-mark') return;
            const box = node.getBBox();
            if (box.x < -0.1 || box.y < -0.1 || box.x + box.width > 210.1 || box.y + box.height > 297.1) {
                errors.push(`${node.dataset.component} exceeds A4 bounds.`);
            }
        });
        const title = svg.querySelector('[data-component="guide-title"]');
        if (title) {
            const box = title.getBBox();
            if (box.x + box.width > 160) errors.push(`Guide title overlaps the version panel at x=${(box.x + box.width).toFixed(2)}.`);
        }
        [...svg.querySelectorAll('[data-component="focus-mark"]')].forEach((node) => {
            if (!node.getAttribute('data-target')) errors.push('Focus mark is missing a target name.');
        });
        return { page: index + 1, errors };
    }));
    const errors = result.flatMap((entry) => entry.errors.map((error) => `Page ${entry.page}: ${error}`));
    if (errors.length) throw new Error(`Catalog page geometry failed:\n- ${errors.join('\n- ')}`);
    return result;
}

async function renderGuide(browser, code, guide) {
    const versionNumber = guide.definition.version.match(/v(\d+)/i)?.[1] ?? '1';
    const guideDir = path.join(outputRoot, `${code.toLowerCase()}-v${versionNumber}`);
    fs.mkdirSync(guideDir, { recursive: true });
    fs.mkdirSync(repoPdfOutputRoot, { recursive: true });

    guide.pages.forEach((svg, index) => {
        fs.writeFileSync(path.join(guideDir, `${code.toLowerCase()}-page-${index + 1}.svg`), svg, 'utf8');
    });
    const htmlPath = path.join(guideDir, `${code.toLowerCase()}-v${versionNumber}.html`);
    const proofPdf = path.join(guideDir, guide.outputFile);
    const repoPdf = path.join(repoPdfOutputRoot, guide.outputFile);
    fs.writeFileSync(htmlPath, htmlFor(guide.pages), 'utf8');

    const page = await browser.newPage({ viewport: { width: 1240, height: 1754 }, deviceScaleFactor: 2 });
    await page.goto(pathToFileURL(htmlPath).href, { waitUntil: 'load' });
    const componentQa = await inspectRenderedGuideComponents(page);
    const geometryQa = await inspectPageGeometry(page);
    const pageLocators = await page.locator('.page').all();
    for (let index = 0; index < pageLocators.length; index += 1) {
        await pageLocators[index].screenshot({ path: path.join(guideDir, `${code.toLowerCase()}-page-${index + 1}.png`) });
    }
    await page.pdf({
        path: proofPdf,
        width: '210mm',
        height: '297mm',
        printBackground: true,
        preferCSSPageSize: true,
        margin: { top: '0', right: '0', bottom: '0', left: '0' },
    });
    await page.close();
    fs.copyFileSync(proofPdf, repoPdf);

    const summary = {
        code,
        version: guide.definition.version,
        generatedOn,
        pageCount: guide.pages.length,
        layout: code === 'CAT-00' ? 'reference-chapter' : 'extended-admin-flow',
        componentQa,
        geometryQa,
        evidence: guide.evidence,
        outputs: { htmlPath, proofPdf, repoPdf },
        knownGaps: code === 'CAT-00'
            ? ['CAT-06 source-recording policy remains a product/operations decision.']
            : ['CAT-02 is specified but not yet generated.'],
    };
    fs.writeFileSync(path.join(guideDir, 'generation-summary.json'), `${JSON.stringify(summary, null, 2)}\n`, 'utf8');
    return summary;
}

const requested = selectedGuide ? [selectedGuide] : Object.keys(guides);
requested.forEach((code) => {
    if (!guides[code]) throw new Error(`Unknown catalog guide filter: ${code}`);
});

const browser = await chromium.launch(browserLaunchOptions({ headless: true }));
try {
    const summaries = [];
    for (const code of requested) summaries.push(await renderGuide(browser, code, guides[code]));
    console.log(JSON.stringify({ outputRoot, guides: summaries }, null, 2));
} finally {
    await browser.close();
}
