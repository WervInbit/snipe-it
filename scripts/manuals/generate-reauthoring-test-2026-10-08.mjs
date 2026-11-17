import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import crypto from 'node:crypto';
import { spawnSync } from 'node:child_process';
import { pathToFileURL } from 'node:url';
import {
    GUIDE_TOKENS, GUIDE_FAMILIES, SvgGuideDocument, guideReference,
    drawContextStrip, layoutRelatedGuides, inspectRenderedGuideComponents,
} from './lib/guide-system.mjs';
import {
    repoRoot, repoPdfOutputRoot, evidencePath, loadGuideDependency,
    resolveChromeExecutable,
} from './lib/guide-paths.mjs';

// This round has its own composition and never writes into a retained round.
const round = path.join(repoRoot, 'resources/manuals/operator-guides/review-rounds/2026-10-08-test-guides');
const proof = path.join(repoRoot, 'output/manuals/proofs/reauthoring-test-2026-10-08');
assert.ok(!fs.existsSync(path.join(round, 'manifest.sha256')), 'This round is retained. Render its saved HTML to another destination or create a new version.');
const sharp = loadGuideDependency('sharp');
const { chromium } = loadGuideDependency('playwright');
const C = GUIDE_TOKENS.colors;
const hash = file => crypto.createHash('sha256').update(fs.readFileSync(file)).digest('hex');
const rel = file => path.relative(repoRoot, file).replaceAll('\\', '/');
const images = {};
const sources = [];
const figures = [];
fs.mkdirSync(path.join(round, 'crops'), { recursive: true });
fs.mkdirSync(path.join(round, 'inputs'), { recursive: true });
fs.mkdirSync(proof, { recursive: true });
fs.mkdirSync(repoPdfOutputRoot, { recursive: true });

const sourceFile = id => path.join(round, 'evidence', `${id}.png`);
for (const id of ['PHONE-START-01', 'SCAN-CAMERA-QR-01', 'AST-LABEL-PLACEMENT-PHOTO-01']) {
    const original = evidencePath(id);
    const retained = path.join(round, 'evidence', path.basename(original));
    if (!fs.existsSync(retained)) fs.copyFileSync(original, retained);
    assert.equal(hash(original), hash(retained));
}
const reuseFile = id => path.join(round, 'evidence', `${id}.jpg`);

async function crop(key, file, bounds, job, marks = [], provenance = {}) {
    const [left, top, width, height] = bounds;
    const meta = await sharp(file).metadata();
    assert.ok(left >= 0 && top >= 0 && left + width <= meta.width && top + height <= meta.height, `${key}: crop outside source`);
    const output = path.join(round, 'crops', `${key}.png`);
    const bytes = await sharp(file).extract({ left, top, width, height }).png().toBuffer();
    fs.writeFileSync(output, bytes);
    images[key] = { href: `data:image/png;base64,${bytes.toString('base64')}`, width, height };
    const localMarks = marks.map(mark => ({ ...mark, x: mark.x - left, y: mark.y - top }));
    for (const m of localMarks) {
        const p = m.padding ?? 0;
        assert.ok(m.x - p >= 0 && m.y - p >= 0 && m.x + m.w + p <= width && m.y + m.h + p <= height, `${key}: clipped focus ${m.target}`);
    }
    sources.push({ key, source: rel(file), sourceSha256: hash(file), sourcePixels: { width: meta.width, height: meta.height }, cropPixels: { x: left, y: top, w: width, h: height }, crop: rel(output), cropSha256: hash(output), job, marks: localMarks, ...provenance });
}

await crop('phone', reuseFile('PHONE-START-01'), [0, 0, 1080, 1780], 'Recognize the phone shortcut among the launcher icons.', [
    { x: 64, y: 163, w: 172, h: 180, padding: 14, shape: 'ellipse', target: 'Inbit Snipe-IT shortcut' },
], { reusedCanonical: 'PHONE-START-01' });
await crop('login', sourceFile('LOGIN-NL-MOBILE-20261008'), [68, 144, 694, 770], 'Locate the Dutch login form and sign-in action.', [
    { x: 89, y: 756, w: 653, h: 68, padding: 7, target: 'Inloggen' },
], { locale: 'nl-NL', role: 'signed out' });
await crop('dashboard', sourceFile('DASH-demo_refurbisher-NL-MOBILE-20261008'), [12, 266, 805, 385], 'Recognize successful sign-in from Dashboard and its first two tiles.', [
    { x: 25, y: 285, w: 263, h: 41, padding: 9, target: 'Dashboard heading' },
], { locale: 'nl-NL', role: 'Refurbisher' });
await crop('scanner-entry', sourceFile('DASH-demo_refurbisher-NL-MOBILE-20261008'), [12, 266, 805, 385], 'Locate the scanner tile on the recognizable dashboard.', [
    { x: 447, y: 379, w: 353, h: 254, padding: 7, target: 'Scan QR tile' },
], { locale: 'nl-NL', role: 'Refurbisher' });
await crop('camera-entry', sourceFile('DASH-demo_refurbisher-NL-MOBILE-20261008'), [607, 128, 210, 107], 'Locate the camera icon beside the account icon in the top bar.', [
    { x: 653, y: 158, w: 40, h: 42, padding: 10, shape: 'ellipse', target: 'top-bar camera' },
], { locale: 'nl-NL', role: 'Refurbisher' });
await crop('scan-photo', reuseFile('SCAN-CAMERA-QR-01'), [50, 350, 980, 1320], 'Recognize the physical QR target in the camera image; illustrative physical example.', [
    { x: 536, y: 1139, w: 171, h: 151, padding: 15, target: 'physical QR label' },
], { reusedCanonical: 'SCAN-CAMERA-QR-01', illustrativeOnly: true, note: 'Crop excludes legacy untranslated camera controls; not the synthetic asset.' });
await crop('manual', sourceFile('SC-MANUAL-NL-MOBILE-20261008'), [32, 2618, 768, 135], 'Locate Handmatige invoer, the filled asset tag and Zoeken beneath the scanner.', [
    { x: 38, y: 2674, w: 613, h: 68, padding: 4, target: 'manual asset tag' },
    { x: 656, y: 2674, w: 137, h: 68, padding: 4, target: 'Zoeken' },
], { locale: 'nl-NL', role: 'Refurbisher', asset: 'GUIDE-20261008-01' });
await crop('sc-title', sourceFile('SC-IDENTITY-NL-MOBILE-20261008'), [16, 273, 789, 325], 'Recognize the opened asset title, model and selected Info tab.', [
    { x: 24, y: 378, w: 344, h: 47, padding: 5, target: 'model in asset title' },
    { x: 44, y: 526, w: 80, h: 64, padding: 4, shape: 'ellipse', target: 'Info tab' },
], { locale: 'nl-NL', role: 'Refurbisher', asset: 'GUIDE-20261008-01' });
await crop('sc-identity', sourceFile('SC-IDENTITY-NL-MOBILE-20261008'), [25, 2210, 725, 300], 'Read Assettag and Serienummer on Info; linked to the adjacent page overview.', [
    { x: 41, y: 2298, w: 267, h: 42, padding: 6, target: 'asset tag value' },
    { x: 41, y: 2447, w: 302, h: 42, padding: 6, target: 'serial value' },
], { locale: 'nl-NL', role: 'Refurbisher', asset: 'GUIDE-20261008-01' });
await crop('ast-title', sourceFile('AST-STATUS-NL-MOBILE-20261008'), [16, 397, 789, 170], 'Read the asset/model title before reviewing its Info fields.', [
    { x: 24, y: 493, w: 345, h: 48, padding: 6, target: 'model in asset title' },
], { locale: 'nl-NL', role: 'Supervisor', asset: 'GUIDE-20261008-01' });
await crop('ast-identity', sourceFile('AST-STATUS-NL-MOBILE-20261008'), [25, 2575, 725, 445], 'Verify the same asset tag, serial and incoming QA Hold state.', [
    { x: 41, y: 2662, w: 269, h: 43, padding: 8, target: 'asset tag value' },
    { x: 41, y: 2814, w: 308, h: 45, padding: 8, target: 'serial value' },
    { x: 42, y: 2956, w: 241, h: 43, padding: 8, target: 'QA Hold review state' },
], { locale: 'nl-NL', role: 'Supervisor', asset: 'GUIDE-20261008-01' });
await crop('required', sourceFile('AST-STATUS-NL-MOBILE-20261008'), [25, 3864, 725, 295], 'Recognize the current required workflows on Info and the route to their results.', [
    { x: 40, y: 3944, w: 564, h: 143, padding: 6, target: 'three current completed required workflows' },
    { x: 51, y: 4095, w: 225, h: 37, padding: 6, target: 'Open Workflows' },
], { locale: 'nl-NL', role: 'Supervisor', asset: 'GUIDE-20261008-01', translationGap: 'Required workflows / Completed successfully / Open Workflows' });
await crop('labels', reuseFile('AST-LABEL-PLACEMENT-PHOTO-01'), [0, 0, 1536, 1062], 'Locate manufacturer identification and the asset label on a physical device.', [
    { x: 493, y: 448, w: 480, h: 66, padding: 14, target: 'manufacturer model and serial label' },
    { x: 1310, y: 729, w: 119, h: 118, padding: 14, target: 'asset QR label' },
], { reusedCanonical: 'AST-LABEL-PLACEMENT-PHOTO-01', illustrativeOnly: true, note: 'Label-location example, not the synthetic asset; do not read numerical identity from the photograph.' });
await crop('component-review', sourceFile('AST-COMPONENTS-NL-MOBILE-20261008'), [51, 832, 729, 288], 'Recognize the component panel and distinguish its assumed model baseline from physical verification.', [
    { x: 98, y: 1060, w: 486, h: 42, padding: 6, target: 'Model baseline and Assumed' },
], { locale: 'nl-NL', role: 'Supervisor', asset: 'GUIDE-20261008-01', note: 'Captured after release; component display unchanged, not evidence of physical installation.', translationGap: 'Current Components / Model baseline / Assumed' });
await crop('status-note', sourceFile('AST-STATUS-NL-MOBILE-20261008'), [25, 2880, 725, 425], 'Locate Status and Statusnotitie together before deciding.', [
    { x: 43, y: 3022, w: 685, h: 68, padding: 4, target: 'Status selector' },
    { x: 43, y: 3169, w: 685, h: 108, padding: 4, target: 'Statusnotitie' },
], { locale: 'nl-NL', role: 'Supervisor', asset: 'GUIDE-20261008-01' });
await crop('confirm', sourceFile('AST-CONFIRM-NL-MOBILE-20261008'), [16, 994, 798, 543], 'Read the genuine clean QA Hold to Ready for Sale confirmation and choose its confirm action.', [
    { x: 51, y: 1134, w: 640, h: 48, padding: 8, target: 'old and new status' },
    { x: 451, y: 1434, w: 328, h: 68, padding: 8, target: 'Confirm status change' },
], { locale: 'nl-NL', role: 'Supervisor', asset: 'GUIDE-20261008-01', translationGap: 'protected release dialog retains untranslated English copy' });
await crop('saved', sourceFile('AST-SAVED-NL-MOBILE-20261008'), [25, 2920, 725, 146], 'Verify Ready for Sale after reopening the same asset, not just a pending selection.', [
    { x: 42, y: 3007, w: 197, h: 47, padding: 8, target: 'saved Ready for Sale state' },
], { locale: 'nl-NL', role: 'Supervisor', asset: 'GUIDE-20261008-01', savedStatus: 'Ready for Sale' });
await crop('history-tab', sourceFile('AST-HISTORY-NL-MOBILE-20261008'), [25, 610, 775, 110], 'Locate the selected history tab among the asset tabs to read the saved status note.', [
    { x: 293, y: 631, w: 95, h: 81, padding: 5, shape: 'ellipse', target: 'history tab, arrow and clock' },
], { locale: 'nl-NL', role: 'Supervisor', asset: 'GUIDE-20261008-01', note: 'The actual saved status-history note was verified; mobile table overflows its viewport.' });

function text(d, x, y, value, options = {}) {
    d.text(x, y, value, { size: 2.85, lh: 4.1, ...options });
}
function header(d, code, version, purpose, pageNumber, pageCount, titleSize = 8.3) {
    const family = code.split('-')[0];
    const palette = GUIDE_FAMILIES[family];
    d.rect(12, 13, 2, 16, palette.color, 'none', 0, 1);
    d.text(18, 22, `${code} ${guideReference(code).title}`, { size: titleSize, weight: 900 });
    d.text(18, 29.5, purpose, { size: 3.05, fill: C.muted });
    d.rect(164, 10, 34, 13, palette.fill, palette.color, 0.45, 1.4);
    d.centeredText(181, 14.6, `Concept v${version}`, { size: 2.5, weight: 800, fill: palette.color });
    d.centeredText(181, 19.5, `08-10-2026 | ${pageNumber}/${pageCount}`, { size: 2.15, fill: palette.color });
    d.text(12, 291, `Inbit Snipe-IT | ${code} v${version} | 08-10-2026`, { size: 1.9, fill: C.muted });
    d.text(198, 291, `${pageNumber}/${pageCount} | https://snipe.inbit/`, { size: 1.9, fill: C.muted, anchor: 'end' });
}
function step(d, code, number, x, y, w, h, title, titleSize = 3.8) {
    const p = GUIDE_FAMILIES[code.split('-')[0]];
    d.rect(x, y, w, h, '#FFFFFF', C.line, 0.5, 2.2, 1, { component: 'task-step', number });
    d.stepBadge(x + 10, y + 10, number, p.color);
    d.text(x + 21, y + 11.4, title, { size: titleSize, weight: 900, fill: p.color });
}
function figure(d, key, x, y, w, label, color, caption = [], options = {}) {
    const image = images[key];
    const h = w * image.height / image.width;
    const frame = { x, y, w, h };
    const placement = d.image(key, frame, null, { fit: 'contain' });
    const source = sources.find(s => s.key === key);
    const marks = source.marks.map(m => d.focusMark(placement, m));
    if (label) d.imageBadge(x + w + (options.badgeOffsetX ?? -1.4), options.badgeCorner === 'top-right' ? y + 1.4 : y + h + (options.badgeOffsetY ?? -1.4), label, color);
    if (caption.length) text(d, x, y + h + 4.2, caption, { size: 2.3, lh: 3.3, fill: C.muted, ...options });
    figures.push({ page: d.idPrefix, key, label, frameMm: frame, pixelsPerMm: image.width / w, focusMm: marks });
    return frame;
}
function completion(d, value, y = 253) {
    d.rect(12, y, 186, 12, C.greenSoft, '#86EFAC', 0.45, 1.8, 1, { component: 'completion-row' });
    d.centeredText(17, y + 6, 'Klaar als', { size: 2.8, weight: 900, fill: C.green, anchor: 'start' });
    d.centeredText(40, y + 6, value, { size: 2.6, fill: C.green, anchor: 'start' });
}
function related(d, codes, options = {}) {
    const placements = layoutRelatedGuides(codes.map(([code, width, row]) => guideReference(code, { width, row })), { rightEdge: 198, ...options });
    d.centeredText(12, (options.firstRowY ?? 268) + 3.5, 'Relevante gidsen', { size: 2.1, weight: 800, fill: C.muted, anchor: 'start' });
    placements.forEach(p => d.guideChip(p.x, p.y, p.width, p));
    return placements;
}
function condition(d, x, y, w, lines, options = {}) {
    const isStop = options.stop === true;
    d.rect(x, y, w, options.height ?? 18, isStop ? C.helpSoft : C.orangeSoft, isStop ? C.help : '#F6C36A', 0.4, 1.2, 1, { component: 'conditional-help' });
    text(d, x + 3, y + 4.5, lines, { size: options.size ?? 2.65, lh: options.lh ?? 3.7, fill: isStop ? '#A21C2B' : '#784200' });
}

const ac = new SvgGuideDocument(images, 'AC-01-v11-p1');
header(ac, 'AC-01', 11, 'Open Snipe-IT, log in en herken het dashboard.', 1, 1);
drawContextStrip(ac, [{ label: 'Rol', value: 'Iedere collega' }, { label: 'Nodig', value: 'Inbit-telefoon + account' }, { label: 'Vooraf', value: 'Account ontvangen' }]);
step(ac, 'AC-01', 1, 12, 65, 58, 125, 'Open Snipe-IT', 3.6);
text(ac, 16, 92, ['Tik op Inbit Snipe-IT op', 'het startscherm van de', 'Inbit-telefoon (1A).']);
figure(ac, 'phone', 20, 112, 42, '1A', C.ac, ['Startscherm: omcirkeld is', 'de Snipe-IT-snelkoppeling.'], { badgeCorner: 'top-right' });
step(ac, 'AC-01', 2, 76, 65, 58, 125, 'Log in');
text(ac, 80, 92, ['Vul je gebruikersnaam en', 'wachtwoord in.', 'Tik op Inloggen (2A).']);
figure(ac, 'login', 80, 112, 50, '2A', C.ac, ['Inlogscherm: de knop staat', 'onder de accountvelden.'], { badgeCorner: 'top-right' });
step(ac, 'AC-01', 3, 140, 65, 58, 125, 'Controleer het', 3.5);
text(ac, 161, 84, 'dashboard', { size: 3.5, weight: 900, fill: C.ac });
text(ac, 144, 96, ['Zie je Dashboard met', 'Apparaten en Scan QR?', 'Dan ben je ingelogd (3A).']);
figure(ac, 'dashboard', 144, 116, 50, '3A', C.ac, ['Dashboard: deze kop', 'bevestigt dat je binnen bent.'], { badgeCorner: 'top-right' });
text(ac, 144, 164, ['Ga verder met de gids', 'voor je volgende taak.']);
text(ac, 12, 201, 'Hulp bij inloggen', { size: 3.5, weight: 900, fill: C.muted });
const acHelp = [
    ['Geen account?', ['Vraag je supervisor of', 'beheerder om een account.']],
    ['Wachtwoord kwijt?', ['Vraag je supervisor om', 'je wachtwoord te resetten.']],
    ['Geen Inbit-telefoon?', ['Open een browser op', 'https://snipe.inbit/']],
    ['Sessie verlopen?', ['Log opnieuw in met', 'je eigen account.']],
];
acHelp.forEach(([title, lines], i) => {
    const x = 12 + i * 47.5;
    ac.rect(x, 207, 43.5, 33, C.orangeSoft, '#F6C36A', 0.4, 1.8);
    text(ac, x + 3, 214, title, { size: 2.9, weight: 900, fill: '#784200' });
    text(ac, x + 3, 222, lines, { size: 2.65, lh: 4.2, fill: '#784200' });
});
completion(ac, 'Je bent ingelogd en het dashboard is zichtbaar.');
related(ac, [['SC-01', 73], ['HELP-01', 64]]);

const sc = new SvgGuideDocument(images, 'SC-01-v14-p1');
header(sc, 'SC-01', 14, 'Vind het apparaat en controleer of het record erbij hoort.', 1, 1, 7.1);
drawContextStrip(sc, [{ label: 'Rol', value: 'Refurbisher' }, { label: 'Nodig', value: 'Telefoon + apparaat' }, { label: 'Vooraf', guideCode: 'AC-01', value: 'AC-01 Login' }]);
step(sc, 'SC-01', 1, 12, 64, 186, 39, 'Open de scanner');
text(sc, 16, 88, ['Tik op Scan QR op het Dashboard (1A)', 'of op het camera-icoon bovenin (1B).'], { size: 2.75, lh: 4.1 });
figure(sc, 'scanner-entry', 105, 69, 55, '1A', C.sc, ['Dashboard: Scan QR.'], { size: 2.2, badgeCorner: 'top-right' });
figure(sc, 'camera-entry', 166, 75, 26, '1B', C.sc, ['Bovenbalk.'], { size: 2.2 });
step(sc, 'SC-01', 2, 12, 109, 186, 78, 'Kies scannen OF handmatig zoeken');
sc.line(105, 130, 105, 182, C.line, 0.5);
text(sc, 17, 132, 'Scannen', { size: 3.25, weight: 900, fill: C.sc });
figure(sc, 'scan-photo', 18, 139, 34, '2A', C.sc);
text(sc, 58, 142, ['Geef de camera', 'toegang. Richt op', 'het QR-label en', 'houd de telefoon', 'stil. De pagina opent.'], { size: 2.65, lh: 3.8 });
text(sc, 58, 165, ['Werkt scannen niet?', 'Gebruik de route', 'hiernaast.'], { size: 2.5, lh: 3.6, fill: C.muted });
text(sc, 58, 179.7, ['2A: voorbeeld van', 'het camerabeeld.'], { size: 2.2, lh: 3.3, fill: C.muted });
text(sc, 111, 132, 'Handmatig zoeken', { size: 3.25, weight: 900, fill: C.sc });
text(sc, 111, 141, ['Lees de assettag op het label. Vul die', 'bij Handmatige invoer in en tik op', 'Zoeken (2B).'], { size: 2.75, lh: 3.9 });
figure(sc, 'manual', 112, 154, 79, '2B', C.sc, [], { badgeOffsetX: 2.3, badgeOffsetY: 2.3 });
text(sc, 111, 174, ['Meerdere resultaten? Vergelijk serienummer', 'en model; open de passende apparaatrij.', 'Geen resultaat? Check de tag; vraag hulp bij twijfel.'], { size: 2.5, lh: 3.8, fill: C.muted });
step(sc, 'SC-01', 3, 12, 193, 186, 68, 'Controleer hetzelfde');
text(sc, 33, 210, 'apparaat', { size: 3.8, weight: 900, fill: C.sc });
text(sc, 16, 218, ['Na beide routes: vergelijk model/type', 'in de titel (3A), Assettag en Serienummer', 'op Info (3B) met het fysieke apparaat.'], { size: 2.7, lh: 4 });
condition(sc, 16, 230, 89, ['Wijkt iets af of kun je het niet vaststellen?', 'STOP: wijzig niets. Vraag je supervisor', 'het record te controleren; ga verder als het klopt.'], { height: 15, size: 2.4, lh: 3.7, stop: true });
figure(sc, 'sc-title', 119, 196, 73, '3A', C.sc);
figure(sc, 'sc-identity', 119, 229, 73, '3B', C.sc);
text(sc, 16, 253, '3A/3B: dezelfde apparaatpagina, tab Info.', { size: 2.3, fill: C.muted });
completion(sc, 'De geopende assettag, het serienummer en model kloppen.', 265);
related(sc, [['WF-01', 73], ['HELP-01', 64]], { firstRowY: 280 });

const ast1 = new SvgGuideDocument(images, 'AST-05-v8-p1');
header(ast1, 'AST-05', 8, 'Beoordeel apparaat en bewijs; leg daarna de beslissing vast.', 1, 2, 6.3);
drawContextStrip(ast1, [{ label: 'Rol', value: 'Supervisor met reviewrechten' }, { label: 'Nodig', value: 'Apparaat + overdracht' }]);
ast1.familyBadge(14.5, 63, 'AST', { radius: 2.2, fontSize: 1.2 });
text(ast1, 19, 64, 'Vooraf: AST-04 Werk afronden en overdragen; apparaat in QA Hold.', { size: 2.75, weight: 800, fill: C.ast });
step(ast1, 'AST-05', 1, 12, 72, 186, 75, 'Controleer apparaat en reviewstatus');
text(ast1, 16, 94, ['Vergelijk Assettag, Serienummer en', 'model/type met het apparaat (1A/1B).', 'QA Hold = wacht op beoordeling.'], { size: 2.85, lh: 4.1 });
text(ast1, 16, 109, ['Identiteit wijkt af? STOP: wijzig niets.', 'Zoek met SC-01 Asset vinden en openen.', 'Ga verder als de gegevens kloppen.'], { size: 2.6, lh: 3.8, fill: '#A21C2B' });
figure(ast1, 'ast-title', 17, 119, 81, '1A', C.ast, ['Apparaattitel: model/type van dit record.'], { size: 2.15 });
figure(ast1, 'ast-identity', 111, 81, 81, '1B', C.ast, ['Info: identiteit en QA Hold.'], { size: 2.35 });
step(ast1, 'AST-05', 2, 12, 153, 186, 56, 'Lees het actuele werk en de resultaten');
text(ast1, 16, 176, ['Op Info moet elke vereiste workflow', 'actueel, volledig en geslaagd zijn (2A).', 'Open via Open Workflows de runs en', 'lees resultaten, notities en afwijkingen.', 'Een oude geslaagde run heft gewijzigd', 'of ontbrekend werk niet op.'], { size: 2.7, lh: 3.85 });
figure(ast1, 'required', 111, 171, 81, '2A', C.ast, ['Info: vereiste workflows; open de runs.'], { size: 2.25 });
step(ast1, 'AST-05', 3, 12, 216, 186, 51, 'Controleer het apparaat', 3.6);
figure(ast1, 'labels', 16, 234, 39, '3A', C.ast, [], { badgeCorner: 'top-right' });
text(ast1, 59, 238, ['Vergelijk staat,', 'labels en delen (3A).', 'Controleer de echt', 'geplaatste onderdelen.', 'Kwaliteit: wijzig alleen', 'met dat recht.'], { size: 2.7, lh: 3.65 });
figure(ast1, 'component-review', 114, 222, 78, '3B', C.ast, ['Componenttab (schijf): Assumed / Model baseline', 'is een verwachting. Controleer wat er echt zit.'], { size: 2.2, lh: 3.1 });
text(ast1, 16, 264.3, '3A: labelplekken op een voorbeeldapparaat.', { size: 2.25, fill: C.muted });
ast1.rect(12, 269, 186, 10, C.astSoft, C.ast, 0.45, 1.8, 1, { component: 'continuation-row' });
ast1.centeredText(17, 274, 'Ga verder op bladzijde 2: leg de beslissing vast met stap 4 t/m 6.', { size: 2.75, weight: 800, fill: C.ast, anchor: 'start' });
related(ast1, [['SC-01', 73], ['HELP-01', 64]], { firstRowY: 281 });

const ast2 = new SvgGuideDocument(images, 'AST-05-v8-p2');
header(ast2, 'AST-05', 8, 'Vervolg: beslissing, bevestiging en opgeslagen resultaat.', 2, 2, 6.3);
drawContextStrip(ast2, [{ label: 'Rol', value: 'Supervisor met reviewrechten' }, { label: 'Vooraf', value: 'Stap 1 t/m 3 afgerond' }]);
step(ast2, 'AST-05', 4, 12, 65, 186, 66, 'Leg de beslissing vast');
text(ast2, 16, 88, ['Schrijf bij Statusnotitie waarom je', 'vrijgeeft of wat hersteld moet worden.', 'Kies daarna bij Status (4A):'], { size: 2.75, lh: 4 });
text(ast2, 16, 103, 'Akkoord: Ready for Sale.', { size: 2.8, weight: 900, fill: C.ast });
text(ast2, 16, 108, 'Ga door met stap 5.', { size: 2.75 });
text(ast2, 16, 117, 'Herstel nodig: Being Processed.', { size: 2.8, weight: 900, fill: C.ast });
text(ast2, 16, 122, 'Geef het herstelwerk mee; daarna stap 6.', { size: 2.75 });
figure(ast2, 'status-note', 115, 77, 77, '4A', C.ast, ['Info: status en notitie horen bij elkaar.'], { size: 2.25 });
step(ast2, 'AST-05', 5, 12, 138, 186, 74, 'Lees en bevestig de statuswijziging');
text(ast2, 16, 161, ['Bij Ready for Sale verschijnt de', 'bevestiging (5A). Controleer oude en', 'nieuwe status en de controle-uitkomst.', 'Alles klopt? Tik op Confirm status change.', 'Bij terugsturen zonder dialoog wacht', 'je op de opgeslagen status.'], { size: 2.65, lh: 3.8 });
condition(ast2, 16, 186, 88, ['Ontbreekt werk of klopt iets niet?', 'Kies Annuleren; herstel en beoordeel opnieuw.', 'Override alleen met apart override-recht:', 'leg de reden vast en bevestig die uitzondering.'], { height: 23, size: 2.5, lh: 4 });
figure(ast2, 'confirm', 110, 150, 82, '5A', C.ast, ['Bevestiging: lees de wijziging voordat je bevestigt.'], { size: 2.15 });
step(ast2, 'AST-05', 6, 12, 219, 186, 44, 'Controleer het opgeslagen besluit');
text(ast2, 16, 241, ['Open hetzelfde apparaat opnieuw.', 'Controleer Status op Info (6A) en je', 'notitie in Geschiedenis (6B). Tabel te', 'breed? Gebruik een groter scherm.', 'Verkoopklaar naar verkoop; herstel terug', 'naar de uitvoerder met de herstelopdracht.'], { size: 2.6, lh: 3.65 });
figure(ast2, 'saved', 115, 230, 77, '6A', C.ast);
figure(ast2, 'history-tab', 127, 250, 65, '6B', C.ast);
completion(ast2, 'Je besluit is opgeslagen en het vervolg is doorgegeven.', 265);
related(ast2, [['AST-04', 78], ['WF-02', 79]], { firstRowY: 278 });

const guides = [
    { code: 'AC-01', version: 11, stem: 'AC-01-login-v11-draft', pages: [ac], baseline: 'resources/manuals/operator-guides/review-rounds/2026-09-08/AC-01-login-v9-draft.pdf', proposal: 'resources/manuals/operator-guides/review-rounds/2026-10-01-aligned-guides/AC-01-login-v10-draft.pdf', recipe: 'compact-horizontal-sequence', changes: ['Behoud van de compacte inlogtaak en drie kolommen.', 'Grotere herkenbare inlogvelden en dashboardtegel; geen recente-apparatenstap.', 'Nederlandse schermen en doelmarkeringen; conditionele hulp.'] },
    { code: 'SC-01', version: 14, stem: 'SC-01-asset-vinden-en-openen-v14-draft', pages: [sc], baseline: 'resources/manuals/operator-guides/review-rounds/2026-09-10/owner-corrections/SC-01-asset-vinden-en-openen-v12-draft.pdf', proposal: 'resources/manuals/operator-guides/review-rounds/2026-10-01-aligned-guides/SC-01-asset-vinden-en-openen-v13-draft.pdf', recipe: 'mixed-asymmetric-flow with parallel-visual-choice', changes: ['Scannen OF handmatig zoeken binnen dezelfde stap.', 'Beide routes komen bij een gedeelde identiteitscontrole.', 'Een bladzijde, doelmarkeringen en herstel bij de betreffende conditie.'] },
    { code: 'AST-05', version: 8, stem: 'AST-05-review-release-v8-draft', pages: [ast1, ast2], baseline: 'resources/manuals/operator-guides/review-rounds/2026-09-08/AST-05-review-release-v6-draft.pdf', proposal: 'resources/manuals/operator-guides/review-rounds/2026-10-01-aligned-guides/AST-05-review-release-v7-draft.pdf', recipe: 'stacked-step-flow with inline-route-alternative and two-sided-continuation', changes: ['Beoordeling, keuze, bevestiging en opgeslagen besluit in een doorlopende taak.', 'Schoon vrijgavevoorbeeld voor dezelfde demonstratie-asset; terugsturen als alternatief.', 'Klaar als op de laatste zijde; resterende Engelse appteksten zijn zichtbaar gebleven.'] },
];

const htmlPage = svgs => `<!doctype html><html lang="nl"><head><meta charset="utf-8"><title>Inbit operator guide draft</title><style>@page{size:A4 portrait;margin:0}*{box-sizing:border-box}body{margin:0;background:#dce3eb}.page{width:210mm;height:297mm;background:white;margin:0 auto;break-after:page;overflow:hidden}.page:last-child{break-after:auto}.page svg{display:block;width:210mm;height:297mm}@media screen{.page{margin:12px auto;box-shadow:0 2px 8px #4566}}</style></head><body>${svgs.map(svg => `<section class="page">${svg.replace(/<\?xml[^>]*\?>\s*/, '')}</section>`).join('')}</body></html>`;

const browser = await chromium.launch({ executablePath: resolveChromeExecutable(), headless: true });
const qa = [];
try {
    for (const g of guides) {
        const svgs = g.pages.map(d => d.render());
        svgs.forEach((svg, i) => fs.writeFileSync(path.join(round, `${g.stem}-page-${i + 1}.svg`), svg));
        const html = path.join(round, `${g.stem}.html`);
        fs.writeFileSync(html, htmlPage(svgs));
        const page = await browser.newPage();
        await page.goto(pathToFileURL(html).href);
        await page.evaluate(() => document.fonts.ready);
        const components = await inspectRenderedGuideComponents(page);
        const textBoxes = await page.evaluate(() => [...document.querySelectorAll('.page')].flatMap((p, i) => [...p.querySelectorAll('text')].map(n => {
            const b = n.getBBox(); return { page: i + 1, text: n.textContent, x: b.x, y: b.y, w: b.width, h: b.height, size: n.getAttribute('font-size') };
        })));
        const outside = textBoxes.filter(b => b.x < 0 || b.y < 0 || b.x + b.w > 210 || b.y + b.h > 297);
        assert.deepEqual(outside, [], `${g.code}: text outside A4`);
        qa.push({ code: g.code, components, textBoxes });
        await page.close();
        const pdf = path.join(round, `${g.stem}.pdf`);
        const rendering = spawnSync(process.execPath, [path.join(repoRoot, 'scripts/manuals/render-guide-html.mjs'), '--html', html, '--pdf', pdf, '--pages', String(g.pages.length), '--render-dir', path.join(proof, g.code), '--replace-output'], { encoding: 'utf8', windowsHide: true, env: process.env });
        assert.equal(rendering.status, 0, rendering.stderr || rendering.stdout);
        fs.copyFileSync(pdf, path.join(repoPdfOutputRoot, path.basename(pdf)));
        console.log(rendering.stdout.trim());
        g.pdf = rel(pdf); g.html = rel(html); g.sha256 = hash(pdf);
        g.baselineSha256 = hash(path.join(repoRoot, g.baseline));
        g.proposalSha256 = hash(path.join(repoRoot, g.proposal));
    }
} finally { await browser.close(); }

fs.writeFileSync(path.join(proof, 'geometry-review.json'), JSON.stringify(qa, null, 2));
fs.writeFileSync(path.join(round, 'authoring-manifest.json'), JSON.stringify({ round: '2026-10-08-test-guides', status: 'Working draft; not accepted', generatedAt: new Date().toISOString(), authoringBrief: 'docs/manuals/operator-guides/reviews/reauthoring-test-2026-10-08.md', guides: guides.map(({ pages, ...g }) => ({ ...g, pageCount: pages.length })), sources, figures, limitations: ['Synthetic release fixture; no physical work or novice-reader trial.', 'Dutch account/global locale verified; new workflow and protected-release app copy remains partially untranslated.', 'Return and override branches documented from current application, not exercised as a physical operational trial.'] }, null, 2));
for (const file of ['generate-reauthoring-test-2026-10-08.mjs', 'capture-reauthoring-test-2026-10-08.mjs', 'prepare-reauthoring-test-evidence-2026-10-08.php']) fs.copyFileSync(path.join(repoRoot, 'scripts/manuals', file), path.join(round, 'inputs', file));
console.log(JSON.stringify({ round: rel(round), drafts: guides.map(g => ({ code: g.code, version: g.version, pdf: g.pdf, sha256: g.sha256 })) }));
