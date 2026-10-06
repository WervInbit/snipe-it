import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import { pathToFileURL } from 'node:url';
import { spawnSync } from 'node:child_process';
import {
    loadGuideDependency,
    repoRoot,
    resolveChromeExecutable,
    resolveCommand,
} from './lib/guide-paths.mjs';

function option(name, required = true) {
    const index = process.argv.indexOf(name);
    if (index >= 0 && process.argv[index + 1]) return process.argv[index + 1];
    if (!required) return null;
    throw new Error(`Missing ${name}`);
}

const htmlPath = path.resolve(repoRoot, option('--html'));
const pdfPath = path.resolve(repoRoot, option('--pdf'));
const expectedPages = Number(option('--pages'));
const renderDirectory = option('--render-dir', false);
const replaceOutput = process.argv.includes('--replace-output');

assert.ok(fs.existsSync(htmlPath), `Missing HTML source: ${htmlPath}`);
assert.ok(Number.isInteger(expectedPages) && expectedPages > 0, '--pages must be a positive integer');
assert.ok(!pdfPath.includes(`${path.sep}guides${path.sep}`), 'Never render directly into the frozen guides directory');
assert.ok(!fs.existsSync(pdfPath) || replaceOutput, `Output already exists: ${pdfPath}`);

const { chromium } = loadGuideDependency('playwright');
const browser = await chromium.launch({ executablePath: resolveChromeExecutable(), headless: true });

try {
    const page = await browser.newPage();
    await page.goto(pathToFileURL(htmlPath).href, { waitUntil: 'load' });
    await page.emulateMedia({ media: 'print' });
    await page.evaluate(() => document.fonts.ready);

    const inspection = await page.evaluate(() => {
        const pages = [...document.querySelectorAll('.page, .guide-page')];
        const outside = pages.flatMap((container, pageIndex) => [...container.querySelectorAll('svg > text')]
            .filter((node) => {
                if (node.closest('svg')?.parentElement !== container) return false;
                const box = node.getBBox();
                return box.x < 0 || box.y < 0 || box.x + box.width > 210.3 || box.y + box.height > 297.3;
            })
            .map((node) => ({ page: pageIndex + 1, text: node.textContent })));
        return { pageElements: pages.length, outside };
    });

    assert.equal(inspection.pageElements, expectedPages, 'HTML page element count changed');
    assert.deepEqual(inspection.outside, [], 'Text falls outside an A4 page');
    fs.mkdirSync(path.dirname(pdfPath), { recursive: true });
    await page.pdf({ path: pdfPath, preferCSSPageSize: true, printBackground: true });
} finally {
    await browser.close();
}

const pdfinfo = resolveCommand('GUIDE_PDFINFO_PATH', 'pdfinfo');
const info = spawnSync(pdfinfo, [pdfPath], { encoding: 'utf8', windowsHide: true });
assert.equal(info.status, 0, info.stderr || info.stdout);
const pageMatch = info.stdout.match(/^Pages:\s+(\d+)$/m);
assert.equal(Number(pageMatch?.[1]), expectedPages, 'Rendered PDF page count changed');

if (renderDirectory) {
    const destination = path.resolve(repoRoot, renderDirectory);
    fs.mkdirSync(destination, { recursive: true });
    const pdftoppm = resolveCommand('GUIDE_PDFTOPPM_PATH', 'pdftoppm');
    const rendered = spawnSync(pdftoppm, ['-png', '-r', '144', pdfPath, path.join(destination, 'page')], {
        encoding: 'utf8',
        windowsHide: true,
    });
    assert.equal(rendered.status, 0, rendered.stderr || rendered.stdout);
}

console.log(JSON.stringify({ html: htmlPath, pdf: pdfPath, pages: expectedPages, rendered: renderDirectory }, null, 2));
