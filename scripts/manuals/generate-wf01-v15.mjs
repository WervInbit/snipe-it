import assert from 'node:assert/strict';
import crypto from 'node:crypto';
import fs from 'node:fs';
import path from 'node:path';
import { spawnSync } from 'node:child_process';
import { pathToFileURL } from 'node:url';
import { loadGuideDependency, repoRoot, repoPdfOutputRoot, resolveChromeExecutable, resolveCommand } from './lib/guide-paths.mjs';

const round = path.join(repoRoot, 'resources/manuals/operator-guides/review-rounds/2026-10-01-wf01-v15');
const previous = path.join(repoRoot, 'resources/manuals/operator-guides/review-rounds/2026-10-01-workflows');
const stem = 'WF-01-workflow-starten-v15-draft';
const retain = process.argv.includes('--retain');
const out = path.join(repoRoot, 'output/manuals/proofs/wf01-v15');
const hash = file => crypto.createHash('sha256').update(fs.readFileSync(file)).digest('hex');
const source = path.join(round, 'evidence/WF-START-STANDARD-DIAGNOSTICS-MOBILE-05.jpg');
const sharp = loadGuideDependency('sharp');
const image = await sharp(source).metadata();
assert.equal(image.format, 'jpeg');
const uri = `data:image/jpeg;base64,${fs.readFileSync(source).toString('base64')}`;
const before = ['WF-01-workflow-starten-v14-draft.pdf', 'WF-02-complete-workflow-v13-draft.pdf'].map(file => ({ file, sha256: hash(path.join(previous, file)) }));
if (retain) for (const file of [`${stem}.html`, `${stem}.pdf`, 'manifest.json']) assert.ok(!fs.existsSync(path.join(round, file)), `Frozen output already exists: ${file}`);
if (retain) assert.ok(!fs.existsSync(path.join(repoPdfOutputRoot, `${stem}.pdf`)), 'Delivery already exists');
fs.mkdirSync(out, { recursive: true });
const { chromium } = loadGuideDependency('playwright');
const browser = await chromium.launch({ executablePath: resolveChromeExecutable(), headless: true });
let qa;
try {
    const page = await browser.newPage();
    await page.goto(pathToFileURL(path.join(previous, 'WF-01-workflow-starten-v14-draft.html')).href);
    await page.emulateMedia({ media: 'print' });
    await page.evaluate(({ uri, width, height }) => {
        const shot = (node, label, crop, caption, mark) => {
            const target = mark ? `<rect class="target-mark" x="${mark[0]}" y="${mark[1]}" width="${mark[2]}" height="${mark[3]}" rx="5"/>` : '';
            node.innerHTML = `<span class="image-badge">${label}</span><div class="shot"><svg viewBox="${crop.join(' ')}" preserveAspectRatio="xMidYMid meet" aria-hidden="true"><image href="${uri}" x="0" y="0" width="${width}" height="${height}"/>${target}</svg></div><figcaption>${caption}</figcaption>`;
            node.dataset.sourceId = 'WF-START-STANDARD-DIAGNOSTICS-MOBILE-05';
        };
        document.title = 'WF-01 v15 - niet geaccepteerd';
        document.querySelector('.version').innerHTML = 'Concept v15<br>2026-10-01<small>Niet geaccepteerd<br>1 van 1</small>';
        document.querySelector('.title-block p').textContent = 'Begin bij de eerstvolgende workflow; start of ga verder met een bestaande run';
        shot(document.querySelector('.step-one figure'), '1A', [12, 308, 397, 55], 'Het test-icoon in de volledige tabrij.', [204, 313, 48, 46]);
        document.querySelector('.step-two .step-copy').innerHTML = '<h3>Bekijk de workflowvolgorde</h3><p>Begin bij de eerste workflow die nog moet worden uitgevoerd. Een grijze vervolgstap wacht op eerder werk.</p>';
        shot(document.querySelector('.step-two figure'), '2A', [22, 371, 386, 226], 'Standard Diagnostics kan starten; Cleaning wacht.');
        document.querySelector('.step-three > .step-copy p').innerHTML = 'Nog niet gestart en <b>Start</b> actief? Tik eenmaal op <b>Start</b> bij de workflow die je gaat uitvoeren.';
        shot(document.querySelector('.step-three > figure'), '3A', [22, 371, 386, 108], 'Start bij Standard Diagnostics.', [66, 440, 48, 33]);
        document.querySelector('.step-alternative figcaption').textContent = 'Voorbeeld bestaande run: Pre-Sale Check.';
        const style = document.createElement('style');
        style.textContent = '.wf01-main .step-two figure{width:65mm;align-self:center}.wf01-main .step-two .step-copy p{margin-bottom:0}.wf01-main .step-two{padding-top:2.8mm}.wf01-main .step-three{gap:1mm}';
        document.head.appendChild(style);
    }, { uri, width: image.width, height: image.height });
    await page.evaluate(() => document.fonts.ready);
    qa = await page.evaluate(() => {
        const p = document.querySelector('.guide-page');
        const box = node => node.getBoundingClientRect();
        const outside = (a, b) => a.left < b.left - 1.5 || a.right > b.right + 1.5 || a.top < b.top - 1.5 || a.bottom > b.bottom + 1.5;
        return {
            pages: document.querySelectorAll('.guide-page').length,
            images: document.querySelectorAll('image').length,
            text: document.body.innerText,
            overflow: [...p.querySelectorAll('p,h1,h2,h3,figcaption,.help-item,.done,.version,.context,.guide-chip,.shot,.image-badge')].filter(n => outside(box(n), box(p)) || (n.closest('.step,.help-item') && outside(box(n), box(n.closest('.step,.help-item'))))).map(n => ({element:n.className,text:n.textContent.slice(0,100)})),
            mainOverHelp: [...p.querySelectorAll('main,.step')].some(n => box(n).bottom > box(p.querySelector('.help')).top + 1),
            clippedShots: [...p.querySelectorAll('.shot svg')].filter(n => outside(box(n), box(n.parentElement))).length,
            choiceOverlap: [...p.querySelectorAll('.choice-divider')].some(n => box(n).top < box(n.parentElement.previousElementSibling).bottom),
        };
    });
    fs.writeFileSync(path.join(out, 'geometry.json'), JSON.stringify(qa, null, 2));
    assert.equal(qa.pages, 1);
    assert.equal(qa.images, 5);
    assert.deepEqual(qa.overflow, []);
    assert.equal(qa.mainOverHelp, false);
    assert.equal(qa.clippedShots, 0);
    assert.equal(qa.choiceOverlap, false);
    assert.ok(qa.text.includes('Concept v15') && qa.text.includes('Niet geaccepteerd'));
    assert.ok(!qa.text.includes('afgesproken'));
    fs.writeFileSync(path.join(out, `${stem}.html`), await page.content());
    await page.pdf({ path: path.join(out, `${stem}.pdf`), preferCSSPageSize: true, printBackground: true });
} finally {
    await browser.close();
}
const pdf = path.join(out, `${stem}.pdf`);
const info = spawnSync(resolveCommand('GUIDE_PDFINFO_PATH', 'pdfinfo'), [pdf], {encoding:'utf8',windowsHide:true});
assert.equal(info.status, 0, info.stderr);
assert.match(info.stdout, /^Pages:\s+1$/m);
const size = info.stdout.match(/^Page size:\s+([\d.]+) x ([\d.]+) pts \(A4\)/m);
assert.ok(size && Math.abs(Number(size[1])-595.276)<1 && Math.abs(Number(size[2])-841.890)<1);
const raster = spawnSync(resolveCommand('GUIDE_PDFTOPPM_PATH','pdftoppm'), ['-png','-r','144',pdf,path.join(out,'page')], {encoding:'utf8',windowsHide:true});
assert.equal(raster.status,0,raster.stderr);
for (const item of before) assert.equal(hash(path.join(previous,item.file)),item.sha256,'Previous PDF changed');
const manifest = {date:'2026-10-01',status:'Working draft - not accepted',acceptedSelectionChanged:false,guide:'WF-01',version:15,pages:1,file:`${stem}.pdf`,bytes:fs.statSync(pdf).size,sha256:hash(pdf),htmlSha256:hash(path.join(out,`${stem}.html`)),generatorSha256:hash(path.join(repoRoot,'scripts/manuals/generate-wf01-v15.mjs')),input:{file:'../2026-10-01-workflows/WF-01-workflow-starten-v14-draft.html',sha256:hash(path.join(previous,'WF-01-workflow-starten-v14-draft.html'))},evidence:{file:'evidence/WF-START-STANDARD-DIAGNOSTICS-MOBILE-05.jpg',sha256:hash(source),width:image.width,height:image.height,asset:'INBIT-AA0004',url:'https://dev.inbit/hardware/12#tests',role:'Demo Refurbisher',actions:'Navigation only; no workflow start or result submitted',crops:{'1A':[12,308,397,55],'2A':[22,371,386,226],'3A':[22,371,386,108]},targets:{'1A':[204,313,48,46],'3A':[66,440,48,33]}},retainedVisuals:'3B and 4A remain existing Pre-Sale Check run 12 on INBIT-AA0003, embedded from v14; separate alternative example, not the new asset after a start',preserved:before,qa};
fs.writeFileSync(path.join(out,'manifest.json'),`${JSON.stringify(manifest,null,2)}\n`);
if(retain){
    for(const ext of ['html','pdf']) fs.copyFileSync(path.join(out,`${stem}.${ext}`),path.join(round,`${stem}.${ext}`),fs.constants.COPYFILE_EXCL);
    fs.copyFileSync(path.join(out,'manifest.json'),path.join(round,'manifest.json'),fs.constants.COPYFILE_EXCL);
    fs.copyFileSync(pdf,path.join(repoPdfOutputRoot,`${stem}.pdf`),fs.constants.COPYFILE_EXCL);
}
console.log(JSON.stringify({file:pdf,pages:1,bytes:manifest.bytes,sha256:manifest.sha256,retained:retain}));

