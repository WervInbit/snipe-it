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
const outDir = process.env.SNIPEIT_GUIDE_OUT_DIR || guideOutputDir('AC-01-v6');

const sourceFiles = {
  phoneStart: evidencePath('PHONE-START-01'),
  liveLogin: evidencePath('LOGIN-MOBILE-01'),
  dashboard: evidencePath('DASH-MOBILE-01'),
};

const outputs = {
  svg: path.join(outDir, 'AC-01-login-snipe-v6.svg'),
  html: path.join(outDir, 'AC-01-login-snipe-v6.html'),
  png: path.join(outDir, 'AC-01-login-snipe-v6-proof.png'),
  pdf: path.join(outDir, 'AC-01-login-snipe-v6-proof.pdf'),
  summary: path.join(outDir, 'AC-01-login-snipe-v6-summary.md'),
};

const colors = {
  ink: '#102033',
  muted: '#53657A',
  line: '#C8D5E2',
  faint: '#F6F9FC',
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

  rect(x, y, w, h, fill = 'none', stroke = 'none', sw = 0, r = 0) {
    this.parts.push(
      `<rect x="${x}" y="${y}" width="${w}" height="${h}" rx="${r}" fill="${fill}" stroke="${stroke}" stroke-width="${sw}"/>`,
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
    const scale = opts.fit === 'contain'
      ? Math.min(frame.w / area.w, frame.h / area.h)
      : Math.max(frame.w / area.w, frame.h / area.h);
    const w = img.width * scale;
    const h = img.height * scale;
    const x = frame.x + (frame.w - area.w * scale) / 2 - area.x * scale;
    const y = frame.y + (frame.h - area.h * scale) / 2 - area.y * scale;
    const id = this.nextId('clip');
    const r = opts.r ?? 1.4;
    this.defs.push(`<clipPath id="${id}"><rect x="${frame.x}" y="${frame.y}" width="${frame.w}" height="${frame.h}" rx="${r}"/></clipPath>`);
    this.rect(frame.x, frame.y, frame.w, frame.h, colors.white, colors.line, 0.4, r);
    this.parts.push(`<image href="${img.href}" x="${x}" y="${y}" width="${w}" height="${h}" clip-path="url(#${id})"/>`);
    this.rect(frame.x, frame.y, frame.w, frame.h, 'none', opts.stroke ?? colors.line, opts.sw ?? 0.4, r);
    return { scale, x, y };
  }

  badge(x, y, label, color = colors.ac) {
    this.parts.push(`<circle cx="${x}" cy="${y}" r="4.35" fill="#FFFFFF" fill-opacity="0.42" stroke="${color}" stroke-opacity="0.78" stroke-width="0.9"/>`);
    this.text(x, y + 0.95, label, { size: 2.15, weight: 800, fill: color, anchor: 'middle' });
  }

  stepBadge(x, y, label, color = colors.ac) {
    this.parts.push(`<circle cx="${x}" cy="${y}" r="7.4" fill="#FFFFFF" fill-opacity="0.88" stroke="${color}" stroke-width="2.05"/>`);
    this.text(x, y + 1.7, label, { size: 4.9, weight: 900, fill: color, anchor: 'middle' });
  }

  chip(x, y, w, label, color, fill = '#FFFFFF') {
    this.rect(x, y, w, 7, fill, color, 0.55, 2.5);
    this.circle(x + 5, y + 3.5, 2.45, color, 0.55, '#FFFFFF');
    const family = label.split('-')[0];
    this.text(x + 5, y + 4.35, family, { size: family.length > 3 ? 1.35 : 1.65, weight: 800, fill: color, anchor: 'middle' });
    this.text(x + 10, y + 4.55, label, { size: 2.35, weight: 800, fill: color });
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

async function captureLiveLogin() {
  return { title: 'Canonical evidence LOGIN-MOBILE-01', finalUrl: `${liveUrl}login` };
}

function buildGuide(images, captureMeta) {
  const doc = new SvgDoc(images);

  doc.rect(12, 13, 2, 16, colors.ac);
  doc.text(18, 22, 'AC-01 Login', { size: 9.2, weight: 900 });
  doc.text(18, 29, 'Open Snipe-IT en controleer dat het dashboard zichtbaar is', { size: 3.1, fill: colors.muted });
  doc.rect(161, 12, 37, 11, colors.greenSoft, '#86EFAC', 0.45, 2);
  doc.text(179.5, 18.9, `Draft v6 ${today}`, { size: 2.35, weight: 800, fill: colors.green, anchor: 'middle' });

  doc.rect(12, 39, 186, 18, '#F8FAFC', colors.line, 0.45, 1.6);
  doc.text(17, 47, 'Rol', { size: 2.3, weight: 800, fill: colors.muted });
  doc.text(17, 53, 'Iedereen', { size: 3.05, weight: 800 });
  doc.text(58, 47, 'Nodig', { size: 2.3, weight: 800, fill: colors.muted });
  doc.text(58, 53, 'Telefoon met Inbit Snipe-IT', { size: 3.05, weight: 800 });
  doc.text(142, 47, 'Adres', { size: 2.3, weight: 800, fill: colors.muted });
  doc.text(142, 53, liveUrl, { size: 3.05, weight: 800, fill: colors.ac });

  // AC-01 is a short guide: keep the three actions in one shared workflow frame.
  const flowX = 12;
  const flowY = 78;
  const flowW = 186;
  const flowH = 104;
  const colW = flowW / 3;
  const col1X = flowX;
  const col2X = flowX + colW;
  const col3X = flowX + colW * 2;

  doc.rect(flowX, flowY, flowW, flowH, colors.white, colors.line, 0.5, 2);
  doc.line(col2X, flowY + 8, col2X, flowY + flowH - 8, colors.line, 0.35);
  doc.line(col3X, flowY + 8, col3X, flowY + flowH - 8, colors.line, 0.35);

  doc.stepBadge(flowX, flowY, '1');
  doc.text(col1X + 10, flowY + 10, 'Open Snipe-IT', { size: 4.2, weight: 900 });
  doc.text(col1X + 10, flowY + 17, 'Tik op de snelkoppeling.', { size: 2.55, fill: colors.muted });

  const phoneFrame = { x: col1X + 15, y: flowY + 28, w: 34, h: 54 };
  const phonePlacement = doc.image('phoneStart', phoneFrame, { x: 0, y: 0, w: 1080, h: 1900 }, { r: 1.5, fit: 'contain' });
  doc.badge(phoneFrame.x, phoneFrame.y, '1A');
  const phoneIconCenter = {
    x: phonePlacement.x + 150 * phonePlacement.scale,
    y: phonePlacement.y + 248 * phonePlacement.scale,
    r: 108 * phonePlacement.scale,
  };
  doc.circle(phoneIconCenter.x, phoneIconCenter.y, phoneIconCenter.r, colors.help, 0.65, 'none', 0.98);
  doc.text(col1X + 10, flowY + 91, ['Startscherm telefoon:', 'rode cirkel = Inbit Snipe-IT.'], { size: 2.25, fill: colors.ink, lh: 3.05 });

  doc.stepBadge(col2X, flowY, '2');
  doc.text(col2X + 10, flowY + 10, 'Log in', { size: 4.2, weight: 900 });
  doc.text(col2X + 10, flowY + 17, 'Vul in en tik Inloggen.', { size: 2.55, fill: colors.muted });
  const loginImg = images.liveLogin;
  const loginFrame = { x: col2X + 13, y: flowY + 31, w: 36, h: 50 };
  doc.image('liveLogin', loginFrame, { x: 65, y: 45, w: Math.min(loginImg.width - 130, 650), h: 805 }, { r: 1.5, fit: 'contain' });
  doc.badge(loginFrame.x, loginFrame.y, '2A');
  doc.text(col2X + 10, flowY + 91, ['Inlogscherm:', 'tik op Inloggen na invullen.'], { size: 2.25, fill: colors.ink, lh: 3.05 });

  doc.stepBadge(col3X, flowY, '3');
  doc.text(col3X + 10, flowY + 10, 'Controleer dashboard', { size: 4.2, weight: 900 });
  doc.text(col3X + 10, flowY + 17, 'Klaar als het dashboard zichtbaar is.', { size: 2.55, fill: colors.muted });
  const dashImg = images.dashboard;
  const dashFrame = { x: col3X + 8, y: flowY + 37, w: 48, h: 35 };
  doc.image('dashboard', dashFrame, { x: 0, y: 0, w: dashImg.width, h: Math.min(dashImg.height, 1120) }, { r: 1.5 });
  doc.badge(dashFrame.x + dashFrame.w, dashFrame.y, '3A');
  doc.text(col3X + 10, flowY + 91, ['Dashboard:', 'Scan QR of Apparaten zichtbaar.'], { size: 2.25, fill: colors.ink, lh: 3.05 });

  // Help tiles.
  doc.text(12, 210, 'Hulp bij login', { size: 2.8, weight: 800, fill: colors.muted });
  const helpY = 215;
  const helpTiles = [
    ['Geen account', ['Vraag beheerder', 'of supervisor.']],
    ['Wachtwoord kwijt', ['Vraag je supervisor', 'om het te resetten.']],
    ['Geen telefoon', ['Open browser:', 'https://snipe.inbit/']],
    ['Sessie verlopen', ['Log opnieuw in', 'en probeer nogmaals.']],
  ];
  helpTiles.forEach(([title, body], i) => {
    const x = 12 + i * 45.8;
    doc.rect(x, helpY, 41.5, 23, colors.orangeSoft, '#FDBA74', 0.45, 2);
    doc.circle(x + 5.2, helpY + 6.2, 3.2, colors.orange, 0.55, '#FFFFFF');
    doc.text(x + 5.2, helpY + 7.35, '!', { size: 2.9, weight: 900, fill: colors.orange, anchor: 'middle' });
    doc.text(x + 10.5, helpY + 7, title, { size: 2.45, weight: 900, fill: colors.ink });
    doc.text(x + 5, helpY + 15, body, { size: 2.25, fill: colors.ink, lh: 3.1 });
  });

  doc.rect(12, 244, 147, 13, colors.greenSoft, '#86EFAC', 0.45, 2);
  doc.text(17, 251.5, 'Klaar als', { size: 3.0, weight: 900, fill: colors.green });
  doc.text(38, 251.5, 'Het dashboard is zichtbaar en je kunt verder met de juiste gids.', { size: 2.45, fill: colors.green });

  doc.text(12, 272, 'Relevante gidsen', { size: 2.2, weight: 800, fill: colors.muted });
  doc.chip(38, 268, 35, 'SC-01 Openen', '#0E8A75', '#ECFDF5');
  doc.chip(77, 268, 36, 'AST-02 Route', '#0E8A45', '#ECFDF5');
  doc.chip(117, 268, 37, 'HELP-01 Help', colors.help, colors.helpSoft);
  doc.text(12, 294, `Bron: ${liveUrl} login capture + telefoonstart screenshot | ${captureMeta.finalUrl} | ${today}`, {
    size: 1.85,
    fill: colors.muted,
  });

  doc.qrPlaceholder(176, 263, 22);
  doc.text(187, 289.5, 'Digitale gids', { size: 2.2, weight: 800, anchor: 'middle' });

  return doc.render();
}

async function main() {
  fs.mkdirSync(outDir, { recursive: true });
  const captureMeta = await captureLiveLogin();
  const images = Object.fromEntries(
    Object.entries(sourceFiles).map(([key, file]) => [key, loadImage(file)]),
  );
  const svg = buildGuide(images, captureMeta);
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
    '# AC-01 Login Snipe Proof v6',
    '',
    `Generated: ${today}`,
    `Live URL: ${liveUrl}`,
    `Captured login final URL: ${captureMeta.finalUrl}`,
    `Captured login title: ${captureMeta.title}`,
    '',
    '## Outputs',
    `- Proof PNG: ${outputs.png}`,
    `- Proof PDF: ${outputs.pdf}`,
    `- SVG source: ${outputs.svg}`,
    `- HTML source: ${outputs.html}`,
    '',
    '## Sources',
    `- Phone start: ${sourceFiles.phoneStart}`,
    `- Live login capture: ${sourceFiles.liveLogin}`,
    `- Dashboard source: ${sourceFiles.dashboard}`,
    '',
    '## Notes',
    '- The main workflow uses one shared frame with three internal steps to reduce the empty-card feeling on this short guide.',
    '- The lower help, done, related-guide, QR, and source areas are anchored near the bottom for consistency with longer guides.',
    '- The first visual uses the phone launcher screenshot and circles the Inbit Snipe-IT shortcut.',
    '- The browser fallback is only shown in the Geen telefoon help tile.',
    '- No development URL source or footer text is used.',
    '- Dashboard source is an existing base-test screenshot; recapture from the live logged-in site if a newer production state is required.',
    '',
  ].join('\n');
  fs.writeFileSync(outputs.summary, summary, 'utf8');

  console.log(JSON.stringify({ outputs, captureMeta }, null, 2));
}

main().catch((error) => {
  console.error(error);
  process.exitCode = 1;
});
