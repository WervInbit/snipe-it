import assert from 'node:assert/strict';
import crypto from 'node:crypto';
import fs from 'node:fs';
import path from 'node:path';
import { spawnSync } from 'node:child_process';
import { repoRoot, repoPdfOutputRoot, resolveCommand } from './lib/guide-paths.mjs';

const round = path.join(repoRoot, 'resources/manuals/operator-guides/review-rounds/2026-10-01-workflows');
const manifest = JSON.parse(fs.readFileSync(path.join(round, 'manifest.json'), 'utf8'));
const sha = file => crypto.createHash('sha256').update(fs.readFileSync(file)).digest('hex');
assert.equal(manifest.acceptedSelectionChanged, false);
assert.equal(manifest.status, 'Working drafts - exact-version review pending');
assert.equal(manifest.guides.length, 2);
assert.equal(manifest.evidence.length, 6);
assert.equal(sha(path.join(round, 'source-audit.json')), manifest.sourceAuditSha256);
assert.equal(sha(path.join(repoRoot, 'scripts/manuals/generate-workflow-revisions-2026-10-01.mjs')), manifest.generatorSha256);
for (const source of manifest.evidence) assert.equal(sha(path.join(round, source.file)), source.sha256, source.id);
const expected = { 'WF-01': { version: 14, pages: 1, images: 5 }, 'WF-02': { version: 13, pages: 2, images: 8 } };
for (const guide of manifest.guides) {
    const e = expected[guide.code];
    assert.ok(e, `Unexpected guide ${guide.code}`);
    assert.equal(guide.version, e.version);
    assert.equal(guide.pages, e.pages);
    assert.equal(guide.qa.images, e.images);
    assert.deepEqual(guide.qa.overflow, []);
    assert.equal(guide.qa.mainOverHelp, false);
    assert.equal(guide.qa.clippedShots, 0);
    assert.equal(guide.qa.choiceOverlap, false);
    assert.equal(guide.status, 'Working draft - not accepted');
    assert.equal(sha(path.join(round, 'inputs', guide.input)), guide.inputSha256);
    assert.equal(sha(path.join(round, `${guide.file}.html`)), guide.sourceHtmlSha256);
    const pdf = path.join(round, `${guide.file}.pdf`);
    assert.equal(fs.statSync(pdf).size, guide.bytes);
    assert.equal(sha(pdf), guide.sha256);
    assert.equal(sha(path.join(repoPdfOutputRoot, `${guide.file}.pdf`)), guide.sha256);
    const info = spawnSync(resolveCommand('GUIDE_PDFINFO_PATH', 'pdfinfo'), [pdf], { encoding: 'utf8', windowsHide: true });
    assert.equal(info.status, 0, info.stderr);
    assert.equal(Number(info.stdout.match(/^Pages:\s+(\d+)/m)?.[1]), e.pages);
    const dimensions = info.stdout.match(/^Page size:\s+([\d.]+) x ([\d.]+) pts \(A4\)/m);
    assert.ok(dimensions, 'Expected an A4 page size');
    assert.ok(Math.abs(Number(dimensions[1]) - 595.276) < 1, 'A4 width differs by at least 1 point');
    assert.ok(Math.abs(Number(dimensions[2]) - 841.890) < 1, 'A4 height differs by at least 1 point');
}
const prior = [
    ['resources/manuals/operator-guides/review-rounds/2026-09-10/owner-corrections/WF-01-workflow-starten-v13-draft.pdf', '880429c9b1d599239e72a71fe696b52742b673af8542d5b3cc81a98381c1965b'],
    ['resources/manuals/operator-guides/review-rounds/2026-09-08/WF-02-complete-workflow-v12-draft.pdf', '296632d3e07fe646c4b4a165c828f37a55487952ee25ff57f8b9a5a7af86dd7f'],
];
for (const [file, hash] of prior) assert.equal(sha(path.join(repoRoot, file)), hash, 'Prior comparison PDF changed');
console.log(JSON.stringify({ status: 'passed', newGuides: 2, pages: 3, evidenceSources: 6, previousPdfsPreserved: 2 }));
