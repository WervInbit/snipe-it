// Print the current password-change layout without changing its content.
import fs from 'node:fs';
import path from 'node:path';
import { createHash } from 'node:crypto';
import { createRequire } from 'node:module';

const source = path.resolve('output/manuals/final-guide-set-2026-11-04');
const target = path.resolve('output/pdf/intake-refurbishment-2026-11-10/AC-02-wachtwoord-wijzigen-v8.pdf');
if (fs.existsSync(target)) throw Error('Existing print PDF preserved.');
const relative = 'source/AC-02-v8-p1.svg';
const svg = fs.readFileSync(path.join(source, relative));
const hashes = new Map(fs.readFileSync(path.join(source, 'preparation.sha256'), 'utf8').trim().split(/\r?\n/).map(line => [line.slice(66), line.slice(0,64)]));
const sha256 = createHash('sha256').update(svg).digest('hex');
if (sha256 !== hashes.get(relative)) throw Error('Prepared SVG changed.');
const require = createRequire(import.meta.url);
const {chromium} = require('C:/Program Files/WindowsApps/OpenAI.CodexPrimaryRuntime.v26-1104-709-0_26.1104.709.0_x64__3k8sg7r9htsxt/dependencies/node/node_modules/playwright');
const bundled = chromium.executablePath();
const browser = await chromium.launch({headless:true,executablePath:fs.existsSync(bundled)?bundled:'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe'});
try {
    const page = await browser.newPage();
    await page.route('**/*', route => /^https?:/i.test(route.request().url())?route.abort():route.continue());
    await page.setContent('<!doctype html><html lang="nl"><meta charset="utf-8"><style>@page{size:A4;margin:0}html,body{padding:0;margin:0}svg{display:block;width:210mm;height:297mm}</style><body>'+svg.toString('utf8')+'</body></html>',{waitUntil:'load'});
    await page.emulateMedia({media:'print'});
    await page.evaluate(async()=>{await document.fonts.ready;});
    await page.pdf({path:target,format:'A4',preferCSSPageSize:true,printBackground:true,displayHeaderFooter:false,margin:{top:0,right:0,bottom:0,left:0}});
    await page.close();
} finally {await browser.close();}
fs.writeFileSync(target+'.source.json',JSON.stringify({code:'AC-02',version:8,pages:1,source:relative,sourceSha256:sha256,contentChanged:false,nativeAffinityExport:false},null,2)+'\n');
process.stdout.write(JSON.stringify({file:target,bytes:fs.statSync(target).size,pages:1})+'\n');
