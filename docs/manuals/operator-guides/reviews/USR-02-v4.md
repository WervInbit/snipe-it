# USR-02 v4 Review Record

| Field | Value |
| --- | --- |
| Status | Superseded by USR-02 v5 |
| Previous version | USR-02 v3 |
| Generated | 2026-08-13 |
| PDF | `output/pdf/usr-02-rol-en-rechten-wijzigen-v4-draft.pdf` |
| Proof folder | `C:\Users\Gebruiker\Documents\snipe-it manuals\layout-proofs\2026-08-13-usr02-review-v4` |
| Generator | `scripts/manuals/generate-user-account-guide-review.mjs` |
| Feedback source | Internal guide review |
| Impact | Guide-specific content, evidence layout, and focus targets |

## Included Corrections

- Step 1 now uses two linked visuals. `1A` shows the search control and a real
  user result; `1B` shows the actual `Gebruiker aanpassen` action on that
  user's detail page.
- Search and edit have separate measured red targets. No edit control is
  invented on the user-list page.
- Step 2 now states explicitly that only Superadmin can add groups to a user or
  remove them. This wording is verified against `UserPrivilegeService`, the
  individual user update flow, and the bulk user update flow.
- Step 3 states that direct `Toestaan` or `Weigeren` choices take priority over
  inherited group rights.
- The second help title is now `Effect van recht onduidelijk` so the object of
  the warning is explicit.
- USR-02 now records `mixed-visual-widths` in addition to its stacked layout.

## QA

- Shared component geometry checks: passed with 10 badges and 4 full guide
  references.
- PDF page count and A4 dimensions: passed; one A4 page at 594.96 x 841.92
  points.
- Extracted text: passed for both step-1 actions, explicit group add/remove,
  direct-right priority, and the clarified help title. Stale v3 wording is
  absent.
- Full-page PDF raster review at 180 DPI: passed without overlap, clipping,
  misleading targets, or unreadable permission text.
