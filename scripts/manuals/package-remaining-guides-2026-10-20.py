"""Retain the reviewed proposals and publish their comparison/context, without acceptance."""
import hashlib
import html
import json
import os
from pathlib import Path
import shutil

ROOT = Path(__file__).resolve().parents[2]
ROUND = ROOT / 'resources/manuals/operator-guides/review-rounds/2026-10-20-remaining-guides'
DOCS = ROOT / 'docs/manuals/operator-guides'
PROOF = ROOT / 'output/manuals/proofs/remaining-guides-2026-10-20'
assert not (ROUND / 'manifest.sha256').exists(), 'Sealed round: create a new version.'
read = lambda p: json.loads(p.read_text(encoding='utf-8-sig'))
sha = lambda p: hashlib.sha256(p.read_bytes()).hexdigest()
relative = lambda p, start: Path(os.path.relpath(p, start)).as_posix()
write = lambda p, s: p.write_text(s, encoding='utf-8', newline='\n')
bullets = lambda xs: '\n'.join('- ' + x for x in xs)
manifest = read(ROUND / 'authoring-manifest.json')
inputs = {g['code']: g for g in read(ROUND / 'inputs.json')['guides']}
plan = read(ROUND / 'review-plan.json')
recognition = {g['code']: g for g in read(PROOF / 'recognition-review.json')}
geometry = read(PROOF / 'geometry-review.json')
assert len(manifest['guides']) == len(geometry) == len(recognition) == 17
assert sum(g['pageCount'] for g in manifest['guides']) == 56
assert not any(g['components']['errors'] or g['outside'] for g in geometry)
assert not any(g['retired'] or p['textOutside'] or p['textImageOverlaps'] for g in recognition.values() for p in g['pages'])
(ROUND / 'renders').mkdir(exist_ok=True)
(ROUND / 'qa').mkdir(exist_ok=True)
for name in ['geometry-review.json', 'recognition-review.json']:
    shutil.copyfile(PROOF / name, ROUND / 'qa' / name)

common = '''Current [authoring method](../authoring-method.md), shared contracts and
owner decisions govern this revision. Keep exact access-group names, Dutch
application/account locale, recognizable context, complete relevant controls
and useful A4 target scale. Review the unmarked crop first; annotate only a
remaining search or distinction. Routine checks use ordinary instructions,
recoverable exceptions are conditional, and a genuine halt states its trigger
and recovery. Preserve the task's sequence or explicit alternatives; complete
the required work before declaring completion. No acceptance is inferred.'''
limits = '''The mounted application is the separate refurbisher-followups checkout,
not the root checkout. Source fingerprints, actual actors, Dutch locale,
owned synthetic fixtures and save/trial boundaries are recorded in the batch
provenance. Genuine English strings and narrow native status-choice text
remain product gaps. Header roles preserve the intended task allocation;
local seeded/custom grants can be wider than the operational-role document.
Physical printing/component work, destructive catalogue branches and an
unfamiliar-reader/A4 paper trial are unperformed. Artifact checks and editorial
read-back do not establish reader performance or exact acceptance.'''

rows = []
review_sections = []
for g in manifest['guides']:
    code = g['code']; old = inputs[code]; n = plan[code]; version = g['version']
    pdf = ROOT / g['pdf']; source_html = ROOT / g['html']
    assert sha(pdf) == g['sha256'] == sha(ROOT / 'output/pdf' / pdf.name)
    assert sha(ROUND / old['input']) == old['baselineHtmlSha256']
    assert sha(ROUND / old['baselinePdf']) == old['sha256']
    for p in range(1, g['pageCount'] + 1):
        shutil.copyfile(PROOF / code / f'page-{p}.png', ROUND / 'renders' / f'{code}-{p}.png')
    version_file = DOCS / 'reviews' / f'{code}-v{version}.md'
    spec_file = DOCS / 'guides' / f'{code}.md'
    prior_spec = ROUND / 'inputs' / f'{code}-previous-specification.md'
    placement_rows = []
    for p in recognition[code]['pages']:
        for im in p['images']:
            placement_rows.append(f"| {p['page']} | `{im['key']}` | {im['w']:.1f} x {im['h']:.1f} mm |")
    links = f"[New PDF]({relative(pdf, version_file.parent)}) | [Exact baseline v{old['version']}]({relative(ROUND / old['baselinePdf'], version_file.parent)}) | [Visual comparison]({relative(ROUND / 'review.html', version_file.parent)}#{code})"
    review = f'''# {code} v{version} - Exact Version Review

Status: **Working draft; not accepted**. Date: 2026-10-20.
Baseline: v{old['version']}, {old['status']}; its bytes and decision remain unchanged.
New output: {g['pageCount']} A4 page(s). {links}.

PDF SHA-256: `{g['sha256']}`.

## Reader And Reasoning

Intended role: {n['role']}. Starting context: {n['needs']}.
Outcome: {n['outcome']}

Required alignment: {n['required']}

Chosen reader improvement: {n['improvement']}

Preserved support: {n['preserved']}

Placement/preservation tradeoff: {n['tradeoff']}

## Actions And Choices

{bullets(n['outline'])}

## Concrete Changes

{bullets(n['changes'])}

## Rendered Recognition Review

Every page was read as an instruction/reference at its intended page size and
compared with the retained baseline. Useful target text, complete control groups,
actual alternative/continuation meaning, conditions and handoffs were reviewed.
Screenshot labels were moved where they obscured native content; fresh crops
use their intended frame, not an older image's fitted content rectangle.
The following are actual displayed content sizes, not usability pass thresholds.

| Page | Evidence / recognition job key | Displayed content |
| --- | --- | --- |
{chr(10).join(placement_rows) if placement_rows else '| - | Conceptual diagrams / task index | Reviewed on the full page |'}

## Evidence, Checks And Limits

{limits}

The [batch review](remaining-guides-2026-10-20.md) records shared reconciliation,
actual UI trials, unperformed routes and final preservation/check results.
The [previous specification]({relative(prior_spec, version_file.parent)}) retains
the earlier reasoning and version history; its superseded procedure does not
override the current [specification](../guides/{code}.md).
'''
    write(version_file, review)
    spec = f'''# {code} {old['title']}

## Current Specification - 2026-10-20

Status: **Working draft v{version}; not accepted**. {g['pageCount']} A4 page(s).
Type: {n['kind']}. Recipe: `{n['recipe']}`.
Intended role: **{n['role']}**. Needed: {n['needs']}.
Prerequisite: {n['prerequisite']}. [Exact review](../reviews/{code}-v{version}.md).

This current specification replaces contradictory earlier procedure/metadata.
The [previous complete specification]({relative(prior_spec, spec_file.parent)})
preserves historical detail and exact acceptance links. Accepted artifacts,
runtime selections and the other eight current proposals are unchanged.

## Reader Outcome

{n['outcome']}

## Action And Choice Structure

{bullets(n['outline'])}

## Operating Contract And Boundaries

{bullets(n['contract'])}

## Revision Reasoning

Required alignment: {n['required']}

Chosen improvement: {n['improvement']}

Preserve: {n['preserved']}

Placement and tradeoff: {n['tradeoff']}

## Standards, Desired Targets And Retired Approaches

{common}

Target complete Dutch UI and clear field-to-result recognition. Retire invented
role labels, redundant group outlines, fixed password recipes, blanket STOP
wording, simulated screenshot states and post-completion alignment appendices.
Do not invent unavailable settings, a source field/policy or a future QA status.
{limits}

## Evidence And Maintenance

The exact PDF, HTML/SVG, baseline, current source captures, measured crop plan,
source/role/locale provenance and recognition placements are retained in the
[round]({relative(ROUND / 'README.md', spec_file.parent)}).
The generator is `scripts/manuals/generate-remaining-guides-2026-10-20.mjs`.
Frozen artifacts reproduce a proposal; the current specification and owner
decisions govern future revisions. Create a new version after sealing.
'''
    write(spec_file, spec)
    row = f"| [{code}](#{code}) | v{old['version']} -> v{version} | {old['pages']} -> {g['pageCount']} | {' '.join(n['changes'])} |"
    rows.append(row)
    html_changes = ''.join('<li>' + html.escape(x) + '</li>' for x in n['changes'])
    section = f'<section id="{code}"><h2>{code} v{version} - {html.escape(old["title"])}</h2><p><a href="{pdf.name}">Nieuwe PDF</a> | <a href="{relative(source_html, ROUND)}">HTML</a> | <a href="{old["baselinePdf"]}">Vergelijking v{old["version"]}</a> ({html.escape(old["status"])}) | <a href="{relative(version_file, ROUND)}">Redenering en controles</a></p><ul>{html_changes}</ul>'
    for p in range(1, g['pageCount'] + 1):
        baseline_page = (None if p == 3 else p if p < 3 else p - 1) if code == 'CAT-03' else (1 if code in ['CMP-02','USR-02'] else p)
        if baseline_page and baseline_page <= old['pages']:
            left = f'<figure><figcaption>Vergelijking v{old["version"]}, pagina {baseline_page}</figcaption><a href="baselines/renders/{code}-{baseline_page}.png"><img loading="lazy" src="baselines/renders/{code}-{baseline_page}.png" alt="{code} vergelijking pagina {baseline_page}"></a></figure>'
        else:
            left = '<div class="newpart">Nieuw geintegreerd onderdeel. De oude overige hoofdstukken blijven hierboven vergelijkbaar.</div>'
        section += f'<div class="pair">{left}<figure><figcaption>Nieuw v{version}, {p}/{g["pageCount"]} - concept</figcaption><a href="renders/{code}-{p}.png"><img loading="lazy" src="renders/{code}-{p}.png" alt="{code} nieuwe pagina {p}"></a></figure></div>'
    review_sections.append(section + '</section>')

brief = DOCS / 'reviews/remaining-guides-2026-10-20.md'
initial = brief.read_text(encoding='utf-8').split('## Final Changes And Outputs')[0]
initial = initial.replace('Status: in preparation;', 'Status: produced and reviewed;')
initial = initial.replace('Final exact artifacts, changes, checks and remaining gaps follow below after QA.', '')
change_table = '| Guide | Versions | Pages | Changes |\n| --- | --- | ---: | --- |\n' + '\n'.join(rows)
write(brief, initial.rstrip() + '\n\n## Final Changes And Outputs\n\n' + change_table.replace('](#', '](../../../../resources/manuals/operator-guides/review-rounds/2026-10-20-remaining-guides/review.html#') + '''

All seventeen are separate working drafts, not accepted. Total: 56 A4 pages,
compared with 52 baseline pages. CMP-02 gains a continuous second side to keep
its alternatives/result readable; USR-02 continues to a second side for task
rights and the separate status form; CAT-03 inserts numeric meaning after datatype;
CAT-05 adds one independent indexed maintenance route. Existing long explanatory
chapters remain. The [comparison](../../../../resources/manuals/operator-guides/review-rounds/2026-10-20-remaining-guides/review.html)
aligns CAT-03 chapters across the insertion and repeats CMP-02's and USR-02's old side
beside each new side. Equal A4 widths preserve meaningful visual comparison.

## Actual Evidence And Trials

- Global setting and new capture accounts use nl-NL. Genuine remaining English
  catalogue/component/control strings and narrow status-choice fields are
  retained. No UI text, success result or conflict banner was painted into a
  capture. The old physical photo and HP source excerpts remain exact reuse.
- Ordinary Admin created user 17 Tessavd with Generate. Superadmin assigned
  Refurbisher. Reset/save and temporary login succeeded; the user performed an
  own-password change. Finally the same account was disabled and saved Nee.
- Supervisor created owned asset 19 INBIT-AA0019 / GUIDE-SN-20261020-02 once.
  A post-create lookup failed, so the saved record was checked before read-only
  recapture; creation was not repeated. No printer job was sent.
- Senior Refurbisher created owned component 3, moved it to tray, reinstalled the
  same identity and moved it back. The final state is In Tray, no asset link.
  Its Extra record really coexisted with the Assumed model expectation.
- Supervisor created numeric definition 51 with min0/max8/step1/Sum. A second
  Enum example was staged only in the browser, including Add to list; no second
  definition was submitted. Existing HP specs/definitions were never saved.
- Owned group 6 was saved. Ordinary user 16 temporarily had only this custom group:
  Models view returned 200 and create 403; Refurbisher membership was restored.
  Per-user status choices were prepared for the same Tessavd in an unsaved form;
  no global status rule or other person's access was changed.
- Actual mount: refurbisher-followups at c539b9cd18; root checkpoint differs.
  The operational matrix and customized local foundation grants drift: local
  Senior Refurbisher can create assets despite the documented intended allocation.
  Header roles preserve task ownership; no permission change or claim of a strict
  technical minimum was made. The current Testing UI has one Edit Workflow Runs
  grant, not invented separate own/all edit grants.
- The official HP family page was rechecked on 2026-10-20. It supports the family
  and 15.6-inch diagonal, not exact-SKU RAM or optional configuration. No source
  field, fallback policy, unsupported optional-child behavior or future QA status
  was invented.

## Read-Back And Validation

Every final page and its useful baseline support was visually inspected, including
recognition size, implied actions, alternatives/continuations, conditional help
and return points. The pass corrected stale navigation, a save-before-handoff
error, save order across separate forms, hidden/mismatched recognition targets,
inherited fitted-image shrinkage, cropped Add to list, stale page references,
unconditional STOP treatment and screenshot-label collisions.

Strict generator checks passed for all 17 guides: A4 bounds, complete shared
references, source/crop/focus bounds and actual component geometry. A separate
check uses transformed SVG bounds and actual image content rectangles; no guide
body text crosses a screenshot. Native screenshot text and annotation collisions
still require the visual pass. PDF page sizes/counts/text, delivery hashes,
source preservation, local links, script syntax and historical sealed manifests
are checked in the retained QA record before sealing. Application unit tests are
not relevant to this manuals-only source change; actual bounded UI trials above
exercise the evidence claims.

## Remaining Limits

Exact-version owner review, an unfamiliar-reader trial and printed A4 trials remain.
Physical component work, actual printing/calibration/placement/scan, destructive
catalogue cleanup/default/lifecycle branches, model/model-number creation and
shared HP spec/definition saves were not exercised. Status permission combinations
and workflow override/error cases are not a full negative test suite. Translation,
native narrow-choice text and role-document/local-grant drift remain product or
policy gaps. Editorial predictions and geometry do not establish reader performance.
No accepted/runtime selection, app source, deployment, commit or other agent's
printer/camera/card work was changed.
''')

prior = []
for code, version, folder in [('AC-01',12,'2026-10-08-focus-corrections'),('SC-01',15,'2026-10-08-focus-corrections'),('AST-05',9,'2026-10-08-focus-corrections'),('AST-02',10,'2026-10-20-operational-guides'),('WF-01',16,'2026-10-20-operational-guides'),('WF-02',14,'2026-10-20-operational-guides'),('AST-04',8,'2026-10-20-operational-guides'),('HELP-01',9,'2026-10-20-operational-guides')]:
    import re
    matches = [p for p in (ROUND.parent / folder).glob('*.pdf') if re.match(code + r'.*-v' + str(version) + r'-draft.pdf$', p.name, re.I)]
    assert len(matches) == 1, (code, matches)
    prior.append({'code':code, 'version':version, 'pdf':relative(matches[0],ROOT), 'sha256':sha(matches[0]), 'status':'Working draft; not accepted'})
write(ROUND / 'current-authoring-index.json', json.dumps({'status':'25 current authoring proposals; no accepted/runtime promotion','guides':manifest['guides']+prior},indent=2)+'\n')
nav = ' | '.join(f'<a href="#{g["code"]}">{g["code"]}</a>' for g in manifest['guides'])
earlier = ' | '.join(f'<a href="{relative(ROOT/g["pdf"],ROUND)}">{g["code"]} v{g["version"]}</a>' for g in prior)
write(ROUND / 'review.html', '''<!doctype html><html lang="nl"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Resterende handleidingen - 20-10-2026</title><style>body{font:16px/1.5 system-ui,sans-serif;margin:0;background:#eef2f6;color:#172434}main{max-width:1600px;margin:auto;padding:24px}section{background:white;padding:20px;margin:24px 0;border:1px solid #c7d2de;border-radius:8px;scroll-margin-top:20px}.pair{display:grid;grid-template-columns:1fr 1fr;gap:20px}figure{margin:16px 0}figcaption{font-weight:650;padding-bottom:8px}img{display:block;width:100%;height:auto;border:1px solid #bac7d4}a{color:#145d93}.newpart{align-self:start;margin-top:45px;padding:24px;background:#f5f8fa}@media(max-width:750px){.pair{grid-template-columns:1fr}}summary{cursor:pointer;font-weight:bold}li{margin:5px 0}</style><main><h1>17 nieuwe concepten - 20-10-2026</h1><p>56 A4-pagina's. Alle nieuwe versies zijn werkconcepten, nog niet geaccepteerd. De vorige PDF's blijven ongewijzigd.</p>'''+f'<nav>{nav}</nav><p><a href="{relative(brief,ROUND)}">Centrale wijzigingslijst, redenering en grenzen</a></p><details><summary>Eerder gemaakte acht concepten</summary><p>{earlier}</p></details><p>Beide kolommen gebruiken dezelfde A4-breedte. Klik een afbeelding voor de volledige resolutie. CAT-03 volgt de hoofdstukken rond het ingevoegde berekeningsdeel; bij CMP-02 en USR-02 wordt de oude enkele zijde herhaald. Nieuwe opnamen hebben Nederlandse instellingen; echte onvertaalde toepassingstekst blijft zichtbaar.</p>'+''.join(review_sections)+'</main></html>')

table = '| Guide | Version | Pages | Review |\n| --- | --- | ---: | --- |\n'+'\n'.join(f'| {g["code"]} | v{g["version"]} | {g["pageCount"]} | [Exact review](reviews/{g["code"]}-v{g["version"]}.md) |' for g in manifest['guides'])
intro = '''## Remaining Guides - 2026-10-20

The owner requested all remaining guides using the corrected approach. Seventeen
new working drafts cover 56 A4 pages; all remain unaccepted. Together with the
previous eight proposals they cover all 25 active subjects. AST-01 stays retired.
Use the [comparison](../../../resources/manuals/operator-guides/review-rounds/2026-10-20-remaining-guides/review.html)
and [central changes/reasoning](reviews/remaining-guides-2026-10-20.md).
Exact group names, Dutch settings, task-shaped composition and restrained focus
remain the direction. Prior artifacts and accepted/runtime selections are unchanged.

'''
for name in ['README.md','HANDOFF.md','registry.md']:
    p=DOCS/name; text=p.read_text(encoding='utf-8'); title,body=text.split('\n',1)
    if '## Remaining Guides - 2026-10-20' not in body:
        write(p,title+'\n\n'+intro+(table+'\n\n' if name=='registry.md' else '')+body.lstrip())
p=DOCS/'reviews/README.md';text=p.read_text(encoding='utf-8');title,body=text.split('\n',1)
if '## Remaining Guides - 2026-10-20' not in body:
    write(p,title+'\n\n## Remaining Guides - 2026-10-20\n\n[Changes, reader briefs and limits](remaining-guides-2026-10-20.md). Seventeen\nnew working drafts / 56 A4 pages; no acceptance is inferred.\n\n'+body.lstrip())
write(ROUND / 'README.md', '''# Remaining Operator Guides - 2026-10-20

Seventeen working drafts / 56 A4 pages, not accepted. Open [review.html](review.html)
for new/unchanged-baseline PDFs, equally scaled pages and the per-guide change list.
The previous eight proposals are linked there; current-authoring-index.json is
an authoring index only and does not replace an accepted or runtime selection.

The round retains exact PDFs, self-contained HTML, editable SVG,144dpi renders,
52 baseline pages/PDFs/HTML, previous specifications, actual evidence/state,
crop plans, review briefs, scripts and source/role/locale provenance. Read the
linked batch review for actual local UI trials and unperformed physical,
destructive and reader tests. No print job was sent.

Render a frozen HTML into a different destination using
`scripts/manuals/render-guide-html.mjs --html <saved.html> --pdf <new.pdf>
--pages <count> --render-dir <new-directory>`. Supply GUIDE_NODE_MODULES_ROOT,
GUIDE_PDFINFO_PATH and GUIDE_PDFTOPPM_PATH for the bundled runtime. The new dated
generator and capture/crop/package scripts refuse to overwrite a sealed round;
make a reasoned new version instead. The retained authoring script copies and
input specs explain the proposal; current standards/owner decisions still govern.

manifest.json lists exact retained file hashes; manifest.sha256 seals that manifest.
Delivery copies live in output/pdf. No acceptance, commit or deployment is implied.
''')
print('Packaged17 proposals,56 pages,17 current specifications and17 exact reviews.')
