# USR-05 Groepen beheren

## Application Alignment Revision - 2026-10-01

Current authoring proposal: **v3, working draft; not accepted**, 4 A4 pages. [Exact-version review](../reviews/USR-05-v3.md) and [batch comparison](../reviews/aligned-guides-2026-10-01.md).

Separate operational status access from general group permissions; canonical role matrix and realistic role test.

Added page: Controleer statusrechten naast de rechtengroep. Steps: Houd de standaardrollen herkenbaar; Open het statuslabel voor See en Choose / use; Test de effectieve toegang met een gewone gebruiker.

The frozen new HTML preserves this generated proposal for reproduction. It does not define approved instructional scope or supersede the guide specification, [authoring method](../authoring-method.md), or owner decisions. Resolve conflicting walkthroughs before a new revision. Earlier artifacts and acceptance decisions are unchanged. Generator: scripts/manuals/generate-aligned-guides-2026-10-01.mjs; hash-locked v2 input.

## Purpose And Boundaries

Define a reusable group without confusing its stored grants with a user's
effective access. USR-01 owns user creation; USR-02 owns group assignment and
direct user exceptions. Editing a group affects all its members.

## V2 Page Contract

1. In the dark left sidebar, choose Instellingen (gear), scroll within its
   submenu, and choose Groepen. `Beheerder Instellingen` is the resulting page
   title, not the first navigation control. Compare a candidate's rights and
   members. Reuse unchanged returns immediately to USR-01 step 3 or USR-02
   step 2. Edit for all members uses Groep bewerken. Create a missing package
   uses Nieuwe aanmaken. Preserve unsaved caller input before leaving.
2. Named custom example Catalogus lezen: Models View allowed; other Models,
   Admin and Super User rights denied. Show both column headings and actual
   per-row controls. Section radios affect every row; clicking the section
   title expands/collapses it. Group permissions are additive: if one assigned
   group allows a right and another assigned group denies it, Allow wins. If
   no assigned group allows it, the user lacks it. A direct user setting is
   evaluated before all groups: direct Deny blocks group Allows and direct
   Allow grants the right regardless of group Denies.
3. Opslaan, reopen, check the same group and all changed rights. Use USR-02
   to prepare a test user with known groups and direct exceptions. Model
   viewing must work; creation/editing must not. A Superadmin's own session is
   not evidence of this group's effective access. Return to the original user
   assignment only after checking the intended task boundary.

The standard foundation roles are not the example for a permanently narrowed
custom task; their seeded required grants may be restored. Keep that behavior
in mind when choosing reuse versus a new group.

## Evidence And Verification

The v2 generator lists the exact evidence IDs. New read-only captures show the
same memberless custom group in edit and saved-detail states. A guarded local
fixture helper created only that group, checked five resolver cases without
creating a user, and removed it after capture. No memberships were changed.

Resolver checks do not replace the printed browser trial. See the
[v2 revision review](../reviews/USR-05-CAT-05-CAT-06-v2-2026-09-15.md).

## Completion And History

Complete when stored group rights match the agreed task and the controlled
user passes both allowed and prohibited-task checks. Reuse-only completion
does not require editing the existing group.

V1's effective-rights wording is superseded, not carried forward as procedure.
Its PDF and hash remain unchanged in the [v1 review](../reviews/USR-05-v1.md).
The [audit](../reviews/USR-05-CAT-05-CAT-06-content-audit-2026-09-15.md)
records why v2 was required.

Related: USR-01 Gebruiker toevoegen; USR-02 Rol en rechten wijzigen;
AC-01 Login; HELP-01 Problemen en hulp.
