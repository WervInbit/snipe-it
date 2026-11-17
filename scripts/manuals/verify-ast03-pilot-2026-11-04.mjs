import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import {pathToFileURL} from 'node:url';
import {repoRoot, loadGuideDependency, browserLaunchOptions} from './lib/guide-paths.mjs';

assert.equal(process.argv.length, 2, 'This dated verifier accepts no arguments.');
const round = path.join(repoRoot, 'resources/manuals/operator-guides/review-rounds/2026-11-04-ast03-pilot');
const out = fs.existsSync(path.join(round, 'manifest.sha256'))
    ? path.join(repoRoot, 'output/manuals/proofs/ast03-pilot-2026-11-04') : path.join(round, 'qa');
fs.mkdirSync(out, {recursive: true});
const {chromium} = loadGuideDependency('playwright');
const browser = await chromium.launch(browserLaunchOptions({headless: true}));
const failures = [], measurements = [];
try {
    const page = await browser.newPage({viewport: {width: 1600, height: 1000}});
    await page.goto(pathToFileURL(path.join(round, 'review.html')).href);
    for (const baseline of ['18', '15']) {
        await page.locator('#baseline').selectOption(baseline);
        await page.evaluate(() => Promise.all([...document.images].map(n => n.decode())));
        const state = await page.evaluate(() => ({
            overflow: document.documentElement.scrollWidth > innerWidth,
            images: [...document.images].map(n => ({src: n.getAttribute('src'), loaded: n.complete && n.naturalWidth > 0})),
            widths: [...document.querySelectorAll('.pair')].map(n => [...n.querySelectorAll('img')].map(i => i.getBoundingClientRect().width)),
        }));
        if (state.overflow) failures.push('Unexpected horizontal scrolling for ' + baseline);
        if (state.images.some(i => !i.loaded)) failures.push('Broken image for ' + baseline);
        if (state.widths.some(w => Math.abs(w[0] - w[1]) > .5)) failures.push('Unequal paper widths for ' + baseline);
        measurements.push({baseline, ...state});
    }
    await page.locator('#baseline').selectOption('18');
    await page.evaluate(() => Promise.all([...document.images].map(n => n.decode())));
    await page.screenshot({path: path.join(out, 'review-desktop.png'), fullPage: true});
    await page.locator('#scale').click();
    const a4Widths = await page.locator('.pair img').evaluateAll(nodes => nodes.map(n => n.getBoundingClientRect().width));
    if (a4Widths.some(w => Math.abs(w - 210 * 96 / 25.4) > .6)) failures.push('Actual A4 width differs from 210 mm');
    fs.writeFileSync(path.join(out, 'browser-review.json'), JSON.stringify({failures, measurements, a4Widths}, null, 2) + '\n');
    assert.deepEqual(failures, []);
    console.log(JSON.stringify({baselinesChecked: 2, comparisonImages: 4, equalWidths: true, failures}));
} finally {await browser.close();}
