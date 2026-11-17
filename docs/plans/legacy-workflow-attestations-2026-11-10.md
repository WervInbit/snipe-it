# Legacy Workflow Item Attestations

Date: 2026-11-10

Status: implemented and freshly requalified on an isolated branch; not deployed.

## Purpose

Register pre-Snipe-IT laptops one at a time and identify exactly which
refurbishment steps were historically completed. Keep current Snipe-IT test
results empty until a real workflow is run.

## Product rules

- Web single-asset creation only; no bulk/import/clone/API intake.
- Collapsed by default and permission-gated.
- Applicable items are grouped by active workflow profile after model/model
  number selection. Nothing is selected by default.
- Selected items require a shared source/reason; original date is optional.
- The visible state is `Legacy accepted - not executed in Snipe-IT`, never
  `passed`.
- New/unselected/changed requirements stay pending. Per-item hashes prevent an
  unrelated new item from invalidating prior evidence.
- Real results are authoritative and permanently supersede matching evidence.
  A failure is never hidden, even if a run is later removed.
- Revocation keeps actor, time, reason, original evidence, and audits.
- QR generation occurs after the database transaction. Printing is a separate
  explicit post-save action.

## Architecture map

- Migration/model: `workflow_legacy_attestations`,
  `WorkflowLegacyAttestation`, and `workflow_runs.legacy_attestation_snapshot`.
- Applicability/hash: `WorkflowRunDefinitionService` and
  `DraftAssetWorkflowContextFactory`.
- Merge semantics: `WorkflowItemEvidenceService`.
- Writes/revocation/supersession: `LegacyWorkflowAttestationService`.
- Create/options UI: `AssetsController`, `StoreAssetRequest`,
  `LegacyWorkflowAttestationController`, and hardware Blade partials.
- Consumers: readiness, progression, web/agent run creation, active/history
  views, asset transformer, latest-summary API, and asset-list filter.

## Release boundary

The schema, intake UI, shared resolver, mixed execution, revocation/history,
and API/list states are one release. Do not deploy a partial subset. The full
non-LDAP SQLite gate and authenticated local web/Chrome journey passed on
2026-11-10. Before production: build immutable images, verify a fresh backup,
run the additive migration and `ProductionPermissionGroupSeeder`, then perform
readiness/audit smoke plus a separate explicit 25x25 print check. A rollback
must not drop populated evidence or run snapshots.

## Local qualification evidence

- Guarded suite: 2,281 passed / 11,251 assertions / 2 expected external-DB
  skips / 0 failed.
- Refreshed 2026-11-17 gate: 2,290 passed / 11,316 assertions / 4 expected
  skips (2 external-DB and 2 Windows symbolic-link cases) / 0 failed.
- Live local intake: Senior Refurbisher created asset 16 with two attestations,
  no intake run/results, correct source/actor snapshots, and no print action.
- Mixed execution: starting the applicable profile snapshotted both legacy
  items and created only uncovered results.
- Revocation: one attestation was retained as revoked with reason/audits and a
  pending result was materialized in the open mixed run.
- Chrome JavaScript: collapsed/open state, grouped AJAX options, profile
  selection controls, and stale context clearing passed.
