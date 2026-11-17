/*
 * Owner-run conversion for the prepared operator guides.
 * Run inside Affinity 3.3 or later, through its Script Editor.
 * Build the runnable copy with prepare-affinity-conversion-2026-11-05.py.
 * This script uses Affinity's SDK only; it has no network or desktop control.
 */
'use strict';

const { Application } = require('/application.js');
const { Dialog, DialogResult } = require('/dialog.js');
const { Document, FileExportOptions, FileExportArea, PackageResourcesPolicy } = require('/document.js');
const { File, fs } = require('/fs.js');
const { Buffer } = require('/buffer.js');
const { EnumerationResult } = require('affinity:common');
const { TextFlowOverflowState } = require('/textflow.js');

/* INPUTS_START */
const INPUTS = null;
/* INPUTS_END */

function fail(message) {
    throw new Error(message);
}

function cleanPath(path) {
    return String(path || '').replace(/\\/g, '/').replace(/\/+$/, '');
}

function samePath(a, b) {
    return cleanPath(a).toLowerCase() === cleanPath(b).toLowerCase();
}

function within(path, directory) {
    return cleanPath(path).toLowerCase().startsWith(cleanPath(directory).toLowerCase() + '/');
}

function join(directory, relative) {
    if (!relative || /(^\/|^[A-Za-z]:|(^|\/)\.\.(\/|$))/.test(relative)) {
        fail('Invalid package-relative path: ' + relative);
    }
    return cleanPath(directory) + '/' + relative;
}

function enumValue(value) {
    return value && typeof value === 'object' && 'value' in value ? value.value : value;
}

function enumIs(a, b) {
    return enumValue(a) === enumValue(b);
}

function errorText(error) {
    if (error == null) return 'Unknown error';
    const parts = [error.message, error.desc, error.description].filter(Boolean);
    if (parts.length) return Array.from(new Set(parts.map(String))).join('; ');
    return String(error);
}

function positiveFile(path) {
    if (!fs.isRegularFile(path)) fail('File is missing: ' + path);
    const size = fs.getFileSize(path, false);
    if (!(size > 0)) fail('File is empty: ' + path);
    return Number(size);
}

function noExisting(path, runDirectory) {
    if (!within(path, runDirectory)) fail('Output escaped the new run folder: ' + path);
    if (fs.exists(path)) fail('Existing output was preserved: ' + path);
}

function writeNew(path, text, runDirectory) {
    noExisting(path, runDirectory);
    const file = File.create(path, 'wb');
    try {
        if (!file.isOpen) fail('Could not open report: ' + path);
        writeText(file, text);
    } finally {
        if (file.isOpen) file.close();
    }
    positiveFile(path);
}

function writeText(file, text) {
    const buffer = Buffer.utf8(text);
    if (file.write(buffer, buffer.length) !== buffer.length) fail('An incomplete report/journal write was detected.');
    file.flush();
}

function preflightInputs() {
    if (!INPUTS || INPUTS.guides.length !== 25 || INPUTS.assets.length !== 82) {
        fail('Use the generated convert-all-25-guides.js, not the source template.');
    }
    if (!Number.isFinite(Number(Application.majorVersion)) || !Number.isFinite(Number(Application.minorVersion)) ||
        Number(Application.majorVersion) < 3 ||
        (Number(Application.majorVersion) === 3 && Number(Application.minorVersion) < 3)) {
        fail('This conversion targets Affinity 3.3+. Detected: ' + Application.shortVersion);
    }
    if (String(Application.productPrimaryFileExtension).replace(/^\./, '').toLowerCase() !== 'af') {
        fail('This script requires the unified Affinity application with native .af saving.');
    }
    if (!fs.isDirectory(INPUTS.root)) fail('Prepared guide folder is missing: ' + INPUTS.root);
    if (!fs.isDirectory(INPUTS.idmlRoot || INPUTS.root)) fail('IDML input folder is missing: ' + INPUTS.idmlRoot);
    const codes = new Set();
    let pageCount = 0;
    for (const guide of INPUTS.guides) {
        if (codes.has(guide.code)) fail('Duplicate guide in conversion manifest: ' + guide.code);
        codes.add(guide.code);
        pageCount += guide.pages;
        const path = join(INPUTS.idmlRoot || INPUTS.root, guide.idml);
        if (positiveFile(path) !== guide.bytes) fail('IDML size changed since preparation: ' + path);
    }
    if (pageCount !== 68) fail('Expected the prepared 68-page guide set.');
    for (const asset of INPUTS.assets) {
        const path = join(INPUTS.root, asset.file);
        if (positiveFile(path) !== asset.bytes) fail('Image size changed since preparation: ' + path);
    }
    return codes;
}

function pdfPresetNames() {
    const names = FileExportOptions.allPresetNames.filter(name => /pdf/i.test(String(name)));
    if (!names.length) fail('Affinity reported no PDF export presets.');
    return names;
}

function chooseOptions(presets) {
    const dialog = Dialog.create('Operator guides: native Affinity conversion');
    const group = dialog.addColumn().addGroup();
    group.addStaticText('Input', INPUTS.idmlRoot || INPUTS.root);
    const selection = group.addComboBox('Guides', [
        'All 25 guides (AST-03 first)',
        'AST-03 only',
        'AST-03, CAT-00 and CAT-05 technical proof'
    ], 2);
    const preferred = presets.findIndex(name => /for print|print.?ready|druck|impress|afdruk/i.test(name));
    const preset = group.addComboBox('PDF preset', presets, preferred >= 0 ? preferred : 0);
    const packages = group.addCheckBox('Also package image resources (no font files)', true);
    const stop = group.addCheckBox('Stop the batch after the first failed guide', true);
    group.addStaticText('Output', 'A new conversion-runs folder for each run. Existing files stay intact.');
    group.addStaticText('Review', 'Conversion does not establish layout quality or guide acceptance.');
    if (!enumIs(dialog.runModal(), DialogResult.Ok)) return null;
    const selectedMode = Number(selection.selectedIndex);
    const presetIndex = Number(preset.selectedIndex);
    if (![0, 1, 2].includes(selectedMode) || !presets[presetIndex]) fail('Invalid conversion options.');
    const order = ['AST-03', 'CAT-00', 'CAT-05'];
    const guides = INPUTS.guides.slice().sort((a, b) => {
        const ai = order.indexOf(a.code), bi = order.indexOf(b.code);
        return (ai < 0 ? 99 : ai) - (bi < 0 ? 99 : bi) || a.code.localeCompare(b.code);
    }).filter(guide => selectedMode === 0 ||
        (selectedMode === 1 ? guide.code === 'AST-03' : order.includes(guide.code)));
    return { guides, preset: presets[presetIndex], packages: Boolean(packages.value), stop: Boolean(stop.value) };
}

function checkInitialDocuments(guides) {
    const initial = Document.all.slice();
    for (const document of initial) {
        for (const guide of guides) {
            if (samePath(document.path, join(INPUTS.idmlRoot || INPUTS.root, guide.idml))) {
                fail('Close the already-open input before conversion: ' + guide.idml);
            }
        }
    }
    return initial;
}

function newRunDirectory() {
    const parent = join(INPUTS.root, 'conversion-runs');
    if (!fs.exists(parent)) fs.createDirectory(parent);
    if (!fs.isDirectory(parent)) fail('Output parent is not a directory: ' + parent);
    const stamp = new Date().toISOString().replace(/[:.]/g, '-');
    for (let attempt = 0; attempt < 20; attempt++) {
        const suffix = '-' + Math.floor(Math.random() * 1000000).toString().padStart(6, '0');
        const directory = join(parent, stamp + suffix);
        if (fs.exists(directory)) continue;
        fs.createDirectory(directory);
        if (!fs.isDirectory(directory)) fail('Could not create output folder: ' + directory);
        for (const child of ['affinity', 'pdf', 'packages']) fs.createDirectory(join(directory, child));
        return directory;
    }
    fail('Could not allocate a new run folder. No existing folder was reused.');
}

function assertOwned(document, previousDocuments) {
    if (!document || !document.isOpen) fail('Affinity did not return an open document.');
    if (previousDocuments.some(previous => previous.isOpen && document.isSameObject(previous))) {
        fail('Affinity returned a previously open document; it was not changed or closed.');
    }
}

function loadOwned(path) {
    const before = Document.all.slice();
    const document = Document.load(path);
    assertOwned(document, before);
    return document;
}

function closeOwned(document, initialDocuments) {
    if (!document || !document.isOpen) return;
    assertOwned(document, initialDocuments);
    if (document.isDirty) fail('The script-owned document has unsaved changes; it was left open for inspection.');
    document.close();
    if (document.isOpen) fail('Affinity did not close the script-owned document. Batch stopped to avoid accumulating documents.');
}

function inspectDocument(document, guide) {
    const metrics = {
        pages: Number(document.rootNode.pageCount),
        spreads: Number(document.rootNode.spreadCount),
        dpi: Number(document.dpi),
        liveTextFrames: 0, imageNodes: 0, groups: 0,
        imageResources: [], fonts: [], dimensions: [], overflow: [],
        warnings: [], failures: [], nodeTypes: {}
    };
    if (metrics.pages !== guide.pages) {
        metrics.failures.push('Expected ' + guide.pages + ' pages; imported ' + metrics.pages + '.');
    }
    document.enumerateFontNames((name, installed) => {
        metrics.fonts.push({ name: String(name), installed: Boolean(installed) });
        if (!installed) metrics.failures.push('Missing font: ' + name);
        return EnumerationResult.Continue;
    });
    for (const spread of document.rootNode.children) {
        if (!spread.isSpreadNode) continue;
        const rectangle = spread.getSpreadExtents({ includeSpread: true, includeBleed: false, includeChildren: false });
        const width = Number(rectangle.width) * 25.4 / metrics.dpi;
        const height = Number(rectangle.height) * 25.4 / metrics.dpi;
        metrics.dimensions.push({ widthMm: width, heightMm: height, pages: Number(spread.pageCount) });
        if (Number(spread.pageCount) === 1 && (Math.abs(width - 210) > 0.8 || Math.abs(height - 297) > 0.8)) {
            metrics.failures.push('A spread does not measure A4 portrait: ' + width.toFixed(2) + ' x ' + height.toFixed(2) + ' mm.');
        }
    }
    for (const node of document.layers.all) {
        const type = Object.prototype.toString.call(node).slice(8, -1);
        metrics.nodeTypes[type] = (metrics.nodeTypes[type] || 0) + 1;
        if (node.isGroupNode) metrics.groups++;
        if (node.isTextNode) {
            metrics.liveTextFrames++;
            try {
                const flow = node.textFrameInterface.textFlow;
                if (flow) {
                    const overflow = flow.getOverflowInfo();
                    if (!enumIs(overflow.state, TextFlowOverflowState.None)) {
                        const item = { nodeId: String(node.nodeId), state: String(overflow.state),
                            value: enumValue(overflow.state), position: Number(overflow.position) };
                        metrics.overflow.push(item);
                        if (!enumIs(overflow.state, TextFlowOverflowState.Safe)) {
                            metrics.warnings.push(enumIs(overflow.state, TextFlowOverflowState.Warn) ?
                                'Text overflow warning at node ' + item.nodeId + '.' :
                                'Text flow needs visual review at node ' + item.nodeId + ' (' + item.state + ').');
                        }
                    }
                }
            } catch (error) {
                metrics.warnings.push('Text overflow could not be checked: ' + errorText(error));
            }
        }
        if (node.isImageNode || node.isEmbeddedDocumentNode) {
            metrics.imageNodes++;
            try {
                const image = node.imageResourceInterface;
                const size = image.originalSize;
                metrics.imageResources.push({
                    path: String(image.imageFilePath || ''),
                    placement: String(image.imagePlacement),
                    originalWidth: Number(size.width), originalHeight: Number(size.height)
                });
            } catch (error) {
                metrics.warnings.push('Image resource details could not be read: ' + errorText(error));
            }
        }
    }
    if (!metrics.liveTextFrames) metrics.failures.push('No live guide text was found.');
    if (guide.pictures && !metrics.imageNodes) metrics.failures.push('Expected screenshots/photos, but no image nodes were found.');
    if (metrics.liveTextFrames < guide.bodyTextFrames) {
        metrics.warnings.push('Fewer live text frames than IDML body frames (' + metrics.liveTextFrames + '/' + guide.bodyTextFrames + ').');
    }
    if (metrics.imageNodes < guide.pictures) {
        metrics.warnings.push('Fewer image nodes than IDML placements (' + metrics.imageNodes + '/' + guide.pictures + ').');
    }
    return metrics;
}

function recordExport(records, pdfPath) {
    if (!records || Number(records.count) < 1) fail('PDF export returned no result records.');
    const result = [];
    records.enumerate(record => {
        const item = { path: String(record.path || ''), success: Boolean(record.isSuccess),
            warnings: Boolean(record.hasWarnings), warning: String(record.warningMessage || ''),
            error: String(record.errorMessage || ''), result: String(record.result) };
        result.push(item);
        return EnumerationResult.Continue;
    });
    if (!result.length || result.some(item => !item.success)) {
        fail('PDF export failed: ' + JSON.stringify(result));
    }
    if (!result.some(item => samePath(item.path, pdfPath))) {
        fail('PDF exporter did not report the requested output path: ' + JSON.stringify(result));
    }
    positiveFile(pdfPath);
    return result;
}

function convertGuide(guide, options, context) {
    const base = guide.code + '-v' + guide.version;
    const nativePath = join(context.directory, 'affinity/' + base + '.af');
    const pdfPath = join(context.directory, 'pdf/' + base + '.pdf');
    const result = {
        code: guide.code, version: guide.version, pages: guide.pages,
        status: 'running', native: null, pdf: null, package: null,
        warnings: [], errors: [], stage: 'import', inputSha256: guide.sha256
    };
    context.report.guides.push(result);
    let document = null;
    function event(stage, details) {
        result.stage = stage;
        context.event(stage, Object.assign({ code: guide.code }, details || {}));
    }
    try {
        event('import', { input: guide.idml });
        document = loadOwned(join(INPUTS.idmlRoot || INPUTS.root, guide.idml));
        result.import = inspectDocument(document, guide);
        result.warnings.push(...result.import.warnings);
        event('save-native', { output: nativePath });
        noExisting(nativePath, context.directory);
        document.saveAs(nativePath);
        positiveFile(nativePath);
        result.native = nativePath;
        if (!samePath(document.path, nativePath)) fail('Affinity did not associate the saved document with the requested .af path.');
        closeOwned(document, context.initial);
        document = null;
        if (result.import.failures.length) fail(result.import.failures.join(' '));
        event('reopen-native');
        document = loadOwned(nativePath);
        result.reopened = inspectDocument(document, guide);
        result.warnings.push(...result.reopened.warnings);
        if (result.reopened.failures.length) fail(result.reopened.failures.join(' '));
        if (result.import.liveTextFrames !== result.reopened.liveTextFrames ||
            result.import.imageNodes !== result.reopened.imageNodes ||
            result.import.groups !== result.reopened.groups) {
            fail('Object counts changed after native save/reopen. Inspect the saved file before export.');
        }
        event('export-pdf', { output: pdfPath, preset: options.preset });
        noExisting(pdfPath, context.directory);
        const area = FileExportArea.createForPages('1-' + guide.pages);
        const exportOptions = FileExportOptions.createWithPresetName(options.preset);
        result.exports = recordExport(document.export(pdfPath, exportOptions, area, null), pdfPath);
        result.pdf = pdfPath;
        for (const item of result.exports) {
            if (item.warnings) result.warnings.push('PDF export: ' + (item.warning || item.result));
        }
        if (options.packages) {
            event('package-images');
            const packageFolder = join(context.directory, 'packages/' + base);
            noExisting(packageFolder, context.directory);
            fs.createDirectory(packageFolder);
            const packagePath = join(packageFolder, base + '.afpackage');
            try {
                noExisting(packagePath, context.directory);
                document.saveAsPackage(packagePath, PackageResourcesPolicy.IncludeImages);
                if (!fs.exists(packagePath)) fail('Affinity did not create the requested resource package.');
                result.package = packagePath;
                result.packagePolicy = 'IncludeImages; font files excluded';
            } catch (error) {
                result.warnings.push('Resource packaging failed: ' + errorText(error));
            }
        }
        closeOwned(document, context.initial);
        document = null;
        result.warnings = Array.from(new Set(result.warnings));
        result.status = result.warnings.length ? 'converted-with-warnings' : 'converted-awaiting-review';
        event('guide-finished', { status: result.status });
    } catch (error) {
        result.status = 'failed';
        result.errors.push(errorText(error));
        try { context.event('guide-failed', { code: guide.code, stage: result.stage, error: errorText(error) }); }
        catch (logError) { result.errors.push('Journal error: ' + errorText(logError)); }
    } finally {
        if (document && document.isOpen) {
            try { closeOwned(document, context.initial); }
            catch (error) {
                result.status = 'failed';
                result.errors.push(errorText(error));
                context.mustStop = true;
                result.leftOpen = true;
            }
        }
    }
    return result;
}

function escapeHtml(value) {
    return String(value == null ? '' : value).replace(/[&<>"']/g, character =>
        ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[character]));
}

function reportHtml(report) {
    function link(path, label) {
        if (!path) return '';
        const relative = cleanPath(path).slice(cleanPath(report.directory).length + 1);
        const url = relative.split('/').map(encodeURIComponent).join('/');
        return '<a href="' + escapeHtml(url) + '">' + label + '</a>';
    }
    return '<!doctype html><html lang="en"><meta charset="utf-8"><title>Guide conversion report</title>' +
        '<style>body{font:16px/1.5 Arial,sans-serif;max-width:1150px;margin:40px auto;padding:0 20px}' +
        'table{border-collapse:collapse;width:100%}td,th{text-align:left;vertical-align:top;padding:12px;border-bottom:1px solid #ccc}' +
        'a{color:#075e91}small{display:block}pre{white-space:pre-wrap}</style><h1>Guide conversion report</h1>' +
        '<p>' + escapeHtml(report.application) + ' | ' + escapeHtml(report.started) + '</p>' +
        '<p>These are conversion outputs awaiting visual and editing review. A successful conversion is not guide acceptance.</p>' +
        '<p>Native files: ' + report.summary.nativeFiles + '; PDFs: ' + report.summary.pdfFiles +
        '; failed: ' + report.summary.failed + '; not attempted: ' + report.summary.notAttempted + '.</p>' +
        '<p><a href="report.json">Detailed report</a> | <a href="events.jsonl">Execution journal</a></p>' +
        (report.errors.length ? '<pre>' + escapeHtml(report.errors.join('\n')) + '</pre>' : '') +
        '<table><thead><tr><th>Guide</th><th>Status</th><th>Files</th><th>Checks and issues</th></tr></thead><tbody>' +
        report.guides.map(guide => '<tr><td>' + escapeHtml(guide.code + ' v' + guide.version) +
            '</td><td>' + escapeHtml(guide.status) + '</td><td>' + link(guide.native, 'Affinity') + ' ' +
            link(guide.pdf, 'PDF') + ' ' + link(guide.package, 'Resource package') + '</td><td>' +
            escapeHtml(guide.errors.concat(guide.warnings).join('\n')) +
            '<small>' + (guide.reopened ? escapeHtml(guide.reopened.pages + ' pages; ' +
                guide.reopened.liveTextFrames + ' live text frames; ' + guide.reopened.imageNodes + ' image nodes') : '') +
            '</small></td></tr>').join('') + '</tbody></table>' +
        '<p>Still required: visual A4 review, paragraph/style and picture-frame edits, vector/master checks, ' +
        'and reopening a relocated resource package. Compare with the prepared review pages.</p></html>';
}

function summaryFor(report) {
    return {
        attempted: report.guides.length,
        nativeFiles: report.guides.filter(guide => guide.native).length,
        pdfFiles: report.guides.filter(guide => guide.pdf).length,
        packages: report.guides.filter(guide => guide.package).length,
        failed: report.guides.filter(guide => guide.status === 'failed').length,
        withWarnings: report.guides.filter(guide => guide.status === 'converted-with-warnings').length,
        notAttempted: report.requested.length - report.guides.length
    };
}

function runConversion(options) {
    const initial = checkInitialDocuments(options.guides);
    const directory = newRunDirectory();
    const report = {
        scriptVersion: '2026-11-05', inputSet: INPUTS.inputSet,
        application: String(Application.productFullName) + ' ' + String(Application.shortVersion) +
            ' build ' + String(Application.buildVersion),
        started: new Date().toISOString(), directory,
        requested: options.guides.map(guide => guide.code), preset: options.preset,
        status: 'running', guides: [], errors: [],
        inputValidation: 'Prepared SHA-256 values recorded; runtime checks exact input file sizes, not hashes.',
        reviewStatus: 'Native execution must still be checked visually and by editing; no owner acceptance inferred.'
    };
    const journalPath = join(directory, 'events.jsonl');
    noExisting(journalPath, directory);
    const journal = File.create(journalPath, 'wb');
    const context = {
        directory, initial, report, mustStop: false,
        event: (name, data) => {
            writeText(journal, JSON.stringify({ time: new Date().toISOString(), event: name, data }) + '\n');
            console.log('[Guides] ' + name + (data && data.code ? ': ' + data.code : ''));
        }
    };
    try {
        if (!journal.isOpen) fail('Could not create the execution journal.');
        context.event('run-started', { requested: report.requested, preset: options.preset });
        for (const guide of options.guides) {
            const result = convertGuide(guide, options, context);
            if (context.mustStop || (result.status === 'failed' && options.stop)) break;
        }
        report.status = report.guides.some(guide => guide.status === 'failed') ? 'incomplete' : 'converted-awaiting-review';
    } catch (error) {
        report.status = 'incomplete';
        report.errors.push(errorText(error));
    } finally {
        report.finished = new Date().toISOString();
        report.summary = summaryFor(report);
        try {
            if (journal.isOpen) journal.close();
        } catch (error) {
            report.errors.push('Could not close journal: ' + errorText(error));
            report.status = 'incomplete';
        }
        try {
            writeNew(join(directory, 'report.json'), JSON.stringify(report, null, 2) + '\n', directory);
            writeNew(join(directory, 'report.html'), reportHtml(report), directory);
        } catch (error) {
            report.status = 'incomplete';
            report.errors.push('Could not save final report: ' + errorText(error));
            console.error(JSON.stringify(report, null, 2));
        }
    }
    return report;
}

function main() {
    try {
        preflightInputs();
        const options = chooseOptions(pdfPresetNames());
        if (!options) return;
        const report = runConversion(options);
        const summary = report.summary;
        Application.alert(summary.nativeFiles + ' native files; ' + summary.pdfFiles + ' PDFs; ' +
            summary.failed + ' failed; ' + summary.notAttempted + ' not attempted.\n\n' +
            report.directory + '/report.html\n\n' +
            (report.errors.length ? report.errors.join('\n') + '\n\n' : '') +
            'Outputs await visual and editing review.', 'Guide conversion: ' + report.status);
        return report;
    } catch (error) {
        console.error('[Guides] ' + errorText(error));
        Application.alert(errorText(error), 'Guide conversion stopped');
        return { status: 'preflight-failed', error: errorText(error) };
    }
}

module.exports.main = main;
