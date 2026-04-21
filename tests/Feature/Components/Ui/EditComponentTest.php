<?php

namespace Tests\Feature\Components\Ui;

use App\Models\ComponentInstance;
use App\Models\User;
use Tests\TestCase;

class EditComponentTest extends TestCase
{
    public function testPageRedirectsUntilEditUiExists(): void
    {
        $component = ComponentInstance::factory()->create();

        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('components.edit', $component))
            ->assertRedirect(route('components.show', $component))
            ->assertSessionHas('info');
    }
}
