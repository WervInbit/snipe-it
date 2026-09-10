# AST-03 v5 Review

| Field | Value |
| --- | --- |
| Previous version | v4 working draft |
| Feedback source | Owner review, 2026-08-18 |
| Impact | Create-control target geometry and physical placement evidence |
| Status | Working draft; real placement photo pending |

## Correction

- Calculate the 1A and 1B focus rectangles from the canonical screenshot and
  the actual `object-fit: cover` crop instead of enlarging them by eye.
- Move the 1B image identifier to the opposite corner so it does not compete
  with the highlighted top-bar control.
- Remove the rejected generated underside image from the active guide.
- Keep one bounded evidence slot for a real full-underside photo with the front
  edge facing the reader and the attached QR label at lower right.
- Remove the repeated scanner photo from step 4 and retain only the opened
  asset result after scanning.

## External Reference

HP's official ProBook 450 G8 parts locator includes a full bottom view and
identifies the service-tag area:
<https://h10032.www1.hp.com/ctg/Manual/c06974341.pdf>.

It is not canonical AST-03 evidence because it does not show Inbit's physical
QR placement. A marketplace photo also exists, but third-party product imagery
should not replace an owner-controlled practice photo in the guide.

## QA

- Both v5 A4 pages render within their page bounds.
- The `+` and `Nieuwe aanmaken` targets enclose their controls without covering
  adjacent actions or labels.
- The rejected generated image and repeated scanner photo are absent.
- Exact-version review remains blocked only by the real step-3 placement photo.
