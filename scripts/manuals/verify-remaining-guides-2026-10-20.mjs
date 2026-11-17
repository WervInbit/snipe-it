import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import crypto from 'node:crypto';
import {pathToFileURL} from 'node:url';
import {repoRoot,loadGuideDependency,browserLaunchOptions} from './lib/guide-paths.mjs';

const round=path.join(repoRoot,'resources/manuals/operator-guides/review-rounds/2026-10-20-remaining-guides');
const proof=path.join(repoRoot,'output/manuals/proofs/remaining-guides-2026-10-20');
const manifest=JSON.parse(fs.readFileSync(path.join(round,'authoring-manifest.json'),'utf8'));
assert.equal(manifest.guides.length,17);
const dims={};const sharp=loadGuideDependency('sharp');
for(const s of manifest.sources){const m=await sharp(path.join(repoRoot,s.crop??s.source)).metadata();dims[s.key]={w:m.width,h:m.height};}
const {chromium}=loadGuideDependency('playwright');const browser=await chromium.launch(browserLaunchOptions({headless:true}));
const report=[];
try{
 for(const g of manifest.guides){
  const page=await browser.newPage();await page.goto(pathToFileURL(path.join(repoRoot,g.html)).href);await page.evaluate(()=>document.fonts.ready);
  const result=await page.evaluate(dims=>[...document.querySelectorAll('.page>svg')].map((svg,index)=>{
   const box=n=>{const b=n.getBBox(),m=svg.getCTM().inverse().multiply(n.getCTM()),p=[[b.x,b.y],[b.x+b.width,b.y],[b.x,b.y+b.height],[b.x+b.width,b.y+b.height]].map(([x,y])=>new DOMPoint(x,y).matrixTransform(m)),x=Math.min(...p.map(p=>p.x)),y=Math.min(...p.map(p=>p.y));return{x,y,w:Math.max(...p.map(p=>p.x))-x,h:Math.max(...p.map(p=>p.y))-y};};
   const images=[...svg.querySelectorAll('image')].map(n=>{
    const key=n.dataset.recognitionSource;let b=box(n);
    if(key&&dims[key]){const d=dims[key],scale=Math.min(b.w/d.w,b.h/d.h);b={x:b.x+(b.w-d.w*scale)/2,y:b.y+(b.h-d.h*scale)/2,w:d.w*scale,h:d.h*scale};}
    else{const id=n.getAttribute('clip-path')?.match(/#([^)]*)/)?.[1],clip=id?svg.querySelector(`[id="${id}"] rect`):null;if(clip)b=box(clip);}
    return{key:key??'Retained canonical crop',...b};
   });
   const texts=[...svg.querySelectorAll('text')].filter(n=>n.textContent.trim()).flatMap(n=>{
    const spans=[...n.querySelectorAll('tspan')];return(spans.length?spans:[n]).map(t=>({text:t.textContent,...box(t),annotation:!!n.closest('[data-component="image-badge"], [data-component="step-badge"], [data-component="family-badge"]')}));
   });
   const overlaps=[];for(const t of texts.filter(t=>!t.annotation))for(const im of images){const w=Math.min(t.x+t.w,im.x+im.w)-Math.max(t.x,im.x),h=Math.min(t.y+t.h,im.y+im.h)-Math.max(t.y,im.y);if(w>.35&&h>.35)overlaps.push({text:t.text,image:im.key,overlap:{w,h}});}
   return{page:index+1,images,textOutside:texts.filter(t=>t.x<-.1||t.y<0||t.x+t.w>210.1||t.y+t.h>297.1),textImageOverlaps:overlaps,text:texts.map(t=>t.text).join('\n')};
  }),dims);
  const fullText=result.map(p=>p.text).join('\n'),retired=fullText.match(/\uFFFD|In voorbereiding|Werkconcept|QR volgt|gids volgt nog/ig)??[];
  for(const p of result)assert.deepEqual(p.textOutside,[],g.code+': text outside A4');
  assert.equal(result.length,g.pageCount);
  const pdfHash=crypto.createHash('sha256').update(fs.readFileSync(path.join(repoRoot,g.pdf))).digest('hex');assert.equal(pdfHash,g.sha256);
  const delivered=fs.readFileSync(path.join(repoRoot,'output/pdf',path.basename(g.pdf)));assert.equal(crypto.createHash('sha256').update(delivered).digest('hex'),pdfHash);
  report.push({code:g.code,retired,pages:result});await page.close();
 }
}finally{await browser.close();}
fs.writeFileSync(path.join(proof,'recognition-review.json'),JSON.stringify(report,null,2));
console.log(JSON.stringify({guides:report.length,pages:report.reduce((n,g)=>n+g.pages.length,0),retired:report.filter(g=>g.retired.length).map(g=>({code:g.code,text:g.retired})),textImageOverlaps:report.flatMap(g=>g.pages.filter(p=>p.textImageOverlaps.length).map(p=>({code:g.code,page:p.page,overlaps:p.textImageOverlaps})))}));
assert.deepEqual(report.filter(g=>g.retired.length),[],'Retired placeholders remain; see recognition-review.json.');
