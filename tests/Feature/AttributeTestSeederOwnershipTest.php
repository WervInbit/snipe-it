<?php

namespace Tests\Feature;

use App\Models\TestType;
use App\Models\WorkflowProfile;
use App\Models\WorkflowProfileItem;
use Database\Seeders\AttributeTestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttributeTestSeederOwnershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_rerunning_the_seeder_preserves_user_managed_workflow_configuration(): void
    {
        $this->seed(AttributeTestSeeder::class);

        $battery = TestType::query()->where('slug', 'battery')->firstOrFail();
        $battery->forceFill([
            'name' => 'Handmatige batterijtest',
            'instructions' => 'Door de beheerder ingestelde instructie.',
            'display_order' => 777,
        ])->save();

        $profile = WorkflowProfile::query()->where('slug', 'standard-diagnostics')->firstOrFail();
        $profile->forceFill([
            'name' => 'Mijn diagnosevolgorde',
            'display_order' => 88,
        ])->save();

        $profileItem = WorkflowProfileItem::query()
            ->where('workflow_profile_id', $profile->id)
            ->where('workflow_item_id', $battery->id)
            ->firstOrFail();
        $profileItem->forceFill([
            'sort_order' => 55,
            'is_required' => false,
            'result_label_mode' => WorkflowProfileItem::LABEL_MODE_DONE_NOT_DONE,
        ])->save();

        $this->seed(AttributeTestSeeder::class);

        $this->assertDatabaseHas('workflow_items', [
            'id' => $battery->id,
            'name' => 'Handmatige batterijtest',
            'instructions' => 'Door de beheerder ingestelde instructie.',
            'display_order' => 777,
        ]);
        $this->assertDatabaseHas('workflow_profiles', [
            'id' => $profile->id,
            'name' => 'Mijn diagnosevolgorde',
            'display_order' => 88,
        ]);
        $this->assertDatabaseHas('workflow_profile_items', [
            'id' => $profileItem->id,
            'sort_order' => 55,
            'is_required' => false,
            'result_label_mode' => WorkflowProfileItem::LABEL_MODE_DONE_NOT_DONE,
        ]);
    }

    public function test_operator_owned_steps_are_not_seeded_automatically(): void
    {
        $this->seed(AttributeTestSeeder::class);

        $this->assertDatabaseMissing('workflow_items', ['slug' => 'igpu']);
        $this->assertDatabaseMissing('workflow_items', ['slug' => 'programmable-key']);
        $this->assertDatabaseMissing('workflow_items', ['slug' => 'erase-history']);
        $this->assertDatabaseMissing('workflow_items', ['slug' => 'cleaning-external']);
        $this->assertDatabaseMissing('workflow_items', ['slug' => 'cleaning-internal']);
        $this->assertDatabaseMissing('workflow_profiles', ['slug' => 'cleaning']);
    }
}
