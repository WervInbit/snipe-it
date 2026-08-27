# Security Policy

## Current Support Status

This fork is an internal pre-V1 evaluation build. It does not yet have a generally supported release, public security-response SLA, or approved production image.

| Channel | Status |
| --- | --- |
| Current pre-V1 source | Best-effort security fixes; not approved for public release |
| Unversioned local Docker images | Unsupported; do not publish |
| Official Snipe-IT releases | Supported by upstream under upstream's policy, not by this fork |

Deployers must identify the exact Git commit in use. The inherited Snipe-IT 7.x/8.x support table does not describe this fork.

## Reporting A Vulnerability

Do not open a public issue with vulnerability details, production data, credentials, or exploit code.

Report the issue privately to the repository owner or maintainer through the organization's established private security channel. Include:

- the affected commit and deployment profile;
- the affected route, component, or workflow;
- the minimum steps needed to reproduce the issue safely;
- expected and observed behavior;
- impact and any known containment;
- whether the issue appears inherited from upstream or specific to this fork.

A dedicated security address and named response owner must be published before V1. Until then, no response-time commitment is made.

If the same defect exists in an unmodified official Snipe-IT release, follow the [official Snipe-IT security policy](https://snipeitapp.com/security) separately. Upstream does not own fork-specific changes or this fork's incident response.

## Handling Rules

- Do not test against production or production-derived data without explicit authorization.
- Do not upload executable proof-of-concept files to a running instance.
- Do not copy environment files, database dumps, certificates, tokens, or user data into an issue or pull request.
- Rotate any credential or key that may have entered a shared Docker image, artifact, or log.
- Security fixes require a regression test and must not disclose operational secrets in fixtures or output.

The current disposition and remaining release gates are recorded in the
[V1 release-readiness status](docs/v1-release-readiness-status-2026-08-25.md).
The [2026-07-21 audit](docs/v1-release-readiness-audit-2026-07-21.md) is retained
as the historical record of the findings as originally discovered.
