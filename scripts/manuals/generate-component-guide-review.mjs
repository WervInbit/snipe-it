import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { pathToFileURL } from 'node:url';
import { spawnSync } from 'node:child_process';
import {
  evidencePath,
  guideOutputDir,
  repoPdfOutputRoot,
  resolveChromeExecutable,
  resolveCommand,
} from './lib/guide-paths.mjs';

const generatedOn = '2026-08-04';
const outDir = process.env.SNIPEIT_GUIDE_OUT_DIR || guideOutputDir('CMP-01-v4');
const repoPdfDir = repoPdfOutputRoot;
const chromePath = resolveChromeExecutable();
const pdftoppmPath = resolveCommand('GUIDE_PDFTOPPM_PATH', 'pdftoppm');

const slug = 'CMP-01-install-existing-v4-draft';
const sources = {
  componentTab: evidencePath('CMP-INSTALL-ENTRY-MOBILE-02'),
  componentForm: evidencePath('CMP-INSTALL-SELECTED-MOBILE-02'),
  installedRow: evidencePath('CMP-INSTALL-RESULT-MOBILE-02'),
};
const imageMeta = {
  componentTab: { width: 415, height: 899 },
  componentForm: { width: 415, height: 899 },
  installedRow: { width: 415, height: 932 },
};

const colors = {
  ink: '#102033',
  muted: '#53657A',
  line: '#C8D5E2',
  panel: '#F8FAFC',
  component: '#C17A00',
  componentSoft: '#FFF8E6',
  teal: '#0F8F7B',
  tealSoft: '#ECFDF8',
  green: '#138A43',
  greenSoft: '#ECFDF3',
  red: '#E83448',
  redSoft: '#FFF1F3',
};

function assertInputs() {
  for (const [name, file] of Object.entries(sources)) {
    if (!fs.existsSync(file)) throw new Error(`Missing ${name} source: ${file}`);
  }
}

function escapeHtml(value) {
  return String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

function dataUri(file) {
  return `data:image/png;base64,${fs.readFileSync(file).toString('base64')}`;
}

const images = Object.fromEntries(
  Object.entries(sources).map(([key, file]) => [key, dataUri(file)]),
);

function targetMarks(items) {
  return items.map((item) => item.shape === 'circle'
    ? `<ellipse class="target" cx="${item.x + item.w / 2}" cy="${item.y + item.h / 2}" rx="${item.w / 2}" ry="${item.h / 2}" />`
    : `<rect class="target" x="${item.x}" y="${item.y}" width="${item.w}" height="${item.h}" rx="${item.radius || 6}" />`).join('');
}

function visual({ image, label, caption, crop, marks, className = '' }) {
  const meta = imageMeta[image];
  return `<figure class="visual ${escapeHtml(className)}">
    <span class="image-badge">${escapeHtml(label)}</span>
    <div class="shot">
      <svg viewBox="${crop.x} ${crop.y} ${crop.w} ${crop.h}" preserveAspectRatio="xMidYMid meet" aria-hidden="true">
        <image href="${images[image]}" x="0" y="0" width="${meta.width}" height="${meta.height}" />
        ${targetMarks(marks)}
      </svg>
    </div>
    <figcaption>${escapeHtml(caption)}</figcaption>
  </figure>`;
}

function guideChip(family, code, label) {
  const palette = family === 'SC'
    ? { color: colors.teal, fill: colors.tealSoft }
    : family === 'CMP'
      ? { color: colors.component, fill: colors.componentSoft }
      : { color: colors.red, fill: colors.redSoft };
  return `<span class="guide-chip" style="--chip:${palette.color};--chip-fill:${palette.fill}">
    <b>${escapeHtml(family)}</b><span>${escapeHtml(code)} ${escapeHtml(label)}</span>
  </span>`;
}

function step(number, title, body, visualHtml, stop = '') {
  return `<section class="step step-${number}">
    <span class="step-number">${escapeHtml(number)}</span>
    <div class="step-copy">
      <h2>${escapeHtml(title)}</h2>
      ${body.map((line) => `<p>${line}</p>`).join('')}
      ${stop ? `<p class="inline-stop">${escapeHtml(stop)}</p>` : ''}
    </div>
    ${visualHtml}
  </section>`;
}

const steps = [
  step(
    '1',
    'Open Componenten',
    ['Open het gecontroleerde asset. Tik op het component-icoon en daarna op <b>Add / Install Component</b>.'],
    visual({
      image: 'componentTab',
      label: '1A',
      caption: 'Open eerst Componenten en daarna Add / Install Component.',
      crop: { x: 12, y: 214, w: 390, h: 168 },
      marks: [
        { x: 139, y: 224, w: 42, h: 42, shape: 'circle' },
        { x: 21, y: 321, w: 358, h: 38 },
      ],
    }),
  ),
  step(
    '2',
    'Kies hetzelfde onderdeel',
    ['Kies het onderdeel uit je tray of opslag. Vergelijk bron, componenttag en naam met het fysieke onderdeel.'],
    visual({
      image: 'componentForm',
      label: '2A',
      caption: 'De bron, componenttag en naam staan samen in de keuze.',
      crop: { x: 12, y: 455, w: 390, h: 225 },
      marks: [{ x: 22, y: 532, w: 356, h: 38 }],
    }),
    'STOP als tag, naam of fysiek onderdeel niet overeenkomt.',
  ),
  step(
    '3',
    'Plaats en installeer',
    ['Plaats het onderdeel fysiek. Tik daarna eenmaal op <b>Install</b>.'],
    visual({
      image: 'componentForm',
      label: '3A',
      caption: 'Bevestig de digitale koppeling pas na de fysieke plaatsing.',
      crop: { x: 12, y: 470, w: 390, h: 205 },
      marks: [{ x: 22, y: 620, w: 64, h: 36 }],
    }),
  ),
  step(
    '4',
    'Controleer de koppeling',
    ['Controleer op het asset of <b>Tracked</b>, dezelfde componenttag en hetzelfde serienummer zichtbaar zijn.'],
    visual({
      image: 'installedRow',
      label: '4A',
      caption: 'Het gevolgde onderdeel staat met dezelfde tag en hetzelfde serienummer op het asset.',
      crop: { x: 42, y: 222, w: 365, h: 211 },
      marks: [
        { x: 58, y: 237, w: 65, h: 28 },
        { x: 55, y: 358, w: 341, h: 23 },
      ],
    }),
    'STOP als het record of het fysieke onderdeel niet klopt.',
  ),
].join('');

const helpItems = [
  ['Niet zichtbaar', 'Controleer eigen tray of opslag. Maak geen duplicaat.'],
  ['Tag wijkt af', 'Stop en pak het juiste onderdeel.'],
  ['Conditiewaarschuwing', 'Niet bevestigen; vraag een supervisor.'],
  ['Geen rechten', 'Vraag een bevoegde supervisor.'],
];

const html = `<!doctype html>
<html lang="nl">
<head>
  <meta charset="utf-8">
  <title>CMP-01 Bestaand component plaatsen</title>
  <style>
    @page { size: A4 portrait; margin: 0; }
    * { box-sizing: border-box; }
    html, body { margin: 0; padding: 0; background: #E5E7EB; color: ${colors.ink}; font-family: Arial, Helvetica, sans-serif; }
    body { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
    .page {
      width: 210mm; height: 297mm; margin: 0 auto; padding: 12mm;
      background: #FFF; overflow: hidden; display: grid;
      grid-template-rows: 25mm 14mm 161mm 21mm 11mm 26mm;
      gap: 2mm; --family: ${colors.component};
    }
    header { display: grid; grid-template-columns: minmax(0,1fr) 37mm; gap: 5mm; align-items: start; }
    .title { border-left: 2.4mm solid var(--family); padding-left: 4mm; }
    h1 { margin: 0; font-size: 8mm; line-height: 1.02; letter-spacing: 0; }
    .subtitle { margin: 2mm 0 0; color: ${colors.muted}; font-size: 3mm; }
    .version { height: 22mm; border: .4mm solid #6EE7A0; border-radius: 2mm; background: ${colors.greenSoft}; display: flex; flex-direction: column; align-items: center; justify-content: center; color: #047857; font-weight: 900; font-size: 2.7mm; line-height: 1.25; }
    .version small { color: ${colors.muted}; font-size: 2mm; }
    .context { display: grid; grid-template-columns: 1fr 1.55fr 1.35fr; gap: 4mm; padding: 2.2mm 4mm; border: .4mm solid ${colors.line}; border-radius: 2mm; background: ${colors.panel}; align-items: center; }
    .context > div { min-width: 0; }
    .context span.label { display: block; margin-bottom: .6mm; color: ${colors.muted}; font-size: 2.1mm; font-weight: 800; }
    .context strong { display: block; font-size: 2.75mm; line-height: 1.1; }
    .steps { display: grid; grid-template-rows: 36mm 42mm 34mm 43mm; gap: 2mm; padding-top: 1mm; }
    .step { position: relative; display: grid; grid-template-columns: minmax(0,1fr) 83mm; gap: 4mm; align-items: center; padding: 3mm 3mm 2.2mm 10mm; border: .4mm solid ${colors.line}; border-radius: 2mm; background: #FFF; min-height: 0; }
    .step-number { position: absolute; left: -7mm; top: -6mm; width: 13mm; height: 13mm; border: 1.1mm solid var(--family); border-radius: 50%; background: rgba(255,255,255,.9); color: var(--family); display: flex; align-items: center; justify-content: center; font-size: 4.7mm; font-weight: 900; z-index: 5; }
    .step-copy { align-self: center; min-width: 0; }
    .step-copy h2 { margin: 0 0 1.2mm; font-size: 4mm; line-height: 1.08; }
    .step-copy p { margin: .7mm 0; color: #334155; font-size: 2.55mm; line-height: 1.27; }
    .step-copy .inline-stop { color: ${colors.red}; font-weight: 900; }
    .visual { position: relative; min-width: 0; min-height: 0; margin: 0; display: flex; flex-direction: column; gap: .6mm; }
    .shot { min-height: 0; height: 27mm; border: .4mm solid ${colors.line}; border-radius: 1.7mm; background: #EEF2F6; overflow: hidden; }
    .step-2 .shot { height: 32mm; }
    .step-3 .shot { height: 25mm; }
    .step-4 .shot { height: 33mm; }
    .shot svg { width: 100%; height: 100%; display: block; }
    .visual figcaption { margin: 0; color: #334155; font-size: 2mm; line-height: 1.15; }
    .image-badge { position: absolute; left: -2.4mm; top: -2.4mm; min-width: 6.5mm; height: 6.5mm; padding: 0 .6mm; border: .5mm solid var(--family); border-radius: 50%; background: rgba(255,255,255,.45); color: var(--family); display: flex; align-items: center; justify-content: center; font-size: 1.9mm; font-weight: 900; z-index: 4; }
    .target { fill: none; stroke: ${colors.red}; stroke-width: 2.4; vector-effect: non-scaling-stroke; }
    .help h2 { margin: 0 0 1mm; color: ${colors.muted}; font-size: 2.6mm; }
    .help-grid { display: grid; grid-template-columns: repeat(4,1fr); gap: 1.5mm; }
    .help-item { min-width: 0; height: 16.5mm; padding: 2mm; border: .4mm solid #FDBA74; border-radius: 1.7mm; background: ${colors.componentSoft}; display: grid; grid-template-columns: 5.5mm minmax(0,1fr); gap: 1.5mm; }
    .help-icon { width: 5.5mm; height: 5.5mm; border: .45mm solid #F59E0B; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #B45309; font-size: 2.1mm; font-weight: 900; }
    .help-item strong { display: block; font-size: 2.3mm; line-height: 1.1; }
    .help-item p { margin: 1mm 0 0; color: #334155; font-size: 1.9mm; line-height: 1.18; }
    .done { display: flex; align-items: center; gap: 6mm; padding: 1.7mm 4mm; border: .4mm solid #6EE7A0; border-radius: 1.8mm; background: ${colors.greenSoft}; color: #047857; }
    .done strong { min-width: 20mm; font-size: 3mm; }
    .done span { font-size: 2.45mm; }
    footer { display: grid; grid-template-columns: minmax(0,1fr) 22mm; gap: 4mm; align-items: end; }
    .footer-copy { min-width: 0; display: flex; flex-direction: column; gap: 1mm; }
    .footer-label { color: ${colors.muted}; font-size: 2.1mm; font-weight: 800; }
    .related { display: flex; flex-wrap: wrap; gap: 1.1mm; }
    .guide-chip { display: inline-flex; align-items: center; gap: 1.2mm; padding: .9mm 2mm .9mm 1mm; border: .35mm solid var(--chip); border-radius: 2.2mm; background: var(--chip-fill); color: var(--chip); font-size: 2.35mm; font-weight: 800; white-space: nowrap; }
    .guide-chip b { width: 4.6mm; height: 4.6mm; border: .35mm solid var(--chip); border-radius: 50%; background: rgba(255,255,255,.6); display: inline-flex; align-items: center; justify-content: center; font-size: 1.4mm; }
    .source { color: #64748B; font-size: 1.7mm; }
    .qr { width: 22mm; display: flex; flex-direction: column; align-items: center; gap: .7mm; }
    .qr div { width: 22mm; height: 22mm; border: .5mm solid ${colors.ink}; border-radius: 1mm; background: ${colors.panel}; color: #64748B; display: flex; align-items: center; justify-content: center; text-align: center; font-size: 2.8mm; font-weight: 900; line-height: 1.05; }
    .qr span { font-size: 1.8mm; font-weight: 800; }
  </style>
</head>
<body>
  <article class="page">
    <header>
      <div class="title"><h1>CMP-01 Bestaand component plaatsen</h1><p class="subtitle">Koppel een gevolgd onderdeel uit tray of opslag aan het juiste asset</p></div>
      <div class="version">Draft v4<span>${generatedOn}</span><small>1 van 1</small></div>
    </header>
    <section class="context">
      <div><span class="label">Rol</span><strong>Bevoegde refurbisher</strong></div>
      <div><span class="label">Nodig</span><strong>Fysiek onderdeel met componenttag</strong></div>
      <div><span class="label">Vooraf</span>${guideChip('SC', 'SC-01', 'Asset geopend')}</div>
    </section>
    <main class="steps">${steps}</main>
    <section class="help"><h2>Hulp bij component plaatsen</h2><div class="help-grid">
      ${helpItems.map(([title, body]) => `<div class="help-item"><span class="help-icon">!</span><div><strong>${escapeHtml(title)}</strong><p>${escapeHtml(body)}</p></div></div>`).join('')}
    </div></section>
    <section class="done"><strong>Klaar als</strong><span>Het fysieke onderdeel is geplaatst en dezelfde componenttag en hetzelfde serienummer staan op het juiste asset.</span></section>
    <footer>
      <div class="footer-copy"><span class="footer-label">Relevante gidsen</span><div class="related">
        ${guideChip('SC', 'SC-01', 'Asset openen')}
        ${guideChip('CMP', 'CMP-02', 'Nieuw plaatsen')}
        ${guideChip('CMP', 'CMP-04', 'Naar tray')}
        ${guideChip('HELP', 'HELP-01', 'Hulp')}
      </div><div class="source">Bron: gecontroleerde testopnamen | ${generatedOn} | concept voor review</div></div>
      <div class="qr"><div>QR<br>volgt</div><span>Digitale gids</span></div>
    </footer>
  </article>
</body>
</html>`;

function run(command, args, label) {
  const result = spawnSync(command, args, { encoding: 'utf8', stdio: ['ignore', 'pipe', 'pipe'] });
  if (result.status !== 0) throw new Error(`${label} failed (${result.status}):\n${result.stdout}\n${result.stderr}`);
}

assertInputs();
fs.mkdirSync(outDir, { recursive: true });
fs.mkdirSync(repoPdfDir, { recursive: true });

const htmlPath = path.join(outDir, `${slug}.html`);
const pdfPath = path.join(outDir, `${slug}-proof.pdf`);
const pngBase = path.join(outDir, slug);
const pngPath = `${pngBase}-proof.png`;
fs.writeFileSync(htmlPath, html, 'utf8');

const profileDir = fs.mkdtempSync(path.join(os.tmpdir(), 'component-guide-chrome-'));
try {
  run(chromePath, [
    '--headless=new', '--disable-gpu', '--no-sandbox', '--allow-file-access-from-files',
    `--user-data-dir=${profileDir}`, '--no-pdf-header-footer', `--print-to-pdf=${pdfPath}`,
    pathToFileURL(htmlPath).href,
  ], 'Chrome PDF render');
} finally {
  fs.rmSync(profileDir, { recursive: true, force: true });
}

run(pdftoppmPath, ['-png', '-singlefile', '-r', '144', pdfPath, pngBase], 'PNG proof render');
fs.rmSync(pngPath, { force: true });
fs.renameSync(`${pngBase}.png`, pngPath);
const repoPdfPath = path.join(repoPdfDir, `${slug}.pdf`);
fs.copyFileSync(pdfPath, repoPdfPath);

const manifest = {
  generatedAt: new Date().toISOString(),
  guide: 'CMP-01',
  version: 'Draft v4',
  operatorFacingUrl: 'https://snipe.inbit/',
  captureEnvironment: 'Controlled development environment',
  testRecord: { asset: 'DEMO-001', componentTag: 'INBIT-C-UW4626', serial: 'CMP01-RAM-0001' },
  sources,
  outputs: { html: htmlPath, pdf: pdfPath, png: pngPath, repoPdf: repoPdfPath },
  reviewNotes: [
    'The previous five-step placeholder flow is replaced by the actual four-step interface flow.',
    'Selection and installation reuse one real screenshot with separate image labels and target marks.',
    'The component was returned to the controlled asset after capture.',
    'The operator-facing guide contains no development URL or DEMO-001 label.',
  ],
};
fs.writeFileSync(path.join(outDir, 'generation-manifest.json'), `${JSON.stringify(manifest, null, 2)}\n`, 'utf8');
fs.writeFileSync(path.join(outDir, 'CMP-01-review-summary.md'), `# CMP-01 Review Draft v4\n\n- Four actual steps; no placeholder blocks.\n- Target marks use measured pixel bounds and symmetric control padding.\n- The final identity target sits below the tag and serial-number headings.\n- Controlled component: INBIT-C-UW4626 / CMP01-RAM-0001.\n- The component was installed back into the controlled asset after capture.\n- Exact component role/permissions remain a review point before approval.\n`, 'utf8');

console.log(JSON.stringify({ outDir, pdfPath, pngPath, repoPdfPath }, null, 2));
