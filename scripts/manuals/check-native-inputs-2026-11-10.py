"""Verify that the text-default candidate preserves all guide content and layout."""
import hashlib
import json
from pathlib import Path
import zipfile

ROOT = Path(__file__).resolve().parents[2]
PREPARED = ROOT / 'output/manuals/final-guide-set-2026-11-04'
BASELINE = ROOT / 'output/manuals/affinity-idml-native-candidate-2026-11-05'
CANDIDATE = ROOT / 'output/manuals/affinity-native-2026-11-10/idml-inputs'
allowed = {'Resources/Styles.xml', 'Resources/Preferences.xml'}
verified = 0
for line in (PREPARED / 'preparation.sha256').read_text(encoding='utf-8').splitlines():
    digest, relative = line.split('  ', 1)
    assert hashlib.sha256((PREPARED / relative).read_bytes()).hexdigest() == digest, relative
    verified += 1
inventory = json.loads((PREPARED / 'source/inventory.json').read_text(encoding='utf-8'))
results = []
for guide in inventory['guides']:
    relative = 'idml/' + guide['code'] + '-v' + str(guide['version']) + '.idml'
    with zipfile.ZipFile(BASELINE / relative) as old, zipfile.ZipFile(CANDIDATE / relative) as new:
        assert old.namelist() == new.namelist()
        changed = {name for name in old.namelist() if old.read(name) != new.read(name)}
        assert changed == allowed, (guide['code'], changed)
        assert new.infolist()[0].filename == 'mimetype'
        assert new.infolist()[0].compress_type == zipfile.ZIP_STORED
        assert new.testzip() is None
        results.append({'code': guide['code'], 'pages': guide['pages'],
                        'changedParts': sorted(changed),
                        'guideContentImagesAndGeometryPreserved': True,
                        'nativeQualification': 'Pending'})
with zipfile.ZipFile(CANDIDATE / 'idml/AST-03-v20.idml') as candidate, zipfile.ZipFile(
        ROOT / 'output/manuals/affinity-text-defaults-probe-2026-11-05/idml/AST-03-v20.idml') as probe:
    assert candidate.namelist() == probe.namelist()
    assert all(candidate.read(name) == probe.read(name) for name in candidate.namelist())
report = {'originalFrozenFilesVerified': verified, 'guides': len(results),
          'pages': sum(g['pages'] for g in results), 'results': results,
          'ast03MatchesActualTypedProofInput': True, 'nativeBatchExecuted': False}
(CANDIDATE / 'qa/preservation-check.json').write_text(json.dumps(report, indent=2) + '\n', encoding='utf-8')
print(json.dumps({key: value for key, value in report.items() if key != 'results'}))
