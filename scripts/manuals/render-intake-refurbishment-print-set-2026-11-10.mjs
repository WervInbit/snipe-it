// Render existing guide layouts for printing before the native Affinity handoff.
import fs from 'node:fs';
import path from 'node:path';
import { createRequire } from 'node:module';
import { createHash } from 'node:crypto';

const root = process.cwd();
const source = path.join(root, 'output/manuals/final-guide-set-2026-11-04');
const output = path.join(root, 'output/pdf/intake-refurbishment-2026-11-10');
const intermediate = path.join(root, 'tmp/pdfs/intake-refurbishment-2026-11-10');
if (fs.existsSync(output) || fs.existsSync(intermediate)) throw Error('Existing print preparation preserved.');
fs.mkdirSync(output, { recursive: true });
fs.mkdirSync(path.join(intermediate, 'individual'), { recursive: true });

const core = ['AST-02', 'AC-01', 'SC-01', 'AST-03', 'WF-01', 'WF-02', 'AST-04', 'AST-05', 'HELP-01'];
const full = [...core.slice(0, 6), 'CMP-01', 'CMP-02', 'CMP-04', ...core.slice(6)];
const originalHashes = new Map(fs.readFileSync(path.join(source, 'preparation.sha256'), 'utf8')
    .trim().split(/\r?\n/).map(line => [line.slice(66), line.slice(0, 64)]));
const guides = [];
const sha = data => createHash('sha256').update(data).digest('hex');
for (const code of full) {
    const jsonRelative = 'source/' + code + '.json';
    const bytes = fs.readFileSync(path.join(source, jsonRelative));
    if (sha(bytes) !== originalHashes.get(jsonRelative)) throw Error('Changed source: ' + jsonRelative);
    const doc = JSON.parse(bytes.toString('utf8'));
    const pages = [];
    for (let number = 1; number <= doc.pageCount; number++) {
        const relative = `source/${code}-v${doc.version}-p${number}.svg`;
        const svg = fs.readFileSync(path.join(source, relative));
        if (sha(svg) !== originalHashes.get(relative)) throw Error('Changed SVG: ' + relative);
        pages.push({ file: relative, sha256: sha(svg), svg: svg.toString('utf8') });
    }
    guides.push({ code, title: doc.title, version: doc.version, pages: doc.pageCount, sourcePages: pages });
}

const require = createRequire(import.meta.url);
const modules = 'C:/Program Files/WindowsApps/OpenAI.CodexPrimaryRuntime.v26-1104-709-0_26.1104.709.0_x64__3k8sg7r9htsxt/dependencies/node/node_modules';
const { chromium } = require(path.join(modules, 'playwright'));
const bundledBrowser = chromium.executablePath();
const browser = await chromium.launch({ headless: true,
    executablePath: fs.existsSync(bundledBrowser) ? bundledBrowser : 'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe' });
try {
    const context = await browser.newContext({ viewport: { width: 1000, height: 1400 } });
    await context.route('**/*', route => /^https?:/i.test(route.request().url()) ? route.abort() : route.continue());
    for (const guide of guides) {
        const page = await context.newPage();
        const html = '<!doctype html><html lang="nl"><meta charset="utf-8"><style>' +
            '@page{size:A4;margin:0}html,body{margin:0;padding:0}section{width:210mm;height:297mm;margin:0;overflow:hidden;break-after:page;break-inside:avoid}' +
            'section:last-child{break-after:auto}svg{display:block;width:210mm;height:297mm}</style><body>' +
            guide.sourcePages.map(p => '<section>' + p.svg + '</section>').join('') + '</body></html>';
        await page.setContent(html, { waitUntil: 'load' });
        await page.emulateMedia({ media: 'print' });
        await page.evaluate(async () => { await document.fonts.ready; });
        if (await page.locator('section').count() !== guide.pages) throw Error('Page sections missing: ' + guide.code);
        const target = path.join(intermediate, 'individual', `${guide.code}-v${guide.version}.pdf`);
        await page.pdf({ path: target, format: 'A4', preferCSSPageSize: true, printBackground: true,
            displayHeaderFooter: false, margin: { top: 0, right: 0, bottom: 0, left: 0 } });
        guide.intermediatePdf = target;
        guide.sourcePages = guide.sourcePages.map(({ svg, ...info }) => info);
        await page.close();
        process.stdout.write(JSON.stringify({ code: guide.code, expectedPages: guide.pages, rendered: true }) + '\n');
    }
    await context.close();
} finally { await browser.close(); }
const manifest = { purpose: 'A4 print set from existing source layouts; native Affinity work follows separately',
    date: '2026-11-10', source: source.replaceAll('\\', '/'), core, full, guides,
    guideContentChanged: false, physicalPrintingSent: false, nativeAffinityUsed: false };
fs.writeFileSync(path.join(output, 'manifest.json'), JSON.stringify(manifest, null, 2) + '\n');
process.stdout.write(JSON.stringify({ output, guideCount: guides.length, printedContentPages: guides.reduce((n, g) => n + g.pages, 0) }) + '\n');
