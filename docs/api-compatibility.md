# API and Compatibility Contract

This document belongs to the Inbit Device Refurbishment Platform fork. The
project derives from [Snipe-IT](https://github.com/grokability/snipe-it), but
official Snipe-IT documentation is not the contract for this fork.

## Release status

The current application is a pre-V1 development build. The `/api/v1` prefix is
retained for route compatibility; it does not promise a stable V1 schema until
an Inbit V1 release is approved and tagged.

API consumers must pin the exact application build or image digest they tested.
Before upgrading, compare the tagged fork notes and rerun integration tests
against the candidate deployment.

## Discovering the installed API

- The API base path is `/api/v1`.
- Personal bearer tokens are created from the authenticated account API page.
- `php artisan route:list --path=api/v1` is the exhaustive endpoint inventory
  for the installed build.
- `routes/api.php`, the relevant request validators, policies, controllers, and
  transformers define request, authorization, and response behavior for a
  tagged source revision.
- `GET /api/v1/version` identifies the running application build. Record that
  response with client test evidence.

This project does not currently publish an exhaustive OpenAPI schema. Do not
infer support for an endpoint or field solely because it appears in official
Snipe-IT documentation.

## Important compatibility boundaries

- Legacy asset checkout, checkin, audit, request, and maintenance mutation
  workflows are not part of the refurbishment contract. Disabled mutation
  paths may be absent or return a controlled error.
- Tracked components use component-instance and dedicated lifecycle operations.
  Generic metadata updates do not bypass install, move, verify, condition,
  hierarchy, sale, return, or destruction rules.
- Asset readiness depends on current workflow, model-number, expected-component,
  attached-component, and lifecycle context; a historical pass is not a durable
  readiness guarantee.
- Authorization and company visibility are enforced per endpoint. A token does
  not grant permissions beyond its owning user.
- Some legacy names remain in payloads or PHP classes only as explicit
  compatibility aliases. Their presence is not a promise that the corresponding
  upstream workflow remains supported.

## Imports, statuses, labels, and configuration

- The import mapping screen and the validators in the installed build define
  accepted CSV fields. Validate representative imports in a disposable
  environment before processing operational data. Example files are bundled
  under `sample_csvs/`; they are starting points, not a substitute for checking
  the current mapping screen and validators.
- Status labels carry stable lifecycle semantics in addition to editable display
  names. Renaming a label does not change those semantics, and changing a status
  can trigger assignment or component cleanup rules.
- Barcode and label values must satisfy the selected barcode format. Test the
  generated output with the exact printer, scanner, label dimensions, and
  deployment URL used in production.
- `.env.example` documents application settings for development.
  `docs/production-deployment.md` is the production deployment and upgrade
  runbook. Upstream install and configuration guides do not apply unchanged.

## Reporting problems

Report fork defects and security concerns through this repository's documented
maintainer channels in `CONTRIBUTING.md` and `SECURITY.md`. Do not send
fork-specific issues or vulnerabilities to the upstream Snipe-IT project.
