# Native Affinity Conversion Evidence - 5 November 2026

## Current Result

Actual AST-03 baseline .af saved/reopened/exported on the NUC and returned to
the laptop. Both complete A4 pages now render with their headers in Poppler;
panel colors, baseline positions, leading and focus weights are repaired.
The missing page2 header was specific to PDFium's handling of repeated optional
PDF layers, not missing master content. A no-layers PDF export was canceled
after filename replacement failed; no new PDF was produced. The NUC export
preference now has Include layers OFF. Native files are unchanged.
Do not remove or duplicate native master content to fix that preview symptom.

The corrected all25 IDML candidate is
`output/manuals/affinity-idml-native-candidate-2026-11-05.zip`:1184192 bytes,
SHA256 `ed6c3751b133190e27cc1fdd078b9146cb00f3c177debd484de8ef2580316ac9`.
Reproduce with export-affinity-compatible-idml-2026-11-05.py and both
--layout-compatibility/--baseline-compatibility flags into a fresh output folder.
All712 original checksums, story text and full-image placement/links pass.
The separate affinity-conversion-native-candidate-2026-11-05 script package is
bound to these files;24 offline input/flow/failure checks pass, no native script
execution. Its README states export-layer qualification and native proof gaps.

All25 native masters, shared native template, full editing/relocation proof and
68-page native export review are unfinished. No full final release is claimed.
The remaining sections preserve the individual observations that led here.

NUC work stopped before the owner's logout/disconnection cutoff. All real
native/PDF comparisons and current checklist were returned. Final report-only
archive `output/manuals/AST-03-native-no-pdf-layers-20261105.zip` is9999 bytes,
SHA256 `046071289ef0a4f29510c195169f660f4eef0360ba7361f291b608f36f97d329`.
Its CURRENT-HANDOFF.txt records actual state and owner cleanup/sign-out actions.
No cleanup, account sign-out or cloud conversation deletion was automated.

Later read-only cleanup audit found a two-page AST-03 PDF without optional PDF
layers in Affinity's temp folder, despite the canceled Save dialog. It and four
task-session autosaves/two PNGs/extra review render are preserved separately as
`output/manuals/AST-03-nuc-recovery-backup-20261105.zip`:6709054 bytes,
SHA256 `c02d2888b145bf63430e2868ae21b1d6f63afb380f6168707732358e3fb1581a`.
Independent hash/path/CRC checks pass; original paths/hashes are inventoried.
The temp PDF is recovered evidence, not an accepted final guide. All needed guide
continuation data is local; owner may disconnect/clear the NUC. Autosave contents
remain unknown. Exact cleanup list: output/manuals/NUC-CLEANUP-2026-11-05.txt.

## Scope And Reader Preservation

Continue the authorized all25 guide set on the paired DESKTOP-LK9RHT5 only.
The laptop desktop is excluded. Preserve the prepared operator wording,
screenshots, crops, full original images, role names and accepted design
direction. This is a file-format fidelity repair, not a new guide rewrite.
Native editability, usable A4 exports and exact-master provenance remain gates.

## Real Native Results

Affinity3.3.3.5004 opened AST-03 v20 IDML without an importer warning, saved a
genuine .af, reopened it and exported PDF (for print), All Pages. Two A4 pages
contain live Arial/Arial Bold text. The first result is rejected: step panels
are black, dark copy is unreadable, common master headers/footer are covered,
number labels sit too high, line spacing compresses and focus strokes are thick.
Successful package/XML checks and SDK mocks did not predict native fidelity.

The first diagnostic archive is preserved locally at
`output/manuals/AST-03-native-pilot-diagnostic-20261105-121343-22ba1b0f.zip`:
2260459 bytes; SHA256
`9f523941df92050c4237ac1d993e93f335496ca992b40e839263982c8ac3f84a`.
It contains actual .af/PDF, two renders and operation/inspection reports.
It is diagnostic evidence, not a deliverable or guide acceptance.

## Confirmed Causes And Separate Corrections

- The original writer silently returned black for named SVG `white`. Use the
  captured resolved computed fills/strokes and reject unsupported color syntax.
  Captured scenes contain no unresolved CSS variables; that earlier hypothesis
  was not the cause.
- Page-level opaque white canvas rectangles cover content inherited from the
  master. Put the common canvas first on the master, behind the common header.
- The native PDF uses automatic line leading despite numeric Leading attributes.
  The typography probe uses typed Properties/Leading values and AscentOffset.
  First-baseline behavior and visible line layout require native verification.
- SVG non-scaling focus strokes are CSS pixels. Their native width is pixels
  times0.75 points, rather than SVG millimetres times72/25.4. Keep real vector
  targets separate and preserve their geometry.

The first separate compatibility set is
`output/manuals/affinity-idml-compatibility-2026-11-05.zip`,25 IDMLs,
1192641 bytes, SHA256
`9c174c8656246ff019dc860aed9903d8efb8ac07298629b78a4407fc48e2f36d`.
All712 original prepared checksums still pass; story content and full-image
transforms/links/crops are preserved. Actual corrected AST-03 native export
restores white panels and master header/footer. Typography/focus still fails.

The separate AST-03-only layout probe is
`output/manuals/affinity-idml-layout-probe-2026-11-05.zip`,48118 bytes, SHA256
`55dbd6f71045c48ff42b3a9d75d42b4186aa496002a99f1d93f85aa468b43929`.
Its native test is in progress. Neither archive is a final native release.

The layout probe's real save/reopen/PDF result improves leading/focus/baselines
but is not accepted. Page2's master header is absent from the initial PDF yet
appears in the app after visiting page2; Layers shows A-Basis. No native save
occurred between these observations. A post-visit export attempt was canceled
when filename input stopped responding. Reliable master export remains open;
do not classify this as an absent master assignment without further evidence.
The compatibility proof-copy text insertion caused font fallback/overflow and
was undone. Live paragraph/style editing is not proven.

Both real comparisons, proof copy, reports and cleanup checklist returned as
`output/manuals/AST-03-native-comparison-20261105.zip`:5713522 bytes, SHA256
`330856c1f74b57bc0e607b2552fc3233c0841a77412e6f9d9737aba848291df8`.
Independent chat checksum, ZIP paths and CRC passed on the laptop.

A final AST-03-only baseline probe uses source baseline coordinates with Arial
CapHeight (1467/2048 em) instead of importing browser glyph bounds as native
baseline origins. Numeric/uppercase badges center their cap height around the
source central coordinate. Archive48131 bytes, SHA256
`4ca69e42b08586a4b82cb2a38236def5bc39572ef7621e5bfaad63b31e6ccbca`;
path `output/manuals/affinity-idml-baseline-probe-2026-11-05.zip`.
Native save/reopen/export succeeded. The returned final-probe archive is
`output/manuals/AST-03-native-final-probe-20261105.zip`:4779041 bytes, SHA256
`356279a51466ef40a2916061f0653ac610b02a5d51ba2427483acc17c7245882`.
Independent chat checksum, length, ZIP paths and CRC pass. Both baseline pages
were rendered and visually read with bundled Poppler, which shows full headers.
PDFium's missing header is a renderer/optional-layer compatibility symptom.
Stop new NUC work by11:49UTC to return artifacts
before the owner's11:53UTC disconnect.

## Continuation And Limits

Do not batch-convert the original IDML set using the original script. Its input
binding and checksums describe the original files, which remain unchanged.
The preparer/template now support a separate IDML input root while keeping
original asset validation and fresh output protection. A clearly labeled
diagnostic package bound to the first compatibility set passes24 offline
flow/failure checks. It is not a runnable final recommendation and has not
executed inside Affinity. Existing script packages were not regenerated.
After a usable native AST-03 proof, prove CAT-00/CAT-05 and practical paragraph/
style reflow, original-image recrop/replacement, vector/group/master editing,
save/reopen and relocated resources on separate proof copies. Then build all25
native masters, a shared template, matching PDFs and inspect all68 native pages.

Scripting remained visibly OFF after the owner reported enabling it; no security
toggle was changed. Normal GUI conversion works independently. No current batch
execution/report exists. Plugin Management returned no Affinity integration;
no installed Affinity-specific skill was found. General Computer Use and the
installed Affinity SDK are available, without implying format compatibility.

The owner plans logout/disconnection at11:53UTC/12:53 Amsterdam. Return and
verify actual outputs and the concrete cleanup checklist before that deadline.
No laptop input, application/deployment/DB/printer change, cleanup deletion or
account-conversation deletion is authorized by this conversion work.
