import fs from 'node:fs';
import path from 'node:path';
import crypto from 'node:crypto';
import assert from 'node:assert/strict';
import {pathToFileURL} from 'node:url';
import {repoRoot, loadGuideDependency, browserLaunchOptions} from './lib/guide-paths.mjs';
import {guideChanges} from './lib/final-guide-changes-2026-11-04.mjs';
import {GUIDE_FAMILIES, inspectRenderedGuideComponents} from './lib/guide-system.mjs';

assert.equal(process.argv.length,2,'No arguments.');
const out = path.join(repoRoot,'output/manuals/final-guide-set-2026-11-04');
assert(!fs.existsSync(path.join(out,'manifest.sha256')),'Sealed release.');
const read=p=>JSON.parse(fs.readFileSync(p,'utf8'));
const inventory=read(path.join(out,'source/inventory.json'));
const assets=read(path.join(out,'source/assets.json'));
const baseline=read(path.join(out,'inputs/baseline-index.json'));
const xml=s=>String(s).replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;').replaceAll('"','&quot;');
const {chromium}=loadGuideDependency('playwright');
const browser=await chromium.launch(browserLaunchOptions({headless:true}));
const components=[], recognition=[];
try {
    const page=await browser.newPage({viewport:{width:1000,height:1500},deviceScaleFactor:1.5});
    for(const g of inventory.guides){
        const old=read(path.join(out,'source',g.code+'.baseline.json'));
        const doc=read(path.join(out,'source',g.code+'.json'));
        for(const p of old.pages){
            const dest=path.join(out,'inputs/baselines',`${g.code}-v${g.baselineVersion}-p${p.number}.png`);
            if(fs.existsSync(dest)) continue;
            const svg=fs.readFileSync(path.join(out,'inputs/baselines',`${g.code}-v${g.baselineVersion}-p${p.number}.svg`),'utf8');
            await page.setContent(`<html><meta charset="utf-8"><style>html,body{margin:0}svg{display:block;width:210mm;height:297mm}</style>${svg}</html>`);
            await page.evaluate(()=>document.fonts.ready); await page.locator('svg').screenshot({path:dest});
        }
        for(const p of doc.pages){
            const svg=fs.readFileSync(path.join(out,'source',`${g.code}-v${g.version}-p${p.number}.svg`),'utf8');
            await page.setContent(`<html><meta charset="utf-8"><style>html,body{margin:0}svg{width:210mm;height:297mm}</style>${svg}</html>`);
            await page.evaluate(()=>document.fonts.ready);
            try {const r=await inspectRenderedGuideComponents(page);components.push({code:g.code,page:p.number,errors:[],...r});}
            catch(e){components.push({code:g.code,page:p.number,errors:[e.message]});}
        }
        const effective=e=>{const a=assets[e.asset],b=e.fullImageBounds,c=e.clip,s=b.w/a.width;return {x:(c.x-b.x)/s,y:(c.y-b.y)/s,w:c.w/s,h:c.h/s,scale:s};};
        const current=doc.pages.flatMap(p=>p.elements.filter(e=>e.tag==='image').map(e=>({...e,page:p.number,effective:effective(e)})));
        for(const p of old.pages)for(const e of p.elements.filter(e=>e.tag==='image')){
            const b=effective(e);
            const matches=current.filter(n=>n.asset===e.asset && ['x','y','w','h'].every(k=>Math.abs(b[k]-n.effective[k])<1));
            recognition.push({code:g.code,baselinePage:p.number,key:e.provenance.key,baselinePixelsPerMm:1/b.scale,
                sameCrop:matches.length>0,scaleRatio:matches.length?Math.max(...matches.map(n=>n.effective.scale))/b.scale:null,
                note:matches.length?'Same visible pixels: compare physical target scale.':'Recomposed recognition job: inspect the changed crop and retained information.'});
        }
        console.log(g.code+' comparison and component checks');
    }
} finally {await browser.close();}
fs.writeFileSync(path.join(out,'qa/components.json'),JSON.stringify(components,null,2)+'\n');
fs.writeFileSync(path.join(out,'qa/recognition.json'),JSON.stringify(recognition,null,2)+'\n');
const briefs=inventory.guides.map(g=>({ ...g,...guideChanges[g.code]}));
fs.writeFileSync(path.join(out,'review/changes.json'),JSON.stringify(briefs,null,2)+'\n');

const status='Preparation source, not the native/PDF release. Desktop connection recovery, native edit/save/export proof and application-target reconciliation remain open.';
const cards=briefs.map(g=>`<article id="${g.code}" data-family="${g.code.split('-')[0]}" style="--family:${GUIDE_FAMILIES[g.code.split('-')[0]].color}">
<h2>${g.code} ${xml(g.title)} <small>v${g.baselineVersion} → v${g.version} · ${g.pages} A4</small></h2>
<p>${xml(g.outcome)}</p><ul>${g.changes.map(c=>'<li>'+xml(c)+'</li>').join('')}</ul>
<p><b>Preserved:</b> ${xml(g.preserved)}</p><p><b>Placement / tradeoff:</b> ${xml(g.tradeoff)}</p>
<nav><a href="../source/${g.code}-v${g.version}.html">New layout</a><a href="${g.code}.html">Equal-width comparison</a><a href="../inputs/baselines/${g.code}-v${g.baselineVersion}.pdf">Old PDF v${g.baselineVersion}</a><a href="../idml/${g.code}-v${g.version}.idml">Editable IDML preparation</a></nav>
<p class="pending">Native .af and native-exported PDF: pending.</p></article>`).join('\n');
const css=`:root{font:16px/1.5 system-ui;color:#172335;background:#e8edf2}body{margin:0}main{max-width:1300px;margin:auto;padding:24px}header,article,.notice{background:white;padding:22px;border-radius:8px;margin:18px 0}h1,h2{line-height:1.2}h2{border-left:5px solid var(--family);padding-left:12px;font-size:22px}small{font-size:14px;color:#53657a}nav{display:flex;gap:12px;flex-wrap:wrap}a{color:#174e9b}.notice,.pending{background:#fff7dd;padding:14px}article p{max-width:1100px}input,select{font:inherit;padding:8px}.compare{display:grid;grid-template-columns:1fr 1fr;gap:18px;align-items:start;margin:25px 0}.compare img{width:100%;display:block;background:white;border:1px solid #ccd4df}.label{padding:8px;background:white;font-weight:600}@media(max-width:850px){.compare{grid-template-columns:1fr}}`;
fs.writeFileSync(path.join(out,'review/index.html'),`<!doctype html><html lang="en"><meta charset="utf-8"><title>25 guides - preparation and changes</title><style>${css}</style><main><header><h1>25 guides: preparation and changes</h1><p>${inventory.totalPages} A4 pages. Same design direction, repaired task guidance and editable source.</p><p class="notice">${status}</p><p><a href="CHANGES.md">Complete change/reasoning list</a> · <a href="../EDITING-HANDOFF.md">Editing and native proof handoff</a></p><label>Find a guide <input id="search" type="search" placeholder="code or title"></label> <label>Family <select id="family"><option value="">All</option>${Object.keys(GUIDE_FAMILIES).map(f=>'<option>'+f+'</option>').join('')}</select></label></header>${cards}</main><script>function filter(){const q=document.querySelector('#search').value.toLowerCase(),f=document.querySelector('#family').value;document.querySelectorAll('article').forEach(a=>a.hidden=!(a.querySelector('h2').textContent.toLowerCase().includes(q)&&(!f||a.dataset.family===f)))}document.querySelector('#search').addEventListener('input',filter);document.querySelector('#family').addEventListener('change',filter)</script></html>`);
for(const g of briefs){
    const b=baseline.find(b=>b.code===g.code),n=Math.max(b.pages,g.pages);
    const pairs=Array.from({length:n},(_,i)=>`<section class="compare"><div><div class="label">Old v${g.baselineVersion} - page ${i+1}</div>${i<b.pages?`<a href="../inputs/baselines/${g.code}-v${g.baselineVersion}-p${i+1}.png"><img src="../inputs/baselines/${g.code}-v${g.baselineVersion}-p${i+1}.png" alt="Old page ${i+1}"></a>`:'<p>The old version had no second page.</p>'}</div><div><div class="label">Prepared v${g.version} - page ${i+1}</div>${i<g.pages?`<a href="../previews/${g.code}-${i+1}.png"><img src="../previews/${g.code}-${i+1}.png" alt="Prepared page ${i+1}"></a>`:'<p>No corresponding page.</p>'}</div></section>`).join('');
    fs.writeFileSync(path.join(out,'review',g.code+'.html'),`<!doctype html><html lang="en"><meta charset="utf-8"><title>${g.code} comparison</title><style>${css}</style><main><header><a href="index.html#${g.code}">All guides and changes</a><h1>${g.code} ${xml(g.title)}</h1><p class="notice">${status}</p><ul>${g.changes.map(c=>'<li>'+xml(c)+'</li>').join('')}</ul><p>Images have equal A4 width. Open either image for its full render. The native export still needs a separate comparison.</p></header>${pairs}</main></html>`);
}
const md=`# All25 Guide Preparation And Central Change List\n\nDate: 2026-11-04. ${status}\n\nThe owner authorized the complete final set and native Affinity files, then instructed continued preparation while they restore the failed desktop tool connection. No native .af or final native-exported PDF is claimed here.\n\n## Shared Requirements And Disposition\n\n- Exact existing access-group names, Dutch operator copy, genuine Dutch application/account evidence; no painted translations.\n- Recognizable navigation, real submission, stored-result checks and complete meaningful help. No automatic removal of hints.\n- Real sequences, explicit alternatives and independent route numbering. No routine STOP banners or invented future status/configuration.\n- Preserved concrete controls and actual target scale; selective focus only. AC-02 retains the owner's two requested marks.\n- Full original images, live text/paragraph styles, independent editable vectors and logical groups in the interchange source. Common header/footer objects are assigned to actual IDML masters.\n- Selected newer PDFs remain the exact comparison inputs. Historical rounds and acceptance/runtime selections are unchanged.\n\n## Release-Target Dependencies\n\nThe recorded production application requires creation notes conditionally; the owner requires registration notes to be optional, and root makes them nullable. Preserve the owner decision and resolve the target/application mismatch before claiming a production-current release. START-04 also owns exact workflow applicability/order, staff/QA responsibilities, account conventions and useful contacts. October20/27 captures are reused with their original provenance; no fresh live session, physical printing/component work or unfamiliar-reader trial occurred in this preparation.\n\n## Per-Guide Reasoning And Changes\n\n`+briefs.map(g=>`### ${g.code} ${g.title}: v${g.baselineVersion} -> v${g.version} (${g.pages} pages)\n\nReader outcome: ${g.outcome}\n\n${g.changes.map(c=>'- '+c).join('\n')}\n\nPreserved: ${g.preserved}\n\nPlacement and tradeoff: ${g.tradeoff}\n\n[Old PDF](../inputs/baselines/${g.code}-v${g.baselineVersion}.pdf) | [Prepared layout](../source/${g.code}-v${g.version}.html) | [Equal-width comparison](${g.code}.html) | [IDML preparation](../idml/${g.code}-v${g.version}.idml)\n`).join('\n')+
`\n## Verification Boundaries\n\nThe JSON evidence in qa records page overflow, text/image/text intersections, shared components and same-crop target-scale comparisons. These are artifact checks, not proof of instructional usability. The layout and words need whole-page editorial read-back; native import requires its own edit/save/reopen/relocation/export proof. Native PDFs must be exported from the exact delivered .af masters. The working package is not sealed as the final release.\n`;
fs.writeFileSync(path.join(out,'review/CHANGES.md'),md);
fs.writeFileSync(path.join(out,'source/REVISED-COPY.md'),briefs.map(g=>{const d=read(path.join(out,'source',g.code+'.json'));return '# '+g.code+' v'+g.version+'\n\n'+d.pages.map(p=>'## Page '+p.number+'\n\n'+p.elements.filter(e=>e.tag==='text').map(e=>e.lines.map(l=>l.text).join('\n')).join('\n\n')).join('\n\n');}).join('\n\n')+'\n');
const navigationBrowser=await chromium.launch(browserLaunchOptions({headless:true}));
try {
    const p=await navigationBrowser.newPage();
    await p.goto(pathToFileURL(path.join(out,'review/index.html')).href);
    assert.equal(await p.locator('article:visible').count(),25);
    await p.locator('#search').fill('AST-03'); assert.equal(await p.locator('article:visible').count(),1);
    await p.locator('#search').fill(''); await p.locator('#family').selectOption('USR'); assert.equal(await p.locator('article:visible').count(),5);
    const files=['index.html',...briefs.map(g=>g.code+'.html')]; let checked=0;
    for(const file of files)for(const match of fs.readFileSync(path.join(out,'review',file),'utf8').matchAll(/href="([^"#]+)(?:#[^"]*)?"/g)) {
        if(/^https?:/.test(match[1]))continue;
        assert(fs.existsSync(path.resolve(out,'review',match[1])),file+': '+match[1]); checked++;
    }
    fs.writeFileSync(path.join(out,'qa/review-navigation.json'),JSON.stringify({guides:25,search:'passed',familyFilter:'passed',localLinks:checked,missingLinks:0},null,2)+'\n');
} finally {await navigationBrowser.close();}
console.log(JSON.stringify({guides:briefs.length,pages:inventory.totalPages,componentFailures:components.filter(c=>c.errors.length).length,unchangedCrops:recognition.filter(r=>r.sameCrop).length}));
