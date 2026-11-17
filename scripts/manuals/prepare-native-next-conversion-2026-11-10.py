"""Prepare one explicit native conversion per Script Editor Run, without a modal selector."""
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]
RUNNER = ROOT / 'output/manuals/affinity-native-2026-11-10/runner'
source = (RUNNER / 'convert-one-guide.js').read_text(encoding='utf-8')
target = RUNNER / 'convert-next-guide.js'
assert not target.exists(), 'Existing native runner preserved.'
suffix = '\n// Script Editor Run evaluates top-level code; exported main alone is not invoked.\nmain();\n'
assert source.endswith(suffix)
source = source[:-len(suffix)]
source = source.replace('module.exports.main = main;', 'module.exports.interactiveMain = main;')
source = source.replace("result.packagePolicy = 'IncludeImages; font files excluded';",
                        "result.packagePolicy = 'IncludeImages requested; final assembly must filter any copied fonts';")
source += r'''

function nextGuide() {
    try {
        preflightInputs();
        const preferred = ['AST-03','CAT-00','CAT-05'];
        const guides = INPUTS.guides.slice().sort((a,b)=>{
            const ai=preferred.indexOf(a.code),bi=preferred.indexOf(b.code);
            return (ai<0?99:ai)-(bi<0?99:bi)||a.code.localeCompare(b.code);
        });
        const guide=guides.find(g=>!alreadyConverted(g));
        if(!guide){Application.alert('All25 native files and PDFs exist. Complete visual/editing/relocation review before delivery.','Conversion runs complete');return;}
        const preset='PDF/X-3:2003';
        if(!pdfPresetNames().includes(preset))fail('The visually checked PDF/X-3 preset is unavailable.');
        const report=runConversion({guides:[guide],preset,packages:true,stop:true});
        recordConverted(report);
        const converted=guides.filter(alreadyConverted).length;
        const errors=report.errors.concat(...report.guides.map(g=>g.errors||[]));
        Application.alert(guide.code+' v'+guide.version+': '+report.status+'\n'+converted+'/25 converted; visual review remains.\n'+report.directory+(errors.length?'\n'+errors.join('\n'):''),'One guide conversion');
        return report;
    }catch(e){Application.alert(errorText(e),'Next conversion stopped');return {status:'failed',error:errorText(e)};}
}
module.exports.main=nextGuide;
'''
target.write_text(source, encoding='utf-8', newline='\n')
print(target)
