import assert from 'node:assert/strict';
import crypto from 'node:crypto';
import fs from 'node:fs';
import path from 'node:path';
import { spawnSync } from 'node:child_process';
import { pathToFileURL } from 'node:url';
import { guideFollowups } from './lib/guide-followup-patches.mjs';
import {
  guideOutputDir, loadGuideDependency, repoRoot, repoPdfOutputRoot,
  resolveChromeExecutable, resolveCommand,
} from './lib/guide-paths.mjs';

const finalizeOnly = process.argv.includes('--finalize-only');
const requested = process.argv.slice(2).filter((arg) => arg !== '--finalize-only').map((code) => code.toUpperCase());
const selections = requested.length ? guideFollowups.filter((g) => requested.includes(g.code)) : guideFollowups;
assert.equal(selections.length, requested.length || guideFollowups.length, 'Unknown or repeated guide code');
const { chromium } = loadGuideDependency('playwright');
const hash = (data) => crypto.createHash('sha256').update(data).digest('hex');
const proofRoot = guideOutputDir('guide-followups-2026-09-08');
const filesBelow = (dir) => fs.readdirSync(dir, { withFileTypes: true }).flatMap((entry) => entry.isDirectory()
  ? filesBelow(path.join(dir, entry.name)) : [path.join(dir, entry.name)]);
const run = (command, args, env = process.env) => {
  const result = spawnSync(command, args, { cwd: repoRoot, env, encoding: 'utf8', windowsHide: true });
  assert.equal(result.status, 0, result.stderr || result.stdout);
};

for (const guide of selections) {
  const root = path.join(proofRoot, `${guide.code}-v${guide.version}`);
  fs.mkdirSync(root, { recursive: true });
  fs.mkdirSync(repoPdfOutputRoot, { recursive: true });
  const originalPath = path.join(repoRoot, 'scripts/manuals', guide.script);
  const originalSource = fs.readFileSync(originalPath, 'utf8');
  const originalHash = hash(originalSource);
  const patchedSource = guide.patch(originalSource).replaceAll("from './lib/", `from '${pathToFileURL(path.join(repoRoot, 'scripts/manuals/lib')).href}/`);
  const localGenerator = path.join(root, guide.script);
  if (!finalizeOnly) fs.writeFileSync(localGenerator, patchedSource);
  const intermediate = path.join(root, 'renderer-output');
  if (!finalizeOnly) run(process.execPath, [localGenerator], {
    ...process.env, SNIPEIT_GUIDE_FILTER: guide.code, SNIPEIT_GUIDE_DATE: '2026-09-08',
    SNIPEIT_GUIDE_OUT_DIR: intermediate, SNIPEIT_GUIDE_PDF_OUT_DIR: path.join(root, 'intermediate-pdf'),
    ...guide.env,
  });
  assert.equal(hash(fs.readFileSync(originalPath, 'utf8')), originalHash, 'Historical generator changed');
  const matches = filesBelow(intermediate).filter((file) => path.basename(file) === guide.html);
  assert.equal(matches.length, 1, `Expected one HTML for ${guide.code}`);
  const browser = await chromium.launch({ executablePath: resolveChromeExecutable(), headless: true });
  try {
    const page = await browser.newPage();
    await page.goto(pathToFileURL(matches[0]).href);
    await page.emulateMedia({ media: 'print' });
    const changes = await page.evaluate(({ code, old, version }) => {
      const expression = new RegExp(`\\bv${old}\\b`, 'g');
      const walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT);
      let count = 0;
      while (walker.nextNode()) {
        const node = walker.currentNode;
        if (code === 'SC-01') node.textContent = node.textContent.replaceAll('2026-07-21', '2026-09-08');
        if (expression.test(node.textContent)) {
          expression.lastIndex = 0;
          node.textContent = node.textContent.replace(expression, `v${version}`);
          count++;
        }
        expression.lastIndex = 0;
      }
      document.title = `${code} v${version}`;
      if (code.startsWith('AST-')) {
        const style = document.createElement('style');
        style.textContent = '.guide-chip { white-space: normal; } .guide-chip span { overflow: visible; text-overflow: clip; } .guide-chip b { flex-shrink: 0; }';
        document.head.append(style);
      }
      if (code === 'AST-05') {
        const cards = document.querySelectorAll('.step-card');
        const paragraphs = cards[1].querySelectorAll('.step-copy p');
        paragraphs[0].textContent = 'Controleer alle verplichte profielen voor dit model.';
        paragraphs[1].textContent = 'Overal: afgerond, 0 Mislukt en passend bewijs.';
        paragraphs[2].textContent = 'Profiel of bewijs onduidelijk? Geef nog niet vrij.';
        cards[1].querySelector('figcaption').textContent = 'Controleer meer dan deze ene geslaagde run.';
        cards[1].style.paddingTop = '6mm';
        cards[3].style.paddingTop = '6mm';
        cards[3].style.background = '#FFF7ED';
        cards[3].style.borderColor = '#FDBA74';
      }
      if (code === 'AST-03') {
        const style = document.createElement('style');
        // Recover captions already squeezed by the baseline's fixed 31 mm image bands.
        // Keep those bands intact and use space inside the header and step padding.
        style.textContent = '.guide-page { grid-template-rows:18mm 17mm minmax(0,1fr) auto 12mm 29mm; } .row-card { padding-top:1mm; padding-bottom:1mm; } .caption { font-size:2mm; min-height:3mm; line-height:1.15; }';
        document.head.append(style);
      }
      if (code === 'CMP-02') {
        const choice = document.querySelector('.step-2');
        choice.style.background = '#FFF7ED';
        choice.style.borderColor = '#FDBA74';
      }
      if (code === 'WF-02') {
        for (const warning of document.querySelectorAll('.inline-stop')) {
          if (warning.textContent.startsWith('Resultaat ontbreekt')) warning.style.color = '#B45309';
        }
      }
      if (code === 'CAT-00') {
        for (const text of document.querySelectorAll('svg text')) {
          if (text.textContent === 'Eerst de samenhang; de volgende delen leggen ieder blok afzonderlijk uit.') {
            text.textContent = 'Voorbeeld: geplaatst vanuit een definitie. Een aangepast component of component in tray kan afwijken.';
          }
          if (text.textContent === 'Dit record:') text.textContent = 'Dit geplaatste record:';
        }
      }
      if (code === 'CAT-01') {
        for (const text of document.querySelectorAll('svg text')) {
          if (text.textContent === 'In voorbereiding') text.textContent = 'Werkconcept';
          if (text.textContent === 'Vul code en label in; kies Opslaan') text.textContent = 'Vul in en kies Opslaan';
        }
      }
      const localLabels = code === 'USR-03' ? {
        'Controleer de gebruiker': 'Zoek juiste gebruiker',
        'Maak één tijdelijk wachtwoord': 'Maak tijdelijk wachtwoord',
      } : code === 'AC-02' ? { 'Sla het nieuwe wachtwoord op': 'Sla op en lees de melding' }
        : code === 'USR-02' ? {
          'Effect van recht onduidelijk': 'Recht onduidelijk',
          'Controleer de zichtbare groep op het opgeslagen account.': 'Schermvoorbeeld: controleer de groep op het gekozen account.',
        } : {};
      for (const text of document.querySelectorAll('svg text')) {
        if (localLabels[text.textContent]) text.textContent = localLabels[text.textContent];
      }
      // Keep the retained tray guide's title consistent in the new references.
      for (const label of document.querySelectorAll('.guide-chip span')) {
        label.textContent = label.textContent.replace('Component verwijderen naar tray', 'Component naar tray verplaatsen');
      }
      return count;
    }, { code: guide.code, old: guide.old, version: guide.version });
    assert.ok(changes > 0, `${guide.code} has no visible version substitution`);
    const inventory = await page.evaluate(() => {
      const pages = [...document.querySelectorAll('.page, .guide-page')];
      return pages.map((container, index) => ({
        page: index + 1,
        images: [...container.querySelectorAll('image, img')].map((image) => ({
          href: image.getAttribute('href') ?? image.getAttribute('xlink:href') ?? image.getAttribute('src'),
          width: image.getAttribute('width'), height: image.getAttribute('height'),
        })),
        text: container.textContent,
        overflow: [...container.querySelectorAll('.step, .step-card, .help-tile, .problem')].flatMap((card) => {
          const box = card.getBoundingClientRect();
          return [...card.querySelectorAll('h3, p, figcaption')].filter((node) => {
            const b = node.getBoundingClientRect();
            if (b.width === 0 || b.height === 0) return false;
            return b.left < box.left - 1 || b.right > box.right + 1 || b.top < box.top - 1 || b.bottom > box.bottom + 1;
          }).map((node) => ({text:node.textContent, card:box.toJSON(), content:node.getBoundingClientRect().toJSON()}));
        }),
        outside: [...container.querySelectorAll('svg > text')].filter((text) => {
          const b = text.getBBox(); return b.x < 0 || b.y < 0 || b.x + b.width > 210.2 || b.y + b.height > 297.2;
        }).map((text) => text.textContent),
      }));
    });
    assert.equal(inventory.length, guide.pages, `${guide.code} page count changed`);
    assert.deepEqual(inventory.flatMap((p) => p.overflow), [], `${guide.code} content escaped a card`);
    const html = path.join(root, guide.file.replace('.pdf', '.html'));
    const pdf = path.join(repoPdfOutputRoot, guide.file);
    fs.writeFileSync(html, await page.content());
    await page.pdf({ path: pdf, preferCSSPageSize: true, printBackground: true });
    run(resolveCommand('GUIDE_PDFTOPPM_PATH', 'pdftoppm'), ['-png', '-r', '144', pdf, path.join(root, guide.file.replace('.pdf', ''))]);
    const record = {
      code: guide.code, baselineVersion: guide.old, version: guide.version, pages: guide.pages,
      file: guide.file, sha256: hash(fs.readFileSync(pdf)), bytes: fs.statSync(pdf).size,
      originalGenerator: guide.script, originalSourceHash: originalHash, patchedSourceHash: hash(fs.readFileSync(localGenerator, 'utf8')),
      outputHtml: path.relative(repoRoot, html).replaceAll('\\', '/'),
      inventory: inventory.map((p) => ({ ...p, images: p.images.map(({ href, ...image }) => ({ ...image, sha256: hash(href ?? '') })) })),
    };
    fs.writeFileSync(path.join(root, 'validation.json'), `${JSON.stringify(record, null, 2)}\n`);
    console.log(JSON.stringify({ code: guide.code, version: guide.version, pages: guide.pages, file: guide.file, outside: inventory.flatMap((p) => p.outside) }));
  } finally { await browser.close(); }
}
