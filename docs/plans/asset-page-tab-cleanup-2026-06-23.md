# Asset Page Tab Cleanup Notes

Date: 2026-06-23

These notes capture quick product decisions from the asset-page tab cleanup investigation. Items marked for rework are intentionally not implementation-ready yet; each needs feature planning before code changes.

## Current Decisions

| Asset page area | Direction | Notes |
| --- | --- | --- |
| Licenses | Rework | Licenses remain relevant for sold devices and for users who buy additional software such as Office. Access should be restricted. Rework needs separate planning for license ownership, sold-device visibility, user/software assignment, and terminology that does not rely on generic checkout language. |
| Devices / Apparaten | Deprecate / remove | This is not expected to be used in the current workflow. Asset-to-asset attachment has effectively been replaced by Components. |
| Images | Rework | Expand into one place for all images attached to a device, including direct asset uploads, workflow/test images, and thumbnails. This should connect to the later photo normalization/optimization work. |
| Maintenance / Onderhoud | Deprecate / remove | The generic maintenance module is not part of the current refurb workflow and overlaps with workflows, status history, components, and possible future work-order/service flows. |
| Files / Bestanden | Rework | Consider combining with Images into a single attachments/media area for QR codes, images, PDFs, certificates, reports, and similar files. Needs planning around file ownership, permissions, and where upload controls belong. |
| Extra files / Extra bestanden | Investigate / rework | These are model-level files exposed on the asset page. Investigate whether they should move to model pages, be nested under attachments, or be shown only as read-only model resources. |
| Send / Upload paperclip | Deprecate / remove | This is only a nav action for generic asset uploads. Upload controls should move under the relevant blocks, but this needs investigation first so asset images, test photos, QR files, PDFs, and other uploads land in the right place. |

## Follow-up Planning Questions

- Which user roles should see and manage licenses on sold devices or user-owned add-on software?
- Should the future media/attachments area be one tab with filters, or separate grouped blocks inside one tab?
- How should workflow/test photos be retained, displayed, promoted, or hidden from the general media gallery?
- Should generated QR labels be stored as attachments, regenerated on demand, or both?
- Should model-level files be visible from the asset page, and if so, should they be clearly read-only and labelled as model resources?
- What should replace maintenance if external repair, warranty, supplier, or cost tracking is needed later?

## 2026-08-18 Scope Clarification

The current tabs represent different data types and should not be treated as
one interchangeable upload bucket:

- **Licenses** are the inherited Snipe-IT software-entitlement and seat model.
  They cover device-bound Windows/OEM licenses, Office or other add-on
  software, and multi-seat/volume licenses assigned to assets or users. License
  metadata, product keys, attached license files, seat assignment, check-in,
  and check-out already have distinct authorization abilities. The asset page
  now hides its check-in action unless the viewer has the matching permission.
- **Images** are the fork's ordered device gallery and cover-image source.
  Workflow evidence can be explicitly promoted into that public-facing gallery.
  These images currently use public storage and therefore must contain only
  non-sensitive device/catalog media. A future unified media design must first
  decide public versus private visibility; hiding a tab cannot protect a public
  URL.
- **Files** are private generic attachments owned by one asset. For V1 they are
  suitable for restricted warranty documents, diagnostic exports, wipe
  certificates, and similar records. `assets.files.view`,
  `assets.files.upload`, and `assets.files.manage` now gate reading, uploading,
  and deletion independently from ordinary asset access.
- **Extra files** are private files owned by the asset model, not by the
  individual device. They are appropriate for shared manuals, driver packages,
  or model reference documents. `models.files.view`,
  `models.files.upload`, and `models.files.manage` now gate them independently.
  The later UI rework should move or clearly label them as read-only model
  resources instead of presenting them as device-specific evidence.
- **Workflow photos, results, notes, and exceptions** remain workflow evidence.
  New photos are stored privately and served through a controlled route; they
  should remain connected to the item/run that explains why they exist. A wipe
  confirmation can be a required workflow item, with a final certificate added
  as a restricted asset file when a separate document is required.

Repair or customer passwords must not be placed in notes, images, workflow
photos, or generic attachments. If temporary repair credentials must be
retained, use a separately approved secret-management flow with encryption,
least-privilege access, access logging, expiry, and verified deletion. Designing
that capability is outside the current V1 media rework.

The current QR layout ships in V1 and labels are regenerated on demand. A
post-V1 label builder will own printer limits, physical sticker sizes,
resolution validation, preview, and customizable templates. Battery-health
collection and smart diagnostics are also post-V1 work: the existing Windows
`scripts/hw-inventory.ps1` helper already contains a preliminary
full-charge/design-capacity calculation, but its Windows data sources and units
need validation before it can submit authoritative workflow results.

Workflow Profiles and Workflow Items are the configurable product vocabulary.
Legacy `Test*` class/table compatibility and diagnostic-specific words such as
"test result" can remain; they do not represent a separate task subsystem.
