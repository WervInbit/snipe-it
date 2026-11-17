import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import crypto from 'node:crypto';
import {spawnSync} from 'node:child_process';
import {pathToFileURL} from 'node:url';
import {SvgGuideDocument,GUIDE_TOKENS,GUIDE_FAMILIES,guideReference,drawContextStrip,drawCompletionRow,layoutRelatedGuides,inspectRenderedGuideComponents} from './lib/guide-system.mjs';
import {repoRoot,repoPdfOutputRoot,loadGuideDependency,browserLaunchOptions,evidencePath} from './lib/guide-paths.mjs';

assert.ok(process.argv.slice(2).every(arg=>arg==='--crops'),'Only --crops is supported; unknown arguments must not generate files.');
const round=path.join(repoRoot,'resources/manuals/operator-guides/review-rounds/2026-10-20-remaining-guides');
const proof=path.join(repoRoot,'output/manuals/proofs/remaining-guides-2026-10-20');
assert.ok(!fs.existsSync(path.join(round,'manifest.sha256')),'Retained round: create a new version.');
const inputs=JSON.parse(fs.readFileSync(path.join(round,'inputs.json'),'utf8')).guides;
const plan=JSON.parse(fs.readFileSync(path.join(round,'crop-plan.json'),'utf8'));
const sharp=loadGuideDependency('sharp');const {chromium}=loadGuideDependency('playwright');
const images={},sources=[],figures=[],C=GUIDE_TOKENS.colors;
const recordedRoles={'GROUP-EDIT':'Superadmin','GROUP-DETAIL':'Superadmin','GROUP-LIST':'Superadmin','USR-PERMISSIONS':'Superadmin','REG-DASH':'Supervisor'};
const hash=p=>crypto.createHash('sha256').update(fs.readFileSync(p)).digest('hex');
const rel=p=>path.relative(repoRoot,p).replaceAll('\\','/');
for(const g of inputs)assert.equal(hash(path.join(round,g.input)),g.baselineHtmlSha256,g.code+': retained input changed');
fs.mkdirSync(proof,{recursive:true});
for(const[key,p]of Object.entries(plan)){
 const source=path.join(round,'evidence',p.source+'.png');
 const state=JSON.parse(fs.readFileSync(path.join(round,'evidence',p.source+'.json'),'utf8'));
 const dpr=state.viewport.dpr;const meta=await sharp(source).metadata();
 const [left,top,width,height]=p.bounds.map((v,i)=>i<2?Math.floor(v*dpr):Math.ceil(v*dpr));
 assert.ok(left>=0&&top>=0&&left+width<=meta.width&&top+height<=meta.height,`${key}: crop [${left},${top},${width},${height}] outside ${meta.width}x${meta.height}`);
 const bytes=await sharp(source).extract({left,top,width,height}).png().toBuffer();
 const file=path.join(round,'crops',key+'.png');fs.writeFileSync(file,bytes);
 images[key]={href:'data:image/png;base64,'+bytes.toString('base64'),width,height};
 sources.push({key,source:rel(source),sourceSha256:hash(source),crop:rel(file),cropSha256:hash(file),sourcePixels:{width:meta.width,height:meta.height},cropPixels:{x:left,y:top,w:width,h:height},job:p.job,locale:state.locale,role:state.notes?.actor??recordedRoles[p.source]??'Recorded capture actor',marks:p.marks.map(m=>({...m,x:m.x*dpr-left,y:m.y*dpr-top,w:m.w*dpr,h:m.h*dpr,padding:(m.padding??3)*dpr}))});
}
for(const[key,id]of [['labels','AST-LABEL-PLACEMENT-PHOTO-01'],['hp-source','CAT-SOURCE-HP-PROBOOK-450-G8-02'],['people-nav','USR-DASHBOARD-PEOPLE-NAV-DESKTOP-01']]){
 const file=evidencePath(id),bytes=fs.readFileSync(file),m=await sharp(bytes).metadata();const retained=path.join(round,'evidence',path.basename(file));if(!fs.existsSync(retained))fs.copyFileSync(file,retained);assert.equal(hash(file),hash(retained));
 images[key]={href:`data:image/${m.format==='jpeg'?'jpeg':'png'};base64,${bytes.toString('base64')}`,width:m.width,height:m.height};
 sources.push({key,source:rel(retained),sourceSha256:hash(file),sourcePixels:{width:m.width,height:m.height},cropPixels:{x:0,y:0,w:m.width,h:m.height},job:key==='labels'?'Recognize the physical identification label and QR placement on a separate physical example.':key==='hp-source'?'Read official family and display statement; exact SKU and optional configuration remain unconfirmed.':'Locate Personen > Toon Alles; canonical unchanged Dutch navigation.',canonicalId:id,marks:[]});
}
sources.find(s=>s.key==='labels').marks=[{x:1310,y:729,w:119,h:118,padding:14,target:'Physical asset QR',reason:'Locate the small QR on the complete physical underside example.'}];
if(process.argv.includes('--crops')){console.log(JSON.stringify({crops:sources.length}));process.exit(0);}

function text(d,x,y,v,o={}){d.text(x,y,v,{size:2.75,lh:3.85,...o});}
function header(d,code,version,purpose,page=1,pages=1){
 const p=GUIDE_FAMILIES[code.split('-')[0]],title=code+' '+guideReference(code).title;
 d.rect(12,13,2,16,p.color);text(d,18,22,title,{size:title.length>43?4.7:title.length>32?5.6:7.2,weight:900});
 text(d,18,29.5,purpose,{size:2.8,fill:C.muted});d.rect(164,10,34,13,p.fill,p.color,.45,1.4);
 d.centeredText(181,14.6,'Concept v'+version,{size:2.5,weight:800,fill:p.color});d.centeredText(181,19.5,`20-10-2026 | ${page}/${pages}`,{size:2.15,fill:p.color});
 text(d,12,291,`Inbit Snipe-IT | ${code} v${version} | 20-10-2026`,{size:1.9,fill:C.muted});text(d,198,291,`${page}/${pages} | https://snipe.inbit/`,{size:1.9,fill:C.muted,anchor:'end'});
}
function page(code,n,total,purpose,role,need,pre){const g=inputs.find(g=>g.code===code),d=new SvgGuideDocument(images,code+'-v'+g.nextVersion+'-p'+n);header(d,code,g.nextVersion,purpose,n,total);drawContextStrip(d,[{label:'Rol',value:role},{label:'Nodig',value:need},code===pre?{label:'Vervolg',value:'Dezelfde taak; vorige zijde'}:{label:'Vooraf',guideCode:pre}]);return d;}
function step(d,code,n,y,h,title,o={}){const x=o.x??12,w=o.w??186,p=GUIDE_FAMILIES[code.split('-')[0]];d.rect(x,y,w,h,'#FFFFFF',C.line,.45,2,1,{component:'task-step',number:n});d.stepBadge(x+9,y+9,n,p.color);text(d,x+20,y+10.5,title,{size:o.size??3.6,weight:900,fill:p.color});}
function figure(d,key,x,y,w,label,caption=[],maxH=999){const im=images[key];assert.ok(im,key);let h=w*im.height/im.width;if(h>maxH){w*=maxH/h;h=maxH;}const b={x,y,w,h};const first=d.parts.length,place=d.image(key,b,null,{fit:'contain'});for(let i=first;i<d.parts.length;i++)if(d.parts[i].startsWith('<image '))d.parts[i]=d.parts[i].replace('<image ',`<image data-recognition-source="${key}" `);d.imageBadge(['ac-menu','ac-save','usr-credentials','usr-reset','reg-title','reg-preview','maint-spec-save','numbers'].includes(key)?(key==='reg-preview'?x:x+1.2):x+w-1.2,key==='reg-preview'?y+5:['maint-inuse','maint-inuse-aggregation'].includes(key)?y-3.5:y+1.2,label,GUIDE_FAMILIES[d.idPrefix.split('-')[0]].color);const source=sources.find(s=>s.key===key);(source.marks??[]).forEach(m=>d.focusMark(place,m));if(caption.length)text(d,x,y+h+4,caption,{size:2.2,lh:3.1,fill:C.muted});figures.push({page:d.idPrefix,key,label,frameMm:b,sourcePixelsPerMm:im.width/w});return b;}
function chip(d,code,x,y,w=85){d.guideChip(x,y,w,guideReference(code));}
function related(d,codes,y=280){const width=codes.length<=2?90:60;const entries=codes.map((code,i)=>guideReference(code,{width,row:i<3?1:2}));const ps=layoutRelatedGuides(entries,{firstRowX:12,rightEdge:198,firstRowY:y});d.parts.push('<g data-footer-references="true">');ps.forEach(p=>d.guideChip(p.x,p.y,p.width,p));d.parts.push('</g>');}
function done(d,line,y=266,h=12){drawCompletionRow(d,line,{x:12,y,width:186,height:h});}
function next(d,line,y=267){d.rect(12,y,186,10,'#F7F2FB',C.cat,.4,1.5);text(d,16,y+6.2,line,{size:2.6,weight:800,fill:C.cat});}
function note(d,x,y,w,h,title,lines,kind='ordinary'){d.rect(x,y,w,h,kind==='amber'?C.orangeSoft:C.faint,kind==='amber'?'#F6C36A':C.line,.4,1.5);text(d,x+4,y+7,title,{size:2.8,weight:850,fill:kind==='amber'?'#784200':C.ink});text(d,x+4,y+14,lines,{size:2.6,lh:3.7});}
function title(d,code,title,subtitle){text(d,12,70,title,{size:4.3,weight:900,fill:GUIDE_FAMILIES[code.split('-')[0]].color});if(subtitle)text(d,12,77,subtitle,{size:2.65,fill:C.muted});}
const created=[];
const htmlFor=svgs=>`<!doctype html><html lang="nl"><meta charset="utf-8"><title>Inbit handleiding concept</title><style>@page{size:A4 portrait;margin:0}*{box-sizing:border-box}body{margin:0;background:#dce3eb}.page{width:210mm;height:297mm;background:white;margin:0 auto;break-after:page;overflow:hidden}.page:last-child{break-after:auto}.page svg{display:block;width:210mm;height:297mm}@media screen{.page{margin:12px auto;box-shadow:0 2px 8px #4566}}</style><body>${svgs.map(s=>`<section class="page">${s}</section>`).join('')}</body></html>`;

function buildShort(){
 let d=page('AC-02',1,1,'Wijzig het wachtwoord van je lokale account en controleer de opslag.','Iedereen','Huidig of tijdelijk wachtwoord','AC-01');
 step(d,'AC-02',1,65,48,'Open Wachtwoord wijzigen');text(d,16,88,['Open rechtsboven je accountmenu.','Kies Wachtwoord wijzigen (1A).'],{size:2.9,lh:4.2});figure(d,'ac-menu',125,70,64,'1A',[],38);
 step(d,'AC-02',2,119,91,'Vul huidig, nieuw en bevestiging in');text(d,16,143,['Boven: je huidige of tijdelijke wachtwoord.','Midden: je nieuwe eigen wachtwoord.','Onder: herhaal dat nieuwe wachtwoord.','De laatste twee heten allebei','Nieuw wachtwoord.','Volg de zichtbare wachtwoordregel.','Deel je eigen wachtwoord met niemand.'],{size:2.85,lh:4.1});figure(d,'ac-form',111,139,81,'2A',['De drie velden horen bij dezelfde wijziging.'],62);
 step(d,'AC-02',3,216,43,'Sla op en lees de melding');text(d,16,237,['Kies Opslaan. Lees de succesmelding.','Gebruik vanaf nu het nieuwe wachtwoord.','Fout? Corrigeer het genoemde veld.','Andere apparaten moeten opnieuw inloggen.'],{size:2.7,lh:3.8});figure(d,'ac-save',111,221,81,'3A');figure(d,'ac-success',111,241,81,'3B');
 text(d,12,265,'Huidig wachtwoord kwijt of reset werkt niet? Vraag een beheerder:',{size:2.5,fill:C.muted});chip(d,'USR-03',110,260,88);done(d,'De wijziging is opgeslagen; alleen jij kent je eigen wachtwoord.',273,12);created.push({code:'AC-02',pages:[d]});

 let a=page('AST-03',1,2,'Registreer een fysiek apparaat eenmaal met de juiste identiteit.','Supervisor','Apparaat + fabrikantlabel','AC-01');
 step(a,'AST-03',1,65,36,'Zoek eerst; open dan Nieuwe Asset');text(a,16,86,['Zoek eerst op assettag of S/N: voorkom een duplicaat.','Nog niet geregistreerd? Kies op het dashboard Nieuwe Asset.'],{size:2.6,lh:3.7});figure(a,'reg-entry',151,70,39,'1A',[],27);
 step(a,'AST-03',2,107,41,'Neem tag en serienummer over');text(a,16,130,['Behoud de voorgestelde tag. Unlock maakt die bewerkbaar.','Neem S/N van het apparaat over; geen Product ID of ProdID.','Aa is alleen voor bewust afwijkende hoofd-/kleine letters.'],{size:2.55,lh:3.65});figure(a,'reg-identity',121,123,71,'2A',[],21);
 step(a,'AST-03',3,154,47,'Kies categorie en exact modelnummer');text(a,16,177,['Kies het juiste apparaattype en model met de complete','fabrikantcode, zoals de HP Product ID/P/N inclusief suffix.','Past geen keuze? Laat de catalogus eerst aanvullen.','Je fysieke S/N en de modelcode zijn verschillende gegevens.'],{size:2.55,lh:3.65});figure(a,'reg-model',121,170,71,'3A',[],26);
 step(a,'AST-03',4,207,52,'Kies status, noteer en sla eenmaal op');text(a,16,230,['Kies de huidige werkstatus, hier Being Processed.','Vul Statusnotitie in als die status een notitie vraagt.','Kies Opslaan eenmaal; controleer tag, S/N en model','in het resultaatpaneel op de volgende zijde.','Dubbelmelding? Open en vergelijk eerst het bestaande record.'],{size:2.55,lh:3.65});figure(a,'reg-status',127,222,65,'4A',[],32);
 next(a,'Ga naar bladzijde 2: controleer het resultaat en maak het fysieke label.');related(a,['SC-01','CAT-01'],281);
 let b=page('AST-03',2,2,'Vervolg: kies het juiste label, plaats het en scan ter controle.','Supervisor','Opgeslagen asset + labelprinter','AST-03');
 step(b,'AST-03',5,65,43,'Open het opgeslagen asset');text(b,16,88,['Vergelijk tag en S/N in het resultaat (5A).','Kies Asset openen en controleer het model (5B).','Kies daar je printer en formaat voordat je print.'],{size:2.7,lh:3.9});figure(b,'reg-saved-identity',115,71,77,'5A',[],17);figure(b,'reg-title',115,90,77,'5B',[],18);
 step(b,'AST-03',6,114,65,'Kies label en printer; print eenmaal');text(b,16,138,['Controleer de QR-preview en QR Label Template.','Vergelijk Afdrukformaat met de geladen labels.','Kies Printerlocatie en daarna Print QR label.','Wacht op de melding en het fysieke label.','Uitkomst onduidelijk? Controleer de taak of vraag hulp.'],{size:2.7,lh:3.8});figure(b,'reg-print-controls',118,121,74,'6A',[],54);figure(b,'reg-preview',16,158,22,'6B',[],19);text(b,42,163,['De snelle printknop in het resultaat','gebruikt standaardinstellingen.','Maak bij printfouten geen nieuw asset.'],{size:2.4,lh:3.4});
 step(b,'AST-03',7,185,56,'Plaats het label rechtsonder');text(b,16,209,['Leg de onderzijde met de voorkant naar je toe.','Plaats het label rechtsonder op een vlak stuk.','Bedek geen ventilatie, poort, schroef of','servicelabel. Controleer of het goed vastzit.'],{size:2.8,lh:4});figure(b,'labels',112,191,80,'7A',['Plaatsingsvoorbeeld: de voorkant ligt naar je toe.'],44);
 step(b,'AST-03',8,247,27,'Scan en vergelijk dezelfde identiteit');text(b,16,269,['Scan met SC-01; controleer tag, S/N en model. Ander record? Wijzig niets; vraag Supervisor.'],{size:2.45});chip(b,'SC-01',111,251,81);done(b,'Het apparaat bestaat eenmaal; zijn geplaatste label opent hetzelfde record.',279,10);created.push({code:'AST-03',pages:[a,b]});

 d=page('CMP-01',1,1,'Plaats een bestaand geregistreerd onderdeel op het juiste apparaat.','Senior Refurbisher','Gecontroleerd asset + onderdeel','SC-01');
 step(d,'CMP-01',1,65,38,'Open Componenten');text(d,16,89,['Tik op de componenttab.','Kies Add / Install Component.'],{size:2.9,lh:4});figure(d,'cmp-entry',117,72,75,'1A',[],28);
 step(d,'CMP-01',2,109,52,'Kies hetzelfde onderdeel');text(d,16,133,['Zoek de bestaande registratie in je tray of opslag.','Vergelijk bron, naam en tag met het fysieke onderdeel.','Vervanging? Controleer eerst dat het oude onderdeel','ook uit dit asset is losgekoppeld. Onzeker? Vraag Supervisor.','Dezelfde naam alleen is geen identiteit.','Andere identiteit? Wijzig niets; vraag Supervisor.'],{size:2.65,lh:3.8});figure(d,'cmp-existing',117,117,75,'2A',[],39);
 step(d,'CMP-01',3,167,38,'Plaats fysiek en kies Install');text(d,16,190,['Plaats het onderdeel in het apparaat en kies Install eenmaal.','Melding over conditie? Laat Supervisor die eerst beoordelen.'],{size:2.65,lh:3.8});figure(d,'cmp-existing',128,172,64,'3A',[],29);
 step(d,'CMP-01',4,211,50,'Controleer de koppeling');text(d,16,234,['Controleer op het juiste asset: Tracked, dezelfde tag','en hetzelfde serienummer. Vergelijk met het onderdeel.','Controleer ook of Extra en Specificatie op Info kloppen.','Afwijking? Laat Supervisor dit controleren voordat je verdergaat.'],{size:2.6,lh:3.8});figure(d,'cmp-result',121,218,71,'4A',[],38);
 done(d,'Hetzelfde onderdeel is geplaatst; identiteit, koppeling en specificatie kloppen.',267,12);related(d,['CMP-04','WF-02'],282);created.push({code:'CMP-01',pages:[d]});

 a=page('CMP-02',1,2,'Registreer een nieuw fysiek onderdeel en plaats het op het asset.','Senior Refurbisher','Asset + nieuw onderdeel','SC-01');
 step(a,'CMP-02',1,65,72,'Open het nieuwe componentformulier');text(a,16,86,['Open Componenten; kies Add / Install Component (1A).','Nieuw? Kies Show New Component Form (1B).','Al geregistreerd? Gebruik CMP-01.'],{size:2.6,lh:3.8});chip(a,'CMP-01',111,91,81);figure(a,'cmp-entry',18,101,78,'1A',[],33);figure(a,'cmp-new-entry',113,101,78,'1B',[],33);
 step(a,'CMP-02',2,143,130,'Kies een van beide registratieroutes');text(a,16,167,['A: Use Component Definition voor een herbruikbaar onderdeeltype.','OF B: Custom Component alleen voor een overeengekomen eenmalige uitzondering.','Vul de definitie of naam, het echte serienummer en de conditie in.','Vervanging? Controleer eerst dat het oude onderdeel uit dit asset is losgekoppeld. Onzeker? Vraag Supervisor.'],{size:2.85,lh:4});
 text(a,16,184,'Herbruikbaar type ontbreekt? Laat Supervisor dit eerst aanvullen:',{size:2.6,fill:'#784200'});chip(a,'CAT-04',111,180,81);
 figure(a,'cmp-definition',25,195,70,'2A',['A: definitie, echt S/N en conditie.'],65);figure(a,'cmp-custom',119,195,70,'2B',['OF B: eigen naam, echt S/N en conditie.'],65);
 next(a,'Ga verder op bladzijde 2: plaatsen, eenmaal aanmaken en de uitkomst controleren.',279);
 b=page('CMP-02',2,2,'Vervolg: controleer de plaatsing en het effect op het juiste asset.','Senior Refurbisher','Dezelfde invoer; stap 1 en 2','CMP-02');
 step(b,'CMP-02',3,65,73,'Plaats fysiek en maak eenmaal aan');text(b,16,90,['Vergelijk de gekozen definitie of naam, het S/N en de conditie','met het fysieke onderdeel. Plaats het onderdeel in het apparaat.','Kies Create And Install eenmaal, onderaan het formulier.','Lees de melding; een nieuwe tag wordt automatisch toegekend.','Dubbelmelding? Open en vergelijk de bestaande registratie.','Identiteit onduidelijk? Wijzig niets; vraag Supervisor.'],{size:2.85,lh:4.1});figure(b,'cmp-create',116,113,76,'3A',[],19);
 step(b,'CMP-02',4,144,111,'Controleer identiteit en specificatie');text(b,16,170,['Controleer op het juiste asset: Tracked,','gegenereerde tag en het ingevoerde S/N.','Vergelijk met het echte onderdeel.','Controleer ook Specificatie op Info.','Is dit de bedoelde toevoeging of vervanging?'],{size:2.85,lh:4.1});figure(b,'cmp-result',116,152,76,'4A',['Volledige identificatie van het nieuwe record.'],48);
 note(b,16,205,176,42,'Extra terwijl je een vervanging bedoelde?',['De oude modelverwachting kan daarnaast blijven staan.','Laat Supervisor de registratie en de afgeleide specificatie controleren.','Maak geen tweede component om dit te herstellen.'], 'amber');
 done(b,'Het juiste onderdeel staat eenmaal op het asset; identiteit en specificatie kloppen.',262,14);related(b,['CMP-04','AST-02'],281);created.push({code:'CMP-02',pages:[a,b]});

 d=page('CMP-04',1,1,'Verplaats het juiste fysieke onderdeel met behoud van identiteit naar je tray.','Senior Refurbisher','Apparaat + onderdeel + tray','SC-01');
 step(d,'CMP-04',1,65,50,'Vergelijk het onderdeel en kies Naar tray');text(d,16,89,['Vergelijk naam, tag en S/N met het fysieke onderdeel.','Kies Naar tray op precies die componentkaart.','Identiteit onzeker? Wijzig niets; vraag Supervisor.'],{size:2.65,lh:3.8});figure(d,'cmp-result',121,72,71,'1A',[],31);figure(d,'cmp-to-tray',121,104,71,'1B',[],9);
 step(d,'CMP-04',2,121,56,'Controleer het bevestigingsvenster');text(d,16,145,['Controleer opnieuw naam en serienummer.','Het bestaande serienummer staat vergrendeld.','Een verplaatsing verandert de identiteit niet.','Andere identiteit? Annuleer en vraag Supervisor.'],{size:2.7,lh:3.8});figure(d,'cmp-tray-confirm',121,128,71,'2A',[],44);
 step(d,'CMP-04',3,183,34,'Verwijder fysiek en bevestig eenmaal');text(d,16,206,['Verwijder het onderdeel en plaats het in je eigen tray.','Kies Naar tray bevestigen eenmaal.'],{size:2.7,lh:3.8});figure(d,'cmp-tray-submit',121,190,71,'3A',[],20);
 step(d,'CMP-04',4,223,38,'Controleer status en loskoppeling');text(d,16,247,['Open het component en controleer In Tray en Asset: N.v.t.','Tag en S/N blijven behouden. Nog gekoppeld? Vraag hulp.'],{size:2.65,lh:3.8});figure(d,'cmp-tray-status',125,229,67,'4A',[],13);figure(d,'cmp-tray-asset',125,245,67,'4B',[],15);
 done(d,'Het onderdeel ligt in je tray en is digitaal van het asset losgekoppeld.',267,12);related(d,['CMP-01','WF-02'],282);created.push({code:'CMP-04',pages:[d]});

 d=page('USR-01',1,1,'Maak een lokaal account en draag de juiste toegang veilig over.','Admin / Superadmin','Naam + gewenste taak','AC-01');
 step(d,'USR-01',1,65,38,'Zoek de persoon; open Nieuwe gebruiker');text(d,16,88,['Open Personen > Toon Alles. Zoek naam, gebruikersnaam en e-mail.','Bestaat de persoon al? Laat de bestaande toegang controleren.','Anders: kies boven de lijst Nieuwe aanmaken.'],{size:2.6,lh:3.7});figure(d,'usr-nav',145,70,47,'1A',[],29);
 step(d,'USR-01',2,109,53,'Vul identiteit en tijdelijk wachtwoord in');text(d,16,132,['Vul naam en een echte, gecontroleerde e-mail in.','Gebruikersnaam: Voornaam + kleine eerste letters','van alle achternaamdelen; geen punten of spaties.','Jan de Vries wordt Jandv. Bestaat die? Vraag de uitzondering.','Kies Genereer; beide wachtwoordvelden worden gevuld.','Zet Deze gebruiker kan inloggen met toegangsbesluit aan.','Houd het tijdelijke wachtwoord alleen beschikbaar','voor persoonlijke overdracht.'],{size:2.45,lh:3.45});figure(d,'usr-credentials',108,136,84,'2A',[],23);
 step(d,'USR-01',3,168,48,'Kies de route voor de groep');text(d,16,190,['Superadmin: Optionele informatie > Groepen.','Kies de taakgroep, doorgaans Refurbisher.','Admin: sla eerst het account op. Laat Superadmin','het openen, de groep kiezen en Opslaan. Extra rechten?'],{size:2.45,lh:3.6});figure(d,'usr-group',108,177,84,'3A',[],25);chip(d,'USR-02',16,206,84);
 step(d,'USR-01',4,222,40,'Controleer en draag persoonlijk over');text(d,16,244,['Formulier nog open? Kies Opslaan. Open het account.','Controleer naam, gebruikersnaam, groep en login.','Geef het tijdelijke wachtwoord persoonlijk door;','de gebruiker volgt meteen AC-02.'],{size:2.45,lh:3.5});figure(d,'usr-saved',108,231,84,'4A',[],25);
 done(d,'Het juiste account en de toegang zijn gecontroleerd; de gebruiker gaat naar AC-02.',267,12);related(d,['AC-02','USR-02','USR-05'],282);created.push({code:'USR-01',pages:[d]});

 a=page('USR-02',1,2,'Kies groepen en directe rechten voor de taak van deze gebruiker.','Admin / Superadmin','Juiste gebruiker + gewenste toegang','AC-01');
 step(a,'USR-02',1,65,45,'Open en controleer het juiste account');text(a,16,89,['Personen > Toon Alles: zoek naam of gebruikersnaam.','Vergelijk beide; open het account en kies Gebruiker aanpassen.','Identiteit onzeker? Wijzig niets en vraag degene die toegang aanvraagt.'],{size:2.7,lh:3.8});figure(a,'usr-saved',125,72,67,'1A',[],20);figure(a,'usr-detail',125,94,67,'1B',[],12);
 step(a,'USR-02',2,116,54,'Groepen wijzigen: Superadmin');text(a,16,141,['Open op Informatie de balk Optionele informatie.','Superadmin kan bij Groepen toevoegen of verwijderen.','Ctrl+klik voegt een groep toe of deselecteert die.','Admin: laat een groepswijziging door Superadmin uitvoeren.','Iedere groep telt mee; verwijder ook oude, overbodige toegang.'],{size:2.7,lh:3.8});figure(a,'usr-group',125,127,67,'2A',[],28);
 step(a,'USR-02',3,176,85,'Kies directe uitzonderingen bewust');text(a,16,199,['Open Machtigingen. Overnemen gebruikt het gecombineerde groepsresultaat.','Toestaan geeft dit recht direct. Weigeren blokkeert ook een groeps-Toestaan.','Directe keuzes gaan voor groepen. Alleen Superadmin wijzigt Global: Super User.','Laat onduidelijke rechten op Overnemen; een herbruikbare taakgroep hoort in USR-05.'],{size:2.6,lh:3.8});figure(a,'usr-permissions-head',16,216,176,'3A',[],22);figure(a,'usr-permissions',16,242,176,'3B',['Voorbeeld: View en Create onder Assets; dezelfde drie kolommen.'],14);next(a,'Vervolg op bladzijde 2: controleer de taak, statustoegang, opslag en werking.');related(a,['USR-05','USR-04'],281);
 b=page('USR-02',2,2,'Vervolg: controleer wat de gebruiker echt mag doen, dan opslaan en testen.','Admin / Superadmin','Dezelfde gebruiker; stap 1 t/m 3','USR-02');
 step(b,'USR-02',4,65,72,'Controleer de taakrechten en sla de gebruiker op');text(b,16,89,['Zoek in Machtigingen de rechten voor de taak; klap de sectie zo nodig open.','Assets Create registreert een nieuw asset. Edit Quality Grade wijzigt kwaliteit; lezen is onvoldoende.','Onder Testing bepalen Execute Tests, Execute Senior Workflows en Execute Supervisor Workflows de uitvoering.','Start Additional Workflow Runs, Edit Workflow Runs en Override Workflow Guards zijn aparte rechten.','Geef alleen de benodigde toegang. Kies nu Opslaan op het gebruikersformulier.','Heropen de gebruiker en controleer groepen en directe keuzes voordat je een ander formulier opent.'],{size:2.8,lh:4.2});
 step(b,'USR-02',5,143,79,'Alleen bij een beperking door de assetstatus');text(b,16,167,['Ontbreekt het asset of de statuskeuze? Open Instellingen > Statuslabels en bewerk de juiste status.','Bij Gebruikersuitzonderingen toevoegen of vervangen selecteer je dezelfde gebruiker.','Moet die status gekozen kunnen worden? Zet Zien en Kiezen / gebruiken op Toestaan.','Overnemen volgt groepen; de gebruikersregel gaat voor. Overige actierechten en statusbeveiliging blijven gelden.','Kies Opslaan in het statusformulier als je een regel hebt gewijzigd.'],{size:2.65,lh:3.8});figure(b,'status-user',18,190,174,'5A',[],26);
 step(b,'USR-02',6,228,34,'Test de effectieve toegang');text(b,16,251,['Laat de gewone gebruiker opnieuw aanmelden en de bedoelde taak uitvoeren; test ook een uitgesloten taak.','Een Admin- of Superadmin-sessie bewijst die grens niet. Toegang te ruim? Controleer andere groepen,','directe Toestaan-keuzes en eventuele statusregels.'],{size:2.65,lh:3.8});done(b,'De opgeslagen keuzes geven de juiste gebruiker de bedoelde, geteste toegang.',267,12);related(b,['USR-05','USR-04'],282);created.push({code:'USR-02',pages:[a,b]});

 d=page('USR-03',1,1,'Reset een lokaal wachtwoord en laat de gebruiker direct een eigen kiezen.','Admin','Gebruiker + overdracht','AC-01');
 step(d,'USR-03',1,65,55,'Controleer de gebruiker');text(d,16,90,['Open Personen > Toon Alles; zoek naam of gebruikersnaam.','Vergelijk beide en open het juiste account.','Kies Gebruiker aanpassen.','Identiteit onzeker? Wijzig niets en vraag om verduidelijking.'],{size:2.9,lh:4.1});figure(d,'usr-saved',123,73,69,'1A',[],22);figure(d,'usr-detail',123,99,69,'1B',[],16);
 step(d,'USR-03',2,126,78,'Genereer en sla een tijdelijk wachtwoord op');text(d,16,150,['Kies Genereer eenmaal. Het tijdelijke wachtwoord verschijnt onder het eerste veld.','Beide wachtwoordvelden worden gevuld. Bewaar het alleen voor de persoonlijke overdracht.','Laat de bestaande loginstand en de rechten ongewijzigd.','Kies Opslaan eenmaal en controleer de succesmelding.','Zet het tijdelijke wachtwoord niet in chat, e-mail, notities, tickets of screenshots.'],{size:2.75,lh:3.9});figure(d,'usr-reset',95,175,97,'2A',[],25);
 step(d,'USR-03',3,210,50,'Draag persoonlijk over; gebruiker kiest zelf');text(d,16,233,['Geef het tijdelijke wachtwoord persoonlijk aan de gebruiker.','De gebruiker logt in en volgt meteen AC-02 voor een eigen wachtwoord.','Vraag nooit om dat definitieve eigen wachtwoord als bewijs.','Geen veilig overdrachtsmoment? Regel de overdracht eerst.'],{size:2.8,lh:3.9});chip(d,'AC-02',111,250,81);done(d,'De juiste gebruiker heeft de reset ontvangen en gaat direct door met AC-02.',267,12);related(d,['AC-02','USR-01'],282);created.push({code:'USR-03',pages:[d]});

 d=page('USR-04',1,1,'Schakel inloggen uit op het juiste bestaande account.','Admin','Besluit om toegang uit te zetten','AC-01');
 step(d,'USR-04',1,65,59,'Controleer het account');text(d,16,91,['Open Personen > Toon Alles. Zoek naam of gebruikersnaam.','Vergelijk beide en open de juiste gebruiker.','Kies Gebruiker aanpassen.','Account onduidelijk? Wijzig niets en vraag om verduidelijking.'],{size:2.9,lh:4.2});figure(d,'usr-saved',121,77,71,'1A',[],25);figure(d,'usr-detail',121,104,71,'1B',[],15);
 step(d,'USR-04',2,130,59,'Haal het vinkje voor inloggen weg');text(d,16,156,['Haal Deze gebruiker kan inloggen weg.','Kies Opslaan.','Laat wachtwoord, groepen en directe rechten ongewijzigd.'],{size:2.95,lh:4.3});figure(d,'usr-disable',111,172,81,'2A',[],12);
 step(d,'USR-04',3,195,63,'Controleer de opgeslagen stand');text(d,16,221,['Open na Opslaan de Info-tab.','Controleer nogmaals naam en gebruikersnaam.','Bij Login ingeschakeld moet Nee staan.','Het bestaande account en zijn historie blijven beschikbaar.'],{size:2.9,lh:4.1});figure(d,'usr-disabled',111,242,81,'3A',[],12);
 text(d,12,265,'Opslag mislukt of nog Ja? Lees de melding en laat dit corrigeren.',{size:2.7,fill:'#784200'});done(d,'Het juiste account bestaat nog; Login ingeschakeld staat opgeslagen op Nee.',273,12);created.push({code:'USR-04',pages:[d]});
}

function cat00Reality(){
 const d=page('CAT-00',4,6,'Begrijp modellen, definities, verwachtingen en fysieke registraties.','Supervisor','Catalogus + een asset','AC-01');title(d,'CAT-00','Deel 4: Verwachting tegenover werkelijkheid','Dit zijn verschillende betekenissen; geen verplichte opeenvolgende stappen.');
 note(d,12,86,90,115,'Assumed: een verwachting',['De modelbaseline verwacht dit onderdeel.','Er is nog geen eigen fysieke registratie','voor deze rij. Controleer wat echt aanwezig is.']);
 figure(d,'cmp-assumed',17,119,80,'4A',['Model baseline en Assumed op het asset.'],69);
 note(d,108,86,90,115,'Tracked: een fysiek record',['Dit onderdeel heeft een eigen tag en S/N.','Vergelijk die met het werkelijke onderdeel.','Een Tracked record kan ook een Extra zijn.']);
 figure(d,'cmp-result',113,119,80,'4B',['Geregistreerde identiteit; hier ook Extra.'],69);
 note(d,12,209,186,43,'Een registratie vervangt een verwachting niet vanzelf',['Een extra Tracked record kan naast de Assumed baseline blijven staan. Controleer het bedoelde effect.','Removed betekent dat de verwachting van die plaats is verwijderd; het is geen gewone statusstap.','Een assetstatus wijzigen maakt van een Assumed rij geen fysiek geregistreerd component.']);
 next(d,'Volgende deel: directe feiten en componenten vormen samen de specificatie.',263);related(d,['CAT-02','CMP-02'],280);return d.render();
}
function cat00Spec(){
 const d=page('CAT-00',5,6,'Begrijp hoe directe feiten en onderdelen de specificatie vormen.','Supervisor','Modelspecificatie','CAT-00');title(d,'CAT-00','Deel 5: Hoe een modelspecificatie ontstaat');
 note(d,12,84,186,45,'Direct attribuut: een feit over de hele variant',['Bijvoorbeeld Introductiejaar = 2021 of de schermdiagonaal.','Het is geen afzonderlijk verplaatsbaar onderdeel.','De waarde gebruikt een herbruikbare attribuutdefinitie.']);figure(d,'spec-detail',130,88,62,'5A',[],36);
 d.rect(12,135,186,76,'#FFFFFF',C.line,.45,1.5);text(d,16,143,'Verwacht component: een los onderdeel in de fabrieksbaseline',{size:3.3,weight:850,fill:C.cat});text(d,16,152,['Bijvoorbeeld 1 x RAM 8 GB DDR4. De hoeveelheid en bijdragen bepalen de afgeleide waarde.'],{size:2.7});figure(d,'spec-component',18,159,174,'5B',[],46);
 note(d,12,217,90,39,'Sum: capaciteit of aantal',['Rekenvoorbeeld: 2 x 8 GB geeft 16 GB.','Het aantal van het onderdeel telt mee.','Gebruik dit voor optelbare hoeveelheden.']);
 note(d,108,217,90,39,'Keep distinct values: eigenschap',['2 x 3200 MHz blijft 3200 MHz.','3200 en 2666 MHz blijven beide zichtbaar.','Herhaling maakt een eigenschap niet groter.']);
 text(d,12,263,'Voer dezelfde eigenschap niet nogmaals in als een concurrerende directe waarde.',{size:2.65,fill:'#784200'});next(d,'Volgende deel: kies de taakgids. De attribuutregel zelf wordt beheerd in CAT-03.',268);related(d,['CAT-02','CAT-03'],282);return d.render();
}
function cat02Check(){
 const d=page('CAT-02',5,6,'Controleer de bronnen en berekening voordat je de baseline opslaat.','Supervisor','Exact modelnummer + bron','CAT-02');title(d,'CAT-02','5 Controleer afgeleide waarden en overlap');
 text(d,16,87,['Controleer bij elk Expected Component de definitie, Stks, Derived attributes en eventuele kinderen.','De bijdragevlaggen van de definitie bepalen welke eigenschappen in de specificatie terechtkomen.'],{size:2.9,lh:4.2});figure(d,'spec-children',18,102,174,'5A',['Compleet voorbeeld: afgeleide waarden en de verwachte onderdelen van het moederbord.'],75);
 note(d,12,184,90,45,'Controleer de numerieke betekenis',['Capaciteit/aantal: Sum telt waarde x aantal.','Eigenschap: Keep distinct values behoudt','afzonderlijke waarden, zonder dubbeling.','Een kindbijdrage gaat voor dezelfde waarde','van de bovenliggende definitie.']);
 note(d,108,184,90,45,'Een attribuut ontbreekt in de keuzelijst?',['Controleer eerst de componentbijdragen.','Een al door componenten geleverd feit','wordt niet ook als handmatig feit aangeboden.','Verwijder geen correcte bron om de keuze','weer zichtbaar te maken.']);
 text(d,16,239,['Corrigeer een nieuwe dubbele of verkeerde invoer voor Opslaan. Bij een conflict gaat de componentwaarde voor.','Een al opgeslagen rij verwijderen vraagt Admin en CAT-05. Een verkeerde definitie corrigeer je via CAT-04.'],{size:2.65,lh:3.8});chip(d,'CAT-05',16,248,85);chip(d,'CAT-04',107,248,85);next(d,'Volgende pagina: controleer de hele baseline, sla op en heropen het resultaat.',264);related(d,['CAT-03','CAT-06'],281);return d.render();
}
function cat03Aggregation(){
 const d=page('CAT-03',3,6,'Kies de betekenis van numerieke bijdragen voordat je de definitie opslaat.','Supervisor','Betekenis + eenheid + bron','CAT-00');title(d,'CAT-03','3 Kies hoe componentgetallen samenkomen','Alleen bij Int of Decimal. Ander datatype? Ga naar deel 4: Gedrag.');
 text(d,16,88,['Scroll naar Numeric Component Aggregation. Dit bepaalt de berekening van componentwaarden.','Component Spec Display bepaalt daarna hoe de uitkomst wordt getoond; dat is een andere keuze.'],{size:2.85,lh:4});figure(d,'attr-aggregation',18,101,174,'3A',['Nieuwe numerieke definitie: kies de regel voordat je haar voor het eerst opslaat.'],41);
 note(d,12,154,90,45,'Sum values by quantity',['Gebruik voor optelbare capaciteit of aantallen.','2 x 8 GB geeft samen 16 GB.','Het voorbeeld Aantal geheugenslots telt','aantallen op wanneer componenten ze leveren.']);
 note(d,108,154,90,45,'Keep distinct values',['Gebruik voor een gedeelde eigenschap.','2 x 3200 MHz blijft 3200 MHz.','Bij 3200 en 2666 MHz blijven beide staan.','Aantallen vermenigvuldigen deze waarde niet.']);
 figure(d,'attr-distinct',18,207,174,'3B',['Ander voorbeeld: Geheugensnelheid gebruikt Keep distinct values.'],30);
 text(d,16,247,['Wordt de definitie al door componenten gebruikt? Een andere regel verandert bestaande uitkomsten.','Dat is Admin-onderhoud via CAT-05; pas de regel niet als gewone veldcorrectie aan.'],{size:2.7,lh:3.8});next(d,'Volgende deel: bepaal categorie, verplichting, overrides en weergave.',264);related(d,['CAT-05','CAT-04'],281);return d.render();
}
function cat01Search(){
 const d=page('CAT-01',1,5,'Hergebruik of maak de juiste productroute en exacte fabrikantvariant.','Supervisor','Productlabel + bron','CAT-00');title(d,'CAT-01','Zoek de bestaande catalogusroute');
 step(d,'CAT-01',1,85,48,'Open Asset modellen');text(d,16,109,['Open Instellingen > Asset modellen.','Hier staan herbruikbare Basismodellen.','Fysieke apparaten staan onder Apparaten.'],{size:2.8,lh:4});figure(d,'cat-nav',139,92,53,'1A',[],34);
 step(d,'CAT-01',2,139,122,'Controleer product en volledige code op duplicaten');text(d,16,163,['Zoek eerst fabrikant, product en generatie bij Asset modellen (2A).','Vergelijk de gevonden rij voordat je iets aanmaakt.'],{size:2.75,lh:3.9});figure(d,'models',18,171,174,'2A',[],51);
 text(d,16,226,'Open ook Instellingen > Model Numbers en zoek de complete geprinte code (2B).',{size:2.75});figure(d,'numbers',18,232,174,'2B',[],27);
 next(d,'Volgende pagina: kies de bestaande of ontbrekende route op basis van je zoekresultaat.',267);related(d,['CAT-00','CAT-06'],282);return d.render();
}
function cat04Check(){
 const d=page('CAT-04',5,6,'Controleer bijdragen en verwachte onderdelen voordat je de definitie opslaat.','Supervisor','Onderdeeltype + definities','CAT-00');title(d,'CAT-04','5 Controleer overlap, aantallen en uitkomst');
 note(d,12,85,186,40,'Laat ieder feit uit de bedoelde bron komen',['Een attribuut op een verwacht kind gaat voor hetzelfde attribuut op de bovenliggende definitie.','Controleer daarom de bijdragen op beide niveaus. Verwijder of corrigeer onbedoelde overlap.','Zie je een overlapmelding? Controleer de genoemde bron voordat je opslaat.']);
 figure(d,'def-values',18,134,174,'5A',['Controleer complete bijdragen en de twee displaykeuzes.'],85);
 text(d,16,229,['Controleer ook de aantallen van de verwachte kinderen. Voor getallen geldt de regel van het attribuut:','Sum telt op met de hoeveelheid; Keep distinct values behoudt unieke waarden. CAT-03 beheert die regel.'],{size:2.75,lh:3.9});
 text(d,16,246,'Alles klopt? Kies Create Definition of Save Changes. Controleer het resultaat op de volgende pagina.',{size:2.7});figure(d,'def-save-action',123,251,69,'5B',[],14);next(d,'Volgende pagina: zoek de opgeslagen definitie, controleer haar en keer terug naar je taak.',269);related(d,['CAT-03','CAT-05'],282);return d.render();
}
function cat05Index(){
 const d=page('CAT-05',1,9,'Kies de onderhoudstaak die bij je huidige scherm en wijziging hoort.','Admin','Bestaand object + bekende impact','CAT-00');title(d,'CAT-05','Kies een onderhoudstaak','De routes zijn alternatieven. Lees alleen de passende pagina; andere routes zijn geen vervolgstappen.');
 const rows=[['A','Code actief of verouderd zetten','Model Numbers: status van een exacte code',2],['B','Standaardcode kiezen of ongebruikte code verwijderen','Model Numbers: standaard / lege ongebruikte code',3],['C','Een opgeslagen regel uit de modelspecificatie halen','Edit Spec: direct attribuut of verwacht component',4],['D','Een attribuut verbergen of terugzetten','Attributes: de hele herbruikbare definitie',5],['E','Een bestaande Enum-keuze onderhouden','Attributes: een bestaande optie, Value en Label',6],['F','Een componentdefinitie uitzetten of herstellen','Component Definitions: Active / Deactivate',7],['G','Een kind of bijdrage uit een definitie halen','Component Definitions: een opgeslagen relatie',8],['H','Numerieke aggregatie bij bestaand gebruik wijzigen','Attributes: Numeric Component Aggregation',9]];
 rows.forEach(([key,heading,sub,p],i)=>{const y=86+i*21.5;d.rect(12,y,186,19,'#FFFFFF',C.line,.4,1.4);d.stepBadge(21,y+9.5,key,C.cat);text(d,32,y+7,heading,{size:2.9,weight:800});text(d,32,y+13,sub,{size:2.45,fill:C.muted});text(d,192,y+11,'blz. '+p,{size:2.55,weight:800,fill:C.cat,anchor:'end'});});
 text(d,12,268,['Onbekende identiteit of gevolgen? Wijzig nog niets; laat de impact eerst vaststellen.','Normaal aanmaken blijft in CAT-01 t/m CAT-04; CAT-06 controleert een onzekere bron.'],{size:2.65,lh:3.8,fill:'#784200'});related(d,['CAT-01','CAT-06'],282);return d.render();
}
function cat05Aggregation(){
 const d=page('CAT-05',9,9,'Onderhoudsroute H: een numerieke regel met bestaand componentgebruik.','Admin','Attribuut + oude regel + impact','CAT-00');title(d,'CAT-05','H Numerieke aggregatie bij bestaand gebruik');
 step(d,'CAT-05',1,85,60,'Bepaal betekenis en alle geraakte bronnen');text(d,16,109,['Open Instellingen > Attributes; kies het attribuut en Edit. Noteer de oude regel en model- en assetvoorbeelden.','Controleer bronnen, kinderen, aantallen, vlaggen en gebruik. Gevolgen onbekend? Stel de wijziging uit.'],{size:2.75,lh:4});figure(d,'maint-inuse',82,119,110,'1A',[],24.4);
 step(d,'CAT-05',2,151,57,'Kies de bedoelde regel alleen als die moet wijzigen');text(d,16,175,['Bij Numeric Component Aggregation: Sum telt capaciteit of aantallen maal de hoeveelheid.','Keep distinct values behoudt eigenschappen zoals MHz.','Is de huidige betekenis juist? Laat de regel staan.','In dit voorbeeld blijft Werkgeheugen op Sum staan.'],{size:2.8,lh:4});figure(d,'maint-inuse-aggregation',82,193,110,'2A',[],12);
 step(d,'CAT-05',3,214,47,'Sla een wijziging op; heropen en vergelijk');text(d,16,238,['Na een wijziging: Opslaan, heropen en controleer dezelfde regel en voorbeelden.','De regel werkt direct door in bestaande model- en assetspecificaties.','Onverwachte uitkomst? Herstel de genoteerde oude regel, controleer opnieuw','en bespreek de oorzaak met de cataloguseigenaar.','Een juiste bestaande regel hoef je niet opnieuw op te slaan.'],{size:2.65,lh:3.8});done(d,'De regel past bij het feit; dezelfde model- en assetvoorbeelden zijn gecontroleerd.',267,12);related(d,['CAT-02','CAT-03','CAT-04'],282);return d.render();
}
function cat05SpecificationRemoval(){
 const d=page('CAT-05',4,9,'Onderhoudsroute C: verwijder een bedoelde opgeslagen rij uit de modelspecificatie.','Admin','Exact modelnummer + impact','CAT-00');title(d,'CAT-05','C Een rij verwijderen uit Edit Spec');
 text(d,16,85,['Direct attribuut: ook de bijbehorende assetoverrides van dit modelnummer worden verwijderd.','Open de geraakte assets: Specificatie > Override; bewaar identiteit en oude afwijkende waarden.','Verwacht component: bewaar aantal, afgeleide waarden en kinderen.','Geen volledige lijst of onduidelijke gevolgen? Stel de verwijdering uit.'],{size:2.65,lh:3.8});
 step(d,'CAT-05',1,103,123,'Kies een rijsoort: A OF B');text(d,16,126,'A - Direct attribuut: controleer de geselecteerde naam en waarde; kies alleen op die rij de rode X.',{size:2.65});
 figure(d,'maint-spec-attribute',18,133,110,'1A',[],33);text(d,134,143,['De andere rijen blijven staan.','Open de juiste modelcode','via Model Numbers > Edit Spec.'],{size:2.4,lh:3.7});
 text(d,16,175,'OF B - Verwacht component: controleer definitie en aantal; kies alleen daar Remove.',{size:2.65});figure(d,'maint-spec-component',18,182,174,'1B',[],36);
 step(d,'CAT-05',2,232,31,'Sla op, heropen en controleer');figure(d,'maint-spec-save',108,239,84,'2A',[],11);
 text(d,16,251,['Kies Opslaan; heropen dezelfde variant.','Controleer de andere rijen en alle geraakte assets.','Een teruggezet attribuut herstelt geen oude overrides.'],{size:2.6,lh:3.8});
 done(d,'Alleen de bedoelde rij is weg; de vooraf bepaalde gevolgen zijn gecontroleerd.',267,12);related(d,['CAT-02','CAT-04'],282);return d.render();
}
function cat05Relationship(){
 const d=page('CAT-05',8,9,'Onderhoudsroute G: verwijder alleen de bedoelde opgeslagen relatie.','Admin','Definitie + bekende impact','CAT-00');title(d,'CAT-05','G Een kind OF bijdrage uit een definitie halen');
 text(d,16,86,['Bewaar de oude definitie, aantallen, Required en bijdragevlaggen; controleer alle geraakte modellen en assets.','Instances/Templates tonen niet alle kindrelaties. Onvolledig beeld? Stel de verwijdering uit.','Remove is pas definitief na Save Changes. Verwijder alleen de relatie die werkelijk weg moet.'],{size:2.65,lh:3.8});
 step(d,'CAT-05',1,104,121,'Kies een van beide: kind OF attribuutbijdrage');text(d,16,127,'Open Component Definitions, zoek de juiste definitie en kies Edit. Gebruik Remove op precies de bedoelde rij.',{size:2.65});
 figure(d,'maint-child-row',18,137,174,'1A',['Kind verwijderen: controleer definitie, naam, aantal en Required op deze rij.'],32);
 figure(d,'maint-contribution-row',18,181,174,'1B',['OF een attribuutbijdrage verwijderen: controleer attribuut, waarde en bijdragevlaggen.'],33);
 step(d,'CAT-05',2,231,31,'Sla op, heropen en controleer');text(d,16,251,['Kies Save Changes; heropen dezelfde definitie.','Controleer dat alleen die relatie weg is.','Controleer de geraakte modellen en assets.'],{size:2.65,lh:3.8});figure(d,'maint-save-action',125,238,67,'2A',[],14);
 done(d,'Alleen de bedoelde relatie is verwijderd; de geraakte uitkomsten zijn gecontroleerd.',267,12);related(d,['CAT-02','CAT-04'],282);return d.render();
}
function usr05Choice(){
 const d=page('USR-05',1,3,'Hergebruik of wijzig een rechtengroep; controleer daarna de toegang van een gebruiker.','Superadmin','Taak + testgebruiker','AC-01');
 step(d,'USR-05',1,65,79,'Open Groepen en vergelijk de taak');text(d,16,89,['Bepaal welke taken de groep wel en niet mag uitvoeren.','Open rechtsboven Beheerder Instellingen via het tandwiel (1A); kies Groepen (1B).','Vergelijk in de lijst naam en taak van een kandidaatgroep; open die groepsnaam.'],{size:2.8,lh:4});
 figure(d,'group-gear',18,109,88,'1A',[],22);figure(d,'group-nav',130,109,54,'1B',[],28);
 step(d,'USR-05',2,150,109,'Kies precies een van deze routes');figure(d,'group-overview',18,173,174,'2A',[],23);
 text(d,16,207,['A - Pakket gelijk aan de gewenste taak? Hergebruik de groep ongewijzigd.','Wijs haar toe via USR-01 stap 3 of USR-02 stap 2; je hoeft de groep niet op te slaan.','B - Moet de taak voor alle leden veranderen? Kies Groep bewerken en ga naar pagina 2.','C - Ontbreekt het juiste pakket? Terug naar de lijst, Nieuwe aanmaken, dan pagina 2.'],{size:2.75,lh:5.2});
 text(d,16,248,'Een uitzondering voor een lid regel je bij die gebruiker met USR-02.',{size:2.7,weight:800,fill:C.muted});next(d,'Alleen bij aanmaken of bewerken: ga verder op pagina 2.',266);related(d,['USR-01','USR-02'],281);return d.render();
}
function usr05Rights(){
 const d=page('USR-05',2,3,'Stel een herkenbaar pakket in en controleer de taakgrens voor alle leden.','Superadmin','Taak + testgebruiker','AC-01');title(d,'USR-05','3 en 4: Stel het rechtenpakket bewust in');
 step(d,'USR-05',3,85,44,'Geef de groep een herkenbare naam');text(d,16,109,['Voorbeeld: Catalogus lezen. Noteer de taak en grenzen.','Gebruik voor een afwijkend pakket een eigen groepsnaam.','Standaardgroepen kunnen vereiste rechten terugkrijgen.'],{size:2.7,lh:3.8});figure(d,'group-top',16,120,176,'3A',[],8.8);
 step(d,'USR-05',4,133,79,'Geef alleen de rechten voor die taak');text(d,16,157,['Voorbeeld: alleen Models View toestaan; Create en Edit weigeren.','Laat alle andere rechten uit, ook Admin en Super User.','De sectieradio kiest alle rijen; de titel klapt ze open of dicht.','Groepen tellen op: Toestaan uit een groep volstaat.','Een Weigeren in een andere groep blokkeert dat niet.','Een directe gebruikerskeuze gaat voor; direct Weigeren blokkeert groeps-Toestaan.'],{size:2.6,lh:3.8});figure(d,'group-models',107,184,100,'4A',[],24);text(d,107,180,'Links Toestaan | rechts Weigeren',{size:2.3,weight:800,fill:C.muted});
 note(d,12,218,186,43,'Alleen als deze groep met assets of statussen werkt',['Algemene actierechten en statustoegang zijn verschillende grenzen.','Controleer onder Instellingen > Statuslabels: Zien en Kiezen / gebruiken.','Een gebruikersregel gaat voor groepsregels; kiezen vraagt beide toegangen.','Test die grens straks met een gewone gebruiker. Admin/Superadmin kunnen haar omzeilen.']);next(d,'Volgende pagina: Opslaan, heropenen en toegestane en verboden taken testen.',267);related(d,['USR-02','USR-01'],281);return d.render();
}
function cat06Result(){
 const d=page('CAT-06',3,3,'Volg de uitkomst van de vraag waarmee je deze gids begon.','Supervisor','Dezelfde vraag + fabrikantbron','CAT-00');title(d,'CAT-06','3 Kies de passende uitkomst','Deze uitkomsten zijn alternatieven; volg alleen de route die je vraag oplost.');
 note(d,12,87,186,46,'A - De volledige modelcode is bevestigd',['Gebruik fabrikant, Basismodel en de complete code inclusief suffix.','Keer terug naar zoeken of invoeren van die identiteit.']);chip(d,'CAT-01',16,120,92);
 note(d,12,139,186,46,'B - Dit ene specificatiefeit is bevestigd',['Voer alleen het onderbouwde feit in met de juiste definitie en eenheid.','Een familie-optie, maximum of later gewijzigde hardware is geen fabrieksbaseline.']);chip(d,'CAT-02',16,172,92);
 note(d,12,191,186,67,'C - Nog niet bevestigd',['Vereiste identiteit onzeker? Stop de aanmaak; verzin geen placeholdercode.','Alleen een optioneel feit onzeker? Laat alleen dat veld leeg.','In dit voorbeeld blijft RAM voor 2E9F8EA#ABH open; de familiepagina bewijst die variant niet.','Geef label, bron en de precieze open vraag aan de cataloguseigenaar.','Gebruik een andere bron pas na diens besluit.'], 'amber');
 done(d,'De bevestigde uitkomst is teruggebracht of de open vraag is overgedragen.',264,12);related(d,['CAT-03','CAT-04','CAT-05'],282);return d.render();
}

const imageMap={
 'CAT-00':[[],['model'],['attrs','definitions'],[],[],[]],
 'CAT-01':[['cat-nav','models','numbers'],['model','models-create-action'],['model-create'],['model-entry','number-create'],['model','model-info']],
 'CAT-02':[['model','spec-top'],['spec-detail','spec-component'],['spec-available','spec-detail'],['spec-component','spec-add'],[],['spec-save','spec-top']],
 'CAT-03':[['cat-nav','attrs'],['attr-identity'],['attr-behavior'],['attr-constraints','attr-options'],['attr-save','attr-result']],
 'CAT-04':[['cat-nav','definitions'],['def-identity'],['def-children'],['def-values'],[],['definitions']],
 'CAT-05':[[],['maint-numbers','maint-number-edit'],['maint-number-default'],['maint-spec-attribute','maint-spec-component','maint-spec-save'],['maint-enum-inuse','maint-attrs'],['maint-options'],['maint-definitions'],[]],
 'USR-05':[['group-nav','group-overview'],[],['group-save','group-detail']],
 'CAT-06':[[],[],[]],
};
async function buildLong(browser,filter){
 for(const code of ['USR-05','CAT-00','CAT-01','CAT-02','CAT-03','CAT-04','CAT-05','CAT-06']){
  if(filter&&!filter.includes(code))continue;
  const g=inputs.find(x=>x.code===code);const p=await browser.newPage();await p.goto(pathToFileURL(path.join(round,g.input)).href);await p.evaluate(()=>document.fonts.ready);
  let pages=await p.evaluate(({code,version,mapping,imgs,marks})=>{
   const list=[...document.querySelectorAll('.page>svg')];
   const setLines=(n,lines)=>{const ns='http://www.w3.org/2000/svg',x=n.getAttribute('x');const old=n.querySelectorAll('tspan');const lh=old[1]?.getAttribute('dy')??'2.9';n.innerHTML='';lines.forEach((s,i)=>{const t=document.createElementNS(ns,'tspan');t.setAttribute('x',x);t.setAttribute('dy',i?lh:'0');t.textContent=s;n.append(t);});};
   return list.map((s,i)=>{
    s.querySelectorAll('[data-component="focus-mark"]').forEach(n=>n.remove());
    [...s.children].forEach(n=>{if(['defs'].includes(n.tagName))return;try{const b=n.getBBox();if(b.x>=174&&b.y>=260&&b.width<=35&&b.height<=37&&!n.textContent.match(/^Pagina /))n.remove();}catch{}});
    s.querySelectorAll('text').forEach(n=>{
     const t=n.textContent;
     if(/QR volgt|Digitale gids|Proef - QR|^In voorbereiding$|^Werkconcept$/.test(t)){n.remove();return;}
     const lines=[...n.querySelectorAll('tspan')].map(t=>t.textContent);const a=lines.length?lines:[t];
     const revised=a.map(v=>v.replaceAll('\uFFFD','e').replace(/Draft v\d+/g,'Concept v'+version).replace(/2026-09-\d{2}/g,'2026-10-20').replace('CAT-05 Varianten en lifecycle beheren','CAT-05 Bestaande catalogus aanpassen').replace('CAT-06 Catalogus controleren en bronnen','CAT-06 Productgegevens controleren').replace('CAT-05 volgt nog','volg CAT-05').replace('CAT-03 volgt nog','volg CAT-03').replace('CAT-04 volgt nog','volg CAT-04').replace('gids volgt','gids beschikbaar'));
     if(JSON.stringify(a)!==JSON.stringify(revised))setLines(n,revised);
    });
    const replacements=mapping[i]??[];const nodes=[...s.querySelectorAll('image')];
    if(replacements.length) {if(nodes.length!==replacements.length)throw new Error(code+' p'+(i+1)+': image mapping count '+nodes.length+' vs '+replacements.length);}
    nodes.forEach((node,j)=>{
     const key=replacements[j];if(!key)return;
     const match=node.getAttribute('clip-path')?.match(/#([^)]*)/),clip=match?s.querySelector('[id="'+match[1]+'"] rect'):null;
     const previous=node.previousElementSibling,frame=previous?.tagName==='rect'&&previous.getAttribute('stroke')==='#C8D5E2'?previous:clip;
     const b=frame?{x:+frame.getAttribute('x'),y:+frame.getAttribute('y'),w:+frame.getAttribute('width'),h:+frame.getAttribute('height')}:{x:+node.getAttribute('x'),y:+node.getAttribute('y'),w:+node.getAttribute('width'),h:+node.getAttribute('height')};
     if(code==='CAT-01'&&i===3&&j===0){
      Object.assign(b,{x:23,y:105,w:165,h:18.3});
      for(const r of s.querySelectorAll('rect'))if(r.getAttribute('x')==='105'&&r.getAttribute('y')==='90'&&r.getAttribute('width')==='83'&&r.getAttribute('height')==='26.5')Object.entries({x:b.x,y:b.y,width:b.w,height:b.h}).forEach(([k,v])=>r.setAttribute(k,v));
      for(const g of s.querySelectorAll('[data-component="image-badge"]'))if(g.textContent.trim()==='5A'){g.querySelector('circle').setAttribute('cx','186.8');g.querySelector('circle').setAttribute('cy','106.2');g.querySelector('text').setAttribute('x','186.8');g.querySelector('text').setAttribute('y','106.2');}
      for(const t of s.querySelectorAll('text'))if(t.textContent.startsWith('Open de exacte variant vanaf')){t.setAttribute('x','23');t.setAttribute('y','127.3');t.querySelectorAll('tspan').forEach(n=>n.setAttribute('x','23'));}
     }
     node.setAttribute('href',imgs[key].href);node.setAttribute('x',b.x);node.setAttribute('y',b.y);node.setAttribute('width',b.w);node.setAttribute('height',b.h);node.setAttribute('preserveAspectRatio','xMidYMid meet');node.removeAttribute('clip-path');node.dataset.recognitionSource=key;
     for(const m of marks[key]??[]){const ns='http://www.w3.org/2000/svg',scale=Math.min(b.w/imgs[key].width,b.h/imgs[key].height),x=b.x+(b.w-imgs[key].width*scale)/2+(m.x-(m.padding??0))*scale,y=b.y+(b.h-imgs[key].height*scale)/2+(m.y-(m.padding??0))*scale,w=(m.w+2*(m.padding??0))*scale,h=(m.h+2*(m.padding??0))*scale;const e=document.createElementNS(ns,m.shape==='ellipse'?'ellipse':'rect');const attrs=m.shape==='ellipse'?{cx:x+w/2,cy:y+h/2,rx:w/2,ry:h/2}:{x,y,width:w,height:h,rx:1};Object.entries({...attrs,fill:'none',stroke:'#E83448','stroke-width':1.15,'vector-effect':'non-scaling-stroke','data-component':'focus-mark','data-target':m.target}).forEach(([k,v])=>e.setAttribute(k,v));s.append(e);}
    });
    return s.outerHTML;
   });
  },{code,version:g.nextVersion,mapping:imageMap[code],imgs:images,marks:Object.fromEntries(sources.map(s=>[s.key,s.marks??[]]))});
  await p.close();
  if(code==='CAT-00'){pages[3]=cat00Reality();pages[4]=cat00Spec();}
  if(code==='CAT-01')pages[0]=cat01Search();
  if(code==='CAT-02')pages[4]=cat02Check();
  if(code==='CAT-04')pages[4]=cat04Check();
  if(code==='USR-05'){pages[0]=usr05Choice();pages[1]=usr05Rights();}
  if(code==='CAT-03')pages.splice(2,0,cat03Aggregation());
  if(code==='CAT-05'){pages[0]=cat05Index();pages[3]=cat05SpecificationRemoval();pages[7]=cat05Relationship();pages.push(cat05Aggregation());}
  if(code==='CAT-06')pages[2]=cat06Result();
  const q=await browser.newPage();await q.setContent(htmlFor(pages));await q.evaluate(()=>document.fonts.ready);
  await q.evaluate(({code,total})=>{
   const pages=[...document.querySelectorAll('.page>svg')],ns='http://www.w3.org/2000/svg';
   const update=(n,v)=>{const spans=n.querySelectorAll('tspan');if(spans.length===1){spans[0].textContent=v;}else n.textContent=v;};
   pages.forEach((s,i)=>{
    for(const n of s.querySelectorAll('text')){
     let t=n.textContent;
     if(/^Pagina \d+ van \d+$/.test(t))update(n,`Pagina ${i+1} van ${total}`);
     if(code==='CAT-03'&&i>=3){if(t===String(i))update(n,String(i+1));if(new RegExp('^'+i+'[AB]$').test(t))update(n,String(i+1)+t.slice(1));}
     if(code==='CAT-06'&&i===1&&t==='1')update(n,'2');
     if(code==='CAT-02'&&i===2&&t.startsWith('Voorbeeld: voeg 5G-ondersteuning'))update(n,'Staat het feit al bij Selected Attributes? Selecteer die rij. Anders: zoek links en kies +.');
     if(code==='CAT-02'&&i===2&&t.startsWith('Zoek bij Available Attributes'))update(n,'Zoek bij Available Attributes; kies + bij een nog niet toegevoegde definitie.');
     if(code==='CAT-02'&&i===2&&t.startsWith('Selecteer de nieuwe rij'))update(n,'Selecteer de toegevoegde of bestaande rij; vul rechts de bevestigde waarde in.');
     if(code==='CAT-02'&&i===5&&t.startsWith('Controleer de bevestiging'))update(n,'Lees de melding; heropen dezelfde variant en controleer alle opgeslagen waarden en aantallen.');
     if(code==='CAT-03'&&i===1&&t.startsWith('bepaal categorie'))update(n,'Int/Decimal: kies de berekening op pagina 3. Ander type: ga naar Gedrag op pagina 4.');
     if(code==='CAT-03'&&i===4&&t.startsWith('Kies A voor Int/Decimal'))update(n,'Kies A voor Int/Decimal OF B voor Enum. Bij Bool/Text: sla A en B over en ga naar pagina 6.');
     if(code==='CAT-03'&&i===5&&t.startsWith('De lijst bevestigt'))update(n,'Controleer de opgeslagen betekenis en regels; keer terug naar de oorspronkelijke taak.');
     if(code==='CAT-03'&&i===5&&t.startsWith('Voor Opslaan:')){
      const spans=n.querySelectorAll('tspan');const lines=['Voor Opslaan: Label, systeemnaam, datatype, eenheid, categorie, gedrag, berekening en invoerregels kloppen.','Na Opslaan: controleer dezelfde definitie en alle instellingen; zoek haar ook terug in de lijst.'];
      spans.forEach((sp,j)=>{sp.textContent=lines[j]??'';});
     }
     if(code==='CAT-03'&&i===5&&t.startsWith('Controleer de opgeslagen kolommen'))update(n,'Werkelijk opgeslagen numeriek voorbeeld; controleer ook de lijst en het overige gedrag.');
     if(code==='CAT-05'&&i>0&&i<8&&new RegExp('^'+(i+1)+' ').test(t))update(n,String.fromCharCode(64+i)+t.slice(String(i+1).length));
     if(code==='CAT-05'&&i===2&&t.startsWith('Links: Instellingen > Model Numbers'))update(n,'Instellingen > Model Numbers. Kies A (standaard) OF B (verwijderen); alleen de passende taak.');
     if(code==='CAT-05'&&i===2&&n.closest('[data-component="step-badge"]')){if(t==='1')update(n,'A');if(t==='2')update(n,'B');}
     if(code==='CAT-04'&&i===2&&t.startsWith('Wel vaste onderdelen?'))update(n,'Wel vaste onderdelen? Klik Add Expected Subcomponent. RAM zonder vaste kinderen: ga naar pagina 4.');
     if(code==='CAT-04'&&i===2&&t.startsWith('Na Add Expected Subcomponent:'))update(n,'Ander voorbeeld: een moederbord verwacht poorten. Kies definitie, naam, aantal, Required en Notes.');
     if(code==='CAT-06'&&i===1){if(n.getAttribute('fill')==='#E83448')n.setAttribute('fill','#784200');}
    }
    if(code==='CAT-06'&&i===1){
     for(const n of s.querySelectorAll('[fill="#FFF1F2"], [fill="#FFF1F3"]'))n.setAttribute('fill','#FFF7ED');
     for(const n of s.querySelectorAll('[stroke="#FDA4AF"], [stroke="#E83448"]'))n.setAttribute('stroke','#F6C36A');
     for(const g of s.querySelectorAll('[data-component="image-badge"]')){const label=g.textContent.trim(),x=label==='2A'?96:187.8,y=label==='2A'?100.2:96;g.querySelector('circle').setAttribute('cx',x);g.querySelector('circle').setAttribute('cy',y);g.querySelector('text').setAttribute('x',x);g.querySelector('text').setAttribute('y',y);}
    }
    const badgePositions=code==='USR-05'&&i===2?{'5B':[186,98.2]}:code==='USR-05'&&i===1?{'4A':[190.8,180]}:code==='CAT-02'&&i===2?{'3A':[187.8,103.2]}:code==='CAT-03'&&i===0?{'1B':[187.8,158.2]}:code==='CAT-04'&&i===1?{'2A':[187.8,95.2]}:code==='CAT-05'&&i===4?{'5B':[187.8,171.5]}:code==='CAT-05'&&i===5?{'6A':[187.8,95.2]}:{};
    for(const g of s.querySelectorAll('[data-component="image-badge"]')){const pos=badgePositions[g.textContent.trim()];if(pos){g.querySelector('circle').setAttribute('cx',pos[0]);g.querySelector('circle').setAttribute('cy',pos[1]);g.querySelector('text').setAttribute('x',pos[0]);g.querySelector('text').setAttribute('y',pos[1]);}}
    const selfReplacement=code==='CAT-03'?['CAT-04','CAT-04 Componentdefinities beheren']:code==='CAT-04'?['CAT-05','CAT-05 Bestaande catalogus aanpassen']:null;
    if(selfReplacement)for(const g of s.querySelectorAll('[data-component="guide-chip"]'))if(g.dataset.guideCode===code){g.dataset.guideCode=selfReplacement[0];g.querySelector('[data-role="guide-label"]').textContent=selfReplacement[1];}
    if(code==='CAT-03'){
     [...s.children].forEach(n=>{if(n.tagName==='defs')return;try{const b=n.getBBox();if(b.y>=60&&b.y+b.height<=67)n.remove();}catch{}});
     const labels=['1 Zoeken','2 Datatype','3 Berekening','4 Gedrag','5 Invoer','6 Controle'];
     labels.forEach((label,j)=>{const x=12+j*31.2;const r=document.createElementNS(ns,'rect');Object.entries({x,y:61,width:30,height:4.5,rx:.7,fill:i===j?'#F7F2FB':'#F8FAFC',stroke:i===j?'#754696':'#C8D5E2','stroke-width':.3}).forEach(([k,v])=>r.setAttribute(k,v));s.append(r);const t=document.createElementNS(ns,'text');Object.entries({x:x+15,y:64,'font-size':1.6,'font-family':'Arial','text-anchor':'middle',fill:'#754696'}).forEach(([k,v])=>t.setAttribute(k,v));t.textContent=label;s.append(t);});
    }
   });
  },{code,total:pages.length});
  pages=await q.evaluate(()=>[...document.querySelectorAll('.page>svg')].map(s=>s.outerHTML));await q.close();created.push({code,svgs:pages});
 }
}

const browser=await chromium.launch(browserLaunchOptions({headless:true}));
const reviewErrorsAllowed=process.env.GUIDE_ALLOW_REVIEW_ERRORS==='1';
const qa=[];
const previousQA=process.env.SNIPEIT_GUIDE_FILTER&&fs.existsSync(path.join(proof,'geometry-review.json'))?JSON.parse(fs.readFileSync(path.join(proof,'geometry-review.json'),'utf8')):[];
const combinedQA=()=>inputs.map(i=>qa.find(q=>q.code===i.code)??previousQA.find(q=>q.code===i.code)).filter(Boolean);
try{
 const filter=process.env.SNIPEIT_GUIDE_FILTER?.split(',').map(v=>v.trim().toUpperCase());
 buildShort();await buildLong(browser,filter);
 for(const entry of created){
  if(filter&&!filter.includes(entry.code))continue;const g=inputs.find(g=>g.code===entry.code);const svgs=entry.svgs??entry.pages.map(d=>d.render());
  const stem=path.basename(g.input,'.html').replace(/-v\d+-draft$/i,`-v${g.nextVersion}-draft`),html=path.join(round,stem+'.html'),pdf=path.join(round,stem+'.pdf');
  svgs.forEach((s,i)=>fs.writeFileSync(path.join(round,stem+`-page-${i+1}.svg`),s));fs.writeFileSync(html,htmlFor(svgs));
  const p=await browser.newPage();await p.goto(pathToFileURL(html).href);await p.evaluate(()=>document.fonts.ready);
  await p.evaluate(()=>{for(const footer of document.querySelectorAll('[data-footer-references]')){const chips=[...footer.querySelectorAll('[data-component="guide-chip"]')];const widths=chips.map(c=>Math.ceil((c.querySelector('[data-role="guide-label"]').getBBox().width+13)*10)/10);if(widths.reduce((a,b)=>a+b,0)+3*(widths.length-1)>186)throw new Error('Footer references require a second row: '+chips.map(c=>c.dataset.guideCode));let x=12;chips.forEach((c,i)=>{const r=c.querySelector('rect'),dx=x-Number(r.getAttribute('x'));c.setAttribute('transform',`translate(${dx},0)`);r.setAttribute('width',widths[i]);x+=widths[i]+3;});}});
  const finalSvgs=await p.evaluate(()=>[...document.querySelectorAll('.page>svg')].map(s=>s.outerHTML));finalSvgs.forEach((s,i)=>fs.writeFileSync(path.join(round,stem+`-page-${i+1}.svg`),s));fs.writeFileSync(html,htmlFor(finalSvgs));
  let components;try{components=await inspectRenderedGuideComponents(p);}catch(error){if(!reviewErrorsAllowed)throw error;components={errors:[error.message]};}
  const boxes=await p.evaluate(()=>[...document.querySelectorAll('.page')].flatMap((s,i)=>[...s.querySelectorAll('text')].map(n=>{const b=n.getBBox(),m=s.querySelector('svg').getCTM().inverse().multiply(n.getCTM()),points=[[b.x,b.y],[b.x+b.width,b.y],[b.x,b.y+b.height],[b.x+b.width,b.y+b.height]].map(([x,y])=>new DOMPoint(x,y).matrixTransform(m)),x=Math.min(...points.map(p=>p.x)),y=Math.min(...points.map(p=>p.y));return{page:i+1,text:n.textContent,x,y,w:Math.max(...points.map(p=>p.x))-x,h:Math.max(...points.map(p=>p.y))-y};})));
  const outside=boxes.filter(b=>b.x<-.1||b.y<0||b.x+b.w>210.1||b.y+b.h>297.1);if(!reviewErrorsAllowed)assert.deepEqual(outside,[],entry.code+': text outside A4');
  qa.push({code:entry.code,components,outside,textBoxes:boxes});fs.writeFileSync(path.join(proof,'geometry-review.json'),JSON.stringify(combinedQA(),null,2));await p.close();
  const result=spawnSync(process.execPath,[path.join(repoRoot,'scripts/manuals/render-guide-html.mjs'),'--html',html,'--pdf',pdf,'--pages',String(svgs.length),'--render-dir',path.join(proof,entry.code),'--replace-output'],{encoding:'utf8',windowsHide:true,env:process.env});assert.equal(result.status,0,result.stderr||result.stdout);
  fs.copyFileSync(pdf,path.join(repoPdfOutputRoot,path.basename(pdf)));entry.pdf=rel(pdf);entry.html=rel(html);entry.pageCount=svgs.length;entry.sha256=hash(pdf);entry.stem=stem;
  console.log(JSON.stringify({code:entry.code,pages:svgs.length,pdf:entry.pdf}));
 }
}finally{await browser.close();}
fs.writeFileSync(path.join(proof,'geometry-review.json'),JSON.stringify(combinedQA(),null,2));
let records=created.filter(g=>g.pdf).map(({pages,svgs,...g})=>({...g,version:inputs.find(i=>i.code===g.code).nextVersion}));
const previousFile=path.join(round,'authoring-manifest.json');
if(process.env.SNIPEIT_GUIDE_FILTER&&fs.existsSync(previousFile)){
 const previous=JSON.parse(fs.readFileSync(previousFile,'utf8'));for(const g of previous.guides)assert.equal(hash(path.join(repoRoot,g.pdf)),records.find(r=>r.code===g.code)?.sha256??g.sha256,g.code+': proposal changed outside this render');
 records=inputs.map(i=>records.find(g=>g.code===i.code)??previous.guides.find(g=>g.code===i.code)).filter(Boolean);
}
fs.writeFileSync(previousFile,JSON.stringify({date:'2026-10-20',status:'Working drafts; not accepted',brief:'docs/manuals/operator-guides/reviews/remaining-guides-2026-10-20.md',guides:records,sources,figures},null,2));
