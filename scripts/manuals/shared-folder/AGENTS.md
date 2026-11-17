# Manual Maintenance Entry Point

This directory is the portable Handleidingen set. Start here without relying on
chat history, a particular model, an installed skill or the original repository.

## Find The Current Material

- For ordinary reading/printing, open [START HIER.html](START%20HIER.html) or PDF.
- For a question about one manual, find its code/title in
  [_Beheer/handleidingen.json](_Beheer/handleidingen.json), then read the linked
  manual text and the relevant PDF pages. Extracted text cannot show a screenshot
  control, its visible state or the actual reading order reliably.
- For editing, first read [_Beheer/LEESMIJ.md](_Beheer/LEESMIJ.md), the selected
  manual record, its maintenance specification and the applicable rules.
- [_Beheer/skill/SKILL.md](_Beheer/skill/SKILL.md) is optional reusable guidance.
  Its principles also apply when the host cannot install skills.

The JSON register selects the current native/PDF pair and records exact version,
roles, hashes, sources, verification and remaining gaps. There is one current
file per manual in PDF and Affinity. Descriptive filenames are stable; printed
versions and the register establish revision identity. Earlier exact-version
acceptance never automatically accepts a new version.

## Authority And Changes

Apply current user instructions and owner decisions in their actual scope.
Use [the authoring method](_Beheer/regels/authoring-method.md) for reasoning and
system.md's precedence for the family plan, individual specification, shared
components, layout, screenshot evidence and maintenance rules. Historical
pending-conversion statements do not override the completed current register.
An archive, frozen generator or latest timestamp cannot override those rules.

Establish the reader outcome, navigation, actions versus alternatives, actual
submission and recognizable result before composing pages. Preserve useful
existing support. Keep exact access-group names, genuine Dutch application
evidence, recognizable context and useful A4 target sizes. Focus marks must
resolve a real search or distinction; routine checks should remain calm.
Use manual or handleiding in new names and prose.

Native .af files are the editing masters. Compare current file hashes before
work; unexpected changes may be another person's work. Never regenerate over
manual edits. Visible/content changes require a new documented integer version,
an updated PDF from the saved native file, comparison and matching register.
Preserve the prior exact pair in _Beheer/archief. Check other affected manuals.
Do not treat a successful save, text check or model review as operator acceptance.

## Resources And Environment

The delivered native manuals contain full embedded image pixels. Separate full
originals and provenance are under _Beheer/bronmateriaal; retain them. Screenshots
are raster images. Native text, frames, styles, vectors and groups are separate.
Arial is required; font binaries are not included. Keep images independently
replaceable/croppable and verify annotation alignment after changing a crop.

Paths in current navigation are relative to this folder. Historical source
paths in evidence identify earlier inputs, not required installation locations.
Read [_Beheer/bronmateriaal/context-links.md](_Beheer/bronmateriaal/context-links.md)
for repository-only or archived references. State missing source/runtime evidence
honestly; do not invent it or infer deployment from an old capture.

This package does not establish current application/database/printer access.
Use the actual application's repository handbook and named runtime for work
beyond manual maintenance. Do not perform application changes, account actions,
printing or remote-device operations merely because this folder mentions them.
Computer control needs current task authorization; an interruption pauses input.

## Check And Hand Off

Keep separate evidence for artifact integrity, rendered interpretation, actual
native editing and observed user/physical acceptance. Inspect complete affected
pages and plausible wrong readings, comparing useful baseline support.

Run `python _Beheer/hulpmiddelen/controleer-map.py` for the delivery's hashes,
registered files and local index links. This is an integrity check. Use the
PDF/native procedures and source/PDF checker for deeper changes; inspect renders.
After native edits, export all intended pages, check overflow, resources/fonts,
layout and actual target recognition, then update text, version, evidence and
hash records together. Record results, unresolved issues and exact affected files
in the maintenance log. Leave other people's changes intact.
