import fs from 'node:fs';
import path from 'node:path';
import crypto from 'node:crypto';
import { pathToFileURL } from 'node:url';
import {
    GUIDE_FAMILIES, GUIDE_TOKENS, SvgGuideDocument, drawContextStrip,
    drawCompletionRow, drawRelatedGuideRows, guideReference,
    inspectRenderedGuideComponents,
} from './lib/guide-system.mjs';
import {
    browserLaunchOptions, evidencePath, guideOutputDir, loadGuideDependency,
    repoPdfOutputRoot,
} from './lib/guide-paths.mjs';

// These opt-in comparison pages do not alter any historical generator branch.
const { chromium } = loadGuideDependency('playwright');
const sharp = loadGuideDependency('sharp');
const date = '2026-09-08';
const work = process.env.SNIPEIT_GUIDE_OUT_DIR ?? guideOutputDir('audit-pilot-2026-09-08');
const output = process.env.SNIPEIT_GUIDE_PDF_OUT_DIR ?? path.join(repoPdfOutputRoot, 'audit-pilot-2026-09-08');
const colors = GUIDE_TOKENS.colors;
const sourceIds = {
    wfEntry: 'WF-ENTRY-MOBILE-03', wfCards: 'WF-NEUTRAL-MOBILE-03',
    people: 'USR-DASHBOARD-PEOPLE-NAV-DESKTOP-01', userList: 'USR-LIST-DESKTOP-01',
    inactive: 'USR-DEACTIVATED-DESKTOP-01', activated: 'USR-EDIT-ACTIVATED-DESKTOP-01',
    assignments: 'USR-ASSIGNMENTS-DESKTOP-01', deletion: 'USR-DELETE-DESKTOP-01',
    deleted: 'USR-DELETED-LIST-DESKTOP-01', restore: 'USR-RESTORE-DESKTOP-01',
    restored: 'USR-RESTORED-DESKTOP-01',
    catalog: 'CAT-COMPONENT-DEFINITION-ENTRY-DESKTOP-01',
    identity: 'CAT-COMPONENT-DEFINITION-IDENTITY-DESKTOP-02',
    children: 'CAT-COMPONENT-DEFINITION-CHILDREN-DESKTOP-02',
    contributions: 'CAT-COMPONENT-DEFINITION-CONTRIBUTIONS-DESKTOP-02',
    save: 'CAT-COMPONENT-DEFINITION-SAVE-DESKTOP-02',
};
const images = {};
for (const [key, id] of Object.entries(sourceIds)) {
    const bytes = fs.readFileSync(evidencePath(id));
    const metadata = await sharp(bytes).metadata();
    images[key] = { width: metadata.width, height: metadata.height,
        href: `data:image/${metadata.format};base64,${bytes.toString('base64')}` };
}

function text(doc, x, y, lines, width = 175, options = {}) {
    const size = options.size ?? 3.25;
    doc.text(x, y, lines, { size, lh: options.lh ?? 4.4, ...options,
        data: { 'qa-text': 'true', right: x + width } });
}
function panel(doc, x, y, w, h, fill = colors.white, stroke = colors.line) {
    doc.rect(x, y, w, h, fill, stroke, 0.45, 1.8);
}
function heading(doc, number, title, y, family, x = 12, width = 186) {
    doc.stepBadge(x, y + 1, number, GUIDE_FAMILIES[family].color);
    text(doc, x + 11, y + 2.5, title, width - 13, { size: 4, weight: 900 });
}
function shot(doc, key, label, caption, frame, crop, marks = [], family = 'CAT') {
    const area = crop ?? { x: 0, y: 0, w: images[key].width, h: images[key].height };
    const placement = doc.image(key, frame, area, { fit: 'contain' });
    const clip = { x: placement.x + area.x * placement.scale,
        y: placement.y + area.y * placement.scale,
        w: area.w * placement.scale, h: area.h * placement.scale };
    for (const mark of marks) {
        const box = doc.focusMark(placement, { padding: 3, shape: 'rect', ...mark });
        if (box.x < clip.x || box.y < clip.y || box.x + box.w > clip.x + clip.w
            || box.y + box.h > clip.y + clip.h) throw new Error(`Clipped focus: ${label}`);
    }
    doc.rect(frame.x, frame.y, frame.w, frame.h, 'none', 'none', 0, 0, 0,
        { 'qa-image': 'true', label });
    doc.imageBadge(frame.x - 1.2, frame.y - 1.2, label, GUIDE_FAMILIES[family].color);
    text(doc, frame.x + 1, frame.y + frame.h + 4, caption, frame.w - 1,
        { size: 2.4, lh: 3.2, fill: colors.muted });
}
function start(code, title, version, pageNumber, pageCount, purpose, role, needed, prerequisite) {
    const family = code.split('-')[0];
    const doc = new SvgGuideDocument(images, `${code}-v${version}-${pageNumber}`);
    doc.rect(12, 12, 2, 19, GUIDE_FAMILIES[family].color);
    text(doc, 18, 21, `${code} ${title}`, 143, { size: 5.6, weight: 900 });
    text(doc, 18, 29, purpose, 177, { size: 3, fill: colors.muted });
    panel(doc, 164, 12, 34, 12, GUIDE_FAMILIES[family].fill, GUIDE_FAMILIES[family].color);
    doc.centeredText(181, 16, `CONCEPT v${version}`, { size: 2.7, weight: 900 });
    doc.centeredText(181, 21, `Pagina ${pageNumber} van ${pageCount}`, { size: 2.25 });
    drawContextStrip(doc, [
        { label: 'Rol', value: role }, { label: 'Nodig', value: needed, size: 2.65 },
        { label: 'Vooraf', guide: guideReference(prerequisite), size: 2.35 },
    ]);
    return doc;
}
function end(doc, code, version, page, count, completion, references) {
    drawCompletionRow(doc, completion, { width: 186 });
    drawRelatedGuideRows(doc, references.map(([id, width, row = 1]) => guideReference(id, { width, row })),
        { rightEdge: 198 });
    text(doc, 12, 291, `${code} v${version} | ${date} | Concept - niet geaccepteerd`, 151,
        { size: 2.2, fill: colors.muted });
    text(doc, 198, 291, `${page} / ${count}`, 0,
        { size: 2.2, fill: colors.muted, anchor: 'end' });
    return doc.render();
}
function note(doc, y, title, lines, color = colors.orange) {
    const body = Array.isArray(lines) ? lines : [lines];
    panel(doc, 12, y, 186, 13 + body.length * 4.4, colors.orangeSoft, color);
    text(doc, 17, y + 6, title, 176, { size: 3.25, weight: 900, fill: color });
    text(doc, 17, y + 12, body, 176, { size: 3.1, lh: 4.4 });
}

function wf01() {
    const doc = start('WF-01', 'Workflow starten', 11, 1, 1,
        'Controleer eerst wat er al is. Kies daarna precies een route.',
        'Refurbisher', 'Juiste asset + profiel', 'SC-01');
    heading(doc, '1', 'Open Tests', 67, 'WF');
    text(doc, 23, 77, 'Tik op het test-icoon van het gecontroleerde asset.', 172);
    shot(doc, 'wfEntry', '1A', 'Test-icoon in de tabrij.', { x: 23, y: 84, w: 72, h: 17 },
        { x: 15, y: 413, w: 400, h: 101 }, [{ x: 266, y: 423, w: 42, h: 43, target: 'Tests' }], 'WF');
    text(doc, 106, 86, ['Scroll daarna onder de blauwe startknop.',
        'Bekijk eerst de bestaande workflows.', 'Druk nog niet op de startknop.'], 90, { size: 3.1 });

    heading(doc, '2', 'Controleer de bestaande workflows', 113, 'WF');
    text(doc, 23, 123, ['Zoek het afgesproken profiel. Vouw de regel open om de controles te bekijken.',
        'Is de juiste workflow nog niet af? Kies A. Bestaat er geen passende workflow? Kies B.',
        'Al afgerond, meerdere passende regels of twijfel? Vraag eerst een supervisor.'], 174, { size: 3.1 });

    panel(doc, 12, 140, 186, 67, colors.wfSoft, colors.wf);
    heading(doc, '3', 'Kies A of B - voer nooit beide uit', 143, 'WF');
    doc.line(105, 155, 105, 203);
    text(doc, 23, 157, 'A  Bestaande workflow vervolgen', 79, { size: 3.2, weight: 900 });
    text(doc, 23, 165, ['Tik bij de juiste onafgeronde regel', 'op Bewerk. Ga daarna naar stap 4.'], 79, { size: 3.1 });
    shot(doc, 'wfEntry', '3A', 'Bewerk opent deze workflow.', { x: 23, y: 177, w: 76, h: 18 },
        { x: 18, y: 879, w: 394, h: 48 }, [{ x: 269, y: 891, w: 53, h: 24, target: 'Bewerk' }], 'WF');
    text(doc, 113, 157, 'B  Alleen als er geen passende is', 79, { size: 3.2, weight: 900 });
    text(doc, 113, 165, ['Kies bovenaan het Workflowprofiel.', 'Tik eenmaal op Nieuwe workflow starten.'], 79, { size: 3.05 });
    shot(doc, 'wfEntry', '3B', 'Kies het afgesproken profiel.', { x: 113, y: 177, w: 78, h: 18 },
        { x: 18, y: 524, w: 394, h: 69 }, [{ x: 27, y: 551, w: 374, h: 30, target: 'Workflowprofiel' }], 'WF');

    heading(doc, '4', 'Controleer en ga verder', 218, 'WF');
    text(doc, 23, 228, ['De juiste kaarten moeten zichtbaar zijn.', 'Bij hervatten mogen resultaten al ingevuld zijn.',
        'Ga verder met de gids hieronder.'], 105, { size: 3.1 });
    shot(doc, 'wfCards', '4A', 'Voorbeeld: een resultaatkaart.', { x: 137, y: 217, w: 58, h: 26 },
        { x: 17, y: 326, w: 378, h: 162 }, [], 'WF');
    text(doc, 23, 247, 'Geen kaarten? Start niet opnieuw. Vraag een supervisor.', 170,
        { size: 3, weight: 800, fill: colors.orange });
    return end(doc, 'WF-01', 11, 1, 1, 'De juiste workflow is open; voer nu de controles uit.',
        [['WF-02', 93], ['SC-01', 75, 2], ['HELP-01', 64, 2]]);
}

const usrRefs = [['USR-02', 85], ['HELP-01', 65, 2]];
function usrStart(page, title, purpose, needed) {
    return start('USR-04', title, 4, page, 3, purpose, 'Admin', needed, 'AC-01');
}
function usrA() {
    const doc = usrStart(1, 'Gebruiker uitschakelen', 'Kies eerst de taak. De drie routes zijn geen vervolgstappen.', 'Stopbesluit + opvolger');
    panel(doc, 12, 62, 186, 27, colors.usrSoft, colors.usr);
    text(doc, 17, 69, 'KIES EEN ROUTE', 176, { size: 3.2, weight: 900, fill: colors.usr });
    text(doc, 17, 75, ['A  Alleen login stoppen: blijf op deze pagina.',
        'B  Account verwijderen: ga naar pagina 2.',
        'C  Verwijderd account herstellen: ga naar pagina 3.'], 176, { size: 3.2 });
    heading(doc, '1', 'A: zoek en controleer het account', 100, 'USR');
    text(doc, 23, 110, ['Open Personen > Toon Alles. Zoek de naam of gebruikersnaam.',
        'Open het account en vergelijk beide met je opdracht.'], 174, { size: 3.2 });
    shot(doc, 'inactive', '1A', 'Voorbeeldidentiteit: Mila de Boer / Miladb.', { x: 23, y: 122, w: 110, h: 16 },
        { x: 76, y: 261, w: 860, h: 78 }, [], 'USR');
    text(doc, 143, 126, ['Identiteit onzeker?', 'Wijzig niets.', 'Vraag je supervisor.'], 53,
        { size: 3.1, weight: 800, fill: colors.help });

    heading(doc, '2', 'Controleer en draag eigendom over', 152, 'USR');
    text(doc, 23, 162, ['Bekijk Apparaten, Licenties, Accessoires en Verbruiksartikelen.',
        'Controleer ook Beheerde locaties en Beheerde gebruikers.',
        'Draag items en beheer over aan de afgesproken opvolger.',
        'Geen opvolger of overdracht onduidelijk? Vraag de verantwoordelijke.'], 174, { size: 3.2 });

    heading(doc, '3', 'Zet login uit en sla op', 188, 'USR');
    text(doc, 23, 198, ['Kies Gebruiker aanpassen. Haal het vinkje weg.',
        'Scroll naar onderen en kies Opslaan.'], 108, { size: 3.2 });
    shot(doc, 'activated', '3A', 'Haal dit vinkje weg.', { x: 138, y: 192, w: 57, h: 14 },
        { x: 465, y: 455, w: 271, h: 45 }, [{ x: 475, y: 466, w: 23, h: 24, target: 'Loginvinkje' }], 'USR');

    heading(doc, '4', 'Controleer Login ingeschakeld: Nee', 224, 'USR');
    text(doc, 23, 234, ['Open hetzelfde account opnieuw. Controleer naam, gebruikersnaam en Nee.',
        'Klopt dit en is het eigendom overgedragen? Klaar. Ga niet naar pagina 2.'], 174, { size: 3.05 });
    return end(doc, 'USR-04', 4, 1, 3, 'Login staat uit en eigendom is overgedragen. Route A is klaar.', usrRefs);
}
function usrB() {
    const doc = usrStart(2, 'Account verwijderen', 'Route B - alleen na een afzonderlijk verwijderbesluit.', 'Verwijderbesluit');
    note(doc, 63, 'Alleen toegang stoppen? Gebruik route A op pagina 1.',
        'Verwijder nooit je eigen account. Herstellen is geen stap na verwijderen.');
    heading(doc, '1', 'Controleer identiteit en besluit', 100, 'USR');
    text(doc, 23, 110, ['Open Personen > Toon Alles. Zoek en open het juiste account.',
        'Vergelijk naam en gebruikersnaam. Controleer dat verwijderen is afgesproken.',
        'Onzeker? Wijzig niets en vraag de verantwoordelijke.'], 174);
    heading(doc, '2', 'Controleer alle toewijzingen en beheer', 137, 'USR');
    text(doc, 23, 147, ['Er mogen geen apparaten, licenties of accessoires meer toegewezen zijn.',
        'Controleer ook verbruiksartikelen en leg de overdracht daarvan vast.',
        'Wijs Beheerde locaties en Beheerde gebruikers toe aan de opvolger.',
        'Nog iets open? Rond die overdracht eerst af; behoud de geschiedenis.'], 174, { size: 3.15 });
    heading(doc, '3', 'Kies alleen Verwijder en bevestig', 177, 'USR');
    text(doc, 23, 187, ['Klik op Verwijder. Lees de bevestiging en controleer de naam.',
        'Bevestig alleen als die klopt. Gebruik de bulkactie eronder niet.'], 174, { size: 3.1 });
    shot(doc, 'deletion', '3A', 'Kies Verwijder, niet Check Alles In / Verwijder Gebruiker.',
        { x: 23, y: 201, w: 111, h: 16 }, { x: 970, y: 585, w: 275, h: 88 },
        [{ x: 986, y: 596, w: 239, h: 29, target: 'Verwijder' }], 'USR');
    text(doc, 145, 204, ['Geblokkeerd?', 'Omzeil dit niet.', 'Vraag een Admin.'], 50,
        { size: 3.1, fill: colors.orange });
    heading(doc, '4', 'Controleer de verwijderde gebruiker', 232, 'USR');
    text(doc, 23, 242, ['Open Personen > Verwijderde Gebruikers. Zoek dezelfde identiteit.',
        'Daar gevonden? Route B is klaar. Herstel het account nu niet.'], 174, { size: 3.05 });
    return end(doc, 'USR-04', 4, 2, 3, 'De juiste identiteit staat bij Verwijderde Gebruikers. Route B is klaar.', usrRefs);
}
function usrC() {
    const doc = usrStart(3, 'Account herstellen', 'Route C - begin bij een account dat al verwijderd is.', 'Herstelbesluit + rol');
    heading(doc, '1', 'Zoek de bestaande verwijderde identiteit', 69, 'USR');
    text(doc, 23, 79, ['Open Personen > Verwijderde Gebruikers. Zoek de naam of gebruikersnaam.',
        'Open de bestaande identiteit. Maak geen vervangend account.'], 174);
    shot(doc, 'deleted', '1A', 'Vergelijk naam en gebruikersnaam voordat je het account opent.',
        { x: 23, y: 94, w: 171, h: 25 }, { x: 75, y: 287, w: 1164, h: 93 }, [], 'USR');
    heading(doc, '2', 'Beoordeel toegang voordat je herstelt', 134, 'USR');
    text(doc, 23, 144, ['Controleer naam, gebruikersnaam, groep en Login ingeschakeld.',
        'Herstel bewaart de eerdere logininstelling. Login kan dus meteen weer aan zijn.',
        'Staat login op Ja, of is de bedoelde toegang onzeker? Herstel nog niet.',
        'Laat de verantwoordelijke Admin eerst de toegang beoordelen.'], 174, { size: 3.1 });
    shot(doc, 'restore', '2A', 'Controleer de bestaande logininstelling; dit voorbeeld toont Nee.',
        { x: 23, y: 166, w: 116, h: 12 }, { x: 75, y: 535, w: 860, h: 43 }, [], 'USR');
    heading(doc, '3', 'Herstel het gecontroleerde account', 190, 'USR');
    text(doc, 23, 200, ['Login staat op Nee en het herstelbesluit is duidelijk?',
        'Klik op Herstel. Wacht op de resultaatmelding.'], 115, { size: 3.15 });
    shot(doc, 'restore', '3A', 'Herstel dit bestaande record.', { x: 145, y: 193, w: 49, h: 13 },
        { x: 975, y: 626, w: 260, h: 45 }, [{ x: 986, y: 636, w: 239, h: 29, target: 'Herstel' }], 'USR');
    heading(doc, '4', 'Open opnieuw en controleer het resultaat', 224, 'USR');
    text(doc, 23, 234, ['Open Personen > Toon Alles. Zoek dezelfde naam en gebruikersnaam.',
        'Controleer login en actuele rechten. Voor rechten: gebruik de gids hieronder.',
        'Login opnieuw aanzetten vraagt een apart toegangsbesluit.'], 174, { size: 3.05 });
    return end(doc, 'USR-04', 4, 3, 3, 'De identiteit is hersteld en toegang is gecontroleerd. Route C is klaar.', usrRefs);
}

function catStart(page, title, purpose) {
    const doc = start('CAT-04', 'Componentdefinities beheren', 3, page, 6, purpose,
        'Supervisor', 'Onderdeelbron + definities', 'CAT-00');
    heading(doc, String(page), title, 69, 'CAT');
    return doc;
}
const catRefs = [['CAT-00', 70], ['CAT-03', 70, 2]];
function catEnd(doc, page, message, references = catRefs) {
    return end(doc, 'CAT-04', 3, page, 6, message, references);
}
function cat1() {
    const doc = catStart(1, 'Zoek eerst en kies je route', 'Een componentdefinitie beschrijft een herbruikbaar type fysiek onderdeel.');
    text(doc, 23, 81, ['Open Instellingen > Component Definitions.',
        'Zoek op naam, part code of modelnummer. Klik op Search.',
        'Vergelijk ook categorie en fabrikant; een vergelijkbare naam is niet genoeg.'], 174);
    shot(doc, 'catalog', '1A', 'Zoek de definitie; rechts staan Create New, Search en Edit.',
        { x: 23, y: 101, w: 171, h: 39 }, { x: 230, y: 106, w: 1125, h: 208 },
        [{ x: 1100, y: 116, w: 183, h: 32, target: 'Zoekveld' }]);
    text(doc, 23, 152, 'Kies precies een route:', 174, { size: 3.5, weight: 900 });
    text(doc, 23, 162, ['A  Dezelfde definitie, alles klopt: hergebruik deze. Ga naar pagina 6.',
        'B  Dezelfde definitie, gegevens corrigeren: kies Edit. Ga naar pagina 2.',
        'C  Geen passende definitie: kies Create New. Ga naar pagina 2.'], 174, { size: 3.25, lh: 7 });
    note(doc, 186, 'Controleer het gebruik voordat je een bestaande definitie wijzigt.',
        ['Instances = aantal fysieke componentrecords met deze definitie.',
            'Templates = aantal modelnummerbaselines die deze definitie verwachten.']);
    text(doc, 23, 222, ['Alleen een eigenschap nodig, zoals kleur of capaciteit?',
        'Gebruik een attribuutdefinitie. Bespreek de keuze via CAT-03 hieronder.',
        'Twijfel of een bestaande definitie past? Maak geen duplicaat; vraag hulp.'], 174, { size: 3.2 });
    return catEnd(doc, 1, 'Je hebt hergebruik, bewerken of nieuw gekozen; volg alleen die route.');
}
function cat2() {
    const doc = catStart(2, 'Vul de herbruikbare identiteit in', 'Werk op hetzelfde formulier. Sla pas op na de controle op pagina 5.');
    shot(doc, 'identity', '2A', 'Identiteit, korte omschrijving en optioneel label op hetzelfde formulier.',
        { x: 23, y: 83, w: 171, h: 55 }, { x: 65, y: 155, w: 1280, h: 482 });
    const rows = [
        ['Name', 'Herkenbaar type onderdeel; nooit een unieke tag of serienummer.'],
        ['Part Code', 'Geverifieerde fabrikant- of leverancierscode, indien aanwezig.'],
        ['Model Number', 'Geverifieerde modelcode van het onderdeel, indien aanwezig.'],
        ['Category', 'Categorie van dit onderdeel; niet van het complete apparaat.'],
        ['Manufacturer', 'Werkelijke fabrikant. Onbekend? Laat leeg; gok niet.'],
        ['Specification Summary', 'Optionele korte herkenning; herhaal geen attribuutwaarden.'],
        ['Spec Display Label', 'Optioneel leesbaar label. Leeg gebruikt het gegenereerde label.'],
    ];
    rows.forEach(([label, body], i) => {
        const y = 146 + i * 12.5;
        text(doc, 23, y, label, 174, { size: 3.2, weight: 900 });
        text(doc, 23, y + 5, body, 174, { size: 3.15 });
    });
    text(doc, 23, 243, 'Wijzig Active niet in deze route. Lifecycle wijzigen vraagt een Admin.', 174,
        { size: 3.1, weight: 800, fill: colors.orange });
    return catEnd(doc, 2, 'De identiteit klopt. Ga naar pagina 3 voor eventuele verwachte onderdelen.');
}
function cat3() {
    const doc = catStart(3, 'Voeg alleen normale verwachte onderdelen toe', 'Een verwacht onderdeel is zelf een bestaande componentdefinitie.');
    text(doc, 23, 81, ['Geen vaste onderdelen in deze definitie? Sla deze pagina over; ga naar pagina 4.',
        'Wel vaste onderdelen? Scroll naar Expected Subcomponents.'], 174, { size: 3.2 });
    shot(doc, 'contributions', '3A', 'Deze knop staat onder de bestaande verwachte onderdelen.',
        { x: 23, y: 96, w: 96, h: 15 }, { x: 66, y: 73, w: 410, h: 52 },
        [{ x: 76, y: 82, w: 208, h: 33, target: 'Add Expected Subcomponent' }]);
    text(doc, 23, 125, ['1. Klik op Add Expected Subcomponent. Er verschijnt een nieuwe rij.',
        '2. Typ bij Component Definition en klik op de passende zoekuitkomst.',
        '3. Laat Expected Name leeg, tenzij een duidelijkere herkenningsnaam nodig is.',
        '4. Vul bij Quantity het normale aantal in: minstens 1. Laat Required aan.',
        '5. Open Notes alleen als extra herkenning of plaatsing nodig is.'], 174, { size: 3.15, lh: 5.2 });
    shot(doc, 'children', '3B', 'Voorbeeld van een ingevulde rij met Required aangevinkt.',
        { x: 23, y: 159, w: 171, h: 36 }, { x: 75, y: 162, w: 1265, h: 174 });
    note(doc, 208, 'Voeg alleen onderdelen toe die normaal altijd aanwezig horen te zijn.',
        ['Een optioneel of per apparaat wisselend onderdeel hoort hier niet.',
            'Blijf bij een niveau: deze definitie en haar verwachte onderdelen.']);
    text(doc, 23, 239, ['Definitie ontbreekt? Vraag een Supervisor om die eerst voor te bereiden.',
        'Noteer je invoer voordat je dit formulier verlaat. Sla geen onvolledige rij op.'], 174,
        { size: 3, fill: colors.orange });
    return catEnd(doc, 3, 'De verwachte onderdelen kloppen, of zijn niet nodig. Ga naar pagina 4.');
}
function cat4() {
    const doc = catStart(4, 'Voeg de attribuutwaarden toe', 'Een attribuut beschrijft een herbruikbare eigenschap van het onderdeel.');
    text(doc, 23, 81, ['Scroll naar Attribute Contributions. Klik op Add Attribute Contribution.',
        'Typ bij Attribute en klik op de juiste bestaande attribuutdefinitie.'], 174, { size: 3.2 });
    shot(doc, 'save', '4A', 'Klik op Add Attribute Contribution om een nieuwe rij te openen.',
        { x: 23, y: 96, w: 100, h: 14 }, { x: 66, y: 743, w: 410, h: 55 },
        [{ x: 76, y: 752, w: 191, h: 33, target: 'Add Attribute Contribution' }]);
    text(doc, 23, 124, ['Vul bij Value de geverifieerde waarde in. Volg het getoonde datatype:',
        'getal met de juiste eenheid en grenzen, een keuzewaarde, Yes/No of tekst.',
        'Kies daarna bewust de twee vinkjes. Herhaal dit voor iedere extra eigenschap.'], 174, { size: 3.2 });
    shot(doc, 'contributions', '4B', 'Voorbeeld: een getal met grenzen en beide weergavekeuzes.',
        { x: 23, y: 143, w: 171, h: 38 }, { x: 75, y: 450, w: 1265, h: 211 });
    text(doc, 23, 193, 'Show as asset spec', 174, { size: 3.4, weight: 900 });
    text(doc, 23, 199, 'Aan: deze waarde draagt bij aan de berekende assetspecificatie.', 174);
    text(doc, 23, 208, 'Use in component label', 174, { size: 3.4, weight: 900 });
    text(doc, 23, 214, 'Aan: gebruik de waarde in het componentlabel als dat zo is ingesteld.', 174);
    text(doc, 23, 226, ['Ontbreekt een attribuut? Noteer wat ontbreekt en laat dit via CAT-03 voorbereiden.',
        'Bewaar je invoergegevens apart voordat je dit onopgeslagen formulier verlaat.',
        'Keer daarna terug: open de bestaande definitie, of vul een nieuwe opnieuw in.',
        'Voeg pas dan de complete attribuutrij toe.'], 174,
        { size: 3.05, fill: colors.orange });
    return catEnd(doc, 4, 'Elke bijdrage heeft een geldige waarde en bewuste vinkjes. Ga naar pagina 5.');
}
function cat5() {
    const doc = catStart(5, 'Controleer de bijdragen en sla op', 'Vergelijk deze definitie met haar verwachte onderdelen.');
    text(doc, 23, 81, ['Controleer of hetzelfde feit op beide niveaus wordt bijgedragen.',
        'Een gekoppeld onderdeel kan de bovenliggende waarde vervangen.'], 174);
    panel(doc, 23, 94, 171, 37, colors.cmpSoft, colors.cmp);
    text(doc, 28, 102, 'Uitlegvoorbeeld - dit is geen schermmelding', 161,
        { size: 3.25, weight: 900, fill: colors.cmp });
    text(doc, 28, 111, ['Bovenliggende definitie: geheugencapaciteit 8 GB.',
        'Gekoppeld onderdeel: dezelfde bijdrage 8 GB.',
        'De onderliggende bijdrage vervangt de bovenliggende: niet dubbel optellen.'], 161,
        { size: 3.1, lh: 5 });
    text(doc, 23, 143, ['Hierarchy overlap warning meldt overlap voor getalattributen die op',
        'beide niveaus bijdragen aan de assetspecificatie. Geen waarschuwing',
        'betekent niet dat alle gegevens kloppen. Controleer ook andere feiten.'], 174, { size: 3.2 });
    text(doc, 23, 164, ['Bepaal welk niveau de waarde moet leveren. Corrigeer een onbedoeld duplicaat.',
        'Moet een opgeslagen rij weg? Laat een Admin die opruimen. Wacht met hergebruik.',
        'Is de bedoelde opbouw onduidelijk? Laat die eerst beoordelen.'], 174, { size: 3.1 });
    shot(doc, 'save', '5A', 'Bij bewerken: Save Changes. Bij nieuw: Create Definition.',
        { x: 23, y: 185, w: 107, h: 17 }, { x: 65, y: 803, w: 515, h: 65 },
        [{ x: 76, y: 822, w: 110, h: 32, target: 'Save Changes' }]);
    text(doc, 23, 217, ['Alles gecontroleerd? Klik eenmaal op de opslaanknop.',
        'Verschijnt een fout? Blijf op het formulier, lees de melding en corrigeer het veld.',
        'Lukt dat niet, vraag hulp. Maak geen tweede definitie als oplossing.'], 174, { size: 3.2 });
    text(doc, 23, 241, 'Controleer na opslaan opnieuw: een waarschuwing kan pas dan zichtbaar worden.', 174,
        { size: 3.05, weight: 800, fill: colors.orange });
    return catEnd(doc, 5, 'De opslag is bevestigd. Controleer de opgeslagen inhoud op pagina 6.');
}
function cat6() {
    const doc = catStart(6, 'Controleer het resultaat en keer terug', 'Gebruik dezelfde definitie in de taak waarvoor je haar nodig had.');
    text(doc, 23, 81, ['Open Instellingen > Component Definitions. Zoek dezelfde naam of code.',
        'Controleer de rij. Open Edit om de opgeslagen gegevens opnieuw te bekijken.'], 174);
    shot(doc, 'catalog', '6A', 'Bestaand voorbeeld: naam, categorie, fabrikant, gebruik en Active.',
        { x: 23, y: 97, w: 171, h: 34 }, { x: 239, y: 166, w: 1110, h: 145 });
    text(doc, 23, 145, ['Vergelijk identiteit, verwachte onderdelen, aantallen en attribuutwaarden.',
        'Controleer de vinkjes en lees eventuele waarschuwingen. Wijzig niets bij twijfel.',
        'Klopt alles? Kies Cancel om deze controle zonder nieuwe wijzigingen te verlaten.'], 174, { size: 3.2 });
    text(doc, 23, 168, 'Kies alleen de terugroute die bij je oorspronkelijke taak hoort:', 174,
        { size: 3.4, weight: 900 });
    doc.guideChip(23, 175, 99, guideReference('CAT-02'));
    text(doc, 23, 189, ['Voor een modelnummer: selecteer deze definitie als verwacht component.',
        'Keer terug naar de stap waar de definitie ontbrak; controleer dat modelnummer.'], 174, { size: 3.1 });
    doc.guideChip(23, 202, 113, guideReference('CMP-02'));
    text(doc, 23, 216, ['Voor een fysiek onderdeel: geef de definitie door aan de Senior Refurbisher.',
        'Die registreert en plaatst het fysieke exemplaar vanuit deze definitie.'], 174, { size: 3.1 });
    text(doc, 23, 233, ['Opruimen of deactiveren nodig? Vraag een Admin. CAT-05 is nog in voorbereiding.',
        'Maak geen tweede definitie voor een uniek serienummer of Inbit-label.'], 174,
        { size: 3.05, fill: colors.orange });
    return catEnd(doc, 6, 'De opgeslagen definitie klopt en de oorspronkelijke taak kan verder.',
        [['CAT-02', 99], ['CMP-02', 113, 2]]);
}

const guides = [
    { code: 'WF-01', version: 11, title: 'Workflow starten', pages: [wf01],
        file: 'WF-01-workflow-starten-v11-draft.pdf', baseline: 10, accepted: 9 },
    { code: 'USR-04', version: 4, title: 'Gebruiker uitschakelen of herstellen', pages: [usrA, usrB, usrC],
        file: 'USR-04-gebruiker-uitschakelen-of-herstellen-v4-draft.pdf', baseline: 3, accepted: null },
    { code: 'CAT-04', version: 3, title: 'Componentdefinities beheren', pages: [cat1, cat2, cat3, cat4, cat5, cat6],
        file: 'CAT-04-componentdefinities-beheren-v3-draft.pdf', baseline: 2, accepted: null },
];

async function inspectLayout(page) {
    return page.evaluate(() => {
        const errors = [];
        const texts = [...document.querySelectorAll('text[data-qa-text]')];
        for (const element of texts) {
            const b = element.getBBox();
            if (b.x + b.width > Number(element.dataset.right) + .1 || b.y < 8 || b.y + b.height > 295)
                errors.push(`Text out of bounds: ${element.textContent}`);
            for (const frame of document.querySelectorAll('[data-qa-image]')) {
                if (element.closest('svg') !== frame.closest('svg')) continue;
                const f = frame.getBBox();
                if (b.x < f.x + f.width - .2 && b.x + b.width > f.x + .2
                    && b.y < f.y + f.height - .2 && b.y + b.height > f.y + .2)
                    errors.push(`Text intersects image ${frame.dataset.label}: ${element.textContent}`);
            }
            for (const badge of element.closest('svg').querySelectorAll('[data-component="image-badge"] circle')) {
                const circle = badge.getBBox();
                if (b.x < circle.x + circle.width && b.x + b.width > circle.x
                    && b.y < circle.y + circle.height && b.y + b.height > circle.y)
                    errors.push(`Text intersects an image badge: ${element.textContent}`);
            }
        }
        for (let i = 0; i < texts.length; i++) for (let j = i + 1; j < texts.length; j++) {
            if (texts[i].closest('svg') !== texts[j].closest('svg')) continue;
            const a = texts[i].getBBox(); const b = texts[j].getBBox();
            if (a.x < b.x + b.width - .1 && a.x + a.width > b.x + .1
                && a.y < b.y + b.height - .1 && a.y + a.height > b.y + .1)
                errors.push(`Text intersection: ${texts[i].textContent} / ${texts[j].textContent}`);
        }
        return { errors, textBlocks: texts.length };
    });
}

fs.mkdirSync(work, { recursive: true });
fs.mkdirSync(output, { recursive: true });
const browser = await chromium.launch(browserLaunchOptions({ headless: true }));
const results = [];
try {
    for (const guide of guides) {
        const pages = guide.pages.map(fn => fn());
        const html = '<!doctype html><html lang="nl"><meta charset="utf-8"><title>' + guide.code
            + ' auditconcept</title><style>@page{size:A4;margin:0}*{box-sizing:border-box}'
            + 'body{margin:0}.page{width:210mm;height:297mm;break-after:page}.page:last-child{break-after:auto}'
            + '.page>svg{display:block;width:210mm;height:297mm}</style><body>'
            + pages.map(svg => `<div class="page">${svg.replace(/^<\?xml[^>]+>/, '')}</div>`).join('') + '</body></html>';
        const htmlPath = path.join(work, `${guide.code}-v${guide.version}.html`);
        fs.writeFileSync(htmlPath, html);
        const page = await browser.newPage({ viewport: { width: 794, height: 1123 }, deviceScaleFactor: 1 });
        await page.goto(pathToFileURL(htmlPath).href);
        await page.evaluate(() => document.fonts.ready);
        const componentQa = await inspectRenderedGuideComponents(page);
        const layoutQa = await inspectLayout(page);
        if (layoutQa.errors.length) throw new Error(`${guide.code}: ${layoutQa.errors.join('\n')}`);
        await page.pdf({ path: path.join(output, guide.file), format: 'A4', printBackground: true,
            preferCSSPageSize: true, tagged: true, displayHeaderFooter: false });
        fs.writeFileSync(path.join(work, `${guide.code}-qa.json`), JSON.stringify({ componentQa, layoutQa }, null, 2));
        await page.close();
        results.push({ code: guide.code, title: guide.title, version: guide.version, pages: pages.length,
            status: 'Unaccepted working draft', baselineVersion: guide.baseline,
            acceptedPredecessorVersion: guide.accepted, file: guide.file,
            sha256: crypto.createHash('sha256').update(fs.readFileSync(path.join(output, guide.file))).digest('hex') });
    }
} finally { await browser.close(); }
fs.writeFileSync(path.join(output, 'manifest.json'), JSON.stringify({ schemaVersion: 1, round: date,
    statusDefinition: 'Audit comparison proposals; no baseline selection or acceptance changed', artifacts: results }, null, 2) + '\n');
console.log(JSON.stringify({ output, guides: results }, null, 2));
