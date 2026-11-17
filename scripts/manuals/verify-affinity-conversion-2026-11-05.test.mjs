// Offline fault-injection checks. No Affinity process, UI or native API is called.
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import vm from 'node:vm';
import { fileURLToPath } from 'node:url';
import { createHash } from 'node:crypto';

const repository = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const output = process.argv[2] ? path.resolve(process.argv[2]) : path.join(repository, 'output/manuals/affinity-conversion-2026-11-05');
const script = fs.readFileSync(path.join(output, 'convert-all-25-guides.js'), 'utf8');
const manifest = JSON.parse(fs.readFileSync(path.join(output, 'input-manifest.json'), 'utf8'));
const inventory = JSON.parse(fs.readFileSync(path.join(manifest.root, 'source/inventory.json'), 'utf8'));
const normalize = value => String(value || '').replaceAll('\\', '/').replace(/\/+$/, '').toLowerCase();
const checks = [];

function check(name, run) {
    try {
        run();
        checks.push({ name, passed: true });
    } catch (error) {
        checks.push({ name, passed: false, error: String(error.stack || error) });
    }
}

function harness(settings = {}) {
    const files = new Map();
    const directories = new Set([normalize(manifest.root), normalize(manifest.idmlRoot || manifest.root)]);
    const documents = [];
    const calls = [];
    const alerts = [];
    let serial = 0;
    for (const item of manifest.guides) files.set(normalize((manifest.idmlRoot || manifest.root) + '/' + item.idml), { size: item.bytes, original: true });
    for (const item of manifest.assets) files.set(normalize(manifest.root + '/' + item.file), { size: item.bytes, original: true });
    const cleanFilesAtStart = new Map([...files].map(([key, value]) => [key, { ...value }]));
    const e = value => ({ value, toString() { return 'Enum(' + value + ')'; } });
    const overflowStates = { None: 1, Safe: 2, Warn: 3, Unknown: 0 };
    const sdkFs = {
        exists(name) { return files.has(normalize(name)) || directories.has(normalize(name)); },
        isDirectory(name) { return directories.has(normalize(name)); },
        isRegularFile(name) { return files.has(normalize(name)); },
        getFileSize(name, big) {
            assert.equal(big, false);
            const record = files.get(normalize(name));
            if (!record) throw Error('Missing file');
            return record.size;
        },
        createDirectory(name) {
            const key = normalize(name);
            assert.ok(!sdkFs.exists(name), 'Must not reuse an existing output directory');
            directories.add(key);
            calls.push(['mkdir', name]);
        }
    };
    const File = {
        create(name, mode) {
            assert.equal(mode, 'wb');
            const key = normalize(name);
            assert.ok(!files.has(key), 'Existing file would be overwritten');
            files.set(key, { size: 0, content: '', original: false });
            calls.push(['write-report', name]);
            return {
                isOpen: true,
                write(buffer, length) {
                    assert.ok(this.isOpen);
                    if (settings.shortWrite) return length - 1;
                    const item = files.get(key);
                    item.content += buffer.text;
                    item.size += length;
                    return length;
                },
                flush() {},
                close() { this.isOpen = false; }
            };
        }
    };
    function mockDocument(guide, sourcePath, reopened = false) {
        const identifier = ++serial;
        let texts = guide.bodyTextFrames;
        let images = guide.pictures;
        if (settings.noText) texts = 0;
        if (settings.reopenChanged && reopened) texts--;
        if (settings.noImages) images = 0;
        const layers = [];
        for (let index = 0; index < texts; index++) {
            layers.push({
                [Symbol.toStringTag]: 'FrameTextNode',
                isTextNode: true, nodeId: index,
                textFrameInterface: { textFlow: { getOverflowInfo() {
                    return { state: e(overflowStates[settings.overflow || 'None']), position: -1 };
                } } }
            });
        }
        for (let index = 0; index < images; index++) {
            layers.push({
                [Symbol.toStringTag]: 'ImageNode', isImageNode: true,
                imageResourceInterface: { imageFilePath: 'assets/original.png', imagePlacement: e(0),
                    originalSize: { width: 1200, height: 800 } }
            });
        }
        layers.push({ [Symbol.toStringTag]: 'GroupNode', isGroupNode: true });
        const document = {
            identifier, path: sourcePath, isOpen: true, isDirty: false, dpi: 72,
            rootNode: {
                pageCount: settings.wrongPageCount ? guide.pages + 1 : guide.pages, spreadCount: guide.pages,
                children: Array.from({ length: guide.pages }, () => ({
                    isSpreadNode: true, pageCount: 1,
                    getSpreadExtents(options) {
                        assert.equal(JSON.stringify(options), JSON.stringify({ includeSpread: true, includeBleed: false, includeChildren: false }));
                        return { width: (settings.wrongA4 ? 200 : 210) / 25.4 * 72, height: 297 / 25.4 * 72 };
                    }
                }))
            },
            layers: { all: layers },
            isSameObject(other) { return identifier === other.identifier; },
            enumerateFontNames(callback) {
                assert.equal(callback('Arial', !settings.missingFont), 0);
            },
            saveAs(name) {
                assert.ok(!sdkFs.exists(name));
                calls.push(['save', name, identifier]);
                files.set(normalize(name), { size: 8000, original: false, nativeGuide: guide, content: 'owned-native' });
                if (settings.saveFails) {
                    document.isDirty = true;
                    throw Error('Injected native save failure');
                }
                document.path = name;
            },
            close() {
                calls.push(['close', document.path, identifier]);
                if (!settings.closeFails) document.isOpen = false;
            },
            export(name, preset, area, size) {
                calls.push(['export', name, identifier, document.path]);
                assert.equal(size, null);
                assert.equal(area.pages, '1-' + guide.pages);
                assert.equal(preset.name, 'PDF (for print)');
                assert.ok(reopened, 'PDF must be exported from reopened native master');
                assert.ok(document.path.toLowerCase().endsWith('.af'));
                assert.ok(!sdkFs.exists(name));
                files.set(normalize(name), { size: 3000, original: false, content: 'mock-pdf' });
                return {
                    count: settings.emptyExport ? 0 : 1,
                    enumerate(callback) {
                        const result = callback({
                            path: settings.wrongExportPath ? name + '.other' : name,
                            isSuccess: !settings.exportFails, hasWarnings: Boolean(settings.exportWarns),
                            warningMessage: settings.exportWarns ? 'Injected export warning' : '',
                            errorMessage: settings.exportFails ? 'Injected PDF failure' : '',
                            result: e(settings.exportFails ? 2 : 0)
                        });
                        assert.equal(result, 0);
                    }
                };
            },
            saveAsPackage(name, policy) {
                assert.equal(policy, 1, 'Only image resources should be requested');
                calls.push(['package', name, identifier]);
                if (settings.packageFails) throw Error('Injected package failure');
                assert.ok(!sdkFs.exists(name));
                files.set(normalize(name), { size: 6000, original: false, content: 'mock-package' });
            }
        };
        documents.push(document);
        return document;
    }
    const Document = {
        get all() { return documents.filter(document => document.isOpen); },
        load(name) {
            calls.push(['load', name]);
            if (settings.loadFails) throw Error('Injected IDML load failure');
            if (settings.returnPreviouslyOpen) return settings.returnPreviouslyOpen;
            const item = files.get(normalize(name));
            const guide = manifest.guides.find(entry => normalize((manifest.idmlRoot || manifest.root) + '/' + entry.idml) === normalize(name)) || item?.nativeGuide;
            assert.ok(guide, 'Only declared IDML inputs or newly saved native files may be loaded');
            return mockDocument(guide, name, /\.af$/i.test(name));
        }
    };
    const defaultInitial = { identifier: 999999, path: 'C:/personal/work.af', isOpen: true, isDirty: true };
    documents.push(defaultInitial);
    if (settings.openInput) documents.push({ ...defaultInitial, identifier: 999998, path: (manifest.idmlRoot || manifest.root) + '/' + manifest.guides[0].idml });
    if (settings.returnPreviouslyOpen) {
        const original = settings.returnPreviouslyOpen;
        documents.push(original);
    }
    const group = {
        addStaticText() {},
        addComboBox(label, items, selectedIndex) {
            return { selectedIndex: label === 'Guides' ? (settings.mode ?? (settings.useUiDefault ? selectedIndex : 0)) : selectedIndex };
        },
        addCheckBox(label, initialValue) {
            return { value: label.startsWith('Also') ? settings.packages ?? initialValue : settings.stop ?? initialValue };
        }
    };
    const Dialog = { create() { return {
        addColumn() { return { addGroup() { return group; } }; },
        runModal() { return e(settings.cancel ? 0 : 1); }
    }; } };
    const Application = {
        majorVersion: 3, minorVersion: 3, shortVersion: '3.3.3', buildVersion: '5004',
        productPrimaryFileExtension: '.af', productFullName: 'Affinity',
        alert(message, title) { alerts.push({ message, title }); }
    };
    const modules = {
        '/application.js': { Application },
        '/dialog.js': { Dialog, DialogResult: { Ok: 1 } },
        '/document.js': { Document, FileExportOptions: {
            get allPresetNames() { return settings.noPdfPreset ? ['PNG'] : ['PNG', 'PDF (for print)']; },
            createWithPresetName(name) { return { name }; }
        }, FileExportArea: { createForPages(pages) { return { pages }; } }, PackageResourcesPolicy: { IncludeImages: 1 } },
        '/fs.js': { File, fs: sdkFs },
        '/buffer.js': { Buffer: { utf8(text) { return { text, length: Buffer.byteLength(text) }; } } },
        'affinity:common': { EnumerationResult: { Continue: 0 } },
        '/textflow.js': { TextFlowOverflowState: overflowStates }
    };
    const context = vm.createContext({
        module: { exports: {} },
        require(name) { assert.ok(name in modules, 'Undeclared dependency: ' + name); return modules[name]; },
        console: { log() {}, error() {} }
    });
    vm.runInContext(script, context, { filename: 'convert-all-25-guides.js' });
    function run() { return context.module.exports.main(); }
    return { run, files, directories, calls, alerts, documents, initial: defaultInitial,
        originals: cleanFilesAtStart, fs: sdkFs, mockDocument };
}

check('exact 25-guide version/page inventory and SHA-256 inputs', () => {
    assert.equal(manifest.guides.length, 25);
    assert.equal(manifest.guides.reduce((sum, guide) => sum + guide.pages, 0), 68);
    assert.equal(manifest.assets.length, 82);
    assert.deepEqual(manifest.guides.map(({ code, version, pages }) => ({ code, version, pages })),
        inventory.guides.map(({ code, version, pages }) => ({ code, version, pages })));
    for (const input of [...manifest.guides.map(guide => ({ file: guide.idml, ...guide })), ...manifest.assets]) {
        const bytes = fs.readFileSync(path.join(input.idml ? (manifest.idmlRoot || manifest.root) : manifest.root, input.file));
        assert.equal(bytes.length, input.bytes);
        assert.equal(createHash('sha256').update(bytes).digest('hex'), input.sha256);
    }
});

check('all 25 save/reopen/export/package sequentially; user document and original files untouched', () => {
    const h = harness();
    const report = h.run();
    assert.equal(report.summary.nativeFiles, 25);
    assert.equal(report.summary.pdfFiles, 25);
    assert.equal(report.summary.packages, 25);
    assert.equal(report.summary.failed, 0);
    assert.equal(report.summary.notAttempted, 0);
    assert.equal(report.requested[0], 'AST-03');
    assert.equal(report.requested[1], 'CAT-00');
    assert.equal(report.requested[2], 'CAT-05');
    assert.ok(report.guides.every(guide => guide.status === 'converted-awaiting-review'));
    assert.equal(h.documents.filter(document => document.isOpen).length, 1);
    assert.equal(h.initial.isOpen, true);
    assert.equal(h.initial.isDirty, true);
    assert.ok(h.calls.filter(call => call[0] === 'close').every(call => call[2] !== h.initial.identifier));
    for (const [name, original] of h.originals) assert.deepEqual(h.files.get(name), original);
    const writtenReport = JSON.parse(h.files.get(normalize(report.directory + '/report.json')).content);
    assert.equal(writtenReport.summary.pdfFiles, 25);
    assert.match(h.files.get(normalize(report.directory + '/report.html')).content, /PDF/);
});

check('pilot selectors execute one guide or the three declared representative guides', () => {
    for (const [mode, expected] of [[1, ['AST-03']], [2, ['AST-03', 'CAT-00', 'CAT-05']]]) {
        const report = harness({ mode }).run();
        assert.deepEqual(Array.from(report.requested), expected);
        assert.equal(report.summary.failed, 0);
    }
});

check('current portable script defaults to the three representative guides', () => {
    if (!manifest.inputSet.startsWith('Portable corrected proposal;')) return;
    const report = harness({ useUiDefault: true }).run();
    assert.deepEqual(Array.from(report.requested), ['AST-03', 'CAT-00', 'CAT-05']);
});

check('missing last image prevents all imports and output writes', () => {
    const h = harness();
    h.files.delete(normalize(manifest.root + '/' + manifest.assets.at(-1).file));
    assert.equal(h.run().status, 'preflight-failed');
    assert.equal(h.calls.length, 0);
});

check('changed IDML byte count prevents all imports', () => {
    const h = harness();
    h.files.get(normalize((manifest.idmlRoot || manifest.root) + '/' + manifest.guides.at(-1).idml)).size++;
    assert.equal(h.run().status, 'preflight-failed');
    assert.equal(h.calls.length, 0);
});

check('cancelled start and absent PDF preset have no mutation', () => {
    for (const options of [{ cancel: true }, { noPdfPreset: true }]) {
        const h = harness(options);
        h.run();
        assert.equal(h.calls.length, 0);
    }
});

check('already-open input is preserved before creating any run folder', () => {
    const h = harness({ openInput: true });
    assert.equal(h.run().status, 'preflight-failed');
    assert.equal(h.calls.length, 0);
});

check('unexpected already-open document returned by loader is never saved or closed', () => {
    const original = { identifier: 77, path: 'C:/personal/unsaved.af', isOpen: true, isDirty: true,
        isSameObject(other) { return other.identifier === this.identifier; } };
    const h = harness({ returnPreviouslyOpen: original });
    const report = h.run();
    assert.equal(report.summary.failed, 1);
    assert.equal(report.summary.nativeFiles, 0);
    assert.equal(original.isOpen, true);
    assert.ok(!h.calls.some(call => ['save', 'close', 'export'].includes(call[0])));
});

for (const [name, injected] of [
    ['wrong page count', { wrongPageCount: true }],
    ['missing font', { missingFont: true }],
    ['missing live guide text', { noText: true }],
    ['missing screenshots', { noImages: true }],
    ['non-A4 page', { wrongA4: true }]
]) {
    check(name + ' retains a diagnostic native file and prevents PDF export', () => {
        const h = harness(injected);
        const report = h.run();
        assert.equal(report.summary.failed, 1);
        assert.equal(report.summary.nativeFiles, 1);
        assert.equal(report.summary.pdfFiles, 0);
        assert.equal(report.summary.notAttempted, 24);
        assert.ok(!h.calls.some(call => call[0] === 'export'));
    });
}

check('changed object count on reopen prevents export', () => {
    const h = harness({ reopenChanged: true });
    const report = h.run();
    assert.equal(report.summary.failed, 1);
    assert.equal(report.summary.pdfFiles, 0);
    assert.ok(!h.calls.some(call => call[0] === 'export'));
});

for (const [name, settings] of [
    ['failed export record', { exportFails: true }],
    ['empty export result', { emptyExport: true }],
    ['unexpected export path', { wrongExportPath: true }]
]) {
    check(name + ' is not reported as a successful PDF', () => {
        const report = harness(settings).run();
        assert.equal(report.summary.failed, 1);
        assert.equal(report.summary.pdfFiles, 0);
        assert.equal(report.status, 'incomplete');
    });
}

check('PDF warnings and package failure remain explicit with native/PDF outputs retained', () => {
    const report = harness({ mode: 1, exportWarns: true, packageFails: true }).run();
    assert.equal(report.summary.pdfFiles, 1);
    assert.equal(report.summary.packages, 0);
    assert.equal(report.guides[0].status, 'converted-with-warnings');
    assert.ok(report.guides[0].warnings.some(value => value.includes('Resource packaging failed')));
    assert.ok(report.guides[0].warnings.some(value => value.includes('Injected export warning')));
});

check('native close failure stops the batch even when continue-after-failure is selected', () => {
    const report = harness({ closeFails: true, stop: false }).run();
    assert.equal(report.summary.attempted, 1);
    assert.equal(report.guides[0].leftOpen, true);
    assert.equal(report.summary.pdfFiles, 0);
});

check('failed native save leaves the unsaved owned document for inspection and stops', () => {
    const h = harness({ saveFails: true, stop: false });
    const report = h.run();
    assert.equal(report.summary.attempted, 1);
    assert.equal(report.guides[0].leftOpen, true);
    assert.ok(!h.calls.some(call => call[0] === 'close'));
    assert.equal(h.initial.isOpen, true);
});

check('explicit continue processes other guides only after failed documents have closed', () => {
    const report = harness({ wrongPageCount: true, stop: false }).run();
    assert.equal(report.summary.attempted, 25);
    assert.equal(report.summary.failed, 25);
    assert.equal(report.summary.pdfFiles, 0);
});

check('text overflow stays visible as a review warning; SDK-safe overflow is recorded separately', () => {
    const warned = harness({ mode: 1, overflow: 'Warn' }).run();
    assert.equal(warned.guides[0].status, 'converted-with-warnings');
    assert.ok(warned.guides[0].warnings.some(message => message.includes('Text overflow warning')));
    const safe = harness({ mode: 1, overflow: 'Safe' }).run();
    assert.equal(safe.guides[0].status, 'converted-awaiting-review');
    assert.ok(safe.guides[0].import.overflow.length > 0);
});

check('second run preserves previous output and a manual native edit', () => {
    const h = harness({ mode: 1 });
    const first = h.run();
    const key = normalize(first.guides[0].native);
    h.files.get(key).content = 'human edit';
    const second = h.run();
    assert.notEqual(first.directory, second.directory);
    assert.equal(h.files.get(key).content, 'human edit');
    assert.equal(second.summary.pdfFiles, 1);
});

check('short filesystem writes are detected and do not claim a complete run', () => {
    const report = harness({ shortWrite: true }).run();
    assert.equal(report.status, 'incomplete');
    assert.equal(report.summary.pdfFiles, 0);
    assert.ok(report.errors.some(message => message.includes('incomplete')));
});

const failed = checks.filter(check => !check.passed);
const result = {
    status: failed.length ? 'failed' : 'passed', checks,
    checksRun: checks.length, failures: failed.length,
    affinityExecuted: false, desktopControlUsed: false,
    scope: 'Offline mocked SDK calls and original input identities; native importer/rendering untested.'
};
fs.mkdirSync(path.join(output, 'qa'), { recursive: true });
fs.writeFileSync(path.join(output, 'qa/offline-tests.json'), JSON.stringify(result, null, 2) + '\n');
console.log(JSON.stringify(result, null, 2));
if (failed.length) process.exitCode = 1;
