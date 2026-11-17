"""Bind the verified converter to one guide per explicit Script Editor Run."""
import json
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]
SESSION = ROOT / 'output/manuals/affinity-native-2026-11-10'
RUNNER = SESSION / 'runner'
source = (RUNNER / 'convert-all-25-guides.js').read_text(encoding='utf-8')
target = RUNNER / 'convert-one-guide.js'
assert not target.exists(), 'Existing runner preserved.'
source = source.replace("const parent = join(INPUTS.root, 'conversion-runs');",
                        "const parent = join(SESSION_ROOT, 'conversion-runs');")
start = source.index('function chooseOptions(presets) {')
end = source.index('function checkInitialDocuments(guides)', start)
choice = r'''const SESSION_ROOT = __SESSION__;

function conversionMarker(guide) {
    return join(SESSION_ROOT, 'converted/' + guide.code + '-v' + guide.version + '.json');
}

function alreadyConverted(guide) {
    const marker = conversionMarker(guide);
    if (!fs.exists(marker)) return false;
    if (positiveFile(marker) > 65536) fail('Unexpected conversion marker size.');
    const record = JSON.parse(File.readAll(marker).toString('utf8'));
    if (record.code !== guide.code || record.inputSha256 !== guide.sha256) fail('Conversion marker does not match input.');
    positiveFile(record.native);
    positiveFile(record.pdf);
    return true;
}

function recordConverted(report) {
    const parent = join(SESSION_ROOT, 'converted');
    if (!fs.exists(parent)) fs.createDirectory(parent);
    if (!fs.isDirectory(parent)) fail('Conversion marker directory is invalid.');
    for (const result of report.guides) {
        if (result.status === 'failed' || !result.native || !result.pdf) continue;
        const guide = INPUTS.guides.find(item => item.code === result.code);
        const marker = conversionMarker(guide);
        if (fs.exists(marker)) continue;
        writeNew(marker, JSON.stringify({code: result.code, version: result.version,
            inputSha256: result.inputSha256, native: result.native, pdf: result.pdf,
            package: result.package, report: join(report.directory, 'report.json'),
            status: 'converted-awaiting-review'}, null, 2) + '\n', SESSION_ROOT);
    }
}

function chooseOptions(presets) {
    const order = ['AST-03', 'CAT-00', 'CAT-05'];
    const guides = INPUTS.guides.slice().sort((a,b) => {
        const ai=order.indexOf(a.code), bi=order.indexOf(b.code);
        return (ai<0?99:ai)-(bi<0?99:bi) || a.code.localeCompare(b.code);
    });
    const done = guides.map(alreadyConverted);
    const next = done.findIndex(item => !item);
    const dialog = Dialog.create('Native guides: one guide per Run');
    const group = dialog.addColumn().addGroup();
    group.addStaticText('Progress', done.filter(Boolean).length + '/25 converted; each still needs visual review.');
    const selection = group.addComboBox('Guide', guides.map((item,index) =>
        item.code + ' v' + item.version + (done[index]?' - already converted':'')), next>=0?next:0);
    let preferred = presets.findIndex(name => /inbit.*guide.*a4/i.test(name));
    if (preferred<0) preferred=presets.findIndex(name => /for print|print.?ready|afdruk/i.test(name));
    const preset = group.addComboBox('PDF preset', presets, preferred>=0?preferred:0);
    const packages = group.addCheckBox('Package full original images; exclude fonts', true);
    group.addStaticText('Pause', 'This Run handles one guide, saves/reopens/exports, then stops.');
    group.addStaticText('Files', 'Fresh output folder; existing native edits are preserved.');
    if (!enumIs(dialog.runModal(), DialogResult.Ok)) return null;
    const index=Number(selection.selectedIndex), presetIndex=Number(preset.selectedIndex);
    if (!guides[index] || !presets[presetIndex]) fail('Invalid guide or PDF preset.');
    return {guides:[guides[index]],preset:presets[presetIndex],packages:Boolean(packages.value),stop:true};
}

'''.replace('__SESSION__', json.dumps(SESSION.as_posix()))
source = source[:start] + choice + source[end:]
source = source.replace('const summary = report.summary;', 'recordConverted(report);\n        const summary = report.summary;')
assert source.count('function chooseOptions(') == 1
assert 'join(SESSION_ROOT, \'conversion-runs\')' in source
assert source.endswith('module.exports.main = main;\n')
source += '\n// Script Editor Run evaluates top-level code; exported main alone is not invoked.\nmain();\n'
target.write_text(source, encoding='utf-8', newline='\n')
print(json.dumps({'runner': str(target), 'mode':'One guide per explicit Run',
                  'preservesExistingFiles':True, 'nativeExecuted':False}))
