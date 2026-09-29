# Agent Session Addendum - 2026-09-29

## Starting Context

- Continued from `codex/refurbisher-followups-2026-09-22` in its isolated,
  clean worktree; the primary checkout's unrelated uncommitted work remains
  untouched.
- Reviewed the current progress log, fork notes, dashboard view, recent-assets
  partial, and focused dashboard coverage before changing behavior.
- Confirmed the local `dev.inbit` application and web containers still mount
  this feature worktree.

## Scope

- Render New Asset as a full dashboard tile beside the existing resource and
  Scan QR tiles.
- Preserve the existing asset-create authorization check so ordinary
  Refurbishers do not receive the tile.
- Remove the duplicate compact New Asset action from the recent-devices panel
  and cover the tile visibility and presentation with focused tests.
- Continue with production-readiness verification of account identity,
  status access/transition controls, workflow ordering, the post-create Print
  QR action, and attribute/model/component specification management.

## Safety Boundary

- Initial investigation did not authorize production changes. A later explicit
  owner instruction authorized commit, push, merge, and the production rollout;
  the deployment therefore used maintenance mode, a complete verified backup,
  immutable images, additive migrations, and post-change data-parity checks.
- Do not run destructive database commands. No destructive database command was
  used during the production rollout.
- Run PHPUnit only with explicit testing, SQLite, and in-memory database
  settings after clearing cached Laravel configuration.

## Outcome

- Added a green New Asset tile immediately after Assets, with the existing
  create permission controlling visibility.
- Removed the compact recent-device action and the duplicate empty-dashboard
  asset button; recent devices now stays absent until it has rows.
- `DashboardTest` passes all 9 tests / 42 assertions against in-memory SQLite.
  Cleared the local Blade view cache and confirmed the active `dev.inbit` app
  mount points at this worktree.
- Recorded phone preparation as an open operational TODO: disable translation
  behavior for the production site, pre-authorize camera access where the
  platform permits it, and verify Scan QR before operator handoff.
- Current regression rerun passed all 106 focused feature tests / 696
  assertions, syntax checks for 55 changed PHP files, Blade compilation, and
  whitespace validation. The full asset API directory passed 105 tests and
  failed four unchanged model-number image tests; that separate baseline image
  test-health issue is not in the refurbisher branch's changed paths.
- Added signed-in name/username details to the compact and standard account
  menus, and covered both header variants with feature tests.
- Replaced the post-create Print QR static-storage link with the existing
  authorized server-print endpoint. It renders the label server-side and sends
  it to the configured default CUPS/`lp` queue instead of opening a PDF.
- Applied the status configuration first on development and later on production
  after explicit rollout approval: QA
  Hold requires a note, Afgevoerd is a destroyed/archived status available to
  Supervisor/Admin, and Broken/Parts explicitly allows Supervisor View and
  Choose/use. Further Dutch names and matrix refinements remain editable and
  administrator-owned.
- Live role checks covered Admin, Supervisor, Senior Refurbisher, and
  Refurbisher. A QA Hold transition without a note was rejected, Supervisor
  could enter Afgevoerd but not leave it, and Admin had to provide both a
  reason and exact asset tag to restore the demo asset to Stand-by.
- Live profile reordering persisted and was restored. Attribute definition,
  enum option, model-number attribute add/remove, component-contribution, and
  derived-specification controls were exercised without saving catalogue
  changes. Workflow-item reorder persistence passed automated coverage; the
  browser harness could not reproduce the pointer-only drag reliably.
- Final production-relevant regression: 180 tests / 760 assertions passed on
  guarded in-memory SQLite. A corrective focused run for the printer path
  passes 26 tests / 153 assertions with a mocked CUPS dispatch. PHP syntax,
  Blade compilation, route registration, and whitespace checks pass.
- Fast-forwarded the feature branch and `master` through `0df107a50b`. Clean
  release images passed framework-patch, production-contract, runtime, and
  zero-HIGH/CRITICAL vulnerability gates before the server was changed.
- Backed up the live database, uploads, Redis session state, runtime settings,
  secrets, TLS material, and release state under maintenance mode. Verified the
  server archive and restricted off-host copy byte-for-byte before promotion.
- Promoted immutable app/web digests, ran the two pending additive migrations
  and role seeder once, then applied the approved QA Hold, Broken / Parts, and
  Afgevoerd status rules through application models. All baseline entity counts
  and zero-duplicate checks remained unchanged.
- All seven services are healthy; HTTPS health is 200, TLS is valid, queues are
  empty, and service error scans are clean. One controlled label for existing
  asset `INBIT-AA0010` completed as CUPS job `dymo330-7`, after which `dymo330`
  was enabled, idle, and had no pending jobs.
- Authenticated production role/UI smoke remains an owner-assisted acceptance
  step because the production browser had no saved credentials and correctly
  stopped at login. No temporary user, password-manager access, or bypass was
  introduced. See the linked production release record for full evidence.
- Full-suite infrastructure remains open: `phpunit.xml` lists a missing
  maintenance API directory, the shared container recreated a cached config
  during a broad run and triggered the intended test guard, and this worktree
  requires the example `APP_KEY` at the command boundary because no
  `.env.testing` is present.
- Added the owner-approved Cisco Aironet catalogue configuration to production
  without creating a physical asset. A verified native database backup was
  retained on-host and off-host first. The new model uses the exact primary
  model number `AIR-CAP2702I-E-K9`, reuses the existing 802.11ac and 1GbE RJ-45
  definitions for Wireless/PoE/AUX, and adds one RJ-45 Console Port definition
  using the existing connector attribute. No new attribute or custom fieldset
  was introduced. Post-write entity/duplicate checks and all service, HTTPS,
  and log checks passed.
- Returned exclusively to local development after the owner froze production.
  Numeric component attributes now expose additive versus distinct aggregation
  in the attribute settings UI. The migration and seed data set
  `ram_speed_mhz` to distinct, fixing the two-times-3200-MHz display while
  retaining summed RAM capacity. Mixed component speeds remain visible and
  warn operators, and changing an in-use aggregation mode requires lifecycle
  permission. The local migration is applied; 55 focused tests / 357
  assertions pass against guarded in-memory SQLite.
- Audited the saved production catalogue without reconnecting to production.
  Only `ram_speed_mhz` currently needs a mode change; the component, template,
  instance, and asset rows remain valid. An isolated MariaDB 11.4 restore of
  the verified off-host backup rehearsed migration, rollback, reapplication,
  and idempotency. It preserved counts and duplicate checks while changing the
  NUC result from `6400 MHz` / `16 GB` to `3200 MHz` / `16 GB`. The rollout and
  per-attribute decisions are recorded in
  `docs/plans/numeric-component-aggregation-production-plan-2026-09-29.md`.
