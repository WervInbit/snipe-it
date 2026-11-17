# Operator Manual Registry

## Last Delivered Set - 2026-11-17

The November 12 delivery contains **25 manuals / 68 A4 pages**, each with a PDF
and editable native `.af` file. Conversion and packaging are complete. These
are working candidates; delivery, technical checks and the owner's reported
NAS copy do not establish exact-version acceptance or current app alignment.

[current-set.json](current-set.json) is the machine-readable **last delivered
baseline**, including exact versions, role names, relative filenames and
SHA256 hashes. It supersedes older proposal tables for this delivery selection.
The NAS path and contents have not been independently verified. A newer shared
edit may supersede this baseline: compare actual files before updating it.

All rows have review state **Working draft; exact-version acceptance open**.
AST-01 remains retired in favor of SC-01.

| Code / specification | Manual | Version | Pages | Access group (exact name) |
| --- | --- | --- | ---: | --- |
| [AC-01](guides/AC-01.md) | Login | v13 | 1 | Iedereen |
| [AC-02](guides/AC-02.md) | Eigen wachtwoord wijzigen | v8 | 1 | Iedereen |
| [AST-02](guides/AST-02.md) | Refurbishment route | v11 | 1 | Refurbisher |
| [AST-03](guides/AST-03.md) | Asset registreren en labelen | v20 | 2 | Supervisor |
| [AST-04](guides/AST-04.md) | Werk afronden en overdragen | v9 | 1 | Refurbisher |
| [AST-05](guides/AST-05.md) | Asset beoordelen en vrijgeven | v10 | 2 | Supervisor |
| [CAT-00](guides/CAT-00.md) | Catalogus begrijpen | v14 | 6 | Supervisor |
| [CAT-01](guides/CAT-01.md) | Model en modelnummer aanmaken | v10 | 5 | Supervisor |
| [CAT-02](guides/CAT-02.md) | Modelspecificatie opbouwen | v5 | 6 | Supervisor |
| [CAT-03](guides/CAT-03.md) | Attributen beheren | v5 | 6 | Supervisor |
| [CAT-04](guides/CAT-04.md) | Componentdefinities beheren | v8 | 6 | Supervisor |
| [CAT-05](guides/CAT-05.md) | Bestaande catalogus aanpassen | v5 | 9 | Admin |
| [CAT-06](guides/CAT-06.md) | Productgegevens controleren | v4 | 3 | Supervisor |
| [CMP-01](guides/CMP-01.md) | Bestaand component plaatsen | v8 | 1 | Senior Refurbisher |
| [CMP-02](guides/CMP-02.md) | Nieuw component registreren en plaatsen | v9 | 2 | Senior Refurbisher |
| [CMP-04](guides/CMP-04.md) | Component naar tray verplaatsen | v9 | 1 | Senior Refurbisher |
| [HELP-01](guides/HELP-01.md) | Problemen en hulp | v10 | 2 | Iedereen |
| [SC-01](guides/SC-01.md) | Asset vinden en openen | v16 | 1 | Refurbisher |
| [USR-01](guides/USR-01.md) | Gebruiker toevoegen | v16 | 2 | Admin / Superadmin |
| [USR-02](guides/USR-02.md) | Rol en rechten wijzigen | v14 | 2 | Admin / Superadmin |
| [USR-03](guides/USR-03.md) | Wachtwoord resetten | v7 | 1 | Admin |
| [USR-04](guides/USR-04.md) | Gebruiker uitschakelen | v9 | 1 | Admin |
| [USR-05](guides/USR-05.md) | Groepen beheren | v5 | 3 | Superadmin |
| [WF-01](guides/WF-01.md) | Workflow starten | v17 | 1 | Refurbisher |
| [WF-02](guides/WF-02.md) | Workflow uitvoeren en afronden | v15 | 2 | Refurbisher |

## Artifact And Editing Authority

User files live in flat `PDF/` and `Affinity/` folders under the delivered
`Handleidingen` root. `START HIER.html` is its offline task index; root
`AGENTS.md` and `_Beheer/` contain portable rules, searchable text, sources,
tools, checks and history. The owner reports copying this set to the NAS.
The local root folder and ZIP are generated/ignored, not fresh-clone resources.

The actual saved shared `.af` file owns subsequent human edits. Repository
specifications own task scope and rules; frozen HTML, IDML and dated generators
are provenance, not replacements for a newer native master. Update the paired
PDF, version record and relevant specification/review together. Follow
[maintenance.md](maintenance.md#shared-manual-updates) and [HANDOFF.md](HANDOFF.md).

Current bounded revisions include [CAT-04 v8](reviews/CAT-04-v8.md),
[USR-04 v9](reviews/USR-04-v9.md) and CAT-06 v4's verified URL-frame repair.
All print PDFs retain the earlier delivered bytes. Native test exports match
67/68 pages at 150 dpi; AC-01 v13 page 1 has the separately recorded phone-image
colour variation. The generic strict comparison remains non-identical; this is
not a general tolerance or owner acceptance. Details and remaining gates are
in the handoff.

## Field Contract

| Record | Required meaning |
| --- | --- |
| Code, title and role | Manual identity and existing access-group name; do not invent role labels |
| Version and pages | Exact delivered content version and A4 page count |
| Review state | Exact-version decision, separate from technical conversion or distribution |
| Native and PDF files/hashes | Relative to the delivery root; compare with actual shared files before editing |
| Specification | Reader outcome, scope, choices, evidence, completion and applicable family rules |
| Layout and source | Recipe/brief and exact original generation/import evidence; do not imply a generator reproduces later native edits |
| Review record | Previous version, intentional differences, evidence, remaining gaps and decision |

The [CAT family plan](catalog-guide-plan.md) controls topic ownership and
handoffs; it cannot accept or select a new artifact by itself.

## Accepted Artifact Protection

Earlier acceptance stays attached to the exact earlier PDF/version. Preserve
the [accepted snapshot](../../../resources/manuals/operator-guides/accepted-guides-v1/README.md)
and [dated registry history](registry-history-through-2026-11-12.md).
Do not copy an older code-level `GUIDE_REGISTRY` status onto a newer manual.
Legacy runtime registry metadata is not the delivered native-set ledger;
changes to it require a deliberate application/review target and consistent
references, without erasing prior acceptance.

## Synchronization Checks

- Compare the selected shared native/PDF files and hashes with the baseline.
- Reconcile version, pages, title and role across the specification, review,
  shared register and this register. Use a new version for visible changes.
- Preserve full embedded image sources, editable objects and any human changes.
- Export/reopen and inspect all affected pages; record integrity, editorial
  judgment, reader trials and acceptance as separate evidence.
- Preserve earlier artifacts and update the last-delivered record only when a
  new exact set is actually handed over; do not report an unverified NAS edit
  as a verified local delivery.
