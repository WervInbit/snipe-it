<?php

namespace Database\Seeders;

use App\Models\Group;
use Illuminate\Database\Seeder;

class ProductionPermissionGroupSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->permissionGroups() as $name => $permissions) {
            /** @var Group $group */
            $group = Group::query()->firstOrNew(['name' => $name]);
            $existing = json_decode((string) $group->permissions, true);

            if (!is_array($existing)) {
                $existing = [];
            }

            // Foundation reruns add the required operational floor. They do not
            // remove permissions that an administrator added to the same group.
            $group->permissions = json_encode(array_replace($existing, $permissions));
            $group->save();
        }
    }

    /**
     * @return array<string,array<string,int>>
     */
    private function permissionGroups(): array
    {
        return [
            'Refurbisher' => [
                'assets.view' => 1,
                'assets.edit' => 1,
                'assets.images.upload' => 1,
                'scanning' => 1,
                'tests.execute' => 1,
                'components.view' => 1,
                'components.create' => 1,
                'components.update' => 1,
                'components.extract' => 1,
                'components.install' => 1,
                'components.move' => 1,
                'components.verify' => 1,
            ],
            'Senior Refurbisher' => [
                'assets.view' => 1,
                'assets.edit' => 1,
                'assets.images.upload' => 1,
                'assets.images.manage' => 1,
                'scanning' => 1,
                'tests.execute' => 1,
                'components.view' => 1,
                'components.create' => 1,
                'components.update' => 1,
                'components.extract' => 1,
                'components.install' => 1,
                'components.move' => 1,
                'components.verify' => 1,
            ],
            'Supervisor' => [
                'assets.view' => 1,
                'assets.edit' => 1,
                'assets.images.upload' => 1,
                'assets.images.manage' => 1,
                'scanning' => 1,
                'tests.execute' => 1,
                'assets.sale_transition' => 1,
                'assets.create' => 1,
                'assets.delete' => 1,
                'tests.delete' => 1,
                'models.view' => 1,
                'models.create' => 1,
                'models.edit' => 1,
                'attributes.view' => 1,
                'attributes.create' => 1,
                'attributes.edit' => 1,
                'workflows.view' => 1,
                'workflows.create' => 1,
                'workflows.edit' => 1,
                'components.view' => 1,
                'components.create' => 1,
                'components.update' => 1,
                'components.delete' => 1,
                'components.extract' => 1,
                'components.install' => 1,
                'components.move' => 1,
                'components.verify' => 1,
                'components.destroy' => 1,
                'components.manage_definitions' => 1,
                'workorders.view' => 1,
                'workorders.create' => 1,
                'workorders.update' => 1,
                'workorders.manage_visibility' => 1,
            ],
            'Admin' => [
                'admin' => 1,
                'assets.view' => 1,
                'assets.edit' => 1,
                'assets.images.upload' => 1,
                'assets.images.manage' => 1,
                'scanning' => 1,
                'tests.execute' => 1,
                'assets.sale_transition' => 1,
                'assets.create' => 1,
                'assets.delete' => 1,
                'tests.delete' => 1,
                'models.view' => 1,
                'models.create' => 1,
                'models.edit' => 1,
                'models.delete' => 1,
                'models.manage_lifecycle' => 1,
                'models.manage_specification_cleanup' => 1,
                'attributes.view' => 1,
                'attributes.create' => 1,
                'attributes.edit' => 1,
                'attributes.lifecycle' => 1,
                'attributes.delete' => 1,
                'workflows.view' => 1,
                'workflows.create' => 1,
                'workflows.edit' => 1,
                'workflows.delete' => 1,
                'components.view' => 1,
                'components.create' => 1,
                'components.update' => 1,
                'components.delete' => 1,
                'components.extract' => 1,
                'components.install' => 1,
                'components.move' => 1,
                'components.verify' => 1,
                'components.destroy' => 1,
                'components.manage_definitions' => 1,
                'components.manage_definition_lifecycle' => 1,
                'components.manage_storage_locations' => 1,
                'workorders.view' => 1,
                'workorders.create' => 1,
                'workorders.update' => 1,
                'workorders.manage_visibility' => 1,
                'audits.view' => 1,
                'config.manage' => 1,
                'config.qr_tooltips' => 1,
            ],
        ];
    }
}
