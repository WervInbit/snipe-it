# Manual Source And Artifact Availability

## Repository Source Checkpoint - 2026-11-17

The scoped manual source commits preserve the method, shared rules, all 25
specifications, version/review records, maintained skill and installer,
selected production/checking tools and shared-folder templates. They do not
publish the application changes or constitute a self-contained binary build.
The installed personal skill is a copy; the maintained source is in this tree.

The source commits are local until deliberately pushed. The surrounding
application/onboarding/backlog reconciliation still contains unrelated pending
work; manual publication does not merge, deploy or qualify that work.

Source checkpoint: **f51593d906**, followed by the delivery/handoff record.
This is publication into local Git history, without a remote push or a clean
application/environment qualification. The shared root documentation is staged
by manual section; its newer cross-project reconciliation remains in the
working tree for its owning task.

## Delivered Native/PDF Baseline

[current-set.json](current-set.json) records the 25 exact delivered pairs / 68
pages and hashes. The owner reports copying Handleidingen to the NAS. Its
path, integrity and later human edits have not been independently checked.
The generated local folder and output/manuals/Handleidingen-2026-11-12.zip
remain outside Git. They are not files supplied by a fresh clone.

The current native masters must be obtained from the actual shared delivery
before revising it. Compare them first; old generation inputs cannot replace
subsequent human edits. The shared set contains searchable text, specifications,
original images, native image maps and its own checks/provenance under _Beheer.
Do not infer that every older review round is present in that delivery.

## Historical Review Evidence

[review-artifacts-2026-11-17.json](review-artifacts-2026-11-17.json) records all
1133 retained files / 198343825 bytes from six uncommitted review rounds. Their
PDFs, screenshots, HTML/SVG, manifests and frozen source copies are preserved
in the separate ignored local archive:

```text
output/manuals/Manual-review-history-2026-11-17.zip
163554075 bytes
SHA256 062078b6ce709a788006d59b59c0f3e851c1eea4b8f57746fe1ddcf7bbaa330f
```

ZIP integrity and each archived file's bytes were checked. No complete NAS copy
of this history archive is verified. Preserve it as a separate artifact; it is
not included in Git or automatically supplied with the current shared set.

Older review links name their original paths. If a required path is absent in
a clone, consult the manifest and obtain this exact archive from its holder.
Validate its hash and extract to a separate evidence checkout, retaining the
repository-relative paths. Do not overwrite a newer file or invent missing
evidence. Existing tracked accepted artifacts remain unchanged in Git.

## Tool Scope And Dependencies

- The maintained skill installer and checking/assembly scripts are source
  tools. Read their arguments and source requirements before execution.
- Python PDF/image checks use pypdf, pypdfium2 and Pillow where imported; the
  skill structural validator additionally needs PyYAML. Use the current bundled
  dependency runtime or deliberately provide those dependencies. No application
  package or font binary is added by this source checkpoint.
- JavaScript generation uses the existing scripts/manuals package/lockfile.
  Affinity scripts require the application SDK and the documented filesystem
  grants; syntax checking does not establish native execution.
- Dated capture/generation/conversion scripts retain exact historical hashes,
  output paths and machine assumptions as provenance. Some require ignored
  preparation artifacts, a controlled application or an explicitly selected
  runtime. They are not advertised as a clean-clone one-command rebuild.
- The old NUC transfer sender/receiver and temporary review server remain
  local historical helpers, outside these commits. No service, transfer or
  computer control is started by publishing the source.

Source/metadata verification, previous native editing proof, practical user
trials and exact-version acceptance remain separate evidence. Use
[HANDOFF.md](HANDOFF.md) for current state and remaining work.
