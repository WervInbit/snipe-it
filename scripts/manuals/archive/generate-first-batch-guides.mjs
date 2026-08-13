import fs from 'node:fs';
import path from 'node:path';
import { createRequire } from 'node:module';

const require = createRequire(import.meta.url);
const { chromium } = require('playwright');

const today = '2026-07-02';
const outDir = 'C:\\Users\\Gebruiker\\Documents\\snipe-it manuals\\layout-proofs\\2026-07-02-first-batch-affinity-v1';
const sourceDir = 'C:\\Users\\Gebruiker\\Documents\\snipe-it manuals\\screenshot-source\\2026-06-25-blocks';
const refreshDir = 'C:\\Users\\Gebruiker\\Documents\\snipe-it manuals\\screenshot-source\\2026-07-02-first-batch-refresh';
const astProofDir = 'C:\\Users\\Gebruiker\\Documents\\snipe-it manuals\\layout-proofs\\2026-06-30-ast01-clean-open-asset';
const astShotDir = path.join(astProofDir, 'screenshots');

const imageFiles = {
  acLogin: path.join(sourceDir, 'AC-01-01-login-form-mobile.png'),
  acFilled: path.join(sourceDir, 'AC-01-02-login-filled-mobile.png'),
  acDashboard: path.join(astProofDir.replace('2026-06-30-ast01-clean-open-asset', '2026-06-30-ac01-clean-login'), 'screenshots', 'AC-01-clean-03-dashboard-no-alert-phone.png'),
  dashboardScan: path.join(astShotDir, 'AST-01-clean-01-dashboard-scan-entry-phone.png'),
  scanPage: path.join(astShotDir, 'AST-01-clean-02-scan-page-phone.png'),
  searchAsset: path.join(astShotDir, 'AST-01-clean-03-search-asset-tag-phone.png'),
  searchResult: path.join(astShotDir, 'AST-01-clean-04-hardware-result-phone.png'),
  assetVerify: path.join(astShotDir, 'AST-01-clean-05-asset-detail-verify-phone.png'),
  assetTagModel: path.join(astShotDir, 'AST-01-clean-06-asset-detail-tag-model-phone.png'),
  assetQrLabel: path.join(astShotDir, 'AST-01-clean-07-asset-detail-qr-label-phone.png'),
  cameraQr: 'C:\\Users\\Gebruiker\\Downloads\\Screenshot_20260630-132442_Chrome.jpg',
  scScanPage: path.join(sourceDir, 'SC-01-01-scan-page-mobile.png'),
  astDetailTop: path.join(sourceDir, 'AST-01-02-asset-detail-top-mobile.png'),
  ast02Dashboard: path.join(sourceDir, 'AST-02-01-dashboard-mobile.png'),
  wfTestsTab: path.join(sourceDir, 'WF-01-01-asset-tests-tab-mobile.png'),
  wfForm: path.join(sourceDir, 'WF-01-02-start-workflow-form-mobile.png'),
  cmpComponents: path.join(sourceDir, 'CMP-04-01-asset-components-tab-mobile.png'),
  wfTestsWideLive: path.join(refreshDir, 'WF-01-tests-tab-wide-live.png'),
  cmpWideLive: path.join(refreshDir, 'CMP-04-components-tab-wide-live.png'),
  cmpModalLocked: path.join(refreshDir, 'CMP-04-tray-modal-locked-serial-mobile.png'),
  cmpModalUnlocked: path.join(refreshDir, 'CMP-04-tray-modal-unlocked-serial-mobile.png'),
};

const colors = {
  ink: '#102033',
  muted: '#53657A',
  line: '#C8D5E2',
  faint: '#F6F9FC',
  ac: '#2563EB',
  sc: '#0E8A75',
  ast: '#0E8A45',
  wf: '#F97316',
  cmp: '#D97706',
  help: '#EF3340',
  tealDark: '#087162',
  blueSoft: '#EFF6FF',
  greenSoft: '#ECFDF5',
  orangeSoft: '#FFF7ED',
  redSoft: '#FFF1F3',
  amberSoft: '#FFFBEB',
  graySoft: '#F8FAFC',
};

const guideColors = {
  AC: colors.ac,
  SC: colors.sc,
  AST: colors.ast,
  WF: colors.wf,
  CMP: colors.cmp,
  HELP: colors.help,
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
  if (!fs.existsSync(file)) {
    return null;
  }
  const buffer = fs.readFileSync(file);
  const size = imageSize(buffer);
  return {
    ...size,
    file,
    uri: `data:${size.mime};base64,${buffer.toString('base64')}`,
  };
}

const images = Object.fromEntries(
  Object.entries(imageFiles).map(([key, file]) => [key, loadImage(file)]),
);

function lines(value) {
  if (Array.isArray(value)) {
    return value;
  }
  return [value];
}

class SvgDoc {
  constructor(meta) {
    this.meta = meta;
    this.defs = [];
    this.body = [];
    this.clipId = 0;
    this.gaps = [];
  }

  add(markup) {
    this.body.push(markup);
  }

  rect(x, y, w, h, fill = '#FFFFFF', stroke = colors.line, sw = 0.35, r = 1.6, extra = '') {
    this.add(`<rect x="${x}" y="${y}" width="${w}" height="${h}" rx="${r}" fill="${fill}" stroke="${stroke}" stroke-width="${sw}" ${extra}/>`);
  }

  line(x1, y1, x2, y2, stroke = colors.line, sw = 0.35) {
    this.add(`<line x1="${x1}" y1="${y1}" x2="${x2}" y2="${y2}" stroke="${stroke}" stroke-width="${sw}"/>`);
  }

  text(x, y, value, opts = {}) {
    const {
      size = 3,
      weight = 400,
      fill = colors.ink,
      anchor = 'start',
      lh = size * 1.25,
      family = 'Arial, Helvetica, sans-serif',
    } = opts;
    const tspans = lines(value)
      .map((line, index) => `<tspan x="${x}" dy="${index === 0 ? 0 : lh}">${xml(line)}</tspan>`)
      .join('');
    this.add(`<text text-anchor="${anchor}" font-family="${family}" font-size="${size}" font-weight="${weight}" fill="${fill}" x="${x}" y="${y}">${tspans}</text>`);
  }

  richText(x, y, spans, opts = {}) {
    const {
      family = 'Arial, Helvetica, sans-serif',
      fill = colors.ink,
      weight = 400,
      size = 3,
    } = opts;
    const body = spans
      .map((span) => `<tspan font-size="${span.size ?? size}" font-weight="${span.weight ?? weight}" fill="${span.fill ?? fill}">${xml(span.text)}</tspan>`)
      .join('');
    this.add(`<text font-family="${family}" font-size="${size}" font-weight="${weight}" fill="${fill}" x="${x}" y="${y}">${body}</text>`);
  }

  card(x, y, w, h, fill = '#FFFFFF', stroke = colors.line) {
    this.rect(x, y, w, h, fill, stroke, 0.35, 1.6);
  }

  stepBadge(cx, cy, label, fill = this.meta.color) {
    this.add(`<circle cx="${cx}" cy="${cy}" r="4.6" fill="${fill}"/>`);
    this.text(cx, cy + 1.25, label, { size: label.length > 1 ? 2.35 : 3.4, weight: 800, fill: '#FFFFFF', anchor: 'middle' });
  }

  imageBadge(cx, cy, label, fill = this.meta.color) {
    this.add(`<circle cx="${cx}" cy="${cy}" r="5.3" fill="#FFFFFF" fill-opacity="0.28" stroke="${fill}" stroke-width="1.05"/>`);
    this.text(cx, cy + 1.05, label, { size: label.length > 1 ? 2.3 : 3.05, weight: 800, fill, anchor: 'middle' });
  }

  clippedImage(key, frame, crop = null, options = {}) {
    const img = images[key];
    if (!img) {
      this.placeholder(frame.x, frame.y, frame.w, frame.h, `SCREENSHOT NEEDED`, options.note ?? key);
      this.gaps.push(`${this.meta.code}: missing image ${key}`);
      return;
    }
    const area = crop ?? { x: 0, y: 0, w: img.width, h: img.height };
    const id = `clip-${this.clipId++}`;
    const radius = options.r ?? 1.2;
    this.defs.push(`<clipPath id="${id}"><rect x="${frame.x}" y="${frame.y}" width="${frame.w}" height="${frame.h}" rx="${radius}"/></clipPath>`);
    const scale = options.fit === 'contain'
      ? Math.min(frame.w / area.w, frame.h / area.h)
      : Math.max(frame.w / area.w, frame.h / area.h);
    const cropW = area.w * scale;
    const cropH = area.h * scale;
    const ix = frame.x + (frame.w - cropW) / 2 - area.x * scale;
    const iy = frame.y + (frame.h - cropH) / 2 - area.y * scale;
    const iw = img.width * scale;
    const ih = img.height * scale;

    this.rect(frame.x, frame.y, frame.w, frame.h, '#FFFFFF', '#D6DEE8', 0.35, radius);
    this.add(`<image href="${img.uri}" x="${ix}" y="${iy}" width="${iw}" height="${ih}" clip-path="url(#${id})"/>`);
    this.rect(frame.x, frame.y, frame.w, frame.h, 'none', options.stroke ?? '#D6DEE8', options.sw ?? 0.35, radius);
  }

  placeholder(x, y, w, h, title, detail = '') {
    this.rect(x, y, w, h, colors.graySoft, '#CBD5E1', 0.45, 1.4);
    this.add(`<path d="M ${x + 3} ${y + 3} L ${x + w - 3} ${y + h - 3} M ${x + w - 3} ${y + 3} L ${x + 3} ${y + h - 3}" stroke="#CBD5E1" stroke-width="0.45"/>`);
    this.text(x + w / 2, y + h / 2 - 1.5, title, { size: 2.7, weight: 800, fill: colors.muted, anchor: 'middle' });
    if (detail) {
      this.text(x + w / 2, y + h / 2 + 3.5, detail, { size: 2.15, fill: colors.muted, anchor: 'middle' });
    }
  }

  focusRect(x, y, w, h, stroke = colors.help) {
    this.rect(x, y, w, h, 'none', stroke, 0.75, 1.2);
  }

  focusCircle(cx, cy, r, stroke = colors.help) {
    this.add(`<circle cx="${cx}" cy="${cy}" r="${r}" fill="none" stroke="${stroke}" stroke-width="0.75"/>`);
  }

  guideChip(x, y, w, family, code, label, fill = null) {
    const stroke = guideColors[family] ?? this.meta.color;
    const bg = fill ?? (family === 'HELP' ? colors.redSoft : family === 'WF' ? colors.orangeSoft : family === 'CMP' ? colors.amberSoft : family === 'AC' ? colors.blueSoft : colors.greenSoft);
    this.rect(x, y, w, 6.2, bg, stroke, 0.35, 1.8);
    this.add(`<circle cx="${x + 4}" cy="${y + 3.1}" r="2.1" fill="#FFFFFF" fill-opacity="0.45" stroke="${stroke}" stroke-width="0.35"/>`);
    this.text(x + 4, y + 3.85, family, { size: family.length > 2 ? 1.2 : 1.65, weight: 800, fill: stroke, anchor: 'middle' });
    this.text(x + 8, y + 4.15, `${code} ${label}`, { size: 2.15, weight: 700, fill: stroke });
  }

  guidePrereq(x, y, family, main, ref) {
    const stroke = guideColors[family] ?? this.meta.color;
    this.add(`<circle cx="${x + 2.6}" cy="${y - 0.9}" r="2.25" fill="#FFFFFF" fill-opacity="0.35" stroke="${stroke}" stroke-width="0.45"/>`);
    this.text(x + 2.6, y - 0.1, family, { size: family.length > 2 ? 1.25 : 1.65, weight: 800, fill: stroke, anchor: 'middle' });
    this.richText(x + 6.2, y, [
      { text: main, size: 2.55, weight: 700, fill: stroke },
      { text: ` ${ref}`, size: 2.05, weight: 700, fill: stroke },
    ], { fill: stroke });
  }

  helpTile(x, y, w, title, detail, family = 'HELP') {
    const stroke = family === 'HELP' ? colors.help : this.meta.color;
    const bg = family === 'HELP' ? colors.redSoft : colors.orangeSoft;
    this.rect(x, y, w, 19, bg, stroke === colors.help ? '#FDA4AF' : '#FDBA74', 0.35, 1.5);
    this.add(`<circle cx="${x + 4.5}" cy="${y + 5.2}" r="2.8" fill="#FFFFFF" fill-opacity="0.5" stroke="${stroke}" stroke-width="0.45"/>`);
    this.text(x + 4.5, y + 6.05, family === 'HELP' ? '!' : '?', { size: 2.45, weight: 800, fill: stroke, anchor: 'middle' });
    this.text(x + 8.4, y + 5.5, title, { size: 2.25, weight: 800, fill: colors.tealDark });
    this.text(x + 3, y + 12, detail, { size: 2.0, fill: colors.ink, lh: 3 });
  }

  qrPlaceholder(x, y, size) {
    this.rect(x, y, size, size, '#FFFFFF', colors.ink, 0.45, 0.5);
    const cells = [
      [1, 1], [2, 1], [4, 1], [6, 1], [1, 2], [4, 2], [5, 2], [7, 2],
      [2, 3], [3, 3], [6, 3], [1, 4], [5, 4], [7, 4], [2, 5], [4, 5],
      [6, 5], [7, 5], [1, 6], [3, 6], [5, 6], [2, 7], [4, 7], [7, 7],
    ];
    const unit = size / 9;
    for (const [cx, cy] of cells) {
      this.add(`<rect x="${x + cx * unit}" y="${y + cy * unit}" width="${unit * 0.72}" height="${unit * 0.72}" fill="${colors.ink}"/>`);
    }
  }

  header(subtitle = this.meta.subtitle) {
    this.rect(0, 0, 210, 297, '#FFFFFF', 'none', 0, 0);
    this.add(`<rect x="12" y="10" width="2.2" height="15" fill="${this.meta.color}"/>`);
    this.text(17, 18, `${this.meta.code} ${this.meta.title}`, { size: 7.0, weight: 800 });
    this.text(17, 24, subtitle, { size: 2.9, fill: colors.muted });
    this.rect(160, 11, 38, 10, colors.greenSoft, '#86EFAC', 0.35, 1.5);
    this.text(179, 17.3, `Draft ${this.meta.version ?? today}`, { size: 2.35, weight: 700, fill: colors.tealDark, anchor: 'middle' });
  }

  context(fields) {
    this.rect(12, 35, 186, 16, colors.faint, colors.line, 0.35, 1.4);
    const cols = fields.length === 3
      ? [{ x: 17, w: 37 }, { x: 58, w: 80 }, { x: 143, w: 47 }]
      : [{ x: 17, w: 58 }, { x: 83, w: 105 }];
    fields.forEach((field, index) => {
      const col = cols[index];
      this.text(col.x, 40, field.label, { size: 2.25, weight: 700, fill: colors.muted });
      if (field.guide) {
        this.guidePrereq(col.x, 45.4, field.guide.family, field.value, field.guide.ref);
      } else {
        this.text(col.x, 45.3, field.value, { size: field.size ?? 2.85, weight: 700 });
      }
    });
  }

  doneBox(textValue, related = []) {
    this.rect(12, 270, 148, 14, colors.greenSoft, '#86EFAC', 0.35, 1.3);
    this.text(17, 275, 'Klaar als', { size: 3, weight: 800, fill: colors.tealDark });
    this.text(17, 280, textValue, { size: 2.25, fill: colors.tealDark });
    this.text(12, 289, 'Relevante gidsen', { size: 2.2, weight: 700, fill: colors.muted });
    let x = 36;
    related.forEach((chip) => {
      this.guideChip(x, 285.2, chip.w, chip.family, chip.code, chip.label);
      x += chip.w + 3;
    });
    this.text(12, 294, `Bron: dev.inbit screenshots | Concept first batch | ${today}`, { size: 1.9, fill: colors.muted });
    this.qrPlaceholder(179, 263, 22);
    this.text(190, 289, 'Digitale gids', { size: 2.1, weight: 700, fill: colors.ink, anchor: 'middle' });
  }

  toSvg() {
    return `<svg xmlns="http://www.w3.org/2000/svg" width="210mm" height="297mm" viewBox="0 0 210 297">
<defs>${this.defs.join('\n')}</defs>
${this.body.join('\n')}
</svg>`;
  }
}

function makeDoc(code, title, subtitle, family, version = today) {
  return new SvgDoc({
    code,
    title,
    subtitle,
    family,
    version,
    color: guideColors[family],
  });
}

function stepTitle(doc, x, y, num, title, body, color = doc.meta.color) {
  doc.stepBadge(x + 5, y + 6, num, color);
  doc.text(x + 14, y + 6.8, title, { size: 4.1, weight: 800 });
  doc.text(x + 14, y + 13, body, { size: 2.55, fill: colors.muted, lh: 3.2 });
}

function visualLabel(doc, x, y, label, title, caption) {
  doc.imageBadge(x, y, label);
  doc.text(x + 7.5, y + 1.2, title, { size: 2.6, weight: 800 });
  doc.text(x + 7.5, y + 6.5, caption, { size: 2.15, fill: colors.ink, lh: 2.8 });
}

function renderAc01() {
  const doc = makeDoc('AC-01', 'Login', 'Log in en controleer dat het dashboard zichtbaar is', 'AC');
  doc.header();
  doc.context([
    { label: 'Rol', value: 'Iedereen' },
    { label: 'Nodig', value: 'Account + telefoon of laptop' },
    { label: 'Adres', value: 'https://dev.inbit', size: 2.7 },
  ]);

  doc.card(12, 57, 186, 112);
  stepTitle(doc, 12, 57, '1', 'Open en log in', ['Gebruik de browser of telefoon.', 'Vul daarna je accountgegevens in.'], colors.ac);
  const panels = [
    {
      label: '1A',
      title: 'Open site',
      caption: ['Ga naar', 'dev.inbit.'],
      image: 'acLogin',
      frame: { x: 20, y: 82, w: 50, h: 62 },
      crop: { x: 35, y: 0, w: 710, h: 850 },
    },
    {
      label: '1B',
      title: 'Vul gegevens in',
      caption: ['Gebruikersnaam', 'en wachtwoord.'],
      image: 'acFilled',
      frame: { x: 80, y: 82, w: 50, h: 62 },
      crop: { x: 35, y: 0, w: 710, h: 850 },
    },
    {
      label: '1C',
      title: 'Dashboard',
      caption: ['Controleer dat', 'Scan QR zichtbaar is.'],
      image: 'acDashboard',
      frame: { x: 140, y: 82, w: 50, h: 62 },
      crop: { x: 0, y: 0, w: 780, h: 900 },
    },
  ];
  panels.forEach((panel) => {
    doc.clippedImage(panel.image, panel.frame, panel.crop, { r: 1.4 });
    doc.imageBadge(panel.frame.x, panel.frame.y, panel.label, colors.ac);
    doc.text(panel.frame.x, 151, panel.title, { size: 2.75, weight: 800 });
    doc.text(panel.frame.x, 156, panel.caption, { size: 2.25, fill: colors.ink, lh: 3 });
  });

  doc.text(12, 180, 'Hulp bij login', { size: 2.6, weight: 700, fill: colors.muted });
  doc.helpTile(12, 184, 42, 'Geen account', ['Vraag beheerder', 'om account.'], 'HELP');
  doc.helpTile(59, 184, 42, 'Wachtwoord', ['Gebruik vergeten', 'of vraag hulp.'], 'HELP');
  doc.helpTile(106, 184, 42, 'Geen telefoon', ['Gebruik laptop', 'met camera.'], 'HELP');
  doc.helpTile(153, 184, 42, 'Sessie verlopen', ['Log opnieuw in', 'voor scannen.'], 'HELP');

  doc.card(12, 211, 186, 35, colors.blueSoft, '#93C5FD');
  doc.text(18, 220, 'Let op', { size: 3.4, weight: 800, fill: colors.ac });
  doc.text(18, 227, ['De login gids bewijst alleen dat je binnen bent.', 'Ga daarna naar SC-01 voor scannen of AST-01 voor asset controleren.'], { size: 2.65, fill: colors.ink, lh: 4 });
  doc.guideChip(132, 218, 42, 'SC', 'SC-01', 'Scan Asset');
  doc.guideChip(132, 228, 42, 'AST', 'AST-01', 'Open Asset');

  doc.doneBox('Dashboard of Scan QR is zichtbaar.', [
    { family: 'SC', code: 'SC-01', label: 'Scan', w: 29 },
    { family: 'AST', code: 'AST-01', label: 'Open Asset', w: 35 },
    { family: 'HELP', code: 'HELP-01', label: 'Problemen', w: 34 },
  ]);
  return doc;
}

function renderSc01() {
  const doc = makeDoc('SC-01', 'Scan asset', 'Scan een QR-label of gebruik zoeken als scannen niet lukt', 'SC');
  doc.header();
  doc.context([
    { label: 'Rol', value: 'Refurbisher' },
    { label: 'Nodig', value: 'Telefoon met camera + QR/asset tag' },
    { label: 'Vooraf', value: 'Ingelogd', guide: { family: 'AC', ref: '(AC-01 Login)' } },
  ]);

  doc.card(12, 57, 186, 52);
  stepTitle(doc, 12, 57, '1', 'Open de scanner', ['Kies de Scan QR kaart of het camera-icoon.', 'Beide openen dezelfde scanner.']);
  doc.clippedImage('dashboardScan', { x: 22, y: 78, w: 70, h: 24 }, { x: 150, y: 530, w: 630, h: 330 });
  doc.imageBadge(22, 78, '1A');
  doc.text(95, 84, 'Dashboard', { size: 2.8, weight: 800 });
  doc.text(95, 90, ['Tik op Scan QR.', 'Deze kaart staat op het dashboard.'], { size: 2.25, fill: colors.ink, lh: 3 });
  doc.clippedImage('scanPage', { x: 130, y: 78, w: 46, h: 24 }, { x: 240, y: 55, w: 500, h: 260 });
  doc.imageBadge(130, 78, '1B');
  doc.text(178, 84, 'Bovenbalk', { size: 2.5, weight: 800 });
  doc.text(178, 90, ['Of tik camera.', ''], { size: 2.15, fill: colors.ink, lh: 3 });

  doc.card(12, 115, 88, 72);
  stepTitle(doc, 12, 115, '2', 'Richt op QR', ['Houd het label rustig in beeld.', 'Wacht tot de asset opent.']);
  doc.clippedImage('cameraQr', { x: 21, y: 137, w: 72, h: 42 }, { x: 120, y: 720, w: 825, h: 650 });
  doc.imageBadge(21, 137, '2A');
  doc.rect(75, 119, 20, 13, colors.greenSoft, '#86EFAC', 0.35, 1.3);
  doc.text(77, 124, 'QR-locatie', { size: 1.9, weight: 800, fill: colors.tealDark });
  doc.text(77, 128.5, 'Onder/achterkant.', { size: 1.65, fill: colors.tealDark });

  doc.card(106, 115, 92, 72);
  stepTitle(doc, 106, 115, '3', 'Als camera niet werkt', ['Gebruik de zoekbalk.', 'Typ asset tag of QR-code.']);
  doc.clippedImage('searchAsset', { x: 119, y: 139, w: 64, h: 16 }, { x: 50, y: 225, w: 520, h: 120 });
  doc.imageBadge(119, 139, '3A');
  doc.text(116, 161, 'Zoek op INBIT-HG0001 of QR-code.', { size: 2.25, fill: colors.ink });
  doc.clippedImage('searchResult', { x: 119, y: 166, w: 64, h: 15 }, { x: 45, y: 1325, w: 690, h: 310 });
  doc.imageBadge(119, 166, '3B');

  doc.card(12, 194, 186, 39);
  stepTitle(doc, 12, 194, '4', 'Controleer de geopende asset', ['Vergelijk asset tag en apparaat voordat je verdergaat.']);
  doc.text(26, 211, 'STOP als de QR een andere asset opent of geen passend resultaat geeft.', { size: 2.35, weight: 800, fill: colors.help });
  doc.clippedImage('assetVerify', { x: 96, y: 205, w: 84, h: 17 }, { x: 0, y: 425, w: 780, h: 190 });
  doc.imageBadge(96, 205, '4A');

  doc.text(12, 242, 'Hulp bij scannen', { size: 2.6, weight: 700, fill: colors.muted });
  doc.helpTile(12, 246, 42, 'Camera', ['Sta toegang toe', 'of refresh.'], 'HELP');
  doc.helpTile(59, 246, 42, 'QR beschadigd', ['Typ asset tag', 'in zoekbalk.'], 'HELP');
  doc.helpTile(106, 246, 42, 'Geen resultaat', ['Controleer tag.', 'Vraag hulp.'], 'HELP');
  doc.helpTile(153, 246, 42, 'Geen telefoon', ['Gebruik laptop', 'of scanner.'], 'HELP');

  doc.doneBox('De juiste assetpagina is open.', [
    { family: 'AC', code: 'AC-01', label: 'Login', w: 30 },
    { family: 'AST', code: 'AST-01', label: 'Open Asset', w: 36 },
    { family: 'HELP', code: 'HELP-01', label: 'Problemen', w: 34 },
  ]);
  return doc;
}

function renderAst01() {
  const doc = makeDoc('AST-01', 'Asset openen', 'Open het juiste apparaat en controleer voor je iets wijzigt', 'AST');
  doc.header();
  doc.context([
    { label: 'Rol', value: 'Refurbisher' },
    { label: 'Nodig', value: 'Telefoon met camera + apparaat met QR/asset tag' },
    { label: 'Vooraf', value: 'Ingelogd', guide: { family: 'AC', ref: '(AC-01 Login)' } },
  ]);

  doc.card(12, 57, 186, 53);
  stepTitle(doc, 12, 57, '1', 'Open de scanner', ['Kies een van deze twee manieren.']);
  doc.clippedImage('dashboardScan', { x: 24, y: 76, w: 52, h: 26 }, { x: 220, y: 535, w: 590, h: 295 });
  doc.imageBadge(24, 76, '1A');
  doc.text(79, 82, 'Dashboard', { size: 2.65, weight: 800 });
  doc.text(79, 87, ['Tik op', 'Scan QR.'], { size: 2.3, fill: colors.ink, lh: 3.1 });
  doc.clippedImage('scanPage', { x: 108, y: 76, w: 52, h: 26 }, { x: 275, y: 65, w: 500, h: 250 });
  doc.imageBadge(108, 76, '1B');
  doc.text(163, 82, 'Bovenbalk', { size: 2.65, weight: 800 });
  doc.text(163, 87, ['Of tik op het', 'camera-icoon.'], { size: 2.3, fill: colors.ink, lh: 3.1 });

  doc.card(12, 115, 88, 71);
  stepTitle(doc, 12, 115, '2', 'Richt op QR', ['Houd de sticker in beeld', 'tot de asset opent.']);
  doc.rect(73, 119.5, 21, 13, colors.greenSoft, '#86EFAC', 0.35, 1.3);
  doc.text(75, 124.2, 'QR-locatie', { size: 1.9, weight: 800, fill: colors.tealDark });
  doc.text(75, 128.6, 'Onder/achterkant.', { size: 1.65, fill: colors.tealDark });
  doc.clippedImage('cameraQr', { x: 21, y: 136, w: 72, h: 42 }, { x: 120, y: 720, w: 825, h: 650 });
  doc.imageBadge(21, 136, '2');

  doc.card(106, 115, 92, 71);
  stepTitle(doc, 106, 115, '3', 'Zoek handmatig', ['Als scannen niet lukt:', 'typ asset tag of QR-code.']);
  doc.clippedImage('searchAsset', { x: 120, y: 138, w: 64, h: 15 }, { x: 50, y: 225, w: 520, h: 115 });
  doc.imageBadge(120, 138, '3A');
  doc.text(116, 158, 'Zoekbalk: typ INBIT-HG0001 of QR-code.', { size: 2.25, fill: colors.ink });
  doc.clippedImage('searchResult', { x: 120, y: 163, w: 64, h: 16 }, { x: 45, y: 1325, w: 690, h: 310 });
  doc.imageBadge(120, 163, '3B');
  doc.text(116, 183, 'Kies de juiste asset uit de resultaten.', { size: 2.25, fill: colors.ink });

  doc.card(12, 192, 186, 44);
  stepTitle(doc, 12, 192, '4', 'Controleer asset', ['Vergelijk titel, tag/model en apparaat.']);
  doc.text(25, 210, 'STOP als titel, tag/model of apparaat niet overeenkomt.', { size: 2.35, weight: 800, fill: colors.help });
  doc.clippedImage('assetVerify', { x: 22, y: 216, w: 82, h: 13 }, { x: 0, y: 425, w: 780, h: 185 });
  doc.imageBadge(22, 216, '4A');
  doc.clippedImage('assetTagModel', { x: 110, y: 216, w: 68, h: 13 }, { x: 0, y: 955, w: 780, h: 150 });
  doc.imageBadge(110, 216, '4B');

  doc.text(12, 244, 'Hulp bij openen', { size: 2.6, weight: 700, fill: colors.muted });
  doc.helpTile(12, 248, 37, 'Camera', 'Gebruik zoeken.', 'HELP');
  doc.helpTile(55, 248, 37, 'QR beschadigd', 'Typ asset tag.', 'HELP');
  doc.helpTile(98, 248, 37, 'Geen resultaat', ['Controleer code.', 'Vraag hulp.'], 'HELP');
  doc.helpTile(141, 248, 37, 'Geen telefoon', ['Gebruik laptop', 'of zoekbalk.'], 'HELP');

  doc.doneBox('De juiste assetpagina is open en komt overeen met het apparaat in je hand.', [
    { family: 'AC', code: 'AC-01', label: 'Login', w: 30 },
    { family: 'SC', code: 'SC-01', label: 'Scan', w: 29 },
    { family: 'HELP', code: 'HELP-01', label: 'Problemen', w: 34 },
  ]);
  return doc;
}

function band(doc, index, y, title, body, refs, image) {
  doc.card(12, y, 186, 36);
  doc.stepBadge(18, y + 10, String(index));
  doc.text(28, y + 9, title, { size: 4.0, weight: 800 });
  doc.text(28, y + 16, body, { size: 2.55, fill: colors.ink, lh: 3.4 });
  let chipX = 28;
  refs.forEach((ref) => {
    doc.guideChip(chipX, y + 26.5, ref.w, ref.family, ref.code, ref.label);
    chipX += ref.w + 3;
  });
  if (image) {
    doc.clippedImage(image.key, { x: 143, y: y + 5, w: 43, h: 21 }, image.crop, { r: 1.2, fit: 'contain' });
    doc.imageBadge(143, y + 5, `${index}A`);
  }
}

function renderAst02Front() {
  const doc = makeDoc('AST-02', 'Asset refurbishment', 'Hoofdroute van asset openen tot workflow en onderdelen', 'AST');
  doc.header();
  doc.context([
    { label: 'Rol', value: 'Refurbisher' },
    { label: 'Nodig', value: 'Asset in hand + telefoon/laptop' },
    { label: 'Vooraf', value: 'Ingelogd', guide: { family: 'AC', ref: '(AC-01 Login)' } },
  ]);

  band(doc, 1, 57, 'Login en scanner openen', ['Log in en open Scan QR of zoeken.', 'Gebruik de scan gids als je vastloopt.'], [
    { family: 'AC', code: 'AC-01', label: 'Login', w: 30 },
    { family: 'SC', code: 'SC-01', label: 'Scan', w: 29 },
  ], { key: 'dashboardScan', crop: { x: 140, y: 520, w: 640, h: 330 } });
  band(doc, 2, 97, 'Open de juiste asset', ['Scan of zoek de asset tag.', 'Controleer titel en tag voordat je verdergaat.'], [
    { family: 'AST', code: 'AST-01', label: 'Open Asset', w: 36 },
  ], { key: 'assetVerify', crop: { x: 0, y: 420, w: 780, h: 230 } });
  band(doc, 3, 137, 'Check status en waarschuwingen', ['Lees statusblokken bovenaan.', 'Los blokkades op of vraag hulp.'], [
    { family: 'AST', code: 'AST-01', label: 'Check', w: 28 },
    { family: 'HELP', code: 'HELP-01', label: 'Problemen', w: 34 },
  ], { key: 'wfTestsTab', crop: { x: 0, y: 360, w: 780, h: 360 } });
  band(doc, 4, 177, 'Start of vervolg workflow', ['Open Tests/Workflows.', 'Start de juiste workflow of ga verder met de open workflow.'], [
    { family: 'WF', code: 'WF-01', label: 'Start', w: 29 },
    { family: 'WF', code: 'WF-02', label: 'Uitvoeren', w: 33 },
  ], { key: 'wfTestsWideLive', crop: { x: 70, y: 600, w: 650, h: 120 } });
  band(doc, 5, 217, 'Onderdelen behandelen', ['Als een onderdeel uit het apparaat gaat:', 'gebruik de component gids en registreer serial.'], [
    { family: 'CMP', code: 'CMP-04', label: 'Naar tray', w: 35 },
  ], { key: 'cmpWideLive', crop: { x: 80, y: 430, w: 630, h: 240 } });

  doc.doneBox('Workflow is klaar of blokkades zijn duidelijk geescaleerd.', [
    { family: 'AST', code: 'AST-01', label: 'Open', w: 28 },
    { family: 'WF', code: 'WF-01', label: 'Start', w: 29 },
    { family: 'WF', code: 'WF-02', label: 'Uitvoeren', w: 35 },
    { family: 'CMP', code: 'CMP-04', label: 'Tray', w: 29 },
  ]);
  return doc;
}

function renderAst02Back() {
  const doc = makeDoc('AST-02B', 'Workflow details', 'Achterkant voor workflow-resultaten tijdens refurbishment', 'AST');
  doc.header();
  doc.context([
    { label: 'Rol', value: 'Refurbisher' },
    { label: 'Nodig', value: 'Open asset + actieve workflow' },
    { label: 'Vooraf', value: 'Workflow gestart', guide: { family: 'WF', ref: '(WF-01 Start)' } },
  ]);

  doc.card(12, 58, 86, 76);
  stepTitle(doc, 12, 58, '1', 'Werk item voor item', ['Begin bovenaan de lijst.', 'Lees instructie als die er is.']);
  doc.clippedImage('wfForm', { x: 23, y: 82, w: 63, h: 42 }, { x: 40, y: 610, w: 700, h: 470 });
  doc.imageBadge(23, 82, '1A');

  doc.card(104, 58, 94, 76);
  stepTitle(doc, 104, 58, '2', 'Kies resultaat', ['Tik Geslaagd of Mislukt.', 'Kies pas na fysieke controle.']);
  doc.clippedImage('wfForm', { x: 114, y: 82, w: 70, h: 26 }, { x: 40, y: 720, w: 700, h: 220 }, { fit: 'contain' });
  doc.imageBadge(116, 82, '2A');
  doc.text(116, 129, 'STOP bij twijfel: vraag senior of supervisor.', { size: 2.25, weight: 800, fill: colors.help });

  doc.card(12, 142, 86, 80);
  stepTitle(doc, 12, 142, '3', 'Voeg bewijs toe', ['Gebruik notitie of foto wanneer nodig.', 'Foto is voor zichtbare schade/afwijking.']);
  doc.clippedImage('wfForm', { x: 23, y: 169, w: 68, h: 23 }, { x: 0, y: 850, w: 780, h: 190 }, { fit: 'contain' });
  doc.imageBadge(23, 169, '3A');

  doc.card(104, 142, 94, 80);
  stepTitle(doc, 104, 142, '4', 'Controleer voortgang', ['Alle verplichte items moeten resultaat tonen.', 'Laat open items niet onverklaard.']);
  doc.placeholder(117, 170, 63, 31, 'SCREENSHOT NEEDED', 'voltooide workflow');
  doc.gaps.push('AST-02B: completed workflow summary screenshot missing');
  doc.imageBadge(117, 170, '4A');

  doc.card(12, 229, 186, 21, colors.orangeSoft, '#FDBA74');
  doc.text(18, 238, 'Gebruik deze achterkant niet als losse uitleg.', { size: 3, weight: 800, fill: colors.wf });
  doc.text(18, 244, 'Deze pagina hoort bij AST-02 en verwijst voor details naar WF-02 Complete Workflow.', { size: 2.5, fill: colors.ink });
  doc.guideChip(143, 236, 39, 'WF', 'WF-02', 'Complete');

  doc.doneBox('Alle verplichte workflow-items hebben een opgeslagen resultaat.', [
    { family: 'WF', code: 'WF-01', label: 'Start', w: 29 },
    { family: 'WF', code: 'WF-02', label: 'Complete', w: 35 },
    { family: 'HELP', code: 'HELP-01', label: 'Problemen', w: 34 },
  ]);
  return doc;
}

function renderWf01() {
  const doc = makeDoc('WF-01', 'Workflow starten', 'Start de juiste workflow vanaf de assetpagina', 'WF');
  doc.header();
  doc.context([
    { label: 'Rol', value: 'Refurbisher' },
    { label: 'Nodig', value: 'Open asset + workflow rechten' },
    { label: 'Vooraf', value: 'Asset open', guide: { family: 'AST', ref: '(AST-01)' } },
  ]);

  doc.card(12, 57, 90, 74);
  stepTitle(doc, 12, 57, '1', 'Open tab Tests', ['Gebruik het checklist/test icoon.', 'Controleer dat je op de juiste asset zit.']);
  doc.clippedImage('wfTestsWideLive', { x: 24, y: 82, w: 68, h: 24 }, { x: 55, y: 268, w: 680, h: 135 });
  doc.imageBadge(24, 82, '1A');

  doc.card(108, 57, 90, 74);
  stepTitle(doc, 108, 57, '2', 'Kies profiel', ['Gebruik het workflowprofiel dat bij dit apparaat hoort.', 'Laat het staan als supervisor dat zegt.']);
  doc.clippedImage('wfTestsWideLive', { x: 120, y: 82, w: 64, h: 26 }, { x: 50, y: 315, w: 420, h: 170 });
  doc.imageBadge(120, 82, '2A');
  doc.text(120, 113, 'Profielkeuze staat rechts op de tab.', { size: 2.25, fill: colors.ink });

  doc.card(12, 138, 90, 78);
  stepTitle(doc, 12, 138, '3', 'Start workflow', ['Tik Nieuwe workflow starten.', 'Start niet dubbel als er al een actieve run is.']);
  doc.clippedImage('wfTestsWideLive', { x: 24, y: 164, w: 68, h: 23 }, { x: 65, y: 595, w: 660, h: 100 });
  doc.imageBadge(24, 164, '3A');

  doc.card(108, 138, 90, 78);
  stepTitle(doc, 108, 138, '4', 'Controleer kaarten', ['Resultaatkaarten moeten zichtbaar zijn.', 'Daarna ga je naar WF-02.']);
  doc.clippedImage('wfForm', { x: 120, y: 164, w: 62, h: 31 }, { x: 40, y: 610, w: 700, h: 390 });
  doc.imageBadge(120, 164, '4A');

  doc.text(12, 228, 'Hulp bij workflow starten', { size: 2.6, weight: 700, fill: colors.muted });
  doc.helpTile(12, 232, 42, 'Geen knop', ['Controleer rechten', 'of status.'], 'HELP');
  doc.helpTile(59, 232, 42, 'Verkeerd profiel', ['Stop en vraag', 'supervisor.'], 'HELP');
  doc.helpTile(106, 232, 42, 'Al gestart', ['Ga door met', 'open workflow.'], 'HELP');
  doc.helpTile(153, 232, 42, 'Geen items', ['Gebruik HELP-01', 'of vraag hulp.'], 'HELP');

  doc.doneBox('De gekozen workflow staat open als resultaatkaarten.', [
    { family: 'AST', code: 'AST-01', label: 'Open Asset', w: 36 },
    { family: 'WF', code: 'WF-02', label: 'Complete', w: 35 },
    { family: 'HELP', code: 'HELP-01', label: 'Problemen', w: 34 },
  ]);
  return doc;
}

function renderWf02() {
  const doc = makeDoc('WF-02', 'Workflow uitvoeren', 'Registreer resultaten, notities en foto-bewijs waar nodig', 'WF');
  doc.header();
  doc.context([
    { label: 'Rol', value: 'Refurbisher' },
    { label: 'Nodig', value: 'Actieve workflow + apparaat in hand' },
    { label: 'Vooraf', value: 'Workflow gestart', guide: { family: 'WF', ref: '(WF-01)' } },
  ]);

  doc.card(12, 57, 186, 50);
  stepTitle(doc, 12, 57, '1', 'Controleer het item', ['Lees de kaart en eventuele instructies.', 'Controleer het echte apparaat, niet alleen het scherm.']);
  doc.clippedImage('wfForm', { x: 96, y: 70, w: 82, h: 27 }, { x: 40, y: 610, w: 700, h: 230 });
  doc.imageBadge(96, 70, '1A');

  doc.card(12, 113, 88, 73);
  stepTitle(doc, 12, 113, '2', 'Kies resultaat', ['Tik Geslaagd als het klopt.', 'Tik Mislukt als het niet klopt.']);
  doc.clippedImage('wfForm', { x: 23, y: 140, w: 68, h: 22 }, { x: 40, y: 720, w: 700, h: 180 }, { fit: 'contain' });
  doc.imageBadge(23, 140, '2A');

  doc.card(106, 113, 92, 73);
  stepTitle(doc, 106, 113, '3', 'Notitie of foto', ['Gebruik bewijs bij schade, afwijking of mislukking.', 'Laat lege reden niet onduidelijk.']);
  doc.clippedImage('wfForm', { x: 118, y: 140, w: 68, h: 22 }, { x: 0, y: 850, w: 780, h: 190 }, { fit: 'contain' });
  doc.imageBadge(118, 140, '3A');

  doc.card(12, 194, 186, 43);
  stepTitle(doc, 12, 194, '4', 'Rond af of vraag hulp', ['Alle verplichte items moeten een opgeslagen resultaat hebben.']);
  doc.text(26, 211, ['STOP bij twijfel, verkeerde asset of fysiek defect.', 'Vraag hulp bij ontbrekende rechten of onduidelijke instructie.'], { size: 2.15, weight: 800, fill: colors.help, lh: 3.1 });
  doc.placeholder(112, 204, 62, 22, 'SCREENSHOT NEEDED', 'opgeslagen resultaat');
  doc.gaps.push('WF-02: final saved/completed workflow state screenshot missing');
  doc.imageBadge(112, 204, '4A');

  doc.text(12, 245, 'Hulp bij uitvoeren', { size: 2.6, weight: 700, fill: colors.muted });
  doc.helpTile(12, 249, 42, 'Onzeker', ['Stop en vraag', 'senior.'], 'HELP');
  doc.helpTile(59, 249, 42, 'Foto nodig', ['Maak foto bij', 'schade.'], 'HELP');
  doc.helpTile(106, 249, 42, 'Geen rechten', ['Niet omzeilen.', 'Vraag rol.'], 'HELP');
  doc.helpTile(153, 249, 42, 'Geen items', ['Gebruik HELP-01', 'of supervisor.'], 'HELP');

  doc.doneBox('Alle verplichte workflow-items hebben een opgeslagen resultaat.', [
    { family: 'WF', code: 'WF-01', label: 'Start', w: 29 },
    { family: 'AST', code: 'AST-02', label: 'Route', w: 31 },
    { family: 'HELP', code: 'HELP-01', label: 'Problemen', w: 34 },
  ]);
  return doc;
}

function renderCmp04() {
  const doc = makeDoc('CMP-04', 'Component naar tray', 'Verwijder een component veilig naar tray en leg serial vast', 'CMP');
  doc.header();
  doc.context([
    { label: 'Rol', value: 'Refurbisher' },
    { label: 'Nodig', value: 'Open asset + component in hand' },
    { label: 'Vooraf', value: 'Asset open', guide: { family: 'AST', ref: '(AST-01)' } },
  ]);

  doc.card(12, 57, 186, 42);
  stepTitle(doc, 12, 57, '1', 'Open componenten', ['Ga naar de component-tab van de juiste asset.']);
  doc.clippedImage('cmpWideLive', { x: 94, y: 68, w: 82, h: 22 }, { x: 55, y: 280, w: 690, h: 260 });
  doc.imageBadge(94, 68, '1A');

  doc.card(12, 106, 88, 69);
  stepTitle(doc, 12, 106, '2', 'Kies component', ['Vergelijk naam, categorie en serial.', 'Selecteer alleen het onderdeel dat fysiek weggaat.']);
  doc.clippedImage('cmpWideLive', { x: 22, y: 132, w: 66, h: 31 }, { x: 80, y: 420, w: 650, h: 230 });
  doc.imageBadge(22, 132, '2A');

  doc.card(106, 106, 92, 69);
  stepTitle(doc, 106, 106, '3', 'Tik Naar tray', ['Gebruik de oranje knop bij dat component.', 'Niet bij een vergelijkbare kaart.']);
  doc.clippedImage('cmpWideLive', { x: 119, y: 132, w: 64, h: 31 }, { x: 80, y: 560, w: 650, h: 155 });
  doc.imageBadge(119, 132, '3A');

  doc.card(12, 183, 88, 53);
  stepTitle(doc, 12, 183, '4', 'Controleer serial', ['Modal moet juiste onderdeel tonen.', 'Serial aanpassen alleen als nodig.']);
  doc.clippedImage('cmpModalLocked', { x: 25, y: 207, w: 60, h: 18 }, { x: 20, y: 150, w: 350, h: 160 });
  doc.imageBadge(25, 207, '4A');

  doc.card(106, 183, 92, 53);
  stepTitle(doc, 106, 183, '5', 'Bevestig tray', ['Controleer doel: tray/opslag.', 'Daarna bevestigen.']);
  doc.text(120, 209, 'STOP als component, serial of doel niet klopt.', { size: 2.25, weight: 800, fill: colors.help });
  doc.clippedImage('cmpModalUnlocked', { x: 120, y: 216, w: 60, h: 13 }, { x: 90, y: 485, w: 280, h: 70 });
  doc.gaps.push('CMP-04: post-confirm tray/storage result screenshot missing because the modal was not submitted');
  doc.imageBadge(120, 216, '5A');

  doc.text(12, 245, 'Hulp bij componenten', { size: 2.6, weight: 700, fill: colors.muted });
  doc.helpTile(12, 249, 42, 'Serial anders', ['Niet gokken.', 'Vraag hulp.'], 'HELP');
  doc.helpTile(59, 249, 42, 'Verkeerd onderdeel', ['Stop voor', 'bevestigen.'], 'HELP');
  doc.helpTile(106, 249, 42, 'Geen knop', ['Controleer rechten', 'of status.'], 'HELP');
  doc.helpTile(153, 249, 42, 'Niet naar tray', ['Gebruik juiste', 'component gids.'], 'HELP');

  doc.doneBox('Component staat in tray/opslag en serialbesluit is vastgelegd.', [
    { family: 'AST', code: 'AST-01', label: 'Open', w: 28 },
    { family: 'AST', code: 'AST-02', label: 'Route', w: 31 },
    { family: 'HELP', code: 'HELP-01', label: 'Problemen', w: 34 },
  ]);
  return doc;
}

function problemTile(doc, x, y, w, h, title, body, refs = []) {
  doc.rect(x, y, w, h, '#FFFFFF', colors.line, 0.35, 1.6);
  doc.text(x + 5, y + 8, title, { size: 3.2, weight: 800, fill: colors.help });
  doc.text(x + 5, y + 15, body, { size: 2.45, fill: colors.ink, lh: 3.35 });
  let cx = x + 5;
  refs.forEach((ref) => {
    doc.guideChip(cx, y + h - 9, ref.w, ref.family, ref.code, ref.label);
    cx += ref.w + 3;
  });
}

function renderHelp01() {
  const doc = makeDoc('HELP-01', 'Veel voorkomende problemen', 'Stop, herstel of vraag hulp zonder gegevens verkeerd te wijzigen', 'HELP');
  doc.header();
  doc.context([
    { label: 'Rol', value: 'Iedereen' },
    { label: 'Nodig', value: 'Onzekerheid of blokkade tijdens gids' },
    { label: 'Vooraf', value: 'Gebruik relevante gids', size: 2.45 },
  ]);

  problemTile(doc, 12, 58, 59, 40, 'Geen account', ['Vraag beheerder of supervisor.', 'Maak geen gedeeld wachtwoord.'], [
    { family: 'AC', code: 'AC-01', label: 'Login', w: 30 },
  ]);
  problemTile(doc, 76, 58, 59, 40, 'Wachtwoord kwijt', ['Gebruik wachtwoord vergeten of', 'vraag supervisor om herstel.'], [
    { family: 'AC', code: 'AC-01', label: 'Login', w: 30 },
  ]);
  problemTile(doc, 140, 58, 58, 40, 'Geen telefoon', ['Gebruik laptop met camera', 'of handmatige zoekbalk.'], [
    { family: 'SC', code: 'SC-01', label: 'Scan', w: 29 },
  ]);

  problemTile(doc, 12, 105, 59, 43, 'Camera opent niet', ['Sta cameratoegang toe.', 'Refresh of gebruik zoeken.'], [
    { family: 'SC', code: 'SC-01', label: 'Scan', w: 29 },
  ]);
  problemTile(doc, 76, 105, 59, 43, 'QR beschadigd', ['Typ asset tag of QR-code.', 'Controleer label later.'], [
    { family: 'SC', code: 'SC-01', label: 'Scan', w: 29 },
    { family: 'AST', code: 'AST-01', label: 'Open', w: 28 },
  ]);
  problemTile(doc, 140, 105, 58, 43, 'QR opent verkeerd', ['STOP. Wijzig niets.', 'Vraag hulp met het apparaat.'], [
    { family: 'AST', code: 'AST-01', label: 'Open', w: 28 },
  ]);

  problemTile(doc, 12, 155, 59, 43, 'Geen workflow', ['Controleer status en model.', 'Vraag senior/supervisor.'], [
    { family: 'WF', code: 'WF-01', label: 'Start', w: 29 },
  ]);
  problemTile(doc, 76, 155, 59, 43, 'Geen rechten', ['Niet omzeilen.', 'Vraag juiste rol.'], [
    { family: 'AC', code: 'AC-01', label: 'Login', w: 30 },
  ]);
  problemTile(doc, 140, 155, 58, 43, 'Printer/label faalt', ['Gebruik download/fallback.', 'Meld printerprobleem.'], [
    { family: 'AST', code: 'AST-03', label: 'Sticker', w: 34 },
  ]);

  doc.card(12, 209, 186, 36, colors.redSoft, '#FDA4AF');
  doc.text(18, 220, 'Algemene stopregel', { size: 4.0, weight: 800, fill: colors.help });
  doc.text(18, 228, ['Stop voordat je opslaat wanneer asset, component, serial, workflow of fysiek apparaat niet overeenkomt.', 'Vraag hulp en laat de pagina open als bewijs.'], { size: 2.65, fill: colors.ink, lh: 4 });

  doc.doneBox('Je weet welke gids of persoon nodig is voordat je verdergaat.', [
    { family: 'AC', code: 'AC-01', label: 'Login', w: 30 },
    { family: 'SC', code: 'SC-01', label: 'Scan', w: 29 },
    { family: 'AST', code: 'AST-01', label: 'Open', w: 28 },
    { family: 'WF', code: 'WF-02', label: 'Workflow', w: 34 },
  ]);
  return doc;
}

const pages = [
  ['AC-01-login-draft', renderAc01()],
  ['SC-01-scan-asset-draft', renderSc01()],
  ['AST-01-open-existing-asset-draft', renderAst01()],
  ['AST-02-existing-asset-refurbishment-front-draft', renderAst02Front()],
  ['AST-02-existing-asset-refurbishment-back-draft', renderAst02Back()],
  ['WF-01-start-workflow-draft', renderWf01()],
  ['WF-02-complete-workflow-draft', renderWf02()],
  ['CMP-04-remove-component-to-tray-draft', renderCmp04()],
  ['HELP-01-common-problems-draft', renderHelp01()],
];

function htmlForSvg(title, svg) {
  return `<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>${xml(title)}</title>
<style>
  @page { size: A4 portrait; margin: 0; }
  html, body { margin: 0; padding: 0; background: #2f3338; }
  body { display: flex; justify-content: center; align-items: flex-start; }
  svg { width: 210mm; height: 297mm; background: white; }
</style>
</head>
<body>${svg}</body>
</html>`;
}

function combinedHtml(pageSvgs) {
  return `<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>First batch operator guides</title>
<style>
  @page { size: A4 portrait; margin: 0; }
  html, body { margin: 0; padding: 0; background: #2f3338; }
  .page { width: 210mm; height: 297mm; page-break-after: always; break-after: page; background: white; }
  .page:last-child { page-break-after: auto; break-after: auto; }
  svg { width: 210mm; height: 297mm; display: block; }
</style>
</head>
<body>
${pageSvgs.map((svg) => `<div class="page">${svg}</div>`).join('\n')}
</body>
</html>`;
}

function contactHtml(items) {
  return `<!doctype html>
<html>
<head>
<meta charset="utf-8">
<style>
  html, body { margin: 0; padding: 0; background: #e5e7eb; font-family: Arial, sans-serif; }
  .grid { display: grid; grid-template-columns: repeat(3, 380px); gap: 18px; padding: 24px; }
  .item { background: white; border: 1px solid #cbd5e1; padding: 10px; }
  .label { font-weight: 700; font-size: 18px; margin-bottom: 8px; color: #102033; }
  svg { width: 360px; height: auto; display: block; border: 1px solid #e2e8f0; }
</style>
</head>
<body><div class="grid">
${items.map(([name, svg]) => `<div class="item"><div class="label">${xml(name)}</div>${svg}</div>`).join('\n')}
</div></body>
</html>`;
}

fs.mkdirSync(outDir, { recursive: true });

const outputs = [];
const pageSvgs = [];
const gaps = [];

for (const [name, doc] of pages) {
  const svg = doc.toSvg();
  const html = htmlForSvg(name, svg);
  const base = path.join(outDir, name);
  fs.writeFileSync(`${base}.svg`, svg, 'utf8');
  fs.writeFileSync(`${base}.html`, html, 'utf8');
  pageSvgs.push(svg);
  gaps.push(...doc.gaps);
  outputs.push({
    name,
    svg: `${base}.svg`,
    html: `${base}.html`,
    png: `${base}-proof.png`,
    pdf: `${base}-proof.pdf`,
  });
}

const combined = combinedHtml(pageSvgs);
const contact = contactHtml(outputs.map((item, index) => [item.name, pageSvgs[index]]));
const combinedHtmlPath = path.join(outDir, 'first-batch-operator-guides.html');
const combinedPdfPath = path.join(outDir, 'first-batch-operator-guides-proof.pdf');
const contactHtmlPath = path.join(outDir, 'first-batch-contact-sheet.html');
const contactPngPath = path.join(outDir, 'first-batch-contact-sheet.png');
fs.writeFileSync(combinedHtmlPath, combined, 'utf8');
fs.writeFileSync(contactHtmlPath, contact, 'utf8');

const browser = await chromium.launch({ headless: true });
try {
  for (const item of outputs) {
    const page = await browser.newPage({ viewport: { width: 1240, height: 1754 }, deviceScaleFactor: 2 });
    await page.goto(`file:///${item.html.replace(/\\/g, '/')}`, { waitUntil: 'load' });
    await page.locator('svg').screenshot({ path: item.png });
    await page.pdf({
      path: item.pdf,
      width: '210mm',
      height: '297mm',
      printBackground: true,
      margin: { top: '0mm', right: '0mm', bottom: '0mm', left: '0mm' },
    });
    await page.close();
  }

  const combinedPage = await browser.newPage({ viewport: { width: 1240, height: 1754 }, deviceScaleFactor: 2 });
  await combinedPage.goto(`file:///${combinedHtmlPath.replace(/\\/g, '/')}`, { waitUntil: 'load' });
  await combinedPage.pdf({
    path: combinedPdfPath,
    width: '210mm',
    height: '297mm',
    printBackground: true,
    margin: { top: '0mm', right: '0mm', bottom: '0mm', left: '0mm' },
  });
  await combinedPage.close();

  const contactPage = await browser.newPage({ viewport: { width: 1200, height: 3900 }, deviceScaleFactor: 2 });
  await contactPage.goto(`file:///${contactHtmlPath.replace(/\\/g, '/')}`, { waitUntil: 'load' });
  await contactPage.screenshot({ path: contactPngPath, fullPage: true });
  await contactPage.close();
} finally {
  await browser.close();
}

const summaryPath = path.join(outDir, 'first-batch-summary.md');
const summary = [
  '# First Batch Operator Guide Drafts',
  '',
  `Generated: ${today}`,
  '',
  '## Outputs',
  '',
  ...outputs.map((item) => `- ${item.name}: PDF ${item.pdf}`),
  '',
  `- Combined PDF: ${combinedPdfPath}`,
  `- Contact sheet: ${contactPngPath}`,
  '',
  '## Known Capture Gaps',
  '',
  ...(gaps.length ? [...new Set(gaps)].map((gap) => `- ${gap}`) : ['- None detected by generator.']),
  '',
  '## Source Notes',
  '',
  '- Real screenshots are used where available from the 2026-06-25, 2026-06-30, and 2026-07-02 capture sets.',
  '- Placeholder frames mark source screenshots that the planning docs already identified as missing.',
  '- These are draft proof artifacts for review/import, not final laminated production files.',
  '',
].join('\n');
fs.writeFileSync(summaryPath, summary, 'utf8');

console.log(JSON.stringify({
  outDir,
  combinedPdf: combinedPdfPath,
  contactSheet: contactPngPath,
  summary: summaryPath,
  pages: outputs,
  gaps: [...new Set(gaps)],
}, null, 2));
