# V1 Operational Role-Capability Matrix

This matrix is the implemented least-privilege baseline for the four production foundation groups. It uses explicit permission keys; the operational groups do not receive the legacy `refurbisher`, `senior-refurbisher`, or broad `supervisor` marker flags.

| Capability | Refurbisher | Senior Refurbisher | Supervisor | Admin |
| --- | ---: | ---: | ---: | ---: |
| View and edit an existing asset | Yes | Yes | Yes | Yes |
| Create or soft-delete an asset | No | No | Yes | Yes |
| Upload/caption asset images | Yes | Yes | Yes | Yes |
| Remove asset images | No | Yes | Yes | Yes |
| View/upload/delete private asset files | No | No | No | Yes |
| View/upload/delete model-level files | No | No | No | Yes |
| View licenses, keys, and license files | No | No | No | Yes |
| Scan asset/component labels | Yes | Yes | Yes | Yes |
| Execute and complete workflows | Yes | Yes | Yes | Yes |
| Delete workflow history | No | No | Yes | Yes |
| View/register/update components | Yes | Yes | Yes | Yes |
| Extract/install/move/verify components | Yes | Yes | Yes | Yes |
| Soft-delete a component record | No | No | Yes | Yes |
| Mark component destruction pending/destroyed | No | No | Yes | Yes |
| View work orders | No | No | Yes | Yes |
| Create/update work orders | No | No | Yes | Yes |
| Manage work-order visibility | No | No | Yes | Yes |
| Move assets to Ready for Sale/Sold | No | No | Yes | Yes |
| View/create/edit product models and model numbers | No | No | Yes | Yes |
| Add/edit model specifications | No | No | Yes | Yes |
| Change model-number lifecycle/defaults | No | No | No | Yes |
| Remove saved model specification rows | No | No | No | Yes |
| View/create/edit reusable attributes | No | No | Yes | Yes |
| Hide/restore/delete attributes or saved options | No | No | No | Yes |
| View/create/edit workflow items and profiles | No | No | Yes | Yes |
| Delete workflow items or profiles | No | No | No | Yes |
| Create/edit component definitions | No | No | Yes | Yes |
| Deactivate/delete definitions or remove saved definition rows | No | No | No | Yes |
| Manage component storage catalog | No | No | No | Yes |

## Permission Mapping

- Existing asset work: `assets.view`, `assets.edit`.
- Asset creation/deletion and sale release remain separate: `assets.create`, `assets.delete`, `assets.sale_transition`.
- Asset media uses `assets.images.upload` and `assets.images.manage`. Promoting
  private workflow evidence into the public gallery is a publish action and
  also requires `assets.images.upload`. The controller temporarily continues
  to accept the historical role-marker permissions for backward compatibility,
  but foundation groups use the explicit keys. Asset soft deletion preserves
  gallery files so an authorized restore does not produce broken media.
- Private asset attachments use separate `assets.files.view`,
  `assets.files.upload`, and `assets.files.manage` abilities. Model-level
  resources use the equivalent `models.files.*` abilities. They are deliberately
  not implied by ordinary asset/model view or edit rights. The default Admin's
  broad administrator permission covers them; narrower custom groups can grant
  only the needed file ability.
- License metadata, product keys, files, checkout, and check-in remain separate
  through `licenses.view`, `licenses.keys`, `licenses.files`,
  `licenses.checkout`, and `licenses.checkin`. None are part of the operational
  Refurbisher/Senior/Supervisor foundation grants. General license search,
  reports, and CSV exports omit product keys without `licenses.keys`;
  `licenses.files` gates attachment list, download, upload, and deletion.
- Scanning and workflow work use `scanning`, `tests.execute`, and the separately granted `tests.delete`.
- Operational component work uses `components.view`, `components.create`, `components.update`, `components.extract`, `components.install`, `components.move`, and `components.verify`.
- Component record deletion, lifecycle destruction, and catalog administration remain separate: `components.delete`, `components.destroy`, `components.manage_definitions`, and `components.manage_storage_locations`.
- Work orders use `workorders.view`, `workorders.create`, `workorders.update`, and the separately granted `workorders.manage_visibility`.
- Normal product setup uses `models.view`, `models.create`, `models.edit`,
  `attributes.view`, `attributes.create`, `attributes.edit`, and
  `components.manage_definitions`. Changing primary/deprecated model-number
  state uses `models.manage_lifecycle`; removing saved specification rows uses
  `models.manage_specification_cleanup`; attribute hide/restore/option removal
  uses `attributes.lifecycle`; definition deactivation and saved-row removal
  use `components.manage_definition_lifecycle`. The latter lifecycle and
  cleanup abilities remain Admin-only in the foundation roles.
- On upgraded installations, a historical `models.delete` grant may remain
  because the seeder deliberately preserves custom permissions. Model and
  model-number deletion/restoration therefore also require
  `models.manage_lifecycle`; the legacy grant alone cannot widen Supervisor
  access.
- Workflow setup uses `workflows.view`, `workflows.create`, and
  `workflows.edit`. `workflows.delete` remains Admin-only. The runtime model is
  still named `TestType` for compatibility, but authorization uses the
  registered `workflows.*` vocabulary.

`ProductionPermissionGroupSeeder` merges these required grants into same-name groups. It does not remove additional permissions an administrator has added. Explicit administrator denials of required baseline keys are converted back to the required grant on rerun; use a differently named custom group when a deployment needs a narrower role than this baseline.

Run that seeder after migrations on every upgrade as described in
`docs/production-deployment.md`; deploying the new code alone cannot modify
permissions already stored in an existing database.
