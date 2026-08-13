# Canonical Screenshot Catalog

Status: current reusable evidence registry through 2026-08-13.

Use this catalog before capturing or copying screenshots. When two guides show the same application state, they should reference the same source ID and file. A guide may use a different crop or target annotation without creating a new source asset.

## Capture Rules

- Reuse a canonical source when the application state and device context are the same.
- Create a new source ID only for a materially different state, device context, or replacement capture.
- Keep annotations out of the source file. Add circles, rectangles, labels, and crops in the guide generator.
- Record the real capture environment internally.
- Use `https://dev.inbit/` for controlled missing or state-changing evidence.
- Keep `https://snipe.inbit/` as the URL shown to operators.
- A development capture that differs materially from live remains draft evidence until replaced or explicitly accepted.

## Current Sources

| Source ID | State or purpose | Canonical file | Current uses | Environment |
| --- | --- | --- | --- | --- |
| `PHONE-START-01` | Recognizable phone launcher and Inbit Snipe-IT shortcut | `resources/manuals/operator-guides/evidence/PHONE-START-01.jpg` | AC-01 1A | Existing device capture |
| `DASH-MOBILE-01` | Mobile dashboard with `Apparaten`, `Scan QR`, and the top-bar camera icon | `resources/manuals/operator-guides/evidence/DASH-MOBILE-01.jpg` | AC-01 3A; SC-01 1A and 1B | Existing device capture |
| `LOGIN-MOBILE-01` | Mobile login form | `resources/manuals/operator-guides/evidence/LOGIN-MOBILE-01.png` | AC-01 2A | Live login page |
| `DASH-DESKTOP-01` | Desktop dashboard top bar and camera icon | `C:\Users\Gebruiker\Documents\snipe-it manuals\layout-proofs\2026-07-07-sc01-snipe-scan\captures\SC-01-live-dashboard.jpg` | Historical SC-01 experiments only; not used in the current mobile-first guide | Live capture |
| `SCAN-CAMERA-QR-01` | Mobile scanner view aimed at a physical QR label | `resources/manuals/operator-guides/evidence/SCAN-CAMERA-QR-01.jpg` | SC-01 2A; AST-03 3A; AST-05 3A | Existing device capture |
| `SEARCH-FIELD-01` | Manual asset search field | `resources/manuals/operator-guides/evidence/SEARCH-FIELD-01.png` | SC-01 3A | Existing controlled capture |
| `SEARCH-RESULT-01` | Asset search result | `resources/manuals/operator-guides/evidence/SEARCH-RESULT-01.png` | SC-01 3B | Existing controlled capture |
| `ASSET-VERIFY-01` | Opened asset title and identifying details | `resources/manuals/operator-guides/evidence/ASSET-VERIFY-01.png` | SC-01 4A; AST-03 4A/1A; AST-05 1A | Existing controlled capture |
| `ASSET-DETAIL-02` | Opened asset tag, model, status, and quality details | `resources/manuals/operator-guides/evidence/ASSET-DETAIL-02.png` | SC-01 4B; AST-03 4A; AST-04 3A; AST-05 4A | Existing controlled capture |
| `ASSET-LABEL-01` | Asset QR label print/template area | `resources/manuals/operator-guides/evidence/ASSET-LABEL-01.png` | AST-03 2A | Existing controlled capture |
| `ASSET-INDEX-01` | Hardware index with the new-asset action | `resources/manuals/operator-guides/evidence/ASSET-INDEX-01.png` | AST-03 1A | Controlled development capture |
| `WF-TESTS-WIDE-01` | Historical open asset Tests/Workflows tab with profile selector and start action | `C:\Users\Gebruiker\Documents\snipe-it manuals\screenshot-source\2026-07-02-first-batch-refresh\WF-01-tests-tab-wide-live.png` | Historical WF-01 v5 proof | Controlled development capture |
| `WF-ACTIVE-CARDS-01` | Historical active workflow cards with instruction, result, and note controls | `C:\Users\Gebruiker\Documents\snipe-it manuals\draft-guides\2026-06-25-login-asset-test\_crops\active-test-cards.jpg` | Historical WF-01 v5 and WF-02 v3 proofs | Existing controlled crop source |
| `WF-RESULTS-01` | Saved workflow-result rows, upper area | `resources/manuals/operator-guides/evidence/WF-RESULTS-01.png` | AST-04 1A | Controlled development capture |
| `WF-RESULTS-02` | Saved workflow-result rows, lower area | `resources/manuals/operator-guides/evidence/WF-RESULTS-02.png` | AST-05 2A | Controlled development capture |
| `WF-ENTRY-MOBILE-02` | Historical phone-width Tests tab, workflow profile, start action, and existing-run row | `C:\Users\Gebruiker\Documents\snipe-it manuals\screenshot-source\2026-07-23-workflow-refresh\WF-01-profile-existing-run-wide-phone.png` | Historical WF-01 v6 and WF-02 v4 proofs | Controlled development capture, 2026-07-23 |
| `WF-ACTIVE-MOBILE-02` | Historical phone-width existing workflow with selected result cards | `C:\Users\Gebruiker\Documents\snipe-it manuals\screenshot-source\2026-07-23-workflow-refresh\WF-01-existing-run-mobile.png` | Historical WF-01 v6 and WF-02 v4 proofs | Controlled development capture, 2026-07-23 |
| `WF-INSTRUCTIONS-MOBILE-02` | Historical result card with instructions expanded after a selected result | `C:\Users\Gebruiker\Documents\snipe-it manuals\screenshot-source\2026-07-23-workflow-refresh\WF-02-instructions-expanded-mobile.png` | Historical WF-02 v4 proof | Controlled development capture, 2026-07-23 |
| `WF-NOTE-MOBILE-02` | Historical result card with note panel open and a saved note | `C:\Users\Gebruiker\Documents\snipe-it manuals\screenshot-source\2026-07-23-workflow-refresh\WF-02-note-open-mobile.png` | Historical WF-02 v4 proof | Controlled development capture, 2026-07-23 |
| `WF-PHOTO-MOBILE-02` | Historical result card with Photo panel and Add photo action | `C:\Users\Gebruiker\Documents\snipe-it manuals\screenshot-source\2026-07-23-workflow-refresh\WF-02-photo-open-mobile.png` | Historical WF-02 v4 proof | Controlled development capture, 2026-07-23 |
| `WF-ENTRY-MOBILE-03` | Current mobile Tests tab, profile selector, new-run action, unfinished run, and completed run | `resources/manuals/operator-guides/evidence/WF-ENTRY-MOBILE-03.png` | WF-01 1A, 2A, 3A, 3B; WF-02 6A | Controlled development capture, 2026-08-04 |
| `WF-NEUTRAL-MOBILE-03` | Blank active workflow with neutral result buttons | `resources/manuals/operator-guides/evidence/WF-NEUTRAL-MOBILE-03.png` | WF-01 4A; WF-02 1A and 3A | Controlled development capture, 2026-08-04; WF-02 1A reconstructs the complete anonymized breadcrumb in the generator |
| `WF-INSTRUCTIONS-MOBILE-03` | Full blank workflow card with instructions expanded and result buttons still neutral | `resources/manuals/operator-guides/evidence/WF-INSTRUCTIONS-MOBILE-03.png` | WF-02 2A | Controlled development capture, 2026-08-04 |
| `WF-NOTE-MOBILE-03` | Full blank workflow card with instructions collapsed and note panel open | `resources/manuals/operator-guides/evidence/WF-NOTE-MOBILE-03.png` | WF-02 4A | Controlled development capture, 2026-08-04 |
| `WF-PHOTO-MOBILE-03` | Full blank workflow card with instructions collapsed and photo panel open | `resources/manuals/operator-guides/evidence/WF-PHOTO-MOBILE-03.png` | WF-02 5A | Controlled development capture, 2026-08-04 |
| `CMP-INSTALL-ENTRY-MOBILE-02` | Mobile asset component tab and `Add / Install Component` action | `resources/manuals/operator-guides/evidence/CMP-INSTALL-ENTRY-MOBILE-02.png` | CMP-01 1A; reusable by CMP-02 | Controlled development capture, 2026-08-04; printed crop excludes workflow-attention banner |
| `CMP-INSTALL-SELECTED-MOBILE-02` | Mobile install form with controlled tray component selected | `resources/manuals/operator-guides/evidence/CMP-INSTALL-SELECTED-MOBILE-02.png` | CMP-01 2A and 3A | Controlled development capture, 2026-08-04; 2A targets identity, 3A targets Install |
| `CMP-INSTALL-RESULT-MOBILE-02` | Mobile tracked component row after installation with matching tag and serial | `resources/manuals/operator-guides/evidence/CMP-INSTALL-RESULT-MOBILE-02.png` | CMP-01 4A | Controlled development capture, 2026-08-04 |
| `CMP-NEW-ENTRY-MOBILE-03` | Mobile new-component entry with `Show New Component Form` | `resources/manuals/operator-guides/evidence/CMP-NEW-ENTRY-MOBILE-03.jpg` | CMP-02 1B | Controlled development capture, 2026-08-04 |
| `CMP-NEW-DEFINITION-MOBILE-03` | Definition-backed new-component route with serial, condition, and create/install action | `resources/manuals/operator-guides/evidence/CMP-NEW-DEFINITION-MOBILE-03.jpg` | CMP-02 2A and 3A | Controlled development capture, 2026-08-04; 2A targets the route, 3A targets Create And Install |
| `CMP-NEW-CUSTOM-MOBILE-03` | Custom new-component route with custom name, serial, and condition | `resources/manuals/operator-guides/evidence/CMP-NEW-CUSTOM-MOBILE-03.jpg` | CMP-02 2B | Controlled development capture, 2026-08-04; form opened but not submitted |
| `CMP-NEW-INSTALLED-MOBILE-03` | Definition-backed tracked row after create/install | `resources/manuals/operator-guides/evidence/CMP-NEW-INSTALLED-MOBILE-03.jpg` | CMP-02 4A; CMP-04 1A and 1B | Controlled development capture, 2026-08-04; printed crops exclude workflow-attention banner |
| `CMP-TRAY-CONFIRM-MOBILE-03` | Move-to-tray confirmation with locked serial and confirmation action | `resources/manuals/operator-guides/evidence/CMP-TRAY-CONFIRM-MOBILE-03.jpg` | CMP-04 2A and 3A | Controlled development capture, 2026-08-04 |
| `CMP-TRAY-RESULT-MOBILE-03` | Component detail after removal with `In Tray` and no asset attached | `resources/manuals/operator-guides/evidence/CMP-TRAY-RESULT-MOBILE-03.jpg` | CMP-04 4A and 4B | Controlled development capture, 2026-08-04 |
| `CMP-TAB-WIDE-01` | Component tab and Add / Install Component action | `resources/manuals/operator-guides/evidence/CMP-TAB-WIDE-01.png` | CMP-01 1A; CMP-02 1A; CMP-04 1A | Controlled development capture |
| `CMP-ROWS-01` | Component rows and `Naar tray` action | `resources/manuals/operator-guides/evidence/CMP-ROWS-01.png` | AST-04 2A; CMP-01 5A; CMP-02 5A; CMP-04 2A | Controlled development capture |
| `CMP-LIST-01` | Tracked component identity list | `resources/manuals/operator-guides/evidence/CMP-LIST-01.png` | CMP-01 3A; CMP-02 4A | Controlled development capture |
| `CMP-TRAY-LOCKED-01` | `Naar tray` dialog with locked serial field and confirmation | `resources/manuals/operator-guides/evidence/CMP-TRAY-LOCKED-01.png` | CMP-04 3A, 4A | Controlled development capture |
| `CMP-TRAY-UNLOCKED-01` | `Naar tray` dialog with editable serial field | `resources/manuals/operator-guides/evidence/CMP-TRAY-UNLOCKED-01.png` | CMP-04 3B | Controlled development capture |

## Adding Evidence

For each new source, record:

1. A stable source ID.
2. The exact unannotated file path.
3. The application state and device context.
4. Capture environment and date.
5. Guides and image labels currently using it.
6. Whether it is approved evidence or still draft-only.

## User-Management Evidence

USR-01 through USR-04 and AC-02 use unannotated captures from the controlled
development environment. The original set was captured on 2026-08-11 with the
fictional Mila de Boer account; the expanded navigation source was added on
2026-08-13. No password value is visible. The common source
directory is `resources/manuals/operator-guides/evidence/`.

| Source ID | State or purpose | Used by |
| --- | --- | --- |
| `USR-DASHBOARD-PEOPLE-NAV-DESKTOP-01` | Dutch dashboard with expanded `Personen` navigation and `Toon Alles` | USR-01 1A |
| `USR-LIST-DESKTOP-01` | Users list with search and add-user action | USR-01 1B; USR-02 1A |
| `USR-CREATE-FORM-DESKTOP-01` | Add-user identity, password generator, and login controls | USR-01 2A |
| `USR-GROUP-EDIT-DESKTOP-01` | Editable group selector showing standard operational groups | USR-01 3A; USR-02 2A |
| `USR-PERMISSIONS-DESKTOP-01` | Permissions tab with Toestaan, Weigeren, and Overnemen | USR-02 3A |
| `USR-DETAIL-DESKTOP-01` | Saved fictional user identity, login state, group, and edit action | USR-01 4A; USR-02 1B and 4A; USR-03 1A |
| `USR-RESET-LINK-DESKTOP-01` | User-detail password-reset-link action | USR-03 2A |
| `USR-EDIT-PASSWORD-DESKTOP-01` | Edit-user generated temporary-password route with values hidden | USR-03 2B |
| `AC-ACCOUNT-MENU-DESKTOP-01` | Account menu and self-service password-change action | USR-03 3A; AC-02 1A |
| `AC-SELF-PASSWORD-DESKTOP-01` | Empty self-service password fields and save action | AC-02 2A and 3A |
| `USR-ASSIGNMENTS-DESKTOP-01` | User detail with assignment and management tabs | USR-04 side 1, 1A and 2A |
| `USR-EDIT-ACTIVATED-DESKTOP-01` | Edit-user activated checkbox | USR-04 side 1, 3A |
| `USR-DEACTIVATED-DESKTOP-01` | Saved deactivated account state | USR-04 side 1, 4A |
| `USR-DELETE-DESKTOP-01` | Delete and bulk check-in/delete controls | USR-04 side 2, 1A |
| `USR-DELETED-LIST-DESKTOP-01` | Removed-user list containing the fictional identity | USR-04 side 2, 2A |
| `USR-RESTORE-DESKTOP-01` | Removed-user Restore action | USR-04 side 2, 3A |
| `USR-RESTORED-DESKTOP-01` | Restored identity, group, and login state | USR-04 side 2, 4A |

The group captures used an existing controlled Superadmin session. No live
access was elevated. AC-02 intentionally does not include a success-state
capture because producing one would require entering and submitting a real
password; the draft shows the empty form and save control instead.
