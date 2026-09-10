import fs from 'node:fs';
import path from 'node:path';
import { createRequire } from 'node:module';

const require = createRequire(import.meta.url);
const { chromium } = require('playwright');

const outDir = 'C:\\Users\\Gebruiker\\Documents\\snipe-it manuals\\layout-proofs\\2026-06-30-ast01-clean-open-asset';
const shotDir = path.join(outDir, 'screenshots');

const files = {
  dashboard: path.join(shotDir, 'AST-01-clean-01-dashboard-scan-entry-phone.png'),
  scanner: path.join(shotDir, 'AST-01-clean-02-scan-page-phone.png'),
  search: path.join(shotDir, 'AST-01-clean-03-search-asset-tag-phone.png'),
  result: path.join(shotDir, 'AST-01-clean-04-hardware-result-phone.png'),
  verify: path.join(shotDir, 'AST-01-clean-05-asset-detail-verify-phone.png'),
  tagModel: path.join(shotDir, 'AST-01-clean-06-asset-detail-tag-model-phone.png'),
  cameraQr: 'C:\\Users\\Gebruiker\\Downloads\\Screenshot_20260630-132442_Chrome.jpg',
};

const outputs = {
  svg: path.join(outDir, 'AST-01-open-asset-clean-v12.svg'),
  html: path.join(outDir, 'AST-01-open-asset-clean-v12.html'),
  png: path.join(outDir, 'AST-01-open-asset-clean-v12-proof.png'),
  pdf: path.join(outDir, 'AST-01-open-asset-clean-v12-proof.pdf'),
};

const colors = {
  ink: '#102033',
  muted: '#53657A',
  line: '#C8D5E2',
  faint: '#F6F9FC',
  teal: '#0E8A75',
  tealDark: '#087162',
  blue: '#2B8DBC',
  orange: '#F59E0B',
  orangeSoft: '#FFF7ED',
  red: '#EF3340',
  redSoft: '#FFF1F3',
  greenSoft: '#ECFDF5',
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
    uri: `data:${size.mime};base64,${buffer.toString('base64')}`,
  };
}

const images = Object.fromEntries(
  Object.entries(files).map(([key, file]) => [key, loadImage(file)]),
);

const defs = [];
const body = [];
let clipId = 0;

function add(markup) {
  body.push(markup);
}

function rect(x, y, w, h, fill = '#FFFFFF', stroke = colors.line, sw = 0.35, r = 1.6, extra = '') {
  add(`<rect x="${x}" y="${y}" width="${w}" height="${h}" rx="${r}" fill="${fill}" stroke="${stroke}" stroke-width="${sw}" ${extra}/>`);
}

function text(x, y, lines, opts = {}) {
  const {
    size = 3,
    weight = 400,
    fill = colors.ink,
    anchor = 'start',
    lh = size * 1.25,
    family = 'Arial, Helvetica, sans-serif',
  } = opts;
  const arr = Array.isArray(lines) ? lines : [lines];
  const tspans = arr
    .map((line, index) => `<tspan x="${x}" dy="${index === 0 ? 0 : lh}">${xml(line)}</tspan>`)
    .join('');
  add(`<text text-anchor="${anchor}" font-family="${family}" font-size="${size}" font-weight="${weight}" fill="${fill}" x="${x}" y="${y}">${tspans}</text>`);
}

function card(x, y, w, h) {
  rect(x, y, w, h, '#FFFFFF', colors.line, 0.35, 1.6);
}

function stepBadge(cx, cy, label, fill = colors.teal) {
  add(`<circle cx="${cx}" cy="${cy}" r="4.2" fill="${fill}"/>`);
  text(cx, cy + 1.25, label, { size: label.length > 1 ? 2.4 : 3.2, weight: 700, fill: '#FFFFFF', anchor: 'middle' });
}

function miniBadge(x, y, label, fill = colors.teal) {
  add(`<rect x="${x}" y="${y}" width="10" height="5.2" rx="2.6" fill="${fill}"/>`);
  text(x + 5, y + 3.7, label, { size: 2.4, weight: 700, fill: '#FFFFFF', anchor: 'middle' });
}

function imageBadge(cx, cy, label, fill = colors.teal) {
  add(`<circle cx="${cx}" cy="${cy}" r="4.9" fill="#FFFFFF" fill-opacity="0.2" stroke="${fill}" stroke-width="1.05"/>`);
  text(cx, cy + 1.05, label, { size: label.length > 1 ? 2.35 : 3, weight: 800, fill, anchor: 'middle' });
}

function clippedImage(key, frame, crop, options = {}) {
  const img = images[key];
  const id = `clip-${clipId++}`;
  defs.push(`<clipPath id="${id}"><rect x="${frame.x}" y="${frame.y}" width="${frame.w}" height="${frame.h}" rx="${options.r ?? 1.2}"/></clipPath>`);
  const scale = Math.max(frame.w / crop.w, frame.h / crop.h);
  const cropW = crop.w * scale;
  const cropH = crop.h * scale;
  const ix = frame.x + (frame.w - cropW) / 2 - crop.x * scale;
  const iy = frame.y + (frame.h - cropH) / 2 - crop.y * scale;
  const iw = img.width * scale;
  const ih = img.height * scale;

  rect(frame.x, frame.y, frame.w, frame.h, '#FFFFFF', '#D6DEE8', 0.35, options.r ?? 1.2);
  add(`<image href="${img.uri}" x="${ix}" y="${iy}" width="${iw}" height="${ih}" clip-path="url(#${id})"/>`);
  rect(frame.x, frame.y, frame.w, frame.h, 'none', options.stroke ?? '#D6DEE8', options.sw ?? 0.35, options.r ?? 1.2);
}

function focusRect(x, y, w, h) {
  add(`<rect x="${x}" y="${y}" width="${w}" height="${h}" rx="1.2" fill="none" stroke="${colors.red}" stroke-width="0.75"/>`);
}

function focusCircle(cx, cy, r) {
  add(`<circle cx="${cx}" cy="${cy}" r="${r}" fill="none" stroke="${colors.red}" stroke-width="0.75"/>`);
}

function helpTile(x, y, title, line, icon) {
  rect(x, y, 37, 19, colors.orangeSoft, '#FDBA74', 0.35, 1.5);
  add(`<circle cx="${x + 4.2}" cy="${y + 5.1}" r="2.8" fill="#FFFFFF" stroke="${colors.orange}" stroke-width="0.45"/>`);
  text(x + 4.2, y + 6, icon, { size: 2.6, weight: 700, fill: colors.orange, anchor: 'middle' });
  text(x + 8.2, y + 5.4, title, { size: 2.25, weight: 700, fill: colors.tealDark });
  text(x + 3, y + 12, line, { size: 2.05, fill: colors.ink, lh: 3 });
}

function qrPlaceholder(x, y, size) {
  rect(x, y, size, size, '#FFFFFF', colors.ink, 0.45, 0.5);
  const cells = [
    [1, 1], [2, 1], [4, 1], [6, 1], [1, 2], [4, 2], [5, 2], [7, 2],
    [2, 3], [3, 3], [6, 3], [1, 4], [5, 4], [7, 4], [2, 5], [4, 5],
    [6, 5], [7, 5], [1, 6], [3, 6], [5, 6], [2, 7], [4, 7], [7, 7],
  ];
  const unit = size / 9;
  for (const [cx, cy] of cells) {
    add(`<rect x="${x + cx * unit}" y="${y + cy * unit}" width="${unit * 0.72}" height="${unit * 0.72}" fill="${colors.ink}"/>`);
  }
}

function chip(x, y, label, stroke, fill) {
  rect(x, y, 28, 5.4, fill, stroke, 0.35, 1.7);
  text(x + 14, y + 3.85, label, { size: 2.5, weight: 700, fill: stroke, anchor: 'middle' });
}

function guideChip(x, y, w, icon, label, stroke, fill) {
  rect(x, y, w, 6.2, fill, stroke, 0.35, 1.8);
  add(`<circle cx="${x + 4}" cy="${y + 3.1}" r="2.1" fill="#FFFFFF" stroke="${stroke}" stroke-width="0.35"/>`);
  text(x + 4, y + 3.85, icon, { size: 1.8, weight: 800, fill: stroke, anchor: 'middle' });
  text(x + 8, y + 4.15, label, { size: 2.25, weight: 700, fill: stroke });
}

function guideInline(x, y, icon, label, stroke) {
  add(`<circle cx="${x + 2.6}" cy="${y - 0.9}" r="2.25" fill="#FFFFFF" fill-opacity="0.35" stroke="${stroke}" stroke-width="0.45"/>`);
  text(x + 2.6, y - 0.1, icon, { size: 1.7, weight: 800, fill: stroke, anchor: 'middle' });
  text(x + 6.2, y, label, { size: 2.5, weight: 700, fill: stroke });
}

function guidePrerequisite(x, y, icon, main, ref, stroke) {
  add(`<circle cx="${x + 2.6}" cy="${y - 0.9}" r="2.25" fill="#FFFFFF" fill-opacity="0.35" stroke="${stroke}" stroke-width="0.45"/>`);
  text(x + 2.6, y - 0.1, icon, { size: 1.7, weight: 800, fill: stroke, anchor: 'middle' });
  add(`<text font-family="Arial, Helvetica, sans-serif" font-weight="700" fill="${stroke}" x="${x + 6.2}" y="${y}"><tspan font-size="2.55">${xml(main)}</tspan><tspan font-size="2.05"> ${xml(ref)}</tspan></text>`);
}

// Page background and header.
rect(0, 0, 210, 297, '#FFFFFF', 'none', 0, 0);
add(`<rect x="12" y="10" width="2.2" height="15" fill="${colors.teal}"/>`);
text(17, 18, 'AST-01 Asset openen', { size: 7.2, weight: 700 });
text(17, 24, 'Open het juiste apparaat en controleer voor je iets wijzigt', { size: 2.9, fill: colors.muted });
rect(161, 11, 37, 10, colors.greenSoft, '#86EFAC', 0.35, 1.5);
text(179.5, 17.3, 'Draft v12 2026-07-02', { size: 2.45, weight: 700, fill: colors.tealDark, anchor: 'middle' });

// Context strip.
rect(12, 35, 186, 16, colors.faint, colors.line, 0.35, 1.4);
text(17, 40, 'Rol', { size: 2.25, weight: 700, fill: colors.muted });
text(17, 45.3, 'Refurbisher', { size: 3, weight: 700 });
text(58, 40, 'Nodig', { size: 2.25, weight: 700, fill: colors.muted });
text(58, 45.3, 'Telefoon met camera + apparaat met QR/asset tag', { size: 2.85, weight: 700 });
text(143, 40, 'Vooraf', { size: 2.25, weight: 700, fill: colors.muted });
guidePrerequisite(143, 45.4, 'AC', 'Ingelogd', '(AC-01 Login)', '#2563EB');

// Step 1: choice step.
card(12, 57, 186, 53);
stepBadge(17, 63, '1');
text(25, 63.8, 'Open de scanner', { size: 4.2, weight: 700 });
text(25, 70, 'Kies een van deze twee manieren.', { size: 2.75, fill: colors.muted });

rect(22, 74, 82, 30, '#FFFFFF', '#D6DEE8', 0.35, 1.3);
clippedImage('dashboard', { x: 24, y: 76, w: 52, h: 26 }, { x: 220, y: 535, w: 590, h: 295 });
imageBadge(24, 76, '1A');
text(79, 82, 'Dashboard', { size: 2.65, weight: 700 });
text(79, 87, ['Tik op', 'Scan QR.'], { size: 2.3, fill: colors.ink, lh: 3.1 });

rect(106, 74, 82, 30, '#FFFFFF', '#D6DEE8', 0.35, 1.3);
clippedImage('scanner', { x: 108, y: 76, w: 52, h: 26 }, { x: 275, y: 65, w: 500, h: 250 });
imageBadge(108, 76, '1B');
text(163, 82, 'Bovenbalk', { size: 2.65, weight: 700 });
text(163, 87, ['Of tik op het', 'camera-icoon.'], { size: 2.3, fill: colors.ink, lh: 3.1 });

// Step 2: scan the physical QR.
card(12, 115, 88, 71);
stepBadge(17, 121, '2');
text(25, 121.8, 'Richt op QR', { size: 4.1, weight: 700 });
text(25, 128, ['Houd de sticker in beeld', 'tot de asset opent.'], { size: 2.55, fill: colors.muted, lh: 3.2 });
rect(73, 119.5, 21, 13, colors.greenSoft, '#86EFAC', 0.35, 1.3);
text(75, 124.2, 'QR-locatie', { size: 1.9, weight: 700, fill: colors.tealDark });
text(75, 128.6, 'Onder/achterkant.', { size: 1.65, fill: colors.tealDark });
clippedImage('cameraQr', { x: 21, y: 136, w: 72, h: 42 }, { x: 120, y: 720, w: 825, h: 650 }, { r: 1.4 });
imageBadge(21, 136, '2');

// Step 3: manual fallback/search.
card(106, 115, 92, 71);
stepBadge(111, 121, '3');
text(119, 121.8, 'Zoek handmatig', { size: 4.1, weight: 700 });
text(119, 128, ['Als scannen niet lukt:', 'typ asset tag of QR-code.'], { size: 2.55, fill: colors.muted, lh: 3.2 });
clippedImage('search', { x: 120, y: 138, w: 64, h: 15 }, { x: 50, y: 225, w: 520, h: 115 }, { r: 1.2 });
imageBadge(120, 138, '3A');
text(116, 158, 'Zoekbalk: typ INBIT-HG0001 of QR-code.', { size: 2.25, fill: colors.ink });
clippedImage('result', { x: 120, y: 163, w: 64, h: 16 }, { x: 45, y: 1325, w: 690, h: 310 }, { r: 1.2 });
imageBadge(120, 163, '3B');
text(116, 183, 'Kies de juiste asset uit de resultaten.', { size: 2.25, fill: colors.ink });

// Step 4: verification with attached stop.
card(12, 192, 186, 44);
stepBadge(17, 198, '4');
text(25, 198.8, 'Controleer asset', { size: 4.1, weight: 700 });
text(25, 205, 'Vergelijk titel, tag/model en apparaat.', { size: 2.55, fill: colors.ink });
text(25, 210, 'STOP als titel, tag/model of apparaat niet overeenkomt.', { size: 2.35, weight: 700, fill: colors.red });
clippedImage('verify', { x: 22, y: 216, w: 82, h: 13 }, { x: 0, y: 425, w: 780, h: 185 }, { r: 1.2 });
imageBadge(22, 216, '4A');
text(22, 233, 'Titel en model', { size: 2.2, weight: 700, fill: colors.ink });
clippedImage('tagModel', { x: 110, y: 216, w: 68, h: 13 }, { x: 0, y: 955, w: 780, h: 150 }, { r: 1.2 });
imageBadge(110, 216, '4B');
text(110, 233, 'Asset tag/status', { size: 2.2, weight: 700, fill: colors.ink });

// Help tiles.
text(12, 244, 'Hulp bij openen', { size: 2.6, weight: 700, fill: colors.muted });
helpTile(12, 248, 'Camera', 'Gebruik zoeken.', '!');
helpTile(55, 248, 'QR beschadigd', 'Typ asset tag.', 'QR');
helpTile(98, 248, 'Geen resultaat', ['Controleer code.', 'Vraag hulp.'], '?');
helpTile(141, 248, 'Geen telefoon', ['Gebruik laptop', 'of zoekbalk.'], 'i');

// Done and footer.
rect(12, 270, 148, 14, colors.greenSoft, '#86EFAC', 0.35, 1.3);
text(17, 275, 'Klaar als', { size: 3, weight: 800, fill: colors.tealDark });
text(17, 280, 'De juiste assetpagina is open en komt overeen met het apparaat in je hand.', { size: 2.35, fill: colors.tealDark });

text(12, 289, 'Relevante gidsen', { size: 2.2, weight: 700, fill: colors.muted });
guideChip(36, 285.2, 31, 'AC', 'AC-01 Login', '#2563EB', '#EFF6FF');
guideChip(70, 285.2, 29, 'SC', 'SC-01 Scan', colors.teal, colors.greenSoft);
guideChip(102, 285.2, 27, '?', 'HELP-01', colors.red, colors.redSoft);
text(12, 294, 'Bron: dev.inbit screenshots | Asset INBIT-HG0001 | Screenshots 2026-06-30 | Concept AST-01', { size: 1.9, fill: colors.muted });

qrPlaceholder(179, 263, 22);
text(190, 289, 'Digitale gids', { size: 2.1, weight: 700, fill: colors.ink, anchor: 'middle' });

const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="210mm" height="297mm" viewBox="0 0 210 297">
<defs>${defs.join('\n')}</defs>
${body.join('\n')}
</svg>`;

const html = `<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>AST-01 open asset clean v12</title>
<style>
  @page { size: A4 portrait; margin: 0; }
  html, body { margin: 0; padding: 0; background: #2f3338; }
  body { display: flex; justify-content: center; align-items: flex-start; }
  svg { width: 210mm; height: 297mm; background: white; }
</style>
</head>
<body>${svg}</body>
</html>`;

fs.writeFileSync(outputs.svg, svg, 'utf8');
fs.writeFileSync(outputs.html, html, 'utf8');

const browser = await chromium.launch({ headless: true });
try {
  const page = await browser.newPage({ viewport: { width: 1240, height: 1754 }, deviceScaleFactor: 2 });
  await page.setContent(html, { waitUntil: 'load' });
  await page.locator('svg').screenshot({ path: outputs.png });
  await page.pdf({
    path: outputs.pdf,
    width: '210mm',
    height: '297mm',
    printBackground: true,
    margin: { top: '0mm', right: '0mm', bottom: '0mm', left: '0mm' },
  });
} finally {
  await browser.close();
}

console.log(JSON.stringify(outputs, null, 2));
