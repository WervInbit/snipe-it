# Session Init - 2026-07-23 V1 Release Sequencing

## Context

- The 2026-07-21 audit decision remains **not ready for V1**.
- The local production-like environment was reseeded and smoke-tested after the documented test-isolation incident.
- Test and Docker build-context containment fixes exist in the current uncommitted worktree.
- Operator-guide work is owned by another active session and remains outside this session's scope.

## Intended Sequence

1. Close the workflow report/readiness integrity blocker and executable workflow-photo upload blocker.
2. Close authorization, company-boundary, lifecycle, hierarchy, and operator-data preservation gaps with regression tests.
3. Triage production dependency advisories and establish safe migration, seeding, deployment, and upstream-baseline paths.
4. Prove a release candidate with isolated full-suite CI, seeded-role browser acceptance, an end-to-end refurbishment journey, and backup/restore and packaging checks.
5. Finalize versioning, changelog, compatibility, installation, upgrade, security, and known-limitations documentation.

## Safety Constraints

- Do not run destructive database commands.
- Run PHPUnit only with the repository's executable guard and explicit in-memory SQLite boundary.
- Preserve unrelated and operator-guide worktree changes.
