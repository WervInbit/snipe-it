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
const outDir = process.env.SNIPEIT_GUIDE_OUT_DIR || guideOutputDir('workflow-review-v8');
const repoPdfDir = repoPdfOutputRoot;
const chromePath = resolveChromeExecutable();
const pdftoppmPath = resolveCommand('GUIDE_PDFTOPPM_PATH', 'pdftoppm');

const sources = {
  wfEntry: evidencePath('WF-ENTRY-MOBILE-03'),
  wfNeutral: evidencePath('WF-NEUTRAL-MOBILE-03'),
  wfInstructions: evidencePath('WF-INSTRUCTIONS-MOBILE-03'),
  wfNote: evidencePath('WF-NOTE-MOBILE-03'),
  wfPhoto: evidencePath('WF-PHOTO-MOBILE-03'),
};

const imageMeta = {
  wfEntry: { width: 430, height: 1064 },
  wfNeutral: { width: 415, height: 868 },
  wfInstructions: { width: 415, height: 868 },
  wfNote: { width: 415, height: 868 },
  wfPhoto: { width: 415, height: 868 },
};

const colors = {
  ink: '#102033',
  muted: '#53657A',
  line: '#C8D5E2',
  panel: '#F8FAFC',
  workflow: '#F97316',
  workflowSoft: '#FFF7ED',
  teal: '#0F8F7B',
  tealSoft: '#ECFDF8',
  green: '#138A43',
  greenSoft: '#ECFDF3',
  red: '#E83448',
  redSoft: '#FFF1F3',
  amber: '#D97706',
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
  const ext = path.extname(file).toLowerCase();
  const mime = ext === '.jpg' || ext === '.jpeg' ? 'image/jpeg' : 'image/png';
  return `data:${mime};base64,${fs.readFileSync(file).toString('base64')}`;
}

const images = Object.fromEntries(
  Object.entries(sources).map(([key, file]) => [key, dataUri(file)]),
);

function markSvg(mark) {
  if (!mark) return '';
  const marks = Array.isArray(mark) ? mark : [mark];
  return marks.map((item) => {
    if (item.shape === 'circle') {
      return `<ellipse class="target-mark" cx="${item.x + item.w / 2}" cy="${item.y + item.h / 2}" rx="${item.w / 2}" ry="${item.h / 2}" />`;
    }
    return `<rect class="target-mark" x="${item.x}" y="${item.y}" width="${item.w}" height="${item.h}" rx="${item.radius || 7}" />`;
  }).join('');
}

function overlaySvg(overlays) {
  if (!overlays) return '';
  const items = Array.isArray(overlays) ? overlays : [overlays];
  return items.map((item) => `<g class="shot-overlay">
    <rect x="${item.x}" y="${item.y}" width="${item.w}" height="${item.h}" rx="${item.radius || 0}" fill="${escapeHtml(item.fill || '#EEF2F6')}" />
    ${item.text ? `<text x="${item.textX ?? item.x + 4}" y="${item.textY ?? item.y + item.h * .72}" font-size="${item.fontSize || 17}" font-weight="${item.fontWeight || 500}" fill="${escapeHtml(item.color || '#334155')}">${escapeHtml(item.text)}</text>` : ''}
  </g>`).join('');
}

function visual({
  image,
  label,
  caption,
  className = '',
  crop,
  mark,
  overlays,
  alt = caption,
}) {
  const meta = imageMeta[image];
  if (!meta) throw new Error(`Missing image metadata for ${image}`);
  const view = crop || { x: 0, y: 0, w: meta.width, h: meta.height };
  return `<figure class="visual ${escapeHtml(className)}">
    <span class="image-badge">${escapeHtml(label)}</span>
    <div class="shot" role="img" aria-label="${escapeHtml(alt)}">
      <svg viewBox="${view.x} ${view.y} ${view.w} ${view.h}" preserveAspectRatio="xMidYMid meet" aria-hidden="true">
        <image href="${images[image]}" x="0" y="0" width="${meta.width}" height="${meta.height}" />
        ${overlaySvg(overlays)}
        ${markSvg(mark)}
      </svg>
    </div>
    <figcaption>${escapeHtml(caption)}</figcaption>
  </figure>`;
}

function chip(family, code, label) {
  const palette = family === 'WF'
    ? { color: colors.workflow, fill: colors.workflowSoft }
    : family === 'SC'
      ? { color: colors.teal, fill: colors.tealSoft }
      : family === 'AST'
        ? { color: colors.green, fill: colors.greenSoft }
        : family === 'CMP'
          ? { color: '#C17A00', fill: '#FFF8E6' }
        : { color: colors.red, fill: colors.redSoft };
  return `<span class="guide-chip" style="--chip:${palette.color};--chip-fill:${palette.fill}">
    <b>${escapeHtml(family)}</b><span>${escapeHtml(code)} ${escapeHtml(label)}</span>
  </span>`;
}

function context({ role, needed, prerequisite }) {
  return `<section class="context">
    <div><span>Rol</span><strong>${escapeHtml(role)}</strong></div>
    <div><span>Nodig</span><strong>${escapeHtml(needed)}</strong></div>
    <div><span>Vooraf</span>${prerequisite}</div>
  </section>`;
}

function help(title, items) {
  return `<section class="help">
    <h2>${escapeHtml(title)}</h2>
    <div class="help-grid" style="--help-count:${items.length}">
      ${items.map((item) => `<div class="help-item">
        <span class="help-symbol">${escapeHtml(item.icon || '!')}</span>
        <div><strong>${escapeHtml(item.title)}</strong><p>${escapeHtml(item.body)}</p></div>
      </div>`).join('')}
    </div>
  </section>`;
}

function footer(related, sourceText = 'gecontroleerde testopnamen') {
  return `<footer>
    <div class="footer-copy">
      <span class="footer-label">Relevante gidsen</span>
      <div class="related">${related.join('')}</div>
      <div class="source">Bron: ${escapeHtml(sourceText)} | ${generatedOn} | concept voor review</div>
    </div>
    <div class="qr"><div>QR<br>volgt</div><span>Digitale gids</span></div>
  </footer>`;
}

function pageShell({
  code,
  title,
  subtitle,
  version,
  pageLabel,
  contextHtml,
  main,
  helpHtml,
  done,
  footerHtml,
  className = '',
}) {
  return `<article class="guide-page ${escapeHtml(className)}">
    <header>
      <div class="title-block">
        <span class="family-bar"></span>
        <div><h1>${escapeHtml(code)} ${escapeHtml(title)}</h1><p>${escapeHtml(subtitle)}</p></div>
      </div>
      <div class="version">${escapeHtml(version)}<br>${generatedOn}<small>${escapeHtml(pageLabel)}</small></div>
    </header>
    ${contextHtml}
    ${main}
    ${helpHtml}
    <section class="done"><strong>Klaar als</strong><span>${escapeHtml(done)}</span></section>
    ${footerHtml}
  </article>`;
}

function stepNumber(number) {
  return `<span class="step-number">${escapeHtml(number)}</span>`;
}

const breadcrumbOverlayNeutral = [
  { x: 0, y: 208, w: 415, h: 106, fill: '#EEF2F6' },
  { x: 0, y: 0, w: 0, h: 0, fill: 'none', text: 'Apparaten > HP ProBook 450 G8', textX: 14, textY: 238, fontSize: 17, fontWeight: 500 },
  { x: 0, y: 0, w: 0, h: 0, fill: 'none', text: '(INBIT-HG0421) - HP ProBook 450 G8', textX: 14, textY: 264, fontSize: 16, fontWeight: 500 },
  { x: 0, y: 0, w: 0, h: 0, fill: 'none', text: '> Tests', textX: 14, textY: 292, fontSize: 18, fontWeight: 600 },
];

function wf01Page() {
  const step1 = `<section class="step step-one">
    ${stepNumber('1')}
    <div class="step-copy">
      <h3>Open de tab Tests</h3>
      <p>Het juiste asset is al gecontroleerd met SC-01. Tik nu op het test-icoon.</p>
    </div>
    ${visual({
      image: 'wfEntry',
      label: '1A',
      caption: 'Tik op het omcirkelde test-icoon in de volledige tabrij.',
      crop: { x: 14, y: 410, w: 402, h: 104 },
      mark: { x: 264, y: 421, w: 48, h: 48, shape: 'circle' },
    })}
  </section>`;

  const step2 = `<section class="step step-two">
    ${stepNumber('2')}
    <div class="step-copy">
      <h3>Kies het workflowprofiel</h3>
      <p>Kies het afgesproken profiel. Vraag bij twijfel een supervisor.</p>
    </div>
    ${visual({
      image: 'wfEntry',
      label: '2A',
      caption: 'Selecteer hier het juiste workflowprofiel.',
      crop: { x: 16, y: 510, w: 398, h: 88 },
      mark: { x: 23, y: 548, w: 383, h: 38 },
    })}
  </section>`;

  const step3 = `<section class="step step-three">
    ${stepNumber('3')}
    <div class="step-copy">
      <h3>Start de workflow eenmaal</h3>
      <p>Tik eenmaal op <b>Nieuwe workflow starten</b>.</p>
    </div>
    ${visual({
      image: 'wfEntry',
      label: '3A',
      caption: 'De blauwe knop start een nieuwe run.',
      crop: { x: 16, y: 798, w: 398, h: 59 },
      mark: { x: 23, y: 810, w: 383, h: 37 },
    })}
    <div class="step-alternative">
      <div class="choice-divider"><span>OF</span></div>
      <h3>Doorgaan met bestaande workflow</h3>
      <p>Staat er al een juiste, onafgeronde run? Open die via <b>Bewerk</b>. Dit is een alternatief, geen vaste stap.</p>
      ${visual({
        image: 'wfEntry',
        label: '3B',
        caption: 'Vergelijk profiel en aantallen; open daarna de juiste run.',
        crop: { x: 16, y: 842, w: 398, h: 142 },
        mark: { x: 267, y: 882, w: 61, h: 31 },
      })}
    </div>
  </section>`;

  const step4 = `<section class="step step-four horizontal">
    ${stepNumber('4')}
    <div class="step-copy">
      <h3>Controleer de kaarten</h3>
      <p>Controleer de kaarttitels. De knoppen zijn nog neutraal totdat je een controle uitvoert.</p>
      <p class="next-guide">Ga verder met:<br>${chip('WF', 'WF-02', 'Workflow uitvoeren')}</p>
    </div>
    ${visual({
      image: 'wfNeutral',
      label: '4A',
      caption: 'De titel, een volledige kaart en de volgende kaart blijven herkenbaar.',
      crop: { x: 14, y: 323, w: 388, h: 304 },
    })}
  </section>`;

  const main = `<main class="wf01-main">${step1}${step2}${step3}${step4}</main>`;

  return pageShell({
    code: 'WF-01',
    title: 'Workflow starten',
    subtitle: 'Ga door met de juiste run of start een nieuwe workflow precies eenmaal',
    version: 'Draft v9',
    pageLabel: '1 van 1',
    className: 'wf01',
    contextHtml: context({
      role: 'Senior refurbisher',
      needed: 'Gecontroleerde open asset + workflowrechten',
      prerequisite: chip('SC', 'SC-01', 'Asset geopend'),
    }),
    main,
    helpHtml: help('Hulp bij workflow starten', [
      { title: 'Geen test-icoon', body: 'Controleer rechten en assetstatus.' },
      { title: 'Run onduidelijk', body: 'Stop. Vraag welke run je moet openen.' },
      { title: 'Verkeerd profiel', body: 'Start niet. Vraag een supervisor.' },
      { title: 'Geen kaarten', body: 'Start niet opnieuw; vraag hulp.' },
    ]),
    done: 'De juiste bestaande of nieuwe workflow is open en de verwachte resultaatkaarten zijn zichtbaar.',
    footerHtml: footer([
      chip('SC', 'SC-01', 'Asset openen'),
      chip('WF', 'WF-02', 'Workflow uitvoeren'),
      chip('HELP', 'HELP-01', 'Hulp'),
    ]),
  });
}

function wf02Front() {
  const step1 = `<section class="step workflow-row">
    ${stepNumber('1')}
    <div class="step-copy">
      <h3>Valideer de actieve workflow</h3>
      <p>Controleer in het kruimelpad of assettag en model bij het apparaat voor je horen.</p>
    </div>
    ${visual({
      image: 'wfNeutral',
      label: '1A',
      caption: 'Alleen het kruimelpad wordt hier gecontroleerd.',
      crop: { x: 8, y: 204, w: 399, h: 112 },
      overlays: breadcrumbOverlayNeutral,
    })}
  </section>`;

  const step2 = `<section class="step workflow-row">
    ${stepNumber('2')}
    <div class="step-copy">
      <h3>Lees de instructie</h3>
      <p>Tik op <b>Toon instructies</b>. Lees de volledige controle voordat je een resultaat kiest.</p>
    </div>
    ${visual({
      image: 'wfInstructions',
      label: '2A',
      caption: 'De volledige kaart blijft zichtbaar terwijl de instructie openstaat.',
      crop: { x: 17, y: 325, w: 378, h: 230 },
      mark: { x: 235, y: 374, w: 124, h: 25 },
    })}
  </section>`;

  const step3 = `<section class="step workflow-row">
    ${stepNumber('3')}
    <div class="step-copy">
      <h3>Voer uit en kies het resultaat</h3>
      <p>Voer de fysieke of softwarecontrole uit. Kies daarna <b>Geslaagd</b> of <b>Mislukt</b> naar waarheid.</p>
    </div>
    ${visual({
      image: 'wfNeutral',
      label: '3A',
      caption: 'De volledige kaart is nog neutraal; kies pas na uitvoering.',
      crop: { x: 17, y: 326, w: 378, h: 162 },
      mark: { x: 30, y: 411, w: 340, h: 41 },
    })}
  </section>`;

  return pageShell({
    code: 'WF-02',
    title: 'Workflow uitvoeren',
    subtitle: 'Lees de controle, voer haar uit en leg het eerlijke resultaat vast',
    version: 'Draft v10',
    pageLabel: 'Voorzijde 1 van 2',
    className: 'wf02 wf02-front',
    contextHtml: context({
      role: 'Senior refurbisher',
      needed: 'Actieve workflow + fysiek apparaat',
      prerequisite: chip('WF', 'WF-01', 'Workflow geopend'),
    }),
    main: `<main class="wf02-main">${step1}${step2}${step3}</main>`,
    helpHtml: help('Hulp tijdens de controle', [
      { title: 'Instructie onduidelijk', body: 'Stop en vraag een supervisor.' },
      { title: 'Controle niet mogelijk', body: 'Kies nog geen resultaat; vraag hulp.' },
      { title: 'Verkeerd apparaat', body: 'Sluit de run en open het juiste asset.' },
    ]),
    done: 'De controle is uitgevoerd en het juiste resultaat is zichtbaar. Ga verder op de volgende pagina.',
    footerHtml: footer([
      chip('WF', 'WF-01', 'Workflow openen'),
      chip('HELP', 'HELP-01', 'Hulp'),
    ]),
  });
}

function wf02Back() {
  const step4 = `<section class="step workflow-row">
    ${stepNumber('4')}
    <div class="step-copy">
      <h3>Voeg een notitie toe</h3>
      <p>Tik op <b>Notitie</b> bij een mislukking, uitzondering of relevante meetwaarde. Schrijf kort wat je zag.</p>
      <p class="detail-note">Voorbeeld: fout, meetwaarde en wat je al hebt gecontroleerd.</p>
    </div>
    ${visual({
      image: 'wfNote',
      label: '4A',
      caption: 'Volledige kaart met ingeklapte instructie en geopend notitieveld.',
      crop: { x: 17, y: 299, w: 378, h: 276 },
      mark: { x: 30, y: 478, w: 340, h: 78 },
    })}
  </section>`;

  const step5 = `<section class="step workflow-row">
    ${stepNumber('5')}
    <div class="step-copy">
      <h3>Voeg foto of schermafbeelding toe</h3>
      <p>Tik op <b>Foto</b> en daarna <b>Foto toevoegen</b>. Maak een foto of kies een schermafbeelding wanneer de controle bewijs vraagt.</p>
      <p class="detail-note">Controleer dat het juiste apparaat en relevante detail zichtbaar zijn.</p>
    </div>
    ${visual({
      image: 'wfPhoto',
      label: '5A',
      caption: 'Volledige kaart met ingeklapte instructie en geopend fotopaneel.',
      crop: { x: 17, y: 299, w: 378, h: 237 },
      mark: { x: 91, y: 460, w: 139, h: 25 },
    })}
  </section>`;

  const step6 = `<section class="step workflow-row summary-row">
    ${stepNumber('6')}
    <div class="step-copy">
      <h3>Herhaal en controleer afronding</h3>
      <p>Herhaal stap 2 tot en met 5 voor alle vereiste kaarten. Controleer daarna de aantallen en open de run opnieuw bij twijfel.</p>
      <p class="inline-stop">STOP bij een ontbrekend, onduidelijk of tegenstrijdig resultaat.</p>
    </div>
    ${visual({
      image: 'wfEntry',
      label: '6A',
      caption: 'Lees de volledige runregel en controleer de opgeslagen aantallen.',
      crop: { x: 16, y: 918, w: 398, h: 72 },
      mark: { x: 23, y: 929, w: 383, h: 44 },
    })}
  </section>`;

  return pageShell({
    code: 'WF-02',
    title: 'Bewijs en afronding',
    subtitle: 'Voeg uitleg of beeld toe waar nodig en controleer de opgeslagen uitkomst',
    version: 'Draft v10',
    pageLabel: 'Achterzijde 2 van 2',
    className: 'wf02 wf02-back',
    contextHtml: context({
      role: 'Senior refurbisher',
      needed: 'Dezelfde actieve workflow + apparaat',
      prerequisite: chip('WF', 'WF-02', 'Voorzijde uitgevoerd'),
    }),
    main: `<main class="wf02-main">${step4}${step5}${step6}</main>`,
    helpHtml: help('Hulp bij bewijs en opslaan', [
      { title: 'Notitie niet opgeslagen', body: 'Laat de kaart open en vraag hulp.' },
      { title: 'Foto lukt niet', body: 'Herhaal eenmaal; vraag daarna een supervisor.' },
      { title: 'Resultaat wijzigt niet', body: 'Start geen nieuwe run. Controleer dezelfde kaart.' },
    ]),
    done: 'Alle vereiste kaarten hebben een eerlijk resultaat en waar nodig een notitie of foto; de aantallen kloppen.',
    footerHtml: footer([
      chip('WF', 'WF-01', 'Workflow openen'),
      chip('CMP', 'CMP-01', 'Component plaatsen'),
      chip('AST', 'AST-04', 'Werk overdragen'),
      chip('HELP', 'HELP-01', 'Hulp'),
    ]),
  });
}

const css = `
  @page { size: A4 portrait; margin: 0; }
  * { box-sizing: border-box; }
  html, body { margin: 0; padding: 0; background: #E5E7EB; color: ${colors.ink}; font-family: Arial, Helvetica, sans-serif; }
  body { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
  .guide-page {
    --family: ${colors.workflow};
    width: 210mm;
    height: 297mm;
    padding: 12mm;
    background: #FFFFFF;
    display: grid;
    grid-template-rows: 22mm 15mm minmax(0, 1fr) auto 10mm 25mm;
    gap: 2.5mm;
    break-after: page;
    page-break-after: always;
    overflow: hidden;
  }
  .guide-page:last-child { break-after: auto; page-break-after: auto; }
  header { display: flex; justify-content: space-between; gap: 5mm; }
  .title-block { min-width: 0; display: flex; gap: 4mm; align-items: flex-start; }
  .family-bar { width: 2mm; height: 17mm; flex: 0 0 auto; background: var(--family); }
  h1 { margin: 0; font-size: 8.1mm; line-height: 1; font-weight: 900; letter-spacing: 0; white-space: nowrap; }
  .title-block p { margin: 2.1mm 0 0; color: ${colors.muted}; font-size: 3.05mm; line-height: 1.25; }
  .version {
    min-width: 34mm;
    padding: 2.2mm 3mm;
    border: .4mm solid #86EFAC;
    border-radius: 2mm;
    background: ${colors.greenSoft};
    color: #047857;
    text-align: center;
    font-size: 2.55mm;
    line-height: 1.3;
    font-weight: 900;
  }
  .version small { display: block; margin-top: .7mm; color: ${colors.muted}; font-size: 2mm; }
  .context {
    display: grid;
    grid-template-columns: .72fr 1.45fr 1.2fr;
    gap: 4mm;
    align-items: center;
    padding: 2.4mm 4mm;
    border: .4mm solid ${colors.line};
    border-radius: 1.8mm;
    background: ${colors.panel};
  }
  .context > div { min-width: 0; display: flex; flex-direction: column; gap: .7mm; }
  .context span:first-child { color: ${colors.muted}; font-size: 2.1mm; font-weight: 800; }
  .context strong { font-size: 3mm; line-height: 1.15; }
  .guide-chip {
    --chip: #64748B;
    --chip-fill: #F8FAFC;
    display: inline-flex;
    width: fit-content;
    max-width: 100%;
    min-height: 6.5mm;
    align-items: center;
    gap: 1.5mm;
    padding: .7mm 1.8mm .7mm 1mm;
    border: .4mm solid var(--chip);
    border-radius: 2mm;
    background: var(--chip-fill);
    color: var(--chip);
    font-size: 2.4mm;
    font-weight: 900;
    white-space: nowrap;
  }
  .guide-chip b {
    width: 4.7mm;
    height: 4.7mm;
    flex: 0 0 4.7mm;
    border: .35mm solid var(--chip);
    border-radius: 50%;
    background: rgba(255,255,255,.62);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1.45mm;
  }
  .step {
    position: relative;
    min-width: 0;
    min-height: 0;
    padding: 6.5mm 3.3mm 2.8mm 9mm;
    border: .4mm solid ${colors.line};
    border-radius: 2mm;
    background: #FFFFFF;
  }
  .step-number {
    position: absolute;
    left: -7mm;
    top: -6.2mm;
    width: 13mm;
    height: 13mm;
    border: 1.1mm solid var(--family);
    border-radius: 50%;
    background: rgba(255,255,255,.9);
    color: var(--family);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 4.7mm;
    line-height: 1;
    font-weight: 900;
    z-index: 5;
  }
  .step-copy h3 { margin: 0 0 1.2mm; font-size: 3.95mm; line-height: 1.08; }
  .step-copy p { margin: .7mm 0; color: #334155; font-size: 2.55mm; line-height: 1.27; }
  .step-copy .inline-stop { color: ${colors.red}; font-weight: 900; }
  .step-copy .detail-note { color: ${colors.workflow}; font-size: 2.35mm; font-weight: 800; }
  .visual-stack { min-height: 0; display: flex; flex: 1; flex-direction: column; gap: 2mm; }
  .visual-stack.compact { gap: 1.4mm; }
  .visual {
    position: relative;
    min-width: 0;
    min-height: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: .8mm;
  }
  .shot {
    position: relative;
    min-height: 0;
    border: .4mm solid ${colors.line};
    border-radius: 1.7mm;
    background: #EEF2F6;
    overflow: hidden;
  }
  .shot svg { width: 100%; height: auto; display: block; }
  .visual figcaption { margin: 0; color: #334155; font-size: 2.15mm; line-height: 1.18; }
  .image-badge {
    position: absolute;
    left: -2.5mm;
    top: -2.5mm;
    min-width: 6.8mm;
    height: 6.8mm;
    padding: 0 .7mm;
    border: .55mm solid var(--family);
    border-radius: 50%;
    background: rgba(255,255,255,.42);
    color: var(--family);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2mm;
    font-weight: 900;
    z-index: 4;
  }
  .target-mark {
    fill: none;
    stroke: ${colors.red};
    stroke-width: 2.4;
    vector-effect: non-scaling-stroke;
    pointer-events: none;
  }
  .help h2 { margin: 0 0 1.2mm; color: ${colors.muted}; font-size: 2.7mm; }
  .help-grid { display: grid; grid-template-columns: repeat(var(--help-count), 1fr); gap: 1.6mm; }
  .help-item {
    min-width: 0;
    min-height: 18mm;
    padding: 2.3mm;
    border: .4mm solid #FDBA74;
    border-radius: 1.8mm;
    background: ${colors.workflowSoft};
    display: flex;
    gap: 1.8mm;
    align-items: flex-start;
  }
  .help-symbol {
    width: 6.2mm;
    height: 6.2mm;
    flex: 0 0 6.2mm;
    border: .5mm solid #F59E0B;
    border-radius: 50%;
    color: ${colors.amber};
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.2mm;
    font-weight: 900;
  }
  .help-item strong { font-size: 2.55mm; line-height: 1.12; }
  .help-item p { margin: 1.2mm 0 0; color: #334155; font-size: 2.1mm; line-height: 1.2; }
  .done {
    display: flex;
    align-items: center;
    gap: 6mm;
    padding: 1.7mm 4mm;
    border: .4mm solid #6EE7A0;
    border-radius: 1.8mm;
    background: ${colors.greenSoft};
    color: #047857;
  }
  .done strong { min-width: 20mm; font-size: 3mm; }
  .done span { font-size: 2.5mm; line-height: 1.2; }
  footer { min-height: 0; display: grid; grid-template-columns: minmax(0,1fr) 22mm; gap: 4mm; align-items: end; }
  .footer-copy { min-width: 0; display: flex; flex-direction: column; gap: 1.2mm; }
  .footer-label { color: ${colors.muted}; font-size: 2.15mm; font-weight: 800; }
  .related { display: flex; flex-wrap: wrap; gap: 1.2mm; }
  .source { color: #64748B; font-size: 1.75mm; white-space: nowrap; }
  .qr { width: 22mm; display: flex; flex-direction: column; align-items: center; gap: .8mm; }
  .qr > div {
    width: 22mm;
    height: 22mm;
    border: .5mm solid ${colors.ink};
    border-radius: 1mm;
    background: ${colors.panel};
    color: #64748B;
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    font-size: 2.9mm;
    font-weight: 900;
    line-height: 1.05;
  }
  .qr span { font-size: 1.9mm; font-weight: 800; }

  .wf01-main {
    min-height: 0;
    display: grid;
    grid-template-columns: 1fr 1fr;
    grid-template-rows: .55fr 1.45fr;
    gap: 2.2mm;
    padding-top: 1.5mm;
  }
  .wf01-main .step-one { grid-column: 1; grid-row: 1; display: flex; flex-direction: column; justify-content: center; gap: 1.8mm; }
  .wf01-main .step-two { grid-column: 2; grid-row: 1; display: flex; flex-direction: column; justify-content: center; gap: 1.2mm; }
  .wf01-main .step-three { grid-column: 1; grid-row: 2; display: flex; flex-direction: column; justify-content: center; gap: 1.3mm; }
  .wf01-main .step-four { grid-column: 2; grid-row: 2; display: flex; flex-direction: column; justify-content: center; gap: 1.5mm; }
  .step-alternative {
    margin-top: 1mm;
    padding-top: 1.7mm;
  }
  .choice-divider { display: flex; align-items: center; gap: 2mm; margin: 0 0 1.6mm; color: ${colors.workflow}; }
  .choice-divider::before, .choice-divider::after { content: ''; height: .35mm; flex: 1; background: #FDBA74; }
  .choice-divider span { font-size: 2.35mm; font-weight: 900; }
  .step-alternative h3 { margin: 0 0 1.1mm; font-size: 3.95mm; line-height: 1.08; }
  .step-alternative > p { margin: .8mm 0 1.4mm; color: #334155; font-size: 2.35mm; line-height: 1.22; }
  .branch-next { color: ${colors.workflow} !important; font-weight: 900; }
  .horizontal { display: flex; flex-direction: column; }
  .next-guide { display: block; color: ${colors.workflow} !important; font-weight: 800; }
  .next-guide .guide-chip { margin-top: 1mm; transform: scale(.9); transform-origin: left center; }

  .wf02-main {
    min-height: 0;
    display: grid;
    grid-template-rows: repeat(3, minmax(0, 1fr));
    gap: 2.3mm;
    padding-top: 1.5mm;
  }
  .wf02-front .wf02-main { grid-template-rows: .68fr 1.25fr 1fr; }
  .wf02-back .wf02-main { grid-template-rows: 1.28fr 1.12fr .72fr; }
  .workflow-row {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 78mm;
    gap: 4mm;
    align-items: center;
  }
  .workflow-row .step-copy { align-self: center; }
  .summary-row { background: #FFFCF6; }
  .wf02 .help-item { min-height: 18mm; }
  .wf02 .guide-chip { font-size: 2.25mm; }
  @media print { html, body { background: #FFFFFF; } }
`;

function htmlDocument(pages, title) {
  return `<!doctype html>
  <html lang="nl">
    <head>
      <meta charset="utf-8">
      <title>${escapeHtml(title)}</title>
      <style>${css}</style>
    </head>
    <body>${pages.join('')}</body>
  </html>`;
}

function run(command, args, label) {
  const result = spawnSync(command, args, { encoding: 'utf8', stdio: ['ignore', 'pipe', 'pipe'] });
  if (result.status !== 0) {
    throw new Error(`${label} failed (${result.status}):\n${result.stdout}\n${result.stderr}`);
  }
}

function renderPdf(htmlPath, pdfPath) {
  const profileDir = fs.mkdtempSync(path.join(os.tmpdir(), 'workflow-guide-chrome-'));
  try {
    run(chromePath, [
      '--headless=new',
      '--disable-gpu',
      '--no-sandbox',
      '--allow-file-access-from-files',
      `--user-data-dir=${profileDir}`,
      '--no-pdf-header-footer',
      `--print-to-pdf=${pdfPath}`,
      pathToFileURL(htmlPath).href,
    ], `Chrome PDF render for ${path.basename(htmlPath)}`);
  } finally {
    fs.rmSync(profileDir, { recursive: true, force: true });
  }
}

function renderPngs(pdfPath, outputBase, expectedPages) {
  run(pdftoppmPath, ['-png', '-r', '144', pdfPath, outputBase], `PNG render for ${path.basename(pdfPath)}`);
  const pngs = [];
  for (let page = 1; page <= expectedPages; page += 1) {
    const generated = `${outputBase}-${page}.png`;
    const finalName = expectedPages === 1
      ? `${outputBase}-proof.png`
      : `${outputBase}-page-${page}-proof.png`;
    fs.rmSync(finalName, { force: true });
    fs.renameSync(generated, finalName);
    pngs.push(finalName);
  }
  return pngs;
}

function writeGuide(slug, pages) {
  const htmlPath = path.join(outDir, `${slug}.html`);
  const pdfPath = path.join(outDir, `${slug}-proof.pdf`);
  fs.writeFileSync(htmlPath, htmlDocument(pages, slug), 'utf8');
  renderPdf(htmlPath, pdfPath);
  const pngs = renderPngs(pdfPath, path.join(outDir, slug), pages.length);
  return { slug, pages: pages.length, html: htmlPath, pdf: pdfPath, pngs };
}

assertInputs();
fs.mkdirSync(outDir, { recursive: true });
fs.mkdirSync(repoPdfDir, { recursive: true });

const wf01 = wf01Page();
const wf02Pages = [wf02Front(), wf02Back()];
const outputs = [
  writeGuide('WF-01-start-workflow-v9-draft', [wf01]),
  writeGuide('WF-02-complete-workflow-v10-draft', wf02Pages),
];

const combinedHtml = path.join(outDir, 'workflow-guides-review-batch-v8-2026-08-04.html');
const combinedPdf = path.join(outDir, 'workflow-guides-review-batch-v8-2026-08-04.pdf');
fs.writeFileSync(combinedHtml, htmlDocument([wf01, ...wf02Pages], 'Workflow guides review batch v8'), 'utf8');
renderPdf(combinedHtml, combinedPdf);
const combinedPngs = renderPngs(
  combinedPdf,
  path.join(outDir, 'workflow-batch-v8-render'),
  3,
);
fs.copyFileSync(combinedPdf, path.join(repoPdfDir, path.basename(combinedPdf)));

const manifest = {
  generatedAt: new Date().toISOString(),
  operatorFacingUrl: 'https://snipe.inbit/',
  captureEnvironment: 'Controlled development environment',
  outputDirectory: outDir,
  outputs,
  combined: { html: combinedHtml, pdf: combinedPdf, pngs: combinedPngs },
  sourceFiles: sources,
  reviewNotes: [
    'WF-01 relies on SC-01 for asset validation and uses one clear Tests-tab image in step 1.',
    'The Tests icon, workflow profile, existing-run action, and evidence controls use thin red target marks.',
    'The orange workflow attention banner is excluded from all instructional crops.',
    'WF-01 presents Doorgaan met bestaande workflow inside step 3 after an OF divider and uses the same heading scale as the primary route.',
    'WF-02 is intentionally two-sided and uses neutral live cards before the result-selection step.',
    'WF-02 step 1 uses a complete reconstructed breadcrumb context instead of a chopped source crop.',
    'WF-02 instruction, result, note-entry, and photo-action targets use measured source-pixel control bounds.',
    'WF-02 4A keeps the native yellow Notitie active state and places the red target on the note-entry field instead of duplicating the tab outline.',
    'WF-02 uses Valideer for the opening check and refers to the volgende pagina in the front-page completion block.',
    'Non-critical stop warnings are handled by the help section instead of repeated in steps 2 and 3.',
    'All screenshot crops and target marks use source-pixel coordinates inside SVG viewboxes.',
    'The controlled DEMO-001 capture label is consistently presented as synthetic example INBIT-HG0421.',
    'One blank Standard Diagnostics run was created on the controlled development asset for these captures.',
  ],
  remainingEvidenceGap: 'A device-native camera or file-picker state after Foto toevoegen is not shown; the live in-app Foto and Foto toevoegen controls are shown.',
};
fs.writeFileSync(path.join(outDir, 'generation-manifest.json'), `${JSON.stringify(manifest, null, 2)}\n`, 'utf8');

const summary = `# Workflow Guide Review Batch V8

Generated: ${generatedOn}

- WF-01: Draft v9, one page.
- WF-02: Draft v10, intentionally two-sided.
- Combined review PDF: ${combinedPdf}
- Operator-facing URL remains https://snipe.inbit/
- Captures came from a controlled development environment and contain no development URL.
- One blank Standard Diagnostics run was created on the controlled development asset for capture.

## Deliberate Changes

- WF-01 step 1 only shows the Tests tab; prior asset validation remains owned by SC-01.
- The Tests icon, workflow-profile selector, and action controls use crop-relative target marks.
- Starting a new workflow remains the numbered default route.
- \`Doorgaan met bestaande workflow\` follows an \`OF\` divider inside step 3 and uses normal step-heading hierarchy.
- WF-02 uses a complete breadcrumb context and source-pixel-centered action targets on both pages.
- WF-02 step 1 uses \`Valideer\`; the completion block directs operators to the \`volgende pagina\`.
- Duplicate non-critical stop warnings were removed from WF-02 steps 2 and 3.
- WF-02 uses full neutral cards for instructions, results, notes, and photos.
- Note and photo evidence have separate visual steps.
- WF-02 4A uses the native yellow Notitie state for the selected section and a red target around the note-entry field.
- The visible example asset tag is consistently anonymized as \`INBIT-HG0421\`.

## Remaining Review Point

- The in-app Foto panel and Foto toevoegen action are shown. A device-native camera or file-picker screen is not included because that interface varies by device and has not been captured as controlled evidence.
`;
fs.writeFileSync(path.join(outDir, 'workflow-guide-review-summary.md'), summary, 'utf8');

console.log(JSON.stringify({ outDir, combinedPdf, outputs }, null, 2));
