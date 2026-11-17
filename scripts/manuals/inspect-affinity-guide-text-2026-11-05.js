/* Run manually inside Affinity. Reads open guide documents; never edits them. */
'use strict';
const { Application } = require('/application.js');
const { Document } = require('/document.js');
const { Dialog, DialogResult } = require('/dialog.js');
const { File, fs } = require('/fs.js');
const { Buffer } = require('/buffer.js');
const { EnumerationResult } = require('affinity:common');
const ROOT = 'C:/dev/snipe-it-fork/output/manuals';

function value(x) { return x && typeof x === 'object' && 'value' in x ? x.value : x; }
function clean(x) { return String(x || '').replace(/\\/g, '/').replace(/\/+$/, ''); }
function ownGuide(document) {
    const path = clean(document.path);
    return document.isOpen && path.toLowerCase().startsWith(clean(ROOT).toLowerCase() + '/') &&
        /\/(?:AC|AST|CAT|CMP|HELP|SC|USR|WF)-\d+-v\d+[^/]*\.(?:af|idml)$/i.test(path);
}
function writeNew(path, payload) {
    if (fs.exists(path)) throw new Error('Existing inspection preserved.');
    const file = File.create(path, 'wb');
    try {
        if (!file.isOpen) throw new Error('Cannot create inspection report.');
        const buffer = Buffer.utf8(JSON.stringify(payload, null, 2) + '\n');
        if (file.write(buffer, buffer.length) !== buffer.length) throw new Error('Incomplete report write.');
        file.flush();
    } finally { if (file.isOpen) file.close(); }
}

function main() {
    const documents = Array.from(Document.all).filter(ownGuide);
    if (!documents.length) throw new Error('Open a guide .af or IDML below ' + ROOT + ' first. Other documents are excluded.');
    const dialog = Dialog.create('Guide text inspection - read only');
    const group = dialog.addColumn().addGroup();
    const choice = group.addComboBox('Guide', documents.map(d => clean(d.path)), 0);
    const phases = ['Before edit', 'After one typed character', 'After plain-text paste', 'After Enter/new paragraph', 'After save/reopen'];
    const phase = group.addComboBox('Observation', phases, 0);
    group.addStaticText('Scope', 'Reads fonts/styles/overflow. Does not modify, save or close any document.');
    if (value(dialog.runModal()) !== value(DialogResult.Ok)) return;
    const document = documents[Number(choice.selectedIndex)];
    if (!document || !ownGuide(document)) throw new Error('Guide closed or changed during selection.');
    const report = { document: clean(document.path), phase: phases[Number(phase.selectedIndex)],
        dpi: Number(document.dpi), pages: Number(document.rootNode.pageCount),
        dirty: Boolean(document.isDirty), fonts: [], textFrames: [], images: [], groups: 0,
        errors: [], documentModifiedByScript: false, editabilityProven: false };
    document.enumerateFontNames((name, installed) => {
        report.fonts.push({ name: String(name), installed: Boolean(installed) });
        return EnumerationResult.Continue;
    });
    for (const node of document.layers.all) {
        if (node.isGroupNode) report.groups++;
        if (node.isTextNode) {
            const item = { nodeId: String(node.nodeId), name: String(node.name || ''), runs: [], errors: [] };
            try {
                item.textSample = String(node.getText(0, 180));
                const range = node.storyRange;
                const story = node.story;
                let position = Number(range.begin), end = Number(range.end);
                item.storyBegin = position; item.storyEnd = end;
                for (let limit = 0; position < end && limit < 12; limit++) {
                    const glyph = story.getGlyphAtts(position);
                    const paragraph = story.getParagraphAtts(position);
                    const font = glyph.font;
                    const next = Number(story.getGlyphAttsRunEnd(position, end));
                    item.runs.push({ begin: position, end: next, family: String(font.familyName),
                        postscript: String(font.postscriptName), fontValid: Boolean(font.isValid),
                        glyphStyle: String(glyph.styleName), paragraphStyle: String(paragraph.styleName),
                        heightNative: Number(glyph.height), leadingNative: Number(glyph.absoluteLeading) });
                    if (!(next > position)) break;
                    position = next;
                }
                const overflow = node.textFrameInterface.textFlow.getOverflowInfo();
                item.overflow = { state: value(overflow.state), position: Number(overflow.position) };
            } catch (error) { item.errors.push(String(error.message || error)); }
            report.textFrames.push(item);
        }
        if (node.isImageNode) {
            try {
                const image = node.imageResourceInterface, size = image.originalSize;
                report.images.push({ nodeId: String(node.nodeId), path: String(image.imageFilePath || ''),
                    width: Number(size.width), height: Number(size.height), placement: value(image.imagePlacement) });
            } catch (error) { report.errors.push('Image inspection: ' + String(error.message || error)); }
        }
    }
    const parent = clean(ROOT) + '/font-inspections';
    if (!fs.exists(parent)) fs.createDirectory(parent);
    if (!fs.isDirectory(parent)) throw new Error('Inspection parent is not a directory.');
    const stamp = new Date().toISOString().replace(/[^0-9TZ]/g, '') + '-' + Math.floor(Math.random() * 1000000);
    const folder = parent + '/' + stamp;
    if (fs.exists(folder)) throw new Error('Existing inspection folder preserved.');
    fs.createDirectory(folder);
    const path = folder + '/inspection.json';
    writeNew(path, report);
    Application.alert('Inspection saved: ' + path + '\nThe document was not edited. This report alone does not prove editability.', 'Guide text inspection');
    return report;
}
module.exports.main = main;
