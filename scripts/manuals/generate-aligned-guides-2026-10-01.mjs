import assert from 'node:assert/strict';
import crypto from 'node:crypto';
import fs from 'node:fs';
import path from 'node:path';
import { spawnSync } from 'node:child_process';
import { pathToFileURL } from 'node:url';
import { loadGuideDependency, repoRoot, repoPdfOutputRoot, resolveChromeExecutable, resolveCommand } from './lib/guide-paths.mjs';
import { GUIDE_TOKENS, GUIDE_FAMILIES, GUIDE_REGISTRY, SvgGuideDocument, guideReference, xml } from './lib/guide-system.mjs';
import { revisions } from './aligned-guide-content-2026-10-01.mjs';

const round = path.join(repoRoot, 'resources/manuals/operator-guides/review-rounds/2026-10-01-aligned-guides');
const output = path.join(repoRoot, 'output/manuals/proofs/aligned-guides');
const inputs = JSON.parse(fs.readFileSync(path.join(round, 'inputs.json')));
const codesArg = process.argv.find(a => a.startsWith('--codes='));
const selected = codesArg ? codesArg.slice(8).split(',') : inputs.guides.map(g => g.code);
const retain = process.argv.includes('--retain');
assert.ok(!retain || !codesArg, 'Retain only a complete, visually reviewed batch.');
const hash = f => crypto.createHash('sha256').update(fs.readFileSync(f)).digest('hex');
const sharp = loadGuideDependency('sharp');
const { chromium } = loadGuideDependency('playwright');
fs.mkdirSync(output, { recursive:true });
fs.mkdirSync(repoPdfOutputRoot, { recursive:true });

const evidence = [];
for (const file of fs.readdirSync(path.join(round,'evidence')).filter(f => f.endsWith('.jpg'))) {
    const full = path.join(round,'evidence',file);
    const meta = await sharp(full).metadata();
    assert.equal(meta.format,'jpeg');
    evidence.push({file:`evidence/${file}`,sha256:hash(full),width:meta.width,height:meta.height,format:meta.format});
}
const cropCache = new Map();
async function crop(file, area) {
    const key = `${file}:${area.join(',')}`;
    if (cropCache.has(key)) return cropCache.get(key);
    const full = path.join(round,'evidence',file);
    const meta = evidence.find(e => e.file === `evidence/${file}`);
    assert.ok(meta && area[0]>=0 && area[1]>=0 && area[0]+area[2]<=meta.width && area[1]+area[3]<=meta.height, `Invalid crop ${key}`);
    const bytes = await sharp(full).extract({left:area[0],top:area[1],width:area[2],height:area[3]}).png().toBuffer();
    const result = {uri:`data:image/png;base64,${bytes.toString('base64')}`,file,crop:area};
    cropCache.set(key,result);
    return result;
}

function diagram(kind, family) {
    const p = GUIDE_FAMILIES[family];
    const d = new SvgGuideDocument({},`aligned-${kind}`);
    const box = (y,label,value) => {d.rect(3,y,174,31,p.fill,p.color,.55,3);d.text(10,y+10,label,{size:4,weight:800,fill:p.color});d.text(10,y+22,value,{size:5.4,weight:800});};
    if (kind==='sum') {
        box(3,'Twee modules','8 GB  +  8 GB');
        box(41,'Totale capaciteit','16 GB');
        d.text(10,85,'Bijdrage x hoeveelheid; tel de totalen op.',{size:3.5});
    } else if (kind==='distinct') {
        box(3,'Gelijke eigenschap','3200 MHz  +  3200 MHz  ->  3200 MHz');
        box(41,'Verschillende eigenschappen','2666 MHz  en  3200 MHz');
        d.text(10,85,'Gelijke waarde eenmaal; verschillende blijven zichtbaar.',{size:3.5});
    } else if (kind==='numeric') {
        box(3,'Sum values by quantity','2 x 8 GB  =  16 GB');
        box(41,'Keep distinct values','2 x 3200 MHz  =  3200 MHz');
        d.text(10,85,'2666 + 3200 MHz  ->  2666 en 3200 MHz',{size:3.5});
    } else {
        const rows = {
            route:[['Workflow uitvoeren','Done + actuele voorwaarden controleren'],['Overdragen','QA Hold + notitie -> supervisor']],
            handoff:[['Dossier','Asset tag + uitgevoerd werk + afwijking'],['Vervolg','Gevraagde beoordeling + fysieke QA-locatie']],
            rights:[['Taakrechten','Uitvoeren | bewerken | extra run | override'],['Effectieve toegang','Groepen + directe rechten + statusregels']],
            roles:[['Refurbisher','Werk uitvoeren; kwaliteit lezen'],['Senior / Supervisor','Aanmaken en kwaliteit; eigen workflowrol']],
            source:[['Directe waarde','Feit bij het exacte modelnummer'],['Componentbron','Definitie + bijdrage + hoeveelheid + kinderen']],
        }[kind];
        assert.ok(rows,`Unknown diagram ${kind}`);
        rows.forEach((row,i)=>box(3+i*38,...row));
        d.text(10,85,'Controleer de bron en het opgeslagen resultaat.',{size:3.5});
    }
    return `<svg class="aligned-diagram" viewBox="0 0 180 94" aria-label="${xml(kind)}">${d.parts.join('')}</svg>`;
}

async function supplement(g, revision) {
    const e = revision.extra;
    if (!e) return '';
    const family = g.code.split('-')[0];
    const palette = GUIDE_FAMILIES[family];
    const cards = e.steps.map((s,i) => `<article class="aligned-step" data-step="${i+1}"><span class="aligned-number">${i+1}</span><h3>${xml(s.title)}</h3><p>${xml(s.body)}</p><span class="aligned-see">Zie ${i+1}A</span></article>`).join('');
    const figures = [];
    for (const [i,s] of e.steps.entries()) {
        const shot = s.shot ? await crop(s.shot,s.crop) : null;
        const tall = s.shot?.startsWith('RELEASE-');
        const height = tall ? 77 : (s.shot?.startsWith('MODEL-SPEC') ? 63 : 40);
        figures.push(`<figure class="aligned-figure" data-source="${xml(s.shot ?? `diagram:${s.diagram}`)}" style="--figure-height:${height}mm"><span class="aligned-image-number">${i+1}A</span><div class="aligned-picture">${shot ? `<img src="${shot.uri}" alt="${xml(s.caption)}">` : diagram(s.diagram,family)}</div><figcaption>${xml(s.caption)}</figcaption></figure>`);
    }
    const refs = e.related.map(code => {const ref=guideReference(code);const pal=GUIDE_FAMILIES[ref.family];return `<span class="aligned-reference" data-guide-code="${code}" style="--ref:${pal.color};--ref-fill:${pal.fill}"><b>${ref.family}</b>${xml(ref.label)}</span>`;}).join('');
    return `<div class="page aligned-page" style="--family:${palette.color};--family-fill:${palette.fill}" data-code="${g.code}">
<header class="aligned-header"><div><h1>${xml(g.code+' '+GUIDE_REGISTRY[g.code].title)}</h1><p>${xml(e.title)}</p></div><aside>Concept v${g.nextVersion}<br>2026-10-01<br><strong>Niet geaccepteerd</strong><br>${g.pages+1} van ${g.pages+1}</aside></header>
<div class="aligned-context"><div><small>Rol</small><b>${xml(e.role)}</b></div><div><small>Nodig</small><b>${xml(e.need)}</b></div><div><small>Vooraf</small><b>${xml(guideReference(e.before).label)}</b></div></div>
<main class="aligned-main"><section class="aligned-steps">${cards}</section><section class="aligned-figures">${figures.join('')}</section></main>
<section class="aligned-help"><h2>Hulp en controle</h2>${e.help.map(t=>`<p>${xml(t)}</p>`).join('')}</section>
<p class="aligned-done"><b>Klaar als</b> ${xml(e.done)}</p>
<nav class="aligned-related"><small>Relevante gidsen</small>${refs}</nav>
<footer>Schermvoorbeelden: dev.inbit, 2026-10-01. Labels kunnen Engels zijn. Concept v${g.nextVersion}.</footer>
</div>`;
}

function compactRelated(code) {
    const codes=code==='AC-01'?['SC-01','AST-02','HELP-01']:['AC-01','WF-01','HELP-01'];
    const d=new SvgGuideDocument({},`${code}-related`);
    const y=code==='SC-01'?285:268;
    d.text(12,y+4.2,'Relevante gidsen',{size:2.1,weight:800,fill:GUIDE_TOKENS.colors.muted});
    let x=38;
    for(const code of codes){const ref=guideReference(code);const w=13+ref.label.length*1.08;assert.ok(x+w<=174);d.guideChip(x,y,w,ref);x+=w+2;}
    return d.parts.join('');
}

const css = `
html,body{height:auto!important;overflow:visible!important}
.page,.guide-page{break-after:page;page-break-after:always}
.aligned-page{display:block;box-sizing:border-box;width:210mm!important;height:297mm!important;padding:12mm;background:#fff;color:${GUIDE_TOKENS.colors.ink};font-family:${GUIDE_TOKENS.type.fontFamily};overflow:hidden;position:relative;break-after:page;page-break-after:always}
.aligned-header{height:29mm;display:grid;grid-template-columns:148mm 34mm;gap:4mm;border-left:2mm solid var(--family);padding-left:4mm;box-sizing:border-box}
.aligned-header h1{margin:0;font-size:5.5mm;line-height:1.06;font-weight:900;max-width:140mm;white-space:normal}
.aligned-header p{font-size:3.1mm;line-height:1.2;margin:3mm 0 0;color:${GUIDE_TOKENS.colors.muted}}
.aligned-header aside{font-size:2.25mm;line-height:1.35;text-align:center;background:#fff7ed;border:0.4mm solid #f3bd74;border-radius:2mm;align-self:start;padding:1.5mm 0}
.aligned-header aside strong{font-size:2mm}
.aligned-context{display:grid;grid-template-columns:1fr 1fr 1.5fr;height:17mm;box-sizing:border-box;background:#f8fafc;border:.4mm solid #c8d5e2;border-radius:1.6mm;margin:2mm 0 5mm;padding:3mm;gap:3mm}
.aligned-context small{display:block;font-size:2.2mm;color:#53657a;margin-bottom:1.1mm;font-weight:bold}
.aligned-context b{display:block;font-size:2.6mm;line-height:1.15}
.aligned-main{height:175mm;display:grid;grid-template-columns:91mm 89mm;gap:6mm;box-sizing:border-box}
.aligned-steps{display:flex;flex-direction:column;gap:4mm}
.aligned-step{box-sizing:border-box;position:relative;border:.4mm solid #c8d5e2;border-radius:2mm;padding:3.5mm 3mm 3mm 8mm;min-height:52mm;flex:1}
.aligned-number{position:absolute;left:-3mm;top:1mm;width:9mm;height:9mm;box-sizing:border-box;display:flex;align-items:center;justify-content:center;border:1mm solid var(--family);border-radius:50%;background:#fff;color:var(--family);font-size:4.5mm;font-weight:900}
.aligned-step h3{font-size:3.65mm;font-weight:900;line-height:1.15;margin:0 0 2mm}
.aligned-step p{font-size:2.85mm;line-height:1.32;margin:0;color:#53657a}
.aligned-see{display:block;font-size:2.1mm;color:var(--family);font-weight:bold;margin-top:1.2mm}
.aligned-figures{display:flex;flex-direction:column;justify-content:space-between;gap:2mm}
.aligned-figure{margin:0;position:relative}
.aligned-picture{height:var(--figure-height);max-width:89mm;display:flex;align-items:center;justify-content:center;border:.35mm solid #c8d5e2;border-radius:1.2mm;background:#fff;overflow:hidden}
.aligned-picture img{max-width:100%;max-height:100%;object-fit:contain;display:block}
.aligned-diagram{width:100%;height:100%}
.aligned-image-number{position:absolute;top:-1mm;left:-1mm;display:flex;align-items:center;justify-content:center;width:8.1mm;height:8.1mm;border:.75mm solid var(--family);border-radius:50%;background:rgba(255,255,255,.86);font-size:2.5mm;font-weight:800;color:var(--family);z-index:1}
.aligned-figure figcaption{font-size:2.2mm;line-height:1.2;color:#53657a;margin:1.2mm 0 0}
.aligned-help{box-sizing:border-box;margin-top:4mm;height:20mm;display:grid;grid-template-columns:1fr 1fr;column-gap:5mm;padding:2mm 3mm;background:#fff1f3;border:.4mm solid #ffd0d7;border-radius:2mm}
.aligned-help h2{grid-column:1 / -1;font-size:3mm;line-height:1;margin:0 0 1mm;color:#a91d32}
.aligned-help p{font-size:2.3mm;line-height:1.2;margin:0}
.aligned-done{font-size:2.4mm;line-height:1.2;margin:2mm 0;color:#138a43;background:#ecfdf3;padding:1.5mm 2mm;border-radius:1.4mm}
.aligned-done b{margin-right:2mm}
.aligned-related{display:flex;flex-wrap:wrap;gap:1mm 3mm;align-items:center;max-height:15mm}
.aligned-related small{font-size:2.1mm;font-weight:bold;color:#53657a}
.aligned-reference{display:inline-flex;align-items:center;gap:1mm;font-size:2.05mm;font-weight:bold;line-height:1.15;padding:.8mm 1.3mm;border:.25mm solid var(--ref);border-radius:1.5mm;color:var(--ref);background:var(--ref-fill)}
.aligned-reference b{border:.3mm solid var(--ref);border-radius:50%;background:#fff;min-width:3.5mm;height:3.5mm;font-size:1.2mm;display:flex;align-items:center;justify-content:center}
.aligned-page footer{display:block;position:absolute;bottom:4mm;left:12mm;font-size:1.8mm;color:#53657a}
.aligned-page:last-child{break-after:auto;page-break-after:auto}
`;

const browser = await chromium.launch({executablePath:resolveChromeExecutable(),headless:true});
const produced=[];
try {
    for (const g of inputs.guides.filter(g=>selected.includes(g.code))) {
        const rev=revisions[g.code];
        assert.ok(rev,`Missing revision ${g.code}`);
        const fullInput=path.join(round,g.input);
        assert.equal(hash(fullInput),g.inputSha256,`Input changed: ${g.code}`);
        assert.equal(hash(path.join(repoRoot,g.sourcePdfPath)),g.sha256,`Previous PDF changed: ${g.code}`);
        const stem=g.code==='CAT-05'?`cat-05-bestaande-catalogus-aanpassen-v${g.nextVersion}-draft`:path.basename(g.input,'.html').replace(new RegExp(`-v${g.version}-`),`-v${g.nextVersion}-`);
        if(retain) for(const ext of ['html','pdf']) assert.ok(!fs.existsSync(path.join(round,`${stem}.${ext}`)),`Frozen ${stem} already exists`);
        const replacements=[];
        for(const item of rev.images??[]) replacements.push({...item,...await crop(item.file,item.crop)});
        const extra=await supplement(g,rev);
        const page=await browser.newPage();
        await page.goto(pathToFileURL(fullInput).href);
        await page.emulateMedia({media:'print'});
        const footerMarkup=['AC-01','SC-01'].includes(g.code)?compactRelated(g.code):'';
        const changes=await page.evaluate(({g,patches,replacements,extra,css,footerMarkup})=>{
            const isSvg = Boolean(document.querySelector('.page > svg'));
            const texts=[...document.querySelectorAll(isSvg?'.page > svg text':'p,h1,h2,h3,h4,figcaption,li')];
            const norm=t=>t.replace(/\s+/g,' ').trim();
            const edited=[];
            const wrapText=(node,value,maxWidth,maxLines)=>{
                const spans=[...node.querySelectorAll('tspan')];
                const x=node.getAttribute('x');
                const lh=spans[1]?.getAttribute('dy')??String(Number(node.getAttribute('font-size'))*1.35);
                const measure=line=>{node.textContent=line;return node.getComputedTextLength();};
                const words=value.split(' ');const lines=[];let line='';
                for(const word of words){const trial=line?line+' '+word:word;if(measure(trial)>maxWidth&&line){lines.push(line);line=word;}else line=trial;}
                if(line)lines.push(line);
                node.innerHTML=lines.map((line,i)=>`<tspan x="${x}" dy="${i?lh:0}"></tspan>`).join('');
                [...node.children].forEach((s,i)=>s.textContent=lines[i]);
                return {lines:lines.length,maxLines,tooMany:lines.length>maxLines};
            };
            for(const [index,value] of Object.entries(patches)){
                const node=texts[Number(index)];
                if(!node)throw new Error(`Missing patch index ${g.code}:${index}`);
                const previous=norm(node.textContent);
                if(isSvg){
                    const box=node.getBBox();
                    const x=Number(node.getAttribute('x'));const size=Number(node.getAttribute('font-size'));
                    const maxLines=Math.max(1,node.querySelectorAll('tspan').length);
                    let maxWidth=box.width+1;
                    if(size===2.35 && x<80)maxWidth=Math.max(maxWidth,64);
                    if(node.dataset.role==='guide-label')maxWidth=Math.min(174-x,Math.max(maxWidth,78));
                    if(g.code==='SC-01'&&['55','57'].includes(index))maxWidth=40;
                    if(g.code==='CAT-01'&&index==='174')maxWidth=55;
                    const result=wrapText(node,value,maxWidth,maxLines);
                    node.dataset.alignedEdit=index;
                    edited.push({index,previous,value,original:{x:box.x,y:box.y,width:box.width,height:box.height},...result});
                }else{node.textContent=value;node.dataset.alignedEdit=index;edited.push({index,previous,value});}
            }
            for(const item of replacements){
                const image=document.querySelectorAll('.page > svg image')[item.index];
                if(!image)throw new Error(`Missing image ${g.code}:${item.index}`);
                const clip=image.getAttribute('clip-path');
                const id=clip?.match(/#([^)]*)/)?.[1];
                const rect=document.getElementById(id)?.querySelector('rect');
                if(!rect)throw new Error(`Missing crop frame ${id}`);
                for(const a of ['x','y','width','height'])image.setAttribute(a,rect.getAttribute(a));
                image.setAttribute('href',item.uri);image.setAttribute('preserveAspectRatio','xMidYMid meet');
                image.dataset.sourceId=item.file;
                for(const mark of document.querySelectorAll('[data-component="focus-mark"]'))if(mark.getAttribute('clip-path')===clip)mark.remove();
            }
            document.title=`${g.code} v${g.nextVersion} - niet geaccepteerd`;
            if(isSvg){
                for(const node of document.querySelectorAll('.page > svg text')){
                    const text=norm(node.textContent);
                    if(/^Draft v\d+/.test(text)){
                        node.textContent=`Concept v${g.nextVersion} 2026-10-01`;
                        node.setAttribute('y','17.4');node.setAttribute('fill','#b45309');
                        const status=document.createElementNS('http://www.w3.org/2000/svg','text');
                        for(const attr of ['x','font-family','fill','text-anchor'])status.setAttribute(attr,node.getAttribute(attr));
                        status.setAttribute('y','21.6');status.setAttribute('font-size','1.7');status.textContent='Niet geaccepteerd';node.after(status);
                        const svg=node.closest('svg');
                        for(const r of svg.querySelectorAll('rect'))if(Number(r.getAttribute('x'))>=160&&Number(r.getAttribute('y'))===12&&Number(r.getAttribute('width'))<=40){r.setAttribute('height','16');r.setAttribute('fill','#fff7ed');r.setAttribute('stroke','#f3bd74');}
                        if(![...svg.querySelectorAll('text')].some(t=>/^Pagina \d+ van/.test(norm(t.textContent))&&Number(t.getAttribute('y'))<40)){
                            const count=status.cloneNode(true);count.setAttribute('y','25.2');count.textContent=`Pagina 1 van ${g.pages+(extra?1:0)}`;status.after(count);
                        }
                    }
                    if(text.startsWith('Bron:')){node.textContent='Bronnen: bewaarde basis + dev.inbit | 2026-10-01 | Niet geaccepteerd';node.setAttribute('font-size','1.7');}
                    if(/^Pagina \d+ van \d+$/.test(text)){node.textContent=text.replace(/van \d+$/,`van ${g.pages+(extra?1:0)}`);if(Number(node.getAttribute('y'))<40){node.setAttribute('y','25.2');node.setAttribute('font-size','1.7');}}
                    if(text==='CAT-05 Varianten en lifecycle beheren')node.textContent='CAT-05 Bestaande catalogus aanpassen';
                    if(text==='CAT-06 Catalogus controleren en bronnen')node.textContent='CAT-06 Productgegevens controleren';
                }
                if(footerMarkup){
                    const svg=document.querySelector('.page > svg');
                    const range=g.code==='SC-01'?[284,293]:[265,276];
                    for(const n of [...svg.children]){
                        if(!['text','rect','circle','g'].includes(n.tagName))continue;
                        const b=n.getBBox();
                        if(b.x<170&&b.y>=range[0]&&b.y+b.height<=range[1]+.2)n.remove();
                    }
                    svg.insertAdjacentHTML('beforeend',footerMarkup);
                    const chipBaseY=g.code==='SC-01'?285:268;
                    let chipX=38,chipY=chipBaseY;
                    for(const chip of svg.querySelectorAll('[data-component="guide-chip"]')){
                        const rect=chip.querySelector('rect'),label=chip.querySelector('[data-role="guide-label"]');
                        if(!rect||!label||Number(rect.getAttribute('y'))!==chipBaseY)continue;
                        const width=label.getComputedTextLength()+12.5;
                        if(chipX+width>174){chipX=38;chipY+=8;}
                        const dx=chipX-Number(rect.getAttribute('x')),dy=chipY-Number(rect.getAttribute('y'));
                        rect.setAttribute('width',width);chip.setAttribute('transform',`translate(${dx} ${dy})`);
                        chipX+=width+2;
                    }
                }
            }else{
                [...document.querySelectorAll('.version')].forEach((node,i)=>{node.innerHTML=`Concept v${g.nextVersion}<br>2026-10-01<small>Niet geaccepteerd<br>${i+1} van ${g.pages+(extra?1:0)}</small>`;node.style.background='#fff7ed';node.style.borderColor='#f3bd74';node.style.color='#b45309';});
                for(const node of document.querySelectorAll('footer *'))if(node.children.length===0&&node.textContent.trim().startsWith('Bron'))node.textContent='Bronnen: bewaarde basis + dev.inbit | 2026-10-01 | Niet geaccepteerd';
                if(g.code==='AST-04')document.querySelector('.context strong').textContent='Refurbisher';
                if(g.code==='AST-03')document.querySelector('.context strong').textContent='Senior / Supervisor';
            }
            const style=document.createElement('style');style.textContent=css;document.head.append(style);
            if(extra)document.body.insertAdjacentHTML('beforeend',extra);
            return {isSvg,edited};
        },{g,patches:rev.patches,replacements,extra,css,footerMarkup});
        await page.evaluate(()=>document.fonts.ready);
        const qa=await page.evaluate(()=>{
            const pages=[...document.body.children].filter(n=>n.matches('.page,.guide-page'));
            const outside=(a,b)=>a.left<b.left-1.8||a.right>b.right+1.8||a.top<b.top-1.8||a.bottom>b.bottom+1.8;
            const violations=[];
            for(const [i,p] of pages.entries()){
                const pb=p.getBoundingClientRect();
                const nodes=p.querySelectorAll('text,p,h1,h2,h3,h4,figcaption,.context,.version,.guide-chip,.aligned-reference,.aligned-number,.aligned-picture');
                for(const n of nodes){
                    if(n.closest('defs'))continue;
                    const b=n.getBoundingClientRect();
                    if(outside(b,pb))violations.push({page:i+1,type:'page',text:n.textContent.slice(0,100)});
                    const card=n.closest('.aligned-step,.aligned-help');
                    if(card&&!n.matches('.aligned-number')&&outside(b,card.getBoundingClientRect()))violations.push({page:i+1,type:'card',text:n.textContent.slice(0,100)});
                }
                if(p.matches('.aligned-page')){
                    const main=p.querySelector('main').getBoundingClientRect();
                    for(const n of p.querySelectorAll('.aligned-figure,.aligned-step'))if(outside(n.getBoundingClientRect(),main))violations.push({page:i+1,type:'main',text:n.textContent.slice(0,100)});
                    const footer=p.querySelector('footer').getBoundingClientRect();
                    if(p.querySelector('nav').getBoundingClientRect().bottom>footer.top-2)violations.push({page:i+1,type:'footer',text:'Related guides overlap footer'});
                }
            }
            return {pages:pages.length,images:document.querySelectorAll('image,img').length,violations,text:document.body.innerText};
        });
        fs.writeFileSync(path.join(output,`${g.code}-geometry.json`),JSON.stringify({changes,qa},null,2));
        fs.writeFileSync(path.join(output,`${stem}.html`),await page.content());
        await page.pdf({path:path.join(output,`${stem}.pdf`),preferCSSPageSize:true,printBackground:true});
        await page.close();
        const pdf=path.join(output,`${stem}.pdf`);
        const info=spawnSync(resolveCommand('GUIDE_PDFINFO_PATH','pdfinfo'),[pdf],{encoding:'utf8',windowsHide:true});
        assert.equal(info.status,0,info.stderr);
        const count=Number(info.stdout.match(/^Pages:\s+(\d+)/m)?.[1]);
        assert.equal(count,g.pages+(rev.extra?1:0),`Wrong PDF pages ${g.code}`);
        const size=info.stdout.match(/Page size:\s+([\d.]+) x ([\d.]+) pts \(A4\)/);
        assert.ok(size&&Math.abs(Number(size[1])-595.276)<1&&Math.abs(Number(size[2])-841.890)<1,'Expected A4 size within Chromium rounding.');
        const rasters=path.join(output,g.code);fs.mkdirSync(rasters,{recursive:true});
        const render=spawnSync(resolveCommand('GUIDE_PDFTOPPM_PATH','pdftoppm'),['-png','-r','144',pdf,path.join(rasters,'page')],{encoding:'utf8',windowsHide:true});
        assert.equal(render.status,0,render.stderr);
        const imgs=fs.readdirSync(rasters).filter(f=>/^page-\d+\.png$/.test(f)).sort((a,b)=>a.localeCompare(b,undefined,{numeric:true}));
        const cols=Math.min(3,imgs.length);const composites=[];
        for(const [i,img] of imgs.entries()) composites.push({input:await sharp(path.join(rasters,img)).resize({width:420,height:594,fit:'contain'}).png().toBuffer(),left:(i%cols)*430,top:Math.floor(i/cols)*604});
        await sharp({create:{width:cols*430,height:Math.ceil(imgs.length/cols)*604,channels:3,background:'#cbd5e1'}}).composite(composites).png().toFile(path.join(output,`${g.code}-contact.png`));
        const item={code:g.code,title:GUIDE_REGISTRY[g.code].title,version:g.nextVersion,status:'Working draft - not accepted',pages:count,file:`${stem}.pdf`,bytes:fs.statSync(pdf).size,sha256:hash(pdf),htmlSha256:hash(path.join(output,`${stem}.html`)),previous:{file:g.sourcePdfPath,version:g.version,sha256:g.sha256,pages:g.pages},input:{file:g.input,sha256:g.inputSha256},delta:rev.delta,patches:changes.edited,images:rev.images??[],newPage:rev.extra??null,qa:{...qa,text:undefined},lineCapacityIssues:changes.edited.filter(e=>e.tooMany)};
        produced.push(item);
        console.log(JSON.stringify({code:g.code,pages:count,geometry:qa.violations.length,lineCapacity:item.lineCapacityIssues.length,file:pdf}));
    }
}finally{await browser.close();}

const shared=['scripts/manuals/lib/guide-system.mjs','scripts/manuals/lib/guide-paths.mjs'].map(file=>({file,sha256:hash(path.join(repoRoot,file))}));
const manifest={date:'2026-10-01',status:'Working drafts - not accepted',acceptedSelectionChanged:false,evidenceContext:'Local dev.inbit; source worktree c539b9cd18; old embedded evidence retained where controls are unchanged.',generator:{file:'scripts/manuals/generate-aligned-guides-2026-10-01.mjs',sha256:hash(path.join(repoRoot,'scripts/manuals/generate-aligned-guides-2026-10-01.mjs'))},content:{file:'scripts/manuals/aligned-guide-content-2026-10-01.mjs',sha256:hash(path.join(repoRoot,'scripts/manuals/aligned-guide-content-2026-10-01.mjs'))},shared,evidence,guides:produced};
fs.writeFileSync(path.join(output,'manifest.json'),JSON.stringify(manifest,null,2)+'\n');
if(retain){
    assert.equal(produced.length,19);
    assert.ok(produced.every(g=>g.qa.violations.length===0&&g.lineCapacityIssues.length===0),'Fix geometry and line capacity before retaining.');
    assert.ok(!fs.existsSync(path.join(round,'manifest.json')),'Frozen manifest already exists');
    for(const g of produced){for(const ext of ['pdf','html']){const name=g.file.replace(/\.pdf$/,`.${ext}`);fs.copyFileSync(path.join(output,name),path.join(round,name),fs.constants.COPYFILE_EXCL);}fs.copyFileSync(path.join(output,g.file),path.join(repoPdfOutputRoot,g.file),fs.constants.COPYFILE_EXCL);}
    fs.copyFileSync(path.join(output,'manifest.json'),path.join(round,'manifest.json'),fs.constants.COPYFILE_EXCL);
}
console.log(JSON.stringify({guides:produced.length,pages:produced.reduce((n,g)=>n+g.pages,0),retained:retain}));
