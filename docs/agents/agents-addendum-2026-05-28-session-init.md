# Agents Addendum - 2026-05-28 Session Init

## Startup Context
- Re-read `AGENTS.md`, recent `PROGRESS.md`, `docs/fork-notes.md`, and the 2026-05-26 session addendum.
- Current branch at initialization: `codex/component-hierarchy-sprints`.
- Current commit at initialization: `e72fc14b3`.
- Current date/time context: 2026-05-28, Europe/Amsterdam.

## Repository State
- The working tree is already dirty.
- Dirty tracked files include the workflow/profile implementation, workflow settings UI, readiness warning changes, translations, tests, seeders, docs, and earlier local Docker/upload placeholder changes.
- Untracked implementation files include `WorkflowProfileController`, `WorkflowProfile`, `WorkflowProfileItem`, workflow factories, the workflow migration, Workflow Profiles views, and `ManageWorkflowProfilesTest`.
- Older local-only artifacts remain present: `.env.before-prodclone.2026-04-30`, `.env.prodclone.prodkey`, `prodbak/`, and `storage/tmp-testtypes-reorder.js`.
- No unrelated changes were reverted, normalized, staged, or stashed during reinitialization.

## Implemented Workflow State
- Workflow/profile database foundation exists in `database/migrations/2026_05_26_120000_rename_tests_to_workflows_and_add_profiles.php`.
- Existing `TestType`, `TestRun`, `TestResult`, `TestResultPhoto`, and `TestAudit` compatibility classes point at workflow-named tables.
- Asset workflow start requires/selects an active profile, snapshots profile metadata, and creates ordered profile item results.
- Active workflow UI keeps the existing two-button notes/photos flow and can show task-style Done/Not Done labels.
- Workflow Profiles settings UI exists under `admin/workflow-profiles`.
- Ready for Sale/Sold warnings now evaluate sale-blocking workflow profiles while retaining the existing acknowledgment flow.
- Last recorded verification from 2026-05-26 passed against `.env.testing` SQLite:
- `ManageWorkflowProfilesTest`: `4` tests, `16` assertions.
- broader workflow/profile regression set: `42` tests, `197` assertions.

## Open Work
- Present/summary-style seed cleanup is not done. Remaining references include `battery_health_percent`, `webcam_present`, `usb_ports_summary`, `webcam`, `usb_ports`, `hdmi`, and `sale-photos-present`.
- Structured ports have not been implemented yet.
- No conversion has been made from present-style booleans into components or structured attributes.
- Local prod-clone database has not been backed up or migrated for workflow tables.
- Browser smoke testing against the local prod clone remains blocked until backup and migration are done.
- Two stale `ShowAssetTest` page assertions remain from the previous smoke pass: old QR-label copy and old no-run row expectation.

## Local DB State
- Local app DB preflight:
- `APP_ENV=local`
- `DB_CONNECTION=mysql`
- `DB_DATABASE=snipeit_prod_work`
- Pending migration: `2026_05_26_120000_rename_tests_to_workflows_and_add_profiles`.
- Destructive DB commands remain forbidden without explicit current approval and preflight.

## Ambiguities To Resolve
- Structured ports representation: dedicated repeatable table/model versus grouped values in the existing attribute system. Current recommendation is a dedicated repeatable model because ports are multi-row facts with connector/version/capability fields.
- Webcam handling: expected component if repair/replacement tracking matters, otherwise workflow check only. Current recommendation is workflow check only unless webcam parts will be tracked.
- Battery health handling: workflow result/note/measurement versus battery component attribute. Current recommendation is not a product attribute.
- HDMI/USB checks: keep as workflow items for functional testing, but remove product-spec booleans and summary dropdowns.
- Seed strategy: whether to clean definitions first and reenter device specs manually, or also migrate existing demo devices to structured port rows in the same block.

## Proposed Next Goal
- Implement the attribute/ports cleanup block before updating the local prod clone:
- remove clean-start present/summary attributes from seeds
- add structured ports foundation
- update model/spec display and seed data accordingly
- update tests
- then backup and migrate `snipeit_prod_work` and browser-smoke the workflow/profile pages

## Follow-up Planning Notes
- Ports should be represented as component definitions/instances, not a single dropdown summary. This lets a USB-C port live directly on an asset, under a motherboard/I/O board, or as another subcomponent-style tracked item.
- USB-C needs separate attributes for connector/protocol/capabilities: connector type, USB standard/generation, DisplayPort alt-mode support, Power Delivery support, Thunderbolt support/version, and optional PD wattage or DisplayPort version.
- Asset-level arbitrary component creation already exists through `hardware.components.register`, and child creation exists through `components.children.store`.
- Moving an existing installed asset-level component under another installed component is not exposed as a correction workflow. The model constraints allow it only when the component has no children and shares the same asset/root asset as the parent, but lifecycle methods currently clear `parent_component_instance_id`.
- Existing hierarchy tooling can preview/apply component-definition template conversions, but it does not reparent live component instances.
- Wi-Fi should remain a workflow item for testing. The product/catalog side should treat Wi-Fi as a physical component only when a replaceable module/card is being tracked; otherwise it is an integrated capability/spec attribute on the motherboard/asset/model.
- Follow-up decision: seed common component/attribute information, but also preserve the current model and model-number catalog as seedable data. The local work-copy currently has 11 asset models, 11 model numbers, 411 model-number attributes, 18 users, and 7 assets.
- Current component catalog data is not a useful source of truth yet: the work-copy has only 3 component definitions, 0 model-number component templates, and 1 subcomponent template.
- Component reparenting should stay within the same asset. Cross-asset movement remains a normal lifecycle move.
- Expected-template accounting is not required for the first correction workflow. A manual reparent can simply attach the component as a manual child; expected-slot matching can be added later if the missing-expected display becomes confusing.
- Workflow items/tests should not require product attributes. Existing code still supports attribute-linked workflow items for applicability and expected-value snapshots, but this should become optional rather than the reason to create present-style attributes.
- Production cleanup direction: do not migrate old tests/photos. Users should be migrated. Devices/assets will be manually recreated against the new seedable model/model-number/component/attribute foundation.
- Full clean-start mapping investigation created at `docs/plans/catalog-clean-start-mapping-2026-05-28.md`.
- Mapping conclusion: seed the current 11 real models/model numbers, do not seed the three current example component definitions, and convert present/summary-style attributes into workflow items plus expected components/ports.
- System caveat: current effective attribute resolution only rolls up numeric component attributes marked `resolves_to_spec`; non-numeric component details stay on component views unless duplicated as model-number attributes or the resolver/display is enhanced.
- Browser check reached `https://dev.inbit` and the login page. Internal component/workflow UI could not be verified without an authenticated browser session, and the work-copy workflow migration is still pending.
- Follow-up decisions accepted: Surface Type Cover is a sale accessory/workflow item; Pixel 8 Pro seeds with Google as manufacturer; phone cameras use generic expected camera components with `camera_position`, `camera_role`, and `camera_megapixels`; HP ProBook 430 G3 battery capacity is deferred until actual battery scan/health work.
- End-of-session handoff written to `docs/agents/session-handoff-2026-05-28.md`.
