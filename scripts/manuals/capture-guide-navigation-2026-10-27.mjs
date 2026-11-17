import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import {browserLaunchOptions, loadGuideDependency, repoRoot} from './lib/guide-paths.mjs';

const round=path.join(repoRoot,'resources/manuals/operator-guides/review-rounds/2026-10-27-navigation-and-space');
assert.ok(!fs.existsSync(path.join(round,'manifest.sha256')),'Sealed round: create a new revision.');
const out=path.join(round,'evidence');fs.mkdirSync(out,{recursive:true});
const password=process.env.GUIDE_CAPTURE_PASSWORD;assert.ok(password,'Capture password must come from the environment.');
const mode=process.argv[2]??'read';assert.ok(['read','registration','profile','saved'].includes(mode));
const serial='GUIDE271027A';
const {chromium}=loadGuideDependency('playwright');
const browser=await chromium.launch(browserLaunchOptions({headless:true}));
let page;
async function actor(username,role,mobile=false){
 const context=await browser.newContext({ignoreHTTPSErrors:true,viewport:mobile?{width:415,height:915}:{width:1280,height:1100},deviceScaleFactor:mobile?2:1.5,isMobile:mobile,hasTouch:mobile});
 page=await context.newPage();page.role=role;
 await page.goto('https://dev.inbit/login',{waitUntil:'networkidle'});
 await page.locator('[name="username"]').fill(username);await page.locator('[name="password"]').fill(password);
 await Promise.all([page.waitForURL(u=>!u.pathname.endsWith('/login')),page.locator('button[type="submit"]').click()]);
 return context;
}
async function open(route){await page.goto('https://dev.inbit'+route,{waitUntil:'networkidle'});assert.ok(!page.url().includes('/login'));}
async function capture(id,notes={}){
 await page.waitForTimeout(350);
 const state=await page.evaluate(()=>{
  const bounds=e=>{const r=e.getBoundingClientRect();return{x:r.x+scrollX,y:r.y+scrollY,w:r.width,h:r.height};};
  const visible=e=>e.getBoundingClientRect().width>0&&e.getBoundingClientRect().height>0;
  return {locale:document.documentElement.lang,path:location.pathname+location.search+location.hash,viewport:{width:innerWidth,height:innerHeight,dpr:devicePixelRatio},text:document.body.innerText,
   profileIcons:[...document.querySelectorAll('li.user-menu > a img,li.user-menu > a svg,li.user-menu > a .fa,li.user-menu > a .fas')].filter(visible).map(e=>({tag:e.tagName,bounds:bounds(e)})),
   profileControls:[...document.querySelectorAll('li.user-menu > a')].map(e=>({html:e.outerHTML,bounds:bounds(e),before:{content:getComputedStyle(e,'::before').content,fontSize:getComputedStyle(e,'::before').fontSize,fontFamily:getComputedStyle(e,'::before').fontFamily}})),
   elements:[...document.querySelectorAll('h1,h2,h3,h4,.breadcrumb,.form-group,.box-header,.box-footer,.alert,label,input:not([type="hidden"]):not([type="password"]),select,textarea,button,a,tbody tr,li.user-menu > a i,li.user-menu > a span')].filter(visible).map(e=>({tag:e.tagName,id:e.id,name:e.getAttribute('name'),classes:e.className,text:(e.innerText??'').slice(0,1500),href:e.getAttribute('href'),value:e.matches('input:not([type="password"]),select,textarea')?e.value:undefined,bounds:bounds(e)}))};
 });
 assert.equal(state.locale,'nl-NL');state.notes={actor:page.role,...notes};
 await page.screenshot({path:path.join(out,id+'.png'),fullPage:true});
 fs.writeFileSync(path.join(out,id+'.json'),JSON.stringify(state,null,2));
 console.log(JSON.stringify({id,path:state.path}));return state;
}
async function fillAsset(){
 await page.locator('input[name="serials[1]"]').fill(serial);
 await page.locator('#select2-category_select_id-container').click();await page.locator('.select2-container--open .select2-search__field').fill('Laptops');await page.getByRole('option',{name:'Laptops',exact:true}).click();
 await page.locator('#select2-model_select_id-container').click();await page.locator('.select2-container--open .select2-search__field').fill('HP ProBook 450 G8');await page.getByRole('option',{name:/HP ProBook 450 G8/}).first().click();
 await page.locator('#status_select_id').selectOption('9');assert.equal(await page.locator('#status_change_note').inputValue(),'');
}
try{
 if(mode==='profile'){
 await actor('guide_20261020_user','Refurbisher',true);await page.locator('li.user-menu > a').click();await capture('AC-MENU',{serverWrite:false});await open('/account/password');await capture('AC-PASSWORD',{serverWrite:false});
 }else if(mode==='saved'){
 const reg=JSON.parse(fs.readFileSync(path.join(round,'registration.json'),'utf8'));
 await actor('demo_supervisor','Supervisor');await open('/hardware/'+reg.id+'/created');await capture('AST-SAVED-DESKTOP',{serverWrite:false,actualSaved:true,assetId:reg.id});
 }else if(mode==='read'){
 await actor('guide_20261020_user','Refurbisher',true);
 await page.locator('li.user-menu > a').click();await capture('AC-MENU',{serverWrite:false});
 await open('/account/password');await capture('AC-PASSWORD',{serverWrite:false});
 await actor('demo_supervisor','Supervisor');
 await open('/');await capture('AST-DASH',{serverWrite:false});
 await Promise.all([page.waitForURL(u=>u.pathname==='/hardware'),page.locator('a[href="https://dev.inbit/hardware"]').filter({hasText:'alles weergeven'}).click()]);
 await capture('AST-NAV',{serverWrite:false,entryVerified:'Dashboard > Apparaten > alles weergeven'});
 const search=page.locator('.fixed-table-toolbar .search input').first();
 await search.fill(serial);await search.press('Enter');
 await page.locator('tbody tr.no-records-found').waitFor({state:'visible'});
 await capture('AST-SEARCH-EMPTY',{serverWrite:false,exampleSerial:serial});
 await Promise.all([page.waitForURL(u=>u.pathname==='/hardware/create'),page.locator('button[name="btnAdd"]').click()]);
 await capture('AST-CREATE-BLANK',{serverWrite:false,tagProposalMayReserve:true,entryVerified:'Visible plus button above the asset list'});
 await fillAsset();await capture('AST-CREATE-DESKTOP',{serverWrite:false,registrationNoteEmpty:true});
 await actor('demo_supervisor','Supervisor',true);await open('/hardware/create');await fillAsset();await capture('AST-CREATE-MOBILE',{serverWrite:false,registrationNoteEmpty:true});
 }else{
 assert.ok(!fs.existsSync(path.join(round,'registration-intent.json')),'A registration was already attempted. Inspect the saved state before any further action.');
 await actor('demo_supervisor','Supervisor',true);await open('/hardware/create');await fillAsset();
 const tag=await page.locator('#asset_tag').inputValue();
 await capture('AST-CREATE-MOBILE',{serverWrite:false,registrationNoteEmpty:true});
 // The running follow-up checkout still has the old per-status create validator.
 // The root project's current StoreAssetRequest makes this note nullable.
 await page.locator('#status_change_note').fill('Owned synthetic capture fixture; legacy mounted create validator. No physical device.');
 await page.locator('#notes').fill('Manuals navigation 2026-10-27: owned synthetic fixture; no physical device or print.');
 fs.writeFileSync(path.join(round,'registration-intent.json'),JSON.stringify({serial,tag,actor:'Supervisor',submitCount:1,noteAddedOnlyForOlderMountedValidator:true,rootRegistrationNoteOptional:true,physicalDevice:false},null,2));
 await page.locator('#submit_button').click();await page.waitForTimeout(1200);
 await capture('AST-SAVED',{serverWrite:true,submittedOnce:true,legacyValidatorNoteUsed:true});
 const id=Number(page.url().match(/hardware\/(\d+)\/created/)?.[1]);assert.ok(id,'Inspect result before any repeat submission.');
 fs.writeFileSync(path.join(round,'registration.json'),JSON.stringify({id,tag,serial,actualSaved:true,legacyValidatorNoteUsed:true,rootRegistrationNoteOptional:true,physicalDevice:false,physicalPrinting:false},null,2));
 await open('/hardware/'+id);await capture('AST-ASSET',{serverWrite:false,actualSaved:true,assetId:id});
 }
}finally{await browser.close();}
