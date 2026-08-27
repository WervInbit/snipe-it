# Agent Addendum - 2026-08-27 Workflow Capability Review

## Scope

- Determine whether the current fork has configured workflows for Windows
  installation, secure device wiping, and diagnostic/test execution.
- Verify whether workflow items and profile membership support explicit order.
- Compare source configuration with the current development and populated
  rehearsal data without changing either environment.

## Safety Boundary

- This is a read-only implementation and runtime review.
- Do not create, reorder, run, or delete workflow records during inspection.

## Findings

- Both development and the populated rehearsal have the same four active
  profiles: Standard Diagnostics, Pre-Sale Check, Cleaning, and Shipping
  Laptop.
- Standard Diagnostics has 20 ordered pass/fail items selected at run time by
  asset category, resolved attributes, and installed/expected components.
- No Windows-installation or secure-device-wipe item/profile is currently
  seeded or stored in either inspected environment.
- The engine can represent those processes as reusable done/not-done or pass/
  fail items, with instructions, required/optional status, category/component
  applicability, notes, private photo evidence, and sale-readiness blocking.
- Workflow Items and Workflow Profile Items expose persisted drag-and-drop
  ordering. Each run copies item `sort_order`, so subsequent profile reorder
  does not rewrite historical runs.
- Ordering controls display/checklist order only. The update path accepts every
  result independently and does not lock a later item until earlier items are
  complete.
- Diagnostic tools, Windows setup, and storage erasure are external/manual
  actions. The current workflow records their outcome but does not execute or
  verify those tools automatically.

## Release-Control Follow-up

- `master` matches both the locally cached and live remote `origin/master` at
  `91a6db797496`, whose latest change is the portable operator-guide package.
- The repository is not clean and the implementation is not captured by the
  current commits: zero paths are staged, while 953 tracked paths differ and
  204 paths are untracked. About 1,148 dirty paths are outside manual-related
  paths, including application code, database migrations, routes, production
  container files, and automated tests.
- The populated-rehearsal qualification applies to the working-tree candidate
  and its recorded image digests, not to a reproducible checkout of current
  `master`. Do not deploy from `master` until the non-manual release content is
  reviewed and committed. No commit, fetch, push, or deployment was performed
  during this check.
