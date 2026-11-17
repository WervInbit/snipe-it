import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import crypto from 'node:crypto';
import {spawnSync} from 'node:child_process';
import {pathToFileURL} from 'node:url';
import {SvgGuideDocument,GUIDE_TOKENS,GUIDE_FAMILIES,guideReference,drawContextStrip,drawCompletionRow,inspectRenderedGuideComponents} from './lib/guide-system.mjs';
import {repoRoot,repoPdfOutputRoot,loadGuideDependency,browserLaunchOptions} from './lib/guide-paths.mjs';

assert.equal(process.argv.length,2,'This dated generator accepts no arguments.');
const round=path.join(repoRoot,'resources/manuals/operator-guides/review-rounds/2026-10-27-navigation-and-space');
assert.ok(!fs.existsSync(path.join(round,'manifest.sha256')),'Sealed round: create a new revision.');
const old=path.join(repoRoot,'resources/manuals/operator-guides/review-rounds/2026-10-20-remaining-guides');
const proof=path.join(repoRoot,'output/manuals/proofs/navigation-and-space-2026-10-27');
const read=p=>JSON.parse(fs.readFileSync(p,'utf8').replace(/^\uFEFF/,''));
const hash=p=>crypto.createHash('sha256').update(fs.readFileSync(p)).digest('hex');
const rel=p=>path.relative(repoRoot,p).replaceAll('\\','/');
const sharp=loadGuideDependency('sharp'),{chromium}=loadGuideDependency('playwright');
const images={},sources=[],figures=[];const C=GUIDE_TOKENS.colors;
const state=n=>read(path.join(round,'evidence',n+'.json'));
const element=(n,p)=>{const e=state(n).elements.find(p);assert.ok(e,n+': missing target');return e;};
const group=(n,prefix)=>element(n,e=>e.tag==='DIV'&&e.classes.includes('form-group')&&e.text.startsWith(prefix)).bounds;
function union(boxes,pad=4){const x=Math.max(0,Math.min(...boxes.map(b=>b.x))-pad),y=Math.max(0,Math.min(...boxes.map(b=>b.y))-pad);return{x,y,w:Math.max(...boxes.map(b=>b.x+b.w))+pad-x,h:Math.max(...boxes.map(b=>b.y+b.h))+pad-y};}
async function crop(key,name,bounds,job,marks=[]){
 const source=path.join(round,'evidence',name+'.png'),s=state(name),meta=await sharp(source).metadata();
 assert.equal(s.locale,'nl-NL');const b={x:Math.floor(bounds.x*s.viewport.dpr),y:Math.floor(bounds.y*s.viewport.dpr),w:Math.ceil(bounds.w*s.viewport.dpr),h:Math.ceil(bounds.h*s.viewport.dpr)};
 assert.ok(b.x>=0&&b.y>=0&&b.x+b.w<=meta.width&&b.y+b.h<=meta.height,key+': source crop');
 const bytes=await sharp(source).extract({left:b.x,top:b.y,width:b.w,height:b.h}).png().toBuffer();
 const file=path.join(round,'crops',key+'.png');fs.writeFileSync(file,bytes);images[key]={href:'data:image/png;base64,'+bytes.toString('base64'),width:b.w,height:b.h};
 const converted=marks.map(m=>({...m,x:m.x*s.viewport.dpr-b.x,y:m.y*s.viewport.dpr-b.y,w:m.w*s.viewport.dpr,h:m.h*s.viewport.dpr,padding:(m.padding??3)*s.viewport.dpr}));
 sources.push({key,source:rel(source),sourceSha256:hash(source),crop:rel(file),cropSha256:hash(file),cropPixels:b,sourcePixels:{width:meta.width,height:meta.height},locale:s.locale,role:s.notes.actor,job,marks:converted});
}
async function reuse(key,file,job,marks=[]){const bytes=fs.readFileSync(file),m=await sharp(bytes).metadata();images[key]={href:`data:image/${m.format==='jpeg'?'jpeg':'png'};base64,${bytes.toString('base64')}`,width:m.width,height:m.height};sources.push({key,source:rel(file),sourceSha256:hash(file),job,sourcePixels:{width:m.width,height:m.height},marks,retained:true});}

const profile=state('AC-MENU').profileIcons.find(e=>e.tag==='IMG');assert.ok(profile,'Measured profile icon is required.');
const passwordLink=element('AC-MENU',e=>e.tag==='A'&&e.text.trim()==='Wachtwoord wijzigen');
await crop('ac-menu','AC-MENU',{x:173,y:62,w:242,h:184},'Locate the profile icon and Wachtwoord wijzigen in the real account menu.',[
 {...profile.bounds,target:'Profielicoon',shape:'ellipse',padding:4,reason:'Explicit owner correction; small unlabeled navigation icon.'},
 {...passwordLink.bounds,target:'Wachtwoord wijzigen',shape:'rect',padding:2,reason:'Explicit owner correction; distinguish the menu action.'}]);
await crop('ac-form','AC-PASSWORD',union([group('AC-PASSWORD','Huidig wachtwoord'),...state('AC-PASSWORD').elements.filter(e=>e.tag==='DIV'&&e.classes.includes('form-group')&&e.text.startsWith('Nieuw wachtwoord')).map(e=>e.bounds)],4),'Read all three password fields and the actual rule without outlining the form.');
await crop('ac-save','AC-PASSWORD',element('AC-PASSWORD',e=>e.tag==='DIV'&&e.classes.includes('box-footer')).bounds,'Recognize the real Opslaan control at the end of the password form.');
{
 const source=path.join(old,'evidence/AC-OWN-SAVED.png'),file=path.join(round,'crops/ac-success.png'),b={x:97,y:156,w:410,h:76};
 const bytes=await sharp(source).extract({left:b.x,top:b.y,width:b.w,height:b.h}).png().toBuffer();fs.writeFileSync(file,bytes);
 images['ac-success']={href:'data:image/png;base64,'+bytes.toString('base64'),width:b.w,height:b.h};
 sources.push({key:'ac-success',source:rel(source),sourceSha256:hash(source),crop:rel(file),cropSha256:hash(file),cropPixels:b,sourcePixels:{width:1920,height:1500},locale:'nl-NL',role:'Refurbisher',marks:[],retained:true,job:'Read the complete genuine anonymous saved-password message at useful scale; unrelated empty bar width is cropped. Different owned account trial.'});
}

const dashLink=element('AST-DASH',e=>e.tag==='A'&&e.href==='https://dev.inbit/hardware'&&e.text.includes('alles weergeven'));
const dashHeading=element('AST-DASH',e=>e.tag==='H1');
await crop('ast-dashboard','AST-DASH',union([dashLink.bounds,dashHeading.bounds],5),'Locate Apparaten / alles weergeven under the Dashboard heading.');
const search=element('AST-SEARCH-EMPTY',e=>e.tag==='INPUT'&&e.value==='GUIDE271027A');
const plus=element('AST-SEARCH-EMPTY',e=>e.tag==='BUTTON'&&e.name==='btnAdd');
await crop('ast-search','AST-SEARCH-EMPTY',union([search.bounds,plus.bounds],8),'Read the applied asset-list search and locate the creation plus in the same toolbar.',[
 {...search.bounds,target:'Zoeken in apparatenlijst',shape:'rect',padding:3,reason:'Distinguish this list search from the global asset-tag lookup.'},
 {...plus.bounds,target:'Nieuwe asset via plus',shape:'ellipse',padding:3,reason:'Locate the small creation action among competing toolbar icons.'}]);
await crop('ast-identity','AST-CREATE-MOBILE',union([group('AST-CREATE-MOBILE','Assettag'),group('AST-CREATE-MOBILE','Serienummer')],4),'Read the complete proposed tag and physical serial fields, Unlock and Aa.');
await crop('ast-model','AST-CREATE-MOBILE',union([group('AST-CREATE-MOBILE','Categorie'),group('AST-CREATE-MOBILE','Model\n')],4),'Read category and the full selected manufacturer model code.');
const statusSelect=element('AST-CREATE-DESKTOP',e=>e.id==='status_select_id').bounds,notes=element('AST-CREATE-DESKTOP',e=>e.id==='status_change_note').bounds;
await crop('ast-status','AST-CREATE-DESKTOP',{x:statusSelect.x-5,y:statusSelect.y-5,w:statusSelect.w+10,h:notes.y+notes.h-statusSelect.y+10},'Read the status selector and whole empty optional note field; no older helper text.');
const save=element('AST-CREATE-MOBILE',e=>e.tag==='BUTTON'&&e.name==='submit').bounds;
await crop('ast-save','AST-CREATE-MOBILE',union([save],7),'Recognize the actual upper-form Opslaan button.');
const saved=state('AST-SAVED-DESKTOP'),row=saved.elements.find(e=>e.tag==='TR').bounds;
await crop('ast-saved','AST-SAVED-DESKTOP',{x:row.x-3,y:row.y-38,w:row.w+6,h:row.h+43},'Read the same saved tag, serial and model, and the Asset openen action.',[
 {...element('AST-SAVED-DESKTOP',e=>e.tag==='A'&&e.text.trim()==='Asset openen'&&e.bounds.y<row.y+row.h).bounds,target:'Asset openen',shape:'rect',padding:2,reason:'Distinguish opening the record from the adjacent quick print.'}]);
const template=element('AST-ASSET',e=>e.tag==='LABEL'&&e.text==='QR Label Template').bounds,print=element('AST-ASSET',e=>e.id==='qr-server-print-button').bounds;
await crop('ast-print','AST-ASSET',{x:50,y:template.y-4,w:315,h:print.y+print.h-template.y+10},'Read template, loaded print size, printer selection and Print QR label together.');
await crop('ast-preview','AST-ASSET',{x:109,y:791,w:197,h:197},'Recognize the full actual QR preview including tag/serial and quiet zone.');
const oldManifest=read(path.join(old,'authoring-manifest.json')),photo=oldManifest.sources.find(s=>s.key==='labels');
await reuse('placement',path.join(repoRoot,photo.source),'Orient the full physical underside and locate the separate example label.',photo.marks);

function text(d,x,y,v,o={}){d.text(x,y,v,{size:2.75,lh:3.85,...o});}
function page(code,v,n,total,purpose,need,previous=false){
 const d=new SvgGuideDocument(images,`${code}-v${v}-p${n}`),p=GUIDE_FAMILIES[code.split('-')[0]];
 d.raw('<g id="header" data-editable-group="Header">');d.rect(12,13,2,16,p.color);text(d,18,22,code+' '+guideReference(code).title,{size:code==='AC-02'?6.1:5.5,weight:900});text(d,18,29.5,purpose,{size:2.8,fill:C.muted});d.rect(164,10,34,13,p.fill,p.color,.45,1.4);d.centeredText(181,14.6,'Concept v'+v,{size:2.5,weight:800,fill:p.color});d.centeredText(181,19.5,`27-10-2026 | ${n}/${total}`,{size:2.15,fill:p.color});d.raw('</g>');
 d.raw('<g id="context" data-editable-group="Context">');drawContextStrip(d,[{label:'Rol',value:code==='AC-02'?'Iedereen':'Supervisor'},{label:'Nodig',value:need},previous?{label:'Vervolg',value:'Dezelfde taak; vorige zijde'}:{label:'Vooraf',guideCode:'AC-01'}]);d.raw('</g>');
 text(d,12,295,`Inbit Snipe-IT | ${code} v${v} | 27-10-2026`,{size:1.65,fill:C.muted});text(d,198,295,`${n}/${total} | https://snipe.inbit/`,{size:1.65,anchor:'end',fill:C.muted});return d;
}
function step(d,n,y,h,title){const p=GUIDE_FAMILIES[d.idPrefix.split('-')[0]];d.raw(`<g id="step-${n}" data-editable-group="Stap ${n}" data-component="task-step" data-number="${n}">`);d.rect(12,y,186,h,'white',C.line,.45,2);d.stepBadge(21,y+9,n,p.color);text(d,32,y+10.5,title,{size:3.5,weight:900,fill:p.color});}
function end(d){d.raw('</g>');}
function fig(d,key,x,y,w,label,o={}){
 const im=images[key],h=w*im.height/im.width,b={x,y,w,h};d.raw(`<g id="figure-${label}" data-editable-group="Beeld ${label}" data-source-key="${key}">`);const start=d.parts.length,place=d.image(key,b,null,{fit:'contain'});for(let i=start;i<d.parts.length;i++)if(d.parts[i].startsWith('<image '))d.parts[i]=d.parts[i].replace('<image ',`<image data-recognition-source="${key}" `);d.raw('</g>');
 d.raw(`<g id="focus-${label}" data-editable-group="Focus ${label}">`);for(const m of sources.find(s=>s.key===key).marks??[])d.focusMark(place,m);d.raw('</g>');
 d.imageBadge(o.badgeX??x+2,o.badgeY??y+1,label,GUIDE_FAMILIES[d.idPrefix.split('-')[0]].color);figures.push({page:d.idPrefix,key,label,frameMm:b,actualSourcePixelsPerMm:im.width/w});return b;
}
function tip(d,x,y,w,h,title,lines){d.raw('<g data-editable-group="Tip en hulp">');d.rect(x,y,w,h,C.faint,C.line,.35,1);text(d,x+3,y+5,title,{size:2.6,weight:800});text(d,x+3,y+9.3,lines,{size:2.45,lh:3.35});d.raw('</g>');}
function done(d,line,y=282,h=10){d.raw('<g id="completion" data-editable-group="Klaar als">');drawCompletionRow(d,line,{x:12,y,width:186,height:h});d.raw('</g>');}
const guides=[];
let d=page('AC-02',7,1,1,'Wijzig het wachtwoord van je lokale account en controleer de opslag.','Huidig of tijdelijk wachtwoord');
step(d,1,64,63,'Open je accountmenu');text(d,16,86,['Klik rechtsboven op het profielicoon.','Kies daarna Wachtwoord wijzigen (1A).','Je opent het formulier van je eigen account.'],{size:2.9,lh:4.3});fig(d,'ac-menu',118,69,73,'1A',{badgeX:119,badgeY:69});end(d);
step(d,2,132,80,'Vul huidig, nieuw en bevestiging in');text(d,16,155,['Boven: je huidige of tijdelijke wachtwoord.','Midden: kies je nieuwe eigen wachtwoord.','Onder: herhaal dat nieuwe wachtwoord.','De onderste twee velden heten allebei','Nieuw wachtwoord. Hun positie verschilt.'],{size:2.8,lh:4.1});text(d,16,183,['Volg de regel die onder het veld staat.','Gebruik een eigen wachtwoord.','Deel het met niemand.'],{size:2.8,lh:4.1});fig(d,'ac-form',96,147,98,'2A',{badgeX:192,badgeY:147});end(d);
step(d,3,217,42,'Sla op en lees de melding');text(d,16,240,['Klik op Opslaan (3A).','Gelukt (3B)? Gebruik je nieuwe wachtwoord.','Foutmelding? Corrigeer het genoemde veld.','Andere apparaten moeten opnieuw inloggen.'],{size:2.65,lh:3.8});fig(d,'ac-save',101,227,93,'3A');fig(d,'ac-success',120,244,74,'3B',{badgeX:120,badgeY:244});end(d);
text(d,16,271,'Huidig wachtwoord kwijt? Vraag een Admin:',{size:2.7,fill:C.muted});d.guideChip(111,264,87,guideReference('USR-03'));done(d,'De wijziging is opgeslagen; alleen jij kent je eigen wachtwoord.',280,11);guides.push({code:'AC-02',version:7,stem:'ac-02-eigen-wachtwoord-wijzigen-v7-draft',pages:[d]});

let a=page('AST-03',18,1,2,'Registreer het juiste apparaat eenmaal en maak daarna zijn label.','Apparaat + fabrikantlabel');
step(a,1,60,63,'Zoek eerst; maak alleen aan als het ontbreekt');text(a,16,81,['Dashboard: kies Apparaten > alles weergeven (1A).','Zoek met het serienummer (S/N) of de bestaande tag.','Druk op Enter. Vergelijk het resultaat met het apparaat.'],{size:2.65,lh:3.8});fig(a,'ast-dashboard',156,66,35,'1A',{badgeX:190,badgeY:67});fig(a,'ast-search',16,96,130,'1B');text(a,16,115,['Gevonden? Klik op de apparaatnaam; controleer de identiteit bij stap 5.','Niet gevonden? Klik op + boven de lijst (1B) voor Nieuwe aanmaken.'],{size:2.6,lh:3.65});end(a);
step(a,2,127,47,'Neem de tag en fysieke S/N over');text(a,16,150,['Assettag is onze interne apparaatcode.','Behoud de voorgestelde code bovenaan.','Serienummer (S/N) komt van het fabrikantlabel.','Gebruik daar geen Product ID of P/N.'],{size:2.55,lh:3.65});text(a,16,166,['Unlock bewerkt de tag; normaal laat je die staan.','Tag en S/N worden hoofdletters; Aa bewaart kleine letters.'],{size:2.5,lh:3.6});fig(a,'ast-identity',114,140,79,'2A',{badgeX:192,badgeY:141});end(a);
step(a,3,178,44,'Kies categorie en de exacte fabrikantcode');text(a,16,199,['Kies het apparaattype, bijvoorbeeld Laptops.','Kies product en fabrikantcode bij Model.','Bij HP heet die code Product ID of P/N.','Neem ook het deel na # over, zoals #ABH.','Dat is de modelcode, niet de unieke S/N.'],{size:2.6,lh:3.45});text(a,16,216,['Code ontbreekt? Laat deze toevoegen met CAT-01.','Keer daarna terug naar deze stap.'],{size:2.35,lh:3.2,fill:C.orange});fig(a,'ast-model',114,190,79,'3A',{badgeX:192,badgeY:191});end(a);
step(a,4,226,43,'Kies status en sla eenmaal op');text(a,16,248,['Kies de werkstatus, hier Being Processed.','Notities zijn bij een nieuwe registratie optioneel.'],{size:2.6,lh:3.8});text(a,50,258,'Controleer alles; klik bovenaan op Opslaan (4B).',{size:2.25});text(a,50,265,'Dubbelmelding? Ga terug naar stap 1.',{size:2.25});fig(a,'ast-status',117,237,76,'4A',{badgeX:192,badgeY:238});fig(a,'ast-save',16,255,28,'4B',{badgeX:12,badgeY:258});end(a);
a.rect(12,273,186,8,C.catSoft,C.cat,.4,1.3);text(a,16,278.3,'Ga naar bladzijde 2: controleer het record, print, plaats en scan het label.',{size:2.65,weight:800,fill:C.cat});a.guideChip(12,284,86,guideReference('SC-01'));a.guideChip(101,284,97,guideReference('CAT-01'));
let b=page('AST-03',18,2,2,'Vervolg: controleer hetzelfde record en maak het fysieke label.','Asset + labelprinter',true);
step(b,5,60,41,'Open en controleer het juiste record');text(b,16,77,['Na aanmaken: vergelijk tag, S/N en model en kies Asset openen (5A).','Al geopend via stap 1? Controleer dezelfde gegevens; ga verder naar stap 6.'],{size:2.55,lh:3.5});fig(b,'ast-saved',16,83,177,'5A',{badgeX:192,badgeY:79});end(b);
step(b,6,105,78,'Kies formaat en printer; print eenmaal');text(b,16,130,['Vergelijk de QR-preview met tag en S/N (6B).','Kies bij QR Label Template het juiste label.','Controleer Afdrukformaat en het geladen papier.','Kies Printerlocatie en daarna Print QR label (6A).','De snelle knop in 5A gebruikt de standaardkeuzes.'],{size:2.65,lh:3.8});fig(b,'ast-print',113,118,80,'6A',{badgeX:192,badgeY:119});fig(b,'ast-preview',17,153,27,'6B',{badgeX:17,badgeY:151});tip(b,49,153,61,26,'Geen label of uitkomst onduidelijk?',['Controleer printer, papier en opdracht.','Vraag lokale ondersteuning.','Maak geen nieuwe asset.']);end(b);
step(b,7,187,59,'Plaats het label rechtsonder');text(b,16,210,['Leg de onderzijde met de voorkant naar je toe.','Plaats het label rechtsonder op een vlak stuk.','Bedek geen ventilatie, poort, schroef of','servicelabel. Druk het goed vast.'],{size:2.75,lh:4});fig(b,'placement',113,189,80,'7A',{badgeX:192,badgeY:190});text(b,16,237,'Plaatsingsvoorbeeld: de voorkant ligt naar je toe.',{size:2.1,fill:C.muted});end(b);
step(b,8,250,29,'Scan en vergelijk');text(b,16,270,['Scan het label; vergelijk tag, S/N en model.','Andere identiteit? Wijzig niets; vraag Supervisor.','Label kapot? Zoek handmatig op tag of S/N.'],{size:2.6,lh:3.3});b.guideChip(113,254,80,guideReference('SC-01'));end(b);done(b,'Het apparaat bestaat eenmaal; zijn label opent hetzelfde record.',282,10);guides.push({code:'AST-03',version:18,stem:'AST-03-asset-registreren-en-labelen-v18-draft',pages:[a,b]});

const htmlFor=svgs=>`<!doctype html><html lang="nl"><meta charset="utf-8"><title>Inbit handleidingen - 27-10-2026</title><style>@page{size:A4 portrait;margin:0}*{box-sizing:border-box}body{margin:0;background:#dce3eb}.page{width:210mm;height:297mm;background:white;margin:0 auto;break-after:page;overflow:hidden}.page:last-child{break-after:auto}.page svg{display:block;width:210mm;height:297mm}@media screen{.page{margin:12px auto;box-shadow:0 2px 8px #4566}}</style><body>${svgs.map(s=>`<section class="page">${s}</section>`).join('')}</body></html>`;
const browser=await chromium.launch(browserLaunchOptions({headless:true}));const qa=[];
try{
 for(const g of guides){
  const svgs=g.pages.map(d=>d.render()),html=path.join(round,g.stem+'.html'),pdf=path.join(round,g.stem+'.pdf');
  svgs.forEach((s,i)=>fs.writeFileSync(path.join(round,g.stem+`-page-${i+1}.svg`),s));fs.writeFileSync(html,htmlFor(svgs));
  const p=await browser.newPage();await p.goto(pathToFileURL(html).href);await p.evaluate(()=>document.fonts.ready);
  const components=await inspectRenderedGuideComponents(p);const boxes=await p.evaluate(()=>[...document.querySelectorAll('.page>svg')].flatMap((s,i)=>[...s.querySelectorAll('text')].map(n=>{const r=n.getBBox(),m=s.getCTM().inverse().multiply(n.getCTM()),points=[[r.x,r.y],[r.x+r.width,r.y],[r.x,r.y+r.height],[r.x+r.width,r.y+r.height]].map(([x,y])=>new DOMPoint(x,y).matrixTransform(m)),x=Math.min(...points.map(p=>p.x)),y=Math.min(...points.map(p=>p.y));return{page:i+1,text:n.textContent,x,y,w:Math.max(...points.map(p=>p.x))-x,h:Math.max(...points.map(p=>p.y))-y};})));
  const outside=boxes.filter(b=>b.x<0||b.y<0||b.x+b.w>210||b.y+b.h>297);assert.deepEqual(outside,[],g.code);qa.push({code:g.code,components,outside,textBoxes:boxes});await p.close();
  const result=spawnSync(process.execPath,[path.join(repoRoot,'scripts/manuals/render-guide-html.mjs'),'--html',html,'--pdf',pdf,'--pages',String(svgs.length),'--render-dir',path.join(proof,g.code),'--replace-output'],{encoding:'utf8',windowsHide:true,env:process.env});assert.equal(result.status,0,result.stderr||result.stdout);
  fs.copyFileSync(pdf,path.join(repoPdfOutputRoot,path.basename(pdf)));for(let i=1;i<=svgs.length;i++)fs.copyFileSync(path.join(proof,g.code,`page-${i}.png`),path.join(round,'renders',`${g.code}-${i}.png`));
  g.pdf=rel(pdf);g.html=rel(html);g.pageCount=svgs.length;g.sha256=hash(pdf);delete g.pages;console.log(JSON.stringify({code:g.code,pages:g.pageCount,pdf:g.pdf}));
 }
}finally{await browser.close();}
fs.writeFileSync(path.join(round,'qa/geometry-review.json'),JSON.stringify(qa,null,2));fs.writeFileSync(path.join(round,'authoring-manifest.json'),JSON.stringify({date:'2026-10-27',status:'Working drafts; not accepted',guides,sources,figures},null,2));
