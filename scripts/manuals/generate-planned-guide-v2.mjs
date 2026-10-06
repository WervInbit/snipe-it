import fs from 'node:fs';
import path from 'node:path';
import { pathToFileURL } from 'node:url';
import {
    GUIDE_FAMILIES,
    GUIDE_TOKENS,
    SvgGuideDocument,
    drawCompletionRow,
    drawContextStrip,
    drawRelatedGuideRows,
    guideReference,
    inspectRenderedGuideComponents,
    validateGuideDefinition,
} from './lib/guide-system.mjs';
import {
    browserLaunchOptions,
    draftPdfRoot,
    evidencePath,
    guideOutputDir,
    loadGuideDependency,
    repoPdfOutputRoot,
} from './lib/guide-paths.mjs';

const { chromium } = loadGuideDependency('playwright');
const generatedOn = process.env.SNIPEIT_GUIDE_DATE ?? '2026-09-15';
const outputRoot = process.env.SNIPEIT_GUIDE_OUT_DIR ?? guideOutputDir('2026-09-15-planned-guides-v2');
const selectedGuide = process.env.SNIPEIT_GUIDE_FILTER?.trim().toUpperCase() || null;
const colors = GUIDE_TOKENS.colors;

const sourceIds = {
 groupList:'USR-GROUPS-LIST-DESKTOP-01',
 groupTop:'USR-GROUP-EDIT-FULL-DESKTOP-02',
 groupModels:'USR-GROUP-MODELS-DESKTOP-02',
 groupDetail:'USR-GROUP-DETAIL-DESKTOP-02',
 groupRights:'USR-GROUP-STORED-RIGHTS-DESKTOP-02',
 groupSave:'USR-GROUP-SAVE-DESKTOP-01',
 modelLifecycle:'CAT-MODEL-NUMBER-LIFECYCLE-DESKTOP-01',
 modelEdit:'CAT-MODEL-NUMBER-EDIT-DESKTOP-02',
 specAttributes:'CAT-MODEL-SPEC-CLEANUP-ATTRIBUTES-DESKTOP-01',
 specComponents:'CAT-MODEL-SPEC-CLEANUP-COMPONENTS-DESKTOP-01',
 specSave:'CAT-MODEL-SPEC-SAVE-DESKTOP-01',
 attributeList:'CAT-ATTRIBUTE-ENTRY-DESKTOP-01',
 attributeEdit:'CAT-ATTRIBUTE-EXISTING-OPTIONS-DESKTOP-02',
 componentList:'CAT-COMPONENT-DEFINITION-LIST-DESKTOP-01',
 componentEdit:'CAT-COMPONENT-EXISTING-EDIT-DESKTOP-02',
 labelPhoto:'AST-LABEL-PLACEMENT-PHOTO-01',
 hpSource:'CAT-SOURCE-HP-PROBOOK-450-G8-02',
};
function imageSize(buffer) {
    if (buffer[0] === 0x89 && buffer[1] === 0x50 && buffer[2] === 0x4e && buffer[3] === 0x47) {
        return { width: buffer.readUInt32BE(16), height: buffer.readUInt32BE(20), mime: 'image/png' };
    }
    if (buffer[0] === 0xff && buffer[1] === 0xd8) {
        const markers = new Set([0xc0, 0xc1, 0xc2, 0xc3, 0xc5, 0xc6, 0xc7, 0xc9, 0xca, 0xcb, 0xcd, 0xce, 0xcf]);
        let offset = 2;
        while (offset + 8 < buffer.length) {
            if (buffer[offset] !== 0xff) { offset += 1; continue; }
            const marker = buffer[offset + 1];
            if (markers.has(marker)) {
                return { width: buffer.readUInt16BE(offset + 7), height: buffer.readUInt16BE(offset + 5), mime: 'image/jpeg' };
            }
            if (marker === 0xd8 || marker === 0xd9) { offset += 2; continue; }
            const length = buffer.readUInt16BE(offset + 2);
            if (length < 2) break;
            offset += length + 2;
        }
    }
    throw new Error('Guide evidence must be PNG or JPEG.');
}

function loadImage(sourceId) {
    const file = evidencePath(sourceId);
    const buffer = fs.readFileSync(file);
    const size = imageSize(buffer);
    return { ...size, href: `data:${size.mime};base64,${buffer.toString('base64')}`, file, sourceId };
}

const images = Object.fromEntries(Object.entries(sourceIds).map(([key, sourceId]) => [key, loadImage(sourceId)]));

const ref = (code, width, row=1) => guideReference(code,{width,row});
const definitions = {
 'USR-05':{code:'USR-05',family:'USR',title:'Groepen beheren',version:'Draft v2',
 purpose:'Hergebruik of wijzig een rechtengroep; controleer daarna de toegang van een gebruiker',
 related:[ref('USR-01',57),ref('USR-02',62),ref('HELP-01',55,2)]},
 'CAT-05':{code:'CAT-05',family:'CAT',title:'Bestaande catalogus aanpassen',version:'Draft v2',
 purpose:'Vind de juiste onderhoudstaak vanuit het scherm dat je voor je hebt',
 related:[ref('CAT-01',67),ref('CAT-02',62),ref('CAT-03',55,2),ref('CAT-04',65,2)]},
 'CAT-06':{code:'CAT-06',family:'CAT',title:'Productgegevens controleren',version:'Draft v2',
 purpose:'Controleer een onzekere modelcode of specificatie voordat je catalogusdata invoert',
 related:[ref('CAT-01',67),ref('CAT-02',62),ref('CAT-03',55,2),ref('CAT-04',65,2)]},
};
Object.values(definitions).forEach(validateGuideDefinition);
function card(doc, x, y, w, h, options = {}) {
    doc.rect(x, y, w, h, options.fill ?? colors.white, options.stroke ?? colors.line, options.sw ?? 0.45, options.r ?? 1.8, 1, { component: options.component ?? 'content-card' });
}

function header(doc, definition, pageNumber, pageCount, context) {
    const palette = GUIDE_FAMILIES[definition.family];
    const title = `${definition.code} ${definition.title}`;
    const titleSize = title.length > 34 ? 4.3 : title.length > 28 ? 5.1 : 7.4;
    doc.rect(12, 13, 2, 16, palette.color);
    doc.text(18, 22, title, { size: titleSize, weight: 900, data: { component: 'guide-title' } });
    doc.text(18, 29, definition.purpose, { size: 2.75, fill: colors.muted });
    doc.rect(164, 12, 34, 14, colors.orangeSoft, '#FDBA74', 0.45, 2);
    doc.centeredText(181, 16.5, `${definition.version} ${generatedOn}`, { size: 2.05, weight: 800, fill: colors.orange });
    doc.centeredText(181, 22, `Pagina ${pageNumber} van ${pageCount}`, { size: 2.05, weight: 800, fill: colors.orange });
    drawContextStrip(doc, context);
}

function pageHeading(doc, definition, number, title, subtitle) {
    const palette = GUIDE_FAMILIES[definition.family];
    doc.familyBadge(15.5, 68.5, definition.family, { radius: 3.5, fontSize: definition.family.length > 3 ? 1.4 : 1.9, fill: palette.fill });
    doc.text(22, 70, `${number} ${title}`, { size: 5, weight: 900 });
    if (subtitle) doc.text(22, 76, subtitle, { size: 2.3, fill: colors.muted });
}

function stepCard(doc, definition, number, title, y, h, options = {}) {
    const palette = GUIDE_FAMILIES[definition.family];
    const x = options.x ?? 12;
    const w = options.w ?? 186;
    card(doc, x, y, w, h, { component: 'step-card' });
    doc.stepBadge(x, y, String(number), palette.color);
    doc.text(x + 11, y + 8, title, { size: 3.75, weight: 900 });
    return { x, y, w, h, bodyX: x + 11 };
}

function visual(doc, definition, imageKey, label, caption, frame, crop = null, options = {}) {
    const palette = GUIDE_FAMILIES[definition.family];
    const captionHeight = options.captionHeight ?? 6;
    const imageFrame = { x: frame.x, y: frame.y, w: frame.w, h: frame.h - captionHeight };
    const placement = doc.image(imageKey, imageFrame, crop, { fit: options.fit ?? 'cover', r: 1.2 });
    doc.rect(imageFrame.x, imageFrame.y, imageFrame.w, imageFrame.h, 'none', 'none', 0, 1.2, 0, { component: 'evidence-frame' });
    doc.imageBadge(imageFrame.x + 1.2, imageFrame.y + 1.2, label, palette.color);
    doc.text(frame.x + 1, frame.y + frame.h - 1.6, caption, { size: 2.3, weight: 700, fill: colors.muted });
    return placement;
}

function callout(doc, x, y, w, lines, options = {}) {
    const height = options.height ?? 20;
    const color = options.color ?? colors.orange;
    card(doc, x, y, w, height, { fill: options.fill ?? colors.orangeSoft, stroke: options.stroke ?? '#FDBA74', component: 'callout-card' });
    doc.circle(x + 5.5, y + 6, 2.8, color, 0.6, colors.white);
    doc.centeredText(x + 5.5, y + 6, options.icon ?? '!', { size: 2.5, weight: 900, fill: color });
    doc.text(x + 11, y + 5.8, options.title ?? 'Let op', { size: 2.45, weight: 900, fill: color });
    doc.text(x + 4, y + 11.2, lines, { size: options.size ?? 2.05, fill: options.textColor ?? colors.ink, lh: options.lh ?? 2.75 });
}

function infoRow(doc, x, y, w, h, title, lines, options = {}) {
    card(doc, x, y, w, h, { fill: options.fill ?? colors.faint, stroke: options.stroke ?? colors.line, component: 'definition-row' });
    doc.circle(x + 5.5, y + 6, 2.7, options.color ?? colors.muted, 0.6, colors.white);
    doc.centeredText(x + 5.5, y + 6, options.icon ?? '-', { size: 2.2, weight: 900, fill: options.color ?? colors.muted });
    doc.text(x + 11, y + 6.2, title, { size: 2.55, weight: 900, fill: options.color ?? colors.ink });
    doc.text(x + 4, y + 12, lines, { size: options.size ?? 2.05, fill: colors.muted, lh: options.lh ?? 2.7 });
}

function footer(doc, definition, pageNumber, pageCount, completion = null, nextText = null, source = 'Bron: gecontroleerde testomgeving') {
    const palette = GUIDE_FAMILIES[definition.family];
    if (completion) {
        drawCompletionRow(doc, completion, { width: 150 });
    } else {
        card(doc, 12, 253, 150, 12, { fill: palette.fill, stroke: palette.color, component: 'continuation-row' });
        doc.centeredText(17, 259, 'Volgende pagina', { size: 2.55, weight: 900, fill: palette.color, anchor: 'start' });
        doc.centeredText(47, 259, nextText, { size: 2.1, fill: palette.color, anchor: 'start' });
    }
    drawRelatedGuideRows(doc, definition.related);
    doc.qrPlaceholder(176, 263, 22);
    doc.centeredText(187, 289, 'Proef - QR volgt', { size: 2.05, weight: 800, fill: colors.muted });
    doc.text(12, 294, `${source} | ${generatedOn}`, { size: 1.75, fill: colors.muted });
    doc.text(198, 294, `Pagina ${pageNumber} van ${pageCount}`, { size: 1.75, fill: colors.muted, anchor: 'end' });
}

function createPage(definition, pageNumber, pageCount, context, draw) {
    const doc = new SvgGuideDocument(images, `${definition.code.toLowerCase()}-${pageNumber}`);
    header(doc, definition, pageNumber, pageCount, context);
    draw(doc);
    return doc.render();
}


function body(doc,y,lines,opts={}) {
 doc.text(opts.x??23,y,lines,{size:opts.size??2.55,lh:opts.lh??3.8,fill:opts.fill??colors.ink,...opts});
}
function note(doc,y,title,lines,opts={}) {
 callout(doc,12,y,186,lines,{title,height:opts.height??27,size:2.45,lh:3.6,...opts});
}
function end(doc,d,n,total,text,complete=false) {
 footer(doc,d,n,total,complete?text:null,complete?null:text,d.code==='CAT-06'?'Bron: fysiek fabrikantlabel + HP productpagina':'Bron: gecontroleerde testomgeving');
}
function finish(doc,d,n,total,lines) {
 card(doc,12,253,150,12,{fill:colors.greenSoft,stroke:'#86EFAC'});
 doc.text(17,258,'Klaar als',{size:2.6,weight:900,fill:colors.green});
 doc.text(41,258,lines,{size:2.35,lh:3.2,fill:colors.green});
 drawRelatedGuideRows(doc,d.related);
 doc.qrPlaceholder(176,263,22);
 doc.centeredText(187,289,'Proef - QR volgt',{size:2.05,weight:800,fill:colors.muted});
 doc.text(12,294,(d.code==='CAT-06'?'Bron: fysiek fabrikantlabel + HP productpagina':'Bron: gecontroleerde schermen en werkwijze')+' | '+generatedOn,{size:1.75,fill:colors.muted});
 doc.text(198,294,'Pagina '+n+' van '+total,{size:1.75,fill:colors.muted,anchor:'end'});
}
const usrContext=[{label:'Rol',value:'Superadmin'},{label:'Nodig',value:'Taak + testgebruiker',size:2.45},{label:'Vooraf',guide:guideReference('AC-01'),size:2.2}];
const catContext=[{label:'Rol',value:'Admin'},{label:'Nodig',value:'Exact object + impact',size:2.4},{label:'Vooraf',guide:guideReference('CAT-00'),size:2.1}];


function usr05Pages() {
 const d=definitions['USR-05'], pages=[];
 pages.push(createPage(d,1,3,usrContext,doc=>{
  pageHeading(doc,d,'1','Hergebruiken, bewerken of nieuw?','Bepaal eerst welke taken de groep wel en niet mag uitvoeren.');
  stepCard(doc,d,'1','Open Groepen vanuit het donkere linkermenu',85,64);
  visual(doc,d,'groupList','1A','Klik Instellingen (tandwiel), scrol in het submenu en kies Groepen.',
   {x:23,y:97,w:164,h:44},{x:0,y:0,w:1265,h:710},{fit:'contain'});
  body(doc,146,'Beheerder Instellingen is de paginatitel na deze keuze. Bewaar eerst invoer uit de gebruikersgids.',{size:2.25});
  stepCard(doc,d,'2','Open de kandidaatgroep en kies precies een route',158,84);
  visual(doc,d,'groupDetail','2A','Groepsdetail met Groep bewerken.',
   {x:23,y:171,w:164,h:24},{x:187,y:128,w:821,h:147},{fit:'contain'});
  body(doc,201,[
   'Gelijk aan de gewenste taak? Hergebruik de groep ongewijzigd; je hoeft niets op te slaan.',
   'Keer terug naar USR-01 stap 3 of USR-02 stap 2 en wijs daar de groep toe.',
   'Moet de taak voor alle leden veranderen? Kies Groep bewerken en ga naar pagina 2.',
   'Ontbreekt het juiste pakket? Ga terug naar de lijst, kies Nieuwe aanmaken en ga naar pagina 2.',
  ],{size:2.45,lh:4.6});
  body(doc,233,'Verbreed of beperk een bestaande groep nooit alleen voor de wens van een enkel lid.',{size:2.35,fill:colors.help});
  end(doc,d,1,3,'Alleen bij aanmaken of bewerken: pagina 2.');
 }));
 pages.push(createPage(d,2,3,usrContext,doc=>{
  pageHeading(doc,d,'3','Stel een klein, herkenbaar rechtenpakket in','Voorbeeld: Catalogus lezen mag assetmodellen bekijken, maar niet beheren.');
  stepCard(doc,d,'3','Naam, notitie en brede rechten',85,62);
  visual(doc,d,'groupTop','3A','Naam en de kolommen Toestaan / Weigeren.',
   {x:23,y:98,w:88,h:43},{x:345,y:230,w:495,h:360},{fit:'contain'});
  body(doc,99,[
   'Naam: Catalogus lezen.',
   'Notitie: beschrijf de bedoelde taak.',
   'Laat Global: Super User en Admin',
   'op Weigeren voor dit voorbeeld.',
   'Gebruik een aparte groep voor een',
   'afwijkende taak, geen standaardrol.',
  ],{x:117,size:2.35,lh:4});
  stepCard(doc,d,'4','Models: alleen de rij View op Toestaan',157,62);
  visual(doc,d,'groupModels','4A','Links Toestaan; rechts Weigeren.',
   {x:23,y:169,w:90,h:43},{x:450,y:320,w:580,h:326},{fit:'contain'});
  body(doc,170,[
   'Zet View op Toestaan.',
   'Laat Create, Edit, lifecycle, cleanup',
   'en Delete op Weigeren.',
   'Laat overige rechten op Weigeren.',
   'Radio in de sectiekop = alle rijen.',
   'Klik de koptekst om uit/in te klappen.',
  ],{x:119,size:2.25,lh:3.9});
  note(doc,223,'Bij twee groepen wint Toestaan',[
   'Groep A Toestaan + groep B Weigeren = toegestaan. Groepsrechten worden bij elkaar opgeteld.',
   'Geen enkele groep Toestaan = niet toegestaan. Weigeren in een groep blokkeert geen andere groep.',
   'Een direct gebruikersrecht gaat voor alle groepen: direct Weigeren blokkeert; direct Toestaan verleent.',
  ],{height:26,size:2.05,lh:3.25});
  end(doc,d,2,3,'Sla op, controleer de groep en test toegang op pagina 3.');
 }));
 pages.push(createPage(d,3,3,usrContext,doc=>{
  pageHeading(doc,d,'5','Opslaan, heropenen en toegang testen','Een groepsdetail bewijst de groepsinstelling, niet de effectieve toegang van ieder lid.');
  stepCard(doc,d,'5','Kies Opslaan en controleer de opgeslagen groep',85,63);
  visual(doc,d,'groupSave','5A','Opslaan staat onderaan het formulier.',
   {x:23,y:97,w:91,h:21},{x:280,y:560,w:760,h:135},{fit:'contain'});
  visual(doc,d,'groupRights','5B','Rechten van deze groep.',
   {x:120,y:97,w:67,h:44},{x:1005,y:345,w:245,h:178},{fit:'contain'});
  body(doc,126,['Open zo nodig opnieuw via de groepenlijst.',
   'Controleer de naam en alle gewijzigde rechten.',
   'In het voorbeeld: View ja, Create en Edit nee.',
   'Opslaan mislukt? Herstel de gemelde fout en probeer opnieuw.'],{size:2.2,lh:4});
  stepCard(doc,d,'6','Test met een gecontroleerde gebruiker',158,67);
  body(doc,171,[
   'Wijs de groep via USR-02 toe aan een afgesproken testgebruiker.',
   'Controleer alle andere groepen en directe rechten van die gebruiker.',
   'Voor dit voorbeeld: geen rechten uit andere groepen, geen directe uitzonderingen of Admin/Super User.',
   'Meld die testgebruiker opnieuw aan: Asset modellen bekijken moet lukken.',
   'Model aanmaken of bewerken mag niet toegankelijk zijn. Test zonder echte gegevens te wijzigen.',
   'Onverwachte toegang? Stop uitrol en controleer groepen en uitzonderingen met USR-02.',
  ],{size:2.35,lh:4.1});
  body(doc,233,[
   'Je eigen Superadmin-sessie is geen geldige toegangstest voor de groep.',
   'Na een geslaagde test: terug naar USR-01 stap 3 of USR-02 stap 2 voor de bedoelde gebruiker.',
  ],{size:2.35,lh:4.3});
  finish(doc,d,3,3,['de groep klopt en toegestane en verboden taken zijn getest.']);
 }));
 return pages;
}

function cat05Pages() {
 const d=definitions['CAT-05'], pages=[];
 pages.push(createPage(d,1,8,catContext,doc=>{
  pageHeading(doc,d,'Start','Wat wil je wijzigen?','Kies op basis van wat je op het scherm ziet. Je hoeft de technische naam vooraf niet te kennen.');
  note(doc,85,'Gebruik CAT-05 alleen voor iets dat al bestaat',[
   'Iets nieuws maken? Gebruik CAT-01 voor model/code, CAT-02 voor specificaties,',
   'CAT-03 voor velden en keuzelijsten, of CAT-04 voor onderdeeltypen.',
  ],{height:20,size:2.2,lh:3.3,color:GUIDE_FAMILIES.CAT.color,fill:GUIDE_FAMILIES.CAT.fill,stroke:GUIDE_FAMILIES.CAT.color});
  infoRow(doc,12,112,186,27,'Je ziet een code in de lijst Model Numbers',[
   'Active/Deprecated wijzigen -> pagina 2. Primary wijzigen of een ongebruikte code verwijderen -> pagina 3.',
  ],{icon:'2',size:2.35,lh:3.7,color:GUIDE_FAMILIES.CAT.color});
  infoRow(doc,12,145,186,27,'Je bent in Edit Spec en wilt één regel verwijderen',[
   'Een direct attribuut of verwacht component uit deze modelcode halen -> pagina 4.',
  ],{icon:'4',size:2.35,lh:3.7,color:GUIDE_FAMILIES.CAT.color});
  infoRow(doc,12,178,186,27,'Je wilt een veld of keuze niet meer aanbieden',[
   'Open Attributes. Veld verbergen/herstellen -> pagina 5. Keuzewaarde beheren -> pagina 6.',
  ],{icon:'5',size:2.35,lh:3.7,color:GUIDE_FAMILIES.CAT.color});
  infoRow(doc,12,211,186,27,'Je wilt een herbruikbaar onderdeeltype aanpassen',[
   'Open Component Definitions. Status -> pagina 7. Opgeslagen kind of bijdrage verwijderen -> pagina 8.',
  ],{icon:'7',size:2.35,lh:3.7,color:GUIDE_FAMILIES.CAT.color});
  body(doc,244,'Nog steeds onzeker welk blok past? Wijzig niets en gebruik CAT-00 om het schermtype te herkennen.',{size:2.25,fill:colors.help});
  end(doc,d,1,8,'Ga direct naar het paginanummer naast jouw zichtbare scherm of taak.');
 }));
 pages.push(createPage(d,2,8,catContext,doc=>{
  pageHeading(doc,d,'2','Code uit nieuwe keuzes halen of terugzetten','Links: Instellingen > Model Numbers. Gebruik deze pagina voor Active / Deprecated.');
  stepCard(doc,d,'1','Zoek het Basismodel en de exacte Code',85,49);
  visual(doc,d,'modelLifecycle','2A','Status en Assets horen bij deze code, niet bij de hele familie.',
   {x:23,y:97,w:164,h:28},{x:55,y:160,w:1280,h:240},{fit:'contain'});
  body(doc,131,'Kies Edit bij de juiste rij. Is de code Primary? Regel eerst een andere standaard op pagina 3.',{size:2.25});
  stepCard(doc,d,'2','Wijzig Status en kies Opslaan',144,73);
  visual(doc,d,'modelEdit','2B','Status en Opslaan in Edit Model Number.',
   {x:23,y:156,w:86,h:54},{x:415,y:446,w:442,h:175},{fit:'contain'});
  body(doc,158,[
   'Uit nieuwe keuzes halen:',
   'kies Status > Deprecated.',
   'Weer beschikbaar maken:',
   'kies Status > Active.',
   'Kies Opslaan. Controleer de lijst',
   'en open Edit opnieuw.',
   'Staat de bedoelde status er nog?',
  ],{x:115,size:2.4,lh:4.3});
  body(doc,230,[
   'Wijzig hierbij geen Code, Label of afbeeldingen. De afbeelding toont een bestaande Primary:',
   'dat is alleen een voorbeeld van de bediening, geen opdracht om die rij te deprecaten.',
   'Gereed? Terug naar de modelnummerkeuze in CAT-01, of naar de taak waarmee je begon.',
  ],{size:2.25,lh:4.3});
  finish(doc,d,2,8,['de bedoelde status na heropenen is bevestigd.']);
 }));
 pages.push(createPage(d,3,8,catContext,doc=>{
  pageHeading(doc,d,'3','Standaardcode wijzigen of lege code verwijderen','Links: Instellingen > Model Numbers. Gebruik deze pagina voor Primary of Verwijder.');
  stepCard(doc,d,'1','Een andere actieve code als standaard instellen',85,71);
  visual(doc,d,'modelEdit','3A','De default-checkbox staat onder Status.',
   {x:23,y:97,w:164,h:28},{x:470,y:522,w:382,h:87},{fit:'contain'});
  body(doc,132,[
   'Open Edit bij de gewenste code van hetzelfde Basismodel.',
   'Nog Deprecated? Zet eerst Active, kies Opslaan en open Edit opnieuw.',
   'Vink Make this the default selection for new assets aan en kies Opslaan.',
   'Controleer: de gekozen rij toont Primary; de vorige standaard niet meer.',
  ],{size:2.4,lh:4.7});
  stepCard(doc,d,'2','Alleen een aantoonbaar ongebruikte rij verwijderen',167,55);
  body(doc,180,[
   'Controleer exacte Code, Assets = 0 en dat de rij niet Primary is.',
   'Controleer ook of de specificatie, afbeeldingen of historische variant nog nodig zijn.',
   'Kies Verwijder bij de gecontroleerde rij. De bevestigingsvraag noemt geen code: bij twijfel annuleren.',
   'Bevestig alleen als de gekozen rij zeker klopt.',
   'Zoek dezelfde code opnieuw: de rij moet weg zijn. Geen verwijderactie beschikbaar? Stop.',
   'Primary of gekoppelde assets blokkeren verwijderen; verander geen koppelingen als omweg.',
  ],{size:2.35,lh:4.3});
  body(doc,232,[
   'Terug: CAT-01 Model en modelnummer aanmaken, bij de selectie van de gewenste code.',
   'Kopieer model neemt alleen basisvelden en optioneel de modelafbeelding over: geen modelnummers,',
   'modelnummerbeelden, specificatiewaarden of verwachte componenten. Controleer de kopie via CAT-01.',
  ],{size:2.15,lh:4});
  finish(doc,d,3,8,['de nieuwe Primary klopt of uitsluitend de bedoelde rij weg is.']);
 }));
 pages.push(createPage(d,4,8,catContext,doc=>{
  pageHeading(doc,d,'4','Een regel verwijderen uit Edit Spec','Links: Instellingen > Model Numbers > exacte Code > Edit Spec. Gebruik dit voor één foutieve regel.');
  note(doc,85,'STOP - bepaal eerst alle geraakte gegevens',[
   'Direct attribuut verwijderen wist ook de bijbehorende assetoverrides voor dit modelnummer.',
   'Open elke betrokken asset: Specification > Override. Bewaar identiteit en oude afwijkende waarde.',
   'Volledige lijst gekoppelde assets onzeker? Stop en laat de beheerder de impact vaststellen.',
   'Verwacht component: bewaar vooraf aantal, vereiste status en de afgeleide attributen/kindverwachtingen.',
  ],{height:29,size:2.35,lh:3.8,color:colors.help,fill:colors.helpSoft,stroke:'#FDA4AF'});
  stepCard(doc,d,'1','Verwijder alleen de bedoelde opgeslagen rij',125,62);
  visual(doc,d,'specAttributes','4A','Direct attribuut: rode X.',
   {x:23,y:137,w:78,h:42},{x:280,y:210,w:850,h:400},{fit:'contain'});
  visual(doc,d,'specComponents','4B','Verwacht component: Remove.',
   {x:109,y:137,w:78,h:42},{x:285,y:195,w:825,h:400},{fit:'contain'});
  visual(doc,d,'specSave','4C','Opslaan onderaan.',
   {x:23,y:191,w:55,h:16},{x:1008,y:804,w:112,h:50},{fit:'contain'});
  body(doc,195,['Remove is nog niet opgeslagen.','Controleer eerst de overige rijen.'],{x:85,size:2.4,lh:4});
  body(doc,214,[
   'Kies Opslaan. Bij een fout: corrigeer de melding en sla opnieuw op. Annuleren bewaart niets.',
   'Open Edit Spec opnieuw: alleen de doelrij is weg. Controleer de overige rijen en alle geraakte assets.',
   'Een attribuut opnieuw toevoegen herstelt verwijderde assetoverrides niet automatisch.',
  ],{size:2.3,lh:4.2});
  body(doc,232,[
   'Dit wijzigt catalogusverwachtingen, niet de fysieke Tracked-inventaris. Gebruik daarvoor de CMP-gidsen.',
   'Terug naar CAT-02 Modelspecificatie opbouwen, pagina 5 (conflictcontrole), dan pagina 6 (opslaan).',
  ],{size:2.15,lh:4});
  finish(doc,d,4,8,['de doelrij weg is en de vooraf bepaalde gevolgen zijn gecontroleerd.']);
 }));
 pages.push(createPage(d,5,8,catContext,doc=>{
  pageHeading(doc,d,'5','Een volledig veld verbergen of terugzetten','Links: Instellingen > Attributes. Gebruik dit als het hele veld niet in nieuwe keuzes moet staan.');
  stepCard(doc,d,'1','Open Edit en lees de gebruikswaarschuwing',85,70);
  visual(doc,d,'attributeEdit','5A','Geheugentype / ram_type: bestaand gebruik is zichtbaar.',
   {x:23,y:97,w:164,h:40},{x:338,y:228,w:519,h:190},{fit:'contain'});
  body(doc,143,[
   'De aantallen betreffen de definitie, niet het gebruik van een afzonderlijke Enum-optie.',
   'Controleer relevante modelspecificaties, overrides, componenten en testverwachtingen.',
  ],{size:2.4,lh:4.5});
  stepCard(doc,d,'2','Terug naar de lijst: Hide of Unhide',166,53);
  visual(doc,d,'attributeList','5B','De rijactie wisselt tussen Hide en Unhide.',
   {x:23,y:178,w:164,h:26},{x:225,y:115,w:1135,h:230},{fit:'contain'});
  body(doc,211,'Hide werkt meteen: controleer Hidden en Unhide. Herstellen: Unhide, daarna Active en Hide.',{size:2.25});
  body(doc,230,[
   'Hiervoor is geen extra Opslaan nodig. Bestaande gegevens blijven staan; controleer relevante selecties.',
   'Er is hier geen knop om de attribuutdefinitie te verwijderen. Vraag bij noodzakelijke cleanup om hulp.',
   'Gereed? Terug naar CAT-03 Attributen beheren of de selectie waarmee je begon.',
  ],{size:2.2,lh:4});
  finish(doc,d,5,8,['de rijstatus en de beschikbaarheid in de bedoelde keuze kloppen.']);
 }));
 pages.push(createPage(d,6,8,catContext,doc=>{
  pageHeading(doc,d,'6','Eén bestaande keuzewaarde beheren','Links: Instellingen > Attributes > Edit > Options. Gebruik dit voor één keuze, niet het hele veld.');
  stepCard(doc,d,'1','Kies de opgeslagen optierij op Value en Label',85,78);
  visual(doc,d,'attributeEdit','6A','Bestaande opties: Active en Remove zijn selectievakjes.',
   {x:23,y:98,w:164,h:42},{x:474,y:1350,w:380,h:225},{fit:'contain'});
  body(doc,148,[
   'Value is de opgeslagen sleutel (bijvoorbeeld ddr4); Label is de leesbare naam (DDR4).',
   'Controleer gebruik van die exacte waarde, niet alleen de totale definitietelling.',
  ],{size:2.4,lh:4.7});
  stepCard(doc,d,'2','Deactiveren of verwijderen, daarna Opslaan',174,53);
  body(doc,187,[
   'Tijdelijk niet meer aanbieden: vink Active uit. De optie blijft als bestaande optie herkenbaar.',
   'Alleen aantoonbaar ongebruikt en niet meer nodig: vink Remove aan bij die ene rij.',
   'Kies Opslaan onderaan; Remove alleen aanvinken slaat nog niets op.',
   'Open dezelfde definitie opnieuw: Active staat uit, of uitsluitend de bedoelde optierij is weg.',
   'Controleer de relevante selectie en bestaande records; vertrouw niet op een automatische gebruiksblokkade.',
  ],{size:2.28,lh:4.4});
  body(doc,236,[
   'Onzeker over gebruik of betekenis? Laat Value, Active en Remove ongewijzigd en vraag de eigenaar.',
   'Gereed? Terug naar CAT-03 Attributen beheren, bij de controle van de opgeslagen definitie.',
  ],{size:2.25,lh:4.3});
  finish(doc,d,6,8,['de bedoelde optie en de geraakte selecties na opslaan kloppen.']);
 }));
 pages.push(createPage(d,7,8,catContext,doc=>{
  pageHeading(doc,d,'7','Een herbruikbaar onderdeeltype uitzetten of herstellen','Links: Instellingen > Component Definitions. Gebruik dit voor Activate / Deactivate.');
  stepCard(doc,d,'1','Controleer identiteit, Instances en Templates',85,70);
  visual(doc,d,'componentList','7A','De lijst toont gebruik en de beschikbare statusactie.',
   {x:23,y:98,w:164,h:36},{x:63,y:207,w:1284,h:176},{fit:'contain'});
  body(doc,143,[
   'Instances telt fysieke componenten; Templates telt modelnummer-verwachtingen, niet alle kindrelaties.',
   'Controleer ook bijdragen en verwachte onderdelen via Edit voordat je gedeeld gedrag wijzigt.',
  ],{size:2.35,lh:4.5});
  stepCard(doc,d,'2','Kies Deactivate of Activate in de lijst',166,55);
  body(doc,179,[
   'Niet meer voor nieuwe keuzes gebruiken: kies Deactivate bij de bedoelde definitie.',
   'Controleer de status Inactive en de actie Activate.',
   'Opnieuw beschikbaar maken: kies Activate en controleer de omgekeerde status.',
   'De lijstactie werkt direct; je hoeft daarna geen Save Changes te kiezen.',
   'Controleer de bedoelde selectie en bestaand gebruik. Een ontbrekende actie is geen reden voor een omweg.',
  ],{size:2.35,lh:4.4});
  note(doc,230,'Geen fysieke verwijdering',[
   'Deactiveren verwijdert geen Tracked-component. De browser heeft geen definitie-deleteknop.',
   'Gereed? Terug naar CAT-04 Componentdefinities beheren of de oorspronkelijke componentkeuze.',
  ],{height:19,size:2.15,lh:3.4});
  finish(doc,d,7,8,['de status klopt en de bedoelde selectie is gecontroleerd.']);
 }));
 pages.push(createPage(d,8,8,catContext,doc=>{
  pageHeading(doc,d,'8','Eén kind of bijdrage uit een onderdeeltype halen','Links: Instellingen > Component Definitions > Edit. Gebruik dit voor één foutieve opgeslagen relatie.');
  note(doc,85,'Vooraf: houd de bestaande betekenis en relaties herstelbaar',[
   'Bewaar bij een bijdrage het attribuut, de waarde en beide weergavevlaggen.',
   'Bewaar bij een kind de definitie, Expected Name, Quantity, Required, volgorde en notities.',
   'Is de impact op gebruik onbekend? Stop en vraag de cataloguseigenaar.',
  ],{height:29,size:2.35,lh:3.8});
  stepCard(doc,d,'1','Kies Remove alleen bij de bedoelde opgeslagen rij',125,57);
  visual(doc,d,'componentEdit','8A','Expected Subcomponents: Remove.',
   {x:23,y:137,w:78,h:37},{x:202,y:616,w:790,h:137},{fit:'contain'});
  visual(doc,d,'componentEdit','8B','Attribute Contributions: Remove.',
   {x:109,y:137,w:78,h:37},{x:202,y:1699,w:790,h:190},{fit:'contain'});
  stepCard(doc,d,'2','Save Changes, heropenen en resultaat controleren',193,34);
  visual(doc,d,'componentEdit','8C','Save Changes bewaart; Cancel niet.',
   {x:23,y:205,w:75,h:16},{x:200,y:2565,w:210,h:52},{fit:'contain'});
  body(doc,207,['Open Edit opnieuw: de doelrij is weg.',
   'Controleer alle overige waarden, aantallen en vlaggen.'],{x:103,size:2.18,lh:4.2});
  body(doc,235,[
   'Controleer de vooraf bepaalde afgeleide assetwaarden en kindverwachtingen; dit is geen CMP-verwijdering.',
   'Terug naar CAT-04 Componentdefinities beheren, bij de heropende definitie en eindcontrole.',
  ],{size:2.2,lh:4.3});
  finish(doc,d,8,8,['de doelrelatie weg is en de geraakte specificaties kloppen.']);
 }));
 return pages;
}

function cat06Pages() {
 const d=definitions['CAT-06'], pages=[];
 const context=[{label:'Rol',value:'Supervisor'},{label:'Nodig',value:'Label + fabrikantbron',size:2.4},{label:'Vooraf',guide:guideReference('CAT-00'),size:2.1}];
 pages.push(createPage(d,1,3,context,doc=>{
  pageHeading(doc,d,'1','Wanneer gebruik je CAT-06?','Alleen vóór CAT-01 of CAT-02 als een modelcode of specificatie nog onzeker is.');
  note(doc,85,'CAT-06 controleert; deze gids wijzigt niets',[
   'Gebruik CAT-01 om een model/code in te voeren en CAT-02 om een specificatie op te slaan.',
   'Kom hier alleen eerst langs wanneer bewijs ontbreekt, te breed is of elkaar tegenspreekt.',
  ],{height:21,size:2.15,lh:3.25,color:GUIDE_FAMILIES.CAT.color,fill:GUIDE_FAMILIES.CAT.fill,stroke:GUIDE_FAMILIES.CAT.color});
  infoRow(doc,12,113,186,24,'Gebruik deze gids voor één concrete vraag',[
   'Klopt de volledige modelcode? Geldt deze bron voor exact die variant? Onderbouwt hij dit ene feit?',
  ],{icon:'?',size:2.3,lh:3.6,color:GUIDE_FAMILIES.CAT.color});
  infoRow(doc,12,143,186,24,'Uitkomst: bevestigd of nog open',[
   'Bevestigd gaat terug naar de invoergids. Nog open wordt niet ingevuld en gaat naar de eigenaar.',
  ],{icon:'=',size:2.3,lh:3.6,color:GUIDE_FAMILIES.CAT.color});
  stepCard(doc,d,'1','Werkvoorbeeld: lees eerst het fysieke fabrikantlabel',174,68);
  visual(doc,d,'labelPhoto','1A','HP ProBook 450 G8; ProdID 2E9F8EA#ABH.',
   {x:23,y:187,w:86,h:43},{x:790,y:420,w:260,h:150},{fit:'contain'});
  body(doc,188,[
   'Fabrikant: HP',
   'Basismodel: ProBook 450 G8',
   'Volledige modelcode: 2E9F8EA#ABH',
   'De suffix #ABH hoort bij de code.',
   'S/N, Inbit-tag en software Product ID',
   'zijn identifiers van iets anders.',
  ],{x:115,size:2.25,lh:4.1});
  end(doc,d,1,3,'Controleer op pagina 2 precies één feit tegen de fabrikantbron.');
 }));
 pages.push(createPage(d,2,3,context,doc=>{
  pageHeading(doc,d,'2','Controleer één feit tegen de bron','Werkvoorbeeld: geldt de HP familiepagina ook voor modelcode 2E9F8EA#ABH?');
  stepCard(doc,d,'1','Open de fabrikantbron en controleer het bereik',85,62);
  visual(doc,d,'hpSource','2A','Titel van de fabrikantpagina.',
   {x:23,y:99,w:75,h:17},{x:8,y:287,w:220,h:23},{fit:'contain'});
  visual(doc,d,'hpSource','2B','De displayregel noemt 15.6 inch.',
   {x:109,y:99,w:78,h:29},{x:8,y:361,w:223,h:46},{fit:'contain'});
  body(doc,134,[
   'De familie en generatie komen overeen. De exacte code 2E9F8EA#ABH staat hier niet.',
   'Dit is familie-informatie, geen bewijs van de volledige fabrieksconfiguratie van deze suffix.',
  ],{size:2.4,lh:4.4});
  infoRow(doc,12,157,186,33,'Onderbouwd: schermdiagonaal 15,6 inch',[
   'De productpagina geeft de diagonaal voor deze familie. Dat ondersteunt dit familiekenmerk.',
   'Neem hieruit geen FHD, Touch of Sure View over: de pagina presenteert die als opties.',
  ],{icon:'+',color:colors.green,fill:colors.greenSoft,stroke:'#86EFAC',size:2.4,lh:4});
  infoRow(doc,12,199,186,33,'Niet onderbouwd: de standaard hoeveelheid RAM van 2E9F8EA#ABH',[
   'Deze pagina geeft geen exacte RAM-configuratie voor die code. Vul dus niet zomaar 8 of 32 GB in.',
   'Ook een maximum, keuzelijst of meting aan een later aangepast asset bewijst geen fabrieksbaseline.',
  ],{icon:'!',color:colors.help,fill:colors.helpSoft,stroke:'#FDA4AF',size:2.35,lh:4});
  body(doc,239,'Bron: HP - HP ProBook 450 G8. Open de productpagina (klikbare link):',{size:2.2,fill:colors.muted});
  body(doc,244,'hp.com/us-en/laptops/business/probook-400/product-card/hp-probook-450-g8.html',{size:2.1,fill:colors.muted});
  doc.raw('<a href="https://www.hp.com/us-en/laptops/business/probook-400/product-card/hp-probook-450-g8.html"><rect x="18" y="236" width="180" height="11" fill="transparent"/></a>');
  end(doc,d,2,3,'Gebruik alleen het onderbouwde resultaat; afhandeling op pagina 3.');
 }));
 pages.push(createPage(d,3,3,context,doc=>{
  pageHeading(doc,d,'3','Wat doe je met het resultaat?','CAT-06 beslist alleen of het gegeven betrouwbaar genoeg is; de andere gids voert het in.');
  infoRow(doc,12,85,186,37,'Modelcode bevestigd -> terug naar CAT-01',[
   'Gebruik fabrikant, Basismodel en de volledige code bij zoeken of invoeren.',
   'Is de vereiste identiteit niet bevestigd? Stop de aanmaak; maak geen placeholdercode.',
  ],{icon:'1',color:colors.green,fill:colors.greenSoft,stroke:'#86EFAC',size:2.35,lh:4});
  infoRow(doc,12,129,186,37,'Specificatie bevestigd -> terug naar CAT-02',[
   'Voer alleen het onderbouwde feit in, met de juiste definitie, eenheid en bronroute.',
   'Een familie-optie, maximum of meting aan een gewijzigd asset is geen fabrieksbaseline.',
  ],{icon:'2',color:colors.green,fill:colors.greenSoft,stroke:'#86EFAC',size:2.35,lh:4});
  infoRow(doc,12,173,186,45,'Nog niet bevestigd -> niet invoeren',[
   'In dit voorbeeld blijft RAM voor 2E9F8EA#ABH open: de familiepagina bewijst die configuratie niet.',
   'Optioneel feit onzeker? Laat alleen dat veld leeg. Bronnen in conflict? Geef label, bron en exacte vraag',
   'aan de cataloguseigenaar. Gebruik een andere bron pas na diens besluit.',
  ],{icon:'!',color:colors.help,fill:colors.helpSoft,stroke:'#FDA4AF',size:2.3,lh:4});
  body(doc,228,[
   'Ontbreekt een veld of onderdeeltype zelf? Gebruik CAT-03 of CAT-04. Iets bestaands aanpassen? CAT-05.',
   'Later ingebouwde hardware hoort bij het asset / fysieke component, niet in de fabrieksbaseline.',
  ],{size:2.2,lh:4.1});
  finish(doc,d,3,3,['de bevestigde feiten naar de juiste taak zijn teruggebracht.',
   'Open vragen zijn apart overgedragen, niet als feiten opgeslagen.']);
 }));
 return pages;
}
const guides={
 'USR-05':{definition:definitions['USR-05'],pages:usr05Pages(),outputFile:'usr-05-groepen-beheren-v2-draft.pdf',layout:'group-choice-permissions-test',evidence:Object.values(sourceIds).filter(id=>id.startsWith('USR-')),knownGaps:['Controlled browser user trial and owner acceptance remain pending; resolver cases were checked without creating users.']},
 'CAT-05':{definition:definitions['CAT-05'],pages:cat05Pages(),outputFile:'cat-05-varianten-en-lifecycle-beheren-v2-draft.pdf',layout:'independent-maintenance-routes',evidence:Object.values(sourceIds).filter(id=>id.startsWith('CAT-')&&!id.startsWith('CAT-SOURCE')),knownGaps:['Destructive lifecycle/cleanup routes are source-reviewed and visually located, not executed against shared data. Disposable end-to-end trial and owner review remain required.']},
 'CAT-06':{definition:definitions['CAT-06'],pages:cat06Pages(),outputFile:'cat-06-catalogus-controleren-en-bronnen-v2-draft.pdf',layout:'worked-source-check',evidence:[sourceIds.labelPhoto,sourceIds.hpSource],knownGaps:['Source retention/fallback approval policy remains open. The manufacturer family page does not prove exact-SKU RAM.']},
};

function htmlFor(title, pages) {
    return `<!doctype html><html><head><meta charset="utf-8"><title>${title}</title><style>
        @page { size: A4; margin: 0; }
        html, body { margin: 0; padding: 0; background: #dfe3ea; }
        .page { width: 210mm; height: 297mm; overflow: hidden; background: #fff; break-after: page; page-break-after: always; }
        .page:last-child { break-after: auto; page-break-after: auto; }
        .page > svg { display: block; width: 210mm; height: 297mm; }
    </style></head><body>${pages.map((svg) => `<div class="page">${svg}</div>`).join('')}</body></html>`;
}

async function inspectPageGeometry(page) {
    const result = await page.evaluate(() => [...document.querySelectorAll('.page')].map((pageNode, index) => {
        const errors = [];
        const svg = pageNode.querySelector('svg');
        if (!svg) return { page: index + 1, errors: ['Missing SVG page.'] };
        [...svg.querySelectorAll('text')].forEach((node) => {
            const box = node.getBBox();
            if (box.x < -0.1 || box.y < -0.1 || box.x + box.width > 210.1 || box.y + box.height > 297.1) {
                errors.push(`Text outside A4 bounds: ${node.textContent.trim().slice(0, 60)}`);
            }
        });
        [...svg.querySelectorAll('[data-component]')].forEach((node) => {
            if (node.dataset.component === 'focus-mark') return;
            const box = node.getBBox();
            if (box.x < -0.1 || box.y < -0.1 || box.x + box.width > 210.1 || box.y + box.height > 297.1) errors.push(`${node.dataset.component} exceeds A4 bounds.`);
        });
        const title = svg.querySelector('[data-component="guide-title"]');
        if (title) {
            const box = title.getBBox();
            if (box.x + box.width > 160) errors.push(`Guide title overlaps version panel at x=${(box.x + box.width).toFixed(2)}.`);
        }
        return { page: index + 1, errors };
    }));
    const errors = result.flatMap((entry) => entry.errors.map((error) => `Page ${entry.page}: ${error}`));
    if (errors.length) throw new Error(`Guide geometry failed:\n- ${errors.join('\n- ')}`);
    return result;
}

async function renderGuide(browser, code, guide) {
    const guideDir = path.join(outputRoot, `${code.toLowerCase()}-v2`);
    fs.mkdirSync(guideDir, { recursive: true });
    fs.mkdirSync(repoPdfOutputRoot, { recursive: true });
    fs.mkdirSync(draftPdfRoot, { recursive: true });
    guide.pages.forEach((svg, index) => fs.writeFileSync(path.join(guideDir, `${code.toLowerCase()}-page-${index + 1}.svg`), svg, 'utf8'));
    const htmlPath = path.join(guideDir, `${code.toLowerCase()}-v2.html`);
    const proofPdf = path.join(guideDir, guide.outputFile);
    const repoPdf = path.join(repoPdfOutputRoot, guide.outputFile);
    const draftPdf = path.join(draftPdfRoot, guide.outputFile);
    fs.writeFileSync(htmlPath, htmlFor(`${code} ${guide.definition.title} - Draft v2`, guide.pages), 'utf8');
    const page = await browser.newPage({ viewport: { width: 1240, height: 1754 }, deviceScaleFactor: 2 });
    await page.goto(pathToFileURL(htmlPath).href, { waitUntil: 'load' });
    const componentQa = await inspectRenderedGuideComponents(page);
    const geometryQa = await inspectPageGeometry(page);
    const pageLocators = await page.locator('.page').all();
    for (let index = 0; index < pageLocators.length; index += 1) {
        await pageLocators[index].screenshot({ path: path.join(guideDir, `${code.toLowerCase()}-page-${index + 1}.png`) });
    }
    await page.pdf({ path: proofPdf, width: '210mm', height: '297mm', printBackground: true, preferCSSPageSize: true, margin: { top: '0', right: '0', bottom: '0', left: '0' } });
    await page.close();
    fs.copyFileSync(proofPdf, repoPdf);
    fs.copyFileSync(proofPdf, draftPdf);
    const summary = { code, version: 'Draft v2', generatedOn, pageCount: guide.pages.length, layout: guide.layout, componentQa, geometryQa, evidence: guide.evidence, outputs: { htmlPath, proofPdf, repoPdf, draftPdf }, knownGaps: guide.knownGaps };
    fs.writeFileSync(path.join(guideDir, 'generation-summary.json'), `${JSON.stringify(summary, null, 2)}\n`, 'utf8');
    return summary;
}

const requested = selectedGuide ? [selectedGuide] : Object.keys(guides);
requested.forEach((code) => { if (!guides[code]) throw new Error(`Unknown planned guide filter: ${code}`); });
const browser = await chromium.launch(browserLaunchOptions({ headless: true }));
try {
    const summaries = [];
    for (const code of requested) summaries.push(await renderGuide(browser, code, guides[code]));
    console.log(JSON.stringify({ outputRoot, guides: summaries }, null, 2));
} finally {
    await browser.close();
}
