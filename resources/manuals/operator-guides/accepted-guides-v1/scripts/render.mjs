import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import {createRequire} from 'node:module';
import {pathToFileURL} from 'node:url';
import {kitRoot,sha,verifyKit} from './verify.mjs';

const require=createRequire(import.meta.url);
const args=process.argv.slice(2);
const option=name=>{const i=args.indexOf(name);return i<0?null:args[i+1];};
const m=verifyKit();
let playwright;
try {playwright=require('playwright');}
catch {assert.ok(process.env.GUIDE_NODE_MODULES_ROOT,'Run npm ci inside scripts, or set GUIDE_NODE_MODULES_ROOT.');playwright=require(path.join(process.env.GUIDE_NODE_MODULES_ROOT,'playwright'));}
const run=option('--run')??`run-${Date.now()}`;
assert.match(run,/^[a-zA-Z0-9][a-zA-Z0-9_-]*$/,'Use a simple new run name');
const work=path.join(kitRoot,'work');fs.mkdirSync(work,{recursive:true});
assert.equal(fs.realpathSync(work),work,'work must be a real directory, not a symlink');
const out=path.join(work,run);
assert.ok(!fs.existsSync(out),'This proof run already exists; use a new --run name.');
const custom=option('--html');
let jobs;
if(custom) {
  const file=fs.realpathSync(path.resolve(kitRoot,custom));
  assert.ok(file.startsWith(work+path.sep),'Draft HTML must be in work/, preserving accepted inputs.');
  assert.equal(path.extname(file).toLowerCase(),'.html');
  const match=path.basename(file).match(/^([a-z]+-\d+)-.*-v(\d+)(?:-draft)?\.html$/i);
  assert.ok(match,'Name the draft CODE-description-vN-draft.html');
  const code=match[1].toUpperCase(),version=Number(match[2]);
  assert.ok(version>(m.highestKnownVersions[code]??0),`Use a version above the highest known ${code} version (${m.highestKnownVersions[code]??0}).`);
  jobs=[{code,version,html:file,pdf:path.basename(file,'.html')+'.pdf',expectedPages:null}];
} else {
  const code=option('--guide')?.toUpperCase();
  assert.ok(args.includes('--all')||code,'Use --all, --guide CODE, or --html work/...html');
  const selected=code?m.guides.filter(g=>g.code===code):m.guides;
  assert.ok(selected.length,'Guide is not in the accepted set');
  jobs=selected.map(g=>({code:g.code,version:g.version,html:path.join(kitRoot,g.html),pdf:path.basename(g.pdf),expectedPages:g.pages}));
}
const chromeCandidates=[process.env.GUIDE_CHROME_PATH,
  path.join(process.env.PROGRAMFILES??'C:/Program Files','Google/Chrome/Application/chrome.exe'),
  path.join(process.env['PROGRAMFILES(X86)']??'C:/Program Files (x86)','Google/Chrome/Application/chrome.exe'),
  '/usr/bin/google-chrome','/usr/bin/chromium','/Applications/Google Chrome.app/Contents/MacOS/Google Chrome'].filter(Boolean);
const executablePath=chromeCandidates.find(p=>fs.existsSync(p));
const browser=await playwright.chromium.launch({headless:true,...(executablePath?{executablePath}:{})});
fs.mkdirSync(path.join(out,'pdf'),{recursive:true});
const report={run,mode:custom?'new draft':'regeneration proof',browser:browser.version(),platform:process.platform,results:[]};
try {
  for(const job of jobs) {
    const page=await browser.newPage();
    const failures=[];
    page.on('pageerror',e=>failures.push(e.message));
    await page.route('**/*',route=>{
      const url=route.request().url();
      if(url.startsWith('data:')||url.startsWith('about:')||url===pathToFileURL(job.html).href)return route.continue();
      failures.push(`External resource requested: ${url}`);return route.abort();
    });
    await page.goto(pathToFileURL(job.html).href);
    await page.emulateMedia({media:'print'});
    await page.evaluate(()=>document.fonts.ready);
    const inventory=await page.evaluate(()=>({pages:document.querySelectorAll('.page,.guide-page').length,
      brokenImages:[...document.images].filter(i=>!i.complete||i.naturalWidth===0).map(i=>i.alt||'unnamed'),
      title:document.title}));
    assert.deepEqual(failures,[],'HTML must be self-contained');
    assert.deepEqual(inventory.brokenImages,[],'Missing image');
    if(job.expectedPages!==null)assert.equal(inventory.pages,job.expectedPages,'HTML page count');
    const pdf=path.join(out,'pdf',job.pdf);
    await page.pdf({path:pdf,preferCSSPageSize:true,printBackground:true,displayHeaderFooter:false});
    report.results.push({...job,html:path.relative(kitRoot,job.html).replaceAll('\\','/'),pdf:path.relative(out,pdf).replaceAll('\\','/'),sha256:sha(pdf),inventory});
    console.log(`${job.code} v${job.version}: rendered`);
    await page.close();
  }
} finally {await browser.close();}
fs.writeFileSync(path.join(out,'render-report.json'),JSON.stringify(report,null,2)+'\n');
console.log(`Proofs: ${out}`);
