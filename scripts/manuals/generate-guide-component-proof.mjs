import fs from 'node:fs';
import path from 'node:path';
import { pathToFileURL } from 'node:url';
import {
    GUIDE_FAMILIES,
    GUIDE_TOKENS,
    SvgGuideDocument,
    drawCompletionRow,
    drawContextStrip,
    drawRelatedGuideRows,
    guideReference,
    inspectRenderedGuideComponents,
} from './lib/guide-system.mjs';
import {
    browserLaunchOptions,
    guideOutputDir,
    loadGuideDependency,
    repoPdfOutputRoot,
} from './lib/guide-paths.mjs';

const { chromium } = loadGuideDependency('playwright');

const generatedOn = process.env.SNIPEIT_GUIDE_DATE ?? '2026-08-13';
const outDir = process.env.SNIPEIT_GUIDE_OUT_DIR
    ?? guideOutputDir('component-system-v1');
const repoPdf = path.join(repoPdfOutputRoot, 'operator-guide-component-system-v1.pdf');

const colors = GUIDE_TOKENS.colors;
const doc = new SvgGuideDocument({}, 'component-proof');

doc.rect(12, 13, 2, 16, colors.usr);
doc.text(18, 22, 'Operator guide component system', { size: 7, weight: 900 });
doc.text(18, 29, 'Shared alignment, references, focus marks and review primitives', { size: 3, fill: colors.muted });
doc.rect(164, 12, 34, 10, colors.greenSoft, '#86EFAC', 0.45, 2);
doc.centeredText(181, 17, `Proof v1 ${generatedOn}`, { size: 2.1, weight: 800, fill: colors.green });

drawContextStrip(doc, [
    { label: 'Rol', value: 'Admin / Superadmin' },
    { label: 'Nodig', value: 'Goedgekeurde taakgegevens' },
    { label: 'Vooraf', value: 'Ingelogd (AC-01 Login)', guide: guideReference('AC-01') },
]);

doc.text(12, 66, '1. Family markers', { size: 4.2, weight: 900 });
doc.text(12, 72, 'Every marker uses the same center coordinate for its circle and text.', { size: 2.4, fill: colors.muted });
Object.entries(GUIDE_FAMILIES).forEach(([family, palette], index) => {
    const x = 19 + index * 27;
    doc.familyBadge(x, 82, family, { radius: 4.2, fontSize: family.length > 3 ? 1.8 : 2.25, fill: palette.fill });
    doc.centeredText(x, 90, family, { size: 2.1, weight: 800, fill: palette.color });
});

doc.text(12, 104, '2. Step and visual hierarchy', { size: 4.2, weight: 900 });
doc.rect(12, 116, 186, 42, colors.white, colors.line, 0.45, 1.8);
doc.stepBadge(12, 116, '1', colors.usr);
doc.text(23, 124, 'Actual workflow step', { size: 3.85, weight: 900 });
doc.text(23, 131, ['Large badge overlaps the step corner.', 'Image labels stay smaller and more subtle.'], { size: 2.35, fill: colors.muted, lh: 3.2 });
doc.rect(106, 122, 77, 27, colors.faint, colors.line, 0.4, 1.2);
doc.imageBadge(106, 122, '1A', colors.usr);
doc.centeredText(144.5, 134, 'Recognizable screenshot context', { size: 2.4, weight: 800, fill: colors.muted });
doc.text(107, 154, '1A Context - short action or recognition caption.', { size: 2.05, weight: 700, fill: colors.muted });

doc.text(12, 168, '3. Focus alignment', { size: 4.2, weight: 900 });
doc.text(12, 174, 'Targets derive from source bounds plus symmetric padding; callouts remain optional.', { size: 2.4, fill: colors.muted });
doc.rect(12, 180, 186, 36, colors.faint, colors.line, 0.45, 1.8);
doc.rect(41, 190, 50, 14, colors.white, colors.line, 0.4, 1.2);
doc.centeredText(66, 197, 'Voorbeeldknop', { size: 2.6, weight: 800 });
doc.focusMark({ x: 0, y: 0, scale: 1 }, { x: 41, y: 190, w: 50, h: 14, padding: 2, target: 'Voorbeeldknop' });
doc.text(108, 191, 'Target bounds', { size: 2.2, weight: 900 });
doc.text(108, 197, ['x/y/w/h describe the actual control.', 'Padding expands every side equally.'], { size: 2.25, fill: colors.muted, lh: 3.1 });

doc.text(12, 226, '4. Completion and related guides', { size: 4.2, weight: 900 });
doc.text(12, 232, 'Related guides use full registered names and zero to five slots.', { size: 2.4, fill: colors.muted });
drawCompletionRow(doc, 'The visible result matches the task and no unresolved stop remains.');
drawRelatedGuideRows(doc, [
    guideReference('AC-02', { width: 57 }),
    guideReference('USR-02', { width: 59 }),
    guideReference('USR-03', { width: 47, row: 2 }),
    guideReference('USR-04', { width: 66, row: 2 }),
    guideReference('USR-05', { width: 42, row: 2 }),
]);
doc.qrPlaceholder(176, 263, 22);
doc.centeredText(187, 289, 'Digitale gids', { size: 2.15, weight: 800 });
doc.text(12, 294, `Component proof | Internal review infrastructure | ${generatedOn}`, { size: 1.75, fill: colors.muted });

const svg = doc.render();
const html = `<!doctype html><html><head><meta charset="utf-8"><style>@page{size:A4;margin:0}html,body{margin:0;padding:0;background:#dfe3ea}.page{width:210mm;height:297mm;overflow:hidden;background:#fff}.page>svg{display:block;width:210mm;height:297mm}</style></head><body><div class="page">${svg}</div></body></html>`;

fs.mkdirSync(outDir, { recursive: true });
fs.mkdirSync(path.dirname(repoPdf), { recursive: true });
const svgPath = path.join(outDir, 'operator-guide-component-system-v1.svg');
const htmlPath = path.join(outDir, 'operator-guide-component-system-v1.html');
const pdfPath = path.join(outDir, 'operator-guide-component-system-v1.pdf');
const pngPath = path.join(outDir, 'operator-guide-component-system-v1.png');
fs.writeFileSync(svgPath, svg, 'utf8');
fs.writeFileSync(htmlPath, html, 'utf8');

const browser = await chromium.launch(browserLaunchOptions({ headless: true }));
const page = await browser.newPage({ viewport: { width: 1240, height: 1754 }, deviceScaleFactor: 2 });
await page.goto(pathToFileURL(htmlPath).href, { waitUntil: 'load' });
const geometry = await inspectRenderedGuideComponents(page);
await page.locator('.page').screenshot({ path: pngPath });
await page.pdf({
    path: pdfPath,
    width: '210mm',
    height: '297mm',
    printBackground: true,
    preferCSSPageSize: true,
    margin: { top: '0', right: '0', bottom: '0', left: '0' },
});
await browser.close();
fs.copyFileSync(pdfPath, repoPdf);
fs.writeFileSync(path.join(outDir, 'component-qa.json'), `${JSON.stringify({ generatedOn, geometry, outputs: { svgPath, htmlPath, pdfPath, pngPath, repoPdf } }, null, 2)}\n`, 'utf8');

console.log(JSON.stringify({ outDir, repoPdf, pngPath, geometry }, null, 2));
