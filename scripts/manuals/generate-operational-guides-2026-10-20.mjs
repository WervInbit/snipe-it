import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import crypto from 'node:crypto';
import { spawnSync } from 'node:child_process';
import { pathToFileURL } from 'node:url';
import { GUIDE_TOKENS, GUIDE_FAMILIES, SvgGuideDocument, guideReference, drawContextStrip, drawCompletionRow, layoutRelatedGuides, inspectRenderedGuideComponents } from './lib/guide-system.mjs';
import { repoRoot, repoPdfOutputRoot, evidencePath, loadGuideDependency, resolveChromeExecutable } from './lib/guide-paths.mjs';

const round = path.join(repoRoot, 'resources/manuals/operator-guides/review-rounds/2026-10-20-operational-guides');
const proof = path.join(repoRoot, 'output/manuals/proofs/operational-guides-2026-10-20');
assert.ok(!fs.existsSync(path.join(round, 'manifest.sha256')), 'Retained round: create a new version instead of replacing it.');
const sharp = loadGuideDependency('sharp');
const { chromium } = loadGuideDependency('playwright');
const C = GUIDE_TOKENS.colors;
const hash = p => crypto.createHash('sha256').update(fs.readFileSync(p)).digest('hex');
const rel = p => path.relative(repoRoot, p).replaceAll('\\', '/');
const images = {}, sources = [], figures = [];
fs.mkdirSync(path.join(round, 'crops'), { recursive: true });
fs.mkdirSync(path.join(round, 'inputs'), { recursive: true });
fs.mkdirSync(proof, { recursive: true });
const state = id => JSON.parse(fs.readFileSync(path.join(round, 'evidence', `${id}-NL-20261020.json`), 'utf8'));
const firstCard = id => state(id).elements.find(e => String(e.classes).split(' ')[0] === 'testing-card').bounds;
const padded = (b, n = 5) => [b.x-n,b.y-n,b.w+2*n,b.h+2*n];
async function crop(key, id, cssBounds, job, marks = [], extra = {}) {
    const file = path.join(round, 'evidence', `${id}-NL-20261020.png`);
    const bounds = cssBounds.map((n, i) => i < 2 ? Math.floor(n*2) : Math.ceil(n*2));
    const [left, top, width, height] = bounds;
    const meta = await sharp(file).metadata();
    assert.ok(left >= 0 && top >= 0 && left+width <= meta.width && top+height <= meta.height, `${key}: crop outside source`);
    const bytes = await sharp(file).extract({ left, top, width, height }).png().toBuffer();
    const output = path.join(round, 'crops', `${key}.png`); fs.writeFileSync(output,bytes);
    images[key] = { href:`data:image/png;base64,${bytes.toString('base64')}`, width, height };
    const localMarks = marks.map(m => ({...m,x:m.x*2-left,y:m.y*2-top,w:m.w*2,h:m.h*2,padding:(m.padding??3)*2}));
    sources.push({key,source:rel(file),sourceSha256:hash(file),sourcePixels:{width:meta.width,height:meta.height},cropPixels:{x:left,y:top,w:width,h:height},crop:rel(output),cropSha256:hash(output),job,marks:localMarks,locale:'nl-NL',role:'Refurbisher',asset:'GUIDE-20261020-01',...extra});
}
await crop('tabs','WF-ASSET',[12,330,391,60],'Locate Workflows among the complete asset tabs.',[{x:206,y:339,w:48,h:43,shape:'ellipse',padding:2,target:'Workflows tab, clipboard icon',reason:'Choose the clipboard among different unlabeled tab icons.'}]);
await crop('order','WF-ENTRY',[20,390,375,239],'Recognize Standard Diagnostics Start followed by waiting Cleaning; read the row order.');
await crop('start','WF-ENTRY',[20,390,375,122],'Recognize Start within its named Standard Diagnostics row.');
await crop('continue','WF-CONTINUE',[20,390,375,122],'Recognize Continue in the same profile after a run has started.');
await crop('cards','WF-CARDS',padded(firstCard('WF-CARDS')),'Recognize a complete neutral card on the chosen run; larger complete controls instead of a repeated second example.');
await crop('identity','WF-CARDS',[12,134,391,111],'Match the run breadcrumb to the physical asset; profile was selected in WF-01.');
await crop('instructions','WF-INSTRUCTIONS',padded(firstCard('WF-INSTRUCTIONS')),'Read the complete instruction in its card; no extra outline on the complete check.');
await crop('control','WF-CARDS',padded(firstCard('WF-CARDS')),'Recognize the neutral Geslaagd/Mislukt control card.');
await crop('task','WF-TASK',padded(firstCard('WF-TASK')),'Recognize Gedaan/Niet gedaan on a task card; explicit separate Cleaning example.',[],{comparisonExample:'Cleaning task, distinct from the main Standard Diagnostics card.'});
await crop('note','WF-NOTE',padded(firstCard('WF-NOTE')),'Recognize Notitie, Testnotitie and a real save timestamp within the complete card.');
await crop('photo','WF-PHOTO',padded(firstCard('WF-PHOTO')),'Recognize Foto toevoegen and the uploaded demonstration thumbnail in the complete card.',[],{demonstrationUpload:true});
const done = state('WF-DONE').elements.find(e => e.testid==='workflow-done-area').bounds;
await crop('done','WF-DONE',padded(done),'Recognize enabled Klaar and its same-asset Workflows return explanation.');
await crop('required','HANDOFF-READY',[12,1727,366,150],'Read the current required-work summary on Info, not just one historical passing run.');
await crop('handoff-note','HANDOFF-NOTE',[15,1392,355,112],'Read Statusnotitie before changing the status; complete labeled field.',[],{viewState:'Native textarea resize handle used to show the existing saved demonstration note; no second save.'});
await crop('handoff-saved','HANDOFF-SAVED',[15,1283,355,106],'Recognize saved QA Hold after reopen, with its status control.');
await crop('components','HANDOFF-COMPONENTS',[25,350,365,185],'Recognize the component panel and its Assumed model-baseline meaning.',[],{translationGap:'Current Components / Model baseline / Assumed remain untranslated.'});
const photoOriginal = evidencePath('AST-LABEL-PLACEMENT-PHOTO-01');
const photoRetained = path.join(round,'evidence','AST-LABEL-PLACEMENT-PHOTO-01.jpg');
if (!fs.existsSync(photoRetained)) fs.copyFileSync(photoOriginal, photoRetained);
assert.equal(hash(photoOriginal),hash(photoRetained));
const pb = fs.readFileSync(photoRetained); const pm = await sharp(pb).metadata();
images.labels = {href:`data:image/jpeg;base64,${pb.toString('base64')}`,width:pm.width,height:pm.height};
sources.push({key:'labels',source:rel(photoRetained),sourceSha256:hash(photoRetained),sourcePixels:{width:pm.width,height:pm.height},cropPixels:{x:0,y:0,w:pm.width,h:pm.height},job:'Locate manufacturer identification and asset QR on a physical example; not the synthetic device.',illustrativeOnly:true,marks:[{x:493,y:448,w:480,h:66,padding:14,target:'manufacturer identity label',reason:'Locate the small label on the physical underside.'},{x:1310,y:729,w:119,h:118,padding:14,target:'physical asset QR',reason:'Locate the small QR among other physical details.'}]});

function text(d,x,y,v,o={}) { d.text(x,y,v,{size:2.75,lh:3.85,...o}); }
function header(d,code,version,purpose,page=1,pages=1,size=7.6) {
    const p=GUIDE_FAMILIES[code.split('-')[0]];
    d.rect(12,13,2,16,p.color,'none',0,1);
    text(d,18,22,`${code} ${guideReference(code).title}`,{size,weight:900});
    text(d,18,29.5,purpose,{size:3.05,fill:C.muted});
    d.rect(164,10,34,13,p.fill,p.color,0.45,1.4);
    d.centeredText(181,14.6,`Concept v${version}`,{size:2.5,weight:800,fill:p.color});
    d.centeredText(181,19.5,`20-10-2026 | ${page}/${pages}`,{size:2.15,fill:p.color});
    text(d,12,291,`Inbit Snipe-IT | ${code} v${version} | 20-10-2026`,{size:1.9,fill:C.muted});
    text(d,198,291,`${page}/${pages} | https://snipe.inbit/`,{size:1.9,fill:C.muted,anchor:'end'});
}
function step(d,code,n,x,y,w,h,title,size=3.6) {
    const p=GUIDE_FAMILIES[code.split('-')[0]];
    d.rect(x,y,w,h,'#FFFFFF',C.line,0.5,2.2,1,{component:'task-step',number:n});
    d.stepBadge(x+10,y+10,n,p.color);
    text(d,x+21,y+11.4,title,{size,weight:900,fill:p.color});
}
function figure(d,key,x,y,w,label,color,caption=[],o={}) {
    const i=images[key],h=w*i.height/i.width,frame={x,y,w,h};
    const placement=d.image(key,frame,null,{fit:'contain'}),source=sources.find(s=>s.key===key);
    const marks=(source.marks??[]).map(m=>d.focusMark(placement,m));
    d.imageBadge(o.badgeLeft?x+1.4:x+w-1.3,y+1.4,label,color);
    if(caption.length)text(d,x,y+h+4.1,caption,{size:2.2,lh:3.15,fill:C.muted,...o});
    figures.push({page:d.idPrefix,key,label,frameMm:frame,focusMm:marks,pixelsPerMm:i.width/w});
    return frame;
}
function chip(d,code,x,y,w=80) { d.guideChip(x,y,w,guideReference(code)); }
function related(d,entries,y=278) {
    const placements=layoutRelatedGuides(entries.map(([code,width,row])=>guideReference(code,{width,row})),{rightEdge:198,firstRowY:y});
    d.centeredText(12,y+3.5,'Relevante gidsen',{size:2.1,weight:800,fill:C.muted,anchor:'start'});
    placements.forEach(p=>d.guideChip(p.x,p.y,p.width,p));
}
function complete(d,value,y=263,h=12) { drawCompletionRow(d,value,{x:12,y,width:186,height:h}); }
function continuation(d,value,y=266) {
    d.rect(12,y,186,10,C.orangeSoft,C.wf,0.45,1.8,1,{component:'continuation-row'});
    d.centeredText(17,y+5,value,{size:2.65,weight:800,fill:C.wf,anchor:'start'});
}
function helpBox(d,x,y,w,h,title,lines,code=null) {
    d.rect(x,y,w,h,C.orangeSoft,'#F6C36A',0.4,1.5);
    text(d,x+3,y+6,title,{size:2.65,weight:800,fill:'#784200'});
    text(d,x+3,y+12,lines,{size:2.5,lh:3.6,fill:'#784200'});
    if(code)chip(d,code,x+3,y+h-10,w-6);
}

const route=new SvgGuideDocument(images,'AST-02-v10-p1');
header(route,'AST-02',10,'Zoek waar je nu bent en open de juiste taakgids.',1,1,7.0);
drawContextStrip(route,[{label:'Rol',value:'Refurbisher'},{label:'Nodig',value:'Apparaat + taakgidsen'}]);
const routeRows=[
 ['AC-01',['Meld je aan en herken het dashboard.']],
 ['SC-01',['Vind het record en vergelijk het','met het fysieke apparaat.']],
 ['WF-01',['Lees de volgorde; start eenmaal','of open de bestaande run.']],
 ['WF-02',['Voer het werk uit; sla eerlijke','resultaten en gevraagd bewijs op.']],
 ['AST-04',['Controleer het complete werk en','draag over voor beoordeling.']],
 ['AST-05',['Vanaf hier: Supervisor beoordeelt','en legt vrijgave of herstel vast.']],
];
route.line(22,75,22,191,C.line,0.8);
routeRows.forEach(([code,lines],i)=>{
    const y=65+i*23;
    route.rect(12,y,186,20,i===5?C.faint:'#FFFFFF',C.line,0.45,1.6);
    route.stepBadge(22,y+10,i+1,C.ast);
    chip(route,code,35,y+6.5,80);
    text(route,121,y+8.4,lines,{size:2.65,lh:3.8});
});
route.rect(12,209,114,50,C.orangeSoft,'#F6C36A',0.4,1.5);
text(route,16,216,'Keuze bij componentwerk',{size:2.95,weight:900,fill:'#784200'});
chip(route,'CMP-01',16,220,106);chip(route,'CMP-02',16,229,106);chip(route,'CMP-04',16,238,106);
text(route,16,253,'Daarna:',{size:2.4,fill:'#784200'});chip(route,'WF-02',32,248.7,90);
route.rect(132,209,66,50,C.orangeSoft,'#F6C36A',0.4,1.5);
text(route,136,216,'Nog niet geregistreerd?',{size:2.9,weight:900,fill:'#784200'});
text(route,136,223,'Laat Supervisor registreren:',{size:2.45,fill:'#784200'});
chip(route,'AST-03',136,227,58);
text(route,136,242,'Daarna: vind het record.',{size:2.45,fill:'#784200'});
chip(route,'SC-01',136,246,58);
complete(route,'Je weet welke taakgids nu nodig is.',264);
related(route,[['HELP-01',70]],280);

const start=new SvgGuideDocument(images,'WF-01-v16-p1');
header(start,'WF-01',16,'Open de juiste workflow; start een nieuwe run precies eenmaal.');
drawContextStrip(start,[{label:'Rol',value:'Refurbisher'},{label:'Nodig',value:'Gecontroleerd apparaat'},{label:'Vooraf',guideCode:'SC-01'}]);
step(start,'WF-01',1,12,65,90,61,'Open Workflows');
text(start,16,88,['Het juiste apparaat is al gecontroleerd.','Tik op het klembord in de tabrij (1A).'],{size:2.6,lh:3.7});
figure(start,'tabs',18,101,78,'1A',C.wf,['Tabrij: klembord = Workflows.']);
step(start,'WF-01',2,108,65,90,90,'Bekijk de volgorde');
text(start,112,88,['Begin bij het eerste nog uit te voeren werk.','Een grijze opvolger wacht op eerder werk.'],{size:2.55,lh:3.6});
figure(start,'order',113,98,78,'2A',C.wf,['Standard Diagnostics kan starten;','Cleaning wacht op die eerdere stap.'],{size:2.1,lh:3});
step(start,'WF-01',3,12,132,90,94,'Kies een van beide',3.55);
text(start,16,153,'Nog geen run: Start eenmaal (3A).',{size:2.65,weight:800,fill:C.wf});
figure(start,'start',18,156,78,'3A',C.wf);
start.line(18,184,45,184,C.wf,0.4);start.line(57,184,96,184,C.wf,0.4);
start.centeredText(51,184,'OF',{size:2.7,weight:900,fill:C.wf});
text(start,16,191,['Run bestaat al: Continue of Edit (3B).','Open de run uit dezelfde profielrij.'],{size:2.55,lh:3.6});
figure(start,'continue',18,198,78,'3B',C.wf);
step(start,'WF-01',4,108,158,90,68,'Controleer de kaarten');
text(start,112,181,['Controleer de titels. Neutraal betekent:','nog geen resultaat voor die kaart.'],{size:2.55,lh:3.6});
figure(start,'cards',113,190,78,'4A',C.wf);
text(start,12,233,'Hulp bij starten',{size:3.1,weight:900,fill:C.muted});
helpBox(start,12,237,43.5,24,'Geen tab of rechten?',['Controleer rechten','en de assetstatus.']);
helpBox(start,59.5,237,43.5,24,'Run onduidelijk?',['Vraag Supervisor','welke run nodig is.']);
helpBox(start,107,237,43.5,24,'Rij geblokkeerd?',['Lees de reden. Vraag','hulp; wijk niet uit.']);
helpBox(start,154.5,237,43.5,24,'Geen kaarten?',['Start niet opnieuw.','Vraag Supervisor.']);
complete(start,'De juiste workflow is open; de verwachte kaarten zijn zichtbaar.',265);
related(start,[['WF-02',79],['HELP-01',64]],281);

const run1=new SvgGuideDocument(images,'WF-02-v14-p1');
header(run1,'WF-02',14,'Lees de controle, voer haar uit en leg het eerlijke resultaat vast.',1,2,6.25);
drawContextStrip(run1,[{label:'Rol',value:'Refurbisher'},{label:'Nodig',value:'Workflow + apparaat'},{label:'Vooraf',guideCode:'WF-01'}]);
step(run1,'WF-02',1,12,65,186,37,'Controleer apparaat en run');
text(run1,16,87,['Vergelijk assettag en model met het apparaat.','Je gebruikt de run uit de gekozen profielrij.'],{size:2.65,lh:3.7});
figure(run1,'identity',112,72,80,'1A',C.wf,[],{});
text(run1,16,97,'Ander apparaat? Wijzig niets; vraag Supervisor.',{size:2.4,fill:'#A21C2B'});
step(run1,'WF-02',2,12,108,186,71,'Lees de instructie');
text(run1,16,133,['Tik op Toon instructies (2A).','Lees de volledige controle voordat je','een resultaat kiest. Voer de beschreven','fysieke of softwarecontrole uit.'],{size:2.75,lh:3.9});
text(run1,16,157,['Onduidelijk of niet uitvoerbaar?','Vraag Supervisor; kies nog geen resultaat.'],{size:2.5,lh:3.6,fill:'#784200'});
figure(run1,'instructions',112,116,80,'2A',C.wf,['De hele instructie binnen dezelfde kaart.']);
step(run1,'WF-02',3,12,185,186,76,'Kies het resultaat na uitvoering');
text(run1,16,207,['Kies naar waarheid. Je ziet een controlekaart of een taakkaart.','Geslaagd / Mislukt en Gedaan / Niet gedaan zijn alternatieven per kaart.'],{size:2.7,lh:3.8});
figure(run1,'control',18,220,78,'3A',C.wf,['Controlekaart: Geslaagd of Mislukt.']);
figure(run1,'task',112,220,78,'3B',C.wf,['Voorbeeld taakkaart: Cleaning.']);
continuation(run1,'Ga verder op bladzijde 2: notitie, foto en afronding met stap 4 t/m 6.');
related(run1,[['WF-01',73],['HELP-01',64]],280);

const run2=new SvgGuideDocument(images,'WF-02-v14-p2');
header(run2,'WF-02',14,'Vervolg: bewijs vastleggen en de opgeslagen uitkomst controleren.',2,2,6.25);
drawContextStrip(run2,[{label:'Rol',value:'Refurbisher'},{label:'Vooraf',value:'Dezelfde run; stap 1 t/m 3'}]);
step(run2,'WF-02',4,12,65,186,75,'Voeg een notitie toe');
text(run2,16,89,['Tik op Notitie bij een afwijking, mislukking','of relevante meetwaarde. Schrijf wat je zag','en wat je al hebt gecontroleerd.','Controleer dat Opgeslagen om is bijgewerkt;','een oud tijdstip bewijst geen nieuwe opslag.'],{size:2.7,lh:3.8});
text(run2,16,115,['Geen nieuwe opslagmelding? Laat de kaart','open en vraag hulp. Start geen nieuwe run.'],{size:2.5,lh:3.6,fill:'#784200'});
figure(run2,'note',118,72,74,'4A',C.wf,['Volledige kaart met notitie en tijdstip.']);
step(run2,'WF-02',5,12,146,186,79,'Voeg gevraagd beeld toe');
text(run2,16,170,['Tik op Foto en daarna Foto toevoegen.','Maak een foto of kies een schermafbeelding','als de controle bewijs vraagt. Controleer','het miniatuur en het relevante detail.','Houd wachtwoorden, codes en','klantgegevens buiten beeld.'],{size:2.65,lh:3.75});
text(run2,16,199,['Foto lukt niet? Probeer eenmaal opnieuw;','vraag daarna Supervisor om hulp.'],{size:2.5,lh:3.6,fill:'#784200'});
figure(run2,'photo',118,152,74,'5A',C.wf,['Voorbeeld: toegevoegde demonstratiefoto.'],{size:2.1});
step(run2,'WF-02',6,12,231,186,44,'Controleer en kies Klaar');
text(run2,16,253.5,['Herhaal 2 t/m 5 en wacht op opslag.','Kies Klaar (6A); lees de status op Workflows.','Volg vervolgwerk. Mislukt? Vraag Supervisor.','Alles actueel afgerond? Draag over:'],{size:2.6,lh:3.65});
figure(run2,'done',118,247,74,'6A',C.wf,[],{badgeLeft:true});
chip(run2,'AST-04',16,267.3,88);
complete(run2,'Resultaten en gevraagd bewijs zijn opgeslagen; het vervolg is duidelijk.',277,10);

const handoff=new SvgGuideDocument(images,'AST-04-v8-p1');
header(handoff,'AST-04',8,'Controleer het werk en draag het apparaat over voor beoordeling.',1,1,6.15);
drawContextStrip(handoff,[{label:'Rol',value:'Refurbisher'},{label:'Nodig',value:'Apparaat + afgerond werk'},{label:'Vooraf',guideCode:'WF-02',size:2.35}]);
step(handoff,'AST-04',1,12,65,186,61,'Controleer het actuele werk');
text(handoff,16,89,['Lees op Info de vereiste workflows (1A).','Elke vereiste taak moet actueel en afgerond','zijn; open de runs en lees de resultaten.','Ontbreekt werk of is iets mislukt? Herstel dat','eerst. Een oude geslaagde run heft gewijzigd','of ontbrekend werk niet op.'],{size:2.65,lh:3.75});
figure(handoff,'required',115,73,77,'1A',C.ast,['Info: huidige vereiste workflows.']);
step(handoff,'AST-04',2,12,132,186,76,'Vergelijk registratie en apparaat');
figure(handoff,'labels',16,152,39,'2A',C.ast,['Voorbeeld: labelplekken.'],{size:2.05});
text(handoff,59,155,['Vergelijk label, S/N,','modeltype en staat.','Controleer de echt','geplaatste onderdelen.','Alleen Tracked: ook','tag en serienummer','met registratie vergelijken.'],{size:2.55,lh:3.6});
figure(handoff,'components',119,148,73,'2B',C.ast,['Assumed / Model baseline is een verwachting;','controleer wat er echt in het apparaat zit.'],{size:2.15,lh:3.1});
text(handoff,16,187,'Componentwerk nodig? Gebruik:',{size:2.35,fill:C.muted});
chip(handoff,'AST-02',16,190,88);
text(handoff,16,201,['Identiteit wijkt af of is onduidelijk? Wijzig niets.','Vraag Supervisor om apparaat en record te controleren.'],{size:2.5,lh:3.6,fill:'#A21C2B'});
step(handoff,'AST-04',3,12,212,186,61,'Draag over aan Supervisor');
text(handoff,16,235,['Schrijf eerst Statusnotitie (3A): werk en','afwijkingen. Kies daarna QA Hold (3B).','Open opnieuw; controleer QA Hold en je','notitie in Geschiedenis. Plaats het apparaat','op de afgesproken QA-plek. Onduidelijk?','Vraag Supervisor. Die gaat verder met:'],{size:2.6,lh:3.7});
chip(handoff,'AST-05',16,260,88);
figure(handoff,'handoff-note',119,218,73,'3A',C.ast);
figure(handoff,'handoff-saved',119,245,73,'3B',C.ast);
text(handoff,119,270,'Info: eerst notitie, daarna status; controleer na heropenen.',{size:1.95,fill:C.muted});
complete(handoff,'QA Hold en de notitie zijn opgeslagen; het apparaat is overgedragen.',277,10);

function problem(d,x,y,title,lines,guide,critical=false) {
    d.rect(x,y,90,43,critical?'#FFF5F5':'#FFFFFF',critical?C.help:C.line,0.45,1.5);
    text(d,x+4,y+7,title,{size:2.85,weight:900,fill:critical?'#A21C2B':C.ink});
    text(d,x+4,y+14,lines,{size:2.6,lh:3.65});
    if(guide)chip(d,guide,x+4,y+33,82);
}
const help1=new SvgGuideDocument(images,'HELP-01-v9-p1');
header(help1,'HELP-01',9,'Kies je probleem en lees alleen de passende herstelactie.',1,2,7.1);
drawContextStrip(help1,[{label:'Rol',value:'Iedereen'},{label:'Deze zijde',value:'Toegang, zoeken en labels'},{label:'Andere zijde',value:'Workflow, status, onderdelen',size:2.4}]);
const problems1=[
 ['Geen account',['Vraag Supervisor om een account.','Gebruik je eigen inloggegevens.'],'AC-01'],
 ['Wachtwoord kwijt',['Vraag Supervisor om een wachtwoordreset.','Deel je wachtwoord niet met anderen.'],'AC-01'],
 ['Geen Inbit-telefoon',['Open een browser op','https://snipe.inbit/ en log in.'],'AC-01'],
 ['Camera werkt niet',['Gebruik Handmatige invoer en Zoeken.','Lees de assettag van het apparaat.','Vergelijk na openen de identiteit.'],'SC-01'],
 ['QR beschadigd',['Zoek handmatig op de assettag.','Laat het label controleren of vervangen;','controleer eerst het juiste record.'],'SC-01'],
 ['Verkeerd apparaat of record',['Komt de identiteit niet overeen?','STOP: wijzig niets. Toon apparaat en','scherm aan Supervisor. Ga verder zodra','het juiste record is vastgesteld.'],'SC-01',true],
 ['Dubbele assettag of serienummer',['Zoek het bestaande record en vergelijk','de identiteit. Maak geen duplicaat om','de melding te omzeilen. Vraag Supervisor.'],'AST-03'],
 ['Printer of label faalt',['Controleer printer, formaat en taakstatus.','Uitkomst onduidelijk? Vraag lokale hulp;','stuur niet blind dezelfde print opnieuw.'],'AST-03'],
];
problems1.forEach((p,i)=>problem(help1,12+(i%2)*96,65+Math.floor(i/2)*48,...p));
text(help1,12,262,'Workflow, opslag, rechten, status of onderdelen? Kijk op de andere zijde.',{size:2.7,weight:800,fill:C.muted});
complete(help1,'Je kent de passende herstelactie of weet wie je moet vragen.',269,10);

const help2=new SvgGuideDocument(images,'HELP-01-v9-p2');
header(help2,'HELP-01',9,'Problemen bij werk, opslaan, rechten en overdragen.',2,2,7.1);
drawContextStrip(help2,[{label:'Rol',value:'Iedereen'},{label:'Deze zijde',value:'Workflow, status, onderdelen'},{label:'Andere zijde',value:'Toegang, zoeken en labels',size:2.4}]);
const problems2=[
 ['Geen workflow of start geblokkeerd',['Controleer het apparaat en lees de reden.','Een opvolger kan op eerder werk wachten.','Rond dat werk af of vraag Supervisor.','Kies geen ander profiel om dit te omzeilen.'],'WF-01'],
 ['Workflow is gewijzigd',['Een oude geslaagde run vervangt de','gewijzigde definitie niet. Vraag Supervisor','welk werk opnieuw nodig is. Start of','override niet op eigen initiatief.'],'WF-01'],
 ['Resultaat of bewijs slaat niet op',['Blijf op dezelfde kaart. Controleer de','opslagmelding en het nieuwe notitietijdstip.','Foto: controleer het miniatuur. Fout?','Probeer eenmaal; vraag dan hulp.','Start hiervoor geen nieuwe workflow.'],'WF-02'],
 ['Geen rechten of kwaliteit wijzigen',['Vraag Supervisor om de juiste toegang.','Kwaliteit kunnen lezen geeft niet vanzelf','het recht om die te wijzigen. Gebruik','geen ander account of omweg.'],null],
 ['Status wordt niet opgeslagen',['Lees de melding. Vul de gevraagde','Statusnotitie in voordat je de status kiest.','Open opnieuw en controleer de uitkomst.','Blijft het misgaan? Vraag Supervisor.'],'AST-04'],
 ['Probleem bij vrijgavebevestiging',['Lees de getoonde bevindingen. Ontbreekt','werk of klopt iets niet? Annuleer en laat','het herstellen. Laat Supervisor de','uitzondering beoordelen; forceer niets.'],'AST-05'],
 ['Component niet in tray',['Maak geen nieuw component om dit op','te lossen. Controleer de echte tray of','opslag en de registratie. Vraag hulp als','je het juiste onderdeel niet vindt.'],'CMP-01'],
 ['Component klopt niet',['Annuleer de componentactie en controleer','het juiste onderdeel en de registratie.','Laat Supervisor een onduidelijke identiteit','controleren voordat je verdergaat.'],'CMP-04'],
];
problems2.forEach((p,i)=>problem(help2,12+(i%2)*96,65+Math.floor(i/2)*48,...p));
text(help2,12,262,'Zoek je probleem; deze tegels zijn geen stappen die je achter elkaar uitvoert.',{size:2.65,fill:C.muted});
complete(help2,'Je kent de passende herstelactie en wanneer je weer verder kunt.',269,10);

const base='resources/manuals/operator-guides/';
const guides=[
 {code:'AST-02',version:10,stem:'AST-02-refurbishment-route-v10-draft',pages:[route],recipe:'route-list with help-alternative',baseline:base+'review-rounds/2026-09-10/owner-corrections/AST-02-refurbishment-route-v8-draft.pdf',previous:base+'review-rounds/2026-10-01-aligned-guides/AST-02-refurbishment-route-v9-draft.pdf',changes:['Een routeoverzicht van een zijde; geen herstart na klaar.','Workflowvolgorde en opgeslagen werk horen in hun eigen fase.','Componentkeuzes en ongeregistreerde invoer hebben een duidelijke terugkeer.']},
 {code:'WF-01',version:16,stem:'WF-01-workflow-starten-v16-draft',pages:[start],recipe:'two-column-step-grid with inline-route-alternative',baseline:base+'review-rounds/2026-09-10/owner-corrections/WF-01-workflow-starten-v13-draft.pdf',previous:base+'review-rounds/2026-10-01-wf01-v15/WF-01-workflow-starten-v15-draft.pdf',changes:['Actuele profielrijen: Standard Diagnostics Start voor wachtende Cleaning.','Start OF Continue/Edit blijft een keuze; dezelfde voorbeeldidentiteit.','Nieuwe Nederlandse opname van kaarten en tabrij; geen extra kaders op complete rijen.']},
 {code:'WF-02',version:14,stem:'WF-02-complete-workflow-v14-draft',pages:[run1,run2],recipe:'stacked-step-flow with two-sided-continuation',baseline:base+'pdf/WF-02-workflow-uitvoeren-en-afronden-v10.pdf',previous:base+'review-rounds/2026-10-01-workflows/WF-02-complete-workflow-v13-draft.pdf',changes:['Nederlandse instructies, beide resultaattypen, notitie en echte upload.','Zes doorlopende stappen; klaar alleen na bewijs en opslagcontrole.','Klaar keert terug naar hetzelfde apparaat; geen automatische verkoopvrijgave.']},
 {code:'AST-04',version:8,stem:'AST-04-complete-handoff-v8-draft',pages:[handoff],recipe:'stacked-step-flow with reused-evidence and inline-warning',baseline:base+'review-rounds/2026-09-08/AST-04-complete-handoff-v6-draft.pdf',previous:base+'review-rounds/2026-10-01-aligned-guides/AST-04-complete-handoff-v7-draft.pdf',changes:['Actuele vereiste workflows in plaats van alleen een oude geslaagde run.','Fysieke labels, werkelijke onderdelen en alleen waar nodig Tracked-identiteit.','Eerst Statusnotitie, daarna QA Hold; heropenen en fysieke overdracht voor voltooiing.']},
 {code:'HELP-01',version:9,stem:'HELP-01-problems-v9-draft',pages:[help1,help2],recipe:'troubleshooting-grid',baseline:base+'review-rounds/2026-09-08/HELP-01-problems-v7-draft.pdf',previous:base+'review-rounds/2026-10-01-aligned-guides/HELP-01-problems-v8-draft.pdf',changes:['Twee probleembladen zonder stapnummers; twaalf bestaande onderwerpen behouden.','Extra hulp bij gewijzigde workflows, opslag, verplichte notitie en vrijgavebevestiging.','Identiteitshalt staat bij de passende tegel; geen algemene STOP-banner boven elk probleem.']},
];
const htmlPage=svgs=>`<!doctype html><html lang="nl"><meta charset="utf-8"><title>Inbit handleiding concept</title><style>@page{size:A4 portrait;margin:0}*{box-sizing:border-box}body{margin:0;background:#dce3eb}.page{width:210mm;height:297mm;background:white;margin:0 auto;break-after:page;overflow:hidden}.page:last-child{break-after:auto}.page svg{display:block;width:210mm;height:297mm}@media screen{.page{margin:12px auto;box-shadow:0 2px 8px #4566}}</style><body>${svgs.map(s=>`<section class="page">${s}</section>`).join('')}</body></html>`;
const browser=await chromium.launch({executablePath:resolveChromeExecutable(),headless:true});
const qa=[];
try {
 for(const g of guides){
    const svgs=g.pages.map(d=>d.render());
    svgs.forEach((s,i)=>fs.writeFileSync(path.join(round,`${g.stem}-page-${i+1}.svg`),s));
    const html=path.join(round,`${g.stem}.html`);fs.writeFileSync(html,htmlPage(svgs));
    const page=await browser.newPage();await page.goto(pathToFileURL(html).href);await page.evaluate(()=>document.fonts.ready);
    const components=await inspectRenderedGuideComponents(page);
    const taskFrames=await page.evaluate(()=>[...document.querySelectorAll('.page')].flatMap((p,i)=>[...p.querySelectorAll('rect[data-component="task-step"]')].map(r=>({page:i+1,number:Number(r.dataset.number),x:Number(r.getAttribute('x')),y:Number(r.getAttribute('y')),w:Number(r.getAttribute('width')),h:Number(r.getAttribute('height'))}))));
    for(const f of figures.filter(f=>f.page.startsWith(`${g.code}-`))){
        const pageNumber=Number(f.page.match(/-p(\d+)$/)[1]),number=Number(f.label.match(/^\d+/)[0]);
        const frame=taskFrames.find(t=>t.page===pageNumber&&t.number===number);
        if(!frame)continue;
        const b=f.frameMm;
        assert.ok(b.x>=frame.x && b.y>=frame.y && b.x+b.w<=frame.x+frame.w && b.y+b.h<=frame.y+frame.h,`${g.code} ${f.label}: screenshot leaves its task`);
    }
    const textBoxes=await page.evaluate(()=>[...document.querySelectorAll('.page')].flatMap((p,i)=>[...p.querySelectorAll('text')].map(n=>{const b=n.getBBox();return{page:i+1,text:n.textContent,x:b.x,y:b.y,w:b.width,h:b.height};})));
    assert.deepEqual(textBoxes.filter(b=>b.x<0||b.y<0||b.x+b.w>210||b.y+b.h>297),[],`${g.code}: text outside A4`);
    qa.push({code:g.code,components,taskFrames,textBoxes});await page.close();
    const pdf=path.join(round,`${g.stem}.pdf`);
    const render=spawnSync(process.execPath,[path.join(repoRoot,'scripts/manuals/render-guide-html.mjs'),'--html',html,'--pdf',pdf,'--pages',String(g.pages.length),'--render-dir',path.join(proof,g.code),'--replace-output'],{encoding:'utf8',windowsHide:true,env:process.env});
    assert.equal(render.status,0,render.stderr||render.stdout);
    fs.copyFileSync(pdf,path.join(repoPdfOutputRoot,path.basename(pdf)));
    g.pdf=rel(pdf);g.html=rel(html);g.sha256=hash(pdf);g.baselineSha256=hash(path.join(repoRoot,g.baseline));g.previousSha256=hash(path.join(repoRoot,g.previous));
    console.log(JSON.stringify({code:g.code,version:g.version,pages:g.pages.length,pdf:g.pdf}));
 }
} finally {await browser.close();}
fs.writeFileSync(path.join(proof,'geometry-review.json'),JSON.stringify(qa,null,2));
fs.writeFileSync(path.join(round,'authoring-manifest.json'),JSON.stringify({round:'2026-10-20-operational-guides',status:'Working drafts; not accepted',authoringBrief:'docs/manuals/operator-guides/reviews/operational-guides-2026-10-20.md',generatedAt:new Date().toISOString(),guides:guides.map(({pages,...g})=>({...g,pageCount:pages.length})),sources,figures,limitations:['Demonstration results do not establish physical work.','Missing English workflow progression/component labels persist despite nl-NL.','Physical QA location, print and novice-reader trials remain open.']},null,2));
