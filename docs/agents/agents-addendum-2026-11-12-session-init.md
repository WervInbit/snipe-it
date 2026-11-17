# Agent Addendum - 2026-11-12 - Status And Backlog Audit

## Session boundary

- Worktree: `C:\Users\Gebruiker\.codex\worktrees\legacy-workflow-attestations\snipe-it-fork`.
- Branch: `codex/legacy-workflow-attestations`; HEAD and `origin/master` remain
  `c539b9cd18`.
- This was a read-oriented reconciliation of implementation, test evidence,
  deployment records, and active backlog. Application code and production were
  not changed.

## Current implementation state

- The legacy workflow evidence slice is fully implemented in the worktree but
  remains uncommitted. It includes its additive migration, permission changes,
  domain services, intake UI, readiness/progression/API/list integration,
  English/Dutch strings, documentation, and feature coverage.
- Latest qualification is unchanged from 2026-11-10: 2,281 guarded non-LDAP
  SQLite tests, 11,251 assertions, two expected external-MariaDB skips, zero
  failures, authenticated local create/start/revoke coverage, and a real Chrome
  AJAX interaction pass.
- Docker Desktop was unavailable during this audit. No local runtime or DB
  state was changed; current container/migration counts must be rechecked after
  Docker restarts.

## Backlog reconciliation

- Closed stale TODO claims for the completed Snipe-IT host move, production
  status matrix, and the deployed RAM-speed distinct-aggregation fix.
- Kept CUPS relocation open and separated it from the already-completed
  application-host move. Current queue owners/endpoints must be rediscovered
  before acting because the printer topology has changed since the original
  migration plan.
- Added missing operator issues: intermittent mobile hardware-page scrolling,
  slow History feedback, stable printer location labels, and bounded Zebra
  ZD421 media/alignment qualification.

## Immediate next decision

Choose between:

1. release the already-qualified legacy-evidence slice (review, commit,
   immutable build, verified backup, additive migration, permission seeder,
   production smoke); or
2. first fix the phone scrolling/History feedback issues in a separate branch
   so they do not expand the legacy-evidence release.

The safer default is option 1 with strict scope separation, followed by a new
UX branch.
## Manual Guide Workstream Notes

## Operator Guide Status Review After Native Conversion Pause

- Scope: START-05/NEXT-08 progress review requested by the owner. Computer Use
  remains paused after physical Escape on November10; this request does not
  resume Affinity control. NUC access remains excluded.
- Checkout: C:/dev/snipe-it-fork, master at a766abedee, ahead1/behind17 with
  extensive existing shared changes. Preserve other agents' work. Status/file
  inspection only; current application mounts/image/database and desktop state
  were not inspected because no runtime/GUI operation is part of this review.
- Read checkpoint/backlog, recent progress/fork notes and guide/native handoffs.
  Rechecked actual conversion markers, reports and nonempty native/PDF/package
  files rather than trusting stale prose. USR-01 completed before the pause:
  19 guides/58 pages converted. All19 imported-source hashes and native reopen
  text/image/group counts match; no conversion errors. CAT-06 alone has a
  recorded text-overflow warning and a clipped HP address in its PDF.
- Six native conversions remain: USR-02 v14, USR-03 v7, USR-04 v8, USR-05 v5,
  WF-01 v17 and WF-02 v15;10 pages. Their guide sources already exist.
- Rechecked all712 frozen preparation hashes: zero changes. Saved evidence:
  output/manuals/affinity-native-2026-11-10/qa/status-2026-11-12.json.
  The independent PDF audit last covered18 guides/56 pages. It needs rerunning
  for USR-01; the newer exact input-name fix prevents the separate CAT-06 repair
  candidate being mistaken for its original input by a wildcard search.
- Reconciled the visual-review ledger with pages actually viewed on November10:
  44 pages viewed, with CAT-06 page2 explicitly failing because the address is
  clipped. This adds no new visual review or reader/paper acceptance today.
- CAT-06's separate frame-size correction and native repair runner are prepared
  but have not run. Screenshot replacement, representative catalogue diagram/
  independent-route edits, full68-page review, shared native template and final
  portable assembly/relocation remain open. IncludeImages also copied Arial
  files, so final assembly must filter actual package contents.
- Existing intake/refurbishment and password print PDFs remain on disk. Both
  former loopback review endpoints (53233/63986) are unavailable on today's
  read-only HTTP checks; they must be served again for browser review.
- Updated the maintained handoff/backlog progress so a continuation starts at
  USR-02 through runner/current-task.js -> convert-next-guide.js after an explicit
  owner continue. Native25 PDF operation marker already ran; do not repeat it.
  No guide content, application, database, printer, deployment, commit or push.

## Guide Skill Build And Resumed Affinity Work

- Owner authorizes building/refining a reusable guide skill from current rules
  and feedback, testing representative guides before continuing the six native
  conversions, and using this laptop's Affinity. Pause app input on interruption
  and wait for continue. The cleaned NUC remains excluded.
- START-05/NEXT-08; same master checkout at a766abedee with shared existing
  changes. No application/runtime/database operations are needed for this work.
- Plan: keep a maintained skill source with the manuals, install its exact copy
  in the personal skills directory, test reader interpretation and native/PDF
  preservation separately, then resume one-guide native checkpoints. Preserve
  frozen inputs and existing native edits. Record comparisons and actual tests
  in a dated review; structural skill validation is not usability evidence.
- Built and installed snipeit-operator-guides from the maintained manuals source.
  Structural validation passes. Applied retrospective rendered comparisons to
  AST-03/SC-01/AC-02. The reusable exact IDML/PDF checker passes these and flags
  CAT-06's real missing URL fragment. Tests are not blind generation/reader use.
- Executed CAT-06 frame repair: complete URL, zero warnings, unchanged text/font
  sizes/images, pages1/3 pixel-identical and only missing suffix changes page2.
  Retained original marker/output; reconciled selected repair path/hash in the
  strict conversion runner after its guard initially stopped the continuation.
- Converted the six remaining guides individually. All25 guides/68 pages now
  have native .af, derived PDF and image package. Full native audit: no failed
  input/count/A4/layer checks, missing source fragments or warnings/errors.
  Practical remaining editing/template/visual/portable gates still open.
- Later in the same authorized block: image replacement, CAT-00 diagram text/
  independent connector and CAT-05 route-step movement all pass actual native
  save/reopen. Shared scaffold is saved/reopened with one A4/master,18 frames,
  23 declared styles and no overflow. Initial SDK export name and longer
  placeholder-frame defects were corrected from actual errors/checks.
- All68 selected pages have rendered review across November10/12. CAT-04 v8
  adds its two missing Add controls at first use while preserving field views;
  USR-04 v9 fixes the caption's checked/unchecked mismatch. Updated skill cases
  and retained earlier markers/artifacts. These are not owner acceptances.
- Assembled output/manuals/affinity-guide-handoff-2026-11-12 with all25 native/
  PDF/image packages, current docs/skill, retained full source preparation and
  scaffold. Four representative relocated packages reproduce23 pages pixel-
  identically with full-image hashes. All25 pairs find2580 story fragments;
  all100 package images and712 frozen source hashes match; no font binaries.
  The240 review links and loopback review/PDF access pass. Same-machine proof
  leaves original sources available; fresh-device/operator/paper trials stay
  open along with recorded live-target/status-choice evidence gaps.
- Skill validator and installation/update/independent-edit protection tests
  pass. ZIP sealing is the final filesystem step. Manual/tooling scope only;
  application tests not relevant, no runtime/DB/printer/deployment/commit/push.
- ZIP sealing completed:1296 files,278948279 bytes, archive integrity pass,
  SHA256196807dc8c2166b174a2709047a52f3d698c3a6e1daa1adce023b7977378b66e.
  The current review has243 links, including exact before-correction PDFs,
  and is served at http://127.0.0.1:60158/REVIEW.html for12hours from startup.
  Computer Use finished; current-task.js again points to the completed next-
  guide runner. Its final preflight confirms all25 markers match. No remaining
  conversion or scheduled app input. Fresh-device/live-target/reader/A4 and
  exact owner acceptance remain open. Focused diff whitespace check passes.

## Shared Manual Folder Investigation And Proposal

- START-05/NEXT-08, same root master a766abedee and shared dirty checkout.
  Scope: check delivered native/PDF equivalence and suggest a simpler share
  for users and future models. No runtime/database target is needed for this
  filesystem/documentation investigation; no Computer Use or deployment.
- All25 delivered native/PDF hashes agree with the handoff manifest. Exporter
  source confirms save/close/reopen before PDF export. Four existing copied
  packages produced23 pixel-identical pages, while original sources remained
  available. Preserve this proof's source-unavailable/fresh-device boundary.
- Existing11-folder/1296-file package has no root agent entry and intermixes
  production/history with reading/editing. Suggested two flat descriptive
  user folders, local index, root AGENTS.md and a single _Beheer entry.
  Current rules/specifications, searchable guide text, full original images,
  skill/procedures, status/version/hash mapping and relevant evidence must be
  navigable through relative links; historical paths/status are not current
  operating instructions. Proposal is in the maintained guide handoff.
- No files rearranged or sealed bundle/ZIP changed. A future implementation
  must qualify embedded resources or explicit managed relinking and test with
  original sources unavailable before describing the flat native set as portable.

## Shared Handleidingen Delivery Implementation

- Owner approves implementation and chooses manual/handleiding terminology.
  START-05/NEXT-08, same root master a766abedee/shared dirty checkout.
- Root Handleidingen is a fresh output; preserve existing sealed bundle and
  native masters. Use descriptive paired filenames, one current version per
  manual, a local HTML task index, root AGENTS.md and one _Beheer area.
- Establish image-resource portability on isolated native copies; retain full
  originals/provenance and independently verify exported appearance. Current
  app source, database and printers are outside this task. Respect laptop
  Computer Use interruptions and do not reconnect the cleaned NUC.

### Shared Handleidingen Delivery Outcomes

- New root Handleidingen delivery:25 descriptive native/PDF pairs, offline
  START HIER.html, model-neutral AGENTS.md, handleidingen.json and current text,
  specifications/rules/owner feedback, original images, native image maps,
  relevant dated app contracts, tools/evidence and archived original handoff.
- Native source pixels/crops/counts survive all25 embedded copies. Four files
  open alone, with no linked resources and working recrop/undo;23 page exports
  match. All25 print PDFs remain unchanged. Generic native-export comparison
  records67 exact pages and one bounded AC-01 phone-image colour variation;
  native Resource Manager embedding produces the same result. No hidden fuzzy
  pass: the exact pair is separately qualified and the strict result retained.
- First integrity/index checks,1311 Markdown file links/approximate fragments
  and six offline cases executing the actual index filter pass. Browser file
  protocol policy blocks visual preview; no bypass attempted. Same-device image
  independence is proven, fresh-device/font/operator/paper acceptance is separate.
- Owned test document closed; prior open proof documents left intact. No further
  app input is scheduled. Sealed prior bundle/source hashes remain unchanged.
  Native generation after interruption resumes only on explicit user continue.
- Final archive sealed:583 files,383665275 bytes, integrity pass, direct root
  entries rather than an extra enclosing folder. SHA256
  d62409d7bde326951ea241a6ff4b0e9c5071967d1197ecf4242f7966aab5119a.
  Delivery folder remains C:/dev/snipe-it-fork/Handleidingen; no shared destination
  was configured or permission changed.
