# Agent Addendum - 2026-08-13 USR-01 Guide Review

## Scope

- Revise only USR-01 from the user-account review batch.
- Make add-user entry the primary first action and duplicate searching a
  secondary safeguard.
- Document the agreed temporary password as username plus current year and
  require immediate self-change through AC-02.
- Name the bottom-page group selector and top `Machtigingen` tab, including
  the risk of exposing unused features through incorrect direct rights.
- Remove image-badge and screenshot overlap from step titles.

## Constraints

- Keep the other user-account guide drafts unchanged.
- Use the existing real controlled evidence; do not fabricate application
  states or expose a password value.
- Generate a new USR-01 draft version and visually verify its final PDF before
  presenting it for review.

## Outcome

- Generated focused `USR-01 v2` without altering the combined user-account v1
  review batch or the other guide PDFs.
- The page now treats the add action as primary, duplicate search as a
  secondary safeguard, and username plus current year as the temporary
  first-login password followed by immediate AC-02 replacement.
- The guide points to `Groepen` at the bottom and the second top
  `Machtigingen` tab, including `Global: Super User` and the risk of activating
  unused functions through incorrect rights.
- All image badges now clear their headings. The password/login focus and the
  step 3 warning were tightened so no target, caption, or text overlaps.
- The final one-page A4 PDF passed text, geometry, forbidden-string, render,
  and full-resolution visual checks. It remains a draft awaiting user review.
- A subsequent detail pass centered the 1A target, retained only the relevant
  login target in 2A, added a full blue AC-02 inline reference, removed the
  non-critical step 3 stop, and removed the unnecessary 4A focus overlay.
- Step 4 now explains how to reach the saved-user page. Help now uses minimum
  rights and points custom per-user rights to USR-02 instead of showing LDAP or
  asking an account creator for separate permission.
- USR-01 v4 makes the route to `Gebruikers` explicit, corrects the completion
  row's vertical alignment, and introduces planned USR-05 as the separate
  guide for creating or editing reusable group definitions.
- The focused v4 PDF passed one-page A4 geometry, required-text, and
  full-resolution rendered-layout checks.
- USR-01 v5 corrects the account-creator role to Admin / Superadmin, adds the
  AC family icon to the AC-01 prerequisite, and replaces three abbreviated
  footer chips with two full guide names.
- The v5 PDF passed one-page A4, required-text, and full-resolution header and
  footer layout checks.
- USR-01 v6 adds a second related-guide row for five complete guide names
  without duplicating AC-01 from the prerequisite context.
- The v6 repository PDF passed A4/text checks and a full-resolution footer
  inspection; all five names, the QR block, and the source line remain clear.
- Implemented the first shared guide-component system in
  `scripts/manuals/lib/guide-system.mjs`: central-baseline badges, family and
  guide registry, symmetric focus bounds, common context/completion/reference
  components, five-reference/two-row layout, and renderer geometry checks.
- Added contract tests and an A4 visual regression proof. The first proof run
  caught a real USR-03 reference overflow; the rebalanced layout now passes all
  five measured reference checks.
- Migrated USR-01 to the shared system as v7. The one-page proof passes 11 badge
  alignment checks and 5 related-guide overflow checks and retains the content
  and layout corrections from v6.
- Added `components.md` plus a focused v7 review record so global, family,
  guide, and version-level feedback, including small alignment nudges, remains
  traceable and reusable.
- Clarified artifact states across the current guide documentation: internally
  accepted versions are `Internal review candidate`; only an exact version
  accepted by the later reviewer is `Third-party approved`.
- Captured a new unannotated Dutch dashboard source with the expanded
  `Personen` navigation and `Toon Alles`, then added it to USR-01 v8 as 1A.
  The previous add-user toolbar became 1B, with unequal visual widths retaining
  recognizable context for both screenshots.
- User explicitly accepted USR-01 v8. Recorded the exact version as an
  `Internal review candidate for V1`; future changes require a new version.
- Prepared USR-02 v2 as the next focused review page. It preserves the verified
  standard-group and direct-rights workflow while adopting shared components,
  clear heading/image separation, measured focus padding, and four full guide
  references over two rows.
- Revised USR-02 to v3 after review. Removed redundant/approval-oriented stops,
  documented multi-group `Ctrl+klik` selection and deselection, changed the
  role to Admin / Superadmin, preserved actual Superadmin-only group and Super
  User boundaries, and explained Overnemen/Toestaan/Weigeren on the page.
