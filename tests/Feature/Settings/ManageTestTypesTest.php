<?php

namespace Tests\Feature\Settings;

use App\Models\TestType;
use App\Models\User;
use Tests\TestCase;

class ManageTestTypesTest extends TestCase
{
    public function test_admin_can_update_tooltip(): void
    {
        $user = User::factory()->superuser()->create();
        $type = TestType::factory()->create(['tooltip' => 'Old']);

        $response = $this->actingAs($user)
            ->put(route('settings.testtypes.update', $type), [
                'name' => $type->name,
                'slug' => $type->slug,
                'tooltip' => 'New tip',
                'is_required' => 1,
            ]);

        $response->assertRedirect(route('settings.testtypes.index'));
        $this->assertDatabaseHas('test_types', [
            'id' => $type->id,
            'tooltip' => 'New tip',
        ]);
    }
}
