import fs from 'node:fs';
import path from 'node:path';
import {
  browserLaunchOptions,
  evidencePath,
  guideOutputDir,
  loadGuideDependency,
} from './lib/guide-paths.mjs';

const { chromium } = loadGuideDependency('playwright');

const today = '2026-07-21';
const liveUrl = 'https://snipe.inbit/';
const outDir = process.env.SNIPEIT_GUIDE_OUT_DIR || guideOutputDir('SC-01-v10');

const sourceFiles = {
  acDashboard: evidencePath('DASH-MOBILE-01'),
  mobileCameraQr: evidencePath('SCAN-CAMERA-QR-01'),
  searchFallback: evidencePath('SEARCH-FIELD-01'),
  searchResult: evidencePath('SEARCH-RESULT-01'),
  assetVerify: evidencePath('ASSET-VERIFY-01'),
};

const outputs = {
  svg: path.join(outDir, 'SC-01-find-open-asset-snipe-v10.svg'),
  html: path.join(outDir, 'SC-01-find-open-asset-snipe-v10.html'),
  png: path.join(outDir, 'SC-01-find-open-asset-snipe-v10-proof.png'),
  pdf: path.join(outDir, 'SC-01-find-open-asset-snipe-v10-proof.pdf'),
  summary: path.join(outDir, 'SC-01-find-open-asset-snipe-v10-summary.md'),
};

const colors = {
  ink: '#102033',
  muted: '#53657A',
  line: '#C8D5E2',
  faint: '#F6F9FC',
  sc: '#0E8A75',
  scDark: '#087162',
  scSoft: '#ECFDF5',
  ac: '#2563EB',
  acSoft: '#EFF6FF',
  help: '#EF3340',
  helpSoft: '#FFF1F3',
  green: '#059669',
  greenSoft: '#ECFDF5',
  orange: '#F59E0B',
  orangeSoft: '#FFF7ED',
  white: '#FFFFFF',
};

function xml(value) {
  return String(value)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

function imageSize(buffer) {
  if (
    buffer[0] === 0x89 &&
    buffer[1] === 0x50 &&
    buffer[2] === 0x4e &&
    buffer[3] === 0x47
  ) {
    return { width: buffer.readUInt32BE(16), height: buffer.readUInt32BE(20), mime: 'image/png' };
  }

  if (buffer[0] === 0xff && buffer[1] === 0xd8) {
    let offset = 2;
    while (offset < buffer.length) {
      if (buffer[offset] !== 0xff) {
        offset += 1;
        continue;
      }
      const marker = buffer[offset + 1];
      const length = buffer.readUInt16BE(offset + 2);
      if (marker >= 0xc0 && marker <= 0xcf && ![0xc4, 0xc8, 0xcc].includes(marker)) {
        return {
          height: buffer.readUInt16BE(offset + 5),
          width: buffer.readUInt16BE(offset + 7),
          mime: 'image/jpeg',
        };
      }
      offset += 2 + length;
    }
  }

  throw new Error('Unsupported image format');
}

function loadImage(file) {
  const buffer = fs.readFileSync(file);
  const size = imageSize(buffer);
  return {
    ...size,
    href: `data:${size.mime};base64,${buffer.toString('base64')}`,
    file,
  };
}

class SvgDoc {
  constructor(images) {
    this.images = images;
    this.defs = [];
    this.parts = [];
    this.uid = 0;
  }

  nextId(prefix) {
    this.uid += 1;
    return `${prefix}-${this.uid}`;
  }

  rect(x, y, w, h, fill = 'none', stroke = 'none', sw = 0, r = 0, extra = '') {
    this.parts.push(
      `<rect x="${x}" y="${y}" width="${w}" height="${h}" rx="${r}" fill="${fill}" stroke="${stroke}" stroke-width="${sw}" ${extra}/>`,
    );
  }

  line(x1, y1, x2, y2, stroke = colors.line, sw = 0.4) {
    this.parts.push(`<line x1="${x1}" y1="${y1}" x2="${x2}" y2="${y2}" stroke="${stroke}" stroke-width="${sw}"/>`);
  }

  circle(cx, cy, r, stroke, sw = 1, fill = 'none', opacity = 1) {
    this.parts.push(`<circle cx="${cx}" cy="${cy}" r="${r}" fill="${fill}" stroke="${stroke}" stroke-width="${sw}" opacity="${opacity}"/>`);
  }

  text(x, y, value, opts = {}) {
    const {
      size = 3.2,
      weight = 400,
      fill = colors.ink,
      anchor = 'start',
      lh = size * 1.35,
      family = 'Arial, Helvetica, sans-serif',
    } = opts;
    const lines = Array.isArray(value) ? value : [value];
    const tspans = lines
      .map((line, index) => `<tspan x="${x}" dy="${index === 0 ? 0 : lh}">${xml(line)}</tspan>`)
      .join('');
    this.parts.push(
      `<text x="${x}" y="${y}" font-family="${family}" font-size="${size}" font-weight="${weight}" fill="${fill}" text-anchor="${anchor}">${tspans}</text>`,
    );
  }

  image(key, frame, crop = null, opts = {}) {
    const img = this.images[key];
    if (!img) throw new Error(`Missing image ${key}`);
    const area = crop ?? { x: 0, y: 0, w: img.width, h: img.height };
    const fit = opts.fit ?? 'cover';
    const scale = fit === 'contain'
      ? Math.min(frame.w / area.w, frame.h / area.h)
      : Math.max(frame.w / area.w, frame.h / area.h);
    const x = frame.x + (frame.w - area.w * scale) / 2 - area.x * scale;
    const y = frame.y + (frame.h - area.h * scale) / 2 - area.y * scale;
    const id = this.nextId('clip');
    const r = opts.r ?? 1.4;
    this.defs.push(`<clipPath id="${id}"><rect x="${frame.x}" y="${frame.y}" width="${frame.w}" height="${frame.h}" rx="${r}"/></clipPath>`);
    this.rect(frame.x, frame.y, frame.w, frame.h, colors.white, colors.line, 0.4, r);
    this.parts.push(`<image href="${img.href}" x="${x}" y="${y}" width="${img.width * scale}" height="${img.height * scale}" clip-path="url(#${id})"/>`);
    this.rect(frame.x, frame.y, frame.w, frame.h, 'none', opts.stroke ?? colors.line, opts.sw ?? 0.4, r);
    return { scale, x, y };
  }

  imageBadge(x, y, label, color = colors.sc) {
    this.parts.push(`<circle cx="${x}" cy="${y}" r="4.15" fill="#FFFFFF" fill-opacity="0.34" stroke="${color}" stroke-opacity="0.88" stroke-width="1.0"/>`);
    this.text(x, y + 0.95, label, { size: 2.05, weight: 850, fill: color, anchor: 'middle' });
  }

  stepBadge(x, y, label, color = colors.sc) {
    this.parts.push(`<circle cx="${x}" cy="${y}" r="7.1" fill="#FFFFFF" fill-opacity="0.86" stroke="${color}" stroke-width="2.0"/>`);
    this.text(x, y + 1.65, label, { size: 4.7, weight: 900, fill: color, anchor: 'middle' });
  }

  guidePrereq(x, y, icon, main, ref, color) {
    this.circle(x + 2.6, y - 0.9, 2.25, color, 0.55, '#FFFFFF', 0.95);
    this.text(x + 2.6, y - 0.1, icon, { size: 1.6, weight: 900, fill: color, anchor: 'middle' });
    this.parts.push(`<text font-family="Arial, Helvetica, sans-serif" font-weight="800" fill="${color}" x="${x + 6.3}" y="${y}"><tspan font-size="2.55">${xml(main)}</tspan><tspan font-size="2.0"> ${xml(ref)}</tspan></text>`);
  }

  chip(x, y, w, icon, label, color, fill = '#FFFFFF') {
    this.rect(x, y, w, 7, fill, color, 0.55, 2.5);
    this.circle(x + 5, y + 3.5, 2.45, color, 0.55, '#FFFFFF');
    this.text(x + 5, y + 4.35, icon, { size: icon.length > 3 ? 1.35 : 1.65, weight: 900, fill: color, anchor: 'middle' });
    this.text(x + 10, y + 4.55, label, { size: 2.35, weight: 800, fill: color });
  }

  helpTile(x, y, title, body, icon = '!') {
    this.rect(x, y, 41.5, 22, colors.orangeSoft, '#FDBA74', 0.45, 2);
    this.circle(x + 5.2, y + 6.2, 3.2, colors.orange, 0.55, '#FFFFFF');
    this.text(x + 5.2, y + 7.35, icon, { size: icon.length > 1 ? 2.1 : 2.9, weight: 900, fill: colors.orange, anchor: 'middle' });
    this.text(x + 10.5, y + 7, title, { size: 2.35, weight: 900, fill: colors.ink });
    this.text(x + 5, y + 15, body, { size: 2.1, fill: colors.ink, lh: 3 });
  }

  qrPlaceholder(x, y, size) {
    this.rect(x, y, size, size, colors.white, colors.ink, 0.6, 0.8);
    const cells = [
      [1, 1], [2, 1], [5, 1], [7, 1], [1, 2], [4, 2], [8, 2],
      [2, 3], [6, 3], [7, 3], [3, 4], [5, 4], [8, 4], [1, 5],
      [4, 5], [6, 5], [2, 6], [5, 6], [7, 6], [8, 7], [1, 8],
      [3, 8], [6, 8], [7, 8],
    ];
    const cell = size / 10;
    for (const [cx, cy] of cells) this.rect(x + cx * cell, y + cy * cell, cell * 0.72, cell * 0.72, colors.ink);
  }

  render() {
    return `<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" width="210mm" height="297mm" viewBox="0 0 210 297">
  <defs>${this.defs.join('\n')}</defs>
  <rect width="210" height="297" fill="#FFFFFF"/>
  ${this.parts.join('\n')}
</svg>`;
  }
}

function drawGuide(images) {
  const doc = new SvgDoc(images);

  doc.rect(12, 13, 2, 16, colors.sc);
  doc.text(18, 22, 'SC-01 Asset vinden en openen', { size: 7.4, weight: 900 });
  doc.text(18, 29, 'Scan of zoek het apparaat en controleer het voordat je iets wijzigt', { size: 3.1, fill: colors.muted });
  doc.rect(161, 12, 37, 11, colors.greenSoft, '#86EFAC', 0.45, 2);
  doc.text(179.5, 18.9, `Draft v10 ${today}`, { size: 2.35, weight: 800, fill: colors.green, anchor: 'middle' });

  doc.rect(12, 39, 186, 18, '#F8FAFC', colors.line, 0.45, 1.6);
  doc.text(17, 47, 'Rol', { size: 2.3, weight: 800, fill: colors.muted });
  doc.text(17, 53, 'Refurbisher', { size: 3.05, weight: 800 });
  doc.text(58, 47, 'Nodig', { size: 2.3, weight: 800, fill: colors.muted });
  doc.text(58, 53, 'Telefoon met camera + QR/asset tag', { size: 3.05, weight: 800 });
  doc.text(142, 47, 'Vooraf', { size: 2.3, weight: 800, fill: colors.muted });
  doc.guidePrereq(142, 53, 'AC', 'Ingelogd', '(AC-01 Login)', colors.ac);

  // Step 1: scanner entry choices.
  const s1 = { x: 12, y: 68, w: 186, h: 48 };
  doc.rect(s1.x, s1.y, s1.w, s1.h, colors.white, colors.line, 0.45, 2);
  doc.stepBadge(s1.x, s1.y, '1');
  doc.text(s1.x + 10, s1.y + 9, 'Open de scanner', { size: 4.3, weight: 900 });
  doc.text(s1.x + 10, s1.y + 16, 'Kies een van de twee manieren.', { size: 2.55, fill: colors.muted });

  const dashCard = { x: 23, y: 88.5, w: 42, h: 26.5 };
  const dashCrop = { x: 0, y: 0, w: images.acDashboard.width, h: Math.min(images.acDashboard.height, 1120) };
  const dashPlacement = doc.image('acDashboard', dashCard, dashCrop, { r: 1.4 });
  doc.imageBadge(dashCard.x, dashCard.y, '1A');
  doc.rect(
    dashPlacement.x + 575 * dashPlacement.scale,
    dashPlacement.y + 410 * dashPlacement.scale,
    462 * dashPlacement.scale,
    395 * dashPlacement.scale,
    'none',
    colors.help,
    0.7,
    1.2,
  );
  doc.text(69, 95, 'Dashboard', { size: 2.55, weight: 800 });
  doc.text(69, 101, ['Tik op de paarse', 'Scan QR kaart.'], { size: 2.2, fill: colors.ink, lh: 3 });

  const topCard = { x: 116, y: 91, w: 42, h: 20 };
  const topPlacement = doc.image('acDashboard', topCard, { x: 600, y: 0, w: 476, h: 230 }, { r: 1.4 });
  doc.imageBadge(topCard.x, topCard.y, '1B');
  doc.circle(topPlacement.x + 868 * topPlacement.scale, topPlacement.y + 84 * topPlacement.scale, 3.6, colors.help, 0.7);
  doc.text(162, 96, 'Bovenbalk', { size: 2.55, weight: 800 });
  doc.text(162, 102, ['Of tik op het', 'camera-icoon.'], { size: 2.2, fill: colors.ink, lh: 3 });

  // Step 2: use the earlier mobile camera screenshot as the scan evidence.
  const s2 = { x: 12, y: 124, w: 88, h: 70 };
  doc.rect(s2.x, s2.y, s2.w, s2.h, colors.white, colors.line, 0.45, 2);
  doc.stepBadge(s2.x, s2.y, '2');
  doc.text(s2.x + 10, s2.y + 9, 'Richt op QR', { size: 4.1, weight: 900 });
  doc.text(s2.x + 10, s2.y + 15.5, 'Houd de QR rustig in beeld.', { size: 2.35, fill: colors.muted });
  doc.text(s2.x + 10, s2.y + 19, 'Wacht tot de asset opent.', { size: 2.25, fill: colors.muted });
  const qrFrame = { x: s2.x + 25, y: s2.y + 23, w: 38, h: 39 };
  doc.image('mobileCameraQr', qrFrame, { x: 0, y: 0, w: 1080, h: 2340 }, { r: 1.4 });
  doc.imageBadge(qrFrame.x, qrFrame.y, '2A');
  doc.text(qrFrame.x + qrFrame.w / 2, s2.y + 66.5, 'QR-locatie: meestal onder/achter.', {
    size: 1.8,
    weight: 600,
    fill: colors.scDark,
    anchor: 'middle',
  });

  // Step 3: manual fallback if scanning is not possible.
  const s3 = { x: 106, y: 124, w: 92, h: 70 };
  doc.rect(s3.x, s3.y, s3.w, s3.h, colors.white, colors.line, 0.45, 2);
  doc.stepBadge(s3.x, s3.y, '3');
  doc.text(s3.x + 10, s3.y + 9, 'Zoek handmatig', { size: 4.1, weight: 900 });
  doc.text(s3.x + 10, s3.y + 15.5, ['Als scannen niet lukt:', 'typ asset tag, QR-code of serienummer.'], { size: 2.45, fill: colors.muted, lh: 3.1 });
  const searchFrame = { x: s3.x + 13, y: s3.y + 31, w: 64, h: 14 };
  doc.image('searchFallback', searchFrame, { x: 60, y: 225, w: 510, h: 110 }, { r: 1.2 });
  doc.imageBadge(searchFrame.x, searchFrame.y, '3A');
  doc.text(s3.x + 21, s3.y + 50, 'Voorbeeld: INBIT-HG0001, QR-code of serienummer.', { size: 1.9, fill: colors.ink });
  const resultFrame = { x: s3.x + 13, y: s3.y + 53, w: 64, h: 15 };
  doc.image('searchResult', resultFrame, { x: 35, y: 1170, w: 700, h: 560 }, { r: 1.2 });
  doc.imageBadge(resultFrame.x, resultFrame.y, '3B');

  // Step 4: verify the opened asset.
  const s4 = { x: 12, y: 201, w: 186, h: 31 };
  doc.rect(s4.x, s4.y, s4.w, s4.h, colors.white, colors.line, 0.45, 2);
  doc.stepBadge(s4.x, s4.y, '4');
  doc.text(s4.x + 10, s4.y + 9, 'Controleer de geopende asset', { size: 4.1, weight: 900 });
  doc.text(s4.x + 10, s4.y + 15.2, 'Vergelijk asset tag, model en apparaat voordat je verdergaat.', { size: 2.45, fill: colors.ink });
  doc.text(s4.x + 10, s4.y + 20.2, 'STOP als asset tag, model of apparaat niet overeenkomt.', { size: 2.25, weight: 800, fill: colors.help });
  const verifyFrame = { x: 112, y: s4.y + 8, w: 70, h: 15 };
  doc.image('assetVerify', verifyFrame, { x: 0, y: 405, w: 780, h: 250 }, { r: 1.2 });
  doc.imageBadge(verifyFrame.x, verifyFrame.y, '4A');

  // Bottom utility zone: keep anchored for cross-guide consistency.
  doc.text(12, 236, 'Hulp bij scannen', { size: 2.8, weight: 800, fill: colors.muted });
  const helpY = 241;
  doc.helpTile(12, helpY, 'Camera', ['Gebruik zoeken', 'als camera niet kan.']);
  doc.helpTile(57.8, helpY, 'QR beschadigd', ['Zoek op tag, QR', 'of serienummer.'], 'QR');
  doc.helpTile(103.6, helpY, 'Geen resultaat', ['Controleer code.', 'Vraag hulp.'], '?');
  doc.helpTile(149.4, helpY, 'Verkeerde asset', ['STOP. Wijzig niets.', 'Vraag hulp.'], '!');

  doc.rect(12, 266, 147, 13, colors.greenSoft, '#86EFAC', 0.45, 2);
  doc.text(17, 273.5, 'Klaar als', { size: 3.0, weight: 900, fill: colors.green });
  doc.text(38, 273.5, 'De juiste assetpagina is open en komt overeen met het apparaat.', { size: 2.35, fill: colors.green });

  doc.text(12, 290, 'Relevante gidsen', { size: 2.2, weight: 800, fill: colors.muted });
  doc.chip(38, 286, 35, 'AC', 'AC-01 Login', colors.ac, colors.acSoft);
  doc.chip(77, 286, 39, 'WF', 'WF-01 Start', colors.orange, colors.orangeSoft);
  doc.chip(120, 286, 37, '?', 'HELP-01 Help', colors.help, colors.helpSoft);
  doc.text(12, 294.5, `Bron: ${liveUrl} dashboard capture + mobiele scanfoto + bestaande assetdetail screenshot | ${today}`, {
    size: 1.75,
    fill: colors.muted,
  });

  doc.qrPlaceholder(176, 263, 22);
  doc.text(187, 289.5, 'Digitale gids', { size: 2.2, weight: 800, anchor: 'middle' });

  return doc.render();
}

async function main() {
  fs.mkdirSync(outDir, { recursive: true });
  for (const file of Object.values(sourceFiles)) {
    if (!fs.existsSync(file)) throw new Error(`Missing source image: ${file}`);
  }

  const images = Object.fromEntries(
    Object.entries(sourceFiles).map(([key, file]) => [key, loadImage(file)]),
  );
  const svg = drawGuide(images);
  const html = `<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <style>
    @page { size: A4; margin: 0; }
    html, body { width: 210mm; height: 297mm; margin: 0; padding: 0; overflow: hidden; background: #dfe3ea; }
    .page, .page > svg { display: block; width: 210mm; height: 297mm; overflow: hidden; background: white; }
  </style>
</head>
<body><div class="page">${svg}</div></body>
</html>`;

  fs.writeFileSync(outputs.svg, svg, 'utf8');
  fs.writeFileSync(outputs.html, html, 'utf8');

  const browser = await chromium.launch(browserLaunchOptions({ headless: true }));
  const page = await browser.newPage({ viewport: { width: 1240, height: 1754 }, deviceScaleFactor: 2 });
  await page.goto(`file:///${outputs.html.replace(/\\/g, '/')}`, { waitUntil: 'load' });
  await page.locator('svg').screenshot({ path: outputs.png });
  await page.pdf({
    path: outputs.pdf,
    width: '210mm',
    height: '297mm',
    printBackground: true,
    preferCSSPageSize: true,
    margin: { top: '0', right: '0', bottom: '0', left: '0' },
  });
  await browser.close();

  const summary = [
    '# SC-01 Asset Vinden En Openen Snipe Proof v10',
    '',
    `Generated: ${today}`,
    `Live URL: ${liveUrl}`,
    '',
    '## Outputs',
    `- Proof PNG: ${outputs.png}`,
    `- Proof PDF: ${outputs.pdf}`,
    `- SVG source: ${outputs.svg}`,
    `- HTML source: ${outputs.html}`,
    '',
    '## Sources',
    `- AC-01 mobile dashboard source: ${sourceFiles.acDashboard}`,
    `- Mobile dashboard top-bar source: ${sourceFiles.acDashboard}`,
    `- Mobile scanner/QR screenshot: ${sourceFiles.mobileCameraQr}`,
    `- Search fallback screenshot: ${sourceFiles.searchFallback}`,
    `- Search result screenshot: ${sourceFiles.searchResult}`,
    `- Asset verification screenshot: ${sourceFiles.assetVerify}`,
    '',
    '## Notes',
    '- Laptop camera capture is intentionally not used; the guide uses the earlier mobile scanner screenshot for the scan step.',
    '- Step 1 reuses the AC-01 mobile dashboard for both alternatives: the Scan QR card and the mobile top-bar camera icon.',
    '- The QR-location hint is attached to image 2A as a subtle image caption.',
    '- The manual fallback supports asset tag, QR-code, and serial-number search.',
    '- Stop guidance is attached to the verification step, not moved to the help row.',
    '',
  ].join('\n');
  fs.writeFileSync(outputs.summary, summary, 'utf8');

  console.log(JSON.stringify({ outputs, sourceFiles }, null, 2));
}

main().catch((error) => {
  console.error(error);
  process.exitCode = 1;
});
