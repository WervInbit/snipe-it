import assert from 'node:assert/strict';
import crypto from 'node:crypto';
import fs from 'node:fs';
import path from 'node:path';
import {spawnSync} from 'node:child_process';
import {pathToFileURL} from 'node:url';
import {repoRoot,repoPdfOutputRoot,loadGuideDependency,resolveChromeExecutable,resolveCommand} from './lib/guide-paths.mjs';
import {ownerCorrections,referenceData,applyOwnerCorrection} from './lib/owner-correction-patches.mjs';

const sha=data=>crypto.createHash('sha256').update(data).digest('hex');
const base=path.join(repoRoot,'output/manuals/proofs');
const root=path.join(base,'owner-corrections-2026-09-10');
const inputRoot=path.join(root,'inputs');fs.mkdirSync(inputRoot,{recursive:true});
const sources=Object.fromEntries(ownerCorrections.map(g=>[g.code,path.join(base,'guide-followups-2026-09-08',`${g.code}-v${g.old}`,g.file.replace(`v${g.version}`,`v${g.old}`).replace('.pdf','.html'))]));
sources['WF-01']=path.join(base,'WF-01-v12/WF-01-workflow-starten-v12-draft.html');
sources['CMP-04']=path.join(base,'2026-08-20-cold-start-rework/component/CMP-04-component-to-tray-v6-draft.html');
const requested=process.argv.slice(2).map(x=>x.toUpperCase());
const selected=requested.length?ownerCorrections.filter(g=>requested.includes(g.code)):ownerCorrections;
assert.equal(selected.length,requested.length||ownerCorrections.length);
const {chromium}=loadGuideDependency('playwright');
const browser=await chromium.launch({executablePath:resolveChromeExecutable(),headless:true});
try {
 for(const guide of selected) {
  assert.ok(!fs.existsSync(path.join(repoRoot,'resources/manuals/operator-guides/review-rounds/2026-09-10/owner-corrections',guide.file)),'This candidate is frozen. Make the next integer version for further visible changes.');
  assert.ok(!fs.existsSync(path.join(repoRoot,'resources/manuals/operator-guides/review-rounds/2026-09-10/owner-reference-followups',guide.file)),'This reference candidate is frozen. Make the next integer version.');
  const input=path.join(inputRoot,`${guide.code}-v${guide.old}.html`);
  if(!fs.existsSync(input))fs.copyFileSync(sources[guide.code],input,fs.constants.COPYFILE_EXCL);
  const page=await browser.newPage();await page.goto(pathToFileURL(input).href);await page.emulateMedia({media:'print'});await page.evaluate(()=>document.fonts.ready);
  const before=await page.evaluate(()=>({images:document.querySelectorAll('image,img').length,text:document.body.innerText}));
  const tray=path.join(repoRoot,'resources/manuals/operator-guides/evidence/CMP-INSTALL-RESULT-MOBILE-02.png');
  await page.evaluate(applyOwnerCorrection,{guide,refs:referenceData,trayImage:`data:image/png;base64,${fs.readFileSync(tray).toString('base64')}`});
  await page.evaluate(()=>document.fonts.ready);
  const inventory=await page.evaluate(()=>{
   const pages=[...document.querySelectorAll('.page,.guide-page')];
   return pages.map((p,i)=>({page:i+1,images:p.querySelectorAll('image,img').length,text:p.textContent,
    outside:[...p.querySelectorAll('svg > text')].filter(t=>{if(t.closest('svg').parentElement!==p)return false;const b=t.getBBox();return b.x<0||b.y<0||b.x+b.width>210.3||b.y+b.height>297.3;}).map(t=>t.textContent),
    overflow:[...p.querySelectorAll('.step,.help-tile,.route-item,.help-branch')].flatMap(card=>{const b=card.getBoundingClientRect();return [...card.querySelectorAll('p,h2,strong,.guide-chip,figcaption')].filter(n=>{const r=n.getBoundingClientRect();return r.width&&r.height&&(r.left<b.left-1||r.right>b.right+1||r.top<b.top-1||r.bottom>b.bottom+1);}).map(n=>n.textContent);})}));
  });
  assert.equal(inventory.length,guide.pages);assert.deepEqual(inventory.flatMap(x=>x.outside),[],'Text outside A4');
  assert.deepEqual(inventory.flatMap(x=>x.overflow),[],'Content outside its instruction card');
  const focused=await page.evaluate((code)=>{
   const checks={};
   const bounds=n=>n.getBoundingClientRect();
   if(code==='SC-01')checks.badgeClearsInstruction=bounds(document.querySelector('[data-owner-check="sc01-badge"]')).top>bounds(document.querySelector('[data-owner-check="sc01-instruction"]')).bottom+2;
   if(code==='WF-01') {
    checks.sc01Styled=!!document.querySelector('.step-one [data-guide-code="SC-01"]');
    checks.helpClear=bounds(document.querySelector('.wf01-main')).bottom<=bounds([...document.querySelectorAll('h2')].find(n=>n.textContent==='Hulp bij workflow starten')).top;
    checks.profile2A=[...document.querySelectorAll('figure')].some(f=>f.querySelector('.image-badge')?.textContent==='2A'&&f.closest('.step')?.textContent.includes('Kies het workflowprofiel'));
    checks.start3A=[...document.querySelectorAll('figure')].some(f=>f.querySelector('.image-badge')?.textContent==='3A'&&f.closest('.step')?.textContent.includes('Start de workflow eenmaal'));
   }
   if(code==='AST-02')checks.routesStyled=['CMP-01','CMP-02','CMP-04','WF-02','HELP-01','SC-01','AST-03'].every(c=>document.querySelector(`.help-section [data-guide-code="${c}"]`));
   if(code==='CMP-02')for(const [label,x,y] of [['2A',35.06,292.38],['2B',226.46,345.10]]){const n=document.querySelector(`[data-owner-check="radio-${label}"]`);checks[label]=Number(n.getAttribute('cx'))===x&&Number(n.getAttribute('cy'))===y;}
   if(code==='CMP-04'){const n=document.querySelector('[data-owner-check="complete-tray-button"]');const v=n.closest('svg').viewBox.baseVal;checks.completeButton=+n.getAttribute('x')>=v.x&&+n.getAttribute('y')>=v.y&&+n.getAttribute('x')+ +n.getAttribute('width')<=v.x+v.width&&+n.getAttribute('y')+ +n.getAttribute('height')<=v.y+v.height;checks.explicitCrop=!!n.closest('svg').querySelector('image[clip-path]');}
   if(code==='USR-04'){checks.singleTask=!/check.?in|verwijderen|herstellen|licenties|toegewezen/i.test(document.body.innerText)&&!/\bOF\b/.test(document.body.innerText);checks.retainedEvidence=document.querySelectorAll('[data-component="preserved-evidence"]').length===4;}
   if(code==='CAT-00')checks.attachedArrow=document.querySelector('[data-owner-check="asset-connector"]').getAttribute('y2')==='145';
   if(code==='CAT-01')checks.routes=['A - Code bestaat: pagina 5, stap 7.','B - Code ontbreekt: pagina 4, stap 5.','C - Basismodel ontbreekt: pagina 3, stap 4.'].every(s=>document.body.textContent.includes(s));
   if(code==='USR-01'||code==='USR-02')checks.usr04Scope=!document.body.textContent.includes('Gebruiker uitschakelen of herstellen')&&document.body.textContent.includes('USR-04 Gebruiker uitschakelen');
   return checks;
  },guide.code);
  assert.ok(Object.values(focused).every(Boolean),JSON.stringify(focused));
  const dir=path.join(root,`${guide.code}-v${guide.version}`);fs.mkdirSync(dir,{recursive:true});
  const html=path.join(dir,guide.file.replace('.pdf','.html'));fs.writeFileSync(html,await page.content());
  const pdf=path.join(repoPdfOutputRoot,guide.file);await page.pdf({path:pdf,preferCSSPageSize:true,printBackground:true});
  const result=spawnSync(resolveCommand('GUIDE_PDFTOPPM_PATH','pdftoppm'),['-png','-r','144',pdf,path.join(dir,'page')],{encoding:'utf8',windowsHide:true});assert.equal(result.status,0,result.stderr);
  fs.writeFileSync(path.join(dir,'validation.json'),JSON.stringify({...guide,input:path.relative(repoRoot,input).replaceAll('\\','/'),inputSha256:sha(fs.readFileSync(input)),sha256:sha(fs.readFileSync(pdf)),bytes:fs.statSync(pdf).size,before,inventory,focused},null,2)+'\n');
  console.log(JSON.stringify({code:guide.code,version:guide.version,pages:inventory.length,images:inventory.map(x=>x.images),overflow:inventory.flatMap(x=>x.overflow)}));
  await page.close();
 }
} finally {await browser.close();}
