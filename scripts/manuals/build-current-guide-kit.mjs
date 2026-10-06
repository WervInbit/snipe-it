import assert from 'node:assert/strict';
import crypto from 'node:crypto';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const scriptDirectory = path.dirname(fileURLToPath(import.meta.url));
const repoRoot = path.resolve(scriptDirectory, '..', '..');
const defaultTarget = path.join(repoRoot, 'output', 'manuals', 'current-guide-kit-2026-09-15');
const targetArgument = process.argv.indexOf('--out');
const targetRoot = path.resolve(targetArgument >= 0 ? process.argv[targetArgument + 1] : defaultTarget);
const temporaryRoot = `${targetRoot}.building-${process.pid}`;
const slash = (value) => value.replaceAll('\\', '/');
const sha256 = (file) => crypto.createHash('sha256').update(fs.readFileSync(file)).digest('hex');
const readJson = (file) => JSON.parse(fs.readFileSync(file, 'utf8'));
const copyFile = (source, destination) => {
    assert.ok(fs.existsSync(source), `Missing source file: ${source}`);
    fs.mkdirSync(path.dirname(destination), { recursive: true });
    fs.copyFileSync(source, destination, fs.constants.COPYFILE_EXCL);
};
const copyTree = (source, destination) => {
    assert.ok(fs.existsSync(source), `Missing source directory: ${source}`);
    fs.cpSync(source, destination, { recursive: true, errorOnExist: true, force: false });
};

assert.ok(!fs.existsSync(targetRoot), `Target already exists: ${targetRoot}`);
assert.ok(!fs.existsSync(temporaryRoot), `Temporary target already exists: ${temporaryRoot}`);
fs.mkdirSync(temporaryRoot, { recursive: true });

const selectionSource = path.join(
    repoRoot,
    'resources', 'manuals', 'operator-guides', 'review-rounds', '2026-09-10', 'current-guide-selection-v4.json',
);
const draftManifestSource = path.join(repoRoot, 'resources', 'manuals', 'operator-guides', 'drafts', 'manifest.json');
const selection = readJson(selectionSource);
const draftManifest = readJson(draftManifestSource);
const trioCodes = ['USR-05', 'CAT-05', 'CAT-06'];
const trio = trioCodes.map((code) => {
    const matches = draftManifest.artifacts.filter((entry) => entry.code === code);
    assert.ok(matches.length > 0, `No draft manifest entry for ${code}`);
    return matches.sort((left, right) => right.version - left.version)[0];
});

const details = {
    'AC-01': {
        purpose: 'Sign in to Snipe-IT with the assigned account and follow the recovery route when login fails.',
        sourceHtml: 'output/manuals/proofs/guide-followups-2026-09-08/AC-01-v9/AC-01-login-v9-draft.html',
        scripts: ['scripts/manuals/generate-guide-followups.mjs', 'scripts/manuals/generate-ac01-snipe-proof.mjs'],
        command: 'node scripts/manuals/generate-guide-followups.mjs AC-01',
    },
    'AC-02': {
        purpose: 'Change the signed-in user\'s own password and verify the success message.',
        sourceHtml: 'output/manuals/proofs/guide-followups-2026-09-08/AC-02-v4/ac-02-eigen-wachtwoord-wijzigen-v4-draft.html',
        scripts: ['scripts/manuals/generate-guide-followups.mjs', 'scripts/manuals/generate-user-account-guide-review.mjs'],
        command: 'node scripts/manuals/generate-guide-followups.mjs AC-02',
    },
    'SC-01': {
        purpose: 'Find the correct asset by identifier, compare identifying data, and open its detail page.',
        sourceHtml: 'output/manuals/proofs/owner-corrections-2026-09-10/SC-01-v12/SC-01-asset-vinden-en-openen-v12-draft.html',
        scripts: ['scripts/manuals/generate-owner-corrections.mjs', 'scripts/manuals/generate-guide-followups.mjs', 'scripts/manuals/generate-sc01-snipe-proof.mjs'],
        command: 'node scripts/manuals/generate-owner-corrections.mjs SC-01',
    },
    'AST-02': {
        purpose: 'Choose the correct refurbishment route for an opened asset and hand off to the matching task guide.',
        sourceHtml: 'output/manuals/proofs/owner-corrections-2026-09-10/AST-02-v8/AST-02-refurbishment-route-v8-draft.html',
        scripts: ['scripts/manuals/generate-owner-corrections.mjs', 'scripts/manuals/generate-guide-followups.mjs', 'scripts/manuals/generate-revised-guide-set.mjs'],
        command: 'node scripts/manuals/generate-owner-corrections.mjs AST-02',
    },
    'AST-03': {
        purpose: 'Register a new asset, check its identity, and print and apply the asset label.',
        sourceHtml: 'output/manuals/proofs/guide-followups-2026-09-08/AST-03-v15/AST-03-asset-registreren-en-labelen-v15-draft.html',
        scripts: ['scripts/manuals/generate-guide-followups.mjs', 'scripts/manuals/generate-revised-guide-set.mjs'],
        command: 'node scripts/manuals/generate-guide-followups.mjs AST-03',
    },
    'AST-04': {
        purpose: 'Confirm required work is complete, finish the asset work, and hand it to the next responsible person.',
        sourceHtml: 'output/manuals/proofs/guide-followups-2026-09-08/AST-04-v6/AST-04-complete-handoff-v6-draft.html',
        scripts: ['scripts/manuals/generate-guide-followups.mjs', 'scripts/manuals/generate-revised-guide-set.mjs'],
        command: 'node scripts/manuals/generate-guide-followups.mjs AST-04',
    },
    'AST-05': {
        purpose: 'Review the completed asset and either release it or return it for correction.',
        sourceHtml: 'output/manuals/proofs/guide-followups-2026-09-08/AST-05-v6/AST-05-review-release-v6-draft.html',
        scripts: ['scripts/manuals/generate-guide-followups.mjs', 'scripts/manuals/generate-revised-guide-set.mjs'],
        command: 'node scripts/manuals/generate-guide-followups.mjs AST-05',
    },
    'WF-01': {
        purpose: 'Start the right workflow profile once for the correct asset and recognize an already-open run.',
        sourceHtml: 'output/manuals/proofs/owner-corrections-2026-09-10/WF-01-v13/WF-01-workflow-starten-v13-draft.html',
        scripts: ['scripts/manuals/generate-owner-corrections.mjs', 'scripts/manuals/generate-wf01-v12.mjs', 'scripts/manuals/generate-workflow-guide-review.mjs'],
        command: 'node scripts/manuals/generate-owner-corrections.mjs WF-01',
    },
    'WF-02': {
        purpose: 'Carry out workflow checks, record results and evidence, resolve failures, and complete the run.',
        sourceHtml: 'output/manuals/proofs/guide-followups-2026-09-08/WF-02-v12/WF-02-complete-workflow-v12-draft.html',
        scripts: ['scripts/manuals/generate-guide-followups.mjs', 'scripts/manuals/generate-workflow-guide-review.mjs'],
        command: 'node scripts/manuals/generate-guide-followups.mjs WF-02',
    },
    'CMP-01': {
        purpose: 'Place an already-registered physical component into the correct asset.',
        sourceHtml: 'output/manuals/proofs/guide-followups-2026-09-08/CMP-01-v6/CMP-01-install-existing-v6-draft.html',
        scripts: ['scripts/manuals/generate-guide-followups.mjs', 'scripts/manuals/generate-component-guide-review.mjs'],
        command: 'node scripts/manuals/generate-guide-followups.mjs CMP-01',
    },
    'CMP-02': {
        purpose: 'Register a new physical component and then place it into the correct asset.',
        sourceHtml: 'output/manuals/proofs/owner-corrections-2026-09-10/CMP-02-v6/CMP-02-register-install-v6-draft.html',
        scripts: ['scripts/manuals/generate-owner-corrections.mjs', 'scripts/manuals/generate-guide-followups.mjs', 'scripts/manuals/generate-component-followup-guides.mjs'],
        command: 'node scripts/manuals/generate-owner-corrections.mjs CMP-02',
    },
    'CMP-04': {
        purpose: 'Remove a component from an asset and return it to the correct storage tray.',
        sourceHtml: 'output/manuals/proofs/owner-corrections-2026-09-10/CMP-04-v7/CMP-04-component-to-tray-v7-draft.html',
        scripts: ['scripts/manuals/generate-owner-corrections.mjs', 'scripts/manuals/generate-component-followup-guides.mjs'],
        command: 'node scripts/manuals/generate-owner-corrections.mjs CMP-04',
    },
    'HELP-01': {
        purpose: 'Diagnose common operator problems, stop safely, and choose the correct escalation route.',
        sourceHtml: 'output/manuals/proofs/guide-followups-2026-09-08/HELP-01-v7/HELP-01-problems-v7-draft.html',
        scripts: ['scripts/manuals/generate-guide-followups.mjs', 'scripts/manuals/generate-component-followup-guides.mjs'],
        command: 'node scripts/manuals/generate-guide-followups.mjs HELP-01',
    },
    'USR-01': {
        purpose: 'Create a user account with the agreed identity, login state, and group assignment.',
        sourceHtml: 'output/manuals/proofs/owner-corrections-2026-09-10/USR-01-v13/USR-01-gebruiker-toevoegen-v13-draft.html',
        scripts: ['scripts/manuals/generate-owner-corrections.mjs', 'scripts/manuals/generate-guide-followups.mjs', 'scripts/manuals/generate-user-account-guide-review.mjs'],
        command: 'node scripts/manuals/generate-owner-corrections.mjs USR-01',
    },
    'USR-02': {
        purpose: 'Change a user\'s group membership or direct rights and verify the stored access result.',
        sourceHtml: 'output/manuals/proofs/owner-corrections-2026-09-10/USR-02-v11/USR-02-rol-en-rechten-wijzigen-v11-draft.html',
        scripts: ['scripts/manuals/generate-owner-corrections.mjs', 'scripts/manuals/generate-guide-followups.mjs', 'scripts/manuals/generate-user-account-guide-review.mjs'],
        command: 'node scripts/manuals/generate-owner-corrections.mjs USR-02',
    },
    'USR-03': {
        purpose: 'Reset another user\'s password without changing the account\'s login decision.',
        sourceHtml: 'output/manuals/proofs/guide-followups-2026-09-08/USR-03-v4/usr-03-wachtwoord-resetten-v4-draft.html',
        scripts: ['scripts/manuals/generate-guide-followups.mjs', 'scripts/manuals/generate-user-account-guide-review.mjs'],
        command: 'node scripts/manuals/generate-guide-followups.mjs USR-03',
    },
    'USR-04': {
        purpose: 'Disable a user\'s login while keeping the account and its history intact.',
        sourceHtml: 'output/manuals/proofs/owner-corrections-2026-09-10/USR-04-v6/USR-04-gebruiker-uitschakelen-of-herstellen-v6-draft.html',
        scripts: ['scripts/manuals/generate-owner-corrections.mjs', 'scripts/manuals/generate-guide-followups.mjs', 'scripts/manuals/generate-user-account-guide-review.mjs'],
        command: 'node scripts/manuals/generate-owner-corrections.mjs USR-04',
    },
    'USR-05': {
        purpose: 'Open administrator settings, create or edit a permission group, understand conflicting group rights, assign the group, and test the result.',
        sourceHtml: 'output/manuals/proofs/2026-09-15-planned-guides-v2/usr-05-v2/usr-05-v2.html',
        scripts: ['scripts/manuals/generate-planned-guide-v2.mjs'],
        command: 'set SNIPEIT_GUIDE_FILTER=USR-05 && node scripts/manuals/generate-planned-guide-v2.mjs',
    },
    'CAT-00': {
        purpose: 'Explain how the catalog is structured and route each catalog task to the correct detailed guide.',
        sourceHtml: 'output/manuals/proofs/owner-corrections-2026-09-10/CAT-00-v11/CAT-00-catalogus-begrijpen-v11-draft.html',
        scripts: ['scripts/manuals/generate-owner-corrections.mjs', 'scripts/manuals/generate-guide-followups.mjs', 'scripts/manuals/generate-catalog-guide-review.mjs'],
        command: 'node scripts/manuals/generate-owner-corrections.mjs CAT-00',
    },
    'CAT-01': {
        purpose: 'Decide whether a base model or model number is missing and create only the required catalog identity.',
        sourceHtml: 'output/manuals/proofs/owner-corrections-2026-09-10/CAT-01-v7/CAT-01-model-en-modelnummer-aanmaken-v7-draft.html',
        scripts: ['scripts/manuals/generate-owner-corrections.mjs', 'scripts/manuals/generate-guide-followups.mjs', 'scripts/manuals/generate-catalog-guide-review.mjs'],
        command: 'node scripts/manuals/generate-owner-corrections.mjs CAT-01',
    },
    'CAT-02': {
        purpose: 'Build a model specification from reusable attributes and expected components, including conflict handling.',
        sourceHtml: 'output/manuals/proofs/guide-followups-2026-09-08/CAT-02-v2/CAT-02-modelspecificatie-opbouwen-v2-draft.html',
        scripts: ['scripts/manuals/generate-guide-followups.mjs', 'scripts/manuals/generate-catalog-guide-review.mjs'],
        command: 'node scripts/manuals/generate-guide-followups.mjs CAT-02',
    },
    'CAT-03': {
        purpose: 'Create and maintain reusable attribute definitions, datatypes, limits, and enumeration options.',
        sourceHtml: 'output/manuals/proofs/guide-followups-2026-09-08/CAT-03-v2/CAT-03-attributen-beheren-v2-draft.html',
        scripts: ['scripts/manuals/generate-guide-followups.mjs', 'scripts/manuals/generate-catalog-guide-review.mjs'],
        command: 'node scripts/manuals/generate-guide-followups.mjs CAT-03',
    },
    'CAT-04': {
        purpose: 'Create and maintain reusable component definitions, expected subcomponents, contributions, and hierarchy rules.',
        sourceHtml: 'output/manuals/proofs/guide-followups-2026-09-08/CAT-04-v4/CAT-04-componentdefinities-beheren-v4-draft.html',
        scripts: ['scripts/manuals/generate-guide-followups.mjs', 'scripts/manuals/generate-catalog-guide-review.mjs'],
        command: 'node scripts/manuals/generate-guide-followups.mjs CAT-04',
    },
    'CAT-05': {
        purpose: 'Safely adjust one existing catalog object through independent routes for model data, specifications, attributes, component definitions, and lifecycle state.',
        sourceHtml: 'output/manuals/proofs/2026-09-15-planned-guides-v2/cat-05-v2/cat-05-v2.html',
        scripts: ['scripts/manuals/generate-planned-guide-v2.mjs'],
        command: 'set SNIPEIT_GUIDE_FILTER=CAT-05 && node scripts/manuals/generate-planned-guide-v2.mjs',
    },
    'CAT-06': {
        purpose: 'Check product data against authoritative sources without changing the catalog, and record unknown or conflicting facts for follow-up.',
        sourceHtml: 'output/manuals/proofs/2026-09-15-planned-guides-v2/cat-06-v2/cat-06-v2.html',
        scripts: ['scripts/manuals/generate-planned-guide-v2.mjs'],
        command: 'set SNIPEIT_GUIDE_FILTER=CAT-06 && node scripts/manuals/generate-planned-guide-v2.mjs',
    },
};

const expectedOrder = [
    'AC-01', 'AC-02', 'SC-01', 'AST-02', 'AST-03', 'AST-04', 'AST-05',
    'WF-01', 'WF-02', 'CMP-01', 'CMP-02', 'CMP-04', 'HELP-01',
    'USR-01', 'USR-02', 'USR-03', 'USR-04', 'USR-05',
    'CAT-00', 'CAT-01', 'CAT-02', 'CAT-03', 'CAT-04', 'CAT-05', 'CAT-06',
];
const selectedByCode = new Map([...selection.guides, ...trio].map((guide) => [guide.code, guide]));
assert.equal(selectedByCode.size, expectedOrder.length, 'Unexpected number of current guide codes');
const selected = expectedOrder.map((code) => {
    const guide = selectedByCode.get(code);
    assert.ok(guide, `Current-guide selection is missing ${code}`);
    return guide;
});

const packagedGuides = [];
for (const guide of selected) {
    const detail = details[guide.code];
    assert.ok(detail, `Missing package metadata for ${guide.code}`);
    const sourcePdf = guide.path
        ? path.join(repoRoot, ...guide.path.split('/'))
        : path.join(repoRoot, 'resources', 'manuals', 'operator-guides', 'drafts', guide.file);
    const sourceHtml = path.join(repoRoot, ...detail.sourceHtml.split('/'));
    const filename = path.basename(sourcePdf);
    const packagePdfPath = slash(path.join('guides', filename));
    const sourceHtmlPath = slash(path.join('source-html', filename.replace(/\.pdf$/i, '.html')));
    const specPath = slash(path.join('docs', 'manuals', 'operator-guides', 'guides', `${guide.code}.md`));
    assert.equal(sha256(sourcePdf), guide.sha256, `${guide.code} source PDF hash does not match its selection manifest`);
    copyFile(sourcePdf, path.join(temporaryRoot, packagePdfPath));
    copyFile(sourceHtml, path.join(temporaryRoot, sourceHtmlPath));
    packagedGuides.push({
        code: guide.code,
        title: guide.title,
        version: guide.version,
        pages: guide.pages,
        status: guide.reviewLabel ?? guide.status,
        ownerAccepted: guide.ownerAccepted ?? false,
        purpose: detail.purpose,
        sourcePdfPath: slash(path.relative(repoRoot, sourcePdf)),
        packagePdfPath,
        sha256: guide.sha256,
        bytes: fs.statSync(sourcePdf).size,
        sourceHtmlPath,
        sourceHtmlSha256: sha256(sourceHtml),
        specPath,
        generator: {
            command: detail.command,
            scripts: detail.scripts,
            note: 'The packaged source HTML is the exact final authoring snapshot; the listed scripts are the maintainable generation lineage.',
        },
    });
}

copyTree(path.join(repoRoot, 'scripts', 'manuals'), path.join(temporaryRoot, 'scripts', 'manuals'));
copyTree(
    path.join(repoRoot, 'resources', 'manuals', 'operator-guides', 'evidence'),
    path.join(temporaryRoot, 'resources', 'manuals', 'operator-guides', 'evidence'),
);
copyTree(
    path.join(repoRoot, 'resources', 'manuals', 'operator-guides', 'baselines'),
    path.join(temporaryRoot, 'resources', 'manuals', 'operator-guides', 'baselines'),
);
copyTree(
    path.join(repoRoot, 'docs', 'manuals', 'operator-guides'),
    path.join(temporaryRoot, 'docs', 'manuals', 'operator-guides'),
);
copyTree(
    path.join(repoRoot, 'output', 'manuals', 'proofs', 'owner-corrections-2026-09-10', 'inputs'),
    path.join(temporaryRoot, 'output', 'manuals', 'proofs', 'owner-corrections-2026-09-10', 'inputs'),
);
copyFile(selectionSource, path.join(temporaryRoot, 'provenance', 'current-guide-selection-v4.json'));
copyFile(draftManifestSource, path.join(temporaryRoot, 'provenance', 'drafts-manifest-2026-09-15.json'));

const manifest = {
    schemaVersion: 1,
    createdOn: '2026-09-15',
    title: 'Current Inbit Snipe-IT operator guide kit',
    selectionRule: 'The 22-guide current-guide-selection-v4 set from 2026-09-10, plus USR-05 v2, CAT-05 v2, and CAT-06 v2 from 2026-09-15.',
    statusWarning: 'Current means selected for continued use or review; it does not mean every guide is accepted or third-party approved.',
    guideCount: packagedGuides.length,
    pageCount: packagedGuides.reduce((sum, guide) => sum + guide.pages, 0),
    guides: packagedGuides,
    includedMaterials: [
        'Exact selected PDFs',
        'Exact final HTML authoring snapshots',
        'Manual generation, capture, retention, comparison, and verification scripts',
        'Shared JavaScript guide libraries and locked Node dependencies',
        'Canonical evidence and baselines',
        'Guide specifications, layout rules, decisions, registry, and reviews',
        'Frozen inputs needed by the owner-correction generator',
        'Source selection manifests and SHA-256 checksums',
    ],
};
fs.writeFileSync(path.join(temporaryRoot, 'manifest.json'), `${JSON.stringify(manifest, null, 2)}\n`);

const statusLabel = (guide) => guide.status === 'Geaccepteerd'
    ? 'Accepted by owner'
    : guide.status === 'Gereed voor gebruikersreview'
        ? 'Ready for user review'
        : guide.status === 'Unaccepted working draft'
            ? 'Working draft'
            : guide.status;
const rows = packagedGuides.map((guide) =>
    `| ${guide.code} | v${guide.version} | ${guide.title} | ${guide.pages} | ${statusLabel(guide)} | ${guide.purpose} | \`${guide.generator.command}\` |`,
).join('\n');
const readme = `# Current Operator Guide Kit

This folder is the portable working set for the Inbit Snipe-IT operator guides.
It contains one current PDF for each of the 25 active guide codes, together with
the exact final HTML behind each PDF, the generators and shared libraries, the
evidence and baselines, the guide specifications and reviews, and integrity
records.

"Current" is a selection label, not an approval claim. The first 22 guides come
from \`current-guide-selection-v4.json\` dated 2026-09-10. USR-05 v2, CAT-05 v2,
and CAT-06 v2 are the revised working drafts dated 2026-09-15. Read the status
column and the relevant review before publishing or training users.

## Start here

1. Read \`AGENTS.md\` before changing anything.
2. Read \`manifest.json\` for machine-readable paths, hashes, versions, page
   counts, source HTML, and generator lineage.
3. Run the integrity check from this folder:

   \`node scripts/manuals/verify-current-guide-kit.mjs --kit-root .\`

4. For a change, read the guide specification in
   \`docs/manuals/operator-guides/guides/<CODE>.md\` and its latest review.
5. Install the locked authoring dependencies only when generation is needed:

   \`cd scripts/manuals && npm ci\`

Chrome or Chromium, Poppler \`pdfinfo\`, and Poppler \`pdftoppm\` are required.
Use \`GUIDE_NODE_MODULES_ROOT\`, \`GUIDE_CHROME_PATH\`,
\`GUIDE_PDFINFO_PATH\`, or \`GUIDE_PDFTOPPM_PATH\` when the tools are supplied
outside their default locations.

## Folder map

| Path | Contents |
| --- | --- |
| \`guides/\` | The 25 exact current PDF artifacts. Treat these bytes as frozen. |
| \`source-html/\` | Exact final HTML snapshots matching the selected PDFs. |
| \`scripts/manuals/\` | Generators, patch layers, capture tools, shared libraries, and verification tools. |
| \`resources/manuals/operator-guides/evidence/\` | Canonical screenshots and their evidence manifest. |
| \`resources/manuals/operator-guides/baselines/\` | Baseline image inputs used by generators. |
| \`docs/manuals/operator-guides/\` | Scope specifications, layout rules, decisions, review records, and the historical registry. |
| \`output/manuals/proofs/owner-corrections-2026-09-10/inputs/\` | Frozen HTML inputs required by the owner-correction layer. |
| \`provenance/\` | The two source manifests used to select this kit. |
| \`CHECKSUMS.sha256\` | Checksums for every packaged file other than the checksum list itself. |

## What each guide describes

| Code | Version | Guide | Pages | State | Description | Current generator entry point |
| --- | ---: | --- | ---: | --- | --- | --- |
${rows}

## Reproducing and changing a guide

The PDF in \`guides/\` is the checksum-protected current artifact. The file in
\`source-html/\` is its exact final authoring snapshot and is the fastest way to
confirm or reproduce the layout. For example:

\`node scripts/manuals/render-guide-html.mjs --html source-html/<guide>.html --pdf output/pdf/<next-version>.pdf --pages <count> --render-dir output/rendered/<code>\`

That command refuses to write into \`guides/\`. A visible content or layout
change must get the next integer version and be implemented in the listed
generator or a documented new patch layer. Do not silently edit only the final
HTML and call that maintainable source. Regenerated PDFs can have different PDF
metadata and therefore a different checksum even when their visible layout is
unchanged; the selected PDF bytes remain the identity record.

The three 2026-09-15 guides share \`generate-planned-guide-v2.mjs\`. On
PowerShell, set \`$env:SNIPEIT_GUIDE_FILTER='USR-05'\` (or CAT-05/CAT-06) before
running it. The \`set ... &&\` commands in the table are portable command
descriptions from the Windows cmd convention; adapt environment-variable syntax
to the active shell.

Before promoting any next version, regenerate to \`output/pdf/\`, render every
page to PNG at 144 DPI or higher, inspect every page, run the package and guide
verifiers, update the specification and review record, and record new hashes.
Approval must be recorded separately; generation alone never changes status.
`;
fs.writeFileSync(path.join(temporaryRoot, 'README.md'), readme);

const agents = `# AGENTS.md - Current Operator Guide Kit

These instructions apply to this entire folder. They are written for an agent
that has no prior conversation or repository context.

## Mission and authority

Maintain or revise the Inbit Snipe-IT operator-guide PDFs without losing the
identity, provenance, visual language, or approval state of the current set.
You may inspect, generate, and validate guide artifacts. Do not claim acceptance,
publish, deploy, change the Snipe-IT application, capture production data, or
replace frozen artifacts unless the user explicitly asks for that action.

## Required initialization

Before acting:

1. Read \`README.md\` completely.
2. Read \`manifest.json\` and locate the exact guide code, current version,
   current PDF, final HTML snapshot, specification, generator command, and
   status.
3. Read the guide specification at
   \`docs/manuals/operator-guides/guides/<CODE>.md\`.
4. Read \`docs/manuals/operator-guides/layouts.md\`, \`decisions.md\`, and the
   latest review that names the guide/version. For CAT guides, also read
   \`catalog-guide-plan.md\`.
5. Run \`node scripts/manuals/verify-current-guide-kit.mjs --kit-root .\` and
   stop if a current PDF, HTML snapshot, or required input fails integrity.

## Sources of truth

- \`guides/\` contains the exact current PDF bytes. Never overwrite a file in
  this directory during ordinary authoring.
- \`manifest.json\` is the authoritative portable selection and identity map.
- \`source-html/\` contains exact final snapshots for reproduction and visual
  comparison. It is not a substitute for maintaining the generator chain.
- The guide specification owns scope, role, prerequisites, warnings, completion
  state, and related-guide boundaries.
- \`scripts/manuals/\` and \`scripts/manuals/lib/\` own generated content and
  layout behavior. The manifest lists the entry point and lineage for each guide.
- The evidence manifest owns screenshot IDs. Existing evidence files are
  immutable inputs; add a new ID/file for changed evidence.
- Human review records own acceptance. A newly generated file is a draft until
  a human decision explicitly changes that state.

## Change workflow

1. Restate the requested operator outcome and identify the single guide that
   owns it. Avoid duplicating instructions across guides; use a handoff reference.
2. Compare the request with the current PDF, exact HTML, specification, and
   neighboring guides.
3. Make the smallest maintainable change in the generator or a clearly named
   new patch layer. Preserve shared components and style tokens.
4. Increase the visible version by one for any content, evidence, layout, or
   pagination change. Never revise a frozen version in place.
5. Generate into \`output/pdf/\` and proofs into \`output/manuals/\`; never
   generate directly into \`guides/\` or \`source-html/\`.
6. Render every PDF page to PNG at 144 DPI or higher and visually inspect every
   page for clipping, overlap, unreadable crops, wrong navigation labels,
   contradictory choices, and incomplete completion criteria.
7. Run focused generator checks and the kit verifier. Compare page count,
   screenshots, visible version, title, and guide references with the manifest.
8. Add or update the guide specification and a dated review record. Record the
   predecessor, intentional differences, tests, exact SHA-256, and unresolved
   operator trials. Do not mark acceptance without an explicit human decision.

## Generation routes

- For byte-preserving review, use the PDF in \`guides/\`; no regeneration is
  needed.
- For a layout reproduction or controlled experiment, render the matching file
  from \`source-html/\` with \`render-guide-html.mjs\` to a new output path.
- For USR-05, CAT-05, or CAT-06, edit
  \`scripts/manuals/generate-planned-guide-v2.mjs\` and filter by guide code.
- For guides whose entry point is \`generate-guide-followups.mjs\`, update the
  appropriate base generator or \`lib/guide-followup-patches.mjs\`; create the
  next version instead of mutating the frozen round definition.
- For guides whose entry point is \`generate-owner-corrections.mjs\`, update the
  appropriate base/follow-up generator or add a new dated correction layer.
  The packaged \`output/.../inputs/\` files exist so the current layer can be
  reproduced without the original 500 MB proof archive.

Install dependencies with \`npm ci\` inside \`scripts/manuals\`. Chrome or
Chromium and Poppler's \`pdfinfo\`/\`pdftoppm\` must be available. Prefer the
environment variables documented in \`README.md\` over hard-coded machine paths.

## Content and design rules

- Write for an operator who does not know the page in advance. Every procedure
  must say where to start, what visible label to choose, what to enter, how to
  save, and how to confirm success.
- Make alternatives explicit with A/B or OR routes. Never require users to carry
  out all mutually exclusive choices.
- State scope and precedence precisely. For permissions, explain whether a rule
  is group-local or global and what wins when several groups apply.
- Use exact UI labels from evidence. If the interface and guide disagree, stop
  and resolve the evidence rather than guessing.
- Keep warnings beside the step that creates the risk. Completion text must be
  observable, not an assumption.
- Preserve A4 dimensions, family colors, guide chips, page numbering,
  continuation cues, screenshot-source IDs, and the established visual density.

## Safety and handoff

Never delete historical inputs, overwrite accepted/current PDFs, rewrite
evidence in place, or treat a checksum change as harmless. Keep experimental
output under \`output/\`. At handoff, report the new files, pages, checksums,
generation command, visual inspection performed, verification results, and any
review or operator trial still required.
`;
fs.writeFileSync(path.join(temporaryRoot, 'AGENTS.md'), agents);

const filesBelow = (directory) => fs.readdirSync(directory, { withFileTypes: true })
    .flatMap((entry) => entry.isDirectory()
        ? filesBelow(path.join(directory, entry.name))
        : [path.join(directory, entry.name)]);
const checksumLines = filesBelow(temporaryRoot)
    .filter((file) => path.basename(file) !== 'CHECKSUMS.sha256')
    .sort((left, right) => slash(path.relative(temporaryRoot, left)).localeCompare(slash(path.relative(temporaryRoot, right))))
    .map((file) => `${sha256(file)}  ${slash(path.relative(temporaryRoot, file))}`);
fs.writeFileSync(path.join(temporaryRoot, 'CHECKSUMS.sha256'), `${checksumLines.join('\n')}\n`);
fs.renameSync(temporaryRoot, targetRoot);

console.log(JSON.stringify({
    targetRoot,
    guides: manifest.guideCount,
    pages: manifest.pageCount,
    files: checksumLines.length + 1,
    bytes: filesBelow(targetRoot).reduce((sum, file) => sum + fs.statSync(file).size, 0),
}, null, 2));
