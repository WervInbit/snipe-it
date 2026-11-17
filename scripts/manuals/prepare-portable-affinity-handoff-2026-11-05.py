"""Package corrected editable sources and a reversible, offline path rebinder."""
from pathlib import Path
import argparse
import hashlib
import html
import json
import shutil
import uuid
import zipfile

ROOT = Path(__file__).resolve().parents[2]
SOURCE = ROOT / 'output/manuals/final-guide-set-2026-11-04'
CANDIDATE = ROOT / 'output/manuals/affinity-idml-native-candidate-2026-11-05'
CONVERSION = ROOT / 'output/manuals/affinity-conversion-native-candidate-2026-11-05'
DEST = ROOT / 'output/manuals/affinity-portable-handoff-2026-11-05'
parser = argparse.ArgumentParser()
parser.add_argument('--output-directory', type=Path)
args = parser.parse_args()
if args.output_directory:
    DEST = args.output_directory.resolve()
    DEST.relative_to(ROOT / 'output/manuals')
STAGE = DEST.with_name(DEST.name + '.partial-' + uuid.uuid4().hex[:8])
assert not DEST.exists() and not DEST.with_suffix('.zip').exists(), 'Existing handoff preserved.'
STAGE.mkdir()


def digest(path):
    return hashlib.sha256(path.read_bytes()).hexdigest()


def copy(source, relative, expected=None):
    if expected:
        assert digest(source) == expected, str(source)
    target = STAGE / relative
    target.parent.mkdir(parents=True, exist_ok=True)
    shutil.copyfile(source, target)
    assert digest(target) == digest(source)


manifest = json.loads((CONVERSION / 'input-manifest.json').read_text(encoding='utf-8'))
inventory = json.loads((SOURCE / 'source/inventory.json').read_text(encoding='utf-8'))
assert len(manifest['guides']) == 25 and sum(g['pages'] for g in manifest['guides']) == 68
assert len(manifest['assets']) == 82
for asset in manifest['assets']:
    copy(SOURCE / asset['file'], asset['file'], asset['sha256'])
for guide in manifest['guides']:
    copy(CANDIDATE / guide['idml'], guide['idml'], guide['sha256'])
    copy(SOURCE / 'source' / (guide['code'] + '.json'), 'source/' + guide['code'] + '.json')
    copy(ROOT / 'docs/manuals/operator-guides/guides' / (guide['code'] + '.md'), 'specifications/' + guide['code'] + '.md')
    for page in range(1, guide['pages'] + 1):
        name = f'{guide["code"]}-{page}.png'
        copy(SOURCE / 'previews' / name, 'previews/' + name)
copy(SOURCE / 'source/inventory.json', 'source/inventory.json')
copy(CONVERSION / 'input-manifest.json', 'source/input-manifest.json')
copy(ROOT / 'scripts/manuals/convert-guides-in-affinity.template.js', 'tools/conversion-template.js')
copy(ROOT / 'scripts/manuals/prepare-portable-affinity.template.ps1', 'Prepare-Affinity.ps1')
for name in ['authoring-method', 'system', 'components', 'layouts', 'screenshots', 'decisions']:
    copy(ROOT / 'docs/manuals/operator-guides' / (name + '.md'), 'standards/' + name + '.md')
copy(ROOT / 'docs/manuals/operator-guides/reviews/native-conversion-2026-11-05.md', 'native-proof/OPERATION-REVIEW.md')
baseline = ROOT / 'output/manuals/AST-03-native-final-probe-20261105/baseline'
copy(baseline / 'AST-03-v20-baseline.af', 'native-proof/AST-03-v20.af',
     '99a2d623fb8c3d3bec92a502412a3ac206e530720f78652764ba0a912a833f0b')
copy(baseline / 'AST-03-v20-baseline.pdf', 'native-proof/AST-03-v20-layered.pdf',
     'd2fa3dcf9d552c014f1b758837980424732eb7e93d092050027cc86f1bad59b5')
recovered = ROOT / 'output/manuals/AST-03-nuc-recovery-backup-20261105/temp/2E6AEC9E-8213-4AC0-9863-5AB975662C53.pdf'
copy(recovered, 'native-proof/AST-03-v20-recovered-no-layers.pdf',
     'b1fa88d516382a6d0792d4903ba7e508f36386185039ad9f3cf662144e9f88c6')
for page in (1, 2):
    copy(baseline / f'poppler-page-{page}.png', f'native-proof/AST-03-{page}.png')

readme = """# Affinity Guide Handoff - 5 November 2026

This is the corrected 25-guide / 68-page source handoff. It is a working
proposal, not the completed native release. Guide wording and image evidence
remain the selected November4 proposals; this pass repairs file conversion.

Open REVIEW.html to see all25 guides at their intended page sizes. The previews
show the source design. AST-03 also has real native/PDF proof in native-proof.
The other24 native files have not been made. Full editing and portability proof,
the shared native template and exact-version acceptance are still outstanding.

## Prepare At Any Extracted Folder

1. Extract the entire ZIP, including assets, rather than opening it inside ZIP.
2. Open RUN-ME.html. Copy its preparation code and paste it in PowerShell.
   Supply the extracted folder when prompted. No admin or policy change is
   needed. Alternatively run Prepare-Affinity.ps1 with -GuidePackageRoot.
3. Preparation verifies every packaged source, makes new IDML copies with image
   links to this folder, and writes a generated conversion script in a fresh
   native-inputs folder. Source files and human native edits are not overwritten.
4. Open that generated JS in Affinity 3.3.3.5004 or a separately qualified later
   version's Scripting studio / Script Editor. Run the representative technical
   proof first: AST-03, CAT-00 and CAT-05. Scripting must be enabled by its owner.
5. Choose the actual qualified PDF preset. Prefer Include layers disabled for
   the PDF: PDFium hides a repeated optional layer in our layered AST-03 export,
   while Poppler displays it. This does not require flattening the native .af.
6. Read the per-run report, inspect every native-exported page and perform the
   practical editing tests below before running/releasing the full native set.

The preparer uses only Windows PowerShell/.NET file tools. It does not start or
control an app, use the network, enable scripting, alter security settings or
delete data. Each conversion run uses a new conversion-runs folder. The package
checksums establish integrity, not instructional quality or native compatibility.

## Native Editing Proof Still Required

Use separate proof copies. Edit a paragraph, lengthen it and resize its frame to
check actual reflow; edit a shared text style; move/resize a focus vector; recrop
the retained original image and replace one image; edit a group and the master.
Save/reopen the proof and inspect its PDF. Check copied native files/resources
after relocation. Never use a flattened PDF/SVG as the editable native master.
An earlier text insertion through the UI caused font fallback/overflow and was
undone. That has not established whether the cause was import or edit selection.
The saved native proof is genuine but is not yet fully editability-qualified.

Native-proof PDFs are technical evidence. The recovered no-layers PDF came from
Affinity's temporary export after a canceled Save dialog; preserve that provenance.
The actual matched native export is AST-03-v20-layered.pdf. The native proof may
retain original absolute resource links; all82 originals are included, but its
relocated resource behavior remains to be checked.

## Editing And Content Rules

Keep the actual access-group names, Dutch captures, recognizable entry controls,
complete alternatives and actual Save/result evidence. Use focus only for a
remaining search, not around an already isolated form. Preserve useful help at
its point of use and keep warnings conditional. AST-03 retains duplicate search,
both Dashboard entries, the list plus and the actual Save; registration notes
remain optional by owner decision. Existing application/configuration gaps are
recorded in the original review and still require release-target reconciliation.

Source JSON preserves text, vectors, logical groups, full-image crop transforms
and capture provenance. Standards/specification copies supply context; their
historical cross-links refer to the original repository. Do not infer acceptance
or application deployment from this source package. Continue human native edits
in new master versions rather than regenerating over them.
"""
(STAGE / 'README.md').write_text(readme, encoding='utf-8')
setup = '& {\n' + (STAGE / 'Prepare-Affinity.ps1').read_text(encoding='utf-8') + '\n}\n'
page = """<!doctype html><html lang="en"><meta charset="utf-8"><title>Prepare guides for Affinity</title>
<style>body{font:17px/1.5 Arial;max-width:950px;margin:40px auto;padding:0 22px;color:#102033}
button{font:inherit;padding:12px;background:#075e91;color:white;border:0;border-radius:4px}
textarea{display:block;width:100%;height:260px;font:12px/1.4 Consolas,monospace;margin-top:15px}
.note{padding:14px;background:#fff3d5}a{color:#075e91}</style>
<h1>Prepare the 25 guides for Affinity</h1><p class="note">Working source handoff. The full native release and practical editability proof are incomplete.</p>
<ol><li>Extract the complete package.</li><li>Copy the preparation code, open PowerShell and paste it. Supply this package folder when asked.</li>
<li>It creates a fresh folder with linked IDML copies and a conversion JS. Open that JS in Affinity's Script Editor.</li>
<li>Start with AST-03/CAT-00/CAT-05 technical proof. Inspect/edit the real native results before full conversion.</li></ol>
<p>No app control, network, deletion, security changes or overwrite of existing native edits.</p>
<button id="copy">Copy preparation code</button><span id="state" role="status"></span>
<textarea id="code" readonly spellcheck="false">""" + html.escape(setup, quote=False) + """</textarea>
<p><a href="README.md">Full instructions</a> | <a href="REVIEW.html">All25 source previews</a> | <a href="Prepare-Affinity.ps1">PowerShell file</a></p>
<script>document.getElementById('copy').onclick=async()=>{const t=document.getElementById('code');try{await navigator.clipboard.writeText(t.value);document.getElementById('state').textContent=' Copied. Paste in PowerShell.'}catch(e){t.focus();t.select();document.getElementById('state').textContent=' Selected. Press Ctrl+C.'}};</script></html>"""
(STAGE / 'RUN-ME.html').write_text(page, encoding='utf-8')
cards = []
for guide in manifest['guides']:
    doc = json.loads((STAGE / 'source' / (guide['code'] + '.json')).read_text(encoding='utf-8'))
    images = ''.join(f'<a href="previews/{guide["code"]}-{p}.png"><img loading="lazy" src="previews/{guide["code"]}-{p}.png" alt="{guide["code"]} page {p}"></a>'
                     for p in range(1, guide['pages'] + 1))
    cards.append(f'<section id="{guide["code"]}"><h2>{html.escape(doc["title"])} - {guide["code"]} v{guide["version"]}</h2>'
                 f'<p>{guide["pages"]} A4 pages. Source proposal; native release incomplete. '
                 f'<a href="{guide["idml"]}">IDML source</a> | <a href="specifications/{guide["code"]}.md">Specification</a></p><div>{images}</div></section>')
(STAGE / 'REVIEW.html').write_text('<!doctype html><html lang="en"><meta charset="utf-8"><title>All25 corrected source guides</title>'
    '<style>body{font:16px/1.5 Arial;margin:30px;color:#102033}section{border-top:1px solid #cad5e1;padding:20px 0}'
    'section div{display:flex;gap:12px;flex-wrap:wrap}img{width:240px;height:auto;border:1px solid #ccd}a{color:#075e91}</style>'
    '<h1>All25 source proposals</h1><p>These are intended source-page previews. Only AST-03 has returned native proof; full native production and editing proof remain incomplete.</p>'
    '<p><a href="RUN-ME.html">Prepare for Affinity</a> | <a href="README.md">Editing handoff</a></p>' + ''.join(cards) + '</html>', encoding='utf-8')
checksums = ''.join(f'{digest(p)}  {p.relative_to(STAGE).as_posix()}\n' for p in sorted(STAGE.rglob('*')) if p.is_file())
(STAGE / 'package-files.sha256').write_text(checksums, encoding='utf-8')
STAGE.rename(DEST)
archive_path = DEST.with_suffix('.zip')
with zipfile.ZipFile(archive_path, 'x', compression=zipfile.ZIP_DEFLATED) as z:
    for file in sorted(DEST.rglob('*')):
        if file.is_file():
            z.write(file, file.relative_to(DEST.parent).as_posix())
print(json.dumps({'folder': str(DEST), 'zip': str(archive_path), 'bytes': archive_path.stat().st_size,
                  'sha256': digest(archive_path), 'guides': 25, 'pages': 68, 'nativeRelease': False}))
