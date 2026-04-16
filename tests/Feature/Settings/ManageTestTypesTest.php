<?php

namespace Tests\Feature\Settings;

use App\Models\TestType;
use App\Models\User;
use Tests\TestCase;

class ManageTestTypesTest extends TestCase
{
    public function test_admin_can_create_test_type_with_slug_generated_from_name(): void
    {
        $user = User::factory()->superuser()->create();

        $response = $this->actingAs($user)
            ->post(route('settings.testtypes.store'), [
                'name' => 'Battery / Health',
                'tooltip' => 'Checks battery condition',
                'is_required' => 1,
            ]);

        $response->assertRedirect(route('settings.testtypes.index'));
        $this->assertDatabaseHas('test_types', [
            'name' => 'Battery / Health',
            'slug' => 'battery-health',
        ]);
    }

    public function test_admin_create_suffixes_generated_slug_when_name_collides(): void
    {
        $user = User::factory()->superuser()->create();
        TestType::factory()->create([
            'name' => 'Battery Health',
            'slug' => 'battery-health',
        ]);

        $response = $this->actingAs($user)
            ->post(route('settings.testtypes.store'), [
                'name' => 'Battery Health',
                'is_required' => 1,
            ]);

        $response->assertRedirect(route('settings.testtypes.index'));
        $this->assertDatabaseHas('test_types', [
            'name' => 'Battery Health',
            'slug' => 'battery-health-2',
        ]);
    }

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

    public function test_admin_update_uses_name_for_slug_when_manual_override_is_off(): void
    {
        $user = User::factory()->superuser()->create();
        $type = TestType::factory()->create([
            'name' => 'Camera',
            'slug' => 'camera',
        ]);

        $response = $this->actingAs($user)
            ->put(route('settings.testtypes.update', $type), [
                'name' => 'Battery / Health',
                'tooltip' => $type->tooltip,
                'is_required' => 1,
                'manual_slug_override' => 0,
            ]);

        $response->assertRedirect(route('settings.testtypes.index'));
        $this->assertDatabaseHas('test_types', [
            'id' => $type->id,
            'name' => 'Battery / Health',
            'slug' => 'battery-health',
        ]);
    }

    public function test_admin_can_manually_override_slug_and_it_is_normalized_with_suffix(): void
    {
        $user = User::factory()->superuser()->create();
        TestType::factory()->create([
            'name' => 'Battery Health',
            'slug' => 'battery-health',
        ]);
        $type = TestType::factory()->create([
            'name' => 'Camera',
            'slug' => 'camera',
        ]);

        $response = $this->actingAs($user)
            ->put(route('settings.testtypes.update', $type), [
                'name' => 'Camera',
                'slug' => 'Battery ### Health',
                'tooltip' => $type->tooltip,
                'is_required' => 1,
                'manual_slug_override' => 1,
            ]);

        $response->assertRedirect(route('settings.testtypes.index'));
        $this->assertDatabaseHas('test_types', [
            'id' => $type->id,
            'slug' => 'battery-health-2',
        ]);
    }

    public function test_admin_can_reorder_test_types(): void
    {
        $user = User::factory()->superuser()->create();
        $first = TestType::factory()->create([
            'name' => 'First',
            'display_order' => 0,
        ]);
        $second = TestType::factory()->create([
            'name' => 'Second',
            'display_order' => 1,
        ]);
        $third = TestType::factory()->create([
            'name' => 'Third',
            'display_order' => 2,
        ]);

        $response = $this->actingAs($user)->patch(route('settings.testtypes.reorder'), [
            'order' => [$third->id, $first->id, $second->id],
        ]);

        $response->assertOk()->assertJson(['status' => 'ok']);

        $this->assertDatabaseHas('test_types', [
            'id' => $third->id,
            'display_order' => 0,
        ]);
        $this->assertDatabaseHas('test_types', [
            'id' => $first->id,
            'display_order' => 1,
        ]);
        $this->assertDatabaseHas('test_types', [
            'id' => $second->id,
            'display_order' => 2,
        ]);
    }
}
