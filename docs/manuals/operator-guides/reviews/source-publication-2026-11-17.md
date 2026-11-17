# Manual Source Publication Review - 2026-11-17

## Scope

The owner accepts scoped commits for the completed manual work. Preserve the
method/specifications/reviews, maintained skill, selected source tooling and
current delivery/handoff metadata. Stage shared-file manual sections without
absorbing application/printer changes. No push or runtime/GUI/NAS operation.

The historical review rounds remain outside Git. Their exact 1133 files are
preserved in a separate verified local archive, selected by
[the archive manifest](../review-artifacts-2026-11-17.json). The delivered 25
native/PDF pairs remain in the existing ignored Handleidingen set. See
[source/artifact availability](../source-availability.md) for retrieval limits.
No deletion, history rewrite, binary replacement or new owner acceptance.

## Checks

- All selected Python sources parse; 26 selected JavaScript files pass syntax
  checks. These checks do not execute captures, fixtures, Affinity or servers.
- The maintained skill validates. Its source/PDF helper passes the current
  AST-03 v20 two-page pair with zero missing source fragments.
- Shared component/registry tests pass for 25 registered manuals.
- The conversion script passes 25 offline mocked fault-injection checks in a
  fresh proof folder. Affinity/native execution is not part of this test.
- Six offline cases execute the actual delivered index filter against inert
  data. This is neither browser layout verification nor an operator trial.
- Staged source checks retain frozen CRLF script bytes under the existing
  `-text` attributes. Whitespace verification recognizes CR at end-of-line;
  no other whitespace suppression or frozen-source normalization is used.
- The prior current-set audit passed 25 version/role/page/specification matches,
  all 582 delivered file hashes, 130 current links and unchanged delivery ZIP.
  Publication does not rerun or replace the native/physical evidence.

## Known Pre-Existing Package Check Failure

`verify-guide-package.mjs` fails on three PDFs present in the old draft folder
but absent from that folder's manifest:

- CAT-00-catalogus-begrijpen-v7-draft.pdf
- CAT-00-catalogus-begrijpen-v8-draft.pdf
- CAT-01-model-en-modelnummer-aanmaken-v4-draft.pdf

All three are already tracked in starting commit a766abedee and match its
bytes; the checker is also unchanged. Preserve this historical manifest drift
as a separate maintenance question. Do not delete old evidence or rewrite a
frozen manifest to turn this source-publication check green. This failure does
not describe the current 25-pair/68-page delivery or invalidate its hash audit.

Current application alignment, fresh-device/fonts, operator/A4/label trials
and exact-version acceptance remain open as recorded in the handoff.
