import fs from 'node:fs';
import path from 'node:path';
import crypto from 'node:crypto';
import assert from 'node:assert/strict';
import {pathToFileURL} from 'node:url';
import {repoRoot, loadGuideDependency, browserLaunchOptions} from './lib/guide-paths.mjs';
import {finalScenes, provenanceIndex} from './lib/final-guide-scenes-2026-11-04.mjs';
import {finalCatalogScenes} from './lib/final-catalog-scenes-2026-11-04.mjs';
import {GUIDE_REGISTRY, GUIDE_FAMILIES} from './lib/guide-system.mjs';

assert.equal(process.argv.length, 2, 'This dated production script accepts no arguments.');
const out = path.join(repoRoot, 'output/manuals/final-guide-set-2026-11-04');
assert(!fs.existsSync(path.join(out, 'manifest.sha256')), 'Sealed release: create another version.');
const read = p => JSON.parse(fs.readFileSync(p, 'utf8').replace(/^\uFEFF/, ''));
const write = (p, value) => fs.writeFileSync(p, JSON.stringify(value, null, 2) + '\n');
const {chromium} = loadGuideDependency('playwright'), sharp = loadGuideDependency('sharp');
const assets = read(path.join(out, 'source/assets.json'));
const baselineIndex = read(path.join(out, 'inputs/baseline-index.json'));
const scenes = await finalScenes();
const catalog = await finalCatalogScenes();
const overrides = {...scenes.pages, ...catalog};
const provenance = provenanceIndex();
const inventory = [], geometry = [];
const browser = await chromium.launch(browserLaunchOptions({headless: true}));
try {
    const p = await browser.newPage({viewport: {width: 1000, height: 1500}, deviceScaleFactor: 1.5});
    for (const guide of baselineIndex) {
        const code = guide.code, baseline = read(path.join(out, 'source', code + '.baseline.json'));
        const pageCount = Array.isArray(overrides[code]) ? overrides[code].length : baseline.pages.length;
        const finalPages = [];
        for (let i = 0; i < pageCount; i++) {
            const custom = Array.isArray(overrides[code]) ? overrides[code][i] : overrides[code]?.[i + 1];
            const svg = custom ?? fs.readFileSync(path.join(out, 'inputs/baselines', `${code}-v${guide.version}-p${i + 1}.svg`), 'utf8');
            await p.setContent(`<html><head><style>html,body{margin:0;background:white}svg{display:block;width:210mm;height:297mm}</style></head><body>${svg}</body></html>`);
            await p.evaluate(() => document.fonts.ready);
            if (!custom) await p.evaluate(({elements, code, version}) => {
                const svg = document.querySelector('svg'), ns = svg.namespaceURI;
                const nodes = [...svg.querySelectorAll('rect,circle,ellipse,line,path,polyline,polygon,text,image')].filter(n => !n.closest('defs'));
                const map = Object.fromEntries(elements.map((e, i) => {nodes[i].setAttribute('data-scene-id', e.id); return [e.id, nodes[i]];}));
                const defs = svg.querySelector('defs') ?? svg.insertBefore(document.createElementNS(ns, 'defs'), svg.firstChild);
                for (const e of elements.filter(e => e.tag === 'image')) {
                    const n = map[e.id], b = e.fullImageBounds, c = e.clip;
                    for (const [k, v] of Object.entries({x: b.x, y: b.y, width: b.w, height: b.h})) n.setAttribute(k, v);
                    const clip = document.createElementNS(ns, 'clipPath'), rect = document.createElementNS(ns, 'rect');
                    clip.id = 'source-' + e.id;
                    for (const [k, v] of Object.entries({x: c.x, y: c.y, width: c.w, height: c.h})) rect.setAttribute(k, v);
                    clip.append(rect); defs.append(clip); n.setAttribute('clip-path', `url(#${clip.id})`);
                    n.setAttribute('data-source-key', e.provenance.key ?? e.id);
                    n.setAttribute('data-asset-sha', e.asset);
                }
                const replace = (needle, lines, opts = {}) => {
                    const n = [...svg.querySelectorAll('text')].find(n => n.textContent.includes(needle));
                    if (!n) throw new Error(code + ': missing text ' + needle);
                    const x = opts.x ?? n.getAttribute('x'), y = opts.y ?? n.getAttribute('y');
                    n.replaceChildren(); n.setAttribute('x', x); n.setAttribute('y', y);
                    if (opts.size) n.setAttribute('font-size', opts.size);
                    (Array.isArray(lines) ? lines : [lines]).forEach((line, i) => {
                        const t = document.createElementNS(ns, 'tspan'); t.setAttribute('x', x); t.setAttribute('dy', i ? opts.lh ?? 3.8 : 0); t.textContent = line; n.append(t);
                    }); return n;
                };
                for (const n of svg.querySelectorAll('text')) {
                    const b = n.getBBox();
                    if (b.y < 34 || b.y > 290) {
                        for (const child of n.querySelectorAll('tspan').length ? n.querySelectorAll('tspan') : [n]) {
                            child.textContent = child.textContent.replace(/Concept v\d+|Versie v?\d+/g, 'Versie ' + version)
                                .replace(new RegExp(code + ' v\\d+', 'g'), code + ' v' + version);
                            if (b.y < 34 || child.textContent.includes('Inbit Snipe-IT')) child.textContent = child.textContent
                                .replace(/\d{2}-\d{2}-2026|2026-\d{2}-\d{2}/g, '04-11-2026');
                        }
                    }
                }
                if (code === 'AC-01') {
                    replace('Vraag je supervisor of', ['Vraag een Admin om', 'een account.']);
                    replace('Vraag je supervisor om', ['Vraag een Admin om', 'een wachtwoordreset.']);
                }
                if (code === 'HELP-01' && elements[0].id.startsWith('p1-')) {
                    replace('Vraag Supervisor om een account.', ['Vraag een Admin om een account.','Gebruik je eigen inloggegevens.']);
                    replace('Vraag Supervisor om een wachtwoordreset.', ['Vraag een Admin om een wachtwoordreset.','Deel je wachtwoord niet met anderen.']);
                }
                if (code === 'CAT-00' && elements[0].id.startsWith('p6-')) {
                    replace('Gids volgt; vraag een Admin', 'Laat een Admin de passende onderhoudsroute volgen.');
                    replace('Gids volgt; laat een Supervisor', 'Laat een Supervisor label, bron en exacte vraag controleren.');
                }
                if (code === 'USR-05') {
                    for (const n of svg.querySelectorAll('text')) for (const child of n.querySelectorAll('tspan').length ? n.querySelectorAll('tspan') : [n]) {
                        child.textContent = child.textContent.replace(/USR-01 stap 3/g, 'USR-01 stap 4');
                    }
                }
                if (code === 'CMP-01') replace('Zoek de bestaande registratie', ['Zoek de bestaande registratie in je tray of opslag.',
                    'Vergelijk bron, naam, tag en S/N met het fysieke onderdeel.',
                    'Vervanging? Koppel het oude onderdeel eerst los (CMP-04).',
                    'Geen passende registratie of een conditiebarriere?',
                    'Laat een Supervisor de identiteit en juiste route bepalen.',
                    'Maak geen tweede record om de melding te omzeilen.'], {lh: 3.2});
                if (code === 'CAT-04') replace('Vraag een Admin om lifecycle of opruimen. volg CAT-05.', 'Vraag een Admin om lifecycle of opruimen; volg CAT-05.');
            }, {elements: baseline.pages[i].elements, code, version: guide.version + 1});
            if (!custom) {
                const imageNodes = await p.locator('svg image[data-asset-sha]').all();
                for (const n of imageNodes) {
                    const sha = await n.getAttribute('data-asset-sha'), asset = assets[sha];
                    const bytes = fs.readFileSync(path.join(out, asset.file));
                    await n.evaluate((n, href) => {n.setAttribute('href', href); n.removeAttributeNS('http://www.w3.org/1999/xlink', 'href');},
                        `data:image/${asset.file.endsWith('.jpg') ? 'jpeg' : 'png'};base64,${bytes.toString('base64')}`);
                }
            }
            await p.evaluate(({code, version, number, total, title, palette}) => {
                const svg = document.querySelector('svg'), ns = svg.namespaceURI;
                const texts = [...svg.querySelectorAll('text')];
                const purpose = texts.find(n => {const b = n.getBBox(); return b.x < 25 && b.y > 24 && b.y < 32;})?.textContent ?? '';
                for (const n of svg.querySelectorAll('rect,circle,text,line')) {
                    if (n.closest('defs')) continue;
                    const b = n.getBBox();
                    const oldFooter = n.tagName.toLowerCase() === 'text' && b.y > 285 &&
                        (/^Inbit Snipe-IT \||^Bron:|^Pagina \d+ van \d+$/.test(n.textContent) || n.textContent.includes('https://snipe.inbit/'));
                    if ((b.y >= 0 && b.y + b.height < 34) || oldFooter) n.remove();
                }
                const make = (tag, attrs, parent = svg) => {const n = document.createElementNS(ns, tag); for (const [k,v] of Object.entries(attrs)) n.setAttribute(k,v); parent.append(n); return n;};
                const head = make('g', {'data-component':'page-header'});
                const copy = (x,y,txt,size,weight,fill,anchor='start',parent=head) => {
                    const n = make('text', {x,y,'font-family':'Arial, Helvetica, sans-serif','font-size':size,'font-weight':weight,fill,'text-anchor':anchor},parent); n.textContent = txt; return n;
                };
                make('rect',{x:12,y:13,width:2,height:16,fill:palette.color},head);
                const name = copy(18,22,code+' '+title,5.9,900,'#102033');
                while (name.getBBox().width > 140 && +name.getAttribute('font-size') > 4.5) name.setAttribute('font-size',+name.getAttribute('font-size')-.1);
                copy(18,29.5,purpose,2.8,400,'#53657A');
                make('rect',{x:164,y:10,width:34,height:13,rx:1.4,fill:palette.fill,stroke:palette.color,'stroke-width':.45},head);
                copy(181,14.6,'Versie '+version,2.5,800,palette.color,'middle');
                copy(181,19.5,`04-11-2026 | ${number}/${total}`,2.15,400,palette.color,'middle');
                const foot = make('g',{'data-component':'page-footer'});
                copy(12,296,`Inbit Snipe-IT | ${code} v${version} | 04-11-2026`,1.65,400,'#53657A','start',foot).setAttribute('data-component','page-footer-left');
                copy(198,296,`${number}/${total} | https://snipe.inbit/`,1.65,400,'#53657A','end',foot);
                for (const n of svg.querySelectorAll('[data-component="context-value"]')) {
                    const right = +n.getAttribute('data-right');
                    while (n.getBBox().x + n.getBBox().width > right && +n.getAttribute('font-size') > 2.0) n.setAttribute('font-size', +n.getAttribute('font-size') - .05);
                }
            }, {code, version:guide.version+1, number:i+1,total:pageCount,title:GUIDE_REGISTRY[code].title,palette:GUIDE_FAMILIES[code.split('-')[0]]});
            const result = await p.evaluate(() => {
                const svg = document.querySelector('svg');
                const box = n => {const b = n.getBBox(); return {x: b.x, y: b.y, w: b.width, h: b.height};};
                const ancestors = n => {const a = []; for (let g = n.parentElement; g && g !== svg; g = g.parentElement) {
                    if (g.tagName.toLowerCase() === 'g') a.push({id: g.id, component: g.getAttribute('data-component'), target: g.getAttribute('data-target'),
                        attrs: Object.fromEntries([...g.attributes].map(a => [a.name, a.value]))});
                } return a.reverse();};
                const elements = [...svg.querySelectorAll('rect,circle,ellipse,line,path,polyline,polygon,text,image')].filter(n => !n.closest('defs')).map((n, index) => {
                    const tag = n.tagName.toLowerCase(), attrs = Object.fromEntries([...n.attributes].map(a => [a.name, a.value]));
                    const st = getComputedStyle(n), matrix = svg.getCTM().inverse().multiply(n.getCTM());
                    const item = {id: `item-${index + 1}`, tag, attrs, bounds: box(n),
                        matrix: [matrix.a, matrix.b, matrix.c, matrix.d, matrix.e, matrix.f].map(n => Math.abs(n) < 1e-10 ? 0 : n), groups: ancestors(n),
                        style: {fill: st.fill, stroke: st.stroke, strokeWidth: st.strokeWidth, opacity: st.opacity,
                            fontFamily: st.fontFamily, fontSize: st.fontSize, fontWeight: st.fontWeight, textAnchor: st.textAnchor}};
                    if (tag === 'text') {
                        item.text = n.textContent; item.lines = [...n.querySelectorAll('tspan')].map(t => ({text: t.textContent, x: t.getAttribute('x'), dy: t.getAttribute('dy')}));
                        if (!item.lines.length) item.lines = [{text: n.textContent}];
                        item.hyperlink = n.closest('a')?.getAttribute('href');
                        const ctx = document.createElement('canvas').getContext('2d'); ctx.font = `${st.fontWeight} ${st.fontSize} ${st.fontFamily}`;
                        const m = ctx.measureText('Hgx'); item.fontMetrics = {ascent: m.fontBoundingBoxAscent, descent: m.fontBoundingBoxDescent};
                    }
                    if (tag === 'image') {
                        item.href = n.getAttribute('href') ?? n.getAttributeNS('http://www.w3.org/1999/xlink', 'href');
                        const id = n.getAttribute('clip-path')?.match(/url\(#([^)]*)\)/)?.[1];
                        const clip = id && svg.querySelector(`[id="${id}"] rect`);
                        item.clip = clip ? {x: +clip.getAttribute('x'), y: +clip.getAttribute('y'), w: +clip.getAttribute('width'), h: +clip.getAttribute('height')} : null;
                    }
                    return item;
                });
                const text = elements.filter(e => e.tag === 'text');
                const global = (e, b = e.bounds) => ({x:b.x+e.matrix[4], y:b.y+e.matrix[5],w:b.w,h:b.h});
                const overlaps = (a,b) => Math.min(a.x+a.w,b.x+b.w)-Math.max(a.x,b.x) > 2 && Math.min(a.y+a.h,b.y+b.h)-Math.max(a.y,b.y) > .7;
                const copy = text.filter(e => e.attrs['data-role'] !== 'badge-label' && e.attrs['data-role'] !== 'family-label');
                const collisions = copy.flatMap(t => elements.filter(e => e.tag==='image' && overlaps(global(t),global(e,e.clip))).map(e => ({text:t.text,image:e.id,key:e.attrs['data-source-key']??e.groups.find(g=>g.attrs['data-source-key'])?.attrs['data-source-key']})));
                const textCollisions = copy.flatMap((a,i) => copy.slice(i+1).filter(b=>overlaps(global(a),global(b))).map(b=>({a:a.text,b:b.text})));
                return {svg: svg.outerHTML, width: 210, height: 297, elements, collisions,textCollisions,
                    overflow: text.filter(e => e.bounds.x < 8 || e.bounds.x + e.bounds.w > 202 || e.bounds.y < 0 || e.bounds.y + e.bounds.h > 298).map(e => ({id:e.id, text:e.text, bounds:e.bounds}))};
            });
            geometry.push({code, page: i + 1, overflow: result.overflow, textImageCollisions:result.collisions, textCollisions:result.textCollisions});
            const svgName = `${code}-v${guide.version + 1}-p${i + 1}.svg`;
            fs.writeFileSync(path.join(out, 'source', svgName), result.svg);
            await p.locator('svg').screenshot({path: path.join(out, 'previews', `${code}-${i + 1}.png`)});
            for (const e of result.elements.filter(e => e.tag === 'image')) {
                const bytes = Buffer.from(e.href.split(',')[1], 'base64'), sha = crypto.createHash('sha256').update(bytes).digest('hex');
                const meta = await sharp(bytes).metadata();
                if (!assets[sha]) {
                    const key = e.attrs['data-source-key'] ?? e.groups.find(g => g.attrs['data-source-key'])?.attrs['data-source-key'];
                    const ext = meta.format === 'jpeg' ? 'jpg' : meta.format, file = `assets/${sha.slice(0,24)}.${ext}`;
                    fs.writeFileSync(path.join(out, file), bytes);
                    assets[sha] = {file, width: meta.width, height: meta.height, sha256: sha, originalSource: provenance[key]?.source};
                }
                const key = e.attrs['data-source-key'] ?? e.groups.find(g => g.attrs['data-source-key'])?.attrs['data-source-key'];
                e.asset = sha; e.provenance = {...provenance[key], key, fullSourceRecovered: true};
                e.fullImageBounds = {x: +e.attrs.x, y: +e.attrs.y, w: +e.attrs.width, h: +e.attrs.height};
                assert(e.clip, code + ' image has no editable crop');
                const sx = e.fullImageBounds.w / meta.width, sy = e.fullImageBounds.h / meta.height;
                assert(Math.abs(sx - sy) < 1e-5, code + ' image distortion');
                e.sourceCrop = {x: (e.clip.x - e.fullImageBounds.x) / sx, y: (e.clip.y - e.fullImageBounds.y) / sy,
                    w: e.clip.w / sx, h: e.clip.h / sy};
                delete e.href; delete e.attrs.href; delete e.attrs['xlink:href'];
            }
            delete result.svg; delete result.overflow; delete result.collisions; delete result.textCollisions;
            finalPages.push({number: i + 1, ...result});
        }
        const doc = {code, title: GUIDE_REGISTRY[code].title, version: guide.version + 1, baselineVersion: guide.version, pageCount,
            status: 'Prepared source; native import and production-target verification pending', pages: finalPages};
        write(path.join(out, 'source', code + '.json'), doc);
        const html = '<!doctype html><html lang="nl"><meta charset="utf-8"><title>' + code + ' v' + doc.version + '</title>' +
            '<style>body{margin:0;background:#dce2e8}.page{width:210mm;height:297mm;margin:12px auto;background:white}.page>svg{width:210mm;height:297mm;display:block}@media print{@page{size:A4;margin:0}body{background:white}.page{margin:0;break-after:page}}</style>' +
            finalPages.map(pg => '<div class="page">' + fs.readFileSync(path.join(out, 'source', `${code}-v${doc.version}-p${pg.number}.svg`), 'utf8') + '</div>').join('');
        fs.writeFileSync(path.join(out, 'source', `${code}-v${doc.version}.html`), html);
        inventory.push({code, title: doc.title, baselineVersion: guide.version, version: doc.version, pages: pageCount, source: `source/${code}.json`, html: `source/${code}-v${doc.version}.html`});
        console.log(JSON.stringify({code, pages: pageCount, version: doc.version}));
    }
} finally {await browser.close();}
write(path.join(out, 'source/assets.json'), assets);
write(path.join(out, 'source/inventory.json'), {guides: inventory, totalPages: inventory.reduce((n,g) => n + g.pages,0), nativeStatus: 'Pending desktop restoration and native proof'});
write(path.join(out, 'qa/geometry.json'), geometry);
console.log(JSON.stringify({guides: inventory.length, pages: inventory.reduce((n,g) => n + g.pages,0), textOverflows: geometry.flatMap(g => g.overflow).length}));
