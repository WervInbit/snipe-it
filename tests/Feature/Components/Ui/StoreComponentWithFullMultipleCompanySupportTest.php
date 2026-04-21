<?php

namespace Tests\Feature\Components\Ui;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\User;
use Tests\TestCase;

class StoreComponentWithFullMultipleCompanySupportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    public function testDirectWebStoreCreatesLooseComponent(): void
    {
        $user = User::factory()
            ->viewComponents()
            ->createComponents()
            ->create();

        $stock = \App\Models\ComponentStorageLocation::factory()->stock()->create();

        $response = $this->actingAs($user)
            ->post(route('components.store'), [
                'display_name' => 'My Cool Component',
                'source_type' => \App\Models\ComponentInstance::SOURCE_MANUAL,
                'condition_code' => \App\Models\ComponentInstance::CONDITION_GOOD,
                'storage_location_id' => $stock->id,
                'notes' => 'Bench intake',
            ]);

        $component = \App\Models\ComponentInstance::query()->where('display_name', 'My Cool Component')->first();

        $response->assertRedirect(route('components.show', $component))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('component_instances', [
            'display_name' => 'My Cool Component',
            'storage_location_id' => $stock->id,
            'status' => \App\Models\ComponentInstance::STATUS_IN_STOCK,
        ]);
    }
}
