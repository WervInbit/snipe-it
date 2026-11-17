# Affinity Production And Proof

Use the current HANDOFF and exact conversion/proof records for paths, app
version, selected guides, remaining work and permissions. The procedure below
records mechanics established in this project, not universal SDK guarantees.

## Inputs And Scope

- Confirm the selected IDML/content version and original capture inventory;
  choose exact filenames. An adjacent repair file must not be selected by a
  wildcard as though it were the original. Hash the actual imported file.
- When promoting a verified repair, reconcile its exact selection in the input
  manifest and conversion marker together. Retain the original record; preserve
  strict hash guards instead of bypassing a mismatch to continue the batch.
- Preserve full-resolution images, editable text/style frames, separate vector
  annotations/diagrams, sensible groups and reusable master structure. An SVG
  or PDF import alone does not prove these properties.
- Keep technical conversion repairs separate from editorial changes. Compare
  the complete native/PDF result against its chosen content and source. The
  normal create-note, status, rights or source-policy gaps remain unresolved
  until their owning application/owner evidence resolves them.
- Use new paths and isolated proofs. Never regenerate over a human-edited master
  or close an unrelated/dirty open document to make a script proceed.

## Execute In Small Checkpoints

Prefer a sufficient available API/connector. When the project requires Windows
app control, read and follow the installed Computer Use skill and select a
fresh observed Affinity window. Execute prepared SDK code through Affinity's
own Script Editor. Filesystem preparation is separate from app execution.

The tested Script Editor Run evaluates top-level code. Exporting `main` alone
does not call it. A small wrapper explicitly invokes the prepared module:

```js
try {
    require('ABSOLUTE_PATH_TO_CURRENT_TASK.js').main();
} catch (error) {
    require('/application.js').Application.alert(String(error.stack || error), 'Guide task stopped');
}
```

Use one guide per explicit run when interruptions are expected. Import, inspect,
save native, close/reopen, inspect again, export and package, then record exact
outputs and warnings. A file-exists marker is not an approval marker. Verify
actual state after an interrupted/unknown run before retrying. Resume only
after the user's continue; skill installation cannot resume paused app control.
Use current tool recovery guidance for black/locked captures, never stale input.

Scripting/file access are application permissions. Check existing grants and
the user's explicit scope rather than assuming they are absent or re-requesting
already supplied authorization. Respect the current Computer Use restrictions.
Do not modify unrelated settings or reconnect a previously excluded device.

## Known Mechanics To Recheck Against The Installed Version

The tested Affinity 3.3.3.5004 environment:

- IDML text insertion needed compatible defaults as well as visible paragraph
  styles. Verify typing, pasted insertion and Return, including font/leading
  and paragraph reflow, on a copy; metadata inspection alone was insufficient.
- Font/object heights are document pixels in these SDK reports. Convert to
  points using `height * 72 / document.dpi`; do not assume a fixed DPI.
- Built-in PDF/X-3:2003 exported repeated header content without optional layers
  across both tested viewers. Other presets/layer settings require their own
  validation; do not create a custom preset merely for consistency.
- The verified SDK method is `document.export(path, options, area, null)`.
  Its returned records use `enumerate(callback)`, `isSuccess`, `hasWarnings`
  and `errorMessage`; return `EnumerationResult.Continue` from the callback.
  Check the records and the intended nonempty file. `exportFile` is not the
  tested Document API. Reuse the working exporter rather than guessing a method.
- `IncludeImages` packaging also copied font files. Inspect actual contents and
  retain a font inventory; do not redistribute copied fonts automatically.
- Image resource paths could be basenames. Do not resolve them against an
  arbitrary script directory and call the resulting failure a broken resource.
- `ReplaceBitmap` returned without proving image replacement. The tested node
  still retained its old dimensions/hash. Use a demonstrated UI/SDK operation
  and inspect the saved image; retrying that call is not a replacement proof.
- The native **Replace image** toolbar control does work for the selected image
  child inside its existing crop frame. Select that child, choose Replace image,
  select the retained full-resolution file, then adjust its crop and annotation
  alignment as needed. The project proof replaced830x8854 with1920x1650 pixels,
  retained the parent frame, recropped/undid and saved/reopened with both full
  extracted image hashes matching. This is a demonstrated UI route, not a
  claim that ReplaceBitmap is fixed. Keep proof edits outside delivered masters.
- A URL clipped in CAT-06 even though import/reopen object counts agreed.
  Preserve wording and useful type size; use available frame room and verify
  the full fragment in the new render/PDF instead of shrinking it to fit.

Inspect local SDK documentation/source before using unfamiliar signatures.
Record a newly proven technique with its version and result; do not promote a
failed experiment or undocumented guess into the reusable procedure.

## Evidence For Editable Delivery

### Independently Movable Native Files

The November12 shared-folder work qualified full embedded image pixels in the
flat native masters. Native Resource Manager > Embed is an available UI route.
For the tested bitmap route, create a native ImageNodeDefinition from the old
image's complete compatible bitmap and insert it inside the retained crop frame.
The insertion builder expects the old image's baseToSpreadTransform, with forced
pixel alignment disabled. Using its local transform moved the image in the
first isolated probe and was rejected. Preserve every crop transform, full pixel
dimension, source-pixel comparison and live text/image/group/page count.

Node IDs are session-local. After saving/reopening, verify semantic identity,
tree position, dimensions and transforms instead of treating a previous ID as
a persistent key. Four copies opened from folders containing only the .af file,
with Embedded resources, full-image extraction and working recrop/undo. Their
23 page exports matched the selected embedded copies. This is stronger image
portability evidence than an opening while resources still remain linked;
fresh-device/font and operator trials are separate.

Full original pixels and rendered export equality are distinct checks. The
all25 comparison preserved170 image placements and all text/geometry;67 of68
native-export pages were pixel-identical at150dpi. AC-01's phone screenshot
alone differed by at most4/255 per channel inside its existing image area.
Native Resource Manager embedding produced the same small variation. The
shared delivery retains all original print PDFs byte-for-byte, documents this
exact comparison and leaves the generic pixel comparison marked non-identical.
The specific technical-copy qualification is not owner acceptance or a general
tolerance for another image or future revision. Investigate a difference and
judge its actual effect; do not hide it or equate every byte difference with
instructional degradation. Original encoded files remain separately available.

Run representative edits on copies, save/close/reopen, and inspect the result:

- insert/paste a paragraph and Return with intended formatting/reflow;
- change a named shared style and verify all intended occurrences;
- replace a screenshot, retain the complete source pixels, and recrop it;
- independently edit a focus mark and a diagram label/connector;
- move a useful local group and a reusable master element;
- export the saved native version and compare all pages with its source.

Restore proof edits or keep them in a clearly separate proof file. Test a
relocated package without relying on the original absolute source locations;
reopen, save native, export and inspect resource/content preservation. A copied
file opening on the same machine is narrower evidence than a fresh-device test.

Use `../scripts/verify-guide-pair.py` for exact source/PDF checks. Normalize
Unicode compatibility forms and whitespace for extraction comparisons, while
retaining original fragments in reports. Inspect missing text and cross-page
placement visually. Compare native import/reopen counts, actual warnings,
font availability and full image hashes separately in the conversion report.

Render final A4 pages and inspect both full-page reading order and actual target
details. Record viewed pages against the PDF hash so a later export cannot
inherit a stale visual pass. A contact sheet supports structural screening but
does not establish every control's recognition size or a physical print trial.

Package only the chosen outputs, originals/provenance, edit/export instructions,
styles/template and an exact version/evidence ledger. Build one current review
index and check its links. Preserve all earlier proposals/proofs outside the
current selection. Native masters become the editing authority after handoff;
generation inputs cannot overwrite subsequent human changes without reconciliation.
