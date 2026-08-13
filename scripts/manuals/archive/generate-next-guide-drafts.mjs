import fs from 'node:fs';
import path from 'node:path';
import { createRequire } from 'node:module';

const require = createRequire(import.meta.url);
const { chromium } = require('playwright');

const today = '2026-07-21';
const outDir = 'C:\\Users\\Gebruiker\\Documents\\snipe-it manuals\\layout-proofs\\2026-07-21-next-guide-drafts';
const astShotDir = 'C:\\Users\\Gebruiker\\Documents\\snipe-it manuals\\layout-proofs\\2026-06-30-ast01-clean-open-asset\\screenshots';
const sourceDir = 'C:\\Users\\Gebruiker\\Documents\\snipe-it manuals\\screenshot-source\\2026-06-25-blocks';
const refreshDir = 'C:\\Users\\Gebruiker\\Documents\\snipe-it manuals\\screenshot-source\\2026-07-02-first-batch-refresh';

const sourceFiles = {
  dashMobile: 'C:\\Users\\Gebruiker\\Documents\\snipe-it manuals\\base tests\\screenshot dashboard.jpg',
  dashDesktop: 'C:\\Users\\Gebruiker\\Documents\\snipe-it manuals\\layout-proofs\\2026-07-07-sc01-snipe-scan\\captures\\SC-01-live-dashboard.jpg',
  cameraQr: 'C:\\Users\\Gebruiker\\Downloads\\Screenshot_20260630-132442_Chrome.jpg',
  searchField: path.join(astShotDir, 'AST-01-clean-03-search-asset-tag-phone.png'),
  searchResult: path.join(astShotDir, 'AST-01-clean-04-hardware-result-phone.png'),
  assetVerify: path.join(astShotDir, 'AST-01-clean-05-asset-detail-verify-phone.png'),
  assetDetail: path.join(astShotDir, 'AST-01-clean-06-asset-detail-tag-model-phone.png'),
  wfWide: path.join(refreshDir, 'WF-01-tests-tab-wide-live.png'),
  wfCards: path.join(sourceDir, 'WF-01-02-start-workflow-form-mobile.png'),
};

const sourceIds = {
  dashMobile: 'DASH-MOBILE-01',
  dashDesktop: 'DASH-DESKTOP-01',
  cameraQr: 'SCAN-CAMERA-QR-01',
  searchField: 'SEARCH-FIELD-01',
  searchResult: 'SEARCH-RESULT-01',
  assetVerify: 'ASSET-VERIFY-01',
  assetDetail: 'ASSET-DETAIL-02',
  wfWide: 'WF-TESTS-WIDE-01',
  wfCards: 'WF-ACTIVE-CARDS-01',
};

const colors = {
  ink: '#102033',
  muted: '#53657A',
  line: '#C8D5E2',
  pale: '#F8FAFC',
  white: '#FFFFFF',
  ac: '#2563EB',
  sc: '#0E8A75',
  ast: '#0E8A45',
  astSoft: '#ECFDF5',
  wf: '#F97316',
  wfSoft: '#FFF7ED',
  help: '#EF3340',
  helpSoft: '#FFF1F3',
  green: '#059669',
  greenSoft: '#ECFDF5',
  orange: '#F59E0B',
  orangeSoft: '#FFF7ED',
};

function xml(value) {
  return String(value)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

function imageSize(buffer) {
  if (buffer[0] === 0x89 && buffer[1] === 0x50 && buffer[2] === 0x4e && buffer[3] === 0x47) {
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
  constructor(images, familyColor) {
    this.images = images;
    this.familyColor = familyColor;
    this.defs = [];
    this.parts = [];
    this.uid = 0;
  }

  nextId(prefix) {
    this.uid += 1;
    return `${prefix}-${this.uid}`;
  }

  rect(x, y, w, h, fill = 'none', stroke = 'none', sw = 0, r = 0) {
    this.parts.push(`<rect x="${x}" y="${y}" width="${w}" height="${h}" rx="${r}" fill="${fill}" stroke="${stroke}" stroke-width="${sw}"/>`);
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
    this.parts.push(`<text x="${x}" y="${y}" font-family="${family}" font-size="${size}" font-weight="${weight}" fill="${fill}" text-anchor="${anchor}">${tspans}</text>`);
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

  stepBadge(x, y, label) {
    this.circle(x, y, 7.1, this.familyColor, 2, colors.white, 0.9);
    this.text(x, y + 1.65, label, { size: 4.7, weight: 900, fill: this.familyColor, anchor: 'middle' });
  }

  imageBadge(x, y, label) {
    this.parts.push(`<circle cx="${x}" cy="${y}" r="4.15" fill="#FFFFFF" fill-opacity="0.34" stroke="${this.familyColor}" stroke-opacity="0.88" stroke-width="1"/>`);
    this.text(x, y + 0.95, label, { size: 2.05, weight: 850, fill: this.familyColor, anchor: 'middle' });
  }

  guidePrereq(x, y, icon, main, ref, color) {
    this.circle(x + 2.6, y - 0.9, 2.25, color, 0.55, colors.white, 0.95);
    this.text(x + 2.6, y - 0.1, icon, { size: 1.6, weight: 900, fill: color, anchor: 'middle' });
    this.parts.push(`<text font-family="Arial, Helvetica, sans-serif" font-weight="800" fill="${color}" x="${x + 6.3}" y="${y}"><tspan font-size="2.55">${xml(main)}</tspan><tspan font-size="2.0"> ${xml(ref)}</tspan></text>`);
  }

  guideChip(x, y, w, icon, label, color, fill = colors.white) {
    this.rect(x, y, w, 7, fill, color, 0.55, 2.5);
    this.circle(x + 5, y + 3.5, 2.45, color, 0.55, colors.white);
    this.text(x + 5, y + 4.35, icon, { size: icon.length > 3 ? 1.35 : 1.65, weight: 900, fill: color, anchor: 'middle' });
    this.text(x + 10, y + 4.55, label, { size: 2.3, weight: 800, fill: color });
  }

  helpTile(x, y, w, title, body, icon = '!') {
    this.rect(x, y, w, 20, colors.orangeSoft, '#FDBA74', 0.45, 2);
    this.circle(x + 5.2, y + 6.2, 3.2, colors.orange, 0.55, colors.white);
    this.text(x + 5.2, y + 7.35, icon, { size: icon.length > 1 ? 2.1 : 2.9, weight: 900, fill: colors.orange, anchor: 'middle' });
    this.text(x + 10.5, y + 7, title, { size: 2.3, weight: 900, fill: colors.ink });
    this.text(x + 5, y + 14.2, body, { size: 2.05, fill: colors.ink, lh: 2.85 });
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

function header(doc, code, title, subtitle, version) {
  doc.rect(12, 13, 2, 16, doc.familyColor);
  doc.text(18, 22, `${code} ${title}`, { size: 8.2, weight: 900 });
  doc.text(18, 29, subtitle, { size: 3.1, fill: colors.muted });
  doc.rect(161, 12, 37, 11, colors.greenSoft, '#86EFAC', 0.45, 2);
  doc.text(179.5, 18.9, `${version} ${today}`, { size: 2.35, weight: 800, fill: colors.green, anchor: 'middle' });
}

function contextBox(doc, columns) {
  doc.rect(12, 39, 186, 18, colors.pale, colors.line, 0.45, 1.6);
  const xs = [17, 58, 142];
  columns.forEach((column, index) => {
    const x = xs[index];
    doc.text(x, 47, column.label, { size: 2.3, weight: 800, fill: colors.muted });
    if (column.guide) {
      doc.guidePrereq(x, 53, column.guide.icon, column.value, column.guide.ref, column.guide.color);
    } else {
      doc.text(x, 53, column.value, { size: column.size ?? 3.05, weight: 800, fill: column.fill ?? colors.ink });
    }
  });
}

function standardFooter(doc, options) {
  const doneY = options.doneY ?? 266;
  doc.rect(12, doneY, 147, 13, colors.greenSoft, '#86EFAC', 0.45, 2);
  doc.text(17, doneY + 7.5, 'Klaar als', { size: 3, weight: 900, fill: colors.green });
  doc.text(38, doneY + 7.5, options.done, { size: 2.25, fill: colors.green });

  doc.text(12, 290, 'Relevante gidsen', { size: 2.2, weight: 800, fill: colors.muted });
  let x = 38;
  for (const ref of options.refs) {
    doc.guideChip(x, 286, ref.w, ref.icon, ref.label, ref.color, ref.fill);
    x += ref.w + 4;
  }
  doc.text(12, 294.5, options.source, { size: 1.7, fill: colors.muted });
  doc.qrPlaceholder(176, 263, 22);
  doc.text(187, 289.5, 'Digitale gids', { size: 2.2, weight: 800, anchor: 'middle' });
}

function buildAst01(images) {
  const doc = new SvgDoc(images, colors.ast);
  header(doc, 'AST-01', 'Asset openen', 'Open het juiste apparaat en controleer voor je iets wijzigt', 'Draft v13');
  contextBox(doc, [
    { label: 'Rol', value: 'Refurbisher' },
    { label: 'Nodig', value: 'Telefoon met camera + QR/asset tag', size: 2.8 },
    { label: 'Vooraf', value: 'Ingelogd', guide: { icon: 'AC', ref: '(AC-01 Login)', color: colors.ac } },
  ]);

  const s1 = { x: 12, y: 68, w: 186, h: 46 };
  doc.rect(s1.x, s1.y, s1.w, s1.h, colors.white, colors.line, 0.45, 2);
  doc.stepBadge(s1.x, s1.y, '1');
  doc.text(s1.x + 10, s1.y + 9, 'Open de scanner', { size: 4.2, weight: 900 });
  doc.text(s1.x + 10, s1.y + 16, 'Kies een van de twee manieren.', { size: 2.5, fill: colors.muted });

  const dashFrame = { x: 23, y: 88, w: 42, h: 24 };
  const dashCrop = { x: 0, y: 0, w: images.dashMobile.width, h: Math.min(images.dashMobile.height, 1120) };
  const dashPlacement = doc.image('dashMobile', dashFrame, dashCrop, { r: 1.4 });
  doc.imageBadge(dashFrame.x, dashFrame.y, '1A');
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
  doc.text(69, 94, 'Dashboard', { size: 2.5, weight: 800 });
  doc.text(69, 100, ['Tik op de paarse', 'Scan QR kaart.'], { size: 2.15, lh: 2.9 });

  const topFrame = { x: 116, y: 89, w: 42, h: 20 };
  const topPlacement = doc.image('dashDesktop', topFrame, { x: 246, y: 149, w: 185, h: 96 }, { r: 1.4 });
  doc.imageBadge(topFrame.x, topFrame.y, '1B');
  doc.circle(topPlacement.x + 351 * topPlacement.scale, topPlacement.y + 190 * topPlacement.scale, 3.6, colors.help, 0.7);
  doc.text(162, 94, 'Bovenbalk', { size: 2.5, weight: 800 });
  doc.text(162, 100, ['Of tik op het', 'camera-icoon.'], { size: 2.15, lh: 2.9 });

  const s2 = { x: 12, y: 121, w: 88, h: 64 };
  doc.rect(s2.x, s2.y, s2.w, s2.h, colors.white, colors.line, 0.45, 2);
  doc.stepBadge(s2.x, s2.y, '2');
  doc.text(s2.x + 10, s2.y + 9, 'Richt op QR', { size: 4.1, weight: 900 });
  doc.text(s2.x + 10, s2.y + 15.5, ['Houd het label rustig in beeld.', 'Wacht tot de asset opent.'], { size: 2.25, fill: colors.muted, lh: 3.1 });
  const qrFrame = { x: s2.x + 25, y: s2.y + 23, w: 38, h: 34 };
  doc.image('cameraQr', qrFrame, null, { r: 1.4 });
  doc.imageBadge(qrFrame.x, qrFrame.y, '2A');
  doc.text(qrFrame.x + qrFrame.w / 2, s2.y + 62, 'QR-locatie: meestal onder/achter.', {
    size: 1.75,
    weight: 600,
    fill: colors.ast,
    anchor: 'middle',
  });

  const s3 = { x: 106, y: 121, w: 92, h: 64 };
  doc.rect(s3.x, s3.y, s3.w, s3.h, colors.white, colors.line, 0.45, 2);
  doc.stepBadge(s3.x, s3.y, '3');
  doc.text(s3.x + 10, s3.y + 9, 'Zoek handmatig', { size: 4.1, weight: 900 });
  doc.text(s3.x + 10, s3.y + 15.5, ['Als scannen niet lukt:', 'typ asset tag, QR-code of serienummer.'], { size: 2.25, fill: colors.muted, lh: 3.1 });
  const searchFrame = { x: s3.x + 13, y: s3.y + 28, w: 64, h: 13 };
  doc.image('searchField', searchFrame, { x: 60, y: 225, w: 510, h: 110 }, { r: 1.2 });
  doc.imageBadge(searchFrame.x, searchFrame.y, '3A');
  doc.text(s3.x + 20, s3.y + 45, 'Zoek op tag, QR-code of serienummer.', { size: 1.9 });
  const resultFrame = { x: s3.x + 13, y: s3.y + 48, w: 64, h: 13 };
  doc.image('searchResult', resultFrame, { x: 35, y: 1170, w: 700, h: 560 }, { r: 1.2 });
  doc.imageBadge(resultFrame.x, resultFrame.y, '3B');

  const s4 = { x: 12, y: 192, w: 186, h: 42 };
  doc.rect(s4.x, s4.y, s4.w, s4.h, colors.white, colors.line, 0.45, 2);
  doc.stepBadge(s4.x, s4.y, '4');
  doc.text(s4.x + 10, s4.y + 9, 'Controleer asset', { size: 4.1, weight: 900 });
  doc.text(s4.x + 10, s4.y + 15, 'Vergelijk titel, tag/model, status en apparaat.', { size: 2.25 });
  doc.text(s4.x + 10, s4.y + 20, 'STOP als iets niet overeenkomt. Vraag hulp voor je wijzigt.', { size: 2.05, weight: 800, fill: colors.help });
  const verifyFrame = { x: 22, y: s4.y + 26, w: 60, h: 12 };
  doc.image('assetVerify', verifyFrame, { x: 0, y: 425, w: 780, h: 185 }, { r: 1.2 });
  doc.imageBadge(verifyFrame.x, verifyFrame.y, '4A');
  doc.text(verifyFrame.x, s4.y + 41, 'Titel en model', { size: 1.8, weight: 700 });
  const tagFrame = { x: 87, y: s4.y + 26, w: 46, h: 12 };
  doc.image('assetDetail', tagFrame, { x: 0, y: 950, w: 780, h: 200 }, { r: 1.2 });
  doc.imageBadge(tagFrame.x, tagFrame.y, '4B');
  doc.text(tagFrame.x, s4.y + 41, 'Asset tag', { size: 1.8, weight: 700 });
  const statusFrame = { x: 138, y: s4.y + 26, w: 46, h: 12 };
  doc.image('assetDetail', statusFrame, { x: 0, y: 1120, w: 780, h: 204 }, { r: 1.2 });
  doc.imageBadge(statusFrame.x, statusFrame.y, '4C');
  doc.text(statusFrame.x, s4.y + 41, 'Status', { size: 1.8, weight: 700 });

  doc.text(12, 239, 'Hulp bij openen', { size: 2.8, weight: 800, fill: colors.muted });
  doc.helpTile(12, 243, 41.5, 'Camera', ['Gebruik zoeken', 'als camera niet kan.']);
  doc.helpTile(57.8, 243, 41.5, 'QR beschadigd', ['Zoek op tag', 'of serienummer.'], 'QR');
  doc.helpTile(103.6, 243, 41.5, 'Geen resultaat', ['Controleer code.', 'Vraag supervisor.'], '?');
  doc.helpTile(149.4, 243, 41.5, 'Geen telefoon', ['Gebruik laptop', 'en zoekbalk.'], 'i');

  standardFooter(doc, {
    done: 'De juiste assetpagina is open en komt overeen met het apparaat.',
    refs: [
      { w: 35, icon: 'AC', label: 'AC-01 Login', color: colors.ac, fill: '#EFF6FF' },
      { w: 35, icon: 'SC', label: 'SC-01 Scan', color: colors.sc, fill: colors.astSoft },
      { w: 39, icon: '?', label: 'HELP-01 Help', color: colors.help, fill: colors.helpSoft },
    ],
    source: `Bron: gedeelde screenshots + gecontroleerde testopnamen | ${today}`,
  });
  return doc.render();
}

function problemTile(doc, x, y, w, h, icon, title, body, ref = null) {
  doc.rect(x, y, w, h, colors.white, colors.line, 0.45, 2);
  doc.circle(x + 6, y + 7.2, 3.2, colors.help, 0.7, colors.helpSoft);
  doc.text(x + 6, y + 8.3, icon, { size: icon.length > 1 ? 1.8 : 2.8, weight: 900, fill: colors.help, anchor: 'middle' });
  doc.text(x + 12, y + 8.2, title, { size: 2.9, weight: 900, fill: colors.help });
  doc.text(x + 5, y + 17, body, { size: 2.15, fill: colors.ink, lh: 3.05 });
  if (ref) doc.guideChip(x + 5, y + h - 10, ref.w, ref.icon, ref.label, ref.color, ref.fill);
}

function buildHelp01(images) {
  const doc = new SvgDoc(images, colors.help);
  header(doc, 'HELP-01', 'Problemen', 'Stop, herstel of vraag hulp zonder verkeerde gegevens op te slaan', 'Draft v4');
  contextBox(doc, [
    { label: 'Rol', value: 'Iedereen' },
    { label: 'Gebruik bij', value: 'Probleem, blokkade of twijfel', size: 2.8 },
    { label: 'Vooraf', value: 'Stop en laat de pagina open', size: 2.45 },
  ]);

  doc.rect(12, 64, 186, 21, colors.helpSoft, '#FDA4AF', 0.5, 2);
  doc.text(18, 72.5, 'Algemene stopregel', { size: 3.6, weight: 900, fill: colors.help });
  doc.text(18, 79, 'STOP voor je opslaat als scherm en fysiek apparaat niet overeenkomen. Vraag je supervisor.', {
    size: 2.45,
    weight: 700,
  });

  const acRef = { w: 34, icon: 'AC', label: 'AC-01 Login', color: colors.ac, fill: '#EFF6FF' };
  const scRef = { w: 33, icon: 'SC', label: 'SC-01 Scan', color: colors.sc, fill: colors.astSoft };
  const astRef = { w: 35, icon: 'AST', label: 'AST-01 Open', color: colors.ast, fill: colors.astSoft };
  const wfRef = { w: 34, icon: 'WF', label: 'WF-01 Start', color: colors.wf, fill: colors.wfSoft };

  problemTile(doc, 12, 92, 59, 37, '!', 'Geen account', ['Vraag beheerder of supervisor.', 'Gebruik nooit andermans account.'], acRef);
  problemTile(doc, 76, 92, 59, 37, '!', 'Wachtwoord kwijt', ['Vraag je supervisor om', 'het wachtwoord te resetten.'], acRef);
  problemTile(doc, 140, 92, 58, 37, 'i', 'Geen telefoon', ['Gebruik een laptop.', 'Open https://snipe.inbit/.'], acRef);

  problemTile(doc, 12, 136, 59, 37, '!', 'Camera opent niet', ['Controleer cameratoegang.', 'Gebruik handmatig zoeken.'], scRef);
  problemTile(doc, 76, 136, 59, 37, 'QR', 'QR beschadigd', ['Zoek op asset tag', 'of serienummer.'], scRef);
  problemTile(doc, 140, 136, 58, 37, '!', 'Verkeerde asset', ['STOP. Wijzig niets.', 'Vraag je supervisor.'], astRef);

  problemTile(doc, 12, 180, 59, 37, '?', 'Geen workflow', ['Controleer asset en model.', 'Vraag je supervisor.'], wfRef);
  problemTile(doc, 76, 180, 59, 37, '!', 'Geen rechten', ['Niet omzeilen.', 'Vraag om de juiste rol.'], acRef);
  problemTile(doc, 140, 180, 58, 37, '!', 'Printer/label faalt', ['Stop met labelen.', 'Meld het bij supervisor.']);

  standardFooter(doc, {
    doneY: 229,
    done: 'Je weet wat je veilig kunt doen voordat je verdergaat.',
    refs: [
      { w: 35, icon: 'AC', label: 'AC-01 Login', color: colors.ac, fill: '#EFF6FF' },
      { w: 35, icon: 'SC', label: 'SC-01 Scan', color: colors.sc, fill: colors.astSoft },
      { w: 36, icon: 'AST', label: 'AST-01 Open', color: colors.ast, fill: colors.astSoft },
    ],
    source: `Bron: lokale supportafspraken + huidige gidsen | ${today}`,
  });
  return doc.render();
}

function buildWf01(images) {
  const doc = new SvgDoc(images, colors.wf);
  header(doc, 'WF-01', 'Workflow starten', 'Start een workflow vanaf de juiste geopende asset', 'Draft v4');
  contextBox(doc, [
    { label: 'Rol', value: 'Refurbisher' },
    { label: 'Nodig', value: 'Open asset + workflowrechten', size: 2.8 },
    { label: 'Vooraf', value: 'Asset open', guide: { icon: 'AST', ref: '(AST-01 Open)', color: colors.ast } },
  ]);

  const s1 = { x: 12, y: 68, w: 88, h: 62 };
  doc.rect(s1.x, s1.y, s1.w, s1.h, colors.white, colors.line, 0.45, 2);
  doc.stepBadge(s1.x, s1.y, '1');
  doc.text(s1.x + 10, s1.y + 9, 'Open tab Tests', { size: 4.05, weight: 900 });
  doc.text(s1.x + 10, s1.y + 15.5, ['Controleer eerst de assettitel.', 'Tik daarna op het test-icoon.'], { size: 2.25, fill: colors.muted, lh: 3.05 });
  const titleFrame = { x: s1.x + 10, y: s1.y + 25, w: 68, h: 4 };
  doc.image('wfWide', titleFrame, { x: 55, y: 145, w: 680, h: 40 }, { r: 1.2, fit: 'contain' });
  doc.imageBadge(titleFrame.x, titleFrame.y, '1A');
  const tabFrame = { x: s1.x + 10, y: s1.y + 35, w: 68, h: 9 };
  const tabPlacement = doc.image('wfWide', tabFrame, { x: 55, y: 270, w: 680, h: 90 }, { r: 1.2, fit: 'contain' });
  doc.imageBadge(tabFrame.x, tabFrame.y, '1B');
  doc.circle(tabPlacement.x + 393 * tabPlacement.scale, tabPlacement.y + 301 * tabPlacement.scale, 3.5, colors.help, 0.7);
  doc.text(titleFrame.x, s1.y + 51.5, '1A Controleer titel. 1B Tik op het test-icoon.', { size: 1.85, fill: colors.ink });

  const s2 = { x: 110, y: 68, w: 88, h: 62 };
  doc.rect(s2.x, s2.y, s2.w, s2.h, colors.white, colors.line, 0.45, 2);
  doc.stepBadge(s2.x, s2.y, '2');
  doc.text(s2.x + 10, s2.y + 9, 'Kies profiel', { size: 4.05, weight: 900 });
  doc.text(s2.x + 10, s2.y + 15.5, 'Kies het profiel voor dit apparaat.', { size: 2.25, fill: colors.muted });
  doc.text(s2.x + 10, s2.y + 20, 'STOP als het juiste profiel onduidelijk is.', { size: 2.05, weight: 800, fill: colors.help });
  const profileFrame = { x: s2.x + 10, y: s2.y + 25, w: 68, h: 13.5 };
  const profilePlacement = doc.image('wfWide', profileFrame, { x: 55, y: 270, w: 680, h: 135 }, { r: 1.2, fit: 'contain' });
  doc.imageBadge(profileFrame.x, profileFrame.y, '2A');
  doc.rect(
    profilePlacement.x + 72 * profilePlacement.scale,
    profilePlacement.y + 352 * profilePlacement.scale,
    653 * profilePlacement.scale,
    40 * profilePlacement.scale,
    'none',
    colors.help,
    0.7,
    1,
  );
  doc.text(profileFrame.x, s2.y + 57, 'Voorbeeld: Standard Diagnostics.', { size: 1.85 });

  const s3 = { x: 12, y: 137, w: 88, h: 66 };
  doc.rect(s3.x, s3.y, s3.w, s3.h, colors.white, colors.line, 0.45, 2);
  doc.stepBadge(s3.x, s3.y, '3');
  doc.text(s3.x + 10, s3.y + 9, 'Start workflow', { size: 4.05, weight: 900 });
  doc.text(s3.x + 10, s3.y + 15.5, 'Tik een keer op Nieuwe workflow starten.', { size: 2.2, fill: colors.muted });
  doc.text(s3.x + 10, s3.y + 20, 'Niet opnieuw starten als al een workflow actief is.', { size: 1.95, weight: 800, fill: colors.help });
  const startFrame = { x: s3.x + 10, y: s3.y + 26, w: 68, h: 22 };
  const startPlacement = doc.image('wfWide', startFrame, { x: 60, y: 560, w: 680, h: 125 }, { r: 1.2, fit: 'contain' });
  doc.imageBadge(startFrame.x, startFrame.y, '3A');
  doc.rect(
    startPlacement.x + 73 * startPlacement.scale,
    startPlacement.y + 612 * startPlacement.scale,
    653 * startPlacement.scale,
    36 * startPlacement.scale,
    'none',
    colors.help,
    0.7,
    1,
  );
  doc.text(startFrame.x, s3.y + 55, 'Controleer eerst of er al een open workflow staat.', { size: 1.85 });

  const s4 = { x: 110, y: 137, w: 88, h: 66 };
  doc.rect(s4.x, s4.y, s4.w, s4.h, colors.white, colors.line, 0.45, 2);
  doc.stepBadge(s4.x, s4.y, '4');
  doc.text(s4.x + 10, s4.y + 9, 'Controleer kaarten', { size: 4.05, weight: 900 });
  doc.text(s4.x + 10, s4.y + 15.5, ['Wacht tot resultaatkaarten zichtbaar zijn.', 'Ga daarna verder met WF-02.'], { size: 2.2, fill: colors.muted, lh: 3.05 });
  const cardsFrame = { x: s4.x + 10, y: s4.y + 27, w: 68, h: 28 };
  doc.image('wfCards', cardsFrame, { x: 40, y: 610, w: 700, h: 390 }, { r: 1.2 });
  doc.imageBadge(cardsFrame.x, cardsFrame.y, '4A');
  doc.text(cardsFrame.x, s4.y + 61, 'De workflow is nu actief.', { size: 1.85 });

  doc.text(12, 210, 'Hulp bij workflow starten', { size: 2.8, weight: 800, fill: colors.muted });
  doc.helpTile(12, 215, 41.5, 'Geen knop', ['Controleer rechten', 'en assetstatus.']);
  doc.helpTile(57.8, 215, 41.5, 'Verkeerd profiel', ['STOP. Vraag', 'je supervisor.'], '!');
  doc.helpTile(103.6, 215, 41.5, 'Al gestart', ['Start niet opnieuw.', 'Open bestaande run.'], '!');
  doc.helpTile(149.4, 215, 41.5, 'Geen kaarten', ['Vraag supervisor', 'of gebruik HELP-01.'], '?');

  standardFooter(doc, {
    doneY: 242,
    done: 'De gekozen workflow is actief en de resultaatkaarten zijn zichtbaar.',
    refs: [
      { w: 36, icon: 'AST', label: 'AST-01 Open', color: colors.ast, fill: colors.astSoft },
      { w: 35, icon: 'WF', label: 'WF-02 Verder', color: colors.wf, fill: colors.wfSoft },
      { w: 39, icon: '?', label: 'HELP-01 Help', color: colors.help, fill: colors.helpSoft },
    ],
    source: `Bron: gedeelde screenshots + gecontroleerde testopnamen | ${today}`,
  });
  return doc.render();
}

function htmlFor(svg, title) {
  return `<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>${xml(title)}</title>
  <style>
    @page { size: A4; margin: 0; }
    html, body { width: 210mm; height: 297mm; margin: 0; padding: 0; overflow: hidden; background: #dfe3ea; }
    .page, .page > svg { display: block; width: 210mm; height: 297mm; overflow: hidden; background: white; }
  </style>
</head>
<body><div class="page">${svg}</div></body>
</html>`;
}

async function main() {
  fs.mkdirSync(outDir, { recursive: true });
  for (const file of Object.values(sourceFiles)) {
    if (!fs.existsSync(file)) throw new Error(`Missing source image: ${file}`);
  }

  const images = Object.fromEntries(Object.entries(sourceFiles).map(([key, file]) => [key, loadImage(file)]));
  const guides = [
    { name: 'AST-01-open-asset-v13-draft', svg: buildAst01(images) },
    { name: 'HELP-01-problems-v4-draft', svg: buildHelp01(images) },
    { name: 'WF-01-start-workflow-v4-draft', svg: buildWf01(images) },
  ];

  const browser = await chromium.launch({ headless: true });
  try {
    for (const guide of guides) {
      const outputs = {
        svg: path.join(outDir, `${guide.name}.svg`),
        html: path.join(outDir, `${guide.name}.html`),
        png: path.join(outDir, `${guide.name}-proof.png`),
        pdf: path.join(outDir, `${guide.name}-proof.pdf`),
      };
      const html = htmlFor(guide.svg, guide.name);
      fs.writeFileSync(outputs.svg, guide.svg, 'utf8');
      fs.writeFileSync(outputs.html, html, 'utf8');

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
      await page.close();
      guide.outputs = outputs;
    }
  } finally {
    await browser.close();
  }

  const summary = [
    '# Next Operator Guide Drafts',
    '',
    `Generated: ${today}`,
    '',
    '## Guides',
    ...guides.flatMap((guide) => [
      `- ${guide.name}`,
      `  - PDF: ${guide.outputs.pdf}`,
      `  - PNG: ${guide.outputs.png}`,
      `  - SVG: ${guide.outputs.svg}`,
    ]),
    '',
    '## Canonical Sources',
    ...Object.entries(sourceFiles).map(([key, file]) => `- ${sourceIds[key]}: ${file}`),
    '',
    '## Notes',
    '- AST-01 intentionally reuses the AC-01/SC-01 dashboard, scanner, search, and asset verification evidence.',
    '- HELP-01 is a non-sequential troubleshooting sheet; it does not add screenshots that do not answer a visual question.',
    '- WF-01 uses existing controlled development captures. Printed guide text does not present the development URL as the operator destination.',
    '- All digital-guide QR patterns remain draft placeholders.',
    '',
  ].join('\n');
  const summaryPath = path.join(outDir, 'next-guide-drafts-summary.md');
  fs.writeFileSync(summaryPath, summary, 'utf8');

  console.log(JSON.stringify({ outDir, guides: guides.map(({ name, outputs }) => ({ name, outputs })), summaryPath }, null, 2));
}

main().catch((error) => {
  console.error(error);
  process.exitCode = 1;
});
