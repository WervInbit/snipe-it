import assert from 'node:assert/strict';
import crypto from 'node:crypto';
import fs from 'node:fs';
import path from 'node:path';
import { spawnSync } from 'node:child_process';
import { pathToFileURL } from 'node:url';
import { GUIDE_FAMILIES, guideReference } from './lib/guide-system.mjs';
import { loadGuideDependency, repoRoot, repoPdfOutputRoot, resolveChromeExecutable, resolveCommand } from './lib/guide-paths.mjs';

const round = path.join(repoRoot, 'resources/manuals/operator-guides/review-rounds/2026-10-01-workflows');
const outIndex = process.argv.indexOf('--out-dir');
const out = outIndex >= 0 ? path.resolve(process.argv[outIndex + 1]) : path.join(repoRoot, 'output/manuals/proofs/workflow-revisions-2026-10-01');
const retain = process.argv.includes('--retain');
const sha = file => crypto.createHash('sha256').update(fs.readFileSync(file)).digest('hex');
const sharp = loadGuideDependency('sharp');
const { chromium } = loadGuideDependency('playwright');
const sources = {};
for (const file of fs.readdirSync(path.join(round, 'evidence')).filter(file => file.endsWith('.jpg'))) {
    const source = path.join(round, 'evidence', file);
    const { width, height } = await sharp(source).metadata();
    sources[file.replace('.jpg', '')] = { width, height, uri: `data:image/jpeg;base64,${fs.readFileSync(source).toString('base64')}`, sha256: sha(source) };
}
const definitions = [
    { code: 'WF-01', version: 14, oldVersion: 13, pages: 1, input: 'WF-01-workflow-starten-v13-draft.html', file: 'WF-01-workflow-starten-v14-draft' },
    { code: 'WF-02', version: 13, oldVersion: 12, pages: 2, input: 'WF-02-complete-workflow-v12-draft.html', file: 'WF-02-complete-workflow-v13-draft' },
];
if (retain) {
    for (const guide of definitions) {
        for (const ext of ['pdf', 'html']) assert.ok(!fs.existsSync(path.join(round, `${guide.file}.${ext}`)), 'Retained candidates are immutable; create a later integer version.');
        assert.ok(!fs.existsSync(path.join(repoPdfOutputRoot, `${guide.file}.pdf`)), 'Delivery already exists; reproduce into a separate proof directory.');
    }
    assert.ok(!fs.existsSync(path.join(round, 'manifest.json')), 'This review round is already frozen.');
}
fs.mkdirSync(out, { recursive: true });
const references = Object.fromEntries(['SC-01', 'WF-01', 'WF-02', 'AST-04', 'HELP-01'].map(code => {
    const reference = guideReference(code);
    return [code, { ...reference, ...GUIDE_FAMILIES[reference.family] }];
}));
const browser = await chromium.launch({ executablePath: resolveChromeExecutable(), headless: true });
const report = [];
try {
    for (const guide of definitions) {
        const page = await browser.newPage();
        await page.goto(pathToFileURL(path.join(round, 'inputs', guide.input)).href);
        await page.emulateMedia({ media: 'print' });
        await page.evaluate(({ guide, sources, palette, references }) => {
            const chip = code => {
                const r = references[code];
                return `<span class="guide-chip" data-guide-code="${code}" style="--chip:${r.color};--chip-fill:${r.fill}"><b>${r.family}</b><span>${r.code} ${r.title}</span></span>`;
            };
            const shot = (id, crop, mark = null) => {
                const s = sources[id];
                const target = mark ? `<rect class="target-mark" x="${mark[0]}" y="${mark[1]}" width="${mark[2]}" height="${mark[3]}" rx="5"/>` : '';
                return `<div class="shot"><svg viewBox="${crop.join(' ')}" preserveAspectRatio="xMidYMid meet" aria-hidden="true"><image href="${s.uri}" x="0" y="0" width="${s.width}" height="${s.height}"/>${target}</svg></div>`;
            };
            const figure = (node, label, id, crop, caption, mark = null) => {
                node.innerHTML = `<span class="image-badge">${label}</span>${shot(id, crop, mark)}<figcaption>${caption}</figcaption>`;
                node.setAttribute('data-source-id', id);
            };
            const copy = (node, title, paragraphs) => {
                node.innerHTML = `<h3>${title}</h3>${paragraphs.map(p => `<p>${p}</p>`).join('')}`;
            };
            const help = (container, title, items) => {
                container.querySelector('h2').textContent = title;
                container.querySelector('.help-grid').innerHTML = items.map(([heading, text]) => `<div class="help-item"><span class="help-symbol">!</span><div><strong>${heading}</strong><p>${text}</p></div></div>`).join('');
                container.querySelector('.help-grid').style.setProperty('--help-count', items.length);
            };
            const style = document.createElement('style');
            style.textContent = `
                .guide-page{--family:${palette.color}}
                .version{background:${palette.fill};border-color:${palette.color};color:${palette.color}}
                .version small{font-size:2.05mm}
                .step-number,.image-badge{border-color:${palette.color};color:${palette.color}}
                .target-mark{stroke:#E83448}
                .guide-page.wf01{grid-template-rows:22mm 15mm 167.7mm 22.3mm 10mm 25mm;gap:2.2mm}
                .wf01-main{height:167.7mm;grid-template-rows:60mm 104mm}
                .wf01-main .step-one,.wf01-main .step-two{height:60mm}
                .wf01-main .step-three,.wf01-main .step-four{height:104mm}
                .wf01-main .step{min-height:0}
                .wf01-main .shot,.wf01-main .visual,.wf01-main .step-copy,.wf01-main .step-alternative,.wf01-main .choice-divider{flex-shrink:0}
                .wf01-main .step{justify-content:flex-start;padding-top:3.6mm;padding-bottom:2mm;gap:1.2mm}
                .wf01-main .step-three{background:${palette.fill};border-color:${palette.color}}
                .wf01-main .step h3{font-size:3.6mm}
                .wf01-main .step p{font-size:2.35mm;line-height:1.23}
                .wf01-main .step-two figure{margin-top:1mm}
                .wf01-main .step-one{justify-content:center}
                .wf01-main .step-three .choice-intro p{margin:.8mm 0}
                .wf01-main .step-three .step-alternative{margin:0;padding-top:0}
                .wf01-main .step-three figure{margin:.7mm 0}
                .wf01-main .step-three .choice-divider{margin:.5mm 0 1mm}
                .wf01-main .step-four .guide-chip{font-size:2mm;min-height:5mm;padding:.5mm .7mm}
                .wf01-main .step-four .guide-chip b{width:3.5mm;height:3.5mm;font-size:1.2mm}
                .wf02-front .wf02-main{grid-template-rows:.46fr 1fr 1.22fr}
                .wf02-back .wf02-main{grid-template-rows:1.42fr 1.42fr 1fr}
                .wf02-back .workflow-row{padding-top:3.8mm;padding-bottom:2mm}
                .wf02-back .workflow-row{grid-template-columns:minmax(0,1fr) 72mm}
                .wf02-back .image-badge{top:0;left:-5.5mm}
                .wf02 .workflow-row p{font-size:2.45mm;line-height:1.24}
                .double-shot .shot + .shot{margin-top:1.2mm}
                .double-shot figcaption{font-size:2.05mm}
                .context strong{font-size:2.7mm}
                .related .guide-chip{font-size:2.1mm;min-height:5.5mm;padding:.5mm 1mm;gap:1mm}
                .related .guide-chip b{width:3.6mm;height:3.6mm;font-size:1.3mm}
                .help-item strong{font-size:2.4mm}
                .help-item p{font-size:2.1mm}
                .source{white-space:normal}
            `;
            document.head.appendChild(style);
            document.title = `${guide.code} v${guide.version} - niet geaccepteerd`;
            document.querySelectorAll('.version').forEach((node, i) => {
                node.innerHTML = `Concept v${guide.version}<br>2026-10-01<small>Niet geaccepteerd<br>${i + 1} van ${guide.pages}</small>`;
            });
            document.querySelectorAll('.source').forEach(node => node.textContent = 'Bron: ontwikkelomgeving | 2026-10-01 | Niet geaccepteerd');
            document.querySelectorAll('.context > div:first-child strong').forEach(node => node.textContent = 'Bevoegde uitvoerder');
            document.querySelectorAll('.qr span').forEach(node => node.textContent = 'Digitale gids volgt');

            if (guide.code === 'WF-01') {
                document.querySelector('.title-block p').textContent = 'Kies de juiste workflowrij; start eenmaal of open de bestaande run';
                document.querySelector('.context > div:nth-child(2) strong').textContent = 'Juiste asset + workflowrechten';
                document.querySelector('.context > div:nth-child(3)').innerHTML = `<span>Vooraf</span>${chip('SC-01')}`;
                const one = document.querySelector('.step-one');
                copy(one.querySelector('.step-copy'), 'Open Tests / workflows', [`Controleer het apparaat met ${chip('SC-01')}. Tik daarna op het test-icoon in de asset.`]);
                figure(one.querySelector('figure'), '1A', 'WF-PROGRESSION-MOBILE-04', [12, 308, 397, 55], 'Het test-icoon in de volledige tabrij.', [204, 313, 48, 46]);
                const two = document.querySelector('.step-two');
                copy(two.querySelector('.step-copy'), 'Kies de afgesproken rij', ['Lees de profielnaam, status en uitleg. De nummers geven de volgorde aan; kies de workflow die bij je taak hoort.', 'Een grijze rij kan wachten op eerder werk of meer rechten. Lees de melding; sla de voorwaarde niet over.']);
                figure(two.querySelector('figure'), '2A', 'WF-PROGRESSION-MOBILE-04', [22, 483, 386, 133], 'Voorbeeld: Cleaning wacht op Standard Diagnostics.');
                const three = document.querySelector('.step-three');
                three.querySelector('.choice-intro').innerHTML = '<strong>Kies 3A of 3B. Voer maar een optie uit.</strong>';
                copy(three.querySelector('.step-copy'), 'Nieuwe run starten', ['Is de afgesproken workflow nog niet gestart en is <b>Start</b> actief? Tik eenmaal op <b>Start</b> in diezelfde rij.']);
                figure(three.querySelector('figure'), '3A', 'WF-PROGRESSION-MOBILE-04', [22, 749, 386, 99], 'Start hoort bij deze profielrij, niet bij alle workflows.', [66, 804, 48, 34]);
                const alternative = three.querySelector('.step-alternative');
                alternative.querySelector('h3').textContent = 'Bestaande run openen';
                alternative.querySelector('p').innerHTML = 'Open via de profielrij: <b>Continue</b> bij lopend werk, <b>Edit</b> voor bestaande resultaten, ook na afronding. Definitie gewijzigd? Vraag eerst een supervisor.';
                figure(alternative.querySelector('figure'), '3B', 'WF-PROGRESSION-MOBILE-04', [22, 616, 386, 133], 'Edit opent Pre-Sale Check; de waarschuwing blijft gelden.', [66, 705, 46, 34]);
                const four = document.querySelector('.step-four');
                copy(four.querySelector('.step-copy'), 'Controleer de kaarten', ['Controleer de asset en de kaarttitels van het gekozen profiel. Een nieuwe run begint met neutrale knoppen; een bestaande run kan al antwoorden tonen.']);
                figure(four.querySelector('figure'), '4A', 'WF-ACTIVE-NEUTRAL-MOBILE-04', [22, 238, 386, 280], 'Bestaande Pre-Sale Check: deze kaarten zijn nog neutraal.');
                four.querySelector('.step-copy').insertAdjacentHTML('beforeend', `<p class="next-guide">Ga verder met ${chip('WF-02')}</p>`);
                help(document.querySelector('.help'), 'Hulp bij kiezen en starten', [
                    ['Voorwaarde of rechten', 'Lees de melding. Voer het genoemde werk uit of vraag een bevoegde collega.'],
                    ['Profiel klopt niet', 'Niet ingesteld of gewijzigd? Stop en vraag een supervisor.'],
                    ['Nogmaals starten?', 'Start new / Override: alleen bevoegd, met reden en bevestiging. Vraag een supervisor.'],
                    ['Geen icoon of kaarten', 'Controleer asset en rechten. Vraag hulp; start niet nog een run.'],
                ]);
                document.querySelector('.done span').textContent = 'De juiste run is open; asset en kaarttitels kloppen. Ga verder met WF-02.';
                document.querySelector('.related').innerHTML = ['SC-01', 'WF-02', 'HELP-01'].map(chip).join('');
            } else {
                const pages = [...document.querySelectorAll('.guide-page')];
                pages[0].querySelector('.title-block p').textContent = 'Voer de controle uit en leg het echte resultaat vast';
                pages[1].querySelector('.title-block p').textContent = 'Voeg bewijs toe, wacht op opslaan en keer terug naar Workflows';
                const steps = [...document.querySelectorAll('.workflow-row')];
                copy(steps[0].querySelector('.step-copy'), 'Controleer apparaat en run', ['Vergelijk assettag en model in het kruimelpad met het apparaat. Gebruik de run die je in WF-01 bij de afgesproken profielrij opende.']);
                figure(steps[0].querySelector('figure'), '1A', 'WF-ACTIVE-NEUTRAL-MOBILE-04', [10, 132, 400, 58], 'De breadcrumb toont de asset; WF-01 bepaalt de juiste run.');
                copy(steps[1].querySelector('.step-copy'), 'Lees de instructie', ['Tik op <b>Show instructions</b> (Toon instructies). Lees de hele opdracht voordat je een resultaat kiest.']);
                figure(steps[1].querySelector('figure'), '2A', 'WF-ACTIVE-INSTRUCTIONS-MOBILE-04', [22, 238, 386, 175], 'De volledige kaart en de echte opdracht blijven zichtbaar.', [252, 256, 135, 24]);
                copy(steps[2].querySelector('.step-copy'), 'Voer uit en kies resultaat', ['Voer de controle uit. Kies <b>Pass</b> (Geslaagd) of <b>Fail</b> (Mislukt) naar waarheid.', 'Een taakkaart gebruikt <b>Done</b> (Gedaan) en <b>Not Done</b> (Niet gedaan). Onuitgevoerd? Kies nog geen positief resultaat.', 'Een kaart met <b>Optional</b> is optioneel voor de voortgang. Een verplicht item moet positief zijn voor de volgende afhankelijke workflow.']);
                const third = steps[2].querySelector('figure');
                third.classList.add('double-shot');
                third.innerHTML = `<span class="image-badge">3A</span>${shot('WF-ACTIVE-NEUTRAL-MOBILE-04', [22, 238, 386, 137], [34, 293, 362, 42])}${shot('WF-ACTIVE-NEUTRAL-MOBILE-04', [22, 523, 386, 137], [34, 578, 362, 42])}<figcaption>Boven: controle met Pass/Fail. Onder: taakkaart met Done/Not Done.</figcaption>`;
                copy(steps[3].querySelector('.step-copy'), 'Voeg een notitie toe', ['Open <b>Note</b> (Notitie) bij een mislukking, uitzondering of relevante meetwaarde. Schrijf kort wat je zag en deed.', '<b>Wacht op een bijgewerkte Saved at-melding</b> nadat je hebt getypt. Een oude tijd bij een leeg veld bewijst geen nieuwe opslag.']);
                figure(steps[3].querySelector('figure'), '4A', 'WF-ACTIVE-NOTE-MOBILE-04', [22, 238, 386, 273], 'Note opent het veld; controleer de nieuwe opslagtijd.', [33, 402, 364, 77]);
                copy(steps[4].querySelector('.step-copy'), 'Voeg foto of schermbeeld toe', ['Open <b>Photo</b> (Foto) en kies <b>Add photo</b> (Foto toevoegen) als de opdracht bewijs vraagt.', 'Kies of maak een duidelijk beeld van dit apparaat en het relevante detail. Controleer dat de miniatuur verschijnt.', 'Neem geen wachtwoorden, herstelcodes of klantgegevens op. Workflowbewijs is afgeschermd.']);
                figure(steps[4].querySelector('figure'), '5A', 'WF-ACTIVE-PHOTO-MOBILE-04', [22, 238, 386, 286], 'Volledige kaart, afgeschermd bewijs en Add photo.', [123, 481, 80, 24]);
                copy(steps[5].querySelector('.step-copy'), 'Controleer en kies Klaar', ['Herhaal stap 2-5. Resultaten slaan automatisch op; wacht tot alle wijzigingen bewaard zijn.', '<b>Done</b> (Klaar) blijft uitgeschakeld zolang verplichte antwoorden ontbreken of een opslag loopt. Actief? Tik erop en controleer dezelfde asset op het tabblad Workflows.', 'Lees daar de profielstatus en eventuele blokkade. Mislukt werk blijft mislukt; Klaar geeft de asset niet vrij voor verkoop.']);
                const sixth = steps[5].querySelector('figure');
                sixth.classList.add('double-shot');
                sixth.innerHTML = `<span class="image-badge">6A</span>${shot('WF-ACTIVE-NEUTRAL-MOBILE-04', [22, 665, 386, 79])}${shot('WF-DONE-READY-MOBILE-04', [22, 524, 386, 79], [327, 527, 80, 49])}<figcaption>Boven: ontbrekende antwoorden. Onder: klaar om terug te keren.</figcaption>`;
                help(pages[0].querySelector('.help'), 'Hulp tijdens de controle', [
                    ['Instructie onduidelijk', 'Stop en vraag een supervisor. Raad geen resultaat.'],
                    ['Controle niet mogelijk', 'Leg de reden vast en vraag hulp. Kies geen Pass of Done zonder uitvoering.'],
                    ['Verkeerd apparaat of run', 'Kies geen resultaat. Open de juiste asset en profielrij via WF-01.'],
                ]);
                help(pages[1].querySelector('.help'), 'Hulp bij bewijs en afronden', [
                    ['Opslaan of foto mislukt', 'Controleer melding en verbinding. Laat de kaart open en vraag hulp bij een blijvende fout.'],
                    ['Klaar blijft grijs', 'Controleer verplichte kaarten en wacht op opslag. Start geen extra run.'],
                    ['Vervolg blijft geblokkeerd', 'Lees de melding. Vraag hulp bij fouten of een gewijzigde definitie.'],
                ]);
                pages[0].querySelector('.done span').textContent = 'De controle is uitgevoerd en eerlijk vastgelegd. Ga verder op de volgende pagina.';
                pages[1].querySelector('.done span').textContent = 'Resultaten en bewijs zijn opgeslagen; je bent terug bij dezelfde asset en kent de volgende stap.';
                pages[0].querySelector('.related').innerHTML = ['WF-01', 'HELP-01'].map(chip).join('');
                pages[1].querySelector('.related').innerHTML = ['WF-01', 'AST-04', 'HELP-01'].map(chip).join('');
                pages[1].querySelector('.context > div:nth-child(3)').innerHTML = `<span>Vooraf</span>${chip('WF-02')}`;
            }
        }, { guide, sources, palette: GUIDE_FAMILIES.WF, references });
        await page.evaluate(() => document.fonts.ready);
        await page.evaluate(() => new Promise(resolve => requestAnimationFrame(() => requestAnimationFrame(resolve))));
        const qa = await page.evaluate(() => {
            const pages = [...document.querySelectorAll('.guide-page')];
            const box = n => n.getBoundingClientRect();
            const outside = (child, parent, tolerance = 1.5) => child.left < parent.left - tolerance || child.right > parent.right + tolerance || child.top < parent.top - tolerance || child.bottom > parent.bottom + tolerance;
            return {
                pages: pages.length,
                images: document.querySelectorAll('image').length,
                layout: pages.map(p=>({rows:getComputedStyle(p.querySelector('main')).gridTemplateRows,mainBottom:box(p.querySelector('main')).bottom,helpTop:box(p.querySelector('.help')).top,cards:[...p.querySelectorAll('.step')].map(n=>({top:box(n).top,bottom:box(n).bottom,height:box(n).height}))})),
                text: document.body.innerText,
                overflow: pages.flatMap((p, index) => [...p.querySelectorAll('p,h1,h2,h3,figcaption,.help-item,.done,.version,.context,.guide-chip,.shot,.image-badge,.step-number')].filter(n => {
                    const r = box(n), card = n.closest('.step,.help-item');
                    return outside(r, box(p)) || (card && !n.matches('.step-number') && outside(r, box(card)));
                }).map(n => ({ page: index + 1, element: n.className, text: n.textContent.slice(0, 100), top:box(n).top,bottom:box(n).bottom,cardTop:box(n.closest('.step,.help-item')??p).top,cardBottom:box(n.closest('.step,.help-item')??p).bottom }))),
                mainOverHelp: pages.some(p => [...p.querySelectorAll('main,.step')].some(n => box(n).bottom > box(p.querySelector('.help')).top + 1)),
                clippedShots:[...document.querySelectorAll('.shot svg')].filter(n=>outside(box(n),box(n.parentElement))).length,
                choiceOverlap:[...document.querySelectorAll('.choice-divider')].some(n=>box(n).top<box(n.parentElement.previousElementSibling).bottom),
            };
        });
        fs.writeFileSync(path.join(out, `${guide.code}-geometry.json`), JSON.stringify(qa, null, 2));
        fs.writeFileSync(path.join(out, `${guide.file}.html`), await page.content());
        assert.equal(qa.pages, guide.pages);
        assert.deepEqual(qa.overflow, [], JSON.stringify(qa.overflow));
        assert.equal(qa.mainOverHelp, false, 'Instruction area overlaps help');
        assert.equal(qa.clippedShots,0,'Screenshot viewport clipped by its frame');
        assert.equal(qa.choiceOverlap,false,'Choice divider overlaps the preceding caption');
        assert.ok(qa.text.includes(`Concept v${guide.version}`) && qa.text.includes('Niet geaccepteerd'));
        assert.ok(!qa.text.includes('Nieuwe workflow starten'), 'Obsolete general start action');
        const html = path.join(out, `${guide.file}.html`);
        const pdf = path.join(out, `${guide.file}.pdf`);
        fs.writeFileSync(html, await page.content());
        await page.pdf({ path: pdf, preferCSSPageSize: true, printBackground: true });
        const info = spawnSync(resolveCommand('GUIDE_PDFINFO_PATH', 'pdfinfo'), [pdf], { encoding: 'utf8', windowsHide: true });
        assert.equal(info.status, 0, info.stderr);
        assert.equal(Number(info.stdout.match(/^Pages:\s+(\d+)/m)?.[1]), guide.pages);
        const raster = path.join(out, `${guide.code}-render`);
        fs.mkdirSync(raster, { recursive: true });
        const rendered = spawnSync(resolveCommand('GUIDE_PDFTOPPM_PATH', 'pdftoppm'), ['-png', '-r', '144', pdf, path.join(raster, 'page')], { encoding: 'utf8', windowsHide: true });
        assert.equal(rendered.status, 0, rendered.stderr);
        report.push({ ...guide, status: 'Working draft - not accepted', sha256: sha(pdf), sourceHtmlSha256: sha(html), inputSha256: sha(path.join(round, 'inputs', guide.input)), bytes: fs.statSync(pdf).size, qa });
        await page.close();
    }
} finally {
    await browser.close();
}
const manifest = { schemaVersion: 1, date: '2026-10-01', status: 'Working drafts - exact-version review pending', acceptedSelectionChanged: false, generatorSha256: sha(path.join(repoRoot,'scripts/manuals/generate-workflow-revisions-2026-10-01.mjs')), sourceAuditSha256: sha(path.join(round,'source-audit.json')), sourceContext: 'Read-only dev.inbit captures; refurbisher-followups worktree at 731bb43c88 with concurrent uncommitted changes', evidence: Object.entries(sources).map(([id, source]) => ({ id, file: `evidence/${id}.jpg`, width: source.width, height: source.height, sha256: source.sha256 })), guides: report };
fs.writeFileSync(path.join(out, 'manifest.json'), `${JSON.stringify(manifest, null, 2)}\n`);
if (retain) {
    fs.mkdirSync(repoPdfOutputRoot, { recursive: true });
    for (const guide of definitions) {
        for (const ext of ['pdf', 'html']) fs.copyFileSync(path.join(out, `${guide.file}.${ext}`), path.join(round, `${guide.file}.${ext}`), fs.constants.COPYFILE_EXCL);
        fs.copyFileSync(path.join(out, `${guide.file}.pdf`), path.join(repoPdfOutputRoot, `${guide.file}.pdf`), fs.constants.COPYFILE_EXCL);
    }
    fs.copyFileSync(path.join(out, 'manifest.json'), path.join(round, 'manifest.json'), fs.constants.COPYFILE_EXCL);
}
console.log(JSON.stringify(report.map(({ code, version, pages, bytes, sha256, qa }) => ({ code, version, pages, bytes, sha256, images: qa.images })), null, 2));
