import assert from 'node:assert/strict';
import crypto from 'node:crypto';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { spawnSync } from 'node:child_process';

const scriptDirectory = path.dirname(fileURLToPath(import.meta.url));
const kitArgument = process.argv.indexOf('--kit-root');
const kitRoot = path.resolve(
    kitArgument >= 0 ? process.argv[kitArgument + 1] : path.join(scriptDirectory, '..', '..'),
);
const sha256 = (file) => crypto.createHash('sha256').update(fs.readFileSync(file)).digest('hex');
const readJson = (file) => JSON.parse(fs.readFileSync(file, 'utf8'));

const manifestPath = path.join(kitRoot, 'manifest.json');
assert.ok(fs.existsSync(manifestPath), `Missing kit manifest: ${manifestPath}`);
const manifest = readJson(manifestPath);
assert.equal(manifest.schemaVersion, 1, 'Unsupported kit manifest schema');
assert.equal(manifest.guideCount, 25, 'The current guide set must contain 25 guide codes');
assert.equal(manifest.guides.length, manifest.guideCount, 'Guide count does not match manifest');
assert.equal(new Set(manifest.guides.map((guide) => guide.code)).size, manifest.guideCount, 'Guide codes are not unique');

let checkedPages = 0;
for (const guide of manifest.guides) {
    for (const field of ['packagePdfPath', 'sourceHtmlPath', 'specPath']) {
        const file = path.join(kitRoot, guide[field]);
        assert.ok(fs.existsSync(file), `${guide.code} is missing ${field}: ${file}`);
    }
    for (const script of guide.generator.scripts) {
        assert.ok(fs.existsSync(path.join(kitRoot, script)), `${guide.code} is missing generator script ${script}`);
    }
    const pdf = path.join(kitRoot, guide.packagePdfPath);
    const html = path.join(kitRoot, guide.sourceHtmlPath);
    assert.equal(fs.statSync(pdf).size, guide.bytes, `${guide.code} PDF byte count changed`);
    assert.equal(sha256(pdf), guide.sha256, `${guide.code} PDF checksum changed`);
    assert.equal(sha256(html), guide.sourceHtmlSha256, `${guide.code} HTML checksum changed`);
    checkedPages += guide.pages;
}
assert.equal(checkedPages, manifest.pageCount, 'Manifest page total is inconsistent');

const checksumPath = path.join(kitRoot, 'CHECKSUMS.sha256');
assert.ok(fs.existsSync(checksumPath), 'Missing CHECKSUMS.sha256');
for (const line of fs.readFileSync(checksumPath, 'utf8').split(/\r?\n/).filter(Boolean)) {
    const match = line.match(/^([0-9a-f]{64})  (.+)$/);
    assert.ok(match, `Invalid checksum line: ${line}`);
    const file = path.join(kitRoot, match[2]);
    assert.ok(fs.existsSync(file), `Checksum target is missing: ${match[2]}`);
    assert.equal(sha256(file), match[1], `Checksum mismatch: ${match[2]}`);
}

const pdfinfo = process.env.GUIDE_PDFINFO_PATH || 'pdfinfo';
for (const guide of manifest.guides) {
    const result = spawnSync(pdfinfo, [path.join(kitRoot, guide.packagePdfPath)], {
        encoding: 'utf8',
        windowsHide: true,
    });
    assert.equal(result.status, 0, result.stderr || `pdfinfo failed for ${guide.code}`);
    const pageMatch = result.stdout.match(/^Pages:\s+(\d+)$/m);
    assert.equal(Number(pageMatch?.[1]), guide.pages, `${guide.code} PDF page count changed`);
}

console.log(JSON.stringify({
    kitRoot,
    guides: manifest.guideCount,
    pages: manifest.pageCount,
    checksumEntries: fs.readFileSync(checksumPath, 'utf8').split(/\r?\n/).filter(Boolean).length,
    status: 'ok',
}, null, 2));
