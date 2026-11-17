import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import crypto from 'node:crypto';
import {spawnSync} from 'node:child_process';
import {pathToFileURL} from 'node:url';
import {SvgGuideDocument, GUIDE_TOKENS, GUIDE_FAMILIES, guideReference,
    drawContextStrip, drawCompletionRow, inspectRenderedGuideComponents} from './lib/guide-system.mjs';
import {repoRoot, repoPdfOutputRoot, loadGuideDependency, browserLaunchOptions} from './lib/guide-paths.mjs';

assert.equal(process.argv.length, 2, 'This dated generator accepts no arguments.');
const round = path.join(repoRoot, 'resources/manuals/operator-guides/review-rounds/2026-11-04-ast03-pilot');
assert.ok(!fs.existsSync(path.join(round, 'manifest.sha256')), 'Sealed proposal: create a new version.');
const prior = path.join(repoRoot, 'resources/manuals/operator-guides/review-rounds/2026-10-27-navigation-and-space');
for (const dir of ['inputs', 'evidence', 'crops', 'renders', 'baselines', 'qa', 'editable']) {
    fs.mkdirSync(path.join(round, dir), {recursive: true});
}
const stem = 'AST-03-asset-registreren-en-labelen-v19-draft';
const hash = p => crypto.createHash('sha256').update(fs.readFileSync(p)).digest('hex');
const rel = p => path.relative(repoRoot, p).replaceAll('\\', '/');
const read = p => JSON.parse(fs.readFileSync(p, 'utf8').replace(/^\uFEFF/, ''));
const writeJson = (p, v) => fs.writeFileSync(p, JSON.stringify(v, null, 2) + '\n');
const sharp = loadGuideDependency('sharp');
const {chromium} = loadGuideDependency('playwright');
const C = GUIDE_TOKENS.colors, palette = GUIDE_FAMILIES.AST;
const images = {}, sources = [], figures = [];

// Complete genuine captures stay separate from crops and focus annotations.
for (const name of ['AST-DASH', 'AST-NAV', 'AST-SEARCH-EMPTY', 'AST-CREATE-MOBILE',
    'AST-CREATE-DESKTOP', 'AST-SAVED-DESKTOP', 'AST-ASSET']) {
    for (const ext of ['png', 'json']) fs.copyFileSync(path.join(prior, 'evidence', `${name}.${ext}`), path.join(round, 'evidence', `${name}.${ext}`));
}
const oldManifest = read(path.join(prior, 'authoring-manifest.json'));
const state = n => read(path.join(round, 'evidence', `${n}.json`));
const el = (n, test) => {const e = state(n).elements.find(test); assert.ok(e, n + ': missing element'); return e;};
const union = boxes => {
    const x = Math.min(...boxes.map(b => b.x)) - 5, y = Math.min(...boxes.map(b => b.y)) - 5;
    return {x, y, w: Math.max(...boxes.map(b => b.x + b.w)) + 5 - x,
        h: Math.max(...boxes.map(b => b.y + b.h)) + 5 - y};
};
async function crop(key, name, pixels, job, marks = []) {
    const source = path.join(round, 'evidence', name + '.png'), s = state(name);
    assert.equal(s.locale, 'nl-NL');
    const meta = await sharp(source).metadata();
    const p = {x: Math.floor(pixels.x), y: Math.floor(pixels.y), w: Math.ceil(pixels.w), h: Math.ceil(pixels.h)};
    assert.ok(p.x >= 0 && p.y >= 0 && p.x + p.w <= meta.width && p.y + p.h <= meta.height, key);
    const bytes = await sharp(source).extract({left: p.x, top: p.y, width: p.w, height: p.h}).png().toBuffer();
    const file = path.join(round, 'crops', key + '.png'); fs.writeFileSync(file, bytes);
    images[key] = {href: 'data:image/png;base64,' + bytes.toString('base64'), width: p.w, height: p.h};
    sources.push({key, source: rel(source), sourceSha256: hash(source), crop: rel(file), cropSha256: hash(file),
        cropPixels: p, sourcePixels: {width: meta.width, height: meta.height}, marks, job,
        locale: s.locale, role: s.notes.actor, evidenceDate: '2026-10-27', freshCapture: false});
}
const dash = union([
    el('AST-DASH', e => e.tag === 'H1').bounds,
    el('AST-DASH', e => e.tag === 'A' && e.href === 'https://dev.inbit/hardware' && e.text.includes('alles weergeven')).bounds,
    el('AST-DASH', e => e.tag === 'A' && e.href === 'https://dev.inbit/hardware/create').bounds,
]);
await crop('ast-dashboard', 'AST-DASH', Object.fromEntries(Object.entries(dash).map(([k, v]) => [k, v * 1.5])),
    'Recognize Dashboard with both Apparaten and Nieuwe Asset / Nieuwe aanmaken. Search and creation are complementary.');
for (const key of ['ast-search', 'ast-identity', 'ast-model', 'ast-status', 'ast-save', 'ast-saved', 'ast-print', 'ast-preview']) {
    const old = oldManifest.sources.find(s => s.key === key);
    const marks = key === 'ast-search' ? old.marks.filter(m => m.target === 'Nieuwe asset via plus') : old.marks;
    await crop(key, path.basename(old.source, '.png'), old.cropPixels, old.job, marks);
}
await crop('ast-open-identity', 'AST-ASSET', {x: 20, y: 2746, w: 732, h: 306},
    'Read native Assettag and Serienummer on the opened record after scanning. Model is in the asset title above.');
const photo = oldManifest.sources.find(s => s.key === 'placement');
const photoFile = path.join(round, 'evidence', 'AST-LABEL-PLACEMENT-PHOTO-01.jpg');
fs.copyFileSync(path.join(repoRoot, photo.source), photoFile);
const photoBytes = fs.readFileSync(photoFile), pm = await sharp(photoBytes).metadata();
images.placement = {href: 'data:image/jpeg;base64,' + photoBytes.toString('base64'), width: pm.width, height: pm.height};
sources.push({...photo, source: rel(photoFile), sourceSha256: hash(photoFile), freshCapture: false,
    job: 'Recognize the full underside, front edge and physical QR in the separate owner-supplied placement example.'});

const pinned = [];
for (const file of ['authoring-method.md', 'system.md', 'components.md', 'layouts.md', 'screenshots.md',
    'decisions.md', 'guides/AST-03.md', 'reviews/AST-03-v15.md', 'reviews/AST-03-v19.md',
    'reviews/navigation-and-space-feedback-2026-10-27.md']) {
    const source = path.join(repoRoot, 'docs/manuals/operator-guides', file);
    const out = path.join(round, 'inputs', 'authoring-context', file);
    fs.mkdirSync(path.dirname(out), {recursive: true}); fs.copyFileSync(source, out);
    pinned.push({source: rel(source), retained: rel(out), sha256: hash(out)});
}
const application = [];
const mounted = 'C:/Users/Gebruiker/.codex/worktrees/refurbisher-followups/snipe-it-fork';
for (const [tree, root] of [['root', repoRoot], ['captured-checkout', mounted]]) {
    for (const file of ['app/Http/Requests/StoreAssetRequest.php', 'app/Presenters/AssetPresenter.php', 'resources/views/dashboard.blade.php',
        'resources/views/hardware/edit.blade.php', 'resources/views/hardware/partials/qr-label-widget.blade.php']) {
        const src = path.join(root, file), out = path.join(round, 'inputs/application-sources', tree, file);
        fs.mkdirSync(path.dirname(out), {recursive: true}); fs.copyFileSync(src, out);
        application.push({tree, source: file, retained: rel(out), sha256: hash(out)});
    }
}
writeJson(path.join(round, 'evidence-provenance.json'), {date: '2026-11-04', mode: 'Reused genuine evidence; editorial pilot',
    captureDate: '2026-10-27', localDockerAvailable: false, freshProductionUiCheck: false,
    capturedCheckout: mounted, rootHasSameDashboardTile: false, rootRegistrationNotesOptional: true,
    writesThisSession: {application: false, database: false, fixtures: false, printer: false}, application, pinned});

function text(d, x, y, value, options = {}) {
    d.text(x, y, value, {size: 2.65, lh: 3.7, data: {copy: options.caption ? 'caption' : 'instruction'}, ...options});
}
function caption(d, x, y, value) {text(d, x, y, value, {size: 2.05, fill: C.muted, caption: true});}
function page(n, subtitle, need) {
    const d = new SvgGuideDocument(images, `AST-03-v19-p${n}`);
    d.rect(12, 13, 2, 16, palette.color);
    text(d, 18, 22, 'AST-03 Asset registreren en labelen', {size: 5.5, weight: 900});
    text(d, 18, 29.5, subtitle, {size: 2.8, fill: C.muted});
    d.rect(164, 10, 34, 13, palette.fill, palette.color, .45, 1.4);
    d.centeredText(181, 14.6, 'Concept v19', {size: 2.5, weight: 800, fill: palette.color});
    d.centeredText(181, 19.5, `04-11-2026 | ${n}/2`, {size: 2.15, fill: palette.color});
    drawContextStrip(d, [{label: 'Rol', value: 'Supervisor'}, {label: 'Nodig', value: need},
        n === 1 ? {label: 'Vooraf', guideCode: 'AC-01'} : {label: 'Vervolg', value: 'Dezelfde taak; vorige zijde'}], {y: 34, height: 17});
    text(d, 12, 296, 'Inbit Snipe-IT | AST-03 v19 | 04-11-2026', {size: 1.65, fill: C.muted});
    text(d, 198, 296, `${n}/2 | https://snipe.inbit/`, {size: 1.65, anchor: 'end', fill: C.muted});
    return d;
}
function step(d, n, y, h, title) {
    d.raw(`<g id="step-${n}" data-component="task-step" data-number="${n}">`);
    d.rect(12, y, 186, h, 'white', C.line, .45, 2, 1, {component: 'step-frame'});
    d.stepBadge(21, y + 9, n, palette.color);
    text(d, 32, y + 10.5, title, {size: 3.5, weight: 900, fill: palette.color});
}
const end = d => d.raw('</g>');
function fig(d, key, x, y, w, label, options = {}) {
    const im = images[key], h = w * im.height / im.width;
    d.raw(`<g id="figure-${label}" data-component="figure" data-source-key="${key}">`);
    const placement = d.image(key, {x, y, w, h}, null, {fit: 'contain'});
    d.raw('</g><g data-component="annotations">');
    for (const mark of sources.find(s => s.key === key).marks ?? []) d.focusMark(placement, mark);
    d.raw('</g>');
    d.imageBadge(options.badgeX ?? x + 2, options.badgeY ?? y + 1, label, palette.color);
    figures.push({page: d.idPrefix, key, label, frameMm: {x, y, w, h}, sourcePixelsPerMm: im.width / w});
}

const a = page(1, 'Zoek het apparaat eerst; registreer alleen wanneer het nog ontbreekt.', 'Apparaat + fabrikantlabel');
step(a, 1, 56, 86, 'Zoek eerst; open daarna het formulier');
text(a, 16, 79, ['Open op Dashboard Apparaten (1A).', 'Zoek op de fysieke S/N of een bestaande tag.',
    'Gebruik Zoeken boven de apparatenlijst (1B).', 'Typ het nummer en druk op Enter.', 'Wacht tot de resultaten geladen zijn.']);
caption(a, 113, 67, '1A Eerst Apparaten; Nieuwe Asset pas na het zoeken.');
fig(a, 'ast-dashboard', 112, 70, 81, '1A', {badgeX: 192, badgeY: 70});
fig(a, 'ast-search', 16, 107, 130, '1B', {badgeX: 16, badgeY: 107});
caption(a, 16, 126.5, '1B Zoekveld boven de lijst; de + opent Nieuwe aanmaken.');
text(a, 16, 131, 'Gevonden? Klik op de assettag van het passende apparaat; controleer de identiteit bij stap 5.', {size: 2.6});
text(a, 16, 135.2, ['Geen passend record? Klik op + (1B). Of ga met Terug naar Dashboard en kies',
    'Nieuwe Asset > Nieuwe aanmaken (1A).'], {size: 2.6, lh: 3.7});
end(a);

step(a, 2, 146, 44, 'Vul de assettag en fysieke S/N in');
text(a, 16, 166, ['Assettag is onze interne apparaatcode.', 'Behoud de voorgestelde code.',
    'Serienummer (S/N) staat op het fabrikantlabel.', 'Vul hier geen Product ID of P/N in.'], {size: 2.6, lh: 3.5});
text(a, 16, 181.8, ['Unlock wijzigt de tag; normaal laat je die staan.',
    'Tag/S/N worden hoofdletters; Aa bewaart afwijkende letters.'], {size: 2.5, lh: 3.3});
fig(a, 'ast-identity', 109, 158, 84, '2A', {badgeX: 192, badgeY: 158});
caption(a, 109, 189, '2A Tag en serienummer in het formulier.');
end(a);

step(a, 3, 194, 44, 'Kies categorie en de exacte modelcode');
text(a, 16, 214, ['Kies Categorie, bijvoorbeeld Laptops.', 'Kies bij Model de exacte fabrikantcode.',
    'Bij HP: Product ID/P/N, ook het deel na #.', 'Dat is de modelcode, niet de unieke S/N.'], {size: 2.6, lh: 3.5});
text(a, 16, 228.8, 'Ontbreekt die? Voeg toe; keer terug naar stap 3:', {size: 2.35, fill: C.orange});
a.guideChip(16, 230, 90, guideReference('CAT-01'));
fig(a, 'ast-model', 109, 206, 84, '3A', {badgeX: 192, badgeY: 206});
caption(a, 109, 237, '3A Categorie en de volledige modelcode.');
end(a);

step(a, 4, 242, 39, 'Kies status en sla eenmaal op');
text(a, 16, 262, ['Kies Status: hier Being Processed.', 'Statusnotitie en Notities zijn optioneel.'], {size: 2.6, lh: 3.7});
caption(a, 113, 250, '4A Status en de optionele statusnotitie.');
fig(a, 'ast-status', 117, 252, 76, '4A', {badgeX: 192, badgeY: 251.5});
fig(a, 'ast-save', 16, 267, 28, '4B', {badgeX: 13, badgeY: 275});
text(a, 49, 271, 'Controleer alles; klik bovenaan op Opslaan (4B).', {size: 2.4});
text(a, 49, 276, 'Duplicaatmelding? Eerst vergelijken bij stap 1.', {size: 2.4});
end(a);
text(a, 16, 288, 'Vervolg op bladzijde 2: controleer het record, print, plaats en scan het label.',
    {size: 2.75, weight: 800, fill: palette.color});

const b = page(2, 'Vervolg: controleer hetzelfde record en maak het fysieke label.', 'Asset + labelprinter');
step(b, 5, 56, 45, 'Open en controleer hetzelfde record');
text(b, 16, 76, ['Na Opslaan: vergelijk tag, S/N en model; kies Asset openen (5A).',
    'Controleer Status onder het QR-paneel. Via stap 1 al geopend? Controleer dat record.'], {size: 2.6, lh: 3.7});
fig(b, 'ast-saved', 16, 81, 177, '5A', {badgeX: 192, badgeY: 81});
caption(b, 16, 100, '5A Opgeslagen record. De snelle printknop ernaast gebruikt de standaardkeuzes.');
end(b);

step(b, 6, 105, 66, 'Controleer label, formaat en printer');
text(b, 16, 125, ['Scroll naar QR-paneel; vergelijk tag en S/N (6B).',
    'Kies QR Label Template; Afdrukformaat moet bij het papier passen.',
    'Kies Printerlocatie; klik eenmaal Print QR label (6A).'], {size: 2.6, lh: 3.6});
fig(b, 'ast-print', 113, 106, 80, '6A', {badgeX: 192, badgeY: 106});
caption(b, 113, 170, '6A Template, formaat, printer en afdrukken.');
fig(b, 'ast-preview', 16, 139, 27, '6B', {badgeX: 16, badgeY: 139});
caption(b, 16, 169.5, '6B Labelvoorbeeld.');
b.rect(49, 140, 61, 28, C.faint, C.line, .35, 1);
text(b, 52, 145, 'Geen label of uitkomst onduidelijk?', {size: 2.45, weight: 800});
text(b, 52, 150, ['Controleer printer, papier en opdracht.', 'Vraag lokale ondersteuning.',
    'Gebruik dezelfde asset;', 'maak geen nieuwe registratie.'], {size: 2.35, lh: 3.7});
end(b);

step(b, 7, 175, 57, 'Plaats het label rechtsonder');
text(b, 16, 196, ['Leg de onderzijde met de voorkant naar je toe.', 'Plaats het label rechtsonder op een vlak stuk.',
    'Bedek geen ventilatie, poort, schroef of', 'servicelabel. Druk het label goed vast.'], {size: 2.7, lh: 4});
fig(b, 'placement', 113, 176, 80, '7A', {badgeX: 192, badgeY: 176});
caption(b, 16, 225, '7A Hele onderzijde; de voorkant ligt naar je toe.');
caption(b, 16, 229, 'De foto is een afzonderlijk plaatsingsvoorbeeld.');
end(b);

step(b, 8, 236, 36, 'Scan en vergelijk');
text(b, 16, 256, ['Scan het label; vergelijk tag/S/N (8A) en model bovenaan.', 'Label kapot? Zoek handmatig op tag of S/N.',
    'Andere identiteit? Wijzig niets; vraag Supervisor.'], {size: 2.55, lh: 3.3});
b.guideChip(16, 264, 90, guideReference('SC-01'));
fig(b, 'ast-open-identity', 120, 237, 73, '8A', {badgeX: 192, badgeY: 237});
caption(b, 120, 270, '8A Identiteit in de geopende asset.');
end(b);
drawCompletionRow(b, 'Het apparaat bestaat eenmaal; zijn label opent hetzelfde record.', {x: 12, y: 274, width: 186, height: 9});
b.guideChip(12, 286, 89, guideReference('AST-02'));
b.guideChip(104, 286, 94, guideReference('HELP-01'));

const svgs = [a, b].map(d => d.render());
svgs.forEach((s, i) => {fs.writeFileSync(path.join(round, stem + `-page-${i + 1}.svg`), s);
    fs.writeFileSync(path.join(round, 'editable', stem + `-page-${i + 1}.svg`), s);});
const html = path.join(round, stem + '.html'), pdf = path.join(round, stem + '.pdf');
fs.writeFileSync(html, `<!doctype html><html lang="nl"><meta charset="utf-8"><title>AST-03 v19</title><style>@page{size:A4 portrait;margin:0}*{box-sizing:border-box}body{margin:0;background:#dce3eb}.page{width:210mm;height:297mm;background:white;margin:0 auto;break-after:page;overflow:hidden}.page:last-child{break-after:auto}.page svg{display:block;width:210mm;height:297mm}@media screen{.page{margin:12px auto;box-shadow:0 2px 8px #4566}}</style><body>${svgs.map(s => `<section class="page">${s}</section>`).join('')}</body></html>`);
const browser = await chromium.launch(browserLaunchOptions({headless: true}));
try {
    const p = await browser.newPage(); await p.goto(pathToFileURL(html).href); await p.evaluate(() => document.fonts.ready);
    const components = await inspectRenderedGuideComponents(p);
    const geometry = await p.evaluate(() => [...document.querySelectorAll('.page>svg')].map((svg, index) => {
        const box = n => {const r = n.getBBox(); return {x: r.x, y: r.y, w: r.width, h: r.height};};
        const overlap = (a, b) => a.x < b.x + b.w - .12 && a.x + a.w > b.x + .12 && a.y < b.y + b.h - .12 && a.y + a.h > b.y + .12;
        const text = [...svg.querySelectorAll('text')].map(n => ({...box(n), value: n.textContent,
            copy: n.getAttribute('data-copy'), badge: !!n.closest('[data-component="step-badge"], [data-component="image-badge"]'),
            step: n.closest('[data-component="task-step"]')?.getAttribute('data-number')}));
        const images = [...svg.querySelectorAll('image')].map(n => ({x: +n.getAttribute('x'), y: +n.getAttribute('y'),
            w: +n.getAttribute('width'), h: +n.getAttribute('height'), key: n.closest('[data-component="figure"]')?.getAttribute('data-source-key')}));
        const badges = [...svg.querySelectorAll('[data-component="step-badge"], [data-component="image-badge"]')].map(n => {
            const c = n.querySelector('circle'), r = +c.getAttribute('r') + +c.getAttribute('stroke-width') / 2;
            return {x: +c.getAttribute('cx') - r, y: +c.getAttribute('cy') - r, w: r * 2, h: r * 2, label: n.textContent.trim()};
        });
        const frames = [...svg.querySelectorAll('[data-component="step-frame"]')].map(n => ({...box(n), number: n.parentElement.getAttribute('data-number')}));
        return {page: index + 1, text, images, outside: text.filter(t => t.x < 0 || t.y < 0 || t.x + t.w > 210 || t.y + t.h > 297),
            textImageOverlap: text.filter(t => t.copy).flatMap(t => images.filter(i => overlap(t, i)).map(i => ({text: t.value, key: i.key}))),
            textBadgeOverlap: text.filter(t => t.copy).flatMap(t => badges.filter(b => overlap(t, b)).map(b => ({text: t.value, badge: b.label}))),
            stepTextOutside: text.filter(t => t.step && !t.badge).filter(t => {const f = frames.find(f => f.number === t.step); return t.x < f.x || t.y < f.y || t.x + t.w > f.x + f.w || t.y + t.h > f.y + f.h;}),
            textOverlap: text.filter(t => t.copy).flatMap((t, i, all) => all.slice(i + 1).filter(o => overlap(t, o)).map(o => ({a: t.value, b: o.value})))};
    }));
    writeJson(path.join(round, 'qa/geometry-review.json'), {components, pages: geometry});
    assert.deepEqual(geometry.flatMap(g => g.outside), [], 'A4 overflow');
    assert.deepEqual(geometry.flatMap(g => g.textImageOverlap), [], 'Copy intersects screenshot');
    assert.deepEqual(geometry.flatMap(g => g.textBadgeOverlap), [], 'Copy intersects badge');
    assert.deepEqual(geometry.flatMap(g => g.stepTextOutside), [], 'Text outside its step');
    assert.deepEqual(geometry.flatMap(g => g.textOverlap), [], 'Copy intersects copy');
} finally {await browser.close();}
const render = spawnSync(process.execPath, [path.join(repoRoot, 'scripts/manuals/render-guide-html.mjs'),
    '--html', html, '--pdf', pdf, '--pages', '2', '--render-dir', path.join(round, 'renders'), '--replace-output'],
{encoding: 'utf8', windowsHide: true, env: process.env});
assert.equal(render.status, 0, render.stderr || render.stdout);
fs.mkdirSync(repoPdfOutputRoot, {recursive: true}); fs.copyFileSync(pdf, path.join(repoPdfOutputRoot, path.basename(pdf)));
for (let n = 1; n <= 2; n++) fs.copyFileSync(path.join(round, 'renders', `page-${n}.png`), path.join(round, 'renders', `AST-03-${n}.png`));
writeJson(path.join(round, 'authoring-manifest.json'), {date: '2026-11-04', status: 'Working draft; not accepted',
    guide: {code: 'AST-03', version: 19, stem, pageCount: 2, pdf: rel(pdf), html: rel(html), sha256: hash(pdf)}, sources, figures});
console.log(JSON.stringify({pdf: rel(pdf), pages: 2, sources: sources.length, sha256: hash(pdf)}));
