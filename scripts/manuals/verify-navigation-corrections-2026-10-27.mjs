import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import crypto from 'node:crypto';
import {pathToFileURL} from 'node:url';
import {repoRoot,loadGuideDependency,browserLaunchOptions} from './lib/guide-paths.mjs';

const round=path.join(repoRoot,'resources/manuals/operator-guides/review-rounds/2026-10-27-navigation-and-space');
const manifest=JSON.parse(fs.readFileSync(path.join(round,'authoring-manifest.json'),'utf8'));
const hash=p=>crypto.createHash('sha256').update(fs.readFileSync(p)).digest('hex');
const {chromium}=loadGuideDependency('playwright'),browser=await chromium.launch(browserLaunchOptions({headless:true}));const report=[];
try{
 for(const g of manifest.guides){
  assert.equal(hash(path.join(repoRoot,g.pdf)),g.sha256);assert.equal(hash(path.join(repoRoot,'output/pdf',path.basename(g.pdf))),g.sha256);
  const page=await browser.newPage();await page.goto(pathToFileURL(path.join(repoRoot,g.html)).href);await page.evaluate(()=>document.fonts.ready);
  const pages=await page.evaluate(()=>[...document.querySelectorAll('.page>svg')].map((svg,index)=>{
   const box=n=>{const b=n.getBBox(),m=svg.getCTM().inverse().multiply(n.getCTM()),pts=[[b.x,b.y],[b.x+b.width,b.y],[b.x,b.y+b.height],[b.x+b.width,b.y+b.height]].map(([x,y])=>new DOMPoint(x,y).matrixTransform(m)),x=Math.min(...pts.map(p=>p.x)),y=Math.min(...pts.map(p=>p.y));return{x,y,w:Math.max(...pts.map(p=>p.x))-x,h:Math.max(...pts.map(p=>p.y))-y};};
   const images=[...svg.querySelectorAll('image')].map(n=>({key:n.dataset.recognitionSource,...box(n)}));
   const lines=[...svg.querySelectorAll('text')].filter(n=>!n.closest('[data-component="image-badge"],[data-component="step-badge"],[data-component="family-badge"]')).flatMap(n=>[...(n.querySelectorAll('tspan').length?n.querySelectorAll('tspan'):[n])].map(t=>({text:t.textContent,...box(t)})));
   const overlaps=[];for(const t of lines)for(const im of images){const w=Math.min(t.x+t.w,im.x+im.w)-Math.max(t.x,im.x),h=Math.min(t.y+t.h,im.y+im.h)-Math.max(t.y,im.y);if(w>.3&&h>.3)overlaps.push({text:t.text,image:im.key,w,h});}
   const outsideSteps=[];for(const step of svg.querySelectorAll('[data-component="task-step"]')){const r=box(step.querySelector('rect'));for(const n of step.querySelectorAll('image,text')){if(n.closest('[data-component="image-badge"],[data-component="step-badge"]'))continue;const b=box(n);if(b.x<r.x-.2||b.y<r.y-.2||b.x+b.w>r.x+r.w+.2||b.y+b.h>r.y+r.h+.2)outsideSteps.push({step:step.dataset.number,kind:n.tagName,text:n.tagName==='text'?n.textContent:n.dataset.recognitionSource,box:b,frame:r});}}
   return{page:index+1,images,overlaps,outsideSteps};
  }));
  report.push({code:g.code,pages});await page.close();
 }
}finally{await browser.close();}
const findings=report.flatMap(g=>g.pages.flatMap(p=>[...p.overlaps.map(f=>({code:g.code,page:p.page,type:'body/image',...f})),...p.outsideSteps.map(f=>({code:g.code,page:p.page,type:'step containment',...f}))]));
const destination=fs.existsSync(path.join(round,'manifest.sha256'))?path.join(repoRoot,'output/manuals/proofs/navigation-and-space-2026-10-27/recognition-postseal.json'):path.join(round,'qa/recognition-review.json');fs.writeFileSync(destination,JSON.stringify({guides:report,findings},null,2));console.log(JSON.stringify({guides:report.length,pages:report.reduce((n,g)=>n+g.pages.length,0),findings}));assert.deepEqual(findings,[]);
