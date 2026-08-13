# 2026-06-30 Session Init

## Startup Context
- Reinitialized for continued operator-guide creation/planning feedback.
- Current checkout is `master` at `51208bff3` (`Merge pull request #66 from WervInbit/codex/inactivity-timeout-serial-ocr-plan`).
- Reviewed `AGENTS.md`, recent `PROGRESS.md`, `docs/fork-notes.md`, `docs/manuals/operator-guide-planning.md`, `docs/manuals/affinity-development-blocks-2026-06-25.md`, and the 2026-06-25 guide session addendum.
- No fetch, pull, branch switch, Docker, database, browser, Laravel, PHPUnit, migration, seeder, or asset-build command was run during reinitialization.

## Local Workspace State
- The working tree was dirty before this session with guide planning/session files, `docs/manuals/operator-guide-planning.md`, upload placeholder `.gitignore` line-ending changes under `public/uploads/**`, and local untracked runtime/backups.
- Untracked local artifacts include `.env.before-prodclone.2026-04-30`, `.env.prodclone.prodkey`, `docs/agents/agents-addendum-2026-06-25-session-init.md`, `docs/agents/session-handoff-2026-06-25-affinity-guide-setup.md`, `docs/manuals/affinity-development-blocks-2026-06-25.md`, and `prodbak/`.
- Do not revert or clean those unrelated local files unless explicitly requested.

## Guide Planning Context
- `docs/manuals/operator-guide-planning.md` remains the broad brainstorming and decision-tracking document. Treat guide codes, color choices, wording, grouping, and workflow recommendations as draft unless explicitly marked `Final`.
- `docs/manuals/affinity-development-blocks-2026-06-25.md` remains the smaller implementation block spec for Affinity work. It defines reusable blocks `B00` through `B13`, page plans, screenshot mappings, missing screenshot backlog, and a pass-by-pass Computer Use build queue.
- First floor/refurbisher pass remains Dutch-oriented, with guide-code/reference chips and QR placeholders as draft directions.

## Existing Artifacts
- AST-02 proof/template:
  - `C:\Users\Gebruiker\Documents\snipe-it manuals\AST-02 Affinity proof template.pdf`
  - `C:\Users\Gebruiker\Documents\snipe-it manuals\AST-02 Affinity proof template.af`
- Screenshot source folder:
  - `C:\Users\Gebruiker\Documents\snipe-it manuals\screenshot-source\2026-06-25-blocks`
- Draft guide PDFs:
  - `C:\Users\Gebruiker\Documents\snipe-it manuals\draft-guides\2026-06-25-login-asset-test\AC-01-login-draft.pdf`
  - `C:\Users\Gebruiker\Documents\snipe-it manuals\draft-guides\2026-06-25-login-asset-test\AST-01-open-existing-asset-draft.pdf`
  - `C:\Users\Gebruiker\Documents\snipe-it manuals\draft-guides\2026-06-25-login-asset-test\WF-01-test-uitvoeren-draft.pdf`
  - `C:\Users\Gebruiker\Documents\snipe-it manuals\draft-guides\2026-06-25-login-asset-test\operator-guides-login-asset-test-draft.pdf`

## Ready For Feedback
- The likely next step is to record user feedback against the guide plan/block spec, then update the relevant planning docs or regenerate draft guide artifacts as needed.

## Broad Reinitialization Update
- Later in the same session, fetched remote refs while staying on `master`; local `HEAD` remains `51208bff3` and matches `origin/master`.
- Reread `AGENTS.md`, `TODO.md`, `docs/fork-notes.md`, recent `PROGRESS.md`, this addendum, `docs/manuals/operator-guide-feedback-replan-2026-06-30.md`, and `docs/manuals/affinity-development-blocks-2026-06-25.md`.
- Next work direction is documentation alignment/research against the ChatGPT website. Since that source is external and may have changed, use live browsing/source capture when the actual alignment work starts.

## Feedback Replan
- Captured and sorted the first design feedback in `docs/manuals/operator-guide-feedback-replan-2026-06-30.md`.
- Main design shift: next drafts should be step-first, with one focused screenshot crop per action step, instead of screenshot galleries before the steps.
- Keep from the current/old direction: guide codes, related-guide chips, version/source footer, larger latest-guide QR, compact role/needed context, finished-when box, and the older right-side help information for no account, forgotten password, and missing phone/device.
- Change from the current PDF drafts: make `AC-01 Login` compact rather than full-page by default, shrink the role/needed/stop strip, move most stop warnings inline to the relevant step, and put the `AST-01` mismatch warning at the title/model/device check.
- Product dependency surfaced: serial-number search is desired in the guide flow but is not supported yet, so final guide copy should not present it as current behavior until implemented.
- Follow-up clarification: compacting `AC-01 Login` is a preference, not a hard requirement. Screenshot crops should land between the too-small initial example and the too-large current `AC-01`/`AST-01` proof screenshots; printed readability decides.
