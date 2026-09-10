# Accepted Operator Guides - Package v1

Snapshot: 2026-09-10. Start here before creating, editing or regenerating a guide.

The `pdf/` folder contains **16 guide codes / 27 pages**, using the latest
recorded accepted version of each guide. Accepted means **Internal review
candidate**, including the owner's clearance for review by other users. No
third-party approval or completed user trial is implied.

`history/pdf/` preserves seven older accepted PDFs for comparison and rollback.
Filenames, version numbers, printed draft labels and PDF bytes are unchanged.
The separate catalogue prototype and unaccepted guides are outside this set.

## Folder Map

- [manifest.json](manifest.json): exact selection, hashes, source HTML,
  acceptance evidence and highest known versions at this snapshot.
- `pdf/`: latest accepted PDF for each included guide; use these for review.
- `history/pdf/`: older accepted PDFs, kept out of the current review folder.
- `source/html/`: self-contained, editable final HTML for all 16 current guides.
- `scripts/`: portable regeneration and integrity/comparison tools, plus
  pinned Node and Python dependency lists.
- `source/repository/`: the original guide rules, specifications, review
  records, generators, shared libraries, canonical screenshots and baseline
  SVGs, preserved in their repository layout.
- `work/`: created by the renderer for new proofs and revisions. These files
  are never part of the accepted snapshot.

## Included Current Versions

| Guide | Version | Pages | Accepted PDF |
| --- | --- | ---: | --- |
| AC-01 | v9 | 1 | [AC-01-login-v9-draft.pdf](pdf/AC-01-login-v9-draft.pdf) |
| AC-02 | v4 | 1 | [ac-02-eigen-wachtwoord-wijzigen-v4-draft.pdf](pdf/ac-02-eigen-wachtwoord-wijzigen-v4-draft.pdf) |
| SC-01 | v12 | 1 | [SC-01-asset-vinden-en-openen-v12-draft.pdf](pdf/SC-01-asset-vinden-en-openen-v12-draft.pdf) |
| AST-02 | v8 | 1 | [AST-02-refurbishment-route-v8-draft.pdf](pdf/AST-02-refurbishment-route-v8-draft.pdf) |
| AST-03 | v15 | 2 | [AST-03-asset-registreren-en-labelen-v15-draft.pdf](pdf/AST-03-asset-registreren-en-labelen-v15-draft.pdf) |
| AST-04 | v6 | 1 | [AST-04-complete-handoff-v6-draft.pdf](pdf/AST-04-complete-handoff-v6-draft.pdf) |
| WF-01 | v13 | 1 | [WF-01-workflow-starten-v13-draft.pdf](pdf/WF-01-workflow-starten-v13-draft.pdf) |
| WF-02 | v10 | 2 | [WF-02-workflow-uitvoeren-en-afronden-v10.pdf](pdf/WF-02-workflow-uitvoeren-en-afronden-v10.pdf) |
| CMP-01 | v4 | 1 | [CMP-01-bestaand-component-plaatsen-v4.pdf](pdf/CMP-01-bestaand-component-plaatsen-v4.pdf) |
| CMP-02 | v6 | 1 | [CMP-02-register-install-v6-draft.pdf](pdf/CMP-02-register-install-v6-draft.pdf) |
| CMP-04 | v7 | 1 | [CMP-04-component-to-tray-v7-draft.pdf](pdf/CMP-04-component-to-tray-v7-draft.pdf) |
| USR-01 | v13 | 1 | [usr-01-gebruiker-toevoegen-v13-draft.pdf](pdf/usr-01-gebruiker-toevoegen-v13-draft.pdf) |
| USR-02 | v11 | 1 | [usr-02-rol-en-rechten-wijzigen-v11-draft.pdf](pdf/usr-02-rol-en-rechten-wijzigen-v11-draft.pdf) |
| USR-04 | v6 | 1 | [USR-04-gebruiker-uitschakelen-of-herstellen-v6-draft.pdf](pdf/USR-04-gebruiker-uitschakelen-of-herstellen-v6-draft.pdf) |
| CAT-00 | v11 | 6 | [CAT-00-catalogus-begrijpen-v11-draft.pdf](pdf/CAT-00-catalogus-begrijpen-v11-draft.pdf) |
| CAT-01 | v7 | 5 | [CAT-01-model-en-modelnummer-aanmaken-v7-draft.pdf](pdf/CAT-01-model-en-modelnummer-aanmaken-v7-draft.pdf) |

WF-02 v10 and CMP-01 v4 are deliberately selected: newer v12/v6 proposals
exist but do not have recorded acceptance. Their existing wording, screenshots
and references remain as accepted. Do not silently replace them with a newer
file based only on its number.

AST-05, HELP-01, USR-03, CAT-02, CAT-03 and CAT-04 have no exact accepted
version in the consulted records. They are still dependencies of some guides;
the source documents describe their draft/planned state. USR-05, CAT-05 and
CAT-06 are planned. Creating this folder does not approve any of them.

## Verify The Package

Run from this folder, using Node.js 22 or later:

```powershell
node scripts/verify.mjs
```

This checks the manifest, every packaged source/tool/document, all current
PDFs and all historical PDFs. Keep this snapshot intact. Work on copied HTML
under `work/`; after a later approval, create a new versioned package.
Keep `.gitattributes` with the folder so Git preserves the recorded file bytes.

## Regenerate Accepted Guides

The portable renderer uses final HTML with embedded screenshots. It does not
need Laravel, a database, a live account, network access or this checkout.

1. Install the pinned dependencies once. Node 22+, Chrome/Chromium and Arial
   are required. The Windows tests use system Chrome and Arial.

```powershell
cd scripts
npm ci
cd ..
```

If system Chrome is unavailable, run `npx playwright install chromium` from
`scripts/`. You can explicitly set `GUIDE_CHROME_PATH` to a browser executable.
In Codex, load the bundled workspace dependencies and set
`GUIDE_NODE_MODULES_ROOT` to its Node module directory instead of installing.
Use the bundled Python executable for the comparison step when available.

2. Rebuild all accepted guides, or one guide, into a new named run:

```powershell
node scripts/render.mjs --all --run baseline-check
node scripts/render.mjs --guide SC-01 --run sc01-check
```

Output is `work/<run>/pdf/`. Existing run directories are refused. The
accepted PDFs and their HTML sources cannot be overwritten by this command.
The render report records the browser version, source, output and page count.
All resource requests outside the supplied HTML are blocked.

3. Compare rendered pages with the accepted PDFs. Install Poppler's
`pdftoppm` on PATH, then use Python 3.10+ with the pinned requirements:

```powershell
python -m pip install -r scripts/requirements.txt
python scripts/compare.py work/baseline-check
```

Set `GUIDE_PDFTOPPM_PATH` if Poppler is not on PATH. The comparison checks
page counts and pixels at 96 dpi and records any differences. Inspect changed
pages at 144 dpi and at actual A4 size before a new version is reviewed.
Font/browser changes can affect layout. Regenerated PDF hashes can differ
because of metadata even when pages match. To restore exact accepted bytes,
copy the original from `pdf/` or `history/pdf/` and verify its manifest hash.

## Create A New Revision Or Guide

1. Read the [guide system](source/repository/docs/manuals/operator-guides/system.md),
   [components](source/repository/docs/manuals/operator-guides/components.md),
   [layouts](source/repository/docs/manuals/operator-guides/layouts.md),
   [maintenance rules](source/repository/docs/manuals/operator-guides/maintenance.md)
   and the relevant specification in `source/repository/docs/manuals/operator-guides/guides/`.
   The root manifest owns this package's exact accepted selection; original
   documents contain historical notes and newer unaccepted proposals too.
2. Copy the chosen accepted HTML into `work/CODE-vN/`. For a new guide,
   use an appropriate accepted layout as a starting point and check that its
   new code is free in the registry. Keep the established filename stem.
3. Pick an integer above `highestKnownVersions[CODE]` in the manifest and
   check the current repository for versions created after this snapshot.
   For example, a WF-02 revision must follow the existing v12 proposal even
   though v10 is the accepted PDF. Do not reuse a rejected version number.
4. Update the visible code/title, version/date and exact content in the copied
   HTML. A re-render does not automatically change its printed version or
   review label. New or visibly changed content needs a new owner decision.
5. Render the new HTML, for example after preparing SC-01 v13:

```powershell
node scripts/render.mjs --html work/SC-01-v13/SC-01-asset-vinden-en-openen-v13-draft.html --run sc01-v13-proof
```

This command rejects filenames that reuse known version numbers. Review the
new proof against its accepted predecessor. The exact-equality comparison
script is intended for regeneration, not for deliberately changed drafts.

Preserve screenshots, hints, bottom help, completion checks and useful page
width. Keep each task's layout; do not force the rejected sparse generic grid.
Write predictable steps for users who do not know the system. Use family
color, marker, code and title for handoffs. Check the whole badge/crop/control
boundary, not just its centre, after any screenshot or spacing change.
WF-01 keeps profile selection at 2A and starting at 3A. USR-04 is disable-login
only. Testing completed is a future QA status preference, not a live status
implemented by these files. The catalogue diagram prototype is still separate.

Update the guide specification, exact review record, affected incoming
references, registry and decision ledger in the main repository. Original
generator sources are retained under `source/repository/scripts/manuals/` for
larger changes; some expect the full repository and historical paths. They
are provenance/authoring references, not the portable entry points above.
Links in those original documents retain repository-relative context; app
code and unaccepted artifact links may require the original repository.

After approval, preserve the exact new PDF and hash, then build the next kit
version. Do not change this kit's acceptance records to make a proof accepted.

## Validation And Provenance

Assembly verifies source and copied PDF hashes and records all package files.
The regeneration test is recorded in [VALIDATION.md](VALIDATION.md).
Acceptance sources are linked per guide in the manifest. Exact full-set
selection is based on the fourteen owner-cleared current versions plus the
earlier WF-02/CMP-01 acceptances, not on filename dates or generation labels.
