import { GUIDE_REGISTRY, GUIDE_FAMILIES } from './guide-system.mjs';

export const ownerCorrections = [
  ['SC-01',11,12,'SC-01-asset-vinden-en-openen-v12-draft.pdf',1],
  ['AST-02',7,8,'AST-02-refurbishment-route-v8-draft.pdf',1],
  ['WF-01',12,13,'WF-01-workflow-starten-v13-draft.pdf',1],
  ['CMP-02',5,6,'CMP-02-register-install-v6-draft.pdf',1],
  ['CMP-04',6,7,'CMP-04-component-to-tray-v7-draft.pdf',1],
  ['USR-04',5,6,'USR-04-gebruiker-uitschakelen-of-herstellen-v6-draft.pdf',1],
  ['CAT-00',10,11,'CAT-00-catalogus-begrijpen-v11-draft.pdf',6],
  ['CAT-01',6,7,'CAT-01-model-en-modelnummer-aanmaken-v7-draft.pdf',5],
  ['USR-01',12,13,'usr-01-gebruiker-toevoegen-v13-draft.pdf',1],
  ['USR-02',10,11,'usr-02-rol-en-rechten-wijzigen-v11-draft.pdf',1],
].map(([code,old,version,file,pages])=>({code,old,version,file,pages}));

export const referenceData = Object.fromEntries(Object.entries(GUIDE_REGISTRY).map(([code, ref])=>[code, {...ref, ...GUIDE_FAMILIES[ref.family]}]));

export function applyOwnerCorrection({ guide, refs, trayImage }) {
  const ns='http://www.w3.org/2000/svg';
  const svgNodes=[...document.querySelectorAll('.page > svg')];
  const exact=(root,value)=>[...root.querySelectorAll('text')].find(n=>n.textContent.trim()===value);
  const change=(root,before,after)=>{const n=exact(root,before); if(!n)throw Error(`Missing text: ${before}`);n.replaceChildren();n.textContent=after;return n;};
  const el=(tag,attrs={},text)=>{const n=document.createElementNS(ns,tag);Object.entries(attrs).forEach(([k,v])=>n.setAttribute(k,v));if(text!==undefined)n.textContent=text;return n;};
  const txt=(root,x,y,value,size=2.3,fill='#53657A',weight=400)=>{const n=el('text',{x,y,'font-size':size,fill,'font-family':'Arial, Helvetica, sans-serif','font-weight':weight},value);root.append(n);return n;};
  const chip=(code)=>{const r=refs[code];return `<span class="guide-chip" data-guide-code="${code}" style="--chip:${r.color};--chip-fill:${r.fill}"><b>${r.family}</b><span>${code} ${r.title}</span></span>`;};
  const addStyle=(css)=>{const n=document.createElement('style');n.textContent=css;document.head.append(n);};
  const figure=(label)=>[...document.querySelectorAll('figure')].find(n=>n.querySelector('.image-badge')?.textContent===label);
  const moveSvgNode=(node,dx,dy)=>node.setAttribute('transform',`translate(${dx} ${dy})`);
  const fullChip=(root,x,y,w,code)=>{
    const r=refs[code],g=el('g',{'data-component':'guide-chip','data-guide-code':code});
    g.append(el('rect',{x,y,width:w,height:7,rx:2,fill:r.fill,stroke:r.color,'stroke-width':.5}));
    g.append(el('circle',{cx:x+4.5,cy:y+3.5,r:2.2,fill:'white',stroke:r.color,'stroke-width':.45}));
    txt(g,x+4.5,y+4,r.family,1.35,r.color,900).setAttribute('text-anchor','middle');
    txt(g,x+8.3,y+4.4,`${code} ${r.title}`,2.05,r.color,800).setAttribute('data-role','guide-label');
    root.append(g);return g;
  };

  const walker=document.createTreeWalker(document.body,NodeFilter.SHOW_TEXT);
  const ver=new RegExp(`\\bv${guide.old}\\b`,'g');
  while(walker.nextNode()) {
    const n=walker.currentNode;
    if(n.parentElement?.closest('style,script'))continue;
    n.textContent=n.textContent.replace(ver,`v${guide.version}`).replace(/2026-0[89]-\d{2}/g,'2026-09-10');
  }
  document.title=`${guide.code} v${guide.version}`;

  if(guide.code==='USR-01'||guide.code==='USR-02') {
    const textWalker=document.createTreeWalker(document.body,NodeFilter.SHOW_TEXT);
    let replaced=0;
    while(textWalker.nextNode()) {const n=textWalker.currentNode;if(n.textContent.includes('Gebruiker uitschakelen of herstellen')){n.textContent=n.textContent.replaceAll('Gebruiker uitschakelen of herstellen','Gebruiker uitschakelen');replaced++;}}
    if(replaced!==1)throw Error(`Expected one USR-04 reference, found ${replaced}`);
  }

  if(guide.code==='SC-01') {
    const svg=svgNodes[0];
    const label=exact(svg,'2A');
    label.setAttribute('y','151.45');
    const circle=[...svg.querySelectorAll('circle')].find(n=>n.getAttribute('cx')==='37'&&n.getAttribute('cy')==='147');
    circle.setAttribute('cy','150.5');circle.setAttribute('data-owner-check','sc01-badge');
    exact(svg,'Asset open? Ga naar stap 4.').setAttribute('data-owner-check','sc01-instruction');
  }
  if(guide.code==='AST-02') {
    const boxes=document.querySelectorAll('.help-grid .help-tile > div');
    boxes[0].innerHTML=`<strong>Componentwerk nodig</strong><div class="route-links">${chip('CMP-01')}${chip('CMP-02')}${chip('CMP-04')}</div><div class="return-link">Daarna terug naar ${chip('WF-02')}</div>`;
    boxes[1].innerHTML=`<strong>Probleem of mismatch</strong><p>Stop en gebruik:</p><div class="route-links">${chip('HELP-01')}</div>`;
    const branch=document.querySelector('.help-branch');
    branch.innerHTML=`<span class="help-icon">!</span><div><strong>Asset nog niet geregistreerd?</strong><p>Laat de Supervisor registreren:</p>${chip('AST-03')}</div><div><p>Daarna terug naar:</p>${chip('SC-01')}</div>`;
    addStyle('.help-grid{grid-template-columns:1.5fr 1fr}.help-tile{min-height:34mm;padding:2mm;gap:1.5mm}.route-links{display:flex;flex-direction:column;gap:1mm;margin-top:1.4mm}.help-section .guide-chip{font-size:2.05mm;min-height:5.6mm;padding:.5mm 1.1mm;gap:1mm;white-space:normal;width:fit-content}.help-section .guide-chip b{width:4mm;height:4mm;flex-shrink:0;font-size:1.3mm}.return-link{display:flex;align-items:center;gap:1.5mm;font-size:2.15mm;margin-top:1.5mm}.help-branch{grid-template-columns:7mm 1.2fr 1fr;padding:2mm;align-items:start}.help-branch strong{font-size:2.6mm}.help-branch p{margin:0 0 1mm;font-size:2.2mm}.route-main{gap:2mm}.route-item{min-height:14mm;padding-top:1.4mm;padding-bottom:1.4mm}.route-track{gap:1.6mm}');
  }
  if(guide.code==='WF-01') {
    const step=document.querySelector('.step-one');
    step.querySelector('p').innerHTML=`Gecontroleerd met ${chip('SC-01')}<br>Tik nu op het test-icoon.`;
    addStyle('.step-one .guide-chip{font-size:2mm;min-height:4.8mm;margin:.4mm 0;padding:.3mm .6mm;gap:.6mm}.step-one .guide-chip b{width:3.4mm;height:3.4mm;font-size:1.2mm}.wf01-main .step-one{gap:1mm;padding-top:2mm;padding-bottom:2mm}.wf01-main{grid-template-rows:minmax(0,.60fr) minmax(0,1.40fr)}');
  }
  if(guide.code==='CMP-02') {
    for(const [label,cx,cy] of [['2A',35.06,292.38],['2B',226.46,345.10]]) {
      const mark=figure(label).querySelector('.target');
      mark.setAttribute('cx',cx);mark.setAttribute('cy',cy);
      mark.setAttribute('rx','16');mark.setAttribute('ry','16');
      mark.setAttribute('data-owner-check',`radio-${label}`);
    }
  }
  if(guide.code==='CMP-04') {
    const fig=figure('1B'), svg=fig.querySelector('svg'), mark=svg.querySelector('.target');
    svg.setAttribute('viewBox','45 480 325 131');
    const image=svg.querySelector('image');image.setAttribute('href',trayImage);image.setAttribute('width',415);image.setAttribute('height',932);
    const clip=el('clipPath',{id:'complete-tray-control-crop'});clip.append(el('rect',{x:45,y:480,width:325,height:131}));
    svg.prepend(clip);image.setAttribute('clip-path','url(#complete-tray-control-crop)');
    Object.entries({x:55,y:556,width:304,height:49,'data-owner-check':'complete-tray-button'}).forEach(([k,v])=>mark.setAttribute(k,v));
    fig.querySelector('figcaption').textContent='Bedieningsdetail: Naar tray op de gecontroleerde regel.';
  }
  if(guide.code==='USR-04') {
    const old=svgNodes[0], defs=old.querySelector('defs').cloneNode(true);
    const image=(id,x,y,w,h,label,caption)=>{
      const clip=old.querySelector(`#usr-04-1-clip-${id} rect`);
      const b=['x','y','width','height'].map(a=>clip.getAttribute(a));
      const nested=el('svg',{x,y,width:w,height:h,viewBox:b.join(' '),preserveAspectRatio:'xMidYMid meet'});
      for(const node of old.querySelectorAll(`[clip-path="url(#usr-04-1-clip-${id})"]`))nested.append(node.cloneNode(true));
      const g=el('g',{'data-component':'preserved-evidence','data-image-label':label});
      g.append(el('rect',{x,y,width:w,height:h,rx:1.3,fill:'#F8FAFC',stroke:'#C8D5E2','stroke-width':.4}),nested);
      g.append(el('circle',{cx:x,cy:y,r:3.7,fill:'white',stroke:'#4F46E5','stroke-width':.65}));
      txt(g,x,y+.65,label,1.9,'#4F46E5',800).setAttribute('text-anchor','middle');
      txt(g,x,y+h+3.5,caption,2,'#53657A',600);return g;
    };
    const svg=el('svg',{xmlns:ns,width:'210mm',height:'297mm',viewBox:'0 0 210 297'});
    svg.append(defs,el('rect',{width:210,height:297,fill:'white'}));
    const rect=(x,y,w,h,fill='#FFFFFF',stroke='#C8D5E2')=>svg.append(el('rect',{x,y,width:w,height:h,rx:1.8,fill,stroke,'stroke-width':.45}));
    rect(12,12,2,17,'#4F46E5','#4F46E5');
    txt(svg,18,22,'USR-04 Gebruiker uitschakelen',6.1,'#102033',900);
    txt(svg,18,29,'Schakel inloggen uit en controleer het opgeslagen account',2.7);
    rect(166,12,32,11,'#ECFDF5','#86EFAC');
    txt(svg,182,17,`Draft v${guide.version}`,2.4,'#047857',800).setAttribute('text-anchor','middle');
    txt(svg,182,21,'2026-09-10',1.9,'#047857',700).setAttribute('text-anchor','middle');
    rect(12,39,186,17,'#F8FAFC');
    [['Rol','Admin',17],['Nodig','Besluit om login uit te schakelen',61],['Vooraf','',151]].forEach(([label,value,x])=>{txt(svg,x,44,label,2.1,'#53657A',800);if(value)txt(svg,x,51,value,2.75,'#102033',800);});
    fullChip(svg,151,47,41,'AC-01');
    const step=(n,y,h,title,lines)=>{
      rect(12,y,186,h);svg.append(el('circle',{cx:12,cy:y,r:7.2,fill:'white',stroke:'#4F46E5','stroke-width':1.9}));
      txt(svg,12,y+1.5,n,4.8,'#4F46E5',900).setAttribute('text-anchor','middle');
      txt(svg,23,y+10,title,3.7,'#102033',900);lines.forEach((line,i)=>txt(svg,23,y+18+i*4,line,2.65));
    };
    step(1,64,48,'Controleer het account',['Open Personen > Toon Alles.','Zoek op naam of gebruikersnaam.','Vergelijk beide en open het account.']);
    txt(svg,23,102,'Identiteit onduidelijk? Stop en vraag hulp.',2.25,'#E83448',800);
    svg.append(image(1,88,70,32,28,'1A','Open Personen > Toon Alles.'),image(2,125,70,65,28,'1B','Zoek de juiste gebruiker.'));
    step(2,117,57,'Schakel inloggen uit',['Kies Gebruiker aanpassen.','Haal het vinkje weg bij:','Deze gebruiker kan inloggen.','Kies Opslaan.']);
    txt(svg,23,163,'Laat wachtwoord, groep en rechten staan.',2.25,'#E83448',800);
    svg.append(image(4,96,130,94,27,'2A','Haal alleen dit vinkje weg; kies daarna Opslaan.'));
    step(3,179,49,'Controleer de opgeslagen stand',['Open na Opslaan de Info-tab.','Controleer naam en gebruikersnaam.','Login ingeschakeld moet Nee zijn.']);
    txt(svg,23,218,'Het bestaande account blijft bewaard.',2.25,'#047857',800);
    svg.append(image(5,105,184,85,35,'3A','Schermvoorbeeld: controleer je gekozen account.'));
    txt(svg,12,235,'Hulp bij uitschakelen',2.65,'#53657A',800);
    const helps=[['Account onduidelijk',['Wijzig niets. Controleer naam','en gebruikersnaam opnieuw.']],['Opslaan mislukt',['Lees de foutmelding. Corrigeer','of vraag een bevoegde Admin.']],['Login staat nog op Ja',['Controleer opnieuw het vinkje.','Sla op en verifieer de Info-tab.']]];
    helps.forEach(([title,lines],i)=>{const x=12+i*63;rect(x,238,60,17,'#FFF7ED','#FDBA74');txt(svg,x+3,243,title,2.5,'#102033',800);lines.forEach((s,j)=>txt(svg,x+3,248+j*3,s,2.15));});
    rect(12,257,186,10,'#ECFDF3','#6EE7A0');txt(svg,17,264,'Klaar als',3,'#047857',900);txt(svg,43,264,'Het juiste account bestaat nog en Login ingeschakeld staat op Nee.',2.4,'#047857');
    fullChip(svg,12,271,67,'USR-01');fullChip(svg,82,271,67,'USR-02');fullChip(svg,12,281,58,'HELP-01');
    rect(176,269,22,22,'white','#102033');txt(svg,187,281,'QR volgt',2.4,'#102033',800).setAttribute('text-anchor','middle');
    txt(svg,187,294,'Digitale gids',1.8,'#102033',700).setAttribute('text-anchor','middle');
    txt(svg,12,294,'Bron: bestaande gecontroleerde schermafbeeldingen | 2026-09-10 | Pagina 1 van 1',1.65);
    const page=document.querySelector('.page');page.replaceChildren(svg);[...document.querySelectorAll('.page')].slice(1).forEach(p=>p.remove());
    document.title='USR-04 Gebruiker uitschakelen v6';
  }
  if(guide.code==='CAT-00') {
    const svg=svgNodes[0];
    const line=[...svg.querySelectorAll('line')].find(n=>n.getAttribute('x1')==='169.5'&&n.getAttribute('y2')==='143');
    if(!line)throw Error('Missing asset connector');line.setAttribute('y2','145');line.setAttribute('data-owner-check','asset-connector');
    const arrow=[...svg.querySelectorAll('polygon')].find(n=>n.getAttribute('points').startsWith('169.5,143'));
    arrow.setAttribute('points','169.5,145 168.15,142.8 170.85,142.8');
    change(svg,'gebruikt 1 of meer','kan meerdere waarden leveren');
    const relation=exact(svg,'gebruikt');
    if(relation&&relation.getAttribute('x')==='137.5')relation.textContent='0 of meer';
    change(svg,'Op het modelnummer: waarden en fabrieksverwachting','Per modelnummer: meerdere waarden en verwachte onderdelen');
    const heading=change(svg,'Op één fysiek asset','Onderdelen op dit asset');
    heading.setAttribute('font-size','1.9');heading.setAttribute('y','132');
    txt(svg,141,136,'0 of meer',1.8,'#138A43',800);
    change(svg,'heeft','meerdere');
    const replaceLines=(before,lines)=>{const n=exact(svg,before);if(!n)throw Error(before);const x=n.getAttribute('x');n.replaceChildren(...lines.map((s,i)=>el('tspan',{x,dy:i?2.25:0},s)));};
    replaceLines('Eén fysiek apparaattag + serienummer',['Eén fysiek apparaat','eigen tag + serienummer','kan eigen waarden hebben']);
    replaceLines('Werkelijk onderdeelmet eigen staat',['Eén werkelijk onderdeel','met eigen staat']);
    const componentTitle=exact(svg,'Geregistreerd component');
    componentTitle.setAttribute('y','153.4');componentTitle.replaceChildren(el('tspan',{x:152,dy:0},'Geregistreerd'),el('tspan',{x:152,dy:3},'component'));
    exact(svg,'Eén werkelijk onderdeelmet eigen staat').setAttribute('y','162');
    replaceLines('Betekenis +invoerregels',['Eén herbruikbare betekenis','met invoerregels']);
    replaceLines('- gebruikt een Componentdefinitie- hoort bij precies één asset- beschrijft actuele hardware',['- meerdere waarden per onderdeel','- kan eigen subonderdelen hebben','- in tray: niet op een asset']);
    exact(svg,'Dit geplaatste record:').textContent='Een componentrecord:';
    const callout=exact(svg,'Een definitie beschrijft wat herbruikbaar is; een baseline verwacht; een fysiek record beschrijft wat er werkelijk aanwezig is.');
    callout.textContent='Een code kan bij meerdere assets horen. Nog geen assets, waarden of onderdelen? Een nieuwe registratie kan leeg beginnen.';
    callout.setAttribute('font-size','2.05');
    change(svg,'Lees eerst de rol van het record','Een voorbeeldblok kan meerdere records voorstellen');
    change(svgNodes[2],'Een Componentdefinitie gebruikt één of meer Attribuutdefinities','Een Componentdefinitie kan meerdere Attribuutdefinities gebruiken').setAttribute('font-size','2.7');
    // Teach reusable building blocks first while retaining the task-dependent route cards.
    const last=svgNodes[5];
    change(last,'Deel 6: Kies de juiste vervolggids','Deel 6: Leer de bouwstenen; kies je taak');
    const intro=exact(last,'Kies op basis van het object dat ontbreekt of gewijzigd moet worden.');
    intro.textContent='Leer eerst de vier bovenste gidsen in volgorde. Dagelijks werk: kies wat ontbreekt; hergebruik wat bestaat.';
    intro.setAttribute('font-size','2.1');
    const blocks=[...last.querySelectorAll('[data-component="guide-route-card"]')];
    const groups=blocks.map((start,i)=>{const stop=blocks[i+1]??last.querySelector('[data-component="physical-followup-routes"]');const group=el('g');let n=start;while(n&&n!==stop){const next=n.nextSibling;group.append(n);n=next;}last.insertBefore(group,stop);return group;});
    const positions=[[12,82],[107,82],[12,117],[107,117],[12,152],[107,152]];
    [2,3,0,1,4,5].forEach((oldIndex,newIndex)=>moveSvgNode(groups[oldIndex],positions[newIndex][0]-positions[oldIndex][0],positions[newIndex][1]-positions[oldIndex][1]));
  }
  if(guide.code==='CAT-01') {
    const second=svgNodes[1], fourth=svgNodes[3];
    change(second,'Volgende pagina','Jouw gekozen route');
    const footer=change(second,'volg A naar p. 5, B naar p. 4 OF C naar p. 3.','');
    footer.remove();
    txt(second,69,254,'A - Code bestaat: pagina 5, stap 7.',2.25,'#7A4E9D',700);
    txt(second,69,258,'B - Code ontbreekt: pagina 4, stap 5.',2.25,'#7A4E9D',700);
    txt(second,69,262,'C - Basismodel ontbreekt: pagina 3, stap 4.',2.25,'#7A4E9D',700);
    const warning=exact(fourth,'Wijkt één asset af? Leg dat vast op het asset;maak daarvoor geen nieuw modelnummer.');
    if(!warning)throw Error('Missing CAT-01 deviation warning');
    warning.replaceChildren();const x=warning.getAttribute('x');
    ['Later RAM vervangen: 8 GB wordt 16 GB?','De fabrikantcode blijft dezelfde.','Registreer de wijziging bij dat apparaat.'].forEach((s,i)=>warning.append(el('tspan',{x,dy:i?2.6:0},s)));
    warning.setAttribute('font-size','1.95');warning.setAttribute('y','201');
  }
  return {code:guide.code,pages:document.querySelectorAll('.page,.guide-page').length};
}
