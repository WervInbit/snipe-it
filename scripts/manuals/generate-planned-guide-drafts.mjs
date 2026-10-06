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
    draftPdfRoot,
    evidencePath,
    guideOutputDir,
    loadGuideDependency,
    repoPdfOutputRoot,
} from './lib/guide-paths.mjs';

const { chromium } = loadGuideDependency('playwright');
const generatedOn = process.env.SNIPEIT_GUIDE_DATE ?? '2026-09-15';
const outputRoot = process.env.SNIPEIT_GUIDE_OUT_DIR ?? guideOutputDir('2026-09-15-planned-guides');
const selectedGuide = process.env.SNIPEIT_GUIDE_FILTER?.trim().toUpperCase() || null;
const colors = GUIDE_TOKENS.colors;

const sourceIds = {
    groupList: 'USR-GROUPS-LIST-DESKTOP-01',
    groupCreate: 'USR-GROUP-CREATE-DESKTOP-01',
    groupTop: 'USR-GROUP-PERMISSIONS-TOP-DESKTOP-01',
    groupModels: 'USR-GROUP-PERMISSIONS-MODELS-DESKTOP-01',
    groupSave: 'USR-GROUP-SAVE-DESKTOP-01',
    groupDetail: 'USR-GROUP-DETAIL-DESKTOP-01',
    modelLifecycle: 'CAT-MODEL-NUMBER-LIFECYCLE-DESKTOP-01',
    specAttributes: 'CAT-MODEL-SPEC-CLEANUP-ATTRIBUTES-DESKTOP-01',
    specComponents: 'CAT-MODEL-SPEC-CLEANUP-COMPONENTS-DESKTOP-01',
    attributeList: 'CAT-ATTRIBUTE-ENTRY-DESKTOP-01',
    componentList: 'CAT-COMPONENT-DEFINITION-LIST-DESKTOP-01',
    labelPhoto: 'AST-LABEL-PLACEMENT-PHOTO-01',
};

function imageSize(buffer) {
    if (buffer[0] === 0x89 && buffer[1] === 0x50 && buffer[2] === 0x4e && buffer[3] === 0x47) {
        return { width: buffer.readUInt32BE(16), height: buffer.readUInt32BE(20), mime: 'image/png' };
    }
    if (buffer[0] === 0xff && buffer[1] === 0xd8) {
        const markers = new Set([0xc0, 0xc1, 0xc2, 0xc3, 0xc5, 0xc6, 0xc7, 0xc9, 0xca, 0xcb, 0xcd, 0xce, 0xcf]);
        let offset = 2;
        while (offset + 8 < buffer.length) {
            if (buffer[offset] !== 0xff) { offset += 1; continue; }
            const marker = buffer[offset + 1];
            if (markers.has(marker)) {
                return { width: buffer.readUInt16BE(offset + 7), height: buffer.readUInt16BE(offset + 5), mime: 'image/jpeg' };
            }
            if (marker === 0xd8 || marker === 0xd9) { offset += 2; continue; }
            const length = buffer.readUInt16BE(offset + 2);
            if (length < 2) break;
            offset += length + 2;
        }
    }
    throw new Error('Guide evidence must be PNG or JPEG.');
}

function loadImage(sourceId) {
    const file = evidencePath(sourceId);
    const buffer = fs.readFileSync(file);
    const size = imageSize(buffer);
    return { ...size, href: `data:${size.mime};base64,${buffer.toString('base64')}`, file, sourceId };
}

const images = Object.fromEntries(Object.entries(sourceIds).map(([key, sourceId]) => [key, loadImage(sourceId)]));

const definitions = {
    'USR-05': {
        code: 'USR-05', family: 'USR', title: 'Groepen beheren', version: 'Draft v1',
        purpose: 'Maak of wijzig een herbruikbare rechtengroep met zo weinig mogelijk rechten',
        related: [guideReference('USR-02', { width: 60 }), guideReference('HELP-01', { width: 52 })],
        steps: [
            { number: '1', title: 'Open Groepen', visuals: [{ label: '1A', caption: 'Groepenlijst.' }] },
            { number: '2', title: 'Hergebruik of maak nieuw', visuals: [{ label: '2A', caption: 'Bestaande groepen.' }, { label: '2B', caption: 'Nieuwe groep.' }] },
            { number: '3', title: 'Naam en grens', visuals: [{ label: '3A', caption: 'Naam en standaard weigeren.' }, { label: '3B', caption: 'Hoog risico.' }] },
            { number: '4', title: 'Ken minimale rechten toe', visuals: [{ label: '4A', caption: 'Taakgerichte rechten.' }] },
            { number: '5', title: 'Sla op en controleer', visuals: [{ label: '5A', caption: 'Opslaan.' }, { label: '5B', caption: 'Groepsdetail.' }] },
            { number: '6', title: 'Test met een gecontroleerde gebruiker', visuals: [{ label: '6A', caption: 'Leden en effectieve rechten.' }] },
        ],
    },
    'CAT-05': {
        code: 'CAT-05', family: 'CAT', title: 'Varianten en lifecycle beheren', version: 'Draft v1',
        purpose: 'Wijzig status, standaardkeuze of opgeslagen catalogusrelaties na een impactcontrole',
        related: [guideReference('CAT-01', { width: 67 }), guideReference('CAT-02', { width: 59 }), guideReference('CAT-03', { width: 54, row: 2 }), guideReference('CAT-04', { width: 62, row: 2 })],
        steps: [
            { number: '1', title: 'Kies het object en de onderhoudsroute', visuals: [{ label: '1A', caption: 'Catalogusobjecten.' }] },
            { number: '2', title: 'Modelnummerstatus en standaard', visuals: [{ label: '2A', caption: 'Status en acties.' }] },
            { number: '3', title: 'Opgeslagen modelspecificatie opruimen', visuals: [{ label: '3A', caption: 'Direct attribuut.' }, { label: '3B', caption: 'Verwacht component.' }] },
            { number: '4', title: 'Attribuut lifecycle en opties', visuals: [{ label: '4A', caption: 'Attribuutstatus.' }] },
            { number: '5', title: 'Componentdefinitie lifecycle', visuals: [{ label: '5A', caption: 'Definitiestatus.' }] },
        ],
    },
    'CAT-06': {
        code: 'CAT-06', family: 'CAT', title: 'Catalogus controleren en bronnen', version: 'Draft v1',
        purpose: 'Bepaal de exacte fabrikantvariant en leg alleen verdedigbare catalogusfeiten vast',
        related: [guideReference('CAT-01', { width: 67 }), guideReference('CAT-02', { width: 59 }), guideReference('CAT-05', { width: 67, row: 2 })],
        steps: [
            { number: '1', title: 'Gebruik de sterkste bron', visuals: [{ label: '1A', caption: 'Fysiek fabrikantlabel.' }] },
            { number: '2', title: 'Controleer identiteit en feiten', visuals: [{ label: '2A', caption: 'Exacte code op label.' }] },
            { number: '3', title: 'Pas het resultaat via de juiste gids toe', visuals: [] },
        ],
    },
};

Object.values(definitions).forEach(validateGuideDefinition);

function card(doc, x, y, w, h, options = {}) {
    doc.rect(x, y, w, h, options.fill ?? colors.white, options.stroke ?? colors.line, options.sw ?? 0.45, options.r ?? 1.8, 1, { component: options.component ?? 'content-card' });
}

function header(doc, definition, pageNumber, pageCount, context) {
    const palette = GUIDE_FAMILIES[definition.family];
    const title = `${definition.code} ${definition.title}`;
    const titleSize = title.length > 42 ? 4.3 : title.length > 34 ? 5.1 : 7.4;
    doc.rect(12, 13, 2, 16, palette.color);
    doc.text(18, 22, title, { size: titleSize, weight: 900, data: { component: 'guide-title' } });
    doc.text(18, 29, definition.purpose, { size: 2.75, fill: colors.muted });
    doc.rect(164, 12, 34, 14, colors.orangeSoft, '#FDBA74', 0.45, 2);
    doc.centeredText(181, 16.5, `${definition.version} ${generatedOn}`, { size: 2.05, weight: 800, fill: colors.orange });
    doc.centeredText(181, 22, `Pagina ${pageNumber} van ${pageCount}`, { size: 2.05, weight: 800, fill: colors.orange });
    drawContextStrip(doc, context);
}

function pageHeading(doc, definition, number, title, subtitle) {
    const palette = GUIDE_FAMILIES[definition.family];
    doc.familyBadge(15.5, 68.5, definition.family, { radius: 3.5, fontSize: definition.family.length > 3 ? 1.4 : 1.9, fill: palette.fill });
    doc.text(22, 70, `${number} ${title}`, { size: 5, weight: 900 });
    if (subtitle) doc.text(22, 76, subtitle, { size: 2.3, fill: colors.muted });
}

function stepCard(doc, definition, number, title, y, h, options = {}) {
    const palette = GUIDE_FAMILIES[definition.family];
    const x = options.x ?? 12;
    const w = options.w ?? 186;
    card(doc, x, y, w, h, { component: 'step-card' });
    doc.stepBadge(x, y, String(number), palette.color);
    doc.text(x + 11, y + 8, title, { size: 3.75, weight: 900 });
    return { x, y, w, h, bodyX: x + 11 };
}

function visual(doc, definition, imageKey, label, caption, frame, crop = null, options = {}) {
    const palette = GUIDE_FAMILIES[definition.family];
    const captionHeight = options.captionHeight ?? 6;
    const imageFrame = { x: frame.x, y: frame.y, w: frame.w, h: frame.h - captionHeight };
    const placement = doc.image(imageKey, imageFrame, crop, { fit: options.fit ?? 'cover', r: 1.2 });
    doc.rect(imageFrame.x, imageFrame.y, imageFrame.w, imageFrame.h, 'none', 'none', 0, 1.2, 0, { component: 'evidence-frame' });
    doc.imageBadge(imageFrame.x + 1.2, imageFrame.y + 1.2, label, palette.color);
    doc.text(frame.x + 1, frame.y + frame.h - 1.6, caption, { size: 2.05, weight: 700, fill: colors.muted });
    return placement;
}

function callout(doc, x, y, w, lines, options = {}) {
    const height = options.height ?? 20;
    const color = options.color ?? colors.orange;
    card(doc, x, y, w, height, { fill: options.fill ?? colors.orangeSoft, stroke: options.stroke ?? '#FDBA74', component: 'callout-card' });
    doc.circle(x + 5.5, y + 6, 2.8, color, 0.6, colors.white);
    doc.centeredText(x + 5.5, y + 6, options.icon ?? '!', { size: 2.5, weight: 900, fill: color });
    doc.text(x + 11, y + 5.8, options.title ?? 'Let op', { size: 2.45, weight: 900, fill: color });
    doc.text(x + 4, y + 11.2, lines, { size: options.size ?? 2.05, fill: options.textColor ?? colors.ink, lh: options.lh ?? 2.75 });
}

function infoRow(doc, x, y, w, h, title, lines, options = {}) {
    card(doc, x, y, w, h, { fill: options.fill ?? colors.faint, stroke: options.stroke ?? colors.line, component: 'definition-row' });
    doc.circle(x + 5.5, y + 6, 2.7, options.color ?? colors.muted, 0.6, colors.white);
    doc.centeredText(x + 5.5, y + 6, options.icon ?? '-', { size: 2.2, weight: 900, fill: options.color ?? colors.muted });
    doc.text(x + 11, y + 6.2, title, { size: 2.55, weight: 900, fill: options.color ?? colors.ink });
    doc.text(x + 4, y + 12, lines, { size: options.size ?? 2.05, fill: colors.muted, lh: options.lh ?? 2.7 });
}

function footer(doc, definition, pageNumber, pageCount, completion = null, nextText = null, source = 'Bron: gecontroleerde testomgeving') {
    const palette = GUIDE_FAMILIES[definition.family];
    if (completion) {
        drawCompletionRow(doc, completion, { width: 150 });
    } else {
        card(doc, 12, 253, 150, 12, { fill: palette.fill, stroke: palette.color, component: 'continuation-row' });
        doc.centeredText(17, 259, 'Volgende pagina', { size: 2.55, weight: 900, fill: palette.color, anchor: 'start' });
        doc.centeredText(47, 259, nextText, { size: 2.1, fill: palette.color, anchor: 'start' });
    }
    drawRelatedGuideRows(doc, definition.related);
    doc.qrPlaceholder(176, 263, 22);
    doc.centeredText(187, 289, 'Proef - QR volgt', { size: 2.05, weight: 800, fill: colors.muted });
    doc.text(12, 294, `${source} | ${generatedOn}`, { size: 1.75, fill: colors.muted });
    doc.text(198, 294, `Pagina ${pageNumber} van ${pageCount}`, { size: 1.75, fill: colors.muted, anchor: 'end' });
}

function createPage(definition, pageNumber, pageCount, context, draw) {
    const doc = new SvgGuideDocument(images, `${definition.code.toLowerCase()}-${pageNumber}`);
    header(doc, definition, pageNumber, pageCount, context);
    draw(doc);
    return doc.render();
}

function usr05Pages() {
    const d = definitions['USR-05'];
    const context = [
        { label: 'Rol', value: 'Superadmin' },
        { label: 'Nodig', value: 'Taaklijst + testgebruiker', size: 2.25 },
        { label: 'Adres', value: 'snipe.inbit', size: 2.55 },
    ];
    const pages = [];
    pages.push(createPage(d, 1, 2, context, (doc) => {
        pageHeading(doc, d, 1, 'Kies of maak de juiste groep', 'Een groep is een herbruikbare set rechten voor meerdere gebruikers.');
        stepCard(doc, d, 1, 'Open Instellingen > Groepen', 84, 66);
        visual(doc, d, 'groupList', '1A', 'Controleer naam, leden en bestaande groepen.', { x: 23, y: 96, w: 164, h: 47 }, null, { fit: 'contain' });
        doc.text(23, 147, 'Zoek eerst of een bestaande groep exact dezelfde taakgrens heeft.', { size: 2.2, fill: colors.muted });
        stepCard(doc, d, 2, 'Hergebruik of kies Nieuwe aanmaken', 159, 76);
        visual(doc, d, 'groupCreate', '2B', 'Nieuwe groep: naam, notitie en rechten.', { x: 23, y: 171, w: 80, h: 48 }, { x: 250, y: 105, w: 800, h: 520 });
        infoRow(doc, 108, 171, 79, 22, 'Hergebruik', ['De taak en alle benodigde rechten', 'zijn gelijk aan een bestaande groep.'], { icon: '=', color: colors.green, fill: colors.greenSoft, stroke: '#86EFAC' });
        infoRow(doc, 108, 198, 79, 22, 'Maak nieuw', ['De taakgrens wijkt af of een bestaand', 'pakket zou te ruime rechten geven.'], { icon: '+', color: GUIDE_FAMILIES.USR.color, fill: GUIDE_FAMILIES.USR.fill });
        callout(doc, 108, 224, 79, ['Wijzig geen brede bestaande groep voor één gebruiker.', 'Dat verandert de toegang van ieder groepslid.'], { height: 23, title: 'Impact op alle leden' });
        footer(doc, d, 1, 2, null, 'benoem de groep en zet alleen noodzakelijke rechten aan.');
    }));
    pages.push(createPage(d, 2, 2, context, (doc) => {
        pageHeading(doc, d, 3, 'Begrens, sla op en test', 'Weigeren is de veilige standaard; Toestaan wordt alleen per vereiste taak gekozen.');
        stepCard(doc, d, 3, 'Geef een taakgerichte naam en notitie', 84, 42);
        doc.text(23, 96, ['Gebruik een naam die de werkrol beschrijft, niet een persoonsnaam.', 'Noteer doel, eigenaar en uitzonderingen zodat een volgende beheerder de grens begrijpt.'], { size: 2.25, lh: 3.2 });
        callout(doc, 112, 91, 75, ['Global: Super User en Admin geven brede toegang.', 'Zet die alleen aan als de rol dit aantoonbaar vereist.'], { height: 29, title: 'STOP - brede rechten', color: colors.help, fill: colors.helpSoft, stroke: '#FDA4AF' });
        stepCard(doc, d, 4, 'Kies alleen de minimale Toestaan-rechten', 135, 49);
        visual(doc, d, 'groupModels', '4A', 'Voorbeeld: aparte Users- en Models-rechten.', { x: 23, y: 147, w: 84, h: 31 }, { x: 300, y: 350, w: 720, h: 340 }, { fit: 'contain' });
        doc.text(113, 149, ['Lees elke rij als een zelfstandige bevoegdheid.', 'Geef View voordat Create/Edit nodig is.', 'Lifecycle en cleanup zijn Admin-taken.', 'Laat overige rijen op Weigeren.'], { size: 2.15, lh: 3.35 });
        stepCard(doc, d, 5, 'Kies Opslaan en controleer het groepsdetail', 193, 35);
        visual(doc, d, 'groupSave', '5A', 'Opslaan onderaan het formulier.', { x: 23, y: 204, w: 68, h: 18 }, { x: 280, y: 560, w: 760, h: 135 });
        visual(doc, d, 'groupDetail', '5B', 'Leden links; effectieve rechten rechts.', { x: 96, y: 204, w: 91, h: 18 }, { x: 55, y: 110, w: 1190, h: 520 });
        card(doc, 12, 233, 186, 15, { component: 'step-card' });
        doc.stepBadge(12, 240.5, '6', GUIDE_FAMILIES.USR.color);
        doc.text(23, 239, 'Test eerst met één gecontroleerde gebruiker', { size: 3.05, weight: 900 });
        doc.text(23, 245, 'Wijs de groep via USR-02 toe; controleer daarna vereiste én verboden routes.', { size: 1.95 });
        footer(doc, d, 2, 2, 'De groep is opgeslagen, gecontroleerd en met één gebruiker getest.');
    }));
    return pages;
}

function cat05Pages() {
    const d = definitions['CAT-05'];
    const context = [
        { label: 'Rol', value: 'Admin' },
        { label: 'Nodig', value: 'Object + impactcontrole', size: 2.2 },
        { label: 'Regel', value: 'Eén onderhoudsroute', size: 2.25 },
    ];
    const pages = [];
    pages.push(createPage(d, 1, 5, context, (doc) => {
        pageHeading(doc, d, 1, 'Kies één onderhoudsroute', 'Controleer eerst wat wordt gebruikt en welk resultaat nodig is.');
        visual(doc, d, 'modelLifecycle', '1A', 'Modelnummers tonen status, assetgebruik en acties.', { x: 23, y: 84, w: 164, h: 53 }, { x: 50, y: 110, w: 1290, h: 560 });
        infoRow(doc, 12, 146, 89, 29, 'A. Modelnummer', ['Primary wijzigen, deprecaten, herstellen', 'of alleen ongebruikte rij verwijderen.'], { icon: 'A', color: GUIDE_FAMILIES.CAT.color, fill: GUIDE_FAMILIES.CAT.fill });
        infoRow(doc, 109, 146, 89, 29, 'B. Modelspecificatie', ['Opgeslagen attribuut- of verwachte-', 'componentrij na impactcontrole verwijderen.'], { icon: 'B', color: GUIDE_FAMILIES.CAT.color, fill: GUIDE_FAMILIES.CAT.fill });
        infoRow(doc, 12, 182, 89, 29, 'C. Attribuut', ['Tijdelijk verbergen/herstellen, verouderde', 'Enum-optie of ongebruikte definitie opruimen.'], { icon: 'C', color: GUIDE_FAMILIES.CAT.color, fill: GUIDE_FAMILIES.CAT.fill });
        infoRow(doc, 109, 182, 89, 29, 'D. Componentdefinitie', ['Activeren/deactiveren of opgeslagen', 'bijdrage/verwacht onderdeel opruimen.'], { icon: 'D', color: GUIDE_FAMILIES.CAT.color, fill: GUIDE_FAMILIES.CAT.fill });
        callout(doc, 12, 220, 186, ['Controleer vóór de wijziging: gekoppelde assets, modelnummers, bijdragen, verwachte onderdelen en overrides.', 'Voer alleen de gekozen route uit; de pagina’s hierna zijn alternatieven, geen verplichte reeks.'], { height: 27, title: 'Impactcontrole eerst' });
        footer(doc, d, 1, 5, null, 'kies alleen de pagina voor het object dat je onderhoudt.');
    }));
    pages.push(createPage(d, 2, 5, context, (doc) => {
        pageHeading(doc, d, 2, 'Route A - modelnummerstatus en standaard', 'Instellingen > Model Numbers; zoek het exacte Basismodel en de exacte code.');
        stepCard(doc, d, 2, 'Controleer status, Assets en gewenste actie', 84, 88);
        visual(doc, d, 'modelLifecycle', '2A', 'Primary, assetaantal, Edit, Edit Spec en Verwijder.', { x: 23, y: 96, w: 164, h: 56 }, { x: 55, y: 160, w: 1280, h: 360 }, { fit: 'contain' });
        doc.text(23, 159, 'Maak alleen een lifecyclewijziging als de exacte code en het Basismodel zeker kloppen.', { size: 2.2 });
        infoRow(doc, 12, 181, 57, 47, 'Primary', ['De eerste code is automatisch Primary.', 'Primary kan niet deprecated of verwijderd.', 'Primary maken herstelt een deprecated rij.'], { icon: 'P', color: colors.green, fill: colors.greenSoft, stroke: '#86EFAC' });
        infoRow(doc, 76.5, 181, 57, 47, 'Deprecated', ['Gebruik voor een historische variant die', 'niet meer gekozen mag worden.', 'Herstel als de variant weer geldig is.'], { icon: 'D', color: colors.orange, fill: colors.orangeSoft, stroke: '#FDBA74' });
        infoRow(doc, 141, 181, 57, 47, 'Verwijderen', ['Alleen wanneer Assets 0 toont en er', 'geen vereiste relatie overblijft.', 'Een in-use rij kan niet worden verwijderd.'], { icon: 'X', color: colors.help, fill: colors.helpSoft, stroke: '#FDA4AF' });
        callout(doc, 12, 232, 186, ['Kopieer model is een beperkte snelweg voor een nieuw Basismodel; controleer alle gekopieerde gegevens.', 'Gebruik CAT-01 voor gewone aanmaak.'], { height: 16, title: 'Geen vervanging voor CAT-01', size: 1.8, lh: 2.2 });
        footer(doc, d, 2, 5, null, 'route B verwijdert opgeslagen specificatierijen.');
    }));
    pages.push(createPage(d, 3, 5, context, (doc) => {
        pageHeading(doc, d, 3, 'Route B - modelspecificatie opruimen', 'Open Edit Spec bij het exacte modelnummer; verwijderen kan assetoverrides raken.');
        stepCard(doc, d, 3, 'Verwijder één opgeslagen rij na impactcontrole', 84, 123);
        visual(doc, d, 'specAttributes', '3A', 'Rode X verwijdert een direct attribuut.', { x: 23, y: 96, w: 78, h: 73 }, { x: 280, y: 210, w: 850, h: 540 });
        visual(doc, d, 'specComponents', '3B', 'Remove verwijdert een verwacht component.', { x: 109, y: 96, w: 78, h: 73 }, { x: 285, y: 195, w: 825, h: 590 }, { fit: 'contain' });
        infoRow(doc, 23, 176, 78, 24, 'Direct attribuut', ['Verwijderen wist ook assetoverrides', 'voor die modelnummer-attribuutrelatie.'], { icon: 'A', color: colors.help, fill: colors.helpSoft, stroke: '#FDA4AF' });
        infoRow(doc, 109, 176, 78, 24, 'Verwacht component', ['Controleer afgeleide attributen en', 'verwachte kindstructuur vóór Remove.'], { icon: 'C', color: colors.help, fill: colors.helpSoft, stroke: '#FDA4AF' });
        callout(doc, 12, 216, 186, ['Maak zo nodig eerst een herstelbare notitie van de oude waarde en relaties.', 'Sla op, open de modelspecificatie opnieuw en controleer minstens één geraakt asset.'], { height: 31, title: 'Verwijderen is niet automatisch herstelbaar', color: colors.help, fill: colors.helpSoft, stroke: '#FDA4AF' });
        footer(doc, d, 3, 5, null, 'route C onderhoudt attribuutdefinities en Enum-opties.');
    }));
    pages.push(createPage(d, 4, 5, context, (doc) => {
        pageHeading(doc, d, 4, 'Route C - attribuut lifecycle en opties', 'Instellingen > Attributes; zoek op naam én systeemkey.');
        visual(doc, d, 'attributeList', '4A', 'Status, datatype, categorieën, overrides, opties en Hide.', { x: 23, y: 84, w: 164, h: 59 }, { x: 225, y: 115, w: 1135, h: 300 }, { fit: 'contain' });
        infoRow(doc, 12, 153, 57, 48, 'Hide / Unhide', ['Hide stopt nieuwe selectie zonder de', 'bestaande betekenis te wissen.', 'Gebruik Unhide als dezelfde betekenis', 'weer nodig is.'], { icon: 'H', color: colors.orange, fill: colors.orangeSoft, stroke: '#FDBA74' });
        infoRow(doc, 76.5, 153, 57, 48, 'Enum-optie', ['Verwijder alleen een aantoonbaar', 'verouderde en ongebruikte optie.', 'Controleer modellen, componenten, assets', 'en workflows op die opgeslagen waarde.'], { icon: 'E', color: GUIDE_FAMILIES.CAT.color, fill: GUIDE_FAMILIES.CAT.fill });
        infoRow(doc, 141, 153, 57, 48, 'Definitie', ['Verwijder alleen als de definitie nergens', 'wordt gebruikt. Anders verbergen.', 'Een gekoppelde definitie hoort niet', 'verwijderbaar te zijn.'], { icon: 'X', color: colors.help, fill: colors.helpSoft, stroke: '#FDA4AF' });
        callout(doc, 12, 211, 186, ['Verander nooit achteraf de betekenis, systeemkey of datatype van een gebruikte definitie.', 'Maak voor een werkelijk nieuwe betekenis een nieuwe definitie via CAT-03.'], { height: 36, title: 'Betekenis blijft stabiel', color: colors.help, fill: colors.helpSoft, stroke: '#FDA4AF' });
        footer(doc, d, 4, 5, null, 'route D onderhoudt componentdefinities en hun relaties.');
    }));
    pages.push(createPage(d, 5, 5, context, (doc) => {
        pageHeading(doc, d, 5, 'Route D - componentdefinitie lifecycle', 'Instellingen > Component Definitions; controleer Instances en Templates.');
        visual(doc, d, 'componentList', '5A', 'Status, gebruiksaantallen en Deactivate per definitie.', { x: 23, y: 84, w: 164, h: 61 }, { x: 55, y: 135, w: 1290, h: 520 });
        infoRow(doc, 12, 155, 89, 38, 'Activate / Deactivate', ['Deactiveer een definitie die niet meer nieuw gekozen mag', 'worden. Activeer alleen wanneer exact dezelfde definitie', 'weer geldig is; maak geen duplicaat.'], { icon: 'S', color: colors.orange, fill: colors.orangeSoft, stroke: '#FDBA74' });
        infoRow(doc, 109, 155, 89, 38, 'Relaties opruimen', ['Open Edit en verwijder een opgeslagen bijdrage of verwacht', 'onderdeel pas na controle van modelbaselines, afgeleide feiten', 'en onderliggende structuur.'], { icon: 'R', color: GUIDE_FAMILIES.CAT.color, fill: GUIDE_FAMILIES.CAT.fill });
        callout(doc, 12, 202, 186, ['Er is bewust geen Delete-actie in de browser voor componentdefinities.', 'Controleer na iedere wijziging: selectorzichtbaarheid, betrokken modelbaseline en assetweergave.'], { height: 29, title: 'Geen browserverwijdering', color: colors.help, fill: colors.helpSoft, stroke: '#FDA4AF' });
        doc.text(12, 242, 'Klaar? Noteer het gewijzigde object, de impactcontrole en het gecontroleerde resultaat in het werkitem.', { size: 2.2, weight: 700 });
        footer(doc, d, 5, 5, 'De gekozen onderhoudsroute is uitgevoerd en het geraakte resultaat is gecontroleerd.');
    }));
    return pages;
}

function cat06Pages() {
    const d = definitions['CAT-06'];
    const context = [
        { label: 'Rol', value: 'Supervisor (controle)' },
        { label: 'Admin', value: 'alleen voor cleanup' },
        { label: 'Doel', value: 'exacte, verdedigbare feiten', size: 2.05 },
    ];
    const source = 'Bron: fysiek label + fabrikantdocumentatie';
    const pages = [];
    pages.push(createPage(d, 1, 3, context, (doc) => {
        pageHeading(doc, d, 1, 'Gebruik de sterkste beschikbare bron', 'Begin bij het fysieke apparaat; zoek daarna bevestiging voor de exacte variant.');
        visual(doc, d, 'labelPhoto', '1A', 'Fysiek fabrikantlabel op het onderzochte apparaat.', { x: 23, y: 84, w: 164, h: 66 }, { x: 790, y: 420, w: 260, h: 150 }, { fit: 'contain' });
        infoRow(doc, 12, 159, 57, 59, '1. Fysiek label', ['Sterkste identiteit voor dit apparaat.', 'Neem fabrikant, familie en exacte', 'Product ID / P/N over, inclusief', 'tekens, suffix en hoofdletters.'], { icon: '1', color: colors.green, fill: colors.greenSoft, stroke: '#86EFAC' });
        infoRow(doc, 76.5, 159, 57, 59, '2. Fabrikantbron', ['Gebruik officiële product-, support-', 'of servicedocumentatie om de exacte', 'code en fabriekspecificatie te', 'bevestigen.'], { icon: '2', color: GUIDE_FAMILIES.CAT.color, fill: GUIDE_FAMILIES.CAT.fill });
        infoRow(doc, 141, 159, 57, 59, '3. Goedgekeurde bron', ['Alleen wanneer label en fabrikantbron', 'onvoldoende zijn: een door Inbit', 'goedgekeurde gezaghebbende bron.', 'Leg onzekerheid voor aan de eigenaar.'], { icon: '3', color: colors.orange, fill: colors.orangeSoft, stroke: '#FDBA74' });
        callout(doc, 12, 226, 186, ['Software Product ID, serienummer en Inbit-tag identificeren niet de catalogusvariant.', 'Een zoekresultaat of verkopersnaam zonder exacte code is geen voldoende bron.'], { height: 21, title: 'Niet verwisselen', color: colors.help, fill: colors.helpSoft, stroke: '#FDA4AF' });
        footer(doc, d, 1, 3, null, 'controleer code, punctuation en de grens tussen fabriek en huidig apparaat.', source);
    }));
    pages.push(createPage(d, 2, 3, context, (doc) => {
        pageHeading(doc, d, 2, 'Controleer identiteit én soort feit', 'Schrijf de code exact over; beslis daarna of het feit bij catalogus of asset hoort.');
        visual(doc, d, 'labelPhoto', '2A', 'Op het label: HP ProBook 450 G8 en Product ID 2E9F8EA#ABH.', { x: 23, y: 84, w: 164, h: 60 }, { x: 790, y: 420, w: 260, h: 150 }, { fit: 'contain' });
        infoRow(doc, 12, 153, 57, 43, 'Catalogusidentiteit', ['Fabrikant, productfamilie, generatie', 'en exacte model-/productcode.', 'Behoud #, -, /, suffix en case.'], { icon: 'I', color: GUIDE_FAMILIES.CAT.color, fill: GUIDE_FAMILIES.CAT.fill });
        infoRow(doc, 76.5, 153, 57, 43, 'Uniek apparaat', ['Serienummer en Inbit-tag horen bij', 'één fysiek asset. Ze worden nooit', 'modelnummer of definitienaam.'], { icon: 'A', color: GUIDE_FAMILIES.AST.color, fill: GUIDE_FAMILIES.AST.fill });
        infoRow(doc, 141, 153, 57, 43, 'Geen identiteit', ['Software Product ID, computernaam', 'en vrije omschrijving mogen de', 'fabrikantcode niet vervangen.'], { icon: 'X', color: colors.help, fill: colors.helpSoft, stroke: '#FDA4AF' });
        infoRow(doc, 12, 205, 89, 35, 'Fabrieksfeit', ['Een standaardconfiguratie uit documentatie hoort in de', 'modelnummerbaseline: processorfamilie, standaard RAM,', 'standaard opslag of verwachte componenten.'], { icon: 'F', color: GUIDE_FAMILIES.CAT.color, fill: GUIDE_FAMILIES.CAT.fill });
        infoRow(doc, 109, 205, 89, 35, 'Huidige hardware', ['Een later vervangen SSD, extra RAM of ontbrekend onderdeel', 'hoort op het asset/componentniveau. Pas de catalogus niet', 'aan om één afwijkend apparaat passend te maken.'], { icon: 'N', color: GUIDE_FAMILIES.AST.color, fill: GUIDE_FAMILIES.AST.fill });
        footer(doc, d, 2, 3, null, 'stuur het geverifieerde resultaat naar de gids die dat object beheert.', source);
    }));
    pages.push(createPage(d, 3, 3, context, (doc) => {
        pageHeading(doc, d, 3, 'Pas het resultaat via de juiste gids toe', 'CAT-06 controleert; de doelgids maakt of onderhoudt het catalogusobject.');
        infoRow(doc, 12, 86, 89, 34, 'CAT-01 - identiteit', ['Basismodel of exacte fabrikantcode ontbreekt.', 'Maak of hergebruik model en modelnummer.'], { icon: '1', color: GUIDE_FAMILIES.CAT.color, fill: GUIDE_FAMILIES.CAT.fill });
        infoRow(doc, 109, 86, 89, 34, 'CAT-02 - baseline', ['Geverifieerde fabriekspecificatie ontbreekt.', 'Leg directe waarden en verwachte componenten vast.'], { icon: '2', color: GUIDE_FAMILIES.CAT.color, fill: GUIDE_FAMILIES.CAT.fill });
        infoRow(doc, 12, 128, 89, 34, 'CAT-03 / CAT-04 - definitie', ['Een herbruikbare betekenis of onderdeeldefinitie', 'ontbreekt. Maak eerst de passende bouwsteen.'], { icon: '3', color: GUIDE_FAMILIES.CAT.color, fill: GUIDE_FAMILIES.CAT.fill });
        infoRow(doc, 109, 128, 89, 34, 'CAT-05 - onderhoud', ['Status, Primary, bestaande optie of relatie moet', 'na impactcontrole worden gewijzigd of opgeruimd.'], { icon: '5', color: GUIDE_FAMILIES.CAT.color, fill: GUIDE_FAMILIES.CAT.fill });
        callout(doc, 12, 174, 186, ['Kun je geen verdedigbare bron vinden? Maak geen code, label of specificatie.', 'Laat het veld leeg of stop de taak en vraag de cataloguseigenaar om een bronbesluit.'], { height: 31, title: 'Niet gokken', color: colors.help, fill: colors.helpSoft, stroke: '#FDA4AF' });
        callout(doc, 12, 213, 186, ['De huidige Snipe-IT-structuur heeft geen duurzaam bronveld voor catalogusfeiten.', 'Deze v1 is daarom een verificatiegids; leg geen niet-bestaande bronregistratieprocedure op.'], { height: 34, title: 'Bekende grens van v1', color: colors.orange, fill: colors.orangeSoft, stroke: '#FDBA74' });
        footer(doc, d, 3, 3, 'De exacte identiteit en feiten zijn geverifieerd of de taak is zonder gok gestopt.', null, source);
    }));
    return pages;
}

const guides = {
    'USR-05': { definition: definitions['USR-05'], pages: usr05Pages(), outputFile: 'usr-05-groepen-beheren-v1-draft.pdf', layout: 'two-page-admin-flow', evidence: ['USR-GROUPS-LIST-DESKTOP-01', 'USR-GROUP-CREATE-DESKTOP-01', 'USR-GROUP-PERMISSIONS-TOP-DESKTOP-01', 'USR-GROUP-PERMISSIONS-MODELS-DESKTOP-01', 'USR-GROUP-SAVE-DESKTOP-01', 'USR-GROUP-DETAIL-DESKTOP-01'], knownGaps: ['Requires Superadmin review and a controlled-user permission test before acceptance.'] },
    'CAT-05': { definition: definitions['CAT-05'], pages: cat05Pages(), outputFile: 'cat-05-varianten-en-lifecycle-beheren-v1-draft.pdf', layout: 'branching-admin-maintenance', evidence: ['CAT-MODEL-NUMBER-LIFECYCLE-DESKTOP-01', 'CAT-MODEL-SPEC-CLEANUP-ATTRIBUTES-DESKTOP-01', 'CAT-MODEL-SPEC-CLEANUP-COMPONENTS-DESKTOP-01', 'CAT-ATTRIBUTE-ENTRY-DESKTOP-01', 'CAT-COMPONENT-DEFINITION-LIST-DESKTOP-01'], knownGaps: ['Requires Admin owner review and controlled lifecycle/cleanup execution before acceptance.', 'The proposed narrower CAT-05 title remains undecided.'] },
    'CAT-06': { definition: definitions['CAT-06'], pages: cat06Pages(), outputFile: 'cat-06-catalogus-controleren-en-bronnen-v1-draft.pdf', layout: 'reference-checklist', evidence: ['AST-LABEL-PLACEMENT-PHOTO-01'], knownGaps: ['Verification-only v1: the product has no durable catalog-source field.', 'Requires catalog-owner approval of the fallback-source policy before acceptance.'] },
};

function htmlFor(title, pages) {
    return `<!doctype html><html><head><meta charset="utf-8"><title>${title}</title><style>
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
            if (box.x < -0.1 || box.y < -0.1 || box.x + box.width > 210.1 || box.y + box.height > 297.1) errors.push(`${node.dataset.component} exceeds A4 bounds.`);
        });
        const title = svg.querySelector('[data-component="guide-title"]');
        if (title) {
            const box = title.getBBox();
            if (box.x + box.width > 160) errors.push(`Guide title overlaps version panel at x=${(box.x + box.width).toFixed(2)}.`);
        }
        return { page: index + 1, errors };
    }));
    const errors = result.flatMap((entry) => entry.errors.map((error) => `Page ${entry.page}: ${error}`));
    if (errors.length) throw new Error(`Guide geometry failed:\n- ${errors.join('\n- ')}`);
    return result;
}

async function renderGuide(browser, code, guide) {
    const guideDir = path.join(outputRoot, `${code.toLowerCase()}-v1`);
    fs.mkdirSync(guideDir, { recursive: true });
    fs.mkdirSync(repoPdfOutputRoot, { recursive: true });
    fs.mkdirSync(draftPdfRoot, { recursive: true });
    guide.pages.forEach((svg, index) => fs.writeFileSync(path.join(guideDir, `${code.toLowerCase()}-page-${index + 1}.svg`), svg, 'utf8'));
    const htmlPath = path.join(guideDir, `${code.toLowerCase()}-v1.html`);
    const proofPdf = path.join(guideDir, guide.outputFile);
    const repoPdf = path.join(repoPdfOutputRoot, guide.outputFile);
    const draftPdf = path.join(draftPdfRoot, guide.outputFile);
    fs.writeFileSync(htmlPath, htmlFor(`${code} ${guide.definition.title} - Draft v1`, guide.pages), 'utf8');
    const page = await browser.newPage({ viewport: { width: 1240, height: 1754 }, deviceScaleFactor: 2 });
    await page.goto(pathToFileURL(htmlPath).href, { waitUntil: 'load' });
    const componentQa = await inspectRenderedGuideComponents(page);
    const geometryQa = await inspectPageGeometry(page);
    const pageLocators = await page.locator('.page').all();
    for (let index = 0; index < pageLocators.length; index += 1) {
        await pageLocators[index].screenshot({ path: path.join(guideDir, `${code.toLowerCase()}-page-${index + 1}.png`) });
    }
    await page.pdf({ path: proofPdf, width: '210mm', height: '297mm', printBackground: true, preferCSSPageSize: true, margin: { top: '0', right: '0', bottom: '0', left: '0' } });
    await page.close();
    fs.copyFileSync(proofPdf, repoPdf);
    fs.copyFileSync(proofPdf, draftPdf);
    const summary = { code, version: 'Draft v1', generatedOn, pageCount: guide.pages.length, layout: guide.layout, componentQa, geometryQa, evidence: guide.evidence, outputs: { htmlPath, proofPdf, repoPdf, draftPdf }, knownGaps: guide.knownGaps };
    fs.writeFileSync(path.join(guideDir, 'generation-summary.json'), `${JSON.stringify(summary, null, 2)}\n`, 'utf8');
    return summary;
}

const requested = selectedGuide ? [selectedGuide] : Object.keys(guides);
requested.forEach((code) => { if (!guides[code]) throw new Error(`Unknown planned guide filter: ${code}`); });
const browser = await chromium.launch(browserLaunchOptions({ headless: true }));
try {
    const summaries = [];
    for (const code of requested) summaries.push(await renderGuide(browser, code, guides[code]));
    console.log(JSON.stringify({ outputRoot, guides: summaries }, null, 2));
} finally {
    await browser.close();
}
