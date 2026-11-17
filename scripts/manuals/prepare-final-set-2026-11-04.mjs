import fs from 'node:fs';
import path from 'node:path';
import crypto from 'node:crypto';
import assert from 'node:assert/strict';
import {pathToFileURL} from 'node:url';
import {repoRoot, loadGuideDependency, browserLaunchOptions} from './lib/guide-paths.mjs';

assert.equal(process.argv.length, 2, 'No arguments supported.');
const out = path.join(repoRoot, 'output/manuals/final-guide-set-2026-11-04');
assert.ok(!fs.existsSync(path.join(out, 'manifest.sha256')), 'Sealed release: create a new version.');
for (const dir of ['inputs/baselines', 'inputs/specifications', 'source', 'assets', 'previews', 'pdf', 'affinity', 'idml', 'qa', 'review']) {
    fs.mkdirSync(path.join(out, dir), {recursive: true});
}
const read = p => JSON.parse(fs.readFileSync(p, 'utf8').replace(/^\uFEFF/, ''));
const hash = p => crypto.createHash('sha256').update(fs.readFileSync(p)).digest('hex');
const bytesHash = b => crypto.createHash('sha256').update(b).digest('hex');
const relative = p => path.relative(out, p).replaceAll('\\', '/');
const write = (file, obj) => fs.writeFileSync(file, JSON.stringify(obj, null, 2) + '\n');
const selectedFile = path.join(repoRoot, 'resources/manuals/operator-guides/review-rounds/2026-11-04-ast03-pilot/current-authoring-index.json');
const selected = read(selectedFile);
const {chromium} = loadGuideDependency('playwright'), sharp = loadGuideDependency('sharp');
const traceByHash = new Map(), sourceDirectories = new Set();
const guides = [];
for (const g of selected.guides) {
    const dir = path.dirname(path.join(repoRoot, g.pdf));
    sourceDirectories.add(dir);
    const frozen = read(path.join(dir, 'manifest.json'));
    const detail = frozen.guide ?? frozen.guides.find(v => v.code === g.code);
    assert.equal(hash(path.join(repoRoot, g.pdf)), g.sha256, g.code);
    guides.push({...g, ...detail, baselineVersion: g.version, version: g.version + 1,
        baselinePdf: g.pdf, baselineHtml: detail.html, baselineSha256: g.sha256});
}
for (const dir of sourceDirectories) {
    const file = path.join(dir, 'authoring-manifest.json');
    if (!fs.existsSync(file)) continue;
    for (const s of read(file).sources ?? []) {
        if (s.cropSha256) traceByHash.set(s.cropSha256, s);
        if (s.sourceSha256 && !traceByHash.has(s.sourceSha256)) traceByHash.set(s.sourceSha256, {...s, embeddedIsFullSource: true});
    }
}
const baselineIndex = [];
fs.copyFileSync(selectedFile, path.join(out, 'inputs/current-authoring-index.json'));
fs.copyFileSync(path.join(repoRoot, 'docs/manuals/operator-guides/reviews/final-set-and-affinity-plan-2026-11-04.md'), path.join(out, 'inputs/production-plan.md'));
const docs = path.join(repoRoot, 'docs/manuals/operator-guides');
for (const f of ['authoring-method.md', 'system.md', 'components.md', 'layouts.md', 'decisions.md', 'screenshots.md', 'catalog-guide-plan.md']) {
    fs.copyFileSync(path.join(docs, f), path.join(out, 'inputs', f));
}
const browser = await chromium.launch(browserLaunchOptions({headless: true}));
const assets = {}, inventory = [];
try {
    const page = await browser.newPage();
    for (const guide of guides.sort((a, b) => a.code.localeCompare(b.code))) {
        const src = path.join(repoRoot, guide.baselineHtml);
        const baselineCopy = path.join(out, 'inputs/baselines', `${guide.code}-v${guide.baselineVersion}`);
        fs.copyFileSync(src, baselineCopy + '.html');
        fs.copyFileSync(path.join(repoRoot, guide.baselinePdf), baselineCopy + '.pdf');
        fs.copyFileSync(path.join(docs, 'guides', guide.code + '.md'), path.join(out, 'inputs/specifications', guide.code + '.md'));
        baselineIndex.push({code: guide.code, version: guide.baselineVersion, pages: guide.pageCount,
            sourcePdf: guide.baselinePdf, sourceHtml: guide.baselineHtml, sha256: guide.baselineSha256});
        await page.goto(pathToFileURL(src).href);
        await page.evaluate(() => document.fonts.ready);
        const pages = await page.evaluate(() => [...document.querySelectorAll('.page>svg,.guide-page>svg')].map((svg, p) => {
            const box = n => {const b = n.getBBox(); return {x: b.x, y: b.y, w: b.width, h: b.height};};
            const ancestors = n => {const a = []; for (let g = n.parentElement; g && g !== svg; g = g.parentElement) {
                if (g.tagName.toLowerCase() === 'g') a.push({id: g.id, component: g.getAttribute('data-component'), target: g.getAttribute('data-target'),
                    attrs: Object.fromEntries([...g.attributes].map(a => [a.name, a.value]))});
            } return a.reverse();};
            const elements = [...svg.querySelectorAll('rect,circle,ellipse,line,path,polyline,polygon,text,image')].filter(n => !n.closest('defs')).map((n, index) => {
                const tag = n.tagName.toLowerCase(), attrs = Object.fromEntries([...n.attributes].map(a => [a.name, a.value]));
                const st = getComputedStyle(n), matrix = svg.getCTM().inverse().multiply(n.getCTM());
                const item = {id: `p${p + 1}-item-${index + 1}`, tag, attrs, bounds: box(n),
                    matrix: [matrix.a, matrix.b, matrix.c, matrix.d, matrix.e, matrix.f], groups: ancestors(n),
                    style: {fill: st.fill, stroke: st.stroke, strokeWidth: st.strokeWidth, opacity: st.opacity,
                        fontFamily: st.fontFamily, fontSize: st.fontSize, fontWeight: st.fontWeight, textAnchor: st.textAnchor}};
                if (tag === 'text') {
                    item.text = n.textContent;
                    item.lines = [...n.querySelectorAll('tspan')].map(t => ({text: t.textContent, x: t.getAttribute('x'), dy: t.getAttribute('dy')}));
                    if (!item.lines.length) item.lines = [{text: n.textContent}];
                    const ctx = document.createElement('canvas').getContext('2d');
                    ctx.font = `${st.fontWeight} ${st.fontSize} ${st.fontFamily}`;
                    const metrics = ctx.measureText('Hgx');
                    item.fontMetrics = {ascent: metrics.fontBoundingBoxAscent, descent: metrics.fontBoundingBoxDescent};
                }
                if (tag === 'image') {
                    item.href = n.getAttribute('href') ?? n.getAttributeNS('http://www.w3.org/1999/xlink', 'href');
                    const id = n.getAttribute('clip-path')?.match(/url\(#([^)]*)\)/)?.[1];
                    const clip = id && svg.querySelector(`[id="${id}"] rect`);
                    item.clip = clip ? {x: +clip.getAttribute('x'), y: +clip.getAttribute('y'), w: +clip.getAttribute('width'), h: +clip.getAttribute('height')} : null;
                }
                return item;
            });
            return {number: p + 1, svg: svg.outerHTML, width: 210, height: 297, elements};
        }));
        assert.equal(pages.length, guide.pageCount, guide.code + ': pages');
        for (const p of pages) {
            fs.writeFileSync(path.join(out, 'inputs/baselines', `${guide.code}-v${guide.baselineVersion}-p${p.number}.svg`), p.svg);
            delete p.svg;
            for (const item of p.elements.filter(i => i.tag === 'image')) {
                const payload = Buffer.from(item.href.split(',')[1], 'base64'), sha = bytesHash(payload);
                const trace = traceByHash.get(sha);
                const meta = await sharp(payload).metadata();
                let source = payload, crop = {x: 0, y: 0, w: meta.width, h: meta.height};
                let provenance = {embeddedSha256: sha, fullSourceRecovered: false};
                if (trace && fs.existsSync(path.join(repoRoot, trace.source))) {
                    const candidate = fs.readFileSync(path.join(repoRoot, trace.source));
                    assert.equal(bytesHash(candidate), trace.sourceSha256);
                    source = candidate;
                    crop = trace.embeddedIsFullSource ? {x: 0, y: 0, w: meta.width, h: meta.height} : trace.cropPixels;
                    provenance = {...provenance, ...trace, fullSourceRecovered: true};
                }
                const sourceSha = bytesHash(source), sourceMeta = await sharp(source).metadata();
                const ext = sourceMeta.format === 'jpeg' ? 'jpg' : sourceMeta.format;
                const file = path.join(out, 'assets', sourceSha.slice(0, 24) + '.' + ext);
                if (!fs.existsSync(file)) fs.writeFileSync(file, source);
                assets[sourceSha] = {file: relative(file), width: sourceMeta.width, height: sourceMeta.height,
                    sha256: sourceSha, originalSource: trace?.source};
                item.asset = sourceSha;
                item.sourceCrop = crop;
                item.provenance = provenance;
                let sx = +item.attrs.width / meta.width, sy = +item.attrs.height / meta.height;
                let imageX = +item.attrs.x, imageY = +item.attrs.y;
                if (item.attrs.preserveAspectRatio !== 'none') {
                    const scale = item.attrs.preserveAspectRatio?.includes('slice') ? Math.max(sx, sy) : Math.min(sx, sy);
                    sx = sy = scale;
                    imageX += (+item.attrs.width - meta.width * scale) / 2;
                    imageY += (+item.attrs.height - meta.height * scale) / 2;
                }
                item.originalLayoutBox = {x: +item.attrs.x, y: +item.attrs.y, w: +item.attrs.width, h: +item.attrs.height};
                item.paintBounds = {x: imageX, y: imageY, w: meta.width * sx, h: meta.height * sy};
                if (!item.clip) item.clip = {...item.paintBounds};
                item.fullImageBounds = {x: imageX - crop.x * sx, y: imageY - crop.y * sy,
                    w: sourceMeta.width * sx, h: sourceMeta.height * sy};
                delete item.href; delete item.attrs.href; delete item.attrs['xlink:href'];
            }
        }
        const doc = {code: guide.code, version: guide.version, baselineVersion: guide.baselineVersion,
            pageCount: guide.pageCount, status: 'In production; native proof pending', pages};
        write(path.join(out, 'source', guide.code + '.baseline.json'), doc);
        inventory.push({code: guide.code, baselineVersion: guide.baselineVersion, version: guide.version, pages: pages.length,
            images: pages.flatMap(p => p.elements.filter(e => e.tag === 'image')).length,
            source: `source/${guide.code}.baseline.json`});
        console.log(JSON.stringify({code: guide.code, pages: pages.length, fullSourceImages: pages.flatMap(p => p.elements).filter(e => e.provenance?.fullSourceRecovered).length}));
    }
} finally {await browser.close();}
write(path.join(out, 'inputs/baseline-index.json'), baselineIndex);
write(path.join(out, 'source/assets.json'), assets);
write(path.join(out, 'source/inventory.json'), {guides: inventory, totalPages: inventory.reduce((n, g) => n + g.pages, 0),
    nativeStatus: 'Pending desktop connection recovery and native proof'});
const copy = inventory.map(g => {
    const doc = read(path.join(out, g.source));
    return '# ' + g.code + ' v' + g.baselineVersion + '\n\n' + doc.pages.map(p => '## Page ' + p.number + '\n\n' + p.elements.filter(i => i.tag === 'text').map(i => i.lines.map(l => l.text).join('\n')).join('\n\n')).join('\n\n');
}).join('\n\n');
fs.writeFileSync(path.join(out, 'source/BASELINE-COPY.md'), copy + '\n');
console.log(JSON.stringify({guides: inventory.length, pages: inventory.reduce((n, g) => n + g.pages, 0), assets: Object.keys(assets).length}));
