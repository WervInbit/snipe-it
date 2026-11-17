# Operator Manuals / Handleidingen

## Current State - 2026-11-17

**25 manuals / 68 A4 pages are delivered as PDF and editable Affinity files.**
Native conversion, embedded image preparation and packaging are complete.
The owner confirms copying the set to the NAS. Its path, integrity and any
subsequent edits have not been independently checked here.

The delivered folder is designed for users:

```text
Handleidingen/
  START HIER.html
  PDF/          25 descriptively named print files
  Affinity/     25 matching editable .af files
  AGENTS.md     entry point for unfamiliar models
  _Beheer/      text, rules, sources, tools, checks and history
```

Use **manual/handleiding** in new user-facing names and prose. Existing internal
`operator-guides` paths, identifiers and historical artifacts keep their names.

[registry.md](registry.md) lists all exact delivered versions and access groups.
[current-set.json](current-set.json) records their filenames/hashes as the
last delivered baseline, not a live NAS inventory. The local `Handleidingen/`
and `output/manuals/Handleidingen-2026-11-12.zip` are generated/ignored; a fresh
clone does not supply those artifacts. Source changes in this shared working
tree also require scoped publication before a clone can use them.

[Source/artifact availability](source-availability.md) identifies what the
manual source commits contain, external review archives and tool dependencies.
Absent historical links require their recorded evidence package; a clone is
not a complete native-artifact rebuild.

## Continue Or Revise

Start with [HANDOFF.md](HANDOFF.md) for the current state, limits and remaining
evidence. Before editing, compare the actual NAS files and preserve human
changes. Saved native `.af` files own those edits; old HTML/IDML/generators are
retained provenance. Update native, PDF and version/specification records
together using [the shared update procedure](maintenance.md#shared-manual-updates).

For authoring or review, use the maintained
[snipeit-operator-guides skill](skill/SKILL.md) and
[authoring method](authoring-method.md). Install the personal copy with
`scripts/manuals/install-operator-guide-skill.py` when needed. The skill was
tested on selected comparisons and repairs; it did not newly reauthor all 25
manuals and does not establish general novice-use success.

No conversion or Computer Use task remains scheduled. Current open work is
application/release alignment (including USR-02 choice text and AST-03 optional
notes), fresh-device/font qualification, actual operator/A4/label trials and
exact-version decisions. [TODO START-05/NEXT-08](../../../TODO.md) owns that work;
the handoff does not create a competing task queue. Distribution is not acceptance.

## Owning Documents

- [Authoring method](authoring-method.md): reader outcome, reasoning and complete revision review.
- [System](system.md): precedence, shared content, visual identity and production rules.
- [Specifications](guides/) and [CAT family plan](catalog-guide-plan.md): scope, choices and handoffs.
- [Components](components.md), [layouts](layouts.md) and [screenshots](screenshots.md): visual and evidence contracts.
- [Registry](registry.md) and [delivered baseline](current-set.json): exact selected version metadata.
- [Maintenance](maintenance.md): ownership, preservation, impact and versioning.
- [Decisions](decisions.md) and [reviews](reviews/README.md): owner directions and exact-version evidence.
- [Inventory](inventory.md): dated source/tool history; check current artifact authority above.

## Status Vocabulary

| State | Meaning |
| --- | --- |
| Working draft | Editable candidate; remaining evidence/review may require changes |
| Technically converted / delivered | Native or distribution property; does not change editorial acceptance |
| Internal review candidate | Exact version internally accepted for further user/reviewer evaluation |
| Third-party approved | Exact version accepted by the named external reviewer |
| Needs revision | Reviewed content requires correction |
| Retired | Scope moved or removed; AST-01 is replaced by SC-01 |

## Historical Evidence

The [former project index](project-index-history-through-2026-11-12.md) and
[former registry](registry-history-through-2026-11-12.md) retain earlier
production tables, planning holds and pending-conversion statements with their
original context. They do not reopen completed work or select today's set.
Earlier accepted PDFs remain immutable. Each changed version needs its own
decision; exact historical acceptance never transfers automatically.
