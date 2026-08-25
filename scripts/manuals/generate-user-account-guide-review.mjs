import fs from 'node:fs';
import path from 'node:path';
import { pathToFileURL } from 'node:url';
import {
    GUIDE_FAMILIES as familyColors,
    GUIDE_STATUSES,
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
    evidenceRoot,
    guideOutputDir,
    loadGuideDependency,
    repoPdfOutputRoot,
} from './lib/guide-paths.mjs';

const { chromium } = loadGuideDependency('playwright');
const sharp = loadGuideDependency('sharp');

const guideFilter = process.env.SNIPEIT_GUIDE_FILTER?.trim().toUpperCase() || null;
const usr01Version = process.env.SNIPEIT_USR01_VERSION ?? '11';
const usr02Version = process.env.SNIPEIT_USR02_VERSION ?? '9';
const usr03Version = process.env.SNIPEIT_USR03_VERSION ?? '3';
const usr04Version = process.env.SNIPEIT_USR04_VERSION ?? '3';
const ac02Version = process.env.SNIPEIT_AC02_VERSION ?? '3';
const usr01FeedbackRevision = Number(usr01Version) >= 9;
const generatedOn = process.env.SNIPEIT_GUIDE_DATE ?? '2026-08-25';
const sourceDir = evidenceRoot;
const focusedReviewDirs = {
    'USR-01': `2026-08-25-usr01-review-v${usr01Version}`,
    'USR-02': `2026-08-25-usr02-review-v${usr02Version}`,
    'USR-03': `2026-08-25-usr03-review-v${usr03Version}`,
    'USR-04': `2026-08-25-usr04-review-v${usr04Version}`,
    'AC-02': `2026-08-25-ac02-review-v${ac02Version}`,
};
const combinedBatchName = `operator-guides-user-account-review-${generatedOn}`;
const outDir = process.env.SNIPEIT_GUIDE_OUT_DIR ?? path.join(
    guideOutputDir('user-account-review'),
    guideFilter ? (focusedReviewDirs[guideFilter] ?? `2026-08-13-${guideFilter.toLowerCase()}-review`) : 'batch-v1',
);
const repoPdfDir = repoPdfOutputRoot;

const colors = GUIDE_TOKENS.colors;

const sourceFiles = {
    peopleNav: 'USR-DASHBOARD-PEOPLE-NAV-DESKTOP-01.png',
    list: 'USR-LIST-DESKTOP-01.png',
    create: 'USR-CREATE-FORM-DESKTOP-01.png',
    groups: 'USR-GROUP-EDIT-DESKTOP-01.png',
    permissions: 'USR-PERMISSIONS-DESKTOP-01.png',
    detail: 'USR-DETAIL-DESKTOP-01.png',
    reset: 'USR-RESET-LINK-DESKTOP-01.png',
    editPassword: 'USR-EDIT-PASSWORD-DESKTOP-01.png',
    editPasswordGenerated: 'USR-EDIT-PASSWORD-GENERATED-DESKTOP-01.png',
    editActivated: 'USR-EDIT-ACTIVATED-DESKTOP-01.png',
    accountMenu: 'AC-ACCOUNT-MENU-DESKTOP-01.png',
    selfPassword: 'AC-SELF-PASSWORD-DESKTOP-01.png',
    inactive: 'USR-DEACTIVATED-DESKTOP-01.png',
    assignments: 'USR-ASSIGNMENTS-DESKTOP-01.png',
    deleteControls: 'USR-DELETE-DESKTOP-01.png',
    deletedList: 'USR-DELETED-LIST-DESKTOP-01.png',
    restore: 'USR-RESTORE-DESKTOP-01.png',
    restored: 'USR-RESTORED-DESKTOP-01.png',
};

const preparedCropSpecs = {
    resetAction: { source: 'reset', left: 900, top: 500, width: 345, height: 190 },
    editPasswordAction: { source: 'editPassword', left: 420, top: 300, width: 630, height: 220 },
    editPasswordGeneratedAction: { source: 'editPasswordGenerated', left: 330, top: 285, width: 650, height: 220 },
    accountMenuAction: { source: 'accountMenu', left: 930, top: 80, width: 310, height: 205 },
    selfPasswordFields: { source: 'selfPassword', left: 55, top: 105, width: 925, height: 295 },
    selfPasswordSave: { source: 'selfPassword', left: 650, top: 250, width: 330, height: 150 },
    activatedAction: { source: 'editActivated', left: 250, top: 215, width: 820, height: 330 },
    deleteAction: { source: 'deleteControls', left: 895, top: 520, width: 355, height: 165 },
    restoreAction: { source: 'restore', left: 850, top: 450, width: 390, height: 235 },
};

function imageSize(buffer) {
    if (buffer[0] === 0x89 && buffer[1] === 0x50 && buffer[2] === 0x4e && buffer[3] === 0x47) {
        return { width: buffer.readUInt32BE(16), height: buffer.readUInt32BE(20), mime: 'image/png' };
    }
    throw new Error('Only PNG evidence is supported.');
}

function loadImage(file) {
    const buffer = fs.readFileSync(file);
    const size = imageSize(buffer);
    return {
        ...size,
        href: `data:${size.mime};base64,${buffer.toString('base64')}`,
        file,
    };
}

function drawHeader(doc, page) {
    const color = page.family === 'AC' ? colors.ac : colors.usr;
    const titleSize = page.titleSize ?? (`${page.code} ${page.title}`.length > 33 ? 7.25 : 8.3);
    doc.rect(12, 13, 2, 16, color);
    doc.text(18, 22, `${page.code} ${page.title}`, { size: titleSize, weight: 900 });
    doc.text(18, 29, page.purpose, { size: 3, fill: colors.muted });
    doc.rect(164, 12, 34, 10, colors.greenSoft, '#86EFAC', 0.45, 2);
    doc.text(181, 18.2, `${page.version} ${generatedOn}`, { size: 2.1, weight: 800, fill: colors.green, anchor: 'middle' });

    drawContextStrip(doc, page.context);
}

function drawVisual(doc, frame, visual, familyColor) {
    const captionH = 5.3;
    const imageFrame = { x: frame.x, y: frame.y, w: frame.w, h: frame.h - captionH };
    const placement = doc.image(visual.image, imageFrame, visual.crop, { fit: visual.fit ?? 'cover', r: 1.2 });
    (visual.marks ?? []).forEach((mark) => doc.focusMark(placement, mark));
    doc.imageBadge(imageFrame.x + 1.2, imageFrame.y + 1.2, visual.label, familyColor);
    doc.text(frame.x + 1, frame.y + frame.h - 1.5, visual.caption, { size: 2.05, weight: 700, fill: colors.muted });
}

function drawStep(doc, step, y, h, color) {
    doc.rect(12, y, 186, h, colors.white, colors.line, 0.45, 1.8);
    doc.stepBadge(12, y, step.number, color);
    doc.text(23, y + 7.4, step.title, { size: 3.85, weight: 900 });
    doc.text(23, y + 13.8, step.body, { size: 2.35, fill: colors.muted, lh: 3.15 });
    if (step.warning) {
        const bodyLineCount = Array.isArray(step.body) ? step.body.length : 1;
        const warningY = step.warningY ?? y + 14.6 + bodyLineCount * 3.15;
        doc.text(23, warningY, step.warning, { size: 2.15, weight: 800, fill: colors.help, lh: 2.8 });
    }
    if (step.note) {
        doc.text(23, y + (step.noteY ?? 28), step.note, { size: 2.15, weight: 800, fill: colors.ink });
    }
    if (step.guideReference) {
        const referenceY = y + h - 4.2;
        const reference = step.guideReference.reference ?? step.guideReference;
        const palette = familyColors[reference.family];
        const iconX = step.guideReference.iconX ?? 55;
        doc.text(23, referenceY, step.guideReference.prefix, {
            size: 2.05,
            weight: 800,
            fill: step.guideReference.prefixColor ?? colors.help,
        });
        const iconY = referenceY - 0.75;
        doc.familyBadge(iconX, iconY, reference.family, { radius: 1.85, fontSize: 1.15 });
        doc.centeredText(iconX + 3, iconY, reference.label, { size: 1.9, weight: 800, fill: palette.color, anchor: 'start' });
    } else if (step.stop) {
        doc.text(23, y + h - 4.2, step.stop, { size: 2.2, weight: 800, fill: colors.help });
    }

    if (!step.visuals?.length) return;
    const visualX = step.visualX ?? 91;
    const visualW = step.visualW ?? 98;
    const gap = 3;
    const availableW = visualW - gap * (step.visuals.length - 1);
    const visualWidths = step.visualWidths ?? step.visuals.map(() => availableW / step.visuals.length);
    if (visualWidths.length !== step.visuals.length) {
        throw new Error(`${step.number} visualWidths must match its visual count.`);
    }
    const widthDelta = Math.abs(visualWidths.reduce((sum, width) => sum + width, 0) - availableW);
    if (widthDelta > 0.01) {
        throw new Error(`${step.number} visualWidths must total ${availableW}.`);
    }
    let currentX = visualX;
    step.visuals.forEach((visual, index) => {
        drawVisual(doc, {
            x: currentX,
            y: y + 4,
            w: visualWidths[index],
            h: h - 8,
        }, visual, color);
        currentX += visualWidths[index] + gap;
    });
}

function drawHelpFooter(doc, page) {
    doc.text(12, 230, page.helpLabel ?? 'Hulp', { size: 2.45, weight: 800, fill: colors.muted });
    const gap = 3;
    const tileW = (186 - gap * (page.help.length - 1)) / page.help.length;
    const tileHeight = page.helpTileHeight ?? 16;
    page.help.forEach((item, index) => {
        const x = 12 + index * (tileW + gap);
        doc.rect(x, 233, tileW, tileHeight, colors.orangeSoft, '#FDBA74', 0.45, 1.8);
        doc.circle(x + 4.5, 238, 2.7, colors.orange, 0.55, '#FFFFFF');
        doc.centeredText(x + 4.5, 238, item.icon ?? '?', { size: 2.35, weight: 900, fill: colors.orange });
        doc.text(x + 9, 238.7, item.title, { size: 2.25, weight: 900 });
        if (item.guide) {
            const reference = typeof item.guide === 'string' ? guideReference(item.guide) : item.guide;
            const palette = familyColors[reference.family];
            doc.text(x + 4, 243.3, item.body, { size: 1.7, fill: colors.ink });
            const referenceY = tileHeight > 16 ? 248.2 : 247;
            const lowerClearance = 233 + tileHeight - (referenceY + 1.85);
            if (lowerClearance < 1) {
                throw new Error(`${page.code} help guide reference requires a taller aligned help row.`);
            }
            doc.familyBadge(x + 5.5, referenceY, reference.family, { radius: 1.85, fontSize: 1.15 });
            doc.centeredText(x + 8.5, referenceY, reference.label, { size: 1.75, weight: 800, fill: palette.color, anchor: 'start' });
        } else {
            doc.text(x + 4, 244.2, item.body, { size: 1.9, fill: colors.ink, lh: 2.45 });
        }
    });

    if (page.componentSystemVersion) {
        drawCompletionRow(doc, page.complete);
        drawRelatedGuideRows(doc, page.related);
    } else {
        doc.rect(12, 253, 150, 12, colors.greenSoft, '#86EFAC', 0.45, 1.8);
        const completeTextY = page.completeTextY ?? 258.5;
        doc.text(17, completeTextY, 'Klaar als', { size: 2.8, weight: 900, fill: colors.green });
        doc.text(38, completeTextY, page.complete, { size: 2.25, fill: colors.green });

        doc.text(12, 272, 'Relevante gidsen', { size: 2.1, weight: 800, fill: colors.muted });
        const chipRows = {
            1: { x: 38, y: 268 },
            2: { x: 12, y: 277 },
        };
        page.related.forEach((item) => {
            const row = chipRows[item.row ?? 1];
            doc.chip(row.x, row.y, item.width, item.label);
            row.x += item.width + 3;
        });
    }

    doc.qrPlaceholder(176, 263, 22);
    doc.centeredText(187, 289, 'Digitale gids', { size: 2.15, weight: 800 });
    doc.text(12, 294, `Bron: gecontroleerde testomgeving | ${page.code} ${page.version} | ${generatedOn}`, { size: 1.75, fill: colors.muted });
    if (page.pageCount > 1) {
        doc.text(198, 294, `Pagina ${page.pageNumber} van ${page.pageCount}`, { size: 1.75, fill: colors.muted, anchor: 'end' });
    }
}

function buildPage(images, page) {
    const idPrefix = `${page.code}-${page.pageNumber ?? 1}`.toLowerCase().replace(/[^a-z0-9]+/g, '-');
    const doc = new SvgGuideDocument(images, idPrefix);
    const color = page.family === 'AC' ? colors.ac : colors.usr;
    drawHeader(doc, page);
    let y = 64;
    page.steps.forEach((step, index) => {
        const h = step.height ?? page.stepHeight ?? 38;
        drawStep(doc, step, y, h, color);
        y += h + (index === page.steps.length - 1 ? 0 : 3);
    });
    drawHelpFooter(doc, page);
    return doc.render();
}

const commonUsrContext = [
    { label: 'Rol', value: 'Admin' },
    { label: 'Nodig', value: 'Stopbesluit + nieuwe beheerder' },
    { label: 'Vooraf', value: 'Ingelogd (AC-01 Login)', color: colors.ac, guide: guideReference('AC-01') },
];

const pages = [
    {
        guide: 'USR-01', code: 'USR-01', family: 'USR', title: 'Gebruiker toevoegen', version: `Draft v${usr01Version}`,
        status: usr01FeedbackRevision ? GUIDE_STATUSES.WORKING_DRAFT : GUIDE_STATUSES.INTERNAL_REVIEW,
        componentSystemVersion: 1,
        purpose: 'Maak een lokaal account met de juiste naam, toegang en standaardrol',
        context: [
            { label: 'Rol', value: 'Admin' },
            { label: 'Nodig', value: 'Naam, rol en veilige overdracht' },
            { label: 'Vooraf', value: 'Ingelogd (AC-01 Login)', color: colors.ac, guide: guideReference('AC-01') },
        ],
        stepHeight: 38,
        steps: [
            {
                number: '1', title: 'Open Nieuwe gebruiker',
                body: ['Klik links op Personen.', 'Kies Toon Alles en daarna + boven de lijst.', 'Bestaat de persoon mogelijk al? Zoek dan eerst', 'op naam, gebruikersnaam of e-mail.'],
                stop: 'Bestaand account? Hergebruik of herstel.',
                visualX: 78, visualW: 111, visualWidths: [38, 70],
                visuals: [
                    {
                        image: 'peopleNav', label: '1A', caption: 'Open Personen en kies Toon Alles.',
                        crop: { x: 0, y: 245, w: 330, h: 330 },
                        marks: [{ shape: 'rect', x: 2, y: 345, w: 220, h: 34, padding: 2, target: 'Personen' }],
                    },
                    {
                        image: 'list', label: '1B', caption: 'De + opent het formulier Nieuwe gebruiker.',
                        crop: { x: 55, y: 105, w: 1190, h: 285 }, fit: 'contain',
                        marks: [{ shape: 'rect', x: 947, y: 181, w: 47, h: 45, padding: 2, target: 'Nieuwe gebruiker' }],
                    },
                ],
            },
            {
                number: '2', title: 'Vul account en wachtwoord in',
                body: ['Gebruikersnaam: Voornaam + kleine eerste letters van', 'alle achternaamdelen, zonder punten of spaties.', 'Jan de Vries wordt Jandv. Tijdelijk: Jandv2026.', 'Zet Deze gebruiker kan inloggen aan.'],
                guideReference: {
                    prefix: 'Eerste login: direct naar',
                    reference: guideReference('AC-02'),
                    iconX: 55,
                },
                visualX: 98, visualW: 91,
                visuals: [{ image: 'create', label: '2A', caption: 'Vul account en tijdelijk wachtwoord in; zet inloggen aan.', crop: { x: 250, y: 145, w: 820, h: 390 }, fit: 'contain', marks: [{ shape: 'rect', x: 460, y: 455, w: 230, h: 48 }] }],
            },
            {
                number: '3', title: usr01FeedbackRevision ? 'Open Optionele informatie' : 'Kies groep en eventuele rechten',
                body: usr01FeedbackRevision
                    ? ['Open onderaan de balk Optionele informatie.', 'Kies daar bij Groepen de standaardgroep.', 'Normaal: Refurbisher.', 'Extra rechten: open bovenaan Machtigingen.']
                    : ['Onderaan: kies de standaardgroep bij Groepen.', 'Normaal: Refurbisher.', 'Tweede tab bovenaan: Machtigingen.', 'Daar staat Global: Super User (Superadmin).'],
                note: 'Gebruik altijd de minimaal benodigde rechten.',
                guideReference: {
                    prefix: 'Groep maken of wijzigen:',
                    prefixColor: colors.muted,
                    reference: guideReference('USR-05'),
                    iconX: 59,
                },
                visualX: 98, visualW: 91,
                visuals: [{ image: 'groups', label: '3A', caption: usr01FeedbackRevision ? 'Na openen van Optionele informatie: kies Groepen.' : 'Groepen staat onderaan de pagina.', crop: { x: 385, y: 345, w: 680, h: 315 }, marks: [{ shape: 'rect', x: 485, y: 400, w: 350, h: 110 }] }],
            },
            {
                number: '4', title: 'Controleer en draag over',
                body: ['Kies Opslaan en open de gebruiker vanuit Gebruikers.', 'Controleer naam, gebruikersnaam, groep en login.', 'Geef het tijdelijke wachtwoord persoonlijk door.', 'Laat de gebruiker direct inloggen en AC-02 volgen.'],
                visualX: 98, visualW: 91,
                visuals: [{ image: 'detail', label: '4A', caption: 'Open de opgeslagen gebruiker vanuit de gebruikerslijst.', crop: { x: 55, y: 155, w: 1190, h: 505 } }],
            },
        ],
        helpLabel: 'Hulp bij toevoegen',
        helpTileHeight: 19,
        help: [
            { title: 'Naam bestaat', body: ['Herstel of hergebruik', 'het bestaande account.'] },
            { title: 'Geen e-mail', body: ['Vul niets verzonnens in;', 'draag tijdelijk persoonlijk over.'] },
            { title: 'Minimale rechten', body: ['Kies de laagste groep', 'die het werk mogelijk maakt.'] },
            { title: 'Maatwerk nodig', body: 'Gebruik Machtigingen en volg:', guide: guideReference('USR-02') },
        ],
        complete: 'Het account klopt en de gebruiker gaat direct door met AC-02.',
        related: [
            guideReference('AC-02', { width: 57 }),
            guideReference('USR-02', { width: 59 }),
            guideReference('USR-03', { width: 47, row: 2 }),
            guideReference('USR-04', { width: 66, row: 2 }),
            guideReference('USR-05', { width: 42, row: 2 }),
        ],
    },
    {
        guide: 'USR-02', code: 'USR-02', family: 'USR', title: 'Rol en rechten wijzigen', version: `Draft v${usr02Version}`,
        status: Number(usr02Version) > 7 ? GUIDE_STATUSES.WORKING_DRAFT : GUIDE_STATUSES.INTERNAL_REVIEW,
        componentSystemVersion: 1,
        purpose: 'Wijzig groepen of pas rechten per gebruiker bewust aan',
        context: [
            { label: 'Rol', value: 'Admin' },
            { label: 'Nodig', value: 'Juiste gebruiker + gewenste toegang' },
            { label: 'Vooraf', value: 'Ingelogd (AC-01 Login)', color: colors.ac, guide: guideReference('AC-01') },
        ],
        steps: [
            {
                number: '1', title: 'Open het juiste account',
                body: ['Open links Personen > Toon Alles.', 'Zoek op naam of gebruikersnaam.', 'Open de gebruiker en klik Gebruiker aanpassen.'],
                height: 32,
                visualX: 80, visualW: 109, visualWidths: [25, 48, 30],
                visuals: [
                    { image: 'peopleNav', label: '1A', caption: 'Personen > Toon Alles.', crop: { x: 0, y: 245, w: 330, h: 330 }, marks: [{ shape: 'rect', x: 2, y: 345, w: 220, h: 34, padding: 2, target: 'Personen' }] },
                    { image: 'list', label: '1B', caption: 'Zoek en open de juiste gebruiker.', crop: { x: 55, y: 165, w: 810, h: 230 }, fit: 'contain', marks: [{ shape: 'rect', x: 590, y: 185, w: 230, h: 42, padding: 3, target: 'Zoeken' }] },
                    { image: 'detail', label: '1C', caption: 'Klik Gebruiker aanpassen.', crop: { x: 950, y: 500, w: 290, h: 70 }, fit: 'contain', marks: [{ shape: 'rect', x: 985, y: 518, w: 240, h: 31, padding: 4, target: 'Gebruiker aanpassen' }] },
                ],
            },
            {
                number: '2', title: 'Wijzig de standaardrol via Groepen',
                body: ['Alleen Superadmin kan bij deze gebruiker', 'groepen toevoegen of verwijderen.', 'Ctrl+klik voegt toe of deselecteert een groep.'],
                height: 38,
                visualX: 111, visualW: 78,
                visuals: [{ image: 'groups', label: '2A', caption: 'Groepsrechten zijn de normale route voor een functie.', crop: { x: 385, y: 345, w: 680, h: 315 }, marks: [{ shape: 'rect', x: 485, y: 400, w: 350, h: 110, padding: 2, target: 'Groepen' }] }],
            },
            {
                number: '3', title: 'Pas rechten per gebruiker aan',
                body: [
                    'Open Machtigingen. Kies per recht:',
                    'Overnemen: gebruik het resultaat van de groep(en).',
                    'Toestaan: geef dit recht extra aan deze gebruiker.',
                    'Weigeren: blokkeer dit recht, ook als een groep het geeft.',
                    'Directe keuzes gaan voor groepsrechten.',
                    'Alleen Superadmin kan Global: Super User wijzigen.',
                ],
                height: 47,
                visualX: 111, visualW: 78,
                visuals: [{ image: 'permissions', label: '3A', caption: 'Open Machtigingen; Overnemen is de standaardkeuze.', crop: { x: 260, y: 155, w: 790, h: 480 }, marks: [{ shape: 'rect', x: 360, y: 160, w: 125, h: 49, padding: 2, target: 'Machtigingen' }, { shape: 'rect', x: 665, y: 215, w: 370, h: 265, padding: 2, target: 'Directe rechten' }] }],
            },
            {
                number: '4', title: 'Sla op en controleer opnieuw',
                body: ['Controleer de groepen op de gebruikerspagina.', 'Heropen Machtigingen en controleer directe afwijkingen.'],
                height: 34,
                visualX: 101, visualW: 88,
                visuals: [{ image: 'detail', label: '4A', caption: 'Controleer de zichtbare groep op het opgeslagen account.', crop: { x: 55, y: 180, w: 1190, h: 430 }, marks: [{ shape: 'rect', x: 75, y: 330, w: 890, h: 55, padding: 2, target: 'Opgeslagen groep' }] }],
            },
        ],
        helpLabel: 'Hulp bij rollen en rechten',
        helpTileHeight: 19,
        help: [
            { title: 'Groepen vergrendeld', body: ['Alleen Superadmin kan', 'groepskeuzes wijzigen.'] },
            { title: 'Effect van recht onduidelijk', body: ['Laat Overnemen staan;', 'lees de omschrijving.'] },
            { title: 'Meerdere gebruikers', body: 'Gebruik een herbruikbare groep:', guide: guideReference('USR-05') },
            { title: 'Toegang te ruim', body: ['Controleer oude groep', 'en direct Toestaan.'] },
        ],
        complete: 'De groepen en directe rechten geven samen de bedoelde toegang.',
        related: [
            guideReference('USR-01', { width: 54 }),
            guideReference('USR-05', { width: 42 }),
            guideReference('USR-04', { width: 66, row: 2 }),
            guideReference('HELP-01', { width: 50, row: 2 }),
        ],
    },
    {
        guide: 'USR-03', code: 'USR-03', family: 'USR', title: 'Wachtwoord resetten', version: `Draft v${usr03Version}`,
        purpose: 'Herstel toegang zonder het definitieve privéwachtwoord te kennen of bewaren',
        context: [
            { label: 'Rol', value: 'Admin' },
            { label: 'Nodig', value: 'Lokale gebruiker + overdracht' },
            { label: 'Vooraf', value: 'Ingelogd (AC-01 Login)', color: colors.ac, guide: guideReference('AC-01') },
        ],
        stepHeight: 50,
        steps: [
            {
                number: '1', title: 'Vind en controleer de gebruiker',
                body: ['Open Personen > Toon Alles en zoek de gebruiker.', 'Vergelijk naam en gebruikersnaam.', 'Open het account en kies Gebruiker aanpassen.'],
                visualX: 74, visualW: 115, visualWidths: [32, 48, 29],
                visuals: [
                    { image: 'peopleNav', label: '1A', caption: 'Open Personen > Toon Alles.', crop: { x: 0, y: 245, w: 330, h: 330 }, marks: [{ shape: 'rect', x: 2, y: 345, w: 220, h: 34, padding: 2, target: 'Personen' }] },
                    { image: 'list', label: '1B', caption: 'Zoek en open de gebruiker.', crop: { x: 55, y: 165, w: 810, h: 230 }, fit: 'contain', marks: [{ shape: 'rect', x: 590, y: 185, w: 230, h: 42, padding: 3, target: 'Zoeken' }] },
                    { image: 'detail', label: '1C', caption: 'Kies Gebruiker aanpassen.', crop: { x: 950, y: 500, w: 290, h: 70 }, fit: 'contain', marks: [{ shape: 'rect', x: 985, y: 518, w: 240, h: 31, padding: 4, target: 'Gebruiker aanpassen' }] },
                ],
            },
            {
                number: '2', title: 'Maak één tijdelijk wachtwoord',
                body: ['Kies eenmaal Genereer.', 'Het tijdelijke wachtwoord verschijnt onder het eerste veld.', 'Laat Deze gebruiker kan inloggen aan staan.', 'Kies eenmaal Opslaan.', 'Gebruik geen zelfbedacht vast wachtwoord.'],
                visuals: [{ image: 'editPasswordGeneratedAction', label: '2A', caption: 'Neem het zichtbare tijdelijke wachtwoord persoonlijk over.', fit: 'contain', marks: [{ shape: 'rect', x: 397, y: 38, w: 90, h: 38, target: 'Genereer' }] }],
            },
            {
                number: '3', title: 'Draag veilig over',
                body: ['Geef het tijdelijke wachtwoord persoonlijk door.', 'Niet in chat, e-mail, notities of screenshots.', 'Laat de gebruiker direct AC-02 volgen.'],
                stop: 'Vraag nooit om het definitieve wachtwoord als bewijs.',
                visuals: [{ image: 'accountMenuAction', label: '3A', caption: 'AC-02 start bij Wachtwoord wijzigen in het accountmenu.', fit: 'contain', marks: [{ shape: 'rect', x: 106, y: 121, w: 188, h: 29, target: 'Wachtwoord wijzigen' }] }],
            },
        ],
        helpLabel: 'Hulp bij resetten',
        helpTileHeight: 19,
        help: [
            { title: 'Account staat uit', body: ['Zet login alleen aan als', 'dit de juiste gebruiker is.'] },
            { title: 'Genereer werkt niet', body: ['Sla niet half op;', 'vraag systeembeheer.'] },
            { title: 'Geen veilig kanaal', body: ['Niet doorgeven;', 'regel eerst overdracht.'] },
            { title: 'Wachtwoord gewijzigd', body: 'Ga direct door voor een privéwachtwoord:', guide: guideReference('AC-02') },
        ],
        complete: 'De gebruiker heeft één resetroute en kent daarna alleen zelf het privéwachtwoord.',
        related: [guideReference('AC-02', { width: 57 }), guideReference('USR-01', { width: 54 }), guideReference('HELP-01', { width: 50, row: 2 })],
    },
    {
        guide: 'AC-02', code: 'AC-02', family: 'AC', title: 'Eigen wachtwoord wijzigen', version: `Draft v${ac02Version}`, titleSize: 6.65,
        purpose: 'Vervang het huidige of tijdelijke wachtwoord door een privéwachtwoord',
        context: [
            { label: 'Rol', value: 'Iedereen met lokaal account' },
            { label: 'Nodig', value: 'Huidig of tijdelijk wachtwoord' },
            { label: 'Vooraf', value: 'Ingelogd (AC-01 Login)', color: colors.ac, guide: guideReference('AC-01') },
        ],
        stepHeight: 50,
        steps: [
            {
                number: '1', title: 'Open Wachtwoord wijzigen',
                body: ['Open rechtsboven het accountmenu.', 'Kies Wachtwoord wijzigen.'],
                visuals: [{ image: 'accountMenuAction', label: '1A', caption: 'Open het accountmenu en kies Wachtwoord wijzigen.', fit: 'contain', marks: [{ shape: 'rect', x: 106, y: 121, w: 188, h: 29, target: 'Wachtwoord wijzigen' }] }],
            },
            {
                number: '2', title: 'Vul de drie velden in',
                body: ['Huidig wachtwoord.', 'Nieuw privéwachtwoord, daarna nogmaals hetzelfde.', 'Hergebruik het tijdelijke wachtwoord niet.'],
                visuals: [{ image: 'selfPasswordFields', label: '2A', caption: 'Vul huidig, nieuw en bevestiging in.', fit: 'contain' }],
            },
            {
                number: '3', title: 'Sla het nieuwe wachtwoord op',
                body: ['Kies Opslaan en lees de melding.', 'Andere aangemelde apparaten worden uitgelogd.'],
                warning: ['Deel het nieuwe wachtwoord nooit met', 'een beheerder of collega.'],
                visuals: [{ image: 'selfPasswordSave', label: '3A', caption: 'Opslaan bevestigt de wijziging en sluit andere sessies.', fit: 'contain', marks: [{ shape: 'rect', x: 188, y: 89, w: 105, h: 48, target: 'Opslaan' }] }],
            },
        ],
        helpLabel: 'Hulp bij eigen wachtwoord',
        helpTileHeight: 19,
        help: [
            { title: 'Tijdelijk werkt niet', body: 'Vraag een beheerder om een nieuwe reset:', guide: guideReference('USR-03') },
            { title: 'Afgekeurd', body: ['Volg de zichtbare', 'wachtwoordregel.'] },
            { title: 'Geen huidig wachtwoord', body: 'Vraag een supervisor om:', guide: guideReference('USR-03') },
            { title: 'Vergeten', body: 'Vraag een supervisor om:', guide: guideReference('USR-03') },
        ],
        complete: 'De wijziging is opgeslagen en alleen de gebruiker kent het nieuwe wachtwoord.',
        related: [guideReference('AC-01', { width: 44 }), guideReference('USR-03', { width: 58 }), guideReference('HELP-01', { width: 50, row: 2 })],
    },
    {
        guide: 'USR-04', code: 'USR-04', family: 'USR', title: 'Gebruiker uitschakelen', version: `Draft v${usr04Version}`,
        purpose: 'Stop toegang veilig en behoud accountgeschiedenis en eigendom',
        pageNumber: 1, pageCount: 2,
        context: commonUsrContext,
        stepHeight: 38,
        steps: [
            {
                number: '1', title: 'Vind en controleer het account',
                body: ['Open Personen > Toon Alles en zoek de gebruiker.', 'Vergelijk naam en gebruikersnaam.', 'Open het juiste account.'],
                warning: ['Account niet zeker? Wijzig niets en', 'controleer de identiteit.'],
                visualX: 78, visualW: 111, visualWidths: [38, 70],
                visuals: [
                    { image: 'peopleNav', label: '1A', caption: 'Open Personen > Toon Alles.', crop: { x: 0, y: 245, w: 330, h: 330 }, marks: [{ shape: 'rect', x: 2, y: 345, w: 220, h: 34, padding: 2, target: 'Personen' }] },
                    { image: 'list', label: '1B', caption: 'Zoek en open de juiste gebruiker.', crop: { x: 55, y: 165, w: 810, h: 230 }, fit: 'contain', marks: [{ shape: 'rect', x: 590, y: 185, w: 230, h: 42, padding: 3, target: 'Zoeken' }] },
                ],
            },
            {
                number: '2', title: 'Verwerk open eigendom',
                body: ['Controleer Apparaten, Licenties en beheerrelaties.', 'Check items in of draag ze over aan de afgesproken', 'nieuwe verantwoordelijke voor apparatuur en beheer.'],
                warning: ['Behoud de geschiedenis; verwerk eerst', 'alle open eigendom.'],
                visuals: [{ image: 'assignments', label: '2A', caption: 'Controleer elke relevante tab voordat toegang wordt gestopt.', crop: { x: 100, y: 150, w: 850, h: 260 }, marks: [{ shape: 'rect', x: 110, y: 155, w: 830, h: 80 }] }],
            },
            {
                number: '3', title: 'Schakel inloggen uit',
                body: ['Kies Gebruiker aanpassen.', 'Haal het vinkje weg bij Deze gebruiker kan inloggen.', 'Sla op.'],
                visuals: [{ image: 'activatedAction', label: '3A', caption: 'Uitvinken stopt nieuwe logins; het account blijft bestaan.', fit: 'contain', marks: [{ shape: 'rect', x: 202, y: 222, w: 266, h: 75, target: 'Deze gebruiker kan inloggen' }] }],
            },
            {
                number: '4', title: 'Controleer deactivering',
                body: ['De gebruiker blijft zichtbaar voor historie.', 'Login ingeschakeld moet Nee zijn.'],
                visuals: [{ image: 'inactive', label: '4A', caption: 'Login uit, account en groep blijven zichtbaar.', crop: { x: 55, y: 155, w: 1190, h: 505 }, marks: [{ shape: 'rect', x: 75, y: 555, w: 875, h: 60 }] }],
            },
        ],
        helpLabel: 'Hulp bij uitschakelen',
        help: [
            { title: 'Alleen toegang stoppen', body: ['Deactiveer;', 'verwijder niet.'] },
            { title: 'Eigendom open', body: ['Draag eerst items', 'en beheerrelaties over.'] },
            { title: 'Account onduidelijk', body: ['Niet wijzigen;', 'controleer identiteit.'] },
            { title: 'Toch verwijderen', body: ['Ga alleen na besluit', 'naar volgende pagina.'] },
        ],
        complete: 'Login staat uit en alle open eigendom heeft een nieuwe verantwoordelijke.',
        related: [guideReference('USR-01', { width: 54 }), guideReference('USR-02', { width: 59 }), guideReference('HELP-01', { width: 50, row: 2 })],
    },
    {
        guide: 'USR-04', code: 'USR-04', family: 'USR', title: 'Verwijderen of herstellen', version: `Draft v${usr04Version}`, titleSize: 6.65,
        purpose: 'Gebruik verwijderen en herstellen alleen na het afgesproken verwijderbesluit',
        pageNumber: 2, pageCount: 2,
        context: [
            { label: 'Rol', value: 'Admin' },
            { label: 'Nodig', value: 'Afgesproken verwijderbesluit' },
            { label: 'Vooraf', value: 'USR-04 pagina 1 voltooid', color: colors.usr, guide: guideReference('USR-04') },
        ],
        stepHeight: 38,
        steps: [
            {
                number: '5', title: 'Controleer voor verwijderen',
                body: ['Deactiveren is de normale offboardingroute.', 'Verwijder alleen na controle van alle toewijzingen.'],
                warning: ['Check Alles In / Verwijder Gebruiker', 'is geen normale snelkoppeling.'],
                visuals: [{ image: 'deleteAction', label: '5A', caption: 'Gebruik Verwijder pas na de volledige controle.', fit: 'contain', marks: [{ shape: 'rect', x: 80, y: 70, w: 260, h: 48 }, { shape: 'rect', x: 80, y: 115, w: 260, h: 42 }] }],
            },
            {
                number: '6', title: 'Vind het verwijderde account',
                body: ['Open Verwijderde Gebruikers.', 'Zoek op naam en gebruikersnaam.', 'Maak geen duplicaat met dezelfde identiteit.'],
                visuals: [{ image: 'deletedList', label: '6A', caption: 'De verwijderde identiteit blijft vindbaar.', crop: { x: 55, y: 100, w: 1190, h: 360 }, marks: [{ shape: 'rect', x: 75, y: 285, w: 1160, h: 100 }] }],
            },
            {
                number: '7', title: 'Herstel het bestaande account',
                body: ['Open de verwijderde gebruiker.', 'Kies Herstel.'],
                warning: ['Herstel activeert login niet automatisch;', 'controleer dit apart.'],
                visuals: [{ image: 'restoreAction', label: '7A', caption: 'De oranje melding en Herstel bevestigen de verwijderde staat.', fit: 'contain', marks: [{ shape: 'rect', x: 105, y: 162, w: 282, h: 58, target: 'Herstel' }] }],
            },
            {
                number: '8', title: 'Controleer na herstel',
                body: ['Controleer gebruikersnaam, groep en directe rechten.', 'Bepaal apart of login weer aan mag.'],
                warning: ['Rol niet zeker? Laat login uit en', 'controleer eerst de actuele toegang.'],
                visuals: [{ image: 'restored', label: '8A', caption: 'Hersteld account: identiteit en groep zichtbaar, login nog uit.', crop: { x: 55, y: 155, w: 1190, h: 505 }, marks: [{ shape: 'rect', x: 75, y: 300, w: 875, h: 320 }] }],
            },
        ],
        helpLabel: 'Hulp bij verwijderen en herstellen',
        helpTileHeight: 19,
        help: [
            { title: 'Verwijderen geblokkeerd', body: ['Controleer resterende', 'toewijzingen en beheer.'] },
            { title: 'Per ongeluk verwijderd', body: ['Herstel het bestaande', 'account.'] },
            { title: 'Dubbel account', body: ['Niet samenvoegen of', 'verwijderen zonder besluit.'] },
            { title: 'Rol verouderd', body: 'Controleer de toegang voor reactivatie:', guide: guideReference('USR-02') },
        ],
        complete: 'Het bestaande account is hersteld en rol plus login zijn opnieuw beoordeeld.',
        related: [guideReference('USR-01', { width: 54 }), guideReference('USR-02', { width: 59 }), guideReference('HELP-01', { width: 50, row: 2 })],
    },
];

function htmlForSvgs(svgs) {
    return `<!doctype html><html><head><meta charset="utf-8"><style>@page{size:A4;margin:0}html,body{margin:0;padding:0;background:#dfe3ea}.page{width:210mm;height:297mm;page-break-after:always;break-after:page;overflow:hidden;background:white}.page:last-child{page-break-after:auto;break-after:auto}.page>svg{display:block;width:210mm;height:297mm}</style></head><body>${svgs.map((svg) => `<div class="page">${svg}</div>`).join('')}</body></html>`;
}

async function renderHtml(browser, htmlFile, pdfFile, pngPrefix, options = {}) {
    const page = await browser.newPage({ viewport: { width: 1240, height: 1754 }, deviceScaleFactor: 2 });
    await page.goto(pathToFileURL(htmlFile).href, { waitUntil: 'load' });
    const componentQa = options.componentQa ? await inspectRenderedGuideComponents(page) : null;
    const pageLocators = await page.locator('.page').all();
    for (let index = 0; index < pageLocators.length; index += 1) {
        await pageLocators[index].screenshot({ path: `${pngPrefix}-${index + 1}.png` });
    }
    await page.pdf({
        path: pdfFile,
        width: '210mm',
        height: '297mm',
        printBackground: true,
        preferCSSPageSize: true,
        margin: { top: '0', right: '0', bottom: '0', left: '0' },
    });
    await page.close();
    return componentQa;
}

async function main() {
    fs.mkdirSync(outDir, { recursive: true });
    fs.mkdirSync(repoPdfDir, { recursive: true });

    const images = Object.fromEntries(Object.entries(sourceFiles).map(([key, name]) => {
        const file = path.join(sourceDir, name);
        if (!fs.existsSync(file)) throw new Error(`Missing evidence: ${file}`);
        return [key, loadImage(file)];
    }));

    const cropDir = path.join(outDir, '_crops');
    fs.mkdirSync(cropDir, { recursive: true });
    for (const [key, spec] of Object.entries(preparedCropSpecs)) {
        const sourceFile = path.join(sourceDir, sourceFiles[spec.source]);
        const cropFile = path.join(cropDir, `${key}.png`);
        await sharp(sourceFile).extract({ left: spec.left, top: spec.top, width: spec.width, height: spec.height }).png().toFile(cropFile);
        images[key] = loadImage(cropFile);
    }

    const selectedPages = guideFilter ? pages.filter((page) => page.guide === guideFilter) : pages;
    if (selectedPages.length === 0) throw new Error(`Unknown guide filter: ${guideFilter}`);
    selectedPages.filter((page) => page.componentSystemVersion).forEach((page) => validateGuideDefinition(page));
    const renderedPages = selectedPages.map((page) => ({ page, svg: buildPage(images, page) }));
    const browser = await chromium.launch(browserLaunchOptions({ headless: true }));
    const outputs = [];
    const geometryReports = [];

    for (const guideCode of [...new Set(selectedPages.map((page) => page.guide))]) {
        const guidePages = renderedPages.filter((item) => item.page.guide === guideCode);
        const versionNumber = guidePages[0].page.version.match(/v(\d+)/i)?.[1] ?? '1';
        const slug = `${guideCode.toLowerCase()}-${guidePages[0].page.title.toLowerCase().replace(/[^a-z0-9]+/g, '-')}-v${versionNumber}-draft`;
        guidePages.forEach((item, index) => fs.writeFileSync(path.join(outDir, `${slug}-page-${index + 1}.svg`), item.svg, 'utf8'));
        const htmlFile = path.join(outDir, `${slug}.html`);
        const pdfFile = path.join(outDir, `${slug}.pdf`);
        fs.writeFileSync(htmlFile, htmlForSvgs(guidePages.map((item) => item.svg)), 'utf8');
        const componentQa = await renderHtml(browser, htmlFile, pdfFile, path.join(outDir, `${slug}-proof`), {
            componentQa: guidePages.some((item) => item.page.componentSystemVersion),
        });
        if (componentQa) geometryReports.push({ guideCode, ...componentQa });
        fs.copyFileSync(pdfFile, path.join(repoPdfDir, path.basename(pdfFile)));
        outputs.push(pdfFile);
    }

    if (guideFilter) {
        const repoPdf = path.join(repoPdfDir, path.basename(outputs[0]));
        fs.copyFileSync(outputs[0], repoPdf);
        await browser.close();
        const summary = [
            `# ${guideFilter} Focused Review`,
            '',
            `Generated: ${generatedOn}`,
            `Source evidence: ${sourceDir}`,
            `Repository review PDF: ${repoPdf}`,
            '',
            '- Existing controlled screenshots are reused without visible password values.',
            '- Shared component geometry QA passed for centered badges and full-name guide references.',
            '- This is a focused draft for review; the earlier combined v1 batch is unchanged.',
            '',
        ].join('\n');
        fs.writeFileSync(path.join(outDir, 'SUMMARY.md'), summary, 'utf8');
        fs.writeFileSync(path.join(outDir, 'component-qa.json'), `${JSON.stringify(geometryReports, null, 2)}\n`, 'utf8');
        console.log(JSON.stringify({ outDir, repoPdf, outputs, geometryReports }, null, 2));
        return;
    }

    const combinedHtml = path.join(outDir, `${combinedBatchName}.html`);
    const combinedPdf = path.join(outDir, `${combinedBatchName}.pdf`);
    const repoPdf = path.join(repoPdfDir, `${combinedBatchName}.pdf`);
    fs.writeFileSync(combinedHtml, htmlForSvgs(renderedPages.map((item) => item.svg)), 'utf8');
    await renderHtml(browser, combinedHtml, combinedPdf, path.join(outDir, 'operator-guides-user-account-review-v1-proof'));
    fs.copyFileSync(combinedPdf, repoPdf);
    await browser.close();

    const summary = [
        '# User Account Guide Review Batch',
        '',
        `Generated: ${generatedOn}`,
        '',
        '## Guides',
        '- USR-01 Gebruiker toevoegen (1 page)',
        '- USR-02 Rol en rechten wijzigen (1 page)',
        '- USR-03 Wachtwoord resetten (1 page)',
        '- AC-02 Eigen wachtwoord wijzigen (1 page)',
        '- USR-04 Gebruiker uitschakelen of herstellen (2 pages)',
        '',
        '## Evidence',
        `- Controlled capture directory: ${sourceDir}`,
        '- Screens show real Dutch application states from the controlled development environment.',
        '- Mila de Boer / Miladb is a fictional reversible evidence account.',
        '- No passwords are present in screenshots or generated guides.',
        '',
        '## Review Notes',
        '- QR areas intentionally say QR volgt; no fake scannable destination is supplied.',
        '- Direct permission guidance keeps Overnemen as the default and treats Toestaan/Weigeren as exceptions.',
        '- USR-04 keeps deactivation as the normal offboarding path and separates delete/restore onto page 2.',
        '',
        `Combined PDF: ${combinedPdf}`,
        `Repository review PDF: ${repoPdf}`,
        ...outputs.map((file) => `- ${file}`),
        '',
    ].join('\n');
    fs.writeFileSync(path.join(outDir, 'SUMMARY.md'), summary, 'utf8');
    console.log(JSON.stringify({ outDir, combinedPdf, repoPdf, outputs }, null, 2));
}

main().catch((error) => {
    console.error(error);
    process.exitCode = 1;
});
