import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import { browserLaunchOptions, loadGuideDependency, repoRoot } from './lib/guide-paths.mjs';

const round=path.join(repoRoot,'resources/manuals/operator-guides/review-rounds/2026-10-20-remaining-guides');
assert.ok(!fs.existsSync(path.join(round,'manifest.sha256')),'Sealed evidence cannot be replaced.');
const fixture=JSON.parse(fs.readFileSync(path.join(round,'fixture.json'),'utf8').replace(/^\uFEFF/,''));
const out=path.join(round,'evidence');fs.mkdirSync(out,{recursive:true});
const password=process.env.GUIDE_CAPTURE_PASSWORD;assert.ok(password);
const {chromium}=loadGuideDependency('playwright');
const browser=await chromium.launch(browserLaunchOptions({headless:true}));
const mode=process.argv[2]??'probe';let page;
async function actor(key,mobile=false){
 const username=key==='supervisor'?'demo_supervisor':fixture.actors[key].username;
 const context=await browser.newContext({ignoreHTTPSErrors:true,viewport:mobile?{width:415,height:915}:{width:1280,height:1000},deviceScaleFactor:mobile?2:1.5,isMobile:mobile,hasTouch:mobile});
 page=await context.newPage();await page.goto('https://dev.inbit/login',{waitUntil:'networkidle'});
 await page.locator('input[name="username"]').fill(username);await page.locator('input[name="password"]').fill(password);
 await Promise.all([page.waitForURL(u=>!u.pathname.endsWith('/login')),page.locator('button[type="submit"]').click()]);
 return context;
}
async function open(route){await page.goto('https://dev.inbit'+route,{waitUntil:'networkidle'});assert.ok(!page.url().includes('/login'));}
async function capture(id,notes={}){
 await page.waitForTimeout(450);
 const state=await page.evaluate(()=>{
  const box=e=>{const r=e.getBoundingClientRect();return{x:r.x+scrollX,y:r.y+scrollY,w:r.width,h:r.height};};
  return {locale:document.documentElement.lang,path:location.pathname+location.search+location.hash,viewport:{width:innerWidth,height:innerHeight,dpr:devicePixelRatio},text:document.body.innerText,
   elements:[...document.querySelectorAll('h1,h2,h3,h4,.breadcrumb,.nav-tabs,.form-group,.box-header,.box-footer,.alert,.modal.in .modal-content,.asset-component-card,label,input:not([type="hidden"]):not([type="password"]),select,textarea,button,a.btn,th,tbody tr,#details .row-new-striped > .row,[data-component-template-row],[data-contribution-row],[data-subcomponent-template-row]')].filter(e=>e.getBoundingClientRect().width>0).map(e=>({tag:e.tagName,id:e.id,name:e.getAttribute('name'),type:e.getAttribute('type'),classes:e.className,text:(e.innerText??'').slice(0,1400),value:e.matches('input:not([type="password"]),select,textarea')?e.value:undefined,bounds:box(e)})),
   links:[...document.querySelectorAll('a[href]')].map(e=>({text:e.innerText.trim(),href:e.getAttribute('href'),bounds:box(e)}))};
 });
 assert.equal(state.locale,'nl-NL');state.notes=notes;
 await page.screenshot({path:path.join(out,id+'.png'),fullPage:true});
 fs.writeFileSync(path.join(out,id+'.json'),JSON.stringify(state,null,2));
 console.log(JSON.stringify({id,path:state.path,elements:state.elements.length}));return state;
}
async function optional(){const notes=page.locator('#notes');if(await notes.count() && !await notes.isVisible()){await page.getByText('Optionele informatie',{exact:true}).click();await page.waitForTimeout(350);}}
try{
 if(mode==='probe'){
  await actor('superadmin');await open('/users/create');await optional();await capture('USR-CREATE');
  await open('/users/'+fixture.actors.user.id+'/edit');await optional();await capture('USR-EDIT');
  await open('/admin/groups/'+fixture.group.id+'/edit');await capture('GROUP-EDIT');
  await open('/statuslabels');await capture('STATUS-LIST');
  await actor('supervisor');await open('/models/2/model-numbers/2/spec');await capture('CAT-SPEC');
  await open('/attributes/create');await capture('ATTR-CREATE');
  await open('/admin/settings/component-definitions/17/edit');await capture('DEF-EDIT');
  await actor('senior',true);await open('/hardware/18#components');await page.locator('a[href="#components"]').first().click();await capture('CMP-ASSET');
 } else if(mode==='catalog'){
  await actor('supervisor');
  for(const [id,route] of [['CAT-MODELS','/models'],['CAT-MODEL','/models/2'],['CAT-MODEL-CREATE','/models/create'],['CAT-NUMBER-CREATE','/models/2/model-numbers/create'],['CAT-NUMBERS','/admin/settings/model-numbers?search=2E9F8EA%23ABH'],['CAT-SPEC','/models/2/model-numbers/2/spec'],['ATTR-LIST','/attributes?search=geheugen'],['ATTR-ENUM','/attributes/6/edit'],['ATTR-SUM','/attributes/5/edit'],['ATTR-DISTINCT','/attributes/20/edit'],['DEF-LIST','/admin/settings/component-definitions?search=RAM'],['DEF-RAM','/admin/settings/component-definitions/17/edit'],['DEF-PARENT','/admin/settings/component-definitions/1/edit']]){
   await open(route);await capture(id,{actor:'Supervisor',serverWrite:false});
  }
  await open('/attributes/create');await page.locator('#label').fill('Aantal geheugenslots - handleiding');await page.locator('#datatype').selectOption('int');
  await page.locator('#constraints_min').fill('0');await page.locator('#constraints_max').fill('8');await page.locator('#constraints_step').fill('1');
  await page.locator('#category_ids').selectOption('3',{force:true});await capture('ATTR-NUMERIC-CREATE',{actor:'Supervisor',serverWrite:false});
 } else if(mode==='users-read'){
  await actor('superadmin');await open('/users');await capture('USR-LIST');
  await open('/users/'+fixture.actors.user.id);await capture('USR-DETAIL');
  await open('/users/'+fixture.actors.user.id+'/edit');await optional();await capture('USR-EDIT');
  await page.getByRole('link',{name:'Machtigingen',exact:true}).click();await capture('USR-PERMISSIONS');
  await open('/admin/groups');await capture('GROUP-LIST');
  await open('/admin/groups/'+fixture.group.id+'/edit');await capture('GROUP-EDIT');
  await open('/admin/groups/'+fixture.group.id);await capture('GROUP-DETAIL');
  await open('/statuslabels');await capture('STATUS-LIST');
  await actor('user');await page.locator('li.user-menu > a').click();await capture('AC-MENU');
  await open('/account/password');await capture('AC-PASSWORD');
 } else if(mode==='catalog-details'){
  await actor('supervisor');await open('/models');await page.locator('a.sidebar-toggle').click();
  await page.locator('.sidebar-menu').getByText('Instellingen',{exact:true}).click();await capture('CAT-NAV',{actor:'Supervisor',serverWrite:false});
  await open('/models');const search=page.locator('.fixed-table-toolbar .search input').first();await search.fill('HP ProBook 450 G8');await page.waitForTimeout(850);await capture('CAT-MODELS-FILTERED',{actor:'Supervisor',serverWrite:false});
  await open('/models/create');await page.locator('#name').fill('HP ProBook 450 G8');
  for(const[id,value]of[['category_select_id','Laptops'],['manufacturer_select_id','HP']]){await page.locator('#select2-'+id+'-container').click();await page.locator('.select2-container--open .select2-search__field').fill(value);await page.getByRole('option',{name:value,exact:true}).click();}
  await capture('CAT-MODEL-FILLED',{actor:'Supervisor',serverWrite:false});
  await open('/models/2/model-numbers/create');await page.locator('#code').fill('2E9F8EA#ABH');await page.locator('#label').fill('HP ProBook 450 G8 - i5-1135G7 - 8GB - 256GB');await capture('CAT-NUMBER-FILLED',{actor:'Supervisor',serverWrite:false});
  await open('/models/2/model-numbers/2/spec');await page.locator('.js-attribute-search').first().fill('Introductiejaar');await capture('CAT-SPEC-ATTRIBUTE',{actor:'Supervisor',serverWrite:false});
  await page.locator('[data-add-component-template]').click();const row=page.locator('[data-component-template-row]').last();await row.locator('.js-component-template-definition-select').selectOption('17');await row.locator('input[type="number"]').fill('1');await capture('CAT-SPEC-ADD',{actor:'Supervisor',serverWrite:false});
  assert.ok(!fs.existsSync(path.join(round,'attribute-journey.json')),'Numeric fixture already saved.');
  await open('/attributes/create');await page.locator('#label').fill('Aantal geheugenslots - handleiding');await page.locator('#datatype').selectOption('int');await page.locator('#category_ids').selectOption('3',{force:true});await page.locator('#constraints_min').fill('0');await page.locator('#constraints_max').fill('8');await page.locator('#constraints_step').fill('1');await capture('ATTR-NUMERIC-CREATE',{actor:'Supervisor',serverWrite:false});
  await page.locator('#submit_button').click();await page.waitForTimeout(850);await capture('ATTR-NUMERIC-RESULT',{actor:'Supervisor',actualSaved:true});
  fs.writeFileSync(path.join(round,'attribute-journey.json'),JSON.stringify({label:'Aantal geheugenslots - handleiding',route:page.url().replace('https://dev.inbit',''),datatype:'int',scope:'Laptops',aggregation:'sum',constraints:{min:0,max:8,step:1},createdBy:'Supervisor'},null,2));
 } else if(mode==='registration'){
  assert.ok(!fs.existsSync(path.join(round,'registration.json')),'Registration already executed; use read-only capture.');
  await actor('supervisor',true);await capture('REG-DASH');await open('/hardware/create');
  await page.locator('input[name="serials[1]"]').fill('GUIDE-SN-20261020-02');
  await page.locator('#select2-category_select_id-container').click();await page.locator('.select2-container--open .select2-search__field').fill('Laptops');await page.getByRole('option',{name:'Laptops',exact:true}).click();
  await page.locator('#select2-model_select_id-container').click();await page.locator('.select2-container--open .select2-search__field').fill('HP ProBook 450 G8');
  await page.getByRole('option',{name:/HP ProBook 450 G8/}).first().click();
  await page.locator('#status_select_id').selectOption('9');await page.locator('#status_change_note').fill('Handleidingdemonstratie; geen fysiek apparaat geregistreerd.');
  await page.locator('#notes').fill('Manuals remaining guides 2026-10-20: synthetic demonstration; no physical tests claimed.');
  const tag=await page.locator('#asset_tag').inputValue();await capture('REG-FORM',{actor:'Supervisor',proposedTag:tag,serverWrite:false});
  await page.locator('#submit_button').click();await page.waitForTimeout(1800);await capture('REG-SAVED',{actor:'Supervisor',proposedTag:tag,submittedOnce:true});
  const id=Number(page.url().match(/hardware\/(\d+)\/created/)?.[1]);assert.ok(id);assert.ok((await page.locator('body').innerText()).includes('GUIDE-SN-20261020-02'));
  fs.writeFileSync(path.join(round,'registration.json'),JSON.stringify({id,tag,serial:'GUIDE-SN-20261020-02',physicalDevice:false},null,2));
  await open('/hardware/'+id);await capture('REG-ASSET',{actor:'Supervisor',serverWrite:false});
 } else if(mode==='registration-read'){
  const reg=JSON.parse(fs.readFileSync(path.join(round,'registration.json'),'utf8'));await actor('supervisor',true);await open('/hardware/'+reg.id+'/created');await capture('REG-SAVED',{actor:'Supervisor',serverWrite:false,actualSaved:true});await open('/hardware/'+reg.id);await capture('REG-ASSET',{actor:'Supervisor',serverWrite:false});
 } else if(mode==='components'){
  const reg=JSON.parse(fs.readFileSync(path.join(round,'registration.json'),'utf8'));const progress=path.join(round,'component-journey.json');assert.ok(!fs.existsSync(progress),'Component journey already executed; inspect before resuming.');
  await actor('senior',true);await open('/hardware/'+reg.id+'#components');await page.locator('a[href="#components"]').first().click();await capture('CMP-ENTRY',{actor:'Senior Refurbisher',assetId:reg.id,serverWrite:false});
  await page.getByRole('link',{name:'Add / Install Component',exact:true}).click();await capture('CMP-NEW-ENTRY',{actor:'Senior Refurbisher',serverWrite:false});
  await page.locator('[data-toggle-new-component]').click();await page.locator('#component_definition_id').selectOption('17',{force:true});await page.locator('#serial').fill('GUIDE-CMP-20261020-01');await page.locator('#condition_code').selectOption('good');await page.locator('#note').fill('Handleidingdemonstratie; geen fysiek onderdeel geplaatst.');
  await capture('CMP-NEW-DEFINITION',{actor:'Senior Refurbisher',serverWrite:false});await page.locator('input[name="creation_mode"][value="custom"]').check();await page.locator('#display_name').fill('Voorbeeldonderdeel');await capture('CMP-NEW-CUSTOM',{actor:'Senior Refurbisher',serverWrite:false,alternativeOnly:true});
  await page.locator('input[name="creation_mode"][value="definition"]').check();await page.getByRole('button',{name:'Create And Install',exact:true}).click();await page.waitForTimeout(1100);await page.locator('a[href="#components"]').first().click();
  const card=page.locator('.asset-component-card').filter({hasText:'GUIDE-CMP-20261020-01'}).first();await card.waitFor();const id=Number(await card.locator('[data-tray-id]').getAttribute('data-tray-id'));assert.ok(id);const tag=(await card.locator('.asset-component-card__meta').innerText()).split('\n').find(s=>s.startsWith('INBIT-C-'));assert.ok(tag);
  const journey={id,tag,serial:'GUIDE-CMP-20261020-01',assetId:reg.id,physicalWork:false,createdAndInstalled:true};fs.writeFileSync(progress,JSON.stringify(journey,null,2));await capture('CMP-NEW-RESULT',{actor:'Senior Refurbisher',actualSaved:true});
  await card.getByRole('button',{name:'Naar tray',exact:true}).click();await page.locator('#assetComponentTrayModal').waitFor({state:'visible'});await capture('CMP-TRAY-CONFIRM',{actor:'Senior Refurbisher',serverWrite:false});
  await page.locator('#asset_component_tray_modal_note').fill('Handleidingdemonstratie; geen fysiek onderdeel verwijderd.');await page.getByRole('button',{name:'Naar tray bevestigen',exact:true}).click();await page.waitForTimeout(900);await open('/components/'+id);await capture('CMP-TRAY-RESULT',{actor:'Senior Refurbisher',actualSaved:true});journey.movedToTray=true;fs.writeFileSync(progress,JSON.stringify(journey,null,2));
  await open('/hardware/'+reg.id+'/components/add');await page.locator('#asset_add_component_id').selectOption(String(id),{force:true});await capture('CMP-EXISTING-SELECTED',{actor:'Senior Refurbisher',serverWrite:false});await page.getByRole('button',{name:'Install',exact:true}).click();await page.waitForTimeout(900);await page.locator('a[href="#components"]').first().click();await capture('CMP-EXISTING-RESULT',{actor:'Senior Refurbisher',actualSaved:true});journey.reinstalledSameIdentity=true;fs.writeFileSync(progress,JSON.stringify(journey,null,2));
  await page.locator('.asset-component-card').filter({hasText:journey.serial}).first().getByRole('button',{name:'Naar tray',exact:true}).click();await page.locator('#asset_component_tray_modal_note').fill('Einde handleidingdemonstratie; geen fysiek onderdeel.');await page.getByRole('button',{name:'Naar tray bevestigen',exact:true}).click();await page.waitForTimeout(900);await open('/components/'+id);await capture('CMP-FINAL-TRAY',{actor:'Senior Refurbisher',actualSaved:true});journey.finalState='in_tray; asset null';fs.writeFileSync(progress,JSON.stringify(journey,null,2));
 } else if(mode==='components-resume'){
  const progress=path.join(round,'component-journey.json');const j=JSON.parse(fs.readFileSync(progress,'utf8'));assert.equal(j.createdAndInstalled,true);assert.ok(!j.finalState);
  await actor('senior',true);await open('/hardware/'+j.assetId+'#components');await page.locator('a[href="#components"]').first().click();
  await page.locator('.asset-component-card').filter({hasText:j.serial}).first().locator('[data-tray-id]').click();await page.locator('#asset_component_tray_modal_note').fill('Handleidingdemonstratie; geen fysiek onderdeel verwijderd.');await page.locator('#assetComponentTrayModal button[type="submit"]').click();await page.waitForTimeout(800);
  await open('/components/'+j.id);await capture('CMP-TRAY-RESULT',{actor:'Senior Refurbisher',actualSaved:true});j.movedToTray=true;fs.writeFileSync(progress,JSON.stringify(j,null,2));
  await open('/hardware/'+j.assetId+'/components/add');await page.locator('#asset_add_component_id').selectOption(String(j.id),{force:true});await capture('CMP-EXISTING-SELECTED',{actor:'Senior Refurbisher',serverWrite:false});await page.getByRole('button',{name:'Install',exact:true}).click();await page.waitForTimeout(800);await page.locator('a[href="#components"]').first().click();await capture('CMP-EXISTING-RESULT',{actor:'Senior Refurbisher',actualSaved:true});j.reinstalledSameIdentity=true;fs.writeFileSync(progress,JSON.stringify(j,null,2));
  await page.locator('.asset-component-card').filter({hasText:j.serial}).first().locator('[data-tray-id]').click();await page.locator('#asset_component_tray_modal_note').fill('Einde handleidingdemonstratie; geen fysiek onderdeel.');await page.locator('#assetComponentTrayModal button[type="submit"]').click();await page.waitForTimeout(800);await open('/components/'+j.id);await capture('CMP-FINAL-TRAY',{actor:'Senior Refurbisher',actualSaved:true});j.finalState='in_tray; asset null';fs.writeFileSync(progress,JSON.stringify(j,null,2));
 } else if(mode==='navigation'){
  await actor('supervisor');await open('/models');if(await page.locator('body').evaluate(e=>e.classList.contains('sidebar-collapse')))await page.locator('a.sidebar-toggle').click();
  const modelLink=page.locator('.sidebar-menu').getByText('Asset modellen',{exact:true});if(!await modelLink.isVisible())await page.locator('.sidebar-menu').getByText('Instellingen',{exact:true}).click();await page.waitForTimeout(500);await capture('CAT-NAV',{actor:'Supervisor',serverWrite:false});
  await actor('admin');await open('/users');if(await page.locator('body').evaluate(e=>e.classList.contains('sidebar-collapse')))await page.locator('a.sidebar-toggle').click();
  const people=page.locator('.sidebar-menu').getByText('Personen',{exact:true});if(await people.count() && !await people.locator('..').getByText('Toon Alles',{exact:true}).isVisible())await people.click();await page.waitForTimeout(500);await capture('USR-NAV',{actor:'Admin',serverWrite:false});
  await open('/attributes/5/edit');await capture('MAINT-INUSE',{actor:'Admin',serverWrite:false});
  await actor('superadmin');await open('/admin/groups/'+fixture.group.id+'/edit');await page.locator('#submit_button').click();await page.waitForTimeout(700);await open('/admin/groups/'+fixture.group.id);await capture('GROUP-SAVED',{actor:'Superadmin',actualSaved:true});
 } else if(mode==='catalog-repair'){
  await actor('supervisor');await open('/models');if(await page.locator('body').evaluate(e=>e.classList.contains('sidebar-collapse')))await page.locator('a.sidebar-toggle').click();const search=page.locator('.fixed-table-toolbar .search input').first();await search.fill('HP ProBook 450 G8');await search.press('Enter');await page.waitForFunction(()=>document.querySelectorAll('#asssetModelsTable tbody tr').length===1);await capture('CAT-MODELS-FILTERED',{actor:'Supervisor',serverWrite:false,searchApplied:true});
  await Promise.all([page.waitForURL(u=>u.pathname==='/models/create'),page.locator('button[name="btnAdd"]').click()]);await capture('CAT-MODEL-CREATE-ENTRY',{actor:'Supervisor',serverWrite:false});
  await open('/admin/settings/component-definitions/1/edit');await capture('DEF-PARENT',{actor:'Supervisor',serverWrite:false});
  await open('/models/2/model-numbers/2/spec');await capture('CAT-SPEC',{actor:'Supervisor',serverWrite:false});
  await actor('admin');for(const[id,route]of[['MAINT-SPEC','/models/2/model-numbers/2/spec'],['MAINT-DEF-PARENT','/admin/settings/component-definitions/1/edit'],['MAINT-DEF-RAM','/admin/settings/component-definitions/17/edit']]){await open(route);await capture(id,{actor:'Admin',serverWrite:false});}
 } else if(mode==='component-layout'){
  await actor('senior',true);await open('/components/3');await capture('CMP-TRAY-DETAIL',{actor:'Senior Refurbisher',serverWrite:false});
  const rows=await page.locator('dt,dd').evaluateAll(es=>es.map(e=>{const r=e.getBoundingClientRect();return {tag:e.tagName,text:e.innerText,bounds:{x:r.x+scrollX,y:r.y+scrollY,w:r.width,h:r.height}};}));fs.writeFileSync(path.join(out,'component-detail-layout.json'),JSON.stringify(rows,null,2));
 } else if(mode==='user-target-details'){
  await actor('admin');await open('/users');if(await page.locator('body').evaluate(e=>e.classList.contains('sidebar-collapse')))await page.locator('a.sidebar-toggle').click();
  const people=page.locator('.sidebar-menu').getByText('Personen',{exact:true});const tree=people.locator('xpath=ancestor::li[contains(@class,"treeview")][1]');if(!await tree.getByText('Toon Alles',{exact:true}).isVisible())await people.click();await tree.getByText('Toon Alles',{exact:true}).waitFor({state:'visible'});await capture('USR-NAV',{actor:'Admin',serverWrite:false});
  await open('/statuslabels/10/edit');await page.locator('select[name="new_status_access_user_id"]').locator('..').locator('.select2-selection').click();await page.locator('.select2-container--open .select2-search__field').fill('Tessavd');await page.getByRole('option',{name:/Tessavd/}).first().click();await page.locator('select[name="new_status_access_user_view"]').selectOption({label:'Zien: Toestaan'});await page.locator('select[name="new_status_access_user_select"]').selectOption({label:'Kiezen / gebruiken: Toestaan'});await capture('STATUS-USER-TARGET',{actor:'Admin',serverWrite:false,userId:17});
 } else if(mode==='group-navigation'){
  await actor('superadmin');await open('/admin');
  const icon=page.locator('header a[href$="/admin"] i,header a[href$="/admin"] svg').first();
  const adminGearIcon=await icon.evaluate(e=>{const r=e.getBoundingClientRect();return{x:r.x+scrollX,y:r.y+scrollY,w:r.width,h:r.height};});
  await capture('GROUP-NAV',{actor:'Superadmin',serverWrite:false,adminGearIcon});
 } else if(mode==='recognition-refinements'){
  await actor('supervisor');await open('/models/2/model-numbers/2/spec');
  if(!await page.locator('body').evaluate(e=>e.classList.contains('sidebar-collapse')))await page.locator('a.sidebar-toggle').click();
  await capture('CAT-SPEC',{actor:'Supervisor',serverWrite:false,sidebarCollapsedForFormWidth:true});
  await open('/attributes/create');await page.locator('#label').fill('Geheugenfamilie - voorbeeld');await page.locator('#datatype').selectOption('enum');
  await page.locator('#new_option_value').fill('ddr4');await page.locator('#new_option_label').fill('DDR4');await page.locator('#new_option_sort').fill('10');
  await page.locator('[data-option-add]').click();await capture('ATTR-ENUM-NEW',{actor:'Supervisor',serverWrite:false,clientOnlyOptionAdded:true,alternativeExample:true});
  await open('/admin/settings/component-definitions?search=RAM%208GB%20DDR4');await capture('DEF-LIST',{actor:'Supervisor',serverWrite:false});
  await actor('admin');await open('/admin/settings/component-definitions?search=RAM%208GB%20DDR4');await capture('MAINT-DEF-LIST',{actor:'Admin',serverWrite:false});
  await open('/attributes?search=Geheugentype');await capture('MAINT-ATTR-LIST',{actor:'Admin',serverWrite:false});
  await actor('superadmin');await open('/admin');await capture('GROUP-NAV',{actor:'Superadmin',serverWrite:false});
 } else if(mode==='extra'){
  await actor('user',true);await page.locator('li.user-menu > a').click();await capture('AC-MOBILE-MENU',{actor:'Refurbisher',serverWrite:false});await open('/account/password');await capture('AC-MOBILE-FORM',{actor:'Refurbisher',serverWrite:false});
  await actor('superadmin');await open('/users/'+fixture.actors.user.id+'/edit');await optional();await page.locator('select[name="groups[]"]').selectOption(String(fixture.group.id),{force:true});await page.locator('#submit_button').click();await page.waitForTimeout(600);
  await actor('user');await open('/models');await capture('GROUP-ORDINARY-VIEW',{actor:'Ordinary member of '+fixture.group.name,serverWrite:false});const response=await page.goto('https://dev.inbit/models/create',{waitUntil:'networkidle'});
  assert.ok(response.status()===403 || (await page.locator('body').innerText()).match(/bevoegd|permission|toegang|authorized/i),'Prohibited creation route was not blocked');
  fs.writeFileSync(path.join(round,'ordinary-user-trial.json'),JSON.stringify({userId:fixture.actors.user.id,groupId:fixture.group.id,superuser:false,admin:false,directOverrides:[],viewModels:200,createModels:response.status(),prohibitedResult:'Access refused',physicalWork:false},null,2));
  await actor('superadmin');await open('/users/'+fixture.actors.user.id+'/edit');await optional();await page.locator('select[name="groups[]"]').selectOption('1',{force:true});await page.locator('#submit_button').click();await page.waitForTimeout(600);
 } else if(mode==='maintenance'){
  await actor('admin');
  for(const[id,route]of[['MAINT-NUMBERS','/admin/settings/model-numbers?search=2E9F8EA%23ABH'],['MAINT-NUMBER-EDIT','/admin/settings/model-numbers/2/edit'],['MAINT-SPEC','/models/2/model-numbers/2/spec'],['MAINT-ATTR','/attributes/20/edit'],['MAINT-ENUM','/attributes/6/edit'],['MAINT-ATTR-LIST','/attributes?search=geheugen'],['MAINT-DEF-LIST','/admin/settings/component-definitions?search=RAM'],['MAINT-DEF-PARENT','/admin/settings/component-definitions/1/edit'],['MAINT-DEF-RAM','/admin/settings/component-definitions/17/edit'],['STATUS-EDIT','/statuslabels/10/edit']]){
   await open(route);await capture(id,{actor:'Admin',serverWrite:false});
  }
 } else if(mode==='users-actions'){
  assert.ok(!fs.existsSync(path.join(round,'user-journey.json')),'Owned user journey already executed.');
  await actor('admin');await open('/users/create');
  await page.locator('#first_name').fill('Tessa');await page.locator('#last_name').fill('van Dijk');await page.locator('#username').click();await page.locator('#username').fill('Tessavd');await page.locator('#email').click();await page.locator('#email').fill('tessa.vandijk@example.test');await page.locator('#activated').check();
  await capture('USR-NEW-IDENTITY',{actor:'Admin',secretVisible:false});
  await page.getByText('Genereer',{exact:true}).click();
  const generated=await page.locator('input[name="password"]').inputValue();assert.ok(generated.length>=8);assert.equal(await page.locator('input[name="password_confirmation"]').inputValue(),generated);
  await optional();await page.locator('select[name="locale"]').selectOption('nl-NL',{force:true});await page.locator('#notes').fill(fixture.marker);
  await page.locator('select[name="redirect_option"]').selectOption({label:'Ga naar Gebruiker'},{force:true});
  await Promise.all([page.waitForURL(u=>/\/users\/\d+$/.test(u.pathname)),page.locator('#submit_button').click()]);
  const id=Number(page.url().match(/users\/(\d+)$/)?.[1]);assert.ok(id);
  await open('/users/'+id);await capture('USR-CREATED',{actor:'Admin',actualSaved:true});
  await actor('superadmin');await open('/users/'+id+'/edit');await optional();await page.locator('select[name="groups[]"]').selectOption('1');await capture('USR-GROUP-CHOSEN',{actor:'Superadmin',userId:id});
  await page.locator('#submit_button').click();await page.waitForTimeout(800);await open('/users/'+id);await capture('USR-GROUP-SAVED',{actor:'Superadmin',actualSaved:true,userId:id});
  await actor('admin');await open('/users/'+id+'/edit');await capture('USR-RESET-CONTROL',{actor:'Admin',userId:id,secretVisible:false});
  await page.getByText('Genereer',{exact:true}).click();const reset=await page.locator('input[name="password"]').inputValue();assert.ok(reset.length>=8);assert.equal(await page.locator('input[name="password_confirmation"]').inputValue(),reset);
  await page.locator('#submit_button').click();await page.waitForTimeout(800);await open('/users/'+id);await capture('USR-RESET-SAVED',{actor:'Admin',actualSaved:true,userId:id});
  const userContext=await browser.newContext({ignoreHTTPSErrors:true,viewport:{width:1280,height:1000},deviceScaleFactor:1.5});page=await userContext.newPage();await page.goto('https://dev.inbit/login',{waitUntil:'networkidle'});
  await page.locator('input[name="username"]').fill('Tessavd');await page.locator('input[name="password"]').fill(reset);await Promise.all([page.waitForURL(u=>!u.pathname.endsWith('/login')),page.locator('button[type="submit"]').click()]);
  await open('/account/password');await capture('AC-OWN-FORM',{actor:'Refurbisher',userId:id,secretVisible:false});
  const inputs=page.locator('input[type="password"]');assert.equal(await inputs.count(),3);await inputs.nth(0).fill(reset);await inputs.nth(1).fill(password+'-Own-20261020!');await inputs.nth(2).fill(password+'-Own-20261020!');
  await page.getByRole('button',{name:'Opslaan',exact:true}).click();await page.waitForTimeout(850);await capture('AC-OWN-SAVED',{actor:'Refurbisher',actualSaved:true,userId:id});
  await actor('admin');await open('/users/'+id+'/edit');await page.locator('#activated').uncheck();await capture('USR-DISABLE-FORM',{actor:'Admin',userId:id});await page.locator('#submit_button').click();await page.waitForTimeout(850);await open('/users/'+id);await capture('USR-DISABLED',{actor:'Admin',actualSaved:true,userId:id});
  fs.writeFileSync(path.join(round,'user-journey.json'),JSON.stringify({id,username:'Tessavd',createdBy:'Admin',groupAssignedBy:'Superadmin',group:'Refurbisher',resetLoginSucceeded:true,ownPasswordChanged:true,finalLoginEnabled:false,passwordsRetained:false},null,2));
 } else throw new Error('Unknown capture mode '+mode);
}catch(error){if(page && mode!=='users-actions'){await page.screenshot({path:path.join(out,'failure-'+mode+'.png'),fullPage:true}).catch(()=>{});fs.writeFileSync(path.join(out,'failure-'+mode+'.txt'),await page.locator('body').innerText().catch(()=>''));}throw error;}
finally{await browser.close();}
