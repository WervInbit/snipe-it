# Prerequisites And Return Paths - 2026-09-10

Desk review of all 22 selected guides and their guide specifications. This is
not an observed novice trial. All referenced IDs resolve in the registry;
three referenced guides have no delivered PDF: USR-05, CAT-05 and CAT-06.

| Area | What the reader needs | Current handling / remaining work |
| --- | --- | --- |
| AC-01/02, SC-01, HELP-01 | Device, existing account, camera permission and support contact | Login/search/help routes exist. Confirm local support contact in an operator trial. |
| AST-02/03/04/05 | Valid catalogue identity, required profiles, physical label and handoff location | CAT-01 and SC-01 handoffs exist. Profile assignment and QA-completion rules still depend on local operating decisions. |
| WF-01/02 | Correct asset, profile and unfinished run, tests/save behaviour | 2A/3A sequence preserved; profile choice and return to existing run are explicit. Confirm required profiles and note/error persistence live. |
| CMP-01/02/04 | Correct physical component, serial/tag identity, tray and reusable type | Missing type routes through Supervisor/CAT-04 and returns to CMP-02 2A. Defining the return from an interrupted unsaved form still needs a practical exercise. |
| USR-01/02/03/04 | Appropriate admin role, group, account identity and credential delivery | USR-04 now only disables login, and referring titles agree. USR-01 still mentions restoring an existing account, but restoration has no dedicated delivered guide. Scope that task separately; USR-05 is already reserved for groups. |
| CAT-00 | Which record is shared and which is physical | New learning cards put CAT-03/04 before CAT-01/02. IDs and comparison-bundle order stay stable. |
| CAT-01 | Manufacturer/category, exact code and reliable identity source | Use existing exact code; missing global catalogue data requires an Admin. CAT-06 source verification is still planned. Do not choose a near match. |
| CAT-02 | Model number and reusable attribute/component definitions | Create missing definitions through CAT-03/04 only. Preserving unsaved input and returning to the same model number needs explicit tested handoffs. |
| CAT-03/04 | Datatype, unit, scope, aggregation and child/contribution meaning | Learning route introduces these before use. CAT-05 cleanup and CAT-06 source guidance remain planned; current fallback is the appropriate Admin/Supervisor. |

Implemented learning route: CAT-00 -> CAT-03 -> CAT-04 -> CAT-01 -> CAT-02,
then AST-03 for a physical device or CMP guidance for a physical component.
This is a reading order; ordinary work reuses existing definitions and only
creates what is missing. A single universal execution order would create
duplicates and unnecessary work.

Next bounded review: give a first-time reader an existing-model task and a
missing-definition task; observe where they stop, what they expect to see,
and whether they return to the correct saved or unsaved record. Include
readers who benefit from predictable explicit steps. Do not treat one reader
as representative of all autistic users.
