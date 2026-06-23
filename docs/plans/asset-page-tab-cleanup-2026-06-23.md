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
