import fs from 'node:fs';
import path from 'node:path';
import {
  baselinePath,
  browserLaunchOptions,
  evidencePath,
  guideOutputDir,
  loadGuideDependency,
  repoPdfOutputRoot,
} from './lib/guide-paths.mjs';

const { chromium } = loadGuideDependency('playwright');

const today = '2026-07-21';
const outDir = process.env.SNIPEIT_GUIDE_OUT_DIR || guideOutputDir('revised-guide-set-v2');
const repoPdfDir = repoPdfOutputRoot;
const combinedBaseName = 'operator-guides-revised-set-v2-draft';

const baselineFiles = {
  ac: baselinePath('AC-01-login-v6.svg'),
  sc: baselinePath('SC-01-asset-vinden-en-openen-v10.svg'),
};

const imageFiles = {
  phoneStart: evidencePath('PHONE-START-01'),
  login: evidencePath('LOGIN-MOBILE-01'),
  dashboard: evidencePath('DASH-MOBILE-01'),
  dashboardWide: evidencePath('DASH-MOBILE-01'),
  scanCamera: evidencePath('SCAN-CAMERA-QR-01'),
  searchField: evidencePath('SEARCH-FIELD-01'),
  searchResult: evidencePath('SEARCH-RESULT-01'),
  assetVerify: evidencePath('ASSET-VERIFY-01'),
  assetDetail: evidencePath('ASSET-DETAIL-02'),
  assetLabel: evidencePath('ASSET-LABEL-01'),
  assetIndex: evidencePath('ASSET-INDEX-01'),
  componentsWide: evidencePath('CMP-TAB-WIDE-01'),
  componentRows: evidencePath('CMP-ROWS-01'),
  componentList: evidencePath('CMP-LIST-01'),
  trayLocked: evidencePath('CMP-TRAY-LOCKED-01'),
  trayUnlocked: evidencePath('CMP-TRAY-UNLOCKED-01'),
  workflowWide: evidencePath('WF-ENTRY-MOBILE-03'),
  workflowCards: evidencePath('WF-NEUTRAL-MOBILE-03'),
  workflowResults1: evidencePath('WF-RESULTS-01'),
  workflowResults2: evidencePath('WF-RESULTS-02'),
};

const colors = {
  AC: '#2563EB',
  SC: '#0F8F7B',
  AST: '#138A43',
  WF: '#F97316',
  CMP: '#C17A00',
  HELP: '#E83448',
};

const softColors = {
  AC: '#EFF6FF',
  SC: '#ECFDF8',
  AST: '#ECFDF3',
  WF: '#FFF7ED',
  CMP: '#FFF8E6',
  HELP: '#FFF1F3',
};

function escapeHtml(value) {
  return String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

function imageData(file) {
  if (!file || !fs.existsSync(file)) return null;
  const ext = path.extname(file).toLowerCase();
  const mime = ext === '.jpg' || ext === '.jpeg' ? 'image/jpeg' : 'image/png';
  return `data:${mime};base64,${fs.readFileSync(file).toString('base64')}`;
}

const images = Object.fromEntries(
  Object.entries(imageFiles).map(([key, file]) => [key, { file, data: imageData(file) }]),
);

function loadBaselineSvg(file) {
  if (!fs.existsSync(file)) throw new Error(`Missing baseline SVG: ${file}`);
  const match = fs.readFileSync(file, 'utf8').match(/<svg[\s\S]*<\/svg>/);
  if (!match) throw new Error(`Invalid baseline SVG: ${file}`);
  return match[0];
}

const acBaselineSvg = loadBaselineSvg(baselineFiles.ac)
  .replace('SC-01 Scan', 'SC-01 Openen')
  .replace('AST-01 Open', 'AST-02 Route');
const scBaselineSvg = loadBaselineSvg(baselineFiles.sc);

function annotationHtml(annotation) {
  if (!annotation) return '';
  const style = [
    `left:${annotation.x}%`,
    `top:${annotation.y}%`,
    `width:${annotation.w}%`,
    `height:${annotation.h}%`,
    `border-radius:${annotation.shape === 'circle' ? '50%' : '7px'}`,
  ].join(';');
  return `<span class="target-mark" style="${style}"></span>`;
}

function visualHtml(visual) {
  if (visual.gap) {
    return `<div class="visual-wrap gap-wrap">
      <div class="evidence-gap">
        <span>BEELD NOG VASTLEGGEN</span>
        <strong>${escapeHtml(visual.gap)}</strong>
      </div>
      ${visual.caption ? `<div class="caption">${escapeHtml(visual.caption)}</div>` : ''}
    </div>`;
  }

  const img = images[visual.image];
  if (!img?.data) {
    return visualHtml({
      gap: `Bron ontbreekt: ${visual.image}`,
      caption: visual.caption,
    });
  }

  const fit = visual.fit || 'cover';
  const position = visual.position || 'center';
  return `<figure class="visual-wrap ${escapeHtml(visual.className || '')}">
    ${visual.label ? `<span class="image-badge">${escapeHtml(visual.label)}</span>` : ''}
    <div class="shot ${visual.tall ? 'shot-tall' : ''}">
      <img src="${img.data}" alt="${escapeHtml(visual.alt || visual.caption || '')}" style="object-fit:${fit};object-position:${position};">
      ${annotationHtml(visual.annotation)}
    </div>
    ${visual.caption ? `<figcaption class="caption">${escapeHtml(visual.caption)}</figcaption>` : ''}
  </figure>`;
}

function stepHtml(step, family, rowLayout = false) {
  const visuals = (step.visuals || []).map(visualHtml).join('');
  return `<section class="step-card ${rowLayout ? 'row-card' : ''} ${step.className || ''}">
    <span class="step-badge">${escapeHtml(step.number)}</span>
    <div class="step-copy">
      <h3>${escapeHtml(step.title)}</h3>
      ${(step.body || []).map((line) => `<p>${escapeHtml(line)}</p>`).join('')}
      ${step.note ? `<p class="note">${escapeHtml(step.note)}</p>` : ''}
      ${step.stop ? `<p class="inline-stop">${escapeHtml(step.stop)}</p>` : ''}
    </div>
    ${visuals ? `<div class="step-visuals ${step.visuals?.length > 1 ? 'multi' : ''}">${visuals}</div>` : ''}
  </section>`;
}

function guideChip(ref) {
  const color = colors[ref.family] || '#64748B';
  const fill = softColors[ref.family] || '#F8FAFC';
  return `<span class="guide-chip" style="--chip:${color};--chip-fill:${fill}">
    <b>${escapeHtml(ref.family)}</b><span>${escapeHtml(ref.code)} ${escapeHtml(ref.label)}</span>
  </span>`;
}

function contextHtml(page) {
  const items = [
    { label: 'Rol', value: page.role },
    { label: 'Nodig', value: page.needed },
  ];
  if (page.prerequisite) items.push({ label: 'Vooraf', ref: page.prerequisite });
  else if (page.address) items.push({ label: 'Adres', value: page.address, accent: true });
  return `<div class="context context-${items.length}">
    ${items.map((item) => `<div class="context-item">
      <span>${escapeHtml(item.label)}</span>
      ${item.ref ? guideChip(item.ref) : `<strong class="${item.accent ? 'accent-value' : ''}">${escapeHtml(item.value)}</strong>`}
    </div>`).join('')}
  </div>`;
}

function helpHtml(page) {
  if (!page.help?.length) return '';
  const branch = page.helpBranch
    ? `<div class="help-branch">
        <span class="help-icon">${escapeHtml(page.helpBranch.icon || '!')}</span>
        <strong>${escapeHtml(page.helpBranch.title)}</strong>
        ${guideChip(page.helpBranch.ref)}
        <span>${escapeHtml(page.helpBranch.body)}</span>
      </div>`
    : '';
  return `<section class="help-section">
    <h2>${escapeHtml(page.helpTitle || 'Hulp bij deze taak')}</h2>
    <div class="help-grid" style="--help-cols:${Math.min(page.help.length, 4)}">
      ${page.help.map((item) => `<div class="help-tile">
        <span class="help-icon">${escapeHtml(item.icon || '!')}</span>
        <div><strong>${escapeHtml(item.title)}</strong><p>${escapeHtml(item.body)}</p></div>
      </div>`).join('')}
    </div>
    ${branch}
  </section>`;
}

function footerHtml(page) {
  return `<section class="done"><strong>Klaar als</strong><span>${escapeHtml(page.done)}</span></section>
  <footer>
    <div class="footer-main">
      <span class="footer-label">Relevante gidsen</span>
      <div class="related">${(page.related || []).map(guideChip).join('')}</div>
      <div class="source">Bron: bestaande opnamen + gecontroleerde testomgeving | ${escapeHtml(page.date || today)} | concept voor review</div>
    </div>
    <div class="qr-draft"><div>QR<br>volgt</div><span>Digitale gids</span></div>
  </footer>`;
}

function standardMain(page) {
  const rowLayout = page.layout === 'rows';
  return `<main class="steps layout-${escapeHtml(page.layout || 'two')}">
    ${(page.steps || []).map((step) => stepHtml(step, page.family, rowLayout)).join('')}
  </main>`;
}

function routeMain(page) {
  return `<main class="route-main">
    <div class="route-start">Start bij een geregistreerd apparaat</div>
    <div class="route-track">
      ${page.route.map((item, index) => `<div class="route-item ${item.optional ? 'route-optional' : ''}">
        <span class="route-number">${index + 1}</span>
        ${guideChip(item.ref)}
        <p>${escapeHtml(item.text)}</p>
      </div>`).join('')}
    </div>
  </main>`;
}

function helpPageMain(page) {
  return `<main class="problem-main">
    <div class="global-stop"><strong>STOP</strong><span>Als apparaat, label, serienummer of digitaal record niet overeenkomt: wijzig niets en vraag een supervisor.</span></div>
    <div class="problem-grid">
      ${page.problems.map((item) => `<section class="problem-tile">
        <span class="problem-icon">${escapeHtml(item.icon || '?')}</span>
        <div><h3>${escapeHtml(item.title)}</h3><p>${escapeHtml(item.body)}</p>${item.ref ? guideChip(item.ref) : ''}</div>
      </section>`).join('')}
    </div>
  </main>`;
}

function pageHtml(page) {
  if (page.kind === 'frozen') {
    return `<article class="guide-page frozen">${page.frozenSvg}</article>`;
  }

  const familyColor = colors[page.family];
  const pageDate = page.date || today;
  const titleLength = `${page.code} ${page.title}`.length;
  const titleClass = titleLength > 44
    ? 'title-xsmall'
    : titleLength > 32
      ? 'title-small'
      : titleLength > 22
        ? 'title-medium'
        : '';
  let main = standardMain(page);
  if (page.kind === 'route') main = routeMain(page);
  if (page.kind === 'help') main = helpPageMain(page);

  return `<article class="guide-page ${page.kind || 'task'}" style="--family:${familyColor};--family-soft:${softColors[page.family]}">
    <header>
      <div class="title-block"><span class="family-bar"></span><div><h1 class="${titleClass}">${escapeHtml(page.code)} ${escapeHtml(page.title)}</h1><p>${escapeHtml(page.subtitle)}</p></div></div>
      <div class="version">${escapeHtml(page.version)}<br>${escapeHtml(pageDate)}${page.pageLabel ? `<small>${escapeHtml(page.pageLabel)}</small>` : ''}</div>
    </header>
    ${contextHtml(page)}
    ${main}
    ${helpHtml(page)}
    ${footerHtml(page)}
  </article>`;
}

const css = `
  @page { size: A4 portrait; margin: 0; }
  * { box-sizing: border-box; }
  html, body { margin: 0; padding: 0; background: #E5E7EB; color: #102033; font-family: Arial, Helvetica, sans-serif; }
  body { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
  .guide-page { width: 210mm; height: 297mm; padding: 12mm; background: #FFFFFF; display: grid; grid-template-columns: minmax(0, 1fr); grid-template-rows: 24mm 17mm minmax(0, 1fr) auto 12mm 29mm; gap: 3mm; break-after: page; page-break-after: always; overflow: hidden; }
  .guide-page:last-child { break-after: auto; page-break-after: auto; }
  .guide-page > * { min-width: 0; max-width: 100%; }
  .guide-page.frozen { padding: 0; display: block; }
  .guide-page.frozen > svg { display: block; width: 210mm; height: 297mm; }
  header { display: flex; justify-content: space-between; align-items: flex-start; min-width: 0; }
  .title-block { display: flex; flex: 1 1 auto; min-width: 0; max-width: calc(100% - 40mm); gap: 4mm; }
  .title-block > div { min-width: 0; }
  .family-bar { width: 2mm; height: 17mm; background: var(--family); flex: 0 0 auto; }
  h1 { margin: 0; font-size: 10mm; line-height: 1; font-weight: 900; letter-spacing: 0; white-space: nowrap; }
  h1.title-medium { font-size: 7.7mm; }
  h1.title-small { font-size: 6.45mm; }
  h1.title-xsmall { font-size: 4.7mm; }
  .title-block p { margin: 2.2mm 0 0; color: #53657A; font-size: 3.25mm; }
  .version { flex: 0 0 35mm; min-width: 35mm; padding: 2.6mm 3mm; border: .45mm solid #86EFAC; border-radius: 2mm; background: #ECFDF5; color: #047857; text-align: center; font-size: 2.55mm; line-height: 1.35; font-weight: 800; }
  .version small { display: block; margin-top: 1mm; color: #53657A; font-weight: 700; }
  .context { display: grid; align-items: center; border: .4mm solid #C8D5E2; border-radius: 1.8mm; background: #F8FAFC; padding: 2.8mm 5mm; gap: 4mm; }
  .context-3 { grid-template-columns: .7fr 1.45fr 1.25fr; }
  .context-2 { grid-template-columns: 1fr 2fr; }
  .context-item { min-width: 0; display: flex; flex-direction: column; gap: 1mm; }
  .context-item > span { color: #53657A; font-size: 2.25mm; font-weight: 800; }
  .context-item > strong { font-size: 3.1mm; line-height: 1.15; }
  .accent-value { color: var(--family); }
  .guide-chip { --chip: #64748B; --chip-fill: #F8FAFC; display: inline-flex; align-items: center; gap: 1.8mm; min-width: 0; max-width: 100%; min-height: 7mm; padding: .8mm 2.2mm .8mm 1.2mm; border: .45mm solid var(--chip); border-radius: 2.3mm; background: var(--chip-fill); color: var(--chip); font-size: 2.65mm; font-weight: 800; white-space: nowrap; overflow: hidden; }
  .guide-chip span { min-width: 0; overflow: hidden; text-overflow: ellipsis; }
  .guide-chip b { width: 5mm; height: 5mm; border: .4mm solid var(--chip); border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; background: rgba(255,255,255,.65); font-size: 1.7mm; }
  .steps { min-height: 0; display: grid; gap: 3mm; padding-top: 2mm; }
  .layout-two { grid-template-columns: 1fr 1fr; grid-auto-rows: 1fr; }
  .layout-three { grid-template-columns: repeat(3, 1fr); grid-auto-rows: 1fr; }
  .layout-rows { grid-template-columns: 1fr; grid-auto-rows: 1fr; gap: 2.2mm; }
  .step-card { position: relative; min-width: 0; min-height: 0; padding: 9mm 4mm 3.5mm 10mm; border: .4mm solid #C8D5E2; border-radius: 2mm; background: #FFFFFF; display: flex; flex-direction: column; gap: 2mm; overflow: visible; }
  .step-badge { position: absolute; left: -8.5mm; top: -7.5mm; width: 15mm; height: 15mm; border: 1.35mm solid var(--family); border-radius: 50%; background: rgba(255,255,255,.88); color: var(--family); display: flex; align-items: center; justify-content: center; font-size: 5.2mm; line-height: 1; font-weight: 900; z-index: 4; }
  .step-copy h3 { margin: 0 0 1.2mm; font-size: 4.15mm; line-height: 1.1; }
  .step-copy p { margin: .6mm 0; font-size: 2.75mm; line-height: 1.28; color: #334155; }
  .step-copy .note { color: var(--family); font-size: 2.45mm; font-weight: 800; }
  .step-copy .inline-stop { color: #E83448; font-weight: 900; }
  .step-visuals { min-height: 0; flex: 1; display: flex; gap: 2.2mm; align-items: stretch; }
  .step-visuals.multi .visual-wrap { flex: 1 1 0; min-width: 0; }
  .visual-wrap { position: relative; min-width: 0; min-height: 0; margin: 0; display: flex; flex: 1; flex-direction: column; gap: 1mm; overflow: visible; }
  .shot { position: relative; flex: 1; min-height: 18mm; border: .4mm solid #C8D5E2; border-radius: 1.7mm; background: #F1F5F9; overflow: hidden; }
  .shot img { width: 100%; height: 100%; display: block; }
  .visual-wrap.title-crop .shot { flex: 0 0 9.5mm; min-height: 9.5mm; }
  .visual-wrap.tab-crop .shot { flex: 0 0 14mm; min-height: 14mm; }
  .visual-wrap.control-crop .shot { flex: 0 0 18mm; min-height: 18mm; }
  .image-badge { position: absolute; left: -3.2mm; top: -3.2mm; min-width: 8mm; height: 8mm; padding: 0 1mm; border: .75mm solid var(--family); border-radius: 50%; background: rgba(255,255,255,.55); color: var(--family); display: flex; align-items: center; justify-content: center; font-size: 2.35mm; font-weight: 900; z-index: 3; }
  .target-mark { position: absolute; border: .9mm solid #EF3340; pointer-events: none; z-index: 2; }
  .caption { margin: 0; min-height: 5mm; color: #334155; font-size: 2.25mm; line-height: 1.2; }
  .evidence-gap { flex: 1; min-height: 18mm; border: .45mm dashed #F59E0B; border-radius: 1.7mm; background: #FFF7ED; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 1.5mm; padding: 3mm; text-align: center; }
  .evidence-gap span { color: #B45309; font-size: 2.1mm; font-weight: 900; }
  .evidence-gap strong { color: #7C2D12; font-size: 2.6mm; line-height: 1.25; }
  .row-card { padding: 5mm 4mm 3mm 10mm; display: grid; grid-template-columns: 68mm minmax(0, 1fr); grid-template-rows: 1fr; gap: 4mm; align-items: stretch; }
  .row-card .step-copy { align-self: center; }
  .row-card .step-visuals { min-height: 0; }
  .row-card .caption { display: none; }
  .help-section { min-height: 0; }
  .help-section h2 { margin: 0 0 1.5mm; color: #53657A; font-size: 2.8mm; }
  .help-grid { display: grid; grid-template-columns: repeat(var(--help-cols), 1fr); gap: 2mm; }
  .help-tile { min-width: 0; min-height: 22mm; padding: 3mm; border: .4mm solid #FDBA74; border-radius: 2mm; background: #FFF7ED; display: flex; gap: 2mm; }
  .help-icon { width: 7mm; height: 7mm; flex: 0 0 7mm; border: .55mm solid #F59E0B; border-radius: 50%; color: #D97706; display: flex; align-items: center; justify-content: center; font-size: 2.5mm; font-weight: 900; }
  .help-tile strong { font-size: 2.75mm; overflow-wrap: anywhere; }
  .help-tile p { margin: 2mm 0 0; color: #334155; font-size: 2.3mm; line-height: 1.25; }
  .help-branch { margin-top: 2mm; min-width: 0; padding: 2.5mm 3mm; border: .4mm solid #FDBA74; border-radius: 2mm; background: #FFF7ED; display: grid; grid-template-columns: 7mm max-content max-content minmax(0, 1fr); align-items: center; gap: 2mm; }
  .help-branch > strong { font-size: 2.75mm; }
  .help-branch > span:last-child { color: #334155; font-size: 2.4mm; line-height: 1.25; }
  .help-branch .guide-chip { width: fit-content; transform: none; }
  .done { display: flex; align-items: center; gap: 7mm; padding: 2.5mm 5mm; border: .45mm solid #6EE7A0; border-radius: 2mm; background: #ECFDF5; color: #047857; }
  .done strong { min-width: 22mm; font-size: 3.25mm; }
  .done span { font-size: 2.75mm; line-height: 1.2; }
  footer { display: grid; grid-template-columns: minmax(0, 1fr) 24mm; gap: 5mm; align-items: end; min-height: 0; }
  .footer-main { min-width: 0; display: flex; flex-direction: column; gap: 1.7mm; }
  .footer-label { color: #53657A; font-size: 2.3mm; font-weight: 800; }
  .related { min-width: 0; display: flex; flex-wrap: wrap; gap: 1.5mm; }
  .source { color: #64748B; font-size: 1.85mm; white-space: nowrap; }
  .qr-draft { width: 22mm; display: flex; flex-direction: column; align-items: center; gap: 1mm; }
  .qr-draft > div { width: 22mm; height: 22mm; border: .55mm solid #102033; border-radius: 1mm; background: #F8FAFC; color: #64748B; display: flex; align-items: center; justify-content: center; text-align: center; font-size: 3mm; font-weight: 900; line-height: 1.05; }
  .qr-draft span { font-size: 2mm; font-weight: 800; }
  .route-main { min-height: 0; display: flex; flex-direction: column; justify-content: flex-start; gap: 3mm; padding: 2mm 0; }
  .route-start { align-self: center; padding: 2.5mm 6mm; border: .45mm solid #C8D5E2; border-radius: 2mm; background: #F8FAFC; font-size: 3mm; font-weight: 800; }
  .route-track { position: relative; display: flex; flex-direction: column; gap: 2mm; padding-left: 7mm; }
  .route-track::before { content: ''; position: absolute; left: 7mm; top: 7mm; bottom: 7mm; width: .5mm; background: #B7D9D1; }
  .route-item { position: relative; min-width: 0; min-height: 16mm; padding: 2.3mm 3mm 2.3mm 10mm; border: .4mm solid #C8D5E2; border-radius: 2mm; display: grid; grid-template-columns: 58mm minmax(0, 1fr); align-items: center; gap: 4mm; text-align: left; background: #FFFFFF; }
  .route-item .guide-chip { width: fit-content; max-width: 58mm; transform: none; }
  .route-number { position: absolute; top: 50%; left: 0; transform: translate(-50%, -50%); width: 9mm; height: 9mm; border: .9mm solid var(--family); border-radius: 50%; background: rgba(255,255,255,.94); color: var(--family); display: flex; align-items: center; justify-content: center; font-size: 3.4mm; font-weight: 900; z-index: 2; }
  .route-item p { margin: 0; font-size: 2.5mm; line-height: 1.3; }
  .problem-main { min-height: 0; display: flex; flex-direction: column; gap: 3mm; }
  .global-stop { padding: 3mm 4mm; border: .55mm solid #FDA4AF; border-radius: 2mm; background: #FFF1F3; display: flex; align-items: center; gap: 4mm; color: #BE123C; }
  .global-stop strong { font-size: 3.3mm; }
  .global-stop span { font-size: 2.7mm; font-weight: 700; }
  .problem-grid { min-height: 0; flex: 1; display: grid; grid-template-columns: 1fr 1fr; grid-auto-rows: 1fr; gap: 2mm; }
  .problem-tile { min-height: 0; padding: 2.5mm 3mm; border: .4mm solid #C8D5E2; border-radius: 2mm; display: flex; gap: 3mm; align-items: flex-start; }
  .problem-icon { width: 8mm; height: 8mm; flex: 0 0 8mm; border: .65mm solid var(--family); border-radius: 50%; color: var(--family); display: flex; align-items: center; justify-content: center; font-size: 2.8mm; font-weight: 900; }
  .problem-tile h3 { margin: .7mm 0 1mm; font-size: 3mm; }
  .problem-tile p { margin: 0 0 1.3mm; color: #334155; font-size: 2.35mm; line-height: 1.22; }
  .problem-tile .guide-chip { min-height: 5.5mm; font-size: 2.15mm; padding: .5mm 1.4mm .5mm .8mm; }
  .problem-tile .guide-chip b { width: 4mm; height: 4mm; font-size: 1.3mm; }
  .problem-main + .help-section { display: none; }
  .help.guide-page { grid-template-rows: 24mm 17mm minmax(0, 1fr) 0 12mm 29mm; }
  .help.guide-page > .done { grid-row: 5; }
  .help.guide-page > footer { grid-row: 6; }
  @media print { html, body { background: #FFFFFF; } }
`;

function documentHtml(pages, title) {
  return `<!doctype html><html lang="nl"><head><meta charset="utf-8"><title>${escapeHtml(title)}</title><style>${css}</style></head><body>${pages.map(pageHtml).join('')}</body></html>`;
}

const ref = (family, code, label) => ({ family, code, label });

const guides = [
  {
    slug: 'AC-01-login-snipe-v6',
    pages: [{
      kind: 'frozen', frozenSvg: acBaselineSvg,
      family: 'AC', code: 'AC-01', title: 'Login', version: 'Draft v6',
      subtitle: 'Open Inbit Snipe-IT en controleer dat het dashboard zichtbaar is',
      role: 'Iedereen', needed: 'Telefoon met Inbit Snipe-IT', address: 'https://snipe.inbit/',
      layout: 'three',
      steps: [
        { number: '1', title: 'Open Snipe-IT', body: ['Tik op de herkenbare snelkoppeling.'], visuals: [
          { image: 'phoneStart', label: '1A', fit: 'contain', position: 'center', caption: 'Startscherm telefoon: Inbit Snipe-IT.', annotation: { x: 8, y: 11, w: 16, h: 16, shape: 'circle' } },
        ] },
        { number: '2', title: 'Log in', body: ['Vul gebruikersnaam en wachtwoord in.', 'Tik op Inloggen.'], visuals: [
          { image: 'login', label: '2A', fit: 'contain', position: 'center', caption: 'Inlogscherm: Inloggen na invullen.' },
        ] },
        { number: '3', title: 'Controleer dashboard', body: ['Ga verder als Dashboard zichtbaar is.'], visuals: [
          { image: 'dashboard', label: '3A', fit: 'contain', position: 'center', caption: 'Dashboard: Scan QR of Apparaten zichtbaar.' },
        ] },
      ],
      helpTitle: 'Hulp bij login',
      help: [
        { title: 'Geen account', body: 'Vraag beheerder of supervisor.' },
        { title: 'Wachtwoord kwijt', body: 'Vraag je supervisor om een reset.' },
        { title: 'Geen telefoon', body: 'Open browser: https://snipe.inbit/.' },
        { title: 'Sessie verlopen', body: 'Log opnieuw in en probeer nogmaals.' },
      ],
      done: 'Het dashboard is zichtbaar en je kunt verder met de juiste gids.',
      related: [ref('SC', 'SC-01', 'Asset vinden'), ref('AST', 'AST-02', 'Refurb route'), ref('HELP', 'HELP-01', 'Hulp')],
    }],
  },
  {
    slug: 'SC-01-find-open-asset-snipe-v10',
    pages: [{
      kind: 'frozen', frozenSvg: scBaselineSvg,
      family: 'SC', code: 'SC-01', title: 'Asset vinden en openen', version: 'Draft v10',
      subtitle: 'Scan of zoek het apparaat en controleer het voordat je iets wijzigt',
      role: 'Refurbisher', needed: 'Telefoon met camera + apparaat met QR/asset tag',
      prerequisite: ref('AC', 'AC-01', 'Login'), layout: 'two',
      steps: [
        { number: '1', title: 'Open de scanner', body: ['Kies een van de twee manieren.'], visuals: [
          { image: 'dashboard', label: '1A', fit: 'cover', position: 'center 18%', caption: 'Dashboard: tik op de paarse Scan QR kaart.', annotation: { x: 54, y: 26, w: 42, h: 36 } },
          { image: 'dashboardWide', label: '1B', fit: 'cover', position: 'center 16%', caption: 'Bovenbalk: tik op het camera-icoon.', annotation: { x: 41, y: 10, w: 10, h: 14, shape: 'circle' } },
        ] },
        { number: '2', title: 'Scan het label', body: ['Houd de QR rustig in beeld.', 'Wacht tot de asset opent.'], note: 'QR-locatie: meestal onder- of achterkant.', visuals: [
          { image: 'scanCamera', label: '2A', fit: 'cover', position: 'center 51%', caption: 'Camera met QR-label en apparaatcontext.' },
        ] },
        { number: '3', title: 'Zoek handmatig', body: ['Als scannen niet lukt: zoek op asset tag, QR-code of ondersteund serienummer.'], visuals: [
          { image: 'searchField', label: '3A', fit: 'cover', position: 'center 0%', caption: 'Typ de code in de zoekbalk.' },
          { image: 'searchResult', label: '3B', fit: 'cover', position: 'center 82%', caption: 'Kies het passende resultaat.' },
        ] },
        { number: '4', title: 'Controleer de asset', body: ['Vergelijk titel, asset tag, model en apparaat.'], stop: 'STOP als asset tag, model of apparaat niet overeenkomt.', visuals: [
          { image: 'assetVerify', label: '4A', fit: 'cover', position: 'center 29%', className: 'title-crop', caption: 'Controleer de titel van de geopende asset.' },
          { image: 'assetDetail', label: '4B', fit: 'cover', position: 'center 72%', caption: 'Controleer asset tag, status en model.' },
        ] },
      ],
      helpTitle: 'Hulp bij vinden en openen',
      help: [
        { title: 'Camera werkt niet', body: 'Gebruik handmatig zoeken.' },
        { title: 'QR beschadigd', body: 'Zoek op tag of QR-waarde.' },
        { title: 'Geen resultaat', body: 'Controleer de code en vraag hulp.' },
        { title: 'Verkeerde asset', body: 'Wijzig niets; vraag een supervisor.' },
      ],
      done: 'De juiste assetpagina is open en komt overeen met het fysieke apparaat.',
      related: [ref('AC', 'AC-01', 'Login'), ref('WF', 'WF-01', 'Workflow starten'), ref('HELP', 'HELP-01', 'Hulp')],
    }],
  },
  {
    slug: 'AST-02-refurbishment-route-v5-draft',
    pages: [{
      family: 'AST', code: 'AST-02', title: 'Refurbishment-route', version: 'Draft v5', date: '2026-07-23', kind: 'route',
      subtitle: 'Kies de juiste taakgids voor de fase waarin het apparaat zich bevindt',
      role: 'Refurbisher / senior refurbisher', needed: 'Apparaat + taakgidsen',
      prerequisite: ref('AC', 'AC-01', 'Login'),
      route: [
        { ref: ref('AC', 'AC-01', 'Login'), text: 'Meld aan en controleer het dashboard.' },
        { ref: ref('SC', 'SC-01', 'Asset openen'), text: 'Vind het apparaat en controleer de identiteit.' },
        { ref: ref('WF', 'WF-01', 'Workflow starten'), text: 'Kies en start de juiste workflow een keer.' },
        { ref: ref('WF', 'WF-02', 'Workflow uitvoeren'), text: 'Voer controles uit en sla eerlijke resultaten op.' },
        { ref: ref('AST', 'AST-04', 'Werk overdragen'), text: 'Controleer werk en zet klaar voor beoordeling.' },
        { ref: ref('AST', 'AST-05', 'Vrijgeven'), text: 'Supervisor beoordeelt en bepaalt de eindstatus.' },
      ],
      helpTitle: 'Afwijkende route',
      help: [
        { title: 'Componentwerk nodig', body: 'Gebruik CMP-01, CMP-02 of CMP-04 en keer terug naar WF-02.' },
        { title: 'Probleem of mismatch', body: 'Stop en gebruik HELP-01.' },
      ],
      helpBranch: {
        title: 'Asset nog niet geregistreerd?',
        ref: ref('AST', 'AST-03', 'Registreren en labelen'),
        body: 'Keer daarna terug naar SC-01.',
      },
      done: 'Je weet in welke fase het apparaat zit en welke taakgids als volgende nodig is.',
      related: [ref('AST', 'AST-03', 'Registreren'), ref('CMP', 'CMP-01', 'Bestaand plaatsen'), ref('HELP', 'HELP-01', 'Hulp')],
    }],
  },
  {
    slug: 'AST-03-register-label-v1-draft',
    pages: [
      {
        family: 'AST', code: 'AST-03', title: 'Asset registreren', version: 'Draft v1', pageLabel: 'Voorzijde 1 van 2',
        subtitle: 'Maak een record voor bekend hardware zonder te gokken of te dupliceren',
        role: 'Supervisor / asset creator', needed: 'Apparaat + asset tag + serie/modelgegevens',
        prerequisite: ref('AC', 'AC-01', 'Login'), layout: 'two',
        steps: [
          { number: '1', title: 'Open Nieuw asset', body: ['Open Apparaten en kies Nieuwe aanmaken.'], visuals: [
            { image: 'assetIndex', label: '1A', fit: 'cover', position: 'center 8%', caption: 'Apparatenlijst met Nieuwe aanmaken in de bovenbalk.' },
          ] },
          { number: '2', title: 'Vul identiteit in', body: ['Vul asset tag en serienummer in.', 'Kies categorie, model en variant.'], stop: 'STOP als model, variant of asset tag onduidelijk is.', visuals: [
            { gap: 'Registratieformulier: asset tag, serienummer, categorie, model en variant.', caption: 'Vervang door een gecontroleerde brede formulieropname.' },
          ] },
          { number: '3', title: 'Vul plaatsing in', body: ['Kies afgesproken status en locatie.', 'Voeg alleen noodzakelijke notities toe.'], visuals: [
            { gap: 'Registratieformulier: status, kwaliteit en locatie met voldoende context.', caption: 'Exacte velden moeten op de releaseomgeving worden bevestigd.' },
          ] },
          { number: '4', title: 'Sla eenmaal op', body: ['Controleer de invoer en sla een keer op.', 'Herken de nieuwe assetpagina.'], visuals: [
            { image: 'assetVerify', label: '4A', fit: 'cover', position: 'center 29%', className: 'title-crop', caption: 'Na opslaan: titel en asset tag zijn zichtbaar.' },
          ] },
        ],
        helpTitle: 'Hulp bij registreren',
        help: [
          { title: 'Mogelijk duplicaat', body: 'Stop; zoek eerst op tag en serienummer.' },
          { title: 'Model ontbreekt', body: 'Vraag catalogusbeheer; kies niet zomaar een model.' },
          { title: 'Geen rechten', body: 'Vraag supervisor of beheerder.' },
        ],
        done: 'Een uniek assetrecord is opgeslagen met de gecontroleerde identiteit en plaatsing.',
        related: [ref('AC', 'AC-01', 'Login'), ref('SC', 'SC-01', 'Asset vinden'), ref('HELP', 'HELP-01', 'Hulp')],
      },
      {
        family: 'AST', code: 'AST-03', title: 'Label printen en controleren', version: 'Draft v1', pageLabel: 'Achterzijde 2 van 2',
        subtitle: 'Print, plaats en test het fysieke QR-label van de geregistreerde asset',
        role: 'Supervisor / asset creator', needed: 'Opgeslagen asset + labelprinter + apparaat',
        prerequisite: ref('AST', 'AST-03', 'Voorzijde'), layout: 'two',
        steps: [
          { number: '1', title: 'Open de opgeslagen asset', body: ['Controleer titel en asset tag voordat je print.'], visuals: [
            { image: 'assetVerify', label: '1A', fit: 'cover', position: 'center 29%', className: 'title-crop', caption: 'Geopende asset met herkenbare titel.' },
          ] },
          { number: '2', title: 'Print het QR-label', body: ['Kies het afgesproken label en de juiste printer.', 'Print eenmaal.'], visuals: [
            { image: 'assetLabel', label: '2A', fit: 'cover', position: 'center 48%', caption: 'QR-label, template, printerlocatie en printknop.' },
          ] },
          { number: '3', title: 'Plaats het label', body: ['Plaats op de afgesproken zichtbare plek.', 'Bedek geen ventilatie, poort, schroef of servicelabel.'], note: 'Veelvoorkomend: onder- of achterkant.', visuals: [
            { image: 'scanCamera', label: '3A', fit: 'cover', position: 'center 51%', caption: 'Voorbeeld van QR-label op de onderzijde.' },
          ] },
          { number: '4', title: 'Scan ter controle', body: ['Gebruik SC-01.', 'Controleer dat het label dezelfde asset opent.'], stop: 'STOP als het label een andere asset opent.', visuals: [
            { image: 'assetDetail', label: '4A', fit: 'cover', position: 'center 74%', caption: 'Controleer asset tag en status na de scan.' },
          ] },
        ],
        helpTitle: 'Hulp bij labelen',
        help: [
          { title: 'Printer werkt niet', body: 'Stop na een poging en vraag ondersteuning.' },
          { title: 'Label beschadigd', body: 'Print een nieuw label; plak geen onleesbaar label.' },
          { title: 'Verkeerde asset', body: 'Verwijder het foute label niet op eigen initiatief; vraag hulp.' },
        ],
        done: 'Het label zit veilig op het apparaat en opent bij scannen dezelfde asset.',
        related: [ref('SC', 'SC-01', 'Asset vinden'), ref('AST', 'AST-02', 'Refurb route'), ref('HELP', 'HELP-01', 'Hulp')],
      },
    ],
  },
  {
    slug: 'AST-04-complete-handoff-v1-draft',
    pages: [{
      family: 'AST', code: 'AST-04', title: 'Werk afronden en overdragen', version: 'Draft v1',
      subtitle: 'Controleer operatorwerk en zet het apparaat klaar voor supervisorbeoordeling',
      role: 'Senior refurbisher', needed: 'Gecontroleerde asset + afgeronde werkresultaten',
      prerequisite: ref('WF', 'WF-02', 'Workflow afronden'), layout: 'two',
      steps: [
        { number: '1', title: 'Controleer workflow', body: ['Alle vereiste kaarten hebben een opgeslagen resultaat.', 'Er staat geen verplicht werk open.'], stop: 'STOP bij ontbrekende of tegenstrijdige resultaten.', visuals: [
          { image: 'workflowResults1', label: '1A', fit: 'cover', position: 'center 58%', caption: 'Workflowoverzicht met opgeslagen resultaten.' },
        ] },
        { number: '2', title: 'Controleer asset', body: ['Vergelijk apparaat, label, onderdelen, notities en zichtbare waarschuwingen.'], stop: 'STOP bij een fysieke of digitale mismatch.', visuals: [
          { image: 'componentRows', label: '2A', fit: 'cover', position: 'center 20%', caption: 'Controleer relevante componentregels.' },
        ] },
        { number: '3', title: 'Zet klaar voor review', body: ['Gebruik de afgesproken overdrachtsstatus en locatie.'], note: 'Exacte statusnaam moet nog worden bevestigd.', visuals: [
          { image: 'assetDetail', label: '3A', fit: 'cover', position: 'center 80%', caption: 'Status en kwaliteit op de assetpagina.' },
        ] },
        { number: '4', title: 'Draag fysiek over', body: ['Plaats het apparaat op de afgesproken reviewplek.', 'Meld de overdracht volgens lokale afspraak.'], stop: 'Kies geen verkoop- of vrijgavestatus die voor supervisors is.', visuals: [
          { gap: 'Foto of herkenbare opname van de afgesproken reviewlocatie en overdrachtsmarkering.', caption: 'Lokale fysieke overdracht moet nog worden vastgelegd.' },
        ] },
      ],
      helpTitle: 'Hulp bij overdragen',
      help: [
        { title: 'Werk niet compleet', body: 'Ga terug naar WF-02; draag nog niet over.' },
        { title: 'Onderdeel mismatch', body: 'Gebruik de passende CMP-gids of vraag hulp.' },
        { title: 'Status onduidelijk', body: 'Vraag supervisor; kies niet op gevoel.' },
      ],
      done: 'Digitaal en fysiek werk is compleet en de asset staat klaar voor AST-05.',
      related: [ref('WF', 'WF-02', 'Workflow afronden'), ref('CMP', 'CMP-04', 'Naar tray'), ref('AST', 'AST-05', 'Vrijgeven'), ref('HELP', 'HELP-01', 'Hulp')],
    }],
  },
  {
    slug: 'AST-05-review-release-v1-draft',
    pages: [{
      family: 'AST', code: 'AST-05', title: 'Asset beoordelen en vrijgeven', version: 'Draft v1',
      subtitle: 'Beoordeel bewijs en apparaat voordat je de goedgekeurde eindstatus toepast',
      role: 'Supervisor', needed: 'Asset in review + toegang tot vrijgavevelden',
      prerequisite: ref('AST', 'AST-04', 'Werk overdragen'), layout: 'rows',
      steps: [
        { number: '1', title: 'Open de overdracht', body: ['Open de juiste asset en controleer de reviewstatus.'], visuals: [
          { image: 'assetVerify', label: '1A', fit: 'cover', position: 'center 29%', className: 'title-crop', caption: 'Titel en asset tag.' },
        ] },
        { number: '2', title: 'Controleer bewijs', body: ['Bekijk workflowresultaten, notities en waarschuwingen.'], stop: 'STOP als bewijs ontbreekt of elkaar tegenspreekt.', visuals: [
          { image: 'workflowResults2', label: '2A', fit: 'cover', position: 'center 45%', caption: 'Resultatenoverzicht.' },
        ] },
        { number: '3', title: 'Controleer apparaat', body: ['Vergelijk fysieke conditie, label, onderdelen en bestemming.'], visuals: [
          { image: 'scanCamera', label: '3A', fit: 'cover', position: 'center 51%', caption: 'Fysieke identiteit en label.' },
        ] },
        { number: '4', title: 'Beslis', body: ['Stuur terug voor correctie of kies de goedgekeurde vrijgavestatus.'], note: 'Exacte statusnaam moet nog worden bevestigd.', visuals: [
          { image: 'assetDetail', label: '4A', fit: 'cover', position: 'center 80%', caption: 'Status- en kwaliteitsvelden.' },
        ] },
        { number: '5', title: 'Controleer eindstatus', body: ['Controleer dat besluit, status en bestemming zichtbaar zijn.'], stop: 'Geen vrijgave bij open waarschuwing of mismatch.', visuals: [
          { gap: 'Bevestigde eindstatus en bestemming na supervisorbesluit.', caption: 'End-state capture vereist voor goedkeuring.' },
        ] },
      ],
      helpTitle: 'Hulp bij beoordeling',
      help: [
        { title: 'Bewijs ontbreekt', body: 'Stuur terug naar AST-04/WF-02.' },
        { title: 'Mismatch', body: 'Wijzig niet om het passend te maken; laat corrigeren.' },
        { title: 'Status onduidelijk', body: 'Gebruik de afgesproken vrijgavecriteria of vraag beheerder.' },
      ],
      done: 'Het supervisorbesluit is opgeslagen en een vrijgegeven asset toont de goedgekeurde eindstatus.',
      related: [ref('AST', 'AST-04', 'Werk overdragen'), ref('WF', 'WF-02', 'Workflow'), ref('HELP', 'HELP-01', 'Hulp')],
    }],
  },
  {
    slug: 'WF-01-start-workflow-v5-draft',
    pages: [{
      family: 'WF', code: 'WF-01', title: 'Workflow starten', version: 'Draft v5',
      subtitle: 'Kies het juiste profiel en start de workflow een keer',
      role: 'Senior refurbisher', needed: 'Gecontroleerde open asset + workflowrechten',
      prerequisite: ref('SC', 'SC-01', 'Asset geopend'), layout: 'two',
      steps: [
        { number: '1', title: 'Open tab Tests', body: ['Controleer eerst de assettitel.', 'Open daarna het test-icoon.'], visuals: [
          { image: 'assetVerify', label: '1A', fit: 'cover', position: 'center 29%', className: 'title-crop', caption: 'Controleer de assettitel.' },
          { image: 'workflowWide', label: '1B', fit: 'cover', position: 'center 43%', className: 'tab-crop', caption: 'Tik daarna op het test-icoon.' },
        ] },
        { number: '2', title: 'Kies profiel', body: ['Kies het afgesproken profiel voor apparaat en taak.'], stop: 'STOP als het juiste profiel onduidelijk is.', visuals: [
          { image: 'workflowWide', label: '2A', fit: 'cover', position: 'center 44%', className: 'control-crop', caption: 'Workflowprofiel-selector.' },
        ] },
        { number: '3', title: 'Start eenmaal', body: ['Controleer eerst of al een workflow actief is.', 'Tik eenmaal op Nieuwe workflow starten.'], stop: 'Niet opnieuw starten als al een run actief is.', visuals: [
          { image: 'workflowWide', label: '3A', fit: 'cover', position: 'center 79%', className: 'control-crop', caption: 'Startknop en bestaande runs.' },
        ] },
        { number: '4', title: 'Controleer kaarten', body: ['Wacht tot resultaatkaarten zichtbaar zijn.', 'Ga daarna verder met WF-02.'], visuals: [
          { image: 'workflowCards', label: '4A', fit: 'cover', position: 'center 18%', caption: 'Actieve resultaatkaarten.' },
        ] },
      ],
      helpTitle: 'Hulp bij workflow starten',
      help: [
        { title: 'Geen knop', body: 'Controleer rechten en assetstatus.' },
        { title: 'Verkeerd profiel', body: 'Stop en vraag supervisor.' },
        { title: 'Al gestart', body: 'Open de bestaande run; start niet opnieuw.' },
        { title: 'Geen kaarten', body: 'Vraag supervisor of gebruik HELP-01.' },
      ],
      done: 'De gekozen workflow is actief en de verwachte resultaatkaarten zijn zichtbaar.',
      related: [ref('SC', 'SC-01', 'Asset openen'), ref('WF', 'WF-02', 'Workflow uitvoeren'), ref('HELP', 'HELP-01', 'Hulp')],
    }],
  },
  {
    slug: 'WF-02-complete-workflow-v3-draft',
    pages: [{
      family: 'WF', code: 'WF-02', title: 'Workflow uitvoeren en afronden', version: 'Draft v3',
      subtitle: 'Voer elke controle uit en sla het eerlijke resultaat op',
      role: 'Senior refurbisher', needed: 'Actieve workflow + fysiek apparaat',
      prerequisite: ref('WF', 'WF-01', 'Workflow gestart'), layout: 'rows',
      steps: [
        { number: '1', title: 'Lees en voer uit', body: ['Lees de kaart en instructie.', 'Voer de fysieke of softwarecontrole uit.'], visuals: [
          { image: 'workflowCards', label: '1A', fit: 'cover', position: 'center 23%', caption: 'Workflowkaart met instructieknop.' },
        ] },
        { number: '2', title: 'Kies het resultaat', body: ['Kies Geslaagd/Mislukt of Gedaan/Niet gedaan naar waarheid.'], stop: 'Markeer nooit zonder de controle uit te voeren.', visuals: [
          { image: 'workflowCards', label: '2A', fit: 'cover', position: 'center 43%', caption: 'Resultaatknoppen op een kaart.' },
        ] },
        { number: '3', title: 'Leg afwijking vast', body: ['Voeg een korte notitie toe bij mislukking of uitzondering.'], note: 'Foto-instructies zijn bewust nog niet opgenomen.', visuals: [
          { image: 'workflowCards', label: '3A', fit: 'cover', position: 'center 50%', caption: 'Notitiegebied onder het resultaat.' },
        ] },
        { number: '4', title: 'Ga door', body: ['Herhaal voor alle vereiste items.', 'Controleer dat ieder resultaat is opgeslagen.'], visuals: [
          { image: 'workflowResults1', label: '4A', fit: 'cover', position: 'center 56%', caption: 'Meerdere resultaatregels.' },
        ] },
        { number: '5', title: 'Controleer afronding', body: ['Geen verplicht item blijft onbeantwoord.', 'Controleer de zichtbare eindstatus.'], stop: 'STOP bij ontbrekende, onduidelijke of tegenstrijdige resultaten.', visuals: [
          { gap: 'Volledig afgeronde workflow met bevestigde eindstatus.', caption: 'End-state capture vereist voor goedkeuring.' },
        ] },
      ],
      helpTitle: 'Hulp bij uitvoeren',
      help: [
        { title: 'Instructie onduidelijk', body: 'Stop en vraag supervisor.' },
        { title: 'Controle mislukt', body: 'Kies Mislukt en noteer kort waarom.' },
        { title: 'Resultaat niet opgeslagen', body: 'Ververs niet direct; controleer eerst de kaartstatus.' },
      ],
      done: 'Alle vereiste items hebben een eerlijk opgeslagen resultaat en de workflow is afgerond.',
      related: [ref('WF', 'WF-01', 'Workflow starten'), ref('CMP', 'CMP-01', 'Component plaatsen'), ref('AST', 'AST-04', 'Werk overdragen'), ref('HELP', 'HELP-01', 'Hulp')],
    }],
  },
  {
    slug: 'CMP-01-install-existing-v1-draft',
    pages: [{
      family: 'CMP', code: 'CMP-01', title: 'Bestaand component plaatsen', version: 'Draft v1',
      subtitle: 'Koppel een bestaand gevolgd onderdeel aan de juiste asset',
      role: 'Bevoegde refurbisher', needed: 'Open asset + fysiek onderdeel uit tray/opslag',
      prerequisite: ref('SC', 'SC-01', 'Asset geopend'), layout: 'rows',
      steps: [
        { number: '1', title: 'Open Componenten', body: ['Open het componenttabblad op de gecontroleerde asset.'], visuals: [
          { image: 'componentsWide', label: '1A', fit: 'cover', position: 'center 38%', className: 'tab-crop', caption: 'Componenttab en toevoegknop.' },
        ] },
        { number: '2', title: 'Kies bestaand', body: ['Open Add / Install Component.', 'Kies het pad voor een bestaand gevolgd onderdeel.'], visuals: [
          { gap: 'Add / Install Component met de keuze voor bestaand onderdeel.', caption: 'Installatieformulier nog gecontroleerd vastleggen.' },
        ] },
        { number: '3', title: 'Controleer identiteit', body: ['Vergelijk type, tag/serienummer, conditie en brontray met het fysieke onderdeel.'], stop: 'STOP als onderdeel of identiteit niet overeenkomt.', visuals: [
          { image: 'componentList', label: '3A', fit: 'cover', position: 'center 29%', caption: 'Gevolgde componenten met tag, naam en serienummer.' },
        ] },
        { number: '4', title: 'Plaats en bevestig', body: ['Plaats het onderdeel fysiek.', 'Bevestig daarna de digitale koppeling eenmaal.'], visuals: [
          { gap: 'Bevestiging van bestaand component voor de gekozen asset.', caption: 'Geen formulierstatus tekenen of nabootsen.' },
        ] },
        { number: '5', title: 'Controleer de asset', body: ['Hetzelfde component staat nu op de juiste asset met de juiste identiteit.'], visuals: [
          { image: 'componentRows', label: '5A', fit: 'cover', position: 'center 9%', caption: 'Componentregel op de asset.' },
        ] },
      ],
      helpTitle: 'Hulp bij bestaand component',
      help: [
        { title: 'Niet gevonden', body: 'Controleer tray/tag; registreer niet meteen een duplicaat.' },
        { title: 'Serienummer wijkt af', body: 'Stop en vraag supervisor.' },
        { title: 'Geen rechten', body: 'Vraag een bevoegde rol.' },
      ],
      done: 'Het fysieke onderdeel is geplaatst en hetzelfde gevolgde component staat op de juiste asset.',
      related: [ref('SC', 'SC-01', 'Asset openen'), ref('CMP', 'CMP-02', 'Nieuw plaatsen'), ref('CMP', 'CMP-04', 'Naar tray'), ref('HELP', 'HELP-01', 'Hulp')],
    }],
  },
  {
    slug: 'CMP-02-register-install-v1-draft',
    pages: [{
      family: 'CMP', code: 'CMP-02', title: 'Nieuw component registreren en plaatsen', version: 'Draft v1',
      subtitle: 'Registreer een nieuw fysiek onderdeel en koppel het aan de juiste asset',
      role: 'Bevoegde refurbisher', needed: 'Open asset + nieuw fysiek onderdeel',
      prerequisite: ref('SC', 'SC-01', 'Asset geopend'), layout: 'rows',
      steps: [
        { number: '1', title: 'Open Nieuw component', body: ['Open Componenten en kies Add / Install Component.', 'Open daarna Nieuw component.'], visuals: [
          { image: 'componentsWide', label: '1A', fit: 'cover', position: 'center 38%', className: 'tab-crop', caption: 'Componenttab met toevoegknop.' },
        ] },
        { number: '2', title: 'Kies registratie', body: ['Gebruik Uit definitie voor een bekend catalogusonderdeel.', 'Gebruik Aangepast alleen voor een goedgekeurd eenmalig onderdeel.'], stop: 'STOP als onduidelijk is welke keuze hoort.', visuals: [
          { gap: 'Nieuw-componentpaneel met Uit definitie en Aangepast naast elkaar.', caption: 'CMP-03 is hier een alternatief, geen aparte gids.' },
        ] },
        { number: '3', title: 'Vul identiteit in', body: ['Vul definitie/type, tag of serienummer, conditie en vereiste details in.'], stop: 'STOP bij een duplicaat of identiteit die niet klopt.', visuals: [
          { gap: 'Registratievelden voor definitie/custom, serienummer, tag en conditie.', caption: 'Brede formulieropname met context vereist.' },
        ] },
        { number: '4', title: 'Controleer en plaats', body: ['Vergelijk invoer met het fysieke onderdeel.', 'Plaats fysiek en sla eenmaal op.'], visuals: [
          { image: 'componentList', label: '4A', fit: 'cover', position: 'center 29%', caption: 'Voorbeeld van geregistreerde componentidentiteit.' },
        ] },
        { number: '5', title: 'Controleer de asset', body: ['Een componentrecord met de juiste identiteit staat op de juiste asset.'], visuals: [
          { image: 'componentRows', label: '5A', fit: 'cover', position: 'center 9%', caption: 'Nieuwe componentregel op de asset.' },
        ] },
      ],
      helpTitle: 'Hulp bij nieuw component',
      help: [
        { title: 'Definitie ontbreekt', body: 'Vraag supervisor/catalogusbeheer; kies custom niet automatisch.' },
        { title: 'Dubbele tag/serienummer', body: 'Stop en zoek het bestaande record.' },
        { title: 'Geen rechten', body: 'Vraag een bevoegde rol.' },
      ],
      done: 'Een uniek correct componentrecord is geplaatst op de juiste asset.',
      related: [ref('SC', 'SC-01', 'Asset openen'), ref('CMP', 'CMP-01', 'Bestaand plaatsen'), ref('CMP', 'CMP-04', 'Naar tray'), ref('HELP', 'HELP-01', 'Hulp')],
    }],
  },
  {
    slug: 'CMP-04-component-to-tray-v3-draft',
    pages: [{
      family: 'CMP', code: 'CMP-04', title: 'Component naar tray', version: 'Draft v3',
      subtitle: 'Verwijder het juiste onderdeel en behoud identiteit en bestemming',
      role: 'Bevoegde refurbisher', needed: 'Open asset + onderdeel + bedoelde tray/opslag',
      prerequisite: ref('SC', 'SC-01', 'Asset geopend'), layout: 'rows',
      steps: [
        { number: '1', title: 'Open Componenten', body: ['Open het componenttabblad van de gecontroleerde asset.'], visuals: [
          { image: 'componentsWide', label: '1A', fit: 'cover', position: 'center 38%', className: 'tab-crop', caption: 'Componenttab op de asset.' },
        ] },
        { number: '2', title: 'Kies het onderdeel', body: ['Vergelijk de componentregel met het fysieke onderdeel.', 'Tik op Naar tray bij precies die regel.'], stop: 'STOP als type, tag of serienummer niet overeenkomt.', visuals: [
          { image: 'componentRows', label: '2A', fit: 'cover', position: 'center 25%', caption: 'Componentregel met Naar tray.' },
        ] },
        { number: '3', title: 'Controleer identiteit', body: ['Controleer component en serienummer in het venster.', 'Leg een serienummer alleen vast als dat klopt.'], visuals: [
          { image: 'trayLocked', label: '3A', fit: 'cover', position: 'center 17%', caption: 'Naar tray: component en serienummer.' },
          { image: 'trayUnlocked', label: '3B', fit: 'cover', position: 'center 17%', caption: 'Ontgrendeld serienummerveld indien nodig.' },
        ] },
        { number: '4', title: 'Verwijder en bevestig', body: ['Verwijder het fysieke onderdeel.', 'Plaats het in de bedoelde tray en bevestig eenmaal.'], stop: 'STOP als bestemming of identiteit onjuist is.', visuals: [
          { image: 'trayLocked', label: '4A', fit: 'cover', position: 'center 62%', caption: 'Bevestigingsknop Naar tray.' },
        ] },
        { number: '5', title: 'Controleer eindstaat', body: ['Onderdeel staat niet meer op de asset.', 'Het is zichtbaar in de bedoelde tray/opslag.'], visuals: [
          { gap: 'Component niet meer op asset en zichtbaar in de gekozen tray/opslag.', caption: 'Gecontroleerde end-state capture vereist.' },
        ] },
      ],
      helpTitle: 'Hulp bij naar tray',
      help: [
        { title: 'Serienummer ontbreekt', body: 'Controleer fysiek; verzin geen nummer.' },
        { title: 'Verkeerde component', body: 'Annuleer en kies de juiste regel.' },
        { title: 'Bestemming onduidelijk', body: 'Vraag supervisor voor bevestigen.' },
      ],
      done: 'Het onderdeel ligt in de bedoelde tray/opslag en is niet meer aan de asset gekoppeld.',
      related: [ref('SC', 'SC-01', 'Asset openen'), ref('CMP', 'CMP-01', 'Bestaand plaatsen'), ref('WF', 'WF-02', 'Workflow'), ref('HELP', 'HELP-01', 'Hulp')],
    }],
  },
  {
    slug: 'HELP-01-problems-v5-draft',
    pages: [{
      family: 'HELP', code: 'HELP-01', title: 'Problemen en hulp', version: 'Draft v5', kind: 'help',
      subtitle: 'Kies het probleem en voer alleen de veilige herstelactie uit',
      role: 'Iedereen', needed: 'Het apparaat en de gids waarbij het probleem ontstond',
      prerequisite: ref('AC', 'AC-01', 'Login indien mogelijk'),
      problems: [
        { icon: '@', title: 'Geen account', body: 'Vraag beheerder of supervisor om een account.', ref: ref('AC', 'AC-01', 'Login') },
        { icon: '*', title: 'Wachtwoord kwijt', body: 'Alleen een supervisor kan het wachtwoord laten resetten.', ref: ref('AC', 'AC-01', 'Login') },
        { icon: 'P', title: 'Geen telefoon', body: 'Open een browser en ga naar https://snipe.inbit/.', ref: ref('AC', 'AC-01', 'Login') },
        { icon: 'C', title: 'Camera werkt niet', body: 'Gebruik handmatig zoeken op asset tag of QR-waarde.', ref: ref('SC', 'SC-01', 'Asset vinden') },
        { icon: 'QR', title: 'QR beschadigd', body: 'Zoek handmatig. Laat het label later gecontroleerd vervangen.', ref: ref('SC', 'SC-01', 'Asset vinden') },
        { icon: 'X', title: 'Verkeerde asset', body: 'Wijzig niets. Leg apparaat en scherm voor aan een supervisor.', ref: ref('SC', 'SC-01', 'Asset vinden') },
        { icon: 'WF', title: 'Geen workflow of kaarten', body: 'Controleer asset en rechten. Start niet meerdere keren.', ref: ref('WF', 'WF-01', 'Workflow starten') },
        { icon: '!', title: 'Geen rechten', body: 'Vraag een bevoegde rol; probeer niet via een andere route.', ref: ref('AST', 'AST-02', 'Route') },
        { icon: 'PR', title: 'Printer of label faalt', body: 'Stop na een herhaalpoging en vraag lokale ondersteuning.', ref: ref('AST', 'AST-03', 'Labelen') },
        { icon: '2x', title: 'Dubbele tag of serienummer', body: 'Stop. Zoek het bestaande record en vraag supervisor.', ref: ref('AST', 'AST-03', 'Registreren') },
      ],
      done: 'Je weet welke veilige herstelactie, gids of persoon nodig is voordat je verdergaat.',
      related: [ref('AC', 'AC-01', 'Login'), ref('SC', 'SC-01', 'Asset vinden'), ref('AST', 'AST-02', 'Route')],
    }],
  },
];

fs.mkdirSync(outDir, { recursive: true });
fs.mkdirSync(repoPdfDir, { recursive: true });

const browser = await chromium.launch(browserLaunchOptions({ headless: true }));
const context = await browser.newContext({
  viewport: { width: 900, height: 1300 },
  deviceScaleFactor: 2,
});

const outputs = [];
try {
  for (const guide of guides) {
    const htmlPath = path.join(outDir, `${guide.slug}.html`);
    const pdfPath = path.join(outDir, `${guide.slug}-proof.pdf`);
    fs.writeFileSync(htmlPath, documentHtml(guide.pages, guide.slug), 'utf8');

    const page = await context.newPage();
    await page.goto(`file:///${htmlPath.replace(/\\/g, '/')}`, { waitUntil: 'load' });
    const pageLocators = page.locator('.guide-page');
    const pageCount = await pageLocators.count();
    const pngs = [];
    for (let index = 0; index < pageCount; index += 1) {
      const suffix = pageCount === 1 ? '' : `-page-${index + 1}`;
      const pngPath = path.join(outDir, `${guide.slug}${suffix}-proof.png`);
      await pageLocators.nth(index).screenshot({ path: pngPath });
      pngs.push(pngPath);
    }
    await page.pdf({
      path: pdfPath,
      printBackground: true,
      preferCSSPageSize: true,
      margin: { top: 0, right: 0, bottom: 0, left: 0 },
    });
    await page.close();

    outputs.push({ slug: guide.slug, pageCount, html: htmlPath, pdf: pdfPath, pngs });
  }

  const combinedPages = guides.flatMap((guide) => guide.pages);
  const combinedHtml = path.join(outDir, `${combinedBaseName}.html`);
  const combinedPdf = path.join(outDir, `${combinedBaseName}.pdf`);
  fs.writeFileSync(combinedHtml, documentHtml(combinedPages, 'Operator guides revised set draft'), 'utf8');
  const combinedPage = await context.newPage();
  await combinedPage.goto(`file:///${combinedHtml.replace(/\\/g, '/')}`, { waitUntil: 'load' });
  await combinedPage.pdf({
    path: combinedPdf,
    printBackground: true,
    preferCSSPageSize: true,
    margin: { top: 0, right: 0, bottom: 0, left: 0 },
  });
  await combinedPage.close();
  fs.copyFileSync(combinedPdf, path.join(repoPdfDir, path.basename(combinedPdf)));

  const missingSources = Object.entries(images).filter(([, image]) => !image.data).map(([key, image]) => ({ key, file: image.file }));
  const manifest = {
    generatedAt: new Date().toISOString(),
    operatorFacingUrl: 'https://snipe.inbit/',
    outputDirectory: outDir,
    guideCount: guides.length,
    pageCount: combinedPages.length,
    outputs,
    combined: { html: combinedHtml, pdf: combinedPdf },
    missingSources,
    evidenceGaps: [
      'AST-03 registration form and exact status/location fields',
      'AST-04 physical review location and exact handoff status',
      'AST-05 confirmed supervisor release end state and exact status label',
      'WF-02 completed workflow end state',
      'CMP-01 existing-component install form and confirmed end state',
      'CMP-02 definition/custom registration form',
      'CMP-04 confirmed tray/storage end state',
    ],
  };
  fs.writeFileSync(path.join(outDir, 'generation-manifest.json'), `${JSON.stringify(manifest, null, 2)}\n`, 'utf8');

  const summary = [
    '# Revised Operator Guide Set',
    '',
    `Generated: ${today}`,
    '',
    `- Guides: ${guides.length}`,
    `- A4 pages: ${combinedPages.length}`,
    `- Combined PDF: ${combinedPdf}`,
    `- Operator-facing URL in guide copy: https://snipe.inbit/`,
    '- Digital-guide QR areas are explicitly labelled draft placeholders; no fake scannable pattern is printed.',
    '- Orange workflow-attention banners are not instructional content; crops target the controls below them.',
    '',
    '## Individual Guides',
    '',
    ...outputs.map((output) => `- ${output.slug}: ${output.pageCount} page(s)`),
    '',
    '## Evidence Gaps Before Base Approval',
    '',
    ...manifest.evidenceGaps.map((gap) => `- ${gap}`),
    '',
    'These gaps are shown as labelled evidence slots in the review drafts. They must be replaced with verified screenshots or confirmed local process evidence before Base approved.',
    '',
  ].join('\n');
  fs.writeFileSync(path.join(outDir, 'revised-guide-set-summary.md'), summary, 'utf8');

  console.log(JSON.stringify({ outDir, combinedPdf, guides: guides.length, pages: combinedPages.length, missingSources: missingSources.length }));
} finally {
  await context.close();
  await browser.close();
}
