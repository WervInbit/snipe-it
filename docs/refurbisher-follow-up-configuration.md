# Refurbisher Follow-Up Configuration

This change ships the application behavior for the September 2026 refurbisher
review. It deliberately leaves business names, status assignments, and local
work instructions editable instead of fixing them in seed data.

## After Deploying

1. Run the normal additive migrations. They create per-user recent-device
   activity and status access rules, and add the `requires_note` status flag.
2. Run `ProductionPermissionGroupSeeder` to apply the role floor:
   - Refurbisher: no asset creation and no quality edit.
   - Senior Refurbisher: asset creation and quality edit.
   - Supervisor and Admin: asset creation and quality edit.
3. Review every status under **Settings > Status Labels**:
   - set the desired Dutch name;
   - set group/user **View** and **Choose/use** values;
   - enable **Require a note when changing to this status** for QA or other
     statuses where an explanation is mandatory.
4. Add **Afgevoerd** manually and assign its lifecycle stage to
   `Destroyed`. Leaving Sold, Broken/Parts, or Destroyed is then Admin-only and
   requires a reason plus the exact asset tag/QR confirmation.

Until any explicit access rule exists for a status/capability, legacy access is
kept. Once rules exist, direct user overrides take precedence and group Allows
are additive. A user must be able to view a status before it can be selected.

## Workflow Content Owned by Administrators

Create and write the instructions for these local steps rather than adding them
to the foundation seeder:

- Programmeerbare toets.
- Geschiedenis wissen.
- iGPU/CPU checks.
- External cleaning.
- Internal cleaning.

Workflow profiles and their items can be reordered in Settings. Rerunning
`AttributeTestSeeder` now creates missing foundation rows only; it preserves
administrator-edited item text, order, requiredness, button mode, profile
names, and profile order.

On an active workflow, **Klaar** stays at the bottom and is disabled with an
explanation until all required results are saved and complete. Finishing it
returns to the same asset's Workflows tab. When every available workflow is
complete, the asset page says so below the final editable workflow.

## Account, Dashboard, and Scan Behavior

- Refurbishers have a compact account menu containing password change and
  logout actions. Local passwords require at least eight characters, one
  uppercase character, and one number.
- **Remember me remains available on desktop and mobile**, including
  non-shared Supervisor phones that are expected to stay signed in.
- The dashboard action is named **Scan QR**. The browser scanner already asks
  for camera permission through `getUserMedia`, offers retry/camera-selection
  controls, and retains manual lookup. Camera permission must be allowed for
  the application origin in the phone/browser/OS settings, and production must
  use a secure HTTPS origin. This feature does not perform serial-number OCR.
- Authorized users see **New Asset**. The five most recent permitted devices
  that the current user viewed or changed appear as one-line links below the
  action/status blocks.

## Creation, Asset Detail, and Attributes

Successful creation opens a focused result page with **Print QR** and
**Open device** actions. Multi-create uses one summary and reports partial
failures there instead of stacking messages on the asset list. The Serial row
appears immediately below the asset tag on asset detail.

Hidden or deprecated attributes already assigned to a component/model remain
editable and removable there, but cannot be newly assigned. New assignments
must use a current attribute. This closes the lifecycle gap that made some
existing component specifications impossible to edit.

No repository seed or application source identifies a 6400 MHz value that
should be changed to 3200 MHz. Verify the affected live model number, component
contribution, and effective-spec provenance before correcting data; do not
silently replace the value during deployment.
