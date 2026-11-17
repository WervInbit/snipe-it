export const guideChanges = {
    'AC-01': {
        outcome: 'Sign in with the received account and recognize the Dashboard.',
        changes: ['Login help now names Admin for account creation and password reset.', 'Prepare the selected layout as editable objects with a common page master.'],
        preserved: 'The phone shortcut, login form and Dashboard recognition keep their actual A4 image sizes. No recent-device content.',
        tradeoff: 'No new task or screenshots: account support is corrected at the existing help point.'
    },
    'AC-02': {
        outcome: 'Save a private replacement password on the same local account.',
        changes: ['Carry the corrected form, menu and saved result into editable source.'],
        preserved: 'Both explicitly requested red focus marks, profile/menu context, complete unoutlined form, field positions, visible rule, actual Save, result and lost-password handoff.',
        tradeoff: 'No further focus or password recipe is introduced.'
    },
    'SC-01': {
        outcome: 'Find the same physical asset and open its verified record.',
        changes: ['Prepare the selected alternative routes as live text, independent image frames and annotations.'],
        preserved: 'Scan versus manual search remain alternatives on one page, including identity comparison and ambiguous/no-match recovery.',
        tradeoff: 'No second page or restarted step sequence.'
    },
    'AST-02': {
        outcome: 'Choose the task guide that belongs to the current refurbishment position.',
        changes: ['Prepare the existing route and component detours as editable references.'],
        preserved: 'AC-01 -> SC-01 -> WF-01 -> WF-02 -> AST-04 -> Supervisor/AST-05, with component choices and return.',
        tradeoff: 'The overview remains a router; it does not duplicate execution instructions.'
    },
    'AST-03': {
        outcome: 'Register one physical device, label it and verify the resulting identity.',
        changes: ['Carry pilot v19 into the editable two-sided source without substituting either creation entry.'],
        preserved: 'Both Dashboard tiles, duplicate search, list plus alternative, identity/model fields, optional registration note, actual Save, saved row, print/QR/photo and recovery.',
        tradeoff: 'The owner-required optional note is retained. Its contradiction with the recorded deployed validator remains a release-target issue, not rewritten policy.'
    },
    'AST-04': {
        outcome: 'Check completed work and hand the asset over for assessment.',
        changes: ['Prepare the selected single-page task and its saved status-note evidence for editing.'],
        preserved: 'Whole-work check, honest unresolved work, real status transition and its required later note, saved verification and handoff.',
        tradeoff: 'Registration-note policy is not applied to later status-transition auditing. No proposed future QA status.'
    },
    'AST-05': {
        outcome: 'Assess identity, work and specification, then record the permitted release or repair route.',
        changes: ['Prepare the selected two-sided assessment with editable decision and result objects.'],
        preserved: 'Supervisor role, real permitted status choices, assessment-before-release, conditional restoration and stored result.',
        tradeoff: 'No configuration proposal becomes an available status or an unverified physical QA location.'
    },
    'WF-01': {
        outcome: 'Start the available workflow once or open the existing run.',
        changes: ['Prepare the natural Start example and separate existing-run route for editing.'],
        preserved: 'Ordinary available diagnostics first, later blocked follow-up meaning, explicit start versus continue/edit, one-run checks and concrete selection criteria.',
        tradeoff: 'The old blocked-first demonstration and vague agreed-row instruction are not restored.'
    },
    'WF-02': {
        outcome: 'Carry out the work, save truthful results and requested evidence, and verify completion.',
        changes: ['Prepare the continuous two-sided task with editable result, upload and completion visuals.'],
        preserved: 'Continuous steps 1-6, actual saving, return/Continue, interruption recovery and whole-run verification.',
        tradeoff: 'A prepared demonstration result is not represented as a new physical test.'
    },
    'CMP-01': {
        outcome: 'Install the same existing tracked physical component on the intended asset.',
        changes: ['Recompose the four actions around larger entry, selected record and result details.', 'Show the actual Install control separately, retain its location under the selected record, and explain Tracked/Extra.', 'Restore missing-record/access/condition recovery through Supervisor; preserve removal of the old component before replacement.'],
        preserved: 'Tray/source identity, name/tag/SN comparison, physical placement before Install, final link/specification check, CMP-04 and WF-02 references.',
        tradeoff: 'A missing search match does not itself authorize another CMP-02 registration.'
    },
    'CMP-02': {
        outcome: 'Create exactly one new physical component and link it to the intended asset.',
        changes: ['Recompose side two with a full-width Create And Install control and a larger complete identity result.', 'Explain that the action creates the record and links it immediately; integrate duplicate, condition and unexpected Extra recovery as ordinary conditional guidance.'],
        preserved: 'Complete definition/custom alternatives on side one, missing-definition handoff, true serial/condition, replacement prerequisite, continuous steps 1-4 and physical verification.',
        tradeoff: 'The sparse warning-shaped block is retired; its concrete recovery moves to the result check.'
    },
    'CMP-04': {
        outcome: 'Move the same tracked identity to the operator tray with no current asset link.',
        changes: ['Show Components, the tracked identity and Naar tray as complementary details from the same record.', 'Retain locked-serial/cancel/missing-serial/access recovery and the actual confirmation.', 'Crop the result to readable Status/In Tray and Asset/N.v.t. labels, removing unused horizontal area.'],
        preserved: 'Four actions, tag/SN continuity, physical move, actual stored result, reinstallation and workflow references.',
        tradeoff: 'Two linked result details replace a small entire detail page; the full screenshot stays available in each editable frame.'
    },
    'USR-01': {
        outcome: 'Create one local account, save its intended group and transfer the temporary password privately.',
        changes: ['Use two continuous pages and five actions: search/create, identity, Generate/Save, group assignment/Save, verification/handoff.', 'Show Persons navigation, list search and plus, name/username/email, both actual saves and saved identity.', 'Explain a group as a task-rights package and save the new account before Superadmin edits it.', 'Preserve uncertainty help for an older/deleted account and an unavailable verified email; do not invent an address or require a duplicate.'],
        preserved: 'Exact Admin/Superadmin boundary, username convention, generated temporary password, intended login state, duplicate prevention, private handoff and AC-02/USR-02/USR-05.',
        tradeoff: 'One additional side protects readable entry/field/save evidence instead of compressing several unfamiliar actions into one.'
    },
    'USR-02': {
        outcome: 'Save groups, direct choices and any necessary status rule, then test the ordinary-user boundary.',
        changes: ['Recompose both sides with the editor entry, Machtigingen tab/columns and larger representative rows.', 'Show user Save before the separate status editor and the separate status Save.', 'Explain groups/direct precedence, concrete quality/workflow rights, and an allowed plus excluded task trial.'],
        preserved: 'Continuous steps 1-6, Superadmin membership boundary, additive groups, direct deny precedence and status visibility/use distinction.',
        tradeoff: 'Representative complete rows remain readable; no miniature of the entire long permissions table. The retained status dropdowns truncate their native displayed values; obtain an opened-choice capture or a real UI-width repair against the selected release before final delivery. Recropping cannot recover UI text that the application itself clipped.'
    },
    'USR-03': {
        outcome: 'Save a local-password reset and transfer it privately for the user to choose a private password.',
        changes: ['Show verified user/editor entry, a larger complete Generate group and a separate real Save control.'],
        preserved: 'Three actions, unchanged login state/rights, save-error recovery, private handoff, no captured password and AC-02/USR-01 references.',
        tradeoff: 'No redundant screenshot of the separate private-password task.'
    },
    'USR-04': {
        outcome: 'Save the existing account with Login ingeschakeld: Nee.',
        changes: ['Recompose identity/editor entry, complete login checkbox, separate Save and saved Nee near their instructions.', 'State that the check confirms the block on new logins.'],
        preserved: 'Disable-login-only scope, unchanged passwords/groups/rights, identity check, save failure recovery and account history.',
        tradeoff: 'No deletion/reactivation task or claim that every existing authenticated session ends immediately.'
    },
    'USR-05': {
        outcome: 'Reuse or save the correct shared group and verify both permitted and excluded user tasks.',
        changes: ['Reflow the three pages around larger settings/list, permission, Save and stored-rights evidence.', 'Show both Nieuwe aanmaken and Groep bewerken and preserve reuse as a route that needs no Save.', 'Make reopening and the ordinary-user test concrete; update creation handoffs to USR-01 step 4.'],
        preserved: 'Gear/Groepen entry, reuse/edit/create alternatives, own names for new packages, additive rights/direct precedence, status boundary and useful help.',
        tradeoff: 'The existing six-action continuation remains; no universal step reset or new rights policy.'
    },
    'CAT-00': {
        outcome: 'Recognize the catalogue object and select its owning task guide.',
        changes: ['Recompose reusable definitions as editable concepts/relationships, replacing unreadable list miniatures with the owning CAT-03/04 handoffs.', 'Enlarge the Assumed and Tracked/Extra examples; explain quantity, Sum and distinct beside their actual examples.', 'Remove stale guide-follows wording from maintenance/source routes.'],
        preserved: 'Six reference parts, complete first relationship map, two identity examples, reusable meanings, Removed boundary and learning/task router.',
        tradeoff: 'Concept headings remain reference material; they are not mandatory numbered application actions.'
    },
    'CAT-01': {
        outcome: 'Reuse or save one correct Basismodel and exact manufacturer code without a duplicate.',
        changes: ['Recompose pages 3-5 with complete Asset model naam/category/manufacturer fields, actual Save controls and larger saved row/information evidence.', 'Give missing category/manufacturer an Admin handoff and return point.', 'Keep full suffix and product/configuration distinctions with the next object choice.'],
        preserved: 'A/B/C routes, search in both object lists, named plus and Create Model Number, source checks, optional image, standard selection and copy limitations.',
        tradeoff: 'Shrunken field-example cards and empty image-frame bands give way to readable real fields; their meaning is retained in nearby copy.'
    },
    'CAT-02': {
        outcome: 'Save the correct variant baseline with direct facts and expected parts from the intended sources.',
        changes: ['Recompose all six pages with full-width natural crops, explicit Edit Spec entry and direct/expected choice.', 'Explain definitions, quantities, child precedence, Sum/distinct and why a component-supplied attribute is unavailable as a duplicate manual fact.', 'Show Save, reentry and the required complete stored-baseline check.'],
        preserved: 'Missing-definition handoff and unsaved-input return, Up/Down/X meanings, required expected rows, no physical-record creation and Admin removal boundary.',
        tradeoff: 'The reference to quantities stays at the component choice; aggregation maintenance stays in CAT-03/05.'
    },
    'CAT-03': {
        outcome: 'Save one reusable attribute meaning with the intended valid input rules.',
        changes: ['Recompose all six pages with the actual settings entry, field meanings and larger natural editor crops.', 'Retain numerical rule examples, show Enum Add to list in context, and show actual Save plus reentry.'],
        preserved: 'Search/reuse, generated key, fixed datatype, unit, category/required/override/display settings, numeric/Enum alternatives, regex boundary and existing-use impact.',
        tradeoff: 'No new key for an existing meaning; in-use rule/lifecycle changes still hand off to Admin/CAT-05.'
    },
    'CAT-04': {
        outcome: 'Save one reusable part definition with the right identity, children and contributions.',
        changes: ['Recompose all six pages with settings entry, full-width identity/child/contribution crops and actual Save.', 'Explain Instances/Templates, Quantity/Required, both display flags, source precedence and return to the calling task.'],
        preserved: 'RAM example, explicitly different motherboard example, optional-field restraint, always-present child boundary, missing-attribute return, saved verification and physical-record distinction.',
        tradeoff: 'A unique serial/tag never becomes another reusable definition. Lifecycle remains Admin maintenance.'
    },
    'CAT-05': {
        outcome: 'Complete only the selected maintenance change and verify its known affected outcomes.',
        changes: ['Recompose routes A/B/D/E/F/H with larger natural crops, exact entry/actions, separate Enum/aggregation Save and explicit recovery.', 'Show the actual row delete action, preserving certainty and impact checks before its generic confirmation.'],
        preserved: 'A-H index, independent routes, explicit alternatives, C/G saved relationship-removal consequences, override loss warning, direct-action versus staged-save differences and rollback of an unexpected numeric result.',
        tradeoff: 'Local route numbering remains legitimate. No browser definition-delete feature or automatic usage safeguard is invented.'
    },
    'CAT-06': {
        outcome: 'Return only a supported identity/fact, or hand over the precise unresolved question.',
        changes: ['Show the physical underside and a linked manufacturer-label detail together.', 'Use the complete HP product card and one focus mark on the relevant display statement, preserving source context.', 'Keep the confirmed family fact separate from the unproven exact-code RAM baseline.'],
        preserved: 'Complete suffix, identifier distinctions, source scope, supported/unresolved alternatives, actual source URL and return to CAT-01/02.',
        tradeoff: 'Routine scope guidance becomes ordinary copy; uncertainty remains conditional. The existing real source is not claimed as a fresh live capture.'
    },
    'HELP-01': {
        outcome: 'Look up the observed problem and take the matching recovery or role handoff.',
        changes: ['Account creation and password-reset help now name Admin, consistently with the owning account guides.', 'Prepare the selected two independent lookup sides as editable blocks.'],
        preserved: 'Practical login/search/workflow/component/status/printing recovery and exact role handoffs.',
        tradeoff: 'No mandatory read-all sequence, blind print retry or retired Windows setup procedure.'
    },
};
