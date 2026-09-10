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

const generatedOn = process.env.SNIPEIT_GUIDE_DATE || '2026-08-25';
const guideFilter = process.env.SNIPEIT_GUIDE_FILTER?.trim().toUpperCase() || null;
const cmp02Version = process.env.SNIPEIT_CMP02_VERSION || '4';
const cmp04Version = process.env.SNIPEIT_CMP04_VERSION || '6';
const outDir = process.env.SNIPEIT_GUIDE_OUT_DIR || guideOutputDir('component-followup-v2');
const repoPdfDir = repoPdfOutputRoot;
const chromePath = resolveChromeExecutable();
const pdftoppmPath = resolveCommand('GUIDE_PDFTOPPM_PATH', 'pdftoppm');
const pythonPath = resolveCommand('GUIDE_PYTHON_PATH', process.platform === 'win32' ? ['python', 'py'] : ['python3', 'python']);

const sources = {
  componentTab: evidencePath('CMP-INSTALL-ENTRY-MOBILE-02'),
  newEntry: evidencePath('CMP-NEW-ENTRY-MOBILE-03'),
  definitionForm: evidencePath('CMP-NEW-DEFINITION-MOBILE-03'),
  customForm: evidencePath('CMP-NEW-CUSTOM-MOBILE-03'),
  installedRow: evidencePath('CMP-NEW-INSTALLED-MOBILE-03'),
  trayModal: evidencePath('CMP-TRAY-CONFIRM-MOBILE-03'),
  trayDetail: evidencePath('CMP-TRAY-RESULT-MOBILE-03'),
};

const imageMeta = {
  componentTab: { width: 415, height: 899 },
  newEntry: { width: 400, height: 1217 },
  definitionForm: { width: 465, height: 930 },
  customForm: { width: 480, height: 960 },
  installedRow: { width: 400, height: 5728 },
  trayModal: { width: 415, height: 932 },
  trayDetail: { width: 400, height: 898 },
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
  workflow: '#F97316',
  workflowSoft: '#FFF7ED',
  access: '#2563EB',
  accessSoft: '#EFF6FF',
  catalog: '#7A4E9D',
  catalogSoft: '#F7F1FB',
  help: '#E83448',
  helpSoft: '#FFF1F3',
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
  const mime = path.extname(file).toLowerCase() === '.png' ? 'image/png' : 'image/jpeg';
  return `data:${mime};base64,${fs.readFileSync(file).toString('base64')}`;
}

const images = Object.fromEntries(Object.entries(sources).map(([key, file]) => [key, dataUri(file)]));

function targetMarks(items = []) {
  return items.map((item) => item.shape === 'circle'
    ? `<ellipse class="target" cx="${item.x + item.w / 2}" cy="${item.y + item.h / 2}" rx="${item.w / 2}" ry="${item.h / 2}" />`
    : `<rect class="target" x="${item.x}" y="${item.y}" width="${item.w}" height="${item.h}" rx="${item.radius || 6}" />`).join('');
}

function visual({ image, label, caption, crop, marks = [], className = '' }) {
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

function chip(family, code, label) {
  const palette = family === 'SC'
    ? { color: colors.teal, fill: colors.tealSoft }
    : family === 'CMP'
      ? { color: colors.component, fill: colors.componentSoft }
      : family === 'AST'
        ? { color: colors.green, fill: colors.greenSoft }
      : family === 'WF'
        ? { color: colors.workflow, fill: colors.workflowSoft }
        : family === 'AC'
          ? { color: colors.access, fill: colors.accessSoft }
          : family === 'CAT'
            ? { color: colors.catalog, fill: colors.catalogSoft }
          : { color: colors.help, fill: colors.helpSoft };
  return `<span class="guide-chip" style="--chip:${palette.color};--chip-fill:${palette.fill}">
    <b>${escapeHtml(family)}</b><span>${escapeHtml(code)} ${escapeHtml(label)}</span>
  </span>`;
}

function step(number, title, body, visualHtml, stop = '', className = '') {
  return `<section class="step step-${number} ${escapeHtml(className)}">
    <span class="step-number">${escapeHtml(number)}</span>
    <div class="step-copy">
      <h2>${escapeHtml(title)}</h2>
      ${body.map((line) => `<p>${line}</p>`).join('')}
      ${stop ? `<p class="inline-stop">${escapeHtml(stop)}</p>` : ''}
    </div>
    <div class="step-visuals">${visualHtml}</div>
  </section>`;
}

function header(code, title, subtitle, version, familyColor) {
  return `<header style="--family:${familyColor}">
    <div class="title"><h1>${escapeHtml(code)} ${escapeHtml(title)}</h1><p class="subtitle">${escapeHtml(subtitle)}</p></div>
    <div class="version">${escapeHtml(version)}<span>${generatedOn}</span><small>1 van 1</small></div>
  </header>`;
}

function context(role, needed, prerequisite) {
  return `<section class="context">
    <div><span class="label">Rol</span><strong>${escapeHtml(role)}</strong></div>
    <div><span class="label">Nodig</span><strong>${escapeHtml(needed)}</strong></div>
    <div><span class="label">Vooraf</span>${prerequisite}</div>
  </section>`;
}

function helpStrip(title, items) {
  return `<section class="help"><h2>${escapeHtml(title)}</h2><div class="help-grid help-${items.length}">
    ${items.map((item) => {
      const normalized = Array.isArray(item) ? { title: item[0], body: item[1] } : item;
      return `<div class="help-item ${normalized.ref ? 'help-reference' : ''}"><span class="help-icon">!</span><div><strong>${escapeHtml(normalized.title)}</strong><p>${escapeHtml(normalized.body)}</p>${normalized.ref ? chip(normalized.ref.family, normalized.ref.code, normalized.ref.label) : ''}</div></div>`;
    }).join('')}
  </div></section>`;
}

function done(text) {
  return `<section class="done"><strong>Klaar als</strong><span>${escapeHtml(text)}</span></section>`;
}

function footer(related) {
  return `<footer><div class="footer-copy"><span class="footer-label">Relevante gidsen</span><div class="related">${related.join('')}</div>
    <div class="source">Bron: gecontroleerde testopnamen | ${generatedOn} | concept voor review</div></div>
    <div class="qr"><div>QR<br>volgt</div><span>Digitale gids</span></div></footer>`;
}

const sharedCss = `
  @page { size: A4 portrait; margin: 0; }
  * { box-sizing: border-box; }
  html, body { margin: 0; padding: 0; background: #E5E7EB; color: ${colors.ink}; font-family: Arial, Helvetica, sans-serif; }
  body { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
  .page { width: 210mm; height: 297mm; margin: 0 auto; padding: 12mm; background: #FFF; overflow: hidden; display: grid; gap: 2mm; --family:${colors.component}; }
  header { display: grid; grid-template-columns: minmax(0,1fr) 37mm; gap: 5mm; align-items: start; }
  .title { border-left: 2.4mm solid var(--family); padding-left: 4mm; }
  h1 { margin: 0; font-size: 7.2mm; line-height: 1.02; letter-spacing: 0; }
  .subtitle { margin: 2mm 0 0; color: ${colors.muted}; font-size: 3mm; }
  .version { height: 22mm; border: .4mm solid #6EE7A0; border-radius: 2mm; background: ${colors.greenSoft}; display: flex; flex-direction: column; align-items: center; justify-content: center; color: #047857; font-weight: 900; font-size: 2.7mm; line-height: 1.25; }
  .version small { color: ${colors.muted}; font-size: 2mm; }
  .context { display: grid; grid-template-columns: 1fr 1.55fr 1.35fr; gap: 4mm; padding: 2.2mm 4mm; border: .4mm solid ${colors.line}; border-radius: 2mm; background: ${colors.panel}; align-items: center; }
  .context > div { min-width: 0; }
  .context .label { display: block; margin-bottom: .6mm; color: ${colors.muted}; font-size: 2.1mm; font-weight: 800; }
  .context strong { display: block; font-size: 2.65mm; line-height: 1.1; }
  .steps { display: grid; gap: 2mm; padding-top: 1mm; }
  .step { position: relative; display: grid; grid-template-columns: minmax(0,1fr) 83mm; gap: 4mm; align-items: center; padding: 3mm 3mm 2.2mm 10mm; border: .4mm solid ${colors.line}; border-radius: 2mm; background: #FFF; min-height: 0; }
  .step-number { position: absolute; left: -7mm; top: -6mm; width: 13mm; height: 13mm; border: 1.1mm solid var(--family); border-radius: 50%; background: rgba(255,255,255,.9); color: var(--family); display: flex; align-items: center; justify-content: center; font-size: 4.7mm; font-weight: 900; z-index: 5; }
  .step-copy { align-self: center; min-width: 0; }
  .step-copy h2 { margin: 0 0 1.2mm; font-size: 4mm; line-height: 1.08; }
  .step-copy p { margin: .7mm 0; color: #334155; font-size: 2.45mm; line-height: 1.25; }
  .step-copy .inline-stop { color: ${colors.help}; font-weight: 900; }
  .step-visuals { min-width: 0; min-height: 0; display: grid; gap: 2mm; align-items: center; }
  .visual { position: relative; min-width: 0; min-height: 0; margin: 0; display: flex; flex-direction: column; gap: .6mm; }
  .shot { min-height: 0; border: .4mm solid ${colors.line}; border-radius: 1.7mm; background: #EEF2F6; overflow: hidden; }
  .shot svg { width: 100%; height: 100%; display: block; }
  .visual figcaption { margin: 0; color: #334155; font-size: 1.9mm; line-height: 1.15; }
  .image-badge { position: absolute; left: -2.4mm; top: -2.4mm; min-width: 6.5mm; height: 6.5mm; padding: 0 .6mm; border: .5mm solid var(--family); border-radius: 50%; background: rgba(255,255,255,.45); color: var(--family); display: flex; align-items: center; justify-content: center; font-size: 1.9mm; font-weight: 900; z-index: 4; }
  .target { fill: none; stroke: ${colors.help}; stroke-width: 2.4; vector-effect: non-scaling-stroke; }
  .help h2 { margin: 0 0 1mm; color: ${colors.muted}; font-size: 2.6mm; }
  .help-grid { display: grid; grid-template-columns: repeat(3,1fr); gap: 1.5mm; }
  .help-item { min-width: 0; height: 16.5mm; padding: 2mm; border: .4mm solid #FDBA74; border-radius: 1.7mm; background: ${colors.componentSoft}; display: grid; grid-template-columns: 5.5mm minmax(0,1fr); gap: 1.5mm; }
  .help-icon { width: 5.5mm; height: 5.5mm; border: .45mm solid #F59E0B; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #B45309; font-size: 2.1mm; font-weight: 900; }
  .help-item strong { display: block; font-size: 2.3mm; line-height: 1.1; }
  .help-item p { margin: 1mm 0 0; color: #334155; font-size: 1.85mm; line-height: 1.18; }
  .help-item.help-reference { height: 19.5mm; padding: 1.6mm 2mm; }
  .help-reference .guide-chip { margin-top: .8mm; padding: .45mm 1.2mm .45mm .7mm; font-size: 1.85mm; }
  .help-reference .guide-chip b { width: 3.8mm; height: 3.8mm; font-size: 1.15mm; }
  .done { display: flex; align-items: center; gap: 6mm; padding: 1.7mm 4mm; border: .4mm solid #6EE7A0; border-radius: 1.8mm; background: ${colors.greenSoft}; color: #047857; }
  .done strong { min-width: 20mm; font-size: 3mm; }
  .done span { font-size: 2.4mm; }
  footer { display: grid; grid-template-columns: minmax(0,1fr) 22mm; gap: 4mm; align-items: end; }
  .footer-copy { min-width: 0; display: flex; flex-direction: column; gap: 1mm; }
  .footer-label { color: ${colors.muted}; font-size: 2.1mm; font-weight: 800; }
  .related { display: flex; flex-wrap: wrap; gap: 1.1mm; }
  .guide-chip { display: inline-flex; align-items: center; gap: 1.2mm; padding: .9mm 2mm .9mm 1mm; border: .35mm solid var(--chip); border-radius: 2.2mm; background: var(--chip-fill); color: var(--chip); font-size: 2.3mm; font-weight: 800; white-space: nowrap; }
  .guide-chip b { width: 4.6mm; height: 4.6mm; border: .35mm solid var(--chip); border-radius: 50%; background: rgba(255,255,255,.6); display: inline-flex; align-items: center; justify-content: center; font-size: 1.35mm; }
  .source { color: #64748B; font-size: 1.65mm; }
  .qr { width: 22mm; display: flex; flex-direction: column; align-items: center; gap: .7mm; }
  .qr div { width: 22mm; height: 22mm; border: .5mm solid ${colors.ink}; border-radius: 1mm; background: ${colors.panel}; color: #64748B; display: flex; align-items: center; justify-content: center; text-align: center; font-size: 2.8mm; font-weight: 900; line-height: 1.05; }
  .qr span { font-size: 1.8mm; font-weight: 800; }
`;

function pageDocument(title, pageClass, body, extraCss = '') {
  return `<!doctype html><html lang="nl"><head><meta charset="utf-8"><title>${escapeHtml(title)}</title><style>${sharedCss}${extraCss}</style></head>
  <body><article class="page ${escapeHtml(pageClass)}">${body}</article></body></html>`;
}

function buildCmp02() {
  const steps = [
    step('1', 'Open Nieuw component', [
      'Open <b>Componenten</b> en tik <b>Add / Install Component</b>.',
      'Tik daarna <b>Show New Component Form</b>.',
    ], [
      visual({
        image: 'componentTab', label: '1A', caption: 'Open eerst de componentactie.',
        crop: { x: 12, y: 214, w: 390, h: 168 },
        marks: [
          { x: 139, y: 224, w: 42, h: 42, shape: 'circle' },
          { x: 21, y: 321, w: 358, h: 38 },
        ],
      }),
      visual({
        image: 'newEntry', label: '1B', caption: 'Open het formulier voor een nieuw component.',
        crop: { x: 15, y: 745, w: 370, h: 220 },
        marks: [{ x: 28, y: 880, w: 262, h: 47 }],
      }),
    ].join(''), '', 'two-visuals'),
    step('2', 'Kies een registratieroute', [
      '<b>Gebruik definitie</b>: normaal voor een bekend type dat vaker voorkomt.',
      '<b>Aangepast</b>: alleen voor één afgesproken uitzondering die niet wordt hergebruikt.',
      'Ontbreekt het type? Gebruik de catalogusgids in Hulp; kies niet automatisch Aangepast.',
    ], [
      visual({
        image: 'definitionForm', label: '2A', caption: 'Normaal: kies een definitie, serienummer en conditie.',
        crop: { x: 15, y: 165, w: 435, h: 475 },
        marks: [{ x: 18, y: 280, w: 35, h: 35, shape: 'circle' }],
      }),
      visual({
        image: 'customForm', label: '2B', caption: 'Alleen na akkoord: geef het aangepaste onderdeel een naam.',
        crop: { x: 15, y: 200, w: 450, h: 475 },
        marks: [{ x: 205, y: 313, w: 35, h: 35, shape: 'circle' }],
      }),
    ].join(''), '', 'alternatives'),
    step('3', 'Plaats en maak aan', [
      'Plaats het fysieke onderdeel. Vergelijk definitie of naam, serienummer en conditie.',
      'Tik daarna eenmaal op <b>Create And Install</b>.',
    ], visual({
      image: 'definitionForm', label: '3A', caption: 'Maak pas aan nadat de invoer en het onderdeel kloppen.',
      crop: { x: 15, y: 690, w: 435, h: 215 },
      marks: [{ x: 20, y: 849, w: 139, h: 41 }],
    }), 'STOP bij een duplicaat of fysieke/digitale mismatch.'),
    step('4', 'Controleer het asset', [
      'Controleer op het asset of <b>Tracked</b>, de nieuwe componenttag en het serienummer zichtbaar zijn.',
    ], visual({
      image: 'installedRow', label: '4A', caption: 'Het nieuwe gevolgde component staat op het juiste asset.',
      crop: { x: 45, y: 805, w: 345, h: 205 },
      marks: [
        { x: 108, y: 818, w: 66, h: 28 },
        { x: 54, y: 939, w: 338, h: 34 },
      ],
    }), 'STOP als het record niet overeenkomt.'),
  ].join('');

  return pageDocument('CMP-02 Nieuw component registreren en plaatsen', 'cmp02', `
    ${header('CMP-02', 'Nieuw component registreren en plaatsen', 'Registreer een nieuw fysiek onderdeel en koppel het aan het juiste asset', `Draft v${cmp02Version}`, colors.component)}
    ${context('Senior refurbisher', 'Open asset + nieuw fysiek onderdeel', chip('SC', 'SC-01', 'Asset vinden en openen'))}
    <main class="steps">${steps}</main>
    ${helpStrip('Hulp bij nieuw component', [
      { title: 'Definitie ontbreekt', body: 'Laat de herbruikbare definitie eerst beheren:', ref: { family: 'CAT', code: 'CAT-04', label: 'Componentdefinities beheren' } },
      ['Dubbele tag/serienummer', 'Stop en zoek het bestaande record.'],
      ['Conditiewaarschuwing', 'Niet bevestigen zonder supervisor.'],
    ])}
    ${done('Een uniek correct componentrecord staat met dezelfde identiteit op het juiste asset.')}
    ${footer([
      chip('SC', 'SC-01', 'Asset vinden en openen'), chip('CMP', 'CMP-01', 'Bestaand component plaatsen'),
      chip('CMP', 'CMP-04', 'Component naar tray'), chip('HELP', 'HELP-01', 'Problemen en hulp'),
    ])}
  `, `
    .cmp02 { grid-template-rows: 25mm 14mm 162mm 24mm 11mm 27mm; }
    .cmp02 .steps { grid-template-rows: 34mm 57mm 31mm 34mm; }
    .cmp02 .two-visuals .step-visuals, .cmp02 .alternatives .step-visuals { grid-template-columns: repeat(2,minmax(0,1fr)); }
    .cmp02 .step-1 .shot { height: 23mm; }
    .cmp02 .step-2 .shot { height: 47mm; }
    .cmp02 .step-3 .shot { height: 23mm; }
    .cmp02 .step-4 .visual { width: 65mm; justify-self: center; }
    .cmp02 .step-4 .shot { height: 28mm; }
    .cmp02 .step-2 .step-copy p { font-size: 2.25mm; }
    .cmp02 .step-2 .inline-stop { font-size: 2.2mm; }
  `);
}

function buildCmp04() {
  const steps = [
    step('1', 'Kies het juiste onderdeel', [
      'Vergelijk naam, componenttag en serienummer met het fysieke onderdeel.',
      'Tik op <b>Naar tray</b> bij precies die regel.',
    ], [
      visual({
        image: 'installedRow', label: '1A', caption: 'Vergelijk naam, tag en serienummer.',
        crop: { x: 45, y: 805, w: 345, h: 205 },
        marks: [
          { x: 108, y: 818, w: 66, h: 28 },
          { x: 54, y: 939, w: 338, h: 34 },
        ],
      }),
      visual({
        image: 'installedRow', label: '1B', caption: 'Gebruik Naar tray op dezelfde regel.',
        crop: { x: 45, y: 1085, w: 315, h: 100 },
        marks: [{ x: 54, y: 1134, w: 288, h: 38 }],
      }),
    ].join(''), 'STOP als naam, tag of serienummer niet overeenkomt.', 'two-visuals'),
    step('2', 'Controleer het venster', [
      'Controleer de componentnaam en het serienummer.',
      'Laat het serienummer vergrendeld als het klopt.',
    ], visual({
      image: 'trayModal', label: '2A', caption: 'Controleer component en serienummer voor je iets verwijdert.',
      crop: { x: 10, y: 105, w: 395, h: 205 },
      marks: [
        { x: 21, y: 121, w: 373, h: 59 },
        { x: 21, y: 215, w: 373, h: 43 },
      ],
    }), 'STOP als de identiteit niet klopt.'),
    step('3', 'Verwijder en bevestig', [
      'Verwijder het fysieke onderdeel en leg het in je eigen tray.',
      'Tik eenmaal op <b>Naar tray bevestigen</b>.',
    ], visual({
      image: 'trayModal', label: '3A', caption: 'Bevestig pas nadat het fysieke onderdeel in de tray ligt.',
      crop: { x: 10, y: 310, w: 395, h: 220 },
      marks: [{ x: 233, y: 470, w: 161, h: 41 }],
    })),
    step('4', 'Controleer de eindstaat', [
      'Open het component en controleer <b>Status: In Tray</b>.',
      'Bij <b>Asset</b> moet <b>N.v.t.</b> staan.',
    ], [
      visual({
        image: 'trayDetail', label: '4A', caption: 'Status is In Tray.',
        crop: { x: 15, y: 420, w: 370, h: 310 },
        marks: [{ x: 20, y: 562, w: 78, h: 27 }],
      }),
      visual({
        image: 'trayDetail', label: '4B', caption: 'Er is geen asset gekoppeld.',
        crop: { x: 15, y: 785, w: 370, h: 110 },
        marks: [{ x: 20, y: 850, w: 72, h: 28 }],
      }),
    ].join(''), 'STOP als het component nog aan een asset gekoppeld staat.', 'two-visuals'),
  ].join('');

  return pageDocument('CMP-04 Component naar tray', 'cmp04', `
    ${header('CMP-04', 'Component naar tray', 'Verwijder het juiste onderdeel en behoud identiteit en bestemming', `Draft v${cmp04Version}`, colors.component)}
    ${context('Senior refurbisher', 'Open asset + fysiek onderdeel', chip('SC', 'SC-01', 'Asset vinden en openen'))}
    <main class="steps">${steps}</main>
    ${helpStrip('Hulp bij naar tray', [
      ['Serienummer ontbreekt', 'Verzin geen nummer; vraag supervisor.'],
      ['Verkeerd component', 'Annuleer en kies de juiste regel.'],
      ['Geen rechten', 'Vraag een bevoegde supervisor.'],
    ])}
    ${done('Het onderdeel ligt in je tray, heeft Status In Tray en is niet meer aan een asset gekoppeld.')}
    ${footer([
      chip('SC', 'SC-01', 'Asset vinden en openen'), chip('CMP', 'CMP-01', 'Bestaand component plaatsen'),
      chip('CMP', 'CMP-02', 'Nieuw component registreren en plaatsen'), chip('HELP', 'HELP-01', 'Problemen en hulp'),
    ])}
  `, `
    .cmp04 { grid-template-rows: 25mm 14mm 165mm 21mm 11mm 27mm; }
    .cmp04 .steps { grid-template-rows: 42mm 38mm 34mm 45mm; }
    .cmp04 .two-visuals .step-visuals { grid-template-columns: repeat(2,minmax(0,1fr)); }
    .cmp04 .step-1 .shot { height: 28mm; }
    .cmp04 .step-2 .shot { height: 29mm; }
    .cmp04 .step-3 .shot { height: 26mm; }
    .cmp04 .step-4 .shot { height: 32mm; }
  `);
}

const helpTopics = [
  { icon: '@', title: 'Geen account', body: 'Vraag een supervisor om een account.', ref: chip('AC', 'AC-01', 'Login') },
  { icon: '*', title: 'Wachtwoord kwijt', body: 'Alleen een supervisor kan het wachtwoord laten resetten.', ref: chip('AC', 'AC-01', 'Login') },
  { icon: 'P', title: 'Geen telefoon', body: 'Open een browser en ga naar https://snipe.inbit/.', ref: chip('AC', 'AC-01', 'Login') },
  { icon: 'C', title: 'Camera werkt niet', body: 'Zoek handmatig op QR-waarde, assettag of serienummer.', ref: chip('SC', 'SC-01', 'Asset vinden') },
  { icon: 'QR', title: 'QR beschadigd', body: 'Zoek handmatig en laat het label controleren.', ref: chip('SC', 'SC-01', 'Asset vinden') },
  { icon: 'X', title: 'Verkeerde asset', body: 'Wijzig niets. Toon apparaat en scherm aan een supervisor.', ref: chip('SC', 'SC-01', 'Asset vinden') },
  { icon: 'WF', title: 'Geen workflow', body: 'Controleer asset en rechten. Start niet meerdere keren.', ref: chip('WF', 'WF-01', 'Workflow starten') },
  { icon: '!', title: 'Geen rechten', body: 'Vraag een bevoegde supervisor; probeer geen andere route.', ref: '' },
  { icon: 'PR', title: 'Printer of label faalt', body: 'Probeer eenmaal opnieuw en vraag daarna lokale hulp.', ref: chip('AST', 'AST-03', 'Labelen') },
  { icon: '2x', title: 'Dubbele assettag of serienummer', body: 'Stop. Zoek het bestaande assetrecord en vraag supervisor.', ref: chip('AST', 'AST-03', 'Registreren') },
  { icon: 'TR', title: 'Component niet in tray', body: 'Maak geen duplicaat. Controleer tray of opslag.', ref: chip('CMP', 'CMP-01', 'Bestaand plaatsen') },
  { icon: 'CMP', title: 'Component klopt niet', body: 'Stop. Kies het juiste onderdeel of annuleer de actie.', ref: chip('CMP', 'CMP-04', 'Naar tray') },
];

function buildHelp01() {
  const tiles = helpTopics.map((topic) => `<article class="problem">
    <span class="problem-icon">${escapeHtml(topic.icon)}</span>
    <div><h2>${escapeHtml(topic.title)}</h2><p>${escapeHtml(topic.body)}</p><div class="problem-ref">${topic.ref}</div></div>
  </article>`).join('');

  return pageDocument('HELP-01 Problemen en hulp', 'help01', `
    ${header('HELP-01', 'Problemen en hulp', 'Kies het probleem en voer alleen de veilige herstelactie uit', 'Draft v6', colors.help)}
    ${context('Iedereen', 'Apparaat + gids waarbij het probleem ontstond', chip('AC', 'AC-01', 'Login indien mogelijk'))}
    <section class="general-stop"><strong>STOP</strong><span>Als apparaat, label, serienummer of digitaal record niet overeenkomt: wijzig niets en vraag een supervisor.</span></section>
    <main class="problem-grid">${tiles}</main>
    ${done('Je weet welke veilige herstelactie, gids of persoon nodig is voordat je verdergaat.')}
    ${footer([chip('AC', 'AC-01', 'Login'), chip('SC', 'SC-01', 'Asset vinden'), chip('CMP', 'CMP-01', 'Componenten'), chip('WF', 'WF-01', 'Workflow')])}
  `, `
    .help01 { --family:${colors.help}; grid-template-rows: 25mm 14mm 12mm 176mm 11mm 25mm; }
    .help01 .general-stop { display: grid; grid-template-columns: 14mm minmax(0,1fr); align-items: center; gap: 2mm; padding: 2mm 4mm; border: .5mm solid #FDA4AF; border-radius: 2mm; background: ${colors.helpSoft}; color: #BE123C; }
    .help01 .general-stop strong { font-size: 3.4mm; }
    .help01 .general-stop span { font-size: 2.55mm; font-weight: 800; line-height: 1.15; }
    .problem-grid { display: grid; grid-template-columns: repeat(2,minmax(0,1fr)); grid-template-rows: repeat(6,1fr); gap: 2mm; }
    .problem { min-width: 0; display: grid; grid-template-columns: 9mm minmax(0,1fr); gap: 2.3mm; padding: 2.4mm 3mm; border: .4mm solid ${colors.line}; border-radius: 2mm; background: #FFF; }
    .problem-icon { width: 8mm; height: 8mm; border: .7mm solid ${colors.help}; border-radius: 50%; color: ${colors.help}; display: flex; align-items: center; justify-content: center; font-size: 2.25mm; font-weight: 900; }
    .problem h2 { margin: 0; font-size: 3mm; line-height: 1.05; }
    .problem p { margin: .8mm 0 1.1mm; color: #334155; font-size: 2.15mm; line-height: 1.18; }
    .problem .guide-chip { padding: .55mm 1.6mm .55mm .8mm; font-size: 1.9mm; }
    .problem .guide-chip b { width: 3.9mm; height: 3.9mm; font-size: 1.15mm; }
    .problem-ref { margin-top: auto; }
  `);
}

function run(command, args, label) {
  const result = spawnSync(command, args, { encoding: 'utf8', stdio: ['ignore', 'pipe', 'pipe'] });
  if (result.status !== 0) throw new Error(`${label} failed (${result.status}):\n${result.stdout}\n${result.stderr}`);
}

function renderGuide(slug, html) {
  const htmlPath = path.join(outDir, `${slug}.html`);
  const pdfPath = path.join(outDir, `${slug}-proof.pdf`);
  const pngBase = path.join(outDir, slug);
  const pngPath = `${pngBase}-proof.png`;
  fs.writeFileSync(htmlPath, html, 'utf8');
  const profileDir = fs.mkdtempSync(path.join(os.tmpdir(), 'component-followup-chrome-'));
  try {
    run(chromePath, [
      '--headless=new', '--disable-gpu', '--no-sandbox', '--allow-file-access-from-files',
      `--user-data-dir=${profileDir}`, '--no-pdf-header-footer', `--print-to-pdf=${pdfPath}`,
      pathToFileURL(htmlPath).href,
    ], `Render ${slug}`);
  } finally {
    fs.rmSync(profileDir, { recursive: true, force: true });
  }
  run(pdftoppmPath, ['-png', '-singlefile', '-r', '144', pdfPath, pngBase], `Render proof ${slug}`);
  fs.rmSync(pngPath, { force: true });
  fs.renameSync(`${pngBase}.png`, pngPath);
  const repoPdf = path.join(repoPdfDir, `${slug}.pdf`);
  fs.copyFileSync(pdfPath, repoPdf);
  return { slug, html: htmlPath, pdf: pdfPath, png: pngPath, repoPdf };
}

assertInputs();
fs.mkdirSync(outDir, { recursive: true });
fs.mkdirSync(repoPdfDir, { recursive: true });

const guideBuilders = {
  'CMP-02': { slug: `CMP-02-register-install-v${cmp02Version}-draft`, build: buildCmp02 },
  'CMP-04': { slug: `CMP-04-component-to-tray-v${cmp04Version}-draft`, build: buildCmp04 },
  'HELP-01': { slug: 'HELP-01-problems-v6-draft', build: buildHelp01 },
};
const selectedGuides = guideFilter ? [guideFilter] : Object.keys(guideBuilders);
selectedGuides.forEach((code) => {
  if (!guideBuilders[code]) throw new Error(`Unknown component guide filter: ${code}`);
});
const outputs = selectedGuides.map((code) => {
  const guide = guideBuilders[code];
  return renderGuide(guide.slug, guide.build());
});

const combinedSuffix = guideFilter ? guideFilter.toLowerCase() : 'batch';
const combinedName = `component-followup-guides-${combinedSuffix}-${generatedOn}.pdf`;
const combinedPdf = path.join(outDir, combinedName);
const mergeCode = [
  'from pypdf import PdfReader, PdfWriter',
  'import sys',
  'w=PdfWriter()',
  '[(w.add_page(p)) for f in sys.argv[2:] for p in PdfReader(f).pages]',
  'w.write(sys.argv[1])',
].join(';');
run(pythonPath, ['-c', mergeCode, combinedPdf, ...outputs.map((item) => item.pdf)], 'Merge review batch');
const repoCombined = path.join(repoPdfDir, combinedName);
fs.copyFileSync(combinedPdf, repoCombined);

const manifest = {
  generatedAt: new Date().toISOString(),
  operatorFacingUrl: 'https://snipe.inbit/',
  captureEnvironment: 'Controlled development environment',
  testRecord: { asset: 'DEMO-001', componentTag: 'INBIT-C-HH9376', serial: 'CMP02-RAM-0001', finalState: 'In Tray' },
  sources,
  outputs,
  combinedPdf,
  repoCombined,
  reviewNotes: [
    'CMP-02 follows the verified four-step create-and-install flow.',
    'CMP-04 follows the verified four-step locked-serial move-to-tray flow.',
    'All screenshot targets use source-pixel coordinates and symmetric padding.',
    'HELP-01 includes component/tray recovery routes and supervisor-only password reset.',
    'Printed crops exclude the development URL and controlled asset tag.',
  ],
};
fs.writeFileSync(path.join(outDir, 'generation-manifest.json'), `${JSON.stringify(manifest, null, 2)}\n`, 'utf8');
fs.writeFileSync(path.join(outDir, 'review-summary.md'), `# Component Follow-up Review Batch\n\n- CMP-02 v${cmp02Version}: four-step verified new-component flow with an operator-language definition/custom choice.\n- CMP-04 v${cmp04Version}: four-step verified move-to-tray flow.\n- HELP-01 v6: twelve compact recovery routes.\n- Existing tracked tray/storage records belong to CMP-01; new physical parts based on a catalog definition belong to CMP-02 route 2A.\n- Controlled component INBIT-C-HH9376 / CMP02-RAM-0001 ends in Status In Tray and is not attached to an asset.\n`, 'utf8');

console.log(JSON.stringify({ outDir, outputs, combinedPdf, repoCombined }, null, 2));
