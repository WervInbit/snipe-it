import assert from 'node:assert/strict';
import crypto from 'node:crypto';
import fs from 'node:fs';
import path from 'node:path';
import { spawnSync } from 'node:child_process';
import { repoRoot, repoPdfOutputRoot, resolveCommand } from './lib/guide-paths.mjs';

const relativeRound='resources/manuals/operator-guides/review-rounds/2026-10-01-aligned-guides';
const round=path.join(repoRoot,relativeRound);
const proof=process.argv.includes('--proof');
const artifacts=proof?path.join(repoRoot,'output/manuals/proofs/aligned-guides'):round;
const read=file=>JSON.parse(fs.readFileSync(file,'utf8'));
const sha=file=>crypto.createHash('sha256').update(fs.readFileSync(file)).digest('hex');
const pdftotext=resolveCommand('GUIDE_PDFTOTEXT_PATH','pdftotext',{required:false});
const python=pdftotext?null:resolveCommand('GUIDE_PYTHON_PATH',['python','python3']);
const manifest=read(path.join(artifacts,'manifest.json'));
const inputs=read(path.join(round,'inputs.json'));
assert.equal(manifest.acceptedSelectionChanged,false);
assert.equal(manifest.guides.length,19);
assert.equal(new Set(manifest.guides.map(g=>g.code)).size,19);
assert.equal(manifest.guides.reduce((n,g)=>n+g.pages,0),66);
assert.equal(manifest.evidence.length,21);
for(const item of [manifest.generator,manifest.content,...manifest.shared])assert.equal(sha(path.join(repoRoot,item.file)),item.sha256,item.file);
for(const item of manifest.evidence)assert.equal(sha(path.join(round,item.file)),item.sha256,item.file);
for(const guide of manifest.guides){
    const input=inputs.guides.find(g=>g.code===guide.code);
    assert.ok(input,guide.code);
    assert.equal(guide.version,input.nextVersion);
    assert.equal(guide.status,'Working draft - not accepted');
    assert.equal(guide.pages,input.pages+(guide.newPage?1:0));
    assert.deepEqual(guide.qa.violations,[]);
    assert.deepEqual(guide.lineCapacityIssues,[]);
    assert.equal(sha(path.join(round,guide.input.file)),guide.input.sha256);
    assert.equal(sha(path.join(repoRoot,guide.previous.file)),guide.previous.sha256,'Previous PDF changed');
    const pdf=path.join(artifacts,guide.file);
    assert.equal(sha(pdf),guide.sha256);
    assert.equal(fs.statSync(pdf).size,guide.bytes);
    assert.equal(sha(pdf.replace(/\.pdf$/,'.html')),guide.htmlSha256);
    if(!proof)assert.equal(sha(path.join(repoPdfOutputRoot,guide.file)),guide.sha256);
    const info=spawnSync(resolveCommand('GUIDE_PDFINFO_PATH','pdfinfo'),[pdf],{encoding:'utf8',windowsHide:true});
    assert.equal(info.status,0,info.stderr);
    assert.equal(Number(info.stdout.match(/^Pages:\s+(\d+)/m)?.[1]),guide.pages);
    const size=info.stdout.match(/^Page size:\s+([\d.]+) x ([\d.]+) pts \(A4\)/m);
    assert.ok(size&&Math.abs(Number(size[1])-595.276)<1&&Math.abs(Number(size[2])-841.89)<1,'A4 size');
    const text=spawnSync(pdftotext??python,pdftotext?[pdf,'-']:['-X','utf8','-c',"from pypdf import PdfReader; import sys; print('\\f'.join(p.extract_text() or '' for p in PdfReader(sys.argv[1]).pages))",pdf],{encoding:'utf8',windowsHide:true});
    assert.equal(text.status,0,text.stderr);
    assert.match(text.stdout,/Niet geaccepteerd/i);
    const pageTexts=text.stdout.split('\f').filter(t=>t.trim());
    assert.equal(pageTexts.length,guide.pages);
    for(const [i,pageText] of pageTexts.entries()){
        assert.match(pageText,/Niet geaccepteerd/i,`${guide.code} page ${i+1}: draft status`);
        assert.match(pageText,new RegExp(`Concept\\s+v${guide.version}\\b`),`${guide.code} page ${i+1}: version`);
    }
    assert.doesNotMatch(text.stdout,/Jandv2026/);
    if(guide.newPage)for(const step of guide.newPage.steps)assert.ok(text.stdout.replace(/\s+/g,' ').includes(step.title),`${guide.code}: missing ${step.title}`);
}
if(!proof){
    for(const field of ['sourceAudit','provenance','visualReview']){
        const item=manifest[field];assert.ok(item,field);
        assert.equal(sha(path.join(round,item.file)),item.sha256,field);
    }
    const review=read(path.join(round,manifest.visualReview.file));
    assert.equal(review.guides.length,19);
    assert.equal(review.pages,66);
    assert.equal(review.result,'Passed');
    for(const guide of manifest.guides){
        const r=review.guides.find(item=>item.code===guide.code);
        assert.equal(r.sha256,guide.sha256);
        assert.equal(r.pages,guide.pages);
        assert.equal(r.result,'Passed');
    }
}
const kit=path.join(repoRoot,'output/manuals/aligned-guide-review-kit-2026-10-01');
if(!proof&&fs.existsSync(path.join(kit,'manifest.json'))){
    const selection=read(path.join(kit,'manifest.json'));
    assert.equal(selection.guides.length,25);assert.equal(selection.pages,75);
    assert.equal(selection.acceptedSelectionChanged,false);
    for(const guide of selection.guides){
        assert.equal(sha(path.join(kit,guide.pdf)),guide.sha256,guide.code);
        assert.equal(sha(path.join(kit,guide.html)),guide.htmlSha256,guide.code);
        if(guide.previous)assert.equal(sha(path.join(kit,guide.previous.pdf)),guide.previous.sha256);
    }
}
console.log(JSON.stringify({status:'passed',mode:proof?'proof':'retained',guides:19,pages:66,evidenceSources:21,previousPdfsPreserved:19}));
