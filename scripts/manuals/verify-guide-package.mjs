import assert from 'node:assert/strict';
import crypto from 'node:crypto';
import fs from 'node:fs';
import path from 'node:path';
import { spawnSync } from 'node:child_process';
import {
    acceptedPdfRoot,
    baselineRoot,
    evidenceRoot,
    repoRoot,
    resolveCommand,
    runtimeSummary,
} from './lib/guide-paths.mjs';
import { GUIDE_REGISTRY, GUIDE_STATUSES } from './lib/guide-system.mjs';

function readJson(file) {
    return JSON.parse(fs.readFileSync(file, 'utf8'));
}

function hash(file) {
    return crypto.createHash('sha256').update(fs.readFileSync(file)).digest('hex');
}

function verifyManifestFiles(root, entries, label) {
    const names = new Set();
    for (const entry of entries) {
        assert.ok(entry.file && entry.sha256, `${label} entry is missing file or sha256`);
        assert.ok(!path.isAbsolute(entry.file), `${label} contains an absolute file path: ${entry.file}`);
        assert.ok(!names.has(entry.file), `${label} contains duplicate file: ${entry.file}`);
        names.add(entry.file);

        const file = path.join(root, entry.file);
        assert.ok(fs.existsSync(file), `${label} file is missing: ${file}`);
        assert.equal(hash(file), entry.sha256, `${label} checksum mismatch: ${entry.file}`);
    }

    const unlisted = fs.readdirSync(root, { withFileTypes: true })
        .filter((entry) => entry.isFile() && entry.name !== 'manifest.json')
        .map((entry) => entry.name)
        .filter((file) => !names.has(file));
    assert.deepEqual(unlisted, [], `${label} has unlisted files`);
}

function run(command, args, label) {
    const shell = /\.(cmd|bat)$/i.test(command);
    const result = spawnSync(command, args, {
        encoding: 'utf8',
        shell,
        stdio: ['ignore', 'pipe', 'pipe'],
    });
    assert.equal(result.status, 0, `${label} failed: ${result.stderr || result.stdout}`);
    return result.stdout;
}

function filesBelow(directory) {
    return fs.readdirSync(directory, { withFileTypes: true }).flatMap((entry) => {
        const file = path.join(directory, entry.name);
        if (entry.isDirectory()) return filesBelow(file);
        return [file];
    });
}

function verifyMarkdownLinks(directory) {
    const markdownFiles = filesBelow(directory).filter((file) => file.endsWith('.md'));
    for (const file of markdownFiles) {
        const source = fs.readFileSync(file, 'utf8');
        for (const match of source.matchAll(/\[[^\]]*\]\(([^)]+)\)/g)) {
            const target = match[1].trim().replace(/^<|>$/g, '').split('#')[0];
            if (!target || target.startsWith('#') || /^[a-z][a-z0-9+.-]*:/i.test(target)) continue;
            const resolved = path.resolve(path.dirname(file), decodeURIComponent(target));
            assert.ok(fs.existsSync(resolved), `Broken Markdown link in ${file}: ${match[1]}`);
        }
    }
}

const evidenceManifest = readJson(path.join(evidenceRoot, 'manifest.json'));
assert.equal(evidenceManifest.sources.length, 48, 'Canonical evidence count changed without review');
assert.equal(new Set(evidenceManifest.sources.map((entry) => entry.id)).size, 48, 'Duplicate evidence source ID');
verifyManifestFiles(evidenceRoot, evidenceManifest.sources, 'Evidence manifest');

const screenshotCatalog = fs.readFileSync(
    path.join(repoRoot, 'docs', 'manuals', 'operator-guides', 'screenshots.md'),
    'utf8',
);
verifyMarkdownLinks(path.join(repoRoot, 'docs', 'manuals', 'operator-guides'));
for (const source of evidenceManifest.sources) {
    assert.match(screenshotCatalog, new RegExp(`\\x60${source.id}\\x60`), `Evidence ID is not documented: ${source.id}`);
}

const pdfManifest = readJson(path.join(acceptedPdfRoot, 'manifest.json'));
assert.equal(pdfManifest.artifacts.length, 8, 'Accepted PDF count changed without review');
assert.equal(new Set(pdfManifest.artifacts.map((entry) => entry.code)).size, 8, 'Duplicate accepted guide code');
verifyManifestFiles(acceptedPdfRoot, pdfManifest.artifacts, 'Accepted PDF manifest');

const pdfinfo = resolveCommand('GUIDE_PDFINFO_PATH', 'pdfinfo');
const pdftotext = resolveCommand('GUIDE_PDFTOTEXT_PATH', 'pdftotext', { required: false });
const python = process.env.GUIDE_PYTHON_PATH
    ? resolveCommand('GUIDE_PYTHON_PATH', [], { required: false })
    : null;
for (const artifact of pdfManifest.artifacts) {
    assert.equal(artifact.status, 'Internal review candidate', `${artifact.code} has an unexpected artifact status`);
    assert.equal(GUIDE_REGISTRY[artifact.code]?.status, GUIDE_STATUSES.INTERNAL_REVIEW, `${artifact.code} runtime status drift`);
    assert.ok(!artifact.file.toLowerCase().includes('draft'), `${artifact.code} accepted filename still says draft`);

    const file = path.join(acceptedPdfRoot, artifact.file);
    const info = run(pdfinfo, [file], `${artifact.code} pdfinfo`);
    const pages = Number(info.match(/^Pages:\s+(\d+)/m)?.[1]);
    assert.equal(pages, artifact.pages, `${artifact.code} page count drift`);

    const dimensions = info.match(/^Page size:\s+([\d.]+) x ([\d.]+) pts/m);
    assert.ok(dimensions, `${artifact.code} has no readable page dimensions`);
    assert.ok(Math.abs(Number(dimensions[1]) - 595.28) < 2, `${artifact.code} page width is not A4`);
    assert.ok(Math.abs(Number(dimensions[2]) - 841.89) < 2, `${artifact.code} page height is not A4`);

    let text = null;
    if (pdftotext) {
        text = run(pdftotext, [file, '-'], `${artifact.code} text extraction`);
    } else if (python) {
        text = run(python, [
            '-c',
            'import sys; from pypdf import PdfReader; print("\\n".join((p.extract_text() or "") for p in PdfReader(sys.argv[1]).pages))',
            file,
        ], `${artifact.code} pypdf text extraction`);
    }
    if (text !== null) {
        assert.ok(!text.includes('dev.inbit'), `${artifact.code} exposes the development URL`);
    }
}

const baselineManifest = readJson(path.join(baselineRoot, 'manifest.json'));
assert.equal(baselineManifest.baselines.length, 2, 'Locked baseline count changed without review');
verifyManifestFiles(baselineRoot, baselineManifest.baselines, 'Baseline manifest');

const activeScripts = filesBelow(path.join(repoRoot, 'scripts', 'manuals'))
    .filter((file) => file.endsWith('.mjs'))
    .filter((file) => !file.includes(`${path.sep}archive${path.sep}`))
    .filter((file) => path.basename(file) !== 'verify-guide-package.mjs');
for (const file of activeScripts) {
    const source = fs.readFileSync(file, 'utf8');
    assert.ok(!/C:\\\\Users\\/i.test(source), `Active script contains a user-specific path: ${file}`);
    assert.ok(!/C:\\\\dev\\/i.test(source), `Active script contains a repository-specific path: ${file}`);
    assert.ok(!/codex-runtimes/i.test(source), `Active script depends directly on a Codex runtime: ${file}`);
    for (const match of source.matchAll(/evidencePath\(['"]([^'"]+)['"]\)/g)) {
        assert.ok(
            evidenceManifest.sources.some((entry) => entry.id === match[1]),
            `Active script references unknown evidence ${match[1]}: ${file}`,
        );
    }
}

console.log(JSON.stringify({
    status: 'ok',
    evidence: evidenceManifest.sources.length,
    acceptedPdfs: pdfManifest.artifacts.length,
    acceptedPages: pdfManifest.artifacts.reduce((total, artifact) => total + artifact.pages, 0),
    baselines: baselineManifest.baselines.length,
    activeScripts: activeScripts.length,
    runtime: runtimeSummary(),
}, null, 2));
