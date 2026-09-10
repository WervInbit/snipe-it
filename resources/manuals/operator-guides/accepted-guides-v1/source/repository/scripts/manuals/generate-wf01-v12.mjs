import assert from 'node:assert/strict';
import crypto from 'node:crypto';
import fs from 'node:fs';
import path from 'node:path';
import { spawnSync } from 'node:child_process';
import { pathToFileURL } from 'node:url';
import {
  guideOutputDir, loadGuideDependency, repoPdfOutputRoot, repoRoot,
  resolveChromeExecutable, resolveCommand,
} from './lib/guide-paths.mjs';

const slug = 'WF-01-workflow-starten-v12-draft';
const proofRoot = guideOutputDir('WF-01-v12');
const baselineRoot = path.join(proofRoot, 'baseline');
const pdfPath = path.join(repoPdfOutputRoot, `${slug}.pdf`);
const htmlPath = path.join(proofRoot, `${slug}.html`);
const baselineGenerator = path.join(repoRoot, 'scripts/manuals/generate-workflow-guide-review.mjs');
const hash = (value) => crypto.createHash('sha256').update(value).digest('hex');
const baselineSourceHash = hash(fs.readFileSync(baselineGenerator));

function run(command, args, options = {}) {
  const result = spawnSync(command, args, {
    cwd: repoRoot, encoding: 'utf8', windowsHide: true,
    stdio: ['ignore', 'pipe', 'pipe'], ...options,
  });
  assert.equal(result.status, 0, result.stderr || result.stdout);
}

fs.mkdirSync(proofRoot, { recursive: true });
fs.mkdirSync(repoPdfOutputRoot, { recursive: true });
// The legacy generator renders a batch; all of it stays in the proof directory.
run(process.execPath, [baselineGenerator], {
  env: {
    ...process.env, SNIPEIT_WF01_VERSION: '10', SNIPEIT_WF02_VERSION: '11',
    SNIPEIT_GUIDE_DATE: '2026-08-18', SNIPEIT_GUIDE_OUT_DIR: baselineRoot,
    SNIPEIT_GUIDE_PDF_OUT_DIR: path.join(baselineRoot, 'batch'),
  },
});
const baselineHtml = path.join(baselineRoot, 'WF-01-start-workflow-v10-draft.html');
const { chromium } = loadGuideDependency('playwright');
const browser = await chromium.launch({ executablePath: resolveChromeExecutable(), headless: true });
let validation;
try {
  const page = await browser.newPage();
  await page.goto(pathToFileURL(baselineHtml).href);
  await page.emulateMedia({ media: 'print' });
  await page.evaluate(() => document.fonts.ready);

  const inventory = () => {
    const box = (node) => {
      const { x, y, width, height } = node.getBoundingClientRect();
      return { x, y, width, height };
    };
    return {
      visuals: [...document.querySelectorAll('.visual')].map((node) => ({
        label: node.querySelector('.image-badge').textContent,
        html: node.innerHTML, box: box(node), shot: box(node.querySelector('.shot')),
      })),
      protected: ['.title-block', '.context', '.step-one', '.step-two', '.step-four', '.help', '.done']
        .map((selector) => ({ selector, html: document.querySelector(selector).outerHTML,
          box: box(document.querySelector(selector)) })),
      paragraphs: [...document.querySelectorAll('.step-three h3, .step-three p, .step-three figcaption')]
        .map((node) => node.textContent),
      related: document.querySelector('.related').outerHTML,
      qr: document.querySelector('.qr').outerHTML,
      step3: box(document.querySelector('.step-three')),
      page: box(document.querySelector('.guide-page')),
    };
  };
  const before = await page.evaluate(inventory);
  await page.evaluate(() => {
    document.title = 'WF-01 Workflow starten - v12';
    document.querySelector('.version').innerHTML = 'Draft v12<br>2026-09-08<small>1 van 1</small>';
    const source = document.querySelector('.source');
    source.textContent = source.textContent.replace('2026-08-18', '2026-09-08');
    const step = document.querySelector('.step-three');
    const intro = document.createElement('div');
    intro.className = 'choice-intro';
    intro.innerHTML = '<strong>Kies 3A of 3B. Voer maar een optie uit.</strong>'
      + '<p>Staat er al een juiste, onafgeronde run? Kies dan 3B.</p>';
    step.insertBefore(intro, step.querySelector('.step-copy'));
    const style = document.createElement('style');
    style.textContent = `
      .wf01-main .step-three { border-color: #F97316; background: #FFF7ED; }
      .choice-intro { margin-bottom: 2mm; }
      .choice-intro strong { display: block; color: #102033; font-size: 2.8mm; line-height: 1.27; }
      .choice-intro p { margin: 1mm 0 0; color: #334155; font-size: 2.55mm; line-height: 1.27; }
      .step-three .choice-divider span { padding: .6mm 2mm; border: .35mm solid #FDBA74;
        border-radius: 1mm; background: #FFFFFF; }
    `;
    document.head.append(style);
  });
  const after = await page.evaluate(inventory);
  assert.deepEqual(after.protected, before.protected, 'An unrelated region changed');
  assert.deepEqual(after.related, before.related);
  assert.deepEqual(after.qr, before.qr);
  assert.deepEqual(after.step3, before.step3, 'Step-3 frame changed size');
  assert.deepEqual(after.page, before.page);
  assert.deepEqual(after.visuals.map((v) => v.label), ['1A', '2A', '3A', '3B', '4A']);
  before.visuals.forEach((visual, index) => {
    const revised = after.visuals[index];
    assert.equal(revised.html, visual.html, `Changed screenshot/caption ${visual.label}`);
    assert.equal(revised.shot.width, visual.shot.width, `Changed width ${visual.label}`);
    assert.equal(revised.shot.height, visual.shot.height, `Changed height ${visual.label}`);
  });
  before.paragraphs.forEach((text) => assert.ok(after.paragraphs.includes(text), `Removed: ${text}`));
  const bounds = await page.evaluate(() => {
    const step = document.querySelector('.step-three').getBoundingClientRect();
    const content = [...document.querySelectorAll('.step-three .choice-intro, .step-three .step-copy, .step-three > .visual, .step-three .step-alternative')]
      .map((node) => { const b = node.getBoundingClientRect(); return { top: b.top, bottom: b.bottom, left: b.left, right: b.right }; });
    return { step: { top: step.top, bottom: step.bottom, left: step.left, right: step.right }, content };
  });
  bounds.content.forEach((box, index) => {
    assert.ok(box.top >= bounds.step.top && box.bottom <= bounds.step.bottom, 'Step-3 content overflow');
    assert.ok(box.left >= bounds.step.left && box.right <= bounds.step.right, 'Step-3 horizontal overflow');
    if (index) assert.ok(box.top >= bounds.content[index - 1].bottom, 'Step-3 content overlap');
  });
  fs.writeFileSync(htmlPath, await page.content(), 'utf8');
  await page.pdf({ path: pdfPath, preferCSSPageSize: true, printBackground: true, tagged: true });
  validation = {
    version: 12, baselineVersion: 10, baselineSourceHash,
    allFiveScreenshotHtmlAndDimensions: 'unchanged', originalStep3Copy: 'retained',
    protectedRegionsHtmlAndGeometry: 'unchanged', step3ContentBounds: bounds,
    beforeVisuals: before.visuals.map(({ html, ...v }) => ({ ...v, htmlSha256: hash(html) })),
    afterVisuals: after.visuals.map(({ html, ...v }) => ({ ...v, htmlSha256: hash(html) })),
    pdf: { file: path.basename(pdfPath), sha256: hash(fs.readFileSync(pdfPath)) },
  };
} finally {
  await browser.close();
}
assert.equal(hash(fs.readFileSync(baselineGenerator)), baselineSourceHash);
run(resolveCommand('GUIDE_PDFTOPPM_PATH', 'pdftoppm'), ['-png', '-r', '144', pdfPath, path.join(proofRoot, slug)]);
fs.writeFileSync(path.join(proofRoot, 'validation.json'), `${JSON.stringify(validation, null, 2)}\n`);
console.log(JSON.stringify({ pdfPath, htmlPath, proofRoot, checks: 'passed' }, null, 2));
