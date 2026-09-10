import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import crypto from 'node:crypto';
import {fileURLToPath} from 'node:url';

export const kitRoot=path.resolve(path.dirname(fileURLToPath(import.meta.url)),'..');
export const sha=file=>crypto.createHash('sha256').update(fs.readFileSync(file)).digest('hex');
export function verifyKit() {
  const file=path.join(kitRoot,'manifest.json');
  const expected=fs.readFileSync(path.join(kitRoot,'manifest.sha256'),'utf8').trim().split(/\s+/)[0];
  assert.equal(sha(file),expected,'Package manifest changed');
  const manifest=JSON.parse(fs.readFileSync(file,'utf8'));
  for(const entry of manifest.files) {
    const target=path.resolve(kitRoot,entry.path);
    assert.ok(target.startsWith(kitRoot+path.sep),'Invalid package path');
    assert.equal(sha(target),entry.sha256,`Changed or incomplete package file: ${entry.path}`);
  }
  return manifest;
}
if(process.argv[1]&&path.resolve(process.argv[1])===fileURLToPath(import.meta.url)) {
  const m=verifyKit();
  console.log(JSON.stringify({status:'passed',acceptedGuides:m.guides.length,historicalPdfs:m.history.length,verifiedFiles:m.files.length}));
}
