import fs from 'node:fs';
import path from 'node:path';
import assert from 'node:assert/strict';
import {repoRoot} from './lib/guide-paths.mjs';
import {guideChanges} from './lib/final-guide-changes-2026-11-04.mjs';

assert.equal(process.argv.length,2,'No arguments.');
const out=path.join(repoRoot,'output/manuals/final-guide-set-2026-11-04');
assert(!fs.existsSync(path.join(out,'manifest.sha256')),'Sealed release.');
const inventory=JSON.parse(fs.readFileSync(path.join(out,'source/inventory.json'),'utf8'));
for(const g of inventory.guides){
    const file=path.join(repoRoot,'docs/manuals/operator-guides/guides',g.code+'.md');
    const brief=guideChanges[g.code];
    let content=fs.readFileSync(file,'utf8');
    content=content.replace(/\n<!-- final-preparation-2026-11-04:start -->[\s\S]*?<!-- final-preparation-2026-11-04:end -->\n/,'\n');
    content=content.replace(/^## Current Specification([^\n]*)/m,'## Baseline Specification$1');
    const first=content.indexOf('\n');
    const block=`\n<!-- final-preparation-2026-11-04:start -->\n## Prepared Revision - 2026-11-04\n\nProposed **v${g.version}**, ${g.pages} A4 page(s). Status: prepared source;\nnative Affinity import/edit/save/export and final PDF release remain incomplete.\nThis follows the owner's all25 execution authorization. No exact-version\nacceptance or runtime selection is inferred.\n\nReader outcome: ${brief.outcome}\n\n${brief.changes.map(c=>'- '+c).join('\n')}\n\nPreserve: ${brief.preserved}\n\nPlacement and tradeoff: ${brief.tradeoff}\n\nUse the [all25 execution record](../reviews/final-set-preparation-2026-11-04.md)\nand [complete changes/comparison index](../../../../output/manuals/final-guide-set-2026-11-04/review/index.html#${g.code}).\nThe retained baseline below supplies the domain contract and historical evidence;\nthis brief owns the changed outline and placements in the prepared proposal.\nThe original capture/role/locale, application-target and native-proof limits\nare recorded centrally.\n<!-- final-preparation-2026-11-04:end -->\n`;
    content=content.slice(0,first+1)+block+content.slice(first+1);
    fs.writeFileSync(file,content);
}
console.log(JSON.stringify({specifications:inventory.guides.length}));
