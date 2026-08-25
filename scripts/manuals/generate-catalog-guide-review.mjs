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

const generatedOn = process.env.SNIPEIT_GUIDE_DATE ?? '2026-08-20';
const outputRoot = process.env.SNIPEIT_GUIDE_OUT_DIR ?? guideOutputDir('catalog-guide-review');
const selectedGuide = process.env.SNIPEIT_GUIDE_FILTER?.trim().toUpperCase() || null;
const cat00Version = process.env.SNIPEIT_CAT00_VERSION ?? '2';
const cat01Version = process.env.SNIPEIT_CAT01_VERSION ?? '2';
const colors = GUIDE_TOKENS.colors;
const catColor = GUIDE_FAMILIES.CAT.color;
const catSoft = GUIDE_FAMILIES.CAT.fill;

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
            { number: '1', title: 'De catalogus in een beeld', visuals: [] },
            { number: '2', title: 'Basismodel, modelnummer en asset', visuals: [
                { label: '2A', caption: 'Basismodel en exacte modelnummerrij.' },
                { label: '2B', caption: 'Categorie en fabrikant van het basismodel.' },
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
        purpose: 'Maak een modelroute zonder dubbele of verzonnen catalogusgegevens',
        related: related.cat01,
        steps: [
            { number: '1', title: 'Open Basismodellen', visuals: [{ label: '1A', caption: 'Instellingen > Asset modellen.' }] },
            { number: '2', title: 'Zoek voordat je aanmaakt', visuals: [{ label: '2A', caption: 'Zoek naam en exact modelnummer.' }] },
            { number: '3', title: 'Kies de route', visuals: [{ label: '3A', caption: 'Bestaand basismodel.' }, { label: '3B', caption: 'Nieuw basismodel.' }] },
            { number: '4', title: 'Vul het basismodel in', visuals: [{ label: '4A', caption: 'Algemene modelgegevens.' }] },
            { number: '5', title: 'Open Create Model Number', visuals: [{ label: '5A', caption: 'Open de exacte-variantroute.' }] },
            { number: '6', title: 'Vul code en label in', visuals: [{ label: '6A', caption: 'Exacte code en leesbaar label.' }] },
            { number: '7', title: 'Controleer het resultaat', visuals: [
                { label: '7A', caption: 'Code, label en status.' },
                { label: '7B', caption: 'Categorie en fabrikant.' },
            ] },
            { number: '8', title: 'Bouw de specificatie', visuals: [] },
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
    doc.imageBadge(imageFrame.x + 1.2, imageFrame.y + 1.2, label, catColor);
    doc.text(frame.x + 1, frame.y + frame.h - 1.7, caption, { size: 2.05, weight: 700, fill: colors.muted });
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
    card(doc, x, y, w, h, { fill: options.fill ?? colors.faint, stroke: options.stroke ?? colors.line, component: 'definition-row' });
    if (options.family) {
        doc.familyBadge(x + 5, y + h / 2, options.family, { radius: 2.6, fontSize: 1.5, fill: colors.white });
    } else {
        doc.circle(x + 5, y + h / 2, 2.7, options.color ?? catColor, 0.6, colors.white);
        doc.centeredText(x + 5, y + h / 2, options.icon ?? label.slice(0, 1), { size: 2.1, weight: 900, fill: options.color ?? catColor });
    }
    doc.text(x + 10, y + 6, label, { size: 2.5, weight: 900, fill: options.color ?? colors.ink });
    doc.text(x + 10, y + 11.2, lines, { size: 2, fill: colors.muted, lh: 2.65 });
}

function arrow(doc, x1, y1, x2, y2) {
    doc.line(x1, y1, x2, y2, catColor, 0.8);
    doc.raw(`<polygon points="${x2},${y2} ${x2 - 2.2},${y2 - 1.4} ${x2 - 2.2},${y2 + 1.4}" fill="${catColor}"/>`);
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
    pageHeading(doc, 1, 'De catalogus in één beeld', 'De catalogus beschrijft eerst wat herbruikbaar is en daarna wat voor één modelnummer of asset geldt.');

    card(doc, 12, 82, 186, 30, { fill: catSoft, stroke: catColor, component: 'definition-layer' });
    doc.text(17, 89, 'Herbruikbare bouwstenen', { size: 2.8, weight: 900, fill: catColor });
    simpleRow(doc, 17, 94, 82, 'Attribuutdefinitie', ['Betekenis, datatype, eenheid, keuzes en bereik.', 'Voorbeeld: Werkgeheugen in GB.'], { h: 14, icon: 'A' });
    simpleRow(doc, 111, 94, 82, 'Componentdefinitie', ['Herbruikbaar type onderdeel met eigen attributen.', 'Voorbeeld: RAM 8GB DDR4.'], { h: 14, icon: 'C' });

    card(doc, 12, 118, 186, 38, { component: 'identity-layer' });
    doc.text(17, 125, 'Product en fysieke apparaten', { size: 2.8, weight: 900, fill: catColor });
    simpleRow(doc, 17, 131, 48, 'Basismodel', ['Product + generatie'], { h: 18, icon: 'M' });
    simpleRow(doc, 80, 131, 52, 'Modelnummer', ['Exacte fabrikantvariant'], { h: 18, icon: '#' });
    simpleRow(doc, 147, 131, 46, 'Asset', ['Eén fysiek apparaat'], { h: 18, icon: 'I' });
    arrow(doc, 66, 140, 79, 140);
    arrow(doc, 133, 140, 146, 140);

    card(doc, 12, 162, 88, 67, { fill: colors.faint, stroke: colors.line, component: 'model-number-contents' });
    doc.text(18, 171, 'Het modelnummer brengt samen', { size: 2.75, weight: 900, fill: catColor });
    doc.text(18, 180, ['Directe attribuutwaarden', 'Vaste feiten van deze exacte variant.', '', 'Verwachte componenten', 'De fabrieksbaseline van vervangbare onderdelen.', '', 'Componentbijdragen', 'Waarden die een verwachte component aan de specificatie levert.'], { size: 2.05, fill: colors.ink, lh: 3.15 });

    card(doc, 110, 162, 88, 67, { fill: colors.faint, stroke: colors.line, component: 'asset-contents' });
    doc.text(116, 171, 'Het asset voegt de werkelijkheid toe', { size: 2.75, weight: 900, fill: catColor });
    doc.text(116, 180, ['Aangenomen of geplaatste componenten', 'Wat werkelijk in dit apparaat zit.', '', 'Asset override', 'Alleen een toegestane uitzondering op één asset.', '', 'Workflowresultaten', 'Test, conditie, notitie en foto; geen catalogusdefinitie.'], { size: 2.05, fill: colors.ink, lh: 3.15 });

    callout(doc, 12, 228, 186, [
        'Een definitie beschrijft de vorm en betekenis. Een waarde, verwachting of geplaatst onderdeel gebruikt die definitie.',
    ], { height: 16, title: 'Definitie is niet hetzelfde als ingevulde waarde', icon: 'i', color: catColor, fill: catSoft, stroke: catColor });

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
    simpleRow(doc, xs[0], 82, widths[0], 'Basismodel', ['Product + generatie', 'ProBook 450 G8'], { h: 22, icon: 'M' });
    simpleRow(doc, xs[1], 82, widths[1], 'Modelnummer', ['Exacte fabrikantvariant', '2E9F8EA#ABH'], { h: 22, icon: '#' });
    simpleRow(doc, xs[2], 82, widths[2], 'Asset', ['Eén fysiek apparaat', 'tag + serienummer'], { h: 22, icon: 'I' });
    arrow(doc, 68.5, 93, 76, 93);
    arrow(doc, 133.5, 93, 141, 93);

    card(doc, 12, 108, 186, 46, { fill: catSoft, stroke: catColor, component: 'example-hierarchy' });
    doc.text(17, 115, 'Voorbeelden: elk apparaattype gebruikt dezelfde structuur', { size: 2.8, weight: 900, fill: catColor });
    const columnXs = [17, 78, 139];
    ['Basismodel', 'Exact modelnummer', 'Fysiek asset'].forEach((label, index) => doc.text(columnXs[index], 122, label, { size: 2.35, weight: 900, fill: catColor }));
    doc.line(17, 125, 193, 125, catColor, 0.45);
    doc.line(73, 118, 73, 149, catColor, 0.3);
    doc.line(134, 118, 134, 149, catColor, 0.3);
    const exampleRows = [
        [['HP ProBook 450 G8', 'Laptop, generatie G8'], ['2E9F8EA#ABH', 'i5 / 8 GB / 256 GB'], ['INBIT-HG0042', 'S/N 5CD1234ABC']],
        [['Samsung Galaxy A5', 'Telefoon, modeljaar 2017'], ['SM-A520F', '32 GB / Black Sky'], ['INBIT-TF0187', 'S/N R58M1234ABC']],
    ];
    exampleRows.forEach((row, rowIndex) => row.forEach((cell, columnIndex) => {
        const y = 131 + rowIndex * 14;
        doc.text(columnXs[columnIndex], y, cell[0], { size: 2.35, weight: 900 });
        doc.text(columnXs[columnIndex], y + 5, cell[1], { size: 1.95, fill: colors.muted });
    }));
    doc.line(17, 139, 193, 139, colors.line, 0.35);

    visual(doc, 'detail', '2A', 'Basismodel in het kruimelpad; exacte code en label in één rij.',
        { x: 12, y: 160, w: 130, h: 44 },
        { x: 50, y: 105, w: 985, h: 255 },
        [
            { x: 390, y: 112, w: 245, h: 38, padding: 4, target: 'Naam basismodel' },
            { x: 72, y: 235, w: 500, h: 54, padding: 4, target: 'Exact modelnummer en label' },
        ], { fit: 'contain' });
    visual(doc, 'detail', '2B', 'Categorie en fabrikant horen bij het basismodel.',
        { x: 148, y: 160, w: 50, h: 72 },
        { x: 1048, y: 108, w: 305, h: 410 },
        [{ x: 1053, y: 219, w: 292, h: 54, padding: 4, target: 'Categorie en fabrikant' }]);

    card(doc, 12, 210, 130, 22, { fill: catSoft, stroke: catColor, component: 'relationship-note' });
    doc.text(17, 217, 'Eén basismodel kan meerdere echte modelnummers hebben.', { size: 2.15, weight: 900, fill: catColor });
    doc.text(17, 223, ['Meerdere assets mogen hetzelfde modelnummer gebruiken.', 'Een latere RAM- of opslagwissel verandert het fysieke asset, niet de fabrikantcode.'], { size: 1.95, fill: colors.muted, lh: 2.55 });

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

    card(doc, 12, 82, 186, 48, { fill: catSoft, stroke: catColor, component: 'attribute-definition-explanation' });
    doc.text(17, 90, 'Attribuutdefinitie = herbruikbare afspraak', { size: 2.9, weight: 900, fill: catColor });
    const definitionFields = [
        ['Label', 'zichtbare naam, bijvoorbeeld Werkgeheugen'],
        ['Sleutel', 'stabiele systeemnaam; normaal automatisch aangemaakt'],
        ['Datatype', 'ja/nee, geheel getal, decimaal, keuzelijst of tekst'],
        ['Eenheid en grenzen', 'bijvoorbeeld GB, minimum, maximum en stap'],
        ['Bereik', 'voor welke categorieën de definitie beschikbaar is'],
        ['Keuzes', 'toegestane waarden voor een keuzelijst'],
        ['Asset override', 'of één asset een afwijkende waarde mag krijgen'],
        ['Componentweergave', 'toon waarden of herkenbare componentlabels'],
    ];
    definitionFields.forEach((row, index) => {
        const leftColumn = index < 4;
        const x = leftColumn ? 17 : 112;
        const y = 98 + (leftColumn ? index : index - 4) * 7;
        doc.text(x, y, `${row[0]}:`, { size: 1.9, weight: 900 });
        doc.text(x + (leftColumn ? 28 : 28), y, row[1], { size: 1.7, fill: colors.muted });
    });

    card(doc, 12, 136, 186, 32, { component: 'attribute-value-locations' });
    doc.text(17, 144, 'Dezelfde definitie kan op verschillende plekken een waarde krijgen', { size: 2.7, weight: 900, fill: catColor });
    const valuePlaces = [
        ['Modelnummer', 'directe vaste waarde'],
        ['Componentdefinitie', 'waarde op een herbruikbare componentdefinitie'],
        ['Asset', 'toegestane uitzondering'],
        ['Workflow', 'verwachting of resultaat, apart van de catalogusspecificatie'],
    ];
    valuePlaces.forEach((row, index) => {
        const x = 17 + index * 44;
        doc.text(x, 152, row[0], { size: 2, weight: 900 });
        doc.text(x, 158, row[1], { size: 1.75, fill: colors.muted, lh: 2.2 });
    });

    card(doc, 12, 174, 186, 24, { fill: colors.faint, stroke: colors.line, component: 'datatype-table' });
    doc.text(17, 181, 'Datatype bepaalt de invoer', { size: 2.45, weight: 900, fill: catColor });
    const datatypes = [
        ['Ja/nee', '5G-ondersteuning'], ['Geheel getal', '8 GB'], ['Decimaal', '1,74 kg'], ['Keuzelijst', 'DDR4'], ['Tekst', 'DisplayPort 1.4b'],
    ];
    datatypes.forEach((row, index) => {
        const x = 17 + index * 35;
        doc.text(x, 188, row[0], { size: 1.85, weight: 900 });
        doc.text(x, 193, row[1], { size: 1.65, fill: colors.muted });
    });

    visual(doc, 'attributeList', '3A', 'De lijst toont naam, sleutel, datatype, categorie en gebruiksregels.',
        { x: 12, y: 204, w: 90, h: 38 },
        { x: 60, y: 145, w: 1180, h: 430 },
        [{ x: 72, y: 198, w: 930, h: 68, padding: 3, target: 'Kolommen van attribuutdefinities' }]);
    visual(doc, 'spec', '3B', 'Op Edit Spec krijgt de definitie een waarde voor dit modelnummer.',
        { x: 108, y: 204, w: 90, h: 38 },
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
    pageHeading(doc, 4, 'Componentdefinitie, verwacht en geplaatst', 'Een componentdefinitie kan worden hergebruikt zonder dat er al een fysiek onderdeel bestaat.');

    simpleRow(doc, 12, 82, 56, 'Componentdefinitie', ['Herbruikbaar type onderdeel.', 'Naam, categorie, codes en attribuutbijdragen.'], { h: 31, icon: 'D' });
    simpleRow(doc, 77, 82, 56, 'Verwachte component', ['Baseline op een modelnummer.', 'Definitie + aantal + verwachte plek.'], { h: 31, icon: 'V' });
    simpleRow(doc, 142, 82, 56, 'Geplaatst component', ['Werkelijk onderdeel in één asset.', 'Kan tag, serienummer, status en conditie hebben.'], { h: 31, icon: 'G' });
    arrow(doc, 68.5, 97, 76, 97);
    arrow(doc, 133.5, 97, 141, 97);

    card(doc, 12, 119, 186, 31, { fill: catSoft, stroke: catColor, component: 'component-details' });
    doc.text(17, 127, 'Wat de componentdefinitie daarnaast kan bepalen', { size: 2.65, weight: 900, fill: catColor });
    doc.text(17, 135, ['Identiteit: naam, categorie, fabrikant, onderdeelcode,', 'modelcode, status en herkenbaar weergavelabel.', 'Attribuutbijdragen: bijvoorbeeld 8 GB en DDR4.', 'Toon als assetspecificatie laat zo een bijdrage meetellen.'], { size: 1.75, fill: colors.ink, lh: 2.35 });
    doc.text(112, 135, ['Verwachte subcomponenten: één laag onder een component,', 'bijvoorbeeld poorten onder een moederbord.', 'Gebruik in componentlabel bepaalt welke kenmerken in de', 'herkenbare componentnaam verschijnen.'], { size: 1.75, fill: colors.ink, lh: 2.35 });

    card(doc, 12, 156, 186, 24, { component: 'component-reuse-note' });
    doc.text(17, 164, 'Eén definitie, meerdere toepassingen', { size: 2.45, weight: 900, fill: catColor });
    doc.text(17, 171, 'De lijst toont hoeveel fysieke exemplaren (Instances) en hoeveel modelbaselines (Templates) dezelfde definitie gebruiken.', { size: 1.95, fill: colors.muted });

    visual(doc, 'componentDefinitionList', '4A', 'Component Definitions: herbruikbare typen met aantallen Instances en Templates.',
        { x: 12, y: 186, w: 90, h: 52 },
        { x: 50, y: 105, w: 1260, h: 620 },
        [{ x: 410, y: 205, w: 550, h: 74, padding: 3, target: 'Instances en Templates' }]);
    visual(doc, 'specComponents', '4B', 'Expected Components koppelt een definitie en aantal aan één modelnummer.',
        { x: 108, y: 186, w: 90, h: 52 },
        { x: 265, y: 92, w: 875, h: 600 },
        [{ x: 380, y: 210, w: 735, h: 100, padding: 4, target: 'Verwachte componentdefinitie en aantal' }]);

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

    card(doc, 12, 82, 186, 29, { fill: catSoft, stroke: catColor, component: 'model-number-formula' });
    doc.text(17, 90, 'Exact modelnummer', { size: 2.8, weight: 900, fill: catColor });
    doc.text(52, 90, '=', { size: 2.8, weight: 900, fill: catColor });
    doc.text(62, 90, 'directe attribuutwaarden', { size: 2.5, weight: 900 });
    doc.text(111, 90, '+', { size: 2.8, weight: 900, fill: catColor });
    doc.text(121, 90, 'verwachte componenten en hun bijdragen', { size: 2.5, weight: 900 });
    doc.text(17, 101, 'Een modelafbeelding en leesbaar label helpen herkennen, maar bepalen de specificatiewaarden niet.', { size: 1.95, fill: colors.muted });

    card(doc, 12, 117, 186, 63, { component: 'model-number-example' });
    doc.text(17, 125, 'Voorbeeld: HP ProBook 450 G8 - 2E9F8EA#ABH', { size: 2.8, weight: 900, fill: catColor });
    doc.text(17, 133, 'Direct op het modelnummer', { size: 2.15, weight: 900 });
    doc.text(75, 133, 'Via verwachte componentdefinities', { size: 2.15, weight: 900 });
    doc.line(68, 129, 68, 174, colors.line, 0.35);
    const directRows = [
        ['Introductiejaar', '2021'], ['Gewicht', '1,74 kg'], ['Kleur', 'Silver'], ['Besturingssysteem', 'Windows'],
    ];
    directRows.forEach((row, index) => {
        const y = 142 + index * 8;
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
        const y = 142 + index * 8;
        doc.text(75, y, row[0], { size: 1.95, weight: 800 });
        doc.text(117, y, row[1], { size: 1.85, fill: colors.muted });
    });

    visual(doc, 'spec', '5A', 'Directe attributen staan bovenaan op Edit Spec voor het gekozen modelnummer.',
        { x: 12, y: 186, w: 90, h: 52 },
        { x: 235, y: 185, w: 865, h: 460 },
        [
            { x: 505, y: 286, w: 475, h: 48, padding: 4, target: 'Geselecteerd modelnummer' },
            { x: 575, y: 425, w: 265, h: 70, padding: 4, target: 'Direct attribuut en waarde' },
        ]);
    visual(doc, 'specComponents', '5B', 'Verwachte componenten staan lager op dezelfde Edit Spec-pagina.',
        { x: 108, y: 186, w: 90, h: 52 },
        { x: 265, y: 92, w: 875, h: 600 },
        [{ x: 380, y: 210, w: 735, h: 100, padding: 4, target: 'Componentbijdragen in de modelbaseline' }]);

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

    card(doc, 12, 82, 88, 69, { fill: catSoft, stroke: catColor, component: 'baseline-card' });
    doc.text(18, 91, 'Modelnummer: fabrieksbaseline', { size: 2.8, weight: 900, fill: catColor });
    doc.text(18, 101, ['Verwacht RAM 8GB DDR4', 'Verwacht Storage 256GB NVMe', 'Verwacht Battery 45 Wh', '', 'Een verwachte rij kan als Aangenomen zichtbaar zijn.', 'Dat is nog geen apart getagd fysiek onderdeel.'], { size: 2.05, fill: colors.ink, lh: 3.15 });

    card(doc, 110, 82, 88, 69, { component: 'actual-asset-card' });
    doc.text(116, 91, 'Asset: actuele werkelijkheid', { size: 2.8, weight: 900, fill: catColor });
    doc.text(116, 101, ['Geplaatst RAM 16GB DDR4', 'Storage 256GB blijft aangenomen', 'Batterij kan later worden vervangen', '', 'Een geregistreerd of gewijzigd component beschrijft', 'wat werkelijk in dit ene apparaat zit.'], { size: 2.05, fill: colors.ink, lh: 3.15 });

    callout(doc, 12, 157, 186, [
        'Een RAM- of opslagupgrade maakt geen nieuwe fabrikantvariant. Het asset houdt hetzelfde modelnummer; de componenten en getoonde assetspecificatie veranderen.',
    ], { height: 16, title: 'Zelfde modelnummer, andere actuele opbouw', icon: 'i', color: catColor, fill: catSoft, stroke: catColor });

    visual(doc, 'componentInstallResult', '6A', 'Tracked: geregistreerd RAM.',
        { x: 12, y: 179, w: 50, h: 59 },
        { x: 10, y: 250, w: 395, h: 390 },
        [], { fit: 'contain' });
    visual(doc, 'componentInstallResult', '6B', 'Assumed: verwachte opslag.',
        { x: 66, y: 179, w: 50, h: 59 },
        { x: 10, y: 555, w: 395, h: 365 },
        [], { fit: 'contain' });
    card(doc, 122, 179, 76, 59, { fill: colors.faint, stroke: colors.line, component: 'asset-exception-rules' });
    doc.text(128, 188, 'Andere informatie op één asset', { size: 2.55, weight: 900, fill: catColor });
    doc.text(128, 198, ['Asset override', 'Alleen wanneer de attribuutdefinitie dit toestaat en een component de waarde niet bepaalt.', '', 'Workflowresultaat', 'Conditie, test, notitie of foto. Dit verandert niet automatisch de catalogusdefinitie.', '', 'Serienummer en asset tag', 'Identificeren het fysieke apparaat; het zijn geen modelattributen.'], { size: 1.85, fill: colors.ink, lh: 2.7 });

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
    doc.text(18, 91, 'Volgorde voor de getoonde assetspecificatie', { size: 2.8, weight: 900, fill: catColor });
    const precedence = [
        ['1', 'Actueel geplaatst component', ['Alleen bijdragen met Toon als assetspecificatie.', 'Een waarde op het fysieke component gaat vóór de definitiestandaard.']],
        ['2', 'Toegestane asset override', ['Alleen als geen componentbijdrage de waarde bepaalt', 'en de attribuutdefinitie overrides toestaat.']],
        ['3', 'Modelnummerbaseline', ['Een verwachte componentbijdrage die meetelt gaat', 'vóór een dubbele directe modelnummerwaarde.']],
        ['4', 'Directe modelnummerwaarde', ['Wordt gebruikt wanneer geen meetellende component', 'dezelfde specificatie levert.']],
    ];
    precedence.forEach((row, index) => simpleRow(doc, 18, 98 + index * 24.2, 100, row[1], row[2], { h: 21, icon: row[0] }));

    card(doc, 132, 82, 66, 119, { fill: catSoft, stroke: catColor, component: 'precedence-example' });
    doc.text(138, 91, 'Voorbeeld: werkgeheugen', { size: 2.7, weight: 900, fill: catColor });
    doc.text(138, 102, ['Modelnummer direct', '8 GB', '', 'Verwachte RAM-definitie', '8 GB en Toon als assetspecificatie', '', 'Asset heeft geplaatst RAM', '16 GB met dezelfde meetellende definitie', '', 'Getoond voor het asset', '16 GB vanuit het actuele component'], { size: 2, fill: colors.ink, lh: 3.05 });
    doc.text(138, 181, ['Geen dubbele handmatige correctie nodig.', 'Pas de bron aan die de waarde werkelijk bezit.'], { size: 1.9, weight: 800, fill: catColor, lh: 2.7 });

    card(doc, 12, 207, 186, 32, { fill: colors.faint, stroke: colors.line, component: 'precedence-rules' });
    doc.text(18, 215, 'Twee aanvullende regels', { size: 2.55, weight: 900, fill: catColor });
    doc.text(18, 223, ['Een specifiek vastgelegde waarde van één fysiek component gaat vóór de standaardwaarde van zijn componentdefinitie.', 'Wanneer een onderliggend subcomponent dezelfde specificatie levert, gebruikt het systeem die bijdrage in plaats van de dubbele bijdrage van de ouder.'], { size: 1.95, fill: colors.ink, lh: 3 });

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
    pageHeading(doc, 8, 'Kies de juiste vervolggids', 'Kies op basis van het object dat ontbreekt of gewijzigd moet worden.');

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
        { label: 'Nodig', value: 'Rechten + bron + code' },
        { label: 'Login', value: 'AC-01 Login', guide: guideReference('AC-01') },
        { label: 'Vooraf', value: 'CAT-00 begrepen', guide: guideReference('CAT-00') },
    ];
}

function cat01Page1() {
    const def = definitions['CAT-01'];
    const doc = docFor('cat01-page1');
    header(doc, def, 1, 5, cat01Context());

    const first = stepCard(doc, 1, 'Open Basismodellen', 67, 80);
    doc.text(first.bodyX, 82, ['Open vanaf het dashboard:', 'Instellingen > Asset modellen.', '', 'Dit menu heet nu Asset modellen;', 'hier beheer je de basismodellen,', 'niet de fysieke assets.'], { size: 2.25, fill: colors.muted, lh: 3.15 });
    visual(doc, 'list', '1A', 'Instellingen > Asset modellen.',
        { x: 101, y: 73, w: 87, h: 64 },
        { x: 0, y: 430, w: 560, h: 373 },
        [{ x: 13, y: 681, w: 170, h: 33, padding: 4, target: 'Asset modellen' }]);

    const second = stepCard(doc, 2, 'Zoek voordat je aanmaakt', 154, 87);
    doc.text(second.bodyX, 169, [
        'Zoek eerst op:',
        '- fabrikant + product + generatie;',
        '- exact modelnummer;',
        '- onderscheidend naamdeel bij twijfel.',
        '',
        'Vergelijk Naam, Model Nr., categorie',
        'en het aantal Model Numbers.',
    ], { size: 2.15, fill: colors.muted, lh: 2.9 });
    visual(doc, 'list', '2A', 'Zoek naam en exacte code voordat je de + gebruikt.',
        { x: 90, y: 161, w: 99, h: 59 },
        { x: 230, y: 145, w: 1135, h: 607 },
        [{ x: 782, y: 185, w: 186, h: 36, padding: 4, target: 'Model search' }]);
    doc.text(90, 232, ['Een andere echte fabrikant-/SKU-code is een ander exact modelnummer.', 'Later vervangen RAM of opslag is een componentwijziging, geen nieuw modelnummer.'], { size: 1.9, weight: 800, fill: colors.orange, lh: 2.55 });

    continuationFooter(doc, 'kies de bestaande of nieuwe route.', [
        guideReference('CAT-00', { width: 52 }),
        guideReference('CAT-06', { width: 69 }),
    ], 1, 5);
    return doc.render();
}

function cat01Page2() {
    const def = definitions['CAT-01'];
    const doc = docFor('cat01-page2');
    header(doc, def, 2, 5, cat01Context());
    const step = stepCard(doc, 3, 'Kies de bestaande of nieuwe route', 67, 174);
    doc.text(step.bodyX, 82, 'Kies een route. Beide routes komen daarna samen bij het exacte modelnummer.', { size: 2.25, fill: colors.muted });

    card(doc, 23, 90, 166, 85, { fill: catSoft, stroke: catColor, component: 'route-card' });
    doc.text(29, 100, '3A Bestaand basismodel', { size: 3.15, weight: 900, fill: catColor });
    doc.text(29, 107, ['Open het model en controleer fabrikant, categorie,', 'productnaam, generatie en de bestaande Model Numbers.', 'Ontbreekt alleen de exacte code? Kies Create Model Number.'], { size: 2.15, fill: colors.muted, lh: 2.85 });
    visual(doc, 'detail', '3A', 'Gebruik het bestaande basismodel als product en generatie gelijk zijn.',
        { x: 29, y: 119, w: 154, h: 49 },
        { x: 0, y: 80, w: 1365, h: 381 },
        [{ x: 870, y: 177, w: 142, h: 31, padding: 4, target: 'Create Model Number' }]);

    card(doc, 23, 181, 166, 48, { fill: colors.faint, stroke: colors.line, component: 'route-card' });
    doc.text(29, 191, '3B Nieuw basismodel', { size: 3.15, weight: 900, fill: catColor });
    doc.text(29, 198, ['Het product of de generatie bestaat nog niet.', 'Gebruik dan pas de + in de modellenlijst.'], { size: 2.15, fill: colors.muted, lh: 2.85 });
    visual(doc, 'list', '3B', 'De + opent het formulier voor een nieuw basismodel.',
        { x: 112, y: 187, w: 71, h: 34 },
        { x: 600, y: 130, w: 765, h: 307 },
        [{ x: 1135, y: 185, w: 39, h: 36, padding: 4, target: 'Create model plus' }],
        { captionHeight: 5.5 });
    doc.text(29, 235, 'Bij twijfel niet dupliceren: vergelijk volledige naam, generatie en exacte code.', { size: 2.05, weight: 800, fill: colors.orange });

    continuationFooter(doc, 'vul het algemene basismodel in.', [
        guideReference('CAT-00', { width: 52 }),
        guideReference('CAT-06', { width: 69 }),
    ], 2, 5);
    return doc.render();
}

function cat01Page3() {
    const def = definitions['CAT-01'];
    const doc = docFor('cat01-page3');
    header(doc, def, 3, 5, cat01Context());
    const step = stepCard(doc, 4, 'Vul het basismodel in', 67, 174);
    doc.text(step.bodyX, 82, ['Het basismodel beschrijft product + generatie.', 'Het exacte modelnummer volgt op de volgende pagina.'], { size: 2.25, fill: colors.muted, lh: 3 });

    visual(doc, 'create', '4A', 'Onopgeslagen voorbeeld: algemene modelgegevens en Opslaan.',
        { x: 92, y: 78, w: 97, h: 91 },
        { x: 280, y: 100, w: 850, h: 745 },
        []);

    card(doc, 23, 101, 65, 68, { fill: colors.faint, stroke: colors.line, component: 'field-summary' });
    doc.text(29, 111, 'Verplicht', { size: 2.7, weight: 900, fill: catColor });
    const requiredFields = [
        ['Naam basismodel:', 'HP ProBook 450 G8'],
        ['Categorie:', 'Laptops'],
        ['Fabrikant:', 'HP'],
    ];
    requiredFields.forEach((row, index) => {
        const y = 121 + index * 13;
        doc.text(29, y, row[0], { size: 1.95, weight: 900, fill: colors.ink });
        doc.text(51, y, row[1], { size: 1.95, fill: colors.ink });
    });

    const fieldRows = [
        ['Afschrijving', 'Volg financieel beleid; raad niet op leeftijd.'],
        ['Veldverzameling', 'Alleen goedgekeurde legacy velden; geen CAT-vervanger.'],
        ['Notities', 'Optionele beheercontext; geen tag, serie of modelnummer.'],
        ['Afbeelding', 'Optioneel algemeen modelbeeld; variantbeeld kan later.'],
    ];
    card(doc, 23, 177, 166, 50, { component: 'field-table' });
    fieldRows.forEach((row, index) => {
        const y = 185 + index * 10.3;
        if (index) doc.line(29, y - 4.4, 183, y - 4.4, colors.line, 0.3);
        doc.text(29, y, row[0], { size: 2.1, weight: 900, fill: catColor });
        doc.text(63, y, row[1], { size: 2, fill: colors.muted });
    });
    doc.text(23, 236, 'Controleer dat de naam geen asset tag, serienummer of verzonnen configuratiecode bevat.', { size: 2.05, weight: 800, fill: colors.orange });

    continuationFooter(doc, 'voeg het exacte modelnummer toe.', [
        guideReference('CAT-00', { width: 52 }),
        guideReference('CAT-03', { width: 48 }),
    ], 3, 5);
    return doc.render();
}

function cat01Page4() {
    const def = definitions['CAT-01'];
    const doc = docFor('cat01-page4');
    header(doc, def, 4, 5, cat01Context());

    const step5 = stepCard(doc, 5, 'Open Create Model Number', 67, 54);
    doc.text(step5.bodyX, 82, ['Na Opslaan staat het basismodel nog niet volledig klaar.', 'Kies Create Model Number op de detailpagina.'], { size: 2.2, fill: colors.muted, lh: 3 });
    visual(doc, 'detail', '5A', 'Open de exacte variant vanaf het opgeslagen basismodel.',
        { x: 103, y: 73, w: 85, h: 40 },
        { x: 0, y: 80, w: 1050, h: 426 },
        [{ x: 870, y: 177, w: 142, h: 31, padding: 4, target: 'Create Model Number' }],
        { captionHeight: 5.5 });

    const step6 = stepCard(doc, 6, 'Vul code en label in', 129, 112);
    doc.text(step6.bodyX, 144, ['Code: exacte fabrikantcode inclusief suffix.', 'Voorbeeld: 2E9F8EA#ABH.', '', 'Aa: alleen voor bewust kleine letters;', 'normale invoer wordt automatisch hoofdletters.', '', 'Label: product + CPU + RAM + opslag,', 'zodat de refurbisher de variant herkent.'], { size: 2.15, fill: colors.muted, lh: 2.9 });
    visual(doc, 'numberCreate', '6A', 'Code is exact; Label beschrijft de variant voor de gebruiker.',
        { x: 92, y: 136, w: 97, h: 76 },
        { x: 280, y: 100, w: 850, h: 613 });
    callout(doc, 92, 215, 97, ['Gebruik hier geen serienummer, Product ID,', 'Inbit asset tag of zelfgemaakte code.'], { height: 19, title: 'Identificatie niet verwisselen' });
    doc.text(23, 236, 'Bij het eerste exacte modelnummer kiest het systeem automatisch de standaardrij.', { size: 2.05, weight: 800, fill: catColor });

    continuationFooter(doc, 'controleer en bouw de specificatie.', [
        guideReference('CAT-02', { width: 57 }),
        guideReference('CAT-05', { width: 69 }),
    ], 4, 5);
    return doc.render();
}

function cat01Page5() {
    const def = definitions['CAT-01'];
    const doc = docFor('cat01-page5');
    header(doc, def, 5, 5, cat01Context());

    const step7 = stepCard(doc, 7, 'Controleer het resultaat', 67, 91);
    doc.text(step7.bodyX, 82, [
        'Controleer op de detailpagina:',
        '- naam, fabrikant en categorie;',
        '- exacte code inclusief suffix;',
        '- begrijpelijk variantlabel;',
        '- de rij is actief;',
        '- de eerste rij staat automatisch als standaard;',
        '- geen dubbele rij.',
    ], { size: 2.15, fill: colors.muted, lh: 3 });
    visual(doc, 'detail', '7A', 'Code, label en actieve/standaardstatus.',
        { x: 75, y: 74, w: 72, h: 36 },
        { x: 50, y: 80, w: 885, h: 369 });
    visual(doc, 'detail', '7B', 'Categorie en fabrikant.',
        { x: 151, y: 74, w: 38, h: 71 },
        { x: 1035, y: 140, w: 330, h: 564 });

    const step8 = stepCard(doc, 8, 'Optioneel vervolg: bouw de specificatie', 166, 43);
    doc.text(step8.bodyX, 181, ['Het basismodel en exacte nummer zijn nu klaar.', 'Gebruik CAT-02 voor specificaties; registreer daarna met AST-03.'], { size: 2.25, fill: colors.muted, lh: 3.1 });
    doc.familyBadge(116, 187, 'CAT', { radius: 2.5, fontSize: 1.5, fill: catSoft });
    doc.centeredText(120, 187, 'CAT-02 Modelspecificatie opbouwen', { size: 2.2, weight: 800, fill: catColor, anchor: 'start' });
    doc.familyBadge(116, 198, 'AST', { radius: 2.5, fontSize: 1.5, fill: GUIDE_FAMILIES.AST.fill });
    doc.centeredText(120, 198, 'AST-03 Asset registreren en labelen', { size: 2.2, weight: 800, fill: GUIDE_FAMILIES.AST.color, anchor: 'start' });

    callout(doc, 12, 217, 186, [
        'Kopieer model kopieert alleen het basismodel-formulier en eventueel de basisafbeelding.',
        'Modelnummers, waarden, verwachte componenten en modelnummerbeelden worden niet gekopieerd.',
    ], { height: 27, title: 'Kopieer model is geen volledige duplicatie' });

    finalFooter(doc, 'Het basismodel en exacte modelnummer bestaan zonder duplicaat en zijn gecontroleerd.', related.cat01, 5, 5);
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
